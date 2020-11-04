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
$data["index_carousel"]["desktop"]["item"] = json_decode('[{"img":"img/home/banner03.jpg","link":"#"}]',true);

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
        <!-- Marquee-->        

          <!-- end Marquee-->
          <!-- Main-->
          <div id="main">

          <!-- 幻燈片部分 -->
          <?php index_carousel(0,3000); ?>
          <!-- 結束幻燈片部分 -->

            <div class="container m_content">         

          <?php
            // 跑馬燈
            echo Scroll_marquee();
          ?>
          </div>
      <!--section1-->
          <div class="indexgamenumbwrap w1000 m-auto">
            <div class="indexgamenumb fl" id="indexgamenumb">84,252,968.88</div>
            <a href="<?php echo $config['website_baseurl']; ?>gamelobby.php?mgc=game" title="" class="indexgamenumbur fl"></a>
          </div>
          <script type="text/javascript">
          $(document).ready(function(){
            sumFormat("indexgamenumb");
            myscrolltop("indexgamet2","tr",42);
          }); 
          </script>

      <!--section2-->
          <div class="section1">            
            <div class="playerboard">
              <p class="indexgametitle">超级赢家榜</p>
               <div class="indexgamet1">
                <table>
                  <tr>
                    <td width="64">客户ID</td>
                    <td width="70">金额</td>
                    <td>游戏名称</td>
                  </tr>
                </table>
              </div>
                  <div class="indexgamescrollwrap">
                    <div class="indexgamescrollbox">
                      <table class="indexgamet2" id="indexgamet2">
                        <tr>
                          <td width="64">skk***680</td>
                          <td width="70">9842.61</td>
                          <td>捕鱼大亨</td>
                        </tr>
                        <tr>
                          <td>ar1***12</td>
                          <td>7546.3</td>
                          <td>多人射龙门</td>
                        </tr>
                        <tr>
                          <td>q30****04</td>
                          <td>5876.54</td>
                          <td>摇滚怪兽</td>
                        </tr>
                        <tr>
                          <td>a32*****224</td>
                          <td>9657.12</td>
                          <td>警察与土匪</td>
                        </tr>
                        <tr>
                          <td>wasd***630</td>
                          <td>6478.32</td>
                          <td>糖果嘉年华</td>
                        </tr>
                        <tr>
                          <td>qwe***446</td>
                          <td>81264.9</td>
                          <td>神的时代</td>
                        </tr>
                        <tr>
                          <td>ber***r24</td>
                          <td>7619.42</td>
                          <td>德州扑克</td>
                        </tr>
                        <tr>
                          <td>zxc***101</td>
                          <td>5972.5</td>
                          <td>冲浪度假</td>
                        </tr>
                      </table>
                    </div>
                  </div>
                </div>
            
            <?php ui_gametab_gp06(1,'c','','',8,'1'); ?>
          </div>

      <!--section3-->
      <div class="indexgamelist2 w1000 m-auto">
        <ul>
          <li><a href="<?php echo $config['website_baseurl']; ?>gamelobby.php?mgc=game" title="" class="trans indexgamelist2a1">
            <span class="igamepic"><img class="trans" src="<?php echo $cdnfullurl ?>img/home/i_img02.png" alt=""></span>
            <p class="igameinfo trans">
              <span class="igameih1">电子游戏</span>
              <span class="igameih2 cz">高额奖池！</span>
              <span class="igameidesc">体验上万款游戏带来的刺激Starburst,Millionaire,Genie,Snack Time...</span>
              <span class="igameimore trans">立即玩游戏</span>
              <span class="igameilogo"><img src="<?php echo $cdnfullurl ?>img/home/icon_logo01.png" alt=""></span>
            </p>
          </a></li>

          <li><a href="<?php echo $config['website_baseurl']; ?>gamelobby.php?mgc=Live" title="" class="trans indexgamelist2a2">
            <span class="igamepic"><img class="trans" src="<?php echo $cdnfullurl ?>img/home/i_img03.png" alt=""></span>
            <p class="igameinfo trans">
              <span class="igameih1">真人荷官</span>
              <span class="igameih2 cgreen">美女主播！</span>
              <span class="igameidesc">享受专业赌场荷官带给您的真正的极限体验</span>
              <span class="igameimore trans">立即玩游戏</span>
              <span class="igameilogo"><img src="<?php echo $cdnfullurl ?>img/home/icon_logo02.png" alt=""></span>
            </p>
          </a></li>

        <!--
          <li><a href="" title="" class="trans indexgamelist2a3">
            <span class="igamepic"><img class="trans" src="<?php echo $cdnfullurl ?>img/home/i_img04.png" alt=""></span>
            <p class="igameinfo trans">
              <span class="igameih1">运动</span>
              <span class="igameih2 ct">独具特色！</span>
              <span class="igameidesc">畅玩实时下注、足球、赛马以及更多游戏</span>
              <span class="igameimore trans">立即玩游戏</span>
              <span class="igameilogo"><img src="<?php echo $cdnfullurl ?>img/home/icon_logo03.png" alt=""></span>
            </p>
          </a></li>
        -->
          <li><a href="<?php echo $config['website_baseurl']; ?>gamelobby.php?mgc=Fishing" title="" class="trans indexgamelist2a4">
            <span class="igamepic"><img class="trans" src="<?php echo $cdnfullurl ?>img/home/i_img05.png" alt=""></span>
            <p class="igameinfo trans">
              <span class="igameih1">捕鱼达人</span>
              <span class="igameih2">高额奖金！</span>
              <span class="igameidesc">千炮捕鱼奖金高达400倍，多样技能、丰富玩法</span>
              <span class="igameimore trans">立即玩游戏</span>
              <span class="igameilogo"><img src="<?php echo $cdnfullurl ?>img/home/icon_logo04.png" alt=""></span>
            </p>
          </a></li>

          <li><a href="<?php echo $config['website_baseurl']; ?>gamelobby.php?mgc=Lottery" title="" class="trans indexgamelist2a5">
            <span class="igamepic"><img class="trans" src="<?php echo $cdnfullurl ?>img/home/i_img06.png" alt=""></span>
            <p class="igameinfo trans">
              <span class="igameih1">彩票</span>
              <span class="igameih2 cblur2">多种玩法！</span>
              <span class="igameidesc">六合经典，全新进化，官方同步派彩迅速！</span>
              <span class="igameimore trans">立即玩游戏</span>
              <span class="igameilogo"><img src="<?php echo $cdnfullurl ?>img/home/icon_logo05.png" alt=""></span>
            </p>
          </a></li>
        </ul>
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
