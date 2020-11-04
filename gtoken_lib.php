<?php
// ----------------------------------------------------------------------------
// Features:	前台 -- 代幣操作處理專用用函式, 將處理代幣的函式集中統一處理.
// File Name:	gtoken_lib.php
// Author:		Barkley
// Related:   寫在函式說明 , 前台及後台各有哪些程式對應他
// Log:
// 2017.5.8  v0.1 by Barkley
// ----------------------------------------------------------------------------
/*
使用方式：在需要得 action 再引入使用即可, 無須每個檔案都載入。
// 引用代幣處理函式庫
require_once dirname(__FILE__) ."/gtoken_lib.php";

*/



// ----------------------------------------------------------------------------
// Features:
//   會員間, 代幣轉帳功能函式
// Usage:
//   member_depositgtoken($member_id, $source_transferaccount, $destination_transferaccount, $transaction_money, $password_verify_sha1, $summary, $transaction_category, $realcash, $auditmode_select, $auditmode_amount, $system_note_input=NULL, $debug=0)
// Input:
//   $member_id --> 會員ID 同時也是操作者 ID 也是轉帳人員
//   $source_transferaccount --> 指定轉帳帳號
//   $destination_transferaccount --> 目的帳號 , 有檢查是否被 casino 鎖定
//   $transaction_money --> 轉帳金額
//   $password_verify_sha1 --> 來源或管理員帳號的密碼驗證，驗證後才可以轉帳
//   $summary --> 摘要資訊
//   $transaction_category --> 交易類別
//   $realcash --> 實際存提
//   $auditmode_select --> 稽核模式，三種：免稽核freeaudit、存款稽核depositaudit、優惠存款稽核 shippingaudit
//   $system_note_input --> 系統轉帳文字資訊
//   $debug = 1 --> 進入除錯模式 , debug = 0 --> 關閉除錯
// Return:
//   code = 1  --> 成功
//   code != 1  --> 其他原因導致失敗
// Releated:
//   後台 member_depositgtoken.php
//   前台
//   使用到這個 lib , 如果修正的話, 需要一起修正
// Log:
//   by barkley 2017.5.7
// ----------------------------------------------------------------------------
// 1.前台線上入款到遊戲幣 20180529
// 2.前台遊戲幣取款，預扣"提款金額"，送出審查 20180530
// 3.前台遊戲幣取款，預扣"手續費"，送出審查 20180530
// 4.前台遊戲幣取款，預扣"預扣稽核不通過優惠金額"，送出審查 20180530
function member_gtoken_transfer($member_id, $source_transferaccount, $destination_transferaccount, $transaction_money, $password_verify_sha1,
 $summary, $transaction_category, $realcash, $auditmode_select, $auditmode_amount, $system_note_input=NULL, $debug=0,$merchantorderid='',$operator=NULL) {

  // 會員ID 同時也是操作者 ID 也是轉帳人員
  $d['member_id']                    = $member_id;
  // 指定轉帳帳號
  $d['source_transferaccount']       = $source_transferaccount;
  // 目的帳號
  $d['destination_transferaccount']  = $destination_transferaccount;
  // 轉帳金額
  $d['transaction_money']            = $transaction_money;
  // 稽核金額
  $d['auditmode_amount']            = $auditmode_amount;

   // 摘要資訊
   $d['summary']                     = $summary;
   // 交易類別
   $d['transaction_category']        = $transaction_category;
   // 實際存提
   $d['realcash']                    = $realcash;
   // 稽核模式，三種：免稽核freeaudit、存款稽核depositaudit、優惠存款稽核 shippingaudit
   $d['auditmode_select']            = $auditmode_select;
   // 來源或管理員帳號的密碼驗證，驗證後才可以轉帳
   $d['password_verify_sha1']        = $password_verify_sha1;
   // 系統轉帳文字資訊
   $d['system_note_input']           = $system_note_input;
   //入款單號或取款單號
   $d['transaction_id']              = $merchantorderid;
   // 操作人員
   $d['operator']                    = $operator;

   if($debug == 1) {
     echo '輸入的資訊';
     var_dump($d);
   }

   // 轉帳金額及稽核金額，需要為浮點數型態或是整數型態，才可以繼續. 浮點數取道小數點第二位。
  //  if (!filter_var($transaction_money, FILTER_VALIDATE_FLOAT) === false  AND !filter_var($auditmode_amount, FILTER_VALIDATE_FLOAT) === false) {
  if (!filter_var($transaction_money, FILTER_VALIDATE_FLOAT) === false) {
       // echo("轉帳金額 is an FLOAT");
       // 轉成浮點數, 預設四捨五入 2 位數。可以入款小數點的數字。


     // 取得管理員的帳號資料, 確認沒有被 lock 或是有效帳號
     $member_acc_sql = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.id = '".$d['member_id']."' AND root_member.status = '1';";
     $member_acc = runSQLall($member_acc_sql);
     if($debug == 1) {
       echo '取得管理員的帳號資料';
       var_dump($member_acc);
     }

     // 取得轉帳來源的帳號資料, 確認沒有被 lock 或是有效帳號
     $source_acc_sql = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.account = '".$d['source_transferaccount']."' AND root_member.status = '1';";
     $source_acc = runSQLall($source_acc_sql);
     if($debug == 1) {
       echo '取得轉帳來源的帳號資料';
       var_dump($source_acc);
     }


     // 取得目標帳號的資料, 確認沒有被 lock 或是有效帳號
     $check_acc_sql = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.account = '".$d['destination_transferaccount']."' AND root_member.status = '1';";
     $check_acc = runSQLall($check_acc_sql);
     if($debug == 1) {
       echo '取得目標帳號的資料';
       var_dump($check_acc);
     }

     // 三個帳號資料, 有資料
     if($check_acc[0] == 1 AND $member_acc[0] == 1 AND $source_acc[0] ==1 ){

       // 帳號正確
       $error['code'] = '1';
       $error['messages'] = '目標帳號, 來源帳號 及操作員帳號存在';

       // 檢查目標的代幣是否已經被鎖在娛樂城了
       // if($check_acc[1]->gtoken_lock == NULL) {

         // check 轉帳密碼是否正確 , 密碼須為管理員, 或是來源帳號的密碼
        //  if($d['password_verify_sha1'] == $member_acc[1]->passwd OR $d['password_verify_sha1'] == $source_acc[1]->passwd  ) {
        // 檢查轉帳密碼是否和來源帳號密碼一樣 , 也就是來源者需要同意才轉帳
        // 如果是管理員操作轉帳, 預設填入 tran5566 的密碼. 因為管理員不會知道會員的密碼, 所以給個固定值
        if($d['password_verify_sha1'] == $source_acc[1]->withdrawalspassword OR $d['password_verify_sha1'] == 'tran5566') {
           // correct
           $error['code'] = '1';
           $error['messages'] = '轉帳密碼正確';

           // 轉帳 gtoken 的動作

           // 0. 取得目的端使用者完整的資料
           //$destination_transferaccount_sql = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.account = '".$d['destination_transferaccount']."';";
           //$destination_transferaccount_result = runSQLALL($destination_transferaccount_sql);
           $destination_transferaccount_result = $check_acc;
           //var_dump($destination_transferaccount_result);
     			if($destination_transferaccount_result[0] == 1){
     				// 1. 取得來源端使用者完整的資料
     				$error['code'] = '1';
     				$error['messages'] = '取得來源端使用者完整的資料';

     				//$source_transferaccount_sql = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.account = '".$d['source_transferaccount']."';";
     				//$source_transferaccount_result = runSQLALL($source_transferaccount_sql);
             $source_transferaccount_result = $source_acc;
     				//var_dump($source_transferaccount_result);
     				if($source_transferaccount_result[0] == 1){
     					// 2. 檢查帳戶 $source_transferaccount 是否有錢,且大於 $transaction_money , 成立才工作,否則結束
     					if($source_transferaccount_result[1]->gtoken_balance >= $d['transaction_money']){
     						$error['code'] = '1';
     						$error['messages'] = $d['source_transferaccount'].'有餘額，且大於轉帳金額'.$d['transaction_money'];

     						// 來源ID $source_transferaccount_result[1]->id
     						// 目的ID $destination_transferaccount_result[1]->id

                 // 稽核判斷寫入 notes 的文字 , and 控制稽核金額
                 if($d['auditmode_select'] == 'depositaudit'){
                   $audit_notes = '存款稽核'.$d['auditmode_amount'];
                 }elseif($d['auditmode_select'] == 'shippingaudit'){
                   $audit_notes = '優惠稽核'.$d['auditmode_amount'];
                 }else{
                   if( $d['transaction_category'] == 'tokenpreferential'){
                     // 反水稽核 0 倍
                     $d['auditmode_amount'] = $d['transaction_money']*0;
                     $audit_notes = '反水稽核'.$d['auditmode_amount'];
                   }elseif( $d['transaction_category'] == 'tokenpay'){
                     $d['auditmode_amount'] = 0;
                     $audit_notes = '派彩免稽核'.$d['auditmode_amount'];
                   }else{
                     $audit_notes = '免稽核'.$d['auditmode_amount'];
                   }
                 }

                 if($debug == 1) {
                   var_dump($d);
                   var_dump($audit_notes);
                 }

     						// 交易開始
     						$transaction_money_sql = 'BEGIN;';

     						// 操作：root_member_wallets
     						// 來源帳號餘額刪除 transaction_money
     						$transaction_money_sql = $transaction_money_sql.
     						'UPDATE root_member_wallets SET changetime = NOW(), gtoken_balance = (SELECT (gtoken_balance-'.$d['transaction_money'].') as amount FROM root_member_wallets WHERE id = '.$source_transferaccount_result[1]->id.') WHERE id = '.$source_transferaccount_result[1]->id.';';
     						// 目的帳號加入上 transaction_money 餘額
     						$transaction_money_sql = $transaction_money_sql.
     						'UPDATE root_member_wallets SET changetime = NOW(), gtoken_balance = (SELECT (gtoken_balance+'.$d['transaction_money'].') as amount FROM root_member_wallets WHERE id = '.$destination_transferaccount_result[1]->id.') WHERE id = '.$destination_transferaccount_result[1]->id.';';

                 // 操作：root_member_gtokenpassbook
     						// PGSQL 新增 1 筆紀錄 帳號 source_transferaccount 轉帳到 destination_transferaccount 金額 transaction_money
                 $source_notes = "(管理员将".$d['source_transferaccount']. $d['summary'] ." 到 ".$d['destination_transferaccount'].'帐号, '.$audit_notes.')'.$d['system_note_input'];
                 $transaction_money_sql = $transaction_money_sql.
     						'INSERT INTO "root_member_gtokenpassbook" ("transaction_time", "deposit", "withdrawal", "system_note", "member_id", "currency", "summary", "source_transferaccount", "auditmode", "auditmodeamount", "realcash", "destination_transferaccount", "transaction_category", "balance","transaction_id","operator")'.
     						"VALUES ('now()', '0', '".$d['transaction_money']."', '".$source_notes."', '".$member_acc[1]->id."', '".$config['currency_sign']."', '".$d['summary']."', '".$d['source_transferaccount']."', '".$d['auditmode_select']."', '".$d['auditmode_amount']."', '".$d['realcash']."', '".$d['destination_transferaccount']."', '".$d['transaction_category']."',
                (SELECT gtoken_balance FROM root_member_wallets WHERE id = ".$source_transferaccount_result[1]->id."),
                 '". $d['transaction_id']."','".$d['operator'] ."');";

     						// PGSQL 新增 1 筆紀錄 帳號 destination_transferaccount 收到來自 source_transferaccount 金額 transaction_money
                 $destination_notes = "(管理员".$d['summary']."到".$d['destination_transferaccount']."帐号, ".$audit_notes.')'.$d['system_note_input'];
                 $transaction_money_sql = $transaction_money_sql.
                 'INSERT INTO "root_member_gtokenpassbook" ("transaction_time", "deposit", "withdrawal", "system_note", "member_id", "currency", "summary", "source_transferaccount", "auditmode", "auditmodeamount", "realcash", "destination_transferaccount", "transaction_category", "balance","transaction_id","operator")'.
     						"VALUES ('now()', '".$d['transaction_money']."', '0', '".$destination_notes."', '".$member_acc[1]->id."', '".$config['currency_sign']."', '".$d['summary']."', '".$d['destination_transferaccount']."', '".$d['auditmode_select']."', '".$d['auditmode_amount']."', '".$d['realcash']."', '".$d['source_transferaccount']."', '".$d['transaction_category']."', (SELECT gtoken_balance FROM root_member_wallets WHERE id = ".$destination_transferaccount_result[1]->id."),
                '".$d['transaction_id']."','".$d['operator'] ."' );";

                // echo(transaction_money_sql);die();
                 // commit 提交
     						$transaction_money_sql = $transaction_money_sql.'COMMIT;';
     						// echo '<p>'.$transaction_money_sql.'</p>';

                 if($debug==1) {
                   echo '<pre>';
                   print_r($transaction_money_sql);
                   echo '</pre>';
                 }

                 // 執行 transaction sql
     						$transaction_money_result = runSQLtransactions($transaction_money_sql);
     						if($transaction_money_result){
     							$error['code'] = '1';
                   //setlocale(LC_MONETARY, $config['default_lang']);
                   $transaction_money_html = money_format('%i', $d['transaction_money']);
     							$error['messages'] = '成功转帐从'.$d['source_transferaccount'].'到'.$d['destination_transferaccount'].'金额:'.$transaction_money_html;
     						}else{
     							$error['code'] = '7';
     							$error['messages'] = 'SQL转帐失败从'.$d['source_transferaccount'].'到'.$d['destination_transferaccount'].'金额'.$d['transaction_money'];;
     						}
                 // to exit
     					}else{
     						$error['code'] = '6';
     						$error['messages'] = $d['source_transferaccount'].'余额不足'.$d['transaction_money'];
     					}

     				}else{
     					$error['code'] = '4';
     					$error['messages'] = '查不到来源端的使用者'.$d['source_transferaccount'].'资料。';
     				}

     			}else{
             $error['code'] = '5';
             $error['messages'] = '查不到目的端的使用者'.$d['destination_transferaccount'].'资料。';
     			}

         }else{
           // incorrect
           $error['code'] = '3';
           $error['messages'] = '管理员或是来源帐号确认的密码不正确';
         }

       // }else{
       //   $error['code'] = '505';
       //   $error['messages'] = '代币钱包被锁定在'.$check_acc[1]->gtoken_lock.'请先取回娱乐城的钱包';
       // }

     }else{
       // error return
       $error['code'] = '2';
       $error['messages'] = '帐号有问题';
     }

   } else {
     $error['code'] = '521';
     $error['messages'] = '稽核金額 OR 轉帳金額 , 非整數或是浮點數金額';
     // echo '<p align="center"><button type="button" class="btn btn-danger" onclick="window.location.reload();">'.$error['messages'].'</button></p>';
     return($error);
   }



   if($debug == 1){
     var_dump($error);
   }

  return($error);
}
// ----------------------------------------------------------------------------
// 代幣轉帳函式 end
// ----------------------------------------------------------------------------

