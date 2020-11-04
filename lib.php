<?php
// ----------------------------------------
// Features:	前台 -- JIGDEMO 專用 PHP lib 函式庫
// 						和後台的不一樣，專屬於前台的 LIB
// File Name:	lib.php
// Author:		Barkley
// Related:
// Log:
// 2017.7.14 by Barkley 拆分 lib
// -----------------------------------------------------------------------------

// 原本的 lib.php 拆分為三個, 顺序如下
// lib_common.php 專門的資料庫存取函式 , 由 config.php 程式護機
// lib.php 專門放置單一登入控制的函式, 由每個使用者的 *.php 呼叫使用
// lib_menu.php 這個專門負責系統選單部份功能(有程式判斷), 由 lib.php 呼叫



// 系統選單
require_once dirname(__FILE__) ."/lib_menu.php";


/*
// function 索引及說明：
// -------------------

// 單一登入控制 REDIS 選單專用 function 索引及說明：
// -----------------------------------
1. runRedisSET 設定一個 redis key and value
2. runRedisDEL 刪除一個 redis key
3. RegSession2RedisDB 如果使用者有登入, session_start 則 redis set 就要寫入資料並延長一次時間.
4. runRedisKeepOneUser 建立一個 redis client 連線，刪除除了當下的 session 以外，刪除所有同使用者的 key
5. Member_CheckLoginUser  檢查目前 redis db 2 內，有多少已經登入的使用者 member 資訊。 已當前登入者為資訊。
*/



// ----------------------------------------------------------------------------
// 這個區段為 session 的檢查及註冊，主要是用來判斷前台是否有重複的登入帳號。
// ----------------------------------------------------------------------------

// --------------------------------------
// 設定一個 redis key and value
// 參考文件:https://github.com/phpredis/phpredis#connect-open
// use: runRedisSET($sid, $value, $expire)
// 成功傳回 1 失敗傳回 0
// --------------------------------------
function runRedisSET($key, $value, $expire=14400) {

	global $redisdb;
	// 預設 DB 定義在全域變數
	if(isset($redisdb['db'])) {
		$db = $redisdb['db'];
	}else{
		die('No select RedisDB');
	}


	$redis = new Redis();
	// 2 秒 timeout
	if($redis->pconnect($redisdb['host'], 6379, 1)) {
		// success
		if($redis->auth($redisdb['auth'])) {
			// echo 'Authentication Success';
		}else{
			return(0);
			die('Authentication failed');
		}
	}else{
		// error
		return(0);
		die('Connection Failed');
	}
	// 選擇 DB
	$redis->select($db);
	// echo "Server is running: ".$redis->ping();

	// 設定 timeout 值, 目前的時間往後加上去
	$server_time 	= time();
	// $expire			= 14400;
	$expire_time	= $server_time + $expire;

	$r[1] = $redis->set($key, $value);
	$r[2] = $redis->expireAt($key, $expire_time);

	// var_dump($r);die();

	return(1);
}


// --------------------------------------
// 刪除一個 redis key
// 參考文件:https://github.com/phpredis/phpredis#connect-open
// use: runRedisDEL($key, $db)
// 成功傳回 1 失敗傳回 0
// --------------------------------------
function runRedisDEL($key, $db) {

	global $redisdb;
	if(!isset($db)) {
		die('No select RedisDB');
	}

	$redis = new Redis();
	// 2 秒 timeout
	if($redis->pconnect($redisdb['host'], 6379, 1)) {
		// success
		if($redis->auth($redisdb['auth'])) {
			// echo 'Authentication Success';
		}else{
			return(0);
			die('Authentication failed');
		}
	}else{
		// error
		return(0);
		die('Connection Failed');
	}
	// 選擇 DB
	$redis->select($db);
	// echo "Server is running: ".$redis->ping();

	$redis->delete($key);

	return(1);
}


