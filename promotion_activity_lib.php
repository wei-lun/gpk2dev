<?php
// 檢查使用者輸入的優惠碼
// 把會員輸入的優惠碼 拆2部分:
// 前10碼 和 後2碼
// 前10碼: sha1,base64,substr，取出稽核碼後2碼，看取出的結果跟使用者輸入的後兩碼是否符合
// 符合，db撈資料
function check_userpromotion_code($act_id,$promotion_slice,$current_value,$next_value,$promotion_code){
    global $tr;

    $user_input_promotion = sha1($current_value); 
    $user_input_promotion_encode = base64_encode($user_input_promotion); 
    $user_substr = substr(strtolower($user_input_promotion_encode),0,2); // 取前2碼當稽核碼

    // 跟使用者在input輸入的稽核碼做檢查 -- 條件1
    if($next_value == $user_substr){
        // 檢查db內有沒有這優惠碼 -- 條件2
        if($next_value == $user_substr){
            $sql=<<<SQL
                SELECT 
                    promo_id
                FROM root_promotion_code AS code 
                    WHERE promo_id 
                        LIKE '%{$promotion_code}%' 
                        AND code.activity_id = '{$act_id}' 
                        AND code.status = '0'
SQL;
            $result = runSQLall($sql);

            if($result[0] >= 1 ){              
                for($i= 1; $i <= $result[0]; $i++){
                    $select_promotion = $result[$i]->promo_id;       
                    // 如果有這優惠碼
                    if($select_promotion == $promotion_code){
                        // member有沒有重複
                        $check_member_account_sql = <<<SQL
                            SELECT 
                                member_account 
                            FROM root_promotion_code 
                            WHERE activity_id = '{$act_id}' 
                            AND member_account = '{$_SESSION['member']->account}'
SQL;
                        $check_member_result = runSQLall($check_member_account_sql);
                        if($check_member_result[0] > 0){
                            $logger = $tr['You have already redeemed'];//'你已经领过了。'
                            echo '<script>alert("'.$logger.'");location.href="home.php";</script>';
                            die();
                        }else{
                            $promotion_check = '1';
                        }
                    }          
                }
            }else{
                $logger = $tr['Invalid promotion code'];//'优惠码无效'
                echo '<script>alert("'.$logger.'");window.location.reload();</script>';
                die();
            }
        }
    }else{
        $logger = $tr['Promotion code error'];//'优惠码错误'
        echo '<script>alert("'.$logger.'");window.location.reload();</script>';
        die();
    }
 
    return $promotion_check;
}

// 要判斷使用者有沒有領過
function check_user_frompromotion($act_id){
    $sql =<<<SQL
    SELECT 
        * 
    FROM root_promotion_code 
    WHERE activity_id = '{$act_id}' 
    AND member_account = '{$_SESSION['member']->account}'
SQL;
    $result = runSQLall($sql);
    // unset($result[0]);
    
    return $result;
} 

// 取得活動條件
function get_activity_data($act_id){
    $requirement_act_sql = <<<SQL
        SELECT *,
            to_char((effecttime AT TIME ZONE 'AST'),'YYYY-MM-DD HH24:MI:SS') as effecttime,
            to_char((endtime AT TIME ZONE 'AST'),'YYYY-MM-DD HH24:MI:SS') as endtime,
            jsonb_pretty(promocode_req)
        FROM root_promotion_activity AS act
        WHERE act.activity_id = '{$act_id}'
SQL;
    $actsql_result = runSQLall($requirement_act_sql);
    //unset($actsql_result[0]);

    return $actsql_result;
};

// 取得domain
function get_certain_act($act_id){
    $sql=<<<SQL
        SELECT activity_domain,activity_subdomain FROM root_promotion_activity
        WHERE activity_id = '{$act_id}'
        AND activity_status = 1
SQL;
    $result = runSQLall($sql);
    return $result;
}

// 所有活動
function get_all_activity(){
	$sql=<<<SQL
		SELECT 
			*
		FROM root_promotion_activity 	
SQL;

	$all_data_result = runSQLall($sql);

  	return $all_data_result;
}

