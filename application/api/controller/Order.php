<?php

namespace app\api\controller;

use app\common\controller\Api;
use addons\litestore\model\Litestoreorder;
use think\Cache;
use addons\litestore\model\CacheCart as cart;
use addons\qrcode\controller\Index as qrcode;
use think\Db;
use app\common\model\Order as ordermodel;
use app\common\model\Coupons as couponsmodel;

/**
 * 订单接口
 */
class Order extends Api
{
    protected $noNeedLogin = ['callback_for_wxapp', 'callback_for_wxgzh'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new Litestoreorder;
        $user = $this->auth->getUser();
        $this->order = new ordermodel();
        $this->couponsmodel = new couponsmodel();
    }

    /**
     * 确认订单
     * @param $param ['reservation_id'] 场馆ID
     * @param $param ['date_id'] 场次ID
     * @param $param ['time'] 预定日期
     * @param $param ['time_slot'] 场次时间段
     * @param $param ['total_price'] 价格
     * @param $param ['date'] 场地
     * @param $param ['groupbuying'] 是否开团 10=否；20=是
     */
    public function confirmorder()
    {
        $param = $this->request->param();
        if (!$param['reservation_id'] || !$param['total_price'] || !$param['groupbuying'] || !$param['specs']) {
            $this->error('参数不全');
        }
        $param['specs'] = preg_replace("/(\s|\&quot\;|　|\xc2\xa0)/", '"', strip_tags($param['specs']));
        $arr = json_decode($param['specs'], true);
        foreach ($arr as $v) {
            $v['num'] = strpos($v['time_slot'], '-');
            $v['str'] = mb_substr($v['time_slot'], 0, $v['num']);
            $v['time'] = $v['time'] . ' ' . $v['str'];
            if (strtotime($v['time']) <= strtotime(date('Y-m-d H:i', time()))) {
                $this->error('当前所选场次中存在已过期的');
            }
        }
        $reservation_name = db('reservation')->where('id', $param['reservation_id'])->value('name');
        $coupons = json_decode($this->couponsmodel->see_coupons(20, $param['total_price'], $param['reservation_id'], $this->auth->id), true);
        //订单数据
        $uservip = db('user_vip')->where(['user_id' => $this->auth->id, 'venue_id' => $this->auth->venue_id])->where('endtime', '>', time())->find();
        $venuevip = db('venue_vip')->where('id', $uservip['venuevip_id'])->find();
        $paytype = explode(',', $venuevip['paytype_ids']);
        // 商品总价
        if (in_array('3', $paytype)) {
            $discount = $venuevip['discount'] / 10;
            $discount_vip_price = sprintf("%.2f", $param['total_price'] - $param['total_price'] * $discount);
            $param['total_price'] = $totalPrice = $param['total_price'] * $discount;
        } else {
            $discount_vip_price = 0;
        }
        $data = [
            'reservation_name' => $reservation_name,
            'total_price' => $param['total_price'],
            'mobile' => $this->auth->mobile,
            'reservation_id' => $param['reservation_id'],
            'groupbuying' => $param['groupbuying'],
            'specs' => json_decode($param['specs'], true),
            'coupons' => $coupons,
            'discount_vip_price' => $discount_vip_price,
        ];
        $this->success('ok', $data);
    }

