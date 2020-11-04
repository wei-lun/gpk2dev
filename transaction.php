<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 交易紀錄查詢, 查詢帳戶的異動狀況
// File Name:	Transaction.php
// Author:		Barkley
// Related:
// Log:
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
$function_title = '交易紀錄查詢';
// 擴充 head 內的 css or js
$extend_head				= '';
// 放在結尾的 js
$extend_js					= '';
// body 內的主要內容
$indexbody_content	= '';
// 系統訊息選單
$messages 					= '';





//搜尋交易紀錄的條件
$show_transaction_condition_html = '';
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

// 自訂搜尋 + 快速查詢
$show_transaction_condition_html = $show_transaction_condition_html.'
<div class="form-inline">
	<a href="#" class="btn btn-info" role="button">快速查詢</a>
	<a href="?t=1" class="btn btn-default" role="button">1天內</a>
	<a href="?t=7" class="btn btn-default" role="button">7天內</a>
	<a href="?t=30" class="btn btn-default" role="button">30天內</a>
	<a href="?t=90" class="btn btn-default" role="button">90天內</a>
</div>
';

/*
<button type="button" class="btn btn-success">自訂查詢</button>
<input type="text" class="form-control" id="transaction_begin" placeholder="開始時間">
<input type="text" class="form-control" id="transaction_end" placeholder="結束時間">
<button type="submit" id="submit_search" class="btn btn-default">搜尋</button>
*/


