<?php
// ----------------------------------------------------------------------------
// Features:	多國語系的功能,可自動偵測目前瀏覽器預設的語系自動找尋該語言檔
// File Name:	language.php
// Author:		mtchang.tw@gmail.com
// Related:
// Log:
// 如果該語系檔案不存在，則以英文語系為預設語系。
// ----------------------------------------------------------------------------

// 支援多國語系 , 這段寫在 config.php 內
// require("./i18n/language.php");



// 偵測語言，如果有指定以指定為主，沒有的話以瀏覽器狀態決定。
$lang = NULL;
// 有 get 以 get為主
if(isset($_GET['lang'])) {
	$lang = filter_var($_GET['lang'], FILTER_SANITIZE_STRING);
	$_SESSION['lang'] = $lang;
}elseif(isset($_SESSION['lang'])){
	// 有 session 以 session 為主
	$lang = $_SESSION['lang'];
}else{
	// 都沒有,以 http 為主.  檢查，避免一些 rebot 的存取。
	/*
	if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		$lang = strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 5));
		//var_dump($_SERVER['HTTP_ACCEPT_LANGUAGE']);
	}else{
		$lang = 'zh-cn';
	}
	*/
	// 先以 zh-cn 為主, 否則測試有時候會失準 by mtchang 2017.11.1
	$valid_lang = array("zh-cn", "en-us", "zh-tw", "vi-vn", "id-id", "th-th", "ja-jp");
	// 如果該語系在支援的語系內的時候，就用該語系. 否則就用英語
	if (in_array($lang, $valid_lang, true)) {
		$_SESSION['lang'] = $lang;
	}else{
		$lang = 'zh-cn';
		$_SESSION['lang'] = $lang;
	}
}
//var_dump($lang);



// var_dump($_SERVER);
// 依據 $lang 的語系，指定檔名, 避免錯誤的對應.
// 如需要增加語系，請在下面增加 case 判斷條件
switch ($lang){
  case "en-us":
		$langfile = "en-us.php";
		$langfile_ui 	= "en-us_ui.php";
		break;
	case "zh-tw":
	  // 主要語言檔
		$langfile 		= "zh-tw.php";
		// UI 界面廣告專用語言檔
		$langfile_ui 	= "zh-tw_ui.php";
		break;
	case "zh-cn":
		$langfile = "zh-cn.php";
		$langfile_ui 	= "zh-cn_ui.php";
		break;
	case "vi-vn":
		$langfile = "vi-vn.php";
		$langfile_ui 	= "vi-vn_ui.php";
		break;
	case "id-id":
		$langfile = "id-id.php";
		$langfile_ui 	= "id-id_ui.php";
		break;
	case "ja-jp":
		$langfile = "ja-jp.php";
		$langfile_ui 	= "ja-jp_ui.php";
		break;			
	case "th-th":
		$langfile = "th-th.php";
		$langfile_ui 	= "th-th_ui.php";
		break;										
  default:
	  $langfile = "zh-cn.php";
	  $langfile_ui 	= "zh-cn_ui.php";
		break;
}
//var_dump($langfile);


// 如果語系檔檔案存在，先 load 該語系檔。
// 避免語系檔沒有寫到的翻譯變數，所以先 load  en_us.php
$i18_base = dirname(__FILE__) . '/';
include($i18_base . 'en-us.php');

// 並依據存在的檔案，變更為該語系.
$i18_ui_base = $config['template_path'].'i18n/';

// 載入主要語言檔
if (file_exists($i18_base.$langfile))
{
  include($i18_base.$langfile);
}else{
	$langfile = 'en-us.php';
	include($i18_base.$langfile);
}

// 讀取theme裡的補充主語言檔
if (file_exists($i18_ui_base.$langfile))
{
  include($i18_ui_base.$langfile);
}

// 載入預設的 UI 語言檔(根目錄) , 再載入UI裡面的自訂語言檔。
include($i18_base.$langfile_ui);
if (file_exists($i18_ui_base.$langfile_ui))
{
    include($i18_ui_base.$langfile_ui);
}

