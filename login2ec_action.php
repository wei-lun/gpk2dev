<?php
use PHPMailer\PHPMailer;
// ----------------------------------------------------------------------------
// Features:  前台 -- 登入 EC 之註冊驗證
// File Name:  login2ec_action.php
// Author:    Webb Lu
// Related:   login2ec.php
// Log:
// 2017.09.12 prototype and verify action; with sql
// 2017.09.13 send mail with phpmailer
// 2017.09.14 full test and comments
// ----------------------------------------------------------------------------


// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";
// PHPMailer + SMTP + Exception
require_once dirname(__FILE__) . '/in/phpmailer/PHPMailer.php';
require_once dirname(__FILE__) . '/in/phpmailer/SMTP.php';
require_once dirname(__FILE__) . '/in/phpmailer/Exception.php';

// 未定義行為 action
if(is_null($action = filter_input(INPUT_GET, 'a'))) die('(x)不合法的測試');
// 身分驗證; 驗證 token 不須登入
if($action == 'checkout_token');
elseif(!isset($_SESSION['member']) OR $_SESSION['member']->therole == 'T') die('操作身分錯誤');

if(isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T') {
  $sql = "SELECT * FROM root_member LEFT JOIN root_member_opencart ON root_member.id=root_member_opencart.id WHERE root_member.id = '".$_SESSION['member']->id."';";
  $m = runSQLall($sql);
  // var_dump($m);
}

$server_protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http';
$redirect_url = $server_protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']).'/login2ec.php';

