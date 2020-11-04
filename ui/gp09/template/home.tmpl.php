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
$data["index_carousel"]["desktop"]["item"] = json_decode('[{"img":"img/home/index_banner.jpg","link":"#"}]',true);
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

<!-- Main-->
<div id="main">
<div class="w-1000">
<?php
// 跑馬燈
echo Scroll_marquee();
?>

<section class="index_block">
  <?php index_carousel(0,4000,400) ?>
</section>

<section class="index_block ">
  <div class="row sense_banner01">
    <div class="sense_item">
      <div class="wow bounceIn row sense_icon"><img src="<?php echo $cdnfullurl ?>img/home/content_icon_01.png" alt="" /></div>
      <div class="row sense_title">全球最大</div>
      <div class="row sense_content">拥有最多客户的博彩公司。</div>
    </div>
    <div class="sense_item">
      <div class="wow bounceIn row sense_icon" data-wow-delay="0.3s"><img src="<?php echo $cdnfullurl ?>img/home/content_icon_02.png" alt="" /></div>
      <div class="row sense_title">值得您信赖的公司</div>
      <div class="row sense_content">专业监管娱乐场、游戏。</div>
    </div>
    <div class="sense_item">
    <div class="wow bounceIn row sense_icon" data-wow-delay="0.6s"><img src="<?php echo $cdnfullurl ?>img/home/content_icon_03.png" alt="" /></div>
      <div class="row sense_title">顾客至上</div>
      <div class="row sense_content">各项完善客户服务。</div>
    </div>
  </div>
</section>
<div class="w100">
  <!-- 熱門遊戲部分 -->  
    <!-- 首頁gamelobby顯示區域 -->
      <?php ui_gametab_gp06(1,'c','','',10,'1'); ?>
  <!-- 結束熱門遊戲 -->
</div>   
<section class="index_block sense_link">
  <div class="wow bounceInLeft row link_item">
    <div class="link_content">
      <div class="row link_title">真人娱乐场</div>
      <div class="row link_txt">让我们的现场荷官邀您入座！</div>
      <div class="row link_btn"><a href="<?php echo $config['website_baseurl']; ?>gamelobby.php?mgc=Live">立刻开始</a></div>
    </div>
    <div class="link_img"><img src="<?php echo $cdnfullurl ?>img/home/live_img.png" alt="" /></div>
  </div>
  <div class="wow bounceInRight row link_item">
    <div class="link_content">
      <div class="row link_title">电子游艺</div>
      <div class="row link_txt">超高爆奖率、超多累积大奖</div>
      <div class="row link_btn"><a href="<?php echo $config['website_baseurl']; ?>gamelobby.php?mgc=game">立刻开始</a></div>
    </div>
    <div class="link_img"><img src="<?php echo $cdnfullurl ?>img/home/slots_img.png" alt="" /></div>
  </div>
  <div class="wow bounceInLeft row link_item">
    <div class="link_content">
      <div class="row link_title">捕鱼达人</div>
      <div class="row link_txt">各式捕鱼游戏任你玩！</div>
      <div class="row link_btn"><a href="<?php echo $config['website_baseurl']; ?>gamelobby.php?mgc=Fishing">立刻开始</a></div>
    </div>
    <div class="link_img"><img src="<?php echo $cdnfullurl ?>img/home/fish_img.png" alt="" /></div>
  </div>
  <div class="wow bounceInRight row link_item">
    <div class="link_content">
      <div class="row link_title">彩票投注</div>
      <div class="row link_txt">各类彩票游戏祝您中得大奖</div>
      <div class="row link_btn"><a href="<?php echo $config['website_baseurl']; ?>gamelobby.php?mgc=Lottery">立刻开始</a></div>
    </div>
    <div class="link_img"><img src="<?php echo $cdnfullurl ?>img/home/lotto_img.png" alt="" /></div>
  </div>
</section>
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
        </body>
  </html>
