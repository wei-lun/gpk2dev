<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 訪客透過頁面自動註冊功能
// 管理員，無須審查，但是可以透過後台看到註冊的狀況
// 需要有推薦代理商的欄位才可以註冊，需產生推薦代理商專用的網址。
// File Name:	register.php
// Author:		Barkley
// Related:
// Log: 2016.10.11
// ----------------------------------------------------------------------------



// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

require_once dirname(__FILE__) ."/spread_register_lib.php";
require_once dirname(__FILE__) ."/register_lib.php";
// uisetting函式庫
require_once dirname(__FILE__) ."/lib_uisetting.php";

// 註冊專用函式庫
// require_once dirname(__FILE__) ."/register_lib.php";

require_once dirname(__FILE__) ."/in/phpcaptcha/simple-php-captcha.php";
$_SESSION['register_captcha'] = simple_php_captcha();

//var_dump($_SESSION);

// var_dump(session_id());
// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// 檢查獎置介面是行動裝置還是桌機
// ----------------------------------------------------------------------------
$device_chk_html = clientdevice_detect(0,0)['html'];
echo $device_chk_html;
// ----------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
$function_title = $tr['register title'];
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


function get_accountdata_html($arr)
{

	global $config;

	$required_html = ($arr['inputid'] != 'linkcode') ? '<i>*</i>' : '';
	$pattern_html =  '';

	$td_html = '
	<input type="'.$arr['inputtype'].'" class="form-control" id="'.$arr['inputid'].'" placeholder="ex: '.$arr['placeholder_text'].'"  autocomplete="'.$arr['autocomplete_text'].'" >
	';

	if ($arr['value'] != '') {
		$td_html = '
		<input type="'.$arr['inputtype'].'" class="form-control" id="'.$arr['inputid'].'" placeholder="ex: '.$arr['placeholder_text'].'" value="'.$arr['value'].'" autocomplete="'.$arr['autocomplete_text'].'" >
		';
	}

	$col_name = $required_html.$arr['col_name'];

	if($config['site_style'] == 'mobile') {
	$html = '
	<tr class="row">
		<th scope="row" class="col-12">'.$col_name.'</th>
		<td class="col-12">'.$td_html.'</td>
	</tr>
	';
	}else{
	$html = '
	<tr class="row">
		<th scope="row" class="col-2">'.$col_name.'</th>
		<td class="col-10">'.$td_html.'</td>
	</tr>
	';
	}

	return $html;
}

function get_persondata_html($arr)
{
	global $config;
	$html = '';

	if ($arr['isshow'] == 'on') {

		$required_html = $arr['ismust'] == 'on' ? '<i>*</i>' : '';

		$td_html = '
		<input type="text" class="form-control" id="'.$arr['inputid'].'" placeholder="ex: '.$arr['placeholder_text'].'" autocomplete="'.$arr['autocomplete_text'].'">
		';

		if (isset($arr['value']) && $arr['value'] != '') {
			$td_html = '
			<input type="text" class="form-control" id="'.$arr['inputid'].'" placeholder="ex: '.$arr['placeholder_text'].'" value="'.$arr['value'].'" autocomplete="'.$arr['autocomplete_text'].'">
			';
		}

		$col_name = $required_html.$arr['col_name'];

		if($config['site_style'] == 'mobile') {
		$html = '
    <tr class="row">
      <th scope="row" class="col-12">'.$col_name.'</th>
      <td class="col-12">'.$td_html.'</td>
    </tr>
    ';
  }else{
		$html = '
    <tr class="row">
      <th scope="row" class="col-2">'.$col_name.'</th>
      <td class="col-10">'.$td_html.'</td>
    </tr>
    ';
  }
  }

	return $html;
}

function get_register_sex_html($arr)
{
  global $tr;
  global $config;

	$html = '';

  if ($arr['isshow'] == 'on') {

		$required_html = $arr['ismust'] == 'on' ? '<i>*</i>' : '';

		$td_html = '
		<select id="sex" name="sex" class="form-control">
			<option value="1">&nbsp;'.$tr['male'].'&nbsp;</option>
			<option value="0">&nbsp;'.$tr['female'].'&nbsp;</option>
			<option value="2" selected>&nbsp;'.$tr['gender unknown'].'&nbsp;</option>
		</select>
		';

		$col_name = $required_html.$arr['col_name'];

		if($config['site_style'] == 'mobile') {
		$html = '
    <tr class="row">
      <th scope="row" class="col-12">'.$col_name.'</th>
      <td class="col-12">'.$td_html.'</td>
    </tr>
    ';
	  }else{
	  $html = '
    <tr class="row">
      <th scope="row" class="col-2">'.$col_name.'</th>
      <td class="col-10">'.$td_html.'</td>
    </tr>
    ';
	  }

  }

  return $html;
}

