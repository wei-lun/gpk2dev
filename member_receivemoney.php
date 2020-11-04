
<?php
// ----------------------------------------------------------------------------
// Features:	會員彩金領取
// File Name:	member_receivemoney.php
// Author:		Neil
// Related:
// Log:
// 隱藏預設文字，但還是保留系統訊息欄位
//$preview_status_html = '
//<div id="preview_area" class="alert alert-info" role="alert">
//'.$tr['receivemoney info'].'
//</div>
//';
// ----------------------------------------------------------------------------


// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

// var_dump($_SESSION);
//var_dump(session_id());

// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------


/**
 * Timezones list with GMT offset
 *
 * @return array
 * @link http://stackoverflow.com/a/9328760
 */
function tz_list() {
  $zones_array = array();
  $timestamp = time();
  foreach(timezone_identifiers_list() as $key => $zone) {
    date_default_timezone_set($zone);
    $zones_array[$key]['zone'] = $zone;
    $zones_array[$key]['diff_from_GMT'] = 'UTC/GMT ' . date('P', $timestamp);
    $zones_array[$key]['GMT'] = date('P', $timestamp);
  }
  return $zones_array;
}
// 全部的時區列表
$timezone_list = tz_list();
// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
$function_title 		= $tr['membercenter_member_receivemoney'];
// 擴充 head 內的 css or js
$extend_head				= '';
// 放在結尾的 js
$extend_js					= '<script src="./in/jquery.blockUI.js"></script>';
// body 內的主要內容
$indexbody_content	= '';
// 系統訊息選單
$messages 					= '';
// 初始化變數 end
// ----------------------------------------------------------------------------
// 導覽列
$navigational_hierarchy_html = '
<ul class="breadcrumb">
  <li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
  <li><a href="member.php">'.$tr['Member Centre'].'</a></li>
  <li class="active">'.$function_title.'</li>
</ul>
';
if($config['site_style']=='mobile'){
  $navigational_hierarchy_html =<<<HTML
    <a href="{$config['website_baseurl']}menu_admin.php?gid=message"><i class="fas fa-chevron-left"></i></a>
    <span>$function_title</span>
    <i></i>
HTML;
}
// ----------------------------------------------------------------------------

//點擊立即領取按鈕即可領取彩金。已過期及已領取彩金資訊只顯示包含今天30天內的資料。
$preview_status_html = '<div id="preview_area" class="alert-info" role="alert"></div>';

// if ($_SESSION['member']->gtoken_lock != '') {
//   $preview_status_html = $preview_status_html.'
//   <div id="preview_area" class="alert alert-danger" role="alert">
//   提醒您，该帐户目前有现金在娱乐城，将无法领取现金彩金。<br>
//   您可以 <a href="./wallets.php">点击此处</a> 前往取回娱乐城现金。
//   </div>';
// }

function getTodayDate()
{
  $tz = '-04';

  return gmdate('Y-m-d H:i:s',time() + $tz * 3600);
}

function getDateRenge($todayDate)
{
  $twentynineDaysAgo = date('Y-m-d H:i:s', strtotime("$todayDate -29 day"));

  return $twentynineDaysAgo;
}

function getAllReceiveMoneyData($todayDate, $tzname = 'posix/Etc/GMT+4')
{
  $endDate = getDateRenge($todayDate);

  $sql = <<<SQL
  SELECT *,
        to_char((givemoneytime AT TIME ZONE '$tzname'),'YYYY-MM-DD HH24:MI:SS') AS givemoneytime,
        to_char((receivedeadlinetime AT TIME ZONE '$tzname'),'YYYY-MM-DD HH24:MI:SS' ) AS receivedeadlinetime, 
        to_char((receivetime AT TIME ZONE '$tzname'),'YYYY-MM-DD HH24:MI:SS' ) as receivetime 
  FROM root_receivemoney 
  WHERE member_account = '{$_SESSION['member']->account}'
  AND status = '1' 
  AND receivetime IS NULL
  AND to_char((receivedeadlinetime AT TIME ZONE '{$tzname}'),'YYYY-MM-DD HH24:MI:SS') >= '{$todayDate}'
  -- AND to_char((receivedeadlinetime AT TIME ZONE '$tzname'),'YYYY-MM-DD HH24:MI:SS') > '{$endDate}'
  ORDER BY id
  LIMIT 8;
SQL;

  $result = runSQLall($sql);

  if (empty($result[0])) {
    return false;
  }

  unset($result[0]);

  return $result;
}

