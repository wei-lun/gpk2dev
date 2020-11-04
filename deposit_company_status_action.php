<?php
// ----------------------------------------------------------------------------
// Features:    審核狀態資料處理
// File Name:   deposit_company_status_action.php
// Author:      Neil
// Related:     
// Table :      
// Log:
// ----------------------------------------------------------------------------

// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

require_once dirname(__FILE__) ."/stationmail_lib.php";

// -----------------------------------------------------------------------------
// 只允許代理權限操作
if (!isset($_SESSION['member']) || $_SESSION['member']->therole == 'R' || $_SESSION['member']->therole == 'T') {
  echo login2return_url(2);
  die($tr['permission error']);//'不合法的帐号权限'
};

// csrf驗證
$csrftoken_ret = csrf_action_check();
if($csrftoken_ret['code'] != 1) {
  // die($csrftoken_ret['messages']);
  echo json_encode(['status' => 'fail', 'result' => $csrftoken_ret['messages']]);
  die();
}
// -----------------------------------------------------------------------------

function getReviewData($status, $tzonename = 'posix/Etc/GMT-8')
{
  global $tr;

  $data = [];

  $deposit_review_status = [
    // $tr['discard deposit application'], // 放棄存款申請
    '存款申请审查退回',
    $tr['deposit application approved'],  // 存款申請審查通過
    $tr['deposit submitted for review'],  // 存款提交審查中
    $tr['deleted deposit request']  // 已刪除的存款申請
  ];

  $sql = <<<SQL
  SELECT to_char((transfertime AT TIME ZONE '{$tzonename}'),'YYYY-MM-DD HH24:MI:SS') as transfertime,
         to_char((changetime AT TIME ZONE 'posix/Etc/GMT+4'), 'YYYY-MM-DD HH24:MI:SS' ) as changetime,
        companyname,
        accountnumber,
        amount,
        reconciliation_notes,
        transaction_id,
        status
  FROM root_deposit_review
  WHERE status = $status
  AND account = '{$_SESSION['member']->account}'
SQL;
  
  if ($status != '2') {
    $sql .= <<<SQL
    AND transfertime > now() - interval '1 month'
SQL;
  }
  
  $sql .= <<<SQL
    ORDER BY changetime DESC;
SQL;
  
  $result = runSQLall($sql);

  if (empty($result[0])) {
    return false;
  }

  unset($result[0]);

  foreach ($result as $v) {
    $status = ($v->status === NULL) ? $deposit_review_status[4] : $deposit_review_status[$v->status];

    $data[] = [
      'changetime' =>  $v->changetime,
      'companyname' => $v->companyname,
      'accountnumber' => $v->accountnumber,
      'transfertime' =>  $v->transfertime,
      'amount' => $v->amount,
      'reconciliation_notes' => $v->reconciliation_notes,
      'transaction_id' => $v->transaction_id,
      'status' => $status
    ];
  }

  return $data;
}

if ($_POST['action'] == 'statusData') {
  $option = [ 
    'review' => '2',
    'pass' => '1',
    'reject' => '0'
  ];

  $data = json_decode($_POST['data']);
  
  if (!array_key_exists($data->status, $option)) {
    echo json_encode(['status' => 'fail', 'result' => $tr['Data is error']]);
    die();
  }
  
  $status = $option[$data->status];

  $reviewData = getReviewData($status);

  if (!$reviewData) {
    echo json_encode(['status' => 'fail', 'result' => $tr['no deposit result']]);
    die();
  }
    
  echo json_encode(['status' => 'success', 'site_style' => $config['site_style'], 'result' => $reviewData]);
} else {
  echo json_encode(['status' => 'fail', 'result' => $tr['bad request']]);
  die();
}
