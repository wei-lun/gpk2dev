<?php
/*-----------------------------------------*/
//全民代理廣告圖
/*-----------------------------------------*/

function national_agent()
{
  global $config;
  global $cdnfullurl;
  global $cdnfullurl_js;
  global $protalsetting;

  $national_agent_isopen = $protalsetting['national_agent_isopen']??'off';
  //全民代理關閉
  if($national_agent_isopen == 'off'){
    return '';
  }
  //已是代理商
  if(isset($_SESSION['member']) && $_SESSION['member']->therole == 'A'){
    return '';
  }

  if(!isset($_SESSION['member'])){
    $url= $config['website_baseurl'].'login2page.php';
  }else{
    $url= $config['website_baseurl'].'allagent_register.php';
  }

  $national_section=<<<HTML
  	<div class="allagent"><a href="{$url}" role="button"><img class="allagent-img" src="{$cdnfullurl_js}img/promotion/allagent_promote.png" alt=""></a></div>
HTML;

  return $national_section;
}
?>
