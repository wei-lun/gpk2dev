<?php
// ----------------------------------------------------------------------------
// Features:	試玩背景 ajax post 動作操作,對應到 trial.php
// File Name:	trial_post_action.php
// Author:		Barkley
// Related:		前端的 trial.php
// Log:
// 不允許直接從網頁操作，需要透過 ajax 呼叫才可以
//
/*
// 在前端的 post jquery ajax 範例
// example:
<script>
	$(document).ready(function() {
		$('#submit_to_register').click(function(){
			var agentaccount_input = $('#agentaccount_input').val();

			if(jQuery.trim(agentaccount_input) == '' || jQuery.trim(memberaccount_input) == '' ){
				alert('請將底下所有 * 欄位資訊填入');
			}else{
				var qq_input = $('#qq_input').val();

				$('#submit_to_register').attr('disabled', 'disabled');
				$.post('post_action.php?a=test',
					{
						agentaccount_input: agentaccount_input,
					},
					function(result){
						$('#submit_to_register_result').html(result);}
				);
			}
		});

	});
</script>
*/
// ----------------------------------------------------------------------------





require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

if(isset($_GET['a'])) {
    $action = filter_var($_GET['a'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
}else{
    die($tr['Illegal test']);//'(x)不合法的測試'
}


// var_dump($_SESSION);
// var_dump($_POST);
// var_dump($_GET);



// ----------------------------------------------------------------------------
// trial_gpk2_memberdeposit_transfer 轉帳函式 -- 前台試玩專用
// ----------------------------------------------------------------------------
//  前台試玩專用 -- 給試玩帳號加錢的函式
function trial_gpk2_memberdeposit_transfer($member_id, $source_transferaccount, $destination_transferaccount, $transaction_money , $summary, $realcash, $auditmode_select, $auditmode_amount, $password_verify_sha1, $system_note_input ) {
    // ----------------------
    // 轉帳邏輯
    // source_transferaccount_input 轉帳給 destination_transferaccount transaction_money(額度)
    // ----------------------
    // 轉帳操作人員，只能是管理員或是會員的上線使用者.
    $d['member_id']                   = $member_id;
    // 娛樂城代號
    $d['casino']                      = 'gpk2';
    // 來源轉帳帳號
    $d['source_transferaccount']      = $source_transferaccount;
    // 目的轉帳帳號
    $d['destination_transferaccount'] = $destination_transferaccount;
    // 轉帳金額，需要依據會員等級限制每日可轉帳總額。
    $d['transaction_money']           = $transaction_money;
    // 摘要資訊
    $d['summary']                     = $summary;
    // 實際存提
    $d['realcash']                    = $realcash;
    // 稽核模式，三種：免稽核、存款稽核、優惠存款稽核
    $d['auditmode_select']            = $auditmode_select;
    // 稽核金額
    $d['auditmode_amount']            = $auditmode_amount;
    // 來源帳號的密碼驗證，驗證後才可以存款
    $d['password_verify_sha1']        = $password_verify_sha1;
    // 系統轉帳文字資訊
    $d['system_note_input']           = $system_note_input;

    $d['system_note_input'] = $d['source_transferaccount'].$d['summary'].'到'.$d['destination_transferaccount'].'金額'.$d['transaction_money'].','.$d['system_note_input'];

    // var_dump($d);
    // 回傳的訊息
    $r['code'] = 0;
    $r['message'] = '';

    // 0. 取得使用者完整的資料
    $destination_transferaccount_sql = "SELECT * FROM gpk.root_member WHERE status = '1' AND account = '".$d['destination_transferaccount']."';";
    // var_dump($destination_transferaccount_sql);
    $destination_transferaccount_result = runSQLALL($destination_transferaccount_sql);
    if($destination_transferaccount_result[0] != 1){
      $logger = '目的端使用者'.$d['destination_transferaccount'].'資料有問題，可能是凍結或被關閉了。';
      // echo $logger;
      $r['code'] = 7;
      $r['message'] = $logger;
      return($r);
    }
    // var_dump($destination_transferaccount_result);
    // 驗證 來源端使用者資料
    $source_transferaccount_sql = "SELECT * FROM gpk.root_member WHERE status = '1' AND account = '".$d['source_transferaccount']."';";
    // var_dump($source_transferaccount_sql);
    $source_transferaccount_result = runSQLALL($source_transferaccount_sql);
    if($source_transferaccount_result[0] != 1){
      $logger = '來源端使用者'.$d['source_transferaccount'].'資料有問題，可能是凍結或被關閉了。';
      //echo $logger;
      $r['code'] = 6;
      $r['message'] = $logger;
      return($r);
    }
    //var_dump($source_transferaccount_result);

    // 驗證來源端使用者的密碼
    // check password to transaction_money
    if($source_transferaccount_result[1]->passwd == $d['password_verify_sha1']) {
      // 密碼對，才工作
      //var_dump($source_transferaccount_result[1]->passwd);
      //var_dump($d['password_verify_sha1']);
    }else{
      // 密碼錯，結束
      $logger = '來源端使用者'.$d['password_verify_sha1'].'驗證的密碼錯誤，結束';
      //echo $logger;
      $r['code'] = 5;
      $r['message'] = $logger;
      return($r);
    }

    // 1. PHP 檢查帳戶 destination_transferaccount 是否存在，不存在建立一個 root_member_wallet 帳號
    $user_alive_sql = "SELECT gpk2_balance FROM gpk.root_member_wallet WHERE gpk2_account = '".$d['destination_transferaccount']."';";
  	$user_alive_result = runSQLALL($user_alive_sql);
    // var_dump($user_alive_result);
    if($user_alive_result == 0) {
      // 不存在，建立 destination_transferaccount root_member_wallet
      $add_member_wallet_sql = "INSERT INTO gpk.root_member_wallet (id, changetime, gpk2_account, gpk2_balance) VALUES ('37', 'now()', 'dora', '0'); ";
      $add_member_wallet_result = runSQLALL($add_member_wallet_sql);
      if($add_member_wallet_result[0] == 1) {
        // 成功建立這個帳號錢包
      }else{
        // 建立帳號錢包失敗，結束
        $logger = '建立帳號錢包失敗，結束';
        //echo $logger;
        $r['code'] = 4;
        $r['message'] = $logger;
        return($r);
      }
      //var_dump($add_member_wallet_result);
    }


    // 2. PHP 檢查帳戶 source_transferaccount 是否有錢,且大於 transaction_money , 成立才工作,否則結束
    $check_source_transferaccount_balance_sql = "SELECT gpk.root_member_wallet.gpk2_balance FROM  gpk.root_member_wallet WHERE gpk2_account = '".$source_transferaccount_result[1]->account."' and gpk2_balance >= ".$d['transaction_money']."::money;";
    //var_dump($check_source_transferaccount_balance_sql);
    $check_source_transferaccount_balance_result = runSQLALL($check_source_transferaccount_balance_sql);
    //var_dump($check_source_transferaccount_balance_result);

    // 錢夠，轉帳動作才工作,否則結束
    if($check_source_transferaccount_balance_result[0] == 1) {
      // sql 交易 begin
      $transaction_money_sql = 'BEGIN;';
      //$transaction_money_sql = '';
      // 3. PGSQL 將 source_transferaccount 帳戶 ${site}_balance 欄位扣除 transaction_money 的值 , 餘額($new_balabce )為 ${site}_balance - transaction_money 但是要 source_transferaccount 的 gpk2_balance  >= 2000::money 才作這件事

      $transaction_money_sql = $transaction_money_sql."UPDATE gpk.root_member_wallet SET changetime = now(), gpk2_balance =
      ((SELECT root_member_wallet.gpk2_balance FROM  gpk.root_member_wallet WHERE id = ".$source_transferaccount_result[1]->id.") - ".$d['transaction_money']."::money)
      WHERE id = ".$source_transferaccount_result[1]->id." AND ((SELECT root_member_wallet.gpk2_balance FROM  gpk.root_member_wallet WHERE id = ".$source_transferaccount_result[1]->id.") >= ".$d['transaction_money']."::money);";

      // 4. PGSQL 將 destination_transferaccount 帳號 ${site}_balance 欄位加上 transaction_money 的值
      $transaction_money_sql = $transaction_money_sql."UPDATE gpk.root_member_wallet SET changetime = now(), gpk2_balance =
      ((SELECT root_member_wallet.gpk2_balance FROM  gpk.root_member_wallet WHERE id = ".$destination_transferaccount_result[1]->id.") + ".$d['transaction_money']."::money)
      WHERE id = ".$destination_transferaccount_result[1]->id." ;";

      // 操作：root_memberdepositpassbook
      // 5. PGSQL 新增 1 筆紀錄 帳號 source_transferaccount 轉帳到 destination_transferaccount , destination_transferaccount 存款 withdrawal 為 transaction_money
      $transaction_money_sql = $transaction_money_sql."INSERT INTO gpk.root_memberdepositpassbook
      (transaction_time, deposit, withdrawal, system_note, member_id, currency, summary, source_transferaccount, auditmode, auditmodeamount, casino, realcash, destination_transferaccount)
       VALUES ('now()', '".$d['transaction_money']."', '0', '".$d['system_note_input']."', '".$d['member_id']."', '".$config['currency_sign']."', '".$d['summary']."', '".$d['source_transferaccount']."', '".$d['auditmode_select']."', '".$d['auditmode_amount']."', '".$d['casino']."', '".$d['realcash']."', '".$d['destination_transferaccount']."');";

      // 6. PGSQL 新增 1 筆紀錄 帳號 destination_transferaccount 轉帳從 source_transferaccount , destination_transferaccount 提款 deposit 為 transaction_money
      $transaction_money_sql = $transaction_money_sql."INSERT INTO gpk.root_memberdepositpassbook
      (transaction_time, deposit, withdrawal, system_note, member_id, currency, summary, source_transferaccount, auditmode, auditmodeamount, casino, realcash, destination_transferaccount)
       VALUES ('now()', '0', '".$d['transaction_money']."', '".$d['system_note_input']."', '".$d['member_id']."', '".$config['currency_sign']."', '".$d['summary']."', '".$d['destination_transferaccount']."', '".$d['auditmode_select']."', '".$d['auditmode_amount']."', '".$d['casino']."', '".$d['realcash']."', '".$d['source_transferaccount']."');";

       $transaction_money_sql = $transaction_money_sql.'COMMIT;';

       // echo '<p>'.$transaction_money_sql.'</p>';
       $transaction_money_result = runSQLtransactions($transaction_money_sql);
       //$transaction_money_result = 1;
       if($transaction_money_result) {
        $logger = $d['source_transferaccount'].$d['summary'].'到'.$d['destination_transferaccount'].'金額'.$d['transaction_money'].'成功';
        // echo $logger;
        $r['code'] = 0;
        $r['message'] = $logger;
        return($r);
       }else{
	   	$logger = $d['source_transferaccount'].$d['summary'].'到'.$d['destination_transferaccount'].'金額'.$d['transaction_money'].'失敗';
        // echo $logger;
        $r['code'] = 1;
        $r['message'] = $logger;
        return($r);
       }

    }else{
      $logger = $d['source_transferaccount'].', 存款不足，結束。';
      // echo $logger;
      $r['code'] = 2;
      $r['message'] = $logger;
      return($r);
    }

    return($r);
}
// ----------------------------------------------------------------------------
// trial_gpk2_memberdeposit_transfer 轉帳函式 -- 前台試玩專用 end
// ----------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// 建立一個試用帳號 in gpk2
// ----------------------------------------------------------------------------
/*
$agentaccount_input 推薦人
$memberaccount_input 會員帳號
$password_input 密碼
$realname_input 真實姓名
*/
function create_trial_account($agentaccount_input,$memberaccount_input,$password_input,$realname_input) {
  // 狀態回傳值
  $r['code'] = NULL;
  $r['message'] = NULL;

  // 11.檢查帳號是否衝突存在, 存在就離開
  $check_account_name_alive_sql = "SELECT * FROM gpk.root_member WHERE account = '$memberaccount_input';";
  // var_dump($check_account_name_alive_sql);
  $check_account_name_alive_result = runSQLall($check_account_name_alive_sql);
  if($check_account_name_alive_result[0] == 0) {

  	// 12.密碼欄位不能是空的
  	if($password_input != NULL) {
  		// 13.檢查推薦人是否有效
  		$check_agent_alive_sql = "SELECT * FROM gpk.root_member WHERE therole = 'A' AND account = '$agentaccount_input' AND status = '1'; ";
  		// var_dump($check_agent_alive_sql);
  		$check_agent_alive_result = runSQLall($check_agent_alive_sql);

  		if($check_agent_alive_result[0] == 1) {
  			// $logger = '推薦人存在. insert sql';
  			//echo $logger;
  			// var_dump($check_agent_alive_result);

        // default time zone
  			$timezone_string = 'Asia/Hong_Kong';
        // default trial role
        $therole_input    = 'T';
  			$parent_id = $check_agent_alive_result[1]->id;
  			$insert_new_member_sql = '';
  			$insert_new_member_sql = 'INSERT INTO "root_member" ("account", "nickname", "realname", "passwd", "mobilenumber", "email", "therole", "parent_id", "sex", "birthday", "wechat", "qq", "withdrawalspassword", "timezone", "enrollmentdate") '.
  			" VALUES ( '$memberaccount_input', 'trial', '$realname_input', '$password_input', NULL, NULL, 'T', '$parent_id', NULL, NULL, NULL, NULL, '$password_input', '$timezone_string', 'now()' );";

  			$insert_new_member_result = runSQLtransactions($insert_new_member_sql);
  			var_dump($insert_new_member_result);
  			if($insert_new_member_result == 1) {
  				$logger = '新使用者&nbsp;'. $memberaccount_input.'&nbsp;帳號已經建立。';
  				memberlog2db($memberaccount_input,'member','notice', "$logger");
          $r['code'] = 0;
          $r['message'] = 'Trial account create success.';
  			}else{
  				$logger = '帳號建立失敗!!!';
          $r['code'] = 1;
          $r['message'] = $logger;
  			}
  			// success = 1 , false = 0


  		}else{
  			$logger = '無此推薦人或此推薦人非代理商!!';
        $r['code'] = 13;
        $r['message'] = $logger;
  		}

  	}else{
  		$logger = '密碼是空的，請再確認。';
      $r['code'] = 12;
      $r['message'] = $logger;
  		die();
  	}

  }else{
  	$logger = '帳號已經存在，不可以申請。';
    $r['code'] = 11;
    $r['message'] = $logger;
  }

return($r);
}
// ----------------------------------------------------------------------------
// 建立一個試用帳號 in gpk2 end
// ----------------------------------------------------------------------------


// ----------------------------------------------------------------------------
// 動作檢查
// ----------------------------------------------------------------------------
if($action == 'guest_trial') {
// ----------------------------------------------------------------------------
// 免費試玩功能的動作 , 對應 trial.php 程式
// ----------------------------------------------------------------------------

/*
// 變數設定在 config.php 需要修改成為 db 型態的設定。
// 系統預設

 0. 驗證碼要正確, 不可以是登入的使用者therole M A R 都不可以使用，只能是 T。

1. 檢查這個使用者 IP 是否登入過，如果有登入過。
查詢之前紀錄的 IP 資訊，及對應的登入帳號。
使用者 IP 登入後，登入時間 $GPK2_TRIAL['default_timeout'] 內可以重複登入遊戲。
間隔超過 $GPK2_TRIAL['default_interval'] 才可再次登入平台，顯示還有多少時間可以再度試玩。
如果時間超過使用限制，且餘額 < 1 ，表示錢已經花光，再次可登入時 $GPK2_TRIAL['default_agent'] 轉錢，加上 $GPK2_TRIAL['default_coin'] 的代幣給它。

2. 如果使用者沒有登入過，就建立一個新的 gpk2 trial 帳號。 (後台可以觀看管理測試帳號的狀態)
第一次建立帳號後發給 $GPK2_TRIAL['default_coin'] 的代幣 in gpk2 account上面，且可以隨著遊戲轉錢幣到每個 casino ，也可以(登出後)轉回來。
試玩帳號全部掛在 1 個代理商 $GPK2_TRIAL['default_agent'] 帳號下，每月算帳時需要排除這些帳戶資訊。
帳號建立命名規則為 trial+ip int / 密碼 trial+rand(10) , name trial + ip 管理員可以看到他的帳號密碼。

3. 試玩帳號，只能在前台登入，並只能操作 stationmail.php 站內公告 ,gamelobby.php 遊戲大廳MG , betrecord.php投注紀錄(限10筆紀錄), transaction.php交易紀錄(限10筆紀錄) ，其餘功能都不允許進入。

*/



	//var_dump($_SERVER);
	//var_dump($_POST);
	//var_dump($_COOKIE);
	//var_dump($_SESSION);

	global $GPK2_TRIAL;
  // 預設不能登入
  $trial_login = false;
  // 預設的顯示訊息
  $trial_login_message = '';

	if(isset($_POST['captcha_trial_input']) AND isset($_SESSION['captcha'])) {

		if($_SESSION['captcha'] == $_POST['captcha_trial_input'] ) {
			// captcha 驗證碼正確
			// echo 'captcha 驗證碼正確';
			unset($_SESSION['captcha']);
			// STEP 1
			// --------------------------------------------------------------------
			// status 本次登入的帳號狀態定義
			// 1 = 訪客可以合法登入, and 第 1,2 次合法的登入
			// 2 = 曾經來過的訪客，已經有帳號了，再合法時間內重複登入。
			// 3 = 間隔時間登入
      // 4 = 本次使用時間已經超過可用時間
      // 9 = 關閉此訪客來源，登入試用的功能。

			// NULL = not defaine
			$guestinfo['status']   = NULL;

			$guestinfo['REMOTE_ADDR'] = explode(',',$_SERVER['HTTP_X_FORWARDED_FOR'])['0'];
			$guestinfo['PHPSESSID']   = filter_var($_COOKIE['PHPSESSID'], FILTER_SANITIZE_STRING);
			$guestinfo['HTTP_USER_AGENT'] = filter_var($_SERVER['HTTP_USER_AGENT'], FILTER_SANITIZE_STRING);

			// 檢查這個使用者 IP 是否登入過, status = 1 計算為一次合法的登入，其他為重複或是測試的登入
			$query_triallog_sql = 'SELECT * FROM "root_membertriallog" WHERE ("status" = 1 OR "status" = 9) AND "ip" = '." '".$guestinfo['REMOTE_ADDR']."'".' AND "account" IS NOT NULL AND "passwd" IS NOT NULL;';
			// var_dump($query_triallog_sql);
			$query_triallog_r = runSQLALL($query_triallog_sql);
			// var_dump($query_triallog_r);
			// 以前是否有登錄過且有帳號密碼,  沒有登入為 0
			if($query_triallog_r[0] == 0) {

				//第一次建立帳號後發給 $GPK2_TRIAL['default_coin'] 的代幣 in gpk2 account上面，且可以隨著遊戲轉錢幣到每個 casino ，也可以(登出後)轉回來。
				//試玩帳號全部掛在 1 個代理商 $GPK2_TRIAL['default_agent'] 帳號下，每月算帳時需要排除這些帳戶資訊。
				//帳號建立命名規則為 trial+ip int / 密碼 trial+rand(10) , name trial + ip 管理員可以看到他的帳號密碼。

				// 沒有登入過
				// 建立一個 trial 帳號 in gpk2 , 寫入 log and member
				$guestinfo['agent']           = $GPK2_TRIAL['default_agent'];
				$guestinfo['account']         = 'gt'.time();  // ex:gt1476244176
				$guestinfo['passwd']          = rand(100000,999999);
				$guestinfo['passwd_sha1']     = sha1($guestinfo['passwd']);
				$guestinfo['realname']        = explode(',',$_SERVER['HTTP_X_FORWARDED_FOR'])['0'];
        $guestinfo['status']          = 1;

				// var_dump($guestinfo);
				// 建立使用者帳號
				$create_trial_result = create_trial_account($guestinfo['agent'],$guestinfo['account'],$guestinfo['passwd_sha1'],$guestinfo['realname']);
				// var_dump($create_trial_result);
				// 如果帳號建立成功的話
				if($create_trial_result['code'] == 0) {
					// 紀錄這個訪客的來源資訊
					$add_triallog_sql = 'INSERT INTO "root_membertriallog" ("ip", "account", "cookiename", "logintime", "logouttime", "count", "status", "deviceinfo", "passwd")'."
					VALUES ('".$guestinfo['REMOTE_ADDR']."', '".$guestinfo['account']."', '".$guestinfo['PHPSESSID']."', 'now()', NULL, '1', '".$guestinfo['status']."', '".$guestinfo['HTTP_USER_AGENT']."', '".$guestinfo['passwd']."');";
					var_dump($add_triallog_sql);
					$add_triallog_sql_r = runSQLALL($add_triallog_sql);
					$trial_login = true;
				}else{
					// 沒有建立成功，應該是系統有問題了。
					$logger = '試用功能，帳號建立有問題。請聯絡管理人員。';
					$trial_login = false;
					die($logger);
				}
			}else{
				// IP 有登錄過，查詢之前紀錄的 IP 資訊，及對應的登入帳號及密碼 -- 只有 $query_triallog_r[1] 這各有帳密
				$guestinfo['account']     = $query_triallog_r[1]->account;
				$guestinfo['passwd_sha1'] = sha1($query_triallog_r[1]->passwd);

        // 專門處理 status = 9 ，封鎖該  ip 的使用者。
        $guestinfo['status'] = $query_triallog_r[1]->status;
        // 沒有被封鎖就繼續, 被封鎖就離開。
        if($guestinfo['status'] != 9) {

          // IP 有登錄過，查詢之前登錄紀錄的 IP 資訊 ，最近 1 條就可以。get count
          $check_guest_login_sql = 'SELECT * FROM "root_membertriallog" WHERE "ip" = '."'".$guestinfo['REMOTE_ADDR']."'".'ORDER BY "id" DESC LIMIT 1;';
          //var_dump($check_guest_login_sql);
          $check_guest_login_result = runSQLALL($check_guest_login_sql);
          //var_dump($check_guest_login_result);
          if($check_guest_login_result[0] >= 1) {
            // 之前登入的 count 抓出來
            $guestinfo['count']       = $check_guest_login_result[1]->count+1;

            // echo "計算出，上次合法登入時間和現在時間的距離。";
            // 使用者 IP 登入後，登入時間 $GPK2_TRIAL['default_timeout'] 內可以重複登入遊戲。
            // SELECT id,ip,account,logintime,to_char(age(current_timestamp,logintime),'HH24:MI:SS') as ageinterval FROM root_membertriallog WHERE ip = '122.254.37.119' AND (logintime + 3600 * interval '1 second') >= current_timestamp  ORDER BY id DESC LIMIT 1;
            // $check_interval_sql = "SELECT id,ip,account,logintime,to_char(age(current_timestamp,logintime),'SSSS') as ageinterval FROM root_membertriallog WHERE ip = '".$guestinfo['REMOTE_ADDR']."' AND status = 1  ORDER BY id DESC LIMIT 1;";
            $check_interval_sql = "SELECT id,ip,account,logintime,to_char(age(current_timestamp,logintime),'DDD') as ageinterval_days, to_char(age(current_timestamp,logintime),'SSSS') as ageinterval FROM root_membertriallog WHERE ip = '".explode(',',$_SERVER['HTTP_X_FORWARDED_FOR'])['0']."' AND status = 1  ORDER BY id DESC LIMIT 1;";
            //var_dump($check_interval_sql);
            $check_interval_r = runSQLALL($check_interval_sql);
            //var_dump($check_interval_r);

            // 如果有資料的話
            if($check_interval_r[0] == 1) {
              // 將 ageinterval_days 轉換成為秒數 60*60*24 seconds
              $ageinterval_seconds = ($check_interval_r[1]->ageinterval_days*60*60*24) + $check_interval_r[1]->ageinterval;

              // 間隔超過 $GPK2_TRIAL['default_interval'] 才可再次登入平台，顯示還有多少時間可以再度試玩。
              // 如果時間超過使用限制，且餘額 < 1 ，表示錢已經花光，再次可登入時 $GPK2_TRIAL['default_agent'] 轉錢，加上 $GPK2_TRIAL['default_coin'] 的代幣給它。
              if($ageinterval_seconds > $GPK2_TRIAL['default_timeinterval']) {
                $trial_login_message = '距離下次登入間隔時間已經超過'.$GPK2_TRIAL['default_timeinterval'].'秒, 可以重新登入,繼續使用。';
                $guestinfo['status']          = 1;
                $trial_login = true;
              }else {
                $trial_login_message = '距離下次登入間隔時間尚未超過'.$GPK2_TRIAL['default_timeinterval'].'秒';
                $guestinfo['status']          = 3;
                $trial_login = false;

                // 使用者 IP 登入後，登入時間 $GPK2_TRIAL['default_timeout'] 內可以重複登入遊戲。
                if($ageinterval_seconds > $GPK2_TRIAL['default_timeout']) {
                  $trial_login_message = '本次使用時間已經超過'.$GPK2_TRIAL['default_timeout'].'秒，休息一下！！';
                  $guestinfo['status']          = 4;
                  // 記錄下來本次的登錄的資訊 --使用時間已經超過
                  /*
                  $add_triallog_sql = 'INSERT INTO "root_membertriallog" ("ip", "account", "cookiename", "logintime", "logouttime", "count", "status", "deviceinfo")'."
                  VALUES ('".$guestinfo['REMOTE_ADDR']."', '".$guestinfo['account']."', '".$guestinfo['PHPSESSID']."', 'now()', NULL, '".$guestinfo['count']."', '3', '".$guestinfo['HTTP_USER_AGENT']."');";
                  //var_dump($add_triallog_sql);
                  $add_triallog_sql_r = runSQLALL($add_triallog_sql);
                  //var_dump($add_triallog_sql_r);
                  $trial_login = false;
                  */
                  // die();
                }else{
                  $trial_login_message = '本次使用時間尚未超過'.$GPK2_TRIAL['default_timeout'].'秒，可以繼續使用。';
                  $guestinfo['status']          = 2;
                  $trial_login = true;
                }
              }
            }else{
              // 有可能沒資料, 因為 status != 1 , 表示之其都沒有合法登入的紀錄
            }
            // ----

          }else{
            // 沒有資料？？
            $trial_login_message =  '沒有資料，這不可能呀！！請聯絡管理人員。';
            $trial_login = false;
            die($trial_login_message);
          }

        }else{
          $trial_login_message = '此 IP '.explode(',',$_SERVER['HTTP_X_FORWARDED_FOR'])['0'].'已經被封鎖了。';
          $guestinfo['status']          = 9;
          $guestinfo['count']           = 0;
          $trial_login = false;
        }

        // 記錄下來本次的登錄的資訊
        $add_triallog_sql = 'INSERT INTO "root_membertriallog" ("ip", "account", "cookiename", "logintime", "logouttime", "count", "status", "deviceinfo")'."
        VALUES ('".$guestinfo['REMOTE_ADDR']."', '".$guestinfo['account']."', '".$guestinfo['PHPSESSID']."', 'now()', NULL, '".$guestinfo['count']."', '".$guestinfo['status']."', '".$guestinfo['HTTP_USER_AGENT']."');";
        //var_dump($add_triallog_sql);
        $add_triallog_sql_r = runSQLALL($add_triallog_sql);
        //var_dump($add_triallog_sql_r);

			}
      // end 曾經登入過的處理


			// STEP 2
      // ------------------------------------------------------------
			// 自動依據帳號密碼登入系統 and $trial_login = false;
			// 試用帳號 存在的話 ，嘗試登入並取得 session 使用者資訊。
			if(isset($guestinfo['account']) AND isset($guestinfo['passwd_sha1']) AND $guestinfo['account'] != NULL AND $trial_login == true) {

				// 登入帳號
				$sql = "select * from root_member where therole = 'T' and account = '".$guestinfo['account']."' and passwd = '".$guestinfo['passwd_sha1']."';";
				$r = runSQLALL($sql);
				// var_dump($r);

				// 認證正確
				if($r[0] == 1) {
					// 帳戶已經被鎖定
					if($r[1]->status == 0) {
						$logger = '你的IP及帳號已經被鎖定，請聯絡客服人員處理。';
						// die($logger);
					}else{
						// 此為 user 登入成功的處理

						// 將使用者資訊存到 session
						$_SESSION['member'] = $r[1];

						// 從錢包 wallet 取得帳戶餘額 , 並更新帳戶狀態
						$show_balance = get_gpk2_member_wallet_account_balance($_SESSION['member']->id, $_SESSION['member']->account);

						// 取得使用者預設的語系
						$_SESSION['lang'] = $r[1]->lang;

						// trial 會員 T ，這是測試帳號登入只會有 T 屬性
						// 紀錄這次的登入
						$logger = $_SESSION['member']->account.'登入成功';
						memberlog2db($_SESSION['member']->account,'trial','info', "$logger");
						//echo $logger;
						// 呼叫的頁面, reload 頁面 from server
						//echo '<script>location.reload("true");</script>';

						//var_dump($_SESSION);

            // 如果預設自動儲值功能被打開的話
            if(	$GPK2_TRIAL['auto_deposit_coin'] == 1) {
  						// if 使用者沒錢，給他一個基本額度玩. 有問題的話，可能是 bug
  						// $check_balance_sql = "SELECT regexp_replace(gpk2_balance::money::text, '[$,]', '', 'g')::numeric(7) as gpk2_balance_number , regexp_replace(mg2_balance::money::text, '[$,]', '', 'g')::numeric(7)  as mg2_balance_number  FROM root_member_wallet WHERE id = ".$_SESSION['wallet']->id.";";
  						$check_balance_sql = "SELECT * FROM root_member_wallet  WHERE (gpk2_balance < 1::money AND mg2_balance < 1::money) AND id = ".$_SESSION['member']->id.";";
  						// sql 沒有錢 = 沒有 row = 1 , 如果成立就是沒有錢
  						//var_dump($check_balance_sql);
  						$check_balance_sql_result = runSQLall($check_balance_sql);
  						//var_dump($check_balance_sql_result);
  						if($check_balance_sql_result[0] == 1 ) {
  							$logger =  "帳戶無餘額，自動轉帳1次 ".$GPK2_TRIAL['default_coin'];
                //echo $logger;
  							$trial_coin_result = trial_gpk2_memberdeposit_transfer( $_SESSION['member']->id, $GPK2_TRIAL['default_agent'], $_SESSION['member']->account, $GPK2_TRIAL['default_coin'] , '試玩存款', 0, 'FreeAudit', NULL, $GPK2_TRIAL['default_password_sha1'], 'Trial' );
  							// var_dump($trial_coin_result);
                // 只有出錯時，才顯示錯誤訊息及除錯
                if($trial_coin_result['code'] != 0) {
                  $logger = $trial_coin_result['message'];
                  //echo $logger;
                }
  						}
            }

					}
					$trial_login_message = $logger;
				}else{
					//$logger = $guestinfo['account'].'帳號或密碼'.$guestinfo['passwd_sha1'].'錯誤!';
					//memberlog2db('guest','login','notice', "$logger");
					$trial_login_message = "不允許試玩!!!";
				}
			}
		}else{
			$trial_login_message = '不正確的驗證碼';
		}
	}else{
		$trial_login_message = '沒有填寫驗證碼，或是頁面停留太久 timeout ，請重新整理。';
	}

  //  顯示目前的登入狀態，及登入訊息。
  // var_dump($trial_login);
  echo $trial_login_message;
  echo '<script>alert("'.$trial_login_message.'");location.reload();</script>';
// ----------------------------------------------------------------------------
}
// ----------------------------------------------------------------------------
// END
// ----------------------------------------------------------------------------




?>
