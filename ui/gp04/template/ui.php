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
  
    <div class="hLinkBox clearfix">
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
      <div class="my-3 hBox clearfix">
        <h1 class="logo"><a href="'.$config['website_baseurl'].'">                 
                  <img src="'.$cdn_url_logo.'" style="max-width: 200px;max-height: 70px;" alt="LOGO"/>  
                  '.$membercenter.'
                  '.$membercenter.'             
        </a></h1>
        <div class="headlogin">
          <ul class="clearfix">
                '// 會員登入的界面, 登入後顯示餘額及登出資訊
                .templ_header_login($template).'
          </ul>
        </div>
      </div>
    </div>

    <div class="hInner clearfix">    
      <ul id="gNavi" class="nav clearfix">
              '// 中間主功能選單
              .templ_header_mainmenu($template).$ui['custom_menu'].'
      </ul>
    </div>
  </div>
</div>';

//---------------------------------------------------------------------
// footer集中管理
//---------------------------------------------------------------------
$footer_templ='
      <footer id="footer" class="footer">        
          <div class="container-fluid">
            <div class="row f_nav">
              <div class="w-100">  

              <div class="container">
                <div class="row services">
                    <div class="col-md-3">
                      <div class="service-content">
                        <div class="service-icon">
                          <img src="'.$cdnfullurl.'img/home/footer_tel.png" alt="Service">
                        </div>
                        <div class="service-info">
                          <h4><a href="#" title="Free Delivery">客服热线</a></h4>
                          <p>0800-888-8888</p>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="service-content">
                        <div class="service-icon">
                          <img src="'.$cdnfullurl.'img/home/footer_tel.png" alt="Service">
                        </div>
                        <div class="service-info">
                          <h4><a href="#" title="Support 24/7">客服信箱</a></h4>
                          <p>gpk@gmail.com</p>
                        </div>
                      </div>
                    </div>

                    <div class="col-md-3">
                      <div class="service-content">
                        <div class="service-icon">
                          <img src="'.$cdnfullurl.'img/home/footer_tel.png" alt="Service">
                        </div>
                        <div class="service-info">
                          <h4><a href="#" title="Free return">客服QQ</a></h4>
                          <p>9980888400</p>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="service-content">
                        <div class="service-icon">
                          <img src="'.$cdnfullurl.'img/home/footer_tel.png" alt="Service">
                        </div>
                        <div class="service-info">
                          <h4><a href="#" title="payment method">在线客服</a></h4>
                          <p>7×24小时</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="d-flex justify-content-center w-100 f_paylogo">'.$ui['footer_payment_logo'].'</div>
                 <div class="d-flex justify-content-center w-100 f_parlogo">'.$ui['footer_casino_logo'].'</div>
                <div class="d-flex justify-content-center w-100 f_morelink">'.$ui['footer_link'].'</div>
              '// 頁腳顯示
              .page_footer().'
              </div>
            </div>
          </div>
      </footer>
';

?>