    /**
     * 订场提交订单并支付
     */
    public function paydcorder()
    {
        $param = $this->request->param();
        if (!$param['reservation_id'] || !$param['total_price'] || !$param['groupbuying'] || !$param['specs']) {
            $this->error('参数不全');
        }
        $order_id = $this->order->dcorder($param['reservation_id'], $param['total_price'], $param['specs'], $param['groupbuying'], $this->auth->mobile, $this->auth->id, $this->auth->venue_id);
        $order_no = date('Ymd') . substr(implode(null, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        $data = [
            'order_no' => $order_no,
            'pay_price' => $param['total_price'],
            'pay_status' => '10',
            'user_id' => $this->auth->id,
            'pay_type' => '10',
            'createtime' => time(),
            'updatetime' => time(),
            'order_ids' => $order_id,
            'shop_id' => $param['reservation_id'],
            'order_type' => '10',
        ];
        if (isset($param['coupons_id']) && $param['coupons_id']) {
            $status = db('receive')->where(['coupons_id' => $param['coupons_id'], 'user_id' => $this->auth->id])->value('status');
            if ($status != '10') {
                $this->error('优惠券已使用或已过期');
            }
            $data['discount_price'] = $param['discount_price'];
            $data['coupons_id'] = $param['coupons_id'];
        } else {
            $data['discount_price'] = 0;
            $data['coupons_id'] = 0;
        }
        if ($param['discount_vip_price'] == 'undefined') {
            $param['discount_vip_price'] = 0;
        } else {
            $data['discount_vip_price'] = $param['discount_vip_price'];
        }
        $data['venue_id'] = $this->auth->venue_id;
        $data['total_discount_price'] = ($data['discount_price'] + $param['discount_vip_price']) ?? 0;
        $data['venue_id'] = $this->auth->venue_id;
        $add = db('order')->insertGetId($data);//合并订单
        if ($add) {
            //合并成功发起支付
            $amount = $param['total_price'];
            if (!$amount || $amount < 0) {
                $this->error("支付金额必须大于0");
            }
            $amount = 0.01;
            $method = 'miniapp';
            $type = 'wechat';
            $openid = db('third')->where('user_id', $this->auth->id)->value('openid');
            //订单号
            $out_trade_no = $order_no;
            //订单标题
            $title = '场馆通订场';
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
        $this->success('ok');
    }

    /**
     * 订单列表
     * 订单类型:10=订场订单,20=订票订单,30=购物订单,40=全部订单
     */
    public function orderlist()
    {
        $type = $this->request->request('type');
        $key = $this->request->request('key') ?? '';
        if (!in_array($type, [10, 20, 30, 40, 50])) {
            $this->error('类型参数错误');
        }
        $user_id = $this->auth->id;
        if ($type == '10') {
            # 订场
            $list['list'] = db('order')->where('order_no', 'like', '%' . $key . '%')->where('pay_status', '<>', '10')->where(['order_type' => '10', 'user_id' => $user_id])->field('id,pay_price,pay_status,createtime,order_ids,shop_id,order_type')->order('createtime DESC')->select();
        } elseif ($type == '20') {
            # 订票
            $list['list'] = db('order')->where('order_no', 'like', '%' . $key . '%')->where('pay_status', '<>', '10')->where(['order_type' => '20', 'user_id' => $user_id])->field('id,pay_price,pay_status,createtime,order_ids,shop_id,order_type')->order('createtime DESC')->select();
        } elseif ($type == '30') {
            # 购物
            $list['list'] = db('order')->where('order_no', 'like', '%' . $key . '%')->where('pay_status', '<>', '10')->where(['order_type' => '30', 'user_id' => $user_id])->field('id,pay_price,pay_status,createtime,order_ids,shop_id,order_type')->order('createtime DESC')->select();
        } elseif ($type == '50') {
            # 购物
            $list['list'] = db('order')->where('order_no', 'like', '%' . $key . '%')->where('pay_status', '<>', '10')->where(['order_type' => '50', 'user_id' => $user_id])->field('id,pay_price,pay_status,createtime,order_ids,shop_id,order_type')->order('createtime DESC')->select();
        } else {
            $list['list'] = db('order')->where('order_no', 'like', '%' . $key . '%')->where('pay_status', '<>', '10')->where('order_type', 'in', ['10', '30', '50'])->where(['user_id' => $user_id])->field('id,pay_price,pay_status,createtime,order_ids,shop_id,order_type')->order('createtime DESC')->select();
        }
        foreach ($list['list'] as $k => &$v) {
            $v['order_ids'] = explode(',', $v['order_ids']);
            $v['createtime'] = date('Y-m-d H:i', $v['createtime']);
            $v['goods_list'] = db('litestore_order_goods')->where('order_id', 'in', $v['order_ids'])->where('user_id', $user_id)->field('id as litestore_order_goods_id,reservation_id,date,order_id,goods_id,goods_name,images,total_num,goods_attr,goods_price,total_price,total_num')->select();
            $v['total_num'] = array_sum(array_column($v['goods_list'], 'total_num'));
            unset($v['order_ids']);
            foreach ($v['goods_list'] as $kk => &$vv) {
                $vv['images'] = cdnurl(explode(',', $vv['images'])[0], true);
                if ($v['order_type'] == '10') {
                    #订场
                    $vv['lamp_status'] = db('lamplist')->where(['reservation_id' => $vv['reservation_id'], 'field_name' => $vv['date']])->value('status');
                }
            }
            #状态:10=待支付,20=已支付,30=待核销,40=已核销,50=待收货,60=已收货,70=待评价,80=已评价,90=已完成,100=待成团,110=拼团失败
            if ($v['pay_status'] == '10') {
                $v['pay_status_text'] = '未支付';
            } elseif ($v['pay_status'] == '20') {
                $v['pay_status_text'] = '已支付';
            } elseif ($v['pay_status'] == '30') {
                $v['pay_status_text'] = '待核销';
            } elseif ($v['pay_status'] == '40') {
                $v['pay_status_text'] = '已核销';
            } elseif ($v['pay_status'] == '50') {
                $v['pay_status_text'] = '待收货';
            } elseif ($v['pay_status'] == '60') {
                $v['pay_status_text'] = '已收货';
            } elseif ($v['pay_status'] == '70') {
                $v['pay_status_text'] = '待评价';
            } elseif ($v['pay_status'] == '80') {
                $v['pay_status_text'] = '已评价';
            } elseif ($v['pay_status'] == '90') {
                $v['pay_status_text'] = '已完成';
            } elseif ($v['pay_status'] == '100') {
                $v['pay_status_text'] = '待成团';
            } else {
                $v['pay_status_text'] = '拼团失败';
            }
            if ($v['order_type'] == '10') {
                # 订场
                $v['shop_name'] = db('venue')->where('id', $v['shop_id'])->value('name');
            } elseif ($v['order_type'] == '20') {
                # 订票
                $v['shop_name'] = db('reservation')->where('id', $v['shop_id'])->value('name');
            } elseif ($v['order_type'] == '30') {
                # 购物
                $v['shop_name'] = db('business')->where('id', $v['shop_id'])->value('name');
            }
        }
        $this->success('ok', $list);
    }

    /**
     * 购物车生成订单并预览
     */
    public function cart_pay()
    {
        $param = $this->request->param();
        $goods_spec_id = $this->request->request('goods_spec_id');
        $business_id = $this->request->request('business_id');
        if (!$goods_spec_id || !$business_id) {
            $this->error('参数不全');
        }
        //处理订单 - 将多个订单合为一个订单
        $datas = [];
        $cart = Cache::get('cart_' . $this->auth->id . $business_id) ?: [];
        $carts = new cart($this->auth->id, $business_id);
        $result = explode(',', $goods_spec_id);
        foreach ($cart as $k => $v) {
            if (in_array($k, $result)) {
                $orders = $this->model->getBuyNow($this->auth->id, $v['goods_id'], (int)$v['goods_num'], $v['goods_sku_id'], $business_id, $this->auth->venue_id);
                if ($orders) {
                    $order_no = $this->model->order_add($this->auth->id, $orders);
                    if ($order_no) {
                        $carts->delete($k);//删除购物车商品
                        array_push($datas, $order_no);
                    }
                }
            }
        }
        if ($datas) {
            $list['list'] = $this->model->getList($this->auth->id, '10', $datas, '');
            foreach ($list['list'] as $k => &$v) {
                foreach ($v['goods'] as $kk => $vv) {
                    $vv['images'] = cdnurl(explode(",", $vv['images'])[0], true);
                    unset($vv['content']);
                    unset($vv['goods_no']);
                    unset($vv['goods_weight']);
                    unset($vv['createtime']);
                    unset($vv['date_id']);
                    unset($vv['time']);
                    unset($vv['time_slot']);
                    unset($vv['date']);
                    unset($vv['groupbuying']);
                    unset($vv['mobile']);
                }
                $v['starting_price'] = db('business')->where('id', $business_id)->value('starting_price');//起送价
                unset($v['pay_status']);
                unset($v['pay_time']);
                unset($v['express_company']);
                unset($v['express_no']);
                unset($v['freight_status']);
                unset($v['freight_time']);
                unset($v['receipt_status']);
                unset($v['receipt_time']);
                unset($v['status']);
                unset($v['captcha']);
                unset($v['pay_type']);
                unset($v['order_type']);
                unset($v['packing_fee']);
                unset($v['express_price']);
                unset($v['discount_vip_price']);
            }
            unset($v);
            $list['litestore_adress'] = db('litestore_adress')->where(['user_id' => $this->auth->id, 'isdefault' => '1'])->find();
            if ($list['litestore_adress']) {
                $list['litestore_adress']['province_id'] = db('area')->where('id', $list['litestore_adress']['province_id'])->value('name');
                $list['litestore_adress']['city_id'] = db('area')->where('id', $list['litestore_adress']['city_id'])->value('name');
                $list['litestore_adress']['region_id'] = db('area')->where('id', $list['litestore_adress']['region_id'])->value('name');
            } else {
                $list['litestore_adress'] = null;
            }
            $list['cost'] = db('business')->where('id', $business_id)->field('packing_fee,distribution_fee as express_price')->find();
            $total = $list['cost']['packing_fee'] + $list['cost']['express_price'];
            $uservip = db('user_vip')->where(['user_id' => $this->auth->id, 'venue_id' => $this->auth->venue_id])->where('endtime', '>', time())->find();
            $venuevip = db('venue_vip')->where('id', $uservip['venuevip_id'])->find();
            $paytype = explode(',', $venuevip['paytype_ids']);
            if (in_array('1', $paytype)) {
                $discount = $venuevip['discount'] / 10;
                $list['cost']['discount_vip_price'] = sprintf("%.2f", $list['list'][0]['total_price'] - $list['list'][0]['total_price'] * $discount);
                $list['list'][0]['total_price'] = $totalPrice = $list['list'][0]['total_price'] * $discount;
            } else {
                $list['cost']['discount_vip_price'] = 0;
            }
            $list['cost']['pay_price'] = sprintf("%.2f", bcadd(array_sum(array_column($list['list'], 'total_price')), $total, 2), 2);
            $list['cost']['coupons'] = json_decode($this->couponsmodel->see_coupons(40, $list['cost']['pay_price'], '', $this->auth->id), true);
            $this->success('ok', $list);
        } else {
            $this->error($this->model->getError() ?: '订单创建失败');
        }
    }

    /**
     * 多个订单合并成一个订单并支付
     * @param $type 支付类型：支付宝：alipay；微信：wechat
     * @param $method 支付平台：app：app支付；mp：公众号支付；miniapp：小程序支付
     * @param $order_ids 订单ID集合（字符串）
     */
    public function orders_pay()
    {
        $type = $this->request->post('type');
        $method = $this->request->post('method');
        $order_ids = $this->request->post('order_ids');
        $total_price = $this->request->post('total_price');
        $address_id = $this->request->post('address_id');
        $coupons_id = $this->request->post('coupons_id');
        $discount_price = $this->request->post('discount_price');
        $discount_vip_price = $this->request->post('discount_vip_price') ?? 0;
        $user = $this->auth->getUser();
        if (!$total_price || !$address_id) {
            $this->error('参数不全');
        }
        if (!in_array($type, ['alipay', 'wechat'])) {
            $this->error('支付方式错误');
        }
        if (!in_array($method, ['app', 'mp', 'miniapp'])) {
            $this->error('支付平台参数错误');
        }
        if (!$order_ids) {
            $this->error('订单ID不能为空');
        }
        if ($type == 'alipay') {
            $pay_type = '20';
        } else {
            $pay_type = '10';
        }
        $order_no = date('Ymd') . substr(implode(null, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        $orderids = explode(',', $order_ids);
        $goods_id = db('litestore_order_goods')->where('order_id', $orderids[0])->value('goods_id');
        $business_id = db('litestore_goods')->where('goods_id', $goods_id)->value('business_id');
        $business = db('business')->where('id', $business_id)->field('packing_fee,distribution_fee,starting_price,type')->find();
        if ($business['type'] == '20') {
            $order_type = '50';
        } else {
            $order_type = '30';
        }
        $data = [
            'order_no' => $order_no,
            'pay_price' => $total_price,
            'pay_status' => '10',
            'user_id' => $this->auth->id,
            'pay_type' => $pay_type,
            'createtime' => time(),
            'updatetime' => time(),
            'order_type' => $order_type,
            'shop_id' => $business_id,
            'order_ids' => $order_ids,
            'packing_fee' => $business['packing_fee'],//包装费
            'distribution_fee' => $business['distribution_fee'],//配送费
            'discount_vip_price' => $discount_vip_price,//vip优惠价
        ];
        $address = db('litestore_adress')->where('address_id', $address_id)->find();
        foreach ($orderids as $k => $v) {
            $bb[] = [
                'user_id' => $this->auth->id,
                'name' => $address['name'],
                'phone' => $address['phone'],
                'province_id' => $address['province_id'],
                'city_id' => $address['city_id'],
                'order_id' => $v,
                'region_id' => $address['region_id'],
                'detail' => $address['details'],
                'createtime' => time(),
            ];
            $update = db('litestore_order_address')->insert($bb[$k]);
        }
        if (isset($coupons_id) && $coupons_id) {
            $status = db('receive')->where(['coupons_id' => $coupons_id, 'user_id' => $this->auth->id])->value('status');
            if ($status !== '10') {
                $this->error('优惠券已使用或已过期');
            }
            $data['discount_price'] = $discount_price;
            $data['coupons_id'] = $coupons_id;
        } else {
            $data['discount_price'] = 0;
            $data['coupons_id'] = 0;
        }
        $data['total_discount_price'] = $data['discount_price'] + $data['discount_vip_price'];
        $data['venue_id'] = $this->auth->venue_id;
        $add = db('order')->insertGetId($data);//合并订单
        if ($add) {
            foreach ($orderids as $k => $v) {
                db('litestore_order_goods')->where('order_id', $v)->update(['orderid' => $add]);
            }
            //合并成功发起支付
            $amount = $total_price;
            if (!$amount || $amount < 0) {
                $this->error("支付金额必须大于0");
            }
            $amount = 0.01;
            if ($method == 'mp' || $method == 'miniapp') {
                $openid = db('third')->where('user_id', $this->auth->id)->value('openid');
            } else {
                $openid = '';
            }
            //订单号
            $out_trade_no = $order_no;
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
            $this->error('发起支付失败', $add);
        }
    }


    /**
     * 下单购买
     * @param $group_buy 团购类型：10=开团,20=拼团
     */
    public function addorder()
    {
        $param = $this->request->param();
        $goods_id = $this->request->request('goods_id');
        $total_num = $this->request->request('total_num');
        $group_buy = $this->request->request('group_buy');
        $group_buy_id = $this->request->request('group_buy_id');
        $coupons_id = $this->request->request('coupons_id');
        $discount_price = $this->request->request('discount_price');
        $discount_vip_price = $this->request->request('discount_vip_price');
        $spec_sku_id = $this->request->request('spec_sku_id') ? $this->request->request('spec_sku_id') : '';
        $type = $this->request->request('type');
        $method = $this->request->request('method');
        if (!$goods_id || !$total_num) {
            $this->error('参数不全');
        }
        if (!in_array($type, ['alipay', 'wechat'])) {
            $this->error('支付方式错误');
        }
        if (!in_array($method, ['app', 'mp', 'miniapp'])) {
            $this->error('支付平台参数错误');
        }
        if (!$goods_id || !$total_num) {
            $this->error('参数不全');
        }
        $uservip = db('user_vip')->where(['user_id' => $this->auth->id, 'venue_id' => $this->auth->venue_id])->where('endtime', '>', time())->find();
        $venuevip = db('venue_vip')->where('id', $uservip['venuevip_id'])->find();
        $paytype = explode(',', $venuevip['paytype_ids']);
        $ordervalue = db('litestore_goods')->where('goods_id', $goods_id)->field('reservation_id,venue_id,group_work_number')->find();
        $time = strtotime(date('Y-m-d H:i:s', strtotime('+' . $ordervalue['group_work_number'] . 'minute')));
        if (isset($coupons_id) || isset($discount_price)) {
            $order = $this->model->getBuyNows($this->auth->id, $goods_id, $total_num, $spec_sku_id, $ordervalue['reservation_id'], $ordervalue['venue_id'], $group_buy, $coupons_id, $discount_price);
        } else {
            $order = $this->model->getBuyNows($this->auth->id, $goods_id, $total_num, $spec_sku_id, $ordervalue['reservation_id'], $ordervalue['venue_id'], $group_buy, '', '');
        }
        if ($this->model->hasError()) {
            return $this->error($this->model->getError());
        }
        // 创建订单
        if ($this->model->order_adds($this->auth->id, $order, $ordervalue['reservation_id'], $time, $group_buy_id, $ordervalue['group_work_number'])) {
            $user = $this->auth->getUser();
            if (!$this->model['pay_price']) {
                $this->error('参数不全');
            }
            if ($spec_sku_id) {
                $goods = db('litestore_goods_spec')->where(['goods_id' => $goods_id, 'spec_sku_id' => $spec_sku_id])->field('goods_price,group_price')->find();
            } else {
                $goods = db('litestore_goods_spec')->where(['goods_id' => $goods_id])->field('goods_price,group_price')->find();
            }
            if ($type == 'alipay') {
                $pay_type = '20';
            } else {
                $pay_type = '10';
            }
            $order_no = date('Ymd') . substr(implode(null, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
            if (!$this->model['id']) {
                $this->error('订单创建失败');
            }
            $data = [
                'order_no' => $order_no,
                'pay_status' => '10',
                'user_id' => $this->auth->id,
                'pay_type' => $pay_type,
                'createtime' => time(),
                'updatetime' => time(),
                'order_type' => '20',
                'shop_id' => $ordervalue['reservation_id'],
                'order_ids' => $this->model['id'],
                'discount_vip_price' => $discount_vip_price,
            ];
            $bb = [
                'user_id' => $this->auth->id,
                'name' => $this->auth->nickname,
                'phone' => $this->auth->mobile,
                'province_id' => 0,
                'city_id' => 0,
                'order_id' => $this->model['id'],
                'region_id' => 0,
                'detail' => '课程订单',
                'createtime' => time(),
            ];
            db('litestore_order_address')->insert($bb);
            if (isset($coupons_id) && $coupons_id) {
                $status = db('receive')->where(['coupons_id' => $coupons_id, 'user_id' => $this->auth->id])->value('status');
                if ($status != '10') {
                    $this->error('优惠券已使用或已过期');
                }
                $data['discount_price'] = $discount_price;
                $data['coupons_id'] = $coupons_id;
            } else {
                $data['discount_price'] = 0;
                $data['coupons_id'] = 0;
            }
            if ($this->model['groupbuying'] == '20') {
                $data['groupbuying'] = '20';
                # 团购
                if ($this->model['is_head'] == '10') {
                    $data['groupbuying_status'] = '20';
                    $data['is_head'] = '10';
                    $data['collage_sign'] = $group_buy_id;
                } else {
                    $data['groupbuying_status'] = '20';
                    $data['is_head'] = '20';
                    $data['group_work_number'] = $this->model['group_work_number'];
                }
            }
            $data['total_discount_price'] = $data['discount_price'] + $discount_vip_price;
            if ($group_buy) {
                $data['pay_price'] = ($goods['group_price'] * $total_num) - $data['total_discount_price'];
            } else {
                $data['pay_price'] = ($goods['goods_price'] * $total_num) - $data['total_discount_price'];
            }
            $data['venue_id'] = $this->auth->venue_id;
            $add = db('order')->insertGetId($data);//合并订单
            // $this->success('支付暂未开放',$add);
            if ($add) {
                //合并成功发起支付
                $amount = $this->model['pay_price'];
                if (!$amount || $amount < 0) {
                    $this->error("支付金额必须大于0");
                }
                $amount = 0.01;
                if ($method == 'mp' || $method == 'miniapp') {
                    $openid = db('third')->where('user_id', $this->auth->id)->value('openid');
                } else {
                    $openid = '';
                }
                //订单号
                $out_trade_no = $order_no;
                $data['type'] = $type;
                $data['out_trade_no'] = $out_trade_no;
                //订单标题
                $title = '场馆通';
                //回调链接
                $notifyurl = $this->request->root(true) . '/addons/epay/api/notifyx/type/' . $type;
                $returnurl = $this->request->root(true) . '/addons/epay/api/returnx/type/' . $type . '/out_trade_no/' . $out_trade_no;
                $arr = \addons\epay\library\Service::submitOrder($amount, $out_trade_no, $type, $title, $notifyurl, $returnurl, $method, $openid);
                if ($type == 'wechat') {
                    $data = json_decode($arr);
                    $data->order_id = $this->model['id'];
                    $this->success('ok', $data);
                } else {
                    $str['sign'] = $arr;
                    $this->success('ok', $str);
                }
            } else {
                $this->error('发起支付失败', $add);
            }
        } else {
            $this->error('发起支付失败');
        }
        $error = $this->model->getError() ?: '订单创建失败';
        return $this->error($error);
    }

    /**
     * 课程-确认订单
     */
    public function seeorder()
    {
        $goods_id = $this->request->request('goods_id');
        $total_num = $this->request->request('total_num');
        $group_buy = $this->request->request('group_buy');
        $spec_sku_id = $this->request->request('spec_sku_id') ? $this->request->request('spec_sku_id') : '';
        if (!$goods_id || !$total_num) {
            $this->error('参数不全');
        }
        $ordervalue = db('litestore_goods')->where('goods_id', $goods_id)->field('reservation_id,venue_id')->find();
        if (isset($group_buy) || $group_buy) {
            $order = $this->model->getBuyNows($this->auth->id, $goods_id, $total_num, $spec_sku_id, $ordervalue['reservation_id'], $ordervalue['venue_id'], $group_buy, '', '');
        } else {
            $order = $this->model->getBuyNows($this->auth->id, $goods_id, $total_num, $spec_sku_id, $ordervalue['reservation_id'], $ordervalue['venue_id'], '', '', '');
        }
        $data = [
            'goods_id' => $order['goods_list'][0]['goods_id'],
            'goods_name' => $order['goods_list'][0]['goods_name'],
            'image' => $order['goods_list'][0]['image'],
            'goods_attr' => $order['goods_list'][0]['goods_sku']['goods_attr'],
            'spec_sku_id' => $order['goods_list'][0]['goods_sku']['spec_sku_id'],
            'goods_price' => $order['goods_list'][0]['goods_price'],
            'total_num' => $order['goods_list'][0]['total_num'],
            'total_price' => $order['goods_list'][0]['total_price'],
            'reservation_id' => $order['goods_list'][0]['reservation_id'],
            'discount_vip_price' => $order['discount_vip_price'],
        ];
        $data['coupons'] = json_decode($this->couponsmodel->see_coupons(30, $order['goods_list'][0]['total_price'], $ordervalue['reservation_id'], $this->auth->id), true);
        $this->success('ok', $data);
    }

    /**
     * 订单详情
     */
    public function ordervalue()
    {
        $order_id = $this->request->request('order_id');
        $qrcode = new qrcode();
        $data = db('litestore_order')
            ->alias('o')
            ->join('litestore_order_goods g', 'g.order_id = o.id')
            ->where('o.id', $order_id)
            ->field('o.id as order_id,g.goods_name,g.goods_id,o.venue_id,o.order_type,o.pay_status,o.captcha,o.order_no,o.total_price,o.pay_price,o.createtime,g.total_num,g.goods_attr')
            ->find();
        if ($data['order_type'] == '30') {
            $this->error('商品订单设计图不清楚');
        }
        if (in_array($data['order_type'], [10, 20])) {
            $data['reservation_id'] = db('litestore_goods')->where('goods_id', $data['goods_id'])->value('reservation_id');
            $data['reservation_name'] = db('reservation')->where('id', $data['reservation_id'])->value('name');
            if ($data['pay_status'] == '20') {
                $data['qrcode'] = cdnurl($qrcode->builds($data['captcha']), true);
            } else {
                $data['qrcode'] = '';
            }
        }
        $data['venue_name'] = db('venue')->where('id', $data['venue_id'])->value('name');
        $data['createtime'] = date('Y-m-d H:i:s', $data['createtime']);
        $data['pay_type'] = '微信支付';
        $this->success('ok', $data);
    }


    /**
     * 订单详情
     */
    public function orderdetails()
    {
        $order_id = $this->request->request('order_id');
        if (!$order_id) {
            $this->error('请输入总订单ID');
        }
        $qrcode = new qrcode();
        $user_id = $this->auth->id;
        $data['ordervalue'] = db('order')->where('id', $order_id)->field('id,order_no,pay_price,groupbuying,pay_status,discount_price,order_ids,pay_type,shop_id,order_type,packing_fee,distribution_fee,discount_vip_price,createtime,captcha,total_discount_price')->find();
        $data['ordervalue']['captcha'] = str_replace('"', '', $data['ordervalue']['captcha']);
        $orderids = explode(',', $data['ordervalue']['order_ids']);
        $data['goodslist'] = db('litestore_order_goods')->where('order_id', 'in', $orderids)->where('user_id', $user_id)->field('order_id,goods_id,goods_name,images,total_num,goods_attr,goods_price,group_price,time,time_slot')->select();
        foreach ($data['goodslist'] as $k => &$v) {
            if ($data['ordervalue']['groupbuying'] == '20') {
                $v['goods_price'] = $v['group_price'];
            }
            $v['images'] = cdnurl(explode(',', $v['images'])[0], true);
            if ($data['ordervalue']['order_type'] == '10') {
                unset($v['goods_attr']);
                unset($data['ordervalue']['packing_fee']);
                unset($data['ordervalue']['distribution_fee']);
            } elseif ($data['ordervalue']['order_type'] == '20') {
                unset($data['ordervalue']['packing_fee']);
                unset($data['ordervalue']['distribution_fee']);
                unset($v['time']);
                unset($v['time_slot']);
            } else {
                $data['address'] = db('litestore_order_address')->where('order_id', $orderids[0])->field('name,phone,province_id,city_id,region_id,detail')->find();
                $region = db('area')->where('id', $data['address']['region_id'])->value('mergename');
                $region = mb_substr($region, 3);
                $data['address']['detail'] = $region . ',' . $data['address']['detail'];
                unset($data['address']['province_id']);
                unset($data['address']['city_id']);
                unset($data['address']['region_id']);
            }
        }
        $data['ordervalue']['total_shop_num'] = count($data['goodslist']);
        if ($data['ordervalue']['pay_type'] == '10') {
            $data['ordervalue']['pay_type_text'] = '微信支付';
        } else {
            $data['ordervalue']['pay_type_text'] = '支付宝支付';
        }
        if ($data['ordervalue']['pay_status'] == '10') {
            $data['ordervalue']['pay_status_text'] = '待支付';
        } elseif ($data['ordervalue']['pay_status'] == '20') {
            $data['ordervalue']['pay_status_text'] = '已支付';
        } elseif ($data['ordervalue']['pay_status'] == '30') {
            $data['ordervalue']['pay_status_text'] = '待核销';
        } elseif ($data['ordervalue']['pay_status'] == '40') {
            $data['ordervalue']['pay_status_text'] = '已核销';
        } elseif ($data['ordervalue']['pay_status'] == '50') {
            $data['ordervalue']['pay_status_text'] = '待收货';
        } elseif ($data['ordervalue']['pay_status'] == '60') {
            $data['ordervalue']['pay_status_text'] = '已收货';
        } elseif ($data['ordervalue']['pay_status'] == '70') {
            $data['ordervalue']['pay_status_text'] = '待评价';
        } elseif ($data['ordervalue']['pay_status'] == '80') {
            $data['ordervalue']['pay_status_text'] = '已评价';
        } elseif ($data['ordervalue']['pay_status'] == '90') {
            $data['ordervalue']['pay_status_text'] = '已完成';
        } elseif ($data['ordervalue']['pay_status'] == '100') {
            $data['ordervalue']['pay_status_text'] = '待成团';
        } else {
            $data['ordervalue']['pay_status_text'] = '拼团失败';
        }
        $data['ordervalue']['createtime'] = date('Y-m-d H:i:s', $data['ordervalue']['createtime']);
        if ($data['ordervalue']['order_type'] == '10') {
            if ($data['ordervalue']['pay_status'] == '20') {
                $data['ordervalue']['qrcode'] = cdnurl($qrcode->builds($data['ordervalue']['captcha']), true);
            }
            $data['ordervalue']['remarks'] = db('venue')->where('id', $data['ordervalue']['shop_id'])->value('name');
        } elseif ($data['ordervalue']['order_type'] == '20') {
            if ($data['ordervalue']['pay_status'] == '20') {
                $data['ordervalue']['qrcode'] = cdnurl($qrcode->builds($data['ordervalue']['captcha']), true);
            }
            $data['ordervalue']['remarks'] = db('reservation')->where('id', $data['ordervalue']['shop_id'])->value('name');
        } else {
            $data['ordervalue']['remarks'] = db('business')->where('id', $data['ordervalue']['shop_id'])->value('name');
            unset($data['ordervalue']['captcha']);
        }
        $this->success('ok', $data);
    }


    /**
     * 计算优惠价
     */

    public function calculate_price()
    {
        $param = $this->request->param();
        if (!$param['total_price']) {
            $this->error('参数不全');
        }
        //10=订场订单,20=课程订单,30=商品订单,40=活动订单
        if (!in_array($param['type'], ['10', '20', '30', '40'])) {
            $this->error('订单类型错误');
        }
        $uservip = db('user_vip')->where(['user_id' => $this->auth->id, 'venue_id' => $this->auth->venue_id])->where('endtime', '>', time())->find();
        $venuevip = db('venue_vip')->where('id', $uservip['venuevip_id'])->find();
        $paytype = explode(',', $venuevip['paytype_ids']);
        // 商品总价
        if ($param['type'] == '10') {
            # 订场
            if (in_array('3', $paytype)) {
                $discount = $venuevip['discount'] / 10;
                $data['discount_vip_price'] = sprintf("%.2f", $param['total_price'] - $param['total_price'] * $discount);
                $param['total_price'] = $param['total_price'] * $discount;
            } else {
                $data['discount_vip_price'] = 0;
            }
        } elseif ($param['type'] == '20') {
            # 课程
            if (in_array('4', $paytype)) {
                $discount = $venuevip['discount'] / 10;
                $data['discount_vip_price'] = sprintf("%.2f", $param['total_price'] - $param['total_price'] * $discount);
                $param['total_price'] = $param['total_price'] * $discount;
            } else {
                $data['discount_vip_price'] = 0;
            }
        } elseif ($param['type'] == '30') {
            # 商品
            if (in_array('1', $paytype)) {
                $discount = $venuevip['discount'] / 10;
                $data['discount_vip_price'] = sprintf("%.2f", $param['total_price'] - $param['total_price'] * $discount);
                $param['total_price'] = $param['total_price'] * $discount;
            } else {
                $data['discount_vip_price'] = 0;
            }
        } elseif ($param['type'] == '40') {
            # 活动
            if (in_array('2', $paytype)) {
                $discount = $venuevip['discount'] / 10;
                $data['discount_vip_price'] = sprintf("%.2f", $param['total_price'] - $param['total_price'] * $discount);
                $param['total_price'] = $param['total_price'] * $discount;
            } else {
                $data['discount_vip_price'] = 0;
            }
        } else {
            $data['discount_vip_price'] = 0;
        }
        if ($param['coupons_id']) {
            $coupons = db('coupons')->where('id', $param['coupons_id'])->field('id,coupons_money,use_condition,coupons_type')->find();
            if ($param['total_price'] < $coupons['use_condition']) {
                $this->error('未达到使用条件');
            }
            if (in_array($coupons['coupons_type'], ['10', '30'])) {
                # 代金券
                $data['settlement_price'] = sprintf("%.2f", $param['total_price'] - $coupons['coupons_money']);#结算价
                $data['discount_price'] = sprintf("%.2f", $coupons['coupons_money']);#优惠价
            } else {
                # 折扣券
                // $money = $coupons['coupons_money'];
                $data['discount_price'] = $param['total_price'] * $coupons['coupons_money'] / 10;
                $data['settlement_price'] = sprintf("%.2f", $data['discount_price']);#结算价
                $data['discount_price'] = sprintf("%.2f", $param['total_price'] - $data['discount_price']);#优惠价
            }
        } else {
            $data['settlement_price'] = $param['total_price'];#结算价
            $data['discount_price'] = 0;#优惠价
        }
        $this->success('ok', $data);
    }

    public function calculateprice($total_price = '', $type = '', $user_id = '', $coupons_id = '')
    {
        $user = db('user')->where('id', $user_id)->field('id,venue_id')->find();
        $uservip = db('user_vip')->where(['user_id' => $user['id'], 'venue_id' => $user['venue_id']])->where('endtime', '>', time())->find();
        $venuevip = db('venue_vip')->where('id', $uservip['venuevip_id'])->find();
        $paytype = explode(',', $venuevip['paytype_ids']);
        // 商品总价
        if ($type == '10') {
            # 订场
            if (in_array('3', $paytype)) {
                $discount = $venuevip['discount'] / 10;
                $data['discount_vip_price'] = sprintf("%.2f", $total_price - $total_price * $discount);
                $total_price = $total_price * $discount;
            } else {
                $data['discount_vip_price'] = 0;
            }
        } elseif ($type == '20') {
            # 课程
            if (in_array('4', $paytype)) {
                $discount = $venuevip['discount'] / 10;
                $data['discount_vip_price'] = sprintf("%.2f", $total_price - $total_price * $discount);
                $total_price = $total_price * $discount;
            } else {
                $data['discount_vip_price'] = 0;
            }
        } elseif ($type == '30') {
            # 商品
            if (in_array('1', $paytype)) {
                $discount = $venuevip['discount'] / 10;
                $data['discount_vip_price'] = sprintf("%.2f", $total_price - $total_price * $discount);
                $total_price = $total_price * $discount;
            } else {
                $data['discount_vip_price'] = 0;
            }
        } elseif ($type == '40') {
            # 活动
            if (in_array('2', $paytype)) {
                $discount = $venuevip['discount'] / 10;
                $data['discount_vip_price'] = sprintf("%.2f", $total_price - $total_price * $discount);
                $total_price = $total_price * $discount;
            } else {
                $data['discount_vip_price'] = 0;
            }
        } else {
            $data['discount_vip_price'] = 0;
        }
        if ($coupons_id) {
            $coupons = db('coupons')->where('id', $coupons_id)->field('id,coupons_money,use_condition,coupons_type')->find();
            if ($total_price < $coupons['use_condition']) {
                $this->error('未达到使用条件');
            }
            if (in_array($coupons['coupons_type'], ['10', '30'])) {
                # 代金券
                $data['settlement_price'] = sprintf("%.2f", $total_price - $coupons['coupons_money']);#结算价
                $data['discount_price'] = sprintf("%.2f", $coupons['coupons_money']);#优惠价
            } else {
                # 折扣券
                // $money = $coupons['coupons_money'];
                $data['discount_price'] = $total_price * $coupons['coupons_money'] / 10;
                $data['settlement_price'] = sprintf("%.2f", $data['discount_price']);#结算价
                $data['discount_price'] = sprintf("%.2f", $total_price - $data['discount_price']);#优惠价
            }
        } else {
            $data['settlement_price'] = $total_price;#结算价
            $data['discount_price'] = 0;#优惠价
        }
        return json_encode($data, true);
        // $this->success('ok', $data);
    }

    /**
     * 取消订单
     * @param $order_id 订单ID
     */
    public function cancelorder()
    {
        $order_id = $this->request->post('order_id');
        if (!$order_id) {
            $this->error('请选择你要取消的订单');
        }
        $orderid = db('order')->where('id', $order_id)->field('order_ids,pay_status')->find();
        if ($orderid['pay_status'] == '10') {
            // 启动事务
            Db::startTrans();
            try {
                $del = db('order')->where('id', $order_id)->delete();
                db('litestore_order')->where('id', 'in', explode(',', $orderid['order_ids']))->delete();
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->error($e->getMessage());
            }
            $this->success('订单取消成功', $del);
        }
    }

    /**
     * 订单支付
     */
    public function orderpay()
    {
        $order_no = $this->request->post('order_no');
        if (!$order_no) {
            $this->error('参数不全');
        }
        $orderdata = db('order')->where('order_no', $order_no)->field('id,pay_price,pay_status')->find();
        $order_no = date('Ymd') . substr(implode(null, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        db('order')->where('id', $orderdata['id'])->update(['order_no' => $order_no]);
        if ($orderdata['pay_status'] == '20') {
            $this->error('该订单已支付');
        }
        //合并成功发起支付
        $amount = $orderdata['pay_price'];
        if (!$amount || $amount < 0) {
            $this->error("支付金额必须大于0");
        }
        $amount = 0.01;
        $method = 'miniapp';
        $type = 'wechat';
        if ($method == 'mp' || $method == 'miniapp') {
            $openid = db('third')->where('user_id', $this->auth->id)->value('openid');
        } else {
            $openid = '';
        }
        //订单号
        $out_trade_no = $order_no;
        //订单标题
        $title = '场馆通';
        //回调链接
        $notifyurl = $this->request->root(true) . '/addons/epay/api/notifyx/type/' . $type;
        $returnurl = $this->request->root(true) . '/addons/epay/api/returnx/type/' . $type . '/out_trade_no/' . $out_trade_no;
        $arr = \addons\epay\library\Service::submitOrder($amount, $out_trade_no, $type, $title, $notifyurl, $returnurl, $method, $openid);
        if ($type == 'wechat') {
            $data = json_decode($arr);
            $data->order_id = $orderdata['id'];
            $this->success('ok', $data);
        } else {
            $str['sign'] = $arr;
            $this->success('ok', $str);
        }
    }

    /**
     * 确认收货
     */
    public function updateorder()
    {
        $order_id = $this->request->request('order_id');
        if (!$order_id) {
            $this->error('请选择要收货的订单');
        }
        $pay_status = db('order')->where('id', $order_id)->value('pay_status');
        if ($pay_status !== '50') {
            $this->error('当前订单不可收货');
        }
        $update = db('order')->where('id', $order_id)->update(['pay_status' => '90', 'updatetime' => time()]);
        $this->success('ok', $update);
    }

    /**
     * 入场二维码
     */
    public function qrcode()
    {
        $captcha = db('order')
            ->where('pay_status', '<>', '10')
            ->where(['order_type' => '10', 'user_id' => $this->auth->id])
            ->where('pay_status', 'in', ['20', '30', '40', '70', '80', '90'])
            ->value('captcha');
        $qrcode = new qrcode();
        if ($captcha) {
            $venueqrcode = cdnurl($qrcode->builds($captcha), true);
        } else {
            $venueqrcode = '';
        }
        $this->success('ok', $venueqrcode);
    }
}
