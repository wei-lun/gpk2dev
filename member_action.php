<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 針對 member.php 程式的修改欄位資料做後端的處理
// File Name:	member_action.php
// Author:		Barkley
// Related:   member.php
// Log:
// ----------------------------------------------------------------------------



// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";


// -----------------------------------------------------------------------------
if(isset($_GET['a']) AND isset($_SESSION['member'])) {
    $action = filter_var($_GET['a'], FILTER_SANITIZE_STRING);

    $csrftoken_ret = csrf_action_check();
    if($csrftoken_ret['code'] != 1) {
      die($csrftoken_ret['messages']);
    }
} else {
    echo login2return_url(2);
    die('(x)deny to access.');
}
// -----------------------------------------------------------------------------
//var_dump($_SESSION );
// var_dump($_POST);
//var_dump($_GET);

/**
 * Undocumented function
 *
 * @param [type] $regexp - 正規表示式
 * @param [type] $post_value - 要檢查的 post 值
 * @param [type] $item_name - 欄位名稱 
 * @param [type] $item_name_txt - 欄位中文名稱
 * @return array
 */
// function check_contact_method($regexp, $post_value, $item_name, $item_name_chinese)
// {
//   global $tr;
//   $prompt_msg = '';
//   $regexp_ret = filter_var($post_value, FILTER_VALIDATE_REGEXP, array("options"=>array("regexp"=>"/$regexp/")));

//   if($regexp_ret == false) {
//     // 不合法
//     ${$item_name} = '';
//     $prompt_msg = $item_name_chinese.' : '.${$item_name}.' '.$tr['illegal please enter again'];//不合法，請重新輸入。
//     echo '<script>alert("'.$prompt_msg.'");</script>';
//     die();
//   } else {
//     // 合法的結果字串
//     ${$item_name} = $regexp_ret;
//     $check_sql = "SELECT ".$item_name." FROM root_member WHERE ".$item_name." = '".${$item_name}."';";
//     $check_sql_result = runSQLALL($check_sql);
//     if ($check_sql_result[0] > 0) {
//       $logger = $item_name_chinese.' : '.${$item_name}.' '.$tr['duplicate please enter again'];//重複，請重新輸入。
//       echo '<script>alert("'.$logger.'");</script>';
//       die();
//     }
//   }

//   $result['value'] = ${$item_name};
//   $result['msg'] = $prompt_msg;

//   return $result;
// }

/**
 * 
 *
 * @param [type] $post_value - 要檢查的 post 值
 * @param [type] $item_name - 欄位名稱 
 * @param [type] $item_name_txt - 欄位中文名稱
 * @return array
 */
function check_contact_method($post_value, $item_name, $item_name_chinese)
{
  global $tr;

  $check_sql = "SELECT ".$item_name." FROM root_member WHERE ".$item_name." = '".$post_value."';";
  $check_sql_result = runSQLALL($check_sql);

  if ($check_sql_result[0] > 0) {
    $logger = $item_name_chinese.' : '.$post_value.' '.$tr['duplicate please enter again'];//重複，請重新輸入。
    echo '<script>alert("'.$logger.'");</script>';
    die();
  }

  // $result['value'] = ${$item_name};
  $result['value'] = $post_value;

  return $result;
}

/**
 * 驗證使用者 post 過來的資料
 * 組合需要被更新的欄位 sql 並回傳相關訊息
 *
 * @param [type] $check_item - 要確認的項目
 * @param [type] $member_data
 * @param [type] $post - post資料
 * @param [type] $pk - 會員 id
 * @return array
 */
