<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use fast\Random;
use think\Validate;
use addons\qrcode\controller\Index as qrcode;

/**
 * 会员接口
 */
class User extends Api
{
    protected $noNeedLogin = ['login', 'mobilelogin', 'register', 'resetpwd', 'changeemail', 'changemobile', 'third'];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 会员中心
     */
    public function index()
    {
        $this->success('', ['welcome' => $this->auth->nickname]);
    }

    /**
     * 会员登录
     *
     * @param string $account 账号
     * @param string $password 密码
     */
    public function login()
    {
        $account = $this->request->request('account');
        $password = $this->request->request('password');
        if (!$account || !$password) {
            $this->error(__('Invalid parameters'));
        }
        $ret = $this->auth->login($account, $password);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 手机验证码登录
     *
     * @param string $mobile 手机号
     * @param string $captcha 验证码
     */
    public function mobilelogin()
    {
        $mobile = $this->request->request('mobile');
        // $captcha = $this->request->request('captcha');
        if (!$mobile) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        // if (!Sms::check($mobile, $captcha, 'mobilelogin')) {
        //     $this->error(__('Captcha is incorrect'));
        // }
        $user = \app\common\model\User::getByMobile($mobile);
        if ($user) {
            if ($user->status != 'normal') {
                $this->error(__('Account is locked'));
            }
            //如果已经有账号则直接登录
            $ret = $this->auth->direct($user->id);
        } else {
            $ret = $this->auth->register($mobile, Random::alnum(), '', $mobile, []);
        }
        if ($ret) {
            Sms::flush($mobile, 'mobilelogin');
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 注册会员
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $email 邮箱
     * @param string $mobile 手机号
     * @param string $code 验证码
     */
    public function register()
    {
        $username = $this->request->request('username');
        $password = $this->request->request('password');
        $email = $this->request->request('email');
        $mobile = $this->request->request('mobile');
        $code = $this->request->request('code');
        if (!$username || !$password) {
            $this->error(__('Invalid parameters'));
        }
        if ($email && !Validate::is($email, "email")) {
            $this->error(__('Email is incorrect'));
        }
        if ($mobile && !Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        $ret = Sms::check($mobile, $code, 'register');
        if (!$ret) {
            $this->error(__('Captcha is incorrect'));
        }
        $ret = $this->auth->register($username, $password, $email, $mobile, []);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Sign up successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        $this->auth->logout();
        $this->success(__('Logout successful'));
    }

    /**
     * 修改会员个人信息
     *
     * @param string $avatar 头像地址
     * @param string $username 用户名
     * @param string $nickname 昵称
     * @param string $bio 个人简介
     */
    public function profile()
    {
        $user = $this->auth->getUser();
        $username = $this->request->request('username');
        $nickname = $this->request->request('nickname');
        $bio = $this->request->request('bio');
        $avatar = $this->request->request('avatar', '', 'trim,strip_tags,htmlspecialchars');
        if ($username) {
            $exists = \app\common\model\User::where('username', $username)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error(__('Username already exists'));
            }
            $user->username = $username;
        }
        if ($nickname) {
            $exists = \app\common\model\User::where('nickname', $nickname)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error(__('Nickname already exists'));
            }
            $user->nickname = $nickname;
        }
        $user->bio = $bio;
        $user->avatar = $avatar;
        $user->save();
        $this->success();
    }

    /**
     * 修改邮箱
     *
     * @param string $email 邮箱
     * @param string $captcha 验证码
     */
    public function changeemail()
    {
        $user = $this->auth->getUser();
        $email = $this->request->post('email');
        $captcha = $this->request->request('captcha');
        if (!$email || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::is($email, "email")) {
            $this->error(__('Email is incorrect'));
        }
        if (\app\common\model\User::where('email', $email)->where('id', '<>', $user->id)->find()) {
            $this->error(__('Email already exists'));
        }
        $result = Ems::check($email, $captcha, 'changeemail');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $verification = $user->verification;
        $verification->email = 1;
        $user->verification = $verification;
        $user->email = $email;
        $user->save();

        Ems::flush($email, 'changeemail');
        $this->success();
    }

    /**
     * 修改手机号
     *
     * @param string $mobile 手机号
     * @param string $captcha 验证码
     */
    public function changemobile()
    {
        $user = $this->auth->getUser();
        $mobile = $this->request->request('mobile');
        $captcha = $this->request->request('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (\app\common\model\User::where('mobile', $mobile)->where('id', '<>', $user->id)->find()) {
            $this->error(__('Mobile already exists'));
        }
        $result = Sms::check($mobile, $captcha, 'changemobile');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $verification = $user->verification;
        $verification->mobile = 1;
        $user->verification = $verification;
        $user->mobile = $mobile;
        $user->save();

        Sms::flush($mobile, 'changemobile');
        $this->success();
    }

    /**
     * 第三方登录
     *
     * @param string $platform 平台名称
     * @param string $code Code码
     */
    public function third()
    {
        $url = url('user/index');
        $platform = $this->request->request("platform");
        $code = $this->request->request("code");
        $config = get_addon_config('third');
        if (!$config || !isset($config[$platform])) {
            $this->error(__('Invalid parameters'));
        }
        $app = new \addons\third\library\Application($config);
        //通过code换access_token和绑定会员
        $result = $app->{$platform}->getUserInfo(['code' => $code]);
        if ($result) {
            $loginret = \addons\third\library\Service::connect($platform, $result);
            if ($loginret) {
                $data = [
                    'userinfo' => $this->auth->getUserinfo(),
                    'thirdinfo' => $result
                ];
                $this->success(__('Logged in successful'), $data);
            }
        }
        $this->error(__('Operation failed'), $url);
    }

    /**
     * 重置密码
     *
     * @param string $mobile 手机号
     * @param string $newpassword 新密码
     * @param string $captcha 验证码
     */
    public function resetpwd()
    {
        $type = $this->request->request("type");
        $mobile = $this->request->request("mobile");
        $email = $this->request->request("email");
        $newpassword = $this->request->request("newpassword");
        $captcha = $this->request->request("captcha");
        if (!$newpassword || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if ($type == 'mobile') {
            if (!Validate::regex($mobile, "^1\d{10}$")) {
                $this->error(__('Mobile is incorrect'));
            }
            $user = \app\common\model\User::getByMobile($mobile);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Sms::check($mobile, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Sms::flush($mobile, 'resetpwd');
        } else {
            if (!Validate::is($email, "email")) {
                $this->error(__('Email is incorrect'));
            }
            $user = \app\common\model\User::getByEmail($email);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Ems::check($email, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Ems::flush($email, 'resetpwd');
        }
        //模拟一次登录
        $this->auth->direct($user->id);
        $ret = $this->auth->changepwd($newpassword, '', true);
        if ($ret) {
            $this->success(__('Reset password successful'));
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 我的课程
     */
    public function mycourse()
    {
        $param = $this->request->param();
        if (!$param['page'] || !$param['showpage']) {
            $this->error('参数不全');
        }
        $list['list'] = db('litestore_order')
            ->alias('o')
            ->join('litestore_order_goods g', 'o.id = g.order_id')
            ->where('o.order_type', '20')
            ->where('o.status', '<>', '10')
            //   ->where('o.status', '<>', '100')
            ->where(['o.user_id' => $this->auth->id, 'o.venue_id' => $this->auth->venue_id])
            ->field('o.id as order_id,g.goods_id,g.goods_name,g.images,g.goods_attr,o.status,o.createtime')
            ->limit($param['showpage'])
            ->page($param['page'])
            ->select();
        foreach ($list['list'] as &$v) {
            $v['images'] = cdnurl(explode(',', $v['images'])[0], true);
            $v['createtime'] = date('Y/m/d', $v['createtime']);
            if ($v['status'] == '10') {
                $v['status_text'] = '未支付';
            } elseif ($v['status'] == '20') {
                $v['status_text'] = '已支付';
            } elseif ($v['status'] == '30') {
                $v['status_text'] = '待核销';
            } elseif ($v['status'] == '40') {
                $v['status_text'] = '已核销';
            } elseif ($v['status'] == '50') {
                $v['status_text'] = '待收货';
            } elseif ($v['status'] == '60') {
                $v['status_text'] = '已收货';
            } elseif ($v['status'] == '70') {
                $v['status_text'] = '待评价';
            } elseif ($v['status'] == '80') {
                $v['status_text'] = '已评价';
            } elseif ($v['status'] == '90') {
                $v['status_text'] = '已完成';
            } elseif ($v['status'] == '100') {
                $v['status_text'] = '待成团';
            } else {
                $v['status_text'] = '拼团失败';
            }
        }
        $list['count'] = db('litestore_order')
            ->alias('o')
            ->join('litestore_order_goods g', 'o.id = g.order_id')
            ->where('o.order_type', '20')
            ->where('o.status', '<>', '10')
            //   ->where('o.status', '<>', '100')
            ->where(['o.user_id' => $this->auth->id, 'o.venue_id' => $this->auth->venue_id])
            ->count();
        $list['total_page'] = ceil($list['count'] / $param['showpage']);
        $this->success('ok', $list);
    }

    /**
     * 我的活动
     */
    public function myactivity()
    {
        $param = $this->request->param();
        if (!$param['page'] || !$param['showpage']) {
            $this->error('参数不全');
        }
        db('order')->where(['user_id' => $this->auth->id, 'order_type' => '40', 'pay_status' => '10'])->delete();
        $list['list'] = db('signup')
            ->alias('s')
            ->join('activity a', 'a.id = s.activity_id')
            ->join('order o', 's.id = o.order_ids')
            ->where(['s.user_id' => $this->auth->id, 'a.venue_id' => $this->auth->venue_id])
            //   ->where('o.order_type', '40')
            ->field('a.id as activity_id,a.name,a.images,s.createtime,s.status,o.id as order_id')
            ->limit($param['showpage'])
            ->page($param['page'])
            ->order('createtime DESC')
            ->group('a.id')
            ->select();
        foreach ($list['list'] as &$v) {
            $v['images'] = cdnurl(explode(',', $v['images'])[0], true);
            $v['createtime'] = date('Y/m/d', $v['createtime']);
            if ($v['status'] == '10') {
                $v['status_text'] = '待支付';
            } elseif ($v['status'] == '20') {
                $v['status_text'] = '已支付';
            } elseif ($v['status'] == '30') {
                $v['status_text'] = '支付失败';
            } elseif ($v['status'] == '40') {
                $v['status_text'] = '待成团';
            } else {
                $v['status_text'] = '成团失败';
            }
        }
        $list['count'] = db('signup')
            ->alias('s')
            ->join('activity a', 'a.id = s.activity_id')
            ->join('order o', 's.id = o.order_ids')
            //   ->where('o.order_type', '40')
            ->where(['s.user_id' => $this->auth->id, 'a.venue_id' => $this->auth->venue_id])
            ->group('a.id')
            ->count();
        $list['total_page'] = ceil($list['count'] / $param['showpage']);
        $this->success('ok', $list);
    }

    /**
     * 故障申报
     */
    public function myfaultlist()
    {
        $param = $this->request->param();
        if (!$param['page'] || !$param['showpage']) {
            $this->error('参数不全');
        }
        $list['list'] = db('faultdeclaration')
            ->alias('f')
            ->join('venue v', 'v.id = f.venue_id')
            ->where(['user_id' => $this->auth->id, 'venue_id' => $this->auth->venue_id])
            ->field('v.name as venue_name,f.createtime,f.remarks,f.status,f.images')
            ->limit($param['showpage'])
            ->page($param['page'])
            ->select();
        foreach ($list['list'] as &$v) {
            $v['createtime'] = date('Y-m-d H:i:s', $v['createtime']);
            $v['images'] = explode(',', $v['images']);
            foreach ($v['images'] as &$vv) {
                $vv = cdnurl($vv, true);
            }
        }
        $list['count'] = db('faultdeclaration')
            ->alias('f')
            ->join('venue v', 'v.id = f.venue_id')
            ->where(['user_id' => $this->auth->id, 'venue_id' => $this->auth->venue_id])
            ->count();
        $list['total_page'] = ceil($list['count'] / $param['showpage']);
        $this->success('ok', $list);
    }

    /**
     * 用户信息
     */
    public function userinfo()
    {
        $userinfo = $this->auth->getuserinfo();
        $this->success('ok', $userinfo);
    }

    /**
     * 我的求助记录
     */
    public function myhelplist()
    {
        $param = $this->request->param();
        if (!$param['page'] || !$param['showpage']) {
            $this->error('参数不全');
        }
        $list['list'] = db('seekhelp')
            ->alias('s')
            ->join('helptype h', 's.helptype_id = h.id')
            ->where(['s.user_id' => $this->auth->id, 's.venue_id' => $this->auth->venue_id])
            ->field('h.name,s.phone as mobile,s.reason,s.createtime')
            ->limit($param['showpage'])
            ->page($param['page'])
            ->select();
        foreach ($list['list'] as &$v) {
            $v['createtime'] = date('Y-m-d H:i:s', $v['createtime']);
        }
        $list['count'] = db('seekhelp')
            ->alias('s')
            ->join('helptype h', 's.helptype_id = h.id')
            ->where(['s.user_id' => $this->auth->id, 's.venue_id' => $this->auth->venue_id])
            ->count();
        $list['total_page'] = ceil($list['count'] / $param['showpage']);
        $this->success('ok', $list);
    }

    /**
     * 订单详情
     */
    public function orderdetails()
    {
        $order_id = $this->request->request('order_id');
        if (!$order_id) {
            $this->error('请输入总订单ID');
        }
        $qrcode = new qrcode();
        $user_id = $this->auth->id;
        $data['goodslist'] = db('litestore_order_goods')->where('order_id', $order_id)->where('user_id', $user_id)->field('order_id,goods_id,goods_name,images,total_num,goods_attr,goods_price')->find();
        $data['goodslist']['images'] = cdnurl(explode(',', $data['goodslist']['images'])[0], true);
        $order = db('litestore_order')->where('id', $order_id)->field('status,createtime,pay_status,captcha,business_id')->find();
        $data['goodslist']['total_shop_num'] = count($data['goodslist']);
        if ($order['status'] == '10') {
            $groupbuying_status = db('order')->where('order_ids', $order_id)->value('groupbuying_status');
            // 团购状态:10=非团购,20=待成团,30=已成团,40=拼团失败
            if ($groupbuying_status == '20') {
                $data['goodslist']['pay_status_text'] = '待成团';
            } elseif ($groupbuying_status == '30') {
                $data['goodslist']['pay_status_text'] = '已成团';
            } elseif ($groupbuying_status == '40') {
                $data['goodslist']['pay_status_text'] = '拼团失败';
            }
        } elseif ($order['status'] == '20') {
            $data['goodslist']['pay_status_text'] = '已支付';
        } elseif ($order['status'] == '30') {
            $data['goodslist']['pay_status_text'] = '待使用';
        } elseif ($order['status'] == '40') {
            $data['goodslist']['pay_status_text'] = '已使用';
        } elseif ($order['status'] == '50') {
            $data['goodslist']['pay_status_text'] = '待收货';
        } elseif ($order['status'] == '60') {
            $data['goodslist']['pay_status_text'] = '已收货';
        } elseif ($order['status'] == '70') {
            $data['goodslist']['pay_status_text'] = '待评价';
        } elseif ($order['status'] == '80') {
            $data['goodslist']['pay_status_text'] = '已评价';
        } elseif ($order['status'] == '90') {
            $data['goodslist']['pay_status_text'] = '已完成';
        }
        $data['goodslist']['goods_price'] = db('order')->where('order_ids', $data['goodslist']['order_id'])->value('pay_price');
        $order['createtime'] = date('Y-m-d H:i:s', $order['createtime']);
        $data['goodslist']['discount_price'] = db('order')->where('order_ids', $data['goodslist']['order_id'])->value('discount_price');
        $data['goodslist']['time'] = db('litestore_goods')->where('goods_id', $data['goodslist']['goods_id'])->value('end_time');
        if ($order['pay_status'] == '20') {
            if (strtotime($data['goodslist']['time']) > time() && $order['status'] == '20') {
                $order['captcha'] = str_replace('"', '', $order['captcha']);
                $data['goodslist']['qrcode'] = cdnurl($qrcode->builds($order['captcha']), true);
                $data['goodslist']['captch'] = $order['captcha'];
            } else {
                $order['captcha'] = '';
                $data['goodslist']['qrcode'] = cdnurl('/uploads/20220109/5d6f0cd018226449cd79a36903ca4b4e.png', true);
                $data['goodslist']['captch'] = $order['captcha'];
            }
        } else if ($order['status'] == '100' || $order['status'] == '110') {
            $order['captcha'] = '';
            $data['goodslist']['qrcode'] = cdnurl('/uploads/20220109/5d6f0cd018226449cd79a36903ca4b4e.png', true);
            $data['goodslist']['captch'] = $order['captcha'];
        }
        $this->success('ok', $data);
    }

    public function editavatar()
    {
        $avatar = $this->request->request('avatar');
        if (!$avatar) {
            $this->error('参数不全');
        }
        $update = db('user')->where('id', $this->auth->id)->update(['avatar' => $avatar]);
        $this->success('ok', $update);
    }

    public function editusername()
    {
        $username = $this->request->request('username');
        if (!$username) {
            $this->error('参数不全');
        }
        $update = db('user')->where('id', $this->auth->id)->update(['username' => $username, 'nickname' => $username]);
        $this->success('ok', $update);
    }

    public function editmobile()
    {
        $mobile = $this->request->request('mobile');
        if (!$mobile) {
            $this->error('参数不全');
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        $ids = db('user')->where('mobile', $mobile)->value('id');
        if ($ids) {
            $this->error('手机号已存在');
        }
        $update = db('user')->where('id', $this->auth->id)->update(['mobile' => $mobile]);
        $this->success('ok', $update);
    }

    public function edituserinfo()
    {
        $param = $this->request->param();
        if (!$param['avatar'] || !$param['username'] || !$param['mobile']) {
            $this->error('参数不全');
        }
        $user_id = $this->auth->id;
        $ids = db('user')->where('id', $user_id)->value('id');
        if ($ids !== $user_id && $ids) {
            $this->error('手机号已存在');
        }
        $param['updatetime'] = time();
        $param['nickname'] = $param['username'];
        $update = db('user')->where('id', $user_id)->update($param);
        $this->success('ok', $update);
    }
}
