<?php

// ----------------------------------------------------------------------------
// Features:	前端 -- 行銷UI樣式 (BANNER....)
// File Name:	ui.php
// Author:		Joyce
// Related: home.php
// Log:
// 2017.07.3
// ----------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// 各首頁Banner和AD Block
// ----------------------------------------------------------------------------

// main home
$ui['Banner_home'] = '
<style>
html,
body {
  height: 100%;
}
.carousel {
  width: 100%;
  background-color: #000;
  height: 100%;
}
.carousel-fade .carousel-inner .item {
  -webkit-transition-property: opacity;
  transition-property: opacity;
}
.carousel-fade .carousel-inner .item,
.carousel-fade .carousel-inner .active.left,
.carousel-fade .carousel-inner .active.right {
  opacity: 0;
}
.carousel-fade .carousel-inner .active,
.carousel-fade .carousel-inner .next.left,
.carousel-fade .carousel-inner .prev.right {
  opacity: 1;
}
.carousel-fade .carousel-inner .next,
.carousel-fade .carousel-inner .prev,
.carousel-fade .carousel-inner .active.left,
.carousel-fade .carousel-inner .active.right {
  left: 0;
  -webkit-transform: translate3d(0, 0, 0);
  transform: translate3d(0, 0, 0);
}
.carousel-fade .carousel-control {
  z-index: 2;
  display: flex;
  justify-content: center;
  align-items: center;
}
.carousel-fade .carousel-control .glyphicon {
  font-size: 6rem;
}
.carousel,
.carousel-inner,
.carousel-inner .item {
  height: 100%;
}
.stopfade {
  opacity: 0.5;
}
.slide-content {
  color: #fff;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  min-height: 100%;
}
.slide-content img {
  position: absolute;
  top: 50%;
  left: 50%;
  min-width: 100%;
  min-height: 100%;
  width: auto;
  height: auto;
  z-index: 0;
  -webkit-transform: translateX(-50%) translateY(-50%);
  transform: translateX(-50%) translateY(-50%);
  -webkit-transition: 1s opacity;
  transition: 1s opacity;
}
.slide-content img::-webkit-media-controls-start-playback-button {
  display: none !important;
  -webkit-appearance: none;
}
</style>
<div id="carousel" class="carousel slide carousel-fade" data-ride="carousel" data-interval="false">
  <ol class="carousel-indicators">
      <li data-target="#carousel" data-slide-to="0" class="active"></li>
      <li data-target="#carousel" data-slide-to="1"></li>
      <li data-target="#carousel" data-slide-to="2"></li>
      <li data-target="#carousel" data-slide-to="3"></li>
		  <li data-target="#carousel" data-slide-to="4"></li>
      <li data-target="#carousel" data-slide-to="5"></li>
  </ol>

  <!-- Carousel items -->
  <div class="carousel-inner">
    <div class="carousel-item active">
       <a href="' . $config['ec_protocol'] . '://' . $config['ec_host'] . '/index.php?route=product/product&product_id=50"><img src="'.$cdnfullurl.'img/home/homebanner_food01.jpg" /></a>
    </div>
    <div class="carousel-item">
       <a href="' . $config['ec_protocol'] . '://' . $config['ec_host'] . '/index.php?route=product/product&product_id=51"><img src="'.$cdnfullurl.'img/home/homebanner_clothes01.jpg" /></a>
    </div>
    <div class="carousel-item">
       <a href="' . $config['ec_protocol'] . '://' . $config['ec_host'] . '/index.php?route=product/product&product_id=52"><img src="'.$cdnfullurl.'img/home/homebanner_living01.jpg" /></a>
      </div>
    <div class="carousel-item">
       <a href="' . $config['ec_protocol'] . '://' . $config['ec_host'] . '/index.php?route=product/product&product_id=53"><img src="'.$cdnfullurl.'img/home/homebanner_travel01.jpg" /></a>
    </div>
    <div class="carousel-item">
        <a href="' . $config['ec_protocol'] . '://' . $config['ec_host'] . '/index.php?route=product/product&product_id=54"><img src="'.$cdnfullurl.'img/home/homebanner_edu01.jpg" /></a>
    </div>
        <div class="carousel-item">
      <a href="gamelobby.php"><img src= "'.$cdnfullurl.'img/home/homebanner_fun01.jpg" /></a>
    </div>
  </div>

  <a class="carousel-control-prev left" href="#carousel" data-slide="prev">
   <span class="glyphicon glyphicon-chevron-left"></span>
  </a>

  <a class="carousel-control-next right" href="#carousel" data-slide="next">
   <span class="glyphicon glyphicon-chevron-right"></span>
  </a>

