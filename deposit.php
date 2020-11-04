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

// uisetting函式庫
require_once dirname(__FILE__) . "/lib_uisetting.php";

// 線上金流 pay api
require_once __DIR__ . '/lib_errorcode.php';

use Onlinepay\SDK\PaymentGateway;

// TODO: 整合 test/release 模式到 API
$pay_config = $config['gpk2_pay'] + ['system_mode' => PaymentGateway::SYS_MODE_TEST, 'debug' => 1];
$config['payment_mode'] == PaymentGateway::SYS_MODE_PROD and $pay_config = $config['gpk2_pay'] + ['system_mode' => PaymentGateway::SYS_MODE_PROD];

$onlinepayGateway = new PaymentGateway($pay_config);
$apiEntry = $config['gpk2_pay']['apiHost'] ?? null;
$apiEntry and $onlinepayGateway->setApiEntry($config['gpk2_pay']['apiHost'] . '/api');
$onlinepayGateway->lang = $_SESSION['lang'] ?? 'zh-cn';

// 頁面上的各個服務狀態
$compinfo = [
    'onlinepay' => ['code' => 0, 'desc' => 'success'],
];

try {
    $service_list = $onlinepayGateway->getServiceList()->data;
    $currency = $onlinepayGateway->getAgentInfo()->data->currency_info->codename;
} catch (Throwable $e) {
    $debug_fmt = "%s (%s): %s";
    $compinfo['onlinepay'] = [
        'code' => ErrorCode::CURL_EXCEPTION,
        'desc' => "{$tr['system info error']}({$tr['online momey']})"
    ];
    error_log(sprintf($debug_fmt, $_SERVER['SCRIPT_NAME'], date('c'), $e->getMessage()));
}

// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
//线上存款功能
$function_title = $tr['membercenter_deposit'];

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
$navigational_hierarchy_html = <<<HTML
    <ul class="breadcrumb">
        <li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
        <li class="active">{$function_title}</li>
    </ul>
HTML;

if ($config['site_style'] == 'mobile') {
    $navigational_hierarchy_html = <<<HTML
        <a href="{$config['website_baseurl']}menu_admin.php?gid=deposit">
            <i class="fas fa-chevron-left"></i>
        </a>
        <span>$function_title</span>
        <i></i>
    HTML;
}
// ----------------------------------------------------------------------------
// 不合法登入者的顯示訊息 (x) 請先登入會員，才可以使用此功能。
if (!isset($_SESSION['member']) || $_SESSION['member']->therole == 'T') {
    echo <<<HTML
        <script>
            alert("{$tr['login first']}");
            location.href = "./login2page.php";
        </script>
    HTML;
    die();
}
// ----------------------------------------------------------------------------
// $tr['If you have any questions about the use of this website, you can contact the customer service in any of the following ways'] = '如果您對本網站的使用有任何疑問，可以透過下列任一方式與客服人員聯繫：';
// $tr['online customer service'] = '在線客服';
// $tr['customer service'] = '客服人員';

//是否有申請公司入款
function get_deposit_review_data( $acc ){
    global $no_deposit_result;

    $sql = <<<SQL
        SELECT *
        FROM root_deposit_review
        WHERE (account = '{$acc}') AND
              (status = 2)
        ORDER BY id
        DESC
    SQL;
    $result = runSQLall($sql);

    if (empty($result[0])) {
        $error_msg = $no_deposit_result;
        return array('status' => false, 'result' => $error_msg);
    }
    return array('status' => true, 'result' => $result);
} // end get_deposit_review_data

$deposit_review_data = (object) get_deposit_review_data($_SESSION['member']->account);
//公司入款處理狀態按鈕是否顯示
if (!$deposit_review_data->status) {
    $deposit_status_btn = '';
}
else {
    $deposit_status_btn = <<<HTML
        <a href="deposit_company_status.php" class="deposit_status_btn ml-auto bd-highlight">{$tr['process status']}</a>
    HTML;
}

//列表是否顯示入款幣別
function get_deposit_currency_html(){
    global $tr;
    global $protalsetting;
           $html = '';

    if ($protalsetting['member_deposit_currency_isshow'] == 'on') {
        $currency = ($protalsetting['member_deposit_currency'] == 'gtoken') ? $tr['GTOKEN'] : $tr['GCASH'];

        $html = <<<HTML
            <p>{$tr['deposit_currency']}：{$currency}</p>
        HTML;
    }

    return $html;
} // end get_deposit_currency_html

