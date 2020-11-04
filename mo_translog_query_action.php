<?php
// ----------------------------------------------------------------------------
// Features:  前台 -- 會員及代理商，交易紀錄查詢，動作處理
// File Name: moa_betlog_action.php
// Author:    yaoyuan
// Related:   mo_translog_query、mo_translog_query_action、mo_translog_query_lib、
// DB Table:  root_member_gcashpassbook、root_member_gtokenpassbook
// Log:       會員只查自己的紀錄、而代理商只查下線的交易紀錄
// ----------------------------------------------------------------------------

// 主機及資料庫設定

// 載入預設lib檔
require_once dirname(__FILE__) . "/mo_translog_query_lib.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// var_dump($_POST);
// var_dump($_GET);
// die();

// 只允許會員及代理權限操作
if (!isset($_SESSION['member']) || $_SESSION['member']->therole == 'T') {
    echo '<script>alert("不合法的帐号权限！");history.go(-1);</script>';die();
}

// ----------------接收過濾變數 start-----------------
$query_str             = $query_where = array();
$sql                   = '';
$action             = isset($_REQUEST['action']) ? my_filter($_REQUEST['action'], "string") : '';
$query_str['time']  = isset($_POST['time'])  ? my_filter($_POST['time'], "string") : '';
$query_str['sel_date']       = isset($_POST['data']['sel_date'])  ? my_filter($_POST['data']['sel_date'], "string") : '';
$query_str['sel_money_cate'] = isset($_POST['data']['sel_money_cate'])  ? my_filter($_POST['data']['sel_money_cate'], "string") : '';
$query_str['sel_cate']       = isset($_POST['data']['sel_cate'])  ? my_filter($_POST['data']['sel_cate'], "string") : '';
$query_str['offset']         = isset($_POST['data']['tr_offset'])  ? my_filter($_POST['data']['tr_offset'], "int") : '';
$query_str['transpage']      = isset($_POST['data']['transpage'])  ? my_filter($_POST['data']['transpage'], "string") : '';

// 1.有輸入帳號，表示組成此1人sql。2.沒有輸入帳號，代表找出所有下線。3.以上不符，表錯誤。
if (isset($_POST['data']['account']) and $_POST['data']['account'] != '') {
    $query_str['account'][] = my_filter($_POST['data']['account'], "string");
    $query_str['xls_account'][] = my_filter($_POST['data']['account'], "string");
} elseif (isset($_POST['data']['account']) and $_POST['data']['account'] == '') {
    $query_str['account'] = json_decode(find_downline_member($_SESSION['member']->id),true)['account'];
    $query_str['xls_account'][] = '';
}
// ----------------接收過濾變數 end -----------------


