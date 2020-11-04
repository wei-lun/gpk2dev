<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 訪客透過頁面申請免費試玩
// File Name:	Trial.php
// Author:		Barkley
// Related:
// Log:
// ----------------------------------------------------------------------------


// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

die('網站維護中');

// var_dump($_SESSION);

// var_dump(session_id());
// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------
/*
function clean_session() {
  // 確認有沒有清空 session  , 沒有的話再 run 一次
	if(isset($_SESSION)) {

		// 重置会话中的所有变量
		$_SESSION = array();

		// 如果要清理的更彻底，那么同时删除会话 cookie
		// 注意：这样不但销毁了会话中的数据，还同时销毁了会话本身
		if (ini_get("session.use_cookies")) {
				$params = session_get_cookie_params();
				setcookie(session_name(), '', time() - 42000,
						$params["path"], $params["domain"],
						$params["secure"], $params["httponly"]
				);
		}

		// 最后，销毁会话
		@session_destroy();
		// echo '<script>window.location="login_action.php?a=logout";</script>';
	}

  return(1);
}
*/

// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
$function_title = '免費試玩';
// 擴充 head 內的 css or js
$extend_head				= '';
// 放在結尾的 js
$extend_js					= '';
// body 內的主要內容
$indexbody_content	= '';
// 系統訊息選單
$messages 					= '';
// ----------------------------------------------------------------------------




// Javascript
$trial_form_send_js = '';

// -------------------------------------------------------------------
// 試玩帳戶註冊表單 ,  trial_preview_area -- JS 呼叫後回傳的提示區塊
$trial_timeout = $GPK2_TRIAL['default_timeout']/60;
$trial_timeinterval = $GPK2_TRIAL['default_timeinterval']/60;

$trial_free_form_row = '';

// 試玩限制說明
$trial_limit_message = '';
// 使用者 IP 登入後，登入時間 $GPK2_TRIAL['default_timeout'] 內可以重複登入遊戲。
// DDD 顯示差多少天應該只會是正數, SSSS 顯示差多少秒(只能一天的秒數)
// SELECT id,ip,account,logintime,to_char(age(current_timestamp,logintime),'HH24:MI:SS') as ageinterval FROM root_membertriallog WHERE ip = '122.254.37.119' AND (logintime + 3600 * interval '1 second') >= current_timestamp  ORDER BY id DESC LIMIT 1;
$check_interval_sql = "SELECT id,ip,account,logintime,to_char(age(current_timestamp,logintime),'DDD') as ageinterval_days, to_char(age(current_timestamp,logintime),'SSSS') as ageinterval FROM root_membertriallog WHERE ip = '".explode(',',$_SERVER['HTTP_X_FORWARDED_FOR'])['0']."' AND status = 1  ORDER BY id DESC LIMIT 1;";
//var_dump($check_interval_sql);
$check_interval_r = runSQLALL($check_interval_sql);
//var_dump($check_interval_r);


// 有資料的話
if($check_interval_r[0] == 1 ) {
	// 如果時間超過使用限制，且餘額 < 1 ，表示錢已經花光，再次可登入時 $GPK2_TRIAL['default_agent'] 轉錢，加上 $GPK2_TRIAL['default_coin'] 的代幣給它。
	// 間隔超過 $GPK2_TRIAL['default_interval'] 才可再次登入平台，顯示還有多少時間可以再度試玩。

	// 將 ageinterval_days 轉換成為秒數 60*60*24 seconds
	$ageinterval_seconds = ($check_interval_r[1]->ageinterval_days*60*60*24) + $check_interval_r[1]->ageinterval;

	if($ageinterval_seconds > $GPK2_TRIAL['default_timeinterval']) {
		$trial_limit_message = '距離下次登入間隔時間已經超過'.$GPK2_TRIAL['default_timeinterval'].'秒 ，可以重新登入,繼續使用。';
		// clean_session();
	}else {
		$trial_limit_message = '距離下次登入間隔時間尚未超過'.$GPK2_TRIAL['default_timeinterval'].'秒 。';

		// 使用者 IP 登入後，登入時間 $GPK2_TRIAL['default_timeout'] 內可以重複登入遊戲。就沒有資料會呈現。代表此次可以登入的時間已經用完。
		if($ageinterval_seconds > $GPK2_TRIAL['default_timeout']) {
			$trial_limit_message = $trial_limit_message.'本次使用時間已經超過'.$GPK2_TRIAL['default_timeout'].'秒，登出系統。';
			// clean_session();
		}else{
			$trial_limit_message = $trial_limit_message.'本次使用時間尚未超過'.$GPK2_TRIAL['default_timeout'].'秒，繼續使用。';
		}
	}
	$ageinterval = $ageinterval_seconds;
}else{
	$trial_limit_message = '';
	$ageinterval = 0;
}

