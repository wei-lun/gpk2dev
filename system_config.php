<?php
// ----------------------------------------
// Features:	前台 -- 系統環境檔案變數設定檔
// File Name:	system_config.php
// Author:		barkley
// Related:   config.php 由此檔案載入
// Log:
// 2017.5.1 前後台檔案幾乎雷同
// -----------------------------------------------------------------------------

// =============================================================================
// 系統 HEAD 的限制條件
// =============================================================================
// https://devco.re/blog/2014/03/10/security-issues-of-http-headers-1/
header("X-Frame-Options: ALLOW-FROM https://error.jutainet.com");
// 限制系統同源
header("X-Frame-Options: SAMEORIGIN");

// X-XSS-Protection https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-XSS-Protection
header("X-XSS-Protection: 1; mode=block");

// https://msdn.microsoft.com/zh-tw/library/gg622941(v=vs.85).aspx
// 如果在 styleSheet 參考接收的回應上收到 "nosniff" 指示詞，除非 MIME 類型符合 "text/css"，否則 Windows Internet Explorer 將不會載入「樣式表」檔案。
header("X-Content-Type-Options: nosniff");
// load composer
require_once dirname(__FILE__) . '/addon/vendor/autoload.php';

// http://calos-tw.blogspot.tw/2009/11/phphttponly-xss-cookie.html
if(!isset($_SESSION)) ini_set("session.cookie_httponly", 1);

// 小心使用, 用了後系統會很安全. https://devco.re/blog/2014/04/08/security-issues-of-http-headers-2-content-security-policy/
//header("Content-Security-Policy: default-src *");

  // 強制更新 memcache 資料，用於redis 推播更新用
  function system_config_callback($instance, $channelName, $message) {
   echo $channelName, "==>", $message,PHP_EOL;
   if($message == 'update'){
     sys_casino_list(1);
     sys_protalsetting_list(1);
     sys_user_timezone(1);
     sys_casherdata(1);
   }
  }


  // memcache連線
  $memcache = new Memcached();
  $memcache->addServer('localhost', 11211) or die ("Could not connect memcache server !! ");
  // --------------------------------------------------------------------------
  // 檢查 key 是否有存在 memcache 中
  // 若不存在就將 sql 執行並回傳。
  // 若存在就回傳空值。
  // --------------------------------------------------------------------------
  function checkmemcache($key_alive){
      global $config,$system_mode,$memcache;

      $returndata='';
      // $memcache = new Memcached();
      // $memcache->addServer('localhost', 11211) or die ("Could not connect memcache server !! ");
      // $key = 'The vale is from '.$key;
      $key_alive = ($system_mode == 'developer') ? $key_alive.$config['website_baseurl'] : $key_alive.$config['projectid'];
      $get_result = $memcache->get($key_alive);
      if($get_result){
          $returndata=$get_result;
      }

      return $returndata;
  }


  // --------------------------------------------------------------------------
  // 將新資料存入 memcache 中，並且將之 get 回來並回傳
  // --------------------------------------------------------------------------
  function setandget_memcache($key_alive,$key_stay,$memcached_timeout){
      global $config,$system_mode,$memcache;
      // $memcache = new Memcached();
      // $memcache->addServer('localhost', 11211) or die ("Could not connect memcache server !! ");
  	// $memcached_timeout = 120;
  	// var_dump($key_stay);
      $key_alive = $key_alive.$config['projectid'].'11';
      $key_alive = ($system_mode == 'developer') ? $key_alive.$config['website_baseurl'] : $key_alive.$config['projectid'];
      $memcache->set($key_alive, $key_stay, $memcached_timeout) or die ("Failed to save data at the memcache server");
      // $memcache->set($key_alive, $key_stay, time()+$memcached_timeout) or die ("Failed to save data at the memcache server");
  	  $result=$memcache->get($key_alive);
  	// var_dump($key_stay);
      return $result;
  }

	// --------------------------------------------------------------------------
	// 娛樂城列表
	// 1.建立 key 並經過 hash。     ->$key_alive
	// 2.建立好sql條件。            ->$getdata_sql
	// 3.丟入 function checkmemcache($key_alive,$getdata_sql)中。
	// 4.若傳回來的值為空           -> 代表 memcache 有此 key，可以直接從 memcache 中撈資料。
	// 5.若傳回來的值不為空         -> 代表 memcache 沒有此 key，其值為: 函式中把 $getdata_sql 執行後取值，return回來。
	// 6.設定好 timeout 時間，代表若時間已過，memache 中此 key 的資料也將不在，要重撈。  ->$memcached_timeout
	// 7.透過函式 setandget_memcache 將 key 與資料存入 memcache 中，下一次詢問 memcache 時，就可以直接從 memcache 拿取。
	// --------------------------------------------------------------------------
  function sys_casino_list($force_update=0){
  	$key_casino = 'casino_list';
  	$key_alive_casino = sha1($key_casino);
  	$casino_list_sql = "SELECT casinoid FROM casino_list";
  	$casino_list = checkmemcache($key_alive_casino);
  	if($casino_list =='' OR $force_update == 1 ){
      $casino_list = [];
      $casino_list_mem=runSQLall($casino_list_sql);
  		unset($casino_list_mem[0]);
  		foreach ($casino_list_mem as $casino) {
  			$casino_list[] = $casino->casinoid;
  		}
  		$memcached_timeout = 5;
  		$casino_list = setandget_memcache($key_alive_casino, $casino_list,$memcached_timeout);
  	}
    return $casino_list;
  }

	// -----------------------------------------------------
	// 取得前後台會員端設定相關資訊
	// -----------------------------------------------------
	// 前後台變數都會用到 root_protalsetting 這張 table 裡的設定值
	// 在這一次取出方便後面變數初始化
	// setttingname = 'default' 會員端設定名稱
  function sys_protalsetting_list($force_update = 0){
  	$key_pro='protalsetting_list';
  	$key_alive_pro = sha1($key_pro);
  	$protalsetting_list_sql = "SELECT id, name, value FROM root_protalsetting WHERE setttingname = 'default' AND status = '1' ORDER BY id;";
  	$protalsetting=checkmemcache($key_alive_pro);
    // var_dump($protalsetting);
  	if($protalsetting == '' OR $force_update == 1 ){
      $protalsetting = [];
      $protalsetting_list_sql_result=runSQLall($protalsetting_list_sql);
  		if ($protalsetting_list_sql_result[0] >= 1) {
  			for($i=1;$i<=$protalsetting_list_sql_result[0];$i++) {
  				$protalsetting[$protalsetting_list_sql_result[$i]->name] = $protalsetting_list_sql_result[$i]->value;

  			}
  		}
  		$memcached_timeout = 60;
  		$protalsetting = setandget_memcache($key_alive_pro, $protalsetting,$memcached_timeout);
  	}
    return $protalsetting;
  }

  // -----------------------------------------------------
  // 使用者所在的時區，sql 依據所在時區顯示 time
  // -----------------------------------------------------
  // 有設定則依據使用者變數來設定，如果沒有的話就使用系統預設。
  function sys_user_timezone($force_update=0){
  	if(isset($_SESSION['agent']->timezone) AND $_SESSION['agent']->timezone != NULL) {
  		$tz = $_SESSION['agent']->timezone;
  	}else{
  		$tz = '+08';
  	}
  	$key_tz='pg_timezone_names';
  	$key_alive_tz = sha1($key_tz);
  	// 轉換時區所要用的 sql timezone 參數
  	$tzsql = "select * from pg_timezone_names where name like '%posix/Etc/GMT%' AND abbrev = '".$tz."'; ";
  	$tzonename=checkmemcache($key_alive_tz);
  	if($tzonename == '' OR $force_update == 1 ){
      $get_tz_result=runSQLall($tzsql);
  		if($get_tz_result[0]==1){
  			$tzonename = $get_tz_result[1]->name;
  		}else{
  			$tzonename = 'posix/Etc/GMT-8';
  		}
  		$memcached_timeout = 60;
  		$tzonename = setandget_memcache($key_alive_tz, $tzonename,$memcached_timeout);
  	}
    return $tzonename;
  }

  // -----------------------------------------------------
  // gcash 及 gtoken 的 casher id 資料
  // -----------------------------------------------------
  function sys_casherdata($force_update=0){
  	$key_casherdata='sys_casherdata';
  	$key_alive_casherdata = sha1($key_casherdata);
  	// 轉換時區所要用的 sql timezone 參數
  	$casherdatasql = "select id,account from root_member where account IN ('gcashcashier','gtokencashier'); ";
  	$casherdata=checkmemcache($key_alive_casherdata);
  	if($casherdata == '' OR $force_update == 1 ){
      $casherdata = [];
      $casherdata_result=runSQLall($casherdatasql);
  		if($casherdata_result[0] >= 1){
        array_shift($casherdata_result);
        foreach($casherdata_result as $k => $casher){
    			$casherdata[$casher->account] = $casher->id;
        }
  		}else{
        die('（SA501）会员系统错误，请洽客服处理！！');
      }
  		$memcached_timeout = 60;
  		$casherdata = setandget_memcache($key_alive_casherdata, $casherdata,$memcached_timeout);
  	}
    return $casherdata;
  }


