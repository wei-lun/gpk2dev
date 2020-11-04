<?php
// ----------------------------------------------------------------------------
// Features:    前台 - site_api專用用函式, 將處理代幣得函式集中統一處理.
// File Name:    site_api/lib_api.php
// Author:        Dright
// Related:   寫在函式說明 , 前台及後台各有哪些程式對應他
// Log:
// ----------------------------------------------------------------------------

// 主機及資料庫設定
require_once __DIR__ . '/../config.php';

// gcash lib 現金轉帳函式庫
require_once dirname(__FILE__) . "/../gcash_lib.php";
// gtoken lib 代幣轉帳函式庫
require_once dirname(__FILE__) . "/../gtoken_lib.php";
// mqtt 通知 lib
require_once __DIR__ . '/../lib_message.php';

/**
 * send a new deposit message to mqtt server
 * only send when deposis success
 */
function notify_new_income() {
  $message = new Message;
  $message
    ->setTitle('新的存款通知')
    ->setMessage('您有一笔新的收入，请查照')
    ->setUrl('/depositing_siteapi_audit.php')
    ->setDelay(5000); //顯示1sec後dismiss 不設則不會消失

  // get channel
  $channel = get_message_channel(
    'backstage', // platform = [front|backstage]
    'test' // channel
  );

// send message
  mqtt_send(
    $channel,
    $message
  );
}

/**
 * [respond_json description]
 * @param  array   $json_data     [description]
 * @param  integer $response_code [description]
 * @return [type]                 [description]
 */
function respond_json(array $json_data, $response_code = 200) {
  http_response_code($response_code);
  header('Content-Type: application/json');
  echo json_encode($json_data);
}

/**
 * [generate_validator description]
 * @param  array  $rules [description]
 * @return [type]        [description]
 */
function generate_validator(array $rules) {

  return function (array $request_data) use ($rules) {
    foreach ($rules as $attribute => $error_message) {
      if (!isset($request_data[$attribute]) || empty($request_data[$attribute])) {
        respond_json(['message' => $error_message], 406);

        return false;
      }
    }

    return true;
  };

}

/**
 * [generate_sign description]
 * @param  array  $params [description]
 * @param  [type] $key    [description]
 * @return [type]         [description]
 */
function generate_sign($params = [], $key) {
  unset($params['sign']);
  ksort($params);

  $sign_text = $key . http_build_query($params) . $key;

  return strtoupper(sha1($sign_text));
}

/**
 * [check_sign description]
 * @param  [type] $api_data [description]
 * @param  [type] $key      [description]
 * @return [type]           [description]
 */
function check_sign($api_data, $key) {
  return generate_sign($api_data, $key) == $api_data['sign'];
}

/**
 * [get_api_account_key description]
 * @param  string $api_account [description]
 * @param  boolean $debug [description]
 * @return [type]              [description]
 */
function get_api_account_key(string $api_account, $debug = 0) {
  // key for test
  if ($debug) {
    return '431ED18A910C41A75DF4DAEA010F8999E189A5B2';
  }

  // get site_api_account
  $site_api_account_sql = "SELECT * FROM root_site_api_account WHERE api_account = '" . $api_account . "'";
  $site_api_account_result = runSQLall($site_api_account_sql);

  if ($site_api_account_result[0] == 0) {
    return '';
  }

  return $site_api_account_result[1]->api_key;
}

/**
 * [is_service_availible description]
 * @param  string  $api_account [description]
 * @param  string  $service     [description]
 * @return boolean              [description]
 */
function is_service_availible(string $api_account, string $service) {
  $site_api_account_sql = "SELECT * FROM root_site_api_account WHERE api_account = :a";
  $site_api_account = runSQLall_prepared($site_api_account_sql, ['a' => $api_account], '', '', 'r')[0] ?? null;

  if (is_null($site_api_account)) {
    return false;
  }

  $available_services = json_decode($site_api_account->available_services, true);
  return in_array($service, $available_services);
}

/**
 * [create_gcash_order gcash_order訂單寫入 table]
 * @return [type] [description]
 */
