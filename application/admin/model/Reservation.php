<?php

namespace app\admin\model;

use think\Model;


class Reservation extends Model
{





    // 表名
    protected $name = 'reservation';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'type_text',
        'group_work_time_text',
        'status_text'
    ];



    public function getTypeList()
    {
        return ['10' => __('Type 10'), '20' => __('Type 20')];
    }

    public function getStatusList()
    {
        return ['10' => __('Status 10'), '20' => __('Status 20')];
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getGroupWorkTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['group_work_time']) ? $data['group_work_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setGroupWorkTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    public function type()
    {
        return $this->belongsTo('type', 'type_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
