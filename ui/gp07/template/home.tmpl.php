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
$data["index_carousel"]["desktop"]["item"] = json_decode('[{"img":"img/home/banner01.jpg","link":"#"},{"img":"img/home/banner02.jpg","link":"#"},{"img":"img/home/banner03.jpg","link":"#"},{"img":"img/home/banner04.jpg","link":"#"}]',true);
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

<!--banner-->

<div class="mainImg clearfix wow fadeInUp">
  <div class="sliderBox03">
    <div class="foo">
      <ul class="clearfix">
        <li><a href="#"><img src="<?php echo $cdnfullurl ?>img/home/main_img1.png" alt=""></a></li>
        <li><a href="#"><img src="<?php echo $cdnfullurl ?>img/home/main_img2.png" alt=""></a></li>
        <li><a href="#"><img src="<?php echo $cdnfullurl ?>img/home/main_img3.png" alt=""></a></li>
        <li><a href="#"><img src="<?php echo $cdnfullurl ?>img/home/main_img4.png" alt=""></a></li>
      </ul>
    </div>
  </div>
</div>

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

            </div>

<!--section01-->
    <div class="section01 clearfix">
      <div class="lBox wow bounceInLeft">
        <div class="sliderBox">
          <div class="foo">
            <!-- 幻燈片-->

      <?php index_carousel(0,4000,300) ?>

          <!-- 幻燈片 -->
          </div>
          <div class="foopage"></div>
        </div>
      </div>
      <div class="rBox wow bounceInRight">
        <ul class="ulLink clearfix">
          <li class="li01"><a href="<?php echo $config['website_baseurl'];?>promotions.php"><span>优惠大厅</span></a></li>
          <li class="li02"><a href="<?php echo $config['website_baseurl'];?>register.php"><span>免费注册</span></a></li>
          <li class="li04"><a href="<?php echo $config['website_baseurl'];?>howtodeposit.php"><span>快速存款</span></a></li>
          <li class="li05"><a href="<?php echo $config['website_baseurl'];?>partner.php"><span>占成代理</span></a></li>
        </ul>
      </div>
    </div>
<!--section01 end-->

<!-- 熱門遊戲部分 -->      
    <!-- 首頁gamelobby顯示區域 -->
  <div class="section02">
    <div class="wow bounceInLeft">
    <?php ui_gametab_gp06(1,'c','','',12,'1'); ?>
  </div>
            <div class="rBox wow bounceInRight"> <img src="<?php echo $cdnfullurl ?>img/home/img32.png" class="img01" alt="">  <img src="<?php echo $cdnfullurl ?>img/home/img33.png" class="img02" alt=""> </div>
  </div>  
     <!-- gamelobby結束 -->
<!-- 結束熱門遊戲 -->

<!--section03 end -->
<script type="text/javascript">
  $(function(){
      $('.indexitemconlist > li').hover(
    function () {
      var $this = $(this);
      $this.siblings().stop().animate({'width':'100px'},400);
      $this.siblings().find("span").removeClass("on");
      $this.stop().animate({'width':'603px'},400);
      $this.find("span").addClass("on");
    },function(){
    }
  );
  });
