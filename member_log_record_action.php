<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 會員登入紀錄action
// File Name:	member_log_record_action.php
// Author:		Mavis
// Related:  
// Table :
// Log:
// ----------------------------------------------------------------------------

require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

require_once dirname(__FILE__) ."/member_log_record_lib.php";

// ----------------------------------------------------------------------------
// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------

//可根據指定資料類型來過濾變數
function my_filter($var, $type = "string"){
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

$action = isset($_GET['action']) ? my_filter($_GET['action'], "string") : '';
// var_dump($action);die();

function combineData($data){
    
    $arr=[];
    
    foreach($data as $v){
        $arr[] =[
            'id'                => $v->id,
            'occurtime'         => gmdate('m/d',strtotime($v->occurtime)-4*3600).'('.convert_to_fuzzy_time($v->occurtime).')',
            'who'               => $v->who,
            'message'           => $v->message,
            'agent_ip'          => $v->agent_ip.' ('.ip_location_text($v->ip_region).')',
            // 'fingerprinting_id' => $v->fingerprinting_id,
            'http_user_agent'   => get_device($v->http_user_agent),
            'detail_user_agent' => $v->http_user_agent,
            'detail_occurtime'  => gmdate('Y/m/d H:i:s',strtotime($v->occurtime)-4*3600), // 美東時間
            'target_users'      => $v->target_users
        ];
    }

    return $arr;
}

// 取最近10筆資料
$sql = get_user_log();
$result = runSQLall($sql);

// 沒資料
if(empty($result[0])){
    echo json_encode(['action' => $action, 'data'=>'无资料','status' => 'fail']);
    die();
}

unset($result[0]);
$data = combineData($result);

echo json_encode(['action' => $action, 'data'=> $data, 'status' => 'success']);

?>