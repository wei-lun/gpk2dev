<?php
// ----------------------------------------------------------------------------
// Features:	針對 member 登入檢查的處理, 預設給 member 資料相關的程式使用。
// 包含 reload balance , login ,logout
// File Name:	login_action.php
// Author:		Barkley
// Related:   每一個有登入、登出的頁面，都會用到這個
// Log:
// ----------------------------------------------------------------------------



// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

// login 專用函式庫 -- 提供 login_action 及 login2page_action 使用
require_once dirname(__FILE__) ."/login2page_lib.php";
// 登入錯誤
require_once dirname(__FILE__) ."/loginattempt_lib.php";
// 2FA 檢查
require_once dirname(__FILE__) ."/member_authentication_lib.php";
require_once dirname(__FILE__) ."/in/PHPGangsta/GoogleAuthenticator.php";
// 娛樂城函式庫
require_once dirname(__FILE__) ."/casino_lib.php";

$debug = 0	;

// 宣告兩階段驗證物件
$ga = new PHPGangsta_GoogleAuthenticator();
$casinoLib = new casino_lib();

// -----------------------------------------------------------------------------
// 前台 action 會員登入身份專用：檢查有沒有參數,以及是否帶有 session
if(isset($_GET['a'])) {
	$action = filter_var($_GET['a'], FILTER_SANITIZE_STRING);
	// var_dump($action);die();

	// 如果是登出系統, by pass　CSRF check
	if($action == 'logout' OR $action == 'changewebpanel') {
		// by pass CSRF check
	}elseif($action == 'factor_check'){
		// 2 fa
	}else{
		// 檢查產生的 CSRF token 是否存在 , 錯誤就停止使用. 定義在 lib.php
		$csrftoken_ret = csrf_action_check();
		// -------------------
		// 2019.6.19
		if($csrftoken_ret['code'] == 0){
			$return = [
				'code' => $csrftoken_ret['code'],
				'error' => '<script>alert("'.$csrftoken_ret['messages'].'");location.href="./home.php";</script>',
			];
			$return_jsonencode = json_encode($return);
			echo($return_jsonencode);die();

		}elseif($csrftoken_ret['code'] == 1){
			// data token correct

		}else{
			die($csrftoken_ret['messages']);
		}
		// --------------------
	}

} else {
	// 不合法的測試, 轉回 home.php. function 定義在 lib.php
	echo login2return_url(2);
	die(' (x)deny to access.');
}

// 原版
// if(isset($_GET['a'])) {
// 	$action = filter_var($_GET['a'], FILTER_SANITIZE_STRING);

// 	// 如果是登出系統, by pass　CSRF check
// 	if($action == 'logout' OR $action == 'changewebpanel') {
// 		// by pass CSRF check
// 	}else{
// 		// 檢查產生的 CSRF token 是否存在 , 錯誤就停止使用. 定義在 lib.php
// 		$csrftoken_ret = csrf_action_check();
// 		if($csrftoken_ret['code'] != 1) {
// 			//var_dump($csrftoken_ret);
// 			die($csrftoken_ret['messages']);
// 		}
// 	}

// } else {
// 	// 不合法的測試, 轉回 home.php. function 定義在 lib.php
// 	echo login2return_url(2);
// 	die(' (x)deny to access.');
// }

// -----------------------------------------------------------------------------

//var_dump($_SESSION);
//var_dump($_POST);
//var_dump($_GET);


