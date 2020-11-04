
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
$function_title = '登入連結';
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
	$data['is_null'] = '';
	if ($member_data != '') {
		$td_html = $member_data;
	} else {
		$data['is_null'] = true;
		$td_html = '
		<input type="text" class="form-control" id="' . $input_id . '" placeholder=" ' . $placeholder_text . '">
		';
	}

	$col_name = $col_name;
	$data['html'] = '
	<tr>
		<td>' . $col_name . '</td>
		<td>' . $td_html . '</td>
	</tr>';

	return $data;
}

function get_sex_html($member_data, $col_name) {
  global $tr;

	$data['is_null'] = '';
	if ($member_data != '2') {
		$td_html = $member_data == '0' ? $tr['female'] : $tr['male'];
	} else {
		$data['is_null'] = true;
		$td_html = '
		<select id="sex" name="sex" class="form-control">
			<option value="1">&nbsp;'.$tr['male'].'&nbsp;</option>
			<option value="0">&nbsp;'.$tr['female'].'&nbsp;</option>
			<option value="2" selected>&nbsp;'.$tr['gender unknown'].'&nbsp;</option>
		</select>
		';
	}

	$col_name = $col_name;
	$data['html'] = '
	<tr>
		<td>' . $col_name . '</td>
		<td>' . $td_html . '</td>
	</tr>';

	return $data;
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
	$recommendedcode = get_recommendedcode($acc);

	$sql = <<<SQL
    UPDATE root_member SET recommendedcode = '$recommendedcode' WHERE account = '$acc'
SQL;

	$result = runSQL($sql);

	if (!$result) {
		$error_msg = '推薦碼更新失敗';
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
$preview_status_html ='';
$member_persondata_html = $preview_status_html;
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
		// 顯示會員資料，會員資料可以透過 ajax 即時修改
		//帳號
		$member_account_col_name = $tr['Account'];
		$member_account_col = $member_account_col . '
		<tr>
			<td>' . $member_account_col_name . '</td>
			<td>' . $m[1]->account . '</td>
		</tr>';

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
		$member_account_col = $member_account_col . '
		<tr>
			<td>' . $member_account_col_name . '</td>
			<td>' . $therole_html . '</td>
		</tr>';

		//暱稱
		$member_account_col_name = $tr['nickname'];
		// $tr['Current nickname'] = '目前暱稱';
		// $tr['edit nickname'] = 'Edit';
		$member_account_col = $member_account_col . '
		<tr>
			<td>' . $member_account_col_name . '</td>
			<td>
				<div class="form-inline">
					<input type="text" class="form-control" id="nickname" placeholder="' . $tr['Current nickname'] . '' . $m[1]->nickname . '">
					<button type="submit" id="submit_change_nickname" class="btn btn-warning"><span class="glyphicon glyphicon-ok" aria-hidden="true">' . $tr['edit nickname'] . '</button>
				</div>
			</td>
		</tr>';

		// 顯示註冊日期
		$member_account_col_name = $tr['registration date'];
		$member_account_col = $member_account_col . '
		<tr>
			<td>' . $member_account_col_name . '</td>
			<td>' . $m[1]->enrollmentdate . '</td>
		</tr>';


		$member_persondata_arr = [
			'realname' => [
				'member_persondata_col_name' => $tr['real name'],
				'placeholder_text' => $tr['real name notice'],
				'member_data' => $m[1]->realname
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
				'member_persondata_col_name' => $tr['sns1'],
				'placeholder_text' => $tr['wechat ID'],
				'member_data' => $m[1]->wechat
			],
			'qq' => [
				'member_persondata_col_name' => $tr['sns2'],
				'placeholder_text' => $tr['QQ ID'],
				'member_data' => $m[1]->qq
			]
		];

		foreach ($member_persondata_arr as $colname => $content) {
			$table_data = ($colname != 'sex') ? get_bankaccountdata_persondata_html($content['member_data'], $colname, $content['placeholder_text'], $content['member_persondata_col_name']) : get_sex_html($content['member_data'], $content['member_persondata_col_name']);
			$member_persondata_col = $member_persondata_col . $table_data['html'];

			if ($table_data['is_null'] != '') {
				$persondata_is_null = $table_data['is_null'];
			}
		}

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
				'placeholder_text' => $tr['bank account'],
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
		// 主表格框架 -- 上層代理商資訊以及提供申請成為代理商的資訊
		// ------------------------------

		$tzonename = get_tzonename($_SESSION['member']->timezone);
		$find_sql = "SELECT *, to_char((enrollmentdate AT TIME ZONE '$tzonename'),'YYYY-MM-DD HH24:MI:SS') as enrollmentdate FROM root_member WHERE id = '" . $m[1]->parent_id . "';";
		$find_result = runSQLall($find_sql, 0, 'r');
		// var_dump($find_result);
		if ($find_result[0] == 1) {
			$parent_enrollmentdate = gmdate('Y-m-d H:i:s', strtotime($find_result[1]->enrollmentdate) + -4 * 3600);

			// show 上層代理商資訊
			//我的推薦人資訊
			//我的推薦人帳號
			//我的推薦人姓名
			//我的推薦人註冊的日期
			$member_persondata_html = $member_persondata_html.'
			<table class="table table-bordered">
				<thead></thead>
				<tbody>
					<tr>
						<td>' . $tr['referrer account'] . '</td>
						<td>' . $find_result[1]->account . '</td>
					</tr>
					<tr>
						<td>' . $tr['referrer name'] . '</td>
						<td>' . $find_result[1]->realname . '</td>
					</tr>
					<tr>
						<td>' . $tr['referrer registration date'] . '</td>
						<td>' . $parent_enrollmentdate . '</td>
					</tr>
				</tbody>
			</table>
			';
		}

		$urlcode_base64 = get_member_login_urlcode($m[1]->account);
		$login_url = "https://".$config['website_domainname']."/app.php?m=".$urlcode_base64;

		$member_persondata_html = $member_persondata_html.'
		<table class="table table-bordered">
			<thead></thead>
			<tbody>
				<tr>
					<td>' . $tr['login url'] . '</td>
					<td><a href="' . $login_url . '">' . $login_url . '</a></td>
				</tr>
			</tbody>
		</table>
		';

		// ------------------------------
		// 如果餘額大於申請額度，顯示申請成為代理商的連結, 如果已經是代理商顯示推廣連結。
		// ------------------------------
		// 申請代理商最低需要的金額 CNY -- config
		// 你的條件符合，可以申請成為我們的聯營夥伴。 設定擋在 system_config.php 檔案
		$agent_need_balance = $system_config['agency_registration_gcash'];

		if ($_SESSION['member']->therole == 'A' OR $_SESSION['member']->therole == 'R') {
			// 有寫成 url rewrite
			/*
	      [root@allinone conf.d]# more new_RewriteRule
	          if ($uri ~ "^/r/(.*)$"){
	             set $id $1;
	             rewrite "/" /register.php?r=$id? redirect;
	          }
*/
			if (!empty($_SESSION['member']->recommendedcode)) {
				$updata_recommendedcode = (object)updata_recommendedcode($_SESSION['member']->account);
				if (!$updata_recommendedcode->status) {
					echo '<script>alert("'.$updata_recommendedcode->result.'");</script>';
    			die();
				}

				$agent_r = get_recommendedcode($_SESSION['member']->account);
			} else {
				$agent_r = $_SESSION['member']->recommendedcode;
			}

		} else {
			//你的条件符合，可以申请成为我们的代理商。
			$became_partner_html = '<hr>
  		<p align="right"><a href="partner.php" target="_BLANK" class="btn btn-success btn-block" role="button">' . $tr['Balance'] . money_format('%i', $agent_need_balance) . $tr['conditions meet , apply to become our agents'] . '</a></p>';

			//加入成为代理商资讯。
			$became_partner_need_html = '<hr>
      <p align="right"><a href="partner.php" target="_BLANK" class="btn btn-info btn-block" role="button">' . $tr['join agent info'] . '</a></p>   ';

			if ($_SESSION['member']->gcash_balance >= $agent_need_balance AND $_SESSION['member']->therole == 'M') {
				$member_persondata_html = $member_persondata_html . $became_partner_html;
			} else {
				$member_persondata_html = $member_persondata_html . $became_partner_need_html;
			}
		}

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

      // for nickname
      $('#submit_change_nickname').click(function(){
				var nickname = $('#nickname').val();
				var csrftoken = '$csrftoken';

  			if((nickname) == ''){
  				alert('" . $tr['Confirmation of information'] . "');
        }else{
          // $('#save_ben').attr('disabled', 'disabled');
          $.post('member_action.php?a=member_editpersondata_nickname',
            {
							nickname: nickname,
							csrftoken : csrftoken
            },
            function(result){
              $('#preview_result').html(result);}
          );
        }
  		});

      $('#submit_change_member_data').click(function(){
  			var realname = $('#realname').val();
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

			$('#submit_change_bank_data').click(function(){
        var bankname = $('#bankname').val();
        var bankaccount = $('#bankaccount').val();
        var bankprovince = $('#bankprovince').val();
				var bankcounty = $('#bankcounty').val();
				var csrftoken = '$csrftoken';

        var r = confirm('" . $tr['Confirmation of information'] . "');
        if (r == true) {
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
        }
  		});

      // for password 1
      $('#submit_change_password').click(function(){
  			var current_password =  $().crypt({method:'sha1', source:jQuery.trim($('#current_password').val()) });
        var change_password_valid1 = $().crypt({method:'sha1', source:jQuery.trim($('#change_password_valid1').val()) });
				var change_password_valid2 = $().crypt({method:'sha1', source:jQuery.trim($('#change_password_valid2').val()) });
				var csrftoken = '$csrftoken';

  			if((current_password) == '' || (change_password_valid1) == '' || (change_password_valid2) == ''  ){
  				alert('" . $tr['please fill all * field'] . "');
        }else{
          if(change_password_valid1 == change_password_valid2 ){
            $('#submit_change_password').attr('disabled', 'disabled');
    				$.post('member_action.php?a=member_editpersondata_passwordm',
    					{
                current_password: current_password,
    						change_password_valid1: change_password_valid1,
								change_password_valid2: change_password_valid2,
								csrftoken : csrftoken
    					},
    					function(result){
    						$('#preview_result').html(result);}
    				);

          }else{
    				alert('" . $tr['password 1and 2 not the same'] . "');
          }
        }
  		});

      // for password 2
      $('#send_change_withdrawalpassword').click(function(){
  			var withdrawal_password =  $().crypt({method:'sha1', source:jQuery.trim($('#withdrawal_password').val()) });
        var change_withdrawalpassword_valid1 = $().crypt({method:'sha1', source:jQuery.trim($('#change_withdrawalpassword_valid1').val()) });
				var change_withdrawalpassword_valid2 = $().crypt({method:'sha1', source:jQuery.trim($('#change_withdrawalpassword_valid2').val()) });
				var csrftoken = '$csrftoken';

  			if((withdrawal_password) == '' || (change_withdrawalpassword_valid1) == '' || (change_withdrawalpassword_valid2) == ''  ){
  				alert('" . $tr['please fill all * field'] . "');
        }else{
          if(change_withdrawalpassword_valid1 == change_withdrawalpassword_valid2 ){
            $('#send_change_withdrawalpassword').attr('disabled', 'disabled');
    				$.post('member_action.php?a=member_editpersondata_passwordw',
    					{
                withdrawal_password: withdrawal_password,
    						change_withdrawalpassword_valid1: change_withdrawalpassword_valid1,
								change_withdrawalpassword_valid2: change_withdrawalpassword_valid2,
								csrftoken : csrftoken
    					},
    					function(result){
    						$('#preview_result').html(result);}
    				);

          }else{
    				alert('" . $tr['password 1and 2 not the same'] . "');
          }
        }
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
<div class="row justify-content-md-center">
	<div class="col-12">
  ' . $member_persondata_html . '
    </div>
</div>
<div class="row justify-content-md-center">
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
$tmpl['sidebar_content'] =['safe','member_share_friends'];

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include $config['template_path'] . "template/admin.tmpl.php";

?>