function encryption_agent_account($account)
{
  $first_cher = substr($account, 0, 1);
  $last_cher = substr($account, -1);

  $encryption_str = $first_cher.'******'.$last_cher;

  return $encryption_str;
}


$member_persondata_html = '
<div id="preview_area" class="well well-sm" ><i>* </i>'.$tr['require field'].'</div>
';
$member_accountdata_col = '';
$member_persondata_col = '';

// 登入中無法進入註冊頁
if (isset($_SESSION['member'])) {
	echo '<script>window.location.replace("./home.php");</script>';
	die();
}

if (!member_register_isopen()) {
	echo '<script>
	alert("'.$tr['member registration closed'].'");
	window.location.replace("./");
	</script>';
	die();
}

// -------------------------------------------
// guest 身份，推薦人欄位存在
// -------------------------------------------
$linkcode = '';
if(isset($_GET['r'])) {
	$link_code = filter_var($_GET['r'], FILTER_SANITIZE_STRING);
	if ($link_code == '') {
		echo '<script>alert("错误的请求参数");document.location.href="./register.php";</script>';
    die();
	}

	$spreadlink = (object)select_spreadlink_bylinkcode($link_code);

	if (!$spreadlink->status) {
		echo '<script>alert("'.$spreadlink->result.'");document.location.href="./register.php";</script>';
    die();
	}
	//邀請碼已過期
	if ($spreadlink->result->end_date < getEDTDate()) {
		echo '<script>alert("'.$tr['invitation code expired'].'");document.location.href="./register.php";</script>';
		die();
  }

	if ($protalsetting['agency_registration_gcash'] != '0' && $spreadlink->result->register_type == 'A') {
		echo '<script>alert("无效的推广连结");document.location.href="./register.php";</script>';
		die();
	}

	$linkcode = $spreadlink->result->link_code;

	// 將推薦人帳號加密
	// $encryptionaccount = encryption_agent_account($spreadlink->result->account);

	// $member_accountdata_col = $member_accountdata_col.'
	// <tr>
	// 	<th scope="row">'.$tr['recommender'].'</th>
	// 	<td id="agentaccount_input">'.$encryptionaccount.'</td>
	// </tr>
	// ';

	$visits_data = (object)[
		'link_code' => $linkcode,
		'browser' => get_userbrowser($_SERVER['HTTP_USER_AGENT']),
		'ip' => $_SESSION['fingertracker_remote_addr'] ?? explode(',',$_SERVER['HTTP_X_FORWARDED_FOR'])['0'],
		'fingerprinting' => $_SESSION['fingertracker'] ?? ''
	];

	// 更新訪問人次
	update_visits_number($visits_data);
}


// 帳戶資料區塊
$arr['linkcode'] = [
	'col_name'=>$tr['membercenter_spread_register'],
	'placeholder_text'=>$tr['invitation_code8'],
	'isshow'=>'on',
	'ismust'=>$protalsetting['member_register_linkcode_must'],
	'inputid'=>'linkcode',
	'value'=>$linkcode,
	'autocomplete_text'=>'new-linkcode'
];
$table_data = get_persondata_html($arr['linkcode']);
$member_accountdata_col = $member_accountdata_col.$table_data;

//帳號
$arr['account'] = [
	'col_name'=>$tr['member account'],
	'placeholder_text'=>$tr['register account limit'],
	'inputid'=>'account',
	'inputtype'=>'text',
	'value'=>'',
	'autocomplete_text'=>'new-user'
];
$table_data = get_accountdata_html($arr['account']);
$member_accountdata_col = $member_accountdata_col.$table_data;

$arr['password'] = [
	'col_name'=>$tr['member password'],
	'placeholder_text'=>$tr['please enter password'].'('.$tr['need to be 6 to 12'].')',
	'inputid'=>'password',
	'inputtype'=>'password',
	'value'=>'',
	'autocomplete_text'=>'new-password'
];
$table_data = get_accountdata_html($arr['password']);
$member_accountdata_col = $member_accountdata_col.$table_data;

