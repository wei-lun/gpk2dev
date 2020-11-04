<?php
/**
 *  RG 娛樂城轉頁相關功能
 *  接收 gamelobby.php 將網頁轉到 casino
 *
 * @author Letter
 * @date   2018/12/03
 * @since  2018/12/03 新建: Letter
 */


require_once dirname(dirname(__DIR__)) . "/config.php";
// 支援多國語系
require_once dirname(dirname(__DIR__)) . "/i18n/language.php";
// 自訂函式庫
require_once dirname(dirname(__DIR__)) . "/lib.php";
// 自訂casino lobby通用函式庫
require_once dirname(__DIR__) . "/casino_config.php";
// Restful API lib
require_once dirname(__FILE__) . "/lobby_rggame_lib.php";

$debug = 0;

// 檢查RG娛樂城的狀態，如果是 OPEN=1 才顯示選單及遊戲
$casino_name = '';
$gamelist_table = '';
$casino_state = 0;
$check_casino_state_sql = 'SELECT * from casino_list WHERE casinoid = \'RG\'';
$check_casino_state_result = runSQLall($check_casino_state_sql, 0, 'r');
if ($check_casino_state_result['0'] > '0') {
	$casino_name = $check_casino_state_result['1']->casino_name;
	$gamelist_table = $check_casino_state_result['1']->casino_dbtable;
	$casino_state = $check_casino_state_result['1']->open;
}

