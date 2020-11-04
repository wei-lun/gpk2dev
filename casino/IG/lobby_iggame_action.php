<?php
// ----------------------------------------------------------------------------
// Features:	接收 gamelobby.php 將網頁轉到 casino
// File Name:	lobby_iggame_action.php
// Author:		Ian
// Related:
// Log:
// 此程式僅供 IG game 登入使用
// 包含所有的 IG 函式都在這個檔案內。
// 管理端網址： https://tegbolll.totalegame.net/
// ----------------------------------------------------------------------------


// var_dump($_SESSION);
//var_dump($_POST);
//var_dump($_GET);



// ----------------------------------------------------------------------------



require_once dirname(dirname(__DIR__))."/config.php";
// 支援多國語系
require_once dirname(dirname(__DIR__))."/i18n/language.php";
// 自訂函式庫
require_once dirname(dirname(__DIR__))."/lib.php";
// 自訂casino lobby通用函式庫
require_once dirname(__DIR__)."/casino_config.php";
// Restful API lib
require_once dirname(__FILE__)."/lobby_iggame_lib.php";

// debug on/off (1/0) , 除錯功能, 設定為 1 可以觀看連接到 IG game 的 URL
$debug = 0;
$system_mode = 'webb_test';

// 檢查IG娛樂城的狀態，如果是 OPEN=1 才顯示選單及遊戲
$check_casino_state_sql = 'SELECT * from casino_list WHERE casinoid = \'IG\'';
$check_casino_state_result = runSQLall($check_casino_state_sql);
if($check_casino_state_result['0'] > '0'){
	$casino_name = $check_casino_state_result['1']->casino_name;
	$gamelist_table = $check_casino_state_result['1']->casino_dbtable;
	$casino_state = $check_casino_state_result['1']->open;
}