$arr['confirm_password'] = [
	'col_name'=>$tr['confirm password'],
	'placeholder_text'=>$tr['please confirm passord'],
	'inputid'=>'confirm_passord',
	'inputtype'=>'password',
	'value'=>'',
	'autocomplete_text'=>'new-confirm-password'
];
$table_data = get_accountdata_html($arr['confirm_password']);
$member_accountdata_col = $member_accountdata_col.$table_data;

$member_persondata_html = $member_persondata_html.'
<form>
<table class="table mb-0">
	<thead></thead>
	<tbody>
		'.$member_accountdata_col.'
	</tbody>
</table>
</form>
';


// 個人資料設定區塊

//真實名稱
$arr['realname'] = [
	'col_name'=>$tr['real name'],
	'placeholder_text'=>$tr['real name notice'],
	'isshow'=>$protalsetting['member_register_name_show'],
	'ismust'=>$protalsetting['member_register_name_must'],
	'inputid'=>'realname',
	'autocomplete_text'=>'new-realname'
];
$table_data = get_persondata_html($arr['realname']);
$member_persondata_col = $table_data;

//行動電話
$arr['mobilenumber'] = [
	'col_name'=>$tr['cellphone'],
	'placeholder_text'=>'请填写手机号码',
	'isshow'=>$protalsetting['member_register_mobile_show'],
	'ismust'=>$protalsetting['member_register_mobile_must'],
	'inputid'=>'cellphone',
	'autocomplete_text'=>'new-cellphone'
];
$table_data = get_persondata_html($arr['mobilenumber']);
$member_persondata_col = $member_persondata_col.$table_data;

//電子郵件
$arr['email'] = [
	'col_name'=>$tr['email'],
	'placeholder_text'=>'(abc@aa.com)',
	'isshow'=>$protalsetting['member_register_mail_show'],
	'ismust'=>$protalsetting['member_register_mail_must'],
	'inputid'=>'email',
	'autocomplete_text'=>'new-email'
];
$table_data = get_persondata_html($arr['email']);
$member_persondata_col = $member_persondata_col.$table_data;

// 生日
$arr['birthday'] = [
	'col_name'=>$tr['brithday'],
	'placeholder_text'=>$tr['brithday'],
	'isshow'=>$protalsetting['member_register_birthday_show'],
	'ismust'=>$protalsetting['member_register_birthday_must'],
	'inputid'=>'birthday',
	'autocomplete_text'=>'new-birthday'
];
$table_data = get_persondata_html($arr['birthday']);
$member_persondata_col = $member_persondata_col.$table_data;

// 性別
$arr['sex'] = [
	'col_name'=>$tr['gender'],
	'isshow'=>$protalsetting['member_register_sex_show'],
	'ismust'=>$protalsetting['member_register_sex_must'],
	'inputid'=>'sex',
	'autocomplete_text'=>'new-sex'
];
$table_data = get_register_sex_html($arr['sex']);
$member_persondata_col = $member_persondata_col.$table_data;

//微信 ID
$arr['wechat'] = [
	'col_name'=>$protalsetting["custom_sns_rservice_1"]??$tr['sns1'],
	'placeholder_text'=>$protalsetting["custom_sns_rservice_1"]??$tr['sns1'],
	'isshow'=>$protalsetting['member_register_wechat_show'],
	'ismust'=>$protalsetting['member_register_wechat_must'],
	'inputid'=>'wechat',
	'autocomplete_text'=>'new-wechat'
];
$table_data = get_persondata_html($arr['wechat']);
$member_persondata_col = $member_persondata_col.$table_data;

// QQ
$arr['qq'] = [
	'col_name'=>$protalsetting["custom_sns_rservice_2"]??$tr['sns2'],
	'placeholder_text'=>$protalsetting["custom_sns_rservice_2"]??$tr['sns2'],
	'isshow'=>$protalsetting['member_register_qq_show'],
	'ismust'=>$protalsetting['member_register_qq_must'],
	'inputid'=>'qq',
	'autocomplete_text'=>'new-qq'
];
$table_data = get_persondata_html($arr['qq']);
$member_persondata_col = $member_persondata_col.$table_data;

