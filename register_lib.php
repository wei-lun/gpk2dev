<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 註冊專用 lib
// File Name:	register_lib.php
// Author:		Neil
// Related:
// Log:
// ----------------------------------------------------------------------------


require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
// require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

// gtoken lib 現金轉帳函式庫
require_once dirname(__FILE__) ."/gtoken_lib.php";

require_once dirname(__FILE__) ."/spread_register_lib.php";


// ==============================================================================================
// function create_member_wallets_by_account($useraccount)
// 使用帳號建立錢包帳號, 確定錢包沒有存在才可以使用這個函式。
// 只給 register_action.php 使用
// ==============================================================================================
function create_member_wallets_by_account($useraccount) {
  global $tr;

  // 檢查帳號的 id 值
	$user_sql = "SELECT * FROM root_member WHERE account = '$useraccount';";
	$user_result = runSQLALL($user_sql);
	 //var_dump($user_result);

	if($user_result[0] == 1) {
		// account id 存在
		$member_wallets_addaccount_sql = "INSERT INTO root_member_wallets (id, changetime, gcash_balance, gtoken_balance) VALUES ('".$user_result[1]->id."', 'now()', '0', '0');";
		// var_dump($member_wallets_addaccount_sql);
		$rwallets = runSQL($member_wallets_addaccount_sql);
		if($rwallets == 1) {
			$logger = "";
			//echo $logger;
      $error['code'] = '1';
      //帳號錢包建立完成
      $error['messages'] = $tr['account wallets complete'];
      $logger = $error['messages'].'';
      // memberlog 2db("$useraccount",'member','info', "$logger");

      $msg=$error['messages'];
      $msg_log = $useraccount.$tr['account wallets complete'];
      $sub_service='wallet';
      memberlogtodb($useraccount,'member','info',"$msg",$useraccount,"$msg_log",'f',$sub_service);

      // 再取出一次, 更新 session
      // 如果沒有 session, 表示此會員註冊非代理引導註冊
      // 註冊成功後引導會員登入
      // 如果有 session, 表示該會員為代理引導註冊
      // 不引導會員登入, 如果 session 更新會兩個帳號都在登入狀態
			// $user_balance_sql = "SELECT * FROM root_member_wallets WHERE id = $userid;";
      // if(!isset($_SESSION['member'])) {
      //   $user_balance_sql = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.id = '".$user_result[1]->id."';";
      //   $user_balance_result = runSQLALL($user_balance_sql);
      //   $_SESSION['member'] = $user_balance_result[1];
      // }
    } else {
      $error['code'] = '401';      //帳號錢包建立失敗
      $error['messages'] = $tr['account wallets failed'];
    }
  } else {
    $error['code'] = '404';    //找不到此帳號
    $error['messages'] = $tr['can not found this account'];
  }
	return $error;
}

// ==============================================================================================

/*
會員註冊功能是否開啟
*/
function member_register_isopen() {
  global $protalsetting;

  if ($protalsetting['member_register_isopen'] == 'on') {
    $register_isopen = 1;
  } else {
    $register_isopen = 0;
  }

  return $register_isopen;
}

function get_defaultparent_data()
{
  global $system_config;

  $sql = <<<SQL
  SELECT *
  FROM root_member
  WHERE therole = 'A'
  AND account = '{$system_config['default_agent']}'
  AND status = '1'
SQL;

  $result = runSQLall($sql);

  if (empty($result[0])) {
    $error_msg = '预设推荐人资料查询错误';
    return array('stetus' => false, 'result' => $error_msg);
  }

  return array('ststus' => true, 'result' => $result[1]);
}