/**
 * 代幣轉帳 sql 組合
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
 *  'audit_notes' => '',
 *  'source_transferaccount' => (object)['id' => '', 'account' => ''],
 *  'destination_transferaccount' => (object)['id' => ', 'account' => ''],
 *  'fingertracker_remote_addr' => '',
 *  'fingertracker' => '',
 *  'realcash' => '',
 *  'auditmode_select' => '',
 *  'auditmode_amount' => '',
 *  'transaction_id' => ''
 * ]
 *
 */
function get_gtoken_transfer_sql(array $data)
{
  global $config;

  $source_wallets_sql  = <<<SQL
  UPDATE root_member_wallets
  SET changetime = NOW(),
      gtoken_balance = (SELECT (gtoken_balance-'{$data['transaction_money']}') as amount FROM root_member_wallets WHERE id = '{$data['source_transferaccount']->id}')
  WHERE id = '{$data['source_transferaccount']->id}';
SQL;

  $destination_wallets_sql  = <<<SQL
  UPDATE root_member_wallets
  SET changetime = NOW(),
      gtoken_balance = (SELECT (gtoken_balance+'{$data['transaction_money']}') as amount FROM root_member_wallets WHERE id = '{$data['destination_transferaccount']->id}')
  WHERE id = '{$data['destination_transferaccount']->id}';
SQL;

  // $transaction_id = $data['action'].date("YmdHis").$data['destination_transferaccount']->account.str_pad(mt_rand(1,999),3,'0',STR_PAD_LEFT);

  $source_notes = "(管理员将".$data['source_transferaccount']->account.$data['summary']." 到 ".$data['destination_transferaccount']->account."帐号)".$data['system_note'];
  $source_account_insert_sql = <<<SQL
  INSERT INTO "root_member_gtokenpassbook"
  (
    "transaction_time", "withdrawal", "system_note", "member_id", "currency",
    "summary", "source_transferaccount", "auditmode", "auditmodeamount", "realcash",
    "destination_transferaccount", "transaction_category", "balance", "transaction_id", "operator"
  ) VALUES (
    now(), '{$data['transaction_money']}', '{$source_notes}', '{$data['member_id']}', '{$config['currency_sign']}',
    '{$data['summary']}', '{$data['source_transferaccount']->account}', '{$data['auditmode_select']}', '{$data['auditmode_amount']}', '{$data['realcash']}',
    '{$data['destination_transferaccount']->account}', '{$data['transaction_category_index']}', (SELECT gtoken_balance FROM root_member_wallets WHERE id = '{$data['source_transferaccount']->id}'), '{$data['transaction_id']}', '{$data['operator']}'
  );
SQL;

  $destination_notes = "(管理员".$data['summary']."到".$data['destination_transferaccount']->account."帐号, ".$data['audit_notes'].")".$data['system_note'];
  $destination_account_insert_sql = <<<SQL
  INSERT INTO "root_member_gtokenpassbook"
  (
    "transaction_time", "deposit", "system_note", "member_id", "currency",
    "summary", "source_transferaccount", "auditmode", "auditmodeamount", "realcash",
    "destination_transferaccount", "transaction_category", "balance", "transaction_id", "operator"
  ) VALUES (
    now(), '{$data['transaction_money']}', '{$destination_notes}', '{$data['member_id']}', '{$config['currency_sign']}',
    '{$data['summary']}', '{$data['destination_transferaccount']->account}', '{$data['auditmode_select']}', '{$data['auditmode_amount']}', '{$data['realcash']}',
    '{$data['source_transferaccount']->account}', '{$data['transaction_category_index']}', (SELECT gtoken_balance FROM root_member_wallets WHERE id = '{$data['destination_transferaccount']->id}'), '{$data['transaction_id']}', '{$data['operator']}'
  );
SQL;

  $sql = $source_wallets_sql
        .$destination_wallets_sql
        .$source_account_insert_sql
        .$destination_account_insert_sql;

  return $sql;
}

?>
