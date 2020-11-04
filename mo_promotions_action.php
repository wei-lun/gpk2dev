<?php

require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";


if(isset($_GET['a']) AND $_GET['a'] != NULL){
    $action = filter_var($_GET['a'],FILTER_SANITIZE_STRING);
}


// function validatePost($post){
//     $input = array();
  
//   //   $go_action =[
//   //       0=>'select',
//   //       1=>'more'
//   //   ];
  
//     if ($post['action'] == 'more') {
//       $post = array_merge($post, $post['data']);
//       unset($post['data']);
//     }
  
//     foreach ($post as $k => $v) {
//       if ($k == 'note') {
//         $input[$k] = ($v != '') ? filter_var($v, FILTER_SANITIZE_STRING) : '';
//       } else {
//         $input[$k] = filter_var($v, FILTER_SANITIZE_STRING);
  
//         if ($input[$k] == '') {
//           return array('status' => false, 'result' => '资料不合法，请确认资料正确性后再行尝试');
//         }
//       }
//     }
  
//     return array('status' => true, 'result' => (object)$input);
//   }

  
// if ($_SERVER['REQUEST_METHOD'] == 'POST') {
//     $post = (object)validatePost($_POST);

//     switch ($post->result->action) {
        
//         case 'more':
//           $moreData = get_more($_SERVER['HTTP_HOST'], $post->result->limit, $post->result->condition);
    
//           if (!$moreData) {
//             echo json_encode(['status' => 'fail', 'result' => '查無資料']);
//             die();
//           }
    
//           echo json_encode(['status' => 'success', 'result' => $moreData]);
//           break;

//         case 'select':
//           $selectData = select($_SERVER['HTTP_HOST'],  $post->result->action);
    
//           if (!$selectData) {
//             echo json_encode(['status' => 'fail', 'result' => '查無資料']);
//             die();
//           }
    
//           echo json_encode(['status' => 'success', 'result' => $selectData]);
//           break;
       
//         default:
//           echo json_encode(['status' => 'fail', 'result' => '错误的请求']);
//           break;
//       }
// }

// function select(){
//     $select_class = filter_var($_POST['select'],FILTER_SANITIZE_STRING);
//     $domain = $_SERVER['HTTP_HOST'];
//     $tools = $config['site_style'];
   
//     if(isset($select_class) AND $select_class != 'all' AND $tools == 'mobile'){
//         $sql=<<<SQL
//             SELECT * FROM root_promotions 
//             WHERE classification = '{$select_class}'
//             AND mobile_domain = '{$domain}' 
//             AND status = 1
//             AND mobile_show = 1
//             AND effecttime <= current_timestamp 
//             AND endtime >= current_timestamp
// SQL;
//     }elseif($select_class != 'all'  AND $tools == 'desktop'){
//         $sql=<<<SQL
//             SELECT * FROM root_promotions 
//             WHERE classification = '{$select_class}'
//             AND desktop_domain = '{$domain}' 
//             AND status = 1
//             AND desktop_show = 1
//             AND effecttime <= current_timestamp 
//             AND endtime >= current_timestamp
// SQL;
//     }

//     // all
//     if($select_class == 'all' AND $tools == 'mobile'){
//         $sql=<<<SQL
//             SELECT * FROM root_promotions 
//             WHERE mobile_domain = '{$domain}' 
//             AND status = 1
//             AND mobile_show = 1
//             AND effecttime <= current_timestamp 
//             AND endtime >= current_timestamp
// SQL;
//     }elseif($select_class == 'all' AND $tools == 'desktop'){
//         $sql=<<<SQL
//             SELECT * FROM root_promotions 
//             WHERE desktop_domain = '{$domain}'
//             AND status = 1
//             AND desktop_show = 1
//             AND effecttime <= current_timestamp 
//             AND endtime >= current_timestamp
// SQL;
//     }
//     // var_dump($sql);die();
//     $result = runSQLall($sql);

//     $item = '';
//     if($result[0]>=1){
//         for($i=1;$i<=$result[0];$i++){
//             $id = $result[$i]->id;
//             $name = $result[$i]->name;
//             $endtime = $result[$i]->endtime;
            
