<?php
// ----------------------------------------------------------------------------
// Features:    前端 -- 站內信件, 使用在傳遞站內的系統訊息
// File Name:   stationmail.php
// Author:      Neil
// Related:     stationmail_action.php
// Table :      
// Log:
// 只有登入的會員才可以看到這個功能。
//
// ----------------------------------------------------------------------------

// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

require_once dirname(__FILE__) ."/stationmail_lib.php";

// var_dump($_SESSION);

// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------


// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------

// 初始化變數
// 功能標題，放在標題列及meta
//站內信件
$function_title         = $tr['membercenter_stationmail'];
// 擴充 head 內的 css or js
$extend_head                = '';
// 放在結尾的 js
$extend_js                  = '';
// body 內的主要內容
$indexbody_content  = '';
// 系統訊息選單
$messages                   = '';
// 初始化變數 end
// ----------------------------------------------------------------------------

if(!isset($_SESSION['member']) || $_SESSION['member']->therole == 'T' ) {
  echo login2return_url(2);
  die($tr['permission error']);//'不合法的帐号权限'
}

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

//  收件匣
$inboxMail = getInboxMailData();

$inboxTableContent = '';
$inboxLoadMoreBtn = '';

if ($inboxMail) {
  if (count($inboxMail) == 7) {
    $inboxLoadMoreBtn = '<button type="button" class="send_btn load-more" id="inbox-load-more">'.$tr['load more'].'</button>';
  }

  foreach ($inboxMail as $k => $v) {
    $subject = ($v['isRead'] == 'unread') ? '<p>'.$v['subject'].'</p>' : $v['subject'];
    $messagetext = strip_tags($v['message']);

    if($config['site_style'] == 'mobile') {
      $inboxTableContent .= <<<HTML
      <tr class="inbox-data-row row {$v['isRead']}" id="{$v['mailcode']}_{$v['mailtype']}">
        <td class="col-1">
          <input type="checkbox" class="del-inbox" name="delMail" value="{$v['mailcode']}_{$v['mailtype']}">
        </td>
        <td class="col-11 inbox-data-col">
          <div class="title inbox-subject">{$subject}</div>
          <time>{$v['sendtime']}</time>
        </td>
      </tr>
HTML;
    } else {
      $inboxTableContent .= <<<HTML
      <tr class="inbox-data-row row {$v['isRead']}" id="{$v['mailcode']}_{$v['mailtype']}">
        <td>
          <input type="checkbox" class="del-inbox" name="delMail" value="{$v['mailcode']}_{$v['mailtype']}">
        </td>
        <td class="inbox-data-col">{$k}</td>
        <td class="col-8 inbox-data-col inbox-subject">{$subject}</td>
        <td class="col inbox-data-col">{$v['sendtime']}</td>
      </tr>
HTML;
    }
  }
} else {
  $inboxTableContent = '<tr class="row no_data"><td colspan="6" class="col no_data_style"><p>'.$tr['no mail was found'].'</p></td></tr>';
}

// 寄件備份
$sentMail = getSentMailData();

$sentTableContent = '';
$sentLoadMoreBtn = '';

if ($sentMail) {
  if (count($sentMail) == 7) {
    $sentLoadMoreBtn = '<button type="button" class="send_btn load-more" id="sent-load-more">'.$tr['load more'].'</button>';
  }

  foreach ($sentMail as $k => $v) {
    if($config['site_style'] == 'mobile') {
      $sentTableContent .= <<<HTML
      <tr class="sent-data-row row" id="{$v['mailcode']}_{$v['mailtype']}">
        <td class="col-1">
          <input type="checkbox" class="del-sent" name="delMail" value="{$v['mailcode']}_{$v['mailtype']}">
        </td>
        <td class="col-11 sent-data-col">
          <div class="title sent-subject">{$v['subject']}</div>
          <time>{$v['sendtime']}</time>
        </td>
      </tr>
HTML;
    } else {
      $sentTableContent .= <<<HTML
      <tr class="sent-data-row row" id="{$v['mailcode']}_{$v['mailtype']}">
        <td>
          <input type="checkbox" class="del-sent" name="delMail" value="{$v['mailcode']}_{$v['mailtype']}">
        </td>
        <td class="sent-data-col">{$k}</td>
        <td class="col-8 sent-data-col sent-subject">{$v['subject']}</td>
        <td class="col sent-data-col">{$v['sendtime']}</td>
      </tr>
HTML;
    }
  }
} else {
  $sentTableContent = '<tr class="row no_data"><td colspan="5" class="col no_data_style"><p>'.$tr['no mail was found'].'</p></td></tr>';
}

