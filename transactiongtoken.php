<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- GTOKEN 交易紀錄查詢 此程式顯示會員的交易內容。
// File Name:	transactiongtoken.php
// Author:		Barkley
// Related:   wallets.php
// Log:
/*
前台
會寫入root_member_gtokenpassbook DB ,
withdrawapplication_action.php 代币(GTOKEN)线上取款前台動作
*/
// ----------------------------------------------------------------------------



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
//現金帳戶交易紀錄
$function_title = $tr['transactiongtoken title'] ;
// 擴充 head 內的 css or js
$extend_head				= '';
// 放在結尾的 js
$extend_js					= '';
// body 內的主要內容
$indexbody_content	= '';
// 系統訊息選單
$messages 					= '';
// 初始化變數 end
// ----------------------------------------------------------------------------
// 導覽列
$navigational_hierarchy_html = '
<ul class="breadcrumb">
  <li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
  <li><a href="member.php">'.$tr['Member Centre'].'</a></li>
  <li><a href="wallets.php">'.$tr['wallets title'].'</a></li>
  <li class="active">'.$function_title.'</li>
</ul>
<style>
	/*暫時*/
	#show_list td{ color:#000;}
</style>
';
// ----------------------------------------------------------------------------


/*
<button type="button" class="btn btn-success">自訂查詢</button>
<input type="text" class="form-control" id="transaction_begin" placeholder="開始時間">
<input type="text" class="form-control" id="transaction_end" placeholder="結束時間">
<button type="submit" id="submit_search" class="btn btn-default">搜尋</button>
*/
$show_transaction_condition_html = '';
//快速查詢 1天內 7天內 30天內 90天內
$show_transaction_condition_html = '
<p>
  <div class="form-inline">
  	<a href="#" class="btn btn-info" role="button">'.$tr['quick search'].'</a>
  	<a href="?t=1" class="btn btn-default" role="button">'.$tr['within 1 days'].'</a>
  	<a href="?t=7" class="btn btn-default" role="button">'.$tr['within 7 days'].'</a>
  	<a href="?t=30" class="btn btn-default" role="button">'.$tr['within 30 days'] .'</a>
  	<a href="?t=90" class="btn btn-default" role="button">'.$tr['within 90 days'].'</a>
  </div>
</p><hr>
';

//搜尋交易紀錄的條件
// ref: https://www.postgresql.org/docs/9.1/static/functions-datetime.html
if(isset($_GET['t'])) {
	$t = $_GET['t'];
	if($t == 1) {
		// 1 天  (transaction_time >= (current_timestamp - interval '1 days')
		$sql_timerange 	= " (transaction_time >= (current_timestamp - interval '24 hours')) ";
	}elseif($t == 7){
		// 7 天
		$sql_timerange 	= " (transaction_time >= (current_timestamp - interval '7 days')) ";
	}elseif($t == 30){
		// 30 天
		$sql_timerange 	= " (transaction_time >= (current_timestamp - interval '30 days')) ";
	}elseif($t == 90){
		// 90 天
		$sql_timerange 	= " (transaction_time >= (current_timestamp - interval '90 days')) ";
 	}else{
		// default
		$sql_timerange 	= " (transaction_time >= (current_timestamp - interval '24 hours')) ";
  	}
}else{
	// 預設顯示 24 hr
	$sql_timerange 	= " (transaction_time >= (current_timestamp - interval '24 hours')) ";
	$t=1;
}

function get_tab_botton($days){
    global $tr;
    $btn_style='btn-default';
    if (isset($_GET['t']) && $_GET['t']==$days) $btn_style='btn-primary';
    $button_name=$tr['within '.$days.' days']?? $days.'天內';
    return <<<HTML
    <a href="?t=$days" class="btn $btn_style m-1" role="button">$button_name</a>
HTML;
}

 $show_transaction_condition_html = '
    <p>
      <div class="form-inline">
        <a href="#" class="btn btn-info m-1" role="button">'.$tr['quick search'].'</a>';

