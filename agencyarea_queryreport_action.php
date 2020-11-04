<?php
// ----------------------------------------------------------------------------
// Features: 接收並處理 agencyarea_queryreport.php 的請求
// File Name:	agencyarea_queryreport_action.php
// Author:		Neil
// Related: agencyarea_queryreport.php
// Log:
// ----------------------------------------------------------------------------



// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
require_once dirname(__FILE__) ."/config_betlog.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

// -----------------------------------------------------------------------------
// 前台 action 會員登入身份專用：檢查有沒有參數,以及是否帶有 session
if (!isset($_SESSION['member']) || $_SESSION['member']->therole == 'T'):
	echo login2return_url(2);
	die(' (x)deny to access.');
endif;

// 檢查請求的 action
if(isset($_GET['a'])) {
	$action = filter_var($_GET['a'], FILTER_SANITIZE_STRING);
	
	// 如果是登出系統, by pass　CSRF check
	if($action == 'logout') {
		// by pass CSRF check
	} else {
		// 檢查產生的 CSRF token 是否存在 , 錯誤就停止使用. 定義在 lib.php
		$csrftoken_ret = csrf_action_check();
		if($csrftoken_ret['code'] != 1) {
			die($csrftoken_ret['messages']);
    }
	}

} elseif($_SERVER['REQUEST_METHOD'] == 'POST') {
  $csrftoken_ret = csrf_action_check();
  if($csrftoken_ret['code'] != 1) {
    die($csrftoken_ret['messages']);
  }
	parse_str(file_get_contents('php://input'), $_POST);
	foreach($_POST as $key => $value) {
		unset($_POST[$key]);
		$_POST[str_replace('amp;', '', $key)] = $value;
  }
} else {
	// 不合法的測試, 轉回 home.php. function 定義在 lib.php
	echo login2return_url(2);
	die(' (x)deny to access.');
}
// -----------------------------------------------------------------------------

// ----------------------------------
// 動作為會員登入檢查 login_check
// ----------------------------------

// 身分 A 才能進行的操作
if($_SESSION['member']->therole == 'A') {
	file_get_contents('php://input');
	if(isset($_POST)) {
		switch ($_POST['action']) {
      case 'queryreport':
        $start_time = filter_var($_POST['start_time'], FILTER_SANITIZE_STRING);
        $end_time = filter_var($_POST['end_time'], FILTER_SANITIZE_STRING);
        $acc = filter_var($_POST['acc'], FILTER_SANITIZE_STRING);

        if (empty($start_time) OR empty($end_time) OR empty($acc)) {
          $html = '<div class="alert alert-danger" role="alert">* 請確認開始日、結束日及用戶名稱皆正確輸入</div>';
          echo $html;
          die();
        }

        $range_start = gmdate('Y-m-d',strtotime('- 1 month') + -4 * 3600);
        $range_end = gmdate('Y-m-d',time() + -4 * 3600);
        if ((strtotime($range_end) < strtotime($end_time)) OR (strtotime($range_start) > strtotime($start_time))) {
          $html = '<div class="alert alert-danger" role="alert">* 合法查詢時間 : '.$range_start.' ~ '.$range_end.'，請確認開始日、結束日是否正確</div>';
          echo $html;
          die();
        } elseif (strtotime($end_time) < strtotime($start_time)) {
          $html = '<div class="alert alert-danger" role="alert">* 結束日期不可小於開始日期，請確認開始日、結束日是否正確</div>';
          echo $html;
          die();
        }

        $member_data = get_member_data($acc, 'account');

        $isdownlinemember = checkin_member2root_list($_SESSION['member']->id, $depth = 20, $member_data->id);

        if (!$isdownlinemember) {
          $html = '<div class="alert alert-danger" role="alert">* 查詢帳號 : '.$acc.' 非下線會員，請重新查詢</div>';
          echo $html;
          die();
        }

        $direct_downlines = get_direct_downlines($member_data->id);

        $deposit_withdrawal_data = get_deposit_withdrawal_data($acc);

        $report = get_statistics_daily_report($member_data->account, $start_time, $end_time);
        
        $report_list = [];
        foreach ($report as $key => $value) {
          $report_list[$value->dailydate] = $value;   
        }

        $ouput_memberdata = (object)[
          'start_sime' => $start_time,
          'end_time' => $end_time,
          'member_data'=>$member_data,
          'lastlogin' => $member_data->lastlogin,
          'deposit_withdrawal_data' => $deposit_withdrawal_data,
          'downlinemember_count' => $direct_downlines,
          'statistics_daily_report' => (object)$report_list
        ];

        combination_ouputdata_html($ouput_memberdata);
				break;

			default:
				// Do Nothing
				break;
    }
  }
}

