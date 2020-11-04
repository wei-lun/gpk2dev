<?php
// ----------------------------------------------------------------------------
// Features:  前台 行動版 -- 會員及代理商，交易紀錄查詢lib
// File Name: moa_betlog_lib.php
// Author:    YaoYuan
// Related:   mo_translog_query、mo_translog_query_action、mo_translog_query_lib、
// DB Table:  root_member_gcashpassbook、root_member_gtokenpassbook
// Log:       會員只查自己的紀錄、而代理商只查下線的交易紀錄
// ----------------------------------------------------------------------------
// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";
require_once dirname(__FILE__) ."/lib_member_tree.php";


// $tr['transaction_log_manualDeposit'] = '人工存款';$tr['transaction_log_manualWithdrawal'] = '人工提款';$tr['transaction_log_onlineDeposit'] = '線上存款';$tr['transaction_log_onlineWithdrawals'] = '線上提款';$tr['transaction_log_companyDeposits'] = '公司入款';$tr['transaction_log_agencyCommission'] = '代理佣金';$tr['transaction_log_agencyTransfer'] = '現金轉帳';$tr['transaction_log_walletTransfer'] = '錢包轉帳';$tr['transaction_log_promotions'] = '優惠活動';$tr['transaction_log_payout'] = '派彩';$tr['transaction_log_bouns'] = '反水';$tr['transaction_log_other'] = '其它';$tr['transaction_log_withdrawalAdministrationFee'] = '提款行政費';
$transaction_ary = [
    "transaction_log_manualDeposit"               => "manualDeposit",
    "transaction_log_manualWithdrawal"            => "manualWithdrawal",
    "transaction_log_onlineDeposit"               => "onlineDeposit",
    "transaction_log_onlineWithdrawals"           => "onlineWithdrawals",
    "transaction_log_companyDeposits"             => "companyDeposits",
    "transaction_log_agencyCommission"            => "agencyCommission",
    "transaction_log_agencyTransfer"              => "agencyTransfer",
    "transaction_log_walletTransfer"              => "walletTransfer",
    "transaction_log_promotions"                  => "promotions",
    "transaction_log_payout"                      => "payout",
    "transaction_log_bouns"                       => "bouns",
    "transaction_log_other"                       => "other",
    "transaction_log_withdrawalAdministrationFee" => "withdrawalAdministrationFee",
];


// 函數：找出該代理商的所有下線，轉成json
function find_downline_member($id,$identity='a'){
    $all_downline_data = MemberTreeNode::getSuccessorList($id);
    $downline_ary      = array();
    foreach ($all_downline_data as $k => $val) {
        if($identity=='a'){
            if ($val->id != $_SESSION['member']->id) {
                $downline_ary['id'][]      = ($val->id);
                $downline_ary['account'][] = ($val->account);
            }
        }else{
            $downline_ary['id'][]      = ($val->id);
            $downline_ary['account'][] = ($val->account);
        }
    }
    // var_dump($downline_ary);
    return json_encode($downline_ary);
}

//  指定資料類型來過濾變數
function my_filter($var, $type = "int"){
    switch ($type) {
        case 'string':
            $var = isset($var) ? filter_var($var, FILTER_SANITIZE_STRING) : '';
            break;
        case 'url':
            $var = isset($var) ? filter_var($var, FILTER_SANITIZE_URL) : '';
            break;
        case 'email':
            $var = isset($var) ? filter_var($var, FILTER_SANITIZE_EMAIL) : '';
            break;
        case 'int':
        default:
            $var = isset($var) ? filter_var($var, FILTER_SANITIZE_NUMBER_INT) : '';
            break;
    }
    return $var;
}

