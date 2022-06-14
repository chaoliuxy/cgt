<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Course as courseModel;
use app\common\model\Activity;

/**
 * 球馆接口
 */
class Reservation extends Api
{
    protected $noNeedLogin = ['venue', 'sporttypelist', 'venuelist'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->coursemodel = new courseModel();
        $this->activity = new Activity();
    }

    /**
     * 球馆类型
     */
    public function typelist()
    {
        $list = db('type')->where('venue_id', $this->auth->venue_id)->field('id,type_name as name')->order('weigh DESC')->select();
        $this->success('ok', $list);
    }


    /**
     * 球馆列表
     */
    public function reservationlist()
    {
        $param = $this->request->param();
        if (!$param['type_id']) {
            $this->error('请选择球馆类型');
        }
        if (!$param['page'] || !$param['showpage']) {
            $this->error('参数不全');
        }
        $list['list'] = db('reservation')
            ->where(['type_id' => $param['type_id'],'status'=>'20'])->field('id,images,name,address,lat,lng')
            ->order('createtime DESC')
            ->limit($param['showpage'])
            ->page($param['page'])->select();
        foreach ($list['list'] as &$v) {
            $v['images'] = cdnurl(explode(',', $v['images'])[0], true);
        }
        $list['count'] = db('reservation')
            ->where(['type_id' => $param['type_id']])->field('id,images,name,address,lat,lng')->count();
        $list['total_page'] = ceil($list['count'] / $param['showpage']);
        $this->success('ok', $list);
    }

    /**
     * 订场订票
     */
    public function bookinglist()
    {
        $param = $this->request->param();
        if (!$param['lat'] || !$param['lng']) {
            $this->error('请输入当前经纬度');
        }
        if (!$param['page'] || !$param['showpage']) {
            $this->error('请输入当前页数和每页显示数量');
        }
        $list['list'] = db('reservation')->where('type', '20');
        $list['count'] = db('reservation')->where('type', '20');
        if ($param['type_id']) {
            $list['list'] = $list['list']->where('type_id', $param['type_id']);
            $list['count'] = $list['list']->where('type_id', $param['type_id']);
        }
        if ($param['key']) {
            $list['list'] = $list['list']->where('name|address', 'like', '%' . $param['key'] . '%');
            $list['count'] = $list['list']->where('name|address', 'like', '%' . $param['key'] . '%');
        }
        $list['list'] = $list['list']->field('id,images,name,address,score,lat,lng,round(sqrt( ( ((' . $param['lng'] . '-lng)*PI()*12656*cos(((' . $param['lat'] . '+lat)/2)*PI()/180)/180) * ((' . $param['lng'] . '-lng)*PI()*12656*cos (((' . $param['lat'] . '+lat)/2)*PI()/180)/180) ) + ( ((' . $param['lat'] . '-lat)*PI()*12656/180) * ((' . $param['lat'] . '-lat)*PI()*12656/180) ) )/2,2) as dis');
        if ($param['distance_type'] == '10') {
            # 距离正序
            $list['list'] = $list['list']->order('dis');
        } else {
            $list['list'] = $list['list']->order('dis DESC');
        }
        if ($param['praise_type'] == '10') {
            # 评分正序
            $list['list'] = $list['list']->order('score');
        } else {
            # 评分倒序
            $list['list'] = $list['list']->order('score DESC');
        }
        $list['list'] = $list['list']->limit($param['showpage'])->page($param['page'])->select();
        foreach ($list['list'] as &$v) {
            $v['images'] = cdnurl(explode(',', $v['images'])[0], true);
        }
        $list['count'] = db('reservation')->count();
        $list['total_page'] = ceil($list['count'] / $param['showpage']);
        $this->success('ok', $list);
    }

    /**
     * 订场订票ball_date
     */
    public function details()
    {
        $reservation_id = $this->request->request('reservation_id');
        if (!$reservation_id) {
            $this->error('请选择球馆');
        }
        $data = db('reservation')->where(['id' => $reservation_id])->field('*')->find();
        if ($data) {
            $data['images'] = explode(",", $data['images']);
            foreach ($data['images'] as &$v) {
                $v = cdnurl($v, true);
            }
            $data['facilities_ids'] = explode(",", $data['facilities_ids']);
            $data['facilities_text'] = '';
            foreach ($data['facilities_ids'] as &$v) {
                $v = db('facilities')->where('id', $v)->value('name');
                $data['facilities_text'] .= $v . '、';
            }
            unset($data['type_id']);
            unset($data['facilities_ids']);
            $data['date_list'] = db('ball_date')->where('reservation_id', $data['id'])->field('*')->select();
            foreach ($data['date_list'] as $k => &$v) {
                $v['week'] = get_week($v['time']);
                $v['big_content'] = json_decode($v['big_content'], 1);
                $num = $k + 1;
                $v['field'] = $data['field_name'] . $num;
                foreach ($v['big_content'] as &$vv) {
                    foreach ($vv as &$vs) {
                        if (count($vs) == 2) {
                            $vs['time'] = $vs[0] . '-' . $vs[1];
                            unset($vs[0]);
                            unset($vs[1]);
                        }
                    }
                }
                unset($v['small_content']);
            }
        } else {
            $data['date_list'] = [];
        }
        $this->success('ok', $data);
    }

