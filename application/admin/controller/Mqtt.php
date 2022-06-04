<?php

namespace app\admin\controller;

use think\Loader;
use think\Log;

class Mqtt
{
    /**
     * Mgtt模型对象
     * @var \app\admin\model\Mqtt
     */
    protected $model = null;
    protected $noNeedLogin = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Mqtt;
    }

    //mqtt发布
    public function pub($msg='')
    {
        // 客户端id  可以用随机数
        $client = "tp5Mqtt".rand(1000, 9999);
        // mqtt主机 主机，请配置为自己的主机
        $host = "112.74.105.251";
        // mqtt端口
        $port = 1883;
        // 密钥 用于证书配置,如果需要ssl认证，则必须填写
        // $this->cert= 'ca.pem';
        // mqtt账号
        $username = "cgt";
        // mqtt密码
        $password = "2nywSbKLKmm23jkf";
        // 订阅主题 订阅的主题，注意使用的主题一定要是mqtt配置过的主题，比如百度天工需要策略认证过的
        // 自己学习的时候，可以随意自定义，一般跟发布主题一致便可以收到消息
        // 如要要接受所有主题，请使用#
        $topics_name = "topic_server";
        //引入phpMQTT 创建mqtt实例
        Loader::import('phpmqtt/phpMQTT', EXTEND_PATH);
        $mqtt = new \phpmqtt\phpMQTT($host, $port, $client);
        //发布，发布内容可自定义，这里写死
        if ($mqtt->connect(true, null, $username, $password)) {
            $mqtt->publish($topics_name, $msg, 0);
            $mqtt->close();
        } else {
            echo "Time out!\n";
        }
    }

    /**
     * 要使用命令行运行此方法！！！
     *  think5.0 运行方法为 在宝塔终端中，cd到Public 目录  然后用守户程序运行 nohup php index.php admin/index/sub &
     * 该类主要为订阅，建议订阅代码和发布代码不要写在同一个类中，避免修改造成不必要的误改。nohup php index.php admin/mqtt/sub > /dev/null 2> /dev/null &
     * 每次更新该类后需要重启mqtt订阅，否则新的改动不会生效。
     * 请在相应的位置放入phpMQTT的库
     * 库代码：https://github.com/bluerhinos/phpMQTT/blob/master/phpMQTT.php
     * 类库使用的时候注意命名空间，类名称命名要和thinkphp的保持一致，不然会报错
     */
    public function sub()
    {
        // 客户端id  可以用随机数
        $client = "tp5Mqtt".rand(1000, 9999);
        // mqtt主机 主机，请配置为自己的主机
        $host = "112.74.105.251";
        // mqtt端口
        $port = 1883;
        // 密钥 用于证书配置,如果需要ssl认证，则必须填写
        // $this->cert= 'ca.pem';
        // mqtt账号
        $username = "cgt";
        // mqtt密码
        $password = "2nywSbKLKmm23jkf";
        // 订阅主题 订阅的主题，注意使用的主题一定要是mqtt配置过的主题，比如百度天工需要策略认证过的
        // 自己学习的时候，可以随意自定义，一般跟发布主题一致便可以收到消息
        // 如要要接受所有主题，请使用#
        $topics_name = "topic_server";
        //引入phpMQTT 创建mqtt实例
        Loader::import('phpmqtt/phpMQTT', EXTEND_PATH);
        $mqtt = new \phpmqtt\phpMQTT($host, $port, $client);
        if (!$mqtt->connect(true, null, $username, $password)) {
            exit('error');   //连接失败
        } else {
            echo "success"; //连接成功
        }
        //topics["topic"]  为接受的主题名  需要和发送的主题名一致  否则会订阅不到
        //订阅信息 Qos为信息登记，需要和发送的等级一致
        $topics[$topics_name] = array("qos" => 0, "function" =>array($this,"onMessage"));
        $mqtt->subscribe($topics, 0);
        //死循环监听
        while ($mqtt->proc()) {
        }
        $mqtt->close();
    }

    /**
     * 在此处接MQtt的信息 进行业务处理
     * @param $topic
     * @param $msg
     */
    public function onMessage($topic, $msg)
    {
        $data['topicName'] = $topic;
        $data['content'] = $msg;
        //保存数据到数据库
        if ($data['content']!=null || $data['content']!='') {
            $content = json_decode($data['content'],true);
            // Log::error($data['content'].'1111');
            if (isset($content['msgType'])) {
            switch ($content['msgType']) {
                case '501':
                    # 柜子状态查询响应...
                    break;
                    case '110':
                    # 登录注册发起后响应...
                    $msg ='{
                        "msgType":111,
                        "devId":"'.$content["devId"].'",
                        "result":1,
                        "txnNo":'.$content["txnNo"].'
                    }';
                    $this->pubs($msg);
                    break;
                    case '111':
                    # 登录注册响应...
                    break;
                    case '210':
                    # 数据上报发起...
                    $msg ='{
                      "msgType":211,
                      "devId":"'.$content["devId"].'",
                      "result":1,
                      "txnNo":'.$content["txnNo"].'
                    }';
                    $this->pubs($msg);
                    // if (isset($content['dataType'])) {
                    if ($content['dataType']==0) {
                        foreach ($content['list'] as $v) {
                            $ids = db('boxlattices')->where(['devId'=>$content['devId'],'boxId'=>$v['boxId']])->value('id');
                            if (!$ids) {
                              db('boxlattices')->insert(['devId'=>$content['devId'],'boxId'=>$v['boxId'],'boxType'=>$v['boxType'],'createtime'=>time()]);
                            }
                        }
                    }else{
                        if ($content['msgType']=='210' && isset($content['list'][0]['state'])) {
                            $a = $content['list'][0]['state'];
                            $str = str_split($a,1);
                            foreach ($str as $k=>$v) {
                            $s = $k+1;
                            if (in_array($v,[3,5])) {
                                db('boxlattices')->where(['devId'=>$content['devId'],'boxId'=>$s])->update(['state'=>$v,'use_status'=>'20']);
                            }else{
                                db('boxlattices')->where(['devId'=>$content['devId'],'boxId'=>$s])->update(['state'=>$v,'use_status'=>'10']);
                            }
                            }
                        }elseif($content['msgType']=='210' && isset($content['list'][0]['goodsState'])){
                            $a = $content['list'][0]['goodsState'];
                            $str = str_split($a,1);
                            foreach ($str as $k=>$v) {
                            $s = $k+1;
                            if (in_array($v,[1])) {
                                db('boxlattices')->where(['devId'=>$content['devId'],'boxId'=>$s])->update(['goodsState'=>$v]);
                                // db('boxlattices')->where(['devId'=>$content['devId'],'boxId'=>$s])->update(['goodsState'=>$v,'use_status'=>'20']);
                            }else{
                                db('boxlattices')->where(['devId'=>$content['devId'],'boxId'=>$s])->update(['goodsState'=>$v,'use_status'=>'10']);
                            }
                            }
                        }elseif($content['msgType']=='210' && isset($content['list'][0]['doorState'])){
                            $a = $content['list'][0]['doorState'];
                            $str = str_split($a,1);
                            foreach ($str as $k=>$v) {
                            $s = $k+1;
                            db('boxlattices')->where(['devId'=>$content['devId'],'boxId'=>$s])->update(['doorState'=>$v]);
                            }
                        }else{
                            db('mqtt')->insert($data);
                            $data = db('useboxlog')->where('txnNo',$content['list'][0]['orderId'])->where('boxId',$content['list'][0]['boxId'])->field('id,type,boxId,devId,results,result')->find();
        					db('box')->where('case_id',$data['devId'])->update(['type'=>'10','updatetime'=>time()]);
                            if ($content['list'][0]['operType']==1) {
                                # 存物
                                db('boxlattices')->where(['boxId'=>$data['boxId'],'devId'=>$data['devId']])->update(['use_status'=>'20']);
                                db('useboxlog')->where('txnNo',$content['list'][0]['orderId'])->where('boxId',$content['list'][0]['boxId'])->update(['result'=>1,'updatetime'=>time()]);
                            }else{
                                # 取物
                                db('useboxlog')->where('txnNo',$content['list'][0]['orderId'])->where('boxId',$content['list'][0]['boxId'])->update(['results'=>1,'updatetime'=>time()]);
                            }
                        }
                    }
                // }

                    break;
                    case '211':
                    # 数据上报响应...
                    break;
                    case '310':
                    # 远程控制发起...
                    // $msg ='{
                    //     "msgType":311,
                    //     "devId":"'.$content["devId"].'",
                    //     "result":1,
                    //     "txnNo":'.$content["txnNo"].'
                    // }';
                    // $this->pubs($msg);
                    break;
                    case '311':
                        db('mqtt')->insert($data);
                    # 远程控制响应...
                    $data = db('useboxlog')->where('txnNo',$content['txnNo'])->where('devId',$content['devId'])->field('id,type,boxId,devId,results,result')->find();
                    if ($content['result']==1) {
                        if ($data['result']==Null) {
                            # 存物
                          db('boxlattices')->where(['boxId'=>$data['boxId'],'devId'=>$data['devId']])->update(['use_status'=>'20']);
                          db('useboxlog')->where('txnNo',$content['txnNo'])->where('devId',$content['devId'])->update(['result'=>$content['result'],'updatetime'=>time()]);
                        }else{
                            # 取物
                          db('useboxlog')->where('txnNo',$content['txnNo'])->where('devId',$content['devId'])->update(['results'=>$content['result'],'updatetime'=>time()]);
                        }
                    }else{
                        if ($data['result']==Null) {
                            # 存物
                        //   db('boxlattices')->where(['boxId'=>$data['boxId'],'devId'=>$data['devId']])->update(['use_status'=>'20']);
                          db('useboxlog')->where('txnNo',$content['txnNo'])->where('devId',$content['devId'])->update(['result'=>$content['result'],'updatetime'=>time()]);
                        }else{
                            # 取物
                          db('useboxlog')->where('txnNo',$content['txnNo'])->where('devId',$content['devId'])->update(['results'=>$content['result'],'updatetime'=>time()]);
                        }
                    //    db('useboxlog')->where('txnNo',$content['txnNo'])->where('devId',$content['devId'])->update(['result'=>$content['result'],'updatetime'=>time()]);
                    }
                    break;
                    case '500':
                    # 状态查询请求...
                    if (isset($content['resultList'][0]['state'])) {
                        $a = $content['resultList'][0]['state'];
                        $b = [];$c = [];
                        while($a > 0) {
                        $t = $a % 10;
                        $a = intval($a / 10);
                        $b[] = $t;
                        if (!isset($c[$t])) {
                        $c[$t] = 0;
                        }
                        $c[$t]++;
                        }
                        $b = array_reverse($b);
                        foreach ($b as $k=>$v) {
                          $s = $k+1;
                          if (in_array($v,[3,5])) {
                            db('boxlattices')->where(['devId'=>$content['devId'],'boxId'=>$s])->update(['state'=>$v,'use_status'=>'20']);
                          }else{
                            db('boxlattices')->where(['devId'=>$content['devId'],'boxId'=>$s])->update(['state'=>$v]);
                          }
                        }
                    }elseif(isset($content['resultList'][0]['goodsState'])){
                        $a = $content['resultList'][0]['goodsState'];
                        $b = [];$c = [];
                        while($a > 0) {
                        $t = $a % 10;
                        $a = intval($a / 10);
                        $b[] = $t;
                        if (!isset($c[$t])) {
                        $c[$t] = 0;
                        }
                        $c[$t]++;
                        }
                        $b = array_reverse($b);
                        foreach ($b as $k=>$v) {
                          $s = $k+1;
                          if (in_array($v,[1])) {
                            db('boxlattices')->where(['devId'=>$content['devId'],'boxId'=>$s])->update(['goodsState'=>$v,'use_status'=>'20']);
                          }else{
                            db('boxlattices')->where(['devId'=>$content['devId'],'boxId'=>$s])->update(['goodsState'=>$v]);
                          }
                        }
                    }elseif(isset($content['resultList'][0]['doorState'])){
                        $a = $content['resultList'][0]['doorState'];
                        $b = [];$c = [];
                        while($a > 0) {
                        $t = $a % 10;
                        $a = intval($a / 10);
                        $b[] = $t;
                        if (!isset($c[$t])) {
                        $c[$t] = 0;
                        }
                        $c[$t]++;
                        }
                        $b = array_reverse($b);
                        foreach ($b as $k=>$v) {
                          $s = $k+1;
                          db('boxlattices')->where(['devId'=>$content['devId'],'boxId'=>$s])->update(['doorState'=>$v]);
                        }
                    }
                    break;
                default:
                    # code...
                    break;
            }
        }

        }
    }

    //mqtt发布
    public function pubs($msg='')
    {
        // 客户端id  可以用随机数
        $client = "tp5Mqtt".rand(1000, 9999);
        // mqtt主机 主机，请配置为自己的主机
        $host = "112.74.105.251";
        // mqtt端口
        $port = 1883;
        // 密钥 用于证书配置,如果需要ssl认证，则必须填写
        // $this->cert= 'ca.pem';
        // mqtt账号
        $username = "cgt";
        // mqtt密码
        $password = "2nywSbKLKmm23jkf";
        // 订阅主题 订阅的主题，注意使用的主题一定要是mqtt配置过的主题，比如百度天工需要策略认证过的
        // 自己学习的时候，可以随意自定义，一般跟发布主题一致便可以收到消息
        // 如要要接受所有主题，请使用#
        $topics_name = "topic_client";
        //引入phpMQTT 创建mqtt实例
        Loader::import('phpmqtt/phpMQTT', EXTEND_PATH);
        $mqtt = new \phpmqtt\phpMQTT($host, $port, $client);
        //发布，发布内容可自定义，这里写死
        if ($mqtt->connect(true, null, $username, $password)) {
            $mqtt->publish($topics_name, $msg, 0);
            $mqtt->close();
        } else {
            echo "Time out!\n";
        }
    }

    /**
     * 要使用命令行运行此方法！！！
     *  think5.0 运行方法为 在宝塔终端中，cd到Public 目录  然后用守户程序运行 nohup php index.php admin/index/sub &
     * 该类主要为订阅，建议订阅代码和发布代码不要写在同一个类中，避免修改造成不必要的误改。nohup php index.php admin/mqtt/sub > /dev/null 2> /dev/null &
     * 每次更新该类后需要重启mqtt订阅，否则新的改动不会生效。
     * 请在相应的位置放入phpMQTT的库
     * 库代码：https://github.com/bluerhinos/phpMQTT/blob/master/phpMQTT.php
     * 类库使用的时候注意命名空间，类名称命名要和thinkphp的保持一致，不然会报错
     */
    public function subs()
    {
        // 客户端id  可以用随机数
        $client = "tp5Mqtt".rand(1000, 9999);
        // mqtt主机 主机，请配置为自己的主机
        $host = "112.74.105.251";
        // mqtt端口
        $port = 1883;
        // 密钥 用于证书配置,如果需要ssl认证，则必须填写
        // $this->cert= 'ca.pem';
        // mqtt账号
        $username = "cgt";
        // mqtt密码
        $password = "2nywSbKLKmm23jkf";
        // 订阅主题 订阅的主题，注意使用的主题一定要是mqtt配置过的主题，比如百度天工需要策略认证过的
        // 自己学习的时候，可以随意自定义，一般跟发布主题一致便可以收到消息
        // 如要要接受所有主题，请使用#
        $topics_name = "topic_client";
        //引入phpMQTT 创建mqtt实例
        Loader::import('phpmqtt/phpMQTT', EXTEND_PATH);
        $mqtt = new \phpmqtt\phpMQTT($host, $port, $client);
        if (!$mqtt->connect(true, null, $username, $password)) {
            exit('error');   //连接失败
        } else {
            echo "success"; //连接成功
        }
        //topics["topic"]  为接受的主题名  需要和发送的主题名一致  否则会订阅不到
        //订阅信息 Qos为信息登记，需要和发送的等级一致
        $topics[$topics_name] = array("qos" => 0, "function" =>array($this,"onMessages"));
        $mqtt->subscribe($topics, 0);
        //死循环监听
        while ($mqtt->proc()) {
        }
        $mqtt->close();
    }

    /**
     * 在此处接MQtt的信息 进行业务处理
     * @param $topic
     * @param $msg
     */
    public function onMessages($topic, $msg)
    {
        $data['topicName'] = $topic;
        $data['content'] = $msg;
        //保存数据到数据库
        if ($data['content']!=null || $data['content']!='') {
            $content = json_decode($data['content'],true);
            // Log::error($data['content'].'2222');
            if (isset($content['msgType'])) {
            switch ($content['msgType']) {
                case '501':
                    # 柜子状态查询响应...
                    break;
                    case '110':
                    # 登录注册发起后响应...
                    break;
                    case '111':
                    # 登录注册响应...
                    break;
                    case '210':
                    # 数据上报发起...
                    break;
                    case '211':
                    # 数据上报响应...
                    break;
                    case '310':
                    # 远程控制发起...

                    break;
                    case '311':
                    # 远程控制响应...
                    db('mqtt')->insert($data);

                    db('box')->where('case_id',$content['devId'])->update(['type'=>'10','updatetime'=>time()]);
                    $data = db('useboxlog')->where('txnNo',$content['txnNo'])->where('devId',$content['devId'])->field('id,type,boxId,devId')->find();
                    if ($content['result']==1) {
                        if ($data['type']=='10') {
                            # 存物
                          db('boxlattices')->where(['boxId'=>$data['boxId'],'devId'=>$data['devId']])->update(['use_status'=>'20']);
                          db('useboxlog')->where('txnNo',$content['txnNo'])->where('devId',$content['devId'])->update(['result'=>$content['result'],'updatetime'=>time()]);
                        }else{
                            # 取物
                            db('useboxlog')->where('txnNo',$content['txnNo'])->where('devId',$content['devId'])->update(['result'=>$content['result'],'updatetime'=>time()]);
                        }
                    }else{
                       db('useboxlog')->where('txnNo',$content['txnNo'])->where('devId',$content['devId'])->update(['result'=>$content['result'],'updatetime'=>time()]);
                    }
                    break;
                    case '500':
                    # 状态查询请求...
                    break;
                default:
                    # code...
                    break;
            }
         }else{
            Log::error($data['content'].'3333');
         }
        }
    }


}

?>