if(isset($action) AND ($action=='query' or $action=='loadmore') ){
    // csrf驗證
    $csrftoken_ret = csrf_action_check();
    if ($csrftoken_ret['code'] != 1) {die($csrftoken_ret['messages']);}

    // 組成下載xls加密字串
    $dl_xls_querystr['time']           = $query_str['time'];
    $dl_xls_querystr['sel_date']       = $query_str['sel_date'];
    $dl_xls_querystr['sel_money_cate'] = $query_str['sel_money_cate'];
    $dl_xls_querystr['sel_cate']       = $query_str['sel_cate'];
    $dl_xls_querystr['account']        = $query_str['xls_account'];
    $dl_xls_querystr['transpage']      = $query_str['transpage'];

    $dl_csv_code = jwtenc('motranslogquery', $dl_xls_querystr);
    // var_dump($dl_csv_code);die();

    // 組成sql 查詢字串
    $getPassbookConfig = getPassbookConfig();
    $combineSelecteSql = combineSelecteSql($getPassbookConfig[$query_str['sel_money_cate']]['table'], $getPassbookConfig[$query_str['sel_money_cate']]['account']);
    // echo($combineSelecteSql);die();
    
    
    //每次顯示幾筆
    $limit_show_count = 7;
    // 實際要撈多加一筆，判斷是否已至最底
    $real_limit_show_count = $limit_show_count + 1;
    
    $query_where=query_str_sql($query_str);
    // var_dump($query_where);die();
    //組成sql完整字串
    $query_str['finish_str'] = ' AND ' . implode(' AND ', $query_where);
    $sql = $combineSelecteSql . $query_str['finish_str'] . ' ORDER BY trans_time DESC OFFSET '.$query_str['offset'].' LIMIT '.$real_limit_show_count.';';
    // echo ($sql);die();
    
    $datatableInitData = runSQLall($sql);
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
    
    $data = combineDataTableInitData($datatableInitData,$getPassbookConfig[$query_str['sel_money_cate']]['str']);
    // var_dump($data);die();
    
    // 回傳至前端
    echo json_encode([
        'status' => 'success',
        'result' => $data,
        'hvaedata'=>$hvaedata,
        "download_url"    => 'mo_translog_query_action.php?action=download_xls&csv=' . $dl_csv_code,
        // 'stime'=>$query_str['sdate'],
        // 'etime'=>$query_str['edate']
    ]);

}elseif(isset($action) AND $action=='download_xls'){
    // 解碼
    $query_str = (array)jwtdec('motranslogquery', $_GET['csv']);
    if(isset($query_str[0]) AND $query_str[0] == false){
        echo 'error code:csv error';
        die();
    }

    
    // 1.有輸入帳號，表示組成此1人sql。2.沒有輸入帳號，代表找出所有下線。3.以上不符，表錯誤。
    if (isset($query_str['account']) and $query_str['account'][0] != '') {

    } elseif (isset($query_str['account']) and $query_str['account'][0] == '') {
        $query_str['account'] = json_decode(find_downline_member($_SESSION['member']->id),true)['account'];
    }
    // print("<pre>" . print_r($query_str, true) . "</pre>");die();

    // 組成sql 查詢字串
    $getPassbookConfig = getPassbookConfig();
    $combineSelecteSql = combineSelecteSql($getPassbookConfig[$query_str['sel_money_cate']]['table'], $getPassbookConfig[$query_str['sel_money_cate']]['account']);
    
    // 組成查詢條件
    $query_where = query_str_sql($query_str);

    //組成sql完整字串
    $query_str['finish_str'] = ' AND ' . implode(' AND ', $query_where);
    $sql                     = $combineSelecteSql . $query_str['finish_str'] . ' ORDER BY trans_time DESC;';
    // echo($sql);die();
    
    $datatableInitData = runSQLall($sql);

    // --------------------------------------
    // xlsx
    // 找該代理商所有下線
    $export_one_translog_query = json_decode(find_downline_member($_SESSION['member']->id,'a'),true);
    // 被搜尋的帳號
    $member_name = implode($query_str["account"]);
    // 分類
    $transcategory = transaction2category();
    // 檔名顯示的日期
    $filter_date_str = filter_time_filename($query_str['sel_date'],$query_str['time']); 
    // 檔名組合
    $output_filename = combine_filename($filter_date_str,$query_str['transpage'],$member_name,$export_one_translog_query['account']);
    
    // 幣別
    $money_cate = $getPassbookConfig[$query_str['sel_money_cate']]['str'];

    if($datatableInitData[0] >= 1){
        $j = $v = 1;
        // 欄位名稱
        $excel_col_name[0][$v++] = $tr['account'];
        $excel_col_name[0][$v++] = $tr['Transfer number'];
        $excel_col_name[0][$v++] = $tr['transcation time'];
        $excel_col_name[0][$v++] = $tr['Deposit amount'] ;
        $excel_col_name[0][$v++] = $tr['withdrawal amount'];
        $excel_col_name[0][$v++] = $tr['transaction_log_payout'];
        $excel_col_name[0][$v++] = $tr['current balance'];
        $excel_col_name[0][$v++] = $tr['transaction type'];
        $excel_col_name[0][$v++] = $tr['summary'] ;
        $excel_col_name[0][$v++] = $tr['currency'];

        $summaryst = '';
        $payout_content = '';
        // 寫入資料
        for($i = 1; $i <= $datatableInitData[0]; $i++){
            $v = 1;
            // 如果是公司入款，則抓取入款單狀態->summary
            if($datatableInitData[$i]->transaction_category == 'company_deposits'){
                $summaryst = '同意';
            }
            // 派彩沒有值
            if($datatableInitData[$i]->payout == ''){
                $payout_content = '-';
            }
            $transactionCategoryStr = $tr[array_keys($transaction_ary,$transcategory[$datatableInitData[$i]->transaction_category])[0]]??'分类错误，请联系客服人员!';

            $excel_col_name[$i][$v++] = $datatableInitData[$i]->source_transferaccount; // 帳號
            $excel_col_name[$i][$v++] = $datatableInitData[$i]->transaction_id; // 交易單號
            $excel_col_name[$i][$v++] = $datatableInitData[$i]->trans_time; // 交易時間
            $excel_col_name[$i][$v++] = $datatableInitData[$i]->deposit; // 存款金額
            $excel_col_name[$i][$v++] = $datatableInitData[$i]->withdrawal; // 提款金額
            $excel_col_name[$i][$v++] = $payout_content; // 派彩
            $excel_col_name[$i][$v++] = $datatableInitData[$i]->balance; // 當下平台餘額
            $excel_col_name[$i][$v++] = $transactionCategoryStr; // $datatableInitData[$i]->transaction_category; // 分類
            $excel_col_name[$i][$v++] = $datatableInitData[$i]->summary.$summaryst. '('.$datatableInitData[$i]->source_transferaccount.')'; // 摘要
            $excel_col_name[$i][$v++] = $money_cate; // 幣別
            $j++;
        }
    }else{
        echo 'error:查無資料';
    }
    // 清除快取以防亂碼
    ob_end_clean();

    //---------------phpspreadsheet----------------------------
    $spreadsheet = new Spreadsheet();

    // Create a new worksheet called "My Data"
    $myWorkSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, '明细');

    // Attach the "My Data" worksheet as the first worksheet in the Spreadsheet object
    $spreadsheet->addSheet($myWorkSheet, 0);

    // 總表索引標籤開始寫入資料
    $sheet = $spreadsheet->setActiveSheetIndex(0);

    // 寫入總表資料陣列
    $sheet->fromArray($excel_col_name, null, 'A1');
    // var_dump($excel_col_name);die();

    // 自動欄寬
    $worksheet = $spreadsheet->getActiveSheet();
    foreach (range('A', $worksheet->getHighestColumn()) as $column) {
        $spreadsheet->getActiveSheet()->getColumnDimension($column)->setAutoSize(true);
    }

    // xlsx
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $output_filename . '.xlsx"');
    header('Cache-Control: max-age=0');

    // 直接匯出，不存於disk
    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
}else{
    echo json_encode(['status' => 'query_fail', 'result' => '查无资料!']);
    die();

}






