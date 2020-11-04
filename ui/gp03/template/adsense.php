<?php

// ----------------------------------------------------------------------------
// Features:	前端 -- 浮動廣告(們)
// File Name:	adsense.php
// Author:		Pia , Barkley
// Related: home.php之類的
// Log:
// 2017.03.15
// ----------------------------------------------------------------------------

// 廣告的 CSS
$ad['adsense_extend_head'] = '<link rel="stylesheet" href="'.$cdnfullurl.'css/style_sense.css">';

// ----------------------------------------------------------------------------
//左中側的浮動廣告 
// ----------------------------------------------------------------------------


$ad['left_float_img_html'] = '
<div id="left_float_img_html_close" class="float_img" style="left: 1.5%;top: 30%;">
        <ul id="girl">
          <li id="chat" title="'.$tr['online customer service'] .'" onclick="window.open(\''.$customer_service_cofnig['online_weblink'].'\', \''.$tr['online customer service'] .'\', config=\'height=800,width=700\');"></li>
          <li id="qq" title="企業QQ" onclick="window.open(\'http://wpd.b.qq.com/page/webchat.html?nameAccount='.$customer_service_cofnig['qq'].'\', \'QQ\', config=\'height=500,width=500\');"></li>
          <li id="deposit" title="帐户存款"><a href="./deposit.php"></a></li>
          <li id="closed" onclick="document.getElementById(\'left_float_img_html_close\').style.display=\'none\';"></li>
 </ul>
</div>

';

// ----------------------------------------------------------------------------
//左中側的浮動廣告 END
// ----------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// 左下角的浮動廣告
// ----------------------------------------------------------------------------
$ad['lb_float_img_html'] = '

<div id="lb_float_float_close" class="float_img" style="left: 1.5%;">
<button class="float_img_close_btn_left" onclick="document.getElementById(\'lb_float_float_close\').style.display=\'none\';"></button>
<a href="#"><div data-img = "01"></div></a>
</div>
';
// ----------------------------------------------------------------------------
// 左下角的浮動廣告  END
// ----------------------------------------------------------------------------


// ----------------------------------------------------------------------------
// 右上側的浮動廣告
// ----------------------------------------------------------------------------
$ad['right_top_float_img_html'] = '
<div id="right_top_float_close" class="float_img" style="right:-10px;top: 0%;">
<button class="float_img_close_btn_right" onclick="document.getElementById(\'right_top_float_close\').style.display=\'none\';"></button>
<a href="#"><div data-img = "02"></div></a>
</div>
';

// ----------------------------------------------------------------------------
// 左上側的浮動廣告 END
// ----------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// 左上側的浮動廣告
// ----------------------------------------------------------------------------
$ad['left_top_float_img_html'] = '
<div id="left_top_float_close" class="float_img" style="left:-10px;top: 0%;">
<button class="float_img_close_btn_left" onclick="document.getElementById(\'left_top_float_close\').style.display=\'none\';"></button>
<a href="#"><div data-img = "04"></div></a>
</div>
';

// ----------------------------------------------------------------------------
// 右上側的浮動廣告 END
// ----------------------------------------------------------------------------




// ----------------------------------------------------------------------------
// 右中的浮動廣告
// ----------------------------------------------------------------------------

$ad['right_float_img_html'] = '

';

//<div class="pollSlider">
//<ul class="pollSiderContent">
//<a href="http://'.$customer_service_cofnig['online_weblink'].'"><li class="pollSiderContentli"></li></a>
//<a href="tel:'.$customer_service_cofnig['mobile_tel'].'"><li class="pollSiderContentli">&nbsp&nbsp'.$customer_service_cofnig['mobile_tel'].'</li></a>
//<a href="http://wpa.qq.com/msgrd?v=3&uin='.$customer_service_cofnig['qq'].'&site=qq&menu=yes"><li class="pollSiderContentli">&nbsp&nbsp'.$customer_service_cofnig['qq'].'</li></a>
//</ul>
//</div>
//<div id="pollSlider-button"></div>

$ad['right_float_img_script'] = '
<script>
$(document).ready(function() {
  var slider_width = $(\'.pollSlider\').width();
  $(\'#pollSlider-button\').mouseover(function() {
    if ($(this).css("margin-right") == slider_width + "px" && !$(this).is(\':animated\')) {
      $(\'.pollSlider,#pollSlider-button\').animate({
        "margin-right": \'-=\' + slider_width
      });
    } else {
      if (!$(this).is(\':animated\'))
      {
        $(\'.pollSlider,#pollSlider-button\').animate({
          "margin-right": \'+=\' + slider_width
        });
      }
    }
  });
});
</script>
';


// ----------------------------------------------------------------------------
//右中的浮動廣告 END
// ----------------------------------------------------------------------------




// ----------------------------------------------------------------------------
// 右下側的浮動廣告
// ----------------------------------------------------------------------------
$ad['right_bottom_float_img_html'] = '
<div id="right_bottom_float_close" class="float_img" style="right:-10px;bottom: 0;width:160px;overflow:hidden;">
<button class="float_img_close_btn_right" onclick="document.getElementById(\'right_bottom_float_close\').style.display=\'none\';"></button>
<div onclick="location.href = \'./gamelobby.php\';" style="cursor: pointer"><iframe src="'.$cdnfullurl.'img/sensepic/elves.html" style="width:100%;height:100%;pointer-events: none;"></iframe></div>
</div>



';

// ----------------------------------------------------------------------------
// 右下側的浮動廣告 END
// ----------------------------------------------------------------------------





?>
