<?php
/**
 * Casino 的專用函式庫(RG)
 * 此函式提供給 lobby_rggame_action.php 使用,配合GPK變更主要API為Restful，修改自
 * lobby_megagame_lib.php, 負責娛樂城的轉換操作
 *
 * @author Letter
 * @date 2018/12/03
 * @since  version_no 2018/12/03 Letter: 新建
 */
/*
function 索引及說明：

1. 輸入資料格式：
$RG_API_data = array(
	'key' => 'value',
	...
);

2. RG API 文件函式及用法 sample , 操作 RG API (by Hoimi/Tiger API 0.0.2)
rg_api($method, $RG_API_data, $debug=0)

3. 依據使用者帳號資訊，檢查遠端 RG 的帳號是否存在，不存在就建立
create_casino_rg_account()

4.將傳入的 member id 值，取得他的 DB GTOKEN 餘額並全部傳送到 RG CASINO 上
把本地端的資料庫 root_member_wallets 的 GTOKEN_LOCK設定為 RG 餘額儲存在 rg_balance 上面
transferout_gtoken_rg_casino_balance($member_id, $debug = 0)

5. 取回 RG Casino 的餘額，並檢查上次離開時和目前的差額得出派彩金額，紀錄存簿。
db_rg2gpk_balance($gtoken_cashier_account, $rg_balance_api, $rg2rg_balance, $rg_balance_db, $debug=0 )

6. 取回 RG Casino 的餘額 -- retrieve_rg_casino_balance
retrieve_rg_casino_balance($member_id, $debug=0)

7. 產生可以連到 RG game 的 url 位址
rg_gameurl($rg_account, $rg_password, $rg_gamecode)

*/

require_once dirname(dirname(__DIR__)) . '/lib_common.php';
require_once dirname(dirname(__DIR__)) . '/lib_member_tree.php';
require_once dirname(__DIR__) . '/casino_config.php';
require_once dirname(__DIR__) . '/RG/lobby_rggame_params.php';


/**
 * RG 無限代彩票 實作呼叫 API 方法
 * 實作 CreateMember、GetMemberCurrentInfo、Transfer、Login、KickMember
 *
 * @param string $method      使用API的方法
 * @param array  $RG_API_data API所需參數
 * @param int    $debug       是否為開發者模式，1為開發者模式
 *
 * @return array 取得API返回資料
 */
function rg_api(string $method, array $RG_API_data, $debug = 0)
{
	// 設定 socket_timeout , http://php.net/manual/en/soapclient.soapclient.php
	ini_set('default_socket_timeout', 5);

	global $RGAPI_CONFIG;
	$url = $RGAPI_CONFIG['api_url'] . $RGAPI_CONFIG['sub_url'][$method];
	$apiUrl = '';

	// 依照叫用API方法組成完整 API URL
	// url patten: https://api.base.rul/method.url?Key=key_patten&param1=param1&...
	switch ($method) {
		case 'CreateMember':
			$key = genApiKey($RGAPI_CONFIG['apikey'], $RG_API_data);
			$apiUrl = $url . 'Key=' . $key . '&masterId=' . $RG_API_data['masterId'] . '&memberId=' .
				$RG_API_data['memberId'] . '&nickname=' . $RG_API_data['nickname'] . '&memberBranch=' .
				json_encode($RG_API_data['memberBranch']);
			break;
		case 'GetMemberCurrentInfo':
			$key = genApiKey($RGAPI_CONFIG['apikey'], $RG_API_data);
			$apiUrl = $url . 'Key=' . $key . '&memberIds=' . $RG_API_data['memberIds'];
			break;
		case 'Transfer':
			$key = genApiKey($RGAPI_CONFIG['apikey'], $RG_API_data);
			$apiUrl = $url . 'Key=' . $key . '&memberId=' . $RG_API_data['memberId'] . '&transactionId=' .
				$RG_API_data['transactionId'] . '&amount=' . $RG_API_data['amount'] . '&transferType=' .
				$RG_API_data['transferType'];
			break;
		case 'Login':
			$key = genApiKey($RGAPI_CONFIG['apikey'], $RG_API_data);
			$apiUrl = $url . 'Key=' . $key . '&masterId=' . $RG_API_data['masterId'] . '&memberId=' .
				$RG_API_data['memberId'] . '&gameId=' . $RG_API_data['gameId'] . '&memberBranch=' .
				json_encode($RG_API_data['memberBranch']);
			break;
		case 'KickMember':
			$key = genApiKey($RGAPI_CONFIG['apikey'], $RG_API_data);
			$apiUrl = $url . 'Key=' . $key . '&memberIds=' . $RG_API_data['memberIds'];
			break;
		case 'GetStatement':
			$key = genApiKey($RGAPI_CONFIG['apikey'], $RG_API_data);
			$apiUrl = $url . 'Key=' . $key . '&versionKey=' . $RG_API_data['versionKey'];
			break;
		default:
			break;
	}

	if (isset($RG_API_data)) {
		$ret = array();
		try {
			$ch = curl_init($apiUrl);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,  false);
			curl_setopt($ch, CURLOPT_CAINFO,  $_SERVER['DOCUMENT_ROOT'] .'/cacert.pem');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			if ($_SESSION['site_mode'] == 'mobile') {
				curl_setopt($ch, CURLOPT_USERAGENT, isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'UserAgent');
			}

			$response = curl_exec($ch);

			if ($debug == 1) {
				echo $method . "\n";
				echo curl_error($ch);
				var_dump($apiUrl);
				var_dump($RG_API_data);
				var_dump($response);
			}

			if ($response) {
				$body = json_decode($response);

				if ($debug == 1) {
					var_dump($body);
				}

				// curl 正確
				$ret['curl_status'] = 0;
				$ret['error'] = $body->response->error;
				$ret['Result'] = $body;
			} else {
				// curl 錯誤
				$ret['curl_status'] = 1;
				$ret['error'] = curl_errno($ch);
				// 錯誤訊息
				$ret['Result'] = '系统维护中，请稍候再试';
			}
			// 關閉 curl
			curl_close($ch);
		} catch (Exception $e) {
			// curl 錯誤
			$ret['curl_status'] = 1;
			$ret['error'] = 500;
			// 錯誤訊息
			$ret['Result'] = $e->getMessage();
		}
	} else {
		$ret = 'NAN';
	}

	return ($ret);
}


/**
 * 建立 RG 無限代彩票 娛樂城帳號
 * 依據使用者帳號資訊，檢查遠端 RG 的帳號是否存在，不存在就建立
 *
 * @param int $debug 是否為除錯模式，1為除錯模式
 *
 * @return array
 */
