<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 公告點擊後彈出公告全文視窗
// File Name:	announcement_fullread.php
// Author:		Yuan , Barkley
// Related:   服務 uiphp 顯示完整公告內容
// Table :
// Log:
// ----------------------------------------------------------------------------



// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
// require_once dirname(__FILE__) ."/lib.php";


// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
//announcement_fullread.php
// $tr['announcement_fullread title']= '跑馬燈公告全文觀看';
$function_title 		= $tr['announcement_fullread title'];
// 擴充 head 內的 css or js
$extend_head				= '';
// 放在結尾的 js
$extend_js					= '';
// body 內的主要內容
$indexbody_content	= '';
// 系統訊息選單
$messages 					= '';
// ----------------------------------------------------------------------------

$title_html = '<h3>公告</h3>';
$content_html = '<p>'.$tr['can not find announcement '].'</p>';
$btn_html = '<button onclick="window.close();" class="btn btn-default" type="submit">我知道了</button>';

if (isset($_GET['a']) && !empty($_GET['a'])) {
  $a = base64_decode($_GET['a']);
  $id = filter_var($a, FILTER_SANITIZE_STRING);

  if (!empty($id)) {
    $sql = "SELECT * FROM root_announcement WHERE status = '1' AND now() < endtime AND effecttime < now() AND id='$id' ORDER BY id LIMIT 1;";
    $result = runSQLall($sql,0,'r');

    // $tr['off'] = '關閉';

    if(!empty($result[0])) {
      $title_html = '<h5><span class="mr-2">['.date("Y-m-d", strtotime($result[1]->effecttime)).']</span>'.$result[1]->title;'</h5>';
      $content_html = '<p>'.htmlspecialchars_decode($result[1]->content).'</p>';
      $btn_html = '<button onclick="history.back();" class="btn btn-default">更多公告</button>';
    }
  }

} else {
  $announcement_btn_html = '';

  $sql = "SELECT * FROM root_announcement WHERE status = '1' AND now() < endtime AND effecttime < now() ORDER BY id;";
  $result = runSQLall($sql,0,'r');

  if (!empty($result[0])) {
    unset($result[0]);

    foreach ($result as $k => $v) {
      $base64_code = base64_encode($v->id);
      $announcement_title = htmlspecialchars_decode($v->title);
      $announcement_btn_html .= <<<HTML
      <a href="./announcement_fullread.php?a={$base64_code}" class="list-group-item">{$announcement_title}</a>
HTML;
    }

    $content_html = <<<HTML
      <div class="list-group">
        {$announcement_btn_html}
      </div>
HTML;
  }
}


$indexbody_content = <<<HTML
<div class="row"> 
<div class="col-12">
  <h3>{$title_html}</h3>
  <hr>
  {$content_html}
</div>
</div>
<hr>
<div class="row">
<div class="col-12 col-md-12 text-center">
  {$btn_html}
</div>
</div>
HTML;


// ----------------------------------------------------------------------------
// 準備填入的內容
// ----------------------------------------------------------------------------
// 將內容塞到 html meta 的關鍵字, SEO 加強使用
$tmpl['html_meta_description'] 		= $config['companyShortName'];
$tmpl['html_meta_author']	 				= $config['companyShortName'];
$tmpl['html_meta_title'] 					= $function_title.'-'.$config['companyShortName'];

// 系統訊息顯示
$tmpl['message']									= $indexbody_content;
// 擴充再 head 的內容 可以是 js or css
$tmpl['extend_head']							= $extend_head;
// 擴充於檔案末端的 Javascript
$tmpl['extend_js']								= $extend_js;

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
// include($config['template_path']."template/static.tmpl.php");

// ----------------------------------------------------------------------------
// 有變數才執行，沒有變數就是不正常進入此 tmpl 檔案
if(isset($tmpl)) {
	// 正常
}else{
	die('ERROR tmpl');
}


?>

<!DOCTYPE html>
<html lang="en">
  <head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1, minimum-scale=1, maximum-scale=1">
	<link rel="shortcut icon" href="<?php echo $config['companyFavicon'] ?>">
	<meta name="description" content="<?php echo $tmpl['html_meta_description']; ?>">
	<meta name="author" content="<?php echo $tmpl['html_meta_author']; ?>" >
	<title><?php echo $tmpl['html_meta_title']; ?></title>

	<!-- head_m.js -->
	<script src='<?php echo $cdnfullurl_js; ?>head_m.js?mode=mobile'></script>

</head>
<body>

  <body>
    <div class="container-fluid ann_data my-4">
	  <?php echo $tmpl['message']; ?>
    </div>
</body>
</html>
