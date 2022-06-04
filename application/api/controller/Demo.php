<?php

namespace app\api\controller;

use app\common\controller\Api;
use addons\litestore\model\Litestoreorder;
use think\Log;
use think\Db;
use app\common\model\Order as ordermodel;
use think\Exception;
use fast\Http;
use app\common\model\Venuelog;

/**
 * 示例接口
 */
class Demo extends Api
{

    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    //
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['test', 'test1', 'orderdetails', 'ceshi', 'ss', 'ceshi0', 'sessionsvalue'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new Litestoreorder;
        $user = $this->auth->getUser();
        $this->order = new ordermodel();
    }

    /**
     * 处理场馆VIP订单支付结果
     * 1、增加用户所属场馆VIP记录【包括到期时间等】
     * 2、
     */
    public function demo()
    {
        $out_trade_no = $this->request->request('order_no');
        $order = db('order')->where('order_no', $out_trade_no)->find(); # 订单信息
        $userviplog = db('user_vip_log')->where('id', $order['order_ids'])->find();
        $uservip = db('user_vip')->where(['venuevip_id' => $userviplog['venuevip_id'], 'venue_id' => $userviplog['venue_id'], 'user_id' => $userviplog['user_id']])->find(); # 查询当前用户是否开通过VIP
        if ($order['pay_status'] == '20') {
            $this->error('订单已支付');
        }
        // 启动事务
        Db::startTrans();
        try {
            if ($uservip['id'] && $uservip['endtime'] > time()) {
                # 当前用户开通过且未过期
                if ($userviplog['type'] == '10') {
                    # 月卡
                    $data['endtime'] = strtotime(date('Y-m-d H:i:s', strtotime("+1month", $uservip['endtime'])));
                } elseif ($userviplog['type'] == '20') {
                    # 季卡
                    $data['endtime'] = strtotime(date('Y-m-d H:i:s', strtotime("+3month", $uservip['endtime'])));
                } else {
                    # 年卡
                    $data['endtime'] = strtotime(date('Y-m-d H:i:s', strtotime("+12month", $uservip['endtime'])));
                }
                $update = db('user_vip')->where('id', $uservip['id'])->update(['endtime' => $data['endtime'], 'updatetime' => time()]);
            } else {
                if ($userviplog['type'] == '10') {
                    # 月卡
                    $data['endtime'] = strtotime("+1 month");
                } elseif ($userviplog['type'] == '20') {
                    # 季卡
                    $data['endtime'] = strtotime("+3 month");
                } else {
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
            db('order')->where('order_no', $out_trade_no)->update(['pay_status' => '20', 'pay_time' => time()]); # 更新订单状态
            db('user_vip_log')->where('id', $order['order_ids'])->update(['status' => '20', 'paytime' => time()]); # 更新订单状态
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error($e->getMessage());
        }
    }

    public function test()
    {
        try {
            $payamount = $type == 'alipay' ? $data['total_amount'] : $data['total_fee'] / 100;
            $out_trade_no = $data['out_trade_no'];
            $orderdata = db('order')->where('order_no', $out_trade_no)->find();
            //你可以在此编写订单逻辑
            //修改主订单状态
            if (in_array($orderdata['order_type'], ['10', '20', '30', '40', '50'])) {
                $user->paysuccess($out_trade_no);
                $orderdata = db('order')->where('order_no', $out_trade_no)->find();
                if (in_array($orderdata['order_type'], ['20', '40']) && $orderdata['groupbuying_status'] !== '10') {
                    // $user->paysuccess($out_trade_no);
                    # 团购且是课程订单、活动订单
                    db('order')->where('order_no', $out_trade_no)->update(['pay_status' => '20', 'groupbuying_status' => '20', 'pay_time' => time(), 'updatetime' => time()]);
                    $orderdata = db('order')->where('order_no', $out_trade_no)->find();
                    \addons\faqueue\library\QueueApi::push("app\job\Groupbuy@fire", $orderdata, 'Groupbuy');
                }
            } else {
                # 购买VIP订单处理方式
                $user->handleorder($out_trade_no);
            }
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * 订单支付
     * @param['order_no'] 总订单号
     */
    public function payorder()
    {
        $param = $this->request->param();
        if (!$param['order_no']) {
            $this->error('请选择要支付的订单');
        }
        $order = db('order')->where('order_no', $param['order_no'])->find();
        if ($order['pay_status'] == '20') {
            $this->error('该订单已支付');
        }
        # 订单类型:10=订场订单,20=订票订单,30=购物订单,40=活动订单,50=点餐订单,60=开通VIP
        if ($order['order_type'] == '10') {
            # 订场订单
            $ordergoods = db('litestore_order_goods')->where('order_id', 'in', explode(',', $order['order_ids']))->field('time,time_slot')->select();
        } elseif ($order['order_type'] == '20') {
            # 课程订单
        } elseif ($order['order_type'] == '30') {
            # 购物订单
        } elseif ($order['order_type'] == '40') {
            # 活动订单
        } elseif ($order['order_type'] == '50') {
            # 点餐订单
        } elseif ($order['order_type'] == '60') {
            # 开通VIP
        }
    }

    public function ceshi()
    {
        $order_id = '811';#830\811\837
        $orders = db('litestore_order_goods')->where('order_id', $order_id)->field('id,reservation_id,date,time,time_slot')->select();
        // var_dump($orders);
        $s = 0;
        $sum = count($orders) - 1;
        foreach ($orders as $k => &$v) {
            if ($k < $sum) {
                $s = $k + 1;
            }
            $orders[$k]['star'] = $orders[$k]['time'] . ' ' . explode('-', $orders[$k]['time_slot'])[0];
            $orders[$k]['end'] = $orders[$k]['time'] . ' ' . explode('-', $orders[$k]['time_slot'])[1];
            $orders[$s]['star'] = $orders[$s]['time'] . ' ' . explode('-', $orders[$s]['time_slot'])[0];
            $orders[$s]['end'] = $orders[$s]['time'] . ' ' . explode('-', $orders[$s]['time_slot'])[1];
            $orders[$sum]['end'] = $orders[$sum]['time'] . ' ' . explode('-', $orders[$sum]['time_slot'])[1];
            if ($orders[$k]['date'] == $orders[$s]['date'] && $orders[$k]['time'] == $orders[$s]['time'] && $orders[$k]['reservation_id'] == $orders[$s]['reservation_id']) {
                # 有相同的场地
                if ($orders[$k]['end'] == $orders[$s]['star']) {
                    $open['litestore_order_goods_id'] = $v['id'];
                    $open['type'] = '20';
                    $opentime = strtotime($orders[0]['star']) - strtotime(date('Y-m-d H:i', time()));
                    $close['litestore_order_goods_id'] = $v['id'];
                    $close['type'] = '10';
                    $closetime = strtotime($orders[$sum]['end']) - strtotime(date('Y-m-d H:i', time()));
                    \think\Queue::later($opentime, 'app\job\Open@expire', $open, 'Open');
                    \think\Queue::later($closetime, 'app\job\Open@expire', $close, 'Open');
                    die;
                } else {
                    $v['open']['litestore_order_goods_id'] = $v['id'];
                    $v['open']['type'] = '20';
                    $v['opentime'] = strtotime($orders[$k]['star']) - strtotime(date('Y-m-d H:i', time()));
                    $v['close']['litestore_order_goods_id'] = $v['id'];
                    $v['close']['type'] = '10';
                    $v['closetime'] = strtotime($orders[$k]['end']) - strtotime(date('Y-m-d H:i', time()));
                    \think\Queue::later($v['opentime'], 'app\job\Open@expire', $v['open'], 'Open');
                    \think\Queue::later($v['closetime'], 'app\job\Open@expire', $v['close'], 'Open');
                    var_dump($v['close']);
                    var_dump($v['open']);
                    var_dump($v['opentime']);
                    var_dump($v['closetime']);
                }
            } else {
                #无相同的场地
                $v['open']['litestore_order_goods_id'] = $v['id'];
                $v['open']['type'] = '20';
                $v['opentime'] = strtotime($orders[$k]['star']) - strtotime(date('Y-m-d H:i', time()));
                $v['close']['litestore_order_goods_id'] = $v['id'];
                $v['close']['type'] = '10';
                $v['closetime'] = strtotime($orders[$k]['end']) - strtotime(date('Y-m-d H:i', time()));
                \think\Queue::later($v['opentime'], 'app\job\Open@expire', $v['open'], 'Open');
                \think\Queue::later($v['closetime'], 'app\job\Open@expire', $v['close'], 'Open');
            }
        }
    }

    public function ss()
    {
        $orderdata = db('order')->where('order_no', '2022022452565252')->find();
        if ($orderdata['order_type'] == '10') {
            # 订场订单
            $orders = [];
            $orders = db('litestore_order_goods')->where('order_id', $orderdata['order_ids'])->field('id,reservation_id,date,time,time_slot')->select();
            if ($orders) {
                $s = 0;
                $sum = count($orders) - 1;
                // $open = [];
                // $close = [];
                foreach ($orders as $k => &$v) {
                    if ($k < $sum) {
                        $s = $k + 1;
                    }
                    $orders[$k]['star'] = $orders[$k]['time'] . ' ' . explode('-', $orders[$k]['time_slot'])[0];
                    $orders[$k]['end'] = $orders[$k]['time'] . ' ' . explode('-', $orders[$k]['time_slot'])[1];
                    $orders[$s]['star'] = $orders[$s]['time'] . ' ' . explode('-', $orders[$s]['time_slot'])[0];
                    $orders[$s]['end'] = $orders[$s]['time'] . ' ' . explode('-', $orders[$s]['time_slot'])[1];
                    $orders[$sum]['end'] = $orders[$sum]['time'] . ' ' . explode('-', $orders[$sum]['time_slot'])[1];
                    if ($orders[$k]['date'] == $orders[$s]['date'] && $orders[$k]['time'] == $orders[$s]['time'] && $orders[$k]['reservation_id'] == $orders[$s]['reservation_id']) {
                        # 有相同的场地
                        if ($orders[$k]['end'] == $orders[$s]['star']) {
                            $open[$k]['litestore_order_goods_id'] = $v['id'];
                            $open[$k]['type'] = '20';
                            $open[$k]['opentime'] = strtotime($orders[0]['star']) - strtotime(date('Y-m-d H:i:s', time()));
                            $close[$k]['litestore_order_goods_id'] = $v['id'];
                            $close[$k]['type'] = '10';
                            $close[$k]['closetime'] = strtotime($orders[$sum]['end']) - strtotime(date('Y-m-d H:i:s', time()));
                            // die;
                        } else {
                            $open[$k]['litestore_order_goods_id'] = $v['id'];
                            $open[$k]['type'] = '20';
                            $open[$k]['opentime'] = strtotime($orders[$k]['star']) - strtotime(date('Y-m-d H:i:s', time()));
                            $close[$k]['litestore_order_goods_id'] = $v['id'];
                            $close[$k]['type'] = '10';
                            $close[$k]['closetime'] = strtotime($orders[$k]['end']) - strtotime(date('Y-m-d H:i:s', time()));
                        }
                    } else {
                        #无相同的场地
                        $open[$k]['litestore_order_goods_id'] = $v['id'];
                        $open[$k]['type'] = '20';
                        $open[$k]['opentime'] = strtotime($orders[$k]['star']) - strtotime(date('Y-m-d H:i:s', time()));
                        $close[$k]['litestore_order_goods_id'] = $v['id'];
                        $close[$k]['type'] = '10';
                        $close[$k]['closetime'] = strtotime($orders[$k]['end']) - strtotime(date('Y-m-d H:i:s', time()));
                    }
                }
                if ($open && $close) {
                    foreach ($open as $k => $v) {
                        \think\Queue::later($v['opentime'], 'app\job\Open@fire', $v, 'Open');
                    }
                    foreach ($close as $k => $v) {
                        \think\Queue::later($v['closetime'], 'app\job\Open@fire', $v, 'Open');
                    }
                    // \think\Queue::later($close['closetime'], 'app\job\Open@fire', $close,'Open');
                }
            }
        }
    }

    public function operation($litestore_order_goods_id = '', $type = '')
    {
        // $litestore_order_goods_id = '918';
        // $type ='10';#操作类型：20：开启；10：关闭
        $orders = db('litestore_order_goods')->where('id', $litestore_order_goods_id)->field('order_id,reservation_id,date,time,time_slot')->find();
        $ids = db('order')->where('order_ids', $orders['order_id'])->value('id');
        $data = db('lamplist')->where(['reservation_id' => $orders['reservation_id'], 'field_name' => $orders['date']])->find();
        if ($ids && $data['id']) {
            $url = 'http://120.79.196.238:8001/light';
            if ($type == '20') {
                # open
                $datas = [
                    'id' => $data['lamp_id'],
                    'action' => 'open' . $data['number'],
                ];
            } else {
                # close
                $datas = [
                    'id' => $data['lamp_id'],
                    'action' => 'close' . $data['number'],
                ];
            }
            Log::error($datas);
            $result = Http::http_post_json($url, json_encode($datas, true));
            if (json_decode($result[1], true)['result'] == 'OK') {
                db('lamplist')->where('id', $data['id'])->update(['status' => $type]);
            }
        }
    }

    /**
     * ceshi
     * 订单类型:10=订场订单,20=订票订单,30=购物订单,40=活动订单,50=点餐订单,60=开通VIP
     * 交易类型:10=充值VIP,20=订场,30=课程,40=购物,50=活动,60=点餐,70=提现,80=退款
     */
    public function ceshi0()
    {
        // $out_trade_no = '2022012556525798';
        // $orderdata = db('order')->where('order_no', $out_trade_no)->find();
        $venuelog = new Venuelog();
        $venue_id = db('user')->where('id', $orderdata['user_id'])->value('venue_id');
        $before = db('venue')->where('id', $venue_id)->value('money');
        if ($orderdata['order_type'] == '10') {
            # 订场
            $memo = '订场进账';
            $type = '20';
        } elseif ($orderdata['order_type'] == '20') {
            # 课程
            $memo = '课程进账';
            $type = '30';
        } elseif ($orderdata['order_type'] == '30') {
            # 购物
            $memo = '购物进账';
            $type = '40';
        } elseif ($orderdata['order_type'] == '40') {
            # 活动
            $memo = '活动进账';
            $type = '50';
        } elseif ($orderdata['order_type'] == '50') {
            # 点餐
            $memo = '点餐进账';
            $type = '60';
        } elseif ($orderdata['order_type'] == '60') {
            # 开通VIP
            $memo = '开通VIP进账';
            $type = '10';
        }
        $venuelog->addvenuemongylog($venue_id, $orderdata['pay_price'], $before, $memo, $type);
    }

    /**
     * 场次列表
     */
    public function sessionsvaluess()
    {
        $param = $this->request->param();
        if (!isset($param['reservation_id']) || empty($param['reservation_id'])) {
            $this->error('请选择场馆');
        }
        for ($i = 0; $i < 7; $i++) {
            $dateArray[$i] = date('Y-m-d', strtotime(date('Y-m-d') . '+' . $i . 'day'));
        };
        $time = get_date($dateArray);//调用函数
        foreach ($time as $k => &$v) {
            $v['datas'] = [];
            $v['datas'] = db('ball_date')->where(['time' => $v['date'], 'reservation_id' => $param['reservation_id']])->field('*')->find();
            $v['datas']['big_content'] = json_decode($v['datas']['big_content'], true);
            if ($v['datas']['big_content']) {
                foreach ($v['datas']['big_content'] as &$vv) {
                    foreach ($vv as $k => &$vs) {
                        if (stripos($vs[0], ':')) {
                            $vs['time'] = $vs[0] . '-' . $vs[1];
                            unset($vs[0]);
                            unset($vs[1]);
                        }
                    }
                }
                $name = db('reservation')->where('id', $param['reservation_id'])->value('field_name');
                unset($v['datas']['small_content']);
                $v['datas']['list'] = [];
                $v['optional'] = [];
                $v['notoptional'] = [];
                $v['times'] = [];
                foreach ($v['datas']['big_content']['big_money'] as $key => $value) {
                    foreach ($value as $keys => $values) {
                        $v['datas']['list'][$key][$keys]['price'] = $values;
                        $v['num'] = $keys + 1;
                        $v['datas']['list'][$key][$keys]['field_name'] = $name . $v['num'];
                        if ($values) {
                            $v['datas']['list'][$key][$keys]['status'] = '20';//可预定
                            $order_id = db('litestore_order_goods')->where('date', $v['datas']['list'][$key][$keys]['field_name'])->where(['time' => $v['datas']['time'], 'time_slot' => $v['datas']['big_content']['big_time'][$key]['time']])->value('order_id');
                            $status = db('litestore_order')->where('id', $order_id)->value('status');
                            if ($status !== '10' && $status !== null) {
                                $ids = db('litestore_order_goods')->where('date', 'in', $v['datas']['list'][$key][$keys]['field_name'])->where(['time' => $param['time'], 'time_slot' => $v['datas']['big_content']['big_time'][$key]['time']])->value('id');
                                if ($ids) {
                                    array_push($times, $ids);
                                    $v['datas']['list'][$key][$keys]['status'] = '30';//可预定
                                }
                            }
                            array_push($v['optional'], $v['datas']['list'][$key][$keys]['status']);
                        } else {
                            $v['datas']['list'][$key][$keys]['status'] = '10';//不可选
                            array_push($v['notoptional'], $v['datas']['list'][$key][$keys]['status']);
                        }
                    }
                }
                foreach ($v['datas']['big_content']['big_time'] as $k => &$vs) {
                    $vs['big_money'] = $v['datas']['list'][$k];
                    if (strtotime($v['date'] . ' ' . explode('-', $vs['time'])[0]) <= strtotime(date('Y-m-d H:i', time()))) {
                        foreach ($vs['big_money'] as &$vv) {
                            $vv['status'] = '10';
                            $vv['price'] = '';
                        }
                    }
                }
                if ($v['times']) {
                    $v['datas']['occupy'] = count($v['times']);//已预约
                } else {
                    $v['datas']['occupy'] = 0;
                }
                $v['datas']['optional'] = count($v['optional']) - $v['datas']['occupy'];//可选
                $v['datas']['notoptional'] = count($v['notoptional']);//不可选
                unset($v['datas']['list']);
                unset($v['datas']['big_content']['big_money']);
                unset($v['id']);
                unset($v['date']);
                unset($v['week']);
            }
        }

        $this->success('ok', $time);
    }
}
