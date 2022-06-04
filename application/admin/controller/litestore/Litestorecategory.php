<?php

namespace app\admin\controller\litestore;

use app\common\controller\Backend;
use fast\Tree;
use think\Log;
use think\Db;

/**
 * 商品分类
 *
 * @icon fa fa-circle-o
 */
class Litestorecategory extends Backend
{

    /**
     * Litestorecategory模型对象
     * @var \app\admin\model\Litestorecategory
     */
    protected $model = null;
    protected $categorylist = [];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\litestore\Litestorecategory;

        $tree = Tree::instance();
        $tree->init(collection($this->model->order('weigh desc,id desc')->select())->toArray(), 'pid');
        $this->categorylist = $tree->getTreeList($tree->getTreeArray(0), 'name');
        $categorydata = [0 => ['type' => 'all', 'name' => __('None')]];
        foreach ($this->categorylist as $k => $v)
        {
            $categorydata[$v['id']] = $v;
        }
        $this->view->assign("parentList", $categorydata);
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    public function selectpage()
    {
        return parent::selectpage();
    }


    /**
     * 查看
     */
    public function index()
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
             ->with(['venue','business'])
             ->where('litestorecategory.venue_id',session('venue_id'))
             ->where($where);
            }else{
             $list = $this->model
              ->where($where)
              ->with(['venue','business']);
            }
            $list = $list
                    ->order($sort, $order)
                    ->paginate($limit);
            foreach ($list as $row) {
                $row->getRelation('venue')->visible(['name']);
                $row->getRelation('business')->visible(['name']);
            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }
}
