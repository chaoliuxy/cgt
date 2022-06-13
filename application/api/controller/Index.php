<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Activity;
use think\Log;
use Yansongda\Pay\Pay;
use addons\epay\library\Service;
use app\common\model\News;
use app\common\model\Venuelog;
use fast\Date;
use think\Db;

/**
 * 首页接口
 */
class Index extends Api
{
    protected $noNeedLogin = ['venue', 'sporttypelist', 'venuelist', 'addetails', 'goodslist', 'getactivitylist'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->activity = new Activity();
        $this->news = new News();
        $this->venuelog = new Venuelog();
    }

    /**
     * 首页
     *
     */
    public function index()
    {
        $this->success('请求成功');
    }

    /**
     * 新用户根据其所在位置推荐最近的场馆
     */
    public function venue()
    {
        $param = $this->request->param();
        if (!$param['lat'] || !$param['lng']) {
            $this->error('请输入经纬度');
        }
        $venue = db('venue')
            ->where('status', '10')
            ->field('id,name,images,address,sporttype_ids,round(sqrt( ( ((' . $param['lng'] . '-lng)*PI()*12656*cos(((' . $param['lat'] . '+lat)/2)*PI()/180)/180) * ((' . $param['lng'] . '-lng)*PI()*12656*cos (((' . $param['lat'] . '+lat)/2)*PI()/180)/180) ) + ( ((' . $param['lat'] . '-lat)*PI()*12656/180) * ((' . $param['lat'] . '-lat)*PI()*12656/180) ) )/2,2) as dis')
            ->order('dis')->find();
        $venue['images'] = cdnurl(explode(",", $venue['images'])[0], true);
        $ids = explode(",", $venue['sporttype_ids']);
        $venue['sporttype_list'] = db('sporttype')->where('id', 'in', $ids)->field('id,name')->select();
        $this->success('ok', $venue);
    }

    /**
     * 运动类型列表
     */
    public function sporttypelist()
    {
        $type = $this->request->request('type');
        if (!in_array($type, [10, 20])) {
            $this->error('类型参数错误');
        }
        if ($type == '10') {
            $list = db('sporttype')->field('id,name')->select();
        } else {
            $list = db('sporttype')->where('venue_id', $this->auth->venue_id)->field('id,name')->select();
        }
        $this->success('ok', $list);
    }

    /**
     * 场馆列表
     * @param $distance_type 10:距离正序、20：距离倒序
     * @param $sporttype_ids 运动类型
     * @param $praise_type 10:好评正序、20：好评倒序
     */
    public function venuelist()
    {
        $param = $this->request->param();
        if (!in_array($param['distance_type'], [10, 20])) {
            $this->error('距离参数错误');
        }
        if (!in_array($param['praise_type'], [10, 20])) {
            $this->error('好评率参数错误');
        }
        // if (!$param['sporttype_ids']) {
        //     $this->error('请输入运动类型');
        // }
        if (!$param['lat'] || !$param['lng']) {
            $this->error('请输入当前经纬度');
        }
        if (!$param['page'] || !$param['showpage']) {
            $this->error('请输入当前页数和每页显示数量');
        }
        $list['list'] = db('venue');
        $list['count'] = db('venue');
        if ($param['key']) {
            $list['list'] = $list['list']->where('name|address', 'like', '%' . $param['key'] . '%');
            $list['count'] = $list['count']->where('name|address', 'like', '%' . $param['key'] . '%');
        }
        if ($param['sporttype_ids']) {
            $list['list'] = $list['list']->where('sporttype_ids', 'like', '%' . $param['sporttype_ids'] . '%');
            $list['count'] = $list['count']->where('sporttype_ids', 'like', '%' . $param['sporttype_ids'] . '%');
        }
        $list['list'] = $list['list']
            ->where('status', '10')
            ->field('id,name,images,address,sporttype_ids,round(sqrt( ( ((' . $param['lng'] . '-lng)*PI()*12656*cos(((' . $param['lat'] . '+lat)/2)*PI()/180)/180) * ((' . $param['lng'] . '-lng)*PI()*12656*cos (((' . $param['lat'] . '+lat)/2)*PI()/180)/180) ) + ( ((' . $param['lat'] . '-lat)*PI()*12656/180) * ((' . $param['lat'] . '-lat)*PI()*12656/180) ) )/2,2) as dis');
        $list['count'] = $list['count']
            ->where('status', '10');
        if ($param['distance_type'] == '10') {
            # 距离正序
            $list['list'] = $list['list']->order('dis')->group('id')->limit($param['showpage'])->page($param['page'])->select();
            $list['count'] = $list['count']->group('id')->count();
        } else {
            $list['list'] = $list['list']->order('dis DESC')->group('id')->limit($param['showpage'])->page($param['page'])->select();
            $list['count'] = $list['count']->group('id')->count();
        }
        foreach ($list['list'] as &$v) {
            $v['images'] = cdnurl(explode(",", $v['images'])[0], true);
            $ids = explode(",", $v['sporttype_ids']);
            $v['sporttype_list'] = db('sporttype')->where('id', 'in', $ids)->field('id,name')->select();
            unset($v['sporttype_ids']);
        }
        unset($v);
        $list['total_page'] = ceil($list['count'] / $param['showpage']);
        $this->success('ok', $list);
    }