    /**
     * 超值团购
     * @param $param ['type'] 类型:10=课程,20=活动
     */
    public function groupbuyinglist()
    {
        $param = $this->request->param();
        if (!$param['page'] || !$param['showpage']) {
            $this->error('参数不全');
        }
        if (!in_array($param['type'], [10, 20])) {
            $this->error('类型参数错误');
        }
        if ($param['type'] == '10') {
            # 课程
            if (!$param['lat'] || !$param['lng']) {
                $this->error('请输入经纬度');
            }
            $list = json_decode($this->coursemodel->list($this->auth->venue_id, $param['page'], $param['showpage'], $param['lat'], $param['lng'], '', '', '10'), true);
            foreach ($list['list'] as &$v) {
                $v['groupon_log_id'] = db('goods_groupon_log')->where('is_refund', '0')->where('user_id', $this->auth->id)->where('goods_id', $v['goods_id'])->value('id');
                if ($v['groupon_log_id']) {
                    $v['is_partake'] = '10'; #已参与
                } else {
                    $v['is_partake'] = '20'; # 未参与
                }
            }
        } else {
            # 活动
            $list = json_decode($this->activity->list($this->auth->venue_id, $param['showpage'], $param['page'], '', '10'), true);
            foreach ($list['list'] as &$v) {
                $v['groupon_log_id'] = db('goods_groupon_log')->where('is_refund', '0')->where('user_id', $this->auth->id)->where('goods_id', $v['id'])->value('id');
                if ($v['groupon_log_id']) {
                    $v['is_partake'] = '10'; #已参与
                } else {
                    $v['is_partake'] = '20'; # 未参与
                }
            }
        }
        $this->success('ok', $list);
    }

    /**
     * 订场订票ball_date
     */
    public function groupbuyingdetails()
    {
        $id = $this->request->request('id');
        $type = $this->request->request('type');
        if (!$id) {
            $this->error('请选择要查看的团购');
        }
        if (!in_array($type, ['10', '20'])) {
            $this->error('类型参数错误');
        }
        if ($type == '10') {
            # 课程
            $data = json_decode($this->coursemodel->details($id), true);
            $data['detail']['content'] = replacePicUrl($data['detail']['content'], config('fastadmin.url'));
            $data['detail']['course_content'] = replacePicUrl($data['detail']['course_content'], config('fastadmin.url'));
            foreach ($data['detail']['groupon'] as &$v) {
                $v['list'] = db('goods_groupon_log')->where('groupon_id', $v['id'])->field('id as groupon_id,user_avatar,is_refund,user_id')->order('createtime')->select();
                $v['userid'] = [];
                foreach ($v['list'] as $kk => &$vv) {
                    array_push($v['userid'], $vv['user_id']);
                    if ($vv['user_avatar']) {
                        $vv['user_avatar'] = cdnurl($vv['user_avatar'], true);
                    } else {
                        $vv['user_avatar'] = cdnurl('/assets/img/avatar.png', true);
                    }
                }
                $v['surplus_num'] = $v['num'] - $v['current_num'];
                if ($v['status'] == 'finish') {
                    $v['join_text'] = '已经完成';
                } elseif ($v['status'] == 'invalid') {
                    $v['join_text'] = '已过期';
                } elseif ($v['status'] == 'ing') {
                    if (in_array($this->auth->id, $v['userid'])) {
                        $v['join_text'] = '已参与';
                    } else {
                        $v['join_text'] = '未参与';
                    }
                } elseif ($v['status'] == 'finish-fictitious') {
                    $v['join_text'] = '虚拟成团';
                }
                unset($v['userid']);
            }
            $this->success('ok', $data);
        } else {
            # 活动
            $data = json_decode($this->activity->details($id), true);
            foreach ($data['groupon'] as &$v) {
                $v['list'] = db('goods_groupon_log')->where('groupon_id', $v['id'])->field('id as groupon_id,user_avatar,is_refund,user_id')->order('createtime')->select();
                $v['userid'] = [];
                foreach ($v['list'] as $kk => &$vv) {
                    array_push($v['userid'], $vv['user_id']);
                    if ($vv['user_avatar']) {
                        $vv['user_avatar'] = cdnurl($vv['user_avatar'], true);
                    } else {
                        $vv['user_avatar'] = cdnurl('/assets/img/avatar.png', true);
                    }
                }
                $v['surplus_num'] = $v['num'] - $v['current_num'];
                if ($v['status'] == 'finish') {
                    $v['join_text'] = '已经完成';
                } elseif ($v['status'] == 'invalid') {
                    $v['join_text'] = '已过期';
                } elseif ($v['status'] == 'ing') {
                    if (in_array($this->auth->id, $v['userid'])) {
                        $v['join_text'] = '已参与';
                    } else {
                        $v['join_text'] = '未参与';
                    }
                } elseif ($v['status'] == 'finish-fictitious') {
                    $v['join_text'] = '虚拟成团';
                }
                unset($v['userid']);
            }
            $this->success('ok', $data);
        }
    }

