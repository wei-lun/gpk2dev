<?php
// ----------------------------------------
// Features:    前台 -- JIGDEMO 專用 PHP lib 函式庫 -- 拆分出來 menu 選單專用的部份
// File Name:    lib_menu.php
// Author:        Barkley
// Related:
// Log:
// -----------------------------------------------------------------------------
// 原本的 lib.php 拆分為三個
// lib_common.php 專門的資料庫存取函式 , 由 config.php 程式護機
// lib.php 專門放置單一登入控制的函式, 由每個使用者的 *.php 呼叫使用
// lib_menu.php 這個專門負責系統選單部份功能(有程式判斷), 由 lib.php 呼叫


// login 專用函式庫 -- 提供 login_action 及 login2page_action 使用
// 整合lib_menu.php、login2page.php的登入action
require_once dirname(__FILE__) ."/login2page_lib.php";
require_once 'casino_lib.php';

/*
// function 索引及說明：
// -------------------

// tmplate 選單專用 function 索引及說明：
// -----------------------------------
0. hide_gcash_mode() 是否啟用「現金帳戶未使用自動隱藏」功能
0. member_balance_reload 更新會員的個資及餘額狀態, 目前專門給： menu_login_ui function 使用
0. stationmail_member_messages_count 取得未讀站內信件訊息數量. 有資料顯示 html 沒有資料不显示,  目前專門給： menu_login_ui function 使用
0. receivemoney_messages 彩金領取入款通知資訊, 帳號, 接收時間為空, 狀態為 1, 截止時間前領取
1. menu_features 中間 -- 前端娛樂城功能選單
2. menu_admin_management 會員及管理員專用選單, 依據權限有不同的顯示
3. menu_guest_management 訪客權限的選單以及有登入的時候的選單
4. menu_login_ui 會員登入選單，提供會員登入、登出的功能
5. page_footer 頁腳顯示
6. menu_member 前台登入後 -- 會員中心專用選單 + 加盟商專用選單
7. menu_time 美东时间显示
8. menu_agentadmin 前台 - 代理商专属选单
9. templ_header_mainmenu 判定回傳哪種主功能選單
10. templ_header_login 判定是否回傳登入表單
11. mobile_header_menu 行動裝置用選單
12. combine_banner banner標題之生成
13. menu_active menu新增active
14. combine_sidebarmenu 靜態頁與會員頁側邊次選單生成
15. breadcrumb 麵包屑生成
16. login_data 傳回會員等級、會員ID、餘額、是否有錢在娛樂城等資料(僅數據)
17. assets_include 載入所有必要js css檔案 (bootstrap fonts 等)
*/

// ----------------------------------------------------------------------------
// 頁面的 menu function , 一定要放在 lib.php 的最後面。否則 call lib 的時候會出現順序的問題。
// ---------------------------------------------------------------------

// -----------------------------------------------
// 是否啟用「現金帳戶未使用自動隱藏」功能
// 作用:若會員60日內無現金交易紀錄  前台將會隱藏所有"現金"相關功能及名詞
// 回傳 on/off (string) 為on->將隱藏現金及其功能
// -----------------------------------------------
function hide_gcash_mode(){
    global $protalsetting;

    //後台開關關閉則隱藏模式off
    if(isset($protalsetting['hide_gcash_mode']) AND $protalsetting['hide_gcash_mode'] == 'off'){
        return 'off';
    }
    //後台開關開啟
    if (!isset($protalsetting['hide_gcash_mode']) OR $protalsetting['hide_gcash_mode'] == 'on') {
        //現金餘額不為0 則隱藏模式off
        if ((float)$_SESSION['member']->gcash_balance != 0) {
            return 'off';
        }else{
            //gcash_log_exist == true 代表60日內有進行現金交易  則隱藏模式off
            return json_decode($_SESSION['member']->gcash_log_exist)->gcash_log_exist?'off':'on';
        }
    }
}
// -----------------------------------------------
// 会员登出专用的 logout JS, 执行会回传 logout 的 html JS code
// member_logout_html
// -----------------------------------------------
function member_logout_html() {
    global $config;
    global $csrftoken;
    $member_logout_html = "
    <script>
        function member_logout(){
            var send_logout  = 'true';
            var csrftoken = '".$csrftoken."';
            $.post('login_action.php?a=logout',
                { send_logout: send_logout,
                    csrftoken: csrftoken
                },
                function(result){
                    location.href='".$config['website_baseurl']."index.php';}
            );
        }
    </script>
    ";

    return($member_logout_html);
}
// -----------------------------------------------

// -----------------------------------------------
// 更新會員的個資及餘額狀態, 目前專門給： menu_login_ui function 使用
// 回傳： 成功: $_SESSION['member'] 更新為最新的資料
// 失敗： 沒有更新 $_SESSION
// -----------------------------------------------
function member_balance_reload($account_id = NULL) {
    global $tr;
    $user_balance_sql = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.id = '".$account_id."';";
    $user_balance_result = runSQLall($user_balance_sql, 0, 'r');
    if($user_balance_result[0] == 1) {
        // 存在，取出餘額
        $casino_info = json_decode($user_balance_result[1]->casino_accounts,'true');
        if(count($casino_info) >= 1){
            foreach($casino_info as $cid => $cinfo){
                $cid = strtolower($cid);
                $cida = $cid.'_account';
                $cidp = $cid.'_password';
                $cidb = $cid.'_balance';
                $user_balance_result[1]->$cida = $cinfo['account'];
                $user_balance_result[1]->$cidp = $cinfo['password'];
                $user_balance_result[1]->$cidb = $cinfo['balance'];
            }
        }
        unset($user_balance_result[1]->casino_accounts);

        $_SESSION['member'] = $user_balance_result[1];
        $r['code'] = 1;
        $r['messages'] = $tr['Update balance and member account'];//'更新余额及会员帐号'
    }else{
        $r['code'] = 0;
        $r['messages'] = $tr['Update balance and member account failed'];//'更新余额及会员帐号失敗'
    }
    return($r);
}
// -----------------------------------------------

// -----------------------------------------------
// 取得未讀站內信件訊息數量. 有資料顯示 html 沒有資料不显示,  目前專門給： menu_login_ui function 使用
// Usage: stationmail_member_messages_count(帐号)
// return: 回传阵列, 包含显示的 html 资讯
// -----------------------------------------------
function stationmail_member_messages_count($account = NULL) {
    global $config;
    global $tr;

    // unread message count , 取得未讀站內信件訊息數量. 有資料顯示 html 沒有資料影藏
    // $msql = "SELECT * FROM root_stationmail WHERE msgto = '".$account."' AND readtime IS NULL AND status = 1;";
    $msql = <<<SQL
    SELECT root_member_groupmail.subject,
                root_member_groupmail.message,
                root_member_groupmail.mailtype,
                root_member_groupmail.mailcode,
                root_member_groupmail.template
    FROM root_member_groupmail
    WHERE root_member_groupmail.msgto = '{$account}'
    AND readtime IS NULL
    AND status = '1'
    UNION ALL
    SELECT root_stationmail.subject,
                root_stationmail.message,
                root_stationmail.mailtype,
                root_stationmail.mailcode,
                root_stationmail.template
    FROM root_stationmail
    WHERE root_stationmail.msgto = '{$account}'
    AND readtime IS NULL
    AND status = '1';
SQL;

    // $msql_result = runSQLall($msql, 0, 'r');
    $msql_count = runSQL($msql, 0, 'r');
    // 没有错误, 且数量大于 0 才显示
    if($msql_count != FALSE AND $msql_count >= 1) {
        $r['code'] = 1;
        $r['messages_count'] = $msql_count;
        $r['html'] = '<a class="notice_unread" href="'.$config['website_baseurl'].'stationmail.php" title="'.$tr['you have'].$msql_count.$tr['count'].$tr['unread'].$tr['Messages'].'"><i class="mdi mdi-email-outline"></i><span class="badge member_number">'.$msql_count.'</span></a>';

    }else{
        $r['code'] = 0;
        $r['messages_count'] = 0;
        // 目前没有信件
        $r['html'] = '<a class="notice_unread" href="'.$config['website_baseurl'].'stationmail.php" title="'.$tr['you have'].$msql_count.$tr['count'].$tr['unread'].$tr['Messages'].'"><i class="mdi mdi-email-outline"></i></a>';
    }
    // 取得未讀訊息數量 end
    return($r);
}
// -----------------------------------------------

// -----------------------------------------------
// 彩金領取入款通知資訊, 帳號, 接收時間為空, 狀態為 1, 截止時間前領取
// Usage: receivemoney_messages(帐号)
// return: 回传阵列, 包含显示的 html 资讯
// -----------------------------------------------
function receivemoney_messages($account = NULL) {
    global $config;
    global $tr;
    // 彩金領取入款通知資訊, 帳號, 接收時間為空, 狀態為 1, 截止時間前領取
    //$receiv_sql = "SELECT * FROM root_receivemoney WHERE member_account = '".$_SESSION['member']->account."' AND receivetime IS NULL AND status = 1  AND receivedeadlinetime <= current_timestamp  AND  current_timestamp <= receivedeadlinetime  ;";
    $receiv_sql = "
    SELECT * FROM root_receivemoney WHERE member_account = '".$_SESSION['member']->account."' AND status = 1
    AND receivetime IS NULL
    AND givemoneytime <= current_timestamp
    AND receivedeadlinetime >= current_timestamp ;
    ";
    // var_dump($receiv_sql);
    $receiv_result = runSQL($receiv_sql, 0, 'r');
    if($receiv_result != FALSE AND $receiv_result >= 1) {
        $r['code'] = 1;
        $r['messages_count'] = $receiv_result;
        $r['html'] = '<a class="notice_receiv" href="'.$config['website_baseurl'].'member_receivemoney.php" title="'.$tr['you have'].$receiv_result.$tr['get receivemoney'].'"><i class="mdi mdi-gift"></i><span class="badge member_number">'.$receiv_result.'</span></a>';
    }else{
        $r['code'] = 0;
        $r['messages_count'] = 0;
        $r['html'] = '<a class="notice_receiv" href="'.$config['website_baseurl'].'member_receivemoney.php" title="'.$tr['you have'].$receiv_result.$tr['get receivemoney'].'"><i class="mdi mdi-gift"></i></a>';
    }
    // 彩金領取入款通知資訊 end
    return($r);

}
// -----------------------------------------------

// -----------------------------------------------
// 用來生成6大類別選單的function
// Usage: main_category_itemmaker(客制選單,選單樣版)
// return: 回传選單html
// -----------------------------------------------
function main_category_itemmaker($menu_top_item,$menu_top_item_tmplate){
    global $gamelobby_setting;
    global $config;
    global $cdnfullurl;
    global $tr;
    // var_dump($gamelobby_setting);

    $mct_item_arr = array();
    foreach($gamelobby_setting['main_category_info'] as $mctid => $mct_arr){
        if($mct_arr['open'] == 1){
            if(isset($tr['menu_'.strtolower($mctid)])){
                $mct_name = $tr['menu_'.strtolower($mctid)];
            }elseif(isset($tr[$mct_arr['name']])){
                $mct_name = $tr[$mct_arr['name']];
            }else{
                $mct_name = $mct_arr['name'];
            }
             // var_dump($mct_name);
            $mct_item_tmp = str_replace(' website_baseurl ',$config['website_baseurl'],$menu_top_item_tmplate);
            $mct_item_tmp = str_replace(' cdnfullurl ',$cdnfullurl,$mct_item_tmp);
            $mct_item_tmp = str_replace(' mct_id ',$mctid,$mct_item_tmp);
            $mct_item_tmp = str_replace(' mct_name ',$mct_name,$mct_item_tmp);
            // var_dump($mct_item_tmp);
            $mct_item_arr[$mct_arr['order']] = $mct_item_tmp;
        }
    }
  ksort($mct_item_arr);
    $mct_item = implode("\n",$mct_item_arr);
    $menu_top_item = str_replace('main_category_item',$mct_item,$menu_top_item);
    $menu_top_item = str_replace(' cdnfullurl ',$cdnfullurl,$menu_top_item);

    return $menu_top_item;
}
// -----------------------------------------------

