<?php
// ----------------------------------------------------------------------------
// Features:  前端 -- 遊藝大廳商業邏輯
// File Name: gamelobby_action.php
// Author:	  Ian
// Related:   gamelobby.php
// Log:
// 2019.07.29 新增瀏覽紀錄、娛樂城遊戲排序 Letter
// 2019.09.26 修改通用娛樂城設定 Letter
// ----------------------------------------------------------------------------
// actions:
// gametable 組成遊戲列表
// gametable_mini 組成遊戲列表(手機)
// myfavtable 我的最愛列表
// hotgamestable 熱門遊戲列表
// searchgame 搜尋遊戲
// addmyfav 新增我的最愛
// delmyfav 刪除我的最愛
// gotogame 進入遊戲
// Transferout_GCASH_GTOKEN_balance 自動儲值
// Retrieve_Casino_balance 從娛樂城取回餘額
// businessDemo 業務展示模式
// getReviewGames 取得瀏覽遊戲紀錄
// setReviewGame 存入瀏覽遊戲紀錄
// recommendList 推薦遊戲列表

require_once dirname(__FILE__) . "/config.php";
// 支援多國語系
require_once dirname(__FILE__) . "/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) . "/lib.php";
// 取得 casino 的 function
require_once dirname(__FILE__) . "/casino/casino_config.php";

// -------------------------------------------------------------------------
// 本程式使用的 function
// -------------------------------------------------------------------------

// 把DB出來的結果構成給頁面用生成BUTTON用的陣列
function queryresult2array($query_result, $myfav = 0)
{
	global $cdn4gamesicon;
	global $cdnrooturl;
	global $tr;
	global $config;
	global $memberstatus;

	//var_dump($query_result);

	// -------------------------------------------------------------------------------------
	// 需要修改變換保護 cdn , 避免 http://cdn.baidu-cdn-hk.com/ 這個網址直接暴露, 被 GFW 封鎖
	// $mggame_cdn_baseurl 	= 'http://cdn.baidu-cdn-hk.com/Multimedia/Mg2Game/';
	// 上面網址對應成為下面目錄

	$gameitem = array();

	if ($myfav == 1) {
		$myfavlabel = $tr['DelMyFav'];
		$myfavfunc = 'delmyfav';
	} else {
		$myfavlabel = $tr['AddMyFav'];
		$myfavfunc = 'addmyfav';
	}
	$g2gamelabel = $tr['GoToGame'];
	$memberstatus = '0';

	// 判斷是否為已登入會員，如果是，則取出會員我的最愛的gameid來做比對用
	if (isset($_SESSION['member']) AND $myfav == 0) {
		$favgamelist = array();
		$favgamelist_sql = 'SELECT gameid FROM "root_member_gamelist" WHERE "open" = 1 AND memberid = ' . $_SESSION['member']->id . ';';
		$favgamelist_result = runSQLall($favgamelist_sql, 0, 'r');
		if ($favgamelist_result[0] >= 1) {
			// 資料庫依據不同的條件變換資料庫檔案
			for ($i = 1; $i <= $favgamelist_result[0]; $i++) {
				$favgamelist[] = $favgamelist_result[$i]->gameid;
			}
			//var_dump($favgamelist);
		}
	}

	for ($i = 1; $i <= $query_result[0]; $i++) {
		// 行銷想關設定
		$ms = json_decode($query_result[$i]->marketing_strategy, 'true');
		$gameplatform = $query_result[$i]->gameplatform;
		// 產生圖片路徑 ，因為 mg2 系統是 .net 很多命名原則有大小寫，再 linux 變成很困難的處理。所以預設把所有的檔名轉成小寫處理。 -- skip
		// 因為 FLASH GAME的圖跟HTML5 GAME的圖顯示格式不一樣，故加掛判斷
		$ButtonImageName = $query_result[$i]->imagefilename;
		// 呼叫的 game 的 lanchID
		$gamecode = $query_result[$i]->gamelist_id;
		$category = $query_result[$i]->category;
		$casinoid = $query_result[$i]->casino_id;
		$casinoname = json_decode($query_result[$i]->display_name, true)['default'];

		if ($myfav == 0) {
			// 判斷是否已記為最愛
			if (isset($favgamelist) AND in_array($gamecode, $favgamelist)) {
				$myfavlabel = $tr['DelMyFav'];
				$myfavfunc = 'delmyfav';
			} else {
				$myfavlabel = $tr['AddMyFav'];
				$myfavfunc = 'addmyfav';
			}
		}

		switch ($casinoid) {
			case 'MEGA':
				$casinolabel = 'GPK';
				break;
			case 'PGS':
				$casinolabel = 'PG';
				break;
			default:
				$casinolabel = $casinoid;
				break;
		}

		if (isset($ms['image']) AND $ms['image'] != '') {
			$game_image_url = $ms['image'];
		} elseif ($ButtonImageName != '') {
			if ($casinoid == 'MG') {
				$mggame_html5_cdn_baseurl = $cdn4gamesicon . 'mghtml5/'; //HTML5 GAME 的圖片
				$mggame_flash_cdn_baseurl = $cdn4gamesicon . 'mg/'; //FLASH GAME 的圖片
				if ($gameplatform == 'flash') {
					$game_image_url = $mggame_flash_cdn_baseurl . $ButtonImageName . '.png';
				} elseif ($gameplatform == 'html5') {
					$game_image_url = $mggame_html5_cdn_baseurl . $ButtonImageName . '.png';
				}
			} else {
				$game_image_url = $cdn4gamesicon . strtolower($casinoid) . '/' . $ButtonImageName . '.png';
			}
		} else {
			$game_image_url = $cdnrooturl . 'gamesicon/casino.png';
		}
		// echo $game_image_url;

		if ($gameplatform == 'flash') {
			$gamename_mark = 'Fl';
		} elseif ($gameplatform == 'html5') {
			$gamename_mark = 'H5';
		}

		// 顯示名稱, default english, 如果沒有中文就顯示英文。
		$game_showname = translateSpecificChar("'", getDisplayNameByLanguage($query_result[$i]->game_display_name, $_SESSION['lang']), 1);
//		if ($_SESSION['lang'] == 'zh-cn') {
//			if (isset($ms['cname']) AND $ms['cname'] != '') {
//				$game_showname = $ms['cname'];
//			} else {
//				if (!isset($query_result[$i]->gamename_cn) OR trim($query_result[$i]->gamename_cn) == '') {
//					$game_showname = $query_result[$i]->gamename;
//				} else {
//					$game_showname = $query_result[$i]->gamename_cn;
//				}
//			}
//		} else {
//			$game_showname = (isset($ms['ename']) AND $ms['ename'] != '') ? $ms['ename'] : $query_result[$i]->gamename;
//		}

		// 判斷是否為業務展示站台
		if($memberstatus == '2') {
			$bsdemo = 'walletlock';
		} elseif ($config['businessDemo'] == 0 OR in_array($casinoid, $config['businessDemo_skipCasino'])) {
			$bsdemo = 0;
		} else {
			$bsdemo = 'bsdemo';
		}

		// 當未登入時，加上JWT字串
		// 需要傳遞的陣列
		// formtype --> [POST|GET] 轉址傳遞變數的方式(必要)
		// formurl --> 自訂轉址指定的網址, 相對路徑或絕對路徑都可以 (必要)
		// 其他變數(自訂)
		if (!isset($_SESSION['member'])) {
			$gotogame_array = array(
				'formtype' => 'GET',
				'formurl' => 'gamelobby_action.php',
				'a' => 'gotogame',
				'login' => '1',
				'casinoid' => $casinoid,
				'casinoname' => $casinoname,
				'gamecode' => $gamecode,
				'gametype' => $category,
				'gameplatform' => $gameplatform,
				'bsdemo' => $bsdemo
			);
			$myfav_array = array(
				'formtype' => 'GET',
				'formurl' => 'gamelobby_action.php',
				'a' => 'addmyfav',
				'login' => '1',
				'casinoid' => $casinoid,
				'casinoname' => $casinoname,
				'gameid' => $gamecode,
				'gameplatform' => $gameplatform
			);
			// 產生 token , salt是檢核密碼預設值為123456 ,需要配合 jwtdec 的解碼, 此範例設定為 123456
			$token = jwtenc('123456', $gotogame_array);
			$favtoken = jwtenc('123456', $myfav_array);
		} else {
			$token = '';
			$favtoken = '';
		}

		$gameitem[$i] = array('gamename' => $game_showname,
			'casinoid' => $casinoid,
			'casinoname' => $casinoname,
			'casinolabel' => $casinolabel,
			'gamecode' => $gamecode,
			'gametype' => $category,
			'gameplatform' => $gameplatform,
			'gamename_mark' => $gamename_mark,
			'gameimgurl' => $game_image_url,
			'g2gamelabel' => $g2gamelabel,
			'myfavlabel' => $myfavlabel,
			'myfavfunc' => $myfavfunc,
			'myfavtoken' => $favtoken,
			'token' => $token,
			'bsdemo' => $bsdemo,
			'cdnurl' => $cdnrooturl . 'gamesicon/casino');
	}
	return $gameitem;
}


