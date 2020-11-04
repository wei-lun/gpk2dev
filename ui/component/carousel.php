<?php
/*-----------------------------------------*/
// 使用方法：echo ui_carousel($id,$item,$type,$speed)
// $id = 名稱
// $item = 連結與圖片放置位置(二維陣列)-array('連結','圖片')  ex: $item = array( array('#','img/home/banner01.jpg'), array('#','img/home/banner02.jpg') );
// $type = 型態 0 為預設，其餘變數會移除左右導航
// $speed = 輪播速度(ms)
/*-----------------------------------------*/

function ui_carousel($id,$item,$type,$device,$speed,$height=400)
{
  global $cdnfullurl;
  global $cdnfullurl_js;
  $count = count($item);
  $carousel_html ="";

/*----------------輪播指標---------------*/
  $carousel_html.='
  <div id="'.$id.'" class="carousel slide">';
  if($count>1){
    $carousel_html.='
      <!-- 轮播（Carousel）指标 -->
        <ol class="carousel-indicators">';

      for ($i=0; $i < $count; $i++) { 
        if($i==0)
          $carousel_html .='<li data-target="#'.$id.'" data-slide-to="'.$i.'" class="active"></li>';
        else
          $carousel_html .='<li data-target="#'.$id.'" data-slide-to="'.$i.'"></li>';
      }

      $carousel_html .= '</ol>';
  }
/*----------------輪播圖片項目---------------*/
$carousel_html .= '
 <!-- 轮播（Carousel）项目 -->
  <div class="carousel-inner align-items-center">';

for ($i=0; $i < $count; $i++) {
  if(!isset(parse_url($item[$i][1])['host'])){
    $item[$i][1]=$cdnfullurl.$item[$i][1];
  }
  if($i==0){
    $carousel_html .='
      <div class="carousel-item active">
        <a href="'.$item[$i][0].'" target="'.$item[$i][2].'"><img onerror="this.src=\''.$cdnfullurl.'img/common/logo.png'.'\'" src="'.$item[$i][1].'" alt="'.$i.' slide"></a>
      </div>
    ';
  }
  else{
    $carousel_html .='
      <div class="carousel-item">
        <a href="'.$item[$i][0].'" target="'.$item[$i][2].'"><img onerror="this.src=\''.$cdnfullurl.'img/common/logo.png'.'\'" src="'.$item[$i][1].'" alt="'.$i.' slide"></a>
      </div>
    ';
  }

}

$carousel_html .='</div>';

/*----------------輪播左右導航---------------*/
if($type == 0 AND $count>1 ){
  $carousel_html .='
            <!-- 轮播（Carousel）导航 -->
            <a class="carousel-control-prev" href="#'.$id.'" role="button" data-slide="prev">
              <span class="carousel-control-prev-icon" aria-hidden="true"></span>
              <span class="sr-only">Previous</span>
            </a>
            <a class="carousel-control-next" href="#'.$id.'" role="button" data-slide="next">
              <span class="carousel-control-next-icon" aria-hidden="true"></span>
              <span class="sr-only">Next</span>
            </a>           
  ';
}

$carousel_html .='</div>';

/*----------------------------------------*/
if($device=='desktop'){
  $carousel_html .='
            <style>
              #'.$id.' .carousel-item img{
                  /*max-width: 100%;
                  max-height: '.$height.'px;*/
                  height:'.$height.'px;
                  margin:auto;

                }
              #'.$id.' .carousel-item{
                text-align: center;
              }
            </style>

  ';
}
else{
  $carousel_html .='
            <style>
              #'.$id.' .carousel-item img{
                width:100%;
                /*max-width: 100%;
                  max-height: '.$height.'px;*/
                  margin:auto;

                }
              #'.$id.' .carousel-item{
                text-align: center;
              }
            </style>

  ';
}
/*----------------自動輪播js速度---------------*/
/*$(window).resize(function() {
    img_height = [];
    $("#{$id} .carousel-item").each(function(){
    img_height.push($(this).height());
    });
    img_height = img_height.sort(sortNumber);
    var maximg=img_height[img_height.length-1];
    $("#{$id}").height(maximg);
    console.log(maximg);
  }).resize();*/
  /*
  var carousel = $(this).width(),
    act_img = $(this).find('.carousel-item.active img').width();
  $("#{$id} .carousel-item.active img").css('margin-left',-(act_img-carousel)/2);
  $('#{$id}').on('slide.bs.carousel', function () {
    //$("#{$id} .carousel-item.active img").css('margin-left',$(this).)
    var carousel = $(this).width(),
    act_img = $(this).find('.carousel-item.active img').width();

    console.log($(this).width());
    console.log($(this).find('.carousel-item.active img').width());
    console.log((act_img-carousel)/2);
    $("#{$id} .carousel-item.active img").css('margin-left',-(act_img-carousel)/2);                
              });*/
$carousel_html .=<<<HTML
          <script>
            $("#{$id}").carousel({
                interval: {$speed}
            })
            function sortNumber(a, b)
            {
            return a - b
            }
            $(document).ready(function(){ 
              $(window).resize(function() {
                var carousel = $('#{$id}').width();
                $("#{$id} .carousel-item.active img").each(function(){
                  var act_img = $(this).width();
                  if(-(act_img-carousel)/2 < 0){
                    $(this).css('margin-left',-(act_img-carousel)/2);
                  }
                  else{
                    $(this).css('margin-left','');
                  }
                });
              }).resize();
              $('#{$id}').on('slid.bs.carousel', function () {
                $(window).resize();
              });
            });
          </script>

