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
// 右下角的浮動廣告
// ----------------------------------------------------------------------------
$ad['float_img_html'] = '
<div id="float_close" class="float_img">
<button class="float_img_close_btn" onclick="document.getElementById(\'float_close\').style.display=\'none\';"></button>
<a href="#"><div data-img = "01"></div></a>
</div>
';
// ----------------------------------------------------------------------------
// 右下角的浮動廣告  END
// ----------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// 右側的浮動廣告
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
// 左側的浮動廣告 END
// ----------------------------------------------------------------------------
$ad['left_float_img_html'] = '
<div id="float_close" class="float_img" style="left: 1.5%;">
<button class="float_img_close_btn" onclick="document.getElementById(\'float_close\').style.display=\'none\';"></button>
<a href="#"><div data-img = "01"></div></a>
</div>
';
// ----------------------------------------------------------------------------
// 左側的浮動廣告 END
// ----------------------------------------------------------------------------
?>
