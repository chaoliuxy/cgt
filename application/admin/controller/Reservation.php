<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use think\Log;

/**
 * 订场订票管理
 *
 * @icon fa fa-circle-o
 */
class Reservation extends Backend
{

    /**
     * Reservation模型对象
     * @var \app\admin\model\Reservation
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Reservation;
        $this->template = new \app\admin\model\ball\Template;
        $this->view->assign("typeList", $this->model->getTypeList());
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
                        ->with('type')
                        ->where($where)
                        ->where('reservation.venue_id', session('venue_id'));
            } else {
                $list = $this->model
                ->with('type')
                ->where($where);
            }
            $list = $list->order($sort, $order)->paginate($limit);
            foreach ($list as $row) {
                $row->getRelation('type')->visible(['type_name']);
            }
            foreach ($list as &$v) {
                // $v['type_id'] = db('type')->where('id',$v['type_id'])->value('name');
            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    //根据模板自动生成7天的数据,开始日期以数据库的最后一天为始点
    public function create_date($ids = null)
    {
        // $num = db('ball_template')->where('venue_id', session('venue_id'))->value('id');
        $num = db('reservation')->where('id', $ids)->value('balltemplate_id');
        $row = $this->template->get($num);
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
        $last_row = db("ball_date")->where('reservation_id', $ids)->whereTime('time', '>', date("Y-m-d"))->order("time desc")->value('time');
        $row = $row->toArray();
        unset($row['id'],$row['title'],$row['descript'],$row['status_text']);
        $venue_id = db('reservation')->where('id', $ids)->value('venue_id');
        $array = array();
        //查询当天的日期是否存在，如果没有则添加当天的
        if (!$last_row) {
            $check_curr_today = db("ball_date")->whereTime('time', 'today')->find();
            if (!$check_curr_today) {
                $row['time'] = date("Y-m-d", strtotime("now"));
                $row['createtime'] = time();
                $row['updatetime'] = time();
                $row['reservation_id'] = $ids;
                $row['venue_id'] = $venue_id;
                $array[] = $row;
            }
        }
        for ($i=1;$i<=7;$i++) {
            $row['time'] = date("Y-m-d", strtotime($last_row?$last_row:"now")+(3600*24*$i));
            $row['createtime'] = time();
            $row['updatetime'] = time();
            $row['reservation_id'] = $ids;
            $row['venue_id'] = $venue_id;
            $array[] = $row;
        }
        $result = db("ball_date")->insertAll($array);
        if ($result) {
            $this->success("生成成功");
        } else {
            $this->error("生成失败");
        }
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
                    } else {
                        $params['venue_id'] = 0;
                    }
                }
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $deviceid = explode(',', $params['deviceid']);
                $id = db('lamplist')->where('lamp_id', 'in', $deviceid)->value('id');
                if ($id) {
                    $this->error('当前所填设备号已占用');
                }
                $num = $params['field_number']/8;#所需开关数
                if (count($deviceid)<$num) {
                    $this->error('当前开关数不足');
                }
                $data = [];
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $result = $this->model->allowField(true)->save($params);
                    $ids = db('reservation')->where(['name'=>$params['name'],'address'=>$params['address'],'deviceid'=>$params['deviceid']])->value('id');
                    for ($i=1; $i <= $params['field_number']; $i++) {
                        if ($i<=8) {
                            db('lamplist')->insert([
                                    'field_name'=>$params['field_name'].$i,#场地名
                                    'reservation_id'=>$ids,#所属场馆
                                    'lamp_id'=>$deviceid[0],#所属灯控
                                    'number'=>$i, #编号
                                    'field_name'=>$params['field_name'].$i,
                                    'createtime'=>time()]);
                        }
                        if ($i>=9 && $i<=16) {
                            db('lamplist')->insert([
                                    'field_name'=>$params['field_name'].$i,#场地名
                                    'reservation_id'=>$ids,#所属场馆
                                    'lamp_id'=>$deviceid[1],#所属灯控
                                    'number'=>$i, #编号
                                    'field_name'=>$params['field_name'].$i,
                                    'createtime'=>time()]);
                        }
                        if ($i>=17 && $i<=24) {
                            db('lamplist')->insert([
                                    'field_name'=>$params['field_name'].$i,#场地名
                                    'reservation_id'=>$ids,#所属场馆
                                    'lamp_id'=>$deviceid[3],#所属灯控
                                    'number'=>$i, #编号
                                    'field_name'=>$params['field_name'].$i,
                                    'createtime'=>time()]);
                        }
                    }
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
                if (!isset($params['venue_id'])) {
                    if (session('venue_id')) {
                        $params['venue_id'] = session('venue_id');
                    } else {
                        $params['venue_id'] = 0;
                    }
                }
                $deviceid = explode(',', $params['deviceid']);
                $num = $params['field_number']/8;#所需开关数
                if (count($deviceid)<$num) {
                    $this->error('当前开关数不足');
                }
                $data = [];
                for ($i=1; $i <= $params['field_number']; $i++) {
                    if ($i<=8) {
                        $id = db('lamplist')->where(['field_name'=>$params['field_name'].$i,'reservation_id'=>$ids,'lamp_id'=>$deviceid[0]])->value('id');
                        if (!$id) {
                            db('lamplist')->insert([
                                'field_name'=>$params['field_name'].$i,#场地名
                                'reservation_id'=>$ids,#所属场馆
                                'lamp_id'=>$deviceid[0],#所属灯控
                                'number'=>$i, #编号
                                'field_name'=>$params['field_name'].$i,
                                'createtime'=>time()]);
                        }
                    }
                    if ($i>=9 && $i<=16) {
                        $id = db('lamplist')->where(['field_name'=>$params['field_name'].$i,'reservation_id'=>$ids,'lamp_id'=>$deviceid[1]])->value('id');
                        if (!$id) {
                            db('lamplist')->insert([
                                'field_name'=>$params['field_name'].$i,#场地名
                                'reservation_id'=>$ids,#所属场馆
                                'lamp_id'=>$deviceid[1],#所属灯控
                                'number'=>$i, #编号
                                'field_name'=>$params['field_name'].$i,
                                'createtime'=>time()]);
                        }
                    }
                    if ($i>=17 && $i<=24) {
                        $id = db('lamplist')->where(['field_name'=>$params['field_name'].$i,'reservation_id'=>$ids,'lamp_id'=>$deviceid[2]])->value('id');
                        if (!$id) {
                            db('lamplist')->insert([
                                'field_name'=>$params['field_name'].$i,#场地名
                                'reservation_id'=>$ids,#所属场馆
                                'lamp_id'=>$deviceid[3],#所属灯控
                                'number'=>$i, #编号
                                'field_name'=>$params['field_name'].$i,
                                'createtime'=>time()]);
                        }
                    }
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
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
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        $venue_id = session('venue_id');
        $this->view->assign('venue_id', $venue_id);
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ? $ids : $this->request->post("ids");
        if ($ids) {
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $list = $this->model->where($pk, 'in', $ids)->select();

            $count = 0;
            Db::startTrans();
            try {
                foreach ($list as $k => $v) {
                    $count += $v->delete();
                }
                db('lamplist')->where('reservation_id', $ids)->delete();
                Db::commit();
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            if ($count) {
                $this->success();
            } else {
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }
}
