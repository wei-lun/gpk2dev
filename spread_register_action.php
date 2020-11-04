<?php
// ----------------------------------------------------------------------------
// Features:  前端 -- 服務推廣註冊
// File Name: spread_register_action.php
// Author:    Neil
// Related:   
// DB Table:  
// Log:
// ----------------------------------------------------------------------------

// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

require_once dirname(__FILE__) ."/spread_register_lib.php";

// var_dump($_SESSION);
// var_dump($_POST);
// echo $_POST;

// 只允許代理權限操作
if (!isset($_SESSION['member']) || $_SESSION['member']->therole != 'A') {
  echo login2return_url(2);
  die($tr['permission error']);//'不合法的帐号权限'
};

// csrf驗證
$csrftoken_ret = csrf_action_check();
if($csrftoken_ret['code'] != 1) {
  die($csrftoken_ret['messages']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $post = (object)validatePost($_POST);

  if (!$post->status) {
    echo json_encode(['status' => 'fail', 'result' => $post->result]);
    die();
  }

  switch ($post->result->action) {
    case 'typeData':
      $linkData = getSpreadLinkByAccountType($_SESSION['member']->account, $post->result->type);

      if (!$linkData) {
        echo json_encode(['status' => 'fail', 'result' => $tr['spread link data not found']]);//'查无邀请码资料'
        die();
      }

      foreach ($linkData as $v) {
        $v->visits_count = ($v->visits_count == '') ? '0' : $v->visits_count;
        $v->register_count = ($v->register_count == '') ? '0' : $v->register_count;

        $v->end_date = (comparison_date(getEDTDate(), $v->end_date)) ? $v->end_date : $tr['spread link Expired'];//'邀请码过期'
        if (comparison_date(getEDTDate(), $v->end_date)) {
          $v->htmlIdPrefix = 'e';
          $v->expired = '';
        } else {
          $v->end_date = $tr['spread link Expired'];//'邀请码过期'
          $v->htmlIdPrefix = 'd';
          $v->expired = 'expired';
        }
        if($protalsetting['agency_registration_gcash'] !== '0'&&$post->result->type=='A'){
          $v->end_date = $tr['Can not register now'] ;
        }
      }

      echo json_encode(['status' => 'success', 'result' => (array)$linkData]);
      break;
    case 'detail':
      $linkData = getSpreadLinkByLinkCode($post->result->code);

      if (!$linkData) {
        echo json_encode(['status' => 'fail', 'result' => ['link_code' => $post->result->id, 'msg' => $tr['spread link data not found']]]);//'查無邀请码資料'
        die();
      }

      $linkData[1]->visits_count = ($linkData[1]->visits_count == '') ? '0' : $linkData[1]->visits_count;
      $linkData[1]->register_count = ($linkData[1]->register_count == '') ? '0' : $linkData[1]->register_count;
      
      if($protalsetting['agency_registration_gcash'] !== '0'&&$linkData[1]->register_type=='A'){
        $linkData[1]->status =false;
      }else{
        $linkData[1]->status = get_linkstatus_text($linkData[1]->end_date);
      }      
      // $linkData[1]->register_type = get_register_types_text($linkData[1]->register_type);

      echo json_encode(['status' => 'success', 'result' => (array)$linkData[1]]);
      break;
    case 'copy':
      $linkData = getSpreadLinkByLinkCode($post->result->code);

      if (!$linkData) {
        echo json_encode(['status' => 'fail', 'result' => ['link_code' => $post->result->id, 'msg' => $tr['spread link data not found']]]);
        die();
      }

      $linkData[1]->visits_count = ($linkData[1]->visits_count == '') ? '0' : $linkData[1]->visits_count;
      $linkData[1]->register_count = ($linkData[1]->register_count == '') ? '0' : $linkData[1]->register_count;
      $linkData[1]->link = 'https://'.$config['website_domainname'].'/register_app.php?r='.$linkData[1]->link_code;

      echo json_encode(['status' => 'success', 'result' => (array)$linkData[1]]);
      break;
    case 'edit':
      $link_data = (object)select_spreadlink_bylinkcode($post->result->linkCode, $_SESSION['member']->account);

      if (!$link_data->status) {
        echo json_encode(['status' => 'fail', 'result' => $tr['spread link action error']]);//'该邀请码不为您帐号所属或已被删除，请确认后再行尝试'
        die();
      }

      $result = editLinkAction($post->result);

      if (!$result) {
        echo json_encode(['status' => 'fail', 'result' => $tr['spread link update failed']]);//'邀请码更新失败'
        die();
      }

      echo json_encode(['status' => 'success', 'result' => $tr['spread link update success']]);//'邀请码更新成功'
      break;
    case 'add':
      $result = insertLinkAction($post->result);

      if (!$result) {
        echo json_encode(['status' => 'fail', 'result' => $tr['spread link add failed']]);//'邀请码新增失败'
        die();
      }

      echo json_encode(['status' => 'success', 'result' => $tr['spread link add success'], 'id' => $result, 'type' => $post->result->registerType]);//'邀请码新增成功'
      break;
    case 'delete':
      $link_data = (object)select_spreadlink_bylinkcode($post->result->linkCode, $_SESSION['member']->account);

      if (!$link_data->status) {
        echo json_encode(['status' => 'fail', 'result' => $tr['spread link action error']]);//'该邀请码不为您帐号所属或已被删除，请确认后再行尝试'
        die();
      }

      $result = deleteLinkAction($post->result);

      if (!$result) {
        echo json_encode(['status' => 'fail', 'result' => $tr['spread link delete failed']]);//'邀请码删除失败'
        die();
      }

      echo json_encode(['status' => 'success', 'result' => $tr['spread link delete success']]);//'邀请码删除成功'
      break;
    default:
      echo json_encode(['status' => 'fail', 'result' => $tr['bad request']]);//'错误的请求'
      break;
  }
}


/**
 * post 資料檢查
 *
 * @return void
 */
function validatePost($post)
{
  global $tr;
  $input = array();

  if ($post['action'] == 'add') {
    $post = array_merge($post, $post['data']);
    unset($post['data']);
  }

  foreach ($post as $k => $v) {
    if ($k == 'note') {
      $input[$k] = ($v != '') ? filter_var($v, FILTER_SANITIZE_STRING) : '';
    } else {
      $input[$k] = filter_var($v, FILTER_SANITIZE_STRING);

      if ($input[$k] == '') {
        return array('status' => false, 'result' => $tr['data is illegal , please confirm the information is correct']);//'资料不合法，请确认资料正确性后再行尝试'
      }
    }
  }

  return array('status' => true, 'result' => (object)$input);
}

function insertLinkAction($post)
{
  $linkCode = get_linkcode();
  $intervalText = get_validity_period_text($post->validityPeriod, 'sql');

  $memcache = new Memcached();
  $memcached_timeout = 300;

	$key = sha1('link_code');
  $allLinkcode = get_linkmemcache($memcache, $key);

  if (!$allLinkcode) {
    $codes = select_all_linkcode();

    foreach ($codes as $v) {
      $allLinkcode[] = $v->link_code;
    }
    set_linkmemcache($memcache, $key, $allLinkcode, $memcached_timeout);
  }

  while (in_array($linkCode, $allLinkcode)) {
    $linkCode = get_linkcode();
  }

  $adddata = [
    'account' => $_SESSION['member']->account, 
    'recommendedcode' => $_SESSION['member']->recommendedcode, 
    'link_code' => $linkCode, 
    'register_type' => $post->registerType, 
    'interval_text' => $intervalText, 
    'validity_period' => $post->validityPeriod, 
    'status' => 1, 
    'description' => $post->note
  ];

  if ($post->registerType == 'A') {
    $adddata['feedbackinfo'] = json_encode(['preferential' => $post->preferential, 'dividend' => $post->dividend]);
  }

  $result = insert_link((object)$adddata);

  if (!$result) {
    return false;
  }

  $allLinkcode[] = $linkCode;
  set_linkmemcache($memcache, $key, $allLinkcode, $memcached_timeout);

  return $linkCode;
}

function editLinkAction($post)
{
  $intervalText = get_validity_period_text($post->validityPeriod, 'sql');

  $editdata = (object)[
    'link_code' => $post->linkCode, 
    'account' => $_SESSION['member']->account, 
    'interval_text' => $intervalText, 
    'validity_period' => $post->validityPeriod, 
    'description' => $post->note
  ];

  $result = edit_link($editdata);

  if (!$result) {
    return false;
  }

  return true;
}

function deleteLinkAction($post)
{
  $deldata = (object)[
    'link_code' => $post->linkCode, 
    'account' => $_SESSION['member']->account, 
  ];

  $result = del_link($deldata);

  if (!$result) {
    return false;
  }

  return true;
}