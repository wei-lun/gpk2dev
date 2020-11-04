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

      <!-- Banner-->
<section class="mainImg">

      <?php
						// Banner
            //echo $tmpl['banner']; <a href="#" class="lunLink">&nbsp;</a>
      ?>
        <div class="mainImg">
          <?php index_carousel(0,4000,450) ?>

      <div class="login animated  flipInX">        
        <p class="login-title"><?php echo $tr['Member Centre'] ?></p>
              <?php // 會員登入的界面, 登入後顯示餘額及登出資訊
              echo menu_login_ui();
              ?>         

        <!--<p class="p04">
          <input type="button" class="subbtn" name="__submit__">
          <a href="register.html" class="reg">立即注册</a> </p>-->
        <!--<p class="p05"> <a href="#" class="shiwan">免费试玩</a><a href="#" class="pass">忘记密码</a></p>-->
        <p class="p05"> <a href="<?php echo $config['website_baseurl']; ?>partner.php" class="shiwan"><?php echo $tr['Partner']; ?></a>
          <a href="<?php echo $config['website_baseurl']; ?>promotions.php" class="pass"><?php echo $tr['Promotions']; ?></a></p>
      </div>

  </div>
</section>
      <div class="col-xs-7 col-md-7 h_login">

        
      </div>

  <!-- header -->
  <?php echo $header_templ; ?>
  <!-- end header-->


        <!-- end Banner-->
        <!-- Marquee-->
        <?php
						// 跑馬燈
            echo Scroll_marquee();
          ?>

          <!-- end Marquee-->
          <!-- Main-->
          <div id="main">
            <div class="container-fluid m_content">
              <?php
						// 工作區內容
						//echo $tmpl['panelbody_content'];
						?>

<?php require_once dirname(dirname(__DIR__)) ."/component/gametab_gp05/gametab_gp05.php"; ?>

    <div class="row section02">
      <div class="wrap">
        <ul class="photoUl clearfix">
          <li class="li01 wow bounceInUp"> <a href="<?php echo $config['website_baseurl'];?>gamelobby.php">
            <div class="hov"><img src="<?php echo $cdnfullurl ?>img/home/icon05.png" alt=""><span><?php echo $tr['GoToGame']; ?></span></div>
            <div class="photo"><img src="<?php echo $cdnfullurl ?>img/home/photo01.jpg" alt=""></div>
            <p class="ttl"><?php echo $tr['Electronic entertainment']; ?></p>
            <p class="text">老虎机514倍奖金一拉即中，等你来战</p>
            </a> </li>
          <li class="li02 wow bounceInUp" data-wow-delay="100ms"> <a href="<?php echo $config['website_baseurl'];?>gamelobby.php?mgc=Live">
            <div class="hov"><img src="<?php echo $cdnfullurl ?>img/home/icon05.png" alt=""><span><?php echo $tr['GoToGame']; ?></span></div>
            <div class="photo"><img src="<?php echo $cdnfullurl ?>img/home/photo02.jpg" alt=""></div>
            <p class="ttl"><?php echo $tr['Live video']; ?></p>
            <p class="text">性感美女真人视讯直播，24小时不打烊</p>
            </a> </li>
          <li class="li03 wow bounceInUp" data-wow-delay="200ms"> <a href="<?php echo $config['website_baseurl'];?>gamelobby.php?mgc=Fishing">
            <div class="hov"><img src="<?php echo $cdnfullurl ?>img/home/icon05.png" alt=""><span><?php echo $tr['GoToGame']; ?></span></div>
            <div class="photo"><img src="<?php echo $cdnfullurl ?>img/home/photo03.jpg" alt=""></div>
            <p class="ttl"><?php echo $tr['Fishing people']; ?></p>
            <p class="text">王者捕鱼,捕鱼大亨,深海大赢家</p>
            </a> </li>
          <li class="li04 wow bounceInUp" data-wow-delay="300ms"> <a href="<?php echo $config['website_baseurl'];?>gamelobby.php?mgc=Lottery">
            <div class="hov"><img src="<?php echo $cdnfullurl ?>img/home/icon05.png" alt=""><span><?php echo $tr['GoToGame']; ?></span></div>
            <div class="photo"><img src="<?php echo $cdnfullurl ?>img/home/photo04.jpg" alt=""></div>
            <p class="ttl"><?php echo $tr['Lottery game']; ?></p>
            <p class="text">六合彩，时时彩，北京赛车等多种游戏</p>
            </a> </li>
        </ul>
      </div>
    </div>


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
