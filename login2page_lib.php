<?php
// ----------------------------------------------------------------------------
// Features:	針對 member 登入檢查的處理, 預設給 member 資料相關的程式使用。
// File Name:	login2page_action.php
// Author:		Barkley
// Related:   每一個有登入、登出的頁面，都會用到這個
// Log:
// ----------------------------------------------------------------------------

// ==============================================================================================
// 用途：搜尋帳號是否在2個月內曾經有"現金"交易紀錄
// 對應後台會員端設定開關"現金帳戶未使用自動隱藏"
// 會判斷 該會員無現金交易紀錄則看不到所有"現金"相關資訊與功能
// $_SESSION['member']->gcash_log_exist
/* 參數說明：$gcash_log_exist = json_encode(
  ['gcash_log_exist' => true(是否存在60日內現金交易紀錄T/F),
   'last_log_date' => $current_date_time,(最後現金交易時間)
    'check_date' => $current_date_time(最後更新此資訊時間(前台若超過60日會檢查一次此資料正確性))
    ]);
*/
//==============================================================================================
function check_gcash_log_exist($session_gcash_log_exist) {
    $current_date_time = gmdate('Y-m-d H:i', time()+-4 * 3600);

    if (!is_null($session_gcash_log_exist)) {
      //若session已有現金提記錄 則確認更新時間(check_date)是否過舊
      $session_gcash_log_exist = json_decode($session_gcash_log_exist,true);
      $minus_days=(strtotime($session_gcash_log_exist['check_date']) - strtotime($current_date_time))/ (60*60*24);

      if($minus_days>60){
        //check_date超過60天 更新資料確保資料正確性
        login_update_gcash_log_exist();
      }
    }else{
      login_update_gcash_log_exist();
    }
    return json_decode($_SESSION['member']->gcash_log_exist)->gcash_log_exist;
}

//function set_hide_gcash_mode($session_gcash_log_exist)用
//撈現金記錄 並更新member的gcash_log_exist欄位以及更新session
function login_update_gcash_log_exist(){
    $account = $_SESSION['member']->account;
    $current_date      = gmdate('Y-m-d', time()+-4 * 3600);
    $current_date_time = gmdate('Y-m-d H:i', time()+-4 * 3600);
    $lastmonth_s       = date("Y-m", strtotime("$current_date - 1 month")) . '-01 00:00';
    $today_e           = $current_date_time;

    //sql搜尋現金交易紀錄 最新的一筆 並把時間儲存
    $query =<<<SQL
    SELECT to_char((transaction_time AT TIME ZONE 'AST'),'YYYY-MM-DD HH24:MI:SS') as trans_time,
            id as trans_id,
            transaction_id,
            transaction_category,
            deposit,
            withdrawal,
            balance,
            source_transferaccount,
            summary as summary,
            deposit-withdrawal AS transaction_amount
    FROM root_member_gcashpassbook as psbk
    WHERE source_transferaccount != 'gcashcashier'  AND source_transferaccount ='{$account}' AND transaction_time >='{$lastmonth_s} -4' AND transaction_time <='{$today_e} -4' ORDER BY trans_time DESC LIMIT 1
SQL;

  $datatableInitData = runSQLall($query);
  //var_dump($query);
  //gcash_log_exist 60日內(有/無)現金交易 last_log_date最後一筆交易日期、check_date此筆資訊更新時間
  if (empty($datatableInitData[0])) {
      $gcash_log_exist = json_encode(['gcash_log_exist' => false, 'last_log_date' => '', 'check_date' => $current_date_time]);
  }else{
      $gcash_log_exist = json_encode(['gcash_log_exist' => true, 'last_log_date' => $datatableInitData[1]->trans_time, 'check_date' => $current_date_time]);
  }

  //把新的資訊寫入SQL 更新新的交易紀錄時間
  $update_query=<<<SQL
UPDATE "root_member" SET
"gcash_log_exist" = '{$gcash_log_exist}'
WHERE "account" = '{$account}';
SQL;
  $update_sql_res = runSQLall($update_query);

  //更新session
  $_SESSION['member']->gcash_log_exist = $gcash_log_exist;
}

