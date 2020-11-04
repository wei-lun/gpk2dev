<?php
// ----------------------------------------
// Features:	前台 -- 系統設定檔
// File Name:	config.php
// Author:		barkley Fix by Ian
// Related:
// Log:
// -----------------------------------------------------------------------------
// 算累每個程式累計花費時間 start , 統計設定在 foot 的函式
$program_start_time =  microtime(true);
// -----------------------------------------------------------------------------

// 陣列宣告
$pdo = [];
$redisdb = [];
$config = [];
$tr = [];
$betlogpdo = [];

// -----------------------------------------------------------------------------
// 切換系統模式及資料庫
// -----------------------------------------------------------------------------
// VERSION.txt 檔案內容放置 release version or developer , 依據此變數自動判斷目前所在開發環境
$version_url = dirname(__FILE__).'/version.txt';
if(file_exists($version_url)) {
	$system_mode = strtolower(trim(file_get_contents($version_url)));
	// $system_mode = 'developer';
	// $system_mode = 'release version';

	// 開發者的環境設定檔
	if($system_mode == 'developer') {
		// postgresql DB infomation
		$pdo['db']					= "pgsql";
		$pdo['host']				= "10.22.114.110";
		$pdo['host4write']	= "10.22.114.110";
		$pdo['dbname']			= "dev";
		$pdo['user']				= "lamort";
		$pdo['password']		= "t9052108";

		// 注單用DB設定
		$betlogpdo['db']				= "pgsql";
		$betlogpdo['host']			= "10.22.112.8";
		$betlogpdo['user']			= "gpk"; // same as schema
		$betlogpdo['password']	= "www@gpk17";

		// redis server DB information
		// redis server DB use information 每次登入最長的 timeout 時間, 系統預設 timeout 設定在 redisdb 的 timeout.
		// php session 改成預設寫入寫在 redisdb db 0 上面
		// redisdb DB 1 為後台的 login 資訊
		// redisdb DB 2 為前台的 login 資訊
		$redisdb['host']		= '10.22.114.104';
		$redisdb['auth']		= '123456';
		$redisdb['db']			= 2;

		// 系統是否顯示debug資訊 ref:http://www.php.net/manual/en/function.error-reporting.php
		ini_set('error_reporting', E_ALL);
		ini_set('display_errors', 'ON');
		ini_set('display_startup_errors', 'ON');

		// mqtt config
		$config['mqtt_url'] = 'wss://message.shopeebuy.com:11883/';
		$config['mqtt_host'] = 'message.shopeebuy.com';
		$config['mqtt_port'] = 1883;
		$config['mqtt_username'] = 'mtchang';
		$config['mqtt_password'] = 'qw';
		$config['mqtt_channel_hash'] = false;
		$config['mqtt_message_reciever_host'] = 'http://dright.jutainet.com/message/public';
		$config['mqtt_channel_hash_salt'] = 'hhhee';

		$config['rebbit_host'] = '10.22.115.40';
		$config['rebbit_port'] = '5672';
		$config['rebbit_vhost'] = 'demo_mq';
		$config['rebbit_user'] = 'demo';
		$config['rebbit_password'] = 'demo@jtn@2019';

		// GPK2 API 代理參數
		$config['gpk2_url'] = 'http://gapi.apighub.com/';
		$config['gpk2_apikey'] = '0c6626eee994cb11f96d2db685cba311';
		$config['gpk2_token'] = 'fe96dd032a4c25733bb06df1eecb4f9b3e5f3b3cab22f275cf1ad4d6fcb67c2668f6d022d59cb2f6bc85a495e44ee27a5b9de51b3def30735a910516ea5e7a79';

		// GPK2 金流 API 代理參數
		$config['gpk2_pay'] = [
			'apiKey' => '05638f481550f98085f0f91e1edcad0c',
			'apiToken' => '645e056ab315d45a0875860e43b50321e2e8a453a836eb23e831771ba4a0de7fdd223f8ced36233d59bd93b037fd78bce3e5b6aef06514f1af52e072129db7e0',
		];

		if(isset($_SERVER["HTTP_HOST"])){
			// 取得目前domain的主網址
			$currency_suburl = explode('.',$_SERVER["HTTP_HOST"])[0];
			$currency_basedomain = preg_replace("/^($currency_suburl.)/i", '', $_SERVER["HTTP_HOST"]);
			if($_SERVER["HTTP_HOST"] == 'dev.gpk17.com'){
				$desktop_suburl = 'dev';
				$mobile_suburl = 'mdev';
				$config['desktop_url']	= $_SERVER["HTTP_HOST"].'/';
				$config['mobile_url']	= $_SERVER["HTTP_HOST"].'/';
			}else{
				$desktop_suburl = 'lamort';
				$mobile_suburl = 'belamort';
				$pathway = explode('/',$_SERVER["REQUEST_URI"]);
				$config['desktop_url']	= $desktop_suburl.'.'.$currency_basedomain.'/'.$pathway[1].'/';
				$config['mobile_url']	= $mobile_suburl.'.'.$currency_basedomain.'/'.$pathway[1].'/';
			}
		}

		// 客制 CDN 網址，開發模式預設為自已的網址
		$custom_cdn	= ((isset($_SERVER["HTTP_X_FORWARDED_PROTO"])) ? $_SERVER["HTTP_X_FORWARDED_PROTO"] : $_SERVER["REQUEST_SCHEME"]).'://'.$_SERVER["HTTP_HOST"].'/'.$pathway[1].'/';
	// -----------------------------------------------------------------------------
	}else{

		// postgresql DB infomation
		$pdo['db']					= "pgsql";
		$pdo['host']				= "10.22.114.110";
		$pdo['host4write']	= "10.22.114.110";
		$pdo['dbname']			= "testgpk";
		$pdo['user']				= "gpk";
		$pdo['password']		= "a0926033571";

		// 注單用DB設定
		$betlogpdo['db']				= "pgsql";
		$betlogpdo['host']			= "10.22.112.8";
		$betlogpdo['user']			= "gpk"; // same as schema
		$betlogpdo['password']	= "www@gpk17";

		// redis server DB information
		// redis server DB use information 每次登入最長的 timeout 時間, 系統預設 timeout 設定在 redisdb 的 timeout.
		// php session 改成預設寫入寫在 redisdb db 0 上面
		// redisdb DB 1 為後台的 login 資訊
		// redisdb DB 2 為前台的 login 資訊
		$redisdb['host']		= '10.22.114.104';
		$redisdb['auth']		= '123456';
		$redisdb['db']			= 2;

		// 系統是否顯示debug資訊 ref:http://www.php.net/manual/en/function.error-reporting.php
		ini_set('error_reporting', E_ALL & ~E_DEPRECATED & ~E_STRICT);
		ini_set('display_errors', 'Off');
		ini_set('display_startup_errors', 'Off');

		// mqtt config
		$config['mqtt_url'] = 'wss://message.shopeebuy.com:11883/';
		$config['mqtt_host'] = 'message.shopeebuy.com';
		$config['mqtt_port'] = 1883;
		$config['mqtt_username'] = 'mtchang';
		$config['mqtt_password'] = 'qw';
		$config['mqtt_channel_hash'] = true;
		$config['mqtt_message_reciever_host'] = 'http://message.shopeebuy.com';
		$config['mqtt_channel_hash_salt'] = 'hhhee';

		$config['rebbit_host'] = '10.22.115.40';
		$config['rebbit_port'] = '5672';
		$config['rebbit_vhost'] = 'demo_mq';
		$config['rebbit_user'] = 'demo';
		$config['rebbit_password'] = 'demo@jtn@2019';

		// GPK2 API 代理參數
		$config['gpk2_url'] = 'http://gapi.apighub.com/';
		$config['gpk2_apikey'] = 'ed72d97659db0dba54cab42621c60c3f';
		$config['gpk2_token'] = 'efd696f88282129f20b146e80fafebbca5795a7748880624ead3e74a13418e30166d7b4e6945a32a01fb6eb643d2d1393cda366303bbae732083a420d8ced4ec';

		// GPK2 金流 API 代理參數
		$config['gpk2_pay'] = [
			'apiHost' => 'https://demo.shopeebuy.com',
			'apiKey' => '823eabc9c17e5c2f29935ac208c820e6',
			'apiToken' => '038535ec7e3d39262beb1d1e00070d785a9ce7f475071da6945370d4496de36908d67390bbd40685b2f6fd5c25e07ecef5ef517aa9c8392790b6e59f76e59d30'
		];

		if(isset($_SERVER["HTTP_HOST"])){
			// 取得目前domain的主網址
			$url_arr = explode('.',$_SERVER["HTTP_HOST"]);
			if (count($url_arr) >= 3) {
						$currency_suburl = $url_arr[0] ;
						$currency_basedomain = preg_replace("/^($currency_suburl.)/i", '', $_SERVER["HTTP_HOST"]);
			}else{
						$currency_suburl = '';
						$currency_basedomain = $_SERVER["HTTP_HOST"];
			}
			$desktop_suburl = 'www';
			$mobile_suburl = 'm';
			$config['desktop_url']	= $desktop_suburl.'.'.$currency_basedomain.'/';
			$config['mobile_url']	= $mobile_suburl.'.'.$currency_basedomain.'/';
		}

		// 客制 CDN 網址
		$custom_cdn	= '';
	// -----------------------------------------------------------------------------
	}
}else{
	// 沒有設定 STOP
	die('system mode setting error.');
}

