<?php
// ----------------------------------------------------------------------------
// Features:	前台 -- 線上存款功能 -- A 公司入款的動作
// File Name:	deposit_company_action.php
// Author:		Barkley
// Related:   deposit_company.php
// Log:
// 2017.7.5 改寫
// ----------------------------------------------------------------------------

// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

require_once __DIR__ . '/lib_message.php';
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
//var_dump($_SESSION);
// var_dump($_POST);
//var_dump($_GET);

function gatPerTransactionLimitLowerUpper($perTransactionLimit)
{
	global $member_grade_config_detail;

	$gradeTransactionLower = $member_grade_config_detail->depositlimits_lower;
	$gradeTransactionUpper = $member_grade_config_detail->depositlimits_upper;

	$result['lower'] = ($gradeTransactionLower == 0) ? 1 : $gradeTransactionLower;
	// $result['upper'] = ($perTransactionLimit > $gradeTransactionUpper) ? $gradeTransactionUpper: $perTransactionLimit;
	$result['upper'] = ($gradeTransactionUpper == 0) ? 1000 : $gradeTransactionUpper;


	return $result;
}

function checkPerTransactionLimit($amount, $perTransactionLimit)
{
	return ($amount <= $perTransactionLimit['upper'] && $amount >= $perTransactionLimit['lower']);
}

function checkDailyTransactionLimit($amout, $dailyLimit, $dailyTotal)
{
	return ($amout <= ($dailyLimit - $dailyTotal));
}

function checkMonthlyTransactionLimit($amout, $monthlyLimit, $monthlyTotal)
{
	return ($amout <= ($monthlyLimit - $monthlyTotal));
}

function getDepositCompanyData($depositCompanyId)
{
	$sql = <<<SQL
	SELECT * 
	FROM root_deposit_company 
	WHERE id = '{$depositCompanyId}';
SQL;

	$result = runSQLall($sql);

	if (empty($result[0])) {
		return false;
	}

	return $result[1];
}

function getDailyMonthlyTransactionTotal($type)
{
	$todayDate = date('Y-m-d', strtotime(date("Y-m-d")));

	$monthBeginDate = date('Y-m-01', strtotime(date("Y-m-d")));
	$monthEndDate = date('Y-m-d', strtotime("$monthBeginDate +1 month -1 day"));

	$sql = <<<SQL
	SELECT SUM(CASE WHEN transfertime BETWEEN '{$todayDate} 00:00:00' AND '{$todayDate} 23:59:59' THEN amount END) dailyTotal,
  			SUM(CASE WHEN transfertime BETWEEN '{$monthBeginDate} 00:00:00' AND '{$monthEndDate} 23:59:59' THEN amount END) monthlyTotal
	FROM root_deposit_review
	WHERE type = '{$type}'
	AND status != 0;
SQL;

	$result = runSQLall($sql);

	if (empty($result[0])) {
		return false;
	}

	return $result[1];
}