    /**
     * 场次列表
     */
    public function sessionsvalue()
    {
        $param = $this->request->param();
        if (!$param['time'] || !$param['reservation_id']) {
            $this->error('参数不全');
        }
        $data = db('ball_date')->where(['time' => $param['time'], 'reservation_id' => $param['reservation_id']])->field('*')->find();
        $data['big_content'] = json_decode($data['big_content'], true);
        if ($data['big_content']) {
            foreach ($data['big_content'] as &$vv) {
                foreach ($vv as $k => &$vs) {
                    if (stripos($vs[0], ':')) {
                        $vs['time'] = $vs[0] . '-' . $vs[1];
                        unset($vs[0]);
                        unset($vs[1]);
                    }
                }
            }
            $name = db('reservation')->where('id', $param['reservation_id'])->value('field_name');
            unset($data['small_content']);
            $data['list'] = [];
            $optional = [];
            $notoptional = [];
            $times = [];
            foreach ($data['big_content']['big_money'] as $key => $value) {
                foreach ($value as $keys => $values) {
                    $data['list'][$key][$keys]['price'] = $values;
                    $num = $keys + 1;
                    $data['list'][$key][$keys]['field_name'] = $name . $num;
                    if ($values) {
                        $data['list'][$key][$keys]['status'] = '20';//可预定
                        $order_id = db('litestore_order_goods')->where('date', $data['list'][$key][$keys]['field_name'])->where(['time' => $data['time'], 'time_slot' => $data['big_content']['big_time'][$key]['time']])->value('order_id');
                        $status = db('litestore_order')->where('id', $order_id)->value('status');
                        if ($status !== '10' && $status !== null) {
                            $ids = db('litestore_order_goods')->where('date', 'in', $data['list'][$key][$keys]['field_name'])->where(['time' => $param['time'], 'time_slot' => $data['big_content']['big_time'][$key]['time']])->value('id');
                            if ($ids) {
                                array_push($times, $ids);
                                $data['list'][$key][$keys]['status'] = '30';//可预定
                            }
                        }
                        array_push($optional, $data['list'][$key][$keys]['status']);
                    } else {
                        $data['list'][$key][$keys]['status'] = '10';//不可选
                        array_push($notoptional, $data['list'][$key][$keys]['status']);
                    }
                }
            }

            foreach ($data['big_content']['big_time'] as $k => &$v) {
                $v['big_money'] = $data['list'][$k];
                if (strtotime($param['time'] . ' ' . explode('-', $v['time'])[0]) <= strtotime(date('Y-m-d H:i', time()))) {
                    foreach ($v['big_money'] as &$vv) {
                        $vv['status'] = '10';
                        $vv['price'] = '';
                    }
                }
            }
            if ($times) {
                $data['occupy'] = count($times);//已预约
            } else {
                $data['occupy'] = 0;
            }
            $data['optional'] = count($optional) - $data['occupy'];//可选
            $data['notoptional'] = count($notoptional);//不可选
            unset($data['list']);
            unset($data['big_content']['big_money']);
        }
        $this->success('ok', $data);
    }

    /**
     * 场次列表
     */
    public function sessionsvalues()
    {
        $param = $this->request->param();
        if (!isset($param['reservation_id']) || empty($param['reservation_id'])) {
            $this->error('请选择场馆');
        }
        for ($i = 0; $i < 7; $i++) {
            $dateArray[$i] = date('Y-m-d', strtotime(date('Y-m-d') . '+' . $i . 'day'));
        };
        $time = get_date($dateArray);//调用函数
        $name = db('reservation')->where('id', $param['reservation_id'])->value('field_name');
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
                                $ids = db('litestore_order_goods')->where('date', 'in', $v['datas']['list'][$key][$keys]['field_name'])->where(['time' => $v['datas']['time'], 'time_slot' => $v['datas']['big_content']['big_time'][$key]['time']])->value('id');
                                if ($ids) {
                                    array_push($v['times'], $ids);
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
            }
            unset($v['optional']);
        }

        $this->success('ok', $time);
    }

    /**
     * 时间
     */
    public function timelist()
    {
        for ($i = 0; $i < 7; $i++) {
            $dateArray[$i] = date('Y-m-d', strtotime(date('Y-m-d') . '+' . $i . 'day'));
        };
        $date = get_date($dateArray);//调用函数
        $this->success('ok', $date);
    }
}
