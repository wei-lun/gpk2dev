<?php
// ----------------------------------------------------------------------------
// Features:  前端 -- 手機版代理商投注紀錄查詢
// File Name: moa_betlog_action.php
// Author:    yaoyuan
// Related:   
// DB Table:  
// Log:
// ----------------------------------------------------------------------------

// 主機及資料庫設定

// 載入預設lib檔
require_once dirname(__FILE__) . "/moa_betlog_lib.php";


// var_dump($_SESSION);
// var_dump($csrftoken_ret);
// die();

// 只允許會員及代理權限操作
if (!isset($_SESSION['member']) || $_SESSION['member']->therole == 'T') {
    echo '<script>alert("不合法的帐号权限！");history.go(-1);</script>';die();
}

// csrf驗證
$csrftoken_ret = csrf_action_check();
if ($csrftoken_ret['code'] != 1) {die($csrftoken_ret['messages']);}


// 接收過濾變數
$query_str             = $query_where = array();
$sql                   = '';
$action                = isset($_POST['action']) ? my_filter($_POST['action'], "string") : '';
$query_str['time']     = isset($_POST['time'])  ? my_filter($_POST['time'], "string") : '';
$query_str['sel_date'] = isset($_POST['data']['sel_date'])  ? my_filter($_POST['data']['sel_date'], "string") : '';
$query_str['sel_casino'] = isset($_POST['data']['sel_casino'])  ? my_filter($_POST['data']['sel_casino'], "string") : '';
$query_str['sel_betstatus'] = isset($_POST['data']['sel_betstatus'])  ? my_filter($_POST['data']['sel_betstatus'], "string") : '';
$query_str['offset']   = isset($_POST['data']['tr_offset'])  ? my_filter($_POST['data']['tr_offset'], "int") : '';
// 接收，並判斷帳號
if (isset($_POST['data']['account']) and $_POST['data']['account'] != '') {
    $query_str['account'][] = my_filter($_POST['data']['account'], "string");
} elseif ($_POST['data']['account'] == '') {
    $query_str['account'] = json_decode(find_downline_member($_SESSION['member']->id),true)['account'];
    // var_dump($query_str);die();
} else {
    echo json_encode(['status' => 'fail', 'result' => '设定错误!']);
    die();
}

// 取得所有下線的帳號及遊戲帳號
$get_game_account=get_game_account($query_str['account']);
// var_dump($action);die();

// 傳入帳號及遊戲帳號，轉出成 IN (遊戲帳號)的sql字串
$sql_game_account=gameacc_tosql($get_game_account);


// 帶入查詢區間，及現在時間。加載更多時，則帶入之前時間，使得sql查詢區間不會更新，畫面資料量正確
$generate_time = array();
$generate_time = time_interval($query_str['sel_date'],$query_str['time'] );
// var_dump($generate_time);die();

// 將美東日期格式轉成台灣時區
$query_str['sdate'] =gmdate('Y-m-d H:i:s', strtotime($generate_time['s'] . ':00 -04') + 8 * 3600) . '+08';
$query_str['edate'] =gmdate('Y-m-d H:i:s', strtotime($generate_time['e'] . ' -04') + 8 * 3600) . '+08';
// var_dump($query_str['sdate']);var_dump($query_str['edate']);die();

// 組成sql where 日期查詢字串
$query_where['sdate'] = 'bettime >=\'' . $query_str['sdate'] . '\'';
$query_where['edate'] = 'bettime <=\'' . $query_str['edate'] . '\'';

// 組成sql 娛樂城查詢字串
if($query_str['sel_casino']=='' OR $query_str['sel_casino']=='all_casino'){
    $query_casino='';
}else{
    $query_casino=' AND casinoid =\''.$query_str['sel_casino'].'\'';
}

// 組成sql 注單狀態查詢字串
$status_chn_map_array=['Unpaid'=>'0','Paid'=>'1','modified'=>'2'];
if ($query_str['sel_betstatus'] == '' OR $query_str['sel_betstatus'] == 'all_status') {
    $query_betstatus = '';
} else {
    $query_betstatus = ' AND status =\'' . $status_chn_map_array[$query_str['sel_betstatus']] . '\'';
}



// 列出sql字串主體
$combineSelecteSql=combineSelecteSql();

//組成where 字串，中間加上 AND
$query_str['finish_str'] = ' AND ' . implode(' AND ', $query_where);

//每次顯示幾筆
$limit_show_count=7;

// 實際要撈多加一筆，判斷是否已至最底
$real_limit_show_count = $limit_show_count+1;

// 組成最終sql字串
$sql = $combineSelecteSql .$sql_game_account.$query_str['finish_str'].$query_casino.$query_betstatus.
' ORDER BY bettime DESC OFFSET '.$query_str['offset'].' LIMIT '.$real_limit_show_count.';';
// echo($sql);die();

$datatableInitData = runSQLall_betlog($sql);
// var_dump($datatableInitData);die();




if (empty($datatableInitData[0])) {
    if(isset($action) AND $action=='query'){
        echo json_encode(['status' => 'query_fail', 'result' => '查无资料!']);
        die();
    }elseif(isset($action) AND $action=='loadmore'){
        echo json_encode(['status' => 'loadmore_fail', 'result' => '加载资料，已到底!']);
        die();
    }
}

if($datatableInitData[0]>$limit_show_count){$hvaedata = '1';}else{$hvaedata='0';}

unset($datatableInitData[0]);
unset($datatableInitData[$real_limit_show_count]);

// 傳入帳號及遊戲帳號，轉出成$[遊戲帳號]=帳號
$gname_map_user = gname_map_user($get_game_account);
// var_dump($gname_map_user);die();


$data = combineDataTableInitData($datatableInitData,$gname_map_user);
// var_dump($data);die();

// 回傳至前端
echo json_encode([
    'status' => 'success',
    'result' => $data,
    'hvaedata'=>$hvaedata
    // 'stime'=>$query_str['sdate'],
    // 'etime'=>$query_str['edate']
]);

