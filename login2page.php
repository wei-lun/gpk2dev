<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 訪客透過頁面登入系統可以透過頁面前往指定位置並帶有變數
// File Name:	login2page.php
// Author:		Barkley
// Related:
// Log: 2017.8.29
// ----------------------------------------------------------------------------
/*
功能說明:
1. 訪客可以透過這個頁面, 登入系統. 如果沒有指定的參數, 則預設引導到 home.php
2. 如果有預設的參數, 登入系統後, 預設引導到指定的 URL ,且可以指定使用 POST or GET 到達指定的 URL 並帶有指定 POST or GET 變數
3. 參數為使用 jwtenc($salt,value) 包裝為一組字串, 透過 $_GET['t']傳入此網頁. jwtenc和 jwtdec是一對, 定義在 lib.php 檔案內
4. 使用者 login 登入驗證正確的話, 依據參數引導到指定位置. 登入驗證失敗的話, 停留在這個頁面.
5. 使用者如果已經登入的話, 依據參數狀態, 引導到指定位置. 沒有參數的話, 引導到系統首頁 home.php

// 需要傳遞的陣列
// formtype --> [POST|GET]   轉址傳遞變數的方式(必要)
// formurl  --> 自訂轉址指定的網址, 相對路徑或絕對路徑都可以 (必要)
// 其他變數(自訂)
// 範例如下:
$value_array = array(
  'formtype'              => 'POST',
  'formurl' 			        => 'https://test.gpk17.com/login2page_action.php?a=test',
  'gamecasino' 			      => 'MG',
  'gamecode' 			        => 'gamecodesample',
  'amount' 			          => '9999',
  'account'               => 'test'
);
// 產生 token , salt是檢核密碼預設值為123456 ,需要配合 jwtdec 的解碼, 此範例設定為 123456
$send_code = jwtenc('123456', $value_array);
//var_dump($send_code);
$token = $send_code;
}
* 網址範例:
https://test.gpk17.com/login2page.php?t=8b3c129fb8ddd3d9b82e45210773cf9d6cb12056_eyJhY2NvdW50IjoiZ3Vlc3QiLCJhbW91bnQiOiIxMDAwIiwiZm9ybXR5cGUiOiJHRVQiLCJmb3JtdXJsIjoiaHR0cHM6XC9cL3Rlc3QuZ3BrMTcuY29tXC9sb2dpbjJwYWdlX2FjdGlvbi5waHA/YT10ZXN0IiwiZ2FtZWNhc2lubyI6Ik1HIiwiZ2FtZWNvZGUiOiJnYW1lY29kZXNhbXBsZSJ9

by mtchang 2017.08.30 登入功能尚未完成, 目前使用 debug 模式方便除錯.
*/
// ----------------------------------------------------------------------------


// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";
// login2page 此功能的自訂函式庫
require_once dirname(__FILE__) ."/login2page_lib.php";
// 2fa
// require_once dirname(__FILE__) ."/member_authentication_lib.php";

// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------

// $debug = 1;
$debug = 0;

// ----------------------------------------------------------------------------
// 檢查獎置介面是行動裝置還是桌機
// ----------------------------------------------------------------------------
$device_chk_html = clientdevice_detect(0,0)['html'];
echo $device_chk_html;
// ----------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 語言
//$lang = $tr['text_login2page'];
// 功能標題，放在標題列及meta
$function_title = $tr['login to system'];
// 擴充 head 內的 css or js
$extend_head				= '';
// 放在結尾的 js
$extend_js					= '';
// body 內的主要內容
$indexbody_content	= '';
// 系統訊息選單
$messages 					= '';
// ----------------------------------------------------------------------------
// 導覽列
$navigational_hierarchy_html = '
<ul class="breadcrumb">
  <li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
  <li class="active">'.$function_title.'</li>
</ul>
';
// ----------------------------------------------------------------------------




// var_dump($_SESSION);

// ----------------------------------------------------------------------------
// 判断一个字符串是不是base64编码
function checkStringIsBase64($str){
  return $str == base64_encode(base64_decode($str)) ? true : false;
}
// 有 預設登入的帳號, 並檢查是否為 base64 編碼
if(isset($_GET['m']) and checkStringIsBase64($_GET['m'])) {
  // 使用傳入的值
  $member_account = base64_decode($_GET['m']);
}else{
  $member_account = '';
}

