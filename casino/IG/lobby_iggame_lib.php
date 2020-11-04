<?php
// ----------------------------------------------------------------------------
// Features:	Casino 的專用函式庫
// File Name:	lobby_iggame_lib.php
// Author:		Webb Lu
// Related:
// Log:
// ----------------------------------------------------------------------------
// 2017.11.30
// 此函式提供給 lobby_iggame_action.php 使用, 修改自 lobby_iggame_lib.php, 負責娛樂城的轉換操作
// ----------------------------------------------------------------------------
/*
// function 索引及說明：
// -------------------
0. 輸入資料格式：
$IG_API_data = array(
'membercode' => igaccount,
'password' => igpassword,
'producttype' => producttype,
'amount' => amount,
'externaltransactionid' => externaltransactionid,
'currency' => 'CNY'
);
1. IG API 文件函式及用法 sample , 操作 IG API (by totalegame)
ig_api($method, $debug=0, $IG_API_data)

2. 依據使用者帳號資訊，檢查遠端 IG 的帳號是否存在，不存在就建立
create_casino_ig_account()

3.將傳入的 member id 值，取得他的 DB GTOKEN 餘額並全部傳送到 IG CASINO 上
把本地端的資料庫 root_member_wallets 的 GTOKEN_LOCK設定為 IG 餘額儲存在 ig_balance 上面
transferout_gtoken_ig_casino_balance($member_id, $debug = 0)

5. 取回 IG Casino 的餘額，並檢查上次離開時和目前的差額得出派彩金額，紀錄存簿。 v2017.1.2
db_ig2gpk_balance($gtoken_cashier_account, $ig_balance_api, $ig2gpk_balance, $ig_balance_db, $debug=0 )

6. 取回 IG Casino 的餘額 -- retrieve_ig_casino_balance
retrieve_ig_casino_balance($member_id, $debug=0)

7. 產生可以連到 IG FLASH game 的 url 位址
ig_flash_gamecode_restful_url($ig_account, $ig_password, $ig_gamecode)

8. 產生可以連到 IG HTML5 game 的 url 位址
ig_html5_gamecode_restful_url($ig_account, $ig_password, $ig_gamecode)

 */

// ---------------------------------------------------------------------------
// Features:
//  填入設定的功能，登入 IG api 的函式
// Usage:
//  ig_gpk_api($method, $debug=0, $IG_API_data)
// Input:
//  $method --> 操作的功能
//  $debug=0 --> 設定為 1 為除錯。
//  $IG_API_data --> 填入需的參數，需要搭配 method
// Return:
// -- 如果讀取投注紀錄成功的話 --
// $IG_API_result['curl_status'] = 0; // curl 正確
// $IG_API_result['count'] // 計算取得的紀錄數量有多少
// $IG_API_result['errorcode'] = 0; // 取得紀錄沒有錯誤
// $IG_API_result['Status'] // 回傳的狀態
// $IG_API_result['Result'] // 回傳的緬果
//
// -- 如果讀取投注紀錄失敗的話 --
// $IG_API_result['curl_status'] = 1; // curl 錯誤
// $IG_API_result['errorcode'] = 500; // 錯誤碼
// $IG_API_result['Result'] // 回傳的錯誤訊息
// ---------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// login IG through GPK API function
// ----------------------------------------------------------------------------
function ig_gpk_api($method, $debug = 0, $IG_API_data, $url_type) {
	//$debug=1;
	// 設定 socket_timeout , http://php.net/manual/en/soapclient.soapclient.php
	ini_set('default_socket_timeout', 5);

	// global $GPKAPI_CONFIG;
	global $config;
	global $system_mode, $IG_CONFIG;
	global $type;
	$dataarr = [];

	if($system_mode != 'developer') {
		// Setting url
		$url = $IG_CONFIG['url']->$url_type;
		$dataarr['hashCode'] = $config['ig_hashCode'];
		$dataarr['params'] = $IG_API_data;

		switch ($method) {
			case 'Login':
				$dataarr['command'] = 'LOGIN';
				break;

			case 'ChangePassword':
				$dataarr['command'] = 'CHANGE_PASSWORD';

				break;

			case 'GetBalance':
				$dataarr['command'] = 'GET_BALANCE';

				break;

			case 'Deposit':
				$dataarr['command'] = 'DEPOSIT';

				break;

			case 'Withdraw':
				$dataarr['command'] = 'WITHDRAW';
				if($IG_CONFIG['mode'] == 'test') $dataarr['command'] = 'GET_BALANCE';

				break;

			// 以 API 登入模擬踢除行為，踢除已經登入到香港彩及時時彩的特定使用者
		    case 'LockAccounts':
		      $func = __FUNCTION__;
		      $IG_categories = array_filter(array_keys((array) $IG_CONFIG['url']), function($value) { return !in_array($value, ['trade', 'record']); });

		      $ret = [];
		      foreach ($IG_categories as $game_category) {
		        $IG_API_data['currency'] = ($IG_CONFIG['mode'] == 'test') ? 'TEST' : 'CNY';
		        $IG_API_data['gameType'] = strtoupper($game_category);
		        $IG_API_data[strtolower($game_category).'Tray'] = 'B';

		        $IG_API_result = $func('Login', $debug, $IG_API_data, $game_category);
		        $ret[] = $IG_API_result;
		      };
		      if($debug) var_dump($ret);
		      // 返回陣列形式的 $ret, 特例, 待優化
		      return $ret;

		      break;

			default:
				$result = NULL;
				die('UNDEFINED ACTION');
				break;
		}
		$plaintext = json_encode($dataarr);
		if($debug) echo $plaintext;

		if (isset($plaintext)) {
			$ret = [];

			try {
				$ch = curl_init();

				curl_setopt_array($ch, [
					CURLOPT_URL => $url,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_ENCODING => "",
					CURLOPT_MAXREDIRS => 10,
					CURLOPT_TIMEOUT => 30,
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST => "POST",
					CURLOPT_POSTFIELDS => $plaintext,
					CURLOPT_HTTPHEADER => [
						"cache-control: no-cache",
						"content-type: application/json",
				 	],
				]);

				$response = curl_exec($ch);
				$err = curl_error($ch);

				if ($debug == 1) {
					echo curl_error($ch);
					var_dump($response);
				}

				if ($response) {
					$body = json_decode($response);
					if ($debug == 1) var_dump($body);

					$ret['curl_status'] = 0;
					$ret['errorcode'] = 0;
					$ret['Result'] = $body;
				} else {
					// curl 錯誤
					$ret['curl_status'] = 1;
					$ret['errorcode'] = curl_errno($ch);
					// 錯誤訊息
					$ret['Result'] = '系统维护中，请稍候再试';
				}
				// 關閉 curl
				curl_close($ch);
			} catch (Exception $e) {
				// curl 錯誤
				$ret['curl_status'] = 1;
				$ret['errorcode'] = 500;
				// 錯誤訊息
				$ret['Result'] = $e->getMessage();
			}
		} else {
			$ret = '';
		}
	}else{
		// curl 錯誤
		$ret['curl_status'] = 1;
		$ret['errorcode'] = 540;
		// 錯誤訊息
		$ret['Result'] = '开发环境不开发测试API，请至DEMO平台测试';
	}
	sleep(3);

	return ($ret);
}
// ----------------------------------------------------------------------------
// login IG through GPK API function end
// ----------------------------------------------------------------------------

