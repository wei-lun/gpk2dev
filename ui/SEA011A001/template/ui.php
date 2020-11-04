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
$tmpl['extend_js'] .="<script>
$(document).ready(function(){
  $('#main').css('margin-top',$('#header').height());
  // On Scroll
  $(window).on('scroll', function() {
    var wScroll = $(this).scrollTop();
    // Fixed nav
    wScroll > 144 ? $('#header .hBox').css('height','0px') : $('#header .hBox').css('height','72px')
  });

});
</script>";
//---------------------------------------------------------------------
// header集中管理
//---------------------------------------------------------------------
//Scroll_marquee(5000).
$headlogin='
  <div class="headbottom">
    <div class="headlogin">
      <div class="container clearfix">
        <div class="rform">
          <div class="formUl clearfix">
            <ul class="menu-admin-manage">'.menu_admin_management($template_name).'</ul>
          '.templ_header_login($template).
          '</div>
        </div>
      </div>
    </div>
  </div>';

$menu_for_member='';//樣式class name
if($template == 'admin')
{
	$menu_for_member='menu_for_member';//套用admin中間選單專用樣式(class name)
}
$menu_language_flag=menu_language_flag_ver();
//$menu_admin_management=menu_admin_management($template_name);
//{$menu_admin_management}
//客製化
$custom_top_bar =<<<HTML
<div id="custom-tb" class="row w-100">
  <div class="hb-col col-3">
    <div class="hb-row row">
      <div class="hb-tit col-4">品牌</div>
      <div class="col"><div id="license">牌照展示</div></div>
      <div class="col"><a href="{$config['website_baseurl']}aboutus.php">云顶简介</a></div>
    </div>
    <div class="hb-row row">
      <div class="hb-tit col-4">手机端</div>
      <div class="col"><a href="javascript:void(0);" onclick="msitechg('mobile'); return false;">手机投注</a></div>
      <div class="col"></div>
    </div>
  </div>

  <div class="hb-col col-3">
    <div class="hb-row row">
      <div class="hb-tit col-4">代理</div>
      <div class="col"><a href="{$config['website_baseurl']}partner.php">代理加盟</a></div>
      <div class="col"><a href="{$config['website_baseurl']}contactus.php">代理咨询</a></div>
    </div>
    <div class="hb-row row">
      <div class="hb-tit col-4">快速</div>
      <div class="col"><a href="{$config['website_baseurl']}howtodeposit.php">支付宝支付</a></div>
      <div class="col"><a href="{$config['website_baseurl']}howtodeposit.php">微信支付</a></div>
    </div>
  </div>

  <div class="hb-col col-3">
    <div class="hb-row row">
      <div class="hb-tit col-4">活动</div>
      <div class="col"><a href="{$config['website_baseurl']}promotions.php">最新优惠</a></div>
      <div class="col"></div>
    </div>
    <div class="hb-row row">
      <div class="hb-tit col-4">支付</div>
      <div class="col"><a href="{$config['website_baseurl']}howtodeposit.php">银联支付</a></div>
      <div class="col"><a href="{$config['website_baseurl']}howtodeposit.php">QQ钱包</a></div>
    </div>
  </div>

  <div class="hb-col col-3">
    <div class="hb-row row">
      <div class="hb-tit col-4">热门</div>
      <div class="col"><a href="{$config['website_baseurl']}gamelobby.php?mgc=game">电子游艺</a></div>
      <div class="col"><a href="{$config['website_baseurl']}gamelobby.php?mgc=Fishing">捕鱼达人</a></div>
    </div>
    <div class="hb-row row">
      <div class="hb-tit col-4">网站</div>
      <div class="col"><a href="{$config['website_baseurl']}register.php">免费开户</a></div>
      <div class="col"><a href="{$config['website_baseurl']}contactus.php">联系我们</a></div>
    </div>
  </div>

</div>
HTML;

