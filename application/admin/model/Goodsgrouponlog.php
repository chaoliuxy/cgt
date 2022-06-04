<?php

namespace app\admin\model;

use think\Model;


class Goodsgrouponlog extends Model
{

    

    

    // 表名
    protected $name = 'goods_groupon_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'is_refund_text'
    ];
    

    
    public function getIsRefundList()
    {
        return ['0' => __('Is_refund 0'), '1' => __('Is_refund 1')];
    }


    public function getIsRefundTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_refund']) ? $data['is_refund'] : '');
        $list = $this->getIsRefundList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}