    /**
     * 选择体育馆
     * @param $param ['venue_id'] 体育馆ID
     */
    public function choice_venue()
    {
        $param = $this->request->param();
        if (!$param['venue_id']) {
            $this->error('请选择你要进入的体育馆');
        }
        $update = db('user')->where('id', $this->auth->id)->update(['venue_id' => $param['venue_id'], 'updatetime' => time()]);
        $venue_name = db('venue')->where('id', $param['venue_id'])->value('name');
        $this->success('ok', $venue_name);
    }

    /**
     * 轮播图
     * @param $param ['position_status']:展示位置:homepage=首页,explain=说明
     */
    public function bannerlist()
    {
        $param = $this->request->param();
        if (!in_array($param['position_status'], ['homepage', 'explain', 'business'])) {
            $this->error('展示的位置参数错误');
        }
        if (!$this->auth->venue_id) {
            $this->error('请选择所在体育馆');
        }
        $list = db('banner')->where(['venue_id' => $this->auth->venue_id, 'position_status' => $param['position_status']])->field('id,image,type,position_id')->order('weigh DESC')->select();
        foreach ($list as &$v) {
            $v['image'] = cdnurl($v['image'], true);
        }
        unset($v);
        $this->success('ok', $list);
    }

    /**
     * 场馆介绍
     */
    public function venue_introduction()
    {
        if (!$this->auth->venue_id) {
            $this->error('请选择所在体育馆');
        }
        $list = db('venue')->where('id', $this->auth->venue_id)->field('id,sporttype_ids,name,images,star_time,end_time,content,address,lat,lng')->find();
        $list['images'] = explode(",", $list['images']);
        $list['content'] = replacePicUrl($list['content'], config('fastadmin.url'));
        foreach ($list['images'] as &$v) {
            $v = cdnurl($v, true);
        }
        unset($v);
        $ids = explode(",", $list['sporttype_ids']);
        unset($list['sporttype_ids']);
        $list['sporttype_list'] = db('sporttype')->where('id', 'in', $ids)->field('id,name')->select();
        $this->success('ok', $list);
    }

    /**
     * 赛事活动
     */
    public function activitylist()
    {
        $page = input('post.page');
        $showpage = input('post.showpage', '10', 'intval');
        if (!$page || !$showpage) {
            $this->error('参数不全');
        }
        if (!$this->auth->venue_id) {
            $this->error('请选择所在体育馆');
        }
        $list = json_decode($this->activity->list($this->auth->venue_id, $showpage, $page), true);
        $this->success('ok', $list);
    }

    /**
     * 赛事活动详情
     */
    public function activitydetails()
    {
        $activity_id = $this->request->request('activity_id');
        if (!$activity_id) {
            $this->error('请选择赛事活动');
        }
        $data = json_decode($this->activity->details($activity_id, $this->auth->id, $this->auth->venue_id), true);
        $this->success('ok', $data);
    }

