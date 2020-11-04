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
    <script type="text/javascript">
      function sumFormat(item){
        var _item = $("."+item);
        _item.each(function(){
          var timer = null,
            _wrap = $(this);
            _whtml = _wrap.html(),
            _warr = _whtml.split(","),
            _wnum = parseFloat(_warr.join(""),2);
            _wnum = _wnum*100;
            _timer = setInterval(function(){
              _wnum+= parseInt(Math.random()*2000);
              var _flag = parseFloat(_wnum/100,2)+"",
                _flagarr = _flag.split("."),
                _flagarr1 = _flagarr[0],
                _flagarr2 = _flagarr[1]?_flagarr[1]:"00",
                _flaghtml = "."+_flagarr2,
                j=0;
              for(var i=_flagarr1.length; i>0 ; i--){
                j++;
                if(j%3==0 && i!=1){
                  _flaghtml=","+_flagarr1[i-1]+_flaghtml;
                }else{
                  _flaghtml=_flagarr1[i-1]+_flaghtml;
                }
              }
              _wrap.html(_flaghtml);
            },1000);
        });
      };
      function myscrolltop(wrap,item,h){
        var _wrap = $("#"+wrap),
          _item = _wrap.find(item),
          _h = 0;
          _item.each(function(){
            _h+= $(this).outerHeight(true,true);
          });
          if(_h>h){
            var _timer = null;
            _whtml = _wrap.html();
            _wrap.html(_whtml+_whtml);
            _wrap.height(_h*2);
            function fn(){
              var _curtop = parseInt(_wrap.css("top"));
              _curtop--;
              if(_curtop == -_h){
                _curtop = 0;
              }
              _wrap.css("top",_curtop);
            }
            _timer = setInterval(function(){
              fn();
            },50)
          }
      }
    </script>
  </head>
  <body id="home">
    <div id="wrapper">

  <!-- header -->
  <?php echo $header_templ; ?>
  <!-- end header-->


          <!-- Main-->
          <div id="main">


      <!-- Banner-->
        <section class="mainImg">

                <div class="mainImg">
                  <?php index_carousel(0,4000,450) ?>

          </div>
        </section>

        <!-- end Banner-->
      <div class="w-100" style="background-color: #300;">
        <div class="w1000">
          <?php echo Scroll_marquee(10000); ?>
        </div>
      </div>
      <div class="container-fluid m_content">
              <?php
						// 工作區內容
						//echo $tmpl['panelbody_content'];
						?>
<!-- section01 -->
        <div id="section01" class="row">
          <div class="col-6 p-0">
<!-- 首頁gamelobby顯示區域 -->
              <?php ui_gametab_gp06(1,'c','','',8,'1'); ?>
<!-- gamelobby結束 -->
          </div>
          <div class="col-6">
            <div class="casino-menu">
              <div class="tab-content" id="v-pills-tabContent">
                <div class="tab-pane fade show active" id="v-pills-game" role="tabpanel" aria-labelledby="v-pills-game-tab"><img src="<?php echo $cdnfullurl ?>img/home/photo01.jpg" alt="">
                  <p>汇聚最火爆热门的电子游戏平台，上万款电子小游戏让你享乐无穷</p>
                  <div class="mt-2 ml-3"><a class="base-link" href="<?php echo $config['website_baseurl']; ?>gamelobby.php?mgc=game">进入</a></div>
                </div>
                <div class="tab-pane fade" id="v-pills-live" role="tabpanel" aria-labelledby="v-pills-profile-tab"><img src="<?php echo $cdnfullurl ?>img/home/photo02.jpg" alt="">
                  <p>全球最佳真人视讯平台，真人荷官在线发牌，画面真实高清，给您亲临赌场的真实爽快感受！</p>
                  <div class="mt-2 ml-3"><a class="base-link" href="<?php echo $config['website_baseurl']; ?>gamelobby.php?mgc=Live">进入</a></div>
                </div>
                <div class="tab-pane fade" id="v-pills-lottery" role="tabpanel" aria-labelledby="v-pills-messages-tab"><img src="<?php echo $cdnfullurl ?>img/home/photo05.jpg" alt="">
                  <p>提供重庆时时彩，江西时时彩，新疆时时彩等各地彩票在线投注，保证公平公开让你快乐游戏！</p>
                  <div class="mt-2 ml-3"><a class="base-link" href="<?php echo $config['website_baseurl']; ?>gamelobby.php?mgc=Lottery">进入</a></div>
                </div>
                <div class="tab-pane fade" id="v-pills-fishing" role="tabpanel" aria-labelledby="v-pills-settings-tab"><img src="<?php echo $cdnfullurl ?>img/home/photo06.jpg" alt="">
                  <p>捕鱼游戏强袭来袭，众多热门平台，千炮捕鱼，深海捕鱼，天天送豪礼</p>
                  <div class="mt-2 ml-3"><a class="base-link" href="<?php echo $config['website_baseurl']; ?>gamelobby.php?mgc=Fishing">进入</a></div>
                </div>
              </div>

              <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                <a class="active" id="v-pills-game-tab" data-toggle="pill" href="#v-pills-game" role="tab" aria-controls="v-pills-game" aria-selected="true">电子游艺<span>GAMES CASINO</span></a>
                <a class="" id="v-pills-live-tab" data-toggle="pill" href="#v-pills-live" role="tab" aria-controls="v-pills-live" aria-selected="false">真人娱乐<span>LIVE CASINO</span></a>
                <a class="" id="v-pills-lottery-tab" data-toggle="pill" href="#v-pills-lottery" role="tab" aria-controls="v-pills-lottery" aria-selected="false">彩票游戏<span>LOTTERY GAMES</span></a>
                <a class="" id="v-pills-fishing-tab" data-toggle="pill" href="#v-pills-fishing" role="tab" aria-controls="v-pills-fishing" aria-selected="false">捕鱼达人<span>FISHING GAMES</span></a>
              </div>
            </div>
          </div>
        </div>