// tab及table欄位名稱html
if($config['site_style']=='mobile') {
  $navheaderbutton = <<<HTML
  <div class="nav nav-tabs nav-headerbutton row" role="tablist">
    <a class="col" href="#outbox-view" aria-controls="outbox-tab" role="tab" data-toggle="tab" id="outbox-tab">{$tr['send mail']}</a>
    <a class="nav-item nav-link box-tab active col" href="#inbox-view" aria-controls="inbox-tab" role="tab" data-toggle="tab" id="inbox">{$tr['inbox']}</a>
    <a class="nav-item nav-link box-tab col" href="#sent-view" aria-controls="sent-tab" role="tab" data-toggle="tab" id="sent">{$tr['send mail backup']}</a>
  </div>
HTML;

  $inboxTableColHtml = <<<HTML
  <tr class="stationmail_input row">
    <td colspan="2" class="d-flex align-items-center">
      <input type="checkbox" id="del-all-inbox" class="del-all-inbox">
      <label for="del-all-inbox" >{$tr['select all']}</label>
      <div class="mobile-refresh-btn">
       <button type="button" class="renew_btn btn btn-secondary">
        <i class="fas fa-redo"></i>
      </button>
      </div>
    </td>
  </tr>
HTML;

  $sentTableColHtml = <<<HTML
  <tr class="row stationmail_input">
    <td colspan="2" class="d-flex align-items-center">
      <input type="checkbox" id="del-all-sent" class="del-all-sent">
      <label for="del-all-sent">{$tr['select all']}</label>
      <div class="mobile-refresh-btn">
       <button type="button" class="renew_btn btn btn-secondary">
        <i class="fas fa-redo"></i>
      </button>
      </div>
    </td>
  </tr> 
HTML;

} else {
  $navheaderbutton = <<<HTML
  <div class="nav nav-tabs page_button" role="tablist">
    <span class="del-sent-select"></span>
    <span class="del-inbox-select"></span>
    <a class="nav-item nav-link box-tab" href="#outbox-view" aria-controls="outbox-tab" role="tab" data-toggle="tab" id="outbox-tab">{$tr['send mail']}</a>
    <a class="nav-item nav-link box-tab active" href="#inbox-view" aria-controls="inbox-tab" role="tab" data-toggle="tab" id="inbox">{$tr['inbox']}</a>
    <a class="nav-item nav-link box-tab" href="#sent-view" aria-controls="sent-tab" role="tab" data-toggle="tab" id="sent">{$tr['send mail backup']}</a>
  </div>
HTML;

  $inboxTableColHtml = <<<HTML
  <tr class="row">
    <th class="">
      <input type="checkbox" class="del-all-inbox  background-none pointer">
    </th>
    <th class="refresh-btn">
      <div>
        <button type="button" class="renew_btn">
          <span class="glyphicon glyphicon-repeat" aria-hidden="true"></span>
        </button>
      </div>
    </th>
  </tr>
HTML;

  $sentTableColHtml = <<<HTML
  <tr class="row">
    <th class="">
      <input type="checkbox" class="del-all-sent  background-none pointer">
    </th>
    <th class="refresh-btn">
      <div>
        <button type="button" class="renew_btn">
          <span class="glyphicon glyphicon-repeat" aria-hidden="true"></span>
        </button>
      </div>
    </th>
  </tr>
HTML;
}

