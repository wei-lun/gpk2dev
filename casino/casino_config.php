<?php
// ----------------------------------------------------------------------------
// Features:	Casino lobby 的專用設定
// File Name:	casino_config.php
// Author:		Ian
// Related:
// Log:
// 2019.01.23 新增 MG PLUS 娛樂城 Letter
// 2019.04.19 新增 AP 娛樂城 Letter
// 2019.04.26 新增 VG 娛樂城 Letter
// 2019.09.26 修改娛樂城通用方法 Letter
// ----------------------------------------------------------------------------
// 此設定檔用於記錄各casino所使用的action、lib的php檔名及取錢時的function名，
// 這些資料將在gamelobby_action裡使用，用來做自動取回各casino的餘額以利轉換到其他casino，
// EX：
// $casino_action['MG'] MG使用的action
// $casino_lib['MG']    MG使用的lib
// $casino_retrieve['MG'] MG lib裡的取錢function
// UPDATE:
// 配合總後台，新增娛樂城通用方法，配合 API，使用通用方法，另外保留非透過 API
// 鍵接娛樂城舊方法與設定，方法如下:
// getCasinoActionUrl('娛樂城ID') 該娛樂城的 action
// getCasinoLib('娛樂城ID') 該娛樂城的 lib
// getCasinoRetrieveFunc('娛樂城ID') 該娛樂城的取錢方法
// 原有通用方法:
// auto_gcash2gtoken 自動 GCASH TO GTOKEN
// Transferout_GCASH_GTOKEN_balance 將 GCASH 依據設定值，自動加值到 GTOKEN 上面
// member_casino_transferrecords 產生會員轉換娛樂城的紀錄
// agent_walletscontrol_check 檢查後台是否有在協助會員操作錢包轉錢至娛樂或是自娛樂城取錢
// home_gamelist 輸出給首頁使用的遊戲列表
// 2019.09.26 新增通用方法:
// getCasinoAccount() 取得娛樂城帳號
// getCasinoPassword() 取得娛樂城密碼
// ----------------------------------------------------------------------------

$casino_action = [];
$casino_lib = [];
$casino_retrieve = [];

// MG
$casino_action['MG'] = 'casino/MG/lobby_mggame_action.php';
$casino_lib['MG'] = '/casino/MG/lobby_mggame_lib.php';
$casino_retrieve['MG'] = 'retrieve_mg_restful_casino_balance';

// MEGA
$casino_action['MEGA'] = 'casino/MEGA/lobby_megagame_action.php';
$casino_lib['MEGA'] = '/casino/MEGA/lobby_megagame_lib.php';
$casino_retrieve['MEGA'] = 'retrieve_mega_casino_balance';

// IG
$casino_action['IG'] = 'casino/IG/lobby_iggame_action.php';
$casino_lib['IG'] = '/casino/IG/lobby_iggame_lib.php';
$casino_retrieve['IG'] = 'retrieve_ig_casino_balance';

// RG
$casino_action['RG'] = 'casino/RG/lobby_rggame_action.php';
$casino_lib['RG'] = '/casino/RG/lobby_rggame_lib.php';
$casino_retrieve['RG'] = 'retrieve_rg_casino_balance';

/**
 *  取得 casino action
 *
 * @param string $casinoName 娛樂城名稱
 * @param int    $debug 除錯模式，預設 0 為關閉
 *
 * @return string action 路徑
 */
function getCasinoActionUrl(string $casinoName, $debug = 0)
{
	$specialCasino = getCasinoType($casinoName, $debug);
	if ($specialCasino == 1) {
		$action = 'casino/' . $casinoName . '/lobby_' . strtolower($casinoName) . 'game_action.php';
	} else {
		$action = 'casino/lobby_casino_game_action.php';
	}
	return $action;
}


/**
 *  取得娛樂城函式庫
 *
 * @param string $casinoName 娛樂城名稱
 * @param int    $debug 除錯模式，預設 0 為關閉
 *
 * @return string 函式庫路徑
 */
function getCasinoLib(string $casinoName, $debug = 0)
{
	$specialCasino = getCasinoType($casinoName, $debug);
	if ($specialCasino == 1) {
		$lib = '/casino/' . $casinoName . '/lobby_' . strtolower($casinoName) . 'game_lib.php';
	} else {
		$lib = '/casino/lobby_casino_game_lib.php';
	}
	return $lib;
}


/**
 *  取得娛樂城取錢方法
 *
 * @param string $casinoName 娛樂城名稱
 * @param int    $debug 除錯模式，預設 0 為關閉
 *
 * @return string 取錢方法名稱
 */
function getCasinoRetrieveFunc($casinoName, $debug = 0)
{
	$specialCasino = getCasinoType($casinoName, $debug);
	if ($specialCasino == 1) {
		$retrieve = 'retrieve_' . strtolower($casinoName) . '_casino_balance';
	} else {
		$retrieve = 'retrieve_casino_balance';
	}
	return $retrieve;
}