function check_post_data($check_item, $member_data, $post, $pk)
{
  global $tr;
  $update_sql = '';
  for ($i=0; $i < count($check_item) ; $i++) {
    if (($member_data->{$check_item[$i]} == '' OR ($check_item[$i] == 'sex' AND $member_data->sex == '2') OR $check_item[$i] == 'nickname') AND $post[$check_item[$i]] != '' ) {
      switch ($check_item[$i]) {
        case 'mobilenumber':
          // $regexp = '^13[0-9]{1}[0-9]{8}|^15[0-9]{1}[0-9]{8}|^18[8-9]{1}[0-9]{8}';
  
          // $result = check_contact_method($regexp, $post['mobilenumber'], $check_item[$i], $tr['cellphone']);
          $result = check_contact_method($post['mobilenumber'], $check_item[$i], $tr['cellphone']);
          ${$check_item[$i]} = $result['value'];
          // $mobilenumber_prompt_msg = $result['msg'];
          break;
        case 'qq':
          // $regexp = '^[0-9]{5,9}$';

          // $result = check_contact_method($regexp, $post['qq'], $check_item[$i], 'QQ');
          $result = check_contact_method($post['qq'], $check_item[$i], 'QQ');
          ${$check_item[$i]} = $result['value'];
          // $qq_prompt_msg = $result['msg'];
          break;
        case 'wechat':
          // $regexp = '^.{1,20}$';
  
          // $result = check_contact_method($regexp, $post['wechat'], $check_item[$i], 'wechat');
          $result = check_contact_method($post['wechat'], $check_item[$i], 'wechat');
          ${$check_item[$i]} = $result['value'];
          // $wechat_prompt_msg = $result['msg'];
          break;
        case 'email':
          if (filter_var($post['email'], FILTER_VALIDATE_EMAIL) == false) {
            ${$check_item[$i]} = '';
          } else {
            ${$check_item[$i]} = $post['email'];
          }
          break;
        case 'sex':
          ${$check_item[$i]} = filter_var($post[$check_item[$i]], FILTER_SANITIZE_NUMBER_INT);
          if (${$check_item[$i]} != '0' AND ${$check_item[$i]} != '1') {
            ${$check_item[$i]} = '';
          }
          break;
        case 'birthday':
          ${$check_item[$i]} = filter_var($post[$check_item[$i]], FILTER_SANITIZE_STRING);
          ${$check_item[$i]} = str_replace('/', '', ${$check_item[$i]});
          break;
        default:
          ${$check_item[$i]} = filter_var($post[$check_item[$i]], FILTER_SANITIZE_STRING);
          break;
      }

      if (${$check_item[$i]} != '') {
        $update_sql = $update_sql."UPDATE root_member SET ".$check_item[$i]." = '".${$check_item[$i]}."', changetime = now() WHERE id = '$pk';";
      }
    }
  }

  // $result['mobilenumber_prompt_msg'] = $mobilenumber_prompt_msg;
  // $result['qq_prompt_msg'] = $qq_prompt_msg;
  // $result['wechat_prompt_msg'] = $wechat_prompt_msg;
  $result['update_sql'] = $update_sql;

  return $result;
}

