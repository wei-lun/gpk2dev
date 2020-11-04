<?php
// ----------------------------------------------------------------------------
// Features:    會員中心選單(手機版)
// File Name:    member_admin.php
// Author:        orange
// Related:
// Log:
// ----------------------------------------------------------------------------

// 主機及資料庫設定
require_once dirname(__FILE__) . "/config.php";
// 支援多國語系
require_once dirname(__FILE__) . "/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) . "/lib.php";
//會員錢包
require_once dirname(__FILE__) . "/lib_wallets.php";
//取得代理團隊人數
require_once dirname(__FILE__) . "/lib_agentarea.php";
//取得全民代理廣告
require_once dirname(__FILE__) . "/ui/component/national_agent.php";

// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
$function_title = $tr['membercenter_menu_admin'];//"会员中心"
// 擴充 head 內的 css or js
$extend_head = '';
// 放在結尾的 js
$extend_js = '';
// body 內的主要內容
$indexbody_content = '';
// 系統訊息選單
$messages = '';
// 初始化變數 end
// -----------------------------------------------------
// 全民代理開關
// -----------------------------------------------------
$allagentsetting = $protalsetting['national_agent_isopen'] ?? 'off';

// ----------------------------------------------------------------------------
// 導覽列
$navigational_hierarchy_html = '
<ul class="breadcrumb">
   <li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
   <li class="active">'.$function_title.'</li>
 </ul>';
 if($config['site_style']=='mobile'){
     $navigational_hierarchy_html = "";
 }

function combine_menulist($menu_list){
     $menu_html='';
     foreach ($menu_list as $key => $value) {
    $menu_html.=<<<HTML
    <li><a href="{$value}"><span>{$key}</span><i class="fas fa-chevron-right"></i></a></li>
HTML;
    }
    return $menu_html;
}

//財務中心選單
function combine_depositlist($depositlist_list){
    $depositlist_html='';
    foreach ($depositlist_list as $key => $value) {
 $depositlist_html.=<<<HTML
<li>
         <a href="{$value}">
             <span><i class="fa fa-credit-card"></i></span>
             <p>{$key}</p>
             <i class="fas fa-chevron-right"></i>
         </a>
</li>
HTML;
}
 return $depositlist_html;
}