/**
 *  取得陣列內娛樂城帳號
 *
 * @param string $casinoName 娛樂城名稱
 * @param mixed  $dataArr 資料陣列|物件
 *
 * @return string 娛樂城帳號
 */
function getCasinoAccount(string $casinoName, $dataArr)
{
	return getSpecificKeyValueFromArray($casinoName, '_account', $dataArr);
}


/**
 *  取得陣列內娛樂城帳號密碼
 *
 * @param string $casinoName 娛樂城名稱
 * @param mixed  $dataArr 資料陣列|物件
 *
 * @return string 娛樂城帳號密碼
 */
function getCasinoPassword(string $casinoName, $dataArr)
{
	return getSpecificKeyValueFromArray($casinoName, '_password', $dataArr);
}


/**
 *  取得資料陣列內組合鍵的值
 *
 * @param string $prefix 鍵字首
 * @param string $suffix 鍵字尾
 * @param mixed  $dataArr 資料陣列|物件
 *
 * @return mixed 鍵值
 */
function getSpecificKeyValueFromArray(string $prefix, string $suffix, $dataArr)
{
	$value = '';
	$key = strtolower($prefix) . strtolower($suffix);
	if (is_object($dataArr)) {
		$dataArr = get_object_vars($dataArr);
	}
	if (key_exists($key, $dataArr)) {
		$value = $dataArr[$key];
	}
	return $value;
}


/**
 *  取得娛樂城建立來源
 *
 * @param mixed $casinoId  娛樂城ID
 * @param int $debug 除錯模式，預設 0 為關閉
 *
 * @return int 0 表示透過 API 連接，1 為單接娛樂城
 */
function getCasinoType($casinoId, $debug = 0)
{
	$artificial_casino = -1;
	$sql = 'SELECT "artificial_casino" FROM casino_list WHERE "casinoid" = \''. $casinoId .'\';';
	$result = runSQLall($sql, $debug);
	if ($result[0] > 0) {
		$artificial_casino = $result[1]->artificial_casino;
	}
	return $artificial_casino;
}


/**
 *  自動 GCASH TO GTOKEN
 *  將會員本人的 GCASH 餘額，依據設定值轉換為 GTOKEN 餘額，當 gtoken_lock 不是 null 的時候, 不可以轉帳
 *
 * 後台 member_gcash2gtoken_action.php
 * 前台 casino_config.php
 * 前台測試工具 test_unit.php
 * 引用此 function
 *
 * @param  mixed $member_id 會員 ID
 * @param  mixed $gcash2gtoken_account 指定轉帳帳號
 * @param  mixed $balance_input 轉帳金額
 * @param  mixed $password_verify_sha1 會員的提款密碼
 * @param int  $debug 除錯模式，預設 0 為關閉
 * @param mixed $system_note_input 備註
 *
 * @return array 狀態碼。1 為成功，不為 1 表示其他原因導致失敗
 */
