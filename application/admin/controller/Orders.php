<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use addons\litestore\model\Litestoreorder;
use think\Log;

/**
 *
 *
 * @icon fa fa-circle-o
 */
class Orders extends Backend
{

    /**
     * Orders模型对象
     * @var \app\admin\model\Orders
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Orders;
        $this->view->assign("payStatusList", $this->model->getPayStatusList());
        $this->view->assign("payTypeList", $this->model->getPayTypeList());
        $this->view->assign("orderTypeList", $this->model->getOrderTypeList());
        $this->view->assign("groupbuyingList", $this->model->getGroupbuyingList());
        $this->view->assign("groupbuyingStatusList", $this->model->getGroupbuyingStatusList());
        $this->view->assign("isHeadList", $this->model->getIsHeadList());
        // $this->view->assign("statusList", $this->model->getStatusList());
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
            if (session('venue_id')) {
            $list = $this->model
                    ->with(['litestoreordergoods','user','litestoreorderaddress','reservation','venue'])
                    ->where('orders.pay_status', '20')
                    ->where('orders.order_type', 'in', ['20','10'])
                    ->where($where)
                    ->where('orders.shop_id',session('venue_id'))
                    ->order($sort, $order)
                    ->group('orders.id')
                    ->paginate($limit);
            }else{
                        $list = $this->model
                        ->with(['litestoreordergoods','user','litestoreorderaddress','reservation','venue'])
                        ->where('orders.pay_status', '20')
                        ->where('orders.order_type', 'in', ['20','10'])
                        ->where($where)
                        ->order($sort, $order)
                        ->group('orders.id')
                        ->paginate($limit);
                    }

            foreach ($list as $row) {

                $row->getRelation('litestoreordergoods')->visible(['goods_name','goods_attr']);
				$row->getRelation('user')->visible(['nickname']);
				$row->getRelation('litestoreorderaddress')->visible(['name','phone','detail']);
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }


    public function detail()
    {
        $param = $this->request->param();
        $row = db('order')->where('id', $param['ids'])->find();
        if (in_array($row['order_type'], ['10','20'])) {
            $row['goods_list'] = db('litestore_order_goods')->where('order_id', 'in', $row['order_ids'])->field('order_id,goods_id,goods_name,images,total_num,goods_attr,goods_price,total_price,total_num')->select();
        } else {
            if ($row['order_type']=='40') {
                # 活动
                $activity_id = db('signup')->where('id', $row['order_ids'])->value('activity_id');
                $row['goods_list'] = db('activity')->where('id', $activity_id)->field('name as goods_name,price as goods_price')->select();
            }
            $row['goods_list'] = db('litestore_order_goods')->where('order_id', 'in', $row['order_ids'])->field('order_id,goods_id,goods_name,images,total_num,goods_attr,goods_price,total_price,total_num')->select();
        }
        if ($row['pay_status']=='20') {
            $row['pay_status_text'] = '已支付';
        } else {
            $row['pay_status_text'] = '未支付';
        }
        //团购状态:10=非团购,20=待成团,30=已成团,40=拼团失败
        if ($row['groupbuying_status']=='10') {
            $row['order_status_text'] = '已支付';
        } elseif ($row['groupbuying_status']=='20') {
            $row['order_status_text'] = '待成团';
        } elseif ($row['groupbuying_status']=='30') {
            $row['order_status_text'] = '已完成';
        } elseif ($row['groupbuying_status']=='40') {
            $row['order_status_text'] = '拼团失败';
        }
        $row['user'] = db('user')->where('id', $row['user_id'])->field('nickname,avatar')->find();
        $this->view->assign('vo', $row);
        return $this->view->fetch();
    }

}
