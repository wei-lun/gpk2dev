<?php
// -------------------------------------------
// Features: 前台 2FA lib
// File Name: member_authentication_lib.php
// Author: Mavis
// Related: member_authentication.php,member_authentication_check.php,member_authentication_action.php
// Log:
// --------------------------------------------

// 停用驗證問題
$disable_question=[
	1=>$tr['What is the name of your best friend in your youth?'],
	2=>$tr['What is the name of your first pet?'],
	3=>$tr['What was the first dish you learned to cook?'],
	4=>$tr['Which movie did you watch at the cinema for the first time?'],
	5=>$tr['Where did you go, the first time you ever flew on an airplane?'],
	6=>$tr['What was your favorite teacher is surname when you were in elementary school?'],
];

function filter_string($var,$type="string"){
	switch($type){
		case 'string':
			$var = isset($var) ? filter_var($var,FILTER_SANITIZE_STRING) : "";
			break;
		case 'url':
			$var = isset($var) ? filter_var($var,FILTER_SANITIZE_URL) : "";
			break;
		case 'email':
			$var = isset($var) ? filter_var($var,FILTER_SANITIZE_EMAIL) : "";
			break;
		case "int":
		default:
			$var = isset($var) ? filter_var($var,FILTER_SANITIZE_NUMBER_INT) : "";
			break;
	}
	return $var;
}

// 清除2FA 要用到的session，導回首頁
function clear_session(){
	unset($_SESSION['origURL']);
	unset($_SESSION['check_fa_account']);
	unset($_SESSION["check_fa_token"]);
	// var_dump($_SESSION);die();
	header('Location:home.php');
    die(); 
}

// 開啟2fa 才會有
// 從登入畫面連到2FA檢查頁面
function link_2fa_page($member_account,$token){
	$_SESSION["origURL"] = array('home.php','login2page.php'); // 2FA檢查頁面必須是從home.php、login2page 連來的
	$_SESSION["check_fa_account"] = $member_account; // 2fa 帳號
	$_SESSION["check_fa_token"] = $token; // 2fa token，為 login2page($token, $debug=1) 指定前往指定的 url
	// var_dump($token);die();

	$encode_account = base64_encode($member_account);
	$return_html= '<script>window.location="member_authentication_check.php?a='.$encode_account.'";</script>';

	return ($return_html);
}

// 撈出使用者資料
function member_data($id){
	$sql=<<<SQL
		SELECT * FROM root_member 
		WHERE id = '{$id}'
SQL;
	return runSQLall($sql);
}

// 找所有user
function get_all_member_data(){
	$sql=<<<SQL
		SELECT id,account FROM root_member
		WHERE status = '1'
SQL;
	$result = runSQLall($sql);
	unset($result[0]);

  	foreach ($result as $val){
		$return[$val->account]['id'] = $val->id;
		$return[$val->account]['account'] = $val->account;
	}
  	return $return;
}

// 撈出使用者認證資料
function sql_member_authentication($id){
	$sql=<<<SQL
		SELECT * FROM root_member_authentication
		WHERE id='{$id}'
SQL;
	return runSQLall($sql);
}

// 新增2FA
function insert_member_authentication($id,$factor_questions,$secret_key,$factor_ans){
	$sql=<<<SQL
		INSERT INTO root_member_authentication 
		(id,changetime,two_fa_status,two_fa_question,two_fa_secret,two_fa_ans) 
		VALUES ('{$id}',now(),'1','{$factor_questions}','{$secret_key}','{$factor_ans}') 
SQL;

	return runSQLall($sql);
}

// 2FA 狀態改成停用
function update_factor_disable($id){
	$sql=<<<SQL
		UPDATE root_member_authentication 
			SET two_fa_status = '0'
			WHERE id = '{$id}'
SQL;

	return runSQLall($sql);
}

// 修改2階段驗證資料(關->開)
function update_member_authentication($id,$factor_questions,$secret_key,$factor_ans){
	$sql=<<<SQL
		UPDATE root_member_authentication 
		SET changetime = now() , 
			two_fa_status   = '1',
		    two_fa_question = '{$factor_questions}',
		    two_fa_secret   = '{$secret_key}',
		    two_fa_ans      = '{$factor_ans}'
		WHERE id            = '{$id}';
SQL;
	return runSQLall($sql);
}


