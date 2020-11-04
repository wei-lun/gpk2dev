<?php
// ----------------------------------------------------------------------------
// Features:  前台 - 代币(GTOKEN)线上取款前台動作
// File Name:	withdrawapplication_action.php
// Author:		Barkley
// Related:   對應  withdrawapplication.php
// DB table:  root_withdraw_review , root_member_gtokenpassbook
// Log:
/*
操作的表格：root_withdraw_review
前台
withdrawapplication.php 代币(GTOKEN)线上取款前台程式
withdrawapplication_action.php 代币(GTOKEN)线上取款前台動作, 會寫入 root_member_gtokenpassbook , transactiongtoken.php 可以觀看
後台
withdrawalgtoken_company_audit.php  後台審查列表頁面
withdrawalgtoken_company_audit_review.php  後台單筆紀錄審查
withdrawapgtoken_company_audit_review_action.php 審查用的同意或是轉帳動作SQL操作
*/
// ----------------------------------------------------------------------------





require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

// gtoken lib 代幣轉帳函式庫
require_once dirname(__FILE__) ."/gtoken_lib.php";
// 即時稽核 lib
require_once dirname(__FILE__) ."/token_auditorial_lib.php";

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


// ----------------------------------
// 確保這個 script 執行不會因為 user abort 而中斷!!
// Ignore user aborts and allow the script to run forever
ignore_user_abort(true);
// disable php time limit , 60*60 = 3600sec = 1hr
set_time_limit(300);
// ----------------------------------


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


    $sql = "SELECT $name FROM root_member WHERE id = '$pk';";
    $sql_result = runSQLALL($sql);

    if ($sql_result[0] == 1) {
      if ($sql_result[1]->$name == '') {
        // 驗證,登入 id 和 pk 一樣表示沒有被修改. 可以進行更動資料
        if($_SESSION['member']->id == $pk) {
          $update_sql = "UPDATE root_member SET $name = '$value'  WHERE id = '$pk';";
          //var_dump($update_sql);
          $update_sql_result = runSQL($update_sql);
          //var_dump($update_sql_result);
          if($update_sql_result == 1) {
            $logger = "Member id = $pk Change $name value to $value success.";
            memberlog2db($_SESSION['member']->account,'member','notice', "$logger");
            echo '<script>location.reload();</script>';
          }else{
            $logger = "Member id = $pk Change $name value to $value false.";
            memberlog2db($_SESSION['member']->account,'member','warning', "$logger");
            // echo '<script>location.reload();</script>';
            echo '<script>alert("'.$logger.'");window.location.reload();</script>';
          }
        }

      } else {
        $logger = $name.$tr['Existing data error'];//'已有資料，如需修改請聯絡客服人員。'
        echo '<script>alert("'.$logger.'");location.reload();</script>';
      }

    } else {
      $logger = $name.$tr['member search error'];//'会员资料查询错误。'
      echo '<script>alert("'.$logger.'");location.reload();</script>';
    }


