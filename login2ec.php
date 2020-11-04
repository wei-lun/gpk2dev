<?php
// ----------------------------------------------------------------------------
// Features:	會員驗證完 ec 用 email 後，就能夠從 JIGDEMO 登入到 ec。
// File Name:	login2ec.php
// Author:		Webb Lu
// Related:
// Log:
// ------------2017.09.11
// 1. 驗證 ec mail
// 2. 註冊/登入到 ec
// ----------------------
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



parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $uri2ec_arr);
unset($uri2ec_arr['formtype'], $uri2ec_arr['formurl']);
$uri2ec = urldecode(http_build_query($uri2ec_arr));

$location_href = '';
if(!empty($uri2ec)) $location_href = $config['ec_protocol'] . '://' . $config['ec_host'] . '/index.php?' . $uri2ec;

// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
$function_title 		= $tr['EC Login'];
// 擴充 head 內的 css or js
$extend_head				= '';
// 放在結尾的 js
$extend_js					= '<script>
  $(function() {
    var _form = $("#login2ec");
    var _href2ec = "' . $location_href. '";
    if(_form.size() != 1) { console.log("非驗證通過之狀態"); return; }
    if(_href2ec === "") return;

    $.ajax({
      url: _form.attr("action"),
      method: "POST",
      data: _form.serialize(),
      xhrFields: { withCredentials: true },
      beforeSend: function() {
        // alert("等待跳轉...");
        $("body").append(
          [
            $("<div/>", {
              "id": "shadow-masking",
              "style": "width:100%;height:100%;background-color:#000;filter:alpha(opacity=50);-moz-opacity:0.5;opacity:0.5;position:absolute;left:0px;top:0px;display:block;z-index:1000;"}),
            $("<div/>",
              {
                "id":"response_data",
                "style":"border: 8px solid #E8E9F7;width:40%;height:10%;background-color:white;z-index:1001;position:absolute;top:0%;right:0;bottom:0;left:0;margin:auto;text-align:center",
                "html":"<h2>處理中，請稍後...</h2>"
              })
          ]
        );
      },
    }).done(function(response, status, xhr) {
      location.href = _href2ec;
    }).fail(function(error) {
      console.log(error);
    });
  });
</script>';
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
  <li class="active">'.$function_title.'</li>
</ul>
';
// ----------------------------------------------------------------------------