// ----------------------------------------------------------------------------
// 有 token 就傳 token 到登入頁, 沒有的話正常登入
if(isset($_GET['t'])) {
  // 使用傳入的值
  $token = $_GET['t'];
}else{
  // 需要傳遞的陣列
  // formtype --> [POST|GET]   轉址傳遞變數的方式(必要)
  // formurl  --> 自訂轉址指定的網址(必要)
  // 其他變數(自訂)
  $value_array = array(
    'formtype'              => 'GET',
    'formurl' 			        => './home.php'
  );
  // 產生 token , salt預設值為123456
  $send_code = jwtenc('123456', $value_array);
  // var_dump($send_code);die();
  $token = $send_code;
}

// https://test.gpk17.com/login2page.php?t=8b3c129fb8ddd3d9b82e45210773cf9d6cb12056_eyJhY2NvdW50IjoiZ3Vlc3QiLCJhbW91bnQiOiIxMDAwIiwiZm9ybXR5cGUiOiJHRVQiLCJmb3JtdXJsIjoiaHR0cHM6XC9cL3Rlc3QuZ3BrMTcuY29tXC9sb2dpbjJwYWdlX2FjdGlvbi5waHA/YT10ZXN0IiwiZ2FtZWNhc2lubyI6Ik1HIiwiZ2FtZWNvZGUiOiJnYW1lY29kZXNhbXBsZSJ9
// ----------------------------------------------------------


