<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 站內信件, 使用在傳遞站內的系統訊息
// File Name:	stationmail.php
// Author:		Yuan
// Related:     stationmail_action.php
// Table :      root_stationmail
// Log:
// 只有登入的會員才可以看到這個功能。
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
$function_title 		= $tr['membercenter_announcement'];
// 擴充 head 內的 css or js
$extend_head				= '';
// 放在結尾的 js
$extend_js					= '';
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

function getAllAnnouncement()
{
  $tzname = 'posix/Etc/GMT+4';

  $sql = <<<SQL
  SELECT *,
        to_char((effecttime AT TIME ZONE '{$tzname}'),'YYYY-MM-DD HH24:MI:SS') AS effecttime,
        to_char((endtime AT TIME ZONE '{$tzname}'),'YYYY-MM-DD HH24:MI:SS') AS endtime
  FROM root_announcement 
  WHERE status = '1' 
  AND now() < endtime 
  AND effecttime < now() 
  ORDER BY id 
  LIMIT 100;
SQL;

  $result = runSQLall($sql);

  if (empty($result[0])) {
    return false;
  }

  unset($result[0]);

  return $result;
}

// ------------------------------------------------------------------------------------
// 站內信 start
// ------------------------------------------------------------------------------------
// 有會員資料 不論身份 therole 為何，都可以修改個人資料。但是除了 therole = T 除外。
if(isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T' ) {
  $allAnnouncement = getAllAnnouncement();

  //var_dump($result);
  $ann_system_title = $tr['ann system title'];

    if ($allAnnouncement) {
      $ann_item_html = '';
  
      foreach ($allAnnouncement as $v) {
        // $id = base64_encode($v->id);
        $id = $v->id;
        $content = htmlspecialchars_decode($v->content);
        $ann_title = $v->title;
        $ann_title = mb_strlen($v->title, 'utf-8');
        $ann_title = mb_substr($v->title,0);
        // if($config['site_style']=='mobile') {
        //   $ann_title = ($ann_title > 40) ? mb_substr($v->title,0, 40) : $v->title;
        // } else {
        //   $ann_title = ($ann_title > 43) ? mb_substr($v->title,0, 43) : $v->title;
        // }       
        $date = date('Y-m-d H:i:s', strtotime($v->effecttime));
        if($config['site_style']=='mobile'){
          $ann_item_html .= <<<HTML
            <tr>
            <td>
              <a href="#" class="row announcement fly_window_open" id="{$id}" data-toggle="modal" data-target="#admin-announcementModal">
              <div class="col-11">
                <div class="announcement_title">{$ann_title}</div>
                <time class="announcement_time">{$date}</time>
                </div>
                <div class="col-1">
                  <i class="fas fa-chevron-right tail"></i>
                </div>
              </a>
            </td>
            </tr>
HTML;
        }else{
          $ann_item_html .= <<<HTML
            <tr>
            <td>
              <a href="#" class="row announcement fly_window_open" id="{$id}" data-toggle="modal" data-target="#admin-announcementModal">
              <div class="col-9">
                <div class="announcement_title">{$ann_title}</div> 
                </div>
                <div class="col-3 d-flex align-items-center">
                  <time class="announcement_time mr-4 ml-auto">{$date}</time>
                  <i class="fas fa-chevron-right tail"></i>
                </div>            
              </a>
            </td>
            </tr>
HTML;
        }
      
    }

    $system_ann_html = <<<HTML
    <table class="table table_form2 announcement_content">
    <tbody>
    {$ann_item_html}
    </tbody>
    </table>
HTML;

  } else {
    $system_ann_html = <<<HTML
    <div class="main_content">
    <div class="no_data_style" role="alert">
        <p class="mb-0">{$tr['no announcenment']}</p>
    </div>
  </div>
HTML;
  }

  $announcement_modal_html = <<<HTML
  <div class="modal fade text-dark announcementModal" id="admin-announcementModal" tabindex="-1" role="dialog" aria-labelledby="announcementmodaltitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content modal_contentstyle">
        <div class="modal-header mail-title">
          <h6 class="modal-title font-weight-bold" id="announcementmodaltitle"></h6>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <h6 class="modal-header modal-time mail-detail-header m-0" id="announcementmodaltime"></h6>
        <div class="modal-body">          
          <div id="announcementmodaldetail" class="py-2"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-block" data-dismiss="modal">{$tr['off']}</button>
        </div>
      </div>
    </div>
  </div>
HTML;

  if($config['site_style']=='mobile') {
    $output_html = <<<HTML
    <div class="row" id="testArea">
      <div class="col">
        <div class="row">
          <div class="col">
            {$system_ann_html}
          </div>
        </div>        
      </div>
    </div>
    <div id="postresult"></div>
    {$announcement_modal_html}    
HTML;
  } else {
    $output_html = <<<HTML
    <div class="w-100" id="testArea">
      {$system_ann_html}
    </div>
    <div id="postresult"></div>  
    {$announcement_modal_html}  
HTML;
  }  

  // $output_html .= $popupWindowHtml;

} else { 
    // 不合法登入者的顯示訊息
    //(x) 請先登入會員，才可以使用此功能。
    $output_html = login2return_url(0);
}

$extend_js =<<<HTML
    <script>
      $(document).on('click', '.fly_window_open', function() {
        var id = $(this).attr('id');
        var csrftoken = '{$csrftoken}';

        $.ajax({
            type: 'POST',
            url: 'announcement_action.php',
            data: {
              id: id,
              action: 'detail',
              csrftoken: csrftoken
            },
            success: function(resp) {
              // $('#postresult').html(resp);
              var res = JSON.parse(resp);
            if (res.status == 'success') {
                var detailHtml = combineDetailHtml(res.result);
                //$('#testArea').after(detailHtml);
                //console.log(detailHtml);
                //openDetailWindow(res.id);

             } else {

                alert(res.result);
              
           }
         }
         });
      });

      function combineDetailHtml(detail){
        $('#announcementmodaltitle').text(detail.title);
        $('#announcementmodaltime').text(detail.effecttime);
        $('#announcementmodaldetail').html(detail.content);
      }   
      
    </script>
HTML;

// ------------------------------------------------------------------------------------
// 站內信 end
// ------------------------------------------------------------------------------------

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
$tmpl['panelbody_content']				= $output_html;
// 側邊欄MENU var[0]側邊欄組合(lib_menu) var[1]目前頁籤名
$tmpl['sidebar_content'] =['message','announcement'];
// banner標題
$tmpl['banner'] = ['membercenter_menu_admin_message'];
// menu增加active
$tmpl['menu_active'] =['announcement.php'];
// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include($config['template_path']."template/admin.tmpl.php");

?>
