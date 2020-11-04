<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 線上存款功能 -- B 線上支付 -- 動作處理
// File Name:	deposit_online_pay_action.php
// Author:		Barkley
// Related:   deposit_company.php
// Log:
// 2017.7.24 改寫
// ----------------------------------------------------------------------------



// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";




if(isset($_GET['a'])) {
  $action = $_GET['a'];
}else{
  die('(x)不合法的測試');
}
//var_dump($_SESSION);
// var_dump($_POST);
//var_dump($_GET);

// 取得 microtime
function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}


// ----------------------------------------------------------------------------
// 入款訂單寫入 table ,  並且同時計算此筆入款的金流成本寫入欄位
// onlinepayment_insert_db($Account,$Amt,$MerchantOrderNo,$merchantname,$MerchantID,$cashfeerate, $IP, $fingerprinting)
// 帳號, 金額, 訂單, 金流代號, 商店, 手續費, IP , 指紋, 金流商ID
// ----------------------------------------------------------------------------
function onlinepayment_insert_db($Account,$Amt,$MerchantOrderNo,$merchantname,$MerchantID,$cashfeerate, $IP, $fingerprinting, $onlinepaymentid){

  // 手續費: 入款金額 * 手續費%
  $cashfee_amount = ($Amt * $cashfeerate)/100 ;

  $insert_sql = "INSERT INTO root_deposit_onlinepay_summons
  (account, amount,transfertime, merchantorderid, onlinepay_company, transactioninfo, cashfee_amount, ip, fingerprinting, onlinepaymentid)
  VALUES
  ('$Account', '$Amt', now(),'$MerchantOrderNo','$merchantname','$MerchantID', '$cashfee_amount', '$IP', '$fingerprinting', '$onlinepaymentid')
  ";
  // var_dump($insert_sql);
  $insert_sql_result = runSQLall($insert_sql);
  if($insert_sql_result[0] == 1) {
    $r = $insert_sql_result;
  }else{
    $r = false;
  }
  return $insert_sql_result;
}
// ----------------------------------------------------------------------------
// 會員等級全域變數 -- in system_config
// var_dump($member_grade_config); --> 這個沒有弄好, 需要修正. 2017.8.3



