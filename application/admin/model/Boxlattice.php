<?php

namespace app\admin\model;

use think\Model;


class Boxlattice extends Model
{

    

    

    // 表名
    protected $name = 'boxlattice';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'type_text',
        'state_text',
        'goodsState_text',
        'doorState_text'
    ];
    

    
    public function getTypeList()
    {
        return ['10' => __('Type 10'), '20' => __('Type 20'), '30' => __('Type 30')];
    }

    public function getStateList()
    {
        return ['1' => __('State 1'), '3' => __('State 3'), '5' => __('State 5')];
    }

    public function getGoodsstateList()
    {
        return ['1' => __('Goodsstate 1'), '0' => __('Goodsstate 0')];
    }

    public function getDoorstateList()
    {
        return ['1' => __('Doorstate 1'), '0' => __('Doorstate 0')];
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStateTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['state']) ? $data['state'] : '');
        $list = $this->getStateList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getGoodsstateTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['goodsState']) ? $data['goodsState'] : '');
        $list = $this->getGoodsstateList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getDoorstateTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['doorState']) ? $data['doorState'] : '');
        $list = $this->getDoorstateList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}