$refreshbtn = <<<HTML
<button type="button" class="btn btn-primary renew_btn">
    <span class="glyphicon glyphicon-repeat" aria-hidden="true"></span>
  </button>
HTML;

$html = <<<HTML
<!-- Tab Html -->
<div>
  <!-- Tab Html -->
  {$navheaderbutton}
  <!-- 收件匣 tab 內容 --> 
  <div class="tab-content"> 
    <div role="tabpanel" class="tab-pane active" id="inbox-view"> 
        <div class="col-12">           
        <table id="inbox_transaction_list" class="table mail-list stationmail_list" width="100%" cellspacing="0">
        <tbody>
          {$inboxTableColHtml}
          {$inboxTableContent}
        </tbody>
      </table>   
      </div>
      {$inboxLoadMoreBtn}
    </div>

    <!-- 寄信 tab 內容 -->
    <div role="tabpanel" class="tab-pane" id="outbox-view">
      <div class="row flex-column stationmail_sedletter">
        <div class="form-group col">
          <label for="recipient" class="control-label">{$tr['recipient']}</label>
          <input type="text" class="form-control" id="recipient" name="recipient" value="{$tr['Customer service']}" disabled="disabled">
        </div>
        <div class="form-group col">
          <label for="sender" class="control-label">{$tr['sender']}</label>
          <input type="text" class="form-control" id="sender" name="sender" value="{$_SESSION['member']->account}" disabled="disabled">
        </div>
        <div class="form-group col">
          <label for="outbox-subject" class="control-label">{$tr['subject']}</label>
          <textarea class="form-control" rows="1" id="outbox-subject" name="outbox-subject" placeholder="{$tr['words number limit']}100{$tr['words number limit end']}"></textarea>
        </div>
        <div class="form-group col">
          <label for="outbox-message" class="control-label">{$tr['content']}</label>
          <textarea class="form-control" cols="45" rows="5" id="outbox-message" name="outbox-message" placeholder="{$tr['words number limit']}1000{$tr['words number limit end']}"></textarea>
        </div>
        <div class="form-group col">
          <button type="button" class="send_btn send-mail btn-primary" id="send-mail" name="send-mail" disabled>{$tr['send']}</button>
        </div>
      </div>
    </div>

    <!-- 寄件備份 tab 內容 -->
    <div role="tabpanel" class="tab-pane" id="sent-view">
      <div class="col-12">      
      <table id="sendABackup_transaction_list" class="table mail-list stationmail_list" width="100%" cellspacing="0">
        <tbody>
          {$sentTableColHtml}
          {$sentTableContent}
        </tbody>
      </table>
      </div>
      {$sentLoadMoreBtn}
    </div>
  </div>
</div>

<!-- Inbox Modal -->
<div class="modal fade" id="inbox-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content modal_contentstyle">
      <div class="modal-header mail-detail-title">
        <p class="modal-title mail-detail-title-word" id="inbox-modal-subject"></p>
      </div>
      <div class="modal-body p-0" id="inbox-modal-body">
        <!-- <input class="form-control d-none" id="inboxCode" type="text" placeholder="Default input"> -->
      </div>
      <div class="modal-footer">                    
        <button type="button" class="btn btn-secondary closs_update_readtime inbox-modal-close" data-dismiss="modal"  id="inbox-modal-close">{$tr['close']}</button>
        <button type="button" class="btn btn-danger delete-mail" id="inbox-delete-mail">{$tr['delete mail']}</button>
      </div>
    </div>
  </div>
</div>

<!-- Sent Modal -->
<div class="modal" id="sendABackup_Modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content modal_contentstyle">
      <div class="modal-header  mail-detail-title">
        <h6 class="modal-title  mail-detail-title-word" id="sent-modal-subject"></h6>
      </div>
      <div class="modal-body p-0" id="sent-modal-body"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary sent-modal-close" data-dismiss="modal" id="inbox-modal-close">{$tr['close']}</button>
        <button type="button" class="btn btn-danger delete-mail" id="sent-delete-mail">{$tr['delete mail']}</button>
      </div>
    </div>
  </div>