// ---------------------------------------------------------------------
// 選單 -- 世界名牌商城功能選單 (0703byjoyce)
// ---------------------------------------------------------------------
function menu_features($template=NULL)
{
    global $tr;
    global $customer_service_cofnig;
    global $config;
    global $protalsetting;
    // 中間功能選單
    // ----------------------------------------------------------------------------

    $menu_top_item = '';

    if($config['website_type'] == 'ecshop'){
        $menu_tmpl = $config['template_path']."template/menu_ecshop.tmpl.php";
        if (file_exists($menu_tmpl)){
          include($menu_tmpl);
        }else{
            // 美食
            $menu_top_item = $menu_top_item.'
                <li><a href="'.$config['website_baseurl'].'home.php?a=food" target="_self"><span class="glyphicon glyphicon-gift" aria-hidden="true"></span>'.$tr['HomeMenu Food'].'</a></li>
            ';

            // 服飾
            $menu_top_item = $menu_top_item.'
                <li><a href="'.$config['website_baseurl'].'home.php?a=clothing" target="_self"><span class="glyphicon glyphicon-gift" aria-hidden="true"></span>'.$tr['HomeMenu Clothing'].'</a></li>
            ';

            // 生活
            $menu_top_item = $menu_top_item.'
                    <li><a href="'.$config['website_baseurl'].'home.php?a=live" target="_self"><span class="glyphicon glyphicon-gift" aria-hidden="true"></span>'.$tr['HomeMenu Live'].'</a></li>
            ';


            // 旅行
            $menu_top_item = $menu_top_item.'
                    <li><a href="'.$config['website_baseurl'].'home.php?a=travel" target="_self"><span class="glyphicon glyphicon-gift" aria-hidden="true"></span>'.$tr['HomeMenu Travel'].'</a></li>
            ';

            // 教育
            $menu_top_item = $menu_top_item.'
                    <li><a href="'.$config['website_baseurl'].'home.php?a=edu" target="_self"><span class="glyphicon glyphicon-gift" aria-hidden="true"></span>'.$tr['HomeMenu Edu'].'</a></li>
            ';

            // 娛樂
            /*
            $menu_top_item = $menu_top_item.'
                        <li class="dropdown-mid">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                    <span class="glyphicon glyphicon-th" aria-hidden="true"></span>'.$tr['HomeMenu Fun'].'<span class="caret"></span></a>
                    <ul class="dropdown-menu">
                    <li><a href="home_fun.php"  target="_self">'.$tr['HomeMenu Fun'].'</a></li>
                    <li><a href="lobby_mggameh5.php"  target="_self">'.$tr['MG Electronic Casino'].'</a></li>
                    </ul>
                </li>
            ';
            */

            // 娛樂
            $menu_top_item = $menu_top_item.'
                    <li><a href="'.$config['website_baseurl'].'gamelobby.php" target="_self"><span class="glyphicon glyphicon-gift" aria-hidden="true"></span>'.$tr['HomeMenu Fun'].'</a></li>
            ';
        }
    }elseif($config['website_type'] == 'casino'){
        $menu_tmpl = $config['template_path']."template/menu_casino.tmpl.php";

        // 6大分類預設樣版
        $menu_top_item_tmplate = '<li class="nav-item navi_ mct_id "><a class="nav-link" href=" website_baseurl gamelobby.php?mgc= mct_id " target="_self"> mct_name </a></li>';
        // 樣版輸出選單位置
        $menu_top_item_base = ' main_category_item ';
        // 優惠活動
        $menu_top_item_base .= '
                <li class="nav-item navi_promotions"><a class="nav-link" href="'.$config['website_baseurl'].'promotions.php" target="_self">'.$tr['menu_promotions'].'</a></li>
        ';
        // 在線客服
        $menu_top_item_base .= '
            <li class="nav-item navi_service"><a class="nav-link" href="'.$config['website_baseurl'].'contactus.php" target="_self">'.$tr['menu_service'].'</a></li>
        ';
         //全民代理
         if(isset($protalsetting['national_agent_isopen']) && $protalsetting['national_agent_isopen'] == 'on'){
             if(isset($_SESSION['member']) && $_SESSION['member']->therole != 'A'){
                $menu_top_item_base .= '<li class="nav-item navi_allagent blinknotice"><a class="nav-link" href="'.$config['website_baseurl'].'allagent_register.php">'.$tr['membercenter_allagent_register'].'</a></li>';
            }elseif(!isset($_SESSION['member'])){
                $menu_top_item_base .= '<li class="nav-item navi_allagent blinknotice"><a class="nav-link" href="'.$config['website_baseurl'].'login2page.php">'.$tr['membercenter_allagent_register'].'</a></li>';
            }
        }

        //如果有menu_casino 讀取它覆蓋
        if (file_exists($menu_tmpl)){
          include($menu_tmpl);
         }
        $menu_top_item = main_category_itemmaker($menu_top_item_base,$menu_top_item_tmplate);
    }

    //手機板與桌機版輸出不同menu
    if(isset($template) AND $template == 'mobile'){
        $menu_top_output_html = $menu_top_item;
    }else{
        $menu_top_output_html = '
        <nav>
          <ul class="main-menu">
                    '.$menu_top_item.'
          </ul>
        </nav>';
    }

    return($menu_top_output_html);

}