<!-- section02 end -->
      <div class="row section02">
      <div class="wrap clearfix">
        <div class="textBox wow bounceInLeft">
          <p class="title"><img src="<?php echo $cdnfullurl ?>img/home/imgtext01.png" alt=""></p>
          <p class="text">我们的手机投注平台面向全网玩家，提供近百款老虎机·百家乐·以及彩票游戏投注，线上存款及线上存款，一键操作，整合同步账号和资料传输，达到随时随地不间断娱乐的享受概念。</p>
          <p><a class="base-link" href="javascript:void(0);" onclick="msitechg('mobile'); return false;">手机投注</a></p>
          </div>
          <div class="maBox wow bounceInUp">
          <!--<p class="ttl"><img src="images/index/icon03.png" alt=""><span>扫码下载APP</span><img src="images/index/icon04.png" alt=""></p>-->
          <div class="ma"></div>
          </div>
          <div class="priceBox wow bounceInRight">
            <!--<p class="price"><?php echo $config['currency_sign'] ?><span class="timer" id="count-number" data-to="99698100" data-from="67910638" data-speed="10000000000">98,765,432.96</span></p>-->
            <p class="price"><?php echo $config['currency_sign'] ?> <span class="indexgamenumb fl" id="count-number">98,765,432.96</span></p>
          <div class="wininfo">
                <div class="title"><span>客户ID</span><span class="cspan">金额</span><span>游戏名称</span></div>
                <div class="inbd">
                  <div class="tempWrap" style="overflow:hidden; position:relative; height:130px">
                    <ul class="indexgamet2" id="indexgamet2" style="height: 660px; position: relative; padding: 0px; margin: 0px; top: -369px;">
                      <li class="clone"><span>db***s4</span><span class="num">82800</span><span>宝石联机</span></li>
                      <li><span>chia***i</span><span class="num">229058</span><span>钻石列车</span></li>
                      <li><span>lid***78</span><span class="num">195134</span><span>足球拉霸</span></li>
                      <li><span>a6***15</span><span class="num">142301</span><span>蒸气炸弹</span></li>
                      <li><span>zhi***2</span><span class="num">292916</span><span>战火佳人</span></li>
                      <li><span>di***un</span><span class="num">24222</span><span>月光宝盒</span></li>
                      <li><span>hu1***5</span><span class="num">254690</span><span>玉蒲团</span></li>
                      <li><span>am***314</span><span class="num">104053</span><span>鱼虾蟹</span></li>
                      <li><span>ni***29</span><span class="num">250827</span><span>夜市人生</span></li>
                      <li><span>lia***88</span><span class="num">238539</span><span>幸运财神</span></li>
                      <li><span>jun***g5</span><span class="num">241416</span><span>星际大战</span></li>
                      <li><span>zgy5***3</span><span class="num">170452</span><span>喜福牛年</span></li>
                      <li><span>qiu***10</span><span class="num">112372</span><span>喜福猴年</span></li>
                      <li><span>yl8***6</span><span class="num">133324</span><span>西游记</span></li>
                      <li><span>lds***25</span><span class="num">120855</span><span>王牌5PK</span></li>
                      <li><span>ab1***32</span><span class="num">218046</span><span>外星争霸</span></li>
                      <li><span>jj1***27</span><span class="num">242827</span><span>外星战记</span></li>
                      <li><span>Zhw***uf</span><span class="num">30063</span><span>筒子拉霸</span></li>
                      <li><span>fei***22</span><span class="num">13541</span><span>天山侠侣传</span></li>
                      <li><span>ba***mei</span><span class="num">213411</span><span>特务危机</span></li>
                      <li><span>li***93</span><span class="num">279860</span><span>糖果派对</span></li>
                      <li><span>qq1***11</span><span class="num">111201</span><span>水果乐园</span></li>
                      <li><span>zdy***46</span><span class="num">155124</span><span>水果拉霸</span></li>
                      <li><span>Qim***08</span><span class="num">39664</span><span>尸乐园</span></li>
                      <li><span>ya***n00</span><span class="num">171983</span><span>圣兽传说</span></li>
                      <li><span>wan***91</span><span class="num">237711</span><span>圣诞派对</span></li>
                      <li><span>vv***27</span><span class="num">114689</span><span>神舟27</span></li>
                      <li><span>xio***34</span><span class="num">90579</span><span>沙滩排球</span></li>
                      <li><span>cp1***79</span><span class="num">256232</span><span>三国拉霸</span></li>
                      <li><span>y***99</span><span class="num">123702</span><span>三国</span></li>
                      <li><span>zds***5</span><span class="num">123435</span><span>热带风情</span></li>
                      <li><span>yy***17</span><span class="num">87990</span><span>奇幻花园</span></li>
                    </ul>
                  </div>
                </div>
              </div>
          </div>
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
          <script type="text/javascript">
          $(document).ready(function(){
            sumFormat("indexgamenumb");
            myscrolltop("indexgamet2","li",42);
          });
          </script>
        </body>
  </html>