function agentcode_check($code = NULL, $ismust)
{
  $agentcode_input = filter_var($code, FILTER_SANITIZE_STRING);
  if(($agentcode_input == NULL OR $agentcode_input == '') AND $ismust == 'on') {
    $error['code'] = '0';
    $error['messages'] = '不合法的邀请码';

    return $error;
  }

  $check_linkcode = (object)select_spreadlink_bylinkcode($agentcode_input);
  if(!$check_linkcode->status) {
    $error['code'] = '300';
    $error['messages'] = $check_linkcode->result ;

    return $error;
  }

  if ($check_linkcode->result->end_date < getEDTDate()) {
		$error['code'] = '301';
    $error['messages'] = '邀请码已过期';

    return $error;
  }

  $mid_sql = <<<SQL
  SELECT *
  FROM root_member
  WHERE therole = 'A'
  AND recommendedcode = '{$check_linkcode->result->recommendedcode}'
  AND account = '{$check_linkcode->result->account}'
  AND status = '1'
SQL;

  $mid_sql_r = runSQLall($mid_sql);

  if (empty($mid_sql_r[0])) {
    $error['code'] = '302';
    $error['messages'] = '代理帐号查询错误';

    return $error;
  }

  $error['code'] = '1';
  $error['messages'] = '推荐人存在' ;
  $error['agent_id'] = $mid_sql_r[1]->id;
  $error['linkcode'] = $agentcode_input;
  $error['register_type'] = $check_linkcode->result->register_type;


  return $error;
}

// ---------------------------------------------------------
// (1)推薦人(加盟聯營股東)帳號 -- 需要檢查是否存在且合法 end
// ---------------------------------------------------------

// ---------------------------------------------------------
// (2)會員帳號規則檢查
// ---------------------------------------------------------
function memberaccount_check($memberaccount = NULL) {
  global $tr;

  // 不可以為空
  if($memberaccount == NULL ) {
    $error['code'] = '0';
    //帳號沒有資料
    $error['messages'] = '帐号没有资料';
  }else{
    // 會員帳號 - 3~12個字元， a-z 字母開頭、數字, 強制帳號為小寫
    // $memberaccount_input      	= strtolower(filter_var($_POST['memberaccount_input'], FILTER_SANITIZE_STRING));
    $memberaccount_input      	= strtolower(filter_var($memberaccount, FILTER_SANITIZE_STRING));
    $memberaccount_input_re     = '/^[a-z][a-z0-9]{2,12}/i';
    $memberaccount_input_len    = strlen($memberaccount_input);
    preg_match($memberaccount_input_re, $memberaccount_input, $matches);

    /*
     判斷帳號是否合法

     使用 preg_match() 過濾使用者輸入的帳號
     過濾條件為 3~12個字元， a-z 字母開頭、數字, 強制帳號為小寫

     過濾完成後 , 有些值會使結果陣列為空 , 例如: Aa123
     會產生 Notice: Undefined offset: 0 的訊息
     如果結果陣列為空 , 在陣列 0 寫入空值

     使用者有輸入符號的話, 過濾出來的結果會與輸入的值不同
     例如輸入帳號為 : test_999
     過濾出來的結果會為 : test

     帳號合法與否判斷條件:
     結果陣列0是否有值.過濾後的值是否與輸入時相同.20>=字串長度>=3

     */
    if ($matches == null) {
      $matches[0] = '';
    }

    if($matches[0] != '' AND $matches[0] == $memberaccount_input AND $memberaccount_input_len <= 12 AND $memberaccount_input_len >= 3) {
      $error['code'] = '1';
      //帳號合法
      $error['messages'] = $tr['account valid'];

      // 檢查帳號是否存在
      $check_memberaccount_sql = "SELECT account FROM root_member WHERE account = '$memberaccount_input'; ";
      $check_memberaccount_sql_result = runSQLall($check_memberaccount_sql);

      if ($check_memberaccount_sql_result[0] >= 1) {
        $error['code'] = '302';
        $error['messages'] = '会员帐号 '.$memberaccount_input.' 已存在，请重新输入' ;
      } else {
        $error['code'] = '1';
        $error['messages'] = '会员帐号 '.$memberaccount_input.' 可用' ;
        $error['memberaccount_input'] = $memberaccount_input;
      }

    }else{
      $error['code'] = '301';
      $error['messages'] = '帐号需为 3~12 个字元，字母开头、数字' ;
    }
  }

  return $error;
}

// ---------------------------------------------------------
// (2)會員帳號檢查 end
// ---------------------------------------------------------

// ---------------------------------------------------------
// (3) 驗證碼檢查
// ---------------------------------------------------------

