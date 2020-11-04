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

//客製化menu
require dirname(dirname(__DIR__)).'/component/custom_menu.php';
//短網址
$ui["shorturl_m"]=menu_short_url('mobile');
//客製化theme
require dirname(dirname(__DIR__)).'/component/custom_theme.php';
template_themes('mobile');
//跑馬燈
require dirname(dirname(__DIR__)).'/component/marquee.php';
$ui['Scroll_marquee'] = Scroll_marquee();
//-------------------------------------------------------------------------------------------
//header集中管理
//-------------------------------------------------------------------------------------------
$mobile_header=<<<HTML
<!-- header -->
<header id="header">
  <nav class="navbar navbar-dark">
   <div class="container">
    <!--会员中心移出-->
    <div class="membercenter"><a href="{$config['website_baseurl']}menu_admin.php">
    <span class="glyphicon glyphicon-user" aria-hidden="true" data-toggle="tooltip_therole" data-placement="left" title="会员中心"></span></a>
    </div>
    <!--LOGO-->
    <a class="navbar-brand mx-auto" href="{$config['website_baseurl']}"><div class="logo"></div></a>

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

// 指紋偵測 iframe
$fingerprintsession_html = '<iframe name="print" frameborder="0" src="'.$config['website_baseurl'].'fingerprintsession.php" height="0px" width="100%" scrolling="no"></iframe>';

$admin_menu_list=menu_admin_management('mobile');