//
// 語系切換選單，寫成一個模組. 提供所有程式選單內使用
//
function menu_language_choice() {
	global $tr;

	// 紀錄當下的 URL, 切換LANG後可以 return 回到原本的網址
	$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443 || getenv('HTTP_X_FORWARDED_PROTO') === 'https') ? "https://" : "http://";
	$current_url = $protocol.$_SERVER['HTTP_HOST'];

	// 顯示目前的語言
	$lang = $_SESSION['lang'];
	switch ($lang){
	  case "en-us":
			$show_change_lang = 'Language';
			$show_change_lang_icon = 'English';
			break;
		case "zh-tw":
			$show_change_lang = '選擇語系';
			$show_change_lang_icon = '正體中文';
			break;
		case "zh-cn":
			$show_change_lang = '选择语系';
			$show_change_lang_icon = '简体中文';
			break;
		case "vi-vn":
			$show_change_lang = 'Ngôn ngữ';
			$show_change_lang_icon = 'Việt Nam';
			break;
		case "id-id":
			$show_change_lang = 'Bahasa';
			$show_change_lang_icon = 'Indonesia';
			break;
		case "th-th":
			$show_change_lang = 'ภาษา';
			$show_change_lang_icon = 'ไทย';
			break;	
		case "ja-jp":
			$show_change_lang = '言語';
			$show_change_lang_icon = '日本語';
			break;															
	  default:
			$show_change_lang = '选择语系';
			$show_change_lang_icon = '简体中文';
			break;
	}

	$qs = preg_split('/lang='.$lang.'/', $_SERVER['REQUEST_URI']);
	if(count($qs) > 1 ){
		$sub_url = '<a class="dropdown-item py-2" href="'.$current_url.$qs[0].'lang=en-us'.$qs[1].'" target="_SELF">English</a>
								<a class="dropdown-item py-2" href="'.$current_url.$qs[0].'lang=zh-cn'.$qs[1].'" target="_SELF">简体中文</a>
								<a class="dropdown-item py-2" href="'.$current_url.$qs[0].'lang=zh-tw'.$qs[1].'" target="_SELF">正體中文</a>
								<a class="dropdown-item py-2" href="'.$current_url.$qs[0].'lang=vi-vn'.$qs[1].'" target="_SELF">Việt Nam</a>
								<a class="dropdown-item py-2" href="'.$current_url.$qs[0].'lang=id-id'.$qs[1].'" target="_SELF">Indonesia</a>
								<a class="dropdown-item py-2" href="'.$current_url.$qs[0].'lang=th-th'.$qs[1].'" target="_SELF">ไทย</a>
								<a class="dropdown-item py-2" href="'.$current_url.$qs[0].'lang=ja-jp'.$qs[1].'" target="_SELF">日本語</a>																	
								';								
	}else{
		$qs = preg_split('/\?/', $qs[0]);
		if(count($qs) > 1 ){
		$sub_url = '<a class="dropdown-item py-2" href="'.$current_url.$qs[0].'?lang=en-us&'.$qs[1].'" target="_SELF">English</a>
								<a class="dropdown-item py-2" href="'.$current_url.$qs[0].'?lang=zh-cn&'.$qs[1].'" target="_SELF">简体中文</a>
								<a class="dropdown-item py-2" href="'.$current_url.$qs[0].'?lang=zh-tw&'.$qs[1].'" target="_SELF">正體中文</a>
								<a class="dropdown-item py-2" href="'.$current_url.$qs[0].'?lang=vi-vn&'.$qs[1].'" target="_SELF">Việt Nam</a>
								<a class="dropdown-item py-2" href="'.$current_url.$qs[0].'?lang=id-id&'.$qs[1].'" target="_SELF">Indonesia</a>
								<a class="dropdown-item py-2" href="'.$current_url.$qs[0].'?lang=th-th&'.$qs[1].'" target="_SELF">ไทย</a>
								<a class="dropdown-item py-2" href="'.$current_url.$qs[0].'?lang=ja-jp&'.$qs[1].'" target="_SELF">日本語</a>																		
								';
		}else{
			$sub_url = '<a class="dropdown-item py-2" href="'.$current_url.$qs[0].'?lang=en-us" target="_SELF">English</a>
									<a class="dropdown-item py-2" href="'.$current_url.$qs[0].'?lang=zh-cn" target="_SELF">简体中文</a>
									<a class="dropdown-item py-2" href="'.$current_url.$qs[0].'?lang=zh-tw" target="_SELF">正體中文</a>
									<a class="dropdown-item py-2" href="'.$current_url.$qs[0].'?lang=vi-vn" target="_SELF">Việt Nam</a>
									<a class="dropdown-item py-2" href="'.$current_url.$qs[0].'?lang=id-id" target="_SELF">Indonesia</a>
									<a class="dropdown-item py-2" href="'.$current_url.$qs[0].'?lang=th-th" target="_SELF">ไทย</a>
								  <a class="dropdown-item py-2" href="'.$current_url.$qs[0].'?lang=ja-jp" target="_SELF">日本語</a>																			
									';
		}
	}

  // 語系切換選單，寫成一個模組. 提供所有程式使用
  $menu_language_content = '
	<li class="nav-item dropdown-lang">
	<a href="#" title="'.$show_change_lang.'" class="nav-link dropdown-toggle pl-0" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
	  <span>'.$show_change_lang_icon.'</span>
	  <span class="caret"></span>
	  </a>
	  <div class="dropdown-menu">
		<div class="dropdown-item disabled lang-disabled-style">'.$show_change_lang.'</div>
		<div class="dropdown-divider"></div>
		'.$sub_url.'
	  </div>
	</li>'
	;

	return($menu_language_content);
}



