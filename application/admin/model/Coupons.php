<?php

namespace app\admin\model;

use think\Model;


class Coupons extends Model
{





    // 表名
    protected $name = 'coupons';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'scene_type_text',
        'coupons_type_text'
    ];


    protected static function init()
    {
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });
    }


    public function getSceneTypeList()
    {
        return ['10' => __('Scene_type 10'), '20' => __('Scene_type 20'), '30' => __('Scene_type 30'), '40' => __('Scene_type 40'), '50' => __('Scene_type 50')];
    }

    public function getCouponsTypeList()
    {
        return ['10' => __('Coupons_type 10'), '20' => __('Coupons_type 20'), '30' => __('Coupons_type 30')];
    }


    public function getSceneTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['scene_type']) ? $data['scene_type'] : '');
        $list = $this->getSceneTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getCouponsTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['coupons_type']) ? $data['coupons_type'] : '');
        $list = $this->getCouponsTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function venue()
    {
        return $this->belongsTo('app\admin\model\Venue', 'venue_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
