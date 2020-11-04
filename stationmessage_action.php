<?php
// ----------------------------------------------------------------------------
// Features:	訊息除存資料庫
// File Name:	stationmessage_action.php
// Author:		Barkley
// Related:   stationmessage.php
// Log:
// ----------------------------------------------------------------------------



// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";


if(isset($_GET['a'])) {
    $action = $_GET['a'];
}else{
    die($tr['Illegal test']);//'(x)不合法的測試'
}
//var_dump($_SESSION);
// var_dump($_POST);
//var_dump($_GET);

if($action == 'stationmessage_send' AND isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T' ) {
// ----------------------------------------------------------------------------
// 使用者修改自己的資料, 對應前台 member.php 檔案的功能
// 有登入系統才可以工作
// 可以修改的欄位：暱稱,真實名稱,行動電話,電子郵件,微信 ID,QQ ID
// ----------------------------------------------------------------------------
// var_dump($_POST);
$send_message_text  = filter_var($_POST['send_message_text'], FILTER_SANITIZE_STRING);
$sendto_cs  = 'gpk';
$sendfrom   = $_SESSION['member']->account;
$sql = 'INSERT INTO "root_stationmessage" ("sendtime", "msgfrom", "msgto", "message", "read")'."VALUES (now(), '$sendfrom', '$sendto_cs', '$send_message_text', NULL);";
// echo $sql;
$ret = runSQLall($sql);
echo '<script>location.reload("");</script>';

}elseif($action == 'test') {
// ----------------------------------------------------------------------------
// test developer
// ----------------------------------------------------------------------------
    var_dump($_POST);
    echo 'ERROR';

}



?>
