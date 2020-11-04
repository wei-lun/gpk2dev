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
      <div id="banner" class="carousel slide py-2" data-ride="carousel">
        <ol class="carousel-indicators">
          <li data-target="#banner" data-slide-to="0" class="active"></li>
          <li data-target="#banner" data-slide-to="1"></li>
          <li data-target="#banner" data-slide-to="2"></li>
        </ol>
       
        <div class="carousel-inner">
        <div class="carousel-item active">
        <img class="d-block w-100" alt="First slide" data-holder-rendered="true" src="'.$cdnfullurl.'img/home/banner_01.jpg">
        </div>
        <div class="carousel-item">
        <img class="d-block w-100" alt="Second slide" data-holder-rendered="true" src="'.$cdnfullurl.'img/home/banner_02.jpg">
        </div>
        <div class="carousel-item">
        <img class="d-block w-100" alt="Third slide" data-holder-rendered="true" src="'.$cdnfullurl.'img/home/banner_03.jpg">
        </div>
        </div>
        <a class="carousel-control-prev" href="#banner" role="button" data-slide="prev">
          <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          <span class="sr-only">Previous</span>
        </a>
        <a class="carousel-control-next" href="#banner" role="button" data-slide="next">
          <span class="carousel-control-next-icon" aria-hidden="true"></span>
          <span class="sr-only">Next</span>
        </a>
      </div>
';

$ui['Block_home'] = '
<div class="row m_feature">
<!-- feature-block 1 -->
<a href="./promotions.php">
<div class="feature feature-left bg-light">
<div class="feature block">
<div class="feature-icon"><span class="lnr lnr-gift"></span></div>
'.$tr['img 01'].'
</div>
</div>
</a>

