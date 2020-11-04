<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 營利分紅明細明細明細報表。
// File Name:	agencyarea.php
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
$function_title = '營利分紅明細';
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
  <br>
  <div class="btn-group btn-group-justified" role="group" aria-label="">
		<div class="btn-group" role="group">
      <a href="bonus_commission_agent_deltail.php" title="前往傭金分紅明細" class="btn btn-default" role="button">傭金分紅明細</a>&nbsp;
		</div>
		<div class="btn-group" role="group">
      <a href="bonus_commission_sale_deltail.php" title="前往營業分紅明細" class="btn btn-default" role="button">營業分紅明細</a>&nbsp;
		</div>
		<div class="btn-group" role="group">
      <a href="bonus_commission_profit_deltail.php" title="前往營利分紅明細" class="btn btn-primary" role="button">營利分紅明細</a>&nbsp;
		</div>
    <div class="btn-group" role="group">
      <a href="bonus_commission_dividend_deltail.php" title="前往股利分紅明細" class="btn btn-default" role="button">股利分紅明細</a>&nbsp;
		</div>
	</div>
  <hr>
  <br>
  ';


  if (isset($_GET['dailydate_start']) AND $_GET["dailydate_start"] != NULL AND isset($_GET['dailydate_end']) AND $_GET["dailydate_end"] != NULL) {

    $month_date_start = validateDate($_GET['dailydate_start'], 'Y-m-d');
    $month_date_end = validateDate($_GET['dailydate_end'], 'Y-m-d');

    if ($month_date_start AND $month_date_end) {
      $month_date_start = $_GET['dailydate_start'];
      $month_date_end = $_GET['dailydate_end'];
    } else {
      $month_date_start = gmdate('Y-m-01',time() + -4*3600);
      $month_date_end = date('Y-m-d',strtotime("$month_date_start +1 month -1 day"));
    }
  } else {
    // 這個月第一天和最後一天
    $month_date_start = gmdate('Y-m-01',time() + -4*3600);
    $month_date_end = date('Y-m-d',strtotime("$month_date_start +1 month -1 day"));
  }


  // ------------------------------------------------------------------
  // 下線貢獻詳細
  // ------------------------------------------------------------------

  if(isset($_GET['dailydate_start']) AND $_GET["dailydate_start"] != NULL AND isset($_GET['dailydate_end']) AND $_GET["dailydate_end"] != NULL) {

    //$check_data_alive_sql = "SELECT * FROM root_statisticsbonusprofit WHERE dailydate_start ='".$month_date_start."' AND dailydate_end ='".$month_date_end."' ;";
    $check_data_alive_sql = "SELECT * FROM root_statisticsbonusprofit
    WHERE dailydate_start ='$month_date_start' AND dailydate_end ='$month_date_end' AND
    (profitaccount_1 = '".$_SESSION['member']->account."'  OR profitaccount_2 = '".$_SESSION['member']->account."' OR profitaccount_3 = '".$_SESSION['member']->account."' OR profitaccount_4 = '".$_SESSION['member']->account."'); ";
    //print_r($check_data_alive_sql);
    $check_data_alive_result = runSQLall($check_data_alive_sql,0,'r');
    // var_dump($getdata_bonussale_result);

    $show_listrow_html = '';
    if($check_data_alive_result[0] >= 1) {
      for($i = 1 ; $i <= $check_data_alive_result[0] ; $i++) {

        $b['id'] = $check_data_alive_result[$i]->id;
        $b['updatetime'] = $check_data_alive_result[$i]->updatetime;

        // $b['member_id'] = $userlist[$i]->id;
        $b['member_account']  = $check_data_alive_result[$i]->member_account;

        // 預設有會員的 ID , Account, Role
        $b['member_level']  = $check_data_alive_result[$i]->member_level;
        $b['skip_bonusinfo']  = $check_data_alive_result[$i]->skip_bonusinfo;
        $skip_bonusinfo_count     = explode(":",$b['skip_bonusinfo']);
        //var_dump($skip_bonusinfo_count);  取得第一個字串，為跳過的代數
        $b['skip_agent_tree_count'] = $skip_bonusinfo_count[0];
        $b['profitaccount_1']  = $check_data_alive_result[$i]->profitaccount_1;
        $b['profitaccount_2']  = $check_data_alive_result[$i]->profitaccount_2;
        $b['profitaccount_3']  = $check_data_alive_result[$i]->profitaccount_3;
        $b['profitaccount_4']  = $check_data_alive_result[$i]->profitaccount_4;
        $b['profit_amount']  = $check_data_alive_result[$i]->profit_amount;
        $b['profit_amount_1']  = $check_data_alive_result[$i]->profit_amount_1;
        $b['profit_amount_2']  = $check_data_alive_result[$i]->profit_amount_2;
        $b['profit_amount_3']  = $check_data_alive_result[$i]->profit_amount_3;
        $b['profit_amount_4']  = $check_data_alive_result[$i]->profit_amount_4;

        // 統計的欄位
        $b['member_profitlost_cashcost']  = $check_data_alive_result[$i]->member_profitlost_cashcost;
        $b['member_profitlost_marketingcost']  = $check_data_alive_result[$i]->member_profitlost_marketingcost;
        $b['sum_tokenfavorable']  = $check_data_alive_result[$i]->sum_tokenfavorable;
        $b['sum_tokenpreferential']  = $check_data_alive_result[$i]->sum_tokenpreferential;
        $b['member_profitlost_platformcost']  = $check_data_alive_result[$i]->member_profitlost_platformcost;
        $b['days_count']  = $check_data_alive_result[$i]->days_count;
        $b['sum_all_count']  = $check_data_alive_result[$i]->sum_all_count;
        $b['sum_all_bets']  = $check_data_alive_result[$i]->sum_all_bets;
        $b['sum_all_wins']  = $check_data_alive_result[$i]->sum_all_wins;
        $b['sum_all_profitlost']  = $check_data_alive_result[$i]->sum_all_profitlost;

        // 代理商本月分潤
        $b['member_profitamount_1']       = $check_data_alive_result[$i]->member_profitamount_1;
        $b['member_profitamount_count_1'] = $check_data_alive_result[$i]->member_profitamount_count_1;
        $b['member_profitamount_2']       = $check_data_alive_result[$i]->member_profitamount_2;
        $b['member_profitamount_count_2'] = $check_data_alive_result[$i]->member_profitamount_count_2;
        $b['member_profitamount_3']       = $check_data_alive_result[$i]->member_profitamount_3;
        $b['member_profitamount_count_3'] = $check_data_alive_result[$i]->member_profitamount_count_3;
        $b['member_profitamount_4']       = $check_data_alive_result[$i]->member_profitamount_4;
        $b['member_profitamount_count_4'] = $check_data_alive_result[$i]->member_profitamount_count_4;
        $b['member_profitamount_count']   = $check_data_alive_result[$i]->member_profitamount_count;
        $b['member_profitamount']  = $check_data_alive_result[$i]->member_profitamount;
        $b['member_profitamount_paid']  = $check_data_alive_result[$i]->member_profitamount_paid;
        $b['member_profitamount_paidtime']  = $check_data_alive_result[$i]->member_profitamount_paidtime;

        // 上月留抵
        $b['lasttime_stayindebt']  = $check_data_alive_result[$i]->lasttime_stayindebt;
        // 備註
        $b['notes']  = $check_data_alive_result[$i]->notes;


        // skip agent
        $skip_agent_tree_html = '<a href="#" title="'.$b['skip_bonusinfo'].'">'.$b['skip_agent_tree_count'].'</a>';


        $profitaccount_color_1 = '';
        $profitaccount_color_2 = '';
        $profitaccount_color_3 = '';
        $profitaccount_color_4 = '';
        if ($b['profitaccount_1'] == $_SESSION['member']->account) {
          // $b['level_account_1'] = $getdata_bonusagent_result[1]->level_account_1;
          // $b['level_bonus_1'] = $getdata_bonusagent_result[1]->level_bonus_1;
          $profitaccount_color_1 = 'red';
        } else {
          $b['profit_amount_1'] = '****';
        }

        if ($b['profitaccount_2'] == $_SESSION['member']->account) {
          // $b['level_account_2'] = $getdata_bonusagent_result[1]->level_account_2;
          // $b['level_bonus_2'] = $getdata_bonusagent_result[1]->level_bonus_2;
          $profitaccount_color_2 = 'red';
        } else {
          $b['profit_amount_2'] = '****';
        }

        if ($b['profitaccount_3'] == $_SESSION['member']->account) {
          // $b['level_account_3'] = $getdata_bonusagent_result[1]->level_account_3;
          // $b['level_bonus_3'] = $getdata_bonusagent_result[1]->level_bonus_3;
          $profitaccount_color_3 = 'red';
        } else {
          $b['profit_amount_3'] = '****';
        }

        if ($b['profitaccount_4'] == $_SESSION['member']->account) {
          // $b['level_account_4'] = $getdata_bonusagent_result[1]->level_account_4;
          // $b['level_bonus_4'] = $getdata_bonusagent_result[1]->level_bonus_4;
          $profitaccount_color_4 = 'red';
        } else {
          $b['profit_amount_4'] = '****';
        }

        if ($b['profit_amount_1'] != 0.00 OR $b['profit_amount_2'] != 0.00 OR $b['profit_amount_3'] != 0.00 OR $b['profit_amount_4'] != 0.00) {
        //if ($b['profitaccount_1'] == $_SESSION['member']->account OR $b['profitaccount_2'] == $_SESSION['member']->account OR $b['profitaccount_3'] == $_SESSION['member']->account OR $b['profitaccount_4'] == $_SESSION['member']->account) {
          // <td>'.$b['member_id'].'</td>
          $show_listrow_html = $show_listrow_html.'
          <tr>
            <td>'.$b['member_account'].'</td>
            <td>'.$skip_agent_tree_html.'</td>
            <td><font color="'.$profitaccount_color_1.'">'.$b['profit_amount_1'].'</font></td>
            <td><font color="'.$profitaccount_color_2.'">'.$b['profit_amount_2'].'</font></td>
            <td><font color="'.$profitaccount_color_3.'">'.$b['profit_amount_3'].'</font></td>
            <td><font color="'.$profitaccount_color_4.'">'.$b['profit_amount_4'].'</font></td>
          </tr>
          ';
        }
      }
    }
  } else {
    // $show_listrow_html = '
    // <tr>
    //   <th></th>
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

  $perforbouns_title = '
  <div style="border-bottom: 1px #555 solid;border-left: 10px #820000 solid;padding: 8px;margin: 5px; font-weight:bold; font-size: 1em; width: 400px;" id="deltail_table">
  '.$_SESSION['member']->account.' 會員 '.$month_date_start.' ~ '.$month_date_end.' 下線貢獻詳細(月)
  </div>
  ';

  // 貢獻詳細表格欄位名稱
  // <th>會員ID</th>
  $table_colname_html = '
  <tr>
    <th>帳號</th>
    <th>被跳過層數</th>
    <th>第一代分紅</th>
    <th>第二代分紅</th>
    <th>第三代分紅</th>
    <th>第四代分紅</th>
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
  $show_list_html = $perforbouns_title.'<br>'.$show_list_html.'
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

  // ------------------------------------------------------------------


  // ------------------------------------------------------------------
  // 取時間範圍
  // ------------------------------------------------------------------

  // 取得今年第一天與最後一天(美東)
  $summary_start_day = gmdate('Y-01-01',time() + -4*3600);
  $summary_end_day = date('Y-m-d', strtotime("$summary_start_day +1 year -1 day"));


  // ------------------------------------------------------------------
  // 時間範圍內總計資料表(total)
  // ------------------------------------------------------------------

  $time_limit_summary_sum_table_colname_html = '
	<tr>
		<th class="info text-center">時間範圍內分潤總筆數</th>
		<th class="info text-center">時間範圍內分潤總計</th>
	</tr>
	';

  // 表格欄位名稱
	$summary_sum_table_colname_html = '
	<tr>
		<th class="info">第一代分潤總筆數</th>
		<th class="info">第一代分潤總計</th>
		<th class="info">第二代分潤總筆數</th>
		<th class="info">第二代分潤總計</th>
    <th class="info">第三代分潤總筆數</th>
		<th class="info">第三代分潤總計</th>
    <th class="info">第四代分潤總筆數</th>
		<th class="info">第四代分潤總計</th>
	</tr>
	';


  $summary_member_bonusamount_total_sql = "SELECT SUM(member_profitamount_count_1) AS member_profitamount_count_1, SUM(member_profitamount_count_2) AS member_profitamount_count_2, SUM(member_profitamount_count_3) AS member_profitamount_count_3, SUM(member_profitamount_count_4) AS member_profitamount_count_4, SUM(member_profitamount_1) AS member_profitamount_1, SUM(member_profitamount_2) AS member_profitamount_2, SUM(member_profitamount_3) AS member_profitamount_3, SUM(member_profitamount_4) AS member_profitamount_4 FROM root_statisticsbonusprofit WHERE member_account = '".$_SESSION['member']->account."' AND dailydate_start >= '".$summary_start_day."' AND dailydate_end <= '".$summary_end_day."';";
  //var_dump($summary_member_bonusamount_total_sql);
  //print_r($summary_member_bonusamount_total_sql);
  $summary_member_bonusamount_total_sql_result = runSQLall($summary_member_bonusamount_total_sql,0,'r');
  // var_dump($summary_member_bonusamount_total_sql_result);

  if ($summary_member_bonusamount_total_sql_result[0] == 1) {
    $member_bonuscount_1_sum = $summary_member_bonusamount_total_sql_result[1]->member_profitamount_count_1;
    $member_bonuscount_2_sum = $summary_member_bonusamount_total_sql_result[1]->member_profitamount_count_2;
    $member_bonuscount_3_sum = $summary_member_bonusamount_total_sql_result[1]->member_profitamount_count_3;
    $member_bonuscount_4_sum = $summary_member_bonusamount_total_sql_result[1]->member_profitamount_count_4;

    $member_bonus_1_sum = $summary_member_bonusamount_total_sql_result[1]->member_profitamount_1;
    $member_bonus_2_sum = $summary_member_bonusamount_total_sql_result[1]->member_profitamount_2;
    $member_bonus_3_sum = $summary_member_bonusamount_total_sql_result[1]->member_profitamount_3;
    $member_bonus_4_sum = $summary_member_bonusamount_total_sql_result[1]->member_profitamount_4;

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


  // ------------------------------------------------------------------
  // 時間範圍內每天的資料(summary)
  // ------------------------------------------------------------------

  $summary_member_bonusamount_sql = "SELECT * FROM root_statisticsbonusprofit WHERE member_account = '".$_SESSION['member']->account."' AND dailydate_start >= '".$summary_start_day."' AND dailydate_end <= '".$summary_end_day."' ORDER BY dailydate_start DESC;";
  // $summary_member_bonusamount_sql = "SELECT * FROM root_statisticsbonussale WHERE member_account = '".$_SESSION['member']->account."' AND dailydate_start >= '".$current_datepicker_start."' AND dailydate_end <= '".$current_datepicker."' ORDER BY dailydate_start;";
  // var_dump($summary_member_bonusamount_sql);
  // print_r($summary_member_bonusamount_sql);
  $summary_member_bonusamount_sql_result = runSQLall($summary_member_bonusamount_sql,0,'r');
  // var_dump($summary_member_bonusamount_sql_result);



  $summary_listrow_html = '';
  if ($summary_member_bonusamount_sql_result[0] >= 0) {
    for ($i = 1; $i <= $summary_member_bonusamount_sql_result[0]; $i++) {
      $dailydate_start = $summary_member_bonusamount_sql_result[$i]->dailydate_start;
      $dailydate_end = $summary_member_bonusamount_sql_result[$i]->dailydate_end;
      // var_dump($dailydate_start);
      // var_dump($dailydate_end);

      $member_bonuscount_1 = $summary_member_bonusamount_sql_result[$i]->member_profitamount_count_1;
      $member_bonuscount_2 = $summary_member_bonusamount_sql_result[$i]->member_profitamount_count_2;
      $member_bonuscount_3 = $summary_member_bonusamount_sql_result[$i]->member_profitamount_count_3;
      $member_bonuscount_4 = $summary_member_bonusamount_sql_result[$i]->member_profitamount_count_4;

      $member_bonus_1 = $summary_member_bonusamount_sql_result[$i]->member_profitamount_1;
      $member_bonus_2 = $summary_member_bonusamount_sql_result[$i]->member_profitamount_2;
      $member_bonus_3 = $summary_member_bonusamount_sql_result[$i]->member_profitamount_3;
      $member_bonus_4 = $summary_member_bonusamount_sql_result[$i]->member_profitamount_4;

      $member_bonusamount = $summary_member_bonusamount_sql_result[$i]->member_profitamount;

      $skip_bonusinfo = $summary_member_bonusamount_sql_result[$i]->skip_bonusinfo;

      $skip_bonusinfo_count     = explode(":",$skip_bonusinfo);
      // 取得第一個字串，為跳過的代數
      // var_dump($skip_bonusinfo_count);
      $skip_agent_tree_count = $skip_bonusinfo_count[0];

      // skip agent
      $skip_agent_tree_html = '<a href="#" title="'.$skip_bonusinfo.'">'.$skip_agent_tree_count.'</a>';


      $summary_bonuscount_color_1 = $member_bonuscount_1 != 0 ? 'blue' : '' ;
      $summary_bonuscount_color_2 = $member_bonuscount_2 != 0 ? 'blue' : '' ;
      $summary_bonuscount_color_3 = $member_bonuscount_3 != 0 ? 'blue' : '' ;
      $summary_bonuscount_color_4 = $member_bonuscount_4 != 0 ? 'blue' : '' ;

      $summary_bonus_color_1 = $member_bonus_1 != 0 ? 'red' : '' ;
      $summary_bonus_color_2 = $member_bonus_2 != 0 ? 'red' : '' ;
      $summary_bonus_color_3 = $member_bonus_3 != 0 ? 'red' : '' ;
      $summary_bonus_color_4 = $member_bonus_4 != 0 ? 'red' : '' ;


      // <td><a href="?current_datepicker='.$dailydate_start.'" title="觀看指定時間區間的內容" target="_top">'.$dailydate_start.'~'.$dailydate_end.'</a></td>
      $text_center_html = 'align="center" valign="center"';
      $summary_listrow_html = $summary_listrow_html.'
      <tr id="'.$dailydate_start.'" class="">
        <td><a href="?dailydate_start='.$dailydate_start.'&dailydate_end='.$dailydate_end.'#deltail_table" title="觀看指定時間區間的內容" target="_top" class="btn btn-xs btn-default" role="button">'.$dailydate_start.'~'.$dailydate_end.'</a></td>
        <td '.$text_center_html.'>'.$skip_agent_tree_html.'</td>
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
    //   <td></td>
    // </tr>
    // ';
    $summary_listrow_html = '';
  }


  $summary_title = '
  <div style="border-bottom: 1px #555 solid;border-left: 10px #820000 solid;padding: 8px;margin: 5px; font-weight:bold; font-size: 1em; width: 300px;">
  個人營利分紅資訊(月)
  </div>
  ';


// <a href="?current_datepicker='.$current_datepicker_start.'" title="觀看指定時間區間的內容" target="_top">'.$current_datepicker_start.'~'.$current_datepicker.'</a>
// <td>'.$current_datepicker_start.'~'.$current_datepicker.'</td>

  $table_summary_html = $summary_title.'
  <br>
  <table class="table table-bordered small">
    <thead>
      <tr class="active">
        <th>時間範圍</th>
        <th>被跳過層數</th>
        <th>第一代分潤筆數</th>
        <th>第一代分潤</th>
        <th>第二代分潤筆數</th>
        <th>第二代分潤</th>
        <th>第三代分潤筆數</th>
        <th>第三代分潤</th>
        <th>第四代分潤筆數</th>
        <th>第四代分潤</th>
        <th>分潤總和</th>
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
    // console.log('".$month_date_start."');
    $('#".$month_date_start."').attr('class','success');
  </script>
  ";

  // ------------------------------------------------------------------


  // 切成 1 欄版面
  $indexbody_content = '';
  $indexbody_content = $details_btn_html.$indexbody_content.'
  <div class="row">

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
