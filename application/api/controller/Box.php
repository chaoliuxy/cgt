<?php

namespace app\api\controller;

use app\common\controller\Api;
use addons\qrcode\controller\Index as qrcode;
use fast\Http;

/**
 * 储物柜接口
 */
class Box extends Api
{
    protected $noNeedLogin = ['test'];
    protected $noNeedRight = ['*'];

    /**
     * 储物柜
     */
    public function index()
    {
        $list = db('box')->where('venue_id', $this->auth->venue_id)->field('id,case_id,opentime,address,tel')->find();
        if ($list) {
            $qrcode = new qrcode();
            $list['qrcode'] = cdnurl($qrcode->builds($list['case_id']), true);
        }
        $this->success('ok', $list);
    }

    /**
     * 生成小程序二维码
     */
    public function mpcodes()
    {
        //参数
        $user = $this->auth->getUser();
        // $postdata['scene']="nidaodaodao";
        $postdata['scene'] = $user;
        // 宽度
        $postdata['width'] = 430;
        // 页面
        // $postdata['page']=$page;
        $page = 1;
        if (!$page) {
            $this->error('参数不全');
        }
        $postdata['page'] = $page;
        // 线条颜色
        $postdata['auto_color'] = false;
        //auto_color 为 false 时生效
        $postdata['line_color'] = ['r' => '0', 'g' => '0', 'b' => '0'];
        // 是否有底色为true时是透明的
        $postdata['is_hyaline'] = true;
        $post_data = json_encode($postdata);
        $access_token = $this->getAccesstoken();
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $access_token;
        $result = $this->api_notice_increment($url, $post_data);
        $data = 'image/png;base64,' . base64_encode($result);
        echo '<img src="data:' . $data . '">';
    }


    /**
     * 码二，正方形的二维码，数量限制调用十万条
     */
    public function mpcode()
    {
        $box['id'] = 1002;
        $path = "/pages/storage/index?scene=" . $box['id'];
        // 宽度
        $postdata['width'] = 300;
        // 页面
        $postdata['scene'] = $box['id'];
        $postdata['path'] = $path;
        $post_data = json_encode($postdata);
        $access_token = $this->getAccesstoken();
        $url = "https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token=" . $access_token;
        $result = $this->api_notice_increment($url, $post_data);
        $data = 'image/png;base64,' . base64_encode($result);
        $data = substr($data, 17);
        $base_img = str_replace('data:image/jpg;base64,', '', $data);

        //  设置文件路径和文件前缀名称

        $path = ROOT_PATH . 'public/uploads/qrcode/';

        $prefix = 'nx_';

        $output_file = $prefix . time() . rand(100, 999) . '.jpg';

        $path = $path . $output_file;

        //  创建将数据流文件写入我们创建的文件内容中

        $ifp = fopen($path, "wb");

        fwrite($ifp, base64_decode($data));

        fclose($ifp);
        $this->success('ok', cdnurl('/uploads/qrcode/' . $output_file, true));
        return '<img src="data:' . $data . '">';
    }

    /**
     * 获取accesstoken
     */
    public function getAccesstoken()
    {
        $appid = config('fastadmin.appid');                     /*小程序appid*/
        $srcret = config('fastadmin.secret');      /*小程序秘钥*/
        $tokenUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appid . "&secret=" . $srcret;
        $getArr = array();
        $tokenArr = json_decode($this->send_post($tokenUrl, $getArr, "GET"));
        $access_token = $tokenArr->access_token;
        return $access_token;
    }

    public function send_post($url, $post_data, $method = 'POST')
    {
        $postdata = http_build_query($post_data);
        $options = array(
            'http' => array(
                'method' => $method, //or GET
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => $postdata,
                'timeout' => 15 * 60 // 超时时间（单位:s）
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return $result;
    }

    public function api_notice_increment($url, $data)
    {
        $ch = curl_init();
        $header = array('Accept-Language:zh-CN', 'x-appkey:114816004000028', 'x-apsignature:933931F9124593865313864503D477035C0F6A0C551804320036A2A1C5DF38297C9A4D30BB1714EC53214BD92112FB31B4A6FAB466EEF245710CC83D840D410A7592D262B09D0A5D0FE3A2295A81F32D4C75EBD65FA846004A42248B096EDE2FEE84EDEBEBEC321C237D99483AB51235FCB900AD501C07A9CAD2F415C36DED82', 'x-apversion:1.0', 'Content-Type:application/x-www-form-urlencoded', 'Accept-Charset: utf-8', 'Accept:application/json', 'X-APFormat:json');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmpInfo = curl_exec($ch);
        if (curl_errno($ch)) {
            return false;
        } else {
            return $tmpInfo;
        }
    }

    /**
     * 测试灯控
     */
    public function test()
    {
        $number = $this->request->request('number');
        $type = $this->request->request('type');
        if (!in_array($type, ['10', '20'])) {
            $this->error('操作类型错误');
        }
        $url = 'http://120.79.196.238:8001/light';
        if ($type == '10') {
            # open
            $data = [
                'id' => 1,
                'action' => 'open' . $number,
            ];
        } else {
            # close
            $data = [
                'id' => 1,
                'action' => 'close' . $number,
            ];
        }
        $result = Http::post($url, json_encode($data, true));
        $this->success('ok', json_decode($result, true));
    }

    /**
     * 场地灯开/关
     */
    public function operation()
    {
        $param = $this->request->param();
        $litestore_order_goods_id = $this->request->request('litestore_order_goods_id');
        $type = $this->request->request('type');
        if (!in_array($type, ['10', '20'])) {
            $this->error('操作类型错误');
        }
        if (!$litestore_order_goods_id) {
            $this->error('参数不全');
        }
        $orders = db('litestore_order_goods')->where('id', $litestore_order_goods_id)->field('order_id,reservation_id,date,time,time_slot')->find();
        $pay_status = db('order')->where('id', $orders['order_id'])->value('pay_status');
        if ($pay_status == '10') {
            $this->error('订单未支付');
        }
        $data = db('lamplist')->where(['reservation_id' => $orders['reservation_id'], 'field_name' => $orders['date']])->find();
        if (!$data['id']) {
            $this->error('当前场地暂未设置灯控开关');
        }
        $times = $orders['time'] . ' ' . $orders['time_slot'];
        if (strtotime($times) > time()) {
            $time = strtotime($times) - time();
            if ($time > 600) {
                $this->error('请于订场时间前10分钟操作');
            }
        } else {
            $time = time() - strtotime($times);
            if ($time > 1800) {
                $this->error('距离当前订场时间已超过30分钟，不可再次操作');
            }
        }
        if (!in_array($type, ['10', '20'])) {
            $this->error('操作类型错误');
        }
        $url = 'http://120.79.196.238:8001/light';
        if ($type == '20') {
            # open
            $datas = [
                'id' => $data['lamp_id'],
                'action' => 'open' . $data['number'],
            ];
        } else {
            # close
            $datas = [
                'id' => $data['lamp_id'],
                'action' => 'close' . $data['number'],
            ];
        }
        $result = json_decode(Http::post($url, json_encode($datas, true)), true);
        if ($result['result'] == 'OK') {
            db('lamplist')->where('id', $data['id'])->update(['status' => $type]);
            $this->success('操作成功', $result['result']);
        } else {
            $this->error('操作失败', $result['result']);
        }
    }
}
