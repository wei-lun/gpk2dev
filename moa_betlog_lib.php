<?php
// ----------------------------------------------------------------------------
// Features:  前台 行動版 --投注紀錄lib
// File Name: moa_betlog_lib.php
// Author:    YaoYuan
// Related:   
// DB Table:  
// Log:
// ----------------------------------------------------------------------------
// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";
require_once dirname(__FILE__) ."/lib_member_tree.php";
// 前台投注紀錄專用檔
require_once dirname(__FILE__) ."/config_betlog.php";

// 函數：找出該代理商的所有下線，轉成json
function find_downline_member($id,$betpage='a'){
    $all_downline_data = MemberTreeNode::getSuccessorList($id);
    $downline_ary      = array();
    foreach ($all_downline_data as $k => $val) {
        if($betpage=='a'){
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

// 組成sql字串
function combineSelecteSql(){
  $sql = <<<SQL
    SELECT
        rowid,
        casino_account,
        game_name,
        game_category,
        betvalid,
        betamount,
        betresult ,
        to_char((bettime AT TIME ZONE 'AST'),'YYYY-MM-DD HH24:MI:SS') as bet_time,
        to_char((receivetime AT TIME ZONE 'AST'),'YYYY-MM-DD HH24:MI:SS') as receive_time,
        case casinoid when 'MEGA' then 'GPK' ELSE casinoid  END,
        favorable_category,
        status
    FROM
        betrecordsremix
    WHERE
SQL;
    return $sql;
}



// sql 解出datatable所需資料
function combineDataTableInitData($data,$gnametouser){
    global $tr;
    global $transaction_category;

    // 遊戲中英文名稱
    $gamename_i18n=load_gamelist_i18n();


    $show_status=['0'=>$tr['Unpaid'],'1'=>$tr['Paid'],'2'=>$tr['modified']];

    $initDatas = [];
    foreach ($data as $k => $v) {
        $show_receive_time=$show_betresult='';

        // 當娛樂城為PT時，取括號之前的遊戲名稱，否則取遊戲名稱
        if ($v->casinoid == 'PT') {
            $gamenamekey = trim(strtolower(explode("(", $v->game_name)[0]));
        } else {
            $gamenamekey = trim(strtolower($v->game_name));
        }

        if ($v->status == 0) {
            $show_receive_time = '未派彩';
            $show_betresult    = '-';
        }else{
            $show_receive_time = $v->receive_time;
            if($v->betresult>=0){
                $show_betresult='<span class="text-danger version_color">$'.$v->betresult.'</span>';
            }else{
                $show_betresult='<span class="text-success version_color">$'.$v->betresult.'</span>';
            }
        }               

        $initDatas[] = [
            'id'                 => $k,
            'rowid'              => $v->rowid,
            'user_account'       => $gnametouser[$v->casino_account],
            'game_name'          => empty($gamename_i18n->$gamenamekey) ? $v->game_name : $gamename_i18n->$gamenamekey,
            'game_category'      => empty($tr[$v->game_category]) ? $v->game_category : $tr[$v->game_category],
            'betvalid'           => $v->betvalid,
            'betamount'          => $v->betamount,
            'betresult'          => $show_betresult,
            'bet_time'           => $v->bet_time,
            'receive_time'       => $show_receive_time,
            'casinoid'           => $v->casinoid,
            'favorable_category' => $v->favorable_category,
            'status'             => $show_status[$v->status],
        ];
    }
    return $initDatas;
}


// 會員帳號對映遊戲帳號陣列
function get_game_account($account_ary){
    $sql_acc='(\''.implode("','",$account_ary).'\')';
    $query_sql = <<<SQL
    SELECT
      root_member.id,
      account,
      casino_accounts
    FROM root_member
    JOIN root_member_wallets ON root_member.id = root_member_wallets.id
    WHERE account IN {$sql_acc}
SQL;
    $query_result = runSQLall($query_sql, 0, 'r');
    unset($query_result[0]);
    // var_dump($query_result);die();
    
    $game_account = array();

    foreach ($query_result as $row) {
        $casino_acc = json_decode($row->casino_accounts, 'true');
        if (count($casino_acc) >= 1) {
            // $caccount = array();
            $game_account[$row->id]['account']=$row->account;
            foreach ($casino_acc as $casinokey => $casinoval) {
                  $game_account[$row->id]['gaccount'][] = $casinoval['account'];
            }
            $game_account[$row->id]['gaccount']=array_unique($game_account[$row->id]['gaccount']);
        }
    }
      // var_dump($game_account);
      // -----result------
      // 1785 => 
      //   array (size=2)
      //     'account' => string 'y0012' (length=5)
      //     'gaccount' => 
      //       array (size=3)
      //         0 => string 'kt120000001785a' (length=15)
      //         1 => string 'kt120000001785b' (length=15)
      //         2 => string 'kt120000001785c' (length=15)
    return $game_account;
}


// 傳入帳號及遊戲帳號，轉出成 IN (遊戲帳號)的sql字串
function gameacc_tosql($gacct){
    $gameaccountarray = array();
    foreach ($gacct as $key => $value) {
        foreach ($value['gaccount'] as $subkey => $subvalue) {
            $gameaccountarray[] = $subvalue;
        }}
    $gameaccountarray['sql'] = ' LOWER(casino_account) IN (\'' . implode("','", $gameaccountarray) . '\')';
    return $gameaccountarray['sql'];
}

// 傳入帳號及遊戲帳號，轉出成$[遊戲帳號]=帳號
function gname_map_user($get_game_account){
    $gname_map_user_ary = array();
    foreach ($get_game_account as $key => $val) {
        foreach ($val['gaccount'] as $subkey => $subval) {
            $gname_map_user_ary[$subval] = $val['account'];
        }
    }
    return $gname_map_user_ary;
}

// 注單遊戲名稱，產生中英文對照表
function load_gamelist_i18n(){
    $GameNameI18          = new stdClass();
    $translatetionChn_sql = 'SELECT DISTINCT trim(lower("gamename")) enlowgamename , "gamename_cn","casino_id" FROM "casino_gameslist";';
    // var_dump($translatetionChn_sql);--撈出sql語法
    $translatetionChn_sql_result = runSQLall($translatetionChn_sql, 0, 'r');
    // var_dump($translatetionChn_sql_result);// --執行結果
    foreach ($translatetionChn_sql_result as $key => $value) {
        //將索引值給$key，整個英中遊戲名稱給$value
        if ($key != 0) {
            //對照表的遊戲名稱，以中文為主，否則英文
            $GameNameI18->{$value->enlowgamename} = empty($value->gamename_cn) ? $value->enlowgamename : $value->gamename_cn;
        }
    }

    // get MEGA's gamelist
    require_once dirname(__FILE__) . "/casino/MEGA/lobby_megagame_lib.php";
    $MEGA_API_data['version'] = 'v2';
    $MEGA_gamelist            = mega_gpk_api('GamenameLists', '0', $MEGA_API_data);
    // var_dump($MEGA_gamelist);
    if ($MEGA_gamelist['errorcode'] == 0 and $MEGA_gamelist['Status'] == 1 and $MEGA_gamelist['count'] > 0) {
        foreach ($MEGA_gamelist['Result'] as $key => $value) {
            $value                         = get_object_vars($value);
            $GameNameI18->{$value['enus']} = $value['zhcn'];
        }
    }

    return $GameNameI18;
}

// 取出娛樂城id
function casino_kind(){
    $show_html='';
    $sql = <<<SQL
    SELECT casinoid
    FROM casino_list
SQL;
    $sql_result = runSQLall($sql, 0, 'r');
    unset($sql_result[0]);
    foreach ($sql_result as $v) {
        if($v->casinoid=='MEGA'){
            $casino_name_change='GPK';
        }else{
            $casino_name_change = $v->casinoid;
        }
        $show_html .=  '<tr><td class="cl_casino" data-casinoval="'.$v->casinoid.'">'.$casino_name_change.'</td></tr>';
    }
    return $show_html;
}