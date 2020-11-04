<?php
// ----------------------------------------------------------------------------
// Features:	推廣註冊 lib
// Author:		Neil
// Related:
// Log: 
// ----------------------------------------------------------------------------

function set_linkmemcache($memcache, $key, $data, $timeout)
{
  global $config;
  global $system_mode;

  $memcache->addServer('localhost', 11211) or die ("Could not connect memcache server !! ");

  $key = ($system_mode == 'developer') ? $key.$config['website_domainname'] : $key.$config['projectid'];
  $memcache->set($key, $data, $timeout) or die ("Failed to save data at the memcache server");
}

function get_linkmemcache($memcache, $key)
{
  global $config;
  global $system_mode;

  $memcache->addServer('localhost', 11211) or die ("Could not connect memcache server !! ");
  $key = ($system_mode == 'developer') ? $key.$config['website_domainname'] : $key.$config['projectid'];

  return $memcache->get($key);
}

/**
 * 取得使用者所在時區
 *
 * @param string $tz
 * @return string
 */
function get_tzonename($tz)
{
  // 轉換時區所要用的 sql timezone 參數
  $tzsql = "select * from pg_timezone_names where name like '%posix/Etc/GMT%' AND abbrev = '".$tz."';";
  $tzone = runSQLALL($tzsql);

  if($tzone[0]==1) {
    $tzonename = $tzone[1]->name;
  } else {
    $tzonename = 'posix/Etc/GMT-8';
  }

  return $tzonename;
}

/**
 * 時間比較
 *
 * @param string $d1
 * @param string $d2
 * @return boolon
 */
function comparison_date($d1, $d2)
{
  if (strtotime($d1) > strtotime($d2)) {
    return false;
  }

  return true;
}

function getEDTDate()
{
  $tz = '-04';

  return gmdate('Y-m-d H:i:s',time() + $tz * 3600);
}

/**
 * 組合邀请码
 *
 * @param string $code
 * @return string
 */
function get_spreadlink($code)
{
  global $config;

  // $code = produce_link_code($acc, $recommendedcode);
  $link = 'https://'.$config['website_domainname'].'/register_app.php?r='.$code;

  return $link;
}

/**
 * 產生邀请码
 *
 * @return string
 */
function get_linkcode()
{
  $code = str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);

  return $code;
}

/**
 * 會員全部邀请码資料
 *
 * @param string $acc
 * @param string $recommendedcode
 * @param string $tzname
 * @return array
 */
function select_all_spreadlink($acc, $recommendedcode, $tzname = 'posix/Etc/GMT+4')
{
  $sql = <<<SQL
  SELECT
    *,
    root_spreadlink.link_code,
    to_char((root_spreadlink.start_date AT TIME ZONE '{$tzname}') ,'YYYY-MM-DD  HH24:MI:SS') AS start_date, 
    to_char((root_spreadlink.end_date AT TIME ZONE '{$tzname}') ,'YYYY-MM-DD  HH24:MI:SS') AS end_date,
    detail.visits_count,
    detail.register_count
  FROM root_spreadlink 
  LEFT JOIN (
    SELECT
      link_code,
      COUNT(CASE WHEN register_status = '0' THEN link_code END) visits_count,
      COUNT(CASE WHEN register_status = '1' THEN link_code END) register_count
    FROM root_spreadlink_detail 
    GROUP BY link_code
  ) AS detail 
  ON detail.link_code= root_spreadlink.link_code
  WHERE root_spreadlink.recommendedcode = '{$recommendedcode}'
  AND account = '{$acc}'
  AND status = '1'
  ORDER BY root_spreadlink.id DESC;
SQL;

  $result = runSQLALL($sql);

	if (empty($result[0])) {
		$error_msg = '邀请码查询错误';
		return array('status' => false, 'result' => $error_msg);
	}

  unset($result[0]);
	return array('status' => true, 'result' => $result);
}

