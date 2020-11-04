<?php
//---------------------------------------------------------------------
// language選單
//---------------------------------------------------------------------
function menu_language_flag_ver() {
  global $tr;
  global $cdnfullurl_js;

  // 紀錄當下的 URL, 切換LANG後可以 return 回到原本的網址
  if($_SERVER['SERVER_PORT'] == 443)  {
    $current_url = 'https://'.$_SERVER['HTTP_HOST'];
  }elseif($_SERVER['SERVER_PORT'] == 80) {
    $current_url = 'https://'.$_SERVER['HTTP_HOST'];
  }else{
    $current_url = 'http://'.$_SERVER['HTTP_HOST'].':'.$_SERVER['SERVER_PORT'];
  }

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
	  default:
			$show_change_lang = '选择语系';
			$show_change_lang_icon = '简体中文';
			break;
  }

  $qs = preg_split('/lang='.$lang.'/', $_SERVER['REQUEST_URI']);
  if(count($qs) > 1 ){
    $sub_url = '<li>'.$tr['Language'].'：</li>
                <li><a href="'.$current_url.$qs[0].'lang=en-us'.$qs[1].'" target="_SELF"><img src="'.$cdnfullurl_js.'img/common/en-us.png"/></a></li>
                <li><a href="'.$current_url.$qs[0].'lang=zh-cn'.$qs[1].'" target="_SELF"><img src="'.$cdnfullurl_js.'img/common/zh-cn.png"/></a></li>
                <li><a href="'.$current_url.$qs[0].'lang=zh-tw'.$qs[1].'" target="_SELF"><img src="'.$cdnfullurl_js.'img/common/zh-tw.png"/></a></li>
                <li><a href="'.$current_url.$qs[0].'lang=vi-vn'.$qs[1].'" target="_SELF"><img src="'.$cdnfullurl_js.'img/common/vi-vn.png"/></a></li>
                <li><a href="'.$current_url.$qs[0].'lang=id-id'.$qs[1].'" target="_SELF"><img src="'.$cdnfullurl_js.'img/common/id-id.png"/></a></li>
                <li><a href="'.$current_url.$qs[0].'lang=th-th'.$qs[1].'" target="_SELF"><img src="'.$cdnfullurl_js.'img/common/th-th.png"/></a></li>
                ';
  }else{
    $qs = preg_split('/\?/', $qs[0]);
    if(count($qs) > 1 ){
    $sub_url = '<li>'.$tr['Language'].'：</li>
                <li><a href="'.$current_url.$qs[0].'?lang=en-us&'.$qs[1].'" target="_SELF"><img src="'.$cdnfullurl_js.'img/common/en-us.png"/></a></li>
                <li><a href="'.$current_url.$qs[0].'?lang=zh-cn&'.$qs[1].'" target="_SELF"><img src="'.$cdnfullurl_js.'img/common/zh-cn.png"/></a></li>
                <li><a href="'.$current_url.$qs[0].'?lang=zh-tw&'.$qs[1].'" target="_SELF"><img src="'.$cdnfullurl_js.'img/common/zh-tw.png"/></a></li>
                <li><a href="'.$current_url.$qs[0].'?lang=vi-vn&'.$qs[1].'" target="_SELF"><img src="'.$cdnfullurl_js.'img/common/vi-vn.png"/></a></li>
                <li><a href="'.$current_url.$qs[0].'?lang=id-id&'.$qs[1].'" target="_SELF"><img src="'.$cdnfullurl_js.'img/common/id-id.png"/></a></li>
                <li><a href="'.$current_url.$qs[0].'?lang=th-th&'.$qs[1].'" target="_SELF"><img src="'.$cdnfullurl_js.'img/common/th-th.png"/></a></li>
                ';
    }else{
      $sub_url = '<li>'.$tr['Language'].'：</li>
                  <li><a href="'.$current_url.$qs[0].'?lang=en-us" target="_SELF"><img src="'.$cdnfullurl_js.'img/common/en-us.png"/></a></li>
                  <li><a href="'.$current_url.$qs[0].'?lang=zh-cn" target="_SELF"><img src="'.$cdnfullurl_js.'img/common/zh-cn.png"/></a></li>
                  <li><a href="'.$current_url.$qs[0].'?lang=zh-tw" target="_SELF"><img src="'.$cdnfullurl_js.'img/common/zh-tw.png"/></a></li>
                  <li><a href="'.$current_url.$qs[0].'?lang=vi-vn" target="_SELF"><img src="'.$cdnfullurl_js.'img/common/vi-vn.png"/></a></li>
                  <li><a href="'.$current_url.$qs[0].'?lang=id-id" target="_SELF"><img src="'.$cdnfullurl_js.'img/common/id-id.png"/></a></li>
                  <li><a href="'.$current_url.$qs[0].'?lang=th-th" target="_SELF"><img src="'.$cdnfullurl_js.'img/common/th-th.png"/></a></li>                  
                  ';
    }
  }

  // 語系切換選單，寫成一個模組. 提供所有程式使用
  $menu_language_content = '
  <ul class="menu_language">
    '.$sub_url.'    
  </ul>'
  ;

  return($menu_language_content);
}
?>