// ----------------------------------------------------------------------------
// 如果使用者有登入, session_start 則 redis set 就要寫入資料並延長一次時間.
// 取得儲存於 session 中的 name and id , 當使用者登入成功時. 紀錄這些 id. 於 redis server 上面. 統計有多少同 id 使用者在線上.
// php session 改成預設寫入寫在 redisdb db 0 上面
// redisdb DB 1 為後台的 login 資訊
// redisdb DB 2 為前台的 login 資訊
// user: RegSession2RedisDB()
// 此為前台專用
// ----------------------------------------------------------------------------
function RegSession2RedisDB() {

	// 取得目前的系統專案代碼
	global $config;
	global $redisdb;
	if(isset($redisdb['db'])) {
		$db = $redisdb['db'];
	}else{
		die('No select RedisDB');
	}

	// 判斷使用者的 session 是否存在
	if(isset($_SESSION['member'])) {
		// 前台 + 專案站台 + 帳號
		$value = $config['projectid'].'_front_'.$_SESSION['member']->account;
		// $value = $_SESSION['member']->account;
		// 目前程式所在的 session , 需要加上 phpredis_session
		$session_id = session_id();
		// db 2 自己寫出來的 session, save member session data
		$sid = sha1($value).':'.$session_id;
		// db 0 系統的 php session
		$phpredis_sid = 'PHPREDIS_SESSION:'.$session_id;

		// echo "目前程式所在的 session , 需要加上 phpredis_session";
		// var_dump($session_id);
		// echo "db 2 自己寫出來的 session, save member session data";
		// var_dump($sid);
		// echo "db 0 系統的 php session";
		// var_dump($phpredis_sid);

		// browser reload 則 redis session 又會跑回來，處理方式要 remove cookie
		$logger = "當登入後，發現有2個 redisdb in db ".$redisdb['db']." session data ，就刪除其他人的，並且移除這個 login cookie";
		//var_dump($logger);
		$checkuser_result = Member_CheckLoginUser($value);
		// var_dump($checkuser_result);

		// 從哪裡點擊來的 , 後台呈現資料使用
		if (!empty($_SERVER['HTTP_REFERER'])) {
		    $http_referer = $_SERVER['HTTP_REFERER'];
		}else{
		    $http_referer = "No HTTP_REFERER";
		}
		// 寫入 redis server 的資訊
		$value = $value.','.time().','.$_SERVER["SCRIPT_NAME"].','.$_SERVER["REMOTE_ADDR"].','.$http_referer.','.$_SERVER["HTTP_USER_AGENT"];
		// var_dump($value);


		// 沒有資料的時候, 註冊這個使用者資訊
		if($checkuser_result['count'] == 0){
			// 會員 or 代理商註冊在 redis db 內
			$rrset = runRedisSET($sid, $value);
			// var_dump($rrset);die();


		}elseif($checkuser_result['count'] == 1 AND $checkuser_result['key'] != $sid){
			// 使用者存在, 和目前的 session id 不同, 後面踢前面
			$logger = '有重複的使用者 1 人，重複的使者 KEY '.$checkuser_result['key'].' SID = '.$sid."  phpredis sid = $phpredis_sid";
			// 刪除 redisdb db0 php 的 session sid
			//$r[0] = runRedisDEL($phpredis_sid,0);
			// 刪除 redisdb DB $redisdb['db'] 的 session id (自行註冊)
			// $r[1] = runRedisDEL($sid, $redisdb['db']);

			// 只留下一個當下的 browser sesion , 然後登出. 強制重來
			$keepone = Member_runRedisKeepOneUser();
			//var_dump($keepone);
			//var_dump($r);
			//var_dump($logger);

			// 會員 or 代理商註冊在 redis db 內
			$rrset = runRedisSET($sid, $value);

			//echo '<script>alert("'.$logger.'");";</script>';
			//echo $logger;
			// die($logger);

		}elseif(isset($checkuser_result['count']) AND $checkuser_result['count'] >= 2) {
			$logger = '有重複的使用者'.$checkuser_result['count'].'人以上登入，全體退出系統後重新登入。';

			// 只留下一個當下的 browser sesion , 然後登出. 強制重來
			$keepone = Member_runRedisKeepOneUser();
			// var_dump($keepone);
			// 刪除 redisdb sid
			$r[0] = runRedisDEL($phpredis_sid,0);
			$r[1] = runRedisDEL($sid, $db);
			//var_dump($r);
			//var_dump($logger);
			echo '<script>alert("'.$logger.'");document.location.href="login_action.php?a=logout";</script>';
			echo '<a href="login_action.php?a=logout">logout</a>';
			die($logger);
		}


	}else{
		return(0);
	}
	return(1);
}