function getSpreadLinkByLinkCode($code, $tzname = 'posix/Etc/GMT+4')
{
  $sql = <<<SQL
  SELECT
    root_spreadlink.link_code,
    root_spreadlink.validity_period,
    root_spreadlink.status,
    root_spreadlink.description,
    root_spreadlink.register_type,
    root_spreadlink.feedbackinfo,
    to_char((root_spreadlink.start_date AT TIME ZONE '{$tzname}') ,'YYYY-MM-DD  HH24:MI:SS') AS start_date, 
    to_char((root_spreadlink.end_date AT TIME ZONE '{$tzname}') ,'YYYY-MM-DD  HH24:MI:SS') AS end_date,
    detail.visits_count,
    detail.register_count
  FROM root_spreadlink 
  LEFT JOIN (
    SELECT
      link_code,
      COUNT(CASE WHEN register_status = '0' THEN link_code END) visits_count,
      COUNT(CASE WHEN register_status = '1' THEN link_code END) register_count
    FROM root_spreadlink_detail 
    GROUP BY link_code
  ) AS detail 
  ON detail.link_code = root_spreadlink.link_code
  WHERE root_spreadlink.link_code = '{$code}'
  AND root_spreadlink.status = '1'
  ORDER BY root_spreadlink.id DESC;
SQL;

  $result = runSQLall($sql);

  if (empty($result[0])) {
    return false;
  }

  unset($result[0]);

  return $result;
}

function getSpreadLinkByAccountType($acc, $type, $tzname = 'posix/Etc/GMT+4')
{
  $sql = <<<SQL
  SELECT
    root_spreadlink.link_code,
    root_spreadlink.validity_period,
    root_spreadlink.status,
    root_spreadlink.description,
    root_spreadlink.register_type,
    root_spreadlink.feedbackinfo,
    to_char((root_spreadlink.start_date AT TIME ZONE '{$tzname}') ,'YYYY-MM-DD  HH24:MI:SS') AS start_date, 
    to_char((root_spreadlink.end_date AT TIME ZONE '{$tzname}') ,'YYYY-MM-DD  HH24:MI:SS') AS end_date,
    detail.visits_count,
    detail.register_count
  FROM root_spreadlink 
  LEFT JOIN (
    SELECT
      link_code,
      COUNT(CASE WHEN register_status = '0' THEN link_code END) visits_count,
      COUNT(CASE WHEN register_status = '1' THEN link_code END) register_count
    FROM root_spreadlink_detail 
    GROUP BY link_code
  ) AS detail 
  ON detail.link_code = root_spreadlink.link_code
  WHERE root_spreadlink.account = '{$acc}'
  AND root_spreadlink.register_type = '{$type}'
  AND root_spreadlink.status = '1'
  ORDER BY root_spreadlink.id DESC;
SQL;

  $result = runSQLall($sql);

  if (empty($result[0])) {
    return false;
  }

  unset($result[0]);

  return $result;
}

/**
 * 取得指定邀请码資料
 *
 * @param string $id
 * @param string $acc
 * @param string $tzname
 * @return array
 */
function select_one_spreadlink($id, $acc, $tzname = 'posix/Etc/GMT+4')
{
  $sql = <<<SQL
  SELECT *, 
        to_char((start_date AT TIME ZONE '{$tzname}') ,'YYYY-MM-DD  HH24:MI:SS') AS start_date, 
        to_char((end_date AT TIME ZONE '{$tzname}') ,'YYYY-MM-DD  HH24:MI:SS') AS end_date
  FROM root_spreadlink
  WHERE id = '{$id}'
  AND account = '{$acc}'
  AND status = '1'
SQL;

  $result = runSQLALL($sql);

	if (empty($result[0])) {
		$error_msg = '邀请码查询错误';
		return array('status' => false, 'result' => $error_msg);
	}

  unset($result[0]);
	return array('status' => true, 'result' => $result[1]);
}

/**
 * 取得指定邀请码的推廣連結資料
 *
 * @param string $code
 * @return array
 */
function select_spreadlink_bylinkcode($code, $acc = '')
{
  global $tr;
  $tzname = 'posix/Etc/GMT+4';

  if ($acc == '') {
    $sql = <<<SQL
    SELECT *,
          to_char((start_date AT TIME ZONE '{$tzname}'),'YYYY-MM-DD HH24:MI:SS') AS start_date,
          to_char((end_date AT TIME ZONE '{$tzname}'),'YYYY-MM-DD HH24:MI:SS') AS end_date
    FROM root_spreadlink
    WHERE link_code = '{$code}'
    -- AND end_date > now()
    AND status = '1';
SQL;
  } else {
    $sql = <<<SQL
    SELECT *,
          to_char((start_date AT TIME ZONE '{$tzname}'),'YYYY-MM-DD HH24:MI:SS') AS start_date,
          to_char((end_date AT TIME ZONE '{$tzname}'),'YYYY-MM-DD HH24:MI:SS') AS end_date
    FROM root_spreadlink
    WHERE link_code = '{$code}'
    AND account = '{$acc}'
    -- AND end_date > now()
    AND status = '1';
SQL;
  }

  $result = runSQLALL($sql);

	if (empty($result[0])) {
    $error_msg = $tr['Invalid invitation code'];//'无效的邀请码'
		return array('status' => false, 'result' => $error_msg);
  }

  unset($result[0]);
	return array('status' => true, 'result' => $result[1]);
}

