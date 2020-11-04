<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 服務協助開戶
// Author:		Neil
// Related:
// Log:
// ----------------------------------------------------------------------------

require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

// 註冊專用函式庫
require_once dirname(__FILE__) ."/register_lib.php";

require_once dirname(__FILE__) ."/gcash_lib.php";

require_once dirname(__FILE__) ."/lib_agents_setting.php";
// var_dump($_POST);
// die();

// 只允許代理權限操作
if (!isset($_SESSION['member']) || $_SESSION['member']->therole != 'A') {
  echo login2return_url(2);
  die('不合法的帳號權限');
};

$csrftoken_ret = csrf_action_check();

if($csrftoken_ret['code'] != 1) {
  die($csrftoken_ret['messages']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $agentData = getMemberDataByAccount($_SESSION['member']->account);

  if (!$agentData['status']) {
    echo json_encode(['status' => 'fail', 'result' => $agentData['result']]);
    die();
  }

  $gcashCashierData = getMemberDataByAccount($gcash_cashier_account);

  if (!$gcashCashierData['status']) {
    echo json_encode(['status' => 'fail', 'result' => $agentData['result']]);
    die();
  }

  $ipCheck = ip_countregister_check();

  if ($ipCheck['code'] != 1) {
    if ($ipCheck['code'] == 0) {
      echo json_encode(['status' => 'fail', 'result' => $ipCheck['messages']]);
      die();
    }

    echo json_encode(['status' => 'fail', 'result' => 'IP註冊次數超過限制，請稍後再試']);
    die();
  }

  $fingerprinterCheck = fingerprinter_check();

  if ($fingerprinterCheck['code'] != 1) {
    if ($fingerprinterCheck['code'] == 0) {
      echo json_encode(['status' => 'fail', 'result' => $fingerprinterCheck['messages']]);
      die();
    }

    echo json_encode(['status' => 'fail', 'result' => '註冊次數超過限制，請稍後再試']);
    die();
  }

  $now = gmdate('Y-m-d H:i:s',time() + '-4' * 3600);
  $becomeagentDateLimit = date('Y-m-d H:i:s', strtotime($agentData['result']->becomeagentdate.' +'.$protalsetting['becomeagent_datelimit'].' minute'));

  if (strtotime($becomeagentDateLimit) > strtotime($now)) {
    echo json_encode(['status' => 'fail', 'result' => '於 '.$becomeagentDateLimit.' 後才可註冊下線']);
    die();
  }

  $validate_r = validatedata($_POST['data'], $agentData['result']->gcash_balance);

  switch ($_POST['action']) {
    case 'add':
      if (!$validate_r['status']) {
        echo json_encode(['status' => 'fail', 'result' => $validate_r['result']]);
        die();
      }

      $registerData = [
        'memberdata' => $validate_r['result'],
        'parent_id' => $_SESSION['member']->id,
        'withdrawal_password' => sha1($system_config['withdrawal_default_password']),
        'transaction_money' => $system_config['agency_registration_gcash'],
        'fingertracker_remote_addr' => $_SESSION['fingertracker_remote_addr'],
        'fingertracker' => $_SESSION['fingertracker'],
        'recommendedcode' => get_recommendedcode($validate_r['result']['account']),

        'member_id' => $_SESSION['member']->id,
        'operator' => $_SESSION['member']->account,
        'transaction_category_index' => 'cashwithdrawal',
        'summary' => $transaction_category['cashwithdrawal'],
        'source_transferaccount' => $agentData['result'],
        'destination_transferaccount' => $gcashCashierData['result'],
        'realcash' => 1,
        'system_note' => '協助開戶代理帳號加盟金扣款',
        'transaction_id' => get_transaction_id($_SESSION['member']->account, 'w')
      ];

      $addResult = registerMember($registerData);

      if (!$addResult['status']) {
        echo json_encode(['status' => 'fail', 'result' => '帳號新增失敗']);
        die();
      }

      echo json_encode(['status' => 'success', 'result' => '帳號新增成功', 'acc' => base64_encode($validate_r['result']['account'])]);
      break;
    default:
      echo json_encode(['status' => 'fail', 'result' => '錯誤的請求']);
      break;
  }
}

/**
 * 註冊資料驗證
 *
 * @return array
 */
function validatedata($post, $agentGcashBalance)
{
  global $protalsetting;

  $acc = memberaccount_check($post['account']);

  if ($acc['code'] != '1') {
    return ['status' => false, 'result' => $acc['messages']];
  }

  $input['account'] = $acc['memberaccount_input'];

  $pw = password_check($post['password'], $post['checkPassword']);

  if ($pw['code'] != '1') {
    return ['status' => false, 'result' => $pw['messages']];
  }

  $input['password'] = sha1($pw['password1_input']);

  $registerType = filter_var($post['type'], FILTER_SANITIZE_STRING);

  if (empty($registerType) || ($registerType != 'A' && $registerType != 'M')) {
    return ['status' => false, 'result' => '无效的开户类型'];
  }

  if ($registerType == 'A' && $protalsetting['agency_registration_gcash'] > $agentGcashBalance) {
    return array('status' => false, 'result' => '帐户余额不足，无法建立代理帐号');
  }

  if (($protalsetting['member_register_switch'] == 'off' && $registerType == 'M') ||
      $protalsetting['agent_register_switch'] == 'off' && $registerType == 'A') {
    return ['status' => false, 'result' => '开户类型关闭中，请重新选择'];
  }

  $input['registertype'] = $registerType;

  $captcha = captcha_check($post['captcha']);

  if ($captcha['code'] != '1') {
    return ['status' => false, 'result' => $captcha['messages']];
  }

  if ($registerType == 'A') {
    $feedbackRange = getFeedbackRange();

    $preferential = filter_var($post['preferential'], FILTER_SANITIZE_STRING);

    if (!in_array($preferential, $feedbackRange['preferential'])) {
      return ['status' => false, 'result' => '錯誤的反水比'];
    }

    $input['preferential'] = ($preferential / 100);

    $dividend = filter_var($post['dividend'], FILTER_SANITIZE_STRING);

    if (!in_array($dividend, $feedbackRange['dividend'])) {
      return ['status' => false, 'result' => '錯誤的佣金比'];
    }

    $input['dividend'] = ($dividend / 100);
  }

  return ['status' => true, 'result' => $input];
}

function getMemberDataByAccount($acc, $tzonename = 'posix/Etc/GMT+4')
{
  $sql = <<<SQL
  SELECT *,
        to_char((becomeagentdate AT TIME ZONE '{$tzonename}'),'YYYY-MM-DD HH24:MI:SS') AS becomeagentdate
  FROM root_member
  JOIN root_member_wallets ON root_member.id = root_member_wallets.id
  WHERE root_member.account = '{$acc}'
  AND root_member.status = '1';
SQL;

  $result = runSQLall($sql);

  if (empty($result[0])) {
    return ['status' => false, 'result' => '会员资料查询错误'];
  }

  return ['status' => true, 'result' => $result[1]];
}

function getFeedbackRange()
{
  $feedbackinfoHelper = new FeedbackInfoHelper(['member_id' => $_SESSION['member']->id]);
  $feedbackinfoHelper->initFeedbackInfo();
  $feedbackinfoHelper->save();

  $preferentialUpperLowerLimit = $feedbackinfoHelper->getNewChildAllocationRange('preferential');
  $dividendUpperLowerLimit = $feedbackinfoHelper->getNewChildAllocationRange('dividend');

  $preferentialRange = getRange(float_to_percent($preferentialUpperLowerLimit['max']), float_to_percent($preferentialUpperLowerLimit['min']));
  $dividendRange = getRange(float_to_percent($dividendUpperLowerLimit['max']), float_to_percent($dividendUpperLowerLimit['min']));

  return ['dividend' => $dividendRange, 'preferential' => $preferentialRange];
}

function getRange($max, $min)
{
  if ($max == 0 && $min == 0) {
    $range[] = 0;
  } else {
    $range = range($min, $max);
  }

  foreach ($range as $v) {
    $result[] = $v;
  }

  return $result;
}

/**
 * 新增會員相關動作
 *
 * @param array $register_data
 * @return array
 */
function registerMember($data)
{
  global $member_grade_config_detail;
  global $config;

  if ($data['memberdata']['registertype'] == 'A') {
    $sql = <<<SQL
    INSERT INTO "root_member"
    (
      account, passwd, therole, parent_id, withdrawalspassword,
      enrollmentdate, registerfingerprinting, registerip, recommendedcode,
      becomeagentdate
    ) VALUES (
      '{$data['memberdata']['account']}', '{$data['memberdata']['password']}', '{$data['memberdata']['registertype']}', '{$data['parent_id']}', '{$data['withdrawal_password']}',
      now(), '{$data['fingertracker']}', '{$data['fingertracker_remote_addr']}', '{$data['recommendedcode']}',
      now()
    );
SQL;

    $sql .= get_gcash_transfer_sql($data);

    $notes_text = '協助開戶代理帳號';

    $sql .= <<<SQL
    INSERT INTO "root_agent_review"
    (
      account, changetime, status, applicationip, applicationtime,
      processingtime, amount, fingerprinting, notes
    ) VALUES (
      '{$data['memberdata']['account']}', now(), '1', '{$data['fingertracker_remote_addr']}', now(),
      now(), '{$data['transaction_money']}', '{$data['fingertracker']}', '{$notes_text}'
    );
SQL;
  } else {
    $sql = <<<SQL
    INSERT INTO "root_member"
    (
      account, passwd, therole, parent_id, withdrawalspassword,
      enrollmentdate, registerfingerprinting, registerip, recommendedcode
    ) VALUES (
      '{$data['memberdata']['account']}', '{$data['memberdata']['password']}', '{$data['memberdata']['registertype']}', '{$data['parent_id']}', '{$data['withdrawal_password']}',
      now(), '{$data['fingertracker']}', '{$data['fingertracker_remote_addr']}', '{$data['recommendedcode']}'
    );
SQL;
  }

  $sql = 'BEGIN;'.$sql.'COMMIT;';

  $result = runSQLtransactions($sql);

  if (!$result) {
    return array('status' => false, 'result' => '帐号新增失败');
  }

  // 建立錢包
  $createWalletResult = create_member_wallets_by_account($data['memberdata']['account']);

  if ($createWalletResult['code'] != 1) {
    return ['status' => false, 'result' => '钱包建立失败'];
  }

  $activityRegisterPreferential = json_decode($member_grade_config_detail->activity_register_preferential, true);

  // 註冊送彩金有開啟才動作
  if ($activityRegisterPreferential['activity_register_preferential_enable'] == 1) {
    $gtoken_transfer_error = promotion_register_sendbouns($data['memberdata']['account'], $activityRegisterPreferential['activity_register_preferential_amount'], $activityRegisterPreferential['activity_register_preferential_audited']);

    if ($gtoken_transfer_error['code'] != 1) {
      return ['status' => false, 'result' => $gtoken_transfer_error['message']];
    }
  }

  // 如果是協助開戶，初始化新代理商的分佣比
  if ($data['memberdata']['registertype'] == 'A') {
    $member_id = runSQLall_prepared("SELECT id From root_member WHERE account = :account", ['account' => $data['memberdata']['account']])[0]->id;
    $feedbackhelper = new FeedbackInfoHelper(compact('member_id'));
    $feedbackhelper->initFeedbackInfo();
    $feedbackhelper->setAllocable('preferential', $data['memberdata']['preferential']);
    $feedbackhelper->setAllocable('dividend', $data['memberdata']['dividend']);
    $feedbackhelper->save();
  }

  // $urlcode_base64 = get_member_login_urlcode($register_data['memberdata']['account']);
  // $login_url = 'https://'.$config['website_domainname'].'/app.php?m='.$urlcode_base64;
  // $msg = "帳號 ".$register_data['memberdata']['account']." 已成功注册，立即点击专属登入网址来登入您的帐号。\n".'<a href="' . $login_url . '">'.$login_url;

  $msg = "帳號 ".$data['memberdata']['account']." 已成功注册。";

  return ['status' => true, 'result' => $msg];
}
