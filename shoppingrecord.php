<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 投注紀錄明細查詢
// File Name:	betrecord_deltail.php
// Author:		Yuan
// Related:		對應 betrecord.php 的投注時間 , 顯示該天的所有投注紀錄明細
// Log:
// ----------------------------------------------------------------------------
/*
Table:
test_mg_bettingrecords : mg投注紀錄

// 資料庫依據不同的條件變換資料庫檔案
$stats_config['mg_bettingrecords_tables'];

File:
betrecord.php : 前台 - 會員投注紀錄查詢
betrecord_deltail.php : 前台 - 投注紀錄明細查詢
statistics_daily_report_lib.php : 前台 - MG CASINO 資料表函式 $mg_account 帶入錢包的 MG帳號
config_betlog.php : 前台 - 抓投注單的專用資料庫用 SQL lib 及參數
 */



// 主機及資料庫設定
require_once dirname(__FILE__) . "/config.php";
// 支援多國語系
require_once dirname(__FILE__) . "/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) . "/lib.php";
// 前台投注紀錄專用檔
require_once dirname(__FILE__) . "/config_betlog.php";


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
// $tr['Betting record detail'] = '投注紀錄明細';

$function_title = $tr['Shopping records'];
// 擴充 head 內的 css or js
$extend_head = '';
// 放在結尾的 js
$extend_js = '';
// body 內的主要內容
$indexbody_content = '';
// 系統訊息選單
$messages = '';
// 初始化變數 end
// ----------------------------------------------------------------------------
// 導覽列
$navigational_hierarchy_html = '
<ul class="breadcrumb">
  <li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
  <li><a href="member.php">' . $tr['Member Centre'] . '</a></li>
  <li><a href="betrecord.php">' . $tr['betrecord title'] . '</a></li>
  <li class="active">' . $function_title . '</li>
</ul>
';
// ----------------------------------------------------------------------------
//var_dump($_GET['d']);

