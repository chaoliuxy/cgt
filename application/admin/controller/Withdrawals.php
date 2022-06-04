<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\model\Venuelog;
use think\Db;

/**
 * 场馆申请提现
 *
 * @icon fa fa-circle-o
 */
class Withdrawals extends Backend
{

    /**
     * Withdrawals模型对象
     * @var \app\admin\model\Withdrawals
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Withdrawals;
		$this->venuelog = new Venuelog();
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

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            if (session('venue_id')) {
                $list = $this->model
                        ->with(['venue'])
                        ->where($where)
                        ->where('venue_id',session('venue_id'))
                        ->order($sort, $order)
                        ->paginate($limit);
            }else{
                $list = $this->model
                ->with(['venue'])
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            }
            foreach ($list as $row) {

                $row->getRelation('venue')->visible(['name']);
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

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
								if (!isset($params['venue_id'])) {
									if (session('venue_id')) {
										$params['venue_id'] = session('venue_id');
									}else{
										$params['venue_id'] = 0;
									}
								}
								$money = db('venue')->where('id',session('venue_id'))->value('money');
								if ($money<$params['money']) {
									$this->error('余额不足');
								}
								if ((int)$params['money']<(int)config('site.min')) {
									$this->error('单次提现不得小于'.config('site.min').'元');
								}
								if (config('site.rate')) {
									$rate = config('site.rate')/100;
								}else{
                  $rate = 0;
								}
								$params['servicecharge'] = $params['money'] * $rate;
								$params['actualamount'] = $params['money'] - $params['servicecharge'];
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
										$this->venuelog->addvenuemongylog($params['venue_id'],$params['money'],$money,'场馆提现','70');
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
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
				$venue_id = session('venue_id');
				$this->view->assign('venue_id',$venue_id);
        return $this->view->fetch();
    }
}