$bonus_html = '';
$expired_bonus_html = '';
$received_bonus_html = '';
$bonusLoadMoreBtn = '';
if(isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T' ) {
  $today = getTodayDate();

  $bonus_tablevalue_html = '';
  $expired_tablevalue_bonus_html = '';
  $received_tablevalue_bonus_html = '';
  $receiveAllMoneyIsDisabled = '';

  $allReceiveMoneyData = getAllReceiveMoneyData($today);

  if ($allReceiveMoneyData) {
    if (count($allReceiveMoneyData) > 7) {
      $bonusLoadMoreBtn = '<button type="button" class="send_btn load-more" id="bonusLoadMore" value="bonus">'.$tr['load more'].'</button>';
      $allReceiveMoneyData = array_slice($allReceiveMoneyData, 0, 7);
    }

    foreach ($allReceiveMoneyData as $v) {
      $id = $v->id;
      $summary = $v->summary;
      $gcash_balance = $v->gcash_balance;
      $gtoken_balance = $v->gtoken_balance;
      $givemoneytime = $v->givemoneytime;
      $receivedeadlinetime = $v->receivedeadlinetime;
      $receivetime = $v->receivetime;
      $member_fingerprinting = $v->member_fingerprinting;

      if($config['site_style'] == 'mobile') {
        $receiveMoneyStatus = '<button class="btn btn-success btn-sm receiveMoney mr-0 " type="button" value="'.$id.'">'.$tr['get it now'].'</button>';

        $bonus_tablevalue_html .= '
          <tr class="row bonusData" id="bonus_'.$id.'">
            <td class="col-9 title receiveMoneyDetail"><div>'.$summary.'</div></td>
            <td class="col-3 receiveMoneyDetail">$'.($gcash_balance + $gtoken_balance).'</td>
            <td class="col-9 receiveMoneyDetail d-flex align-items-center">'.$tr['time limit'].'：'.$receivedeadlinetime.'</td>
            <td class="col-3">'.$receiveMoneyStatus.'</td>
          </tr>
        ';
      } else {
        $receiveMoneyStatus = '<button class="btn btn-success btn-sm receiveMoney mr-0 " type="button" value="'.$id.'"><i class="fas fa-dollar-sign mr-2"></i>'.($gcash_balance + $gtoken_balance).'</button>';

        $bonus_tablevalue_html .= '
        <tr class="row bonusData" id="bonus_'.$id.'">
          <td class="col-9 title receiveMoneyDetail">
            <div>'
              .$summary.
            '</div>
            <div class="pt-3">'
              .$tr['time limit'].'：'.$receivedeadlinetime.
            '</div> 
          </td>
          <td class="col-3 d-flex align-items-center justify-content-end">'.$receiveMoneyStatus.'</td>
        </tr>
        ';
      } 
    }
  } else {
    $bonus_tablevalue_html = '<div class="bonusData no_data_p no_data_style"><p>'.$tr['search no data'].'</p></div>';
    $bonusLoadMoreBtn = '';
    $receiveAllMoneyIsDisabled = 'disabled';
  }

  // 領取彩金
  $bonus_html = '
  <div class="bonusDataArea col-12">
    <div class="bonusDataAreaBody">
      <table class="table recievemoneyTable table_from4" id="bonusTable">
        <tbody id="bonusTableBody">
          '.$bonus_tablevalue_html.'
        </tbody>
      </table>
    </div>
  </div>
  ';


  // 已領取彩金
  $received_bonus_html = '
  <div class="receivedBonusDataArea col-12">
    <div class="receivedBonusDataAreaBody">
      <table class="table table_from4 recievemoneyTable" id="receivedBonusTable"></table>
    </div>
  </div>
  ';


  // 已過期彩金
  $expired_bonus_html = '
  <div class="expiredBonusDataArea">
    <div class="expiredBonusDataAreaBody col-12">
      <table class="table table_from4 recievemoneyTable" id="expiredBonusTable"></table>
    </div>
  </div>
  ';

  $extend_js .= <<<JS
  <script>
  $(function () {
  $('[data-toggle="popover"]').popover();
  $('.popover-dismiss').popover({
  trigger: 'focus'
  });
  });

  $('#receiveMoneyDetailModal').on('hide.bs.modal', function(e) {
    $('#receiveMoneyDetailModal').remove();
  });

  $('#dialogModal').on('hide.bs.modal', function(e) {
    $('#dialogText').remove();
  });

  $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
    if (e.target.id == 'received' || e.target.id == 'expired') {
      updateTabContent(e.target.id);
    }
  });

  $(document).on( 'click', '.receiveMoney', function() {
    $.blockUI({ message: "<img src=\"{$cdnrooturl}spinner.gif\" />" });
    var id = $(this).val();
    var csrftoken = '{$csrftoken}';

    if ($('#receiveMoneyDetailModal').length != 0) {
      $('#receiveMoneyDetailModal').modal('hide');
    }

    $.ajax({
      type: 'POST',
      url: 'member_receivemoney_action.php',
      data: {
        id: id,
        action: 'receive',
        csrftoken: csrftoken
      },
      success: function(resp) {
        var res = JSON.parse(resp);

        if (res.status == 'success') {
          $('#bonus_'+id).remove();

          if ($('.bonusData ').length == 0 && $('#bonusLoadMore').length == 0) {
            $('#receiveAllMoney').prop('disabled', true);
            $('#bonusTableBody').append(`<div class="bonusData no_data_p no_data_style"><p>{$tr['search no data']}</p></div>`);
          }

          if ($('.bonusData').length == 0 && $('#bonusLoadMore').length != 0) {
            location.reload();
          }
        } else {
          if ($('#receiveMoneyDetailModal').length != 0) {
            $('#receiveMoneyDetailModal').remove();
          }
        }

        $.unblockUI()
        popupHideDialogModal(res.result);
      }
    });
  });

  $(document).on( 'click', '#receiveAllMoney', function() {
    $.blockUI({ message: "<img src=\"{$cdnrooturl}spinner.gif\" />" });
    var csrftoken = '{$csrftoken}';

    $.ajax({
      type: 'POST',
      url: 'member_receivemoney_action.php',
      data: {
        action: 'receiveAll',
        csrftoken: csrftoken
      },
      success: function(resp) {
        var res = JSON.parse(resp);
        $('#dialogModalArea').append(`<p id="dialogText">`+res.result+`</p>`);

        $.unblockUI()
        setTimeout(() => {
          $('#dialogModal').modal('show');
        }, 500);

        setTimeout(() => {
          $('#dialogModal').modal('hide');
          if (res.status == 'success') {
            location.reload();
          }
        }, 2000);
      }
    });
  });

  $(document).on('click', '.receiveMoneyDetail', function() {
    $.blockUI({ message: "<img src=\"{$cdnrooturl}loading_hourglass.gif\" />" });
    var trId = $(this).parent().attr('id').split('_');
    var id = trId[1];
    var areaName = trId[0];
    var csrftoken = '{$csrftoken}';

    $.ajax({
      type: 'POST',
      url: 'member_receivemoney_action.php',
      data: {
        id: id,
        action: 'detail',
        csrftoken: csrftoken
      },
      success: function(resp) {
        var res = JSON.parse(resp);

        if (res.status == 'success') {
          if ($('#receiveMoneyDetailModal').length != 0) {
            $('#receiveMoneyDetailModal').remove();
          }

          $.unblockUI()
          $('#bonus').after(combineDetailHtml(res.result, areaName));
          $('#receiveMoneyDetailModal').modal('show');
        } else {
          popupHideDialogModal(res.result);
        }
      }
    });
  });

  $(document).on('click', '.load-more', function() {
    var dataArea = $(this).val();
    var limit = $('.'+dataArea+'Data').length;
    var csrftoken = '{$csrftoken}';

    $.ajax({
      type: 'POST',
      url: 'member_receivemoney_action.php',
      data: {
        condition: dataArea,
        limit: limit,
        action: 'more',
        csrftoken: csrftoken
      },
      success: function(resp) {
        var res = JSON.parse(resp);

        if (res.status == 'success') {
          $('.'+dataArea+'Data:last').after(combineLoadMoreDataHtml('{$config['site_style']}', dataArea, res.result.data));

          if (res.result.count < 7) {
            $('#'+dataArea+' br').remove();
            $('#'+dataArea+'LoadMore').remove();
          }

          $.unblockUI()
        } else {
          $('#'+dataArea+' br').remove();
          $('#'+dataArea+'LoadMore').remove();

          $.unblockUI()
          popupHideDialogModal(res.result);
        }
      }
    });
  });

  function updateTabContent(source) {
    var csrftoken = '{$csrftoken}';

    $.ajax({
      type: 'POST',
      url: 'member_receivemoney_action.php',
      data: {
        action: source,
        csrftoken: csrftoken
      },
      success: function(resp) {
        var res = JSON.parse(resp);

        if (res.status == 'success') {
          if (res.result.count == 0) {
            popupHideDialogModal('<i class="fas fa-exclamation-circle"></i> {$tr['search no data']}');
          } else {
            $('#'+source+'BonusTable tbody').remove();
            $('#'+source+'BonusTable').append('<tbody>'+combineLoadMoreDataHtml('{$config['site_style']}', source + 'Bonus', res.result.data)+'</tbody>');
            if (res.result.count < 7) {
              $('#'+source+'Bonus br').remove();
              $('#'+source+'Bonus button').remove();
            }
          }
        } else {
          if ($('.'+source+'BonusData').length == 0) {
            $('#'+source+'Bonus br').remove();
            $('#'+source+'Bonus button').remove();
            $('#'+source+'BonusTable').append(`<div class="`+source+`BonusData text-center  no_data_p no_data_style mb-0"><p> `+res.result.data+`</p></div>`);
          }
        }
      }
    });
  }

  function combineDetailHtml(data, areaName) {
    var receiveBtn = '';

    if (areaName == 'bonus') {
      receiveBtn = `<button type="button" class="btn btn-primary receiveMoney" value="`+data.id+`">{$tr['get receivemoney']}</button>`;
    }

    var html = `
    <div class="modal fade" id="receiveMoneyDetailModal" tabindex="-1" role="dialog" aria-labelledby="receiveMoneyDetailModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content modal_contentstyle">
          <div class="modal-header">
            <h5 class="modal-title" id="receiveMoneyDetailModalLabel">{$tr['receivemoney_modal_title']}</h5> 
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            `+combineContentHtml(data)+`
          </div>
          <div class="modal-footer">
          `+receiveBtn+`
            <button type="button" class="btn btn-secondary" data-dismiss="modal">{$tr['off']}</button>            
          </div>
        </div>
      </div>
    </div>
    `;

    return html;
  }

  function combineContentHtml(data) {
    if(data.gcash != 0)
    {
      var moneyhtml = `
      <div class="row">
       <div class="col-5 col-sm-5">{$tr['receivemoney_modal_cash']}</div>
        <div class="col-7 col-sm-7">$`+data.gcash+`</div>
      </div>
      `;
    }else{
      var moneyhtml = `
      <div class="row">
        <div class="col-5 col-sm-5">{$tr['receivemoney_modal_token']}</div>
        <div class="col-7 col-sm-7">$`+data.gtoken+`</div>
      </div>
      `;
    }
    var html = `
    <div class="container-fluid receivemoney_modal">
    <div class="row">
      <div class="col-5 col-sm-5">{$tr['receivemoney_modal_info']}</div>
      <div class="col-7 col-sm-7">`+data.summary+`</div>
    </div>
     `+moneyhtml+` 
    <div class="row">
      <div class="col-5 col-sm-5">{$tr['receivemoney_modal_start_time']}</div>
      <div class="col-7 col-sm-7">`+data.starttime+`</div>
    </div>
    <div class="row">
      <div class="col-5 col-sm-5">{$tr['receivemoney_modal_time_limit']}</div>
      <div class="col-7 col-sm-7">`+data.endtime+`</div>
    </div>
    <div class="row">
      <div class="col-5 col-sm-5">{$tr['receivemoney_modal_received_time']}</div>
      <div class="col-7 col-sm-7">`+data.receivetime+`</div>
    </div>
    </div>
    `;

    return html;
  }

  function combineLoadMoreDataHtml(siteStyle, className, data) {
    var html = '';
    var receiveMoneyStatus = '';

    var loadMoreDataHtml = data.reduce(function(accumulator, currentValue, currentIndex, array) {
      var dateHtml = `<td class="col-9 receiveMoneyDetail d-flex align-items-center">{$tr['time limit']}：`+currentValue.endtime+`</td>`;

      if (className == 'bonus' && siteStyle == 'desktop') {
        receiveMoneyStatus = `<button class="btn btn-success btn-sm receiveMoney mr-0 " type="button" value="`+currentValue.id+`"><i class="fas fa-dollar-sign mr-2"></i>`+currentValue.totalBlance+`</button>`;
      } else if(className == 'bonus'){
        receiveMoneyStatus = `<button class="btn btn-success btn-sm receiveMoney" type="button" value="`+currentValue.id+`">{$tr['get it now']}</button>`;
      }else if (className == 'receivedBonus') {
        receiveMoneyStatus = `<span class="btn btn-outline-secondary btn-sm disabled mr-0">{$tr['Received']}</span>`;
        dateHtml = `<td class="col-9 receiveMoneyDetail d-flex align-items-center">{$tr['receivemoney_modal_received_time']}：`+currentValue.receivetime+`</td>`;
      } else if (className == 'expiredBonus') {
        receiveMoneyStatus = `<span class="btn btn-outline-danger btn-sm disabled mr-0">{$tr['Expired']}</span>`;
      }

      if (className == 'bonus' && siteStyle == 'desktop'){
        html = `
          <tr class="row `+className+`Data " id="`+className+`_`+currentValue.id+`">
            <td class="col-9 title receiveMoneyDetail">
              <div>`+currentValue.summary+`</div>
              <div class="pt-3">{$tr['time limit']}：`+currentValue.endtime+`</div> 
            </td>
            <td class="col-3 d-flex align-items-center justify-content-end">`+receiveMoneyStatus+`</td>
          </tr>
          `;
      }else{
        html = `
          <tr class="row `+className+`Data " id="`+className+`_`+currentValue.id+`">
            <td class="col-9 receiveMoneyDetail"><div>`+currentValue.summary+`</div></td>
            <td class="col-3 receiveMoneyDetail">$`+currentValue.totalBlance+`</td>
            `+dateHtml+`
            <td class="col-3 ">`+receiveMoneyStatus+`</td>
          </tr>
          `;
      }
      

      return accumulator + html;
    }, '');

    return loadMoreDataHtml;
  }

  function popupHideDialogModal(msg)
  {
    $('#dialogModalArea').append(`<p id="dialogText">`+msg+`</p>`);

    setTimeout(() => {
      $('#dialogModal').modal('show');
    }, 500);

    setTimeout(() => {
      $('#dialogModal').modal('hide');
    }, 2000);
  }
  </script>
