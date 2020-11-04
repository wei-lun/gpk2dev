<?php
// ----------------------------------------------------------------------------
// Features :	錢包相關功能html and js生成
// File Name: lib_wallets.php
// Author   : Neil
// Related  :
// Log      :
// ----------------------------------------------------------------------------

// 娛樂城函式庫
require_once dirname(__FILE__) ."/casino_lib.php";

function getMemberData()
{
	$sql = <<<SQL
	SELECT *
	FROM root_member
	JOIN root_member_wallets
	ON root_member.id=root_member_wallets.id
	WHERE root_member.id = '{$_SESSION['member']->id}'
	AND status = '1';
SQL;

	$result = runSQLALL($sql, 0, 'r');

	if ($result[0] == 1 ) {
		return $result[1];
	}
	return false;
}

function combineManualGcashToGtokenHtml()
{
	global $tr;
	global $config;
	//現金錢包充值
	if($config['site_style']=='mobile'){
		$html = <<<HTML
		<div class="money_package_pc_m">
		<h2 class="font-1r">{$tr['Gcash wallet deposit']}
			<label for="description_off" class="description_label">
			<i class="fas fa-info-circle description_i description_color_gray" title="{$tr['Instructions for use']}">
			<input type="checkbox" id="description_off" name="" value="" class="description_off">
			<!-- change add class top bottom left right-->
			<div class="description_div bottom">
				<p>{$tr['GCASH to cash notice1']}</p>
				<p>{$tr['GCASH to cash notice2']}</p>
			</div>
			</i>
			</label>
		</h2>
		<form class="money_package_box">
			<div class="select_box form-row">
			<div class="col-9 col-md-10">
			<select class="form-control" id="manual_gcashtogtoken">
				<option>{$tr['select deposit amount']}</option>
				<option>{$tr['deposit amount less 100']}</option>
				<option>100</option>
				<option>200</option>
				<option>500</option>
				<option>1000</option>
				<option>2000</option>
				<option>5000</option>
				<option>10000</option>
			</select>
			</div>
			<div class="col-3 col-md-2">
			<button type="button" class="btn btn-primary" id="manual_gcashtogtoken_btn">{$tr['recharge']}</button>
			</div>
			</div>

			<div class="row">
				<div class="col-12">
					<p>{$tr['GCASH to cash notice1']}</p>
					<p>{$tr['GCASH to cash notice2']}</p>
				</div>
			</div>
		</form>
		</div>

		<div class="money_package_pc_m mobile_only">
			<h2>{$tr['More deposit types']}</h2>
		</div>
HTML;
	}else{
		$html = <<<HTML
		<div class="money_package_content">
		<div class="money_package_pc_m">
		<div class="title">
			<div><i class="fas fa-dollar-sign"></i></div>
			<div class="d-flex justify-content-center align-items-center">
				<p>{$tr['Gcash wallet deposit']}</p>
			</div>
		</div>
		<div>
			<p>{$tr['GCASH to cash notice1']}</p>
			<p>{$tr['GCASH to cash notice2']}</p>
		</div>
		<form class="money_package_box">
			<div class="select_box form-row">
				<div class="w-100">
					<select class="form-control" id="manual_gcashtogtoken">
						<option>{$tr['select deposit amount']}</option>
						<option>{$tr['deposit amount less 100']}</option>
						<option>100</option>
						<option>200</option>
						<option>500</option>
						<option>1000</option>
						<option>2000</option>
						<option>5000</option>
						<option>10000</option>
					</select>
				</div>
				<div class="w-100 mt-3">
					<button type="button" class="btn btn-primary" id="manual_gcashtogtoken_btn">{$tr['recharge']}</button>
				</div>
			</div>
		</form>
		</div>
</div>
HTML;
}

/*	<!--<div class="card">
		<strong class="card-header">現金錢包充值</strong>
		<div class="card-body">
			<form>
				<div class="form-row">
					<div class="col-11">
						<select class="form-control" id="manual_gcashtogtoken">
							<option>{$tr['select deposit amount']}</option>
							<option>{$tr['deposit amount less 100']}</option>
							<option>100</option>
							<option>200</option>
							<option>500</option>
							<option>1000</option>
							<option>2000</option>
							<option>5000</option>
							<option>10000</option>
						</select>
					</div>
					<div class="col-1">
						<button type="button" class="btn btn-primary" id="manual_gcashtogtoken_btn">{$tr['recharge']}</button>
					</div>
				</div>
				<br>
				<div class="form-row">
					<div class="col-12">
						<p>{$tr['GCASH to cash notice1']}</p>
						<p>{$tr['GCASH to cash notice2']}</p>
					</div>
				</div>
			</form>
		</div>
	</div>-->
*/
	return $html;
}