function create_casino_rg_account(int $debug = 0)
{
	/** @var mixed $config 設定檔 */
	global $config;
	/** @var array $r 回傳的變數 */
	$r = array();

	// 需要有 session 才可以登入, 且帳號只有 A and R 權限才可以建立 RG 帳號, 不允許管理員建立帳號進入遊戲
	// 判斷會員是否可進入遊戲 if/else block start
	if (isset($_SESSION['member']) and ($_SESSION['member']->therole == 'A' or $_SESSION['member']->therole == 'M')
		and ($config['businessDemo'] == 0 or in_array('RG', $config['businessDemo_skipCasino']))) {

		// 當 $_SESSION['wallet']->transfer_flag 存在時，不可以執行，因為有其他程序再記憶體中執行
		// 判斷是否有其他程式在執行 if/else block start
		if (!isset($_SESSION['wallet_transfer'])) {
			// 透過這個旗標控制不能有第二個執行緒進入
			$_SESSION['wallet_transfer'] = 'create_casino_rg_account ' . $_SESSION['member']->account;

			// 判斷 SESSION 裡 member 是否存在 if/else block start
			if (isset($_SESSION['member'])) {
				// 判斷是否有RG帳號 if/else block start
				if (!isset($_SESSION['member']->rg_account)) {
					// 1. 取得代理鏈及反水鏈
					$memberList = MemberTreeNode::getMemberList($_SESSION['member']->id, true);
					MemberTreeNode::buildMemberTree($memberList[1], $memberList); // 建代理鏈
					$fsList = MemberTreeNode::getPredecessorPreferentialRate($memberList[$_SESSION['member']->id],
						$memberList); // 反水鏈

					// 2. 組出 nickname
					$firstname = $_SESSION['member']->account;
					$lastname = $config['projectid'];
					$nickName = $lastname . '_' . $firstname;

					// 3.1 判斷鏈上會員是否有 game account，沒有建立，有的放入鍊
					$branchArr = genMemberBranch($memberList, $lastname, $fsList);

					// 3.2 組成呼叫API所需資料
					$RG_API_data = array(
						'masterId' => $branchArr['master'],
						'memberId' => $branchArr['member'],
						'nickname' => $nickName,
						'memberBranch' => $branchArr['branch']
					);

					$RG_API_result = rg_api('CreateMember', $RG_API_data, $debug);

					// 處理 RG 帳號建立後動作 if/else block start
					if ($RG_API_result['error'] == lobby_rggame_params::$API_ERROR_CODE_NO_ERROR) {

						$logger = 'API RG 帐号建立成功';
						$r['ErrorCode'] = lobby_rggame_params::$EXECUTE_SUCCESS;
						$r['ErrorMessage'] = $logger;

						// 取得建立好的帳號資訊
						$rg_accountnumber_api = $branchArr['member'];

						// 紀錄建立 API 的帳號相關資訊
						$logger = 'API RG 帐号建立成功 AccountNumber=' . $rg_accountnumber_api . ', ' . json_encode($RG_API_result);
						memberlog2db($_SESSION['member']->account, 'RG API', 'notice', "$logger");
						member_casino_transferrecords('lobby', 'RG', '0', $logger, 'success');

						// 更新鏈上會員錢包的 RG 帳號密碼
						foreach ($branchArr['idAccountList'] as $id => $dataArr) {
							$db_account = getGameAccountByMemberId($dataArr['id'], lobby_rggame_params::$RG_CASINO_ID);
							// 判斷DB裡是否已有娛樂城遊戲帳號
							if (is_null($db_account)) {
								$result = updateWattleAccountAndPassword(lobby_rggame_params::$RG_CASINO_ID,
									$dataArr['account'], '', $dataArr['id']);
							} else {
								$result = lobby_rggame_params::$DB_EXIST_RECORD;
							}

							// 錢包建立判斷 if/else block start
							if ($result == lobby_rggame_params::$DB_EXECUTE_SUCCESS) {
								// 成功且ID為會員ID的話，更新 session 的 RG account and password 資訊。
								if ($_SESSION['member']->id == $dataArr['id']) {
									$_SESSION['member']->rg_account = $rg_accountnumber_api;
								}

								// 更新 wallet 成功
								$logger = 'RG 帐号 ' . $rg_accountnumber_api . ' 写入 DB root_member_wallet 成功';
								$r['ErrorCode'] = lobby_rggame_params::$EXECUTE_SUCCESS;
								$r['ErrorMessage'] = $logger;
							} elseif ($result = lobby_rggame_params::$DB_EXIST_RECORD) {
								$logger = 'RG 帐号 ' . $rg_accountnumber_api . ' 已存在钱包';
								$r['ErrorCode'] = lobby_rggame_params::$EXECUTE_SUCCESS;
								$r['ErrorMessage'] = $logger;
							} else {
								// 更新 wallet 失敗
								$logger = 'RG 帐号 ' . $rg_accountnumber_api . ' 写入 DB root_member_wallet 失败';
								$r['ErrorCode'] = lobby_rggame_params::$EXECUTE_FAILED;
								$r['ErrorMessage'] = $logger;
							}
							// 錢包建立判斷 if/else block end
						}

						// 處理 RG 帳號建立後動作 if block end
					} else {
						$logger = 'API RG 帐号建立失败' . $RG_API_result['error'] . ':' . $RG_API_result['Result'];
						$r['ErrorCode'] = lobby_rggame_params::$API_FUNCTION_ERROR;
						$r['ErrorMessage'] = $logger;
						member_casino_transferrecords('lobby', 'RG', '0', $logger, 'fail');
					}
					// 處理 RG 帳號建立後動作 if/else block end

					// 判斷是否有RG帳號 if block end
				} else {
					// 會員已有 RG 帳號
					// 查詢目前使用者的 RG 錢包餘額
					$delimitedAccountNumbers = $_SESSION['member']->rg_account;
					$RG_API_data = array(
						'memberIds' => $delimitedAccountNumbers
					);
					$RG_API_result = rg_api('GetMemberCurrentInfo', $RG_API_data, $debug);

					// 確認會員是否存在 if/else block start
					if ($RG_API_result['error'] == lobby_rggame_params::$API_ERROR_CODE_NO_ERROR) {
						// 查詢餘額動作
						$logger = '会员帐号 ' . $delimitedAccountNumbers . '，余额 ' . $RG_API_result['Result']->data[0]->coin;
						$r['ErrorCode'] = lobby_rggame_params::$CHECK_WATTLE_COIN_SUCCESS;
						$r['ErrorMessage'] = $logger;
					} else {
						$logger = '会员帐号 ' . $delimitedAccountNumbers . ' 不存在';
						$r['ErrorCode'] = lobby_rggame_params::$MEMBER_NOT_EXIST;
						$r['ErrorMessage'] = $logger;
					}
					// 確認會員是否存在 if/else block end

					member_casino_transferrecords('lobby', 'RG', '0', $logger, 'info');

				}
				// 判斷是否有RG帳號 if/else block end

				// 判斷 SESSION 裡 member 是否存在 if block end
			} else {
				$logger = '会员需要登入才可以建立 RG 帐号';
				$r['ErrorCode'] = lobby_rggame_params::$SESSION_MEMBER_MISSING;
				$r['ErrorMessage'] = $logger;
			}
			// 判斷 SESSION 裡 member 是否存在 if/else block end

			// 執行完成後，把 $_SESSION['wallet_transfer'] 變數清除, 其他程式才可以進入。
			unset($_SESSION['wallet_transfer']);

			// 判斷是否有其他程式在執行 if block end
		} else {
			$logger = '同使用者，API 动作产生，不可以执行。' . $_SESSION['wallet_transfer'];
			$r['ErrorCode'] = lobby_rggame_params::$MULTIPLE_FUNCTION_EXECUTE;
			$r['ErrorMessage'] = $logger;
		}
		// 判斷是否有其他程式在執行 if/else block end

		// 判斷會員是否可進入遊戲 if block end
	} else {
		// 條件不符合, 不建立帳號
		$logger = '需要有 session 才可以操作, 且帐号只有 A and M 权限才可以建立 RG 帐号, 不允许管理员建立帐号进入游戏';
		$r['ErrorCode'] = lobby_rggame_params::$ILLEGAL_CONDITIONS;
		$r['ErrorMessage'] = $logger;
	}
	// 判斷會員是否可進入遊戲 if/else block end

	return ($r);
}