// 取得線上支付-付款方式
function queryPaymentMethods($service_list, $currency, $tr){
    $available_payment_methods = [];
    if(count($service_list) > 0){
        foreach($service_list as $key=>$val){
            if($val->status == 1){
                array_push($available_payment_methods, [
                    'codename' => $val->codename,
                    'title' => $val->title,
                    'min_amount' => ( (isset($val->allowed_amount->$currency->min) && ($val->allowed_amount->$currency->min != null)) ? $val->allowed_amount->$currency->min : 0 ),
                    'max_amount' => ( (isset($val->allowed_amount->$currency->max) && ($val->allowed_amount->$currency->max != null)) ? $val->allowed_amount->$currency->max : 0 ),
                    'placeholder'=> ( (isset($val->allowed_amount->$currency->min) && ($val->allowed_amount->$currency->min != null) && isset($val->allowed_amount->$currency->max) && ($val->allowed_amount->$currency->max != null)) ? ( isset($tr['Fast pay amount notice']) ? sprintf($tr['Fast pay amount notice'], $val->allowed_amount->$currency->min, $val->allowed_amount->$currency->max) : 'Please enter interger amount between '.$val->allowed_amount->$currency->min.' and '.$val->allowed_amount->$currency->max ) : '' ),
                    'min_amount_msg' => ( (isset($val->allowed_amount->$currency->min) && ($val->allowed_amount->$currency->min != null)) ? ( isset($tr['deposit min amount']) ? sprintf($tr['deposit min amount'], $val->allowed_amount->$currency->min) : 'the min amount of deposit is '.$val->allowed_amount->$currency->min ) : '' ),
                    'max_amount_msg' => ( (isset($val->allowed_amount->$currency->max) && ($val->allowed_amount->$currency->max != null)) ? ( isset($tr['deposit max amount']) ? sprintf($tr['deposit max amount'], $val->allowed_amount->$currency->max) : 'the min amount of deposit is '.$val->allowed_amount->$currency->max ) : '' )
                ]);
            }
        } // end outer foreach
    }
    return $available_payment_methods;
} // end queryPaymentMethods
//  echo '<pre>', var_dump( queryPaymentMethods($service_list, 'VND', $tr) ), '</pre>'; exit();

// 取得指定付款方式的金流商名單
function queryOrderPaymentMethodProvider($service_list, $codename){
    global $config;
    $providers = [];
    if(count($service_list) > 0){
        foreach($service_list as $val_outer){
            if( ($val_outer->status == 1) && ($val_outer->codename == $codename) ){
                if(count($val_outer->available_payment_methods) > 0){
                    foreach($val_outer->available_payment_methods as $val_inner){
                        if($val_inner->status == 1){
                            array_push($providers, [
                                'provider_code' => $val_inner->payment,
                                'provider_title'=> strcasecmp($config['payment_mode'], 'test') === 0 ? $val_inner->alias_provider : $val_inner->alias,
                                'is_bank_required' => $val_inner->is_bank_required,
                                'method_code' => $val_inner->codename,
                            ]);
                        }
                    } // end inner foreach
                }
            }
        } // end outer foreach
    }
    return json_encode($providers);
} // end queryOrderPaymentMethodProvider

/* 解析線上支付金流商銀行選項 */
function getPaymethodBanks($service_list): array
{
    $banks = [];
    foreach ($service_list as $service) {
        foreach ($service->available_payment_methods as $method) {
            $method->is_bank_required and $banks[$method->codename] = $method->support_banks;
        }
    }
    return $banks;
}