function combineGetCasinoBalanceHtml($currentpage=null)
{
	global $tr;
	global $cdnrooturl;
	global $config;

	$casinoLib = new casino_lib();
	$html = '';

	$memberData = getMemberData();

		if($memberData){
		$total_balance =$memberData->gtoken_balance+$memberData->gcash_balance;
		if( $memberData->gcash_balance == NULL OR $memberData->gcash_balance == ''){
			$gcash_total = '$0';
		}else{
			$gcash_total = '$'.$memberData->gcash_balance;
		}
		if ($memberData->gtoken_lock == NULL OR $memberData->gtoken_lock == '') {
			$statusHtml ='';
			$gtoken_balance='$'.$memberData->gtoken_balance;
		} else {
			$casino = $casinoLib->getCasinoNameByCasinoId($_SESSION['member']->gtoken_lock, $_SESSION['lang']);
			if($config['site_style']=='mobile'){
				$statusHtml = <<<HTML
				<button type="button" id="gtokenrecycling" class="deposit_using btn-danger" data-toggle="gtokenrecycling" title="{$tr['all casino to gtoken']}" >{$tr['retrieve']}</button>
				<button type="button" class="btn description_icon" data-container="body" data-toggle="popover" data-placement="left" data-content="{$tr['recycle gtoken']} - {$tr['token now']}{$casino}{$tr['casino used']}">
					<i class="fa fa-info-circle" aria-hidden="true"></i>
				</button>
	HTML;
		}else{
			$statusHtml = <<<HTML
			<button type="button" id="gtokenrecycling" class="btn-danger btn-block" data-toggle="gtokenrecycling" title="{$tr['all casino to gtoken']}" ><div class="h5">{$tr['recycle gtoken']}</div>
			<div id="token-lock-info"><small>{$tr['token now']}{$casino}{$tr['casino used']}</small></div>
			</button>
	HTML;
		}
		$gtoken_balance = '<span class="text-danger">'.$tr['casino used'].'!</span>';
	}

		// 判斷會員等級
		$therole_icon_html = '';
		if($_SESSION['member']->therole == 'M') {
			//會員
			$tooltip_therole_show_html = $tr['member'];
			$therole_icon_html = '<span id="member-role" class="glyphicon glyphicon-user" aria-hidden="true"  data-toggle="tooltip_therole" data-placement="left" title="'.$tooltip_therole_show_html.'" style="cursor:pointer";></span>';
		}elseif($_SESSION['member']->therole == 'A') {
			// '加盟联营股东' = 代理商
			$tooltip_therole_show_html = $tr['agent'];
			$therole_icon_html = '<span id="member-role" class="glyphicon glyphicon-knight" aria-hidden="true"  data-toggle="tooltip_therole" data-placement="left" title="'.$tooltip_therole_show_html.'" style="cursor:pointer"></span>';
		}elseif($_SESSION['member']->therole == 'R') {
			//管理員
			$tooltip_therole_show_html = $tr['management'];
			$therole_icon_html = '<span id="member-role" class="glyphicon glyphicon-king" aria-hidden="true"  data-toggle="tooltip_therole" data-placement="left" title="'.$tooltip_therole_show_html.'" style="cursor:pointer"></span>';
		}else{
			//測試帳號
			$tooltip_therole_show_html = $tr['test account'];
			$therole_icon_html = '<span id="member-role" class="glyphicon glyphicon-eye-open" aria-hidden="true"  data-toggle="tooltip_therole" data-placement="left" title="'.$tooltip_therole_show_html.'"></span>';
		}

		$hide_gcash_mode = hide_gcash_mode();//隱藏現金模式

		$pages =[
			'deposit'=>$tr['membercenter_deposit'],
			'wallets'=>$tr['membercenter_wallets']
		];

		if ($hide_gcash_mode == 'off') {
			$pages['exchange_token'] = $tr['membercenter_exchange_token'];
		}

		$sider_menu_list='';

	if($currentpage!=null){
		foreach ($pages as $key => $value) {
			$active = ($key==$currentpage)? ' class="active"':'';
			$url = $key . '.php';
			$sider_menu_list .=<<<HTML
			<li{$active}><a href="{$config['website_baseurl']}{$url}">{$value}</a></li>
	HTML;
		}
	}


	if ($hide_gcash_mode == 'on') {
		$sider_gcash_info = '';
	$sider_money_info =<<<HTML
				<p class="d-inline-block text-truncate"><span>{$tr['GTOKEN']}：</span>{$gtoken_balance}</p>
				<p id="sider-token-status" class="sider-member-txt d-inline-block text-truncate">
				{$statusHtml}
				</p>
	HTML;
		$mobile_gcash_info = '';
	}else{
		$sider_gcash_info = '<p class="sider-member-txt"><span>'.$tr['total balance'].'</span>：$'.$total_balance.'</p>
		<p class="sider-member-txt"><span>'.$tr['GCASH'].'</span>：'.$gcash_total.'</p>
		';
		$sider_money_info = '<p class="sider-member-txt d-inline-block text-truncate" data-toggle="tooltip" data-placement="top" title="$'.$total_balance.'"><span>'.$tr['total balance'].'：</span>$'.$total_balance.'</p>';
		$mobile_gcash_info = <<<HTML
		<div class="row deposit_header deposit_content border-bottom">
			<div class="col-5 deposit_p">
				<p class="d-inline-block text-truncate" data-toggle="tooltip" data-placement="top" title="{$gcash_total}"><span>{$tr['GCASH']}：</span>{$gcash_total}</p>
			</div>
			<div class="col-7 pl-0 deposit_p">
				<p class="d-inline-block text-truncate"><span>{$tr['GTOKEN']}：</span>{$gtoken_balance}</p>
				<p id="sider-token-status" class="sider-member-txt d-inline-block text-truncate">
				{$statusHtml}
				</p>
			</div>
		</div>
		<script>
		  $(function () {
		  	$('[data-toggle="tooltip"]').tooltip();
		  });
		</script>
	HTML;
	}

	if($config['site_style']=='mobile'){
		$html = <<<HTML
		<div class="container cuustom_mb_5">
		<div class="row deposit_header deposit_content border-bottom">
			<div class="col-5 deposit_p">
				<p class="d-inline-block text-truncate" data-toggle="tooltip" data-placement="top" title="{$_SESSION['member']->account}"><span>{$tr['Account']}：</span>{$_SESSION['member']->account}</p>
			</div>
			<div class="col-7 pl-0 deposit_p">
				{$sider_money_info}
			</div>
		</div>
			{$mobile_gcash_info}
		</div>
		<script>
		  $(function () {
		  $('[data-toggle="popover"]').popover();
		  $('.popover-dismiss').popover({
		  trigger: 'focus'
		  });
		  });
		</script>
	HTML;
	}else{
		$html = <<<HTML
		<div id="sider-deposit-center">
			<div class="sider-roleicon"><span class="roleicon-circle">{$therole_icon_html}</span></div>
			<div class="sider-member-infobox">
				<p class="sider-member-txt"><span>{$tr['Account']}</span>：{$_SESSION['member']->account}</p>
				{$sider_gcash_info}
				<p class="sider-member-txt"><span>{$tr['GTOKEN']}</span>：{$gtoken_balance}</p>
			</div>
			<div id="sider-token-status" class="sider-member-txt p-0 mb-3">
				{$statusHtml}
			</div>
			<ul class="sidebar_menu">
			$sider_menu_list
			</ul>
		</div>
	HTML;
	}
}
	return $html;
}

