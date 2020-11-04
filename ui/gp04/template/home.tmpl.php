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
$data["index_carousel"]["desktop"]["item"] = json_decode('[{"img":"img/common/banner01.jpg","link":"gamelobby.php"},{"img":"img/common/banner02.jpg","link":"gamelobby.php"},{"img":"img/common/banner03.jpg","link":"gamelobby.php"}]',true);
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
    <?php echo assets_include('animate'); ?>
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

          <!-- 幻燈片部分 -->
          <?php index_carousel(0,5000) ?>
          <!-- 結束幻燈片部分 -->
          
          <style>
            .redoutside {
              border: 4px #c42133 solid;
              border-radius: 5px;
            }

            #single .carousel-control {
              width: 5%;
            }

            #single .carousel-indicators {
              display: none;
            }

            #single .carousel-item .col-xs-2 {
              text-align: center;
            }

            #single .carousel-item {
              padding: 15px 40px;
            }
            #single .carousel-inner>.carousel-item{
              padding: 15px 70px;
            }
            .feature-left {
              background-image: url(<?php echo $cdnfullurl ?>img/home/tag01.png), url(<?php echo $cdnfullurl ?>img/home/button_live.png), url(<?php echo $cdnfullurl ?>img/home/live.png), linear-gradient(to bottom, rgba(30, 75, 115, 1), rgba(255, 255, 255, 0));
              background-repeat: no-repeat, no-repeat, repeat-x, no-repeat;
              background-position: left top, center bottom, center center, right;
            }

            .feature-center {
              background-image: url(<?php echo $cdnfullurl ?>img/home/tag02.png), url(<?php echo $cdnfullurl ?>img/home/button_game.png), url(<?php echo $cdnfullurl ?>img/home/game.png), linear-gradient(to bottom, rgba(30, 75, 115, 1), rgba(255, 255, 255, 0));
              background-repeat: no-repeat, no-repeat, repeat-x, no-repeat;
              background-position: left top, center bottom, center center, right;
            }

            .feature-right {
              background-image: url(<?php echo $cdnfullurl ?>img/home/tag03.png), url(<?php echo $cdnfullurl ?>img/home/button_fish.png), url(<?php echo $cdnfullurl ?>img/home/fish.png), linear-gradient(to right, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)); 
              background-repeat: no-repeat, no-repeat, repeat-x, no-repeat;
              background-position: left top, center bottom, center center, right;
            }

            .disable {
              background-image: linear-gradient(to right, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), inherit, inherit, inherit;
              background-repeat: no-repeat, no-repeat, no-repeat, repeat-x;
              background-position: right, left top, center bottom, center center;
            }

            .services div {
              text-align: center;
              position: relative;
            }

            .services>div:after {
              content: '';
              width: 1px;
              height: 50px;
              position: absolute;
              right: 0;
              top: 50%;
              margin-top: -25px;
              background-color: #ebebeb;
            }

            .services div:last-child:after {
              display: none;
            }

            .services>div .service-content {
              margin: 29px 0;
            }

            .services>div .service-content .service-icon {
              display: inline-block;
              vertical-align: top;
            }

            .services .service-content .service-info {
              display: inline-block;
              vertical-align: top;
              text-align: left;
              padding-left: 20px;
              line-height: 1.2;
              color: #ffffff;
            }

            .services .service-content .service-info h4 {
              font-weight: 700;
              text-transform: uppercase;
              font-size: 100%;
            }

            .services .service-content .service-info h4 a {
              color: #999999;
              transition: 0.2s;
              -moz-transition: 0.2s;
              -webkit-transition: 0.2s;
            }
          </style>
          <!-- Main-->
          <div id="main">
            <div class="container m_content">
              <!-- Marquee-->
               <?php
                // 跑馬燈
                echo Scroll_marquee();
              ?>
              <!-- end Marquee-->
              <div class="row">
                <div class="col-md-12 col-12">
                  <div id="single" class="carousel slide redoutside" data-ride="carousel">
                    <!-- Indicators -->
                    <ol class="carousel-indicators">
                      <li data-target="#single" data-slide-to="0" class=""></li>
                      <li data-target="#single" data-slide-to="1" class=""></li>
                      <li data-target="#single" data-slide-to="2" class="active"></li>
                    </ol>

                    <!-- Wrapper for slides -->
                    <div class="carousel-inner">

                      <div class="carousel-item">

                        <ul class="row d-flex justify-content-around">
                          <li class="col-xs-2"> <img src="<?php echo $cdnfullurl ?>img/home/lotteryhot01.png"></li>
                          <li class="col-xs-2"> <img src="<?php echo $cdnfullurl ?>img/home/lotteryhot02.png"></li>
                          <li class="col-xs-2"> <img src="<?php echo $cdnfullurl ?>img/home/lotteryhot03.png"></li>
                          <li class="col-xs-2"> <img src="<?php echo $cdnfullurl ?>img/home/lotteryhot04.png"></li>
                          <li class="col-xs-2"> <img src="<?php echo $cdnfullurl ?>img/home/lotteryhot05.png"></li>
                          <li class="col-xs-2"> <img src="<?php echo $cdnfullurl ?>img/home/lotteryhot06.png"></li>
                        </ul>
                      </div>

                      <div class="carousel-item">
                        <ul class="row d-flex justify-content-around">
                          <li class="col-xs-2"> <img src="<?php echo $cdnfullurl ?>img/home/lotteryhot01.png"></li>
                          <li class="col-xs-2"> <img src="<?php echo $cdnfullurl ?>img/home/lotteryhot02.png"></li>
                          <li class="col-xs-2"> <img src="<?php echo $cdnfullurl ?>img/home/lotteryhot03.png"></li>
                          <li class="col-xs-2"> <img src="<?php echo $cdnfullurl ?>img/home/lotteryhot04.png"></li>
                          <li class="col-xs-2"> <img src="<?php echo $cdnfullurl ?>img/home/lotteryhot05.png"></li>
                          <li class="col-xs-2"> <img src="<?php echo $cdnfullurl ?>img/home/lotteryhot06.png"></li>
                        </ul>
                      </div>

                      <div class="carousel-item active">
                        <ul class="row d-flex justify-content-around">
                          <li class="col-xs-2"> <img src="<?php echo $cdnfullurl ?>img/home/lotteryhot01.png"></li>
                          <li class="col-xs-2"> <img src="<?php echo $cdnfullurl ?>img/home/lotteryhot02.png"></li>
                          <li class="col-xs-2"> <img src="<?php echo $cdnfullurl ?>img/home/lotteryhot03.png"></li>
                          <li class="col-xs-2"> <img src="<?php echo $cdnfullurl ?>img/home/lotteryhot04.png"></li>
                          <li class="col-xs-2"> <img src="<?php echo $cdnfullurl ?>img/home/lotteryhot05.png"></li>
                          <li class="col-xs-2"> <img src="<?php echo $cdnfullurl ?>img/home/lotteryhot06.png"></li>
                        </ul>
                      </div>

                    </div>

                    <!-- Left and right controls -->
                    <a class="left carousel-control-prev" href="#single" data-slide="prev">
      <span class="glyphicon glyphicon-chevron-left"></span>
      <span class="sr-only">Previous</span>
    </a>
                    <a class="right carousel-control-next" href="#single" data-slide="next">
      <span class="glyphicon glyphicon-chevron-right"></span>
      <span class="sr-only">Next</span>
    </a>
                  </div>
                </div>
              </div>
          <div class="w100">
            <!-- 熱門遊戲部分 -->  
              <!-- 首頁gamelobby顯示區域 -->
                <?php ui_gametab_gp06(1,'c','','',10,'1'); ?>
            <!-- 結束熱門遊戲 -->
          </div>   

              <div class="row m_feature">
                <!-- feature-block 1 -->
                <div class="col-md-4 col-xs-4">
                  <a href="./gamelobby.php?mgc=Live">
                    <div class="feature feature-left">
                      <div class="feature block">
                      </div>
                    </div>
                  </a>
                </div>
                <!-- feature-block 2 -->
                <div class="col-md-4 col-xs-4">
                  <a href="./gamelobby.php" target="_BLANK">
                    <div class="feature feature-center">
                      <div class="feature block">
                      </div>
                    </div>
                  </a>
                </div>
                <!-- feature-block 3 -->
                <div class="col-md-4 col-xs-4">
          <a href="./gamelobby.php?mgc=Fishing" target="_BLANK">
                  <div class="feature feature-right">
                    <div class="feature block">
                     <!-- <h5>即將推出 </h5>
                      <p>精彩可期，敬請期待</p>-->
                    </div>
                  </div>
          </a> 
                </div>
              </div>
              <div class="row m_projects">
              </div>
              <div class="row">
                <div class="col-xs-2 col-md-2">
                </div>
                <div class="col-xs-8 col-md-8">
                  <div id="preview"></div>
                </div>
                <div class="col-xs-2 col-md-2">
                </div>
              </div>

            </div>
          </div>
          <!-- end Main -->
          <!-- Footer -->
      <?php echo $footer_templ ?>
      <!-- end Footer -->

</div>

  <script>
    wow = new WOW(
      {
        animateClass: 'animated',
        offset:       200,
        callback:     function(box) {
          //console.log("WOW: animating <" + box.tagName.toLowerCase() + ">")
        }
      }
    );
    wow.init();
</script> 
          <?php
              // Javascript
              echo $tmpl['extend_js'];
               
              
      ?>
        </body>
  </html>