/**
 * 生成會員樹
 *
 * @param array $memberList
 * @param       $lastname
 * @param       $fsList
 *
 * @return array
 */
function genMemberBranch(array $memberList, $lastname, $fsList): array
{
	$idAccountList = [];
	foreach ($memberList as $id => $member) {
		$gameAccount = getGameAccountByMemberId($id, lobby_rggame_params::$RG_CASINO_ID);
		// 檢查代理鏈上是否有娛樂城遊戲帳號 if/else block start
		if (is_null($gameAccount)) {
			$ac = genGameAccountByMemberId($id, 20000000000, $lastname);
			array_push($idAccountList, [
				'id' => $id,
				'account' => $ac
			]);
		} else {
			array_push($idAccountList, [
				'id' => $id,
				'account' => $gameAccount
			]);
		}
		// 檢查代理鏈上是否有娛樂城遊戲帳號 if/else block end
	}

	// 放入反水 fs
	$branch = [];
	$count = count($idAccountList);
	$i = 0;
	$masterGameAccount = '';
	$memberGameAccount = '';
	$acfs = 0.0;
	foreach ($idAccountList as $key => $idToAccount) {
		if ($i == 0) $memberGameAccount = $idToAccount['account'];
		if ($i == $count - 1) $masterGameAccount = $idToAccount['account'];
		$acfs = $acfs + array_pop($fsList);
		array_unshift($branch, [
			'memberId' => $idToAccount['account'],
			'parentId' => $i == $count - 1 ? '' : $idAccountList[$i + 1]['account'],
			'fs' => $i == $count - 1 ? lobby_rggame_params::$RG_CASINO_ROOT_FS_RATIO :
				$acfs * lobby_rggame_params::$RG_CASINO_FS_RATIO_EXCHANGE
		]);
		$i++;
	}

	return array(
		'idAccountList' => $idAccountList,
		'branch' => $branch,
		'master' => $masterGameAccount,
		'member' => $memberGameAccount
	);
}


/**
 * RG 無限代彩票 API 轉帳
 *
 * @param int $member_id 會員 ID
 * @param int $debug     是否為除錯模式，1為除錯模式
 *
 * @return mixed
 */
function transferout_gtoken_rg_casino_balance(int $member_id, $debug = 0)
{
	global $config;
	$casinoId = lobby_rggame_params::$RG_CASINO_ID;
	$r = getMemberInRGCasinoById($member_id);
	if ($debug == 1) {
		var_dump($r);
	}

	// 判斷有無RG帳號及平台環境 if/elseif/else block start
	if ($r[0] == 1 and $config['casino_transfer_mode'] != lobby_rggame_params::$CASINO_TRANSFER_MODE_TRAIL) {
		// 判斷轉帳模式 if/elseif/else block start
		// 確認有沒有 RG 娛樂城遊戲帳號
		if ($r[1]->rg_account == null or $r[1]->rg_account == '') {
			$check_return['messages'] = '你还没有 RG 帐号。';
			$check_return['code'] = 12;
			// 判斷轉帳模式 elseif block start
			// 確認遊戲代幣 gtoken 最少有 1
		} elseif ($r[1]->gtoken_balance >= '1') {
			// 需要 gtoken_lock 沒有被設定的時候，才可以使用這功能。
			if ($r[1]->gtoken_lock == null or $r[1]->gtoken_lock == lobby_rggame_params::$RG_CASINO_ID) {
				// 動作： 將本地端所有的 gtoken 餘額 Deposit 到 rg 對應的帳戶
				$accountNumber = $r[1]->rg_account;
				$amount = round($r[1]->gtoken_balance, lobby_rggame_params::$BALANCE_PRECISION);
				$rg_balance = round($r[1]->rg_balance, lobby_rggame_params::$BALANCE_PRECISION);

				if ($config['casino_transfer_mode'] == lobby_rggame_params::$CASINO_TRANSFER_MODE_LIMITED) {
					$amount = lobby_rggame_params::$CASINO_TRANSFER_LIMITED_AMOUNT;
				}

				$transactionId = $casinoId . '0withdraw0' . date("Ymdhis");
				$RG_API_data = array(
					'memberId' => $accountNumber,
					'transactionId' => $transactionId,
					'amount' => $amount,
					'transferType' => lobby_rggame_params::$TRANSFER_TYPE['Withdraw']
				);

				$RG_API_result = rg_api('Transfer', $RG_API_data, $debug);
				// var_dump($RG_API_result);
				if ($RG_API_result['error'] == lobby_rggame_params::$API_ERROR_CODE_NO_ERROR) {
					if ($debug == 1) {
						var_dump($RG_API_data);
						var_dump($RG_API_result);
					}
					// 娛樂城最終餘額
					$rg_balance = $rg_balance + $amount;

					// 本地端 db 的餘額處理
					$togtoken_sql = "UPDATE root_member_wallets SET gtoken_lock = '$casinoId'  WHERE id = '$member_id';";
					$togtoken_sql = $togtoken_sql . "UPDATE root_member_wallets SET gtoken_balance = gtoken_balance - '$amount', casino_accounts=jsonb_set(casino_accounts,'{\"$casinoId\",\"balance\"}','{$rg_balance}')  WHERE id = '$member_id';";
					if ($debug == 1) {
						var_dump($togtoken_sql);
					}
					$togtoken_sql_result = runSQLtransactions($togtoken_sql);
					if ($debug == 1) {
						var_dump($togtoken_sql_result);
					}
					if ($togtoken_sql_result) {
						$check_return['messages'] = '所有GTOKEN余额已经转到RG娱乐城。 RG转帐单号 ' . $transactionId . ' RG帐号' . $accountNumber . 'RG新增' . $amount;
						$check_return['code'] = 1;
						memberlog2db($_SESSION['member']->account, 'rg', 'info', $check_return['messages']);
						member_casino_transferrecords('lobby', 'RG', $amount, $check_return['messages'], 'success',
							$transactionId, 1);
					} else {
						$check_return['messages'] = '余额处理，本地端资料库交易错误。';
						$check_return['code'] = 14;
						memberlog2db($_SESSION['member']->account, 'rg', 'error', $check_return['messages']);
						member_casino_transferrecords('lobby', 'RG', $amount, $check_return['messages'], 'warning',
							$transactionId, 2);
					}
				} else {
					$check_return['messages'] = '余额转移到 RG 时失败！！';
					$check_return['code'] = 13;
					memberlog2db($_SESSION['member']->account, 'rg', 'error', $check_return['messages']);
					member_casino_transferrecords('lobby', 'RG', $amount, $check_return['messages'] . '(' .
						$RG_API_result['Result'] . ')', 'fail', $transactionId, 2);
				}
			} else {
				$check_return['messages'] = '此帐号已经在 RG 娱乐城活动，请勿重复登入。';
				$check_return['code'] = 11;
				member_casino_transferrecords('lobby', 'RG', '0', $check_return['messages'], 'warning');
			}

			// 判斷轉帳模式 else block start
		} else {
			$check_return['messages'] = '所有GTOKEN余额为 0 故不进行转帐交易。';
			$check_return['code'] = 1;
			memberlog2db($_SESSION['member']->account, 'rg', 'info', $check_return['messages']);
			member_casino_transferrecords('lobby', 'RG', '0', $check_return['messages'], 'info');
		}
		// 判斷轉帳模式 if/elseif/else block end
		// 判斷有無RG帳號及平台環境 elseif block start
	} elseif ($r[0] == 1 and $config['casino_transfer_mode'] == 0) {
		$check_return['messages'] = '测试环境不进行转帐交易';
		$check_return['code'] = 1;
		member_casino_transferrecords('lobby', 'RG', '0', $check_return['messages'], 'info');
		// 判斷有無RG帳號及平台環境 else block start
	} else {
		$check_return['messages'] = '无此帐号 ID = ' . $member_id;
		$check_return['code'] = 0;
		member_casino_transferrecords('lobby', 'RG', '0', $check_return['messages'], 'fail');
	}
	// 判斷有無RG帳號及平台環境 if/elseif/else block end

	return ($check_return);
}


