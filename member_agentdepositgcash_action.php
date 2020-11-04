<?php
// ----------------------------------------------------------------------------
// Features:	前台 -- 針對 member_agentdepositgcash.php 程式的修改欄位資料做後端的處理
// File Name:	member_agentdepositgcash_action.php
// Author:		Barkley
// Related:   member_agentdepositgcash.php
// Log:
//隱藏標題
//<div class="panel-heading">'.$title_html.'</div>
// ----------------------------------------------------------------------------



// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

require_once dirname(__FILE__) ."/gcash_lib.php";


if(isset($_GET['a']) AND isset($_SESSION['member'])) {
  $action = filter_var($_GET['a'], FILTER_SANITIZE_STRING);

  // $csrftoken_ret = csrf_action_check();
  // if($csrftoken_ret['code'] != 1) {
  //   die($csrftoken_ret['messages']);
  // }
} else {
  echo login2return_url(2);
  die('(x)deny to access.');
}
// var_dump($_SESSION);
// var_dump($_POST);
// var_dump($_GET);

function get_check_code(array $arr, $encryption_mode)
{
  $str = '';
  foreach ($arr as $key => $value) {
    $str = ($encryption_mode == 'sha1') ? $str.$value : $str.'&'.$value;
  }
  $str = substr($str, 1);

  $encryption_code = ($encryption_mode == 'sha1') ? sha1($str) : base64_encode($str);

  return $encryption_code;
}

function get_member_data($acc)
{
  $sql = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.account = '".$acc."' AND root_member.status = '1';";
  $sql_result = runSQLall($sql);

  $data = ($sql_result[0] == 1) ? $sql_result[1] : null;

  return $data;
}

function check_acc_is_legitimate($agent_data, $member_data) {
  global $tr;
  $result['error_code'] = 1;
  $result['error_msg'] = $tr['account error_msg'];//'来源与目的帐号合法。'

  if ($agent_data == null) {
    $result['error_code'] = 0;
    $result['error_msg'] = '(x) '.$tr['account error_msg2'];//来源帐号不合法。
    // memberlog 2db($_SESSION['member']->account,'member_agentdepositgcash','warning', $result['error_msg']);
    $msg= $result['error_msg'];
    $msg_log = 'agent_data is illegal.';
    $sub_service='cashtransfer';
    memberlogtodb($_SESSION['member']->account,'accounting','error',"$msg",$member_data->account,"$msg_log",'f',$sub_service);

    return $result;
  }

  if ($member_data == null) {
    $result['error_code'] = 0;
    $result['error_msg'] = '(x) '.$tr['account error_msg3'];//目的帐号不合法。
    // memberlog 2db($_SESSION['member']->account,'member_agentdepositgcash','warning', $result['error_msg']);
    $msg= $result['error_msg'];
    $msg_log = 'member_data is illegal.';
    $sub_service='cashtransfer';
    memberlogtodb($_SESSION['member']->account,'accounting','error',"$msg",$member_data->account,"$msg_log",'f',$sub_service);

    return $result;
  }

  if ($member_data->parent_id != $agent_data->id) {
    $result['error_code'] = 0;
    $result['error_msg'] = '(x) '.$tr['account error_msg4'];//来源与目的帐号关系不合法。
    // memberlog 2db($_SESSION['member']->account,'member_agentdepositgcash','warning', $result['error_msg']);
    $msg= $result['error_msg'];
    $msg_log = 'member_data and agent_data are illegal.';
    $sub_service='cashtransfer';
    memberlogtodb($_SESSION['member']->account,'accounting','error',"$msg",$member_data->account,"$msg_log",'f',$sub_service);

    return $result;
  }

  return $result;
}

