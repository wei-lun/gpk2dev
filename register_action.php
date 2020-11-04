<?php
// ----------------------------------------------------------------------------
// Features:	背景 ajax post 動作操作, 給 register.php 使用
// File Name:	register_action.php
// Author:		Barkley
// Related:		前端的
// Log:
// 不允許直接從網頁操作，需要透過 ajax 呼叫才可以
// ----------------------------------------------------------------------------


require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

// gtoken lib 現金轉帳函式庫
// require_once dirname(__FILE__) ."/gtoken_lib.php";
// 註冊專用函式庫
require_once dirname(__FILE__) ."/register_lib.php";

require_once dirname(__FILE__) ."/in/phpcaptcha/simple-php-captcha.php";

require_once dirname(__FILE__) ."/lib_agents_setting.php";
// 推廣連結函式庫
require_once dirname(__FILE__) ."/spread_register_lib.php";

require_once __DIR__ . '/Utils/RabbitMQ/Publish.php';
require_once __DIR__ . '/Utils/MessageTransform.php';

// -----------------------------------------------------------------------------
// 前台 action 會員登入身份專用：檢查有沒有參數,以及是否帶有 session
if(isset($_GET['a'])) {
    $action = filter_var($_GET['a'], FILTER_SANITIZE_STRING);

    // 檢查產生的 CSRF token 是否存在 , 錯誤就停止使用. 定義在 lib.php
    $csrftoken_ret = csrf_action_check();
    if($csrftoken_ret['code'] != 1) {
      die($csrftoken_ret['messages']);
    }

    $mq = Publish::getInstance();
    $msg = MessageTransform::getInstance();
} else {
    // 不合法的測試, 轉回 home.php. function 定義在 lib.php
    echo login2return_url(2);
    die('(x)deny to access.');
}
// -----------------------------------------------------------------------------

if (!member_register_isopen()) {
	echo '<script>alert("会员注册功能关闭中");</script>';
  die();
}

// var_dump($_SESSION);
// var_dump($_POST);
// var_dump($_GET);

function get_input_value($check_result, $col_name)
{
  if($check_result['code'] != 1) {
    $error['code'] = $check_result['code'];
    echo '<script>alert("'.$check_result['messages'].'");</script>';
    die();
  }
  return $check_result[$col_name];
}

