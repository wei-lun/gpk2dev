<?php
// ----------------------------------------------------------------------------
// Features:	對應 member_receivemoney.php
// File Name:	member_receivemoney_action.php
// Author:		Neil
// Related:
// Log:
// ----------------------------------------------------------------------------


// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

// gcash lib 現金轉帳函式庫
require_once dirname(__FILE__) ."/gcash_lib.php";
// gtoken lib 代幣轉帳函式庫
require_once dirname(__FILE__) ."/gtoken_lib.php";


// if(isset($_GET['a']) AND isset($_SESSION['member'])) {
//   $action = filter_var($_GET['a'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);

//   $csrftoken_ret = csrf_action_check();
//   if($csrftoken_ret['code'] != 1) {
//     die($csrftoken_ret['messages']);
//   }
// } else {
//   echo login2return_url(2);
//   die('(x)deny to access.');
// }

//var_dump($_SESSION);
// var_dump($_POST);
// var_dump($_GET);

if (!isset($_SESSION['member']) OR $_SESSION['member']->therole == 'T') {
  echo login2return_url(2);
  die('不合法的帐号权限');
};

// csrf驗證
$csrftoken_ret = csrf_action_check();
if($csrftoken_ret['code'] != 1) {
  die($csrftoken_ret['messages']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $post = (object)validatePost($_POST);

  if (!$post) {
    echo json_encode(['status' => 'fail', 'result' => '资料不合法，请确认资料正确性后再行尝试']);
    die();
  }

  switch ($post->result->action) {
    case 'detail':
      $receiveMoneyDetail = getReceiveMoneyDetail($post->result->id, $_SESSION['member']->account);

      if (!$receiveMoneyDetail) {
        echo json_encode(['status' => 'fail', 'result' => $tr['search no data']]);
        die();
      }

      $outputData = combineOutoutDataArr($receiveMoneyDetail);

      echo json_encode(['status' => 'success', 'result' => $outputData]);
      break;
    case 'more':
      $moreData = getMoreReceiveMoneyData($_SESSION['member']->account, $post->result->limit, $post->result->condition);

      if (!$moreData) {
        echo json_encode(['status' => 'fail', 'result' => $tr['search no data']]);
        die();
      }

      echo json_encode(['status' => 'success', 'result' => $moreData]);
      break;
    case 'expired':
      $todayDate = getTodayDate();
      $expiredData = getExpiredReceiveMoneyData($todayDate);

      if (!$expiredData) {
        echo json_encode(['status' => 'fail', 'result' => ['count' => 0, 'data' => $tr['search no data']]]);
        die();
      }

      echo json_encode(['status' => 'success', 'result' => $expiredData]);
      break;
    case 'received':
      $todayDate = getTodayDate();
      $receivedData = getReceivedReceiveMoneyData($todayDate);

      if (!$receivedData) {
        echo json_encode(['status' => 'fail', 'result' => ['count' => 0, 'data' => $tr['search no data']]]);
        die();
      }

      echo json_encode(['status' => 'success', 'result' => $receivedData]);
      break;
    case 'receive':
      $result = receiveMoney($post->result->id);

      if (!$result) {
        echo json_encode(['status' => 'fail', 'result' => '彩金领取失败']);
        die();
      }
      //update_gcash_log_exist($_SESSION['member']->account);
      echo json_encode(['status' => 'success', 'result' => '彩金領取成功']);
      break;
    case 'receiveAll':
      $result = receiveAllMoney($_SESSION['member']->account);

      if (!$result) {
        echo json_encode(['status' => 'fail', 'result' => '彩金领取失败']);
        die();
      }
      //update_gcash_log_exist($_SESSION['member']->account);
      echo json_encode(['status' => 'success', 'result' => '彩金领取成功']);
      break;
    default:
      echo json_encode(['status' => 'fail', 'result' => '错误的请求']);
      break;
  }
}

function validatePost($post)
{
  $input = array();

  if ($post['action'] == 'add') {
    $post = array_merge($post, $post['data']);
    unset($post['data']);
  }

  foreach ($post as $k => $v) {
    if ($k == 'note') {
      $input[$k] = ($v != '') ? filter_var($v, FILTER_SANITIZE_STRING) : '';
    } else {
      $input[$k] = filter_var($v, FILTER_SANITIZE_STRING);

      if ($input[$k] == '') {
        return array('status' => false, 'result' => '资料不合法，请确认资料正确性后再行尝试');
      }
    }
  }

  return array('status' => true, 'result' => (object)$input);
}

function receiveMoney($id)
{
  global $auditmode_select;

  $receiveMoneyDetail = getReceiveMoneyDetail($id, $_SESSION['member']->account);

  if (!$receiveMoneyDetail) {
    return false;
  }

  $data = [
    'member_id' => $_SESSION['member']->id,
    'operator' => $_SESSION['member']->account,
    'transaction_money' => '',
    'transaction_category_index' => $receiveMoneyDetail->transaction_category,
    'system_note' => $receiveMoneyDetail->summary,
    'audit_notes' => $auditmode_select[$receiveMoneyDetail->auditmode],
    'source_transferaccount' => '',
    'destination_transferaccount' => (object)['id' => $_SESSION['member']->id, 'account' => $_SESSION['member']->account],
    'fingertracker_remote_addr' => $_SESSION['fingertracker_remote_addr'],
    'fingertracker' => $_SESSION['fingertracker'],
    'realcash' => 0,
    'auditmode_select' => $receiveMoneyDetail->auditmode,
    'auditmode_amount' => $receiveMoneyDetail->auditmodeamount,
    'transaction_id' => get_transaction_id($_SESSION['member']->account, 'd')
  ];

  $updateReceivemoneyStatusSql = <<<SQL
  UPDATE root_receivemoney 
  SET receivetime = now(), 
      member_ip = '{$data['fingertracker_remote_addr']}', 
      member_fingerprinting = '{$data['fingertracker']}', 
      status = '3' 
  WHERE id = '{$receiveMoneyDetail->id}';
SQL;
  
  $updateBalanceSql = getTransferSql($receiveMoneyDetail->gcash_balance, $receiveMoneyDetail->gtoken_balance, $data);

  $transactionsSql = 'BEGIN;'.$updateBalanceSql['sql'].$updateReceivemoneyStatusSql.'COMMIT;';

  $result = runSQLtransactions($transactionsSql);
  
  if (!$result) {
    return false;
  }
  if($updateBalanceSql['transaction_money'] == 'gcash'){
    update_gcash_log_exist($_SESSION['member']->account);
  }
  
  return true;
}

function receiveAllMoney($acc)
{
  global $auditmode_select;

  $sql = '';

  $todayDate = getTodayDate();
  $receiveMoneyData = getReceiveMoneyData($todayDate, 100);

  if (!$receiveMoneyData) {
    return false;
  }

  foreach ($receiveMoneyData as $v) {
    $data = [
      'member_id' => $_SESSION['member']->id,
      'operator' => $_SESSION['member']->account,
      'transaction_money' => '',
      'transaction_category_index' => $v->transaction_category,
      'system_note' => $v->summary,
      'audit_notes' => $auditmode_select[$v->auditmode],
      'source_transferaccount' => '',
      'destination_transferaccount' => (object)['id' => $_SESSION['member']->id, 'account' => $_SESSION['member']->account],
      'fingertracker_remote_addr' => $_SESSION['fingertracker_remote_addr'],
      'fingertracker' => $_SESSION['fingertracker'],
      'realcash' => 0,
      'auditmode_select' => $v->auditmode,
      'auditmode_amount' => $v->auditmodeamount,
      'transaction_id' => get_transaction_id($_SESSION['member']->account, 'd')
    ];

    $sql .= <<<SQL
    UPDATE root_receivemoney 
    SET receivetime = now(), 
        member_ip = '{$data['fingertracker_remote_addr']}', 
        member_fingerprinting = '{$data['fingertracker']}', 
        status = '3' 
    WHERE id = '{$v->id}';
SQL;
    $Transfer=getTransferSql($v->gcash_balance, $v->gtoken_balance, $data);
    $sql .= $Transfer['sql'];
  }

  $transactionsSql = 'BEGIN;'.$sql.'COMMIT;';

  $result = runSQLtransactions($transactionsSql);

  if (!$result) {
    return false;
  }
  if($Transfer['transaction_money'] == 'gcash'){
    update_gcash_log_exist($_SESSION['member']->account);
  }
  return true;
}

function getTransferSql($gcash, $gtoken, $data)
{
  global $system_config;
  global $transaction_category;

  if ($gcash != 0 && $gtoken == 0) {
    $data['summary'] = $transaction_category['cashdeposit'];
    $data['transaction_money'] = $gcash;
    $data['source_transferaccount'] = (object)['id' => $system_config['casherid']['gcashcashier'], 'account' => 'gcashcashier'];
    $transaction_money='gcash';

    $sql = get_gcash_transfer_sql($data);
  } elseif($gtoken != 0 && $gcash == 0) {
    $data['summary'] = $transaction_category['tokendeposit'];
    $data['transaction_money'] = $gtoken;
    $data['source_transferaccount'] = (object)['id' => $system_config['casherid']['gtokencashier'], 'account' => 'gtokencashier'];
    $transaction_money='gtoken';

    $sql = get_gtoken_transfer_sql($data);
  } else {
    return false;
  }

  return ['sql'=>$sql,'transaction_money'=>$transaction_money];
}

function getReceiveMoneyDetail($id, $acc, $tzname = 'posix/Etc/GMT+4')
{
  $sql = <<<SQL
  SELECT *,
        to_char((givemoneytime AT TIME ZONE '{$tzname}') ,'YYYY-MM-DD  HH24:MI:SS') AS givemoneytime, 
        to_char((receivedeadlinetime AT TIME ZONE '{$tzname}') ,'YYYY-MM-DD  HH24:MI:SS') AS receivedeadlinetime,
        to_char((receivetime AT TIME ZONE '{$tzname}'),'YYYY-MM-DD HH24:MI:SS' ) as receivetime
  FROM root_receivemoney
  WHERE id = '{$id}'
  AND member_account = '{$acc}';
SQL;

  $result = runSQLall($sql);

  if (empty($result[0])) {
    return false;
  }

  unset($result[0]);

  return $result[1];
}

function getMoreReceiveMoneyData($acc, $limit, $condition, $tzname = 'posix/Etc/GMT+4')
{
  $todayDate = getTodayDate();
  $endDate = getDateRenge($todayDate);

  if ($condition == 'bonus') {
    $sqlCondition = <<<SQL
    AND status = '1'
    AND receivetime IS NULL
    AND to_char((receivedeadlinetime AT TIME ZONE '{$tzname}') ,'YYYY-MM-DD  HH24:MI:SS') > '{$todayDate}'
SQL;
  } elseif ($condition == 'expiredBonus') {
    $sqlCondition = $sqlCondition = <<<SQL
    AND status = '1'
    AND to_char((receivedeadlinetime AT TIME ZONE '{$tzname}') ,'YYYY-MM-DD  HH24:MI:SS') < '{$todayDate}'
    AND to_char((receivedeadlinetime AT TIME ZONE '$tzname'),'YYYY-MM-DD HH24:MI:SS') > '{$endDate}'
SQL;
  } elseif ($condition == 'receivedBonus') {
    $sqlCondition = <<<SQL
    AND status = '3'
    AND receivetime IS NOT NULL
    AND to_char((receivedeadlinetime AT TIME ZONE '$tzname'),'YYYY-MM-DD HH24:MI:SS') > '{$endDate}'
SQL;
  } else {
    return false;
  }

  $sql = <<<SQL
  SELECT *,
        to_char((givemoneytime AT TIME ZONE '{$tzname}') ,'YYYY-MM-DD  HH24:MI:SS') AS givemoneytime, 
        to_char((receivedeadlinetime AT TIME ZONE '{$tzname}') ,'YYYY-MM-DD  HH24:MI:SS') AS receivedeadlinetime
  FROM root_receivemoney
  WHERE member_account = '{$acc}'
  {$sqlCondition}
  ORDER BY id
  LIMIT 7
  OFFSET '{$limit}';
SQL;

  $result = runSQLall($sql);

  if (empty($result[0])) {
    return false;
  }

  $outputData['count'] = $result[0];

  unset($result[0]);

  $outputData['data'] = combineOutputDatasArr($result);

  return $outputData;
}

function getReceiveMoneyData($todayDate, $limit = 7, $tzname = 'posix/Etc/GMT+4')
{
  $endDate = getDateRenge($todayDate);

  $sql = <<<SQL
  SELECT *,
        to_char((givemoneytime AT TIME ZONE '{$tzname}'),'YYYY-MM-DD HH24:MI:SS') AS givemoneytime,
        to_char((receivedeadlinetime AT TIME ZONE '{$tzname}'),'YYYY-MM-DD HH24:MI:SS' ) AS receivedeadlinetime, 
        to_char((receivetime AT TIME ZONE '{$tzname}'),'YYYY-MM-DD HH24:MI:SS' ) as receivetime 
  FROM root_receivemoney 
  WHERE member_account = '{$_SESSION['member']->account}'
  AND status = '1' 
  AND to_char((receivedeadlinetime AT TIME ZONE '{$tzname}'),'YYYY-MM-DD HH24:MI:SS') >= '{$todayDate}'
  -- AND to_char((receivedeadlinetime AT TIME ZONE '{$tzname}'),'YYYY-MM-DD HH24:MI:SS') >= '{$endDate}'
  AND receivetime IS NULL
  ORDER BY id
  -- LIMIT 7;
  LIMIT {$limit};
SQL;

  $result = runSQLall($sql);

  if (empty($result[0])) {
    return false;
  }

  unset($result[0]);

  return $result;
}

// 已領取
function getReceivedReceiveMoneyData($todayDate, $tzname = 'posix/Etc/GMT+4')
{
  $endDate = getDateRenge($todayDate);

  $sql = <<<SQL
  SELECT *,
        to_char((givemoneytime AT TIME ZONE '{$tzname}'),'YYYY-MM-DD HH24:MI:SS') AS givemoneytime,
        to_char((receivedeadlinetime AT TIME ZONE '{$tzname}'),'YYYY-MM-DD HH24:MI:SS' ) AS receivedeadlinetime, 
        to_char((receivetime AT TIME ZONE '{$tzname}'),'YYYY-MM-DD HH24:MI:SS' ) as receivetime 
  FROM root_receivemoney 
  WHERE member_account = '{$_SESSION['member']->account}'
  AND status = '3'
  AND receivetime IS NOT NULL
  AND to_char((receivedeadlinetime AT TIME ZONE '{$tzname}'),'YYYY-MM-DD HH24:MI:SS') > '{$endDate}'
  ORDER BY id DESC
  LIMIT 7;
SQL;

  $result = runSQLall($sql);

  if (empty($result[0])) {
    return false;
  }

  $outputData['count'] = $result[0];

  unset($result[0]);

  $outputData['data'] = combineOutputDatasArr($result);

  return $outputData;
}

// 已過期
function getExpiredReceiveMoneyData($todayDate, $tzname = 'posix/Etc/GMT+4')
{
  $endDate = getDateRenge($todayDate);

  $sql = <<<SQL
  SELECT *,
        to_char((givemoneytime AT TIME ZONE '{$tzname}'),'YYYY-MM-DD HH24:MI:SS') AS givemoneytime,
        to_char((receivedeadlinetime AT TIME ZONE '{$tzname}'),'YYYY-MM-DD HH24:MI:SS' ) AS receivedeadlinetime, 
        to_char((receivetime AT TIME ZONE '$tzname'),'YYYY-MM-DD HH24:MI:SS' ) as receivetime 
  FROM root_receivemoney 
  WHERE member_account = '{$_SESSION['member']->account}'
  AND to_char((receivedeadlinetime AT TIME ZONE '{$tzname}'),'YYYY-MM-DD HH24:MI:SS') <= '{$todayDate}'
  AND to_char((receivedeadlinetime AT TIME ZONE '{$tzname}'),'YYYY-MM-DD HH24:MI:SS') >= '{$endDate}'
  ORDER BY id
  LIMIT 7;
SQL;

  $result = runSQLall($sql);

  if (empty($result[0])) {
    return false;
  }

  $outputData['count'] = $result[0];

  unset($result[0]);

  $outputData['data'] = combineOutputDatasArr($result);

  return $outputData;
}

function combineOutoutDataArr($data)
{
// ($v->receivetime == '') ? '-' : $v->receivetime
  $arr = [
    'id' => $data->id,
    'summary' => $data->summary,
    'gcash' => $data->gcash_balance,
    'gtoken' => $data->gtoken_balance,
    'totalBlance' => ($data->gcash_balance + $data->gtoken_balance),
    'starttime' => $data->givemoneytime,
    'endtime' => $data->receivedeadlinetime,
    'receivetime' => ($data->receivetime == '') ? '-' : $data->receivetime
  ];

  return $arr;
}

function combineOutputDatasArr($data)
{
  $arr = [];

  foreach ($data as $v) {
    $arr[] = [
      'id' => $v->id,
      'summary' => $v->summary,
      'gcash' => $v->gcash_balance,
      'gtoken' => $v->gtoken_balance,
      'totalBlance' => ($v->gcash_balance + $v->gtoken_balance),
      'starttime' => $v->givemoneytime,
      'endtime' => $v->receivedeadlinetime,
      'receivetime' => ($v->receivetime == '') ? '-' : $v->receivetime
    ];
  }

  return $arr;
}

function getTodayDate()
{
  $tz = '-04';

  return gmdate('Y-m-d H:i:s',time() + $tz * 3600);
}

function getDateRenge($todayDate)
{
  $twentynineDaysAgo = date('Y-m-d H:i:s', strtotime("$todayDate -29 day"));

  return $twentynineDaysAgo;
}
?>
