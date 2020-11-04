<?php
// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

// var_dump($_POST);

if (!isset($_SESSION['member']) || $_SESSION['member']->therole == 'T') {
  echo login2return_url(2);
  die('不合法的帳號權限');
};

$csrftoken_ret = csrf_action_check();

if($csrftoken_ret['code'] != 1) {
  die($csrftoken_ret['messages']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $post = validatedata($_POST);

  if (!$post) {
    echo json_encode(['status' => 'fail', 'result' => '资料不合法，请确认资料正确性后再行尝试']);
    die();
  }

  switch ($post['action']) {
    case 'detail':
      $announcement = getAllAnnouncementById($post['id']);

      if (!$announcement) {
        echo json_encode(['status' => 'fail', 'result' => '查无公告资料']);
        die();
      }

      $output = [
        'id' => $announcement->id,
        'title' => $announcement->title,
        'content' => htmlspecialchars_decode($announcement->content),
        'effecttime' => $announcement->effecttime
      ];
    
      echo json_encode(['status' => 'success', 'result' => $output]);
      break;
    default:
      echo json_encode(['status' => 'success', 'result' => '错误的请求']);
      break;
  }
}

function validatedata($post)
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

function getAllAnnouncementById($id)
{
  $tzname = 'posix/Etc/GMT+4';

  $sql = <<<SQL
  SELECT *,
        to_char((effecttime AT TIME ZONE '{$tzname}'),'YYYY-MM-DD HH24:MI:SS') AS effecttime,
        to_char((endtime AT TIME ZONE '{$tzname}'),'YYYY-MM-DD HH24:MI:SS') AS endtime
  FROM root_announcement 
  WHERE status = '1' 
  AND now() < endtime 
  AND effecttime < now() 
  AND id = '{$id}';
SQL;

  $result = runSQLall($sql);

  if (empty($result[0])) {
    return false;
  }

  return $result[1];
}