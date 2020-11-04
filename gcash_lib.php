<?php
// ----------------------------------------------------------------------------
// Features:	前台 - 代幣操作處理專用用函式, 將處理代幣得函式集中統一處理.
// File Name:	gcash_lib.php
// Author:		Barkley
// Related:   寫在函式說明 , 前台及後台各有哪些程式對應他
// Log:
// 2017.5.9  v0.2 by Barkley
// ----------------------------------------------------------------------------
/*
使用方式：在需要得 action 再引入使用即可, 無須每個檔案都載入。
// 引用加盟金處理函式庫
require_once dirname(__FILE__) ."/gcash_lib.php";

*/




  // ----------------------------------------------------------------------------
  // Features:
  //   會員間, 現金轉帳功能函式
  // Usage:
  //   member_gtoken_transfer($transaction_category_index, $summary, $member_id, $source_transferaccount, $destination_transferaccount, $withdrawal_password, $transaction_money, $realcash, $system_note, $debug=0)
  // Input:
  //   $transaction_category --> 交易類別
  //   $summary --> 摘要資訊
  //   $member_id --> 操作者 ID
  //   $source_transferaccount --> 指定轉帳帳號
  //   $destination_transferaccount --> 目的帳號
  //   $withdrawal_password --> 來源或管理員帳號的密碼驗證，驗證後才可以轉帳
  //   $transaction_money --> 轉帳金額
  //   $realcash --> 實際存提
  //   $system_note --> 系統轉帳文字資訊
  //   $debug = 1 --> 進入除錯模式 , debug = 0 --> 關閉除錯
  // Return:
  //   code = 1  --> 成功
  //   code != 1  --> 其他原因導致失敗
  // Releated:
  //   後台
  //   前台 member_agentdepositgcash_action.php
  //   使用到這個 lib , 如果修正的話, 需要一起修正
  // Log:
  //   by barkley 2017.5.10
  // ----------------------------------------------------------------------------
  /*

  // 使用案例： member_agentdepositgcash.php_action.php

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

  // 限制只有上線可以轉給下線 , 其他關係不可以轉帳。檢查來源帳號和目的帳號的關係, 來源帳號是否為目的帳號 parent 。
  // 取得轉帳來源帳號
  $source_acc_sql     = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.account = '".$source_transferaccount."' AND root_member.status = '1';";
  $source_acc_result  = runSQLall($source_acc_sql);
  if($debug == 1) {
   echo '取得轉帳來源帳號';
   var_dump($source_acc_result);
  }

  // 轉帳目標帳號
  $destination_acc_sql     = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.account = '".$destination_transferaccount."' AND root_member.status = '1';";
  $destination_acc_result  = runSQLall($destination_acc_sql);
  if($debug == 1) {
   echo '轉帳目標帳號';
   var_dump($destination_acc_result);
  }

  // 來源帳號 == 目的帳號的上線, 才可以轉帳
  if($source_acc_result[1]->id == $destination_acc_result[1]->parent_id) {
    // 執行轉帳
    $error = member_gcash_transfer($transaction_category_index, $summary, $member_id, $source_transferaccount, $destination_transferaccount, $withdrawal_password, $transaction_money, $realcash, $system_note, $debug);
  }else{
    $error['code'] = '9';
    $error['messages'] = '只有代理商可以转给旗下会员 , 其他关系不可以转帐';
  }

  if($error['code'] == 1) {
    echo '<p align="center"><button type="button" class="btn btn-success" onclick="window.location.reload();">'.$error['messages'].'</button></p>';
  }else{
    echo '<p align="center"><button type="button" class="btn btn-danger" onclick="window.location.reload();">'.$error['messages'].'</button></p>';
  }
  // 結果
  //var_dump($error);
  */

  // 1.前台現金提款，預扣函式20180525， 會執行2次，一次提款金額，一次手續費
  // 2.前台線上存款，入到現金
  // 3.前台申請成為代理商，預扣款項
  // 4.前台現金轉帳給下線
  function member_gcash_transfer($transaction_category_index, $summary, $member_id, $source_transferaccount, $destination_transferaccount, $withdrawal_password, $transaction_money, $realcash, $system_note, $debug=0,$merchantorderid='',$operator=NULL){

    // 取得管理員的帳號資料
    $member_acc_sql     = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.id = '".$member_id."' AND root_member.status = '1';";
    $member_acc_result  = runSQLall($member_acc_sql);
    if($debug == 1) {
     echo '取得管理員的帳號';
     var_dump($member_acc_result);
    }

    // 取得轉帳來源帳號
    $source_acc_sql     = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.account = '".$source_transferaccount."' AND root_member.status = '1';";
    $source_acc_result  = runSQLall($source_acc_sql);
    if($debug == 1) {
     echo '取得轉帳來源帳號';
     var_dump($source_acc_result);
    }

    // 轉帳目標帳號
    $destination_acc_sql     = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.account = '".$destination_transferaccount."' AND root_member.status = '1';";
    $destination_acc_result  = runSQLall($destination_acc_sql);
    if($debug == 1) {
     echo '轉帳目標帳號';
     var_dump($destination_acc_result);
    }

    // (1)轉帳金額及稽核金額，需要為浮點數型態或是整數型態，才可以繼續. 浮點數取道小數點第二位。
    if (!(filter_var($transaction_money, FILTER_VALIDATE_FLOAT) === false)) {

      // (2)確認三個帳號都存在, 才繼續
      if($member_acc_result[0] == 1 AND $source_acc_result[0] == 1 AND $destination_acc_result[0] == 1) {

        // (3) 檢查來源帳號現金餘額, 是否大於等於轉帳金額
        if($source_acc_result[1]->gcash_balance >=  $transaction_money ) {

          // (4) 檢查轉帳密碼是否和來源帳號密碼一樣 , 也就是來源者需要同意才轉帳
          // 如果是管理員操作轉帳, 預設填入 tran5566 的密碼. 因為管理員不會知道會員的密碼, 所以給個固定值
          if($withdrawal_password == $source_acc_result[1]->withdrawalspassword OR $withdrawal_password == 'tran5566') {

            // (5) 開始轉帳
            // 操作 table: root_member_wallets 錢包表格
            // 提款(來源錢包) - 轉帳金額
            $source_wallets_sql  = 'UPDATE root_member_wallets SET changetime = NOW(), gcash_balance = (SELECT (gcash_balance-'.$transaction_money.') as amount FROM root_member_wallets WHERE id = '.$source_acc_result[1]->id.') WHERE id = '.$source_acc_result[1]->id.';';
            // 存款(目標錢包) + 轉帳金額
            $destination_wallets_sql  = 'UPDATE root_member_wallets SET changetime = NOW(), gcash_balance = (SELECT (gcash_balance+'.$transaction_money.') as amount FROM root_member_wallets WHERE id = '.$destination_acc_result[1]->id.') WHERE id = '.$destination_acc_result[1]->id.';';

            // 操作 table： root_member_gcashpassbook 現金存簿
            //提款交易訊息(來源帳號) -- 提款
            $d_gcash_notes = "(來源".$source_acc_result[1]->account."帳號成功轉入".$destination_acc_result[1]->account."帳號)".$system_note;
            $source_account_insert_sql = 'INSERT INTO "root_member_gcashpassbook" ("transaction_time", "withdrawal", "system_note", "member_id", "currency", "realcash", "summary", "source_transferaccount", "destination_transferaccount", "balance", "transaction_category","transaction_id","operator")'.
            "VALUES ('now()', '".$transaction_money."', '".$d_gcash_notes."', '".$member_acc_result[1]->id."', '".$config['currency_sign']."', '".$realcash."'
            ,'".$summary."', '".$source_acc_result[1]->account."', '".$destination_acc_result[1]->account."', (SELECT gcash_balance FROM root_member_wallets WHERE id = '".$source_acc_result[1]->id."'), '".$transaction_category_index."','".$merchantorderid."'
            ,'".$operator."');";
            // var_dump($deposit_dest_member_ins);
            //存款交易訊息(目標帳號) -- 存款
            $w_gcash_notes = "(目標".$destination_acc_result[1]->account."帳號收到來自".$source_acc_result[1]->account."帳號的金額)".$system_note;
            $destination_account_insert_sql = 'INSERT INTO "root_member_gcashpassbook" ("transaction_time", "deposit", "system_note", "member_id", "currency", "realcash", "summary", "source_transferaccount", "destination_transferaccount", "balance", "transaction_category","transaction_id","operator")'.
            "VALUES ('now()', '".$transaction_money."', '".$w_gcash_notes."', '".$member_acc_result[1]->id."', '".$config['currency_sign']."', '".$realcash."'
              , '".$summary."', '".$destination_acc_result[1]->account."','".$source_acc_result[1]->account."', (SELECT gcash_balance FROM root_member_wallets where id = '".$destination_acc_result[1]->id."'), '".$transaction_category_index."','".$merchantorderid."','".$operator."'
            );";
            // var_dump($deposit_dest_account_ins);

            // 最後資料輸入動態
            $transaction_money_sql = 'BEGIN;'
                .$source_wallets_sql
                .$destination_wallets_sql
                .$source_account_insert_sql
                .$destination_account_insert_sql
                .'COMMIT;';

            if($debug==1) {
              echo '<pre>';
              print_r($transaction_money_sql);
              echo '</pre>';
            }

            // 執行交易 SQL
            $transaction_money_result = runSQLtransactions($transaction_money_sql);
            if($transaction_money_result){
              $error['code'] = '1';
               //setlocale(LC_MONETARY, $config['default_lang']);
               $transaction_money_html = money_format('%i', $transaction_money);
              $error['messages'] = '成功转帐从'.$source_acc_result[1]->account.'到'.$destination_acc_result[1]->account.'金额:'.$transaction_money_html;
            }else{
              $error['code'] = '0';
              $transaction_money_html = money_format('%i', $transaction_money);
              $error['messages'] = '转帐失败从'.$source_acc_result[1]->account.'到'.$destination_acc_result[1]->account.'金额'.$transaction_money_html;
            }

          }else{
            // (4)
            $error['code'] = '4';
            $error['messages'] = '轉帳密碼錯誤。';
          }

        }else{
          // (3)
          $error['code'] = '3';
          $error['messages'] = '來源帳號餘額不足，停止轉帳。';
        }

      }else{
        // (2) else
        $error['code'] = '2';
        $error['messages'] = '来源、目的或是操作者帐号有问题。';
      }

    } else {
      // (1) else
      $error['code'] = '521';
      $error['messages'] = '轉帳金額不是數字型態。';
      // echo '<p align="center"><button type="button" class="btn btn-danger" onclick="window.location.reload();">'.$error['messages'].'</button></p>';
    }

    return($error);
  }
