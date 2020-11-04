<?php
// ----------------------------------------------------------------------------
// Features:  前端 -- 新增推廣註冊
// File Name: spread_register_add.php
// Author:    Neil
// Related:   
// DB Table:  
// Log:
// ----------------------------------------------------------------------------

session_start();

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

require_once dirname(__FILE__)."/casino/RG/lobby_rggame_lib.php";

// ----------------------------------------------------------------------------
// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------

// 初始化變數
// 功能標題，放在標題列及meta
$function_title = $tr['membercenter_spread_register_add'];//'新增邀请码'
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
    <a href="{$config['website_baseurl']}spread_register.php"><i class="fas fa-chevron-left"></i></a>
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
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="{$v['name']}" id="{$v['id']}" value="{$k}" {$v['isChecked']}>
      <label class="form-check-label" for="{$v['id']}">{$v['translation']}</label>
    </div>
HTML;
  }

  return $optionHtml;
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

$validityPeriod = [
  '1' => $tr['a day'], 
  '2' => $tr['two days'], 
  '3' => $tr['three days'],
  '10' => $tr['One month'], 
  '20' => $tr['Two months'], 
  '30' => $tr['Three months'], 
  '1000' => $tr['Permanent']
];
//'一天''两天''三天''一个月''两个月''三个月''永久有效'
$memberType['M'] = [
  'id' => 'memberRadio',
  'name' => 'registerTypeOptions',
  'translation' => $tr['member'],//'会员'
  'isChecked' => 'checked'
];

$dividendPreferentialSettingHtml = '';
if ($protalsetting['agency_registration_gcash'] == '0') {
  // $memberType['M']['checked'] = '';
  $memberType['A'] = [
    'id' => 'agentRadio',
    'name' => 'registerTypeOptions',
    'translation' => $tr['agent'],//'代理'
    'isChecked' => ''
  ];

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
    <div class="form-group row">
      <div class="col-12 my-1">
          <button type="button" class="spread_add_btn d-none" 
            id="rg_odds_table"><a href="{$oddsTableUrl}" target="_blank" rel="noopener 
            noreferrer" class="text-danger">{$tr['Rebate table']}</a></button>
        </div>
     </div>
HTML;
//返点对照表
if($config['site_style']=='mobile'){
  $dividendPreferentialSettingHtml = <<<HTML
  <div class="agentSettingArea layoutblock">
    <div class="agentSettingAreaBody">
      <form>		 
      {$oddsTableHtml} 
        <div class="form-group row spread_add_style">
          <div class="col-4">{$tr['bonus']}</div>
          <div class="col-8">
            <select class="form-control" id="preferential">
              {$preferentialOptionsHtml}
            </select>
          </div>
        </div>
        <div class="form-group row spread_add_style">
          <div class="col-4">{$tr['commission']}</div>
          <div class="col-8">
            <select class="form-control" id="dividend">
              {$dividendOptionsHtml}
            </select>
          </div>
        </div>
      </form>
    </div>
  </div>
HTML;
}else{
  $dividendPreferentialSettingHtml = <<<HTML
  <div class="agentSettingArea layoutblock">
    <div class="agentSettingAreaBody">
      <form>		 
      {$oddsTableHtml} 
        <div class="form-group row spread_add_style">
          <div class="col-2">{$tr['bonus']}</div>
          <div class="col-10">
            <select class="form-control" id="preferential">
              {$preferentialOptionsHtml}
            </select>
          </div>
        </div>
        <div class="form-group row spread_add_style">
          <div class="col-2">{$tr['commission']}</div>
          <div class="col-10">
            <select class="form-control" id="dividend">
              {$dividendOptionsHtml}
            </select>
          </div>
        </div>
      </form>
    </div>
  </div>
HTML;
  //反水 佣金
}
  
}

$validityPeriodHtml = combineSelectOptionHtml($validityPeriod);
$registerTypeHtml = combineRadioOptionHtml($memberType);

