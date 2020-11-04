<?php
// ----------------------------------------------------------------------------
// Features:	前台 -- 優惠紅包兌現
// File Name:   promotion_activity.php
// Author:		Mavis
// Related:
// Log:
// 依據後台的活動優惠碼管理, 引導進入對應的行銷活動頁面。
// ----------------------------------------------------------------------------


// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";
// 優惠碼函式庫
require_once dirname(__FILE__) ."/promotion_activity_lib.php";

// require_once dirname(__FILE__) ."/in/mobiledetect/Mobile_Detect.php";

//var_dump($_SESSION);
//var_dump($_POST);

// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------
// var_dump($_SESSION['member']->account);die();

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
$function_title 		=  $tr['Promotion code redeem'];
// 擴充 head 內的 css or js
$extend_head				= '';
// 放在結尾的 js
$extend_js					= '';
// body 內的主要內容
$indexbody_content	= '';
// 系統訊息選單
$messages 					= '';
// ----------------------------------------------------------------------------
// 初始化變數 end
// ----------------------------------------------------------------------------


// ----------------------------------------------------------------------------

// 活動代碼
if(isset($_GET['a']) AND $_GET['a'] != NULL){
	$act_id = filter_var($_GET['a'],FILTER_SANITIZE_STRING);
	
}else{
	header('Location:home.php');
	die();
}

// 回上頁
// 優惠活動id
if(isset($_GET['more_detail']) AND $_GET['more_detail'] != NULL){
	// 如果有在優惠活動設定 優惠紅包兌患的連結，回到指定的上一頁
	$get_promotions_id = filter_var($_GET['more_detail'],FILTER_SANITIZE_STRING);

	$url = "promotions_detail.php?id={$get_promotions_id}";
}else{
	// 回到優惠活動
	$get_promotions_id = '';
	$url = 'promotions.php';
}

// ----------------------------------------------------------------------------

// 導覽列
$navigational_hierarchy_html =<<<HTML
	<ul class="breadcrumb">
		<li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
		<li><a href="promotions.php">{$tr['Pormotions']}</a></li>
		<li><a href="{$url}">{$tr['Pormotions detail']}</a></li>
		<li class="active">{$function_title}</li>
	</ul>
HTML;

if($config['site_style']=='mobile'){
	$navigational_hierarchy_html =<<<HTML
		<a href="{$config['website_baseurl']}{$url}"><i class="fas fa-chevron-left"></i></a>
		<span>{$function_title}</span>
		<i></i>
HTML;
}

// 當未登入時，加上JWT字串
// 需要傳遞的陣列
// formtype --> [POST|GET] 轉址傳遞變數的方式(必要)
// formurl --> 自訂轉址指定的網址, 相對路徑或絕對路徑都可以 (必要)
// 其他變數(自訂)
$get_serial = array(
	"formtype" => "POST",
	"formurl" => "promotion_activity.php?a=$act_id"
);
// var_dump($get_serial);die();
// 產生 token , salt是檢核密碼預設值為123456 ,需要配合 jwtdec 的解碼, 此範例設定為 123456
$token = jwtenc('123456', $get_serial);
$gotourl = 'login2page.php?t='.$token;

// -------------------------------------------------

