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

$template_name = 'gamelobby';
//header&footer
require_once dirname(__FILE__) ."/ui.php";
require_once dirname(dirname(__DIR__)) ."/component/carousel.php";
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

// if(isset($tmpl['banner'])){
// 	$tmpl['banner'] = combine_banner($tmpl['banner']);
// }
if(isset($tmpl['banner'])){
	$tmpl['banner'] = gamelobby_combine_banner($tmpl['banner']);
}

if(isset($tmpl['menu_active'])){
	$tmpl['menu_active'] = menu_active($tmpl['menu_active']);
}
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
		

	    <!-- head.js -->
	    <?php echo assets_include(); ?>
		<!-- custom -->
	    
	    <link type="text/css" rel="stylesheet" href="<?php echo $cdnfullurl ?>css/style.css?version_key=<?php echo $config['cdn_version_key'] ?>">    

		<?php echo $tmpl['extend_head'];
		
     	 ?>
	</head>
	<body id="gamelobby">
		<div id="wrapper">

  <!-- header -->
  <?php echo $header_templ; ?>
  <!-- end header-->

  		<div id="banner" class="container-fluid px-0">
				<div class="banner">
				<?php
					// banner標題
					if(isset($tmpl['banner']))
					echo $tmpl['banner'];
				?>
				<?php  				
					$banner_carousel = index_carousel_lobby($maingame_category,0,3000);
					if ( $banner_carousel != '' ){
						echo index_carousel_lobby($maingame_category,0,3000); 						
					}else{
						echo '<div><img src="uic\gp02\img\home\banner20190604.png" class="w-100"></div>';
					}
				?>
			</div>
		</div>	

			<!-- Main-->
		<div>
			<div id="main" class="home-content-background">
				<div class="container">
					<div class="row">
						<div class="col-12">
							<div class="myagencyarea_title"><?php echo $lobbyname ?></div>
							<?php
							// 工作區內容
							echo $tmpl['panelbody_content'];
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
			<!-- end Main -->

      <!-- Footer -->
      <?php echo $footer_templ ?>
      <!-- end Footer -->
      
		</div>
		<!-- end wrapper -->
	<?php
	  // Javascript
	  echo $tmpl['extend_js'];
	   
      if(isset($tmpl['menu_active'])){
		echo $tmpl['menu_active'];
		}
    ?>  
		</body>
	</html>