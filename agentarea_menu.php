<?php
// ----------------------------------------------------------------------------
// Features:  代理中心主選單
// File Name: agentarea_menu.php
// Author:    Neil
// Related:   
// DB Table:  
// Log:
// ----------------------------------------------------------------------------

// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

// ----------------------------------------------------------------------------
// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------

// 初始化變數
// 功能標題，放在標題列及meta
$function_title = '代理專區主選單';
// 擴充 head 內的 css or js
$extend_head				= '';
// 放在結尾的 js
$extend_js					= '';
// body 內的主要內容
$indexbody_content	= '';
// 系統訊息選單
$messages 					= '';
// ----------------------------------------------------------------------------
// 導覽列
$navigational_hierarchy_html = '
<ul class="breadcrumb">
  <li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
  <li class="active">'.$function_title.'</li>
</ul>
';
// ----------------------------------------------------------------------------

if (!isset($_SESSION['member']) || $_SESSION['member']->therole != 'A') {
  echo '<script>document.location.href="./home.php";</script>';
}

function getLastKey($arr) {
  end($arr);
  $lastKey = key($arr);

  return $lastKey;
}

function combineMenuHtml()
{
  $menuList = '';
  // $arr = ['agency_center', 'invitation_code', 'account_settings', 'betting_detail', 'transaction_detail'];
  $fileArr = [
    'agency_center' => '代理說明', 
    'spread_register' => '邀請碼', 
    'account_settings' => '會員管理', 
    'betting_detail' => '投注明細', 
    'transaction_detail' => '交易明細'
  ];

  $lastKey = getLastKey($fileArr);

  foreach ($fileArr as $file => $name) {
    $menuList .= <<<HTML
    <form>
      <div class="form-row">
        <div class="col text-lift"></div>
        <div class="col text-center">
          <h4>{$name}</h4>
        </div>
        <div class="col text-right">
          <a class="btn btn-primary" href="{$file}.php" role="button"><span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span></a>
        </div>
      </div>
    </form>
HTML;

    if ($file != $lastKey) {
      $menuList = $menuList.'<hr>';
    }
  }

  $html = <<<HTML
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <form>
            <div class="form-row">
              <div class="col text-lift">
                <a class="btn btn-primary" href="agent_report.php" role="button"><i class="fas fa-chevron-left"></i></a>
              </div>
              <div class="col text-center">
                <h4>代理中心</h4>
              </div>
              <div class="col text-right">
              </div>
            </div>
          </form>
        </div>
        <div class="card-body">
          {$menuList}
        </div>
      </div>
    </div>
  </div>
HTML;

  return $html;
}

$menuHtml = combineMenuHtml();

$indexbody_content = <<<HTML
<div class="row">
  <div class="col-12">
    {$menuHtml}
  </div>
</div>
HTML;

// ----------------------------------------------------------------------------
// 準備填入的內容
// ----------------------------------------------------------------------------
// 將內容塞到 html meta 的關鍵字, SEO 加強使用
$tmpl['html_meta_description'] = $config['companyShortName'];
$tmpl['html_meta_author'] = $config['companyShortName'];
$tmpl['html_meta_title'] = $function_title . '-' . $config['companyShortName'];

// 系統訊息顯示
$tmpl['message'] = $messages;
// 擴充再 head 的內容 可以是 js or css
$tmpl['extend_head'] = $extend_head;
// 擴充於檔案末端的 Javascript
$tmpl['extend_js'] = $extend_js;
// 主要內容 -- title
$tmpl['paneltitle_content'] = $navigational_hierarchy_html;
// 主要內容 -- content
$tmpl['panelbody_content'] = $indexbody_content;

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
// 如果有登入的話, 畫面不一樣。
if(isset($_SESSION['member'])) {
  include($config['template_path']."template/admin.tmpl.php");
} else {
  // 訪客註冊使用
  include($config['template_path']."template/member.tmpl.php");
}