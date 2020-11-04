<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 娛樂城的樣板
// File Name:	lobby_mggame.tmpl.php
// Author:		Barkley
// Related:
// Log:
// 2016.10.18
// ----------------------------------------------------------------------------

//廣告們
//require_once dirname(__FILE__) ."/adsense.php";
//UI樣式(BANNER...)
//跑馬燈
require dirname(dirname(__DIR__)).'/component/marquee.php';
//推廣遊戲
require dirname(dirname(__DIR__)).'/component/game_exposure.php';
//推薦遊戲
require dirname(dirname(__DIR__)).'/component/game_recommend.php';
// 有變數才執行，沒有變數就是不正常進入此 tmpl 檔案
if(isset($tmpl)) {
  // 正常
}else{
  die('ERROR');
}

$cdn_url_logo		= $cdnfullurl.'img/common/logo.png';
$cdn_url_banner		= $cdnfullurl.'img/common/banner.png';
$template_name ='gamelobby';
//header&footer
require_once dirname(__FILE__) ."/header.tmpl.php";

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
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <link rel="shortcut icon" href="<?php echo $config['companyFavicon'] ?>">
    <meta name="description" content="<?php echo $tmpl['html_meta_description']; ?>">
    <meta name="author" content="<?php echo $tmpl['html_meta_author']; ?>">
    <title>
      <?php echo $tmpl['html_meta_title']; ?>
    </title>
    

	  <!-- head.js -->
	  <?php echo assets_include(); ?>
    <!-- custom -->
<link type="text/css" rel="stylesheet" href="<?php echo $cdnfullurl ?>css/style.css?ver_key_m=<?php echo $config['cdn_version_key'] ?>">   
<?php echo $tmpl['extend_head']; ?>
  </head>
  <body id="gamelobby">
     <div id="wrapper">
      <!-- header -->
			<?php
				echo $mobile_header;
			?>
			<!-- end header -->
        <!-- Marquee-->
        <?php
						// 跑馬燈
            echo Scroll_marquee();
        ?>
          <!-- end Marquee-->
          <!-- Main-->
          <div id="main">
          <?php
						// 推廣遊戲
            //echo game_exposure();
          ?>
          <?php
						// 推薦遊戲
            echo game_recommend();
          ?>
          <!-- 首頁顯示區域 -->
					<?php

					// gamelobby主選單顯示區域
          require_once dirname(dirname(dirname(__DIR__))).'/casino/casino_config.php';
					$gamelist_template['main'] = '
					<div id="mainctag">
						<!-- Swiper -->
						<div id="gamelobby-swiper" class="swiper-container">
							<ul id="gNavi" class="nav nav-tabs index_tabmenu swiper-wrapper" role="tablist">
								<li role="presentation" class="swiper-slide navi_hot"><a href="home.php" class="" aria-selected="false">'.$tr['hot'].'</a></li>
								{mct_item}
							</ul>
						</div>
						<!-- Initialize Swiper -->
						<script>
							var swiper = new Swiper(\'#gamelobby-swiper\', {
								slidesPerView: 4.55,
								spaceBetween: 5,
							});
						</script>
        	</div><div class="container-fluid">';
						$gamelist_template['mct_item'] = '<li id="mctag_{mctid}" role="presentation" class="swiper-slide {mctactive} navi_{mctid}">
							<a href="#index_m_gametable" aria-controls="index_m_gametable" role="tab" data-toggle="tab" class="" onclick="mct_getgamelist(\'{mctid}\');" aria-selected="true">							{mct_name}</a></li>';
						$gamelist_template['mctag_item'] = '';
						$gamelist_template['mcmtag_item'] = '';
					home_gamelist($gamelist_template);
					 // End gamelobby主選單顯示區域
							// 工作區內容

							echo $tmpl['panelbody_content'];
							?>
          <!-- 首頁結束 -->
            </div>
          </div>
          <!-- end Main -->
          <!-- Footer -->
          <footer id="footer" class="footer">
                  <?php
						// 頁腳顯示
						echo $mobile_footer;
				    ?>              
          </footer>
    </div>
    <div class="row f_config">
                <?php
              // Javascript
              echo $tmpl['extend_js'];
        // 搭配 adsense.php 需 include
        // 右側的廣告 + JS script
        //echo $ad['right_float_img_html'];
              //echo $ad['right_float_img_script'];
              //echo $ad['right_top_float_img_html'];
              // 左側的廣告
              //echo $ad['lb_float_img_html'];
          ?>
    </div>
  </body>

  </html>