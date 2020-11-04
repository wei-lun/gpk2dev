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

              <div class="login animated  flipInX">        
                <p class="login-title"><?php echo $tr['Member Centre'] ?></p>
                      <?php // 會員登入的界面, 登入後顯示餘額及登出資訊
                      echo menu_login_ui();
                      ?>         
              </div>

          </div>
        </section>

        <!-- end Banner-->
        <!-- Marquee-->
        
        <?php
            // 跑馬燈
            echo Scroll_marquee();
          ?>

          <!-- end Marquee-->
          
      <div class="container-fluid m_content">
              <?php
						// 工作區內容
						//echo $tmpl['panelbody_content'];
						?>
<!-- section01 -->
            <div class="row d-flex justify-content-center section_link">
              <a href="<?php echo $config['website_baseurl']; ?>gamelobby.php"><div class="p-2 item_casino"></div></a>
              <a href="<?php echo $config['website_baseurl']; ?>gamelobby.php?mgc=Live"><div class="p-2 item_live"></div></a>
              <!--<a href="#"><div class="p-2 item_sport"></div></a>-->
              <a href="<?php echo $config['website_baseurl']; ?>gamelobby.php?mgc=Lottery"><div class="p-2 item_lottery"></div></a>
            </div>
<!-- section01 end -->

<!-- section02 -->
<!-- 首頁gamelobby顯示區域 -->
              <?php ui_gametab_gp06(1,'c','','',8,'1'); ?>
              <div class="row d-flex justify-content-center link_more"><a href="<?php echo $config['website_baseurl']; ?>gamelobby.php">更多游戏</a></div>
<!-- gamelobby結束 -->

<!-- section02 end -->

<!-- section03 -->
            <div class="row section_promotion">
              <div class="col-6">
                <a href="<?php echo $config['website_baseurl']; ?>gamelobby.php?mgc=Lottery"><img src="<?php echo $cdnfullurl ?>img/home/jkpot_bg.jpg" alt="">
                  <div class="dollar">100,000,000</div></a>
              </div>
              <div class="col-6">
                <a href="<?php echo $config['website_baseurl']; ?>promotions.php"><img src="<?php echo $cdnfullurl ?>img/home/promo_bg.gif" alt=""></a>
              </div>
            </div>
<!-- section03 end -->

<!-- section04 -->
            <div class="row link_mobile">
              <a href="javascript:void(0);" onclick="msitechg('mobile'); return false;">手机投注</a>
            </div>
<!-- section04 end -->

<!-- section05 -->
            <div class="row d-flex justify-content-center section_link">
              <a href="<?php echo $config['website_baseurl']; ?>contactus.php"><div class="p-2 item_wechat"></div></a>
              <a href="<?php echo $config['website_baseurl']; ?>contactus.php"><div class="p-2 item_mail"></div></a>
              <a href="<?php echo $config['website_baseurl']; ?>contactus.php"><div class="p-2 item_qq"></div></a>
              <a href="<?php echo $config['website_baseurl']; ?>contactus.php"><div class="p-2 item_online"></div></a>
            </div>
<!-- section05 end -->

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
