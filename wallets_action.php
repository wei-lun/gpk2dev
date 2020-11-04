<?php
// ----------------------------------------------------------------------------
// Features:	針對 wallets.php 的對應動作檔案
// File Name:	wallets_action.php
// Author:		Barkley
// Related:
// Log:
// ----------------------------------------------------------------------------



// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

// 呼叫 casino lib 來轉帳
require_once dirname(__FILE__) ."/casino/casino_config.php";


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
//var_dump($_SESSION);
// var_dump($_POST);
//var_dump($_GET);


// ----------------------------------
// 動作為會員登入檢查 login_check
// ----------------------------------
if($action == 'edit_auto_cashtotoken' AND isset($_SESSION['member']) AND $_SESSION['member']->therole == 'A' AND $_SESSION['member']->therole != 'T') {
  // 	每次儲值金額
  // var_dump($_POST);

  $pk = filter_var($_POST['pk'], FILTER_SANITIZE_NUMBER_INT);
  $autocash2token = filter_var($_POST['autocash2token'], FILTER_SANITIZE_STRING);

  $is_update = '0';

  $select_wallets_sql = "SELECT auto_gtoken, auto_min_gtoken, auto_once_gotken FROM root_member_wallets WHERE id = '".$pk."';";
  $select_wallets_sql_result = runSQLALL($select_wallets_sql);
  // var_dump($select_wallets_sql_result);


  if ($select_wallets_sql_result[0] == 1) {

    // 交易開始
    $autocashtotoken_transaction_sql = 'BEGIN;';

    if ($autocash2token != '' AND ($autocash2token == $tr['on'] OR $autocash2token == $tr['off'])) {
      if ($autocash2token == $tr['on']) {
        $autocash2token_status = 1;
      } else {
        $autocash2token_status = 0;
      }

      if ($autocash2token_status != $select_wallets_sql_result[1]->auto_gtoken) {
        $is_update = true;
        $autocashtotoken_transaction_sql = $autocashtotoken_transaction_sql."
        UPDATE root_member_wallets SET changetime = NOW(), auto_gtoken = '$autocash2token_status' WHERE id = '$pk';
        ";
      }

    } else {
      $error['messages'] = $tr['Illegal test'];//'不合法的測試。'
      // echo '<p align="center"><button type="button" class="btn btn-danger" onclick="window.location.reload();">'.$error['messages'].'</button></p>';
      echo '<script>alert("'.$error['messages'].'");</script>';
      die();
    }

    if ($_POST['tokenautostart'] != '') {
      if (!filter_var($_POST['tokenautostart'], FILTER_VALIDATE_INT) === false) {
        $tokenautostart = floor($_POST['tokenautostart']);

        if ($tokenautostart != $select_wallets_sql_result[1]->auto_min_gtoken AND $select_wallets_sql_result[1]->auto_once_gotken > $tokenautostart AND $tokenautostart >= 1) {
          $is_update = true;
          $autocashtotoken_transaction_sql = $autocashtotoken_transaction_sql."
          UPDATE root_member_wallets SET changetime = NOW(), auto_min_gtoken = '$tokenautostart' WHERE id = '$pk';
          ";
        } elseif ($tokenautostart != $select_wallets_sql_result[1]->auto_min_gtoken OR $select_wallets_sql_result[1]->auto_once_gotken < $tokenautostart) {
          $error['code'] = 402;
          //最低自動轉帳餘額, 不可以小於 CNY 1 元 且 不可以大於每次儲值金額
          $error['messages'] = $tr['min auto deposit amount error'];
          echo '<script>alert("'.$error['messages'].'");</script>';
          die();
        }

      }else{
        $error['messages'] = $tr['Please enter an integer greater than 0'];//'請輸入一個大於 0 的整數。'
        // echo '<p align="center"><button type="button" class="btn btn-danger" onclick="window.location.reload();">'.$error['messages'].'</button></p>';
        echo '<script>alert("'.$error['messages'].'");</script>';
        die();
      }
    }

    if ($_POST['tokenoncesave'] != '') {
      if (!filter_var($_POST['tokenoncesave'], FILTER_VALIDATE_INT) === false) {
        $tokenoncesave = floor($_POST['tokenoncesave']);

        if ($tokenoncesave != $select_wallets_sql_result[1]->auto_once_gotken AND $tokenoncesave > $select_wallets_sql_result[1]->auto_min_gtoken AND $tokenoncesave >= 1) {
          $is_update = true;
          $autocashtotoken_transaction_sql = $autocashtotoken_transaction_sql."
          UPDATE root_member_wallets SET changetime = NOW(), auto_once_gotken = '$tokenoncesave' WHERE id = '$pk';
          ";
        } else {
          $error['code'] = 402;
          //每次儲值金額, 不可以小於 1 元 且 不可以小於 最低自動轉帳餘額
          $error['messages'] = $tr['auto deposit amount error'];
          echo '<script>alert("'.$error['messages'].'");</script>';
          die();
        }
      }else{
        $error['messages'] = $tr['Please enter an integer greater than 0'];//'請輸入一個大於 0 的整數。'
        // echo '<p align="center"><button type="button" class="btn btn-danger" onclick="window.location.reload();">'.$error['messages'].'</button></p>';
        echo '<script>alert("'.$error['messages'].'");</script>';
        die();
      }
    }

    // commit 提交
    $autocashtotoken_transaction_sql = $autocashtotoken_transaction_sql.'COMMIT;';
    // var_dump($autocashtotoken_transaction_sql);

    if ($is_update == true) {
      // 執行 transaction sql
      $autocashtotoken_transaction_result = runSQLtransactions($autocashtotoken_transaction_sql);

      if($autocashtotoken_transaction_result) {
        $error['code'] = 100;
        //每次儲值金額餘額更新為 CNY $value 完成
        // $error['messages'] = $tr['auto deposit balance update'].$value.$tr['complete'];

        $error['messages'] = $tr['auto deposit setting update success'];//'自动储值设定更新成功。'
        // echo '<p align="center"><button type="button" class="btn btn-success" onclick="window.location.reload();">'.$error['messages'].'</button></p>';
        echo '<script>alert("'.$error['messages'].'");location.reload();</script>';
      }else{
        $error['code'] = 403;
        //資料庫更新失敗
        // $error['messages'] = $tr['DB update error'];

        $error['messages'] = $tr['auto deposit setting update failed'];//'自動儲值設定更新失敗。'
        // echo '<p align="center"><button type="button" class="btn btn-danger" onclick="window.location.reload();">'.$error['messages'].'</button></p>';
        echo '<script>alert("'.$error['messages'].'");</script>';
      }
    }

  } else {
    $error['code'] = 401;
    //資料庫存取錯誤
    $error['messages'] = $tr['DB access error'];
  }


// --------------------------------------------------------------------------------
/*
}elseif($action == 'gtokenrecycling' AND isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T'  AND ($_SESSION['member']->therole == 'M' OR $_SESSION['member']->therole == 'A' OR  $_SESSION['member']->therole == 'R' ) ) {
  // 回收所有娛樂城的代幣
  var_dump($_POST);
  //取回所有娛樂城的餘額
  echo $tr['get back all casino'];
  echo '<a href="lobby_mggame_action.php?a=Retrieve_MG_Casino_balance">'.$tr['get back all casino'].'</a>';
  $return_html = "<script>window.open('http://tw.yahoo.com',  'Yahoo', config='height=500,width=500');</script>";
  // $return_html = '<button onclick="window.close();" class="btn btn-success" type="submit">關閉</button>';
  echo $return_html;
*/

}elseif($action == 'manual_gcashtogtoken' AND isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T' ) {
  // var_dump($_POST);

  // $pk = filter_var($_POST['pk'], FILTER_SANITIZE_NUMBER_INT);
  $pk = $_SESSION['member']->id;
  $manual_amount = filter_var($_POST['manual_amount'], FILTER_SANITIZE_STRING);

  /*
  選擇小於100的儲值金額, post過來的會是小於100餘額的字串
  如果為該字串就將值設為99
  */
  if ($manual_amount == $tr['deposit amount less 100']) {
    $value = 99;
  } else {
    $value = $manual_amount;
  }


  // 取得會員與錢包資訊
  $member_acc_result = get_acc_data($pk, 'member_id');
  // var_dump($member_acc_result);


  /*
  檢查項目 :

  1. 檢查帳號是否有效
  2. 檢查代幣是否在娛樂城
  3. 檢查儲值金額是否為整數
  */

  // 1. 檢查帳號是否有效
  if ($member_acc_result != NULL) {
    // 2. 檢查代幣是否在娛樂城
    // if($member_acc_result->gtoken_lock == NULL OR $member_acc_result->gtoken_lock == '') {
      // 3. 檢查儲值金額是否為整數
      if (!filter_var($value, FILTER_VALIDATE_INT) === false) {
        // $value = floor($_POST['value']);

        if ($value == '99') {
          if ($member_acc_result->gcash_balance < 100 AND $member_acc_result->gcash_balance >= 1) {
            $value = $member_acc_result->gcash_balance;
          } else {
            if ($member_acc_result->gcash_balance > 100) {
              $logger = $tr['balance greater than 100 message'];//'餘額大於100，請選擇其他儲值金額再行操作。'
              memberlog2db($_SESSION['member']->account,'manual_gcashtogtoken','warning', "$logger");
              // echo '<p align="center"><button type="button" class="btn btn-danger" onclick="window.location.reload();">'.$logger.'</button></p>';
              echo json_encode(['status' => 'fail', 'result' => $logger]);
              die();
            } elseif($member_acc_result->gcash_balance < 1) {
              $logger = $tr['balance less than 1 message'];//'餘額小於1，無法進行手動儲值。'
              memberlog2db($_SESSION['member']->account,'manual_gcashtogtoken','warning', "$logger");
              // echo '<p align="center"><button type="button" class="btn btn-danger" onclick="window.location.reload();">'.$logger.'</button></p>';
              echo json_encode(['status' => 'fail', 'result' => $logger]);
              die();
            }
          }
        }


        $gcashtogtoken_result = auto_gcash2gtoken($member_acc_result->id, $member_acc_result->account, $value, $member_acc_result->withdrawalspassword, 0);

        if($gcashtogtoken_result['code'] == 1){
          // echo '<script>location.reload();</script>';
          echo json_encode(['status' => 'success', 'result' => ['msg' => $tr['Currency conversion completed'], 'tid' => $gcashtogtoken_result['transaction_id']['deposit']]]);//'币别转换完成'
        }else{
          // echo '<p align="center"><button type="button" class="btn btn-danger" onclick="window.location.reload();">'.$gcashtogtoken_result['messages'].'</button></p>';
          echo json_encode(['status' => 'fail', 'result' => $gcashtogtoken_result['messages']]);
          die();
        }


      }else{
        // 3. else

        $logger = $tr['Stored value failed'];//'儲值失敗，儲值金額必需為整數。'
        memberlog2db($_SESSION['member']->account,'manual_gcashtogtoken','warning', "$logger");
        // echo '<p align="center"><button type="button" class="btn btn-danger" onclick="window.location.reload();">'.$logger.'</button></p>';
        echo json_encode(['status' => 'fail', 'result' => $logger]);
        die();
      }

    // } else {
    //   // 2. else
    //
    //   $logger = '儲值失敗，請取回娛樂城代幣後再行操作。';
    //   memberlog2db($_SESSION['member']->account,'manual_gcashtogtoken','warning', "$logger");
    //   echo '<p align="center"><button type="button" class="btn btn-danger" onclick="window.location.reload();">'.$logger.'</button></p>';
    // }

  } else {
    // 1. else

    $logger = $tr['Account query error'];//'帐号查询错误，请联络客服协助。'
    memberlog2db($_SESSION['member']->account,'manual_gcashtogtoken','warning', "$logger");
    // echo '<p align="center"><button type="button" class="btn btn-danger" onclick="window.location.reload();">'.$logger.'</button></p>';
    echo json_encode(['status' => 'fail', 'result' => $logger]);
    die();
  }


}elseif($action == 'test') {
// ----------------------------------------------------------------------------
// test developer
// ----------------------------------------------------------------------------
    //var_dump($_POST);

}


// ----------------------------------------------------------------------------
// 取得會員帳戶資料函式 start
// ----------------------------------------------------------------------------

/**
 * 根據不同條件取得會員帳戶資料
 *
 * @param [type] $who - 會員 id 或帳號
 * @param [type] $where_condition - 選擇根據 id 進行搜尋或是帳號
 *
 * @return array
 */
function get_acc_data($who, $where_condition) {
  if ($where_condition == 'member_id') {
    $member_acc_sql = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.id = '".$who."' AND root_member.status = '1';";
  } elseif($where_condition == 'member_account') {
    $member_acc_sql = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.account = '".$who."' AND root_member.status = '1';";
  }

  $member_acc_result  = runSQLall($member_acc_sql);

  if ($member_acc_result[0] == 1) {
    $member_acc_result = $member_acc_result[1];
  } else {
    $member_acc_result = NULL;
  }

  return $member_acc_result;
}

// ----------------------------------------------------------------------------
// 取得會員帳戶資料函式 end
// ----------------------------------------------------------------------------



?>