function captcha_check($captcha_code = NULL) {
  global $tr;
  global $system_config;

  // 給測試用的預設驗證碼變數
  $captcha_for_test = $system_config['captcha_for_test'];

  // 不可以為空
  if($captcha_code == NULL ) {
    $error['code'] = '0';
    //驗證碼不可以為空。
    $error['messages'] = $tr['verification code empty'];
  }else{

    if(isset($_SESSION['captcha']) AND ($_SESSION['captcha'] == $captcha_code OR ($captcha_for_test != '' AND $captcha_for_test == $captcha_code))) {
      $error['code'] = '1';
      $error['messages'] = 'CAPTCHA验证码正确';
    }else{
      $error['code'] = '300';
      $error['messages'] = 'CAPTCHA验证码'.$captcha_code.'错误';
    }
  }
  return $error;
}

// ---------------------------------------------------------
// (3) 驗證碼檢查 end
// ---------------------------------------------------------

// ---------------------------------------------------------
// (4) 會員密碼檢查, 只檢查是否相同. 複雜度在前端檢查。
// ---------------------------------------------------------

function password_check($password1_input = NULL, $password2_input = NULL) {
  global $tr;

  // 第一次密碼
  $password1_input			     = filter_var($password1_input, FILTER_SANITIZE_STRING);
  // 驗證密碼
  $password2_input			     = filter_var($password2_input, FILTER_SANITIZE_STRING);
  // 會員密碼及提款密碼，不可以為空。
  if($password1_input == '' AND $password2_input == '') {
    // 驗證碼不可以為空
    $error['code'] = '0';
    //會員密碼及提款密碼，不可以為空。
    $error['messages'] = $tr['password empty'];
  }else{
    if($password1_input == $password2_input) {
      $error['code'] = '1';
      $error['messages'] = '前后密码输入不正确';
      $error['password1_input'] = $password1_input;
    }else{
      $error['code'] = '300';
      $error['messages'] = '前后密码输入不正确';
    }
  }
  return $error;
}

// ---------------------------------------------------------
// (4) 會員密碼檢查, 只檢查是否相同. 複雜度在前端檢查。 end
// ---------------------------------------------------------

// ---------------------------------------------------------
// (7) 檢查指紋碼註冊是否有超過限制次數
// ---------------------------------------------------------

function fingerprinter_check() {
  global $member_register;

  // 一般訪客, 瀏覽器指紋允許註冊次數
  $registerfingerprinting_numberoftimes = $member_register['registerfingerprinting_member_numberoftimes'];

  // 如果是代理登入引導註冊, 可允許註冊人數較多. (todo: 分類在會員等級管制 by mtchang)
  if(isset($_SESSION['member']) AND $_SESSION['member']->therole == 'A' ) {
    // 瀏覽器指紋允許註冊次數
    $registerfingerprinting_numberoftimes = $member_register['registerfingerprinting_agent_numberoftimes'];
  }

  // 檢查指紋識別, 如果不是使用瀏覽器或是不支援html5的瀏覽器會沒有指紋碼
  if ($_SESSION['fingertracker'] != 'no_fingerprinting') {

    // 計算這個指紋碼出現的次數
    $check_registerfingerprinting_numberoftimes_sql = "SELECT COUNT (registerfingerprinting) AS registerfingerprinting_numberoftimes FROM root_member WHERE registerfingerprinting = '".$_SESSION['fingertracker']."'; ";
    $check_registerfingerprinting_numberoftimes_sql_result = runSQLall($check_registerfingerprinting_numberoftimes_sql);

    // 如果沒取到指紋碼表示沒出現過, 設為0
    if ($check_registerfingerprinting_numberoftimes_sql_result[0] >= 1) {
      $registerfingerprinting_count = $check_registerfingerprinting_numberoftimes_sql_result[1]->registerfingerprinting_numberoftimes;
    } else {
      $registerfingerprinting_count = 0;
    }

    // 判斷是否超過限制 -- 指紋識別碼
    if ($registerfingerprinting_count >= $registerfingerprinting_numberoftimes) {
      $error['code'] = '300';
      $error['messages'] = '目前注册次数超过限制：指纹识别码已经注册'.$registerfingerprinting_count.'次。系统限制注册次数为指纹识别注册'.$registerfingerprinting_numberoftimes.'次';
    }else{
      $error['code'] = '1';
      $error['messages'] = '目前注册次数在合法范围内：指纹识别码已经注册'.$registerfingerprinting_count.'次。系统限制注册次数为指纹识别注册'.$registerfingerprinting_numberoftimes.'次';
    }
  }else{
    $error['code'] = '0';
    $error['messages'] = '你的浏览器来源不明或是不支援html5的浏览器';
  }

  return $error;
}

