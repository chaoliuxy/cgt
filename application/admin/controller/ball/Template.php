<?php

namespace app\admin\controller\ball;

use app\common\controller\Backend;
use think\Db;
use think\Log;

/**
 *
 *
 * @icon fa fa-circle-o
 */
class Template extends Backend
{
    
    /**
     * Template模型对象
     * @var \app\admin\model\ball\Template
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\ball\Template;
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
        $this->relationSearch = false;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            if (session('venue_id')) {
              $total = $this->model
              ->where($where)
              ->where('venue_id',session('venue_id'))
              ->order($sort, $order)
              ->count();
             $list = $this->model
              ->where($where)
              ->where('venue_id',session('venue_id'))
              ->order($sort, $order)
              ->limit($offset, $limit)
              ->select();
            }else{
              $total = $this->model
              ->where($where)
              ->order($sort, $order)
              ->count();
             $list = $this->model
              ->where($where)
              ->order($sort, $order)
              ->limit($offset, $limit)
              ->select();
            }
            foreach ($list as $row) {
                $row->visible(['id','title','descript','smallswitch','status','weigh','createtime','updatetime']);
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
									if (session('venue_id')) {
										$params['venue_id'] = session('venue_id');
									}else{
										$params['venue_id'] = 0;
									}
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
                if (session('venue_id')) {
                  $params['venue_id'] = session('venue_id');
                }else{
                  $params['venue_id'] = 0;
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
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    //根据模板自动生成7天的数据,开始日期以数据库的最后一天为始点
    public function create_date($ids = null)
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
        if ($row['status'] == '0') {
            $this->error("当前模板为关闭状态，不可执行当前操作");
        }
        //查询ball_date数据库的最新日期是几号
        $last_row = db("ball_date")->whereTime('time', '>', date("Y-m-d"))->order("time desc")->value('time');
        $row = $row->toArray();
        unset($row['id'],$row['title'],$row['descript'],$row['status_text']);
        $array = array();
        //查询当天的日期是否存在，如果没有则添加当天的
        if (!$last_row) {
            $check_curr_today = db("ball_date")->whereTime('time', 'today')->find();
            if (!$check_curr_today) {
                $row['time'] = date("Y-m-d", strtotime("now"));
                $row['createtime'] = time();
                $row['updatetime'] = time();
                $array[] = $row;
            }
        }
        for ($i=1;$i<=7;$i++) {
            $row['time'] = date("Y-m-d", strtotime($last_row?$last_row:"now")+(3600*24*$i));
            $row['createtime'] = time();
            $row['updatetime'] = time();
						$row['reservation_id'] = $ids;
            $array[] = $row;
        }
        $result = db("ball_date")->insertAll($array);
        if ($result) {
            $this->success("生成成功");
        } else {
            $this->error("生成失败");
        }
    }
}
