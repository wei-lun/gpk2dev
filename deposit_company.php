<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 線上存款功能 -- A 公司入款
// File Name:	deposit_company.php
// Author:		Barkley
// Related: 	deposit.php
// Log:
// 只有登入的會員才可以看到這個功能。
// 2017.7.5 改寫
// ----------------------------------------------------------------------------
/*
DB Table :
root_protalsetting : 後台 - 會員端設定
root_member : 會員資料
root_member_grade : 後台 - 會員等級設定


File :
deposit.php - 線上存款功能 index 索引頁
deposit_company.php - 線上存款功能 -- A 公司入款
deposit_company.php - 線上存款功能 -- B 線上支付
*/



// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";


//var_dump($_SESSION);
//var_dump($_POST);

// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------


// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
$function_title 		= $tr['deposit_company title'];
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
  <li><a href="deposit.php">'.$tr['deposit title'].'</a></li>
  <li class="active">'.$function_title.'</li>
</ul>
';
if($config['site_style']=='mobile'){
  	$navigational_hierarchy_html =<<<HTML
		<a href="{$config['website_baseurl']}deposit.php"><i class="fas fa-chevron-left"></i></a>
		<span>{$function_title}</span>
		<i></i>
HTML;
}
if(isset($_POST['companynameid']) && $config['site_style']=='mobile'){
  	$navigational_hierarchy_html =<<<HTML
		<a href="{$config['website_baseurl']}deposit_company.php"><i class="fas fa-chevron-left"></i></a>
		<span>{$function_title}</span>
		<i></i>
HTML;
}
// ----------------------------------------------------------------------------

$deposit_review_status[0] = $tr['discard deposit application'];
//存款申請審查通過
$deposit_review_status[1] = $tr['deposit application approved'];
//存款提交審查中
$deposit_review_status[2] = $tr['deposit submitted for review'];
//已刪除的存款申請
$deposit_review_status[NULL] = $tr['deleted deposit request'];
$no_deposit_result = $tr['no deposit result'];

function get_deposit_currency_html()
{
	global $tr;
	global $protalsetting;

	$html = '';

	if ($protalsetting['member_deposit_currency_isshow'] == 'on') {
		$currency = ($protalsetting['member_deposit_currency'] == 'gtoken') ? $tr['GTOKEN'] : $tr['GCASH'];

		$html = <<<HTML
		<tr>
			<td>{$tr['deposit platform account']}</td>
			<td>{$currency}</td>
		</tr>
HTML;
	}

	return $html;
}

function get_banklist()
{
	$sql = <<<SQL
	SELECT *
	FROM root_deposit_company
	WHERE status = '1'
	-- AND type = 'bank'
	ORDER BY id
	LIMIT 100
SQL;

	$result = runSQLall($sql);

	if (empty($result[0])) {
		return false;
	}

	unset($result[0]);
	return $result;
}

function get_deposit_company_data($id)
{
	$sql = <<<SQL
		SELECT *
		FROM root_deposit_company
		WHERE status = '1'
		-- AND type = 'bank'
		AND id = '{$id}'
		ORDER BY id
		LIMIT 1
SQL;

	$result = runSQLall($sql);

	if (empty($result[0])) {
		return false;
	}

	unset($result[0]);
	return $result[1];
}

function gatPerTransactionLimitLowerUpper($perTransactionLimit)
{
	global $member_grade_config_detail;

	$gradeTransactionLower = $member_grade_config_detail->depositlimits_lower;
	$gradeTransactionUpper = $member_grade_config_detail->depositlimits_upper;

	$result['lower'] = ($gradeTransactionLower == 0) ? 1 : $gradeTransactionLower;
	// $result['upper'] = ($perTransactionLimit > $gradeTransactionUpper) ? $gradeTransactionUpper: $perTransactionLimit;
	$result['upper'] = ($gradeTransactionUpper == 0) ? 1000 : $gradeTransactionUpper;

	return $result;
}

