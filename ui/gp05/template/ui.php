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
//引入元件
// -------------------------------------------------------------------
//廣告元件
$tmpl['extend_head'] .= '<link type="text/css" rel="stylesheet" href="'.$cdnfullurl_js.'component/component.css">';
$tmpl['extend_head'] .= '<script src="'.$cdnfullurl_js.'component/component.js"></script>';
$tmpl['extend_js'] .= '<script>get_component("'.$ui_link.'");</script>';

//跑馬燈
require dirname(dirname(__DIR__)).'/component/marquee.php';
//語系選擇-國旗ver
require dirname(dirname(__DIR__)).'/component/menu_language_flag_ver.php';
//客製化menu+footer
require dirname(dirname(__DIR__)).'/component/custom_menu.php';
//客製化theme
require dirname(dirname(__DIR__)).'/component/custom_theme.php';
template_themes('desktop');
//---------------------------------------------------------------------
// header集中管理
//---------------------------------------------------------------------
if($template != 'home')//樣板home 沒有header登入系統
{	
	$headlogin='
    <div class="headbottom">
      <div class="headlogin">
        <div class="container clearfix">
          <div class="rform">
            <ul class="formUl clearfix">
            '// 跑馬燈
            .Scroll_marquee(5000).templ_header_login($template).
            '</ul>
          </div>
        </div>
      </div>
    </div>';
}
else{
	$headlogin='';
}

$menu_for_member='';//樣式class name
if($template == 'admin')
{
	$menu_for_member='menu_for_member';//套用admin中間選單專用樣式(class name)
}
//header本人
$header_templ='
  <div id="header">
    <div class="hSection">
      <div class="hBox">
        <div class="hInfo clearfix">
          <ul class="menu_top">

                    '// 左側選單(語言切換列)
                    .menu_language_flag_ver().
                    // 美東時間
                    menu_time().menu_short_url().'
                      
          </ul>
          <ul class="menu_admin">
                    '// 會員選單
                     .menu_admin_management($template_name).'
                    
          </ul>          
        </div>
      </div>
    </div>
    <div class="hInner">
      <div class="container clearfix">
        <h1 class="logo"><a href="'.$config['website_baseurl'].'">
              <img src="'.$cdn_url_logo.'" alt="LOGO"/>'.$membercenter.'
              </a></h1>
        <ul id="gNavi" class="nav clearfix '.$menu_for_member.'">
             '// 中間主功能選單
            .templ_header_mainmenu($template).$ui['custom_menu'].'
        </ul>
      </div>
    </div>
    '.$headlogin.'
  </div>';



//---------------------------------------------------------------------
// footer集中管理
//---------------------------------------------------------------------
//footer本人
$footer_templ='
			<footer id="footer" class="footer">
      <div class="d-flex justify-content-center w-100 f_paylogo">'.$ui['footer_payment_logo'].'</div>
      <div class="d-flex justify-content-center w-100 f_morelink">'.$ui['footer_link'].'</div>
				<div class="container-fluid">
					<div class="row f_nav">
						<div class="col-12">              
							<div class="d-flex justify-content-center f_parlogo">'.$ui['footer_casino_logo'].'</div>
						'// 頁腳顯示
						.page_footer().'
						</div>
					</div>
				</div>
			</footer>
';

?>