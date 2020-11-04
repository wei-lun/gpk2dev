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
$data["index_carousel"]["desktop"]["item"] = json_decode('[{"img":"img/home/lun1.jpg","link":"#"},{"img":"img/home/lun2.jpg","link":"#"},{"img":"img/home/lun3.jpg","link":"#"},{"img":"img/home/lun4.jpg","link":"#"}]',true);

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
<div class="container">
<?php
// 跑馬燈
echo Scroll_marquee();
?>

<div class="row">
  <div class="lbox">
<!-- 幻燈片部分 -->
      <div class="section_carousel">
            <!-- 幻燈片-->

      <?php index_carousel(0,4000,446) ?>

          <!-- 幻燈片 -->
    </div>
<!-- 結束幻燈片部分 -->
</div>

<div class="rbox">

<!--平台圖-->
          <div id="rCarousel" class="carousel slide">
            <!-- 轮播（Carousel）指标 -->
            <ol class="carousel-indicators">
              <li data-target="#rCarousel" data-slide-to="0" class="active">GPK平台</li>
              <li data-target="#rCarousel" data-slide-to="1">IG平台</li>
              <li data-target="#rCarousel" data-slide-to="2">MG平台</li>
              <li data-target="#rCarousel" data-slide-to="3">PT平台</li>
            </ol>   
            <!-- 轮播（Carousel）项目 -->
            <div class="carousel-inner">
              <div class="carousel-item item active">
                <a href="<?php echo $config['website_baseurl'];?>gamelobby.php"><img src="<?php echo $cdnfullurl ?>img/home/11.jpg" alt="First slide"></a>
              </div>
              <div class="carousel-item item">
                <a href="<?php echo $config['website_baseurl'];?>gamelobby.php"><img src="<?php echo $cdnfullurl ?>img/home/66.jpg" alt="Second slide"></a>
              </div>
              <div class="carousel-item item">
                <a href="<?php echo $config['website_baseurl'];?>gamelobby.php"><img src="<?php echo $cdnfullurl ?>img/home/33.jpg" alt="Third slide"></a>
              </div>
              <div class="carousel-item item">
                <a href="<?php echo $config['website_baseurl'];?>gamelobby.php"><img src="<?php echo $cdnfullurl ?>img/home/44.jpg" alt="Fourth slide"></a>
              </div>
            </div>
            <!-- 轮播（Carousel）导航 
            <a class="left carousel-control" href="#rCarousel" role="button" data-slide="prev">
              <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
              <span class="sr-only">Previous</span>
            </a>
            <a class="right carousel-control" href="#rCarousel" role="button" data-slide="next">
              <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
              <span class="sr-only">Next</span>
            </a>-->
          </div> 
          <script>
            $('#rCarousel').carousel({
                interval: 4000
            })
          </script>
<!---->

</div>
    </div>
    </div>

<?php require_once dirname(dirname(__DIR__)) ."/component/gametab_gp05/gametab_gp05.php"; ?>

  
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