// 有會員資料 不論身份 therole 為何，都可以修改個人資料。但是除了 therole = T 除外。
if ( isset($_SESSION['member']) && ($_SESSION['member']->therole != 'T') ) {

 // 取得會員狀態
 $memberstatus_sql = 'SELECT status FROM "root_member" WHERE id = ' . $_SESSION['member']->id . ';';
 $memberstatus_result = runSQLall($memberstatus_sql, 0, 'r');
 $memberstatus = ($memberstatus_result['0'] == 1) ? $memberstatus_result['1']->status : '0';

	//客服資料
	//在線客服 $tr['online customer service']
	if( $customer_service_cofnig['online_weblink'] != '' ){
		$online_service = '
		<li>
			<a href="'.$customer_service_cofnig['online_weblink'].'">
			<div class="img_box">
				<i class="fas fa-comments"></i>
			</div>
			<div class="contacts_text_box">
				<p class="mb-0">'.$tr['online customer service'].'</p>
			</div>
			</a>
		</li>
		';
	}else{
		$online_service = '';
	}

	//客服信箱 $tr['customer service mail']
	if( $customer_service_cofnig['email'] != '' ){
		$service_mail = '
		<li>
			<a href="mailto:'.$customer_service_cofnig['email'].'">
			<div class="img_box">
				<i class="far fa-envelope"></i>
			</div>
			<div class="contacts_text_box">
				<p class="mb-0">'.$tr['customer service mail'].'</p>
				<p class="mb-0">'.$customer_service_cofnig['email'].'</p>
			</div>
			</a>
		</li>
		';
	}else{
		$service_mail = '';
	}
	//客服專線 $tr['customer service tel']
	if( $tr['customer service tel']  != '' ){
		$service_tel = '
		<li>
			<a href="tel:'.$customer_service_cofnig['mobile_tel'].'">
			<div class="img_box">
				<i class="fas fa-phone-volume"></i>
			</div>
			<div class="contacts_text_box">
				<p class="mb-0">'.$tr['customer service tel'].'</p>
				<p class="mb-0">'.$customer_service_cofnig['mobile_tel'].'</p>
			</div>
			</a>
		</li>
		';
	}else{
		$service_tel = '';
	}

		//qr1
	if( $customer_service_cofnig['contact_app_name'] != '' || $customer_service_cofnig['contact_app_id'] != '' || $customer_service_cofnig['qrcode_1'] != ''  ){
		if( $customer_service_cofnig['qrcode_1'] == '' ) {
			$qrcode_1_img = '';
		}else{
			$qrcode_1_img = '<img src="'.$customer_service_cofnig['qrcode_1'].'" alt="">';
		}
	$qrcode_1_html = '
	<li>
	<div class="img_box">
		'.$qrcode_1_img.'
		<i class="fas fa-qrcode"></i>
	</div>
	<div class="contacts_text_box">
		<p class="mb-0">'.$customer_service_cofnig['contact_app_name'].'</p>
		<p class="mb-0">'.$customer_service_cofnig['contact_app_id'].'</p>
	</div>
	</li>
	';
	}else{
		$qrcode_1_html = '';
	}

	//qr2
	if( $customer_service_cofnig['contact_app_name_2'] != '' || $customer_service_cofnig['contact_app_id_2'] != '' || $customer_service_cofnig['qrcode_2'] != '' ){
		if( $customer_service_cofnig['qrcode_2'] == '' ) {
			$qrcode_2_img = '';
		}else{
			$qrcode_2_img = '<img src="'.$customer_service_cofnig['qrcode_2'].'" alt="">';
		}
		$qrcode_2_html = '
		<li>
		<div class="img_box">
			'.$qrcode_2_img.'
			<i class="fas fa-qrcode"></i>
		</div>
		<div class="contacts_text_box">
			<p class="mb-0">'.$customer_service_cofnig['contact_app_name_2'].'</p>
			<p class="mb-0">'.$customer_service_cofnig['contact_app_id_2'].'</p>
		</div>
		</li>
		';
	}else{
		$qrcode_2_html = '';
	}

		// 客服聯絡資訊
		$contact_us_list = '
		<ol class="contact_list_deposit">
			<li>'.$tr['click'].'<a href="#" onclick="window.open(\''.$customer_service_cofnig['online_weblink'].'\', \''.$tr['online customer service'] .'\', config=\'height=800,width=700\');">'.$tr['online customer service'] .'</a>'.$tr['contact the customer service'].' </li>
			<li>'.$tr['contact with other method'].'</li>
		</ol>
		<ul class="contacts_list">
		'.$online_service.'
		'.$service_mail.'
		'.$service_tel.'
		</ul>
		<ul class="contacts_list">
			'.$qrcode_1_html.'
			'.$qrcode_2_html.'
		</ul>
		';
		$showtext_html = <<<HTML
		<p class="contact_list_deposit_p">
				{$tr['If you have any questions about the use of this website, you can contact the customer service in any of the following ways']}<br>
		</p>
		{$contact_us_list}
		HTML;

    $deposit_allow = $member_grade_config_detail->deposit_allow;
    $onlinepayment_allow = 0;
    $apifastpay_allow = $member_grade_config_detail->apifastpay_allow;

    // 線上支付手續費
    if (is_null($member_grade_config_detail->pointcardfee_member_rate)) {
        $compinfo['onlinepay'] = [
            'code' => ErrorCode::SOMETHING_WRONG,
            'desc' => "{$tr['the deposit fee rate of member grade not set, please contact us']}({$tr['online momey']})"
        ];
    }

    if ($protalsetting['companydeposit_switch'] == 'on') {

        //公司入款
        $deposit_title = $tr['company money'];

        $deposit_desc = get_deposit_currency_html();
        //即通过支付宝、微信、ATM、柜台、网银等方式手工转账到公司账户，最小存款额 100 ，提交申请后系统在2-30分钟内入账。
        //客製化文字
        if (isset($ui_data['copy']['companypay'][$_SESSION['lang']]) && $ui_data['copy']['companypay'][$_SESSION['lang']] != "") {
            $deposit_desc .= $ui_data['copy']['companypay'][$_SESSION['lang']];
        }
        else {
            $deposit_desc .= sprintf($tr['company money description'], $member_grade_config_detail->depositlimits_lower, $member_grade_config_detail->depositlimits_upper);
        }


        // 公司入款(從Table-root_member_grade讀取角色權限)
        switch ($deposit_allow) {
            case 0:
                $deposit_allow_html = '';
                break;
            case 2:
                //公司入款维护中
                $deposit_btn_message = $tr['company money maintaining'];
                if ($config['site_style'] == 'mobile') {
                    $deposit_allow_html = <<<HTML
                        <div class="row deposit_list_bg">
                            <div class="col">
                                <div id="companymoney">
                                    <div class="deposit_menu_icon row">
                                        <div class="col-2">
                                            <span><i class="far fa-building"></i></span>
                                        </div>
                                        <div class="deposit_icon_title col-5 text-truncate">
                                                <div>
                                                    {$deposit_title}
                                                </div>
                                        </div>
                                        <div class="deposit_icon_btn col-5">
                                            <div class="text-danger" id="companymstop" disabled>
                                                {$deposit_btn_message}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row border-top deposit_list_footer">
                            <div class="col">
                                <div class="deposit_menu_icon d-flex bd-highlight">
                                    <div class="datacontent_company d-none">{$deposit_desc}</div>
                                    <button type="button" class="btn description_icon bd-highlight" id="deposit_textcompany" data-container="body" data-toggle="popover"  data-placement="left" data-content="{$deposit_desc}">
                                        <i class="fa fa-info-circle" aria-hidden="true"></i> {$tr['description']}
                                    </button>
                                </div>
                            </div>
                        </div>
                        HTML;
                }
                else {
                    $deposit_allow_html = <<<HTML
                        <div class="deposit_method mx-auto">
                            <div class="title">
                                <div><i class="far fa-building"></i></div>
                                {$deposit_title}
                            </div>
                            <div class="card-text">{$deposit_desc}</div>
                            <a href="#" class="btn btn-danger w-100" id="companymstop" disabled>
                                {$deposit_btn_message}
                            </a>
                        </div>
                    HTML;
                }
                break;
            default:
                // 公司入款
                $deposit_btn_message = $tr['company money'];
                //公司入款
                if ($config['site_style'] == 'mobile') {
                    $deposit_allow_html = <<<HTML
                        <div class="row deposit_list_bg">
                            <div class="col">
                                <a href="deposit_company.php" id="companymoney">
                                    <div class="deposit_menu_icon row">
                                        <div class="col-2">
                                            <span><i class="fa fa-credit-card"></i></span>
                                        </div>
                                        <div class="deposit_icon_title col-8  text-truncate">
                                                <div>
                                                    {$deposit_title}
                                                </div>
                                        </div>
                                        <div class="deposit_icon_btn col-2">
                                            <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="row border-top deposit_list_footer">
                            <div class="col">
                                <div class="deposit_menu_icon d-flex bd-highlight">
                                    <div class="datacontent_company d-none">{$deposit_desc}</div>
                                    <button type="button" class="btn description_icon bd-highlight" id="deposit_textcompany" data-container="body" data-toggle="popover"  data-placement="left" data-content="{$deposit_desc}">
                                    <i class="fa fa-info-circle" aria-hidden="true"></i> {$tr['description']}
                                </button>
                                {$deposit_status_btn}
                                </div>
                            </div>
                        </div>
                    HTML;
                }
                else {
                    $deposit_allow_html = <<<HTML
                        <div class="deposit_method mx-auto">
                            <div class="title">
                                <div><i class="far fa-building"></i></div>
                                {$deposit_title}
                            </div>
                            <div class="card-text">{$deposit_desc}</div>
                            <div class="d-flex justify-content-between">
                                <a href="deposit_company.php" id="companymoney" class="btn btn-success">
                                    {$deposit_btn_message}
                                </a>
                                <a href="deposit_company_status.php" class="btn btn-primary status">{$tr['process status']}</a>
                            </div>
                        </div>
                    HTML;
                }
        } // end switch

        // $tr['online momey'] = '线上支付';
        $deposit_title = $tr['online momey'];
        $deposit_desc = sprintf($tr['Through the online payment, safe and reliable'], $member_grade_config_detail->onlinepaymentlimits_lower, $member_grade_config_detail->onlinepaymentlimits_upper);
        $deposit_desc = '<div class="alert alert-primary" role="alert">' . $deposit_desc . '</div>';

        //客製化文字
        if (isset($ui_data['copy']['onlinepay'][$_SESSION['lang']]) && $ui_data['copy']['onlinepay'][$_SESSION['lang']] != "") {
            $deposit_desc .= $ui_data['copy']['onlinepay'][$_SESSION['lang']];
        }
        else {
            $deposit_desc .= sprintf($tr['Through the online payment, safe and reliable'], $member_grade_config_detail->onlinepaymentlimits_lower, $member_grade_config_detail->onlinepaymentlimits_upper);
        }


        // 線上支付(停用)
        switch ($onlinepayment_allow) {
            case 0:
                $onlinepayment_allow_html = '';
                break;
            case 2:
                // 線上支付
                // $tr['Online pay maintenance'] = '線上支付維護中';
                $deposit_btn_message = $tr['Online pay maintenance'];

                $onlinepayment_allow_html = <<<HTML
                    <div class="deposit_method mx-auto">
                        <div class="title">
                            <div><i class="far fa-credit-card"></i></div>
                            {$deposit_title}
                        </div>
                        <div class="card-text">{$deposit_desc}</div>
                        <a  id="onlinemstop" href="#" class="btn btn-success w-100">
                            {$deposit_btn_message}
                        </a>
                    </div>
                HTML;
                break;
            default:
                // 線上支付
                // $tr['online momey'] = '線上支付';
                $deposit_btn_message = $tr['online momey'];

                $onlinepayment_allow_html = <<<HTML
                    <div class="card deposit-card">
                        <div class="card-header">
                            <div class="row bd-highlight">
                                <div class="col deposit_color mr-auto bd-highlight">{$deposit_title}</div>
                                <div class="col bd-highlight text-right">
                                    <a href="deposit_online_pay.php" id="onlinemomey" class="btn btn-success btn-sm">
                                        <span class="glyphicon glyphicon-log-in mr-2" aria-hidden="true"></span>
                                        {$deposit_btn_message}
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body deposit_color">
                            <div class="card-text">{$deposit_desc}</div>
                        </div>
                    </div>
                    <hr>
                HTML;
        } // end switch

        // $deposit_title = $tr['fast pay'];
        $deposit_title = $tr['online momey'];


        $deposit_desc = $tr['payment limit'] . '：' . $member_grade_config_detail->apifastpaylimits_lower . '~' . $member_grade_config_detail->apifastpaylimits_upper;
        $deposit_desc = '<p>' . $deposit_desc . '</p>';
        //客製化文字
        if (isset($ui_data['copy']['fastpay'][$_SESSION['lang']]) && $ui_data['copy']['fastpay'][$_SESSION['lang']] != "") {
            $deposit_desc .= $ui_data['copy']['fastpay'][$_SESSION['lang']];
        }
        else {
            $deposit_desc .= '<p>' . $tr['fast pay tip'] . '</p>';
        }

        // 交易明細 金鑰
        // $user_account = password_hash($_SESSION['member']->account, PASSWORD_DEFAULT);
        // 使用 php 先算好支付的固定參數，金額不驗證
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443 || getenv('HTTP_X_FORWARDED_PROTO') === 'https') ? "https://" : "http://";

        // 從後台protalsetting取得線上支付開關設定值(By Damocles)
        $apifastpay_allow_html = ''; // 要留-初始化
        $query = <<<SQL
            SELECT value,
                   status
            FROM root_protalsetting
            WHERE (name = 'front_payment')
            LIMIT 1;
        SQL;
        $result = runSQLall( $query, 0 );
        if( $result[0] == 1 ){
            if( $result[1]->status == 1 ){
                if( $result[1]->value == 'on' ){
                    // 快速入款(從Table-root_member_grade讀取角色權限)
                    switch ($apifastpay_allow) {
                        case 0:
                            $apifastpay_allow_html = '';
                            break;
                        case 2:
                            $deposit_btn_message = $tr['Online pay maintenance'];
                            if ($config['site_style'] == 'mobile') {
                                $apifastpay_allow_html = <<<HTML
                                    <div class="row deposit_list_bg" id="onlinemomey_bt">
                                        <div class="col">
                                            <div class="deposit_menu_icon row">
                                                <div class="col-2">
                                                    <span><i class="fa fa-credit-card"></i></span>
                                                </div>
                                                <div class="deposit_icon_title col-5 text-truncate">
                                                        <div>
                                                            {$deposit_title}
                                                        </div>
                                                </div>
                                                <div class="deposit_icon_btn col-5">
                                                    <div class="text-danger w-100" disabled>
                                                        {$deposit_btn_message}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                        <div class="row border-top deposit_list_footer">
                                            <div class="col">
                                                <div class="deposit_menu_icon d-flex bd-highlight">
                                                    <div class="datacontent d-none">{$deposit_desc}</div>
                                                    <button type="button" class="btn description_icon bd-highlight" id="description_textonline" data-container="body" data-toggle="popover"  data-placement="left" data-content="">
                                                        <i class="fa fa-info-circle" aria-hidden="true"></i> {$tr['description']}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                            HTML;
                            }
                            else {
                                $apifastpay_allow_html = <<<HTML
                                    <div class="deposit_method mx-auto">
                                        <div class="title">
                                            <div><i class="far fa-credit-card"></i></div>
                                            {$deposit_title}
                                        </div>
                                        <div class="card-text">{$deposit_desc}</div>
                                        <a href="#" class="btn btn-danger w-100" disabled>
                                            {$deposit_btn_message}
                                        </a>
                                    </div>
                                    <hr>
                                HTML;
                            }
                            break;
                        default:
                            // $tr['fast pay'] = '快速入款';
                            $deposit_btn_message = $tr['fast pay'];
                            $deposit_btn_message = $tr['online momey'];

                            //線上支付
                            if ($config['site_style'] == 'mobile') {
                                $apifastpay_allow_html = <<<HTML
                                    <div class="row deposit_list_bg" data-toggle="modal" id="onlinemomey_bt" data-target="#fast-pay-order">
                                        <div class="col">
                                            <div class="deposit_menu_icon row">
                                                <div class="col-2">
                                                    <span><i class="fa fa-credit-card"></i></span>
                                                </div>
                                                <div class="deposit_icon_title col-8  text-truncate">
                                                    <div>
                                                        {$deposit_title}
                                                    </div>
                                                </div>
                                                <div class="deposit_icon_btn col-2">
                                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row border-top deposit_list_footer">
                                        <div class="col">
                                            <div class="deposit_menu_icon d-flex bd-highlight">
                                                <div class="datacontent d-none">{$deposit_desc}</div>
                                                <button type="button" class="btn description_icon bd-highlight" id="description_textonline" data-container="body" data-toggle="popover"  data-placement="left" data-content="">
                                                    <i class="fa fa-info-circle" aria-hidden="true"></i> {$tr['description']}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    HTML;
                            }
                            else {
                                $apifastpay_allow_html = <<<HTML
                                    <div class="deposit_method mx-auto">
                                        <div class="title">
                                            <div><i class="far fa-credit-card"></i></div>
                                            {$deposit_title}
                                        </div>
                                        <div class="card-text">{$deposit_desc}</div>
                                        <button data-toggle="modal" id="onlinemomey_bt" data-target="#fast-pay-order" class="btn btn-success w-100">
                                            {$deposit_btn_message}
                                        </button>
                                    </div>
                                HTML;
                            }

                            $fast_pay_amount_notice = sprintf($tr['Fast pay amount notice'], $member_grade_config_detail->apifastpaylimits_lower, $member_grade_config_detail->apifastpaylimits_upper);
                            try {
                                // 付款方式的placeholder
                                $tr['please select pay service'] = ( isset($tr['please select pay service']) && ($tr['please select pay service'] != null) ? $tr['please select pay service'] : 'please select a pay service' );
                                // 付款方式的預設選項
                                $pay_services_options_html = <<<HTML
                                    <option value="" default disabled selected>{$tr['please select pay service']}</option>
                                HTML;

                                // 付款方式的選單內容
                                $pay_service_option_html = <<<HTML
                                    <option value="%1\$s" data-provider='%3\$s' data-min_amount="%4\$s" data-max_amount="%5\$s" data-placeholder="%6\$s" data-min_amount_msg="%7\$s" data-max_amount_msg="%8\$s">%2\$s</option>
                                HTML;

                                if (!isset($service_list)) {
                                    throw new Exception(ErrorCode::getErrorMessage(ErrorCode::UNAVAILABLE), ErrorCode::UNAVAILABLE);
                                }

                                // 查詢付款方式
                                foreach (queryPaymentMethods($service_list, $currency, $tr) as $payservice) {
                                    // 查詢付款方式下的第三方支付方式
                                    $payment_providers = queryOrderPaymentMethodProvider($service_list, $payservice['codename']);
                                    // 有查到付款方式下的第三方支付方式，才會顯示在下拉選單中 (如果該付款方式下，沒有其他第三方支付方式，使用者還是得點選其他選項，故先做判斷)
                                    if( count(json_decode($payment_providers)) > 0 ){
                                        $pay_services_options_html .= sprintf(
                                            $pay_service_option_html,
                                            $payservice['codename'],
                                            $payservice['title'],
                                            $payment_providers,
                                            $payservice['min_amount'],
                                            $payservice['max_amount'],
                                            $payservice['placeholder'],
                                            $payservice['min_amount_msg'],
                                            $payservice['max_amount_msg']
                                        );
                                    }
                                } // end foreach
                                $service_provider = $tr['pay service'] ?? 'Service';
                                $pay_service_provider = $tr['pay service provider'] ?? 'Service Provider';
                                $pay_service_select_html = <<<HTML
                                    <!-- 選擇支付方式 -->
                                    <label for="payservice_method" class="control-label col-sm-3 mb-2">{$service_provider}</label>
                                    <div class="col-sm-9 mb-2">
                                        <select id="payservice_method" name="payservice" class="form-control" required>{$pay_services_options_html}</select>
                                    </div>
                                    <!-- 選擇金流商 -->
                                    <label for="payservice" class="control-label col-sm-3 mb-2">{$pay_service_provider}</label>
                                    <div class="col-sm-9 mb-2">
                                        <select  id="provider" name="provider" class="form-control" required></select>
                                    </div>
                                    <!-- 選擇銀行 -->
                                    <div class="row px-3" for="bank" style="display: none;">
                                        <label for="bank" class="control-label col-sm-3 mb-2">{$tr['bank']}</label>
                                        <div class="col-sm-9 mb-2">
                                            <select name="bank" id="bank" class="form-control"></select>
                                        </div>
                                    </div>
                                    <script>
                                        function update_banks_html() {
                                            var paymethod_code = $('#provider').children(':selected').data('method_code');
                                            var is_bank_required = $('#provider').children(':selected').data('is_bank_required');
                                            $('div[for="bank"]').hide()
                                            $('#bank').attr('required', is_bank_required)
                                            if (!is_bank_required) return false;
                                            var bank_option_html = '';
                                            var banks = onlinepay.banks[paymethod_code];
                                            for (key in banks) {
                                                bank_option_html += `<option value="\${banks[key].swift_code}">\${banks[key].bank}</option>`
                                            }
                                            $('#bank').html(bank_option_html)
                                            $('div[for="bank"]').show()
                                        }
                                        //
                                        $(document).on('change', '#payservice_method', function(){
                                            var min_amount = parseInt( $(this).children(':selected').data('min_amount') );
                                            var max_amount = parseInt( $(this).children(':selected').data('max_amount') );
                                            var placeholder = $(this).children(':selected').data('placeholder');
                                            var min_amount_msg = $(this).children(':selected').data('min_amount_msg');
                                            var max_amount_msg = $(this).children(':selected').data('max_amount_msg');
                                            var provider_data = $(this).children(':selected').data('provider');
                                            var provider_option_html = '';
                                            for(i=0; i<provider_data.length; i++){
                                                // provider_option_html += '<option value="'+provider_data[i].provider_code+'">'+provider_data[i].provider_title+'</option>';
                                                provider_option_html += $('<option>', {
                                                    value: provider_data[i].provider_code,
                                                    "data-method_code": provider_data[i].method_code,
                                                    "data-is_bank_required": provider_data[i].is_bank_required,
                                                    html: provider_data[i].provider_title
                                                })[0].outerHTML
                                            } // end for
                                            $('#provider').html(provider_option_html);
                                            $('#provider').prop('selectedIndex', 0);
                                            $('#bank').prop('selectedIndex', 0);
                                            $('#provider').trigger('change')
                                            if( (min_amount == 0) && (max_amount == 0) ){
                                                $('#order_amount').attr('min', 1).attr('max', '').attr('placeholder', placeholder).data('min_amount_msg', min_amount_msg);
                                            }
                                            else{
                                                $('#order_amount').val('').attr('min', min_amount).attr('max', max_amount).attr('placeholder', placeholder).data('min_amount_msg', min_amount_msg).data('max_amount_msg', max_amount_msg);
                                            }
                                        }); // end on
                                        /* update banks list */
                                        $('#provider').on('change', update_banks_html)
                                        /* check amount range */
                                        $(document).on('blur', '#order_amount', function(){
                                            if( $(this).attr('max') != '' ){
                                                if( parseInt($(this).val()) > parseInt($(this).attr('max')) ){
                                                    $(this).val( $(this).attr('max') );
                                                    if( ($(this).data('max_amount_msg') != undefined) && ($(this).data('max_amount_msg') != '') ){
                                                        alert( $(this).data('max_amount_msg') );
                                                    }
                                                }
                                            }
                                            if( $(this).attr('min') != '' ){
                                                if( parseInt($(this).val()) < parseInt($(this).attr('min')) ){
                                                    $(this).val( $(this).attr('min') );
                                                    if( ($(this).data('min_amount_msg') != undefined) && ($(this).data('min_amount_msg') != '') ){
                                                        alert( $(this).data('min_amount_msg') );
                                                    }
                                                }
                                            }
                                            // fee info
                                            var round2 = function (input, points) {
                                                return Math.round(input * Math.pow(10, points)) / Math.pow(10, points)
                                            }
                                            amount = Number($(this).val())
                                            fee_rate = parseInt({$member_grade_config_detail->pointcardfee_member_rate}) / 100
                                            if (isNaN(fee_rate)) {
                                                var msg = "{$tr['the deposit fee rate of member grade not set, please contact us']}"
                                                $('#fee_info').html(`\${msg}`);
                                                $('#expected_amount_info').html(`\${msg}`)
                                            } else {
                                                fee = round2(amount * fee_rate, 2)
                                                expected_amount = round2(amount - fee, 2)
                                                expected_amount = expected_amount > 0 ? expected_amount : 0
                                                $('#fee_info').html(`\${amount} * \${fee_rate} = \${fee}`);
                                                $('#expected_amount_info').html(`\${amount} - \${fee} = \${expected_amount}`)
                                            }
                                        }); // end on
                                    </script>
                                HTML;

                                $go2pay_btn_status = $compinfo['onlinepay']['code'] !== 0 ? 'disabled' : '';

                                $apifastpay_allow_html .= <<<HTML
                                    <div id="fast-pay-order" class="modal fade" role="dialog" style="top: 20%">
                                        <div class="modal-dialog">
                                            <!-- Modal content-->
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h4 class="modal-title">{$tr['Fast pay info']}</h4>
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                </div>
                                                <form class="form-horizontal" action="deposit_action.php?a=get_pay_link" method="POST" target="_blank" id="form_fastpay" name="form_fastpay">
                                                    <div class="modal-body">
                                                        <div class="row form-group">
                                                            {$pay_service_select_html}
                                                            <label class="control-label col-sm-3 mb-2" for="order_amount">{$tr['amount']}</label>
                                                            <div class="col-sm-9 mb-2">
                                                                <input
                                                                    class="form-control"
                                                                    type="number"
                                                                    min="{$member_grade_config_detail->apifastpaylimits_lower}"
                                                                    max="{$member_grade_config_detail->apifastpaylimits_upper}"
                                                                    step="0.01"
                                                                    name="amount"
                                                                    id="order_amount"
                                                                    value=""
                                                                    placeholder="{$fast_pay_amount_notice}"
                                                                    required
                                                                >
                                                            </div>
                                                            <label for="fee_info" class="control-label col-sm-3 mb-2" title="{$member_grade_config_detail->pointcardfee_member_rate} %">{$tr['fee']}</label>
                                                            <div class="pt-2 col-sm-9" title="{$tr['according to member grade']}" id="fee_info">0</div>
                                                            <label for="expected_amount_info" class="control-label col-sm-3 mb-2">{$tr['expected deposit amount']}</label>
                                                            <div class="pt-2 col-sm-9" title="{$tr['real amount without fee']}" id="expected_amount_info">0</div>
                                                        </div>
                                                        <div>
                                                            <input type="hidden" name="csrftoken" value="${!${''}=csrf_token_make()}">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="submit" id="form_fastpay_submit" class="btn btn-success" form="form_fastpay" data-dismisss="modal" {$go2pay_btn_status}>{$tr['go to pay']}</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                HTML;
                            }
                            catch (\Exception $e) {
                                $apifastpay_allow_html .= <<<HTML
                                    <div id="fast-pay-order" class="modal fade" role="dialog" style="top: 20%">
                                        <div class="modal-dialog">
                                            <!-- Modal content-->
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h4 class="modal-title">{$tr['Fast pay info']}</h4>
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>{$tr['There is no available payment method,please contact customer service.']}</p>
                                                    <p>{$e->getMessage()}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                HTML;
                            }
                    } // end switch
                }
            }
            else{
                die("warn: protal setting is disabled, if it's still happening, ask the web keeper for help.");
            }
        }
        else{
            die('protal setting is not defined, get the protal setting and try again.');
        }

        //新增入款連結
        function moreadd_deposit($title, $content, $link){
            global $config, $tr;
            if (!preg_match("/http/i", $link)) {
                $link = '//' . $link;
            }

            //更多
            if ($config['site_style'] == 'mobile') {
                $moreadd_deposit = <<<HTML
                    <div class="row deposit_list_bg">
                    <div class="col">
                    <a href="{$link}">
                        <div class="deposit_menu_icon row">
                            <div class="col-2">
                                <span><i class="fa fa-credit-card"></i></span>
                            </div>
                            <div class="deposit_icon_title col-8  text-truncate">
                                    <div>
                                    {$title}
                                    </div>
                            </div>
                            <div class="deposit_icon_btn col-2">
                                <i class="fas fa-chevron-right" aria-hidden="true"></i>
                            </div>
                        </div>
                        </a>
                        </div>
                    </div>
                    <div class="row border-top deposit_list_footer">
                        <div class="col">
                            <div class="deposit_menu_icon d-flex bd-highlight">
                                <div class="datacontent_company d-none">{$content}</div>
                                <button type="button" class="btn description_icon bd-highlight" data-container="body" data-toggle="popover"  data-placement="left" data-content="{$content}">
                                <i class="fa fa-info-circle" aria-hidden="true"></i> {$tr['description']}
                            </button>
                            </div>
                        </div>
                    </div>
                HTML;
            }
            else {
                $moreadd_deposit = <<<HTML
                    <div class="deposit_method mx-auto">
                        <div class="title">
                            <div><i class="far fa-credit-card"></i></div>
                            {$title}
                        </div>
                        <div class="card-text">{$content}</div>
                        <a href="{$link}" class="btn btn-success w-100">
                            {$title}
                        </a>
                    </div>
                HTML;
            }
            return $moreadd_deposit;
        } // end moreadd_deposit

        //對應後台排序入款方式
        $deposit_html = array(
            "companypay" => $deposit_allow_html,
            "onlinepay" => $onlinepayment_allow_html,
            "fastpay" => $apifastpay_allow_html,
        );

        if (isset($ui_data["deposit"]["sort"]) && count($ui_data["deposit"]["sort"])!==0) {
            $form_html_s = '';
            foreach ($ui_data["deposit"]["sort"] as $key => $value) {
                if (isset($deposit_html[$value])) {
                    $form_html_s .= $deposit_html[$value];
                }
                else {
                    $form_html_s .= moreadd_deposit($ui_data["deposit"][$value]["title"], $ui_data["deposit"][$value]["content"], $ui_data["deposit"][$value]["link"]);
                }
            }
        }
        else {
            $form_html_s = $onlinepayment_allow_html . $apifastpay_allow_html . $deposit_allow_html;
        }
        //餘額不足提示
        $messages_deposit = <<<HTML
            <div class="row justify-content-md-center">
                <div class="col-12">
                    <div id="preview" class="deposit_preview"></div>
                </div>
            </div>
        HTML;

        if ($config['site_style'] == 'desktop') {
            $form_html = <<<HTML
                <div class="main_content deposit_center">
                    <div class="deposit_list_method">
                        {$form_html_s}
                    </div>
                    {$messages_deposit}
                </div>
            HTML;
        }
        else {
            $form_html = <<<HTML
                {$messages_deposit}{$form_html_s}
            HTML;
        }
        $extend_js = <<<HTML
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
                <script type="text/javascript">
                $(document).ready(function(){
                    var datacontent = $('.datacontent').text();
                    $('#description_textonline').attr('data-content',datacontent);
                    var datacontent_company = $('.datacontent_company').text();
                    $('#deposit_textcompany').attr('data-content',datacontent_company);
                });
                $(function () {
                    $('[data-toggle="popover"]').popover();
                    $('#form_fastpay').on('submit', function(e) {
                        $('#fast-pay-order').modal('hide')
                        return true;
                    });
                });
                </script>
        HTML;

        $extend_js .= combineGetCasinoBalanceJs();

    }
    else {
        $companydeposit_offline_desc_html = <<<HTML
            <p class="alert alert-danger">
                {$protalsetting['companydeposit_offline_desc']}
            </p>
        HTML;

        if ($config['site_style'] == 'desktop') {
            $form_html =  <<<HTML
                <div class="main_content deposit_center">
                {$companydeposit_offline_desc_html}<hr>{$showtext_html}
                </div>
HTML;
        }
        else {
            $form_html =  <<<HTML
            <div class="row">
                <div class="col">
                    <div class="main_content deposit_center">
                    {$companydeposit_offline_desc_html}<hr>{$showtext_html}
                    </div>
                </div>
            </div>
HTML;
        }
    }

    // 如果兩各如款方式都被關閉的話
    // $tr['There is no available payment method,please contact customer service.'] = '目前沒有可用的入款方式，如須入款請聯繫客服人員處理。';
    if ($memberstatus == '2' || ( $deposit_allow == 0 && $onlinepayment_allow == 0 && ($apifastpay_allow == 0) )) {
        // $form_html = $tr['There is no available payment method,please contact customer service.'];
        $notify = ($memberstatus == '2') ? $tr['Your wallet has been frozen, please contact customer'] : $tr['There is no available payment method,please contact customer service.'];

        if ($config['site_style'] == 'desktop') {
            $form_html =  <<<HTML
            <div class="main_content deposit_center">
                <div class="d-flex justify-content-center">
                    <p class="no_available">{$notify}</p>
                </div>
            </div>
HTML;
        }
        else {
            $form_html =  <<<HTML
            <div class="row">
                <div class="col">
                    <div class="main_content deposit_center">
                        <div class="d-flex justify-content-center">
                            <p class="no_available">{$notify}</p>
                        </div>
                    </div>
                </div>
            </div>
HTML;
    }
    }

    //財務中心(餘額刷新欄位)>
    if ($config['site_style'] == 'mobile') {
        $casinoBalanceHtml = combineGetCasinoBalanceHtml();
        $deposit_total_information = <<<HTML
            <div class="row">{$casinoBalanceHtml}</div>
        HTML;

    }
    else {
        $deposit_total_information = combineGetCasinoBalanceHtml('deposit');
    }

}
else {
    // 不合法登入者的顯示訊息 (x) 請先登入會員，才可以使用此功能。
    $deposit_total_information = '';
    $form_html = $tr['login first'];
}