// ---------------------------------------------------------
// (7) 檢查指紋碼註冊是否有超過限制次數
// ---------------------------------------------------------

// ---------------------------------------------------------
// (8) 檢查IP註冊是否有超過限制次數
// ---------------------------------------------------------

function ip_countregister_check() {
  global $member_register;

  // 一般訪客, ip允許註冊次數
  $registerip_numberoftimes = $member_register['registerip_member_numberoftimes'];

  // 如果是代理登入引導註冊, 可允許註冊人數較多. (todo: 分類在會員等級管制 by mtchang)
  if(isset($_SESSION['member']) AND $_SESSION['member']->therole == 'A' ) {
    // ip允許註冊次數
    $registerip_numberoftimes = $member_register['registerip_agent_numberoftimes'];
  }

  // 檢查指紋識別, 如果不是使用瀏覽器或是不支援html5的瀏覽器會沒有指紋碼
  if ($_SESSION['fingertracker'] != 'no_fingerprinting') {

    // 計算這個ip出現的次數
    $check_registerip_numberoftimes_sql = "SELECT COUNT (registerip) AS registerip_numberoftimes FROM root_member WHERE registerip = '".$_SESSION['fingertracker_remote_addr']."'; ";
    $check_registerip_numberoftimes_sql_result = runSQLall($check_registerip_numberoftimes_sql);

    if ($check_registerip_numberoftimes_sql_result[0] >= 1) {
      $registerip_count = $check_registerip_numberoftimes_sql_result[1]->registerip_numberoftimes;
    } else {
      $registerip_count = 0;
    }

    // 判斷是否超過限制 -- 來源 IP
    if ($registerip_count >= $registerip_numberoftimes) {
      $error['code'] = '300';
      $error['messages'] = '目前注册次数超过限制：IP出现'.$registerip_count.'次。系统限制注册次数为IP注册'.$registerip_numberoftimes.'次。';
    }else{
      $error['code'] = '1';
      $error['messages'] = '目前注册次数在合法范围内：IP出现'.$registerip_count.'次。系统限制注册次数为IP注册'.$registerip_numberoftimes.'次。';
    }

  }else{
    $error['code'] = '0';
    $error['messages'] = '你的浏览器来源不明或是不支援html5的浏览器';
  }

  return $error;
}
// ---------------------------------------------------------
// (8) 檢查IP註冊是否有超過限制次數 end
// ---------------------------------------------------------

// ---------------------------------------------------------
// (9) 真實姓名檢查 start
// ---------------------------------------------------------
function real_name_check($real_name, $isshow, $ismust) {
  global $tr;

  $error['code'] = '1';
  $error['realname_input'] = '';

  $realname_input	= filter_var($real_name, FILTER_SANITIZE_STRING);
  // 輸入的姓名長度 , 不可以超過 12 個字.
  $realname_strlen = mb_strlen ($realname_input);

  if($realname_input == '' AND $ismust == 'on') {
    $error['code'] = '300';
    //真實姓名，不可以為空。
    $error['messages'] = $tr['real name empty'];
    unset($error['realname_input']);

    return $error;
  }

  if($realname_strlen <= 12) {
    $error['code'] = '1';
    $error['messages'] = '真实姓名合法';
    $error['realname_input'] = $realname_input;
  } else {
    $error['code'] = '301';
    $error['messages'] = '真实姓名长度请勿超过 12 个字。';
  }

  return $error;
}

// ---------------------------------------------------------
// (9) 真實姓名檢查 end
// ---------------------------------------------------------