// ----------------------
// 功能：Email 驗證與各狀態對應動作
// ----------------------
// 有會員資料 不論身份 therole 為何，都可以使用此頁面功能。但是除了 therole = T 除外。
if(isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T') {

  $sql = "SELECT * FROM root_member LEFT JOIN root_member_opencart ON root_member.id=root_member_opencart.id WHERE root_member.id = '".$_SESSION['member']->id."';";

	$m = runSQLall($sql, 0, 'r');
	// var_dump($m);

  // 開始生資料
  // ------------------------------
  // 主表格框架 -- 登入到 EC 的資料
  // ------------------------------
  // EC 資料設定

  $ec_login_pair = [
    'email' => $m[1]->ec_account,
    'password' => $m[1]->ec_password
  ];

  $login2ec_acctdata_col = '';

  // email 帳號*、驗證 button、驗證 status、驗證信過期時間

  switch ($m[1]->ec_verifyresult) {
    // 驗證正確
    case '1':
      $preview_status_html = '
        <div id="preview_area" class="alert alert-info" role="alert">
        已通過驗證。
        </div>';

      $login2ec_acctdata_col .= '<tr><td>' . $tr['email'] . '</td>
        <td>
          <div class="form-inline">
            <span>'. $m[1]->ec_account .'</span>
            <form action="'.$config['ec_protocol'].'://'.$config['ec_host'].'/index.php?route=account/login" method="post" target="_blank" id="login2ec">
              <input type="hidden" name="email" value="'.$ec_login_pair['email'].'" />
              <input type="hidden" name="gpk_token" value="'.jwtenc($m[1]->ec_salt, $ec_login_pair).'" />
              <button type="submit" id="email_verify" class="btn btn-success"><span class="glyphicon glyphicon-ok" aria-hidden="true">已通過驗證，點我前往 EC 商城</button>
            </form>
          </div>
        </td></tr>';
      break;

    // 等待確認
    case '2':
      $preview_status_html = '
        <div id="preview_area" class="alert alert-info" role="alert">
        請在 '.date('r', $timeout = strtotime($m[1]->ec_verifytokentimeout)).' 前至您的郵箱收信，並完成驗證。
        </div>';

      if (time() > $timeout) {
        $login2ec_acctdata_col .= '<tr><td>' . $tr['email'] . '</td>
        <td>
          <div class="form-inline">
            <form action="./login2ec_action.php?a=resend_verification" method="post">
              <input type="text" class="form-control" name="email" id="email" placeholder="請輸入 email" value="'. ($ec_login_pair['email'] ?? $m[1]->email) .'">
              <button type="submit" id="email_verify" class="btn btn-primary"><span class="glyphicon glyphicon-question-sign" aria-hidden="true">重新發送驗證信</button>
            </form>
          </div>
        </td></tr>';
      } else {
        $login2ec_acctdata_col .= '<tr><td>' . $tr['email'] . '</td>
        <td>
          <div class="form-inline">
              <input type="text" class="form-control" name="email" id="email" readonly placeholder="'. ($ec_login_pair['email'] ?? $m[1]->email) .'">
              <button type="button" class="btn btn-warning"><span class="glyphicon glyphicon-remove" aria-hidden="true">等待驗證中</button>
          </div>
        </td></tr>';
      }
      break;

    // mail 驗證通過，商城註冊失敗
    case '3':
      $preview_status_html = '
        <div id="preview_area" class="alert alert-warning" role="alert">
        Mail 已通過驗證；商城錯誤，請聯絡客服。
        </div>';

      $login2ec_acctdata_col .= '<tr><td>' . $tr['email'] . '</td>
        <td>
          <div class="form-inline">
            <span>'. $m[1]->ec_account .'</span>
              <input type="hidden" name="email" value="'.$ec_login_pair['email'].'" />
          </div>
        </td></tr>';
      break;

    // 狀態為 0; 預設值或無效
    default:
      $preview_status_html = '
        <div id="preview_area" class="alert alert-info" role="alert">
        請輸入您要綁定 EC 商城的郵箱，驗證後不可再進行更改。
        </div>';

      $login2ec_acctdata_col .= '<tr><td>' . $tr['email'] . '</td>
        <td>
          <div class="form-inline">
            <form action="./login2ec_action.php?a=send_verification" method="post">
              <input type="text" class="form-control" name="email" id="email" placeholder="請輸入 email" value="'. ($ec_login_pair['email'] ?? $m[1]->email) .'">
              <button type="submit" id="email_verify" class="btn btn-primary"><span class="glyphicon glyphicon-question-sign" aria-hidden="true">點擊驗證</button>
            </form>
          </div>
        </td></tr>';
      break;
  }

  // 最終輸出字串
  $login2ec_acctdata_html = $preview_status_html;
  $login2ec_acctdata_html .= '
  <h4><strong><span class="glyphicon glyphicon-user" aria-hidden="true"></span>綁定&nbsp;EC&nbsp;之電子郵件</strong></h4>
  <table class="table table-bordered">
    <tr class="active">
    <td>'.$tr['field'].'</td>
    <td>'.$tr['content'].'</td>
    </tr>
    '.$login2ec_acctdata_col.'
  </table>
  ';

} else {
  // $login2ec_acctdata_html = '(x)你沒有權限，請登入系統。';
  // $logger = $login2ec_acctdata_html;
  // memberlog 2db('guest','member','notice', "$logger");
  $server_protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? 'https' : 'http';

  $login2page_params = [
    'formtype'     => 'GET',
    // 'formurl'      => $server_protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
    'formurl'      => $server_protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME']
  ];
  $login2page_params = array_merge($login2page_params, $uri2ec_arr);
  $login2page_token = jwtenc('123456', $login2page_params);

  header("location: login2page.php?t=$login2page_token");
}



// 切成 3 欄版面 3:8:1
$indexbody_content = '';
//功能選單(美工)  功能選單(廣告)
$indexbody_content = $indexbody_content.'
<div class="row">
  <div class="col-xs-1 col-md-1">
  </div>
  <div class="col-xs-10 col-md-10">
  '.$login2ec_acctdata_html.'
  </div>
  <div class="col-xs-1 col-md-1">
  </div>
</div>
<div class="col-12">
  <div id="preview_result"></div>
<div>
<br>
';



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


// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include($config['template_path']."template/login2ec.tmpl.php");


?>