function get_tzonename()
{
  $tz = '+08';
  if(isset($_SESSION['member']->timezone) AND $_SESSION['member']->timezone != NULL) {
    $tz = $_SESSION['member']->timezone;
  }

  // 轉換時區所要用的 sql timezone 參數
  $tzsql = <<<SQL
  SELECT * 
  FROM pg_timezone_names
  WHERE name like '%posix/Etc/GMT%' 
  AND abbrev = '$tz'
SQL;

  $tzone = runSQLALL($tzsql);

  $tzonename = 'posix/Etc/GMT-8';
  if($tzone[0] == 1) {
    $tzonename = $tzone[1]->name;
  }

  return $tzonename;
}

function get_member_data($member, $search_method = 'id')
{
  $tzonename = get_tzonename();

  $sql = <<<SQL
  SELECT * , 
        to_char((enrollmentdate AT TIME ZONE '$tzonename'), 'YYYY-MM-DD HH24:MI:SS' ) AS enrollmentdate, 
        to_char((lastlogin AT TIME ZONE '$tzonename'), 'YYYY-MM-DD HH24:MI:SS' ) AS lastlogin
  FROM root_member
  JOIN root_member_wallets ON root_member.id=root_member_wallets.id 
  WHERE root_member.account = '$member'
SQL;

  if ($search_method == 'id') {
    $sql = <<<SQL
    SELECT * , 
          to_char((enrollmentdate AT TIME ZONE '$tzonename'), 'YYYY-MM-DD HH24:MI:SS' ) AS enrollmentdate, 
          to_char((lastlogin AT TIME ZONE '$tzonename'), 'YYYY-MM-DD HH24:MI:SS' ) AS lastlogin
    FROM root_member
    JOIN root_member_wallets ON root_member.id=root_member_wallets.id 
    WHERE root_member.id = '$member'
SQL;
  }

  $result = runSQLall($sql);
  if (empty($result[0])) {
    $html = '<div class="alert alert-danger" role="alert">* 帳號資料查詢失敗或無此帳號，請重新查詢</div>';
    echo $html;
    die();
  }

  return $result[1];
}

function get_direct_downlines($id, $depth=0)
{
	$sql = <<<SQL
  SELECT * FROM
  (WITH RECURSIVE upperlayer(id, parent_id, account, therole, nickname, favorablerule, grade, favorablerule, feedbackinfo, status, depth) AS (
    SELECT id, parent_id, account, therole, nickname, favorablerule, grade, favorablerule, feedbackinfo, status, 1
    FROM root_member WHERE parent_id= $id
  UNION ALL
    SELECT p.id, p.parent_id, p.account, p.therole, p.nickname, p.favorablerule, p.grade, p.favorablerule, p.feedbackinfo, p.status, u.depth+1
    FROM root_member p
    INNER JOIN upperlayer u ON u.id = p.parent_id
    WHERE u.depth <= $depth
  )
  SELECT * FROM upperlayer ) AS agent_tree

  LEFT JOIN (SELECT  parent_id, count(parent_id) as parent_id_count FROM root_member GROUP BY parent_id) AS agent_user_count
  ON agent_tree.parent_id = agent_user_count.parent_id ORDER BY agent_tree.parent_id , agent_tree.depth, agent_tree.id;
SQL;

  $result = runSQLall($sql);

  $downlinemember_count = (empty($result[0])) ? 0 : $result[1]->parent_id_count;

  return $downlinemember_count;
}

function checkin_member2root_list($member_id = NULL, $depth = 20, $guest_id = NULL) {

  //-- 從指定的 id 往上遞迴搜尋
  $sql =<<<SQL
   SELECT * FROM
   (WITH RECURSIVE subordinates(id, parent_id, account, nickname, wechat, therole, feedbackinfo, grade, enrollmentdate, commissionrule, status, depth)
   AS( SELECT id, parent_id, account, nickname, wechat, therole, feedbackinfo, grade, enrollmentdate, commissionrule, status, 1
   FROM root_member WHERE id = $guest_id
   UNION
   SELECT m.id, m.parent_id, m.account, m.nickname, m.wechat, m.therole, m.feedbackinfo, m.grade, m.enrollmentdate, m.commissionrule, m.status, s.depth+1
   FROM root_member m
   INNER JOIN subordinates s
   ON s.parent_id = m.id
   WHERE m.account != 'root' AND s.depth <= $depth
   )
  SELECT * FROM subordinates )as rootpath
  WHERE rootpath.id = $member_id;
SQL;

  $result = runSQLall($sql, 0, 'r');

  $isdownlinemember = (!empty($result[0]) AND $result[1]->depth != 1) ? true : false;

  return $isdownlinemember;
}