</div>
<script>

$(".carousel").carousel({
  interval: 5000
})
</script>
';

$ui['Block_home'] = '
<div class="row m_feature">
<!-- feature-block 1 -->
<a href="./promotions.php">
<div class="feature feature-left bg-light">
<div class="feature block">
<div class="feature-icon"><span class="lnr lnr-gift"></span></div>
'.$tr['Home Info 01'].'
</div>
</div>
</a>

<!-- feature-block 2 -->
<a href="./partner.php" target="_BLANK">
<div class="feature feature-center bg-dark">
<div class="feature block">
<div class="feature-icon"><span class="lnr lnr-diamond"></span></div>
'.$tr['Home Info 02'].'
</div>
</div>
</a>

<!-- feature-block 3 -->
<a href="./register.php">
<div class="feature feature-right bg-light">
<div class="feature block">
<div class="feature-icon"><span class="lnr lnr-exit"></span></div>
'.$tr['Home Info 03'].'
</div>
</div>
</a>

</div>

<div class="row m_title">
        <div class="m_title col-12">
        <h2><span>new arrivals</span></h2>
        </div>
</div>

<div class="row m_projects">
	<div class="col-12">
    <ul id="game-box">
      <li data-img="mg_roulette">
          <a href="gamelobby.php?g=Roulette">
              <div>
                '.$tr['img 03'].'
              </div>
          </a>
      </li>
      <li data-img="mg_slot">
          <a href="gamelobby.php?g=Advanced+Slots">
              <div>
               '.$tr['img 04'].'
              </div>
          </a>
      </li>
      <li data-img="mg_baccarat">
          <a href="gamelobby.php?g=Poker">
              <div>
                '.$tr['img 05'].'
              </div>
          </a>
      </li>
      <li data-img="more">
          <a href="contactus.php">
              <div>
                '.$tr['img 06'].'
              </div>
          </a>
      </li>
      <li data-img="home_food_project01">
        <a href="' . $config['ec_protocol'] . '://' . $config['ec_host'] . '/index.php?route=product/product&product_id=50">
          <div>'.$tr['img 06'].'</div>
        </a>
      </li>
      <li data-img="home_clothing_project02">
        <a href="' . $config['ec_protocol'] . '://' . $config['ec_host'] . '/index.php?route=product/product&product_id=51">
          <div>'.$tr['img 06'].'</div>
        </a>
      </li>
      <li data-img="home_live_project02">
        <a href="' . $config['ec_protocol'] . '://' . $config['ec_host'] . '/index.php?route=product/product&product_id=52">
          <div>'.$tr['img 06'].'</div>
        </a>
      </li>
      <li data-img="home_travel_project02">
        <a href="' . $config['ec_protocol'] . '://' . $config['ec_host'] . '/index.php?route=product/product&product_id=53">
          <div>'.$tr['img 06'].'</div>
        </a>
      </li>
  </ul>
 </div>
</div>
';

// For Food Home
$ui['Banner_home_food'] = '
	<div id="banner" class="picBtnTop">
		<div class="bd">
			<ul>
				<li>
<div class="pic"><img src="'.$cdnfullurl.'img/home/homebanner_food01.jpg" /></div>
				</li>
			</ul>
		</div>
	</div>
	<script type="text/javascript">
		jQuery("#banner").slide({
			mainCell: ".bd ul",
			effect: "fold",
			autoPlay: true,
      delayTime:1000,
			triggerTime: 50
		});
	</script>
';

$ui['Block_home_food'] = '
<div class="row m_feature">
<!-- feature-block 1 -->
<a href="./register.php">
<div class="feature feature-left bg-light">
<div class="feature block">
<div class="feature-icon"><span class="lnr lnr-gift"></span></div>
'.$tr['Home Info 01'].'
</div>
</div>
</a>

<!-- feature-block 2 -->
<a href="./promotions.php">
<div class="feature feature-center bg-dark">
<div class="feature block">
<div class="feature-icon"><span class="lnr lnr-diamond"></span></div>
'.$tr['Home Info 02'].'
</div>
</div>
</a>

