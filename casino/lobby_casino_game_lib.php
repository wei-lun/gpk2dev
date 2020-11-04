<?php
// ----------------------------------------------------------------------------
// Features:	娛樂城遊戲通用函式庫
// File Name:	lobby_casino_game_lib.php
// Author:		Letter
// Related:
// Log:
// 2019.09.25 新建
/*
// function 索引及說明：
// -------------------
0. 輸入資料格式：
$GT_API_data = array(
  'account' =>  玩家帳號,
  'password' => 玩家密碼,
  'nickname' => 玩家別名,
  'gamehall' => 遊戲廠商id,
  'amount' => 金額,
  'transaction_id' => 交易代碼,
  'sign' => 簽章
);
1. GT API 文件函式及用法 sample , 操作 GT API (by kapi)
gt_gpk_api($method, $debug=0, $GT_API_data)

2. 依據使用者帳號資訊，檢查遠端 娛樂城 的帳號是否存在，不存在就建立
create_casino_account()

3.將傳入的 member id 值，取得他的 DB GTOKEN 餘額並全部傳送到 CASINO 上
把本地端的資料庫 root_member_wallets 的 GTOKEN_LOCK設定為 CASINO 餘額儲存在 CASINO_balance 上面
transferout_gtoken_to_casino_balance($member_id, $debug = 0)

5. 取回 Casino 的餘額，並檢查上次離開時和目前的差額得出派彩金額，紀錄存簿。 v2017.1.2
db_retrieve_casino_balance($gtoken_cashier_account, $gt_api_balance, $payout_balance, $casino_balance_db, $debug = 0)

6. 取回 Casino 的餘額 -- retrieve_casino_balance
retrieve_casino_balance($member_id, $debug=0)

7. 產生可以連到 娛樂城 game 的 url 位址
getGameUrl($casino_account, $casino_password, $casino_gameid, $debug = 0)

8. 查询帐号资讯
GetCasinoAccountInfo($casino_account, $debug = 0)

*/

// GAPI資料欄位
$gtapi_column = [];
$gtapi_column['account'] = strtolower($casinoid) . '_account';
$gtapi_column['password'] = strtolower($casinoid) . '_password';
$gtapi_column['balance'] = strtolower($casinoid) . '_balance';
$gtapi_column['casinoid'] = $casinoid;
$gtapi_column['gamehall'] = strtolower($casinoid);

// 狀態碼
$statusCodes = [
	'success' => 1,
	'api_withdrawal_success' => 100
];

// 錯誤碼
$errorCode = [
	'does_not_have_casino_account' => 12,
	'transfer_success' => 1,
	'database_transfer_error' => 14,
	'transfer_pending' => 19,
	'transfer_fail' => 13,
	'duplicate_account' => 11,
	'does_not_have_member_account' => 0
];

/**
 *  sign key generator
 *
 * @param array $data   傳遞的參數陣列，若沒傳遞參數則放空陣列
 * @param mixed $apiKey 代理商的API KEY
 *
 * @return string 加密鍵值
 */
function generateSign($data, $apiKey)
{
	ksort($data);
	return md5(http_build_query($data) . $apiKey);
}


/**
 *  使用 GT API 方法
 *
 * @param string $method 呼叫方法
 * @param int $debug 除錯模式，預設 0 為關閉
 * @param mixed  $GT_API_data GTAPI 參數
 *
 * @return array|string 回覆資料
 */