JS;

if($config['site_style']=='mobile'){
  $header_content = '<div class="col header_description">
  <div class="row">
  <div class="col-8">
    <button type="button" class="btn btn-primary btn-info w-100" id="receiveAllMoney" '.$receiveAllMoneyIsDisabled.'>'.$tr['get all receivemoney'].'</button>
    </div>
  <div class="col">
  <button type="button" class="btn" data-container="body" data-toggle="popover"  data-placement="left" data-content="'.$tr['receivemoney info'].'">
    <i class="fa fa-info-circle" aria-hidden="true"></i> '.$tr['description'].'
  </button>
  </div>      
  </div>
  </div>';
    }else{
  
  $header_content = '';
  
    }
  
    if($config['site_style']=='mobile'){
    $navheaderbutton = '';
    $navheaderbuttonmobile = '
    <div class="nav nav-tabs nav-headerbutton w-100">
        <a class="nav-item nav-link active col" href="#bonus" role="tab" data-toggle="tab">'.$tr['Uncollected'].'</a>
        <a class="nav-item nav-link col" id="received" href="#receivedBonus" role="tab" data-toggle="tab">'.$tr['Received'].'</a>
        <a class="nav-item nav-link col" id="expired" href="#expiredBonus" role="tab" data-toggle="tab">'.$tr['Expired'].'</a>    
    </div>';
  
    }else{
  // 將各 tab 表格內容填入
  $navheaderbuttonmobile = '';
  $navheaderbutton = '
  <div>
    <!-- tabs 选单-->
    <div class="nav nav-tabs position-relative" role="tablist">
      <a class="nav-item nav-link active" href="#bonus" role="tab" data-toggle="tab">'.$tr['Uncollected'].'</a>
      <a class="nav-item nav-link" id="received" href="#receivedBonus" role="tab" data-toggle="tab">'.$tr['Received'].'</a>
      <a class="nav-item nav-link" id="expired" href="#expiredBonus" role="tab" data-toggle="tab">'.$tr['Expired'].'</a>
      <div class="d-flex align-items-center ml-auto">
        <button type="button" class="btn nav-headerbuttonpc ml-auto" data-container="body" data-toggle="popover" data-placement="left" data-content="'.$tr['receivemoney info'].'">
          <i class="fa fa-info-circle" aria-hidden="true" title="'.$tr['description'].'"></i>
        </button>
        <button type="button" class="btn btn-primary" id="receiveAllMoney" '.$receiveAllMoneyIsDisabled.'>'.$tr['get all receivemoney'].'</button>  
      </div>  
    </div>';
    }

    // 將各 tab 表格內容填入
    $tab_html = $navheaderbutton.'
    <!-- 内容显示 -->
      <div class="tab-content row">
        <div role="tabpanel" class="tab-pane active col" id="bonus">
          '.$bonus_html.'
          '.$bonusLoadMoreBtn.'
        </div>
        <div role="tabpanel" class="tab-pane col" id="receivedBonus">
          '.$received_bonus_html.'
          <button type="button" class=" load-more send_btn col" id="receivedBonusLoadMore" value="receivedBonus">'.$tr['load more'].'</button>
        </div>
        <div role="tabpanel" class="tab-pane col" id="expiredBonus">
          '.$expired_bonus_html.'
          <button type="button" class="send_btn load-more " id="expiredBonusLoadMore" value="expiredBonus">'.$tr['load more'].'</button>
        </div>
      </div>
    </div>
  ';

  //功能選單(美工)  功能選單(廣告)
  $indexbody_content = $indexbody_content.'
  <div class="row" id="member_receivemoney">    
    '.$header_content.'
    '.$navheaderbuttonmobile.'    
    <div class="col-12">
    '.$preview_status_html.'
    '.$tab_html.'
    </div>
  </div>
  <div class="col-12">
    <div class="modal fade bd-example-modal-sm text-dark" id="dialogModal" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content modal_contentstyle">
          <div class="modal-header">
            <h6>'.$tr['hint'].'</h6>
          </div>
          <div class="modal-body" id="dialogModalArea"></div>
          <div class="modal-footer border-0"></div>
        </div>
      </div>
    </div>
  </div>
  ';

} else {
  $indexbody_content = '';
  $logger = $indexbody_content;
  // memberlog 2db('guest','member','notice', "$logger");
  $msg=$tr['no permission login first'];
  $msg_log = $tr['no permission login first'];
  $sub_service='authority';
  memberlogtodb('guest','member','warning',"$msg",'guest',"$msg_log",'f',$sub_service);

  // 回到首頁
  echo '<script>window.location="'.$config['website_baseurl'].'login2page.php";</script>';
}

// ----------------------------------------------------------------------------
// 準備填入的內容
// ----------------------------------------------------------------------------
// 將內容塞到 html meta 的關鍵字, SEO 加強使用
$tmpl['html_meta_description'] 		= $config['companyShortName'];
$tmpl['html_meta_author']	 				= $config['companyShortName'];
$tmpl['html_meta_title'] 					= $function_title.'-'.$config['companyShortName'];

// 系統訊息顯示
$tmpl['message']									= $messages;
// 擴充再 head 的內容 可以是 js or css
$tmpl['extend_head']							= $extend_head;
// 擴充於檔案末端的 Javascript
$tmpl['extend_js']								= $extend_js;
// 主要內容 -- title
$tmpl['paneltitle_content'] 			= $navigational_hierarchy_html;
// 主要內容 -- content
$tmpl['panelbody_content']				= $indexbody_content;
// 側邊欄MENU var[0]側邊欄組合(lib_menu) var[1]目前頁籤名
$tmpl['sidebar_content'] = ['message','member_receivemoney'];
// banner標題
$tmpl['banner'] = ['membercenter_menu_admin_message'];
// menu增加active
$tmpl['menu_active'] =['announcement.php'];
// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include($config['template_path']."template/admin.tmpl.php");

?>