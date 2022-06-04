<?php

namespace app\common\model;

use think\Model;

/**
 * 场馆余额日志模型
 */
class Venuelog Extends Model
{

    // 表名
    protected $name = 'venue_money_log';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = '';
    // 追加属性
    protected $append = [
    ];

		/**
		 * 添加场馆余额流水
		 * @param $type 交易类型:10=充值VIP,20=订场,30=课程,40=购物,50=活动,60=点餐,70=提现,80=退款
		 */
		public function addvenuemongylog($venue_id='',$money='',$before='',$memo='',$type=''){
      if (in_array($type,['70','80'])) {
				# 支出
				db('venue')->where('id',$venue_id)->setDec('money',$money);
				$after = db('venue')->where('id',$venue_id)->value('money');
			}else{
				# 收入
				db('venue')->where('id',$venue_id)->setInc('money',$money);
				$after = db('venue')->where('id',$venue_id)->value('money');
			}

			$data = [
				'venue_id'  =>$venue_id,
				'money'     =>$money,
				'before'    =>$before,
				'after'     =>$after,
				'type'      =>$type,
				'memo'      =>$memo,
				'createtime'=>time(),
			];
			$add = db('venue_money_log')->insert($data);
			if ($add) {
					return true;
			}else{
					return false;
		  }
		}
}