<!-- feature-block 3 -->
<a href="./register.php">
<div class="feature feature-right bg-light">
<div class="feature block">
<div class="feature-icon"><span class="lnr lnr-exit"></span></div>
'.$tr['Home Info 03'].'
</div>
</div>
</a>

</div>

<div class="row m_title">
        <div class="m_title col-12">
        <h2><span>new arrivals</span></h2>
        </div>
</div>

<div class="row m_projects">
				<div class="col-12">
        <ul id="game-box">
         <li data-img="home_food_project01">
            <a href="http://jangmt.com/o/cart/index.php?route=product/product&path=20&product_id=51">
                <div>
                  異國美食
                </div>
            </a>
        </li>
        <li data-img="home_food_project02">
            <a href="http://jangmt.com/o/cart/index.php?route=product/product&path=20&product_id=51">
                <div>
                 地方小吃
                </div>
            </a>
        </li>
        <li data-img="home_food_project03">
            <a href="http://jangmt.com/o/cart/index.php?route=product/product&path=20&product_id=51">
                <div>
                  精緻甜點
                </div>
            </a>
        </li>
        <li data-img="home_food_project04">
            <a href="contactus.php">
                <div>
                  '.$tr['img 06'].'
                </div>
            </a>
        </li>
      </ul>
				</div>
</div>
';

// For Clothing Home
$ui['Banner_home_clothing'] = '
	<div id="banner" class="picBtnTop">
		<div class="bd">
			<ul>
				<li>
<div class="pic"><img src="'.$cdnfullurl.'img/home/homebanner_clothes01.jpg" /></div>
				</li>
			</ul>
		</div>
	</div>
	<script type="text/javascript">
		jQuery("#banner").slide({
			mainCell: ".bd ul",
			effect: "fold",
			autoPlay: true,
      delayTime:1000,
			triggerTime: 50
		});
	</script>
';

$ui['Block_home_clothing'] = '
<div class="row m_feature">
<!-- feature-block 1 -->
<a href="./register.php">
<div class="feature feature-left bg-light">
<div class="feature block">
<div class="feature-icon"><span class="lnr lnr-gift"></span></div>
'.$tr['Home Info 01'].'
</div>
</div>
</a>

<!-- feature-block 2 -->
<a href="./promotions.php">
<div class="feature feature-center bg-dark">
<div class="feature block">
<div class="feature-icon"><span class="lnr lnr-diamond"></span></div>
'.$tr['Home Info 02'].'
</div>
</div>
</a>

<!-- feature-block 3 -->
<a href="./register.php">
<div class="feature feature-right bg-light">
<div class="feature block">
<div class="feature-icon"><span class="lnr lnr-exit"></span></div>
'.$tr['Home Info 03'].'
</div>
</div>
</a>

</div>

<div class="row m_title">
        <div class="m_title col-12">
        <h2><span>new arrivals</span></h2>
        </div>
</div>

<div class="row m_projects">
				<div class="col-12">
        <ul id="game-box">
         <li data-img="home_clothing_project01">
            <a href="http://jangmt.com/o/cart/index.php?route=product/category&path=18">
                <div>
                 男仕服飾
                </div>
            </a>
        </li>
        <li data-img="home_clothing_project02">
            <a href="http://jangmt.com/o/cart/index.php?route=product/category&path=18">
                <div>
                女仕服飾
                </div>
            </a>
        </li>
        <li data-img="home_clothing_project03">
            <a href="http://jangmt.com/o/cart/index.php?route=product/category&path=18">
                <div>
                精品配件
                </div>
            </a>
        </li>
        <li data-img="home_clothing_project04">
            <a href="contactus.php">
                <div>
                  '.$tr['img 06'].'
                </div>
            </a>
        </li>
      </ul>
				</div>
</div>
';

// For Live Home
$ui['Banner_home_live'] = '

	<div id="banner" class="picBtnTop">
		<div class="bd">
			<ul>
				<li>
<div class="pic"><img src="'.$cdnfullurl.'img/home/homebanner_living01.jpg" /></div>
				</li>
			</ul>
		</div>
	</div>
	<script type="text/javascript">
		jQuery("#banner").slide({
			mainCell: ".bd ul",
			effect: "fold",
			autoPlay: true,
      delayTime:1000,
			triggerTime: 50
		});
	</script>
