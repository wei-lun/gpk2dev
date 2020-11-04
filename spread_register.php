<?php
// ----------------------------------------------------------------------------
// Features:  前端 -- 推廣註冊
// File Name: spread_register.php
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

// require_once dirname(__FILE__) ."/lib_view.php";
require_once dirname(__FILE__) ."/spread_register_lib.php";
require_once dirname(__FILE__) ."/lib_agents_setting.php";
//require_once dirname(__FILE__) ."/in/mobiledetect/Mobile_Detect.php";

// ----------------------------------------------------------------------------
// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------

// 初始化變數
// 功能標題，放在標題列及meta
$function_title = $tr['membercenter_spread_register'];//'邀请码'
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
  <li><a href="agentarea_munu.php">主選單</a></li>
  <li class="active">'.$function_title.'</li>
</ul>
';

if($config['site_style']=='mobile'){
  $navigational_hierarchy_html =<<<HTML
    <a href="{$config['website_baseurl']}menu_admin.php?gid=agent"><i class="fas fa-chevron-left"></i></a>
    <span>$function_title</span>
    <i>
    <a class="btn btn-primary spreadadd_btn" href="./spread_register_add.php" role="button">{$tr['add']}</a>
    </i>
HTML;
}
// ----------------------------------------------------------------------------
if (!isset($_SESSION['member']) || $_SESSION['member']->therole != 'A') {
  echo '<script>document.location.href="./home.php";</script>';
}

function combineSelectOptionHtml(array $option)
{
  $optionHtml = '';

  foreach ($option as $k => $v) {
    $optionHtml .= <<<HTML
    <option value="{$k}">{$v}</option>
HTML;
  }

  return $optionHtml;
}

function combineRadioOptionHtml(array $htmlContent, $type)
{
  global $config;
  $optionHtml = '';


  foreach ($htmlContent as $k => $v) {

    if ($type != '' && $k == $type && $v['isChecked'] == '') {
      $v['isChecked'] = 'checked';
    } elseif ($type != '' && $k != $type && $v['isChecked'] != '') {
      $v['isChecked'] == '';
    }

if($config['site_style']=='mobile'){
$optionHtml .= <<<HTML
    <div class="form-check col">
      <input class="form-check-input" type="radio" name="{$v['name']}" id="{$v['id']}" value="{$k}" {$v['isChecked']}>
      <label class="form-check-label" for="{$v['id']}">{$v['translation']}</label>
      <div class="active_border"></div>
    </div>
HTML;
} else {
$optionHtml .= <<<HTML
    <div>
      <input class="spread_input_style" type="radio" name="{$v['name']}" id="{$v['id']}" value="{$k}" {$v['isChecked']}>
      <label for="{$v['id']}">{$v['translation']}</label>
    </div>
HTML;
}

}
  return $optionHtml;
}

function combineTableContentHtml($data, $linkCode)
{

  global $tr;
  $html = '';
  foreach ($data as $v) {
    $registerCount = ($v->register_count == '') ? '0' : $v->register_count;

    $highlight = ($linkCode == $v->link_code) ? 'table-success' : '';

    if (comparison_date(getEDTDate(), $v->end_date)) {
      $dateStr = $v->end_date;
      $htmlIdPrefix = 'e';
      $expired = '';
    } else {
      $dateStr = $tr['spread link Expired'];//'邀请码过期'
      $htmlIdPrefix = 'd';
      $expired = 'expired';
    }

    $html .= <<<HTML
    <tr class="dataCol {$highlight} row {$expired}" id="{$htmlIdPrefix}_{$v->link_code}">
      <td class="linkCode col" scope="row">{$v->link_code}</td>
      <td class="col text-truncate">{$dateStr}</td>
      <td class="col"><div>{$tr['register']} ({$registerCount})</div></td>
    </tr>
HTML;
  }

  return $html;
}

$memberType = [
  'M' => [
    'id' => 'memberRadio',
    'name' => 'registerTypeOptions',
    'translation' => $tr['member'],//'会员'
    'isChecked' => 'checked'
  ],
  'A' => [
    'id' => 'agentRadio',
    'name' => 'registerTypeOptions',
    'translation' => $tr['agent'],//'代理'
    'isChecked' => '' 
  ]
];

