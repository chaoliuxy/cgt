<?php

namespace app\admin\model;

use think\Model;


class Orders extends Model
{





    // 表名
    protected $name = 'order';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'pay_status_text',
        'pay_time_text',
        'pay_type_text',
        'order_type_text',
        'groupbuying_text',
        'groupbuying_status_text',
        'is_head_text',
        'group_buy_time_text',
        // 'status_text'
    ];



    public function getPayStatusList()
    {
        return ['10' => __('Pay_status 10'), '20' => __('Pay_status 20'),'70' => __('Pay_status 70'), '80' => __('Pay_status 80'), '90' => __('Pay_status 90'), '100' => __('Pay_status 100'), '110' => __('Pay_status 110')];
    }

    public function getPayTypeList()
    {
        return ['10' => __('Pay_type 10'), '20' => __('Pay_type 20')];
    }

    public function getOrderTypeList()
    {
        return ['10' => __('Order_type 10'), '20' => __('Order_type 20'), '30' => __('Order_type 30'), '40' => __('Order_type 40'), '50' => __('Order_type 50'), '60' => __('Order_type 60')];
    }

    public function getGroupbuyingList()
    {
        return ['10' => __('Groupbuying 10'), '20' => __('Groupbuying 20')];
    }

    public function getGroupbuyingStatusList()
    {
        return ['10' => __('Groupbuying_status 10'), '20' => __('Groupbuying_status 20'), '30' => __('Groupbuying_status 30'), '40' => __('Groupbuying_status 40')];
    }

    public function getIsHeadList()
    {
        return ['10' => __('Is_head 10'), '20' => __('Is_head 20')];
    }

    // public function getStatusList()
    // {
    //     return ['10' => __('Status 10'), '20' => __('Status 20'), '30' => __('Status 30'), '40' => __('Status 40'), '50' => __('Status 50'), '60' => __('Status 60'), '70' => __('Status 70'), '80' => __('Status 80'), '90' => __('Status 90'), '100' => __('Status 100'), '110' => __('Status 110')];
    // }


    public function getPayStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['pay_status']) ? $data['pay_status'] : '');
        $list = $this->getPayStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getPayTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['pay_time']) ? $data['pay_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getPayTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['pay_type']) ? $data['pay_type'] : '');
        $list = $this->getPayTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getOrderTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['order_type']) ? $data['order_type'] : '');
        $list = $this->getOrderTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getGroupbuyingTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['groupbuying']) ? $data['groupbuying'] : '');
        $list = $this->getGroupbuyingList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getGroupbuyingStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['groupbuying_status']) ? $data['groupbuying_status'] : '');
        $list = $this->getGroupbuyingStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getIsHeadTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_head']) ? $data['is_head'] : '');
        $list = $this->getIsHeadList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getGroupBuyTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['group_buy_time']) ? $data['group_buy_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    // public function getStatusTextAttr($value, $data)
    // {
    //     $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
    //     $list = $this->getStatusList();
    //     return isset($list[$value]) ? $list[$value] : '';
    // }

    protected function setPayTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setGroupBuyTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function litestoreordergoods()
    {
        return $this->belongsTo('LitestoreOrderGoods', 'order_ids', 'order_id', [], 'LEFT')->setEagerlyType(0);
    }

    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function reservation()
    {
        return $this->belongsTo('Reservation', 'shop_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function venue()
    {
        return $this->belongsTo('Venue', 'venue_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function litestoreorderaddress()
    {
        return $this->belongsTo('LitestoreOrderAddress', 'order_ids', 'order_id', [], 'LEFT')->setEagerlyType(0);
    }
}