/**
 * 生成前往彩票遊戲 url
 *
 * @param    string $rg_account RG 娛樂城遊戲帳號
 * @param    string $rg_gameid  RG 彩票遊戲  ID
 * @param    int    $debug      是否為除錯模式，1為除錯模式
 *
 * @return string 遊戲 url
 */
function rg_gameurl(string $rg_account, string $rg_gameid, int $debug = 0)
{
	global $rgapi_column;

	// 取得game的資料
	$gamedata_sql = 'SELECT * FROM "casino_gameslist" WHERE "id" = \'' . $rg_gameid . '\';';
	$gamedata_result = runSQLall($gamedata_sql);

	$rg_gamecode = $gamedata_result['1']->gameid;
	$gamename = $gamedata_result['1']->gamename;

	// 判斷是否有娛樂城遊戲帳號 if/else block start
	if (isset($rg_account)) {
		// 取得代理鏈及反水鏈
		$memberList = MemberTreeNode::getMemberList($_SESSION['member']->id, true);
		MemberTreeNode::buildMemberTree($memberList[1], $memberList); // 建代理鏈
		$fsList = MemberTreeNode::getPredecessorPreferentialRate($memberList[$_SESSION['member']->id],
			$memberList); // 反水鏈

		// 建立ID與娛樂城帳號對應鏈
		$branchArr = genMemberBranch($memberList, '', $fsList);

		$masterId = $branchArr['master'];
		$memberId = $branchArr['member'];
		$branch = $branchArr['branch'];
		$RG_API_data = array(
			'masterId' => $masterId,
			'memberId' => $memberId,
			'gameId' => $rg_gamecode,
			'memberBranch' => $branch
		);

		$RG_API_result = rg_api('Login', $RG_API_data, $debug);
		if ($RG_API_result['error'] == lobby_rggame_params::$API_ERROR_CODE_NO_ERROR) {
			$re = $RG_API_result['Result']->ssoUrl;
			$logger = '会员 ' . $_SESSION['member']->account . ' 前往游戏' . $gamename . '.';
			member_casino_transferrecords('lobby', 'RG', '0', $logger, 'info');
		} else {
			$re = '';
			$logger = '会员 ' . $_SESSION['member']->account . ' 前往游戏 ' . $gamename . ' 但游戏网址取得错误(' . $RG_API_result['error'] . ')';
			member_casino_transferrecords('lobby', 'RG', '0', $logger, 'fail');
		}
	} else {
		$re = '';
		$logger = '会员 ' . $_SESSION['member']->account . ' 前往游戏 ' . $gamename . ' 但没有娱乐城帐号';
		member_casino_transferrecords('lobby', 'RG', '0', $logger, 'fail');
	}
	// 判斷是否有娛樂城遊戲帳號 if/else block end

	return ($re);
}


/**
 * 處理 RG 彩票 API 餘額資料庫相關方法
 *
 * @param   mixed $gtoken_cashier_account 系統代幣出納帳號
 * @param   mixed $rg_balance_api         RG API 取得餘額
 * @param   mixed $rg2gpk_balance         派彩
 * @param   mixed $rg_balance_db          資料庫錢包餘額
 * @param   int   $debug                  除錯模式，1為除錯模式
 *
 * @return mixed 查詢結果
 */