    /**
     * 报名
     * @param $param ['group_buy'] 团购类型：10=开团,20=拼团
     * @param $param ['group_buy_id'] 参团ID
     */
    public function signup()
    {
        $param = $this->request->param();
        if (!$param['name']) {
            $this->error('请输入姓名');
        }
        if (!$param['mobile']) {
            $this->error('请输入手机号');
        }
        if ($param['activity_id'] == 'undefined' || !$param['activity_id']) {
            $this->error('请选择要报名的活动');
        }
        if (!in_array($param['gender'], [10, 20])) {
            $this->error('性别参数错误');
        }
        if (isset($param['group_buy'])) {
            $group_buy = $param['group_buy'];
            unset($param['group_buy']);
        }
        if (isset($param['group_buy_id'])) {
            $group_buy_id = $param['group_buy_id'];
            unset($param['group_buy_id']);
        }
        $param['user_id'] = $this->auth->id;
        $data = db('activity')->where('id', $param['activity_id'])->field('type,venue_id,price,startime,endtime,quota')->find();
        if (strtotime($data['startime']) <= time()) {
            $this->error('活动已开始了');
        }
        if (strtotime($data['endtime']) <= time()) {
            $this->error('活动已结束');
        }
        $total_num = db('signup')->where(['activity_id' => $param['activity_id']])->count();
        if ($total_num >= $data['quota']) {
            $this->error('报名人数已达上限');
        }
        $ids = db('signup')->where(['user_id' => $param['user_id'], 'activity_id' => $param['activity_id']])->value('status');
        if (in_array($ids, ['20', '40'])) {
            $this->error('你已报过名了');
        }
        $param['venue_id'] = $data['venue_id'];
        $type = $data['type'];
        $param['user_id'] = $this->auth->id;
        unset($param['token']);
        $param['createtime'] = time();
        if ($type == '10') {
            //免费
            $param['status'] = '20';
            $order_no = date('Ymd') . substr(implode(null, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
            $add = db('signup')->insertGetId($param);
            $data = [
                'order_no' => $order_no,
                'pay_price' => 0,
                'pay_status' => '20',
                'user_id' => $this->auth->id,
                'pay_type' => '10',
                'createtime' => time(),
                'updatetime' => time(),
                'order_ids' => $add,
                'shop_id' => $param['activity_id'],
                'order_type' => '40',
                'discount_price' => 0,
                'coupons_id' => 0,
                'discount_vip_price' => 0,
                'total_discount_price' => 0,
            ];
            $data['venue_id'] = $this->auth->venue_id;
            $add = db('order')->insertGetId($data);//合并订单
            $this->success('ok', $add);
        } else {
            if (!isset($param['total_price']) && !$param['total_price']) {
                $this->error('请输入当前报名费');
            }
            $param['status'] = '10';
            $total_price = $param['total_price'];
            unset($param['total_price']);
            if (isset($group_buy) && $group_buy) {
                $param['type'] = '20';
            }
            $add = db('signup')->insertGetId($param);
            $order_no = date('Ymd') . substr(implode(null, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
            $group_work_number = db('activity')->where(['id' => $param['activity_id']])->value('group_work_number');
            $time = strtotime(date('Y-m-d H:i:s', strtotime('+' . $group_work_number . 'minute')));
            if (isset($group_buy) && $group_buy == '10') {
                //开团
                $data = [
                    'order_no' => $order_no,
                    'pay_price' => $total_price,
                    'pay_status' => '10',
                    'user_id' => $this->auth->id,
                    'pay_type' => '10',
                    'createtime' => time(),
                    'updatetime' => time(),
                    'order_ids' => $add,
                    'shop_id' => $param['activity_id'],
                    'order_type' => '40',
                    'groupbuying' => '20',
                    'groupbuying_status' => '20',
                    'is_head' => '20',
                    'group_buy_time' => $time,
                    'group_work_number' => $group_work_number,
                ];
            } elseif (isset($group_buy) && $group_buy == '20') {
                // 拼团
                $groupon = db('goods_groupon')->where('id', $group_buy_id)->field('num,current_num,status')->find();
                if ($groupon['current_num'] >= $groupon['num'] || in_array($param['status'], ['invalid', 'finish'])) {
                    $this->error('该团不可用');
                }
                $data = [
                    'order_no' => $order_no,
                    'pay_price' => $total_price,
                    'pay_status' => '10',
                    'user_id' => $this->auth->id,
                    'pay_type' => '10',
                    'createtime' => time(),
                    'updatetime' => time(),
                    'order_ids' => $add,
                    'shop_id' => $param['activity_id'],
                    'order_type' => '40',
                    'groupbuying' => '20',//是否团购:10=否,20=是
                    'groupbuying_status' => '20',//团购状态:10=非团购,20=待成团,30=已成团,40=拼团失败
                    'is_head' => '10',//是否团长:10=否,20=是
                    'collage_sign' => $group_buy_id,//拼团标识
                    'group_buy_time' => $time,
                ];
            } else {
                $discount_vip_price = $data['price'] - $total_price;
                if (isset($param['coupons_id']) && isset($param['discount_price'])) {
                    $status = db('receive')->where(['coupons_id' => $param['coupons_id'], 'user_id' => $this->auth->id])->value('status');
                    if ($status !== '10') {
                        $this->error('优惠券已使用或已过期');
                    }
                    $discount_price = $param['discount_price'];
                    $coupons_id = $param['coupons_id'];
                } else {
                    $discount_price = 0;
                    $coupons_id = 0;
                }
                $data = [
                    'order_no' => $order_no,
                    'pay_price' => $total_price,
                    'pay_status' => '10',
                    'user_id' => $this->auth->id,
                    'pay_type' => '10',
                    'createtime' => time(),
                    'updatetime' => time(),
                    'order_ids' => $add,
                    'shop_id' => $param['activity_id'],
                    'order_type' => '40',
                    'discount_price' => $discount_price,
                    'coupons_id' => $coupons_id,
                    'discount_vip_price' => $discount_vip_price,
                ];
                $data['total_discount_price'] = $data['discount_price'] + $data['discount_vip_price'];
            }
            $data['venue_id'] = $this->auth->venue_id;
            $add = db('order')->insertGetId($data);//合并订单
            if ($add) {
                //合并成功发起支付
                $amount = $total_price;
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
                $title = '场馆通活动报名';
                //回调链接
                $notifyurl = $this->request->root(true) . '/addons/epay/api/notifyx/type/' . $type;
                $returnurl = $this->request->root(true) . '/addons/epay/api/returnx/type/' . $type . '/out_trade_no/' . $out_trade_no;
                $arr = \addons\epay\library\Service::submitOrder($amount, $out_trade_no, $type, $title, $notifyurl, $returnurl, $method, $openid);
                if ($type == 'wechat') {
                    $data = json_decode($arr);
                    $data->order_id = $param['activity_id'];
                    $this->success('ok', $data);
                    $this->success('ok', json_decode($arr));
                } else {
                    $str['sign'] = $arr;
                    $this->success('ok', $str);
                }
            }
        }
    }

    /**
     * 新闻资讯
     */
    public function newslist()
    {
        $page = input('post.page');
        $showpage = input('post.showpage', '10', 'intval');
        if (!$page || !$showpage) {
            $this->error('参数不全');
        }
        if (!$this->auth->venue_id) {
            $this->error('请选择所在体育馆');
        }
        $list = json_decode($this->news->list($this->auth->venue_id, $showpage, $page), true);
        $this->success('ok', $list);
    }

    /**
     * 新闻资讯详情
     */
    public function newsdetails()
    {
        $news_id = $this->request->request('news_id');
        if (!$news_id) {
            $this->error('请选择赛事活动');
        }
        $page = input('post.page');
        $showpage = input('post.showpage', '10', 'intval');
        if (!$page || !$showpage) {
            $this->error('参数不全');
        }
        $data = json_decode($this->news->details($news_id, $this->auth->id, $showpage, $page), true);
        $this->success('ok', $data);
    }

    /**
     * 赛事活动
     */
    public function hotactivitylist()
    {
        $page = input('post.page');
        $showpage = input('post.showpage', '10', 'intval');
        if (!$page || !$showpage) {
            $this->error('参数不全');
        }
        if (!$this->auth->venue_id) {
            $this->error('请选择所在体育馆');
        }
        $list = json_decode($this->activity->list($this->auth->venue_id, $showpage, $page, '10'), true);
        $this->success('ok', $list);
    }

    /**
     * 场馆广告
     */
    public function adlist()
    {
        $page = input('post.page');
        $showpage = input('post.showpage', '10', 'intval');
        if (!$page || !$showpage) {
            $this->error('参数不全');
        }
        if (!$this->auth->venue_id) {
            $this->error('请选择所在体育馆');
        }
        $list['list'] = db('ad')
            ->where(['venue_id' => $this->auth->venue_id, 'status' => '10'])
            ->field('id,images,title,subtitle')
            ->order('createtime DESC')
            ->limit($showpage)
            ->page($page)
            ->select();
        foreach ($list['list'] as &$v) {
            $v['images'] = cdnurl(explode(',', $v['images'])[0], true);
        }
        $list['count'] = db('ad')
            ->where(['venue_id' => $this->auth->venue_id, 'status' => '10'])
            ->count();
        $list['total_page'] = ceil($list['count'] / $showpage);
        $this->success('ok', $list);
    }

    /**
     * 公告
     */
    public function noticevalue()
    {
        $data = db('notice')->where(['venue_id' => $this->auth->venue_id, 'status' => '10'])->field('id,title as content,createtime')->select();
        foreach ($data as &$v) {
            $v['createtime'] = Date::human($v['createtime']);
        }
        $this->success('ok', $data);
    }

    /**
     * 公告详情
     */
    public function noticedetails()
    {
        $notice_id = $this->request->request('notice_id');
        if (!$notice_id) {
            $this->error('参数不全');
        }
        $data = db('notice')->where('id', $notice_id)->field('id,title,content,createtime')->select();
        foreach ($data as &$v) {
            $v['createtime'] = Date::human($v['createtime']);
            $v['content'] = replacePicUrl($v['content'], config('fastadmin.url'));
        }
        $this->success('ok', $data);
    }

    /**
     * 最新推荐
     */
    public function newactivitylist()
    {
        $page = input('post.page');
        $showpage = input('post.showpage', '10', 'intval');
        if (!$page || !$showpage) {
            $this->error('参数不全');
        }
        if (!$this->auth->venue_id) {
            $this->error('请选择所在体育馆');
        }
        $list = json_decode($this->activity->list($this->auth->venue_id, $showpage, $page, '20'), true);
        $this->success('ok', $list);
    }

    /**
     * 取消活动
     */
    public function cancel_activity()
    {
        $order_id = $this->request->request('order_id');
        if (!$order_id) {
            $this->error('参数不全');
        }
        $order = db('order')->where('id', $order_id)->find();
        $activity_id = db('signup')->where('id', $order['order_ids'])->value('activity_id');
        $type = db('activity')->where('id', $activity_id)->value('type');
        if ($type == '10') {
            # 免费
            $del = db('signup')->where(['user_id' => $this->auth->id, 'activity_id' => $activity_id])->delete();
            db('order')->where('id', $order_id)->delete();
        } else {
            if (!$order['pay_status'] == '10') {
                # 未支付
                db('order')->where('id', $order_id)->delete(); #删除对应订单
                $del = db('signup')->where('id', $order['order_ids'])->delete(); #删除报名记录
            } else {
                # 已支付
                if ($order['groupbuying'] == '20') {
                    if ($order['is_head'] == '20') {
                        # 团购订单且是团长
                        $num = db('goods_groupon_log')->where('order_id', $order['order_ids'])->count();
                        if ($num > 1) {
                            $this->error('已有人拼团无法取消');
                        } else {
                            db('goods_groupon_log')->where('order_id', $order['order_ids'])->delete();
                            db('goods_groupon')->where('order_id', $order['order_ids'])->delete();
                            $del = db('order')->where('id', $order_id)->delete(); #删除对应订单
                        }
                    } else {
                        db('goods_groupon_log')->where('order_id', $order['order_ids'])->delete();
                        $del = db('order')->where('id', $order_id)->delete(); #删除对应订单
                    }
                } else {
                    db('order')->where('id', $order_id)->delete(); #删除对应订单
                    $del = db('signup')->where('id', $order['order_ids'])->delete(); #删除报名记录
                }
                $order['pay_price'] = 0.01;
                $del = $this->unexamine($order['pay_price'], $order['order_no'], $activity_id);
                $before = db('venue')->where('id', $this->auth->venue_id)->value('money');
                $this->venuelog->addvenuemongylog($this->auth->venue_id, $order['pay_price'], $before, '取消报名退款', '80');
            }
        }
        if ($del) {
            $this->success('取消成功', $del);
        } else {
            $this->error('取消失败', $del);
        }
    }

    /**
     * 广告详情
     */
    public function addetails()
    {
        $ad_id = $this->request->request('ad_id');
        if (!$ad_id) {
            $this->error('请选择要查看的广告');
        }
        $data = db('ad')->where('id', $ad_id)->find();
        $data['venue_name'] = db('venue')->where('id', $data['venue_id'])->value('name');
        $data['content'] = replacePicUrl($data['content'], config('fastadmin.url'));
        $data['createtime'] = date('Y/m/d H:i', $data['createtime']);
        $data['images'] = explode(',', $data['images']);
        foreach ($data['images'] as &$v) {
            $v = cdnurl($v, true);
        }
        $this->success('ok', $data);
    }

    /**
     * 轮播图详情
     */
    public function bannerdetails()
    {
        $banner_id = $this->request->request('banner_id');
        if (!$banner_id) {
            $this->error('请选择要查看的广告');
        }
        $data = db('banner')->where('id', $banner_id)->find();
        $data['venue_name'] = db('venue')->where('id', $data['venue_id'])->value('name');
        $data['content'] = replacePicUrl($data['content'], config('fastadmin.url'));
        $data['createtime'] = date('Y/m/d H:i', $data['createtime']);
        $data['image'] = cdnurl($data['image'], true);
        $this->success('ok', $data);
    }

    /**
     * 微信退款
     */
    public function unexamine($price = '', $order_no = '', $activity_id = '', $type = '')
    {
        //微信
        $data = [
            'total_fee' => $price * 100, //订单金额  单位 转为分
            'refund_fee' => $price * 100, //退款金额 单位 转为分
            'sign_type' => 'MD5', //签名类型 支持HMAC-SHA256和MD5，默认为MD5
            'out_trade_no' => $order_no, //商户订单号
            'out_refund_no' => $order_no, //商户退款单号
            // 'type' => 'miniapp'
        ];
        $wechat = Service::getConfig('wechat');
        $data['sign'] = self::getSign($data, $wechat['key']);
        $pay = Pay::wechat($wechat);
        $row = $pay->refund($data);
        $row = json_decode($row);
        if ($row->return_code == 'SUCCESS') {
            if (!$type) {
                //取消报名
                $del = db('signup')->where(['user_id' => $this->auth->id, 'activity_id' => $activity_id])->delete();
            } else {
                // 取消课程
                // 启动事务
                Db::startTrans();
                try {
                    db('order')->where(['user_id' => $this->auth->id, 'id' => $activity_id])->delete();
                    db('litestore_order')->where(['user_id' => $this->auth->id, 'id' => $type])->delete();
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                }
            }
            return true;
        } else {
            // $this->error('操作失败');
            return false;
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

    /**
     * 取消课程
     */
    public function cancel_course()
    {
        $order_id = $this->request->post('order_id');
        if (!$order_id) {
            $this->error('请选择你要取消的订单');
        }
        $order = db('order')->where('order_ids', $order_id)->where('order_type', '20')->field('id as order_id,coupons_id,pay_price,order_no,groupbuying_status,groupbuying,is_head')->find();
        $order['pay_price'] = 0.01;
        if ($order['groupbuying'] == '20') {
            if ($order['is_head'] == '20') {
                # 团购订单且是团长
                $num = db('goods_groupon_log')->where('order_id', $order_id)->count();
                if ($num > 1) {
                    $this->error('已有人拼团无法取消');
                }
            }
        }
        // 启动事务
        Db::startTrans();
        try {
            if ($order['groupbuying'] == '20') {
                if ($order['is_head'] == '20') {
                    # 团购订单且是团长
                    db('goods_groupon_log')->where('order_id', $order_id)->delete();
                    db('goods_groupon')->where('order_id', $order_id)->delete();
                } else {
                    db('goods_groupon_log')->where('order_id', $order_id)->delete();
                    db('goods_groupon')->where('order_id', $order_id)->delete();
                }
            } else {
            }
            if ($order['coupons_id']) {
                db('receive')->where(['user_id' => $this->auth->id, 'coupons_id' => $order['coupons_id']])->update(['status' => '10']);
            }
            $this->unexamine($order['pay_price'], $order['order_no'], $order['order_id'], $order_id);
            $before = db('venue')->where('id', $this->auth->venue_id)->value('money');
            $this->venuelog->addvenuemongylog($this->auth->venue_id, $order['pay_price'], $before, '取消课程退款', '80');
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error('取消失败,请检查账户余额是否充足');
        }
        $this->success('取消成功');
    }

    /**
     * 报名
     * @param $param ['group_buy'] 团购类型：10=开团,20=拼团
     * @param $param ['group_buy_id'] 参团ID
     */
    public function payorder()
    {
        $order_id = $this->request->request('order_id');
        if (!$order_id) {
            $this->error('参数不全');
        }
        $order = db('order')->where('id', $order_id)->find();
        $signup = db('signup')->where('id', $order['order_ids'])->find();
        if (!$signup) {
            $this->error('订单不存在');
        }
        $data = db('activity')->where('id', $signup['activity_id'])->field('type,venue_id,startime,endtime,quota')->find();
        if (strtotime($data['startime']) <= time()) {
            $this->error('活动已开始了');
        }
        if (strtotime($data['endtime']) <= time()) {
            $this->error('活动已结束');
        }
        $total_num = db('signup')->where(['activity_id' => $signup['activity_id']])->count();
        if ($total_num >= $data['quota']) {
            $this->error('报名人数已达上限');
        }
        $ids = db('signup')->where(['user_id' => $this->auth->id, 'activity_id' => $signup['activity_id'], 'status' => '20'])->value('id');
        if ($ids) {
            $this->error('你已报过名了');
        }
        //合并成功发起支付
        $amount = $order['pay_price'];
        if (!$amount || $amount < 0) {
            $this->error("支付金额必须大于0");
        }
        $amount = 0.01;
        $method = 'miniapp';
        $type = 'wechat';
        $openid = db('third')->where('user_id', $this->auth->id)->value('openid');
        //订单号
        $out_trade_no = $order['order_no'];
        //订单标题
        $title = '场馆通';
        //回调链接
        $notifyurl = $this->request->root(true) . '/addons/epay/api/notifyx/type/' . $type;
        $returnurl = $this->request->root(true) . '/addons/epay/api/returnx/type/' . $type . '/out_trade_no/' . $out_trade_no;
        $arr = \addons\epay\library\Service::submitOrder($amount, $out_trade_no, $type, $title, $notifyurl, $returnurl, $method, $openid);
        if ($type == 'wechat') {
            $data = json_decode($arr);
            $data->order_id = $signup['activity_id'];
            $this->success('ok', $data);
            $this->success('ok', json_decode($arr));
        } else {
            $str['sign'] = $arr;
            $this->success('ok', $str);
        }
    }

    public function goodslist()
    {
        $param = $this->request->param();
        if (!$param['data']) {
            $param['data'] = session('venue_id');
        }
        $list = db('litestore_goods')->where('venue_id', $param['data'])->where('type', '10')->field('goods_id,goods_name')->select();
        $this->success('ok', $list);
    }

    public function getactivitylist()
    {
        $param = $this->request->param();
        if (!$param['data']) {
            $param['data'] = session('venue_id');
        }
        $list = db('activity')->where('venue_id', $param['data'])->field('id,name')->select();
        $this->success('ok', $list);
    }

}
