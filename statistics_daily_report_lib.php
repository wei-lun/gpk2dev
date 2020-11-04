<?php
// ----------------------------------------
// Features:	每日營收日結報表--專用函式庫 (前台,後台.使用同個程式碼計算, 後續如果有新增娛樂城統計才會正確)
// File Name:	statistics_daily_report_lib.php
// Author:		Barkley
// 前台的相關資訊:
// betrecord_deltail.php 投注明細加總計算使用島此函示
// 後台的相關資訊:
// DB table:  root_statisticsdailyreport  每日營收日結報表
// Related:   每日營收報表, 搭配的程式功能說明
// statistics_daily_immediately.php    後台 - 即時統計 - 每日營收日結報表, 要修改下面的程式增加項目的時候，需要先使用這只程式即時測試函式並驗證。
// statistics_daily_report.php         後台 - 每日營收日結報表(讀取已生成資料庫頁面), 透過 php system 功能呼叫 statistics_daily_output_cmd.php 執行, 主要都從這個程式開始呼叫。
// statistics_daily_report_lib.php     後台 - 每日營收日結報表 - 專用函式庫(計算資料使用函式, 每個統計項目的公式都放這裡)
// statistics_daily_report_action.php  後台 - 每日營收日結報表動作程式 - 透過此程式呼叫 php system command 功能, 及其他後續擴充功能.
// statistics_daily_output_cmd.php     後台 - 每日營收日結報表(命令列模式, 主要用來排程生成日報表)
// command example: /usr/bin/php70 /home/testgpk2demo/web/begpk2/statistics_daily_report_output_cmd.php run 2017-02-26
// Log:
// 2017.2.27 改寫,原本的即時計算移除.以資料庫為主,排程定時統計。
// ----------------------------------------------------------------------------

  // -----------------------------------------
  // 代理商審查的函式 -- 取得本日 $today_date 貢獻的傭金金額
  // -----------------------------------------
  function agent_review($account, $today_date){

    $sql = "SELECT * FROM root_agent_review WHERE status = 1 AND account = '".$account."'" .
    "AND processingtime >= '$today_date 00:00:00-04' AND processingtime < '$today_date 24:00:00-04'
    ;";

    // var_dump($sql);
    $result_sql = runSQLall($sql);
    if($result_sql[0] == 1) {
      $r['amount']          = $result_sql[1]->amount;
      $r['commissioned']    = $result_sql[1]->commissioned;
      $r['applicationtime'] = $result_sql[1]->applicationtime;
      $r['code']            = 1;
      $r['messages']        = 'DATA';
    }else{
      $r['amount']   = NULL;
      $r['code']     = 0;
      $r['messages'] = 'No data';
    }
    // var_dump($r);
    return($r);
  }
  // -----------------------------------------

  // -----------------------------------------
  // MEGA CASINO 資料表函式 $casino_account 帶入錢包的 MEGA帳號
  // -----------------------------------------
  function bettingrecords_mega($casino_account , $today_date){
    // 統計用的 config 變數, 設定在 config.php
    global $stats_config;
    // 資料庫依據不同的條件變換資料庫檔案
    // $casino_bettingrecords_tables = 'test_mg_bettingrecords';
    $casino_bettingrecords_tables = $stats_config['mega_bettingrecords_tables'];

    // var_dump($casino_account);
    if($casino_account != NULL) {
      // 東部標準時（Eastern Standard Time；EST；UTC-5；R區）, 時間為 -05 才是正確的時間，夏令時間 -06 不列入計算。以美東時間每日為計算單位。
      $casino_sql = 'SELECT count("User") as accountnumber_count, SUM("BetValid") as totalwager_sum, SUM("BetResult") as profitlost_sum  FROM "'.$casino_bettingrecords_tables.'"  WHERE "User"'." = '$casino_account'  AND \"BetAt2\" >= '$today_date 00:00:00-04' AND \"BetAt2\" < '$today_date 24:00:00-04'  GROUP BY \"User\";";
      // var_dump($casino_sql);
      $casino_result = runSQLall_betlog($casino_sql,0,'MEGA');
      // var_dump($casino_result);

      if($casino_result[0] == 1) {
        // 注單量
        $r['accountnumber_count']  = $casino_result[1]->accountnumber_count;
        // 收入
        $r['TotalWager']  = $casino_result[1]->totalwager_sum;
        // 支出
        $r['ProfitLost'] = $casino_result[1]->profitlost_sum;
        // $r['ProgressiveWage']   = $casino_result[1]->ProgressiveWage;
        $r['TotalPayout']  = $r['TotalWager'] + $r['ProfitLost'];
        $r['code']        = 1;
        $r['messages']    = 'OK';
      }else{
        $r['accountnumber_count']   = 0;
        $r['code']     = 12;
        $r['messages'] = 'NO DATA';
      }
    }else{
      $r['accountnumber_count']   = NULL;
      $r['code']     = 9;
      $r['messages'] = 'ARGU ERROR';
    }
    // var_dump($r);
    return($r);
  }
  // -----------------------------------------

  // -----------------------------------------
  // PT CASINO 資料表函式 $casino_account 帶入錢包的 PT帳號
  // -----------------------------------------
  function bettingrecords_pt($casino_account , $today_date){
    // 統計用的 config 變數, 設定在 config.php
    global $stats_config;
    // 資料庫依據不同的條件變換資料庫檔案
    // $casino_bettingrecords_tables = 'test_mg_bettingrecords';
    $casino_bettingrecords_tables = $stats_config['pt_bettingrecords_tables'];

    // var_dump($casino_account);
    if($casino_account != NULL) {
      // 東部標準時（Eastern Standard Time；EST；UTC-5；R區）, 時間為 -05 才是正確的時間，夏令時間 -06 不列入計算。以美東時間每日為計算單位。
      $casino_sql = 'SELECT count("PlayerName") as accountnumber_count, SUM("Win") as totalwager_sum, SUM("Bet") as totalpayout_sum  FROM "'.$casino_bettingrecords_tables.'"  WHERE "PlayerName"'." = '$casino_account'  AND \"GameDate\" >= '$today_date 00:00:00-04' AND \"GameDate\" < '$today_date 24:00:00-04'  GROUP BY \"PlayerName\";";
      // var_dump($casino_sql);
      $casino_result = runSQLall_betlog($casino_sql,0,'PT');
      // var_dump($casino_result);

      if($casino_result[0] == 1) {
        // 注單量
        $r['accountnumber_count']  = $casino_result[1]->accountnumber_count;
        // 收入
        $r['TotalWager']  = $casino_result[1]->totalwager_sum;
        // 支出
        $r['TotalPayout'] = $casino_result[1]->totalpayout_sum;
        // $r['ProgressiveWage']   = $casino_result[1]->ProgressiveWage;
        $r['ProfitLost']  = $r['TotalWager'] - $r['TotalPayout'];
        $r['code']        = 1;
        $r['messages']    = 'OK';
      }else{
        $r['accountnumber_count']   = 0;
        $r['code']     = 12;
        $r['messages'] = 'NO DATA';
      }
    }else{
      $r['accountnumber_count']   = NULL;
      $r['code']     = 9;
      $r['messages'] = 'ARGU ERROR';
    }
    // var_dump($r);
    return($r);
  }
  // -----------------------------------------

  // -----------------------------------------
  // MG CASINO 資料表函式 $mg_account 帶入錢包的 MG帳號
  // -----------------------------------------
  function bettingrecords_mg($mg_account , $today_date){
    // 統計用的 config 變數, 設定在 system_config.php
    global $stats_config;
    // 資料庫依據不同的條件變換資料庫檔案
    // $mg_bettingrecords_tables = 'test_mg_bettingrecords';
    $mg_bettingrecords_tables = $stats_config['mg_bettingrecords_tables'];

/*
    // 判斷投注紀錄中，是否帳號有此日期資料, 沒有的話就跳過回應 NO data。
    $mg_count_sql = 'SELECT "AccountNumber" FROM '.$mg_bettingrecords_tables.' WHERE "AccountNumber"'." = '$mg_account'  AND gamereceivetime >= '$today_date 00:00:00-04' AND gamereceivetime < '$today_date 24:00:00-04';";
    //var_dump($mg_count_sql);
    $mg_count_result = runSQL_betlogmg($mg_count_sql);
    //var_dump($mg_count_result);
*/

    // var_dump($mg_account);
    if($mg_account != NULL) {
      // 東部標準時（Eastern Standard Time；EST；UTC-5；R區）, 時間為 -05 才是正確的時間，夏令時間 -06 不列入計算。以美東時間每日為計算單位。
      $mg_sql = 'SELECT count("AccountNumber") as accountnumber_count, SUM("TotalWager") as totalwager_sum, SUM("TotalPayout") as totalpayout_sum  FROM "'.$mg_bettingrecords_tables.'"  WHERE "AccountNumber"'." = '$mg_account'  AND gamereceivetime >= '$today_date 00:00:00-04' AND gamereceivetime < '$today_date 24:00:00-04'  GROUP BY \"AccountNumber\";";
      // var_dump($mg_sql);
      $mg_result = runSQLall_betlog($mg_sql,0,'MG');
      // var_dump($mg_result);

      if($mg_result[0] == 1) {
        // 注單量
        $r['accountnumber_count']  = $mg_result[1]->accountnumber_count;
        // 收入
        $r['TotalWager']  = $mg_result[1]->totalwager_sum/100;
        // 支出
        $r['TotalPayout'] = $mg_result[1]->totalpayout_sum/100;
        // $r['ProgressiveWage']   = $mg_result[1]->ProgressiveWage;
        $r['ProfitLost']  = $r['TotalWager'] - $r['TotalPayout'];
        $r['code']        = 1;
        $r['messages']    = 'OK';
      }else{
        $r['accountnumber_count']   = 0;
        $r['code']     = 12;
        $r['messages'] = 'NO DATA';
      }
    }else{
      $r['accountnumber_count']   = NULL;
      $r['code']     = 9;
      $r['messages'] = 'ARGU ERROR';
    }
    // var_dump($r);
    return($r);
  }
  // -----------------------------------------

  // -----------------------------------------
  //  代幣存款量 1
  // -----------------------------------------
  function gtokenpassbook_tokendeposit($gtoken_account , $today_date){
    if($gtoken_account != NULL){
      // 代幣存款量
      $gtoken_sql = "
      SELECT SUM(withdrawal) as withdrawal_sum, sum(deposit) as deposit_sum, count(withdrawal) as withdrawal_count
      FROM root_member_gtokenpassbook
      WHERE source_transferaccount = '".$gtoken_account."' AND transaction_category = 'tokendeposit'
      AND transaction_time >= '$today_date 00:00:00-04' AND transaction_time < '$today_date 24:00:00-04'
      ;";
      // var_dump($gtoken_sql);

      $gtoken_result = runSQLall($gtoken_sql);
      // var_dump($gtoken_result);
      if($gtoken_result[0] == 1){
        $r['withdrawal'] = $gtoken_result[1]->withdrawal_sum;
        $r['deposit'] = $gtoken_result[1]->deposit_sum;
        $r['withdrawal_count'] = $gtoken_result[1]->withdrawal_count;
        $r['balance'] = $gtoken_result[1]->deposit_sum - $gtoken_result[1]->withdrawal_sum;
        $r['code']       = TRUE;
        $r['messages']   = 'No Data';
      }else{
        $r['code']     = 12;
        $r['messages'] = 'DB query error';
      }

    }else{
      $r['code']                = 0;
      $r['messages']            = 'No Data';
    }
    // var_dump($r);
    return $r;
  }
  // -----------------------------------------

  // -----------------------------------------
  // 代幣提款量 2
  // 會員提款代幣withdrawal會預先扣款，但是可能審核不通過退款。會把會退款紀錄在 deposit
  // -----------------------------------------
  function gtokenpassbook_tokengcash($gtoken_account, $today_date){
    if($gtoken_account != NULL){
      // 代幣轉現金
      $gtoken_sql = "
      SELECT SUM(withdrawal) as withdrawal_sum, sum(deposit) as deposit_sum, count(withdrawal) as withdrawal_count
      FROM root_member_gtokenpassbook
      WHERE source_transferaccount = '".$gtoken_account."' AND transaction_category = 'tokengcash'
      AND transaction_time >= '$today_date 00:00:00-04' AND transaction_time < '$today_date 24:00:00-04'
      ;";
      //var_dump($gtoken_sql);
      $gtoken_result = runSQLall($gtoken_sql);
      // var_dump($gtoken_result);
      if($gtoken_result[0] == 1){
        $r['withdrawal'] = $gtoken_result[1]->withdrawal_sum;
        $r['deposit'] = $gtoken_result[1]->deposit_sum;
        $r['withdrawal_count'] = $gtoken_result[1]->withdrawal_count;
        $r['balance'] = $gtoken_result[1]->deposit_sum - $gtoken_result[1]->withdrawal_sum;
        $r['code']       = TRUE;
        $r['messages']   = 'No Data';
      }else{
        $r['code']     = 12;
        $r['messages'] = 'DB query error';
      }

    }else{
      $r['code']                = 0;
      $r['messages']            = 'No Data';
    }
    // var_dump($r);
    return $r;
  }
  // -----------------------------------------

  // -----------------------------------------
  //  代幣優惠量 3
  // -----------------------------------------
  function gtokenpassbook_tokenfavorable($gtoken_account, $today_date){
    $gtoken_sql = "
    SELECT SUM(deposit) as deposit_sum, SUM(withdrawal) as withdrawal_sum, count(withdrawal) as withdrawal_count
    FROM root_member_gtokenpassbook
    WHERE source_transferaccount = '".$gtoken_account."' AND transaction_category = 'tokenfavorable'
    AND transaction_time >= '$today_date 00:00:00-04' AND transaction_time < '$today_date 24:00:00-04'
    ;";
    // var_dump($gtoken_sql);
    $gtoken_result = runSQLall($gtoken_sql);
    // var_dump($gtoken_result);
    if($gtoken_result[0] == 1){
      $r['withdrawal']  = $gtoken_result[1]->withdrawal_sum;
      $r['deposit']     = $gtoken_result[1]->deposit_sum;
      $r['withdrawal_count'] = $gtoken_result[1]->withdrawal_count;
      // 對於個人使用者，存款 - 取款，為當日的進帳。
      $r['balance']     = $gtoken_result[1]->deposit_sum - $gtoken_result[1]->withdrawal_sum;
      $r['code']        = 1;
      $r['messages']    = 'success';
    }else{
      $r['code']     = 12;
      $r['messages'] = 'DB query error';
    }

/*
    // 檢查有資料 debug
    if($r['balance'] != 0){
      var_dump($r);
      var_dump($gtoken_sql);
    }
*/
    // var_dump($r);
    return $r;
  }
  // -----------------------------------------

  // -----------------------------------------
  //  代幣反水量 4
  // -----------------------------------------
  function gtokenpassbook_tokenpreferential($gtoken_account, $today_date){
    $gtoken_sql = "
    SELECT SUM(deposit) as deposit_sum, SUM(withdrawal) as withdrawal_sum, count(withdrawal) as withdrawal_count
    FROM root_member_gtokenpassbook
    WHERE source_transferaccount = '".$gtoken_account."' AND transaction_category = 'tokenpreferential'
    AND transaction_time >= '$today_date 00:00:00-04' AND transaction_time < '$today_date 24:00:00-04'
    ;";
    // var_dump($gtoken_sql);
    $gtoken_result = runSQLall($gtoken_sql);
    // var_dump($gtoken_result);
    if($gtoken_result[0] == 1){
      $r['withdrawal']  = $gtoken_result[1]->withdrawal_sum;
      $r['deposit']     = $gtoken_result[1]->deposit_sum;
      $r['withdrawal_count'] = $gtoken_result[1]->withdrawal_count;
      // 對於個人使用者，存款 - 取款，為當日的進帳。
      $r['balance']     = $gtoken_result[1]->deposit_sum - $gtoken_result[1]->withdrawal_sum;
      $r['code']        = 1;
      $r['messages']    = 'success';
    }else{
      $r['code']     = 12;
      $r['messages'] = 'DB query error';
    }
    // var_dump($r);
    return $r;
  }
  // -----------------------------------------

  // -----------------------------------------
  //  代幣派彩 5 -- for casino usage
  // -----------------------------------------
  function gtokenpassbook_tokenpay($gtoken_account, $today_date){
    // 派彩正常為 tokenpay
    // 但是為了區分那各娛樂城的派彩，所以多了 MG_ 來區隔是那個娛樂城的派彩 MG_tokenpay
    $gtoken_sql = "
    SELECT SUM(deposit) as deposit_sum, SUM(withdrawal) as withdrawal_sum, count(withdrawal) as withdrawal_count
    FROM root_member_gtokenpassbook
    WHERE source_transferaccount = '".$gtoken_account."' AND (transaction_category = 'MG_tokenpay' OR transaction_category = 'tokenpay')
    AND transaction_time >= '$today_date 00:00:00-04' AND transaction_time < '$today_date 24:00:00-04'
    ;";
    // var_dump($gtoken_sql);
    $gtoken_result = runSQLall($gtoken_sql);
    // var_dump($gtoken_result);
    if($gtoken_result[0] == 1){
      $r['withdrawal']  = $gtoken_result[1]->withdrawal_sum;
      $r['deposit']     = $gtoken_result[1]->deposit_sum;
      $r['withdrawal_count'] = $gtoken_result[1]->withdrawal_count;
      // 對於個人使用者，存款 - 取款，為當日的進帳。
      $r['balance']     = $gtoken_result[1]->deposit_sum - $gtoken_result[1]->withdrawal_sum;
      $r['code']        = 1;
      $r['messages']    = 'success';
    }else{
      $r['code']     = 12;
      $r['messages'] = 'DB query error';
    }
    // var_dump($r);
    return $r;
  }
  // -----------------------------------------

  // -----------------------------------------
  //  代幣回收 6
  // -----------------------------------------
  function gtokenpassbook_tokenrecycling($gtoken_account, $today_date){
    $gtoken_sql = "
    SELECT SUM(deposit) as deposit_sum, SUM(withdrawal) as withdrawal_sum, count(withdrawal) as withdrawal_count
    FROM root_member_gtokenpassbook
    WHERE source_transferaccount = '".$gtoken_account."' AND transaction_category = 'tokenrecycling'
    AND transaction_time >= '$today_date 00:00:00-04' AND transaction_time < '$today_date 24:00:00-04'
    ;";
    // var_dump($gtoken_sql);
    $gtoken_result = runSQLall($gtoken_sql);
    // var_dump($gtoken_result);
    if($gtoken_result[0] == 1){
      $r['withdrawal']  = $gtoken_result[1]->withdrawal_sum;
      $r['deposit']     = $gtoken_result[1]->deposit_sum;
      $r['withdrawal_count'] = $gtoken_result[1]->withdrawal_count;
      // 對於個人使用者，存款 - 取款，為當日的進帳。
      $r['balance']     = $gtoken_result[1]->deposit_sum - $gtoken_result[1]->withdrawal_sum;
      $r['code']        = 1;
      $r['messages']    = 'success';
    }else{
      $r['code']     = 12;
      $r['messages'] = 'DB query error';
    }
    // var_dump($r);
    return $r;
  }
  // -----------------------------------------

  // -----------------------------------------
  //  現金存款量 1
  // -----------------------------------------
  function gcashpassbook_cashdeposit($gcash_account, $today_date){
    $gcash_sql = "
    SELECT SUM(deposit) as deposit_sum, SUM(withdrawal) as withdrawal_sum, count(withdrawal) as withdrawal_count
    FROM root_member_gcashpassbook
    WHERE source_transferaccount = '".$gcash_account."' AND transaction_category = 'cashdeposit'
    AND transaction_time >= '$today_date 00:00:00-04' AND transaction_time < '$today_date 24:00:00-04'
    ;";
    $gcash_result = runSQLall($gcash_sql);
    // var_dump($gcash_sql);

    if($gcash_result[0] == 1){
      $r['withdrawal']  = $gcash_result[1]->withdrawal_sum;
      $r['deposit']     = $gcash_result[1]->deposit_sum;
      $r['withdrawal_count'] = $gcash_result[1]->withdrawal_count;
      // 對於個人使用者，存款 - 取款，為當日的進帳。
      $r['balance']     = $gcash_result[1]->deposit_sum - $gcash_result[1]->withdrawal_sum;
      $r['code']        = 1;
      $r['messages']    = 'success';
    }else{
      $r['code']     = 12;
      $r['messages'] = 'DB query error';
    }

/*
    // 檢查有資料 debug
    if($r['balance'] != 0){
      var_dump($r);
      var_dump($gcash_sql);
    }
*/
    // var_dump($r);
    return $r;
  }
  // -----------------------------------------

  // -----------------------------------------
  //  電子支付存款 2
  // -----------------------------------------
  function gcashpassbook_payonlinedeposit($gcash_account, $today_date){
    $gcash_sql = "
    SELECT SUM(deposit) as deposit_sum, SUM(withdrawal) as withdrawal_sum, count(withdrawal) as withdrawal_count
    FROM root_member_gcashpassbook
    WHERE source_transferaccount = '".$gcash_account."' AND transaction_category = 'payonlinedeposit'
    AND transaction_time >= '$today_date 00:00:00-04' AND transaction_time < '$today_date 24:00:00-04'
    ;";
    $gcash_result = runSQLall($gcash_sql);

    if($gcash_result[0] == 1){
      $r['withdrawal']  = $gcash_result[1]->withdrawal_sum;
      $r['deposit']     = $gcash_result[1]->deposit_sum;
      $r['withdrawal_count'] = $gcash_result[1]->withdrawal_count;
      // 對於個人使用者，存款 - 取款，為當日的進帳。
      $r['balance']     = $gcash_result[1]->deposit_sum - $gcash_result[1]->withdrawal_sum;
      $r['code']        = 1;
      $r['messages']    = 'success';
    }else{
      $r['code']     = 12;
      $r['messages'] = 'DB query error';
    }

/*
    // 檢查有資料 debug
    if($r['balance'] != 0){
      var_dump($r);
      var_dump($gcash_sql);
    }
*/
    // var_dump($r);
    return $r;
  }
  // -----------------------------------------

  // -----------------------------------------
  //  現金轉帳 3 , 存入 - 提出 = 現金轉帳額
  // -----------------------------------------
  function gcashpassbook_cashtransfer($gcash_account, $today_date){
    $gcash_sql = "
    SELECT SUM(deposit) as deposit_sum, SUM(withdrawal) as withdrawal_sum, count(withdrawal) as withdrawal_count
    FROM root_member_gcashpassbook
    WHERE source_transferaccount = '".$gcash_account."' AND transaction_category = 'cashtransfer'
    AND transaction_time >= '$today_date 00:00:00-04' AND transaction_time < '$today_date 24:00:00-04'
    ;";
    $gcash_result = runSQLall($gcash_sql);

    if($gcash_result[0] == 1){
      $r['withdrawal']  = $gcash_result[1]->withdrawal_sum;
      $r['deposit']     = $gcash_result[1]->deposit_sum;
      $r['withdrawal_count'] = $gcash_result[1]->withdrawal_count;
      // 對於個人使用者，存款 - 取款，為當日的進帳。
      $r['balance']     = $gcash_result[1]->deposit_sum - $gcash_result[1]->withdrawal_sum;
      $r['code']        = 1;
      $r['messages']    = 'success';
    }else{
      $r['code']     = 12;
      $r['messages'] = 'DB query error';
    }
/*
    // 檢查有資料 debug
    if($r['balance'] != 0){
      var_dump($r);
      var_dump($gcash_sql);
    }
*/
    // var_dump($r);
    return $r;
  }

  // -----------------------------------------

  // -----------------------------------------
  //  現金提款 4 ,雙面都要觀看，會有正負值。
  // -----------------------------------------
  function gcashpassbook_cashwithdrawal($gcash_account, $today_date){
    $gcash_sql = "
    SELECT SUM(deposit) as deposit_sum, SUM(withdrawal) as withdrawal_sum, count(withdrawal) as withdrawal_count
    FROM root_member_gcashpassbook
    WHERE source_transferaccount = '".$gcash_account."' AND transaction_category = 'cashwithdrawal'
    AND transaction_time >= '$today_date 00:00:00-04' AND transaction_time < '$today_date 24:00:00-04'
    ;";
    $gcash_result = runSQLall($gcash_sql);

    if($gcash_result[0] == 1){
      $r['withdrawal']  = $gcash_result[1]->withdrawal_sum;
      $r['deposit']     = $gcash_result[1]->deposit_sum;
      $r['withdrawal_count'] = $gcash_result[1]->withdrawal_count;
      // 對於個人使用者，存款 - 取款，為當日的進帳。
      $r['balance']     = $gcash_result[1]->deposit_sum - $gcash_result[1]->withdrawal_sum;
      $r['code']        = 1;
      $r['messages']    = 'success';
    }else{
      $r['code']     = 12;
      $r['messages'] = 'DB query error';
    }
/*
    // 檢查有資料 debug
    if($r['balance'] != 0){
      var_dump($r);
      var_dump($gcash_sql);
    }
*/
    // var_dump($r);
    return $r;
  }
  // -----------------------------------------


  // -----------------------------------------
  //  現金轉代幣 5
  // -----------------------------------------
  function gcashpassbook_cashgtoken($gcash_account, $today_date){
    $gcash_sql = "
    SELECT SUM(withdrawal) as withdrawal_sum, sum(deposit) as deposit_sum, count(withdrawal) as withdrawal_count
    FROM root_member_gcashpassbook
    WHERE source_transferaccount = '".$gcash_account."' AND transaction_category = 'cashgtoken'
    AND transaction_time >= '$today_date 00:00:00-04' AND transaction_time < '$today_date 24:00:00-04'
    ;";
    $gcash_result = runSQLall($gcash_sql);

    if($gcash_result[0] == 1){
      $r['withdrawal']  = $gcash_result[1]->withdrawal_sum;
      $r['deposit']     = $gcash_result[1]->deposit_sum;
      $r['withdrawal_count'] = $gcash_result[1]->withdrawal_count;
      // 對於個人使用者，存款 - 取款，為當日的進帳。
      $r['balance']     = $gcash_result[1]->deposit_sum - $gcash_result[1]->withdrawal_sum;
      $r['code']        = 1;
      $r['messages']    = 'success';
    }else{
      $r['code']     = 12;
      $r['messages'] = 'DB query error';
    }
/*
    // 檢查有資料 debug
    if($r['balance'] != 0){
      var_dump($r);
      var_dump($gcash_sql);
    }
*/
    // var_dump($r);
    return $r;
  }
  // -----------------------------------------





  // ----------------------------------------------------
  // 7.會員提領代幣提領轉現金的手續費用。 紀錄在 root_withdraw_review ，但是這裡以存簿 root_member_gtokenpassbook 的資訊為主。
  // ----------------------------------------------------
  function gtokenpassbook_tokenadministrationfees($gtoken_account, $today_date){

    if($gtoken_account != NULL){
      // 代幣轉現金
      $gtoken_sql = "
      SELECT SUM(withdrawal) as withdrawal_sum, sum(deposit) as deposit_sum, count(withdrawal) as withdrawal_count
      FROM root_member_gtokenpassbook
      WHERE source_transferaccount = '".$gtoken_account."' AND transaction_category = 'tokenadministrationfees'
      AND transaction_time >= '$today_date 00:00:00-04' AND transaction_time < '$today_date 24:00:00-04';";
      //var_dump($gtoken_sql);
      $gtoken_result = runSQLall($gtoken_sql);
      // var_dump($gtoken_result);


      if($gtoken_result[0] == 1){
        $r['withdrawal']  = $gtoken_result[1]->withdrawal_sum;
        $r['deposit']     = $gtoken_result[1]->deposit_sum;
        $r['withdrawal_count'] = $gtoken_result[1]->withdrawal_count;
        // 行政費用 = 提款扣除退款的結果
        $r['balance']     = $gtoken_result[1]->withdrawal_sum - $gtoken_result[1]->deposit_sum;
        $r['code']        = 1;
        $r['messages']    = 'success';
      }else{
        $r['code']     = 12;
        $r['messages'] = 'DB query error';
      }

    }else{
      $r['code']                = 0;
      $r['messages']            = 'No Data';
    }

/*
    // 檢查有資料 debug
    if($r['balance'] != 0){
      var_dump($r);
      var_dump($gtoken_sql);
    }
*/

    // var_dump($r);
    return $r;
  }
  // ----------------------------------------------------


  // ----------------------------------------------------
  // 8.現金提款手續費
  // ----------------------------------------------------
  function gtokenpassbook_cashadministrationfees($gtoken_account, $today_date){

    if($gtoken_account != NULL){
      $sql = "
      SELECT SUM(withdrawal) as withdrawal_sum, sum(deposit) as deposit_sum, count(withdrawal) as withdrawal_count
      FROM root_member_gcashpassbook
      WHERE source_transferaccount = '".$gtoken_account."' AND transaction_category = 'cashadministrationfees'
      AND transaction_time >= '$today_date 00:00:00-04' AND transaction_time < '$today_date 24:00:00-04';";
      //var_dump($sql);
      $result = runSQLall($sql);
      // var_dump($result);

      if($result[0] == 1){
        $r['withdrawal']  = $result[1]->withdrawal_sum;
        $r['deposit']     = $result[1]->deposit_sum;
        $r['withdrawal_count'] = $result[1]->withdrawal_count;
        // 行政費用 = 提款扣除退款的結果
        $r['balance']     = $result[1]->withdrawal_sum - $result[1]->deposit_sum;
        $r['code']        = 1;
        $r['messages']    = 'success';
      }else{
        $r['code']     = 12;
        $r['messages'] = 'DB query error';
      }

    }else{
      $r['code']                = 0;
      $r['messages']            = 'No Data';
    }

/*
    // 檢查有資料 debug
    if($r['balance'] != 0){
      var_dump($r);
      var_dump($sql);
    }
*/

    // var_dump($r);
    return $r;
  }
  // ----------------------------------------------------



  // ----------------------------------------------------
  // 9. 代幣提款為現金時，行政稽核不通過的行政費用
  // ----------------------------------------------------
  function gtokenpassbook_tokenadministration($gtoken_account, $today_date){

    if($gtoken_account != NULL){
      $sql = "
      SELECT sum(amount) as amount_sum, sum(administrative_amount) as administrative_amount_sum, sum(fee_amount) as fee_amount_sum, count(amount) as amount_count
      FROM root_withdraw_review
      WHERE status = '1' AND account = '".$gtoken_account."'
      AND processingtime >= '$today_date 00:00:00-04' AND processingtime < '$today_date 24:00:00-04';";
      //var_dump($sql);
      $result = runSQLall($sql);
      // var_dump($result);

      if($result[0] == 1){
        $r['amount']  = $result[1]->amount_sum;
        $r['administrative_amount']     = $result[1]->administrative_amount_sum;
        $r['fee_amount'] = $result[1]->fee_amount_sum;
        $r['amount_count'] = $result[1]->amount_count;
        $r['code']        = 1;
        $r['messages']    = 'success';
      }else{
        $r['code']     = 12;
        $r['messages'] = 'DB query error';
      }

    }else{
      $r['code']                = 0;
      $r['messages']            = 'No Data';
    }

/*
    // 檢查有資料 debug
    if($r['balance'] != 0){
      var_dump($r);
      var_dump($sql);
    }
*/

    // var_dump($r);
    return $r;
  }
  // ----------------------------------------------------


  // ---------------------------------------------------------------
  // check date format
  // ---------------------------------------------------------------
  // get example: ?current_datepicker=2017-02-03
  // ref: http://php.net/manual/en/function.checkdate.php
  function validateDate($date, $format = 'Y-m-d H:i:s')
  {
      $d = DateTime::createFromFormat($format, $date);
      return $d && $d->format($format) == $date;
  }
  // -----------------------------------------
?>
