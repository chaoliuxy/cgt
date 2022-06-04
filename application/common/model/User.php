<?php

namespace app\common\model;

use think\Model;
use think\Db;

/**
 * 会员模型
 */
class User extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [
        'url',
    ];

    /**
     * 获取个人URL
     * @param   string $value
     * @param   array  $data
     * @return string
     */
    public function getUrlAttr($value, $data)
    {
        return "/u/" . $data['id'];
    }

    /**
     * 获取头像
     * @param   string $value
     * @param   array  $data
     * @return string
     */
    public function getAvatarAttr($value, $data)
    {
        if (!$value) {
            //如果不需要启用首字母头像，请使用
            //$value = '/assets/img/avatar.png';
            $value = letter_avatar($data['nickname']);
        }
        return $value;
    }

    /**
     * 获取会员的组别
     */
    public function getGroupAttr($value, $data)
    {
        return UserGroup::get($data['group_id']);
    }

    /**
     * 获取验证字段数组值
     * @param   string $value
     * @param   array  $data
     * @return  object
     */
    public function getVerificationAttr($value, $data)
    {
        $value = array_filter((array)json_decode($value, true));
        $value = array_merge(['email' => 0, 'mobile' => 0], $value);
        return (object)$value;
    }

    /**
     * 设置验证字段
     * @param mixed $value
     * @return string
     */
    public function setVerificationAttr($value)
    {
        $value = is_object($value) || is_array($value) ? json_encode($value) : $value;
        return $value;
    }

    /**
     * 变更会员余额
     * @param int    $money   余额
     * @param int    $user_id 会员ID
     * @param string $memo    备注
     */
    public static function money($money, $user_id, $memo)
    {
        $user = self::get($user_id);
        if ($user && $money != 0) {
            $before = $user->money;
            //$after = $user->money + $money;
            $after = function_exists('bcadd') ? bcadd($user->money, $money, 2) : $user->money + $money;
            //更新会员信息
            $user->save(['money' => $after]);
            //写入日志
            MoneyLog::create(['user_id' => $user_id, 'money' => $money, 'before' => $before, 'after' => $after, 'memo' => $memo]);
        }
    }

    /**
     * 变更会员积分
     * @param int    $score   积分
     * @param int    $user_id 会员ID
     * @param string $memo    备注
     */
    public static function score($score, $user_id, $memo)
    {
        $user = self::get($user_id);
        if ($user && $score != 0) {
            $before = $user->score;
            $after = $user->score + $score;
            $level = self::nextlevel($after);
            //更新会员信息
            $user->save(['score' => $after, 'level' => $level]);
            //写入日志
            ScoreLog::create(['user_id' => $user_id, 'score' => $score, 'before' => $before, 'after' => $after, 'memo' => $memo]);
        }
    }

    /**
     * 根据积分获取等级
     * @param int $score 积分
     * @return int
     */
    public static function nextlevel($score = 0)
    {
        $lv = array(1 => 0, 2 => 30, 3 => 100, 4 => 500, 5 => 1000, 6 => 2000, 7 => 3000, 8 => 5000, 9 => 8000, 10 => 10000);
        $level = 1;
        foreach ($lv as $key => $value) {
            if ($score >= $value) {
                $level = $key;
            }
        }
        return $level;
    }

    /**
     * 支付成功后的操作
     */
    public function paysuccess($out_trade_no=''){
        $data = db('order')->where('order_no', $out_trade_no)->field('pay_status,order_type,order_ids,coupons_id,user_id,groupbuying,collage_sign,group_work_number')->find();
        if ($data['pay_status']=='10') {
        // 启动事务
        Db::startTrans();
        try{
            $captcha = $this->get_code();
            db('order')->where('order_no', $out_trade_no)->update(['pay_status'=>'20','pay_time'=>time(),'updatetime'=>time(),'captcha'=>$captcha]);
            if ($data['coupons_id']) {
                # 修改优惠券的状态
                db('receive')->where(['user_id'=>$data['user_id'],'coupons_id'=>$data['coupons_id']])->update(['status'=>'20']);
            }
            //修改单个订单状态
                // 课程、商品需要减库存
                $order_ids = db('order')->where('order_no', $out_trade_no)->value('order_ids');
                $orderids = explode(',', $order_ids);
                $datas = db('litestore_order')->where('id', 'in', $orderids)->field('id,collage_sign,group_work_number,groupbuying')->select();//商品
            if (in_array($data['order_type'],['10','30','50'])) {
                if ($datas) {
                    foreach ($datas as $v) {
                        db('litestore_order')->where('id', $v['id'])->update(['pay_status'=>'20','pay_time'=>time(),'status'=>'20']);
                    }
                }
            }elseif($data['order_type']=='20'){
                if ($datas) {
                foreach ($datas as $v) {
                    $v['captcha'] = $this->get_code();
                    if ($v['groupbuying']=='20') {
                        //处理活动拼团
                        db('litestore_order')->where('id', $v['id'])->update(['pay_status'=>'20','pay_time'=>time(),'status'=>'100','captcha'=>$v['captcha']]);
                    }else{
                        db('litestore_order')->where('id', $v['id'])->update(['pay_status'=>'20','pay_time'=>time(),'status'=>'20','captcha'=>$v['captcha']]);
                    }
                }
                }
            }else{
                if ($data['groupbuying']=='20') {
                    db('signup')->where('id', $data['order_ids'])->update(['status'=>'40']);
                }else{
                    db('signup')->where('id', $data['order_ids'])->update(['status'=>'20']);
                }
            }
            if (in_array($data['order_type'],['20','30'])) {
                //减少商品库存、增加销量
                $goods_spec_ids = db('litestore_order_goods')->where('order_id', 'in', $orderids)->field('goods_spec_id,total_num,goods_id,order_id,user_id,goods_price,goods_name,images')->select();//
                foreach ($goods_spec_ids as $k => $v) {
                    $deduct_stock_type = db('litestore_goods')->where('goods_id', $v['goods_id'])->value('deduct_stock_type');
                    if ($deduct_stock_type=='20') {
                        db('litestore_goods_spec')->where('goods_spec_id', $v['goods_spec_id'])->setDec('stock_num', $v['total_num']);//减库存
                    }
                    db('litestore_goods')->where('goods_id', $v['goods_id'])->setInc('sales_actual', $v['total_num']);//增加销量
                }
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        }
}

//   public function get_code($nums = 1 ,$codelength = 6 ,$format = '' ,$type = 'json' )
//   {
//       $mcode = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
//       $mcode_len = strlen($mcode);
//       $rs = array();
//       for($i=0;$i<$nums;)
//       {
//           $code = '';
//           for($j=0;$j<$codelength;$j++)
//           {
//               $str_len = rand(0,$mcode_len-1);
//               $str = substr($mcode,$str_len,1);
//               $code .=$str;
//           }
//           $d = in_array($code,$rs);
//           if(!$d){
//               $rs[] = $format.$code;
//               $i++;
//           }
//       }
//       if($type =='array')
//       return $rs;
//       else
//       return json_encode($rs[0]);
//   }

  public function get_code() {
    list($t1, $t2) = explode(' ', microtime());
    return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
  }

    /**
     * 处理场馆VIP订单支付结果
     * 1、增加用户所属场馆VIP记录【包括到期时间等】
     * 2、
     */
    public function handleorder($out_trade_no=''){
        $order = db('order')->where('order_no',$out_trade_no)->find(); # 订单信息
        $userviplog = db('user_vip_log')->where('id',$order['order_ids'])->find();
        $uservip = db('user_vip')->where(['venuevip_id'=>$userviplog['venuevip_id'],'venue_id'=>$userviplog['venue_id'],'user_id'=>$userviplog['user_id']])->find(); # 查询当前用户是否开通过VIP
        if ($order['pay_status']=='20') {
            $this->error('订单已支付');
        }
         // 启动事务
         Db::startTrans();
         try{
             if ($uservip['id'] && $uservip['endtime']>time()) {
                 # 当前用户开通过且未过期
                  if ($userviplog['type']=='10') {
                      # 月卡
                      $data['endtime'] = strtotime(date('Y-m-d H:i:s',strtotime("+1month",$uservip['endtime'])));
                  }elseif($userviplog['type']=='20'){
                      # 季卡
                      $data['endtime'] = strtotime(date('Y-m-d H:i:s',strtotime("+3month",$uservip['endtime'])));
                  }else{
                      # 年卡
                      $data['endtime'] = strtotime(date('Y-m-d H:i:s',strtotime("+12month",$uservip['endtime'])));
                  }
                  $update = db('user_vip')->where('id',$uservip['id'])->update(['endtime'=>$data['endtime'],'updatetime'=>time()]);
             }else{
                  if ($userviplog['type']=='10') {
                      # 月卡
                      $data['endtime'] = strtotime("+1 month");
                  }elseif($userviplog['type']=='20'){
                      # 季卡
                      $data['endtime'] = strtotime("+3 month");
                  }else{
                      # 年卡
                      $data['endtime'] = strtotime("+12 month");
                  }
                  $data['venuevip_id'] = $userviplog['venuevip_id'];
                  $data['venue_id'] = $userviplog['venue_id'];
                  $data['user_id'] = $userviplog['user_id'];
                  $data['updatetime'] = time();
                  $data['createtime'] = time();
                  $update = db('user_vip')->insert($data);
             }
             if ($order['coupons_id']) {
                 db('receive')->where('coupons_id',$order['coupons_id'])->update(['status'=>'20','usetime'=>time(),'updatetime'=>time()]);
             }
             db('order')->where('order_no',$out_trade_no)->update(['pay_status'=>'20','pay_time'=>time()]); # 更新订单状态
             db('user_vip_log')->where('id',$order['order_ids'])->update(['status'=>'20','paytime'=>time()]); # 更新订单状态
             // 提交事务
             Db::commit();
         } catch (\Exception $e) {
             // 回滚事务
             Db::rollback();
             $this->error($e->getMessage());
         }
     }
}