</script>
    <div class="section03 wow flipInX">
      <div class="indexitem">
        <div class="indexitemcon w1000">
          <ul class="indexitemconlist">
            <li class="li01" style="width:603px"> <span class="t01 on"></span>
              <div class="con item01"><a href="<?php echo $config['website_baseurl'];?>gamelobby.php?mgc=Fishing">
                <p class="ttl">捕鱼达人</p>
                <p class="text">经典捕鱼万人在线，画质清晰，高爆率，绚丽画面以及简单轻松的玩法一直以来都深受电玩游戏玩家的青睐....</p>
                <p class="link"><a href="<?php echo $config['website_baseurl'];?>gamelobby.php?mgc=Fishing">进入游戏&gt;</a></p></a>
              </div>
            </li>
            <li class="li02"> <span class="t02"></span>
              <div class="con item02"><a href="<?php echo $config['website_baseurl'];?>gamelobby.php?mgc=Live">
                <p class="ttl">真人娱乐</p>
                <p class="text">奇迹电游携手七大真人平台BBIN /AG/ MG/ PT/ GPI/ EVO /欧博，玩法多样，高清美女荷官发牌犹如身临其境……</p>
                <p class="link"><a href="<?php echo $config['website_baseurl'];?>gamelobby.php?mgc=Live">进入游戏&gt;</a></p></a>
              </div>
            </li>
            <!--<li class="li03"> <span class="t03"></span>
              <div class="con item03">
                <p class="ttl">体育赛事</p>
                <p class="text">奇迹电游提供丰富的体育投注，内容涵盖足球、网球、篮球、棒球及乒乓球等。您可同时通过手机访问。</p>
                <p class="link"><a href="sports.html">进入游戏&gt;</a></p>
              </div>
            </li>-->
            <li class="li04"> <span class="t04"></span>
              <div class="con item04"><a href="<?php echo $config['website_baseurl'];?>gamelobby.php?mgc=Lottery">
                <p class="ttl">彩票游戏</p>
                <p class="text">奇迹电游拥有亚洲最好的彩票系统，涵盖东南亚所有最热门的彩票游戏，香港六合彩、重庆时时彩、PK10……一网打进 </p>
                <p class="link"><a href="<?php echo $config['website_baseurl'];?>gamelobby.php?mgc=Lottery">进入游戏&gt;</a></p></a>
              </div>
            </li>
            <li class="li05"> <span class="t05"></span>
              <div class="con item05"><a href="javascript:void(0);" onclick="msitechg('mobile'); return false;">
                <p class="ttl">手机投注</p>
                <p class="text">全面支持HTML5，平台所有游戏皆可进行投注，支持支付宝微信线上存款及线上取款....</p>
                <p class="link"><a href="javascript:void(0);" onclick="msitechg('mobile'); return false;">进入游戏&gt;</a></p></a>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>
<!--section03 end -->
<!--section4-->

    <div class="btmBox clearfix">
      <div class="lBox wow flipInX">
        <p class="title">游戏流程</p>
        <ul class="ulList clearfix">
          <li class=""><a href="<?php echo $config['website_baseurl'];?>register.php"><img src="<?php echo $cdnfullurl ?>img/home/img16.png" alt=""><span>免费开户</span></a></li>
          <li class="arrow"><img src="<?php echo $cdnfullurl ?>img/home/img17.png" alt=""></li>
          <li><a href="<?php echo $config['website_baseurl'];?>member.php"><img src="<?php echo $cdnfullurl ?>img/home/img18.png" alt=""><span>绑定资料</span></a></li>
          <li class="arrow"><img src="<?php echo $cdnfullurl ?>img/home/img17.png" alt=""></li>
          <li><a href="<?php echo $config['website_baseurl'];?>howtodeposit.php"><img src="<?php echo $cdnfullurl ?>img/home/img19.png" alt=""><span>账户存款</span></a></li>
          <li class="arrow"><img src="<?php echo $cdnfullurl ?>img/home/img17.png" alt=""></li>
          <li><a href="<?php echo $config['website_baseurl'];?>gamelobby.php"><img src="<?php echo $cdnfullurl ?>img/home/img20.png" alt=""><span>投注游戏</span></a></li>
          <li class="arrow"><img src="<?php echo $cdnfullurl ?>img/home/img17.png" alt=""></li>
          <li><a href="<?php echo $config['website_baseurl'];?>howtowithdraw.php"><img src="<?php echo $cdnfullurl ?>img/home/img21.png" alt=""><span>提款到账</span></a></li>
        </ul>
      </div>
      <div class="rBox wow flipInX">
        <p class="title">联系我们</p>
        <ul>
          <li class="li01">官方电话：<span>+1234-5678-567</span></li>
          <li class="li02">官方邮箱：<span>xxxx@gmail.com</span></li>
        </ul>
      </div>
    </div>

<!--section4-->
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