// ---------------------------------------------------------------------------
// Features:
// 依據使用者帳號資訊，檢查遠端 IG 的帳號是否存在，不存在就建立
// Usage:
//  create_casino_ig_account()
// Input:
//
// Return:
//
// Log: 2017.12.01 Webb
// ---------------------------------------------------------------------------
function create_casino_ig_account($debug = 0) {
	// 變數
	global $config, $IG_CONFIG;
	// 回傳的變數
	$r = array();

	// 需要有 session 才可以登入, 且帳號只有 A and R 權限才可以建立 IG 帳號, 不允許管理員建立帳號進入遊戲。
	if (isset($_SESSION['member']) AND ($_SESSION['member']->therole == 'A' OR $_SESSION['member']->therole == 'M') AND ($config['businessDemo'] == 0 OR in_array('IG',$config['businessDemo_skipCasino']))) {

		// 當 $_SESSION['wallet']->transfer_flag 存在時，不可以執行，因為有其他程序在記憶體中執行。
		if (!isset($_SESSION['wallet_transfer'])) {

			// 透過這個旗標控制不能有第二個執行緒進入。
			$_SESSION['wallet_transfer'] = 'create_casino_ig_account ' . $_SESSION['member']->account;
			// 使用session時，若先前的頁面尚未執行完畢，預設session會被鎖住。
			// 此時，若執行另外一個也有使用session的頁面，則須等前一個頁面執行完畢，才能再執行。
			// 要避免等前一個頁面執行完畢，才能執行下一個頁面的情況，
			// 可以使用 session_write_close() ，告之不會再對session做寫入的動作，
			// 這樣其他頁面就不會等此頁面執行完才能再執行。
			// session_write_close() ;

			// echo '<p>確認會員使用者有登入，檢查是否已經有 session ，如果沒有建立一個。有的話驗證後離開。</p>';
			if (isset($_SESSION['member'])) {
				// ----------------------------------------------------------------------------

				// 如果沒有 ig 帳號的話, 馬上建立一個。
				if (!isset($_SESSION['member']->ig_account)) {
					//echo '沒有 ig 帳號, 馬上建立一個';
					$firstname = $_SESSION['member']->account;
					$lastname = $config['projectid'];
					// 建立代碼時，以系統的代碼為前三碼. 後面以會員的 ID 編號為對應，補滿 10 碼;
					$accnumber_id = 20000000000 + $_SESSION['member']->id;
					$ig_accountnumber = $lastname . $accnumber_id;
					// 密碼為 6-12 碼數字或英文，設定為 8 碼亂數。
					$ig_password = mt_rand(10000000, 99999999) . $lastname;
					if($debug) echo 'ig_password: ', $ig_password, '<br>';
					// 動作： AddAccount  建立帳戶 , accountNumber left empty to be generated automatically
					// firstname 設定為 gpk 系統帳號, 對照方便
					// lastname 為網站代碼, 對帳容易.

					$IG_API_data = [
						'username' 	=> $ig_accountnumber,
						'password' 	=> md5($ig_password),
						'currency' 	=> ($IG_CONFIG['mode'] == 'test') ? 'TEST' : 'CNY', // 測試線, 正式線用 CNY
						'nickname' 	=> $_SESSION['member']->nickname, //  optional
						'language' 	=> 'CN', // optional
						'line'     	=> 1, // optional, default 1
						'gameType' 	=> 'LOTTO', // ig 帳號可共用
						'lottoTray'	=> 'A', // ABCD 取1
						'userCode' 	=> 'testcode'
					];

					$IG_API_result = ig_gpk_api('Login', $debug, $IG_API_data, $url_type = 'lotto');
					if($debug) var_dump($ig_password, $IG_API_result);


					if ($IG_API_result['errorcode'] == 0 AND isset($IG_API_result['Result']->errorCode) AND $IG_API_result['Result']->errorCode == 0) {
						// 取得建立好的帳號資訊
						$ig_accountnumber_api = $ig_accountnumber;
						$ig_password_api = $ig_password;
						$member_wallet_id = $_SESSION['member']->id;

						// 紀錄建立 API 的帳號相關資訊
						$logger = 'API IG 帐号建立成功 AccountNumber=' . $ig_accountnumber_api . ',  PinCode=' . $ig_password_api . ', ' . json_encode($IG_API_result);
						memberlog2db($_SESSION['member']->account, 'IG2 API', 'notice', "$logger");
						member_casino_transferrecords('lobby', 'IG', '0', $logger, 'success');

						// 成功
						$r['ErrorCode'] = 10;
						$r['ErrorMessage'] = $logger;

						// 更新錢包的 IG 帳號密碼
						// $update_wattet_sql = "UPDATE root_member_wallets SET changetime = now(), ig_account = '$ig_accountnumber_api', ig_password = '$ig_password_api' WHERE id = $member_wallet_id;";
						$update_wattet_sql = "UPDATE root_member_wallets SET changetime = now(), casino_accounts= casino_accounts || '{\"IG\":{\"account\":\"$ig_accountnumber_api\", \"password\":\"$ig_password_api\", \"balance\":\"0.0\"}}' WHERE id = '$member_wallet_id';";

						if($debug) echo $update_wattet_sql, "\n測試模式";
						$update_wattet_sql_result = runSQL($update_wattet_sql);

						if ($update_wattet_sql_result == 1) {
							// 成功的話，更新 session 的 IG account and password 資訊。
							$_SESSION['member']->ig_account = $ig_accountnumber_api;
							$_SESSION['member']->ig_password = $ig_password_api;

							// 更新 wallet 成功
							$logger = 'IG 帐号 ' . $ig_accountnumber_api . ' 密码： ' . $ig_password_api . ' 写入 DB root_member_wallet 成功';
							$r['ErrorCode'] = 0;
							$r['ErrorMessage'] = $logger;
						} else {
							// 更新 wallet 失敗ˋ
							$logger = 'IG 帐号 ' . $ig_accountnumber_api . ' 密码： ' . $ig_password_api . ' 写入 DB root_member_wallet 失败';
							$r['ErrorCode'] = 1;
							$r['ErrorMessage'] = $logger;
						}
						// var_dump($update_wattet_sql_result);
					} else {
						// 失敗
						// var_dump($IG_API_result);
						$logger = 'API IG 帐号建立失败; ig_api(' . $IG_API_result['Result']->errorCode . '): ' . $IG_API_result['Result']->errorMessage;
						$r['ErrorCode'] = 21;
						$r['ErrorMessage'] = $logger;
						member_casino_transferrecords('lobby', 'IG', '0', $logger, 'fail');
					}

				} else {
					// 有帳號存在 IG 內  echo '目前使用者，是否真的存在 IG ';
					// 查詢 目前使用者的 IG 餘額
					$delimitedAccountNumbers = $_SESSION['member']->ig_account;
					$IG_API_data = array(
						'username' => $delimitedAccountNumbers,
						'password' => md5($_SESSION['member']->ig_password)
					);
					$IG_API_result = ig_gpk_api('GetBalance', $debug, $IG_API_data, 'trade');
					if($debug) var_dump($IG_API_result);

					if ($IG_API_result['errorcode'] == 0 AND isset($IG_API_result['Result']->errorCode) AND $IG_API_result['Result']->errorCode == 0) {
						// 查詢餘額動作, 使用者存在
						//var_dump($IG_API_result);
						$logger = '会员帐号 ' . $delimitedAccountNumbers . '，余额 ' . $IG_API_result['Result']->params->balance;
						$r['ErrorCode'] = 20;
						$r['ErrorMessage'] = $logger;
						member_casino_transferrecords('lobby', 'IG', '0', $logger, 'success');
					} else {
						$logger = '会员帐号 ' . $delimitedAccountNumbers . ' 不存在';
						$r['ErrorCode'] = 14;
						$r['ErrorMessage'] = $logger;
						member_casino_transferrecords('lobby', 'IG', '0', $logger, 'fail');
					}
				}
			} else {
				$logger = '会员需要登入才可以建立 IG 帐号';
				$r['ErrorCode'] = 22;
				$r['ErrorMessage'] = $logger;
			}

			// 執行完成後，把 $_SESSION['wallet_transfer'] 變數清除, 其他程式才可以進入。
			unset($_SESSION['wallet_transfer']);

		} else {
			$logger = '同使用者，API 动作产生，不可以执行。' . $_SESSION['wallet_transfer'];
			// echo $logger;
			$r['ErrorCode'] = 9;
			$r['ErrorMessage'] = $logger;
			member_casino_transferrecords('lobby', 'IG', '0', $_SESSION['member']->account.':'.$logger, 'fail');
		}

	} else {
		// 條件步符合, 不建立帳號
		$logger = '需要有 session 才可以操作, 且帐号只有 A and M 权限才可以建立 IG 帐号, 不允许管理员建立帐号进入游戏';
		$r['ErrorCode'] = 99;
		$r['ErrorMessage'] = $logger;
	}

	return ($r);
}
// ---------------------------------------------------------------------------
// 依據使用者帳號資訊，建立遠端 IG 的對應帳號 END
// ---------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// Features:
//   將傳入的 member id 值，取得他的 DB GTOKEN 餘額並全部傳送到 IG CASINO 上
//   把本地端的資料庫 root_member_wallets 的 GTOKEN_LOCK設定為 IG 餘額儲存在 ig_balance 上面
// Usage:
//   transferout_gtoken_ig_casino_balance($member_id)
// Input:
//   $member_id --> 會員 ID
//   debug = 1 --> 進入除錯模式
//   debug = 0 --> 關閉除錯
// Return:
//   code = 1  --> 成功
//   code != 1  --> 其他原因導致失敗
// ----------------------------------------------------------------------------
function transferout_gtoken_ig_casino_balance($member_id, $debug = 0) {
	global $config, $IG_CONFIG;
	$check_return = [];
	// 將目前所在的 ID 值
	// $member_id = $_SESSION['member']->id;
	// 驗證並取得帳戶資料
	$member_sql = "SELECT gtoken_balance,gtoken_lock,
        casino_accounts->'IG'->>'account' as ig_account,
        casino_accounts->'IG'->>'password' as ig_password,
        casino_accounts->'IG'->>'balance' as ig_balance FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.id = '" . $member_id . "';";
	$r = runSQLall($member_sql);
	if ($debug == 1) {
		var_dump($r);
	}
	if ($r[0] == 1 AND $config['casino_transfer_mode'] != 0) {

		// 沒有 IG 帳號的話，根本不可以進來。
		if ($r[1]->ig_account == NULL OR $r[1]->ig_account == '') {
			$check_return['messages'] = '你还没有 IG 帐号。';
			$check_return['code'] = 12;
		} elseif ($r[1]->gtoken_balance >= '1') {

			// 需要 gtoken_lock 沒有被設定的時候，才可以使用這功能。
			if ($r[1]->gtoken_lock == NULL OR $r[1]->gtoken_lock == 'IG') {
				$username = $r[1]->ig_account;
				$password_md5 = md5($r[1]->ig_password);

				// 查詢 ig 餘額
				$IG_API_data = [
					'username' => (string) $username,
					'password' => (string) $password_md5,
				];
				$IG_API_result = ig_gpk_api('GetBalance', $debug, $IG_API_data, 'trade');

				if ($IG_API_result['errorcode'] == 0 AND isset($IG_API_result['Result']->errorCode) AND $IG_API_result['Result']->errorCode == 0):
					// 查詢餘額動作，成立後執行. 失敗的話結束，可能網路有問題。
					$ig_balance_api = round($IG_API_result['Result']->params->balance, 2);
					$logger = 'IG API 查询余额为' . $IG_API_result['Result']->params->balance . '操作的余额为' . $ig_balance_api;
					$r['code'] = 1;
					$r['messages'] = $logger;
					$amount = $r[1]->gtoken_balance;
				else:
					$ig_balance_api = 0;
					$logger = '[测试线] IG API 查询余额失败，系统维护中请晚点再试。';
					$r['code'] = 403;
					$r['messages'] = $logger;
					member_casino_transferrecords('IG', 'lobby', '0', $logger, 'fail');
					if ($debug == 1) var_dump($IG_API_result);
					$amount = 0;
				endif;

				if ($IG_CONFIG['mode'] == 'test' && $ig_balance_api > 0):
					// 是測試線，且 IG 尚有餘額則不存款，並記錄當下餘額
					$togtoken_sql = "UPDATE root_member_wallets SET gtoken_lock = 'IG'  WHERE id = '$member_id';";
					$togtoken_sql.= "UPDATE root_member_wallets SET casino_accounts=jsonb_set(casino_accounts,'{\"IG\",\"balance\"}','{$ig_balance_api}') WHERE id = '$member_id';";
				elseif ($IG_CONFIG['mode'] == 'test' && $ig_balance_api == 0):
					// 動作： 將本地端所有的 gtoken 餘額 Deposit 到 ig 對應的帳戶
					// $amount = $r[1]->gtoken_balance;
					// IG 專用：交易唯一號; 這裡使用 timestamp 並與 logger 一同寫入
					// $ref_number = time();(fix by Ian : 改成統一ref格式)
					$ref_number = 'ig0Deposit0'.date("Ymdhis");
					if ($config['casino_transfer_mode'] == 2) $amount = 10;
					$IG_API_data = [
						'username' => (string) $username,
						'password' => (string) $password_md5,
						'ref' => (string) $ref_number,
						'desc' => "api 存入 $accountNumber 金额 $amount",
						'amount' => (string) $amount
					];

					$togtoken_sql = "UPDATE root_member_wallets SET gtoken_lock = 'IG'  WHERE id = '$member_id';";
					$togtoken_sql.= "UPDATE root_member_wallets SET casino_accounts=jsonb_set(casino_accounts,'{\"IG\",\"balance\"}','{$amount}') WHERE id = '$member_id';";
					$IG_API_result = ig_gpk_api('Deposit', $debug, $IG_API_data, 'trade');
				else:
					// 動作： 將本地端所有的 gtoken 餘額 Deposit 到 ig 對應的帳戶
					// $amount = $r[1]->gtoken_balance;
					// IG 專用：交易唯一號; 這裡使用 timestamp 並與 logger 一同寫入
					// $ref_number = time();(fix by Ian : 改成統一ref格式)
					$ref_number = 'ig0Deposit0'.date("Ymdhis");
					if ($config['casino_transfer_mode'] == 2) $amount = 10;
					$IG_API_data = [
						'username' => (string) $username,
						'password' => (string) $password_md5,
						'ref' => (string) $ref_number,
						'desc' => "api 存入 $accountNumber 金额 $amount",
						'amount' => (string) $amount
					];

					$togtoken_sql = "UPDATE root_member_wallets SET gtoken_lock = 'IG'  WHERE id = '$member_id';";
					$togtoken_sql.= "UPDATE root_member_wallets SET gtoken_balance='0',casino_accounts=jsonb_set(casino_accounts,'{\"IG\",\"balance\"}','{$amount}')  WHERE id = '$member_id';";
					$IG_API_result = ig_gpk_api('Deposit', $debug, $IG_API_data, 'trade');
				endif;

				if ($IG_API_result['errorcode'] == 0 AND isset($IG_API_result['Result']->errorCode) AND $IG_API_result['Result']->errorMessage == null) {
					if ($debug == 1) {
						var_dump($IG_API_data);
						var_dump($IG_API_result);
					}

					$togtoken_sql_result = runSQLtransactions($togtoken_sql);
					if ($debug == 1) {
						var_dump($togtoken_sql);
						var_dump($togtoken_sql_result);
					}
					if ($togtoken_sql_result) {
						$check_return['messages'] = '所有GTOKEN余额已经转到IG彩票。 IG API logid ' . $IG_API_result['Result']->logId .
							' 转帐单号 ref(时间戳) ' . $ref_number .
							' IG帐号' . $accountNumber . 'IG新增' . $amount;
						$check_return['code'] = 1;
						memberlog2db($_SESSION['member']->account, 'gpk2ig', 'info', $check_return['messages']);
						member_casino_transferrecords('lobby', 'IG', $amount, $check_return['messages'], 'success',
							$IG_API_data['ref'], 1);
					} else {
						$check_return['messages'] = '余额处理，本地端资料库交易错误。';
						$check_return['code'] = 14;
						memberlog2db($_SESSION['member']->account, 'gpk2ig', 'error', $check_return['messages']);
						member_casino_transferrecords('lobby', 'IG', $amount, $check_return['messages'], 'warning',
							$IG_API_data['ref'], 2);
					}
				} else {
					$check_return['messages'] = '余额转移到 IG 时失败！！';
					$check_return['code'] = 13;
					memberlog2db($_SESSION['member']->account, 'gpk2ig', 'error', $check_return['messages']);
					member_casino_transferrecords('lobby', 'IG', $amount, $check_return['messages'], 'fail',
						$IG_API_data['ref'], 2);
				}

			} else {
				$check_return['messages'] = '此帐号已经在 IG 彩票活动，请勿重复登入。';
				$check_return['code'] = 11;
				member_casino_transferrecords('lobby', 'IG', '0', $check_return['messages'], 'warning');
			}

		} else {
			$check_return['messages'] = '所有GTOKEN余额为 0 故不进行转帐交易。';
			$check_return['code'] = 15;
			memberlog2db($_SESSION['member']->account, 'gpk2ig', 'info', $check_return['messages']);
			member_casino_transferrecords('lobby', 'IG', '0', $check_return['messages'], 'info');
		}
	} elseif ($r[0] == 1 AND $config['casino_transfer_mode'] == 0) {
		$check_return['messages'] = '测试环境不进行转帐交易';
		$check_return['code'] = 1;
		member_casino_transferrecords('lobby', 'IG', '0', $check_return['messages'], 'info');
	} else {
		$check_return['messages'] = '无此帐号 ID = ' . $member_id;
		$check_return['code'] = 0;
		member_casino_transferrecords('lobby', 'IG', '0', $check_return['messages'], 'fail');
	}

	// var_dump($check_return);
	return ($check_return);
}
// ----------------------------------------------------------------------------
// END: 將傳入的 member id 值，取得他的 DB GTOKEN 餘額並全部傳送到 IG CASINO 上
// ----------------------------------------------------------------------------

