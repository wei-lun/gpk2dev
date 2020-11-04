<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 代币(GTOKEN)线上取款前台程式
// 先確認會員資料中的銀行帳號資訊是否正確，不正確提供修改界面
// 輸入提款金額, 只允許從 GTOKEN 轉出餘額，不允許從 GCASH 轉出。
// 提款時，可以選擇轉到 GCASH 或是直接提領出來到銀行。
// 確認提款後，直接先扣除 GTOKEN 帳號餘額，等處理完成後，退款或是扣除。
// File Name:	withdrawapplication.php
// Author:		Barkley
// Related:
// DB table:  root_withdraw_review
// Log:
/*
操作的表格：root_withdraw_review
前台
withdrawapplication.php 代币(GTOKEN)线上取款前台程式
withdrawapplication_action.php 代币(GTOKEN)线上取款前台動作
後台
withdrawalgtoken_company_audit.php  後台審查列表頁面
withdrawalgtoken_company_audit_review.php  後台單筆紀錄審查
withdrawapgtoken_company_audit_review_action.php 審查用的同意或是轉帳動作SQL操作
*/
// ----------------------------------------------------------------------------



// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";
// 即時稽核 lib
require_once dirname(__FILE__) ."/token_auditorial_lib.php";


// var_dump($_SESSION);

// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------


// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
$function_title 		= $tr['withdrawapplication title'];
// 擴充 head 內的 css or js
$extend_head				= '';
// 放在結尾的 js
$extend_js					= '';
// body 內的主要內容
$indexbody_content	= '';
// 系統訊息選單
$messages 					= '';
// 初始化變數 end
// ----------------------------------------------------------------------------
// 導覽列
$navigational_hierarchy_html = '
<ul class="breadcrumb">
  <li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
  <li><a href="member.php">'.$tr['Member Centre'].'</a></li>
  <li><a href="wallets.php">'.$tr['wallets title'].'</a></li>
  <li class="active">'.$function_title.'</li>
</ul>
';
if($config['site_style']=='mobile'){
    $navigational_hierarchy_html =<<<HTML
    <a href="{$config['website_baseurl']}wallets.php"><i class="fas fa-chevron-left"></i></a>
    <span>{$function_title}</span>
    <i></i>
HTML;
}
// ----------------------------------------------------------------------------

$account = NULL;
if(isset($_GET['account'])) {
  $account = $_GET['account'];
} else {
  $account = 'confirm';
}

//提款功能若關閉，則GTOKEN提款頁面，會導回會員錢包
if ($protalsetting['withdrawalapply_switch'] != 'on'){
  echo '<script language="javascript">document.location.href="wallets.php";</script>';
}

function get_bankaccountdata_html($member_data, $input_id, $placeholder_text, $col_name) {
  global $config;
	if ($member_data != '') {
		$td_html = $member_data;
	} else {
		$td_html = <<<HTML
		<input type="text" class="form-control" id="{$input_id}" placeholder="ex: {$placeholder_text}">
HTML;
	}

	$col_name = $col_name;
if($config['site_style']=='mobile'){
  $data['html'] = <<<HTML
  <tr class="row">
    <th>{$col_name}</th>
    <td>{$td_html}</td>
  </tr>
HTML;
}else{
	$data['html'] = <<<HTML
	<tr>
		<td>{$col_name}</td>
		<td>{$td_html}</td>
	</tr>
HTML;
}
	return $data;
}