// -----------------------------------------------------------------------------
// 主站預設設定值，非主站設定請至 websiteconf.php 新增該 domain 的設定值
// -----------------------------------------------------------------------------

$config['hostname'] = 'GPKDEMO';
$config['footer']   = 'CopyRight';
// 此前台網站的 web root URL
$config['website_domainname']	= 'dev.gpk17.com';

// 後台網站的 web root URL
$config['besite_domainname']	= 'bedev.gpk17.com';

// 站台專案代碼及theme等的設定值
$config['website_project_type']	= 'uic';
// 網站樣版的 THEME 路徑設定
$config['themepath'] = 'gp02';
// 網站的遊戲圖示路徑設定
$config['gameiconpath'] = 'gamesicon';

// 網站的型態：'ecshop' => 商城，'casino' => 娛樂城
//$config['website_type'] = 'ecshop';
$config['website_type'] = 'casino';

// 預設站台主語系(包含站台使用幣別)
$config['default_lang'] = 'zh_CN';

// google analytics id
$config['google_analytics_id'] = '';

// --------------------以上區塊為可經website config 變更的值--------------------
// -----------------------------------------------------
// 專案代碼 , 每個網站都有一個獨一無二的專案代碼, 建立 casion 帳號時，以這個專案代碼當開頭。三碼為限。
// -----------------------------------------------------
// 建立帳號時以 prijectid + 流水號為主 , 對應到娛樂城的代碼, 才可以分辨不同的網站. kt1 為測試站台代碼
$config['projectid'] 				= 'kt1';