// ----------------------------------------------------------------------------
/*
目的：取回 IG Casino 的餘額，並檢查上次離開時和目前的差額得出派彩金額，紀錄存簿。 v2017.1.2

1. 查詢 DB 的 gtoken_lock  是否有紀錄在 IG 帳戶，NULL 沒有紀錄的話表示沒有餘額在 IG 帳戶。
2. AND 當 session 有 ig_balance 的時候才動作, 如果沒有則結束。表示 db 帳號資料有問題。
del ---- 3. lock 這個程序, 確保唯一性。使用 $_SESSION['wallet_transfer']  旗標，鎖住程序，不要同時間同一個人執行。需要配合 session_write_close() 才可以。
4. Y , gtoken 紀錄為 IG , API 檢查 IG 的餘額有多少

5. 承接 4 ,如果 IG餘額 > 1
5.1 執行 IG API 取回 IG 餘額 ，到 IG 的出納帳戶(API操作) , 成功才執行 5.2,5.3

5.2 把 IG API 傳回的餘額，透過 GTOKEN出納帳號，轉帳到的相對使用者 account 的 GTOKEN 帳戶。 (DB操作)
5.3 紀錄當下 DB ig_balance 的餘額紀錄：存摺 GTOKEN 紀錄: (IG API)收入=4 , (DB IG餘額)支出=10 ,(派彩)餘額 = -6 + 原有結餘 , 摘要：IG派彩 (DB操作)
5.1 ~ 5.3 必須一定要全部成功，才算成功。如果 5.1 成功後，但 5.2,5.3 失敗的話，紀錄為 ERROR LOG，需要通知管理員處理。(message system)
6. 紀錄這次的 retrieve_ig_casino_balance 操作，為一次交易紀錄。後續可以查詢。(Confirmation Number)
7. 執行完成後，需要 reload page in lobby_iggame ，把 $_SESSION['wallet_transfer'] 變數清除, 其他程式才可以進入。
8. 把 GTOKEN_LOCK 設定為 NULL , 表示已經沒有餘額在娛樂城了。
 */

