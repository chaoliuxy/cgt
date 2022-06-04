<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Coupons;

/**
 * 场馆VIP接口
 */
class Venuevip extends Api
{
    protected $noNeedLogin = ['text'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 场馆VIP数据
     */
    public function venuevip()
    {
        $list = db('venue_vip')->where('venue_id', $this->auth->venue_id)->find();
        $paytype_ids = explode(',', $list['paytype_ids']);
        $paytypeids = [];
        foreach ($paytype_ids as &$v) {
            array_push($paytypeids, $v);
        }
        unset($list['paytype_ids']);
        $list['discount'] = $list['discount'];
        $list['paytypeids'] = db('paytype')->where('id', 'in', $paytypeids)->field('id,name,image')->select();
        foreach ($list['paytypeids'] as &$v) {
            $v['image'] = cdnurl($v['image'], true);
        }
        $list['list'][0]['id'] = $list['id'];
        $list['list'][0]['name'] = '月卡';
        $list['list'][0]['type'] = '10';
        $list['list'][0]['price'] = $list['month_cost'];
        $list['list'][0]['venue_id'] = $list['venue_id'];
        $list['list'][0]['discount'] = $list['discount'];

        $list['list'][1]['id'] = $list['id'];
        $list['list'][1]['name'] = '季卡';
        $list['list'][1]['type'] = '20';
        $list['list'][1]['price'] = $list['quarter_cost'];
        $list['list'][1]['venue_id'] = $list['venue_id'];
        $list['list'][1]['discount'] = $list['discount'];

        $list['list'][2]['id'] = $list['id'];
        $list['list'][2]['name'] = '年卡';
        $list['list'][2]['type'] = '30';
        $list['list'][2]['price'] = $list['year_cost'];
        $list['list'][2]['venue_id'] = $list['venue_id'];
        $list['list'][2]['discount'] = $list['discount'];

        unset($list['month_cost']);
        unset($list['quarter_cost']);
        unset($list['year_cost']);
        unset($list['venue_id']);
        unset($list['discount']);
        $list['icon_text'] = '加入黑金会员，立享四大特权';
        $this->success('ok', $list);
    }

    /**
     * 购买会员
     * @param $param [''venuevip_id'] 场馆VIPid
     * @param $param ['type'] 类型:10:月卡；20:季卡；30：年卡；
     */
    public function payvip()
    {
        $param = $this->request->param();
        if ($param['discount_price']) {
            $discount_price = $param['discount_price'];
        }
        if ($param['coupons_id']) {
            $coupons_id = $param['coupons_id'];
        }
        unset($param['discount_price']);
        unset($param['coupons_id']);
        if (!$param['venuevip_id']) {
            $this->error('请选择所属场馆VIP');
        }
        if (!in_array($param['type'], [10, 20, 30])) {
            $this->error('购买类型参数错误');
        }
        $param['venuevip_id'] = db('venue_vip')->where('venue_id', $this->auth->venue_id)->value('id');
        $param['user_id'] = $this->auth->id;
        $param['venue_id'] = $this->auth->venue_id;
        if ($param['type'] == '10') {
            # 月卡
            $param['amount'] = db('venue_vip')->where(['venue_id' => $this->auth->venue_id])->value('month_cost');
            $param['endtime'] = strtotime("+1 month");
        } elseif ($param['type'] == '20') {
            # 季卡
            $param['amount'] = db('venue_vip')->where(['venue_id' => $this->auth->venue_id])->value('quarter_cost');
            $param['endtime'] = strtotime("+3 month");
        } else {
            # 年卡
            $param['amount'] = db('venue_vip')->where(['venue_id' => $this->auth->venue_id])->value('year_cost');
            $param['endtime'] = strtotime("+12 month");
        }
        unset($param['token']);
        $param['order_no'] = date('Ymd') . substr(implode(null, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        $param['createtime'] = time();
        $data['discount_price'] = $discount_price;
        if (isset($coupons_id) && $coupons_id) {
            $status = db('receive')->where(['coupons_id' => $coupons_id, 'user_id' => $this->auth->id])->value('status');
            if ($status != 10) {
                $this->error('优惠券已使用或已过期');
            }
            $pay_price = $discount_price;
            $discount_price = $param['amount'] - $pay_price;
        } else {
            $pay_price = $param['amount'];
            $coupons_id = 0;
            $discount_price = 0;
        }
        $add = db('user_vip_log')->insertGetId($param);#添加购买VIP订单
        $data = [
            'order_no' => date('Ymd') . substr(implode(null, array_map('ord', str_split(substr(uniqid(), 7, 12), 1))), 0, 7),
            'pay_price' => $pay_price,
            'pay_status' => '10',
            'user_id' => $this->auth->id,
            'pay_type' => '10',
            'createtime' => time(),
            'updatetime' => time(),
            'order_ids' => $add,
            'shop_id' => $this->auth->venue_id,
            'venue_id' => $this->auth->venue_id,
            'order_type' => '60',
            'coupons_id' => $coupons_id,
            'total_discount_price' => $discount_price,
            'discount_price' => $discount_price,
            'discount_vip_price' => 0,
        ];
        $add = db('order')->insertGetId($data);#添加购买VIP订单
        if ($add) {
            //合并成功发起支付
            $amount = $param['amount'];
            if (!$amount || $amount < 0) {
                $this->error("支付金额必须大于0");
            }
            $amount = 0.01;
            $method = 'miniapp';
            $type = 'wechat';
            $openid = db('third')->where('user_id', $this->auth->id)->value('openid');
            //订单号
            $out_trade_no = $data['order_no'];
            //订单标题
            $title = '场馆通';
            //回调链接
            $notifyurl = $this->request->root(true) . '/addons/epay/api/notifyx/type/' . $type;
            $returnurl = $this->request->root(true) . '/addons/epay/api/returnx/type/' . $type . '/out_trade_no/' . $out_trade_no;
            $arr = \addons\epay\library\Service::submitOrder($amount, $out_trade_no, $type, $title, $notifyurl, $returnurl, $method, $openid);
            if ($type == 'wechat') {
                $data = json_decode($arr);
                $data->order_id = $add;
                $this->success('ok', $data);
            } else {
                $str['sign'] = $arr;
                $this->success('ok', $str);
            }
        } else {
            $this->error('订单创建失败');
        }
        $this->success('ok', $param);
    }

    /**
     * 检查是否有符合条件的券
     */
    public function chckekcoupon()
    {
        $param = $this->request->param();
        if (!$param['goods_price']) {
            $this->error('参数不全');
        }
        if (!$param['type']) {
            $param['type'] = 0;
        }
        if (!$param['reservation_id']) {
            $param['reservation_id'] = 0;
        }
        $coupons = new Coupons();
        $data = json_decode($coupons->see_coupons($param['type'], $param['goods_price'], $param['reservation_id'], $this->auth->id), true);
        $this->success('ok', $data);
    }
}