function get_bankdata_html($bank_data, $grade)
{
	global $tr;
	global $config;

	$html = '';

	foreach ($bank_data as $k => $v) {
		$bank_grade = (array) json_decode($v->grade);
		if (!in_array($grade, $bank_grade)) {
			continue;
		}

		$companyurl_html = ($v->companyurl != '') ? '<a href="'.$v->companyurl.'" title="'.$tr['click for bank web'].'" target="_BLANK"><span class="glyphicon glyphicon-link" aria-hidden="true"></span></a>' : '';

if($config['site_style']=='mobile'){
		$html .= <<<HTML
		<li class="col-12">
			<div class="w-100">
				<div class="row deposit_company_icon">
				<div class="col-2">
					<span><i class="fa fa-credit-card"></i></span>
				</div>
				<div class="col-8 d-flex align-items-center">
					<label for="company{$v->id}">{$v->companyname}</label>{$companyurl_html}
				</div>
				<div class="col-2 deposit_company_input flex-row-reverse">
					<input type="radio" name="companynameid" id="company{$v->id}" value="{$v->id}">
					<label for="company{$v->id}"></label>
				</div>
			</div>
			</div>
		</li>
HTML;
}else{
		$html .= <<<HTML
		<tr>
			<td>
				<label><input type="radio" name="companynameid" value="{$v->id}">&nbsp;{$v->companyname}</label>
				{$companyurl_html}
			</td>
		</tr>
HTML;
}
	}

	// 指定跳到 step 2
	$html .= <<<HTML
	<input type="hidden" name="goto_step" value="2">
HTML;

	return $html;
}

function get_bankdata_detail_html($bankdata)
{
	global $tr;
	global $config;
//step2
if($config['site_style']=='mobile'){
		$html = <<<HTML
		<div class="row deposit_company_step_bg border-bottom">
			<div class="col deposit_company_text">
				{$tr['deposit bank account']}
			</div>
		</div>

		<div class="row deposit_company_step_text">
			<div class="col-12 d-flex mb-10">
				<div class="deposit_company_title">
					{$tr['bank_collect_money']}
				</div>
				<div class="col">
					{$bankdata->companyname}
				</div>
			</div>

			<div class="col-12 d-flex mb-10">
				<div class="deposit_company_title">
					{$tr['payee']}
				</div>
				<div class="col">
					{$bankdata->accountname}
				</div>
			</div>

			<div class="col-12 d-flex mb-10">
				<div class="deposit_company_title">
					{$tr['bank account number']}
				</div>
				<div class="col">
					{$bankdata->accountnumber}
				</div>
			</div>

			<div class="col-12 d-flex">
				<div class="deposit_company_title">
					{$tr['open_account_bank']}
				</div>
				<div class="col">
					{$bankdata->accountarea}
				</div>
			</div>
			</div>
HTML;
}else{
	$html = <<<HTML
	<table class="table transfer_details">
		<thead>
			<tr>
				<th colspan="2">{$tr['deposit bank account']}</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>{$tr['bank']}<span>:</span><strong>{$bankdata->companyname}</td>
			</tr>
			<tr>
				<td>{$tr['payee']}<span>:</span><strong>{$bankdata->accountname}</td>
			</tr>
			<tr>
				<td>{$tr['bank account number']}<span>:</span><strong>{$bankdata->accountnumber}</td>
			</tr>
			<tr>
				<td>{$tr['open account bank']}<span>:</span><strong>{$bankdata->accountarea}</td>
			</tr>
		</tbody>
	</table>
HTML;
}

	return $html;
}

