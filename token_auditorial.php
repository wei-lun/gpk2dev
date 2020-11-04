<?php
// ----------------------------------------------------------------------------
// Features:	後台--GTOKEN即時稽核資料表
// File Name:	token_auditorial.php
// Author:		Yuan
// Related:   對應 member_account.php 即時稽核連結功能
// Log:
//
// ----------------------------------------------------------------------------



// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";
// 即時稽核 lib
require_once dirname(__FILE__) ."/token_auditorial_lib.php";

// ----------------------------------------------------------------------------
// 只要 session 活著,就要同步紀錄該 agent user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------


// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
// $tr['token_auditorial_title'] = '即時稽核';
$function_title 		= $tr['token_auditorial_title'];
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
// $tr['withdrawapplication.php'] = '現金取款';
// 導覽列
$navigational_hierarchy_html = '
<ul class="breadcrumb">
  <li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
  <li><a href="member.php">'.$tr['Member Centre'].'</a></li>
  <li><a href="wallets.php">'.$tr['wallets title'].'</a></li>
  <li><a href="withdrawapplication.php">'.$tr['withdrawapplication.php'].'</a></li>
  <li class="active">'.$function_title.'</li>
</ul>
';
if($config['site_style']=='mobile'){
    $navigational_hierarchy_html =<<<HTML
    <a href="{$config['website_baseurl']}withdrawapplication.php"><i class="fas fa-chevron-left"></i></a>
    <span>{$function_title}</span>
    <i></i>
HTML;
}
// ----------------------------------------------------------------------------

function auditorial_html($auditorial_details)
{
  global $auditmode_select;
  global $config;
  global $tr;

  $html = '';

  foreach ($auditorial_details as $k => $v) {
    if ($v['audit_status'] == '1') {
      $audit_amount = '(无)';
      $is_audit_message = '<span class="glyphicon glyphicon-ok text-success" aria-hidden="true"></span>&nbsp;('.$v['audit_amount'].')';
    } else {
      $audit_amount = ($v['audit_method'] == 'shippingaudit') ? '$ '.$v['offer_deduction_amount'] : '$ '.$v['withdrawal_fee'];
      $is_audit_message = '<span class="glyphicon glyphicon-remove text-danger" aria-hidden="true"></span>&nbsp;('.$v['afterdeposit_bet'].' / '.$v['audit_amount'].')';
    }

    $howlongago = get_howlongago($v['deposit_time1']);

    $audit_method = $auditmode_select[$v['audit_method']];

    $sdate = gmdate('Y-m-d H:i',strtotime($v['deposit_time1']) + -4*3600);
    $edate = gmdate('Y-m-d H:i',strtotime($v['deposit_time2']) + -4*3600);

    if($config['site_style']=='mobile'){
    $html .= <<<HTML
    <tr class="rounded shadow-sm">
      <td class="gid" name="gid">
        <div class="d-flex bd-highlight">
        <div class="flex-fill bd-highlight text-left">{$tr['seq']}</div>
        <div class="flex-fill bd-highlight text-right">{$v['gtoken_id']}</div>
      </div>
      </td>
      <td>
        <div class="d-flex bd-highlight">
          <div class="flex-fill bd-highlight text-left">{$tr['time']}</div>
          <div class="flex-fill bd-highlight text-right">{$v['deposit_time1']}<p class="text-muted">({$howlongago})</p></div>
        </div>
      </td>
      <td class="text-center">
         <div class="d-flex bd-highlight">
          <div class="flex-fill bd-highlight text-left">{$tr['Deposit amount']}</div>
          <div class="flex-fill bd-highlight text-right">$ {$v['deposit_amount']}</div>
        </div>
      </td>
      <td class="text-center">
        <div class="d-flex bd-highlight">
          <div class="flex-fill bd-highlight text-left">{$tr['Deposits Balance']}</div>
          <div class="flex-fill bd-highlight text-right">$ {$v['deposit_balance']}</div>
        </div>
      </td>
      <td>
        <div class="d-flex bd-highlight">
          <div class="flex-fill bd-highlight text-left">{$tr['audit method']}</div>
          <div class="flex-fill bd-highlight text-right">{$audit_method}</div>
        </div>
      </td>
      <td>
        <div class="d-flex bd-highlight">
          <div class="flex-fill bd-highlight text-left">{$tr['deposit Betting money']}</div>
          <div class="flex-fill bd-highlight text-right">{$is_audit_message}</div>
        </div>
      </td>
      <td class="text-center">
        <div class="d-flex bd-highlight">
          <div class="flex-fill bd-highlight text-left">{$tr['audit amount']}</div>
          <div class="flex-fill bd-highlight text-right">{$audit_amount}</div>
        </div>
      </td>
    </tr>
HTML;
}else{
    $html .= <<<HTML
    <tr>
      <td class="gid" name="gid">{$v['gtoken_id']}</td>
      <td>
      {$v['deposit_time1']}
      <p class="text-muted">({$howlongago})</p>
      </td>
      <td class="text-center">$ {$v['deposit_amount']}</td>
      <td class="text-center">$ {$v['deposit_balance']}</td>
      <td>{$audit_method}</td>
      <td>{$is_audit_message}</td>
      <td class="text-center">{$audit_amount}</td>
    </tr>
HTML; 
}
  }

  return $html;
}