function auto_gcash2gtoken($member_id, $gcash2gtoken_account, $balance_input, $password_verify_sha1, $debug = 0, $system_note_input = NULL)
{

	// 會員等級
	global $member_grade_config_detail;
	// 交易的變數 default
	global $transaction_category;
	// 系統現金出納
	global $gcash_cashier_account;
	// 系統游戏币出納
	global $gtoken_cashier_account;
	// 讀取config設定
	global $config;

	$error = [];
	$d = [];

	// 確認帳號是否存在, 存在才繼續。
	$check_acc_sql = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.account = '$gcash2gtoken_account' AND root_member.status = '1';";
	$check_acc = runSQLall($check_acc_sql);
	if ($debug == 1) {
		var_dump($check_acc);
		var_dump($member_id);
		var_dump($balance_input);
		var_dump($password_verify_sha1);
	}

	if ($check_acc[0] == 1) {
		// 帳號正確
		$error['code'] = '1';
		$error['messages'] = '帐号正确';

		// 會員 ID
		$d['member_id'] = $member_id;
		// 來源帳號(會員帳號)
		$d['source_transferaccount'] = $check_acc[1]->account;
		// 目的轉帳帳號 = 來源帳號，同一個人的帳號(會員帳號)
		$d['destination_transferaccount'] = $check_acc[1]->account;
		// 轉帳金額，需要依據會員等級限制每日可轉帳總額。如果不小心被輸入浮點數了，就取整數部位
		$d['transaction_money'] = round($balance_input, 2);
		// 真實轉換, 其實在這個程式還找不到此欄位定義定位
		$d['realcash'] = 1;
		// 摘要資訊
		$d['summary'] = $transaction_category['cashgtoken'];
		// 交易類別
		$d['transaction_category'] = 'cashgtoken';
		// 來源帳號的密碼驗證，驗證後才可以存款
		$d['password_verify_sha1'] = $password_verify_sha1;
		// 系統轉帳文字資訊
		$d['system_note_input'] = $system_note_input;

		// 確認轉帳密碼是否正確，和登入者的轉帳管理員密碼一樣，避免 api 被 xss 直接攻擊, 加上密碼稽核
		// 如果是管理員操作的話, 使用 5566bypass 為預設密碼.
		if ($d['password_verify_sha1'] == $check_acc[1]->withdrawalspassword OR $d['password_verify_sha1'] == '5566bypass') {
			// correct
			$error['code'] = '1';
			$error['messages'] = '转帐密码正确';

			// 轉帳 gtoken 的動作
			// 0.取得目的端使用者完整的資料
			$destination_transferaccount_sql = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.account = '" . $d['destination_transferaccount'] . "';";
			$destination_transferaccount_result = runSQLALL($destination_transferaccount_sql);
			if ($destination_transferaccount_result[0] == 1) {
				$error['code'] = '1';
				$error['messages'] = '取得来源端使用者完整的资料';

				// 1.取得來源端使用者完整的資料
				$source_transferaccount_sql = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.account = '" . $d['source_transferaccount'] . "';";
				$source_transferaccount_result = runSQLALL($source_transferaccount_sql);
				if ($source_transferaccount_result[0] == 1) {
					// 2. 檢查帳戶 gcash現金錢包 是否有錢,且大於 轉帳金額 ,成立才工作,否則結束
					if ($source_transferaccount_result[1]->gcash_balance >= $d['transaction_money']) {
						$error['code'] = '1';
						$error['messages'] = $d['source_transferaccount'] . ' 现金(GCASH)有余额，且大于' . $d['transaction_money'];

						// 來源ID $source_transferaccount_result[1]->id
						// 目的ID $destination_transferaccount_result[1]->id
						// 稽核判斷寫入 備註 的文字及控制稽核金額

						// 存款稽核 * 1 倍
						$d['auditmode_select'] = 'depositaudit';
						// 取得會員對應的轉帳稽核倍數。稽核金額 * 參考會員等級的倍數(預設稽核_gcash轉gtoken存款稽核比公司入款% 在 root_member_grade 資料表)
						$d['auditmode_amount'] = round(($d['transaction_money'] * $member_grade_config_detail->deposit_rate / 100), 2);
						$audit_notes = '稽核金额' . $d['auditmode_amount'];

						// 3.取得現金出納及游戏币出納的 ID 及檢查所剩金額是否足夠轉帳
						// 現金出納 ID
						$gcash_cashier_account_sql = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.account = '" . $gcash_cashier_account . "';";
						$gcash_cashier_account_result = runSQLall($gcash_cashier_account_sql);
						// 游戏币出納 ID
						$gtoken_cashier_account_sql = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.account = '" . $gtoken_cashier_account . "';";
						$gtoken_cashier_account_result = runSQLall($gtoken_cashier_account_sql);
						if ($debug == 1) {
							var_dump($gcash_cashier_account_result);
							var_dump($gtoken_cashier_account_result);
						}
						if ($gcash_cashier_account_result[0] == 1 AND $gtoken_cashier_account_result[0] == 1) {
							// 檢查現金及游戏币的餘額是否大於 0
							if ($gcash_cashier_account_result[1]->gcash_balance > 0 AND $gtoken_cashier_account_result[1]->gtoken_balance > 0) {
								// 取得現金出納及游戏币出納的 ID
								$gcash_cashier_account_id = $gcash_cashier_account_result[1]->id;
								$gtoken_cashier_account_id = $gtoken_cashier_account_result[1]->id;
								if ($debug == 1) {
									var_dump($gcash_cashier_account_id);
									var_dump($gtoken_cashier_account_id);
								}
							} else {
								$error['code'] = '532';
								$error['messages'] = '系统现金帐号或是出纳帐号的余额没了，请联络客服人员处理。';
								echo '<p align="center"><button type="button" class="btn btn-danger">' . $error['messages'] . '</button></p>' . '<script>alert("' . $error['messages'] . '");</script>';
								return ($error);
								die();
							}
						} else {
							$error['code'] = '531';
							$error['messages'] = '现金帐号或是出纳帐号的取得有问题，请联络客服人员处理。';
							echo '<p align="center"><button type="button" class="btn btn-danger">' . $error['messages'] . '</button></p>' . '<script>alert("' . $error['messages'] . '");</script>';
							return ($error);
							die();
						}

						// 交易開始
						// ----------------------------------------------------------------
						// * 將 GCASH 轉 $$ 到 GTOKEN
						// (A) 交易動作為  使用者的 gcash $$ to 系統現金出納
						// (B) 交易動作為  系統的游戏币出納 to $$ 到使用者的 gtoken
						// ----------------------------------------------------------------
						$transaction_money_sql = 'BEGIN;';
						// ----------------------------------------------------------------
						// (A) 交易動作為  使用者的 gcash $$ to 系統現金出納
						// ----------------------------------------------------------------
						$withdrawalTransactionId = get_transaction_id($_SESSION['member']->account, 'w');

						// 操作：進行轉帳，在 會員錢包及娛樂城錢包(root_member_wallets)資料表 轉移金額
						// 會員帳號 gcash現金錢包 餘額減去 transaction_money轉帳金額
						$transaction_money_sql = $transaction_money_sql .
							'UPDATE root_member_wallets SET changetime = NOW(), gcash_balance = (SELECT (gcash_balance-' . $d['transaction_money'] . ') as amount FROM root_member_wallets WHERE id = ' . $source_transferaccount_result[1]->id . ') WHERE id = ' . $source_transferaccount_result[1]->id . ';';
						// 目的(系統出納)帳號加入上 transaction_money轉帳金額
						$transaction_money_sql = $transaction_money_sql .
							'UPDATE root_member_wallets SET changetime = NOW(), gcash_balance = (SELECT (gcash_balance+' . $d['transaction_money'] . ') as amount FROM root_member_wallets WHERE id = ' . $gcash_cashier_account_id . ') WHERE id = ' . $gcash_cashier_account_id . ';';

						// 操作：紀錄轉帳資訊在 現金 GCASH 存摺(root_member_gcashpassbook)資料表
						// 資料庫 新增 1 筆 給會員看的紀錄：
						// 來源帳號(source_transferaccount) 轉帳到 現金出納($gcash_cashier_account_id)
						// 金額(transaction_money)
						$source_notes = "(帐号" . $d['source_transferaccount'] . " 现金转到同帐号代币)";
						$transaction_money_sql = $transaction_money_sql .
							'INSERT INTO "root_member_gcashpassbook" ("transaction_time", "deposit", "withdrawal", "system_note", "member_id", "currency", "summary", "source_transferaccount",  "realcash", "destination_transferaccount", "transaction_category", "balance", "transaction_id")' .
							"VALUES ('now()', '0', '" . $d['transaction_money'] . "', '" . $source_notes . "', '" . $d['member_id'] . "', '".$config['currency_sign']."', '" . $d['summary'] . "', '" . $d['source_transferaccount'] . "','" . $d['realcash'] . "', '" . $gcash_cashier_account . "', '" . $d['transaction_category'] . "', (SELECT gcash_balance FROM root_member_wallets WHERE id = " . $source_transferaccount_result[1]->id . "), '" . $withdrawalTransactionId . "');";

						// PGSQL 新增 1 筆 給系統出納看的紀錄：
						// 目的帳號(destination_transferaccount) 收到來自 來源帳號(source_transferaccount) 金額(transaction_money)
						$destination_notes = "(帐号" . $d['source_transferaccount'] . "转帐到" . $gcash_cashier_account . "帐号, " . $audit_notes . ')' . $d['system_note_input'];
						$transaction_money_sql = $transaction_money_sql .
							'INSERT INTO "root_member_gcashpassbook" ("transaction_time", "deposit", "withdrawal", "system_note", "member_id", "currency", "summary", "source_transferaccount",  "realcash", "destination_transferaccount", "transaction_category", "balance", "transaction_id")' .
							"VALUES ('now()', '" . $d['transaction_money'] . "', '0', '" . $destination_notes . "', '" . $d['member_id'] . "', '".$config['currency_sign']."', '" . $d['summary'] . "', '" . $gcash_cashier_account . "', '" . $d['realcash'] . "', '" . $d['source_transferaccount'] . "', '" . $d['transaction_category'] . "', (SELECT gcash_balance FROM root_member_wallets WHERE id = " . $gcash_cashier_account_id . "), '" . $withdrawalTransactionId . "');";

						// ----------------------------------------------------------------
						// (B) 交易動作為  系統的游戏币出納 to $$ 到使用者的 gtoken
						// ----------------------------------------------------------------
						// 取得轉帳用 transaction id
						$depositTransactionId = get_transaction_id($_SESSION['member']->account, 'd');

						// 操作：進行轉帳，在 會員錢包及娛樂城錢包(root_member_wallets)資料表 進行遊戲幣轉移
						// 系統出納帳號 gtoken代幣錢包 餘額減去 transaction_money轉帳金額
						$transaction_money_sql = $transaction_money_sql .
							'UPDATE root_member_wallets SET changetime = NOW(), gtoken_balance = (SELECT (gtoken_balance-' . $d['transaction_money'] . ') as amount FROM root_member_wallets WHERE id = ' . $gtoken_cashier_account_id . ') WHERE id = ' . $gtoken_cashier_account_id . ';';
						// 會員帳號 gtoken代幣錢包 加上 transaction_money轉帳金額
						$transaction_money_sql = $transaction_money_sql .
							'UPDATE root_member_wallets SET changetime = NOW(), gtoken_balance = (SELECT (gtoken_balance+' . $d['transaction_money'] . ') as amount FROM root_member_wallets WHERE id = ' . $source_transferaccount_result[1]->id . ') WHERE id = ' . $source_transferaccount_result[1]->id . ';';

						// 操作：紀錄轉帳資訊在 現金 GCASH 存摺(root_member_gcashpassbook)資料表
						// 資料庫新增 1 筆給會員看的紀錄：
						// 現金出納($gcash_cashier_account_id)帳號 轉帳到 來源帳號(source_transferaccount)
						// 金額 transaction_money (GTOKEN)
						$source_notes = "(帐号" . $d['source_transferaccount'] . '现金转代币, ' . $audit_notes . ')' . $d['system_note_input'];
						$transaction_money_sql = $transaction_money_sql .
							'INSERT INTO "root_member_gtokenpassbook" ("transaction_time", "auditmode", "auditmodeamount", "deposit", "withdrawal", "system_note", "member_id", "currency", "summary", "source_transferaccount",  "realcash", "destination_transferaccount", "transaction_category", "balance", "transaction_id")' .
							"VALUES ('now()', '" . $d['auditmode_select'] . "', '" . $d['auditmode_amount'] . "', '" . $d['transaction_money'] . "', '0', '" . $source_notes . "', '" . $d['member_id'] . "', '".$config['currency_sign']."', '" . $d['summary'] . "', '" . $d['source_transferaccount'] . "','" . $d['realcash'] . "', '" . $gtoken_cashier_account . "', '" . $d['transaction_category'] . "', (SELECT gtoken_balance FROM root_member_wallets WHERE id = " . $source_transferaccount_result[1]->id . "), '" . $depositTransactionId . "');";
						// 資料庫新增 1 筆給游戏币出納人員看的：
						// 目的帳號($destination_transferaccount) 收到來自 來源帳號($source_transferaccount) 轉帳
						// 金額 transaction_money (GTOKEN)
						$destination_notes = "(帐号" . $gtoken_cashier_account . "存款到," . $d['source_transferaccount'] . $audit_notes . ')' . $d['system_note_input'];
						$transaction_money_sql = $transaction_money_sql .
							'INSERT INTO "root_member_gtokenpassbook" ("transaction_time", "auditmode", "auditmodeamount", "deposit", "withdrawal", "system_note", "member_id", "currency", "summary", "source_transferaccount",  "realcash", "destination_transferaccount", "transaction_category", "balance", "transaction_id")' .
							"VALUES ('now()', '" . $d['auditmode_select'] . "', '" . $d['auditmode_amount'] . "', '0', '" . $d['transaction_money'] . "', '" . $destination_notes . "', '" . $d['member_id'] . "', '".$config['currency_sign']."', '" . $d['summary'] . "', '" . $gtoken_cashier_account . "', '" . $d['realcash'] . "', '" . $d['source_transferaccount'] . "', '" . $d['transaction_category'] . "', (SELECT gtoken_balance FROM root_member_wallets WHERE id = " . $gtoken_cashier_account_id . "), '" . $depositTransactionId . "');";

						// commit 提交
						$transaction_money_sql = $transaction_money_sql . 'COMMIT;';

						if ($debug == 1) {
							echo '<pre>';
							print_r($transaction_money_sql);
							echo '</pre>';
						}

						// 執行 transaction sql
						$transaction_money_result = runSQLtransactions($transaction_money_sql);
						if ($transaction_money_result) {
							$error['code'] = '1';
							$transaction_money_html = money_format('%i', $d['transaction_money']);
							$error['messages'] = '成功将' . $d['source_transferaccount'] . '帐号 GCASH 转换为 GTOKEN 金额:' . $transaction_money_html;
							$error['transaction_id'] = ['deposit' => $depositTransactionId, 'withdrawal' => $withdrawalTransactionId];
						} else {
							$error['code'] = '7';
							$error['messages'] = 'SQL转帐失败从' . $d['source_transferaccount'] . '到' . $d['destination_transferaccount'] . '金額' . $d['transaction_money'];;
						}
					} else {
						$error['code'] = '6';
						$error['messages'] = $d['source_transferaccount'] . '余额不足于' . $d['transaction_money'];
					}

				} else {
					$error['code'] = '4';
					$error['messages'] = '查不到来源端的使用者' . $d['source_transferaccount'] . '资料。';
				}

			} else {
				$error['code'] = '5';
				$error['messages'] = '查不到目的端的使用者' . $d['destination_transferaccount'] . '资料。';
			}

		} else {
			// incorrect
			$error['code'] = '3';
			$error['messages'] = $d['source_transferaccount'] . '来源帐号的转帐密码不正确';
		}

	} else {
		// error return
		$error['code'] = '2';
		$error['messages'] = '帐号有问题' . $check_acc[1]->account;
	}

	if ($debug == 1) {
		var_dump($error);
	}

	return ($error);
}