</div>
HTML;

$extend_js = <<<HTML
<script>
$(document).on('click', '.inbox-data-col', function() {
  var csrftoken = '{$csrftoken}';
  var code = $(this).parent().attr('id').split('_');

  $('#inbox-modal-subject').empty();
  $('#inbox-modal-body').empty();
  $('#inbox-delete-mail').val('');
  $('#inbox-delete-mail').val(code[0]+'_'+code[1]);

  $.ajax({
    type: "POST",
    url: "stationmail_action.php",
    data: {
      csrftoken: csrftoken,
      data: JSON.stringify({
        'mailcode' : code[0],
        'mailtype' : code[1],
        'source' : 'inbox'
      }),
      action: 'mailDetail'
    },
  }).done(function(resp) {
    // $('#preview_result').html(resp);
    var res = JSON.parse(resp);

    if (res.status == 'success') {
      if($('#'+code[0]+'_'+code[1]).hasClass('unread')){
        $('#'+code[0]+'_'+code[1]+' .readtime').html(res.result.readtime);
        $('#'+code[0]+'_'+code[1]).removeClass('unread');
        $('#'+code[0]+'_'+code[1]+' .inbox-subject').html(res.result.subject);
      }

      var modalBodyHtml = combineInboxModalContentHtml(res.result);
      $('#inbox-modal-subject').append(res.result.subject);
      $('#inbox-modal-body').append(modalBodyHtml);
      $('#inbox-delete-mail').val(code[0]+'_'+code[1]);
    } else {
      alert(res.result);
    }
  }).fail(function(jqXHR, textStatus) {
    alert('Request failed: ' + textStatus);
  });

  setTimeout(function(){
    $('#inbox-modal').modal('show');
  }, 200);
  setTimeout(function(){
    $('.modal-text table').removeAttr('style');
    var bordernum = $('.modal-text table').attr('border');
    if( bordernum == '1' ){
      $('.modal-text table').addClass('borderstyle');
    }else if( bordernum > 1 ){
      $('.modal-text table').addClass('borderstyle');
      $('.modal-text table').css({'border-width':bordernum+'px'});
      $('.modal-text table tr td').css({'border-width':bordernum+'px'});
      $('.modal-text table tr th').css({'border-width':bordernum+'px'});
    }
  }, 400);
});

$(document).on('click', '.sent-data-col', function() {
  var csrftoken = '{$csrftoken}';
  var code = $(this).parent().attr('id').split('_');

  $('#sent-modal-subject').empty();
  $('#sent-modal-body').empty();
  $('#sent-delete-mail').val('');
  $('#sent-delete-mail').val(code[0]+'_'+code[1]);
  $.ajax({
    type: "POST",
    url: "stationmail_action.php",
    data: {
      csrftoken: csrftoken,
      data: JSON.stringify({
        'mailcode' : code[0],
        'mailtype' : code[1],
        'source' : 'sent'
      }),
      action: 'mailDetail'
    },
  }).done(function(resp) {
    // $('#preview_result').html(resp);
    var res = JSON.parse(resp);

    if (res.status == 'success') {
      var modalBodyHtml = combineSentModalContentHtml(res.result);
      $('#sent-modal-subject').append(res.result.subject);
      $('#sent-modal-body').append(modalBodyHtml);
      $('#sent-delete-mail').val(code[0]+'_'+code[1]);
    } else {
      alert(res.result);
    }
  }).fail(function(jqXHR, textStatus) {
    alert('Request failed: ' + textStatus);
  });
  
  setTimeout(function(){
    $('#sendABackup_Modal').modal('show');
  }, 200);
});

