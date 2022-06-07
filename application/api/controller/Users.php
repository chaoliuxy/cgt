<?php
  
  namespace app\api\controller;
  
  use app\common\controller\Api;
  use addons\third\library\Service;
  use addons\third\model\Third;
  use app\common\library\Auth;
  use fast\Http;
  use weixin\wxBizDataCrypt;
  use think\Log;
  
  class Users extends Api
  
  {
    
    protected $noNeedLogin = ['login_hawk', 'Updata_user_hawk'];
    protected $token = '';
    
    public function _initialize()
    {
      $this->token = $this->request->post('token');
      if ($this->request->action() == 'login' && $this->token) {
        $this->request->post(['token' => '']);
      }
      parent::_initialize();
      $ucenter = get_addon_info('ucenter');
      if ($ucenter && $ucenter['state']) {
        include ADDON_PATH . 'ucenter' . DS . 'uc.php';
      }
    }
    
    public function Updata_user_hawk()
    {
      $encryptedData = $this->request->post('encryptedData');//加密的用户数据
      $iv = $this->request->post('iv');//与用户数据一同返回的初始向量
      $code = $this->request->post('code');
      if (!$this->token || !$code) {
        $this->error("参数不正确");
      }
      $this->auth->init($this->token);
      //检测是否登录
      if ($this->auth->isLogin()) {
        $user = $this->auth->getUser();
        $fields = [];
        if ($encryptedData) {
          try {
            $access_token = $this->getsessionkey($code);
            $pc = new WXBizDataCrypt(config('fastadmin.appid'), $access_token);
            $errCode = $pc->decryptData($encryptedData, $iv, $data);//解密手机号
            if ($errCode == 0) {
              $data = json_decode($data);
              $ids = db('user')->where('mobile', $data->phoneNumber)->value('id');
              if ($ids) {
                $this->error('该手机号已存在', $ids);
              }
              $fields['mobile'] = $data->phoneNumber;
            } else {
              $this->error('errCode', $errCode);
            }
          } catch (\Throwable $th) {
            //   Log::error($th);
          }
        }
        $user->save($fields);
        $this->success("已经登录", ['userInfo' => $this->auth->getUserinfo()]);
      } else {
        $this->error("未登录状态");
      }
    }
    
    public function login_hawk()
    {
      $code = $this->request->post("code");
      $avatarUrl = $this->request->post("avatarUrl");
      $nickName = $this->request->post("nickName");
      if (!$code || !$avatarUrl || !$nickName) {
        $this->error("参数不正确");
      }
      $params = [
        'appid' => config('fastadmin.appid'),
        'secret' => config('fastadmin.secret'),
        'js_code' => $code,
        'grant_type' => 'authorization_code'
      ];
      $result = Http::sendRequest("https://api.weixin.qq.com/sns/jscode2session", $params, 'GET');
      if ($result['ret']) {
        $json = (array)json_decode($result['msg'], true);
        if (isset($json['openid'])) {
          //如果有传Token
          if ($this->token) {
            $this->auth->init($this->token);
            //检测是否登录
            if ($this->auth->isLogin()) {
              $third = Third::where(['openid' => $json['openid'], 'platform' => 'wxapp'])->find();
              if ($third && $third['user_id'] == $this->auth->id) {
                //把最新的 session_key 保存到 第三方表的 access_token 里
                $third['access_token'] = $json['session_key'];
                $third->save();
                $this->success("登录成功", $this->Format_avatarUrl($this->auth->getUserinfo()));
              }
            }
          }
          $platform = 'wxapp';
          $result = [
            'openid' => $json['openid'],
            'userinfo' => [
              'nickname' => $nickName,
            ],
            'access_token' => $json['session_key'],
            'refresh_token' => '',
            'expires_in' => isset($json['expires_in']) ? $json['expires_in'] : 0,
          ];
          $extend = ['mobile' => 'NoLoginData', 'gender' => '0', 'nickname' => $nickName, 'avatar' => $avatarUrl];
          try {
            $ret = Service::connect($platform, $result, $extend);
          } catch (\Throwable $th) {
            $this->error($th->getMessage());
          }
          if ($ret) {
            Auth::instance();
            $this->success("登录成功", $this->Format_avatarUrl($this->auth->getUserinfo()));
          } else {
            $this->error("连接失败");
          }
        } else {
          $this->error("登录失败", $json);
        }
      }
    }
    
    private function startsWith($str, $prefix)
    {
      for ($i = 0; $i < strlen($prefix); ++$i) {
        if ($prefix[$i] !== $str[$i]) {
          return false;
        }
      }
      return true;
    }
    
    private function Format_avatarUrl($userInfo)
    {
      $avatar = $userInfo['avatar'];
      if ($this->startsWith($avatar, "/uploads/")) {
        $userInfo['avatar'] = cdnurl($avatar, true);
      }
      return ['userInfo' => $userInfo];
    }
    
    public function getTokenCurl($ispost = 0)
    {
      $url = 'https://api.weixin.qq.com/cgi-bin/token';//请求url
      $paramsArray = array(
        'appid' => config('site.appid'),//你的appid
        'secret' => config('site.appsecret'),//你的秘钥
        'grant_type' => 'client_credential'//微信授权类型,官方文档定义为 ： client_credential
      );
      $params = http_build_query($paramsArray);//生成URL参数字符串
      $httpInfo = array();
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
      curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36');
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
      curl_setopt($ch, CURLOPT_TIMEOUT, 30);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      if ($ispost) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_URL, $url);
      } else {
        if ($params) {
          curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
        } else {
          curl_setopt($ch, CURLOPT_URL, $url);
        }
      }
      $response = curl_exec($ch);
      if ($response === FALSE) {
        return false;
      }
      curl_getinfo($ch, CURLINFO_HTTP_CODE);
      array_merge($httpInfo, curl_getinfo($ch));
      curl_close($ch);
      return json_decode($response, true)['access_token'];
    }
    
    
    public function getsessionkey($code)
    {
      $params = [
        'appid' => config('fastadmin.appid'),
        'secret' => config('fastadmin.secret'),
        'js_code' => $code,
        'grant_type' => 'authorization_code'
      ];
      $result = Http::sendRequest("https://api.weixin.qq.com/sns/jscode2session", $params, 'GET');
      if ($result['ret']) {
        $json = (array)json_decode($result['msg'], true);
        return $json['session_key'];
      }
    }
    
  }

