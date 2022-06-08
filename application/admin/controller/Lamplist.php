<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use fast\Http;
use think\Db;
use think\Log;

/**
 * 灯控列管理
 *
 * @icon fa fa-circle-o
 */
class Lamplist extends Backend
{

    /**
     * Lamplist模型对象
     * @var \app\admin\model\Lamplist
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Lamplist;
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
        //设置过滤方法reservation
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            if (session('venue_id')) {
                $list = $this->model
                        ->where($where);
            } else {
                $list = $this->model
                        ->where($where);
            }
            $list = $list
                ->where('reservation_id', $ids)
                ->order($sort, $order)
                ->paginate($limit);
            foreach ($list as &$v) {
                $v['reservation_id'] = db('reservation')->where('id', $v['reservation_id'])->value('name');
            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    public function open($ids='')
    {
        $row = $this->model->where('id', $ids)->find();
//        $url = 'http://120.79.196.238:8001/light';
        $url = 'http://112.74.105.251:8001/light';
        $datas = [
            'id'=>$row['lamp_id'],
            'action'=>'open'.$row['number'],
        ];
        $result = Http::http_post_json($url, json_encode($datas, true));
        if (json_decode($result[1], true)['result']=='OK') {
            db('lamplist')->where('id', $ids)->update(['status'=>'20']);
            $this->success('操作成功', json_decode($result[1], true)['result']);
        } else {
            $this->error('操作失败', json_decode($result[1], true)['result']);
        }
    }

    public function close($ids='')
    {
        $row = $this->model->where('id', $ids)->find();
        $url = 'http://120.79.196.238:8001/light';
        $datas = [
            'id'=>$row['lamp_id'],
            'action'=>'close'.$row['number'],
        ];
        $result = Http::http_post_json($url, json_encode($datas, true));
        if (json_decode($result[1], true)['result']=='OK') {
            db('lamplist')->where('id', $ids)->update(['status'=>'10']);
            $this->success('操作成功', json_decode($result[1], true)['result']);
        } else {
            $this->error('操作失败', json_decode($result[1], true)['result']);
        }
    }
}
