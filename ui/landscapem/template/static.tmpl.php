<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- static html 樣本頁面, 給靜態公告頁面的樣板
// File Name:	static.tmpl.php
// Author:		Barkley
// Related:		aboutus.php partner.php howtodeposit.php howtowithdraw.php contactus.php
// Log:
// 2016.10.18
// ----------------------------------------------------------------------------
//廣告們
require_once dirname(__FILE__) ."/adsense.php";
//UI樣式(BANNER...)
require_once dirname(__FILE__) ."/ui.php";

// 有變數才執行，沒有變數就是不正常進入此 tmpl 檔案
if(isset($tmpl)) {
	// 正常
}else{
	die('ERROR');
}

$cdn_url_logo				= $cdnfullurl.'img/common/logo.png';
$cdn_url_banner			= $cdnfullurl.'img/common/banner.png';
$template_name ='static';
//header&footer
require_once dirname(__FILE__) ."/header.tmpl.php";
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
if(isset($tmpl['sidebar_content'])){
  $sidebar_template=<<<HTML
  <div class="col-2 sidebar">
    <div class="sider-back">
      <img onclick="history.go(-1);" src="{$cdnfullurl}img/home/btn_back.png" alt="">
    </div>
   _siderbar-menu_ 
   </div>
HTML;
  $tmpl['sidebar_content'] = combine_sidebarmenu($tmpl['sidebar_content'],$sidebar_template);
}
?>


  <!DOCTYPE html>
  <html>

  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE11" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <link rel="shortcut icon" href="<?php echo $config['companyFavicon'] ?>">
    <meta name="description" content="<?php echo $tmpl['html_meta_description']; ?>">
    <meta name="author" content="<?php echo $tmpl['html_meta_author']; ?>">
    <title>
      <?php echo $tmpl['html_meta_title']; ?>
    </title>
    
	  <!-- head.js -->
	  <?php echo assets_include(); ?>
    <!-- custom -->
<link type="text/css" rel="stylesheet" href="<?php echo $cdnfullurl ?>css/style.css?ver_key_m=<?php echo $config['cdn_version_key'] ?>">
		<?php
			echo $tmpl['extend_head'];
			//搭配 adsense.php -- 右邊廣告的 CSS
			echo $ad['adsense_extend_head']; ?>
<style>
	#static #main .panel {
    border: 0;
    border-radius: 0 0 4px 4px;
}

.panel {
    -webkit-box-shadow: 0 1px 1px rgba(0,0,0,.05);
    background-color: #fff;
    border: 1px solid transparent;
    border-radius: 4px;
    box-shadow: 0 1px 1px rgba(0,0,0,.05);
    margin-bottom: 20px;
}

#static .pages {
    background-color: #f8e1cb;
    border: 1px solid #e1c2a4;
    padding: 15px;
}

    .swiper-container {
      width: 100%;
     /* height: 5rem;*/
    }
    .swiper-container .nav {
      flex-wrap: nowrap;
    }
    .swiper-slide {
      text-align: center;
      font-size: 18px;
      background: #fff;
      /* Center slide text vertically */
      display: -webkit-box;
      display: -ms-flexbox;
      display: -webkit-flex;
      display: flex;
      -webkit-box-pack: center;
      -ms-flex-pack: center;
      -webkit-justify-content: center;
      justify-content: center;
      -webkit-box-align: center;
      -ms-flex-align: center;
      -webkit-align-items: center;
      align-items: center;
    }


.test .col-md-2,
.test .col-sm-3,
.test .col-xs-6 {
  padding-right: 5px;
  padding-left: 5px;
}
.allcasinotab a {
  font-size:12px;
}
html {
  font-size:12px;
}
#searchform {
  padding-bottom: 5px;
}
.allcasinotab span.glyphicon {
  margin: 0 5px 0 0 !important;
}
#casinotab.nav > li.active > a {
  padding: 6px 10px;
}
#casinotab.nav > li > a {
  padding: 6px 10px;
}
#collapsemenu {
  padding: 5px 0;
}
.index_tabmenu li i {  
  font-size:18px;
  padding-bottom:5px;
}
#gNavi li a::before{
	content: "";
    position: absolute;
    top: 5px;
    left: calc(50% - 12px);
}

