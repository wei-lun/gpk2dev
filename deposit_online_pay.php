<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 線上存款功能 -- B 線上支付
// File Name:	deposit_online_pay.php 線上存款的功能選擇
// Author:		Barkley, Yuan
// Related: 	deposit.php 線上存款的頁面
// Log:
// 只有登入的會員才可以看到這個功能。 且需要會員等級有達到才可以開放。
// 2017.7.24
// ----------------------------------------------------------------------------
/*
 * 相關的資料庫及變數說明
DB Table :
root_protalsetting : 後台 - 會員端設定
root_member : 會員資料
root_member_grade : 後台 - 會員等級設定

 * 前後台都有 DB table
root_deposit_onlinepayment 線上支付入款商資訊
root_deposit_onlinepay_summons 線上支付記帳支付傳票

相關檔案 - 前台
deposit_online_pay.php - 線上存款功能 -- B 線上支付
deposit_online_pay_action.php  處理線上存款的動作

相關檔案 - 後台

 */



// 主機及資料庫設定
require_once dirname(__FILE__) . "/config.php";
// 支援多國語系
require_once dirname(__FILE__) . "/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) . "/lib.php";

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
// $tr['online momey'] = '线上支付';
$function_title = $tr['online momey'];
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
  <li><a href="deposit.php">' . $tr['deposit title'] . '</a></li>
  <li class="active">' . $function_title . '</li>
</ul>
';
// ----------------------------------------------------------------------------

/**
 * [check_member_data_completeness 檢查會員資料是否完整]
 * @param  [integer]  $member_id [會員id]
 * @return boolean
 */
function check_member_data_completeness($member_id) {
	$sql = "SELECT * FROM root_member WHERE id = '$member_id' ";
	$result = runSQLall($sql);

	if ($result[0] <= 0) {
		return false;
	}

	$member_data = $result[1];

	//銀行帳號*4欄、姓名
	if (empty($member_data->bankaccount)
		|| empty($member_data->bankname)
		|| empty($member_data->bankprovince)
		|| empty($member_data->bankcounty)
		|| empty($member_data->bankcounty)
		|| empty($member_data->realname)
		|| empty($member_data->nickname)
	) {
		return false;
	}

	//聯絡方式 4 選 2
	$contact_info_count = 0;
	$contact_attributes = ['email', 'mobilenumber', 'wechat', 'qq'];

	foreach ($contact_attributes as $contact_attribute) {
		if (!empty($member_data->$contact_attribute)) {
			$contact_info_count++;
		}
	}

	if ($contact_info_count < 2) {
		return false;
	}

	//會員資料完整
	return true;
}

// ----------------------------------------------------------------------------
// 顯示可以選擇的線上支付業者 , 條件為符合 grade 的使用者
// choice_onlinepay_service($grade)
// 參數：$grade 搜尋等級為 $grade 的
// 輸出：
// ----------------------------------------------------------------------------
function choice_onlinepay_service($grade) {
	// 這段 sql 在 php sql 上面不能跑 , 在 adminer 可以跑. 需要在找原因
	//$sql = "SELECT * FROM root_deposit_onlinepayment WHERE status = 1 AND (grade ? '$grade');";
	//$sql = "SELECT * FROM root_deposit_onlinepayment WHERE status = 1;";
	$sql = "SELECT * FROM root_deposit_onlinepayment WHERE status = 1 AND (grade  ->> '$grade' >= '1')";
	//var_dump($sql);
	$r = runSQLall($sql);

	if ($r[0] > 0) {
		$payitem = '';
		for ($i = 1; $i <= $r[0]; $i++) {
			//$grade_json = json_decode($r[$i]->grade);
			//var_dump($grade_json);
			//var_dump(in_array($grade, $grade_json, true));
			$payitem = $payitem . '<p><label><input type="radio" name=onlinepaymentid" id="onlinepaymentid" value="' . $r[$i]->id . '">&nbsp;' . $r[$i]->payname . '</label>';
		}
	} else {
		// $tr['There is no online payment'] = '沒有可以使用的線上支付';
		$payitem = $tr['There is no online payment'];
	}
	$output_html = $payitem;

	return ($output_html);
}
// ----------------------------------------------------------------------------

