<?php
// ----------------------------------------
// 程式功能：如果使用者使用WeChat註冊會插入遮罩頁面請使用者打開瀏覽器
// 檔案名稱：register_app.php
// Author: 	Neil
//------------------------------------------

// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 此功能的自訂函式庫
require_once dirname(__FILE__) ."/app_lib.php";


$html = '';

if(isset($_GET['r'])) {
  $account = filter_var($_GET['r'], FILTER_SANITIZE_STRING);
} else {
  $html = '<div class="error_html"><h4>網址參數有問題，請聯絡客服人員。</h4></div>';

  $block_script =
  '<script>
   $("#container_html").html("");
  </script>
  ';
}

if ($html == '') {
  $to = 'register.php';
  $urlcode['r'] = $account;
  $link = get_page_link($to,$urlcode);

  $browser_img_path = get_open_os() == 1 ? $cdnrooturl.'safari.png' : $cdnrooturl.'browser.png';

  if(is_wechat()) {
    $html = get_tips_msg_html($browser_img_path);

    $block_script =
    '<script>
      $("#container_html").html("");
    </script>
    ';
  } else {
    header("Location: $link");
  }
}

?>

<html>
<head>
  <title></title>
  <meta charset="utf-8">
  <title>OpenToRegister</title>

  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="stylesheet" href="<?php echo $cdnrooturl;?>/app.css">

</head>

<body id="body_container" class="body_background">

    <?php echo $html;?>

</body>

<?php echo $block_script;?>
</html>
