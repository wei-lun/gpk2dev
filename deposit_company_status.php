<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 線上存款功能 -- A 公司入款 審查中的狀況
// File Name:	deposit_company_status.php
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
$function_title 		= $tr['membercenter_deposit_company_status'];
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

if (!isset($_SESSION['member']) || $_SESSION['member']->therole == 'R' || $_SESSION['member']->therole == 'T') {
  echo login2return_url(2);
  die($tr['permission error']);//'不合法的帐号权限'
};

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

// $deposit_review_status[0] = $tr['discard deposit application'];
// //存款申請審查通過
// $deposit_review_status[1] = $tr['deposit application approved'];
// //存款提交審查中
// $deposit_review_status[2] = $tr['deposit submitted for review'];
// //已刪除的存款申請
// $deposit_review_status[NULL] = $tr['deleted deposit request'];
$deposit_review_status = [
	// $tr['discard deposit application'],	// 放棄存款申請
	'存款申请审查退回',
	$tr['deposit application approved'],  // 存款申請審查通過
	$tr['deposit submitted for review'],  // 存款提交審查中
	$tr['deleted deposit request']  // 已刪除的存款申請
];

$no_deposit_result = $tr['no deposit result'];

// function get_tzonename($tz)
// {
//   // 轉換時區所要用的 sql timezone 參數
//   $tzsql = "select * from pg_timezone_names where name like '%posix/Etc/GMT%' AND abbrev = '".$tz."';";
//   $tzone = runSQLALL($tzsql);

//   if($tzone[0]==1) {
//     $tzonename = $tzone[1]->name;
//   } else {
//     $tzonename = 'posix/Etc/GMT-8';
//   }

//   return $tzonename;
// }

function get_deposit_review_data($acc, $tzonename = 'posix/Etc/GMT-8')
{
	global $no_deposit_result;

	$tzonename = 'posix/Etc/GMT-8';

	$sql = <<<SQL
	SELECT to_char((transfertime AT TIME ZONE '{$tzonename}'),'YYYY-MM-DD HH24:MI:SS') as transfertime,
		   to_char((changetime AT TIME ZONE 'posix/Etc/GMT+4'), 'YYYY-MM-DD HH24:MI:SS' ) as changetime,
        companyname,
        accountnumber,
        amount,
        reconciliation_notes,
        transaction_id,
        status
  FROM root_deposit_review
  WHERE status = '2'
  AND account = '{$acc}'
	ORDER BY changetime DESC;
SQL;

	$result = runSQLall($sql);

	if (empty($result[0])) {
		$error_msg = $no_deposit_result;
		return array('status' => false, 'result' => $error_msg);
	}

	unset($result[0]);
	return array('status' => true, 'result' => $result);
}

