<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- page html 樣本頁面, 給靜態頁面的樣板
// File Name:	page.tmpl.php
// Author:		orange
// Related:		page.php
// Log:
// 2019.04.11
// ----------------------------------------------------------------------------
// 功能標題，放在標題列及meta
$function_title 		= '財務中心';

if (!isset($_SESSION['member']) OR $_SESSION['member']->therole == 'T') {
	die('<script>location.href ="'.$config['website_baseurl'].'login2page.php";</script>');
}

// 導覽列
$navigational_hierarchy_html = '
<ul class="breadcrumb">
  <li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
  <li class="active">'.$function_title.'</li>
</ul>
';
if($config['site_style']=='mobile'){
	$navigational_hierarchy_html =<<<HTML
		<a href="{$config['website_baseurl']}home.php"><i class="fas fa-chevron-left" aria-hidden="true"></i></a>
		<span>{$function_title}</span>
		<i></i>
HTML;
}

$user_account = password_hash($_SESSION['member']->account, PASSWORD_DEFAULT);

$deposit_center=<<<HTML
<div class="row mt-3">
	<div class="col-6 text-center">
		<a class="text-primary h5" href="{$config['website_baseurl']}deposit.php">
			<img src="{$cdnfullurl}img/others/depositcompany.png">
			公司入款
		</a>
	</div>
	<div class="col-6 text-center">
		<a class="text-primary h5" href="{$config['website_baseurl']}wallets.php">
			<img src="{$cdnfullurl}img/others/wallets.png">
			線上取款
		</a>
	</div>
	<div class="col-6 text-center">
		<a class="text-primary h5" href="{$config['website_baseurl']}moa_betlog.php?betpage=m&id={$_SESSION['member']->id}">
			<img src="{$cdnfullurl}img/others/transaction_log.png">
			投注紀錄
		</a>
	</div>
	<div class="col-6 text-center">
		<a class="text-primary h5" href="{$config['website_baseurl']}mo_translog_query.php?transpage=m&id={$_SESSION['member']->id}">
			<img src="{$cdnfullurl}img/others/transaction_log.png">
			交易紀錄
		</a>
	</div>
</div>
HTML;



$indexbody_content	.= $deposit_center;

?>