';

$ui['Block_home_live'] = '
<div class="row m_feature">
<!-- feature-block 1 -->
<a href="./register.php">
<div class="feature feature-left bg-light">
<div class="feature block">
<div class="feature-icon"><span class="lnr lnr-gift"></span></div>
'.$tr['Home Info 01'].'
</div>
</div>
</a>

<!-- feature-block 2 -->
<a href="./promotions.php">
<div class="feature feature-center bg-dark">
<div class="feature block">
<div class="feature-icon"><span class="lnr lnr-diamond"></span></div>
'.$tr['Home Info 02'].'
</div>
</div>
</a>

<!-- feature-block 3 -->
<a href="./register.php">
<div class="feature feature-right bg-light">
<div class="feature block">
<div class="feature-icon"><span class="lnr lnr-exit"></span></div>
'.$tr['Home Info 03'].'
</div>
</div>
</a>

</div>

<div class="row m_title">
        <div class="m_title col-12">
        <h2><span>new arrivals</span></h2>
        </div>
</div>

<div class="row m_projects">
				<div class="col-12">
        <ul id="game-box">
         <li data-img="home_live_project01">
            <a href="http://jangmt.com/o/cart/index.php?route=product/category&path=25">
                <div>
                  辦公書房
                </div>
            </a>
        </li>
        <li data-img="home_live_project02">
            <a href="http://jangmt.com/o/cart/index.php?route=product/category&path=25">
                <div>
                 生活起居
                </div>
            </a>
        </li>
        <li data-img="home_live_project03">
            <a href="http://jangmt.com/o/cart/index.php?route=product/category&path=25">
                <div>
                 臥室寢具
                </div>
            </a>
        </li>
        <li data-img="home_live_project04">
            <a href="contactus.php">
                <div>
                  '.$tr['img 06'].'
                </div>
            </a>
        </li>
      </ul>
				</div>
</div>
';

// For Travel Home
$ui['Banner_home_travel'] = '
	<div id="banner" class="picBtnTop">
		<div class="bd">
			<ul>
				<li>
<div class="pic"><img src="'.$cdnfullurl.'img/home/homebanner_travel01.jpg" /></div>
				</li>
			</ul>
		</div>
	</div>
	<script type="text/javascript">
		jQuery("#banner").slide({
			mainCell: ".bd ul",
			effect: "fold",
			autoPlay: true,
      delayTime:1000,
			triggerTime: 50
		});
	</script>

';

$ui['Block_home_travel'] = '
<div class="row m_feature">
<!-- feature-block 1 -->
<a href="./register.php">
<div class="feature feature-left bg-light">
<div class="feature block">
<div class="feature-icon"><span class="lnr lnr-gift"></span></div>
'.$tr['Home Info 01'].'
</div>
</div>
</a>

<!-- feature-block 2 -->
<a href="./promotions.php">
<div class="feature feature-center bg-dark">
<div class="feature block">
<div class="feature-icon"><span class="lnr lnr-diamond"></span></div>
'.$tr['Home Info 02'].'
</div>
</div>
</a>

<!-- feature-block 3 -->
<a href="./register.php">
<div class="feature feature-right bg-light">
<div class="feature block">
<div class="feature-icon"><span class="lnr lnr-exit"></span></div>
'.$tr['Home Info 03'].'
</div>
</div>
</a>

</div>

<div class="row m_title">
        <div class="m_title col-12">
        <h2><span>new arrivals</span></h2>
        </div>
</div>

<div class="row m_projects">
				<div class="col-12">
        <ul id="game-box">
         <li data-img="home_travel_project01">
            <a href="http://jangmt.com/o/cart/index.php?route=product/category&path=17">
                <div>
                促銷機票
                </div>
            </a>
        </li>
        <li data-img="home_travel_project02">
            <a href="http://jangmt.com/o/cart/index.php?route=product/category&path=17">
                <div>
                南國風情
                </div>
            </a>
        </li>
        <li data-img="home_travel_project03">
            <a href="http://jangmt.com/o/cart/index.php?route=product/category&path=17">
                <div>
                旅行必備
                </div>
            </a>
        </li>
        <li data-img="home_travel_project04">
            <a href="contactus.php">
                <div>
                  '.$tr['img 06'].'
                </div>
            </a>
        </li>
      </ul>
				</div>