// 時間區間對映
function time_interval($timevalue,$deadline){
    // $current_date      = gmdate('Y-m-d', time()+-4 * 3600);
    // $current_date_time = gmdate('Y-m-d H:i', time()+-4 * 3600);
    $current_date      = gmdate('Y-m-d', $deadline+-4 * 3600);
    $current_date_time = gmdate('Y-m-d H:i:s', $deadline+-4 * 3600);
    $today_s           = $current_date . ' 00:00';
    $today_e           = $current_date_time;
    $yesterday_s       = date("Y-m-d", strtotime("$current_date - 1 days")) . ' 00:00';
    $yesterday_e       = date("Y-m-d", strtotime("$current_date - 1 days")) . ' 23:59:59';
    $this_week_s       = date("Y-m-d", strtotime("$current_date - " . date('w', strtotime("$current_date")) . "days")) . ' 00:00';
    $this_week_e       = $current_date_time;
    $this_month_s      = date("Y-m", strtotime("$current_date")) . '-01 00:00';
    $this_month_e      = $current_date_time;
    $lastmonth_s       = date("Y-m", strtotime("$current_date - 1 month")) . '-01 00:00';
    $lastmonth_e       = date("Y-m-t", strtotime("$current_date - 1 month")) . ' 23:59:59';
    // 查看日期區間
    // var_dump($current_date);var_dump($current_date_time);var_dump($today_s);var_dump($today_e);var_dump($yesterday_s);var_dump($yesterday_e);var_dump($this_week_s);var_dump($this_week_e);var_dump($this_month_s);var_dump($this_month_e);var_dump($lastmonth_s);var_dump($lastmonth_e);    die();
    $date_ary = array();
    $start    = $end    = '';
    switch ($timevalue) {
        case 'today':
            $start = $today_s;
            $end   = $today_e;
            break;
        case 'yesterday':
            $start = $yesterday_s;
            $end   = $yesterday_e;
            break;
        case 'week':
            $start = $this_week_s;
            $end   = $this_week_e;
            break;
        case 'month':
            $start = $this_month_s;
            $end   = $this_month_e;
            break;
        case 'lastmonth':
            $start = $lastmonth_s;
            $end   = $lastmonth_e;
            break;
        default:
            break;
    }
    $date_ary['s'] = $start;
    $date_ary['e'] = $end;
    return $date_ary;
}

function getPassbookConfig(){
    $config = [
        'gcash_val'  => [
            'table'   => 'root_member_gcashpassbook',
            'account' => 'gcashcashier',
            'str'     => '现金',
        ],
        'gtoken_val' => [
            'table'   => 'root_member_gtokenpassbook',
            'account' => 'gtokencashier',
            'str'     => '游戏币',
        ],
    ];
    return $config;
}

// 組成sql字串
function combineSelecteSql($tableName, $cashierAccount){
$sql = <<<SQL
    SELECT to_char((transaction_time AT TIME ZONE 'AST'),'YYYY-MM-DD HH24:MI:SS') as trans_time,
            id as trans_id,
            transaction_category,
            deposit,
            withdrawal,
            balance,
            source_transferaccount,
            destination_transferaccount,
            transaction_id,
            summary as summary,
            CASE WHEN transaction_category = 'tokenpay' THEN deposit-withdrawal END AS payout,
            deposit-withdrawal as transaction_amount
    FROM {$tableName} as psbk
    WHERE source_transferaccount != '{$cashierAccount}'
SQL;
    return $sql;
}

function transaction2category(){
    $result=[
            'cashdeposit'  => 'manualDeposit',//现金存款
            'tokendeposit' => 'manualDeposit',//游戏币存款
            'cashwithdrawal'           => 'manualWithdrawal',//现金提款
            'reject_cashwithdrawal'    => 'manualWithdrawal',
            'tokengcash'               => 'manualWithdrawal',
            'reject_tokengcash'        => 'manualWithdrawal',
            'tokentogcashpoint'        => 'manualWithdrawal',
            'reject_tokentogcashpoint' => 'manualWithdrawal',
            'apicashdeposit'   => 'onlineDeposit',
            'payonlinedeposit' => 'onlineDeposit',
            'apitokendeposit'  => 'onlineDeposit',
            'apitokenwithdrawal' => 'onlineWithdrawals',
            'apicashwithdrawal'  => 'onlineWithdrawals',
            'company_deposits'        => 'companyDeposits',//公司入款
            'reject_company_deposits' => 'companyDeposits',//公司入款退回
            'agent_commission' => 'agencyCommission',
            'cashtransfer' => 'agencyTransfer',
            'cashgtoken' => 'walletTransfer',
            'tokenfavorable' => 'promotions',
            'cashadministrationfees'  => 'withdrawalAdministrationFee',
            'tokenadministrationfees' => 'withdrawalAdministrationFee',
            'tokenpay' => 'payout',
            'tokenpreferential' => 'bouns',
            'tokenrecycling' => 'other',
    ];

        return $result;
}