// =============================================================================
// 變數類別： 環境變數
// 變數檔案： 程式後台全部程式使用
// =============================================================================

	// session timeout
	// ini_set('session.cookie_lifetime', 86400);
	// 可用 print_r(session_get_cookie_params()); 觀察
	// ini_set('session.gc_maxlifetime', 86400);
	// 可用 echo ini_get("session.gc_maxlifetime");

	// 程式預設的 timezone +0800
	date_default_timezone_set('Asia/Taipei');

  // 宣告變數
  $system_config = [];
  $MG_CONFIG = [];
  $PT_CONFIG = [];
  $GPKAPI_CONFIG = [];
  $MEGA_CONFIG = [];
  $RGAPI_CONFIG = [];
  $IG_CONFIG = [];
  $GPK2_TRIAL = [];
  $stationmail = [];
  $transaction_category = [];
  $auditmode_select = [];
  $rule = [];
  $customer_service_cofnig = [];
  $member_register = [];
  $page_config = [];
  $gamelobby_setting = [];
  // 人工接的娛樂城，路徑需要特別處理
  $specialCasinos = ['MG', 'MEGA', 'IG', 'RG'];

	// -----------------------------------------------------
	// 前台：register.php 使用的預設參數
	// register.php, register_action.php ,member.php  都有使用此變數
	// -----------------------------------------------------
	$system_config['default_agent'] = (isset($config['default_agent']) AND $config['default_agent'] != '' ) ? $config['default_agent'] : 'bigagent';
	$system_config['withdrawal_default_password'] 		= '12345678';

	// -----------------------------------------------------
	// 使用在 login.php
	// -----------------------------------------------------
	// 登入的驗證碼，增加一組可以直接進入的驗證碼。給 test unit 使用 only 4 letters。
	// use in : login_action.php
	$system_config['captcha_for_test'] 	= '5566';

  // gcash 及 gtoken 的 casher id 資料
  // $system_config['casherid']['gcashcashier']： gcashcashier 的 id
  // $system_config['casherid']['gtokencashier']： gtokencashier 的 id
  $system_config['casherid'] 	=  sys_casherdata();

  // 娛樂城列表
  $system_config['casino_list'] = sys_casino_list();

	// -----------------------------------------------------
	// Totalegame MG2 的 API 帳號密碼 , 使用在 lobby_mggame_lib.php
	// -----------------------------------------------------
	// 管理界面 https://tegbolll.totalegame.net/ , 正式機器需要更換帳號
	// 應用在 MG_API_login($method, $debug=0, $MG_API_data) 函式內
	$MG_CONFIG['url'] = "https://tegapi05.totalegame.net/";
	$MG_CONFIG['apiaccount']	= 'test188405';
	$MG_CONFIG['apipassword']	= 'f3ac74';
	$MG_CONFIG['CSID']['16118'] = array('subid' => '2207','flash' => 'igamingA5','html5' => 'igamingA5HTML5');
	$MG_CONFIG['CSID']['16619'] = array('subid' => '2210','flash' => 'igamingA7','html5' => 'igamingA7HTML5');
	$MG_CONFIG['CSID']['21410'] = array('subid' => '2215','flash' => 'igamingA8','html5' => 'igamingA8HTML5');
	// -----------------------------------------------------

	// -----------------------------------------------------
	// PT 的 API 帳號密碼 , 使用在 lobby_ptgame_lib.php
	// -----------------------------------------------------
	// 管理界面 http://gpkbet.imptbo2.inplaymatrix.com, 正式機器需要更換帳號
	// 應用在 ptapi()  函式內
	$PT_CONFIG['apiaccount']	= 'luck17prod';
	$PT_CONFIG['apipassword']	= 'eOJiDSunbh9AdNZS4A6dQqDMdqQGDaDB';
	// -----------------------------------------------------

	// -----------------------------------------------------
	// GPK API 帳號密碼 , 使用在 lobby_ptgame_lib.php
	// -----------------------------------------------------
	// 管理界面 http://gpkbet.imptbo2.inplaymatrix.com, 正式機器需要更換帳號
	// 應用在 ptapi()  函式內
	$GPKAPI_CONFIG['url'] = $config['gpk2_url'];
	// 正式環境用 $GPKAPI_CONFIG['url'] = 'https://kapi.apighub.com/';
	$GPKAPI_CONFIG['id'] = $config['projectid'].'2';
	$GPKAPI_CONFIG['VI']	= base64_decode('hDfDr48tcpQ3f51H4FQk7Q==');
	$GPKAPI_CONFIG['PASSPHRASE']	= base64_decode('CBmxB10FFas3O9O5j4pkDuEbT6+BobcWZYPkNc2ahUU=');
	$GPKAPI_CONFIG['pw'] = '1234qwer';
	// -----------------------------------------------------

	// -----------------------------------------------------
	// RG API 參數 , 使用在 lobby_rggame_lib.php
	// -----------------------------------------------------
	$RGAPI_CONFIG['apikey']	= '1bfe867954984b52cde26ac04146ccd466544521';
	$RGAPI_CONFIG['api_url'] = 'https://api-demo.mach-play.com/'; // 測試線
