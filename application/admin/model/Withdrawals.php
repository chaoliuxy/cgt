<?php

namespace app\admin\model;

use think\Model;


class Withdrawals extends Model
{

    

    

    // 表名
    protected $name = 'withdrawal';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text'
    ];
    

    
    public function getStatusList()
    {
        return ['10' => __('Status 10'), '20' => __('Status 20'), '30' => __('Status 30')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }




    public function venue()
    {
        return $this->belongsTo('Venue', 'venue_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
