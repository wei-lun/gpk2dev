<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 股利分紅明細報表。
// File Name:	bonus_commission_dividend_deltail.php
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
$function_title = '股利分紅明細報表';
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
      <a href="bonus_commission_profit_deltail.php" title="前往營利分紅明細" class="btn btn-default" role="button">營利分紅明細</a>&nbsp;
		</div>
    <div class="btn-group" role="group">
      <a href="bonus_commission_dividend_deltail.php" title="前往股利分紅明細" class="btn btn-primary" role="button">股利分紅明細</a>&nbsp;
		</div>
	</div>
  <hr>
  <br>
  ';


  // if (isset($_GET['dailydate_start']) AND $_GET["dailydate_start"] != NULL AND isset($_GET['dailydate_end']) AND $_GET["dailydate_end"] != NULL) {

  //   $month_date_start = validateDate($_GET['dailydate_start'], 'Y-m-d');
  //   $month_date_end = validateDate($_GET['dailydate_end'], 'Y-m-d');

  //   if ($month_date_start AND $month_date_end) {
  //     $month_date_start = $_GET['dailydate_start'];
  //     $month_date_end = $_GET['dailydate_end'];
  //   } else {
  //     $month_date_start = gmdate('Y-m-01',time() + -4*3600);
  //     $month_date_end = date('Y-m-d',strtotime("$month_date_start +1 month -1 day"));
  //   }
  // } else {
  //   // 這個月第一天和最後一天
  //   $month_date_start = gmdate('Y-m-01',time() + -4*3600);
  //   $month_date_end = date('Y-m-d',strtotime("$month_date_start +1 month -1 day"));
  // }




  // ------------------------------------------------------------------
  // 取時間範圍
  // ------------------------------------------------------------------

  // 取得今年第一天與最後一天(美東)
  $summary_start_day = gmdate('Y-01-01',time() + -4*3600);
  $summary_end_day = date('Y-m-d', strtotime("$summary_start_day +1 year -1 day"));


  // ------------------------------------------------------------------
  // 時間範圍內總計資料表(total)
  // ------------------------------------------------------------------

  // $time_limit_summary_sum_table_colname_html = '
	// <tr>
	// 	<th class="info text-center">時間範圍內分潤總筆數</th>
	// 	<th class="info text-center">時間範圍內分潤總計</th>
	// </tr>
	// ';

  // // 表格欄位名稱
	// $summary_sum_table_colname_html = '
	// <tr>
	// 	<th class="info">第一代分潤總筆數</th>
	// 	<th class="info">第一代分潤總計</th>
	// 	<th class="info">第二代分潤總筆數</th>
	// 	<th class="info">第二代分潤總計</th>
  //   <th class="info">第三代分潤總筆數</th>
	// 	<th class="info">第三代分潤總計</th>
  //   <th class="info">第四代分潤總筆數</th>
	// 	<th class="info">第四代分潤總計</th>
	// </tr>
	// ';


  // $summary_member_bonusamount_total_sql = "SELECT SUM(member_profitamount_count_1) AS member_profitamount_count_1, SUM(member_profitamount_count_2) AS member_profitamount_count_2, SUM(member_profitamount_count_3) AS member_profitamount_count_3, SUM(member_profitamount_count_4) AS member_profitamount_count_4, SUM(member_profitamount_1) AS member_profitamount_1, SUM(member_profitamount_2) AS member_profitamount_2, SUM(member_profitamount_3) AS member_profitamount_3, SUM(member_profitamount_4) AS member_profitamount_4 FROM root_statisticsbonusprofit WHERE member_account = '".$_SESSION['member']->account."' AND dailydate_start >= '".$summary_start_day."' AND dailydate_end <= '".$summary_end_day."';";
  // // var_dump($summary_member_bonusamount_total_sql);
  // $summary_member_bonusamount_total_sql_result = runSQLall($summary_member_bonusamount_total_sql,0,'r');
  // // var_dump($summary_member_bonusamount_total_sql_result);

  // if ($summary_member_bonusamount_total_sql_result[0] == 1) {
  //   $member_bonuscount_1_sum = $summary_member_bonusamount_total_sql_result[1]->member_profitamount_count_1;
  //   $member_bonuscount_2_sum = $summary_member_bonusamount_total_sql_result[1]->member_profitamount_count_2;
  //   $member_bonuscount_3_sum = $summary_member_bonusamount_total_sql_result[1]->member_profitamount_count_3;
  //   $member_bonuscount_4_sum = $summary_member_bonusamount_total_sql_result[1]->member_profitamount_count_4;

  //   $member_bonus_1_sum = $summary_member_bonusamount_total_sql_result[1]->member_profitamount_1;
  //   $member_bonus_2_sum = $summary_member_bonusamount_total_sql_result[1]->member_profitamount_2;
  //   $member_bonus_3_sum = $summary_member_bonusamount_total_sql_result[1]->member_profitamount_3;
  //   $member_bonus_4_sum = $summary_member_bonusamount_total_sql_result[1]->member_profitamount_4;

  // // '.$summary_start_day.'~'.$summary_end_day.'

  //   $time_limit_total_bonuscount = round((float)($member_bonuscount_1_sum + $member_bonuscount_2_sum + $member_bonuscount_3_sum + $member_bonuscount_4_sum),2);
  //   $time_limit_total_bonus = round((float)($member_bonus_1_sum + $member_bonus_2_sum + $member_bonus_3_sum + $member_bonus_4_sum),2);

  //   $show_time_limit_summary_sum_html = '
  //   <tr>
  //     <td class="text-center"><span>'.$time_limit_total_bonuscount.'</span></td>
  //     <td class="text-center"><span>'.$time_limit_total_bonus.'</span></td>
  //   </tr>
  //   ';

  //   $show_summary_sum_html = '
  //   <tr>
  //     <td class="text-center"><span>'.$member_bonuscount_1_sum.'</span></td>
  //     <td class="text-center"><span>'.$member_bonus_1_sum.'</span></td>
  //     <td class="text-center"><span>'.$member_bonuscount_2_sum.'</span></td>
  //     <td class="text-center"><span>'.$member_bonus_2_sum.'</span></td>
  //     <td class="text-center"><span>'.$member_bonuscount_3_sum.'</span></td>
  //     <td class="text-center"><span>'.$member_bonus_3_sum.'</span></td>
  //     <td class="text-center"><span>'.$member_bonuscount_4_sum.'</span></td>
  //     <td class="text-center"><span>'.$member_bonus_4_sum.'</span></td>
  //   </tr>
  //   ';
  // } else {
  //   // $show_time_limit_summary_sum_html = '
  //   // <tr>
  //   //   <td></td>
  //   //   <td></td>
  //   // </tr>
  //   // ';

  //   // $show_summary_sum_html = '
  //   // <tr>
  //   //   <td></td>
  //   //   <td></td>
  //   //   <td></td>
  //   //   <td></td>
  //   //   <td></td>
  //   //   <td></td>
  //   //   <td></td>
  //   //   <td></td>
  //   // </tr>
  //   // ';

  //   $show_time_limit_summary_sum_html = '';
  //   $show_summary_sum_html = '';
  // }


  // $time_limit_html = '<span>時間範圍 : '.$summary_start_day.' ~ '.$summary_end_day.'</span><br><br>';

  // $show_summary_sum_list_html = $time_limit_html.'
	// <table class="table table-bordered small">
	// 	<thead>
	// 		'.$time_limit_summary_sum_table_colname_html.'
	// 	</thead>
	// 	<tbody>
	// 		'.$show_time_limit_summary_sum_html.'
	// 	</tbody>
	// </table>
	// ';

  // $show_summary_sum_list_html = $show_summary_sum_list_html.'
	// <table class="table table-bordered small">
	// 	<thead>
	// 		'.$summary_sum_table_colname_html.'
	// 	</thead>
	// 	<tbody>
	// 		'.$show_summary_sum_html.'
	// 	</tbody>
	// </table>
	// <hr>
	// ';


  // ------------------------------------------------------------------
  // 時間範圍內每天的資料(summary)
  // ------------------------------------------------------------------

  // $summary_member_bonusamount_sql = "SELECT * FROM root_dividendreference WHERE member_account = '".$_SESSION['member']->account."' AND dailydate_start >= '".$summary_start_day."' AND dailydate_end <= '".$summary_end_day."' ORDER BY dailydate_start DESC;";
  // $summary_member_bonusamount_sql = "SELECT * FROM root_dividendreference WHERE member_account = '".$_SESSION['member']->account."';";

  // 將兩個合併起來
  $summary_member_bonusamount_sql = "
  SELECT * FROM (SELECT * FROM root_dividendreference WHERE member_account = '".$_SESSION['member']->account."') as root_dividendreference_member
  LEFT JOIN root_dividendreference_setting
  ON root_dividendreference_member.dividendreference_setting_id=root_dividendreference_setting.id; ";
  //var_dump($summary_member_bonusamount_sql);
  $summary_member_bonusamount_sql_result = runSQLall($summary_member_bonusamount_sql,0,'r');
  //var_dump($summary_member_bonusamount_sql_result);



  $summary_listrow_html = '';
  if ($summary_member_bonusamount_sql_result[0] >= 0) {
    for ($i = 1; $i <= $summary_member_bonusamount_sql_result[0]; $i++) {

      // 日期
      $dailydate_range = $summary_member_bonusamount_sql_result[$i]->dailydate_start.'_'.$summary_member_bonusamount_sql_result[$i]->dailydate_end;

      $member_l1_membercount = $summary_member_bonusamount_sql_result[$i]->member_l1_membercount;
      $member_l1_agentcount = $summary_member_bonusamount_sql_result[$i]->member_l1_agentcount;
      $member_l1_agentsum_allbets = $summary_member_bonusamount_sql_result[$i]->member_l1_agentsum_allbets;
      $member_dividend_level = $summary_member_bonusamount_sql_result[$i]->member_dividend_level;
      $member_dividend_assigned = $summary_member_bonusamount_sql_result[$i]->member_dividend_assigned;

      $setted = $summary_member_bonusamount_sql_result[$i]->setted;

      if ($setted == 1) {
        // <td><a href="?current_datepicker='.$dailydate_start.'" title="觀看指定時間區間的內容" target="_top">'.$dailydate_start.'~'.$dailydate_end.'</a></td>
        $text_center_html = 'align="center" valign="center"';
        $summary_listrow_html = $summary_listrow_html.'
        <trclass="">
          <td>'.$dailydate_range.'</td>
          <td '.$text_center_html.'><font>'.$member_l1_membercount.'</font></td>
          <td '.$text_center_html.'><font>'.$member_l1_agentcount.'</font></td>
          <td '.$text_center_html.'><font>'.$member_l1_agentsum_allbets.'</font></td>
          <td '.$text_center_html.'><font>'.$member_dividend_level.'</font></td>
          <td '.$text_center_html.'><font>'.$member_dividend_assigned.'</font></td>
        </tr>
        ';
      }
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
    // </tr>
    // ';
    $summary_listrow_html = '';
  }


  $summary_title = '
  <div style="border-bottom: 1px #555 solid;border-left: 10px #820000 solid;padding: 8px;margin: 5px; font-weight:bold; font-size: 1em; width: 300px;">
  個人股利分紅資訊(不固定週期)
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
        <th>會員第1代的會員人數</th>
        <th>會員第1代的代理商人數</th>
        <th>第1代代理商區間累計投注量</th>
        <th>股利分類等級</th>
        <th>股利分配額</th>
      </tr>
    </thead>
    <tbody>
      '.$summary_listrow_html.'
    </tbody>
  </table>
  <hr>
  ';

  // 點擊時改變 summary 該天整行的顏色
  // $extend_js = $extend_js . "
  // <script>
  //   console.log('".$month_date_start."');
  //   $('#".$month_date_start."').attr('class','success');
  // </script>
  // ";

  // ------------------------------------------------------------------


  // 切成 1 欄版面
  $indexbody_content = '';
  $indexbody_content = $details_btn_html.$indexbody_content.'
  <div class="row">

    <div class="col-12">
      '.$table_summary_html.'
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