// 取得會員入會時間、存款金額
function get_member_data(){
    $requirement_member_sql = <<<SQL
        SELECT 
            * 
        FROM root_member_wallets AS w
        JOIN root_member AS m 
        ON m.id = w.id
        WHERE m.id = '{$_SESSION['member']->id}'
SQL;
    // echo($requirement_member_sql);die();
    $membersql_result = runSQLall($requirement_member_sql);
    unset($membersql_result[0]);

    return $membersql_result;
};

// 取得會員投注紀錄
function get_betting_data($activity_select_sdate,$activity_select_edate){

    // 統計時間- 活動開始前1個月到領的前一天

    // 日報表
    $betlog_sql = <<<SQL
        SELECT 
            SUM(all_bets) AS bets
        FROM root_statisticsdailyreport 
        WHERE member_account = '{$_SESSION['member']->account}' 
        AND dailydate >= '{$activity_select_sdate}' 
        AND dailydate <= '{$activity_select_edate}'
SQL;

    $run_betlog = runSQLall($betlog_sql);

    // 十分鐘
    if($run_betlog[0] == 0){
        $betlog_sql = <<<SQL
            SELECT 
                SUM(account_betvalid) AS bets
            FROM root_statisticsbetting 
            WHERE member_account = '{$_SESSION['member']->account}' 
            AND dailydate >= '{$activity_select_sdate}' 
            AND dailydate <= '{$activity_select_edate}'
SQL;
        $run_betlog = runSQLall($betlog_sql);
    }

    return $run_betlog;
};


// ----------------------------------------------------------
// 判斷條件有沒有成立
// 註冊時間
function reg_time_limit($reg_date,$requirement_member_time){
    global $tr;
    $today = date('Y/m/d H:i:s');
    $count_hours = floor((strtotime($today)-strtotime($reg_date))/(60*60)); //從會員註冊到現在，兩個日期差幾小時

    if($requirement_member_time == '0'){
        $member_results['html'] ='';
        $member_results['status'] = 1;
    }else{

        if($count_hours >= $requirement_member_time){
            $member_results['html'] = '<p><span class="sec_color_dark">'.$tr['Registered for'].'</span> '.$requirement_member_time.' '.$tr['hours'].'
            <span class="text-success float-right">'.$tr['completed'].'</span></p>';//已达成注册满小时
            $member_results['status'] = 1;
        }else{
            $member_results['html'] = '<p><span class="sec_color_dark">'.$tr['Registered for'].'</span> '.$requirement_member_time.' '.$tr['hours'].'
            <span class="text-primary float-right">'.$tr['unaccomplished'].'</span></p>';//未达成注册满小时
            $member_results['status'] = 0;
        }
    }
    return $member_results;
}

// 存款
function deposit_limit($member_tokenwallet,$requirement_desposit_amount){
    global $tr;

    if($requirement_desposit_amount == '0'){
        $deposit_result['html'] = '';
        $deposit_result['status'] = 1;
    }else{

        if($member_tokenwallet >= $requirement_desposit_amount){
            $deposit_result['html'] = '<p ><span class="sec_color_dark">'.$tr['Deposit more than'].'</span> '.$requirement_desposit_amount.' '.$tr['dollars'].'
            <span class="text-success float-right">'.$tr['completed'] .'</span></p>';//存款超过元已达成
            $deposit_result['status'] = 1;
        }else{
            $deposit_result['html'] = '<p ><span class="sec_color_dark">'.$tr['Deposit more than'].'</span> '.$requirement_desposit_amount.' '.$tr['dollars'].'
            <span class="text-primary float-right">'.$tr['unaccomplished'].'</span></p>';//存款超过元未达成
            $deposit_result['status'] = 0;
        }
    }
    return $deposit_result;
}

// 投注
function betting_limit($total_bet,$requirement_betting_amount){
    global $tr;

    if($requirement_betting_amount == '0'){
        $betting_result['html'] = '';
        $betting_result['status'] = 1;
    }else{
        if($total_bet >= $requirement_betting_amount){
            $betting_result['html'] = '<p><span class="sec_color_dark">'.$tr['Bet over'].'</span> '.$requirement_betting_amount.' '.$tr['dollars'].'
                                <span class="text-success float-right">'.$tr['completed'].'</span></p>';//投注超过元已达成
            $betting_result['status'] = 1;
        }else{
            $betting_result['html'] = '<p><span class="sec_color_dark">'.$tr['Bet over'].'</span> '.$requirement_betting_amount.' '.$tr['dollars'].'
            <span class="text-primary float-right">'.$tr['unaccomplished'].'</span></p>';//投注超过元未达成
            $betting_result['status'] = 0; 
        }
    }
   
    return $betting_result;
}