function get_deposit_withdrawal_data($acc)
{
  global $gtoken_cashier_account;
  global $gcash_cashier_account;

	// 代幣存款sql
	$gtoken_deposit_sql =<<<SQL
		SELECT SUM(deposit) AS deposit_sum, COUNT(deposit) AS deposit_count 
		FROM root_member_gtokenpassbook 
		WHERE transaction_category = 'tokendeposit' 
		AND source_transferaccount = '$acc'
		AND destination_transferaccount = '$gtoken_cashier_account'
SQL;

	$gtoken_deposit_sql_result = runSQLall($gtoken_deposit_sql);
	if (empty($gtoken_deposit_sql_result[0])) {
    $html = '<div class="alert alert-danger" role="alert">* 帳號 : '.$acc.' 代幣存款資料查詢失敗</div>';
    echo $html;
    die();
	}

	// 代幣提款sql
	$gtoken_withdrawal_sql =<<<SQL
		SELECT SUM(amount) AS amount_sum, COUNT(amount) AS amount_count 
		FROM root_withdraw_review 
		WHERE account = '$acc' 
		AND status = '1'
SQL;
	$gtoken_withdrawal_sql_result = runSQLall($gtoken_withdrawal_sql);
	if (empty($gtoken_withdrawal_sql_result[0])) {
    $html = '<div class="alert alert-danger" role="alert">* 帳號 : '.$acc.' 代幣取款資料查詢失敗</div>';
    echo $html;
    die();
	}

	// 現金存款sql
	$gcash_deposit_sql =<<<SQL
		SELECT SUM(deposit) AS deposit_sum, COUNT(deposit) AS deposit_count 
		FROM root_member_gcashpassbook 
		WHERE transaction_category = 'cashdeposit' 
		AND source_transferaccount = '$acc' 
		AND destination_transferaccount = '$gcash_cashier_account'
SQL;
	$gcash_deposit_sql_result = runSQLall($gcash_deposit_sql);
	if (empty($gcash_deposit_sql_result[0])) {
    $html = '<div class="alert alert-danger" role="alert">* 帳號 : '.$acc.' 現金存款資料查詢失敗</div>';
    echo $html;
    die();
	}

	// 現金提款sql
	$gcash_withdrawal_sql =<<<SQL
		SELECT SUM(amount) AS amount_sum, COUNT(amount) AS amount_count 
		FROM root_withdrawgcash_review
		 WHERE account = '$acc' 
		 AND status = '1'
SQL;
	$gcash_withdrawal_sql_result = runSQLall($gcash_withdrawal_sql);
	if (empty($gcash_withdrawal_sql_result[0])) {
    $html = '<div class="alert alert-danger" role="alert">* 帳號 : '.$acc.' 現金取款資料查詢失敗</div>';
    echo $html;
    die();
	}

	// 代幣存款次數及金額
	$result['gtoken_deposit_count'] = $gtoken_deposit_sql_result[1]->deposit_count;
	$result['gtoken_deposit_count_amount'] = money_format('%i', $gtoken_deposit_sql_result[1]->deposit_sum);
	// 代幣提款次數及金額
	$result['gtoken_withdrawal_count'] = $gtoken_withdrawal_sql_result[1]->amount_count;
	$result['gtoken_withdrawal_count_amount'] = money_format('%i', $gtoken_withdrawal_sql_result[1]->amount_sum);

	// 現金存款次數及金額
	$result['gcash_deposit_count'] = $gcash_deposit_sql_result[1]->deposit_count;
	$result['gcash_deposit_count_amount'] = money_format('%i', $gcash_deposit_sql_result[1]->deposit_sum);
	// 現金提款次數及金額
	$result['gcash_withdrawal_count'] = $gcash_withdrawal_sql_result[1]->amount_count;
	$result['gcash_withdrawal_count_amount'] = money_format('%i', $gcash_withdrawal_sql_result[1]->amount_sum);

	return (object)$result;
}

