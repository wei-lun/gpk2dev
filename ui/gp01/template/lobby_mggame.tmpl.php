<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 娛樂城的樣板
// File Name:	lobby_mggame.tmpl.php
// Author:		Barkley
// Related:
// Log:
// 2016.10.18
// ----------------------------------------------------------------------------


// 有變數才執行，沒有變數就是不正常進入此 tmpl 檔案
if(isset($tmpl)) {
	// 正常
}else{
	die('ERROR');
}

$cdn_url_logo				= $cdnfullurl.'img/common/logo.png';
$cdn_url_banner			= $cdnfullurl.'img/common/banner.png';

/*
// 選單共有 6 個放在 lib.php
// 會員沒有登入的時候，顯示這個選單
menu_guest_management()
// 會員登入前 and 登入後的選單內容
menu_admin_management()
// 語言選擇列的選單
menu_language_choice()
// 會員登入的界面, 登入後顯示餘額及登出資訊
menu_login_ui()
// 系統上方中間功能選單
menu_features()
// 頁腳顯示
page_footer()

// 目前樣本檔 $tmpl 陣列, 共計有下面 8 個變數。
$tmpl['html_meta_description']
$tmpl['html_meta_author']
$tmpl['html_meta_title']
$tmpl['extend_head']
$tmpl['extend_js']
$tmpl['message']
$tmpl['paneltitle_content']
$tmpl['panelbody_content']
*/

?>


<!DOCTYPE html>
<html>

<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<link rel="shortcut icon" href="<?php echo $config['companyFavicon'] ?>">
		<meta name="description" content="<?php echo $tmpl['html_meta_description']; ?>">
		<meta name="author" content="<?php echo $tmpl['html_meta_author']; ?>">
		<title>
			<?php echo $tmpl['html_meta_title']; ?>
		</title>
		<!-- TODO add manifest here -->
		<link rel="manifest" href="<?php echo $config['website_baseurl']; ?>in/manifest/manifest.json">
		<!-- Add to home screen for Safari on iOS -->
		<meta name="mobile-web-app-capable" content="yes">
		<meta name="apple-mobile-web-app-capable" content="yes">
		<link rel="apple-touch-icon" href="<?php echo $cdnfullurl_js; ?>icons/touch-icon-iphone.png" />
		<!-- Parse, validate, manipulate, and display dates in JavaScript. -->
		<script src="<?php echo $cdnfullurl_js; ?>moment/moment-with-locales.js"></script>
		<script src="<?php echo $cdnfullurl_js; ?>moment/moment-timezone-with-data.js"></script>

		<!-- bootstrap and jquery -->
<link rel="stylesheet" href="<?php echo $cdnfullurl_js; ?>fonticon/css/icons.css">
		<link rel="stylesheet" href="<?php echo $cdnfullurl_js; ?>bootstrap/css/bootstrap.min.css">
		<script src="<?php echo $cdnfullurl_js; ?>jquery/jquery.min.js"></script>
		<script src="<?php echo $cdnfullurl_js; ?>bootstrap/js/bootstrap.min.js"></script>
		<!-- jquery.crypt.js -->
		<script src="<?php echo $cdnfullurl_js; ?>jquery.crypt.js"></script>
		<!-- Custom styles for this template -->
		<script src="<?php echo $cdnfullurl_js; ?>js/common.js"></script>
		<link rel="stylesheet" href="<?php echo $cdnfullurl_js; ?>css/common_eshop.css?version_key=eshop180803">
		<link rel="stylesheet" href="<?php echo $cdnfullurl ?>css/style.css?version_key=eshop180803">
		<?php echo $tmpl['extend_head']; ?>
	</head>
	<body>
		<div id="wrapper">
			<!-- header -->
			<header id="header">
        <div class="h_topnav">
          <div class="container">
            <div class="row h_topnavb">
              <div class="col-xs-5 col-md-5">
                <nav>
                  <ul class="nav nav-pills pull-lift">
                    <?php
										// 左側選單(語言切換列)
										echo menu_language_choice();
										// 美東時間
										echo menu_time();
										?>
                  </ul>
                </nav>
              </div>
              <div class="col-xs-7 col-md-7">
                <nav>
                  <ul class="nav nav-pills pull-right">
                    <?php // 會員選單
											echo menu_admin_management('gamelobby');
										?>
                  </ul>
                </nav>
              </div>
            </div>
          </div>
        </div>
				<div class="container">
					<div class="row h_mid">
						<div class="col-xs-5 col-md-5 h_logo">
							<a href="<?php echo $config['website_baseurl'];?>">
							<?php  // LOGO圖片
				      echo '<img src="'.$cdn_url_logo.'" alt="LOGO"/>';
				      ?>
			  			</a>
						</div>
						<div class="col-xs-7 col-md-7 h_login">
							<?php // 會員登入的界面, 登入後顯示餘額及登出資訊
					    echo menu_login_ui();
						  ?>
						</div>
					</div>
					<div class="row h_nav">
						<div class="col-12">
							<?php
						// 中間主功能選單
						echo menu_features();
						?>
						</div>
					</div>
				</div>
			</header>
			<!-- end header -->
			<!-- Main-->
			<div id="main" class="home-content-background">
				<div class="container">
					<div class="row">
						<div class="col-12">
							<?php
							// 工作區內容
							echo $tmpl['panelbody_content'];
							?>
						</div>
					</div>
				</div>
			</div>
			<!-- end Main -->
			<!-- Footer -->
			<footer id="footer">
				<div class="container">
					<div class="row f_nav">
						<div class="col-12">
							<div class="f_parlogo"></div>
							<?php
						// 頁腳顯示
						echo page_footer();
				    ?>
						</div>
					</div>
					<div class="row f_config">
						<?php
	            // Javascript
	            echo $tmpl['extend_js'];
					?>
					</div>
				</div>
			</footer>
		</div>
		<!-- end wrapper -->
	</body>

	</html>
