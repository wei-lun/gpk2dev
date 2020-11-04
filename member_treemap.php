<?php
// ----------------------------------------------------------------------------
// Features:	後台 - 顯示某個會員的組織樹狀圖（四層）
// File Name:	member_treemap.php
// Author:		Barkley
// Related:		index.html
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

// var_dump($_SESSION);


// ----------------------------------------------------------------------------
// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();

// 偵測是否為手機版頁面，如果是，則跳警告並導到推廣註冊頁
mobile_device_detect();
// ----------------------------------------------------------------------------





// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
$function_title 		= '代理商组织图';
// 擴充 head 內的 css or js
$extend_head				= '';
// 放在結尾的 js
$extend_js					= '';
// body 內的主要內容
$indexbody_content	= '';
// 系統訊息選單
$messages 					= '';
// ----------------------------------------------------------------------------
// 導覽列
$navigational_hierarchy_html = '
<ul class="breadcrumb">
  <li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
  <li><a href="member.php">'.$tr['Member Centre'].'</a></li>
  <li><a href="agencyarea.php">'.$tr['agencyarea title'].'</a></li>
  <li class="active">'.$function_title.'</li>
</ul>
';
// ----------------------------------------------------------------------------



// -------------------------------------------------------------------------
// 尋找符合業績達成的上層, 共 n 代. 直到最上層 root 會員。
// 再以計算出來的代數 account 判斷，哪些代數符合達成業績標準的會員。
// -------------------------------------------------------------------------
// 1.1 以節點找出使用者的資料 -- from root_member
// -------------------------------------------------------------------------
function find_member_node($member_id, $tree_level) {

// 加上 memcached 加速,宣告使用 memcached 物件 , 連接看看. 不能連就停止.
$memcache = new Memcached();
$memcache->addServer('localhost', 11211) or die ("Could not connect memcache server !! ");
// 把 query 存成一個 key in memcache
$key = 'member_treemap_findroot'.$member_id;
$key_alive_show = sha1($key);

// 取得看看記憶體中，是否有這個 key 存在, 有的話直接抓 key
$getfrom_memcache_result = $memcache->get($key_alive_show);
if(!$getfrom_memcache_result) {

		$tree_level = $tree_level;
		//$tree_level = 1; 不論 status 為何, 都要找出來. 否則會 lost data 問題.
		$member_sql = "SELECT id, account, parent_id, therole FROM root_member WHERE id = '$member_id';";
		//var_dump($member_sql);
		$member_result = runSQLall($member_sql, 0, 'r');
		//var_dump($member_result);
		if($member_result[0]==1){
			$tree = $member_result[1];
			$tree->level = $tree_level;
		}else{
			$logger ="ID = $member_id 資料遺失, 請聯絡客服人員處理.";
			die($logger);
		}


// save to memcached ref:http://php.net/manual/en/memcached.set.php
$memcached_timeout = 120;
$memcache->set($key_alive_show, $tree, time()+$memcached_timeout) or die ("Failed to save data at the memcache server");
//echo "Store data in the cache (data will expire in $memcached_timeout seconds)<br/>\n";
}else{
	// 資料有存在記憶體中，直接取得 get from memcached
	$tree = $getfrom_memcache_result;
}

//var_dump($tree);
return($tree);
}


// -------------------------------------------------------------------------
// 1.2 找出上層節點的所有會員，直到 root -- from root_member
// -------------------------------------------------------------------------
function find_parent_node($member_id) {

	// 最大層數 100 代
	$tree_level_max = 100;

	$tree_level = 0;
	// treemap 為正常的組織階層
	$treemap[$member_id][$tree_level] = find_member_node($member_id, $tree_level);

	// $treemap_performance 唯有達標的組織階層
	//$treemap_performance[$member_id][$tree_level] = find_agent_performance_node($member_id, $tree_level);
	while($tree_level<=$tree_level_max) {
		$m_id = $treemap[$member_id][$tree_level]->parent_id;
		$m_account = $treemap[$member_id][$tree_level]->account;
		$tree_level = $tree_level+1;
		// 如果到了 root 的話跳離迴圈。表示已經到了最上層的會員了。
		if($m_account == 'root') {
			break;
		}else{
			$treemap[$member_id][$tree_level] = find_member_node($m_id, $tree_level);
		}
	}

	// var_dump($treemap);
	return($treemap);
}
// -------------------------------------------------------------------------