function get_wechat_detail_html($bankdata)
{
	global $tr;
	global $config;
	//wechat_test
if($config['site_style']=='mobile'){
	$html = <<<HTML
	<div class="row deposit_company_step_bg pt-10 wechat_fontsize">
		<div class="col-12">
			<div id="prompt" class="alert alert-danger deposit_company_prompt" role="alert">
			{$tr['deposit wechat hint']}
			</div>
		</div>

		<div class="col-12 mb-10 border-bottom pb-10">
			<div class="row">
				<div class="deposit_company_title text-truncate">
					{$tr['deposit wechat account']}
				</div>
				<div class="col">
					{$bankdata->accountname}
				</div>
			</div>
		</div>

		<div class="col-12 mb-10">
			<div class="row">
			<div class="deposit_company_title text-truncate">
				{$tr['deposit wechat code']}
			</div>
			<div class="col">
				<img id="wechat_qrcode" src="{$bankdata->accountnumber}" height="200">
			</div>
			</div>
		</div>
	</div>
HTML;
}else{
	$html = <<<HTML
<div id="prompt " class="alert alert-danger" role="alert">
{$tr['deposit wechat hint']}
</div>
	<table class="table">
		<thead>
			<tr>
				<th colspan="2">
					{$tr['deposit bank account']}
				</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>{$tr['deposit wechat account']}&nbsp;:&nbsp;<strong>{$bankdata->accountname}</td>
			</tr>
			<tr>
				<td>{$tr['deposit wechat code']}&nbsp;:&nbsp;
					<img id="wechat_qrcode" src="{$bankdata->accountnumber}" height="200">
				</td>
			</tr>
		</tbody>
	</table>
HTML;
}

	return $html;
}

function get_virtualmoney_detail_html($bankdata)
{
	global $tr;
	global $config;
	if($config['site_style']=='mobile'){
	$html = <<<HTML
	<div class="row deposit_company_step_bg pt-10 wechat_fontsize">
		<div class="col-12">
			<div id="prompt" class="alert alert-danger deposit_company_prompt" role="alert">
				{$tr['deposit virtualmoney hint']}
			</div>
		</div>
		<div class="col-12 mb-10 border-bottom pb-10">
			<div class="row">
				<div class="deposit_company_title text-truncate">
					{$tr['deposit virtualmoney rate']}
				<span class="glyphicon glyphicon-info-sign" title="{$tr['deposit virtualmoney currency']}"></span>
				</div>
				<div class="col">
					{$bankdata->cryptocurrency} 1 : {$bankdata->exchangerate} {$config['currency_sign']}
				</div>
			</div>
		</div>

		<div class="col-12 mb-10">
				<div class="row">
					<div class="deposit_company_title text-truncate">
						{$tr['deposit virtualmoney code']}
					</div>
					<div class="col">
						<img id="virtualmoney_qrcode" src="{$bankdata->accountnumber}" height="200">
					</div>
				</div>
		</div>
	</div>
HTML;
	}else{
	//5
	$html = <<<HTML
<div id="prompt" class="alert alert-danger" role="alert">
{$tr['deposit virtualmoney hint']}
</div>
	<table class="table">
		<thead>
			<tr>
				<th colspan="2">
					{$tr['deposit bank account']}
				</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>{$tr['deposit virtualmoney rate']}
				<span class="glyphicon glyphicon-info-sign" title="{$tr['deposit virtualmoney currency']}"></span>
				&nbsp;:&nbsp;<strong>{$bankdata->cryptocurrency} 1 : {$bankdata->exchangerate} {$config['currency_sign']}
				</td>
			</tr>
			<tr>
				<td>{$tr['deposit virtualmoney code']}&nbsp;:&nbsp;
					<img id="virtualmoney_qrcode" src="{$bankdata->accountnumber}" height="200">
				</td>
			</tr>
		</tbody>
	</table>
HTML;
}

	return $html;
}