//個人資料設定
if ($member_persondata_col != '') {
	$member_persondata_html = $member_persondata_html.'
	<table class="table">
		<thead></thead>
		<tbody>
			'.$member_persondata_col.'
		</tbody>
	</table>
	';
}
//驗證碼
if($config['site_style'] == 'mobile') {
$register_captcha = '
<tr class="row">
<th class="col-12"><i>*</i>'.$tr['Verification code'].'</th>
<td class="col-12 verification_code">
		<input name="captcha_register_input" class="form-control" id="captcha_register_input" type="text" placeholder="' . $tr['Verification click code'] . '"  >
		<span id="show_register_captcha" class= "show_captcha_css"><img src="'.$cdnfullurl_js.'img/common/hello.png" id="captcha_register_input" alt="'.$tr['Verification code'].'" title="'.$tr['Verification click code'].'" height="20" width="65" ></span>
</td>
</tr>
';
}else{
$register_captcha = '
<tr class="row">
<th class="col-2"><i>*</i>'.$tr['Verification code'].'</th>
<td class="col-10 verification_code">
		<input name="captcha_register_input" class="form-control" id="captcha_register_input" type="text" placeholder="' . $tr['Verification click code'] . '"  >
		<span id="show_register_captcha" class= "show_captcha_css"><img src="'.$cdnfullurl_js.'img/common/hello.png" id="captcha_register_input" alt="'.$tr['Verification code'].'" title="'.$tr['Verification click code'].'" height="20" width="65" ></span>
</td>
</tr>
';
}
//驗證碼
$member_persondata_html = $member_persondata_html.'
<form>
<table class="table mb-0">
	<thead></thead>
	<tbody>
		'.$register_captcha.'
	</tbody>
</table>
</form>
';

// 把個人隱私保護政策 + 會員條款, 以 php 方式讀入. 如果要作多語系比較方便
require_once dirname(__FILE__) ."/register_privacy_protection.php";

//之全部說明。<br>(需先同意上述會員條款及個人資料暨隱私權保護政策之全部說明，才可提交送出。)
if($config['site_style'] == 'mobile') {
$register_member_form_row = '
	<div class="checkbox row">
		<div class="col-12">
		<label>
			<input type="checkbox" class="user_check" id="terms_agree"  checked>
			<span>'.$tr['i agreed below'].'</span>
			<button type="button" data-toggle="modal" data-target="#membership_terms_modal">
				'.$tr['terms of service title'].'
			</button>
			'.$tr['and'].'
			<button type="button" data-toggle="modal" data-target="#privacy_protection_modal">
				'.$tr['privacy policy'].'
			</button>
		</label>
	</div>
	</div>
';
}else{
$register_member_form_row = '
	<div class="checkbox row">
		<div class="col-2"></div>
		<div class="col-10">
		<label>
			<input type="checkbox" class="user_check" id="terms_agree"  checked>
			'.$tr['i agreed below'].'
			<button type="button" data-toggle="modal" data-target="#membership_terms_modal">
				'.$tr['terms of service title'].'
			</button>
			'.$tr['and'].'
			<button type="button" data-toggle="modal" data-target="#privacy_protection_modal">
				'.$tr['privacy policy'].'
			</button>
		</label>
	</div>
	</div>
';
}
// '.$tr['terms of service notes'].'

//條款內容
//關閉按鈕
if(isset($ui_data['copy']['member'][$_SESSION['lang']]) AND $ui_data['copy']['member'][$_SESSION['lang']]!="")
	$terms_of_service_content=$ui_data['copy']['member'][$_SESSION['lang']];
else{
	$terms_of_service_content=$tr['terms of service content1'].'<br><br>

	'.$tr['terms of service content2'].'<br><br>

	'.$tr['terms of service content3'].' <br><br>

	'.$tr['terms of service content4'].' <br><br>

	'.$tr['terms of service content5'].'<br><br>

	'.$tr['terms of service content6'].' <br><br>

	'.$tr['terms of service content7'];
}

$register_member_form_row = $register_member_form_row.'
<!-- membership_terms_modal -->
<div class="modal fade" id="membership_terms_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content modal_contentstyle">
      <div class="modal-header">
      	<h4 class="modal-title" id="myModalLabel">'.$tr['terms of service title'].'</h4>
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
      </div>
      <div class="modal-body">
        '.$terms_of_service_content.'
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">'.$tr['off'].'</button>
      </div>
    </div>
  </div>
</div>
';

// 商城個人資料隱私權保護政策
$register_member_form_row = $register_member_form_row.'
<!-- privacy_protection_modal -->
<div class="modal fade" id="privacy_protection_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content modal_contentstyle">
      <div class="modal-header">
      	<h4 class="modal-title" id="myModalLabel">'.$register_privacy_protection_title.'</h4>
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
      </div>
      <div class="modal-body">
      '.$register_privacy_protection_desc.'
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">关闭</button>
      </div>
    </div>
  </div>
