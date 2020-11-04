<?php
//------------------------------------------------------------------
// Features:  前端 ~ 行动装制版的选单样板
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
//客制化menu
require dirname(dirname(__DIR__)).'/component/custom_menu.php';
//短网址
$ui["shorturl_m"]=menu_short_url('mobile');
//客制化theme
require dirname(dirname(__DIR__)).'/component/custom_theme.php';
template_themes('mobile');
//logo
$logo='style="background:url(\''.$config['companylogo'].'\') no-repeat 0;width: 100px;min-height: 50px;background-size: 100px;"';
//跑馬燈
require dirname(dirname(__DIR__)).'/component/marquee.php';
$ui['Scroll_marquee'] = Scroll_marquee();
// 全民代理開關
$allagentsetting = $protalsetting['national_agent_isopen']??'off';
//-------------------------------------------------------------------------------------------
//header集中管理
//-------------------------------------------------------------------------------------------
$menu_center=menu_features('mobile');
$login_info = login_data();
$login_data = $login_info['data'];
$logout_function = member_logout_html();
$menu_after_login = '';
//var_dump($login_data);
/*<!--会员中心移出-->
    <div class="membercenter d-block w-25">
    <a href="{$config['website_baseurl']}menu_admin.php">
    {$mobile_menu['membericon']}
    </a>
    </div>*/