// ----------------------
// 依據 id 找出 parent_id 為這個 id 的使用者
// ----------------------
function find_children($root_id,$root_name='') {

	// $root_id = 1;
	// level (root) , 找出上層為 root id 的 member. 但是帳號不能為 root
	$sql_root = "SELECT id, account, parent_id, therole FROM root_member WHERE parent_id = $root_id  AND account != 'root';";
	$rt = runSQLALL($sql_root, 0, 'r');

	// 該節點下面，沒有資料就不要做
	if($rt[0] > 0) {
		$t0['name'] = '('.$root_id.')'.$root_name;
    $t0['color'] = "#4fc1e9";
    $t0['linkurl'] = $_SERVER['SCRIPT_NAME'] . '?id=' . $root_id;

		for($i=0;$i<$rt[0];$i++) {
			$j = $i+1;
			// 樹狀中顯示的文字
			$t[$i]['name'] = '('.$rt[$j]->id.')'.$rt[$j]->account;
      // 顏色屬性參數
      if($rt[$j]->therole == 'A'){
          $t[$i]['color'] = "#8cc152";
      }else{
          $t[$i]['color'] = "lightsteelblue";
      }
      $t[$i]['linkurl'] = $_SERVER['SCRIPT_NAME'] . '?id=' . $rt[$j]->id;
			// $t[$i]['size'] = rand(500,1000);
			// $t[$i]['balance'] = rand(9500,91000);
			$t0['children'][$i]	= $t[$i];
			// 下一個 children 的 id list
			$next_children[$i] = $rt[$j]->id;
		}
	}else{
		$next_children = NULL;
		$t0 = NULL;
	}

	// 下一個 children 的 id list
	//var_dump($next_children);
	// 每一個 id 的 account list
	//var_dump($t0);


	$r['next_children'] = $next_children;
	$r['account_list'] = $t0;

	return($r);
}
// ----------------------
// 依據 id 找出 parent_id 為這個 id 的使用者 END
// ----------------------

