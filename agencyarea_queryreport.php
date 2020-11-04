<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 代理商專區，查询報表。
// File Name:	agencyarea_queryreport.php
// Author:		Barkley, Neil
// Related:
// Log:
// ----------------------------------------------------------------------------




// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

// var_dump($_SESSION);

// var_dump(session_id());
// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------
// 自定义 function
// ----------------------------------------------------------------------------
// 簡易判斷是否透過前台其他頁面訪問
function is_from_frontstage() {
  return isset($_SERVER['HTTP_REFERER']) && parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) == $_SERVER['HTTP_HOST'];
}

// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
//加盟聯營股東專區
$function_title = '代理商查询报表';
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
  <li><a href="agencyarea.php">'.$tr['agencyarea title'].'</a></li>
  <li class="active">'.$function_title.'</li>
</ul>
';
// ----------------------------------------------------------------------------

// 檢查產生的 CSRF token 是否存在 , 錯誤就停止使用
$csrftoken = csrf_token_make();

// 有登入，有錢包才顯示。只有代理商可以進入
if(isset($_SESSION['member']) AND ($_SESSION['member']->therole == 'A' OR $_SESSION['member']->therole == 'R') AND  $_SESSION['member']->therole != 'T') {
// --------------------

// $agent_ra_base64 = base64_encode($_SESSION['member']->account);

  // -------------------------------------------------------------------
  // 代理中心的 menu - in lib_menu.php , 传入预设的档名 link
  // -------------------------------------------------------------------
  $menu_agentadmin_html = menu_agentadmin('agencyarea_queryreport.php');
  // -------------------------------------------------------------------

  // 代理商查询报表
  $page_title_html = '
  <div class="myagencyarea_title"">
  '.$function_title.'
  </div>';

  // 提示说明
  $page_message_html = '
  <div class="well well-sm">
  * 請輸入查詢的日期範圍與帳號
  </div>
  ';

  $today = gmdate('Y-m-d',time() + -4 * 3600);

  $start_time = '';
  $end_time = '';
  $search_acc = '';
  if (isset($_GET['a'])) {
    $start_time = $today;
    $end_time = $today;
    $search_acc = $_GET['a'];
  }

  // 代理商搜寻的条件
  $page_action_option_html = '
  <div class="row">
    <div class="col-12">

      <div class="form-inline">
        &nbsp开始日：<input type="text" class="form-control" name="sdate" id="query_date_start_datepicker" value="'.$start_time.'" placeholder="ex:2017-01-20">
        &nbsp结束日：<input type="text" class="form-control" name="edate" id="query_date_end_datepicker" value="'.$end_time.'" placeholder="ex:2017-01-20">
        &nbsp用户名称：<input type="text" class="form-control" name="query_member_account" id="query_member_account" value="'.$search_acc.'" placeholder="請輸入查詢帳號">
        <button class="btn btn-primary" type="submit" role="button" id="submit_to_query">查询</button>
      </div>

    </div>
  </div>
  ';

  $dateyearrange_start = gmdate('Y-m-d',strtotime('- 1 month') + -4 * 3600);
  $dateyearrange_end = $today;
  $datedefauleyear = $today;

  // 指定查询日期
  $extend_js = $extend_js."
  <script>
  $('#query_date_start_datepicker, #query_date_end_datepicker').datetimepicker({
		defaultDate:'".$datedefauleyear."',
		minDate: '".$dateyearrange_start."',
		maxDate: '".$dateyearrange_end."',
		timepicker:false,
		format:'Y/m/d',
		lang:'en'
  });
  </script>
  ";

  // 表格欄位名稱
  $table_colname_html = '
  <tr>
		<th>日期</th>
    <th>'.$tr['personal action'].'</th>
    <th>'.$tr['personal win'].'</th>
		<th>'.$tr['Betting count'].'</th>
	</tr>
  ';

  $extend_js = $extend_js . "
  <script>
  $(document).ready(function() {
    $('#submit_to_query').click(function() {
      var start_time = $('#query_date_start_datepicker').val();
      var end_time = $('#query_date_end_datepicker').val();
      var acc = $('#query_member_account').val();
      var csrftoken = '".$csrftoken."';

      var req_setting = {
        'url': 'agencyarea_queryreport_action.php',
        'type': 'POST',
        'headers': {
          'content-type': 'application/x-www-form-urlencoded',
          'cache-control': 'no-cache'
        },
        'data': {
          'action': 'queryreport',
          'start_time': start_time,
          'end_time': end_time,
          'acc': acc,
          'csrftoken': csrftoken
        }
      };

      $.ajax(req_setting).done(function(response) {
        // console.log(response);
        $('#preview_result').html(response);
      }).fail(function(error) {
        // console.log(error);
        alert(error);
      });

    });
  });
  </script>
  ";

  // 提供前台其他頁面，通過連結與 uname 查詢下線代理商的報表
  if(isset($_GET['uname']) && is_from_frontstage()):
    $account = filter_input(INPUT_GET, 'uname', FILTER_SANITIZE_STRING);
    $sdate  = gmdate('Y-m-d',strtotime('- 1 month') + -4 * 3600);
    $edate = gmdate('Y-m-d',time() + -4 * 3600);

    $extend_js .= <<<HTML
    <script>
    $(function(){
      $('#query_date_start_datepicker').val('{$sdate}');
      $('#query_date_end_datepicker').val('{$edate}');
      $('#query_member_account').val('{$account}');

      $('#submit_to_query').trigger('click');
    });
    </script>
HTML;
  endif;

  // 主要排版: 列出資料, 主表格架構
  $agentadmin_html = '
  <div class="row">
    <div class="col-12">
    '.$menu_agentadmin_html.'
    </div>
  </div>
  <br><br>

  <div class="row">
    <div class="col-12">
    '.$page_title_html.'
    '.$page_message_html.'
    '.$page_action_option_html.'
    </div>
  </div>
  <br>
  <div class="row">
    <div class="col-12" id="preview_result"></div>
  </div>
  <hr>
  <br>
  ';

} else {
  //(X) 代理商組織列表查無相關資料。
  $agentadmin_html = $tr['agency organization no data'];
}

// --------------------------------------------------------------------------


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
$tmpl['panelbody_content']				= $agentadmin_html;

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include($config['template_path']."template/admin.tmpl.php");
?>