// --------------------------------------
// 刪除除了當下的 session 以外，所有同使用者的 key for 前台
// 已確保所有的 session 只有一個登入者, 只有在登入的狀況下才可以使用生效
// 參考文件: https://github.com/phpredis/phpredis#connect-open
// user: MemberrunRedisKeepOneUser()
// --------------------------------------
function Member_runRedisKeepOneUser() {

	global $config;
	global $redisdb;
	if(isset($redisdb['db'])) {
		$db = $redisdb['db'];
	}else{
		die('No select RedisDB');
	}

	$redis = new Redis();
	// 2 秒 timeout
	if($redis->pconnect($redisdb['host'], 6379, 1)) {
		// success
		if($redis->auth($redisdb['auth'])) {
			// echo 'Authentication Success';
		}else{
			return(0);
			die('Authentication failed');
		}
	}else{
		// error
		return(0);
		die('Connection Failed');
	}
	// 選擇 DB , Member 使用者自訂的 session 放在 $db
	$redis->select($db);
	// echo "Server is running: ".$redis->ping();


	// 找出已經登入的使用者 key from db 2
	if(isset($_SESSION['member'])) {
		// 使用者帳號 sha1 後就是 session id
		$value = $config['projectid'].'_front_'.$_SESSION['member']->account;
		$account_sid = sha1($value);
		// var_dump($account_sid);

		// 搜尋已經登入的 users key
		$userkey = $account_sid.'*';
		$alive_userkeys = $redis->keys("$userkey");
		// var_dump($alive_userkeys);

		// 確認目前當下 session 的 key
		$current_sessionid = $account_sid.':'.session_id();
		// var_dump($current_sessionid);

		// 轉換為要刪除的 php session id  , 保留這個 session 本身的 session id 不刪除
		$alive_userkeys_count = count($alive_userkeys);
		$phpsession_userkeys = [];
		$delalivekey		= NULL;
		$kk = 0;
		for($k=0;$k<$alive_userkeys_count;$k++) {
			if($current_sessionid != $alive_userkeys[$k] ) {
				$phpsession_userkeys[$kk] = str_replace($account_sid,'PHPREDIS_SESSION',$alive_userkeys[$k]);
				// 刪除所有位於 db 1 的除了自己以外的 key,
				// echo 'alive key deleted'.$alive_userkeys[$k];
				$delalivekey[$kk] = $redis->delete($alive_userkeys[$k]);
				$kk++;
			}
		}
		//var_dump($delalivekey);


		// 切換到 db 0 ，刪除除了自己以外的 phpsession 的 key
		$delsessionkey = NULL;
		//var_dump($phpsession_userkeys);
		// 系統 phpsession 放在 db 0
		$redis->select(0);
		$phpsession_userkeys_count = count($phpsession_userkeys);
		for($d=0;$d<$phpsession_userkeys_count;$d++) {
			//echo 'phpsession key deleted'.$phpsession_userkeys[$d];
			$delsessionkey[$d] = $redis->delete($phpsession_userkeys[$d]);

		}
		//var_dump($delsessionkey);
		$r = true;
	}else{
		// echo '你沒有登入系統.';
		// echo '<script>location.reload("true");</script>';
		$r = false;
		return(0);
	}

	return($r);
}



