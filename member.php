
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

require_once dirname(__FILE__) ."/member_log_record_lib.php";

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
$function_title = $tr['membercenter_member'];
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

$extend_head=<<<HTML
	<style type="text/css">
		.show_https_detail{
			color: gray;
		}
		.dropdown{
			display:none;
		}
		.records_row .detail_https{
			border-style: none;
		}
	</style>
HTML;


/**
 * Timezones list with GMT offset
 *
 * @return array
 * @link http://stackoverflow.com/a/9328760
 */
function tz_list() {
	$zones_array = array();
	$timestamp = time();
	foreach (timezone_identifiers_list() as $key => $zone) {
		date_default_timezone_set($zone);
		$zones_array[$key]['zone'] = $zone;
		$zones_array[$key]['diff_from_GMT'] = 'UTC/GMT ' . date('P', $timestamp);
		$zones_array[$key]['GMT'] = date('P', $timestamp);
	}
	return $zones_array;
}
// 全部的時區列表
$timezone_list = tz_list();

function get_bankaccountdata_persondata_html($member_data, $input_id, $placeholder_text, $col_name) {
	global $config;
	if ($member_data != '' && $input_id != 'nickname') {
		$td_html = $member_data;
		$col_name = $col_name;
		if($config['site_style']=='mobile'){
			$html = '
			<tr class="row">
				<th>' . $col_name . '</th>
				<td>' . $td_html . '</td>
			</tr>';
		}else{
			$html = '
			<tr class="row">
				<th class="col-2">' . $col_name . '</th>
				<td class="col">' . $td_html . '</td>
			</tr>';
		}
		
	} else {
		if($config['site_style']=='mobile'){
			$td_html = '
			<i class="fas fa-pencil-alt pen_icon"></i>
			<input type="text" class="form-control" id="' . $input_id . '" placeholder=" ' . $placeholder_text . '">
			';
			$col_name = $col_name;
			$html = '
			<tr class="row">
				<th>' . $col_name . '</th>
				<td>' . $td_html . '</td>
			</tr>';		
		}else{
			$td_html = '
			<input type="text" class="form-control" id="' . $input_id . '" placeholder=" ' . $placeholder_text . '">
			';
			$col_name = $col_name;
			$html = '
			<tr class="row">
				<th class="col-2">' . $col_name . '</th>
				<td class="col">' . $td_html . '</td>
			</tr>';		
		}		
	}

	// $col_name = $col_name;
	// $html = '
	// <tr class="row">
	// 	<td class="col-3   pl-5">' . $col_name . '</td>
	// 	<td class="col   pl-5">' . $td_html . '</td>
	// </tr>';

	return $html;
}

function get_sex_html($member_data, $col_name) {
  global $tr;
  global $config;
	if ($member_data != '2') {
		$td_html = $member_data == '0' ? $tr['female'] : $tr['male'];
		$col_name = $col_name;
	} else {
		$td_html = '
		<select id="sex" name="sex" class="form-control">
			<option value="1">&nbsp;'.$tr['male'].'&nbsp;</option>
			<option value="0">&nbsp;'.$tr['female'].'&nbsp;</option>
			<option value="2" selected>&nbsp;'.$tr['gender unknown'].'&nbsp;</option>
		</select>
		';
		$col_name = $col_name;
	}

	if($config['site_style']=='mobile'){
		$html = '
		<tr class="row">
			<th>' . $col_name . '</th>
			<td>' . $td_html . '</td>
		</tr>';
	}else{
		$html = '
		<tr class="row">
			<th class="col-2">' . $col_name . '</th>
			<td class="col">' . $td_html . '</td>
		</tr>';
	}

	// $col_name = $col_name;
	// $html = '
	// <tr class="row">
	// 	<td class="col-3   pl-5">' . $col_name . '</td>
	// 	<td class="col   pl-5">' . $td_html . '</td>
	// </tr>';

	return $html;
}

function get_member_login_urlcode($account)
{
  $urlcode_base64 = base64_encode($account);

  return $urlcode_base64;
}

function get_recommendedcode($acc) {
  $recommendedcode_sha1 = sha1($acc);

  return $recommendedcode_sha1;
}

