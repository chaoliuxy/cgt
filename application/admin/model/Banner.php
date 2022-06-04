<?php

namespace app\admin\model;

use think\Model;

class Banner extends Model
{





    // 表名
    protected $name = 'banner';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'position_status_text',
        'type_text',
        'status_text'
    ];


    protected static function init()
    {
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });
    }


    public function getPositionStatusList()
    {
        return ['homepage' => __('Position_status homepage'), 'explain' => __('Position_status explain'),'business' => __('Position_status business')];
    }

    public function getTypeList()
    {
        return ['10' => __('Type 10'), '20' => __('Type 20'), '30' => __('Type 30'), '40' => __('Type 40'), '50' => __('Type 50')];
    }

    public function getStatusList()
    {
        return ['10' => __('Status 10'), '20' => __('Status 20')];
    }


    public function getPositionStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['position_status']) ? $data['position_status'] : '');
        $list = $this->getPositionStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function venue()
    {
        return $this->belongsTo('app\admin\model\Venue', 'venue_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
