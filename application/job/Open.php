<?php

namespace app\job;

use think\Controller;
use think\queue\Job;
use fast\Http;

/**
 * 队列
 */
class Open extends Controller
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
        $isJobDone = $this->operation($data['litestore_order_goods_id'], $data['type']);
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

    public function operation($litestore_order_goods_id = '', $type = '')
    {
        // $litestore_order_goods_id = '918';
        // $type ='10';#操作类型：20：开启；10：关闭
        $orders = db('litestore_order_goods')->where('id', $litestore_order_goods_id)->field('order_id,reservation_id,date,time,time_slot')->find();
        $ids = db('order')->where('order_ids', $orders['order_id'])->value('id');
        $data = db('lamplist')->where(['reservation_id' => $orders['reservation_id'], 'field_name' => $orders['date']])->find();
        if ($ids && $data['id']) {
            $url = 'http://112.74.105.251:8001/light';
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
            $result = Http::http_post_json($url, json_encode($datas, true));
            if (json_decode($result[1], true)['result'] == 'OK') {
                db('lamplist')->where('id', $data['id'])->update(['status' => $type]);
                return true;
            }
        }
        return true;
    }
}
