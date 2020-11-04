<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 會員投注紀錄查詢
// File Name:	betrecord.php
// Author:		Yuan, Barkley
// Related:
// Log:
// ----------------------------------------------------------------------------
/*
Table:
root_statisticsdailyreport : 每日營收日結報表

File:
betrecord.php : 前台 - 會員投注紀錄查詢
betrecord_deltail.php : 前台 - 投注紀錄明細查詢
statistics_daily_report_lib.php : 前台 - MG CASINO 資料表函式 $mg_account 帶入錢包的 MG帳號
config_betlog.php : 前台 - 抓投注單的專用資料庫用 SQL lib 及參數
 */



// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

// 前台投注紀錄專用檔
require_once dirname(__FILE__) ."/config_betlog.php";

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
//個人投注紀錄
$function_title = $tr['betrecord title'];
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
  <li class="active">'.$function_title.'</li>
</ul>
';
if($config['site_style']=='mobile'){
  $navigational_hierarchy_html =<<<HTML
    <a href="{$config['website_baseurl']}menu_admin.php"><i class="fas fa-chevron-left"></i></a>
    <span>$function_title</span>
    <i></i>
HTML;
}
// ----------------------------------------------------------------------------
// 捞取会员个人本月投注纪录合计的表单
// $today 今天日期
// $first_day 开始第一天
// $settlement_date
// $settlement_date_next_day
function get_betrecord_table_html($today, $first_day, $settlement_date, $settlement_date_next_day)
{
	global $rule;
	global $tr;

  $total_all_bets = 0;
	$show_list_html = '';
	// 加上 memcached 加速,宣告使用 memcached 物件 , 連接看看. 不能連就停止.
	$memcache = new Memcached();
	$memcache->addServer('localhost', 11211) or die ("Could not connect memcache server !! ");
	// 把 query 存成一個 key in memcache
	$key = 'betrecord'.$_SESSION['member']->id.$_SESSION['member']->account;
	$key_alive_show = sha1($key);

	$getfrom_memcache_result = $memcache->get($key_alive_show);

	if(!$getfrom_memcache_result || $getfrom_memcache_result['first_day'] != $first_day) {
    // 每日计算, use 24hr seconds 去减
		for ($i = strtotime($today); $i > strtotime($first_day); $i -= 86400) {
			$day = date("Y-m-d",$i);
			$week = date('l',strtotime($day));

			// 如果為結算日,日期欄位要以紅色標注
			if ($settlement_date == $week) {
				$settlement_date_html = '<span style="color:red;">'.$day.' ('.$week.')</span>';
			} else {
				$settlement_date_html = '<span>'.$day.' ('.$week.')</span>';
			}

			// 取出該會員該日的總投注量.總派彩量, 總損益以總派彩 - 總投注做計算
			// 不取總損益, 因 DB 內寫入的值是以站方角度, 資料顯示是以 user 角度, 輸贏結果不同
			$statistics_daily_report_sql = "SELECT all_bets, all_wins, all_count FROM root_statisticsdailyreport WHERE member_account = '".$_SESSION['member']->account."' AND dailydate = '$day';";
			$statistics_daily_report_sql_result = runSQLall($statistics_daily_report_sql,0,'r');

      if($statistics_daily_report_sql_result[0] == 0){
  			$statistics_daily_report_sql = "SELECT SUM(account_betvalid) as all_bets, SUM(account_profit) as all_wins, SUM(account_betting) as all_count FROM root_statisticsbetting WHERE member_id = '".$_SESSION['member']->id."' AND dailydate = '$day';";
  			$statistics_daily_report_sql_result = runSQLall($statistics_daily_report_sql,0,'r');
      }
			/*
			每日報表已生成才做資料處理
			如果還沒生成會撈不到資料 , 預設該天總量皆為0
			*/
			if ($statistics_daily_report_sql_result[0] >= 1) {
				// 總投注量
				$all_bets = $statistics_daily_report_sql_result[1]->all_bets;
				// 總派彩量
				$all_wins = $statistics_daily_report_sql_result[1]->all_wins;
				// 總損益量 = 總派彩量 - 總投注量
				// $all_profitlost_result = $statistics_daily_report_sql_result[1]->all_wins - $statistics_daily_report_sql_result[1]->all_bets;
				// $all_profitlost_result = round((float)($statistics_daily_report_sql_result[1]->all_wins - $statistics_daily_report_sql_result[1]->all_bets),2);
        $all_profitlost_result = number_format(($statistics_daily_report_sql_result[1]->all_wins - $statistics_daily_report_sql_result[1]->all_bets),2);
        // 注单量
        $all_count = $statistics_daily_report_sql_result[1]->all_count;

				// 如果是今天, 不显示投注量
        if($day == $today ) {
          //投注明細
  				$show_list_html = $show_list_html.'
  				<tr>
  					<td class="text-left">'.$settlement_date_html.'</td>
  					<td class="text-right">-</td>
  					<td class="text-right">-</td>
  					<td class="text-right"><a href="betrecord_deltail.php?d='.$day.'" class="btn btn-success btn-sm">'.$tr['bet detail'].'</a></td>
            <td class="text-right">-</td>
  				</tr>
  				';
        } else {
          //投注明細
  				$show_list_html = $show_list_html.'
  				<tr>
  					<td class="text-left">'.$settlement_date_html.'</td>
  					<td class="text-right">'.$all_bets.'</td>
  					<td class="text-right">'.$all_profitlost_result.'</td>
  					<td class="text-right"><a href="betrecord_deltail.php?d='.$day.'" class="btn btn-success btn-sm">'.$tr['bet detail'].'</a></td>
            <td class="text-right">'.$all_count.'</td>
  				</tr>
  				';
        }
      }
		}

		if ($show_list_html != '') {
			$betrecord['first_day'] = $first_day;
			$betrecord['betrecord_html'] = $show_list_html;
		}

		// save to memcached ref:http://php.net/manual/en/memcached.set.php
		$memcached_timeout = 300;
		$memcache->set($key_alive_show, $betrecord, time()+$memcached_timeout) or die ("Failed to save data at the memcache server");
	} else {
		// 資料有存在記憶體中，直接取得 get from memcached
		$betrecord = $getfrom_memcache_result;
	}

	return $betrecord;
}