function db_rg2gpk_balance($gtoken_cashier_account, $rg_balance_api, $rg2gpk_balance, $rg_balance_db, $debug = 0)
{
	global $gtoken_cashier_account;
	global $transaction_category;
	global $auditmode_select;

	// 取得來源與目的帳號的 id ,  $gtoken_cashier_account(此為系統代幣出納帳號 global var.)
	$d['source_transferaccount'] = $gtoken_cashier_account; // 來源-系統
	$d['destination_transferaccount'] = $_SESSION['member']->account; // 目的-會員
	$source_id_sql = "SELECT * FROM root_member WHERE account = '" . $d['source_transferaccount'] . "';";
	// var_dump($source_id_sql);
	$source_id_result = runSQLall($source_id_sql);
	$destination_id_sql = "SELECT * FROM root_member WHERE account = '" . $d['destination_transferaccount'] . "';";
	// var_dump($destination_id_sql);
	$destination_id_result = runSQLall($destination_id_sql);
	// 取得帳款出進帳號 ID if/else block start
	if ($source_id_result[0] == 1 and $destination_id_result[0] == 1) {
		$d['source_transfer_id'] = $source_id_result[1]->id;
		$d['destination_transfer_id'] = $destination_id_result[1]->id;
	} else {
		$logger = '转帐的来源与目的帐号可能有问题，请稍候再试。';
		$r['ErrorCode'] = 590;
		$r['ErrorMessage'] = $logger;
		echo "<p> $logger </p>";
		die();
	}
	// 取得帳款出進帳號 ID if/else block end

	if ($debug == 1) {
		var_dump($rg2gpk_balance);
	}

	// 派彩有三種狀態，要有不同的對應 SQL 處理
	// $rg2gpk_balance >= 0 從娛樂城贏錢 or 沒有輸贏，把 RG 餘額取回 gpk
	// $rg2gpk_balance < 0 從娛樂城輸錢
	// 依據娛樂城贏輸處理取款 if/elseif/else block start
	if ($rg2gpk_balance >= 0) { // 從娛樂城贏錢 or 沒有輸贏，把 RG 餘額取回 gpk
		// 先取得當下的  wallets 變數資料,等等 sql 更新後. 就會消失了。
		$wallets_sql = "SELECT gtoken_balance,casino_accounts->'RG'->>'balance' as rg_balance FROM root_member_wallets WHERE id =
'" . $_SESSION['member']->id . "';";
		// var_dump($wallets_sql);
		$wallets_result = runSQLall($wallets_sql);
		// var_dump($wallets_result);
		// 在剛取出的 wallets 資料庫中rg的餘額(支出)
		$gtoken_rg_balance_db = round($wallets_result[1]->rg_balance, 2);
		// 在剛取出的 wallets 資料庫中gtoken的餘額(支出)
		$gtoken_balance_db = round($wallets_result[1]->gtoken_balance, 2);
		// 派彩 = 娛樂城餘額 - 本地端PT支出餘額
		$gtoken_balance = round(($gtoken_balance_db + $gtoken_rg_balance_db + $rg2gpk_balance), 2);

		// 交易開始
		$rg2gpk_transaction_sql = 'BEGIN;';
		// 存款金額 -- 娛樂城餘額
		$d['deposit'] = $gtoken_balance;
		// 提款金額 -- 本地端支出
		$d['withdrawal'] = $rg_balance_db;
		// 操作者
		$d['member_id'] = $_SESSION['member']->id;
		// RG + 代幣派彩
		$d['summary'] = lobby_rggame_params::$RG_CASINO_ID . $transaction_category['tokenpay'];
		// 稽核方式
		$d['auditmode'] = $auditmode_select['rg'];
		// 稽核金額 -- 派彩無須稽核
		$d['auditmodeamount'] = 0;
		// RG 取回的餘額為真錢
		$d['realcash'] = 2;
		// 交易類別 RG + $transaction_category['tokenpay']
		$d['transaction_category'] = 'tokenpay';
		// 變化的餘額
		$d['balance'] = $rg2gpk_balance;
		// var_dump($d);

		// 操作 root_member_wallets DB, 把 rg_balance 設為 0 , 把 gtoken_lock = null, 把 gtoken_balance = $d['deposit']
		// 錢包存入 餘額 , 把 rg_balance 扣除全部表示支出(投注).
		$rg2gpk_transaction_sql = $rg2gpk_transaction_sql . "
      UPDATE root_member_wallets SET changetime = NOW(), gtoken_balance =  " . $d['deposit'] . ", gtoken_lock = NULL,
      casino_accounts= jsonb_set(casino_accounts,'{\"RG\",\"balance\"}','0') WHERE id = '" . $d['destination_transfer_id'] . "'; ";
		// 目的帳號上的註記
		$d['destination_notes'] = '(會員收到RG派彩' . $d['balance'] . ')';
		// 針對目的會員的存簿寫入，$rg2gpk_balance >= 1 表示贏錢，所以從出納匯款到使用者帳號。
		$rg2gpk_transaction_sql = $rg2gpk_transaction_sql .
			'INSERT INTO "root_member_gtokenpassbook" ("transaction_time", "deposit", "withdrawal", "system_note", "member_id", "currency", "summary", "source_transferaccount", "auditmode", "auditmodeamount", "realcash", "destination_transferaccount", "transaction_category", "balance")' .
			"VALUES ('now()', '" . $d['deposit'] . "', '" . $d['withdrawal'] . "', '" . $d['destination_notes'] . "', '" . $d['member_id'] . "', '".$config['currency_sign']."', '" . $d['summary'] . "', '" . $d['destination_transferaccount'] . "', '" . $d['auditmode'] . "', '" . $d['auditmodeamount'] . "', '" . $d['realcash'] . "', " .
			"'" . $d['destination_transferaccount'] . "','" . $d['transaction_category'] . "', (SELECT gtoken_balance FROM root_member_wallets WHERE id = " . $d['destination_transfer_id'] . ") );";

		// 針對來源出納的存簿寫入
		$rg2gpk_transaction_sql = $rg2gpk_transaction_sql . "
      UPDATE root_member_wallets SET changetime = NOW(), gtoken_balance = (gtoken_balance - " . $d['balance'] . ") WHERE id = '" . $d['source_transfer_id'] . "'; ";
		// 來源帳號上的註記
		$d['source_notes'] = '(出納帳號 ' . $d['source_transferaccount'] . ' 幫RG派彩到會員 ' . $d['destination_transferaccount'] . ')';
		$rg2gpk_transaction_sql = $rg2gpk_transaction_sql .
			'INSERT INTO "root_member_gtokenpassbook" ("transaction_time", "deposit", "withdrawal", "system_note", "member_id", "currency", "summary", "source_transferaccount", "auditmode", "auditmodeamount", "realcash", "destination_transferaccount", "transaction_category", "balance")' .
			"VALUES ('now()', '0', '" . $d['balance'] . "', '" . $d['source_notes'] . "', '" . $d['member_id'] . "', '".$config['currency_sign']."', '" . $d['summary'] . "', '" . $d['source_transferaccount'] . "', '" . $d['auditmode'] . "', '" . $d['auditmodeamount'] . "', '" . $d['realcash'] . "', " .
			"'" . $d['source_transferaccount'] . "','" . $d['transaction_category'] . "', (SELECT gtoken_balance FROM root_member_wallets WHERE id = " . $d['source_transfer_id'] . "));";

		// commit 提交
		$rg2gpk_transaction_sql = $rg2gpk_transaction_sql . 'COMMIT;';
		if ($debug == 1) {
			echo '<p>SQL=' . $rg2gpk_transaction_sql . '</p>';
		}

		// 執行 transaction sql
		$rg2gpk_transaction_result = runSQLtransactions($rg2gpk_transaction_sql);
		// 執行轉帳結果 if/else block start
		if ($rg2gpk_transaction_result) {
			$logger = '从RG帐号' . $_SESSION['member']->rg_account . '取回余额到代币，统计后收入=' . $rg_balance_api .
				'，支出=' . $rg_balance_db . '，共计派彩=' . $rg2gpk_balance;
			$r['ErrorCode'] = lobby_rggame_params::$DB_EXECUTE_SUCCESS;
			$r['ErrorMessage'] = $logger;
			memberlog2db($_SESSION['member']->account, 'rglottery', 'info', "$logger");
			member_casino_transferrecords('RG', 'lobby', $rg_balance_api, $logger, 'info');
		} else {
			// 轉帳步驟必須要全部成功，才算成功。如果有失敗的話，紀錄為 ERROR LOG，需要通知管理員處理。(message system)
			$logger = '从RG帐号' . $_SESSION['member']->rg_account . '取回余额到代币，统计后收入=' . $rg_balance_api .
				'，支出=' . $rg_balance_db . '，共计派彩=' . $rg2gpk_balance;
			$logger = $logger . '但资料库处理错误，请通知客服人员处理。';
			memberlog2db($d['member_id'], 'rg_transaction', 'error', "$logger");
			$r['ErrorCode'] = 406;
			$r['ErrorMessage'] = $logger;
			memberlog2db($_SESSION['member']->account, 'rglottery', 'error', "$logger");
			member_casino_transferrecords('RG', 'lobby', $rg_balance_api, $logger, 'warning');
		}
		// 執行轉帳結果 if/else block end

		if ($debug == 1) {
			var_dump($r);
		}

	// 依據娛樂城贏輸處理取款 elseif block start
	} elseif ($rg2gpk_balance < 0) { // 從娛樂城輸錢
		// 先取得當下的  wallets 變數資料,等等 sql 更新後. 就會消失了。
		$wallets_sql = "SELECT gtoken_balance,casino_accounts->'RG'->>'balance' as rg_balance FROM root_member_wallets WHERE id =
'" . $_SESSION['member']->id . "';";
		// var_dump($wallets_sql);
		$wallets_result = runSQLall($wallets_sql);
		// var_dump($wallets_result);
		// 在剛取出的 wallets 資料庫中rg的餘額(支出)
		$gtoken_rg_balance_db = round($wallets_result[1]->rg_balance, 2);
		// 在剛取出的 wallets 資料庫中gtoken的餘額(支出)
		$gtoken_balance_db = round($wallets_result[1]->gtoken_balance, 2);
		// 派彩 = 娛樂城餘額 - 本地端PT支出餘額
		$gtoken_balance = round(($gtoken_balance_db + $gtoken_rg_balance_db + $rg2gpk_balance), 2);

		// 交易開始
		$rg2gpk_transaction_sql = 'BEGIN;';
		// 存款金額 -- 娛樂城餘額
		$d['deposit'] = $gtoken_balance;
		// 提款金額 -- 本地端支出
		$d['withdrawal'] = $rg_balance_db;
		// 操作者
		$d['member_id'] = $_SESSION['member']->id;
		// RG + 代幣派彩
		$d['summary'] = 'RG' . $transaction_category['tokenpay'];
		// 稽核方式
		$d['auditmode'] = $auditmode_select['rg'];
		// 稽核金額 -- 派彩無須稽核
		$d['auditmodeamount'] = 0;
		// RG 取回的餘額為真錢
		$d['realcash'] = 2;
		// 交易類別 RG + $transaction_category['tokenpay']
		$d['transaction_category'] = 'tokenpay';
		// 變化的餘額
		$d['balance'] = abs($rg2gpk_balance);
		// var_dump($d);

		// 操作 root_member_wallets DB, 把 rg_balance 設為 0 , 把 gtoken_lock = null, 把 gtoken_balance = $d['deposit']
		// 錢包存入 餘額 , 把 rg_balance 扣除全部表示支出(投注).
		$rg2gpk_transaction_sql = $rg2gpk_transaction_sql . "
      UPDATE root_member_wallets SET changetime = NOW(), gtoken_balance =  " . $d['deposit'] . ", gtoken_lock = NULL,casino_accounts= jsonb_set(casino_accounts,'{\"RG\",\"balance\"}','0') WHERE id = '" . $d['destination_transfer_id'] . "'; ";
		// 目的帳號上的註記
		$d['destination_notes'] = '(會員收到RG派彩' . $rg2gpk_balance . ')';
		// 針對目的會員的存簿寫入，
		$rg2gpk_transaction_sql = $rg2gpk_transaction_sql .
			'INSERT INTO "root_member_gtokenpassbook" ("transaction_time", "deposit", "withdrawal", "system_note", "member_id", "currency", "summary", "source_transferaccount", "auditmode", "auditmodeamount", "realcash", "destination_transferaccount", "transaction_category", "balance")' .
			"VALUES ('now()', '" . $d['deposit'] . "', '" . $d['withdrawal'] . "', '" . $d['destination_notes'] . "', '" . $d['member_id'] . "', '".$config['currency_sign']."', '" . $d['summary'] . "', '" . $d['destination_transferaccount'] . "', '" . $d['auditmode'] . "', '" . $d['auditmodeamount'] . "', '" . $d['realcash'] . "', " .
			"'" . $d['destination_transferaccount'] . "','" . $d['transaction_category'] . "', (SELECT gtoken_balance FROM root_member_wallets WHERE id = " . $d['destination_transfer_id'] . ") );";

		// 針對來源出納的存簿寫入
		$rg2gpk_transaction_sql = $rg2gpk_transaction_sql . "
      UPDATE root_member_wallets SET changetime = NOW(), gtoken_balance = (gtoken_balance + " . $d['balance'] . ") WHERE id = '" . $d['source_transfer_id'] . "'; ";
		// 來源帳號上的註記
		$d['source_notes'] = '(出納帳號 ' . $d['source_transferaccount'] . ' 從會員 ' . $d['destination_transferaccount'] . ' 取回派彩餘額)';
		$rg2gpk_transaction_sql = $rg2gpk_transaction_sql .
			'INSERT INTO "root_member_gtokenpassbook" ("transaction_time", "deposit", "withdrawal", "system_note", "member_id", "currency", "summary", "source_transferaccount", "auditmode", "auditmodeamount", "realcash", "destination_transferaccount", "transaction_category", "balance")' .
			"VALUES ('now()', '" . $d['balance'] . "', '0', '" . $d['source_notes'] . "', '" . $d['member_id'] . "', '".$config['currency_sign']."', '" . $d['summary'] . "', '" . $d['source_transferaccount'] . "', '" . $d['auditmode'] . "', '" . $d['auditmodeamount'] . "', '" . $d['realcash'] . "', " .
			"'" . $d['source_transferaccount'] . "','" . $d['transaction_category'] . "', (SELECT gtoken_balance FROM root_member_wallets WHERE id = " . $d['source_transfer_id'] . "));";

		// commit 提交
		$rg2gpk_transaction_sql = $rg2gpk_transaction_sql . 'COMMIT;';
		if ($debug == 1) {
			echo '<p>SQL=' . $rg2gpk_transaction_sql . '</p>';
		}

		// 執行 transaction sql
		$rg2gpk_transaction_result = runSQLtransactions($rg2gpk_transaction_sql);
		// 執行轉帳結果 if/else block start
		if ($rg2gpk_transaction_result) {
			$logger = '从RG帐号' . $_SESSION['member']->rg_account . '取回余额到代币，统计后收入=' . $rg_balance_api .
				'，支出=' . $rg_balance_db . '，共计派彩=' . $rg2gpk_balance;
			$r['ErrorCode'] = lobby_rggame_params::$DB_EXECUTE_SUCCESS;
			$r['ErrorMessage'] = $logger;
			memberlog2db($_SESSION['member']->account, 'rglottery', 'info', "$logger");
			member_casino_transferrecords('RG', 'lobby', $rg_balance_api, $logger, 'info');
		} else {
			// 轉帳步驟必須要全部成功，才算成功。如果有失敗的話，紀錄為 ERROR LOG，需要通知管理員處理。(message system)
			$logger = '从RG帐号' . $_SESSION['member']->rg_account . '取回余额到代币，统计后收入=' . $rg_balance_api .
				'，支出=' . $rg_balance_db . '，共计派彩=' . $rg2gpk_balance;
			$logger = $logger . '但资料库处理错误，请通知客服人员处理。';
			$r['ErrorCode'] = 406;
			$r['ErrorMessage'] = $logger;
			memberlog2db($_SESSION['member']->account, 'rglottery', 'error', "$logger");
			member_casino_transferrecords('RG', 'lobby', $rg_balance_api, $logger, 'warning');
		}
		// 執行轉帳結果 if/else block end
		// var_dump($r);
	// 依據娛樂城贏輸處理取款 else block start
	} else {
		$logger = '不可能发生';
		$r['ErrorCode'] = 500;
		$r['ErrorMessage'] = $logger;
		memberlog2db($_SESSION['member']->account, 'rglottery', 'error', "$logger");
		echo "<p> $logger </p>";
	}
	// 依據娛樂城贏輸處理取款 if/elseif/else block end

	return ($r);
}