if($login_info['status'] == true){
  if($login_data['is_gtoken_lock']==true){
    $run_win_js = "
  var wait_text = '<img height=\"20\" width=\"20\" src=\"".$cdnrooturl."spinner.gif\">';
  var finish_text = '<span class=\"mdi mdi-18px mdi-coin gtokenrecycling_balance\" aria-hidden=\"true\"></span>';
  var csrftoken = '".$csrftoken."';
  $('.gtokenrecycling_status').html(wait_text);
  $.get('gamelobby_action.php',
      { a: 'Retrieve_Casino_balance', csrftoken: csrftoken },
      function(result){
        if(result.logger){
          $(\".reload_balance\").html(result.gtoken_b_m);
        }
        setTimeout(function(){ window.location.reload(\"wallets.php\"); }, 15000);
        $('.gtokenrecycling_status').html(finish_text);
        window.location.reload();
        // console.log(result);
      }, 'JSON'
  );
  ";

  // 确认要取回所有娱乐城的余额？
  $confirm_text = $tr['confirm get all casino back'];
  $gtokenrecycling_js = "
    <script>
      $(document).ready(function() {
        $('.gtokenrecycling_balance').click(function(){
          var gtokenrecycling = 1;

          if(confirm('".$confirm_text."')){
            $('.gtokenrecycling_balance').attr('disabled', 'disabled');
            ".$run_win_js."
          }else{
            //放弃,取回所有娱乐城的余额!!
            alert('".$tr['giveup get all casino back']."');
          }

        });

      });
    </script>
  ";
  $getback_token=<<<HTML
  <div class="mt-2 balance-area-getback text-center">
      <a href="#" class="gtokenrecycling_balance btn btn-primary btn-sm" title="{$tr['confirm get all casino back']}"><span class="gtokenrecycling_status">取回游戏币</span></a>
  </div>
HTML;
    $tmpl['extend_js'] .= $gtokenrecycling_js;
    $token_locked='token_locked';
  }else{
    $token_locked='';
    $getback_token='';
  }

/*$account_info =<<<HTML
<div class="sidermenu-balance">
  <div class="text-bar account w-100 text-center rounded-pill p-2 mt-3 mb-1">{$login_data['account']}</div>
  <div class="balance-area position-relative">
      <div class="text-bar balance w-100 text-center rounded-pill p-2 {$token_locked}">\${$login_data['balance']['all']}</div>
      <span onclick="reload_sidermenu_balance()" class="glyphicon glyphicon-refresh position-absolute" aria-hidden="true"></span>
  </div>
  {$getback_token}
</div>
HTML;*/
$account_info =<<<HTML
<div class="sidermenu-balance">
  <div class="text-bar account w-100 text-center rounded-pill p-2 mt-3 mb-1">{$login_data['account']}</div>
  <div class="balance-area position-relative">
        <div id="balance_row"><div id="submit_reload_balance" onclick="reload_balance()">
          <div id="reload_balance_area" class="reload_balance" data-toggle="tooltip_balance" data-placement="bottom" data-original-title="点击立即更新目前余额,游戏币{$login_data['balance']['all']}"><span class="badge badge-success">\${$login_data['balance']['all']}<span class="glyphicon glyphicon-refresh ml-2" aria-hidden="true"></span></span></div>
        </div>
        <div class="reload_balance_icon"></div>
      </div>
  </div>
  {$getback_token}
</div>
HTML;
/*
if($function_title == $tr['membercenter_menu_admin']){
$account_info =<<<HTML
<div class="sidermenu-balance">
  <div class="text-bar account w-100 text-center rounded-pill p-2 mt-3 mb-1">{$login_data['account']}</div>
</div>
HTML;
}*/
$logout_html =<<<HTML
<a href="#" onclick="member_logout()"><div class="icon"><i class="fas fa-sign-out-alt"></i></div>登出</a>
HTML;
$tmpl['extend_js'] .= $logout_function;
/*<a href="{$config['website_baseurl']}member_receivemoney.php"><div class="icon"><i class="fas fa-hand-holding-usd"></i></div>彩金领取{$receivemoney_count}</a>*/
$stationmail = stationmail_member_messages_count($_SESSION['member']->account);
$stationmail_count = ($stationmail['messages_count'] == 0)? '':'('.$stationmail['messages_count'].')';
/*$receivemoney = receivemoney_messages($_SESSION['member']->account);
$receivemoney_count = ($receivemoney['messages_count'] == 0)? '':'('.$receivemoney['messages_count'].')';*/
$user_account = password_hash($_SESSION['member']->account, PASSWORD_DEFAULT);
$menu_after_login=<<<HTML
  <a href="{$config['website_baseurl']}stationmail.php"><div class="icon"><i class="fas fa-envelope"></i></div>{$tr['membercenter_stationmail']}{$stationmail_count}</a>
  <a href="{$config['website_baseurl']}member_changepwd.php"><div class="icon"><i class="fas fa-key"></i></div>{$tr['Change Password']}</a>
  <a href="{$config['website_baseurl']}member_withdrawalpwd.php"><div class="icon"><i class="fas fa-key"></i></div>{$tr['membercenter_member_withdrawalpwd']}</a>
  <a href="{$config['website_baseurl']}mo_translog_query.php?transpage=m&id={$_SESSION['member']->id}"><div class="icon"><i class="fas fa-history"></i></div>{$tr['membercenter_mo_transaction_log']}</a>
  <a href="{$config['website_baseurl']}moa_betlog.php?betpage=m&id={$_SESSION['member']->id}"><div class="icon"><i class="fas fa-history"></i></div>{$tr['membercenter_betrecord']}</a>
HTML;

$header_menu_after_login=<<<HTML
  <a class="btn btn-primary btn-sm" href="{$config['website_baseurl']}wallets.php" role="button">　{$tr['membercenter_wallets']}　</a>
  <a class="btn btn-primary btn-sm head-btn-sec" href="{$config['website_baseurl']}deposit.php" role="button">　{$tr['membercenter_deposit']}　</a>
HTML;

if ($_SESSION['member']->therole === 'A') {
    $menu_after_login .= <<<HTML
        <a class="hot" href="{$config['website_baseurl']}spread_register.php">
            <div class="icon">
                <i class="fas fa-user-friends"></i>
            </div>
            {$tr['siderbar_spread_register']}
        </a>
        <a href="{$config['website_baseurl']}menu_admin.php?gid=agent">
            <div class="icon">
                <i class="fas fa-chess-knight"></i>
            </div>
            {$tr['agencyarea title']}
        </a>
    HTML;
} else {
    // 全民代理為開啟狀態
    if ($allagentsetting === 'on') {
        $menu_after_login .= <<<HTML
            <a class="hot" href="{$config['website_baseurl']}allagent_register.php">
                <div class="icon">
                    <i class="fas fa-chess-knight"></i>
                </div>{$tr['membercenter_allagent_register']}！
            </a>
        HTML;
    } else if ( isset($protalsetting['agent_register_switch']) && ($protalsetting['agent_register_switch'] === 'on') ) {
        $menu_after_login .=<<<HTML
            <a href="{$config['website_baseurl']}register_agent.php">
                <div class="icon">
                    <i class="fas fa-chess-knight"></i>
                </div>{$tr['membercenter_register_agent']}
            </a>
        HTML;
    }
}

}else{
  $account_info =<<<HTML
  <div class="text-center mt-4 mb-2">
    <a class="btn btn-primary mb-1" href="{$config['website_baseurl']}login2page.php" role="button">　{$tr['Login']}　</a>
    <a class="btn btn-primary head-btn-sec mb-1" href="{$config['website_baseurl']}register.php" role="button">{$tr['Free account']}</a>
  </div>
HTML;
$logout_html='';
$header_menu_after_login=<<<HTML
<a class="btn btn-primary btn-sm" href="{$config['website_baseurl']}login2page.php" role="button">　{$tr['Login']}　</a>
<a class="btn btn-primary btn-sm head-btn-sec" href="{$config['website_baseurl']}register.php" role="button">{$tr['Free account']}</a>
HTML;
}
$ann_link ='';
if($ui['Scroll_marquee']!=''){
  $ann_link ='<a href="#" data-toggle="modal" data-target="#announcementModal"><div class="icon"><i class="fas fa-bullhorn"></i></div>'.$tr['latest announcenment'].'</a>';
}
$mobile_header=<<<HTML
<!-- header -->
<header id="header">
  <nav class="navbar">
   <div class="container">
    <!--LOGO-->
    <a class="navbar-brand" href="{$config['website_baseurl']}"><div class="customlogo" {$logo}></div></a>
    <div class="d-block w-50 text-right">
      <button class="navbar-toggler mt-2" type="button" onclick="open_main_menu()">
      <i class="fas fa-bars"></i>
      </button>
      <div class="text-right mt-1 mb-2">
        {$header_menu_after_login}
      </div>
    </div>
    <!--网域-->
    {$ui["shorturl_m"]}
   </div>
    <!--展开样式-->
    <div id="menu-block" onclick="close_main_menu()"></div>
    <div class="main-menu" id="main-menu">
       <div class="control">
          <div class="sidermenu-toggler">
            <button class="navbar-toggler" type="button" onclick="close_main_menu()">
              <i class="fas fa-chevron-right"></i>
            </button>
          </div>
          <div class="sidermenu-icon my-3">
            {$mobile_menu['membericon']}
          </div>
          {$account_info}
     </div>

     <!--基本选单-->
     <div class="container p-0">
      <div class="menu-list">
      <a href="{$config['website_baseurl']}" class="nbox">
        <div class="icon"><i class="fa fa-home"></i></div>
        <span class="title">{$tr['home']}</span>
      </a>
      {$ann_link}
      {$menu_after_login}
      <a href="{$config['website_baseurl']}contactus.php"><div class="icon"><i class="fas fa-comment-dots"></i></div>{$tr['online customer service']}</a>
      <!--客制化menu-->
      {$ui['mobile_custom_menu']}
      <!--语言选单 -->
      {$mobile_menu['language_menu']}
      <!--登出-->
      {$logout_html}
      <!--回到桌机连结-->
      {$mobile_menu['mobile2destop']}
      </div>
     </div>

    </div>
  </nav>
</header>
HTML;
/*
    <!--游戏目录-->
      {$menu_center}
    {$mobile_menu['mobile_menu']}
    <!--按钮样式-->
    <!--<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
    </button>-->
     <!--帐户资讯
      {$mobile_menu['mobile_userinfo']}-->
      <!--会员中心连结
      {$mobile_menu['mobile_membercenter']}-->
      <!--语言选单
      {$mobile_menu['language_menu']} -->
      <!--<div class="dropdown-divider m-0"></div>
登入/登出按钮
     <div class="container py-3 navlog">
        {$mobile_menu['login_ui_menu']}
     </div>-->
*/
     /*
$reload_balance_js="
    function reload_sidermenu_balance(){
      $('.sidermenu-balance .balance').text(\"{$tr['now loading']}...\");
      var send_reload_balance  = 'true';
      var csrftoken = '".$csrftoken."';
      $.post('login_action.php?a=reload_balance',
        { send_reload_balance: send_reload_balance, csrftoken: csrftoken },
        function(result){
          var resulthtml = JSON.parse(result);
          setTimeout(function(){
            $('.sidermenu-balance .balance').text('$'+resulthtml.balance_num);
            if(resulthtml.is_gtoken_lock==true){
              location.reload();
            }
          //$('.reload_balance').html(resulthtml.balance);
          $(\"#balance_modal_content\").html(resulthtml.mobile_modal);
          $('#reload_balance_modal').modal('show');
           }, 300);
        });
    }
";
*/
$reload_balance_js="
    function reload_balance(){
      $('.reload_balance').html(\"<span class='badge badge-warning'><i class='fa fa-spinner fa-spin fa fa-fw mr-2'></i>".$tr['now loading']."...</span>\");
      var send_reload_balance  = 'true';
      var csrftoken = '".$csrftoken."';
      $.post('login_action.php?a=reload_balance',
        { send_reload_balance: send_reload_balance, csrftoken: csrftoken },
        function(result){
          var resulthtml = JSON.parse(result);
          if(resulthtml.is_gtoken_lock==true){
              location.reload();
            }
          setTimeout(function(){ $('.reload_balance').html(resulthtml.balance);
          $(\"#balance_modal_content\").html(resulthtml.mobile_modal);
          $('#reload_balance_modal').modal('show'); }, 300);
        });
    }
";
$tmpl['extend_js'] .=<<<HTML
<div id="reload_balance_modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div id="balance_modal_content" class="text-center my-3"></div>
    </div>
  </div>
</div>
<script type="text/javascript">
  function open_main_menu(){
    $('#main-menu').animate({right:"0"});
    $('#menu-block').fadeIn();
    $('body').addClass('lock-scroll');
  }
  function close_main_menu(){
    $('#main-menu').animate({right:"-70vw"});
    $('#menu-block').fadeOut();
    $('body').removeClass('lock-scroll');
  }
  {$reload_balance_js}
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
    <!--按钮样式-->
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
    </button>
    <!--网域-->
    {$ui["shorturl_m"]}
   </div>
    <!--展开样式-->
    <div class="collapse navbar-collapse" id="navbarCollapse">
     <!--基本选单-->
     <div class="container py-2">
      <div class="navblock">
      <!--游戏目录-->
      {$mobile_menu['mobile_menu']}
      <!--回到桌机连结-->
      {$mobile_menu['mobile2destop']}

      <!--客制化menu-->
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
    <!--按钮样式-->
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
    </button>
    <!--网域-->
    {$ui["shorturl_m"]}
   </div>
    <!--展开样式-->
    <div class="collapse navbar-collapse" id="navbarCollapse">
     <!--基本选单-->
     <div class="container py-2">
      <div class="navblock">
      <!--游戏目录-->
      {$mobile_menu['mobile_menu']}
      <!--回到桌机连结-->
      {$mobile_menu['mobile2destop']}
      <!--客制化menu-->
      {$ui['mobile_custom_menu']}
</div>
     </div>
    </div>
  </nav>
</header>
HTML;

$mobile_footer=page_footer();

?>