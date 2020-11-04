<?php
// ----------------------------------------------------------------------------
// Features:	前台-- 會員注單查詢
// File Name:	shoppingrecord_action.php
// Author:		Barkley
// Related:   shoppingrecord.php
// DB table:  betlogec
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
$current_datetime = date("Y-m-d H:i:s");
$current_date = date("Y-m-d");
$query_limitdate = date( "Y-m-d", strtotime("-6 month"));
//echo $query_limitdate;

if(isset($_GET['a']) AND isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T') {
    $action = filter_var($_GET['a'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
    $ec_account_sql = 'SELECT ec_account FROM root_member_opencart WHERE id=\''.$_SESSION['member']->id.'\';';
    $ec_result = runSQLall($ec_account_sql);
    if($ec_result[0] == 1){
      $ec_account = $ec_result[1]->ec_account;
    }else{
      $ec_account = $_SESSION['member']->email;
    }
    $sql_query_str = 'WHERE site=\''.$config['projectid'].'\' AND ec_account=\''.$ec_account.'\' AND date_added >= \''.$query_limitdate.' 00:00:00\' AND date_added <= \''.$current_datetime.'\'';
}else{
    die('(x)不合法的測試');
}
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
if($action == 'shoppingrecord'){
  // -----------------------------------------------------------------------
  // datatable server process 用資料讀取
  // -----------------------------------------------------------------------
  $betrecords_sql   = 'SELECT id FROM ecshop_betrecords '.$sql_query_str.' ;';
  //echo $betrecords_sql;
  $betrecords_count = runSQL_betlog($betrecords_sql,0,'EC');

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
    if($_GET['order'][0]['column'] == 0){ $sql_order = 'ORDER BY order_id '.$sql_order_dir;
    }elseif($_GET['order'][0]['column'] == 1){ $sql_order = 'ORDER BY date_added '.$sql_order_dir;
    }elseif($_GET['order'][0]['column'] == 2){ $sql_order = 'ORDER BY product_name '.$sql_order_dir;
    }elseif($_GET['order'][0]['column'] == 3){ $sql_order = 'ORDER BY product_model '.$sql_order_dir;
    }elseif($_GET['order'][0]['column'] == 4){ $sql_order = 'ORDER BY product_price '.$sql_order_dir;
    }elseif($_GET['order'][0]['column'] == 5){ $sql_order = 'ORDER BY product_quantity '.$sql_order_dir;
    }elseif($_GET['order'][0]['column'] == 6){ $sql_order = 'ORDER BY product_price_subtotal '.$sql_order_dir;
    }elseif($_GET['order'][0]['column'] == 7){ $sql_order = 'ORDER BY order_status '.$sql_order_dir;
    }else{ $sql_order = 'ORDER BY date_added ASC';}
  }else{ $sql_order = 'ORDER BY date_added ASC';}
  // 取出 root_member 資料
  $betrecords_sql   = 'SELECT to_char((date_added AT TIME ZONE \'AST\'),\'YYYY-MM-DD HH24:MI:SS\') as log_time,
                    order_id,product_name,product_model,product_price,product_quantity,product_price_subtotal,order_status
                    FROM ecshop_betrecords '.$sql_query_str.' '.$sql_order.' OFFSET '.$page['no'].' LIMIT '.$page['per_size'].';';
  // var_dump($betrecords_sql);
  $betrecords = runSQLall_betlog($betrecords_sql,0,'EC');

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

      $b['rowid']                   = $betrecords[$i]->order_id;
      $b['log_time']                = $betrecords[$i]->log_time;
      $b['product_name']            = $betrecords[$i]->product_name;
      $b['product_model']           = $betrecords[$i]->product_model;
      $b['product_price']           = '&yen; '.$betrecords[$i]->product_price;
      $b['product_quantity']        = $betrecords[$i]->product_quantity;
      $b['product_price_subtotal']  = '&yen; '.$betrecords[$i]->product_price_subtotal;
      $b['order_status']            = empty($tr[strtolower($betrecords[$i]->order_status)]) ? $betrecords[$i]->order_status : $tr[strtolower($betrecords[$i]->order_status)];

      // 顯示的表格資料內容
      $show_listrow_array[] = array(
      'rowid'=>$b['rowid'],
      'log_time'=>$b['log_time'],
      'product_name'=>$b['product_name'],
      'product_model'=>$b['product_model'],
      'product_price'=>$b['product_price'],
      'product_quantity'=>$b['product_quantity'],
      'product_price_subtotal'=>$b['product_price_subtotal'],
      'order_status'=>$b['order_status']);
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
}elseif($action == 'shoppingrecord_summary'){
  $betrecords_sql   = 'SELECT count(order_id) as order_count,sum(product_price_subtotal) as order_sum FROM ecshop_betrecords '.$sql_query_str.';';
  // var_dump($betrecords_sql);
  $betrecords = runSQLall_betlog($betrecords_sql,0,'EC');
  //var_dump($betrecords);

	$show_transaction_sum_list_html = '';
  if($betrecords[0] == 1){
    $order_count = $betrecords[1]->order_count;
    if($betrecords[1]->order_sum == ''){
      $order_sum = 0;
    }else{
      $order_sum = $betrecords[1]->order_sum;
    }

  	$show_transaction_sum_list_html = $show_transaction_sum_list_html . '
  	<tr>
  		<td class="text-left"><span>'.$query_limitdate.' ~ '.$current_date.'</span></td>
  		<td class="text-left"><span>' . $order_count . '</span></td>
  		<td class="text-left"><span>&yen; ' . $order_sum . '</span></td>
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