function create_gcash_order($gcash_order_data) {

  // 操作人員的 web http remote ip
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

  // 執行的程式檔名 - client
  if (isset($_SERVER['SCRIPT_NAME'])) {
    $script_name = filter_var($_SERVER['SCRIPT_NAME'], FILTER_SANITIZE_MAGIC_QUOTES);
  } else {
    $script_name = 'no_script_name';
  }

  // 瀏覽器資訊 - client
  if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $http_user_agent = filter_var($_SERVER['HTTP_USER_AGENT'], FILTER_SANITIZE_MAGIC_QUOTES);
  } else {
    $http_user_agent = 'no_http_user_agent';
  }

  // 使用者的 cookie 資訊
  if (isset($_SERVER['HTTP_COOKIE'])) {
    $http_cookie = filter_var($_SERVER['HTTP_COOKIE'], FILTER_SANITIZE_MAGIC_QUOTES);
  } else {
    $http_cookie = 'no_cookie';
  }

  // 使用 $_GET 的傳入網址
  if (isset($_SERVER['QUERY_STRING'])) {
    $query_string = filter_var($_SERVER['QUERY_STRING'], FILTER_SANITIZE_MAGIC_QUOTES);
  } else {
    $query_string = 'no_query_string';
  }

  $insert_sql = "
  INSERT INTO root_gcash_order(
    transaction_id,
    custom_transaction_id,
    site_account_name,
    account,
    amount,
    transaction_time,
    title,
    notes,
    status,
    notify_tried,
    return_url,
    notify_url,
    agent_ip,
    fingerprinting_id,
    script_name,
    http_user_agent,
    http_cookie,
    query_string
  ) VALUES(
    '$gcash_order_data->transaction_id',
    '$gcash_order_data->customer_transaction_id',
    '$gcash_order_data->site_account_name',
    '$gcash_order_data->account',
    '$gcash_order_data->amount',
    now(),
    '$gcash_order_data->title',
    '$gcash_order_data->notes',
    1,
    0,
    '$gcash_order_data->return_url',
    '$gcash_order_data->notify_url',
    '$agent_ip',
    '$fingertracker',
    '$script_name',
    '$http_user_agent',
    '$http_cookie',
    '$query_string'
  )";

  // var_dump($insert_sql);
  $insert_sql_result = runSQLall($insert_sql);
  if ($insert_sql_result[0] == 1) {
    $r = $insert_sql_result;
    return true;
  } else {
    $r = false;
    return false;
  }
  return false;
}

/**
 * [apicashwithdrawal description]
 * @param  [type] $account [description]
 * @param  [type] $amount  [description]
 * @param  [type] $transaction_id  [description]
 * @param  [type] $withdrawal_password  [description]
 * @return [type]          [description]
 */
function apicashwithdrawal($account, $amount, $transaction_id, $withdrawal_password) {
  //  交易的類別分類：應用在所有的錢包相關程式內 , 定義再 system_config.php 內.
  global $transaction_category;
  // 出納  -- 加盟金
  global $gcash_cashier_account;
  // 轉帳摘要 -- api现金提款
  $transaction_category_index = 'apicashwithdrawal';
  // 交易類別 , 定義再 system_config.php 內, 不可以隨意變更.
  $summary = $transaction_category[$transaction_category_index];
  // 轉帳來源帳號 -- 出納
  $source_transferaccount = $account;
  // 轉帳目標帳號
  $destination_transferaccount = $gcash_cashier_account;
  // 來源帳號提款密碼 or 管理員登入的密碼
  $withdrawal_password = $withdrawal_password;
  // 轉帳金額
  $transaction_money = $amount;
  // 實際存提
  $realcash = 1;
  // 系統轉帳文字資訊(補充)
  $system_note = 'api现金取款, gcash_order單號(transaction_id)' . $transaction_id;
  // $debug = 1 --> 進入除錯模式 , debug = 0 --> 關閉除錯
  $debug = 0;

  // 取得轉帳來源帳號
  $source_acc_sql = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.account = '" . $source_transferaccount . "' AND root_member.status = '1';";
  $source_acc_result = runSQLall($source_acc_sql);
  if ($debug == 1) {
    echo '取得轉帳來源帳號';
    var_dump($source_acc_result);
  }
  // 操作者 ID 預設為來源帳號 -- 出納
  $member_id = $source_acc_result[1]->id;

  // 轉帳目標帳號
  $destination_acc_sql = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.account = '" . $destination_transferaccount . "' AND root_member.status = '1';";
  $destination_acc_result = runSQLall($destination_acc_sql);
  if ($debug == 1) {
    echo '轉帳目標帳號';
    var_dump($destination_acc_result);
  }

  $error = member_gcash_transfer(
    $transaction_category_index,
    $summary,
    $member_id,
    $source_transferaccount,
    $destination_transferaccount,
    $withdrawal_password,
    $transaction_money,
    $realcash,
    $system_note,
    $debug
  );

  if ($debug == 1) {
    echo '執行轉帳';
    var_dump($error);
  }

  return ($error);
}