if ($config['site_style'] == 'mobile') {
    // 切成 3 欄版面
    $indexbody_content .= <<<HTML
        {$deposit_total_information}
        <div class="deposit_title_content">
            {$tr['membercenter_deposit_select_mode']}
        </div>
        <div class="row justify-content-md-center" id="deposit">
            <div class="col-12">
                {$form_html}
            </div>
        </div>
    HTML;
}
else {
    //側邊攔
    $indexbody_content .= <<<HTML
        <div class="row justify-content-md-center" id="deposit">
            <div class="col-12">
                <div class="row">
                    <div>{$deposit_total_information}</div>
                    <div class="col">{$form_html}</div>
                </div>
            </div>
        </div>
    HTML;
}

$extend_js .= sprintf(
    <<<HTML
        <script>
            window.compinfo = %s
            compinfo.onlinepay.code !== 0 && alert(compinfo.onlinepay.desc)
            window.onlinepay = {
                banks: %s
            }
        </script>
    HTML,
    json_encode($compinfo),
    json_encode(getPaymethodBanks($service_list))
);

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
$tmpl['sidebar_content'] = ['deposit', 'deposit'];
// banner標題
$tmpl['banner'] = ['membercenter_menu_admin_deposit'];
// menu增加active
$tmpl['menu_active'] = ['deposit.php'];
// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include $config['template_path'] . "template/admin.tmpl.php";
