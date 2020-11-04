<?php
// ----------------------------------------------------------------------------
// Features:	前台 - 註冊申請代理商。
// File Name:	register_agent.php
// Author:
// Related:  register_agent.php
// Log:
// ----------------------------------------------------------------------------

require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

// gcash lib 現金轉帳函式庫
require_once dirname(__FILE__) ."/gcash_lib.php";

require_once __DIR__ . '/Utils/RabbitMQ/Publish.php';
require_once __DIR__ . '/Utils/MessageTransform.php';

// 測試區
// echo '<pre>', var_dump( getMemberDataByAccount($gcash_cashier_account) ), '</pre>'; exit();


if ( !isset($_SESSION['member']) || ($_SESSION['member']->therole != 'M') ) {
    die( json_encode(['status' => 'fail', 'result' => $tr['Invalid account permission or You are already an agent']]) ); //'不合法的帐号权限或您已经是代理'
}

$agent_review_isopen = (isset($protalsetting['agent_review_isopen'])) ? $protalsetting['agent_review_isopen'] : 'on';
if ($agent_review_isopen != 'on') {
    die( json_encode(['status' => 'fail', 'result' => $tr['register agent closed'].$tr['If you have any questions, please contact us.']]) ); //'申请成为代理商目前关闭中，如有疑问请洽客服。'
}