// ---------------------------------------------------------
// (10) 手機檢查 start
// ---------------------------------------------------------
function mobilenumber_check($mobilenumber, $isshow, $ismust, $isunique) {
  global $tr;

  $error['code'] = '1';
  $error['mobilenumber_input'] = '';

  if ($isshow == 'on') {
    $mobilenumber_input	= filter_var($mobilenumber, FILTER_SANITIZE_STRING);
    $regexp_ret = $mobilenumber_input;
    // 檢查電話格式是否正確
    // $regexp = '^[\-0-9]{7,16}$';
    // $regexp = '^13[0-9]{1}[0-9]{8}|^15[0-9]{1}[0-9]{8}|^18[8-9]{1}[0-9]{8}';
    // $regexp_ret = filter_var($mobilenumber_input, FILTER_VALIDATE_REGEXP, array("options"=>array("regexp"=>"/$regexp/")));

    // if( $regexp_ret === false AND $ismust == 'on') {
    if ($mobilenumber_input == '' AND $ismust == 'on') {
      $error['code'] = '300';
      //不合法的電話號碼 限制為 0 ~ 9 的數字和 - 符號。
      $error['messages'] = $tr['invalid phone number'];
      unset($error['mobilenumber_input']);

      return $error;
    }

    if ($isunique == 'on') {
      $check_mobilenumber_sql = "SELECT mobilenumber FROM root_member WHERE mobilenumber = '$regexp_ret'; ";
      $check_mobilenumber_sql_result = runSQLall($check_mobilenumber_sql);

      if ($check_mobilenumber_sql_result[0] > 1) {
        $error['code'] = '302';
        $error['messages'] = '电话号码 '.$regexp_ret.' 已存在，请重新输入' ;
      } else {
        $error['code'] = '1';
        $error['messages'] = '电话合法'.$regexp_ret;
        $error['mobilenumber_input'] = $regexp_ret;
      }
    } else {
      // 合法的結果字串
      $error['code'] = '1';
      $error['messages'] = '电话合法'.$regexp_ret;
      $error['mobilenumber_input'] = $regexp_ret;
    }
  }

  return $error;
}

// ---------------------------------------------------------
// (10) 手機檢查 end
// ---------------------------------------------------------

// ---------------------------------------------------------
// (11) email檢查 start
// ---------------------------------------------------------

function email_check($email, $isshow, $ismust, $isunique) {
  global $tr;

  $error['code'] = '1';
  $error['email_input'] = '';

  if ($isshow == 'on') {
    $email_input = filter_var($email, FILTER_SANITIZE_STRING);

    if (!filter_var($email_input, FILTER_VALIDATE_EMAIL) AND $email_input !== '') {
      $error['code'] = '300';
      $error['messages'] = $tr['email invalid'];
      unset($error['email_input']);

      return $error;
    }

    if ($isunique == 'on') {
      $check_email_sql = "SELECT email FROM root_member WHERE email = '$email_input'; ";
      $check_email_sql_result = runSQLall($check_email_sql);

      if ($check_email_sql_result[0] > 1) {
        $error['code'] = '302';
        $error['messages'] = '电子信箱 '.$email_input.' 已存在，请重新输入' ;
      } else {
        $error['code'] = '1';
        $error['messages'] = $tr['email valid'];
        $error['email_input'] = $email_input;
      }

    } else {
      $error['code'] = '1';
      // Email合法
      $error['messages'] = $tr['email valid'];
      $error['email_input'] = $email_input;
    }
  }

  return $error;
}

// ---------------------------------------------------------
// (11) email檢查 end
// ---------------------------------------------------------

// ---------------------------------------------------------
// (12) 性別檢查 start
// ---------------------------------------------------------

function sex_check($sex, $isshow, $ismust) {
  $error['code'] = '1';
  $error['sex_input'] = '2';

  if ($isshow == 'on') {
    $sex_input = filter_var($sex, FILTER_SANITIZE_NUMBER_INT);

    if (($sex_input == '' OR $sex_input == '2') AND $ismust == 'on') {
      $error['code'] = '300';
      $error['messages'] = '性别栏位不可为空，请选择性别。';
      unset($error['sex_input']);
    } else {
      $error['code'] = '1';
      //性別合法
      $error['messages'] = '性别合法';
      $error['sex_input'] = $sex_input;
    }
  }

  return $error;
}

// ---------------------------------------------------------
// (12) 性別檢查 end
// ---------------------------------------------------------