if($action == 'goto_onlinepayment' AND isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T' ) {

// ----------------------------------------------------------------------------
// 前往指定的金流
// ----------------------------------------------------------------------------
  //var_dump($_POST);
  //var_dump($_SESSION);

  // 全域變數 -- in system_config 站台設定
  // var_dump($protalsetting);
  if($protalsetting['companydeposit_switch'] == 'on') {
    //echo '全站金流系統正常..';
  }else{
    $logger = '全站金流系統維護中!';
    die($logger);
  }


  // ----------------------------------------------------------------------------
  // 取得並過濾存款金額
  if(isset($_POST['deposit_amount'])) {
    $online_payment_amount = filter_var($_POST['deposit_amount'], FILTER_VALIDATE_INT);
  }else{
    $online_payment_amount = fasle;
  }

  // 取得並過濾訂單編號
  if(isset($_POST['onlinepaymentid'])) {
    $online_payment_id     = filter_var($_POST['onlinepaymentid'], FILTER_VALIDATE_INT);
  }else{
    $online_payment_id = false;
  }

  // 如果有資料格式正確的的話 , 不正確就離開
  if($online_payment_amount === false || $online_payment_id === false){
    $logger =  '<div class="alert alert-warning" role="alert">(x)線上支付商家選擇有誤，請重新選取。</div>';
    die();
  }
  // ----------------------------------------------------------------------------


  //確認金額是否在指定的「會員等級」範圍內
  $onlinepaymemt_limit_sql = "SELECT * FROM root_member_grade WHERE id = '".$_SESSION['member']->grade."';";
  //var_dump($onlinepaymemt_limit_sql);
  $onlinepaymemt_limit_sql_result = runSQLall($onlinepaymemt_limit_sql);

  if($onlinepaymemt_limit_sql_result[0] == 1 ){
    $root_member_grade = $onlinepaymemt_limit_sql_result[1];
    //var_dump($root_member_grade);
    // 這個變數要改成系統變數
  }else{
    $logger = '取得會員等級設定錯誤, 請聯絡客服人員處理。';
    die($logger);
  }
  // -----------------------------------------------------



  // 確認支付商id是否存在以及該會員的等級是否可使用, 並檢查該支付商的單筆限額及最大限額
  // SELECT * FROM root_deposit_onlinepayment WHERE status = 1 AND (grade  ->> '$grade' >= '1')
  $onlinepayment_search_sql = "SELECT * FROM root_deposit_onlinepayment WHERE id ='".$online_payment_id."' AND (grade  ->> '".$root_member_grade->gradename."' >= '1');";
  //var_dump($onlinepayment_search_sql);
  $onlinepayment_search_sql_result = runSQLall($onlinepayment_search_sql);
  // 金流商的設定資訊
  //var_dump($onlinepayment_search_sql_result);

  if($onlinepayment_search_sql_result[0] == 1) {
    // 資料庫讀取資料正確,且剛好只有一筆資料
    // 判斷指定的金流設定檔,是否被關閉
    if($onlinepayment_search_sql_result[1]->status == 1 ) {
      // 金流設定檔可以使用
      echo '前往'.$onlinepayment_search_sql_result[1]->payname.'線上支付服務商';
      // --> --> -->
    }else{
      // 金流設定檔被關閉
      $logger = '線上支付服務商'.$onlinepayment_search_sql_result[1]->payname.'，服務維護中。';
      die($logger);
    }
  }else{
    $logger = '「支付商」選擇錯誤，請聯絡客服人員處理。';
    die($logger);
  }
  // ---------------------------------------------------------------------------


  // ---------------------------------------------------------------------------
  // 金流訂單開始
  // ---------------------------------------------------------------------------
    //檢查是哪一個第三方支付商 ex spgateway
    $merchantname = explode('_',$onlinepayment_search_sql_result[1]->name)[0];
    // 金流商對應的 ID, 要寫入訂單內.
    $onlinepaymentid = $onlinepayment_search_sql_result[1]->id;
    //訂單號碼與時間記錄使用的time參數
    $time_stamp = time();
    //var_dump($time_stamp);
    // 訂單使用, 小數點四位拉出來到整數.
    $microtime_stamp = round(microtime_float(),4) * 10000 ;
    //var_dump($microtime_stamp);


  // ---------------------------------------------------------------------------
  // 依據參數前往指定的金流支付商
  // ---------------------------------------------------------------------------
    // switch case 選擇支付商。
    $merchants_supported = ['spgateway', 'pingpp', 'teegon', 'tianxia', 'xinma', 'ips'];
    if( in_array($merchantname, $merchants_supported) ) {
      // 線上支付需要請使用者補上 Email 資訊. 可以讓使用者登入後修改.
      $Email = $_SESSION['member']->email;
      // 商戶代號
      $MerchantID = $onlinepayment_search_sql_result[1]->merchantid;
      // Key -- 各家金流商會有所不同
      $HashKey = $onlinepayment_search_sql_result[1]->hashkey;
      // IV -- 各家金流商會有所不同
      $HashIV = $onlinepayment_search_sql_result[1]->hashiv;
      // 入款訂單號碼 , 站台代碼_time()
      //$MerchantOrderNo = '1501003764';
      $MerchantOrderNo = $config['projectid'].'at'.$microtime_stamp;
      // 目前的 UnixTime
      // $TimeStamp = '1501003764';
      $TimeStamp = $time_stamp;
      // 金額
      $Amt = $online_payment_amount;
      //$Amt = rand(1,30);
      // 將傳過去的資料用 salt 編碼, 避免被盜用

      // 寫入此筆存款訂單, 到本地端的資料庫 root_deposit_onlinepay_summons
      // var_dump($onlinepayment_search_sql_result[1]);
      // var_dump($_SESSION['member']);
      // var_dump($Amt);

      //如果訂單寫入資料庫成功後，就引導到註冊金流的頁面。

      $insert_order_result = onlinepayment_insert_db(
        $_SESSION['member']->account,
        $Amt,
        $MerchantOrderNo,
        $merchantname,
        $MerchantID,
        $onlinepayment_search_sql_result[1]->cashfeerate,
        $_SESSION['fingertracker_remote_addr'],
        $_SESSION['fingertracker'],
        $onlinepaymentid
      );

      if($insert_order_result != false){

        require_once __DIR__ . '/pay/main.php';

        $paymentGateway = PaymentGatewayFactory::getPaymentGateway($merchantname, $onlinepayment_search_sql_result[1]);

        $payment_data = [
          'MerchantOrderNo' => $MerchantOrderNo,
          'TimeStamp' => $TimeStamp,
          'Amt' => $Amt,
        ];

        $create_payment_result = $paymentGateway->createPayment($payment_data);

        // var_dump($create_payment_result);

        if(!empty($create_payment_result['transaction_id'])) {
          // update transaction_id
          $msql = "UPDATE root_deposit_onlinepay_summons SET transaction_id = '" . $create_payment_result['transaction_id'] ."'
        	WHERE merchantorderid = '" . $MerchantOrderNo . "' AND transactioninfo = '" . $MerchantID . "' AND amount = '" . $Amt . "';";
        	//print_r($msql);
        	$msql_result = runSQLall($msql);
        }

        echo $create_payment_result['payment_form'];
        //測試用信用卡卡號 4000-2211-1111-1111
      }else{
        // 寫入訂單失敗.
        $logger = '金流商['.$onlinepaymentid.']'.$merchantname.',存款金額'.$Amt.'訂單編號'.$MerchantOrderNo.'寫入失敗';
        die($logger);
      }

    }else{
      $logger = '沒有指定配合的線上支付商, 請聯絡客服人員處理。';
      die($logger);
    }


// END 金流呼叫

// -----------------------------------------------------------------------------
}elseif($action == 'test' AND isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T' ) {
// -----------------------------------------------------------------------------
// TEST use
// -----------------------------------------------------------------------------
  var_dump($_POST);

  require_once __DIR__ . '/pay/main.php';

  // $merchantname = 'tianxia';
  //
  // $paymentGateway = PaymentGatewayFactory::getPaymentGateway($merchantname, new stdclass);
  // $microtime_stamp = round(microtime_float(),4) * 10000;
  //
  // $payment_data = [
  //   'MerchantOrderNo' => $config['projectid'] . 'at' . $microtime_stamp,
  //   // 'TimeStamp' => $TimeStamp,
  //   'Amt' => '999',
  // ];
  //
  // $create_payment_result = $paymentGateway->createPayment($payment_data);
  //
  // var_dump($create_payment_result);
  //
  // echo $create_payment_result['url'];

  // echo $create_payment_result['payment_form'];
}

// -----------------------------------------------------------------------------

?>
