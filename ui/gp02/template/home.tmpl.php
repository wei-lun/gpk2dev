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
$data["index_carousel"]["desktop"]["item"] = json_decode('[{"img":"img/home/homebanner_fun01.jpg","link":"#"}]',true);
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
        <!-- Marquee-->        

          <!-- end Marquee-->
          <!-- Main-->
          <div id="main">

          <!-- 幻燈片部分 -->
          <?php index_carousel(0,4000) ?>
          <!-- 結束幻燈片部分 -->

            <div class="container m_content">         

          <?php
            // 跑馬燈
            echo Scroll_marquee();
          ?>

          <div class="row m_feature">
          <!-- feature-block 1 -->
        <div class="col-4">
          <a href="./promotions.php">
          <div class="feature feature-odd">
          <div class="feature block">
          <div class="feature-icon"><span class="lnr lnr-gift"></span></div>
          <?php echo $tr['img 01']; ?>
          </div>
          </div>
          </a>
        </div>

          <!-- feature-block 2 -->
        <div class="col-4">
          <a href="./partner.php" target="_BLANK">
          <div class="feature feature-even">
          <div class="feature block">
          <div class="feature-icon"><span class="lnr lnr-diamond"></span></div>
          <?php echo $tr['img 02'];?>
          </div>
          </div>
          </a>
        </div>

          <!-- feature-block 3 -->
        <div class="col-4">
          <a href="./register.php">
          <div class="feature feature-odd">
          <div class="feature block">
          <div class="feature-icon"><span class="lnr lnr-exit"></span></div>
          <?php echo $tr['Home Info 03']; ?>
          </div>
          </div>
          </a>
        </div>

          </div>

          <div class="row m_title">
                  <div class="m_title col-12">
                  <h2><span>new arrivals</span></h2>
                  </div>
          </div>

          <div class="row m_projects">
            <div class="col-12">
              <ul id="game-box">
                <li data-img="game_live">
                    <a href="gamelobby.php?mgc=Live">
                        <div>
                          <?php echo $tr['img 03']; ?>
                        </div>
                    </a>
                </li>
                <li data-img="game_lottery">
                    <a href="gamelobby.php?mgc=Lottery">
                        <div>
                         <?php echo $tr['img 04']; ?>
                        </div>
                    </a>
                </li>
                <li data-img="game_lobby">
                              <a href="gamelobby.php">
                        <div>
                          <?php echo $tr['img 06']; ?>
                        </div>
                    </a>
                </li>
                <li data-img="game_sport">
                  <a href="gamelobby.php?mgc=Fishing">
                        <div>
                          <?php echo $tr['img 06']; ?>
                        </div>
                    </a>
                </li>
                </ul>
           </div>
          </div>  

          <div class="w100">
            <!-- 熱門遊戲部分 -->  
              <!-- 首頁gamelobby顯示區域 -->
                <?php ui_gametab_gp06(1,'c','','',10,'1'); ?>
            <!-- 結束熱門遊戲 -->
          </div>        


            </div>
        </div>
          <!-- end Main -->

      <!-- Footer -->
      <?php echo $footer_templ ?>
      <!-- end Footer -->

</div>
  <script src='<?php echo $cdnfullurl_js; ?>js/css-vars-ponyfill.min.js'></script>
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
    cssVars({
      // Include ony CSS from <link> and <style> nodes with
      // a "data-cssvarsponyfill" attribute set to "true"
      // Ex: <link data-cssvarsponyfill="true" rel="stylesheet" href="...">
      // Ex: <style data-cssvarsponyfill="true">...</style>
      include: '[data-cssvarsponyfill="true"]'
    });
</script> 
          <?php
              // Javascript
              echo $tmpl['extend_js'];
               
              
      ?>
        </body>
  </html>
