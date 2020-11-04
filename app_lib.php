<?php
// ----------------------------------------
// 程式功能：app.php lib
// 檔案名稱：app_lib.php
// Author: 	Neil
//------------------------------------------

function get_page_link($to, $urlcode)
{
  global $config;

  $link = "https://".$config['website_domainname']."/".$to."?".http_build_query($urlcode);

  return $link;
}

function is_wechat()
{
  $result = false;

  if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false){
    $result = true;
  }

  return $result;
}

function get_open_os()
{
  $flag = 1;

  if(strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false ) {
    $flag = 2;
  }

  return $flag;
}

function get_tips_msg_html($browser_img_path)
{
  $html =
  '<div class="float_div">
    <div class="right"><img src="'.$cdnrooturl.'arrow.png" class="arrow"></div>
    <div class="center" style="">
      <h4>
      请点击右上角 <img src="'.$cdnrooturl.'menu.png" class="float_div_icon"> 开启选单 <br>
      选择 <img src="'.$browser_img_path.'" class="float_div_icon"> 开启
      </h4>
    </div>
  </div>';

  return $html;
}