function mobile_menu_language_choice() {
	global $tr;

	// 紀錄當下的 URL, 切換LANG後可以 return 回到原本的網址
	$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443 || getenv('HTTP_X_FORWARDED_PROTO') === 'https') ? "https://" : "http://";
	$current_url = $protocol.$_SERVER['HTTP_HOST'];

	// 顯示目前的語言
	$lang = $_SESSION['lang'];
	switch ($lang){
	  case "en-us":
			$show_change_lang = 'language';
			$show_change_lang_icon = 'English';
			break;
		case "zh-tw":
			$show_change_lang = '選擇語系';
			$show_change_lang_icon = '正體中文';
			break;
		case "zh-cn":
			$show_change_lang = '选择语系';
			$show_change_lang_icon = '简体中文';
			break;
		case "vi-vn":
			$show_change_lang = 'Ngôn ngữ';
			$show_change_lang_icon = 'Việt Nam';
			break;
		case "id-id":
			$show_change_lang = 'Bahasa';
			$show_change_lang_icon = 'Indonesia';
			break;
		case "th-th":
			$show_change_lang = 'ภาษา';
			$show_change_lang_icon = 'ไทย';
			break;
		case "ja-jp":
			$show_change_lang = '言語';
			$show_change_lang_icon = '日本語';
			break;																	
	  default:
			$show_change_lang = '选择语系';
			$show_change_lang_icon = '简体中文';
			break;
	}

	$qs = preg_split('/lang='.$lang.'/', $_SERVER['REQUEST_URI']);
	if(count($qs) > 1 ){
		$sub_url = '<a class="dropdown-item" href="'.$current_url.$qs[0].'lang=en-us'.$qs[1].'" target="_SELF">English</a>
								<a class="dropdown-item" href="'.$current_url.$qs[0].'lang=zh-cn'.$qs[1].'" target="_SELF">简体中文</a>
								<a class="dropdown-item" href="'.$current_url.$qs[0].'lang=zh-tw'.$qs[1].'" target="_SELF">正體中文</a>
								<a class="dropdown-item" href="'.$current_url.$qs[0].'lang=vi-vn'.$qs[1].'" target="_SELF">Việt Nam</a>
								<a class="dropdown-item"  href="'.$current_url.$qs[0].'lang=th-th'.$qs[1].'" target="_SELF">ไทย</a>
								<a class="dropdown-item"  href="'.$current_url.$qs[0].'lang=ja-jp'.$qs[1].'" target="_SELF">日本語</a>									
								';			
	}else{
		$qs = preg_split('/\?/', $qs[0]);
		if(count($qs) > 1 ){
		$sub_url = '<a class="dropdown-item" href="'.$current_url.$qs[0].'?lang=en-us&'.$qs[1].'" target="_SELF">English</a>
								<a class="dropdown-item" href="'.$current_url.$qs[0].'?lang=zh-cn&'.$qs[1].'" target="_SELF">简体中文</a>
								<a class="dropdown-item" href="'.$current_url.$qs[0].'?lang=zh-tw&'.$qs[1].'" target="_SELF">正體中文</a>
								<a class="dropdown-item" href="'.$current_url.$qs[0].'?lang=vi-vn&'.$qs[1].'" target="_SELF">Việt Nam</a>
								<a class="dropdown-item" href="'.$current_url.$qs[0].'?lang=id-id&'.$qs[1].'" target="_SELF">Indonesia</a>
								<a class="dropdown-item"  href="'.$current_url.$qs[0].'?lang=th-th&'.$qs[1].'" target="_SELF">ไทย</a>
								<a class="dropdown-item"  href="'.$current_url.$qs[0].'?lang=ja-jp&'.$qs[1].'" target="_SELF">日本語</a>																	
								';								
		}else{
			$sub_url = '<a class="dropdown-item" href="'.$current_url.$qs[0].'?lang=en-us" target="_SELF">English</a>
									<a class="dropdown-item" href="'.$current_url.$qs[0].'?lang=zh-cn" target="_SELF">简体中文</a>
									<a class="dropdown-item" href="'.$current_url.$qs[0].'?lang=zh-tw" target="_SELF">正體中文</a>
									<a class="dropdown-item" href="'.$current_url.$qs[0].'?lang=vi-vn" target="_SELF">Việt Nam</a>
									<a class="dropdown-item" href="'.$current_url.$qs[0].'?lang=id-id" target="_SELF">Indonesia</a>
									<a class="dropdown-item" href="'.$current_url.$qs[0].'?lang=th-th" target="_SELF">ไทย</a>
								  <a class="dropdown-item" href="'.$current_url.$qs[0].'?lang=ja-jp" target="_SELF">日本語</a>																			
								  ';																		
		}
	}

  // 語系切換選單，寫成一個模組. 提供所有程式使用
  $menu_language_content = '
  <i class="fa fa-globe mobile_language" aria-hidden="true"></i>
  <div class="dropdown">
  <button class="btn mobile_language_btn dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
  <span>'.$show_change_lang_icon.'</span>
  </button>

	<div class="dropdown-menu lang-disabled-style" aria-labelledby="dropdownMenuButton">
		<div class="dropdown-item disabled">'.$show_change_lang.'</div>
		'.$sub_url.'
	  </div>
   </div>  
   ';

	return($menu_language_content);
}
?>