// ----------------------------------
// 動作為會員登入檢查 login_check
// ----------------------------------
if($action == 'login_check') {

	  // 取得傳入的值
	  $account        = strtolower(filter_var($_POST['account'], FILTER_SANITIZE_STRING));
	  $password       = filter_var($_POST['password'], FILTER_SANITIZE_STRING);
	  // 強迫登入的設定
	  if(isset($_POST['login_force']) AND ($_POST['login_force'] == '1' OR $_POST['login_force'] == '0')) {
	    $login_force   = intval($_POST['login_force']);
	  }else{
	    $login_force   = 0;
	  }

		if($system_config['allow_login_passwordchg'] == 'on'){
			$member_status_chk_sql = "SELECT passwd FROM root_member WHERE account='$account' AND allow_login_passwordchg = '1';";
			$member_status_chk = runSQLall($member_status_chk_sql);

			if( $member_status_chk['0'] == '1'){
				$return['code'] = '2';
				$return['pwdcsrf'] = csrf_token_make();
				die(json_encode($return));
			}
		}

		// 2FA 如果直接複製網址到別的瀏覽器、無痕或改參數，就導回首頁
		unset($_SESSION["origURL"]);  // 2FA檢查頁面必須是從home.php、login2page 連來的
		unset($_SESSION["check_fa_account"]); // 2fa 帳號
		unset($_SESSION["check_fa_token"]); // 依data指定前往指定的 url

		// --------- IP 檢查------
		// 只要是此IP都無法登入，換帳號也沒用，由客服在後台開啟該IP
		$user_current_ip = explode(',',$_SERVER['HTTP_X_FORWARDED_FOR'])['0'];
		$check_ip = check_attempt_ip($account,$user_current_ip,$debug);
		if($check_ip['success'] == false){
			$return['code'] = '0';
			$return['error'] = $check_ip['messages'];
		}else{

			// 呼叫會員登入檢查函示
			$error = login_check($account, $password, $login_force, $debug);
			// var_dump($error);die();
			if($error['success'] == true) {
			// 登入成功後, 引導到預設的頁面位置
				if($debug == 1){
						var_dump($error);
				}else{

					// 有2fa 而且有token
					if($error['2fa_check'] == true AND isset($_POST['token'])){
						// 導到2FA 檢查頁面，驗證碼檢查完後，也要檢查是否有重複登入
						$member_token = filter_var($_POST['token'],FILTER_SANITIZE_STRING);
						$return['error'] = link_2fa_page($account,$member_token);

					}elseif($error['login_warning'] == true AND $error['2fa_check'] == false AND isset($_POST['token'])){
						// lib_menu.php line:1149 導回home.php
						// $return['code'] = '1';

						// 沒2FA 但重複登入
						$return['code'] = '3';
						$return['error'] = $error['messages']; // alert錯誤訊息

					}else{
						// 沒2FA而且沒重複登入，自動跳轉
						$token = $_POST['token'];
						$return_html = login2page($token, $debug);
						$return['code'] = '1';
						$logger = ($error['messages'] != '') ? '<script>alert("'.$error['messages'].'");</script>' : '';
						$return['error'] = $logger.$return_html;
					}
				}

			}else{
				// 登入失敗, 顯示失敗的訊息原因
				$return['code'] = '0';
				$return['error'] = $error['messages'];
			}
		}
		// -----------------------

		// // 呼叫會員登入檢查函示
		// $error = login_check($account, $password, $login_force, $debug);
		// // var_dump($error);die();
		// if($error['success'] == true) {
		//   // 登入成功後, 引導到預設的頁面位置
		// 	if($debug == 1){
		// 			var_dump($error);
		// 	}else{

		// 		// 有2fa 而且有token
		// 		if($error['2fa_check'] == true AND isset($_POST['token'])){
		// 			// 導到2FA 檢查頁面，驗證碼檢查完後，也要檢查是否有重複登入
		// 			$member_token = filter_var($_POST['token'],FILTER_SANITIZE_STRING);
		// 			$return['error'] = link_2fa_page($account,$member_token);

		// 		}elseif($error['login_warning'] == true AND $error['2fa_check'] == false AND isset($_POST['token'])){
		// 			// lib_menu.php line:1149 導回home.php
		// 			// $return['code'] = '1';

		// 			// 沒2FA 但重複登入
		// 			$return['code'] = '3';
		// 			$return['error'] = $error['messages']; // alert錯誤訊息

		// 		}else{
		// 			// 沒2FA而且沒重複登入，自動跳轉
		// 			$token = $_POST['token'];
		// 			$return_html = login2page($token, $debug);
		// 			$return['code'] = '1';
		// 			$return['error'] = $return_html;
		// 		}
		// 	}

		// }else{
		// 	// 登入失敗, 顯示失敗的訊息原因
		// 	$return['code'] = '0';
		// 	$return['error'] = $error['messages'];
		// }

		// 原版
		// $error = login_check($account, $password, $login_force, $debug);
		// if($error['success'] == true) {
		//   // 登入成功後, 引導到預設的頁面位置
		//   if($debug == 1){
		//     var_dump($error);
		//   }
		// 	// $return_html = '<script>window.location="home.php";</script>';
		// 	// echo $return_html;
		// 	//echo '<script>window.location="home.php";</script>';
		// 	$return['code'] = '1';
		// }else{
		//   // 登入失敗, 顯示失敗的訊息原因
		// 	$return['code'] = '0';
		//    $return['error'] = $error['messages'];
		// }

		echo json_encode($return);
}elseif($action == 'logout') {
	// ----------------------------------------------------------------------------
	// 會員登出，並清除 session - logout
	// ----------------------------------------------------------------------------
	$return_html = login_logout();
	// var_dump($return_html);
	echo $return_html;

}elseif($action == 'factor_check'){
	// 2 FA 驗證碼檢查action

	$debug = 0;
	// 驗證碼
	$verify = isset($_POST['varify_code']) ? filter_string($_POST['varify_code'],"string"): "";
	// 帳號
	$factor_account = isset($_POST['member_account']) ? filter_string($_POST['member_account'],"string") : "";
	// id
	$factor_member_id = isset($_POST['member_id']) ? filter_string($_POST['member_id'],"string") : "";
	// token
	$factor_token = isset($_SESSION['check_fa_token']) ? filter_string($_SESSION['check_fa_token'],"string"): "";

	// 撈出金鑰
	$get_secret_key = sql_member_authentication($factor_member_id);
	// 檢查
	$check_factor = check_factor_auth($get_secret_key[1]->two_fa_secret, $verify,$ga);

	// 驗證碼符合
	if($check_factor){
		$duplicate_member = check_duplicate_member($factor_account,$factor_member_id); // 檢查是否重複登入
		$factor_pass = pass_factor_insert($factor_member_id); // 成功登入insert到memberlogtodb

		if($duplicate_member['login_warning'] == false AND $factor_pass['success'] == true){
			// 沒有重複登入

			// 自動跳轉到指定的url
			$return_html = login2page($factor_token,$debug);
			$return['code'] = '1';
			$return['error'] = $return_html;

		}else{
			// 驗證碼符合，但重複登入，跳出alert
			$return['code'] = '3';
			$return['error'] = $duplicate_member['messages'];

		}

		// 原本
		// $factor_pass = pass_factor_insert($factor_member_id);
		// if($factor_pass['success'] == true){
		// 	// $return_html = '<script>window.location="home.php";</script>';
		// 	// echo $return_html;

		// 	// 自動跳轉到指定的url
		// 	$return_html = login2page($factor_token,$debug);
		// 	echo $return_html;
		// }

	}else{
		// 驗證碼錯誤，寫進memberlogtodb
		// $tr['Verification code error'] = 驗證碼錯誤

		$logger = $tr['Verification code error'];
		$error['messages'] = $logger;
		// 登入失敗, 顯示失敗的訊息原因
		$return['code'] = '0';
		$return['error'] = $error['messages'];

		$msg = $factor_account.$logger;
		$sub_service ='member_authentication';
		memberlogtodb($factor_account,'member','error',$msg,$factor_account,$msg,'f',$sub_service);
	}

	echo json_encode($return);

}elseif($action == 'KeepOneUser') {
	// ----------------------------------------------------------------------------
	// user 有登入下, 只保留當下登入的使用者的 phpsession key
	// ----------------------------------------------------------------------------
	$keepone = Member_runRedisKeepOneUser();
	if($keepone == true) {
		echo $tr['keep current registrants'];//'只保留當下的登入者完成。'
	}



}elseif($action == 'reload_balance') {
	// ----------------------------------------------------------------------------
	// 更新會員帳戶餘額
	// 只更新 session 內的餘額
	// ----------------------------------------------------------------------------
	if(isset($_SESSION['member'])) {

		// 檢查是否建立了錢包資料，如果有資料的話，把錢包帶進來。沒有的話建立錢包。
		$rcode = get_member_wallets($_SESSION['member']->id);
		if($rcode['code'] == '1') {
			// 同時更新了 $_SESSION['member'] 變數
		}else{
			$logger = 'Re-obtain the wallet information exception'.$rcode['messages'];
			die($logger);
		}

		// 從錢包 session 取得餘額的狀態, 如果更新坐在 reload balacne 按鈕上面
		$show_currencybalance_gcash = $_SESSION['member']->gcash_balance;
		$show_currencybalance_gtoken = $_SESSION['member']->gtoken_balance;
		// 把兩個錢包合成一個顯示, 餘額加總
		$show_currencybalance = $_SESSION['member']->gtoken_balance + $_SESSION['member']->gcash_balance;
		// 用標準格式顯示
		//$show_currencybalance_fmt_html = money_format('%i', $show_currencybalance);
		// $show_currencybalance_fmt_html = '<span class="glyphicon glyphicon-yen" aria-hidden="true"></span>'.$show_currencybalance;
		$show_currencybalance_fmt_html =   '$'.number_format($show_currencybalance,2);

		$hide_gcash_mode = hide_gcash_mode();//隱藏現金模式

		// new in button
		// $show_wallet_balance_html = '<span class="label label-success">Cash'.$_SESSION['member']->gcash_balance.'</span>';
		$show_wallet_balance_html = '';
		// token 是否在娛樂城??
		if($_SESSION['member']->gtoken_lock == NULL) {
			$is_gtoken_lock=false;
			//現金 + 代幣 合併顯示, 滑鼠移動才提示

			if ($hide_gcash_mode == 'on') {
				$reload_balance_title_text = $tr['token'].$show_currencybalance_gtoken;
			}else{
				$reload_balance_title_text = $tr['cash'].$show_currencybalance_gcash.','.$tr['token'].$show_currencybalance_gtoken;
			}

			// gtoken 沒有被使用, 沒有在任何娛樂城. 用綠色表示.$tr['account balance']帳戶餘額
			//$show_wallet_balance_html = $show_wallet_balance_html.'<span class="label label-info">Token'.$_SESSION['member']->gtoken_balance.'</span>';
			$show_wallet_balance_html = $show_wallet_balance_html.'<span class="badge badge-success">'.$show_currencybalance_fmt_html.'<span class="glyphicon glyphicon-refresh ml-2" aria-hidden="true"></span></span>';
		}else{
			$is_gtoken_lock=true;

			if ($hide_gcash_mode == 'on') {
				$reload_balance_title_text = $tr['token'] .$tr['casino used'].'@'.$casinoLib->getCasinoNameByCasinoId($_SESSION['member']->gtoken_lock, $_SESSION['lang']);
			}else{
				$reload_balance_title_text = $tr['cash'].$show_currencybalance_gcash.','.$tr['token'] .$tr['casino used'].'@'.$casinoLib->getCasinoNameByCasinoId($_SESSION['member']->gtoken_lock, $_SESSION['lang']);
			}

			//$reload_balance_title_text = $tr['cash'].$show_currencybalance_gcash.','.$tr['token'] .$show_currencybalance_gtoken.'@'.$tr[$_SESSION['member']->gtoken_lock.' Casino'];

			// 用紅色表示, gtoken 在娛樂城.$tr['account balance'] 帳戶餘額
			//$show_wallet_balance_html = $show_wallet_balance_html.'<span class="label label-danger">Token'.$_SESSION['member']->gtoken_balance.'@'.$_SESSION['member']->gtoken_lock.'</span>';
			$show_wallet_balance_html = $show_wallet_balance_html.'<span class="badge badge-danger">'.$show_currencybalance_fmt_html.'<span class="glyphicon glyphicon-refresh ml-2" aria-hidden="true"></span></span>';
		}

		// 提示文字 , 點擊立即更新目前餘額
		$tooltip_banance_show_html = $tr['click to update balance'].','.$reload_balance_title_text;
		// 目前餘額 for mobile
		$modal_banance_show_html = $reload_balance_title_text;

		if(isset($_POST['send_reload_balance']) AND $_POST['send_reload_balance']){
			echo json_encode(['is_gtoken_lock'=>$is_gtoken_lock,'balance_num'=>number_format($show_currencybalance,2),'balance'=>$show_wallet_balance_html,'tooltip'=>$tooltip_banance_show_html,'mobile_modal'=>$modal_banance_show_html]);

			// 顯示 alert 詳細資訊
			// echo "<script>alert('".$show_wallet."');</script>";
			// echo '<script>location.reload("true");</script>';
			// 轉移到錢包的目錄，觀看目前的錢包狀態。
			/*echo '<script language="javascript">
			function confirm_wallet_balance() {
			  if (confirm("更新成功，是否要前往钱包观看详细资讯？") == true) {
			    document.location.href="wallets.php";
			  } else {
			    return false;
			  }
			}
			confirm_wallet_balance();
			</script>';*/
		}else{
			echo json_encode(['is_gtoken_lock'=>$is_gtoken_lock,'balance_num'=>number_format($show_currencybalance,2),'balance'=>$tr['account balance'].$show_currencybalance_fmt_html,'tooltip'=>$tr['account balance'].$show_currencybalance_fmt_html,'mobile_modal'=>$show_currencybalance_fmt_html]);
		}
	}else{
		// 不動作
	}

}elseif($action == 'changewebpanel' AND isset($_POST['t'])) {
	if($_POST['t'] == 'mobile' OR $_POST['t'] == 'computer'){
		if($_POST['t'] == 'mobile') {
			$_SESSION['site_mode'] = 'mobile';
		}else{
			$_SESSION['site_mode'] = 'desktop';
		}
		echo $_SESSION['site_mode'];
	}else{
		echo 'fail variable!';
	}
}elseif($action == 'login_pwdchg') {
	// ----------------------------------------------------------------------------
	// 登入時強制變更密碼
	// ----------------------------------------------------------------------------
	    // var_dump($_POST);
			// 檢查密碼是否有被變更過
			if($_POST['npassword'] == sha1($_POST['npasswordc'])){
			  $account        = strtolower(filter_var($_POST['account'], FILTER_SANITIZE_STRING));
			  $password       = filter_var($_POST['npassword'], FILTER_SANITIZE_STRING);

				// 強制更新密碼
				$updatepwd_sql = 'UPDATE root_member SET passwd = \''.$password.'\', changetime = now(),allow_login_passwordchg = \'0\' WHERE account = \''.$account.'\';';
				// var_dump($updatepwd_sql);
				$updatepwd_result = runSQL($updatepwd_sql);
				if($updatepwd_result == 1) {
		      $logger = "Member = {$account} change password to {$_POST['npasswordc']} success.";
		      $msg=$tr['Force password change success'];//'[系统强制修改密码]会员个人密码修改完成。'
		      $msg_log = $logger;
		      $sub_service='information';
		      memberlogtodb($account,'member','info',"$msg",$account,"$msg_log",'f',$sub_service);

					// 進入登入程序
					$error = login_check($account, $password, '0', $debug);
					// var_dump($error);
				  if($error['success'] == true) {
				    // 登入成功後, 引導到預設的頁面位置
						$return['code'] = '1';
			      $logger = $tr['password change success'];//"会员个人密码修改完成。"
				    $return['msg'] = $logger;
				  }else{
				    // 登入失敗, 顯示失敗的訊息原因
						$return['code'] = '0';
				    $return['error'] = $error['messages'];
				  }
		    }else{
		      $logger = $tr['Password update error'];//"密码更新错误，请洽客服人员。"
					$return['code'] = '11';
			    $return['error'] = $logger;
		    }
			}else{
				$logger = $tr['Password update error'];//"密码更新错误，请重新输入。"
				$return['code'] = '2';
				$return['error'] = $logger;
			}

			echo json_encode($return);
}elseif($action == 'test') {
	// ----------------------------------------------------------------------------
	// test developer
	// ----------------------------------------------------------------------------
	//     var_dump($_POST);

}



?>