// ---------------------------------------------------------
// (13) 微信檢查 start
// ---------------------------------------------------------
function wechat_check($wechat, $isshow, $ismust, $isunique) {
  $error['code'] = '1';
  $error['wechat_input'] = '';

  if ($isshow == 'on') {
    $wechat_input = filter_var($wechat, FILTER_SANITIZE_STRING);

    if ($wechat_input == '' AND $ismust == 'on') {
      $error['code'] = '300';
      $error['messages'] = 'wechat不合法，请确认你的 wechat 是正确的。';
      unset($error['wechat_input']);

      return $error;
    }

    if ($isunique == 'on') {
      // 檢查wechat是否重複
      $check_wechat_sql = "SELECT wechat FROM root_member WHERE wechat = '$wechat_input'; ";
      $check_wechat_sql_result = runSQLall($check_wechat_sql);

      if ($check_wechat_sql_result[0] > 1) {
        $error['code'] = '302';
        $error['messages'] = 'wechat 帐号 '.$wechat_input.' 已存在，请重新输入' ;
        unset($error['wechat_input']);
      } else {
        $error['code'] = '1';
        $error['messages'] = 'wechat合法';
        $error['wechat_input'] = $wechat_input;
      }

    } else {
      $error['code'] = '1';
      $error['messages'] = 'wechat合法';
      $error['wechat_input'] = $wechat_input;
    }
  }

  return $error;
}

// ---------------------------------------------------------
// (13) 微信檢查 end
// ---------------------------------------------------------

// ---------------------------------------------------------
// (14) QQ檢查 start
// ---------------------------------------------------------
function qq_check($qq, $isshow, $ismust, $isunique) {

  $error['code'] = '1';
  $error['qq_input'] = '';

  if ($isshow == 'on') {
    // $regexp = '[0-9]{5,9}';
    // $regexp_ret = filter_var($qq, FILTER_VALIDATE_REGEXP, array("options"=>array("regexp"=>"/$regexp/")));
    $qq_input = filter_var($qq, FILTER_SANITIZE_STRING);
    $regexp_ret = $qq_input;
    // if ($regexp_ret == false AND $ismust == 'on') {
    if ($qq_input == '' AND $ismust == 'on') {
      $error['code'] = '300';
      $error['messages'] = 'qq不合法，请确认你的 qq 是正确的。';
      unset($error['qq_input']);

      return $error;
    }

    if ($isunique == 'on') {
      // 檢查qq是否重複
      $check_qq_sql = "SELECT qq FROM root_member WHERE qq = '$regexp_ret'; ";
      $check_qq_sql_result = runSQLall($check_qq_sql);

      if ($check_qq_sql_result[0] > 1) {
        $error['code'] = '302';
        $error['messages'] = 'qq 帐号 '.$regexp_ret.' 已存在，请重新输入';
        unset($error['qq_input']);
      } else {
        $error['code'] = '1';
        $error['messages'] = 'qq合法';
        $error['qq_input'] = $regexp_ret;
      }

    } else {
      $error['code'] = '1';
      $error['messages'] = 'qq合法';
      $error['qq_input'] = $regexp_ret;
    }
  }

  return $error;
}

// ---------------------------------------------------------
// (14) QQ檢查 end
// ---------------------------------------------------------

// ---------------------------------------------------------
// (15) 生日檢查 start
// ---------------------------------------------------------

function birthday_check($birthday, $isshow, $ismust) {

  $error['code'] = '1';
  $error['birthday_input'] = '';

  if ($isshow == 'on') {
    $birthday_input = filter_var($birthday, FILTER_SANITIZE_STRING);

    if ($birthday_input == '' AND $ismust == 'on') {
      $error['code'] = '300';
      $error['messages'] = '生日栏位不可为空，请选择生日。';
      unset($error['birthday_input']);

    } else {
      $birthday_input =  (!empty($birthday_input)) ? date("Ymd",strtotime($birthday_input)) : '';
      $error['code'] = '1';
      $error['messages'] = '生日合法';
      $error['birthday_input'] = str_replace('/', '', $birthday_input);
    }
  }

  return $error;
}

// ---------------------------------------------------------
// (15) 生日檢查 end
// ---------------------------------------------------------