// ---------------------------------------------------------------------------------
// 取回 IG Casino 的餘額 -- 針對 db 的處理函式，只針對此功能有用。 -- retrieve_ig_casino_balance
// 不能單獨使用，需要搭配 retrieve_ig_casino_balance
// ---------------------------------------------------------------------------
// Features:
//  5.2 把 IG API 傳回的餘額，透過 GTOKEN出納帳號，轉帳到的相對使用者 account 的 GTOKEN 帳戶。 (DB操作)
//  5.3 紀錄當下 DB ig_balance 的餘額紀錄：存摺 GTOKEN 紀錄: (IG API)收入=4 , (DB IG餘額)支出=10 ,(派彩)餘額 = -6 + 原有結餘 , 摘要：IG派彩 (DB操作)
// Usage:
//  db_ig2gpk_balance($gtoken_cashier_account, $ig_balance_api, $ig2gpk_balance, $ig_balance_db );
// Input:
//  $gtoken_cashier_account   --> $gtoken_cashier_account(此為系統代幣出納帳號 global var.)
//  $ig_balance_api           --> 取得的 IG API 餘額 , 保留小數第二位 round( $x, 2);
//  $ig2gpk_balance           --> 派彩 = 娛樂城餘額 - 本地端IG支出餘額
//  $ig_balance_db            --> 在剛取出的 wallets 資料庫中的餘額(支出)
// Return:
//  $r['ErrorCode']     = 1;  --> 成功
// ---------------------------------------------------------------------------
function db_ig2gpk_balance($gtoken_cashier_account, $ig_balance_api, $ig2gpk_balance, $ig_balance_db, $debug = 0) {

	global $gtoken_cashier_account;
	global $transaction_category;
	global $auditmode_select;
	global $config, $IG_CONFIG;
	$d = [];
	$r = [];

	// 取得來源與目的帳號的 id ,  $gtoken_cashier_account(此為系統代幣出納帳號 global var.)
	// --------
	$d['source_transferaccount'] = $gtoken_cashier_account;
	$d['destination_transferaccount'] = $_SESSION['member']->account;
	// --------
	$source_id_sql = "SELECT * FROM root_member WHERE account = '" . $d['source_transferaccount'] . "';";
	//var_dump($source_id_sql);
	$source_id_result = runSQLall($source_id_sql);
	$destination_id_sql = "SELECT * FROM root_member WHERE account = '" . $d['destination_transferaccount'] . "';";
	//var_dump($destination_id_sql);
	$destination_id_result = runSQLall($destination_id_sql);
	if ($source_id_result[0] == 1 AND $destination_id_result[0] == 1) {
		$d['source_transfer_id'] = $source_id_result[1]->id;
		$d['destination_transfer_id'] = $destination_id_result[1]->id;
	} else {
		$logger = '转帐的来源与目的帐号可能有问题，请稍候再试。';
		$r['ErrorCode'] = 590;
		$r['ErrorMessage'] = $logger;
		echo "<p> $logger </p>";
		die();
	}
	// ---------------------------------------------------------------------------------

	if ($debug == 1) {
		var_dump($ig2gpk_balance);
	}

	// 派彩有三種狀態，要有不同的對應 SQL 處理
	// --------------------------------
	// $ig2gpk_balance >= 0; 從娛樂城贏錢 or 沒有輸贏，把 IG 餘額取回 gpk。
	// $ig2gpk_balance < 0; 從娛樂城輸錢
	// --------------------------------
	if ($ig2gpk_balance >= 0) {
		// ---------------------------------------------------------------------------------
		// $ig2gpk_balance >= 0; 從娛樂城贏錢 or 沒有輸贏，把 IG 餘額取回 gpk。
		// ---------------------------------------------------------------------------------

		// 先取得當下的  wallets 變數資料,等等 sql 更新後. 就會消失了。
		$wallets_sql = "SELECT gtoken_balance,casino_accounts->'IG'->>'balance' as ig_balance FROM root_member_wallets WHERE id = '" . $_SESSION['member']->id . "';";
		//var_dump($wallets_sql);
		$wallets_result = runSQLall($wallets_sql);
		//var_dump($wallets_result);
		// 在剛取出的 wallets 資料庫中ig的餘額(支出)
		$gtoken_ig_balance_db = round($wallets_result[1]->ig_balance, 2);
		// 在剛取出的 wallets 資料庫中token的餘額(支出)
		$gtoken_balance_db = round($wallets_result[1]->gtoken_balance, 2);
		// 派彩 = 娛樂城餘額 - 本地端IG支出餘額
		// 判斷是否為測試環境, 如是則必需用DB中gtoken的值加上玩家的變化餘額才是真正的錢包餘額
		if ($IG_CONFIG['mode'] == 'test') {
			$gtoken_balance = round(($gtoken_balance_db + $ig2gpk_balance), 2);
		} else {
			$gtoken_balance = round(($gtoken_balance_db + $gtoken_ig_balance_db + $ig2gpk_balance), 2);
		}

		// 交易開始
		$ig2gpk_transaction_sql = 'BEGIN;';
		// 存款金額 -- 娛樂城餘額
		$d['deposit'] = $gtoken_balance;
		// 提款金額 -- 本地端支出
		$d['withdrawal'] = $ig_balance_db;
		// 操作者
		$d['member_id'] = $_SESSION['member']->id;
		// IG + 代幣派彩
		$d['summary'] = 'IG' . $transaction_category['tokenpay'];
		// 稽核方式
		$d['auditmode'] = $auditmode_select['ig'];
		// 稽核金額 -- 派彩無須稽核
		$d['auditmodeamount'] = 0;
		// IG 取回的餘額為真錢
		$d['realcash'] = 2;
		// 交易類別 IG + $transaction_category['tokenpay']
		$d['transaction_category'] = 'tokenpay';
		// 變化的餘額
		$d['balance'] = $ig2gpk_balance;
		// var_dump($d);

		// 操作 root_member_wallets DB, 把 ig_balance 設為 0 , 把 gtoken_lock = null, 把 gtoken_balance = $d['deposit']
		// 錢包存入 餘額 , 把 ig_balance 扣除全部表示支出(投注).
		// $ig2gpk_transaction_sql = $ig2gpk_transaction_sql."
		// UPDATE root_member_wallets SET changetime = NOW(), gtoken_balance = ".$d['deposit'].", ig_balance = 0, gtoken_lock = NULL WHERE id = '".$d['destination_transfer_id']."'; ";
		$ig2gpk_transaction_sql = $ig2gpk_transaction_sql . "
      UPDATE root_member_wallets SET changetime = NOW(), gtoken_balance =  " . $d['deposit'] . ", gtoken_lock = NULL,casino_accounts= jsonb_set(casino_accounts,'{\"IG\",\"balance\"}','0') WHERE id = '" . $d['destination_transfer_id'] . "'; ";
		// 目的帳號上的註記
		$d['destination_notes'] = '(會員收到IG派彩' . $d['balance'] . ')';
		// 針對目的會員的存簿寫入，$ig2gpk_balance >= 1 表示贏錢，所以從出納匯款到使用者帳號。
		$ig2gpk_transaction_sql = $ig2gpk_transaction_sql .
			'INSERT INTO "root_member_gtokenpassbook" ("transaction_time", "deposit", "withdrawal", "system_note", "member_id", "currency", "summary", "source_transferaccount", "auditmode", "auditmodeamount", "realcash", "destination_transferaccount", "transaction_category", "balance")' .
			"VALUES ('now()', '" . $d['deposit'] . "', '" . $d['withdrawal'] . "', '" . $d['destination_notes'] . "', '" . $d['member_id'] . "', 'CNY', '" . $d['summary'] . "', '" . $d['destination_transferaccount'] . "', '" . $d['auditmode'] . "', '" . $d['auditmodeamount'] . "', '" . $d['realcash'] . "', " .
			"'" . $d['destination_transferaccount'] . "','" . $d['transaction_category'] . "', (SELECT gtoken_balance FROM root_member_wallets WHERE id = " . $d['destination_transfer_id'] . ") );";

		// 針對來源出納的存簿寫入
		$ig2gpk_transaction_sql = $ig2gpk_transaction_sql . "
      UPDATE root_member_wallets SET changetime = NOW(), gtoken_balance = (gtoken_balance - " . $d['balance'] .") WHERE id = '" . $d['source_transfer_id'] . "'; ";
		// 來源帳號上的註記
		$d['source_notes'] = '(出納帳號 ' . $d['source_transferaccount'] . ' 幫IG派彩到會員 ' . $d['destination_transferaccount'] . ')';
		$ig2gpk_transaction_sql = $ig2gpk_transaction_sql .
			'INSERT INTO "root_member_gtokenpassbook" ("transaction_time", "deposit", "withdrawal", "system_note", "member_id", "currency", "summary", "source_transferaccount", "auditmode", "auditmodeamount", "realcash", "destination_transferaccount", "transaction_category", "balance")' .
			"VALUES ('now()', '0', '" . $d['balance'] . "', '" . $d['source_notes'] . "', '" . $d['member_id'] . "', 'CNY', '" . $d['summary'] . "', '" . $d['source_transferaccount'] . "', '" . $d['auditmode'] . "', '" . $d['auditmodeamount'] . "', '" . $d['realcash'] . "', " .
			"'" . $d['source_transferaccount'] . "','" . $d['transaction_category'] . "', (SELECT gtoken_balance FROM root_member_wallets WHERE id = " . $d['source_transfer_id'] . "));";

		// commit 提交
		$ig2gpk_transaction_sql = $ig2gpk_transaction_sql . 'COMMIT;';

		if ($debug == 1) {
			echo '<p>SQL=' . $ig2gpk_transaction_sql . '</p>';
		}

		// 執行 transaction sql
		$ig2gpk_transaction_result = runSQLtransactions($ig2gpk_transaction_sql);
		if ($ig2gpk_transaction_result) {
			$logger = '从IG帐号' . $_SESSION['member']->ig_account . '取回余额到代币，统计后收入=' . $ig_balance_api . '，支出=' . $ig_balance_db . '，共计派彩=' . $ig2gpk_balance;
			$r['ErrorCode'] = 1;
			$r['ErrorMessage'] = $logger;
			memberlog2db($_SESSION['member']->account, 'iggame', 'info', "$logger");
			member_casino_transferrecords('IG', 'lobby', $ig_balance_api, $logger, 'info');
		} else {
			//5.1 ~ 5.3 必須一定要全部成功，才算成功。如果 5.1 成功後，但 5.2,5.3 失敗的話，紀錄為 ERROR LOG，需要通知管理員處理。(message system)
			$logger = '从IG帐号' . $_SESSION['member']->ig_account . '取回余额到代币，统计后收入=' . $ig_balance_api . '，支出=' . $ig_balance_db . '，共计派彩=' . $ig2gpk_balance;
			$logger = $logger . '但资料库处理错误，请通知客服人员处理。';
			memberlog2db($d['member_id'], 'ig_transaction', 'error', "$logger");
			$r['ErrorCode'] = 406;
			$r['ErrorMessage'] = $logger;
			memberlog2db($_SESSION['member']->account, 'iggame', 'error', "$logger");
			member_casino_transferrecords('IG', 'lobby', $ig_balance_api, $logger, 'warning');
		}

		if ($debug == 1) {
			var_dump($r);
		}
		// ---------------------------------------------------------------------------------

	} elseif ($ig2gpk_balance < 0) {
		// ---------------------------------------------------------------------------------
		// $ig2gpk_balance < 0; 從娛樂城輸錢
		// ---------------------------------------------------------------------------------

		// 先取得當下的  wallets 變數資料,等等 sql 更新後. 就會消失了。
		$wallets_sql = "SELECT gtoken_balance,casino_accounts->'IG'->>'balance' as ig_balance FROM root_member_wallets WHERE id = '" . $_SESSION['member']->id . "';";
		//var_dump($wallets_sql);
		$wallets_result = runSQLall($wallets_sql);
		//var_dump($wallets_result);
		// 在剛取出的 wallets 資料庫中ig的餘額(支出)
		$gtoken_ig_balance_db = round($wallets_result[1]->ig_balance, 2);
		// 在剛取出的 wallets 資料庫中gtoken的餘額(支出)
		$gtoken_balance_db = round($wallets_result[1]->gtoken_balance, 2);
		// 派彩 = 娛樂城餘額 - 本地端IG支出餘額
		// 判斷是否為測試環境, 如是則必需用DB中gtoken的值加上玩家的變化餘額才是真正的錢包餘額
		if ($IG_CONFIG['mode'] == 'test') {
			$gtoken_balance = round(($gtoken_balance_db + $ig2gpk_balance), 2);
		} else {
			$gtoken_balance = round(($gtoken_balance_db + $gtoken_ig_balance_db + $ig2gpk_balance), 2);
		}

		// 交易開始
		$ig2gpk_transaction_sql = 'BEGIN;';
		// 存款金額 -- 娛樂城餘額
		$d['deposit'] = $gtoken_balance;
		// 提款金額 -- 本地端支出
		$d['withdrawal'] = $ig_balance_db;
		// 操作者
		$d['member_id'] = $_SESSION['member']->id;
		// IG + 代幣派彩
		$d['summary'] = 'IG' . $transaction_category['tokenpay'];
		// 稽核方式
		$d['auditmode'] = $auditmode_select['ig'];
		// 稽核金額 -- 派彩無須稽核
		$d['auditmodeamount'] = 0;
		// IG 取回的餘額為真錢
		$d['realcash'] = 2;
		// 交易類別 IG + $transaction_category['tokenpay']
		$d['transaction_category'] = 'tokenpay';
		// 變化的餘額
		$d['balance'] = abs($ig2gpk_balance);
		// var_dump($d);

		// 操作 root_member_wallets DB, 把 ig_balance 設為 0 , 把 gtoken_lock = null, 把 gtoken_balance = $d['deposit']
		// 錢包存入 餘額 , 把 ig_balance 扣除全部表示支出(投注).
		$ig2gpk_transaction_sql = $ig2gpk_transaction_sql . "
      UPDATE root_member_wallets SET changetime = NOW(), gtoken_balance = " . $d['deposit'] . ", gtoken_lock = NULL,casino_accounts= jsonb_set(casino_accounts,'{\"IG\",\"balance\"}','0') WHERE id = '" . $d['destination_transfer_id'] . "'; ";
		// 目的帳號上的註記
		$d['destination_notes'] = '(會員收到IG派彩' . $ig2gpk_balance . ')';
		// 針對目的會員的存簿寫入，
		$ig2gpk_transaction_sql = $ig2gpk_transaction_sql .
			'INSERT INTO "root_member_gtokenpassbook" ("transaction_time", "deposit", "withdrawal", "system_note", "member_id", "currency", "summary", "source_transferaccount", "auditmode", "auditmodeamount", "realcash", "destination_transferaccount", "transaction_category", "balance")' .
			"VALUES ('now()', '" . $d['deposit'] . "', '" . $d['withdrawal'] . "', '" . $d['destination_notes'] . "', '" . $d['member_id'] . "', 'CNY', '" . $d['summary'] . "', '" . $d['destination_transferaccount'] . "', '" . $d['auditmode'] . "', '" . $d['auditmodeamount'] . "', '" . $d['realcash'] . "', " .
			"'" . $d['destination_transferaccount'] . "','" . $d['transaction_category'] . "', (SELECT gtoken_balance FROM root_member_wallets WHERE id = " . $d['destination_transfer_id'] . ") );";

		// 針對來源出納的存簿寫入
		$ig2gpk_transaction_sql = $ig2gpk_transaction_sql . "
      UPDATE root_member_wallets SET changetime = NOW(), gtoken_balance = (gtoken_balance + " . $d['balance'] .") WHERE id = '" . $d['source_transfer_id'] . "'; ";
		// 來源帳號上的註記
		$d['source_notes'] = '(出納帳號 ' . $d['source_transferaccount'] . ' 從會員 ' . $d['destination_transferaccount'] . ' 取回派彩餘額)';
		$ig2gpk_transaction_sql = $ig2gpk_transaction_sql .
			'INSERT INTO "root_member_gtokenpassbook" ("transaction_time", "deposit", "withdrawal", "system_note", "member_id", "currency", "summary", "source_transferaccount", "auditmode", "auditmodeamount", "realcash", "destination_transferaccount", "transaction_category", "balance")' .
			"VALUES ('now()', '" . $d['balance'] . "', '0', '" . $d['source_notes'] . "', '" . $d['member_id'] . "', 'CNY', '" . $d['summary'] . "', '" . $d['source_transferaccount'] . "', '" . $d['auditmode'] . "', '" . $d['auditmodeamount'] . "', '" . $d['realcash'] . "', " .
			"'" . $d['source_transferaccount'] . "','" . $d['transaction_category'] . "', (SELECT gtoken_balance FROM root_member_wallets WHERE id = " . $d['source_transfer_id'] . "));";

		// commit 提交
		$ig2gpk_transaction_sql = $ig2gpk_transaction_sql . 'COMMIT;';
		if ($debug == 1) {
			echo '<p>SQL=' . $ig2gpk_transaction_sql . '</p>';
		}

		// 執行 transaction sql
		$ig2gpk_transaction_result = runSQLtransactions($ig2gpk_transaction_sql);
		if ($ig2gpk_transaction_result) {
			$logger = '从IG帐号' . $_SESSION['member']->ig_account . '取回余额到代币，统计后收入=' . $ig_balance_api . '，支出=' . $ig_balance_db . '，共计派彩=' . $ig2gpk_balance;
			$r['ErrorCode'] = 1;
			$r['ErrorMessage'] = $logger;
			memberlog2db($_SESSION['member']->account, 'iggame', 'info', "$logger");
			member_casino_transferrecords('IG', 'lobby', $ig_balance_api, $logger, 'info');
			// echo "<p> $logger </p>";
		} else {
			//5.1 ~ 5.3 必須一定要全部成功，才算成功。如果 5.1 成功後，但 5.2,5.3 失敗的話，紀錄為 ERROR LOG，需要通知管理員處理。(message system)
			$logger = '从IG帐号' . $_SESSION['member']->ig_account . '取回余额到代币，统计后收入=' . $ig_balance_api . '，支出=' . $ig_balance_db . '，共计派彩=' . $ig2gpk_balance;
			$logger = $logger . '但资料库处理错误，请通知客服人员处理。';
			$r['ErrorCode'] = 406;
			$r['ErrorMessage'] = $logger;
			memberlog2db($_SESSION['member']->account, 'iggame', 'error', "$logger");
			member_casino_transferrecords('IG', 'lobby', $ig_balance_api, $logger, 'warning');
			// echo "<p> $logger </p>";
		}
		// var_dump($r);
		// ---------------------------------------------------------------------------------
	} else {
		// 不可能
		$logger = '不可能发生';
		$r['ErrorCode'] = 500;
		$r['ErrorMessage'] = $logger;
		memberlog2db($_SESSION['member']->account, 'iggame', 'error', "$logger");
		echo "<p> $logger </p>";
	}

	return ($r);
}
// ---------------------------------------------------------------------------------
// 針對 db 的處理函式，只針對此功能有用。 END
// ---------------------------------------------------------------------------------

