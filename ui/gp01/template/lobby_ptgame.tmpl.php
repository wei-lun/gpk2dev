<?php
// ----------------------------------------------------------------------------
// Features:	對應 gamelobby.php 的樣板
// File Name:	lobby_ptgame.tmpl.php
// Author:		Barkley
// Related:
// Log:
// ----------------------------------------------------------------------------


// 有變數才執行，沒有變數就是不正常進入此 tmpl 檔案
if(isset($tmpl)) {
	// 正常
}else{
	die('ERROR');
}

$cdn_url_logo				= $cdnfullurl.'img/common/logo_gold.png';
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
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="shortcut icon" href="favicon.ico">
	<meta name="description" content="<?php echo $tmpl['html_meta_description']; ?>">
	<meta name="author" content="<?php echo $tmpl['html_meta_author']; ?>" >

	<!-- Add to home screen for Safari on iOS -->
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<link rel="apple-touch-icon" href="<?php echo $cdnfullurl_js; ?>icons/touch-icon-iphone.png" />
	<!-- Parse, validate, manipulate, and display dates in JavaScript. -->
	<script src="<?php echo $cdnfullurl_js; ?>moment/moment-with-locales.js"></script>
	<script src="<?php echo $cdnfullurl_js; ?>moment/moment-timezone-with-data.js"></script>

	<title><?php echo $tmpl['html_meta_title']; ?></title>

	<!-- bootstrap and jquery -->
<link rel="stylesheet" href="<?php echo $cdnfullurl_js; ?>fonticon/css/icons.css">
	<link rel="stylesheet" href="<?php echo $cdnfullurl_js; ?>bootstrap/css/bootstrap.min.css">
	<script src="<?php echo $cdnfullurl_js; ?>jquery/jquery.min.js"></script>
	<script src="<?php echo $cdnfullurl_js; ?>bootstrap/js/bootstrap.min.js"></script>

	<!-- jquery.crypt.js -->
	<script src="<?php echo $cdnfullurl_js; ?>jquery.crypt.js"></script>

	<!-- bootstrap flat css-->
	<link rel="stylesheet"  href="<?php echo $cdnfullurl_js; ?>bootflat/css/bootflat.min.css" >

	<!-- Custom styles for this template -->
		<script src="<?php echo $cdnfullurl_js; ?>js/common.js"></script>
		<link rel="stylesheet" href="<?php echo $cdnfullurl_js; ?>css/common_eshop.css?version_key=eshop180803">
	<link rel="stylesheet"  href="<?php echo $cdnfullurl ?>css/style.css?version_key=eshop180803" >

	<meta http-equiv="refresh" content="600">
	<?php echo $extend_head; ?>

	</head>

	<body>
	<!-- menu and login -->
	<div class="container">
		<div class="header clearfix">

			<div class="col-12 col-md-3">
					<div class="navbar-header">
						<a href="./">
							<?php echo '<img src="'.$cdn_url_logo.'" alt="LOGO" height="70px" />'; ?>
						</a>
					</div>
			</div>

			<div class="col-12 col-md-5">
				<nav>
					<ul class="nav nav-pills pull-right">
					<?php // 會員沒有登入的時候，顯示這個選單
					echo menu_guest_management()
					?>

					<?php // 會員登入前 and 登入後的選單內容
					echo menu_admin_management();
					?>

					<?php // 語言選擇列的選單
					echo menu_language_choice(); ?>
					</ul>
				</nav>
			</div>

			<div class="col-12 col-md-4">
				<div class="row">
				<?php // 會員登入的界面, 登入後顯示餘額及登出資訊
				echo menu_login_ui();  ?>
				</div>
			</div>

		</div>
	</div>
	<!-- end container -->


	<!-- nav menu -->
	<div class="container">
		<div class="row">
			<div class="col-12 col-sm-12">
				<nav class="navbar navbar-default">
					<ul class="nav navbar-nav">
					<?php
					// 系統上方中間功能選單
					echo menu_features();
					?>
					</ul>
				</nav>
			</div>

		</div>
	</div>

	<!-- banner and message -->
	<div class="container">
		<div class="row">

			<div class="col-12 col-sm-12">
				<?php
				// 系統中間提示訊息功能
				echo $tmpl['message'];
				?>
			</div>

			<div class="col-12 col-sm-12">
			<?php
				echo '<a href="home.php"><img src="'.$cdn_url_banner.'" alt="BANNER" width="100%"></a> ';
			?>
			</div>

		</div>
	</div>
	<!-- end container -->

	<div class="container">

		<div class="row">
			<div class="col-12 col-sm-12">

				<div class="panel panel-default">

				  <div class="panel-heading">
					<h3 class="panel-title">
					<?php
						// 工作區標題
						echo $tmpl['paneltitle_content'];
					?>
					</h3>
				  </div>

				  <div class="panel-body">
					<?php
						// 工作區內容
						echo $tmpl['panelbody_content'];
					?>
				  </div>

				</div>

			</div>
		</div>

		<?php
		// Javascript
		echo $tmpl['extend_js']; ?>

		<div class="panel panel-default">
			<div class="panel-footer">
				<?php
				// 頁腳顯示
				echo page_footer();
				?>
			</div>
		</div>
	</div>

</body>
</html>