function deposit_finish_html($deposit_review_data)
{
	global $deposit_review_status;
	global $config;
	global $tr;

	$html = '';

	if($config['site_style']=='mobile'){
		foreach ($deposit_review_data as $k => $r) {
			$status = ($r->status === NULL) ? $deposit_review_status[4] : $deposit_review_status[$r->status];

			$html .= <<<HTML
			<div class="shadow-sm rounded withd_cash_list"> 
				<div class="d-flex bd-highlight withd_cash border-bottom">
					<div class="flex-fill bd-highlight text-left">{$tr['application time']}</div>
					<div class="flex-fill bd-highlight text-right">{$r->changetime}</div>
				</div>
				<div class="d-flex bd-highlight withd_cash">
					<div class="flex-fill bd-highlight text-left">{$tr['Transfer number']}</div>
					<div class="flex-fill bd-highlight text-right">{$r->transaction_id}</div>
				</div>
				<div class="d-flex bd-highlight withd_cash">
					<div class="flex-fill bd-highlight text-left">{$tr['Cash out bank']}</div>
					<div class="flex-fill bd-highlight text-right">{$r->companyname}</div>
				</div>
				<div class="d-flex bd-highlight withd_cash">
					<div class="flex-fill bd-highlight text-left">{$tr['Deposit bank account number']}</div>
					<div class="flex-fill bd-highlight text-right">{$r->accountnumber}</div>
				</div>
				<div class="d-flex bd-highlight withd_cash">
					<div class="flex-fill bd-highlight text-left">{$tr['deposit time']}</div>
					<div class="flex-fill bd-highlight text-right">{$r->transfertime}</div>
				</div>
				<div class="d-flex bd-highlight withd_cash">
					<div class="flex-fill bd-highlight text-left">{$tr['deposit amount']}</div>
					<div class="flex-fill bd-highlight text-right">{$r->amount}</div>
				</div>	
				<div class="d-flex bd-highlight withd_cash">
					<div class="flex-fill bd-highlight text-left">              
						<div class="deposit_com_information">
							{$tr['member remittance info shorten']}
						</div>
					</div>
					<div class="flex-fill bd-highlight text-right">{$r->reconciliation_notes}</div>
				</div>
				<div class="d-flex bd-highlight withd_cash">
					<div class="flex-fill bd-highlight text-left">{$tr['process status']}</div>
					<div class="flex-fill bd-highlight text-right text-success">{$status}</div>
				</div>	
			</div>
HTML;
		}
	} else {
		foreach ($deposit_review_data as $k => $r) {
			$status = ($r->status === NULL) ? $deposit_review_status[4] : $deposit_review_status[$r->status];

			$html .= <<<HTML
			<div class="row statusTable">
				<div class="col-3">{$r->changetime}</div>
				<div class="col-3">{$r->companyname}</div>
				<div class="col-2">{$r->amount}</div>
				<div class="col-3" class="col-3">{$status}</div>
				<div class="col-1">
					<button class="btn btn-primary collapsed" type="button" data-toggle="collapse" data-target="#collapse{$k}" aria-expanded="false" aria-controls="collapseExample"></button>
				</div>
				<div class="collapse col-12" id="collapse{$k}">
					<div class="deposit_company_card">
						<div><span>{$tr['Transfer number']}</span> <i>:</i> {$r->transaction_id}</div>
						<div class="mt-3"><span>{$tr['Cash out bank']}</span> <i>:</i> {$r->companyname}</div>
						<div class="mt-3"><span>{$tr['Deposit bank account number']}</span> <i>:</i> {$r->accountnumber}</div>
						<div class="mt-3"><span>{$tr['deposit time']}</span> <i>:</i> {$r->transfertime}</div>
						<div class="mt-3"><span>{$tr['deposit amount']}</span> <i>:</i> {$r->amount}</div>
						<div class="mt-3"><span>{$tr['member remittance info shorten']}</span> <i>:</i> {$r->reconciliation_notes}</div>
						<div class="mt-3"><span>{$tr['process status']}</span> <i>:</i> {$status}</div>
					</div>
				</div>
			</div>
HTML;
		}
	}

	return $html;
}