// ==============================================================================================
// usage: $user_balance = get_member_wallets($userid);
// code and message , code=1 表示正確
// related:
// 2016.11.23 update 預計取代舊的錢包 , 指提供給 login_action.php 使用. 原有錢包程式，不使用了!!
// ==============================================================================================
function get_member_wallets($userid) {
  global $tr;
	// 抓 $user->id 餘額
	// $user_balance_sql = "SELECT * FROM root_member_wallets WHERE id = $userid;";
	$user_balance_sql = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.id = $userid;";
	//var_dump($user_balance_sql);
	$user_balance_result = runSQLall($user_balance_sql);
	//var_dump($user_balance_result);

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
		$r['code'] = '1';
		$r['messages'] = $tr['Update balance and member account'];//'更新余额及会员帐号'
	}else{
		// 沒有資料，建立初始資料。利用callback函數，做錯誤處理
		$member_wallets_addaccount_sql = "INSERT INTO root_member_wallets (id, changetime, gcash_balance, gtoken_balance) VALUES ('".$userid."', 'now()', '0', '0');";
		// var_dump($member_wallets_addaccount_sql);
		$rwallets = runSQL($member_wallets_addaccount_sql, $debug="0", $sqlact='w',
        function($sql) use($userid) {
          global $tr;
          $logger = "$userid ".',Create root_member_wallets account false!! ';
          // echo $logger;
          // memberlog 2db($_SESSION['member']->account,'member wallet','error', "$logger");
          $msg=$tr['Create a user wallet account failed'];//'建立使用者钱包帐户，失敗。'
          $msg_log = "$sql";
          $sub_service='wallet';
          memberlogtodb($_SESSION['member']->account,'member','error',"$msg",$_SESSION['member']->account,"$msg_log",'f',$sub_service);

      }
    );
		if($rwallets == 1){
			// $logger = "$userid ".',Create root_member_wallets account success!! ';
			//echo $logger;
			// memberlog 2db($_SESSION['member']->account,'member wallet','info', "$logger");
      $msg=$tr['Create a user wallet account success'];//'建立使用者钱包帐户，成功。'
      $msg_log = $userid .',Create root_member_wallets account success!!';
      $sub_service='wallet';
      memberlogtodb($_SESSION['member']->account,'member','info',"$msg",$_SESSION['member']->account,"$msg_log",'f',$sub_service);

			$r['code'] = '1';
      // $r['messages'] = $logger;
			$r['messages'] = $msg;
			// 再取出一次
			// $user_balance_sql = "SELECT * FROM root_member_wallets WHERE id = $userid;";
			$user_balance_sql = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.id = $userid;";
			$user_balance_result = runSQLALL($user_balance_sql);
			$_SESSION['member'] = $user_balance_result[1];
		}
	}
	// 回傳訊息， error=0 is correct, error > 0 is another
	// $r['error'] = '0';
	// $r['errormessages'] = '';
	// $r['wallets']
	return($r);
}
// 回傳整各錢包的狀況
// ==============================================================================================

// ------------------------------------------
// 2019.6.4 edit
// token 導回指定url - lib_menu.php
function get_token(){
  // 需要傳遞的陣列
  // formtype --> [POST|GET]   轉址傳遞變數的方式(必要)
  // formurl  --> 自訂轉址指定的網址(必要)
  // 其他變數(自訂)
  $value_array = array(
    'formtype'              => 'POST', // 'GET'
    'formurl' 			        => './home.php'
  );
  // 產生 token , salt預設值為123456
  $send_code = jwtenc('123456', $value_array);
  $token = $send_code;
  // var_dump($token);die();
  return($token);
}
// ---------------------------------------------


