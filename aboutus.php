<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 关于我们
// File Name:	aboutus.php
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
$function_title 		= $tr['About us'];
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
	if(isset($ui_data['copy']['aboutus'][$_SESSION['lang']])&&$ui_data['copy']['aboutus'][$_SESSION['lang']] !="" ){
		$showtext_html = $ui_data['copy']['aboutus'][$_SESSION['lang']];
	}
	else{
	// 網站名稱
	$company_name = ''.$tr['company name'].'';
	$showtext_html = '<h2>'.$tr['About us'].'</h2>';

	// $tr['Entertainment.Innovation.legitimate'] = '娛樂 ．創新 ．合法';
	// $tr['EntertainmentInnovationlegitimate'] = 'JIGDEMO目前擁有哥斯達黎加合法註冊之博彩公司，一切博彩營業行為皆遵從哥斯特黎加政府的博彩條約。我們在日漸熱絡的網絡博彩市場中，不斷求新求變，以傲人的創意團隊開發各種遊戲方式。為客戶提供即時、 刺激、體貼的娛樂產品與高質量服務，是本公司創建JIGDEMO的首要宗旨。';
	$showtext_html = $showtext_html.'
	<h3>'.$tr['Entertainment.Innovation.legitimate'].'</h3>
	<p>'.str_replace("JIGDEMO",$config['companyShortName'],$tr['EntertainmentInnovationlegitimate']).'</p>';

	// $tr['meet entertainment needs'] = '以專業、公正的科技背景滿足各種娛樂需求';
	// $tr['invests a lot of manpower and resources'] = 'JIGDEMO在運動博彩上投注大量的人力與資源，更由頂級的盤房進行專業操盤，提供完整賽事、 搭配 豐富的玩法組合給熱愛體育的玩家。真人視訊 —我們所聘任的荷官均須接受嚴格的國際賭場專業訓練與認證，進行各種賭場遊戲時，所有賭局都依 荷官動作做出反應，而不是無趣的計算機機率默認結果。高科技的網絡直播技術，更能帶給您親歷賭場的刺激經驗！彩票遊戲—官方賽事結果正是本類游戲唯一的勝負標準，讓玩家在活潑的投注接口中，享受最公正的娛樂。電子遊戲—使用最公平的隨機數生成機率，讓您安心享受多元、炫麗的娛樂性遊戲。 JIGDEMO所有遊戲共同的優點 — 不須耗時下載；接口簡單明了；操作功能齊全； 畫面精緻優雅；遊戲結果公平、公正、公開！';
	$showtext_html = $showtext_html.'
	<h3>'.$tr['meet entertainment needs'].'</h3>
	<p>'.str_replace("JIGDEMO",$config['companyShortName'],$tr['invests a lot of manpower and resources']).'</p>';

	// $tr['Good business reputation'] = '良好的商誉．安全、隐密的网络环境';
	// $tr['In the competitive gaming market'] = '在競爭激烈的博彩市場中，JIGDEMO向來是眾多玩家一致的選擇，除了因為多元化的娛樂產品使人流連忘返、更因為高質量的服務以及JIGDEMO長久以來的良好信譽在廣大玩家群眾之間建立了口碑。我們的用心隨處可見，並且獲得了GEOTRUST的權威性國際認證，以確保網站活動的公平、 公正，所有會員數據均經加密處理，保障玩家隱私。 JIGDEMO以服務不打烊的精神，全天候24小時處理會員出入款的相關事宜，嚴格訓練的客服團隊，以專業、親切的態度解決您對於網站、遊戲的種種疑難雜症，讓每位玩家有賓至如歸的感覺！ JIGDEMO以業界前所未見的各種優惠方式回饋我們的會員，絕對是玩家最明智的選擇！';
	$showtext_html = $showtext_html.'
	<h3>'.$tr['Good business reputation'].'</h3>
	<p>'.str_replace("JIGDEMO",$config['companyShortName'],$tr['In the competitive gaming market']).'</p>';
  }
}




// 不論身份都可以觀看。

// 切成 3 欄版面
$indexbody_content = $indexbody_content.'
<div class="main_content">
	<div>
'.$showtext_html.'
	</div>
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
$tmpl['sidebar_content'] = ['static','aboutus'];
// banner標題
$tmpl['banner'] = ['About us'];
// menu增加active
$tmpl['menu_active'] =['contactus.php'];

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include($config['template_path']."template/static.tmpl.php");

?>