/**
 * [apideposit description]
 * @param  [type] $account [description]
 * @param  [type] $amount  [description]
 * @param  [type] $api_account  [description]
 * @return [type]          [description]
 */
function apideposit($account, $amount, $api_account, $transaction_order_id, $curreny_type = 'gcash', $debug = 0) {
  // $debug = 1 --> 進入除錯模式 , debug = 0 --> 關閉除錯

  // 加入轉帳到 token 的判斷
  // 轉帳目標帳號
  $destination_transferaccount = $account;
  // 轉帳目標帳號資訊
  $destination_acc_sql = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.account = '" . $destination_transferaccount . "' AND root_member.status = '1';";
  $destination_acc_result = runSQLall($destination_acc_sql);
  if ($debug == 1) {
    echo '轉帳目標帳號';
    var_dump($destination_acc_result);
  }
  // 目前 api 入款，都入到 cash；錢包有是否 lock 的狀態
  // $is_destination_acc_locked = ! is_null($destination_acc_result[1]->gtoken_lock);
  $is_currency_gtoken = $curreny_type == 'gtoken'; // && ! $is_destination_acc_locked;

  //  交易的類別分類：應用在所有的錢包相關程式內 , 定義再 system_config.php 內.
  global $transaction_category;
  // 出納  -- 現金出納帳號、代幣出納帳號
  global $gcash_cashier_account;
  global $gtoken_cashier_account;
  // 轉帳摘要 -- 現金為电子支付存款，代幣為代幣存款
  $transaction_category_index = $is_currency_gtoken ? 'apitokendeposit' : 'apicashdeposit';
  // 交易類別 , 定義再 system_config.php 內, 不可以隨意變更.
  $summary = $transaction_category[$transaction_category_index];
  // 轉帳來源帳號 -- 出納
  $source_transferaccount = $is_currency_gtoken ? $gtoken_cashier_account : $gcash_cashier_account;
  // 取得轉帳來源帳號資訊
  $source_acc_sql = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.account = '" . $source_transferaccount . "' AND root_member.status = '1';";
  $source_acc_result = runSQLall($source_acc_sql);
  if ($debug == 1) {
    echo '取得轉帳來源帳號';
    var_dump($source_acc_result);
  }
  // 來源帳號提款密碼 or 管理員登入的密碼
  $withdrawal_password = 'tran5566';
  // 轉帳金額
  $transaction_money = $amount;
  // 實際存提
  $realcash = 1;
  // 系統轉帳文字資訊(補充)
  $system_note = '線上' . $api_account . 'api 存款單號' . $transaction_order_id . $is_currency_gtoken ? '，存款到遊戲幣' : '，存款到現金';
  // 操作者 ID 預設為來源帳號 -- 出納
  $member_id = $source_acc_result[1]->id;

  if ($is_currency_gtoken):
    // 如果代幣錢包沒有被鎖定, 才可以進行現金轉代幣...
    // 提款手續費
    $fee_transaction_money = 0;
    // 提款行政手續費(稽核不過的費用)
    $administrative_amount = 0;
    // 稽核方式
    $auditmode_select = 'depositaudit';
    // 目標帳號所屬等級的稽核值
    $deposit_rate = runSQLall_prepared("SELECT * FROM root_member_grade WHERE id = :grade_id", ['grade_id' => $destination_acc_result[1]->grade])[0]->deposit_rate;
    // 稽核金額
    $auditmode_amount = $amount * $deposit_rate * 0.01;

    // member_gtoken_transfer 作轉帳，要先算好稽核值
    // member_gtoken_transfer($member_id, $source_transferaccount, $destination_transferaccount, $transaction_money, $password_verify_sha1,
    //  $summary, $transaction_category, $realcash, $auditmode_select, $auditmode_amount, $system_note_input=NULL, $debug=0)
    $error = member_gtoken_transfer($member_id, $source_transferaccount, $destination_transferaccount, $transaction_money, $withdrawal_password,
      $summary, $transaction_category_index, $realcash, $auditmode_select, $auditmode_amount, $system_note, $debug);

    // 娛樂城自動轉沒有此支付方式的稽核等相關設定
    // require_once __DIR__ . '/../../notusing/lobby_casino_lib.php';
    // $error = auto_gcash2gtoken($member_id, $destination_acc_result[1]->account, $amount, $password_verify_sha1='5566bypass', $debug=0, $system_note_input='電子支付，預設轉代幣');
  else:
    $error = member_gcash_transfer(
      $transaction_category_index,
      $summary,
      $member_id,
      $source_transferaccount,
      $destination_transferaccount,
      $withdrawal_password,
      $transaction_money,
      $realcash,
      $system_note,
      $debug
    );
  endif;
  // $error = '沒有對應的幣別轉帳函式，請聯絡客服人員';

  if ($debug == 1) {
    echo '執行轉帳';
    var_dump($error);
  }

  return ($error);
}

