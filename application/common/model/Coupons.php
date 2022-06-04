<?php

namespace app\common\model;

use think\Model;

/**
 * 优惠券
 */
class Coupons extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [
    ];

    /**
     * 确认订单页面查看是否有相关优惠券使用
     * @param $type 订单类型:20=订场,30=订票,40=购物,50=活动
     * @param $goods_price 价格
     * @param $reservation_id 场馆ID【购物时可不传】
     */
    public function see_coupons($type='',$goods_price='',$reservation_id='',$user_id=''){
      db('receive')->where('status','10')->where('endtime','<=',time())->update(['status'=>'30']);
      $scene_type = '10,'.$type;
      $scenetype = explode(',',$scene_type);
      if ($reservation_id) {
       $reservation_ids = '0,'.$reservation_id;
      }else{
       $reservation_ids = '0';
      }
      $reservationids = explode(',',$reservation_ids);
       $list['list'] = db('receive')
                       ->alias('r')
                       ->join('coupons c','c.id = r.coupons_id');
      $list['count'] = db('receive')
      ->alias('r')
      ->join('coupons c','c.id = r.coupons_id');
      if (in_array($type,[10,20])) {
        $list['list'] = $list['list']->where('r.scene_type','in',$scenetype)->where('r.reservation_ids','in',$reservationids);
        $list['count'] = $list['count']->where('r.scene_type','in',$scenetype)->where('r.reservation_ids','in',$reservationids);
      }else{
        $list['list'] = $list['list']->where('r.scene_type','in',$scenetype);
        $list['count'] = $list['count']->where('r.scene_type','in',$scenetype);
      }
      $list['list'] = $list['list']
                      ->where('c.use_condition','<=',$goods_price)
                      ->where('r.status','10')
                      ->where('r.user_id',$user_id)
                      ->field('c.*')
                      ->order('r.createtime DESC')
                      ->group('r.id')
                      ->select();
      $list['count'] = $list['count']
                      ->where('c.use_condition','<=',$goods_price)
                      ->where('r.status','10')
                      ->where('r.user_id',$user_id)
                      ->order('r.createtime DESC')
                      ->group('r.id')
                      ->count();
      foreach ($list['list'] as &$v) {
        $v['coupons_image'] = cdnurl($v['coupons_image'],true);
        if ($v['coupons_type']=='10') {
          $v['coupons_type_text'] = '代金券';
        }elseif ($v['coupons_type']=='20') {
          $v['coupons_type_text'] = '折扣券';
        }
        if ($v['coupons_type']=='10') {
          $v['coupons_type_text'] = '满减券';
        }
        $v['time'] = $v['startime'].'-'.$v['endtime'];
        $v['coupons_content'] = strip_tags($v['coupons_content']);
        unset($v['startime']);
        unset($v['endtime']);
      }
      return json_encode($list,true);
    }
}