#gNavi li.navi_hot a::before{
	font: normal normal normal 24px/1 "Material Design Icons";
	font-size: 24px;
	content: "\F238";
}

#gNavi li.navi_game a::before{
	font: normal normal normal 24px/1 "Material Design Icons";
	font-size: 24px;
	content: "\F297";
}
#gNavi li.navi_Live a::before{
	font: normal normal normal 24px/1 "Material Design Icons";
	font-size: 24px;
	content: "\F1C8";
}
#gNavi li.navi_Lottery a::before{
	font: normal normal normal 24px/1 "Material Design Icons";
	font-size: 24px;
	content: "\F76D";
}
#gNavi li.navi_Fishing a::before{
	font: normal normal normal 24px/1 "Material Design Icons";
	font-size: 24px;
	content: "\F23A";
}
#gNavi li.navi_promotions a::before{
	font: normal normal normal 24px/1 "Material Design Icons";
	font-size: 24px;
	content: "\F2A1";
}
#gNavi li.navi_service a::before{
	font: normal normal normal 14px/1 FontAwesome;
	font-size: 24px;
	content: "\f086";
}
#gNavi li.navi_more a::before{
	content: "\f005";
	font: normal normal normal 14px/1 FontAwesome;
	font-size: 24px;
	left: calc(50% - 15px);
	top: 10px;
}
/* hover color */
#gNavi li a.on,#gNavi li a:hover{
	background-color: var(--item-hover) !important;
}

#gNavi li a {
    color: #382814;
    display: block;
    padding: 30px 0 15px;
    font-size: 12px;
}

@media (min-width: 768px) {
  .panel-heading {
    display: none;
  }
  .panel {
    border: none;
    box-shadow: none;
  }
  .panel-collapse {
    height: auto;
  }
  .panel-collapse.collapse {
    display: block;
  }
}
@media (max-width: 767px) {
  .tab-content .tab-pane {
    display: block;
  }
  .nav-tabs {
    display: none;
  }
  .panel-title a {
    display: block;
  }
  .panel {
    margin: 0;
    box-shadow: none;
    border-radius: 0;
    margin-top: -2px;
  }
  .tab-pane:first-child .panel {
    border-radius: 5px 5px 0 0;
  }
  .tab-pane:last-child .panel {
    border-radius: 0 0 5px 5px;
  }
}

	</style>

</head>
	<body id="static">
		<div id="wrapper">
			<!-- header -->
			<?php
				//echo $mobile_header;
			?>
			<!-- end header -->
			<!-- Marquee-->
			<?php
						// 跑馬燈
            //echo $ui['Scroll_marquee'];
          ?>
				<!-- end Marquee-->
			<!-- Main-->
      <div class="md-header mb-4">        
        <span class="md-title"><?php echo $function_title; ?></span>
      </div>
      <div class="row m-0">
      <?php
      // 側邊欄內容
      if(isset($tmpl['sidebar_content']))
        echo $tmpl['sidebar_content'];
      ?>
			<div id="md-main" class="col">
				<!-- content-->
				<div class="container">
				<!-- 	<div class="row">
						<div class="col-12">
							<?php
								// Breadcrumb
								//echo $tmpl['paneltitle_content'];
								?>
						</div>
					</div>-->
							<div class="card">
							<!--<h3 class="card-header">
								<?php
							// 工作區標題
							//echo $function_title;
							?>
							</h3>-->
								<div class="card-body">
									<?php
							// 工作區內容
							echo $tmpl['panelbody_content'];
							?>
								</div>
							</div>
						</div>
					</div>
        </div>
			<!-- end main container -->
			<!-- Footer -->
			<footer>
							<?php
						// 頁腳顯示
						//echo $mobile_footer;
				    ?>
					<div class="row f_config">
						<?php
	            // Javascript
	            echo $tmpl['extend_js'];
					?>
					</div>
			</footer>
		</div>
		<!-- end wrapper -->
	</body>

	</html>
