<?php
//------------------------------------------------------------------
// Features:  前端 ~ 行動裝制版的選單樣板
// File Name: header.tmpl.php
// Author:    Barkley, Fixed by Ian
// Related:
// Log:
// 2016.10.18
//------------------------------------------------------------------

//接收templ  id------------------------------------------------------
$template = $template_name;
//lib_menu
$mobile_menu = mobile_header_menu($template);
//會員頭像呼叫
$mobile_member_icon = mobile_header_menu($template);
//客製化menu
require dirname(dirname(__DIR__)).'/component/custom_menu.php';
//短網址
$ui["shorturl_m"]=menu_short_url('mobile');
//客製化theme
require dirname(dirname(__DIR__)).'/component/custom_theme.php';
template_themes('mobile');
//logo
$logo=($template=='admin')? '':'style="background:url(\''.$config['companylogo'].'\') no-repeat 0;width: 100px;min-height: 50px;background-size: 100px;"';
$admin_title=($template=='admin')? $tr['membercenter_menu_admin']:'';
//-------------------------------------------------------------------------------------------
//header集中管理
//-------------------------------------------------------------------------------------------
$menu_center=menu_features('mobile');
$mobile_signin=mobile_login();
$mobile_header=<<<HTML
<!-- header -->
<header id="header">
  <nav class="navbar navbar-dark">
   <div class="container"> 
    <!--会员中心移出--> 
    <div class="membercenter">
    <a href="{$config['website_baseurl']}{$mobile_signin}">
    {$mobile_member_icon['membericon']}
    </a>
    </div>
    <!--LOGO--> 
    <a class="navbar-brand mx-auto" href="{$config['website_baseurl']}"><div class="logo" {$logo}>{$admin_title}</div></a>
    
    <button class="navbar-toggler" type="button" onclick="open_main_menu()">
    <span class="navbar-toggler-icon"></span>
    </button>
    <!--網域--> 
    {$ui["shorturl_m"]}
   </div>
    <!--展開樣式--> 
    <div id="menu-block" onclick="close_main_menu()"></div>
    <div class="main-menu" id="main-menu">
     <div class="control">
       <button class="navbar-toggler" type="button" onclick="close_main_menu()">
          <i class="fas fa-chevron-right"></i>
        </button>     
     </div>

     <!--基本選單-->   
     <div class="container py-2">  
      <div class="menu-list">
      <!--遊戲目錄-->          
      {$menu_center}
      <!--回到桌機連結-->
      {$mobile_menu['mobile2destop']}
      <!--客製化menu-->
      {$ui['mobile_custom_menu']}
      <!--語言選單 -->
      {$mobile_menu['language_menu']}
      </div>
     </div>

    </div>
  </nav>
</header>
HTML;
/*
    {$mobile_menu['mobile_menu']}
    <!--按鈕樣式--> 
    <!--<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
    </button>-->
     <!--帳戶資訊
      {$mobile_menu['mobile_userinfo']}-->      
      <!--會員中心連結
      {$mobile_menu['mobile_membercenter']}-->
      <!--語言選單
      {$mobile_menu['language_menu']} -->
      <!--<div class="dropdown-divider m-0"></div>     
登入/登出按鈕
     <div class="container py-3 navlog">
        {$mobile_menu['login_ui_menu']}
     </div>-->
*/
$tmpl['extend_js'] .=<<<HTML
<script type="text/javascript">
  function open_main_menu(){
    $('#main-menu').animate({right:"0"});
    $('#menu-block').fadeIn();
  }
  function close_main_menu(){
    $('#main-menu').animate({right:"-70vw"});
    $('#menu-block').fadeOut();
  }
</script>
HTML;

$mobile_header_admin=<<<HTML
<!-- header -->
<header id="header">
  <nav class="navbar navbar-dark">
   <div class="container"> 
    <!--回首页--> 
    <div class="membercenter"><a href="{$config['website_baseurl']}home.php">
   <i class="fas fa-home"></i></a>
    </div>
    <!--LOGO--> 
    <a class="navbar-brand mx-auto" href="{$config['website_baseurl']}"><div class="logo" {$logo}>{$admin_title}</div></a>
    <!--按鈕樣式--> 
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
    </button>
    <!--網域--> 
    {$ui["shorturl_m"]}
   </div>
    <!--展開樣式--> 
    <div class="collapse navbar-collapse" id="navbarCollapse">
     <!--基本選單-->   
     <div class="container py-2">  
      <div class="navblock">
      <!--遊戲目錄-->          
      {$mobile_menu['mobile_menu']}
      <!--回到桌機連結-->
      {$mobile_menu['mobile2destop']}

      <!--客製化menu-->
      {$ui['mobile_custom_menu']}
</div>
     </div>
    </div>
  </nav>
</header>
HTML;

$mobile_header_login=<<<HTML
<!-- header -->
<header id="header">
  <nav class="navbar navbar-dark">
   <div class="container"> 
    <!--回首页--> 
    <div class="membercenter"><a href="{$config['website_baseurl']}home.php">
   <i class="fas fa-home"></i></a>
    </div>
    <!--LOGO--> 
    <a class="navbar-brand mx-auto" href="{$config['website_baseurl']}"><div class="logo" {$logo}>{$admin_title}</div></a>
    <!--按鈕樣式--> 
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
    </button>
    <!--網域--> 
    {$ui["shorturl_m"]}
   </div>
    <!--展開樣式--> 
    <div class="collapse navbar-collapse" id="navbarCollapse">
     <!--基本選單-->   
     <div class="container py-2">  
      <div class="navblock">
      <!--遊戲目錄-->          
      {$mobile_menu['mobile_menu']}
      <!--回到桌機連結-->
      {$mobile_menu['mobile2destop']}
      <!--客製化menu-->
      {$ui['mobile_custom_menu']}
</div>
     </div>
    </div>
  </nav>
</header>
HTML;

$mobile_footer=page_footer();

?>