function combineGetCasinoBalanceJs()
{
	global $tr;
	global $cdnrooturl;

	$confirm_text = $tr['confirm get all casino back'];

	$js = <<<JS
	<script>
	$(document).on('click', '#gtokenrecycling', function() {
		var gtokenrecycling = 1;

		if(confirm('{$confirm_text}')){
			$('#gtokenrecycling').attr('disabled', 'disabled');

			var wait_text = `{$tr['running please wait']}<img style="width:30px;height:30px;" src="{$cdnrooturl}loading_balls.gif">`;

			$('#gtoken_status').html(wait_text);

			$.get('gamelobby_action.php',
					{ a: 'Retrieve_Casino_balance' },
					function(result){
						if(result.logger){
							$('#gtoken_status').html(result.logger);
							$('#gtoken_b').html(result.gtoken_b);
							$('#total_b strong').html(result.total_b);
							$('#reload_balance_area').html(result.gtoken_b_m);
							$('#gtokenrecycling_balance').hide();

							setTimeout('window.location.reload()',1000);
						}else{
							$('#gtoken_status').html(result);
						}
					}, 'JSON'
			);
		}else{
			//放棄,取回所有娛樂城的餘額!!
			alert('{$tr['giveup get all casino back']}');
		}
	});
	</script>
JS;

	return $js;
}

function combineManualGcashToGtokenJs($id)
{
  global $tr;
  global $csrftoken;
//转帐完成，是否前往查看
  $js = <<<JS
  <script>
  $(document).on('click', '#manual_gcashtogtoken_btn', function() {
		var manual_amount = $('#manual_gcashtogtoken').val();
		var message = '{$tr['Identify from cash wallet, stored value']}'+manual_amount+'{$tr['To game currency']}';
		var csrftoken = '{$csrftoken}';

		if(jQuery.trim(manual_amount) != '') {
			if(manual_amount != '{$tr['select deposit amount']}') {
				if(confirm(message)) {
					$.ajax({
						type: 'POST',
						url: 'wallets_action.php?a=manual_gcashtogtoken',
						data: {
							manual_amount: manual_amount,
							csrftoken : csrftoken
						},
						success: function(resp) {
							var res = JSON.parse(resp);

							if (res.status == 'success') {
                if (confirm('{$tr['Transfer completed']}') == true) {
                    window.location.href = `./mo_translog_query.php?transpage=m&id={$id}&tid=` + res.result.tid;
                } else {
                    location.reload();
                }
							} else {
									alert(res.result);
							}
						}
					});
				} else {
					window.location.reload();
				}
			}
		} else {
			alert('{$tr['Illegal test']}');
		}
  });
  </script>
JS;

  return $js;
}
