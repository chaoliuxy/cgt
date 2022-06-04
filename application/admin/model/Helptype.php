<?php

namespace app\admin\model;

use think\Model;


class Helptype extends Model
{





    // 表名
    protected $name = 'helptype';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    public function venue()
    {
        return $this->belongsTo('app\admin\model\Venue', 'venue_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }








}