/**
 * [create_api_deposit_record description]
 * @param  [type] $api_data [description]
 * @param  [type] $deposit_status  [description]
 * @return [type] $record [description]
 */
function create_api_deposit_record($api_data, $deposit_status, $debug = 0) {
  $now = new DateTime('now');
  $now->setTimezone(new DateTimeZone("Asia/Taipei"));
  $api_data_json = json_encode($api_data);
  $request_time = $now->format('Y-m-d H:i:s.u+08');

  // 操作人員的 web http remote ip
  $agent_ip = $_SERVER["REMOTE_ADDR"] ?? 'no_remote_addr';
  // 執行的程式檔名 - client
  $script_name = filter_var($_SERVER['SCRIPT_NAME'], FILTER_SANITIZE_MAGIC_QUOTES) ?? 'no_script_name';
  // 使用 $_GET 的傳入網址
  $query_string = filter_var($_SERVER['QUERY_STRING'], FILTER_SANITIZE_MAGIC_QUOTES) ?? 'no_query_string';

  $add_api_log_sql = <<<SQL
    INSERT INTO root_site_api_deposit (
      custom_transaction_id,
      account,
      amount,
      currency_type,
      request_time,
      transactioninfo_json,
      title,
      notes,
      status,
      agent_ip,
      script_name,
      -- query_string,
      site_account_name
    ) VALUES (
      '$api_data->transaction_order_id',
      '$api_data->account',
      $api_data->amount,
      '$api_data->service',
      '$request_time',
      '$api_data_json',
      '$api_data->description',
      '',
      $deposit_status,
      '$agent_ip',
      '$script_name',
      -- '$query_string',
      '$api_data->api_account'
    )

    RETURNING id, custom_transaction_id, status, transaction_time, site_account_name
SQL;

  if ($debug) {
    echo $add_api_log_sql;
  }

  $result = runSQLall_prepared($add_api_log_sql);
  if ($debug) {
    var_dump($result);
  }

  return $result;
}

/**
 * [read_api_deposit_record description]
 * @param  [type] $transaction_order_id [description]
 * @return [type] $record [description]
 */