// 依娛樂城開關狀態顯示頁面 if/else block start
if ($casino_state == 1) {
	// 確認呼叫是否合法 if/else block start
	if (isset($_GET['a']) AND isset($_SESSION['member'])) {
		$action = filter_var($_GET['a'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);

		// 先一張圖，說明 RG play 還活著...!!
		// TODO 還沒有 RG 娛樂城的圖，暫時用 MEGA
		$page_html = '<div style="
			background-color:black;
			height: 100vh;
			display: flex;
			justify-content: center;
			align-items: center;
			overflow: hidden;
			">
			<img src="' . $config['companylogo'] . '" alt="LOGO">
			<img src="' . $cdnrooturl . 'loading_ball.gif" alt="RG">
			</div>
			';

		// 如果是 debug 就不顯示黑底+logo , 以利於 debug
		// 如果是 Retrieve_RG_Casino_balance (取回 RG 娛樂城餘額) 就顯示不同的畫面。
		if ($debug == 1 OR $action == 'Retrieve_RG_Casino_balance') {
			// debug , show debug information
		} else {
			echo $page_html;
		}
	} else {
		die('(402)不合法的呼叫');
	}
	// 確認呼叫是否合法 if/else block end

	// 依動作實作相關功能 if/elseif/else block start
	// goto_game action if block start
	if ($action == 'goto_game' AND isset($_SESSION['member'])) {
		/*
		// in 帳號A 預計登入 RG casion , 帳號為 rg_A , 轉錢的餘額為 $$$
		1. 使用者需要登入系統, 才可以繼續, 否則結束, 預設 錢包 wallets
		2. 判斷使用者在 RG 是否有帳號，如果沒有的話就建立帳號, 如果有的話檢查看是否有餘額。

		// Retrieve_Casino_RG_balance();
		何時取回娛樂城的餘額？
		1. 登入系統的時候 -- 關閉，因為有風險。
		2. 登出系統的時候 -- ok
		3. 轉換娛樂城的時候 -- 尚未有第二個娛樂城

		// Transferout_Casino_RG_balance();
		何時將餘額轉出到娛樂城？
		1. 當玩家點擊指定的遊戲城，檢查餘額是否在該遊戲城
		2. 如果在該遊戲城的話，不用轉餘額。直接登入該遊戲城。
		3. 如果不在該遊戲城，先檢查餘額是否在 gpk2 本地端
		4. 如果不在的話，將所有的餘額轉回來 gpk2 本地端
		5. 轉回再轉到指定的娛樂城。
		*/

		// 已經有登入,且需要 wallet 也在 session 內才可以繼續 , 只有會員 M 和代理商 A 才可以執行遊戲,管理員 R 不可以執行, 避免做帳的問題
		// 檢查登入身分是否可玩遊戲 if/else block start
		if (isset($_SESSION['member']) AND ($_SESSION['member']->therole == 'A' OR $_SESSION['member']->therole == 'M')) {

			// 檢查會員是否存在
			$member_id = $_SESSION['member']->id;
			$member_sql = "SELECT root_member.id,account,gtoken_balance,gtoken_lock,
	            casino_accounts->'RG'->>'account' as rg_account,
	            casino_accounts->'RG'->>'password' as rg_password,
	            casino_accounts->'RG'->>'balance' as rg_balance
	            FROM root_member JOIN root_member_wallets
	            ON root_member.id=root_member_wallets.id
	            WHERE root_member.id = '$member_id' AND root_member.status = '1';";
			$member = runSQLall($member_sql, 0, 'r');

			// 會員是否存在 if/else block start
			if ($member[0] == 1) { // 會員存在
				// 取得外部來源變數 gamecode
				$gamecode = filter_var($_GET['gamecode'], FILTER_SANITIZE_STRING);
				$casinoname = filter_var($_GET['casinoname'], FILTER_SANITIZE_STRING);
				$gameplatform = filter_var($_GET['gameplatform'], FILTER_SANITIZE_STRING);

				// 檢查是否已經有人按了 game 還在等待處理, 將 game code 存在 session 內
				// 判斷剛剛同一個人有沒有很緊張，一直按下很多個 gamecode 按鈕
				// 使用 session 來判斷是否已經點擊進入遊戲 if/else block start
				if (isset($_SESSION['running_gamecode'])) {
					$gamename_sql = 'SELECT gamename FROM casino_gameslist WHERE id=\'' . $_SESSION['running_gamecode'] . '\';';
					$gamename_result = runSQLall($gamename_sql, 0, 'r');
					$logger = '你已经在另一个视窗，执行' . $gamename_result[1]->gamename . '游戏。请关闭该游戏，并重新整理后点选需要前往的新的游戏...';
					echo "<script>alert('$logger');</script>";
					die($logger);
				} else {
					// 沒有其他 gamecode 正在執行, 所以設定 gamecode
					$_SESSION['running_gamecode'] = $gamecode;
				}
				// 使用 session 來判斷是否已經點擊進入遊戲 if/else block end

				// 檢查已經登入的使用者，session and DB 中是否有 RG 帳號，沒有就建立。
				// 檢查是否有娛樂城遊戲帳號 if/elseif/else block start
				if ($member[1]->rg_account == NULL AND $system_mode != 'developer') {
					// 建立 RG 的會員帳號
					$rc = create_casino_rg_account($debug);

					// 判斷API是否建立會員帳號 if/else block start
					if ($rc['ErrorCode'] == lobby_rggame_params::$CHECK_WATTLE_COIN_SUCCESS OR
						$rc['ErrorCode'] == lobby_rggame_params::$EXECUTE_SUCCESS) {
						// RG API 正常 , 繼續工作
						// 取得 rg account來源變數 -- 建立帳號時，會寫入 session
						$rg_account = $_SESSION['member']->rg_account;
					} else {
						// RG API 有問題, 停止工作
						$logger = '建立 RG 的會員帳號 RG API 有問題, 停止工作！ 錯誤碼 R: '. $rc['ErrorCode'];
						memberlog2db($member[1]->account, 'RG API', 'error', "$logger");
						echo '<script>alert("' . $logger . '");window.close();</script>';
						die($logger);
					}
					// 判斷API是否建立會員帳號 if/else block end
				// 檢查是否有娛樂城遊戲帳號 elseif block start
				} elseif ($system_mode == 'developer') {
					$logger = '開發環境不可建立帳號！！';
					echo '<script>alert("' . $logger . '");window.close();</script>';
					die($logger);
				// 檢查是否有娛樂城遊戲帳號 else block start
				} else {
					// 取得 rg_account來源變數 in DB
					$rg_memberId = $member[1]->id;
					$rg_account = $member[1]->rg_account;

					// 檢查玩家帳號是否存在
					$RG_API_data = array(
						'memberIds' => $rg_account
					);
					$RG_API_result = rg_api('GetMemberCurrentInfo', $RG_API_data, $debug);

					// 檢查是否有娛樂城遊戲帳號 if/elseif block start
					if ($RG_API_result['error'] == lobby_rggame_params::$API_ERROR_CODE_MEMBER_NOT_FOUND) {
						// 建立 RG 的會員帳號
						$rc = create_casino_rg_account($debug);
						if ($rc['ErrorCode'] == lobby_rggame_params::$CHECK_WATTLE_COIN_SUCCESS OR
							$rc['ErrorCode'] == lobby_rggame_params::$EXECUTE_SUCCESS) {
							// RG API 正常 , 繼續工作
							// 取得 rg account來源變數 -- 建立帳號時，會寫入 session
							$rg_account = $_SESSION['member']->rg_account;
						} else {
							// RG API 有問題, 停止工作
							$logger = '建立 RG 的會員帳號 RG API 有問題, 停止工作！ 錯誤碼 R: '. $rc['ErrorCode'];
							memberlog2db($member[1]->account, 'RG API', 'error', "$logger");
							echo '<script>alert("' . $logger . '");window.close();</script>';
							die($logger);
						}
					} elseif ($RG_API_result['error'] != lobby_rggame_params::$API_ERROR_CODE_NO_ERROR) {
						// RG API 有問題, 停止工作
						$logger = 'RG 的會員帳號有問題, 請洽客服人員！ 錯誤碼 A: '. $RG_API_result['error'];
						memberlog2db($member[1]->account, 'RG API', 'error', "$logger");
						echo '<script>alert("' . $logger . '");window.close();</script>';
						die($logger);
					}
					// 檢查是否有娛樂城遊戲帳號 if/elseif block end
				}
				// 檢查是否有娛樂城遊戲帳號 if/elseif/else block end

				// 檢查 gtoken_lock 是否為空 ，如果是空的話就 所有代幣 GTOKEN to RG 轉帳 , 並設定為 RG 使用狀態。
				// 如果是在其他娛樂城的話, 去其他娛樂城把餘額收回來。
				// 檢查 gtoken 及後續處理 if/elseif/else block start
				if ($member[1]->gtoken_lock == NULL AND $system_mode != 'developer') {
					// 只有代幣沒被使用的時候，才可以加值。如果已經使用，需要取回才可以加值。
					$topic = '將現金 GCASH 依據設定值，自動加值到 GTOKEN 上面。';
					echo "<br>" . $topic;
					$trans_result = Transferout_GCASH_GTOKEN_balance($_SESSION['member']->id);

					// 自動加值出錯時跳提示通知
					if (!($trans_result['code'] == 1 OR $trans_result['code'] == 403)) {
						$logger = '（' . $trans_result['code'] . '）' . $trans_result['messages'];
						echo "<script>alert('$logger');</script>";
					}

					// 將 GTOKEN 代幣全部傳送到 RG Casino 上，並將 DB GTOKEN_LOCK 上鎖
					$topic = '將 GTOKEN 代幣全部傳送到 RG Casino 上，並將 DB GTOKEN_LOCK 上鎖';
					echo "<br>" . $topic;
					$rr = transferout_gtoken_rg_casino_balance($member[1]->id);
					// 轉到RG出錯時跳提示通知
					if ($rr['code'] != 1) {
						$logger = '（' . $rr['code'] . '）RG 轉帳錯誤，請洽客服人員!';
						echo "<script>alert('$logger');</script>";
						die($logger);
					}

				// 檢查 gtoken 及後續處理 elseif block start
				} elseif ($system_mode != 'developer') {
					$casino_lock = $member[1]->gtoken_lock;
					$undergoing_casino_sql = 'SELECT * FROM casino_list WHERE casinoid = \'' . $casinoname . '\';';
					$undergoing_casino = runSQLall($undergoing_casino_sql, 0, 'r');

					// 判斷 gtoken 是否在 RG 娛樂城 if/else block start
					if (isset($casino_lock) AND $casino_lock == lobby_rggame_params::$RG_CASINO_ID) {
						// ------------------------------------------------------------------------------
						// 查詢 RG 餘額是否大於 1 ,如果大於 1 的時候就不用轉帳, 先把 RG 內的所有錢花光。
						// 當餘額小於 1 時，先取回餘額再重新轉入。
						// ------------------------------------------------------------------------------
						// 判斷餘額 if/elseif block start
						if (!isset($_SESSION['wallet_transfer']) AND $member[1]->gtoken_balance >= 1) {
							$topic = '將現金 GCASH 依據設定值，自動加值到 GTOKEN 上面。';
							if ($debug == 1) {
								echo "<br>" . $topic;
							}
							$trans_result = Transferout_GCASH_GTOKEN_balance($_SESSION['member']->id);
							// 自動加值出錯時跳提示通知 if block start
							if (!($trans_result['code'] == 1 OR $trans_result['code'] == 403)) {
								$logger = '（' . $trans_result['code'] . '）' . $trans_result['messages'];
								echo "<script>alert('$logger');</script>";
							}

							// 將 GTOKEN 代幣全部傳送到 RG Casino 上，並將 DB GTOKEN_LOCK 上鎖
							$topic = '將 GTOKEN 代幣全部傳送到 RG Casino 上，並將 DB GTOKEN_LOCK 上鎖';
							if ($debug == 1) {
								echo "<br>" . $topic;
							}
							$rr = transferout_gtoken_rg_casino_balance($member[1]->id);
							// 轉到RG出錯時跳提示通知 if/else block start
							if ($rr['code'] != 1) {
								$logger = '（' . $rr['code'] . '）RG 轉帳錯誤，請洽客服人員!';
								echo "<script>alert('$logger');</script>";
								die($logger);
							} else {
								die($logger);
							}
							// 轉到RG出錯時跳提示通知 if/else block end
						// 判斷餘額 elseif block start
						} elseif (isset($_SESSION['wallet_transfer'])) {
							$logger = '另外有錢包程式正在執行，請重新整理頁面後後再次執行。';
							if ($debug == 1) {
								echo "<p>$logger</p>";
								echo '<p align="center">' . $logger . '</p>';
							}
						}
						// 判斷餘額 if/elseif block end
					// 判斷 gtoken 是否在 RG 娛樂城 else block start
					} else {
						// 將前往不同casino，需先將錢取回再轉到該casino的action頁面
						require_once dirname(__DIR__) . $casino_lib[$casino_lock];

						// 判斷是否在轉帳 if/else block start
						if (!isset($_SESSION['wallet_transfer'])) {
							// 旗標,轉餘額動作執行時，透過這個旗標控制同一個使用者，不能有第二個執行緒進入。除非已經清除這個旗標。
							$_SESSION['wallet_transfer'] = 'Account:' . $_SESSION['member']->account . ' run in ' . $casino_retrieve[$casino_lock];
							// 取回娛樂城的餘額
							$topic = '將 GTOKEN 代幣從其他 Casino 取回，並將 DB GTOKEN_LOCK 解鎖';
							if ($debug == 1) {
								echo "<br>" . $topic;
							}

							$member_id = $_SESSION['member']->id;
							$rr = $casino_retrieve[$casino_lock]($member_id, 0);

							// 取回RG餘額成功 if/else block start
							if ($rr['code'] == 1) {
								// 只有代幣沒被使用的時候，才可以加值。如果已經使用，需要取回才可以加值。
								$topic = '將現金 GCASH 依據設定值，自動加值到 GTOKEN 上面。';
								if ($debug == 1) {
									echo "<br>" . $topic;
								}
								$trans_result = Transferout_GCASH_GTOKEN_balance($_SESSION['member']->id);

								// 將 GTOKEN 代幣全部傳送到 RG Casino 上，並將 DB GTOKEN_LOCK 上鎖
								$topic = '將 GTOKEN 代幣全部傳送到 RG Casino 上，並將 DB GTOKEN_LOCK 上鎖';
								if ($debug == 1) {
									echo "<br>" . $topic;
								}
								$rr = transferout_gtoken_rg_casino_balance($member[1]->id);
							} else {
								$logger = $rr['messages'];
								echo '<p align="center">' . $logger . '</p>';
								echo "<script>window.location.reload();</script>";
								die($logger);
							}
							// 取回RG餘額成功 if/else block end
						} else {
							$logger = '另外有錢包程式正在執行，請重新整理頁面後後再次執行。';
							$return_json_arr = array('logger' => $logger);
						}
						// 判斷是否在轉帳 if/else block end
					}
					// 判斷 gtoken 是否在 RG 娛樂城 if/else block end
				// 檢查 gtoken 及後續處理 elseif block end
				} else {
					$r['code'] = 401;
					$r['messages'] = '會員不存在，或者已經失效。請聯絡客服人員處理。';
				}
				// 檢查 gtoken 及後續處理 if/elseif/else block end

				// 已經有登入 RG , 繼續
				// 1.查詢 rg 餘額是否大於 1 ,如果大於 1 的時候就不用收回其他 casino 的 coin, 先把 RG 內的所有錢花光。
				//   當餘額小於 1 時，下次登入又會自己轉錢了。
				// 動作： GetMemberCurrentInfo 取得目前 餘額
				$RG_API_data = array(
					'memberIds' => $rg_account
				);
				$RG_API_result = rg_api('GetMemberCurrentInfo', $RG_API_data, $debug);

				// 把查詢 API 回傳的資料顯示出來 for debug
				if ($debug == 1) {
					var_dump($RG_API_result);
				}

				if ($system_mode == 'developer') {
					$RG_amount = 1;
				} else {
					$RG_amount = $RG_API_result['Result']->data[0]->coin;
				}

				// 判斷 RG 是否還有餘額，且餘額 >= 1 if/elseif/else block start
				if ($RG_API_result['error'] == lobby_rggame_params::$API_ERROR_CODE_NO_ERROR AND $RG_amount >= 1) {
					// -------------------------------------------------
					// 直接轉到 RG 指定的 gamecode , 因為餘額大於等於 1 就可以直接玩了!!
					// 等沒錢再轉錢來處理。
					// -------------------------------------------------
					// 取得進進入 rg game 的網址
					$gamecode_url = rg_gameurl($rg_account, $gamecode, $debug);

					// 如果網址不為空的話，就是有資料。則處理網址轉址。
					if ($gamecode_url != '') {
						$logger = '登入 RG Game:' . $gamecode;
						memberlog2db($member[1]->account, 'loginRGLottery', 'info', "$logger");
						unset($_SESSION['running_gamecode']);

						// 除錯訊息 if/else block start
						if ($debug == 1) {
							// 顯示在頁面上, for debug
							echo $gamecode_url;
							echo "<p><a href='$gamecode_url'>$gamecode</a></p>";
						} else {
							// 直接跳轉, 不用顯示
							echo '<script>setTimeout(function(){document.location.href = "' . $gamecode_url . '";},500);</script>';
							$logger = "<p>請不要關閉瀏覽器 Javascript 功能，點擊連結繼續進入遊戲。<a href='$gamecode_url'>$gamecode</a></p>";
							die($logger);
						}
						// 除錯訊息 if/else block end
					} else {
						$logger = $gamecode . ',沒有網址，應該有哪裡出錯了。';
						memberlog2db($member[1]->account, 'loginRGLottery', 'error', "$logger");
						unset($_SESSION['running_gamecode']);
						die($logger);
					}
					// -------------------------------------------------

				// 判斷 RG 是否還有餘額，且餘額 >= 1 elseif block start
				} elseif ($RG_API_result['error'] == 0 AND $RG_amount < 1) {
					// --------------------------------------------------
					// 判斷 RG 餘額 < 1 (沒有錢)， 登入 RG Lottery game
					// --------------------------------------------------
					// 判斷 RG 餘額  < 1 , 0.xx 都算是沒有錢!!
					// 判斷 GTOKEN 有沒有錢，有錢就全部轉過去 RG
					// 判斷 GTOKEN < 1 ，判斷 GCASH 有沒有錢，如果 AUTO 轉帳設定為開啟，就轉帳到 GOTKEN 並直接轉帳到 RG 帳戶。
					// ======================================================================
					// 取得進進入 rg lottery 的網址
					$gamecode_url = rg_gameurl($rg_account, $gamecode, $debug);

					// 如果網址不為空的話，就是有資料。則處理網址轉址。
					if ($gamecode_url != '') {
						$logger = '登入 RG Lottery:' . $gamecode;
						memberlog2db($member[1]->account, 'loginRGLottery', 'info', "$logger");
						unset($_SESSION['running_gamecode']);

						// 除錯訊息 if/else block start
						if ($debug == 1) {
							// 顯示在頁面上, for debug
							echo $gamecode_url;
							echo "<p><a href='$gamecode_url'>$gamecode</a></p>";

							// 算累積花費時間, 另一個開始放在 config.php
							$program_spent_time = microtime(true) - $program_start_time;
							$program_spent_time_html = "<p>判斷 RG 餘額 < 1 (沒有錢)， 登入 RG game。花費時間： $program_spent_time </p>";
							var_dump($program_spent_time_html);
						} else {
							// 直接跳轉, 不用顯示
							echo '<script>setTimeout(function(){document.location.href = "' . $gamecode_url . '";},500);</script>';
							$logger = '請不要關閉瀏覽器 Javascript 功能。';
							die($logger);
						}
						// 除錯訊息 if/else block start
					} else {
						$logger = $gamecode . ',沒有轉址，應該有哪裡出錯了。';
						memberlog2db($member[1]->account, 'loginRGLottery', 'error', "$logger");
						unset($_SESSION['running_gamecode']);
					}
					// 判斷 RG 是否還有餘額，且餘額 >= 1 if/elseif/else block end
				} else {
					// 有錯誤的 GetAccountDetails
					$logger = 'RG API 取得錯誤，請稍候再試。GetMemberCurrentInfo ERROR !!!';
					memberlog2db($member[1]->account, 'RG API', 'error', "$logger");
					die($logger);
				}
				// 判斷 RG 是否還有餘額，且餘額 >= 1 if/elseif/else block end
			// 會員是否存在 if block end
			} else {
				$r['code'] = 401;
				$r['messages'] = '會員不存在，或者已經失效。請聯絡客服人員處理。';
			}
			// 會員是否存在 if/else block end
		// 檢查登入身分是否可玩遊戲 if block end
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
		// 檢查登入身分是否可玩遊戲 if/else block end

	// goto_game action if block end
	// Retrieve_RG_Casino_balance elseif block start
	} elseif ($action == 'Retrieve_RG_Casino_balance' AND isset($_SESSION['member'])
		AND ($_SESSION['member']->therole == 'A'
			OR $_SESSION['member']->therole == 'M'
			OR $_SESSION['member']->therole == 'R')) {
		// ----------------------------------------------------------------------------
		// 取回 RG Casino 的餘額，並檢查上次離開時和目前的差額得出派彩金額。
		// 搭配 wallets.php 使用 , 各娛樂城取回錢幣都要設定這個 session 避免重複執行。
		// 判斷是否為轉帳狀態 if/else block start
		if (!isset($_SESSION['wallet_transfer'])) {
			// 旗標,轉餘額動作執行時，透過這個旗標控制同一個使用者，不能有第二個執行緒進入。除非已經清除這個旗標。
			$_SESSION['wallet_transfer'] = 'Account:' . $_SESSION['member']->account . ' run in ' . 'Retrieve_RG_Casino_balance';
			// 取回娛樂城的餘額
			$member_id = $_SESSION['member']->id;
			$rr = retrieve_rg_casino_balance($member_id, $debug);

			// 取回RG餘額成功 if/elseif/else block start
			if ($rr['code'] == 1) {
				//$logger = $rr['messages'];
				$logger = '取回现金' . $rr['balance'];
				echo '<p align="center">' . $logger . '</p>';
			} elseif ($rr['code'] == 500) {
				//$logger = $rr['messages'];
				$logger = 'ERROR：取回现金失败，娱乐城钱包统整中，请稍侯再试！';
				echo '<p align="center">' . $logger . '</p>';
			} else {
				//$logger = $rr['messages'];
				$logger = 'ERROR：' . $rr['code'] . '，取回现金失败，请联络客服人员！';
				echo '<p align="center">' . $logger . '</p>';
			}
			// 取回RG餘額成功 if/elseif/else block end
		} else {
			$logger = '另外有錢包程式正在執行，請重新整理頁面後後再次執行。';
			echo "<p>$logger</p>";
			echo '<p align="center">' . $logger . '</p>';
		}
		// 判斷是否為轉帳狀態 if/else block end
	// Transferout_GTOKEN_RG_Casino_balance elseif block start
	} elseif ($action == 'Transferout_GTOKEN_RG_Casino_balance' AND isset($_SESSION['member']) AND
		($_SESSION['member']->therole == 'A' OR $_SESSION['member']->therole == 'M' OR $_SESSION['member']->therole == 'R')) {
		// 將 GTOKEN 全部傳送到 RG Casino 上，並將 DB GTOKEN_LOCK 上鎖
		// for test: lobby_mggame_action.php?a=Transferout_GTOKEN_RG_Casino_balance
		$topic = '將 GTOKEN 代幣全部傳送到 RG Casino 上，並將 DB GTOKEN_LOCK 上鎖';
		echo "<br>" . $topic;
		$rr = transferout_gtoken_rg_casino_balance($_SESSION['member']->id);
	// load elseif block start
	} elseif ($action == 'load') {
		// test developer
		// var_dump($_POST);
	} else {
		echo '你不被允許操作娛樂城。';
	}
	// 依動作實作相關功能 if/elseif/else block end

	if ($debug == 1) {
		// 算累積花費時間, 另一個開始放在 config.php
		$program_spent_time = microtime(true) - $program_start_time;
		$program_spent_time_html = "<p>花費時間： $program_spent_time </p>";
		echo $program_spent_time_html;
	}
} else {
	$return_html = '
		<div style="position: absolute; top: 50%; left: 50%;">
		<center>
		<p><img width="180" src="' . $cdnrooturl . 'casinologo/MEGA.png" alt="Playtech" /></p>
		<p><h3><font color="#cccccc">' . $casino_name . '维护中，请稍侯再试 . . .</font></h3></p>
		</center></div>';
	echo $return_html;
}
// 依娛樂城開關狀態顯示頁面 if/else block end