// 站台多網域設定來源 （ 來源為檔案時設為 file ,如不是設file，則預設從DB讀）
$websiteconf_mode = 'db';
// 代入通用設定及子站台設定
require_once dirname(__FILE__) .'/config_common.php';

// --------------------以下區塊為不可經website config 變更的值--------------------

// EC 商城的 host 設定
$config['ec_protocol'] = 'https';
$config['ec_host'] = 'ectest.gpk17.com';

// EC 商城驗證信的設定
$config['ec_mailer_verification'] = (object) [
    'send_by_addr'  => 'jutainetwebb@jutainet.com',
    'send_by_name'  => '測試人員',
    'mail_title'    => '商城 account 驗證信',
];

// -----------------------------------------------------
// 金流設定   test: 測試金流    release: 正式金流
// -----------------------------------------------------
$config['payment_mode'] = 'test';
// $config['payment_mode'] = 'release';

// IG 總代理 hashcode, 與站台專案代碼為 1-1 關係
$config['ig_hashCode'] = 'tgpk2aa01_067e0a53-df18-4105-9b07-ad';

// -----------------------------------------------------
// 娛樂城轉帳設定
// 0: 測試環境，不進行轉帳    1: 正式環境，會正常進行轉帳作業    2: 限額轉帳，每次只轉10元
// -----------------------------------------------------
$config['casino_transfer_mode'] = '0';
//$config['casino_transfer_mode'] = '1';

// -----------------------------------------------------
// 業務測試站台開關參數
// -----------------------------------------------------
// 業務測試站台開關 businessDemo  ( 0 / 1 )
$config['businessDemo'] = 0;
// 業務測試站台例外娛樂城 businessDemo_skipCasino  (array)
$config['businessDemo_skipCasino'] = array();

/**
 * 訊息記錄層級：
 * debug：記錄所有系統的訊息，不分錯誤等級
 * warning：記錄到 warning 層級的訊息
 * error：只記錄出現錯誤的 error 層級的訊息
 *
 * $config['log_level'] 站台內會員操作記錄
 * $config['casino_transferlog_level'] 會員對娛樂城操作記錄
 */
$config['log_level'] = 'debug';
$config['casino_transferlog_level'] = 'debug';

// ip region
// 顯示IP所在區域
$config['ip_region_url'] = 'http://10.22.114.103/receive_ip_data.php';

// -----------------------------------------------------
// 使用錢幣預設的顯示
// -----------------------------------------------------
setlocale(LC_MONETARY, $config['default_lang']);
$config['currency_sign'] = localeconv()['int_curr_symbol'];

// 獨立變數檔案, 負責所以有前台的獨立變數。
require_once dirname(__FILE__) ."/system_config.php";

// END
?>