function get_statistics_daily_report($acc, $start_t, $end_t)
{
  $sql = <<<SQL
  SELECT dailydate, all_bets, all_wins, all_count 
  FROM root_statisticsdailyreport 
  WHERE member_account = '$acc' 
  AND dailydate >= '$start_t' 
  AND dailydate <= '$end_t'
  ORDER BY dailydate DESC
SQL;

$result = runSQLall($sql,0,'r');

  if (empty($result[0])) {
    $html = '<div class="alert alert-danger" role="alert">* 帳號 : '.$acc.' 個人投注紀錄資料查詢失敗</div>';
    echo $html;
    die();
  }

  unset($result[0]);

  return $result;
}

function get_lastlogin($acc)
{
  $sql = <<<SQL
  SELECT 
    to_char((occurtime AT TIME ZONE 'AST'),'YYYY-MM-DD HH24:MI:SS') AS log_time, 
    who AS log_account, 
    service, 
    message, 
    agent_ip AS log_ip, 
    fingerprinting_id AS log_fingerprinting 
  FROM root_memberlog 
  WHERE who = '$acc' 
  AND service = 'login' 
  ORDER BY log_time DESC LIMIT 1
SQL;

  $lastlogin_result = runSQLall($sql);

  $result = (!empty($lastlogin_result[0])) ? $lastlogin_result[1]->log_time : null;

  return $result;
}