// ---------------------------------------------------------------------
// mobile選單 -- 會員及管理員專用選單, 依據權限有不同的顯示
// 相關檔案：
// ---------------------------------------------------------------------
function menu_admin_management($template)
{

    global $tr;
    global $config,$csrftoken;
    global $cdnrooturl;
    global $protalsetting;
    $menu_static_content = NULL;

    // 一般會員有登入的話，顯示選單. 但如果是 T(試用) 則不顯示。
    if(isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T') {
        if(isset($_SESSION['member']->nickname)){
            $showname = $_SESSION['member']->nickname;
        }else{
            $showname = $_SESSION['member']->account;
        }

        // 判斷來源樣版
        if($template == 'home' OR $template == 'static' OR $template == 'gamelobby' OR $template == 'mobile' OR $template == 'mars'){
            // 判斷會員等級
            $therole_icon_html = '';
            if($_SESSION['member']->therole == 'M') {
                //會員
                $tooltip_therole_show_html = $tr['member'];
                $therole_icon_html = '<span id="member-role" class="glyphicon glyphicon-user" aria-hidden="true"  data-toggle="tooltip_therole" data-placement="left" title="'.$tooltip_therole_show_html.'" onclick="javascript:location.href=\'./member.php\'" style="cursor:pointer";></span>';
            }elseif($_SESSION['member']->therole == 'A') {
                // '加盟联营股东' = 代理商
                $tooltip_therole_show_html = $tr['agent'];
                $therole_icon_html = '<span id="member-role" class="glyphicon glyphicon-knight" aria-hidden="true"  data-toggle="tooltip_therole" data-placement="left" title="'.$tooltip_therole_show_html.'" onclick="javascript:location.href=\'./member.php\'" style="cursor:pointer"></span>';
            }elseif($_SESSION['member']->therole == 'R') {
                //管理員
                $tooltip_therole_show_html = $tr['management'];
                $therole_icon_html = '<span id="member-role" class="glyphicon glyphicon-king" aria-hidden="true"  data-toggle="tooltip_therole" data-placement="left" title="'.$tooltip_therole_show_html.'" onclick="javascript:location.href=\'./member.php\'" style="cursor:pointer"></span>';
            }else{
                //測試帳號
                $tooltip_therole_show_html = $tr['test account'];
                $therole_icon_html = '<span id="member-role" class="glyphicon glyphicon-eye-open" aria-hidden="true"  data-toggle="tooltip_therole" data-placement="left" title="'.$tooltip_therole_show_html.'"></span>';
            }

            //----- 會員中心專用選單 -------
            $menu_static_content_menu = '';
            // 站內訊息  -- todo 未來取代客服系統
            /*
            $menu_static_content_menu = $menu_static_content_menu.
            '<li><a href="stationmessage.php" >
            <span class="glyphicon glyphicon-envelope" aria-hidden="true"></span>
            '.$tr['Messages'].'</a><li>';
            */

            // 我的消息 - 使用在傳遞站內的系統訊息
            $menu_static_content_menu = $menu_static_content_menu.
            '<a class="nbox" href="'.$config['website_baseurl'].'announcement.php" target="_self"><div class="icon"><i class="fas fa-envelope"></i></div><span class="title">'.$tr['membercenter_menu_admin_message'].'</span></a>';

            // 交易明細
            $user_account = password_hash($_SESSION['member']->account, PASSWORD_DEFAULT);
            $menu_static_content_menu = $menu_static_content_menu.
            '<a class="nbox" href="'.$config['website_baseurl'].'mo_translog_query.php?transpage=m&id='.$_SESSION['member']->id.'" target="_self"><div class="icon"><i class="fas fa-user"></i></div><span class="title">'.$tr['membercenter_mo_transaction_log'].'</span></a>';

            // 投注明細
            $menu_static_content_menu = $menu_static_content_menu.
            '<a class="nbox" href="'.$config['website_baseurl'].'moa_betlog.php?betpage=m&id='.$_SESSION['member']->id.'" target="_self"><div class="icon"><i class="fas fa-list"></i></div><span class="title">'.$tr['membercenter_betrecord'].'</span></a>';

            // opencart 交易紀錄
            if($config['website_type'] == 'ecshop'){
                $menu_static_content_menu = $menu_static_content_menu.
            '<a class="nbox" href="'.$config['website_baseurl'].'shoppingrecord.php" target="_self"><div class="icon"><i class="fas fa-shopping-cart"></i></div><span class="title">'.$tr['Shopping records'].'</span></a>';
            }

            // 財務中心
            $menu_static_content_menu = $menu_static_content_menu.
            '<a class="nbox" href="'.$config['website_baseurl'].'deposit.php" target="_self"><div class="icon"><i class="fas fa-list"></i></div><span class="title">'.$tr['membercenter_menu_admin_deposit'].'</span></a>';

            // 線上取款
            /*
            $menu_static_content_menu = $menu_static_content_menu.
            '<a class="nbox" href="'.$config['website_baseurl'].'withdrawapplication.php" target="_self"><div class="icon"><i class="fas fa-bitcoin"></i></div><span class="title">'.$tr['Online withdrawal'].'</span></a>';
            */

            // 安全中心
            $menu_static_content_menu = $menu_static_content_menu.
            '<a class="nbox" href="'.$config['website_baseurl'].'member.php" target="_self"><div class="icon"><i class="fas fa-credit-card"></i></div><span class="title">'.$tr['membercenter_menu_admin_safe'].'</span></a>';

            // 代理商專區功能 , 只有代理商或是管理員才可以進入。
            if ( ($_SESSION['member']->therole == 'R') || ($_SESSION['member']->therole == 'A') ) {
                $agentarea_link = 'agent_instruction.php';
                $menu_static_content_menu .= <<<HTML
                    <a class="nbox" href="{$config['website_baseurl']}{$agentarea_link}" target="_self">
                        <div class="icon"><i class="fas fa-th-large"></i></div>
                        <span class="title">{$tr['agencyarea title']}</span>
                    </a>
                HTML;
            }

            // 申請代理商 , 只有會員才可以進入。
            if ($_SESSION['member']->therole === 'M') {
                // 全民代理是否開啟
                if ( isset($protalsetting['national_agent_isopen']) && ($protalsetting['national_agent_isopen'] === 'on') ) {
                    $menu_static_content_menu .= <<<HTML
                        <a class="nbox" href="{$config['website_baseurl']}allagent_register.php" target="_self">
                            <div class="icon blinknotice"><i class="fas fa-user"></i></div>
                            <span class="title hot">{$tr['membercenter_allagent_register']}</span>
                        </a>
                    HTML;
                // 代理商申請是否開啟
                } else if ( isset($protalsetting['agent_register_switch']) && ($protalsetting['agent_register_switch'] === 'on') ) {
                    $menu_static_content_menu .= <<<HTML
                        <a class="nbox" href="{$config['website_baseurl']}register_agent.php" target="_self">
                            <div class="icon blinknotice"><i class="fas fa-user"></i></div>
                            <span class="title hot">{$tr['membercenter_register_agent']}</span>
                        </a>
                    HTML;
                }
            }


//----------------------更新系統錢包(會員中心選單用)------------------------------
            if($_SESSION['member']->gtoken_lock != NULL) {
                // 更新系統錢包(會取回所有娛樂城餘額) ,確認要取回所有娛樂城的餘額？
                $reload_balance_icon = '<a href="#" class="ml-2 reload_balance_icon" title="'.$tr['confirm get all casino back'].'"><span class="gtokenrecycling_status"><span class="mdi mdi-18px mdi-coin gtokenrecycling_balance" aria-hidden="true"></span></span></a>';
                //执行中，请稍侯...
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
                        $(document).ready(function() {
                            $('.gtokenrecycling_balance').click(function(){
                                var gtokenrecycling = 1;

                                if(confirm('".$confirm_text."')){
                                    $('.gtokenrecycling_balance').attr('disabled', 'disabled');
                                    ".$run_win_js."
                                }else{
                                    //放棄,取回所有娛樂城的餘額!!
                                    alert('".$tr['giveup get all casino back']."');
                                }

                            });

                        });
                    </script>
                ";
                }else{
                    $reload_balance_icon = '';
                    $gtokenrecycling_js = '';
                }
//---------更新系統錢包 end    -------------------------------------

//---------會員中心選單(登入後)-------------------------------------------
            if($template == 'mobile' ){
            // mobile版本會員資訊
            $member_login_head= menu_login_ui();
            $menu_mobile_content_userinfo = '<div class="account-info">'.$member_login_head.'</div>';
            /*
            $menu_mobile_content_userinfo = $member_login_head.'

            <div class="container account-top">
            <div class="account-language">
            '.mobile_menu_language_choice().'
            </div>
            <div class="account-notice py-2 mr-3">
            '.stationmail_member_messages_count($_SESSION['member']->account)['html'].receivemoney_messages($_SESSION['member']->account)['html'].'
            </div>
            </div>
            <div class="container media">
              <div class="account_img mr-3">
                  '.$therole_icon_html.'
                  </span>
                </div>
                <div class="media-body pb-1">

                  <div class="text-name"><strong>'.$showname.'</strong></div>
                  <div class="text-light" id="gtokenrecycling_balance_btn"><span class="reload_balance" onclick="gtokenrecycling();">'.$tr['account balance'].'：'.number_format($_SESSION['member']->gcash_balance+$_SESSION['member']->gtoken_balance,2).'</span>
                  '.$reload_balance_icon.$gtokenrecycling_js.'
                  </div>
                </div>
                  </div>
              </div>


            <script>
            function gtokenrecycling(){
                var csrftoken = \''.$csrftoken.'\';
                $(\'#gtokenrecycling_balance_btn>span\').html("<span class=\'badge badge-warning\'><i class=\'fa fa-spinner fa-spin fa fa-fw mr-2\'></i>载入中...</span>");
                $.post(\'login_action.php?a=reload_balance\',
                    { send_reload_balance: false,    csrftoken: csrftoken },
                    function(result){
                        setTimeout(function(){ $(\'.reload_balance\').html(result); }, 300);
                    }
                );
            }
            gtokenrecycling();
            </script>';*/
                $menu_admin_management['menu'] = $menu_static_content_menu;
                $menu_admin_management['userinfo'] = $menu_mobile_content_userinfo;
                // if($template == 'mobile' OR $template == 'mars' ){
                //     $menu_admin_management['userinfo'] = $menu_admin_management['userinfo'].stationmail_member_messages_count($_SESSION['member']->account)['html'];
                //     $menu_admin_management['userinfo'] = $menu_admin_management['userinfo'].receivemoney_messages($_SESSION['member']->account)['html'];
                //     $menu_admin_management['userinfo'] = $menu_admin_management['userinfo'].'
                //     <button class="btn-mini btn-after-login-logout-change" id="submit_to_logout" onclick="member_logout();" ><span class="glyphicon glyphicon-log-out"><span></button>
                //     '.member_logout_html();
                // }
            }
            elseif($template == 'mars' ){
            // 戰神首頁版登入會員資訊
            $menu_static_content_userinfo = '
            <div class="account-info py-2">
            <div class="media">
            <div class="account_img mr-3">
                  '.$therole_icon_html.'
                  </span>
                </div>
                <div class="media-body pb-1">
                  <div class="account-notice mr-3">
                 '.stationmail_member_messages_count($_SESSION['member']->account)['html'].receivemoney_messages($_SESSION['member']->account)['html'].'
                 </div>
                 <div class="text-name"><strong>'.$showname.'</strong></div>
                </div>
                <div class="text-light" id="gtokenrecycling_balance_btn"><span class="reload_balance" onclick="gtokenrecycling();">'.$tr['account balance'].'：'.number_format($_SESSION['member']->gcash_balance+$_SESSION['member']->gtoken_balance,2).'</span>
                '.$reload_balance_icon.$gtokenrecycling_js.'
                </div>
                </div>
              </div>
            <script>
            function gtokenrecycling(){
                var csrftoken = \''.$csrftoken.'\';
                $(\'#gtokenrecycling_balance_btn>span\').html("<span class=\'badge badge-warning\'><i class=\'fa fa-spinner fa-spin fa fa-fw mr-2\'></i>'.$tr['now loading'].'...</span>");
                $.post(\'login_action.php?a=reload_balance\',
                    { send_reload_balance: false,    csrftoken: csrftoken },
                    function(result){
                        var resulthtml = JSON.parse(result);
                        setTimeout(function(){ $(\'.reload_balance\').html(resulthtml.balance);
                        $(".reload_balance").attr("data-original-title",resulthtml.tooltip);
                        $(\'[data-toggle="tooltip_balance"]\').tooltip(\'hide\');}, 300);
                    }
                );
            }
            gtokenrecycling();
            </script>';
                $menu_admin_management['menu'] = $menu_static_content_menu;
                $menu_admin_management['userinfo'] = $menu_static_content_userinfo;
                // if($template == 'mobile' OR $template == 'mars' ){
                //     $menu_admin_management['userinfo'] = $menu_admin_management['userinfo'].stationmail_member_messages_count($_SESSION['member']->account)['html'];
                //     $menu_admin_management['userinfo'] = $menu_admin_management['userinfo'].receivemoney_messages($_SESSION['member']->account)['html'];
                //     $menu_admin_management['userinfo'] = $menu_admin_management['userinfo'].'
                //     <button class="btn-mini btn-after-login-logout-change" id="submit_to_logout" onclick="member_logout();" ><span class="glyphicon glyphicon-log-out"><span></button>
                //     '.member_logout_html();
                // }
            }else{
//------------ 會員資訊(桌機會員中心下拉)--------------------------------------------
                $menu_static_content_userinfo = '
  <div class="media p-2 py-3 account-info">
     <div class="account_img mr-3">
     '.$therole_icon_html.'
     </div>
     <div class="media-body">
       <div class="account-notice">
       '.stationmail_member_messages_count($_SESSION['member']->account)['html'].receivemoney_messages($_SESSION['member']->account)['html'].'
       </div>
       <div class="text-name"><strong>'.$showname.'</strong></div>
       <div class="text-light">'.$tooltip_therole_show_html.'</div>
    </div>
  </div>
    <div class="media p-2 account-balance">
     <div class="media-body d-flex justify-content-center">
       <div class="text-light" id="gtokenrecycling_balance_btn"><span class="m-0 reload_balance" data-toggle="tooltip_balance"
       data-original-title="'.$tr['click to update balance'].'" onclick="gtokenrecycling();">'.$tr['account balance'].'：'.number_format($_SESSION['member']->gcash_balance+$_SESSION['member']->gtoken_balance,2).'
       </span>'.$reload_balance_icon.$gtokenrecycling_js.'</div>
     </div>
   </div>
                    <script>
                    function gtokenrecycling(){
                        $(\'[data-toggle="tooltip_balance"]\').tooltip(\'hide\');
                        var csrftoken = \''.$csrftoken.'\';
                        $(\'.reload_balance\').html("<span class=\'badge badge-warning\'><i class=\'fa fa-spinner fa-spin fa fa-fw mr-2\'></i>'.$tr['now loading'].'...</span>");
                        $.post(\'login_action.php?a=reload_balance\',
                            { send_reload_balance: false,    csrftoken: csrftoken },
                            function(result){
                                var resulthtml = JSON.parse(result);
                                setTimeout(function(){ $(\'.reload_balance\').html(resulthtml.balance);
                                $(".reload_balance").attr("data-original-title",resulthtml.tooltip);
                                $(\'[data-toggle="tooltip_balance"]\').tooltip(\'hide\'); }, 300);
                            }
                        );
                    }
                    gtokenrecycling();
                    </script>';

                $menu_static_content = $menu_static_content_userinfo.$menu_static_content_menu;
            // 關於我們、合作夥伴、會員中心 (0801byjoyce)
                $menu_admin_management = '
                <li><span class="weltitle mr-2">'.$showname. $tr['welcome'] . '</span></li>
                <!--<li><a href="#" target="_self" onclick="member_logout();"><span class="glyphicon glyphicon-log-in mr-2" aria-hidden="true"></span>'.$tr['Logout'].'</a></li>-->
                <li class="dropdown">
                  <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                  <span class="glyphicon glyphicon-user" aria-hidden="true"></span>
                  '.$tr['Member Centre'].'
                  <span class="caret"></span></a>
                  <div id="head_member_menu" class="dropdown-menu py-0">
                  '.$menu_static_content.'
                  <div class="m-0 mt-2 rounded-0 logout btn btn-danger btn-logout p-0"><a class="nav-link" href="'.$config['website_baseurl'].'login_action.php?a=logout"><span class="glyphicon glyphicon-log-in mr-2" aria-hidden="true"></span>'.$tr['Logout'].'</a></div>
                  </div>
                </li>';

                // 加上 i18 語言切換
                // $menu_admin_management = $menu_admin_management.menu_language_choice();
            }
        }else{
            $menu_admin_management='
            <li><span class="weltitle">'. $showname . $tr['welcome'] . '</span></li>
            <!--<li><a href="#" target="_self" onclick="member_logout();"><span class="glyphicon glyphicon-log-in mr-2" aria-hidden="true"></span>'.$tr['Logout'].'</a></li>-->
            <li class="topreturn"><a href="'.$config['website_baseurl'].'" target="_self"><span class="glyphicon glyphicon-share-alt mr-2" aria-hidden="true"></span>' . $tr['Return Home'] . '</a>';
        }
    // mobile會員中心選單(未登入)
    }elseif($template == 'mobile'){
        if(isset($_SESSION['member']) AND $_SESSION['member']->therole == 'T'){
            //測試帳號
            $tooltip_therole_show_html = $tr['test account'];
            $therole_icon_html = '<span class="glyphicon glyphicon-eye-open" aria-hidden="true"  data-toggle="tooltip_therole" data-placement="left" title="'.$tooltip_therole_show_html.'"></span>';
        }else{
            //測試帳號
            $tooltip_therole_show_html = $tr['Visitor'];//'游客'
            $therole_icon_html = '<span class="glyphicon glyphicon-eye-open" aria-hidden="true"  data-toggle="tooltip_therole" data-placement="left" title="'.$tooltip_therole_show_html.'"></span>';
        }
        $menu_admin_management['menu'] = '';
        $menu_admin_management['userinfo'] = '
  <div class="account-info py-2">
      <div class="container account-top">
    <div class="account-language">
    '.mobile_menu_language_choice().'
    </div>
    </div>
  <div class="container media">
    <div class="account_img mr-3">
     <span class="glyphicon glyphicon-user" aria-hidden="true" data-toggle="tooltip_therole" data-placement="left" title="'.$tooltip_therole_show_html.'"></span>
    </div>
    <div class="media-body">
     <h5>'.$tooltip_therole_show_html.'</h5>
    </div>
  </div>
  </div>
';
    }else{

        // 選單 -- 訪客權限的選單以及有登入的時候的選單
        $menu_static_content = NULL;


        // 一般會員有登入的話，顯示選單. 沒有登入的也顯示註冊選單.
        if(!isset($_SESSION['member']) OR $_SESSION['member']->therole == 'T') {
            $hello_str = (isset($_SESSION['member']) AND $_SESSION['member']->therole == 'T') ? $tr['welcome back'] :$tr['Not signed'];//'親，歡迎回來'
            // 免費開戶
            $menu_static_content = $menu_static_content.
            '<li><a href="'.$config['website_baseurl'].'register.php" id="new_account"><span class="glyphicon glyphicon-user mr-2" aria-hidden="true"></span>'.$tr['Free account']    .'</a></li>';
            // 免費試玩
            //$menu_static_content = $menu_static_content.
            //'<li><a href="'.$config['website_baseurl'].'trial.php">'.$tr['Free Trial'].'</a></li>';
        }
        // 如果使用者都沒有登入的話，都沒有資料，就不用輸出。
        // 關於我們、合作夥伴、免費開戶 (0801byjoyce)
        if($menu_static_content == NULL) {
            $menu_admin_management = '';
        }else{
            $menu_admin_management = '
            <li><span class="weltitle">'.$hello_str.'</span></li>
            <!--<li><a href="'.$config['website_baseurl'].'login2page.php" target="_self"><span class="glyphicon glyphicon-log-in mr-2" aria-hidden="true"></span>' . $tr['Login'] . '</a></li>-->
            '.$menu_static_content;
        }
    }

//login2page member 樣板的狀況下輸出不同選單
    if($template == 'login2page'){
        $menu_admin_management='';
        $menu_admin_management=$menu_admin_management.'
            <li><span class="weltitle">'.$tr['Not signed'].'</span></li>
            <li><a href="'.$config['website_baseurl'].'register.php" target="_self"><span class="glyphicon glyphicon-user mr-2" aria-hidden="true"></span>'.$tr['Free account'].'</a></li>
            <li><a href="'.$config['website_baseurl'].'" target="_self"><span class="glyphicon glyphicon-share-alt mr-2" aria-hidden="true"></span>'.$tr['Return Home'].'</a></li>';

    }elseif ($template == 'member') {
        $menu_admin_management='';
        $menu_admin_management=$menu_admin_management.'
            <li><span class="weltitle">'.$tr['Not signed'].'</span></li>
            <li><a href="'.$config['website_baseurl'].'login2page.php" target="_self"><span class="glyphicon glyphicon-log-in mr-2" aria-hidden="true"></span>'.$tr['Login'].'</a></li>
            <li><a href="'.$config['website_baseurl'].'" target="_self"><span class="glyphicon glyphicon-share-alt mr-2" aria-hidden="true"></span>'.$tr['Return Home'].'</a></li>';

    }

    return($menu_admin_management);
}

// ----------------------------------------------------------------------------
// 選單 -- 會員登入選單，提供會員登入、登出的功能
// 相關檔案：
// ----------------------------------------------------------------------------
function menu_login_ui()
{
    global $csrftoken;
    global $system_config;
    global $cdnfullurl_js;
    global $cdnfullurl;
    global $cdnrooturl;
    global $tr;
    global $config;
    global $tmpl;
    $casinoLib = new casino_lib();

    // ---------------------------
    // 圖片驗證功能
    // ---------------------------

    // ---------------------------
    // ajax 回應的訊息顯示區塊
    $preview_status = '<div class="preview_text" id="preview_status"></div>';
    // ---------------------------

    // 底下的 if.. else 動作為, 會員登入功能(還沒登入) , 登入畫面 驗證文字 忘記密碼 新開帳號
    // 登入後顯示 帳戶 訊息 餘額 更新 登出 修改密碼

    $member_login_html = '';
    $member_login_show_captcha_js = '';
    $member_login_click_captcha_js= '';

    if(isset($_SESSION['member'])) {

        // -------------------------------
        // 已登入，使用者登入後，顯示的相關訊息
        // -------------------------------
        // 顯示帳號
        $show_name = $_SESSION['member']->account;

        // 更新會員的個資及餘額狀態, 目前專門給： menu_login_ui function 使用
        $member_balance_reload_result = member_balance_reload($_SESSION['member']->id);

        // 取得未讀站內信件訊息數量. 有資料顯示 html 沒有資料不显示,  目前專門給： menu_login_ui function 使用
        $show_messages_count_result = stationmail_member_messages_count($_SESSION['member']->account);
        $show_messages_count = $show_messages_count_result['html'];

        // 彩金領取入款通知資訊, 帳號, 接收時間為空, 狀態為 1, 截止時間前領取
        $receivemoney_messages_result = receivemoney_messages($_SESSION['member']->account);
        $show_deposit_count = $receivemoney_messages_result['html'];

        // 當代幣在娛樂城的時候,才顯示 JS and icon
        // 餘額狀態顯示欄位, 如果有在娛樂城用不同顏色顯示
        if($_SESSION['member']->gtoken_lock != NULL) {
            // 更新系統錢包(會取回所有娛樂城餘額) ,確認要取回所有娛樂城的餘額？
            $reload_balance_icon = '<button id="gtokenrecycling_balance" onclick="gtokenrecycling_balance()" class="ml-2 btn btn-sm btn-warning" href="#" title="'.$tr['confirm get all casino back'].'">'.$tr['retrieve'].'</button>';
            //执行中，请稍侯...
            $run_win_js = "
            var wait_text = '<img height=\"20\" width=\"20\" src=\"".$cdnrooturl."spinner.gif\">';
            var finish_text = '<span id=\"gtokenrecycling_balance\" class=\"mdi mdi-18px mdi-coin\" aria-hidden=\"true\"></span>';
            var csrftoken = '".$csrftoken."';
            $('#gtokenrecycling_status').html(wait_text);
            $.get('gamelobby_action.php',
                    { a: 'Retrieve_Casino_balance', csrftoken: csrftoken },
                    function(result){
            if(result.logger){
                            $(\".reload_balance\").html(result.gtoken_b_m);
            }
                        setTimeout(function(){ window.location.reload(\"wallets.php\"); }, 15000);
                        $('#gtokenrecycling_status').html(finish_text);
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
/*        $('#gtokenrecycling_balance').click(function(){
            var gtokenrecycling = 1;

            if(confirm('".$confirm_text."')){
                $('#gtokenrecycling_balance').attr('disabled', 'disabled');
                ".$run_win_js."
            }else{
                //放棄,取回所有娛樂城的餘額!!
                alert('".$tr['giveup get all casino back']."');
            }

        });*/
        }else{
            $reload_balance_icon = '';
            $gtokenrecycling_js = '';
        }
      // 更新系統錢包 end



        // 描述使用者身份的中文說明
        $tooltip_therole_show_html = '';
        // 依據使用者身份，決定前方的 icon
        $therole_icon_html = '';
        if($_SESSION['member']->therole == 'M') {
            //會員
            $tooltip_therole_show_html = $tr['member'];
            $therole_icon_html = '<span class="glyphicon glyphicon-user" aria-hidden="true"  data-toggle="tooltip_therole" data-placement="left" title="'.$tooltip_therole_show_html.'"></span>';
        }elseif($_SESSION['member']->therole == 'A') {
            // '加盟联营股东' = 代理商
            //<span onclick="javascript:location.href=\'agencyarea_summary.php\'">  取消常駐選單代理商頭像連結
            $tooltip_therole_show_html = $tr['agent'];
            $therole_icon_html = '<span class="glyphicon glyphicon-knight" aria-hidden="true"  data-toggle="tooltip_therole" data-placement="left" title="'.$tooltip_therole_show_html.'"></span>';
        }elseif($_SESSION['member']->therole == 'R') {
            //管理員
            $tooltip_therole_show_html = $tr['management'];
            $therole_icon_html = '<span class="glyphicon glyphicon-king" aria-hidden="true"  data-toggle="tooltip_therole" data-placement="left" title="'.$tooltip_therole_show_html.'"></span>';
        }else{
            //測試帳號
            $tooltip_therole_show_html = $tr['test account'];
            $therole_icon_html = '<span class="glyphicon glyphicon-eye-open" aria-hidden="true"  data-toggle="tooltip_therole" data-placement="left" title="'.$tooltip_therole_show_html.'"></span>';
        }


        // 顯示帳戶餘額及幣值
        // --------------------
        // 從錢包 session 取得餘額的狀態, 如果更新坐在 reload balacne 按鈕上面
        $show_currencybalance_gcash = $_SESSION['member']->gcash_balance;
        $show_currencybalance_gtoken = $_SESSION['member']->gtoken_balance;
        // 把兩個錢包合成一個顯示, 餘額加總
        $show_currencybalance = $_SESSION['member']->gtoken_balance + $_SESSION['member']->gcash_balance;
        // 用 $ 標準格式顯示
        //$show_currencybalance_fmt_html = money_format('%i', $show_currencybalance);
        // $show_currencybalance_fmt_html = '<span class="glyphicon glyphicon-yen" aria-hidden="true"></span>'.$show_currencybalance;
        $show_currencybalance_fmt_html = '$'.number_format($show_currencybalance,2);
        $hide_gcash_mode = hide_gcash_mode();//隱藏現金模式

        // 餘額狀態顯示欄位, 如果有在娛樂城用不同顏色顯示
        if($_SESSION['member']->gtoken_lock == NULL) {
            //現金 + 代幣 合併顯示, 滑鼠移動才提示
            if ($hide_gcash_mode == 'on') {
                $reload_balance_title_text = $tr['token'].$show_currencybalance_gtoken;
            }else{
                $reload_balance_title_text = $tr['cash'].$show_currencybalance_gcash.','.$tr['token'].$show_currencybalance_gtoken;
            }
            $reload_balance_area_html = '<span class="badge badge-success" title="">'.$show_currencybalance_fmt_html.'<span class="glyphicon glyphicon-refresh ml-2" aria-hidden="true"></span></span>';
    }else{
            // 代幣在娛樂城, 用不同顏色顯示
        if ($hide_gcash_mode == 'on') {
            $reload_balance_title_text = $tr['token'] .$show_currencybalance_gtoken.'@'.$casinoLib->getCasinoNameByCasinoId($_SESSION['member']->gtoken_lock, $_SESSION['lang']);
        }else{
            $reload_balance_title_text = $tr['cash'].$show_currencybalance_gcash.','.$tr['token'] .$show_currencybalance_gtoken.'@'.$casinoLib->getCasinoNameByCasinoId($_SESSION['member']->gtoken_lock, $_SESSION['lang']);
        }
            $reload_balance_area_html = '<span class="badge badge-danger member_balance" title="">'.$show_currencybalance_fmt_html.'<span class="glyphicon glyphicon-refresh ml-2" aria-hidden="true"></span></span>';
    }
        // 提示文字 , 點擊立即更新目前餘額
        $tooltip_banance_show_html = $tr['click to update balance'].','.$reload_balance_title_text;

        // --------------------------------------
        // 橫式 login 頁面 -- 已經登入
        // --------------------------------------
    if($config['site_style']=='desktop'){
        $member_login_html = $member_login_html.<<<HTML
        <div class="login-table-on">
            <div class="btn-group btn-group-sm after-login-account" role="group" aria-label="ACCOUNT">
                <button class="btn btn-light btn-after-login" type="button">
                $therole_icon_html
                $show_name
                </button>
            </div>

            <div class="after-login-info member_datamessage" role="group" aria-label="BALANCE">
                <div class="account-notice">
                $show_messages_count
                $show_deposit_count
                </div>
                <div id="submit_reload_balance" onclick="reload_balance()">
                    <div id="reload_balance_area" class="reload_balance" data-toggle="tooltip_balance" data-placement="bottom" title="$tooltip_banance_show_html">
                    $reload_balance_area_html
                    </div>
                </div>
                $reload_balance_icon
            </div>

            <div class="btn-group btn-group-sm after-login-control" role="group" aria-label="BTN">
                <button class="btn btn-primary btn-after-login-logout-change" id="submit_to_chpassword" type="button" onclick="location.href='{$config['website_baseurl']}member_changepwd.php'" title="{$tr['Change Password']}"><i class="fas fa-key"></i></button>
                <button class="btn btn-danger btn-after-login-logout-change" id="submit_to_logout" type="button" >{$tr['Logout']}</button>
            </div>

            <div>
                $preview_status
            </div>
        </div>
HTML;
        $reload_balance_js="
        function reload_balance(){
            $('.reload_balance').html(\"<span class='badge badge-warning'><i class='fa fa-spinner fa-spin fa fa-fw mr-2'></i>".$tr['now loading']."...</span>\");
            var send_reload_balance  = 'true';
            var csrftoken = '".$csrftoken."';
            $.post('login_action.php?a=reload_balance',
                { send_reload_balance: send_reload_balance,    csrftoken: csrftoken },
                function(result){
                    var resulthtml = JSON.parse(result);
                    setTimeout(function(){ $('.reload_balance').html(resulthtml.balance);
                    $(\".reload_balance\").attr(\"data-original-title\",resulthtml.tooltip);
                    $('[data-toggle=\"tooltip_balance\"]').tooltip('hide'); }, 300);
                });
        }
        ";
    }else{
        $member_login_html = $member_login_html.<<<HTML
            <div class="d-flex member_header_img">
                <div id="member-icon" class="col-auto my-auto">$therole_icon_html</div>
                <div class="col-auto account_information">
                    <div id="account_text">{$tr['Account']}：$show_name</div>
                    <div id="balance_row">{$tr['total balance']}：<div id="submit_reload_balance" onclick="reload_balance()">
                            <div id="reload_balance_area" class="reload_balance" data-toggle="tooltip_balance" data-placement="bottom" data-original-title="$tooltip_banance_show_html">
                            $reload_balance_area_html
                            </div>
                        </div>
                        <div class="reload_balance_icon">
                        $reload_balance_icon
                        </div>
                    </div>
                </div>
            </div>
HTML;
        $balance_modal =<<<HTML
        <div id="reload_balance_modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div id="balance_modal_content" class="text-center my-3"></div>
                </div>
              </div>
            </div>
HTML;
        $reload_balance_js="
        function reload_balance(){
            $('.reload_balance').html(\"<span class='badge badge-warning'><i class='fa fa-spinner fa-spin fa fa-fw mr-2'></i>".$tr['now loading']."...</span>\");
            var send_reload_balance  = 'true';
            var csrftoken = '".$csrftoken."';
            $.post('login_action.php?a=reload_balance',
                { send_reload_balance: send_reload_balance,    csrftoken: csrftoken },
                function(result){
                    var resulthtml = JSON.parse(result);
                    setTimeout(function(){ $('.reload_balance').html(resulthtml.balance);
                    $(\"#balance_modal_content\").html(resulthtml.mobile_modal);
                    $('#reload_balance_modal').modal('show'); }, 300);
                });
        }
        ";
    }

        // 登出按鈕, 此功能不帶變數.
        // 以及更新金錢餘額的按鈕, 此功能不帶任何變數
        $member_login_js = "
        $('#submit_to_logout').click(function(){
            member_logout();
        });";
        //$member_login_js = "";

        // 顯示餘額 alert and tooltip , 點擊需要更新餘額。
        if($config['site_style']=='desktop'){
            $member_login_js = $member_login_js."
            $('[data-toggle=\"tooltip_balance\"]').tooltip();
            $('[data-toggle=\"tooltip_therole\"]').tooltip();
            ";
        }
        /*
        $reload_balance_js="
        function reload_balance(){
            $('.reload_balance').html(\"<span class='badge badge-warning'><i class='fa fa-spinner fa-spin fa fa-fw mr-2'></i>载入中...</span>\");
            var send_reload_balance  = 'true';
            var csrftoken = '".$csrftoken."';
            $.post('login_action.php?a=reload_balance',
                { send_reload_balance: send_reload_balance,    csrftoken: csrftoken },
                function(result){
                    setTimeout(function(){ $('.reload_balance').html(result); }, 300);
                });
        }
        ";*/
        //$('#submit_reload_balance').click(
        //});
        //会员登出专用的 logout JS, 执行会回传 logout 的 html JS code
        $member_login_keypress_js = member_logout_html();
        // -------------------------------


    }else{
        // -------------------------------
        // 未登入，等待使用者登入的畫面
        // -------------------------------
        $member_login_html = $member_login_html.<<<HTML
        <form>
        <div class="login-table justify-content-end">
            <div class="login-table-account-td">
                    <input id="account_input" class="form-control form-control-sm" name="Account" type="text" size="2" maxlength="13" placeholder="{$tr['Account']}" aria-describedby="sizing-addon3" autocomplete="new-account">
            </div>
            <div class="login-table-password-td">
                    <input id="password_input" class="form-control form-control-sm" name="Password" type="password" size="2" maxlength="20" placeholder="{$tr['Password']}" aria-describedby="sizing-addon3" autocomplete="new-password">
            </div>
            <div class="login-table-verification-td">
                    <input name="captcha" class="form-control form-control-sm" id="captcha_input" type="text" size="2"  maxlength="4" placeholder="{$tr['Verification']}"  aria-describedby="sizing-addon3" autocomplete="new-password">
                    <span id="show_captcha" class= "show_captcha_css"><img src="{$cdnfullurl_js}img/common/hello.png" id="captcha" alt="{$tr['Verification']}" title="{$tr['Verification']}" height="20" width="50" ></span>
            </div>

            <div class="login-table-login-btn-td mr-0">
                <div class="btn-group-sm" role="group" aria-label="LOGIN">
                    <a href="#" title="{$tr['agree to the User Agreement']}" id="submit_to_login" class="btn btn-login" type="button">{$tr['Login']}</a>
                    <a href="{$config['website_baseurl']}contactus.php" id="forgot_password" class="btn btn-forget mr-0" title="{$tr['Forgot Password Contact']}">{$tr['Forgot Password']}</a>
                </div>
            </div>
        </div>
        <div class="preview_status mr-0">
            $preview_status
        </div>
        </form>
HTML;

        // 整合home.php、login2page.php登入action
        // 取得token，導回指定url
        $token = get_token();

        // 如果 get 設定有 force 就加入這一段. 表示剛剛有重複的資料再系統內，需要使用強制登入。
        //if(isset($_GET['f']) AND $_GET['f'] == 1) {
        //    $member_login_html = $member_login_html.'<input type="hidden" id="login_force" value="1">';
        //}

        // 強制登入區塊 + ajax 回傳的區塊
        //$member_login_html = $member_login_html.$preview_status;


        // 登入的表單, JS 動作
        // 按下 submit_to_login , 先顯示 loading 畫面，透過 jquery 送出 post data 到 url 位址 , 成功登入後，才顯示正式的畫面。
        // 登入同時清空驗證碼, 如果重複使用者的話使用者才會需要重新輸入.
        $member_login_js = <<<JAVASCRIPT
        $('#submit_to_login').click(function(){
            if($('#account_input').val() == '') {
                alert('{$tr['Please fill in the account field']}');
            }else    if($('#password_input').val() == '') {
                alert('{$tr['Please fill in the password']}');
            }else if($('#captcha_input').val() == '') {
                alert('{$tr['Please fill in the verification code']}');
            }else{
                var captcha_input  = $('#captcha_input').val();
                var account_input  = $('#account_input').val();
                var password_input  = $().crypt({method:'sha1', source:$('#password_input').val()});
                var login_force      = $('#login_force').val();
                var csrftoken = '{$csrftoken}';

                // 登入整合 自動跳轉到指定url token
                var token = '{$token}';

                $('#captcha_input').val('');

                $.post('login_action.php?a=login_check',
                    { captcha: captcha_input , account: account_input, password: password_input, login_force: login_force, csrftoken: csrftoken,token:token},
                    function(result){
                        if(result.code == '2'){
                            // 登入強制變更密碼
                            $('#useraccount').text(account_input);
                            $('#newpasswordcs').val(result.pwdcsrf);
                            $('#newpasswordcc').val(captcha_input);
                            $('#pwchg').modal({backdrop: 'static', keyboard: false});
                        }else if(result.code == '3'){
                            // 重複登入
                            alert(result.error);
                            window.location = 'home.php';
                        }else if(result.code == '1'){
                            // location.reload();
                            // window.location='home.php';

                            // 可以登入
                            $('#preview_status').html(result.error);
                        }else{
                            $('#preview_status').html(result.error);
                        }
                    },'JSON');

            // 原版
            //         $('#submit_to_login').click(function(){
            // if($('#account_input').val() == '') {
            //     alert('{$tr['Please fill in the account field']}');
            // }else    if($('#password_input').val() == '') {
            //     alert('{$tr['Please fill in the password']}');
            // }else if($('#captcha_input').val() == '') {
            //     alert('{$tr['Please fill in the verification code']}');
            // }else{
            //     var captcha_input  = $('#captcha_input').val();
            //     var account_input  = $('#account_input').val();
            //     var password_input  = $().crypt({method:'sha1', source:$('#password_input').val()});
            //     var login_force      = $('#login_force').val();
            //     var csrftoken = '{$csrftoken}';

            //     $('#captcha_input').val('');

            //     $.post('login_action.php?a=login_check',
            //         { captcha: captcha_input , account: account_input, password: password_input, login_force: login_force, csrftoken: csrftoken },
            //         function(result){
            //             if(result.code == '2'){
            //                 $('#useraccount').text(account_input);
            //                 $('#newpasswordcs').val(result.pwdcsrf);
            //                 $('#newpasswordcc').val(captcha_input);
            //                 $('#pwchg').modal({backdrop: 'static', keyboard: false});
            //             }else if(result.code == '1'){
            //                 // location.reload();
            //                 window.location='home.php';
            //             }else{
            //                 $('#preview_status').html(result.error);
            //             }
            //         },'JSON');

            }
        });
JAVASCRIPT;
        $reload_balance_js='';

        if($system_config['allow_login_passwordchg'] == 'on'){
            $member_login_html .= <<<HTML
            <div class="modal fade bs-example-modal-lg" id="pwchg" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
              <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                  <div class="modal-header">
                    <h4 class="modal-title" id="myModalLabel">{$tr['Password not updated for a while']}</h4>
                  </div>
                  <div class="modal-body">
                            <div class="text">
                                <p><text id='useraccount'></text><span> {$tr['Password not updated for a while alert']}</span></p>
                                <input id="newpasswordcs" class="form-control form-control-sm" name="newpasswordcs" type="hidden" autocomplete="new-newpasswordcs">
                                <input id="newpasswordcc" class="form-control form-control-sm" name="newpasswordcc" type="hidden" autocomplete="new-newpasswordcs">
                            </div>
                            <div class="pwchg-table-newpw">
                                <div class="text ">
                                    <p><span>{$tr['Please enter a new password']}</span></p>
                                </div>
                                <input id="newpassword" class="form-control form-control-sm" name="Password" type="password" size="2" maxlength="12" placeholder="{$tr['Please enter 8 to 12 yards, English mixed password']}" aria-describedby="sizing-addon3" autocomplete="new-newpassword" required>
                            </div>
                            <div class="pwchg-table-newpw-chk">
                                <div class="text ">
                                    <p><span>{$tr['Enter the new password again']}</span></p>
                                </div>
                                <input id="newpassword_chk" class="form-control form-control-sm" name="Password" type="password" size="2" maxlength="12" placeholder="{$tr['Please enter 8 to 12 yards, English mixed password']}" aria-describedby="sizing-addon3" autocomplete="new-newpassword" required>
                            </div>
                     </div>
                     <div class="modal-footer">
                       <button id="loginpwdchg" type="button" class="btn btn-login">{$tr['Identify changes']}</button>
                     </div>
                   </div>
              </div>
            </div>
HTML;

            $member_login_js .= <<<JAVASCRIPT
                $('#loginpwdchg').click(function(){
                    if(!/^[0-9a-zA-Z]{6,12}$/i.test($('#newpassword').val())) {
                        alert('{$tr['Please enter 8 to 12 yards, English mixed password']}');
                        console.log($('#newpassword').val());
                    }else if($('#newpassword_chk').val() == '') {
                        alert('{$tr['Enter the new password again']}');
                    }else if($('#newpassword_chk').val() != $('#newpassword').val()) {
                        alert('{$tr['password does not match the verification password']}');
                    }else{
                        var account_input  = $('#account_input').val();
                        var password_input  = $().crypt({method:'sha1', source:$('#password_input').val()});
                        var npassword_input  = $().crypt({method:'sha1', source:$('#newpassword').val()});
                        var npassword_inputc  = $('#newpassword_chk').val();
                        var captcha_input  = $('#newpasswordcc').val();
                        var csrftoken = $('#newpasswordcs').val();

                        $.post('login_action.php?a=login_pwdchg',
                            { captcha: captcha_input, account: account_input, password: password_input, npassword: npassword_input, npasswordc: npassword_inputc, csrftoken: csrftoken },
                            function(result){
                                if(result.code == '2'){
                                    $('#useraccount').text(account_input);
                                    $('#pwchg').modal({backdrop: 'static', keyboard: false});
                                }else if(result.code == '1'){
                                    // window.location='home.php';
                                    alert(result.msg);
                                    setTimeout(10000,location.reload());
                                }else if(result.error){
                                    $('#preview_status').html(result.error);
                                }else{
                                    $('#preview_status').html(result);
                                }
                            },'JSON');
                        }
                    });
JAVASCRIPT;

        }

        // 點擊 captcha 才reload圖片 -- 條件1
        $member_login_show_captcha_js = "
        $('#captcha_input').click(function(){
            $.post( 'in/captcha/captchabase64.php', function( captchabase64data ) {
                var img_cpatcha_html = '<img src=\"'+captchabase64data+'\" id=\"captcha\" alt=\"".$tr['Verification code']."\" height=\"20\" width=\"58\" >';
                $('#show_captcha').html(img_cpatcha_html);
            });

    });
        ";

        // 使其获得焦点 才顯示圖片 -- 條件2
        $member_login_show_captcha_js = $member_login_show_captcha_js."
        $('#captcha_input').focus(function(){
            $.post( 'in/captcha/captchabase64.php', function( captchabase64data ) {
                var img_cpatcha_html = '<img src=\"'+captchabase64data+'\" id=\"captcha\" alt=\"".$tr['Verification code']."\"  height=\"20\" width=\"58\" >';
                $('#show_captcha').html(img_cpatcha_html);
            });
    });
        ";

        //點圖片才顯示驗證碼
        $member_login_click_captcha_js ="
        $('#show_captcha').click(function(){
            $.post( 'in/captcha/captchabase64.php', function( captchabase64data ) {
                var img_cpatcha_html = '<img src=\"'+captchabase64data+'\" id=\"captcha\" alt=\"".$tr['Verification code']."\" height=\"20\" width=\"58\" >';
                $('#show_captcha').html(img_cpatcha_html);
            });

    });
        ";


        // 按下 enter 後,等於 click 登入按鍵
        $member_login_keypress_js = '
        <script>
        $(function() {
           $(document).keydown(function(e) {
            switch(e.which) {
                case 13: // enter key
                    $("#submit_to_login").trigger("click");
                break;
            }
            });
        });
        </script>
        ';

        // 取回錢包 JS and link
        $gtokenrecycling_js = '';
    }
    // end if else ----



    // ----------------------------------------------------------------------------
    // 加密的 jquery lib http://www.itsyndicate.ca/jquery/
    // 在登入用的 JS
    // $member_login_js_html = "
    // <script>
    //     $(document).ready(function() {
    //         ".$member_login_js."
    //         ".$member_login_click_captcha_js."
    //     });
    //     ".$reload_balance_js."
    // </script>
    // ".$member_login_keypress_js."
    // ".$gtokenrecycling_js;
    if(!isset($balance_modal)){
        $balance_modal='';
    }elseif(isset($tmpl['extend_js'])){
        $tmpl['extend_js'].=$balance_modal;
    }
    $member_login_js_html = "
    <script>
        $(document).ready(function() {
            ".$member_login_js."
            ".$member_login_show_captcha_js."
        });
        ".$reload_balance_js."
    </script>
    ".$member_login_keypress_js."
    ".$gtokenrecycling_js;


    // 最後輸出的 html
    $member_login_output_html = $member_login_html.$member_login_js_html;
    // ----------------------------------------------------------------------------

    return($member_login_output_html);

}
// ----------------------------------------------------------------------------


// ----------------------------------------------------------------------------
// 頁腳顯示 , 每個樣本檔案中都會使用到。
// 放置有 時間計算, 選單, 指紋偵測, analytic
// ----------------------------------------------------------------------------
function page_footer(){
    // 計算時間
    global $program_start_time;
    global $tr;
      global $config;
    global $system_mode;
    global $ui;
    global $tmpl;
    // google analytic and piwiki 網站內容分析
    require_once dirname(__FILE__) ."/analytic.php";

    // ----------------------------------------------------------------------------
    // 帆布指紋偵測機制 , 可以識別訪客的瀏覽器唯一值。
    // ref: http://blog.jangmt.com/2017/03/canvas-fingerprinting.html
    // ----------------------------------------------------------------------------
    $fingerprintsession_html = '<iframe name="print" frameborder="0" src="'.$config['website_baseurl'].'fingerprintsession.php" height="0px" width="100%" scrolling="no">
      <p>Your browser does not support iframes.</p>
    </iframe>';
    // 指紋偵測 iframe
    // ----------------------------------------------------------------------------

    // ----------------------------------------------------------------------------
    // 算累積花費時間, 另一個開始放在 config.php
    $program_spent_time = microtime(true) - $program_start_time;
    // $program_spent_time_html = "<script>console.log('".$program_spent_time."')</script>";
    $program_spent_time_html = "<p>Generate time: $program_spent_time </p>";
    // ----------------------------------------------------------------------------

    //$host_footer_html = $tr['host_footer'];

    // ----------------------------------------------------------------------------
    // fingerprinting + 頁腳選單
    // ----------------------------------------------------------------------------

    // 樣版切換
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443 || getenv('HTTP_X_FORWARDED_PROTO') === 'https') ? "https://" : "http://";
    if($system_mode == 'developer') {
        $sub_sitepath = explode('/',$_SERVER['DOCUMENT_URI'])[0];
        $_SERVER['DOCUMENT_URI'] = preg_replace("/^(\/$sub_sitepath\/)/i", '', $_SERVER['DOCUMENT_URI']);
    }
    if(isset($_SERVER['QUERY_STRING']) AND $_SERVER['QUERY_STRING'] != '' ){
        $currency_uri = $_SERVER['DOCUMENT_URI'].'?'.$_SERVER['QUERY_STRING'];
    }else{
        $currency_uri = $_SERVER['DOCUMENT_URI'];
    }
    if(isset($_SESSION['site_mode']) AND $_SESSION['site_mode'] == 'mobile') {
        $urlnotify = 'computer';
        $web_mode_name =$tr['footer menu_desktop'];
        $chguri = $protocol.$config['desktop_url'].$currency_uri;
    }else{
        $urlnotify = 'mobile';
        $web_mode_name =$tr['footer menu_mobile'];
        $chguri = $protocol.$config['mobile_url'].$currency_uri;
    }
    $switch_js = '
    <script>
    function msitechg(type){
        $.post(\'login_action.php?a=changewebpanel\',
        {t:type},
        function(result){
            // console.log(\'goto \'+result);
            window.location.href = \''.$chguri.'\';
        });
    }
    </script>';

    // -----------------
    // 基本型
    // -----------------

    $host_footer_html = $fingerprintsession_html.'
        <div id="footer-meun" style="display:flex;justify-content: center;">
                <ul class="nav navbar-nav">
                        <li class="navi_f_about"><a href="'.$config['website_baseurl'].'aboutus.php">'.$tr['footer menu_about us'].'</a></li>
                        <li class="navi_f_promotions"><a href="'.$config['website_baseurl'].'promotions.php">'.$tr['footer menu_promotions'].'</a></li>
                        <li class="navi_f_partner"><a href="'.$config['website_baseurl'].'partner.php">'.$tr['footer menu_partner'].'</a></li>
                        <li class="navi_f_howtodeposit"><a href="'.$config['website_baseurl'].'howtodeposit.php">'.$tr['footer menu_How deposit'].'</a></li>
                        <li class="navi_f_howtowithdraw"><a href="'.$config['website_baseurl'].'howtowithdraw.php">'.$tr['footer menu_How Withdrawal'].'</a></li>
                        <li class="navi_f_contactus"><a href="'.$config['website_baseurl'].'contactus.php">'.$tr['footer menu_Contact Us'].'</a></li>
                        <li class="navi_f_mobile"><a href="javascript:void(0);" onclick="msitechg(\''.$urlnotify.'\');">'.$web_mode_name.'</a></li>
                </ul>
        </div>';
        // 把時間塞到最後面角落
    $footer_content_html = $host_footer_html.'<div align="right">'.$config['companyName'].' AT '.$config['website_domainname'].$program_spent_time_html.'</div>';

    // --------------------
    // 手機型
    // --------------------

    $host_footer_mobile_html = $fingerprintsession_html.'
    <div class="menu-bottom">
    <div class="row">
            <div class="col game">
                <i class="fa fa-gamepad" aria-hidden="true"></i>
                <a href="'.$config['website_baseurl'].'gamelobby.php">'.$tr['m_gamelobby'].'</a>
            </div>
            <div class="col wallet">
                <i class="far fa-money-bill-alt" aria-hidden="true"></i>
                <a href="'.$config['website_baseurl'].'menu_admin.php?gid=deposit">'.$tr['m_deposit'].'</a>
            </div>
            <div class="col chat">
                <i class="far fa-comment-dots" aria-hidden="true"></i>
                <a href="'.$config['website_baseurl'].'contactus.php">'.$tr['m_contact'].'</a>
            </div>
            <div class="col promo">
               <i class="fa fa-gift" aria-hidden="true"></i>
               <a href="'.$config['website_baseurl'].'promotions.php">'.$tr['m_promotion'].'</a>
            </div>
            <div class="col home">
                <i class="fa fa-user" aria-hidden="true"></i>
                <a href="'.$config['website_baseurl'].'menu_admin.php">'.$tr['m_membercenter'].'</a>
            </div>
    </div>
    </div>
    ';

    if($config['website_type'] == 'ecshop'){
      $menu_tmpl = $config['template_path']."template/menu_ecshop.tmpl.php";
      if (file_exists($menu_tmpl)){
        include($menu_tmpl);
      }
    }elseif($config['website_type'] == 'casino'){
      $menu_tmpl = $config['template_path']."template/menu_casino.tmpl.php";
      if (file_exists($menu_tmpl)){
        include($menu_tmpl);
      }
    }
  // 把時間塞到最後面角落
    $footer_content = $fingerprintsession_html.$switch_js.$host_footer_html.'<div align="right">'.$config['companyName'].' AT '.$config['website_domainname'].$program_spent_time_html.'</div>';



    $footer_content_mobile = $fingerprintsession_html.$switch_js.$host_footer_mobile_html;

    // ----------------------------------------------------------------------------
    // 紀錄網頁在哪裡 - 前台紀錄行為
    // ----------------------------------------------------------------------------
    //$who 誰在那個頁面操作
    if(isset($_SESSION['member']->account)){
        $account =     $_SESSION['member']->account;
    }else{
        $account = 'guest';
    }
    $service = 'behavior';
    $message_level = 'info';
    // 傳入想要寫入的訊息, 沒有的話就是空.
    $logger = $_SERVER['HTTP_HOST'];

    $msg=$account.'点击 '.$_SERVER['HTTP_HOST'].'此页面';
    $msg_log = 'lib_menu.php:1152';
    $sub_service='behavior';
    // $r = memberlog 2db("$account","$service","$message_level", "$logger");
    // 2018.06.28 泰哥說，先停掉---點擊頁面，皆寫入memberlog
    // $r = memberlogtodb("$account",'member','info',"$msg","$account","$msg_log",'f',$sub_service);

    // ----------------------------------------------------------------------------

    //根據目前mode輸出手機或桌機版
    if(isset($_SESSION['site_mode']) AND $_SESSION['site_mode'] == 'mobile') {
        return($footer_content_mobile);
    }else{
        return($footer_content);
    }
}



// ----------------------------------------------------------------------------
// 頁面的 menu menber
// 選單 -- 會員登入後的專屬選單, 當代理商登入時，才會顯示代理商專屬的界面。
// ---------------------------------------------------------------------
function menu_member()
{
    global $tr;
    global $config;
    global $customer_service_cofnig;
    global $protalsetting;

    // 中間功能選單
    // ----------------------------------------------------------------------------

    $menu_member_item = '';

        // 一般會員有登入的話，顯示選單. 但如果是 T(試用) 則不顯示。
        if(isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T') {
            // 我的消息 - 使用在傳遞站內的系統訊息
            $menu_member_item = $menu_member_item.
            '<li><a href="'.$config['website_baseurl'].'announcement.php" target="_self">
            </span>'.$tr['membercenter_menu_admin_message'].'</a></li>
            ';

            // 交易明細
            $user_account = password_hash($_SESSION['member']->account, PASSWORD_DEFAULT);
            $menu_member_item = $menu_member_item.
            '<li><a href="'.$config['website_baseurl'].'mo_translog_query.php?transpage=m&id='.$_SESSION['member']->id.'">
            </span>'.$tr['membercenter_mo_transaction_log'].'</a></li>';

            // 投注明細
            $menu_member_item = $menu_member_item.
            '<li><a href="'.$config['website_baseurl'].'moa_betlog.php?betpage=m&id='.$_SESSION['member']->id.'">
            '.$tr['membercenter_betrecord'].'</a></li>';

            // opencart 交易紀錄
            if($config['website_type'] == 'ecshop'){
                $menu_member_item = $menu_member_item.
                '<li><a href="'.$config['website_baseurl'].'shoppingrecord.php">'.$tr['Shopping records'].'</a></li>';
            }

            // 財務中心
            $menu_member_item = $menu_member_item.
            '<li><a href="'.$config['website_baseurl'].'deposit.php">'.$tr['membercenter_menu_admin_deposit'].'</a></li>';

            // 安全中心
            $menu_member_item = $menu_member_item.
            '<li><a href="'.$config['website_baseurl'].'member.php">'.$tr['membercenter_menu_admin_safe'].'</a></li>';

            // 線上存款
            /*
            $menu_member_item = $menu_member_item.
            '<li><a href="'.$config['website_baseurl'].'deposit.php"><span class="glyphicon glyphicon-credit-card" aria-hidden="true">
            </span>'.$tr['Online deposit'].'</a></li>';
            */
            // 代理商專區功能 , 只有代理商或是管理員才可以進入。
            if($_SESSION['member']->therole == 'R' OR $_SESSION['member']->therole == 'A') {
                $menu_member_item = $menu_member_item.
                '<li><a href="'.$config['website_baseurl'].'agent_instruction.php">'.$tr['membercenter_menu_admin_agent'].'</a></li>';
            }


               // 申請代理商 , 只有會員才可以進入。
            if ($_SESSION['member']->therole === 'M') {
                // 全民代理是否開啟
                if ( isset($protalsetting['national_agent_isopen']) && $protalsetting['national_agent_isopen'] === 'on' ) {
                    $menu_member_item .= <<<HTML
                        <li class="blinknotice">
                            <a href="{$config['website_baseurl']}allagent_register.php">
                                <span class="glyphicon glyphicon-user" aria-hidden="true"></span>{$tr['membercenter_allagent_register']}
                            </a>
                        </li>
                    HTML;
                // 代理商申請是否開啟
                } else if ( isset($protalsetting['agent_register_switch']) && $protalsetting['agent_register_switch'] === 'on' ) {
                    $menu_member_item .= <<<HTML
                        <li class="blinknotice">
                            <a href="{$config['website_baseurl']}register_agent.php">
                                <span class="glyphicon glyphicon-user" aria-hidden="true"></span>{$tr['membercenter_register_agent']}
                            </a>
                        </li>
                    HTML;
                }
            }
        } else {
            $menu_member_item = '';
        }


    // 輸出
    $menu_member_output_html = '
    <nav>
      <ul class="main-menu">
                '.$menu_member_item.'
      </ul>
    </nav>';

    return($menu_member_output_html);
}
// ---------------------------------------------------------------------



// ---------------------------------------------------------------------
// 前台的時間顯示 -- 美東時間 JS 顯示
// user: menu_time()
// ---------------------------------------------------------------------
function menu_time() {
    global $tr;

    $menu_time_html = '';
    // ------------------------------------------------------------------------------
    // javascript 即時顯示美東時間
    // 需要搭配 http://momentjs.com/timezone/ 否則時區問題不好處理 https://github.com/moment/moment-timezone/
    // ------------------------------------------------------------------------------
    $timezone_area_text['zh-hk']     = '美東時間';
    $timezone_area_text['zh-cn']     = '美东时间';
    $timezone_area_text['en']         = 'Eastern Time';
    //$timezone_area_text = $tr['EDT(GMT -5)'];

    if(isset($_SESSION['lang']) AND $_SESSION['lang'] == 'zh-tw') {
        $locale_code = 'zh-cn';
    }elseif(isset($_SESSION['lang']) AND $_SESSION['lang'] == 'zh-cn') {
        $locale_code = 'zh-cn';
    }else{
        $locale_code = 'en';
    }
    //moment.locale('en');
    //moment.locale('zh-hk');
    //moment.locale('zh-cn');

    // https://momentjs.com/docs/#/displaying/
    // 統一設定為 美東時間(夏令時間, 相對於中原時間-12小時), 沒有日光節約的一小時。所以使用 GMT+4
    // 因為他媽的最大的那個公司應該沒有想到日光節約所以 -12 變成了業界不成文標準。
    $showtime_js = "
    <script>
        $( document ).ready(function() {
            ShowTime();
        });

        function ShowTime(){
            moment.locale('$locale_code');
            var d = new Date();
            var d_withtimezone = moment().tz('Etc/GMT+4').format('YYYY/MM/DD(dd) HH:mm:ss')
            document.getElementById('showtimebox').innerHTML = d_withtimezone;
            setTimeout('ShowTime()',1000);
        }
    </script>
    ";

    // 預設以美東時間當顯示，因為很多遊戲都是以美東時間計算，如不顯示客戶容易誤解。
    // 美東時間 == America/St_Thomas
    $timezone_area = 'America/St_Thomas';
    // PHP 的時區計算
    date_default_timezone_set($timezone_area);
    $date_hour = date("Y/m/d h:m:s a");
    // var_dump($date_hour);

    $menu_time_html ='
    <li class="dropdown-lang estime">
    <a href="#" title="'.$timezone_area_text[$locale_code].'"><span id="showtimebox"></span></a>
    </li>
    ';

    // 加上 JS
    $menu_time_html = $menu_time_html.$showtime_js;

return($menu_time_html);
}
// ---------------------------------------------------------------------

// -------------------------------------------------------------------
// 前台 - 代理中心
// 最上面的選單索引 -- 加盟聯營協助註冊  加盟聯營股東會員轉帳 我的組織 代理收入摘要
// -------------------------------------------------------------------
function menu_agentadmin($action_menu) {
    global $tr;

    if(isset($_SESSION['member']) AND $_SESSION['member']->therole == 'A' ) {

        $active_link = array( 0,  0, 0, 0, 0, 0, 0);

        // 依据网址, 选择对应 active
        if($action_menu == 'register_agenthelp.php') {
            $active_link[0] = 'class="active"';
        }elseif($action_menu == 'agencyarea_summary.php') {
            $active_link[2] = 'class="active"';
        }elseif($action_menu == 'member_treemap.php') {
            $active_link[3] = 'class="active"';
        }elseif($action_menu == 'agencyarea.php') {
            $active_link[1] = 'class="active"';
        }elseif($action_menu == 'spread_register.php') {
            $active_link[4] = 'class="active"';
        }

        if($_SESSION['site_mode'] == 'mobile') {
            $menu_agentadmin_html = '
            <ul class="nav nav-tabs">
                <li '.$active_link[0].'><a href="register_agenthelp.php" title="'.$tr['go ahead'].$tr['agency register'].'" target="_SELF" >'.$tr['agency register'].'</a></li>
                <li '.$active_link[4].'><a href="spread_register.php" target="_SELF" title="'.$tr['go ahead'].$tr['Invitation code'].'" >'.$tr['Invitation code'].'</a></li>
            </ul>
            ';
        }else{
            $menu_agentadmin_html = '
            <ul class="nav nav-tabs">
                <li '.$active_link[0].'><a href="register_agenthelp.php" title="'.$tr['go ahead'].$tr['agency register'].'" target="_SELF" >'.$tr['agency register'].'</a></li>
                <li '.$active_link[1].'><a href="agencyarea.php" target="_SELF" title="'.$tr['go ahead'].$tr['my organization'].'" >'.$tr['my organization'].'</a></li>
                <li '.$active_link[2].'><a href="agencyarea_summary.php" target="_SELF" title="'.$tr['go ahead'].$tr['agemcy income summary'].'" >'.$tr['agemcy income summary'].'</a></li>
                <li '.$active_link[3].'><a href="member_treemap.php" target="_SELF" title="'.$tr['go ahead'].$tr['agency organization chart'].'" >'.$tr['agency organization chart'].'</a></li>
                <li '.$active_link[4].'><a href="spread_register.php" target="_SELF" title="'.$tr['go ahead'].$tr['Invitation code'].'" >'.$tr['Invitation code'].'</a></li>
            </ul>
            ';
        }


    }else{
        $menu_agentadmin_html = '(x) '.$tr['Incorrect identity, please use the agent account to access.'];//身分错误，请使用代理商帐号访问。
    }


    //return($menu_agentadmin_html);
    return '';
}
// -------------------------------------------------------------------


//-------------------------------------------------------------------
/*給templ使用的判定function  決定哪些templ傳回哪些(種)menu*/
// -------------------------------------------------------------------
//中間主功能選單 判定 (區分templ辨別傳回 menu_features或是menu_menu_member)
// -------------------------------------------------------------------

function templ_header_mainmenu($template)
{
    if($template == 'home' OR $template == 'static' OR $template == 'gamelobby'){
        //輸出features 中間主功能
        return menu_features();
    }
    elseif ($template == 'admin' OR $template == 'member') {
        //輸出會員中間選單
        return menu_member();
    }
    else{
        return '';//什麼都沒有(login2page)
    }
}
// -------------------------------------------------------------------
//header 登入系統 判定是否在header出現登入的介面
// -------------------------------------------------------------------
function templ_header_login($template)
{
    if($template == 'login2page' OR $template == 'member'){
        //login2page member 沒有header登入功能
        return '';
    }
    else{
        //輸出header login
        return menu_login_ui();
    }
}
/**-------------------------------------------------------------------
 * 行動裝制版的選單樣板用function
 --------------------------------------------------------------------*/
function mobile_header_menu($tmpl_name){
    global $config;
    global $cdnfullurl;
    global $tr;
    // 美東時間
    $nowtime = menu_time();
    // 左側選單(語言切換列)
    $language_menu = mobile_menu_language_choice();
    // 會員狀態及選單
    $admin_management = menu_admin_management('mobile');

    if(isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T'){
        // 取得未讀站內信件訊息數量. 有資料顯示 html 沒有資料不显示,  目前專門給： menu_login_ui function 使用
        $show_messages_count_result = stationmail_member_messages_count($_SESSION['member']->account);
        $show_messages_count = $show_messages_count_result['html'];

        // 彩金領取入款通知資訊, 帳號, 接收時間為空, 狀態為 1, 截止時間前領取
        $receivemoney_messages_result = receivemoney_messages($_SESSION['member']->account);
        $show_deposit_count = $receivemoney_messages_result['html'];
        $mobile_user_simple_menu =<<<HTML
        <div class="img_iocn_menu">
        <div class="row">
            <div  class="col">
                <div class="img_icon">
                    $show_messages_count
                </div>
                <a href="{$config['website_baseurl']}stationmail.php"><p>{$tr['menu header messages']}</p></a>
            </div>
            <div  class="col">
                <div class="img_icon">
                    $show_deposit_count
                </div>
                <a href="{$config['website_baseurl']}member_receivemoney.php"><p>{$tr['menu header receivemoney']}</p></a>
            </div>
            <div  class="col">
            <a href="{$config['website_baseurl']}deposit.php">
                <div class="img_icon">
                    <i class="far fa-money-bill-alt"></i>
                </div>
                <p>{$tr['menu header deposit']}</p>
            </a>
            </div>
            <div  class="col">
                <a href="{$config['website_baseurl']}wallets.php">
                    <div class="img_icon">
                    <i class="far fa-money-bill-alt"></i>
                    </div>
                    <p>{$tr['menu header wallets']}</p>
                </a>
            </div>
        </div>
        </div>
HTML;
    }else{
        $mobile_user_simple_menu='';
    }

    $mobile_userinfo = $admin_management['userinfo'] ;
        //會員頭像
    //判斷未登入與登入的顯示不同
    if(isset($_SESSION['member'])){
        if(isset($show_messages_count_result)&&isset($receivemoney_messages_result)&&($show_messages_count_result['messages_count']+$receivemoney_messages_result['messages_count'])!= 0 ){
            $has_messages='message-alert';
        }else{
            $has_messages='';
        }
        //登入
        //用icon圖片來代替
        $memberimageicon = '<span class="membercentericon rounded-circle '.$has_messages.'" aria-hidden="true" data-toggle="tooltip_therole" data-placement="left" title="'.$tr['membercenter_menu_admin'].'"><i class="fa fa-user" aria-hidden="true"></i></span>';
        //一般會員
        $member_image_m = '<span class="glyphicon glyphicon-user rounded-circle '.$has_messages.'" aria-hidden="true" data-toggle="tooltip_therole" data-placement="left" title="'.$tr['member'].'"></span>';
        //代理商
        $member_image_a = '<span class="glyphicon glyphicon-knight rounded-circle '.$has_messages.'" aria-hidden="true" data-toggle="tooltip_therole" data-placement="left" title="'.$tr['agent'].'"></span>';
        //管理員
        $member_image_r = '<span class="glyphicon glyphicon-knight rounded-circle '.$has_messages.'" aria-hidden="true" data-toggle="tooltip_therole" data-placement="left" title="'.$tr['management'].'"></span>';
        //測試帳號
        $member_image_test = '<span class="glyphicon glyphicon-eye-open rounded-circle '.$has_messages.'" aria-hidden="true" data-toggle="tooltip_therole" data-placement="left" title="'.$tr['test account'].'"></span>';

        if($_SESSION['member']->therole == 'M') {
            //一般會員
            $membericon = $member_image_m;
        }else if($_SESSION['member']->therole == 'A'){
            //代理商
            $membericon = $member_image_a;
        //管理員
        }else if($_SESSION['member']->therole == 'R'){
            $membericon = $member_image_r;
        //測試帳號
        }else{
            $membericon = $member_image_test;
        }
    }else{
        //未登入
        $membericon = '<span class="membercentericon" aria-hidden="true" data-toggle="tooltip_therole" data-placement="left" title="'.$tr['membercenter_menu_admin'].'"><i class="fas fa-user-slash"></i></span>';
    }
    //回到桌機連結
    $mobile2destop ='<a class="nbox" href="javascript:void(0);" onclick="msitechg(\'computer\');"><div class="icon"><i class="fas fa-desktop"></i></div><span class="title">'.$tr['footer menu_desktop'].'</span></a>';
    //會員中心連結
    $mobile_membercenter ='';

    if($tmpl_name == 'admin'){
        $mobile_menu = $admin_management['menu'];
    }else{
        // 中間功能選單
        $mobile_menu =  menu_features('mobile');
        if(isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T'){
            //會員中心連結
            $mobile_membercenter = '<a class="nbox" href="'.$config['website_baseurl'].'menu_admin.php"><div class="icon"><i class="fas fa-user-circle"></i></div><span class="title">'.$tr['Member Centre'].'</span></a>';
        }
    }
    // 會員登出入
    if(isset($_SESSION['member'])){
        $login_ui_menu = '
        <a class="btn btn-warning btn-sm mr-3" href="'.$config['website_baseurl'].'login_action.php?a=logout"><span class="glyphicon glyphicon-log-in mr-2" aria-hidden="true"></span>'.$tr['Logout'].'</a>
        <a class="btn btn-warning btn-sm topreturn" href="'.$config['website_baseurl'].'" target="_self"><span class="glyphicon glyphicon-share-alt mr-2" aria-hidden="true"></span>'.$tr['Return Home'].'</a>';
    }else{
        $login_ui_menu = '
        <a class="btn btn-warning btn-sm mr-3" href="'.$config['website_baseurl'].'login2page.php" target="_self"><span class="glyphicon glyphicon-log-in mr-2" aria-hidden="true"></span>'.$tr['Login'].'</a>
        <a class="btn btn-warning btn-sm" href="'.$config['website_baseurl'].'register.php"><span class="glyphicon glyphicon-user mr-2" aria-hidden="true"></span>'.$tr['Free account']    .'</a>';
    }
    return array('mobile_userinfo'=>$mobile_userinfo,'mobile_menu'=>$mobile_menu,'mobile2destop'=>$mobile2destop,'mobile_membercenter'=>$mobile_membercenter,'language_menu'=>$language_menu,'login_ui_menu'=>$login_ui_menu,'mobile_user_simple_menu'=>$mobile_user_simple_menu,'membericon'=>$membericon);
}

// ----------------------------------------------------------------------------
// 靜態頁與會員頁側邊次選單生成
// ----------------------------------------------------------------------------
function combine_sidebarmenu($part,$template = null){
    global $tr;
    global $config;
    switch ($part[0]) {
        case 'message':
            $tmp =[
                'announcement'=>$tr['membercenter_announcement'],
                'stationmail'=>$tr['membercenter_stationmail'],
                'member_receivemoney'=>$tr['membercenter_member_receivemoney']

                // 增加未讀數量標示
                // 'stationmail'=>$tr['membercenter_stationmail'].'<span class="badge member_number">'.stationmail_member_messages_count($_SESSION['member']->account)['messages_count'].'</span>',
                // 'member_receivemoney'=>$tr['membercenter_member_receivemoney'].'<span class="badge member_number">'.receivemoney_messages($_SESSION['member']->account)['messages_count'].'</span>'
            ];
            break;
        case 'deposit':
            if($config['site_style']=='desktop'){
                return null;
            }
            $tmp =[
                'deposit'=>$tr['membercenter_deposit'],
                'wallets'=>$tr['membercenter_wallets'],
                'exchange_token'=>$tr['membercenter_exchange_token']
            ];
            break;
        case 'safe':
            $tmp =[
                'member'=>$tr['membercenter_member'],
                'member_changepwd'=>$tr['membercenter_member_changepwd'],
                'member_withdrawalpwd'=>$tr['membercenter_member_withdrawalpwd'],
                'member_banksetting'=>$tr['membercenter_member_banksetting'],
                //'分享给好友'=>'member_share_friends',
                'member_authentication'=>$tr['membercenter_member_authentication']
            ];
            break;
        case 'agent':
            $tmp =[
                'agent_instruction'=>$tr['membercenter_agent_instruction'],
                'spread_register'=>$tr['membercenter_spread_register'],
                'member_management'=>$tr['membercenter_member_management'],
                'mo_translog_query'=>$tr['membercenter_moa_transaction_log'],
                'moa_betlog'=>$tr['membercenter_moa_betlog']
            ];
            if($config['site_style']=='desktop'){
                $tmp['agencyarea_summary'] = $tr['membercenter_agencyarea_summary'];
                $tmp['agencyarea'] = $tr['membercenter_agencyarea'];
            }
            break;
        case 'static':
            if($config['site_style']=='desktop'){
                $tmp =[
                    'aboutus'=>$tr['About us'],
                    /*'优惠活动'=>'promotions',*/
                    'partner'=>$tr['Partner'],
                    'howtodeposit'=>$tr['How to deposit'],
                    'howtowithdraw'=>$tr['How to withdraw'],
                    'contactus'=>$tr['Contact us'],
                ];
            }else{
                $tmp =[
                    'promotions'=>$tr['Pormotions'],
                    'contactus'=>$tr['Contact us'],
                ];
                }
            break;
        default:
            return true;
            break;
    }

    $tmpl['sidebar_content'] = '';
    foreach ($tmp as $key => $value) {
        $active = ($key==$part[1])? ' class="active"':'';
        if($key == 'mo_translog_query'){
            $url = $key . '.php?transpage=a&id='.$_SESSION['member']->id;
        }elseif($key == 'moa_betlog'){
            $url = $key . '.php?betpage=a&id='.$_SESSION['member']->id;
        }else{
            $url = $key . '.php';
        }
        $tmpl['sidebar_content'] .=<<<HTML
        <li{$active}><a href="{$config['website_baseurl']}{$url}">{$value}</a></li>
HTML;
    }
    $tmpl['sidebar_content']='<ul class="sidebar_menu">'.$tmpl['sidebar_content'].'</ul>';

    if(isset($template)){
        $tmpl['sidebar_content'] = str_replace(" _siderbar-menu_ ",$tmpl['sidebar_content'],$template);
    }else{
        $tmpl['sidebar_content']='<div>'.$tmpl['sidebar_content'].'</div>';
    }
    return $tmpl['sidebar_content'];
}

// ----------------------------------------------------------------------------
// banner生成
// ----------------------------------------------------------------------------
function combine_banner($part){
    global $tr;

    $banner_title = $part[0];
    $tmpl['banner']='<div class="banner">
    <div><img src="uic\gp02\img\home\banner20190604.png" alt="'.$tr[$banner_title].'" class="w-100"></div>
    <h3>'.$tr[$banner_title].'</h3>
</div>';

    return $tmpl['banner'];
}

// ----------------------------------------------------------------------------
// game lobby banner生成
// ----------------------------------------------------------------------------
function gamelobby_combine_banner($part){
    global $tr;

    $banner_title = $part[0];
    $tmpl['banner']='
    <h3>'.$tr[$banner_title].'</h3>';

    return $tmpl['banner'];
}

// ----------------------------------------------------------------------------
// menu增加active
// ----------------------------------------------------------------------------
function menu_active($part){
    global $config;

    $url = '"'.$config['website_baseurl'].$part[0].'"';
    $tmpl['menu_active'] = "
    <script>
        $(document).ready(function(){
            $('#gNavi').find('a[href=".$url."]').addClass('active');
        });
    </script>
    ";

    return $tmpl['menu_active'];
}
// ----------------------------------------------------------------------------
// 麵包屑生成
// ----------------------------------------------------------------------------
function breadcrumb(){
global $config;
global $tr;

$haystack_json = '
{
    "menu_admin@message": {
      "announcement": null,
      "stationmail": null,
      "member_receivemoney": null
    },
    "mo_transaction_log": null,
    "menu_admin@deposit": {
        "deposit": {
            "deposit_company":{
                "deposit_company_status":null
            }
            },
        "wallets": {
            "withdrawapplicationgcash":null,
            "withdrawapplication":{
                "token_auditorial":null
            }
            },
        "exchange_token":null
      },
    "menu_admin@safe": {
      "member": null,
      "member_changepwd": null,
      "member_withdrawalpwd": null,
      "member_banksetting": null,
            "member_share_friends": null,
            "member_authentication": null
    },
    "menu_admin@agent": {
      "moa_transaction_log":null,
      "member_management":{
          "register_agenthelp":null
          },
      "spread_register": {
        "spread_register_add": null
      }
    }
}';

$now_location = substr($_SERVER['DOCUMENT_URI'],0,-4);
$haystack = json_decode($haystack_json, true);
$keyword = $now_location;

function search_index($array, &$index, &$result)
{
    $call = function ($val, $key) use ($index) {
        return $index == $key;
    };

    $tmp = array_filter($array, $call, ARRAY_FILTER_USE_BOTH);
    if (!empty($tmp)) {
        $result = [key($tmp) => true];
        return;
    }

    foreach ($array as $key => $value) {
        //echo $key . "\n";
        if (is_array($value)) {
            search_index($value, $index, $result[$key]);
        }
    }
    $result = array_filter($result ?? []);
}

search_index($haystack, $keyword, $result);

if($result==null){
    return false;
}

function combine_html($array, &$result_html)
{
    global $config;
    global $tr;
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            /*$key = (preg_match("~menu_admin~", $key))? str_replace("@","_",$key):$key;
            $title = ($tr['membercenter_'.$key])??$key;*/
            if(preg_match("~menu_admin~", $key)){
                $key = str_replace("@","_",$key);
                $title = ($tr['membercenter_'.$key])??$key;
$result_html .=<<<HTML
<li>{$title}</li>
HTML;
            }else{
            $title = ($tr['membercenter_'.$key])??$key;
            $result_html .=<<<HTML
<li><a href="{$config['website_baseurl']}{$key}.php">{$title}</a></li>
HTML;
}
            combine_html($value, $result_html);
        }else{
            $title = ($tr['membercenter_'.$key])??$key;
            $result_html .=<<<HTML
<li class="active">{$title}</li>
HTML;
        }

    }
}

combine_html($result, $result_html);

$result_html = '<ul class="breadcrumb"><li><a href="'.$config['website_baseurl'].'home.php"><span class="glyphicon glyphicon-home"></span></a></li><li>'.$tr['membercenter_menu_admin'].'</li>'.$result_html.'</ul>';

return $result_html;
}

// ----------------------------------------------------------------------------
// 傳回會員等級、會員ID、餘額、是否有錢在娛樂城等資料(僅數據)
// 相關檔案：
// ----------------------------------------------------------------------------
function login_data()
{
    global $csrftoken;
    global $tr;

    if(isset($_SESSION['member'])) {
        //會員角色
        switch ($_SESSION['member']->therole) {
            case 'M':
                $therole=['code'=>'m','name'=>$tr['member']];
                break;
            case 'A':
                $therole=['code'=>'a','name'=>$tr['agent']];
                break;
            case 'R':
                $therole=['code'=>'r','name'=>$tr['management']];
                break;
            default:
                $therole=['code'=>'t','name'=>$tr['test account']];
                break;
        }

        // 顯示帳號
        $account = $_SESSION['member']->account;
        // 更新會員的個資及餘額狀態, 目前專門給： menu_login_ui function 使用
        $member_balance_reload_result = member_balance_reload($_SESSION['member']->id);

        // 從錢包 session 取得餘額的狀態, 如果更新坐在 reload balacne 按鈕上面
        $gcash = $_SESSION['member']->gcash_balance;
        $gtoken = $_SESSION['member']->gtoken_balance;
        // 把兩個錢包合成一個顯示, 餘額加總
        $show_currencybalance = $_SESSION['member']->gtoken_balance + $_SESSION['member']->gcash_balance;
        $all = number_format($show_currencybalance,2);
        $balance = compact('gcash','gtoken','all');

        // 當代幣在娛樂城的時候,才顯示 JS and icon
        // 餘額狀態顯示欄位, 如果有在娛樂城用不同顏色顯示
        if($_SESSION['member']->gtoken_lock != NULL) {
            // 更新系統錢包(會取回所有娛樂城餘額) ,確認要取回所有娛樂城的餘額？
            $is_gtoken_lock = true;
        }else{
            $is_gtoken_lock = false;
        }

        return ['status'=>true,'data'=>compact("therole","account",'balance',"is_gtoken_lock","csrftoken")];

    }else{
        return ['status'=>false,'data'=>null];

    }
}
// ----------------------------------------------------------------------------
// mobile 首頁登入後跳轉到到首頁，登入完全連結改為會員中心
// 相關檔案：各版 home.tmpl.php header.tmpl.php
// ----------------------------------------------------------------------------
function mobile_login(){
    if (!isset($_SESSION['member'])) {
        $mobile_signin = 'login2page.php';
    }else{
        $mobile_signin = 'menu_admin.php';
    }
    return $mobile_signin;
}
// ----------------------------------------------------------------------------
// 載入所有必要js css檔案 (bootstrap fonts 等)
// 相關檔案：
// ----------------------------------------------------------------------------
function assets_include($conf=null)
{
    global $config;
    global $cdnfullurl;
    global $cdnfullurl_js;

    $langjs = (isset($_SESSION['lang'])) ? $_SESSION['lang'] : 'zh-cn';

    $head_asset=<<<HTML
    <!-- TODO add manifest here -->
    <link rel="manifest" href="{$config['website_baseurl']}in/manifest/manifest.json">
    <!-- Add to home screen for Safari on iOS -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="apple-touch-icon" href="{$cdnfullurl_js}icons/touch-icon-iphone.png" />

    <script src='{$cdnfullurl_js}moment/moment-with-locales.min.js'></script>
    <script src='{$cdnfullurl_js}moment/moment-timezone-with-data.min.js'></script>

    <!-- reset.css -->
    <link type='text/css' rel='stylesheet' href='{$cdnfullurl_js}css/reset.css'>

    <!-- bootstrap 4.0.0 and jquery -->
    <link rel='stylesheet' href='{$cdnfullurl_js}bootstrap/css/bootstrap.min.css'>
    <script src='{$cdnfullurl_js}jquery/jquery.min.js'></script>
    <script src='{$cdnfullurl_js}js/common.js'></script>
    <script src='{$cdnfullurl_js}bootstrap/js/bootstrap.min.js'></script>

    <!-- jquery.crypt.js -->
    <script src='{$cdnfullurl_js}jquery.crypt.js'></script>

    <!--  font icon  -->
    <link rel='stylesheet' href='{$cdnfullurl_js}fonticon/css/icons.min.css'>

    <!-- marquee -->
    <script src='{$cdnfullurl_js}jquery.marquee.min.js'></script>
    <script src='{$cdnfullurl_js}jquery.pause.js'></script>
    <!-- CSS共用修正 -->
    <link rel='stylesheet' href="{$cdnfullurl_js}css/add.css?version_key={$config['cdn_version_key']}">

    <!-- JS Language File -->
    <script src='{$cdnfullurl_js}lang/{$langjs}.js'></script>
HTML;

    if($config['site_style']=='mobile'){
        $head_asset.=<<<HTML
    <script src='{$cdnfullurl_js}m/js/swiper.min.js'></script>
    <link rel='stylesheet' href='{$cdnfullurl_js}m/css/swiper.min.css'>
    <link rel='stylesheet' href="{$cdnfullurl_js}css/common_m.css?ver_key_m={$config['cdn_version_key']}">
HTML;
    }

    /*--------wow.js + animate.css -----------*/
    if(isset($conf)&&$conf=='animate'){
        $head_asset.=<<<HTML
        <link rel='stylesheet' href='{$cdnfullurl_js}css/animate.css'>
        <script src='{$cdnfullurl_js}js/wow.js'></script>
HTML;
    }

    return $head_asset;
}
// ----------------------------------------------------------------------------
// 頁腳顯示 -- 隱藏的資訊，不顯示於頁面中。但是有除錯的資訊。
// ----------------------------------------------------------------------------
/*
function info_page_footer(){
    // 計算時間
    global $program_start_time;
    global $tr;

    // 網站內容分析
    require_once dirname(__FILE__) ."analytic.php";

    // 算累積花費時間, 另一個開始放在 config.php
    $program_spent_time = microtime(true) - $program_start_time;
    $program_spent_time_html = "<script>console.log('".$program_spent_time."')</script>";

    $footer_content = $program_spent_time_html;


    return($footer_content);
}
*/
// ----------------------------------------------------------------------------
// 頁面的 menu function , 一定要放在 lib.php 的最後面。否則 call lib 的時候會出現順序的問題。 END
// ----------------------------------------------------------------------------
?>
