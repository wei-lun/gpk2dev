
<?php
// ----------------------------------------------------------------------------
// Features:	會員填完個人資料後，就可以提出申請成為代理。
// File Name:	member.php
// Author:		Barkley
// Related:
// Log:
// ----------------------
// 1. 個人資料維護
// 2. 修改登入密碼、取款密碼
// ----------------------

// ----------------------------------------------------------------------------



// 主機及資料庫設定
require_once dirname(__FILE__) . "/config.php";
// 支援多國語系
require_once dirname(__FILE__) . "/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) . "/lib.php";

// var_dump($_SESSION);
//var_dump(session_id());

// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
$function_title = $tr['membercenter_member_banksetting'];
// 擴充 head 內的 css or js
$extend_head = '';
// 放在結尾的 js
$extend_js = '';
// body 內的主要內容
$indexbody_content = '';
// 系統訊息選單
$messages = '';
// 初始化變數 end
// ----------------------------------------------------------------------------
// 導覽列
$navigational_hierarchy_html = '
<ul class="breadcrumb">
  <li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
  <li><a href="member.php">' . $tr['Member Centre'] . '</a></li>
  <li class="active">' . $function_title . '</li>
</ul>
';
if($config['site_style']=='mobile'){
  $navigational_hierarchy_html =<<<HTML
	<a href="{$config['website_baseurl']}menu_admin.php?gid=safe"><i class="fas fa-chevron-left"></i></a>
	<span>$function_title</span>
	<i></i>
HTML;
}
// ----------------------------------------------------------------------------


/**
 * Timezones list with GMT offset
 *
 * @return array
 * @link http://stackoverflow.com/a/9328760
 */

function get_bankaccountdata_persondata_html($member_data, $input_id, $placeholder_text, $col_name) {
	global $config;
	$data['is_null'] = '';
	if ($member_data != '') {
		$td_html = $member_data;
		if($config['site_style']=='mobile'){
			$data['html'] = '
			<tr class="row">
				<th>' . $col_name . '</th>
				<td>' . $td_html . '</td>
			</tr>';	
		}else{
			$data['html'] = '
			<tr class="row">
				<th class="col-2">' . $col_name . '</th>
				<td class="col">' . $td_html . '</td>
			</tr>';	
		}
			
	} else {
		$data['is_null'] = true;
		if($config['site_style']=='mobile'){
			$td_html = '
			<i class="fas fa-pencil-alt pen_icon"></i>
			<input type="text" class="form-control" id="' . $input_id . '" placeholder=" ' . $placeholder_text . '">
			';

			$data['html'] = '
			<tr class="row">
				<th>' . $col_name . '</th>
				<td>' . $td_html . '</td>
			</tr>';		
		}else{
			$td_html = '
			<input type="text" class="form-control" id="' . $input_id . '" placeholder=" ' . $placeholder_text . '">
			';

			$data['html'] = '
			<tr class="row">
				<th class="col-2">' . $col_name . '</th>
				<td class="col">' . $td_html . '</td>
			</tr>';	
		}
	}

	// $col_name = $col_name;
	// $data['html'] = '
	// <tr class=" row col ">
	// 	<td class="col-3">' . $col_name . '</td>
	// 	<td class="col ">' . $td_html . '</td>
	// </tr>';

	return $data;
}

