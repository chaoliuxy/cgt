<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Course as courseModel;

/**
 * 课程接口
 */
class Course extends Api
{
    protected $noNeedLogin = ['provincelist', 'citylist', 'regionlist'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->coursemodel = new courseModel();
    }

    /**
     * 课程列表
     */
    public function courselist()
    {
        $param = $this->request->param();
        if (!$param['page'] || !$param['showpage'] || !$param['lat'] || !$param['lng']) {
            $this->error('参数不全');
        }
        if (isset($param['key'])) {
            $ids = db('searchlog')->where(['title' => $param['key'], 'user_id' => $this->auth->id, 'venue_id' => $this->auth->venue_id, 'type' => '10'])->value('id');
            if (!$ids) {
                db('searchlog')->insert(['title' => $param['key'], 'user_id' => $this->auth->id, 'venue_id' => $this->auth->venue_id, 'type' => '10', 'createtime' => time()]);
            }
            $list = json_decode($this->coursemodel->list($this->auth->venue_id, $param['page'], $param['showpage'], $param['lat'], $param['lng'], '', $param['key']), true);
        } else {
            $list = json_decode($this->coursemodel->list($this->auth->venue_id, $param['page'], $param['showpage'], $param['lat'], $param['lng'], '', ''), true);
        }
        $this->success('ok', $list);
    }

    /**
     * 课程详情
     */
    public function couresdetails()
    {
        $goods_id = $this->request->request('goods_id');
        if (!$goods_id) {
            $this->error('参数不全');
        }
        $data = json_decode($this->coursemodel->details($goods_id), true);
        $data['detail']['content'] = replacePicUrl($data['detail']['content'], config('fastadmin.url'));
        $data['detail']['course_content'] = replacePicUrl($data['detail']['course_content'], config('fastadmin.url'));
        $this->success('ok', $data);
    }

    /**
     * 推荐课程
     */
    public function tjcourselist()
    {
        $param = $this->request->param();
        if (!$param['page'] || !$param['showpage'] || !$param['lat'] || !$param['lng']) {
            $this->error('参数不全');
        }
        $list = json_decode($this->coursemodel->list($this->auth->venue_id, $param['page'], $param['showpage'], $param['lat'], $param['lng'], '10'), true);
        $this->success('ok', $list);
    }

    /**
     * 删除搜索历史
     */
    public function delsearch()
    {
        $searchlog_id = $this->request->request('searchlog_id');
        if (!$searchlog_id) {
            $this->error('请选择你要删除的搜索记录');
        }
        $del = db('searchlog')->where('id', $searchlog_id)->delete();
        $this->success('ok', $del);
    }

    /**
     * 搜索历史数据
     */
    public function searchloglist()
    {
        $list['search'] = db('searchlog')->where(['user_id' => $this->auth->id, 'venue_id' => $this->auth->venue_id])->field('id,title')->order('id DESC')->select();
        $list['hot_search'] = db('search')->where(['venue_id' => $this->auth->venue_id])->field('id,title')->order('id DESC')->select();
        $this->success('ok', $list);
    }

}