//             $item .=<<<HTML
//             <table class="table table-hover table_reflash" id="promotion_Table">
//                     <tbody id="promotionsTableBody">
//                         <tr class="row border promotion" id="promotion_{$id}" onclick="document.location='mo_promotions_detail.php?id={$id}';">	
                            
//                             <td class="col-8 border-0 h5 font-weight-bold m-0 ">{$name}</td>
//                             <td class="col-4 text-right border-0">
//                                 <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>	
//                             </td>
//                             <td class="col-8 border-0 pt-0 text-secondary ">截止日: {$endtime}</td>
                                
//                         </tr>
//                     </tbody>
//                 </table>
// HTML;
//         }
//     }
//     echo $item;
// }

// 分類
if($action == 'select'){

    $select_class = filter_var($_POST['select'],FILTER_SANITIZE_STRING);
    $domain = $_SERVER['HTTP_HOST'];
   
    if(isset($select_class) AND $select_class != 'all' AND $config['site_style'] == 'mobile'){
        $sql=<<<SQL
            SELECT * FROM root_promotions 
            WHERE classification = '{$select_class}'
            AND mobile_domain = '{$domain}' 
            AND status = 1
            AND mobile_show = 1
            AND effecttime <= current_timestamp 
            AND endtime >= current_timestamp
SQL;
    }elseif($select_class != 'all'  AND $config['site_style'] == 'desktop'){
        $sql=<<<SQL
            SELECT * FROM root_promotions 
            WHERE classification = '{$select_class}'
            AND desktop_domain = '{$domain}' 
            AND status = 1
            AND desktop_show = 1
            AND effecttime <= current_timestamp 
            AND endtime >= current_timestamp
SQL;
    }

    // all
    if($select_class == 'all' AND $config['site_style'] == 'mobile'){
        $sql=<<<SQL
            SELECT * FROM root_promotions 
            WHERE mobile_domain = '{$domain}' 
            AND status = 1
            AND mobile_show = 1
            AND effecttime <= current_timestamp 
            AND endtime >= current_timestamp
SQL;
    }elseif($select_class == 'all' AND $config['site_style'] == 'desktop'){
        $sql=<<<SQL
            SELECT * FROM root_promotions 
            WHERE desktop_domain = '{$domain}'
            AND status = 1
            AND desktop_show = 1
            AND effecttime <= current_timestamp 
            AND endtime >= current_timestamp
SQL;
    }
    // var_dump($sql);die();
    $result = runSQLall($sql);

    $item = '';
    if($result[0]>=1){
        for($i=1;$i<=$result[0];$i++){
            $id = $result[$i]->id;
            $name = $result[$i]->name;
            $endtime =  (gmdate('Y-m-d',strtotime($result[$i]->endtime.'-04')));
            
            $item .=<<<HTML
            <table class="table table-hover table_reflash" id="promotion_Table">
                    <tbody id="promotionsTableBody">
                        <tr class="row border promotion" id="promotion_{$id}" onclick="document.location='mo_promotions_detail.php?id={$id}';">	
                            
                            <td class="col-8 border-0 h5 font-weight-bold m-0 ">{$name}</td>
                            <td class="col-4 text-right border-0">
                                <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>	
                            </td>
                            <td class="col-8 border-0 pt-0 text-secondary ">截止日期: {$endtime}</td>
                                
                        </tr>
                    </tbody>
                </table>
HTML;
        }
    }
    echo $item;
}

// function get_more($domain,$limit,$condition){
    
//     $sql=<<<SQL
//             SELECT * FROM root_promotions 
//             WHERE mobile_domain = '{$domain}' 
//             AND status = 1
//             AND mobile_show = 1
//             AND effecttime <= current_timestamp 
//             AND endtime >= current_timestamp
//             LIMIT 2
//             OFFSET '{$limit}'
// SQL;
//     $result = runSQLall($sql);
//     var_dump($result);die();
//     if(empty($result[0])){
//         return false;
//     }

//     $outoutData['count'] = $result[0];
//     // var_dump($outoutData['count']);die();
//     unset($result[0]);