$linkCode = (isset($_GET['i'])) ? filter_var($_GET['i'], FILTER_SANITIZE_STRING) : '';
$registerType = (isset($_GET['t'])) ? filter_var($_GET['t'], FILTER_SANITIZE_STRING) : 'M';

$registerTypeHtml = combineRadioOptionHtml($memberType, $registerType);

// $defaultRegisterType = ($registerType == '') ? 'M' : $registerType;

$linkData = getSpreadLinkByAccountType($_SESSION['member']->account, $registerType);

if ($linkData) {
  $tableContentHtml = combineTableContentHtml($linkData, $linkCode);
} else {
    //查无邀请码资料
  $tableContentHtml = <<<HTML
  <tr class="row no_data">
    <td colspan="3" class="col no_data_style"><p>{$tr['spread link data not found']}</p></td>
  </tr>
HTML;

}
if($config['site_style']=='mobile'){
$registerTypeHtml = <<<HTML
<div class="nav-headerbutton row">
  {$registerTypeHtml}
</div>
HTML;
} else {
$registerTypeHtml = <<<HTML
<div class="registerTypeArea type_menu">
  <form>
    <div class="form-group mb-0">
      <div class="type_menu_style">
        {$registerTypeHtml}
      </div>
    </div>
  </form>
</div>
HTML;
}
$registration_gcash_alert = ($protalsetting['agency_registration_gcash'] == '0')? '':<<<HTML
<div id="registration_gcash_alert" class="row" style="display:none;">
  <div class="col alert-danger p-3" role="alert">
    {$tr['Agent Application Fee has been activated, so it is not supported to add agents through the invitation code']}
  </div>
</div>
HTML;
$detail_status_alert =<<<HTML
{$tr['Can not register now'] }
<button type="button" class="prompt_btn" data-container="body" data-toggle="popover" data-placement="right" data-content="{$tr['Agent Application Fee has been activated, so it is not supported to add agents through the invitation code']}">
  <i class="fa fa-info-circle" aria-hidden="true"></i>
</button>
HTML;
$linkDataHtml = <<<HTML
<div class="linkDataArea spreadregister">
  <div class="linkDataAreaBody col">
    {$registration_gcash_alert}
    <table class="table table_liststyle" id="linkDataTable">
      <thead>
        <tr class="row">
          <th scope="col" class="col">{$tr['spread link number']}</th>
          <th scope="col" class="col">{$tr['spread link expire day']}</th>
          <th scope="col" class="col"><div>{$tr['spread link status']}</div></th>
        </tr>
      </thead>
      <tbody id="linkDataTableBody">
        {$tableContentHtml}
      </tbody>
    </table>
  </div>
</div>
HTML;
if($config['site_style']=='desktop' || $config['themepath'] == 'landscapem'){
  //新增按鈕
  $desktop_addbtn =<<<HTML
    <div class="d-flex align-items-center">
      <a class="btn btn-primary spread_btn" href="./spread_register_add.php" role="button">
      </span>{$tr['add']}
      </a>
    </div>
HTML;
}else{$desktop_addbtn='';}
$indexbody_content = <<<HTML
        <div class="tablehead">
          {$registerTypeHtml}
          {$desktop_addbtn}      
        </div>
        {$linkDataHtml}
HTML;
$extend_head = <<<HTML
<script>
var csrf = '{$csrftoken}';
var detailStatusAlert = `{$detail_status_alert}`;
</script>
<script src="in/jquery.qrcode.js"></script>
<script src="in/qrcode.js"></script>
<script src="in/js/spread_register.js?version_key={$config['cdn_version_key']}"></script>
<script src="in/js/clipboard.min.js"></script>
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
// 側邊欄MENU var[0]側邊欄組合(lib_menu) var[1]目前頁籤名
$tmpl['sidebar_content'] =['agent','spread_register'];
// banner標題
$tmpl['banner'] = ['membercenter_menu_admin_agent'];
// menu增加active
$tmpl['menu_active'] =['agent_instruction.php'];
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