$(document).on('click', '.load-more', function() {
  var csrftoken = '{$csrftoken}';
  var tabId = $('.box-tab.active').attr('id');
  var count = (tabId == 'inbox') ? $('.inbox-data-row').length : $('.sent-data-row').length;

  $.ajax({
    type: "POST",
    url: "stationmail_action.php",
    data: {
      csrftoken: csrftoken,
      data: JSON.stringify({
        'source' : tabId,
        'count' : count
      }),
      action: 'loadMore'
    },
  }).done(function(resp) {
    // $('#preview_result').html(resp);
    var res = JSON.parse(resp);
    
    if (res.status === 'success') {
      
      var html = '';
      var loadDataHtml = Object.values(res.result).reduce((accumulator, currentValue, currentIndex) => {
        if (tabId == 'inbox') {
          html = combineInboxTableContent('{$config['site_style']}', ((currentIndex + 1) + count), currentValue);
        } else {
          html = combineSentTableContent('{$config['site_style']}', ((currentIndex + 1) + count), currentValue);
        }        
        return accumulator + html;
      }, '');

      $('.'+tabId+'-data-row:last').after(loadDataHtml);

      if (Object.keys(res.result).length < 7) {
        $('#'+tabId+'-load-more').remove();
      }
    } else {
      alert(res.result)      
      $('#'+tabId+'-load-more').remove();
    }
  }).fail(function(jqXHR, textStatus) {
    alert('Request failed: ' + textStatus);
  });
});

$(document).on('click', '#send-mail', function() {
  var csrftoken = '{$csrftoken}';

  if (confirm('{$tr['Are you sure to send the mail']}')) {
    $.ajax({
      type: "POST",
      url: "stationmail_action.php",
      data: {
        csrftoken: csrftoken,
        data: JSON.stringify({
          'subject' : $('#outbox-subject').val(),
          'message' : $('#outbox-message').val()
        }),
        action: 'sendMail'
      },
    }).done(function(resp) {
      // $('#preview_result').html(resp);
      var res = JSON.parse(resp);

      if (res.status === 'success') {
        alert(res.result);
        location.reload();
      } else {
        alert(res.result);
        location.reload();
      }
    }).fail(function(jqXHR, textStatus) {
      alert('Request failed: ' + textStatus);
    });
  }
});

$(document).on('click', '.delete-mail', function() {
  var csrftoken = '{$csrftoken}';

  var tabId = $('.box-tab.active').attr('id');
  var btnId = $(this).attr('id');
  var mails = (btnId === 'delete-mails') ? $('.' + $(this).val()).serialize() : $(this).val();

  if (confirm('{$tr['Are you sure to delete the mail']}')) {
    $.ajax({
      type: "POST",
      url: "stationmail_action.php",
      data: {
        csrftoken: csrftoken,
        data: JSON.stringify({
          'mails' : mails,
          'source' : tabId
        }),
        action: 'deleteMail'
      },
    }).done(function(resp) {
      // $('#preview_result').html(resp);
      var res = JSON.parse(resp);

      if (res.status === 'success') {
        alert(res.result);
        location.reload();
      } else {
        alert(res.result);
        location.reload();
      }
    }).fail(function(jqXHR, textStatus) {
      alert('Request failed: ' + textStatus);
    });
  }
});

$(document).on('click', '.renew_btn', function() {
  location.reload();
});

$(document).on('change', '.del-all-inbox', function() {
  $('.del-inbox').prop('checked', $(this).prop('checked'));

  addRemoveDelButton('del-inbox');
});

$(document).on('change', '.del-all-sent', function() {
  $('.del-sent').prop('checked', $(this).prop('checked'));

  addRemoveDelButton('del-sent');
});

$(document).on('change', '.del-inbox', function() {
  if ($(".del-inbox:checked").length != $('.del-inbox').length) {
    $('.del-all-inbox').prop('checked', false);
  } else {
    $('.del-all-inbox').prop('checked', true);
  }

  addRemoveDelButton('del-inbox');
});

$(document).on('change', '.del-sent', function() {
  if ($(".del-sent:checked").length != $('.del-sent').length) {
    $('.del-all-sent').prop('checked', false);
  } else {
    $('.del-all-sent').prop('checked', true);
  }

  addRemoveDelButton('del-sent');
});

