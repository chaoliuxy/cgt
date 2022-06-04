<?php

namespace app\admin\model;

use think\Model;


class Helpphone extends Model
{

    

    

    // 表名
    protected $name = 'helpphone';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    







    public function venue()
    {
        return $this->belongsTo('Venue', 'venue_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
