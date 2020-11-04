<?php
// ----------------------------------------------------------------------------
// Features:  前端 -- 線上存款功能 index 索引頁
// File Name:  deposit.php
// Author:    Barkley.Yuan
// Related:
// Log:
// 只有登入的會員才可以看到這個功能。
// 2016.10.18
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
require_once dirname(__FILE__) . "/config.php";
// 支援多國語系
require_once dirname(__FILE__) . "/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) . "/lib.php";
// 自訂；有用到 api 相關
require_once dirname(__FILE__) . "/site_api/lib_api.php";

require_once dirname(__FILE__) . "/lib_wallets.php";

// var_dump($_SESSION);

// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
//线上存款功能
$function_title = $tr['membercenter_exchange_token'];
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
  <li class="active">' . $function_title . '</li>
</ul>
';
if($config['site_style']=='mobile'){
  $navigational_hierarchy_html =<<<HTML
    <a href="{$config['website_baseurl']}menu_admin.php?gid=deposit"><i class="fas fa-chevron-left"></i></a>
    <span>$function_title</span>
    <i></i>
HTML;
}

// ----------------------------------------------------------------------------
// $tr['If you have any questions about the use of this website, you can contact the customer service in any of the following ways'] = '如果您對本網站的使用有任何疑問，可以透過下列任一方式與客服人員聯繫：';
// $tr['online customer service'] = '在線客服';
// $tr['customer service'] = '客服人員';