if(isset($_SESSION['member']) ){
  $user_account = password_hash($_SESSION['member']->account, PASSWORD_DEFAULT);
  $admin_menu_list =<<<HTML
    <a class="nbox" onclick="open_md_page(this)" data-target="{$config['website_baseurl']}announcement.php"><div class="icon"><i class="fas fa-envelope"></i></div><span class="title">{$tr['membercenter_menu_admin_message']}</span></a>
    <a class="nbox" onclick="open_md_page(this)" data-target="{$config['website_baseurl']}mo_translog_query.php?transpage=m&id={$_SESSION['member']->id}"><div class="icon"><i class="fas fa-user"></i></div><span class="title">{$tr['membercenter_mo_transaction_log'] }</span></a>
    <a class="nbox" onclick="open_md_page(this)" data-target="{$config['website_baseurl']}moa_betlog.php?betpage=m&id={$_SESSION['member']->id}"><div class="icon"><i class="fas fa-list"></i></div><span class="title">{$tr['membercenter_betrecord']}</span></a>
    <a class="nbox" onclick="open_md_page(this)" data-target="{$config['website_baseurl']}deposit.php"><div class="icon"><i class="fas fa-list"></i></div><span class="title">{$tr['membercenter_menu_admin_deposit']}</span></a>
    <a class="nbox" onclick="open_md_page(this)" data-target="{$config['website_baseurl']}member.php"><div class="icon"><i class="fas fa-credit-card"></i></div><span class="title">{$tr['membercenter_menu_admin_safe']}</span></a>
HTML;
    if ($_SESSION['member']->therole === 'M') {
        $admin_menu_list .=<<<HTML
            <a class="nbox" onclick="open_md_page(this)" data-target="{$config['website_baseurl']}partner.php" target="_self"><span class="title">{$tr['membercenter_register_agent']}</span></a>
        HTML;
    }
  elseif($_SESSION['member']->therole == 'R' OR $_SESSION['member']->therole == 'A'){
    $admin_menu_list .=<<<HTML
    <a class="nbox" onclick="open_md_page(this)" data-target="{$config['website_baseurl']}register_agenthelp.php" target="_self"><div class="icon"><i class="fas fa-th-large"></i></div><span class="title">代理商专区</span></a>
HTML;
  }
  $admin_menu_list .=member_logout_html();
}else{
  $admin_menu_list="";
}
/*<span class="glyphicon glyphicon-user" aria-hidden="true" data-toggle="tooltip_therole" data-placement="left" title="'.$member_data['therole']['name'].'"></span>*/
if(isset($_SESSION['member'])){
  $member_data = login_data()['data'];
  if($member_data['is_gtoken_lock']==true){
    $balance_html = '<span class="text-danger">'.$member_data['balance']['all'].'</span>';
    $recycle_balance_html = '<img class="recycle-balance-btn" onclick="gtokenrecycling_balance()" src="'.$cdnfullurl.'img/home/chipicon.png">';
  }
  else{
    $balance_html = '<span class="text-success">'.$member_data['balance']['all'].'</span>';
    $recycle_balance_html = '';
  }

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
  // 確認要取回所有娛樂城的餘額？
  $confirm_text = $tr['confirm get all casino back'];
  $gtokenrecycling_js = "
    <script>
      function gtokenrecycling_balance(){
        var gtokenrecycling = 1;

        if(confirm('".$confirm_text."')){
          $('#gtokenrecycling_balance').attr('disabled', 'disabled');
          ".$run_win_js."
        }else{
          //放棄,取回所有娛樂城的餘額!!
          alert('".$tr['giveup get all casino back']."');
        }

      }
    </script>
  ";
  $tmpl['extend_js'] .=$gtokenrecycling_js;

  $reload_balance_js=<<<HTML
<script type="text/javascript">
    function reload_balance(){
      $('.balance-num').html("<span class='text-warning'><i class='fa fa-spinner fa-spin fa fa-fw mr-2'></i>载入中...</span>");
      var send_reload_balance  = 'true';
      var csrftoken = '$csrftoken';
      $.post('login_action.php?a=reload_balance',
        { send_reload_balance: send_reload_balance, csrftoken: csrftoken },
        function(result){
          var resulthtml = JSON.parse(result);
          setTimeout(function(){
            if(resulthtml.is_gtoken_lock==true){
              $('.balance-num').html('<span class="text-danger">'+resulthtml.balance_num+'</span>');
              $('.recycle-balance').html('<img class="recycle-balance-btn" onclick="gtokenrecycling_balance()" src="{$cdnfullurl}img/home/chipicon.png">');
            }else{
              $('.balance-num').html('<span class="text-success">'+resulthtml.balance_num+'</span>');
              $('.recycle-balance').html('');
            }
            $("#balance_modal_content").html(resulthtml.mobile_modal);
            $('#reload_balance_modal').modal('show');

          }, 300);
        });
    }
</script>
HTML;
  $tmpl['extend_js'] .=$reload_balance_js;
  switch ($member_data['therole']['code']) {
    case 'm':
      $member_icon='<img src="'.$cdnfullurl.'img/home/icon-member.png" alt="">';
      break;
    case 'a':
      $member_icon='<img src="'.$cdnfullurl.'img/home/icon-agent.png" alt="">';
      break;
    case 'r':
      $member_icon='<img src="'.$cdnfullurl.'img/home/icon-agent.png" alt="">';
      break;
    default:
      $member_icon='<img src="'.$cdnfullurl.'img/home/icon-member.png" alt="">';
      break;
  }
  $landscape_mobile_menu =<<<HTML
<div class="h-menu member-info-area">
  <div class="member-icon">{$member_icon}</div>
  <div class="member-id">{$member_data['therole']['name']}</br>{$member_data['account']}</div>
</div>
<div class="offset-space"></div>
<div class="offset-space"></div>
<div class="h-menu member-balance-area">
  <div class="member-balance">
    <img class="balance-bg" src="{$cdnfullurl}img/home/bg_money.png">
    <div class="balance-num">{$balance_html}</div>
    <img class="reload-balance-btn" onclick="reload_balance()" src="{$cdnfullurl}img/home/btn_reset.png">
    <!--modal-->
      <div id="reload_balance_modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm">
          <div class="modal-content">
            <div id="balance_modal_content" class="text-center my-3"></div>
          </div>
        </div>
      </div>
    <!--modal end-->
  </div>
</div>
<div class="h-menu recycle-balance-area">
  <div class="recycle-balance">
    {$recycle_balance_html}
  </div>
</div>
<div class="offset-space"></div>
<div class="h-menu h-player"><a onclick="open_main_menu()"><img src="{$cdnfullurl}img/home/top_button3.png" alt=""></a></div>
<div class="h-menu h-promote"><a onclick="open_md_page(this)" data-target="{$config['website_baseurl']}promotions.php"><img src="{$cdnfullurl}img/home/top_button4.png" alt=""></a></div>
<div class="h-menu h-backtohome"><a href="{$config['website_baseurl']}"><img src="{$cdnfullurl}img/home/icon_home.png" alt=""></a></div>

<div id="menu-block" onclick="close_main_menu()"></div>
<div class="main-menu" id="main-menu">
 <div class="control">
   <button class="navbar-toggler" type="button" onclick="close_main_menu()">
      <img src="{$cdnfullurl}img/home/side_close.png" alt="">
    </button>
 </div>

 <!--基本選單-->
 <div class="container py-2">
  <div class="menu-list">
  {$admin_menu_list}
  <a class="nbox logout" onclick="member_logout()"><span class="title"><img class="mr-1" src="{$cdnfullurl}img/home/icon_logout.png" alt="">注销</span></a>
  </div>
 </div>

</div>
HTML;

}else{
  $landscape_mobile_menu =<<<HTML
<a href="{$config['website_baseurl']}login2page.php"><div class="h-menu h-login"></div></a>
<a href="{$config['website_baseurl']}register.php"><div class="h-menu h-register"></div></a>
<div class="offset-space"></div>
<div class="offset-space"></div>
<div class="h-menu h-player"><a onclick="open_main_menu()"><img src="{$cdnfullurl}img/home/top_button3.png" alt=""></a></div>
<div class="h-menu h-promote"><a onclick="open_md_page(this)" data-target="{$config['website_baseurl']}promotions.php"><img src="{$cdnfullurl}img/home/top_button4.png" alt=""></a></div>
<div class="h-menu h-backtohome"><a href="{$config['website_baseurl']}"><img src="{$cdnfullurl}img/home/icon_home.png" alt=""></a></div>

<div id="menu-block" onclick="close_main_menu()"></div>
<div class="main-menu" id="main-menu">
 <div class="control">
   <button class="navbar-toggler" type="button" onclick="close_main_menu()">
      <img src="{$cdnfullurl}img/home/side_close.png" alt="">
    </button>
 </div>

 <!--基本選單-->
 <div class="container py-2">
  <div class="menu-list">
  <a class="nbox" href="{$config['website_baseurl']}login2page.php"><span class="title">立即登录</span></a>
  <a class="nbox" href="{$config['website_baseurl']}register.php"><span class="title">免费注册</span></a>
  </div>
 </div>

</div>
HTML;
}

