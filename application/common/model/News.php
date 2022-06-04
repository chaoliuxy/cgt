<?php

namespace app\common\model;

use think\Model;

/**
 * 新闻资讯
 */
class News extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [

    ];

    /**
     * 新闻列表
     */
    public function list($venue_id = '', $showpage = '', $page = '')
    {
        $list['list'] = db('news')->where('venue_id', $venue_id)->where('status', '10')->field('id,image,name,createtime,label_id')->limit($showpage)->page($page)->order('weigh DESC')->select();
        foreach ($list['list'] as &$v) {
            $v['image'] = cdnurl($v['image'], true);
            $v['createtime'] = date('Y/m/d', $v['createtime']);
            $v['label_name'] = db('label')->where('id', $v['label_id'])->value('label_name');
            $v['likes_num'] = db('likes')->where('news_id', $v['id'])->where('type', '10')->count();
        }
        $list['count'] = db('news')->where('venue_id', $venue_id)->count();
        $list['total_page'] = ceil($list['count'] / $showpage);
        return json_encode($list, true);
    }

    /**
     * 新闻资讯详情
     */
    public function details($news_id = '', $user_id = '', $showpage = '', $page = '')
    {
        $data['list'] = db('news')->where('id', $news_id)->find();
        $data['list']['image'] = cdnurl($data['list']['image'], true);
        $data['list']['content'] = replacePicUrl($data['list']['content'], config('fastadmin.url'));
        $data['list']['createtime'] = date('m-d H:i:s', $data['list']['createtime']);
        $data['list']['label_name'] = db('label')->where('id', $data['list']['label_id'])->value('label_name');
        $data['list']['likes_num'] = db('likes')->where(['news_id' => $data['list']['id'], 'type' => '10'])->count();
        $data['list']['comment_num'] = db('comment')->where('news_id', $data['list']['id'])->count();
        $data['list']['is_likes'] = db('likes')->where(['news_id' => $data['list']['id'], 'user_id' => $user_id])->value('type') ? db('likes')->where(['news_id' => $data['list']['id'], 'user_id' => $user_id])->value('type') : 20;
        $data['comment_list'] = db('comment')
            ->alias('c')
            ->join('user u', 'u.id = c.user_id', 'LEFT')
            ->where('c.news_id', $data['list']['id'])
            ->where('c.status', '20')
            ->field('c.id,c.content,u.avatar,u.nickname,FROM_UNIXTIME(c.createtime, "%Y-%m-%d %H:%i") as createtime')
            ->group('c.id')
            ->order('createtime DESC')
            ->limit($showpage)
            ->page($page)
            ->select();

        $data['comment_list_count'] = db('comment')
            ->alias('c')
            ->join('user u', 'u.id = c.user_id', 'LEFT')
            ->where('c.news_id', $data['list']['id'])
            ->where('c.status', '20')
            ->count();

        $data['comment_list_page'] = ceil($data['comment_list_count'] / $showpage);

        return json_encode($data, true);

    }

}