// 帳戶類型
function check_member_role($member_role,$requirement_account_type){
    global $tr;

    $account_text = array('M' => $tr['member'], 'A' => $tr['agent']);//'会员''代理商'
    if($member_role == 'A'){
        $the_member_role['html']= '<p title="'.$tr['member role status'].'"><span class="sec_color_dark">'.$tr['member role'].'</span>'.$account_text[$requirement_account_type].'
                                    <span class="text-success float-right">'.$tr['completed'].'</span></p>';//代理商包含会员身分身分已达成
        $the_member_role['status'] = 1;
    }else{
        if($member_role == $requirement_account_type){
            $the_member_role['html']= '<p><span class="sec_color_dark">'.$tr['member role'].'</span> '.$account_text[$requirement_account_type].' 
                                    <span class="text-success float-right">'.$tr['completed'].'</span></p>';//身分已达成
            $the_member_role['status'] = 1;
        }else{
            $the_member_role['html'] ='<p><span class="sec_color_dark">'.$tr['member role'].'</span> '.$account_text[$requirement_account_type].' 
            <span class="text-primary float-right">'.$tr['unaccomplished'].'</span></p>';//身分未达成
            $the_member_role['status'] = 0;
        }
    }
    return $the_member_role;
}
// ----------------------------------------------------------------

// 活動和優惠碼data
function check_activity_data($act_id){
    $sql = <<<SQL
        SELECT *,
            to_char((effecttime AT TIME ZONE 'AST'),'YYYY-MM-DD HH24:MI:SS') as effecttime,
            to_char((endtime AT TIME ZONE 'AST'),'YYYY-MM-DD HH24:MI:SS') as endtime
        FROM root_promotion_activity as act
        JOIN root_promotion_code as code
        on code.activity_id = act.activity_id
        WHERE act.activity_id = '{$act_id}'
SQL;
    $actsql_result= runSQLall($sql);
    unset($actsql_result[0]);
    return $actsql_result;
}

function insert_promotion($member_ip,$member_fingerprint,$promotion_code,$current){
    $promotion_sql = <<<SQL
        UPDATE 
            root_promotion_code
        SET 
            member_account = '{$_SESSION['member']->account}',
            member_ip = '{$member_ip}',
            member_fingerprint = '{$member_fingerprint}',
            status = '1',
            receivetime = '{$current}'
        WHERE promo_id = '{$promotion_code}'
SQL;
    // echo($promotion_sql);die();
    $run_result = runSQLall($promotion_sql);
}

function insert_recemoney($gbouns_amount,$cbouns_amount,$check_endtime,$check_name,$check_bonus_auditclass,$audit_amount,$check_operator,$activity_category,$activity_summary,$promotion_code='',$current){

    $receivemoney_sql = <<<SQL
        INSERT INTO root_receivemoney (member_id,member_account,gcash_balance,gtoken_balance,givemoneytime,receivedeadlinetime,prizecategories,auditmode,auditmodeamount,summary,transaction_category,system_note,givemoney_member_account,status)
        VALUES ('{$_SESSION['member']->id}','{$_SESSION['member']->account}','{$cbouns_amount}','{$gbouns_amount}','{$current}','{$check_endtime}','{$activity_category}','{$check_bonus_auditclass}','{$audit_amount}','{$activity_summary}','tokenfavorable','{$check_name}優惠活動；兌換碼：{$promotion_code}。','{$check_operator}','1')
SQL;
    // var_dump($receivemoney_sql);die();
    $run_result = runSQLall($receivemoney_sql);
    return $run_result;
}


// 取得優惠活動id
function get_promotion_id($act_id){
    $sql=<<<SQL
        SELECT id FROM root_promotions
        WHERE show_promotion_activity = '{$act_id}'
SQL;
    $result = runSQLall($sql);
    return $result;
}
?>