//	$RGAPI_CONFIG['api_url'] = 'http://gc-api.machplay.cc/'; // 正式線
	$RGAPI_CONFIG['sub_url'] = array(
		'CreateMember' => 'api_CreateMember?',
		'Login' => 'api_Login?',
		'GetMemberCurrentInfo' => 'api_GetMemberCurrentInfo?',
		'KickMember' => 'api_KickMember?',
		'Transfer' => 'api_Transfer?',
		'CheckTransfer' => 'api_CheckTransfer?',
		'GetStatement' => 'api_GetStatement?',
		'GetStatementByTime' => 'api_GetStatement_byTime?',
		'GetGameList' => 'api_GetGameList?',
		'GameRecordView' => 'api_GameRecordView?',
		'GameRecordLink' => 'api_GameRecordLink?',
		'LotteryMasterLogin' => 'lotteryMaster_Login?',
		'LotteryMasterOddsTable' => 'lotteryMaster_OddsTable?'
	);
	// -----------------------------------------------------

	// -----------------------------------------------------
	// PT 的 API 帳號密碼 , 使用在 lobby_ptgame_lib.php
	// -----------------------------------------------------
	// 管理界面 http://gpkbet.imptbo2.inplaymatrix.com, 正式機器需要更換帳號
	// 應用在 ptapi()  函式內
	$MEGA_CONFIG['apiaccount']	= '';
	$MEGA_CONFIG['apipassword']	= '';
	$MEGA_CONFIG['apikey']	= '1b89c093ed918c26ab84fb76c06c797b';
	// -----------------------------------------------------

	// -----------------------------------------------------
	// IG 的 API CONFIG , 使用在 lobby_iggame_lib.php
	// -----------------------------------------------------

	// 香港彩、時時彩只有 LOGIN 的接口不同
	// 測試線
	$IG_CONFIG = [
		'url' => (object) [
			'lotto' => 'http://gpklottosw.iasia99.com/gbkapilottosw/app/api.do',
			'lottery' => 'http://gbklotterysw.iasia999.com/gbkapilotterysw/app/api.do',
			'trade' => 'http://gbktradesw.iasia99.com/gbkapitradesw/app/api.do',
			'record' => 'http://gbkrecordsw.iasia99.com/gbkapirecordsw/app/api.do'
		],
		// 'hashCode' => 'tgpk2aa01_067e0a53-df18-4105-9b07-ad',
		'mode' => 'test'
	];

	// 正式線
	// $IG_CONFIG = [
	// 	'url' => (object) [
	// 		'lotto' => 'http://gbklotto.ppkp88.com/gbkapilotto/app/api.do',
	// 		'lottery' => 'http://gbklottery.ppkp88.com/gbkapilottery/app/api.do',
	// 		'trade' => 'http://gbktrade.ppkp88.com/gbkapitrade/app/api.do',
	// 		'record' => 'http://gbkrecord.ppkp88.com/gbkapirecord/app/api.do'
	// 	]
	//  'hashCode' => '',
	//  'mode' => ''
	// ];
	// -----------------------------------------------------

	// -----------------------------------------------------
	// 前台及後台使用 : 試玩帳號的 agent 預設帳號, 所有的錢及測試帳號都由這個使用者建立
	// 前台： trial.php 檔案專用
	// -----------------------------------------------------
	// 預設試玩代理商帳號
	$GPK2_TRIAL['default_agent']				= 'trial';
	// 預設試玩代理商帳號密碼，轉帳使用。
	$GPK2_TRIAL['default_password']				= '12345678';
	$GPK2_TRIAL['default_password_sha1']	= sha1(	$GPK2_TRIAL['default_password']	);
	// 預設試玩帳號，每一個帳號的預設 balance 額度。
	$GPK2_TRIAL['default_coin']						= 50;
	// 沒錢自動儲值 on/off , 1/0
	$GPK2_TRIAL['auto_deposit_coin']			= 1;
	// 預設帳號間隔，上次中止後多久才可以玩？(秒)
	$GPK2_TRIAL['default_timeinterval']		= 60*60*24;
	// 預設帳號登入後，可以使用多久？(秒)
	$GPK2_TRIAL['default_timeout']				= 60*60*1;
	// -----------------------------------------------------





	// -----------------------------------------------------
	// 前台：站內信件
	// stationmail_action.php 有使用此變數
	// -----------------------------------------------------

	$stationmail['sendto_system_cs'] = (isset($config['default_system_account'])) ? $config['default_system_account'] : 'jigcs';
	// -----------------------------------------------------






	// -----------------------------------------------------
	// 交易的類別分類：應用在所有的錢包相關程式內
	// 使用範圍：前台 and 後台
	// -----------------------------------------------------
	// 交易類別 -- 現金 GCASH
	// -----------------------------------------------------
	// 銀行現金存款，或是人工入款. 由 $gcash_cashier_account 轉入會員帳號
	$transaction_category['cashdeposit'] 			= '现金存款';
	// 金流微服務透過 api 存款  ，由 $gcash_cashier_account 轉入會員帳號
	$transaction_category['apicashdeposit'] 		= 'API现金存款';
	// 第三方支付入款 ， 由 $gcash_cashier_account 轉入 會員帳號
	$transaction_category['payonlinedeposit'] = '电子支付存款';
	// 會員間的現金轉帳
	$transaction_category['cashtransfer'] 		= '现金转帐';
	// 會員提領現金，回收gcash到$gcash_cashier_account
	$transaction_category['cashwithdrawal'] 	= '现金取款';
	$transaction_category['reject_cashwithdrawal'] = '现金取款退回';

	// 會員提領現金 by api，回收gcash到$gcash_cashier_account
	$transaction_category['apicashwithdrawal'] 	= 'API现金取款';
	// 將 gcash to gtoken，回收gcash到$gcash_cashier_account, $gtoken_cashier_account 轉入到 gtoken
	$transaction_category['cashgtoken'] 			= '现金转游戏币';
	// 現金提款行政費費用, 使用在現金gcash提款為現金的收入費用。費用包含轉帳費，及稽核不過行政費。 2017.3.15
	$transaction_category['cashadministrationfees'] 		= '现金取款行政费';

	// 前台公司入款到後台审核，不論後台設定是存到gcashpassbook、gtokenpassbook，都叫公司入款(field：transaction_category)
	$transaction_category['company_deposits'] 		= '公司存款';
	$transaction_category['reject_company_deposits'] = '公司存款退回';

	// 後臺佣金發放，不論是存到gcashpassbook、gtokenpassbook，都叫代理佣金(field：transaction_category)
	$transaction_category['agent_commission'] 		= '代理佣金';


	// -----------------------------------------------------
	// 交易類別 -- 代幣 GTOKEN
	// -----------------------------------------------------
	// $gtoken_cashier_account 轉到每個會員，只有管理員可以執行，屬於給予 gtoken 的行為. 此項目需要被稽核
	$transaction_category['tokendeposit'] = '游戏币存款';
	// $gtoken_cashier_account 轉到每個會員，只有管理員可以執行，屬於給予 gtoken 的行為. 此項目需要被稽核
	$transaction_category['apitokendeposit'] = 'API游戏币存款';
	// $gtoken_cashier_account 轉到每個會員，只有管理員可以執行，屬於給予 gtoken 的行為. 此項目需要被稽核
	$transaction_category['tokenfavorable'] = '游戏币优惠';
	// $gtoken_cashier_account 轉到每個會員，只有管理員可以執行，屬於給予 gtoken 的行為. 此項目需要被稽核
	$transaction_category['tokenpreferential'] = '反水';
	// $gtoken_cashier_account 轉到每個會員，只有管理員可以執行，屬於給予 gtoken 的行為. 此項目無須稽核
	$transaction_category['tokenpay'] = '游戏币派彩';
	// 提領代幣轉成現金，會員執行需要稽核流程加入。
	$transaction_category['tokengcash']             = '游戏币转银行';
	$transaction_category['reject_tokengcash']      = '游戏币转银行退回';
	$transaction_category['tokentogcashpoint']        = '游戏币转现金';
	$transaction_category['reject_tokentogcashpoint'] = '游戏币转现金退回';

	// 會員提領代幣 by api，是否需要稽核？可能用途為商城結帳
	$transaction_category['apitokenwithdrawal'] = 'API游戏币取款';
	// 提領代幣轉成現金，只能管理員執行，無須稽核流程。通常為回收錯誤的派彩。
	$transaction_category['tokenrecycling'] = '游戏币回收';
	// 代幣提款行政費費用, 使用在代幣提款為現金的收入費用。費用包含轉帳費，及稽核不過行政費。 2017.3.15
	$transaction_category['tokenadministrationfees'] = '游戏币取款行政费';
	// -----------------------------------------------------
	// 公司代表帳號, 也就是 ROOT 帳號的資訊
	$config['system_company_account'] = 'root';
	// 因為通常是會 ID = 1 , 避免寫成 hardcore
	$config['system_company_id'] = 1;

	// GCASH提出的轉入帳戶。現金出納帳戶
	$gcash_cashier_account 	= 'gcashcashier';

	// GTOKEN 提出的轉入帳戶。代幣出納帳戶
	$gtoken_cashier_account = 'gtokencashier';

	// -----------------------------------------------------
	// 給 Table: root_member_gtokenpassbook 的 auditmode 欄位 3種：免稽核freeaudit、存款稽核depositaudit、優惠存款稽核 shippingaudit
	// 用於所有的 gtoken 程式
	$auditmode_select['freeaudit']			= '免稽核';
	$auditmode_select['depositaudit']		= '存款稽核';
	// 反水和優惠都一樣，用優惠稽核項目
	$auditmode_select['shippingaudit']	= '优惠稽核';
	// -----------------------------------------------------
	// 配合代幣派彩，區分那各娛樂城的派彩
	$auditmode_select['mg']   = 'MG';
	$auditmode_select['pt']   = 'PT';
	$auditmode_select['mega'] = 'MEGA';
	$auditmode_select['ig']   = 'IG';
	$auditmode_select['gpk2']   = 'GPK2';
	$auditmode_select['cq9']   = 'CQ9';
	$auditmode_select['nwg']   = 'NWG';
	$auditmode_select['pgs']   = 'PGS';
	$auditmode_select['jdb']   = 'JDB';
	$auditmode_select['rg']   = 'RG';
	$auditmode_select['kg']   = 'KG';
	$auditmode_select['mgplus']   = 'MGPLUS';

	// -----------------------------------------------------

	// -----------------------------------------------------
	// END 貨幣相關的交易類別及變數
	// -----------------------------------------------------


	// 使用者所在的時區，sql 依據所在時區顯示 time
  $tzonename = sys_user_timezone();

	// 把 $tzonename 直接用就可以 , sql example 如下
	// to_char((enrollmentdate AT TIME ZONE '$tzonename'),'YYYY-MM-DD') as enrollmentdate_tz
	// -----------------------------------------------------

  // 取得前後台會員端設定相關資訊
  $protalsetting = sys_protalsetting_list();

	// -----------------------------------------------------
	// 前台 + 後台：會員要申請成為代理商時，需要有少額度才可以申請。
	// DB table:root_protalsetting
	// 前台 - member.php, register_agent.php, register_agent.php 都有使用此變數
	// 後台 - agent_review_action.php 都有使用此變數
	//
	// 如果沒取到 DB 內申請費用就使用預設10000
	// -----------------------------------------------------

  $system_config['agency_registration_gcash'] = ($protalsetting['agency_registration_gcash'] != '') ? $protalsetting['agency_registration_gcash'] : 10000;
	// -----------------------------------------------------


	// -----------------------------------------------------
	// 放射線組織 -- 四層分紅獎金計算資訊 -- 前台及後台都有使用
	// -----------------------------------------------------
	// 後台使用的檔案有
	// bonus_commission_agent.php
	// bonus_commission_sale.php
	// bonus_commission_profit.php
	// 前台使用的檔案
	// agencyarea.php

	// -----------------------------------------------------
  // 放射線組織-獎勵分紅辦法 , 獎金計算參數
  // 保證四層分紅-上游第1層(未達業績者不列入分紅計算,往上層保留層數，直到公司帳號) 單位 %
  $rule['commission_1_rate'] = 30;
  // 保證四層分紅-上游第2層(未達業績者不列入分紅計算,往上層保留層數，直到公司帳號) 單位 %
  $rule['commission_2_rate'] = 10;
  // 保證四層分紅-上游第3層(未達業績者不列入分紅計算,往上層保留層數，直到公司帳號) 單位 %
  $rule['commission_3_rate'] = 10;
  // 保證四層分紅-上游第4層(未達業績者不列入分紅計算,往上層保留層數，直到公司帳號) 單位 %
  $rule['commission_4_rate'] = 10;
  // 保證四層分紅 - 公司成本 , 單位 %
  $rule['commission_root_rate'] = 40;
	// 以上累加百分比加總後需為 1
	// ----------------------------------
	// 收入(1)-加盟金
	// ----------------------------------
	// 審閱期 N days
	//$rule['income_commission_reviewperiod_days'] = 3;
	$rule['income_commission_reviewperiod_days'] = $protalsetting['income_commission_reviewperiod_days'];

	// 結算獎金的週期, 每次 n 天算一次  n >=1 n<=7 , 目前系統預設為 1 日 , 設定超過 1 日時, 需要注意重疊時間的問題.
	$rule['stats_commission_days'] = 1;
	// ----------------------------------
	// 收入(2)-營業獎金
	// ----------------------------------
	/*
	// 每個營業點(代理商)需要達成的業績量，才可以參與營運分配。
	$rule['amountperformance'] = 10000;
	// 營業獎金分紅比例 -- (此獎金分配和反水分配共用，需要注意避免總和超過利潤) , 單位 %
	$rule['sale_bonus_rate']    = 1.8;
	// 營業獎金統計週期 美東時間(日)
	$rule['stats_bonus_days']   = 7;
	*/
	$rule['amountperformance'] = $protalsetting['amountperformance'];
	// 營業獎金分紅比例 -- (此獎金分配和反水分配共用，需要注意避免總和超過利潤) , 單位 %
	$rule['sale_bonus_rate']    = $protalsetting['sale_bonus_rate'];
	// 營業獎金統計週期 美東時間(日)
	$rule['stats_bonus_days']   = $protalsetting['stats_bonus_days'];
	// 預設星期幾為預設 7 天的起始週期
	$rule['stats_weekday']			= 'Wednesday';

	// ----------------------------------
	// 收入(3)-公司的營業利潤獎金設定
	// ----------------------------------
	// 營利獎金結算週期，每月的幾號？ 美東時間 (固定週期為月)
	$rule['stats_profit_day']    = 10;
	// 營利獎金發放門檻 -- option 不一定要限制，看是否為正值
	$rule['amountperformance_month'] = $rule['amountperformance'] * 4;
	// 營業利潤計算時 平台佔營運成本的比例
	$rule['platformcost_rate'] 		=	 12;
	// 金流成本比例 0.8 ~ 2%
	$rule['cashcost_rate'] = 1;

	// ----------------------------------
	// 收入(4)-股利分紅等級設定
	// ----------------------------------


	// 如果從DB table : root_protalsetting 有取到後台設定值, 就重新設定參數值
	if(count($protalsetting) >= 1) {
		// --------------------------
		// 四層分紅
		// --------------------------
		if ($protalsetting['commission_1_rate'] != '') {
			$rule['commission_1_rate'] = $protalsetting['commission_1_rate'];
		}
		if ($protalsetting['commission_2_rate'] != '') {
			$rule['commission_2_rate'] = $protalsetting['commission_2_rate'];
		}
		if ($protalsetting['commission_3_rate'] != '') {
			$rule['commission_3_rate'] = $protalsetting['commission_3_rate'];
		}
		if ($protalsetting['commission_4_rate'] != '') {
			$rule['commission_4_rate'] = $protalsetting['commission_4_rate'];
		}
		if ($protalsetting['commission_root_rate'] != '') {
			$rule['commission_root_rate'] = $protalsetting['commission_root_rate'];
		}

		// --------------------------
		// 加盟金
		// --------------------------
		if ($protalsetting['income_commission_reviewperiod_days'] != '') {
			$rule['income_commission_reviewperiod_days'] = $protalsetting['income_commission_reviewperiod_days'];
		}
		// if ($protalsetting['stats_commission_days'] != '') {
		// 	$rule['stats_commission_days'] = 1;
		// }

		// --------------------------
		// 營業獎金
		// --------------------------
		if ($protalsetting['amountperformance'] != '') {
			$rule['amountperformance'] = $protalsetting['amountperformance'];
		}
		if ($protalsetting['sale_bonus_rate'] != '') {
			$rule['sale_bonus_rate'] = $protalsetting['sale_bonus_rate'];
		}
		if ($protalsetting['stats_bonus_days'] != '') {
			$rule['stats_bonus_days'] = $protalsetting['stats_bonus_days'];
		}
		// if ($protalsetting['stats_weekday'] != '') {
		// 	$rule['stats_weekday']			= 'Wednesday';
		// }

		// --------------------------
		// 公司的營業利潤獎金設定
		// --------------------------
		if ($protalsetting['stats_comission_days'] != '') {
			$rule['stats_profit_day'] = $protalsetting['stats_comission_days'];
		}
		// if ($protalsetting['amountperformance'] != '') {
		// 	$rule['amountperformance_month'] = $rule['amountperformance'] * 4;
		// }
		// if ($protalsetting['platformcost_rate'] != '') {
		// 	$rule['platformcost_rate'] =	12;
		// }
		// if ($protalsetting['cashcost_rate'] != '') {
		// 	$rule['cashcost_rate'] = 1;
		// }
	}



  // -------------------------------------------------------------------------
  // 放射線組織-獎勵分紅辦法
  // -------------------------------------------------------------------------
  $rule_text =
  '每代保证 4 层收益：每个会员业绩(营业额)达成，保证收入下 4 层分红。
  每周分红一次， 一年 52 次分红。
  未达成业绩者利润放入彩池，合并于股利分红时发放。

  收入来源：
  ----------------
  1. 代理加盟金：
  * 只赚第一次，加盟公司审查通过后，并通过审阅期 '.$rule['income_commission_reviewperiod_days'].' 天后，计算并发放分红加盟金。(人工管制)
  * 需要加盟成为代理商后，才可以推荐会员加入。开始招募会员后，审阅期就则立即结束。(人工管制)
  * 系统每日计算通过审阅期的加盟金，并分红为系统现金派彩。
  * 如果有加盟金发生，直接发予上四代会员，不受营业额达成限制。
  * 目前加盟金為 '.$config['currency_sign'].$system_config['agency_registration_gcash'].'

  2. 营业奖金：
  * 投注量(营业额)有达成者，以投注量提出适当百分比 1%~3% 为营业奖金 ，目前设定为个人投注量的'.$rule['sale_bonus_rate'].' % 为营业奖金收入。
	* 每周分红一次。目前設定 '.$rule['stats_weekday'].' 为每周结算日。
  * 需要依据游戏别不同设定，不同游戏类别的营业奖金提拨比例不同，此提拨奖金比例需要与反水量合并考量。
  * 每个会员达成业绩的定义：在一周内，达成投注量 '.$rule['amountperformance'].' 值以上，视为达成业绩。 結算日'.$rule['stats_weekday'].'時，计算上周营业奖金分红，并分红为系统现金派彩。
  * 分红计算时，如果上1代没有达成业绩，此业绩往更上1代计算的时候。

  3. 公司的营利奖金(公司代理商营运得到的利润)
  * 发放频率：一个月一次 ，目前設定為每個月的 '.$rule['stats_profit_day'].' 日為結算日。
  * 以目前個人的投注損益為平台收入，个人贡献平台的损益 = (个人娱乐城损益 - 平台成本(12%) - 行销成本 - 金流成本) ，再以4层分红方式分配红利。
  * 当期损益结算为负值时，保留到下期营利后扣除。
  * 分紅的業績門檻為個人投注量 '.$rule['amountperformance_month'].' 達成後才可以進行四層分紅。
	* 分红计算时，如果上1代没有达成业绩，此业绩往更上1代计算的时候。

  4. 股利分配(公司的营收净利，扣除成本) / 依据公司设定(无系统报表)不上系统，但有参考报表。
  * 依据系统报表参数的资讯，将会员分层 A,B,C 三个层级，依据层级分配股利。
  * 分层后依据分配员，分红为系统现金派彩。

  Q&A
  ------------
  每个会员的 4 层加盟的分红计算：上面加盟金、营业奖金、营利奖金 3 个收入来源，都以此方式分红。
  上游第1层 '.$rule['commission_1_rate'].'%
  上游第2层 '.$rule['commission_2_rate'].'%
  上游第3层 '.$rule['commission_3_rate'].'%
  上游第4层 '.$rule['commission_4_rate'].'%
  公司成本'.$rule['commission_root_rate'].' % 四層分紅累加百分比加總後需為 100%
  如果上游以无上一代，将分红归类到公司帐号。
  ';

  $show_rule_html = '
  <!-- Button trigger modal -->
  <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#myModal">
    放射线组织-奖励分红办法
  </button>

  <!-- Modal -->
  <div class="modal fade bs-example-modal-lg" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
         <h4 class="modal-title" id="myModalLabel">放射线组织-奖励分红办法</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
          '.nl2br($rule_text).'
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
  ';
	// -------------------------------------------------------------------------
  // 放射線組織-獎勵分紅辦法 end
  // -------------------------------------------------------------------------


  // -------------------------------------------------------------------------
  // var_dump($rule);
  // -------------------------------------------------------------------------

