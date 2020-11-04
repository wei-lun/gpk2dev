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

<!-- -----------Main----------------->
<div id="main">
    <div class="section01">
      <div class="wrap clearfix">
        <div class="lBox">

<!-- 幻燈片部分 -->
<?php index_carousel(0,4000) ?>
<!-- 結束幻燈片部分 -->

<div class="row" style="height: 30px;"></div>
<!-- 熱門遊戲部分 -->
   <h2 class="wow fadeInUp"><img src="<?php echo $cdnfullurl ?>img/home/h2_img.png" alt=""></h2>   
  <!-- 首頁gamelobby顯示區域 -->
    <?php ui_gametab_gp06(1,'c','','',8,'1'); ?>
<!-- 結束熱門遊戲 -->

        </div>
        <div class="rBox">
      <div class="logSection">
      <div class="login animated flipInX">

        <?php // 會員登入的界面, 登入後顯示餘額及登出資訊
              echo menu_login_ui();
              ?>

      </div>
    </div>  
      <ul class="linkUl clearfix wow fadeInUp">
        <li class="li01"><a href="<?php echo $config['website_baseurl']; ?>gamelobby.php?mgc=Live"><span><img src="<?php echo $cdnfullurl ?>img/home/s_img01.png" alt=""></span><?php echo $tr['Live video']; ?></a></li>
        <li class="li02"><a href="<?php echo $config['website_baseurl']; ?>gamelobby.php?mgc=Lottery"><span><img src="<?php echo $cdnfullurl ?>img/home/s_img02.png" alt=""></span><?php echo $tr['Lottery game']; ?></a></li>
        <li class="li03"><a href="<?php echo $config['website_baseurl'];?>gamelobby.php?mgc=Fishing"><span><img src="<?php echo $cdnfullurl ?>img/home/s_img03.png" alt=""></span><?php echo $tr['Fishing people']; ?></a></li>
        <li class="li04"><a href="javascript:void(0);" onclick="msitechg('mobile'); return false;"><span><img src="<?php echo $cdnfullurl ?>img/home/s_img04.png" alt=""></span>手机投注</a></li>
      </ul>
      <div class="banner animated flipInX"><a href="<?php echo $config['website_baseurl']; ?>partner.php"><img src="<?php echo $cdnfullurl ?>img/home/s_banner.jpg" alt=""></a></div>

    </div>
      </div>
      </div>
     
  </div>
  </div>
<!------ end Main ------------>

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
