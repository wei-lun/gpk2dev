<?php
//接收templ  id------------------------------------------------------
$template = $template_name;

//常用變數-----------------------------------------------------------
//logo
if($template == 'admin' OR $template =='member')//這兩頁logo是人頭
  $cdn_url_logo       = $cdnfullurl.'img/common/logo_company.png';  
else
  $cdn_url_logo       = $config['companylogo'];

//標題文字
if($template == 'admin')
  $membercenter ="<span>".$tr['Member Centre']."</span>";
elseif($template == 'member')
  $membercenter ="<span>".$tr['Free account']."</span>";
else
  $membercenter="";

// -------------------------------------------------------------------
//引入component元件
// -------------------------------------------------------------------
//廣告元件
$tmpl['extend_head'] .= '<link type="text/css" rel="stylesheet" href="'.$cdnfullurl_js.'component/component.css">';
$tmpl['extend_head'] .= '<script src="'.$cdnfullurl_js.'component/component.js"></script>';
$tmpl['extend_js'] .= '<script>get_component("'.$ui_link.'");</script>';

//跑馬燈
require dirname(dirname(__DIR__)).'/component/marquee.php';
//客製化menu+footer
require dirname(dirname(__DIR__)).'/component/custom_menu.php';
//客製化theme
require dirname(dirname(__DIR__)).'/component/custom_theme.php';
template_themes('desktop');

//---------------------------------------------------------------------
// header集中管理
//---------------------------------------------------------------------
$header_templ='
<div id="header">  
    <div class="hLinkBox">
      <div  class="container d-flex ">
            <ul class="d-flex menu_top">
                      '// 左側選單(語言切換列)
                      .menu_language_choice().
                      // 美東時間
                      menu_time().menu_short_url().'                     
            </ul>
            <ul class="d-flex ml-auto menu_admin">
                      '// 會員選單
                        .menu_admin_management($template_name).'
            </ul> 
      </div>
    </div>
  <div  class="container">
    <div class="hSection">
      <div class="my-3 hBox">
        <h1 class="logo"><a class="d-flex align-items-center" href="'.$config['website_baseurl'].'">                 
                  <img src="'.$cdn_url_logo.'" alt="'.$config['companyShortName'].'" class="mr-2" style="max-width: 200px;max-height: 70px;">  
                  '.$membercenter.' '.$membercenter.'            
        </a></h1>
        <div class="headlogin">
          <ul>
                '// 會員登入的界面, 登入後顯示餘額及登出資訊
                .templ_header_login($template).'
          </ul>
        </div>
      </div>
    </div>

    <div class="hInner">    
      <ul id="gNavi" class="nav">
              '// 中間主功能選單
              .templ_header_mainmenu($template).$ui['custom_menu'].'
      </ul>
    </div>
  </div>
</div>';

//---------------------------------------------------------------------
// footer集中管理
//---------------------------------------------------------------------
//如果沒有內容不顯示
if( $ui['footer_payment_logo'] == '' ){
  $footerpaymentlogo = '';
}else{
  $footerpaymentlogo = '<div class="f_paylogo">'.$ui['footer_payment_logo'].'</div>';
}
//如果沒有內容不顯示
if( $ui['footer_casino_logo'] == '' ){
  $footercasinologo = '';
}else{
  $footercasinologo = '<div class="f_paylogo">'.$ui['footer_casino_logo'].'</div>';
}
$footer_templ='
      <footer id="footer" class="footer">
        '.$footerpaymentlogo.'
        '.$footercasinologo.'
          <div class="container">
            <div class="f_nav">             
                <div class="d-flex justify-content-center w-100 f_morelink">'.$ui['footer_link'].'</div>
              '// 頁腳顯示
              .page_footer().'
            </div>
          </div>
      </footer>
';

?>