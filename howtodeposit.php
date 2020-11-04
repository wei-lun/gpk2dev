<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 如何存款
// File Name:	howtodeposit.php
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
$function_title 		= $tr['How to deposit'];
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
if(isset($ui_data['copy']['howtodeposit'][$_SESSION['lang']])&&$ui_data['copy']['howtodeposit'][$_SESSION['lang']]!='')
  $showtext_html = $ui_data['copy']['howtodeposit'][$_SESSION['lang']];
else{
  $showtext_html = '
  <h2>'.$tr['How to deposit'].'</h2>
  <p>
  <h3>'.$tr['how to online deposit'].'：</h3>
  <ol>
    <li>'.$tr['how to deposit content1'].'</li>
    <li>'.$tr['how to deposit content2'].'</li>
    <li>'.$tr['how to deposit content3'].'</li>
    <li>'.$tr['how to deposit content4'].'</li>
    <li>'.$tr['how to deposit content5'].'</li>
  </ol>
  </p>
  <h3>'.$tr['how to online deposit notice'].'：</h3>
  <p>
  <ol>
    <li>'.$tr['how to deposit notice content1'] .'</li>
    <li>'.$tr['how to deposit notice content2'].'</li>
  </ol>
  </p>
  <p>'.$tr['how to deposit notice content3'].'</p>';
}
// 不論身份都可以觀看。

// $showtext_html = '
// <h2>'.$tr['How to deposit'].'</h2>
// <p>
// <h3>在娱乐城进行存款：</h3>
// <ol>
//   <li>会员登入后点选首页左侧的《帐户管理》进入页面后，点选【帐户充值】。</li>
//   <li>选择想使用的入款方法，依据画面步骤操作即可充值。</li>
// </ol>
// </p>
// <h3>存款注意事项：</h3>
// <p>
// <ol>
//   <li>娱乐城单笔存款最低金额为RMB¥ 10，单笔最高存款为RMB¥100000。</li>
//   <li>存款银行一律依据网站系统提供为主。</li>
//   <li>操作线上支付与移动支付前，请确认您的银行帐户已经开通相关服务。</li>
// </ol>
// </p>
// <p>若有其他操作问题，欢迎点击『客服中心』进行洽询。</p>';


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
$tmpl['sidebar_content'] = ['static','howtodeposit'];
// banner標題
$tmpl['banner'] = ['How to deposit'];
// menu增加active
$tmpl['menu_active'] =['contactus.php'];

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include($config['template_path']."template/static.tmpl.php");

?>
