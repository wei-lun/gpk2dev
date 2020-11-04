<?php
// ----------------------------------------------------------------------------
// Features:	前台 -- 行銷優惠活動專區
// File Name:	promotions_action.php
// Author:		Mavis
// Related:
// Log:
// 依據後台開啟的優惠活動, 引導進入對應的行銷活動頁面. 前台要包裝特別在另外連結。
// ----------------------------------------------------------------------------

require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";


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

// var_dump($_REQUEST);die();
$query_var =  $query_anywhere = array();
$sql = '';
$action                 = isset($_GET['action']) ? my_filter($_GET['action'], "string") : '';
$query_var['cate']      = isset($_POST['sdata']['cate']) ? my_filter($_POST['sdata']['cate'],"string"):""; // 類別
$query_var['count']     = isset($_POST['sdata']['count']) ? my_filter($_POST['sdata']['count'],"int"):""; // 筆數
$query_var['list']      = isset($_POST['sdata']['list']) ? my_filter($_POST['sdata']['list'],"string"):""; // 全部優惠

$domain = $_SERVER['HTTP_HOST'];
$determine = $config['site_style'] ;
// var_dump($query_var);die();


// 組成sql字串
function combineSql($query_var,$determine,$domain,$show_limit){

    if($determine == 'mobile'){
        $domain_name = 'mobile_domain';
        $show = 'mobile_show';
    }else{
        $domain_name = 'desktop_domain';
        $show = 'desktop_show';
    }
    
    if($query_var['list'] == 'end_promotion'){
        $list_endtime = ' <=';
        $off = ' OFFSET '.$query_var['count'];
    }else{
        $list_endtime = ' >=';
        $off = ' OFFSET '.$query_var['count'];
    }

    if($query_var['cate'] != 'all_cate'){
        $sql = <<<SQL
            SELECT *,to_char((endtime AT TIME ZONE 'AST'),'YYYY-MM-DD HH24:MI:SS') AS the_endtime FROM root_promotions 
                WHERE classification = '{$query_var['cate']}'
                    AND {$domain_name} = '{$domain}' 
                    AND status = 1
                    AND {$show} = 1
                    AND classification_status = 1
                    AND endtime {$list_endtime} current_timestamp
                        ORDER BY endtime ASC 
                        {$off}
                        LIMIT {$show_limit}  
SQL;
    }else{
        // all
        $sql = <<<SQL
            SELECT *,to_char((endtime AT TIME ZONE 'AST'),'YYYY-MM-DD HH24:MI:SS') AS the_endtime  FROM root_promotions 
                WHERE {$domain_name} = '{$domain}' 
                    AND status = 1
                    AND {$show} = 1
                    AND classification_status = 1
                    AND endtime {$list_endtime} current_timestamp
                        ORDER BY endtime ASC
                        {$off}
                        LIMIT {$show_limit}
SQL;
    }

    return $sql;
}

function combineDatasArr($data){
    $arr = [];
    foreach ($data as $v) {
        $arr[] = [
            'id'            => $v->id,
            'name'          => $v->name,
            'effecttime'    => $v->effecttime,
            'endtime'       => (date('Y-m-d h:i:s',strtotime($v->endtime)+ 12*3600)),
            'status'        => $v->status
        ];
    }
  return $arr;
}

// limit
$show_limit = '7';
// 實際要撈多加一筆，判斷是否已至最底
$real_show_limit = $show_limit + 1;


$sql = combineSql($query_var,$determine,$domain,$real_show_limit);
// var_dump($sql);die();
$result = runSQLall($sql);
$count = $result[0];

// 載入更多文字
if($result[0] > $show_limit){
    $counting_data = '1';
}else{
    $counting_data = '0';
}

if(empty($result[0])){
    echo json_encode(['action'=> $action,'cate'=> $query_var['cate'], 'status' => 'fail', 'count' =>  $count, 'data' => '查无资料!','list'=> $query_var['list'],'counting_data'=> $counting_data]);
    die();
}

unset($result[0]);
unset($result[$real_show_limit]);

$data = combineDatasArr($result);
// var_dump($data);die();
echo json_encode(['action'=> $action,'cate'=> $query_var['cate'],'status' => 'success', 'count' =>  $count, 'data' => $data,'list'=> $query_var['list'],'counting_data'=> $counting_data]);

?>