<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- home 專用 - 給 guest 權限使用
// File Name:	home.tmpl.php
// Author:		Barkley
// Related:		home.php
// Log:
// 2016.10.18
// ----------------------------------------------------------------------------

//廣告們
require_once dirname(__FILE__) ."/adsense.php";
//UI樣式(BANNER...)
require_once dirname(__FILE__) ."/ui.php";

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

// skip
<script src="<?php echo $cdnfullurl_js; ?>jquery/jquery.min.js"></script>
<meta http-equiv="X-UA-Compatible" content="IE=edge">

*/

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
    <!-- SuperSlide -->
    <script src="<?php echo $cdnfullurl_js; ?>superslide/jquery.superslide.2.1.1.js"></script>
    <link rel="stylesheet" href="<?php echo $cdnfullurl_js; ?>superslide/superslide_style.css">
    <!-- Custom styles for this template -->
		<script src="<?php echo $cdnfullurl_js; ?>js/common.js"></script>
		<link rel="stylesheet" href="<?php echo $cdnfullurl_js; ?>css/common_eshop.css?version_key=eshop180803">
    <link rel="stylesheet" href="<?php echo $cdnfullurl_js; ?>fonticon/css/icons.css">
    <link rel="stylesheet" href="<?php echo $cdnfullurl ?>css/style.css?version_key=eshop180803">
    <link rel="stylesheet" href="<?php echo $cdnfullurl ?>css/style_home.css">
    <?php
			echo $tmpl['extend_head'];
			//搭配 adsense.php -- 右邊廣告的 CSS
			echo $ad['adsense_extend_head'];	?>
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
											echo menu_admin_management('home');
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
      <!-- Banner-->
      <?php
						// Banner
            echo $tmpl['banner'];
?>
        <!-- end Banner-->
        <!-- Marquee-->
        <?php
						// 跑馬燈
            echo $ui['Scroll_marquee'];
          ?>

          <!-- end Marquee-->
          <!-- Main-->
          <div id="main">
            <div class="container m_content">
              <?php
						// 工作區內容
						echo $tmpl['panelbody_content'];
						?>
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

							// 搭配 adsense.php 需 include
							// 右側的廣告 + JS script
							echo $ad['right_float_img_html'];
							echo $ad['right_float_img_script'];
							// 左側的浮動廣告
							// echo $ad['left_float_img_html'];
					?>
              </div>
            </div>
          </footer>
    </div>
  </body>

  </html>
