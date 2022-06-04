<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\News as cgtnews;


/**
 * 新闻资讯接口
 */
class News extends Api
{
    protected $noNeedLogin = ['venue', 'sporttypelist', 'venuelist'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->news = new cgtnews();

    }

    /**
     * 新闻资讯
     */
    public function newslist()
    {
        $page = input('post.page');
        $showpage = input('post.showpage', '10', 'intval');
        if (!$page || !$showpage) {
            $this->error('参数不全');
        }
        if (!$this->auth->venue_id) {
            $this->error('请选择所在体育馆');
        }
        $list = json_decode($this->news->list($this->auth->venue_id, $showpage, $page), true);
        $this->success('ok', $list);
    }

    /**
     * 新闻资讯详情
     */
    public function newsdetails()
    {
        $news_id = $this->request->request('news_id');
        if (!$news_id) {
            $this->error('请选择赛事活动');
        }
        $page = input('post.page');
        $showpage = input('post.showpage', '10', 'intval');
        if (!$page || !$showpage) {
            $this->error('参数不全');
        }
        $data = json_decode($this->news->details($news_id, $this->auth->id, $showpage, $page), true);
        $this->success('ok', $data);
    }

    /**
     * 点赞
     */
    public function likes()
    {
        $param = $this->request->param();
        if (!$param['news_id']) {
            $this->error('请选择新闻资讯');
        }
        $type = db('likes')->where(['news_id' => $param['news_id'], 'user_id' => $this->auth->id])->value('type');
        if ($type == '10') {
            $update = db('likes')->where(['news_id' => $param['news_id'], 'user_id' => $this->auth->id])->update(['type' => '20', 'updatetime' => time()]);
            $this->success('取消点赞成功', $update);
        } elseif ($type == '20') {
            $update = db('likes')->where(['news_id' => $param['news_id'], 'user_id' => $this->auth->id])->update(['type' => '10', 'updatetime' => time()]);
            $this->success('点赞成功', $update);
        } else {
            $update = db('likes')->insert(['news_id' => $param['news_id'], 'user_id' => $this->auth->id, 'type' => '10', 'venue_id' => $this->auth->venue_id, 'createtime' => time(), 'updatetime' => time()]);
            $this->success('点赞成功', $update);
        }
    }

    /**
     * 添加评论
     */
    public function addcomment()
    {
        $param = $this->request->param();
        if (!$param['news_id'] || !$param['content']) {
            $this->error('参数不全');
        }
        $param['venue_id'] = $this->auth->venue_id;
        $param['user_id'] = $this->auth->id;
        $param['status'] = '20';
        $param['createtime'] = time();
        unset($param['token']);
        $add = db('comment')->insert($param);
        $this->success('ok', $add);
    }
}