// ----------------------------------------------------------------------------
// 有會員資料 不論身份 therole 為何，都可以修改個人資料。但是除了 therole = T 除外。
if (isset($_SESSION['member'])) {

$target_menu=$_GET['gid']??'default';
//非代理商進不去代理商選單
if($_SESSION['member']->therole != 'A' && $target_menu=='agent'){
    $target_menu='default';
}

switch ($target_menu)
{
    case 'message':
      $menu_at=100;
      break;
    case 'deposit':
      $menu_at=200;
      break;
    case 'safe':
      $menu_at=300;
      break;
    case 'agent':
      $menu_at=400;
      break;
    default:
      $menu_at=0;
}

$extend_head = '<style>
.menu_list_inner{
    right: '.$menu_at.'%;
}
</style>
';

$user_account = password_hash($_SESSION['member']->account, PASSWORD_DEFAULT);
$menu_html ='';

//主menu列表
$menu_html.=<<<HTML
    <ul class="member_mobile_menu">
    <li><a onclick="to_submenu('message');" href="#"><span>{$tr['membercenter_menu_admin_message']}</span><i class="fas fa-chevron-right"></i></a></li>
    <li><a href="{$config['website_baseurl']}mo_translog_query.php?transpage=m&id={$_SESSION['member']->id}"><span>{$tr['membercenter_mo_transaction_log']}</span><i class="fas fa-chevron-right"></i></a></li>
    <li><a href="{$config['website_baseurl']}moa_betlog.php?betpage=m&id={$_SESSION['member']->id}"><span>{$tr['membercenter_betrecord']}</span><i class="fas fa-chevron-right"></i></a></li>
    <li><a onclick="to_submenu('deposit');" href="#"><span>{$tr['membercenter_menu_admin_deposit']}</span><i class="fas fa-chevron-right"></i></a></li>
    <li><a onclick="to_submenu('safe');" href="#"><span>{$tr['membercenter_menu_admin_safe']}</span><i class="fas fa-chevron-right"></i></a></li>
    </ul>
HTML;

//我的消息
$message_submenu_list=array(
$tr['membercenter_announcement'] => $config['website_baseurl'].'announcement.php',//'公告'
$tr['membercenter_stationmail'] => $config['website_baseurl'].'stationmail.php',//'站内信'
$tr['membercenter_member_receivemoney'] => $config['website_baseurl'].'member_receivemoney.php',//'彩金领取'
);
//財務中心
$deposit_submenu_list=array(
$tr['membercenter_deposit'] => $config['website_baseurl'].'deposit.php',//'充值'
$tr['membercenter_wallets'] => $config['website_baseurl'].'wallets.php',//'提款'
);
//隱藏現金功能開啟時隱藏幣別轉換
if(hide_gcash_mode()=='off'){
    $deposit_submenu_list[$tr['membercenter_exchange_token']] = $config['website_baseurl'].'exchange_token.php';//'币别转换'
}
//安全中心
$safe_submenu_list=array(
$tr['membercenter_member'] => $config['website_baseurl'].'member.php',//'个人信息'
$tr['membercenter_member_changepwd'] => $config['website_baseurl'].'member_changepwd.php',//'修改登陆密码'
$tr['membercenter_member_withdrawalpwd'] => $config['website_baseurl'].'member_withdrawalpwd.php',//'修改提款密码'
$tr['membercenter_member_banksetting'] => $config['website_baseurl'].'member_banksetting.php',//'银行卡'
/*'分享给好友' => $config['website_baseurl'].'member_share_friends.php',*/
$tr['membercenter_member_authentication'] => $config['website_baseurl'].'member_authentication.php' //
);

//代理商中心
$agent_submenu_list=array(
/*'代理报表' => '#',*/
$tr['membercenter_agent_instruction'] => $config['website_baseurl'].'agent_instruction.php',
$tr['membercenter_spread_register'] => $config['website_baseurl'].'spread_register.php',//'邀请码'
$tr['membercenter_member_management'] => $config['website_baseurl'].'member_management.php',//'会员管理'
$tr['membercenter_moa_betlog'] => $config['website_baseurl'].'moa_betlog.php?betpage=a&id='.$_SESSION['member']->id,//'投注明细'
$tr['membercenter_mo_transaction_log'] => $config['website_baseurl'].'mo_translog_query.php?transpage=a&id='.$_SESSION['member']->id,//'交易明细'
);
    /*
'反水佣金明细' => $config['website_baseurl'].'agencyarea_summary.php',
'占成比例设定' => $config['website_baseurl'].'agencyarea.php',
*/

//必要參數
$member_login_data = login_data()['data']; //取得member 資訊(ID 身分 餘額)
$mobile_menu = mobile_header_menu('admin'); //取得一般menu
$menu_member = ($config['site_style']=='mobile')? menu_login_ui():''; //取得頭像 餘額框等
$log_out_function = member_logout_html(); //登出鈕之動作
$message_submenu_html = combine_menulist($message_submenu_list); //我的消息
//$deposit_submenu_html = combine_menulist($deposit_submenu_list); //財務中心
$deposit_list_html = combine_depositlist($deposit_submenu_list); //新 財務中心
$safe_submenu_html = combine_menulist($safe_submenu_list); //安全中心
$agent_submenu_html = ''; //代理商中心
$allagent_section = national_agent(); //全民代理中心
$agent_submenu_js =''; //代理中心menu js

//代理商才看得到專區
if($_SESSION['member']->therole == 'A'){
    $menu_html.=<<<HTML
        <ul class="member_mobile_menu_s">
        <li><a onclick="to_submenu('agent');" href="#"><span>{$tr['membercenter_menu_admin_agent']}</span><i class="fas fa-chevron-right"></i></a></li>
        </ul>
    HTML;
    //代理商下線傭金資訊欄//取得代理團隊人數
    $reportdata = new lib_agentarea();
    $successorCount = $reportdata->getSuccessorCount($_SESSION['member']->id);
    $agentCommission = $reportdata->getAgentCommission(30);
    if($agentCommission===false){
        $agentCommission=$tr['search no data'];
    }    else{
        $agentCommission='$'.$agentCommission;
    }

    $agent_infobox =<<<HTML
        <div class="px-10 menu_admin_bt">
        <div class="row agent-info">
            <div class="col agent-info-block">
                <div class="ligh-txt">{$successorCount}{$tr['people']}</div>
                <div class="tit">{$tr['agencyarea_member_count']}</div>
            </div>
            <div class="col agent-info-block">
                <div class="select-box">
                    <button id="id_date_select" type="button" onclick="on_slidemenu('date_select');" class="btn btn-outline-secondary dropdown-toggle btn-sm" value="30">{$tr['month']}</button>
                </div>
                <div id="agent-commission" class="ligh-txt">{$agentCommission}</div>
                <div class="tit">{$tr['agent_commission_balance']}</div>
            </div>
        </div>
        </div>
        <!--filter_select-->
        <div class="block-layout motransaction_layout"></div>
        <div id="date_select" class="slide-up-menu slide-up-style">
            <table class="table">
                    <thead class="thead-light">
                        <tr><th class="bg-secondary">{$tr['please select date']}</th></tr>
                        <tr><td class="cl_date" data-dateval="1">{$tr['yesterday']}</td></tr>
                        <tr><td class="cl_date" data-dateval="7">{$tr['week']}</td></tr>
                        <tr><td class="cl_date" data-dateval="30">{$tr['month']}</td></tr>
                        <tr><td class="ca_tdcancel bg-secondary" onclick="off_slidemenu()">{$tr['cancel']}</td></tr>
                    </thead>
            </table>
        </div>
    HTML;
    $extend_js .=<<<HTML
    <script>
        function off_slidemenu(){
        $('.slide-up-menu').removeClass('slide-up');
        $('.block-layout').fadeOut();
        }
        function on_slidemenu(toggle){
        $('#'+toggle).addClass('slide-up');
        $('.block-layout').fadeIn();
        }
            $('#date_select').on('click', '.cl_date', function() {
                var csrftoken = '{$csrftoken}';
                $('#id_date_select').text($(this).text())
                $.ajax({
                        type: 'POST',
                        url: 'agentarea_action.php',
                        data: {
                                data: JSON.stringify({'limit' : $(this).attr('data-dateval')}),
                                action: 'agentCommission',
                                csrftoken: '{$csrftoken}'
                        },
                        success: function(resp) {
                            resp = JSON.parse(resp)
                            if(resp.status == 'success')
                                $('#agent-commission').text('$'+resp.result)
                            else
                                $('#agent-commission').text(resp.result)
                        }
                });
                off_slidemenu()
        });
    </script>
    HTML;
    // 全民代理
    if ($allagentsetting === 'on') {
        //推廣邀請碼廣告
        $allagent_section = <<<HTML
            <div class="allagent">
                <a href="{$config['website_baseurl']}spread_register.php" role="button">
                    <img class="allagent-img" src="{$cdnfullurl_js}img/promotion/spread_register.png" alt="">
                    <a class="btn btn-primary go-spread-register" href="{$config['website_baseurl']}spread_register.php" role="button">立即邀請</a>
                </a>
            </div>
        HTML;
    }

    // 代理商中心menu
    $agent_submenu_html = combine_menulist($agent_submenu_list);
    $agent_submenu_html = <<<HTML
        <ul id="agent" class="admin_menu_list sub">
            <li class="menu_tit">
                <div class="menuheader_menuadmin">
                    <div class="d-flex justify-content-between align-items-center">
                        <a onclick="to_submenu('main-admin-menu');" href="#">
                            <i class="fas fa-chevron-left"></i><span>{$tr['membercenter_menu_admin_agent']}</span>
                            <i></i>
                        </a>
                    </div>
                </div>
            </li>
            <ul class="member_mobile_menu">
                <!--代理商帳號資訊-->
                <div class="d-flex member_header_img">
                    <div id="member-icon" class="col-auto my-auto">
                        {$mobile_menu['membericon']}
                    </div>
                    <div class="col-auto account_information">
                        <div id="account_text">{$tr['Account']}：{$member_login_data['account']}</div>
                        <div id="account_text">{$tr['idetntity']}：{$member_login_data['therole']['name']}</div>
                    </div>
                </div>
                <!--代理商下線傭金資訊-->
                {$agent_infobox}
                <!--推廣邀請碼廣告-->
                {$allagent_section}
                <!--代理商中心menu-->
                {$agent_submenu_html}
            </ul>
        </ul>
    HTML;

    // 代理中心
    $agent_submenu_js = <<<JS
        case 'agent':
            tag = 400;
            break;
    JS;
} else {
    if ( ($allagentsetting == 'off') && isset($protalsetting["agent_register_switch"]) && ($protalsetting["agent_register_switch"] === 'on') ) {
        //申请代理商
        $menu_html .= <<<HTML
            <ul class="member_mobile_menu">
                <li>
                    <a href="{$config['website_baseurl']}register_agent.php">
                        <span>{$tr['membercenter_register_agent']}</span>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            </ul>
        HTML;
    }
}

//財務中心(餘額刷新欄位)
$casinoBalanceHtml = combineGetCasinoBalanceHtml();
$casinoBalanceJs = combineGetCasinoBalanceJs();

$menu_html =<<<HTML
<div id="menu_container" class="w-100">
    <div id="menu_list">
        <div class="menu_list_inner">
            <!--主畫面-->
            <ul id="main-admin-menu" class="admin_menu_list">
                {$menu_member}
                {$mobile_menu['mobile_user_simple_menu']}
                {$allagent_section}
                {$menu_html}
                <li><button id="btn-logout" class="btn btn-block" onclick="member_logout()">{$tr['Logout']}</button></li>
            </ul>
            <!--我的消息-->
            <ul id="message" class="admin_menu_list sub">
                <li class="menu_tit">
                    <div class="menuheader_menuadmin">
                    <div class="d-flex justify-content-between align-items-center">
                        <a onclick="to_submenu('main-admin-menu');" href="#">
                        <i class="fas fa-chevron-left"></i>
                        <span>{$tr['membercenter_menu_admin_message']}</span>
                        <i></i>
                        </a>
                    </div>
                    </div>
                </li>
                <ul class="member_mobile_menu">
                {$message_submenu_html}
                </ul>
            </ul>
            <!--財務中心-->
            <ul id="deposit" class="admin_menu_list sub">
                <li class="menu_tit">
                    <div class="menuheader_menuadmin">
                    <div class="d-flex justify-content-between align-items-center">
                    <a onclick="to_submenu('main-admin-menu');" href="#">
                        <i class="fas fa-chevron-left"></i>
                        <span>{$tr['membercenter_menu_admin_deposit']}</span>
                        <i></i>
                    </a>
                    </div>
                    </div>
                </li>
                {$casinoBalanceHtml}
                <div class="container">
                    <div class="row">
                    <div class="col-12">
                    <ul class="member_mobile_menu style_deposit">
                        {$deposit_list_html}
                    </ul>
                    </div>
                    </div>
                </div>
            </ul>
            <!--安全中心-->
            <ul id="safe" class="admin_menu_list sub">
                <li class="menu_tit">
                    <div class="menuheader_menuadmin">
                    <div class="d-flex justify-content-between align-items-center">
                    <a onclick="to_submenu('main-admin-menu');" href="#">
                    <i class="fas fa-chevron-left"></i>
                    <span>{$tr['membercenter_menu_admin_safe']}</span>
                    <i></i>
                    </a>
                    </div>
                    </div>
                </li>
                <ul class="member_mobile_menu">
                {$safe_submenu_html}
                </ul>
            </ul>
            <!--代理商中心-->
            {$agent_submenu_html}
        </div>
    </div>
</div>
HTML;

//選單切換JS
$extend_js .=<<<HTML
    {$casinoBalanceJs}
    <style>
    #main>.container {
        padding: 0px;
    }
    </style>
    {$log_out_function}
    <script>
    function to_submenu(toggle){
        var tag = 0;
        switch (toggle) {
                case 'main-admin-menu':
                    tag=0;
                    break;
                case 'message':
                    tag=100;
                    break;
                case 'deposit':
                    tag=200;
                    break;
                case 'safe':
                    tag=300;
                    break;
                {$agent_submenu_js}
                default:
                    tag=0;
                    break;
            }
        $('.menu_list_inner').css('right',tag+'%');
        $('#md-main').scrollTop(0);
    }
    $(document).ready(function() {
        //$('[data-toggle="tooltip_balance"]').tooltip();
        $(window).resize(function() {
          $(".admin_menu_list").css("width",$("#menu_container").width());
        }).resize();
    });
    </script>
