<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 系統首頁
// File Name:	home.php
// Author:		Barkley
// Related:		index.html
// Log:
// 此頁由 index.html 為開門頁引導到此,目的為讓使用者需要透過點擊才可以到此頁
// 避免因為大流量的行銷, 無法負荷. index.html 靜態頁可以使用 CDN 分流。
// 此頁需要透過 url rewrite 改寫, 讓網址列看不到 home.php 避免被 user 加入我的最愛.
// ----------------------------------------------------------------------------




/*
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

// 移除暫存於 buffer 中的 html 指定內容
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
  // $buf = str_replace(array("\n","\r","\t"),'',$buf);
  $buf = str_replace(array("\n","\r", "\t"),'',$buf);
  // 沒有 cache 把這次的 html buffer 存入 memcache , timeout 120s
  $buf = save_memcache('home_node', $buf);

  return $buf;
}

// 開始 buffer html 的輸出
ob_start("ob_html_compress");
//var_dump($_SERVER);

// 檢查 memcache 是否有 cache 可以使用 , 有的話撈出來用
$buf = check_memcache('home_node');
if( $buf != false) {
  echo $buf;
  ob_end_flush();
  die('cached');
  // 結束, 底下無須執行。直到 timeout
}
// -----------------------------------------------------------------------------
// memcache check end
// -----------------------------------------------------------------------------
*/

// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";
//UI樣式(BANNER...)
$template_name ='home';
//require_once $config['template_path']."template/ui.php";

// var_dump($_SESSION);


// ----------------------------------------------------------------------------
// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------


// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
$function_title = $tr['home title'];
// 擴充 head 內的 css or js
$extend_head				= '';
// 放在結尾的 js
$extend_js					= '';
// body 內的主要內容
$indexbody_content	= '';
// 系統訊息選單
$messages 					= '';
//
$banner='';
$lobby_home_html='';
// ----------------------------------------------------------------------------

$home_type = '';

if(isset($_GET['a'])){
  $home_types = filter_var($_GET['a'], FILTER_SANITIZE_STRING);
  switch($home_types){
    case 'clothing':
      $home_type = '_clothing';
      break;
    case 'edu':
      $home_type = '_edu';
      break;
    case 'food':
      $home_type = '_food';
      break;
    case 'fun':
      $home_type = '_fun';
      break;
    case 'live':
      $home_type = '_live';
      break;
    case 'travel':
      $home_type = '_travel';
      break;
    default:
      $home_type = '';
  }
}

if(isset($ui['Banner_home'.$home_type]))
$banner = $ui['Banner_home'.$home_type];

// 首頁廣告
if(isset($ui['Block_home'.$home_type]))
$lobby_home_html = $ui['Block_home'.$home_type];

/*
$lobby_home_html = '<div class="container" style="background: rgba(0,0,0,.4);">


<div class="row" style="height:50px;">
	<div class="col-xs-1 col-md-1">
	</div>
  <div class="col-xs-3 col-md-3">
		<img width="180" src="'.$cdnrooturl.'microgaming_logo.png" alt="Microgaming Software">
  </div>
  <div class="col-xs-8 col-md-8">
  </div>
</div>';

$lobby_home_html = $lobby_home_html.'
  <div class="thumbnail">
    <a href="lobby_mggameh5.php" target="_BLANK"><img src="'.$cdnrooturl.'mg_slot_1.jpg" alt="Microgaming"></a>
    <div class="caption">
      <p align="center">
      <a href="lobby_mggameh5.php" class="btn btn-primary" role="button" target="_BLANK">PLAY</a>
      <a href="lobby_mggameh5.php" class="btn btn-default" role="button" target="_BLANK">TRY</a>
      </p>
    </div>
  </div>
';

$lobby_home_html = $lobby_home_html.'
  <div class="thumbnail">
    <a href="lobby_mggameh5.php" target="_BLANK"><img src="'.$cdnrooturl.'mg_slot_2.jpg" alt="Microgaming"></a>
    <div class="caption">
      <p align="center">
      <a href="lobby_mggameh5.php" class="btn btn-primary" role="button" target="_BLANK">PLAY</a>
      <a href="lobby_mggameh5.php" class="btn btn-default" role="button" target="_BLANK">TRY</a>
      </p>
    </div>
  </div>
';

$lobby_home_html = $lobby_home_html.'
  <div class="thumbnail">
    <a href="lobby_mggameh5.php" target="_BLANK"><img src="'.$cdnrooturl.'mg_slot_3.jpg" alt="Microgaming"></a>
    <div class="caption">
      <p align="center">
      <a href="lobby_mggameh5.php" class="btn btn-primary" role="button" target="_BLANK">PLAY</a>
      <a href="lobby_mggameh5.php" class="btn btn-default" role="button" target="_BLANK">TRY</a>
      </p>
    </div>
  </div>
';

$lobby_home_html = $lobby_home_html.'
  <div class="thumbnail">
    <a href="lobby_mggameh5.php" target="_BLANK"><img src="'.$cdnrooturl.'mg_slot_4.jpg" alt="Microgaming"></a>
    <div class="caption">
      <p align="center">
      <a href="lobby_mggameh5.php" class="btn btn-primary" role="button" target="_BLANK">PLAY</a>
      <a href="lobby_mggameh5.php" class="btn btn-default" role="button" target="_BLANK">TRY</a>
      </p>
    </div>
  </div>
</div>
';*/



// 變換圖片的作法 , 說明範例。以後如果需要不同界面可以修改。
$extend_head = '';

/*

.btn-default {
    background-color: #999;
    border-color: #999;
    color: #333;
}

*/

// Transferout_Casino_MG2_balance() and Retrieve_Casino_MG2_balance()
// 執行完成後，把 $_SESSION['wallet_transfer'] 變數清除, 其他程式才可以進入。
// 避免連續性呼叫 Retrieve_Casino_MG2_balance() lib , 需要到 home.php 清除變數才可以。
if(isset($_SESSION['wallet_transfer'])) {
	unset($_SESSION['wallet_transfer']);
}


// 內容填入整理
// 切成 3 欄版面
$indexbody_content = $indexbody_content.'

'.$lobby_home_html.'

<div class="row">
	<div class="col-xs-2 col-md-2">
	</div>
	<div class="col-xs-8 col-md-8">
		<div id="preview"></div>
	</div>
	<div class="col-xs-2 col-md-2">
	</div>
</div>

';

//game menu
$extend_js =<<<HTML
  <script>
    $(document).ready(function(){
      var navlingth = $('#gNavi li').length;
      if( navlingth == 2 ){
        $('#gNavi li').addClass('widthactive');
      }
    });
  </script>
HTML;
// ----------------------------------------------------------------------------
// 準備填入的內容
// ----------------------------------------------------------------------------
// 將內容塞到 html meta 的關鍵字, SEO 加強使用
$tmpl['html_meta_description'] 		= $config['companyShortName'];
$tmpl['html_meta_author']	 				= $config['companyShortName'];
$tmpl['html_meta_title'] 					= $function_title.'-'.$config['companyShortName'];
$tmpl['banner']                   = $banner;
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
//include($config['template_path']."template/home.tmpl.php");
// include($config['template_path']."template/lobby_mggame.tmpl.php");
//include($config['template_path']."template/static.tmpl.php");

/*
// 輸出緩衝區內容並關閉緩衝
ob_end_flush();
*/
?>
