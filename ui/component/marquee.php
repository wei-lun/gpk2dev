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
    <div class="modal fade announcementModal" id="announcementModal" tabindex="-1" role="dialog" aria-labelledby="announcementModalTitle" aria-hidden="true">
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
            <button type="button" class="btn btn-lg btn-block" data-dismiss="modal">我知道了</button>
          </div>
        </div>
      </div>
    </div>
HTML;
  }

  // if($ann_result[0] != 0){
  // 	for($i=1;$i<=$ann_result[0];$i++) {
  //     // $btn = '<button type="button" class="btn btn-link" data-toggle="modal" data-target="#announcementModal"><span>['.date("Y-m-d", strtotime($ann_result[$i]->effecttime)).']</span>'.$ann_result[$i]->title.'</button>';
  //     $result['ann_data_html'] .= '<li><button type="button" class="btn btn-link" data-toggle="modal" data-target="#announcementModal"><span>['.date("Y-m-d", strtotime($ann_result[$i]->effecttime)).']</span>'.$ann_result[$i]->title.'</button>
  //     </li>
  //     ';

  //     // $menuContentHtml .= '<button type="button" class="btn btn-link" data-toggle="modal" data-target="#announcementModal">'.$ann_result[$i]->title.'</button><hr>';
  //     $result['menuContentHtml'] .= '<button type="button" class="list-group-item list-group-item-action announcementDetail">'.$ann_result[$i]->title.'</button>';
  //   }

  //   $result['ann_data_html'] .= '
  //   <div class="modal fade" id="announcementModal" tabindex="-1" role="dialog" aria-labelledby="announcementModalTitle" aria-hidden="true">
  //     <div class="modal-dialog modal-dialog-centered" role="document">
  //       <div class="modal-content">
  //         <div class="modal-header">
  //           <h5 class="modal-title" id="announcementTitle">公告</h5>
  //           <button type="button" class="close" data-dismiss="modal" aria-label="Close">
  //             <span aria-hidden="true">&times;</span>
  //           </button>
  //         </div>
  //         <div class="modal-body" id="announcementBody">
  //           <ul class="list-group" id="announcementList">
  //             '.$result['menuContentHtml'].'
  //           </ul>
  //         </div>
  //         <div class="modal-footer" id="announcementFooter">
  //           <button type="button" class="btn btn-primary btn-lg btn-block" data-dismiss="modal">我知道了</button>
  //         </div>
  //       </div>
  //     </div>
  //   </div>
  //   ';
  // }

	return $result;
}
function Scroll_marquee($speed=20000){
  global $tmpl;
  global $config;
  $html = get_announcement_data();
  // 向右轮播

  $ui['Scroll_marquee'] = <<<HTML
  <style>
  .marqueebox {
  }
  .marqueebox .title {
    float: left;
    font-size: 18px;
    line-height: 32px; 
  }
  .marquee {
    width: calc(100% - 30px);
    overflow: hidden;
    float: right;
  }
  .marquee a {
   letter-spacing: 1px;
   line-height: 32px;
  }
  .marquee li {
    display: inline-block;
        line-height: 32px;
  }
  .js-marquee li span {
      letter-spacing: 1px;
      margin-right: 10px;
  }
  </style>

  <div class="marqueebox newSection">
    <div class="col-12">
    <span class="title lnr lnr-volume-high"></span>
     <div class="marquee" data-direction="left">
      {$html['ann_data_html']}
     </div>
  	 <div class="tempWrap" style="display: none;">
        <ul class="marqueelist">
          {$html['ann_data_html']}
        </ul>
     </div>     
    </div>
  </div> 
HTML;

$pausehover = ($config['site_style']=='mobile')?'':',pauseOnHover: true';

  if($html['ann_data_html']==''){
    $ui['Scroll_marquee'] ='';
  }else{    
    $tmpl['extend_js'] .=<<<HTML
  {$html['modal_html']}
  <script type="text/javascript">
  $('.marquee').marquee({
    duration : '{$speed}'
    {$pausehover}
  });

  $(document).on('click', '.announcementDetail', function() {
    //console.log('announcementDetail');
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
                              .html(`<button type="button" class="btn btn-lg btn-block" id="announcementMore">更多公告</button>`);
    }).fail(function(){
      alert("Request failed : 公告查詢失敗"); 
    });
  });

  $(document).on('click', '#announcementMore', function() {
    //console.log("announcementMore");
    $("#announcementTitle").html('')
                            .append(`<h5>公告</h5>`);

    $("#announcementBody").html('')
                          .append(`<ul class="list-group" id="announcementList">{$html['menuContentHtml']}</ul>`);

    $("#announcementFooter").html('')
                            .html(`<button type="button" class="btn btn-lg btn-block" data-dismiss="modal">我知道了</button>`);
  });

  $('#announcementModal').on('hidden.bs.modal', function (e) {
    $("#announcementTitle").html('')
                            .append(`<h5>公告</h5>`);

    $("#announcementBody").html('')
                          .append(`<ul class="list-group" id="announcementList">{$html['menuContentHtml']}</ul>`);

    $("#announcementFooter").html('')
                            .html(`<button type="button" class="btn btn-lg btn-block" data-dismiss="modal">我知道了</button>`);
  });
  </script>
HTML;
  }
  return $ui['Scroll_marquee'];
}
// ----------------------------------------------------------------------------
// 跑馬燈公告欄 end
// ----------------------------------------------------------------------------
?>