switch ($action) {
  case 'send_verification':
  // 生成 ec_salt 與 ec_token; 回寫資料庫(UPDATE or INSERT); 生成信件內文; 發送驗證信
    if($m[1]->ec_verifyresult != 0) die('已經執行過驗證動作！；回到<a href="' . $redirect_url . '">登入頁面</a>');
    // 接 $_POST['email']
    $ec_account = filter_input(INPUT_POST, 'email');
    $is_valid_email = preg_match('/^([^@\s]+)@((?:[-a-z0-9]+\.)+[a-z]{2,})$/', $ec_account);
    if(!$ec_account) die('email 欄位不可為空；回到<a href="' . $redirect_url . '">登入頁面</a>');
    if(!$is_valid_email) die('輸入的 email 不合法！；回到<a href="' . $redirect_url . '">登入頁面</a>');
    if(is_ec_acocunt_used($ec_account)) die('此 mail 已經被使用；回到<a href="' . $redirect_url . '">登入頁面</a>');

    $ec_salt = ec_salt(9);
    $ec_password = sha1('ec1' . $_SESSION['member']->id);
    $ec_token = jwtenc($ec_salt, ['email' => $ec_account]);

    // 先只寫 insert; resend verification 的狀況再另外考慮
    $member_opencart_add_verification_sql = "INSERT INTO root_member_opencart (id, ec_account, ec_password, ec_salt, ec_verifytoken, ec_verifytokentimeout, ec_verifyresult) VALUES ('".$_SESSION['member']->id."', '" .$ec_account."', '".$ec_password."', '".$ec_salt."', '".$ec_token."', now() + interval '10 minute', '2');";
    
    //發送驗證信
    if($result = runSQL($member_opencart_add_verification_sql)) {
      send_verify_mail([
        'EC_MAILER' => $config['ec_mailer_verification'],
        'nickname' => $m['1']->nickname ?? $m['1']->realname ?? $ec_account,
        'email_addr' => $ec_account,
        'verify_host' => $server_protocol.'://'.$_SERVER['HTTP_HOST'],
        'verify_path' => $_SERVER['SCRIPT_NAME'].'?a=checkout_token&token='.$ec_token,
        // 'debug'      => 2
      ]);

      header('Location: '.$redirect_url);
    } else die('伺服器錯誤；回到<a href="' . $redirect_url . '">登入頁面</a>');

    break;
  
  case 'checkout_token':
    // 檢查傳過來的 token 是否合法： 1. token 是否存在資料庫? 2. 有效時限內
    $member_opencart_check_verification_sql = "SELECT * FROM root_member LEFT JOIN root_member_opencart ON root_member.id=root_member_opencart.id WHERE ec_verifytoken = '".filter_input(INPUT_GET, 'token')."' AND ec_verifytokentimeout >= now();";
    $ec_member_info = runSQLall($member_opencart_check_verification_sql);

    // var_dump($ec_member_info);

    // 如果 token 正確未過期，且狀態為等待驗證
    if($ec_member_info[0] == 1 AND $ec_member_info[1]->ec_verifyresult == 2) {
      // 透過 curl 到 ec 註冊，salt&token=['email', 'password', 其他可帶資訊];
      $url = $config['ec_protocol'].'://'.$config['ec_host'].'/index.php?route=account/register/bygpk';
      $salt = $ec_member_info[1]->ec_salt;
      $gpk_token = jwtenc($salt, [
        'gpk_account' => $ec_member_info[1]->account,
        'email'   => $ec_member_info[1]->ec_account,
        'password'  => $ec_member_info[1]->ec_password,
        'salt'    => $salt = $ec_member_info[1]->ec_salt,
        'firstname' => $ec_member_info[1]->realname ?? '親愛的',
        'lastname'  => $ec_member_info[1]->nickname ?? '顧客',
        'telephone' => $ec_member_info[1]->mobilenumber,
      ], $debug = 0);
      
      if($system_mode == 'developer') die('Email 驗證通過；環境為 ' . $system_mode . '不允許新增帳號；回到<a href="' . $redirect_url . '">登入頁面</a>');
      elseif ($success = ec_register($url, $salt, $gpk_token, $debug=0)) {
        // 更新驗證狀態為 1
        $member_opencart_update_verification_sql = "UPDATE root_member_opencart SET ec_verifyresult = 1 WHERE id = ".$ec_member_info[1]->id.";";
        $result = runSQL($member_opencart_update_verification_sql);
      } else {
        // 更新驗證狀態為 3: 商城註冊失敗
        $member_opencart_update_verification_sql = "UPDATE root_member_opencart SET ec_verifyresult = 3 WHERE id = ".$ec_member_info[1]->id.";";
        $result = runSQL($member_opencart_update_verification_sql);

        die('商城出錯! 請洽客服；回到<a href="' . $redirect_url . '">登入頁面</a>');
      }

      header('Location: '.$redirect_url);
    }
    elseif ($ec_member_info[0] == 1 AND $ec_member_info[1]->ec_verifyresult == 3) die('商城出錯! 請洽客服；回到<a href="' . $redirect_url . '">登入頁面</a>');
    else die('不合法的驗證行為或已經過期！請至<a href="' . $redirect_url . '"">登入頁面</a>重新操作');

    break;

  case 'resend_verification':
    $now = time();
    // 等待驗證狀態且token過期才能要求重新發送
    if($m[1]->ec_verifyresult != 2 || $now < strtotime($m[1]->ec_verifytokentimeout)) die('操作行為錯誤!!');

    $ec_account = filter_input(INPUT_POST, 'email');
    $is_valid_email = preg_match('/^([^@\s]+)@((?:[-a-z0-9]+\.)+[a-z]{2,})$/', $ec_account);    
    if(!$ec_account) die('email 欄位不可為空；回到<a href="' . $redirect_url . '">登入頁面</a>');
    if(!$is_valid_email) die('輸入的 email 不合法！；回到<a href="' . $redirect_url . '">登入頁面</a>');

    // $ec_salt = ec_salt(9);
    $ec_salt = $m[1]->ec_salt;
    $ec_token = jwtenc($ec_salt, ['email' => $ec_account]);

    // UPDATE
    $member_opencart_update_re_verification_sql = "UPDATE root_member_opencart SET (ec_account, ec_salt, ec_verifytoken, ec_verifytokentimeout, ec_verifyresult) = ('" .$ec_account."', '".$ec_salt."', '".$ec_token."', now() + interval '10 minute', '2') WHERE id = ".$m[1]->id.";";
    
    //發送驗證信
    if($result = runSQL($member_opencart_update_re_verification_sql)) {
      send_verify_mail([
        'EC_MAILER' => $config['ec_mailer_verification'],
        'nickname' => $m['1']->nickname ?? $m['1']->realname ?? $ec_account,
        'email_addr' => $ec_account,
        'verify_host' => $server_protocol.'://'.$_SERVER['HTTP_HOST'],
        'verify_path' => $_SERVER['SCRIPT_NAME'].'?a=checkout_token&token='.$ec_token,
        // 'debug'      => 2
      ]);

      header('Location: '.$redirect_url);
    } else die('伺服器錯誤；回到<a href="' . $redirect_url . '">登入頁面</a>');
  
  default:
    die('(x)不合法的測試；回到<a href="' . $redirect_url . '">登入頁面</a>');
    break;
}

