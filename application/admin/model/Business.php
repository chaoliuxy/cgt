<?php

namespace app\admin\model;

use think\Model;


class Business extends Model
{
    // 表名
    protected $name = 'business';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
      'is_recommend_text',
      'type_text',
    ];

    public function getIsRecommendList()
    {
        return ['10' => __('Is_recommend 10'),'20' => __('Is_recommend 20')];
    }

    public function getTypeList()
    {
        return ['10' => __('Type 10'),'20' => __('Type 20'),'30' => __('Type 30')];
    }

		public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

		public function getIsRecommendTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_recommend']) ? $data['is_recommend'] : '');
        $list = $this->getIsRecommendList();
        return isset($list[$value]) ? $list[$value] : '';
    }
}
