<?php
// ----------------------------------------------------------------------------
// Features:	後台 - 顯示某個會員的組織樹狀圖，對應 ajax 功能
// File Name:	member_treemap_action.php
// Author:		Webb Lu
// Related:		member_treemap.php
// Log:
// ----------------------------------------------------------------------------

// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";
require_once dirname(__FILE__) ."/lib_member_tree.php";
require_once dirname(__FILE__) ."/lib_agents_setting.php";

// ----------------------------------------------------------------------------
// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
$function_title 		= '代理商组织图_action';
// 擴充 head 內的 css or js
$extend_head				= '';
// 放在結尾的 js
$extend_js					= '';
// body 內的主要內容
$indexbody_content	= '';
// 系統訊息選單
$messages 					= '';
// ----------------------------------------------------------------------------

// 查詢特定對象的下線，預設四代，並產生 json data for dataTable
function get_children_list($userid, $options = []) {
  global $tr;
  extract($options);
  $depth = $depth ?? 4;
  $is_json = $is_json ?? false;
  $sql_order = $sql_order ?? '';

  $sql = <<<SQL
WITH RECURSIVE upperlayer(id, parent_id, account, therole, enrollmentdate, nickname, favorablerule, grade, favorablerule, feedbackinfo, status, depth) AS (
  SELECT id, parent_id, account, therole, enrollmentdate, nickname, favorablerule, grade, favorablerule, feedbackinfo, status, 1 FROM root_member WHERE parent_id = :userid
  UNION ALL
  SELECT p.id, p.parent_id, p.account, p.therole, p.enrollmentdate, p.nickname, p.favorablerule, p.grade, p.favorablerule, p.feedbackinfo, p.status, u.depth+1 FROM root_member p
  INNER JOIN upperlayer u ON u.id = p.parent_id AND u.depth < :depth
), agent_tree_reuslt AS (
  SELECT * FROM upperlayer AS agent_tree LEFT JOIN (
    SELECT  parent_id, count(parent_id) as parent_id_count FROM root_member GROUP BY parent_id) AS agent_user_count
  ON agent_tree.id = agent_user_count.parent_id ORDER BY agent_tree.parent_id , agent_tree.depth, agent_tree.id
)

SELECT * FROM agent_tree_reuslt $sql_order
SQL;
$sql_binds = ['userid' => $userid, 'depth' => $depth];
  if(isset($offset) && isset($limit)):
    $sql .= ' OFFSET :offset LIMIT :limit';
    $sql_binds['offset'] = $offset;
    $sql_binds['limit'] = $limit;
  endif;

  $result_sql = runSQLall_prepared($sql, $sql_binds);
  $children_list = [];
  $mapping = [
    // 'therole' => ['A' => '代理商', 'M' => '会员', 'R' => '管理员'],
    'therole' => [
      'M' => '<a href="#" title="'.$tr['member'].'"><span class="glyphicon glyphicon-user"></span> '.$tr['member'].'</a>',
      'A' => '<a href="#" title="'.$tr['agent'].'"><span class="glyphicon glyphicon-knight"></span> '.$tr['agent'].'</a>',
      'R' => '<a href="#" title="'.$tr['management'].'"><span class="glyphicon glyphicon-king"></span> '.$tr['management'].'</a>'
    ],
    // 'status' => ['0' => '停用', '1' => '启用', '2' => '钱包冻结']
    'status' => [
      '0' => '<span class="label label-danger">'.$tr['disabled'].'</span>',
      '1' => '<span class="label label-primary">'.$tr['normal'].'</span>',
      '2' => '<span class="label label-warning">'.$tr['wallet frozen'].'</span>'
    ]
  ];

  foreach ($result_sql as $child):
    $children_list[] = [
      'id'	=> $child->id,
      'therole'	=> $mapping['therole'][$child->therole],
      'account'	=> $child->account,
      // 'enrollmentdate'	=> $child->enrollmentdate,
      'enrollmentdate'	=> date("Y-m-d H:m:s", strtotime($child->enrollmentdate)).'(约'.convert_to_fuzzy_time($child->enrollmentdate).')',
      'nickname'	=> $child->nickname,
      'status'	=> $mapping['status'][$child->status],
      'depth'	=> $child->depth,
      'parent_id_count'	=> $child->parent_id_count
    ];
  endforeach;

return $is_json ? json_encode($children_list) : $children_list;
}

