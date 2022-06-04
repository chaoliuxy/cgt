<?php

namespace app\admin\controller\ball;

use app\common\controller\Backend;
use think\Db;

/**
 *
 *
 * @icon fa fa-circle-o
 */
class Date extends Backend
{

    /**
     * Date模型对象
     * @var \app\admin\model\ball\Date
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\ball\Date;
        $this->view->assign("statusList", $this->model->getStatusList());
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
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
                $total = $this->model
              ->with(['reservation'])
              ->where($where)
              ->where('date.venue_id', session('venue_id'))
              ->order($sort, $order)
            //   ->group('date.reservation_id')
              ->count();
                $list = $this->model
              ->with(['reservation'])
              ->where($where)
              ->where('date.venue_id', session('venue_id'))
              ->order($sort, $order)
              ->limit($offset, $limit)
            //   ->group('date.reservation_id')
              ->select();
            } else {
                $total = $this->model
              ->with(['reservation'])
              ->where($where)
              ->order($sort, $order)
            //   ->group('date.reservation_id')
              ->count();
                $list = $this->model
              ->with(['reservation'])
              ->where($where)
              ->order($sort, $order)
              ->limit($offset, $limit)
            //   ->group('date.reservation_id')
              ->select();
            }
            foreach ($list as $row) {
                $row->getRelation('reservation')->visible(['name']);
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }


    // /**
    //  * 查看
    //  */
    // public function index()
    // {
    //     //当前是否为关联查询
    //     $this->relationSearch = false;
    //     //设置过滤方法
    //     $this->request->filter(['strip_tags', 'trim']);
    //     if ($this->request->isAjax()) {
    //         //如果发送的来源是Selectpage，则转发到Selectpage
    //         if ($this->request->request('keyField')) {
    //             return $this->selectpage();
    //         }
    //         list($where, $sort, $order, $offset, $limit) = $this->buildparams();

    //         $list = $this->model
    //                 // ->with(['reservation'])
    //                 ->where($where)
    //                 ->order($sort, $order)
    //                 ->paginate($limit);

    //         foreach ($list as $row) {

    //             // $row->getRelation('reservation')->visible(['name']);
    //         }

    //         $result = array("total" => $list->total(), "rows" => $list->items());

    //         return json($result);
    //     }
    //     return $this->view->fetch();
    // }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }

                $params['big_content'] = json_encode($params['big_content']);

                if (isset($params['small_content'])) {
                    $params['small_content'] = json_encode($params['small_content']);
                }

                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validate($validate);
                    }
                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result) {
                    $this->success();
                } else {
                    $this->error();
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $venue_id = session('venue_id');
        $this->view->assign('venue_id', $venue_id);
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $params['big_content'] = json_encode($params['big_content']);
                if (isset($params['small_content'])) {
                    $params['small_content'] = json_encode($params['small_content']);
                }

                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validate($validate);
                    }
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result) {
                    $this->success();
                } else {
                    $this->error();
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $row = $row->toArray();

        $row['big_content'] = json_decode($row['big_content'], 1);
        $row['small_content'] = json_decode($row['small_content'], 1);
        $venue_id = session('venue_id');
        $this->view->assign('venue_id', $venue_id);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
}
