<?php
// ----------------------------------------------------------------------------
// Features:  代理協助註冊
// File Name: register_agenthelp.php
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
require_once dirname(__FILE__) ."/register_lib.php";
require_once dirname(__FILE__) ."/lib_agents_setting.php";
//require_once dirname(__FILE__) ."/in/mobiledetect/Mobile_Detect.php";

require_once dirname(__FILE__) ."/in/phpcaptcha/simple-php-captcha.php";
$_SESSION['register_captcha'] = simple_php_captcha();

require_once dirname(__FILE__)."/casino/RG/lobby_rggame_lib.php";

// ----------------------------------------------------------------------------
// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------

// 初始化變數
// 功能標題，放在標題列及meta
$function_title = $tr['agency register'];
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
    <a href="{$config['website_baseurl']}member_management.php"><i class="fas fa-chevron-left"></i></a>
    <span>$function_title</span>
    <i></i>
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

function combineRadioOptionHtml(array $htmlContent)
{
  $optionHtml = '';

  foreach ($htmlContent as $k => $v) {
    $optionHtml .= <<<HTML
    <div class="form-check form-check-inline mr-4">
      <input class="form-check-input" type="radio" name="{$v['name']}" id="{$v['id']}" autocomplete="new-type" value="{$k}" {$v['isChecked']}>
      <label class="form-check-label" for="{$v['id']}">{$v['translation']}</label>
    </div>
HTML;
  }

  return $optionHtml;
}

function combineInputHtml()
{
  $placeholderText = 'ex: '.$coldata['placeholderText'] ?? '';

  $html = <<<HTML
  <input type="text" class="form-control" id="note" placeholder="{$placeholderText}" autocomplete="new-password">
HTML;

  return $html;
}

function getRange($max, $min)
{
  if ($max == 0 && $min == 0) {
    $range[] = 0;
  } else {
    $range = range($min, $max);
  }

  foreach ($range as $v) {
    $result[$v] = $v.'%';
  }

  return $result;
}

function getRGLotteryOddsTableUrl($rgApiConfig, $rgApiData)
{
	$url = $rgApiConfig['api_url'] . $rgApiConfig['sub_url']['LotteryMasterOddsTable'];
	$key = genApiKey($rgApiConfig['apikey'], $rgApiData);
	$apiUrl = $url . 'Key=' . $key . '&masterId=' . $rgApiData['masterId'] . '&memberId=' .
		$rgApiData['memberId'] . '&gameId=' . $rgApiData['gameId'] . '&fs=' . $rgApiData['fs'];
	return $apiUrl;
}

function getRGGameId()
{
	$sql = 'SELECT "gameid" FROM "casino_gameslist" WHERE "casino_id" = \'RG\' ORDER BY "gameid" DESC LIMIT 1;';
	$result = runSQLall($sql);
	return $result[1]->gameid;
}


function getRGMasterId()
{
	global $config;
	$masterId = getGameAccountByMemberId($config['system_company_id'], 'RG');
	return is_null($masterId) ? genGameAccountByMemberId($config['system_company_id'], 20000000000, $config['projectid']) : $masterId;
}

// 取得會員狀態
$memberstatus = '0';
if(isset( $_SESSION['member'])){
	$memberstatus_sql = 'SELECT status FROM "root_member" WHERE id = ' . $_SESSION['member']->id . ';';
	$memberstatus_result = runSQLall($memberstatus_sql, 0, 'r');
	$memberstatus = ($memberstatus_result['0'] == 1) ? $memberstatus_result['1']->status : '0';
}

$validityPeriod = [
  '1' => $tr['a day'],//'一天',
  '2' => $tr['two days'],//'两天',
  '3' => $tr['three days'],//'三天',
  '10' => $tr['One month'],//'一个月',
  '20' => $tr['Two months'],//'两个月',
  '30' => $tr['Three months'],//'三个月',
  '1000' => $tr['Permanent']//'永久有效'
];

$memberType['M'] = [
  'id' => 'memberRadio',
  'name' => 'registerTypeOptions',
  'translation' => $tr['member'],//'会员'
  'isChecked' => 'checked'
];

