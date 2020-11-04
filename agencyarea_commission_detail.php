<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 代理商專區，轉帳及觀看會員的報表。
// File Name:	agencyarea_summary.php
// Author:		Yuan
// Related:
// Log:
// ----------------------------------------------------------------------------
/*
DB Table :
  root_statisticsdailypreferential
*/

session_start();

// 主機及資料庫設定
require_once __DIR__ ."/config.php";
// 支援多國語系
require_once __DIR__ ."/i18n/language.php";
// 自訂函式庫
require_once __DIR__ ."/lib.php";

require_once __DIR__ ."/lib_view.php";

// var_dump($_SESSION);

// var_dump(session_id());
// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------

function get_permission_message() {
  global $tr;
  if(!isset($_SESSION['member'])) {
    return login2return_url('agencyarea_summary.php');
  }

  if($_SESSION['member']->therole == 'T') {
    return $tr['trail use member first'];

  } elseif($_SESSION['member']->therole != 'A' && $_SESSION['member']->therole != 'R') {
    // 直接從 menu 拿描述或自己寫描述

    return menu_agentadmin('agencyarea_summary.php');
  }

  return 'pass';
}

// default of commission detail
function getDefaultCommissionDetail() {
  return (object)[
    'member_account' => '',
    'dailydate' => '',
    'end_date' => '',
    'agent_commission' => 0,
    'all_favorablerate_amount' => '-',
    'commission_detail' => [
      'all_bets_amount' => 0,
      'all_profitloss_amount' => 0,
      'profitloss_distribute' => [
        'total_profitloss' => 0,
        'total_bets_detail' => [],
        'level_distribute ' => [],
      ],
      'all_profitloss_amount_detail' => [
        'level_distribute' => [],
        'plateform_cost' => [],
      ],
    ],
  ];
}
//$tr['Agent Income Summary'] = '代理商收入摘要';$tr['preferential cost'] = '優惠成本';$tr['Bonus Cost'] = '反水成本';
function renderDefaultPage() {
  global $tr;

  $commission_detail = getDefaultCommissionDetail();
  $has_no_commission_from_successor = true;
  $has_no_self_profitloss = true;
  $has_permission = true;

  // render view
  $function_title = '代理商收入摘要';
  $tmpl['html_meta_title'] = $function_title.'-'.$config['companyShortName'];

  return render(
    __DIR__ . '/agencyarea_commission_detail.view.php',
    compact(
      'commission_detail',
      'has_no_commission_from_successor',
      'has_no_self_profitloss',
      'has_permission'
    )
  );
}

// view helper for geting plateform cost name
function get_plateform_cost_name($type) {
  global $tr;
  switch ($type) {
    case 'favorable':
      return '优惠成本';
    case 'preferential':
      return '反水成本';
    default:
      return '';
  }
}


// Main

// 有登入，有錢包才顯示。只有代理商可以進入
$has_permission = (
  isset($_SESSION['member'])
  && ($_SESSION['member']->therole == 'A' || $_SESSION['member']->therole == 'R')
);


// no permission return no permission view
if(! $has_permission) {
  // render view
  $function_title = '代理商收入摘要';
  $tmpl['html_meta_title'] = $function_title.'-'.$config['companyShortName'];

  return render(
    __DIR__ . '/agencyarea_commission_detail.view.php',
    compact('function_title', 'has_permission')
  );
}


// validate request
if( ! (isset($_GET['member_account']) && isset($_GET['dailydate_start']) && isset($_GET['dailydate_end']) ) ) {
  return renderDefaultPage();
}

$member_account = $_GET['member_account'];
$dailydate_start = $_GET['dailydate_start'];
$dailydate_end = $_GET['dailydate_end'];

// validate dailydate format
try {
  $datetime = new \DateTime($dailydate_start);
  $dailydate_start = $datetime->format('Y-m-d');

  $datetime = new \DateTime($dailydate_end);
  $dailydate_end = $datetime->format('Y-m-d');
} catch(\Exception $e) {
  return renderDefaultPage();
}


// get commission_detail
$sql =<<<SQL
  SELECT *
    FROM root_commission_dailyreport as detail
    WHERE detail.member_account = :member_account
      AND detail.dailydate = :dailydate_start
      AND detail.end_date = :dailydate_end;
SQL;

$commission_detail_result =
  runSQLall_prepared(
    $sql,
    [
      ':member_account' => $member_account,
      ':dailydate_start' => $dailydate_start,
      ':dailydate_end' => $dailydate_end
    ]
  );


if(isset($commission_detail_result[0]) ) {
  $commission_detail = $commission_detail_result[0];
} else {
  return renderDefaultPage();
}

// get data and decode json

$commission_detail->commission_detail = json_decode($commission_detail->commission_detail, true);
// $commission_detail->favorable_distribute = json_decode($preferential_detail->favorable_distribute, true);

// 無來自下線的佣金
$has_no_commission_from_successor = (
  ! isset($commission_detail->commission_detail['all_profitloss_amount_detail']['level_distribute'])
  || empty($commission_detail->commission_detail['all_profitloss_amount_detail']['level_distribute'])
);

// 無自身損益
$has_no_self_profitloss = (
  ! isset($commission_detail->commission_detail['profitloss_distribute']['level_distribute'])
  || empty($commission_detail->commission_detail['profitloss_distribute']['level_distribute'])
);


// render view
$tmpl['html_meta_title'] = '代理商收入摘要-'.$config['companyShortName'];

return render(
  __DIR__ . '/agencyarea_commission_detail.view.php',
  compact(
    'has_permission',
    'dailydate_start',
    'dailydate_end',
    'commission_detail',
    'has_no_commission_from_successor',
    'has_no_self_profitloss'
  )
);