// 有登入，且身份不適測試帳號才可以使用。
if(isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T') {

  if($config['site_style']=='desktop'){
    $back_btn = '<a class="btn btn-secondary back_prev" href="./withdrawapplication.php" role="button"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>'.$tr['back to list'].'</a>';
  }else{
    $back_btn = '';
  }

   // 登入的使用者需為有效會員
  if ($_SESSION['member']->status == '1') {

    // 即時稽核相關資訊
    $auditorial_data = get_auditorial_data($_SESSION['member']);

    if ($auditorial_data['withdraw_data'] != null) {
      $withdraw_lasttime = $auditorial_data['withdraw_data']->processing_time;
      $withdraw_lasttime_howlongago = '('.get_howlongago($withdraw_lasttime).')';
      $withdraw_amount = money_format('%i', $auditorial_data['withdraw_data']->amount);

      $gtokenpassbook_balance_sql = "SELECT balance FROM root_member_gtokenpassbook WHERE destination_transferaccount = '$gtoken_cashier_account' AND source_transferaccount = '".$_SESSION['member']->account."' AND to_char((transaction_time AT TIME ZONE '$tzonename'),'YYYY-MM-DD HH24:MI:SS') = '".$auditorial_data['withdraw_data']->processing_time."' ORDER BY id DESC";
      $gtokenpassbook_balance_sql_result = runSQLall($gtokenpassbook_balance_sql);
      // $tr['Invoice balance inquiry failed'] = '提款餘額查詢失敗';
      $withdraw_balance = $gtokenpassbook_balance_sql_result[0] >= 1 ? money_format('%i', $gtokenpassbook_balance_sql_result[1]->balance) : $tr['Invoice balance inquiry failed'];

    } else {
      $withdraw_lasttime = '-';
      $withdraw_lasttime_howlongago = '';
      $withdraw_amount = money_format('%i', 0);
      $withdraw_balance = money_format('%i', 0);
    }
    
    // 提示使用者會員提款資料
    // $tr['Query member account']  = '查詢會員帳號';
    // $tr['Last withdrawal time']= '最後提款時間';
    // $tr['Last withdrawal amount']= '最後提款金額';
    // $tr['The balance of the last withdrawal'] = '最後提款餘額';
    // $tr['The details of deposits are shown in the following table'] = '存款明細如下表';
    if($config['site_style']=='mobile'){
      $member_withdraw_tips_html = '
      <div class="row mt-10">
        <div class="col-12">
        <div class="shadow-sm rounded withd_cash_list">        
          <div class="d-flex bd-highlight withd_cash border-bottom">
            <div class="flex-fill bd-highlight">
              '.$tr['Last withdrawal time'].' :
            </div>
            <div class="flex-fill bd-highlight text-right">
              '.$withdraw_lasttime.'<br/>
              <small class="text-muted">'.$withdraw_lasttime_howlongago.'</small>
            </div>
          </div>
          <div class="d-flex bd-highlight withd_cash">
            <div class="flex-fill bd-highlight">
              '.$tr['Query member account'].' :
            </div>
            <div class="flex-fill bd-highlight text-right">
              '.$_SESSION['member']->account.'
            </div>
          </div>
          <div class="d-flex bd-highlight withd_cash">
            <div class="flex-fill bd-highlight">
              '.$tr['Last withdrawal amount'].' :
            </div>
            <div class="flex-fill bd-highlight text-right">
              '.$withdraw_amount.'
            </div>
          </div>
          <div class="d-flex bd-highlight withd_cash">
            <div class="flex-fill bd-highlight">
              '.$tr['The balance of the last withdrawal'].' :
            </div>
            <div class="flex-fill bd-highlight text-right">
              '.$withdraw_balance.'
            </div>
          </div>
        </div>
        </div>
      </div>
      ';
      }else{
    $member_withdraw_tips_html = '
    <div class="token_au_content">
        <div class="row">
          <div class="col-12 col-md-2" >
           '.$tr['Query member account'].' : 
          </div>
          <div class="col-12 col-md-10">
            '.$_SESSION['member']->account.'
          </div>
        </div>
        <div class="row">
        <div class="col-12 col-md-2">
         '.$tr['Last withdrawal time'].' : 
        </div>
        <div class="col-12 col-md-10">
          '.$withdraw_lasttime.' <small class="text-muted">'.$withdraw_lasttime_howlongago.'</small>
        </div>
      </div>
      <div class="row">
        <div class="col-12 col-md-2">
         '.$tr['Last withdrawal amount'].' : 
        </div>
        <div class="col-12 col-md-10">
          '.$withdraw_amount.'
        </div>
      </div>
      <div class="row">
        <div class="col-12 col-md-2">
         '.$tr['The balance of the last withdrawal'].' : 
        </div>
        <div class="col-12 col-md-10">
          '.$withdraw_balance.'
        </div>
      </div>
      <div class="row">
        <div class="col-12 col-md-2">
          '.$tr['The details of deposits are shown in the following table'].' : 
        </div>
      </div>
    </div>
    '.$back_btn;
    }

    if ($auditorial_data['auditorial_details'] != null) {
      $show_listrow_html = auditorial_html($auditorial_data['auditorial_details']);

      // 表格欄位名稱
      // $tr['seq']          = '單號';
      // $tr['time']         = '時間';
      // $tr['Deposit amount']= '存款金額';
      // $tr['Deposits Balance']= '存款餘額';
      // $tr['audit method'] = '稽核方式';
      // $tr['audit amount'] = '稽核金額';
      // $tr['Deposit audit (deposit after betting amount']= '存款稽核(存款後投注額 / 稽核金額)';
      $table_colname_html = '
        <tr>
          <th>'.$tr['seq'].'</th>
          <th>'.$tr['time'].'</th>
          <th>'.$tr['Deposit amount'].'</th>
          <th>'.$tr['Deposits Balance'].'</th>
          <th>'.$tr['audit method'].'</th>
          <th>'.$tr['deposit Betting money'].'</th>
          <th>'.$tr['audit amount'].'</th>
        </tr>
        ';

      // enable sort table
      $sorttablecss = ' id="show_list"  class="display" cellspacing="0" width="100%" ';

      // 列出資料, 主表格架構
      $show_list_html = $member_withdraw_tips_html;
      $show_list_html = $show_list_html.'
      <div class="h-100">
        <table id="show_list" class="table token_list" cellspacing="0" width="100%">
        <thead>
        '.$table_colname_html.'
        </thead>
        <tbody>
        '.$show_listrow_html.'
        </tbody>
        </table>
        </div>
        ';

      // 參考使用 datatables 顯示
      // https://datatables.net/examples/styling/bootstrap.html
      $extend_head = $extend_head.'
        <link rel="stylesheet" type="text/css" href="'.$cdnfullurl_js.'datatables/css/jquery.dataTables.min.css">
        <script type="text/javascript" language="javascript" src="'.$cdnfullurl_js.'datatables/js/jquery.dataTables.min.js"></script>
        <script type="text/javascript" language="javascript" src="'.$cdnfullurl_js.'datatables/js/dataTables.bootstrap.min.js"></script>
        ';

      // DATA tables jquery plugging -- 要放在 head 內 不可以放 body
      $extend_head = $extend_head.'
        <script type="text/javascript" language="javascript" class="init">
          $(document).ready(function() {
            $("#show_list").DataTable({
                "paging":   true,
                "ordering": true,
                "info":     true,
                "order": [[ 1, "desc" ]],
                "info":     false,
                language: { 
                  search: "search",
                  "lengthMenu": "show _MENU_",
                  "oPaginate": {
                    "sPrevious": "«",
                    "sNext": "»"
                  }
                },
                "pageLength": 30
            });
          });
        </script>
        ';

      // 即時編輯工具 ref: https://vitalets.github.io/x-editable/docs.html#gettingstarted
      $extend_head = $extend_head.'
        <!-- x-editable (bootstrap version) -->
        <link href="'.$cdnfullurl_js.'bootstrap3-editable/css/bootstrap-editable.css" rel="stylesheet"/>
        <script src="'.$cdnfullurl_js.'bootstrap3-editable/js/bootstrap-editable.min.js"></script>
        ';

      // 切成 1 欄版面
      $indexbody_content = '';
      $indexbody_content = $indexbody_content.'
        <div class="row">
          <div class="col-12">
          '.$show_list_html.'
          </div>
        </div>
        <br>
        <div class="row">
          <div id="preview_result"></div>
        </div>
        ';



    } else {
      // $tr['No audit details']= '無任何稽核詳細資訊。';
      $show_transaction_list_html  = '
      <div id="preview_area" class="alert alert-info mt-3" role="alert">
      '.$tr['No audit details'].'
      </div>';

      // 切成 1 欄版面
      $indexbody_content = $member_withdraw_tips_html;
      $indexbody_content = 
      '<div class="row">
        <div class="col-12">
          '.$show_transaction_list_html.'
        </div>
      </div>'.$indexbody_content.'
      <br>
      <div class="row">
        <div id="preview_result"></div>
      </div>
      ';
    }


  } else {
    // 沒有登入的顯示提示俊息
    // $tr['query member error'] = '(x) 查詢的會員帳號錯誤或無效，請確認後重新輸入。';
    $show_transaction_list_html  = '(x) '.$tr['query member error'];

    // 切成 1 欄版面
    $indexbody_content = '';
    $indexbody_content = $indexbody_content.'
    <div class="row">
      <div class="col-12">
      '.$show_transaction_list_html.'
      </div>
    </div>
    <br>
    <div class="row">
      <div id="preview_result"></div>
    </div>
    ';
  }
} else {
  // 沒有登入的顯示提示俊息
  // $tr['Only authorized members can log in to watch']= ' 只有管理員或有權限的會員才可以登入觀看。'
  $show_transaction_list_html  = '(x) '.$tr['Only authorized members can log in to watch'];

  // 切成 1 欄版面
  $indexbody_content = '';
  $indexbody_content = $indexbody_content.'
	<div class="row">
	  <div class="col-12">
	  '.$show_transaction_list_html.'
	  </div>
	</div>
	<br>
	<div class="row">
		<div id="preview_result"></div>
	</div>
	';
}

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

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include($config['template_path']."template/admin.tmpl.php");


?>