function combination_ouputdata_html($outputdata)
{
  $member_data_html = '';

  $member_data_html = $member_data_html.'
  <tr>
    <th>查詢時間區間</th>
    <td id="time_range">'.$outputdata->start_sime.' ~ '.$outputdata->end_time.'</td>
  </tr>';

  $member_data_html = $member_data_html.'
  <tr>
    <th>查詢的會員帳號</th>
    <td id="member_acc">'.$outputdata->member_data->account.'</td>
  </tr>';

  $theroleicon = [
    'R' => '管理員',
    'A' => '代理商',
    'M' => '會員', 
    'T' => '試玩帳號'
  ];

  $member_data_html = $member_data_html.'
  <tr>
    <th>身分</th>
    <td id="therole">'.$theroleicon[$outputdata->member_data->therole].'</td>
  </tr>';

  $status_list = [
    '0' => '帳號停用', 
    '1' => '帳號有效', 
    '2' => '錢包凍結'
  ];

  $member_data_html = $member_data_html.'
  <tr>
    <th>目前狀態</th>
    <td id="status">'.$status_list[$outputdata->member_data->status].'</td>
  </tr>';

  $parent_acc = get_member_data($outputdata->member_data->parent_id);

  $member_data_html = $member_data_html.'
  <tr>
    <th>所屬代理</th>
    <td id="parent">'.$parent_acc->account.'</td>
  </tr>';
  unset($parent_acc);

  $member_data_html = $member_data_html.'
  <tr>
    <th>佣金設定等級</th>
    <td id="commission_lv">'.$outputdata->member_data->commissionrule.'</td>
  </tr>';

  $member_data_html = $member_data_html.'
  <tr>
    <th>反水設定等級</th>
    <td id="favorable_lv">'.$outputdata->member_data->favorablerule.'</td>
  </tr>';

  $gcash_balance_html = '<button type="button" class="btn btn-warning btn-sm mb-1"><span class="glyphicon glyphicon-yen"></span>'.$outputdata->member_data->gcash_balance.'</button>';
  $cash_deposit_withdrawal_info_html = '
	<p> 存款次數 <button type="button" class="btn btn-success btn-sm mb-1">'.$outputdata->deposit_withdrawal_data->gcash_deposit_count.'</button> 次，共 <button type="button" class="btn btn-success btn-xs">'.$outputdata->deposit_withdrawal_data->gcash_deposit_count_amount.'</button> 元 </p>
	<p> 取款次數 <button type="button" class="btn btn-success btn-sm mb-1">'.$outputdata->deposit_withdrawal_data->gcash_withdrawal_count.'</button> 次，共 <button type="button" class="btn btn-success btn-xs">'.$outputdata->deposit_withdrawal_data->gcash_withdrawal_count_amount.'</button> 元 </p>
  ';
  
  $member_data_html = $member_data_html.'
  <tr>
    <th>現金錢包狀態</th>
    <td id="cash_status">
      <p>餘額 : '.$gcash_balance_html.'</p>
      '.$cash_deposit_withdrawal_info_html.'
    </td>
  </tr>';

  $gtoken_balance_html = '<button type="button" class="btn btn-warning btn-sm mb-1"><span class="glyphicon glyphicon-bitcoin"></span>' . $outputdata->member_data->gtoken_balance . '</button>';
	$token_deposit_withdrawal_info_html = '
	<p> 存款次數 <button type="button" class="btn btn-success btn-sm mb-1">'.$outputdata->deposit_withdrawal_data->gtoken_deposit_count.'</button> 次，共 <button type="button" class="btn btn-success btn-xs">'.$outputdata->deposit_withdrawal_data->gtoken_deposit_count_amount.'</button> 元 </p>
	<p> 取款次數 <button type="button" class="btn btn-success btn-sm mb-1">'.$outputdata->deposit_withdrawal_data->gtoken_withdrawal_count.'</button> 次，共 <button type="button" class="btn btn-success btn-xs">'.$outputdata->deposit_withdrawal_data->gtoken_withdrawal_count_amount.'</button> 元 </p>
  ';
  
  $member_data_html = $member_data_html.'
  <tr>
    <th>代幣錢包狀態</th>
    <td id="token_status">
      <p>餘額 : ' . $gtoken_balance_html . '</p>
      '.$token_deposit_withdrawal_info_html.'
    </td>
  </tr>';

  $member_data_html = $member_data_html.'
  <tr>
    <th>銀行帳戶資訊</th>
    <td id="bankaccount_data">
      <p>銀行名稱 : '.$outputdata->member_data->bankname.'</p>
      <p>銀行號碼 : '.$outputdata->member_data->bankaccount.'</p>
      <p>銀行省份 : '.$outputdata->member_data->bankprovince.'</p>
      <p>銀行縣市 : '.$outputdata->member_data->bankcounty.'</p>
    </td>
  </tr>';

  $member_data_html = $member_data_html.'
  <tr>
    <th>聯絡方式</th>
    <td id="contact_method">
      <p>電子郵件 : '.$outputdata->member_data->email.'</p>
      <p>手機 : '.$outputdata->member_data->mobilenumber.'</p>
      <p>微信 : '.$outputdata->member_data->wechat.'</p>
      <p>QQ : '.$outputdata->member_data->qq.'</p>
    </td>
  </tr>';

  $enrollmentdate = (empty($outputdata->member_data->enrollmentdate)) ? '-' : $outputdata->member_data->enrollmentdate;
  $member_data_html = $member_data_html.'
  <tr>
    <th>加入會員時間</th>
    <td id="lastlogin">'.$enrollmentdate.'</td>
  </tr>';

  $lastlogin_result = get_lastlogin($outputdata->member_data->account);
  $lastlogin = (empty($lastlogin_result)) ? '-' : $lastlogin_result;
  $member_data_html = $member_data_html.'
  <tr>
    <th>最近登入時間</th>
    <td id="lastlogin">'.$lastlogin.'</td>
  </tr>';

  $member_data_html = $member_data_html.'
  <tr>
    <th>代理商直屬下線人數</th>
    <td id="downlinemember_count">'.$outputdata->downlinemember_count.'</td>
  </tr>';

  $data_html = '
  <div class="panel panel-success">
    <div class="panel-heading"><span class="glyphicon glyphicon-user" aria-hidden="true"></span>&nbsp;搜尋的會員資訊</div>
    <table class="table">
      <thead></thead>
      <tbody>
        '.$member_data_html.'
      </tbody>
    </table>
  </div>
  ';

  $table_colname_html = '
  <tr>
		<th class="text-left">日期</th>
    <th class="text-right">個人總投注</th>
    <th class="text-right">個人總派彩</th>
		<th class="text-right">注單量</th>
	</tr>
  ';

  $data_table_row = '';
  foreach ($outputdata->statistics_daily_report as $date => $report) {
    $data_table_row = $data_table_row.'
    <tr>
      <td class="text-left">'.$date.'</td>
      <td class="text-right"><span class="glyphicon glyphicon-usd"></span>'.$report->all_bets.'</td>
      <td class="text-right"><span class="glyphicon glyphicon-usd"></span>'.$report->all_wins.'</td>
      <td class="text-right">'.$report->all_count.'</td>
    </tr>
    ';
  }

  $table = '
  <div class="panel panel-success">
    <div class="panel-heading"><span class="glyphicon glyphicon-user" aria-hidden="true"></span>&nbsp;會員 '.$outputdata->member_data->account.' 投注紀錄('.$outputdata->start_sime.' ~ '.$outputdata->end_time.')</div>
    <table class="table">
      <thead></thead>
      <tbody>
        <table id="member_betrecord" class="table table-hover table-striped">
          <thead>
            '.$table_colname_html.'
          </thead>
          <tbody>
            '.$data_table_row.'
          </tbody>
        </table>
      </tbody>
    </table>
  </div>
  ';

  $html = $data_html.$table;

  echo $html;
}

?>