// var_dump($trial_limit_message);
// -------------------------------------------------------------------


// 測試帳號，如果已經登入的話
if(isset($_SESSION['member']->therole) AND $_SESSION['member']->therole == 'T') {

  $trial_logintime = round(($check_interval_r[1]->ageinterval)/60,1);
  $trial_logouttime = round(($GPK2_TRIAL['default_timeout']-$ageinterval)/60,1);
  $trial_free_form_row = $trial_free_form_row.'
  <div class="alert alert-info" id="trial_preview_area">'.'
    <p><strong>試玩訊息:(已經登入)</strong> </p>
    <p>訪客 '.explode(',',$_SERVER['HTTP_X_FORWARDED_FOR'])['0'].' 你好。</p>
  	<p>每個IP 每次可在 '.$trial_timeout.' 分鐘內，隨時的登入試玩。並給予代幣'.$GPK2_TRIAL['default_coin'].'試玩。</p>
    <p>每個IP 第 1 次試玩結束後，第 2 次進入需要間隔 '.$trial_timeinterval.' 分鐘後才可以進入。</p>
    <p>已經登入'.$trial_logintime.'分，現在距離登出還有'.$trial_logouttime.'分鐘</p>
  	<p>歡迎<a href="register.php">註冊成為會員</a></p>
  	'.
  '</div>';

  $trial_free_form_row = $trial_free_form_row.'
  <br>
  <div class="row">
  	<div class="col-12">
  		<button id="logout_trial" class="btn btn-success btn-block" type="submit">登出試玩帳號</button>
  	</div>
  </div>
  ';


$trial_form_send_js = $trial_form_send_js."
<script>
  $(document).ready(function() {

  $('#logout_trial').click(function(){
    var show_text = '你確認要登出試玩??';
    if(confirm(show_text)) {
        $('#logout_trial').attr('disabled', 'disabled');
        var logout_url='login_action.php?a=logout';
        $(document.body).html('<img src=\'".$cdnrooturl."loading_spin.gif\'>');
        window.location.assign(logout_url);
    }
  });

});
</script>
";
}else{
  // 沒有登入的顯示

	$trial_timeinterval_age = round(($GPK2_TRIAL['default_timeinterval']-$ageinterval)/60,1);
	$ageinterval_minutes = round($ageinterval/60,1);
	if($trial_timeinterval_age <= 0) {
		$trial_free_form_row_showstatus = '<p>試玩查驗正確，你可以立即登入系統試玩。</p>';
	}else{
		if($ageinterval < $GPK2_TRIAL['default_timeout']) {
			$trial_free_form_row_showstatus = '<p>歡迎回來，你可再次立即登入系統。(試玩已經 '.$ageinterval_minutes.'分鐘)</p>';
		}else{
			$trial_free_form_row_showstatus = '<p>你還需要經過'.$trial_timeinterval_age.'分鐘後，才可以登入系統。</p>';
		}

	}
	// 顯示的訊息
  $trial_free_form_row = $trial_free_form_row.'
  <div class="alert alert-info" id="trial_preview_area">'.'
    <p><strong>試玩訊息:(沒有登入)</strong> </p>
    <p>訪客 '.explode(',',$_SERVER['HTTP_X_FORWARDED_FOR'])['0'].' 你好。</p>
  	<p>每個IP 每次可在 '.$trial_timeout.' 分鐘內，隨時的登入試玩。並給予代幣'.$GPK2_TRIAL['default_coin'].'試玩。</p>
    <p>每個IP 第 1 次試玩結束後，第 2 次進入需要間隔 '.$trial_timeinterval.' 分鐘後才可以進入。</p>
		'.$trial_free_form_row_showstatus.'
		<p>請填入驗證號碼，就可以開始試玩遊戲。</p>
  	<p>歡迎<a href="register.php">註冊成為會員</a></p>
  	'.
  '</div>';


  $trial_free_form_row = $trial_free_form_row.'
  <div class="row">
  	<div class="col-12 col-md-4">
  		<p class="text-right"><span class="glyphicon glyphicon-star" aria-hidden="true"></span>驗證碼</p>
  	</div>
  	<div class="col-12 col-md-8">
  		<input type="text" class="form-control" id="captcha_trial_input" placeholder="請填入下方驗證碼"  required>
  	</div>
  	<hr>

  	<div class="col-12 col-md-4">
  	</div>
  	<div class="col-12 col-md-8">
  		<img src="'.$cdnfullurl_js.'captcha/captcha.php" id="captcha" />
  	</div>
  	<hr>
  </div>
  ';

  $trial_free_form_row = $trial_free_form_row.'
  <br>
  <div class="row">
  	<div class="col-12">
  		<button id="submit_to_trial" class="btn btn-success btn-block" type="submit">申請免費試玩</button>
  	</div>
  </div>
  ';

  // 登入試玩
  $trial_form_send_js = $trial_form_send_js."
  <script>
  	$(document).ready(function() {

  		$('#submit_to_trial').click(function(){

  			var captcha_trial_input = $('#captcha_trial_input').val();

  			if(jQuery.trim(captcha_trial_input) == '' ){
  				alert('請填入驗證碼');
  			}else{

  				$('#submit_to_trial').attr('disabled', 'disabled');

  				$.post('trial_post_action.php?a=guest_trial',
  					{
  						captcha_trial_input: captcha_trial_input
  					},
  					function(result){
  						$('#trial_preview_area').html(result);}
  				);
  			}
  		});

  	});
  </script>
  ";
}