if($action == 'member_editpersondata' AND isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T' ) {
// ----------------------------------------------------------------------------
// 使用者修改自己的資料, 對應前台 member.php 檔案的功能
// 有登入系統才可以工作
// 可以修改的欄位：暱稱,真實名稱,行動電話,電子郵件,微信 ID,QQ ID
// ----------------------------------------------------------------------------
  // var_dump($_POST);
  // $pk = filter_var($_POST['pk'], FILTER_SANITIZE_NUMBER_INT);
  $pk = $_SESSION['member']->id;

  $sql = "SELECT * FROM root_member WHERE id = '$pk';";
  $sql_result = runSQLALL($sql);

   if ($sql_result[0] == 1) {

    $mobilenumber_prompt_msg = '';
    $qq_prompt_msg = '';
    $wechat_prompt_msg = '';
    // 要檢查的欄位
    $check_item = array('realname', 'nickname', 'mobilenumber', 'email', 'birthday', 'sex', 'wechat', 'qq');
    
    $check_post_data_result = check_post_data($check_item, $sql_result[1], $_POST, $pk);

    // $mobilenumber_prompt_msg = $check_post_data_result['mobilenumber_prompt_msg'];
    // $qq_prompt_msg = $check_post_data_result['qq_prompt_msg'];
    // $wechat_prompt_msg = $check_post_data_result['wechat_prompt_msg'];
    $update_sql = $check_post_data_result['update_sql'];

    if ($update_sql != '') {

      // 組合要執行的 sql
      $transaction_sql = 'BEGIN;'
      .$update_sql
      .'COMMIT;';

      // 執行 transaction sql
      $transaction_sql_result = runSQLtransactions($transaction_sql);

      if($transaction_sql_result) {
        $logger = "Member id = $pk Change mamber data success.";
        // memberlog 2db($_SESSION['member']->account,'member','notice', "$logger");
        $msg=$tr['data update success'];//'個人及帳務資料更新成功。'
        $msg_log = $logger;
        $sub_service='information';
        memberlogtodb($_SESSION['member']->account,'member','info',"$msg",$_SESSION['member']->account,"$msg_log",'f',$sub_service);
        $logger = $tr['data update success'];//"個人及帳務資料更新成功。"
        // echo $logger;
        // echo '<button type="button" class="btn btn-success"'.$logger.'</button>';
        echo '<script>alert("'.$logger.'");location.reload();</script>';
      } else {
        $logger = "Member id = $pk Change mamber data false.";
        //echo '<button type="button" class="btn btn-warning"'.$logger.'</button>';
        // memberlog 2db($_SESSION['member']->account,'member','warning', "$logger");
        $msg=$tr['data update failed'];//'個人及帳務資料更新失敗。'
        $msg_log = json_encode($_POST);
        $sub_service='information';
        memberlogtodb($_SESSION['member']->account,'member','error',"$msg",$_SESSION['member']->account,"$msg_log",'f',$sub_service);
        $logger = $tr['data update failed'];//"個人及帳務資料更新失敗。"
        echo '<script>alert("'.$logger.'");window.location.reload();</script>';
        // echo '<script>location.reload();</script>';
      }

    } else {
      $logger = $tr['Please enter correct information'];//"請填入正確要修改的資料。"
      echo '<script>alert("'.$logger.'");</script>';
    }

  } else {
    $logger = $name.'(x)'.$tr['data error,or account not found'];//會員資料查詢錯誤或查無會員資料。
    echo '<script>alert("'.$logger.'");location.reload();</script>';
  }

} elseif($action == 'member_editbankdata' AND isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T' ) {
  // var_dump($_POST);

  // $pk = filter_var($_POST['pk'], FILTER_SANITIZE_NUMBER_INT);
  $pk = $_SESSION['member']->id;
  
  $sql = "SELECT * FROM root_member WHERE id = '$pk';";
  $sql_result = runSQLALL($sql);

    if ($sql_result[0] == 1) {

    // 要檢查的欄位
    $check_item = array('bankname', 'bankaccount', 'bankprovince', 'bankcounty');
    
    $check_post_data_result = check_post_data($check_item, $sql_result[1], $_POST, $pk);

    $update_sql = $check_post_data_result['update_sql'];

    if ($update_sql != '') {

      // 組合要執行的 sql
      $transaction_sql = 'BEGIN;'
      .$update_sql
      .'COMMIT;';

      $transaction_sql_result = runSQLtransactions($transaction_sql);

      if($transaction_sql_result) {
        $logger = "Member id = $pk Change mamber data success.";
        // memberlog 2db($_SESSION['member']->account,'member','notice', "$logger");
        $msg=$tr['data update success'];//'帳務資料更新成功。'
        $msg_log = $logger;
        $sub_service='information';
        memberlogtodb($_SESSION['member']->account,'accounting','info',"$msg",$_SESSION['member']->account,"$msg_log",'f',$sub_service);
        $logger = $tr['data update success'];//"帳務資料更新成功。"
        echo '<script>alert("'.$logger.'");location.reload();</script>';
      } else {
        $logger = "Member id = $pk Change mamber data false.";
        // memberlog 2db($_SESSION['member']->account,'member','warning', "$logger");
        $msg=$tr['data update failed'];//'帳務資料更新失敗。'
        $msg_log = json_encode($_POST);
        $sub_service='information';
        memberlogtodb($_SESSION['member']->account,'accounting','error',"$msg",$_SESSION['member']->account,"$msg_log",'f',$sub_service);
        $logger = $tr['data update failed'];//"帳務資料更新失敗。"
        echo '<script>alert("'.$logger.'");window.location.reload();</script>';
      }

    } else {
      $logger = $tr['Please enter correct information'];//"請填入正確要修改的資料。"
      echo '<script>alert("'.$logger.'");</script>';
    }

  } else {
    $logger = $name.'(x)'.$tr['data error,or account not found'];//會員資料查詢錯誤或查無會員資料。
    echo '<script>alert("'.$logger.'");location.reload();</script>';
  }
} elseif($action == 'member_editpersondata_passwordm' AND isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T' ) {
// ----------------------------------------------------------------------------
// 使用者修改自己的資料, 對應前台 member.php 檔案的功能
// 可以修改的欄位：會員密碼
// ----------------------------------------------------------------------------
  // var_dump($_POST);
  // $pk         = filter_var($_POST['pk'], FILTER_SANITIZE_NUMBER_INT);
  $pk = $_SESSION['member']->id;
  $current_password       = filter_var($_POST['current_password'], FILTER_SANITIZE_STRING);
  $change_password_valid1       = filter_var($_POST['change_password_valid1'], FILTER_SANITIZE_STRING);
  $change_password_valid2       = filter_var($_POST['change_password_valid2'], FILTER_SANITIZE_STRING);

  // 如果兩個密碼一樣, 才進行修改。
  if($_SESSION['member']->id == $pk AND $change_password_valid1 == $change_password_valid2) {
    $update_password_sql = "UPDATE root_member SET passwd = '".$change_password_valid1."', changetime = now() WHERE id = '".$pk."' AND passwd = '".$current_password."';";
    $update_password_sql_result = runSQL($update_password_sql);
    if($update_password_sql_result == 1) {
      $logger = "Member id = $pk change password to $change_password_valid1 success.";
      // memberlog 2db($_SESSION['member']->account,'member','notice', "$logger");
      $msg=$tr['password change success'];//'會員個人密碼修改完成。'
      $msg_log = $logger;
      $sub_service='information';
      memberlogtodb($_SESSION['member']->account,'member','info',"$msg",$_SESSION['member']->account,"$msg_log",'f',$sub_service);
      $logger = $tr['password change success'];//"會員個人密碼修改完成。"
      echo $logger;
      echo '<script>alert("'.$logger.'");location.reload();</script>';
    }else{
      $logger = $tr['password input error'];//"會員個人現在的密碼輸入錯誤。"
      echo $logger;
      echo '<script>alert("'.$logger.'");location.reload();</script>';
    }

  }else{
    $logger = $tr['password vertify error'];//新的密碼，前後輸入不一樣，請重新輸入。
    echo $logger;
    echo '<script>alert("'.$logger.'");location.reload();</script>';
  }

} elseif($action == 'member_editpersondata_passwordw' AND isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T' ) {
// ----------------------------------------------------------------------------
// 使用者修改自己的資料, 對應前台 member.php 檔案的功能
// 可以修改的欄位：修改會員提款密碼
// ----------------------------------------------------------------------------
  // var_dump($_POST);
  // $pk         = filter_var($_POST['pk'], FILTER_SANITIZE_NUMBER_INT);
  $pk = $_SESSION['member']->id;
  $withdrawal_password       = filter_var($_POST['withdrawal_password'], FILTER_SANITIZE_STRING);
  $change_withdrawalpassword_valid1       = filter_var($_POST['change_withdrawalpassword_valid1'], FILTER_SANITIZE_STRING);
  $change_withdrawalpassword_valid2       = filter_var($_POST['change_withdrawalpassword_valid2'], FILTER_SANITIZE_STRING);

  // 如果兩個密碼一樣, 才進行修改。
  if($_SESSION['member']->id == $pk AND $change_withdrawalpassword_valid1 == $change_withdrawalpassword_valid2) {
    $update_password_sql = "UPDATE root_member SET withdrawalspassword = '$change_withdrawalpassword_valid1'  WHERE id = '$pk' AND withdrawalspassword = '$withdrawal_password';";
    $update_password_sql_result = runSQL($update_password_sql);
    if($update_password_sql_result == 1) {
      $logger = "Member id = $pk change withdrawals password to $change_withdrawalpassword_valid1 success.";
      // memberlog 2db($_SESSION['member']->account,'member','notice', "$logger");
      $msg=$tr['password change success'];//'會員個人密碼修改完成。'
      $msg_log = $logger;
      $sub_service='information';
      memberlogtodb($_SESSION['member']->account,'member','info',"$msg",$_SESSION['member']->account,"$msg_log",'f',$sub_service);
      $logger = $tr['withdrawal password change success'];//"會員提款密碼變更完成。"
      //echo $logger.$update_password_sql;
      echo '<script>alert("'.$logger.'");location.reload();</script>';
    }else{
      $logger = $tr['withdrawal password input error'];//"会员现在的提款密码输入错误。"
      //echo $logger.$update_password_sql;
      $msg=$tr['withdrawal password input error'];//'会员现在的提款密码输入错误。'
      $msg_log = $logger;
      $sub_service='information';
      memberlogtodb($_SESSION['member']->account,'member','error',"$msg",$_SESSION['member']->account,"$msg_log",'f',$sub_service);
      echo '<script>alert("'.$logger.'");location.reload();</script>';
    }

  }else{
    $logger = $tr['withdrawal password vertify error'];//'新的提款密码，前后输入不一样，请重新输入。'
      $msg=$tr['withdrawal password vertify error'];//'新的提款密码，前后输入不一样，请重新输入。'
      $msg_log = $logger;
      $sub_service='information';
      memberlogtodb($_SESSION['member']->account,'member','error',"$msg",$_SESSION['member']->account,"$msg_log",'f',$sub_service);
    echo $logger;
    echo '<script>alert("'.$logger.'");location.reload();</script>';
  }

} elseif($action == 'test' AND isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T' ) {
// ----------------------------------------------------------------------------
// test developer
// ----------------------------------------------------------------------------
    var_dump($_POST);
    echo 'ERROR';

}



?>
