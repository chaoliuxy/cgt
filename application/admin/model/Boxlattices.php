<?php

namespace app\admin\model;

use think\Model;


class Boxlattices extends Model
{

    

    

    // 表名
    protected $name = 'boxlattices';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'boxType_text',
        'state_text',
        'goodsState_text',
        'doorState_text',
        'use_status_text',
        'is_use_text'
    ];
    

    
    public function getBoxtypeList()
    {
        return ['C' => __('Boxtype c'), 'S' => __('Boxtype s'), 'M' => __('Boxtype m'), 'L' => __('Boxtype l'), 'X' => __('Boxtype x')];
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

    public function getUseStatusList()
    {
        return ['10' => __('Use_status 10'), '20' => __('Use_status 20')];
    }

    public function getIsUseList()
    {
        return ['10' => __('Is_use 10'), '20' => __('Is_use 20')];
    }


    public function getBoxtypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['boxType']) ? $data['boxType'] : '');
        $list = $this->getBoxtypeList();
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


    public function getUseStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['use_status']) ? $data['use_status'] : '');
        $list = $this->getUseStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getIsUseTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_use']) ? $data['is_use'] : '');
        $list = $this->getIsUseList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}
