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
$ad['adsense_extend_head'] = '';

// ----------------------------------------------------------------------------
//左中側的浮動廣告 
// ----------------------------------------------------------------------------

$ad['left_float_img_html'] = '


';

// ----------------------------------------------------------------------------
//左中側的浮動廣告 END
// ----------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// 左下角的浮動廣告
// ----------------------------------------------------------------------------
$ad['lb_float_img_html'] = '

<div id="lb_float_float_close" class="float_img" style="left: 1.5%;">
<button class="float_img_close_btn" onclick="document.getElementById(\'lb_float_float_close\').style.display=\'none\';"></button>
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
<button class="float_righttop_img_close_btn" onclick="document.getElementById(\'right_top_float_close\').style.display=\'none\';"></button>
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
<button class="float_lefttop_img_close_btn" onclick="document.getElementById(\'left_top_float_close\').style.display=\'none\';"></button>
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
<div class="pollSlider">
<ul class="pollSiderContent">
<a href="http://'.$customer_service_cofnig['online_weblink'].'"><li class="pollSiderContentli"></li></a>
<a href="tel:'.$customer_service_cofnig['mobile_tel'].'"><li class="pollSiderContentli">&nbsp&nbsp'.$customer_service_cofnig['mobile_tel'].'</li></a>
<a href="http://wpa.qq.com/msgrd?v=3&uin='.$customer_service_cofnig['qq'].'&site=qq&menu=yes"><li class="pollSiderContentli">&nbsp&nbsp'.$customer_service_cofnig['qq'].'</li></a>
</ul>

</div>
<div id="pollSlider-button"></div>
';


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
//<div id="right_bottom_float_close" class="float_img" style="right: 0%;bottom: 0%;">
<div class="float-icon" id="totop" style="">
			<style>
				.float-icon{height:250px;}
			</style>
			<a href="register.php" class="icon2">免費開戶</a>
			<a href="login2page.php" class="icon3">登入帳戶</a>

			<a href="#header" id="toTopBTN" class="icon4">返回顶部</a>
		</div>
</div>
';

// ----------------------------------------------------------------------------
// 右下側的浮動廣告 END
// ----------------------------------------------------------------------------





?>