// 如果已經是登入的話, 呼叫 action 直接前往指定的網址。
if(isset($_SESSION['member']) AND ($_SESSION['member']->therole == 'M' OR $_SESSION['member']->therole == 'A')) {
  // 依據 get 參數, 產生登入需要的 html code
  $return_html = login2page($token, $debug);
  // 提示存自
  $information_text  = '<div class="alert alert-info" role="alert">' . $tr['page message success'] . '</div>';
  // 登出功能連結
  $logout_html = '<a href="./login2page_action.php?a=logout" target="_SELF" class="btn btn-danger">' . $tr['page message logout'] . '</a>';

  // 排版輸出
  $indexbody_content = $indexbody_content.'
    '.$information_text.'
    '.$logout_html.'
    '.$return_html.'
  ';

}else{
  if($debug == 1){
    $login_form_debug = '
    <div class="form-group">
      <label for="debug">DEBUG codevalue</label>
      <input type="text" class="form-control" id="token" value="'.$token.'"  autocomplete="new-codevalue">
    </div>
    <a href="https://test.gpk17.com/login2page.php?t=8b3c129fb8ddd3d9b82e45210773cf9d6cb12056_eyJhY2NvdW50IjoiZ3Vlc3QiLCJhbW91bnQiOiIxMDAwIiwiZm9ybXR5cGUiOiJHRVQiLCJmb3JtdXJsIjoiaHR0cHM6XC9cL3Rlc3QuZ3BrMTcuY29tXC9sb2dpbjJwYWdlX2FjdGlvbi5waHA/YT10ZXN0IiwiZ2FtZWNhc2lubyI6Ik1HIiwiZ2FtZWNvZGUiOiJnYW1lY29kZXNhbXBsZSJ9" class="btn btn-default">使用GET轉移的的範例</a>
    <a href="https://test.gpk17.com/login2page.php?t=bcf01a50c1129c18fd19cc23d28d26e9da4a4386_eyJhY2NvdW50IjoidGVzdCIsImFtb3VudCI6Ijk5OTkiLCJmb3JtdHlwZSI6IlBPU1QiLCJmb3JtdXJsIjoiaHR0cHM6XC9cL3Rlc3QuZ3BrMTcuY29tXC9sb2dpbjJwYWdlX2FjdGlvbi5waHA/YT10ZXN0IiwiZ2FtZWNhc2lubyI6Ik1HIiwiZ2FtZWNvZGUiOiJnYW1lY29kZXNhbXBsZSJ9" class="btn btn-default">使用POST轉移的的範例</a>
    ';
  }else{
    $login_form_debug = '
    <div class="form-group">
      <input type="hidden" class="form-control" id="token" value="'.$token.'" autocomplete="new-codevalue">
    </div>';
  }
  // 沒有登入的處理
  $login_form_html = '
  <form>
  <div class="form-group">
    <label class="" for="text"><i class="fa fa-user" aria-hidden="true"></i><span>' . $tr['account'] . '<span></label>
    <i class="fa fa-exclamation-triangle userwarning warning" aria-hidden="true"></i>
    <input type="text" class="form-control col-12" id="new_account" maxlength="12" value="'.$member_account.'" placeholder="' . $tr['account'] . '"  autocomplete="new-account">
  </div>
  <div class="form-group">
    <label class="" for="password"><i class="fa fa-unlock-alt" aria-hidden="true"></i><span>' . $tr['password'] . '</span></label>
    <i class="fa fa-exclamation-triangle passwordwarning warning" aria-hidden="true"></i>
    <input type="password" class="form-control col-12" id="password" maxlength="25"  placeholder="' . $tr['password'] . '" autocomplete="new-password">
  </div>
  <div class="form-group">
    <label class="" for="password"><i class="fa fa-key" aria-hidden="true"></i><span>' . $tr['verification'] . '</span></label>
    <div class="" style="position: relative; z-index: 1;">
      <input name="captcha" class="form-control col-12" id="new_register_captcha" type="number" size="3"  maxlength="4" placeholder="' .$tr['Verification click code']. '"  aria-describedby="sizing-addon3" autocomplete="new-captcha">
      <span id="show_captcha" class= "show_captcha_css"><img src="'.$cdnfullurl_js.'img/common/hello.png" id="new_register_captcha" alt="'.$tr['Verification code'].'" title="'.$tr['Verification click code'].'" height="25" width="65" ></span>
    </div>
  </div></form>';
  /// 登入按鈕
  $login_form_button_html = '
  <div class="checkbox">
    <label><input type="hidden" type="hidden" id="login_force" value="1" autocomplete="new-loginforce" checked></label>
  </div>
  '.$login_form_debug.'
 <div class="row mb-3">
<div class="col-12">
 <button id="submit_to_login" type="button" class="btn btn-primary btn-lg w-100 border-radius-27">' . $tr['login']. '</button>
 </div>
</div>
<div class="row">
<div class="col-12 nav nav-fill loginpage_content">
  <a href="register.php" class="loginpage_bg  nav-item nav-link active" href="#">'.$tr['Free account'].'</a>
  <a href="contactus.php" class="loginpage_bg  nav-item nav-link" href="#">'.$tr['login_service_contact'].'</a>
</div>
</div>
  ';

// 2018.3.19 移除強制選項, 預設就是後面登入的會剔除前面的
// <label><input type="hidden" type="hidden" id="login_force" value="1" checked>' . $lang['forced login'] . '</label>
  $import_member_js = '';

	if($system_config['allow_login_passwordchg'] == 'on'){
    //您已經有一段時間未更新密碼，請立即更新
    //您好，為保障您的權益，請依下列步驟更新密碼  請輸入新密碼再次輸入新密碼確定變更 請輸入8至12碼，英數混合密碼
		$login_form_html .= <<<HTML
		<div class="modal fade bs-example-modal-lg" id="pwchg" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
		  <div class="modal-dialog modal-lg" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <h4 class="modal-title" id="myModalLabel">{$tr['Password not updated for a while']}</h4>
		      </div>
		      <div class="modal-body">
						<div class="text">
							<p><text id='useraccount'></text><span> {$tr['Password not updated for a while alert']}</span></p>
							<input id="newpasswordcs" class="form-control form-control-sm" name="newpasswordcs" type="hidden"  autocomplete="new-newpasswordcs">
							<input id="newpasswordcc" class="form-control form-control-sm" name="newpasswordcc" type="hidden" autocomplete="new-newpasswordcs">
						</div>
						<div class="pwchg-table-newpw">
							<div class="text ">
								<p><span>{$tr['Please enter a new password']}</span></p>
							</div>
							<input id="newpassword" class="form-control form-control-sm" name="Password" type="password" size="2" maxlength="12" placeholder="{$tr['Please enter 8 to 12 yards, English mixed password']}" aria-describedby="sizing-addon3"  autocomplete="new-newpassword" required>
						</div>
						<div class="pwchg-table-newpw-chk">
							<div class="text ">
								<p><span>{$tr['Enter the new password again']}</span></p>
							</div>
							<input id="newpassword_chk" class="form-control form-control-sm" name="Password" type="password" size="2" maxlength="12" placeholder="{$tr['Please enter 8 to 12 yards, English mixed password']}" aria-describedby="sizing-addon3"  autocomplete="new-newpassword-chk" required>
						</div>
			     </div>
			     <div class="modal-footer">
			       <button id="loginpwdchg" type="button" class="btn btn-login">{$tr['Identify changes']}</button>
			     </div>
	   		</div>
	  	</div>
		</div>
HTML;

	 $import_member_js .= <<<JAVASCRIPT
				$('#loginpwdchg').click(function(){
					if(!/^[0-9a-zA-Z]{6,12}$/i.test($('#newpassword').val())) {
						alert('{$tr['Please enter 8 to 12 yards, English mixed password']}');
						console.log($('#newpassword').val());
					}else if($('#newpassword_chk').val() == '') {
						alert('{$tr['Enter the new password again']}');
					}else if($('#newpassword_chk').val() != $('#newpassword').val()) {
						alert('{$tr['password does not match the verification password']}');
					}else{
						var account_input  = $('#new_account').val();
						var password_input  = $().crypt({method:'sha1', source:$('#password').val()});
						var npassword_input  = $().crypt({method:'sha1', source:$('#newpassword').val()});
						var npassword_inputc  = $('#newpassword_chk').val();
						var captcha_input  = $('#newpasswordcc').val();
						var csrftoken = $('#newpasswordcs').val();
            var token = $('#token').val();

						$.post('login2page_action.php?a=login_pwdchg',
							{ captcha: captcha_input, account: account_input, password: password_input, npassword: npassword_input, npasswordc: npassword_inputc, token:token, csrftoken: csrftoken },
							function(result){
								if(result.code == '2'){
									$('#useraccount').text(account_input);
									$('#pwchg').modal({backdrop: 'static', keyboard: false});
								}else if(result.code == '1'){
									// window.location='home.php';
									alert(result.msg);
  								$('#preview_status').html(result.error);
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

  $login2page_form_send_js = "
  <script>
    $(document).ready(function() {

      //點擊圖片就更換驗證碼
      // $('#show_captcha').click(function(){
      //     $.post( 'in/captcha/captchabase64.php', function( captchabase64data ) {
      //     var img_cpatcha_html = \"<img src='\"+captchabase64data+\"' id='captcha' alt='Verification code' height='20' width='58'>\" ;
      //     $('#show_captcha').html(img_cpatcha_html);
      //     });
      // });

      //input click
      $('#new_register_captcha').click(function(){
        $.post( 'in/captcha/captchabase64.php', function( captchabase64data ) {
        	var img_cpatcha_html = \"<img src='\"+captchabase64data+\"' id='captcha' alt='Verification code' height='20' width='58'>\" ;
        	$('#show_captcha').html(img_cpatcha_html);
        });
      });

      //模糊焦點
      $('#new_register_captcha').focus(function(){
        $.post( 'in/captcha/captchabase64.php', function( captchabase64data ) {
        	var img_cpatcha_html = \"<img src='\"+captchabase64data+\"' id='captcha' alt='Verification code' height='20' width='58'>\" ;
        	$('#show_captcha').html(img_cpatcha_html);
        });
      });

    $('#submit_to_login').click(function(){
      if($('#new_register_captcha').val() == '') {
        alert('".$tr['Please fill in the verification code']." Verification code ');
      }else if($('#new_account').val() == ''){
        alert('".$tr['Please fill in the account field']." Account');
        $('.userwarning').addClass('active');
      }else if($('#password').val() == ''){
        alert('".$tr['Please fill in the password']." Password');
        $('.passwordwarning').addClass('active');
      }else{
        var csrftoken = '$csrftoken';
        var account = $('#new_account').val();
        var passwordsha1  = $().crypt({method:'sha1', source:$('#password').val()});
        var captcha = $('#new_register_captcha').val();
        $('#new_register_captcha').val('');
        var token = $('#token').val();
        if ($('#login_force').is(':checked')) {
          var login_force = 1;
        }else{
          var login_force = 0;
        }

        // 此登入頁面和login2page_action.php依然有效、保留，只是把login2page.php、lib_menu.php 登入時各自會用到的action 整合成一個，放在login_action.php:129~143
        // 原本的url: login2page_action.php?a=login2page

        $.post('login_action.php?a=login_check',
          { account: account , password: passwordsha1, captcha: captcha, login_force: login_force, token:token, csrftoken:csrftoken },
          function(result){
						if(result.code == '2'){
							$('#useraccount').text(account);
							$('#newpasswordcs').val(result.pwdcsrf);
							$('#newpasswordcc').val(captcha);
							$('#pwchg').modal({backdrop: 'static', keyboard: false});
						}else if(result.code == '3'){
              // 重複登入
							alert(result.error);
							window.location='home.php';
            }else{  
							$('#preview_status').html(result.error);
						}
					},'JSON');
      }
    });

    $(function() {
		   $(document).keydown(function(e) {
		    switch(e.which) {
		        case 13: // enter key
		        $('#submit_to_login').trigger('click');
		        break;
		    }
			});
		});
    ".$import_member_js."

  });
  </script>
  ";

  // 提示文字
  $information_text  = '<div class="alert alert-info" role="alert">' . $tr['page message'] . '</div>';

  // 排版輸出
  $indexbody_content = $indexbody_content.'
    '.$information_text.'
    '.$login_form_html.'
    <div id="preview_status"></div>
    '.$login_form_button_html.'
    '.$login2page_form_send_js.'
  ';
}




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


// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include($config['template_path']."template/login2page.tmpl.php");

?>
