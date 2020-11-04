<?php
// ----------------------------------------------------------------------------
// Features:	系統首頁, 意圖當成本網站的 route 入口管制
// File Name:	index.php
// Author:		Barkley
// Related:
// Log:
// ----------------------------------------------------------------------------



// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";
//UI樣式(BANNER...)
$template_name ='home';
//require_once $config['template_path']."template/ui.php";


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
$function_title 		= '首页';
// 擴充 head 內的 css or js
$extend_head				= '';
// 放在結尾的 js
$extend_js					= '';
// body 內的主要內容
$indexbody_content	= '';
// 系統訊息選單
$messages 					= '';
// banner
$banner             = '';
// 初始化變數 end
// ----------------------------------------------------------------------------
// 導覽列
$navigational_hierarchy_html = '
<ul class="breadcrumb">
  <li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
  <li class="active">'.$function_title.'</li>
</ul>
';
// ----------------------------------------------------------------------------

//var_dump($_SERVER);
//var_dump($_SESSION);
//var_dump($websiteconf);

// 偵測裝置類型
$config['website_baseurl'] = clientdevice_detect(1,0)['url'];

// 依据装置的不同, 切换UI, 达成 mobile and desktop 的不同UI
$extend_head = $extend_head.'<script>window.location.href = "'.$config['website_baseurl'].'home.php";</script>';
/*

if(isset($_SERVER['QUERY_STRING'])) {
  $QUERY_STRING = $_SERVER['QUERY_STRING'];
}else{
  $QUERY_STRING = 'default_web';
}

// ----
function check_memcache($key) {
  // 加上 memcached 加速,宣告使用 memcached 物件 , 連接看看. 不能連就停止.
  $memcache = new Memcached();
  $memcache->addServer('localhost', 11211) or die ("Could not connect memcache server !! ");
  // 把 http query 存成一個 key in memcache
  //$key = 'home_node';
  $key_alive_show = sha1($key);

  // 取得看看記憶體中，是否有這個 key 存在, 有的話直接抓 key
  $getfrom_memcache_result = $memcache->get($key_alive_show);
  if(!$getfrom_memcache_result) {
    // 不存在 key , 存 buffer
    $buf = false;
  }else{
  	// 資料有存在記憶體中，直接取得 get from memcached
  	$buf = $getfrom_memcache_result;
  }
  return $buf;
}
// ----

// ----
function save_memcache($key, $buffer) {
  // 加上 memcached 加速,宣告使用 memcached 物件 , 連接看看. 不能連就停止.
  $memcache = new Memcached();
  $memcache->addServer('localhost', 11211) or die ("Could not connect memcache server !! ");
  // 把 http query 存成一個 key in memcache
  //$key = 'home_node';
  $key_alive_show = sha1($key);

  // 取得看看記憶體中，是否有這個 key 存在, 有的話直接抓 key
  $getfrom_memcache_result = $memcache->get($key_alive_show);
  if(!$getfrom_memcache_result) {
    // 不存在 key , 除存 buffer
    // save to memcached ref:http://php.net/manual/en/memcached.set.php
    $memcached_timeout = 120;
    $memcache->set($key_alive_show, $buffer, time()+$memcached_timeout) or die ("Failed to save data at the memcache server");
    //echo "Store data in the cache (data will expire in $memcached_timeout seconds)<br/>\n";
    $buf = false;
  }else{
  	// 資料有存在記憶體中，直接取得 get from memcached
  	$buf = $getfrom_memcache_result;
  }

  return $buf;
}
// ----



// 移除暫存於 buffer 中的 html 指定內容 , 輸出內容前過濾 compress html code
function ob_html_compress($buf){
  // compress html code
  $search = array(
      '/\>[^\S ]+/s',     // strip whitespaces after tags, except space
      '/[^\S ]+\</s',     // strip whitespaces before tags, except space
      '/(\s)+/s',         // shorten multiple whitespace sequences
      '/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/'  // Remove HTML comments
  );
  $replace = array(
      '>',
      '<',
      '\\1',
      ''
  );
  $buf = preg_replace($search, $replace, $buf);
  // 移除空白 tab 斷行
  $buf = str_replace(array("\n","\r","\t"),'',$buf);
  // 沒有 cache 把這次的 html buffer 存入 memcache , timeout 120s
  global $QUERY_STRING;
  $buf = save_memcache($QUERY_STRING, $buf);
  return $buf;
}



// 開始 buffer html 的輸出
ob_start("ob_html_compress");
//var_dump($_SERVER);
// 檢查 memcache 是否有 cache 可以使用 , 有的話撈出來用
$buf = check_memcache($QUERY_STRING);
if( $buf != false) {
  echo $buf;
  ob_end_flush();
die("cached $QUERY_STRING");
// 結束, 底下無須執行。直到 timeout
}
// -----------------------------------------------------------------------------
// memcache check end
// -----------------------------------------------------------------------------
*/
//
// var_dump($_SERVER);

//$filename = 'default.html';
/*
try {

  if($_SERVER['QUERY_STRING'] == '') {
    $QUERY_STRING = 'home';
    $php_script = '/'.$QUERY_STRING.'.php';
    $filename  = dirname(__FILE__) ."$php_script";
  }

  if($e = file_exists($filename)) {
    require_once $filename;
  }
  //throw new Exception($e);
} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
    echo "<p>URL error!!  $QUERY_STRING  </p>";
}
*/

// echo '<script>window.location.href = "home.php";</script>';

//var_dump($_SERVER);
/*
ob_end_flush();
*/

// ----------------------------------------------------------------------------
// 準備填入的內容
// ----------------------------------------------------------------------------
// 將內容塞到 html meta 的關鍵字, SEO 加強使用
$tmpl['html_meta_description'] 		= $config['companyShortName'];
$tmpl['html_meta_author']	 				= $config['companyShortName'];
$tmpl['html_meta_title'] 					= $function_title.'-'.$config['companyShortName'];
$tmpl['banner']									  = $banner;
// 系統訊息顯示
$tmpl['message']									= $messages;
// 擴充再 head 的內容 可以是 js or css
$tmpl['extend_head']							= $extend_head;
// 擴充於檔案末端的 Javascript
$tmpl['extend_js']								= $extend_js;
// 主要內容 -- title
$tmpl['paneltitle_content'] 			= '<span class="glyphicon glyphicon-bookmark" aria-hidden="true"></span>'.$function_title;
// 主要內容 -- content
$tmpl['panelbody_content']				= $indexbody_content;


// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
require_once $config['template_path']."template/home.tmpl.php";


 ?>