$preview_status_html = '';
// $member_persondata_col = '';
// 有會員資料 不論身份 therole 為何，都可以修改個人資料。但是除了 therole = T 除外。
if(isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T' ) {

  if ($member_grade_config_detail->withdrawal_allow == 1) {

    // 預設登入者本身
    $query_id = $_SESSION['member']->id;

    // 查詢來源帳號的資料 , 確認該帳號 status 還是有效的
    $sql = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.id = $query_id AND status = '1';";
    $g = runSQLALL($sql);

    if($g[0] == 1) {

      $show_withdrawal_html = '';

      $bankdata_isnull = false;
      if ($g[1]->bankaccount == '' || $g[1]->bankname == '' || $g[1]->bankprovince == '' || $g[1]->bankcounty == '') {
        $bankdata_tablecontent = '';

        $bankaccountdata_arr = [
          'bankname' => [
            'colname' => $tr['bank name'],
            'placeholder_text' => $tr['bank name'],
            'bank_data' => $g[1]->bankname
          ],
          'bankaccount' => [
            'colname' => $tr['bank account'],
            'placeholder_text' => $tr['bank account'].'('.$tr['Please fill in the numbers'].')',
            'bank_data' => $g[1]->bankaccount
          ],
          'bankprovince' => [
            'colname' => $tr['bank province'],
            'placeholder_text' => $tr['bank province'],
            'bank_data' => $g[1]->bankprovince
          ],
          'bankcounty' => [
            'colname' => $tr['bank country'],
            'placeholder_text' => $tr['bank country'],
            'bank_data' => $g[1]->bankcounty
          ]
        ];

        foreach ($bankaccountdata_arr as $colname => $content) {
          $table_data = get_bankaccountdata_html($content['bank_data'], $colname, $content['placeholder_text'], $content['colname']);
          $bankdata_tablecontent .= $table_data['html'];
        }

        $desc = $tr['withdrawapplicationgcash notice'];
        if($config['site_style']=='mobile'){
        $submitbtn = <<<HTML
        <div>{$desc}</div>
        <button class="send_btn btn-primary mt-2" id="update_bankdata">{$tr['save and next']}</button>
HTML;
        }else{
        $submitbtn = <<<HTML
        <div class="card-footer">
        {$desc}
        <p align="right"><button class="btn btn-success" id="update_bankdata">{$tr['save and next']}</button></p>
        </div>
HTML;
}
        if($config['site_style']=='mobile'){
        $show_withdrawal_html = <<<HTML
        <div class="row withdrawapp_text mt-10">
          <div class="col">
            {$tr['withdrawapplicationgcash complete notice']}
          </div>
        </div>
          <div class="row">
          <div class="col-12">
          <table class="table member_table">
            <tbody>
              {$bankdata_tablecontent}
            </tbody>
          </table>
          </div>
          </div>
          <div class="row">
          <div class="col">
            {$submitbtn}  
          </div>
        </div>
HTML;
        }else{
        $show_withdrawal_html = <<<HTML
        <div class="card deposit-card">
          <div class="card-header h5">{$tr['withdrawapplicationgcash complete notice']}</div>
          <div class="card-body">
          <table class="table arc_style">
            <thead>
              <tr>
                <th>{$tr['field']}</th>
                <th>{$tr['content']}</th>
              </tr>
            </thead>
            <tbody>
              {$bankdata_tablecontent}
            </tbody>
          </table>
          </div>
          {$submitbtn}          
        </div>
HTML;
}

        $extend_js = <<<JS
        <script>
          $(document).ready(function() {
            $('#update_bankdata').click(function(){
              var bankname = $('#bankname').val();
              var bankaccount = $('#bankaccount').val();
              var bankprovince = $('#bankprovince').val();
              var bankcounty = $('#bankcounty').val();
              var csrftoken = '$csrftoken';

              if(jQuery.trim(bankname) != '' || jQuery.trim(bankaccount) != '' ||
                jQuery.trim(bankprovince) != '' || jQuery.trim(bankcounty) != '') {

                var r = confirm('{$tr['Confirmation of information']}');
                if (r == true) {
                  if( !isNaN( bankaccount ) ){
                    $.post('member_action.php?a=member_editbankdata',
                      {
                        bankname: bankname,
                        bankaccount: bankaccount,
                        bankprovince: bankprovince,
                        bankcounty: bankcounty,
                        csrftoken : csrftoken
                      },
                      function(result){
                        $('#preview_area').html(result);
                      }
                    );
                  } else {
                    alert('{$tr['bank account only can be filled number']}');
                  }  
                }
              } else {
                alert('{$tr['Please enter correct information.']}');
              }
            });

          });
        </script>
JS;

        $bankdata_isnull= true;
      }


      $audit_html = '
      <a href="token_auditorial.php" title="'.$tr['GTOKEN withdrawal audit info'].'" class="btn btn-info mb-10" role="button">'.$tr['GTOKEN withdrawal audit info'].'</a>
      ';

      $withdrawal_fee_upper_limit = $member_grade_config_detail->withdrawalfee_max;
      $withdrawal_fee_lower_limit = '1';
      // $tr['withdrawapplication title'] = '現金取款';
      // $tr['GTOKEN withdrawal audit info'] = '現金取款稽核資訊';
      // $tr['GTOKEN withdrawal administrative free'] = '現金取款行政費用';
      // $tr['GTOKEN withdrawal free'] = '現金取款手續費用';
      // $tr['withdrawapplication_1'] = '* 现金提款金额输入不可有小数，如有小数则以整数位计。 <br>* 申请现金提款时，离最后1笔存提款纪录时间需要 ';
      // $tr['withdrawapplication_2'] = ' min 以上，才可以再次申请现金提款。 (依审核时间算起)<br>* 申请现金提款时，提款手续费将自会员帐户中扣除。 <br>* 申请现金提款时，会从会员帐户预扣取款金额及手续费，如审核不通过会返回取款金额及手续费至会员帐户中。 <br>* 按取款金额的 ';
      // $tr['withdrawapplication_3'] = '% 收取手续费，最低 ';
      // $tr['withdrawapplication_4'] = '元，最高 ';
      // $tr['withdrawapplication_5'] = '元。<br>* 手续费、总扣除额及余额计算仅供参考，依实际扣除金额为主，如有疑问请洽客服。';
      $preview_status_html = '
      <div id="preview_status" class="alert alert-info mt-10" role="alert">
      '.$tr['withdrawapplication_1'].$member_grade_config_detail->withdrawal_limitstime_gtoken.$tr['withdrawapplication_2'].$member_grade_config_detail->withdrawalfee.$tr['withdrawapplication_3'].$withdrawal_fee_lower_limit.$tr['withdrawapplication_4'].$withdrawal_fee_upper_limit.$tr['withdrawapplication_5'].'</div>';

      // step 0
      // 如果已經有提款申請的話要等到完成後，才可以再申請下一筆資料。
      // 顯示目前系統中已經提出請款申請的單號。 status = 2  , 0=cancel 1=ok 2=apply 3=process 4=reject null=del
      $tzonename = get_tzonename($_SESSION['member']->timezone);

      $show_sql = "SELECT *, to_char((applicationtime AT TIME ZONE '$tzonename'),'YYYY-MM-DD HH24:MI:SS') as applicationtime FROM root_withdraw_review WHERE account = '".$_SESSION['member']->account."' AND status = '2';";
      $show_sql_result = runSQLALL($show_sql);

      $withdrawal_status_runing = false;
      if($show_sql_result[0] >= 1) {

        // 提款狀態
        //已刪除
        $withdrawal_status[NULL] 	= $tr['withdraw status delete'];
        //使用者取消
        $withdrawal_status[0] 		= $tr['withdraw status cancel'];
        //已完成出款
        $withdrawal_status[1] 		= $tr['withdraw status complete'];
        //請款審查中
        $withdrawal_status[2] 		= $tr['withdraw status apply'];
        //管理端處理中
        $withdrawal_status[3] 		= $tr['withdraw status process'];
        //管理端退回
        $withdrawal_status[4] 		= $tr['withdraw status reject'];

        $withdraw_method = ($show_sql_result[1]->togcash == 1) ? $tr['Convert to cash'] : $tr['Bank withdrawal'];

        $est_date = gmdate('Y-m-d H:i:s', strtotime($show_sql_result[1]->applicationtime) + -4 * 3600);

        // 目前提交的列表
        if($config['site_style']=='mobile'){
          $withdrawal_data_html = '
          <div class="header_description">
            <div class="row">
              <div class="col-8">
                <div class="withdra_description">
                    '.$tr['applied withdrawal seq'].'
                </div>
              </div>
              <div class="col">
                <button type="button" class="btn" data-container="body" data-toggle="popover"  data-placement="left" data-content="'.$tr['applied withdrawal seq'].'">
                  <i class="fa fa-info-circle" aria-hidden="true"></i> '.$tr['description'].'
                </button>
              </div>
            </div>
          </div>
          <div class="row">
          <div class="col-12">
          <div class="shadow-sm rounded withd_cash_list"> 
            <div class="d-flex bd-highlight withd_cash border-bottom">
              <div class="flex-fill bd-highlight">'.$tr['withdrawal apply time'].'</div>
              <div class="flex-fill bd-highlight text-right">'.$est_date.'</div>
            </div>
            <div class="d-flex bd-highlight withd_cash">
              <div class="flex-fill bd-highlight">'.$tr['withdrawal apply money'].'</div>
              <div class="flex-fill bd-highlight text-right">'.$show_sql_result[1]->amount.'</div>
            </div> 
            <div class="d-flex bd-highlight withd_cash">
              <div class="flex-fill bd-highlight">'.$tr['withdraw_method'].'</div>
              <div class="flex-fill bd-highlight text-right">'.$withdraw_method.'</div>
            </div>
            <div class="d-flex bd-highlight withd_cash">
              <div class="flex-fill bd-highlight">'.$tr['withdrawal apply status'].'</div>
              <div class="flex-fill bd-highlight text-right text-success">'.$withdrawal_status[$show_sql_result[1]->status].'</div>
            </div>  
          </div>
          </div>
          </div>
          ';
        }else{
          $withdrawal_data_html = '
        <tr>
          <td>'.$tr['withdrawal apply time'].'</td>
          <td>'.$est_date.'</td>
        </tr>
        <tr>
          <td>'.$tr['withdrawal apply money'].'</td>
          <td>'.$show_sql_result[1]->amount.'</td>
        </tr>
        <tr>
          <td>'.$tr['withdraw_method'].'</td>
          <td>'.$withdraw_method.'</td>
        </tr>
        <tr>
          <td>'.$tr['withdrawal apply status'].'</td>
          <td>'.$withdrawal_status[$show_sql_result[1]->status].'</td>
        </tr>
        ';
        }

        //已經提出請款申請的單號
        //提款單號 申請時間 提款金額 目前狀態
        if($config['site_style']=='mobile'){
          $show_withdrawal_html = '
          <form id="step1_bank_select" method="post">
            '.$withdrawal_data_html.'
          </form>
          ';
        }else{
        $show_withdrawal_html = '
        <form id="step1_bank_select" method="post">
          <div class="request_form deposit-card">
            <div class="de_companytitle">'.$tr['applied withdrawal seq'].'</div>
            <div class="card-body">
            <table class="table arc_style">
              <thead></thead>
              <tbody>
                '.$withdrawal_data_html.'
              </tbody>
            </table>
            </div>
          </div>
        </form>
        ';
      }

        // 已經有申請提款了
        $withdrawal_status_runing = true;
      }


      // 檢查會員是否已提出過提款申請及銀行帳戶資料是否齊全
      if($withdrawal_status_runing == false AND $bankdata_isnull == false) {

        // 檢查代幣是否在娛樂城
        // if ($g[1]->gtoken_lock == NULL) {

          // 檢查是否有餘額且大於最小提款限額
          if ($g[1]->gtoken_balance != 0 AND $g[1]->gtoken_balance >= $member_grade_config_detail->withdrawallimits_lower) {
            // 即時稽核相關資訊
            // $auditorial_data = get_auditorial_data($_SESSION['member']->account);
            $auditorial_data = get_auditorial_data($_SESSION['member']);

            $withdrawal_data_html = '';

            //帳戶餘額
            if($config['site_style']=='mobile'){
            $withdrawal_data_html = $withdrawal_data_html.'
            <div class="shadow-sm rounded-top withdrawapplication_list">
            <div class="d-flex bd-highlight withd_cash border-bottom">
              <div class="flex-fill bd-highlight">'.$tr['account balance'].'</div>
              <div class="flex-fill bd-highlight text-right">'.$_SESSION['member']->gtoken_balance.'</div>
            </div>
            </div>';
            }else{
             $withdrawal_data_html = $withdrawal_data_html.'
            <tr>
              <td><span></span>'.$tr['account balance'].'</td>
              <td id="wallet_balance">'.$_SESSION['member']->gtoken_balance.'</td>
            </tr>';
            }
            // 預設取得所有的代幣 GTOKEN 餘額，不可以提款 GCASH 存款
            $withdrawal_balance = $g[1]->gtoken_balance;


            //取款限額
            if($config['site_style']=='mobile'){
            $withdrawal_data_html = $withdrawal_data_html.'
            <div class="shadow-sm rounded-bottom withdrawapplication_list">
            <div class="d-flex bd-highlight withd_cash border-bottom">
              <div class="flex-fill bd-highlight">'.$tr['withdrawal limit'].'</div>
              <div class="flex-fill bd-highlight text-right">$'.$member_grade_config_detail->withdrawallimits_lower.' ~ $'.$member_grade_config_detail->withdrawallimits_upper.'</div>
            </div>
            </div>';
            }else{
            $withdrawal_data_html = $withdrawal_data_html.'
            <tr>
              <td><span></span>'.$tr['withdrawal limit'].'</td>
              <td>$'.$member_grade_config_detail->withdrawallimits_lower.' ~ $'.$member_grade_config_detail->withdrawallimits_upper.'</td>
            </tr>';  
            }

            //取款金額
            if($config['site_style']=='mobile'){
            // $tr['Need to be an integer (pre-charge)'] = '需為整數(預先收取)';
            $withdrawal_data_html = $withdrawal_data_html.'
            <div class="shadow-sm rounded withdrawapplication_list mt-10">
            <div class="d-flex flex-column bd-highlight p-2">
              <div class="p-2 bd-highlight"><span>*</span>'.$tr['withdrawal money'].'('.$tr['Need to be an integer'].')'.'</div>
              <div class="p-2 bd-highlight">
                <div class="form-group mb-0">
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text">$</span>
                    </div>
                    <input type="text" class="form-control" id="wallet_withdrawal_amount" value="" placeholder="ex: 200">
                    <div class="input-group-append">
                      <span class="input-group-text">.00</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            </div>
            ';
            }else{
            $withdrawal_data_html = $withdrawal_data_html.'
            <tr>
              <td><span class="text-danger">*</span>'.$tr['withdrawal money'].'('.$tr['Need to be an integer'].')'.'</td>
              <td>
                <div class="form-group">
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text">$</span>
                    </div>
                    <input type="text" class="form-control" id="wallet_withdrawal_amount" value="" placeholder="ex: 200">
                    <div class="input-group-append">
                      <span class="input-group-text">.00</span>
                    </div>
                  </div>
                </div>
              </td>
            </tr>
            ';
            }

            // 取款手續費 n %
            if($member_grade_config_detail->withdrawalfee_method != 1) {
              $withdrawalfee = $member_grade_config_detail->withdrawalfee;
            } else {
              $withdrawalfee = 0;
            }
            
            //取款手續費
            // $tr['GTOKEN withdrawal free']
            // $tr['Withdrawal administrative fees'] = '取款行政手續費用';
            // $tr['withdraw_money1'] = '現金';
            if($config['site_style']=='mobile'){
            $withdrawal_data_html = $withdrawal_data_html.'
            <div class="shadow-sm withdrawapplication_list">
            <div class="d-flex bd-highlight withd_cash border-bottom">
              <div class="flex-fill bd-highlight">
                  <div class="withdrawapplication_information">
                  '.$tr['Withdrawal fee'].'('.$withdrawalfee.'%)
                  </div>
              </div>
              <div class="flex-fill bd-highlight text-right" id="wallet_withdrawal_fee_balance">$</div>
            </div>
            </div>';
            }else{
            $withdrawal_data_html = $withdrawal_data_html.'
            <tr>
              <td><span></span>'.$tr['Withdrawal fee'].'('.$withdrawalfee.'%)</td>
              <td id="wallet_withdrawal_fee_balance"></td>
            </tr>'; 
            }

            //取款行政費用(稽核不通過收取)
            // $tr['GTOKEN withdrawal administrative free']
            // $tr['Withdrawal of administrative audit fees'] = '取款行政稽核費用';
            // $tr['withdraw_money1'] = '現金';
            if($config['site_style']=='mobile'){
            $withdrawal_data_html = $withdrawal_data_html.'
            <div class="shadow-sm withdrawapplication_list">
            <div class="d-flex bd-highlight withd_cash border-bottom">
              <div class="flex-fill bd-highlight">
                <div class="withdrawapplication_information">
                '.$tr['withdraw_money1'].$tr['Withdrawal of administrative audit fees'].'
                </div>
              </div>
              <div class="flex-fill bd-highlight text-right text-danger" id="wallet_withdrawal_adminfee_balance">$'.$auditorial_data['total_withdrawal_fee'].'</div>
            </div>
            </div>';
            }else{
            $withdrawal_data_html = $withdrawal_data_html.'
            <tr>
              <td><span></span>'.$tr['withdraw_money1'].$tr['Withdrawal of administrative audit fees'].'</td>
              <td id="wallet_withdrawal_adminfee_balance" class="text-danger">$&nbsp;'.$auditorial_data['total_withdrawal_fee'].'</td>
            </tr>';
            }

            // 優惠扣除額
            // $tr['Discount deduction'] = '優惠扣除額';
            if($config['site_style']=='mobile'){
            $withdrawal_data_html = $withdrawal_data_html.'
             <div class="shadow-sm withdrawapplication_list">
            <div class="d-flex bd-highlight withd_cash border-bottom">
              <div class="flex-fill bd-highlight">
                <div class="withdrawapplication_information">
                '.$tr['Discount deduction'].'
                </div>
              </div>
              <div class="flex-fill bd-highlight text-right text-danger" id="wallet_withdrawal_adminfee_balance">$'.$auditorial_data['total_offer_deduction_amount'].'</div>
            </div>
            </div>';
            }else{
            $withdrawal_data_html = $withdrawal_data_html.'
            <tr>
              <td><span></span>'.$tr['Discount deduction'].'</td>
              <td id="offer_deduction" class="text-danger">$&nbsp;'.$auditorial_data['total_offer_deduction_amount'].'</td>
            </tr>';
            }

            //提交預先扣除的金額
            // $tr['The balance deducted before the withdrawal application is submitted'] = '取款申請提交時預先扣除的餘額';
            if($config['site_style']=='mobile'){
            $withdrawal_data_html = $withdrawal_data_html.'
            <div class="shadow-sm rounded-bottom withdrawapplication_list">
            <div class="d-flex bd-highlight withd_cash border-bottom">
              <div class="flex-fill bd-highlight">
                <div class="withdrawapplication_information">
                '.$tr['Total deduction'].'
                </div>
              </div>
              <div class="flex-fill bd-highlight text-right" id="wallet_withdrawalwithfee_result"></div>
            </div>
            </div>';
            }else{
            $withdrawal_data_html = $withdrawal_data_html.'
            <tr>
              <td><span></span>'.$tr['Total deduction'].'</td>
              <td id="wallet_withdrawalwithfee_result"></td>
            </tr>';
            }

            //取款後帳戶餘額
            if($config['site_style']=='mobile'){
            $withdrawal_data_html = $withdrawal_data_html.'
            <div class="shadow-sm rounded-top withdrawapplication_list mt-10">
            <div class="d-flex bd-highlight withd_cash border-bottom">
              <div class="flex-fill bd-highlight">
                <div class="withdrawapplication_information">
                '.$tr['after withdrawal balance'].'
                </div>
              </div>
              <div class="flex-fill bd-highlight text-right" id="wallet_withdrawal_balance">$</div>
            </div>
            </div>';
            }else{
            $withdrawal_data_html = $withdrawal_data_html.'
            <tr>
              <td><span></span>'.$tr['after withdrawal balance'].'</td>
              <td id="wallet_withdrawal_balance"></td>
            </tr>';
            }  
                      
            // 出款方式
            if($config['site_style']=='mobile'){
            $withdrawal_data_html = $withdrawal_data_html.'
            <div class="shadow-sm rounded withdrawapplication_list mt-10">
              <div class="d-flex flex-column bd-highlight">
                <div class="p-2 bd-highlight"><span>*</span>'.$tr['withdraw_method'].'</div>
                <div class="p-2 bd-highlight">
                  <div class="withdraw_method">                  
                  <label class="form-check-inline">
                    <input type="radio" class="withdraw_method" name="withdraw_method" id="withdraw_method_tobank" value="0">'.$tr['withdraw_method_tobank'].'
                  </label>
                  <label class="form-check-inline">
                    <input type="radio" class="withdraw_method" name="withdraw_method" id="withdraw_method_togcash" value="1">'.$tr['withdraw_method_togcash'].'
                  </label>
                  </div>
                  </div>
              </div>
            </div>';
            }else{
            $withdrawal_data_html = $withdrawal_data_html.'
            <tr>
              <td><span class="text-danger">*</span>'.$tr['withdraw_method'].'</td>
              <td>
                <label class="form-check-inline">
                  <input type="radio" class="withdraw_method" name="withdraw_method" id="withdraw_method_tobank" value="0">'.$tr['withdraw_method_tobank'].'
                </label>
                <label class="form-check-inline">
                  <input type="radio" class="withdraw_method" name="withdraw_method" id="withdraw_method_togcash" value="1">'.$tr['withdraw_method_togcash'].'
                </label>
              </td>
            </tr>';
            }

            //取款密碼
            // 檢查預設的取款密碼是否已經修改。
            $default_withdrawal_password = $system_config['withdrawal_default_password'];
            if(sha1($default_withdrawal_password) == $g[1]->withdrawalspassword) {
              //預設提款密碼未變更，請立即變更
              // $withdrawal_password_change_tip_html = '<a href="member.php" target="_BLANK" title="'.$tr['default withdrawal password'].' '.$default_withdrawal_password.' '.$tr['change immediately'].'"><img src="'.$cdnrooturl.'warning.png" height="20" /></span></a>';
              $withdrawal_password_change_tip_html = '<p class="text-danger">('.$tr['default withdrawal password'].$tr['withdraw_no_change'].'，'.$tr['change immediately'].')</p>';
            } else {
              $withdrawal_password_change_tip_html  = '';
            }
            // 取款密碼
            if($config['site_style']=='mobile'){
            $withdrawal_data_html = $withdrawal_data_html.'
            <div class="shadow-sm rounded withdrawapplication_list mt-10">
            <div class="d-flex flex-column bd-highlight">
              <div class="p-2 bd-highlight"><span>*</span>'.$tr['withdrawal password'].$withdrawal_password_change_tip_html.'</div>
              <div class="p-2 bd-highlight">
                <input type="password" class="form-control" id="withdrawal_password" placeholder="'.$tr['withdrawal password'].'">
              </div>
            </div>
            </div>';
            }else{
              $withdrawal_data_html = $withdrawal_data_html.'
            <tr>
              <td><span class="text-danger">*</span>'.$tr['withdrawal password'].$withdrawal_password_change_tip_html.'</td>
              <td>
                <div class="position-relative">
                  <input type="password" class="form-control" id="withdrawal_password" placeholder="'.$tr['withdrawal password'].'">
                </div>
              </td>
            </tr>';
            }


            //修正帳戶資訊
            $submit_to_withdrawal_btn = '
            <button type="button" class="send_btn bg-primary mt-10" id="submit_to_withdrawal">'.$tr['withdrawal apply submit'].'</button>
            ';

            if($config['site_style']=='mobile'){
            $form_html = $audit_html.'
            <form id="step1_bank_select" method="post">
              '.$withdrawal_data_html.'
              '.$submit_to_withdrawal_btn.'
            </form>
            ';
            }else{
            $form_html = $audit_html.'
            <form id="step1_bank_select" class="mt-3" method="post">
              <div class="game_cu_awal deposit-card">
                <div class="de_companytitle">'.$tr['withdrawal money'].'</div>
                <div class="card-body">
                <table class="table arc_style gcash_style">
                  <thead></thead>
                  <tbody>
                    '.$withdrawal_data_html.'
                  </tbody>
                </table>
                </div>
                '.$submit_to_withdrawal_btn.'                
              </div>
            </form>
            ';
            }



            //取款申請送出
            // 申請送出寫到表格

            // 加密函式密碼
            // var password_input  = $().crypt({method:'sha1', source:$('#password_input').val()});
            $extend_head = '<script src="'.$cdnfullurl_js.'jquery.crypt.js"></script>';

            // (1) wallet_withdrawal_amount 取得餘額數值，運算後填入欄位。
            // (2) submit_to_withdrawal 把提款餘額參數寫入申請表
            $extend_js = "
            <script>
              $(document).ready(function() {
                // wallet_withdrawal_amount
                $('#wallet_withdrawal_amount').keyup(function(){
                  var wallet_balance = ".$withdrawal_balance.";
                  var wallet_withdrawal_amount = parseInt($('#wallet_withdrawal_amount').val());

                  var administrative_amount = ".$auditorial_data['total_withdrawal_fee'].";
                  var offer_deduction = '".$auditorial_data['total_offer_deduction_amount']."';

                  var withdrawalfee = ".($withdrawalfee/100).";
                  var withdrawalfee_balance = Math.round(wallet_withdrawal_amount*withdrawalfee*100)/100;

                  if(".$member_grade_config_detail->withdrawalfee_method." != 1) {
                    if(withdrawalfee_balance < 1) {
                      var withdrawalfee_balance = 1;
                    }

                    if(withdrawalfee_balance > ".$member_grade_config_detail->withdrawalfee_max.") {
                      var withdrawalfee_balance = ".$member_grade_config_detail->withdrawalfee_max.";
                    }
                  } else {
                    var withdrawalfee_balance = 0;
                  }

                  var wallet_withdrawalwithfee_result  = Math.round((parseFloat(wallet_withdrawal_amount) + parseFloat(withdrawalfee_balance) + parseFloat(offer_deduction) + parseFloat(administrative_amount)) * 100) / 100;
                  var wallet_withdrawalwithfee_balance = Math.round((parseFloat(wallet_balance) - parseFloat(wallet_withdrawalwithfee_result)) * 100) / 100;

                  var wallet_withdrawal_balance = Math.round((wallet_balance - wallet_withdrawalwithfee_result)*100)/100;

                  // in member level range
                  if(wallet_withdrawal_amount >= ".$member_grade_config_detail->withdrawallimits_lower." && wallet_withdrawal_amount <= ".$member_grade_config_detail->withdrawallimits_upper.") {
                    if(wallet_withdrawalwithfee_balance >= 0 ) {
                      var wallet_withdrawal_balance_html = '$&nbsp;' + wallet_withdrawal_balance;
                      var withdrawalfee_html = '$&nbsp;' + (withdrawalfee_balance) ;
                      var wallet_withdrawalwithfee_result_html = '$&nbsp;' + (wallet_withdrawalwithfee_result) + '&nbsp;(".$tr['Withdrawal amount']." + ".$tr['Administrative fee']." + ".$tr['Audit fee']." + ".$tr['Discount deduction'].")';
                    }else{
                      //餘額金額小於零或輸入錯誤，提款金額不可以為浮點數。
                      var wallet_withdrawal_balance_html = '$' + wallet_withdrawalwithfee_balance + '<p class=\'text-danger\'>".$tr['balance less than 0']."</p>';
                    }
                  }else{
                    //取款金額不在範圍內
                    var wallet_withdrawal_balance_html = '$' + wallet_withdrawalwithfee_balance + '<p class=\'text-danger\'>".$tr['withdrawal not in range']."</p>';
                  }

                  $('#wallet_withdrawal_balance').html(wallet_withdrawal_balance_html);
                  $('#wallet_withdrawal_fee_balance').html(withdrawalfee_html);
                  $('#wallet_withdrawalwithfee_result').html(wallet_withdrawalwithfee_result_html);
                });



                // submit_to_withdrawal
                $('#submit_to_withdrawal').click(function(){
                  var withdrawal_password = $('#withdrawal_password').val();
                  var wallet_balance = ".$withdrawal_balance.";
                  var wallet_withdrawal_amount = parseInt($('#wallet_withdrawal_amount').val());
                  var withdraw_method = $('input[class=withdraw_method]:checked').val();
                  var csrftoken = '$csrftoken';

                  if(wallet_withdrawal_amount >= ".$member_grade_config_detail->withdrawallimits_lower." && wallet_withdrawal_amount <= ".$member_grade_config_detail->withdrawallimits_upper.") {

                    //請將底下所有 * 欄位資訊填入，並確認提款後餘額大於 0
                    if(jQuery.trim(withdrawal_password) == '' || jQuery.trim(wallet_withdrawal_amount) == ''){
                      alert('".$tr['fill the all * and after with drawal balance more than 0']."');
                    }else if(jQuery.trim(withdraw_method) == ''){
                      alert('".$tr['Please choose the correct payment method.']."');
                    }else{
                      var withdrawal_password_sha1  = $().crypt({method:'sha1', source:$('#withdrawal_password').val()});
                      $('#submit_to_withdrawal').attr('disabled', 'disabled');
                      if(confirm('".$tr['Please confirm whether the information']."') == true){
                        $.post('withdrawapplication_action.php?a=submit_to_withdrawal',
                          {
                            withdrawal_password_sha1: withdrawal_password_sha1,
                            wallet_withdrawal_amount: wallet_withdrawal_amount,
                            withdraw_method: withdraw_method,
                            csrftoken: csrftoken
                          },
                          function(result){
                            $('#preview_area').html(result);
                          }
                        );
                      }else{
                        window.location.reload();
                      }

                    }

                  }else{
                    //取款金額不在範圍內，請重新輸入。
                    alert('".$tr['withdrawal not in range input again']."');
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

          } else {
            $preview_status_html = '';
            $form_html = '
            <div class="alert alert-danger mt-10" role="alert">
            '.$tr['Insufficient balance'].'
            </div>';
          }


        // } else {
        //   $preview_status_html = '';
        //   $form_html = '
        //   <div class="alert alert-danger" role="alert">
        //   您尚有現金存在娛樂城<br>
        //   煩請先至 會員中心 -> 會員錢包 將娛樂城現金取回後再行操作。<br>
        //   您可以 <a href="./wallets.php">點擊此處</a> 前往會員錢包頁面。
        //   </div>';
        // }

      } else {
        $preview_status_html = '';
        $form_html = $show_withdrawal_html;
      }

    } else {
      // (x) 查無使用者錢包資訊。
      $preview_status_html = '';
      $form_html = '(x) '.$tr['Check no user wallet information'];
    }

  } else {
    $msg = ($member_grade_config_detail->withdrawal_allow == 0) ? $tr['off'] : $tr['maintain'];
    $form_html = '(x) '.$tr['cash withdrawal'].$msg.'，'.$tr['If you have any questions, please contact us.'];
  }

} else {
	// 不合法登入者的顯示訊息
  //(x) 請先登入會員，才可以使用此功能。
	$form_html = $tr['login first'];
}

if($config['site_style']=='desktop'){
  $back_btn = '<a class="btn btn-secondary withdrawap back_prev" href="./wallets.php" role="button"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>'.$tr['back to list'].'</a>';
}else{
  $back_btn = '';
}

// 切成 3 欄版面
$indexbody_content = $indexbody_content.'
<div class="row">
  <div class="col-12">
  '.$preview_status_html.'
  '.$form_html.'
  '.$back_btn.'
  </div>
</div>
<br>
<div id="preview_area"></div>
';


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
$tmpl['banner'] = ['membercenter_wallets'];
// menu增加active
$tmpl['menu_active'] =['deposit.php'];

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include($config['template_path']."template/admin.tmpl.php");

?>