/**
 * 取回 RG Lottery 的餘額，不能單獨使用，需要搭配 db_rg2gpk_balance 使用
 *
 * @param int $member_id 會員 ID
 * @param int $debug 是否為除錯模式，1為除錯模式
 *
 * @return mixed 執行取回餘額結果
 */
function retrieve_rg_casino_balance(int $member_id, int $debug = 0)
{

	global $gtoken_cashier_account;

	// 判斷會員是否 status 是否被鎖定了!!
	$member_sql = "SELECT * FROM root_member WHERE id = '" . $member_id . "' AND status = '1';";
	$member_result = runSQLall($member_sql);

	// 先取得當下的  member_wallets 變數資料,等等 sql 更新後. 就會消失了。
	$wallets_sql = <<<SQL
      SELECT gtoken_balance,gtoken_lock,
              casino_accounts->'RG'->>'account' as rg_account,
              casino_accounts->'RG'->>'password' as rg_password,
              casino_accounts->'RG'->>'balance' as rg_balance FROM root_member JOIN root_member_wallets ON
              root_member.id=root_member_wallets.id WHERE root_member.id = '{$member_id}';
SQL;
	$wallets_result = runSQLall($wallets_sql);
	if ($debug == 1) {
		var_dump($wallets_sql);
		var_dump($wallets_result);
	}

	// 查詢 DB 的 gtoken_lock  是否有紀錄在 RG 帳戶，NULL 沒有紀錄的話表示沒有餘額在 RG 帳戶。(已經取回了，代幣一次只能對應一個娛樂城)
	// AND 當 DB 有 rg_balance 的時候才動作, 如果沒有則結束。表示 db 帳號資料有問題。
	// 判斷帳號錢包狀態 if/else block start
	if ($member_result[0] == 1 and $wallets_result[0] == 1
		and $wallets_result[1]->rg_account != null and $wallets_result[1]->gtoken_lock == lobby_rggame_params::$RG_CASINO_ID) {

		// gtoken 紀錄為 RG , API 檢查 RG 的餘額有多少
		$delimitedAccountNumbers = $wallets_result[1]->rg_account;
		$RG_API_data = array(
			'memberIds' => $delimitedAccountNumbers
		);
		if ($debug == 1) {
			var_dump($RG_API_data);
		}
		$RG_API_result = rg_api('GetMemberCurrentInfo', $RG_API_data, $debug);
		$RG_API_kickuser_result = rg_api('KickMember', $RG_API_data, $debug);
		if ($RG_API_result['error'] == lobby_rggame_params::$API_ERROR_CODE_NO_ERROR
			and $RG_API_kickuser_result['error'] == lobby_rggame_params::$API_ERROR_CODE_NO_ERROR) {
			// 查詢餘額動作，成立後執行. 失敗的話結束，可能網路有問題。
			// 取得的 RG API 餘額 , 保留小數第二位 round( $x, 2);
			$rg_balance_api = round($RG_API_result['Result']->data[0]->coin, 2);
			$logger = 'RG API 查询余额为' . $RG_API_result['Result']->data[0]->coin . '操作的余额为' . $rg_balance_api;
			$r['code'] = 1;
			$r['messages'] = $logger;

			// 如果 RG 餘額 > 0
			// RG餘額判斷對應動作 if/elseif/else block start
			if ($rg_balance_api >= 1) {
				// 1.執行 RG API 取回 RG 餘額 ，到 RG 的出納帳戶(API操作) , 成功才執行 2,3
				// 動作：由遊戲帳戶取款
				$transactionId = lobby_rggame_params::$RG_CASINO_ID . '0deposit_all0' . date("Ymdhis");
				$RG_API_data = array(
					'memberId' => $wallets_result[1]->rg_account,
					'transactionId' => $transactionId,
					'amount' => $rg_balance_api,
					'transferType' => lobby_rggame_params::$TRANSFER_TYPE['Deposit_all']
				);
				if ($debug == 1) {
					echo '1.執行 RG API 取回 RG 餘額 ，到 RG 的出納帳戶(API操作) , 成功才執行 2,3';
					var_dump($RG_API_data);
				}

				$RG_API_result = rg_api('Transfer', $RG_API_data, $debug);
				if ($debug == 1) {
					var_dump($RG_API_result);
				}

				// 判斷取款是否成功 if/else block start
				if ($RG_API_result['error'] == lobby_rggame_params::$API_ERROR_CODE_NO_ERROR) {
					// 取回RG餘額成功
					$logger = 'RG API 从帐号' . $wallets_result[1]->rg_account . '取款余额' . $rg_balance_api . '成功。交易编号为'
						. $transactionId;
					$r['code'] = 100;
					$r['messages'] = $logger;
					memberlog2db($_SESSION['member']->account, 'rglottery', 'info', "$logger");
					member_casino_transferrecords('RG', 'lobby', $rg_balance_api, $logger, 'success',
						$transactionId, 1);
					if ($debug == 1) {
						echo "<p> $logger </p>";
						var_dump($RG_API_result);
					}

					// 先取得當下的 wallets 變數資料,等等 sql 更新後. 就會消失了。
					$wallets_sql = "SELECT casino_accounts->'RG'->>'balance' as rg_balance FROM root_member_wallets WHERE id = '" . $_SESSION['member']->id . "';";
					// var_dump($wallets_sql);
					$wallets_result = runSQLall($wallets_sql);
					// var_dump($wallets_result);
					// 在剛取出的 wallets 資料庫中的餘額(支出)
					$rg_balance_db = round($wallets_result[1]->rg_balance, 2);
					// 派彩 = 娛樂城餘額 - 本地端RG支出餘額
					$rg2gpk_balance = round(($rg_balance_api - $rg_balance_db), 2);
					$r['balance'] = $rg2gpk_balance;

					// 處理 DB 的轉帳問題 -- 2 and 3
					$db_rg2gpk_balance_result = db_rg2gpk_balance($gtoken_cashier_account, $rg_balance_api,
						$rg2gpk_balance, $rg_balance_db);
					// 判斷 DB 轉帳是否成功 if/else block start
					if ($db_rg2gpk_balance_result['ErrorCode'] == 1) {
						$r['code'] = 1;
						$r['messages'] = $db_rg2gpk_balance_result['ErrorMessage'];
						$logger = $r['messages'];
						memberlog2db($_SESSION['member']->account, 'rg2gpk', 'info', "$logger");
					} else {
						$r['code'] = 523;
						$r['messages'] = $db_rg2gpk_balance_result['ErrorMessage'];
						$logger = $r['messages'];
						memberlog2db($_SESSION['member']->account, 'rg2gpk', 'error', "$logger");
					}
					// 判斷 DB 轉帳是否成功 if/else block end
					if ($debug == 1) {
						echo '處理 DB 的轉帳問題 -- 2 and 3';
						var_dump($db_rg2gpk_balance_result);
					}
				// 判斷取款是否成功 else block start
				} else {
					$logger = 'RG API 从帐号' . $_SESSION['member']->rg_account . '取款余额' . $rg_balance_api . '失败，原因: ' .
					lobby_rggame_params::$API_ERROR_CODE_TRANSFER[$RG_API_result['error']];
					$r['code'] = 405;
					$r['messages'] = $logger;
					memberlog2db($_SESSION['member']->account, 'rglottery', 'error', "$logger");
					member_casino_transferrecords('RG', 'lobby', '0', $logger . '(' . $RG_API_result['Result']->data[0] .
						')', 'fail', null, 2);

					if ($debug == 1) {
						echo "5.1 執行 RG API 取回 RG 餘額 ，到 RG 的出納帳戶(API操作) , 成功才執行 5.2,5.3";
						echo "<p> $logger </p>";
						var_dump($r);
					}
				}
				// 判斷取款是否成功 if/else block end
			// RG餘額判斷對應動作 elseif block start
			} elseif ($rg_balance_api < 1 and $rg_balance_api >= 0) {
				$logger = 'RG余额 < 1 ，RG余额不足，无法取回任何的余额，将余额转回 GPK。';
				$r['code'] = 406;
				$r['messages'] = $logger;
				memberlog2db($_SESSION['member']->account, 'rglottery', 'info', "$logger");
				member_casino_transferrecords('RG', 'lobby', '0', $logger, 'success');

				// 先取得當下的  wallets 變數資料,等等 sql 更新後. 就會消失了。
				$wallets_sql = <<<SQL
                  SELECT gtoken_balance,gtoken_lock,
                          casino_accounts->'RG'->>'account' as rg_account,
                          casino_accounts->'RG'->>'password' as rg_password,
                          casino_accounts->'RG'->>'balance' as rg_balance FROM root_member JOIN root_member_wallets
                          ON root_member.id=root_member_wallets.id WHERE root_member.id = '{$_SESSION['member']->id}';
SQL;
				// var_dump($wallets_sql);
				$wallets_result = runSQLall($wallets_sql);
				// var_dump($wallets_result);
				// 在剛取出的 wallets 資料庫中的餘額(支出)
				$rg_balance_db = round($wallets_result[1]->rg_balance, 2);
				// 派彩 = 娛樂城餘額 - 本地端RG支出餘額
				$rg2gpk_balance = round(($rg_balance_api - $rg_balance_db), 2);
				$r['balance'] = $rg2gpk_balance;

				// 處理 DB 的轉帳問題 -- 2 and 3
				$db_rg2gpk_balance_result = db_rg2gpk_balance($gtoken_cashier_account, $rg_balance_api,
					$rg2gpk_balance, $rg_balance_db);
				if ($db_rg2gpk_balance_result['ErrorCode'] == lobby_rggame_params::$DB_EXECUTE_SUCCESS) {
					$r['code'] = 1;
					$r['messages'] = $db_rg2gpk_balance_result['ErrorMessage'];
					$logger = $r['messages'];
					memberlog2db($_SESSION['member']->account, 'rg2gpk', 'info', "$logger");
				} else {
					$r['code'] = 523;
					$r['messages'] = $db_rg2gpk_balance_result['ErrorMessage'];
					$logger = $r['messages'];
					memberlog2db($_SESSION['member']->account, 'rg2gpk', 'error', "$logger");
				}

				if ($debug == 1) {
					echo '處理 DB 的轉帳問題 -- 5.2 and 5.3';
					var_dump($db_rg2gpk_balance_result);
				}
			// RG餘額判斷對應動作 else block start
			} else {
				// RG餘額 < 0 , 不可能發生
				$logger = 'RG余额 < 0 ，不可能发生。';
				$r['code'] = 404;
				$r['messages'] = $logger;
			}
			// RG餘額判斷對應動作 if/elseif/else block end
		} else {
			// gtoken 紀錄為 RG , API 檢查 RG 的餘額有多少
			$logger = 'RG API 查询余额失败，系统维护中请晚点再试。';
			$r['code'] = 403;
			$r['messages'] = $logger;
			member_casino_transferrecords('RG', 'lobby', '0', $logger . '(' . $RG_API_result['Result']->data[0]->coin . ')', 'fail');
			if ($debug == 1) {
				var_dump($RG_API_result);
			}
		}
	// 判斷帳號錢包狀態 if block end
	} else {
		// 查詢 session 的 gtoken_lock 是否有紀錄在 RG 帳戶，NULL 沒有紀錄的話表示沒有餘額在 RG 帳戶。
		// AND 當 session 有 rg_balance 的時候才動作, 如果沒有則結束。表示 db 帳號資料有問題。
		$logger = '没有余额在 RG 帐户 OR DB 帐号资料有问题 ';
		$r['code'] = 401;
		$r['messages'] = $logger;
		member_casino_transferrecords('RG', 'lobby', '0', $logger, 'fail');
	}
	// 判斷帳號錢包狀態 if/else block end

	if ($debug == 1) {
		echo "<p> $logger </p>";
		var_dump($r);
	}
	if ($r['code'] == 1) {
		unset($_SESSION['wallet_transfer']);
	}

	return ($r);
}


