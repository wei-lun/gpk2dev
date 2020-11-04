<?php
// ----------------------------------------------------------------------------
// Features:	針對 member 登入檢查的處理, 預設給 member 資料相關的程式使用。
// File Name:	login2page_action.php
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
// login2page 此功能的自訂函式庫
require_once dirname(__FILE__) ."/login2page_lib.php";
// 2fa
require_once dirname(__FILE__) ."/member_authentication_lib.php";


// -----------------------------------------------------------------------------
// 前台 action 會員登入身份專用：檢查有沒有參數,以及是否帶有 session
if(isset($_GET['a'])) {
	$action = filter_var($_GET['a'], FILTER_SANITIZE_STRING);

	// 如果是登出系統, by pass　CSRF check
	if($action == 'logout') {
		// by pass CSRF check
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
// 	if($action == 'logout') {
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
// var_dump($_SESSION);
// var_dump($_POST);
// var_dump($_GET);
// die();
$debug = 0;


// ----------------------------------
// 動作為會員登入檢查 login_check
// ----------------------------------
if($action == 'login_check') {
  // 取得傳入的值
  //$account        = strtolower(filter_var($_POST['account'], FILTER_SANITIZE_STRING));
  //$password       = filter_var($_POST['password'], FILTER_SANITIZE_STRING);



  // 帳號格式
  $account        = strtolower(filter_var($_POST['account'], FILTER_SANITIZE_STRING));
  $accunt_regexp = '/^[a-z0-9]{3,16}$/';
  $accunt_regexp_check = filter_var($account, FILTER_VALIDATE_REGEXP,array("options"=>array("regexp"=>"$accunt_regexp")));
  // var_dump($accunt_regexp_check);
  if($accunt_regexp_check == false) {
    // 帳號格式錯誤
    // $logger = $account.'account format error';
    // memberlog 2db('guest','login','error', "$logger");
    $msg=$tr['account format error'];//'帐号格式错误，或長度需介于3~16码之间。'
    $msg_log = $account.$tr['account format error'];//'帐号格式错误，或長度需介于3~16码之间。'
    $sub_service='login';
    memberlogtodb('guest','member','error',"$msg",$account,"$msg_log",'f',$sub_service);
    die($logger);
  }

  // 檢查密碼格式，需要為  sha1
  $password        = filter_var($_POST['password'], FILTER_SANITIZE_STRING);
  $password_regexp      = '/^[a-fA-F0-9]{40}/';
  $password_regexp_check = filter_var($password, FILTER_VALIDATE_REGEXP,array("options"=>array("regexp"=>"$password_regexp")));
  // var_dump($accunt_regexp_check);
  if($accunt_regexp_check == false) {
    // 密碼格式錯誤
    // $logger = $account.' of '.$u['password'].' password format is incorrect';
    // memberlog 2db('guest','login','error', "$logger");
    $msg=$account.':'.$tr['wrong password'];
    $msg_log = $tr['wrong password'];//'密码错误!'
    $sub_service='login';
    memberlogtodb('guest','member','error',"$msg",$account,"$msg_log",'f',$sub_service);
    die($logger);
  }


  // 強迫登入
  if(isset($_POST['login_force']) AND ($_POST['login_force'] == '1' OR $_POST['login_force'] == '0')) {
    $login_force   = intval($_POST['login_force']);
  }else{
    $login_force   = 0;
  }
  // 呼叫登入檢查
  $error = login_check($account, $password, $login_force, $debug);
  if($error['success'] == true) {
    // var_dump($error);
						$logger = ($error['messages'] != '') ? 'alert("'.$error['messages'].'");' : '';
    echo '<script>'.$logger.'window.location="home.php";</script>';
  }else{
    //var_dump($error);
    echo $error['messages'];
  }

}elseif($action == 'logout') {
  // ----------------------------------------------------------------------------
	// 會員登出，並清除 session - logout
	// ----------------------------------------------------------------------------
	$return_html = login_logout();
	// var_dump($return_html);
	echo $return_html;




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
      $logger = $tr['Regaining wallet data is abnormal'].$rcode['messages'];//'重新取得錢包資料異常'
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


    // new in button
    // $show_wallet_balance_html = '<span class="label label-success">Cash'.$_SESSION['member']->gcash_balance.'</span>';
    $show_wallet_balance_html = '';
    // token 是否在娛樂城??
    if($_SESSION['member']->gtoken_lock == NULL) {
      // gtoken 沒有被使用, 沒有在任何娛樂城. 用綠色表示
      //$show_wallet_balance_html = $show_wallet_balance_html.'<span class="label label-info">Token'.$_SESSION['member']->gtoken_balance.'</span>';
      $show_wallet_balance_html = $show_wallet_balance_html.'<span class="label label-info">'.$tr['account balance'].$show_currencybalance_fmt_html.'</span>';
    }else{
      // 用紅色表示, gtoken 在娛樂城
      //$show_wallet_balance_html = $show_wallet_balance_html.'<span class="label label-danger">Token'.$_SESSION['member']->gtoken_balance.'@'.$_SESSION['member']->gtoken_lock.'</span>';
      $show_wallet_balance_html = $show_wallet_balance_html.'<span class="label label-danger">'.$tr['account balance'].$show_currencybalance_fmt_html.'</span>';
    }
    echo $show_wallet_balance_html;

    // 顯示 alert 詳細資訊
    // echo "<script>alert('".$show_wallet."');</script>";
    // echo '<script>location.reload("true");</script>';
    // 轉移到錢包的目錄，觀看目前的錢包狀態。
    echo '<script language="javascript">document.location.href="wallets.php";</script>';

  }else{
      // 不動作
  }


}elseif($action == 'login2page') {
// ----------------------------------------------------------------------------
//
// ----------------------------------------------------------------------------
    // var_dump($_POST);
    // var_dump($_GET);
    // var_dump($_SESSION);
    // die();

  // 取得傳入的值
  $account        = strtolower(filter_var($_POST['account'], FILTER_SANITIZE_STRING));
  $password       = filter_var($_POST['password'], FILTER_SANITIZE_STRING);
  // 強迫登入
  if(isset($_POST['login_force']) AND ($_POST['login_force'] == '1' OR $_POST['login_force'] == '0')) {
    $login_force   = intval($_POST['login_force']);
  }else{
    $login_force   = 0;
  }


	if($system_config['allow_login_passwordchg'] == 'on'){
		$member_status_chk_sql = "SELECT passwd FROM root_member WHERE account='$account' AND allow_login_passwordchg = '1';";
		$member_status_chk = runSQLall($member_status_chk_sql);
		// var_dump($member_status_chk);

		if( $member_status_chk['0'] == '1'){
			$return['code'] = '2';
			$return['pwdcsrf'] = csrf_token_make();
			die(json_encode($return));
		}
	}

  // 2FA 如果直接複製網址到別的瀏覽器、無痕或改參數，就導回首頁
  unset($_SESSION["origURL"]); // 2fa來源
  unset($_SESSION['check_fa_account']); // 2fa 帳號
  unset($_SESSION["check_fa_token"]);

  // 呼叫會員登入檢查函示
  $error = login_check($account, $password, $login_force, $debug);
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

					$return['code'] = '3';
					$return['error'] = $error['messages']; // alert錯誤訊息

				}else{
					// 沒2FA而且沒重複登入，自動跳轉
					$token = $_POST['token'];
					$return_html = login2page($token, $debug);
					$return['code'] = '1';
					$return['error'] = $return_html;
        }

    }
  }else{
    // 登入失敗, 顯示失敗的訊息原因
		$return['code'] = '0';
		$return['error'] = $error['messages'];
  }

	echo json_encode($return);
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
			      $logger = $tr['password change success'];//"会员个人密码修改完成。"
				    $token = $_POST['token'];
				    $return_html = login2page($token, $debug);
						$return['code'] = '1';
						$return['error'] = $return_html;
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
    var_dump($_POST);
    var_dump($_GET);
    var_dump($_SESSION);
}



?>
