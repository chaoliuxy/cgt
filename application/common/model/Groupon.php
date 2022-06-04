<?php

namespace app\common\model;

use app\common\model\ScoreLog;
use app\common\model\MoneyLog;
use addons\epay\library\Service;
use Yansongda\Pay\Pay;
use think\Db;
use think\Model;
use think\Log;

class Groupon extends Model
{
    /**
     * 支付成功真实加入团
     */
    public function joinGroupon($order, $user)
    {
        if ($order['is_head']=='10') {
            // 加入旧团，查询团 拼团
            if ($order['collage_sign']) {
                # 指定团参与
                $groupon = db('goods_groupon')->where('id', $order['collage_sign'])->field('num,current_num,status')->find();
                if ($groupon['status']=='ing') {
                    $goodsgroupon = $order['collage_sign'];
                } else {
                    $goodsgroupon = 0;
                }
            }
        } else {
            // 创建团  开团
            $goodsgroupon = $this->joinNewGroupon($order, $user);
        }
        // 添加参团记录
        $goodsGrouponLog = $this->addGrouponLog($order, $user, $goodsgroupon);
        return $this->checkGrouponStatus($goodsgroupon, $order);//检查团状态
    }



    /**
     * 支付成功开启新拼团
     */
    public function joinNewGroupon($order, $user)
    {
        // 获取商品拼团信息
        if ($order['order_type']=='20') {
            $goods_id = db('litestore_order_goods')->where('order_id', $order['order_ids'])->value('goods_id');
            $goods = db('litestore_goods')->where('goods_id', $goods_id)->find();
        } else {
            $goods_id = db('signup')->where('id', $order['order_ids'])->value('activity_id as goods_id');
            $goods = db('activity')->where('id', $goods_id)->find();
        }
        // 转为 秒
        $expiretime = $goods['group_work_time'] * 3600;
        $expiretimes = strtotime(date('Y-m-d H:i:s', strtotime('+'.$goods['group_work_time'].'hour')));
        // $expiretime = 60;
        // $expiretimes = strtotime(date('Y-m-d H:i:s', strtotime('+1minute')));
        // 开团
        if ($order['order_type']=='20') {
            $groupbuy_status = '10';
        } elseif ($order['order_type']=='40') {
            $groupbuy_status = '20';
        }
        $data = [
          'user_id'=>$user['id'],
          'goods_id'=>$goods_id,
          'order_id'=>$order['id'],
          'num'=>$order['group_work_number'],
          'current_num'=>0,
          'status'=>'ing',
          'groupbuy_status'=>$groupbuy_status,
          'expiretime'=>$expiretimes,
          'createtime'=>time(),
          'updatetime'=>time(),
        ];
        $groupon_id = db('goods_groupon')->insertGetId($data);
        // 记录团 id
        db('order')->where('id', $order['id'])->update(['groupon_id'=>$groupon_id,'updatetime'=>time()]);
        if ($expiretime > 0) {
            // 增加自动关闭拼团队列(如果有虚拟成团，会判断虚拟成团)
            \think\Queue::later($expiretime, 'app\job\Goodsgroupon@expire', [
                'groupon_id' => $groupon_id
            ], 'Goodsgroupon');
        }
        return $groupon_id;
    }



    /**
     * 增加团成员记录
     */
    public function addGrouponLog($order, $user, $goodsgroupon)
    {
        if (!$goodsgroupon) {
            \think\Log::write('groupon-notfund: order_id: ' . $order['id']);
            return null;
        }
        // // 启动事务
        // Db::startTrans();
        // try{
        // 增加团成员数量
        $goodsgroupon = db('goods_groupon')->where('id', $goodsgroupon)->find();
        db('goods_groupon')->where('id', $goodsgroupon['id'])->setInc('current_num', 1);
        // 增加参团记录
        if ($order['order_type']=='20') {
            $goodsdata = db('litestore_order_goods')->where('order_id', $order['order_ids'])->field('goods_id,goods_spec_id')->find();
        } else {
            $goodsdata = db('signup')->where('id', $order['order_ids'])->field('activity_id as goods_id,id as goods_spec_id')->find();
        }
        $goodsGrouponLog = [
            'user_id'=>$user['id'],
            'user_nickname'=>$user['nickname'],
            'user_avatar'=>$user['avatar'],
            'groupon_id'=>$goodsgroupon['id'],
            'goods_id'=>$goodsdata['goods_id'],
            'goods_sku_price_id'=>$goodsdata['goods_spec_id']?$goodsdata['goods_spec_id']:0,
            'order_id'=>$order['id'],
            'is_leader'=>($goodsgroupon['user_id'] == $user['id']) ? 1 : 0,
            'createtime'=>time(),
            'updatetime'=>time(),
            ];
        $goodsGrouponLog_id = db('goods_groupon_log')->insertGetId($goodsGrouponLog);
        return $goodsGrouponLog_id;
        //     // 提交事务
        //     Db::commit();
        // } catch (\Exception $e) {
        //     // 回滚事务
        //     Db::rollback();
        //     Log::error($e->getMessage().'09');
        // }
    }