// 產生驗證金鑰及QR Code
function generate_secret($ga,$id_query){
    $secret    = $ga->createSecret();
    $qrCodeUrl = $ga->getQRCodeGoogleUrl($id_query, $secret);

    $return['secret']    = $secret;
    $return['qrCodeUrl'] = $qrCodeUrl;
    
    return $return;
}

// 驗證金錀
function verify_secret($secret_id, $verify_code,$ga){
	$checkresult['check_result'] = $ga->verifyCode($secret_id, $verify_code, 3); // 3 = 3*30秒 时钟容差
	return $checkresult;
}

// 2fa 驗證碼檢查
function check_factor_auth($secret_id,$verify,$ga){
	// 驗證碼大約1分30秒換新的
	// 3 = 3*30秒 时钟容差
	$check_factor = $ga->verifyCode($secret_id,$verify,3);
  
	return $check_factor;
}

// 檢查是否重複登入
function check_duplicate_member($account,$id){

	global $config;
	global $system_config;
	global $redisdb;
	global $tr;

  	$check_duplicate['success'] = true;
	$check_duplicate['messages'] = '';
	  
	// 重複登入預設false
	$check_duplicate['login_warning'] = false;

	// 前台 + 專案站台 + 帳號 , 寫入 redisdb 識別是否單一登入使用
	$value = $config['projectid'].'_front_'.$account;
	// 目前程式所在的 session , 需要加上 phpredis_session
	$session_id = session_id();
	// db 2 自己寫出來的 session, save member session data,
	$sid = sha1($value).':'.$session_id;
	// db 0 系統的 php session
	$phpredis_sid = 'PHPREDIS_SESSION:'.$session_id;
	// var_dump($_SESSION);
	// var_dump($session_id);
	// var_dump($sid);
	// var_dump($phpredis_sid);
	// var_dump($_COOKIE);
	// die();

	// 取該會員資料
	$m_data = member_data($id);
	// var_dump($m_data);die();

	if($m_data[0] == 1 AND $check_duplicate['success'] == true){

		// member login session record in redisdb 2
		// 檢查系統中使用者是否已經存在，如果已經存在的話就不允許登入。或是讓使用者選擇刪除這個
		$checkuser_result = Member_CheckLoginUser($value);
		// var_dump($checkuser_result);die();
		// 強迫登入的話，就可以跳過這段。
		if($checkuser_result['count'] == 0 ) {
			// $logger = '同帳號'.$account.'沒有其他人登入系統，可以繼續工作';
		}elseif($checkuser_result['count'] == 1 ){
			// 只有一個人在系統內 , 判斷是否和當前 session id 一致, 不同的話就砍除 session and db sid
			$online_users = array();
			$online_users = explode(",",$checkuser_result['value']);
			// example: gp01_front_mtchang
			$online_user_account = explode("_", $online_users[0]);
			$online_user_details = [];
			$online_user_details['site'] = $online_user_account[0];
			$online_user_details['type'] = $online_user_account[1];
			$online_user_details['account'] = $online_user_account[2];
			$online_user_details['time'] = date('Y-m-d H:i:s',$online_users[1]);
			$online_user_details['page'] = $online_users[2];
			$online_user_details['ip'] = $online_users[3];
			$expiretime_chk = time() - strtotime($online_user_details['time']);
			// var_dump($expiretime_chk);
			// var_dump($online_user_details);
			// var_dump($checkuser_result);die();

			// if($expiretime_chk <= ini_get("session.gc_maxlifetime")){
			$rrdel = [];
			if($expiretime_chk <= 60){
				if($sid != $checkuser_result['key']) {
					// $alert_message_html = '強制剔除重複的帳號'.$online_user_details['account'].'登入時間'.$online_user_details['time'].'來自於IP'.$online_user_details['ip'].'目前操作'.$online_user_details['page'];
					//'帳號'在時間於IP登入已經強制登出該帳號。
					$alert_message_html = $tr['Account'].$online_user_details['account'].', '.$tr['In time'].$online_user_details['time'].', '.$tr['In IP'].$online_user_details['ip'].$tr['Login'].', '.$tr['account has been forced to log out'];
					// echo "<script>alert('$alert_message_html');</script>";

					// 取得使用者的 session 資訊
					$phpsid = explode(':', $checkuser_result['key']);
					//var_dump($phpsid);
					$alive_phpsession_sid = 'PHPREDIS_SESSION:'.$phpsid[1];
					$rrdel[0] = runRedisDEL($alive_phpsession_sid, 0);
					$rrdel[1] = runRedisDEL($checkuser_result['key'], $redisdb['db']);
					// 刪除系統中的以存在的 key
					$logger = '刪除系統中已經登入的帳號 key '.$checkuser_result['key'].' *PHP SESSION: '.$rrdel[0].' *FRONT: '.$rrdel[1];
					// memberlog 2db('guest','login','notice', "$logger");
					// var_dump($logger);die();

					// --------------------------
					// 2019.6.14
					// 重複登入資訊寫進memberlogtodb
					$check_duplicate['login_warning'] = true;
					$check_duplicate['messages'] = $alert_message_html; 

					$msg = $check_duplicate['messages'];
					$msg_log = $logger;
					$sub_service = 'logout';
					memberlogtodb('guest','member','error',"$msg",$account,"$msg_log",'f',$sub_service);
					// ----------------------------

				}
			}else{
				// 刪除系統中已過期的 key
				// 取得使用者的 session 資訊
				$phpsid = explode(':', $checkuser_result['key']);
				//var_dump($phpsid);
				$alive_phpsession_sid = 'PHPREDIS_SESSION:'.$phpsid[1];
				$rrdel[0] = runRedisDEL($alive_phpsession_sid, 0);
				$rrdel[1] = runRedisDEL($checkuser_result['key'], $redisdb['db']);
			}
	/*
			// $rhtml = '已經有使用者('.$online_user_details['account'].' 時間：'.$online_user_details['time'].' 來源IP：'.$online_user_details['ip'].')，登入在系統內。';
			$logger = '重复使用者(IP:'.$online_user_details['ip'].')已登入';
			$error['success'] = false;
			$error['messages'] = $error['messages'].$logger;

			if($login_force == 1) {
				// 如果使用者強制登入的話，先刪除在系統中的使用者。
				$rr = runRedisDEL($checkuser_result['key'], $redisdb['db']);
				$logger = '删除已存在的使用者，强制登入。';
				$error['success'] = true;
				$error['messages'] = $error['messages'].$logger;
			}
	*/
		}else{
			// 很多使用者再系統內的處理。
			$logger = '有重复的使用者'.$checkuser_result['count'].'人以上登入，全体退出系统后重新登入。';
			$check_duplicate['success'] = false;
			$check_duplicate['messages'] = $check_duplicate['messages'].$logger;
		}
		// 檢查系統同帳號，是否已經有人在別的地方登入。 end

		// 帳號狀態, 0=會員停權disable 1= 會員啟用enable 2=會員錢包凍結
		// 帳號密碼正確, 但是帳戶已經被鎖定
		if($m_data[1]->status == 0 ) {
			$logger = $tr['Account has been locked'];//'你的帐号已经被锁定，联络客服人员处理。'
			// $logger = 'Your account has been locked, please contact customer service.';
			$check_duplicate['success'] = false;
			$check_duplicate['messages'] = $check_duplicate['messages'].$logger;
		}

		if($m_data[1]->status == 2 ) {
			$logger = $tr['Your wallet has been frozen, please contact customer'];//'你的帐号暫時被凍結，请联络客服人员处理。'
			$check_duplicate['success'] = false;
			$check_duplicate['messages'] = $check_duplicate['messages'].$logger;
		}
		
	}else{
		//$logger = $account.'帐号或密码错误, 或是使用者不存在.';
		$logger = $tr['The account number, password is incorrect, or the user does not exist.'];//'帐号、密码错误，或是使用者不存在。'
		$check_duplicate['success'] = false;
		$check_duplicate['messages'] = $check_duplicate['messages'].$logger;
	}
	
	return($check_duplicate);
}