function get_virtualmoney_transfer_data_table_htm($perTransactionLimit, $bankdata)
{
	global $tr;
	global $config;
	//5
	$deposit_currency_html = get_deposit_currency_html();
	if($config['site_style']=='mobile'){
	$html = <<<HTML
	<div class="row deposit_company_step_bg border-top">
		<div class="col-12 deposit_company_step_list pt-10">
			<div class="deposit_company_title_2">
				<span></span>{$tr['deposit limit']}
			</div>
			<div class="col">
				<div class="description_deposit">
					$ {$perTransactionLimit['lower']} ~ $ {$perTransactionLimit['upper']}
				</div>
			</div>
		</div>

			<div class="col-12 deposit_company_step_list">
				<div class="deposit_company_title_2">
					<span>*</span>{$tr['deposit amount']}
				</div>
				<div class="col">
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text" id="inputGroup-sizing-default">{$bankdata->cryptocurrency}</span>
						</div>
						<input type="text" class="form-control" id="cryptocurrency" aria-label="cryptocurrency" aria-describedby="inputGroup-sizing-default" value="0">
						<div class="input-group-prepend">
						<span class="input-group-text" id="inputGroup-sizing-default"><span class="glyphicon glyphicon-arrow-right"></span></span>
						</div>
						<input type="text" class="form-control" id="amount" aria-label="{$config['currency_sign']}" aria-describedby="inputGroup-sizing-default" value="0" disabled>
						<div class="input-group-prepend">
							<span class="input-group-text" id="inputGroup-sizing-default">{$config['currency_sign']}</span>
						</div>
					</div>
				</div>
			</div>

			<div class="col-12 deposit_company_step_list">
				<div class="deposit_company_title_2">
					<span>*</span>{$tr['deposit time']}
				</div>
				<div class="col">
					<input id="datetimes" type="text" name="transfertime" value="" class="form-control" placeholder="{$tr['choice deposit time']} ex: 2016-10.20 12:00:00" />
				</div>
			</div>

			<div class="col-12 deposit_company_step_list">
				<div class="deposit_company_title_2">
					<span>*</span>{$tr['deposit name']}
				</div>
				<div class="col">
					<input type="text" id="depositoraccountname" name="depositoraccountname" value="" class="form-control" placeholder="ex: {$tr['name example']}" >
				</div>
			</div>

			<div class="col-12 deposit_company_step_list">
				<div class="deposit_company_title_2">
					<span>*</span>{$tr['member remittance info shorten']}
				</div>
				<div class="col">
					<input type="text" id="reconciliation_notes" name="reconciliation_notes" value="" class="form-control" placeholder="{$tr['member remittance tips']}" >
				</div>
			</div>
	</div>
HTML;
	}else{
	$html = <<<HTML
	<table class="table">
		<thead>
			<tr>
				<th colspan="3">{$tr['fill in transfer info']}</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>{$tr['deposit limit']}</td>
				<td>$ {$perTransactionLimit['lower']} ~ $ {$perTransactionLimit['upper']}</td>
			</tr>
			{$deposit_currency_html}
			<tr>
				<td><span class="text-danger">*</span>{$tr['deposit amount']}</td>
				<td>
					<div class="input-group mb-3">
						<div class="input-group-prepend">
							<span class="input-group-text" id="inputGroup-sizing-default">{$bankdata->cryptocurrency}</span>
						</div>
						<input type="text" class="form-control" id="cryptocurrency" aria-label="cryptocurrency" aria-describedby="inputGroup-sizing-default" value="0">
						<div class="input-group-prepend">
						<span class="input-group-text" id="inputGroup-sizing-default"><span class="glyphicon glyphicon-arrow-right"></span></span>
						</div>
						<input type="text" class="form-control" id="amount" aria-label="{$config['currency_sign']}" aria-describedby="inputGroup-sizing-default" value="0" disabled>
						<div class="input-group-prepend">
							<span class="input-group-text" id="inputGroup-sizing-default">{$config['currency_sign']}</span>
						</div>
					</div>
				</td>
			</tr>
			<tr>
				<td><span class="text-danger">*</span>{$tr['deposit time']}</td>
				<td><input id="datetimes" type="text" name="transfertime" value="" class="form-control" placeholder="{$tr['choice deposit time']} ex: 2016-10.20 12:00:00" /></td>
			</tr>
			<tr>
				<td><span class="text-danger">*</span>{$tr['deposit name']}</td>
				<td><input type="text" id="depositoraccountname" name="depositoraccountname" value="" class="form-control" placeholder="ex: {$tr['name example']}" ></td>
			</tr>
			<tr>
				<td><span class="text-danger">*</span>{$tr['member remittance info']}</td>
				<td><input type="text" id="reconciliation_notes" name="reconciliation_notes" value="" class="form-control" placeholder="{$tr['member remittance tips']}" ></td>
				<td><input class="form-control" type="hidden" id="type" value="{$bankdata->type}"></td>
			</tr>
		</tbody>
	</table>
HTML;
}
	return $html;
}

