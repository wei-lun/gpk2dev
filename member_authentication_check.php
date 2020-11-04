<?php
// ----------------------------------------------------------------------------
// Features:	前台 -- 2階段驗證 檢查頁面
// File Name:   member_authentication_check.php
// Author:		Mavis
// Related:     member_authentication.php,member_authentication_action.php,member_authentication_lib.php
// Log:
//
// ----------------------------------------------------------------------------

session_start();

// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

require_once dirname(__FILE__) ."/login2page_lib.php";

// 2fa
require_once dirname(__FILE__) ."/in/PHPGangsta/GoogleAuthenticator.php";

require_once dirname(__FILE__) ."/member_authentication_lib.php";

//var_dump($_SESSION);
//var_dump($_POST);

// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
$function_title 		= $tr['membercenter_member_authentication'] ;
// 擴充 head 內的 css or js
$extend_head				= '';
// 放在結尾的 js
$extend_js					= '';
// body 內的主要內容
$indexbody_content	= '';
// 系統訊息選單
$messages 					= '';
// ----------------------------------------------------------------------------
// 初始化變數 end
// ----------------------------------------------------------------------------

// 導覽列
$navigational_hierarchy_html =<<<HTML
	<ul class="breadcrumb">
		<li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
        <li><a href="member.php">{$tr['Member Centre']}</a></li>
		<li class="active">{$function_title}</li>
	</ul>
HTML;

if($config['site_style']=='mobile'){
  	$navigational_hierarchy_html =<<<HTML
		<a href="{$config['website_baseurl']}menu_admin.php?gid=agent"><i class="fas fa-chevron-left"></i></a>
		<span>{$function_title}</span>
		<i></i>
HTML;
}
// ----------------------------------------------------------------------------

// 檢查是否從home.php 或 login2page.php連進來的
// 如果直接複製網址到別的瀏覽器 無痕或改參數，就導回首頁
if(isset($_SESSION['origURL']) AND ($_SESSION['origURL'][0] == 'home.php' OR $_SESSION['origURL'][1] == 'login2page.php')){
    if(isset($_GET['a']) AND $_GET['a'] != ''){
        $content = '';
        $account_name = isset($_GET['a']) ? filter_string($_GET['a'],"string") : "";
        $decode_account = base64_decode($account_name);

        // 如果有改帳號參數，導回首頁，清除session
        if(isset($_SESSION['check_fa_account']) AND $_SESSION['check_fa_account'] != $decode_account){
            clear_session();
        }
        
        // 找會員是否真存在
        $find_member = get_all_member_data();
        if(array_key_exists($decode_account,$find_member)){
            $id = $find_member[$decode_account]['id'];
            // 找2階段驗證是否有資料
            $to_check_factor_isset = sql_member_authentication($id);
            $token = $_SESSION['check_fa_token']; // 2FA自動跳轉道指定URL的token

            if($to_check_factor_isset[0] >= 1 AND $to_check_factor_isset[1]->two_fa_status == '1'){
                $content=<<<HTML
                    <div class="row twofa">
                        <div class="col-1"></div>
                        <div class="col-12"><p class="text-center">{$tr['Please enter the verification code provided on your Authenticator']}</p></div>
                        <div class="col-12 mb-2">
                          <label class="check_identity_member">
                            <i class="fa fa-key"></i>
                            <input type="text" name="check_factor_code" class="form-control" placeholder="{$tr['Verification code']}" required>
                          </label>
                        </div>
                        <div class="col-12 send_identity_member">
                            <a href="member_authentication_action.php?s=clear">
                              <button class="btn" id="cancel_factor">{$tr['Back to previous page']}</button> 
                            </a>
                            <button class="btn btn-primary" id="confirm_factor">{$tr['authenticating']}</button>
                        </div>
                        <div class="col-12"><p class="text-left mt-3">{$tr['If you have any questions, please contact customer service.']}</p></div>
                    </div>
HTML;
            }
        }else{
            clear_session();
        }
    }else{
        clear_session();
    }
}else{
    clear_session();
}


$extend_js=<<<HTML
    <script>
    // 檢查驗證碼
    $("#confirm_factor").on('click',function(){
        // 驗證碼
        var verify_code = $("input[name=check_factor_code]").val();
        // 帳號
        var member_account = '$decode_account';
        // id
        var member_id = $id;
        // token
        var fa_token = '$token';

        $.post('login_action.php?a=factor_check',{
            varify_code : verify_code,
            member_account : member_account,
            member_id : member_id,
            fa_token : fa_token
        },
        function(result){
            // $("#preview_result").html(result);
            if(result.code == '3'){
                // 重複登入
                alert(result.error);
                window.location = 'home.php';

            }else if(result.code == '0'){
                // 驗證碼錯誤
                alert(result.error);
                location.reload();
            }else{
                $("#preview_result").html(result.error);
            }
        },'JSON');


        // 原版
        // function(result){
        //     // $("#preview_result").html(result);
        // });

    })

    // 按下 enter 後,等於 click 登入按鍵
    $(function() {
        $(document).keyup(function(e) {
            switch(e.which) {
                case 13: // enter key
                    $("#confirm_factor").trigger("click");
                break;
            }
        });
    });

    </script>
HTML;

// 切成 3 欄版面 3:8:1
$indexbody_content = '';
//功能選單(美工)  功能選單(廣告)
$indexbody_content = $indexbody_content . '
' . $content . '
<div class="row">
    <div class="col-12">
    <div id="preview_result"></div>
    </div>
</div>
';

// ----------------------------------------------------------------------------
// MAIN  END
// ----------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// 準備填入的內容
// ----------------------------------------------------------------------------
// 將內容塞到 html meta 的關鍵字, SEO 加強使用
$tmpl['html_meta_description'] 		= $config['companyShortName'];
$tmpl['html_meta_author']	 				= $config['companyShortName'];
$tmpl['html_meta_title'] 					= $function_title.'-'.$config['companyShortName'];

// 系統訊息顯示
$tmpl['message']									= $messages;
// 擴充再 head 的內容 可以是 js or css
$tmpl['extend_head']							= $extend_head;
// 擴充於檔案末端的 Javascript
$tmpl['extend_js']								= $extend_js;
// 主要內容 -- title
$tmpl['paneltitle_content'] 			= $navigational_hierarchy_html;
// 主要內容 -- content
$tmpl['panelbody_content']				= $indexbody_content;
// 側邊欄MENU var[0]側邊欄組合(lib_menu) var[1]目前頁籤名
// $tmpl['sidebar_content'] = ['safe','member_authentication'];

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
// include $config['template_path'] . "template/admin.tmpl.php";
include($config['template_path']."template/login2page.tmpl.php");

?>
