<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 通用頁面(會call template的內容，作為靜態頁面使用)
// File Name:	page.php
// Author:		orange
// Related:
// Log:
// 2019 04 11
// ----------------------------------------------------------------------------

// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------

$page_tmpl = $config['template_path']."template/page.tmpl.php";
if (!file_exists($page_tmpl)){
	die('<script>location.href ="'.$config['website_baseurl'].'";</script>');
}

// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
$function_title 		= '';
// 擴充 head 內的 css or js
$extend_head				= '';
// 放在結尾的 js
$extend_js					= '';
// body 內的主要內容
$indexbody_content	= '';
// 系統訊息選單
$messages 					= '';
// ----------------------------------------------------------------------------
// 初始化變數 end
// ----------------------------------------------------------------------------
// ----------------------------------------------------------------------------
// MAIN
// ----------------------------------------------------------------------------

include($page_tmpl);

// ----------------------------------------------------------------------------
// MAIN  END
// ----------------------------------------------------------------------------



// ----------------------------------------------------------------------------
// 準備填入的內容
// ----------------------------------------------------------------------------
// 將內容塞到 html meta 的關鍵字, SEO 加強使用
$tmpl['html_meta_description'] 		= $config['companyShortName'];
$tmpl['html_meta_author']	 				= $config['companyShortName'];
$tmpl['html_meta_title'] 					= $function_title.'-'.$config['companyShortName'];

// 系統訊息顯示
$tmpl['message']									= $messages;
// 擴充再 head 的內容 可以是 js or css
$tmpl['extend_head']							= $extend_head;
// 擴充於檔案末端的 Javascript
$tmpl['extend_js']								= $extend_js;
// 主要內容 -- title
$tmpl['paneltitle_content'] 			= $navigational_hierarchy_html;
// 主要內容 -- content
$tmpl['panelbody_content']				= $indexbody_content;
// 側邊欄MENU var[0]側邊欄組合(lib_menu) var[1]目前頁籤名
//$tmpl['sidebar_content'] = ['static','aboutus'];

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include($config['template_path']."template/static.tmpl.php");

?>