// 有登入才顯示。但是不能為試用帳號。therole = 'T'
if(isset($_SESSION['member']) AND ($_SESSION['member']->therole == 'A' OR $_SESSION['member']->therole == 'M') AND $_SESSION['member']->therole != 'T') {
	$daterange_arr = [
		'7' => $tr['within 7 days'],
		'14' => $tr['within 14 days'],
		'21' => $tr['within 21 days'],
		'30' => $tr['within 30 days']
	];

	$days = '7';

	if (isset($_GET['t']) && !empty($_GET['t'])) {
		$days = filter_var($_GET['t'], FILTER_SANITIZE_STRING);
	}

	if (!array_key_exists($days, $daterange_arr)) {
		$msg = '不合法的天數查詢';
		echo '<script>alert("'.$msg.'");location.href="betrecord.php";</script>';
	}

	$show_transaction_list_html = '';
	$show_list_html = '';

  // 總投注合計
  $total_all_bets = 0;
	// 昨日總投注量
	$previous_all_bets = '';

	// 結算日
	$settlement_date = $rule['stats_weekday'];
	// 結算日後一天
	$settlement_date_next_day = date('D',strtotime("$settlement_date +1 day"));
	// $settlement_date_next_day = date('Y-m-d',strtotime("$settlement_date +1 day"));

	$time_calculation_text = ($days == '') ? '-7 days' : '-'.$days.' days';

	// 今日時間
	$today = gmdate('Y-m-d',time() + -4*3600);
	// 取得多少天範圍內的資料 , 時間範圍第一天
	// $first_day = date('Y-m-d', strtotime("$today -1 month"));
	$first_day = date('Y-m-d', strtotime("$today $time_calculation_text"));

	$show_transaction_list_html .= '
	<p>
		<div class="form-inline">
			<div class="form-group">
				<strong>' . $tr['date duration'] . '&nbsp;:&nbsp;</strong>
				<a href="?t=7" class="btn btn-default" role="button">'.$daterange_arr['7'].'</a>
				<a href="?t=14" class="btn btn-default" role="button">'.$daterange_arr['14'].'</a>
				<a href="?t=21" class="btn btn-default" role="button">'.$daterange_arr['21'].'</a>
				<a href="?t=30" class="btn btn-default" role="button">'.$daterange_arr['30'].'</a>
			</div>
		</div>
	</p><hr>
	';

  // 我的投注纪录
  $show_transaction_list_html .= '
  <div class="my_member_title" style="border-bottom: 1px #555 solid;border-left: 10px #820000 solid;padding: 8px;margin: 5px; font-weight:bold; font-size: 1em; width: 300px;">
  '.$tr['betrecord title'].'
  </div>';

  // 提示说明
  $show_transaction_list_html .= '
  <div class="well well-sm">
' . $tr['betrecord search'] . '
  </div>
  ';

	// 表格欄位名稱
  //投注時間(美東時間) 個人總投注 個人總派彩 損益結果 個人總投注累計(週)
	$table_colname_html = '
	<tr>
		<th class="text-left">'.$tr['betting time'].'</th>
		<th class="text-right">'.$tr['personal action'].'</th>
		<th class="text-right">'.$tr['personal win'].'</th>
    <th class="text-right"></th>
    <th class="text-right">' . $tr['Betting count'] . '</th>
	</tr>
	';

  //目前查询的帳戶為：
  $member_account = $_SESSION['member']->account;
	$show_transaction_list_html .= '<p align="left"><span class="label label-default">'.$tr['now account'].$member_account.'</span></p><hr>';

/*
  //紅色標注日期為每周結算日
  //目前系統設定結算日為每周的：
	$show_tips_html = $show_tips_html . '
	<div class="alert alert-success">
		<p>'.$tr['betrecord tips 1'].'</p>
		<p>'.$tr['betrecord tips 2'].$settlement_date.'</p>
  </div>';
*/

	// $show_list_html = $betrecord['betrecord_html'];
  // 投注纪录产生, and cache
	$betrecord_table_html = get_betrecord_table_html($today, $first_day, $settlement_date, $settlement_date_next_day);
	$show_list_html = $betrecord_table_html['betrecord_html'];


	$show_transaction_list_html .= '
	<table id="inbox_transaction_list" class="table table-striped">
		<thead>
			'.$table_colname_html.'
		</thead>
		<tbody>
			'.$show_list_html.'
		</tbody>
	</table>
	';

} else {
	$logger = (isset($_SESSION['member']->therole) AND $_SESSION['member']->therole == 'R') ? $tr['admin can not play'] : $tr['withou login or trail account'];

	// 列出資料
	$show_transaction_list_html =  $logger.login2return_url(0);

}


// 切成 3 欄版面 2:8:2
$indexbody_content .= '
	<div class="row">
		<div class="col-12">
		'.$show_transaction_list_html.'
		</div>
	</div>
	<hr>

	<div class="row">
    <div class="col-12 col-md-6">
      <div id="preview"></div>
      <div id="preview_result"></div>
    </div>
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
$tmpl['paneltitle_content'] 			= $navigational_hierarchy_html;
// 主要內容 -- content
$tmpl['panelbody_content']				= $indexbody_content;

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include($config['template_path']."template/admin.tmpl.php");

?>
