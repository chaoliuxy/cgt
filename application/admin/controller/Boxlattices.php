<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Loader;
use think\Db;

/**
 * 箱格管理
 *
 * @icon fa fa-circle-o
 */
class Boxlattices extends Backend
{

    /**
     * Boxlattices模型对象
     * @var \app\admin\model\Boxlattices
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Boxlattices;
        $this->view->assign("boxtypeList", $this->model->getBoxtypeList());
        $this->view->assign("stateList", $this->model->getStateList());
        $this->view->assign("goodsstateList", $this->model->getGoodsstateList());
        $this->view->assign("doorstateList", $this->model->getDoorstateList());
        $this->view->assign("useStatusList", $this->model->getUseStatusList());
        $this->view->assign("isUseList", $this->model->getIsUseList());
    }

    public function import()
    {
        parent::import();
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 查看
     */
    public function index($ids='')
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $devId = db('box')->where('id', $ids)->value('case_id');
            if (session('venue_id')) {
                $list = $this->model
                ->where('devId', $devId)
                ->where($where)
                 ->where('venue_id', session('venue_id'));
            } else {
                $list = $this->model
                ->where('devId', $devId)
                ->where($where);
            }
            $list = $list
                ->order($sort, $order)
                ->paginate($limit);

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 清箱
     */
    public function clearbox($ids='')
    {
        $row = db('boxlattices')->where('id', $ids)->find();
        $topics_name = "topic_client";
        # 清箱
        $txnNo = $this->getMillisecond();
        $msg = '{
        "msgType":310,
        "operType":112,
        "devId":"'.$row["devId"].'",
        "boxId":"'.$row['boxId'].'",
        "txnNo":'.$txnNo.'
        }';
        $add = db('useboxlog')->insertGetId(['devId'=>$row['devId'],'txnNo'=>$txnNo,'type'=>'60','createtime'=>time()]);
        // 客户端id  可以用随机数
        $client = "tp5Mqtt".rand(1000, 9999);
        // mqtt主机 主机，请配置为自己的主机
        $host = "120.26.72.100";
        // mqtt端口
        $port = 1883;
        // 密钥 用于证书配置,如果需要ssl认证，则必须填写
        //        $this->cert= 'ca.pem';
        // mqtt账号
        $username = "cgt";
        // mqtt密码
        $password = "2nywSbKLKmm23jkf";
        // 订阅主题 订阅的主题，注意使用的主题一定要是mqtt配置过的主题，比如百度天工需要策略认证过的
        // 自己学习的时候，可以随意自定义，一般跟发布主题一致便可以收到消息
        // 如要要接受所有主题，请使用#
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
        $this->getboxstate($row['devId'], '10');
        $this->getboxstate($row['devId'], '20');
        $this->getboxstate($row['devId'], '30');
        usleep(2000000);#延时3秒执行
        $result = db('useboxlog')->where('id', $add)->value('result');
        if ($result) {
            db('boxlattices')->where('id', $ids)->update(['use_status'=>'10']);
            $this->success('远程清箱成功', '/KIldJsDvMC.php/boxlattices?ref=addtabs');
        } else {
            $this->error('远程清箱失败', '/KIldJsDvMC.php/boxlattices?ref=addtabs');
        }
    }

    /**
     * 获取箱子状态
     */
    public function getboxstate($devId, $type='10')
    {
        if (!$devId) {
            $this->error('请输入设备ID');
        }
        if (!in_array($type, ['10','20','30','40','50'])) {
            $this->error('操作类型错误');
        }
        $topics_name = "topic_server";
        $ids = db('useboxlog')->where(['devId'=>$devId,'type'=>'50'])->value('id');
        // if ($ids) {
        //     db('useboxlog')->where('id',$ids)->update(['result'=>'0']);
        //     $add = $ids;
        // }else{
        //     $add = db('useboxlog')->insertGetId(['devId'=>$devId,'txnNo'=>$this->getMillisecond(),'type'=>'50','createtime'=>time(),'result'=>'0']);
        // }
        # 格口状态(箱子当前状态)
        if ($type=='10') {
            $msg = '{
                        "msgType":500,
                        "queryType":1,
                        "devId":"'.$devId.'",
                        "txnNo":'.$this->getMillisecond().'
                    }';
        } elseif ($type=='20') {
            # 格口状态(箱子物品状态)
            $msg = '{
                        "msgType":500,
                        "queryType":2,
                        "devId":"'.$devId.'",
                        "txnNo":'.$this->getMillisecond().'
                    }';
        } elseif ($type=='30') {
            # 格口状态(箱门状态)
            $msg = '{
                        "msgType":500,
                        "queryType":3,
                        "devId":"'.$devId.'",
                        "txnNo":'.$this->getMillisecond().'
                    }';
        }
        // 客户端id  可以用随机数
        $client = "tp5Mqtt".rand(1000, 9999);
        // mqtt主机 主机，请配置为自己的主机
        $host = "120.26.72.100";
        // mqtt端口
        $port = 1883;
        // 密钥 用于证书配置,如果需要ssl认证，则必须填写
        //        $this->cert= 'ca.pem';
        // mqtt账号
        $username = "cgt";
        // mqtt密码
        $password = "2nywSbKLKmm23jkf";
        // 订阅主题 订阅的主题，注意使用的主题一定要是mqtt配置过的主题，比如百度天工需要策略认证过的
        // 自己学习的时候，可以随意自定义，一般跟发布主题一致便可以收到消息
        // 如要要接受所有主题，请使用#
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
        // usleep(6000000);#延时3秒执行
            // $result = db('useboxlog')->where('id',$add)->value('result');
            // if ($result) {
            //     return $result;
            // }else{
            //     return $result;
            // }
    }

    /**
     * 生成13位时间戳
     */
    private function getMillisecond()
    {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($t1)+floatval($t2))*1000);
    }
}
