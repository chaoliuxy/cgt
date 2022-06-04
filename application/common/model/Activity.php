<?php

namespace app\common\model;

use app\common\model\Coupons as couponsmodel;
use think\Model;

/**
 * 会员模型
 */
class Activity extends Model
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
     * 活动列表
     */
    public function list($venue_id='',$showpage='',$page='',$type='',$status=''){
      $star = db('activity')->where(['venue_id'=>$venue_id,'status'=>'10'])->where('startime','<=',time())->field('id')->select();
      $end = db('activity')->where(['venue_id'=>$venue_id,'status'=>'10'])->where('endtime','<',time())->field('id')->select();
      if ($star) {
         foreach ($star as $v) {
           db('activity')->where('id',$v['id'])->update(['status'=>'20','updatetime'=>time()]);
         }
      }
      if ($end) {
        foreach ($end as $v) {
          db('activity')->where('id',$v['id'])->update(['status'=>'30','updatetime'=>time()]);
        }
     }
     if ($type=='10') {
       //热门
      $list['list'] = db('activity')->where('venue_id',$venue_id)->where('hot_status','20')->field('id,images,name,createtime,type,price')->limit($showpage)->page($page)->order('createtime DESC')->select();
      $list['count'] = db('activity')->where('venue_id',$venue_id)->where('hot_status','20')->count();
    }elseif($type=='20'){
      // 最新推荐
      $list['list'] = db('activity')->where('venue_id',$venue_id)->where('recommend_status','20')->field('id,images,name,createtime,type,price')->limit($showpage)->page($page)->order('createtime DESC')->select();
      $list['count'] = db('activity')->where('venue_id',$venue_id)->where('recommend_status','20')->count();
    }else{
      $list['list'] = db('activity')->where('venue_id',$venue_id)->field('id,images,name,createtime,type,price')->limit($showpage)->page($page)->order('createtime DESC')->select();
      $list['count'] = db('activity')->where('venue_id',$venue_id)->count();
    }
    if ($status) {
      # 团购活动
      $list['list'] = db('activity')->where('venue_id',$venue_id)->where('pay_type','10')->field('id,images,name,createtime,type,group_price,group_work_number,group_work_time,price')->limit($showpage)->page($page)->order('createtime DESC')->select();
      $list['count'] = db('activity')->where('venue_id',$venue_id)->where('pay_type','10')->count();
    }
      foreach ($list['list'] as &$v) {
        $v['images'] = cdnurl(explode(',',$v['images'])[0],true);
        $v['createtime'] = date('Y/m/d',$v['createtime']);
      }
      $list['total_page'] = ceil($list['count']/$showpage);
      return json_encode($list,true);
    }

    /**
     * 活动详情
     */
    public function details($activity_id='',$user_id='',$venue_id=''){
      $couponsmodel = new couponsmodel();
      $data = db('activity')->where('id',$activity_id)->find();
      if (!$data) {
        return '';
      }
      $data['images'] = explode(",", $data['images']);
      foreach ($data['images'] as &$v) {
        $v = cdnurl($v, true);
      }
      if ($data['type']=='10') {
        $data['type_text'] = '免费';
      }else{
        $data['type_text'] = '收费';
      }
      if ($data['status']=='10') {
        $data['status_text'] = '报名中';
      }elseif($data['status']=='20'){
        $data['status_text'] = '活动进行中';
      }else{
        $data['status_text'] = '已结束';
      }
      $uservip = db('user_vip')->where(['user_id'=>$user_id,'venue_id'=>$venue_id])->where('endtime','>',time())->find();
      $venuevip = db('venue_vip')->where('id',$uservip['venuevip_id'])->find();
      $paytype = explode(',',$venuevip['paytype_ids']);
      if (in_array('2',$paytype)) {
        $discount = $venuevip['discount']/10;
        $data['discount_vip_price'] = sprintf("%.2f",$data['price'] - $data['price'] * $discount);
        $data['price'] = $data['price'] * $discount;
      }else{
        $data['discount_vip_price'] = 0;
      }
      $data['content'] = replacePicUrl($data['content'], config('fastadmin.url'));
      $data['groupon'] = db('goods_groupon')
      ->alias('g')
      ->join('goods_groupon_log l','g.id = l.groupon_id')
      ->where('l.goods_id',$data['id'])
      ->where('g.status','ing')
      ->field('g.id,g.status,g.expiretime,g.num,g.current_num')->group('g.id')->order('g.createtime DESC')->select();
      $data['coupons'] = json_decode($couponsmodel->see_coupons('50',$data['price'],'',$user_id),true);
      return json_encode($data,true);
    }

}