$dividendPreferentialSettingHtml = '';
// if ($protalsetting['agency_registration_gcash'] == '0') {
  $memberType['A'] = [
    'id' => 'agentRadio',
    'name' => 'registerTypeOptions',
    'translation' => $tr['agent'],//'代理'
    'isChecked' => ''
  ];

  $validityPeriodHtml = combineSelectOptionHtml($validityPeriod);
  $registerTypeHtml = combineRadioOptionHtml($memberType);

  $feedbackinfoHelper = new FeedbackInfoHelper(['member_id' => $_SESSION['member']->id]);
  $feedbackinfoHelper->initFeedbackInfo();
  $feedbackinfoHelper->save();

  $preferentialUpperLowerLimit = $feedbackinfoHelper->getNewChildAllocationRange('preferential');
  $dividendUpperLowerLimit = $feedbackinfoHelper->getNewChildAllocationRange('dividend');

  $preferentialRange = getRange(float_to_percent($preferentialUpperLowerLimit['max']), float_to_percent($preferentialUpperLowerLimit['min']));
  $dividendRange = getRange(float_to_percent($dividendUpperLowerLimit['max']), float_to_percent($dividendUpperLowerLimit['min']));

  $preferentialOptionsHtml = combineSelectOptionHtml($preferentialRange);
  $dividendOptionsHtml = combineSelectOptionHtml($dividendRange);

	global $RGAPI_CONFIG;
	$RG_API_Data = array(
		'masterId' => getRGMasterId(),
		'memberId' => '',
		'gameId' => getRGGameId(),
		'fs' => $fs = $preferentialUpperLowerLimit['max']/10 == 0 ? 10 : $preferentialUpperLowerLimit['max']*10
	);
	$oddsTableUrl = getRGLotteryOddsTableUrl($RGAPI_CONFIG, $RG_API_Data);
	$oddsTableHtml = <<<HTML
      	<div class="form-group register_agenthelpbt row">
        <div class="col-12 my-1">
              <button type="button"
              id="rg_odds_table" class="d-none"><a href="{$oddsTableUrl}" target="_blank" rel="noopener
              noreferrer" class="text-danger">{$tr['Rebate table']}</a></button>
        </div>
        </div>
HTML;

$registration_gcash_alert=($protalsetting['agency_registration_gcash'] == '0')? '':<<<HTML
<div class="form-group row div_from2input">
  <div class="col-3">
    <div id="registration_gcash_check_div">
      <input type="checkbox" class="mr-1" id="registration_gcash_check">
      {$tr['I agreed']}
    </div>
  </div>
  <div class="col-9 px-3">
    <div class="alert alert-warning" role="alert">
      {$tr['An additional fee will be charged for establishing an agency']}: $ {$protalsetting['agency_registration_gcash']}
    </div>
  </div>
</div>
HTML;
if($config['site_style']=='mobile'){
  $dividendPreferentialSettingHtml = <<<HTML
  <div class="agentSettingArea layoutblock col-12">
    <div>
      <form>
        <!-- <div class="form-group row">
          <div class="col-3">
            <button type="button" class="btn btn-primary">賠率表</button>
          </div>
        </div>
        <br> -->
        {$oddsTableHtml}
        <div class="form-group row div_from2input">
          <div class="col-3">{$tr['bonus']}</div>
          <div class="col-9">
            <select class="form-control" id="preferential">
              {$preferentialOptionsHtml}
            </select>
          </div>
        </div>
        <div class="form-group row div_from2input">
          <div class="col-3">{$tr['commission']}</div>
          <div class="col-9">
            <select class="form-control" id="dividend">
              {$dividendOptionsHtml}
            </select>
          </div>
        </div>
        {$registration_gcash_alert}
      </form>
    </div>
  </div>
HTML;
}else{
  $dividendPreferentialSettingHtml = <<<HTML
  <div class="agentSettingArea layoutblock">
    <div>
      <form>
        <!-- <div class="form-group row">
          <div class="col-3">
            <button type="button" class="btn btn-primary">賠率表</button>
          </div>
        </div>
        <br> -->
        {$oddsTableHtml}
        <div class="form-group row div_from2input">
          <div class="col-2">{$tr['bonus']}</div>
          <div class="col-10">
            <select class="form-control" id="preferential">
              {$preferentialOptionsHtml}
            </select>
          </div>
        </div>
        <div class="form-group row div_from2input">
          <div class="col-2">{$tr['commission']}</div>
          <div class="col-10">
            <select class="form-control" id="dividend">
              {$dividendOptionsHtml}
            </select>
          </div>
        </div>
        {$registration_gcash_alert}
      </form>
    </div>
  </div>
HTML;
}

//}

