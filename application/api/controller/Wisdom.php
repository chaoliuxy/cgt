<?php


namespace app\api\controller;

use app\common\controller\Api;

/**
 * 智慧场馆接口
 */
class Wisdom extends Api

{

    protected $noNeedLogin = ['provincelist', 'citylist', 'regionlist'];
    protected $noNeedRight = ['*'];

    /**
     * 故障申报
     */

    public function addwisdom()
    {
        $param = $this->request->param();
        if (!$param['address'] || !$param['lng'] || !$param['lat'] || !$param['images'] || !$param['remarks']) {
            $this->error('参数不全');
        }
        $param['user_id'] = $this->auth->id;
        $param['venue_id'] = $this->auth->venue_id;
        $param['updatetime'] = time();
        $param['createtime'] = time();
        $param['status'] = '20';
        unset($param['token']);
        $add = db('faultdeclaration')->insert($param);
        $this->success('ok', $add);
    }

    /**
     * 求助类型
     */
    public function helptypelist()
    {
        $list = db('helptype')->where('venue_id', $this->auth->venue_id)->field('id,name')->select();
        $this->success('ok', $list);
    }

    /**
     * 求助电话
     */
    public function helpphone()
    {
        $data = db('helpphone')->where('venue_id', $this->auth->venue_id)->value('mobile');
        $this->success('ok', $data);
    }

    /**
     * 紧急求助
     */
    public function addhelpvalue()
    {
        $param = $this->request->param();
        if (!$param['helptype_id'] || !$param['mobile'] || !$param['reason']) {
            $this->error('参数不全');
        }
        $param['phone'] = $param['mobile'];
        unset($param['mobile']);
        $param['createtime'] = time();
        $param['updatetime'] = time();
        $param['venue_id'] = $this->auth->venue_id;
        $param['user_id'] = $this->auth->id;
        unset($param['token']);
        $add = db('seekhelp')->insert($param);
        $this->success('ok', $add);
    }

}

