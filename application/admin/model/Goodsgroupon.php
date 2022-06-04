<?php

namespace app\admin\model;

use think\Model;


class Goodsgroupon extends Model
{





    // 表名
    protected $name = 'goods_groupon';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text',
        'groupbuy_status_text',
        'finishtime_text',
        'expiretime_text'
    ];



    public function getStatusList()
    {
        return ['invalid' => __('Status invalid'), 'ing' => __('Status ing'), 'finish' => __('Status finish'), 'finish-fictitious' => __('Status finish-fictitious')];
    }

    public function getGroupbuyStatusList()
    {
        return ['10' => __('Groupbuy_status 10'), '20' => __('Groupbuy_status 20')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getGroupbuyStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['groupbuy_status']) ? $data['groupbuy_status'] : '');
        $list = $this->getGroupbuyStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getFinishtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['finishtime']) ? $data['finishtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getExpiretimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['expiretime']) ? $data['expiretime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setFinishtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setExpiretimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function goods()
    {
        return $this->hasMany('Litestoreordergoods','order_id','id');
    }
}