function bankdata_check($bankdata, $isshow, $ismust) {

  $bankdata_input = filter_var($bankdata, FILTER_SANITIZE_STRING);

  if ($bankdata_input == '' AND $ismust == 'on' AND $isshow == 'on') {
    $error['code'] = '300';
    $error['messages'] = '帐务资讯不合法，请确认帐务资讯是否正确或皆已填写。';

    return $error;
  }

  $error['code'] = '1';
  $error['messages'] = '帐务资讯合法';
  $error['bankdata_input'] = $bankdata_input;

  return $error;
}

/**
 * 代理推薦碼生成
 *
 * @param [type] $account
 * @return string
 */
function get_recommendedcode($account) {
  $recommendedcode_sha1 = sha1($account);

  return $recommendedcode_sha1;
}

/**
 * 會員登入網址代碼生成
 *
 * @param [type] $account
 * @return string
 */
function get_member_login_urlcode($account) {
  $urlcode_base64 = base64_encode($account);

  return $urlcode_base64;
}

function get_register_setting($type)
{
  global $protalsetting;

  $setting['M'] = [
    'isopen' => $protalsetting['member_register_switch'],
    'setting' => [
      'name' => [
        'isshow'=>$protalsetting['member_register_name_show'],
        'ismust'=>$protalsetting['member_register_name_must']
      ],
      'mobile' => [
        'isshow'=>$protalsetting['member_register_mobile_show'],
        'ismust'=>$protalsetting['member_register_mobile_must'],
        'isunique' => $protalsetting['member_register_mobile_unique']
      ],
      'mail' => [
        'isshow'=>$protalsetting['member_register_mail_show'],
        'ismust'=>$protalsetting['member_register_mail_must'],
        'isunique' => $protalsetting['member_register_mail_unique']
      ],
      'birthday' => [
        'isshow'=>$protalsetting['member_register_birthday_show'],
        'ismust'=>$protalsetting['member_register_birthday_must']
      ],
      'sex' => [
        'isshow'=>$protalsetting['member_register_sex_show'],
        'ismust'=>$protalsetting['member_register_sex_must']
      ],
      'wechat' => [
        'isshow'=>$protalsetting['member_register_wechat_show'],
        'ismust'=>$protalsetting['member_register_wechat_must'],
        'isunique' => $protalsetting['member_register_wechat_unique']
      ],
      'qq' => [
        'isshow'=>$protalsetting['member_register_qq_show'],
        'ismust'=>$protalsetting['member_register_qq_must'],
        'isunique' => $protalsetting['member_register_qq_unique']
      ]
    ]
  ];

  $setting['A'] = [
    'isopen' => $protalsetting['agent_register_switch'],
    'setting' => [
      'name' => [
        'isshow'=>$protalsetting['agent_register_name_show'],
        'ismust'=>$protalsetting['agent_register_name_must']
      ],
      'mobile' => [
        'isshow'=>$protalsetting['agent_register_mobile_show'],
        'ismust'=>$protalsetting['agent_register_mobile_must'],
        'isunique' => $protalsetting['agent_register_mobile_unique']
      ],
      'mail' => [
        'isshow'=>$protalsetting['agent_register_mail_show'],
        'ismust'=>$protalsetting['agent_register_mail_must'],
        'isunique' => $protalsetting['agent_register_mail_unique']
      ],
      'birthday' => [
        'isshow'=>$protalsetting['agent_register_birthday_show'],
        'ismust'=>$protalsetting['agent_register_birthday_must']
      ],
      'sex' => [
        'isshow'=>$protalsetting['agent_register_sex_show'],
        'ismust'=>$protalsetting['agent_register_sex_must']
      ],
      'wechat' => [
        'isshow'=>$protalsetting['agent_register_wechat_show'],
        'ismust'=>$protalsetting['agent_register_wechat_must'],
        'isunique' => $protalsetting['agent_register_wechat_unique']
      ],
      'qq' => [
        'isshow'=>$protalsetting['agent_register_qq_show'],
        'ismust'=>$protalsetting['agent_register_qq_must'],
        'isunique' => $protalsetting['agent_register_qq_unique']
      ],
      'bankdata' => [
        'isshow'=>$protalsetting['agent_bank_information_show'],
        'ismust'=>$protalsetting['agent_bank_information_must']
      ]
    ]
  ];

  return $setting[$type];
}

?>
