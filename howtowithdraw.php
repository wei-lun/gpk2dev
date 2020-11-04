<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 如何取款
// File Name:	howtowithdraw.php
// Author:		Barkley
// Related:
// Log:
// 2016.10.20
// ----------------------------------------------------------------------------



// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";
// uisetting函式庫
require_once dirname(__FILE__) ."/lib_uisetting.php";

//var_dump($_SESSION);
//var_dump($_POST);

// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------


// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
$function_title 		= $tr['How to withdraw'];
// 擴充 head 內的 css or js
$extend_head				= '';
// 放在結尾的 js
$extend_js					= '';
// body 內的主要內容
$indexbody_content	= '';
// 系統訊息選單
$messages 					= '';
// 初始化變數 end
// ----------------------------------------------------------------------------
// 導覽列
$navigational_hierarchy_html = '
<ul class="breadcrumb">
  <li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
  <li class="active">'.$function_title.'</li>
</ul>
';
if($config['site_style']=='mobile'){
  $navigational_hierarchy_html =<<<HTML
    <a href="{$config['website_baseurl']}home.php"><i class="fas fa-chevron-left" aria-hidden="true"></i></a>
    <span>{$function_title}</span>
    <i></i>
HTML;
}
//header 內文功能列
if($config['site_style']=='mobile') {
  $header_content = '<div class="w-100 header_content"></div>';
}else{
 $header_content = '';
}
// ----------------------------------------------------------------------------


// ----------------------------------------------------------------------------
// MAIN
// ----------------------------------------------------------------------------
if(isset($ui_data['copy']['howtowithdraw'][$_SESSION['lang']])&&$ui_data['copy']['howtowithdraw'][$_SESSION['lang']]!='' ){
  $showtext_html = $ui_data['copy']['howtowithdraw'][$_SESSION['lang']];
}else{
  $showtext_html = '
<h2>'.$tr['how to online withdrawal'].'</h2>
<p>
<ol>
  <li>'.$tr['how to withdrawal content1'].'</li>
  <li>'.$tr['how to withdrawal content2'].'</li>
  <li>'.$tr['how to withdrawal content3'].'</li>
  <li>'.$tr['how to withdrawal content4'].'</li>
  <li>'.$tr['how to withdrawal content5'].'</li>
</ol>
</p>';


$showtext_html = $showtext_html.'
<h3>'.$tr['how to withdrawal notice'].'</h3>
<p>
<ol>
  <li>'.$tr['how to withdrawal notice content1'].'</li>
  <li>'.$tr['how to withdrawal notice content2'].'</li>
  <li>'.$tr['how to withdrawal notice content3'].'</li>
</ol>
<b>'.$tr['how to withdrawal notice content4'].'</b>
<h5>'.$tr['how to withdrawal notice content5'].'</h5>
<p>'.$tr['how to withdrawal notice content6'].'</p>
<h5>'.$tr['how to withdrawal notice content7'].'</h5>
<p>'.$tr['how to withdrawal notice content8'].'<br>'.$tr['how to withdrawal notice content9'].'</p>
<h5>'.$tr['how to withdrawal notice content10'].'</h5>
<p>'.$tr['how to withdrawal notice content11'].'</p>
</p>';


$showtext_html = $showtext_html.'
<h3>'.$tr['electronic game , pull money game'].'</h3>
<p>
<ul>
<li>'.$tr['electronic game , pull money game content1'].'</li>
<li>'.$tr['electronic game , pull money game content2'].'</li>
<li>'.$tr['electronic game , pull money game content3'].'</li>
<li>'.$tr['electronic game , pull money game content4'].'</li>
<li>'.$tr['electronic game , pull money game content5'].'</li>
</ul>
</p>';
}

// 不論身份都可以觀看。


// 內容填入整理
// 切成 3 欄版面
$indexbody_content = $indexbody_content.$header_content.'
<div class="main_content">
	<div>
'.$showtext_html.'
	</div>
</div>
<div class="row mx-0">
	<div class="col-md-10 offset-md-1">
		<div id="preview"></div>
	</div>
</div>
';

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
$tmpl['sidebar_content'] = ['static','howtowithdraw'];
// banner標題
$tmpl['banner'] = ['How to withdraw'];
// menu增加active
$tmpl['menu_active'] =['contactus.php'];

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include($config['template_path']."template/static.tmpl.php");

?>
