<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 代理商會員錢包轉帳給其他會員
// 1. 代理商會員可以轉帳給其他會員（此功能只有代理商權限的會員才可以看到）
// File Name:	member_agentdepositgcash.php
// Author:		Barkley
// Related:   member_agentdepositgcash_action.php
// Log:
// 1.隱藏表格標題<div class="panel-heading">'.$title_html.'</div>
// ----------------------------------------------------------------------------



// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

// var_dump($_SESSION);

// var_dump(session_id());
// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------


// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
//代理商會員錢包轉帳給其他會員
$function_title = $tr['member wallet to ohter'];
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
  <li><a href="member.php">'.$tr['Member Centre'].'</a></li>
  <li><a href="agencyarea.php">'.$tr['agencyarea title'].'</a></li>
  <li class="active">'.$function_title.'</li>
</ul>
';
// ----------------------------------------------------------------------------

// 代理商指定, 帶入需要轉帳的目標 id 值
if(isset($_GET['i'])) {
  $dest_account_id = filter_var($_GET['i'], FILTER_VALIDATE_INT);
} else {
  $dest_account_id = null;
}

function get_member_data($id)
{
  $sql = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.id = '".$id."' AND root_member.status = '1';";
  $sql_result = runSQLall($sql);

  $data = ($sql_result[0] == 1) ? $sql_result[1] : null;

  return $data;
}


// 導覽列
$navigational_hierarchy_html =<<<HTML
	<ul class="breadcrumb">
		<li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
		<li><a href="member.php">{$tr['Member Centre']}</a></li>
		<li class="active">{$function_title}</li>
	</ul>
HTML;

if($config['site_style']=='mobile'){
  	$navigational_hierarchy_html =<<<HTML
		<a href="{$config['website_baseurl']}member_management.php"><i class="fas fa-chevron-left"></i></a>
		<span>{$function_title}</span>
		<i></i>
HTML;
}

