<?php

namespace app\admin\model;

use think\Model;


class Distribution extends Model
{





    // 表名
    protected $name = 'distribution';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'delivery_type_text'
    ];



    public function getDeliveryTypeList()
    {
        return ['10' => __('Delivery_type 10'), '20' => __('Delivery_type 20'), '30' => __('Delivery_type 30'), '40' => __('Delivery_type 40'), '50' => __('Delivery_type 50')];
    }


    public function getDeliveryTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['delivery_type']) ? $data['delivery_type'] : '');
        $list = $this->getDeliveryTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function venue()
    {
        return $this->belongsTo('Venue', 'venue_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


}
