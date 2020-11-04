<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 聯絡我们
// File Name:	contactus.php
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
//聯絡我們
$function_title 		= $tr['contact us'];
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
//heder內文功能列
if($config['site_style']=='mobile'){
	$header_content = '<div class="w-100 header_content"></div>';
}else{
	$header_content = '';
}
// ----------------------------------------------------------------------------


// ----------------------------------------------------------------------------
// MAIN
// ----------------------------------------------------------------------------
// 变数写在 system_config 内, 因为广告或其他分页会引用到
// var_dump($customer_service_cofnig);
// var_dump($protalsetting);

$wechat_qrcode_image_html = '<img id="qrcode_img" height="200" src="'.$customer_service_cofnig['wechat_qrcode'].'" tyle="display:none">';

//聯絡我們
//JIGDEMO的客服中心全年無休，提供1週7天、每天24小時的優質服務。 <br>
//如果您對本網站的使用有任何疑問，可以透過下列任一方式與客服人員聯繫，享受最即時的服務：
//點擊  在線客服  連結，即可進入在線客服系統與客服人員聯繫。
//您亦可使用下列聯絡方式與客服人員取得聯繫：
//客服人員
if(isset($ui_data['copy']['contactus'][$_SESSION['lang']])&&$ui_data['copy']['contactus'][$_SESSION['lang']]!=""){
	$contact_us_content=$ui_data['copy']['contactus'][$_SESSION['lang']];
}
else{
	$contact_us_content='<p>'.$tr['contact us content'].'</p>';
}

//在線客服 $tr['online customer service']
if( $customer_service_cofnig['online_weblink'] != '' ){
	$online_service = '
	<li>
		<a href="'.$customer_service_cofnig['online_weblink'].'">
		<div class="img_box">
			<i class="fas fa-comments"></i>
		</div>		
		<div class="contacts_text_box">
			<p class="mb-0">'.$tr['online customer service'].'</p>
		</div>
		</a>
	</li>
	';
}else{
	$online_service = '';
}

//客服信箱 $tr['customer service mail']
if( $customer_service_cofnig['email'] != '' ){
	$service_mail = '
	<li>
		<a href="mailto:'.$customer_service_cofnig['email'].'">
		<div class="img_box">
			<i class="far fa-envelope"></i>
		</div>		
		<div class="contacts_text_box">
			<p class="mb-0">'.$tr['customer service mail'].'</p>
			<p class="mb-0">'.$customer_service_cofnig['email'].'</p>
		</div>
		</a>
	</li>
	';
}else{
	$service_mail = '';
}

//客服專線 $tr['customer service tel'] 
if( $tr['customer service tel']  != '' ){
	$service_tel = '
	<li>
		<a href="tel:'.$customer_service_cofnig['mobile_tel'].'">
		<div class="img_box">
			<i class="fas fa-phone-volume"></i>
		</div>		
		<div class="contacts_text_box">
			<p class="mb-0">'.$tr['customer service tel'].'</p>
			<p class="mb-0">'.$customer_service_cofnig['mobile_tel'].'</p>
		</div>
		</a>
	</li>
	';
}else{
	$service_tel = '';
}

//qr1
if( $customer_service_cofnig['contact_app_name'] != '' || $customer_service_cofnig['contact_app_id'] != '' || $customer_service_cofnig['qrcode_1'] != ''  ){
	if( $customer_service_cofnig['qrcode_1'] == '' ) {
		$qrcode_1_img = '';
	}else{
		$qrcode_1_img = '<img src="'.$customer_service_cofnig['qrcode_1'].'" alt="">';
	}
$qrcode_1_html = '
<li>
<div class="img_box">
	'.$qrcode_1_img.'
	<i class="fas fa-qrcode"></i>
</div>		
<div class="contacts_text_box">
	<p class="mb-0">'.$customer_service_cofnig['contact_app_name'].'</p>
	<p class="mb-0">'.$customer_service_cofnig['contact_app_id'].'</p>
</div>
</li>
';
}else{
	$qrcode_1_html = '';
}


//qr2
if( $customer_service_cofnig['contact_app_name_2'] != '' || $customer_service_cofnig['contact_app_id_2'] != '' || $customer_service_cofnig['qrcode_2'] != '' ){
	if( $customer_service_cofnig['qrcode_2'] == '' ) {
		$qrcode_2_img = '';
	}else{
		$qrcode_2_img = '<img src="'.$customer_service_cofnig['qrcode_2'].'" alt="">';
	}
	$qrcode_2_html = '
	<li>
	<div class="img_box">
		'.$qrcode_2_img.'
		<i class="fas fa-qrcode"></i>
	</div>		
	<div class="contacts_text_box">
		<p class="mb-0">'.$customer_service_cofnig['contact_app_name_2'].'</p>
		<p class="mb-0">'.$customer_service_cofnig['contact_app_id_2'].'</p>
	</div>
	</li>
	';
}else{
	$qrcode_2_html = '';
}

$contact_us_list = $contact_us_content.'
<ul class="contacts_list">
	'.$online_service.'
	'.$service_mail.'
	'.$service_tel.'
</ul>
<ul class="contacts_list">
	'.$qrcode_1_html.'
	'.$qrcode_2_html.'
</ul>
';

// $contact_us_list = $contact_us_content.'
// <ol>
// <li>'.$tr['click'].'<a href="#" onclick="window.open(\''.$customer_service_cofnig['online_weblink'].'\', \''.$tr['online customer service'] .'\', config=\'height=800,width=700\');">'.$tr['online customer service'] .'</a>'.$tr['contact the customer service'].' </li>
// <li>'.$tr['contact with other method'].'</li>
// </ol>
// <ul class="contacts_list">
// 	'.$online_service.'
// 	'.$service_mail.'
// 	'.$service_tel.'
// </ul>
// <ul class="contacts_list">
// 	'.$qrcode_1_html.'
// 	'.$qrcode_2_html.'
// </ul>
// ';