// ---------------------------------------------------------------------------------
// 取回 IG Casino 的餘額 -- retrieve_ig_casino_balance
// 不能單獨使用，需要搭配 db_ig2gpk_balance 使用
// ---------------------------------------------------------------------------
// Features:
// Usage:
// Input:
// Return:
// ---------------------------------------------------------------------------
function retrieve_ig_casino_balance($member_id, $debug = 0) {
	//$debug=1;
	global $gtoken_cashier_account, $config, $IG_CONFIG;
	// $member_id
	// $member_id = $_SESSION['member']->id;
	$r = [];

	// 判斷會員是否 status 是否被鎖定了!!
	$member_sql = "SELECT * FROM root_member WHERE id = '" . $member_id . "' AND status = '1';";
	$member_result = runSQLall($member_sql);

	// 先取得當下的  member_wallets 變數資料,等等 sql 更新後. 就會消失了。
	$wallets_sql = "SELECT gtoken_balance,gtoken_lock,
	        casino_accounts->'IG'->>'account' as ig_account,
	        casino_accounts->'IG'->>'password' as ig_password,
	        casino_accounts->'IG'->>'balance' as ig_balance FROM root_member_wallets WHERE id = '" . $member_id . "';";
	$wallets_result = runSQLall($wallets_sql);
	if ($debug == 1) {
		var_dump($wallets_sql);
		var_dump($wallets_result);
	}

	// 1. 查詢 DB 的 gtoken_lock  是否有紀錄在 IG 帳戶，NULL 沒有紀錄的話表示沒有餘額在 IG 帳戶。(已經取回了，代幣一次只能對應一個娛樂城)
	// 2. AND 當 DB 有 ig_balance 的時候才動作, 如果沒有則結束。表示 db 帳號資料有問題。
	// -----------------------------------------------------------------------------------
	if ($member_result[0] == 1 AND $wallets_result[0] == 1 AND $wallets_result[1]->ig_account != NULL AND $wallets_result[1]->gtoken_lock == 'IG') {

		// 4. Y , gtoken 紀錄為 IG , API 檢查 IG 的餘額有多少
		// -----------------------------------------------------------------------------------
		// $delimitedAccountNumbers = $_SESSION['member']->ig_account;

		$delimitedAccountNumbers = $wallets_result[1]->ig_account;

		$IG_API_data = array(
			'username' => $delimitedAccountNumbers,
			'password' => md5($_SESSION['member']->ig_password)
		);

		if ($debug == 1) {
			var_dump($IG_API_data);
		}

		$IG_API_result = ig_gpk_api('GetBalance', $debug, $IG_API_data, 'trade');
		if ($IG_API_result['errorcode'] == 0 AND isset($IG_API_result['Result']->errorCode) AND $IG_API_result['Result']->errorCode == 0) {
			// 查詢餘額動作，成立後執行. 失敗的話結束，可能網路有問題。
			//echo '4. 查詢餘額動作，成立後執行. 失敗的話結束，可能網路有問題。';
			//var_dump($IG_API_result);
			// 取得的 IG API 餘額 , 保留小數第二位 round( $x, 2);
			$floor2dec = function ($value, $dec = 2) {
				// 取到小數幾位，預設兩位
				$shift = pow(10, $dec);
				return floor($value * $shift) / $shift;
			};
			$ig_balance_api = $floor2dec($IG_API_result['Result']->params->balance, 2);
			$logger = 'IG API 查询余额为' . $IG_API_result['Result']->params->balance . '操作的余额为' . $ig_balance_api;
			$r['code'] = 1;
			$r['messages'] = $logger;
			// echo "<p> $logger </p>";
			// -----------------------------------------------------------------------------------

			// 5. 承接 4 ,如果 IG餘額 > 0
			// -----------------------------------------------------------------------------------
			if ($ig_balance_api > 0) {
				//5.1 執行 IG API 取回 IG 餘額 ，到 IG 的出納帳戶(API操作) , 成功才執行 5.2,5.3
				// 動作： Withdraw 帳戶取款

				// IG 專用：交易唯一號; 這裡使用 timestamp 並與 logger 一同寫入
				// $ref_number = time();(fix by Ian : 改成統一ref格式)
				$ref_number = 'ig0Withdrawal0'.date("Ymdhis");

				$IG_API_data = [
					'username' => (string) $wallets_result[1]->ig_account,
					'password' => (string) md5($wallets_result[1]->ig_password),
					'ref' => (string) $ref_number,
					'desc' => "api 取款 {$wallets_result[1]->ig_account} 金额 $ig_balance_api",
					'amount' => (string) $ig_balance_api
				];

				if ($debug == 1) {
					echo '5.1 執行 IG API 取回 IG 餘額 ，到 IG 的出納帳戶(API操作) , 成功才執行 5.2,5.3';
					var_dump($IG_API_data);
				}

				$IG_API_result = ig_gpk_api('Withdraw', $debug, $IG_API_data, 'trade');

				if ($debug == 1) {
					var_dump($IG_API_result);
				}

				if ($IG_API_result['errorcode'] == 0 AND isset($IG_API_result['Result']->errorCode) AND $IG_API_result['Result']->errorCode == 0) {
					// 取回IG餘額成功
					$logger = 'IG API 从帐号' . $wallets_result[1]->ig_account . '取款余额' . $ig_balance_api . '成功。交易编号(时间戳)为' . $ref_number . '，IG API logid为' . $IG_API_result['Result']->logId;

					$r['code'] = 100;
					$r['messages'] = $logger;
					memberlog2db($_SESSION['member']->account, 'iggame', 'info', "$logger");
					member_casino_transferrecords('IG', 'lobby', $ig_balance_api, $logger, 'success', $ref_number, 1);

					if ($debug == 1) {
						echo "<p> $logger </p>";
						var_dump($IG_API_result);
					}
					// 先取得當下的  wallets 變數資料,等等 sql 更新後. 就會消失了。
					// -----------------------------------------------------------------------------------
					$wallets_sql = "SELECT casino_accounts->'IG'->>'balance' as ig_balance FROM root_member_wallets WHERE id = '" . $_SESSION['member']->id . "';";
					//var_dump($wallets_sql);
					$wallets_result = runSQLall($wallets_sql);
					//var_dump($wallets_result);
					// 在剛取出的 wallets 資料庫中的餘額(支出)
					$ig_balance_db = round($wallets_result[1]->ig_balance, 2);
					// 派彩 = 娛樂城餘額 - 本地端IG支出餘額
					$ig2gpk_balance = round(($ig_balance_api - $ig_balance_db), 2);
					$r['balance'] = $ig2gpk_balance;
					// -----------------------------------------------------------------------------------

					// 處理 DB 的轉帳問題 -- 5.2 and 5.3
					$db_ig2gpk_balance_result = db_ig2gpk_balance($gtoken_cashier_account, $ig_balance_api, $ig2gpk_balance, $ig_balance_db);
					if ($db_ig2gpk_balance_result['ErrorCode'] == 1) {
						$r['code'] = 1;
						$r['messages'] = $db_ig2gpk_balance_result['ErrorMessage'];
						$logger = $r['messages'];
						memberlog2db($_SESSION['member']->account, 'ig2gpk', 'info', "$logger");
					} else {
						$r['code'] = 523;
						$r['messages'] = $db_ig2gpk_balance_result['ErrorMessage'];
						$logger = $r['messages'];
						memberlog2db($_SESSION['member']->account, 'ig2gpk', 'error', "$logger");
					}

					if ($debug == 1) {
						echo '處理 DB 的轉帳問題 -- 5.2 and 5.3';
						var_dump($db_ig2gpk_balance_result);
					}
				} else {
					//5.1 執行 IG API 取回 IG 餘額 ，到 IG 的出納帳戶(API操作) , 成功才執行 5.2,5.3
					$logger = 'IG API 从帐号' . $_SESSION['member']->ig_account . '取款余额' . $ig_balance_api . '失败';
					$r['code'] = 405;
					$r['messages'] = $logger;
					memberlog2db($_SESSION['member']->account, 'iggame', 'error', "$logger");
					member_casino_transferrecords('IG', 'lobby', '0', $logger, 'fail');

					if ($debug == 1) {
						echo "5.1 執行 IG API 取回 IG 餘額 ，到 IG 的出納帳戶(API操作) , 成功才執行 5.2,5.3";
						echo "<p> $logger </p>";
						var_dump($r);
					}
				}

			} elseif ($ig_balance_api == 0) {
				$logger = 'IG余额 = 0 ，IG没有余额，无法取回任何的余额，将余额转回平台。';
				$r['code'] = 406;
				$r['messages'] = $logger;
				memberlog2db($_SESSION['member']->account, 'iggame', 'info', "$logger");
				member_casino_transferrecords('IG', 'lobby', '0', $logger, 'success');

				// 先取得當下的  wallets 變數資料,等等 sql 更新後. 就會消失了。
				// -----------------------------------------------------------------------------------
				$wallets_sql = "SELECT casino_accounts->'IG'->>'balance' as ig_balance FROM root_member_wallets WHERE id = '" . $_SESSION['member']->id . "';";
				//var_dump($wallets_sql);
				$wallets_result = runSQLall($wallets_sql);
				//var_dump($wallets_result);
				// 在剛取出的 wallets 資料庫中的餘額(支出)
				$ig_balance_db = round($wallets_result[1]->ig_balance, 2);
				// 派彩 = 娛樂城餘額 - 本地端IG支出餘額
				$ig2gpk_balance = round(($ig_balance_api - $ig_balance_db), 2);
				$r['balance'] = $ig2gpk_balance;
				// -----------------------------------------------------------------------------------

				// 處理 DB 的轉帳問題 -- 5.2 and 5.3
				$db_ig2gpk_balance_result = db_ig2gpk_balance($gtoken_cashier_account, $ig_balance_api, $ig2gpk_balance, $ig_balance_db);
				if ($db_ig2gpk_balance_result['ErrorCode'] == 1) {
					$r['code'] = 1;
					$r['messages'] = $db_ig2gpk_balance_result['ErrorMessage'];
					$logger = $r['messages'];
					memberlog2db($_SESSION['member']->account, 'ig2gpk', 'info', "$logger");
				} else {
					$r['code'] = 523;
					$r['messages'] = $db_ig2gpk_balance_result['ErrorMessage'];
					$logger = $r['messages'];
					memberlog2db($_SESSION['member']->account, 'ig2gpk', 'error', "$logger");
				}

				if ($debug == 1) {
					echo '處理 DB 的轉帳問題 -- 5.2 and 5.3';
					var_dump($db_ig2gpk_balance_result);
				}

			} else {
				// IG餘額 < 0 , 不可能發生
				$logger = 'IG余额 < 1 ，不可能发生。';
				$r['code'] = 404;
				$r['messages'] = $logger;
			}
			// -----------------------------------------------------------------------------------
		} else {
			// 4. Y , gtoken 紀錄為 IG , API 檢查 IG 的餘額有多少
			$logger = 'IG API 查询余额失败，系统维护中请晚点再试。';
			$r['code'] = 403;
			$r['messages'] = $logger;
			member_casino_transferrecords('IG', 'lobby', '0', $logger, 'fail');
			if ($debug == 1) {
				var_dump($IG_API_result);
			}
		}
		// -----------------------------------------------------------------------------------
	} else {
		// 1. 查詢 session 的 gtoken_lock  是否有紀錄在 IG 帳戶，NULL 沒有紀錄的話表示沒有餘額在 IG 帳戶。
		// 2. AND 當 session 有 ig_balance 的時候才動作, 如果沒有則結束。表示 db 帳號資料有問題。
		$logger = '没有余额在 IG 帐户 OR DB 帐号资料有问题 ';
		$r['code'] = 401;
		$r['messages'] = $logger;
		member_casino_transferrecords('IG', 'lobby', '0', $logger, 'fail');
	}

	if ($debug == 1) {
		echo "<p> $logger </p>";
		var_dump($r);
	}
	if ($r['code'] == 1) {
		unset($_SESSION['wallet_transfer']);
	}

	return ($r);
}
// -----------------------------------------------------------------------------------

