<?php
// 主機及資料庫設定
require_once dirname(__FILE__) ."./../../config.php";
// 支援多國語系
require_once dirname(__FILE__) ."./../../i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."./../../lib.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $post = validatePost($_POST);

  if (!$post) {
    echo json_encode(['status' => 'fail', 'result' => '资料不合法，请确认资料正确性后再行尝试']);
    die();
  }

  switch ($post['action']) {
    case 'detail':
      $announcementData = getAnnouncementDataById($post['id']);

      if (!$announcementData) {
        echo json_encode(['status' => 'fail', 'result' => '公告查询失败']);
        die();
      }
    
      echo json_encode(['status' => 'success', 'result' => $announcementData]);
      break;
    default:
      echo json_encode(['status' => 'fail', 'result' => '错误的请求']);
      die();
      break;
  }
}

function validatePost($post)
{
  $input = [];

  foreach ($post as $k => $v) {
    $data = ($k == 'id') ? base64_decode($v) : $v;

    $input[$k] = filter_var($data, FILTER_SANITIZE_STRING);

    if ($input[$k] == '') {
      return false;
    }
  }

  return $input;
}

function getAnnouncementDataById($id)
{
  $sql = <<<SQL
  SELECT * 
  FROM root_announcement 
  WHERE status = '1' 
  AND now() < endtime 
  AND effecttime < now() 
  AND id='{$id}' 
  ORDER BY id 
  LIMIT 1;
SQL;

  $result = runSQLall($sql);

  if (empty($result[0])) {
    return false;
  }

  return [
    'title' => $result[1]->title,
    'content' => htmlspecialchars_decode($result[1]->content),
    'effecttime' => date("Y-m-d", strtotime($result[1]->effecttime))
  ];
}