// ==============================================================================================
// usage: $user_balance = get_member_wallets($userid);
// code and message , code=1 表示正確
// related:
// ==============================================================================================
// 接收需求的 token , 包含檢查碼及data , 依據 data 指定前往指定的 url ,並且傳送 data
// 傳入 token , 產出需要的 html code
function login2page($token, $debug=1) {
  $codevalue = jwtdec('123456', $token);
  if($debug == 1) {
		echo "傳入的POST";
    var_dump($_POST);
		echo "傳入的JWT TOKEN";
    var_dump($token);
		echo "傳入的 code ";
    var_dump($codevalue);
		echo "目前的 SESSION 狀態";
    var_dump($_SESSION);
  }

  // 確認 check value 資料正確才執行
  if($codevalue != false) {
    // 判斷是 GET or POST 的轉條
    if($codevalue->formtype == 'GET') {
      $url_query  = http_build_query($codevalue);
      // 網址加上參數
      $url = $codevalue->formurl.'?'.$url_query;

      if($debug ==1) {
        $url_html = '除錯狀態：自動跳轉到指定的網址<a href="'.$url.'" title="前往指定網址">'.$url.'</a>';
      }else{
        // 立即前往
        $url_html = '<script language="javascript">document.location.href="'.$url.'";</script>';
      }
      $return_html = $url_html;

    }elseif($codevalue->formtype == 'POST') {

      $form_input = '';
      foreach ($codevalue as $key => $value) {
        //echo $key.' = '.$value.',';
        if($debug == 1) {
          $form_input = $form_input.$key.'<input type="text" name="'.$key.'" value="'.$value.'"><br>';
        }else{
          $form_input = $form_input.'<input type="hidden" name="'.$key.'" value="'.$value.'">';
        }
      }

  		$output_form_html = '
  	  <form name="goto_url" action="'.$codevalue->formurl.'" method="POST">
  	   '.$form_input.'
  	  <input class="d-none" type="submit" value="Submit">
  	  ';

      if($debug == 0) {
        // 自動觸發表單
    		$output_form_html = $output_form_html.'
        <script type="text/javascript">
          document.forms["goto_url"].submit();
        </script>
        ';
      };
      $return_html = $output_form_html;

    }else{
      $return_html = 'type error!!';
    }

  }else{
    $return_html = 'data check value error!!';
  }

  return($return_html);
}
// 解碼 , jwtdec 和 jwtenc 是一對, 放在 lib.php 內
// ==============================================================================================



