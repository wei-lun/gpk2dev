<?php
// ----------------------------------------------------------------------------
// Features:	前台 -- 會員管理action
// File Name:   member_management_action.php
// Author:		Mavis、Damocles
// Related:
// Log:
//
// ----------------------------------------------------------------------------

require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

require_once dirname(__FILE__) ."/member_management_lib.php";

// 判斷是否正常訪問
if( isset($_GET['s']) && isset($_SESSION['member']) ){
    $action = filter_var( $_GET['s'], FILTER_SANITIZE_STRING );
}
else{
    header('Location:home.php');  exit();
}

// 拆解前台傳來的值
if( isset($_POST['search']) && (!empty($_POST['search']['value'])) ){
    $search = json_decode( $_POST['search']['value'] ); // 字串轉物件
    $keyword = $search->keyword;
    $id = $search->id;
    $depth = $search->depth;
    $parent_id = $search->parent_id;
}

// 會員ID (預設是登入帳號的ID)
$select_children_id = $_SESSION['member']->id;
if( isset($id) && ($id!=$_SESSION['member']->id) && (!empty($id)) ){
    $select_children_id = filter_var( $id, FILTER_SANITIZE_NUMBER_INT );
}

// 找上級 (未設定代表是使用找下級)
if( isset($parent_id) && (!empty($parent_id)) ){
	$select_parent_id = filter_var( $parent_id, FILTER_SANITIZE_NUMBER_INT );
}

// 類型 (預設值為1)
$the_depth = '1';
if( isset($depth) && (!empty($depth)) ){
    $the_depth = filter_var( $depth, FILTER_SANITIZE_NUMBER_INT );
}

// 參數 (預設值為空)
$highlight_row = '';
if( isset($_POST['parameter_value']) && (strlen(trim($_POST['parameter_value']))>0) ){
    $highlight_row = filter_var( $_POST['parameter_value'], FILTER_SANITIZE_STRING );
}

// -------------------------------------------------------------------------
// datatable server process 分頁處理及驗證參數
// -------------------------------------------------------------------------
// 程式每次的處理量 -- 當資料量太大時，可以分段處理。 透過 GET 傳遞依序處理。
$current_per_size = $page_config['datatables_pagelength'];
if( isset($_POST['length']) && (!empty($_POST['length'])) ){
    $current_per_size = filter_var( $_POST['length'], FILTER_VALIDATE_INT );
}

// 起始頁面, 搭配 current_per_size 決定起始點位置
$current_page_no = 0;
if( isset($_POST['start']) && (!empty($_POST['start'])) ){
    $current_page_no = filter_var( $_POST['start'], FILTER_VALIDATE_INT );
}

// datatable 回傳驗證用參數，收到後不處理直接跟資料一起回傳給 datatable 做驗證
$secho = '1';
if( isset($_POST['_']) || isset($_POST['draw']) ){
	$secho = ( (isset($_POST['_'])) ? $_POST['_'] : $_POST['draw'] );
}

