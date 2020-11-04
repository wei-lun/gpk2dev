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
// casino 設定值
require_once dirname(__FILE__) . "/casino/casino_config.php";

// 前台投注紀錄專用檔
require_once dirname(__FILE__) . "/config_betlog.php";

// 每日營收日結報表--專用函式庫
require_once dirname(__FILE__) . "/statistics_daily_report_lib.php";

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

$function_title = $tr['Betting record detail'];
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

if (isset($_GET['d']) AND isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T') {
	$day = filter_var($_GET['d'], FILTER_SANITIZE_STRING);
	$casinoid = 'MG';
	if (isset($_GET['cid'])) {
		$cid = filter_var($_GET['cid'], FILTER_SANITIZE_STRING);
		$check_casino_state_sql = 'SELECT * from casino_list WHERE casinoid = \'' . $cid . '\' AND open=\'1\'';
		$check_casino_state_result = runSQLall($check_casino_state_sql);
		if ($check_casino_state_result['0'] > '0') {
			$casinoid = $check_casino_state_result['1']->casinoid;
		}
	}
} else {
	// $tr['Illegal test'] = '(x)不合法的測試。';
	die($tr['Illegal test']);
}
//var_dump($_GET['d']);

// get member's casino acount
$check_casino_state_sql = 'SELECT * from casino_list WHERE casinoid = \'' . $casinoid . '\' AND open=\'1\'';
$check_casino_state_result = runSQLall($check_casino_state_sql);
if ($check_casino_state_result['0'] > '0') {
	$casinoaccount = $check_casino_state_result['1']->account_column;
}

// 有登入才顯示。但是不能為試用帳號。therole = 'T'
if (isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T') {

//	$back_betrecord = '';
	// $tr['Return betting record'] = '返回投注紀錄';
	$show_transaction_list_html = '<input class="btn btn-primary" type="button" value="' . $tr['Return betting record'] . '" onclick="location.href=\'betrecord.php\'"><br><br>';

	$casino_state_sql = 'SELECT * from casino_list WHERE open=1';
	$casino_state_result = runSQLall($casino_state_sql);
	if ($casino_state_result['0'] > 1) {
		for ($i = 1; $i <= $casino_state_result['0']; $i++) {
			$btn_casinoid = $casino_state_result[$i]->casinoid;
			$btn_class = 'success';
			$btn_display = '';
			if ($btn_casinoid == $casinoid) {
				$btn_class = 'danger';
				$btn_display = 'disabled';
			}
			$show_transaction_list_html = $show_transaction_list_html . '<input class="btn btn-' . $btn_class . '" type="button" value="' . $btn_casinoid . '" onclick="location.href=\'betrecord_deltail.php?d=' . $day . '&cid=' . $btn_casinoid . '\'" ' . $btn_display . '>';
		}
		$show_transaction_list_html = $show_transaction_list_html . '<br><br>';
	}
	// $tr['Due to daily statements and note the generation of time difference, the day of betting and betting summary of the results of the drop is a normal phenomenon'] = '* 因日報表及注單生成時間差異，當日投注紀錄與投注明細加總結果有落差屬正常現象。';
	//	$show_tips_html = '';
	$show_tips_html = '
	<div class="alert alert-success">
		<p>' . $tr['Due to daily statements and note the generation of time difference, the day of betting and betting summary of the results of the drop is a normal phenomenon'] . '</p>
 	</div>';

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
		<th class="info">' . $tr['betting time'] . '</th>
		<th class="info">' . $tr['Betting count'] . '</th>
		<th class="info">' . $tr['Total bet amount'] . '</th>
		<th class="info">' . $tr['Total lottery results'] . '</th>
		<th class="info">' . $tr['Total profit and loss results'] . '</th>
	</tr>';

	//var_dump($day);
	//var_dump($_SESSION['member']->mg_account);
	$bettingrecords_sum = $casino_bettingrecords[$casinoid]($_SESSION['member']->$casinoaccount, $day);
	//	var_dump($bettingrecords_sum);

	if ($bettingrecords_sum['accountnumber_count'] == 0) {
		// 注單量
		$gamble_count = 0;
		// 投注量
		$all_bets = 0;
		// 派彩
		$all_wins = 0;
		// 損益
		$all_profitlost_result = 0;
	} else {
		// 注單量
		$gamble_count = $bettingrecords_sum['accountnumber_count'];
		// 投注量
		$all_bets = $bettingrecords_sum['TotalWager'] / 100;
		// 派彩
		$all_wins = $bettingrecords_sum['TotalPayout'] / 100;
		// 損益
		// 此次勝負差額, 100 對應 1 CNY , 所以差額需要除以 100
		$all_profitlost_result = round((float) ($all_wins - $all_bets), 2);
	}

//	$show_transaction_list_html = $show_transaction_list_html . '<button type="button" class="btn btn-warning">條件：美東時間 '.$day.' 內的投注紀錄('.$gamble_count.'筆)</button><br><br>';

	if ($all_profitlost_result >= 0) {
		$difference_payout_style = 'color: blue;';
	} else {
		$difference_payout_style = 'color: red;';
	}

	$show_transaction_sum_list_html = '';
	$show_transaction_sum_list_html = $show_transaction_sum_list_html . '
	<tr>
		<td class="text-left"><span>' . $day . '</span></td>
		<td class="text-left"><span>' . $gamble_count . '</span></td>
		<td class="text-left"><span>' . $all_bets . '</span></td>
		<td class="text-left"><span>' . $all_wins . '</span></td>
		<td class="text-left"><span style="' . $difference_payout_style . '">' . $all_profitlost_result . '</span></td>
	</tr>
	';

	$show_transaction_list_html = $show_transaction_list_html . $show_tips_html . '
	<table id="inbox_transaction_list" class="table table-bordered">
		<thead>
			' . $deltail_sum_table_colname_html . '
		</thead>
		<tbody>
			' . $show_transaction_sum_list_html . '
		</tbody>
	</table>
	<hr>
	';

	// ----------------------------------------------------------------------------
	// 投注明細總和 start
	// ----------------------------------------------------------------------------

	// ----------------------------------------------------------------------------
	// 投注明細 start
	// ----------------------------------------------------------------------------
	require_once dirname(__FILE__) . $casino_lib[$casinoid];
	$show_transaction_listrow_html = $casino_betlogdetail[$casinoid]($_SESSION['member']->$casinoaccount, $day);

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
			<th>' . $tr['betting time'] . '</th>
			<th>' . $tr['game name'] . '</th>
			<th>' . $tr['Betting number'] . '</th>
			<th>' . $tr['Game classification'] . '</th>
			<th>' . $tr['Betting'] . '</th>
			<th>' . $tr['Profit amount'] . '</th>
			<th>' . $tr['Profit and loss'] . '</th>
			<th>' . $tr['currency'] . '</th>
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
		<tbody>
		' . $show_transaction_listrow_html . '
		</tbody>
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
		        "paging":   true,
		        "ordering": true,
		        "info":     true,
						"order": [[ 0, "desc" ]],
						"pageLength": 120
				} );
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