// 產生查詢條件
function query_str($query_sql_array, $sql_column)
{
	$query_sql = '';
	//檢查query的值
	if (isset($query_sql_array) AND $query_sql_array != NULL) {
		$array_count = count($query_sql_array);
		if ($array_count > 1) {
			$for_chk = 0;
			$sub_sql = '';
			foreach ($query_sql_array as $key => $val) {
				if ($for_chk > 0) {
					$sub_sql = $sub_sql . ' OR ';
				}
				$sub_sql = $sub_sql . $sql_column . ' = \'' . $val . '\'';
				$for_chk++;
			}
			$query_sql = $query_sql . '( ' . $sub_sql . ' )';
		} else {
			$query_sql = $query_sql . $sql_column . ' = \'' . $query_sql_array[0] . '\'';
		}
		$query_top = 1;
	}

	return $query_sql;
}

// -------------------------------------------------------------------------
// 取得日期 - 決定開始用份的範圍日期
// -------------------------------------------------------------------------
// get example: ?current_datepicker=2017-02-03
// ref: http://php.net/manual/en/function.checkdate.php
function validateDate($date, $format = 'Y-m-d H:i:s')
{
	$d = DateTime::createFromFormat($format, $date);
	return $d && $d->format($format) == $date;
}


/**
 *  娛樂城遊戲是否開啟
 *
 * @param mixed $gameId 遊戲ID
 * @param int   $debug  除錯模式，預設 0 為關閉
 *
 * @return bool 遊戲開啟回傳 true
 */