if($casino_state == 1){

	// ----------------------------------------------------------------------------
	if(isset($_GET['a']) AND isset($_SESSION['member'])) {
		$action = filter_var($_GET['a'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
			// 先一張圖，說明 IG2 play 還活著...!!

			$page_html = '<div style="
			background-color:black;
			height: 100vh;
			display: flex;
			justify-content: center;
			align-items: center;
			overflow: hidden;
			">
			<img src="' . $config['companylogo'] . '" alt="LOGO">
			<img src="'.$cdnrooturl.'loading_ball.gif" alt="playtech">
			</div>
			';

		// 如果是 debug 就不顯示黑底+logo , 以利於 debug
		// 如果是 Retrieve_IG_Casino_balance (取回 IG 娛樂城餘額) 就顯示不同的畫面。
			if($debug == 1 OR $action == 'Retrieve_IG_Casino_balance') {
				// debug , show debug information
			}else{
				echo $page_html;
			}
	}else{
		die('(402)不合法的呼叫');
	}
	// ----------------------------------------------------------------------------

	// 呼叫 casino lib 來轉帳, 建立帳號的函式
	//require_once $_SERVER[__DIR__] ."/lobby_casino_lib.php";

	// ----------------------------------
	// MAIN START 動作檢查
	// ----------------------------------
	if($action == 'goto_game' AND isset($_SESSION['member']) ) {
	// ----------------------------------------------------------------------------
	// 登入 IG flash 的流程 -- 免轉錢包流程
	// use by Lobby_iggame.php
	// ----------------------------------------------------------------------------


	/*
	// in 帳號A 預計登入 IG2 casion , 帳號為 ig_A , 轉錢的餘額為 $$$
	0. 使用者需要登入系統,才可以繼續, 否則結束, 預設 錢包 wallets
	1. 判斷使用者在 IG 是否有帳號，如果沒有的話就建立帳號, 如果有的話檢查看是否有餘額。

	//Retrieve_Casino_IG2_balance();
	何時取回娛樂城的餘額？
	1. 登入系統的時候 -- 關閉，因為有風險。
	2. 登出系統的時候 -- ok
	3. 轉換娛樂城的時候 -- 尚未有第二個娛樂城

	//Transferout_Casino_IG2_balance();
	何時將餘額轉出到娛樂城？
	1. 當玩家點擊指定的遊戲城，檢查餘額是否在該遊戲城
	2. 如果在該遊戲城的話，不用轉餘額。直接登入該遊戲城。
	3. 如果不在，該遊戲城，先檢查餘額是否在 gpk2 本地端
	4. 如果不在的話，將所有的餘額轉回來 gpk2 本地端，
	5. 轉回再轉到指定的娛樂城。
	*/

	// ----------------------------------------------------------------------------



	// ----------------------------------------------------------------------------
	// 從 gamelobby.php 取得 gamecode 產生網址, 轉 url 到娛樂城
	// ----------------------------------------------------------------------------

	// 已經有登入,且需要 wallet 也在 session 內才可以繼續 , 只有會員 M 和代理商 A 才可以執行遊戲,管理員 R 不可以執行, 避免做帳的問題
	if(isset($_SESSION['member']) AND ($_SESSION['member']->therole == 'A' OR $_SESSION['member']->therole == 'M') ) {

		// 檢查會員是否存在
		$member_id = $_SESSION['member']->id;
		$member_sql = "SELECT root_member.id,account,gtoken_balance,gtoken_lock,
						casino_accounts->'IG'->>'account' as ig_account,
						casino_accounts->'IG'->>'password' as ig_password,
						casino_accounts->'IG'->>'balance' as ig_balance FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.id = '$member_id' AND root_member.status = '1';";
		$member = runSQLall($member_sql);
		if($member[0] == 1) {
		// 會員存在

			// 取得外部來源變數 gamecode
			$gamecode     = filter_var($_GET['gamecode'], FILTER_SANITIZE_STRING);
			$casinoname   = filter_var($_GET['casinoname'], FILTER_SANITIZE_STRING);
			$gameplatform = filter_var($_GET['gameplatform'], FILTER_SANITIZE_STRING);

			// ------------------------------------------------------------------------------------
			// 檢查是否已經有人按了 game 還在等待處理, 將 game code 存在 session 內。
			// 判斷剛剛同一個人有沒有很緊張，一直按下很多個 gamecode 按鈕
			// ------------------------------------------------------------------------------------
			// 使用 session 來判斷
			if(isset($_SESSION['running_gamecode'])){
					$gamename_sql = 'SELECT gamename FROM casino_gameslist WHERE id=\''.$_SESSION['running_gamecode'].'\';';
					$gamename_result = runSQLall($gamename_sql);
				$logger = '你已经在另一个视窗，执行'.$gamename_result[1]->gamename.'游戏。请关闭该游戏，并重新整理后点选需要前往的新的游戏...';
				//echo "<p>$logger</p>";
				// echo "<script>alert('$logger');</script>";
				// echo '<input onclick="window.close();" value="你已經在另一個視窗執行遊戲，請關閉本視窗。" type="button">';
				echo "<script>alert('$logger');</script>";
				die($logger);
			}else{
				// 沒有其他 gamecode 正在執行, 所以設定 gamecode
				$_SESSION['running_gamecode'] = $gamecode;
			}
			// ------------------------------------------------------------------------------------
			// 清除時機，當 gamelobby.php reload or 重新選擇分類時清除。
			// ------------------------------------------------------------------------------------



			// ------------------------------------------------------------------------------------
			// 檢查已經登入的使用者，session and DB 中是否有 IG2 帳號，沒有就建立。
			// ------------------------------------------------------------------------------------
			if($member[1]->ig_account == NULL AND $system_mode != 'developer') {
			// 建立 IG 的會員帳號
				$rc = create_casino_ig_account($debug);
				// $rc['ErrorCode']     = 20;  表示 IG2 API 活著, 且 IG2 有餘額。
				// $rc['ErrorCode']     = 0;   表示剛才建立 IG2 帳號, 且餘額都已經更新。
				// var_dump($rc);
				if($rc['ErrorCode'] == 20 OR $rc['ErrorCode'] == 0) {
				// IG2 API 正常 , 繼續工作
				// 取得 ig2 account來源變數 -- 建立帳號時，會寫入 session
					$gpk2_ig_account  	= $_SESSION['member']->ig_account;
					$gpk2_ig_password 	= $_SESSION['member']->ig_password;
				}else{
					// IG API 有問題, 停止工作
					$logger = '建立 IG 的會員帳號 IG API 有問題, 停止工作';
					memberlog2db($member[1]->account,'IG2 API','error', "$logger");
					echo '<script>alert("'.$logger.'");window.close();</script>';
					die($logger);
				}
			}elseif($system_mode == 'developer'){
				$logger = '開發環境不可建立帳號！！';
				echo '<script>alert("'.$logger.'");window.close();</script>';
				die($logger);
			}else{
				// 取得 ig2 account來源變數 in DB
				$gpk2_ig_account  	= $member[1]->ig_account;
				$gpk2_ig_password 	= $member[1]->ig_password;
			}
			// ------------------------------------------------------------------------------------

			// ------------------------------------------------------------------------------------
			// 檢查gtoken_lock是否為空 ，如果是空的話就 所有代幣GTOKEN to IG 轉帳 , 並設定為 IG 使用狀態。
			// 如果是在其他娛樂城的話, 去其他娛樂城把餘額收回來。 --- todo by barkley 2017.5.8
			// ------------------------------------------------------------------------------------
			if($member[1]->gtoken_lock == NULL AND $system_mode != 'developer') {
				// 只有代幣沒被使用的時候，才可以加值。如果已經使用，需要取回才可以加值。
				$topic = '將現金 GCASH 依據設定值，自動加值到 GTOKEN 上面。';
				echo "<br>".$topic;
				$trans_result = Transferout_GCASH_GTOKEN_balance($_SESSION['member']->id);
				//var_dump($trans_result);
				// 自動加值出錯時跳提示通知
				if(!($trans_result['code'] == 1 OR $trans_result['code'] == 403)){
				  $logger = '（'.$trans_result['code'].'）'.$trans_result['messages'];
				  echo "<script>alert('$logger');</script>";
				  //die($logger);
				}

				// 將 GTOKEN 代幣全部傳送到 IG Casino 上，並將 DB GTOKEN_LOCK 上鎖
				$topic = '將 GTOKEN 代幣全部傳送到 IG Casino 上，並將 DB GTOKEN_LOCK 上鎖';
				echo "<br>".$topic;
				$rr = transferout_gtoken_ig_casino_balance($member[1]->id);
				// 轉到IG出錯時跳提示通知
				if($rr['code'] != 1 AND $rr['code'] != 15){
				  $logger = '（'.$rr['code'].'）IG 轉帳錯誤，請洽客服人員!';
				  echo "<script>alert('$logger');</script>";
				  die($logger);
				}

				//var_dump($rr);
			}elseif($system_mode != 'developer') {
				$casino_lock = $member[1]->gtoken_lock;
				$undergoing_casino_sql = 'SELECT * FROM casino_list WHERE casinoid = \''.$casinoname.'\';';
				//echo $undergoing_casino_sql;
				$undergoing_casino = runSQLall($undergoing_casino_sql);

				if( isset($casino_lock) AND $casino_lock == 'IG' ){
						if(!isset($_SESSION['wallet_transfer']) AND $member[1]->gtoken_balance >= 1 ) {
							// // 旗標,轉餘額動作執行時，透過這個旗標控制同一個使用者，不能有第二個執行緒進入。除非已經清除這個旗標。
							// $_SESSION['wallet_transfer'] = 'Account:'.$_SESSION['member']->account.' run in '.$casino_retrieve[$casino_lock];
							// // 使用session時，若先前的頁面尚未執行完畢，預設session會被鎖住。
							// // 此時，若執行另外一個也有使用session的頁面，則須等前一個頁面執行完畢，才能再執行。
							// // 要避免等前一個頁面執行完畢，才能執行下一個頁面的情況，可以使用 session_write_close() ，告之不會再對session做寫入的動作，這樣其他頁面就不會等此頁面執行完才能再執行。
							// // session_write_close() ;
							// // 取回娛樂城的餘額
							// $topic = '將 GTOKEN 代幣從其他 Casino 取回，並將 DB GTOKEN_LOCK 解鎖';
							// echo "<br>".$topic;
							//
							// $member_id = $_SESSION['member']->id;
							// $rr = $casino_retrieve[$casino_lock]($member_id, 0);
							//
							// //取回MG餘額成功 , == 100
							// if($rr['code'] == 1) {
											// 只有代幣沒被使用的時候，才可以加值。如果已經使用，需要取回才可以加值。
									$topic = '將現金 GCASH 依據設定值，自動加值到 GTOKEN 上面。';
									echo "<br>".$topic;
									$trans_result = Transferout_GCASH_GTOKEN_balance($_SESSION['member']->id);
									//var_dump($trans_result);
									// 自動加值出錯時跳提示通知
									if(!($trans_result['code'] == 1 OR $trans_result['code'] == 403)){
									  $logger = '（'.$trans_result['code'].'）'.$trans_result['messages'];
									  echo "<script>alert('$logger');</script>";
									  //die($logger);
									}

									// 將 GTOKEN 代幣全部傳送到 IG Casino 上，並將 DB GTOKEN_LOCK 上鎖
									$topic = '將 GTOKEN 代幣全部傳送到 IG Casino 上，並將 DB GTOKEN_LOCK 上鎖';
									echo "<br>".$topic;
									$rr = transferout_gtoken_ig_casino_balance($member[1]->id);
									// 轉到IG出錯時跳提示通知
									if($rr['code'] != 1 AND $rr['code'] != 15){
									  $logger = '（'.$rr['code'].'）IG 轉帳錯誤，請洽客服人員!';
									  echo "<script>alert('$logger');</script>";
									  die($logger);
									}
							// }else{
							// 	$logger = '（'.$rr['code'].'）IG 轉帳取回錯誤，請洽客服人員!';
							// 	echo "<script>alert('$logger');</script>";
							// 	die($logger);
							// }
						}elseif(isset($_SESSION['wallet_transfer'])) {
							$logger = '另外有錢包程式正在執行，請重新整理頁面後後再次執行。';
							$return_json_arr = array('logger' => $logger);
						}else{ }
				}else{
					if(!isset($_SESSION['wallet_transfer'])) {
						// 旗標,轉餘額動作執行時，透過這個旗標控制同一個使用者，不能有第二個執行緒進入。除非已經清除這個旗標。
						$_SESSION['wallet_transfer'] = 'Account:'.$_SESSION['member']->account.' run in '.$casino_retrieve[$casino_lock];
						// 使用session時，若先前的頁面尚未執行完畢，預設session會被鎖住。
						// 此時，若執行另外一個也有使用session的頁面，則須等前一個頁面執行完畢，才能再執行。
						// 要避免等前一個頁面執行完畢，才能執行下一個頁面的情況，可以使用 session_write_close() ，告之不會再對session做寫入的動作，這樣其他頁面就不會等此頁面執行完才能再執行。
						// session_write_close() ;
						// 取回娛樂城的餘額
						$topic = '將 GTOKEN 代幣從其他 Casino 取回，並將 DB GTOKEN_LOCK 解鎖';
						echo "<br>".$topic;

						$member_id = $_SESSION['member']->id;
						$rr = $casino_retrieve[$casino_lock]($member_id, 0);

						//取回餘額成功 , == 100
						if($rr['code'] == 1) {
							$logger = $rr['messages'];
							echo '<p align="center">'.$logger.'</p>';
							echo "<script>window.location.reload();</script>";
							die($logger);
						}else{
							$logger = $rr['messages'];
							echo "<script>alert('$logger');</script>";
							die($logger);
						}
					}else{
						$logger = '另外有錢包程式正在執行，請重新整理頁面後後再次執行。';
						$return_json_arr = array('logger' => $logger);
					}
				}
			}else{

			}
			// ------------------------------------------------------------------------------------


			// 已經有登入 IG , 繼續
			// ------------------------------------------------------------------------------------
			// 1.查詢 ig2 餘額是否大於 1 ,如果大於 1 的時候就不用收回其他 casino 的 coin, 先把 IG2 內的所有錢花光。當餘額小於 1 時，下次登入又會自己轉錢了。
			// ------------------------------------------------------------------------------------
			// 動作： GetBalance 取得目前餘額

			$IG_API_data = array(
				'username' => $gpk2_ig_account,
				'password' => md5($gpk2_ig_password)
			);
			$IG_API_result = ig_gpk_api('GetBalance', $debug, $IG_API_data, 'trade');

			// 把查詢 API 回傳的資料顯示出來 for debug
			if($debug == 1) {
				var_dump($IG_API_result);
			}

			if($system_mode == 'developer') {
				$IG_amount = 1;
			}else{
				$IG_amount = $IG_API_result['Result']->params->balance;
			}

			// 判斷 IG2 是否還有餘額，且餘額 >= 1
			if($IG_API_result['errorcode'] == 0 AND isset($IG_API_result['Result']->errorCode) AND $IG_API_result['Result']->errorCode == 0 AND $IG_amount >= 1){
			// -------------------------------------------------
			// 直接轉到 IG 指定的 gamecode , 因為餘額大於等於 1 就可以直接玩了!! 等沒錢再轉錢來處理。
			// -------------------------------------------------
			// 取得進進入 ig game 的網址 -- 需要兼顧 html5 的game , 需要再改寫
				$gamecode_url = ig_gameurl($gpk2_ig_account, md5($gpk2_ig_password), $gamecode);

				// 如果網址不為空的話，就是有資料。則處理網址轉址。
				// echo $gamecode_url;
				if($gamecode_url != '') {
					$logger = '登入 IG Game:'.$gamecode;
					memberlog2db($member[1]->account,'loginIGGame','info', "$logger");
					unset($_SESSION['running_gamecode']);

					// debug info
					if($debug == 1) {
						// 顯示在頁面上, for debug
						echo $gamecode_url;
						echo "<p><a href='$gamecode_url'>$gamecode</a></p>";
					}else{
						// 直接跳轉, 不用顯示
						echo '<script>setTimeout(function(){document.location.href = "'.$gamecode_url.'";},500);</script>';
						$logger = "<p>請不要關閉瀏覽器 Javascript 功能，點擊連結繼續進入遊戲。<a href='$gamecode_url'>$gamecode</a></p>";
						die($logger);
					}

				}else{
					$logger = $gamecode.',沒有網址，應該有哪裡出錯了。';
					memberlog2db($member[1]->account,'loginIGGame','error', "$logger");
					unset($_SESSION['running_gamecode']);
					die($logger);
				}
				// -------------------------------------------------


			}elseif($IG_API_result['errorcode'] == 0 AND isset($IG_API_result['Result']->errorCode) AND $IG_API_result['Result']->errorCode == 0 AND $IG_amount < 1) {
				// --------------------------------------------------
				// 判斷 IG餘額 < 1 (沒有錢)， 登入 IG FLASH game
				// --------------------------------------------------
				// 判斷 IG2 餘額  < 1 , 0.xx 都算是沒有錢!!
				// 判斷 GTOKEN 有沒有錢，有錢就全部轉過去 IG
				// 判斷 GTOKEN < 1 ，判斷 GCASH 有沒有錢，如果 AUTO 轉帳設定為開啟，就轉帳到 GOTKEN 並直接轉帳到 IG 帳戶。
				// echo '判斷 IG2 餘額  < 1 , 0.xx 都算是沒有錢!! ig2 帳戶餘額為 < 1  , 判斷 gpk2 有沒有錢，有錢就轉過去 ig2 , 沒錢就提示通通沒有錢了。<hr>';
				// ======================================================================
				// 取得進進入 ig game 的網址

				$gamecode_url = ig_gameurl($gpk2_ig_account, md5($gpk2_ig_password), $gamecode);

				// 如果網址不為空的話，就是有資料。則處理網址轉址。
				// echo $gamecode_url;
				if($gamecode_url != '') {
					$logger = '登入 IG Game:'.$gamecode;
					memberlog2db($member[1]->account,'loginIGGame','info', "$logger");
					unset($_SESSION['running_gamecode']);

					if($debug == 1) {
						// 顯示在頁面上, for debug
						echo $gamecode_url;
						echo "<p><a href='$gamecode_url'>$gamecode</a></p>";

						// 算累積花費時間, 另一個開始放在 config.php
						$program_spent_time = microtime(true) - $program_start_time;
						$program_spent_time_html = "<p>判斷 IG餘額 < 1 (沒有錢)， 登入 IG game。花費時間： $program_spent_time </p>";
						var_dump($program_spent_time_html);
					}else{
						// 直接跳轉, 不用顯示
						echo '<script>setTimeout(function(){document.location.href = "'.$gamecode_url.'";},500);</script>';
						$logger = '請不要關閉瀏覽器 Javascript 功能。';
						die($logger);
					}

				}else{
					$logger = $gamecode.',沒有轉址，應該有哪裡出錯了。';
					memberlog2db($member[1]->account,'loginIGGame','error', "$logger");
					unset($_SESSION['running_gamecode']);
					die($logger);
				}
				// ======================================================================

			}else{
				// 有錯誤的 GetAccountBalance
				$logger = 'IG API 取得錯誤，請稍候再試。GetAccountBalance ERROR !!!';
				memberlog2db($member[1]->account,'IG API','error', "$logger");
				// echo '<script>alert("'.$logger.'");window.close();</script>';
				// var_dump($IG_API_result);
				die($logger);
			}

		}else{
			$r['code']     = 401;
			$r['messages']  = '會員不存在，或者已經失效。請聯絡客服人員處理。';
		}


	// ------------------------------------------------------------------------------------
	}else{
		if(isset($_SESSION['member']->therole) AND ($_SESSION['member']->therole == 'R')) {
		$logger = '管理员不可以登入游戏。确认回到首页。';
		}else{
		$logger = "你还没有登入，请重新登入系统后继续。确认回到登入页面。";
		}
		// 沒有登入，請使用者回去登入
		echo '<script>alert("'.$logger.'");window.close();</script>';
		echo '<script>document.location.href="home.php";</script>';
		die("(x)$logger");
	}
	// ------------------------------------------------------------------------------------



	// ----------------------------------------------------------------------------
	}elseif($action == 'Retrieve_IG_Casino_balance' AND isset($_SESSION['member']) AND ($_SESSION['member']->therole == 'A' OR $_SESSION['member']->therole == 'M' OR $_SESSION['member']->therole == 'R' ) ){
	// ----------------------------------------------------------------------------
	// 取回 IG Casino 的餘額，並檢查上次離開時和目前的差額得出派彩金額。 v2017.1.6
	// lobby_iggame_action.php?a=Retrieve_IG_Casino_balance
	// ----------------------------------------------------------------------------


	// ----------------------------------------------------------------------------
	// 搭配 wallets.php 使用 , 各娛樂城取回錢幣都要設定這個 session 避免重複執行。
	if(!isset($_SESSION['wallet_transfer'])) {
		// 旗標,轉餘額動作執行時，透過這個旗標控制同一個使用者，不能有第二個執行緒進入。除非已經清除這個旗標。
		$_SESSION['wallet_transfer'] = 'Account:'.$_SESSION['member']->account.' run in '.'Retrieve_IG_Casino_balance';
		// 使用session時，若先前的頁面尚未執行完畢，預設session會被鎖住。
		// 此時，若執行另外一個也有使用session的頁面，則須等前一個頁面執行完畢，才能再執行。
		// 要避免等前一個頁面執行完畢，才能執行下一個頁面的情況，可以使用 session_write_close() ，告之不會再對session做寫入的動作，這樣其他頁面就不會等此頁面執行完才能再執行。
		// session_write_close() ;
		// 取回娛樂城的餘額
		$member_id = $_SESSION['member']->id;
		$rr = retrieve_ig_casino_balance($member_id, 0);

		//取回IG餘額成功 , == 100
		if($rr['code'] == 1) {
		//$logger = $rr['messages'];
		$logger = '取回现金'.$rr['balance'];
		echo '<p align="center">'.$logger.'</p>';
		}else{
		//$logger = $rr['messages'];
		$logger = 'ERROR：'.$rr['code'].'，取回现金失败，请联络客服人员！';
		echo '<p align="center">'.$logger.'</p>';
		}

	}else{
		$logger = '另外有錢包程式正在執行，請重新整理頁面後後再次執行。';
		echo "<p>$logger</p>";
		echo '<p align="center">'.$logger.'</p>';
	}
	//echo '<br><br><p align="center"><button type="button" onclick="window.close();">關閉視窗</button></p>';
	// 清除旗標的功能寫在呼叫他的地方。
	// ----------------------------------------------------------------------------



	// ----------------------------------------------------------------------------
	}elseif($action == 'Transferout_GTOKEN_IG_Casino_balance' AND isset($_SESSION['member']) AND ($_SESSION['member']->therole == 'A' OR $_SESSION['member']->therole == 'M' OR $_SESSION['member']->therole == 'R' )) {
	// ----------------------------------------------------------------------------
	// 將 GTOKEN 全部傳送到 IG Casino 上，並將 DB GTOKEN_LOCK 上鎖
	// for test: lobby_iggame_action.php?a=Transferout_GTOKEN_IG_Casino_balance
	// ----------------------------------------------------------------------------

	// ----------------------------------------------------------------------------
	$topic = '將 GTOKEN 代幣全部傳送到 IG Casino 上，並將 DB GTOKEN_LOCK 上鎖';
	echo "<br>".$topic;
	$rr = transferout_gtoken_ig_casino_balance($_SESSION['member']->id);

	//var_dump($rr);
	// ----------------------------------------------------------------------------

	// ----------------------------------------------------------------------------
	}elseif($action == 'load') {
	// ----------------------------------------------------------------------------
	// test developer
	// ----------------------------------------------------------------------------
	// var_dump($_POST);

	}else{
	echo '你不被允許操作娛樂城。';
	}

	if($debug == 1) {
	// 算累積花費時間, 另一個開始放在 config.php
	$program_spent_time = microtime(true) - $program_start_time;
	$program_spent_time_html = "<p>花費時間： $program_spent_time </p>";
	echo $program_spent_time_html;
	}
}else{
	$return_html = '
		<div style="position: absolute; top: 50%; left: 50%;">
		<center>
		<p><img width="180" src="'.$cdnrooturl.'casinologo/IG.png" alt="Playtech" /></p>
		<p><h3><font color="#cccccc">'.$casino_name.'维护中，请稍侯再试 . . .</font></h3></p>
		</center></div>';
	echo $return_html;
}
// ----------------------------------------------------------------------------
// END
// ----------------------------------------------------------------------------

?>
