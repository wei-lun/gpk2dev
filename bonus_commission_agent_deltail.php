<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 傭金分紅明細報表。
// File Name:	bonus_commission_agent_deltail.php
// Author:		Yuan
// Related:
// Log:
// ----------------------------------------------------------------------------
/*
DB Table :
root_statisticsdailyreport - 每日營收日結報表

root_statisticsbonusagent - 放射線組織獎金計算-直銷組織加盟金
root_statisticsbonussale - 放射線組織獎金計算-營業獎金
root_statisticsbonusprofit - 放射線組織獎金計算-營運利潤獎金


File :
agencyarea.php - 代理商專區

member_agentdepositgcash.php - 代理商會員錢包轉帳給其他會員

bonus_commission_agent_deltail.php - 傭金分紅明細
bonus_commission_sale_deltail.php - 營業分紅明細
bonus_commission_profit_deltail.php - 營利分紅明細
*/



// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

// var_dump($_SESSION);

// var_dump(session_id());
// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------


// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
$function_title = '傭金分紅明細';
// 擴充 head 內的 css or js
$extend_head				= '';
// 放在結尾的 js
$extend_js					= '';
// body 內的主要內容
$indexbody_content	= '';
// 系統訊息選單
$messages 					= '';
// ----------------------------------------------------------------------------
// 導覽列
$navigational_hierarchy_html = '
<ul class="breadcrumb">
  <li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
  <li><a href="member.php">'.$tr['Member Centre'].'</a></li>
  <li><a href="agencyarea.php">'.$tr['agencyarea title'].'</a></li>
  <li><a href="agencyarea_summary.php">加盟联营收入摘要</a></li>
  <li class="active">'.$function_title.'</li>
</ul>
';
// ----------------------------------------------------------------------------



// -------------------------------------------------------------------------
// $_GET 取得日期
// -------------------------------------------------------------------------
// get example: ?current_datepicker=2017-02-03
// ref: http://php.net/manual/en/function.checkdate.php
function validateDate($date, $format = 'Y-m-d H:i:s')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}


