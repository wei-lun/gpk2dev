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
$function_title = '代理商收入摘要';

$has_permission = (
  isset($_SESSION['member'])
  && ($_SESSION['member']->therole == 'A' || $_SESSION['member']->therole == 'R')
);

$is_test_account = ( isset($_SESSION['member']) && $_SESSION['member']->therole == 'T' );

// no permission return no permission view
if(! $has_permission) {
  return render(
    __DIR__ . '/agencyarea_preferential_detail.view.php',
    compact('has_permission', 'is_test_account')
  );
}

// validate request
if( ! (isset($_GET['member_account']) && isset($_GET['dailydate'])) ) {
  $member_account = $_SESSION['member']->account;
  $date = new \DateTime;
  $dailydate = date_format($date->modify('+1 day'), 'Y-m-d');
} else {
  $member_account = $_GET['member_account'];
  $dailydate = $_GET['dailydate'];
}

// validate dailydate format
try {
  $datetime = new \DateTime($dailydate);
  $dailydate = $datetime->format('Y-m-d');
} catch(\Exception $e) {
  $date = new \DateTime;
  $dailydate = date_format($date->modify('+1 day'), 'Y-m-d');
}

// get preferential_detail
$sql =<<<SQL
  SELECT *
    FROM root_statisticsdailypreferential as detail
    WHERE detail.member_account = :member_account AND detail.dailydate = :dailydate;
SQL;

$preferential_detail_result = runSQLall_prepared($sql, [':member_account' => $member_account, ':dailydate' => $dailydate]);



if(isset($preferential_detail_result[0]) ) {
  $preferential_detail = $preferential_detail_result[0];
} else {
  // id not exsited
  // render404();
  // die();

  $preferential_detail = (object)[
    'member_account' => $member_account,
    'dailydate' => $dailydate,
    'all_favorablerate_amount' => '-',
    'all_favorablerate_amount_detail' => '{}',
    'favorable_distribute' => '{}',
  ];
}

// get data and decode json

$preferential_detail->all_favorablerate_amount_detail = json_decode($preferential_detail->all_favorablerate_amount_detail, true);
$preferential_detail->favorable_distribute = json_decode($preferential_detail->favorable_distribute, true);

// 無來自下線的反水
$has_no_preferential_from_successor = (
  ! isset($preferential_detail->all_favorablerate_amount_detail['level_distribute'])
  || empty($preferential_detail->all_favorablerate_amount_detail['level_distribute'])
);

// 無自身反水
$has_no_self_preferential = (
  ! isset($preferential_detail->favorable_distribute['level_distribute'])
  || empty($preferential_detail->favorable_distribute['level_distribute'])
);

// 自身反水比
$self_favorablerate = 0;
if(isset($preferential_detail->all_favorablerate_amount_detail['self_favorablerate'])) {
  $self_favorablerate = $preferential_detail->all_favorablerate_amount_detail['self_favorablerate'];
}


// render view
$tmpl['html_meta_title'] = '代理商收入摘要-'.$config['companyShortName'];
// 側邊欄MENU var[0]側邊欄組合(lib_menu) var[1]目前頁籤名
$tmpl['sidebar_content'] =['agent','agencyarea_summary'];

return render(
  __DIR__ . '/agencyarea_preferential_detail.view.php',
  compact(
    'function_title',
    'preferential_detail',
    'has_permission',
    'is_test_account',
    'has_no_preferential_from_successor',
    'has_no_self_preferential',
    'self_favorablerate'
  )
);
