<?php

namespace app\common\model;

use think\Model;
use addons\litestore\model\Wxlitestoregoods;

/**
 * 新闻资讯
 */
class Course extends Model
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
     * 课程列表
     */
    public function list($venue_id = '', $page = '', $showpage = '', $lat = '', $lng = '', $is_recommend = '', $key = '', $type = '')
    {
        $list['list'] = db('litestore_goods')
            ->alias('g')
            ->join('reservation r', 'g.reservation_id = r.id')
            ->join('litestore_goods_spec s', 'g.goods_id =  s.goods_id');

        $list['count'] = db('litestore_goods')
            ->alias('g')
            ->join('reservation r', 'g.reservation_id = r.id')
            ->join('litestore_goods_spec s', 'g.goods_id =  s.goods_id');

        if ($is_recommend) {
            $list['list'] = $list['list']->where('g.is_recommend', '20');
            $list['count'] = $list['count']->where('g.is_recommend', '20');
        }

        if ($key) {
            $list['list'] = $list['list']->where('g.goods_name|address|line_price|end_time|star_time', 'like', '%' . $key . '%');
            $list['count'] = $list['count']->where('g.goods_name|address|line_price|end_time|star_time', 'like', '%' . $key . '%');
        }
        if ($type) {
            # 开团的
            $list['list'] = $list['list']->where('g.pay_type', '10');
            $list['count'] = $list['count']->where('g.pay_type', '10');
        }

        $list['list'] = $list['list']
            ->where(['g.venue_id' => $venue_id, 'g.goods_status' => '10', 'g.is_delete' => '0', 'g.type' => '10'])
            ->field('g.goods_name,g.goods_id,g.images,g.star_time,g.end_time,r.address,r.lng,r.lat,s.goods_price,s.line_price,s.group_price,g.group_work_number,g.group_work_time,round(sqrt( ( ((' . $lng . '-lng)*PI()*12656*cos(((' . $lat . '+lat)/2)*PI()/180)/180) * ((' . $lng . '-lng)*PI()*12656*cos (((' . $lat . '+lat)/2)*PI()/180)/180) ) + ( ((' . $lat . '-lat)*PI()*12656/180) * ((' . $lat . '-lat)*PI()*12656/180) ) )/2,2) as dis')
            ->limit($showpage)
            ->page($page)
            ->order('dis')
            ->group('g.goods_id')
            ->select();

        $list['count'] = $list['count']
            ->where(['g.venue_id' => $venue_id, 'g.goods_status' => '10', 'g.is_delete' => '0', 'g.type' => '10'])
            ->count();

        foreach ($list['list'] as &$v) {
            $v['images'] = cdnurl(explode(',', $v['images'])[0], true);
            if (strtotime($v['star_time']) <= time()) {
                $v['status'] = '停止销售';
            } else {
                $v['status'] = '销售中';
            }
            $v['star_time'] = $v['star_time'] ? str_replace('-', '.', $v['star_time']) : '';
            $v['end_time'] = $v['end_time'] ? str_replace('-', '.', $v['end_time']) : '';
        }
        $list['total_page'] = ceil($list['count'] / $showpage);
        return json_encode($list, true);
    }

    /**
     * 商品详情
     */
    public function details($goods_id = '')
    {
        // 商品详情
        $detail = Wxlitestoregoods::detail($goods_id);
        $imgs = [];
        foreach (explode(",", $detail['images']) as $index => $item) {
            $imgs[] = cdnurl($item, true);
        }
        $detail['images'] = $imgs;
        if (!$detail || $detail['goods_status'] !== '10') {
            $this->error('很抱歉，课程不存在或已下架');
        }
        // 规格信息
        $specData = $detail['spec_type'] === '20' ? $detail->getManySpecData($detail['spec_rel'], $detail['spec']) : null;
        // 这里对规格的img格式化
        if ($specData != null) {
            foreach ($specData['spec_list'] as $index => $item) {
                $specData['spec_list'][$index]["form"]['imgshow'] = $specData['spec_list'][$index]["form"]['spec_image'] === '' ? null : cdnurl($specData['spec_list'][$index]["form"]['spec_image'], true);
            }
        }
        $detail['groupon'] = db('goods_groupon')
            ->alias('g')
            ->join('goods_groupon_log l', 'g.id = l.groupon_id')
            ->where('l.goods_id', $detail['goods_id'])
            ->where('g.status', 'ing')
            ->field('g.id,g.status,g.expiretime,g.num,g.current_num')->group('g.id')->order('g.createtime DESC')->select();
        $data['detail'] = $detail;
        $data['specData'] = $specData;
        $data['detail']['star_time'] = $data['detail']['star_time'] ? str_replace('-', '.', $data['detail']['star_time']) : '';
        $data['detail']['end_time'] = $data['detail']['end_time'] ? str_replace('-', '.', $data['detail']['end_time']) : '';
        return json_encode($data, true);
    }

}