HTML;
} else {
    $menu_html = $tr['no permission login first'];//'(x)你沒有權限，請登入系統。'
    $logger = $menu_html;
    // memberlog 2db('guest', 'member', 'notice', "$logger");
      $msg=$tr['no permission login first'];//'(x)你没有权限，请登入系统。'
    $msg_log = $tr['no permission login first'];//'(x)你没有权限，请登入系统！'
    $sub_service='authority';
    memberlogtodb('guest','member','warning',"$msg",'guest',"$msg_log",'f',$sub_service);
    // login and goto page
    $menu_html = login2return_url(0);
}

// 切成 3 欄版面 3:8:1
$indexbody_content = '';
//功能選單(美工)  功能選單(廣告)
$indexbody_content = $indexbody_content .$menu_html;

// ----------------------------------------------------------------------------
// 準備填入的內容
// ----------------------------------------------------------------------------
// 將內容塞到 html meta 的關鍵字, SEO 加強使用
$tmpl['html_meta_description'] = $config['companyShortName'];
$tmpl['html_meta_author'] = $config['companyShortName'];
$tmpl['html_meta_title'] = $function_title . '-' . $config['companyShortName'];

// 系統訊息顯示
$tmpl['message'] = $messages;
// 擴充再 head 的內容 可以是 js or css
$tmpl['extend_head'] = $extend_head;
// 擴充於檔案末端的 Javascript
$tmpl['extend_js'] = $extend_js;
// 主要內容 -- title
$tmpl['paneltitle_content'] = $navigational_hierarchy_html;
// 主要內容 -- content
$tmpl['panelbody_content'] = $indexbody_content;
// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
// 桌機手機畫面不同。
if($config['site_style']=='desktop')
    include($config['template_path']."template/admin.tmpl.php");
elseif($config['site_style']=='mobile')
    include($config['template_path']."template/admin_fluid.tmpl.php");
?>