// 如果驗證碼符合，寫入memberlogtodb
function pass_factor_insert($id){
	global $config;
	global $system_config;
	global $redisdb;
	global $tr;

	$factor_check['success'] = true;
	$factor_check['messages'] = '';
	
	// 取該會員資料
	$m_data = member_data($id);
	
	// 如果有 fingerprint 的話,紀錄
	if(isset($_SESSION['fingertracker'])){
		$fingertracker = $_SESSION['fingertracker'];
	}else{
		$fingertracker = 'NOFINGER';
	}

	// 前台 + 專案站台 + 帳號 , 寫入 redisdb 識別是否單一登入使用
	$value = $config['projectid'].'_front_'.$m_data[1]->account;
	// 目前程式所在的 session , 需要加上 phpredis_session
	$session_id = session_id();
	// db 2 自己寫出來的 session, save member session data,
	$sid = sha1($value).':'.$session_id;
	// db 0 系統的 php session
	$phpredis_sid = 'PHPREDIS_SESSION:'.$session_id;
	//var_dump($_SESSION);
	//var_dump($session_id);
	//var_dump($sid);
	//var_dump($phpredis_sid);
	//var_dump($_COOKIE);
	//die();

	// 此為 user 登入成功的處理
	if($factor_check['success'] == true and $m_data[0] == 1) {
		// 將使用者資訊存到 session
		$_SESSION['member'] = $m_data[1];
		//檢查是否開啟現金帳戶隱藏模式
		//$_SESSION['hide_gcash_mode'] = set_hide_gcash_mode();
		//var_dump($_SESSION['member']->gcash_log_exist);
		//var_dump(check_gcash_log_exist($_SESSION['member']->gcash_log_exist));
			check_gcash_log_exist($_SESSION['member']->gcash_log_exist);
			// 檢查是否建立了錢包資料，如果有資料的話，把錢包帶進來。沒有的話建立錢包。
		$rcode = get_member_wallets($m_data[1]->id);

		if($rcode['code'] == '1') {
			// 同時更新了 $_SESSION['member'] 變數

				// 將這個會員 or 代理商註冊在 redis db 內, 避免重複登入的問題
				// 寫入 redis server 的資訊
				// 從哪裡點擊來的 , 後台呈現資料使用
				if (!empty($_SERVER['HTTP_REFERER'])) { $http_referer = $_SERVER['HTTP_REFERER']; }else{ $http_referer = "No HTTP_REFERER";	}
				$value = $value.','.time().','.$_SERVER["SCRIPT_NAME"].','.$_SERVER["REMOTE_ADDR"].','.$fingertracker.','.$http_referer.','.$_SERVER["HTTP_USER_AGENT"];
				$rrset = runRedisSET($sid, $value);

			// 取得使用者預設的語系
			$_SESSION['lang'] = $m_data[1]->lang;

			//$logger =  $account.'登入成功';
			// $logger = $account.' sign in suceesfully';
			// $error['messages'] = $error['messages'].$logger;
			// 紀錄這次的登入
			// $logger2db = json_encode($error);
			$msg=$m_data[1]->account.$tr['sign in suceesfully'];//'登入成功!'
			$msg_log = 'member_authentication_lib.php:225';
			// $msg_log = $m_data[1]->account.'双重验证登入成功';
			// $msg_log = 'login2page_lib.php:428'; 

			$sub_service='login';
			memberlogtodb($m_data[1]->account,'member','info',"$msg",$m_data[1]->account,"$msg_log",'f',$sub_service);
			// memberlog 2db($account,'login','info', "$logger2db");
				//echo '登入成功, 會員資料已經載入.';
				if(is_null ($m_data[1]->lastlogin) OR empty($m_data[1]->lastlogin)){
						$lastseclogin='';
				}else{
						$lastseclogin="lastseclogin ='".$m_data[1]->lastlogin."',";
				}
				$update_logintime="	UPDATE	root_member SET
														".$lastseclogin." lastlogin = NOW()
														WHERE	id ='".$m_data[1]->id."';";
				// var_dump($update_logintime);die();
				runSQLall($update_logintime);
		}else{
			//$logger = '登入後重新取得钱包资料异常'.$rcode['messages'];
			$logger = 'Re-obtain the wallet information exception '.$rcode['messages'];
			$factor_check['success'] = false;
			$factor_check['messages'] = $factor_check['messages'].$logger;
		}
	}else{
		// 紀錄這次的登入所有的錯誤
		// $logger2db = json_encode($error);
		$msg = $factor_check['messages'];
		$msg_log = $m_data[1]->account.$logger;
		$sub_service='login';
		memberlogtodb('guest','member','error',"$msg","$m_data[1]->account","$msg_log",'f',$sub_service);
	}

	return($factor_check);
}

?> 