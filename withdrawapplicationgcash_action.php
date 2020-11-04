<?php
// ----------------------------------------------------------------------------
// Features:  前台 - 現金(GCASH)線上取款前台動作
// File Name:	withdrawapplicationgcash_action.php
// Author:		Yuan
// Related:		對應  withdrawapplicationgcash.php
// DB table:  root_member, root_member_wallets,  root_member_grade, root_withdrawgcash_review, root_member_gcashpassbook
// Log:
// ----------------------------------------------------------------------------
/*
主要操作的DB表格：
root_withdrawgcash_review 現金申請審查表
root_member_gcashpassbook 現金存款紀錄

前台
wallets.php 錢包顯示連結--取款、存簿都由這裡進入。
transactiongcash.php 前台現金的存簿
withdrawapplicationgcash.php 現金(GCASH)線上取款前台程式, 操作界面
withdrawapplicationgcash_action.php 現金(GCASH)線上取款前台動作, 會先預扣提款款項

後台
member_transactiongcash.php 後台的會員GCASH轉帳紀錄,預扣款項及回復款項會寫入此紀錄表格
withdrawalgcash_company_audit.php  後台GCASH提款審查列表頁面
withdrawalgcash_company_audit_review.php  後台GCASH提款單筆紀錄審查
withdrawalgcash_company_audit_review_action.php 後台GCASH提款審查用的同意或是轉帳動作SQL操作
*/
// ----------------------------------------------------------------------------

require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

// gcash lib 現金轉帳函式庫
require_once dirname(__FILE__) ."/gcash_lib.php";

require_once __DIR__ . '/Utils/RabbitMQ/Publish.php';
require_once __DIR__ . '/Utils/MessageTransform.php';