// 有登入，且不是測試帳號才顯示。
if(isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T') {
// --------------------------------------------------------------------------

  $goback_btn_html = '<a href="agencyarea.php" class="btn btn-primary" role="button">回代理商專區</a><hr>';



  $details_btn_html = $goback_btn_html.'
  <div class="btn-group btn-group-justified" role="group" aria-label="">
		<div class="btn-group" role="group">
      <a href="bonus_commission_agent_deltail.php" title="前往傭金分紅明細" class="btn btn-primary" role="button">傭金分紅明細</a>&nbsp;
		</div>
		<div class="btn-group" role="group">
      <a href="bonus_commission_sale_deltail.php" title="前往營業分紅明細" class="btn btn-default" role="button">營業分紅明細</a>&nbsp;
		</div>
		<div class="btn-group" role="group">
      <a href="bonus_commission_profit_deltail.php" title="前往營利分紅明細" class="btn btn-default" role="button">營利分紅明細</a>&nbsp;
		</div>
    <div class="btn-group" role="group">
      <a href="bonus_commission_dividend_deltail.php" title="前往股利分紅明細" class="btn btn-default" role="button">股利分紅明細</a>&nbsp;
		</div>
	</div>
  <hr>
  ';

  // $start_time_html = '<input type="text" class="form-control" name="search_day_start" id="search_day_start" placeholder="ex:2017-01-01" value="">';
  // $end_time_html = '<input type="text" class="form-control" name="search_day_end" id="search_day_end" placeholder="ex:2017-01-01" value="">';

  $time_limit_html = '<span>請選擇顯示資料的天數 : </span><br><br>';

  $search_day_button_html = $time_limit_html.'
  <a href="?timelimit=6" title="查詢7日內分紅資訊" class="btn btn-default" role="button">7日</a>&nbsp;
  <a href="?timelimit=13" title="查詢14日內分紅資訊" class="btn btn-default" role="button">14日</a>&nbsp;
  <a href="?timelimit=29" title="查詢30日內分紅資訊" class="btn btn-default" role="button">30日</a>&nbsp;
  <a href="?timelimit=89" title="查詢90日內分紅資訊" class="btn btn-default" role="button">90日</a>
  <hr>
  ';

  // $search_day_button_html = '
  // <form class="form-inline" method="get">
  //   <div class="form-group">
  //     <div class="input-group">
  //       '.$search_day_button.'
  //     </div>
  //     &nbsp;&nbsp;&nbsp;&nbsp;
  //     <div class="input-group">
  //       <div class="input-group-addon">開始時間 :</div>
  //       <div class="input-group-addon">'.$start_time_html.'</div>
  //       <div class="input-group-addon">結束時間 :</div>
  //       <div class="input-group-addon">'.$end_time_html.'</div>
  //     </div>
  //     <button type="submit" class="btn btn-primary" id="update_bonussale_option_query" onclick="#">查詢</button>
  //   </div>
  // </form>
  // <hr>';

  // // 即時編輯工具 ref: https://vitalets.github.io/x-editable/docs.html#gettingstarted
  // $extend_head = $extend_head.'
  // <!-- x-editable (bootstrap version) -->
  // <link href="bootstrap3-editable/css/bootstrap-editable.css" rel="stylesheet"/>
  // <script src="bootstrap3-editable/js/bootstrap-editable.min.js"></script>
  // ';
  // // 取得日期的 jquery datetime picker -- for 上面的生日格式
  // $extend_head = $extend_head.'<link rel="stylesheet" type="text/css" href="datetimepicker/jquery.datetimepicker.css"/>';
  // $extend_js = $extend_js.'<script src="datetimepicker/jquery.datetimepicker.full.min.js"></script>';
  // $extend_js = $extend_js."
  // <script>
  // $(document).ready(function(){
  //   // datetime picker
  //   $('#search_day_start, #search_day_end').datetimepicker({
  //     yearOffset:0,
  //     lang:'ch',
  //     timepicker:false,
  //     format:'Y/m/d'
  //   })
  // });
  // </script>
  // ";



  // -------------------------------------------------------------------------
  // $_GET 取得日期
  // -------------------------------------------------------------------------
  // get example: ?current_datepicker=2017-02-03
  // ref: http://php.net/manual/en/function.checkdate.php
  // function validateDate($date, $format = 'Y-m-d H:i:s')
  // {
  //     $d = DateTime::createFromFormat($format, $date);
  //     return $d && $d->format($format) == $date;
  // }

  /*
  取得 get 傳來的變數
  */
  if(isset($_GET['current_datepicker']) AND $_GET["current_datepicker"] != NULL) {
    // 判斷格式資料是否正確, 不正確以今天的美東時間為主
    $current_datepicker = validateDate($_GET['current_datepicker'], 'Y-m-d');
    //var_dump($current_datepicker);
    if($current_datepicker) {
      $current_datepicker = $_GET['current_datepicker'];
    } else {
      // 轉換為美東的時間 date
      $current_datepicker = gmdate('Y-m-d',time() + -4*3600);
      // $current_datepicker = '';
    }
  } else {
    // 轉換為美東的時間 date
    $current_datepicker = gmdate('Y-m-d',time() + -4*3600);
    // $current_datepicker = '';
  }
  //var_dump($current_datepicker);
  //echo date('Y-m-d H:i:sP');
  // 統計的期間時間 $rule['stats_commission_days'] 參考次變數
  $stats_commission_days = $rule['stats_commission_days'] - 1;
  $current_datepicker_start = date( "Y-m-d", strtotime( "$current_datepicker -$stats_commission_days day"));

  // ------------------------------------------------------------------
  // 下線貢獻詳細
  // ------------------------------------------------------------------

  if(isset($_GET['current_datepicker']) AND $_GET["current_datepicker"] != NULL) {

    $getdata_bonusagent_sql = "SELECT * FROM root_statisticsbonusagent WHERE dailydate_start ='".$current_datepicker_start."' AND dailydate_end ='".$current_datepicker."' AND (level_account_1 = '".$_SESSION['member']->account."' OR level_account_2 = '".$_SESSION['member']->account."' OR level_account_3 = '".$_SESSION['member']->account."' OR level_account_4 = '".$_SESSION['member']->account."');";
    // var_dump($getdata_bonusagent_sql);
    $getdata_bonusagent_result = runSQLall($getdata_bonusagent_sql,0,'r');
    // var_dump($getdata_bonusagent_result);

    $show_listrow_html = '';
    if($getdata_bonusagent_result[0] >= 1) {
      for($i = 1 ; $i <= $getdata_bonusagent_result[0] ; $i++) {
        // $member_id_sql = "SELECT id FROM root_member WHERE account = '".$getdata_bonusagent_result[$i]->member_account."';";
        // $member_id_sql_result = runSQLall($member_id_sql,0,'r');

        $b['table_id']               = $i;
        $b['id']                     = $getdata_bonusagent_result[$i]->id;
        // 會員的 member ID
        // $b['member_id']              = $member_id_sql_result[1]->id;
        $b['member_account']         = $getdata_bonusagent_result[$i]->member_account;
        $b['member_therole']         = $getdata_bonusagent_result[$i]->member_therole;
        $b['member_parent_id']       = $getdata_bonusagent_result[$i]->member_parent_id;
        $b['dailydate_start']        = $getdata_bonusagent_result[$i]->dailydate_start;
        $b['dailydate_end']          = $getdata_bonusagent_result[$i]->dailydate_end;
        $b['member_level']           = $getdata_bonusagent_result[$i]->member_level;
        $b['level_account_1']        = $getdata_bonusagent_result[$i]->level_account_1;
        $b['level_account_2']        = $getdata_bonusagent_result[$i]->level_account_2;
        $b['level_account_3']        = $getdata_bonusagent_result[$i]->level_account_3;
        $b['level_account_4']        = $getdata_bonusagent_result[$i]->level_account_4;
        $b['agency_commission']      = $getdata_bonusagent_result[$i]->agency_commission;
        $b['level_bonus_1']          = $getdata_bonusagent_result[$i]->level_bonus_1;
        $b['level_bonus_2']          = $getdata_bonusagent_result[$i]->level_bonus_2;
        $b['level_bonus_3']          = $getdata_bonusagent_result[$i]->level_bonus_3;
        $b['level_bonus_4']          = $getdata_bonusagent_result[$i]->level_bonus_4;
        $b['company_bonus']          = $getdata_bonusagent_result[$i]->company_bonus;

        $b['member_bonuscount_1']     = $getdata_bonusagent_result[$i]->member_bonuscount_1;
        $b['member_bonus_1']     = $getdata_bonusagent_result[$i]->member_bonus_1;
        $b['member_bonuscount_2']     = $getdata_bonusagent_result[$i]->member_bonuscount_2;
        $b['member_bonus_2']     = $getdata_bonusagent_result[$i]->member_bonus_2;
        $b['member_bonuscount_3']     = $getdata_bonusagent_result[$i]->member_bonuscount_3;
        $b['member_bonus_3']     = $getdata_bonusagent_result[$i]->member_bonus_3;
        $b['member_bonuscount_4']     = $getdata_bonusagent_result[$i]->member_bonuscount_4;
        $b['member_bonus_4']     = $getdata_bonusagent_result[$i]->member_bonus_4;

        $b['member_bonuscount']     = $getdata_bonusagent_result[$i]->member_bonuscount;
        $b['member_bonusamount']     = $getdata_bonusagent_result[$i]->member_bonusamount;
        $b['member_bonusamount_paid']= $getdata_bonusagent_result[$i]->member_bonusamount_paid;
        $b['member_bonusamount_paidtime'] = $getdata_bonusagent_result[$i]->member_bonusamount_paidtime;
        $b['notes']                   = $getdata_bonusagent_result[$i]->notes;
        // var_dump($b);


        $level_bonus_color_1 = '';
        $level_bonus_color_2 = '';
        $level_bonus_color_3 = '';
        $level_bonus_color_4 = '';
        if ($b['level_account_1'] == $_SESSION['member']->account) {
          $b['level_account_1'] = $getdata_bonusagent_result[$i]->level_account_1;
          $b['level_bonus_1'] = $getdata_bonusagent_result[$i]->level_bonus_1;
          $level_bonus_color_1 = 'red';
        } else {
          $b['level_account_1'] = '****';
          $b['level_bonus_1'] = '****';
        }

        if ($b['level_account_2'] == $_SESSION['member']->account) {
          $b['level_account_2'] = $getdata_bonusagent_result[$i]->level_account_2;
          $b['level_bonus_2'] = $getdata_bonusagent_result[$i]->level_bonus_2;
          $level_bonus_color_2 = 'red';
        } else {
          $b['level_account_2'] = '****';
          $b['level_bonus_2'] = '****';
        }

        if ($b['level_account_3'] == $_SESSION['member']->account) {
          $b['level_account_3'] = $getdata_bonusagent_result[$i]->level_account_3;
          $b['level_bonus_3'] = $getdata_bonusagent_result[$i]->level_bonus_3;
          $level_bonus_color_3 = 'red';
        } else {
          $b['level_account_3'] = '****';
          $b['level_bonus_3'] = '****';
        }

        if ($b['level_account_4'] == $_SESSION['member']->account) {
          $b['level_account_4'] = $getdata_bonusagent_result[$i]->level_account_4;
          $b['level_bonus_4'] = $getdata_bonusagent_result[$i]->level_bonus_4;
          $level_bonus_color_4 = 'red';
        } else {
          $b['level_account_4'] = '****';
          $b['level_bonus_4'] = '****';
        }

        if ($b['level_bonus_1'] != 0.00 OR $b['level_bonus_2'] != 0.00 OR $b['level_bonus_3'] != 0.00 OR $b['level_bonus_4'] != 0.00) {
          // 表格 row -- tables DATA
          // <td>'.$b['member_id'].'</td>
          $show_listrow_html = $show_listrow_html.'
          <tr>
            <td>'.$b['member_account'].'</td>
            <td><font color="'.$level_bonus_color_1.'">'.$b['level_bonus_1'].'</font></td>
            <td><font color="'.$level_bonus_color_2.'">'.$b['level_bonus_2'].'</font></td>
            <td><font color="'.$level_bonus_color_3.'">'.$b['level_bonus_3'].'</font></td>
            <td><font color="'.$level_bonus_color_4.'">'.$b['level_bonus_4'].'</font></td>
          </tr>
          ';
        }
      }
    }

    // -------------------------------------------------------------------------

  } else {
    // $show_listrow_html = '
    // <tr>
    //   <th></th>
    //   <th></th>
    //   <th></th>
    //   <th></th>
    //   <th></th>
    //   <th></th>
    // </tr>
    // ';
    $show_listrow_html = '';
  }

  $bonus_title = '
  <div style="border-bottom: 1px #555 solid;border-left: 10px #820000 solid;padding: 8px;margin: 5px; font-weight:bold; font-size: 1em; width: 300px;" id="deltail_table">
  '.$_SESSION['member']->account.' 會員 '.$current_datepicker_start.' 下線貢獻詳細(日)
  </div>
  ';


  // 貢獻詳細表格欄位名稱
  // <th>會員ID</th>
  $table_colname_html = '
  <tr>
    <th>會員帳號</th>
    <th>上層第1代分傭</th>
    <th>上層第2代分傭</th>
    <th>上層第3代分傭</th>
    <th>上層第4代分傭</th>
  </tr>
  ';


  // -------------------------------------------------------------------------
  // sorttable 的 jquery and plug info
  // -------------------------------------------------------------------------
  $sorttablecss = ' id="show_list"  class="display" cellspacing="0" width="100%" ';
  // $sorttablecss = ' class="table table-striped" ';

  // 列出資料, 主表格架構
  $show_list_html = '';
  // 列表
  $show_list_html = $bonus_title.'<br>'.$show_list_html.'
  <table '.$sorttablecss.'>
  <thead>
  '.$table_colname_html.'
  </thead>
  <tfoot>
  '.$table_colname_html.'
  </tfoot>
  <tbody>
  '.$show_listrow_html.'
  </tbody>
  </table>
  ';

  // 參考使用 datatables 顯示
  // https://datatables.net/examples/styling/bootstrap.html
  $extend_head = $extend_head.'
  <link rel="stylesheet" type="text/css" href="'.$cdnfullurl_js.'datatables/css/jquery.dataTables.min.css">
  <script type="text/javascript" language="javascript" src="'.$cdnfullurl_js.'datatables/js/jquery.dataTables.min.js"></script>
  <script type="text/javascript" language="javascript" src="'.$cdnfullurl_js.'datatables/js/dataTables.bootstrap.min.js"></script>
  ';

  // DATA tables jquery plugging -- 要放在 head 內 不可以放 body
  // 即時計算投注派冊差額，並顯示於表格 footer 內。start in 0
  // ref: https://datatables.net/reference/option/pageLength
  // ref: http://stackoverflow.com/questions/32962506/how-to-sum-of-some-rows-in-datatable-using-footercallback
  // 排序設定 "order" 參考 : https://datatables.net/reference/option/order
  $extend_head = $extend_head.'
  <script type="text/javascript" language="javascript" class="init">
    $(document).ready(function() {
      $("#show_list").DataTable( {
          "paging":   true,
          "ordering": true,
          "info":     true,
          "order": [[ 0, "asc" ]],
          "searching": false,
          "pageLength": 100
      } );
    } )
  </script>
  ';


  // -------------------------------------------------------------------------
  // 取時間範圍
  // -------------------------------------------------------------------------

  // 今日時間(美東)
  $today = gmdate('Y-m-d',time() + -4*3600);

  // 傭金分紅時間範圍,取今天以前3個月(每日)
  // $summary_start_day = date('Y-m-d', strtotime("$today -3 month"));
  // $summary_start_day = date('Y-m-d', strtotime("$today -6 days"));
  // $summary_end_day = $today;

  /*
  預設顯示90天的資料
  */
  $timelimit = '90';
  if (isset($_GET['timelimit']) AND $_GET["timelimit"] != NULL) {
    $timelimit = $_GET['timelimit'];
    $summary_start_day = date('Y-m-d', strtotime("$today -$timelimit day"));
  } else {
    $summary_start_day = date('Y-m-d', strtotime("$today -3 month"));
  }

  $summary_end_day = $today;

  // if(isset($_GET['current_datepicker']) AND $_GET["current_datepicker"] != NULL) {
  //   // $summary_start_day = $current_datepicker_start;
  //   $summary_sum_table_start_day = $current_datepicker_start;
  // } else {
  //   $summary_sum_table_start_day = $summary_start_day;
  // }

  /*
  如果使用者指定開始與結束時間查詢資料
  就將開始與結束時間重新設定
  */
  // 日期時間(開始)
  // if (isset($_GET["search_day_start"]) AND $_GET["search_day_start"] != NULL) {
  //   $summary_start_day = $_GET["search_day_start"];
  //   $summary_sum_table_start_day = $_GET["search_day_start"];
  // }

  // // 日期時間(結束)
  // if (isset($_GET["search_day_end"]) AND $_GET["search_day_end"] != NULL) {
  //   $summary_end_day = $_GET["search_day_end"];
  // }


  // -------------------------------------------------------------------------
  // 時間範圍內總計資料表(total)
  // -------------------------------------------------------------------------

  // $total_transaction_money_check = round((float)($transaction_money + $fee_transaction_money_check),2);
  // 時間範圍內資料加總表格欄位名稱
	$time_limit_summary_sum_table_colname_html = '
	<tr>
		<th class="info text-center">時間範圍內分傭總筆數</th>
		<th class="info text-center">時間範圍內分傭總計</th>
	</tr>
	';


  // 時間範圍內4代總筆數和分傭總計表格欄位名稱
	$summary_sum_table_colname_html = '
	<tr>
		<th class="info">個人第一代分傭總筆數</th>
		<th class="info">個人第一代分傭總計</th>
		<th class="info">個人第二代分傭總筆數</th>
		<th class="info">個人第二代分傭總計</th>
    <th class="info">個人第三代分傭總筆數</th>
		<th class="info">個人第三代分傭總計</th>
    <th class="info">個人第四代分傭總筆數</th>
		<th class="info">個人第四代分傭總計</th>
	</tr>
	';


  $summary_member_bonusamount_total_sql = "SELECT SUM(member_bonuscount_1) AS member_bonuscount_1, SUM(member_bonuscount_2) AS member_bonuscount_2, SUM(member_bonuscount_3) AS member_bonuscount_3, SUM(member_bonuscount_4) AS member_bonuscount_4, SUM(member_bonus_1) AS member_bonus_1, SUM(member_bonus_2) AS member_bonus_2, SUM(member_bonus_3) AS member_bonus_3, SUM(member_bonus_4) AS member_bonus_4 FROM root_statisticsbonusagent WHERE member_account = '".$_SESSION['member']->account."' AND dailydate_start >= '".$summary_start_day."' AND dailydate_end <= '".$summary_end_day."';";
  // var_dump($summary_member_bonusamount_total_sql);
  $summary_member_bonusamount_total_sql_result = runSQLall($summary_member_bonusamount_total_sql,0,'r');
  // var_dump($summary_member_bonusamount_total_sql_result);

  if ($summary_member_bonusamount_total_sql_result[0] == 1) {
    $member_bonuscount_1_sum = $summary_member_bonusamount_total_sql_result[1]->member_bonuscount_1;
    $member_bonuscount_2_sum = $summary_member_bonusamount_total_sql_result[1]->member_bonuscount_2;
    $member_bonuscount_3_sum = $summary_member_bonusamount_total_sql_result[1]->member_bonuscount_3;
    $member_bonuscount_4_sum = $summary_member_bonusamount_total_sql_result[1]->member_bonuscount_4;

    $member_bonus_1_sum = $summary_member_bonusamount_total_sql_result[1]->member_bonus_1;
    $member_bonus_2_sum = $summary_member_bonusamount_total_sql_result[1]->member_bonus_2;
    $member_bonus_3_sum = $summary_member_bonusamount_total_sql_result[1]->member_bonus_3;
    $member_bonus_4_sum = $summary_member_bonusamount_total_sql_result[1]->member_bonus_4;

  // '.$summary_start_day.'~'.$summary_end_day.'


    $time_limit_total_bonuscount = round((float)($member_bonuscount_1_sum + $member_bonuscount_2_sum + $member_bonuscount_3_sum + $member_bonuscount_4_sum),2);
    $time_limit_total_bonus = round((float)($member_bonus_1_sum + $member_bonus_2_sum + $member_bonus_3_sum + $member_bonus_4_sum),2);

    $show_time_limit_summary_sum_html = '
    <tr>
      <td class="text-center"><span>'.$time_limit_total_bonuscount.'</span></td>
      <td class="text-center"><span>'.$time_limit_total_bonus.'</span></td>
    </tr>
    ';

    $show_summary_sum_html = '
    <tr>
      <td class="text-center"><span>'.$member_bonuscount_1_sum.'</span></td>
      <td class="text-center"><span>'.$member_bonus_1_sum.'</span></td>
      <td class="text-center"><span>'.$member_bonuscount_2_sum.'</span></td>
      <td class="text-center"><span>'.$member_bonus_2_sum.'</span></td>
      <td class="text-center"><span>'.$member_bonuscount_3_sum.'</span></td>
      <td class="text-center"><span>'.$member_bonus_3_sum.'</span></td>
      <td class="text-center"><span>'.$member_bonuscount_4_sum.'</span></td>
      <td class="text-center"><span>'.$member_bonus_4_sum.'</span></td>
    </tr>
    ';
  } else {
    // $show_time_limit_summary_sum_html = '
    // <tr>
    //   <td></td>
    //   <td></td>
    // </tr>
    // ';

    // $show_summary_sum_html = '
    // <tr>
    //   <td></td>
    //   <td></td>
    //   <td></td>
    //   <td></td>
    //   <td></td>
    //   <td></td>
    //   <td></td>
    //   <td></td>
    // </tr>
    // ';

    $show_time_limit_summary_sum_html = '';
    $show_summary_sum_html = '';
  }


  $time_limit_html = '<span>時間範圍 : '.$summary_start_day.' ~ '.$summary_end_day.'</span><br><br>';

  $show_summary_sum_list_html = $time_limit_html.'
	<table class="table table-bordered small">
		<thead>
			'.$time_limit_summary_sum_table_colname_html.'
		</thead>
		<tbody>
			'.$show_time_limit_summary_sum_html.'
		</tbody>
	</table>
	';

  $show_summary_sum_list_html = $show_summary_sum_list_html.'
	<table class="table table-bordered small">
		<thead>
			'.$summary_sum_table_colname_html.'
		</thead>
		<tbody>
			'.$show_summary_sum_html.'
		</tbody>
	</table>
	<hr>
	';


  // -------------------------------------------------------------------------
  // 時間範圍內每天的資料(summary)
  // -------------------------------------------------------------------------

  $summary_member_bonusamount_sql = "SELECT * FROM root_statisticsbonusagent WHERE member_account = '".$_SESSION['member']->account."' AND dailydate_start >= '".$summary_start_day."' AND dailydate_end <= '".$summary_end_day."' ORDER BY dailydate_start DESC;";
  // $summary_member_bonusamount_sql = "SELECT * FROM root_statisticsbonusagent WHERE member_account = '".$_SESSION['member']->account."' AND dailydate_start >= '".$current_datepicker_start."' AND dailydate_end <= '".$current_datepicker."' ORDER BY dailydate_start;";
  // var_dump($summary_member_bonusamount_sql);
  $summary_member_bonusamount_sql_result = runSQLall($summary_member_bonusamount_sql,0,'r');
  // var_dump($summary_member_bonusamount_sql_result);


  $summary_listrow_html = '';
  if ($summary_member_bonusamount_sql_result[0] >= 0) {
    for ($i = 1; $i <= $summary_member_bonusamount_sql_result[0]; $i++) {
      $dailydate_start = $summary_member_bonusamount_sql_result[$i]->dailydate_start;
      $dailydate_end = $summary_member_bonusamount_sql_result[$i]->dailydate_end;

      $member_bonuscount_1 = $summary_member_bonusamount_sql_result[$i]->member_bonuscount_1;
      $member_bonuscount_2 = $summary_member_bonusamount_sql_result[$i]->member_bonuscount_2;
      $member_bonuscount_3 = $summary_member_bonusamount_sql_result[$i]->member_bonuscount_3;
      $member_bonuscount_4 = $summary_member_bonusamount_sql_result[$i]->member_bonuscount_4;

      $member_bonus_1 = $summary_member_bonusamount_sql_result[$i]->member_bonus_1;
      $member_bonus_2 = $summary_member_bonusamount_sql_result[$i]->member_bonus_2;
      $member_bonus_3 = $summary_member_bonusamount_sql_result[$i]->member_bonus_3;
      $member_bonus_4 = $summary_member_bonusamount_sql_result[$i]->member_bonus_4;

      $member_bonusamount = $summary_member_bonusamount_sql_result[$i]->member_bonusamount;

      $summary_bonuscount_color_1 = $member_bonuscount_1 != 0 ? 'blue' : '' ;
      $summary_bonuscount_color_2 = $member_bonuscount_2 != 0 ? 'blue' : '' ;
      $summary_bonuscount_color_3 = $member_bonuscount_3 != 0 ? 'blue' : '' ;
      $summary_bonuscount_color_4 = $member_bonuscount_4 != 0 ? 'blue' : '' ;

      $summary_bonus_color_1 = $member_bonus_1 != 0 ? 'red' : '' ;
      $summary_bonus_color_2 = $member_bonus_2 != 0 ? 'red' : '' ;
      $summary_bonus_color_3 = $member_bonus_3 != 0 ? 'red' : '' ;
      $summary_bonus_color_4 = $member_bonus_4 != 0 ? 'red' : '' ;


      $text_center_html = 'align="center" valign="center"';
      // <td><a href="?current_datepicker='.$dailydate_start.'" title="觀看指定時間區間的內容" target="_top" class="btn btn-xs btn-default" role="button">'.$dailydate_start.'</a></td>
      // <td><button type="button" class="btn btn-xs btn-primary" onclick=window.open("?current_datepicker='.$dailydate_start.'")>'.$dailydate_start.'</button></td>
      // <td><button type="button" class="btn btn-xs btn-primary summary_time_limit">'.$dailydate_start.'</button></td>
      $summary_listrow_html = $summary_listrow_html.'
      <tr id="'.$dailydate_start.'" class="">
        <td><a href="?current_datepicker='.$dailydate_start.'&timelimit='.$timelimit.'#deltail_table" title="觀看指定時間區間的內容" target="_top" class="btn btn-xs btn-default summary_time_limit" role="button">'.$dailydate_start.'</a></td>
        <td '.$text_center_html.'><font color="'.$summary_bonuscount_color_1.'">'.$member_bonuscount_1.'</font></td>
        <td '.$text_center_html.'><font color="'.$summary_bonus_color_1.'">'.$member_bonus_1.'</font></td>
        <td '.$text_center_html.'><font color="'.$summary_bonuscount_color_2.'">'.$member_bonuscount_2.'</font></td>
        <td '.$text_center_html.'><font color="'.$summary_bonus_color_2.'">'.$member_bonus_2.'</font></td>
        <td '.$text_center_html.'><font color="'.$summary_bonuscount_color_3.'">'.$member_bonuscount_3.'</font></td>
        <td '.$text_center_html.'><font color="'.$summary_bonus_color_3.'">'.$member_bonus_3.'</font></td>
        <td '.$text_center_html.'><font color="'.$summary_bonuscount_color_4.'">'.$member_bonuscount_4.'</font></td>
        <td '.$text_center_html.'><font color="'.$summary_bonus_color_4.'">'.$member_bonus_4.'</font></td>
        <td '.$text_center_html.'><strong>'.$member_bonusamount.'</strong></td>
      </tr>
      ';
    }
  } else {
    // $summary_listrow_html = '
    // <tr>
    //   <td></td>
    //   <td></td>
    //   <td></td>
    //   <td></td>
    //   <td></td>
    //   <td></td>
    //   <td></td>
    //   <td></td>
    //   <td></td>
    //   <td></td>
    // </tr>
    // ';
    $summary_listrow_html = '';
  }



  $summary_title = '
  <div style="border-bottom: 1px #555 solid;border-left: 10px #820000 solid;padding: 8px;margin: 5px; font-weight:bold; font-size: 1em; width: 300px;">
  個人傭金分紅資訊(日)
  </div>
  ';


// <a href="?current_datepicker='.$current_datepicker_start.'" title="觀看指定時間區間的內容" target="_top">'.$current_datepicker_start.'~'.$current_datepicker.'</a>
// <td>'.$current_datepicker_start.'~'.$current_datepicker.'</td>

  // 分紅資訊欄位名稱
  $table_summary_html = $summary_title.'
  <br>
  <table class="table table-bordered small">
    <thead>
      <tr class="active">
        <th>時間範圍</th>
        <th>個人第一代分傭筆數</th>
        <th>個人第一代分傭累計</th>
        <th>個人第二代分傭筆數</th>
        <th>個人第二代分傭累計</th>
        <th>個人第三代分傭筆數</th>
        <th>個人第三代分傭累計</th>
        <th>個人第四代分傭筆數</th>
        <th>個人第四代分傭累計</th>
        <th>個人分傭合計</th>
      </tr>
    </thead>
    <tbody style="background-color:rgba(255,255,255,0.4);">
      '.$summary_listrow_html.'
    </tbody>
  </table>
  <hr>
  ';

  // 點擊時改變 summary 該天整行的顏色
  $extend_js = $extend_js . "
  <script>
    $('#".$current_datepicker."').attr('class','success');
  </script>
  ";

  // --------------------------------------------------------------------------
  // 排版及 show content
  // --------------------------------------------------------------------------



  // $bonus_commission_agent_deltail_html = indexmenu_stats_switch();
  // <div class="col-12">
  //   '.$bonus_commission_agent_deltail_html.'
  //   </div>

  // 切成 1 欄版面
  $indexbody_content = '';
  $indexbody_content = $details_btn_html.$indexbody_content.'
  <div class="row">

    <div class="col-12">
      '.$search_day_button_html.'
    </div>
    <div class="col-12">
      '.$show_summary_sum_list_html.'
      '.$table_summary_html.'
    </div>
    <div class="col-12">
      '.$show_list_html.'
    </div>

  </div>
  <br>
  <div class="row">
    <div id="preview_result"></div>
  </div>
  ';


// --------------------------------------------------------------------------
} else {
// --------------------------------------------------------------------------
  // 搜尋條件
  $message_html = '';
  // 列出資料
  if(isset($_SESSION['member']) AND $_SESSION['member']->therole == 'T') {
    //試用帳號，請先登出再以會員登入使用。
    $message_html = $tr['trail use member first'];
  } else {
    //會員請先登入。
    $message_html = $tr['member login first'];
  }

  // 切成 1 欄版面
  $indexbody_content = '';
  $indexbody_content = $indexbody_content.'
	<div class="row">
	  <div class="col-12">
	  '.$message_html.'
	  </div>
	</div>
	<br>
	<div class="row">
		<div id="preview_result"></div>
	</div>
	';
}

// --------------------------------------------------------------------------


// ----------------------------------------------------------------------------
// 準備填入的內容
// ----------------------------------------------------------------------------
// 將內容塞到 html meta 的關鍵字, SEO 加強使用
$tmpl['html_meta_description'] 		= $config['companyShortName'];
$tmpl['html_meta_author']	 				= $config['companyShortName'];
$tmpl['html_meta_title'] 					= $function_title.'-'.$config['companyShortName'];

// 系統訊息顯示
$tmpl['message']									= $messages;
// 擴充再 head 的內容 可以是 js or css
$tmpl['extend_head']							= $extend_head;
// 擴充於檔案末端的 Javascript
$tmpl['extend_js']								= $extend_js;
// 主要內容 -- title
$tmpl['paneltitle_content'] 			= $navigational_hierarchy_html;
// 主要內容 -- content
$tmpl['panelbody_content']				= $indexbody_content;

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include($config['template_path']."template/admin.tmpl.php");
?>
