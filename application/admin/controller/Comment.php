<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 评论管理
 *
 * @icon fa fa-comment
 */
class Comment extends Backend
{

    /**
     * Comment模型对象
     * @var \app\admin\model\Comment
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Comment;
        $this->view->assign("statusList", $this->model->getStatusList());
    }

    public function import()
    {
        parent::import();
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 查看
     */
    public function index($ids='')
    {
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            if (session('venue_id')) {
             $list = $this->model
             ->with(['user'])
             ->where($where)
             ->where('comment.venue_id',session('venue_id'));
            }else{
             $list = $this->model
              ->with(['user'])
              ->where($where);
            }
            foreach ($list as $row) {
                $row->getRelation('user')->visible(['nickname']);
            }
            $list = $list
			    ->where('news_id',$ids)
                ->order($sort, $order)
                ->paginate($limit);
            foreach ($list as &$v) {
              $v['num'] = db('signup')->where(['activity_id'=>$v['id'],'status'=>'20'])->count();
              $v['news_id'] = db('news')->where('id',$v['news_id'])->value('name');
            }
            unset($v);
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }
}