// ============================================================================
// 前台專用變數區
// 底下只放前台專用的變數資訊，有些資訊可以從資料庫中直接撈出來覆蓋。
// ============================================================================

	// -----------------------------------------------------
	// 前台：客服資訊
	// DB table:root_protalsetting
	// contactus.php, lib.php 都有使用此變數
	//
	// 如果沒取到 DB 內客服資訊就使用預設客服資訊
	// -----------------------------------------------------

	// $customer_service_cofnig['online_weblink'] = $_SERVER['HTTP_HOST'].'/contactus.php';
	// $customer_service_cofnig['qq'] = 'gpk17_5884655';
	// $customer_service_cofnig['email'] = 'gpk777mail@gpk17.com';
	// $customer_service_cofnig['mobile_tel'] = '0912456789';
	// $customer_service_cofnig['wechat_qrcode'] = 'WechatQR5678';

	$customer_service_cofnig['online_weblink'] = '';
	$customer_service_cofnig['qq'] = '';
	$customer_service_cofnig['email'] = '';
	$customer_service_cofnig['mobile_tel'] = '';
	$customer_service_cofnig['wechat_qrcode'] = '';

	// ---------------
	// 20200429
	$customer_service_cofnig['contact_app_name'] = '';
	$customer_service_cofnig['contact_app_id'] = '';
	$customer_service_cofnig['qrcode_1'] = '';
	$customer_service_cofnig['contact_app_name_2'] = '';
	$customer_service_cofnig['contact_app_id_2'] = '';
	$customer_service_cofnig['qrcode_2'] = '';
	// //---------------

	// if ($protalsetting['customer_service_online_weblink'] != '') {
	// 	$customer_service_cofnig['online_weblink'] = $protalsetting['customer_service_online_weblink'];
	// }
	// if ($protalsetting['customer_service_qq'] != '') {
	// 	$customer_service_cofnig['qq'] = $protalsetting['customer_service_qq'];
	// }
	// if ($protalsetting['customer_service_email'] != '') {
	// 	$customer_service_cofnig['email'] = $protalsetting['customer_service_email'];
	// }
	// if ($protalsetting['customer_service_mobile_tel'] != '') {
	// $customer_service_cofnig['mobile_tel'] = $protalsetting['customer_service_mobile_tel'];
	// }
	// if ($protalsetting['customer_service_wechat_qrcode'] != '') {
	// $customer_service_cofnig['wechat_qrcode'] = $protalsetting['customer_service_wechat_qrcode'];
	// }
	// // 加上 wechat ID
	// if ($protalsetting['customer_service_wechat_id'] != '') {
	// $customer_service_cofnig['customer_service_wechat_id'] = $protalsetting['customer_service_wechat_id'];
	// }

	// -----------------------------
	// 20200427
	// 网页线上客服
	if ($protalsetting['customer_service_online_weblink'] != '') {

		$array_customer = json_decode($protalsetting['customer_service_online_weblink']);

		$customer_service_cofnig['online_weblink'] = (isset($array_customer->status) && $array_customer->status == 'on') ? $array_customer->contact : '';
		// if(isset($array_customer->status) AND $array_customer->status == 'on'){
		// 	$customer_service_cofnig['online_weblink'] =  $array_customer->contact;
		// }else{
		// 	$customer_service_cofnig['online_weblink'] = $protalsetting['customer_service_online_weblink'];
		// }
	}
	if ($protalsetting['customer_service_qq'] != '') {
		$customer_service_cofnig['qq'] = $protalsetting['customer_service_qq'];
	}
	// email
	if ($protalsetting['customer_service_email'] != '') {
		$array_customer = json_decode($protalsetting['customer_service_email']);

		$customer_service_cofnig['email'] = (isset($array_customer->status) && $array_customer->status == 'on') ? $array_customer->contact : '';
		// if(isset($array_customer->status) AND $array_customer->status =='on'){
		// 	$customer_service_cofnig['email'] = $array_customer->contact;
		// }else{
		// 	$customer_service_cofnig['email'] = $protalsetting['customer_service_email'];
		// }
	}
	// phone
	if ($protalsetting['customer_service_mobile_tel'] != '') {
		$array_customer = json_decode($protalsetting['customer_service_mobile_tel']);

		$customer_service_cofnig['mobile_tel'] = (isset($array_customer->status) && $array_customer->status == 'on') ? $array_customer->contact : '';
		// if(isset($array_customer->status) AND  $array_customer->status =='on'){
		// 	$customer_service_cofnig['mobile_tel'] = $array_customer->contact;
		// }else{
		// 	$customer_service_cofnig['mobile_tel'] = $protalsetting['customer_service_mobile_tel'];
		// }

	}
	if ($protalsetting['customer_service_wechat_qrcode'] != '') {
		$customer_service_cofnig['wechat_qrcode'] = $protalsetting['customer_service_wechat_qrcode'];
	}
	// 加上 wechat ID
	if ($protalsetting['customer_service_wechat_id'] != '') {
		$customer_service_cofnig['customer_service_wechat_id'] = $protalsetting['customer_service_wechat_id'];
	}

	// 社群媒體1
	if(isset($protalsetting['customer_service_customization_setting_1']) AND $protalsetting['customer_service_customization_setting_1'] != ''){
		$array_customer = json_decode($protalsetting['customer_service_customization_setting_1']);
		if(isset($array_customer->status) AND $array_customer->status == 'on'){
			$customer_service_cofnig['contact_app_name'] = $array_customer->contact_app_name;
			$customer_service_cofnig['contact_app_id'] = $array_customer->contact_app_id;

			// 社群媒體1 qrcode
			if(isset($protalsetting['customer_service_qrcode_1']) AND $protalsetting['customer_service_qrcode_1'] != ''){
				$customer_service_cofnig['qrcode_1'] = $protalsetting['customer_service_qrcode_1'];
			}
		}
	}
	// 社群媒體2
	if(isset($protalsetting['customer_service_customization_setting_2']) AND $protalsetting['customer_service_customization_setting_2'] != ''){
		$array_customer = json_decode($protalsetting['customer_service_customization_setting_2']);
		if($array_customer->status == 'on'){
			$customer_service_cofnig['contact_app_name_2'] = $array_customer->contact_app_name;
			$customer_service_cofnig['contact_app_id_2'] = $array_customer->contact_app_id;

			// 社群媒體2 qrcode
			if(isset($protalsetting['customer_service_qrcode_2']) AND $protalsetting['customer_service_qrcode_2'] != ''){
				$customer_service_cofnig['qrcode_2'] = $protalsetting['customer_service_qrcode_2'];
			}
		}
	}
	// -----------------------------------

	// -----------------------------------------------------

	// -----------------------------------------------------
	// 代理申請自動審核通過用變數
	//
	// register_agent.php 有使用此變數
	//
	// 操作者帳號預設為 jigcs
	// 是否自動審核通過預設為手動
	// -----------------------------------------------------

	// 代理申請自動審核通過時, 預設操作者帳號
	$automatic_review_processing_account = (isset($config['default_system_account'])) ? $config['default_system_account'] : 'jigcs';

	// 代理申請自動審核 手動(manual) / 自動(automatic)
	$agent_review_automatic_switch = 'manual';
	if ($protalsetting['agent_review_switch'] != '') {
		$agent_review_automatic_switch = $protalsetting['agent_review_switch'];
	}

	// -----------------------------------------------------


	// -----------------------------------------------------
	// 前台：會員等級設定
	// DB table:root_member_grade
	//
	// 加盟金取款 : withdrawapplicationgcash.php, withdrawapplicationgcash_action.php
	// 現金取款 : withdrawapplication.php, withdrawapplication_action.php
	// 公司入款 : deposit_company.php, deposit_company_action.php
	// 註冊 : register_action.php, register_lib.php
	// 以上檔案皆有使用此變數
	//
	// 如果沒取到 DB 內會員等級設定就使用預設會員等級設定資訊
	// -----------------------------------------------------

	/*
	取得該會員的會員等級
	未登入的訪客使用會員端設定的註冊送彩金使用會員等級設定
	*/
	if(isset($_SESSION['member']) AND $_SESSION['member']->grade != NULL) {
		$member_grade_sql = "SELECT grade FROM root_member WHERE account = '".$_SESSION['member']->account."';";
		// var_dump($mamber_favorablerule_sql);
		$member_grade_sql_result = runSQLall($member_grade_sql);
		// var_dump($mamber_favorablerule_sql_result);

		if ($member_grade_sql_result[0] == 1) {
			$member_grade = $member_grade_sql_result[1]->grade;
		} else {
			die('(x) 會員帳號等及查詢錯誤。');
		}

	} else {
		$member_grade = $protalsetting['registrationmoney_member_grade'];
	}

	// 取出會員等級設定
	$grade_sql = "SELECT * FROM root_member_grade WHERE status = 1 AND id = '".$member_grade."';";
	// var_dump($grade_sql);
	$grade_sql_result = runSQLALL($grade_sql);
	// var_dump($grade_sql_result);

	if ($grade_sql_result[0] == 1) {
		$member_grade_config_detail = $grade_sql_result[1];
	} else {
		//die('(x) 會員帳號等及查詢錯誤。');
	}
	// var_dump($member_grade_config_detail);

