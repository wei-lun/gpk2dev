<?php
// ----------------------------------------------------------------------------
// Features:	前台-- 會員注單查詢
// File Name:	bonus_commission_profit_action.php
// Author:		Barkley
// Related:   bonus_commission_profit.php
// DB table:  root_statisticsbonusprofit  營運利潤獎金
// Log:
// ----------------------------------------------------------------------------



require_once dirname(__FILE__) ."/config.php";
require_once dirname(__FILE__) ."/config_betlog.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

$debug = 0;
// -------------------------------------------------------------------------
// 本程式使用的 function
// -------------------------------------------------------------------------

// -------------------------------------------------------------------------
// 注單遊戲名稱，產生中英文對照表
// -------------------------------------------------------------------------
function load_gamelist_i18n() {
  $GameNameI18 = new stdClass();
	$translatetionChn_sql = 'SELECT DISTINCT trim(lower("gamename")) enlowgamename , "gamename_cn","casino_id" FROM "casino_gameslist";';
	// var_dump($translatetionChn_sql);--撈出sql語法
	$translatetionChn_sql_result = runSQLall($translatetionChn_sql,0,'r');
	//var_dump($translatetionChn_sql_result);--執行結果
	foreach ($translatetionChn_sql_result as $key => $value) {
		//將索引值給$key，整個英中遊戲名稱給$value
		if ($key != 0) {
			//對照表的遊戲名稱，以中文為主，否則英文
			$GameNameI18->{$value->enlowgamename} = empty($value->gamename_cn) ? $value->enlowgamename : $value->gamename_cn;
		}

	}

  // get MEGA's gamelist
  require_once dirname(__FILE__) ."/casino/MEGA/lobby_megagame_lib.php";
  $MEGA_API_data['version'] = 'v2';
  $MEGA_gamelist = mega_gpk_api('GamenameLists', '0', $MEGA_API_data);
  // var_dump($MEGA_gamelist);
  if($MEGA_gamelist['errorcode'] == 0 AND $MEGA_gamelist['Status'] == 1 AND $MEGA_gamelist['count'] > 0){
    foreach ($MEGA_gamelist['Result'] as $key => $value) {
      $value = get_object_vars($value);
      $GameNameI18->{$value['enus']} = $value['zhcn'];
    }
  }

  return $GameNameI18;
}
// -------------------------------------------------------------------------
// END function lib
// -------------------------------------------------------------------------


// -------------------------------------------------------------------------
// 取得日期 - 決定開始用份的範圍日期
// -------------------------------------------------------------------------
// get example: ?current_datepicker=2017-02-03
// ref: http://php.net/manual/en/function.checkdate.php
function validateDate($date, $format = 'Y-m-d H:i:s')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}
// -------------------------------------------------------------------------
// END function lib
// -------------------------------------------------------------------------

// -------------------------------------------------------------------------
// GET / POST 傳值處理
// -------------------------------------------------------------------------
//var_dump($_SESSION);
//var_dump($_POST);
//var_dump($_GET);
$query_limitdate = date( "Y-m-d", strtotime("-1 month"));
//echo $query_limitdate;

