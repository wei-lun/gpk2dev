<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 代理說明
// File Name:	agent_guide.php
// Author:		orange
// Related:
// Log:
// 2019.04.30
// ----------------------------------------------------------------------------



// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";
// uisetting函式庫
require_once dirname(__FILE__) ."/lib_uisetting.php";

// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------


// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
$function_title 		= $tr['membercenter_agent_instruction'];
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
//代理商才能進入代理說明
if (!isset($_SESSION['member']) || $_SESSION['member']->therole != 'A') {
	die('<script>document.location.href="./home.php";</script>');
  }
// 導覽列
$navigational_hierarchy_html = '
<ul class="breadcrumb">
  <li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
  <li class="active">'.$function_title.'</li>
</ul>
';
if($config['site_style']=='mobile'){
	$navigational_hierarchy_html =<<<HTML
		<a href="{$config['website_baseurl']}menu_admin.php?gid=agent"><i class="fas fa-chevron-left" aria-hidden="true"></i></a>
		<span>{$function_title}</span>
		<i></i>
HTML;
}
// ----------------------------------------------------------------------------



// ----------------------------------------------------------------------------
// MAIN
// ----------------------------------------------------------------------------
if($config['website_type'] == 'ecshop'){
	
	// 網站名稱
	$company_name = '群旺國際世界名牌商城';
	$showtext_html = '<h2>'.$tr['About us'].'</h2>';

	// $tr['Spend less and buy better'] = '花得更少，買得更好';
	// $tr['Qunwang International World Famous Mall'] = '群旺國際世界名牌商城是亞洲地區首屈一指的電商平台，在東南亞六國皆有佈局（新加坡、馬來西亞、泰國、印尼、越南、菲律賓），簡易方便的操作介面讓你隨時隨地都能輕鬆購物！ 群旺國際世界名牌商城 擁有完整的金流、物流服務，提供安全的線上購物環境，更有蝦皮承諾保障你的交易安全，啟動第三方支付託管交易款項，無須擔心收不到訂購的商品、或是拿不到退還的金額。商品評價和評論透明呈現在你眼前，你可以快速挑選出商品受歡迎、並提供良好服務、得到買家一致推薦的賣家。現在就來加入 群旺國際世界名牌商城 ，享受最獨一無二的網路購物體驗！';
	$showtext_html = $showtext_html.'
	<h3>'.$tr['Spend less and buy better'].'</h3>
	<p>'.$tr['Qunwang International World Famous Mall'].'</p>';

	// $tr['One-stop boutique mall'] = '一站式精品商城';
	// $tr['You can meet the needs of your boutique purchase'] = '在群旺國際世界名牌商城購物，你能夠滿足你精品採購的需求。從每日「限時特賣」專區，你可以找到各式好康下殺品；「每日新發現」貼心的相關商品推薦為你整理出更多好選擇。群旺國際世界名牌商城網羅國內外各大知名大牌進駐，所有商品均享有100%正品的保證，種類繁多、品牌齊全，包括3M、鍋寶、NIVEA、acer、樹德收納、MOTHER-K等，別忘了還有蝦幣回饋，只要在優選賣家賣場和蝦皮商城消費即可獲得蝦幣累積和進行折抵，買越多賺越多，線上購物從未這麼簡單！ 不知道要買什麼？快來「熱門搜尋」一覽時下最夯熱門話題商品，瞧瞧大家都在瘋什麼！或是直接瀏覽美妝保健、女生衣著、女生配件、女鞋、女生包包、嬰幼童與母親、男生衣著、男生包包與配件、男鞋、寵物、美食伴手禮、娛樂收藏、遊戲王、居家生活、手機平板與周邊、3C、家電影音、戶外運動用品、汽機車零件百貨、服務票券、代買代購和其他類別，強大的搜尋功幫助你找尋心中好物，趕快加入 群旺國際世界名牌商城 挖掘最新熱門好貨！';
	$showtext_html = $showtext_html.'
	<h3>'.$tr['One-stop boutique mall'].'</h3>
	<p>'.$tr['You can meet the needs of your boutique purchase'].'</p>';

	// $tr['Meet your food, clothing, shelter, transportation, education, music'] = '滿足你的食、衣、住、行、育、樂';
	// $tr['QunWant International World Brand Mall was established in 2007 to diversified boutique'] = '群旺國際世界名牌商城成立於2007年，以多元精品、娛樂產業起家。在2017年轉進娛樂精品市場，透過優異的網路核心技術提供玩家休閒遊戲、精品購物為核心理念，打造亞洲第一的綜合網路精品商城服務。群旺國際世界名牌商城一路不斷精進，希望能成為買家、玩家正面能量的補充管道，隨時透過精品商城，讓購物滿足內的快感；心情不好時，透過娛樂的爽快讓您再次微笑、煩惱減半。我們更希望能成為玩家真誠相待的長久朋友。';
	$showtext_html = $showtext_html.'
	<h3>'.$tr['Meet your food, clothing, shelter, transportation, education, music'].'</h3>
	<p>'.$tr['QunWant International World Brand Mall was established in 2007 to diversified boutique'].'</p>';
}else{
	if(isset($ui_data['copy']['agent_instruction'][$_SESSION['lang']])&&$ui_data['copy']['agent_instruction'][$_SESSION['lang']] !="" ){
		$showtext_html = $ui_data['copy']['agent_instruction'][$_SESSION['lang']];
	}
	else{
	// 網站名稱
	$company_name = 'GPK娛樂城';
	$showtext_html = '<p>当您能看到这个页面,说明您的账号即是玩家账号也是代理账号,即可以自己投注,也可以发展下级玩家,賺取返点傭金。</p>

	<h3>如何赚取赔率返点?</h3>
	<p>可获得的返点,等于自身返点与下级返点的差值,如自 身返点5,下级返点3,你將能获得下級投注金额2%的返点,如下级投注100元,你将会获得2元。点击下级开户,可查看自身赔率返点,也可对下级设置赔率。</p>
	
	<h3>如何為下级开户?</h3>
	<p>点击下级开户,先为您的下级设置返点,设置成功后会生成一条邀请码,将邀请码发送给您的下级注册,注册后他就是您的下级,点击会员管理,就能查看他注册的账号;如果您对下级设置的是代理类型的账号,那么您的下级 就能继续发展下级,如果设置的是玩家类型,那么您的下级只能投注,不能再发展下级,也看不到代理中心;</p>

	<h3>温馨提示:</h3>
	<p>返点不同赔率也不同,点击返点赔率表,可查看返点赔率表；返点越低,赔率就越低,建议对下级设置的返点不要过低；可在代理报表、投注明细、交易明细查看代理的发展情況。</p>';
  }
}




// 不論身份都可以觀看。

// 切成 3 欄版面
$indexbody_content = $indexbody_content.'
<div id="static" class="main_content agent_ins">
'.$showtext_html.'
</div>
<div class="row">
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
$tmpl['sidebar_content'] = ['agent','agent_instruction'];
// banner標題
$tmpl['banner'] = ['membercenter_menu_admin_agent'];
// menu增加active
$tmpl['menu_active'] =['agent_instruction.php'];
// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include($config['template_path']."template/admin.tmpl.php");

?>
