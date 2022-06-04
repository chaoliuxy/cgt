<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Validate;

/**
 * 地址接口
 */
class Address extends Api
{
    protected $noNeedLogin = ['provincelist', 'citylist', 'regionlist'];
    protected $noNeedRight = ['*'];

    /**
     * 省列表
     */
    public function provincelist()
    {
        $list['province'] = db('area')->where('level', '1')->field('id as value,name as label')->select();
        foreach ($list['province'] as &$v) {
            $v['children'] = db('area')->where('pid', $v['value'])->field('id as value,name as label')->select();
            foreach ($v['children'] as &$vv) {
                $vv['children'] = db('area')->where('pid', $vv['value'])->field('id as value,name as label')->select();
            }
        }
        $this->success('ok', $list['province']);
    }

    /**
     * 市区列表
     */
    public function citylist()
    {
        $province_id = $this->request->post('province_id');
        if (!$province_id) {
            $this->error('参数不全');
        }
        $list = db('area')->where('pid', $province_id)->field('id,name')->select();
        $this->success('ok', $list);
    }

    /**
     * 区县列表
     */
    public function regionlist()
    {
        $city_id = $this->request->post('city_id');
        if (!$city_id) {
            $this->error('参数不全');
        }
        $list = db('area')->where('pid', $city_id)->field('id,name')->select();
        $this->success('ok', $list);
    }

    /**
     *添加收获地址
     */
    public function addaddressvalue()
    {
        $user = $this->auth->getUser();
        $data['user_id'] = $user->id;
        $data['name'] = $this->request->post('name');
        $data['phone'] = $this->request->post('phone');
        $data['province_id'] = $this->request->post('province_id');
        $data['city_id'] = $this->request->post('city_id');
        $data['region_id'] = $this->request->post('region_id');
        $data['detail'] = $this->request->post('detail');
        $data['isdefault'] = $this->request->post('isdefault');
        if (!$data['name'] || !$data['province_id'] || !$data['city_id'] || !$data['region_id'] || !$data['detail']) {
            $this->error('参数不全');
        }
        if (!in_array($data['isdefault'], [0, 1])) {
            $this->error('参数错误');
        }
        if ($data['phone'] && !Validate::regex($data['phone'], "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        $data['details'] = db('area')->where(['id' => $data['region_id']])->value('mergename') . ' ' . $data['detail'];
        $data['updatetime'] = time();
        $data['createtime'] = time();
        $add = db('litestore_adress')->insert($data);
        if ($add) {
            $this->success('添加成功', $add);
        } else {
            $this->error('添加失败', $add);
        }
    }

    /**
     * 地址详情
     */
    public function addressvalue()
    {
        $address_id = $this->request->post('address_id');
        if (!$address_id) {
            $this->error('参数不全');
        }
        $data = db('litestore_adress')->where('address_id', $address_id)->field('*')->find();
        $this->success('ok', $data);
    }

    /**
     * 修改单个收获地址
     */
    public function editaddressvalue()
    {
        $address_id = $this->request->post('address_id');
        if (!$address_id) {
            $this->error('参数不全');
        }
        $user = $this->auth->getUser();
        $data['user_id'] = $user->id;
        $data['name'] = $this->request->post('name');
        $data['phone'] = $this->request->post('phone');
        $data['province_id'] = $this->request->post('province_id');
        $data['city_id'] = $this->request->post('city_id');
        $data['region_id'] = $this->request->post('region_id');
        $data['detail'] = $this->request->post('detail');
        $data['isdefault'] = $this->request->post('isdefault');
        if (!$data['name'] || !$data['province_id'] || !$data['city_id'] || !$data['region_id'] || !$data['detail']) {
            $this->error('参数不全');
        }
        if (!in_array($data['isdefault'], [0, 1])) {
            $this->error('参数错误');
        }
        if ($data['phone'] && !Validate::regex($data['phone'], "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        $data['details'] = db('area')->where(['id' => $data['region_id']])->value('mergename') . ' ' . $data['detail'];
        $data['updatetime'] = time();
        $update = db('litestore_adress')->where('address_id', $address_id)->update($data);
        if ($update) {
            $this->success('修改成功', $update);
        } else {
            $this->error('修改失败', $update);
        }
    }

    /**
     * 删除单个收获地址
     */
    public function deladdress()
    {
        $address_id = $this->request->post('address_id');
        if (!$address_id) {
            $this->error('参数不全');
        }
        $del = db('litestore_adress')->where('address_id', $address_id)->delete();
        if ($del) {
            $this->success('删除成功', $del);
        } else {
            $this->error('删除失败', $del);
        }
    }

    /**
     * 我的收货地址列表
     */
    public function myaddresslist()
    {
        $user = $this->auth->getUser();
        $list = db('litestore_adress')->where('user_id', $user->id)->field('*')->select();
        if ($list) {
            foreach ($list as &$v) {
                $v['province_name'] = db('area')->where('id', $v['province_id'])->value('name');
                $v['city_name'] = db('area')->where('id', $v['city_id'])->value('name');
                $v['region_name'] = db('area')->where('id', $v['region_id'])->value('name');
            }
            unset($v);
        }
        $this->success('ok', $list);
    }

    /**
     * 设置默认地址
     */
    public function setdefault()
    {
        $address_id = $this->request->post('address_id');
        $user = $this->auth->getUser();
        if (!$address_id) {
            $this->error('参数不全');
        }
        $ids = db('litestore_adress')->where(['user_id' => $user->id])->field('address_id')->select();
        if ($ids) {
            foreach ($ids as &$v) {
                db('litestore_adress')->where('address_id', $v['address_id'])->setField('isdefault', '0');
            }
        }
        $update = db('litestore_adress')->where('address_id', $address_id)->setField('isdefault', '1');
        if ($update) {
            $this->success('设置成功', $update);
        } else {
            $this->error('设置失败', $update);
        }
    }
}
