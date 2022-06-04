<?php

namespace app\job;

use app\common\model\Groupon;
use app\common\model\Venuelog;
use think\Controller;
use think\queue\Job;

/**
 * 队列
 */
class Goodsgroupon extends Controller
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * fire方法是消息队列默认调用的方法
     * @param Job $job 当前的任务对象
     * @param array|mixed $data 发布任务时自定义的数据
     */
    public function expire(Job $job, $data)
    {
        //业务处理代码，具体不贴出来了
        $status = db('goods_groupon')->where('id', $data['groupon_id'])->find();
        if ($status && $status['status'] == 'ing') {
            $isJobDone = $this->invalidRefundGroupon($data['groupon_id']);
        } else {
            $isJobDone = '';
        }
        if ($isJobDone) {
            // 如果任务执行成功， 记得删除任务
            $job->delete();
        } else {
            if ($job->attempts() > 3) {
                //通过这个方法可以检查这个任务已经重试了几次了
                $job->delete();
            }
        }
    }

    /**
     * 团过期退款，或者后台手动解散退款
     */
    protected function invalidRefundGroupon($goodsgroupon_id)
    {
        $groupon = new Groupon();
        // 拼团失败
        db('goods_groupon')->where('id', $goodsgroupon_id)->update(['status' => 'invalid']);
        // 查询参团真人
        $list = db('goods_groupon_log')->alias('l')->join('order o', 'o.id = l.order_id')->where('l.groupon_id', $goodsgroupon_id)->field('l.id,l.user_id,o.order_ids as order_id,o.order_no,o.pay_price,o.order_type')->select();
        $venuelog = new Venuelog();
        foreach ($list as $v) {
            $venue_id = db('user')->where('id', $list['user_id'])->value('venue_id');
            $before = db('venue')->where('id', $venue_id)->value('money');
            if ($v['order_type'] == '20') {
                # 课程
                db('litestore_order')->where('id', $v['order_id'])->update(['status' => '110']);
                $venuelog->addvenuemongylog($venue_id, $v['pay_price'], $before, '课程拼团失败退款', '80');
            } elseif ($v['order_type'] == '40') {
                db('signup')->where('id', $v['order_id'])->update(['status' => '50']);
                $venuelog->addvenuemongylog($venue_id, $v['pay_price'], $before, '活动拼团失败退款', '80');
            }
            db('order')->where('order_ids', $v['order_id'])->update(['groupbuying_status' => '40']);
            db('goods_groupon_log')->where('id', $v['id'])->update(['is_refund' => 1]);
            $groupon->refund($v['order_no'], $v['pay_price'], $v['order_id']);//退款
        }
        return true;
    }
}