// 設定預設會員註冊狀態（自動審核時為 1，手動審核時為 4）;
$defaultstatus = (isset($protalsetting['member_register_review']) && $protalsetting['member_register_review'] == 'on') ? '4' : '1';
// var_dump($protalsetting['member_register_review']);
// var_dump($defaultstatus);

	// -----------------------------------------------------

	// -----------------------------------------------------
	// 前台：會員註冊時ip及指紋碼允許次數
	// DB table:root_protalsetting
	// register_action.php 有使用此變數
	//
	//
	// -----------------------------------------------------

	// 會員註冊ip允許次數
	$member_register['registerip_member_numberoftimes'] = 1;
	// 會員註冊指紋碼允許次數
	$member_register['registerfingerprinting_member_numberoftimes'] = 10;
	// 代理引導註冊ip允許次數
	$member_register['registerip_agent_numberoftimes'] = 50;
	// 代理引導註冊指紋碼允許次數
	$member_register['registerfingerprinting_agent_numberoftimes'] = 50;

	if(count($protalsetting) >= 1) {
		if ($protalsetting['registerip_member_numberoftimes'] != '') {
			$member_register['registerip_member_numberoftimes'] = $protalsetting['registerip_member_numberoftimes'];
		}
		if ($protalsetting['registerfingerprinting_member_numberoftimes'] != '') {
			$member_register['registerfingerprinting_member_numberoftimes'] = $protalsetting['registerfingerprinting_member_numberoftimes'];
		}
		if ($protalsetting['registerip_agent_numberoftimes'] != '') {
			$member_register['registerip_agent_numberoftimes'] = $protalsetting['registerip_agent_numberoftimes'];
		}
		if ($protalsetting['registerfingerprinting_agent_numberoftimes'] != '') {
			$member_register['registerfingerprinting_agent_numberoftimes'] = $protalsetting['registerfingerprinting_agent_numberoftimes'];
		}
	}

	// -----------------------------------------------------



