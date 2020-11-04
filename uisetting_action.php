<?php
// ----------------------------------------------------------------------------
// Features:	ui給前台讀取uisetting， 動作的處理
// File Name:	
// Author:		orange
// Related:   uisetting.php
// Log:
// ----------------------------------------------------------------------------

require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

/*
if(isset($_GET['a'])) {
    $action = filter_var($_GET['a'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
}else{
    die('(x)不合法的測試');
}

if (!isset($_SESSION['agent']) || $_SESSION['agent'] == 'R') {
  die('(x)請登入正確管理帳號再行嘗試');
}*/
// var_dump($_SESSION);
//var_dump($_POST);
//var_dump($_GET);
/*
if($_GET["fmt"]=="json"){
$site_stylesetting_sql = "SELECT jsondata FROM site_stylesetting WHERE id = 3";
$site_stylesetting_result = runSQLall($site_stylesetting_sql);
$jsond = $site_stylesetting_result[1]->jsondata;
echo $jsond;}
//echo $_GET["fmt"];

s=100&fmt=json
*/
// ----------------------------------
// 本程式使用的 function
// ----------------------------------

//取得行為呼叫
echo str_replace(array("\r", "\n", "\r\n", "\n\r","\t"), '', urldecode(json_encode($ui_data)));
// if(isset($_GET['sid'])) {
//   //  $id = $_GET['sid'];
//   //  $site_stylesetting_sql = "SELECT jsondata FROM site_stylesetting WHERE id = ".$id;
// 	// $site_stylesetting_result = runSQLall($site_stylesetting_sql);
//   // //print_r($site_stylesetting_result[1]->jsondata);
//   // echo urldecode($site_stylesetting_result[1]->jsondata);
//   echo urldecode(json_encode($ui_data));
// }else{
//   die("ERROR");
// }

?>