</div>
';

// For Education Home
$ui['Banner_home_edu'] = '
	<div id="banner" class="picBtnTop">
		<div class="bd">
			<ul>
				<li>
<div class="pic"><img src="'.$cdnfullurl.'img/home/homebanner_edu01.jpg" /></div>
				</li>
			</ul>
		</div>
	</div>
	<script type="text/javascript">
		jQuery("#banner").slide({
			mainCell: ".bd ul",
			effect: "fold",
			autoPlay: true,
      delayTime:1000,
			triggerTime: 50
		});
	</script>
';

$ui['Block_home_edu'] = '
<div class="row m_feature">
<!-- feature-block 1 -->
<a href="./register.php">
<div class="feature feature-left bg-light">
<div class="feature block">
<div class="feature-icon"><span class="lnr lnr-gift"></span></div>
'.$tr['Home Info 01'].'
</div>
</div>
</a>

<!-- feature-block 2 -->
<a href="./promotions.php">
<div class="feature feature-center bg-dark">
<div class="feature block">
<div class="feature-icon"><span class="lnr lnr-diamond"></span></div>
'.$tr['Home Info 02'].'
</div>
</div>
</a>

<!-- feature-block 3 -->
<a href="./register.php">
<div class="feature feature-right bg-light">
<div class="feature block">
<div class="feature-icon"><span class="lnr lnr-exit"></span></div>
'.$tr['Home Info 03'].'
</div>
</div>
</a>

</div>

<div class="row m_title">
        <div class="m_title col-12">
        <h2><span>new arrivals</span></h2>
        </div>
</div>

<div class="row m_projects">
				<div class="col-12">
        <ul id="game-box">
         <li data-img="home_edu_project01">
            <a href="http://jangmt.com/o/cart/index.php?route=product/category&path=24">
                <div>
                 出版品、書籍
                </div>
            </a>
        </li>
        <li data-img="home_edu_project02">
            <a href="http://jangmt.com/o/cart/index.php?route=product/category&path=24">
                <div>
                文具、工具
                </div>
            </a>
        </li>
        <li data-img="home_edu_project03">
            <a href="http://jangmt.com/o/cart/index.php?route=product/category&path=24">
                <div>
                  線上課程
                </div>
            </a>
        </li>
        <li data-img="home_edu_project04">
            <a href="contactus.php">
                <div>
                  '.$tr['img 06'].'
                </div>
            </a>
        </li>
      </ul>
				</div>
</div>
';

// For Fun Home
$ui['Banner_home_fun'] = '
	<div id="banner" class="picBtnTop">
		<div class="bd">
			<ul>
				<li>
<div class="pic"><img src="'.$cdnfullurl.'img/home/homebanner_fun01.jpg" /></div>
				</li>
			</ul>
		</div>
	</div>
	<script type="text/javascript">
		jQuery("#banner").slide({
			mainCell: ".bd ul",
			effect: "fold",
			autoPlay: true,
      delayTime:1000,
			triggerTime: 50
		});
	</script>
';

$ui['Block_home_fun'] = '
<div class="row m_feature">
<!-- feature-block 1 -->
<a href="./register.php">
<div class="feature feature-left bg-light">
<div class="feature block">
<div class="feature-icon"><span class="lnr lnr-gift"></span></div>
'.$tr['Home Info 01'].'
</div>
</div>
</a>

<!-- feature-block 2 -->
<a href="./partner.php" target="_BLANK">
<div class="feature feature-center bg-dark">
<div class="feature block">
<div class="feature-icon"><span class="lnr lnr-diamond"></span></div>
'.$tr['Home Info 02'].'
</div>
</div>
</a>

<!-- feature-block 3 -->
<a href="./promotion.php">
<div class="feature feature-right bg-light">
<div class="feature block">
<div class="feature-icon"><span class="lnr lnr-exit"></span></div>
'.$tr['Home Info 03'].'
</div>
</div>
</a>

</div>

<div class="row m_title">
        <div class="m_title col-12">
        <h2><span>new arrivals</span></h2>
        </div>
</div>