function select_all_linkcode()
{
  $sql = <<<SQL
    SELECT link_code
    FROM root_spreadlink
    WHERE status != 0;
SQL;

    $code = runSQLall($sql);
    unset($code[0]);

    return $code;
}

/**
 * 新增邀请码sql
 *
 * @param object $data
 * @return int
 */
function insert_link($data)
{
  if ($data->register_type == 'A') {
    $sql = <<<SQL
    INSERT INTO root_spreadlink (
      account, recommendedcode, link_code, 
      register_type, start_date, end_date, 
      validity_period, status, description,
      feedbackinfo
    ) VALUES (
      '{$data->account}', '{$data->recommendedcode}', '{$data->link_code}', 
      '{$data->register_type}', now(), now() + interval '{$data->interval_text}', 
      '{$data->validity_period}', '{$data->status}', '{$data->description}',
      '{$data->feedbackinfo}'
    );
SQL;
  } else {
    $sql = <<<SQL
    INSERT INTO root_spreadlink (
      account, recommendedcode, link_code, 
      register_type, start_date, end_date, 
      validity_period, status, description
    ) VALUES (
      '{$data->account}', '{$data->recommendedcode}', '{$data->link_code}', 
      '{$data->register_type}', now(), now() + interval '{$data->interval_text}', 
      '{$data->validity_period}', '{$data->status}', '{$data->description}'
    );
SQL;
  }

  $result = runSQL($sql);

  return $result;
}

/**
 * 更新邀请码資料
 *
 * @param object $data
 * @return int
 */
function edit_link($data)
{
  $sql = <<<SQL
  UPDATE root_spreadlink 
  SET validity_period = '{$data->validity_period}',
      start_date = now(),
      end_date = now() + interval '{$data->interval_text}',
      description = '{$data->description}',
      updatetime = now()
  WHERE link_code = '{$data->link_code}'
  AND account = '{$data->account}'
SQL;

  $result = runSQL($sql);

  return $result;
}

/**
 * 刪除邀请码資料
 *
 * @param object $data
 * @return int
 */
function del_link($data)
{
  $sql = <<<SQL
  UPDATE root_spreadlink 
  SET status = '0',
      updatetime = now() 
  WHERE link_code = '{$data->link_code}'
  AND account = '{$data->account}'
SQL;

  $result = runSQL($sql);

  return $result;
}

/**
 * 更新訪問量sql
 *
 * @param object $data
 * @return int
 */
function update_visits_number($data)
{
  $sql = <<<SQL
  INSERT INTO root_spreadlink_detail (
    link_code, 
    fingerprinting, 
    ip, 
    browser
  ) VALUES (
    '{$data->link_code}', 
    '{$data->fingerprinting}', 
    '{$data->ip}', 
    '{$data->browser}'
  )
SQL;

  $result = runSQL($sql);

  return $result;
}

/**
 * 更新註冊量sql
 *
 * @param object $data
 * @return int
 */
function update_register_number($data)
{
  // 

  $sql = <<<SQL
  INSERT INTO root_spreadlink_detail (
    link_code, 
    fingerprinting, 
    ip, 
    browser, 
    register_acc, 
    register_status
  ) VALUES (
    '{$data->link_code}', 
    '{$data->fingerprinting}', 
    '{$data->ip}', 
    '{$data->browser}',  
    '{$data->register_acc}', 
    '1'
  )
SQL;

  $result = runSQL($sql);

  return $result;
}

/**
 * 取得指定邀请码訪問量
 *
 * @param string $code
 * @return array
 */
// function get_all_visits_number($code)
// {
//   $sql = <<<SQL
//   SELECT count(link_code) 
//   FROM root_spreadlink_detail 
//   WHERE link_code = '{$code}' 
//   AND register_status = '0'
// SQL;

//   $result = runSQLall($sql);

//   if (empty($result[0])) {
//     $error_msg = '访问人数查询错误';
//     return array('status' => false, 'result' => $error_msg);
//   }

//   return array('status' => true, 'result' => $result[1]);
// }

/**
 * 取得指定邀请码註冊量
 *
 * @param string $code
 * @return array
 */
// function get_all_register_number($code)
// {
//   $sql = <<<SQL
//   SELECT count(link_code) 
//   FROM root_spreadlink_detail 
//   WHERE link_code = '{$code}' 
//   AND register_status = '1'
// SQL;

