<?php

namespace app\admin\model;

use think\Model;


class Activity extends Model
{

    

    

    // 表名
    protected $name = 'activity';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'type_text',
        'status_text',
        'hot_status_text',
        'recommend_status_text',
        'pay_type_text',
    ];
    

    protected static function init()
    {
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });
    }

    public function getPayTypeList()
    {
        return ['10' => __('pay_type 10'), '20' => __('pay_type 20')];
    }

    public function getTypeList()
    {
        return ['10' => __('Type 10'), '20' => __('Type 20')];
    }

    public function getStatusList()
    {
        return ['10' => __('Status 10'), '20' => __('Status 20'), '30' => __('Status 30')];
    }

    public function getHotStatusList()
    {
        return ['10' => __('hot_status 10'), '20' => __('hot_status 20')];
    }

    public function getRecommendStatusList()
    {
        return ['10' => __('recommend_status 10'), '20' => __('recommend_status 20')];
    }

    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getPayTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getPayTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getHotStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['hot_status']) ? $data['hot_status'] : '');
        $list = $this->getHotStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getRecommendStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['recommend_status']) ? $data['recommend_status'] : '');
        $list = $this->getRecommendStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}