// 有登入，有錢包才顯示。
if(isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T') {

	// max show records
	$sql_load_limit = ' LIMIT 1000';
	// $tran_list_sql = "SELECT id, transaction_time, to_char((transaction_time AT TIME ZONE 'AST')  , 'YYYY-MM-DD  HH24:MI:SS' )  as est_transaction_time ,deposit, withdrawal , system_note, summary, balance  FROM root_memberdepositpassbook WHERE member_id = '".$_SESSION['member']->id."'  ORDER BY est_transaction_time DESC LIMIT 10;";
	// $tran_list_sql = "SELECT id,currency,source_transferaccount,destination_transferaccount, transaction_time, to_char((transaction_time AT TIME ZONE 'CCT')  , 'YYYY-MM-DD  HH24:MI:SS' )  as cct_transaction_time, to_char((transaction_time AT TIME ZONE 'AST')  , 'YYYY-MM-DD  HH24:MI:SS' )  as est_transaction_time ,deposit, withdrawal , system_note, summary, balance  FROM root_memberdepositpassbook WHERE member_id = '".$_SESSION['member']->id."' AND $sql_timerange ORDER BY est_transaction_time DESC $sql_load_limit;";
	$tran_list_sql = "SELECT id,currency,source_transferaccount,destination_transferaccount, transaction_time, to_char((transaction_time AT TIME ZONE 'CCT')  , 'YYYY-MM-DD  HH24:MI:SS' )  as cct_transaction_time, to_char((transaction_time AT TIME ZONE 'AST')  , 'YYYY-MM-DD  HH24:MI:SS' )  as est_transaction_time ,deposit, withdrawal , system_note, summary, balance  FROM root_memberdepositpassbook WHERE (member_id = '".$_SESSION['member']->id."' OR source_transferaccount = '".$_SESSION['member']->account."') AND $sql_timerange ORDER BY est_transaction_time DESC $sql_load_limit;";
	// echo $tran_list_sql;
	$list = runSQLall($tran_list_sql);
	// 資料數量
	$list_count = $list[0];

	$show_transaction_listrow_html = '';
	$show_transaction_list_html = '<button type="button" class="btn btn-warning">條件：'.$t.' 天內的交易紀錄('.$list_count.'筆)</button>';
	// var_dump($list_result);
	if($list[0] >= 1) {

		// 列出資料每一行 for loop

		for($i=1;$i<=$list[0];$i++) {

			$show_transaction_listrow_html = $show_transaction_listrow_html.'
			<tr>
			  <td><a href="" title="'.$list[$i]->cct_transaction_time.'">'.$list[$i]->est_transaction_time.'</a></td>
				<td>'.$list[$i]->summary.'</td>
				<td>'.$list[$i]->currency.'</td>
				<td>'.$list[$i]->withdrawal.'</td>
				<td>'.$list[$i]->deposit.'</td>
				<td>'.$list[$i]->balance.'</td>
				<td>'.$list[$i]->system_note.'</td>
				<td>'.$list[$i]->source_transferaccount.' <span class="glyphicon glyphicon-transfer" aria-hidden="true"></span> '.$list[$i]->destination_transferaccount.'</td>
			</tr>
			';

		}
		// 列出資料每一行 for looop -- end


		/*
		// 因為要配合 datepicker 所以先不做
		$show_transaction_list_html = $show_transaction_list_html.'
		<div class="info">
			<p>自訂查詢：(可查詢近三個月明細，每次查詢區間最長為三個月)
		</div>
		';
		*/

		// 列出資料, 主表格架構
		$show_transaction_list_html = $show_transaction_list_html.'
		<table id="transaction_list" class="table table-striped" cellspacing="0" width="100%">
		<thead>
			<tr>
			  <th>交易時間(<a href="#" title="連結內的時間為北京時間">美東時間</a>)</th>
				<th>摘要</th>
				<th>幣別</th>
				<th>支出</th>
				<th>存入</th>
				<th>結餘</th>
				<th>備註</th>
				<th>轉出入帳號</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>交易時間(<a href="#" title="連結內的時間為北京時間">美東時間</a>)</th>
				<th>摘要</th>
				<th>幣別</th>
				<th>支出</th>
				<th>存入</th>
				<th>結餘</th>
				<th>備註</th>
				<th>轉出入帳號</th>
			</tr>
		</tfoot>
		<tbody>
		'.$show_transaction_listrow_html.'
		</tbody>
		</table>
		';

		// 參考使用 datatables 顯示
		// https://datatables.net/examples/styling/bootstrap.html
		$extend_head = $extend_head.'
		<link rel="stylesheet" type="text/css" href="'.$cdnfullurl_js.'datatables/css/jquery.dataTables.min.css">
		<script type="text/javascript" language="javascript" src="'.$cdnfullurl_js.'datatables/js/jquery.dataTables.min.js"></script>
		<script type="text/javascript" language="javascript" src="'.$cdnfullurl_js.'datatables/js/dataTables.bootstrap.min.js"></script>';

		// DATA tables jquery plugging -- 要放在 head 內 不可以放 body
		$extend_head = $extend_head.'
		<script type="text/javascript" language="javascript" class="init">
			$(document).ready(function() {
				$("#transaction_list").DataTable( {
		        "paging":   true,
		        "ordering": true,
		        "info":     true
		    } );
			} )
		</script>
		';

/*
		// todo 先不做
		// 日期選擇器
		// boostrap 3 datepicker , https://eonasdan.github.io/bootstrap-datetimepicker/
		// https://eonasdan.github.io/bootstrap-datetimepicker/#linked-pickers
		// include moment
		$extend_head = $extend_head.'
		<script src="'.$cdnfullurl_js.'moment-with-locales.js"></script>
		<script src="'.$cdnfullurl_js.'moment-timezone-with-data.js"></script>
		';
*/

	}

}else{
	// 搜尋條件
	$show_transaction_condition_html = '';
	// 列出資料
	if(isset($_SESSION['member']) AND $_SESSION['member']->therole == 'T') {
		$show_transaction_list_html = '試用帳號，請先登出再以會員登入使用。';
	}else{
		$show_transaction_list_html = '會員請先登入。';
	}

}





// 切成 3 欄版面 2:8:2
$indexbody_content = '';
$indexbody_content = $indexbody_content.'
<div class="row">
  <div class="col-12">
  '.$show_transaction_condition_html.'
  </div>
</div>
<br>
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
$tmpl['paneltitle_content'] 			= '<span class="glyphicon glyphicon-bookmark" aria-hidden="true"></span>'.$function_title;
// 主要內容 -- content
$tmpl['panelbody_content']				= $indexbody_content;

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include($config['template_path']."template/admin.tmpl.php");
?>
