<?php

namespace app\job;

use app\common\model\Groupon;
use think\Controller;
use think\queue\Job;

/**
 * 队列
 */
class Groupbuy extends Controller
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
    public function fire(Job $job, $data)
    {
        //业务处理代码，具体不贴出来了
        $isJobDone = $this->create($data);
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
     * 添加开团、拼团记录、减库存、检查团状态【未在规定时间成团则解散并退款、成团成功则根据拼团类型进行奖励发货等】
     */
    public function create($data)
    {
        $groupon = new Groupon();
        //减库存、增加销量
        $user = db('user')->where('id', $data['user_id'])->find();
        $groupon->joinGroupon($data, $user);//拼团
        return true;
    }


}

