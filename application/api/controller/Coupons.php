<?php


namespace app\api\controller;


use app\common\controller\Api;
use app\common\model\News as cgtnews;


/**
 * 优惠券接口
 */
class Coupons extends Api
{
    protected $noNeedLogin = ['venue', 'sporttypelist', 'venuelist'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->news = new cgtnews();

    }

    /**
     * 优惠券-领券中心
     */
    public function couponscenter()
    {
        $param = $this->request->param();
        if (!$param['showpage'] || !$param['page']) {
            $this->error('参数不全');
        }
        if (!in_array($param['type'], [10, 20])) {
            $this->error('类型参数错误');
        }
        $venue_ids = '0,' . $this->auth->venue_id;
        $venueids = explode(',', $venue_ids);
        if ($param['type'] == '10') {
            # 领券中心
            $list['list'] = db('coupons')
                ->where('venue_id', 'in', $venueids)
                ->field('id,coupons_name,scene_type,coupons_type,coupons_image,coupons_money,coupons_content,startime,endtime,use_condition')
                ->limit($param['showpage'])
                ->page($param['page'])
                ->order('weigh DESC')
                ->select();
            foreach ($list['list'] as &$v) {
                $v['coupons_image'] = cdnurl($v['coupons_image'], true);
                //适用场景:10=通用,20=订场,30=订票,40=购物
                if ($v['scene_type'] == '10') {
                    $v['scene_type_text'] = '通用';
                } elseif ($v['scene_type'] == '20') {
                    $v['scene_type_text'] = '订场';
                } elseif ($v['scene_type'] == '30') {
                    $v['scene_type_text'] = '订票';
                } elseif ($v['scene_type'] == '40') {
                    $v['scene_type_text'] = '购物';
                }
                //优惠券类型:10=代金券,20=折扣券,30=满减券
                if ($v['coupons_type'] == '10') {
                    $v['coupons_type_text'] = '代金券';
                } elseif ($v['coupons_type'] == '20') {
                    $v['coupons_type_text'] = '折扣券';
                } elseif ($v['coupons_type'] == '30') {
                    $v['coupons_type_text'] = '满减券';
                }
                $v['coupons_content'] = strip_tags($v['coupons_content']);
                $v['startime'] = str_replace('-', '.', $v['startime']);
                $v['endtime'] = str_replace('-', '.', $v['endtime']);
                $v['time'] = $v['startime'] . '-' . $v['endtime'];
                $v['is_receive'] = db('receive')->where(['user_id' => $this->auth->id, 'coupons_id' => $v['id']])->value('id');
                if ($v['is_receive']) {
                    $v['is_receive'] = '已领取';
                } else {
                    $v['is_receive'] = '未领取';
                }
            }
            $list['count'] = db('coupons')->where('venue_id', 'in', $venueids)->count();
        } else {
            # 已领取
            db('receive')->where('status', '10')->where('endtime', '<=', time())->update(['status' => '30']);
            $list['list'] = db('receive')
                ->alias('r')
                ->join('coupons c', 'r.coupons_id = c.id')
                ->where('c.venue_id', 'in', $venueids)
                ->where('r.user_id', $this->auth->id)
                ->field('c.id,c.coupons_name,c.scene_type,c.coupons_type,c.coupons_image,c.coupons_money,c.coupons_content,c.startime,c.endtime,c.use_condition,r.id as receive_id,r.status')
                ->limit($param['showpage'])
                ->page($param['page'])
                ->order('weigh c.DESC')
                ->group('r.id')
                ->select();
            $list['count'] = db('receive')
                ->alias('r')
                ->join('coupons c', 'r.coupons_id = c.id')
                ->where('c.venue_id', 'in', $venueids)
                ->where('r.user_id', $this->auth->id)
                ->group('r.id')
                ->count();
            foreach ($list['list'] as &$v) {
                if ($v['status'] == '10') {
                    $v['status_text'] = '待使用';
                } elseif ($v['status'] == '20') {
                    $v['status_text'] = '已使用';
                } else {
                    $v['status_text'] = '已失效';
                }
                $v['coupons_image'] = cdnurl($v['coupons_image'], true);
                //适用场景:10=通用,20=订场,30=订票,40=购物
                if ($v['scene_type'] == '10') {
                    $v['scene_type_text'] = '通用';
                } elseif ($v['scene_type'] == '20') {
                    $v['scene_type_text'] = '订场';
                } elseif ($v['scene_type'] == '30') {
                    $v['scene_type_text'] = '订票';
                } elseif ($v['scene_type'] == '40') {
                    $v['scene_type_text'] = '购物';
                }
                //优惠券类型:10=代金券,20=折扣券,30=满减券
                if ($v['coupons_type'] == '10') {
                    $v['coupons_type_text'] = '代金券';
                } elseif ($v['coupons_type'] == '20') {
                    $v['coupons_type_text'] = '折扣券';
                } elseif ($v['coupons_type'] == '30') {
                    $v['coupons_type_text'] = '满减券';
                }
                $v['coupons_content'] = strip_tags($v['coupons_content']);
                $v['startime'] = str_replace('-', '.', $v['startime']);
                $v['endtime'] = str_replace('-', '.', $v['endtime']);
                $v['time'] = $v['startime'] . '-' . $v['endtime'];
                $v['is_receive'] = '已领取';
            }
        }
        $list['total_page'] = ceil($list['count'] / $param['showpage']);
        $this->success('ok', $list);
    }

    /**
     * 领取优惠券
     */
    public function receive_coupons()
    {
        $coupons_id = $this->request->request('coupons_id');
        if (!$coupons_id) {
            $this->error('请选择要领取的优惠券');
        }
        $coupons = db('coupons')->where('id', $coupons_id)->field('id,endtime,use_condition,reservation_ids,scene_type,coupons_type')->find();
        if (!$coupons['id']) {
            $this->error('该优惠券不存在');
        }
        $endtime = strtotime(str_replace(".", "-", $coupons['endtime']));
        $ids = db('receive')->where(['user_id' => $this->auth->id, 'coupons_id' => $coupons_id])->value('id');
        if ($ids) {
            $this->error('该优惠券你已领取');
        }
        $param = [
            'coupons_id' => $coupons_id,
            'user_id' => $this->auth->id,
            'status' => '10',
            'use_condition' => '10',
            'reservation_ids' => $coupons['reservation_ids'] ? $coupons['reservation_ids'] : 0,
            'scene_type' => $coupons['scene_type'],
            'coupons_type' => $coupons['coupons_type'],
            'endtime' => $endtime,
            'updatetime' => time(),
            'createtime' => time(),
        ];
        $add = db('receive')->insert($param);
        $this->success('领取成功', $add);
    }

}