function ec_salt($length = 32) {
  // Create random token
  $string = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';  
  $max = strlen($string) - 1;  
  $token = '';

  for ($i = 0; $i < $length; $i++) {
    $token .= $string[mt_rand(0, $max)];
  }   
  return $token;
}

function send_verify_mail($param) {

  $mail = new PHPMailer\PHPMailer;
  $mail->isSMTP();
  $mail->Host = "127.0.0.1"; 
  $mail->CharSet = "utf8"; 
  // $mail->From = 'jutainetwebb@jutainet.com'; 
  // $mail->FromName = "聚泰科技 - 測試人員"; 

  // $mail->Subject = "PHPMailer 測試信件"; 
  $mail->From = $param['EC_MAILER']->send_by_addr;
  $mail->FromName = $param['EC_MAILER']->send_by_name;
  $mail->Subject = $param['EC_MAILER']->mail_title;

  $mail->Body = '
    <h1>Email 地址驗證</h1>'. 
    $param['nickname'].' 您好，
    <p>這封信是由 JIGDEMO 系統發送的，請勿直接回覆此郵件。<br/>
    您收到這封郵件，是由於在 JIGDEMO 驗證了登入 EC 所須的 Emaill 郵箱地址。如果您並沒有執行驗證操作，請忽略這封郵件。您不需要退訂或進行其他進一步的操作。</p>

    <div>----------------------------------------------------------------------</div>
    <h2>帳號激活說明</h2>
    <div>----------------------------------------------------------------------</div>
    <p>您好，我們需要對您的地址有效性進行驗證以避免垃圾郵件或地址被濫用。<br/>
    您只需點擊下面的鏈接即可激活您的帳號：<br/>' .
    $param['verify_host'] . $param['verify_path'] .
    '<br/>(如果上面不是鏈接形式，請將該地址手工粘貼到瀏覽器地址欄再訪問)<br/>
    感謝您的訪問，祝您消費愉快！</p>
    <p>此致<br/>    
    <a href="'.$param['verify_host'].dirname($param['verify_path']).'" target="_blank">JIGDEMO - 管理團隊</a><br/>
  '; 

  $mail->IsHTML(true); 
  // 發送至驗證對象
  $mail->AddAddress($param['email_addr'], $param['nickname']); 
  $mail->SMTPDebug = $param['debug'] ?? 0;

  if(!$mail->Send()) $result = ['state' => true, 'msg' => 'success']; 
  else $result = ['state' => false, 'msg' => 'fail, open smtp debugger'];

  return $result;
}

// 為 user 到 ec 註冊
function ec_register($url, $salt, $token, $debug=false) {

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['token' => $token, 'salt' => $salt])); 
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  $_SERVER['DOCUMENT_ROOT'] .'/cacert.pem');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,  $_SERVER['DOCUMENT_ROOT'] .'/cacert.pem');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  $ch_output = curl_exec($ch); 
  $ch_error = curl_error($ch);
  curl_close($ch);  

  if($debug):
    var_dump($ch_error);
    var_dump($ch_output);
    die('debug mode in ' . __FUNCTION__ );
  endif;

  $register_state = json_decode($ch_output)->status ?? false;

  return !$ch_error && $register_state;
}

// 檢查 table 中 ec_accoun 是否已經被使用
function is_ec_acocunt_used($ec_account) {
  $sql = "SELECT count(*) FROM root_member_opencart WHERE ec_account = '" . $ec_account . "'";
  return (runSQLall($sql)[1]->count > 0) ? true : false;
}
?>