function getTransactionType(){
    $transactionType = [
        'manualDeposit'               => [
            'cashdeposit',
            'tokendeposit',
        ],
        'manualWithdrawal'            => [
            'cashwithdrawal', //現金提款
            'reject_cashwithdrawal', //现金提款退回
            'tokengcash', //游戏币转銀行
            'reject_tokengcash', //游戏币转銀行退回
            'tokentogcashpoint', //遊戲幣轉現金
            'reject_tokentogcashpoint', //遊戲幣轉現金退回
        ],
        'onlineDeposit'               => [
            'apicashdeposit',
            'payonlinedeposit',
            'apitokendeposit',
        ],
        'onlineWithdrawals'           => [
            'apitokenwithdrawal',
            'apicashwithdrawal',
        ],
        'companyDeposits'             => [
            'company_deposits',
            'reject_company_deposits',
        ],
        'agencyCommission'            => [
            'agent_commission',
        ],
        'agencyTransfer'              => [
            'cashtransfer',
        ],
        'walletTransfer'              => [
            'cashgtoken',
        ],
        'promotions'                  => [
            'tokenfavorable',
        ],
        'withdrawalAdministrationFee' => [
            'cashadministrationfees',
            'tokenadministrationfees',
        ],
        'payout'                      => [
            'tokenpay',
        ],
        'bouns'                       => [
            'tokenpreferential',
        ],
        'other'                       => [
            'tokenrecycling',
        ],
    ];

    return $transactionType;
}

// 使用交易單號查詢，公司入款狀態
function query_status_usetransactionid_incompany_deposit($trans_no){
$sql = <<<SQL
    SELECT transaction_id,status
    FROM root_deposit_review
    WHERE transaction_id = '{$trans_no}'
    ORDER BY changetime DESC
SQL;
    return runSQLall($sql);
}

// sql 解出datatable所需資料
function combineDataTableInitData($data,$passbook='')
{
    global $tr;
    global $transaction_category;
    global $transaction_ary;

    $trans2cate = transaction2category();

    // $mapstatus=[0=>$tr['cancel'] ,1=>'同意',2=>'审核中'];

    $initDatas = [];
    foreach ($data as $k => $v) {
        // var_dump($v->transaction_category); 
        // var_dump($transaction_category[$v->transaction_category]);
        // var_dump($trans2cate[$v->transaction_category]);
        // var_dump(array_keys($transaction_ary,$trans2cate[$v->transaction_category]));
        // var_dump(array_keys($transaction_ary,$trans2cate[$v->transaction_category])[0]);
        // var_dump($tr[array_keys($transaction_ary,$trans2cate[$v->transaction_category])[0]]);
        // die();

        // 交易明細顯示+紅，-綠
        if ($v->transaction_amount >= 0) {
            $show_transaction_amount = '<span class="text-danger version_color">$' . $v->transaction_amount . '</span>';
        } else {
            $show_transaction_amount = '<span class="text-success version_color">$' . $v->transaction_amount . '</span>';
        }

        // 如果是公司入款，則抓取入款單狀態->summary
        $summaryst='';
        if($v->transaction_category=='company_deposits'){
            $summaryst='同意';
        }

        $transactionCategoryStr = $tr[array_keys($transaction_ary,$trans2cate[$v->transaction_category])[0]]??'分类错误，请联系客服人员!';
        $initDatas[]            = [
            'id'                   => $k,
            'trans_id'             => $v->trans_id,
            'transtime'            => $v->trans_time,
            'transaction_category' => $transactionCategoryStr,
            'summary'              => $v->summary .$summaryst. '   (' . $v->source_transferaccount . ')',
            'transaction_amount' => $show_transaction_amount,
            'balance'            => $v->balance,
            'deposit'            => $v->deposit,
            'withdrawal'         => $v->withdrawal,
            'transaction_id'     => empty($v->transaction_id)?'-':$v->transaction_id,
            'source_transferaccount'      => $v->source_transferaccount,
            'destination_transferaccount' => $v->destination_transferaccount,
            'passbook' => $passbook,
            'payout'    =>empty($v->payout)?'-':$v->payout,
            // 'account'              => '<a href="member_account.php?a=' . $v->source_transferaccount_id . '" target="_BLANK" data-role=\" button\" title="连至会员详细页面">' . $v->source_transferaccount . '</a>',
            // 'token_balance'        => $tokenBalance,
            // 'cash_balance'         => $cashBalance,
            // 'payout'               => $v->payout,
            // 'detail_trans'         => $modalHtml,
        ];
    }
    return $initDatas;
}



