<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 代理商專區，轉帳及觀看會員的報表。
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
//加盟聯營股東專區
$function_title = $tr['agencyarea title'];
// 擴充 head 內的 css or js
$extend_head				= '';
// 放在結尾的 js
$extend_js					= '';
// body 內的主要內容
$indexbody_content	= '';
// 系統訊息選單
$messages 					= '';




// 有登入，有錢包才顯示。只有代理商可以進入
if(isset($_SESSION['member']) AND ($_SESSION['member']->therole == 'A' OR $_SESSION['member']->therole == 'R') AND  $_SESSION['member']->therole != 'T') {
// --------------------

// $agent_ra_base64 = base64_encode($_SESSION['member']->account);

  // -------------------------------------------------------------------
  //最上面的選單索引 -- 加盟聯營協助註冊  加盟聯營股東會員轉帳 我的組織 代理收入摘要
  // -------------------------------------------------------------------
  $button_group_menu = '
  <br>
  <div class="btn-group btn-group-justified" role="group" aria-label="">
		<div class="btn-group" role="group">
      <a href="register.php?r='.$_SESSION['member']->account.'" title="'.$tr['go ahead'].$tr['agency register'].'" target="_SELF" class="btn btn-default" role="button">'.$tr['agency register'].'</a>&nbsp;
		</div>
		<div class="btn-group" role="group">
      <a href="agencyarea.php" target="_SELF" title="'.$tr['go ahead'].$tr['agency member tranfer'].'"  class="btn btn-default" role="button">'.$tr['agency member tranfer'].'</a>&nbsp;
		</div>
		<div class="btn-group" role="group">
      <a href="agencyarea_myorganization.php" target="_SELF" title="'.$tr['go ahead'].$tr['my organization'].'" class="btn btn-info" role="button">'.$tr['my organization'].'</a>&nbsp;
		</div>
    <div class="btn-group" role="group">
      <a href="agencyarea_summary.php" target="_SELF" title="'.$tr['go ahead'].$tr['agemcy income summary'].'" class="btn btn-default" role="button">'.$tr['agemcy income summary'].'</a>&nbsp;
		</div>
	</div>
  <hr>
  <br>
  ';
  // -------------------------------------------------------------------


  // -------------------------------------------------------------------
  // 如果是代理商的話，提供會員轉帳的功能(代理商限定)
  // -------------------------------------------------------------------

  //代理商會員轉帳功能 -- 標題
  $agent_depositgcash_html = '
  <div style="border-bottom: 1px #555 solid;border-left: 10px #820000 solid;padding: 8px;margin: 5px; font-weight:bold; font-size: 1em; width: 300px;">
  '.$tr['agent deposit'].'
  </div>';

  // 轉帳來源帳戶
  $deposit_source_account_input_html = '
  <div class="form-group">
    <input type="text" class="form-control" id="deposit_source_account" value="'.$_SESSION['member']->account.'" placeholder="'.$tr['source account'].'" disabled>
  </div>
  ';

  // 轉帳來源帳戶balance
  $deposit_source_account_amount_input_html = '
  <div class="form-group">
    <input type="text" class="form-control" id="deposit_source_account_balance" value="'.$_SESSION['member']->gcash_balance.'" placeholder="'.$tr['source account balance'].'" disabled>
  </div>
  ';

  // button to send
  //立即前往轉帳  代理商可以將自己的 GCASH(加盟金) 轉給其他站內的會員使用。
  //轉帳來源帳號
  //可轉帳餘額(GCASH)
  $deposit_dest_send_html  = '<a href="member_agentdepositgcash.php" class="btn btn-success" id="deposit_dest_account_amount_send">'.$tr['transfer now'].'</button></span>';
  $agent_depositgcash_html	= $agent_depositgcash_html.'<div class="well well-sm">'.$tr['agent deposit desc'].'</div>';
  $agent_depositgcash_html	= $agent_depositgcash_html.'
  <table class="table table-striped">
		<thead>
	  <tr>
			<th>'.$tr['transfer source account'].'</th>
      <th>'.$tr['transferable balance GCASH'].'</th>
      <th></th>
	  </tr>
		</thead>
    <tbody>
	  <tr>
	    <td>'.$deposit_source_account_input_html.'</td>
	    <td>'.$deposit_source_account_amount_input_html.'</td>
      <td>'.$deposit_dest_send_html.'</td>
	  </tr>
		</tbody>
	</table>
  ';


  // -------------------------------------------------------------------
  // 結算日.美東時間日期/星期設定
  // -------------------------------------------------------------------

  // 結算日
  $settlement_date = 'Wed';
  // 今日時間(美東)
  $today = gmdate('Y-m-d',time() + -4*3600);
  $week = date('D',strtotime($today));


  // -------------------------------------------------------------------
  // 代理商組織列表
  // -------------------------------------------------------------------

  //我的組織
  //$agentadmin_html = '<p align="center"><h4><span id="myorganization" class="label label-primary">我的組織</span></h4></p>';
  $agentadmin_html = '
  <div style="border-bottom: 1px #555 solid;border-left: 10px #820000 solid;padding: 8px;margin: 5px; font-weight:bold; font-size: 1em; width: 300px;">
  '.$tr['my organization'].'
  </div>';


//  $agentadmin_html = '代理商組織狀態';

  // 取得結算日前一個禮拜的第一天與最後一天
  if ($week == $settlement_date) {
    $start_day = date('Y-m-d', strtotime("$today -7 day"));
    $end_day = $today;
  } else {
    $end_day = date('Y-m-d', strtotime("next $settlement_date"));
    $start_day = date('Y-m-d', strtotime("$end_day -6 day"));
  }

//營運業績量： 投注量未達營運業績以紅色字體標注 投注日期(美東時間)
  $agentadmin_message_html = '
  <div class="well well-sm">
    <p>* '.$tr['operating performace'].'<strong>'.$rule['amountperformance'].' </strong> '.$tr['operating performace notice'].'</p>
    <p>* '.$tr['bet date'].$start_day.' ~ '.$end_day.'</p>
  </div>
  ';

  // 表格欄位名稱
  //下線第1代 身分 入會時間(UTC+8) 帳號狀態 投注量
  $table_colname_html = '
  <tr>
		<th>ID</th>
		<th>'.$tr['1st downline'].'</th>
		<th>'.$tr['idetntity'].'</th>
		<th>'.$tr['admission time'].'</th>
		<th>'.$tr['account status'].'</th>
		<th>'.$tr['action'].'</th>
	</tr>
  ';

  // 會員結構關係圖
  $member_treemap_btn_html = '<a href="member_treemap.php" class="btn btn-primary" role="button">'.$tr['member structure diagram'].'</a><br><br>';

  // 使用者所在的時區，sql 依據所在時區顯示 time
  if(isset($_SESSION['agent']->timezone) AND $_SESSION['agent']->timezone != NULL) {
    $tz = $_SESSION['agent']->timezone;
  } else {
    $tz = '+08';
  }

  // 轉換時區所要用的 sql timezone 參數
  $tzsql = "select * from pg_timezone_names where name like '%posix/Etc/GMT%' AND abbrev = '".$tz."'";
  $tzone = runSQLALL($tzsql);
  // var_dump($tzone);
  if($tzone[0]==1) {
    $tzonename = $tzone[1]->name;
  } else {
    $tzonename = 'posix/Etc/GMT-8';
  }

  // 取出該代理帳號的下線及相關資訊
  $member_sql = "SELECT id, account, therole, to_char((enrollmentdate AT TIME ZONE '$tzonename'),'YYYY-MM-DD HH24:MI:SS') as enrollmentdate, status FROM root_member WHERE parent_id = '".$_SESSION['member']->id."' ORDER BY id;";
//  var_dump($member_sql);
  $member_sql_result = runSQLall($member_sql);
//  var_dump($member_sql_result);

  $show_listrow_html = '';
  if ($member_sql_result[0] >= 1) {
    for ($i = 1; $i <= $member_sql_result[0]; $i++) {
      // 取出各下線的總投注量
      $statisticsdailyreport_sql = "SELECT SUM (all_bets) as all_bets FROM root_statisticsdailyreport WHERE member_account = '".$member_sql_result[$i]->account."' AND dailydate >= '$start_day' AND dailydate <= '$end_day';";
      // var_dump($statisticsdailyreport_sql);
      $statisticsdailyreport_sql_result = runSQLall($statisticsdailyreport_sql);
//      var_dump($statisticsdailyreport_sql_result);

      if($member_sql_result[$i]->status == 1) {
        //正常
        $status_html = '<span class="label label-success">'.$tr['normal'].'</span>';
      } else if($member_sql_result[$i]->status == 2) {
        //錢包凍結
        $status_html = '<span class="label label-warning">'.$tr['wallet frozen'].'</span>';
      } else {
        //禁用
        $status_html = '<span class="label label-danger">'.$tr['disabled'].'</span>';
      }

      if($member_sql_result[$i]->therole == 'M') {
        //會員
        $therole_html = '<span class="label label-info">'.$tr['member'].'</span>';
      } else if($member_sql_result[$i]->therole == 'A') {
        //聯營股東
        $therole_html = '<span class="label label-success">'.$tr['agent'].'</span>';
      } else {
        //管理員
        $therole_html = '<span class="label label-success">'.$tr['management'].'</span>';
      }

      $all_bets_style = $statisticsdailyreport_sql_result[1]->all_bets > $rule['amountperformance'] ? 'color:green' : 'color:red';

      //轉帳
      $show_listrow_html = $show_listrow_html.'
      <tr>
				<td class="text-left" id="member_id">'.$member_sql_result[$i]->id.'</td>
				<td class="text-left">
          <div class="row">
            <div class="col-md-7">
              '.$member_sql_result[$i]->account.'
            </div>
            <div class="col-md-2">
              <button type="button" class="btn btn-default btn-xs"><a href="member_agentdepositgcash.php?i='.$member_sql_result[$i]->id.'">'.$tr['tranfer'].'</a></button>
            </div>
          </div>
				</td>
				<td class="text-left">'.$therole_html.'</td>
				<td class="text-left">'.$member_sql_result[$i]->enrollmentdate.'</td>
				<td class="text-left">'.$status_html.'</td>
				<td class="text-left"><span style="'.$all_bets_style.';">'.$statisticsdailyreport_sql_result[1]->all_bets.'</td>
			</tr>
      ';
    }

    /*
      <span class="label label-warning"><a href="member_agentdepositgcash.php?i='.$member_sql_result[$i]->id.'">轉帳</a></span>
      <button type="button" class="btn btn-default btn-xs btn3d" value="'.$member_sql_result[$i]->id.'">轉帳</button>
     */

    // 列出資料, 主表格架構
    $agentadmin_html = $agentadmin_html.$agentadmin_message_html.$member_treemap_btn_html.'
		<table id="transaction_list" class="table table-striped" cellspacing="0" width="100%">
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


    // 即時計算投注派冊差額，並顯示於表格 footer 內。start in 0
    // ref: https://datatables.net/reference/option/pageLength
    // ref: http://stackoverflow.com/questions/32962506/how-to-sum-of-some-rows-in-datatable-using-footercallback
    // 排序設定 "order" 參考 : https://datatables.net/reference/option/order
    $extend_head = $extend_head.'
		<script type="text/javascript" language="javascript" class="init">
			$(document).ready(function() {
				$("#transaction_list").DataTable( {
		        "paging":   true,
		        "ordering": true,
		        "info":     true,
						"order": [[ 2, "asc" ]],
						"pageLength": 30
				} );
			});
		</script>
		';

    // 轉帳 button 事件 js
    // 跳轉到轉帳頁面
    $extend_js = $extend_js . "";
  } else {
    //(X) 代理商組織列表查無相關資料。
    $agentadmin_html = $member_treemap_btn_html.$tr['agency organization no data'];
  }



  // 分紅明細 button 事件 js
  // 跳轉到分紅明細頁面
  $extend_js = $extend_js . "";

  // --------------------------------------------------------------------------
  // 排版及 show content
  // --------------------------------------------------------------------------
  $indexbody_content		= $indexbody_content.'
  <div class="row">
    <div class="col-12">
    '.$button_group_menu.'
    </div>
  </div>
  <div class="row">
    <div class="col-12">
    '.$agentadmin_html.'
    '.$agent_depositgcash_html.'
    </div>
  </div>
  <hr>
  <br>
  ';


// --------------------------------------------------------------------------
} else {
// --------------------------------------------------------------------------
	// 搜尋條件
	$wallets_content_html = '';
	// 列出資料
	if(isset($_SESSION['member']) AND $_SESSION['member']->therole == 'T') {
    //試用帳號，請先登出再以會員登入使用。
		$wallets_content_html = $tr['trail use member first'];
	} else {
    //會員請先登入。
		$wallets_content_html = $tr['member login first'];
	}

	// 切成 1 欄版面
	$indexbody_content = '';
	$indexbody_content = $indexbody_content.'
	<div class="row">
	  <div class="col-12">
	  '.$wallets_content_html.'
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
$tmpl['paneltitle_content'] 			= '<span class="glyphicon glyphicon-bookmark" aria-hidden="true"></span>'.$function_title;
// 主要內容 -- content
$tmpl['panelbody_content']				= $indexbody_content;

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include($config['template_path']."template/admin.tmpl.php");
?>