// ----------------------------------
// MAIN 動作檢查
// ----------------------------------
if($action == 'member_register') {
// ----------------------------------------------------------------------------
// 會員從前台註冊
// ----------------------------------------------------------------------------

  if ($protalsetting['member_register_switch'] == 'off' AND $_SESSION['member']->therole != 'A') {
    $logger = '会员注册功能关闭中，如有疑问请洽客服。';
    echo '<script>alert("'.$logger.'");window.location.replace("./home.php");</script>';
    die();
  }

  // 沒有link code表示是訪客註冊, 推薦人預設系統帳號
  if ($_POST['linkcode'] == '' && $protalsetting['member_register_linkcode_must'] == 'off') {
    $defaultparent = (object)get_defaultparent_data();
    $agentcode_check_error['code'] = 1;
    $agentcode_check_error['agent_id'] = $defaultparent->result->id;
    $register_type = 'M';
    $parent_agent_id = get_input_value($agentcode_check_error, 'agent_id');
  } else {
    $agentcode_check_error = agentcode_check($_POST['linkcode'], $protalsetting['member_register_linkcode_must']);
    $parent_agent_id = get_input_value($agentcode_check_error, 'agent_id');
    $register_type = $agentcode_check_error['register_type'];
  }

  // (2)會員帳號檢查 - 3~12個字元， a-z 字母開頭、數字, 強制帳號為小寫. 帳號沒有在系統內。
  $memberaccount_check_error = memberaccount_check($_POST['memberaccount_input']);
  $memberaccount_input = get_input_value($memberaccount_check_error, 'memberaccount_input');

  // (3) 驗證碼檢查
  $captcha_check_error = captcha_check($_POST['captcha_register_input']);
  if($captcha_check_error['code'] != 1) {
    echo '<script>alert("'.$captcha_check_error['messages'].'");</script>';
    die();
  }

  // (4) 會員密碼檢查, 只檢查是否相同. 複雜度在前端檢查。
  $password_check_error = password_check($_POST['password1_input'], $_POST['password2_input']);
  $password1_input = get_input_value($password_check_error, 'password1_input');

  // (5) 提款密碼檢查 , 不可以為空
  $withdrawalspassword_input	= sha1($system_config['withdrawal_default_password']);
  if($withdrawalspassword_input == '') {
    $withdrawalspassword_error['code'] = '0';
    $withdrawalspassword_error['messages'] = '预设取款密码不可以为空值';
    echo '<script>alert("'.$withdrawalspassword_error['messages'].'");</script>';
    die();
  }

  // (6) 服務條款是否有勾選同意
  $terms_agree = filter_var($_POST['terms_agree'], FILTER_SANITIZE_STRING);
  if ($terms_agree != 'selected') {
    $terms_agree_error['code'] = '0';
    $terms_agree_error['messages'] = '服务条款没有勾选同意';
    echo '<script>alert("'.$terms_agree_error['messages'].'");</script>';
    die();
  }


  // ------------------------------
  // 此為註冊管制條件, 避免 ROBOT 攻擊的管制條件
  // 檢查指紋碼註冊是否有超過限制次數
  // 檢查IP註冊是否有超過限制次數
  // ------------------------------

  // 紀錄瀏覽器指紋資訊, 提供會員帳戶 log 使用
  $info_logger = '';
  // (7) 檢查指紋碼註冊是否有超過限制次數
  $fingerprinter_check_error = fingerprinter_check();
  $info_logger = $info_logger.get_input_value($fingerprinter_check_error, 'messages');

  // (8) 檢查IP註冊是否有超過限制次數
  $ip_countregister_check_error = ip_countregister_check();
  $info_logger = $info_logger.get_input_value($ip_countregister_check_error, 'messages');


  // ------------------------------
  // 底下開始為註冊的選項條件, 沒有一定要有
  // ------------------------------

  // (9) 真實姓名檢查
  $realname_input = '';
  if (isset($_POST['realname_input'])) {
    $realname_check_error = real_name_check($_POST['realname_input'], $protalsetting['member_register_name_show'], $protalsetting['member_register_name_must']);
    $realname_input = get_input_value($realname_check_error, 'realname_input');
  }

  // (10) 手機
  $mobilenumber_input = '';
  if (isset($_POST['mobilenumber_input'])) {
    $mobilenumber_check_error = mobilenumber_check($_POST['mobilenumber_input'], $protalsetting['member_register_mobile_show'], $protalsetting['member_register_mobile_must'], $protalsetting['member_register_mobile_unique']);
    $mobilenumber_input = get_input_value($mobilenumber_check_error, 'mobilenumber_input');
  }

  // (11) Email
  $email_input = '';
  if (isset($_POST['email_input'])) {
    $email_check_error = email_check($_POST['email_input'], $protalsetting['member_register_mail_show'], $protalsetting['member_register_mail_must'], $protalsetting['member_register_mail_unique']);
    $email_input = get_input_value($email_check_error, 'email_input');
  }

  // (12) 性別
  $sex_input = '2';
  if (isset($_POST['sex_input'])) {
    $sex_check_error = sex_check($_POST['sex_input'], $protalsetting['member_register_sex_show'], $protalsetting['member_register_sex_must']);
    $sex_input = get_input_value($sex_check_error, 'sex_input');
  }

  // (13) wechat
  $wechat_input = '';
  if (isset($_POST['wechat_input'])) {
    $wechat_check_error = wechat_check($_POST['wechat_input'], $protalsetting['member_register_wechat_show'], $protalsetting['member_register_wechat_must'], $protalsetting['member_register_wechat_unique']);
    $wechat_input = get_input_value($wechat_check_error, 'wechat_input');
  }

  // (14) qq
  $qq_input = '';
  if (isset($_POST['qq_input'])) {
    $qq_check_error = qq_check($_POST['qq_input'], $protalsetting['member_register_qq_show'], $protalsetting['member_register_qq_must'], $protalsetting['member_register_qq_unique']);
    $qq_input = get_input_value($qq_check_error, 'qq_input');
  }

  // (15) 生日
  $birthday_input = '';
  if (isset($_POST['birthday_input'])) {
    $birthday_check_error = birthday_check($_POST['birthday_input'], $protalsetting['member_register_birthday_show'], $protalsetting['member_register_birthday_must']);
    $birthday_input = get_input_value($birthday_check_error, 'birthday_input');
  }


  // 條件檢查符合 , 開始建立帳號
  $recommendedcode = get_recommendedcode($memberaccount_input);


  // 註冊送彩金有開啟才動作
  // if ($activity_register_preferential['activity_register_preferential_enable'] == 1) {
  if ($protalsetting['registered_offer_switch_status'] == 'on') {
    $permission = json_encode([
      'registered_offer' => 'on',
      'offer_data' => [
        'gift_amount' => $protalsetting['registered_offer_gift_amount'],
        'review_amount' => $protalsetting['registered_offer_review_amount']
      ]
    ]);

  }else{
    $permission = '{}';
  }

  // 動作：新增會員
  $timezone_string = '+08';
  $parent_id = $parent_agent_id;
  $insert_new_member_sql = '';
  $insert_new_member_sql = 'INSERT INTO "root_member" ("account", "nickname", "realname", "passwd", "mobilenumber", "email", "therole", "parent_id", "sex", "birthday", "wechat", "qq", "withdrawalspassword", "timezone", "enrollmentdate", "grade", "favorablerule", "registerfingerprinting", "registerip", "recommendedcode","status","permission","commissionrule") '.
  " VALUES ( '$memberaccount_input', '$memberaccount_input', '$realname_input', '$password1_input', '$mobilenumber_input', '$email_input', '$register_type', '$parent_id', '$sex_input', '$birthday_input', '$wechat_input', '$qq_input', '$withdrawalspassword_input', '$timezone_string', 'now()', '1', '預設反水設定', '".$_SESSION['fingertracker']."', '".$_SESSION['fingertracker_remote_addr']."', '".$recommendedcode."', '".$defaultstatus."','".$permission."','預設佣金設定');";

  $insert_new_member_result = runSQLtransactions($insert_new_member_sql);

  // 100. 檢查會員帳號是否建立成功
  if($insert_new_member_result == 1) {

    // 有link code才更新註冊人次
    if (isset($agentcode_check_error['linkcode']) && !empty($agentcode_check_error['linkcode'])) {
      $registernumberdata = (object)[
        'link_code' => $agentcode_check_error['linkcode'],
        'register_acc' => $memberaccount_input,
        'browser' => get_userbrowser($_SERVER['HTTP_USER_AGENT']),
        'ip' => $_SESSION['fingertracker_remote_addr'] ?? explode(',',$_SERVER['HTTP_X_FORWARDED_FOR'])['0'],
        'fingerprinting' => $_SESSION['fingertracker'] ?? ''
      ];

      update_register_number($registernumberdata);
    }

    // 建立錢包帳號 by $memberaccount_input , 會判斷是否為代理引導註冊, 如不是會直接引導會員登入系統。(免登入可以登入系統)
    $r = create_member_wallets_by_account($memberaccount_input);
    // $r = 1;
    // 101. 檢查錢包是否建立成功
    if ($r['code'] != 1) {
      $error['code'] = '312';
      //新使用者 錢包帳號建立失敗， 請聯絡客服協助處理。
      $error['messages'] = $tr['new member']. $memberaccount_input.$tr['account wallets failed'].'，'.$r['messages'].$tr['please contact customer service'];
      // echo '<hr><p align="center"><button type="button" class="btn btn-danger" onclick="location.reload();">'.$error['messages'].'</button></p>'.'<script>alert("'.$error['messages'].'");</script>';
      echo '<script>alert("'.$error['messages'].'");</script>';
      die();
    }

    // 手動審核時 insert 入 review 中待審
    $member_id_sql = "SELECT id FROM root_member WHERE account = '$memberaccount_input';";
    $member_id_result = runSQLALL($member_id_sql);

    if($defaultstatus == 1){
      $insert_register_review_sql = 'INSERT INTO "root_member_register_review" ("account", "member_id","realname", "applicationtime", "fingerprinting", "applicationip", "status","processingaccount","processingtime","notes") '.
      " VALUES ( '$memberaccount_input', '{$member_id_result['1']->id}', '$realname_input', 'now()','".$_SESSION['fingertracker']."', '".$_SESSION['fingertracker_remote_addr']."',  '".$defaultstatus."','root','now()','".$tr['audit_by_autoreview']."');";
    }else{
      $insert_register_review_sql = 'INSERT INTO "root_member_register_review" ("account", "member_id","realname", "applicationtime", "fingerprinting", "applicationip", "status") '.
      " VALUES ( '$memberaccount_input', '{$member_id_result['1']->id}', '$realname_input', 'now()','".$_SESSION['fingertracker']."', '".$_SESSION['fingertracker_remote_addr']."',  '".$defaultstatus."');";
    }

    $insert_register_review_result = runSQLtransactions($insert_register_review_sql);
    if ($insert_register_review_result != 1) {
      $error['code'] = '313';
      //新使用者 錢包帳號建立失敗， 請聯絡客服協助處理。
      $error['messages'] = $tr['new member']. $memberaccount_input .'，'. $tr['Review submission failed'];
      // echo '<hr><p align="center"><button type="button" class="btn btn-danger" onclick="location.reload();">'.$error['messages'].'</button></p>'.'<script>alert("'.$error['messages'].'");</script>';
      echo '<script>alert("'.$error['messages'].'");</script>';
      die();
    }

    //新使用者  錢包帳號已經建立。
    $error['messages'] = $tr['new member']. $memberaccount_input.$tr['wallet account build'];

    $activity_register_preferential = json_decode($member_grade_config_detail->activity_register_preferential, true);

    if ($register_type == 'A') {
      $member_id = runSQLall_prepared("SELECT id From root_member WHERE account = :account", ['account' => $memberaccount_input])[0]->id;
      $feedbackhelper = new FeedbackInfoHelper(compact('member_id'));
      $feedbackhelper->initFeedbackInfo();

      if (isset($agentcode_check_error['linkcode']) && !empty($agentcode_check_error['linkcode'])) {
        // 有 linkcode，抓出對應的反水分佣比例
        $spreadlinkinfo = select_spreadlink_bylinkcode($agentcode_check_error['linkcode'], $feedbackhelper->parentinfo->account);
        if ($spreadlinkinfo['status']) {
          $spreadlinkinfo_tmp = json_decode($spreadlinkinfo['result']->feedbackinfo);
          $feedbackhelper->setAllocable('dividend', $spreadlinkinfo_tmp->dividend * 0.01);
          $feedbackhelper->setAllocable('preferential', $spreadlinkinfo_tmp->preferential * 0.01);
        }
      }

      $feedbackhelper->save();
    }

    $currentDate = date("Y-m-d H:i:s", strtotime('now'));
    $notifyMsg = $msg->notifyMsg('MemberRegister', $memberaccount_input, $currentDate);
    $notifyResult = $mq->fanoutNotify('msg_notify', $notifyMsg);

    // $urlcode_base64 = get_member_login_urlcode($memberaccount_input);
    // echo '<script>window.location.replace("./app.php?m='.$urlcode_base64.'");</script>';
    echo '<script>window.location.replace("./register.php?rs=finish&drs='.$defaultstatus.'");</script>';

    // 提示使用者，因為不注意會很快忘記, 並且如果有 email 就要發信件到他的 email 內。
    // 寫入資料庫
    // memberlog 2db($memberaccount_input,'member','notice', $error['messages']);
    $msg=$error['messages'];
    $msg_log = $error['messages'];
    $sub_service='wallet';
    memberlogtodb($memberaccount_input,'member','info',"$msg",$memberaccount_input,"$msg_log",'f',$sub_service);
    // 取消 captcha 這個變數 , 當成功後才取消. 避免重複點擊註冊。
    unset($_SESSION['register_captcha']['code']);

    // 寫入紀錄 + fingerprint and ip info
    $logger = $error['messages'].$info_logger;
    // memberlog 2db('guest','registration','notice', "$logger");
    $msg=$logger;
    $msg_log = $logger;
    $sub_service='registration';
    memberlogtodb($memberaccount_input,'member','notice',"$msg",$memberaccount_input,"$msg_log",'f',$sub_service);
  }



// ----------------------------------------------------------------------------
 }elseif($action == 'check_memberaccount') {
// ----------------------------------------------------------------------------
//  var_dump($_POST);


// ----------------------------------------------------------------------------
}elseif($action == 'test') {
// ----------------------------------------------------------------------------
// test developer
// ----------------------------------------------------------------------------
    var_dump($_POST);

}
// ----------------------------------------------------------------------------
// END
// ----------------------------------------------------------------------------

?>