$landscape_mobile_header=<<<HTML
<div id="landscape-alert">
  <h4 class="text-warning text-center">请翻转手机至横向</h4>
</div>
<!-- header -->
<header id="header">
{$landscape_mobile_menu}
</header>
<!-- Modal {$config['website_baseurl']}stationmail.php-->
<div class="modal fade" id="page-modal" tabindex="-1" role="dialog" aria-labelledby="page-modal" aria-hidden="true" data-backdrop="static">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content border-0 md-page-content">
      <a class="md-page-close" data-dismiss="modal" aria-label="Close">
          <img src="{$cdnfullurl}img/home/close.png" alt="">
        </a>
      <div class="modal-body md-page-body">
        <iframe class="md-page"></iframe>
      </div>
    </div>
  </div>
</div>
<!-- end Modal -->
<script type="text/javascript">
  function open_md_page(link_object){
    $('#page-modal .md-page').attr('src',$(link_object).attr('data-target'));
    $('#page-modal').modal('show');
  }
  $('#page-modal').on('hide.bs.modal', function (e) {
    $('input').blur();
  })
  $('#page-modal').on('hidden.bs.modal', function (e) {
    $('#page-modal .md-page').attr('src','');
  })
</script>
HTML;
/*
<!--網域-->
    {$ui["shorturl_m"]}
      <!--遊戲目錄-->
      {$mobile_menu['mobile_menu']}
      <!--回到桌機連結-->
      {$mobile_menu['mobile2destop']}
      <!--客製化menu-->
      {$ui['mobile_custom_menu']}

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
  $(document).ready(function(){
    try{
        $('#account_text').text($('#account_text').text().substr(3));
      $('#balance_row').html($('#balance_row').html().substr(3));
    }
  catch(err){
    }
  });

  var checkOrientation = function(){
      //mode = Math.abs(window.orientation) == 90 ? 'landscape' : 'portrait';
      var mode=(window.innerWidth > window.innerHeight)? "landscape":"portrait";
      if (mode == 'landscape')
      {
        $("#landscape-alert").hide();
      } else {
        $("#landscape-alert").show();
      }
  };
  window.addEventListener("resize", checkOrientation, false);
  window.addEventListener("orientationchange", checkOrientation, false);
  checkOrientation();
</script>
HTML;

$mobile_footer=page_footer();

$landscape_foo=menu_features('mobile');
$landscape_mobile_footer=<<<HTML
<footer id="footer" class="footer">
{$landscape_foo}
</footer>
HTML;
/*
if($('#page-modal .md-page').attr('src') != $(link_object).attr('data-target')){
      $('#page-modal .md-page').attr('src',$(link_object).attr('data-target'));
    }
*/
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
    <a class="navbar-brand mx-auto" href="{$config['website_baseurl']}"><div class="logo"></div></a>
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
    <a class="navbar-brand mx-auto" href="{$config['website_baseurl']}"><div class="logo"></div></a>
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
$marqueebox_modal = <<<HTML
<!-- Modal -->
<div class="modal fade browse_modal" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <div class="md-header mb-4"><span class="md-title">浏览纪录</span></div>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <img src="{$cdnfullurl}img/home/close.png" alt="">
        </button>
      </div>
      <div class="modal-body">
        <!-- 瀏覽紀錄遊戲 列表 -->
      <ul id="browse_content_list" class="browse_list"></ul>
      </div>
    </div>
  </div>
</div>
HTML;
$marqueebox_content = <<<HTML
<button class="browse_bt" data-toggle="modal" data-target="#exampleModalCenter"><i class="far fa-clock"></i> 浏览</button>
HTML;
?>