<?php
// ----------------------------------------------------------------------------
// Features:  前台 -- 行銷優惠活動專區
// File Name: promotions_lib.php
// Author:    Mavis
// Related:   
// DB Table:  
// Log:
// ----------------------------------------------------------------------------


// -----------------------------------------------------

// 初始化，取得所有優惠活動
function get_promotions_domain($domain,$determine){

	$tzonename = 'posix/Etc/GMT-8';
	if($determine == 'mobile') {
        $domain_show = 'mobile_show';
        $domain_name = 'mobile_domain';
    }else{
        $domain_show = 'desktop_show';
        $domain_name = 'desktop_domain';
    }
    $sql = <<<SQL
		SELECT * , 
			to_char((effecttime AT TIME ZONE '{$tzonename}'),'YYYY/MM/DD HH24:MI') AS effecttime, 
			to_char((endtime AT TIME ZONE '{$tzonename}'),'YYYY/MM/DD HH24:MI') AS e_time 
		FROM root_promotions 
			WHERE status = 1 
				AND {$domain_show} = 1
				AND {$domain_name} = '{$domain}'
				AND effecttime <= current_timestamp 
				AND endtime >= current_timestamp
			ORDER BY endtime ASC
				LIMIT 7
SQL;

	// echo($sql);die();
	$result = runSQLall($sql);
	if(empty($result[0])) {
		$error_text = '优惠查询错误或暫無優惠';
		return array('status' => 'query_error', 'result' => $error_text);
	}
	unset($result[0]);
	return array('status' => 'success', 'result' => $result);
}


// 分類select
function get_classification($domain,$determine){

	if($determine == 'mobile') {
        $domain_show = 'mobile_show';
        $domain_name = 'mobile_domain';
    }else{
        $domain_show = 'desktop_show';
        $domain_name = 'desktop_domain';
	}
	
	$sql=<<<SQL
		SELECT * FROM(
			SELECT DISTINCT ON (classification) classification,sort,classification_status FROM root_promotions 
			WHERE ({$domain_name} = '{$domain}' AND {$domain_name} IS NOT NULL)
			AND status = 1
			AND {$domain_show} = 1
			AND classification_status = 1
			--AND effecttime <= current_timestamp 
            --AND endtime >= current_timestamp
			ORDER BY classification
		) t
		ORDER BY sort
SQL;
	// var_dump($sql);die();
	$result = runSQLall($sql);
	if(empty($result[0])){
		$error_text = '优惠分类查询错误或暫無優惠';
		return array('status' => false, 'result' => $error_text);
	}
	unset($result[0]);
	return array('status' => true, 'result' => $result);
}


// ------------------------------
// 活動詳細
// ------------------------------
// 活動優惠碼管理
function get_promotion_activity($get_promotion_id){

	$sql =<<<SQL
		SELECT * FROM root_promotion_activity
		WHERE id = '{$get_promotion_id}'
		AND activity_status = 1 
SQL;
	$result = runSQLall($sql);
	unset($result[0]);
	return $result;
}

// 取優惠管理id
function getPromotions_id($promotion_id){
	$sql=<<<SQL
		SELECT *--,to_char((endtime AT TIME ZONE 'AST'),'YYYY-MM-DD HH24:MI:SS') AS the_endtime 
		FROM root_promotions 
		WHERE id = '{$promotion_id}' 
		AND status = 1
		-- AND mobile_show = 1
		AND desktop_show = 1

SQL;
	$sql_result = runSQLall($sql);
	// unset($sql_result[0]);
	return $sql_result;
}

// 取手機板 開關
function getPromotions_mobile_id($promotion_id){
	$sql=<<<SQL
		SELECT *--,to_char((endtime AT TIME ZONE 'AST'),'YYYY-MM-DD HH24:MI:SS') AS the_endtime 
		FROM root_promotions 
		WHERE id = '{$promotion_id}' 
		AND status = 1
		AND mobile_show = 1
		-- OR desktop_show = 1

SQL;
	$sql_result = runSQLall($sql);
	// unset($sql_result[0]);
	return $sql_result;
}