<!-- feature-block 2 -->
<a href="./partner.php" target="_BLANK">
<div class="feature feature-center bg-dark">
<div class="feature block">
<div class="feature-icon"><span class="lnr lnr-diamond"></span></div>
'.$tr['img 02'].'
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
      <li data-img="game_live">
          <a href="gamelobby.php?mgc=Live">
              <div>
                '.$tr['img 03'].'
              </div>
          </a>
      </li>
      <li data-img="game_lottery">
          <a href="gamelobby.php?mgc=Lottery">
              <div>
               '.$tr['img 04'].'
              </div>
          </a>
      </li>
      <li data-img="game_lobby">
                    <a href="gamelobby.php">
              <div>
                '.$tr['img 06'].'
              </div>
          </a>
      </li>
      <li data-img="game_sport">
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
/*
// get_announcement_data()
// 取得跑馬燈的資訊, 輸出給 ui.php 使用, 配合 announcement_fullread.php 顯示完整的資訊
function get_announcement_data() {
  $ann_sql = "SELECT * FROM root_announcement WHERE status = '1' AND now() < endtime  AND effecttime < now() ORDER BY id LIMIT 100;";
  $ann_result = runSQLall($ann_sql);
 
 $result = [
    'ann_data_html' => '',
    'menuContentHtml' => ''
  ];

  if (!empty($ann_result[0])) {
    unset($ann_result[0]);

    foreach ($ann_result as $k => $v) {
      $id = base64_encode($v->id);
      $date = date("Y-m-d", strtotime($v->effecttime));

      $result['ann_data_html'] .= <<<HTML
      <li>
        <button type="button" class="btn btn-link" data-toggle="modal" data-target="#announcementModal">
          <span>[{$date}]</span>{$v->title}
        </button>
      </li>
HTML;

      $result['menuContentHtml'] .= <<<HTML
      <button type="button" class="list-group-item list-group-item-action announcementDetail" value="{$id}">{$v->title}</button>
HTML;
    }

    $result['modal_html'] = <<<HTML
    <div class="modal fade" id="announcementModal" tabindex="-1" role="dialog" aria-labelledby="announcementModalTitle" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="announcementTitle">公告</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body" id="announcementBody">
            <ul class="list-group" id="announcementList">
              {$result['menuContentHtml']}
            </ul>
          </div>
          <div class="modal-footer" id="announcementFooter">
            <button type="button" class="btn btn-primary btn-lg btn-block" data-dismiss="modal">我知道了</button>
          </div>
        </div>
      </div>
    </div>


HTML;
  } 

  return $result;     

  // $ann_data_html = '';
  // if($ann_result[0] != 0){
 //    for($i=1;$i<=$ann_result[0];$i++) {
 //        $ann_data_html = $ann_data_html.'<li><a href="#" onclick="window.open(\'announcement_fullread.php\', \'跑马灯公告栏\', config=\'left=300,top=100,menubar=no,status=no,toolbar=no,location=no,scrollbars=yes,height=600,width=500\');"><span>['.date("Y-m-d", strtotime($ann_result[$i]->effecttime)).']</span>'.$ann_result[$i]->title.'</a>
 //        </li>';
 //    }
 //  }
  //  return($ann_data_html);
}

$html = get_announcement_data();

// 向右轮播
$ui['Scroll_marquee'] = '
<div class="marqueebox">
 <div class="container">
  <div class="row">
  <div class="col-12">
  <span class="title lnr lnr-volume-high"></span>
   <div class="marquee" data-direction="left" data-pauseOnHover="true">           
    '.$html['ann_data_html'].'
   </div>
   <div class="tempWrap" style="display: none;">
        <ul class="marqueelist">
          '.$html['ann_data_html'].'
        </ul>
   </div>
   '.$html['modal_html'].'
  </div>
 </div> 
 </div>
</div>
<script type="text/javascript">
$(".marquee").marquee({
  speed : 20000
});
  $(document).on("click", ".announcementDetail", function() {
    
    var id = $(this).val();

    $.ajax({
      method:"POST",
      url:"./ui/component/marquee_action.php",
      data:{
        action:"detail",
        id:id
      }
    }).done(function(resp){
      var res = JSON.parse(resp);
      $("#announcementTitle").html("")
                            .append(`<h5><span class="mr-2">[`+res.result.effecttime+`]</span>`+res.result.title+`</h5>`);

      $("#announcementBody").html("")
                            .append(`<p>`+res.result.content+`</p>`);

      $("#announcementFooter").html("")
                              .html(`<button type="button" class="btn btn-primary btn-lg btn-block" id="announcementMore">更多公告</button>`);
    }).fail(function(){
      alert("Request failed : 公告查詢失敗"); 
    });
  });

  $(document).on("click", "#announcementMore", function() {
    
    $("#announcementTitle").html("")
                            .append(`<h5>公告</h5>`);

    $("#announcementBody").html("")
                          .append(`<ul class="list-group" id="announcementList">'.$html['menuContentHtml'].'</ul>`);

    $("#announcementFooter").html("")
                            .html(`<button type="button" class="btn btn-primary btn-lg btn-block" data-dismiss="modal">我知道了</button>`);
  });

  $("#announcementModal").on("hidden.bs.modal", function (e) {
    $("#announcementTitle").html("")
                            .append(`<h5>公告</h5>`);

    $("#announcementBody").html("")
                          .append(`<ul class="list-group" id="announcementList">'.$html['menuContentHtml'].'</ul>`);

    $("#announcementFooter").html("")
                            .html(`<button type="button" class="btn btn-primary btn-lg btn-block" data-dismiss="modal">我知道了</button>`);
  });

 </script>
';
if($html['ann_data_html']==''){
  $ui['Scroll_marquee'] ='';
}*/
// ----------------------------------------------------------------------------
// 跑馬燈公告欄 end
// ----------------------------------------------------------------------------


// ----------------------------------------------------------------------------
// header宣傳廣告
// ----------------------------------------------------------------------------

// 網域顯示 
$ad['Header_domain'] = '
										<li><a href="#">
									<img src="'.$cdnfullurl.'img/promotion/header_domain.png" alt="LOGO" style="max-width: 400px;
max-height: 20px;">								</a>
</li>
';
// 宣傳圖01 
$ad['Header_pic01'] = '                
							<span><a href="#"><img src="'.$cdnfullurl.'img/promotion/header_promo01.png" alt="LOGO"></a></span>
';
// 宣傳圖02 
$ad['Header_pic02'] = '
<span><a href="#"><img src="'.$cdnfullurl.'img/promotion/header_promo02.png" alt="LOGO"></a></span>
';


?>
