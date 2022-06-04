<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Log;
use think\Db;
use think\Loader;


/**
 * 首页接口
 */
class Demoss extends Api
{
    protected $noNeedLogin = ['text'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();

    }


		// package com.mx.mqtt.sys;

		// import com.hivemq.client.mqtt.MqttGlobalPublishFilter;
		// import com.hivemq.client.mqtt.datatypes.MqttQos;
		// import com.hivemq.client.mqtt.lifecycle.MqttClientConnectedContext;
		// import com.hivemq.client.mqtt.lifecycle.MqttClientConnectedListener;
		// import com.hivemq.client.mqtt.lifecycle.MqttClientDisconnectedContext;
		// import com.hivemq.client.mqtt.lifecycle.MqttClientDisconnectedListener;
		// import com.hivemq.client.mqtt.mqtt5.Mqtt5AsyncClient;
		// import com.hivemq.client.mqtt.mqtt5.Mqtt5BlockingClient;
		// import com.hivemq.client.mqtt.mqtt5.Mqtt5Client;
		// import com.hivemq.client.mqtt.mqtt5.exceptions.Mqtt5ConnAckException;
		// import com.hivemq.client.mqtt.mqtt5.message.auth.Mqtt5SimpleAuth;
		// import com.hivemq.client.mqtt.mqtt5.message.connect.connack.Mqtt5ConnAck;
		// import com.mx.mqtt.jwt.JwtUtils;
		// import org.apache.logging.log4j.LogManager;
		// import org.apache.logging.log4j.Logger;

		// import java.io.UnsupportedEncodingException;

		/**
		 * emqx - Session
		 *
		 * @Ahthor luohq
		 * @Date 2020-04-09
		 */
		public class EmqxOfflineClient {

				/**
				 * 日志
				 */
				private static final Logger logger = LogManager.getLogger(EmqxOfflineClient.class);


				private static final String MQTT_JWT_SECRET = "xxxx";
				private static final String MQTT_SERVER_HOST = "192.168.xxx.xxx";
				private static final Integer MQTT_SERVER_PORT = 1883;
				private static final String MQTT_CLIENT_ID = "luohq-offline";
				public static final String MQTT_SUB_TOPIC = "luohq/offline";
				public static final Long SESSION_EXPIRATION = 5 * 60L;


				private static Boolean isSessionPresent = false;
				private static Mqtt5BlockingClient client;
				private static Mqtt5AsyncClient asyncClient;


				public static void main(String[] args) {
						/** 构建mqtt客户端 */
						buildMqtt5Client();


						/** 若session不存在，则需要再订阅主题 */
						if (!isSessionPresent) {
								logger.info("【CLIENT-SUB】订阅主题：" + MQTT_SUB_TOPIC);
								//订阅主题
								asyncClient.subscribeWith()
												.topicFilter(MQTT_SUB_TOPIC)
												.qos(MqttQos.EXACTLY_ONCE)
												.send();
						}

				}


				public static Mqtt5BlockingClient buildMqtt5Client() {
						/** blocking客户端 */
						client = Mqtt5Client.builder()
										.identifier(MQTT_CLIENT_ID)
										.serverHost(MQTT_SERVER_HOST)
										.serverPort(MQTT_SERVER_PORT)
										.addConnectedListener(new MqttClientConnectedListener() {
												@Override
												public void onConnected(MqttClientConnectedContext context) {
														logger.info("mqtt onConnected context");
												}
										})
										.addDisconnectedListener(new MqttClientDisconnectedListener() {
												@Override
												public void onDisconnected(MqttClientDisconnectedContext context) {
														logger.info("mqtt onDisconnected context");
												}
										})
										//自动重连（指数级延迟重连（起始延迟1s，之后每次2倍，到2分钟封顶） delay : 1s-> 2s -> 4s -> ... -> 2min）
										.automaticReconnectWithDefaultConfig()
										.buildBlocking();
						asyncClient = client.toAsync();


						/** Emqx JWT认证 */
						String authJwt = JwtUtils.generateJwt(MQTT_CLIENT_ID, MQTT_JWT_SECRET);
						Mqtt5SimpleAuth auth = Mqtt5SimpleAuth.builder()
										.username(MQTT_CLIENT_ID)
										.password(authJwt.getBytes())
										.build();
						Mqtt5ConnAck connAck = null;




						/** 全局消息处理（放在connect之前） */
						asyncClient.publishes(MqttGlobalPublishFilter.ALL, mqtt5Publish -> {
								try {
										byte[] msg = mqtt5Publish.getPayloadAsBytes();
										String msgStr = new String(mqtt5Publish.getPayloadAsBytes(), "UTF-8");
										logger.info("【CLIENT-RECV】" + msgStr);
								} catch (UnsupportedEncodingException e) {
										e.printStackTrace();
								}
						});


						/** 连接逻辑 */
						try {
								connAck = client.connectWith()
												.simpleAuth(auth)
												/** cleanSession=false */
												.cleanStart(false)
												/** session 7天过期 */
												.sessionExpiryInterval(SESSION_EXPIRATION)
												/** keepalive 时长*/
												//.keepAlive(60)
												.send();
						} catch (Mqtt5ConnAckException e) {
								e.printStackTrace();
								connAck = e.getMqttMessage();
						}


						/** 连接(普通无密码连接) */
						//Mqtt5ConnAck connAck = client.connect();

						/** 检查之前是否已存在session */
						isSessionPresent = connAck.isSessionPresent();
						if (connAck.isSessionPresent()) {
								logger.info("session is present: " + connAck.getSessionExpiryInterval().orElse(-1));
						}



						logger.info(connAck.getReasonCode() + ":" + connAck.getReasonString() + ":" + connAck.getResponseInformation());


						if (connAck.getReasonCode().isError()) {
								logger.error("Mqtt5连接失败！");
								System.exit(-1);
						}
						return client;
				}
		}

}