// datatable server process 分頁處理及驗證參數
function get_datatable_paginator() {
  // 程式每次的處理量 -- 當資料量太大時，可以分段處理。 透過 GET 傳遞依序處理。
  if(isset($_GET['length']) AND $_GET['length'] != NULL ) {
    $current_per_size = filter_var($_GET['length'],FILTER_VALIDATE_INT);
  }else{
    $current_per_size = $page_config['datatables_pagelength'] ?? 500;
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

  // datatable 的排序請求參數
  // 處理 datatables 傳來的排序需求
  if(isset($_GET['order'][0]) AND $_GET['order'][0]['column'] != ''){
    if($_GET['order'][0]['dir'] == 'asc'){ $sql_order_dir = 'ASC';
    }else{ $sql_order_dir = 'DESC';}
    if($_GET['order'][0]['column'] == 0) $sql_order = 'ORDER BY id '.$sql_order_dir;
    elseif($_GET['order'][0]['column'] == 2) $sql_order = 'ORDER BY account '.$sql_order_dir;
    elseif($_GET['order'][0]['column'] == 3) $sql_order = 'ORDER BY enrollmentdate '.$sql_order_dir;
    elseif($_GET['order'][0]['column'] == 4) $sql_order = 'ORDER BY nickname '.$sql_order_dir;
    elseif($_GET['order'][0]['column'] == 6) $sql_order = 'ORDER BY depth '.$sql_order_dir;
    elseif($_GET['order'][0]['column'] == 7) $sql_order = 'ORDER BY parent_id_count '.$sql_order_dir;
    else{ $sql_order = 'ORDER BY id ASC';}
   }else{ $sql_order = 'ORDER BY id ASC';}

  return $paginator = compact('current_per_size', 'current_page_no', 'secho', 'sql_order');
}

function convert_to_fuzzy_time($times){
    date_default_timezone_set('America/St_Thomas');
    $unix   = strtotime($times);
    $now    = time();
    $diff_sec   = $now - $unix;

    if($diff_sec < 60){
        $time   = $diff_sec;
        $unit   = "秒前";
    }
    elseif($diff_sec < 3600){
        $time   = $diff_sec/60;
        $unit   = "分前";
    }
    elseif($diff_sec < 86400){
        $time   = $diff_sec/3600;
        $unit   = "小時前";
    }
    elseif($diff_sec < 2764800){
        $time   = $diff_sec/86400;
        $unit   = "天前";
    }
    elseif($diff_sec < 31536000){
        $time   = $diff_sec/2592000;
        $unit   = "个月前";
    }
    else{
        $time   = $diff_sec/31536000;
        $unit   = "年前";
    }

    return (int)$time .$unit;
}

// 檢查 $query_member_id 是否合理，如果 SESSION ID 是查詢對象的祖先/自身才允許往下查
function is_query_member_valid($ancestors) {
  $is_query_member_id_valid = false;
  foreach($ancestors as $parent_info) {
    if($parent_info->id == $_SESSION['member']->id):
    $is_query_member_id_valid = true;
    break;
    endif;
  }
  return $is_query_member_id_valid;
  }
// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
$is_agent = isset($_SESSION['member']) && $_SESSION['member']->therole == 'A';
$is_admin = isset($_SESSION['member']) && $_SESSION['member']->therole == 'R';
$action = filter_input(INPUT_GET, 'a');
$userid = filter_input(INPUT_GET, 'uid', FILTER_VALIDATE_INT);

// 權限驗證
$is_agent OR $is_admin OR die('不合法的訪問');

switch($action):
  case 'datatable_children_list':
    // 要加上驗證，驗證查詢 id 是否為自己的下線(後台用)
    // 要加上驗證，驗證查詢 id 是否為登入者id(前台用)
    $query_id = filter_input(INPUT_GET, 'uid', FILTER_VALIDATE_INT);
    $userid = ! $query_id ? $_SESSION['member']->id : $query_id;

    $ancestors = MemberTreeNode::getPredecessorList($userid);
    $ancestors = array_map(function($ancestor) {
      $ancestor->feedbackinfo = getMemberFeedbackinfo($ancestor);
      return $ancestor;
    }, $ancestors);

    is_query_member_valid($ancestors) OR die('不合法測試：試圖查詢組織中非下線成員');
    // $userid == $_SESSION['member']->id OR die('不合法的訪問：查詢 id 錯誤');
    $secho = filter_input(INPUT_GET, '_') ?? '1';

    $paginator = get_datatable_paginator();
    $children_list_options = [
      'offset' => $paginator['current_page_no'],
      'limit' => $paginator['current_per_size'],
      'sql_order' => $paginator['sql_order']
    ];
    $children_list = get_children_list($userid);
    $children_list_paginated = get_children_list($userid, $children_list_options);
    $data_table_json = [
      "sEcho" => intval($paginator['secho']),
      "iTotalRecords" => intval($paginator['current_per_size']),
      "iTotalDisplayRecords" => (int) count($children_list),
      "data" => $children_list_paginated
    ];
    // var_dump($children_list);
    header_remove();
    header('Content-Type: application/json');
    echo json_encode($data_table_json);

    break;
  default:
    break;
endswitch;

?>
