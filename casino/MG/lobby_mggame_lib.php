<?php
// ----------------------------------------------------------------------------
// Features:	Casino 的專用函式庫
// File Name:	lobby_mggame_lib.php
// Author:		Barkley, fix by Ian
// Related:
// Log:
// ----------------------------------------------------------------------------
// 2017.6.2
// 此函式提供給 lobby_mggameh5_action.php 使用,配合MG變更主要API為Restful，修改自
// lobby_mggame_lib.php, 負責娛樂城的轉換操作
// ----------------------------------------------------------------------------
/*
// function 索引及說明：
// -------------------
1. MG API 文件函式及用法 sample , 操作 MG API (by totalegame)
mg_restfulapi($method, $debug=0, $MG_API_data)

2. 依據使用者帳號資訊，檢查遠端 MG 的帳號是否存在，不存在就建立
create_casino_mg_restful_account()

3.將傳入的 member id 值，取得他的 DB GTOKEN 餘額並全部傳送到 MG CASINO 上
把本地端的資料庫 root_member_wallets 的 GTOKEN_LOCK設定為 MG 餘額儲存在 mg_balance 上面
transferout_gtoken_mg_restful_casino_balance($member_id, $debug = 0)

4.
自動 GCASH TO GTOKEN
將會員本人的 GCASH 餘額，依據設定值轉換為 GTOKEN 餘額。
當 gtoken_lock 不是 null 的時候, 不可以轉帳.
auto_gcash2gtoken($member_id, $gcash2gtoken_account, $balance_input, $password_verify_sha1, $debug=0, $system_note_input)

5. 將 GCASH 依據設定值，自動加值到 GTOKEN 上面
需要搭配上面的 auto_gcash2gtoken() 使用才可以。
操作者通常只有會員本人, 所以預設值為同一人。
Transferout_GCASH_GTOKEN_balance($userid,$debug=0)

5. 取回 MG Casino 的餘額，並檢查上次離開時和目前的差額得出派彩金額，紀錄存簿。 v2017.1.2
db_mg2gpk_balance($gtoken_cashier_account, $mg_balance_api, $mg2gpk_balance, $mg_balance_db, $debug=0 )

6. 取回 MG Casino 的餘額 -- retrieve_mg_restful_casino_balance
retrieve_mg_restful_casino_balance($member_id, $debug=0)

7. 產生可以連到 MG FLASH game 的 url 位址
mg_flash_gamecode_restful_url($mg2_account, $mg2_password, $mg2_gamecode)

8. 產生可以連到 MG HTML5 game 的 url 位址
mg_html5_gamecode_restful_url($mg2_account, $mg2_password, $mg2_gamecode)

 */

// ----------------------------------------------------------------------------
// MG API 文件函式及用法 sample , 操作 MG API (by totalegame)
// ----------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// API 動作方式 sample code by barkley 2016.8.25
// ----------------------------------------------------------------------------

/*
// 動作： GetAccountDetails 取得目前 API 的版本
// 將輸入的資料以 array 方式，放入變數傳道 function 內
$MG_API_data  = array(
'AccountNumber' => 'gpk255661133'
);
// $MG_API_result = mg_restfulapi('API 方法', 除錯=[0|1], 資料參數);
$MG_API_result = mg_restfulapi('GetAccountDetails', 1, $MG_API_data);
// 如果沒有 $MG_API_result->GetAccountDetailsResult->ErrorCode 錯誤，就是取得的資訊是正確的。
if($MG_API_result['errorcode'] == 0 AND $MG_API_result['Status']->ErrorCode == 0 AND $MG_API_result['count'] > 0)  {
// 成立後的動作
}
 */

/*
// 動作： GetAccountBalance 取得目前 餘額
// 需注意，這邊傳入 $MG_API_data 中的 'Accounts' 是一個 array ，可以同時查詢多個member的帳號，
// 但如果有同時查多位時，其輸出的result會是用Array回傳的
$delimitedAccountNumbers[] = 'gpk255661133';
$MG_API_data  = array(
'Accounts' => $delimitedAccountNumbers
);
$MG_API_result = mg_restfulapi('GetAccountBalance', 0, $MG_API_data);
if($MG_API_result['errorcode'] == 0 AND $MG_API_result['Status']->ErrorCode == 0 AND $MG_API_result['count'] > 0) {
$logger = '會員帳號 '.$MG_API_result['Result']['0']->AccountNumber.'，餘額 '.$MG_API_result['Result']['0']->Balance;
// 成立後的動作
var_dump($MG_API_result);
}
 */
/*
/home/dev2gpk2demo/web/gpk2/lobby_mggameh5_action.php:448:
array (size=5)
'curl_status' => int 0
'count' => int 1
'errorcode' => int 0
'Status' =>
object(stdClass)[77]
public 'ErrorCode' => int 0
public 'ErrorName' => string 'SUCCEED' (length=7)
public 'ErrorMessage' => string '' (length=0)
public 'ReferenceNumber' => string '' (length=0)
'Result' =>
array (size=1)
0 =>
object(stdClass)[3]
public 'AccountNumber' => string 'kt120000001026' (length=14)
public 'CreditBalance' => float 501.1
public 'Balance' => float 501.1
 */

/*
// 動作： Deposit 帳戶存入
$MG_API_data  = array(
'AccountNumber' => 'gpk255661133',
'Amount' => 200 ,
'TransactionReferenceNumber' => '200'
);
$MG_API_result = mg_restfulapi('Deposit', 0, $MG_API_data);
if($MG_API_result['errorcode'] == 0 AND $MG_API_result['Status']->ErrorCode == 0 AND $MG_API_result['count'] > 0) {
var_dump($MG_API_result);
}
 */

/*
// 動作： Withdrawal 帳戶取款
$MG_API_data  = array(
'AccountNumber' => 'gpk255661133',
'Amount' => 100 ,
'TransactionReferenceNumber' => '100'
);
$MG_API_result = mg_restfulapi('Withdrawal', 0, $MG_API_data);
if($MG_API_result['errorcode'] == 0 AND $MG_API_result['Status']->ErrorCode == 0 AND $MG_API_result['count'] > 0) {
var_dump($MG_API_result);
}
 */

/*
// 動作： GetBettingProfileList
$MG_API_data  = array( );
$MG_API_result = mg_restfulapi('GetBettingProfileList', 0, $MG_API_data);
var_dump($MG_API_result);
 */