// 把上面的 JS 放到最後顯示
$extend_js = $trial_form_send_js;

// ----------
// 已經成為會員後，是不可以免費試玩的。轉到 home.php 頁面。
if(isset($_SESSION['member']) AND ($_SESSION['member']->therole == 'A' OR $_SESSION['member']->therole == 'M' OR $_SESSION['member'] == 'R' ) ) {
	$logger = '會員及代理商無法使用試玩遊戲功能。';
	$indexbody_content = "
	<p>$logger</p>";
	// <script>alert('".$logger."');</script>";
	//$indexbody_content = '<script>location.reload("true");</script>';

}else{
	// 切成 3 欄版面 3:6:3
	$indexbody_content = '';
	$indexbody_content = $indexbody_content.'
	<div class="row">
	  <div class="col-12 col-md-3">
	  功能選單(美工)
	  </div>
	  <div class="col-12 col-md-6">
	  '.$trial_free_form_row.'
	  </div>
	  <div class="col-12 col-md-3">
	  功能選單(廣告)
	  </div>
	</div>
	<br><br><br>
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
$tmpl['paneltitle_content'] 			= '<span class="glyphicon glyphicon-bookmark" aria-hidden="true"></span>'.$function_title;
// 主要內容 -- content
$tmpl['panelbody_content']				= $indexbody_content;


// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include($config['template_path']."template/home.tmpl.php");

?>