    /**
     * 检查团状态
     */
    public function checkGrouponStatus($goodsgroupon_id, $order)
    {
        if (!$goodsgroupon_id) {
            return true;
        }
        // 重新获取团信息
        $goodsgroupon = db('goods_groupon')->where('id', $goodsgroupon_id)->find();
        if ($goodsgroupon['current_num'] >= $goodsgroupon['num'] && !in_array($goodsgroupon['status'], ['finish'])) {
            // 将团设置为已完成
            db('goods_groupon')->where('id', $goodsgroupon['id'])->update(['status'=>'finish','finishtime'=>time()]);
            //并将对应的订单改为待发货
            # 将团设为已完成、并将对应订单设为待发货
            $order_type = db('order')->where('id', $goodsgroupon['order_id'])->value('order_type');
            $ids = db('goods_groupon_log')->where('groupon_id', $goodsgroupon['id'])->field('order_id')->select();
            $orderids = [];
            foreach ($ids as &$v) {
                array_push($orderids, $v['order_id']);
            }
            $orderids = db('order')->where('id', 'in', $orderids)->field('order_ids')->select();
            $idss = [];
            foreach ($orderids as &$v) {
                array_push($idss, $v['order_ids']);
            }
            if ($order['order_type']=='20') {
                # 课程
                db('litestore_order')->where('id', 'in', $idss)->update(['status'=>'20','updatetime'=>'time']);
                db('order')->where('order_ids','in',$idss)->update(['groupbuying_status'=>'30','updatetime'=>'time']);
            } elseif ($order_type=='40') {
                # 活动
                db('signup')->where('id', 'in', $idss)->update(['status'=>'20']);
                db('order')->where('order_ids','in',$idss)->update(['groupbuying_status'=>'30','updatetime'=>'time']);
            }
        }
    }

    public function refund($order_no, $price, $order_id)
    {
        // $data = [
        //     'total_fee' => intval($price * 100), //订单金额  单位 转为分
        //     'refund_fee' => intval($price * 100), //退款金额 单位 转为分
        //     'sign_type' => 'MD5', //签名类型 支持HMAC-SHA256和MD5，默认为MD5
        //     'out_trade_no' => $order_no, //商户订单号
        //     'out_refund_no' => $order_no, //商户退款单号
        //     ];
        $data = [
            'total_fee' => 1, //订单金额  单位 转为分
            'refund_fee' => 1, //退款金额 单位 转为分
            'sign_type' => 'MD5', //签名类型 支持HMAC-SHA256和MD5，默认为MD5
            'out_trade_no' => $order_no, //商户订单号
            'out_refund_no' => $order_no, //商户退款单号
            ];
        $wechat = Service::getConfig('wechat');
        $data['sign'] = self::getSign($data, $wechat['key']);
        $pay = Pay::wechat($wechat);
        $row = $pay->refund($data);
        $row = json_decode($row);
        if ($row->return_code == 'SUCCESS') {
            $coupons_id = db('order')->where('order_no', $order_no)->value('coupons_id');
            if ($coupons_id) {
                db('receive')->where(['user_id'=>$this->auth->id,'coupons_id'=>$coupons_id])->update(['status'=>'10']);
            }
            db('litestore_order')->where('id', $order_id)->update(['status'=>'110']);//修改订单为团失败
        } else {
            Log::error('微信退款失败'.$row);
        }
    }

    public static function getSign($params, $key)
    {
        ksort($params, SORT_STRING);
        $unSignParaString = self::formatQueryParaMap($params, false);
        $signStr = strtoupper(md5($unSignParaString . "&key=" . $key));
        return $signStr;
    }

    protected static function formatQueryParaMap($paraMap, $urlEncode = false)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if (null != $v && "null" != $v) {
                if ($urlEncode) {
                    $v = urlencode($v);
                }
                $buff .= $k . "=" . $v . "&";
            }
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }
}
