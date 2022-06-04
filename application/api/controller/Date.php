<?php

namespace app\api\controller;

use app\common\controller\Api;

class Date extends Api
{
    protected $noNeedLogin = ['*'];
    //获取首页banner图和精彩瞬间
    public function index()
    {
        $banner = db("adv_list")->where(['menu_id' => 1, 'status' => '1'])->limit(5)->cache(60)->order("weigh desc")->select();
        $list = db("ball_article")->where(['category_id' => 1, 'status' => ['neq', '0']])->limit(5)->cache(60)->order("status desc,weigh desc")->select();
        $banner = $this->array_image_add_domain($banner, 'image');
        $list = $this->array_image_add_domain($list, 'image');
        $list = $this->array_time_change($list, 'createtime', 'Y-m-d');
        $this->success('获取成功', ['banner' => $banner, 'list' => $list]);
    }

    //获取店铺配置
    public function shop_config()
    {
        $config = get_addon_config('ball');
        $config['images'] = $this->array_image_add_domain(explode(',', $config['images']), '');
        $config['gps'] = explode(",", $config['gps']);
        $this->success("success", array_slice($config, 4));
    }

    //获取文章列表
    public function article_list()
    {
        $page = input("page", 1, "intval");
        $cid = input("cid", 1, "intval");
        $list = db("ball_article")->where(['category_id' => $cid, 'status' => ['neq', '0']])->page($page, 10)->cache(60)->order("status desc,weigh desc")->select();
        $list = $this->array_image_add_domain($list, 'image');
        $list = $this->array_time_change($list, 'createtime', 'Y-m-d');
        $this->success('获取成功', $list);
    }

    //获取文章
    public function article()
    {
        $id = input("id", 1, "intval");
        $result = db("ball_article")->where(["id" => $id, "status" => ['neq', '0']])->find();
        if ($result) {
            db("ball_article")->where("id", $id)->setInc("read_num");
            $result['images'] = $this->array_image_add_domain(explode(',', $result['images']), '');
            $result['createtime'] = date("Y-m-d", $result['createtime']);
            $this->success("查询成功", ['result' => $result]);
        } else {
            $this->error("文章不存在");
        }
    }

    //获取球场列表
    public function ball_list()
    {
        $list = db("ball_date")->whereTime('time', '>=', date("Y-m-d"))->where("status", "1")->order("time asc")->select();
        $weekarray = array("日", "一", "二", "三", "四", "五", "六");
        foreach ($list as $key => $value) {
            $time = strtotime($value['time']);
            $list[$key]['week_name'] = "星期" . $weekarray[date("w", $time)];
            $list[$key]['time'] = date("m-d", $time);
            $list[$key]['date'] = date("Y-m-d", $time);
            $list[$key]['big_content'] = json_decode($value['big_content'], 1);
        }
        $this->success("查询成功", ['list' => $list]);
    }

    //获取球场详情
    public function ball_detail()
    {
        $id = input("id", 0, "intval");
        $result = db("ball_date")->where("id", $id)->where("status", "1")->find();
        if ($result) {
            $weekarray = array("日", "一", "二", "三", "四", "五", "六");
            $time = strtotime($result['time']);
            $result['week_name'] = "星期" . $weekarray[date("w", $time)];
            $result['time'] = date("m-d", $time);
            $result['date'] = date("Y-m-d", $time);
            $result['big_content'] = json_decode($result['big_content'], 1);
            $this->success("查询成功", ['result' => $result]);
        } else {
            $this->error("数据不存在");
        }
    }

    //获取指定日期的订场情况
    public function date_ticket()
    {
        $date = input("date", '');
        $list = db("ball_order")->alias("a")->join("__BALL_ORDER_DETAIL__ b", 'a.id = b.order_id', 'right')->where(["a.date" => $date, "b.status" => ['in', '0,1,3,4']])->select();
        //价格为o的为不可选
        $info = db("ball_date")->where('time', $date)->find();
        $info['big_content'] = json_decode($info['big_content'], 1);
        $info['small_content'] = json_decode($info['small_content'], 1);
        //循环全场
        foreach ($info['big_content']['big_time'] as $k => $v) {
            foreach ($info['big_content']['big_money'][$k] as $key => $value) {
                //筛选出0元的商品
                if ($value == '0' || strtotime($date . " " . $v[0]) < time()) {
                    $array['date'] = $date;
                    $array['type'] = 'big';
                    $array['time_num'] = $k;
                    $array['money_num'] = $key;
                    $array['sx'] = 0;
                    $list[] = $array;
                }
            }
        }

        //循环半场
        foreach ($info['small_content']['small_time'] as $k => $v) {
            foreach ($info['small_content']['small_money'][$k] as $key => $value) {
                //筛选出0元的商品
                if ($value[0] == '0' || strtotime($date . " " . $v[0]) < time()) {
                    $array['date'] = $date;
                    $array['type'] = 'big';
                    $array['time_num'] = $k;
                    $array['money_num'] = $key;
                    $array['sx'] = 0;
                    $list[] = $array;
                } elseif ($value[1] == '0' || strtotime($date . " " . $v[0]) < time()) {
                    $array['date'] = $date;
                    $array['type'] = 'big';
                    $array['time_num'] = $k;
                    $array['money_num'] = $key;
                    $array['sx'] = 1;
                    $list[] = $array;
                }
            }
        }
        $this->success("success", $list);
    }

}
