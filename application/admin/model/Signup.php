<?php

namespace app\admin\model;

use think\Model;


class Signup extends Model
{

    

    

    // 表名
    protected $name = 'signup';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'gender_text',
        'status_text',
        'type_text'
    ];
    

    
    public function getGenderList()
    {
        return ['10' => __('Gender 10'), '20' => __('Gender 20')];
    }

    public function getStatusList()
    {
        return ['10' => __('Status 10'), '20' => __('Status 20'), '30' => __('Status 30'), '40' => __('Status 40'), '50' => __('Status 50')];
    }

    public function getTypeList()
    {
        return ['10' => __('Type 10'), '20' => __('Type 20')];
    }


    public function getGenderTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['gender']) ? $data['gender'] : '');
        $list = $this->getGenderList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }




    public function activity()
    {
        return $this->belongsTo('Activity', 'activity_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function venue()
    {
        return $this->belongsTo('Venue', 'venue_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
