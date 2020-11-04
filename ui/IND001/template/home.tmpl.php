<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- home 专用 - 给 guest 权限使用
// File Name:	home.tmpl.php
// Author:		Barkley
// Related:		home.php
// Log:
// 2016.10.18
// ----------------------------------------------------------------------------

// 有变数才执行，没有变数就是不正常进入此 tmpl 档案
if(isset($tmpl)) {
	// 正常
}else{
	die('ERROR');
}

$template_name ='home';
//header&footer
require_once dirname(__FILE__) ."/ui.php";

/*首页幻灯片*/
require_once dirname(dirname(__DIR__)) ."/component/carousel.php";
$data["index_carousel"]["desktop"]["item"] = json_decode('[{"img":"img/home/newCasino_03.png","link":"#"}]',true);
/*gametab_gp06*/
require_once dirname(dirname(__DIR__)) ."/component/gametab_gp06/gametab_gp06.php";

/*
// 选单共有 6 个放在 lib.php
// 会员没有登入的时候，显示这个选单
menu_guest_management()
// 会员登入前 and 登入后的选单内容
menu_admin_management()
// 语言选择列的选单
menu_language_choice()
// 会员登入的界面, 登入后显示余额及登出资讯
menu_login_ui()
// 系统上方中间功能选单
menu_features()
// 页脚显示
page_footer()

// 目前样本档 $tmpl 阵列, 共计有下面 8 个变数。
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
// 跑马灯
echo Scroll_marquee();
?>

<section id="home-carousel" class="index_block">
  <?php index_carousel(0,4000,400) ?>
</section>

<section id="home-link" class="index_block">
  <div id="home-sport-link" class="row">
    <div class="col">
      <a href="<?php echo $config['website_baseurl']; ?>gamelobby.php?mgc=Sport">
        <div class="item">
          <div class="box">
            <div class="img-box">
              <img src="<?php echo $cdnfullurl ?>img/home/NCDBP_210x204.png" alt="" />
            </div>
            <div class="rbox">
              <div class="sport-content">
                <div class="title">现场滚球盘</div>
                <div class="subtitle">比赛开始即可进行投注</div>
                <div class="discribe">我们提供最广泛的滚球盘服务</div>
                <div class="golink">立即投注</div>
              </div>
              <div class="sport-content">
                <div class="title">现场滚球盘</div>
                <div class="subtitle">比赛开始即可进行投注</div>
                <div class="discribe">我们提供最广泛的滚球盘服务</div>
                <div class="golink">立即投注</div>
              </div>
            </div>            
          </div>
        </div>     
      </a>     
    </div>
  </div>
  <div class="row">
    <div class="col">
      <a href="<?php echo $config['website_baseurl']; ?>gamelobby.php?mgc=game">
        <div class="item">
          <div class="img-box">
            <img src="<?php echo $cdnfullurl ?>img/home/bg-casino.png" alt="" />
          </div>
          <div class="content-box">
            <div class="title">
              游乐场
            </div>
            <div class="subtitle">
              新玩家奖金
            </div>
            <div class="discribe">
              超过250种精选游戏，包括最经典的现场荷官，精彩内容面向全部玩家
            </div>
            <div class="golink">
            <i class="fas fa-caret-right mr-2"></i>立刻开始
            </div>
          </div>   
        </div>     
      </a>     
    </div>
    <div class="col p-0">
      <a href="<?php echo $config['website_baseurl']; ?>gamelobby.php?mgc=Chessboard">
        <div class="item">
          <div class="img-box">
            <img src="<?php echo $cdnfullurl ?>img/home/bg-poker.png" alt="" />
          </div>
          <div class="content-box">
            <div class="title">
              扑克牌
            </div>
            <div class="subtitle">
              新玩家奖金
            </div>
            <div class="discribe">
              体验全球最奢华的真人娱乐世界，九大真人平台，全球首发，无忧博彩。
            </div>
            <div class="golink">
            <i class="fas fa-caret-right mr-2"></i>立刻开始
            </div>
          </div>  
        </div>      
      </a>     
    </div>
    <div class="col">
      <a href="<?php echo $config['website_baseurl']; ?>gamelobby.php?mgc=game">
        <div class="item">
          <div class="img-box">
            <img src="<?php echo $cdnfullurl ?>img/home/PharaohTreasure.png" alt="" />
          </div>
          <div class="content-box">
            <div class="title">
              游戏
            </div>
            <div class="subtitle">
              新玩家奖金
            </div>
            <div class="discribe">
              以老虎机到刮奖卡，我们种类丰富的在县游戏将让您体验娱乐无限的欢乐感受。
            </div>
            <div class="golink">
            <i class="fas fa-caret-right mr-2"></i>立刻开始
            </div>
          </div>   
        </div>     
      </a>     
    </div>
  </div>
</section>
<div class="w100">
  <!-- 热门游戏部分 -->  
    <!-- 首页gamelobby显示区域 -->
      <?php ui_gametab_gp06(1,'c','','',10,'1'); ?>
  <!-- 结束热门游戏 -->
</div>  

  <section id="home-foot" class="index_block">
    <div class="img-box">
      <img src="<?php echo $cdnfullurl ?>img/home/footer_tindex.png" alt="" />
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
        </body>
  </html>