if($action == 'deposit_to_account' AND isset($_SESSION['member']) AND ($_SESSION['member']->therole == 'A') ) {
  // ----------------------------------------------------------------------------
  // 代理商現金轉帳功能 cashtransfer
  // ----------------------------------------------------------------------------
  // var_dump($_POST);
  // var_dump($_SESSION);

  //  交易的類別分類：應用在所有的錢包相關程式內 , 定義再 system_config.php 內.
  global $transaction_category;
  // 轉帳摘要
  $transaction_category_index   = 'cashtransfer';
  // 交易類別 , 定義再 system_config.php 內, 不可以隨意變更.
  $summary                      = $transaction_category[$transaction_category_index];
  // 操作者 ID
  $member_id                    = $_SESSION['member']->id;
  // 轉帳來源帳號
  $source_transferaccount       = filter_var($_POST['deposit_source_account'], FILTER_SANITIZE_STRING);
  // 轉帳目標帳號
  $destination_transferaccount  = filter_var($_POST['deposit_dest_account'], FILTER_SANITIZE_STRING);
  // 來源帳號提款密碼 or 管理員登入的密碼
  $withdrawal_password          = filter_var($_POST['deposit_password'], FILTER_SANITIZE_STRING);
  // 轉帳金額
  $transaction_money            = round($_POST['deposit_dest_account_amount'], 2);
  // 實際存提
  $realcash                     = 1;
  // 系統轉帳文字資訊(補充)
  $system_note                  = '';
  // $debug = 1 --> 進入除錯模式 , debug = 0 --> 關閉除錯
  $debug                        = 0;
  // 公司入款交易單號，預設以 (w/d)20180515_useraccount_亂數3碼 為單號，其中 W 代表提款 D 代表存款
  $w_transaction_id='d'.date("YmdHis").$source_transferaccount.str_pad(mt_rand(1,999),3,'0',STR_PAD_LEFT);
  // var_dump($w_transaction_id);die();

  if (!isset($_SESSION['sha1_check_code']) OR !isset($_SESSION['base64_check_code'])) {
    $logger = '(x) '.$tr['transfer error_msg'];//转帐流程不合法。
    // memberlog 2db($_SESSION['member']->account,'member_agentdepositgcash','warning', $logger);
    $msg=$logger;
    $msg_log = 'sha1_check_code or base64_check_code is not exsit.';
    $sub_service='cashtransfer';
    memberlogtodb($_SESSION['member']->account,'accounting','error',"$msg",$destination_transferaccount,"$msg_log",'f',$sub_service);
    echo '<script>alert("'.$logger.'");location.reload();</script>';
    return;
  }

  $source_acc_result = get_member_data($source_transferaccount);
  if($debug == 1) {
   echo $tr['get transfer source account'];//'取得轉帳來源帳號'
   var_dump($source_acc_result);
  }

  $destination_acc_result = get_member_data($destination_transferaccount);
  if($debug == 1) {
   echo $tr['transfer target'];//'轉帳目標帳號'
   var_dump($destination_acc_result);
  }

  $is_legitimate = check_acc_is_legitimate($source_acc_result, $destination_acc_result);
  if ($is_legitimate['error_code'] == 0) {
    unset($_SESSION['base64_check_code']);
    unset($_SESSION['sha1_check_code']);
    echo '<script>alert("'.$is_legitimate['error_msg'].'");location.reload();</script>';
    return;
  }


  /*
  將第一次提交資料的base64 check code進行解密
  將本次提交資料與從DB取得的資訊分別進行sha1加密
  比對第一次提交與本次進行sha1的兩個check_code做比對
  符合表示資料未被修改
  */
  $base64_check_code = base64_decode($_SESSION['base64_check_code']);
  $base64_check_code_arr = explode('&', $base64_check_code);

  $post_data_sha1 = [$member_id, $source_transferaccount, $destination_transferaccount, $transaction_money, $_SESSION['member']->salt];
  $post_data_check_code = get_check_code($post_data_sha1, 'sha1');

  $destination_account = ($base64_check_code_arr[2] == $destination_transferaccount) ? $destination_transferaccount : '';
  $action_arr = [$_SESSION['member']->id, $_SESSION['member']->account, $destination_account, $base64_check_code_arr[3], $_SESSION['member']->salt];
  $action_check_code = get_check_code($action_arr, 'sha1');

  if ($_SESSION['sha1_check_code'] != $post_data_check_code OR $_SESSION['sha1_check_code'] != $action_check_code OR $post_data_check_code != $action_check_code) {
    unset($_SESSION['base64_check_code']);
    unset($_SESSION['sha1_check_code']);
    echo '<script>alert("'.$tr['Transfer information is incorrect'].'");location.reload();</script>';//转帐资讯不正确，请确认转帐资讯正确性再行提交。
    return;
  }


  // 執行轉帳
  $error = member_gcash_transfer($transaction_category_index, $summary, $member_id, $source_transferaccount, $destination_transferaccount, $withdrawal_password, $transaction_money, $realcash, $system_note, $debug,$w_transaction_id);

  // 結果
  if($error['code'] == 1) {
    echo '<button type="button" class="btn btn-success" onclick="window.location.reload();">'.$error['messages'].'</button>';
    $msg=$tr['transfer success'];//'代理商会员钱包转帐给其他会员，成功。'
    $msg_log = $tr['Transfer number'].'：'.$w_transaction_id;
    $sub_service='cashtransfer';    
    memberlogtodb($source_transferaccount,'accounting','notice',"$msg", $destination_transferaccount,"$msg_log",'f',$sub_service);
    update_gcash_log_exist($destination_transferaccount);
  } else {
    // memberlog 2db($_SESSION['member']->account,'member_agentdepositgcash','warning', $error['messages']);
    $msg= $tr['transfer failed'];//'代理商会员钱包转帐给其他会员，失敗。'
    $msg_log = $error['messages'];
    $sub_service='cashtransfer';
    memberlogtodb($source_transferaccount,'accounting','error',"$msg", $destination_transferaccount,"$msg_log",'f',$sub_service);
    echo '<script>alert("'.$error['messages'].'");location.reload();</script>';
  }

  unset($_SESSION['base64_check_code']);
  unset($_SESSION['sha1_check_code']);

} elseif($action == 'check_deposit_data' AND isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T' ) {
  // var_dump($_POST);

  $member_id = $_SESSION['member']->id;
  // 轉帳來源帳號
  $source_transferaccount = filter_var($_POST['deposit_source_account'], FILTER_SANITIZE_STRING);
  // 轉帳目標帳號
  $destination_transferaccount = filter_var($_POST['deposit_dest_account'], FILTER_SANITIZE_STRING);
  // 轉帳金額
  $transaction_money = round($_POST['deposit_dest_account_amount'], 2);


  // 將本次提交資料分別進行base64與sha1加密並存入session
  $base64_arr = [$member_id, $source_transferaccount, $destination_transferaccount, $transaction_money];
  $base64_check_code = get_check_code($base64_arr, 'base64');

  $sha1_arr = [$member_id, $source_transferaccount, $destination_transferaccount, $transaction_money, $_SESSION['member']->salt];
  $sha1_check_code = get_check_code($sha1_arr, 'sha1');

  $_SESSION['base64_check_code'] = $base64_check_code;
  $_SESSION['sha1_check_code'] = $sha1_check_code;


  $agent_data = get_member_data($source_transferaccount);
  $member_data = get_member_data($destination_transferaccount);

  $acc_is_legitimate = check_acc_is_legitimate($agent_data, $member_data);


  // 組合輸出 html js
  $title_html = '<span class="glyphicon glyphicon-transfer" aria-hidden="true"></span>&nbsp;'.$tr['member wallet to ohter'];

  $default_withdrawal_password = $system_config['withdrawal_default_password'];
  $default_withdrawal_password_sha1 = sha1($default_withdrawal_password);
  // 判斷密碼是否還是系統預設
  if($default_withdrawal_password_sha1 == $agent_data->withdrawalspassword) {
    //預設提款密碼 請立即變更
    $withdrawal_password_change_tip_html = '<i class="required">*</i>'.$tr['member transfer / withdrawal password'].'<span><a href="member_withdrawalpwd.php" title="'.$tr['default withdrawal password'].''.$default_withdrawal_password.' '.$tr['change immediately'].'"><img src="'.$cdnrooturl.'warning.png" height="20" /></a></span>';
  } else {
    // 已經不是預設提款密碼
    $withdrawal_password_change_tip_html = '<i class="required">*</i>'.$tr['member transfer / withdrawal password'];
  }

  if($config['site_style']=='mobile'){
    $content_html = '
    <tr class="row d-flex">
      <th scope="row" class="col-5 text-truncate">'.$tr['transfer source account'].'</th>
      <td class="col-7">'.$source_transferaccount.'</td>
    </tr>
    <tr class="row d-flex"">
      <th scope="row" class="col-5 text-truncate">'.$tr['transfer source account balance'].'</th>
      <td id="deposit_source_account_balance" class="col-7">$'.$agent_data->gcash_balance.'</td>
    </tr>
    <tr class="row d-flex"">
      <th scope="row" class="col-5 text-truncate">'.$tr['transfer target'].'</th>
      <td class="col-7">'.$destination_transferaccount.'</td>
    </tr>
    <tr class="row d-flex"">
      <th scope="row" class="col-5 text-truncate">'.$tr['transfer amount'].'</th>
      <td class="col-7">$'.$transaction_money.'</td>
    </tr>
    <tr class="row d-flex"">
      <th scope="row" class="col-5 text-truncate">'. $withdrawal_password_change_tip_html.'</th>
      <td class="col-7 p-0">
        <i class="fas fa-pencil-alt pen_icon"></i>      
        <input type="password" class="form-control" id="deposit_password" placeholder="'.$tr['member transfer password'].'">
      </td>
    </tr>
    ';
  }else{
    $content_html = '
    <tr class="row d-flex">
      <th scope="row" class="col-2 text-truncate">'.$tr['transfer source account'].'</th>
      <td class="col">'.$source_transferaccount.'</td>
    </tr>
    <tr class="row d-flex"">
      <th scope="row" class="col-2 text-truncate">'.$tr['transfer source account balance'].'</th>
      <td id="deposit_source_account_balance" class="col">$'.$agent_data->gcash_balance.'</td>
    </tr>
    <tr class="row d-flex"">
      <th scope="row" class="col-2 text-truncate">'.$tr['transfer target'].'</th>
      <td class="col">'.$destination_transferaccount.'</td>
    </tr>
    <tr class="row d-flex"">
      <th scope="row" class="col-2 text-truncate">'.$tr['transfer amount'].'</th>
      <td class="col">$'.$transaction_money.'</td>
    </tr>
    <tr class="row d-flex"">
      <th scope="row" class="col-2 text-truncate">'. $withdrawal_password_change_tip_html.'</th>
      <td class="col p-0">
        <input type="password" class="form-control" id="deposit_password" placeholder="'.$tr['member transfer password'].'">
      </td>
    </tr>
    ';    
  }

  $btn_html = '
  <button id="deposit_dest_account_amount_send" class="send_btn btn-primary" type="submit">'.$tr['transfer immediate'].'</button>
  <button id="deposit_dest_account_amount_cancel" class="send_btn" type="submit" onclick="window.location.reload();">回上一步</button>
  ';

  $js = "
  <script>
  $(document).ready(function() {
    $('#deposit_dest_account_amount_send').click(function() {
      var deposit_source_account = '".$source_transferaccount."';
      var deposit_dest_account = '".$destination_transferaccount."';
      var deposit_dest_account_amount = '".$transaction_money."';
      var deposit_password = $('#deposit_password').val();
      var deposit_source_account_balance = Math.floor(".$agent_data->gcash_balance.");

      if(deposit_source_account_balance <= 1 || deposit_dest_account_amount > deposit_source_account_balance) {
        alert('".$tr['balance insufficient']."');
      } else {
        if(jQuery.trim(deposit_password) == '' ) {
          alert('".$tr['please fill all * field']."');
        } else {
          var r = confirm('".$tr['transfer confirm']."');
          if (r == true) {
            $('#deposit_dest_account_amount_send').attr('disabled', 'disabled');
            deposit_password = $().crypt({method:'sha1', source:$('#deposit_password').val()});
            $.post('member_agentdepositgcash_action.php?a=deposit_to_account',
              {
                deposit_source_account_balance: deposit_source_account_balance,
                deposit_source_account: deposit_source_account,
                deposit_dest_account: deposit_dest_account,
                deposit_dest_account_amount: deposit_dest_account_amount,
                deposit_password: deposit_password,
              },
              function(result)
              {
                $('#submit_check_page_result').html(result);
              }
            );
          } else {
            // alert('".$tr['cancel transfer']."');
          }
        }
      }

    });

    // input-focus
    $('.agentdepositgcash').on('focus','input.form-control',function(e){
      $(e.target).prev('.fa-pencil-alt').addClass('input-focus');
    })
    $('.agentdepositgcash').on('focusout','input.form-control',function(e){
      $(e.target).prev('.fa-pencil-alt').removeClass('input-focus');
    })

  });
  </script>
  ";

  if ($acc_is_legitimate['error_code'] == 0) {
    $content_html = $acc_is_legitimate['error_msg'];

    $btn_html = '
    <button id="deposit_dest_account_amount_cancel" class="send_btn" type="submit" onclick="window.location.reload();">'.$tr['Refill the transfer information'].'</button>
    ';
//重新填寫轉帳資料
    $js = '';


  }

  if($config['site_style']=='desktop'){
    $back_btn = '<a class="btn btn-secondary back_prev" href="./member_management.php" role="button"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>'.$tr['back to list'].'</a>';
  }else{
    $back_btn = '';
  }


  $html = ' 
  <div class="main_content">  
    <div class="col-12">
      <table class="table table_form2 table_form2_input agentdepositgcash">
        <thead></thead>
        <tbody>
          '.$content_html.'
        </tbody>
      </table>
    </div>  
    <div id="submit_check_page_result"></div>
    '.$btn_html.'
  </div> 
  '.$back_btn
  ;

  echo $html.$js;


} elseif($action == 'test' AND isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T' ) {
// ----------------------------------------------------------------------------
// test developer
// ----------------------------------------------------------------------------
  //var_dump($_POST);
  //var_dump($_SESSION);

}else{
  //(x)權限錯誤。
  $logger = $tr['permission error'];
  echo $logger;
//  echo '<script>alert("'.$logger.'");location.reload();</script>';


}


// ----------------------------------------------------------------------------
// END
// ----------------------------------------------------------------------------

?>
