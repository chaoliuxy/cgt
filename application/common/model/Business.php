<?php

namespace app\common\model;

use think\Model;
use addons\litestore\model\Wxlitestoregoods;

/**
 * 附近商家
 */
class Business extends Model
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
     * 商家列表
     * @param $venue_id 所属体育馆
     * @param $showpage 每页显示数量
     * @param $page 页数
     * @param $lat 维度
     * @param $lng 经度
     */
    public function list($venue_id = '', $showpage = '', $page = '', $lng = '', $lat = '', $is_recommend = '', $type = '10')
    {
        if ($is_recommend) {
            $list['list'] = db('business')
                ->where('venue_id', $venue_id)
                ->where('is_recommend', '20')
                ->where('type', $type)
                ->field('id,images,name,labeljson,score,address,round(sqrt( ( ((' . $lng . '-lng)*PI()*12656*cos(((' . $lat . '+lat)/2)*PI()/180)/180) * ((' . $lng . '-lng)*PI()*12656*cos (((' . $lat . '+lat)/2)*PI()/180)/180) ) + ( ((' . $lat . '-lat)*PI()*12656/180) * ((' . $lat . '-lat)*PI()*12656/180) ) )/2,2) as dis')
                ->limit($showpage)->page($page)->order('dis')->select();
            $list['count'] = db('business')->where('venue_id', $venue_id)->where('type', $type)->where('is_recommend', '20')->count();
        } else {
            $list['list'] = db('business')
                ->where('venue_id', $venue_id)
                ->where('type', $type)
                ->field('id,images,name,labeljson,score,address,round(sqrt( ( ((' . $lng . '-lng)*PI()*12656*cos(((' . $lat . '+lat)/2)*PI()/180)/180) * ((' . $lng . '-lng)*PI()*12656*cos (((' . $lat . '+lat)/2)*PI()/180)/180) ) + ( ((' . $lat . '-lat)*PI()*12656/180) * ((' . $lat . '-lat)*PI()*12656/180) ) )/2,2) as dis')
                ->limit($showpage)->page($page)->order('dis')->select();
            $list['count'] = db('business')->where('venue_id', $venue_id)->where('type', $type)->count();
        }
        foreach ($list['list'] as &$v) {
            $v['images'] = cdnurl(explode(',', $v['images'])[0], true);
            $v['labeljson'] = json_decode($v['labeljson'], true);
        }
        $list['total_page'] = ceil($list['count'] / $showpage);
        return json_encode($list, true);
    }

    /**
     * 商家详情
     * @param $business_id 商家ID
     * @param $lat 维度
     * @param $lng 经度
     */
    public function details($business_id = '', $lng = '', $lat = '')
    {
        $data = db('business')
            ->where('id', $business_id)
            ->field('id,images,name,labeljson,score,venue_id,starting_price,round(sqrt( ( ((' . $lng . '-lng)*PI()*12656*cos(((' . $lat . '+lat)/2)*PI()/180)/180) * ((' . $lng . '-lng)*PI()*12656*cos (((' . $lat . '+lat)/2)*PI()/180)/180) ) + ( ((' . $lat . '-lat)*PI()*12656/180) * ((' . $lat . '-lat)*PI()*12656/180) ) )/2,2) as dis')
            ->find();
        $data['images'] = explode(',', $data['images']);
        $data['image'] = cdnurl($data['images'][0], true);
        $data['labeljson'] = json_decode($data['labeljson'], true);
        foreach ($data['images'] as &$v) {
            $v = cdnurl($v, true);
        }
        $data['categorylist'] = db('litestore_category')->where(['venue_id' => $data['venue_id'], 'business_id' => $business_id])->field('id,name')->select();
        return json_encode($data, true);
    }

    /**
     * 商品列表
     * @param $litestorecategory_id 商品分类ID
     * @param $showpage 每页显示数量
     * @param $page 页数
     */

    public function goodslist($category_id, $showpage, $page)
    {
        $list['list'] = db('litestore_goods')
            ->alias('g')
            ->join('litestore_goods_spec s', 'g.goods_id =  s.goods_id');
        $list['count'] = db('litestore_goods')
            ->alias('g')
            ->join('litestore_goods_spec s', 'g.goods_id =  s.goods_id');
        $list['list'] = $list['list']
            ->where(['g.category_id' => $category_id, 'g.goods_status' => '10', 'g.is_delete' => '0'])
            ->field('g.goods_name,g.goods_id,g.images,s.goods_price,s.line_price,s.spec_sku_id,g.sales_actual,g.labeljson,g.spec_type')
            ->limit($showpage)
            ->page($page)
            ->group('g.goods_id')
            ->order('goods_sort DESC')
            ->select();
        $list['count'] = $list['count']
            ->where(['g.category_id' => $category_id, 'g.goods_status' => '10', 'g.is_delete' => '0'])
            ->group('g.goods_id')
            ->count();
        foreach ($list['list'] as &$v) {
            $v['images'] = cdnurl(explode(',', $v['images'])[0], true);
            $v['labeljson'] = json_decode($v['labeljson'], true);
        }
        $list['total_page'] = ceil($list['count'] / $showpage);
        return json_encode($list, true);
    }


    /**
     * 商品详情
     */
    public function goodsdetails($goods_id = '')
    {
        // 商品详情
        $detail = Wxlitestoregoods::detail($goods_id);
        $imgs = [];
        foreach (explode(",", $detail['images']) as $index => $item) {
            $imgs[] = cdnurl($item, true);
        }
        $detail['images'] = $imgs;
        if (!$detail || $detail['goods_status'] !== '10') {
            $this->error('很抱歉，商品不存在或已下架');
        }
        // 规格信息
        $specData = $detail['spec_type'] === '20' ? $detail->getManySpecData($detail['spec_rel'], $detail['spec']) : null;
        // 这里对规格的img格式化
        if ($specData != null) {
            foreach ($specData['spec_list'] as $index => $item) {
                $specData['spec_list'][$index]["form"]['imgshow'] = $specData['spec_list'][$index]["form"]['spec_image'] === '' ? null : cdnurl($specData['spec_list'][$index]["form"]['spec_image'], true);
            }
        }
        unset($detail['category_id']);
        unset($detail['content']);
        unset($detail['course_content']);
        unset($detail['star_time']);
        unset($detail['end_time']);
        unset($detail['reservation_id']);
        unset($detail['is_recommend']);
        unset($detail['labeljson']);
        $data['detail'] = $detail;
        $data['specData'] = $specData;
        return json_encode($data, true);
    }

}