// -----------------------------------
/*
功能: 會員登入檢查的函示 , 需要 lib.php 的函示輔助
使用: login_check($account, $password, $login_force, $debug=0)
必要變數:
$account
$password
$login_force
全域變數:
$system_config
$_POST
$_SESSION
*/
// -----------------------------------
function login_check($account, $password, $login_force, $debug=0) {
  global $config;
  global $system_config;
  global $redisdb;
  global $tr;
  global $protalsetting;

  // step 0 檢查資料及驗證碼正確性
  // -----------------------------------
  $i=0;
  $error = [];
  $error['success'] = true;
  $error['messages'] = '';
  // 2fa
  $error['2fa_check'] = false;
  // 重複登入
  $error['login_warning'] = false;

  $logger = '';
  // 給測試用的預設驗證碼變數
  $captcha_for_test = ($system_config['captcha_for_test'] != '') ? $system_config['captcha_for_test'] : NULL;

  if($debug ==1) {
    var_dump($_POST);
    var_dump($_SESSION);
    var_dump($account);
    var_dump($password);
    var_dump($login_force);
  }

  // step 1 檢查是否已經登入了, 設為正確離開.
  if(isset($_SESSION['member']) AND ($_SESSION['member']->therole == 'M' OR $_SESSION['member']->therole == 'A')) {
    $logger = $_SESSION['member']->account.' '.$tr['user has logged'];//使用者已經登入系統了
    $error['success'] = true;
    $error['messages'] = $error['messages'].$logger;
    return($error);
  }

  // step 2 如果驗證碼正確的話 , 且 captch 存在 session 內. 增加一組給 test unit 用的驗證碼 $system_config['captcha_for_test'] ，可以跳過驗證程序。
  // -----------------------------------
  if(isset($_SESSION['captcha']) AND ( $_SESSION['captcha'] == $_POST['captcha']  OR ($captcha_for_test != '' AND $captcha_for_test == $_POST['captcha']) )) {
    // 驗證碼正確,取消原本的 captcha 變數 session
    unset($_SESSION['captcha']);
  }else{
      //$logger = $_POST['captcha'].' Authentication code is incorrect.';
			$logger = $tr['captcha error'];//'验证码错误，请重新输入。'
      $error['success'] = false;
      $error['messages'] = $error['messages'].$logger;
  }

	// 帳號格式 check
	$account          = strtolower(filter_var($account, FILTER_SANITIZE_STRING));
	$account_orig_len = strlen($account);
	$accunt_regexp 	  = '/^[a-z][a-z0-9]{2,12}$/';
	$accunt_regexp_check = filter_var($account, FILTER_VALIDATE_REGEXP,array("options"=>array("regexp"=>"$accunt_regexp")));
	// var_dump($accunt_regexp_check);
	// var_dump($account_orig_len);
	// 長度需要 3-12 各字元
	if($accunt_regexp_check == false OR ($account_orig_len < 3 OR $account_orig_len > 12)) {
		// 帳號格式錯誤
    $msg=$tr['account format error'];//'帐号格式错误，或長度需介于3~12码之间。'
    $msg_log = 'account: '.$account.' error.';
    $sub_service='login';
    memberlogtodb('guest','member','error',"$msg","$account","$msg_log",'f',$sub_service);
		// memberlog 2db('guest','login','error', "$logger");
		// $logger = 'Account input format error.';
    $logger = $tr['Incorrect account or pwd entry'];//'帐号或密码输入有误'
    $error['success'] = false;
    $error['messages'] = $error['messages'].$logger;
		// die($logger);
	}


  // step 4 密碼組合及長度(密碼有加密過，不需檢查)
  // -----------------------------------
	// 檢查密碼格式，需要為  sha1
	$password        = filter_var($password, FILTER_SANITIZE_STRING);
	$password_regexp      = '/^[a-fA-F0-9]{40}/';
	$password_regexp_check = filter_var($password, FILTER_VALIDATE_REGEXP,array("options"=>array("regexp"=>"$password_regexp")));
	// var_dump($accunt_regexp_check);
	if($password_regexp_check == false) {

  // 密碼格式錯誤
  $msg=$account.':'.$tr['wrong password'];//密码错误!
  $msg_log = 'login2page_lib.php:261';
  $sub_service='login';
  // memberlog 2db('guest','login','error', "$logger");
  memberlogtodb("$account",'member','error',"$msg",'unknow',"$msg_log",'f',$sub_service);
  // $logger = 'password format is incorrect';
  $logger = $tr['wrong password'];//'密码输入错误'
  die($logger);
	}

	// 如果有 fingerprint 的話,紀錄
	if(isset($_SESSION['fingertracker'])){
		$fingertracker = $_SESSION['fingertracker'];
	}else{
		$fingertracker = 'NOFINGER';
	}

	// 前台 + 專案站台 + 帳號 , 寫入 redisdb 識別是否單一登入使用
	$value = $config['projectid'].'_front_'.$account;
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

  // --------------------------

  // 前面的步驟成功才繼續, 否則就離開
  if($error['success'] == true) {
    // step 5 檢查 SQL 是否有資料, 及是否有重複的使用者在系統內
    // -----------------------------------
    $sql = "select * from root_member where account = '".$account."' and passwd = '".$password."' and (therole = 'A' OR therole = 'M');";
    $r = runSQLall($sql);

    // ----------
    // user ip
    $user_current_ip = explode(',',$_SERVER['HTTP_X_FORWARDED_FOR'])['0'];
    // 登入錯誤，是否真有該帳號(只檢查帳號)
    $check_member = check_member_exists($account);
    // db有此帳號，且密碼為空
    $check_import_account = check_import_account($account);
    // 是否有該IP
    $check_ip = IP_data($user_current_ip);
    // -----------

    // member 認證正確
    if($r[0] == 1 and $error['success'] == true) {

      // 帳號審核中
      if($r[1]->status == '4'){
        $error['success'] = false;
        $error['login_warning'] = true;
        $error['messages'] = $tr['registration under review'];

        $msg = $error['messages'];
        $msg_log = $logger;
        $sub_service = 'logout';
        memberlogtodb('guest','member','error',"$msg",$account,"$msg_log",'f',$sub_service);
        return $error;
        die();
      }
      
      //檢查 會員等級是否被關閉
      $grade = $r[1]->grade;
      $check_grade_sql = "SELECT * FROM root_member_grade WHERE status = 1 AND id = '$grade';";
      $check_grade_result = runSQLall($check_grade_sql);
      if($check_grade_result[0] == 0){
        $logger = $tr['Fail to inquire member grade'];
        $error['success'] = false;
        $error['messages'] = $error['messages'].$logger;
        return $error;
        die();
      }
      
      // -----------------------
      // 2fa檢查
      $factor_result = sql_member_authentication($r[1]->id);
      if($factor_result[0] >= 1 AND  $factor_result[1]->two_fa_status == '1'){
        $error['2fa_check'] = true;
        return $error;
        die();
      }
      // -------------------------

      // ---- 2019-7-25-----
      // ip只要被封鎖，換帳號也無法登入
      // 帳戶和IP都沒被封鎖
      if($r[1]->status != 3 AND $check_ip[1]->status != 0){
        // 登入成功，清除redis登入錯誤資料
        $clear_error_attempt_record = clear_record($account,$user_current_ip);
        // 把該帳號的IP錯誤次數紀錄歸0
        $clear_error_count = update_ip_db('0','1',$user_current_ip);
      }
      // --------------------------

  		// member login session record in redisdb 2
  		// 檢查系統中使用者是否已經存在，如果已經存在的話就不允許登入。或是讓使用者選擇刪除這個
  		$checkuser_result = Member_CheckLoginUser($value);

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
				if($expiretime_chk >= 0){
				// if($expiretime_chk <= 60){
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
            //var_dump($logger);

            // --------------------------
            // 2019.6.14
            // 重複登入資訊寫進memberlogtodb
            $error['login_warning'] = true;
            $error['messages'] = $alert_message_html;

            $msg = $error['messages'];
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
        $error['success'] = false;
        $error['messages'] = $error['messages'].$logger;
  		}
  		// 檢查系統同帳號，是否已經有人在別的地方登入。 end

			// 帳號狀態, 0=會員停權disable 1= 會員啟用enable 2=會員錢包凍結
      // 帳號密碼正確, 但是帳戶已經被鎖定
      if($r[1]->status == 0 ) {
        $logger = $tr['Account has been locked'];//'你的帐号已经被锁定，联络客服人员处理。'
				// $logger = 'Your account has been locked, please contact customer service.';
        $error['success'] = false;
        $error['messages'] = $error['messages'].$logger;
      }

      if($r[1]->status == 2 ) {
        // $logger = $tr['Account is frozen'];//'你的帐号暫時被凍結，请联络客服人员处理。'
        $logger = $tr['Your wallet has been frozen, please contact customer'];
        $error['success'] = true;
        $error['messages'] = $error['messages'].$logger;
      }

      //  --------------------------------
      // 209-7-24
      // 帳戶被封鎖(status = 3)，計算封鎖時間
      $get_member_data = check_member_exists($account);
      if($get_member_data[1]->lastlogin == NULL AND $r[1]->status == 3){
        // 未登入過且在後台該帳號狀態已被改成暫時封鎖
        $logger = '此帐号无法登入，请洽客服。';
        $error['success'] = false;
        $error['messages'] = $error['messages'].$logger;

      }elseif($get_member_data[1]->lastlogin != NULL AND $r[1]->status == 3){
        // 計算時間
        $count_due_time = calcu_time($get_member_data[1]->lastlogin);  // 封鎖時間
        if($r[1]->status == 3 AND $count_due_time['counting_time'] >= $protalsetting['account_lock_time']) {
          // 超過15分鐘，解除封鎖
          check_lock_account($account,$count_due_time['current'],'1');
          // ip db 錯誤次數歸0
          update_ip_db('0','1',$user_current_ip);
          // 清除redis db record
          clear_record($account,$user_current_ip);

        }elseif($r[1]->status == 3){
          $logger = '此帐号登入次数过多，请稍后再试。';
          $error['success'] = false;
          $error['messages'] = $error['messages'].$logger;
        }
      }
      //----------------------------------------

      // 註冊送彩金有開啟才動作
      if(isset($r[1]->permission)){
        $registered_offer_date = json_decode($r[1]->permission,true);
        if ($registered_offer_date['registered_offer'] == 'on') {
          $offer_data = $registered_offer_date['offer_data'];
          // 註冊送彩金
          $gtoken_transfer_error = promotion_register_sendbouns($account, $offer_data);
        }
      }
    }elseif($check_member[0] == 1 AND $r[0] == 0 AND $check_import_account[0] == 0){
      // 登入錯誤，後台登入錯誤紀錄，帳號、IP封鎖設定
      // 匯入帳號= (帳號正確，密碼錯誤或密碼=空)

      $attempt = login_attempt($account,$user_current_ip);

      // 會員狀態
      $get_member_data = check_member_exists($account);
      // 計算封鎖時間是否已結束
      $count_due_time = calcu_time($get_member_data[1]->lastlogin);

      // 超過設定的封鎖時間，解除封鎖帳號,且帳號狀態=3
      if($count_due_time['counting_time'] >= $protalsetting['account_lock_time'] AND $get_member_data[1]->status == 3 AND $attempt['status'] == false){

        // 解除帳號封鎖
        check_lock_account($account,$count_due_time['current'],'1');
        // ip db 錯誤次數歸0
        update_ip_db('1','1',$user_current_ip);
        // 清除redis db record
        clear_record($account,$user_current_ip);

        $logger = '此帐号已解除封锁。';
        $error['success'] = false;
        $error['messages'] = $error['messages'].$tr['The account number, password is incorrect, or the user does not exist.'];

      }elseif($count_due_time['counting_time'] <= $protalsetting['account_lock_time'] AND $get_member_data[1]->status == 3 AND $attempt['status'] == false){
        // 還沒解鎖，但還是繼續try
        $logger = '此帐号登入次数过多，请稍后再试。';
        $error['success'] = false;
        $error['messages'] =  $error['messages'].$logger;

      }else{
        $logger = $tr['The account number, password is incorrect, or the user does not exist.'];
        $error['success'] = false;
        $error['messages'] =  $error['messages'].$logger;
      }

    }else{
      //$logger = $account.'帐号或密码错误, 或是使用者不存在.';
      $logger = $tr['The account number, password is incorrect, or the user does not exist.'];//'帐号、密码错误，或是使用者不存在。'
      $error['success'] = false;
      $error['messages'] = $error['messages'].$logger;
    }

  }else{
    // 前面就有錯誤了, 可以先離開. 到這裡中斷.
    return($error);

  }


  // 此為 user 登入成功的處理
  if($error['success'] == true and $r[0] == 1) {

   // 將使用者資訊存到 session
   $_SESSION['member'] = $r[1];
   //檢查是否開啟現金帳戶隱藏模式
   //$_SESSION['hide_gcash_mode'] = set_hide_gcash_mode();
   //var_dump($_SESSION['member']->gcash_log_exist);
   //var_dump(check_gcash_log_exist($_SESSION['member']->gcash_log_exist));
    check_gcash_log_exist($_SESSION['member']->gcash_log_exist);
    // 檢查是否建立了錢包資料，如果有資料的話，把錢包帶進來。沒有的話建立錢包。
   $rcode = get_member_wallets($r[1]->id);

   if($rcode['code'] == '1') {
     // 同時更新了 $_SESSION['member'] 變數

		 // 將這個會員 or 代理商註冊在 redis db 內, 避免重複登入的問題
		 // 寫入 redis server 的資訊
		 // 從哪裡點擊來的 , 後台呈現資料使用
     if (!empty($_SERVER['HTTP_REFERER'])) { $http_referer = $_SERVER['HTTP_REFERER']; }else{ $http_referer = "No HTTP_REFERER";	}
     // 原版
    //  $value = $value.','.time().','.$_SERVER["SCRIPT_NAME"].','.$_SERVER["REMOTE_ADDR"].','.$fingertracker.','.$http_referer.','.$_SERVER["HTTP_USER_AGENT"];
     $value = $value.','.time().','.$_SERVER["SCRIPT_NAME"].','.explode(',',$_SERVER['HTTP_X_FORWARDED_FOR'])['0'].','.$fingertracker.','.$http_referer.','.$_SERVER["HTTP_USER_AGENT"];

		 $rrset = runRedisSET($sid, $value);

     // 取得使用者預設的語系
     $_SESSION['lang'] = $r[1]->lang;

     //$logger =  $account.'登入成功';
     // $logger = $account.' sign in suceesfully';
     // $error['messages'] = $error['messages'].$logger;
     // 紀錄這次的登入
     // $logger2db = json_encode($error);
     $msg=$account.$tr['sign in suceesfully'];//'登入成功!'
     $msg_log = 'login2page_lib.php:428';
     $sub_service='login';
     memberlogtodb($account,'member','info',"$msg",$account,"$msg_log",'f',$sub_service);
     // memberlog 2db($account,'login','info', "$logger2db");
		 //echo '登入成功, 會員資料已經載入.';
		 if(is_null ($r[1]->lastlogin) OR empty($r[1]->lastlogin)){
			 	$lastseclogin='';
		 }else{
			 	$lastseclogin="lastseclogin ='".$r[1]->lastlogin."',";
		 }
		$update_logintime="	UPDATE	root_member SET
												".$lastseclogin." lastlogin = NOW()
												WHERE	id ='".$r[1]->id."';";
		// var_dump($update_logintime);die();
		runSQLall($update_logintime);
   }else{
     //$logger = '登入後重新取得钱包资料异常'.$rcode['messages'];
		 $logger = 'Re-obtain the wallet information exception '.$rcode['messages'];
     $error['success'] = false;
     $error['messages'] = $error['messages'].$logger;
   }

  }else{
   // 紀錄這次的登入所有的錯誤
   // $logger2db = json_encode($error);
   $msg = $error['messages'];
   $msg_log = $account.$logger;
   $sub_service='login';
   memberlogtodb('guest','member','error',"$msg","$account","$msg_log",'f',$sub_service);

  }

  return($error);
}
// -----------------------------------
// end of login_check()
// -----------------------------------