/*
/home/dev2gpk2demo/web/gpk2/resttest.php:39:
object(stdClass)[2]
public 'Status' =>
object(stdClass)[1]
public 'ErrorCode' => int 0
public 'ErrorName' => string 'SUCCEED' (length=7)
public 'ErrorMessage' => string '' (length=0)
public 'ReferenceNumber' => string '' (length=0)
public 'Result' =>
array (size=17)
0 =>
object(stdClass)[3]
public 'Category' => string 'LGBetProfile' (length=12)
public 'ProfileId' => int 955
public 'ProfileName' => string 'CNY1A LB 5/50 30/300 50/500 LR 5/5 10/10 50/50 BJ 50/500' (length=56)
public 'ProfileDesc' => string 'CNY1A LB 5/50 30/300 50/500 LR 5/5 10/10 50/50 BJ 50/500' (length=56)
1 =>
object(stdClass)[4]
public 'Category' => string 'LGBetProfile' (length=12)
public 'ProfileId' => int 958
public 'ProfileName' => string 'CNY4A LB 10/500 100/5000 200/50000 LR 5/5 10/10 50/50 BJ 50/500' (length=63)
public 'ProfileDesc' => string 'CNY4A LB 10/500 100/5000 200/50000 LR 5/5 10/10 50/50 BJ 50/500' (length=63)
2 =>
object(stdClass)[5]
public 'Category' => string 'LGBetProfile' (length=12)
public 'ProfileId' => int 956
public 'ProfileName' => string 'CNY2A LB 10/100 50/500 100/1000 LR 5/5 10/10 50/50 BJ 50/500' (length=60)
public 'ProfileDesc' => string 'CNY2A LB 10/100 50/500 100/1000 LR 5/5 10/10 50/50 BJ 50/500' (length=60)
3 =>
object(stdClass)[6]
public 'Category' => string 'LGBetProfile' (length=12)
public 'ProfileId' => int 957
public 'ProfileName' => string 'CNY3A LB 50/500 100/1000 200/2000 LR 5/5 10/10 50/50 BJ 50/500' (length=62)
public 'ProfileDesc' => string 'CNY3A LB 50/500 100/1000 200/2000 LR 5/5 10/10 50/50 BJ 50/500' (length=62)
4 =>
object(stdClass)[7]
public 'Category' => string 'LGBetProfile' (length=12)
public 'ProfileId' => int 959
public 'ProfileName' => string 'CNY5A LB 200/10000 300/30000 500/50000 LR 5/5 10/10 50/50 BJ 50/500' (length=67)
public 'ProfileDesc' => string 'CNY5A LB 200/10000 300/30000 500/50000 LR 5/5 10/10 50/50 BJ 50/500' (length=67)
5 =>
object(stdClass)[8]
public 'Category' => string 'LGBetProfile' (length=12)
public 'ProfileId' => int 960
public 'ProfileName' => string 'THB6 LB 5000/50000 15000/150000 25000/250000 LR 25/25 50/50 250/250 BJ 250/2500' (length=79)
public 'ProfileDesc' => string 'THB6 LB 5000/50000 15000/150000 25000/250000 LR 25/25 50/50 250/250 BJ 250/2500' (length=79)
6 =>
object(stdClass)[9]
public 'Category' => string 'LGBetProfile' (length=12)
public 'ProfileId' => int 961
public 'ProfileName' => string 'THB5 LB 3000/30000 15000/150000 30000/300000 LR 25/25 50/50 250/250 BJ 250/2500' (length=79)
public 'ProfileDesc' => string 'THB5 LB 3000/30000 15000/150000 30000/300000 LR 25/25 50/50 250/250 BJ 250/2500' (length=79)
7 =>
object(stdClass)[10]
public 'Category' => string 'LGBetProfile' (length=12)
public 'ProfileId' => int 962
public 'ProfileName' => string 'THB4 LB 1000/10000 5000/50000 10000/100000 LR 25/25 50/50 250/250 BJ 250/2500' (length=77)
public 'ProfileDesc' => string 'THB4 LB 1000/10000 5000/50000 10000/100000 LR 25/25 50/50 250/250 BJ 250/2500' (length=77)
8 =>
object(stdClass)[11]
public 'Category' => string 'LGBetProfile' (length=12)
public 'ProfileId' => int 963
public 'ProfileName' => string 'THB3 LB 250/2500 1250/12500 2500/25000 LR 25/25 50/50 250/250 BJ 250/2500' (length=73)
public 'ProfileDesc' => string 'THB3 LB 250/2500 1250/12500 2500/25000 LR 25/25 50/50 250/250 BJ 250/2500' (length=73)
9 =>
object(stdClass)[12]
public 'Category' => string 'LGBetProfile' (length=12)
public 'ProfileId' => int 964
public 'ProfileName' => string 'THB2 LB 50/500 250/2500 500/5000 LR 25/25 50/50 250/250 BJ 250/2500' (length=67)
public 'ProfileDesc' => string 'THB2 LB 50/500 250/2500 500/5000 LR 25/25 50/50 250/250 BJ 250/2500' (length=67)
10 =>
object(stdClass)[13]
public 'Category' => string 'LGBetProfile' (length=12)
public 'ProfileId' => int 965
public 'ProfileName' => string 'THB1 LB 25/250 150/1500 250/2500 LR 25/25 50/50 250/250 BJ 250/2500' (length=67)
public 'ProfileDesc' => string 'THB1 LB 25/250 150/1500 250/2500 LR 25/25 50/50 250/250 BJ 250/2500' (length=67)
11 =>
object(stdClass)[14]
public 'Category' => string 'LGBetProfile' (length=12)
public 'ProfileId' => int 1027
public 'ProfileName' => string 'CNY6A LB 10/5000 100/50000 500/100000 LR 1/1 2/2 5/5 BJ 25/5000' (length=63)
public 'ProfileDesc' => string 'CNY6A LB 10/5000 100/50000 500/100000 LR 1/1 2/2 5/5 BJ 25/5000' (length=63)
12 =>
object(stdClass)[15]
public 'Category' => string 'LGBetProfile' (length=12)
public 'ProfileId' => int 1028
public 'ProfileName' => string 'CNY7A LB 20/10000 200/100000 1000/200000 LR 2/2 5/5 10/10 BJ 25/5000' (length=68)
public 'ProfileDesc' => string 'CNY7A LB 20/10000 200/100000 1000/200000 LR 2/2 5/5 10/10 BJ 25/5000' (length=68)
13 =>
object(stdClass)[16]
public 'Category' => string 'RNGMaxPayout' (length=12)
public 'ProfileId' => int -1
public 'ProfileName' => string 'Standard' (length=8)
public 'ProfileDesc' => string 'Standard' (length=8)
14 =>
object(stdClass)[17]
public 'Category' => string 'RNGMaxPayout' (length=12)
public 'ProfileId' => int 2529
public 'ProfileName' => string 'L1 – Up to 1M CNY' (length=19)
public 'ProfileDesc' => string 'L1 – Up to 1M CNY' (length=19)
15 =>
object(stdClass)[18]
public 'Category' => string 'RNGMaxPayout' (length=12)
public 'ProfileId' => int 2530
public 'ProfileName' => string 'L2 – Up to 1.5M CNY' (length=21)
public 'ProfileDesc' => string 'L2 – Up to 1.5M CNY' (length=21)
16 =>
object(stdClass)[19]
public 'Category' => string 'RNGMaxPayout' (length=12)
public 'ProfileId' => int 2531
public 'ProfileName' => string 'L3 – Up to 3M CNY' (length=19)
public 'ProfileDesc' => string 'L3 – Up to 3M CNY' (length=19)
 */

/*
// 動作： IsAccountAvailable 帳戶 - 建立帳戶前的檢查
// Returns true if account is available for create new player, otherwise return false.
$MG_API_data  = array(
'AccountNumber' => 'gpk255661133'
);
$MG_API_result = mg_restfulapi('IsAccountAvailable', 0, $MG_API_data);
if($MG_API_result['errorcode'] == 0 AND $MG_API_result['Status']->ErrorCode == 0 AND $MG_API_result['count'] > 0) {
var_dump($MG_API_result);
}
 */
/*
/home/testgpk2demo/web/gpk2/Lobby_action.php:282:
object(stdClass)[6]
public 'IsAccountAvailableResult' =>
object(stdClass)[7]
public 'IsSucceed' => boolean true
public 'ErrorCode' => int 0
public 'IsAccountAvailable' => boolean false
 */

/*
// 動作： AddAccount  建立帳戶 , accountNumber left empty to be generated automatically
$MG_API_data  = array(
'password' => 'gpk201608168',
'firstName' => 'dev',
'lastName' => 'test',
'currency' => 'CNY',
'AccountNumber' => '',
'BettingProfileId' => '955'
);
$MG_API_result = mg_restfulapi('AddAccount', 0, $MG_API_data);
if($MG_API_result['errorcode'] == 0 AND $MG_API_result['Status']->ErrorCode == 0 AND $MG_API_result['count'] > 0){
var_dump($MG_API_result);
}
 */
/*
/home/testgpk2demo/web/gpk2/Lobby_action.php:308:
object(stdClass)[6]
public 'AddAccountResult' =>
object(stdClass)[7]
public 'ErrorMessage' => string '' (length=0)
public 'IsSucceed' => boolean true
public 'ErrorCode' => int 0
public 'ErrorId' => string '' (length=0)
public 'CustomerId' => int 31697431
public 'LockAccountStatus' => string 'Open' (length=4)
public 'SuspendAccountStatus' => string 'Open' (length=4)
public 'CasinoId' => int 16118
public 'AccountNumber' => string 'OC0019037078' (length=12)
public 'PinCode' => string 'gpk201608168' (length=12)
public 'FirstName' => string 'dev' (length=3)
public 'LastName' => string 'test' (length=4)
public 'ProfileId' => int 955
public 'IsProgressive' => boolean false
public 'RngBettingProfileId' => int 0
 */

/*
// 動作： GetPlaycheckUrl
$MG_API_data  = array(
'AccountNumber' => 'gpk255661133',
'password' => '393204',
'playCheckType' => 'Player',
'language' => 'CNY'
);
$MG_API_result = mg_restfulapi('GetPlaycheckUrl', 0, $MG_API_data);
if($MG_API_result['errorcode'] == 0 AND $MG_API_result['Status']->ErrorCode == 0 AND $MG_API_result['count'] > 0) {
var_dump($MG_API_result);
}
 */
/*
/home/testgpk2demo/web/gpk2/Lobby_action.php:343:
object(stdClass)[6]
public 'GetPlaycheckUrlResult' => string 'https://redirector3.valueactive.eu/casino/default.aspx?applicationid=1001&username=gpk255661133&password=PTS_ADMIN&serverid=16118&lang=en&transactionID=&timezone=0' (length=163)
 */