$('a[data-toggle="tab"]').on('shown.bs.tab', function(e) { 
  $(".delAllInbox").prop('checked', false);
  $(".del-inbox").prop('checked', false);
  $(".del-all-sent").prop('checked', false);
  $(".del-all-inbox").prop('checked', false);
  $(".del-sent").prop('checked', false);
  $('.delete-icon').remove();
});

$(document).on('keyup', '#outbox-subject', function() {  
  var curLength = $('#outbox-subject').val().length;
  if (curLength > 100) {
    var num = $('#outbox-subject').val().substr(0, 100);
    $('#outbox-subject').val(num);
    alert('{$tr['exceed word limit']}');
  } else {
    $('#textCount').text(100 - $('#outbox-subject').val().length);
  }
});

$(document).on('keyup', '#outbox-message', function() {
  var curLength = $('#outbox-message').val().length;

  if (curLength > 1000) {
    var num = $('#outbox-message').val().substr(0, 1000);    
    $('#outbox-message').val(num);
    alert('{$tr['exceed word limit']}');
  } else {
    $('#textCount').text(1000 - $('#outbox-message').val().length);
  }
});

function addRemoveDelButton(checkboxClass)
{  
  if ($('.'+checkboxClass+':checked').length > 0) {
    if ($('.delete-icon').length == 0) {
      //$('.select-all').before(`
      $('.refresh-btn').after(`
      <th class="delete-icon">
        <div>
          <button type="button" class="stationmail_buttondel delete-mail" id="delete-mails" value="`+checkboxClass+`">
            <i class="fas fa-trash"></i>
          </button>
        </div>
      </th>
      `);
      $('.mobile-refresh-btn').after(`
        <div class="delete-icon">
          <button type="button" class="stationmail_buttondel delete-mail" id="delete-mails" value="`+checkboxClass+`">
            <i class="fas fa-trash"></i>
          </button>
        </div>
      `);      
    }
  } else {
    $('.delete-icon').remove();
  }
}

function combineInboxTableContent(siteStyle, index, data)
{
  var subject = (data.subject == 'unread') ? `<p>`+data.subject+`</p>` : data.subject;
  var textmessage = jQuery(data.message).text();
  if(siteStyle == 'mobile') {
    var html = `
    <tr class="inbox-data-row row `+data.isRead+`" id="`+data.mailcode+`_`+data.mailtype+`">
      <td class="col-1">
        <input type="checkbox" class="del-inbox" name="delMail" value="`+data.mailcode+`_`+data.mailtype+`">
      </td>
      <td class="col-11 inbox-data-col">
        <div class="title inbox-subject">`+subject+`</div>
        <time>`+data.sendtime+`</time>
      </td>
    </tr>
    `;
  } else {
    var html = `
    <tr class="inbox-data-row row `+data.isRead+`" id="`+data.mailcode+`_`+data.mailtype+`">
      <td>
        <input type="checkbox" class="del-inbox" name="delMail" value="`+data.mailcode+`_`+data.mailtype+`">
      </td>
      <td class="inbox-data-col">`+index+`</td>
      <td class="col-8 inbox-data-col inbox-subject">`+subject+`</td>
      <td class="col inbox-data-col">`+data.sendtime+`</td>
    </tr>
    `;
  }

  return html;
}

function combineSentTableContent(siteStyle, index, data)
{
  if(siteStyle == 'mobile') {
    var html = `
    <tr class="sent-data-row row" id="`+data.mailcode+`_`+data.mailtype+`">
      <td class="col-1">
        <input type="checkbox" class="del-sent" name="delMail" value="`+data.mailcode+`_`+data.mailtype+`">
      </td>
      <td class="col-11 sent-data-col">
        <div class="title sent-subject">`+data.subject+`</div>
        <time>`+data.sendtime+`</time>
      </td>
    </tr>
    `;
  } else {
    var html = `
    <tr class="sent-data-row row" id="`+data.mailcode+`_`+data.mailtype+`">
      <td>
        <input type="checkbox" class="del-sent" name="delMail" value="`+data.mailcode+`_`+data.mailtype+`">
      </td>
      <td class="sent-data-col">`+index+`</td>
      <td class="col-8 sent-data-col sent-subject">`+data.subject+`</td>
      <td class="col sent-data-col">`+data.sendtime+`</td>
    </tr>
    `;
  }

  return html;
}