function query_str_sql($query_str)
{
    // 帶入查詢區間，及現在時間。加載更多時，則帶入之前時間，使得sql查詢區間不會更新，畫面資料量正確
    $generate_time = array();
    $generate_time = time_interval($query_str['sel_date'], $query_str['time']);

    // 將美東日期格式轉成台灣時區
    $query_str['sdate'] = gmdate('Y-m-d H:i:s', strtotime($generate_time['s'] . ':00 -04') + 8 * 3600) . '+08';
    $query_str['edate'] = gmdate('Y-m-d H:i:s', strtotime($generate_time['e'] . ' -04') + 8 * 3600) . '+08';

    // 組成sql 查詢條件
    $getTransactionType = getTransactionType();
    if ($query_str['sel_cate'] != '' and $query_str['sel_cate'] != 'cate_all_val') {
        $query_where['sel_cate'] = 'transaction_category IN (\'' . implode("','", $getTransactionType[$query_str['sel_cate']]) . '\')';
    }
    $query_where['account'] = 'source_transferaccount IN ( \'' . implode("','", $query_str['account']) . '\')';
    $query_where['sdate']   = 'transaction_time >=\'' . $query_str['sdate'] . '\'';
    $query_where['edate']   = 'transaction_time <=\'' . $query_str['edate'] . '\'';

    return $query_where;
}

// 輸出成xlsx的時間檔名
function filter_time_filename($timevalue,$deadline){
 
    $current_date      = gmdate('Ymd', $deadline+-4 * 3600);
    $current_date_time = gmdate('Ymd', $deadline+-4 * 3600);
    $today_s           = $current_date;
    $today_e           = $current_date_time;
    $yesterday_s       = date("Ymd", strtotime("$current_date - 1 days"));
    $yesterday_e       = date("Ymd", strtotime("$current_date - 1 days"));
    $this_week_s       = date("Ymd", strtotime("$current_date - " . date('w', strtotime("$current_date")) . "days"));
    $this_week_e       = $current_date_time;
    $this_month_s      = date("Ym", strtotime("$current_date")) . '01';
    $this_month_e      = $current_date_time;
    $lastmonth_s       = date("Ym", strtotime("$current_date - 1 month")) . '01';
    $lastmonth_e       = date("Ymt", strtotime("$current_date - 1 month"));

    // 查看日期區間
    // var_dump($current_date);var_dump($current_date_time);var_dump($today_s);var_dump($today_e);var_dump($yesterday_s);var_dump($yesterday_e);var_dump($this_week_s);var_dump($this_week_e);var_dump($this_month_s);var_dump($this_month_e);var_dump($lastmonth_s);var_dump($lastmonth_e);    die();
    $date_ary = array();
    $start    = $end    = '';
    switch ($timevalue) {
        case 'today':
            $start = $today_s;
            $end   = $today_e;
            $filename_str = $start;
            break;
        case 'yesterday':
            $start = $yesterday_s;
            $end   = $yesterday_e;
            $filename_str = $start;
            break;
        case 'week':
            $start = $this_week_s;
            $end   = $this_week_e;
            $filename_str = $this_week_s.'~'.$this_week_e;
            break;
        case 'month':
            $start = $this_month_s;
            $end   = $this_month_e;
            $filename_str = $this_month_s.'~'.$this_month_e;
            break;
        case 'lastmonth':
            $start = $lastmonth_s;
            $end   = $lastmonth_e;
            $filename_str = $lastmonth_s.'~'.$lastmonth_e;
            break;
        default:
            break;
    }
    $date_ary = $filename_str;
    return $date_ary;
}

// 檔名組合
function combine_filename($sel_date,$transpage,$sel_member,$query_name){

    if($transpage == 'm'){
        // 匯出自己交易明細
        // 日期區間A_日期區間B_translogquery_登入的會員帳號 
        $file_name_result = 'translogquery_'.$sel_date.'_'.$_SESSION['member']->account;

    }elseif($transpage == 'a' AND in_array($sel_member,$query_name)){
        // 特定
        // agenttranslogquery_日期區間A_日期區間B_被搜尋的下線帳號
        
        $file_name_result = 'agtranslogquery_'.$sel_date.'_'.$sel_member;

    }else{
        // 全部下線
        // 日期區間A_日期區間B_agenttranslogquery_代理商帳號
        $file_name_result = 'agtranslogquery_'.$sel_date.'_'.$_SESSION['member']->account;
    }

    return $file_name_result;
  
}