HTML;

if($count!=0)
  return $carousel_html;

}
//桌機輪播
function index_carousel($type,$speed,$height=400){
  global $data;
  global $ui_data;
  $item = array();
  if(isset($ui_data["index_carousel"]["desktop"]["item"])){
    if(count($ui_data["index_carousel"]["desktop"]["item"])==0){
      $ui_data["index_carousel"]["desktop"]["item"]=$data["index_carousel"]["desktop"]["item"];
    }
    for ($i=0; $i < count($ui_data["index_carousel"]["desktop"]["item"]) ; $i++) { 
      if(!isset($ui_data["index_carousel"]["desktop"]["item"][$i]["target"])){
        $ui_data["index_carousel"]["desktop"]["item"][$i]["target"]="_self";
      }
      array_push($item, array($ui_data["index_carousel"]["desktop"]["item"][$i]["link"],$ui_data["index_carousel"]["desktop"]["item"][$i]["img"],$ui_data["index_carousel"]["desktop"]["item"][$i]["target"]));
    }
    echo ui_carousel('index_carousel',$item,$type,'desktop',$speed,$height);
  }  
}
//手機輪播
function index_carousel_m($type,$speed,$height=400){
  global $data;
  global $ui_data;
  $item = array();
  if(isset($ui_data["index_carousel"]["mobile"]["item"])){
    if(count($ui_data["index_carousel"]["mobile"]["item"])==0){
      $ui_data["index_carousel"]["mobile"]["item"]=$data["index_carousel"]["mobile"]["item"];
    }
    for ($i=0; $i < count($ui_data["index_carousel"]["mobile"]["item"]) ; $i++) { 
      if(!isset($ui_data["index_carousel"]["mobile"]["item"][$i]["target"])){
        $ui_data["index_carousel"]["mobile"]["item"][$i]["target"]='_self';
      }
      array_push($item, array($ui_data["index_carousel"]["mobile"]["item"][$i]["link"],$ui_data["index_carousel"]["mobile"]["item"][$i]["img"],$ui_data["index_carousel"]["mobile"]["item"][$i]["target"]));
    }
    return ui_carousel('index_carousel',$item,$type,'mobile',$speed,$height);
  }
  
}
//遊戲大廳輪播
function index_carousel_lobby($page,$type,$speed,$height=250){
  global $ui_data;

  if($page == ''){
    $page = 'game';
  }
  else{
    $page = strtolower($page);
  }
  
  $item = array();
  if(isset($ui_data["lobby_carousel"][$page])){
    for ($i=0; $i < count($ui_data["lobby_carousel"][$page]) ; $i++) { 
      array_push($item, array($ui_data["lobby_carousel"][$page][$i]["link"],$ui_data["lobby_carousel"][$page][$i]["img"],$ui_data["lobby_carousel"][$page][$i]["target"]));
    }
    return ui_carousel('carousel_lobby',$item,$type,'mobile',$speed,$height);
  }  
}

//手機觸控輪播
function index_carousel_m_touch(){
  global $data;
  global $ui_data;
  global $cdnfullurl;
  $item = array();
  if(isset($ui_data["index_carousel"]["mobile"]["item"])){
    if(count($ui_data["index_carousel"]["mobile"]["item"])==0){
      $ui_data["index_carousel"]["mobile"]["item"]=$data["index_carousel"]["mobile"]["item"];
    }
    for ($i=0; $i < count($ui_data["index_carousel"]["mobile"]["item"]) ; $i++) { 
      if(!isset($ui_data["index_carousel"]["mobile"]["item"][$i]["target"])){
        $ui_data["index_carousel"]["mobile"]["item"][$i]["target"]='_self';
      }
      array_push($item, array($ui_data["index_carousel"]["mobile"]["item"][$i]["link"],$ui_data["index_carousel"]["mobile"]["item"][$i]["img"],$ui_data["index_carousel"]["mobile"]["item"][$i]["target"]));
    }
    //return ui_carousel('index_carousel',$item,$type,'mobile',$speed,$height);
    
    $html ='<div id="index_carousel" class="swiper-container">
    <div class="swiper-wrapper">';
    foreach ($item as $value) {
      if(!isset(parse_url($value[1])['host'])){
        $value[1]=$cdnfullurl.$value[1];
      }
      $html .=<<<HTML
      <div class="swiper-slide"><a href="{$value[0]}" target="{$value[2]}"><img  onerror="this.src='{$cdnfullurl}img/common/logo.png';" src="{$value[1]}" alt=""></a></div>
HTML;
    }
    $html .='</div>
    <div class="swiper-pagination"></div>
    <div class="swiper-button-next"></div>
    <div class="swiper-button-prev"></div>
    </div>';

    $js=<<<HTML
    <script type="text/javascript">
      var swiper = new Swiper('.swiper-container', {
        loop: true,
        navigation: {
          nextEl: '.swiper-button-next',
          prevEl: '.swiper-button-prev',
        },
        pagination: {
          el: '.swiper-pagination',
        },
    });
    </script>
HTML;

    return $html.$js;

  }
  
}
?>