// -----------------------------------
// 登出功能 login_logout()
// 目前只有 login_action.php 使用
// -----------------------------------
function login_logout(){
	global $config;
	global $redisdb;
  global $cdnrooturl;
  global $tr;

	// 寫入回傳值
	$return_html = '';

	// Transferout_Casino_MG2_balance() and Retrieve_Casino_MG2_balance()
	// 執行完成後，把 $_SESSION['wallet_transfer'] 變數清除, 其他程式才可以進入。
	// 避免連續性呼叫 Retrieve_Casino_MG2_balance() lib , 需要到 home.php and gamelobby.php 清除變數才可以。
	if(isset($_SESSION['wallet_transfer'])) {
		unset($_SESSION['wallet_transfer']);
	}

	// 登出註銷 redis server record
	if(isset($_SESSION['member'])) {
		// logout 紀錄到 DB , 並刪除所有的 account session , 轉回登出
		// $logger = $_SESSION['member']->account.' Log out of account';
    $msg=$tr['log out suceesfully'];//'登出成功！'
    $msg_log = $_SESSION['member']->account.$tr['log out suceesfully'];//'登出成功！'
    $sub_service='logout';
		// memberlog 2db($_SESSION['member']->account,'logout','info', "$logger");
    memberlogtodb($_SESSION['member']->account,'member','info',"$msg",$_SESSION['member']->account,"$msg_log",'f',$sub_service);
		// del 所有的 same account session
		// session_name().':'.session_id();
		// $value = $_SESSION['member']->account;
		$value = $config['projectid'].'_front_'.$_SESSION['member']->account;
		$sid = sha1($value).':'.session_id();
		// var_dump($sid);
		runRedisDEL($sid, $redisdb['db']);
		$return_html = '<p><img src="'.$cdnrooturl.'loading_ring-alt.gif"></p>';
		$return_html = $return_html.'<script>window.location="'.$config['website_baseurl'].'";</script>';
	}else{
		$return_html = '<p><img src="'.$cdnrooturl.'loading_ring-alt.gif"></p>';
		$return_html = $return_html.'<script>window.location="'.$config['website_baseurl'].'";</script>';
	}

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
	$return_html = $return_html.'<script>window.location="login_action.php?a=logout";</script>';
	}

	return($return_html);
}
// -----------------------------------
// end of 登出功能 login_logout()
// -----------------------------------

