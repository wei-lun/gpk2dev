<?php
// ----------------------------------------------------------------------------
// Features:	維護頁面
// File Name:	maintenance_message.php
// Author:		shiuan
// Related:
// Log:
// 2020.02.17
// ----------------------------------------------------------------------------

// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";
// uisetting函式庫
require_once dirname(__FILE__) ."/lib_uisetting.php";

//var_dump($_SESSION);
//var_dump($_POST);

// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<link rel="shortcut icon" href="<?php echo $config['companyFavicon'] ?>">
		<!-- bootstrap 4.0.0 and jquery -->
		<link rel='stylesheet' href='<?php echo $cdnfullurl_js ?>bootstrap/css/bootstrap.min.css'>
		<script src='<?php echo $cdnfullurl_js ?>jquery/jquery.min.js'></script>
		<script src='<?php echo $cdnfullurl_js ?>js/common.js'></script>
		<script src='<?php echo $cdnfullurl_js ?>bootstrap/js/bootstrap.min.js'></script>
		<!-- CSS共用修正 -->
		<link rel='stylesheet' href="<?php echo $cdnfullurl_js ?>/css/messagehtml.css?version_key=<?php echo $config['cdn_version_key'] ?>">
<?php
// 類型： 娛樂城、遊戲 目前為demo
$types = '娛樂城';
// 娛樂城名稱或遊戲名稱  目前為demo
$title = 'GPK';
//娛樂城img 需接目前的娛樂城logo或者遊戲logo 目前為demo
$imgurl = 'in/img/icon/foo_casino/gpk.png';
//娛樂城img
$title_img = '<img src="'.$imgurl.'" alt="'.$title.'">';
//顯示目前狀態
// 維護 ID maintenance
// 目前為固定id
$status_id = 'maintenance';
//訊息標題
$description_title = '公告訊息';
//訊息內容
$description_container = '我是內容';
//JQ
$js = <<<HTML
<script>
		$(document).ready(function(){
				if ($('body').height() < $(window).height()) {
						$('body').height($(window).height());
						$('.container').addClass('center_style');
				}
		});
</script>
HTML;
?>
</head>
<?php echo $js ?>
<body id="<?php echo $status_id; ?>" class="animate">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <!-- 標題 -->
                <div class="name_box">
										<?php 
												//logo 順序不可換
												//無圖的時候會顯示h2 由CSS控制
												echo $title_img;
										?>										
										<h2>
											<?php 
												//名稱 順序不可換
												//無圖的時候會顯示h2 由CSS控制
												echo $title;
											?>
										</h2>
                    <span>
											<?php
												//娛樂城、遊戲、站台 名稱
												echo $types;
											?>
										</span>
                </div>
                <!-- 訊息 -->
                <div class="description_box">
                    <h3 class="title_container">	
											<?php
												//區塊標題
												echo $description_title;
											?>
										</h3>
                    <div class="description_container">
											<?php
												//區塊內容
												echo $description_container;
											?>
										</div>
                </div>

            </div>
        </div>
    </div>
</body>
</html>