<?php
// ----------------------------------------------------------------------------
// Features:   娛樂城遊戲共通商業邏輯
// File Name:  lobby_casino_game_action.php
// Author:	   Letter
// Related:    lobby_casino_game_lib.php
// Log:
// 2019.09.25 新建
// ----------------------------------------------------------------------------
/*
// in 帳號A 預計登入 casino，帳號為 casino_A , 轉錢的餘額為 $$$
0. 使用者需要登入系統,才可以繼續, 否則結束, 預設 錢包 wallets
1. 判斷使用者在 casino 是否有帳號，如果沒有的話就建立帳號, 如果有的話檢查看是否有餘額。

//Retrieve_Casino_balance();
何時取回娛樂城的餘額？
1. 登入系統的時候 -- 關閉，因為有風險。
2. 登出系統的時候 -- ok
3. 轉換娛樂城的時候 -- 尚未有第二個娛樂城

//Transferout_Casino_balance();
何時將餘額轉出到娛樂城？
1. 當玩家點擊指定的遊戲城，檢查餘額是否在該遊戲城
2. 如果在該遊戲城的話，不用轉餘額。直接登入該遊戲城
3. 如果不在，該遊戲城，先檢查餘額是否在 casino 本地端
4. 如果不在的話，將所有的餘額轉回來 casino 本地端
5. 轉回再轉到指定的娛樂城
*/
// ----------------------------------------------------------------------------

// debug on/off (1/0) , 除錯功能, 設定為 1 可以觀看連接到 casino game 的 URL
$debug = 0;
$casinoid = '';
// 取得娛樂城 ID
if (isset($_GET['casinoname'])) {
	$casinoid = filter_var($_GET['casinoname'], FILTER_SANITIZE_STRING);
}


require_once dirname(__DIR__) . "/config.php";
// 支援多國語系
require_once dirname(__DIR__) . "/i18n/language.php";
// 自訂函式庫
require_once dirname(__DIR__) . "/lib.php";
// 自訂casino lobby通用函式庫
require_once dirname(__DIR__) . "/casino/casino_config.php";
// 娛樂城設定通用函式庫
require_once dirname(__FILE__) . "/lobby_casino_game_lib.php";


// 檢查娛樂城的狀態，如果是 OPEN=1(開啟) 才顯示選單及遊戲
$check_casino_state_sql = 'SELECT * from casino_list WHERE casinoid = \'' . $casinoid . '\'';
$check_casino_state_result = runSQLall($check_casino_state_sql, 0, 'r');
if ($check_casino_state_result['0'] > '0') {
	$casino_name = $check_casino_state_result['1']->casino_name;
	$gamelist_table = $check_casino_state_result['1']->casino_dbtable;
	$casino_state = $check_casino_state_result['1']->open;
}

