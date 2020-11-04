<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- static html 樣本頁面, 給靜態公告頁面的樣板
// File Name:	static.tmpl.php
// Author:		Barkley
// Related:		about.php partner.php howtodeposit.php howtowithdraw.php contactus.php
// Log:
// 2016.10.18
// ----------------------------------------------------------------------------


// 有變數才執行，沒有變數就是不正常進入此 tmpl 檔案
if(isset($tmpl)) {
	// 正常
}else{
	die('ERROR');
}

$cdn_url_logo				= $cdnfullurl.'img/common/logo_company.png';
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
		<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1, minimum-scale=1, maximum-scale=1">
        <link rel="shortcut icon" href="<?php echo $config['companyFavicon'] ?>">
		<meta name="description" content="<?php echo $tmpl['html_meta_description']; ?>">
		<meta name="author" content="<?php echo $tmpl['html_meta_author']; ?>">
		
		<!-- Parse, validate, manipulate, and display dates in JavaScript. -->
		<script src="<?php echo $cdnfullurl_js; ?>moment/moment-with-locales.js"></script>
		<script src="<?php echo $cdnfullurl_js; ?>moment/moment-timezone-with-data.js"></script>
		<title>
			<?php echo $tmpl['html_meta_title']; ?>
		</title>
	  <!-- head.js -->
	  <?php echo assets_include(); ?>
      <!-- custom -->
      <link type="text/css" rel="stylesheet" href="<?php echo $cdnfullurl ?>css/style.css?ver_key_m=<?php echo $config['cdn_version_key'] ?>">
	  <?php echo $tmpl['extend_head']; ?>
	  </head>

	<body id="member">
		<div id="wrapper">
			<!-- header -->
			<?php
				echo menu_admin_management($template);
			?>
			<!-- end header -->

			<!-- Main-->
			<div id="main">
				<!-- content-->
				<div class="container">
				<!--手機不顯示Breadcrumb
					<div class="row">
						<div class="col-12">
							<?php
								// Breadcrumb
								echo $tmpl['paneltitle_content'];
								?>
						</div>
					</div> -->
							<div class="card">
							<h3 class="card-header">
								<?php
							// 工作區標題
							echo $function_title;
							?>
							</h3>
								<div class="card-body">
									<?php
							// 工作區內容
							echo $tmpl['panelbody_content'];
							?>
								</div>
							</div>
				</div>
			</div>
			<!-- end main container -->
			<!-- Footer -->
			<footer id="footer" class="footer">
							<?php
						// 頁腳顯示
						echo page_footer();
				    ?>
					<div class="row f_config">
						<?php
	            // Javascript
	            echo $tmpl['extend_js'];
					?>
					</div>
			</footer>
		</div>
		<!-- end wrapper -->
	</body>


	</html>