foreach ([1,7,30,90] as $days){
    $show_transaction_condition_html .=get_tab_botton($days);
}
$show_transaction_condition_html.='</div>
    </p><hr>
    ';

function check_payout($payout){
    if($payout>=0){return '<font color="red">$'.number_format($payout,2).'</font>';}
    else{return '<font color="green"> - $'.abs(number_format($payout,2)).'</font>';}
 }


// 有登入，有錢包才顯示。
if(isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T') {
// --------------------

	// 使用者所在的時區，sql 依據所在時區顯示 time
	// -------------------------------------
	if(isset($_SESSION['agent']->timezone) AND $_SESSION['agent']->timezone != NULL) {
		$tz = $_SESSION['agent']->timezone;
	}else{
		$tz = '+08';
	}
	// 轉換時區所要用的 sql timezone 參數
	// $tzsql = "select * from pg_timezone_names where name like '%posix/Etc/GMT%' AND abbrev = '".$tz."'";
	// $tzone = runSQLALL($tzsql);
	// // var_dump($tzone);
	// if($tzone[0]==1){
	// 	$tzonename = $tzone[1]->name;
	// }else{
        $tzonename = 'AST';
		// $tzonename = 'posix/Etc/GMT-8';
	// }
	// to_char((transaction_time AT TIME ZONE '$tzonename'),'YYYY-MM-DD') as transaction_time_tz


	// 依據 ID 列出對應的帳號資料
	// -------------------------------------

  // 預設為登入的會員 id
  $account_id = $_SESSION['member']->id;

  // 將 ID 對應的 account , 順便取出所有的 account 資料
	$account_sql = "SELECT *,enrollmentdate FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.id = '".$account_id."';";
	$account_result = runSQLall($account_sql);
  // var_dump($account_result);die();
	if($account_result[0] == 1) {
		$queryaccount = $account_result[1]->account;
    $gtoken_balance = money_format('%i', $account_result[1]->gtoken_balance);
	}else{
		$queryaccount = $_SESSION['agent']->account;
	}

  // SQL
	$list_sql = "SELECT *, to_char((transaction_time AT TIME ZONE '$tzonename'),'YYYY-MM-DD HH24:MI:SS') as transaction_time_tz ,
                CASE WHEN transaction_category='tokenpay' THEN (deposit-withdrawal) END AS payout
                FROM root_member_gtokenpassbook 
                WHERE source_transferaccount = '$queryaccount' AND $sql_timerange ORDER BY id; ";
	// echo $list_sql;die();

	$list = runSQLall($list_sql);
	// var_dump($list);die();

	$show_listrow_html = '';

	if($list[0] >= 1) {

		// 列出資料每一行 for loop
		for($i=1;$i<=$list[0];$i++) {

		//MEGA > GPK 更名
			if($list[$i]->summary == 'MEGA游戏币派彩'){
				$list[$i]->summary = 'GPK游戏币派彩';
			}

    // 提款金额
	$balance_html = money_format('%i', $list[$i]->balance);
    $auditmode_sel = strtolower($list[$i]->auditmode);
    $auditmodeselect_html = $auditmode_select[$auditmode_sel];

    $auditmodeamount_html = money_format('%i', $list[$i]->auditmodeamount);
    $withdrawal_html = $list[$i]->withdrawal.'<p hidden>('.$auditmodeselect_html.')('.$auditmodeamount_html.')</p>';
    $summary_html = $list[$i]->summary.'<p hidden>'.$list[$i]->system_note.'</p>';
    $payout_addmoney=isset($list[$i]->payout)?check_payout($list[$i]->payout):'';

			// 表格 row
			$show_listrow_html = $show_listrow_html.'
			<tr>
				<td align="right">'.$list[$i]->transaction_time_tz.'</td>
				<td align="right">$'.$list[$i]->deposit.'</td>
				<td align="right">$'.$withdrawal_html.'</td>
                <td align="right">'.$payout_addmoney.'</td>
				<td align="right">'.$summary_html.'</td>
				<td align="right">$'.$list[$i]->balance.'</td>
			</tr>
			';
		}
		// 列出資料每一行 for loop -- end
	}

	// 表格欄位名稱
  //單號 交易時間 存款金額 提款金額 摘要 餘額 稽核方式 稽核金額 備註
	$table_colname_html = '
	<tr>
		<th>'.$tr['transcation time'].'  (EST)</th>
		<th>'.$tr['deposit amount'].'</th>
		<th>'.$tr['withdrawal amount'].'</th>
        <th>派彩</th>
		<th>'.$tr['summary'].'</th>
		<th>'.$tr['Balance'].'</th>
	</tr>
	';
  //<th>'.$tr['audit method'].'</th>
  //<th>'.$tr['audit amount'].'</th>
  // skip		<th>'.$tr['remark'].'</th>


  // 顯示這次查詢的使用者帳號
  $show_account_html = '<strong>'.$tr['account colon'].'</strong><span class="label label-primary">'.$queryaccount.'</span>';

  //顯示這次查詢的使用者帳戶餘額
  $show_balance_html = '<strong>'.$tr['GTOKEN balance'].'</strong><span class="label label-success" align="right">'.$gtoken_balance.'</span>';

  $show_account_balance_html =
  '<p>
      <div class="row mb-4">
        <div class="col-12 col-md-7">'.$show_account_html.'</div>
        <div class="col-12 col-md-5">'.$show_balance_html.'</div>
      </div>
    </p>';

	// enable sort table 啟用可排序的表格
	$sorttablecss = ' id="show_list"  class="display" cellspacing="0" width="100%" ';
	//$sorttablecss = ' class="table table-striped" ';
  //1. 現金可使用于娱乐城游戏使用
  //2. 現金可以申请提领，但需要满足稽核条件(存款后的投注量需要大于稽核金额总和)，如没有满足需收取行政费用 50% 。
  //3. 現金可以于钱包设定中，自动由加盟金储值現金。
  /*
	$show_tips_html = '<div class="alert alert-success">
	'.$tr['transcation token tips 1'].'<br>
	'.$tr['transcation token tips 2'].'<br>
	'.$tr['transcation token tips 3'].'<br>
	</div>';
  */

	// 列出資料, 主表格架構
	$show_list_html = $show_transaction_condition_html.$show_account_balance_html;
	$show_list_html = $show_list_html.'
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
    <style type="text/css">
        #show_list { text-align:right; }
    </style>
	';

	// DATA tables jquery plugging -- 要放在 head 內 不可以放 body
	$extend_head = $extend_head.'
	<script type="text/javascript" language="javascript" class="init">
		$(document).ready(function() {
			$("#show_list").DataTable( {
          "searching": false,
					"paging":   true,
					"ordering": true,
          "order": [[ 0, "desc" ], [ 1, "desc" ]],
					"info":     true
			} );
		} )
	</script>
	';


	// 切成 1 欄版面
	$indexbody_content = '';
	$indexbody_content = $indexbody_content.'
	<div class="row">
		<div class="col-12">
		'.$show_list_html.'
		</div>
	</div>
	<br>
	<div class="row">
		<div id="preview_result"></div>
	</div>
	';
// --------------------
}else{
// --------------------
	// 搜尋條件
	$show_transaction_condition_html = '';
	// 列出資料
	if(isset($_SESSION['member']) AND $_SESSION['member']->therole == 'T') {
		$show_transaction_list_html = '試用帳號，請先登出再以會員登入使用。';
	}else{
		$show_transaction_list_html = '會員請先登入。';
	}

	// 切成 1 欄版面
	$indexbody_content = '';
	$indexbody_content = $indexbody_content.'
	<div class="row">
	  <div class="col-12">
	  '.$show_transaction_list_html.'
	  </div>
	</div>
	<br>
	<div class="row">
		<div id="preview_result"></div>
	</div>
	';
// --------------------
}



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