// ----------------------------------------------------------------------------
// 現金轉帳函式 end
// ----------------------------------------------------------------------------


/**
 * 現金轉帳 sql 組合
 *
 * @param array $data
 * @return string
 *
 * 傳入資料格式
 * $data = [
 *  'operator' => '',
 *  'member_id' => '',
 *  'transaction_money' => '',
 *  'transaction_category_index' => '',
 *  'summary' => '',
 *  'system_note' => '',
 *  'source_transferaccount' => (object)['id' => ', 'account' => ''],
 *  'destination_transferaccount' => (object)['id' => ', 'account' => ''],
 *  'fingertracker_remote_addr' => '',
 *  'fingertracker' => '',
 *  'realcash' => '',
 *  'transaction_id' => ''
 * ]
 *
 */
function get_gcash_transfer_sql(array $data)
{
  global $config;

  // 提款錢包
  $source_wallets_sql  = <<<SQL
  UPDATE root_member_wallets
  SET changetime = NOW(),
      gcash_balance = (SELECT (gcash_balance-'{$data['transaction_money']}') as amount FROM root_member_wallets WHERE id = '{$data['source_transferaccount']->id}')
  WHERE id = '{$data['source_transferaccount']->id}';
SQL;

  // 存款錢包
  $destination_wallets_sql  = <<<SQL
  UPDATE root_member_wallets
  SET changetime = NOW(),
      gcash_balance = (SELECT (gcash_balance+'{$data['transaction_money']}') as amount FROM root_member_wallets WHERE id = '{$data['destination_transferaccount']->id}')
  WHERE id = '{$data['destination_transferaccount']->id}';
SQL;

  // $transaction_id = $data['action'].date("YmdHis").$data['destination_transferaccount']->account.str_pad(mt_rand(1,999),3,'0',STR_PAD_LEFT);

  //提款交易訊息(來源帳號)
  $d_gcash_notes = "(來源".$data['source_transferaccount']->account."帳號成功轉入".$data['destination_transferaccount']->account."帳號)".$data['system_note'];
  $source_account_insert_sql = <<<SQL
  INSERT INTO "root_member_gcashpassbook"
  (
    "transaction_time", "withdrawal", "system_note", "member_id", "currency",
    "realcash", "summary", "source_transferaccount", "destination_transferaccount", "balance",
    "transaction_category", "transaction_id", "operator"
  ) VALUES (
    now(), '{$data['transaction_money']}', '{$d_gcash_notes}', '{$data['member_id']}', '{$config['currency_sign']}',
    '{$data['realcash']}', '{$data['summary']}', '{$data['source_transferaccount']->account}', '{$data['destination_transferaccount']->account}', (SELECT gcash_balance FROM root_member_wallets WHERE id = '{$data['source_transferaccount']->id}'),
    '{$data['transaction_category_index']}', '{$data['transaction_id']}', '{$data['operator']}'
  );
SQL;

  //存款交易訊息(目標帳號)
  $w_gcash_notes = "(目標".$data['destination_transferaccount']->account."帳號收到來自".$data['source_transferaccount']->account."帳號的金額)".$data['system_note'];
  $destination_account_insert_sql = <<<SQL
  INSERT INTO "root_member_gcashpassbook"
  (
    "transaction_time", "deposit", "system_note", "member_id", "currency",
    "realcash", "summary", "source_transferaccount", "destination_transferaccount", "balance",
    "transaction_category", "transaction_id", "operator"
  ) VALUES (
    now(), '{$data['transaction_money']}', '{$w_gcash_notes}', '{$data['member_id']}', '{$config['currency_sign']}',
    '{$data['realcash']}', '{$data['summary']}', '{$data['destination_transferaccount']->account}','{$data['source_transferaccount']->account}', (SELECT gcash_balance FROM root_member_wallets where id = '{$data['destination_transferaccount']->id}'),
    '{$data['transaction_category_index']}', '{$data['transaction_id']}', '{$data['operator']}'
  );
SQL;

  $sql = $source_wallets_sql
        .$destination_wallets_sql
        .$source_account_insert_sql
        .$destination_account_insert_sql;

  return $sql;
}

/**
 * 更新現金帳戶異動時間
  供會員端設定"隱藏現金帳戶"功能使用，前台將不會顯示現金相關功能
  若該帳號(60日內)有現金異動則會重新開啟現金功能
  此function將會紀錄帳號最後現金異動時間
  參數$gcash_log_exist = json_encode(['gcash_log_exist' => true(是否存在60日內現金交易紀錄),
   'last_log_date' => $current_date_time,(最後現金交易時間)
    'check_date' => $current_date_time(最後更新此資訊時間(前台若超過60日會檢查一次此資料正確性))
    ]);
 */
function update_gcash_log_exist($account){

  $current_date_time = gmdate('Y-m-d H:i', time()+-4 * 3600);

  $gcash_log_exist = json_encode(['gcash_log_exist' => true, 'last_log_date' => $current_date_time, 'check_date' => $current_date_time]);

  $update_query=<<<SQL
  UPDATE "root_member" SET
  "gcash_log_exist" = '{$gcash_log_exist}'
  WHERE "account" = '{$account}';
SQL;

  $update_sql_res = runSQLall($update_query);
}

?>
