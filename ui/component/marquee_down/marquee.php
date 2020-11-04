<?php
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
            <button type="button" class="btn btn-primary  btn-block" data-dismiss="modal">我知道了</button>
          </div>
        </div>
      </div>
    </div>
HTML;
  }


  // $ann_data_html = '';
  // if($ann_result[0] != 0){
  //   for($i=1;$i<=$ann_result[0];$i++) {
  //       $ann_data_html = $ann_data_html.'<li><a href="#" onclick="window.open(\'announcement_fullread.php\', \'跑马灯公告栏\', config=\'left=300,top=100,menubar=no,status=no,toolbar=no,location=no,scrollbars=yes,height=600,width=500\');"><span>['.date("Y-m-d", strtotime($ann_result[$i]->effecttime)).']</span>'.$ann_result[$i]->title.'</a>
  //       </li>';
  //   }
  // }
    return $result;
}
function Scroll_marquee($speed=20000){
  $html = get_announcement_data();
  // 向上轮播
  $ui['Scroll_marquee'] = <<<HTML
  <style>
  .marquee {
  width: 100%;
  overflow: hidden;
  color: rgb(255, 255, 255);
  }
  .marquee a {
 color: #eee;
 letter-spacing: 1px;
  }
  .marquee li {
  display: block;
  line-height:120%;
  }
  .js-marquee li span {
    color: #63d0b5;
    letter-spacing: 1px;
    display: block;
  }
  </style>
  <div class="marqueebox marquee_down newSection">

  <div class="marquee" data-direction="up" data-pauseOnHover="true">
    {$html['ann_data_html']}
  </div>

   <div class="tempWrap" style="display: none;">
        <ul class="marqueelist">
          {$html['ann_data_html']}
        </ul>
  </div>
    {$html['modal_html']}
  </div> 
  <script type="text/javascript">
    $(".marquee").marquee({
      speed : 8000
    });
   $(document).on('click', '.announcementDetail', function() {
    console.log('announcementDetail');
    var id = $(this).val();

    $.ajax({
      method:'POST',
      url:'./ui/component/marquee_action.php',
      data:{
        action:'detail',
        id:id
      }
    }).done(function(resp){
      var res = JSON.parse(resp);
      $("#announcementTitle").html('')
                            .append(`<h5><span class="mr-2">[`+res.result.effecttime+`]</span>`+res.result.title+`</h5>`);

      $("#announcementBody").html('')
                            .append(`<p>`+res.result.content+`</p>`);

      $("#announcementFooter").html('')
                              .html(`<button type="button" class="btn btn-primary  btn-block" id="announcementMore">更多公告</button>`);
    }).fail(function(){
      alert("Request failed : 公告查詢失敗"); 
    });
  });

  $(document).on('click', '#announcementMore', function() {
    console.log("announcementMore");
    $("#announcementTitle").html('')
                            .append(`<h5>公告</h5>`);

    $("#announcementBody").html('')
                          .append(`<ul class="list-group" id="announcementList">{$html['menuContentHtml']}</ul>`);

    $("#announcementFooter").html('')
                            .html(`<button type="button" class="btn btn-primary  btn-block" data-dismiss="modal">我知道了</button>`);
  });

  $('#announcementModal').on('hidden.bs.modal', function (e) {
    $("#announcementTitle").html('')
                            .append(`<h5>公告</h5>`);

    $("#announcementBody").html('')
                          .append(`<ul class="list-group" id="announcementList">{$html['menuContentHtml']}</ul>`);

    $("#announcementFooter").html('')
                            .html(`<button type="button" class="btn btn-primary  btn-block" data-dismiss="modal">我知道了</button>`);
  });
  </script>
HTML;

  if($html['ann_data_html']==''){
    $ui['Scroll_marquee'] ='';
  }
  return $ui['Scroll_marquee'];
}
// ----------------------------------------------------------------------------
// 跑馬燈公告欄 end
// ----------------------------------------------------------------------------
?>