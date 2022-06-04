<?php

namespace app\admin\controller\ball;

use app\common\controller\Backend;

/**
 *
 *
 * @icon fa fa-circle-o
 */
class Refund extends Backend
{
    
    /**
     * Refund模型对象
     * @var \app\admin\model\ball\Refund
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\ball\Refund;
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
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                    ->with(['user','ballorderdetail','ballorder'])
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['user','ballorderdetail','ballorder'])
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                $row->visible(['id','user_id','admin_id','out_refund_no','total_fee','refund_fee','refund_desc','status','note','createtime','updatetime']);
                $row->visible(['user']);
                $row->getRelation('user')->visible(['nickname','mobile']);
                $row->visible(['ballorderdetail']);
                $row->getRelation('ballorderdetail')->visible(['type','time_num','money_num','sx']);
                $row->visible(['ballorder']);
                $row->getRelation('ballorder')->visible(['date']);
            }

            $list = collection($list)->toArray();

            foreach ($list as $key => $value) {
                $list[$key]['total_fee'] = $value['total_fee']/100;
                $list[$key]['refund_fee'] = $value['refund_fee']/100;
                $list[$key]['ballorderdetail']['sx'] = $value['ballorderdetail']['sx'] == 0 ?"大场":"小场";

                if ($value['ballorder']['date']) {
                    $detail = db("ball_date")->where("time", $value['ballorder']['date'])->find();
                    $detail['big_content'] = json_decode($detail['big_content'], 1);
                    $detail['small_content'] = json_decode($detail['small_content'], 1);
                    $list[$key]['ballorderdetail']['time_num'] = $value['ballorderdetail']['type']?$detail[$value['ballorderdetail']['type']."_content"][$value['ballorderdetail']['type']."_time"][$value['ballorderdetail']['time_num']][0]."--".$detail[$value['ballorderdetail']['type']."_content"][$value['ballorderdetail']['type']."_time"][$value['ballorderdetail']['time_num']][1]:'';

                    $list[$key]['ballorderdetail']['money_num'] = "球场".($value['ballorderdetail']['money_num']+1);
                    unset($detail);
                }
            }

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
}