/**
 * 取得會員
 *
 * @param int $member_id 會員 ID
 *
 * @return object
 */
function getMemberInRGCasinoById(int $member_id)
{
	$member_sql = <<<SQL
    SELECT gtoken_balance,gtoken_lock,
            casino_accounts->'RG'->>'account' as rg_account,
            casino_accounts->'RG'->>'password' as rg_password,
            casino_accounts->'RG'->>'balance' as rg_balance
            FROM root_member JOIN root_member_wallets
            ON root_member.id=root_member_wallets.id WHERE root_member.id = '{$member_id}';
SQL;
	$r = runSQLall($member_sql);
	return $r;
}


/**
 * 更新會員錢包帳號密碼
 *
 * @param string $casinoId         娛樂城 ID
 * @param string $accountnumberApi 娛樂城遊戲帳號
 * @param string $password         娛樂城遊戲密碼
 * @param int    $memberWalletId   會員錢包 ID (即會員 ID)
 *
 * @return mixed 更新資料庫結果
 */
function updateWattleAccountAndPassword(string $casinoId, string $accountnumberApi, string $password, int $memberWalletId)
{
	$sql = "UPDATE root_member_wallets SET changetime = now(), casino_accounts = casino_accounts ||
						'{\"$casinoId\":{\"account\":\"$accountnumberApi\", \"password\":\"$password\",
						\"balance\":\"0.0\"}}' WHERE id = '$memberWalletId';";
	// var_dump($sql);
	return runSQL($sql);
}