// --------------------------------------
// 檢查系統中的使用者數量，是否有其他的登入者. 只留下當下 browser 這一個
// 參考文件: https://github.com/phpredis/phpredis#connect-open
// user: Member_CheckLoginUser($check_account)
// --------------------------------------
function Member_CheckLoginUser($check_account) {

  //$check_agentaccount = 'root';
	global $redisdb;
	if(isset($redisdb['db'])) {
		$db = $redisdb['db'];
	}else{
		die('No select RedisDB');
	}

	$redis = new Redis();
	// 2 秒 timeout
	if($redis->pconnect($redisdb['host'], 6379, 1)) {
		// success
		if($redis->auth($redisdb['auth'])) {
			// echo 'Authentication Success';
		}else{
			return(0);
			die('Redisdb authentication failed');
		}
	}else{
		// error
		return(0);
		die('Redisdb Connection Failed');
	}
	// 選擇 DB , member 使用者自訂的 session 放在 db 2 ($redisdb['db'] 替代)
	$redis->select($db);
	// echo "Server is running: ".$redis->ping();
  // -----------------------------------------------

	// 找出已經登入的使用者 key  from db 1
	// 使用者帳號 sha1 後就是 session id
	$account_sid = sha1($check_account);
	// var_dump($account_sid);

	// 搜尋已經登入的 users key
	$userkey = $account_sid.'*';
	$alive_userkeys = $redis->keys("$userkey");
  // 同一個使用者，只能有一個登入.沒有登入使用者的時候，應該是 false
  // var_dump($alive_userkeys);
	$alive_userkeys_count = count($alive_userkeys);
	// var_dump($alive_userkeys_count);
  if($alive_userkeys_count == 1) {
		// 剛好有一個使用者
		$keyvalue = [];
    $keyvalue['key'] = $alive_userkeys[0];
    $keyvalue['value'] = $redis->get($keyvalue['key']);
		$keyvalue['count'] = $alive_userkeys_count;
    $r = $keyvalue;
  }elseif($alive_userkeys_count == 0){
		// 沒有使用者
		$keyvalue = [];
    $keyvalue['count'] = $alive_userkeys_count;
		$r = $keyvalue;
  }else{
		// 超過一個以上的使用者
		$keyvalue = [];
		$keyvalue['key'] = $alive_userkeys[0];
		$keyvalue['value'] = $redis->get($keyvalue['key']);
		$keyvalue['count'] = $alive_userkeys_count;
		$r = $keyvalue;
		// var_dump($r);
		// 三小，系統應該有漏洞，程式有問題，快點呼叫工程師。
		//die('System redisdb session error, please call service.');
	}
	// var_dump($r);die();
	return($r);
}


// ----------------------------------------------------------------------------
// 這個區段為 session 的檢查及註冊，主要是用來判斷前台是否有重複的登入帳號。 END
// ----------------------------------------------------------------------------

// ----------------------------------------------------------.
/*
// use sample
$salt = '11223344';
// 需要傳遞的陣列
$codevalue_array = array(
  'Amt' 			=> '111',
  'MerchantOrderNo' => 'ertgyhujioiuytre'
);
// 產生
$send_code = jwtenc($salt,$codevalue_array);
var_dump($send_code);
// 解碼
$codevalue = jwtdec($salt,$send_code);
var_dump($codevalue);
*/
// ----------------------------------------------------------.
// jwtenc 傳送需要被回傳的資料, 包含驗證碼
// $salt 加密的密碼
// $codevalue_array 傳送的資料陣列
// ----------------------------------------------------------.
function jwtenc($salt, $codevalue_array, $debug =0) {
  // 將變數排序陣列
  $check_codevalue = ksort($codevalue_array);

  // 將變數使用 json + base64 encode
  $base64_codevalue =base64_encode(json_encode($codevalue_array));

  // 用 sha1 加密 , 產生檢核碼
  $checkvalue = sha1($salt . sha1($salt . $base64_codevalue));

  // 兩個碼合在一起當成變數傳遞
  $send_code = $checkvalue.'_'.$base64_codevalue;

  if($debug  == 1) {
    var_dump($check_codevalue);
    var_dump($base64_codevalue);
    var_dump($checkvalue);
    var_dump($send_code);
  }
  return($send_code);
}
// ----------------------------------------------------------.

