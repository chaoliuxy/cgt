<?php
/**
 * 替换fckedit中的图片 添加域名
 * @param  string $content 要替换的内容
 * @param  string $strUrl 内容中图片要加的域名
 * @return string
 * @eg
 */
function replacePicUrl($content = null, $strUrl = null)
{
    if ($strUrl) {
        //提取图片路径的src的正则表达式 并把结果存入$matches中
        preg_match_all("/<img(.*)src=\"([^\"]+)\"[^>]+>/isU", $content, $matches);
        $img = "";
        if (!empty($matches)) {
            //注意，上面的正则表达式说明src的值是放在数组的第三个中
            $img = $matches[2];
        } else {
            $img = "";
        }
        if (!empty($img)) {
            $patterns = array();
            $replacements = array();
            foreach ($img as $imgItem) {
                $num = stripos($imgItem, 'https');
                if ($num===0) {
                    $final_imgUrl = $imgItem;
                    $replacements[] = $final_imgUrl;
                    $img_new = "/" . preg_replace("/\//i", "\/", $imgItem) . "/";
                    $patterns[] = $img_new;
                } else {
                    $final_imgUrl = $strUrl . $imgItem;
                    $replacements[] = $final_imgUrl;
                    $img_new = "/" . preg_replace("/\//i", "\/", $imgItem) . "/";
                    $patterns[] = $img_new;
                }
            }

            //让数组按照key来排序
            ksort($patterns);
            ksort($replacements);

            //替换内容
            $vote_content = preg_replace($patterns, $replacements, $content);
            $vote_content = preg_replace('#(<img.*?)width=\d;([^"]*?.*?>)#i', '$1width=100%;$2', $vote_content);
            return $vote_content;
        } else {
            return $content;
        }
    } else {
        return $content;
    }
}

  //获取星期方法
  function get_week($date){
    //强制转换日期格式
    // $date_str=date('Y-m-d',strtotime($date));
    //封装成数组
    $arr=explode("-", $date);
    //参数赋值
    //年
    $year=$arr[0];
    //月，输出2位整型，不够2位右对齐
    $month=sprintf('%02d',$arr[1]);
    //日，输出2位整型，不够2位右对齐
    $day=sprintf('%02d',$arr[2]);
    //时分秒默认赋值为0；
    $hour = $minute = $second = 0;
    //转换成时间戳
    $strap = mktime($hour,$minute,$second,$month,$day,$year);
    //获取数字型星期几
    $number_wk=date("w",$strap);
    //自定义星期数组
    $weekArr=array("周日","周一","周二","周三","周四","周五","周六");
    //获取数字对应的星期
    return $weekArr[$number_wk];
  }

  
 /*
    * 返回输入日期数组对应的星期和日期
    * @param $dateArray 需要的日期数组，如未来七天的日期
    * */
    function get_date($dateArray){
      $b=array();
      foreach($dateArray as $key=>$value){
          $b[]=array('id'=>$key,'date'=>$value);
      };
      foreach($b as $k=>$v){
          $b[$k]['week']= get_week($v['date']);
          $b[$k]['date']=$v['date'];
      }
      return $b;
}

function orderNo()
{
    return date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
}

/*
 * 生成劵码
 * $nums 生成多少个劵码
 * $codelength 劵码长度
 * $format 劵码前缀名(不包含在劵码长度内)
 * $type 返回类型 json array
 */
function get_code($nums = 1 ,$codelength = 6 ,$format = '' ,$type = 'json' )
{
    $mcode = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $mcode_len = strlen($mcode);
    $rs = array();
    for($i=0;$i<$nums;)
    {
        $code = '';
        for($j=0;$j<$codelength;$j++)
        {
            $str_len = rand(0,$mcode_len-1);
            $str = substr($mcode,$str_len,1);
            $code .=$str;
        }
        $d = in_array($code,$rs);
        if(!$d){
            $rs[] = $format.$code;
            $i++;
        }
    }
    if($type =='array')
    return $rs;
    else
    return json_encode($rs[0]);
}