/**
 * 隨意字串生成器
 *
 * @param int $count 需要字串長度
 *
 * @return string 生成字串
 */
function randomStrGenerator($count = 5)
{
	$seed = str_split('abcdefghijklmnopqrstuvwxyz'
		. 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
		. '0123456789_');
	shuffle($seed);
	$rand = '';
	foreach (array_rand($seed, $count) as $k) $rand .= $seed[$k];
	return $rand;
}


/**
 * 生成API Key
 *
 * @param string $secret API演算key
 * @param array  $params 參數
 *
 * @return string API Key
 */
function genApiKey(string $secret, array $params = []): string
{
	$head = randomStrGenerator(5);
	$footer = randomStrGenerator(5);
	$middle = '';
	foreach ($params as $key => $value) {
		if ($key == 'memberBranch') {
			$middle = $middle . $key . '=' . json_encode($value) . '&';
		} else {
			$middle = $middle . $key . '=' . $value . '&';
		}
	}
	return $head . md5($middle . 'Key=' . $secret) . $footer;
}


/**
 * 取得會員娛樂城遊戲帳號
 *
 * @param int    $memberId 會員 ID
 * @param string $casinoId 娛樂城 ID
 *
 * @return mixed 娛樂城遊戲帳號查詢結果，無帳號回傳 null
 */
function getGameAccountByMemberId(int $memberId, string $casinoId)
{
	$sql = "SELECT casino_accounts -> '" . $casinoId . "' ->> 'account' FROM root_member_wallets WHERE id = " . $memberId;
	$result = runSQLall($sql);
	if ($result[0] == lobby_rggame_params::$DB_ZERO_RESULT) {
		return null;
	} else {
		return get_object_vars($result[1])['?column?'];
	}
}


/**
 * 生成娛樂城遊戲帳號
 *
 * @param int    $memberId 會員ID
 * @param int    $base     基底位數，如遊戲帳號為十位數，填入長度為 10 減去 $prefix 字串長度
 * @param string $prefix   字首
 *
 * @return string 遊戲帳號
 */
function genGameAccountByMemberId(int $memberId, int $base, string $prefix): string
{
	$account = $base + $memberId;
	return $prefix . $account;
}
