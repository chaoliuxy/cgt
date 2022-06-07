<?php
  
  namespace app\admin\controller;
  
  use app\common\controller\Backend;
  use app\common\controller\Api;
  use addons\qrcode\controller\Index as qrcode;
  use think\Db;
  
  /**
   * 储物柜管理
   *
   * @icon fa fa-circle-o
   */
  class Box extends Backend
  {
    
    /**
     * Box模型对象
     * @var \app\admin\model\Box
     */
    protected $model = null;
    
    public function _initialize()
    {
      parent::_initialize();
      $this->model = new \app\admin\model\Box;
      
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
    public function index()
    {
      $this->relationSearch = true;
      //设置过滤方法
      $this->request->filter(['strip_tags', 'trim']);
      if ($this->request->isAjax()) {
        //如果发送的来源是Selectpage，则转发到Selectpage
        if ($this->request->request('keyField')) {
          return $this->selectpage();
        }
        list($where, $sort, $order, $offset, $limit) = $this->buildparams();
        if (session('venue_id')) {
          $list = $this->model
            ->with(['venue'])
            ->where($where)
            ->where('box.venue_id', session('venue_id'));
        } else {
          $list = $this->model
            ->with(['venue'])
            ->where($where);
        }
        $list = $list
          ->order($sort, $order)
          ->paginate($limit);
        foreach ($list as $row) {
          $row->getRelation('venue')->visible(['name']);
        }
        foreach ($list as &$v) {
          $v['reservation_id'] = db('reservation')->where('id', $v['reservation_id'])->value('name');
        }
        $result = array("total" => $list->total(), "rows" => $list->items());
        
        return json($result);
      }
      return $this->view->fetch();
    }
    
    /**
     * 添加
     */
    public function add()
    {
      if ($this->request->isPost()) {
        $params = $this->request->post("row/a");
        if ($params) {
          $params = $this->preExcludeFields($params);
          if (!isset($params['venue_id'])) {
            if (session('venue_id')) {
              $params['venue_id'] = session('venue_id');
            } else {
              $params['venue_id'] = 0;
            }
          }
          $ids = db('box')->where('case_id', $params['case_id'])->value('id');
          if ($ids) {
            $this->error('该设备ID已存在');
          }
          if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
            $params[$this->dataLimitField] = $this->auth->id;
          }
          $result = false;
          Db::startTrans();
          try {
            //是否采用模型验证
            if ($this->modelValidate) {
              $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
              $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
              $this->model->validateFailException(true)->validate($validate);
            }
            $result = $this->model->allowField(true)->save($params);
            Db::commit();
          } catch (ValidateException $e) {
            Db::rollback();
            $this->error($e->getMessage());
          } catch (PDOException $e) {
            Db::rollback();
            $this->error($e->getMessage());
          } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
          }
          if ($result !== false) {
            $this->success();
          } else {
            $this->error(__('No rows were inserted'));
          }
        }
        $this->error(__('Parameter %s can not be empty', ''));
      }
      $venue_id = session('venue_id');
      $this->view->assign('venue_id', $venue_id);
      return $this->view->fetch();
    }
    
    /**
     * 编辑
     */
    public function edit($ids = null)
    {
      $row = $this->model->get($ids);
      if (!$row) {
        $this->error(__('No Results were found'));
      }
      $adminIds = $this->getDataLimitAdminIds();
      if (is_array($adminIds)) {
        if (!in_array($row[$this->dataLimitField], $adminIds)) {
          $this->error(__('You have no permission'));
        }
      }
      if ($this->request->isPost()) {
        $params = $this->request->post("row/a");
        if ($params) {
          $params = $this->preExcludeFields($params);
          if (!isset($params['venue_id'])) {
            if (session('venue_id')) {
              $params['venue_id'] = session('venue_id');
            } else {
              $params['venue_id'] = 0;
            }
          }
          $id = db('box')->where('case_id', $params['case_id'])->value('id');
          if ($id != $ids) {
            $this->error('该设备ID已存在');
          }
          $result = false;
          Db::startTrans();
          try {
            //是否采用模型验证
            if ($this->modelValidate) {
              $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
              $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
              $row->validateFailException(true)->validate($validate);
            }
            $result = $row->allowField(true)->save($params);
            Db::commit();
          } catch (ValidateException $e) {
            Db::rollback();
            $this->error($e->getMessage());
          } catch (PDOException $e) {
            Db::rollback();
            $this->error($e->getMessage());
          } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
          }
          if ($result !== false) {
            $this->success();
          } else {
            $this->error(__('No rows were updated'));
          }
        }
        $this->error(__('Parameter %s can not be empty', ''));
      }
      $this->view->assign("row", $row);
      $venue_id = session('venue_id');
      $this->view->assign('venue_id', $venue_id);
      return $this->view->fetch();
    }
    
    /**
     * 生成小程序二维码
     */
    public function qrcode($ids = null)
    {
      $row = $this->model->get($ids);
      if (!$row['qrcode']) {
        // $path="/pages/storage/index";
        $box['id'] = db('box')->where('id', $ids)->value('case_id');
        $path = "/pages/storage/index?scene=" . $box['id'];
        // 宽度
        $postdata['width'] = 300;
        // 页面
        // $path = 1;
        // $postdata['scene']="nidaodaodao";
        $postdata['scene'] = $box['id'];
        $postdata['path'] = $path;
        $post_data = json_encode($postdata);
        $access_token = $this->getAccesstoken();
        $url = "https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token=" . $access_token;
        $result = $this->api_notice_increment($url, $post_data);
        $data = 'image/png;base64,' . base64_encode($result);
        // $this->success('ok',$data);
        $data = substr($data, 17);
        str_replace('data:image/jpg;base64,', '', $data);
        //  设置文件路径和文件前缀名称
        $path = ROOT_PATH . 'public/uploads/qrcode/';
        $prefix = 'nx_';
        $output_file = $prefix . time() . rand(100, 999) . '.jpg';
        $path = $path . $output_file;
        //  创建将数据流文件写入我们创建的文件内容中
        $ifp = fopen($path, "wb");
        fwrite($ifp, base64_decode($data));
        fclose($ifp);
        $this->model->where('id',$ids)->update(['qrcode'=>cdnurl('/uploads/qrcode/' . $output_file, true)]);
        $row = $this->model->get($ids);
        $this->view->assign("row", $row);
      }
      $this->view->assign("row", $row);
      return $this->view->fetch();
    }
    
    /**
     * 获取accesstoken
     */
    public function getAccesstoken()
    {
      $appid = config('fastadmin.appid');                     /*小程序appid*/
      $srcret = config('fastadmin.secret');      /*小程序秘钥*/
      $tokenUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appid . "&secret=" . $srcret;
      $getArr = array();
      $tokenArr = json_decode($this->send_post($tokenUrl, $getArr, "GET"));
      $access_token = $tokenArr->access_token;
      return $access_token;
    }
    
    public function send_post($url, $post_data, $method = 'POST')
    {
      $postdata = http_build_query($post_data);
      $options = array(
        'http' => array(
          'method' => $method, //or GET
          'header' => 'Content-type:application/x-www-form-urlencoded',
          'content' => $postdata,
          'timeout' => 15 * 60 // 超时时间（单位:s）
        )
      );
      $context = stream_context_create($options);
      $result = file_get_contents($url, false, $context);
      return $result;
    }
    
    public function api_notice_increment($url, $data)
    {
      $ch = curl_init();
      $header = array('Accept-Language:zh-CN', 'x-appkey:114816004000028', 'x-apsignature:933931F9124593865313864503D477035C0F6A0C551804320036A2A1C5DF38297C9A4D30BB1714EC53214BD92112FB31B4A6FAB466EEF245710CC83D840D410A7592D262B09D0A5D0FE3A2295A81F32D4C75EBD65FA846004A42248B096EDE2FEE84EDEBEBEC321C237D99483AB51235FCB900AD501C07A9CAD2F415C36DED82', 'x-apversion:1.0', 'Content-Type:application/x-www-form-urlencoded', 'Accept-Charset: utf-8', 'Accept:application/json', 'X-APFormat:json');
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
      curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
      curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $tmpInfo = curl_exec($ch);
      if (curl_errno($ch)) {
        return false;
      } else {
        return $tmpInfo;
      }
    }
  }
