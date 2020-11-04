<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 會員錢包檢視
// 1.會員可以設定自己的錢包 , 自動儲值的方式
// 2.可以觀看 GCASH OR GTOKEN 的交易紀錄
// File Name:	wallets.php
// Related:   wallets_action.php , lobby_mggame_action.php
// Author:		Barkley
// Log:
// ----------------------------------------------------------------------------
/*
主要操作的DB表格：
root_withdraw_review 代幣申請審查表
root_member_gtokenpassbook 代幣存款紀錄
前台
wallets.php 錢包顯示連結--取款、存簿都由這裡進入。
transcactiongtoken.php 前台代幣的存簿
withdrawapplication.php 代币(GTOKEN)线上取款前台程式, 操作界面
withdrawapplication_action.php 代币(GTOKEN)线上取款前台動作, 會先預扣提款款項
後台
member_transactiongtoken.php 後台的會員GTOKEN轉帳紀錄,預扣款項及回復款項會寫入此紀錄表格
withdrawalgtoken_company_audit.php  後台GTOKEN提款審查列表頁面
withdrawalgtoken_company_audit_review.php  後台GTOKEN提款單筆紀錄審查
withdrawapgtoken_company_audit_review_action.php 後台GTOKEN提款審查用的同意或是轉帳動作SQL操作
 */
// ----------------------------------------------------------------------------



// 主機及資料庫設定
require_once dirname(__FILE__) . "/config.php";
// 支援多國語系
require_once dirname(__FILE__) . "/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) . "/lib.php";

require_once dirname(__FILE__) . "/lib_wallets.php";
// var_dump($_SESSION);

// var_dump(session_id());
// ----------------------------------------------------------------------------
// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta//會員錢包狀態
$function_title = $tr['membercenter_wallets'];
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
<a href="{$config['website_baseurl']}menu_admin.php?gid=deposit"><i class="fas fa-chevron-left"></i></a>
    <span>$function_title</span>
    <i></i>
HTML;
}
// ----------------------------------------------------------------------------


