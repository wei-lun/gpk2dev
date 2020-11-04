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

$cdn_url_logo				= $config['companylogo'];
$cdn_url_banner			= $cdnfullurl.'img/common/banner.png';
$wechat_qrcode_image_html = '<img id="qrcode_img" height="70px" src="'.$customer_service_cofnig['wechat_qrcode'].'">';

$template_name = 'gamelobby';
//header&footer
require_once dirname(__FILE__) ."/ui.php";
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
  <meta http-equiv="X-UA-Compatible" content="IE=edge"> */ ?>


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

    <script src="<?php echo $cdnfullurl_js; ?>ATBookmarkApp.js"></script>  
    <!-- Custom styles for this template -->
<script src="<?php echo $cdnfullurl_js; ?>js/common.js"></script>

    <link type="text/css" rel="stylesheet" href="<?php echo $cdnfullurl ?>css/style.css?version_key=<?php echo $config['cdn_version_key'] ?>">

<?php echo $tmpl['extend_head'];
    
       ?>  
  </head>
  <body id="home">                            
    <div class="wrapper bar-bg"> 
     <!-- header -->
      <header id="header">
        <div class="w-100">
          <div class="row">
            <div class="col-xs-3 col-md-3"></div>
            <div class="col-xs-9 col-md-9">
              <ul id="top-nav">               
                <li><a href="./" target="_self"><span class="glyphicon glyphicon-home" aria-hidden="true"></span><?php echo $tr['Return Home'] ?></a></li>
                <li><a href="./" target="_self"><span class="glyphicon glyphicon-phone" aria-hidden="true"></span>手机教学</a></li>
                <li><a id="bookmarkme" rel="sidebar" href="javascript:void(0)" onClick="return ATBookmarkApp.addBookmark(this)" title="战神"><span class="glyphicon glyphicon-heart" aria-hidden="true"></span>加入收藏</a></li>
                <li><a href="./partner.php" target="_self"><span class="glyphicon glyphicon-asterisk" aria-hidden="true"></span>代理加盟</a></li>
                <?php
                    // 會員選單
										// echo menu_admin_management('home');
										// 美東時間
                    echo menu_time();
                    // 左側選單(語言切換列)
										echo menu_language_choice();
                    ?>
              </ul>
            </div>
          </div>
          <div class="row">
            <div class="col-xs-3 col-md-3">
              <div id="logo-bg" style="width: 182px;height: 74px;background: url('<?php echo $config['companylogo']; ?>') no-repeat;background-size: 100%;">
                <a href="./"></a>                
              </div>
              <?php echo menu_short_url(); ?>
            </div>
            <div class="col-xs-9 col-md-9">
              <nav id="midadblock">
                  <div class="nav01 ng-scope" ng-controller="HomeCtrl">
                    <span><?php echo $tr['Promotions'] ?></span>
                    <span><a href="./promotions.php" target="_self"><img src="<?php echo $cdnfullurl ?>img/promotion/ad_promo.png" style="width: 100%;margin-top: 30px;"></a></span>
                  </div>
                  <div class="nav02">
                    <span>最新消息</span>
                    <div id="hot-news">
                        <!-- Marquee-->
                        <?php	echo Scroll_marquee(); ?>
                        <!-- end Marquee-->   
                  </div>
                  </div>
                  <div class="nav03">
                    <span>手机投注</span>
                    <div class="qrcode">
                    <?php echo $wechat_qrcode_image_html ?>
                  </div>
                  </div>
                  <div class="nav04">
                    <span>客服中心</span>
                    <ol>
                      <li class="nav-service01" onclick="window.open('http://wpd.b.qq.com/page/webchat.html?nameAccount=<?php echo $customer_service_cofnig['qq'] ?>', 'QQ', config='height=500,width=500');"><?php echo $customer_service_cofnig['qq'] ?></li>
                      <li class="nav-service02"><?php echo $customer_service_cofnig['email'] ?></li>
                      <li class="nav-service03"><?php echo $customer_service_cofnig['mobile_tel'] ?></li>
                      <li class="nav-service04" onclick="window.open('<?php echo $customer_service_cofnig['online_weblink'] ?>', '<?php echo $tr['online customer service'] ?>', config='height=800,width=700');"><?php echo $tr['online customer service'] ?></li>
                    </ol>
                  </div>
              </nav>
            </div>
          </div>
        </div>
      </header>
      <!-- end header -->

      <!-- Main-->
      <div id="main">
        <div class="w-100">
          <div class="row m-0">
            <div class="col-xs-3 col-md-3">

              <!-- 左邊欄登入資訊-->
 <?php
						// 登入切換
            echo $ui['index_login'];
          ?>
  <!-- 左邊欄固定廣告-->
              <div id="sideblock">
                <svg id="nav-game" xmlns="http://www.w3.org/2000/svg" width="190" height="329">
            <defs>
                <pattern id="live01" patternUnits="objectBoundingBox" width="1" height="1">
                    <image xlink:href="<?php echo $cdnfullurl ?>img/common/navgame01.png" width="190" height="126"></image>
                </pattern>
                <pattern id="live02" patternUnits="objectBoundingBox" width="1" height="1">
                    <image xlink:href="<?php echo $cdnfullurl ?>img/common/navgame01_h.png" width="190" height="126"></image>
                </pattern>
                <pattern id="game01" patternUnits="objectBoundingBox" width="1" height="1">
                    <image xlink:href="<?php echo $cdnfullurl ?>img/common/navgame02.png" width="183" height="182"></image>
                </pattern>
                <pattern id="game02" patternUnits="objectBoundingBox" width="1" height="1">
                    <image xlink:href="<?php echo $cdnfullurl ?>img/common/navgame02_h.png" width="183" height="182"></image>
                </pattern>
                <pattern id="sport01" patternUnits="objectBoundingBox" width="1" height="1">
                    <image xlink:href="<?php echo $cdnfullurl ?>img/common/navgame03.png" width="166" height="132"></image>
                </pattern>
                <pattern id="sport02" patternUnits="objectBoundingBox" width="1" height="1">
                    <image xlink:href="<?php echo $cdnfullurl ?>img/common/navgame03_h.png" width="166" height="132"></image>
                </pattern>
            </defs>
            <g id="live">
                <a xlink:href="./gamelobby.php?mgc=Live">
                    <polygon id="live" points="0 126,183 77,190 0,0 0"></polygon>
                </a>
            </g>
            <g id="game">
                <a xlink:href="./gamelobby.php">
                    <polygon id="game" points="0 192,167 262,183 82,0 132"></polygon>
                </a>
            </g>
            <g id="sport">
                <a xlink:href="./gamelobby.php?mgc=Lottery">
                    <polygon id="sport" points="0 330,161 330,166 269,0 198"></polygon>
                </a>
            </g>
        </svg>
              </div>
              <!-- 左邊欄固定廣告 end-->
            </div>
            <div class="col-xs-9 col-md-9">
              <!-- 中間欄廣告 -->
              <ul id="game-box">
                <li data-img="live">
                  <a href="./gamelobby.php?mgc=Live"></a>
                </li>
                <li data-img="game">
                  <a href="./gamelobby.php"></a>
                </li>
                <li data-img="sport">
                  <a href="./gamelobby.php?mgc=Lottery"></a>
                </li>
              </ul>
              <!-- 中間欄廣告 end-->
            </div>
          </div>
        </div>
        <!-- end Main -->
      </div>
    </div>
    <!-- end wrapper -->

      <!-- Footer -->
      <?php echo $footer_templ ?>
      <!-- end Footer -->

      <!-- 浮動廣告 -->
<?php
	            // Javascript
	            echo $tmpl['extend_js'];
							// 搭配 adsense.php 需 include
               
              
					?>

  </body>

  </html>