</div>
';

// 送出表單處理
$register_form_send = '
	<div>
	<button id="submit_to_register" class="btn btn-primary btn-block" type="submit" >'.$tr['submit'].'</button>
	<div id="submit_to_register_result"></div>
	<div class="row button_register">
		<div class="col">
			<a href="login2page.php"><p>'.$tr['Have account'].'</p></a>
		</div>
		<div class="col">
			<a href="contactus.php"><p>'.$tr['login_service_contact'].'</p></a>
		</div>
	</div>
	</div>
';
// <button id="submit_to_register_create_cancel" class="btn btn-danger btn-block" type="submit" onClick="window.location.reload();">'.$tr['cancel'].'</button>

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

	//click code change img
	// $('#show_register_captcha').click(function(){
	// 	$.post( 'in/captcha/captchabase64.php', function( captchabase64data ) {
	// 		var img_cpatcha_html = \"<img src='\"+captchabase64data+\"' id='captcha' alt='Verification code' height='20' width='58'>\" ;
	// 		$('#show_register_captcha').html(img_cpatcha_html);
	// 	});
	// });


	$('#captcha_register_input').click(function(){
		$.post( 'in/captcha/captchabase64.php', function( captchabase64data ) {
			var img_cpatcha_html = \"<img src='\"+captchabase64data+\"' id='captcha' alt='Verification code' height='20' width='58'>\" ;
			$('#show_register_captcha').html(img_cpatcha_html);
		});
	});

	$('#captcha_register_input').focus(function(){
		$.post( 'in/captcha/captchabase64.php', function( captchabase64data ) {
			var img_cpatcha_html = \"<img src='\"+captchabase64data+\"' id='captcha' alt='Verification code' height='20' width='58'>\" ;
			$('#show_register_captcha').html(img_cpatcha_html);
		});
	});

	// for birthday
	$('#birthday').datetimepicker({
		defaultDate:'".$datedefauleyear."/01/01',
		minDate: '".$dateyearrange_start."/01/01',
		maxDate: '".$dateyearrange_end."/01/01',
		timepicker:false,
		format:'Y/m/d',
		lang:'en'
	});

	// click close button
	$('#terms_agree').click(function(){
		if( $('#terms_agree').prop('checked') ){
			//console.log('OK');
			$('#submit_to_register').removeAttr(\"disabled\");
			$('#submit_to_register').removeClass('active');
		}else{
			//console.log('NO');
			$('#submit_to_register').attr('disabled',\"true\");
			$('#submit_to_register').addClass('active');
		}
	});


	// click input open button
	// $('#terms_agree').click(function() {
	// 	$('#terms_agree').attr('disabled',\"true\");
	// 	$('#submit_to_register').removeAttr(\"disabled\");
	// });

	$('#submit_to_register').click(function(){
		if($('#terms_agree').prop('checked')) {
			var terms_agree = 'selected';
		} else {
			var terms_agree = 'unchecked';
		}

		var linkcode = $('#linkcode').val();
		var memberaccount_input = $('#account').val();
		var realname_input = $('#realname').val();
		var captcha_register_input = $('#captcha_register_input').val();
		var mobilenumber_input = $('#cellphone').val();
		var email_input = $('#email').val();
		var sex_input = $('#sex').val();
		var birthday_input = $('#birthday').val();
		var wechat_input = $('#wechat').val();
		var qq_input = $('#qq').val();
		var password1_input = $('#password').val();
		var password2_input = $('#confirm_passord').val();
		var csrftoken = '$csrftoken';
		var minlength = 6;
		var maxlength = 12;

		if( password1_input.length < minlength || password1_input.length > maxlength ){
			//請將底下所有 * 欄位資訊填入
			alert('".$tr['please enter password'].'('.$tr['need to be 6 to 12'].')'."');
			$('#captcha_register_input').val('');
		}else if(jQuery.trim(password1_input) == '' || jQuery.trim(password2_input) == '' || jQuery.trim(memberaccount_input) == '' || 	jQuery.trim($('#captcha_register_input').val()) == ''){
			//請將底下所有 * 欄位資訊填入
			alert('".$tr['please fill all * field']."');
			$('#captcha_register_input').val('');
		}else{
			var password1_input = $().crypt({method:'sha1', source:$('#password').val()});
			var password2_input = $().crypt({method:'sha1', source:$('#confirm_passord').val()});

			// $('#submit_to_register').attr('disabled', 'disabled');
			//你確定要註冊帳號嗎？
			var r = confirm('".$tr['confirm register account']."');
			if (r == true) {
				$('#captcha_register_input').val('');
				$.post('register_action.php?a=member_register',
					{
						csrftoken : csrftoken,
						linkcode: linkcode,
						memberaccount_input: memberaccount_input,
						password1_input: password1_input,
						password2_input: password2_input,
						sex_input: sex_input,
						realname_input: realname_input,
						mobilenumber_input: mobilenumber_input,
						email_input: email_input,
						birthday_input: birthday_input,
						wechat_input: wechat_input,
						qq_input: qq_input,
						captcha_register_input: captcha_register_input,
						terms_agree: terms_agree
					},
					function(result){
						$('#submit_to_register_result').html(result);
					}
				);
			} else {
					x = '放棄註冊帳號';
					console.log(x);
			}
		}
	});

});
</script>
";