//     $outoutData['data'] = combineOutoutDatasArr($result);
//     var_dump($outoutData);die();

//     return $outoutData;
// }


// 加載
if($action == 'more'){

    $load = filter_var($_POST['condition'],FILTER_SANITIZE_STRING);
    $limit = filter_var($_POST['limit'],FILTER_SANITIZE_NUMBER_INT);
    $domain = $_SERVER['HTTP_HOST'];

    if($config['site_style'] == 'mobile'){
    $sql=<<<SQL
            SELECT * FROM root_promotions 
            WHERE mobile_domain = '{$domain}' 
            AND status = 1
            AND mobile_show = 1
            -- AND effecttime <= current_timestamp 
            AND endtime >= current_timestamp
            ORDER BY endtime ASC
            LIMIT 2
            OFFSET '{$limit}'
SQL;
    }else{
        $sql=<<<SQL
            SELECT * FROM root_promotions 
            WHERE desktop_domain = '{$domain}' 
            AND status = 1
            AND desktop_show = 1
            -- AND effecttime <= current_timestamp 
            AND endtime >= current_timestamp
            ORDER BY endtime ASC
            LIMIT 2
            OFFSET '{$limit}'
SQL;
    }

    $result = runSQLall($sql);
    // var_dump($result);die();
    if(empty($result[0])){
        $outcome['status'] = 'fail';
        return false;
    };
    $outcome = [];
    $outcome['status'] = 'success';
    $outcome['count'] = $result[0];
    unset($result[0]);
    $outcome['data'] =  combineOutoutDatasArr($result);
       
    echo json_encode($outcome);
}

function combineOutoutDatasArr($data){
    $arr = [];

    foreach ($data as $v) {
        $arr[] = [
            'id' => $v->id,
            'name' => $v->name,
            'effecttime' => $v->effecttime,
            'endtime' => (gmdate('Y-m-d',strtotime($v->endtime.'-04'))),
            'status' => $v->status
        ];
    }
    // var_dump($arr);die();
  return $arr;
}

// 結束優惠
if($action == 'closed'){

    $tzonename = 'posix/Etc/GMT-8';
    $today = filter_var($_POST['now'],FILTER_SANITIZE_STRING);
    // $today =  gmdate('Y-m-d H:i',time() + '-4' * 3600);
    // $determine = $config['site_style'];
    $domain = $_SERVER['HTTP_HOST'];

	if($config['site_style'] == 'mobile') {
		$sql = <<<SQL
		SELECT * 
		FROM root_promotions 
		WHERE status = 1 
		AND mobile_show = 1
		AND mobile_domain = '{$domain}'

    	AND endtime <= '{$today}'
		ORDER BY id ASC
		-- LIMIT 2
SQL;
	  } else {

		$sql = <<<SQL
		SELECT * 
		FROM root_promotions
		WHERE status = 1
		AND desktop_show = 1
		AND desktop_domain = '{$domain}'
    	AND endtime <= '{$today}'
		ORDER BY id ASC
		-- LIMIT 2
SQL;
	}
    $result = runSQLall($sql);
    // var_dump($result);die();

    $closed_item = '';
    if($result[0]>=1){
        for($i=1;$i<=$result[0];$i++){
            $id = $result[$i]->id;
            $name = $result[$i]->name;
            $endtime = (gmdate('Y-m-d',strtotime($result[$i]->endtime.'-04')));
            
            $closed_item .=<<<HTML
            <table class="table table-hover table_reflash" id="promotion_Table">
                    <tbody id="promotionsTableBody">
                        <tr class="row border promotion" id="promotion_{$id}" onclick="document.location='mo_promotions_detail.php?id={$id}';">	
                            
                            <td class="col-8 border-0 h5 font-weight-bold m-0 ">{$name}</td>
                            <td class="col-4 text-right border-0">
                                <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>	
                            </td>
                            <td class="col-8 border-0 pt-0 text-secondary ">截止日期: {$endtime}</td>
                                
                        </tr>
                    </tbody>
                </table>
HTML;
        }
    }
    echo $closed_item;
}
?>
