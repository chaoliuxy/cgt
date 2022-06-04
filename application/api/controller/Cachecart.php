<?php


namespace app\api\controller;


use addons\litestore\model\CacheCart as cart;
use app\common\controller\Api;


/**
 * 购物车接口
 */
class Cachecart extends Api

{

    protected $noNeedLogin = ['register'];
    protected $noNeedRight = ['*'];
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 购物车列表
     */
    public function getList()
    {
        $business_id = $this->request->post('business_id');
        if (!$business_id) {
            $this->error('参数不全');
        }
        $this->cart = new cart($this->auth->id, $business_id);
        $this->success('', $this->cart->getLists($this->auth->id, $business_id));
    }

    /**
     * 加入购物车
     */
    public function add()
    {
        $goods_id = $this->request->post('goods_id');
        $goods_num = $this->request->post('goods_num');
        $business_id = $this->request->post('business_id');
        $goods_sku_id = $this->request->post('spec_sku_id') ?? '';
        if (!$goods_id || !$goods_num || !$business_id) {
            $this->error('参数不全');
        }
        $this->cart = new cart($this->auth->id, $business_id);
        if ($goods_sku_id) {
            $goods_spec_id = db('litestore_goods_spec')->where(['goods_id' => $goods_id, 'spec_sku_id' => $goods_sku_id])->value('goods_spec_id');
        } else {
            $goods_spec_id = db('litestore_goods_spec')->where(['goods_id' => $goods_id])->value('goods_spec_id');
        }
        if (!$this->cart->add($goods_id, $goods_num, $goods_sku_id, $goods_spec_id)) {
            $this->error($this->cart->getError() ?: '加入购物车失败');
        }
        $total_num = $this->cart->getTotalNum();
        $this->success('加入购物车成功', ['cart_total_num' => $total_num]);
    }

    /**
     * 减少购物车中某商品数量
     * @param $goods_id
     * @param $goods_sku_id
     */
    public function sub()
    {
        $goods_spec_id = $this->request->post('goods_spec_id');
        $business_id = $this->request->post('business_id');
        if (!$goods_spec_id || !$business_id) {
            $this->error('参数不全');
        }
        $this->cart = new cart($this->auth->id, $business_id);
        $this->cart->sub($goods_spec_id);
        $this->success('ok');
    }

    /**
     * 删除购物车中指定商品
     * @param $goods_id
     * @param $goods_sku_id
     */
    public function delete()
    {
        $param = $this->request->param();
        if (!$param['goods_spec_id'] || !$param['business_id']) {
            $this->error('参数不全');
        }
        $param['goods_spec_id'] = explode(',', $param['goods_spec_id']);
        $cart = new cart($this->auth->id, $param['business_id']);
        foreach ($param['goods_spec_id'] as $k => $v) {
            $cart->delete($v);
        }
        $this->success('ok');
    }

}