// 上下級
if( $action=='detail' ){

    if( isset($select_parent_id) && (!empty($select_parent_id)) ){ // 上級
        $find_parent = find_grand_parent($select_parent_id);
        $parent = $find_parent[1]->parent_id;
        $sql = find_parent($parent);

    }
    else{ // 下級
        $sql = find_children($select_children_id);
    }

    // 關鍵字搜尋 (僅判斷account)
    if( isset($keyword) && (!empty($keyword)) ){
        if( $_POST['search']['regex']=="true" ){ // 以正規表達式搜尋
            // 待開發
        }
        else{
            $keyword = filter_var( $keyword, FILTER_SANITIZE_STRING );
            $sql .= " WHERE (agent.account LIKE '%{$keyword}%') ";
        }
    }

    // 處理 datatables 傳來的排序需求
  	if( isset($_POST['order'][0]) && (!empty($_POST['order'][0]['column'])) ){
	    if( strtolower($_POST['order'][0]['dir'])=='asc' ){
	      $sql_order_dir = ' ASC';
        }
        else{
	      $sql_order_dir = ' DESC';
        }

	    if( $_POST['order'][0]['column']==0 ){
            $sql_order = ' ORDER BY account '.$sql_order_dir;
        }
        else if( $_POST['order'][0]['column']==2 ){
            $sql_order = ' ORDER BY lastlogin '.$sql_order_dir;
        }
        else if( $_POST['order'][0]['column']==3 ){
            $sql_order = ' ORDER BY child_count '.$sql_order_dir;
        }
        else{
	        $sql_order = ' ORDER BY id ASC';
        }
    }
    else{
	    $sql_order = ' ORDER BY id ASC';
	}

    // 算資料數
    $count_list = runSQL($sql);

    // 分頁
    // 所有紀錄
    $page['all_records'] = $count_list;

    // 每頁顯示多少
    $page['per_size'] = $current_per_size;

    // 目前所在頁數
    $page['no'] = $current_page_no;

    // 取出資料
    $list_sql = <<<SQL
    	{$sql} {$sql_order} OFFSET {$page['no']} LIMIT {$page['per_size']}
SQL;
    $result = runSQLall($list_sql);
    if( ($result[0]>=1) ){
	    for( $i=1;$i<=$result[0];$i++ ){

            $member['id'] = $result[$i]->id;
            $member['account'] = $result[$i]->account; // 帳號
            $last_login_show = is_null($result[$i]->lastlogin) ? $tr['no signed'] : (gmdate('Y-m-d',strtotime($result[$i]->lastlogin)+ -4*3600));//'尚未登入'
            // (gmdate('Y-m-d',strtotime($result[$i]->lastlogin.'-04')))

            $member['lastlogin'] = $last_login_show; // 最後登入時間
            $member['parent_id'] = $result[$i]->parent_id;
            $member['depth'] = $the_depth.$tr['level']; //'级' 類型，顯示此帳號在團隊中的級數(UI顯示)

            $member['hide_depth'] = $the_depth; // 類型，顯示此帳號在團隊中的級數(數字，用於代理轉帳，因此隱藏)

            if($member['id'] != null){
                $m_id = $member['id'] ;
                $get_all_children_count = get_children_total($m_id);

                foreach($get_all_children_count as $k){
                    if(isset($k->count)){
                        // 下級人數，逐層顯示該帳號下線結構
                        $member[$k->count] = $k->count;
                    }else{
                        $member[$k->count] = '0';
                    }
                }
            }

        //    // 下級人數，逐層顯示該帳號下線結構
        //   	if($result[$i]->child_count != NULL){
		// 		$member['child_count'] = $result[$i]->child_count;
		// 	}else{
		// 		$member['child_count'] = '0';
        //   	}

            $show_list_array[] = array(
                'id'                              => $member['id'],
                'parent'                          => $member['parent_id'],
                'member_name' 				      => $member['account'],
                'member_level' 				      => $member['depth'],
                'member_level_hide'               => $member['hide_depth'],
                'last_login'	                  => $member['lastlogin'],
                'member_lower_level' 		      => $member[$k->count],
                'shouldHighlight'                 => $highlight_row,
                'login_member'                    => $_SESSION['member']->id

            );
        } // end for
        $output = array(
            "sEcho" 								=> intval($secho),
            "iTotalRecords" 				        => intval($page['per_size']),
            "iTotalDisplayRecords" 	                => intval($page['all_records']),
            "data" 									=> $show_list_array
        );
    }
    else{
        $output = array(
            "sEcho" 								=> 0,
            "iTotalRecords" 				        => 0,
            "iTotalDisplayRecords" 	                => 0,
            "data" 									=> ''
        );
    }
    echo json_encode($output);

}
elseif($action == 'test'){
    var_dump($_POST);
    echo 'ERROR';
}

// 檢查上下級、代理轉帳開關
if($action == 'member_details'){
    $member_result ='';
    $transfer = '';

    $select_children_id = $_POST['id'];
    $the_depth = $_POST['depth'];
    $parent_id = $_POST['parent_id'];

    // 下級
    $lower_sql = check_children( $select_children_id );

    $r = runSQLAll($lower_sql);  // var_dump($r);  exit();

    // 代理轉帳開關
    // 只有1級才會出現
	if( isset($protalsetting['agent_transfer_isopen']) && ($protalsetting['agent_transfer_isopen']=='on') && ($the_depth=='1') ){
        $transfer =<<<HTML
            <a href="member_agentdepositgcash.php?i={$select_children_id}" class="d-block"><button type="button" class="btn btn-link btn-lg btn-block agent_transfer text-dark" value="{$select_children_id}"><h6>{$tr['Agent transfer']}</h6></button></a>
HTML;
    }
    else{
        $transfer = '';
    }

	if( $r[0] > 0 ){
        // 有下級
        $member_result .=<<<HTML
            <button type="button" class="btn btn-link btn-lg member_data text-dark w-100" id="to_get_lower_detail" data-depth="{$the_depth}" data-children-id="{$select_children_id}" data-dismiss="modal"><h6>{$tr['View child']}</h6></button>
HTML;
    }

    if( (isset($parent_id)) && ($parent_id!=$_SESSION['member']->id) ){
		// 有上級
        $member_result .=<<<HTML
            <button type="button" class="btn btn-link btn-lg member_data text-dark w-100" id="to_get_upper_detail" data-depth="{$the_depth}" data-grandparent-id="{$parent_id}" data-parameter_account="{$highlight_row}" data-dismiss="modal"><h6>{$tr['View parents']}</h6></button>
HTML;
    }

    echo $transfer;
    echo $member_result;
} //end member_details

?>