// 有登入，有錢包才顯示。需要代理商才可以登入 , 管理員不可以使用
if(isset($_SESSION['member']) AND ($_SESSION['member']->therole == 'A') ) {
// --------------------


  // 功能及操作的說明
  $wallets_content_html = '';


  // -------------------------------------------------------------------
  //最上面的選單索引 -- 加盟聯營協助註冊  加盟聯營股東會員轉帳 我的組織 代理收入摘要
  // -------------------------------------------------------------------
  /*
  $button_group_menu = '
  <br>
  <div class="btn-group btn-group-justified" role="group" aria-label="">
    <div class="btn-group" role="group">
      <a href="register.php?r='.$_SESSION['member']->account.'" title="'.$tr['go ahead'].$tr['agency register'].'" target="_SELF" class="btn btn-default" role="button">'.$tr['agency register'].'</a>&nbsp;
    </div>
    <div class="btn-group" role="group">
      <a href="agencyarea.php" target="_SELF" title="'.$tr['go ahead'].$tr['agency member tranfer'].'"  class="btn btn-info" role="button">'.$tr['agency member tranfer'].'</a>&nbsp;
    </div>
    <div class="btn-group" role="group">
      <a href="agencyarea_myorganization.php" target="_SELF" title="'.$tr['go ahead'].$tr['my organization'].'" class="btn btn-default" role="button">'.$tr['my organization'].'</a>&nbsp;
    </div>
    <div class="btn-group" role="group">
      <a href="agencyarea_summary.php" target="_SELF" title="'.$tr['go ahead'].$tr['agemcy income summary'].'" class="btn btn-default" role="button">'.$tr['agemcy income summary'].'</a>&nbsp;
    </div>
  </div>
  <hr>
  <br>
  ';
  */
  // -------------------------------------------------------------------
  // 最上面的選單索引 -- 只有代理商才顯示
  /*
  $wallets_content_html		= $wallets_content_html.'
  <div class="row">
    <div class="col-12">
    '.$button_group_menu.'
    </div>
  </div>
  <br>
  ';
  */
  // -------------------------------------------------------------------

  // 預設登入者本身
  $query_id = $_SESSION['member']->id;

  // 查詢來源帳號的資料 , 確認該帳號 status 還是有效的
  // $sql = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.id = $query_id AND status = '1';";
  // $g = runSQLALL($sql);
  $g = get_member_data($query_id);
  if($g != null) {

    // -------------------------------------------------------------------
    // 如果是代理商的話，提供會員轉帳的功能(代理商限定)
    // -------------------------------------------------------------------

    // 大標
    // $title_html = '<p align="center"><h4><strong><span class="glyphicon glyphicon-transfer" aria-hidden="true"></span>&nbsp;'.$function_title.'</strong></h4></p><hr>';
    $title_html = '<span class="glyphicon glyphicon-transfer" aria-hidden="true"></span>&nbsp;'.$function_title;

    // --------------------------------------------------------------------------

    // --------------------------------------------------------------------------
    // 轉帳目標帳戶
    // 需要為同推薦人或是同推薦人下面的會員才可以互轉 --> $tr['transfer recommender condition']
    // 只有代理商可以转给旗下会员 , 其他关系不可以转帐

    // 從 url GET 指定轉帳目標 ID --> $dest_account_id
    if ($dest_account_id != null) {
      if($config['site_style']=='mobile'){
        $wallets_content_html = $wallets_content_html.'
        <tr class="row d-flex">
          <th class="col-5">'.$tr['transfer source account'].'</th>
          <td id="deposit_source_account" class="col-7">'.$g->account.'</td>
        </tr>
      ';
      }else{
        $wallets_content_html = $wallets_content_html.'
        <tr class="row d-flex">
          <th class="col-2">'.$tr['transfer source account'].'</th>
          <td id="deposit_source_account" class="col">'.$g->account.'</td>
        </tr>
      ';
      }
      

      // --------------------------------------------------------------------------
      if($config['site_style']=='mobile'){
        $wallets_content_html = $wallets_content_html.'
        <tr class="row d-flex">
          <th class="col-5">'.$tr['transfer source account balance'].'</th>
          <td id="deposit_source_account_balance" class="col-7">$'.$g->gcash_balance.'</td>
        </tr>
        ';
      }else{
        $wallets_content_html = $wallets_content_html.'
        <tr class="row d-flex">
          <th class="col-2">'.$tr['transfer source account balance'].'</th>
          <td id="deposit_source_account_balance" class="col">$'.$g->gcash_balance.'</td>
        </tr>
        ';
      }
      

      $select_purpose_data_result = get_member_data($dest_account_id);

      $purpose_acc = ($select_purpose_data_result != null) ? $select_purpose_data_result->account : $tr['transfer target account failed'];//'目的帐号查询失败'

      if($config['site_style']=='mobile'){
        $wallets_content_html = $wallets_content_html.'
        <tr class="row d-flex">
          <th class="col-5">'.$tr['transfer target'].'</th>
          <td id="deposit_dest_account" class="col-7">'.$purpose_acc.'</td>
        </tr>
        ';
      }else{
        $wallets_content_html = $wallets_content_html.'
        <tr class="row d-flex">
          <th class="col-2">'.$tr['transfer target'].'</th>
          <td id="deposit_dest_account" class="col">'.$purpose_acc.'</td>
        </tr>
        ';        
      }

      // --------------------------------------------------------------------------
      if($config['site_style']=='mobile'){
      $wallets_content_html = $wallets_content_html.'
      <tr class="row d-flex">
        <th class="col-5">
          <span class="glyphicon glyphicon-star" aria-hidden="true"></span>
          '.$tr['transfer amount'].'
        </th>
        <td class="col-7 p-0">
          <i class="fas fa-pencil-alt pen_icon"></i>
          <input type="number" class="form-control" id="deposit_dest_account_amount" placeholder="'.$tr['transfer amount'].'" min="1" step="100">
        </td>
      </tr>
      ';
    }else{
      $wallets_content_html = $wallets_content_html.'
      <tr class="row d-flex">
        <th class="col-2">
          <i class="required">*</i>
          '.$tr['transfer amount'].'
        </th>
        <td class="col p-0">
          <input type="number" class="form-control" id="deposit_dest_account_amount" placeholder="'.$tr['transfer amount'].'" min="1" step="100">
        </td>
      </tr>
      ';      
    }

      // --------------------------------------------------------------------------
      // 會員提款的密碼
      // --------------------------------------------------------------------------
      // 預設的提款密碼檢查並提示
      // ------------------------------------------------------------------------
      // 取得系統預設密碼
      $default_withdrawal_password = $system_config['withdrawal_default_password'];
      $default_withdrawal_password_sha1 = sha1($default_withdrawal_password);
      // 判斷密碼是否還是系統預設
      if($default_withdrawal_password_sha1 == $g->withdrawalspassword) {
        //預設提款密碼 請立即變更
        $withdrawal_password_change_tip_html = '<span><span class="glyphicon glyphicon-star" aria-hidden="true"></span>'.$tr['member transfer / withdrawal password'].'<span><a href="member.php" title="'.$tr['default withdrawal password'].''.$default_withdrawal_password.' '.$tr['change immediately'].'"><img src="'.$cdnrooturl.'warning.png" height="20" /></a></span>';
      } else {
        // 已經不是預設提款密碼
        $withdrawal_password_change_tip_html = '<span class="glyphicon glyphicon-star" aria-hidden="true"></span>'.$tr['member transfer / withdrawal password'];
      }

      $btn_html = '
      <button id="deposit_information_send" class="send_btn btn-primary" type="submit">'.$tr['transfer now'].'</button>
      ';

      if($config['site_style']=='desktop'){
        $back_btn = '<a class="btn btn-secondary back_prev" href="./member_management.php" role="button"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>'.$tr['back to list'].'</a>';
      }else{
        $back_btn = '';
      }

      $agent_to_member_deposit_html = '
      <div class="main_content">
        <div class="col-12">
          <table class="table table_form2 table_form2_input agentdepositgcash">
            <thead></thead>
            <tbody>
              '.$wallets_content_html.'
            </tbody>
          </table>
        </div>
        '.$btn_html.'
      </div>
      '.$back_btn.'
      <div id="submit_to_register_result"></div>
      ';

      // --------------------------------------------------------------------------
      // JS 檢查並確認輸入的資料是否正確，正確才繼續 post send 。
      //餘額不足，請檢查你的轉帳金額是否大於餘額。
      //你確認要進行轉帳的動作嗎？
      //放棄轉帳
      //轉帳金額不可小於等於0。
      $extend_js = "
      <script>
        $(document).ready(function() {
          $('#deposit_information_send').click(function() {
            var deposit_source_account = '".$g->account."';
            var deposit_dest_account = '".$purpose_acc."';
            var deposit_dest_account_amount = $('#deposit_dest_account_amount').val();
            var deposit_source_account_balance = Math.floor(".$g->gcash_balance.");

            if(deposit_source_account_balance <= 1 || deposit_dest_account_amount > deposit_source_account_balance) {
              alert('".$tr['balance insufficient']."');
            } else {
              if(jQuery.trim(deposit_dest_account) == '' || jQuery.trim(deposit_dest_account_amount) == '') {
                alert('".$tr['please fill all * field']."');
              } else {
                if(deposit_dest_account_amount <= 0) {
                  alert('".$tr['transfer amount can not be 0']."');
                } else {
                  $.post('member_agentdepositgcash_action.php?a=check_deposit_data',
                    {
                      deposit_source_account_balance: deposit_source_account_balance,
                      deposit_source_account: deposit_source_account,
                      deposit_dest_account: deposit_dest_account,
                      deposit_dest_account_amount: deposit_dest_account_amount,
                    },
                    function(result)
                    {
                      $('#send_result').html(result);
                    }
                  );
                }
              }
            }
          });

          // input-focus
          $('.agentdepositgcash').on('focus','input.form-control',function(e){
            $(e.target).prev('.fa-pencil-alt').addClass('input-focus');
          })
          $('.agentdepositgcash').on('focusout','input.form-control',function(e){
            $(e.target).prev('.fa-pencil-alt').removeClass('input-focus');
          })
        });
      </script>
      ";

      if ($select_purpose_data_result->parent_id != $g->id) {
        $agent_to_member_deposit_html = '(x) '.$tr['account error_msg4'];//來源與目的帳號關係不合法。
        $extend_js = '';
      }

    } else {
      $agent_to_member_deposit_html = '(x) '.$tr['permission error'];//不合法的嘗試。
    }

  } else {
    // 會員資料可能被鎖定了!!!
    $agent_to_member_deposit_html = '<p class="no_available">'.$tr['Your wallet has been frozen, please contact customer'].'</p>';
  }

// --------------------
} else {
// --------------------
  // 身分不合法

	// 搜尋條件
	$wallets_content_html = '';
	// 列出資料
	if(isset($_SESSION['member']) AND $_SESSION['member']->therole == 'T') {
    //此為試用帳號，請先登出並以會員登入再使用。
		$wallets_content_html = $tr['trail use member first'];
  }elseif(isset($_SESSION['member']) AND $_SESSION['member']->therole == 'M') {
    //只允許代理商身份的會員使用。
    $wallets_content_html = $tr['agent only'];
  }elseif(isset($_SESSION['member']) AND $_SESSION['member']->therole == 'R') {
    //只允許代理商身份的會員使用，管理員無法使用此功能。
    $wallets_content_html = $tr['agent only no management'];
	}else{
    //會員請先登入。
		$wallets_content_html = $tr['member login first'];
	}

	// 切成 1 欄版面
	$agent_to_member_deposit_html = '
	<div class="row">
	  <div class="col-12">
	  '.$wallets_content_html.'
	  </div>
	</div>
	<br>
	<div class="row">
		<div id="preview_result"></div>
	</div>
	';
// --------------------
}

$indexbody_content = '
<div class="row">
  <div class="col-12" id="send_result">
  '.$agent_to_member_deposit_html.'
  </div>
</div>
<div class="row">
<div class="col-12">
  <div id="preview_area"></div>
</div>
</div>
<br>
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
$tmpl['banner'] = ['Agent transfer'];
// menu增加active
$tmpl['menu_active'] =['agent_instruction.php'];

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include($config['template_path']."template/admin.tmpl.php");
?>
