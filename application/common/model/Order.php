<?php



namespace app\common\model;

use think\Model;

use think\Db;

use addons\litestore\model\Wxlitestoregoods;

/**

 * 订场订单

 */

class Order extends Model
{



    // 开启自动写入时间戳字段

    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名

    protected $createTime = 'createtime';

    protected $updateTime = 'updatetime';

    // 追加属性

    protected $append = [

    ];



    /**

     * 添加订场订单

     * @param $reservation_id 场馆ID

     * @param $date_id 场次ID

     * @param $time 预定日期

     * @param $time_slot 场次时间段

     * @param $total_price 价格

     * @param $date 场地

     * @param $groupbuying 是否开团 10=否；20=是

     * @param $mobile 手机号

     */

    public function dcorder($reservation_id ='', $total_price ='', $specs ='', $groupbuying ='', $mobile='', $user_id='', $venue_id='')
    {
        $order = [

         'order_no' => orderNo(),

         'total_price' => $total_price,

         'pay_price' => $total_price,

         'user_id' => $user_id,

         'venue_id'       =>$venue_id,

         'createtime' => time(),

         'updatetime' => time(),

      ];

        $specs = preg_replace("/(\s|\&quot\;|　|\xc2\xa0)/", '"', strip_tags($specs));

        $arr = json_decode($specs, true);

        // 启动事务

        Db::startTrans();

        try {
            $order_id = db('litestore_order')->insertGetId($order);

            if ($order_id) {
                $goods_name = db('reservation')->where('id', $reservation_id)->find();

                foreach ($arr as $v) {
                    $order_goods[] = [

                'order_id'=>$order_id,

                'goods_id'=>$goods_name['id'],

                'goods_name'=>$goods_name['name'],

                'images'=>$goods_name['images'],

                'total_num'=>1,

                'goods_price'=>$total_price,

                'total_price'=>$total_price,

                'user_id'    =>$user_id,

                'time'    =>$v['time'],

                'time_slot'    =>$v['time_slot'],

                'date'    =>$v['date'],

                'goods_price'=>$v['price'],

                'groupbuying'    =>$groupbuying,

                'mobile'         =>$mobile,

                'goods_attr' =>'场地：'. $v['date'].',时间： '.$v['time'].' '.$v['time_slot'],

                'createtime' => time(),

                'reservation_id'=>$reservation_id

              ];
                }

                db('litestore_order_goods')->insertAll($order_goods);
            }

            // 提交事务

            Db::commit();
        } catch (\Exception $e) {

            // 回滚事务

            Db::rollback();

            return false;
        }

        return $order_id;
    }



    /**

     * 订单列表

     * @param $type 订单类型:10=订场订单,20=订票订单,30=购物订单,40=全部订单

     * @param $key 关键词

     */

    public function orderlist($type='', $key='', $user_id='')
    {
        $list = $this->model->getList($user_id, $type, '', $key);

        return json_encode($list, true);
    }
}