//*********************** if (isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T') {
if (isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T') {

	// 客服聯絡資訊
	$showtext_html = '
	<p style="line-height: 175%;  letter-spacing: 1px; font-size: small;">
	客服資訊：<br></p>
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
	// ----------------------------------------------------------------------------

	// 公司入款功能目前關閉或維護中
	// $tr['Online withdrawal is closed'] = '線上取款-公司入款功能目前關閉或維護中，如有任何疑問，可透過下列任一方式與客服人員聯繫。';
	$companydeposit_offline_desc_html = '
	<p class="bg-danger">
	<br>
	&nbsp;&nbsp;&nbsp;&nbsp;' . $tr['Online withdrawal is closed'] . '<br>
	<br>
	</p>
	';

	$warning_message_html = $companydeposit_offline_desc_html . '<hr>' . $showtext_html;
	// ----------------------------------------------------------------------------

	//var_dump($_GET);
	//var_dump($_POST);

	// ---------------------------------------------------------------------------
	// STEP 3 -- 查詢單號進入 -- 給 returnurl 使用
	// ---------------------------------------------------------------------------
	if (isset($_GET['m']) && isset($_SESSION['member']->account)) {
		$step3_result = '';

		$salt = $_SESSION['member']->salt;

		$codevalue = jwtdec($salt, $_GET['m'], $debug = 0);

		//驗證有無被修改 -- 單號
		$merchantorderid = $codevalue->MerchantOrderNo;
		// 拆開驗證
		$mid = explode("at", $codevalue->MerchantOrderNo);
		$mid[1] = filter_var($mid[1], FILTER_VALIDATE_INT);
		// fingerprint
		$fingerprinter = filter_var($codevalue->fingerprint, FILTER_VALIDATE_INT);
		// 已經入款的 amt 金額
		$amt = round($codevalue->Amt, 0);
		// $tr['STEP 1 Input the deposit amount'] = 'STEP 1 填入存款金額';
		// $tr['STEP 2 Select Online Payment'] = 'STEP 2 選擇線上支付';
		// $tr['STEP 3 transaction results'] = 'STEP 3 交易結果';
		$desposit_step_html = '
    <div class="btn-group" role="group" aria-label="">
      <a href="' . $_SERVER['PHP_SELF'] . '" class="btn btn-default">' . $tr['STEP 1 Input the deposit amount'] . '</a>
      <a href="#" class="btn btn-default">' . $tr['STEP 2 Select Online Payment'] . '</a>
      <a href="#" class="btn btn-primary">' . $tr['STEP 3 transaction results'] . '</a>
    </div>
    ';
		// 判斷資料正確
		//if($mid[0] == $config['projectid'] && $mid[1] !== false AND $fingerprinter != false AND $amt != false){
		// 如果稽核碼一樣的話, 表示資料沒有被修改過
		if ($mid[1] !== false AND $fingerprinter != false AND $amt != false) {
			// 檢查指定的單號、指紋碼、金額是否正確
			$search_sql = "SELECT * FROM root_deposit_onlinepay_summons WHERE merchantorderid ='" . $merchantorderid . "' AND fingerprinting = '" . $fingerprinter . "' AND amount = '" . $amt . "' AND account='" . $_SESSION['member']->account . "';";
			//var_dump($search_sql);
			$search_sql_result = runSQLall($search_sql);
			//var_dump($search_sql_result);

			//應該只有唯一一筆紀錄，否則是哪邊出錯了
			if ($search_sql_result[0] == 1) {

				// 把回傳資料放在 console 方便除錯 , product 要關閉
				$search_orderno_json = json_encode($search_sql_result[1]);
				$search_orderno_json_html = '
          <script>
            console.log(\'' . $search_orderno_json . '\');
          </script>
          ';

				// 入款狀態 , 參考資料表設計
				// [處理]NULL=尚未入款0=入款失敗1=入款手動確認2=自動確認
				$online_payment_status['Not yet paid'] = '尚未存款';
				//$online_payment_status[NULL] 	= '尚未入款';
				$online_payment_status[0] = '存款失敗';
				$online_payment_status[1] = '存款手動確認';
				$online_payment_status[2] = '自動確認';

				// 付款狀態 , NULL 特別處理
				//var_dump($search_sql_result[1]->status);
				if (is_null($search_sql_result[1]->status)) {
					$online_payment_status_html = '<button type="button" class="btn btn-default btn-sm">' . $online_payment_status['Not yet paid'] . '</button>';
				} elseif ($search_sql_result[1]->status == 0) {
					$online_payment_status_html = '<button type="button" class="btn btn-warning btn-sm">' . $online_payment_status[0] . '</button>';
				} else {
					$online_payment_status_html = '<button type="button" class="btn btn-info btn-sm">' . $online_payment_status[$search_sql_result[1]->status] . '</button>';
				}
				//var_dump($online_payment_status_html);

				//顯示表格
				$show_result_table = '
          <table class="table table-bordered">
          <caption><strong>線上支付結果</strong></caption>
          <thead>
            <tr class="info">
              <td>存款單單號</td>
              <td>交易金額</td>
              <td>交易時間</td>
              <td>處理狀態</td>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>' . $search_sql_result[1]->merchantorderid . '</td>
              <td>' . money_format('%i', $search_sql_result[1]->amount) . '</td>
              <td>' . $search_sql_result[1]->transfertime . '</td>
              <td>' . $online_payment_status_html . '</td>
            </tr>
          </tbody>
          </table>
          ';
				$step3_result = $show_result_table . $search_orderno_json_html;

				$payment_info_message = '
            <div class="alert alert-warning" role="alert">
            如果处理状态为"尚未存款",请在此画面等待更新。<br>
            如果5分钟后处理状态还未更新为"自动确认",请联络客服。
            </div>
          ';

				$reload_js = '';
				if (is_null($search_sql_result[1]->status)) {
					$reload_js = '
            <script>
            setTimeout(function(){
              location.reload()
            }, 10000)
            </script>
            ';
				}

				$next_step = $payment_info_message . $reload_js . '<hr><p align="right"><a href="deposit_online_pay.php" class="btn btn-success">返回</a></p>';
			} else {
				//查到別人的單或是單號錯誤
				$next_step = '<p align="center"><a href="deposit_online_pay.php" class="btn btn-danger">没有此笔单号</a></p>';
			}
		} else {
			//網站代碼錯誤 或 訂單號碼非純數字
			$next_step = '<p align="center"><a href="deposit_online_pay.php" class="btn btn-danger">没有此笔单号或是连结不正确。</a></p>';
		}

		//產生檢查入款的表單
		// <form id="step1_amount_input" method="post">
		// </form>
		$select_step_html = $step3_result . '' . $next_step;

		//顯示在外
		$form_html = '<hr>' . $select_step_html . '<hr>';

		// ---------------------------------------------------------------------------
		// 查詢 STEP 3 結束
		// ---------------------------------------------------------------------------
	} else {

		// ---------------------------------------------------------------------------
		// 取得公司線上入款功能是否開啟設定參數
		// 這參數影響整個網站, 無論會員等級中公司入款功能設定開啟或關閉 systemconfig.php
		$form_html = '';
		// if (! is_null($_SESSION['member']->gtoken_lock)) {
		// 	// die('錢包鎖住，請先取回餘額');
		// 	$retrieve_gtoken_hint = '您的钱包状态为<button class="btn btn-danger">锁定在'. $_SESSION['member']->gtoken_lock .'娱乐城</button>，请先取回娱乐城馀额后，再使用线上支付操作，以确保您的权益不会受损。';
		// 	$goto_next = '<a href="wallets.php" class="btn btn-success">' . $tr['next step'] . '</a>';
    //
		// 	$desposit_step_html = '
		// 	<div class="btn-group" role="group" aria-label="">
		// 		<a href="' . $_SERVER['PHP_SELF'] . '" class="btn btn-primary">' . '系统提示' . '</a>
		// 		<a href="wallets.php" class="btn btn-default">' . '前往' . $tr['wallets title'] . '</a>
		// 	</div>
		// 	<hr>
		// 	<p>%s</p>
		// 	<p align="right">%s</p>
		// 	<hr>';
		// 	$desposit_step_html = sprintf($desposit_step_html, $retrieve_gtoken_hint, $goto_next);
    //
		// } elseif ($protalsetting['companydeposit_switch'] == 'on') {
    if ($protalsetting['companydeposit_switch'] == 'on') {

			// 根據會員的會員等級取得相關設定參數
			//$onlinedeposit_status_select_sql = "SELECT * FROM root_member_grade WHERE gradename = '".$mamber_favorablerule_sql_result[1]->favorablerule."'";
			$onlinedeposit_status_select_sql = "SELECT * FROM root_member_grade WHERE id = '" . $_SESSION['member']->grade . "';";
			//var_dump($onlinedeposit_status_select_sql);
			$onlinedeposit_status_select_sql_result = runSQLall($onlinedeposit_status_select_sql);
			//var_dump($onlinedeposit_status_select_sql_result);
			// 這個變數要放在 systemconfig 內
			if ($onlinedeposit_status_select_sql_result[0] == 1) {
				$root_member_grade = $onlinedeposit_status_select_sql_result[1];
				//var_dump($root_member_grade);
			} else {
				$logger = '取得会员等级设定错误, 请联络客服人员处理。';
				die($logger);
			}
			// ----

			//(1=允許,0=關閉,2=維護)允許線上支付入款儲值
			if ($root_member_grade->onlinepayment_allow == 1) {

				// $is_member_data_complete = check_member_data_completeness($_SESSION['member']->id);

				// $disabled_attribute = 'disabled';
				// // $tr['member info'] = '会员资料';
				// $please_complete_member_data_alert_html = '
        //   <br>
        //   <div class="alert alert-warning" role="alert">
        //     请补齐您的<a href="https://' . $config['website_domainname'] . '/member.php">' . $tr['member info'] . '</a>以使用此功能<br>
        //     (银行帐号*4栏、姓名及联络方式 4 选 2)
        //   </div>
        //   ';
				// if ($is_member_data_complete) {
				// 	$disabled_attribute = '';
				// 	$please_complete_member_data_alert_html = '';
				// }
				$disabled_attribute = '';
				$please_complete_member_data_alert_html = '';

				// -------
				// STEP 1
				// -------
				// $tr['STEP 1 Input the deposit amount'] = 'STEP 1 填入存款金額';
				// $tr['STEP 2 Select Online Payment'] = 'STEP 2 選擇線上支付';
				// $tr['STEP 3 transaction results'] = 'STEP 3 交易結果';
				$desposit_step_html = '
          <div class="btn-group" role="group" aria-label="">
            <a href="' . $_SERVER['PHP_SELF'] . '" class="btn btn-primary">' . $tr['STEP 1 Input the deposit amount'] . '</a>
            <a href="#" class="btn btn-default">' . $tr['STEP 2 Select Online Payment'] . '</a>
            <a href="#" class="btn btn-default">' . $tr['STEP 3 transaction results'] . '</a>
          </div>
          ';
				// $tr['deposit amount'] = '存入金額';
				$deposit_amount_input = '
          <div class="form-inline">
            ' . $tr['deposit amount'] . '：
            <input
              type="text"
              class="form-control"
              ' . $disabled_attribute . '
              name="deposit_input_amount"
              placeholder="' . $root_member_grade->onlinepaymentlimits_lower . ' ~ ' . $root_member_grade->onlinepaymentlimits_upper . '"
            >
          </div>';

				//下一步
				$goto_step2 = '<p align="right"><button type="submit" class="btn btn-success" form="step1_amount_input" ' . $disabled_attribute . '>' . $tr['next step'] . '</button></p>';

				//產生表單
				$select_step_html = '
          <form id="step1_amount_input" method="post">
            ' . $deposit_amount_input . '
            ' . $please_complete_member_data_alert_html . '
            ' . $goto_step2 . '
          </form>';

				//顯示在外
				$form_html = '<hr>' . $select_step_html . '<hr>';

				// -------
				// STEP 2
				// -------
				// ------------------------------------------------------------------------------------------------------
				//在開始STEP2之前 要檢查入款金額是否在容許範圍內 以及是否為數字
				if (isset($_POST['deposit_input_amount']) AND filter_var($_POST['deposit_input_amount'], FILTER_VALIDATE_INT)
					AND $_POST['deposit_input_amount'] <= $root_member_grade->onlinepaymentlimits_upper
					AND $_POST['deposit_input_amount'] >= $root_member_grade->onlinepaymentlimits_lower) {

					// 狀態列顯示
					// $tr['STEP 1 Input the deposit amount'] = 'STEP 1 填入存款金額';
					// $tr['STEP 2 Select Online Payment'] = 'STEP 2 選擇線上支付';
					// $tr['STEP 3 transaction results'] = 'STEP 3 交易結果';   //
					$desposit_step_html = '
            <div class="btn-group" role="group" aria-label="">
              <a href="' . $_SERVER['PHP_SELF'] . '" class="btn btn-default">' . $tr['STEP 1 Input the deposit amount'] . '</a>
              <a href="#" class="btn btn-primary">' . $tr['STEP 2 Select Online Payment'] . '</a>
              <a href="#" class="btn btn-default">' . $tr['STEP 3 transaction results'] . '</a>
            </div>
            ';

					// 產生選擇線上支付的 html , 傳入目前使用者的等級.
					$online_payment_html = choice_onlinepay_service($onlinedeposit_status_select_sql_result[1]->gradename);

					//上一步 下一步
					$goto_step2 = '<p align="right">
            <a href="' . $_SERVER['PHP_SELF'] . '" class="btn btn-info">' . $tr['pervious step'] . '</a>&nbsp;&nbsp;
            <button class="btn btn-success" id="step2_amount_input">' . $tr['next step'] . '</button></p>';

					//產生表單 , and 回傳結果動作
					$select_step_html = '
              ' . $online_payment_html . '
              <input name="deposit_input_amount" type="hidden" id="deposit_input_amount" value="' . $_POST['deposit_input_amount'] . '">
              ' . $goto_step2 . '
              <div id="show_result"></div>

            ';

					//顯示在外
					$form_html = '<hr>' . $select_step_html . '<hr>';

					//ajax 傳送參數 並接收回傳的表單 -> 第三方支付
					$extend_js = $extend_js . '
              <script>
              $(document).ready(function(){
                  $(\'#step2_amount_input\').click(function() {
                    $(\'#show_result\').html(\'處理中...\');
                     // 使用 ajax 送出 post
                     var deposit_amount   = $(\'#deposit_input_amount\').val();
                     var onlinepaymentid  = $(\'#onlinepaymentid:checked\').val();
                     $.ajax ({
                			 url: "deposit_online_pay_action.php?a=goto_onlinepayment",
                			 type: "POST",
                			 data: ({deposit_amount: deposit_amount, onlinepaymentid: onlinepaymentid}),
                       success: function(response_data){
                			   console.log(response_data);
                         $(\'#show_result\').html(response_data);
                       },
                       error: function (errorinfo) {
                         console.log(errorinfo);
                       },
                			});
                  });
              });
              </script>';

				} elseif (isset($_POST['deposit_input_amount'])) {
					// $tr['Please Input the amount within the specified range'] = '請輸入規定範圍內的金額';
					$form_html = $form_html . '<div class="alert alert-warning" role="alert">' . $tr['Please Input the amount within the specified range'] . '</div>';
				}

			} else {
				// 線上支付，目前全站關閉.
				$desposit_step_html = $warning_message_html;
			}

		} else {
			//(x) ERROR 413 資料查詢錯誤，請聯絡客服人員協助處理。
			$desposit_step_html = '';
			$logger = $tr['error 413'];
			$form_html = 'div class="alert alert-warning" role="alert">' . $logger . '</div>' . $showtext_html;
		}
	}

// ----------
} else {
// ----------
	// 不合法登入者的顯示訊息
	//(x) 請先登入會員，才可以使用此功能。
	$desposit_step_html = '';
	$logger = $tr['login first'];
	$form_html = '<div class="alert alert-warning" role="alert">' . $logger . '</div>';
}
// ---------- end session login check

// 切成 3 欄版面
$indexbody_content = $indexbody_content . '
<div class="row">
	<div class="col-12 col-md-1">
	</div>
	<div class="col-12 col-md-10">
	' . $desposit_step_html . '
	</div>
	<div class="col-12 col-md-1">
	</div>
</div>
<br>
';

$indexbody_content = $indexbody_content . '
<div class="row">
	<div class="col-12 col-md-1">
	</div>
	<div class="col-12 col-md-10">
	' . $form_html . '
	</div>
	<div class="col-12 col-md-1">
	</div>
</div>
<br>
';

$indexbody_content = $indexbody_content . '
<div class="row">
	<div class="col-12 col-md-1">
	</div>
	<div class="col-12 col-md-10">
		<div id="preview"></div>
	</div>
	<div class="col-12 col-md-1">
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

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include $config['template_path'] . "template/admin.tmpl.php";

?>