function updata_recommendedcode($acc)
{
	global $tr;
	$recommendedcode = get_recommendedcode($acc);

	$sql = <<<SQL
    UPDATE root_member SET recommendedcode = '$recommendedcode' WHERE account = '$acc'
SQL;

	$result = runSQL($sql);

	if (!$result) {
		$error_msg = $tr['promotion code update failed'];//推荐码更新失败
		return array('status' => false, 'result' => $error_msg);
	}

	return array('status' => true, 'result' => 'OK');
}

function get_tzonename($tz)
{
  // 轉換時區所要用的 sql timezone 參數
  $tzsql = "select * from pg_timezone_names where name like '%posix/Etc/GMT%' AND abbrev = '".$tz."';";
  $tzone = runSQLALL($tzsql);

  if($tzone[0]==1) {
    $tzonename = $tzone[1]->name;
  } else {
    $tzonename = 'posix/Etc/GMT-8';
  }

  return $tzonename;
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
		$logger = '(x) '.$tr['member error'] . $sql;//會員資料有問題，可能是 BUG 請聯絡管理人員。
		syslog2db($_SESSION['member']->account, 'member', 'error', "$logger");
		$member_persondata_html = $logger;
	} else {
		// 顯示會員資料，會員資料可以透過 ajax 即時修改
		//帳號
		$member_account_col_name = $tr['Account'];
		if($config['site_style']=='mobile'){
			$member_account_col = $member_account_col . '
			<tr class="row">
				<th>' . $member_account_col_name . '</th>
				<td>' . $m[1]->account . '</td>
			</tr>';
		}else{
			$member_account_col = $member_account_col . '
			<tr class="row">
				<th class="col-2">' . $member_account_col_name . '</th>
				<td class="col">' . $m[1]->account . '</td>
			</tr>';
		}
		

		// 身份
		//會員類型
		$member_account_col_name = $tr['membership type'];
		if ($m[1]->therole == 'M') {
			//會員
			$therole_html = $tr['member'];
		} elseif ($m[1]->therole == 'A') {
			//代理商
			$therole_html = $tr['agent'];
		} elseif ($m[1]->therole == 'R') {
			//管理員
			$therole_html = $tr['management'];
		} else {
			//會員身份有問題，請聯絡管理人員。
			$logger = $tr['member identity error'];
			die($logger);
		}
		if($config['site_style']=='mobile'){
			$member_account_col = $member_account_col . '
			<tr class="row">
				<th>' . $member_account_col_name . '</th>
				<td>' . $therole_html . '</td>
			</tr>';
		}else{
			$member_account_col = $member_account_col . '
			<tr class="row">
				<th class="col-2">' . $member_account_col_name . '</th>
				<td class="col">' . $therole_html . '</td>
			</tr>';
		}
		

		// 顯示註冊日期
		$member_account_col_name = $tr['registration date'];
		$registration_date = gmdate('Y-m-d H:i:s',strtotime($m[1]->enrollmentdate)-4*3600);
		if($config['site_style']=='mobile'){
			$member_account_col = $member_account_col . '
			<tr class="row">
				<th>' . $member_account_col_name . '</th>
				<td>' . $registration_date . '</td>
			</tr>';
		}else{
			$member_account_col = $member_account_col . '
			<tr class="row">
				<th class="col-2">' . $member_account_col_name . '</th>
				<td class="col">' . $registration_date . '</td>
			</tr>';
		}
		


		$member_persondata_arr = [
			'realname' => [
				'member_persondata_col_name' => $tr['real name'],
				'placeholder_text' => $tr['real name notice'],
				'member_data' => $m[1]->realname
			],
			'nickname' => [
				'member_persondata_col_name' => $tr['nickname'],
				'placeholder_text' => $tr['Current nickname'].$m[1]->nickname,
				'member_data' => $m[1]->nickname
			],
			'mobilenumber' => [
				'member_persondata_col_name' => $tr['cellphone'],
				'placeholder_text' => $tr['cellphone notice'] ,
				'member_data' => $m[1]->mobilenumber
			],
			'email' => [
				'member_persondata_col_name' => $tr['email'],
				'placeholder_text' => $tr['email'],
				'member_data' => $m[1]->email
			],
			'birthday' => [
				'member_persondata_col_name' => $tr['brithday'],
				'placeholder_text' => $tr['brithday'],
				'member_data' => $m[1]->birthday
			],
			'sex' => [
				'member_persondata_col_name' => $tr['gender'],
				'member_data' => $m[1]->sex
			],
			'wechat' => [
				'member_persondata_col_name' => $protalsetting["custom_sns_rservice_1"]??$tr['sns1'],
				'placeholder_text' => $protalsetting["custom_sns_rservice_1"]??$tr['sns1'],
				'member_data' => $m[1]->wechat
			],
			'qq' => [
				'member_persondata_col_name' => $protalsetting["custom_sns_rservice_2"]??$tr['sns2'],
				'placeholder_text' => $protalsetting["custom_sns_rservice_2"]??$tr['sns2'],
				'member_data' => $m[1]->qq
			],
		];

		foreach ($member_persondata_arr as $colname => $content) {
			$table_data = ($colname != 'sex') ? get_bankaccountdata_persondata_html($content['member_data'], $colname, $content['placeholder_text'], $content['member_persondata_col_name']) : get_sex_html($content['member_data'], $content['member_persondata_col_name']);
			$member_persondata_col = $member_persondata_col . $table_data;

		}

		// modal
		$show_modal = '';
		$detail = convert_to_fuzzy_time($m[1]->lastlogin);
		$show_modal =<<<HTML
			<p class="member_time_style py-2" data-toggle="modal" data-target="#modal" id="get_member_log">{$tr['Last activity time']}: {$detail}</p>
			<!-- <button type="button" class="text-left btn btn-primary" data-toggle="modal" data-target="#modal" id="get_member_log">会员登入纪录</button> -->
			<div class='modal fade MenuModal' id='record_MenuModal' tabindex='-1' role='dialog' aria-labelledby='vLabel' aria-hidden='true'>
				<div class='modal-dialog modal-lg modal-dialog-centered' role='document'>
					<div class='modal-content modal_contentstyle'>
						<div class='modal-header'>
							<h6 class='modal-title' id='MenuModalLabel'>{$tr['Recent activity']}</h6>
							
							<button type='button' class='close' data-dismiss='modal' aria-label='Close'>
								<span aria-hidden='true'>&times;</span>
							</button>
						</div>
						<div class='modal-body'>
							<span>* {$tr['last 10 login records']}</span>
							<table class='table member_table_ac'>
								<thead>
									<tr class='row'>
										<th scope='col' class='col-4 text-truncate'>{$tr['Access type']}</th>
										<th scope='col' class='col-4 text-truncate'>{$tr['IP address']}</th>
										<th scope='col' class='col-4 text-truncate'>{$tr['date time']}</th>
									</tr>
								</thead>
								<tbody id='records_detail'>
								</tbody>
								<tfoot>
									<tr class='row'>
										<th scope='col' class='col-4 text-truncate'>{$tr['Access type']}</th>
										<th scope='col' class='col-4 text-truncate'>{$tr['IP address']}</th>
										<th scope='col' class='col-4 text-truncate'>{$tr['date time']}</th>
									</tr>
								</tfoot>								
							</table>
						</div>
					</div>
				</div>
			</div>
HTML;

		// ------------------------------
		// 主表格框架
		// ------------------------------
		$member_persondata_html = $member_persondata_html.'
		<div class="col-12">
		<table class="table member_table member_data_list">
			<thead></thead>
			<tbody>
				'.$member_account_col.'
			</tbody>			
		</table>
		'.$show_modal.'
		<table class="table member_table member_data_list">
			<thead></thead>
			<tbody>
				'.$member_persondata_col.'
			</tbody>			
		</table>
		</div>		
		<button id="submit_change_member_data" class="send_btn bg-primary">' . $tr['Save profile settings'] . '</button>
		';

		// ref. doc: http://xdsoft.net/jqplugins/datetimepicker/
		// 取得日期的 jquery datetime picker -- for birthday
		// date 選擇器 https://jqueryui.com/datepicker/
		// http://api.jqueryui.com/datepicker/
		// 14 - 100 歲為年齡範圍， 25-55 為主流客戶。
		$dateyearrange_start 	= date("Y") - 100;
		$dateyearrange_end 		= date("Y") - 14;
		$datedefauleyear		= date("Y") - rand(25,55);

		// 加密函式密碼
		// var password_input  = $().crypt({method:'sha1', source:$('#password_input').val()});
		// $extend_js = $extend_js.'<script src="jquery.crypt.js"></script>';
		// member 編輯的欄位 JS
		//訊息1 : 請將底下所有 * 欄位資訊填入
		//訊息2 : 前後密碼不一致
		// $tr['input correct nickname'] = '請填入正確暱稱。';
		// $tr['Confirmation of information'] = '請再次確認資料正確性，送出後不可再修改，如需修改請洽客服人員，確定要送出修改資料嗎？';

		$extend_js = $extend_js . "
  	<script>
  	$(document).ready(function() {
      // for birthday
      $('#birthday').datetimepicker({
        defaultDate:'".$datedefauleyear."/01/01',
				minDate: '".$dateyearrange_start."/01/01',
				maxDate: '".$dateyearrange_end."/01/01',
				timepicker:false,
				format:'Y/m/d',
				lang:'en'
		});
		
		// input-focus
		$('input.form-control').focus(function(e){
			$(e.target).prev('.fa-pencil-alt').addClass('input-focus');
		});
		$('input.form-control').focusout(function(e){
			$(e.target).prev('.fa-pencil-alt').removeClass('input-focus');
		});

      $('#submit_change_member_data').click(function(){
				var realname = $('#realname').val();
				var nickname = $('#nickname').val();
        var mobilenumber = $('#mobilenumber').val();
        var email = $('#email').val();
				var birthday = $('#birthday').val();
				var sex = $('#sex').val();
        var wechat = $('#wechat').val();
        var qq = $('#qq').val();
				var csrftoken = '$csrftoken';

        var r = confirm('" . $tr['Confirmation of information'] . "');
        if (r == true) {
          $.post('member_action.php?a=member_editpersondata',
            {
							realname: realname,
							nickname: nickname,
              mobilenumber: mobilenumber,
              email: email,
							birthday: birthday,
							sex: sex,
              wechat: wechat,
              qq: qq,
							csrftoken : csrftoken
            },
            function(result){
              $('#preview_result').html(result);
            }
          );
        }
			});

		// 會員登入紀錄
		$('#get_member_log').on('click',function(e){
			e.preventDefault();

			$.ajax({
				url:'member_log_record_action.php',
				type: 'POST',
				data:{
					action: 'log_record'
				},
				success:function(resp){
					var res = JSON.parse(resp);

					if(res.status == 'success'){		
						if($('.records_row:last').length == 0){
							$('#records_detail').append(combineHTML(res.data));
						}else{
							
						}
						$('#record_MenuModal').modal('show');
						
					}else{
						console.log('error');
					}
				}
			})
		})

	});

	// modal
	function combineHTML(data){
		var html = '';
		// 	<div class='dropdown' id='dropdown_`+currentValue.id+`'><br>`+currentValue.detail_user_agent+`</div>
		var load = data.reduce(function(accumulator, currentValue, currentIndex, array){
			html = `
				<tr class='records_row row' id='`+currentValue.id+`'>
					<td class='p-2 col-4 text-truncate'>`+currentValue.http_user_agent+` 
						
					</td>
					<td class='p-2 col-4 text-truncate'>`+currentValue.agent_ip+`</td>
					<td class='p-2 col-4 text-truncate'>`+currentValue.occurtime+`
						
					</td>
				</tr>
				<tr class='records_row'>
					<td colspan='3' class='detail_https py-0'>
						<div class='dropdown mb-2' id='dropdown_`+currentValue.id+`'>`+currentValue.detail_occurtime+` <br>`+currentValue.detail_user_agent+`</div>
					</td>
				</tr>
			`;
			
			return accumulator + html;
		},'');
		return load;
	
	  }

  	</script>
	";
	 
	}
	// end of 會員資料存在 db 內 sql

} else {
	$member_persondata_html = $tr['no permission login first'];//(x)你没有权限，請登入系統。
	$logger = $member_persondata_html;
	// memberlog 2db('guest', 'member', 'notice', "$logger");
  	$msg=$tr['no permission login first'];//(x)你没有权限，请登入系统。
    $msg_log = $tr['no permission login first'];//(x)你没有权限，请登入系统！
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
	<div class="col-12" id="member_paget">
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
$tmpl['sidebar_content'] = ['safe','member'];
// banner標題
$tmpl['banner'] = ['membercenter_menu_admin_safe'];
// menu增加active
$tmpl['menu_active'] =['member.php'];
// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include $config['template_path'] . "template/admin.tmpl.php";

?>