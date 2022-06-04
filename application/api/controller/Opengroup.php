<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Activity;
use app\common\model\News;

/**
 * 团购接口
 */
class Opengroup extends Api
{
    protected $noNeedLogin = ['venue', 'sporttypelist', 'venuelist', 'addetails'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->activity = new Activity();
        $this->news = new News();

    }

    /**
     * 开团
     */
    public function opengroup()
    {

    }
}