if(isset($_GET['a']) AND isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T') {
    $action = filter_var($_GET['a'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
}else{
    die('(x)不合法的測試');
}

if(isset($_GET['d']) AND validateDate($_GET['d'], 'Y-m-d') AND strtotime($_GET['d']) >= strtotime($query_limitdate)) {
	$day = $_GET['d'];
  $datepicker_end = date('Y-m-d H:i:s',strtotime($day.' 00:00:00-04')).' +08';
  $current_datepicker =  date('Y-m-d H:i:s',strtotime($day.' 23:59:59-04')).' +08';
}else{
  die('(503)不合法的測試');
}

// -------------------------------------------------------------------------
// 取得日期 - 決定開始用份的範圍日期  END
// -------------------------------------------------------------------------

// 取得會員的娛樂城帳號
$member_casinoaccount = [];
$member_casinoaccount_sql = 'SELECT casino_accounts FROM root_member_wallets WHERE id = \''.$_SESSION['member']->id.'\';';
$member_casinoaccount_result = runSQLall($member_casinoaccount_sql,0,'r');
if($member_casinoaccount_result['0'] == 1){
  $casino_info = json_decode($member_casinoaccount_result[1]->casino_accounts,'true');
  if(count($casino_info) >= 1){
    foreach($casino_info as $cid => $cinfo){
      $member_casinoaccount[$cid] = $cinfo['account'];
    }
  }
}

// 取得傳來的casinoid
if(isset($_GET['cid'])) {
  $cid = filter_var($_GET['cid'], FILTER_SANITIZE_STRING);
  $check_casino_state_sql = 'SELECT casinoid from casino_list WHERE casinoid = \'' . $cid . '\' AND open=\'1\'';
  $check_casino_state_result = runSQLall($check_casino_state_sql,0,'r');
  if ($check_casino_state_result[0] == 1) {
    $casinoid = $check_casino_state_result['1']->casinoid;
    $betlog_query_str = ' casinoid=\''.$casinoid.'\' AND LOWER(casino_account)=\''.$member_casinoaccount[$casinoid].'\'';
  }else{
      die('(501)不合法的測試');
  }
}else{
  $betlog_query_str = 'LOWER(casino_account) IN (\''.implode("','",$member_casinoaccount).'\')';
}

$betlog_query_str = $betlog_query_str.' AND bettime >= \''.$datepicker_end.'\' AND bettime <= \''.$current_datepicker.'\'';
// -------------------------------------------------------------------------
// datatable server process 分頁處理及驗證參數
// -------------------------------------------------------------------------
// 程式每次的處理量 -- 當資料量太大時，可以分段處理。 透過 GET 傳遞依序處理。
if(isset($_GET['length']) AND $_GET['length'] != NULL ) {
  $current_per_size = filter_var($_GET['length'],FILTER_VALIDATE_INT);
}else{
  $current_per_size = $page_config['datatables_pagelength'];
  //$current_per_size = 10;
}

// 起始頁面, 搭配 current_per_size 決定起始點位置
if(isset($_GET['start']) AND $_GET['start'] != NULL ) {
  $current_page_no = filter_var($_GET['start'],FILTER_VALIDATE_INT);
}else{
  $current_page_no = 0;
}

// datatable 回傳驗證用參數，收到後不處理直接跟資料一起回傳給 datatable 做驗證
if(isset($_GET['_'])){
  $secho = $_GET['_'];
}else{
  $secho = '1';
}
// -------------------------------------------------------------------------
// datatable server process 分頁處理及驗證參數  END
// -------------------------------------------------------------------------

// -------------------------------------------------------------------------
// GET / POST 傳值處理 END
// -------------------------------------------------------------------------

// -------------------------------------------------------------------------
// 動作為會員登入檢查 MAIN
// -------------------------------------------------------------------------
if($action == 'loadbetrecord'){
  // get gane name for i18n
  $gamename_i18n = load_gamelist_i18n();
  // -----------------------------------------------------------------------
  // datatable server process 用資料讀取
  // -----------------------------------------------------------------------
  $betrecords_sql   = 'SELECT id FROM betrecordsremix WHERE '.$betlog_query_str.' ;';
  // echo $betrecords_sql;
  $betrecords_count = runSQL_betlog($betrecords_sql);
  // var_dump($betrecords_count);

  // -----------------------------------------------------------------------
  // 分頁處理機制
  // -----------------------------------------------------------------------
  // 所有紀錄數量
  $page['all_records']     = $betrecords_count;
  // 每頁顯示多少
  $page['per_size']        = $current_per_size;
  // 目前所在頁數
  $page['no']              = $current_page_no;
  // var_dump($page);

  // 處理 datatables 傳來的排序需求
  if(isset($_GET['order'][0]) AND $_GET['order'][0]['column'] != ''){
    if($_GET['order'][0]['dir'] == 'asc'){ $sql_order_dir = 'ASC';
    }else{ $sql_order_dir = 'DESC';}
    if($_GET['order'][0]['column'] == 0){ $sql_order = 'ORDER BY game_category '.$sql_order_dir;
    }elseif($_GET['order'][0]['column'] == 1){ $sql_order = 'ORDER BY game_name '.$sql_order_dir;
    }elseif($_GET['order'][0]['column'] == 2){ $sql_order = 'ORDER BY bettime '.$sql_order_dir;
    }elseif($_GET['order'][0]['column'] == 3){ $sql_order = 'ORDER BY receivetime '.$sql_order_dir;
    }elseif($_GET['order'][0]['column'] == 4){ $sql_order = 'ORDER BY betvalid '.$sql_order_dir;
    }elseif($_GET['order'][0]['column'] == 5){ $sql_order = 'ORDER BY betresult '.$sql_order_dir;
    }elseif($_GET['order'][0]['column'] == 6){ $sql_order = 'ORDER BY casinoid '.$sql_order_dir;
    }else{ $sql_order = 'ORDER BY bettime ASC';}
  }else{ $sql_order = 'ORDER BY bettime ASC';}
  // if(isset($_GET['order'][0]) AND $_GET['order'][0]['column'] != ''){
  //   if($_GET['order'][0]['dir'] == 'asc'){ $sql_order_dir = 'ASC';
  //   }else{ $sql_order_dir = 'DESC';}
  //   if($_GET['order'][0]['column'] == 0){ $sql_order = 'ORDER BY bettime '.$sql_order_dir;
  //   }elseif($_GET['order'][0]['column'] == 1){ $sql_order = 'ORDER BY game_name '.$sql_order_dir;
  //   }elseif($_GET['order'][0]['column'] == 2){ $sql_order = 'ORDER BY game_category '.$sql_order_dir;
  //   }elseif($_GET['order'][0]['column'] == 3){ $sql_order = 'ORDER BY betvalid '.$sql_order_dir;
  //   }elseif($_GET['order'][0]['column'] == 4){ $sql_order = 'ORDER BY betresult '.$sql_order_dir;
  //   }elseif($_GET['order'][0]['column'] == 5){ $sql_order = 'ORDER BY casinoid '.$sql_order_dir;
  //   }else{ $sql_order = 'ORDER BY bettime ASC';}
  // }else{ $sql_order = 'ORDER BY bettime ASC';}
  // 取出 root_member 資料
  $betrecords_sql   = 'SELECT to_char((bettime AT TIME ZONE \'AST\'),\'YYYY-MM-DD HH24:MI:SS\') as log_time,
                              to_char((receivetime AT TIME ZONE \'AST\'),\'YYYY-MM-DD HH24:MI:SS\') as receive_time,
                    game_name,game_category,betvalid,betresult,casinoid,rowid,status
                    FROM betrecordsremix WHERE '.$betlog_query_str.' '.$sql_order.' OFFSET '.$page['no'].' LIMIT '.$page['per_size'].';';
  // var_dump($betrecords_sql);
  $betrecords = runSQLall_betlog($betrecords_sql);

  // 存放列表的 html -- 表格 row -- tables DATA
  $show_listrow_html = '';
  // 判斷 root_member count 數量大於 1
  if($betrecords[0] >= 1) {
    // 以會員為主要 key 依序列出每個會員的貢獻金額
    for($i = 1 ; $i <= $betrecords[0]; $i++){
      // 抓出其中一個人的資料一筆
      if($debug == 1) {
        var_dump($betrecords_sql);
        var_dump($betrecords);
      }
      if($betrecords[$i]->casinoid == 'PT'){
        $gamenamekey = trim(strtolower(explode(" (",$betrecords[$i]->game_name)[0]));
      }else{
        $gamenamekey = trim(strtolower($betrecords[$i]->game_name));
      }
      if($betrecords[$i]->casinoid == 'MEGA'){
        $betrecords[$i]->casinoid = 'GPK';
      }

      $b['log_time']         = $betrecords[$i]->log_time;
      $b['game_name']        = empty($gamename_i18n->$gamenamekey) ? $betrecords[$i]->game_name : $gamename_i18n->$gamenamekey;
      $b['game_category']    = empty($tr[$betrecords[$i]->game_category]) ? $betrecords[$i]->game_category : $tr[$betrecords[$i]->game_category];
      $b['receive_time']     = $betrecords[$i]->receive_time;
      $b['betvalid']         = $betrecords[$i]->betvalid;
      $b['betresult']        = $betrecords[$i]->betresult;
      // $b['payout']           = $b['betvalid'] + $b['betresult'];
      // $b['rowid']            = $betrecords[$i]->rowid;
      $b['casinoid']         = $betrecords[$i]->casinoid;

      if($b['betresult'] >= 0){
    		$difference_payout_style = 'color: blue;';
    	}else{
    		$difference_payout_style = 'color: red;';
    	}
      $b['betresult'] = '<span style="'.$difference_payout_style.'">'.$b['betresult'].'</span>';


      if($betrecords[$i]->status==0){
          $b['receive_time'] ='未派彩';
          $b['betresult']    ='-';
      }

      // 顯示的表格資料內容
      $show_listrow_array[] = array(
      'log_time'=>$b['log_time'],
      'game_name'=>$b['game_name'],
      'game_category'=>$b['game_category'],
      'receive_time'=>$b['receive_time'],
      'betvalid'=>$b['betvalid'],
      'betresult'=>$b['betresult'],
      'casinoid'=>$b['casinoid']);
    }
    $output = array(
      "sEcho" => intval($secho),
      "iTotalRecords" => intval($page['per_size']),
      "iTotalDisplayRecords" => intval($page['all_records']),
      "data" => $show_listrow_array
    );
    // --------------------------------------------------------------------
    // 表格資料 row list , end for loop
    // --------------------------------------------------------------------
  }else{
    // NO member
    $output = array(
      "sEcho" => 0,
      "iTotalRecords" => 0,
      "iTotalDisplayRecords" => 0,
      "data" => ''
    );
  }
  // end member sql
  echo json_encode($output);
  // -----------------------------------------------------------------------
  // datatable server process 用資料讀取 END
  // -----------------------------------------------------------------------
}elseif($action == 'betrecord_summary'){
  $betrecords_sql   = 'SELECT count(id) as betcount,SUM(betvalid) as bet_sum,sum(betresult) as result_sum
                    FROM betrecordsremix WHERE '.$betlog_query_str.';';
  // var_dump($betrecords_sql);
  $betrecords = runSQLall_betlog($betrecords_sql);
  //var_dump($betrecords);

	$show_transaction_sum_list_html = '';
  if($betrecords[0] == 1){
    $gamble_count = $betrecords[1]->betcount;
    if($betrecords[1]->bet_sum == ''){
      $all_bets = 0;
      $all_profitlost_result = 0;
    }else{
      $all_bets = $betrecords[1]->bet_sum;
      $all_profitlost_result = $betrecords[1]->result_sum;
    }
    $all_wins = $all_bets + $all_profitlost_result;

  	if ($all_profitlost_result >= 0) {
  		$difference_payout_style = 'color: blue;';
  	} else {
  		$difference_payout_style = 'color: red;';
  	}

  	$show_transaction_sum_list_html = $show_transaction_sum_list_html . '
  	<tr>
  		<td class="text-left"><span>' . $day . '</span></td>
  		<td class="text-left"><span>' . $gamble_count . '</span></td>
  		<td class="text-left"><span>' . $all_bets . '</span></td>
  		<td class="text-left"><span style="' . $difference_payout_style . '">' . $all_profitlost_result . '</span></td>
  	</tr>
  	';
  }else{
    $show_transaction_sum_list_html = array( 'logger' => 'Error!!');
  }

  echo json_encode($show_transaction_sum_list_html);
}else{
  // NO member
  $output = array(
    "sEcho" => 0,
    "iTotalRecords" => 0,
    "iTotalDisplayRecords" => 0,
    "data" => ''
  );
  echo json_encode($output);
}



?>
