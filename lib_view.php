<?php
// ----------------------------------------------------------------------------
// Features :	前台 -- view render utils
// File Name: lib_view.php
// Author   : Dright
// Related  :
// Log      :
// ----------------------------------------------------------------------------
// 功能說明
// utility functions to render page
//
// functions to render:
//  1. render($view, $render_data)
//  2. render404()
//  3. render403()
//
// functions use in views:
//  1. use_layout($layout)
//  2. begin_section($section_name)
//  3. end_section()
//  4. include_partial($partial, $partial_data)
//
// example:
// agencyarea_preferential_detail.php
// agencyarea_preferential_detail.view.php
//

// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";


/**
 * sections in template
 * @var array
 */
$tmpl = [
  'html_meta_description' => $tr['host_descript'],
  'html_meta_author'	 		=> $tr['host_author'],
  'html_meta_title' 			=> '',
  'messages' 					    => '',
  'extend_head'				    => '',
  'extend_js'					    => '',
  'paneltitle_content'    => '',
  'panelbody_content'     => '',
];

/**
 * use by helper functions
 * @var string
 */
$lib_view_cur_layout = '';

/**
 * use by helper functions
 * @var string
 */
$lib_view_cur_section_name = '';



/**
 *  helper functions used in view
 *
 * example: agencyarea_preferential_detail.view.php
 */

// set layout. ex: admin.tmpl.php
function use_layout($layout) {
  $GLOBALS['lib_view_cur_layout'] = $layout;
}

// begin of section
function begin_section($section_name) {
  $GLOBALS['lib_view_cur_section_name'] = $section_name;
  ob_start();
}

// end of section
function end_section() {
  global $tmpl;
  global $lib_view_cur_section_name;

  if(empty($lib_view_cur_section_name)) return;

  $tmpl[$lib_view_cur_section_name] = ob_get_clean();
  $GLOBALS['lib_view_cur_section_name'] = '';
}

// include partial view
function include_partial($partial, $partial_data = []) {
  global $config;
  global $cdnfullurl_js;
  global $cdnfullurl;
  global $cdn4gamesicon;
  global $tr;
  global $ui_link;  

  extract($partial_data);

  ob_start();
  include $partial;
  ob_end_flush();
}

/**
 *  end of helper functions
 */



/**
 * render by view
 * @param  [string] $view      [file name of view]
 * @param  array  $render_data [data passing to view]
 * @return view                [render result]
 */
function render($view, $render_data = []) {
  global $config;
  global $cdnfullurl_js;
  global $cdnfullurl;
  global $cdn4gamesicon;
  global $tr;
  global $tmpl;
  global $lib_view_cur_layout;
  global $ui_link;
  global $customer_service_cofnig;

  extract($render_data);

  ob_start();
  include $view;

  if(! empty($lib_view_cur_layout)) {

    ob_clean();
    include $lib_view_cur_layout;
    $lib_view_cur_layout = '';
  }
  ob_end_flush();
}



/**
 * render 404 page
 * @return view
 */
function render404() {
  http_response_code(404);

  ob_start();
  include __DIR__ . '/error/404.html';
  ob_end_flush();
}


/**
 * render 403 page
 * @return view
 */
function render403() {
  http_response_code(403);

  ob_start();
  include __DIR__ . '/error/403.html';
  ob_end_flush();
}

?>