//   $result = runSQLall($sql);

//   if (empty($result[0])) {
// 		$error_msg = '注册人数查询错误';
// 		return array('status' => false, 'result' => $error_msg);
// 	}

// 	return array('status' => true, 'result' => $result[1]);
// }

/**
 * 取得現在時間(美東)
 *
 * @return string
 */
function get_nowdate()
{
  return gmdate('Y-m-d H:i:s',time() + -4*3600);
}

/**
 * 計算邀请码失效時間(美東)
 *
 * @param string $validity_period
 * @param string $sday
 * @return array
 */
function produce_date($validity_period, $sday = '')
{
  switch ($validity_period) {
    case '1':
    case '2':
    case '3':
      $t = '+ '.$validity_period.' days';
      break;
    case '10':
    case '20':
    case '30':
      $t = '+ '.($validity_period / 10).' month';
      break;
    default:
      $t = '+ 100 year';
      break;
  }

  $date['sdate'] = ($sday == '') ? get_nowdate() : $sday;

  $date['edate'] = '';
  if ($t != '0') {
    // $date['edate'] = gmdate('Y-m-d H:i:s',strtotime($date['sdate'].$t) + -4*3600);
    $date['edate'] = DateTime::createFromFormat('Y-m-d H:i:s', $date['sdate'])
                      ->modify($t)
                      ->format('Y-m-d H:i:s');
  }

  return $date;
}

/**
 * 取得有效時間文字
 *
 * @param string $s
 * @param string $action
 * @return void
 */
function get_validity_period_text($s, $action = '')
{
  global $tr;
  switch ($s) {
    case '1':
    case '2':
    case '3':
      $t = ($action == 'sql') ? $s.' days' : $s.' '.$tr['days'];//天
      break;
    case '10':
    case '20':
    case '30':
      $t = ($action == 'sql') ? ($s / 10).' month' : ($s / 10).' '.$tr['months'];//个月
      break;
    default:
      $t = ($action == 'sql') ? '100 year' : $tr['Permanent'];//'永久有效'
      break;
  }

  return $t;
}

/**
 * 取得會員身分文字
 *
 * @param string $t
 * @return string
 */
function get_register_types_text($t)
{
  global $tr;
  return ($t == 'M') ? $tr['member'] : $tr['agent'];//'会员''代理'
}

/**
 * 取得連結狀態文字
 *
 * @param string $edate
 * @return string
 */
function get_linkstatus_text($edate)
{
  global $tr;
  return (!comparison_date(get_nowdate(), $edate)) ? $tr['spread link Expired'] : $tr['Invitation code available'];//'邀请码过期''邀请码可用'
}

/**
 * 取得使用者瀏覽器
 *
 * @param string $u_agent
 * @return void
 */
function get_userbrowser($u_agent)
{
  if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent)) { 
      $bname = 'IE'; 
      $ub = "MSIE"; 
  } elseif(preg_match('/Trident/i',$u_agent)) { // this condition is for IE11
      $bname = 'IE'; 
      $ub = "rv"; 
  } elseif(preg_match('/Firefox/i',$u_agent)) { 
      $bname = 'Firefox'; 
      $ub = "Firefox"; 
  } elseif(preg_match('/Safari/i',$u_agent) && !preg_match('/Chrome/i',$u_agent)) { 
      $bname = 'Safari'; 
      $ub = "Safari"; 
  } elseif(preg_match('/Chrome/i',$u_agent) && !preg_match('/Edge/i',$u_agent)) { 
      $bname = 'Chrome'; 
      $ub = "Chrome"; 
  } elseif(preg_match('/Edge/i',$u_agent)) { 
      $bname = 'Edge'; 
      $ub = "Edge"; 
  } elseif(preg_match('/Opera/i',$u_agent)) { 
      $bname = 'Opera'; 
      $ub = "Opera"; 
  } elseif(preg_match('/Netscape/i',$u_agent)) { 
      $bname = 'Netscape'; 
      $ub = "Netscape"; 
  } else {
    $bname = 'Other'; 
    $ub = "Other"; 
  }

  return $bname;
}

/**
 * sha1 code
 *
 * @param string $code
 * @return string
 */
function get_sha1code($code) {
  $sha1 = sha1($code);

  return $sha1;
}

/**
 * base64 code
 *
 * @param string $code
 * @return string
 */
function get_base64code($code) {
  $base64 = base64_encode($code);

  return $base64;
}