// ---------------------------------------------
// 產生可以連到 IG 彩票 game 的 url 位址
// ---------------------------------------------------------------------------
// Features:
// Usage:
// Input:
// Return:
// ---------------------------------------------
function ig_gameurl($ig_account, $ig_password, $ig_gameid, $debug = 0) {

	global $config, $IG_CONFIG;
	// 取得game的資料
	$gamedata_sql = 'SELECT * FROM "casino_gameslist" WHERE "id" = \'' . $ig_gameid . '\';';
	$gamedata_result = runSQLall($gamedata_sql);

	$ig_gamecode = $gamedata_result['1']->gameid;
	// $ig_moduleid = $gamedata_result['1']->moduleid;
	// $ig_clientid = $gamedata_result['1']->clientid;
	$game_category = $gamedata_result['1']->category;
	$gameplatform = $gamedata_result['1']->gameplatform;
	$gamename = $gamedata_result['1']->gamename;
	//正式環境用 $gamemode = 'false';
	// $gamemode = 'true';

	// IG 透過 gametype 來決定連到哪個彩票 API
	$url_type = strtolower($gamedata_result['1']->gametype);

	switch ($url_type) {
		case 'lotto':
			$IG_API_data = [
				'username' 	=> $ig_account,
				'password' 	=> $ig_password,
				'currency' 	=> ($IG_CONFIG['mode'] == 'test') ? 'TEST' : 'CNY', // 測試線, 正式線用 CNY
				// 'nickname' 	=> 'Dev',
				// 'language' 	=> 'HK',
				'line'     	=> 1,
				'gameType' 	=> strtoupper($url_type),
				'lottoTray'	=> chr(mt_rand(1, 4) + 64), // A~D
				'userCode' 	=> 'testcode'
			];

			break;

		case 'lottery':
			$IG_API_data = [
				'username' 	=> $ig_account,
				'password' 	=> $ig_password,
				'currency' 	=> ($IG_CONFIG['mode'] == 'test') ? 'TEST' : 'CNY', // 測試線, 正式線用 CNY
				// 'nickname' 	=> 'Dev',
				// 'language' 	=> 'HK',
				'line'     	=> 1,
				'gameType' 	=> strtoupper($url_type),
				'lotteryTray'	=> chr(mt_rand(1, 3) + 64), // A~C
				// 判斷網站型態；只有時時彩有兩種 UI
				'lotteryPage'	=> (string) ($gamedata_result['1']->gameid - 1), // lotto 0~64 種bet  lottery 0~57 種game
				'lotteryType'	=> $_SESSION['site_mode'] == 'mobile' ? 'MP' : 'PC',
				'mobileVersion' => 'new',
				'userCode' 	=> 'testcode'
			];

			break;

		default:
			return $re = '';
			break;
	}

	$IG_API_result = ig_gpk_api('Login', $debug, $IG_API_data, $url_type);
	if($debug) var_dump($IG_API_result);

	if ($IG_API_result['errorcode'] == 0 AND isset($IG_API_result['Result']->errorCode) AND $IG_API_result['Result']->errorCode == 0) {
		$re = $IG_API_result['Result']->params->link;
		$logger = '会员 '.$_SESSION['member']->account.' 前往游戏 '.$gamename.'.';
		member_casino_transferrecords('lobby', 'IG', '0', $logger, 'info');
	}else{
		$re = '';
		$logger = '会员 '.$_SESSION['member']->account.' 前往游戏 '.$gamename.',登入失败：'.$IG_API_result['Result']->errorMessage;
		member_casino_transferrecords('lobby', 'IG', '0', $logger, 'fail');
	}
	if($debug) var_dump($logger);

	return $re;
}
// ---------------------------------------------
// 產生可以連到 IG game 的 url 位址 END
// ---------------------------------------------


?>