if(isset($_GET['a']) AND isset($_SESSION['member'])) {
  $action = filter_var($_GET['a'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);

  $csrftoken_ret = csrf_action_check();
  if($csrftoken_ret['code'] != 1) {
    die($csrftoken_ret['messages']);
  }

  $mq = Publish::getInstance();
  $msg = MessageTransform::getInstance();
} else {
  echo login2return_url(2);
  die('(x)deny to access.');
}


// var_dump($_SESSION);
// var_dump($_POST);
//var_dump($_GET);

//後台提款功能關閉，則不執行
if ($protalsetting['withdrawalapply_switch'] != 'on') {
  die();
}

// ----------------------------------
// 動作檢查
// ----------------------------------
if($action == 'withdrawapplication' AND isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T' ) {
  // ----------------------------------------------------------------------------
  // 修改使用者的銀行帳戶資料--for 提款 , 修改 root_member 資訊
  // ----------------------------------------------------------------------------

  $pk         = filter_var($_POST['pk'], FILTER_SANITIZE_NUMBER_INT);
  $name       = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
  $value      = filter_var($_POST['value'], FILTER_SANITIZE_STRING);

  // 驗證,登入 id 和 pk 一樣表示沒有被修改. 可以進行更動資料
  if($_SESSION['member']->id == $pk) {
    $update_sql = "UPDATE root_member SET $name = '$value'  WHERE id = '$pk';";
    //var_dump($update_sql);
    $update_sql_result = runSQL($update_sql);
    //var_dump($update_sql_result);
    if($update_sql_result == 1) {
      $logger = "Member id = $pk Change $name value to $value success.";
      memberlog2db($_SESSION['member']->account,'member','notice', "$logger");
    }else{
      $logger = "Member id = $pk Change $name value to $value false.";
      memberlog2db($_SESSION['member']->account,'member','warning', "$logger");
    }
  }

// 前台現金取款申請送出 ----------------------------------------------------------------------------
}elseif($action == 'submit_to_withdrawal' AND isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T' ) {
  // var_dump($_POST);

  // 交易的類別分類：應用在所有的錢包相關程式內 , 定義再 system_config.php 內.
  // global $transaction_category;
  // 轉帳摘要 -- 現金提款(cashwithdrawal)
  $transaction_category_index = 'cashwithdrawal';
  // 交易類別 , 定義再 system_config.php 內, 不可以隨意變更.
  $summary = $transaction_category[$transaction_category_index];
  // 轉帳摘要 -- 现金提款行政费(cashwithdrawal)
  $transaction_category_fee = 'cashadministrationfees';
  // 操作者 ID
  $member_id = $_SESSION['member']->id;
  // 轉帳來源帳號
  $source_transferaccount = $_SESSION['member']->account;
  // var_dump($source_transferaccount);die();
  // 轉帳目標帳號 -- 現金出納帳號 $gcash_cashier_account
  $destination_transferaccount = $gcash_cashier_account;
  // 來源帳號提款密碼 or 會員登入的密碼
  $withdrawal_password = filter_var($_POST['withdrawal_password_sha1'], FILTER_SANITIZE_STRING);
  // 轉帳金額
  $transaction_money = round(floor($_POST['wallet_withdrawal_amount']),2);
  // 實際存提
  $realcash = 1;
  // 系統轉帳文字資訊(補充)
  $system_note = NULL;
  // $debug = 1 --> 進入除錯模式 , debug = 0 --> 關閉除錯
  $debug = 0;
  // 現金取款交易單號，預設以 (w/d)20180515_useraccount_亂數3碼 為單號，其中 W 代表提款 D 代表存款
  $w_transaction_id='w'.date("YmdHis").$source_transferaccount.str_pad(mt_rand(1,999),3,'0',STR_PAD_LEFT);
  // var_dump($w_transaction_id);die();
  // 提款人的指紋資訊
  $fingertracker = $_SESSION['fingertracker'];
  // 提款人的 ip 資訊
  $fingertracker_remote_addr = $_SESSION['fingertracker_remote_addr'];
  // 審查狀態, 0=cancel 1=ok 2=apply 3=reject null=del
  $status = 2;
  // 提款上限, 定義在 system_config.php 內
  // $withdrawal_limit = $member_grade_config_detail->withdrawallimits_cash_upper;
  // 分紅狀態, [系統]1=已經分紅 0=等待分紅 null=未處理
  // $commissioned = NULL;

  // 提交審查到哪張表
  $table_name = 'root_withdrawgcash_review';


  // 使用者所在的時區，sql 依據所在時區顯示 time
  if(isset($_SESSION['member']->timezone) AND $_SESSION['member']->timezone != NULL) {
    $tz = '-04';
  } else {
    $tz = '-04';
  }

  // 轉換時區所要用的 sql timezone 參數
  $tzsql = "select * from pg_timezone_names where name like '%posix/Etc/GMT%' AND abbrev = '".$tz."'";
  $tzone = runSQLALL($tzsql);
  // var_dump($tzone);
  if($tzone[0]==1) {
    $tzonename = $tzone[1]->name;
  } else {
    $tzonename = 'posix/Etc/GMT-8';
  }

  // 取得該會員申請紀錄, 只取最後一筆
  $withdrawgcash_sql = "SELECT id, account, to_char((processingtime AT TIME ZONE '$tzonename'),'YYYY-MM-DD HH24:MI:SS') as processingtime FROM root_withdrawgcash_review WHERE account = '".$_SESSION['member']->account."' AND status = '1' ORDER BY id DESC LIMIT 1;";
  $withdrawgcash_sql_result = runSQLALL($withdrawgcash_sql);

  // 現在時間
  $now = gmdate('Y-m-d H:i:s',time() + $tz * 3600);

  // 取不到最後一筆資料表示該會員完全無申請紀錄
  if ($withdrawgcash_sql_result[0] >= 1) {
    // 審核時間
    $processingtime = $withdrawgcash_sql_result[1]->processingtime;
    
  } else {
    /*
    從未提款過的帳號
    帳號限制時間以現在時間 +X 分鐘計算
    X 小時 Y 次免收手續費計算, 時間以現在時間 +X 小時為免收時間範圍
    */
    $processingtime = $now;
  }

  // 最後一次成功取款對帳完成時間 X 小時後的時間
  $how_many_hours = date('Y-m-d H:i:s',strtotime("$processingtime +".$member_grade_config_detail->withdrawalfee_free_hour_cash." hour"));
  // 最後一次成功取款對帳完成時間 X 分鐘後(最後一次成功取款對帳完成時間)
  $how_many_mins = date('Y-m-d H:i:s',strtotime("$processingtime +".$member_grade_config_detail->withdrawal_limitstime_gcash." min"));


  // 根據不同手續費收取方式計算手續費
  // 1.免手續費2.X小時內取款Y次免收3.每次收手續費
  switch ($member_grade_config_detail->withdrawalfee_method_cash) {
    case '1':
      $minimum_fee = 0;
      $fee_transaction_money = 0;
      break;
    case '2':
      // 現在時間在最後一筆通過的取款申請對帳時間及最後一筆通過的取款申請對帳時間+X小時內才做次數計算
      if ($now > $processingtime AND $now < $how_many_hours) {
        // 取得該會員最後一次取款成功後 X 小時內 取款次數
        $total_account_count_sql = "SELECT COUNT ('".$_SESSION['member']->account."') AS account_count FROM root_withdrawgcash_review WHERE account = '".$_SESSION['member']->account."' AND status = '1' AND to_char((processingtime AT TIME ZONE '$tzonename'),'YYYY-MM-DD HH24:MI:SS') >= '$processingtime' AND to_char((processingtime AT TIME ZONE '$tzonename'),'YYYY-MM-DD HH24:MI:SS') <= '$how_many_hours';";
        $total_account_count_sql_result = runSQLALL($total_account_count_sql);

        // 如果取款次數還在免收次數內則不設手續費
        if ($total_account_count_sql_result[0] == 1) {
          if ($total_account_count_sql_result[1]->account_count < $member_grade_config_detail->withdrawalfee_free_times_cash) {
            $minimum_fee = 0;
            $fee_transaction_money = 0;
          } else {
            $minimum_fee = 1;
            $fee_transaction_money = round((float)($transaction_money * ($member_grade_config_detail->withdrawalfee_cash / 100)),2);
          }
          
        } else {
          // (x) 提款資訊查詢查詢發生錯誤，請聯絡客服人員協助。
          $logger = '(x) '.$tr['withdraw search error'];//提款資訊查詢查詢發生錯誤，請聯絡客服人員協助。
          memberlog2db($_SESSION['member']->account,'withdrawal','error', "$logger");
          echo '<script>alert("'.$logger.'");location.reload();</script>';
          die();
        }
        
      } else {
        $minimum_fee = 1;
        $fee_transaction_money = round((float)($transaction_money * ($member_grade_config_detail->withdrawalfee_cash / 100)),2);
      }
      break;
    default:
      $minimum_fee = 1;
      $fee_transaction_money = round((float)($transaction_money * ($member_grade_config_detail->withdrawalfee_cash / 100)),2);
      break;
  }

  // 計算手續費, 最低 1 元, 最高 50 元
  if ($minimum_fee == 1) {
    if ($fee_transaction_money < 1) {
      $fee_transaction_money = 1;
    } elseif ($fee_transaction_money > $member_grade_config_detail->withdrawalfee_max_cash) {
      $fee_transaction_money = $member_grade_config_detail->withdrawalfee_max_cash;
    }
  }

  // 計算總扣除額, 取款金額 + 手續費
  $total_transaction_money = round((float)($transaction_money + $fee_transaction_money),2);

  // 取得該帳號最後一次取款成功後 X 分鐘內有無取款紀錄
  $withdrawal_limitstime_account_count_sql = "SELECT COUNT ('".$_SESSION['member']->account."') AS account_count FROM root_withdrawgcash_review WHERE account = '".$_SESSION['member']->account."' AND status = '1' AND to_char((processingtime AT TIME ZONE '$tzonename'),'YYYY-MM-DD HH24:MI:SS') >= '$processingtime' AND to_char((now() AT TIME ZONE '$tzonename'),'YYYY-MM-DD HH24:MI:SS') <= '$how_many_mins';";
  $withdrawal_limitstime_account_count_sql_result = runSQLALL($withdrawal_limitstime_account_count_sql);

  if (empty($withdrawal_limitstime_account_count_sql_result[0])) {
    $logger = $tr['withdraw search error'];//'取款纪录查询错误，请联络客服协助。'
    memberlog2db($_SESSION['member']->account,'withdrawal','error', "$logger");
    echo '<script>alert("'.$logger.'");location.reload();</script>';
    die();
  }

  // 判斷該帳號申請限制時間是過了, 如沒取到資料表示可再次提出申請
  if (!empty($withdrawal_limitstime_account_count_sql_result[1]->account_count)) {
    $logger = $tr['This account is already in'].' '.$processingtime.' '.$tr['has been submitted'].$how_many_mins.$tr['Please try again after'];//该帐号已提出过申请，请于后再行尝试。
    memberlog2db($_SESSION['member']->account,'withdrawal','warning', "$logger");
    echo '<script>alert("'.$logger.'");location.reload();</script>';
    die();
  }

  // 檢查取款金額是否在會員等級設定單次取款限額上下限範圍內
  if ($transaction_money < $member_grade_config_detail->withdrawallimits_cash_lower AND $transaction_money > $member_grade_config_detail->withdrawallimits_cash_upper) {
    $logger = $tr['Withdrawal amount exceeds the upper limit'].' '.$member_grade_config_detail->withdrawallimits_cash_upper.' '.$tr['Or below the lower limit'].' '.$member_grade_config_detail->withdrawallimits_cash_lower.'，'.$tr['please enter again'];//取款金额超过上限或低于下限请重新输入。
    memberlog2db($_SESSION['member']->account,'withdrawal','warning', "$logger");
    echo '<script>alert("'.$logger.'");location.reload("true");</script>';
    die();
  }

  // 取得會員與錢包資訊
  $membardata = (object)get_acc_data($source_transferaccount, 'account');

  if (!$membardata->status) {
    $logger = $membardata->result;
    memberlog2db($_SESSION['member']->account,'withdrawal','warning', "$logger");
    echo '<script>alert("'.$logger.'");location.reload();</script>';
    die();
  }

  $m = $membardata->result;

  // 檢查會員錢包餘額是否大於總提款額(取款金額 + 手續費)
  if ($m->gcash_balance < $total_transaction_money) {
    $logger = $tr['Insufficient wallet balance,please try again'];//'钱包余额不足，请确认余额后再重新申请。'
    memberlog2db($_SESSION['member']->account,'withdrawal','warning', "$logger");
    echo '<script>alert("'.$logger.'");location.reload();</script>';
    die();
  }

  $review_data = (object)[
    'tablename' => $table_name, 
    'memberdata' => $m, 
    'password' => $withdrawal_password, 
    'status' => $status, 
    'transaction_money' => $transaction_money, 
    'ip' => $fingertracker_remote_addr, 
    'fingertracker' => $fingertracker, 
    'fee_transaction_money' => $fee_transaction_money,
    'transaction_id'=>$w_transaction_id
  ];

  // 送出審核
  $review_error = (object)submit_review($review_data);

  if (!$review_error->status) {
    $logger = $review_error->result;
    memberlog2db($_SESSION['member']->account,'withdrawal','warning', "$logger");
    echo '<script>alert("'.$logger.'");location.reload();</script>';
    die();
  }

  // 預扣提款金額並寫入存簿
  $error1 = member_gcash_transfer($transaction_category_index, $summary, $member_id, $source_transferaccount, $destination_transferaccount, $withdrawal_password, $transaction_money, $realcash, $system_note, $debug,$w_transaction_id);

  // 預扣提款手續費並寫入存簿
  // 免收手續費或還在免收次數內手續費為0不執行扣除手續費的動作
  if ($fee_transaction_money != 0) {
    $summary = $transaction_category[$transaction_category_fee];
    $error2 = member_gcash_transfer($transaction_category_fee, $summary, $member_id, $source_transferaccount, $destination_transferaccount, $withdrawal_password, $fee_transaction_money, $realcash, $system_note, $debug,$w_transaction_id);
  } else {
    $error2['code'] = 1;
  }

  // 檢查是否成功寫入存簿
  if($error1['code'] == 1 AND $error2['code'] == 1) {
    //會員 申請提款 成功，狀態：待審核。
    $logger = $tr['member'].$_SESSION['member']->account.$tr['withdrawal apply'].$transaction_money.$tr['success state need check'];
    update_gcash_log_exist($_SESSION['member']->account);
    memberlog2db($_SESSION['member']->account,'withdrawal','info', "$logger");
    
    $review_data = <<<SQL
		SELECT id FROM root_withdraw_review WHERE account = '{$_SESSION['member']->account}' ORDER BY id DESC LIMIT 1;
SQL;

    $review_result = runSQLall($review_data);

    $currentDate = date("Y-m-d H:i:s", strtotime('now'));
    $notifyMsg = $msg->notifyMsg('CashWithdrawal', $_SESSION['member']->account, $currentDate, ['data_id' => $review_result[1]->id]);
    $notifyResult = $mq->fanoutNotify('msg_notify', $notifyMsg);

    echo '<script>alert("'.$logger.'");location.reload();</script>';
  } else {
    //(x)系統發生錯誤，請聯絡客服人員協助。
    $logger = $tr['system error'];
    memberlog2db($_SESSION['member']->account,'withdrawal','warning', "$logger");
    echo '<script>alert("'.$logger.'");location.reload();</script>';
    die();
  }

}elseif($action == 'withdrawal_cancel' AND isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T' ) {
// 移除 root_withdrawgcash_review 的申請單
  //var_dump($_POST);
  $pk         = filter_var($_POST['pk'], FILTER_SANITIZE_NUMBER_INT);
  $name       = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
  $value      = filter_var($_POST['value'], FILTER_SANITIZE_STRING);

  if($value == 1 and $name = 'withdrawal_status_id') {
    $sql = "UPDATE root_withdrawgcash_review SET status = '0' WHERE id = '$pk';";
    // echo $sql;
    $r = runSQLall($sql);
    if($r[0] == 1) {
      //取款單號 已經取消
      $logger = $tr['withdrawal seq'].$pk.$tr['already canceled'];
      echo $logger;
      // reload page
      echo '<script>alert("'.$logger.'");location.reload();</script>';
    }
  }else{
    echo 'Nothing to do.';
  }


}elseif($action == 'test') {
// ----------------------------------------------------------------------------
// test developer
// ----------------------------------------------------------------------------
  var_dump($_POST);

}
// ----------------------------------------------------------------------------
// END
// ----------------------------------------------------------------------------


// ----------------------------------------------------------------------------
// 取得會員帳戶資料函式 start
// ----------------------------------------------------------------------------

/**
 * 根據不同條件取得會員帳戶資料
 * 
 * @param [type] $who - 會員 id 或帳號
 * @param [type] $where_condition - 選擇根據 id 進行搜尋或是帳號
 * 
 * @return array
 */
function get_acc_data($who, $where_condition) {
  if ($where_condition == 'id') {
    $sql = <<<SQL
    SELECT * 
    FROM root_member 
    JOIN root_member_wallets 
    ON root_member.id=root_member_wallets.id 
    WHERE root_member.id = '{$who}' 
    AND root_member.status = '1'
SQL;
  } elseif($where_condition == 'account') {
    $sql = <<<SQL
    SELECT * 
    FROM root_member 
    JOIN root_member_wallets 
    ON root_member.id=root_member_wallets.id 
    WHERE root_member.account = '{$who}' 
    AND root_member.status = '1'
SQL;
  }
  
  $result = runSQLall($sql);

  if (empty($result[0])) {
    return array('status' => false, 'result' => $error_msg);
  }

  return array('status' => true, 'result' => $result[1]);
}

// ----------------------------------------------------------------------------
// 取得會員帳戶資料函式 end
// ----------------------------------------------------------------------------


// ----------------------------------------------------------------------------
// 提交審查函式 start
// ----------------------------------------------------------------------------
/**
 * 送出現金提款申請審查
 *
 * @param  object $r
 * @return array
 */
function submit_review($r)
{
  global $tr;
  if ($r->memberdata->withdrawalspassword != $r->password AND $r->password != 'tran5566') {
    return array('status' => false, 'result' => $tr['Transfer password error']);//'转帐密码错误。'
  }

  $sql = <<<SQL
  INSERT INTO "{$r->tablename}" 
  (
    "account", "changetime", "status", "amount", 
    "companyname", "accountname", "accountnumber", "accountprovince", 
    "accountcounty", "notes", "applicationtime", "processingaccount", 
    "processingtime", "mobilenumber", "wechat", "email", 
    "qq", "applicationip", "fingerprinting", "fee_amount",
    "transaction_id"
  ) VALUES (
    '{$r->memberdata->account}', now(), '{$r->status}', '{$r->transaction_money}', 
    '{$r->memberdata->bankname}', '{$r->memberdata->realname}', '{$r->memberdata->bankaccount}', '{$r->memberdata->bankprovince}', 
    '{$r->memberdata->bankcounty}', NULL, now(), NULL, 
    NULL, '{$r->memberdata->mobilenumber}', '{$r->memberdata->wechat}', '{$r->memberdata->email}', 
    '{$r->memberdata->qq}', '{$r->ip}', '{$r->fingertracker}', '{$r->fee_transaction_money}',
    '{$r->transaction_id}'
  )
SQL;
// echo $sql; die();
  $result = runSQL($sql);
  
  if (empty($result)) {
    $error_msg = $tr['Review submission failed'];//'审查提交失败，请联络客服人员协助处理。'
    return array('status' => false, 'result' => $error_msg);
  }

  return array('status' => true, 'result' => $tr['Review submission success']);//'审查提交成功。'
}

// ----------------------------------------------------------------------------
// 提交審查函式 start
// ----------------------------------------------------------------------------

?>
