<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- GCASH 交易紀錄查詢
// File Name:	transactiongcash.php
// Author:		Barkley
// Related:   wallets.php
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
//加盟金帳戶交易紀錄
$function_title = $tr['transactiongcash title'] ;
//$tr['transactiongcash title'];
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
';
// ----------------------------------------------------------------------------






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


function get_tab_button($days) {
	global $tr;
	$btn_style = 'btn-default';
	if(isset($_GET['t']) && $_GET['t'] == $days) $btn_style = 'btn-primary';
	$button_name = $tr['within ' . $days . ' days'] ?? $days . '天內';
	return <<<HTML
	<a href="?t=$days" class="btn $btn_style m-1" role="button">$button_name</a>
HTML;
}

$show_transaction_condition_html = '';
// 自訂搜尋 + 快速查詢
//快速查詢 1天內 7天內 30天內 90天內
$show_transaction_condition_html = '
<p>
  <div class="form-inline">
  	<a href="#" class="btn btn-info m-1" role="button">'.$tr['quick search'].'</a>';
 
foreach ([1, 7, 30, 90] as $days) {

	$show_transaction_condition_html .= get_tab_button($days);
}

  	
 $show_transaction_condition_html .= '</div>
</p><hr>
';

/*
<button type="button" class="btn btn-success">自訂查詢</button>
<input type="text" class="form-control" id="transaction_begin" placeholder="開始時間">
<input type="text" class="form-control" id="transaction_end" placeholder="結束時間">
<button type="submit" id="submit_search" class="btn btn-default">搜尋</button>
*/


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
	$tzsql = "select * from pg_timezone_names where name like '%posix/Etc/GMT%' AND abbrev = '".$tz."'";
	$tzone = runSQLALL($tzsql);
	// var_dump($tzone);
	if($tzone[0]==1){
		$tzonename = $tzone[1]->name;
	}else{
		$tzonename = 'posix/Etc/GMT-8';
	}
	// to_char((transaction_time AT TIME ZONE '$tzonename'),'YYYY-MM-DD') as transaction_time_tz


	// 依據 ID 列出對應的帳號資料
	// -------------------------------------

  // 預設為登入的會員 id
  $account_id = $_SESSION['member']->id;

  // 將 ID 對應的 account , 順便取出所有的 account 資料
	$account_sql = "SELECT *,enrollmentdate FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.id = '".$account_id."';";
	$account_result = runSQLall($account_sql);
  // var_dump($account_result);
	if($account_result[0] == 1) {
		$account = $account_result[1]->account;
    $gcash_balance = money_format('%i', $account_result[1]->gcash_balance);
	}else{
		$account = NULL;
	}

	// SQL
	$list_sql = "SELECT *, to_char((transaction_time AT TIME ZONE '$tzonename'),'YYYY-MM-DD HH24:MI:SS') as transaction_time_tz FROM root_member_gcashpassbook WHERE source_transferaccount = '$account' AND  $sql_timerange ORDER BY id DESC; ";
	// echo $list_sql;

	$list = runSQLall($list_sql);
	// var_dump($list);

	$show_listrow_html = '';

	if($list[0] >= 1) {

		// 列出資料每一行 for loop
		for($i=1;$i<=$list[0];$i++) {

    // summary + 不显示的 system note
    $summary_html = $list[$i]->summary.'<p hidden>'.$list[$i]->system_note.'</p>';
    //人名幣格式輸出 ,
		$balance_html = money_format('%i', $list[$i]->balance) ;

			// 表格 row
			$show_listrow_html = $show_listrow_html.'
			<tr>
				<td>'.$list[$i]->transaction_time_tz.'</td>
				<td align="right">$'.$list[$i]->deposit.'</td>
				<td align="right">$'.$list[$i]->withdrawal.'</td>
				<td>'.$summary_html.'</td>
				<td align="right">$'.$list[$i]->balance.'</td>
			</tr>
			';
		}
		// 列出資料每一行 for loop -- end
	}

	// 表格欄位名稱
  //單號 交易時間 存款金額 提款金額 摘要 餘額 備註
	$table_colname_html = '
	<tr>
		<th>'.$tr['transcation time'].'</th>
		<th>'.$tr['deposit amount'].'</th>
		<th>'.$tr['withdrawal amount'].'</th>
		<th>'.$tr['summary'].'</th>
		<th>'.$tr['Balance'].'</th>
	</tr>
	';
  // <th>'.$tr['remark'].'</th> 移除备注栏位

	// 顯示這次查詢的使用者帳號
	$show_account_html = '<strong>'.$tr['account colon'].'</strong><span class="label label-primary">'.$account.'</span>';

  //顯示這次查詢的使用者帳戶餘額
  $show_balance_html = '<strong>'.$tr['GCASH balance'].'</strong><span class="label label-success" align="right">'.$gcash_balance.'</span>';

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
  //1. 現金(GCASH) 無須稽核，可以轉帳給代理商下的會員帳號。
  //2. 現金(GCASH) 可以直接使用於代理商申請入會，及使用於電子商務精品網支付(網站籌備中)。
  //3. 現金(GCASH)  可以設定自動加值到娛樂城代幣(GTOKEN)，即可前往娛樂城娛樂。
  // 不显示, 还没想到说明内容的细节
  /*
	$show_tips_html = '<div class="alert alert-success">
	'.$tr['transcation cash tips 1'].'<br>
	'.$tr['transcation cash tips 2'].'<br>
	'.$tr['transcation cash tips 3'].'<br>
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
	';

	// DATA tables jquery plugging -- 要放在 head 內 不可以放 body
	$extend_head = $extend_head.'
	<script type="text/javascript" language="javascript" class="init">
		$(document).ready(function() {
			$("#show_list").DataTable( {
          "searching": false,
					"paging":   true,
					"ordering": true,
					"info":     true,
          order: [[ 0, "desc" ], [ 0, "desc" ]]
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
    //試用帳號，請先登出再以會員登入使用。
		$show_transaction_list_html = $tr['trail use member first'];
	}else{
    //會員請先登入。
		$show_transaction_list_html = $tr['member login first'];
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