function get_transfer_data_table_htm($perTransactionLimit, $bankdata)
{
	global $tr;
	global $config;

	$deposit_currency_html = get_deposit_currency_html();
	//1
if($config['site_style']=='mobile'){
	$html = <<<HTML
<div class="row deposit_company_step_bg border-top">

	<div class="col-12 deposit_company_step_list pt-10">
		<div class="deposit_company_title_2"><span></span>{$tr['deposit limit']}</div>
		<div class="col">
		<div clas="description_deposit">
			$ {$perTransactionLimit['lower']} ~ $ {$perTransactionLimit['upper']}
		</div>
		</div>
	</div>

	<div class="col-12 deposit_company_step_list">
		<div class="deposit_company_title_2"><span>*</span>{$tr['deposit amount']}</div>
		<div class="col"><input type="number" step="0.01" min="0" id="amount" name="amount" value="" class="form-control" placeholder="ex: $1,000 "></div>
	</div>

	<div class="col-12 deposit_company_step_list">
		<div class="deposit_company_title_2"><span>*</span>{$tr['deposit time']}</div>
		<div class="col"><input id="datetimes" type="text" name="transfertime" value="" class="form-control" placeholder="{$tr['choice deposit time']} ex: 2016-10.20 12:00:00"  autocomplete="off"/></div>
	</div>

	<div class="col-12 deposit_company_step_list">
		<div class="deposit_company_title_2"><span>*</span>{$tr['deposit name']}</div>
		<div class="col"><input type="text" id="depositoraccountname" name="depositoraccountname" value="" class="form-control" placeholder="ex: {$tr['name example']}" ></div>
	</div>

	<div class="col-12 deposit_company_step_list">
		<div class="deposit_company_title_2"><span>*</span>{$tr['member remittance info shorten']}</div>
		<div class="col"><input type="text" id="reconciliation_notes" name="reconciliation_notes" value="" class="form-control" placeholder="{$tr['member remittance tips']}" >
				<input class="form-control" type="hidden" id="type" value="{$bankdata->type}"></div>
	</div>
</div>
HTML;
}else{
$html = <<<HTML
	<table class="table de_company_atm">
		<thead>
			<tr>
				<th colspan="3">{$tr['fill in transfer info']}</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><span></span>{$tr['deposit limit']}</td>
				<td colspan="2">{$config['currency_signer']} {$perTransactionLimit['lower']} ~ {$config['currency_signer']} {$perTransactionLimit['upper']}</td>
			</tr>
			{$deposit_currency_html}
			<tr>
				<td><span class="text-danger">*</span>{$tr['deposit amount']}</td>
				<td colspan="2"><input type="number" step="0.01" min="0" id="amount" name="amount" value="" class="form-control" placeholder="ex: $1,000 "></td>
			</tr>
			<tr>
				<td><span class="text-danger">*</span>{$tr['deposit time']}</td>
				<td colspan="2"><input id="datetimes" type="text" name="transfertime" value="" class="form-control" placeholder="{$tr['choice deposit time']} ex: 2016-10.20 12:00:00"  autocomplete="off"/></td>
			</tr>
			<tr>
				<td><span class="text-danger">*</span>{$tr['deposit name']}</td>
				<td colspan="2"><input type="text" id="depositoraccountname" name="depositoraccountname" value="" class="form-control" placeholder="ex: {$tr['name example']}" ></td>
			</tr>
			<tr>
				<td><span class="text-danger">*</span>{$tr['member remittance info']}</td>
				<td colspan="2"><input type="text" id="reconciliation_notes" name="reconciliation_notes" value="" class="form-control" placeholder="{$tr['member remittance tips']}" >
				<input class="form-control" type="hidden" id="type" value="{$bankdata->type}">
				</td>
			</tr>
		</tbody>
	</table>
HTML;
}
	return $html;
}