if(isset($act_id)){

	$to_select_domain = get_certain_act($act_id); // 特定活動 domain

	$domain = $to_select_domain[1]->activity_domain; // 網域
	$subdomain = $to_select_domain[1]->activity_subdomain; // 子網域
	$find_sub = str_replace('/',' ',$subdomain); // 移除 '/'
	$desktop_sub = explode(" ",$find_sub); // 桌機

	$mobile_sub = substr(strrchr($subdomain,"/"),1); // 取得去掉/後的第一個字母 手機
	$combine_mobile = $mobile_sub.'.'.$domain; // mobile url
	$combine_desktop = $desktop_sub[0].'.'.$domain; // desktop url

	// 過濾domain
	if($_SERVER['HTTP_HOST'] == $combine_desktop OR $_SERVER['HTTP_HOST'] == $combine_mobile){

		$show_html= '';

		$current_datepicker =  gmdate('Y-m-d H:i:s',time() + -4*3600);

		$all_activity_result = get_all_activity(); // 所有活動

		$allact_id = $all_activity_result[1]->activity_id;
		$allact_status = $all_activity_result[1]->activity_status;

		$sql_result = get_activity_data($act_id); // 特定活動

		if($sql_result[0] == 1 AND $sql_result[1]->endtime >= $current_datepicker ){
			$activity_id = $sql_result[1]->activity_id;
			// 活動名稱
			$act_name = $sql_result[1]->activity_name;
			// 活動時間
			$effecttime = $sql_result[1]->effecttime;
			$act_time = gmdate('Y-m-d',strtotime($sql_result[1]->effecttime.'-04')+ 8*3600).' ~ '.gmdate('Y-m-d',strtotime($sql_result[1]->endtime.'-04') + 8*3600);
			// 活動說明
			$act_desc = $sql_result[1]->activity_desc;

			// 活動狀態
			$act_status = $sql_result[1]->activity_status;

			// 活動條件
			$act_requirement = $sql_result[1]->promocode_req;
			$act_decode = json_decode($act_requirement,true);

			$requirement_member_time = $act_decode['reg_member_time'];
			$requirement_desposit_amount = $act_decode['desposit_amount'];
			$requirement_betting_amount = $act_decode['betting_amount'];
			$requirement_account_type = $act_decode['user_therole']; // 帳戶類型

			if(isset($_SESSION['member']->id)){
				$the_requirement = $tr['qualifications'];//'兑换资格'
				$show_login = '';
				$show_serial_buttons = ''; // 優惠碼、兌換
				$show_requirements = '';
				$show_disable = '';
				$more_disable_color = '';

				// 會員資料
				$get_member_data = get_member_data();
				$reg_date = $get_member_data[1]->enrollmentdate; // 入會日期
				$member_tokenwallet = $get_member_data[1]->gtoken_balance; // gtoken錢包
				$member_ip = $get_member_data[1]->registerip; // ip
				$member_fingerprint = $get_member_data[1]->registerfingerprinting; // fingerprint
				$member_role = $get_member_data[1]->therole; // 角色 M，A

				// 取得會員投注紀錄
				$now = date('Y-m-d');
				$activity_select_sdate = date('Y-m-d',strtotime("$effecttime -1 month")); // 活動開始前1個月到領取的前一天
				$activity_select_edate = date('Y-m-d',strtotime("$now -1 day"));
				$get_betting = get_betting_data($activity_select_sdate,$activity_select_edate);
				$total_bet = $get_betting[1]->bets;

				$act_decode_req['betting_amount'] = betting_limit($total_bet,$requirement_betting_amount);
				$act_decode_req['desposit_amount'] = deposit_limit($member_tokenwallet,$requirement_desposit_amount);
				$act_decode_req['reg_member_time'] = reg_time_limit($reg_date,$requirement_member_time);
				$act_decode_req['user_therole'] = check_member_role($member_role,$requirement_account_type);

				$show_disable_ch[]=$act_decode_req['betting_amount']['status'];
				$show_disable_ch[]=$act_decode_req['desposit_amount']['status'];
				$show_disable_ch[]=$act_decode_req['reg_member_time']['status'];
				$show_disable_ch[]=$act_decode_req['user_therole']['status'];

				// 有設定條件而且有登入顯示條件、是否符合資格
				$show_requirements.=$act_decode_req['betting_amount']['html'];
				$show_requirements.=$act_decode_req['desposit_amount']['html'];
				$show_requirements.=$act_decode_req['reg_member_time']['html'];
				$show_requirements.=$act_decode_req['user_therole']['html'];

				$check_member = check_user_frompromotion($act_id); // 判斷使用者有沒有領過

				if($check_member[0] >= 1){
					for($i=1;$i<=$check_member[0];$i++){
						$member = $check_member[$i]->member_account;
						$serial_code = $check_member[$i]->promo_id;

						if($member == $_SESSION['member']->account){
							$show_disable = 'disabled';
							$notice= $tr['You have already redeemed'].','.$tr['Promotion code'].':'.$serial_code;//您已经领过了，优惠码:
						}
					}
				}else{
					if(in_array("0",$show_disable_ch)){
						$show_disable = 'disabled';
						$more_disable_color ='btn-secondary';
						$notice = $tr['Not qualified'];//'您不符合兑换条件'
					}else{
						$more_disable_color ='btn-success';
						$notice = '例:abcd5r59qt6c';
					}
				}
				// 優惠碼、兌換 沒登入，不能看到
				if($config['site_style']=='mobile'){
				$show_serial_buttons=<<<HTML
				<div class="d-flex flex-column bd-highlight mt-10 p_act_name shadow-sm">
				  <div class="p-2 bd-highlight"><input type="text" class="form-control col-12" id="act_promotion_number" aria-describedby="basic-addon2" placeholder="{$notice}" maxlength= "12" onKeyUp="value=value.replace(/[\W]/g,'')" {$show_disable}/></div>
				  <div class="p-2 bd-highlight"><button id="submit" type="button" class="btn col-12 promotions_detail_submit {$more_disable_color}" {$show_disable}>{$tr['redeem']}</button></div>
				</div>
HTML;					
				}else{
				$show_serial_buttons=<<<HTML
				<div class="row">
					<input type="text" class="form-control col-12" id="act_promotion_number" aria-describedby="basic-addon2" placeholder="{$notice}" maxlength= "12" onKeyUp="value=value.replace(/[\W]/g,'')" {$show_disable}/>
					<button id="submit" type="button" class="btn col-12 promotions_detail_submit {$more_disable_color}" {$show_disable}>{$tr['redeem']}</button>
				</div>
HTML;
}
				// 活動說明，沒設定不顯示
				if($act_desc == ''){
					$act_desc_title = '';
					$activity_description = '';
				}else{
					$act_desc_title = $tr['Pormotions detail'];//'活动说明:'
					// 活動說明
					$activity_description = <<<HTML
					<div class="row promotions_detail">
						<!--<div class="col-4 col-lg-2 mt-2 mb-2"><p>{$act_desc_title}</p></div>-->
							<div class="col">
								<p>{$act_desc}</p>
							</div>
					</div>
HTML;
				}
				// 領取資格
				if($config['site_style']=='mobile'){
				$limit_requirements=<<<HTML
				<div class="p-2 title_promotion_activity">{$the_requirement}</div>
				<div class="p-2 content_promotion_activity">
			  	<div class="qualifications_list border-0">
						{$show_requirements}
				</div>
			  </div>
HTML;					
				}else{
				$limit_requirements=<<<HTML
					<div class="row">
						<div class="col-4 border-0">
							<p>{$the_requirement}</p>
						</div>
						<div class="col-8 qualifications_list border-0">
							<!-- 條件 -->
							{$show_requirements}
						</div>
					</div>
HTML;
}
			}else{
				// 沒登入请先登入会员
				$show_login =<<<HTML
					<div class="col-lg-7 col-12">
						<button type="button" id="login_member" class="btn btn-success"><a href="{$gotourl}">{$tr['login first']}</a></button>
					</div>
HTML;
				$the_requirement = '';
				$show_serial_buttons = '';
				$activity_description = '';
				$limit_requirements = '';
				// $section_buttons = '';
			}

			if($act_status == '1' AND $activity_id == $act_id){
				if($config['site_style']=='mobile'){
					$promotion_act_name =<<<HTML
					<div class="promotion_activity_box mt-10">
						<div class="d-flex flex-column bd-highlight border-bottom p_act_name rounded-top">
					  <div class="p-2 bd-highlight text-center">{$act_name}</div>
					</div>
HTML;
				}else{
					$promotion_act_name =<<<HTML
						<div class="row">
						<div class="col-4">
							<p>{$tr['activity name']}</p>
						</div>
						<div class="col-8">
							<p>{$act_name}</p>
						</div>
					</div>
HTML;
				}
				if($config['site_style']=='mobile'){
				$show_html.=<<<HTML
					{$promotion_act_name}
					<div class="d-flex bd-highlight  border-bottom">
					  <div class="p-2 flex-fill bd-highlight p_act_name">{$tr['activity time']}</div>
					  <div class="p-2 flex-fill bd-highlight text-right p_act_name">{$act_time}</div>
					</div>

					<!-- 領取資格 -->
						{$limit_requirements}

					<!-- 優惠碼、兌換 沒登入，不能看到-->
						{$show_serial_buttons}

					<!-- 活動說明-->
						{$activity_description}	

					<!-- 先登入 -->
					<div class="row justify-content-md-center">
						{$show_login}
					</div>
HTML;					
				}else{
				$show_html.=<<<HTML
					{$promotion_act_name}
					<div class="row">
						<div class="col-4">
							<p>{$tr['activity time']}</p>
						</div>
						<div class="col-8">
							<p>{$act_time}</p>
						</div>
					</div>

					<!-- 領取資格 -->
						{$limit_requirements}

					<!-- 優惠碼、兌換 沒登入，不能看到-->
						{$show_serial_buttons}

					<!-- 活動說明-->
						{$activity_description}	

					<!-- 先登入 -->
					<div class="row justify-content-md-center">
						{$show_login}
					</div>
HTML;
}
			}else{
				$show_html =<<<HTML
 					<div class="alert alert-danger" role="alert">{$tr['no activity']}</div>
HTML;
			}

		}elseif($allact_id != $act_id){
			$show_html =<<<HTML
			<div class="alert alert-danger" role="alert">活动已过期，无法兑换</div>
HTML;
		}
	}else{
		// domain跟活動domain不符
		$show_html =<<<HTML
 			<div class="alert alert-danger mt-10" role="alert">{$tr['no activity']}</div>
HTML;
	}

	$extend_js=<<<HTML
	<script>
	$(function(){
		// 按兌換後，做判斷
		$('#submit').click(function(){

			if($('#act_promotion_number').val() == ''){
				alert('{$tr['Please fill in the promotion code']}');
			}else{
				alert('{$tr['Determine redemption?']}');
			};

			var act_id = '$act_id'; // 活動代碼
			var promotion_number = $('#act_promotion_number').val(); // 優惠碼
			var csrftoken = '$csrftoken';

			$.ajax({
				url: 'promotion_activity_action.php?p=receive_promotion',
				type: 'POST',
				data:({
					act_id : act_id,
					promotion_number: promotion_number,
					csrftoken: csrftoken
				}),
				success:function(result){
					$('#preview').html(result);
				},
				error:function(error){
					$('#preview').html(error);
				}
			});
	  	})
	})

	</script>
HTML;

  }else{

    // 3 秒後移轉到lobby
    $logger = '<script>setTimeout(location.href="home.php",3000);</script>';
    $show_html=<<<HTML
		  <div class="alert alert-success" role="alert">{$tr['login first']}</div>
		{$logger}
HTML;
}

// 切成 3 欄版面
$indexbody_content =<<<HTML
<div class="main_content promotion_activity_box">
			{$show_html}
		<div id="preview"></div>
</div>
HTML;

// ----------------------------------------------------------------------------
// MAIN  END
// ----------------------------------------------------------------------------



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
// banner標題
$tmpl['banner'] = ['Pormotions'];
// menu增加active
$tmpl['menu_active'] =['promotions.php'];


// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
// include($config['template_path']."template/static.tmpl.php");
include $config['template_path'] . "template/static.tmpl.php";

?>