// ----------------------------------------------------------------------------
// API 動作方式 sample code by barkley 2016.8.25 END
// ----------------------------------------------------------------------------
// ---------------------------------------------------------------------------
// Features:
//  填入設定的功能，登入 MG api 的函式
// Usage:
//  mg_restfulapi($method, $debug=0, $MG_API_data)
// Input:
//  $method --> 操作的功能
//  $debug=0 --> 設定為 1 為除錯。
//  $MG_API_data --> 填入需的參數，需要搭配 method
// Return:
// -- 如果讀取投注紀錄成功的話 --
// $MG_API_result['curl_status'] = 0; // curl 正確
// $MG_API_result['count'] // 計算取得的紀錄數量有多少
// $MG_API_result['errorcode'] = 0; // 取得紀錄沒有錯誤
// $MG_API_result['Status'] // 回傳的狀態
// $MG_API_result['Result'] // 回傳的緬果
//
// -- 如果讀取投注紀錄失敗的話 --
// $MG_API_result['curl_status'] = 1; // curl 錯誤
// $MG_API_result['errorcode'] = 500; // 錯誤碼
// $MG_API_result['Result'] // 回傳的錯誤訊息
// ---------------------------------------------------------------------------
function mg_restfulapi($method, $debug = 0, $MG_API_data) {
	//$debug=1;
	// 設定 socket_timeout , http://php.net/manual/en/soapclient.soapclient.php
	ini_set('default_socket_timeout', 5);

	global $MG_CONFIG;
	global $system_mode;

	if($system_mode != 'developer') {
		// Setting restful url
		$url = $MG_CONFIG['url'];

		$apilogin = $MG_CONFIG['apiaccount'] . ':' . $MG_CONFIG['apipassword'];

		if ($method == 'GetVersion') {
			// 取得目前的 API 版本, 不一樣就 GG 了!!
			// Product name: TeG API. Product version: 2.8.9.2 ,  2016.8.13
			$service_url = $url . 'GetVersion';
		} elseif ($method == 'GetAccountDetails') {
			$service_url = $url . 'GetAccountDetails';
		} elseif ($method == 'GetAccountBalance') {
			$service_url = $url . 'GetAccountBalance';
		} elseif ($method == 'Deposit') {
			$service_url = $url . 'Deposit';
		} elseif ($method == 'Withdrawal') {
			$service_url = $url . 'Withdrawal';
		} elseif ($method == 'GetBettingProfileList') {
			$service_url = $url . 'GetBettingProfileList';
		} elseif ($method == 'GetPlaycheckUrl') {
			$service_url = $url . 'GetPlaycheckUrl';
		} elseif ($method == 'AddAccount') {
			$service_url = $url . 'AddPlayerAccount';
		} elseif ($method == 'IsAccountAvailable') {
			$service_url = $url . 'IsAccountAvailable';
		} else {
			$result = NULL;
		}

		$ret = array();
		try {
			// 轉成 json 代入 http head
			$postJson = json_encode($MG_API_data);

			//HTTP headers
			$headers = array(
				'Content-Type: application/json',
				//'Authorization: Basic '. base64_encode($username.":".$password),
				'Content-Length: ' . strlen($postJson),
			);

			//Call API
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $service_url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			// curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($ch, CURLOPT_USERPWD, $apilogin);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postJson);
			//curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_VERBOSE, 1);
			curl_setopt($ch, CURLOPT_HEADER, 1);
			$response = curl_exec($ch);

			if ($debug == 1) {
				echo curl_error($ch);
				var_dump($response);
			}

			if ($response) {
				// Then, after your curl_exec call , 移除 http head 剩下 body
				$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
				$header = substr($response, 0, $header_size);
				$body = substr($response, $header_size);
				$body = json_decode($body);

				if ($debug == 1) {
					var_dump($body);
				}
				// 如果 curl 讀取投注紀錄成功的話
				if (isset($body->Status) AND isset($body->Result) AND $body->Status->ErrorCode == 0) {
					// curl 正確
					$ret['curl_status'] = 0;
					// 計算取得的紀錄數量有多少
					$ret['count'] = count($body->Result);
					// 取得紀錄沒有錯誤
					$ret['errorcode'] = 0;
					// 存下 body
					$ret['Status'] = $body->Status;
					$ret['Result'] = $body->Result;
				}
			} else {
				// curl 錯誤
				$ret['curl_status'] = 1;
				$ret['errorcode'] = 500;
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
	}else{
		// curl 錯誤
		$ret['curl_status'] = 1;
		$ret['errorcode'] = 540;
		// 錯誤訊息
		$ret['Result'] = '开发环境不开发测试API，请至DEMO平台测试';
	}

	return ($ret);
}
// ----------------------------------------------------------------------------
// login MG API function end
// ----------------------------------------------------------------------------

// ---------------------------------------------------------------------------
// Features:
// 依據使用者帳號資訊，檢查遠端 MG 的帳號是否存在，不存在就建立
// Usage:
//  create_casino_mg_restful_account()
// Input:
//
// Return:
//
// Log: 2017.5.7 by Barkley
// ---------------------------------------------------------------------------
function create_casino_mg_restful_account() {
	// 變數
	global $config;
	// 回傳的變數
	$r = array();

	// 需要有 session 才可以登入, 且帳號只有 A and R 權限才可以建立 MG 帳號, 不允許管理員建立帳號進入遊戲。
	if (isset($_SESSION['member']) AND ($_SESSION['member']->therole == 'A' OR $_SESSION['member']->therole == 'M') AND ($config['businessDemo'] == 0 OR in_array('MG',$config['businessDemo_skipCasino']))) {

		//var_dump($_SESSION);
		// 當 $_SESSION['wallet']->transfer_flag 存在時，不可以執行，因為有其他程序再記憶體中執行。
		if (!isset($_SESSION['wallet_transfer'])) {

			// 透過這個旗標控制不能有第二個執行緒進入。
			$_SESSION['wallet_transfer'] = 'create_casino_mg_account ' . $_SESSION['member']->account;
			// 使用session時，若先前的頁面尚未執行完畢，預設session會被鎖住。
			// 此時，若執行另外一個也有使用session的頁面，則須等前一個頁面執行完畢，才能再執行。
			// 要避免等前一個頁面執行完畢，才能執行下一個頁面的情況，
			// 可以使用 session_write_close() ，告之不會再對session做寫入的動作，
			// 這樣其他頁面就不會等此頁面執行完才能再執行。
			// session_write_close() ;

			// echo '<p>確認會員使用者有登入，檢查是否已經有 session ，如果沒有建立一個。有的話驗證後離開。</p>';
			if (isset($_SESSION['member'])) {
				// ----------------------------------------------------------------------------

				// 如果沒有 mg2 帳號的話, 馬上建立一個。
				if (!isset($_SESSION['member']->mg_account)) {
					//echo '沒有 mg2 帳號, 馬上建立一個';
					$firstname = $_SESSION['member']->account;
					$lastname = $config['projectid'];
					// 加上2碼隨機後綴
					$rand = substr(md5(microtime()),rand(0,26),2);
					// 建立代碼時，以系統的代碼為前三碼. 後面以會員的 ID 編號為對應，補滿 10 碼;
					$accnumber_id = 20000000000 + $_SESSION['member']->id;
					$mg_accountnumber = $lastname . $accnumber_id.$rand;
					// 密碼為 6-12 碼數字或英文，設定為 8 碼亂數。
					$mg_password = mt_rand(10000000, 99999999);
					// 動作： AddAccount  建立帳戶 , accountNumber left empty to be generated automatically
					// firstname 設定為 gpk 系統帳號, 對照方便
					// lastname 為網站代碼, 對帳容易.
					$BettingProfiles = array(
						'Category' => 'LGBetProfile',
						'ProfileId' => '955',
					);
					$MG_API_data = array(
						'PinCode' => $mg_password,
						'FirstName' => $firstname,
						'LastName' => $lastname,
						'DepositAmount' => '0',
						'PreferredAccountNumber' => $mg_accountnumber,
					);
					//var_dump($MG_API_data);
					$MG_API_result = mg_restfulapi('AddAccount', 0, $MG_API_data);
					//var_dump($MG_API_result);
					if ($MG_API_result['errorcode'] == 0 AND $MG_API_result['Status']->ErrorCode == 0 AND $MG_API_result['count'] > 0) {
						// 成功
						// var_dump($MG_API_result);
						/*
	            object(stdClass)[78]
	              public 'AddAccountResult' =>
	                object(stdClass)[79]
	                  public 'ErrorMessage' => string '' (length=0)
	                  public 'IsSucceed' => boolean true
	                  public 'ErrorCode' => int 0
	                  public 'ErrorId' => string '' (length=0)
	                  public 'CustomerId' => int 59330634
	                  public 'LockAccountStatus' => string 'Open' (length=4)
	                  public 'SuspendAccountStatus' => string 'Open' (length=4)
	                  public 'CasinoId' => int 16619
	                  public 'AccountNumber' => string 'kt120000000015' (length=14)
	                  public 'PinCode' => string '84580157' (length=8)
	                  public 'FirstName' => string 'three' (length=5)
	                  public 'LastName' => string 'kt1' (length=3)
	                  public 'ProfileId' => int 955
	                  public 'IsProgressive' => boolean false
	                  public 'RngBettingProfileId' => int -1
*/
						$logger = 'API MG2 帐号建立成功';
						$r['ErrorCode'] = 10;
						$r['ErrorMessage'] = $logger;

						// 取得建立好的帳號資訊
						$mg_accountnumber_api = $MG_API_result['Result']->AccountNumber;
						$mg_password_api = $MG_API_result['Result']->PinCode;
						$member_wallet_id = $_SESSION['member']->id;
						// 增加紀錄 CasinoId 資訊
						$mg_CasinoId = $MG_API_result['Result']->CasinoId;
						$mg_CustomerId = $MG_API_result['Result']->CustomerId;
						$mg_FirstName = $MG_API_result['Result']->FirstName;
						$mg_LastName = $MG_API_result['Result']->LastName;

						// 紀錄建立 API 的帳號相關資訊
						$logger = 'API MG 帐号建立成功 CustomerId=' . $mg_CustomerId . ',  CasinoId=' . $mg_CasinoId . ',  AccountNumber=' . $mg_accountnumber_api . ',  PinCode=' . $mg_password_api . ', FirstName=' . $mg_FirstName . ', LastName=' . $mg_LastName . ' ,' . json_encode($MG_API_result);
						memberlog2db($_SESSION['member']->account, 'MG2 API', 'notice', "$logger");
						member_casino_transferrecords('lobby', 'MG', '0', $logger, 'success');

						// 更新錢包的 MG 帳號密碼
						// $update_wattet_sql = "UPDATE root_member_wallets SET changetime = now(), mg_account = '$mg_accountnumber_api', mg_password = '$mg_password_api' WHERE id = $member_wallet_id;";
						$update_wattet_sql = "UPDATE root_member_wallets SET changetime = now(), casino_accounts= casino_accounts || '{\"MG\":{\"account\":\"$mg_accountnumber_api\", \"password\":\"$mg_password_api\", \"balance\":\"0.0\"}}' WHERE id = '$member_wallet_id';";
						// echo $update_wattet_sql;

						$update_wattet_sql_result = runSQL($update_wattet_sql);
						if ($update_wattet_sql_result == 1) {
							// 成功的話，更新 session 的 MG account and password 資訊。
							$_SESSION['member']->mg_account = $mg_accountnumber_api;
							$_SESSION['member']->mg_password = $mg_password_api;

							// 更新 wallet 成功
							$logger = 'MG 帐号 ' . $mg_accountnumber_api . ' 密码： ' . $mg_password_api . ' 写入 DB root_member_wallet 成功';
							$r['ErrorCode'] = 0;
							$r['ErrorMessage'] = $logger;
						} else {
							// 更新 wallet 失敗ˋ
							$logger = 'MG 帐号 ' . $mg_accountnumber_api . ' 密码： ' . $mg_password_api . ' 写入 DB root_member_wallet 失败ˋ';
							$r['ErrorCode'] = 1;
							$r['ErrorMessage'] = $logger;
						}
						// var_dump($update_wattet_sql_result);
					} else {
						// 失敗
						// var_dump($MG_API_result);
						$logger = 'API MG 帐号建立失败' . $MG_API_result['Result'];
						$r['ErrorCode'] = 21;
						$r['ErrorMessage'] = $logger;
						member_casino_transferrecords('lobby', 'MG', '0', $logger, 'fail');
					}

				} else {
					// 有帳號存在 MG 內  echo '目前使用者，是否真的存在 MG ';
					// 查詢 目前使用者的 MG2 餘額
					$delimitedAccountNumbers[] = $_SESSION['member']->mg_account;
					$MG_API_data = array(
						'Accounts' => $delimitedAccountNumbers,
					);
					$MG_API_result = mg_restfulapi('GetAccountBalance', 0, $MG_API_data);
					if ($MG_API_result['errorcode'] == 0 AND $MG_API_result['Status']->ErrorCode == 0 AND $MG_API_result['count'] > 0) {
						// 查詢餘額動作, 使用者存在
						//var_dump($MG_API_result);
						$logger = '会员帐号 ' . $MG_API_result['Result']['0']->AccountNumber . '，余额 ' . $MG_API_result['Result']['0']->Balance;
						$r['ErrorCode'] = 20;
						$r['ErrorMessage'] = $logger;
					} else {
						$logger = '会员帐号 ' . $delimitedAccountNumbers . ' 不存在';
						$r['ErrorCode'] = 14;
						$r['ErrorMessage'] = $logger;
					}
					member_casino_transferrecords('lobby', 'MG', '0', $logger, 'info');
				}
			} else {
				$logger = '会员需要登入才可以建立 MG 帐号';
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
		}

	} else {
		// 條件步符合, 不建立帳號
		$logger = '需要有 session 才可以操作, 且帐号只有 A and M 权限才可以建立 MG 帐号, 不允许管理员建立帐号进入游戏';
		$r['ErrorCode'] = 99;
		$r['ErrorMessage'] = $logger;
	}

	return ($r);
}
// ---------------------------------------------------------------------------
// 依據使用者帳號資訊，建立遠端 MG2 的對應帳號 END
// ---------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// Features:
//   將傳入的 member id 值，取得他的 DB GTOKEN 餘額並全部傳送到 MG CASINO 上
//   把本地端的資料庫 root_member_wallets 的 GTOKEN_LOCK設定為 MG 餘額儲存在 mg_balance 上面
// Usage:
//   transferout_gtoken_mg_restful_casino_balance($member_id)
// Input:
//   $member_id --> 會員 ID
//   debug = 1 --> 進入除錯模式
//   debug = 0 --> 關閉除錯
// Return:
//   code = 1  --> 成功
//   code != 1  --> 其他原因導致失敗
// ----------------------------------------------------------------------------
function transferout_gtoken_mg_restful_casino_balance($member_id, $debug = 0) {
	global $config;
	// 將目前所在的 ID 值
	// $member_id = $_SESSION['member']->id;
	// 驗證並取得帳戶資料
	// $member_sql = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.id = '" . $member_id . "';";

	$member_sql = <<<SQL
	SELECT gtoken_balance,gtoken_lock,
					casino_accounts->'MG'->>'account' as mg_account,
					casino_accounts->'MG'->>'password' as mg_password,
					casino_accounts->'MG'->>'balance' as mg_balance FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.id = '{$member_id}';
SQL;
	$r = runSQLall($member_sql);
	if ($debug == 1) {
		var_dump($r);
	}
	if ($r[0] == 1 AND $config['casino_transfer_mode'] != 0) {

		// 沒有 MG 帳號的話，根本不可以進來。
		if ($r[1]->mg_account == NULL OR $r[1]->mg_account == '') {
			$check_return['messages'] = '你还没有 MG 帐号。';
			$check_return['code'] = 12;
		} elseif ($r[1]->gtoken_balance >= '1') {

			// 需要 gtoken_lock 沒有被設定的時候，才可以使用這功能。
			if ($r[1]->gtoken_lock == NULL OR $r[1]->gtoken_lock == 'MG') {

				// 動作： 將本地端所有的 gtoken 餘額 Deposit 到 mg 對應的帳戶
				$accountNumber = $r[1]->mg_account;
				$amount = $r[1]->gtoken_balance;
				$mg_balance = round($r[1]->mg_balance, 2);

				if ($config['casino_transfer_mode'] == 2) {
					$amount = 10;
				}

				$MG_API_data = array(
					'AccountNumber' => $accountNumber,
					'Amount' => $amount,
					'TransactionReferenceNumber' => 'mg0Deposit0'.date("Ymdhis"),
				);

				$MG_API_result = mg_restfulapi('Deposit', 0, $MG_API_data);
				if ($MG_API_result['errorcode'] == 0 AND $MG_API_result['Status']->ErrorCode == 0 AND $MG_API_result['count'] > 0) {
					if ($debug == 1) {
						var_dump($MG_API_data);
						var_dump($MG_API_result);
					}
					// 娛樂城最終餘額
					$mg_balance = $mg_balance + $amount;
					// 本地端 db 的餘額處理
					$togtoken_sql = '';
					$togtoken_sql = "UPDATE root_member_wallets SET gtoken_lock = 'MG'  WHERE id = '$member_id';";
					$togtoken_sql = $togtoken_sql . "UPDATE root_member_wallets SET gtoken_balance = gtoken_balance - '$amount', casino_accounts=jsonb_set(casino_accounts,'{\"MG\",\"balance\"}','{$mg_balance}')   WHERE id = '$member_id';";
					//$togtoken_sql = $togtoken_sql . "UPDATE root_member_wallets SET mg_balance = (SELECT gtoken_balance FROM root_member_wallets WHERE id = '$member_id')  WHERE id = '$member_id';";
					$togtoken_sql_result = runSQLtransactions($togtoken_sql);
					if ($debug == 1) {
						var_dump($togtoken_sql);
						var_dump($togtoken_sql_result);
					}
					if ($togtoken_sql_result) {
						$check_return['messages'] = '所有GTOKEN余额已经转到MG娱乐城。 MG转帐单号 ' . $MG_API_result['Result']->ConfirmationNumber . ' MG帐号' . $MG_API_result['Result']->AccountNumber . 'MG新增' . $MG_API_result['Result']->TransactionAmount;
						$check_return['code'] = 1;
						memberlog2db($_SESSION['member']->account, 'gpk2mg', 'info', $check_return['messages']);
						member_casino_transferrecords('lobby', 'MG', $amount, $check_return['messages'], 'success',
							$MG_API_result['Result']->ConfirmationNumber, 1);
					} else {
						$check_return['messages'] = '余额处理，本地端资料库交易错误。';
						$check_return['code'] = 14;
						memberlog2db($_SESSION['member']->account, 'gpk2mg', 'error', $check_return['messages']);
						member_casino_transferrecords('lobby', 'MG', $amount, $check_return['messages'], 'warning',
							$MG_API_result['Result']->ConfirmationNumber, 2);
					}
				} else {
					$check_return['messages'] = '余额转移到 MG 时失败！！';
					$check_return['code'] = 13;
					memberlog2db($_SESSION['member']->account, 'gpk2mg', 'error', $check_return['messages']);
					member_casino_transferrecords('lobby', 'MG', $amount, $check_return['messages'].'('
						.$MG_API_result['Result'].')', 'fail',$MG_API_result['Result']->ConfirmationNumber, 2);
				}

			} else {
				$check_return['messages'] = '此帐号已经在 MG 娱乐城活动，请勿重复登入。';
				$check_return['code'] = 11;
				member_casino_transferrecords('lobby', 'MG', '0', $check_return['messages'], 'warning');
			}

		} else {
			$check_return['messages'] = '所有GTOKEN余额为 0 故不进行转帐交易。';
			$check_return['code'] = 1;
			memberlog2db($_SESSION['member']->account, 'gpk2mg', 'info', $check_return['messages']);
			member_casino_transferrecords('lobby', 'MG', '0', $check_return['messages'], 'info');
		}
	} elseif ($r[0] == 1 AND $config['casino_transfer_mode'] == 0) {
		$check_return['messages'] = '测试环境不进行转帐交易';
		$check_return['code'] = 1;
		member_casino_transferrecords('lobby', 'MG', '0', $check_return['messages'], 'info');
	} else {
		$check_return['messages'] = '无此帐号 ID = ' . $member_id;
		$check_return['code'] = 0;
		member_casino_transferrecords('lobby', 'MG', '0', $check_return['messages'], 'fail');
	}

	// var_dump($check_return);
	return ($check_return);
}
// ----------------------------------------------------------------------------
// END: 將傳入的 member id 值，取得他的 DB GTOKEN 餘額並全部傳送到 MG CASINO 上
// ----------------------------------------------------------------------------

// ----------------------------------------------------------------------------
/*
目的：取回 MG Casino 的餘額，並檢查上次離開時和目前的差額得出派彩金額，紀錄存簿。 v2017.1.2

1. 查詢 DB 的 gtoken_lock  是否有紀錄在 MG 帳戶，NULL 沒有紀錄的話表示沒有餘額在 MG 帳戶。
2. AND 當 session 有 mg_balance 的時候才動作, 如果沒有則結束。表示 db 帳號資料有問題。
del ---- 3. lock 這個程序, 確保唯一性。使用 $_SESSION['wallet_transfer']  旗標，鎖住程序，不要同時間同一個人執行。需要配合 session_write_close() 才可以。
4. Y , gtoken 紀錄為 MG , API 檢查 MG 的餘額有多少

5. 承接 4 ,如果 MG餘額 > 1
5.1 執行 MG API 取回 MG 餘額 ，到 totle egame 的出納帳戶(API操作) , 成功才執行 5.2,5.3

5.2 把 MG API 傳回的餘額，透過 GTOKEN出納帳號，轉帳到的相對使用者 account 的 GTOKEN 帳戶。 (DB操作)
5.3 紀錄當下 DB mg_balance 的餘額紀錄：存摺 GTOKEN 紀錄: (MG API)收入=4 , (DB MG餘額)支出=10 ,(派彩)餘額 = -6 + 原有結餘 , 摘要：MG派彩 (DB操作)
5.1 ~ 5.3 必須一定要全部成功，才算成功。如果 5.1 成功後，但 5.2,5.3 失敗的話，紀錄為 ERROR LOG，需要通知管理員處理。(message system)
6. 紀錄這次的 retrieve_mg_restful_casino_balance 操作，為一次交易紀錄。後續可以查詢。(Confirmation Number)
7. 執行完成後，需要 reload page in lobby_mggame ，把 $_SESSION['wallet_transfer'] 變數清除, 其他程式才可以進入。
8. 把 GTOKEN_LOCK 設定為 NULL , 表示已經沒有餘額在娛樂城了。
 */

// ---------------------------------------------------------------------------------
// 取回 MG Casino 的餘額 -- 針對 db 的處理函式，只針對此功能有用。 -- retrieve_mg_restful_casino_balance
// 不能單獨使用，需要搭配 retrieve_mg_restful_casino_balance
// ---------------------------------------------------------------------------
// Features:
//  5.2 把 MG API 傳回的餘額，透過 GTOKEN出納帳號，轉帳到的相對使用者 account 的 GTOKEN 帳戶。 (DB操作)
//  5.3 紀錄當下 DB mg_balance 的餘額紀錄：存摺 GTOKEN 紀錄: (MG API)收入=4 , (DB MG餘額)支出=10 ,(派彩)餘額 = -6 + 原有結餘 , 摘要：MG派彩 (DB操作)
// Usage:
//  db_mg2gpk_balance($gtoken_cashier_account, $mg_balance_api, $mg2gpk_balance, $mg_balance_db );
// Input:
//  $gtoken_cashier_account   --> $gtoken_cashier_account(此為系統代幣出納帳號 global var.)
//  $mg_balance_api           --> 取得的 MG API 餘額 , 保留小數第二位 round( $x, 2);
//  $mg2gpk_balance           --> 派彩 = 娛樂城餘額 - 本地端MG支出餘額
//  $mg_balance_db            --> 在剛取出的 wallets 資料庫中的餘額(支出)
// Return:
//  $r['ErrorCode']     = 1;  --> 成功
// ---------------------------------------------------------------------------
function db_mg2gpk_balance($gtoken_cashier_account, $mg_balance_api, $mg2gpk_balance, $mg_balance_db, $debug = 0) {

	global $gtoken_cashier_account;
	global $transaction_category;
	global $auditmode_select;

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
		var_dump($mg2gpk_balance);
	}

	// 派彩有三種狀態，要有不同的對應 SQL 處理
	// --------------------------------
	// $mg2gpk_balance >= 0; 從娛樂城贏錢 or 沒有輸贏，把 MG 餘額取回 gpk。
	// $mg2gpk_balance < 0; 從娛樂城輸錢
	// --------------------------------
	if ($mg2gpk_balance >= 0) {
		// ---------------------------------------------------------------------------------
		// $mg2gpk_balance >= 0; 從娛樂城贏錢 or 沒有輸贏，把 MG 餘額取回 gpk。
		// ---------------------------------------------------------------------------------

		// 判斷是否為測試環境, 如是則必需用DB中gtoken的值加上玩家的變化餘額才是真正的錢包餘額
		//if ($config['casino_transfer_mode'] == 2) {
			// 先取得當下的  wallets 變數資料,等等 sql 更新後. 就會消失了。
			$wallets_sql = "SELECT gtoken_balance,casino_accounts->'MG'->>'balance' as mg_balance FROM root_member_wallets WHERE id = '" . $_SESSION['member']->id . "';";
			//var_dump($wallets_sql);
			$wallets_result = runSQLall($wallets_sql);
			//var_dump($wallets_result);
			// 在剛取出的 wallets 資料庫中的mg餘額(支出)
			$gtoken_mg_balance_db = round($wallets_result[1]->mg_balance, 2);
			// 在剛取出的 wallets 資料庫中的gtoken餘額(支出)
			$gtoken_balance_db = round($wallets_result[1]->gtoken_balance, 2);
			// 派彩 = 娛樂城餘額 - 本地端MG支出餘額
			$gtoken_balance = round(($gtoken_balance_db + $gtoken_mg_balance_db + $mg2gpk_balance), 2);
		//} else {
		//	$gtoken_balance = $mg_balance_api;
		//}

		// 交易開始
		$mg2gpk_transaction_sql = 'BEGIN;';
		// 存款金額 -- 娛樂城餘額
		$d['deposit'] = $gtoken_balance;
		// 提款金額 -- 本地端支出
		$d['withdrawal'] = $mg_balance_db;
		// 操作者
		$d['member_id'] = $_SESSION['member']->id;
		// MG + 代幣派彩
		$d['summary'] = 'MG' . $transaction_category['tokenpay'];
		// 稽核方式
		$d['auditmode'] = $auditmode_select['mg'];
		// 稽核金額 -- 派彩無須稽核
		$d['auditmodeamount'] = 0;
		// MG 取回的餘額為真錢
		$d['realcash'] = 2;
		// 交易類別 MG + $transaction_category['tokenpay']
		$d['transaction_category'] = 'tokenpay';
		// 變化的餘額
		$d['balance'] = $mg2gpk_balance;
		// var_dump($d);

		// 操作 root_member_wallets DB, 把 mg_balance 設為 0 , 把 gtoken_lock = null, 把 gtoken_balance = $d['deposit']
		// 錢包存入 餘額 , 把 mg_balance 扣除全部表示支出(投注).
		$mg2gpk_transaction_sql = $mg2gpk_transaction_sql . "
      UPDATE root_member_wallets SET changetime = NOW(), gtoken_balance =  " . $d['deposit'] . ", gtoken_lock = NULL, casino_accounts= jsonb_set(casino_accounts,'{\"MG\",\"balance\"}','0') WHERE id = '" . $d['destination_transfer_id'] . "'; ";
		// 目的帳號上的註記
		$d['destination_notes'] = '(會員收到MG派彩' . $d['balance'] . ')';
		// 針對目的會員的存簿寫入，$mg2gpk_balance >= 1 表示贏錢，所以從出納匯款到使用者帳號。
		$mg2gpk_transaction_sql = $mg2gpk_transaction_sql .
			'INSERT INTO "root_member_gtokenpassbook" ("transaction_time", "deposit", "withdrawal", "system_note", "member_id", "currency", "summary", "source_transferaccount", "auditmode", "auditmodeamount", "realcash", "destination_transferaccount", "transaction_category", "balance")' .
			"VALUES ('now()', '" . $d['deposit'] . "', '" . $d['withdrawal'] . "', '" . $d['destination_notes'] . "', '" . $d['member_id'] . "', 'CNY', '" . $d['summary'] . "', '" . $d['destination_transferaccount'] . "', '" . $d['auditmode'] . "', '" . $d['auditmodeamount'] . "', '" . $d['realcash'] . "', " .
			"'" . $d['destination_transferaccount'] . "','" . $d['transaction_category'] . "', (SELECT gtoken_balance FROM root_member_wallets WHERE id = " . $d['destination_transfer_id'] . ") );";

		// 針對來源出納的存簿寫入
		$mg2gpk_transaction_sql = $mg2gpk_transaction_sql . "
      UPDATE root_member_wallets SET changetime = NOW(), gtoken_balance = (gtoken_balance - " . $d['balance'] .") WHERE id = '" . $d['source_transfer_id'] . "'; ";
		// 來源帳號上的註記
		$d['source_notes'] = '(出納帳號 ' . $d['source_transferaccount'] . ' 幫MG派彩到會員 ' . $d['destination_transferaccount'] . ')';
		$mg2gpk_transaction_sql = $mg2gpk_transaction_sql .
			'INSERT INTO "root_member_gtokenpassbook" ("transaction_time", "deposit", "withdrawal", "system_note", "member_id", "currency", "summary", "source_transferaccount", "auditmode", "auditmodeamount", "realcash", "destination_transferaccount", "transaction_category", "balance")' .
			"VALUES ('now()', '0', '" . $d['balance'] . "', '" . $d['source_notes'] . "', '" . $d['member_id'] . "', 'CNY', '" . $d['summary'] . "', '" . $d['source_transferaccount'] . "', '" . $d['auditmode'] . "', '" . $d['auditmodeamount'] . "', '" . $d['realcash'] . "', " .
			"'" . $d['source_transferaccount'] . "','" . $d['transaction_category'] . "', (SELECT gtoken_balance FROM root_member_wallets WHERE id = " . $d['source_transfer_id'] . "));";

		// commit 提交
		$mg2gpk_transaction_sql = $mg2gpk_transaction_sql . 'COMMIT;';

		if ($debug == 1) {
			echo '<p>SQL=' . $mg2gpk_transaction_sql . '</p>';
		}

		// 執行 transaction sql
		$mg2gpk_transaction_result = runSQLtransactions($mg2gpk_transaction_sql);
		if ($mg2gpk_transaction_result) {
			$logger = '从MG帐号' . $_SESSION['member']->mg_account . '取回余额到代币，统计后收入=' . $mg_balance_api . '，支出=' . $mg_balance_db . '，共计派彩=' . $mg2gpk_balance;
			$r['ErrorCode'] = 1;
			$r['ErrorMessage'] = $logger;
			memberlog2db($_SESSION['member']->account, 'mggame', 'info', "$logger");
			member_casino_transferrecords('MG', 'lobby', $mg_balance_api, $logger, 'info');
		} else {
			//5.1 ~ 5.3 必須一定要全部成功，才算成功。如果 5.1 成功後，但 5.2,5.3 失敗的話，紀錄為 ERROR LOG，需要通知管理員處理。(message system)
			$logger = '从MG帐号' . $_SESSION['member']->mg_account . '取回余额到代币，统计后收入=' . $mg_balance_api . '，支出=' . $mg_balance_db . '，共计派彩=' . $mg2gpk_balance;
			$logger = $logger . '但资料库处理错误，请通知客服人员处理。';
			memberlog2db($d['member_id'], 'mg_transaction', 'error', "$logger");
			$r['ErrorCode'] = 406;
			$r['ErrorMessage'] = $logger;
			memberlog2db($_SESSION['member']->account, 'mggame', 'error', "$logger", 'error');
			member_casino_transferrecords('MG', 'lobby', $mg_balance_api, $logger, 'warning');
		}

		if ($debug == 1) {
			var_dump($r);
		}
		// ---------------------------------------------------------------------------------

	} elseif ($mg2gpk_balance < 0) {
		// ---------------------------------------------------------------------------------
		// $mg2gpk_balance < 0; 從娛樂城輸錢
		// ---------------------------------------------------------------------------------

		// 判斷是否為測試環境, 如是則必需用DB中gtoken的值加上玩家的變化餘額才是真正的錢包餘額
		//if ($config['casino_transfer_mode'] == 2) {
			// 先取得當下的  wallets 變數資料,等等 sql 更新後. 就會消失了。
			$wallets_sql = "SELECT gtoken_balance,casino_accounts->'MG'->>'balance' as mg_balance FROM root_member_wallets WHERE id = '" . $_SESSION['member']->id . "';";
			//var_dump($wallets_sql);
			$wallets_result = runSQLall($wallets_sql);
			//var_dump($wallets_result);
			// 在剛取出的 wallets 資料庫中mg的餘額(支出)
			$gtoken_mg_balance_db = round($wallets_result[1]->mg_balance, 2);
			// 在剛取出的 wallets 資料庫中gtoken的餘額(支出)
			$gtoken_balance_db = round($wallets_result[1]->gtoken_balance, 2);
			// 派彩 = 娛樂城餘額 - 本地端MG支出餘額
			$gtoken_balance = round(($gtoken_balance_db + $gtoken_mg_balance_db + $mg2gpk_balance), 2);
		//} else {
		//	$gtoken_balance = $mg_balance_api;
		//}

		// 交易開始
		$mg2gpk_transaction_sql = 'BEGIN;';
		// 存款金額 -- 娛樂城餘額
		$d['deposit'] = $gtoken_balance;
		// 提款金額 -- 本地端支出
		$d['withdrawal'] = $mg_balance_db;
		// 操作者
		$d['member_id'] = $_SESSION['member']->id;
		// MG + 代幣派彩
		$d['summary'] = 'MG' . $transaction_category['tokenpay'];
		// 稽核方式
		$d['auditmode'] = $auditmode_select['mg'];
		// 稽核金額 -- 派彩無須稽核
		$d['auditmodeamount'] = 0;
		// MG 取回的餘額為真錢
		$d['realcash'] = 2;
		// 交易類別 MG + $transaction_category['tokenpay']
		$d['transaction_category'] = 'tokenpay';
		// 變化的餘額
		$d['balance'] = abs($mg2gpk_balance);
		// var_dump($d);

		// 操作 root_member_wallets DB, 把 mg_balance 設為 0 , 把 gtoken_lock = null, 把 gtoken_balance = $d['deposit']
		// 錢包存入 餘額 , 把 mg_balance 扣除全部表示支出(投注).
		$mg2gpk_transaction_sql = $mg2gpk_transaction_sql . "
      UPDATE root_member_wallets SET changetime = NOW(), gtoken_balance =  " . $d['deposit'] . ", gtoken_lock = NULL, casino_accounts= jsonb_set(casino_accounts,'{\"MG\",\"balance\"}','0') WHERE id = '" . $d['destination_transfer_id'] . "'; ";
		// 目的帳號上的註記
		$d['destination_notes'] = '(會員收到MG派彩' . $mg2gpk_balance . ')';
		// 針對目的會員的存簿寫入，
		$mg2gpk_transaction_sql = $mg2gpk_transaction_sql .
			'INSERT INTO "root_member_gtokenpassbook" ("transaction_time", "deposit", "withdrawal", "system_note", "member_id", "currency", "summary", "source_transferaccount", "auditmode", "auditmodeamount", "realcash", "destination_transferaccount", "transaction_category", "balance")' .
			"VALUES ('now()', '" . $d['deposit'] . "', '" . $d['withdrawal'] . "', '" . $d['destination_notes'] . "', '" . $d['member_id'] . "', 'CNY', '" . $d['summary'] . "', '" . $d['destination_transferaccount'] . "', '" . $d['auditmode'] . "', '" . $d['auditmodeamount'] . "', '" . $d['realcash'] . "', " .
			"'" . $d['destination_transferaccount'] . "','" . $d['transaction_category'] . "', (SELECT gtoken_balance FROM root_member_wallets WHERE id = " . $d['destination_transfer_id'] . ") );";

		// 針對來源出納的存簿寫入
		$mg2gpk_transaction_sql = $mg2gpk_transaction_sql . "
      UPDATE root_member_wallets SET changetime = NOW(), gtoken_balance = (gtoken_balance + " . $d['balance'] .") WHERE id = '" . $d['source_transfer_id'] . "'; ";
		// 來源帳號上的註記
		$d['source_notes'] = '(出納帳號 ' . $d['source_transferaccount'] . ' 從會員 ' . $d['destination_transferaccount'] . ' 取回派彩餘額)';
		$mg2gpk_transaction_sql = $mg2gpk_transaction_sql .
			'INSERT INTO "root_member_gtokenpassbook" ("transaction_time", "deposit", "withdrawal", "system_note", "member_id", "currency", "summary", "source_transferaccount", "auditmode", "auditmodeamount", "realcash", "destination_transferaccount", "transaction_category", "balance")' .
			"VALUES ('now()', '" . $d['balance'] . "', '0', '" . $d['source_notes'] . "', '" . $d['member_id'] . "', 'CNY', '" . $d['summary'] . "', '" . $d['source_transferaccount'] . "', '" . $d['auditmode'] . "', '" . $d['auditmodeamount'] . "', '" . $d['realcash'] . "', " .
			"'" . $d['source_transferaccount'] . "','" . $d['transaction_category'] . "', (SELECT gtoken_balance FROM root_member_wallets WHERE id = " . $d['source_transfer_id'] . "));";

		// commit 提交
		$mg2gpk_transaction_sql = $mg2gpk_transaction_sql . 'COMMIT;';
		if ($debug == 1) {
			echo '<p>SQL=' . $mg2gpk_transaction_sql . '</p>';
		}

		// 執行 transaction sql
		$mg2gpk_transaction_result = runSQLtransactions($mg2gpk_transaction_sql);
		if ($mg2gpk_transaction_result) {
			$logger = '从MG帐号' . $_SESSION['member']->mg_account . '取回余额到代币，统计后收入=' . $mg_balance_api . '，支出=' . $mg_balance_db . '，共计派彩=' . $mg2gpk_balance;
			$r['ErrorCode'] = 1;
			$r['ErrorMessage'] = $logger;
			memberlog2db($_SESSION['member']->account, 'mggame', 'info', "$logger");
			member_casino_transferrecords('MG', 'lobby', $mg_balance_api, $logger, 'info');
			// echo "<p> $logger </p>";
		} else {
			//5.1 ~ 5.3 必須一定要全部成功，才算成功。如果 5.1 成功後，但 5.2,5.3 失敗的話，紀錄為 ERROR LOG，需要通知管理員處理。(message system)
			$logger = '从MG帐号' . $_SESSION['member']->mg_account . '取回余额到代币，统计后收入=' . $mg_balance_api . '，支出=' . $mg_balance_db . '，共计派彩=' . $mg2gpk_balance;
			$logger = $logger . '但资料库处理错误，请通知客服人员处理。';
			$r['ErrorCode'] = 406;
			$r['ErrorMessage'] = $logger;
			memberlog2db($_SESSION['member']->account, 'mggame', 'error', "$logger");
			member_casino_transferrecords('MG', 'lobby', $mg_balance_api, $logger, 'warning');
			// echo "<p> $logger </p>";
		}
		// var_dump($r);
		// ---------------------------------------------------------------------------------
	} else {
		// 不可能
		$logger = '不可能发生';
		$r['ErrorCode'] = 500;
		$r['ErrorMessage'] = $logger;
		memberlog2db($_SESSION['member']->account, 'mggame', 'error', "$logger");
		echo "<p> $logger </p>";
	}

	return ($r);
}
// ---------------------------------------------------------------------------------
// 針對 db 的處理函式，只針對此功能有用。 END
// ---------------------------------------------------------------------------------

// ---------------------------------------------------------------------------------
// 取回 MG Casino 的餘額 -- retrieve_mg_restful_casino_balance
// 不能單獨使用，需要搭配 db_mg2gpk_balance 使用
// ---------------------------------------------------------------------------
// Features:
// Usage:
// Input:
// Return:
// ---------------------------------------------------------------------------
function retrieve_mg_restful_casino_balance($member_id, $debug = 0) {
	//$debug=1;
	global $gtoken_cashier_account;
	// $member_id
	// $member_id = $_SESSION['member']->id;

	// 判斷會員是否 status 是否被鎖定了!!
	$member_sql = "SELECT * FROM root_member WHERE id = '" . $member_id . "' AND status = '1';";
	$member_result = runSQLall($member_sql);

	// 先取得當下的  member_wallets 變數資料,等等 sql 更新後. 就會消失了。
	// $wallets_sql = "SELECT * FROM root_member_wallets WHERE id = '" . $member_id . "';";
	$wallets_sql = <<<SQL
	SELECT gtoken_balance,gtoken_lock,
					casino_accounts->'MG'->>'account' as mg_account,
					casino_accounts->'MG'->>'password' as mg_password,
					casino_accounts->'MG'->>'balance' as mg_balance FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.id = '{$member_id}';
SQL;
	$wallets_result = runSQLall($wallets_sql);
	if ($debug == 1) {
		var_dump($wallets_sql);
		var_dump($wallets_result);
	}

	// 1. 查詢 DB 的 gtoken_lock  是否有紀錄在 MG 帳戶，NULL 沒有紀錄的話表示沒有餘額在 MG 帳戶。(已經取回了，代幣一次只能對應一個娛樂城)
	// 2. AND 當 DB 有 mg_balance 的時候才動作, 如果沒有則結束。表示 db 帳號資料有問題。
	// -----------------------------------------------------------------------------------
	if ($member_result[0] == 1 AND $wallets_result[0] == 1 AND $wallets_result[1]->mg_account != NULL AND $wallets_result[1]->gtoken_lock == 'MG') {

		// 4. Y , gtoken 紀錄為 MG , API 檢查 MG 的餘額有多少
		// -----------------------------------------------------------------------------------
		// $delimitedAccountNumbers = $_SESSION['member']->mg_account;
		$delimitedAccountNumbers[] = $wallets_result[1]->mg_account;
		$MG_API_data = array(
			'Accounts' => $delimitedAccountNumbers,
		);
		if ($debug == 1) {
			var_dump($MG_API_data);
		}
		$MG_API_result = mg_restfulapi('GetAccountBalance', 0, $MG_API_data);
		if ($MG_API_result['errorcode'] == 0 AND $MG_API_result['Status']->ErrorCode == 0 AND $MG_API_result['count'] > 0) {
			// 查詢餘額動作，成立後執行. 失敗的話結束，可能網路有問題。
			//echo '4. 查詢餘額動作，成立後執行. 失敗的話結束，可能網路有問題。';
			//var_dump($MG_API_result);
			// 取得的 MG API 餘額 , 保留小數第二位 round( $x, 2);
			$mg_balance_api = round($MG_API_result['Result']['0']->Balance, 2);
			$logger = 'MG API 查询余额为' . $MG_API_result['Result']['0']->Balance . '操作的余额为' . $mg_balance_api;
			$r['code'] = 1;
			$r['messages'] = $logger;
			// echo "<p> $logger </p>";
			// -----------------------------------------------------------------------------------

			// 5. 承接 4 ,如果 MG餘額 > 0
			// -----------------------------------------------------------------------------------
			if ($mg_balance_api > 0) {
				//5.1 執行 MG API 取回 MG 餘額 ，到 totle egame 的出納帳戶(API操作) , 成功才執行 5.2,5.3
				// 動作： Withdrawal 帳戶取款
				$MG_API_data = array(
					'AccountNumber' => $wallets_result[1]->mg_account,
					'Amount' => "$mg_balance_api",
					'TransactionReferenceNumber' => 'mg0Withdrawal0'.date("Ymdhis"),
				);

				if ($debug == 1) {
					echo '5.1 執行 MG API 取回 MG 餘額 ，到 totle egame 的出納帳戶(API操作) , 成功才執行 5.2,5.3';
					var_dump($MG_API_data);
				}

				$MG_API_result = mg_restfulapi('Withdrawal', 0, $MG_API_data);
				if ($MG_API_result['errorcode'] == 0 AND $MG_API_result['Status']->ErrorCode == 0 AND $MG_API_result['count'] > 0) {
					// 取回MG餘額成功
					$logger = 'MG API 从帐号' . $wallets_result[1]->mg_account . '取款余额' . $MG_API_result['Result']->TransactionAmount . '成功。交易编号为' . $MG_API_result['Result']->ConfirmationNumber;
					$r['code'] = 100;
					$r['messages'] = $logger;
					memberlog2db($_SESSION['member']->account, 'mggame', 'info', "$logger");
					member_casino_transferrecords('MG', 'lobby', $MG_API_result['Result']->TransactionAmount, $logger, 'success',$MG_API_result['Result']->ConfirmationNumber);

					if ($debug == 1) {
						echo "<p> $logger </p>";
						var_dump($MG_API_result);
					}
					// 先取得當下的  wallets 變數資料,等等 sql 更新後. 就會消失了。
					// -----------------------------------------------------------------------------------
					$wallets_sql = "SELECT casino_accounts->'MG'->>'balance' as mg_balance FROM root_member_wallets WHERE id = '" . $_SESSION['member']->id . "';";
					//var_dump($wallets_sql);
					$wallets_result = runSQLall($wallets_sql);
					//var_dump($wallets_result);
					// 在剛取出的 wallets 資料庫中的餘額(支出)
					$mg_balance_db = round($wallets_result[1]->mg_balance, 2);
					// 派彩 = 娛樂城餘額 - 本地端MG支出餘額
					$mg2gpk_balance = round(($mg_balance_api - $mg_balance_db), 2);
					$r['balance'] = $mg2gpk_balance;
					// -----------------------------------------------------------------------------------

					// 處理 DB 的轉帳問題 -- 5.2 and 5.3
					$db_mg2gpk_balance_result = db_mg2gpk_balance($gtoken_cashier_account, $mg_balance_api, $mg2gpk_balance, $mg_balance_db);
					if ($db_mg2gpk_balance_result['ErrorCode'] == 1) {
						$r['code'] = 1;
						$r['messages'] = $db_mg2gpk_balance_result['ErrorMessage'];
						$logger = $r['messages'];
						memberlog2db($_SESSION['member']->account, 'mg2gpk', 'info', "$logger");
					} else {
						$r['code'] = 523;
						$r['messages'] = $db_mg2gpk_balance_result['ErrorMessage'];
						$logger = $r['messages'];
						memberlog2db($_SESSION['member']->account, 'mg2gpk', 'error', "$logger");
					}

					if ($debug == 1) {
						echo '處理 DB 的轉帳問題 -- 5.2 and 5.3';
						var_dump($db_mg2gpk_balance_result);
					}
				} else {
					//5.1 執行 MG API 取回 MG 餘額 ，到 totle egame 的出納帳戶(API操作) , 成功才執行 5.2,5.3
					$logger = 'MG API 从帐号' . $_SESSION['member']->mg_account . '取款余额' . $mg_balance_api . '失败';
					$r['code'] = 405;
					$r['messages'] = $logger;
					memberlog2db($_SESSION['member']->account, 'mggame', 'error', "$logger");
					member_casino_transferrecords('MG', 'lobby', '0', $logger.'('.$MG_API_result['Result'].')', 'fail',$MG_API_result['Result']->ConfirmationNumber);

					if ($debug == 1) {
						echo "5.1 執行 MG API 取回 MG 餘額 ，到 totle egame 的出納帳戶(API操作) , 成功才執行 5.2,5.3";
						echo "<p> $logger </p>";
						var_dump($r);
					}
				}

			} elseif ($mg_balance_api == 0) {
				$logger = 'MG余额 = 0 ，MG没有余额，无法取回任何的余额，将余额转回 GPK。';
				$r['code'] = 406;
				$r['messages'] = $logger;
				memberlog2db($_SESSION['member']->account, 'mggame', 'info', "$logger");
				member_casino_transferrecords('MG', 'lobby', '0', $logger, 'success',$MG_API_result['Result']->ConfirmationNumber);

				// 先取得當下的  wallets 變數資料,等等 sql 更新後. 就會消失了。
				// -----------------------------------------------------------------------------------
				// $wallets_sql = "SELECT * FROM root_member_wallets WHERE id = '" . $_SESSION['member']->id . "';";
				$wallets_sql = <<<SQL
				SELECT gtoken_balance,gtoken_lock,
								casino_accounts->'MG'->>'account' as mg_account,
								casino_accounts->'MG'->>'password' as mg_password,
								casino_accounts->'MG'->>'balance' as mg_balance FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.id = '{$_SESSION['member']->id}';
SQL;
				//var_dump($wallets_sql);
				$wallets_result = runSQLall($wallets_sql);
				//var_dump($wallets_result);
				// 在剛取出的 wallets 資料庫中的餘額(支出)
				$mg_balance_db = round($wallets_result[1]->mg_balance, 2);
				// 派彩 = 娛樂城餘額 - 本地端MG支出餘額
				$mg2gpk_balance = round(($mg_balance_api - $mg_balance_db), 2);
				$r['balance'] = $mg2gpk_balance;
				// -----------------------------------------------------------------------------------

				// 處理 DB 的轉帳問題 -- 5.2 and 5.3
				$db_mg2gpk_balance_result = db_mg2gpk_balance($gtoken_cashier_account, $mg_balance_api, $mg2gpk_balance, $mg_balance_db);
				if ($db_mg2gpk_balance_result['ErrorCode'] == 1) {
					$r['code'] = 1;
					$r['messages'] = $db_mg2gpk_balance_result['ErrorMessage'];
					$logger = $r['messages'];
					memberlog2db($_SESSION['member']->account, 'mg2gpk', 'info', "$logger");
				} else {
					$r['code'] = 523;
					$r['messages'] = $db_mg2gpk_balance_result['ErrorMessage'];
					$logger = $r['messages'];
					memberlog2db($_SESSION['member']->account, 'mg2gpk', 'error', "$logger");
				}

				if ($debug == 1) {
					echo '處理 DB 的轉帳問題 -- 5.2 and 5.3';
					var_dump($db_mg2gpk_balance_result);
				}

			} else {
				// MG餘額 < 0 , 不可能發生
				$logger = 'MG余额 < 1 ，不可能发生。';
				$r['code'] = 404;
				$r['messages'] = $logger;
			}
			// -----------------------------------------------------------------------------------
		} else {
			// 4. Y , gtoken 紀錄為 MG , API 檢查 MG 的餘額有多少
			$logger = 'MG API 查询余额失败，系统维护中请晚点再试。';
			$r['code'] = 403;
			$r['messages'] = $logger;
			member_casino_transferrecords('MG', 'lobby', '0', $logger.'('.$MG_API_result['Result'].')', 'fail');
			if ($debug == 1) {
				var_dump($MG_API_result);
			}
		}
		// -----------------------------------------------------------------------------------
	} else {
		// 1. 查詢 session 的 gtoken_lock  是否有紀錄在 MG 帳戶，NULL 沒有紀錄的話表示沒有餘額在 MG 帳戶。
		// 2. AND 當 session 有 mg_balance 的時候才動作, 如果沒有則結束。表示 db 帳號資料有問題。
		$logger = '没有余额在 MG 帐户 OR DB 帐号资料有问题 ';
		$r['code'] = 401;
		$r['messages'] = $logger;
		member_casino_transferrecords('MG', 'lobby', '0', $logger, 'fail');
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
// 產生可以連到 MG game 的 url 位址
// ---------------------------------------------------------------------------
// Features:
// Usage:
// Input:
// Return:
// ---------------------------------------------
function mg_game_url($mg2_account, $mg2_password, $mg2_gameid, $gameplatform) {
	//
	// MG flash Game , 2017.5.28 之前得帳號
	// https://redirect.contdelivery.com/Casino/Default.aspx?applicationid=1023&serverid=16118&csid=16118&theme=igamingA5&usertype=0&gameid=FrenchRoulette&sEXT1=gpk20000000099&sEXT2=87803124&ul=zh

	//ex: 新的主機 TNG7  2017.5.28
	// https://redirect.contdelivery.com/Casino/Default.aspx?applicationid=1023&serverid=16619&csid=16619&theme=igamingA7&usertype=0&gameid=FrenchRoulette&sEXT1=kt120000000018&sEXT2=070956&ul=zh

	// $gpk2_csid = 16118;	   --> 這是 casino id , 關係到遊戲主機的位置, TNG 給的, for GPK 使用, GPK2 上線可能要申請新的
	// gameid=carnaval  	     --> flash 遊戲 gamecode
	// sEXT1=gpk255661133  	   --> mg2 的帳號
	// sEXT2=393204			       --> mg2 的密碼
	global $MG_CONFIG;

	// 取得game的資料
	$mg_gamedata_sql = 'SELECT * FROM "casino_gameslist" WHERE "id" = \'' . $mg2_gameid . '\';';
	$mg_gamedata_result = runSQLall($mg_gamedata_sql);

	$mg_gamecode = $mg_gamedata_result['1']->gameid;
	$mg_moduleid = $mg_gamedata_result['1']->moduleid;
	$mg_clientid = $mg_gamedata_result['1']->clientid;
	$game_category = $mg_gamedata_result['1']->category;
	$gamename = $mg_gamedata_result['1']->gamename;

	// mg server for gpk (專屬 GPK 的編號)
	// 透過mg的restful api查得會員帳號所在的mgserver
	if (isset($mg2_account)) {
		$MG_API_data = array(
			'AccountNumber' => $mg2_account,
		);
		$MG_API_result = mg_restfulapi('GetAccountDetails', 0, $MG_API_data);
		//var_dump($MG_API_result);
		if ($MG_API_result['errorcode'] == 0 AND $MG_API_result['Status']->ErrorCode == 0 AND $MG_API_result['count'] > 0) {
			$casinoid = $MG_API_result['Result']->CasinoId;
			$mg2_csid = $casinoid;
			$mg2_serverid = $casinoid;
		}

		$theme = NULL;
		if (isset($MG_CONFIG['CSID'][$mg2_csid])) {
			$theme['flash'] = $MG_CONFIG['CSID'][$mg2_csid]['flash'];
			$theme['html5'] = $MG_CONFIG['CSID'][$mg2_csid]['html5'];
		} else {
			$theme = NULL;
			$logger = '('.$mg2_csid.')CSID ERROR !!!请联络客服人员！';
			echo '<script>alert("'.$logger.'");</script>';
			member_casino_transferrecords('lobby', 'MG', '0', $logger, 'fail');
			die($logger);
		}

		// 有資料才輸出 url , 否則為空.
		if (isset($mg2_csid) AND isset($mg2_account) AND isset($mg2_password) AND isset($mg_gamecode) AND $theme != NULL) {
			$gpk2_mg2_csid = $mg2_csid;
			$gpk2_mg2_serverid = $mg2_serverid;
			$gpk2_mg_account = $mg2_account;
			$gpk2_mg_password = $mg2_password;
			$gpk2_mg_gamecode = $mg_gamecode;
			$gpk2_mg_lobby_url = $_SERVER['HTTP_HOST'];
			// total egame 提供的位址
			if ($game_category == 'Live') {
				$mg_cdn_baseurl = 'https://webservice.basestatic.net/ETILandingPage/?CasinoID=' . $mg2_csid . '&LoginName=' . $gpk2_mg_account . '&Password=' . $gpk2_mg_password . '&UL=zh-cn&ClientID=' . $mg_clientid . '&StartingTab=' . $mg_gamecode . '&altProxy=TNG';
				if ($gameplatform == 'flash') {
					$gpk2_mg_url = $mg_cdn_baseurl . '&BetProfileID=DesignStyleA&ClientType=1&ModuleID=' . $mg_moduleid . '&UserType=0&ProductID=2&ActiveCurrency=Credits&VideoQuality=AutoSD&CustomLDParam=MultiTableMode^^1||LobbyMode^^C||CDNselection^^1&GameTabCH=0';
				} else {
					$gpk2_mg_url = $mg_cdn_baseurl . '&BetProfileID=MobilePostLogin&BrandID=igaming&LogoutRedirect=https%3A%2F%2F' . $gpk2_mg_lobby_url . '%2Fgamelobby.php';
				}
			} else {
				if ($gameplatform == 'flash') {
					$mg_cdn_baseurl = 'https://redirect.contdelivery.com/Casino/Default.aspx';
					$gpk2_mg_url = $mg_cdn_baseurl . '?applicationid=1023&serverid=' . $gpk2_mg2_serverid . '&csid=' . $gpk2_mg2_csid . '&theme=' . $theme['flash'] . '&usertype=0&gameid=' . $gpk2_mg_gamecode . '&sEXT1=' . $gpk2_mg_account . '&sEXT2=' . $gpk2_mg_password . '&ul=zh';
				} else {
					$mg_cdn_baseurl = 'https://mobile'.$MG_CONFIG['CSID'][$mg2_csid]['subid'].'.gameassists.co.uk/MobileWebServices_40/casino/game/launch/';
					$gpk2_mg_url = $mg_cdn_baseurl . $theme['html5'] . '/' . $gpk2_mg_gamecode . '/zh-cn/?lobbyURL=https%3A%2F%2F' . $gpk2_mg_lobby_url . '%2Fgamelobby.php&bankingURL=https%3A%2F%2F' . $gpk2_mg_lobby_url . '%2Fwallets.php&username=' . $gpk2_mg_account . '&password=' . $gpk2_mg_password . '&currencyFormat=%23%2C%23%23%23.%23%23&logintype=fullUPE&xmanEndPoints=https://xplay'.$MG_CONFIG['CSID'][$mg2_csid]['subid'].'.gameassists.co.uk/xman/x.x';
				}
			}
			$re = $gpk2_mg_url;
			$logger = '会员 '.$_SESSION['member']->account.' 前往游戏'.$gamename.'.';
			member_casino_transferrecords('lobby', 'MG', '0', $logger, 'info');
		} else {
			$re = '';
			$logger = '会员 '.$_SESSION['member']->account.' 前往游戏'.$gamename.',登入失败：'.$MG_API_result['Result'];
			member_casino_transferrecords('lobby', 'MG', '0', $logger, 'fail');
		}
	} else {
		$re = '';
		$logger = '会员 '.$_SESSION['member']->account.' 前往游戏 '.$gamename.' 但没有娱乐城帐号';
		member_casino_transferrecords('lobby', 'MG', '0', $logger, 'fail');
	}

	return ($re);
}
// ---------------------------------------------
// 產生可以連到 MG game 的 url 位址 END

?>