// ============================================================================
// 前台專用變數區 END
// 底下只放前台專用的變數資訊，有些資訊可以從資料庫中直接撈出來覆蓋。
// ============================================================================


// ----------------------------------------------------------------------------
// 前台、後台都會用到, 只要是列表資料型態的都會使用。
// 表格的顯示參數, 可以有效控制表格顯示的分頁參數。
// ----------------------------------------------------------------------------
// datatables 使用參數，設定 datatables 每頁顯示的資料量
$page_config['datatables_pagelength'] = '10';
// 資料查詢分頁用參數，設定每次查詢資料的資料量
$page_config['page_limit'] = '100';
// 資料查詢分頁用參數，設定資料分頁用兩組按鈕的最大的資料跨距幅度
// EX：假設當前頁面所在的第一筆資料是資料庫裡的第11001篳資料，此參數設定為 10 ，
// 且上方$page_config['page_limit'] 設定為 1000 時，
// 點選向前查詢會分別可查詢到資料庫第 10001 筆至第 11000 筆資料及查詢到資料庫第 1001 筆到第 2000 筆資料
$page_config['page_rate'] = '3';
// ----------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// 前台、後台都會用到, 6大遊戲類別開關及相關處理
// ----------------------------------------------------------------------------
$gamelobby_setting['main_category_info'] = array(
	'game' => array( 'name' => 'Electronic entertainment','open' => '1', 'order' => '1', 'flatform' => 'game'),
	'Live' => array( 'name' => 'Live video','open' => '1', 'order' => '2', 'flatform' => 'live'),
	'Lottery' => array( 'name' => 'Lottery game','open' => '1', 'order' => '3', 'flatform' => 'lottery'),
	'Sport' => array( 'name' => 'Sport game','open' => '0', 'order' => '4', 'flatform' => 'sports'),
	'Fishing' => array( 'name' => 'Fishing people','open' => '1', 'order' => '5', 'flatform' => 'fish'),
	'Chessboard' => array( 'name' => 'Chess and card','open' => '0', 'order' => '6', 'flatform' => 'card')
);
if (isset($protalsetting['main_category_info']) AND $protalsetting['main_category_info'] != '') {
	$gamelobby_setting['main_category_info'] = json_decode($protalsetting['main_category_info'], true);
}
/**
 * 設定是否開啟使用者登入時要求更換新密碼用開關
 * 用於當新站為他站匯入但無密碼時使用，可於後台
 * 会员端等级详细设定做開啟或關閉
 */
$system_config['allow_login_passwordchg'] =  (isset($protalsetting['allow_login_passwordchg']) AND $protalsetting['allow_login_passwordchg'] != '') ? $protalsetting['allow_login_passwordchg'] : 'off';


// =============================================================================
// 此設定檔，讓依據不同站別來設定。
// =============================================================================
// END
?>