// $showtext_html = $contact_us_content.'
// <ol>
// <li>'.$tr['click'].'<a href="#" onclick="window.open(\''.$customer_service_cofnig['online_weblink'].'\', \''.$tr['online customer service'] .'\', config=\'height=800,width=700\');">'.$tr['online customer service'] .'</a>'.$tr['contact the customer service'].' </li>
// <li>'.$tr['contact with other method'].'</li>
// </ol>
// <hr>
// <strong>'.$tr['customer service'].'</strong>
// <ul>
// <li>'.$tr['customer service mail'].'：'.$customer_service_cofnig['email'].'</li>
// <li>'.$tr['customer service qq'].'：<a href="#" onclick="window.open(\'http://wpd.b.qq.com/page/webchat.html?nameAccount='.$customer_service_cofnig['qq'].'\', \'QQ\', config=\'height=500,width=500\');">'.$customer_service_cofnig['qq'].'</a></li>
// <li><p>'.$tr['customer service tel'].'：'.$customer_service_cofnig['mobile_tel'].'</p></li>
// </ul>
// ';


//'http://'.$config['website_domainname'].'/register.php?ra='.base64_encode($recommend_agent_account);
/*
 * $customer_service_cofnig['online_weblink'] = '12121212';
	$customer_service_cofnig['qq'] = 'gpk17QQ';
	$customer_service_cofnig['email'] = 'gpk1777mail@gpk17.com';
	$customer_service_cofnig['mobile_tel'] = '0987654321';
	$customer_service_cofnig['wechat_qrcode'] = 'WechatQRCode';
 */

//<a href="stationmessage.php"><span class="glyphicon glyphicon-headphones" aria-hidden="true"></span>&nbsp;線上客服</a>

//客服人員微信 QRCODE："'.$customer_service_list_sql_result[61]->value.'"

//JIGDEMO的客服中心全年无休，提供1周7天、每天24小时的优质服务。<br>
//如果您对本网站的使用有任何疑问，可以透过下列任一方式与客服人员联系，享受最实时的服务：<br></p>
//<p>(1)点击<a href="#">在线客服</a>连结，即可进入在线客服系统与客服人员联系。</p>
//<p>(2)您亦可使用Email或电话与客服人员取得联系：</p>



//$customer_service_html = $customer_service_html . '
//    <div class="well well-sm col-sm-12">
//      <strong>客服資訊</strong>
//    </div>
//    <table class="table table-hover">
//      <thead>
//        <th width="25%">欄位</th>
//        <th width="20%">內容</th>
//        <th width="55%">說明</th>
//      </thead>
//      <tbody>
//        <tr>
//          <td>On-line客服 web link</td>
//          <td>
//            <a href="#" id="'.$protal_setting_list_sql_result[57]->name.'" class="text-left edit_customer_service" data-type="text" data-pk="'.$protal_setting_list_sql_result[57]->id.'" data-title="修改On-line客服 web link">'.$protal_setting_list_sql_result[57]->value.'</a>
//          </td>
//          <td>-</td>
//        </tr>
//        <tr>
//          <td>客服資訊QQ</td>
//          <td>
//            <a href="#" id="'.$protal_setting_list_sql_result[58]->name.'" class="text-left edit_customer_service" data-type="text" data-pk="'.$protal_setting_list_sql_result[58]->id.'" data-title="修改客服資訊QQ">'.$protal_setting_list_sql_result[58]->value.'</a>
//          </td>
//          <td>-</td>
//        </tr>
//        <tr>
//          <td>客服資訊Email</td>
//          <td>
//            <a href="#" id="'.$protal_setting_list_sql_result[59]->name.'" class="text-left edit_customer_service" data-type="text" data-pk="'.$protal_setting_list_sql_result[59]->id.'" data-title="修改客服資訊Email">'.$protal_setting_list_sql_result[59]->value.'</a>
//          </td>
//          <td>-</td>
//        </tr>
//        <tr>
//          <td>客服資訊MobileTEL</td>
//          <td>
//            <a href="#" id="'.$protal_setting_list_sql_result[60]->name.'" class="text-left edit_customer_service" data-type="text" data-pk="'.$protal_setting_list_sql_result[60]->id.'" data-title="修改客服資訊MobileTEL">'.$protal_setting_list_sql_result[60]->value.'</a>
//          </td>
//          <td>-</td>
//        </tr>
//        <tr>
//          <td>客服資訊微信 QRCODE</td>
//          <td>
//            <a href="#" id="'.$protal_setting_list_sql_result[61]->name.'" class="text-left edit_customer_service" data-type="text" data-pk="'.$protal_setting_list_sql_result[61]->id.'" data-title="修改客服資訊微信 QRCODE">'.$protal_setting_list_sql_result[61]->value.'</a>
//          </td>
//          <td>-</td>
//        </tr>
//      </tbody>
//    </table>
//    ';


// 不論身份都可以觀看。


// 內容填入整理
// 切成 3 欄版面
$indexbody_content = $indexbody_content.$header_content.'
<div class="main_content contact_content">
	<div>
'.$contact_us_list.'
	</div>
</div>
<div class="row">
	<div class="col-md-10 offset-md-1 col-12">
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
$tmpl['sidebar_content'] = ['static','contactus'];
// banner標題
$tmpl['banner'] = ['Contact us'];
// menu增加active
$tmpl['menu_active'] =['contactus.php'];

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include($config['template_path']."template/static.tmpl.php");

?>