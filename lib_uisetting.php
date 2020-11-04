<?php
// ----------------------------------------------------------------------------
// Features:  ui設定 function
// File Name: 
// Author:     orange
// Related: 
// DB Table:
// Log:
// ----------------------------------------------------------------------------

function data_decode(&$input){
  foreach ($input as $key => $value) {
      if(is_array($input[$key])){
        data_decode($input[$key]);
      }else{
          $input[$key] = urldecode($value);
        }
    }
}

//取得maindomain的uisetting
function get_domainbase_uidata(){
  global $config;
  if(!isset($config['stylesettingid'])){
    return [];
  }

  $site_stylesetting_result = runSQLall('SELECT * FROM site_stylesetting WHERE id = '.$config['stylesettingid'].';');
  if($site_stylesetting_result[0]==0){
  	return [];
  }
  $domainbase_ui_data = json_decode($site_stylesetting_result[1]->jsondata,true);
  data_decode($domainbase_ui_data);

  return $domainbase_ui_data;
}

//將maindomain資料中 switch關閉的部分移除
function domain_data_merge($default,$input,&$output){  
  if(empty($input)){
    $output = array_merge((array) $default, (array) $input);
  }else{
    $output = (array) $input;
  }
  foreach ($default as $key => $value) {
    if(is_array($default[$key]) && !is_int($key)){
      $input[$key] = empty($input[$key])? []:$input[$key];      
      domain_data_merge($default[$key],$input[$key],$output[$key]);
    }else{
      if(isset($output[$key]['switch'])&&$output[$key]['switch']==0){
        unset($output[$key]);
      }
    }
  }
}
// 將文案管理中 無資料(空)的部分移除
function copy_check(&$input){
  foreach ($input as $key => $value) {
    if(!is_string($input[$key])){
      copy_check($input[$key]);
    }elseif($value==''){
      unset($input[$key]);
    }
  }
}

//uisetting資料取得與處理
function get_uisetting(){
  global $config;
  $ui_data = [];
  $domainbase_ui_data = get_domainbase_uidata();
  $ui_link = 'uisetting_action.php';
  //json預設文件讀取
  $json_string = file_get_contents(dirname(__FILE__)."/in/component_ui.json");
  $json_string_data = json_decode($json_string,true);
  
  if(isset($config['component'])){
    $site_stylesetting_result = runSQLall('SELECT * FROM site_stylesetting WHERE id = '.$config['component'].';');
    if($site_stylesetting_result[0]==1){
      if($site_stylesetting_result[1]->open == 1){
        $ui_data = json_decode($site_stylesetting_result[1]->jsondata,true);
        data_decode($ui_data);
        $ui_link = 'uisetting_action.php?sid='.$config['component'];
      }
    }
  }

  //最新版本json與舊有資料merge(確保資料結構為最新版本)     
  $s_ui_data = array_replace_recursive($json_string_data,$ui_data);
  copy_check($s_ui_data['copy']);

  $d_ui_data = null;
  domain_data_merge($json_string_data,$domainbase_ui_data,$d_ui_data);
  copy_check($d_ui_data['copy']);

  $output = $s_ui_data;

  //文案管理優先順  domain<subdomain
  $output['copy'] = array_replace_recursive($d_ui_data['copy'],$s_ui_data['copy']);
  //廣告元件(home static gamelobby)優先順 domain>subdomain
  $output['home'] = array_replace_recursive($s_ui_data['home'],$d_ui_data['home']);
  $output['static'] = array_replace_recursive($s_ui_data['static'],$d_ui_data['static']);
  $output['gamelobby'] = array_replace_recursive($s_ui_data['gamelobby'],$d_ui_data['gamelobby']);
  //carousel轮播式横幅元件 優先順 domain + subdomain合併出現
  $output['index_carousel'] = array_merge_recursive($d_ui_data['index_carousel'],$s_ui_data['index_carousel']);
  $output['lobby_carousel'] = array_merge_recursive($d_ui_data['lobby_carousel'],$s_ui_data['lobby_carousel']);

  // var_dump(json_encode($s_ui_data['home']));
  // echo('</br>');
  // var_dump(json_encode($output));
  // echo('</br>');
  // var_dump(json_encode($ui_data_component['home']));
  // die();

  return ['ui_link'=>$ui_link,'ui_data'=>$output];
}