/**
 *  將 GCASH 依據設定值，自動加值到 GTOKEN 上面
 *  需要搭配上面的 auto_gcash2gtoken() 使用才可以。
 *  操作者通常只有會員本人, 所以預設值為同一人。
 *
 * @param string $userid 會員 ID
 * @param int    $debug  除錯模式，預設 0 為關閉
 *
 * @return array 交易資訊。回傳 code = 1 為成功，code != 1 為其他原因導致失敗
 */
function Transferout_GCASH_GTOKEN_balance($userid, $debug = 0)
{
	$r = [];

	// 取得會員及會員錢包資料
	$user_sql = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.id = '$userid';";
	$user_result = runSQLall($user_sql);

	// 取得系統預設會員存款別
	$deposit_currency_sql = "SELECT value FROM root_protalsetting WHERE setttingname = 'default' AND name = 'member_deposit_currency';";
	$setting_member_deposit_currency = runSQLall($deposit_currency_sql)[1]->value;

	// 是否有會員資料 and 游戏币不在娛樂城(gtoken_lock == null) 才可以轉，游戏币在娛樂城的時後不可以儲值
	if ($user_result[0] == 1) {
		// 允許自動儲值
		if ($user_result[1]->auto_gtoken == 1) {
			// 檢查 gtoken代幣錢包 餘額是否還有錢？且餘額 小於 最低自動轉帳金額(auto_min_gtoken)
			if ($user_result[1]->gtoken_balance <= $user_result[1]->auto_min_gtoken) {
				// gcash現金錢包 是否還有錢
				if ($user_result[1]->gcash_balance < 1) {
					$logger = '你已經沒有現金餘額了，請透過銀行或線上存款方式儲值。';
					$r['code'] = 404;
					$r['messages'] = $logger;
				} else {
					// 判斷可以儲值多少？
					if ($user_result[1]->gcash_balance >= $user_result[1]->auto_once_gotken) {
						// 1. 當 gcash現金錢包(gcash_balance) 餘額 大於等於 每次儲值金額(auto_once_gtoken) 時，儲值 每次儲值金額(auto_once_gtoken)
						$auto_result = auto_gcash2gtoken($userid, $user_result[1]->account, $user_result[1]->auto_once_gotken, $user_result[1]->withdrawalspassword, $debug, NULL);

						if ($auto_result['code'] == 1) {
							$logger = '會員' . $user_result[1]->account . '現金轉游戏币' . $user_result[1]->auto_once_gotken . '完成';
							$r['code'] = 1;
							$r['messages'] = $logger;
						} else {
							$logger = '會員' . $user_result[1]->account . '現金轉游戏币' . $user_result[1]->auto_once_gotken . '失敗';
							$r['code'] = 551;
							$r['messages'] = $logger;
						}
					} else {
						// 2. 當 每次儲值金額(auto_once_gtoken) 大於現金時，儲值 gcash現金錢包(gcash_balance) 的全部金額
						$auto_result = auto_gcash2gtoken($userid, $user_result[1]->account, $user_result[1]->gcash_balance, $user_result[1]->withdrawalspassword, $debug, NULL);

						if ($auto_result['code'] == 1) {
							$logger = '會員' . $user_result[1]->account . '現金' . $user_result[1]->gcash_balance . '轉游戏币' . $user_result[1]->gcash_balance . '完成';
							$r['code'] = 1;
							$r['messages'] = $logger;
						} else {
							$logger = '會員' . $user_result[1]->account . '現金' . $user_result[1]->gcash_balance . '轉游戏币' . $user_result[1]->gcash_balance . '失敗';
							$r['code'] = 552;
							$r['messages'] = $logger;
						}
					}
				}
			} else {
				$logger = '你的游戏币錢包還有餘額' . $user_result[1]->gtoken_balance . '，且大於最低自動轉帳餘額' . $user_result[1]->auto_min_gtoken . '暫停儲值';
				$r['code'] = 403;
				$r['messages'] = $logger;
			}
		} else {
			// 自動儲值關閉
			if ($setting_member_deposit_currency == 'gcash') {
				// 未設定自動儲值
				$logger = '你尚未设定允许自动储值转换，请至「会员钱包」功能处将自动储值转换功能打开。';
				$r['code'] = 401;
				$r['messages'] = $logger;
			} elseif ($user_result[1]->gtoken_balance < '1') {
				// gtoken代幣錢包 餘額不足進入遊戲
				$logger = '游戏币余额不足，请透过银行或线上存款方式储值。';
				$r['code'] = 405;
				$r['messages'] = $logger;
			} else {
				// gtoken代幣錢包 餘額足夠進入遊戲
				$logger = '你的游戏币钱包还有余额' . $user_result[1]->gtoken_balance . '。';
				$r['code'] = 403;
				$r['messages'] = $logger;
			}
		}
	} else {
		// 沒有會員資料
		$logger = '沒有這個會員(id=' . $userid . ')的資料或是游戏币已在娛樂城使用中，暫時不能自動儲值。';
		$r['code'] = 402;
		$r['messages'] = $logger;
	}

	if ($debug == 1) {
		var_dump($r);
	}

	return ($r);
}