// 有登入才顯示。但是不能為試用帳號。therole = 'T'
if (isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T') {
  $show_transaction_list_html = '';
	// ----------------------------------------------------------------------------
	// 投注明細總和 start
	// ----------------------------------------------------------------------------

	// 表格欄位名稱
	// $tr['betting time'] = '投注時間(美東時間)';
	// $tr['Betting count'] = '注單筆數';
	// $tr['Total bet amount'] = '總投注金額';
	// $tr['Total lottery results'] = '總派彩結果';
	// $tr['Total profit and loss results'] = '總損益結果';
	$deltail_sum_table_colname_html = '
	<tr>
		<th class="info">統計區間</th>
		<th class="info">訂單總筆數</th>
		<th class="info">消費金額</th>
	</tr>';

	$show_transaction_list_html = $show_transaction_list_html.'
	<table class="table table-bordered">
		<thead>
			' . $deltail_sum_table_colname_html . '
		</thead>
		<tbody id="inbox_transaction_list"></tbody>
	</table>
	<hr>
	';

	// ----------------------------------------------------------------------------
	// 投注明細總和 end
	// ----------------------------------------------------------------------------

	// ----------------------------------------------------------------------------
	// 投注明細 start
	// ----------------------------------------------------------------------------
	/*
		// 因為要配合 datepicker 所以先不做
		$show_transaction_list_html = $show_transaction_list_html.'
		<div class="info">
			<p>自訂查詢：(可查詢近三個月明細，每次查詢區間最長為三個月)
		</div>
		';
		*/

	// 表格欄位名稱
	// $tr['betting time'] = '投注時間(美東時間)';
	// $tr['game name'] = '遊戲名稱';
	// $tr['Betting number'] = '注單編號';
	// $tr['Game classification'] = '遊戲分類';
	// $tr['Betting'] = '投注量';
	// $tr['Profit amount'] = '派彩量';
	// $tr['Profit and loss'] = '損益量';
	// $tr['currency'] = '幣別';
	//
	$table_colname_html = '
		<tr>
      <th>訂單編號</th>
			<th>購買時間(美東時間)</th>
			<th>商品名稱</th>
			<th>商品型號</th>
			<th>商品售價(單價)</th>
			<th>購買數量</th>
			<th>消費金額</th>
			<th>訂單狀態</th>
		</tr>
		';

	// 列出資料, 主表格架構
	$show_transaction_list_html = $show_transaction_list_html . '
		<table id="transaction_list" class="table table-striped" cellspacing="0" width="100%">
		<thead>
		' . $table_colname_html . '
		</thead>
		<tfoot>
		' . $table_colname_html . '
		</tfoot>
		</table>
		';

	// 參考使用 datatables 顯示
	// https://datatables.net/examples/styling/bootstrap.html
	$extend_head = $extend_head . '
		<link rel="stylesheet" type="text/css" href="' . $cdnfullurl_js . 'datatables/css/jquery.dataTables.min.css">
		<script type="text/javascript" language="javascript" src="' . $cdnfullurl_js . 'datatables/js/jquery.dataTables.min.js"></script>
		<script type="text/javascript" language="javascript" src="' . $cdnfullurl_js . 'datatables/js/dataTables.bootstrap.min.js"></script>
		<script type="text/javascript" language="javascript" src="//cdn.datatables.net/plug-ins/1.10.12/api/sum().js"></script>
		';

	// 即時計算投注派冊差額，並顯示於表格 footer 內。start in 0
	// ref: https://datatables.net/reference/option/pageLength
	// ref: http://stackoverflow.com/questions/32962506/how-to-sum-of-some-rows-in-datatable-using-footercallback
	$extend_head = $extend_head . '
		<script type="text/javascript" language="javascript" class="init">
			$(document).ready(function() {
				$("#transaction_list").DataTable( {
            "bProcessing": true,
            "bServerSide": true,
            "bRetrieve": true,
            "searching": false,
            "aaSorting": [[ 1, "desc" ]],
            "ajax": "shoppingrecord_action.php?a=shoppingrecord",
            "oLanguage": {
              "sSearch": "会员帐号:",
              "sEmptyTable": "目前没有资料!",
              "sLengthMenu": "每页显示 _MENU_ 笔",
              "sZeroRecords": "目前没有资料",
              "sInfo": "目前在第 _PAGE_ 页，共 _PAGES_ 页",
              "sInfoEmpty": "目前没有资料",
              "sInfoFiltered": ""
            },
            "columns": [
              { "data": "rowid"},
              { "data": "log_time"},
              { "data": "product_name"},
              { "data": "product_model"},
              { "data": "product_price"},
              { "data": "product_quantity", "orderable": false},
              { "data": "product_price_subtotal"},
              { "data": "order_status", "orderable": false}
              ]
        } );
        $.get("shoppingrecord_action.php?a=shoppingrecord_summary",
          function(result){
            $("#inbox_transaction_list").html(result);
          }, \'json\');
			});
		</script>
		';

	// ----------------------------------------------------------------------------
	// 投注明細 end
	// ----------------------------------------------------------------------------

} else {
	// 列出資料
	$show_transaction_list_html = '會員請先登入。';
	if (isset($_SESSION['member']->therole) AND $_SESSION['member']->therole == 'T') {
		$show_transaction_list_html = '會員才可使用，試用帳號無法使用此功能。';
	} else {
		$show_transaction_list_html = '會員請先登入。';
	}

}

// 切成 3 欄版面 2:8:2
$indexbody_content = '';
$indexbody_content = $indexbody_content . '
<div class="row">
  <div class="col-12">
  ' . $show_transaction_list_html . '
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
$tmpl['html_meta_description'] = $config['companyShortName'];
$tmpl['html_meta_author'] = $config['companyShortName'];
$tmpl['html_meta_title'] = $function_title . '-' . $config['companyShortName'];

// 系統訊息顯示
$tmpl['message'] = $messages;
// 擴充再 head 的內容 可以是 js or css
$tmpl['extend_head'] = $extend_head;
// 擴充於檔案末端的 Javascript
$tmpl['extend_js'] = $extend_js;
// 主要內容 -- title
$tmpl['paneltitle_content'] = $navigational_hierarchy_html;
// 主要內容 -- content
$tmpl['panelbody_content'] = $indexbody_content;

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include $config['template_path'] . "template/admin.tmpl.php";

?>