function combineInboxModalContentHtml(data)
{
  var html = `
  <div class="container-fluid modal-time py-3">
    <div class="row">
      <div class="col-3 sta_name">{$tr['sender']}:</div>
      <div class="col-9 ml-auto">{$tr['customer service']}</div>
    </div>                            
    <div class="row">
      <div class="col-3 sta_name">{$tr['date']}:</div>
      <div class="col-9 ml-auto">`+data.sendtime+`</div>
    </div>
    <div class="row">
      <div class="col-3 sta_name">{$tr['date read']}:</div>
      <div class="col-9 ml-auto font-1r">`+data.readtime+`</div>
    </div>
  </div>
  <div class="modal-body modal-text">`+data.message+`</div>
  `;

  return html;
}

function combineSentModalContentHtml(data)
{
  var html = `
  <div class="container-fluid modal-time py-3">
    <div class="row">
      <div class="col-3">{$tr['sender']}:</div>
      <div class="col-9 ml-auto">`+data.msgfrom+`</div>
    </div>                            
    <div class="row">
      <div class="col-3">{$tr['recipient']}:</div>
      <div class="col-9 ml-auto">{$tr['customer service']}</div>
    </div>
    <div class="row">
      <div class="col-3">{$tr['date']}:</div>
      <div class="col-9 ml-auto font-1r">`+data.sendtime+`</div>
    </div>
  </div>
  <div class="modal-body modal-text">`+data.message+`</div>
  `;

  return html;
}

//判斷主旨與內容
$(document).ready(function(){
      //判斷主旨是否有輸入文字    
    $('#outbox-subject').click(function(){
      $('#outbox-subject').keyup(function(){
        var title = $("#outbox-subject").val();
        var content = $("#outbox-message").val();
        if( content.trim() != "" && title.trim() !="" ){
            $('#send-mail').removeAttr('disabled');            
        }else if( $("#outbox-subject").val() == "" ){
          $('#send-mail').attr("disabled", true);
        }
      });      
    });

    $('#outbox-message').click(function(){        
      $('#outbox-message').keyup(function(){
        var title = $("#outbox-subject").val();
        var content = $("#outbox-message").val();
        if( content.trim() != "" && title.trim() !="" ){
            $('#send-mail').removeAttr('disabled');
        }else if( $("#outbox-message").val() == "" ){
          $('#send-mail').attr("disabled", true);
        }        
      });      
    });
});

</script>
HTML;

$output_html = <<<HTML
<div id="stationmail_mail" class="row">    
  <div class="col">
   {$html}      
  </div>           
</div>
<div id="preview_result"></div>
HTML;


// ----------------------------------------------------------------------------
// 準備填入的內容
// ----------------------------------------------------------------------------
// 將內容塞到 html meta 的關鍵字, SEO 加強使用
$tmpl['html_meta_description']      = $config['companyShortName'];
$tmpl['html_meta_author']                   = $config['companyShortName'];
$tmpl['html_meta_title']                    = $function_title.'-'.$config['companyShortName'];

// 系統訊息顯示
$tmpl['message']                                    = $messages;
// 擴充再 head 的內容 可以是 js or css
$tmpl['extend_head']                            = $extend_head;
// 擴充於檔案末端的 Javascript
$tmpl['extend_js']                              = $extend_js;
// 主要內容 -- title
$tmpl['paneltitle_content']             = $navigational_hierarchy_html;
// 主要內容 -- content
$tmpl['panelbody_content']              = $output_html;
// 側邊欄MENU var[0]側邊欄組合(lib_menu) var[1]目前頁面檔名
$tmpl['sidebar_content'] = ['message','stationmail'];
// banner標題
$tmpl['banner'] = ['membercenter_menu_admin_message'];
// menu增加active
$tmpl['menu_active'] =['announcement.php'];
// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include($config['template_path']."template/admin.tmpl.php");