//header本人
//menu_short_url()
//menu_admin_management($template_name)
$header_templ='
  <div id="header">
    <div class="hInner">
      <div class="w1100 clearfix">
        <h1 class="logo"><a href="'.$config['website_baseurl'].'">
              <img src="'.$cdn_url_logo.'" alt="LOGO"/>'.$membercenter.'
              </a></h1>
        <ul id="gNavi" class="nav clearfix '.$menu_for_member.'">
             '// 中間主功能選單// 左側選單(語言切換列)
            .templ_header_mainmenu($template).$ui['custom_menu'].'
        </ul>
        '.menu_language_flag_ver().'
      </div>
    </div>
    <div class="hSection">
      <div class="hBox">
        <div class="container hInfo clearfix">
        '.$custom_top_bar.'        
        </div>
      </div>
    </div>
    '.$headlogin.'
  </div>';



//---------------------------------------------------------------------
// footer集中管理
//---------------------------------------------------------------------
$custon_footer=<<<HTML
    <div class="row mt-4">
      <div class="f_custom">

        <div class="d-flex f_morelink">
          <div class="f_tit">网站链接</div>
          <ul class="f_menu">
          {$ui['footer_link']}
          </ul>
        </div>

        <div class="f_help">
          <div class="f_tit">新手帮助</div>
          <ul class="f_menu">
            <li><a href="{$config['website_baseurl']}aboutus.php">关于我们</a></li>
            <li><a href="{$config['website_baseurl']}howtodeposit.php">如何存款</a></li>
            <li><a href="{$config['website_baseurl']}howtowithdraw.php">如何取款</a></li>
            <li><a href="{$config['website_baseurl']}contactus.php">联络我们</a></li>
          </ul>
        </div>

        <div class="f_contact">
          <div class="f_tit">联系我们</div>
          <div class=""><img src="{$cdnfullurl}img/common/f_img01.png" alt=""></div>
          <div class="row my-3">
            <div class="col-auto">国际热线：+60 11-4003-58888</div>
            <div class="col">投诉邮箱：kf@777445.com</div>
          </div>
          <div class="row my-3">
            <div class="col">客服QQ：800154</div>
            <div class="col">代理QQ：400093</div>
          </div>
          
        </div>

        <div class="f_browser">
          <div class="f_tit">浏览器推荐</div>
          <div class="row mx-0 my-3">
            <div class="col">
              <div class="row"><img src="{$cdnfullurl}img/common/f_img02.png" alt=""></div>
              <div class="row">寰宇</div>
            </div>
            <div class="col">
              <div class="row"><img src="{$cdnfullurl}img/common/f_img03.png" alt=""></div>
              <div class="row">遨游</div>
            </div>
            <div class="col">
              <div class="row"><img src="{$cdnfullurl}img/common/f_img04.png" alt=""></div>
              <div class="row">火狐</div>
            </div>
          </div>

          <div class="row mx-0 my-4">
            <div class="col">
              <div class="row"><img src="{$cdnfullurl}img/common/f_img05.png" alt=""></div>
              <div class="row">谷歌</div>
            </div>
            <div class="col">
              <div class="row"><img src="{$cdnfullurl}img/common/f_img06.png" alt=""></div>
              <div class="row">百度</div>
            </div>
            <div class="col">
              <div class="row"><img src="{$cdnfullurl}img/common/f_img07.png" alt=""></div>
              <div class="row">搜狗</div>
            </div>
          </div>

        </div>
        

      </div>
    </div>

    <div class="d-flex justify-content-center w-100 f_paylogo">{$ui['footer_casino_logo']}{$ui['footer_payment_logo']}</div>
HTML;
//footer本人
//<div class="d-flex justify-content-center f_parlogo">''</div>  
$footer_templ='
			<footer id="footer" class="footer">        
      <div class="w1000">
        '.$custon_footer.'  
        <div id="copyright" class="row justify-content-center text-center mt-5 mb-3">COPYRIGHT © 云顶国际 RESERVED  美東時間 - '.menu_time().'</div>
        <div class="f_nav w1000">            
          '// 頁腳顯示
          .page_footer().'            
        </div>

      </div>
      
      </footer>
';

?>