// 有會員資料 不論身份 therole 為何，都可以修改個人資料。但是除了 therole = T 除外。
if (isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T') {

  // 客服聯絡資訊
  $showtext_html = '
	<p style="line-height: 175%;  letter-spacing: 1px; font-size: small;">
	' . $tr['If you have any questions about the use of this website, you can contact the customer service in any of the following ways'] . '<br></p>
	<p>(1)點擊<a href="http://' . $customer_service_cofnig['online_weblink'] . '">' . $tr['online customer service'] . '</a>連結，即可進入在線客服系統與客服人員聯繫。 </p>
	<p>(2)您亦可使用下列聯絡方式與客服人員取得聯繫：<br></p>
	<p class="bg-info">
	<br>
	&nbsp;&nbsp;&nbsp;&nbsp;' . $tr['customer service'] . ' Email：' . $customer_service_cofnig['email'] . '<br>
	&nbsp;&nbsp;&nbsp;&nbsp;' . $tr['customer service'] . ' QQ：' . $customer_service_cofnig['qq'] . '<br>
	&nbsp;&nbsp;&nbsp;&nbsp;' . $tr['customer service'] . ' Mobile TEL：' . $customer_service_cofnig['mobile_tel'] . '<br>
	<br>
	</p>
	<br><hr>';

	$api_config = SiteApiConfig::readByMember(['grade' => $_SESSION['member']->grade]);
	$api_configs = SiteApiConfig::readAvailableByGrade($_SESSION['member']->grade, 'deposit');
	$appid_list = implode(',', array_column($api_configs, 'api_account'));

	// var_dump($_SESSION);
	// var_dump($api_config);
	// var_dump($apifastpay_sign);

  $deposit_allow = $member_grade_config_detail->deposit_allow;
  // $onlinepayment_allow = $member_grade_config_detail->onlinepayment_allow;
  $onlinepayment_allow = 0;
  $apifastpay_allow = !is_null($api_config) ? $member_grade_config_detail->apifastpay_allow : 0;

  if ($protalsetting['companydeposit_switch'] == 'on') {

	$manualGcashToGtokenHtml = combineManualGcashToGtokenHtml();
	$casinoBalanceHtml = combineGetCasinoBalanceHtml('exchange_token');

	//新增入款連結
	function moreadd_deposit($title,$content,$link){
		if (!preg_match("/http/i", $link))
			$link='//'.$link;
			$moreadd_deposit =<<<HTML
			<table class="table table-bordered">
			<tr class="active">
				<td><span class="title-index label label-default mr-2"></span><strong>{$title}</strong></td>
			</tr>
			<tr>
				<td>{$content}
				<hr>
				<p align="right">
				<a  href="{$link}" target="_blank" class="btn btn-success">
					<span class="glyphicon glyphicon-log-in" aria-hidden="true"></span>&nbsp;{$title}</a>
				</p>
				</td>
			</tr>
		</table>
		<hr>
HTML;
		return $moreadd_deposit;
	}

	//餘額不足提示
	$messages_deposit = '<div class="row justify-content-md-center">
		<div class="col-12">
			<div id="preview" class="deposit_preview"></div>
		</div>
	</div>';

	//$form_html = $manualGcashToGtokenHtml.$messages_deposit;//.$form_html_s$casinoBalanceHtml.
  //$form_html = $casinoBalanceHtml.$form_html_s.$manualGcashToGtokenHtml;
	//$api_account = $api_config->api_account ?? null;
	// var_dump($api_config);
	// var_dump($_SESSION['member']);


		// --------------------------------
		// 代幣 GTOKEN 呈現
		// --------------------------------
		// 查詢來源帳號的資料 , 確認該帳號 status 還是有效的
		// 預設登入者本身
		$query_id = $_SESSION['member']->id;
		$sql = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.id = $query_id AND status = '1';";
		$g = runSQLALL($sql, 0, 'r');
		// 目前錢包所在哪裡？ NULL 等於沒有鎖定
		$gtoken_status_html = '';
		if ($g[1]->gtoken_lock == NULL OR $g[1]->gtoken_lock == '') {
			//代幣錢包未使用
			$gtoken_status_html = '<div class="text-center alert alert-warning mb-3 w-100"><span class="glyphicon glyphicon-lock" aria-hidden="true"></span>' . $tr['token wallets unused'].'</div>';
		} else {
			// 顯示轉移錢包的按鈕      //代幣正在 OOXX 娱乐城使用 回收代币
			//回收所有娛樂城的所有代幣到 GTOKEN 帳戶。
			$gtoken_status_html = '<div class="text-center alert alert-warning mb-3 w-100"><p class="h5 mb-0 mr-2">'.$tr['token now'] . '<span class="px-1 font-weight-bold">' . $tr[$g[1]->gtoken_lock.' Casino'] . '</span>' . $tr['casino used'] . ' ' .
				'</p></div>
				<button type="button" id="wallets-gtokenrecycling" class="btn btn-danger  mb-3 ml-3 w-25" data-toggle="gtokenrecycling" onclick="gtokenrecycling_balance()" title="' . $tr['all casino to gtoken'] . '" >' . $tr['recycle gtoken'] . '</button>';

			// ----------------------------------------------------------------
			// 回收代幣的 POST 動作處理 , 有需要才顯示.

			// 點擊圖片後，產生一個新視窗。因為 loading 及自動 API 流程需要時間，如果不用彈出視窗，會讓人覺的沒有在工作。
			// 傳入 gamecode , 產生 game 的 url 轉址。但因為還是需要時間，讓螢幕 full screen 避免使用者點擊。
			// ref: http://www.blooberry.com/indexdot/html/topics/windowopen.htm
			//	screen.availWidth, screen.availHeight
			// myWindow = window.open(gotogamecodeurl, gamecode, 'fullscreen=yes,status=yes,resizable=yes,top=0,left=0,height=600,width=800', false);
			/*$run_win_js = "
	    	var gotocodeurl='lobby_mggame_action.php?a=Retrieve_MG_Casino_balance';
	    	var wait_text = ".'\'<div style="width: 100%;		height: 100vh;		display: flex;		justify-content: center;		align-items: center;		overflow: hidden;">执行中，请勿关闭视窗<img src="'.$cdnrooturl.'loading_balls.gif"></div>\';'."
	    	myWindow = window.open('', 'Get_MGGAME_Balance', config='height=400,width=300');
	    	myWindow.document.write(wait_text);
	    	myWindow.moveTo(0,0);
	    	myWindow = window.open(gotocodeurl, 'Get_MGGAME_Balance', 'height=400,width=300', false);
	    	myWindow.focus();
*/

			//执行中，请稍侯...
			$run_win_js = "
    	var wait_text = " . '\'' . $tr['running please wait'] . '<img style="width: 30px;		height: 30px;" src="'.$cdnrooturl.'loading_balls.gif">\';' . "
    	$(\"#gtoken_status\").html(wait_text);
      $.get('gamelobby_action.php',
  	  		{ a: 'Retrieve_Casino_balance' },
  	  		function(result){
            if(result.logger){
    	        $(\"#gtoken_status\").html(result.logger);
      	      $(\"#gtoken_b\").html(result.gtoken_b);
      	      $(\"#total_b strong\").html(result.total_b);
      	      $(\"#reload_balance_area\").html(result.gtoken_b_m);
      	      $(\"#gtokenrecycling_balance\").hide();

              setTimeout('window.location.reload()',1000);
            }else{
    	        $(\"#gtoken_status\").html(result);
            }
  	      }, 'JSON'
  	  );
    	";

			// 確認要取回所有娛樂城的餘額？
			$confirm_text = $tr['confirm get all casino back'];
			$gtokenrecycling_js = "
        <script>
        		function gtokenrecycling_balance(){
        			var gtokenrecycling = 1;

        			if(confirm('" . $confirm_text . "')){
        				$('#gtokenrecycling').attr('disabled', 'disabled');
                " . $run_win_js . "
        			}else{
                //放棄,取回所有娛樂城的餘額!!
                alert('" . $tr['giveup get all casino back'] . "');
              }

        		}
        </script>
      ";
			$extend_js = $extend_js . $gtokenrecycling_js;

			// 在執行錢包轉移時，需要鎖定。避免重複的執行。
			// 執行完成後，把 $_SESSION['wallet_transfer'] 變數清除, 其他程式才可以進入。
			unset($_SESSION['wallet_transfer']);			
		}
// ----------------------------------------------------------------

if($config['site_style']=='desktop') {

	$form_html =<<<HTML
	<div class="row">
		<div>{$casinoBalanceHtml}</div>
		<div class="col">
			<div class="main_content deposit_center">
				{$manualGcashToGtokenHtml}{$messages_deposit}
			</div>
		</div>
	</div>	
HTML;
}else{
	$casinoBalanceHtml = combineGetCasinoBalanceHtml();
	$form_html =<<<HTML
	<div class="row">
  {$casinoBalanceHtml}
  </div>
	{$manualGcashToGtokenHtml}{$messages_deposit}
HTML;
}

	$extend_js .= <<<HTML
	<style>
	/*暫時補救*/
	.recharge_deposit{
		color: #000;
	}
	.loader {
			border: 16px solid #f3f3f3; /* Light grey */
			border-top: 16px solid #3498db; /* Blue */
			border-radius: 50%;
			width: 120px;
			height: 120px;
			animation: spin 2s linear infinite;
		left: calc(50% - 60px);
		position: relative;
	}

	@keyframes spin {
			0% { transform: rotate(0deg); }
			100% { transform: rotate(360deg); }
	}
	</style>
HTML;

		$extend_js .= combineManualGcashToGtokenJs($_SESSION['member']->id);
		$extend_js .= combineGetCasinoBalanceJs();

  } else {
    $companydeposit_offline_desc_html = '
		<p class="bg-danger">
		<br>
		&nbsp;&nbsp;&nbsp;&nbsp;' . $protalsetting['companydeposit_offline_desc'] . '<br>
		<br>
		</p>
		';

    $form_html = $companydeposit_offline_desc_html . '<hr>' . $showtext_html;
  }

  // 如果兩各如款方式都被關閉的話
  // $tr['There is no available payment method,please contact customer service.'] = '目前沒有可用的入款方式，如須入款請聯繫客服人員處理。';
  if ($deposit_allow == 0 and $onlinepayment_allow == 0 and $apifastpay_allow == 0) {
	$form_html =<<<HTML
	<div class="row">
		<div>{$casinoBalanceHtml}</div>
		<div class="col">
			<div class="main_content deposit_center">
				<p class="no_available">{$tr['There is no available payment method,please contact customer service.']}</p>
			</div>
		</div>
	</div>	
HTML;
  }

} else {
  // 不合法登入者的顯示訊息 (x) 請先登入會員，才可以使用此功能。
  $form_html = $tr['login first'];
}



// 切成 3 欄版面
$indexbody_content = $indexbody_content . '
<div class="row justify-content-md-center">
	<div class="col-12">
	'.$form_html.'
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
$tmpl['sidebar_content'] =['deposit','exchange_token'];
// banner標題
$tmpl['banner'] = ['membercenter_menu_admin_deposit'];
// menu增加active
$tmpl['menu_active'] =['deposit.php'];
// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include $config['template_path'] . "template/admin.tmpl.php";

?>