if ($protalsetting['member_register_switch'] == 'off' AND (!isset($_SESSION['member']) OR (isset($_SESSION['member']) AND $_SESSION['member']->therole != 'A'))) {
	$member_persondata_html = '
	<div class="alert alert-danger" role="alert">
		<p>会员注册功能关闭中，如有疑问请洽客服。</p>
		<p>点击 <a href="./contactus.php">联络我们</a> 取得客服联络资讯。</p>
	</div>';
	$register_member_form_row = '';
	$register_form_send = '';

	$extend_js = '';
}

if (isset($_SESSION['member']) AND $_SESSION['member']->therole == 'A' AND $_SESSION['member']->status != 1) {
	$member_persondata_html = '
	<div class="alert alert-danger" role="alert">
		<p>无法使用协助开户功能，如有疑问请联络客服。</p>
		<p>点击 <a href="./contactus.php">联络我们</a> 取得客服联络资讯。</p>
	</div>';
	$register_member_form_row = '';
	$register_form_send = '';

	$extend_js = '';
}
//註冊成功
if (isset($_GET['rs']) && !empty($_GET['rs']) && $_GET['rs'] == 'finish') {
	$register_status_str =  (isset($_GET['drs']) &&  $_GET['drs'] == '4' ) ? 'registration under review' : 'registration is successful';
	$register_status =  (isset($tr[$register_status_str])) ? $tr[$register_status_str] : $register_status_str;
	if($config['site_style']=='mobile'){
		$member_persondata_html = '
		<div class="row" align="center">
			<div class="col-12 col-md-4"></div>
			<div class="col-12 col-md-4">
				<img src="'.$cdnrooturl.'success_icon.png" alt="..." class="img-rounded mb-3">
				<p class="main_color_dark main_p">'.$register_status.'</p>
				<a href="./login2page.php" class="send_btn btn-primary mb-4 register_text" role="button" id="new_register_submit">'.$tr['login to system'].'</a>
				<a href="./home.php" class="send_btn btn-secondary register_text" role="button">'.$tr['Return Home'].'</a>
			</div>
			<div class="col-12 col-md-4"></div>
		</div>';
	}else{
	$member_persondata_html = '
	<div class="row" align="center">
		<div class="col-12 col-md-4"></div>
		<div class="col-12 col-md-4">
			<img src="'.$cdnrooturl.'success_icon.png" alt="..." class="img-rounded mb-3">
			<p class="main_color_dark main_p">'.$register_status.'</p>
			<p>
				<div class="form-inline justify-content-center mt-3">
					<div class="form-group">
						<a href="./login2page.php" class="btn btn-primary mr-3" role="button" id="new_register_submit">'.$tr['login to system'].'</a>
						<a href="./home.php" class="btn btn-secondary" role="button">'.$tr['Return Home'].'</a>
					</div>
				</div>
			</p>
		</div>
		<div class="col-12 col-md-4"></div>
	</div>';
	}
	$register_member_form_row = '';
	$register_form_send = '';

	$extend_js = '';
}


// 切成 3 欄版面 3:8:1
$indexbody_content = '';
//功能選單(美工)  功能選單(廣告)
$indexbody_content = $indexbody_content . '
	<div id="register">
	'.$member_persondata_html .'
	'.$register_member_form_row.'
  '.$register_form_send.'
  </div>
<br>
';

$extend_head=<<<HTML
	<style>
		.container.menuheader{
			display: none;
		}
	</style>
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

?>
