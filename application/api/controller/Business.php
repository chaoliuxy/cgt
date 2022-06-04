<?php


namespace app\api\controller;


use app\common\controller\Api;
use app\common\model\News as cgtnews;
use app\common\model\Business as businessmodel;


/**
 * 附近商家接口
 */
class Business extends Api

{

    protected $noNeedLogin = ['venue', 'sporttypelist', 'venuelist'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {

        parent::_initialize();
        $this->news = new cgtnews();
        $this->businessmodel = new businessmodel();
    }

    /**
     * 附近商家列表
     */
    public function businesslist()
    {
        $param = $this->request->param();
        if (!isset($param['type'])) {
            $param['type'] = '10';
        }
        if (!in_array($param['type'], ['10', '20', '30'])) {
            $this->error('商家类型错误');
        }
        if (!$param['showpage'] || !$param['page'] || !$param['lng'] || !$param['lat']) {
            $this->error('参数不全');
        }
        $list = json_decode($this->businessmodel->list($this->auth->venue_id, $param['showpage'], $param['page'], $param['lng'], $param['lat'], '', $param['type']), true);
        $this->success('ok', $list);
    }


    /**
     * 附近商家列表
     */
    public function tjbusinesslist()
    {
        $param = $this->request->param();
        if (!$param['showpage'] || !$param['page'] || !$param['lng'] || !$param['lat']) {
            $this->error('参数不全');
        }
        $list = json_decode($this->businessmodel->list($this->auth->venue_id, $param['showpage'], $param['page'], $param['lng'], $param['lat'], 10), true);
        $this->success('ok', $list);
    }

    /**
     * 商家详情
     */
    public function details()
    {
        $param = $this->request->param();
        if (!$param['business_id'] || !$param['lng'] || !$param['lat']) {
            $this->error('参数不全');
        }
        $data = json_decode($this->businessmodel->details($param['business_id'], $param['lng'], $param['lat']), true);
        $this->success('ok', $data);
    }

    /**
     * 根据商品类型查询商品列表
     * @param $litestorecategory_id 商品ID
     */
    public function goodslist()
    {
        $litestorecategory_id = $this->request->request('litestorecategory_id');
        $showpage = $this->request->request('showpage');
        $page = $this->request->request('page');
        if (!$litestorecategory_id || !$showpage || !$page) {
            $this->error('参数不全');
        }
        $data = json_decode($this->businessmodel->goodslist($litestorecategory_id, $showpage, $page), true);
        $this->success('ok', $data);
    }

    /**
     * 商品详情
     */
    public function goodsdetails()
    {
        $goods_id = $this->request->request('goods_id');
        if (!$goods_id) {
            $this->error('参数不全');
        }
        $data = json_decode($this->businessmodel->goodsdetails($goods_id), true);
        $this->success('ok', $data);
    }

}