<div class="row m_projects">
				<div class="col-12">
        <ul id="game-box">
        <li data-img="mg_roulette">
            <a href="lobby_mggameh5.php?gc=Roulette">
                <div>
                  '.$tr['img 03'].'
                </div>
            </a>
        </li>
        <li data-img="mg_slot">
            <a href="lobby_mggameh5.php?g=Advanced+Slots">
                <div>
                 '.$tr['img 04'].'
                </div>
            </a>
        </li>
        <li data-img="mg_baccarat">
            <a href="lobby_mggameh5.php?g=Poker">
                <div>
                  '.$tr['img 05'].'
                </div>
            </a>
        </li>
        <li data-img="home_fun_project04">
            <a href="lobby_mggameh5.php">
                <div>
                  '.$tr['img 06'].'
                </div>
            </a>
        </li>
      </ul>
				</div>
</div>
';


// For Admin Home
$ui['Banner_admin'] = '
<div id="slides" class="container">
  <div class="row m_slides">
    <div id="carousel" class="carousel slide" data-ride="carousel">

      <!-- Indicators 圓點按鈕 單張圖片註銷不顯示
      <ol class="carousel-indicators">
        <li data-target="#carousel" data-slide-to="0" class="active"></li>
        <li data-target="#carousel" data-slide-to="1"></li>
        <li data-target="#carousel" data-slide-to="2"></li>
        <li data-target="#carousel" data-slide-to="3"></li>
        <li data-target="#carousel" data-slide-to="4"></li>
        <li data-target="#carousel" data-slide-to="5"></li>
      </ol> -->

      <!-- Slides 項目﹙需手動新增上述圓點按鈕數量﹚-->
      <div class="carousel-inner" role="listbox">
        <div class="item active">
           <img src="'.$cdnfullurl.'img/home/staticbanner_01.jpg" alt="...">
        </div>
      </div>

      <!-- Controls 換頁箭頭 單張圖片註銷不顯示
      <a class="left carousel-control" href="#carousel" role="button" data-slide="prev">
        <i class="fas fa-chevron-left"></i>
        <span class="sr-only">Previous</span>
      </a>
      <a class="right carousel-control" href="#carousel" role="button" data-slide="next">
        <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
        <span class="sr-only">Next</span>
      </a>-->
    </div>
  </div>
</div>
';

$ui['Block_home_admin'] = '';


// ----------------------------------------------------------------------------
// 跑馬燈公告欄
// ----------------------------------------------------------------------------

// get_announcement_data()
// 取得跑馬燈的資訊, 輸出給 ui.php 使用, 配合 announcement_fullread.php 顯示完整的資訊
function get_announcement_data() {
	$ann_sql = "SELECT * FROM root_announcement WHERE status = '1' AND now() < endtime  AND effecttime < now() ORDER BY id LIMIT 100;";
	// var_dump($ann_sql);
	$ann_result = runSQLall($ann_sql);
	// var_dump($ann_result);

	$ann_data_html = '';
	for($i=1;$i<=$ann_result[0];$i++) {
			$ann_data_html = $ann_data_html.'<li><a href="#" onclick="window.open(\'announcement_fullread.php\', \'跑马灯公告栏\', config=\'left=300,top=100,menubar=no,status=no,toolbar=no,location=no,scrollbars=yes,height=600,width=500\');"><span>['.date("Y-m-d", strtotime($ann_result[$i]->effecttime)).']</span>'.$ann_result[$i]->title.'</a>
			</li>';
	}
		return($ann_data_html);
}
$ann_data_html = get_announcement_data();

// 此變數放置於 tmpl 版型檔案內
$ui['Scroll_marquee'] = '
<div id="marquee">
<div class="marqueebox">
<div class="container">
  <h3><span class="lnr lnr-volume-high"></span>'.$tr['latest announcenment'].'：</h3>
  <div class="marqueenew">
    <div class="bd">
      <div class="tempWrap" style="overflow:hidden; position:relative;">
        <ul class="marqueelist">
					'.$ann_data_html.'
         </ul>
      </div>
    </div>
  </div>
</div>
</div>
</div>
<script type="text/javascript">
  jQuery(".marqueenew").slide({
    mainCell: ".bd ul",
    autoPlay: true,
    effect: "leftMarquee",
    vis: 2,
    interTime: 50
  });
</script>
';
// ----------------------------------------------------------------------------
// 跑馬燈公告欄 end
// ----------------------------------------------------------------------------

?>
