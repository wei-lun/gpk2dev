
<?php
// ----------------------------------------------------------------------------
// Features:	會員填完個人資料後，就可以提出申請成為代理。
// File Name:	member.php
// Author:		Barkley
// Related:
// Log:
// ----------------------
// 1. 個人資料維護
// 2. 修改登入密碼、取款密碼
// ----------------------

// ----------------------------------------------------------------------------

// 主機及資料庫設定
require_once dirname(__FILE__) . "/config.php";
// 支援多國語系
require_once dirname(__FILE__) . "/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) . "/lib.php";

// var_dump($_SESSION);
//var_dump(session_id());

// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
$function_title = '分享給好友';
// 擴充 head 內的 css or js
$extend_head = '';
// 放在結尾的 js
$extend_js = '';
// body 內的主要內容
$indexbody_content = '';
// 系統訊息選單
$messages = '';
// 初始化變數 end
// ----------------------------------------------------------------------------
// 導覽列
$navigational_hierarchy_html = '
<ul class="breadcrumb">
  <li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
  <li><a href="member.php">' . $tr['Member Centre'] . '</a></li>
  <li class="active">' . $function_title . '</li>
</ul>
';
if($config['site_style']=='mobile'){
  $navigational_hierarchy_html =<<<HTML
	<a href="{$config['website_baseurl']}menu_admin.php?gid=safe"><i class="fas fa-chevron-left"></i></a>
	<span>$function_title</span>
	<i></i>
HTML;
}
// ----------------------------------------------------------------------------



// 切成 3 欄版面 3:8:1
$indexbody_content = '';
//功能選單(美工)  功能選單(廣告)
// $indexbody_content = $indexbody_content . '
// <div class="row justify-content-md-center">
// 	<div class="col-12">
//   ' . $member_persondata_html . '
//     </div>
// </div>
// <div class="row justify-content-md-center">
//     <div class="col-12">
//     <div id="preview_result"></div>
//     </div>
// </div>
// ';

// ----------------------------------------------------------------------------
// 準備填入的內容
// ----------------------------------------------------------------------------
// 將內容塞到 html meta 的關鍵字, SEO 加強使用
$tmpl['html_meta_description'] = $config['companyShortName'];
$tmpl['html_meta_author'] = $config['companyShortName'];
$tmpl['html_meta_title'] = $function_title . '-' . $config['companyShortName'];

// 系統訊息顯示
$tmpl['message'] = $messages;
// 擴充再 head 的內容 可以是 js or css
$tmpl['extend_head'] = $extend_head;
// 擴充於檔案末端的 Javascript
$tmpl['extend_js'] = $extend_js;
// 主要內容 -- title
$tmpl['paneltitle_content'] = $navigational_hierarchy_html;
// 主要內容 -- content
$tmpl['panelbody_content'] = $indexbody_content;
// 側邊欄MENU var[0]側邊欄組合(lib_menu) var[1]目前頁籤名
$tmpl['sidebar_content'] =['safe','member_share_friends'];

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include $config['template_path'] . "template/admin.tmpl.php";

?>