// 有會員資料 不論身份 therole 為何，都可以修改個人資料。但是除了 therole = T 除外。
if(isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T' ) {

	// 客服聯絡資訊
	$showtext_html = <<<HTML
	<p style="line-height: 175%;  letter-spacing: 1px; font-size: small;">
		客服资讯：<br>
	</p>
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
			$show_deposit_finish_row = '';

			//公司入款
			$deposit_method = $tr['deposit method company'];

			// $deposit_status_title = $tr['company deposit status runnig'];

			// $deposit_currency_html = get_deposit_currency_html();

			//收款銀行 收款銀行帳號 存入金額 存入時間 目前狀態
			$colname = <<<HTML
			<div class="row statusTable border-top-0">
				<div class="col-3">{$tr['deposit time']}</div>
				<div class="col-3">{$tr['Cash out bank']}</div>
				<div class="col-2">{$tr['deposit amount']}</div>
				<div class="col-3">{$tr['process status']}</div>
				<div class="col-1">more</div>
			</div>
HTML;

			// $tzname = get_tzonename($_SESSION['member']->timezone);
			$deposit_review_data = (object)get_deposit_review_data($_SESSION['member']->account);

			if (!$deposit_review_data->status) {
				$show_deposit_finish_html = <<<HTML
				<div class="alert alert-danger" role="alert">
					{$deposit_review_data->result}
				</div>
				<!-- <tr>
					<td colspan="5">{$deposit_review_data->result}</td>
				</tr> -->
HTML;
			} else {
				$show_deposit_finish_html = deposit_finish_html($deposit_review_data->result);
			}

			if($config['site_style']=='mobile') {
				$deposit_finish_html = <<<HTML
				<div class="header_description">
					<div class="row">
						<div class="col-8">
							<select class="form-control" id="review_status">
								<option value="review" selected>審核中</option>
								<option value="pass">已通過</option>
								<option value="reject">已退回</option>
							</select>
						</div>
						<div class="col">
							<button type="button" class="btn" data-container="body" data-toggle="popover"  data-placement="left" data-content="{$tr['deposit info']}">
								<i class="fa fa-info-circle" aria-hidden="true"></i>{$tr['description']}
							</button>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-12 deposit_status_content" id="deposit_review_data">
						{$show_deposit_finish_html}
					</div>
				</div>
HTML;
			} else {
				$deposit_finish_html = <<<HTML
				<div class="card deposit-card">
					<div class="de_companytitle">{$tr['deposit info']}</div>
					<div class="card-body overflow-auto" id="deposit_review_data">
						{$show_deposit_finish_html}
					</div>
				</div>
HTML;
			}

			$form_html = $deposit_finish_html;
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
// ---------- end session login check

if($config['site_style']=='desktop'){
	$back_btn = '<a class="btn btn-secondary back_prev" href="./deposit.php" role="button"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>'.$tr['back to list'].'</a>';
  }else{
	$back_btn = '';
  }

if($config['site_style']=='desktop') {
require_once dirname(__FILE__) . "/lib_wallets.php";
$casinoBalanceHtml = combineGetCasinoBalanceHtml('deposit');
	$form_html =<<<HTML
	<div class="row">
	<div>{$casinoBalanceHtml}</div>
	<div class="col">
		<select class="form-control" id="review_status">
			<option value="review" selected>審核中</option>
			<option value="pass">已通過</option>
			<option value="reject">已退回</option>
		</select>
		<br>
		{$form_html}
		{$back_btn}
	</div>
	</div>
HTML;
}

// 切成 3 欄版面
$indexbody_content = <<<HTML
<div class="row justify-content-md-center">
	<div class="col-12">
		{$form_html}
	</div>
</div>
<div id="preview_result"></div>
HTML;


$extend_js = <<<HTML
<script>
$(document).on('change', '#review_status', function() {	
  const csrftoken = '{$csrftoken}';
	const status = $(this).val();

	$.ajax({
		type: "POST",
		url: "deposit_company_status_action.php",
		data: {
			csrftoken: csrftoken,
			data: JSON.stringify({
				'status' : status
			}),
			action: 'statusData'
		},
	}).done(function(resp) {
		// $('#preview_result').html(resp);
		const res = JSON.parse(resp);
		let html = '';

		$('#deposit_review_data').empty();

		if (res.status === 'success') {
			if (res.site_style == 'mobile') {
				html = combineMobileReviewDataHtml(res.result);
			} else {
				html = combineDesktopReviewDataHtml(res.result);
			}			
		} else {
			html = `
			<div class="alert alert-danger" role="alert">
				`+res.result+`
			</div>
			`;
		}

		$('#deposit_review_data').append(html);
	}).fail(function(jqXHR, textStatus) {
		alert('Request failed: ' + textStatus);
	});
});


function combineMobileReviewDataHtml(data) {
	const html = data.reduce((accumulator, currentValue, currentIndex, array) => {
		console.log(currentValue)
		return accumulator + `
		<div class="shadow-sm rounded withd_cash_list"> 
			<div class="d-flex bd-highlight withd_cash border-bottom">
				<div class="flex-fill bd-highlight text-left">{$tr['application time']}</div>
				<div class="flex-fill bd-highlight text-right">`+currentValue.changetime+`</div>
			</div>
			<div class="d-flex bd-highlight withd_cash">
				<div class="flex-fill bd-highlight text-left">{$tr['Transfer number']}</div>
				<div class="flex-fill bd-highlight text-right">`+currentValue.transaction_id+`</div>
			</div>
			<div class="d-flex bd-highlight withd_cash">
				<div class="flex-fill bd-highlight text-left">{$tr['Cash out bank']}</div>
				<div class="flex-fill bd-highlight text-right">`+currentValue.companyname+`</div>
			</div>
			<div class="d-flex bd-highlight withd_cash">
				<div class="flex-fill bd-highlight text-left">{$tr['Deposit bank account number']}</div>
				<div class="flex-fill bd-highlight text-right">`+currentValue.accountnumber+`</div>
			</div>
			<div class="d-flex bd-highlight withd_cash">
				<div class="flex-fill bd-highlight text-left">{$tr['deposit time']}</div>
				<div class="flex-fill bd-highlight text-right">`+currentValue.transfertime+`</div>
			</div>
			<div class="d-flex bd-highlight withd_cash">
				<div class="flex-fill bd-highlight text-left">{$tr['deposit amount']}</div>
				<div class="flex-fill bd-highlight text-right">`+currentValue.amount+`</div>
			</div>
			<div class="d-flex bd-highlight withd_cash">
				<div class="flex-fill bd-highlight text-left">              
				<div class="deposit_com_information">
					{$tr['member remittance info shorten']}
				</div>
				</div>
				<div class="flex-fill bd-highlight text-right">`+currentValue.reconciliation_notes+`</div>
			</div>
			<div class="d-flex bd-highlight withd_cash">
				<div class="flex-fill bd-highlight text-left">{$tr['process status']}</div>
				<div class="flex-fill bd-highlight text-right text-success">`+currentValue.status+`</div>
			</div>	
		</div>
		`;
	}, '');

	return html;
}

function combineDesktopReviewDataHtml(data) {
	const html = data.reduce((accumulator, currentValue, currentIndex, array) => {
		console.log(currentValue)
		return accumulator + `
		<div class="row statusTable">
			<div class="col-3">`+currentValue.changetime+`</div>
			<div class="col-3">`+currentValue.companyname+`</div>
			<div class="col-2">`+currentValue.amount+`</div>
			<div class="col-3" class="col-3">`+currentValue.status+`</div>
			<div class="col-1">
				<button class="btn btn-primary collapsed" type="button" data-toggle="collapse" data-target="#collapse`+currentIndex+`" aria-expanded="false" aria-controls="collapseExample"></button>
			</div>
			<div class="collapse col-12" id="collapse`+currentIndex+`">
				<div class="deposit_company_card">
					<div>{$tr['Transfer number']} : `+currentValue.transaction_id+`</div>
					<div class="mt-3">{$tr['Cash out bank']} : `+currentValue.companyname+`</div>
					<div class="mt-3">{$tr['Deposit bank account number']} : `+currentValue.accountnumber+`</div>
					<div class="mt-3">{$tr['deposit time']} : `+currentValue.transfertime+`</div>
					<div class="mt-3">{$tr['deposit amount']} : `+currentValue.amount+`</div>
					<div class="mt-3">{$tr['member remittance info shorten']} : `+currentValue.reconciliation_notes+`</div>
					<div class="mt-3">{$tr['process status']} : `+currentValue.status+`</div>
				</div>
			</div>
		</div>
		`;
	}, '');

	return html;
}
</script>
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
