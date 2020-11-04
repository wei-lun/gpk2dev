<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- home 專用 - 給 guest 權限使用
// File Name:	home.tmpl.php
// Author:		Barkley
// Related:		home.php
// Log:
// 2016.10.18
// ----------------------------------------------------------------------------

// 有變數才執行，沒有變數就是不正常進入此 tmpl 檔案
if(isset($tmpl)) {
	// 正常
}else{
	die('ERROR');
}

$template_name ='home';
//header&footer
require_once dirname(__FILE__) ."/ui.php";
/*首頁幻燈片*/
require_once dirname(dirname(__DIR__)) ."/component/carousel.php";
$data["index_carousel"]["desktop"]["item"] = json_decode('[{"img":"img/home/banner01.jpg","link":"#"}]',true);
/*gametab_gp06*/
require_once dirname(dirname(__DIR__)) ."/component/gametab_gp06/gametab_gp06.php";
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


    <!-- head.js -->
    <?php echo assets_include(); ?>
    <!-- custom -->
    <link type="text/css" rel="stylesheet" href="<?php echo $cdnfullurl ?>css/style.css?version_key=<?php echo $config['cdn_version_key'] ?>">

    <?php
			echo $tmpl['extend_head'];

      ?>
  </head>
  <body id="home">
    <div id="wrapper">

  <!-- header -->
  <?php echo $header_templ; ?>
  <!-- end header-->


          <!-- Main-->
          <div id="main">


      <!-- Banner-->
        <section class="mainImg">

                <div class="mainImg">
                  <?php index_carousel(0,4000,450) ?>

          </div>
        </section>

        <!-- end Banner-->
      <div class="w-100" style="background-color: #300;">
        <div class="w1000">
          <?php echo Scroll_marquee(10000); ?>
        </div>
      </div>
      <div class="container-fluid m_content">
              <?php
						// 工作區內容
						//echo $tmpl['panelbody_content'];
						?>
<!-- section01 -->
        <div id="section01" class="row">
          <div class="col-6 p-0">
<!-- 首頁gamelobby顯示區域 -->
              <?php ui_gametab_gp06(1,'c','','',8,'1'); ?>
<!-- gamelobby結束 -->
          </div>
          <div class="col-6">
            <div class="casino-menu">
              <div class="tab-content" id="v-pills-tabContent">
                <div class="tab-pane fade show active" id="v-pills-game" role="tabpanel" aria-labelledby="v-pills-game-tab"><img src="<?php echo $cdnfullurl ?>img/home/photo01.jpg" alt="">
                  <p></p>
                  <div class="mt-2 ml-3 text-center"><a class="base-link" href="<?php echo $config['website_baseurl']; ?>gamelobby.php?mgc=game">ENTER</a></div>
                </div>
                <div class="tab-pane fade" id="v-pills-live" role="tabpanel" aria-labelledby="v-pills-profile-tab"><img src="<?php echo $cdnfullurl ?>img/home/photo02.jpg" alt="">
                  <p></p>
                  <div class="mt-2 ml-3 text-center"><a class="base-link" href="<?php echo $config['website_baseurl']; ?>gamelobby.php?mgc=Live">ENTER</a></div>
                </div>
                <div class="tab-pane fade" id="v-pills-lottery" role="tabpanel" aria-labelledby="v-pills-messages-tab"><img src="<?php echo $cdnfullurl ?>img/home/photo05.jpg" alt="">
                  <p></p>
                  <div class="mt-2 ml-3 text-center"><a class="base-link" href="<?php echo $config['website_baseurl']; ?>gamelobby.php?mgc=Lottery">ENTER</a></div>
                </div>
                <div class="tab-pane fade" id="v-pills-fishing" role="tabpanel" aria-labelledby="v-pills-settings-tab"><img src="<?php echo $cdnfullurl ?>img/home/photo06.jpg" alt="">
                  <p></p>
                  <div class="mt-2 ml-3 text-center"><a class="base-link" href="<?php echo $config['website_baseurl']; ?>gamelobby.php?mgc=Fishing">ENTER</a></div>
                </div>
              </div>

              <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                <a class="active" id="v-pills-game-tab" data-toggle="pill" href="#v-pills-game" role="tab" aria-controls="v-pills-game" aria-selected="true"><?php echo $tr['menu_game']; ?><span>GAMES CASINO</span></a>
                <a class="" id="v-pills-live-tab" data-toggle="pill" href="#v-pills-live" role="tab" aria-controls="v-pills-live" aria-selected="false"><?php echo $tr['menu_live']; ?><span>LIVE CASINO</span></a>
                <a class="" id="v-pills-lottery-tab" data-toggle="pill" href="#v-pills-lottery" role="tab" aria-controls="v-pills-lottery" aria-selected="false"><?php echo $tr['menu_lottery']; ?><span>LOTTERY GAMES</span></a>
                <a class="" id="v-pills-fishing-tab" data-toggle="pill" href="#v-pills-fishing" role="tab" aria-controls="v-pills-fishing" aria-selected="false"><?php echo $tr['menu_fishing']; ?><span>FISHING GAMES</span></a>
              </div>
            </div>
          </div>
        </div>

<!-- section02 end -->

            </div>
          </div>
          <!-- end Main -->

      <!-- Footer -->
      <?php echo $footer_templ ?>
      <!-- end Footer -->

    </div>
        <?php
              // Javascript
              echo $tmpl['extend_js'];


          ?>
        </body>
  </html>