// 有登入，有錢包才顯示。
if (isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T') {
// ----------------------------------------------------------------------------

	// 功能及操作的說明
	$wallets_content_html = '';

	// 預設登入者本身
	$query_id = $_SESSION['member']->id;

	// 查詢來源帳號的資料 , 確認該帳號 status 還是有效的
	$sql = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.id = $query_id AND status IN ('1','2') ;";
	$g = runSQLALL($sql, 0, 'r');
	// var_dump($g);
	if ($g[0] == 1) {

		// 功能說明文字
		//* 會員可以檢視在所有娛樂城的錢包狀態。<br>
		//* 會員可以查詢 GCASH(現金) 及 GTOKEN(代幣) 交易紀錄資訊。<br>
		//* GTOKEN(代幣)用於娛樂城的遊戲，前往娛樂城時會自動將代幣帶到該娛樂城。<br>
		//* 會員設定自動將 GCASH(現金)儲值到 GTOKEN(代幣)，代幣轉換依此設定無須人工介入。<br>
		$page_desc = '<div class="alert alert-success" role="alert">' . $tr['wallets page_desc'] . '</div>';

		// --------------------------------
		// 現金 GCASH 呈現
		// --------------------------------
		$cash_row_html = '';
		$all_balance = '';
		// GPK 代幣 col name
		//GCASH(現金)
		//GCASH現金交易紀錄
		// GCASH現金可以提款 無須稽核，需要提款手續費 n% ，現金提款每人每月只能提款 10000元額度。
		// 申請現金提款時，離最後1筆存提款紀錄時間需要 72HR 以上，才可以申請現金提款
		// 如果提款申請時，IP OR 瀏覽器指紋，在 72HR 內出現過時，不可以申請現金提款。(管制需要不同的 IP + 瀏覽器指紋才可以避免使用者跳換帳號提款)。
		// 但如透過遊戲代幣，則非使用此管制方式。

		//現金提款
		//現金提款無須稽核，需要提款手續費，現金提款每人每月只能提款 1 萬元額度，如透過遊戲代幣提款則無此限制。申請現金提款時，離最後1筆存提款紀錄時間需要 72HR 以上，才可以申請現金提款。
		// 顯示容易了解的數字顯示方式
		$gcash_balance_html = '$'.$g[1]->gcash_balance;
		$all_balance = $g[1]->gcash_balance;

    // 舊版現金提款判斷
    // if ($protalsetting['withdrawalapply_switch'] == 'on'){
    //     if ($member_grade_config_detail->withdrawalcash_allow != 1) {
    //       $withdrawalcash_btn = '';
    //     } elseif ($g[1]->gcash_balance == 0 AND $member_grade_config_detail->withdrawalcash_allow == 1) {
    //       $withdraw_gcash_url = '#';
    //       $withdraw_gcash_isdisabled = 'disabled';
    //       $withdrawalcash_btn = '<a href="' . $withdraw_gcash_url . '" class="btn btn-default btn-sm" target="_self" ' . $withdraw_gcash_isdisabled . '>' . $tr['GCASH withdrawal'] . '</a>';
    //     } else {
    //       $withdraw_gcash_url = 'withdrawapplicationgcash.php';
    //       $withdraw_gcash_isdisabled = '';
    //       $withdrawalcash_btn = '<a href="' . $withdraw_gcash_url . '" class="btn btn-default btn-sm" target="_self" ' . $withdraw_gcash_isdisabled . '>' . $tr['GCASH withdrawal'] . '</a>';
    //     }
    // }else{
    //     $withdrawalcash_btn='<span class="text-danger"><strong>后台提款功能关闭，无法进行「现金提款」！</strong></span>';
    // }
      $withdrawalapply_offline_desc = (isset($protalsetting['withdrawalapply_offline_desc'])&&$protalsetting['withdrawalapply_offline_desc']!=='')? $protalsetting['withdrawalapply_offline_desc']:$tr['withdrawal function is closed'];
     if ($member_grade_config_detail->withdrawalcash_allow == 1) {
        if($protalsetting['withdrawalapply_switch'] == 'on'){

        $show_sql = "SELECT *, to_char((applicationtime AT TIME ZONE '$tzonename'),'YYYY-MM-DD HH24:MI:SS') as applicationtime FROM root_withdrawgcash_review WHERE account = '".$_SESSION['member']->account."' AND status = '2';";
        $show_sql_result = runSQLALL($show_sql);

        $withdrawal_status_runing = false;
        if($show_sql_result[0] >= 1) {

          // 提款狀態
          //已刪除
          $withdrawal_status[NULL]  = $tr['withdraw status delete'];
          //使用者取消
          $withdrawal_status[0]     = $tr['withdraw status cancel'];
          //已完成出款
          $withdrawal_status[1]     = $tr['withdraw status complete'];
          //請款審查中
          $withdrawal_status[2]     = $tr['withdraw status apply'];
          //管理端處理中
          $withdrawal_status[3]     = $tr['withdraw status process'];
          //管理端退回
          $withdrawal_status[4]     = $tr['withdraw status reject'];
          // 已經有申請提款了
          $deposit_withdrawalcash_status = $withdrawal_status[$show_sql_result[1]->status];
        }else{
          $deposit_withdrawalcash_status = $tr['GCASH withdrawal'];
        }

            $withdraw_gcash_url = 'withdrawapplicationgcash.php';
            $withdraw_gcash_isdisabled = '';
            $withdrawalcash_btn = '<a href="' . $withdraw_gcash_url . '" class="btn btn-primary" target="_self" ' . $withdraw_gcash_isdisabled . '>' . $deposit_withdrawalcash_status . '</a>';
        }else{
            $withdrawalcash_btn='<span class="text-danger">'.$withdrawalapply_offline_desc.'</span>';
        }
     }else{
        $withdrawalcash_btn = '<span class="text-danger">'.$withdrawalapply_offline_desc.'</span>';
     }


     /*交易紀錄
<td><a href="transactiongcash.php" class="btn btn-default btn-sm" target="_self" title="' . $tr['GCASH transaction'] . '">' . $tr['GCASH transaction'] . '</a></td>
     */
     /*現金*/
    if(hide_gcash_mode()=='off'){
      if($config['site_style']=='mobile'){
        $cash_row_html = $cash_row_html . '
        <div class="card mb-2 deposit_color">
          <div class="card-body border">
            <div class="row cash_withdrawals_b">
              <div align="center" class="h5 col-3"><p>' . $tr['GCASH'] . '</p></div>
              <div align="right"  class="h5 col-auto wallets_box"><p><strong>' . $gcash_balance_html . '</strong></p></div>
              <div class="col-auto ml-auto">' . $withdrawalcash_btn . '</div>
            </div>
          </div>
        </div>
        ';
      }else{
        $cash_row_html = $cash_row_html . '
            <div class="wallets_list_div">
              <div class="title">
                <div><i class="fas fa-dollar-sign"></i></div>
                '.$tr['GCASH'].'
              </div>
              <div class="wallets_content">
                <p>' . $gcash_balance_html . '</p>
                <div class="col"></div>
                <div class="mt-3">' . $withdrawalcash_btn . '</div>
              </div>
            </div>
        ';
      }

    }

		// --------------------------------
		// 代幣 GTOKEN 呈現
		// --------------------------------
		// 目前錢包所在哪裡？ NULL 等於沒有鎖定
		$gtoken_status_html = '';
		if ($g[1]->gtoken_lock == NULL OR $g[1]->gtoken_lock == '') {
			//代幣錢包未使用
			$gtoken_status_html = '<div class="alert_content_balance"><span class="glyphicon glyphicon-lock" aria-hidden="true"></span>' . $tr['token wallets unused'].'</div>';
		} else {
			// 顯示轉移錢包的按鈕      //代幣正在 OOXX 娱乐城使用 回收代币
      //回收所有娛樂城的所有代幣到 GTOKEN 帳戶。
      // $tr['recycle gtoken'] = '回收';
			$gtoken_status_html = '<div class="re_wallets_content"><div class="alert_content_balance"><p>'.$tr['token now'] . '<span class="px-1 font-weight-bold">' . $tr[$g[1]->gtoken_lock.' Casino'] . '</span>' . $tr['casino used'] . ' ' .
        '</p></div>
        <button type="button" id="wallets-gtokenrecycling" class="btn btn-danger" data-toggle="gtokenrecycling" onclick="gtokenrecycling_balance()" title="' . $tr['all casino to gtoken'] . '" >' . $tr['recycle gtoken'] . '</button></div>';

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
			// ----------------------------------------------------------------
		}
		// GPK 代幣 col name
		// 顯示容易了解的數字顯示方式
		$gtoken_balance_html = '$'.$g[1]->gtoken_balance;
		$all_balance += $g[1]->gtoken_balance;

    // 舊版遊戲幣提款判斷
    // if ($protalsetting['withdrawalapply_switch'] == 'on'){
    //     if ($member_grade_config_detail->withdrawal_allow != 1) {
    //       $withdraw_btn = '';
    //     } elseif ($g[1]->gtoken_balance == 0 AND $member_grade_config_detail->withdrawal_allow == 1) {
    //       $withdraw_gtoken_url = '#';
    //       $withdraw_gtoken_isdisabled = 'disabled';
    //       $withdraw_btn = '<a href="' . $withdraw_gtoken_url . '" target="_self" class="btn btn-default btn-sm" ' . $withdraw_gtoken_isdisabled . '>' . $tr['GTOKEN withdrawal'] . '</a>';
    //     } else {
    //       $withdraw_gtoken_url = 'withdrawapplication.php';
    //       $withdraw_gtoken_isdisabled = '';
    //       $withdraw_btn = '<a href="' . $withdraw_gtoken_url . '" target="_self" class="btn btn-default btn-sm" ' . $withdraw_gtoken_isdisabled . '>' . $tr['GTOKEN withdrawal'] . '</a>';
    //     }
    // }else{
    //     $withdraw_btn='<span class="text-danger"><strong>后台提款功能关闭，无法进行「游戏币提款」！</strong></span>';
    // }

      $withdrawalapply_offline_desc = (isset($protalsetting['withdrawalapply_offline_desc'])&&$protalsetting['withdrawalapply_offline_desc']!=='')? $protalsetting['withdrawalapply_offline_desc']:$tr['withdrawal function is closed'];

    if( $g[1]->status == 2){
      $withdraw_btn = '';
    }elseif ($member_grade_config_detail->withdrawal_allow == 1) {
        if($protalsetting['withdrawalapply_switch'] == 'on'){
          $withdraw_gtoken_url = 'withdrawapplication.php';
          $withdraw_gtoken_isdisabled = '';

          //若提款審查中顯示不同文字 遊戲幣取款
          $show_sql = "SELECT *, to_char((applicationtime AT TIME ZONE '$tzonename'),'YYYY-MM-DD HH24:MI:SS') as applicationtime FROM root_withdraw_review WHERE account = '".$_SESSION['member']->account."' AND status = '2';";
          $show_sql_result = runSQLALL($show_sql);

          if($show_sql_result[0] >= 1) {
            // 提款狀態
            //已刪除
            $withdrawal_status[NULL]  = $tr['withdraw status delete'];
            //使用者取消
            $withdrawal_status[0]     = $tr['withdraw status cancel'];
            //已完成出款
            $withdrawal_status[1]     = $tr['withdraw status complete'];
            //請款審查中
            $withdrawal_status[2]     = $tr['withdraw status apply'];
            //管理端處理中
            $withdrawal_status[3]     = $tr['withdraw status process'];
            //管理端退回
            $withdrawal_status[4]     = $tr['withdraw status reject'];
            // 已經有申請提款了
            $deposit_withdrawal_status = $withdrawal_status[$show_sql_result[1]->status];
          }else{
            $deposit_withdrawal_status = $tr['GTOKEN withdrawal'];
          }

          $withdraw_btn = '<a href="' . $withdraw_gtoken_url . '" target="_self" class="btn btn-primary" ' . $withdraw_gtoken_isdisabled . '>' . $deposit_withdrawal_status . '</a>';
        }else{
            $withdraw_btn='<span class="text-danger">'.$withdrawalapply_offline_desc.'</span>';
        }
     }else{
        $withdraw_btn = '<span class="text-danger">'.$withdrawalapply_offline_desc.'</span>';
     }

		//GTOKEN(代幣)  GTOKEN代幣交易紀錄 代幣提款 需要提款稽核檢查，如果沒通過稽核需要扣除行政手續費用 50% 及提款手續費用。
    /*交易紀錄
    <td><a href="transactiongtoken.php" target="_self" class="btn btn-default btn-sm" title="' . $tr['GTOKEN transaction'] . '">' . $tr['GTOKEN transaction'] . '</a></td>*/
    /*
    <td id="gtoken_status">' . $gtoken_status_html . '</td>
    */
    if($config['site_style']=='mobile'){
      $cash_row_html = $cash_row_html . '
      <div class="card mb-2 deposit_color">
        <div class="card-body border">
          <div class="row game_withdrawals_b">
            <div align="center" class="h5 col-3"><p>' . $tr['GTOKEN'] . '</p></div>
            <div align="right" class="h5 col-auto  wallets_box"><p><strong id="gtoken_b">' . $gtoken_balance_html . '</strong></p></div>
            <div class="col-auto ml-auto">' . $withdraw_btn . '</div>
          </div>
        </div>
      </div>
      ';
    }else{
      $cash_row_html = $cash_row_html . '
          <div class="wallets_list_div">
            <div class="title">
              <div><i class="fas fa-coins"></i></div>
              '.$tr['GTOKEN'].'
            </div>
            <div class="wallets_content">
              <p id="gtoken_b">' . $gtoken_balance_html . '</p>
              <div class="col"></div>
              <div class="tip_wallets_bt">' . $withdraw_btn . '</div>
            </div>
          </div>
      ';
    }

		// <td><button type="button" class="btn btn-primary btn-sm" id="open_two_page" onclick="window.open("withdrawapplication.php", "test");">test open</button></td>

		// -------------------------------------------------------------------
		// 代幣 列表表格
		// -------------------------------------------------------------------

		//帳號  會員錢包資訊  錢包分類  餘額  錢包狀態  交易紀錄 提款申请
		// $all_balance = money_format('%i', $all_balance);
    /*
    <thead>
      <tr>
        <th>' . $tr['wallets classification'] . '</th>
        <th>' . $tr['Balance'] . '</th>
        <th>' . $tr['wallets status'] . '</th>
        <th>' . $tr['transaction record'] . ' </th>
        <th></th>
      </tr>
    </thead>
    */
    /*標題
  <div class="card-title">
  <h4>
    ' . $tr['Account'] . '' . $g[1]->account . '' . $tr['wallets info'] . '
  </h4>
  </div>
    */
    if($config['site_style']=='mobile'){
      $gtoken_table_html = $cash_row_html . '
      <div class="row success h4 px-4 mb-3 deposit_color">
        <div><strong>' . $tr['wallets all balance'] . '</strong></div>
        <div id="total_b" align="right" class="col-auto"><strong>$' . $all_balance . '</strong></div>
      </div>
    ';
    }else{
      $gtoken_table_html = '<div class="wallets_list_content">'.$cash_row_html . '</div>
          <div class="account_balance">
            <div class="account_balance_title">
              <div>' . $tr['wallets all balance'] . '</div>
              <div id="total_b" align="right" class="col-auto">$' . $all_balance . '</div>
            </div>
           '.$gtoken_status_html.'
          </div>
      ';
    }

	}
	// end sql

	// -------------------------------------------------------------------
	// 手動現金轉代幣
	// -------------------------------------------------------------------

	//加盟金轉現金(確認後立即轉換指定金額)
	//手動儲值 儲值金額 說明
	// $tr['There is not enough money to join, you can not use the manual stored value function'] = '加盟金餘額不足，無法使用手動儲值功能。';
	$manual_gcashtogtoken = '
  <div class="card-title">
  <h4>
  ' . $tr['Manual deposit'] . '
  </h4>
  </div>
  ';

	if ($g[1]->gcash_balance > 0) {
		//1. 手動儲值時，為避免儲值失敗，務必確認代幣已都從娛樂城取回。如未取回娛樂城中代幣，請利用會員錢包資訊中回收代幣功能。
		//2. 餘額不足100進行儲值時，剩餘現金將全數轉換為代幣。
		// $tr['recharge'] = '儲值';
		$manual_gcashtogtoken_row_html = '
    <tr>
      <td>
        <div class="form-inline">
          <select class="form-control" style="width:auto;" id="manual_gcashtogtoken">
            <option>' . $tr['select deposit amount'] . '</option>
            <option>' . $tr['deposit amount less 100'] . '</option>
            <option>100</option>
            <option>200</option>
            <option>500</option>
            <option>1000</option>
            <option>2000</option>
            <option>5000</option>
            <option>10000</option>
          </select>
          <button type="button" class="btn btn-default" id="manual_gcashtogtoken_btn">' . $tr['recharge'] . '</button>
        </div>
      </td>
      <td>
      ' . $tr['GCASH to cash notice1'] . '<br>
      ' . $tr['GCASH to cash notice2'] . '
      </td>
    </tr>
    ';

		$manual_gcashtogtoken = $manual_gcashtogtoken . '
    <table  class="table table-striped">
    <thead>
      <tr>
        <th>' . $tr['Manual deposit amount'] . '</th>
        <th>' . $tr['description'] . '</th>
      </tr>
    </thead>
    <tbody>
      ' . $manual_gcashtogtoken_row_html . '
    </tbody>
    </table>
    ';

		// $tr['Identify from cash wallet, stored value'] = '確定要從現金錢包，儲值';
		// $tr['To game currency'] = '元至遊戲幣？';
		// $tr['Illegal test'] = '(x)不合法的測試。';
		$extend_js = $extend_js . "
    <script>
      $(document).ready(function() {
        $('#manual_gcashtogtoken_btn').click(function() {
          var manual_amount = $('#manual_gcashtogtoken').val();
          var pk = '" . $g[1]->id . "';
					var message = '" . $tr['Identify from cash wallet, stored value'] . "' + manual_amount +'" . $tr['To game currency'] . "' ;
					var csrftoken = '".$csrftoken."';

          if(jQuery.trim(manual_amount) != '') {
            if(manual_amount != '" . $tr['select deposit amount'] . "') {
              if(confirm(message)) {
                $.post('wallets_action.php?a=manual_gcashtogtoken',
                {
                  manual_amount: manual_amount,
									pk: pk,
									csrftoken : csrftoken
                },
                function(result) {
                  $('#preview_area').html(result);
                });
              } else {
                window.location.reload();
              }
            }
          } else {
            alert('" . $tr['Illegal test'] . "');
          }
        });
      });
    </script>
    ";

	} else {
		// $tr['Go to the payment page'] = '前往入款頁面';
		// $tr['Please click to payment page.'] = '請點此前往入款頁面。';
		$manual_gcashtogtoken = $manual_gcashtogtoken . '
    <div class="alert alert-danger" role="alert">' . $tr['There is not enough money to join, you can not use the manual stored value function'] . '</div>
		';
		// <div class="alert alert-danger" role="alert">' . $tr['There is not enough money to join, you can not use the manual stored value function'] . '<a href="deposit.php" title="' . $tr['Go to the payment page'] . '" target="_blank">' . $tr['Please click to payment page.'] . '</a></div>
	}

	// -------------------------------------------------------------------
	// 針對 member 帳號，設定自動化儲值的預設值。
	// -------------------------------------------------------------------
	//自動儲值設定
	// 當GTOKEN(代幣)餘額不足時，可以自動從GCASH(現金)帳戶轉帳到代幣帳戶，此功能打開就可自動儲值轉帳。
	// 只有代幣沒被使用的時候，才可以加值。如果已經使用，需要取回才可以加值。
	// 自動化儲值開啟(開/關)  最低自動轉帳餘額  每次儲值金額
	// --------------------------2018-02-12 fix by Ian----------------------
	// 新增檢查系統預設會員存款別，如預設存gcash則顯示自動儲值設定UI，如是gtoken則不顯示
	// 另系統全部預設為關閉自動儲值
	// ---------------------------------------------------------------------
  if($protalsetting['member_deposit_currency'] == 'gcash'){
  	$auto_gtoken_html = '<div style="border-bottom: 1px #555 solid;border-left: 10px #820000 solid;padding: 8px;margin: 5px; font-weight:bold; font-size: 1em; width: 300px;">
      ' . $tr['auto deposit setting'] . '
      <span class="glyphicon glyphicon-info-sign" aria-hidden="true" data-toggle="auto_gtoken_tooltip" title="' . $tr['auto deposit setting desc'] . '"></span>
      </div>';
  	if ($g[1]->gcash_balance > 0) {

  		// 等入的使用者為代理才允許修改自動儲值功能
  		if ($_SESSION['member']->therole == 'A') {
  			$auto_gtoken_switch = '';
  		} else {
  			$auto_gtoken_switch = 'disabled';
  		}

  		if ($g[1]->auto_gtoken == 1) {
  			$select_on_option = 'selected';
  			$select_off_option = '';
  		} else {
  			$select_on_option = '';
  			$select_off_option = 'selected';
  		}
  		// $tr['Currently set'] = '目前設定：';
  		// $tr['Save settings'] = '儲存設定';
  		$auto_gtoken_html = $auto_gtoken_html . '
      <table class="table table-striped">
        <thead>
        <tr>
          <th>' . $tr['turn on auto deposit'] . '</th>
          <th>' . $tr['min auto deposit balance'] . '</th>
          <th>' . $tr['auto deposit amount'] . '</th>
          <th></th>
        </tr>
        </thead>
        <tbody>
        <tr>
  				<td>
  					<div class="form-group">
  						<select class="form-control" style="width:auto;" id="autocash2token">
  							<option ' . $select_on_option . '>' . $tr['on'] . '</option>
  							<option ' . $select_off_option . '>' . $tr['off'] . '</option>
  						</select>
  					</div>
  				</td>
  				<td>
  					<input type="text" class="form-control" id="tokenautostart" style="width:45%" placeholder="' . $tr['Currently set'] . '' . $g[1]->auto_min_gtoken . '" ' . $auto_gtoken_switch . '>
  				</td>
  				<td>
  					<input type="text" class="form-control" id="tokenoncesave" style="width:45%" placeholder="' . $tr['Currently set'] . '' . $g[1]->auto_once_gotken . '" ' . $auto_gtoken_switch . '>
  				</td>
  				<td>
  					<button type="button" class="btn btn-default" id="auto_gtoken_btn">' . $tr['Save settings'] . '</button>
  				</td>
        </tr>
        </tbody>
      </table>
      <br>
  		';

  		// $tr['Ok to save'] = '確定儲存設定？';
  		// $tr['Please click to payment page.'] = '請點此前往入款頁面。';
  		$extend_js = $extend_js . "
      <script>
        $(document).ready(function() {
          $('#auto_gtoken_btn').click(function() {
            var autocash2token = $('#autocash2token').val();
            var tokenautostart = $('#tokenautostart').val();
            var tokenoncesave = $('#tokenoncesave').val();
  					var pk = '" . $g[1]->id . "';
  					var csrftoken = '".$csrftoken."';

            var message = '" . $tr['Ok to save'] . "';

            if(confirm(message)) {
              $.post('wallets_action.php?a=edit_auto_cashtotoken',
              {
                autocash2token: autocash2token,
                tokenautostart: tokenautostart,
                tokenoncesave: tokenoncesave,
  							pk: pk,
  							csrftoken : csrftoken
              },
              function(result) {
                $('#preview_area').html(result);
              });
            } else {
              window.location.reload();
            }
          });
        });
      </script>
      ";

  		// 提示文字 JS for 自動儲值設定
  		// $tr['There is not enough money to join, you can not use the automatic stored value function'] = '加盟金餘額不足，無法使用自動儲值功能。';
  		$tooltip_js_html = "
      <script>
      $(document).ready(function(){
          $('[data-toggle=\"auto_gtoken_tooltip\"]').tooltip();
          $('[data-toggle=\"gtokenrecycling\"]').tooltip();
      });
      </script>
      ";
  		$extend_js = $extend_js . $tooltip_js_html;
  	} else {
  		$auto_gtoken_html = $auto_gtoken_html . '
      <br>
      <div class="alert alert-danger" role="alert">' . $tr['There is not enough money to join, you can not use the automatic stored value function'] . '<a href="deposit.php" title="' . $tr['Go to the payment page'] . '" target="_blank">' . $tr['Please click to payment page.'] . '
  </a></div>
      <br>
      ';
  	}
  }

	// -------------------------------------------------------------------
	// 如果是代理商的話，提供會員轉帳的功能(代理商限定)
	// -------------------------------------------------------------------
	// var_dump($g);

/*
// 只有代理商 and 管理員可以登入
if($g[1]->therole == 'A' OR $g[1]->therole == 'R') {

//代理商會員轉帳功能
// $agent_depositgcash_html = '<p align="center"><h4><span class="label label-primary">'.$tr['agent deposit'].'</span></h4></p>';

// 轉帳來源帳戶
$deposit_source_account_input_html = '
<div class="form-group">
<input type="text" class="form-control" id="deposit_source_account" value="'.$g[1]->account.'" placeholder="'.$tr['source account'].'" disabled>
</div>
';

// 轉帳來源帳戶balance
$deposit_source_account_amount_input_html = '
<div class="form-group">
<input type="text" class="form-control" id="deposit_source_account_balance" value="'.$g[1]->gcash_balance.'" placeholder="來源帳號餘額" disabled>
</div>
';

// button to send
//立即前往轉帳  代理商可以將自己的 GCASH(現金) 轉給其他站內的會員使用。
//轉帳來源帳號
//可轉帳餘額(GCASH)

$deposit_dest_send_html  = '<a href="member_agentdepositgcash.php" class="btn btn-success" id="deposit_dest_account_amount_send">'.$tr['transfer now'].'</button></span>';
$agent_depositgcash_html	= $agent_depositgcash_html.'<div class="alert alert-success" role="alert">'.$tr['agent deposit desc'].'</div>';
$agent_depositgcash_html	= $agent_depositgcash_html.'
<table class="table table-striped">
<thead>
<tr>
<th>'.$tr['transfer source account'].'</th>
<th>'.$tr['transferable balance GCASH'].'</th>
<th></th>
</tr>
</thead>
<tbody>
<tr>
<td>'.$deposit_source_account_input_html.'</td>
<td>'.$deposit_source_account_amount_input_html.'</td>
<td>'.$deposit_dest_send_html.'</td>
</tr>
</tbody>
</table>
';

}else{
$agent_depositgcash_html = '';
}
 */

	// --------------------------------------------------------------------------

	// --------------------------------------------------------------------------
	// 排版及 show content
	// --------------------------------------------------------------------------
	// 餘額狀態
/*' . $page_desc . '*/
if($config['site_style']=='mobile'){
	$wallets_content_html = $wallets_content_html . '
<div class="row">
	<div class="col-12 px-0">
    ' . $gtoken_table_html . '
    </div>
  </div>
  ';
}else{
	$wallets_content_html = $wallets_content_html . '
<div class="row">
	<div class="col-12">
    ' . $gtoken_table_html . '
    </div>
  </div>
  ';
}

$casinoBalanceHtml = combineGetCasinoBalanceHtml('wallets');
$casinoBalanceJs = combineGetCasinoBalanceJs();
$extend_js = $extend_js . $casinoBalanceJs;

	// preview_area
	$wallets_content_html = $wallets_content_html . '
  <div class="row">
    <div class="col-12">
    <div id="preview_area"></div>
    </div>
  </div>
  ';

if($config['site_style']=='desktop') {
  $wallets_content_html =<<<HTML
<div class="row">
  <div>{$casinoBalanceHtml}</div>
  <div class="col">
    <div class="main_content deposit_center">
      {$wallets_content_html}
    </div>
  </div>
  </div>
HTML;
}else{
  $casinoBalanceHtml = combineGetCasinoBalanceHtml();
  $wallets_content_html =<<<HTML
<div class="row">
  {$casinoBalanceHtml}
  </div>
  {$wallets_content_html}
HTML;
}

	$extend_head = $extend_head . '
	<link href="' . $cdnfullurl_js . 'bootstrap3-editable/css/bootstrap-editable.css" rel="stylesheet"/>
	<script src="' . $cdnfullurl_js . 'bootstrap3-editable/js/bootstrap-editable.min.js"></script>
  <style>
  .table td.fit,
  .table th.fit{
      white-space: nowrap;
      width: 1%;
  }
  </style>
  ';

	// 輸出
	$indexbody_content = $wallets_content_html;

// --------------------
} else {
// --------------------
	// 搜尋條件
	$wallets_content_html = '';
	// 列出資料
	if (isset($_SESSION['member']) AND $_SESSION['member']->therole == 'T') {
		//試用帳號，請先登出再以會員登入使用。
		$wallets_content_html = $tr['trail use member first'];
	} else {
		//會員請先登入。
		$wallets_content_html = $tr['member login first'];
    $wallets_content_html .=<<<js
    <script>
    window.location.href = "{$config['website_baseurl']}login2page.php"
    </script>
js;
	}

	// 切成 1 欄版面
	$indexbody_content = '';
	$indexbody_content = $indexbody_content . '
	<div class="row">
	  <div class="col-12">
	  ' . $wallets_content_html . '
	  </div>
	</div>
	<div class="row">
		<div id="preview_result"></div>
	</div>
	';
// --------------------
}

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
$tmpl['sidebar_content'] = ['deposit','wallets'];
// banner標題
$tmpl['banner'] = ['membercenter_menu_admin_deposit'];
// menu增加active
$tmpl['menu_active'] =['deposit.php'];
// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include $config['template_path'] . "template/admin.tmpl.php";
