<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Log;
use think\Loader;
use think\Validate;


/**
 * 首页接口
 */
class Mqtt extends Api
{
    protected $noNeedLogin = ['text'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();

    }

    //mqtt发布

    /**
     * $type 10=注册/登录请求;20=存物;30=取物；40=锁箱
     */
    public function pub()
    {
        $param = $this->request->param();
        if (!$param["devId"]) {
            $this->error('请输入设备ID');
        }
        if (!in_array($param["type"], ['10', '20', '30', '40', '50', '60'])) {
            $this->error('操作类型错误');
        }
        $type = db('box')->where('case_id', $param['devId'])->where('venue_id', $this->auth->venue_id)->value('type');
        if ($type == '20') {
            $this->error('终端网络故障');
        }
        if ($param['type'] == '10') {
            $topics_name = "topic_server";
            # 登录/注册
            $msg = '{
						"msgType":110,
						"hardVersion":"1",
						"softVersion":"1",
						"devId":"' . $param["devId"] . '",
						"protoVersion":"V1",
						"txnNo":' . $this->getMillisecond() . '
					}';
        } elseif ($param['type'] == '20') {
            $topics_name = "topic_client";
            if (!$param['takeCode']) {
                $this->error('请输入取件码');
            }
            if (!Validate::regex($param['takeCode'], "^[_0-9a-z]{6,16}$")) {
                $this->error(__('密码至少6个纯数字'));
            }
            $boxId = db('boxlattices')->where(['devId' => $param['devId'], 'use_status' => '10', 'is_use' => '20'])->orderRaw('rand()')->value('boxId');
            $boxdata = db('useboxlog')->where(['devId' => $param['devId'], 'user_id' => $this->auth->id, 'takeCode' => $param['takeCode'], 'result' => '1'])->order('createtime DESC')->field('id,boxId,type,results')->find();
            if ($boxdata['results'] == Null && $boxdata) {
                $this->error('请选择' . $boxdata['boxId'] . '号箱格取物', $boxdata['boxId']);
            }
            if (!$boxId) {
                $this->error('暂无可用箱格', $param['devId']);
            }
            # 存物
            $orderId = $this->getMillisecond();
            $msg = '{
						"msgType":310,
					  "operType":100,
						"devId":"' . $param["devId"] . '",
						"boxId":' . $boxId . ',
						"bizData": {
						"orderId":' . $orderId . ',
						"bizType":0,
						"mobile":"' . $param['mobile'] . '",
						"takeCode":' . $param['takeCode'] . '
						},
						"txnNo":' . $orderId . '
					}';
            $add = db('useboxlog')->insertGetId(['mobile' => $param['mobile'], 'takeCode' => $param['takeCode'], 'boxId' => $boxId, 'devId' => $param['devId'], 'type' => '10', 'txnNo' => $orderId, 'createtime' => time(), 'user_id' => $this->auth->id]);
        } elseif ($param['type'] == '30') {
            $topics_name = "topic_client";
            if (!$param['boxId']) {
                $this->error('请输入要开箱的编号');
            }
            # 取物
            if (!$param['takeCode']) {
                $this->error('请输入取件码');
            }
            $takeCode = db('useboxlog')->where(['boxId' => $param['boxId'], 'devId' => $param['devId'], 'mobile' => $param['mobile'], 'takeCode' => $param['takeCode']])->value('takeCode');
            if ($param['takeCode'] !== $takeCode) {
                $this->error('取件码错误');
            }
            $orderId = db('useboxlog')->where(['boxId' => $param['boxId'], 'devId' => $param['devId'], 'mobile' => $param['mobile'], 'takeCode' => $param['takeCode']])->value('txnNo');
            $msg = '{
						"msgType":310,
					  "operType":100,
						"devId":"' . $param["devId"] . '",
						"boxId":' . $param['boxId'] . ',
						"bizData": {
						"orderId":' . $orderId . ',
						"bizType":1,
						"mobile":"' . $param['mobile'] . '",
						"takeCode":' . $param['takeCode'] . '
						},
						"txnNo":' . $orderId . '
					}';
            $add = db('useboxlog')->where(['mobile' => $param['mobile'], 'takeCode' => $param['takeCode'], 'boxId' => $param['boxId'], 'devId' => $param['devId'], 'txnNo' => $orderId])->value('id');
        } elseif ($param['type'] == '40') {
            $topics_name = "topic_client";
            if (!$param['boxId']) {
                $this->error('请输入要开箱的编号');
            }
            # 中途取物
            if (!$param['takeCode']) {
                $this->error('请输入取件码');
            }
            $takeCode = db('useboxlog')->where(['boxId' => $param['boxId'], 'devId' => $param['devId'], 'mobile' => $param['mobile'], 'takeCode' => $param['takeCode']])->value('takeCode');
            if ($param['takeCode'] !== $takeCode) {
                $this->error('取件码错误');
            }
            $orderId = db('useboxlog')->where(['boxId' => $param['boxId'], 'devId' => $param['devId'], 'mobile' => $param['mobile'], 'takeCode' => $param['takeCode']])->value('txnNo');
            $msg = '{
						"msgType":310,
					  "operType":100,
						"devId":"' . $param["devId"] . '",
						"boxId":' . $param['boxId'] . ',
						"bizData": {
						"orderId":' . $orderId . ',
						"bizType":1,
						"mobile":"' . $param['mobile'] . '",
						"takeCode":' . $param['takeCode'] . '
						},
						"txnNo":' . $this->getMillisecond() . '
					}';
            $add = db('useboxlog')->insertGetId(['mobile' => $param['mobile'], 'takeCode' => $param['takeCode'], 'boxId' => $param['boxId'], 'devId' => $param['devId'], 'type' => '40', 'txnNo' => $this->getMillisecond(), 'createtime' => time(), 'user_id' => $this->auth->id]);
        } elseif ($param['type'] == '50') {
            $topics_name = "topic_client";
            # 锁箱
            $boxId = $param['boxId'];
            $msg = '{
						"msgType":310,
					  "operType":110,
						"devId":"' . $param["devId"] . '",
						"boxId":' . $boxId . ',
						"bizData": {
						"orderId":' . $this->getMillisecond() . ',
						"bizType":0,
						"mobile":"' . $param['mobile'] . '",
						"takeCode":' . $param['takeCode'] . '
						},
						"txnNo":' . $this->getMillisecond() . '
					}';
            $add = db('useboxlog')->insertGetId(['mobile' => $param['mobile'], 'takeCode' => $param['takeCode'], 'boxId' => $boxId, 'devId' => $param['devId'], 'txnNo' => $this->getMillisecond(), 'type' => '30', 'createtime' => time(), 'user_id' => $this->auth->id]);
        } else {
            $topics_name = "topic_client";
            # 远程锁定
            $txnNo = $this->getMillisecond();
            $msg = '{
						"msgType":310,
						"operType":110,
						"devId":"' . $param["devId"] . '",
						"boxId":"' . $param['boxId'] . '",
						"txnNo":' . $txnNo . '
						}';
            $add = db('useboxlog')->insertGetId(['devId' => $param['devId'], 'txnNo' => $txnNo, 'type' => '70', 'createtime' => time(), 'user_id' => $this->auth->id]);
        }
        // 客户端id  可以用随机数
        $client = "tp5Mqtt" . rand(1000, 9999);
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
        usleep(5000000);#延时5秒执行
        if ($param['type'] == '10') {
            $this->success('注册/登录成功', $param['devId']);
        } elseif ($param['type'] == '20') {
            $result = db('useboxlog')->where('id', $add)->value('result');
            if ($result == '1') {
                $this->success('远程存物成功', $boxId);
            } elseif ($result == '0') {
                $this->error('远程存物失败', $boxId);
            } else {
                usleep(5000000);#延时10秒执行
                $result = db('useboxlog')->where('id', $add)->value('result');
                if ($result == '1') {
                    $this->success('远程存物成功', $boxId);
                } elseif ($result == '0') {
                    $this->error('远程存物失败', $boxId);
                } else {
                    usleep(5000000);#延时10秒执行
                    $result = db('useboxlog')->where('id', $add)->value('result');
                    if ($result == '1') {
                        $this->success('远程存物成功', $boxId);
                    } elseif ($result == '0') {
                        $this->error('远程存物失败', $boxId);
                    } else {
                        usleep(5000000);#延时10秒执行
                        $result = db('useboxlog')->where('id', $add)->value('result');
                        if ($result == '1') {
                            $this->success('远程存物成功', $boxId);
                        } elseif ($result == '0') {
                            $this->error('远程存物失败', $boxId);
                        } else {
                            usleep(5000000);#延时10秒执行
                            $result = db('useboxlog')->where('id', $add)->value('result');
                            if ($result == '1') {
                                $this->success('远程存物成功', $boxId);
                            } elseif ($result == '0') {
                                $this->error('远程存物失败', $boxId);
                            } else {
                                usleep(5000000);#延时10秒执行
                                $result = db('useboxlog')->where('id', $add)->value('result');
                                if ($result == '1') {
                                    $this->success('远程存物成功', $boxId);
                                } elseif ($result == '0') {
                                    $this->error('远程存物失败', $boxId);
                                } else {
                                    db('box')->where('case_id', $param['devId'])->where('venue_id', $this->auth->venue_id)->update(['type' => '20', 'updatetime' => time()]);
                                    $this->error('终端网络故障', $boxId);
                                }
                            }
                        }
                    }
                }
            }
        } elseif ($param['type'] == '30') {
            #取物
            Log::error($add);
            $result = db('useboxlog')->where('id', $add)->value('results');
            if ($result == '1') {
                db('boxlattices')->where(['boxId' => $param['boxId'], 'devId' => $param['devId']])->update(['use_status' => '10']);
                $this->success('远程取物成功', $param['boxId']);
            } elseif ($result == '0') {
                $this->error('远程取物失败', $param['boxId']);
            } else {
                usleep(5000000);#延时10秒执行
                $result = db('useboxlog')->where('id', $add)->value('results');
                if ($result == '1') {
                    db('boxlattices')->where(['boxId' => $param['boxId'], 'devId' => $param['devId']])->update(['use_status' => '10']);
                    $this->success('远程取物成功', $param['boxId']);
                } elseif ($result == '0') {
                    $this->error('远程取物失败', $param['boxId']);
                } else {
                    usleep(5000000);#延时10秒执行
                    $result = db('useboxlog')->where('id', $add)->value('results');
                    if ($result == '1') {
                        db('boxlattices')->where(['boxId' => $param['boxId'], 'devId' => $param['devId']])->update(['use_status' => '10']);
                        $this->success('远程取物成功', $param['boxId']);
                    } elseif ($result == '0') {
                        $this->error('远程取物失败', $param['boxId']);
                    } else {
                        usleep(5000000);#延时10秒执行
                        $result = db('useboxlog')->where('id', $add)->value('results');
                        if ($result == '1') {
                            db('boxlattices')->where(['boxId' => $param['boxId'], 'devId' => $param['devId']])->update(['use_status' => '10']);
                            $this->success('远程取物成功', $param['boxId']);
                        } elseif ($result == '0') {
                            $this->error('远程取物失败', $param['boxId']);
                        } else {
                            usleep(5000000);#延时10秒执行
                            $result = db('useboxlog')->where('id', $add)->value('results');
                            if ($result == '1') {
                                db('boxlattices')->where(['boxId' => $param['boxId'], 'devId' => $param['devId']])->update(['use_status' => '10']);
                                $this->success('远程取物成功', $param['boxId']);
                            } elseif ($result == '0') {
                                $this->error('远程取物失败', $param['boxId']);
                            } else {
                                usleep(5000000);#延时10秒执行
                                $result = db('useboxlog')->where('id', $add)->value('results');
                                if ($result == '1') {
                                    db('boxlattices')->where(['boxId' => $param['boxId'], 'devId' => $param['devId']])->update(['use_status' => '10']);
                                    $this->success('远程取物成功', $param['boxId']);
                                } elseif ($result == '0') {
                                    $this->error('远程取物失败', $param['boxId']);
                                } else {
                                    db('box')->where('case_id', $param['devId'])->where('venue_id', $this->auth->venue_id)->update(['type' => '20', 'updatetime' => time()]);
                                    $this->error('终端网络故障', $param['boxId']);
                                }
                            }
                        }
                    }
                }
            }
        } elseif ($param['type'] == '40') {
            # 中途取物
            $result = db('useboxlog')->where('id', $add)->value('result');
            if ($result) {
                $this->success('中途取物成功', $param['boxId']);
            } else {
                $this->error('中途取物失败', $param['boxId']);
            }
        } elseif ($param['type'] == '50') {
            $result = db('useboxlog')->where('id', $add)->value('result');
            if ($result) {
                $this->success('远程锁箱成功', $boxId);
            } else {
                $this->error('远程锁箱失败', $boxId);
            }
        } elseif ($param['type'] == '60') {
            $result = db('useboxlog')->where('id', $add)->value('result');
            if ($result) {
                $this->success('远程清箱成功', $param['devId']);
            } else {
                $this->error('远程清箱失败', $param['devId']);
            }
        }
    }

    //mqtt发布

    /**
     * $type 10=注册/登录请求;20=存物;30=取物；40=锁箱
     */
    public function pubs($devId, $type = '10')
    {
        if (!$devId) {
            $this->error('请输入设备ID');
        }
        if (!in_array($type, ['10', '20', '30', '40', '50'])) {
            $this->error('操作类型错误');
        }
        if ($type == '10') {

            $topics_name = "topic_server";
            # 登录/注册
            $msg = '{
							"msgType":110,
							"hardVersion":"1",
							"softVersion":"1",
							"devId":"' . $devId . '",
							"protoVersion":"V1",
							"txnNo":' . $this->getMillisecond() . '
						}';
        }
        // 客户端id  可以用随机数
        $client = "tp5Mqtt" . rand(1000, 9999);
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
    }

    /**
     * 获取箱子状态
     */
    public function getboxstate()
    {
        $devId = '1002';
        $type = '10';
        if (!$devId) {
            $this->error('请输入设备ID');
        }
        if (!in_array($type, ['10', '20', '30', '40', '50'])) {
            $this->error('操作类型错误');
        }
        $topics_name = "topic_server";
        $ids = db('useboxlog')->where(['devId' => $devId, 'type' => '50'])->value('id');
        if ($ids) {
            db('useboxlog')->where('id', $ids)->update(['result' => '0']);
            $add = $ids;
        } else {
            $add = db('useboxlog')->insertGetId(['devId' => $devId, 'txnNo' => $this->getMillisecond(), 'type' => '50', 'createtime' => time(), 'result' => '0']);
        }
        # 格口状态(箱子当前状态)
        if ($type == '10') {
            $msg = '{
							"msgType":210,
							"queryType":1,
							"devId":"' . $devId . '",
							"txnNo":' . $this->getMillisecond() . '
						}';
        } elseif ($type == '20') {
            # 格口状态(箱子物品状态)
            $msg = '{
							"msgType":210,
							"queryType":2,
							"devId":"' . $devId . '",
							"txnNo":' . $this->getMillisecond() . '
						}';
        } elseif ($type == '30') {
            # 格口状态(箱门状态)
            $msg = '{
							"msgType":210,
							"queryType":3,
							"devId":"' . $devId . '",
							"txnNo":' . $this->getMillisecond() . '
						}';
        }
        // 客户端id  可以用随机数
        $client = "tp5Mqtt" . rand(1000, 9999);
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
        usleep(6000000);#延时3秒执行
        $result = db('useboxlog')->where('id', $add)->value('result');
        if ($result) {
            return $result;
        } else {
            return $result;
        }
    }

    /**
     * 生成13位时间戳
     */
    private function getMillisecond()
    {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }

    public function getdevId()
    {
        $arr = db('box')->where(['venue_id' => $this->auth->venue_id])->field('case_id as devId,type')->find();
        if (!$arr['devId']) {
            if (!$this->request->request('devId')) {
                return json(['code' => '-1', 'msg' => '请输入设备ID']);
            }
            $arr['devId'] = $this->request->request('devId');
        }
        // $result = $this->getboxstate($arr['devId'],'10');
        if ($arr['type'] == '20') {
            $this->error('终端网络错误，不可操作');
        }
        if ($arr['devId']) {
            db('useboxlog')->where(['result' => 0, 'devId' => $arr['devId']])->delete();
            db('useboxlog')->where(['result' => 1, 'results' => 1, 'devId' => $arr['devId']])->delete();
            $arr['C'] = db('boxlattices')->where('devId', $arr['devId'])->where('boxType', 'C')->where('use_status', '10')->count() ?? 0;
            $arr['S'] = db('boxlattices')->where('devId', $arr['devId'])->where('boxType', 'S')->where('use_status', '10')->count() ?? 0;
            $arr['M'] = db('boxlattices')->where('devId', $arr['devId'])->where('boxType', 'M')->where('use_status', '10')->count() ?? 0;
            $arr['L'] = db('boxlattices')->where('devId', $arr['devId'])->where('boxType', 'L')->where('use_status', '10')->count() ?? 0;
            $arr['X'] = db('boxlattices')->where('devId', $arr['devId'])->where('boxType', 'X')->where('use_status', '10')->count() ?? 0;
        }
        $boxId = db('useboxlog')->where(['devId' => $arr['devId'], 'user_id' => $this->auth->id, 'result' => '1'])->order('createtime DESC')->field('id,boxId,type,results')->find();
        if ($boxId['results'] == Null) {
            $arr['boxId'] = $boxId['boxId'];
        } else {
            $arr['boxId'] = null;
        }
        $this->pubs($arr['devId'], '10');
        usleep(2000000);#延时3秒执行
        $this->success('ok', $arr);
    }

}