function read_api_deposit_record($transaction_order_id, $debug = 0) {
  $read_sql = <<<SQL

WITH "order_id_records" AS (
  SELECT * FROM "root_site_api_deposit" WHERE custom_transaction_id = :custom_transaction_id ORDER BY transaction_time DESC
)
SELECT * FROM order_id_records LIMIT 1;

SQL;

  $record = runSQLall_prepared($read_sql, ['custom_transaction_id' => $transaction_order_id])[0] ?? null;

  if ($debug) {
    echo $read_sql, "\n";
    var_dump($record);
  }
  return $record;
}

/**
 * 入款訂單類別
 */
class ApiDepositOrder {
  // sql 流水號
  public $id;
  // 請求方定義的入款單號
  public $custom_transaction_id;
  // 入款對象的帳號
  public $account;
  // 入款金額
  public $amount;
  // 入款到平台的貨幣類別；gcash | gtoken
  public $currency_type;
  // api 入款接到請求的時間
  public $request_time;
  // api 入款紀錄新增的時間
  public $transaction_time;
  // api 入款請求變數 log
  public $transactioninfo_json;
  // 入款請求描述
  public $title;
  // 欄位保留
  public $notes;
  // 入款狀態(0=尚未入款 1=入款成功 2=入款失敗[站台出納餘額不足] 3=入款失敗[API帳戶當日額度不足])
  public $status;
  // 透過 api 入款的來源 ip
  public $agent_ip;
  // 執行的程式名稱
  public $script_name;
  // 對應 root_site_api_account
  public $site_account_name;

  public function __construct($apiData = null) {
    if (!is_null($apiData)) {
      $this->init($apiData);
    }

  }

  private function init($apiData) {
    $this->custom_transaction_id = $apiData->transaction_order_id;
    $this->account = $apiData->account;
    $this->amount = $apiData->amount;
    $this->currency_type = $apiData->service;
    $this->request_time = $this->requestTime();
    $this->transaction_time = $this->requestTime();
    $this->transactioninfo_json = json_encode($apiData);
    $this->title = $apiData->description;
    $this->status = 0;
    $this->agent_ip = $this->getRemoteIP();
    $this->script_name = filter_var($_SERVER['SCRIPT_NAME'], FILTER_SANITIZE_MAGIC_QUOTES) ?? 'no_script_name';
    $this->site_account_name = $apiData->api_account;
  }

  /**
   * 將此訂單資料新增到 table: root_site_api_deposit 中
   * @return void
   */
  public function add() {
    $columns = $values = [];
    foreach ($this as $key => $value) {
      if ($key == 'id') {
        continue;
      }

      $columns[] = $key;
      $values[":$key"] = $value;
    }

    $sql = "INSERT INTO root_site_api_deposit (" . implode(',', $columns) . ")
      VALUES ('" . implode("','", $values) . "') RETURNING id;";

    $this->id = runSQLall($sql)[1]->id;
  }

  /**
   * 更新 table: root_site_api_deposit 中此訂單資料
   * @return void
   */
  public function update() {
    $pairs = [];
    foreach ($this as $key => $value) {
      $pairs[] = "$key='$value'";
    }

    $sql = "UPDATE root_site_api_deposit SET " . implode(',', $pairs) . " WHERE id = " . $this->id . ";";

    runSQLall($sql);
  }

  /**
   * 取得 table: root_site_api_deposit 中的特定訂單
   * @param string $transactionId
   * @return object
   */
  public static function read($transactionId) {
    $sql = "SELECT * FROM root_site_api_deposit WHERE custom_transaction_id = :transaction_id LIMIT 1";
    return runSQLall_prepared($sql, ['transaction_id' => $transactionId], 'ApiDepositOrder', 0, 'r')[0] ?? null;
  }

  private function depositToWallet() {
    # preserve for update $this->status
    # there is not api_limited_quota/cashier_quota msg
    # transfer function has to return error_code and error_msg
  }