function gt_api($method, $debug = 0, $GT_API_data)
{
	// 設定 socket_timeout , http://php.net/manual/en/soapclient.soapclient.php
	ini_set('default_socket_timeout', 5);

	global $GPKAPI_CONFIG;
	global $system_mode;
	global $config;

	// Setting restful url
	$url = $GPKAPI_CONFIG['url'];
	$apiKey = $config['gpk2_apikey'];
	$token = $config['gpk2_token'];

	if ($method == 'AddAccount') {
		$url .= '/api/player';
		$apimethod = 'post';
		$GT_API_data['sign'] = generateSign($GT_API_data, $apiKey);
	} elseif ($method == 'Deposit') {
		$url .= '/api/transaction/deposit';
		$apimethod = 'post';
		$GT_API_data['sign'] = generateSign($GT_API_data, $apiKey);
	} elseif ($method == 'Withdrawal') {
		$url .= '/api/transaction/withdraw';
		$apimethod = 'post';
		$GT_API_data['sign'] = generateSign($GT_API_data, $apiKey);
	} elseif ($method == 'GetAccountDetails') {
		$GT_API_data['sign'] = generateSign($GT_API_data, $apiKey);
		$uri = http_build_query($GT_API_data);
		$url .= '/api/player/wallet?' . $uri;
		$apimethod = 'get';
	} elseif ($method == 'CheckUser') {
		$GT_API_data['sign'] = generateSign([], $apiKey);
		$url .= '/api/player/check/' . $GT_API_data['account'] . '?sign=' . $GT_API_data['sign'];;
		$apimethod = 'get';
	} elseif ($method == 'ChangePWD') {
		$url .= '/api/player/pwd';
		$apimethod = 'post';
		$GT_API_data['sign'] = generateSign($GT_API_data, $apiKey);
	} elseif ($method == 'KickUser') {
		$url .= '/api/player/logout';
		$apimethod = 'post';
		$GT_API_data['sign'] = generateSign($GT_API_data, $apiKey);
	} elseif ($method == 'GetGameUrl') {
		$GT_API_data['sign'] = generateSign($GT_API_data, $apiKey);
		$uri = http_build_query($GT_API_data);
		$url .= '/api/game/game-link?' . $uri;
		$apimethod = 'get';
	} elseif ($method == 'GameHallLists') {
		$GT_API_data['sign'] = generateSign($GT_API_data, $apiKey);
		$url .= '/api/game/halls';
		$apimethod = 'get';
	} elseif ($method == 'GamenameLists') {
		$GT_API_data['sign'] = generateSign($GT_API_data, $apiKey);
		$uri = http_build_query($GT_API_data);
		$url .= '/api/game/game-list?' . $uri;
		$apimethod = 'get';
	} else {
		$ret = 'nan';
	}

	if (isset($GT_API_data)) {
		$ret = array();
		try {
			//HTTP headers
			// $headertype = 'application/json';
			// $headertype = 'application/x-www-form-urlencoded';
			$headers = ["Content-Type: multipart/form-data", "Authorization: $token"];

			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			if ($apimethod == 'post') {
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $GT_API_data);
			}

			$response = curl_exec($ch);

			if ($debug == 1) {
				echo $method . "\n";
				echo curl_error($ch);
				var_dump($GT_API_data);
				var_dump($response);
			}

			if ($response) {
				// Then, after your curl_exec call , 移除 http head 剩下 body
				// $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
				// $header = substr($response, 0, $header_size);
				// $body = substr($response, $header_size);
				$body = json_decode($response);

				if ($debug == 1) {
					var_dump($body);
				}

				// 如果 curl 讀取投注紀錄成功的話
				if (isset($body->data) and $body->status->code == 0) {
					// curl 正確
					$ret['curl_status'] = 0;
					// 計算取得的紀錄數量有多少
					$ret['count'] = (is_array($body->data)) ? count($body->data) : '0';
					// 取得紀錄沒有錯誤
					$ret['errorcode'] = 0;
					// 存下 body
					$ret['Status'] = $body->status->code;
					$ret['Result'] = $body->data;
				} else {
					// curl 正確
					$ret['curl_status'] = 0;
					// 計算取得的紀錄數量有多少
					$ret['count'] = (is_array($body)) ? count($body) : '0';
					// 取得紀錄沒有錯誤
					$ret['errorcode'] = $body->status->code;
					// 存下 body
					$ret['Status'] = $body->status->code;
					$ret['Result'] = $body->status->message;
				}
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
		$ret = 'NAN';
	}

	return ($ret);
}


/**
 *  依據使用者帳號資訊，檢查遠端 娛樂城 的帳號是否存在，不存在就建立
 *
 * @param int $debug 除錯模式，預設 0 為關閉
 *
 * @return array 帳號資訊
 */
function create_casino_account($debug = 0)
{
	// 變數
	global $config;
	global $gtapi_column;
	global $tr;

	// 回傳的變數
	$r = array();

	// 需要有 session 才可以登入, 且帳號只有 A and R 權限才可以建立 娛樂城 帳號, 不允許管理員建立帳號進入遊戲。
	if (isset($_SESSION['member']) and
		($_SESSION['member']->therole == 'A' or $_SESSION['member']->therole == 'M') and
		($config['businessDemo'] == 0 or in_array($gtapi_column['casinoid'], $config['businessDemo_skipCasino']))) {
		// 當 $_SESSION['wallet']->transfer_flag 存在時，不可以執行，因為有其他程序再記憶體中執行。
		if (!isset($_SESSION['wallet_transfer'])) {
			// 透過這個旗標控制不能有第二個執行緒進入。
			$_SESSION['wallet_transfer'] = 'create_casino_account ' . $_SESSION['member']->account;
			// 使用session時，若先前的頁面尚未執行完畢，預設session會被鎖住。
			// 此時，若執行另外一個也有使用session的頁面，則須等前一個頁面執行完畢，才能再執行。
			// 要避免等前一個頁面執行完畢，才能執行下一個頁面的情況，
			// 可以使用 session_write_close() ，告之不會再對session做寫入的動作，
			// 這樣其他頁面就不會等此頁面執行完才能再執行。
			// session_write_close() ;

			if (isset($_SESSION['member'])) {
				// 如果沒有帳號的話, 馬上建立一個。
				if (!isset($_SESSION['member']->{$gtapi_column['account']}) OR $_SESSION['member']->{$gtapi_column['account']} == '') {
					$firstname = $_SESSION['member']->account;
					$lastname = $config['projectid'];
					$nickName = $lastname . '_' . $firstname;
					// 建立代碼時，以系統的代碼為前三碼. 後面以會員的 ID 編號為對應，補滿 10 碼;
					$accnumber_id = 20000000000 + $_SESSION['member']->id;
					$casino_account = $lastname . $accnumber_id;
					// 密碼為 6-12 碼數字或英文，設定為 8 碼亂數。
					$casino_account_password = mt_rand(10000000, 99999999) . $lastname;
					// 動作： AddAccount  建立帳戶 , accountNumber left empty to be generated automatically
					// firstname 設定為系統帳號, 對照方便
					// lastname 為網站代碼, 對帳容易.
					$GT_API_data = array(
						'account' => $casino_account
					);
					// 確認是否建立過帳號
					$GT_API_CHK_result = gt_api('CheckUser', $debug, $GT_API_data);

					// 確認沒有娛樂城帳號
					if (!$GT_API_CHK_result['Result']) {
						$GT_API_data = array(
							'account' => $casino_account,
							'password' => $casino_account_password,
							'nickname' => $nickName
						);
						// 建立娛樂城帳號
						$GT_API_result = gt_api('AddAccount', $debug, $GT_API_data);
					}

					// 有娛樂城帳號 或 建立娛樂城帳號成功
					if ($GT_API_CHK_result['Result'] OR
						($GT_API_result['errorcode'] == 0 and $GT_API_result['Status'] == 0 and $GT_API_result['count'] >= 0)) {
						// 有娛樂城帳號
						if ($GT_API_CHK_result['Result']) {
							$member_sql = "SELECT casino_accounts->'". $gtapi_column['casinoid'] ."'->>'account' as ". $gtapi_column['casinoid'] ."_account,
                                    casino_accounts->'". $gtapi_column['casinoid'] ."'->>'password' as ". $gtapi_column['casinoid'] ."_password FROM root_member_wallets WHERE id = '{$_SESSION['member']->id}';";
							$r = runSQLall($member_sql);

							// 取得資料庫會員儲存娛樂城帳密(GAPI)
							if ($r[0] == '1') {
								if (getCasinoAccount($gtapi_column['casinoid'], $r['1']) == '') {
									$GT_API_data = array(
										'account' => $casino_account,
										'password' => $casino_account_password
									);
									$GT_API_result = gt_api('ChangePWD', $debug, $GT_API_data);
								} else {
									$casino_account = getCasinoAccount($gtapi_column['casinoid'], $r['1']);
									$casino_account_password = getCasinoPassword($gtapi_column['casinoid'], $r['1']);
								}
								$GT_API_result = '已建立 GT API 用帐号！';
								echo $GT_API_result;
							} else {
								$logger = $tr[$gtapi_column['casinoid'] . ' Casino'] . '帐号建立失败,资料库操作错误';
								$r['ErrorCode'] = 10;
								$r['ErrorMessage'] = $logger;
								memberlog2db($_SESSION['member']->account, 'gtapi', 'error', $logger);
								return ($r);
							}
						}

						$logger = $gtapi_column['casinoid'] . ' Casino API 帐号建立成功';
						$r['ErrorCode'] = 10;
						$r['ErrorMessage'] = $logger;

						// 取得建立好的帳號資訊
						$casino_accountnumber_api = $casino_account;
						$casino_password_api = $casino_account_password;
						$member_wallet_id = $_SESSION['member']->id;

						// 紀錄建立 API 的帳號相關資訊
						$logger = $gtapi_column['casinoid'] . ' Casino API 帐号建立成功 AccountNumber=' . $casino_accountnumber_api . ',  PinCode=' . $casino_password_api . ', ' . json_encode($GT_API_result);
						memberlog2db($_SESSION['member']->account, $gtapi_column['casinoid'] . ' Casino API', 'notice', "$logger");
						member_casino_transferrecords('lobby', $gtapi_column['casinoid'], '0', $logger, 'success');

						// 更新錢包的帳號密碼
						$update_wattet_sql = "UPDATE root_member_wallets SET changetime = now(), casino_accounts= casino_accounts || '{\"" . $gtapi_column['casinoid'] . "\":{\"account\":\"$casino_accountnumber_api\", \"password\":\"$casino_password_api\", \"balance\":\"0.0\"}}' WHERE id = '$member_wallet_id';";
						$update_wattet_sql_result = runSQL($update_wattet_sql);
						if ($update_wattet_sql_result == 1) {
							// 成功的話，更新 session 的 娛樂城 account and password 資訊。
							$_SESSION['member']->{$gtapi_column['account']} = $casino_accountnumber_api;
							$_SESSION['member']->{$gtapi_column['password']} = $casino_password_api;

							// 更新 wallet 成功
							$logger = $gtapi_column['casinoid'] . ' Casino 帐号 ' . $casino_accountnumber_api . ' 密码： ' . $casino_password_api . ' 写入 DB root_member_wallet 成功';
							$r['ErrorCode'] = 0;
							$r['ErrorMessage'] = $logger;
						} else {
							// 更新 wallet 失敗
							$logger = $gtapi_column['casinoid'] . ' Casino 帐号 ' . $casino_accountnumber_api . ' 密码： ' . $casino_password_api . ' 写入 DB root_member_wallet 失败ˋ';
							$r['ErrorCode'] = 1;
							$r['ErrorMessage'] = $logger;
						}
					} else {
						// 沒有娛樂城帳號 或 建立娛樂城帳號失敗
						$logger = $tr[$gtapi_column['casinoid'] . ' Casino'] . 'API 帐号建立失败' . $GT_API_result['Result'];
						$r['ErrorCode'] = 21;
						$r['ErrorMessage'] = $logger;
						member_casino_transferrecords('lobby', $gtapi_column['casinoid'], '0', $logger, 'fail');
					}
				} else {
					// 有娛樂城帳號存在
					// 查詢 目前使用者的 娛樂城 餘額
					$delimitedAccountNumbers = $_SESSION['member']->{$gtapi_column['account']};
					$GT_API_data = array(
						'account' => $delimitedAccountNumbers,
						'gamehall' => $gtapi_column['gamehall'],
					);

					// 確認使用者帳號明細資料
					$GT_API_result = gt_api('GetAccountDetails', $debug, $GT_API_data);
					if ($GT_API_result['errorcode'] == 0 and $GT_API_result['Status'] == 0 and $GT_API_result['count'] >= 0) {
						// 查詢餘額動作, 使用者存在
						$logger = '会员帐号 ' . $delimitedAccountNumbers . '，余额 ' . $GT_API_result['Result']->balance;
						$r['ErrorCode'] = 20;
						$r['ErrorMessage'] = $logger;
					} else {
						$logger = '会员帐号 ' . $delimitedAccountNumbers . ' 不存在';
						$r['ErrorCode'] = 14;
						$r['ErrorMessage'] = $logger;
					}
					member_casino_transferrecords('lobby', $gtapi_column['casinoid'], '0', $logger, 'info');
				}
			} else {
				$logger = '会员需要登入才可以建立 ' . $gtapi_column['casinoid'] . ' Casino 帐号';
				$r['ErrorCode'] = 22;
				$r['ErrorMessage'] = $logger;
			}

			// 執行完成後，把 $_SESSION['wallet_transfer'] 變數清除, 其他程式才可以進入。
			unset($_SESSION['wallet_transfer']);
		} else {
			$logger = '同使用者，API 动作产生，不可以执行。' . $_SESSION['wallet_transfer'];
			$r['ErrorCode'] = 9;
			$r['ErrorMessage'] = $logger;
		}
	} else {
		// 條件不符合, 不建立帳號
		$logger = '需要有 session 才可以操作, 且帐号只有 A and M 权限才可以建立 ' . $gtapi_column['casinoid'] . ' Casino 帐号, 不允许管理员建立帐号进入游戏';
		$r['ErrorCode'] = 99;
		$r['ErrorMessage'] = $logger;
	}

	return ($r);
}


/**
 *  將傳入的 member id 值，取得他的 DB GTOKEN 餘額並全部傳送到  要玩的娛樂城 上
 *  把本地端的資料庫 root_member_wallets 的 GTOKEN_LOCK設定為 要玩的娛樂城，餘額儲存在 casino_balance 上面
 *
 * @param mixed $member_id 會員 ID
 * @param int $debug 除錯模式，預設 0 為關閉
 *
 * @return array 遊戲幣轉帳訊息
 */
function transferout_gtoken_to_casino_balance($member_id, $debug = 0)
{
	global $config;
	global $gtapi_column;
	global $errorCode;

	// 驗證並取得帳戶資料
	$member_sql = <<<SQL
      SELECT gtoken_balance,gtoken_lock,
              casino_accounts->'{$gtapi_column['casinoid']}'->>'account' as casino_account,
              casino_accounts->'{$gtapi_column['casinoid']}'->>'password' as casino_password,
              casino_accounts->'{$gtapi_column['casinoid']}'->>'balance' as casino_balance FROM root_member JOIN
              root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.id = '{$member_id}';
SQL;
	$r = runSQLall($member_sql);
	if ($debug == 1) {
		var_dump($r);
	}

	$check_return = [];
	if ($r[0] == 1 and $config['casino_transfer_mode'] != 0) {
		// 沒有 娛樂城 帳號的話，根本不可以進來
		if ($r[1]->casino_account == null or $r[1]->casino_account == '') {
			$check_return['messages'] = '你还没有 ' . $gtapi_column['casinoid'] . ' Casino 帐号。';
			$check_return['code'] = $errorCode['does_not_have_casino_account'];
		} elseif ($r[1]->gtoken_balance >= '1') {
			// 需要 gtoken_lock 沒有被設定的時候 或 前往的娛樂城為鎖定的娛樂城，才可以使用這功能
			if ($r[1]->gtoken_lock == null or $r[1]->gtoken_lock == $gtapi_column['casinoid']) {
				// 動作： 將本地端所有的 gtoken 餘額 Deposit(存入) 到 娛樂城 對應的帳戶
				$accountNumber = $r[1]->casino_account;
				$amount = round($r[1]->gtoken_balance, 2);
				$casino_balance = round($r[1]->casino_balance, 2);

				// 娛樂城轉帳設定
				// 0: 測試環境，不進行轉帳    1: 正式環境，會正常進行轉帳作業    2: 限額轉帳，每次只轉10元
				if ($config['casino_transfer_mode'] == 2) {
					$amount = 10;
				}

				// 設置 GAPI 所需資料
				$GT_API_data = array(
					'gamehall' => $gtapi_column['gamehall'],
					'account' => $accountNumber,
					'amount' => $amount,
					'transaction_id' => substr($gtapi_column['casinoid'], 0, 3) . '0Deposit0' . date("Ymdhis")
				);
				if ($debug == 1) {
					var_dump($GT_API_data);
				}

				// 存入娛樂城
				$GT_API_result = gt_api('Deposit', $debug, $GT_API_data);
				if ($GT_API_result['errorcode'] == 0 and $GT_API_result['Status'] == 0 and $GT_API_result['count'] >= 0) {
					if ($debug == 1) {
						var_dump($GT_API_result);
					}
					// 娛樂城最終餘額
					$casino_balance += $amount;
					// 本地端 db 的餘額處理
					$togtoken_sql = "UPDATE root_member_wallets SET gtoken_lock = '{$gtapi_column['casinoid']}'  WHERE id = '$member_id';";
					$togtoken_sql .= <<<SQL
                      UPDATE root_member_wallets SET gtoken_balance = gtoken_balance - '{$amount}',
                          casino_accounts=jsonb_set(casino_accounts,'{"{$gtapi_column['casinoid']}","balance"}','{$casino_balance}')  WHERE id = '{$member_id}';
SQL;
					if ($debug == 1) {
						var_dump($togtoken_sql);
					}
					$togtoken_sql_result = runSQLtransactions($togtoken_sql);
					if ($debug == 1) {
						var_dump($togtoken_sql_result);
					}

					// 轉帳紀錄
					if ($togtoken_sql_result) {
						$check_return['messages'] = '所有GTOKEN余额已经转到 ' . $gtapi_column['casinoid'] . ' Casino 娱乐城。 ' . $gtapi_column['casinoid'] . ' Casino 转帐单号 ' . $GT_API_result['Result']->transaction_id . ' ' . $gtapi_column['casinoid'] . ' Casino 帐号' . $accountNumber . ' ' . $gtapi_column['casinoid'] . ' Casino 新增' . $amount;
						$check_return['code'] = $errorCode['transfer_success'];
						memberlog2db($_SESSION['member']->account, 'transferout', 'info', $check_return['messages']);
						member_casino_transferrecords('lobby', $gtapi_column['casinoid'], $amount, $check_return['messages'], 'success', $GT_API_result['Result']->transaction_id, 1);
					} else {
						$check_return['messages'] = '余额处理，本地端资料库交易错误。';
						$check_return['code'] = $errorCode['database_transfer_error'];
						memberlog2db($_SESSION['member']->account, 'transferout', 'error', $check_return['messages']);
						member_casino_transferrecords('lobby', $gtapi_column['casinoid'], $amount, $check_return['messages'], 'warning', $GT_API_result['Result']->transaction_id, 2);
					}
				} else if (($GT_API_result['errorcode'] == 1006 or $GT_API_result['errorcode'] == 1015 or
					$GT_API_result['errorcode'] == 1999) or ($GT_API_result['curl_status'] == 1 and $GT_API_result['errorcode'] == 28)) {
					// 帳款轉移時發生 timeout，可能會發生在 API -> 娛樂城 及 平台 -> API，紀錄為 API 錯誤
					$check_return['messages'] = '转帐至'. $gtapi_column['casinoid'] .'娱乐城，帐款处理中。';
					$check_return['code'] = $errorCode['transfer_pending'];
					updateMemberStatusById($member_id, 2, $debug);
					memberlog2db($_SESSION['member']->account, 'transferout', 'notice', $check_return['messages']);
					member_casino_transferrecords('lobby', $gtapi_column['casinoid'], $amount, $check_return['messages'], 'fail', $GT_API_data['transaction_id'], 3);
				} else {
					$check_return['messages'] = '余额转移到 ' . $gtapi_column['casinoid'] . ' Casino 时失败！！';
					$check_return['code'] = $errorCode['transfer_fail'];
					memberlog2db($_SESSION['member']->account, 'transferout', 'error', $check_return['messages']);
					member_casino_transferrecords('lobby', $gtapi_column['casinoid'], $amount, $check_return['messages'] . '(' . $GT_API_result['Result'] . ')', 'fail', $GT_API_result['Result']->transaction_id, 2);
				}
			} else {
				$check_return['messages'] = '此帐号已经在 ' . $gtapi_column['casinoid'] . ' Casino 娱乐城活动，请勿重复登入。';
				$check_return['code'] = $errorCode['duplicate_account'];
				member_casino_transferrecords('lobby', $gtapi_column['casinoid'], '0', $check_return['messages'], 'warning');
			}
		} else {
			$check_return['messages'] = '所有GTOKEN余额为 0 故不进行转帐交易。';
			$check_return['code'] = $errorCode['transfer_success'];
			memberlog2db($_SESSION['member']->account, 'transferout', 'info', $check_return['messages']);
			member_casino_transferrecords('lobby', $gtapi_column['casinoid'], '0', $check_return['messages'], 'info');
		}
	} elseif ($r[0] == 1 and $config['casino_transfer_mode'] == 0) {
		$check_return['messages'] = '测试环境不进行转帐交易';
		$check_return['code'] = $errorCode['transfer_success'];
		member_casino_transferrecords('lobby', $gtapi_column['casinoid'], '0', $check_return['messages'], 'info');
	} else {
		$check_return['messages'] = '无此帐号 ID = ' . $member_id;
		$check_return['code'] = $errorCode['does_not_have_member_account'];
		member_casino_transferrecords('lobby', $gtapi_column['casinoid'], '0', $check_return['messages'], 'fail');
	}

	return ($check_return);
}


/**
 *  取回 娛樂城 Casino 的餘額，並檢查上次離開時和目前的差額得出派彩金額，紀錄存簿
 *  方法：
 *  retrieve_casino_balance(...)  取回 娛樂城 的餘額
 *  db_retrieve_casino_balance(...) 取回 娛樂城 的餘額 -- 針對 db 的處理函式
 *
 * 1. 查詢 DB 的 gtoken_lock  是否有紀錄在 娛樂城 帳戶，NULL 沒有紀錄的話表示沒有餘額在 娛樂城 帳戶
 * 2. AND 當 session 有 娛樂城_balance 的時候才動作，如果沒有則結束，表示 db 帳號資料有問題
 * (Deprecated) 3. lock 這個程序, 確保唯一性。使用 $_SESSION['wallet_transfer']  旗標，鎖住程序，不要同時間同一個人執行。需要配合 session_write_close() 才可以。
 * 4.  session 有 娛樂城_balance，gtoken 紀錄為 目的地娛樂城 ，API 檢查 娛樂城 的餘額有多少
 * 5. 承接 4 ,如果 娛樂城 餘額 > 1
 *     5.1 執行 GT API 取回 娛樂城 餘額 到 娛樂城 的出納帳戶(API操作) ， 成功才執行 5.2、5.3
 *     5.2 把 GT API 傳回的餘額，透過 GTOKEN 出納帳號，轉帳到的相對使用者 account 的 GTOKEN 帳戶(DB操作)
 *     5.3 紀錄當下 DB 娛樂城_balance 的餘額紀錄：存摺 GTOKEN 紀錄: ( CASINO GT API)收入 =4 ，(DB CASINO 餘額)支出 = 10 ，(派彩)餘額 = -6 + 原有結餘，
 *          摘要：娛樂城 派彩(DB操作)
 *     5.1 ~ 5.3 必須要全部成功，才算成功。如果 5.1 成功後，但 5.2、5.3 失敗的話，紀錄為 ERROR LOG，需要通知管理員處理(message system)
 * 6. 紀錄這次的 retrieve_casino_balance 操作，為一次交易紀錄。後續可以查詢(Confirmation Number)
 * 7. 執行完成後，需要 reload page，把 $_SESSION['wallet_transfer'] 變數清除, 其他程式才可以進入
 * 8. 把 GTOKEN_LOCK 設定為 NULL , 表示已經沒有餘額在娛樂城了
 */

/**
 *  取回 娛樂城 的餘額 -- 針對 db 的處理函式，只針對此功能有用
 * 不能單獨使用，需要搭配 retrieve_casino_balance
 *  本方法執行下列邏輯：
 *  5.2 把 GT API 傳回的餘額，透過 GTOKEN 出納帳號，轉帳到的相對使用者 account 的 GTOKEN 帳戶(DB操作)
 *  5.3 紀錄當下 DB 娛樂城_balance 的餘額紀錄：存摺 GTOKEN 紀錄: (CASINO GT API)收入 =4 ，(DB GPCASINO 餘額)支出 = 10 ，(派彩)餘額 = -6 + 原有結餘，
 *
 * @param mixed $gtoken_cashier_account 系統代幣出納帳號
 * @param mixed $gt_api_balance GT API 餘額
 * @param mixed $payout_balance 派彩
 * @param mixed $casino_balance_db 資料庫錢包餘額
 * @param int $debug 除錯模式，預設 0 為關閉
 *
 * @return array 處理結果，1 為成功
 */
function db_retrieve_casino_balance($gtoken_cashier_account, $gt_api_balance, $payout_balance, $casino_balance_db, $debug = 0)
{
	global $gtoken_cashier_account;
	global $transaction_category;
	global $config;
	global $gtapi_column;

	// 取得來源與目的帳號的 id，$gtoken_cashier_account(此為系統代幣出納帳號 global var.)
	$d = [];
	$r = [];

	// 設定轉帳來源與目的帳號
	$d['source_transferaccount'] = $gtoken_cashier_account;
	$d['destination_transferaccount'] = $_SESSION['member']->account;
	$source_id_sql = "SELECT * FROM root_member WHERE account = '" . $d['source_transferaccount'] . "';";
	$source_id_result = runSQLall($source_id_sql);
	$destination_id_sql = "SELECT * FROM root_member WHERE account = '" . $d['destination_transferaccount'] . "';";
	$destination_id_result = runSQLall($destination_id_sql);

	if ($source_id_result[0] == 1 and $destination_id_result[0] == 1) {
		$d['source_transfer_id'] = $source_id_result[1]->id;
		$d['destination_transfer_id'] = $destination_id_result[1]->id;
	} else {
		$logger = '转帐的来源与目的帐号可能有问题，请稍候再试。';
		$r['ErrorCode'] = 590;
		$r['ErrorMessage'] = $logger;
		echo "<p> $logger </p>";
		die();
	}

	if ($debug == 1) {
		var_dump($payout_balance);
	}

	// 派彩有三種狀態，要有不同的對應 SQL 處理
	if ($payout_balance >= 0) {
		// $payout_balance >= 0; 從娛樂城贏錢 or 沒有輸贏，把 娛樂城 餘額取回

		// 先取得當下的  wallets 變數資料,等等 sql 更新後. 就會消失了。
		$wallets_sql = "SELECT gtoken_balance,casino_accounts->'{$gtapi_column['casinoid']}'->>'balance' as casino_balance FROM root_member_wallets WHERE id = '" . $_SESSION['member']->id . "';";
		$wallets_result = runSQLall($wallets_sql);

		// 在剛取出的 wallets 資料庫中 娛樂城 的餘額(支出)
		$gtoken_casino_balance_db = round($wallets_result[1]->casino_balance, 2);
		// 在剛取出的 wallets 資料庫中 gtoken(代幣) 的餘額(支出)
		$gtoken_balance_db = round($wallets_result[1]->gtoken_balance, 2);
		// 派彩 = 娛樂城餘額 - 本地端 支出餘額
		$gtoken_balance = round(($gtoken_balance_db + $gtoken_casino_balance_db + $payout_balance), 2);

		// 交易開始
		$payout_transaction_sql = 'BEGIN;';
		// 存款金額 -- 娛樂城餘額
		$d['deposit'] = $gtoken_balance;
		// 提款金額 -- 本地端支出
		$d['withdrawal'] = $casino_balance_db;
		// 操作者
		$d['member_id'] = $_SESSION['member']->id;
		// (說明)娛樂城 + 代幣派彩
		$d['summary'] = $gtapi_column['casinoid'] . $transaction_category['tokenpay'];
		// 稽核方式
		$d['auditmode'] = strtoupper($gtapi_column['casinoid']);
		// 稽核金額 -- 派彩無須稽核
		$d['auditmodeamount'] = 0;
		// 娛樂城 取回的餘額為真錢
		$d['realcash'] = 2;
		// 交易類別 娛樂城 + $transaction_category['tokenpay']
		$d['transaction_category'] = 'tokenpay';
		// 變化的餘額
		$d['balance'] = $payout_balance;

		// 操作 root_member_wallets DB, 把 娛樂城 balance 設為 0 , 把 gtoken_lock = null, 把 gtoken_balance = $d['deposit']
		// 錢包存入 餘額 , 把 娛樂城 balance 扣除全部表示支出(投注)
		$payout_transaction_sql = $payout_transaction_sql . "
      UPDATE root_member_wallets SET changetime = NOW(), gtoken_balance =  " . $d['deposit'] . ", gtoken_lock = NULL,casino_accounts= jsonb_set(casino_accounts,'{\"" . $gtapi_column['casinoid'] . "\",\"balance\"}','0') WHERE id = '" . $d['destination_transfer_id'] . "'; ";
		// 目的帳號上的註記
		$d['destination_notes'] = '(會員收到 ' . $gtapi_column['casinoid'] . ' Casino 派彩 ' . $d['balance'] . ')';
		// 針對目的會員的存簿寫入，$payout_balance >= 1 表示贏錢，所以從出納匯款到使用者帳號。
		$payout_transaction_sql = $payout_transaction_sql .
			'INSERT INTO "root_member_gtokenpassbook" ("transaction_time", "deposit", "withdrawal", "system_note", "member_id", "currency", "summary", "source_transferaccount", "auditmode", "auditmodeamount", "realcash", "destination_transferaccount", "transaction_category", "balance")' .
			"VALUES ('now()', '" . $d['deposit'] . "', '" . $d['withdrawal'] . "', '" . $d['destination_notes'] . "', '" . $d['member_id'] . "', '". $config['currency_sign']. "', '" . $d['summary'] . "', '" . $d['destination_transferaccount'] . "', '" . $d['auditmode'] . "', '" . $d['auditmodeamount'] . "', '" . $d['realcash'] . "', " .
			"'" . $d['destination_transferaccount'] . "','" . $d['transaction_category'] . "', (SELECT gtoken_balance FROM root_member_wallets WHERE id = " . $d['destination_transfer_id'] . ") );";

		// 針對來源出納的存簿寫入
		$payout_transaction_sql = $payout_transaction_sql . "
      UPDATE root_member_wallets SET changetime = NOW(), gtoken_balance = (gtoken_balance - " . $d['balance'] . ") WHERE id = '" . $d['source_transfer_id'] . "'; ";
		// 來源帳號上的註記
		$d['source_notes'] = '(出納帳號 ' . $d['source_transferaccount'] . ' 幫 ' . $gtapi_column['casinoid'] . ' Casino 派彩到會員 ' . $d['destination_transferaccount'] . ')';
		$payout_transaction_sql = $payout_transaction_sql .
			'INSERT INTO "root_member_gtokenpassbook" ("transaction_time", "deposit", "withdrawal", "system_note", "member_id", "currency", "summary", "source_transferaccount", "auditmode", "auditmodeamount", "realcash", "destination_transferaccount", "transaction_category", "balance")' .
			"VALUES ('now()', '0', '" . $d['balance'] . "', '" . $d['source_notes'] . "', '" . $d['member_id'] . "', '". $config['currency_sign']. "', '" . $d['summary'] . "', '" . $d['source_transferaccount'] . "', '" . $d['auditmode'] . "', '" . $d['auditmodeamount'] . "', '" . $d['realcash'] . "', " .
			"'" . $d['source_transferaccount'] . "','" . $d['transaction_category'] . "', (SELECT gtoken_balance FROM root_member_wallets WHERE id = " . $d['source_transfer_id'] . "));";

		// commit 提交
		$payout_transaction_sql = $payout_transaction_sql . 'COMMIT;';

		if ($debug == 1) {
			echo '<p>SQL=' . $payout_transaction_sql . '</p>';
		}

		// 執行 transaction sql
		$payout_transaction_result = runSQLtransactions($payout_transaction_sql);
		if ($payout_transaction_result) {
			$logger = '从 ' . $gtapi_column['casinoid'] . ' Casino 帐号' . $_SESSION['member']->{$gtapi_column['account']} . '取回余额到代币，统计后收入=' . $gt_api_balance . '，支出=' . $casino_balance_db . '，共计派彩=' . $payout_balance;
			$r['ErrorCode'] = 1;
			$r['ErrorMessage'] = $logger;
			memberlog2db($_SESSION['member']->account, $gtapi_column['casinoid'] . '_game', 'info', "$logger");
			member_casino_transferrecords($gtapi_column['casinoid'], 'lobby', $gt_api_balance, $logger, 'info');
		} else {
			//5.1 ~ 5.3 必須一定要全部成功，才算成功。如果 5.1 成功後，但 5.2、5.3 失敗的話，紀錄為 ERROR LOG，需要通知管理員處理(message system)
			$logger = '从 ' . $gtapi_column['casinoid'] . ' Casino 帐号' . $_SESSION['member']->{$gtapi_column['account']} . '取回余额到代币，统计后收入=' . $gt_api_balance . '，支出=' . $casino_balance_db . '，共计派彩=' . $payout_balance;
			$logger = $logger . '但资料库处理错误，请通知客服人员处理。';
			memberlog2db($d['member_id'], 'db_transaction', 'error', "$logger");
			$r['ErrorCode'] = 406;
			$r['ErrorMessage'] = $logger;
			memberlog2db($_SESSION['member']->account, $gtapi_column['casinoid'] . '_game', 'error', "$logger");
			member_casino_transferrecords($gtapi_column['casinoid'], 'lobby', $gt_api_balance, $logger, 'warning');
		}

		if ($debug == 1) {
			var_dump($r);
		}
	} elseif ($payout_balance < 0) {
		// $payout_balance < 0; 從娛樂城輸錢
		// 先取得當下的  wallets 變數資料，等等 sql 更新後，就會消失了
		$wallets_sql = "SELECT gtoken_balance,casino_accounts->'{$gtapi_column['casinoid']}'->>'balance' as casino_balance FROM root_member_wallets WHERE id = '" . $_SESSION['member']->id . "';";
		$wallets_result = runSQLall($wallets_sql);

		// 在剛取出的 wallets 資料庫中 娛樂城 的餘額(支出)
		$gtoken_casino_balance_db = round($wallets_result[1]->casino_balance, 2);
		// 在剛取出的 wallets 資料庫中 gtoken(代幣) 的餘額(支出)
		$gtoken_balance_db = round($wallets_result[1]->gtoken_balance, 2);
		// 派彩 = 娛樂城餘額 - 本地端 支出餘額
		$gtoken_balance = round(($gtoken_balance_db + $gtoken_casino_balance_db + $payout_balance), 2);


		// 交易開始
		$payout_transaction_sql = 'BEGIN;';
		// 存款金額 -- 娛樂城餘額
		$d['deposit'] = $gtoken_balance;
		// 提款金額 -- 本地端支出
		$d['withdrawal'] = $casino_balance_db;
		// 操作者
		$d['member_id'] = $_SESSION['member']->id;
		// (說明)娛樂城 + 代幣派彩
		$d['summary'] = $gtapi_column['casinoid'] . $transaction_category['tokenpay'];
		// 稽核方式
		$d['auditmode'] = strtoupper($gtapi_column['casinoid']);
		// 稽核金額 -- 派彩無須稽核
		$d['auditmodeamount'] = 0;
		// 娛樂城 取回的餘額為真錢
		$d['realcash'] = 2;
		// 交易類別 娛樂城 + $transaction_category['tokenpay']
		$d['transaction_category'] = 'tokenpay';
		// 變化的餘額
		$d['balance'] = abs($payout_balance);

		// 操作 root_member_wallets DB, 把 娛樂城 balance 設為 0 , 把 gtoken_lock = null, 把 gtoken_balance = $d['deposit']
		// 錢包存入 餘額 , 把 娛樂城 balance 扣除全部表示支出(投注)
		$payout_transaction_sql = $payout_transaction_sql . "
      UPDATE root_member_wallets SET changetime = NOW(), gtoken_balance =  " . $d['deposit'] . ", gtoken_lock = NULL,casino_accounts= jsonb_set(casino_accounts,'{\"" . $gtapi_column['casinoid'] . "\",\"balance\"}','0') WHERE id = '" . $d['destination_transfer_id'] . "'; ";
		// 目的帳號上的註記
		$d['destination_notes'] = '(會員收到 ' . $gtapi_column['casinoid'] . ' Casino 派彩' . $payout_balance . ')';
		// 針對目的會員的存簿寫入，
		$payout_transaction_sql = $payout_transaction_sql .
			'INSERT INTO "root_member_gtokenpassbook" ("transaction_time", "deposit", "withdrawal", "system_note", "member_id", "currency", "summary", "source_transferaccount", "auditmode", "auditmodeamount", "realcash", "destination_transferaccount", "transaction_category", "balance")' .
			"VALUES ('now()', '" . $d['deposit'] . "', '" . $d['withdrawal'] . "', '" . $d['destination_notes'] . "', '" . $d['member_id'] . "', '". $config['currency_sign']. "', '" . $d['summary'] . "', '" . $d['destination_transferaccount'] . "', '" . $d['auditmode'] . "', '" . $d['auditmodeamount'] . "', '" . $d['realcash'] . "', " .
			"'" . $d['destination_transferaccount'] . "','" . $d['transaction_category'] . "', (SELECT gtoken_balance FROM root_member_wallets WHERE id = " . $d['destination_transfer_id'] . ") );";

		// 針對來源出納的存簿寫入
		$payout_transaction_sql = $payout_transaction_sql . "
      UPDATE root_member_wallets SET changetime = NOW(), gtoken_balance = (gtoken_balance + " . $d['balance'] . ") WHERE id = '" . $d['source_transfer_id'] . "'; ";
		// 來源帳號上的註記
		$d['source_notes'] = '(出納帳號 ' . $d['source_transferaccount'] . ' 從會員 ' . $d['destination_transferaccount'] . ' 取回派彩餘額)';
		$payout_transaction_sql = $payout_transaction_sql .
			'INSERT INTO "root_member_gtokenpassbook" ("transaction_time", "deposit", "withdrawal", "system_note", "member_id", "currency", "summary", "source_transferaccount", "auditmode", "auditmodeamount", "realcash", "destination_transferaccount", "transaction_category", "balance")' .
			"VALUES ('now()', '" . $d['balance'] . "', '0', '" . $d['source_notes'] . "', '" . $d['member_id'] . "', '". $config['currency_sign']. "', '" . $d['summary'] . "', '" . $d['source_transferaccount'] . "', '" . $d['auditmode'] . "', '" . $d['auditmodeamount'] . "', '" . $d['realcash'] . "', " .
			"'" . $d['source_transferaccount'] . "','" . $d['transaction_category'] . "', (SELECT gtoken_balance FROM root_member_wallets WHERE id = " . $d['source_transfer_id'] . "));";

		// commit 提交
		$payout_transaction_sql = $payout_transaction_sql . 'COMMIT;';
		if ($debug == 1) {
			echo '<p>SQL=' . $payout_transaction_sql . '</p>';
		}

		// 執行 transaction sql
		$payout_transaction_result = runSQLtransactions($payout_transaction_sql);
		if ($payout_transaction_result) {
			$logger = '从 ' . $gtapi_column['casinoid'] . ' Casino 帐号' . $_SESSION['member']->{$gtapi_column['account']} . '取回余额到代币，统计后收入=' . $gt_api_balance . '，支出=' . $casino_balance_db . '，共计派彩=' . $payout_balance;
			$r['ErrorCode'] = 1;
			$r['ErrorMessage'] = $logger;
			memberlog2db($_SESSION['member']->account, $gtapi_column['casinoid'] . '_game', 'info', "$logger");
			member_casino_transferrecords($gtapi_column['casinoid'], 'lobby', $gt_api_balance, $logger, 'info');
		} else {
			// 5.1 ~ 5.3 必須一定要全部成功，才算成功。如果 5.1 成功後，但 5.2、5.3 失敗的話，紀錄為 ERROR LOG，需要通知管理員處理(message system)
			$logger = '从 ' . $gtapi_column['casinoid'] . ' Casino 帐号' . $_SESSION['member']->{$gtapi_column['account']} . '取回余额到代币，统计后收入=' . $gt_api_balance . '，支出=' . $casino_balance_db . '，共计派彩=' . $payout_balance;
			$logger = $logger . '但资料库处理错误，请通知客服人员处理。';
			$r['ErrorCode'] = 406;
			$r['ErrorMessage'] = $logger;
			memberlog2db($_SESSION['member']->account, $gtapi_column['casinoid'] . '_game', 'error', "$logger");
			member_casino_transferrecords($gtapi_column['casinoid'], 'lobby', $gt_api_balance, $logger, 'warning');
		}
	} else {
		// 不可能
		$logger = '不可能发生';
		$r['ErrorCode'] = 500;
		$r['ErrorMessage'] = $logger;
		memberlog2db($_SESSION['member']->account, 'casino game', 'error', "$logger");
		echo "<p> $logger </p>";
	}

	return ($r);
}


/**
 *  取回 娛樂城 的餘額，不能單獨使用，需要搭配 db_retrieve_casino_balance 使用
 *
 * @param mixed $member_id 會員 ID
 * @param int $debug 除錯模式，預設 0 為關閉
 *
 * @return array 轉帳結果
 */
function retrieve_casino_balance($member_id, $debug = 0)
{
	global $gtoken_cashier_account;
	global $gtapi_column;
	global $statusCodes;
	global $errorCode;

	$r = [];

	// 判斷會員 帳號 是否被鎖定
	$member_sql = "SELECT * FROM root_member WHERE id = '" . $member_id . "' AND status = '1';";
	$member_result = runSQLall($member_sql, $debug);

	// 取得未更新前的娛樂城帳號、密碼、餘額
	$wallets_sql = <<<SQL
      SELECT gtoken_balance,gtoken_lock,
              casino_accounts->'{$gtapi_column['casinoid']}'->>'account' as {$gtapi_column['casinoid']}_account,
              casino_accounts->'{$gtapi_column['casinoid']}'->>'password' as {$gtapi_column['casinoid']}_password,
              casino_accounts->'{$gtapi_column['casinoid']}'->>'balance' as {$gtapi_column['casinoid']}_balance FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.id = '{$member_id}';
SQL;
	$wallets_result = runSQLall($wallets_sql, $debug);
	if ($debug == 1) {
		var_dump($wallets_sql);
		var_dump($wallets_result);
	}

	// 1. 查詢 DB 的 gtoken_lock 是否有紀錄在 娛樂城 帳戶，NULL 的話表示沒有餘額在 娛樂城 帳戶。(已經取回了，代幣一次只能對應一個娛樂城)
	// 2. AND 當 DB 有 娛樂城_balance(娛樂城餘額) 的時候才動作，如果沒有則結束，表示 DB 帳號資料有問題
	if ($member_result[0] == 1 and $wallets_result[0] == 1 and
		getCasinoAccount($gtapi_column['casinoid'], $wallets_result[1]) != null and
		$wallets_result[1]->gtoken_lock == $gtapi_column['casinoid']) {

		// 3. gtoken 紀錄為 前往娛樂城 , API 檢查 娛樂城 的餘額有多少
		// 取得娛樂城帳號
		$delimitedAccountNumbers = getCasinoAccount($gtapi_column['casinoid'], $wallets_result[1]);
		$GT_API_data = array(
			'gamehall' => $gtapi_column['gamehall'],
			'account' => $delimitedAccountNumbers
		);
		if ($debug == 1) {
			var_dump($GT_API_data);
		}
		// 取得 會員娛樂城資料(API)
		$GT_API_result = gt_api('GetAccountDetails', $debug, $GT_API_data);
		// 剔除線上會員
		$GT_API_kickuser_result = gt_api('KickUser', $debug, $GT_API_data);
		if ($GT_API_result['errorcode'] == 0 and $GT_API_result['Status'] == 0 and $GT_API_result['count'] >= 0 and
			($GT_API_kickuser_result['Status'] == 0 OR $GT_API_kickuser_result['Status'] == 1017 )) {
			// 4. 查詢餘額動作，成立後執行，失敗的話結束，可能網路有問題
			// 取得 GT API 娛樂城錢包餘額 , 保留小數第二位 round( $x, 2)
			$gt_api_balance = round($GT_API_result['Result']->balance, 2);
			$logger = $gtapi_column['casinoid'] . ' Casino API 查询余额为' . $GT_API_result['Result']->balance . '操作的余额为' . $gt_api_balance;
			$r['code'] = $statusCodes['success'];
			$r['messages'] = $logger;

			// 5. 承接 4，如果 娛樂城 餘額 > 0
			// GT API 娛樂城錢包查詢餘額 > 0
			if ($gt_api_balance >= 1) {
				// 5.1 執行 GT API 取回 娛樂城 餘額，到 娛樂城 的出納帳戶(API操作)，成功才執行 5.2、5.3
				$GT_API_data = array(
					'gamehall' => $gtapi_column['gamehall'],
					'account' => getCasinoAccount($gtapi_column['casinoid'], $wallets_result[1]),
					'amount' => $gt_api_balance,
					'transaction_id' => substr($gtapi_column['casinoid'], 0, 3) . '0Withdrawal0' . date("Ymdhis")
				);

				if ($debug == 1) {
					echo '5.1 執行 ' . $gtapi_column['casinoid'] . ' Casino API 取回 ' . $gtapi_column['casinoid'] . ' Casino 餘額，到 ' . $gtapi_column['casinoid'] . ' Casino 的出納帳戶(API操作)，成功才執行 5.2、5.3';
					var_dump($GT_API_data);
				}

				// 動作：Withdrawal 從會員娛樂城錢包帳戶取款(GT API)
				$GT_API_result = gt_api('Withdrawal', $debug, $GT_API_data);
				if ($debug == 1) {
					var_dump($GT_API_result);
				}

				if ($GT_API_result['errorcode'] == 0 and $GT_API_result['Status'] == 0 and $GT_API_result['count'] >= 0) {
					// 取回 會員娛樂城錢包帳戶 餘額成功
					$logger = $gtapi_column['casinoid'] . ' Casino API 从帐号' . getCasinoAccount($gtapi_column['casinoid'], $wallets_result[1]) . '取款余额' . $gt_api_balance . '成功。交易编号为' . $GT_API_result['Result']->transaction_id;
					$r['code'] = $statusCodes['api_withdrawal_success'];
					$r['messages'] = $logger;
					memberlog2db($_SESSION['member']->account, $gtapi_column['casinoid'] . 'game', 'info', "$logger");
					member_casino_transferrecords($gtapi_column['casinoid'], 'lobby', $gt_api_balance, $logger, 'success', $GT_API_result['Result']->transaction_id, 1);

					if ($debug == 1) {
						echo "<p> $logger </p>";
						var_dump($GT_API_result);
					}

					// 先取得當下資料庫會員娛樂城錢包資料
					$wallets_sql = "SELECT casino_accounts->'" . $gtapi_column['casinoid'] . "'->>'balance' as ".
						$gtapi_column['casinoid'] ."_balance FROM root_member_wallets WHERE id = '" . $_SESSION['member']->id . "';";
					$wallets_result = runSQLall($wallets_sql);

					// 取出 資料庫會員娛樂城錢包 的餘額(支出)
					$casino_balance_db = round(getSpecificKeyValueFromArray($gtapi_column['casinoid'], '_balance',
						$wallets_result[1]), 2);
					// 派彩 = 娛樂城餘額 - 本地端 娛樂城 支出餘額
					$payout_balance = round(($gt_api_balance - $casino_balance_db), 2);
					$r['balance'] = $payout_balance;

					// 處理 DB 的轉帳問題 -- 5.2 and 5.3
					$db_retrieve_casino_balance_result = db_retrieve_casino_balance($gtoken_cashier_account, $gt_api_balance, $payout_balance, $casino_balance_db);
					if ($db_retrieve_casino_balance_result['ErrorCode'] == 1) {
						$r['code'] = 1;
						$r['messages'] = $db_retrieve_casino_balance_result['ErrorMessage'];
						$logger = $r['messages'];
						memberlog2db($_SESSION['member']->account, 'casino retrieve', 'info', "$logger");
					} else {
						$r['code'] = 523;
						$r['messages'] = $db_retrieve_casino_balance_result['ErrorMessage'];
						$logger = $r['messages'];
						memberlog2db($_SESSION['member']->account, 'casino retrieve', 'error', "$logger");
					}

					if ($debug == 1) {
						echo '處理 DB 的轉帳問題 -- 5.2 and 5.3';
						var_dump($db_retrieve_casino_balance_result);
					}
				} else if (($GT_API_result['errorcode'] == 1006 or $GT_API_result['errorcode'] == 1015 or
						$GT_API_result['errorcode'] == 1999) or ($GT_API_result['curl_status'] == 1 and $GT_API_result['errorcode'] == 28)) {
					// 帳款轉移時發生 timeout，可能會發生在 API -> 娛樂城 及 平台 -> API
					$check_return['messages'] = $gtapi_column['casinoid'] . ' Casino API 从帐号' . getCasinoAccount($gtapi_column['casinoid'], $wallets_result[1]) . '取款余额' . $gt_api_balance . '，帐款处理中。';
					$check_return['code'] = $errorCode['transfer_pending'];
					updateMemberStatusById($member_id, 2, $debug);
					memberlog2db($_SESSION['member']->account, 'transferout', 'notice', $check_return['messages']);
					member_casino_transferrecords($gtapi_column['casinoid'], 'lobby', $gt_api_balance, $check_return['messages'], 'fail', $GT_API_data['transaction_id'], 3);
				} else {
					// 5.1 執行 GT API 取回 娛樂城 餘額 ，到 娛樂城 的出納帳戶(API操作) , 成功才執行 5.2、5.3
					$logger = $gtapi_column['casinoid'] . ' Casino API 从帐号' . $_SESSION['member']->{$gtapi_column['account']} . '取款余额' . $gt_api_balance . '失败';
					$r['code'] = 405;
					$r['messages'] = $logger;
					memberlog2db($_SESSION['member']->account, 'casino retrieve', 'error', "$logger");
					member_casino_transferrecords($gtapi_column['casinoid'], 'lobby', '0', $logger . '('. $GT_API_result['Result'] . ')', 'fail', '', 2);

					if ($debug == 1) {
						echo "5.1 執行 {$gtapi_column['casinoid']} API 取回 {$gtapi_column['casinoid']} 餘額 ，到 {$gtapi_column['casinoid']} 的出納帳戶(API操作) , 成功才執行 5.2、5.3";
						echo "<p> $logger </p>";
						var_dump($r);
					}
				}
			} elseif ($gt_api_balance < 1 and $gt_api_balance >= 0) {
				$logger = $gtapi_column['casinoid'] . ' Casino 余额 < 1 ，' . $gtapi_column['casinoid'] . ' Casino 余额不足，无法取回任何的余额，将余额转回 GPK。';
				$r['code'] = 406;
				$r['messages'] = $logger;
				memberlog2db($_SESSION['member']->account, 'casino retrieve', 'info', "$logger");
				member_casino_transferrecords($gtapi_column['casinoid'], 'lobby', '0', $logger, 'success');

				// 先取得當下的  wallets 變數資料,等等 sql 更新後. 就會消失了。
				$wallets_sql = <<<SQL
                  SELECT gtoken_balance,gtoken_lock,
                          casino_accounts->'{$gtapi_column['casinoid']}'->>'account' as {$gtapi_column['casinoid']}_account,
                          casino_accounts->'{$gtapi_column['casinoid']}'->>'password' as {$gtapi_column['casinoid']}_password,
                          casino_accounts->'{$gtapi_column['casinoid']}'->>'balance' as {$gtapi_column['casinoid']}_balance FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.id = '{$_SESSION['member']->id}';
SQL;
				$wallets_result = runSQLall($wallets_sql);

				// 在剛取出的 wallets 資料庫中的餘額(支出)
				$casino_balance_db = round(getSpecificKeyValueFromArray($gtapi_column['casinoid'], '_balance', $wallets_result[1]), 2);
				// 派彩 = 娛樂城餘額 - 本地端 娛樂城 支出餘額
				$payout_balance = round(($gt_api_balance - $casino_balance_db), 2);
				$r['balance'] = $payout_balance;

				// 處理 DB 的轉帳問題 -- 5.2 and 5.3
				$db_retrieve_casino_balance_result = db_retrieve_casino_balance($gtoken_cashier_account, $gt_api_balance, $payout_balance, $casino_balance_db);
				if ($db_retrieve_casino_balance_result['ErrorCode'] == 1) {
					$r['code'] = 1;
					$r['messages'] = $db_retrieve_casino_balance_result['ErrorMessage'];
					$logger = $r['messages'];
					memberlog2db($_SESSION['member']->account, 'casino retrieve', 'info', "$logger");
				} else {
					$r['code'] = 523;
					$r['messages'] = $db_retrieve_casino_balance_result['ErrorMessage'];
					$logger = $r['messages'];
					memberlog2db($_SESSION['member']->account, 'casino retrieve', 'error', "$logger");
				}

				if ($debug == 1) {
					echo '處理 DB 的轉帳問題 -- 5.2 and 5.3';
					var_dump($db_retrieve_casino_balance_result);
				}
			} else {
				// 娛樂城 餘額 < 0 , 不可能發生
				$logger = $gtapi_column['casinoid'] . ' Casino 余额 < 1 ，不可能发生。';
				$r['code'] = 404;
				$r['messages'] = $logger;
			}
		} else {
			// 4. session 有 娛樂城_balance，gtoken 紀錄為 目的地娛樂城 ，API 檢查 娛樂城 的餘額有多少
			$logger = $gtapi_column['casinoid'] . ' Casino API 查询余额失败，系统维护中请晚点再试。';
			$r['code'] = 403;
			$r['messages'] = $logger;
			member_casino_transferrecords($gtapi_column['casinoid'], 'lobby', '0', $logger . '(' . implode('_', $GT_API_result['Result']) . ')', 'fail');
			if ($debug == 1) {
				var_dump($GT_API_result);
			}
		}
	} else {
		// 1. 查詢 DB 的 gtoken_lock  是否有紀錄在 娛樂城 帳戶，NULL 沒有紀錄的話表示沒有餘額在 娛樂城 帳戶
		// 2. AND 當 session 有 娛樂城_balance 的時候才動作，如果沒有則結束，表示 db 帳號資料有問題
		$logger = '没有余额在 ' . $gtapi_column['casinoid'] . ' Casino 帐户 OR DB 帐号资料有问题 ';
		$r['code'] = 401;
		$r['messages'] = $logger;
		member_casino_transferrecords($gtapi_column['casinoid'], 'lobby', '0', $logger, 'fail');
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


/**
 *  產生可以連到 娛樂城 遊戲 的 url 位址
 *
 * @param mixed $casino_account 娛樂城帳號
 * @param mixed $casino_password 娛樂城密碼
 * @param mixed $casino_gameid 遊戲 ID
 * @param int $debug 除錯模式，預設 0 為關閉
 *
 * @return string 遊戲 的 url 位址
 */
function getGameUrl($casino_account, $casino_password, $casino_gameid, $debug = 0)
{
	global $gtapi_column;

	// 取得遊戲的資料
	$gamedata_sql = 'SELECT * FROM "casino_gameslist" WHERE "id" = \'' . $casino_gameid . '\';';
	$gamedata_result = runSQLall($gamedata_sql);

	$casino_gamecode = $gamedata_result['1']->gameid;
	$gamename = $gamedata_result['1']->gamename;
	$sub_gamehall = $gamedata_result['1']->gametype;

	if (isset($casino_account)) {
		$GT_API_data = array(
			'gamehall' => $gtapi_column['gamehall'],
			'gamecode' => $casino_gamecode,
			'account' => $casino_account,
			'lang' => ($_SESSION['lang'] == 'en-us') ? 'en' : $_SESSION['lang'],
			'sub_gamehall' => $sub_gamehall
		);

		$GT_API_result = gt_api('GetGameUrl', $debug, $GT_API_data);
		if ($GT_API_result['errorcode'] == 0 and $GT_API_result['Status'] == 0 and $GT_API_result['count'] >= 0) {
			$re = $GT_API_result['Result']->url;
			$logger = '会员 ' . $_SESSION['member']->account . ' 前往游戏' . $gamename . '.';
			member_casino_transferrecords('lobby', $gtapi_column['casinoid'], '0', $logger, 'info');
		} else {
			$re = '';
			$logger = '会员 ' . $_SESSION['member']->account . ' 前往游戏 ' . $gamename . ' 但游戏网址取得错误(' . $GT_API_result['errorcode'] . ')';
			member_casino_transferrecords('lobby', $gtapi_column['casinoid'], '0', $logger, 'fail');
		}
	} else {
		$re = '';
		$logger = '会员 ' . $_SESSION['member']->account . ' 前往游戏 ' . $gamename . ' 但没有娱乐城帐号';
		member_casino_transferrecords('lobby', $gtapi_column['casinoid'], '0', $logger, 'fail');
	}

	return ($re);
}


/**
 *  取得娛樂城帳號資訊
 *
 * @param mixed $casino_account 娛樂城帳號
 * @param int $debug 除錯模式，預設 0 為關閉
 *
 * @return array 會員帳號資訊
 */
function GetCasinoAccountInfo($casino_account, $debug = 0)
{
	global $config;
	global $gtapi_column;
	global $tr;

	$re = [];

	// 查詢會員帳號的資料
	if (isset($casino_account)) {
		$GT_API_data = array(
			'account' => $casino_account
		);
		$GT_API_result = gt_api('CheckUser', $debug, $GT_API_data);
		if ($debug == 1) {
			var_dump($GT_API_result);
		}
		if ($GT_API_result['Result'] AND $GT_API_result['errorcode'] == 0 and $GT_API_result['Status'] == 0) {
			$GT_API_data = array(
				'account' => $casino_account,
				'gamehall' => $gtapi_column['gamehall']
			);
			$GT_API_result = gt_api('GetAccountDetails', 0, $GT_API_data);
			if ($debug == 1) {
				var_dump($GT_API_result);
			}
			if ($GT_API_result['errorcode'] == 0 and $GT_API_result['Status'] == 0 and $GT_API_result['count'] >= 1) {
				$re['code'] = $GT_API_result['errorcode'];
				$re['balance'] = round($GT_API_result['Result']->balance, 2);
				$re['messages'] = '';
			} else {
				$re['code'] = $GT_API_result['errorcode'];
				$re['balance'] = 0;
				$re['messages'] = $GT_API_result['Result'];
			}

		} else {
			$re['code'] = 1;
			$re['balance'] = 0;
			$re['messages'] = $GT_API_result['Result'];
		}
	} else {
		$re['code'] = '-1';
		$re['balance'] = 0;
		$re['messages'] = '没有会员帐号';
	}

	return ($re);
}


/**
 *  依據會員 ID 更新會員帳號狀態
 *
 * @param mixed $id 會員 ID
 * @param mixed $status 帳號狀態
 * @param int $debug 除錯模式，0 為非除錯模式
 *
 * @return int SQL執行狀態
 */
function updateMemberStatusById($id, $status, $debug = 0)
{
	$sql = 'UPDATE root_member SET "status" = \''. $status .'\' WHERE "id" = '. $id .';';
	return runSQL($sql, $debug);
}
