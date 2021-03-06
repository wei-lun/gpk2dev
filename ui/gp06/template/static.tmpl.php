<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- static html 樣本頁面, 給靜態公告頁面的樣板
// File Name:	static.tmpl.php
// Author:		Barkley
// Related:		aboutus.php partner.php howtodeposit.php howtowithdraw.php contactus.php
// Log:
// 2016.10.18
// ----------------------------------------------------------------------------

// 有變數才執行，沒有變數就是不正常進入此 tmpl 檔案
if(isset($tmpl)) {
	// 正常
}else{
	die('ERROR');
}

$template_name ='static';

//header&footer
require_once dirname(__FILE__) ."/ui.php";

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

// skip
<script src="<?php echo $cdnfullurl_js; ?>jquery/jquery.min.js"></script>
<meta http-equiv="X-UA-Compatible" content="IE=edge">

*/
if(isset($tmpl['sidebar_content'])){
	$tmpl['sidebar_content'] = combine_sidebarmenu($tmpl['sidebar_content'],'<div class="pt-3"> _siderbar-menu_ </div>');
}
?>


<!DOCTYPE html>
<html>

<head>

		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE11" />
    <link rel="shortcut icon" href="<?php echo $config['companyFavicon'] ?>">
		<meta name="description" content="<?php echo $tmpl['html_meta_description']; ?>">
		<meta name="author" content="<?php echo $tmpl['html_meta_author']; ?>">
		<title>
			<?php echo $tmpl['html_meta_title']; ?>
		</title>
		

		<!-- head.js -->
	    <?php echo assets_include(); ?>
	    <!-- custom -->
		
	    <link type="text/css" rel="stylesheet" href="<?php echo $cdnfullurl ?>css/style.css?version_key=<?php echo $config['cdn_version_key'] ?>">
    

		<?php
			echo $tmpl['extend_head'];
			
      		?>
	</head>
	<body id="static">
		<div id="wrapper">
	  <!-- header -->
	  <?php echo $header_templ; ?>
	  <!-- end header-->
			<!-- Main-->
		<div>
			<div id="main" class="">
				<!-- content-->
				<div class="container">
					<div>
							<?php
								// Breadcrumb
								echo $tmpl['paneltitle_content'];
								?>
					</div>
					<!--<div class="row">
						<div class="col-12">
							<h3 class="panel-title">
								<?php
							// 工作區標題
							echo $function_title;
							?>
							</h3>
						</div>
					</div>-->
					<div class="row mt-3">
						<?php
						// 側邊欄內容
						if(isset($tmpl['sidebar_content']))
							echo $tmpl['sidebar_content'];
						?>
						<div class="col pt-3 pages-content">
									<?php
							// 工作區內容
							echo $tmpl['panelbody_content'];
							?>
						</div>

					</div>
				</div>
			</div>
		</div>
			<!-- end main container -->

	      <!-- Footer -->
	      <?php echo $footer_templ ?>
	      <!-- end Footer -->

		</div>
		<!-- end wrapper -->
			<?php
	        // Javascript
	        echo $tmpl['extend_js'];
			 
            
          ?>  
		</body>
	</html>