// 查詢特定對象的下線，預設四代，並產生 json data for dataTable
function get_children_list($userid, $depth = 4, $is_json = true) {
	$sql =<<<SQL
SELECT * FROM
(WITH RECURSIVE upperlayer(id, parent_id, account, therole, enrollmentdate, nickname, favorablerule, grade, favorablerule, feedbackinfo, status, depth) AS (
SELECT id, parent_id, account, therole, enrollmentdate, nickname, favorablerule, grade, favorablerule, feedbackinfo, status, 1
FROM root_member WHERE parent_id= :userid
UNION ALL
SELECT p.id, p.parent_id, p.account, p.therole, p.enrollmentdate, p.nickname, p.favorablerule, p.grade, p.favorablerule, p.feedbackinfo, p.status, u.depth+1
FROM root_member p
INNER JOIN upperlayer u ON u.id = p.parent_id
WHERE u.depth < :depth
)
SELECT * FROM upperlayer ) as agent_tree

LEFT JOIN (SELECT  parent_id, count(parent_id) as parent_id_count FROM root_member GROUP BY parent_id) AS agent_user_count
ON agent_tree.id = agent_user_count.parent_id ORDER BY agent_tree.parent_id , agent_tree.depth, agent_tree.id
SQL;

	$result_sql = runSQLall_prepared($sql, ['userid' => $userid, 'depth' => $depth]);
	// var_dump($result_sql);
	$children_list = [];
	foreach ($result_sql as $child):
		$children_list[] = [
			'id'	=> $child->id,
			'therole'	=> $child->therole,
			'account'	=> $child->account,
			'enrollmentdate'	=> $child->enrollmentdate,
			'nickname'	=> $child->nickname,
			'status'	=> $child->status,
			'depth'	=> $child->depth,
			'parent_id_count'	=> $child->parent_id_count
		];
	endforeach;

return $is_json ? json_encode($children_list) : $children_list;
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




// 有登入，有錢包才顯示。只有代理商可以進入
if(isset($_SESSION['member']) AND ($_SESSION['member']->therole == 'A' OR $_SESSION['member']->therole == 'R') AND  $_SESSION['member']->therole != 'T') {

  // -------------------------------------------------------------------
  // 代理中心的 menu - in lib_menu.php , 传入预设的档名 link
  // -------------------------------------------------------------------
  $menu_agentadmin_html = menu_agentadmin('member_treemap.php');
  // -------------------------------------------------------------------


  $page_title = '
  <div class="myagencyarea_title"">
  '.$function_title.'
  </div>';


  // 以登入的使用者，為預設的 id
  // echo '以登入的使用者，為預設的 id. ';
  $query_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
  $member_id = ! $query_id ? $_SESSION['member']->id : $query_id;

  $ancestors = MemberTreeNode::getPredecessorList($member_id);
  $ancestors = array_map(function($ancestor) {
    $ancestor->feedbackinfo = getMemberFeedbackinfo($ancestor);
    return $ancestor;
  }, $ancestors);

  is_query_member_valid($ancestors) OR die('不合法測試：試圖查詢組織中非下線成員');

  $sql = "SELECT * FROM root_member WHERE id = '".$member_id."';";
  $r = runSQLALL($sql, 0, 'r');
  $user = $r[1];
  //var_dump($sql);


  // --------------------------------------------------------------------------------
  // (0) 指定查詢的帳號，往上查詢到 root 的層數
  // --------------------------------------------------------------------------------

  // -------------------------------------------
  // 找出會員所在的 tree 直到 root
  // -------------------------------------------
  $findroot_tree = find_parent_node($member_id);
  //var_dump($findroot_tree);
  $item2root_html = '';
  // 計算有幾代
  // $item2root_count = 0;
  // $findroot_tree_count = count($findroot_tree[$member_id]);
  // for($j=($findroot_tree_count-1);$j>=0;$j--){
  // 	$find_pnode = $findroot_tree[$member_id][$j];

  // 	if($find_pnode->therole == 'A' OR $find_pnode->therole == 'R') {
  // 		$item_mark = '<span class="glyphicon glyphicon-knight" aria-hidden="true"></span>';
  // 	}else{
  // 		$item_mark = '<span class="glyphicon glyphicon-user" aria-hidden="true"></span>';
  // 	}
  // 	if($j == 0) {
  // 		$account_icon_classcolor = 'btn btn-primary btn-sm';
  // 	}else{
  // 		$account_icon_classcolor = 'btn btn-default btn-sm';
  // 	}
  // 	$item2root_html = $item2root_html.'<button class="'.$account_icon_classcolor.'" role="button">'.$item_mark.$find_pnode->account.'</button>&nbsp;';
  // 	if($j>0) {
  // 		$item2root_html = $item2root_html.'<span class="glyphicon glyphicon-arrow-right" aria-hidden="true"></span>';
  // 	}


  // 	$item2root_count = $item2root_count + 1;
  // }

  // $item2root_html = '<p><span class="glyphicon glyphicon glyphicon-tree-deciduous" aria-hidden="true"></span>帳號往上到 root 共有'.$item2root_count.'層</p>'.$item2root_html.'<hr>';
  // // 從使用者到 root 的路徑及階層數量。
  // var_dump($item2root_html);

  $show_href = function($arrays) {
    $flags = [];
    $flag = false;
    // session 在 array 的 index
    foreach($arrays as $index => $value) {
      if ($value->id == $_SESSION['member']->id) $flag = true;
      $flags[$index] = $flag;
    }
    return $flags;
  };

  $href_flags = $show_href(array_reverse($ancestors));
  $user2root_Arr = array_map(function($ancestor, $href_flag) use ($member_id){
    $html_format = '<button class="btn btn-sm btn-%s" onclick="location.href=\'%s\';"><span class="glyphicon glyphicon-%s" aria-hidden="true"></span>%s</button>';
    $btn_style = $ancestor->id == $member_id ? 'info' : 'default';
    $btn_style = $ancestor->id == $_SESSION['member']->id ? 'primary' : $btn_style;
    $user_mark = ($ancestor->therole == 'A' OR $ancestor->therole == 'R') ? 'knight' : 'user';
    $href = $href_flag ? $_SERVER['SCRIPT_NAME'] . '?id=' . $ancestor->id : 'javascript:void(0)';
    $account = $ancestor->account == 'root' ? '公司' : $ancestor->account;

    return sprintf($html_format, $btn_style, $href, $user_mark, $account);
  }, array_reverse($ancestors), $href_flags);

  $user2root_html = implode('<span class="glyphicon glyphicon-arrow-right" aria-hidden="true"></span>&nbsp;', $user2root_Arr);
  $item2root_html = '<p><span class="glyphicon glyphicon glyphicon-tree-deciduous" aria-hidden="true"></span>帳號往上到公司，共有 '.count($ancestors).' 層</p>'.$user2root_html.'<hr>';

  // --------------------------------------------------------------------------------
  // (1) 管理員觀看會員結構
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------
  // 列出本身下面直接 第一線 會員數量 count , 及列出會員可以提供點擊查詢
  // --------------------------------------------------------------------------
  $member_info_string = '';

  $sql_M = "SELECT id,account,parent_id, therole FROM root_member WHERE parent_id = $member_id;";
  $sql_M_result = runSQLALL($sql_M, 0, 'r');
  // var_dump($sql_M_result);
  $member_info_string = '會員<span class="badge">'.$sql_M_result[0].'</span><span class="glyphicon glyphicon-user" aria-hidden="true"></span>';

  $sql_A = "SELECT id,account,parent_id, therole FROM root_member WHERE therole = 'A' AND parent_id = $member_id;";
  $sql_A_result = runSQLALL($sql_A, 0, 'r');
  $member_info_string = $member_info_string.'(其中有<span class="badge">'.$sql_A_result[0].'</span><span class="glyphicon glyphicon-knight" aria-hidden="true"></span>為代理商)';


  $item = '';
  for($i=1;$i<=$sql_M_result[0];$i++) {
  	// var_dump($M_value);
  	if($sql_M_result[$i]->therole == 'A' OR $sql_M_result[$i]->therole == 'R') {
  		$item_mark = '<span class="glyphicon glyphicon-knight" aria-hidden="true"></span>';
  		$item = $item.'<button class="btn btn-success btn-sm" role="button">'.$item_mark.$sql_M_result[$i]->account.'</button>&nbsp;';
  	}else{
  		$item_mark = '<span class="glyphicon glyphicon-user" aria-hidden="true"></span>';
  		$item = $item.'<button class="btn btn-default btn-sm" role="button">'.$item_mark.$sql_M_result[$i]->account.'</button>&nbsp;';
  	}
  }



  // --------------------------------------------------------------------------
  // 依據使用者身份不同，顯示不同的圖示 R A M
  // --------------------------------------------------------------------------
  if($user->therole == 'A') {
  	$user_mark = '<span class="glyphicon glyphicon-knight" aria-hidden="true"></span>';
  }elseif($user->therole == 'R'){
  	$user_mark = '<span class="glyphicon glyphicon-king" aria-hidden="true"></span>';
  }else{
  	$user_mark = '<span class="glyphicon glyphicon-user" aria-hidden="true"></span>';
  }

  $listuser_title = '<span class="glyphicon glyphicon glyphicon-tree-deciduous" aria-hidden="true"></span>目前查詢的帳號<span class="label label-default">'.$user_mark.'&nbsp;'.$user->account.'</span>%s<hr>';

  if($member_id != $_SESSION['member']->id):
    if($_SESSION['member']->therole == 'A') $logged_mark = '<span class="glyphicon glyphicon-knight" aria-hidden="true"></span>';
    elseif($_SESSION['member']->therole == 'R') $logged_mark = '<span class="glyphicon glyphicon-king" aria-hidden="true"></span>';
    else $logged_mark = '<span class="glyphicon glyphicon-user" aria-hidden="true"></span>';
    $listuser_title = sprintf($listuser_title, "，回到<a href='$_SERVER[SCRIPT_NAME]'><span class='label label-default'>{$logged_mark}{$_SESSION['member']->account}</span></a>");
  else: $listuser_title = sprintf($listuser_title, '');
  endif;

  // --------------------------------------------------------------------------
  // 會員下線列表
  // --------------------------------------------------------------------------
  $member_list_html = $listuser_title.$item2root_html.'
  	<span class="glyphicon glyphicon glyphicon-tree-deciduous" aria-hidden="true"></span>帳號往下第1代帳號列表, '.$member_info_string.'
  	<p style="line-height: 36px;">'.$item.'</p><hr>';

  // 加入顯示行列
  $member_list_html = $member_list_html;



  // --------------------------------------------------------------------------------
  // (2) 計算產生會員關係的 JSON
  // --------------------------------------------------------------------------------



  // ----------------------
  // 從指定的 $member_id 找出往下 4 代，level 0 到 level 3 的 tree 關係圖 json 產生
  // 格式為了配合 D3 的套件，所以使用這樣的寫法可以直接輸出為陣列。目前上限未知。
  // ----------------------
  	// tree 用 css
  	$member_tilford_tree_head = '';

  	// 葉數
  	$tree0_leaf_count = 0;
  	$tree1_leaf_count = 0;
  	$tree2_leaf_count = 0;
  	$tree3_leaf_count = 0;

  	// level 0
  	$r = find_children($member_id,$user->account);
  	$tree0 = $r['account_list'];
  	$next0 = $r['next_children'];
  	//var_dump($r);
  	//var_dump($tree0);
  	//var_dump($next0);
  	// 算一下 level 0 leaf 數量
  	$tree0_leaf_count =  count($next0);

  	// level 1 連接
  	$n_count = count($next0);
  	for($n=0;$n<$n_count;$n++) {
  		$parent_index = $n;
  		$children_id = $next0[$n];
  		// 將上一層的樹，和下一層的樹接起來。
  		$r = find_children($children_id);
  		$tree1 = $r['account_list'];
  		$next1 = $r['next_children'];
  		$tree0['children'][$parent_index]['children'] = $r['account_list']['children'];
  		//var_dump($tree0['children'][$parent_index]['children']);

  		// 算 level 1 leaf 數量
  		$next1_count = count($next1);
  		$tree1_leaf_count = $tree1_leaf_count + $next1_count;


  		// level 2
  		//var_dump($next1);
  		$n2_count = count($next1);
  		for($n2=0;$n2<$n2_count;$n2++) {

  			$parent_index2 = $n2;
  			$children_id2 = $next1[$n2];
  			// 將上一層的樹，和下一層的樹接起來。
  			$r2 = find_children($children_id2);
  			$tree2 = $r2['account_list'];
  			$next2 = $r2['next_children'];
  			$tree0['children'][$parent_index]['children'][$parent_index2]['children'] = $r2['account_list']['children'];

  			// 算 level 2 leaf 數量
  			$next2_count = count($next2);
  			$tree2_leaf_count = $tree2_leaf_count + $next2_count;


  			// level 3
  			//var_dump($next2);
  			$n3_count = count($next2);
  			for($n3=0;$n3<$n3_count;$n3++) {

  				$parent_index3 = $n3;
  				$children_id3 = $next2[$n3];
  				// 將上一層的樹，和下一層的樹接起來。
  				$r3 = find_children($children_id3);
  				$tree3 = $r3['account_list'];
  				$next3 = $r3['next_children'];
  				$tree0['children'][$parent_index]['children'][$parent_index2]['children'][$parent_index3]['children'] = $r3['account_list']['children'];

  				// 算 level 3 leaf 數量
  				$next3_count = count($next3);
  				$tree3_leaf_count = $tree3_leaf_count + $next3_count;
  			}
  			// end level 3

  		}
  		// end level 2
  	}
  	// end level 1


  	// -----------------------
  	// level 0 到 level 3 的 tree 關係圖 json 產生
  	// -----------------------
  	// 將 array 轉成 json tree
  	$tree_json = json_encode($tree0);
  	//echo $tree_json;

  	// 葉子的數量 , level 0 ,1 ,2 ,3 各層的會員數量。
  	//var_dump($tree0_leaf_count);
  	//var_dump($tree1_leaf_count);
  	//var_dump($tree2_leaf_count);
  	//var_dump($tree3_leaf_count);
  	$member_tree_stats_html = '<p><span class="glyphicon glyphicon glyphicon-tree-deciduous" aria-hidden="true"></span>帳號的4代人數分佈：<br>';
  	$member_tree_stats_html = $member_tree_stats_html."帳號的第 1 代有 $tree0_leaf_count 人<br>";
  	$member_tree_stats_html = $member_tree_stats_html."帳號的第 2 代有 $tree1_leaf_count 人<br>";
  	$member_tree_stats_html = $member_tree_stats_html."帳號的第 3 代有 $tree2_leaf_count 人<br>";
  	$member_tree_stats_html = $member_tree_stats_html."帳號的第 4 代有 $tree3_leaf_count 人<br>";
  	$member_tree_stats_html = $member_tree_stats_html."</p><hr>";

    $member_children_list_title = '
    <tr>
      <td>ID</td>
      <td>會員身份</td>
      <td>會員帳號</td>
      <td>註冊時間</td>
      <td>姓名</td>
      <td>狀態</td>
      <td>往下第幾代</td>
      <td>直屬下線數量</td>
      <td>会员报表</td>
    </tr>';
    // 顯示下線列表，深度為四代
    $member_children_list_html = <<<HTML
    <table id="children_list" class="display" cellspacing="0" width="100%">
      <thead>
        {$member_children_list_title}
      </thead>
      <tfoot>
        {$member_children_list_title}
      </tfoot>
    </table>
HTML;

    // 對應的 datatable JS
    $extend_js .= <<<HTML
    <!-- Jquery blockUI js  -->
    <script src="./in/jquery.blockUI.js"></script>
    <!-- Datatables js+css  -->
    <link rel="stylesheet" type="text/css" href="./in/datatables/css/jquery.dataTables.min.css">
    <script type="text/javascript" language="javascript" src="./in/datatables/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" language="javascript" src="./in/datatables/js/dataTables.bootstrap.min.js"></script>
    <script type="text/javascript" language="javascript" class="init">
    $(document).ready(function() {
      var table = $("#children_list").DataTable( {
          "bProcessing": true,
          "bServerSide": true,
          "bRetrieve": true,
          "searching": true,
          "ajax": "member_treemap_action.php?a=datatable_children_list&uid={$member_id}",
          "columns": [
            { "data": "id"},
            { "data": "therole", "orderable": false },
            { "data": "account", "fnCreatedCell": function (nTd, sData, oData, iRow, iCol) {
              $(nTd).html('<a href="member_treemap.php?id='+oData.id+'" target="_self">'+oData.account+'</a>')} },
            { "data": "enrollmentdate"},
            { "data": "nickname"},
            { "data": "status", "orderable": false },
            { "data": "depth"},
            { "data": "parent_id_count"},
            { "data": null, "fnCreatedCell": function (nTd, sData, oData, iRow, iCol) {
              $(nTd).html('<a class="btn btn-success" href="agencyarea_queryreport.php?uname='+oData.account+'" target="_blank">查询</a>')}, "orderable": false }
          ],
      } );
    } )

    // function query_str(csrftoken, account){
    //   //console.log(account);
    //   //console.log(csrftoken);
    //   var goto_url = "member_treemap.php?id=account&t=csrftoken";
    //   console.log(goto_url);
    // }
  </script>
HTML;

  	// ----------------
  	// 顯示會員關係圖
  	// ----------------
  	// ref: https://bl.ocks.org/mbostock/4339184 全部展開
  	// ref: https://bl.ocks.org/mbostock/4339083 可以點開
  	// tree add link ref: http://bl.ocks.org/serra/5012770

  	// 葉數決定高度，寬度3代剛好
  	// 60 leaf --> 1200 px , 約 20px/leaf
  	$member_tilford_tree_height = ($tree1_leaf_count+$tree2_leaf_count+$tree3_leaf_count+$tree0_leaf_count)*18;
  	if($member_tilford_tree_height <= 100) {
  		$member_tilford_tree_height = 400;
  	}
  	$member_tilford_tree_width 	= 1098;


  	// SVG 樹狀圖畫在哪裡
  	$member_tilford_tree_html = '<p><span class="glyphicon glyphicon glyphicon-tree-deciduous" aria-hidden="true">帳號：'.$user->account.'&nbsp;會員4代組織圖</p>';
  	/*
  	$member_tilford_tree_html = $member_tilford_tree_html.'&nbsp;<span class="label label-info">
  	帳號:('.$_SESSION['member']->id.')'.$_SESSION['member']->account.'</span></h4>';'
  	*/
  	$member_tilford_tree_html = $member_tilford_tree_html.'<tilford_tree_area></tilford_tree_area>';

  	// 樹狀圖的關係資料 JSON 格式
  	// $member_tilford_tree_jsonfile = 'test/flare.json';
  	$jsonfilename = 'tmp_jsondata/'.hash('sha1','member_tree_'.$member_id);
  	$member_tilford_tree_jsonfile = $jsonfilename.'.json';
  	file_put_contents("$member_tilford_tree_jsonfile", "$tree_json");

  	// D3 v3 api ref: https://github.com/d3/d3-3.x-api-reference/blob/master/Requests.md
  	// 繪製樹狀圖的 javascript
  	// var_dump($tree_json);

  	// D3 JS show tree
  	$member_tilford_tree_html = $member_tilford_tree_html.'
  	<script src="'.$cdnfullurl_js.'d3/d3.v3.min.js"></script>
  	<script src="'.$cdnfullurl_js.'d3/highlight.min.js"></script>
  	<script>

  	var width = '.$member_tilford_tree_width.',
  	    height = '.$member_tilford_tree_height.';

  	var tree = d3.layout.tree()
  	    .size([height, width - 200]);

  	var diagonal = d3.svg.diagonal()
  	    .projection(function(d) { return [d.y, d.x]; });

  	var svg = d3.select("tilford_tree_area").append("svg")
  	    .attr("width", width)
  	    .attr("height", height)
  	  .append("g")
	    .attr("transform", "translate(100,0)");

  	d3.json("'.$member_tilford_tree_jsonfile.'", function(error, json) {
  	  if (error) throw error;

  	  var nodes = tree.nodes(json),
  	      links = tree.links(nodes);

  	  var link = svg.selectAll("path.link")
  	      .data(links)
  	    	.enter().append("path")
  	      .attr("class", "link")
  	      .attr("d", diagonal);

  	  var node = svg.selectAll("g.node")
  	      .data(nodes)
  	    	.enter().append("g")
  	      .attr("class", "node")
  	      .attr("transform", function(d) { return "translate(" + d.y + "," + d.x + ")"; })

  	  node.append("circle")
  	      .attr("r", 4.5);

  	  node.append("text")
  	      .attr("dx", function(d) { return d.children ? -8 : 8; })
  	      .attr("dy", 3)
  	      .attr("text-anchor", function(d) { return d.children ? "end" : "start"; })
          .text(function(d) { return d.name; });

      node
          .append("a").attr("xlink:href", function(d) { return d.linkurl; })
          .append("rect")
          .attr("class", "clickable")
          .attr("y", -6)
          .attr("x", function (d) { return d.children || d._children ? -60 : 10; })
          .attr("width", 50)
          .attr("height", 12)
          .style("fill", function(d) { return d.color; })
          .style("fill-opacity", .3);
  	});

  	d3.select(self.frameElement).style("height", height + "px");

  	</script>
  	';

  	// 繪製 tree 多加需要的 css style , 否則很醜
  	$member_tilford_tree_head = '
  	<style>

  		.node circle {
  		  fill: #fff;
  		  stroke: steelblue;
  		  stroke-width: 1.5px;
  		}

  		.node {
  		  font: 10px sans-serif;
  		}

  		.link {
  		  fill: none;
  		  stroke: #ccc;
  		  stroke-width: 1.5px;
  		}

  	</style>
  	';

  	// 組合 html D3  + css
  	$member_tilford_content		= $member_tilford_tree_head.$member_tilford_tree_html;

    // 排版 --登入的输出
  	$indexbody_content = '
    <div class="row">
  	  <div class="col-12">
      '.$menu_agentadmin_html.'
      </div>
    </div>
    <br><br>
    <div class="row">
  	  <div class="col-12">
      '.$page_title.'
      </div>
    </div>

  	<div class="row">
  		<div class="col-12">
  	  	'.$member_list_html.'
  	  </div>
		  <div class="col-12">
  	  	'.$member_tree_stats_html.'
      </div>
		  <div class="col-12">
  	  	'.$member_children_list_html.'
      </div>
  		<div class="col-12">
  	    '.$member_tilford_content.'
  	  </div>
  	</div>
  	<br>
  	<div class="row">
  		<div id="preview_result"></div>
  	</div>
  	';

}else{


  // 排版 --登入的输出
  $indexbody_content = '
  <br><br>
  <div class="row">
    <div class="col-12">
    会员请先登入。
    </div>
  </div>

  <div class="row">
    <div id="preview_result"></div>
  </div>
  ';
}
// end if







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
$tmpl['paneltitle_content'] 			= $navigational_hierarchy_html;
// 主要內容 -- content
$tmpl['panelbody_content']				= $indexbody_content;

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include($config['template_path']."template/admin.tmpl.php");

?>
