<?php
// ----------------------------------------------------------------------------
// Features:	前台 -- 優惠紅包兌現action
// File Name:   promotion_activity_action.php
// Author:		Mavis
// Related:
// Log:
// ----------------------------------------------------------------------------

// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";
// 優惠碼函式庫
require_once dirname(__FILE__) ."/promotion_activity_lib.php";

// ---------------------------------------------------
// var_dump($_GET);
// var_dump($_POST);
// die();

if(isset($_GET['p']) AND $_GET['p'] != NULL){
    $action = filter_var($_GET['p'],FILTER_SANITIZE_STRING);

}else{
    header('Location:home.php');
    die();
}

$csrftoken_ret = csrf_action_check();
if($csrftoken_ret['code'] != 1) {
    die($csrftoken_ret['messages']);
}


// 活動代碼
if(isset($_POST['act_id']) AND $_POST['act_id'] != NULL){
    $act_id = filter_var($_POST['act_id'],FILTER_SANITIZE_STRING);
}

// if(isset($_REQUEST['post2action_result']) AND $_REQUEST['post2action_result'] != NULL){
//     $post2action_result = filter_var($_REQUEST['post2action_result'],FILTER_SANITIZE_STRING);
// }

// 優惠碼
// 以最後2碼當檢核碼，檢查優惠碼裡的檢核碼對不對，之後再檢查其他條件
if(isset($_POST['promotion_number']) AND $_POST['promotion_number'] != NULL){
    $promotion_code = filter_var($_POST['promotion_number'],FILTER_SANITIZE_STRING);
    $promotion_slice = str_split($promotion_code,10); // 字串分割，把稽核碼跟前10碼分開
    // 取前10碼
    $current_value = reset($promotion_slice); 
    // 取稽核碼
    $next_value = next($promotion_slice); 
}


// 兌換
if($action == 'receive_promotion'){

    if(isset($promotion_code)){
        // 檢查稽核碼
        $check_promotion = check_userpromotion_code($act_id,$promotion_slice,$current_value,$next_value,$promotion_code);
        // 稽核碼對
        if($check_promotion == '1'){
            // 取得活動
            $activity = get_activity_data($act_id);
      
            $activity_sdate = $activity[1]->effecttime; // 活動開始時間
            $act_requirement = $activity[1]->promocode_req;
			$act_decode = json_decode($act_requirement,true);
			
			$requirement_betting_amount = $act_decode['betting_amount'];
			$requirement_desposit_amount = $act_decode['desposit_amount'];
            $requirement_member_time = $act_decode['reg_member_time'];
            $requirement_account_type = $act_decode['user_therole'];

            // 會員資料
            $get_member_data = get_member_data();

            $reg_date = $get_member_data[1]->enrollmentdate; // 入會日期
            $member_tokenwallet = $get_member_data[1]->gtoken_balance; // gtoken錢包
            $member_ip = $_SESSION["fingertracker_remote_addr"]; // ip
            $member_fingerprint = $get_member_data[1]->registerfingerprinting; // fingerprint
            $member_role = $get_member_data[1]->therole; // 角色
          
            // 取得會員投注紀錄
            $now = date('Y-m-d');
            $activity_select_sdate = date('Y-m-d',strtotime("$activity_sdate -1 month")); // 活動開始前1個月到領取的前一天
            $activity_select_edate = date('Y-m-d',strtotime("$now -1 day"));
            $get_betting = get_betting_data($activity_select_sdate,$activity_select_edate);
            $total_bet = $get_betting[1]->bets;

            // 活動和優惠碼
            $check = check_activity_data($act_id);
            $check_name = $check[1]->activity_name; // 活動名
            $check_effecttime = $check[1]->effecttime; //開始時間
            $check_endtime = $check[1]->endtime; // 結束時間
            //$check_money = $check[1]->bouns_amount; // 獎金
            $check_bonus_auditclass = $check[1]->bonus_auditclass; // 稽核方式
            //$check_auditclass_value = $check[1]->bonus_audit; // 稽核值
            $check_audit_classification = $check[1]->audit_classification; // 稽核類別(稽核倍數,稽核金額)
            $check_status = $check[1]->status; // 優惠碼狀態 0=未領 
            $check_operator = $check[1]->operator; // 操作者

            // 類別
            $activity_category = $check_name.$tr['Pormotions'];//'優惠活動'
            // summary 
            $activity_summary = $tr['redeem'].$check_name.$tr['Pormotions'];//'兑换''优惠活动'
            // 今天
            $current_datetime = gmdate('Y-m-d H:i:s',time() + -4*3600); 

            // 獎金類別(現金 遊戲幣)
            if($check[1]->bouns_classification == 'gtoken'){
                // 選遊戲幣
                $gbouns_amount = $check[1]->bouns_amount; // 遊戲弊獎金
                $cbouns_amount = '0.00'; // 現金
                
                // 選遊戲弊稽核倍數
                if($check_audit_classification == 'audit_ratio'){
                    $audit_amount = $gbouns_amount * $check[1]->bonus_audit; // 稽核金額 = 遊戲弊獎金 * 稽核直(遊戲弊稽核倍數)

                }else{
                    $audit_amount =$check[1]->bonus_audit; // 稽核金額 = 稽核直
                }
                // 免稽核
                if($check_bonus_auditclass == 'freeaudit'){
                    $audit_amount = $check[1]->bouns_amount; // 獎金
                }
            }else{
                // 現金
                $cbouns_amount = $check[1]->bouns_amount; // 獎金
                $gbouns_amount = '0.00'; 
                $audit_amount = $check[1]->bouns_amount; // 獎金存到稽核金額
            }

            $requirement[]=reg_time_limit($reg_date,$requirement_member_time);
            $requirement[]=deposit_limit($member_tokenwallet,$requirement_desposit_amount);
            $requirement[]=betting_limit($total_bet,$requirement_betting_amount);
            $requirement[]=check_member_role($member_role,$requirement_account_type);
            if(in_array(0,$requirement)){
                echo 'wrong';
                die();
            }else{
                $go_check = check_activity_data($act_id);
                $go_promotion_insert = insert_promotion($member_ip,$member_fingerprint,$promotion_code,$current_datetime); // Insert到root_promotion_code
                $get_insert_recemoney = insert_recemoney($gbouns_amount,$cbouns_amount,$check_endtime,$check_name,$check_bonus_auditclass,$audit_amount,$check_operator,$activity_category,$activity_summary,$promotion_code,$current_datetime); // insert到root_receivemoney
                $error['code'] = '1';
                $error['message'] = $tr['Promotion code redemption'];//'優惠碼兌換成功'
                $logger = $tr['Promotion code redemption'];//'优惠码兑换成功'
                // die();
                echo '<script>alert("'.$logger.'");location.href="member_receivemoney.php";</script>';
            }
        
            if($error['code'] == 1){
                $msg = $error['message'];
                $msg_log = $error['message'];
                $sub_service = 'bonus';
                memberlogtodb($_SESSION['member']->account,'marketing','info',"$msg",$_SESSION['member']->account,"$msg_log",'f',$sub_service);
                echo '<script>location.href="member_receivemoney.php";</script>';
            }else{
                $msg = $error['message'];
                $msg_log =  $error['message'];
                $sub_service = 'bonus';
                memberlogtodb($_SESSION['member']->account,'marketing','error',"$msg",$_SESSION['member']->account,"$msg_log",'f',$sub_service);
                echo '<script>alert("'.$logger.'");location.href="home.php";</script>';
            }
        }
    }
}
?>