/**
 * 註冊送彩金專用 function
 *
 * @param [type] $memberaccount - 會員帳號
 * @param [type] $activity_register_preferential_amount - 贈送金額
 * @param [type] $activity_register_preferential_audited - 稽核金額
 * @return array
 */
function promotion_register_sendbouns($account, $offer_data)
{
  global $transaction_category;

  // 交易的類別分類：應用在所有的錢包相關程式內 , 定義再 system_config.php 內.
  // global $transaction_category;
  // 轉帳摘要 -- 代幣轉現金(tokengcash)
  $transaction_category_index = 'tokenfavorable';
  // 交易類別 , 定義再 system_config.php 內, 不可以隨意變更.
  $summary = $transaction_category[$transaction_category_index];

  $givemoneytime = date("Y-m-d H:i:s", strtotime('now'));
  $receivedeadlinetime = date("Y-m-d H:i:sP", strtotime('+1 month', strtotime($givemoneytime)));

  $member_sql = "SELECT * FROM root_member WHERE account = '".$account."';";
  $m = runSQLALL($member_sql);

  $insert_sql = <<<SQL
  INSERT INTO root_receivemoney (
    member_id, member_account, gtoken_balance, givemoneytime, receivedeadlinetime,
    prizecategories, auditmode, auditmodeamount, summary, transaction_category,
    givemoney_member_account, status, member_ip, member_fingerprinting
  ) VALUES (
    '{$m[1]->id}', '{$account}', '{$offer_data['gift_amount']}', '{$givemoneytime}', '{$receivedeadlinetime}',
    '注册送彩金', 'shippingaudit', '{$offer_data['review_amount']}', '{$summary}', '{$transaction_category_index}',
    'jigcs', '1', '{$m[1]->registerip}', '{$m[1]->registerfingerprinting}'
  );
  UPDATE root_member SET permission = NULL WHERE  account = '{$account}';
SQL;

    return runSQLtransactions($insert_sql);
}