if($config['site_style']=='mobile'){
$registerTypeHtml = <<<HTML
<div class="registerTypeArea col-12">
  <div class="layoutblock">
    <form>
      <div class="form-group row div_from2">
        <div class="col-3">{$tr['register type']}</div>
        <div class="col-9">
          {$registerTypeHtml}
        </div>
      </div>
    </form>
  </div>
</div>
HTML;

$linkSettingHtml = <<<HTML
<div class="linkSettingArea layoutblock col-12">
  <div>
    <form>
      <div class="form-group row div_from2input">
        <div class="col-3">{$tr['account']}</div>
        <div class="col-9 position-relative">
        <i class="fas fa-pencil-alt pen_icon"></i>
          <input type="text" class="form-control" id="account" placeholder="{$tr['account']}" autocomplete="new-account">
        </div>
      </div>
      <div class="form-group row div_from2input">
        <div class="col-3">{$tr['password']}</div>
        <div class="col-9">
          <div class="input-group">
            <input type="password" class="form-control" id="password" placeholder="{$tr['password']}" aria-label="password" aria-describedby="basic-addon2" autocomplete="new-password">
            <div class="input-group-append">
              <button class="btn btn-outline-secondary agenthelp_password" type="button" id="showPw"><span class="glyphicon glyphicon-eye-open" id="showPwIcon" aria-hidden="true"></span></button>
            </div>
          </div>
        </div>
      </div>
      <div class="form-group row div_from2input">
        <div class="col-3">{$tr['confirm password']}</div>
        <div class="col-9 position-relative">
          <i class="fas fa-pencil-alt pen_icon"></i>
          <input type="password" class="form-control" id="checkPassword" placeholder="{$tr['confirm password']}" autocomplete="new-confirm-password">
        </div>
      </div>
    </form>
  </div>
</div>
HTML;

$captchaHtml = <<<HTML
<div class="captchaArea layoutblock col-12">
  <div>
    <form>
      <div class="form-group row div_from2input">
        <div class="col-3">{$tr['Verification code']}</div>
        <div class="col-9">
          <div class="input-group-sm" style="position: relative; z-index: 1;">
            <input name="captcha" class="form-control" id="captcha" type="text" placeholder="{$tr['Verification code']}" autocomplete="new-verification-code">
            <span id="captchaShow" class= "show_captcha_css"><img src="{$cdnfullurl_js}img/common/hello.png" id="captchaImg" alt="Verification code" height="20" width="65" ></span>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
HTML;
}else{
$registerTypeHtml = <<<HTML
<div class="registerTypeArea">
  <div class="layoutblock">
    <form>
      <div class="form-group row div_from2">
        <div class="col-2">{$tr['register type']}</div>
        <div class="col-10">
          {$registerTypeHtml}
        </div>
      </div>
    </form>
  </div>
</div>
HTML;

$linkSettingHtml = <<<HTML
<div class="linkSettingArea layoutblock">
  <div>
    <form>
      <div class="form-group row div_from2input">
        <div class="col-2">{$tr['account']}</div>
        <div class="col-10 position-relative">
          <input type="text" class="form-control" id="account" placeholder="{$tr['account']}" autocomplete="new-account">
        </div>
      </div>
      <div class="form-group row div_from2input">
        <div class="col-2">{$tr['password']}</div>
        <div class="col-10">
          <div class="input-group">
            <input type="password" class="form-control" id="password" placeholder="{$tr['password']}" aria-label="password" aria-describedby="basic-addon2" autocomplete="new-password">
            <div class="input-group-append">
              <button class="btn btn-outline-secondary agenthelp_password" type="button" id="showPw"><span class="glyphicon glyphicon-eye-open" id="showPwIcon" aria-hidden="true"></span></button>
            </div>
          </div>
        </div>
      </div>
      <div class="form-group row div_from2input">
        <div class="col-2">{$tr['confirm password']}</div>
        <div class="col-10 position-relative">
          <input type="password" class="form-control" id="checkPassword" placeholder="{$tr['confirm password']}" autocomplete="new-confirm-password">
        </div>
      </div>
    </form>
  </div>
</div>
HTML;

$captchaHtml = <<<HTML
<div class="captchaArea layoutblock">
  <div>
    <form>
      <div class="form-group row div_from2input">
        <div class="col-2">{$tr['Verification code']}</div>
        <div class="col-10">
          <div class="input-group-sm" style="position: relative; z-index: 1;">
            <input name="captcha" class="form-control" id="captcha" type="text" placeholder="{$tr['Verification code']}" autocomplete="new-verification-code">
            <span id="captchaShow" class= "show_captcha_css"><img src="{$cdnfullurl_js}img/common/hello.png" id="captchaImg" alt="Verification code" height="20" width="65" ></span>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
HTML;
}

/*
$detect = new Mobile_Detect;

if ($detect->isMobile()) {
  $menuHtml = '';
} else {
  $menu_agentadmin_html = menu_agentadmin('register_agenthelp.php');

  $menuHtml = <<<HTML
  <div class="row">
    <div class="col-12">
      $menu_agentadmin_html
    </div>
  </div>
  <br><br>
HTML;
}
*/
if($config['site_style']=='desktop'){
  $back_btn = '<a class="btn btn-secondary back_prev" href="./member_management.php" role="button"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>'.$tr['back to list'].'</a>';
}else{
  $back_btn = '';
}

if($memberstatus == 2){
  $register_agenthelp = '<p class="no_available">'.$tr['Your wallet has been frozen, please contact customer'].'</p>';
}else{
  $register_agenthelp = <<<HTML
  <div id="register_agenthelp">
      {$registerTypeHtml}
      {$linkSettingHtml}
      {$captchaHtml}
      <button type="button" class="send_btn bg-primary" id="submitBtn">{$tr['submit']}</button>
  </div>
HTML;
}
$indexbody_content = <<<HTML
<div class="row">
  <div class="col-12">
        {$register_agenthelp}
        {$back_btn}
  </div>
</div>
HTML;

$extend_head = <<<HTML
<style>
#registration_gcash_check_div{
    border: 1px solid transparent;
    padding: 5px;
    border-radius: .25em;
}
</style>
<script>
var csrf = '{$csrftoken}';
var dividendPreferentialSettingHtml = `{$dividendPreferentialSettingHtml}`;
</script>

<script src="in/js/register_agenthelp.js?version_key={$config['cdn_version_key']}"></script>
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
$tmpl['sidebar_content'] =['agent','member_management'];
// banner標題
$tmpl['banner'] = ['membercenter_member_management'];
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