// 有會員資料 不論身份 therole 為何，都可以修改個人資料。但是除了 therole = T 除外。
if(isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T' ) {

	// 客服聯絡資訊
	$showtext_html = <<<HTML
	<p style="line-height: 175%;  letter-spacing: 1px; font-size: small;">
	客服资讯：<br></p>
	<p>(1)點擊<a href="http://{$customer_service_cofnig['online_weblink']}">在线客服</a>连结，即可进入在线客服系统与客服人员联系。 </p>
	<p>(2)您亦可使用下列联络方式与客服人员取得联系：<br></p>
	<p class="bg-info">
	<br>
	&nbsp;&nbsp;&nbsp;&nbsp;客服人员 Email：{$customer_service_cofnig['email']}<br>
	&nbsp;&nbsp;&nbsp;&nbsp;客服人员 QQ：{$customer_service_cofnig['qq']}<br>
	&nbsp;&nbsp;&nbsp;&nbsp;客服人员 Mobile TEL：{$customer_service_cofnig['mobile_tel']}<br>
	<br>
	</p>
	<br><hr>
HTML;

	$companydeposit_offline_desc_html = <<<HTML
	<p class="bg-danger">
	<br>
	&nbsp;&nbsp;&nbsp;&nbsp;线上取款 - 公司存款功能目前关闭或维护中，如有任何疑问，可透过下列任一方式与客服人员联系。<br>
	<br>
	</p>
HTML;

	$warning_message_html = $companydeposit_offline_desc_html . '<hr>' . $showtext_html;


	// 根據會員端設定開關公司入款
	// 這參數影響整個網站, 無論會員等級中公司入款功能設定開啟或關閉
	// 只要這裡設定關閉, 不分會員等級, 公司入款功能一律關閉
	$form_html = '';

	if ($protalsetting['companydeposit_switch'] == 'on') {
		// 根據會員等級設定開關公司入款
		if ($member_grade_config_detail->deposit_allow == 1) {

				//公司入款
				$deposit_method = $tr['deposit method company'];

				// STEP 1
				$bank_data_html = '';

				$bank_data = get_banklist();

				if (!$bank_data) {
					$bank_data_html = $tr['no bank information'];
				} else {
					$bank_data_html = get_bankdata_html($bank_data, $_SESSION['member']->grade);
				}

				//提醒您：同银行转账才能立即到账，所以建议选择同银行的帐户进行入款动作。
				$desposit_desc = $tr['company deposit notice'];
				//下一步
				$goto_step2 = '<button type="submit" class="send_btn de_com_bt" id="select_companynameid" form="step1_bank_select">'.$tr['next step'].'</button>';
				$goto_step2 = <<<HTML
				<button type="submit" class="send_btn de_com_bt" id="select_companynameid" form="step1_bank_select">{$tr['next step']}</button>
HTML;
	if($config['site_style']=='mobile'){
				$step1_save_btn_html = <<<HTML
				<div class="row">
				<div class="col-12 deposit_company_send">
				{$goto_step2}
				</div>
				<div class="col-12">
				<div class="deposit_company_ps">
					{$desposit_desc}
				</div>
				</div>
				</div>
HTML;
}else{
				$step1_save_btn_html = <<<HTML
				<div class="card-footer deposit_color">
				{$desposit_desc}
				{$goto_step2}
				</div>
HTML;
}

if($config['site_style']=='mobile'){
	$step1_html = <<<HTML
	<div class="row d-flex align-items-center">
		<div class="col">
			<div class="deposit_company_step1">{$tr['deposit_company step 1']}</div>
		</div>
	</div>
	<form id="step1_bank_select" method="post">
		<div class="row deposit_company_bg">
			<div class="col">
				<ul class="row">
					{$bank_data_html}
				</ul>
			</div>
		</div>
	</form>
	{$step1_save_btn_html}
HTML;
}else{
				$step1_html = <<<HTML
				<form id="step1_bank_select" method="post">
					<div class="deposit-card company">
						<div class="de_companytitle">{$tr['deposit_company step 1']}</div>
						<div class="card-body">
						<table class="table">
							<tbody>
								{$bank_data_html}
							</tbody>
						</table>
						</div>
						{$step1_save_btn_html}
					</div>
				</form>
HTML;
}

				$form_html = $step1_html;


				// STEP 2
				if(isset($_POST['companynameid'])) {
					$id  = filter_var($_POST['companynameid'], FILTER_VALIDATE_INT);

					$deposit_company_data = get_deposit_company_data($id);

					if($deposit_company_data) {

						// $deposit_currency_html = get_deposit_currency_html();

						switch ($deposit_company_data->type) {
							case 'bank':
								$received_bank_table_html = get_bankdata_detail_html($deposit_company_data);
								break;
							case 'wechat':
								$received_bank_table_html = get_wechat_detail_html($deposit_company_data);
								break;
							case 'virtualmoney':
								$received_bank_table_html = get_virtualmoney_detail_html($deposit_company_data);
								break;
						}

						$companyTransactionLimit = json_decode($deposit_company_data->transaction_limit);
						$perTransactionLimit = gatPerTransactionLimitLowerUpper($companyTransactionLimit->perTransactionLimit);

						//存款限额 存入金額 存入時間 請確實選擇入款時間 存款人姓名 存款方式 會員匯款帳號對帳資訊  請補充上述資料不足，以利客服人員對帳。如銀行帳號末5碼或其他可供辨識資訊。
						// $transfer_data_table_html = get_transfer_data_table_htm($perTransactionLimit);
						$transfer_data_table_html = ($deposit_company_data->type == 'virtualmoney') ? get_virtualmoney_transfer_data_table_htm($perTransactionLimit, $deposit_company_data) : get_transfer_data_table_htm($perTransactionLimit, $deposit_company_data);
						//提交送出
						if($config['site_style']=='mobile'){
						$step2_save_btn_html = <<<HTML
						<div class="row">
							<div class="col deposit_company_send">
								<a href="{$_SERVER['PHP_SELF']}" class="btn w-100">{$tr['pervious step']}</a>
							</div>
							<div class="col">
								<button class="btn btn-primary w-100 deposit_company_send" id="send_desposit_info" form="step3_transfer_info">{$tr['submit']}</button>
							</div>
						</div>
						<div class="row mt-10 mb-10">
							<div class="col deposit_company_ps">
								{$tr['deposit company hint']}
							</div>
						</div>
HTML;
}else{
						$step2_save_btn_html = <<<HTML
						<div class="de_com_footer deposit_color">
							<span>{$tr['deposit company hint']}</span>
							<ul class="nav nav-pills nav-fill">
							<li class="nav-item"><a href="{$_SERVER['PHP_SELF']}" class="send_btn">{$tr['pervious step']}</a></li>
							<li class="nav-item"><button class="send_btn btn-primary" id="send_desposit_info" form="step3_transfer_info">{$tr['submit']}</button></li>
							</ul>
						</div>
HTML;
}

						$transfer_info_html = <<<HTML
						<p><input type="hidden" id="companynameid" name="companynameid" value="{$id}"></p>
						<p><input type="hidden" name="goto_step" value="4"></p>
HTML;
	//STEP 2
if($config['site_style']=='mobile'){
	$transfer_info_html .= <<<HTML
	<div class="row d-flex align-items-center">
		<div class="col deposit_company_step3 pt-0">
			{$tr['deposit_company step 3']}
		</div>
	</div>
	{$received_bank_table_html}
	{$transfer_data_table_html}
	{$step2_save_btn_html}
HTML;
}else{
						$transfer_info_html .= <<<HTML
						<div class="card deposit-card">
						<div class="de_companytitle">{$tr['deposit_company step 3']}</div>
						<div class="deposit_company_body">
							{$received_bank_table_html}
							<br>
							{$transfer_data_table_html}
							<br>
						</div>
						{$step2_save_btn_html}
						</div>
HTML;
}
// 美東時間
$est_time = gmdate("Y/m/d H:i:s",time() + -4*3600);

						// 取得日期的 jquery datetime picker -- for 上面的生日格式
						$extend_js = <<<JS
						<script>
							$(document).ready(function(){
								// datetime picker
								$('#datetimes').datetimepicker({
									yearOffset:0,
									lang:'ch',
									timepicker:false,
									format:'Y/m/d H:i:s',
									defaultDate: '$est_time',
									maxDate: '$est_time'
								})
							});

							// for send_desposit_info
							$('#send_desposit_info').click(function(){
								var csrftoken = '$csrftoken';

								var defaultData = {
									'amount' : jQuery.trim($('#amount').val()),
									'datetimes' : jQuery.trim($('#datetimes').val()),
									'depositoraccountname' : jQuery.trim($('#depositoraccountname').val()),
									'reconciliation_notes' : jQuery.trim($('#reconciliation_notes').val()),
									'companynameid' : jQuery.trim($('#companynameid').val()),
									'type' : $('#type').val()
								};

								var cryptocurrencyData = {
									'current_exchangerate' : '{$deposit_company_data->exchangerate}',
									'cryptocurrency_amount' : $('#cryptocurrency').val()
								};

								var data = ($('#type').val() == 'virtualmoney') ? Object.assign(defaultData, cryptocurrencyData) : defaultData;

								if( amount == '' || datetimes == '' || depositoraccountname == ''){
									alert('{$tr['please fill all * field']}');
								}else{
									var r = confirm('{$tr['submit deposit confirm']}');
									if (r == true) {
										// $('#send_desposit_info').attr('disabled', 'disabled');
										$.post('deposit_company_action.php?a=member_editpersondata',
											{
												data: JSON.stringify(data),
												csrftoken: csrftoken
											},
											function(result){
												$('#preview_area').html(result);}
										);

									}else{
										// alert('cancel!!');
									}
								}
							});

							$('#cryptocurrency').keyup(function() {
								var cryptocurrency = $('#cryptocurrency').val();
								var exchangerate = {$deposit_company_data->exchangerate};

								$('#amount').val(cryptocurrency * exchangerate);
							});

						</script>
JS;

						// 讓這個表單可以被下個 step 使用，使用完後就 unset
						$_SESSION['postuse'] = '1';

						// 小排版
						$step2_html = <<<HTML
						{$transfer_info_html}
						<br>
						<div id="preview_area"></div>
HTML;

						$form_html = $step2_html;
					} else {
						//銀行資料已經失效。
						// $logger = $tr['bank information expired'];
						$logger = '存款方式已失效';
						echo $logger;
						// reload page

					}

				}

		} else {
			$form_html = $warning_message_html;
		}
	} else {
		$form_html = $warning_message_html;
	}

// ----------
} else {
// ----------
	// 不合法登入者的顯示訊息
  //(x) 請先登入會員，才可以使用此功能。
	$form_html = $tr['login first'];
}

if($config['site_style']=='desktop'){
	$back_btn = '<a class="btn btn-secondary back_prev" href="./deposit.php" role="button"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>'.$tr['back to list'].'</a>';
  }else{
	$back_btn = '';
  }
// ---------- end session login check

// 切成 3 欄版面
$indexbody_content = <<<HTML
<div id="deposit_company">
	{$form_html}
	{$back_btn}
</div>
HTML;


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
$tmpl['sidebar_content'] =['deposit','deposit'];
// banner標題
$tmpl['banner'] = ['membercenter_deposit_company'];
// menu增加active
$tmpl['menu_active'] =['deposit.php'];

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include($config['template_path']."template/admin.tmpl.php");

?>