// 判斷娛樂城是否開啟
if ($casino_state == 1) {
	// 檢查會員是否登入
	if (isset($_GET['a']) AND isset($_SESSION['member'])) {
		$action = filter_var($_GET['a'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
		// 顯示讀取圖示
		$page_html = '
			<div style="background-color:black;	height: 100vh; display: flex; justify-content: center;
					align-items: center; overflow: hidden; ">
				<img src="' . $config['companylogo'] . '" alt="LOGO">
				<img src="' . $cdnrooturl . 'loading_ball.gif" alt="' . $casinoid . '">
			</div>';

		// 如果是 debug 就不顯示黑底+logo , 以利於 debug
		// 如果是 Retrieve_Casino_balance (取回 娛樂城 餘額) 就顯示不同的畫面。
		if ($debug == 1 OR $action == 'Retrieve_Casino_balance') {
			// debug , show debug information
		} else {
			echo $page_html;
		}
	} else {
		die('(402)不合法的呼叫');
	}

	// 進入遊戲
	if ($action == 'goto_game' AND isset($_SESSION['member'])) {
		// 從 gamelobby.php 取得 gamecode 產生網址, 轉 url 到娛樂城
		// 已經有登入,且需要 wallet 也在 session 內才可以繼續 , 只有會員 M 和代理商 A 才可以執行遊戲,管理員 R 不可以執行, 避免做帳的問題
		if (isset($_SESSION['member']) AND ($_SESSION['member']->therole == 'A' OR $_SESSION['member']->therole == 'M')) {
			// 檢查會員是否存在
			$member_id = $_SESSION['member']->id;
			$member_sql = "SELECT root_member.id,account,gtoken_balance,gtoken_lock,
	            casino_accounts->'" . $casinoid . "'->>'account' as " . $casinoid . "_account,
	            casino_accounts->'" . $casinoid . "'->>'password' as " . $casinoid . "_password,
	            casino_accounts->'" . $casinoid . "'->>'balance' as " . $casinoid . "_balance FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.id = '$member_id' AND root_member.status = '1';";
			$member = runSQLall($member_sql, 0, 'r');
			if ($member[0] == 1) {
				// 會員存在
				// 取得外部來源變數 gamecode
				$gamecode = filter_var($_GET['gamecode'], FILTER_SANITIZE_STRING);
				$casinoname = filter_var($_GET['casinoname'], FILTER_SANITIZE_STRING);
				$gameplatform = filter_var($_GET['gameplatform'], FILTER_SANITIZE_STRING);

				// ------------------------------------------------------------------------------------
				// 檢查是否已經有人按了 game 還在等待處理, 將 game code 存在 session 內。
				// 判斷剛剛同一個人有沒有很緊張，一直按下很多個 gamecode 按鈕
				// ------------------------------------------------------------------------------------
				// 使用 session 來判斷
				// 沒有其他 gamecode 正在執行, 所以設定 gamecode
				$_SESSION['running_gamecode'] = $gamecode;
				// ------------------------------------------------------------------------------------
				// 清除時機，當 gamelobby.php reload or 重新選擇分類時清除。
				// ------------------------------------------------------------------------------------

				// ------------------------------------------------------------------------------------
				// 檢查已經登入的使用者，session and DB 中是否有 娛樂城 帳號，沒有就建立。
				// ------------------------------------------------------------------------------------
				if (empty(getCasinoAccount($casinoid, $member[1]))) {
					// 建立 娛樂城 的會員帳號
					$rc = create_casino_account($debug);
					// $rc['ErrorCode'] = 20;  表示 GT API 活著，而且有餘額
					// $rc['ErrorCode'] = 0;   表示剛才建立 娛樂城 帳號，且餘額都已經更新

					if ($rc['ErrorCode'] == 20 OR $rc['ErrorCode'] == 0) {
						// GT API 正常 , 繼續工作
						// 取得娛樂城帳戶來源變數 -- 建立帳號時，會寫入 session
						$casino_account = getCasinoAccount($casinoid, $_SESSION['member']);
						$casino_password = getCasinoPassword($casinoid, $_SESSION['member']);
					} else {
						// GT API 有問題, 停止工作
						$logger = '建立 ' . $casinoid . ' 的會員帳號 ' . $casinoid . ' API 有問題, 停止工作';
						memberlog2db(getCasinoAccount($casinoid, $member[1]), '' . $casinoid . ' API',
							'error', "$logger");
						echo '<script>alert("' . $logger . '");window.close();</script>';
						die($logger);
					}
				} else {
					// 取得資料庫內娛樂城帳戶來源變數
					$casino_account = getCasinoAccount($casinoid, $member[1]);
					$casino_password = getCasinoPassword($casinoid, $member[1]);

					// 檢查玩家帳號是否存在
					$GT_API_result = GetCasinoAccountInfo($casino_account, $debug);
					if ($GT_API_result['code'] == 1) {
						// 建立 娛樂城 的會員帳號
						$rc = create_casino_account($debug);
						// $rc['ErrorCode'] = 20;  表示 GT API 活著，而且有餘額
						// $rc['ErrorCode'] = 0;   表示剛才建立 娛樂城 帳號，且餘額都已經更新
						if ($rc['ErrorCode'] == 20 OR $rc['ErrorCode'] == 0) {
							// GT API 正常 , 繼續工作
							// 取得 娛樂城帳號 資料 -- 建立帳號時，會寫入 session
							$casino_account = getCasinoAccount($casinoid, $_SESSION['member']);
							$casino_password = getCasinoPassword($casinoid, $_SESSION['member']);
						} else {
							// GT API 有問題, 停止工作
							$logger = '建立 ' . $casinoid . ' 的會員帳號 ' . $casinoid . ' API 有問題, 停止工作';
							memberlog2db(getCasinoAccount($casinoid, $member[1]), '' . $casinoid . ' API',
								'error', "$logger");
							echo '<script>alert("' . $logger . '");window.close();</script>';
							die($logger);
						}
					} elseif ($GT_API_result['code'] != 0) {
						// GT API 有問題, 停止工作
						$logger = $casinoid . ' 的會員帳號有問題, 請洽客服人員！';
						memberlog2db(getCasinoAccount($casinoid, $member[1]), $casinoid . ' API',
							'error', "$logger");
						echo '<script>alert("' . $logger . '");window.close();</script>';
						die($logger);
					}
				}

				if ($member[1]->gtoken_lock == NULL) {
					// 只有代幣沒被使用(gtoken_lock != NULL)的時候，才可以加值。如果已經使用，需要取回才可以加值
					$topic = '將現金 GCASH 依據設定值，自動加值到 GTOKEN 上面。';
					echo "<br>" . $topic;
					$trans_result = Transferout_GCASH_GTOKEN_balance($_SESSION['member']->id, $debug);

					// 自動加值出錯時跳提示通知
					if (!($trans_result['code'] == 1 OR $trans_result['code'] == 403)) {
						$logger = '（' . $trans_result['code'] . '）' . $trans_result['messages'];
						echo "<script>alert('$logger');</script>";
					}

					// 將 GTOKEN 代幣全部傳送到 Casino 上，並將 DB GTOKEN_LOCK 上鎖
					$topic = '將 GTOKEN 代幣全部傳送到 ' . $casinoid . ' Casino 上，並將 DB GTOKEN_LOCK 上鎖';
					echo "<br>" . $topic;
					$rr = transferout_gtoken_to_casino_balance($member[1]->id, $debug);
					// 轉帳出錯時跳提示通知
					if ($rr['code'] != 1) {
						$logger = '（' . $rr['code'] . '）' . $casinoid . ' 轉帳錯誤，請洽客服人員!';
						echo "<script>alert('$logger');</script>";
						die($logger);
					}

				} else {
					// 取得 遊戲幣 在哪個娛樂城
					$casino_lock = $member[1]->gtoken_lock;
					// 取得要前往娛樂城
					$undergoing_casino_sql = 'SELECT * FROM casino_list WHERE casinoid = \'' . $casinoname . '\';';
					$undergoing_casino = runSQLall($undergoing_casino_sql, 0, 'r');

					if (isset($casino_lock) AND $casino_lock == $casinoid) {
						// 查詢 娛樂城 餘額是否大於 1，如果大於 1 的時候就不用轉帳，先把 娛樂城 內的所有錢花光
						// 當餘額小於 1 時，先取回餘額再重新轉入
						// 先取回餘額，各娛樂城取回錢幣都要設定這個 session 避免重複執行。
						if (!isset($_SESSION['wallet_transfer']) AND $member[1]->gtoken_balance >= 1) {
							// 旗標，轉餘額動作執行時，透過這個旗標控制同一個使用者，不能有第二個執行緒進入。除非已經清除這個旗標
							// 使用 session 時，若先前的頁面尚未執行完畢，預設 session 會被鎖住
							// 此時，若執行另外一個也有使用 session 的頁面，則須等前一個頁面執行完畢，才能再執行。
							$topic = '將現金 GCASH 依據設定值，自動加值到 GTOKEN 上面。';
							echo "<br>" . $topic;
							$trans_result = Transferout_GCASH_GTOKEN_balance($_SESSION['member']->id, $debug);

							// 自動加值出錯時跳提示通知
							if (!($trans_result['code'] == 1 OR $trans_result['code'] == 403)) {
								$logger = '（' . $trans_result['code'] . '）' . $trans_result['messages'];
								echo "<script>alert('$logger');</script>";
							}

							// 將 GTOKEN 代幣全部傳送到 娛樂城 上，並將 DB GTOKEN_LOCK 上鎖
							$topic = '將 GTOKEN 代幣全部傳送到 ' . $casinoid . ' Casino 上，並將 DB GTOKEN_LOCK 上鎖';
							echo "<br>" . $topic;
							$rr = transferout_gtoken_to_casino_balance($member[1]->id, $debug);
							// 轉帳出錯時跳提示通知
							if ($rr['code'] != 1) {
								$logger = '（' . $rr['code'] . '）' . $casinoid . ' 轉帳錯誤，請洽客服人員!';
								echo "<script>alert('$logger');</script>";
								die($logger);
							}

						} elseif (isset($_SESSION['wallet_transfer'])) {
							$logger = '另外有錢包程式正在執行，請重新整理頁面後後再次執行。';
							echo "<p>$logger</p>";
							echo '<p align="center">' . $logger . '</p>';
						}

					} else {
						// 將前往不同casino，需先將錢取回再轉到該casino的action頁面
						require_once dirname(__DIR__) . getCasinoLib($casino_lock);

						if (!isset($_SESSION['wallet_transfer'])) {
							// 未設定旗標
							$_SESSION['wallet_transfer'] = 'Account:' . $_SESSION['member']->account . ' run in ' . getCasinoRetrieveFunc($casino_lock);
							// 使用session時，若先前的頁面尚未執行完畢，預設session會被鎖住。
							// 此時，若執行另外一個也有使用session的頁面，則須等前一個頁面執行完畢，才能再執行
							// 取回娛樂城的餘額
							$topic = '將 GTOKEN 代幣從其他 Casino 取回，並將 DB GTOKEN_LOCK 解鎖';
							echo "<br>" . $topic;

							$member_id = $_SESSION['member']->id;
							$rr = getCasinoRetrieveFunc($casino_lock)($member_id, 0);

							//取回 娛樂城 餘額成功
							if ($rr['code'] == 1) {
								// 只有代幣沒被使用的時候，才可以加值。如果已經使用，需要取回才可以加值。
								$topic = '將現金 GCASH 依據設定值，自動加值到 GTOKEN 上面。';
								echo "<br>" . $topic;
								$trans_result = Transferout_GCASH_GTOKEN_balance($_SESSION['member']->id, $debug);

								// 將 GTOKEN 代幣全部傳送到 目的地娛樂城 上，並將 DB GTOKEN_LOCK 上鎖
								$topic = '將 GTOKEN 代幣全部傳送到 ' . $casinoid . ' Casino 上，並將 DB GTOKEN_LOCK 上鎖';
								echo "<br>" . $topic;
								$rr = transferout_gtoken_to_casino_balance($member[1]->id, $debug);
							} else {
								$logger = $rr['messages'];
								echo '<p align="center">' . $logger . '</p>';
								echo "<script>window.location.reload();</script>";
								die($logger);
							}
						} else {
							$logger = '另外有錢包程式正在執行，請重新整理頁面後後再次執行。';
							$return_json_arr = array('logger' => $logger);
						}
					}
				}

				// 已經有登入 娛樂城 , 繼續
				// ------------------------------------------------------------------------------------
				// 1.查詢 娛樂城 餘額是否大於 1 ,如果大於 1 的時候就不用收回其他 casino 的 coin, 先把 GPK2 內的所有錢花光。當餘額小於 1 時，下次登入又會自己轉錢了。
				// ------------------------------------------------------------------------------------
				// 動作： GetAccountDetails 取得目前 餘額
				$GT_API_result = GetCasinoAccountInfo($casino_account, $debug);

				// 把查詢 API 回傳的資料顯示出來
				if ($debug == 1) {
					var_dump($GT_API_result);
				}

				$casino_amount = $GT_API_result['balance'];
				// 判斷 娛樂城 是否還有餘額，且餘額 >= 1
				if ($GT_API_result['code'] == 0 AND $casino_amount >= 1) {
					// 直接轉到 娛樂城 指定的 gamecode , 因為餘額大於等於 1 就可以直接玩了!! 等沒錢再轉錢來處理
					$gamecode_url = getGameUrl($casino_account, $casino_password, $gamecode, $debug);

					// 如果網址不為空的話，就是有資料。則處理網址轉址
					if ($gamecode_url != '') {
						$logger = '登入 ' . $casinoid . ' Game:' . $gamecode;
						memberlog2db($member[1]->account, 'login' . $casinoid . 'Game', 'info', "$logger");
						unset($_SESSION['running_gamecode']);

						// debug info
						if ($debug == 1) {
							// 顯示在頁面上, for debug
							echo $gamecode_url;
							echo "<p><a href='$gamecode_url'>$gamecode</a></p>";
						} else {
							// 直接跳轉, 不用顯示
							echo '<script>setTimeout(function(){document.location.replace("' . $gamecode_url . '");},500);</script>';
							$logger = "<p>請不要關閉瀏覽器 Javascript 功能，點擊連結繼續進入遊戲。<a href='$gamecode_url'>$gamecode</a></p>";
							die($logger);
						}

					} else {
						$logger = $gamecode . ',沒有網址，應該有哪裡出錯了。';
						memberlog2db($member[1]->account, 'login' . $casinoid . 'Game', 'error', "$logger");
						unset($_SESSION['running_gamecode']);
						die($logger);
					}
				} elseif ($GT_API_result['code'] == 0 AND $casino_amount < 1) {
					// 判斷 娛樂城 餘額 < 1 (沒有錢)， 登入 GPK2 FLASH game
					// 判斷 娛樂城 餘額  < 1 , 0.xx 都算是沒有錢!!
					// 判斷 GTOKEN 有沒有錢，有錢就全部轉過去 娛樂城
					// 判斷 GTOKEN < 1 ，判斷 GCASH 有沒有錢，如果 AUTO 轉帳設定為開啟，就轉帳到 GOTKEN 並直接轉帳到 GPK2 帳戶。

					// 取得進進入 mg game 的網址
					$gamecode_url = getGameUrl($casino_account, $casino_password, $gamecode, $debug);

					// 如果網址不為空的話，就是有資料。則處理網址轉址
					if ($gamecode_url != '') {
						$logger = '登入 ' . $casinoid . ' Game:' . $gamecode;
						memberlog2db($member[1]->account, 'login' . $casinoid . 'Game', 'info', "$logger");
						unset($_SESSION['running_gamecode']);

						if ($debug == 1) {
							echo $gamecode_url;
							echo "<p><a href='$gamecode_url'>$gamecode</a></p>";

							// 算累積花費時間, 另一個開始放在 config.php
							$program_spent_time = microtime(true) - $program_start_time;
							$program_spent_time_html = "<p>判斷 '.$casinoid.' 餘額 < 1 (沒有錢)， 登入 '.$casinoid.' game。花費時間： $program_spent_time </p>";
							var_dump($program_spent_time_html);
						} else {
							// 直接跳轉, 不用顯示
							echo '<script>setTimeout(function(){document.location.replace("' . $gamecode_url . '");},500);</script>';
							$logger = '請不要關閉瀏覽器 Javascript 功能。';
							die($logger);
						}
					} else {
						$logger = $gamecode . ',沒有轉址，應該有哪裡出錯了。';
						memberlog2db($member[1]->account, 'login' . $casinoid . 'Game', 'error', "$logger");
						unset($_SESSION['running_gamecode']);
					}
				} else {
					// 從 GAPI 取得會員資訊有錯誤
					$logger = $casinoid . ' API 取得錯誤，請稍候再試。 GetAccountDetails ERROR !!!';
					memberlog2db($member[1]->account, $casinoid . ' API', 'error', "$logger");
					die($logger);
				}
			} else {
				$r['code'] = 401;
				$r['messages'] = '會員不存在，或者已經失效。請聯絡客服人員處理。';
			}
		} else {
			if (isset($_SESSION['member']->therole) AND ($_SESSION['member']->therole == 'R')) {
				$logger = '管理员不可以登入游戏。确认回到首页。';
			} else {
				$logger = "你还没有登入，请重新登入系统后继续。确认回到登入页面。";
			}
			// 沒有登入，請使用者回去登入
			echo '<script>alert("' . $logger . '");window.close();</script>';
			echo '<script>document.location.href="home.php";</script>';
			die("(x)$logger");
		}
	} elseif ($action == 'Retrieve_Casino_balance' AND
		isset($_SESSION['member']) AND
		($_SESSION['member']->therole == 'A' OR $_SESSION['member']->therole == 'M' OR $_SESSION['member']->therole == 'R')) {
		// 取回 娛樂城 的餘額，並檢查上次離開時和目前的差額得出派彩金額
		// 搭配 wallets.php 使用 , 各娛樂城取回錢幣都要設定這個 session 避免重複執行。
		if (!isset($_SESSION['wallet_transfer'])) {
			// 旗標,轉餘額動作執行時，透過這個旗標控制同一個使用者，不能有第二個執行緒進入。除非已經清除這個旗標。
			$_SESSION['wallet_transfer'] = 'Account:' . $_SESSION['member']->account . ' run in ' . 'Retrieve_Casino_balance';
			// 使用session時，若先前的頁面尚未執行完畢，預設session會被鎖住
			// 此時，若執行另外一個也有使用session的頁面，則須等前一個頁面執行完畢，才能再執行

			// 取回娛樂城的餘額
			$member_id = $_SESSION['member']->id;
			$rr = retrieve_casino_balance($member_id, $debug);

			//取回 娛樂城 餘額成功
			if ($rr['code'] == 1) {
				//$logger = $rr['messages'];
				$logger = '取回现金' . $rr['balance'];
				echo '<p align="center">' . $logger . '</p>';
			} elseif ($rr['code'] == 500) {
				$logger = 'ERROR：取回现金失败，娱乐城钱包统整中，请稍侯再试！';
				echo '<p align="center">' . $logger . '</p>';
			} else {
				$logger = 'ERROR：' . $rr['code'] . '，取回现金失败，请联络客服人员！';
				echo '<p align="center">' . $logger . '</p>';
			}

		} else {
			$logger = '另外有錢包程式正在執行，請重新整理頁面後後再次執行。';
			echo "<p>$logger</p>";
			echo '<p align="center">' . $logger . '</p>';
		}
	} elseif ($action == 'Transferout_GTOKEN_to_Casino_balance' AND isset($_SESSION['member']) AND
		($_SESSION['member']->therole == 'A' OR $_SESSION['member']->therole == 'M' OR $_SESSION['member']->therole == 'R')) {

		// 將 GTOKEN 全部傳送到 Casino 上，並將 DB GTOKEN_LOCK 上鎖
		$topic = '將 GTOKEN 代幣全部傳送到 ' . $casinoid . ' Casino 上，並將 DB GTOKEN_LOCK 上鎖';
		echo "<br>" . $topic;
		$rr = transferout_gtoken_to_casino_balance($_SESSION['member']->id, $debug);
	} else {
		echo '你不被允許操作娛樂城。';
	}

	if ($debug == 1) {
		// 算累積花費時間, 另一個開始放在 config.php
		$program_spent_time = microtime(true) - $program_start_time;
		$program_spent_time_html = "<p>花費時間： $program_spent_time </p>";
		echo $program_spent_time_html;
	}

} else {
	// TODO 娛樂城 ICON 路徑
	$return_html = '
		<div style="position: absolute; top: 50%; left: 50%;">
			<center>
				<p><img src="' . $config['companylogo'] . '" alt="LOGO"></p>
				<p><h3><font color="#cccccc">' . $casino_name . '维护中，请稍侯再试 . . .</font></h3></p>
			</center>
		</div>';
	echo $return_html;
}


?>
