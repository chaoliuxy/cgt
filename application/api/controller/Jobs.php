<?php


namespace app\api\controller;


use app\common\controller\Api;

/**
 * 队列
 */
class Jobs extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
    }

    public function test()
    {
        $data = db('users')->select();
        foreach ($data as $v) {
            $isPushed = \addons\faqueue\library\QueueApi::push("app\job\Groupbuy@fire", $v, 'Groupbuy');
        }
    }

    /**
     * 拼团支付成功后执行此队列
     */
    public function pay($order_id = '')
    {
        $orderdata = db('litestore_order')->where('id', $order_id)->select();
        foreach ($orderdata as $v) {
            \addons\faqueue\library\QueueApi::push("app\job\Groupbuy@fire", $v, 'Groupbuy');
        }
    }

}

