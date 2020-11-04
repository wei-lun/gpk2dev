<?php

//客製化menu
$ui['custom_menu']='';
if(isset($ui_data["custom_menu"])){	
	if($template != "admin" AND $template != "member" AND $template != "login2page")
	{  
	  for ($i=0; $i < count($ui_data["custom_menu"]); $i++) { 
	  	if(!isset($ui_data["custom_menu"][$i]['icon']))
		  	$ui_data["custom_menu"][$i]['icon']='fas fa-star';
	    $ui['custom_menu'] .= '<li class="nav-item navi_more"><a class="nav-link" href="'.$ui_data["custom_menu"][$i]["link"].'" target="'.$ui_data["custom_menu"][$i]["target"].'"><i class="'.$ui_data["custom_menu"][$i]['icon'].' mr-1"></i>'.$ui_data["custom_menu"][$i]["title"].'</a></li>';
	  }
	}
}

//客製化footer
$ui['footer_casino_logo'] ='';
$ui['footer_payment_logo'] ='';
$ui['footer_link'] ='';
if(isset($ui_data["footer_menu"])){	
	for ($i=0; $i < count($ui_data["footer_menu"]["footer_casino_logo"]); $i++) { 
	  $ui['footer_casino_logo'] .= '<li class="footer_casino_logo"><img src="'.$cdnfullurl_js."img/icon/foo_casino/".$ui_data["footer_menu"]["footer_casino_logo"][$i].'.png" alt=""></li>';
	}	
	for ($i=0; $i < count($ui_data["footer_menu"]["footer_payment_logo"]); $i++) { 
	  $ui['footer_payment_logo'] .= '<li class="footer_payment_logo"><img src="'.$cdnfullurl_js."img/icon/foo_pay/".$ui_data["footer_menu"]["footer_payment_logo"][$i].'.png" alt=""></li>';
	}	
	for ($i=0; $i < count($ui_data["footer_menu"]["footer_link"]); $i++) { 
		if(!isset($ui_data["footer_menu"]["footer_link"][$i]['icon']))
		  	$ui_data["footer_menu"]["footer_link"][$i]['icon']='fas fa-star';
	  $ui['footer_link'] .= '<li class="footer_link"><a href="'.$ui_data["footer_menu"]["footer_link"][$i]["link"].'" target="'.$ui_data["footer_menu"]["footer_link"][$i]["target"].'"><i class="'.$ui_data["footer_menu"]["footer_link"][$i]['icon'].' mr-1"></i>'.$ui_data["footer_menu"]["footer_link"][$i]["title"].'</a></li>';
	}
}
//客製化手機menu
$ui['mobile_custom_menu']='';
if(isset($ui_data["mobile"])){	
	if($template != "admin" AND $template != "member" AND $template != "login2page")
	{  
	  for ($i=0; $i < count($ui_data["mobile"]["mobile_morelink"]); $i++) { 
	  	if(!isset($ui_data['mobile']['mobile_morelink'][$i]['icon']))
	  		$ui_data['mobile']['mobile_morelink'][$i]['icon']='fas fa-star';
	    $ui['mobile_custom_menu'] .=<<<HTML
	    <a target="{$ui_data['mobile']['mobile_morelink'][$i]['target']}" href="{$ui_data['mobile']['mobile_morelink'][$i]['link']}" class="nbox">
      	<div class="icon"><i class="{$ui_data['mobile']['mobile_morelink'][$i]['icon']}"></i></div>
      	<span class="title">{$ui_data["mobile"]["mobile_morelink"][$i]["title"]}</span></a>
HTML;
	  }
	}
}

//易記網址
$ui['custom_short_url']='';
if(isset($ui_data['short_url'])){
	for ($i=0; $i < count($ui_data['short_url']); $i++) {
		$ui['custom_short_url'] .= $ui_data['short_url'][$i];
	}
}
function menu_short_url($device='pc'){
	global $tr;
	global $ui;
	$html_pc =<<<HTML
	<li class="ml-2 short_url">{$tr['easy URL']}：{$ui['custom_short_url']}</li>
HTML;

	$html_mobile =<<<HTML
	<div class="domain w-100 mb-2"><div class="ezurl font-weight-bold text-center">{$ui['custom_short_url']}</div></div>
HTML;
	if($ui['custom_short_url']==''){
		$html_pc='';
		$html_mobile='';
	}
	if($device=='pc')
		return $html_pc;
	else
		return $html_mobile;
}

?>