  /**
   * 取得現在時間
   * @return string
   */
  public function requestTime() {
    $now = new DateTime('now');
    $now->setTimezone(new DateTimeZone("Asia/Taipei"));
    $request_time = $now->format('Y-m-d H:i:s.u+08');

    return $request_time;
  }

  /**
   * 發送請求的客戶端之真實 ip
   * @return string
   */
  /* private */ function getRemoteIP() {
    $headers = $_SERVER;
    //Get the forwarded IP if it exists
    if (
      array_key_exists('X-Forwarded-For', $headers)
      && filter_var($headers['X-Forwarded-For'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
    ) {
      $the_ip = $headers['X-Forwarded-For'];
    } elseif (
      array_key_exists('HTTP_X_FORWARDED_FOR', $headers)
      && filter_var($headers['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
    ) {
      $the_ip = $headers['HTTP_X_FORWARDED_FOR'];
    } else {
      $the_ip = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
    }

    return $the_ip;
  }

  /**
   * 判斷這筆訂單是否已經入款完成
   * @return boolean
   */
  public function isCompleted() {
    return $this->status == 1;
  }

  /**
   * 判斷這筆訂單的入款金額是否會超過該 api account 單次限額
   * @return boolean
   */
  public function isBeyondPerQuotas() {
    $apiAccountConfig = $this->getSiteApiAccount();
    return $apiAccountConfig->per_transaction_limit != 0 and $this->amount > $apiAccountConfig->per_transaction_limit;
  }

  /**
   * 判斷這筆訂單的入款金額是否會超過該 api account 單日限額
   * @return boolean
   */
  public function isBeyondDailyQuotas() {
    $apiAccountConfig = $this->getSiteApiAccount();

    $currentDate = explode(' ', $this->transaction_time)[0];

    $dateAccumulatedAmount = self::getAccumulatedAmountByDate($currentDate, $currentDate);

    return $apiAccountConfig->daily_transaction_limit != 0
    && $this->amount + $dateAccumulatedAmount->accumulated_amount > $apiAccountConfig->daily_transaction_limit;
  }

  /**
   * 判斷這筆訂單的入款金額是否會超過該 api account 單月限額
   * @return boolean
   */
  public function isBeyondMonthlyQuotas() {
    $apiAccountConfig = $this->getSiteApiAccount();

    $currentDate = explode(' ', $this->transaction_time)[0];
    $sdate = substr_replace($currentDate, "01", -2);
    $edate = date('Y-m-d', strtotime("$sdate +1 month -1 days"));

    $monthAccumulatedAmount = self::getAccumulatedAmountByDate($sdate, $edate);

    return $apiAccountConfig->monthly_transaction_limit != 0
    and $this->amount + $monthAccumulatedAmount->accumulated_amount > $apiAccountConfig->monthly_transaction_limit;
  }

  /**
   * 判斷這筆訂單的入款請求來源 IP 是否在 api account 的白名單內
   * @return boolean
   */
  public function isOutIpWhiteList() {
    $apiAccountConfig = $this->getSiteApiAccount();
    $ipWhiteList = json_decode($apiAccountConfig->ip_white_list, true);

    return !empty($ipWhiteList) and !in_array(explode(',',$_SERVER['HTTP_X_FORWARDED_FOR'])['0'], $ipWhiteList);
  }

  /**
   * 取得對應訂單的 site_api_account 資訊
   * @return object 對應此筆交易訂單的 site_api_account config
   */
  public function getSiteApiAccount() {
    $sql = "SELECT * FROM root_site_api_account WHERE api_account = :account";
    return runSQLall_prepared($sql, ['account' => $this->site_account_name], '', '', 'r')[0] ?? null;
  }

  /**
   * 取得特定日期的交易訂單
   */
  public static function getOrderByDate($sdate = '1970-01-01', $edate = '1970-01-01') {
    $sql = "SELECT * FROM root_site_api_deposit WHERE transaction_time BETWEEN :sdate AND date ':edate' + interval '1 day'";
    return runSQLall_prepared($sql, ['sdate' => $sdate, 'edate' => $edate], '', '', 'r')[0] ?? null;
  }

  /**
   * 取得特定日期的成功交易總額
   */
  public static function getAccumulatedAmountByDate($sdate = '1970-01-01', $edate = '1970-01-01') {
    $sql = "SELECT count(*) AS count, SUM(amount) AS accumulated_amount FROM root_site_api_deposit WHERE status = 1 AND transaction_time BETWEEN :sdate AND date(:edate) + interval '1 day'";
    return runSQLall_prepared($sql, ['sdate' => $sdate, 'edate' => $edate], '', '', 'r')[0] ?? null;
  }
}

/**
 * 入款訂單設定相關
 * 前台的這個 class 與後台目的不同，主要目的為取得可用的 api 帳戶
 * (by logged member or else, usually read)
 */
class SiteApiConfig {
  public $id;
  public $account_name;
  public $api_account;
  public $api_key;
  public $status;
  public $available_services;
  public $available_member_grade;
  public $ip_white_list;
  public $change_time;
  public $per_transaction_limit;
  public $daily_transaction_limit;
  public $monthly_transaction_limit;
  public $fee_rate;
  public $notes;
  public $transaction_timeout;

  public function __construct() {
  }

  public function init() {
  }

  /**
   * 取得特定 id 與 api_key 的設定值
   *
   * @param array $Arr 包含 id 或 apikey 的陣列
   *
   * @return object
   */
  public static function read(array $Arr = ['id' => 0, 'api_key' => '', 'api_account' => '']) {
    $where = implode(" AND ", array_map(function ($value, $key) {
      return "$key = :$key";
    }, $Arr, array_keys($Arr)));

    $sql = "SELECT * FROM root_site_api_account WHERE " . $where;
    $result = runSQLall_prepared($sql, $Arr, __CLASS__, 0, 'r')[0] ?? null;

    if (!is_null($result)) {
      $result->available_services = json_decode($result->available_services);
      $result->ip_white_list = json_decode($result->ip_white_list);
    }

    return $result;
  }

  /**
   * 取得特定會員可用的 api_account (return 1)
   *
   * @param array  $filter 會員的 id 或 account
   * @param string $type 取得的 api 種類，預設為入款用
   *
   * @return object
   */
  public static function readByMember(array $filter = [], string $type = 'deposit') {
    if (isset($filter['id']) and !empty($filter['id'])) {
      $sql = "SELECT grade FROM root_member WHERE id = :id";
      $grade = runSQLall_prepared($sql, $filter, '', 0, 'r')[0]->grade ?? null;
    } elseif (isset($filter['account']) and !empty($filter['account'])) {
      $sql = "SELECT grade FROM root_member WHERE account = :account";
      $grade = runSQLall_prepared($sql, $filter, '', 0, 'r')[0]->grade ?? null;
    } elseif (isset($filter['grade']) and !empty($filter['grade'])) {
      $grade = $filter['grade'];
    } else {
      $sql = '';
    }
    $api_config = is_null($grade) ? null : self::readAvailableByGrade($grade, $type)[0] ?? null;

    return $api_config;
  }

  /**
   * 取得特定會員等級可用的 api_account
   *
   * @param int $grade 會員等級 id
   *
   * @return array
   */
  public static function readAvailableByGrade(string $grade, $type) {
    // $sql = "SELECT * FROM root_site_api_account WHERE status = '0' AND available_member_grade LIKE :grade AND transaction_category = :type ORDER BY available_member_grade DESC";
    // status: 0 是開啟, 1 關閉, 2 維護
    // 除了關閉以外的，需要帶到金流服務，讓金流服務決定說要顯示哪幾個通道
    $sql = "SELECT * FROM root_site_api_account WHERE status != '1' AND available_member_grade SIMILAR TO '($grade|$grade,%|%,$grade,%|%,$grade)' AND transaction_category = :type ORDER BY available_member_grade DESC";
    $api_configs = runSQLall_prepared($sql, compact('type'), '', __CLASS__, 0, 'r');

    return $api_configs;
  }
}