$csrftoken_ret = csrf_action_check();
if($csrftoken_ret['code'] != 1) {
  die($csrftoken_ret['messages']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $mq = Publish::getInstance();
  $msgTransform = MessageTransform::getInstance();

  $post = validatePost(json_decode($_POST['data']));

  if (!$post['status']) {
    echo json_encode(['status' => 'fail', 'result' => $post['result']]);
    die();
  }

  if ($post == '') {
    die();
  }

  switch ($_POST['action']) {
    case 'editMemberData':
      $sqlSet = [];

      foreach ($post['result'] as $key => $val) {
        $sqlSet[] = "{$key} = '{$val}'";
      }

      $updateResult = updateMemberData(implode(',', $sqlSet));

      if (!$updateResult) {
        $msg = $tr['profile update failed']; // '个人信息更新失败'
        $sub_service = 'information';
        memberlogtodb($_SESSION['member']->account,'accounting','error',"$msg",$_SESSION['member']->account,"$msg",'f',$sub_service);
        die( json_encode(['status' => 'fail', 'result' => $tr['profile update failed']]) ); // '个人信息更新失败'
      }

      $msg = $tr['profile update success'];//'个人信息更新成功'
      $sub_service='information';
      memberlogtodb($_SESSION['member']->account,'accounting','info',$msg,$_SESSION['member']->account,"$msg",'f',$sub_service);

      echo json_encode(['status' => 'success', 'result' => $tr['profile update success']]);//'个人信息更新成功'
      break;
    case 'editBankData':
      $sqlSet = [];

      foreach ($post['result'] as $k => $v) {
        $sqlSet[] = $k." = '".$v."'";
      }

      $updateResult = updateMemberData(implode(",", $sqlSet));

      if (!$updateResult) {
        $msg = $tr['bank setting update failed'];//'银行卡更新失败'
        $sub_service='information';
        memberlogtodb($_SESSION['member']->account,'accounting','error',"$msg",$_SESSION['member']->account,"$msg",'f',$sub_service);

        echo json_encode(['status' => 'fail', 'result' => $tr['bank setting update failed']]);//'银行卡更新失败'
        die();
      }

      $msg = $tr['bank setting update success'];//'银行卡更新成功'
      $sub_service='information';
      memberlogtodb($_SESSION['member']->account,'accounting','info',$msg,$_SESSION['member']->account,"$msg",'f',$sub_service);

      echo json_encode(['status' => 'success', 'result' => $tr['bank setting update success']]);//'银行卡更新成功'
      break;
    case 'registerAgent':
      $memberData = getMemberDataByAccount($_SESSION['member']->account); // 查詢會員資料 與會員錢包資料
      $cashierAccountData = getMemberDataByAccount($gcash_cashier_account); // $gcash_cashier_account為全域變數

      if (!$memberData) { // '会员资料查询错误'
        $msg = $tr['member search error'];
        $sub_service='partner';
        memberlogtodb($_SESSION['member']->account,'member','error',"$msg",$_SESSION['member']->account,"$msg",'f',$sub_service);
        die( json_encode(['status' => 'fail', 'result' => $tr['member search error']]) );
      }

      if (!$cashierAccountData) { // '出纳帐户查询错误'
        $msg = $tr['Cashier account search error'];
        $sub_service='partner';
        memberlogtodb($_SESSION['member']->account,'member','error',"$msg",$_SESSION['member']->account,"$msg",'f',$sub_service);
        die( json_encode(['status' => 'fail', 'result' => $tr['system error']]) );
      }

      if ( ($protalsetting['national_agent_isopen'] == 'off') && !checkMemberDataIsNull($memberData) ) {
        $msg = $tr['confirm all information again'];//'请确认所有必填个人信息及银行卡资料皆已填妥'
        $sub_service='partner';
        memberlogtodb($_SESSION['member']->account,'member','error',"$msg",$_SESSION['member']->account,"$msg",'f',$sub_service);

        echo json_encode(['status' => 'fail', 'result' => $tr['confirm all information again']]);//'请确认所有必填个人信息及银行卡资料皆已填妥'
        die();
      }

      if($memberData->passwd != $post['result']['password']) {
        $msg = $tr['wrong password'];//'密码输入错误'
        $sub_service='partner';
        memberlogtodb($_SESSION['member']->account,'member','error',"$msg",$_SESSION['member']->account,"$msg",'f',$sub_service);

        echo json_encode(['status' => 'fail', 'result' => $tr['wrong password']]);//'密码输入错误'
        die();
      }

      if($memberData->gcash_balance < $system_config['agency_registration_gcash']) {
        $msg = $tr['cash balance is not enough'];//'现金钱包余额不足'
        $sub_service='partner';
        memberlogtodb($_SESSION['member']->account,'member','error',"$msg",$_SESSION['member']->account,"$msg",'f',$sub_service);

        echo json_encode(['status' => 'fail', 'result' => $tr['cash balance is not enough']]);//'现金钱包余额不足'
        die();
      }

      $transferData = [
        'member_id' => $_SESSION['member']->id,
        'operator' => $_SESSION['member']->account,
        'transaction_money' => $system_config['agency_registration_gcash'],
        'transaction_category_index' => 'cashwithdrawal',
        'summary' => $tr['Apply for an agent'],//'申请代理'
        'system_note' => $tr['agent Withholding amount'],//'申请成为代理商预扣金额'
        'source_transferaccount' => (object)['id' => $memberData->id, 'account' => $memberData->account],
        'destination_transferaccount' => (object)['id' => $cashierAccountData->id, 'account' => $cashierAccountData->account],
        'fingertracker_remote_addr' => $_SESSION['fingertracker_remote_addr'],
        'fingertracker' => $_SESSION['fingertracker'],
        'realcash' => '0',
        'transaction_id' => get_transaction_id($_SESSION['member']->account, 'w')
      ];

      $becomeAgentSql = submitReviewSql($transferData, $memberData);
      $becomeAgentSql .= get_gcash_transfer_sql($transferData);

      $becomeAgentSql = 'BEGIN;'.$becomeAgentSql.'COMMIT;';
      $becomeAgentResult = runSQLtransactions($becomeAgentSql);

      if (!$becomeAgentResult) {
        // 申請失敗
        $msg = $tr['Agent application failed'];//'代理审核申请失败'
        $sub_service='partner';
        memberlogtodb($_SESSION['member']->account,'member','error',"$msg",$_SESSION['member']->account,"$msg",'f',$sub_service);

        echo json_encode(['status' => 'fail', 'result' => $tr['Agent application failed']]);//'代理审核申请失败'
        die();
      }

      if ($agent_review_automatic_switch == 'automatic') {
        $agentReviewData = getAgentReviewData($memberData->account);

        if (!$agentReviewData ) {
          echo json_encode(['status' => 'fail', 'result' => $tr['Agent application search failed']]);//'审查资料查询错误'
          die();
        }

        $automaticBecomeAgentSql = getUpdateAgentReviewDataSql($agentReviewData->id);
        $automaticBecomeAgentSql .= getUpdateMemberTheroleSql($memberData->id);
        $automaticBecomeAgentSql .= getInsertGcashPassbookSql($agentReviewData->id, $memberData, $cashierAccountData);

        $automaticBecomeAgentSql = 'BEGIN;'.$automaticBecomeAgentSql.'COMMIT;';
        $automaticBecomeAgentResult = runSQLtransactions($automaticBecomeAgentSql);

        if (!$automaticBecomeAgentResult) {
          // 自動審核申請失敗
          $msg = $tr['application data atatus'].'：'.$tr['member'].$memberData->account.$tr['change failed'];//审核状态会员变更失败
          $sub_service='partner';
          memberlogtodb($_SESSION['member']->account,'member','error',"$msg",$_SESSION['member']->account,"$msg",'f',$sub_service);

          echo json_encode(['status' => 'fail', 'result' => $tr['automatic application failed']]);//'自动审核申请失败'
          die();
        }

        // 已成為代理
        $msg = $tr['application data atatus'].'：'.$tr['member'].$memberData->account.$tr['Account change to agent update success'];//'帐号变更为代理商更新成功'
        $sub_service='partner';
        memberlogtodb($_SESSION['member']->account,'member','info',$msg,$_SESSION['member']->account,"$msg",'f',$sub_service);

        //更新隱藏現金功能
        update_gcash_log_exist($_SESSION['member']->account);

        echo json_encode(['status' => 'success', 'result' => $tr['already become agent'], 'isAutomatic' => 'Y']);//'已成为代理'
      } else {
        // 送出審核成功
        $msg = $tr['Apply for an agent success,Pending review'];//'代理申请成功，状态：待审核'
        $sub_service='partner';
        memberlogtodb($_SESSION['member']->account,'member','info',$msg,$_SESSION['member']->account,"$msg",'f',$sub_service);
        update_gcash_log_exist($_SESSION['member']->account);
        // root_agent_review

        $review_data = <<<SQL
		    SELECT id FROM root_agent_review WHERE account = '{$_SESSION['member']->account}' ORDER BY id DESC LIMIT 1;
SQL;

        $review_result = runSQLall($review_data);

        $currentDate = date("Y-m-d H:i:s", strtotime('now'));
        $notifyMsg = $msgTransform->notifyMsg('AgentReview', $_SESSION['member']->account, $currentDate, ['data_id' => $review_result[1]->id]);
        $notifyResult = $mq->fanoutNotify('msg_notify', $notifyMsg);

        echo json_encode(['status' => 'success', 'result' => $tr['Successfully sent the review'], 'isAutomatic' => 'N']);//'送出审核成功'
      }
      break;
    default:
      echo json_encode(['status' => 'fail', 'result' => $tr['bad request']]);//'错误的请求'
      break;
  }
}

function validatePost($post)
{
  global $tr;

  $input = [];

  $memberData = getMemberDataById($_SESSION['member']->id);
  $dataSetting = getRegisterDataSetting($memberData);

  foreach ($post as $k => $v) {
    $value = filter_var($v, FILTER_SANITIZE_STRING);

    if ($value == '' && $dataSetting[$k]['isshow'] == 'on' && $dataSetting[$k]['ismust'] == 'on') {
      // return false;
      return ['status' => false, 'result' => $tr['Invalid data,please try again later']];//'资料不合法，请确认资料正确性后再行尝试'
    }

    if ($value != '') {
      if ($k == 'agreementAgree' && $value == 'N') {
        return ['status' => false, 'result' => $tr['Please read and agree to the cooperation agreement']];//'请详阅并同意合作协议'
      }

      if ($k == 'sex') {
        if ($dataSetting[$k]['isshow'] == 'on' && $dataSetting[$k]['ismust'] == 'on' && $value != '0' && $value != '1') {
          return ['status' => false, 'result' => $tr['Please choose the correct gender']];//'请选择正确性别'
        }
      }

      if ($k == 'realname' && (mb_strlen($value) > 12)) {
        return ['status' => false, 'result' => $tr['name length should not exceed 12 words']];//'真实姓名长度请勿超过12个字'
      }

      // if ($k == 'mobilenumber') {
      //   $regexp = '^13[0-9]{1}[0-9]{8}|^15[0-9]{1}[0-9]{8}|^18[8-9]{1}[0-9]{8}';
      //   $value = filter_var($value, FILTER_VALIDATE_REGEXP, array("options"=>array("regexp"=>"/$regexp/")));

      //   if ($value === false) {
      //     return ['status' => false, 'result' => $tr['invalid phone number']];
      //   }
      // }

      if ($k == 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
        return ['status' => false, 'result' => $tr['email invalid']];
      }

      // if ($k == 'qq') {
      //   $regexp = '[0-9]{5,9}';
      //   $value = filter_var($value, FILTER_VALIDATE_REGEXP, array("options"=>array("regexp"=>"/$regexp/")));

      //   if ($value === false) {
      //     return ['status' => false, 'result' => $tr['QQ format is incorrect']];//'QQ格式不正确'
      //   }
      // }

      if (isset($dataSetting[$k]['isunique']) && $dataSetting[$k]['isunique'] == 'on' && checkIsUnique($k, $value)) {
        return ['status' => false, 'result' => $tr['Already has the same'].$dataSetting[$k]['col_name']];//'已存在相同'
      }

      $input[$k] = ($k != 'birthday') ? $value : date("Ymd",strtotime($value));
    }
  }

  // return $input;
  return ['status' => true, 'result' => $input];
}

function getMemberDataById($id)
{
  $sql = <<<SQL
  SELECT *
  FROM root_member
  JOIN root_member_wallets
  ON root_member.id = root_member_wallets.id
  WHERE root_member.id = '{$id}'
  AND root_member.status = '1';
SQL;

  $result = runSQLALL($sql);

  if (empty($result[0])) {
    return false;
  }

  return $result[1];
}

//查詢會員資料 與會員錢包資料
function getMemberDataByAccount($acc)
{
    $sql = <<<SQL
        SELECT *
        FROM "root_member"
        JOIN "root_member_wallets"
            ON "root_member"."id" = "root_member_wallets"."id"
        WHERE ("root_member"."account" = '{$acc}')
            AND ("root_member"."status" = '1');
    SQL;
    $result = runSQLALL($sql);
    return ( ( empty($result[0]) ) ? false : $result[1] );
}

function submitReviewSql($transferData, $memberData)
{
  $sql = <<<SQL
  INSERT INTO root_agent_review
  (
    account, changetime, status, applicationip, processingaccount,
    processingtime, applicationtime, notes, amount, commissioned,
    mobilenumber, wechat, email, qq, realname,
    fingerprinting, transaction_id
  ) VALUES (
    '{$memberData->account}', now(), '2', '{$transferData['fingertracker_remote_addr']}', NULL,
    NULL, now(), NULL, '{$transferData['transaction_money']}', NULL,
    '{$memberData->mobilenumber}', '{$memberData->wechat}', '{$memberData->email}', '{$memberData->qq}', '{$memberData->realname}',
    '{$transferData['fingertracker']}','{$transferData['transaction_id']}'
  );
SQL;

  return $sql;
}

function getAgentReviewData($acc)
{
  $sql = <<<SQL
  SELECT *
  FROM root_agent_review
  WHERE status = '2'
  AND account = '{$acc}'
  ORDER BY id DESC
  LIMIT 1;
SQL;

  $result = runSQLall($sql);

  if (empty($result[0])) {
    return false;
  }

  return $result[1];
}

function getUpdateAgentReviewDataSql($reviewId)
{
  global $automatic_review_processing_account;

  $sql = <<<SQL
  UPDATE root_agent_review
  SET status = '1',
      processingaccount = '{$automatic_review_processing_account}' ,
      notes = '代理申请自动审核通过',
      processingtime = now()
  WHERE id = '{$reviewId}';
SQL;

  return $sql;
}

function getUpdateMemberTheroleSql($id)
{
  $sql = <<<SQL
  UPDATE root_member
  SET therole = 'A',
      becomeagentdate = now()
  WHERE id = '{$id}';
SQL;

  return $sql;
}

function getInsertGcashPassbookSql($reviewId, $memberData, $cashierAccountData)
{
  global $config;
  global $transaction_category;

  $note = "(系统自动审核会员为代理商)。";
  $sql = <<<SQL
  INSERT INTO root_member_gcashpassbook
  (
    transaction_time, system_note, member_id, currency, summary,
    source_transferaccount, destination_transferaccount, balance, transaction_category
  ) VALUES (
    now(), '{$note}', '{$reviewId}', '{$config['currency_sign']}', '{$transaction_category['cashwithdrawal']}',
    '{$cashierAccountData->account}', '{$memberData->account}', (select gcash_balance from root_member_wallets where id = '{$cashierAccountData->id}'), 'cashwithdrawal'
  );
SQL;

  //代理商交易訊息(會員)
  $note = "(会员成功审核成为代理商)。";
  $sql .= <<<SQL
  INSERT INTO root_member_gcashpassbook
  (
    transaction_time, system_note, member_id, currency, summary,
    source_transferaccount, destination_transferaccount, balance, transaction_category
  ) VALUES (
    now(), '{$note}', '{$reviewId}', '{$config['currency_sign']}', '{$transaction_category['cashwithdrawal']}',
    '{$memberData->account}', '{$cashierAccountData->account}', (select gcash_balance from root_member_wallets where id = '{$memberData->id}'), 'cashwithdrawal'
  );
SQL;

  return $sql;
}

function getRegisterDataSetting($memberData)
{
  global $tr;
  global $protalsetting;

  $arr = [
    'realname' => [
      'col_name' => $tr['real name'],
      'isshow' => $protalsetting['agent_register_name_show'],
      'ismust' => $protalsetting['agent_register_name_must'],
      'value' => $memberData->realname
    ],
    'mobilenumber' => [
      'col_name' => $tr['cellphone'],
      'isshow' => $protalsetting['agent_register_mobile_show'],
      'ismust' => $protalsetting['agent_register_mobile_must'],
      'isunique' => $protalsetting['agent_register_mobile_unique'],
      'value' => $memberData->mobilenumber
    ],
    'email' => [
      'col_name' => $tr['email'],
      'isshow' => $protalsetting['agent_register_mail_show'],
      'ismust' => $protalsetting['agent_register_mail_must'],
      'isunique' => $protalsetting['agent_register_mail_unique'],
      'value' => $memberData->email
    ],
    'birthday' => [
      'col_name' => $tr['brithday'],
      'isshow' => $protalsetting['agent_register_birthday_show'],
      'ismust' => $protalsetting['agent_register_birthday_must'],
      'value' => $memberData->birthday
    ],
    'sex' => [
      'col_name' => $tr['gender'],
      'isshow' => $protalsetting['agent_register_sex_show'],
      'ismust' => $protalsetting['agent_register_sex_must'],
      'value' => $memberData->sex
    ],
    'wechat' => [
      'col_name' => $tr['wechat ID'],
      'isshow' => $protalsetting['agent_register_wechat_show'],
      'ismust' => $protalsetting['agent_register_wechat_must'],
      'isunique' => $protalsetting['agent_register_qq_unique'],
      'value' => $memberData->wechat
    ],
    'qq' => [
      'col_name' => $tr['QQ number'],
      'isshow' => $protalsetting['agent_register_qq_show'],
      'ismust' => $protalsetting['agent_register_qq_must'],
      'isunique' => $protalsetting['agent_register_wechat_unique'],
      'value' => $memberData->qq
    ],
    'bankname' => [
      'col_name' => $tr['bank name'],
      'isshow'=>$protalsetting['agent_bank_information_show'],
      'ismust'=>$protalsetting['agent_bank_information_must'],
      'value' => $memberData->bankname
    ],
    'bankaccount' => [
      'col_name' => $tr['bank account'],
      'isshow'=>$protalsetting['agent_bank_information_show'],
      'ismust'=>$protalsetting['agent_bank_information_must'],
      'value' => $memberData->bankaccount
    ],
    'bankprovince' => [
      'col_name' => $tr['bank province'],
      'isshow'=>$protalsetting['agent_bank_information_show'],
      'ismust'=>$protalsetting['agent_bank_information_must'],
      'value' => $memberData->bankprovince
    ],
    'bankcounty' => [
      'col_name' => $tr['bank country'],
      'isshow'=>$protalsetting['agent_bank_information_show'],
      'ismust'=>$protalsetting['agent_bank_information_must'],
      'value' => $memberData->bankcounty
    ]
  ];

  return $arr;
}

function getRegisterDataBankDataColSetting($memberData)
{
  global $protalsetting;

  $arr = [
    'isshow'=>$protalsetting['agent_bank_information_show'],
    'ismust'=>$protalsetting['agent_bank_information_must'],
    'bankname' => $memberData->bankname,
    'bankaccount' => $memberData->bankaccount,
    'bankprovince' => $memberData->bankprovince,
    'bankcounty' => $memberData->bankcounty
  ];

  return $arr;
}

function checkMemberDataIsNull($memberData)
{
  $data = getRegisterDataSetting($memberData);

  foreach ($data as $k => $v) {
    if ($v['isshow'] == 'on' && $v['ismust'] == 'on' && $v['value'] === '') {
      return false;
    }
  }

  return true;
}

// 判斷是否有相同的值
function checkIsUnique($col, $colValue)
{
    $sql = <<<SQL
        SELECT *
        FROM root_member
        WHERE {$col} = '{$colValue}';
    SQL;
    return runSQL($sql);
}

function updateMemberData($set)
{
    $sql = <<<SQL
        UPDATE "root_member"
        SET {$set}
        WHERE "id" = '{$_SESSION["member"]->id}';
    SQL;
    return runSQL($sql);
}
