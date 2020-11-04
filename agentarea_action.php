<?php
// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

require_once dirname(__FILE__) ."/lib_agentarea.php";

$post = validatePost(json_decode($_POST['data']));

switch ($_POST['action']) {
  case 'agentCommission':
    $reportdata = new lib_agentarea();
    $agentCommission = $reportdata->getAgentCommission($post['limit']);

    if ($agentCommission === false) {
      echo json_encode(['status' => 'fail', 'result' => $tr['search no data']]);
      die();
    }

    echo json_encode(['status' => 'success', 'result' => $agentCommission]);
    break;
  default:
    echo json_encode(['status' => 'fail', 'result' => $tr['bad request']]);
    break;
}

function validatePost($data)
{
  $input = [];

  foreach ($data as $k => $v) {
    $input[$k] = filter_var($v, FILTER_SANITIZE_STRING);

    if ($k == 'limit' && !in_array($input[$k], ['1', '7', '30'])) {
      return false;
    }

    if ($input[$k] == '') {
      return false;
    }
  }

  return $input;
}