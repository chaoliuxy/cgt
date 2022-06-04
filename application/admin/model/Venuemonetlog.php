<?php

namespace app\admin\model;

use think\Model;


class Venuemonetlog extends Model
{

    

    

    // 表名
    protected $name = 'venue_money_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'type_text'
    ];
    

    
    public function getTypeList()
    {
        return ['10' => __('Type 10'), '20' => __('Type 20'), '30' => __('Type 30'), '40' => __('Type 40'), '50' => __('Type 50'), '60' => __('Type 60'), '70' => __('Type 70')];
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }




    public function venue()
    {
        return $this->belongsTo('Venue', 'venue_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