// 前台遊戲幣取款，類別是 代幣轉現金(tokengcash)----------------------------------------------------------------------------
}elseif($action == 'submit_to_withdrawal' AND isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T' ) {
// ----------------------------------------------------------------------------
  // var_dump($_POST);

  // 更新即時稽核相關資訊
  $auditorial_data = get_auditorial_data($_SESSION['member']);

  // 交易的類別分類：應用在所有的錢包相關程式內 , 定義再 system_config.php 內.
  // global $transaction_category;
  // 轉帳摘要 -- 代幣轉銀行(tokengcash)
  // 2018/11/23 $transaction_category_index = 'tokengcash';
  // 交易類別 , 定義再 system_config.php 內, 不可以隨意變更.
  // 2018/11/23 $summary = $transaction_category[$transaction_category_index];

  // 轉帳摘要 -- 代幣提款行政手續費(tokenadministrationfees)
  $fee_transaction_category_index = 'tokenadministrationfees';
  // 交易類別 , 定義再 system_config.php 內, 不可以隨意變更.
  $fee_summary = $transaction_category[$fee_transaction_category_index];

  // 轉帳摘要 -- 代幣優惠扣除(tokenfavorable)
  $offer_deduction_transaction_category_index = 'tokenfavorable';
  // 交易類別 , 定義再 system_config.php 內, 不可以隨意變更.
  $offer_deduction_summary = $transaction_category[$offer_deduction_transaction_category_index];

  // 操作者 ID
  $member_id = $_SESSION['member']->id;
  // 轉帳來源帳號
  $source_transferaccount = $_SESSION['member']->account;
  // 轉帳目標帳號 -- 代幣出納帳號 $gtoken_cashier_account
  $destination_transferaccount = $gtoken_cashier_account;
  // 來源帳號提款密碼 or 管理員登入的密碼
  $password_verify_sha1 = filter_var($_POST['withdrawal_password_sha1'], FILTER_SANITIZE_STRING);
  // 轉帳金額
  $transaction_money = round(floor($_POST['wallet_withdrawal_amount']),2);
  // 提款行政手續費(稽核不過的費用)
  $administrative_amount = $auditorial_data['total_withdrawal_fee'];
  // 優惠扣除
  $offer_deduction = $auditorial_data['total_offer_deduction_amount'];
  // 實際存提
  $realcash = 1;
  // 稽核方式
  $auditmode_select = 'freeaudit';
  // 稽核金額
  $auditmode_amount = '0';
  // 系統轉帳文字資訊(補充)
  $system_note = NULL;
  // $debug = 1 --> 進入除錯模式 , debug = 0 --> 關閉除錯
  $debug = 0;
  // 遊戲幣取款交易單號，預設以 (w/d)20180515_useraccount_亂數3碼 為單號，其中 W 代表提款 D 代表存款
  $w_transaction_id='w'.date("YmdHis").$source_transferaccount.str_pad(mt_rand(1,999),3,'0',STR_PAD_LEFT);
  // 操作人員
  $operator=$_SESSION['member']->account;

  // 轉帳方式
  if (isset($_POST['withdraw_method'])) {
    $withdraw_method = filter_var($_POST['withdraw_method'], FILTER_SANITIZE_NUMBER_INT);

    if ($withdraw_method == '' OR $withdraw_method > 1 OR $withdraw_method < 0) {
      $logger = $tr['Please choose the correct payment method.'];//'请选择正确出款方式。'
      echo '<script>alert("'.$logger.'");location.reload();</script>';
      die();
    }
  } else {
    $logger = $tr['Please choose the correct payment method.'];//'请选择正确出款方式。'
    echo '<script>alert("'.$logger.'");location.reload();</script>';
    die();
  }

  // 交易類別 , 定義遊戲幣轉銀行(提領現鈔或遊戲幣轉現金點數)--yaoyuan
  $withdraw_method_name=['0'=>'tokengcash','1'=>'tokentogcashpoint'];
  $transaction_category_index = $withdraw_method_name[$withdraw_method];
  $summary = $transaction_category[$transaction_category_index];


  // 提款人的指紋資訊
  $fingertracker = $_SESSION['fingertracker'];
  // 提款人的 ip 資訊
  $fingertracker_remote_addr = $_SESSION['fingertracker_remote_addr'];
  // 審查狀態, 0=cancel 1=ok 2=apply 3=reject null=del
  $status = 2;

  // 提交審查到哪張表
  $table_name = 'root_withdraw_review';

  // 取得會員與錢包資訊
  $membardata = (object)get_acc_data($source_transferaccount, 'account');

  if (!$membardata->status) {
    $logger = $membardata->result;
    memberlog2db($_SESSION['member']->account,'withdrawal','warning', "$logger");
    echo '<script>alert("'.$logger.'");location.reload();</script>';
  }

  $m = $membardata->result;

  // 使用者所在的時區，sql 依據所在時區顯示 time
  if(isset($_SESSION['member']->timezone) AND $_SESSION['member']->timezone != NULL) {
    $tz = '-04';
  } else {
    $tz = '-04';
  }

  // 轉換時區所要用的 sql timezone 參數
  $tzsql = "select * from pg_timezone_names where name like '%posix/Etc/GMT%' AND abbrev = '".$tz."'";
  $tzone = runSQLALL($tzsql);

  if($tzone[0]==1) {
    $tzonename = $tzone[1]->name;
  } else {
    $tzonename = 'posix/Etc/GMT-8';
  }

  // 取得該會員申請紀錄, 只取最後一筆
  $withdrawg_sql = "SELECT id, account, to_char((processingtime AT TIME ZONE '$tzonename'),'YYYY-MM-DD HH24:MI:SS') as processingtime FROM root_withdraw_review WHERE account = '".$_SESSION['member']->account."' ORDER BY id DESC LIMIT 1;";
  $withdrawg_sql_result = runSQLALL($withdrawg_sql);

  // 現在時間
  $now = gmdate('Y-m-d H:i:s',time() + $tz * 3600);

  // 取不到資料表示該會員完全無取款申請紀錄
  $processingtime = $now;
  if ($withdrawg_sql_result[0] == 1) {
    // 對帳完成時間
    $processingtime = $withdrawg_sql_result[1]->processingtime;
    // 最後一次成功取款對帳完成時間 X 小時後的時間
    $how_many_hours = date('Y-m-d H:i:s',strtotime("$processingtime +".$member_grade_config_detail->withdrawalfee_free_hour." hour"));
    // 最後一次成功取款對帳完成時間 X 分鐘後(最後一次成功取款對帳完成時間)
    $how_many_mins = date('Y-m-d H:i:s',strtotime("$processingtime +".$member_grade_config_detail->withdrawal_limitstime_gtoken." min"));
  }

  // 最後一次成功取款對帳完成時間 X 小時後的時間
  $how_many_hours = date('Y-m-d H:i:s',strtotime("$processingtime +".$member_grade_config_detail->withdrawalfee_free_hour." hour"));
  // 最後一次成功取款對帳完成時間 X 分鐘後(最後一次成功取款對帳完成時間)
  $how_many_mins = date('Y-m-d H:i:s',strtotime("$processingtime +".$member_grade_config_detail->withdrawal_limitstime_gtoken." min"));


  // 根據不同手續費收取方式計算手續費
  // 1.免手續費2.X小時內取款Y次免收3.每次收手續費
  switch ($member_grade_config_detail->withdrawalfee_method) {
    case '1':
      $minimum_fee = 0;
      $fee_transaction_money = 0;
      break;
    case '2':
      // 現在時間在最後一筆通過的取款申請對帳時間及最後一筆通過的取款申請對帳時間+X小時內才做次數計算
      if ($now > $processingtime AND $now < $how_many_hours) {
        // 取得該會員最後一次取款成功後 X 小時內 取款次數
        $total_account_count_sql = "SELECT COUNT ('".$_SESSION['member']->account."') AS account_count FROM root_withdraw_review WHERE account = '".$_SESSION['member']->account."' AND status = '1' AND to_char((processingtime AT TIME ZONE '$tzonename'),'YYYY-MM-DD HH24:MI:SS') >= '$processingtime' AND to_char((processingtime AT TIME ZONE '$tzonename'),'YYYY-MM-DD HH24:MI:SS') <= '$how_many_hours';";
        $total_account_count_sql_result = runSQLALL($total_account_count_sql);

        if ($total_account_count_sql_result[0] != 1) {
           $logger = $tr['withdraw search error'];//'提款资讯查询查询发生错误，请联络客服人员协助。'
           memberlog2db($_SESSION['member']->account,'withdrawal','error', "$logger");
           echo '<script>alert("'.$logger.'");location.reload();</script>';
           die();
        }

        // 如果取款次數還在免收次數內則不設手續費
        if ($total_account_count_sql_result[1]->account_count < $member_grade_config_detail->withdrawalfee_free_times) {
          $minimum_fee = 0;
          $fee_transaction_money = 0;
        } else {
          $minimum_fee = 1;
          $fee_transaction_money = round((float)($transaction_money * ($member_grade_config_detail->withdrawalfee / 100)),2);
        }

      } else {
        $minimum_fee = 1;
        $fee_transaction_money = round((float)($transaction_money * ($member_grade_config_detail->withdrawalfee / 100)),2);
      }
      break;

    default:
      $minimum_fee = 1;
      $fee_transaction_money = round((float)($transaction_money * ($member_grade_config_detail->withdrawalfee / 100)),2);
      break;
  }

  // 計算手續費, 最低 1 元, 最高 50 元
  if ($minimum_fee == 1) {
    if ($fee_transaction_money < 1) {
      $fee_transaction_money = 1;
    } elseif ($fee_transaction_money > $member_grade_config_detail->withdrawalfee_max) {
      $fee_transaction_money = $member_grade_config_detail->withdrawalfee_max;
    }
  }

  // 計算總扣除額, 取款金額 + 手續費 + 稽核費用 + 優惠扣除
  $total_transaction_money = round((float)($transaction_money + $fee_transaction_money + $administrative_amount + $offer_deduction),2);

  // 取得該帳號最後一次取款成功後 X 分鐘內有無取款紀錄
  $withdrawal_limitstime_account_count_sql = "SELECT COUNT ('".$_SESSION['member']->account."') AS account_count FROM root_withdraw_review WHERE account = '".$_SESSION['member']->account."' AND status = '1' AND to_char((processingtime AT TIME ZONE '$tzonename'),'YYYY-MM-DD HH24:MI:SS') >= '$processingtime' AND to_char((now() AT TIME ZONE '$tzonename'),'YYYY-MM-DD HH24:MI:SS') <= '$how_many_mins';";
  $withdrawal_limitstime_account_count_sql_result = runSQLALL($withdrawal_limitstime_account_count_sql);

  if ($withdrawal_limitstime_account_count_sql_result[0] != 1) {
    $logger = $tr['withdraw search error'];//'提款资讯查询查询发生错误，请联络客服人员协助。'
    memberlog2db($_SESSION['member']->account,'withdrawal','error', "$logger");
    echo '<script>alert("'.$logger.'");location.reload();</script>';
    die();
  }

  // 判斷該帳號最後一次取款成功後 X 分鐘內有無取款紀錄
  if ($withdrawal_limitstime_account_count_sql_result[1]->account_count != 0) {
    $logger = $tr['This account is already in'].' '.$processingtime.' '.$tr['has been submitted'].$how_many_mins.' '.$tr['Please try again after'];//'该帐号已于 '' 提出过申请，请于''后再行尝试。'
    memberlog2db($_SESSION['member']->account,'withdrawal','warning', "$logger");
    echo '<script>alert("'.$logger.'");location.reload();</script>';
    die();
  }

  // // 檢查代幣是否在娛樂城
  // if($m != NULL AND $m->gtoken_lock != NULL) {
  //   $logger = 'ID '.$_SESSION['member']->id.'会员代币在娱乐城，请先取回娱乐城代币后再申请提款。';
  //   memberlog2db($_SESSION['member']->account,'withdrawal','warning', "$logger");
  //   echo '<script>alert("'.$logger.'");location.reload();</script>';
  //   die();
  // }

  // 檢查取款金額是否在會員等級設定單次取款限額上下限範圍內
  if ($transaction_money < $member_grade_config_detail->withdrawallimits_lower AND $transaction_money > $member_grade_config_detail->withdrawallimits_upper) {
    $logger = $tr['Withdrawal amount exceeds the upper limit'].' '.$member_grade_config_detail->withdrawallimits_upper.' '.$tr['Or below the lower limit'].' '.$member_grade_config_detail->withdrawallimits_lower.'，'.$tr['please enter again'];//取款金额超过上限 或低于下限 请重新输入。
    memberlog2db($_SESSION['member']->account,'withdrawal','warning', "$logger");
    echo '<script>alert("'.$logger.'");location.reload();</script>';
    die();
  }

  // 檢查會員錢包餘額是否大於總提款額(取款金額 + 手續費 + 稽核費用 + 優惠扣除)
  if ($m->gtoken_balance < $total_transaction_money) {
    $logger = $tr['Insufficient wallet balance,please try again'];//'钱包余额不足，请确认余额后再重新申请。'
    memberlog2db($_SESSION['member']->account,'withdrawal','warning', "$logger");
    echo '<script>alert("'.$logger.'");location.reload();</script>';
    die();
  }

  // 送出審核 $table_name = 'root_withdraw_review';
  $review_data = (object)[
    'tablename' => $table_name,
    'memberdata' => $m,
    'password' => $password_verify_sha1,
    'status' => $status,
    'transaction_money' => $transaction_money,
    'ip' => $fingertracker_remote_addr,
    'fingertracker' => $fingertracker,
    'fee_transaction_money' => $fee_transaction_money,
    'administrative_amount' => $administrative_amount,
    'offer_deduction' => $offer_deduction,
    'withdraw_method' => $withdraw_method,
    'transaction_id'=>$w_transaction_id
  ];

  $review_error = (object)submit_review($review_data);

  if (!$review_error->status) {
    $logger = $review_error->result;
    memberlog2db($_SESSION['member']->account,'withdrawal','warning', "$logger");
    // echo '<script>alert("'.$logger.'");</script>';
    echo '<script>alert("'.$logger.'");location.reload();</script>';
    die();
  }

  // 預扣代幣提款金額
  $error1 = member_gtoken_transfer($member_id, $source_transferaccount, $destination_transferaccount, $transaction_money, $password_verify_sha1, $summary, $transaction_category_index, $realcash, $auditmode_select, $auditmode_amount, $system_note, $debug,$w_transaction_id,$operator);

  // 預扣手續費
  if ($fee_transaction_money != 0) {
    $error2 = member_gtoken_transfer($member_id, $source_transferaccount, $destination_transferaccount, $fee_transaction_money, $password_verify_sha1, $fee_summary, $fee_transaction_category_index, $realcash, $auditmode_select, $auditmode_amount, $system_note, $debug,$w_transaction_id,$operator);
  } else {
    $error2['code'] = 1;
  }

  // 預扣稽核費用
  if ($administrative_amount != 0) {
    $error3 = member_gtoken_transfer($member_id, $source_transferaccount, $destination_transferaccount, $administrative_amount, $password_verify_sha1, $fee_summary, $fee_transaction_category_index, $realcash, $auditmode_select, $auditmode_amount, $system_note, $debug,$w_transaction_id,$operator);
  } else {
    $error3['code'] = 1;
  }

  // 預扣稽核不通過優惠金額
  if ($offer_deduction != 0) {
    $error4 = member_gtoken_transfer($member_id, $source_transferaccount, $destination_transferaccount, $offer_deduction, $password_verify_sha1, $offer_deduction_summary, $offer_deduction_transaction_category_index, $realcash, $auditmode_select, $auditmode_amount, $system_note, $debug,$w_transaction_id,$operator);
  } else {
    $error4['code'] = 1;
  }
  // var_dump($error1);   var_dump($error2);  var_dump($error3);   var_dump($error4);   die();

  // 7. 檢查是否成功寫入存簿
  if($error1['code'] == 1 AND $error2['code'] == 1 AND $error3['code'] == 1 AND $error4['code'] == 1) {
    //會員 申請提款 成功，狀態：待審核。
    $logger = $tr['member'].$_SESSION['member']->account.$tr['withdrawal apply'].$transaction_money.$tr['success state need check'];
    memberlog2db($_SESSION['member']->account,'withdrawal','info', "$logger");
    // echo '<script>alert("'.$logger.'");</script>';

    $withdraw_data = <<<SQL
		SELECT id FROM root_withdrawgcash_review WHERE account = '{$_SESSION['member']->account}' ORDER BY id DESC LIMIT 1;
SQL;

    $withdraw_result = runSQLall($withdraw_data);

    $currentDate = date("Y-m-d H:i:s", strtotime('now'));
    $notifyMsg = $msg->notifyMsg('TokenWithdrawal', $_SESSION['member']->account, $currentDate, ['data_id' => $withdraw_result[1]->id]);
    $notifyResult = $mq->fanoutNotify('msg_notify', $notifyMsg);

    echo '<script>alert("'.$logger.'");location.reload();</script>';
  } else {
    //(x)系統發生錯誤，請聯絡客服人員協助。
    $logger = $tr['system error'];
    memberlog2db($_SESSION['member']->account,'withdrawal','warning', "$logger");
    echo '<script>alert("'.$logger.'");location.reload();</script>';
    die();
  }

// ----------------------------------------------------------------------------
}elseif($action == 'withdrawal_cancel' AND isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T' ) {
// ----------------------------------------------------------------------------
// 移除 root_withdraw_review 的申請單
	//var_dump($_POST);
	$pk         = filter_var($_POST['pk'], FILTER_SANITIZE_NUMBER_INT);
	$name       = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
	$value      = filter_var($_POST['value'], FILTER_SANITIZE_STRING);

	if($value == 1 and $name = 'withdrawal_status_id') {
		$sql = "UPDATE root_withdraw_review SET status = '0' WHERE id = '$pk';";
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
// var_dump($_POST);

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
 * 送出代幣提款申請審查
 *
 * @param object $r
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
    "fee_amount", "administrative_amount", "companyname", "accountname",
    "accountnumber", "accountprovince", "accountcounty", "notes",
    "applicationtime", "processingaccount", "processingtime", "mobilenumber",
    "wechat", "email", "qq", "togcash",
    "applicationip", "fingerprinting", "offer_deduction","transaction_id"
  ) VALUES (
    '{$r->memberdata->account}', now(), '{$r->status}', '{$r->transaction_money}',
    '{$r->fee_transaction_money}',  '{$r->administrative_amount}', '{$r->memberdata->bankname}', '{$r->memberdata->realname}',
    '{$r->memberdata->bankaccount}', '{$r->memberdata->bankprovince}', '{$r->memberdata->bankcounty}', NULL,
    now(), NULL, NULL, '{$r->memberdata->mobilenumber}',
    '{$r->memberdata->wechat}', '{$r->memberdata->email}', '{$r->memberdata->qq}', '{$r->withdraw_method}',
    '{$r->ip}', '{$r->fingertracker}', '{$r->offer_deduction}','{$r->transaction_id}'
  )
SQL;

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