if($action == 'member_editpersondata' AND isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T' ) {
	$post = json_decode($_POST['data']);

  // 接收 $_POST 資訊，將資料寫入資料庫, 檢查輸入的字串有無問題
	// 存入到的會員帳號
	$account= $_SESSION['member']->account;
	// 帳號對應的資訊 id to root_deposit_company
	$depositcompanyid = filter_var($post->companynameid, FILTER_VALIDATE_INT);
	// 金額
	// $amount = filter_var($_POST['amount'], FILTER_VALIDATE_INT);
	$amount = filter_var($post->amount, FILTER_SANITIZE_STRING);
	// 轉帳時間 ex: timestamp '2016-10-20 12:00:00'
	// $transfertime = 'timestamp'." '".filter_var($_POST['transfertime'], FILTER_UNSAFE_RAW)."'";
	$transfertime_est = filter_var($post->datetimes, FILTER_UNSAFE_RAW);
	// 存入時間
	$transfertime = date("Y-m-d H:i:s",strtotime($transfertime_est)); 

	// 存款人姓名
	$depositoraccountname = filter_var($post->depositoraccountname, FILTER_SANITIZE_STRING);
	// 存款方式
	// $deposittype = filter_var($_POST['deposittype'], FILTER_SANITIZE_STRING);
	$deposittype = 'deposit_company';
	// 狀態 0=cancel 1=ok 2=apply null=del
	$status = 2;
	// note
	$notes = '';
	// 財富通.微信.支付寶.QQ入款等入款方式帳號暱稱
	// $accountname = filter_var($_POST['accountname'], FILTER_SANITIZE_STRING);
	$accountname = '';
	// 會員匯款帳號對帳資訊
	$reconciliation_notes = filter_var($post->reconciliation_notes, FILTER_SANITIZE_STRING);
	// ip
	$applicationip = $_SESSION['fingertracker_remote_addr'];
	// 指紋碼
	$fingerprinting = $_SESSION['fingertracker'];


	$depositCompanyData = getDepositCompanyData($depositcompanyid);

	if (!$depositCompanyData) {
		$logger = '错误的存款方式，请确认后再行操作';
		$msg = $logger;
		$msg_log = '存款帳戶ID：'.$depositcompanyid;
		$sub_service='deposit';
		memberlogtodb($_SESSION['member']->account,'accounting','notice',"$msg","$account","$msg_log",'f',$sub_service);
		echo '<script>alert("'.$logger.'");</script>';
		die();
	}

	$companyTransactionLimit = json_decode($depositCompanyData->transaction_limit);
	$deposittype = $depositCompanyData->type.'_'.$depositcompanyid;

	$perTransactionLimit = gatPerTransactionLimitLowerUpper($companyTransactionLimit->perTransactionLimit);
	$perTransactionLimitResult = checkPerTransactionLimit($amount, $perTransactionLimit);
	$dailyMonthlyTransactionTotal = getDailyMonthlyTransactionTotal($deposittype);

	if (!$dailyMonthlyTransactionTotal) {
		$logger = '限额查询错误';
		$msg = $logger;
		$msg_log = '存款方式：'.$deposittype;
		$sub_service='deposit';
		memberlogtodb($_SESSION['member']->account,'accounting','notice',"$msg","$account","$msg_log",'f',$sub_service);
		echo '<script>alert("'.$logger.'");</script>';
		die();
	}

	if (!$perTransactionLimitResult) {
		$logger = $tr['Please enter the correct deposit amount, the deposit limit is'].' $' . $perTransactionLimit['lower'] . ' ~ $ ' . $perTransactionLimit['upper'] . '。';
		$msg = $logger;
		$msg_log = '金額：'.$amount;
		$sub_service='deposit';
		memberlogtodb($_SESSION['member']->account,'accounting','notice',"$msg","$account","$msg_log",'f',$sub_service);
		echo '<script>alert("'.$logger.'");</script>';
		die();
	}

	$dailyTransactionLimitResult = checkDailyTransactionLimit($amount, $companyTransactionLimit->dailyTransactionLimit, $dailyMonthlyTransactionTotal->dailytotal);
	
	if (!$dailyTransactionLimitResult) {
		$logger = '存款金额超过本日限额，请修改存款金额或改用其他存款方式';
		$msg = $logger;
		$msg_log = '金額：'.$amount;
		$sub_service='deposit';
		memberlogtodb($_SESSION['member']->account,'accounting','notice',"$msg","$account","$msg_log",'f',$sub_service);
		echo '<script>alert("'.$logger.'");</script>';
		die();
	}

	$monthlyTransactionLimitResult = checkMonthlyTransactionLimit($amount, $companyTransactionLimit->monthlyTransactionLimit, $dailyMonthlyTransactionTotal->monthlytotal);

	if (!$monthlyTransactionLimitResult) {
		$logger = '存款金额超过本月限额，请修改存款金额或改用其他存款方式';
		$msg = $logger;
		$msg_log = '金額：'.$amount;
		$sub_service='deposit';
		memberlogtodb($_SESSION['member']->account,'accounting','notice',"$msg","$account","$msg_log",'f',$sub_service);
		echo '<script>alert("'.$logger.'");</script>';
		die();
	}

	if(strlen($amount) == 0 OR strlen($transfertime) == 0 OR strlen($depositoraccountname) == 0 OR strlen($deposittype) == 0 OR strlen($reconciliation_notes) == 0) {
		$logger = '请填写完整转帐资料';
		// memberlog 2db($_SESSION['member']->account,'withdrawal','warning', "$logger");
		$msg='请填写完整转帐资料。';
		$msg_log = '金額：$'.$amount.'，轉帳時間：'.$transfertime.'，存款人姓名：'.$depositoraccountname.'，匯款帳號對帳資訊：'.$reconciliation_notes.'。';
		$sub_service='deposit';
		memberlogtodb($_SESSION['member']->account,'accounting','notice',"$msg","$account","$msg_log",'f',$sub_service);
		echo '<script>alert("'.$logger.'");location.reload();</script>';
		die();
	}

	$amount = round(floatval($amount), 2);

	if ($amount == '0') {
		$logger = '存款金额需为正整数或小数下两位浮点数';
		// memberlog 2db($_SESSION['member']->account,'withdrawal','warning', "$logger");
		$msg='存款金额需为正整数或小数下两位浮点数';
		$msg_log = '金額：'.$amount;
		$sub_service='deposit';
		memberlogtodb($_SESSION['member']->account,'accounting','notice',"$msg","$account","$msg_log",'f',$sub_service);
		echo '<script>alert("'.$logger.'");location.reload();</script>';
		die();
	}

	$member_sql = "SELECT * FROM root_member WHERE account = '".$_SESSION['member']->account."';";
	$member_sql_result = runSQLall($member_sql);

	if ($member_sql_result[0] != 1) {
		$logger = "会员资料查询错误，请联络客服人员协助。";
		// memberlog 2db($_SESSION['member']->account,'withdrawal','warning', "$logger");
		$msg='会员资料查询错误，请联络客服人员协助。';
		$msg_log = '会员资料错误：'.$_SESSION['member']->accoun;
		$sub_service='information';
		memberlogtodb($_SESSION['member']->account,'member','error',"$msg","$account","$msg_log",'f',$sub_service);
		echo '<script>alert("'.$logger.'");location.reload();</script>';
		die();
	}

	// 該會員銀行帳戶資訊及聯絡資訊
	$member_bankname = $member_sql_result[1]->bankname;
	$member_bankaccount = $member_sql_result[1]->bankaccount;
	$member_bankprovince = $member_sql_result[1]->bankprovince;
	$member_bankcounty = $member_sql_result[1]->bankcounty;
	$member_realname = $member_sql_result[1]->realname;
	$member_mobilenumber = $member_sql_result[1]->mobilenumber;
	$member_email = $member_sql_result[1]->email;
	$member_wechat = $member_sql_result[1]->wechat;
	$member_qq = $member_sql_result[1]->qq;

	// 銀行開戶網點 = 省分+縣市
	$accountarea = $member_bankprovince.$member_bankcounty;

	// 公司入款交易單號，預設以 (w/d)20180515_useraccount_亂數3碼 為單號，其中 W 代表提款 D 代表存款
	$d_transaction_id='d'.gmdate('YmdHis',time() + -4*3600).$account.str_pad(mt_rand(1,999),3,'0',STR_PAD_LEFT);
	// $d_transaction_id='d'.date("YmdHis").$account.str_pad(mt_rand(1,999),3,'0',STR_PAD_LEFT);


	if ($post->type == 'virtualmoney') {
		$current_exchangerate = round(filter_var(floatval($post->current_exchangerate), FILTER_SANITIZE_STRING), 2);
		$cryptocurrency_amount = round(filter_var(floatval($post->cryptocurrency_amount), FILTER_SANITIZE_STRING), 2);

		if (!$current_exchangerate) {
			$logger = '错误的汇率，请确认后再行操作';
			$msg = $logger;
			$msg_log = '虛擬貨幣匯率：'.$current_exchangerate;
			$sub_service='deposit';
			memberlogtodb($_SESSION['member']->account,'accounting','notice',"$msg","$account","$msg_log",'f',$sub_service);
			echo '<script>alert("'.$logger.'");</script>';
			die();
		}

		if (!$cryptocurrency_amount || $cryptocurrency_amount < 0) {
			$logger = '错误的虚拟货币金额或小于等于零，请确认后再行操作';
			$msg = $logger;
			$msg_log = '虛擬貨幣金額：'.$cryptocurrency_amount;
			$sub_service='deposit';
			memberlogtodb($_SESSION['member']->account,'accounting','notice',"$msg","$account","$msg_log",'f',$sub_service);
			echo '<script>alert("'.$logger.'");</script>';
			die();
		}

		$desposit_sql = <<<SQL
		INSERT INTO root_deposit_review (
			account, changetime, status, amount, transfertime, 
			depositoraccountname, notes, type, processingaccount, processingtime, 
			depositcompanyid, fingerprinting, applicationip, companyname, accountnumber, 
			accountarea, accountname, reconciliation_notes, realname, mobilenumber, 
			wechat, email, qq, transaction_id, current_exchangerate, 
			cryptocurrency_amount
		) VALUES (
			'{$account}', now(), '{$status}', '{$amount}', '{$transfertime}' , 
			'{$depositoraccountname}', '{$notes}', '{$deposittype}', NULL, NULL, 
			'{$depositcompanyid}', '{$fingerprinting}', '{$applicationip}', '{$member_bankname}', '{$member_bankaccount}', 
			'{$accountarea}', '{$accountname}', '{$reconciliation_notes}', '{$member_realname}', '{$member_mobilenumber}', 
			'{$member_wechat}', '{$member_email}', '{$member_qq}','{$d_transaction_id}', '{$current_exchangerate}', 
			'{$cryptocurrency_amount}'
		);

SQL;
	} else {
		$desposit_sql = <<<SQL
		INSERT INTO root_deposit_review (
			account, changetime, status, amount, transfertime, 
			depositoraccountname, notes, type, processingaccount, processingtime, 
			depositcompanyid, fingerprinting, applicationip, companyname, accountnumber, 
			accountarea, accountname, reconciliation_notes, realname, mobilenumber, 
			wechat, email, qq, transaction_id
		) VALUES (
			'{$account}', now(), '{$status}', '{$amount}', '{$transfertime}' , 
			'{$depositoraccountname}', '{$notes}', '{$deposittype}', NULL, NULL, 
			'{$depositcompanyid}', '{$fingerprinting}', '{$applicationip}', '{$member_bankname}', '{$member_bankaccount}', 
			'{$accountarea}', '{$accountname}', '{$reconciliation_notes}', '{$member_realname}', '{$member_mobilenumber}', 
			'{$member_wechat}', '{$member_email}', '{$member_qq}','{$d_transaction_id}'
		);

SQL;
	}

	$desposit_result = runSQLall($desposit_sql);

	if($desposit_result[0] == 1) {
		// create message
		$message = new Message;
		$message->setTitle('公司存款')->setMessage('会员 '.$_SESSION['member']->account.' 申请存款成功，状态：待审核。');

		// get channel
		$channel = get_message_channel(
			'backstage', // platform = [front|backstage]
			'account'    // channel 
		);

		// send message
		mqtt_send($channel, $message);

		$desposit_data = <<<SQL
		SELECT id FROM root_deposit_review WHERE account = '{$_SESSION['member']->account}' ORDER BY id DESC LIMIT 1;
SQL;

		$desposit_data_result = runSQLall($desposit_data);

		$currentDate = date("Y-m-d H:i:s", strtotime('now'));
		$notifyMsg = $msg->notifyMsg('CompanyDeposit', $_SESSION['member']->account, $currentDate, ['data_id' => $desposit_data_result[1]->id]);
		$notifyResult = $mq->fanoutNotify('msg_notify', $notifyMsg);
		// $notifyResult = $mq->directAdd('direct_test', 'direct_test', $notifyMsg);


		$logger = '会员 '.$_SESSION['member']->account.' 申请存款成功，状态：待审核。';
		// memberlog 2db($_SESSION['member']->account,'withdrawal','info', "$logger");
		$msg=$_SESSION['member']->account.' 申请存款成功，状态：待审核。';
		$msg_log = ' 申请存款成功，状态：待审核。';
		$sub_service='deposit';
		memberlogtodb($_SESSION['member']->account,'accounting','info',"$msg",$_SESSION['member']->account,"$msg_log",'f',$sub_service);
		echo '<script language="javascript">document.location.href="./deposit_company_status.php";</script>';
	}else{
		$logger = $tr['deposit info error'];
		// memberlog 2db($_SESSION['member']->account,'withdrawal','warning', "$logger");
		$msg=$tr['deposit info error'];
		$msg_log = 'deposit_company_action.php:159，'.$tr['deposit info error'];
		$sub_service='deposit';
		memberlogtodb($_SESSION['member']->account,'accounting','error',"$msg",$_SESSION['member']->account,"$msg_log",'f',$sub_service);
		echo '<script>alert("'.$logger.'");location.reload();</script>';
	}

}elseif($action == 'test' AND isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T' ) {
// ----------------------------------------------------------------------------
// test developer
// ----------------------------------------------------------------------------
  var_dump($_POST);

}



?>