function isGameOpen($gameId, $debug = 0)
{
	$sql = 'SELECT "open" FROM casino_gameslist WHERE "id" = ' . $gameId . ';';
	$result = runSQLall($sql, $debug);

	if ($result[0] > 0) {
		if ($result[1]->open == 1) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}


/**
 *  轉換特殊字元
 *  目前可轉換字元對照表
 *  '   => +0squote
 *  "  => +0dquote
 *  > => +0grater
 *  < => +0less
 *
 * @param mixed $source 要轉換的特殊字元
 * @param mixed $sentence 轉換的單詞
 * @param int $codeMode 轉換模式， 0 為編碼，1 為解碼
 *
 * @return string 轉換後字元
 */
function translateSpecificChar($source, $sentence, $codeMode = 0)
{
	// 解析特殊字元
	$translateArr = array(
		"'" => '+0squote',
		'"' => '+0dquote',
		'>' => '+0grater',
		'<' => '+0less'
	);

	// 轉換後字元
	$changed = '';
	if ($codeMode == 0) { // 編碼
		$changed = str_replace($source, $translateArr[$source], $sentence);
	} elseif ($codeMode == 1) { // 解碼
		$changed = str_replace($translateArr[$source], $source, $sentence);
	}

	return $changed;
}


/**
 *  取得遊戲語系名稱
 *
 * @param string $displayNames 語系顯示名稱
 * @param string $i18n         語系代碼
 *
 * @return mixed|string 遊戲名稱
 */
function getDisplayNameByLanguage($displayNames, $i18n)
{
	$gameName = '';
	$result = json_decode($displayNames, true);
	if (count($result) > 0) {
		if (key_exists($i18n, $result)) {
			$gameName = $result[$i18n];
		} else {
			$gameName = $result['en-us'];
		}
	}
	return $gameName;
}

/**
 * 輸出提示訊息
 *
 * @param string $notify 提示訊息
 *
 */
 function notify_html($notify){
	 global $config;
	 global $tr;
	 global $cdnfullurl_js;
	 global $ui_link;
	 global $customer_service_cofnig;
	 global $cdnfullurl;
	 global $extend_head;
	 global $gamelobby_setting;

	 // ----------------------------------------------------------------------------
	 // 準備填入的內容
	 // ----------------------------------------------------------------------------
	 $maingame_category = '';
	 $lobbyname = '';

	 // 將內容塞到 html meta 的關鍵字, SEO 加強使用
	 $tmpl = array();
	 $tmpl['html_meta_description'] = $config['companyShortName'];
	 $tmpl['html_meta_author'] = $config['companyShortName'];
	 $tmpl['html_meta_title'] = $tr['gamelobby'] . '-' . $config['companyShortName'];

	 // 系統訊息顯示
	 $tmpl['message'] = '';
	 // 擴充再 head 的內容 可以是 js or css
	 $tmpl['extend_head'] = '';
	 // 擴充於檔案末端的 Javascript
	 $tmpl['extend_js'] = '';
	 // 主要內容 -- title
	 $tmpl['paneltitle_content'] = '<span class="glyphicon glyphicon-th" aria-hidden="true"></span>' . $tr['gamelobby'];
	 // 主要內容 -- content
	 $tmpl['panelbody_content'] = $notify;
	 // banner標題
	 $tmpl['banner'] = '';
	 // menu增加active
	 $tmpl['menu_active'] = '';

	 // ----------------------------------------------------------------------------
	 // 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
	 // ----------------------------------------------------------------------------
	 include($config['template_path'] . "template/login2page.tmpl.php");
 }

// -------------------------------------------------------------------------
// END function lib
// -------------------------------------------------------------------------

// -------------------------------------------------------------------------
// GET / POST 傳值處理
// -------------------------------------------------------------------------
$debug = 0;
$reviewCounts = 7;

if (isset($_GET['a'])) {
	$action = filter_var($_GET['a'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
} else {
	$page_html = '<div style="
  background-color:black;
  height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  overflow: hidden;
  ">
  <img src="' . $cdnrooturl . 'loading_spin.gif">
  </div>
  ';

	echo $page_html;
	die('');
}

// 檢查來源是否為 login2page
if (isset($_GET['login']) AND $_GET['login'] == 1) {
	$newlogin = 1;
} else {
	$newlogin = 0;
}
if (isset($_GET['gameid'])) {
	$gameid = filter_var($_GET['gameid'], FILTER_SANITIZE_STRING);
}
if (isset($_GET['gamecode'])) {
	$gamecode = filter_var($_GET['gamecode'], FILTER_SANITIZE_STRING);
}
if (isset($_GET['gametype'])) {
	$category = filter_var($_GET['gametype'], FILTER_SANITIZE_STRING);
}
if (isset($_GET['casinoname'])) {
	$casinoname = filter_var($_GET['casinoname'], FILTER_SANITIZE_STRING);
}
if (isset($_GET['gameplatform'])) {
	$gameplatform = filter_var($_GET['gameplatform'], FILTER_SANITIZE_STRING);
}
if (isset($_GET['gamename'])) {
	$gamename = filter_var($_GET['gamename'], FILTER_SANITIZE_STRING);
}
if (isset($_GET['token'])) {
	$token = filter_var($_GET['token'], FILTER_SANITIZE_STRING);
}

$mobile = (isset($_GET['mobile'])) ? filter_var($_GET['mobile'], FILTER_SANITIZE_STRING) : '0';

// 程式每次的處理量 -- 當資料量太大時，可以分段處理。 透過 GET 傳遞依序處理。
$current_per_size = (isset($_GET['num']) AND filter_var($_GET['num'], FILTER_VALIDATE_INT)) ? filter_var($_GET['num'], FILTER_VALIDATE_INT) : '18';
// 起始頁面, 搭配 current_per_size 決定起始點位置
if (isset($_GET['start']) AND $_GET['start'] != NULL) {
	$current_page_no = filter_var($_GET['start'], FILTER_VALIDATE_INT);
	$current_page_no = ($current_page_no - 1) * $current_per_size;
} else {
	$current_page_no = 0;
}

// -------------------------------------------------------------------------
// GET / POST 傳值處理 END
// -------------------------------------------------------------------------

// 取得目前娛樂城的啟用狀態
$casino_list_sql = 'SELECT casinoid FROM "casino_list" WHERE "open" != 1;';
$casino_list_result = runSQLall($casino_list_sql, $debug, 'r');

$casino_sql = '';
if ($casino_list_result[0] >= 1) {
	$casino_sql = ' AND casino_id NOT IN ( ';
	$first = 1;
	for ($i = 1; $i <= $casino_list_result[0]; $i++) {
		if ($first == 0) {
			$casino_sql = $casino_sql . ',';
		}
		$casino_sql = $casino_sql . '\'' . $casino_list_result[$i]->casinoid . '\'';
		$first = 0;
	}
	$casino_sql = $casino_sql . ')';
}

// 取得會員狀態
$memberstatus = '0';
if(isset( $_SESSION['member'])){
	$memberstatus_sql = 'SELECT status FROM "root_member" WHERE id = ' . $_SESSION['member']->id . ';';
	$memberstatus_result = runSQLall($memberstatus_sql, 0, 'r');
	$memberstatus = ($memberstatus_result['0'] == 1) ? $memberstatus_result['1']->status : '0';
}

// actions
if ($action == 'gametable') {

	$query_str = '';
	if (isset($_POST) AND filter_var_array($_POST, FILTER_SANITIZE_STRING)) {
		// 處理搜尋條件
		// 指定娛樂城
		if (isset($_POST['casino'][0]) && $_POST['casino'][0] != 'allcasino') {
			$query_arr = filter_var_array($_POST['casino'], FILTER_SANITIZE_STRING);
			$query_str = $query_str . ' AND ' . query_str($query_arr, 'casino_id');
		}
		// 指定遊戲類型
		if (isset($_POST['gametype'][0]) && $_POST['gametype'][0] != 'allgametype') {
			$query_arr = filter_var_array($_POST['gametype'], FILTER_SANITIZE_STRING);
			$gametype_chk = preg_split('/_/', $query_arr[0]);
			if (count($gametype_chk) > 1) {
				$query_category = $gametype_chk[0];
				$sub_query_arr = array();
				foreach ($query_arr as $key => $val) {
					$sub_query_arr[] = str_replace($query_category . '_', '', $val);
				}
				$query_str = $query_str . ' AND ' . query_str($sub_query_arr, 'LOWER(gametype)');
			} else {
				$query_str = $query_str . ' AND ' . query_str($query_arr, 'category');
			}
		}
		// 指定遊戲子分類
		if (isset($_POST['subtype'][0]) && $_POST['subtype'][0] != 'allsubcategory') {
			$query_arr = filter_var_array($_POST['subtype'], FILTER_SANITIZE_STRING);
			$query_str = $query_str . ' AND ' . query_str($query_arr, 'sub_category');
		}
		// 字串
		if ($_POST['search'] != '') {
			$query_arr = preg_replace('/([^A-Za-z0-9\p{Han}])/ui', '', urldecode($_POST['search']));
			$query_str = $query_str . ' AND ( gamename ILIKE \'%' . $query_arr . '%\' OR gamename_cn ILIKE \'%' . $query_arr . '%\' OR category ILIKE \'%' . $query_arr . '%\' OR marketing_strategy->>\'ename\' ILIKE \'%' . $query_arr . '%\' OR marketing_strategy->>\'cname\' ILIKE \'%' . $query_arr . '%\' )';
		}

		// 行銷類型
		$query_arr = filter_var($_POST['searchct'], FILTER_SANITIZE_STRING);
		if (empty($query_arr)) {
			$query_str .= ' AND "marketing_strategy"->>\'mct\' = \'game\'';
		} else {
			$query_str = $query_str . ' AND "marketing_strategy"->>\'mct\' = \'' . $query_arr . '\'';
		}
	} else {
		$query_str = $query_str . ' AND category NOT IN (\'Live\',\'Lottery\',\'Sport\')';
	}

	// 判斷客戶端設備，如果不是電腦，就只顯示HTML5的
	$gameplatform_sql = '';
	if (isset($_SESSION['site_mode']) AND $_SESSION['site_mode'] != 'desktop') {
		$gameplatform_sql = 'AND gameplatform = \'html5\' ';
	}

	$gamelist_sql_tmp = 'SELECT *, cg.id AS gamelist_id, cg.display_name AS game_display_name FROM casino_gameslist cg JOIN casino_list cl ON cg.casino_id = cl.casinoid WHERE cg.open = 1 ' . $casino_sql . $gameplatform_sql . $query_str;
	$gamelist_count_sql = $gamelist_sql_tmp . ';';
	$gamelist_count = ceil(runSQL($gamelist_count_sql) / $current_per_size);

	$gamelist_sql = $gamelist_sql_tmp . ' ORDER BY
    CASE
      WHEN custom_order <> 0 AND casino_order <> 0 THEN 0
      WHEN custom_order = 0 AND casino_order <> 0 THEN 1
      WHEN custom_order <> 0 AND casino_order = 0 THEN 2
      WHEN custom_order = 0 AND casino_order = 0 THEN 3
    END,
    casino_order, custom_order, gamelist_id DESC OFFSET ' . $current_page_no . ' LIMIT ' . $current_per_size . ' ;';

	$gamelist_result = runSQLall($gamelist_sql, $debug, 'r');

	$gameitem = queryresult2array($gamelist_result);

	$return_json_arr = array('total' => $gamelist_count,
		'gameitems' => $gameitem);

	echo json_encode($return_json_arr);
} elseif ($action == 'gametable_mini') {
	$query_str = '';
	$hot_query = array();
	// 取得是否隨機輸出list
	$order_sql = (isset($_GET['rnd']) AND filter_var($_GET['rnd'], FILTER_VALIDATE_INT) AND $_GET['rnd'] == '1') ? ' ORDER BY random()' : ' ORDER BY
    CASE
      WHEN custom_order <> 0 AND casino_order <> 0 THEN 0
      WHEN custom_order = 0 AND casino_order <> 0 THEN 1
      WHEN custom_order <> 0 AND casino_order = 0 THEN 2
      WHEN custom_order = 0 AND casino_order = 0 THEN 3
    END,
  casino_order, custom_order, gamelist_id DESC ';
	if (isset($_POST) AND filter_var_array($_POST, FILTER_SANITIZE_STRING)) {
		if (isset($_POST['cid']) AND $_POST['cid'] != '') {
			$query_arr = filter_var($_POST['cid'], FILTER_SANITIZE_STRING);
			$query_str = $query_str . ' AND casino_id=\'' . $query_arr . '\' ';
		}
		if (isset($_POST['ct']) AND $_POST['ct'] != '') {
			$query_arr = filter_var($_POST['category'], FILTER_SANITIZE_STRING);
			$gametype_chk = preg_split('/_/', $query_arr[0]);
			if (count($gametype_chk) > 1) {
				$query_category = $gametype_chk[0];
				//$query_str = $query_str.' AND category=\''.$query_category.'\'';
				$sub_query_arr = array();
				foreach ($query_arr as $key => $val) {
					$sub_query_arr[] = str_replace($query_category . '_', '', $val);
				}
				$query_str = $query_str . ' AND ' . query_str($sub_query_arr, 'LOWER(gametype)');
			} else {
				$query_str = $query_str . ' AND ' . query_str($query_arr, 'category');
			}
		}
		if (isset($_POST['subct']) AND $_POST['subct'] != '') {
			$query_arr = filter_var_array($_POST['subct'], FILTER_SANITIZE_STRING);
			$query_str = $query_str . ' AND ' . query_str($query_arr, 'sub_category');
		}
		if (isset($_POST['mct']) AND $_POST['mct'] != '' AND $_POST['mct'] != 'game') {
			$query_arr = filter_var($_POST['mct'], FILTER_SANITIZE_STRING);
			if ($query_arr == 'hot') {
				$query_str = $query_str . ' AND marketing_strategy->>\'hotgame\' = \'1\' ';
				foreach ($gamelobby_setting['main_category_info'] as $mctid => $mct_arr) {
					if ($mct_arr['open'] == 1) {
						$hot_query[$mctid] = ' AND marketing_strategy->>\'mct\' = \'' . $mctid . '\'';
					}
				}
			} else {
				$query_str = $query_str . ' AND category ILIKE \'%' . $query_arr . '%\'';
			}
		// } else {
		// 	$query_str .= (isset($_POST['cid']) AND $_POST['cid'] != 'IG' AND $_POST['cid'] != 'MEGA' AND $_POST['cid'] != 'RG') ? ' AND category NOT IN  (\'Live\',\'Lottery\',\'Sport\')' : '';
		}
	}


	// 判斷客戶端設備，如果不是電腦，就只顯示HTML5的
	$gameplatform_sql = '';
	if (isset($_SESSION['site_mode']) AND $_SESSION['site_mode'] != 'desktop') {
		$gameplatform_sql = ' AND gameplatform = \'html5\' ';
	}

	$gamelist_mini_sql_tmp = 'SELECT *,cg.id AS gamelist_id, cg.display_name AS game_display_name FROM casino_gameslist cg JOIN casino_list cl ON cg.casino_id = cl.casinoid WHERE cg.open = 1 ' . $casino_sql . $gameplatform_sql . $query_str;
	$gamelist_mini_count_sql = $gamelist_mini_sql_tmp . ';';
	$gamelist_mini_count = ceil(runSQL($gamelist_mini_count_sql) / $current_per_size);

	if (count($hot_query) > 0) {
		$current_per_size = $current_per_size / count($hot_query);
		$gameitem = array();
		foreach ($hot_query as $hot_queryid => $hot_querydata) {
			$gamelist_mini_sql = $gamelist_mini_sql_tmp . $hot_querydata . $order_sql . ' OFFSET ' . $current_page_no . ' LIMIT ' . $current_per_size . ' ;';
			$gamelist_mini_result = runSQLall($gamelist_mini_sql, $debug, 'r');
			$gameitem[$hot_queryid] = queryresult2array($gamelist_mini_result);
		}
	} else {
		$gamelist_mini_sql = $gamelist_mini_sql_tmp . $order_sql . ' OFFSET ' . $current_page_no . ' LIMIT ' . $current_per_size . ' ;';
		$gamelist_mini_result = runSQLall($gamelist_mini_sql, $debug, 'r');
		$gameitem = queryresult2array($gamelist_mini_result);
	}

	$return_json_arr = array('total' => $gamelist_mini_count,
		'gameitems' => $gameitem);

	echo json_encode($return_json_arr);
} elseif ($action == 'myfavtable') {
	if (isset($_SESSION['member'])) {
		$memberid = $_SESSION['member']->id;
		$query_str = '';
		if (isset($_POST) AND filter_var_array($_POST, FILTER_SANITIZE_STRING)) {
			if ($_POST['casino'][0] != 'allcasino') {
				$query_arr = filter_var_array($_POST['casino'], FILTER_SANITIZE_STRING);
				$query_str = $query_str . ' AND ' . query_str($query_arr, 'rmg.casino_id');
			}
			if ($_POST['gametype'][0] != 'allgametype') {
				$query_arr = filter_var_array($_POST['gametype'], FILTER_SANITIZE_STRING);
				$query_str = $query_str . ' AND ' . query_str($query_arr, 'rmg.category');
			}
			if ($_POST['searchct'] == '' OR $_POST['searchct'] == 'game') {
				$query_str = $query_str . ' AND ( rmg."marketing_strategy"->>\'mct\' NOT IN (\'Live\',\'Lottery\',\'Sport\') AND rmg.category NOT IN (\'Live\',\'Lottery\',\'Sport\') )';
			} else {
				$query_arr = filter_var($_POST['searchct'], FILTER_SANITIZE_STRING);
				$query_str = $query_str . ' AND rmg."marketing_strategy"->>\'mct\' = \'' . $query_arr . '\'';
			}
		}

		// 判斷客戶端設備，如果不是電腦，就只顯示HTML5的
		$gameplatform_sql = '';
		if (isset($_SESSION['site_mode']) AND $_SESSION['site_mode'] != 'desktop') {
			$gameplatform_sql = 'AND rmg.gameplatform = \'html5\' ';
		}
		$casino_sql = str_replace('casino_id', 'rmg.casino_id', $casino_sql);

		$gamelist_sql_tmp = 'SELECT *,rmg.gameid AS gamelist_id, cg.display_name AS game_display_name FROM "root_member_gamelist" rmg INNER JOIN casino_gameslist cg ON rmg.gameid = cg.id AND rmg.casino_id=cg.casino_id WHERE rmg."open" = 1 AND memberid = ' . $memberid . ' ' . $gameplatform_sql . $casino_sql . $query_str;
		$gamelist_count_sql = $gamelist_sql_tmp . ';';
		// echo $gamelist_count_sql;
		$gamelist_count = ceil(runSQL($gamelist_count_sql) / $current_per_size);

		$gamelist_sql = $gamelist_sql_tmp . ' ORDER BY rmg.gamename ASC  OFFSET ' . $current_page_no . ' LIMIT ' . $current_per_size . ' ;';
		// echo $gamelist_sql;
		$gamelist_result = runSQLall($gamelist_sql, 0, 'r');
		//var_dump($gamelist_result);

		if ($gamelist_result[0] > 0) {
			$gameitem = queryresult2array($gamelist_result, '1');
			$return_json_arr = array('total' => $gamelist_count,
				'gameitems' => $gameitem);
		} else {
			$mct_str = (isset($tr['menu_' . strtolower($query_arr)])) ? $tr['menu_' . strtolower($query_arr)] : '';
			$return_json_arr = array('total' => '0', 'logger' => str_replace('%s', $mct_str, $tr['MyFav Not Yet added']));
		}
	} else {
		$return_json_arr = array('total' => '0', 'logger' => $tr['please login before continue']);
	}
	echo json_encode($return_json_arr);
} elseif ($action == 'hotgamestable') {
	$query_str = '';
	if (isset($_POST) AND filter_var_array($_POST, FILTER_SANITIZE_STRING)) {
		if ($_POST['casino'][0] != 'allcasino') {
			$query_arr = filter_var_array($_POST['casino'], FILTER_SANITIZE_STRING);
			$query_str = $query_str . ' AND ' . query_str($query_arr, 'casino_id');
		}
		if ($_POST['gametype'][0] != 'allgametype') {
			$query_arr = filter_var_array($_POST['gametype'], FILTER_SANITIZE_STRING);
			$query_str = $query_str . ' AND ' . query_str($query_arr, 'category');
		}
		if ($_POST['searchct'] == '') {
			$query_str = $query_str . ' AND ( "marketing_strategy"->>\'mct\' = \'game\' OR category NOT IN  (\'Live\',\'Lottery\',\'Sport\') )';
		} else {
			$query_arr = filter_var($_POST['searchct'], FILTER_SANITIZE_STRING);
			$query_str = $query_str . ' AND "marketing_strategy"->>\'mct\' = \'' . $query_arr . '\'';
		}
	}

	// 判斷客戶端設備，如果不是電腦，就只顯示HTML5的
	$gameplatform_sql = '';
	if (isset($_SESSION['site_mode']) AND $_SESSION['site_mode'] != 'desktop') {
		$gameplatform_sql = 'AND gameplatform = \'html5\' ';
	}

	$gamelist_sql_tmp = 'SELECT *,cg.id AS gamelist_id, cg.display_name AS game_display_name FROM casino_gameslist cg JOIN casino_list cl ON cg.casino_id = cl.casinoid WHERE cg.open = \'1\' AND marketing_strategy->>\'hotgame\' = \'1\' ' . $gameplatform_sql . $casino_sql . $query_str;
	$gamelist_count_sql = $gamelist_sql_tmp . ';';
	// echo $gamelist_count_sql.'<br/>';
	$gamelist_count = ceil(runSQL($gamelist_count_sql) / $current_per_size);

	$gamelist_sql = $gamelist_sql_tmp . ' ORDER BY custom_order DESC,casino_order DESC,gamename ASC  OFFSET ' . $current_page_no . ' LIMIT ' . $current_per_size . ' ;';
	// echo $gamelist_sql.'<br/>';
	$gamelist_result = runSQLall($gamelist_sql, 0, 'r');

	$gameitem = queryresult2array($gamelist_result);

	$return_json_arr = array('total' => $gamelist_count,
		'gameitems' => $gameitem);

	echo json_encode($return_json_arr);
} elseif ($action == 'searchgame') {
	$query_str = '';
	if (isset($_POST) AND filter_var_array($_POST, FILTER_SANITIZE_STRING)) {
		$query_arr = preg_replace('/([^A-Za-z0-9\p{Han}])/ui', '', urldecode($_POST['search']));
		$query_str = ($query_arr != '') ? $query_str . ' AND ( gamename ILIKE \'%' . $query_arr . '%\' OR gamename_cn ILIKE \'%' . $query_arr . '%\' OR category ILIKE \'%' . $query_arr . '%\' OR marketing_strategy->>\'ename\' ILIKE \'%' . $query_arr . '%\' OR marketing_strategy->>\'cname\' ILIKE \'%' . $query_arr . '%\' )' : '';
		if ($_POST['searchct'] == '') {
			$query_str = $query_str . ' AND category NOT IN  (\'Live\',\'Lottery\',\'Sport\')';
		} else {
			$query_arr = filter_var($_POST['searchct'], FILTER_SANITIZE_STRING);
			// 電子遊戲包含捕魚及棋牌
			if ($query_arr == 'game') {
				$query_str = $query_str . ' AND ( "marketing_strategy"->>\'mct\' NOT IN (\'Live\',\'Lottery\',\'Sport\'))';
			} else {
				$query_str = $query_str . ' AND ( "marketing_strategy"->>\'mct\' ILIKE \'%' . $query_arr . '%\')';
			}
		}
	}

	// 判斷客戶端設備，如果不是電腦，就只顯示HTML5的
	$gameplatform_sql = '';
	if (isset($_SESSION['site_mode']) AND $_SESSION['site_mode'] != 'desktop') {
		$gameplatform_sql = 'AND gameplatform = \'html5\' ';
	}

	$gamelist_sql_tmp = 'SELECT *,cg.id AS gamelist_id, cg.display_name AS game_display_name FROM casino_gameslist cg JOIN casino_list cl ON cg.casino_id = cl.casinoid WHERE cg.open = 1 AND cl.open = 1' . $gameplatform_sql . $query_str;
	$gamelist_count_sql = $gamelist_sql_tmp . ';';
	$gamelist_count = ceil(runSQL($gamelist_count_sql) / $current_per_size);

	$gamelist_sql = $gamelist_sql_tmp . ' ORDER BY custom_order DESC,casino_order DESC,gamename ASC  OFFSET ' . $current_page_no . ' LIMIT ' . $current_per_size . ' ;';
	$gamelist_result = runSQLall($gamelist_sql, 0, 'r');

	$gameitem = queryresult2array($gamelist_result);
	$return_json_arr = array('total' => $gamelist_count, 'gameitems' => $gameitem);
	$return_json_arr = array('total' => $gamelist_count, 'gameitems' => $gameitem);

	echo json_encode($return_json_arr);
} elseif ($action == 'addmyfav') {
	if (isset($_SESSION['member'])) {
		$memberid = $_SESSION['member']->id;
		if (isset($_POST) AND filter_var_array($_POST, FILTER_SANITIZE_STRING)) {
			$gameid = filter_var($_POST['gameid'], FILTER_SANITIZE_STRING);
			$casinoname = filter_var($_POST['casinoname'], FILTER_SANITIZE_STRING);
			$gameplatform = filter_var($_POST['gameplatform'], FILTER_SANITIZE_STRING);
		}

		$myfav_check_sql = 'SELECT * FROM root_member_gamelist WHERE "memberid"=\'' . $memberid . '\' AND "gameid"=\'' . $gameid . '\' AND "casino_id"=\'' . $casinoname . '\' AND "gameplatform"=\'' . $gameplatform . '\' ORDER BY category;';
		//echo $myfav_check_sql;
		$myfav_check_result_count = runSQLall($myfav_check_sql, 0, 'r');
		//var_dump($myfav_check_result_count);

		if ($myfav_check_result_count['0'] <= '1') {
			$gameinfo_query_sql = 'SELECT * FROM casino_gameslist WHERE "open" = 1 AND "id"=\'' . $gameid . '\' ORDER BY category;';
			//echo $gameinfo_query_sql;
			$gameinfo_query_result = runSQLall($gameinfo_query_sql, 0, 'r');
			//var_dump($gameinfo_query_result);
			if ($gameinfo_query_result['0'] == '1' AND $myfav_check_result_count['0'] == '0') {
				$addmyfav_query_sql = 'INSERT INTO root_member_gamelist ("memberid","casino_id","category","gamename","gameid","gamename_cn","imagefilename","gameplatform","marketing_strategy")
        VALUES (\'' . $memberid . '\',\'' . $casinoname . '\',\'' . $gameinfo_query_result['1']->category . '\',\'' . preg_replace('/([\'])/ui', '\'\'', $gameinfo_query_result['1']->gamename) . '\',\'' . $gameinfo_query_result['1']->id . '\',\'' . preg_replace('/([\'])/ui', '\'\'', $gameinfo_query_result['1']->gamename_cn) . '\',\'' . $gameinfo_query_result['1']->imagefilename . '\'
        ,\'' . $gameplatform . '\',\'' . $gameinfo_query_result['1']->marketing_strategy . '\');';
				//echo $addmyfav_query_sql;
				$addmyfav_query_result = runSQL($addmyfav_query_sql);
				//var_dump($addmyfav_query_result);
				if ($addmyfav_query_result == '1') {
					$logger = $tr['MyFav added'];
				}
			} elseif ($gameinfo_query_result['0'] == '1' AND $myfav_check_result_count['0'] == '1') {
				$addmyfav_query_sql = 'UPDATE root_member_gamelist SET "open"=\'1\' WHERE "memberid"=\'' . $memberid . '\' AND "gameid"=\'' . $gameid . '\' AND "casino_id"=\'' . $casinoname . '\' AND "gameplatform"=\'' . $gameplatform . '\';';
				//echo $addmyfav_query_sql;
				$addmyfav_query_result = runSQL($addmyfav_query_sql);
				//var_dump($addmyfav_query_result);
				if ($addmyfav_query_result == '1') {
					$logger = $tr['MyFav added'];
				}
			} else {
				$logger = $tr['MyFav added'];
			}
		}
	} else {
		$logger = $tr['please login before continue'];
	}
	// 透過json 返回新選單及成功加入通知
	$return_json_arr = array('logger' => $logger);

	if ($newlogin == 1) {
		$wait_text = '<p align="center">' . $logger . '<br><button type="button" onclick="opener.location.reload(); self.close();">關閉視窗</button><p>';
		echo $wait_text;
	} else {
		echo json_encode($return_json_arr);
	}
} elseif ($action == 'delmyfav') {
	if (isset($_SESSION['member'])) {
		$memberid = $_SESSION['member']->id;
		if (isset($_POST) AND filter_var_array($_POST, FILTER_SANITIZE_STRING)) {
			$gameid = filter_var($_POST['gameid'], FILTER_SANITIZE_STRING);
			$category = filter_var($_POST['gametype'], FILTER_SANITIZE_STRING);
			$casinoname = filter_var($_POST['casinoname'], FILTER_SANITIZE_STRING);
			$gameplatform = filter_var($_POST['gameplatform'], FILTER_SANITIZE_STRING);
		}

		$delmyfav_check_sql = 'SELECT * FROM root_member_gamelist WHERE "memberid"=\'' . $memberid . '\' AND "gameid"=\'' . $gameid . '\' AND "casino_id"=\'' . $casinoname . '\' AND "gameplatform"=\'' . $gameplatform . '\';';
		//echo $delmyfav_check_sql;
		$delmyfav_check_result_count = runSQLall($delmyfav_check_sql, 0, 'r');
		//var_dump($delmyfav_check_result_count);

		if ($delmyfav_check_result_count['0'] == '1') {
			$delmyfav_query_sql = 'UPDATE root_member_gamelist SET "open"=\'0\' WHERE "memberid"=\'' . $memberid . '\' AND "gameid"=\'' . $gameid . '\' AND "casino_id"=\'' . $casinoname . '\' AND "gameplatform"=\'' . $gameplatform . '\';';
			//echo $delmyfav_query_sql;
			$delmyfav_query_result = runSQL($delmyfav_query_sql);
			//var_dump($delmyfav_query_result);
			if ($delmyfav_query_result == '1') {
				$logger = $tr['MyFav deleteed'];
			}
		} else {
			$logger = $tr['MyFav deleteed'];
		}

		// 透過json 返回新選單及成功加入通知
		$return_json_arr = array('logger' => $logger);
	} else {
		$return_json_arr = array('logger' => $tr['please login before continue']);
	}

	echo json_encode($return_json_arr);
} elseif ($action == 'gotogame') {
	if($memberstatus == '2'){
		// 在業務DEMO站台上
		if ($newlogin == 1) {
			// ----------------------------------------------------------------------------
			$topic = '<br><p class="no_available">'.$tr['Your wallet has been frozen, please contact customer'] . '</p>';
			notify_html($topic);
		} else {
			$url = array('url' => $_SERVER['PHP_SELF'] . '?a=walletlock&');
			echo json_encode($url);
		}
	}elseif ($config['businessDemo'] == 0 OR in_array($_GET['casinoname'], $config['businessDemo_skipCasino'])) {
		// 檢查是否在業務DEMO站台上，如是則引導至業務DEMO頁面
		// 檢查會員權限
		if (isset($_SESSION['member']) AND ($_SESSION['member']->therole == 'A' OR $_SESSION['member']->therole == 'M')) {
			// 檢查後台是否有在操作會員錢包轉出入娛樂城，如有則不給進GAME
			$agent_chk = agent_walletscontrol_check();
			if (isset($agent_chk) AND $agent_chk == '1') {
				$logger = $tr['Cs Fixing Wallets Problem'];
				echo json_encode(array('logger' => $logger));
				die();
			} else {
				// 檢查會員是否存在
				$member_id = $_SESSION['member']->id;
				$member_sql = "SELECT * FROM root_member JOIN root_member_wallets ON root_member.id=root_member_wallets.id WHERE root_member.id = '$member_id' AND root_member.status = '1';";
				$member = runSQLall($member_sql, $debug, 'r');
				if ($member[0] == 1) {
					// 會員存在
					// 檢查是否已經有人按了 game 還在等待處理, 將 game code 存在 session 內
					// 判斷剛剛同一個人有沒有很緊張，一直按下很多個 gamecode 按鈕
					// 使用 session 來判斷
					if (isset($_SESSION['running_gamecode']) && !isset($_GET['closed'])) {
						$gamename_sql = 'SELECT display_name FROM casino_gameslist WHERE id=\'' . $_SESSION['running_gamecode'] . '\';';
						$gamename_result = runSQLall($gamename_sql, $debug, 'r');
						$gamename_show = translateSpecificChar("'", getDisplayNameByLanguage($gamename_result[1]->display_name, $_SESSION['lang']), 1);
						$logger = str_replace('%s', $gamename_show, $tr['Game window opened']);
						$return_json_arr = array('logger' => $logger);
					} else {
						if (isset($_GET['closed']) && $_GET['closed'] == 1) {
							unset($_SESSION['running_gamecode']);
						}

						// 沒有其他 gamecode 正在執行, 所以檢查將要去的CASINO是否與原本在的CASINO一樣
						// 如果不同CASINO就先把原本在的CASINO上的餘額取回
						if (!isset($casinoname) AND isset($_GET['casinoname'])) {
							$casinoname = filter_var($_GET['casinoname'], FILTER_SANITIZE_STRING);
						}
						$casino_lock = $member[1]->gtoken_lock;

						if (isset($casino_lock) AND $casino_lock != '' AND $casino_lock != $casinoname) {
							// 將前往不同casino，需先將錢取回再轉到該casino的action頁面
							$casinoid = $casino_lock;
							// 匯入娛樂城函式庫
							require_once dirname(__FILE__) . getCasinoLib($casino_lock);

							if (!isset($_SESSION['wallet_transfer'])) {
								// 旗標,轉餘額動作執行時，透過這個旗標控制同一個使用者，不能有第二個執行緒進入。除非已經清除這個旗標。
								$_SESSION['wallet_transfer'] = 'Account:' . $_SESSION['member']->account . ' run in ' . getCasinoRetrieveFunc($casino_lock);
								// 使用session時，若先前的頁面尚未執行完畢，預設session會被鎖住
								// 此時，若執行另外一個也有使用session的頁面，則須等前一個頁面執行完畢，才能再執行

								// 取回娛樂城的餘額
								$member_id = $_SESSION['member']->id;
								$rr = getCasinoRetrieveFunc($casino_lock)($member_id, $debug);

								//取回餘額成功
								if ($rr['code'] == 1) {
									$return_json_arr = array('url' => getCasinoActionUrl($casinoname));
								} elseif ($rr['code'] == 500) {
									$logger = $tr['withdraw failed from casino'];
									$return_json_arr = array('logger' => $rr['messages']);
								} else {
									$logger = $rr['messages'];
									$return_json_arr = array('logger' => $rr['messages']);
								}
								unset($_SESSION['wallet_transfer']);

								if ($debug == 1) {
									echo '<p align="center">' . $rr['messages'] . '</p>';
								}
							} else {
								// 取回餘額中
								$logger = $tr['wallets under processing'];
								$return_json_arr = array('logger' => $logger);
							}
						} else {
							// 之前沒去其他casino或是要去的game是同一casino的，直接轉到該casino的action頁面
							$return_json_arr = array('url' => getCasinoActionUrl($casinoname));
						}
					}
				} else {
					// 會員不存在
					$logger = $tr['Account failed'];
					$return_json_arr = array('logger' => $logger);
				}
			}
		} else {
			// 未登入
			$logger = $tr['please login before continue'];
			$return_json_arr = array('logger' => $logger);
		}

		if ($newlogin == 1) {
			// 有登入
			if (isset($logger)) {
				// 錢包餘額處理完成
				$wait_text = '
					<div style="
		                background-color:black;
		                height: 100vh;
		                display: flex;
		                justify-content: center;
		                align-items: center;
		                overflow: hidden;">
		                <img src="' . $cdnrooturl . 'casinologo/' . $casinoname . '.png" alt="' . $casinoname . '">
		                <img src="' . $cdnrooturl . 'loading_ball.gif" alt="' . $casinoname . '">
		            </div>
		            <script src="' . $cdnfullurl_js . 'jquery/jquery.min.js"></script>
		            <script type="text/javascript">
		                $(document).ready(function() {
		                    opener.location.reload();
		                    window.close();
		                });
		            </script>';
				echo $wait_text . $logger;
			} else {
				$orangepage_reload = ($mobile == 1) ? '' : 'opener.location.reload();';
				$url = getCasinoActionUrl($casinoname) . '?a=goto_game&casinoname=' . $casinoname . '&gamecode=' . $gamecode . '&gameplatform=' . $gameplatform;
				if ($debug == 0) {
					$wait_text = '
						<div style="
			                background-color:black;
			                height: 100vh;
			                display: flex;
			                justify-content: center;
			                align-items: center;
			                overflow: hidden;
			            ">
			                <img src="' . $cdnrooturl . 'casinologo/' . $casinoname . '.png" alt="' . $casinoname . '">
			                <img src="' . $cdnrooturl . 'loading_ball.gif" alt="' . $casinoname . '">
			            </div>
			            <script src="' . $cdnfullurl_js . 'jquery/jquery.min.js"></script>
			            <script type="text/javascript">
			                $(document).ready(function() {
			                    ' . $orangepage_reload . ';
			                    setTimeout(function(){window.location.href="' . $url . '"},1000);
			                });
			            </script>';
					$wait_text = $wait_text . '<div style="width: 100%;	height: 100vh; display: flex; justify-content: center; align-items: center; overflow: hidden;">執行中，請勿關閉視窗.<img src="' . $cdnrooturl . 'loading_balls.gif"></div>';
				} else {
					$wait_text = $url;
				}
				echo $wait_text;
			}
		} else {
			// 未登入
			echo json_encode($return_json_arr);
		}
	} else {
		// 在業務DEMO站台上
		if ($newlogin == 1) {
			if (isset($tr[$_GET['casinoname'] . ' Casino'])) {
				$cid = $tr[$_GET['casinoname'] . ' Casino'];
			} else {
				$cid = $_GET['casinoname'] . ' Casino';
			}
			// ----------------------------------------------------------------------------
			$topic = '<br><center><h1>' . str_replace('%s', $cid, $tr['businessDemoNotify']) . '</h1></center>';
			notify_html($topic);
		} else {
			$url = array('url' => $_SERVER['PHP_SELF'] . '?a=businessDemo&cid=' . $_GET['casinoname'] . '&');
			echo json_encode($url);
		}
	}
} elseif ($action == 'Transferout_GCASH_GTOKEN_balance' AND isset($_SESSION['member'])) {
	// 當發現會員沒有錢的時候，就進入這個自動儲值轉換的程序，將 GCASH 依據設定值，自動加值到 GTOKEN 上面
	$topic = $tr['gcash2gtoken'];
	echo "<br>" . $topic;
	$trans_result = Transferout_GCASH_GTOKEN_balance($_SESSION['member']->id);

} elseif ($action == 'Retrieve_Casino_balance' AND isset($_SESSION['member']) AND ($_SESSION['member']->therole == 'A' OR $_SESSION['member']->therole == 'M' OR $_SESSION['member']->therole == 'R')) {
	// 取回 Casino 的餘額，並檢查上次離開時和目前的差額得出派彩金額
	// gamelobby_action.php?a=Retrieve_Casino_balance
	// 檢查後台是否有在操作會員錢包轉出入娛樂城，如有則不給進GAME
	$agent_chk = agent_walletscontrol_check();
	if (isset($agent_chk) AND $agent_chk == '1') {
		die($tr['Cs Fixing Wallets Problem']);
	}

	// 搭配 wallets.php 使用 , 各娛樂城取回錢幣都要設定這個 session 避免重複執行
	if (!isset($_SESSION['wallet_transfer'])) {
		// 旗標，轉餘額動作執行時，透過這個旗標控制同一個使用者，不能有第二個執行緒進入。除非已經清除這個旗標
		$_SESSION['wallet_transfer'] = 'Account:' . $_SESSION['member']->account . ' run in ' . 'Retrieve_Casino_balance';
		// 使用session時，若先前的頁面尚未執行完畢，預設session會被鎖住。
		// 此時，若執行另外一個也有使用session的頁面，則須等前一個頁面執行完畢，才能再執行。

		// 取得目前gtoken_lock的值
		$member_id = $_SESSION['member']->id;
		$member_sql = "SELECT gcash_balance,gtoken_balance,gtoken_lock,casino_accounts FROM root_member_wallets WHERE id = '$member_id';";

		$member = runSQLall($member_sql, $debug, 'r');
		$casino_lock = $member[1]->gtoken_lock;
		$casinoid = $member[1]->gtoken_lock;
		$casinoinfo = json_decode($member[1]->casino_accounts, TRUE);
		$casino_balance = $casinoinfo[$casinoid]['balance'];
		$gcash_balance = $member[1]->gcash_balance;

		// 讀取對應 casino 的 lib
		require_once dirname(__FILE__) . getCasinoLib($casino_lock);

		// 取回娛樂城的餘額
		$rr = getCasinoRetrieveFunc($casino_lock)($member_id, $debug);

		//取回娛樂城餘額成功
		if ($rr['code'] == 1) {
			$_SESSION['member']->gtoken_lock = '';
			$return = [];
			$return['logger'] = $tr['payout recived'] . $rr['balance'];
			$return['gtoken_b'] = '$' . (string)($_SESSION['member']->gtoken_balance + $casino_balance + $rr['balance']);
			$return['total_b'] = '$' . (string)($gcash_balance + $return['gtoken_b'] + $_SESSION['member']->gtoken_balance + $casino_balance + $rr['balance']);
			$return['gtoken_b_m'] = '<span class="label label-success" title="">' . $tr['account balance'] . ' ' . number_format($_SESSION['member']->gcash_balance + $_SESSION['member']->gtoken_balance + $casino_balance + $rr['balance'], 2) . '</span>';
		} elseif ($rr['code'] == 500) {
			$return = $tr['withdraw failed from casino'] . '<script>setTimeout(\'window.location.reload()\',10000);</script>';
		} else {
			$return = str_replace('%s', $rr['code'], $tr['withdraw failed contact CS']);
		}

	} else {
		$return = $tr['wallets under processing'];
	}

	echo json_encode($return);

} elseif ($action == 'businessDemo' AND isset($_GET['cid'])) {
// ----------------------------------------------------------------------------
// 業務demo頁面
// ----------------------------------------------------------------------------
	if (isset($tr[$_GET['cid'] . ' Casino'])) {
		$cid = $tr[$_GET['cid'] . ' Casino'];
	} else {
		$cid = $_GET['cid'] . ' Casino';
	}
	// ----------------------------------------------------------------------------
	$topic = '<br><center><h1>' . str_replace('%s', $cid, $tr['businessDemoNotify']) . '</h1></center>';
	notify_html($topic);
// ----------------------------------------------------------------------------
} elseif ($action == 'walletlock') {
// ----------------------------------------------------------------------------
// 錢包鎖定頁面
// ----------------------------------------------------------------------------
	$topic = '<br><p class="no_available">'.$tr['Your wallet has been frozen, please contact customer'] . '</p>';
	notify_html($topic);
// ----------------------------------------------------------------------------
} elseif ($action == 'getReviewGames') { // 取得瀏覽紀錄
	$account = '';
	if (isset($_SESSION['member'])) {
		$account = $_SESSION['member']->account;
	}

	$accountSql = 'SELECT * FROM root_member WHERE "account" = \'' . trim($account) . '\';';
	$accountResult = runSQLall($accountSql, $debug);
	// 檢查會員帳號是否存在
	$reviewGames = array();
	if ($accountResult[0] > 0 and !is_null($accountResult[1]->review_games)) {
		$reviewGames = json_decode($accountResult[1]->review_games, true);
	}

	$order2code = array();
	if (count($reviewGames) > 0) {
		$j = 0;
		$sql = 'SELECT *, cg.id AS gamelist_id, cg.display_name AS game_display_name FROM casino_gameslist cg JOIN casino_list cl ON cg.casino_id = cl.casinoid WHERE cg.open = 1 AND cg.id IN(';
		for ($i = count($reviewGames); $i > 0; $i--) {
			$record = $reviewGames[$i];
			// 去除重複點選
			if (!key_exists($record, $order2code) and isGameOpen($record)) {
				$j++;
				$order2code[$record] = $j;
			}
			if ($i == 1) {
				$sqlTmp = $record . ')';
			} else {
				$sqlTmp = $record . ',';
			}
			$sql .= $sqlTmp;
		}
		$result = runSQLall($sql, $debug);

		// 排序
		arsort($order2code);
		$order = array_keys($order2code);
		$reviews = queryresult2array($result);
		$reviewsJson = array('reviews' => $reviews, 'sort' => $order);
	} else {
		$reviewsJson = array('reviews' => array(), 'sort' => array());
	}
	echo json_encode($reviewsJson);
} elseif ($action == 'setReviewGame') {
	$account = '';
	if (isset($_SESSION['member'])) {
		$account = $_SESSION['member']->account;
	}

	$accountSql = 'SELECT "review_games" FROM root_member WHERE "account" = \'' . trim($account) . '\';';
	$accountResult = runSQLall($accountSql, $debug);

	// 檢查會員帳號是否存在
	if ($accountResult[0] > 0) {
		$reviewGames = json_decode($accountResult[1]->review_games, true);
	} else {
		$logger = $tr['Account failed'];
		echo json_encode(['logger' => $logger]);
	}

	$code = isset($_POST['code']) ? filter_var($_POST['code'], FILTER_SANITIZE_STRING) : '';

	$counts = count($reviewGames);
	$repeat = false;
	$newReviewGames = [];
	if ($counts == $reviewCounts) {
		for ($i = 1; $i <= $reviewCounts; $i++) {
			if ($code == $reviewGames[$i]) {
				$repeat = true;
				unset($reviewGames[$i]);
				$reviewGames[$counts + 1] = $code;
				$codes = array_values($reviewGames);
				for ($j = 0; $j < count($codes); $j++) {
					$reviewGames[$j + 1] = $codes[$j];
				}
				break;
			}
		}
		if (!$repeat) {
			for ($i = 1; $i <= $reviewCounts; $i++) {
				if ($i == $reviewCounts) {
					$newReviewGames[$i] = $code;
				} else {
					$newReviewGames[$i] = $reviewGames[$i + 1];
				}
			}
		}
	} else {
		for ($i = 1; $i <= $counts; $i++) {
			if ($code == $reviewGames[$i]) {
				$repeat = true;
				unset($reviewGames[$i]);
				$reviewGames[$counts + 1] = $code;
				$codes = array_values($reviewGames);
				for ($j = 0; $j < count($codes); $j++) {
					$newReviewGames[$j + 1] = $codes[$j];
				}
				break;
			}
		}
		if (!$repeat) {
			for ($i = 1; $i <= count($reviewGames) + 1; $i++) {
				if ($i == count($reviewGames) + 1) {
					$newReviewGames[$i] = $code;
				} else {
					$newReviewGames[$i] = $reviewGames[$i];
				}
			}
		}
	}
	// 寫入資料庫
	$newReviewSql = 'UPDATE "root_member" SET review_games = \'' . json_encode($newReviewGames) . '\' WHERE account = \'' . trim($account) . '\'';
	$newReviewResult = runSQL($newReviewSql, $debug);
	echo json_encode(array('save' => $newReviewResult));

} elseif ($action == 'recommendList') {
	$count = filter_var($_POST['recommend'], FILTER_SANITIZE_NUMBER_INT);
	// 判斷客戶端設備，如果不是電腦，就只顯示HTML5的
	$gameplatform_sql = '';
	if (isset($_SESSION['site_mode']) AND $_SESSION['site_mode'] != 'desktop') {
		$gameplatform_sql = 'AND gameplatform = \'html5\' ';
	}

	$gamelist_sql_tmp = 'SELECT *, cg.id AS gamelist_id, cg.display_name AS game_display_name FROM casino_gameslist cg JOIN casino_list cl ON cg.casino_id = cl.casinoid WHERE cg.open = 1 ' . $casino_sql . $gameplatform_sql;
	$gamelist_sql = $gamelist_sql_tmp . ' ORDER BY
    CASE
      WHEN custom_order <> 0 AND casino_order <> 0 THEN 1
      WHEN custom_order = 0 AND casino_order <> 0 THEN 2
      WHEN custom_order <> 0 AND casino_order = 0 THEN 3
      WHEN custom_order = 0 AND casino_order = 0 THEN 4
    END,
    casino_order, custom_order, gamelist_id DESC OFFSET 0 LIMIT ' . $count . ' ;';

	$gamelist_result = runSQLall($gamelist_sql, $debug, 'r');

	$gameitem = queryresult2array($gamelist_result);

	$return_json_arr = array('gameitems' => $gameitem, 'counts' => count($gameitem));

	echo json_encode($return_json_arr);
}