// ----------------------------------------------------------.
// jwtdec 解開並驗證傳回的資料是否正確, 不正確為 false
// $salt 加密的密碼
// $send_code 接收到的 jwt data
// ----------------------------------------------------------.
function jwtdec($salt, $send_code, $debug =0) {
  // 將傳來的 code 拆開
  $send_code_value = explode('_', $send_code);

  // return
  $checkvalue = sha1($salt . sha1($salt . $send_code_value[1]));

  // 判斷資料是否有被竄改
  if($checkvalue == $send_code_value[0]){
    $codevalue =json_decode(base64_decode($send_code_value[1]));

  }else{
    // 資料被串改 false return
    $codevalue = false;
  }

  if($debug  == 1) {
    var_dump($send_code_value);
    var_dump($checkvalue);
    var_dump($codevalue);
  }
  return($codevalue);
}
// ----------------------------------------------------------.


// -------------------------------------------------------------------------
// 不合法登入者的顯示訊息, 轉移到登入後在轉移回來的函式.
// $get_enable = 1 登入後 回來的網址. 加上 GET 參數
// $debug = 1 不自動轉址,除錯專用
// -------------------------------------------------------------------------
function login2return_url($get_enable = 1, $debug=0) {
	global $tr;

	if($get_enable == 1) {
		// 回來的網址. 加上 GET 參數
		$returnurl = $_SERVER['REQUEST_URI'];

	}elseif($get_enable == 0) {
		// 回來的網址, 不加 GET 參數
		$returnurl = $_SERVER['DOCUMENT_URI'];

	}else{
		// 預設為 home.php
		$returnurl = 'home.php';
	}

	// 登入系統後,回到這個頁面. 如果需要加上 POSt 變數, 請放在這個陣列內.
	$value_array = array(
		'formtype'              => 'POST',
		'formurl' 			        => $returnurl
	);
	// 產生 token , salt是檢核密碼預設值為123456 ,需要配合 jwtdec 的解碼, 此範例設定為 123456
	$send_code = jwtenc('123456', $value_array);
	//var_dump($send_code);
	$token = $send_code;
	$returnurl_html = '<script>document.location.href="login2page.php?t='.$token.'";</script>';
	if($debug == 1) {
		$returnurl_html = '<a href="login2page.php?t='.$token.'">'.$tr['login first'].'</a>';
	}

	return($returnurl_html);
}
// -------------------------------------------------------------------------


// -------------------------------------------------------------------------
// 不合法登入者的顯示訊息, 轉移到登入後在轉移回來的函式.
// $get_enable = 1 登入後 回來的網址. 加上 GET 參數
// $debug = 1 不自動轉址,除錯專用
// -------------------------------------------------------------------------
function clientdevice_detect($isIndex = 0, $debug=0) {
	global $config;

	// 帶入相依lib
	require_once dirname(__FILE__) ."/in/mobiledetect/Mobile_Detect.php";
	// 偵測瀏覽器 , 分成 phone , tablet 及 computer , 三個變數放在 session 內提供判斷。
	$detect = new Mobile_Detect;
	// 判断装置类型, 转跳到对应的位置
	$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443 || getenv('HTTP_X_FORWARDED_PROTO') === 'https') ? "https://" : "http://";

	if(isset($_SERVER['QUERY_STRING']) AND $_SERVER['QUERY_STRING'] != '' ){
		$currency_uri = $_SERVER['DOCUMENT_URI'].'?'.$_SERVER['QUERY_STRING'];
	}else{
		$currency_uri = $_SERVER['DOCUMENT_URI'];
	}


	if ( $detect->isMobile() ) {
		$return['url'] = ($isIndex == 1) ? $protocol.$config['mobile_url'] : $protocol.$config['mobile_url'].$currency_uri;
		$return['html'] = ($_SERVER["HTTP_HOST"] == explode('/',$config['mobile_url'])[0]) ? '' : '<script>window.location.href = "'.$return['url'].'";</script>';
	}else{
		$return['url'] = ($isIndex == 1) ? $protocol.$config['desktop_url'] : $protocol.$config['desktop_url'].$currency_uri;
		$return['html'] = ($_SERVER["HTTP_HOST"] == explode('/',$config['desktop_url'])[0]) ? '' : '<script>window.location.href = "'.$return['url'].'";</script>';
	}

	if($debug==1){
		echo 'isMobile : '.$detect->isMobile()."\n";
		var_dump($return);
	}

	return($return);
}
// -------------------------------------------------------------------------



?>