// ----------------------
// 功能1：個人資料維護
// ----------------------
//點選欄位內容，就可直接編輯內容。
// $tr['member notice bar']
// $tr['Please check the correctness'] = '更新資料前請審慎確認資料正確性。資料填入後不可再進行更改，如需更改請洽客服。';
// $preview_status_html = '
// <div id="preview_area" class="alert alert-info" role="alert">' . $tr['Please check the correctness'] . '</div>';
// $member_persondata_html = $preview_status_html;
$member_persondata_html = '';
$member_account_col = '';
$member_persondata_col = '';
$member_bank_account_data_col = '';
$persondata_is_null = false;
$bankdata_is_null = false;
// 有會員資料 不論身份 therole 為何，都可以修改個人資料。但是除了 therole = T 除外。
if (isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T') {

	// $sql = "SELECT * FROM root_member WHERE account = '".$_SESSION['member']->account."';";
	$sql = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.id = '" . $_SESSION['member']->id . "';";
	$m = runSQLALL($sql, 0, 'r');

	// 只有 $m[0] == 1 才工作, 因為會員只有有一個編號或是 ID
	if ($m[0] != 1) {
		$logger = '(x) 會員資料有問題，可能是 BUG 請聯絡管理人員。' . $sql;
		syslog2db($_SESSION['member']->account, 'member', 'error', "$logger");
		$member_persondata_html = $logger;
	} else {

		// ------------------------------------------------------------------------
		// 提款的資訊, 需要填寫才可以提款。
		// ------------------------------------------------------------------------

		$bankaccountdata_arr = [
			'bankname' => [
				'member_bank_account_data_col_name' => $tr['bank name'],
				'placeholder_text' => $tr['bank name'],
				'bank_data' => $m[1]->bankname
			],
			'bankaccount' => [
				'member_bank_account_data_col_name' => $tr['bank account'],
				'placeholder_text' => $tr['bank account'].'('.$tr['Please fill in the numbers'].')',
				'bank_data' => $m[1]->bankaccount
			],
			'bankprovince' => [
				'member_bank_account_data_col_name' => $tr['bank province'],
				'placeholder_text' => $tr['bank province'],
				'bank_data' => $m[1]->bankprovince
			],
			'bankcounty' => [
				'member_bank_account_data_col_name' => $tr['bank country'],
				'placeholder_text' => $tr['bank country'],
				'bank_data' => $m[1]->bankcounty
			]
		];

		foreach ($bankaccountdata_arr as $colname => $content) {
			$table_data = get_bankaccountdata_persondata_html($content['bank_data'], $colname, $content['placeholder_text'], $content['member_bank_account_data_col_name']);
			$member_bank_account_data_col = $member_bank_account_data_col . $table_data['html'];

			if ($table_data['is_null'] != '') {
				$bankdata_is_null = $table_data['is_null'];
			}
		}

		// ------------------------------
		// 主表格框架 -- 帳務資料
		// ------------------------------
		$member_persondata_html = $member_persondata_html.'
		<div class="col-12">
		<table class="table member_table">
			<thead></thead>
			<tbody>
				'.$member_bank_account_data_col.'
			</tbody>
		</table>
		</div>
		';

		if ($bankdata_is_null == true) {
			$member_persondata_html = $member_persondata_html . '
      <button id="submit_change_bank_data" class="send_btn btn-primary">' . $tr['Save account settings'] . '</button>';
		}


		$extend_js = $extend_js . "
  	<script>
  	$(document).ready(function() {

		$('#submit_change_bank_data').click(function(){
        var bankname = $('#bankname').val();
        var bankaccount = $('#bankaccount').val();
        var bankprovince = $('#bankprovince').val();
				var bankcounty = $('#bankcounty').val();
				var csrftoken = '$csrftoken';

        var r = confirm('" . $tr['Confirmation of information'] . "');
        if (r == true) {
		  if( !isNaN( bankaccount ) ) {
				$.post('member_action.php?a=member_editbankdata',
				{
				bankname: bankname,
				bankaccount: bankaccount,
				bankprovince: bankprovince,
								bankcounty: bankcounty,
								csrftoken : csrftoken
				},
				function(result){
				$('#preview_result').html(result);
				}
			);
		  } else {
			  alert('" . $tr['bank account only can be filled number'] . "');
		  }
          
        }
			});
			
			// input-focus
			$('input.form-control').focus(function(e){
				$(e.target).prev('.fa-pencil-alt').addClass('input-focus');
			});
			$('input.form-control').focusout(function(e){
				$(e.target).prev('.fa-pencil-alt').removeClass('input-focus');
			});

  	});
  	</script>
  	";

	}
	// end of 會員資料存在 db 內 sql

} else {
	$member_persondata_html = '(x)你沒有權限，請登入系統。';
	$logger = $member_persondata_html;
	// memberlog 2db('guest', 'member', 'notice', "$logger");
  	$msg='(x)你没有权限，请登入系统。';
    $msg_log = '(x)你没有权限，请登入系统！';
    $sub_service='authority';
    memberlogtodb('guest','member','warning',"$msg",'guest',"$msg_log",'f',$sub_service);
	// login and goto page
	$member_persondata_html = login2return_url(0);
}

// 切成 3 欄版面 3:8:1
$indexbody_content = '';
//功能選單(美工)  功能選單(廣告)
$indexbody_content = $indexbody_content . '
<div class="row">
	<div class="col-12">
	<div class="main_content">
  ' . $member_persondata_html . '
	</div>
    </div>
</div>
<div class="row">
    <div class="col-12">
    <div id="preview_result"></div>
    </div>
</div>
';

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
$tmpl['sidebar_content'] = ['safe','member_banksetting'];
// banner標題
$tmpl['banner'] = ['membercenter_menu_admin_safe'];
// menu增加active
$tmpl['menu_active'] =['member.php'];
// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include $config['template_path'] . "template/admin.tmpl.php";

?>