/**
 *  產生會員轉換娛樂城的紀錄
 *
 * @param mixed $source 來源
 * @param mixed $destination 目的地
 * @param mixed $token 轉帳金額
 * @param mixed $note 附註
 * @param mixed $logstatus 紀錄等級
 * @param mixed $transaction_id  transaction id
 * @param int $casino_transfer_status 娛樂城轉帳狀態，預設 0 為平台內帳款轉移或非轉帳API功能
 *
 * @return int|string|null 轉帳結果
 */
function member_casino_transferrecords($source, $destination, $token, $note, $logstatus, $transaction_id = null, $casino_transfer_status = 0)
{
	global $config;

	// 定義log level所包含要記錄的訊息層級
	$log_level_list = [
		'debug' => ['success', 'info', 'fail', 'warning'],
		'info' => ['success', 'info', 'fail', 'warning'],
		'warning' => ['success', 'fail', 'warning'],
		'error' => ['success', 'fail']
	];

	$member_transferrecords_result = '';

	if (in_array($logstatus, $log_level_list[$config['casino_transferlog_level']])) {
		date_default_timezone_set('America/St_Thomas');
		$source = filter_var($source, FILTER_SANITIZE_MAGIC_QUOTES);
		$destination = filter_var($destination, FILTER_SANITIZE_MAGIC_QUOTES);
		$token = filter_var($token, FILTER_SANITIZE_MAGIC_QUOTES);
		$note = preg_replace('/([\'])/ui', '\'\'', filter_var($note, FILTER_SANITIZE_MAGIC_QUOTES));

		$memberid = $_SESSION['member']->id;
		if (isset($_SERVER["REMOTE_ADDR"])) {
			$agent_ip = $_SERVER["REMOTE_ADDR"];
		} else {
			$agent_ip = 'no_remote_addr';
		}

		// 操作人員使用的 browser 指紋碼, 有可能會沒有指紋碼. JS close 的時候會發生
		if (isset($_SESSION['fingertracker'])) {
			$fingertracker = $_SESSION['fingertracker'];
		} else {
			$fingertracker = 'no_fingerprinting';
		}

		$member_transferrecords_sql = <<<SQL
		    INSERT INTO "root_member_casino_transferrecords" (
		    	"memberid",
		    	"source",
		    	"destination",
		    	"token",
		    	"occurtime",
		    	"agent_ip",
		    	"fingerprint",
		    	"note",
		    	"status",
		    	"transaction_id",
		    	"casino_transfer_status")
		    VALUES (
		    	'$memberid',
		    	'$source',
		    	'$destination',
		    	'$token',
		    	now(),
		    	'$agent_ip',
		    	'$fingertracker',
		    	'$note',
		    	'$logstatus',
		    	'$transaction_id',
		    	'$casino_transfer_status');
SQL;

		$member_transferrecords_result = runSQL($member_transferrecords_sql);
	}

	return ($member_transferrecords_result);
}


