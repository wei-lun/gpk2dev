<?php
// ----------------------------------------------------------------------------
// Features:    站內信件後端處理
// File Name:   stationmail_action.php
// Author:      Neil
// Related:     
// Table :      
// Log:
//
// ----------------------------------------------------------------------------


// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

require_once dirname(__FILE__) ."/stationmail_lib.php";

require_once __DIR__ . '/Utils/RabbitMQ/Publish.php';
require_once __DIR__ . '/Utils/MessageTransform.php';

// -----------------------------------------------------------------------------
// 只允許代理權限操作
if (!isset($_SESSION['member']) || $_SESSION['member']->therole == 'R') {
  echo login2return_url(2);
  die($tr['permission error']);//'不合法的帐号权限'
};

// csrf驗證
$csrftoken_ret = csrf_action_check();
if($csrftoken_ret['code'] != 1) {
  // die($csrftoken_ret['messages']);
  echo json_encode(['status' => 'fail', 'result' => $csrftoken_ret['messages']]);
  die();
}
// -----------------------------------------------------------------------------

// var_dump($_POST);

$validateresult = validatePost(json_decode($_POST['data'], true));
// var_dump($validateresult);
if (!$validateresult) {
  echo json_encode(['status' => 'fail', 'result' => $tr['Data is error'] ]);
  die();
}

switch ($_POST['action']) {
  case 'sendMail':
    if (!wordLimit($validateresult['subject'], 100)) {
      echo json_encode(['status' => 'fail', 'result' => $tr['Your word number of subject exceeds the limit']]);
      die();
    }

    if (!wordLimit($validateresult['message'], 1000)) {
      echo json_encode(['status' => 'fail', 'result' => $tr['Your word number of substance exceeds the limit']]);
      die();
    }

    $snedResult = sendMail($validateresult['subject'], $validateresult['message']);

    if (!$snedResult) {
      echo json_encode(['status' => 'fail', 'result' => $tr['send mail failed']]);
      die();
    }

    $mail_data = <<<SQL
		SELECT id, subject FROM root_stationmail WHERE msgfrom = '{$_SESSION['member']->account}' ORDER BY id DESC LIMIT 1;
SQL;

    $mail_result = runSQLall($mail_data);

    $detail = ['data_id' => $mail_result[1]->id, 'subject' => $mail_result[1]->subject];

    $mq = Publish::getInstance();
    $msg = MessageTransform::getInstance();

    $currentDate = date("Y-m-d H:i:s", strtotime('now'));
    $notifyMsg = $msg->notifyMsg('StationMail ', $_SESSION['member']->account, $currentDate, $detail);
    $notifyResult = $mq->fanoutNotify('msg_notify', $notifyMsg);

    echo json_encode(['status' => 'success', 'result' => $tr['send mail success']]);
    break;
  case 'mailDetail':
    $isRead = 'N';
  
    $codeStr = validateMailcodeMailtype($validateresult['mailcode'], $validateresult['mailtype']);

    if (!$codeStr) {
      echo json_encode(['status' => 'fail', 'result' => $tr['Wrong mail code or type']]);
      die();
    }

    if ($validateresult['source'] == 'inbox') {
      $isRead = getReadTime($codeStr['code'], $codeStr['type']);

      if (!$isRead) {
        echo json_encode(['status' => 'fail', 'result' => $tr['The date that you read is failed when you require it']]);
        die();
      }
    }

    if ($isRead == 'N' && $validateresult['source'] == 'inbox') {
      $result = updateReadTime($codeStr['code'], $codeStr['type']);

      if (!$result) {
        echo json_encode(['status' => 'fail', 'result' => $tr['The date that you update is failed when you require it']]);
        die();
      }
    } else {
      $result = getMailData($codeStr['code'], $codeStr['type'], $validateresult['source']);
    }

    $result->message = htmlspecialchars_decode($result->message);

    if ($result->template != '') {
      $template = json_decode($result->template, true);
      $result->subject = str_replace($template['code'], $template['content'], $result->subject);
      $result->message = str_replace($template['code'], $template['content'], $result->message);
    }

    echo json_encode(['status' => 'success', 'result' => $result]);
    break;
  case 'loadMore':
    if ($validateresult['source'] != 'inbox' && $validateresult['source'] != 'sent') {
      echo json_encode(['status' => 'fail', 'result' => $tr['stationmail error3']]);
      die();
    }

    $mailData = ($validateresult['source'] == 'inbox') ? getInboxMailData($validateresult['count']) : getSentMailData($validateresult['count']);

    if (!$mailData) {
      echo json_encode(['status' => 'fail', 'result' => $tr['no mail was found']]);
      die();
    }

    echo json_encode(['status' => 'success', 'result' => $mailData]);
    break;
  case 'deleteMail':
    $mailcode = str_replace('delMail=', '', $validateresult['mails']);
    $mailcode = explode("&", $mailcode);

    $delResult = deleteMail($mailcode, $validateresult['source']);

    if (!$delResult['status']) {
      echo json_encode(['status' => 'fail', 'result' => $delResult['result']]);
      die();
    }

    echo json_encode(['status' => 'success', 'result' => $tr['delete mail success']]);
    break;
  default:
    echo json_encode(['status' => 'fail', 'result' => $tr['bad request']]);
    break;
}

function validatePost($post)
{
  $input = [];

  foreach ($post as $k => $v) {
    $input[$k] = filter_var($v, FILTER_SANITIZE_STRING);

    if ($input[$k] == '') {
      return false;
    }
  }

  return $input;
}

function validateMailcodeMailtype($code, $type)
{
  $mailcode = filter_var($code, FILTER_SANITIZE_STRING);
  $mailtype = filter_var($type, FILTER_SANITIZE_STRING);

  if ($mailcode == '') {
    return false;
  }

  if ($mailtype != 'group' && $mailtype != 'persona') {
    return false;
  }

  return ['code' => $mailcode, 'type' => $mailtype];
}
?>