$registerTypeAlert= ($protalsetting['agency_registration_gcash'] == '0')? '':<<<HTML
<button type="button" class="prompt_btn" data-container="body" data-toggle="popover" data-placement="right" data-content="{$tr['Agent Application Fee has been activated, so it is not supported to add agents through the invitation code']}">
  <i class="fa fa-info-circle" aria-hidden="true"></i>
</button>
HTML;

if($config['site_style']=='mobile'){
  $registerTypeHtml = <<<HTML
  <div class="col-12">
  <div class="registerTypeArea">
    <div class="layoutblock">
      <form>
        <div class="form-group row spread_add_style">
          <div class="col-4">{$tr['register type']}
            {$registerTypeAlert}
          </div>
          <div class="col-8">
            {$registerTypeHtml}
          </div>
        </div>
      </form>
    </div>
  </div>
  </div>
HTML;
  //开户类型
  $linkSettingHtml = <<<HTML
  <div class="col-12">
  <div class="linkSettingArea layoutblock">
    <div class="linkSettingAreaBody">
      <form>
        <div class="form-group row spread_add_style">
            <div class="col-4">{$tr['spread link expire day']}</div>
            <div class="col-8">
              <select class="form-control" id="validityPeriod">
                {$validityPeriodHtml}
              </select>
            </div>
        </div>
        <div class="form-group row spread_add_style">
            <div class="col-4">{$tr['remark']}</div>
            <div class="col-8">
              <i class="fas fa-pencil-alt pen_icon"></i>
              <input type="text" class="form-control" id="note" placeholder="{$tr['remark']}">
            </div>
        </div>
      </form>
    </div>
  </div>
  </div>
HTML;

}else{
  $registerTypeHtml = <<<HTML
  <div class="registerTypeArea">
    <div class="layoutblock">
      <form>
        <div class="form-group row spread_add_style">
          <div class="col-2">{$tr['register type']}
          {$registerTypeAlert}
          </div>
          <div class="col-10">
            {$registerTypeHtml}
          </div>
        </div>
      </form>
    </div>
  </div>
HTML;
  //开户类型
  $linkSettingHtml = <<<HTML
  <div class="linkSettingArea layoutblock">
    <div class="linkSettingAreaBody">
      <form>
        <div class="form-group row spread_add_style">
            <div class="col-2">{$tr['spread link expire day']}</div>
            <div class="col-10">
              <select class="form-control" id="validityPeriod">
                {$validityPeriodHtml}
              </select>
            </div>
        </div>
        <div class="form-group row spread_add_style">
            <div class="col-2">{$tr['remark']}</div>
            <div class="col-10">
              <input type="text" class="form-control" id="note" placeholder="{$tr['remark']}">
            </div>
        </div>
      </form>
    </div>
  </div>
HTML;
}

//有效期间 散播描述
/*
$detect = new Mobile_Detect;

if ($detect->isMobile()) {
  $menuHtml = '';
} else {
  $menu_agentadmin_html = menu_agentadmin('spread_register.php');

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
  $back_btn = '<a class="btn btn-secondary back_prev" href="./spread_register.php" role="button"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span> '.$tr['back to list'].'</a>';
}else{
  $back_btn = '';
}
$indexbody_content = <<<HTML
<div class="row">
  <div class="col-12">
    <div class="register_add">
        {$registerTypeHtml}
        {$linkSettingHtml}
        <div><button type="button" class="send_btn loadMore bg-primary" id="addLinkBtn">{$tr['Generate invitation code']}</button></div>
    </div>
      {$back_btn}
  </div>

  
</div>
<div id="result"></div>
HTML;
//生成邀请码
$extend_head = <<<HTML
<script>
$(function () {
		$('[data-toggle="popover"]').popover();
		$('.popover-dismiss').popover({
		  trigger: 'focus'
		});
	});	
var csrf = '{$csrftoken}';
var dividendPreferentialSettingHtml = `{$dividendPreferentialSettingHtml}`;
</script>
<style>
  /*temporarily*/
  #main .text-danger{
  width: auto;
}
</style>
<script src="in/js/spread_register_add.js?version_key=1090329"></script>
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
$tmpl['banner'] = ['membercenter_spread_register'];
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