/**
 *  檢查後台是否有在協助會員操作錢包轉錢至娛樂或是自娛樂城取錢
 *
 * @return int
 */
function agent_walletscontrol_check()
{
	global $redisdb;

	$member_lock_key = sha1($_SESSION['member']->account . 'AgentLock');

	$redis = new Redis();
	// 2 秒 timeout
	if ($redis->pconnect($redisdb['host'], 6379, 1)) {
		// success
		if ($redis->auth($redisdb['auth'])) {
			// 認證成功
		} else {
			return (0);
			die('Redisdb authentication failed');
		}
	} else {
		// error
		return (0);
		die('Redisdb Connection Failed');
	}

	// 選擇 DB , member 使用者自訂的 session 放在 db 2
	$redis->select(2);

	$alive_userkeys = $redis->get("$member_lock_key");
	// 同一個使用者，只能有一個登入.沒有登入使用者的時候，應該是 false
	if (isset($alive_userkeys) AND $alive_userkeys != '') {
		return (1);
	} else {
		return (0);
	}
}


// ---------------------------------------------
// 輸出給首頁使用的遊戲列表
// ---------------------------------------------
function home_gamelist($gamelist_template = array(), $generate_type = 'default', $debug = 0)
{
	global $tr;
	global $config;
	global $cdnrooturl;
	global $gamelobby_setting;

	$gamelist_div = '
  	<script type="text/javascript" language="javascript" class="init">
  	var ss = \'' . isset($_SESSION["member"]) . '\';</script>
  	<link rel="stylesheet" href="' . $config['website_baseurl'] . '/casino/gamelobby.css">
  	';
	switch ($generate_type) {
		case 'landscape':
			$gamelist_div .= '<script src="' . $config['website_baseurl'] . '/casino/gamelobby_m_landscape.js"></script>';
			break;
		default:
			$gamelist_div .= '<script src="' . $config['website_baseurl'] . '/casino/gamelobby_m.js"></script>';
			break;
	}

	if ($_SESSION['site_mode'] == 'mobile' AND count($gamelist_template) == 4) {
		$active_tag = array();
		if (preg_match('/gamelobby\.php/', $_SERVER['SCRIPT_NAME'])) {
			$gamelist_div = '';
			if (isset($_GET['mgc'])) {
				$active_tag[$_GET['mgc']] = '1';
			} else {
				$active_tag['game'] = '1';
			}
		}

		$mct_item_arr = array();
		$mctag_item_arr = array();
		$mcmtag_item_arr = array();
		foreach ($gamelobby_setting['main_category_info'] as $mctid => $mct_arr) {
			if ($mct_arr['open'] == 1) {
				if (isset($tr['menu_' . strtolower($mctid)])) {
					$mct_name = $tr['menu_' . strtolower($mctid)];
				} elseif (isset($tr[$mct_arr['name']])) {
					$mct_name = $tr[$mct_arr['name']];
				} else {
					$mct_name = $mct_arr['name'];
				}
				// var_dump($mct_name);
				$mct_item_tmp = str_replace('{mctid}', $mctid, $gamelist_template['mct_item']);
				$mctag_item_tmp = str_replace('{mctid}', $mctid, $gamelist_template['mctag_item']);
				$mcmtag_item_tmp = str_replace('{mctid}', $mctid, $gamelist_template['mcmtag_item']);
				$mct_item_tmp = str_replace('{mct_name}', $mct_name, $mct_item_tmp);
				$mctag_item_tmp = str_replace('{mct_name}', $mct_name, $mctag_item_tmp);
				$mcmtag_item_tmp = str_replace('{mct_name}', $mct_name, $mcmtag_item_tmp);

				$mct_item_tmp = (isset($active_tag[$mctid])) ? str_replace('{mctactive}', 'active', $mct_item_tmp) : str_replace('{mctactive}', '', $mct_item_tmp);

				$mct_item_arr[$mct_arr['order']] = $mct_item_tmp;
				$mctag_item_arr[$mct_arr['order']] = $mctag_item_tmp;
				$mcmtag_item_arr[$mct_arr['order']] = $mcmtag_item_tmp;
			}
		}

		$notClassified_hot_item = str_replace('{mctid}', 'notclassified', $gamelist_template['mcmtag_item']);
		$notClassified_hot_item = str_replace('{mct_name}', $tr['HotGame'], $notClassified_hot_item);

		ksort($mct_item_arr);
		$mct_item = implode("\n", $mct_item_arr);
		$mctag_item = implode("\n", $mctag_item_arr);
		$mcmtag_item = implode("\n", $mcmtag_item_arr);

		$mcmtag_item = $notClassified_hot_item . $mcmtag_item;

		$gamelist_div_tmp = str_replace('{mct_item}', $mct_item, $gamelist_template['main']);
		$gamelist_div_tmp = str_replace('{mctag_item}', $mctag_item, $gamelist_div_tmp);
		$gamelist_div_tmp = str_replace('{mcmtag_item}', $mcmtag_item, $gamelist_div_tmp);

		$gamelist_div .= $gamelist_div_tmp;
	} else {
		// 取得有啟用的 casino
		$casino_list_sql = 'SELECT DISTINCT casino_id,casino_name FROM casino_gameslist JOIN casino_list ON casino_gameslist.casino_id = casino_list.casinoid WHERE casino_list.open = \'1\';';
		$casino_list_result = runSQLall($casino_list_sql, 0, 'r');
		$casinoitem_state = 0;
		$casino_item = '';

		for ($i = 1; $i <= $casino_list_result[0]; $i++) {
			$casinoname = empty($tr[$casino_list_result[$i]->casino_name]) ? $casino_list_result[$i]->casino_name : $tr[$casino_list_result[$i]->casino_name];
			if ($i == '1') {
				$casino_item = $casino_item . '<li class="li' . $i . ' " data-cid="' . $casino_list_result[$i]->casino_id . '"><a href="javascript:void(0);"><img class="gameitem-img" src="' . $cdnrooturl . 'casinologo/' . $casino_list_result[$i]->casino_id . '.png" alt=""><span class="gt selected" id="' . $casino_list_result[$i]->casino_id . '">' . $casinoname . '</span></a></li>';
				$casinoitem_state = 1;
			} else {
				$casino_item = $casino_item . '<li class="li' . $i . ' " data-cid="' . $casino_list_result[$i]->casino_id . '"><a href="javascript:void(0);"><img class="gameitem-img" src="' . $cdnrooturl . 'casinologo/' . $casino_list_result[$i]->casino_id . '.png" alt=""><span class="gt" id="' . $casino_list_result[$i]->casino_id . '">' . $casinoname . '</span></a></li>';
			}
		}

		$gamelist_div .= '
    <div id="casino-tab">
    <button id="casino-pre" class="btn" onclick="slide(\'pre\');"><i class="fas fa-angle-left fa-2x"></i></button>
    <div id="casino">
      <ul class="tabUl clearfix" style="left:0px;">
        ' . $casino_item . '
      </ul>
    </div>
    <button id="casino-next" class="btn" onclick="slide(\'next\');"><i class="fas fa-angle-right fa-2x"></i></button>
    </div>
    <div id="gametable"></div>';
	}
	if ($config['businessDemo'] == 1) $gamelist_div .= '
  <!-- Modal -->
  <div class="modal fade" id="bsdemo" role="dialog">
    <div class="modal-dialog">
  <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">業務展示站台</h4>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
               <center><p>目前所在站台為業務展示站台，不支援進此娛樂城</p></center>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>';
	echo $gamelist_div;
}
// ---------------------------------------------
// END
// ---------------------------------------------
