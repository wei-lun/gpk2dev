<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 遊藝大廳
// File Name:	gamelobby.php
// Author:		Ian
// Related:
// Log:
// 2019.07.19 修改娛樂城排序顯示 Letter
// 2019.07.29 新增瀏覽紀錄、娛樂城遊戲排序、推薦遊戲 Letter
// 2020.02.20 修改娛樂城名稱顯示方法 Letter
// 2020.08.13 Bug #4430 SQL Injection_娛樂城DB欄位防護 Letter
//            1.增加變數過濾特殊字元
//            2.移除頁面 SQL 敘述
// ----------------------------------------------------------------------------


// 主機及資料庫設定
require_once dirname(__FILE__) . "/config.php";
// 支援多國語系
require_once dirname(__FILE__) . "/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) . "/lib.php";
// Casino類
require_once dirname(__FILE__) . "/Casino.php";
// casino 函式庫
require_once dirname(__FILE__) . "/casino_lib.php";
// gamelobby 函式庫
require_once dirname(__FILE__) . "/gamelobby_lib.php";

// var_dump($_SESSION);
// ----------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------

// -------------------------------------------------------------------------
// 本程式使用的 function
// -------------------------------------------------------------------------

$debug = 0;
global $gamelobby_setting;

// ------------------------------------------------
// 顯示 category 分類選單 function
// -------------------------------------------------
function games_category_item($maingame_category, $debug = 0)
{

	global $tr;
	$gamelobbyLib = new gamelobby_lib();

	// 選單子項目
	if ($maingame_category == 'game') {
		$id_head = '';
	} else {
		$id_head = $maingame_category . '_';
	}

	$menu_gametype_item_result = $gamelobbyLib->getMainCategoriesByOpenCasino($maingame_category, $debug);
	if ($menu_gametype_item_result[0] > 0) {
		$menu_gametype_item_list = '<label><span class="gt selected" id="allgametype">' . $tr['all'] . '</span></label>';
		for ($l = 1; $l <= $menu_gametype_item_result[0]; $l++) {
			// 翻譯
			$game_category = trim($menu_gametype_item_result[$l]->category);
			if (strlen($game_category) == 0) continue;
			if (isset($tr[$game_category]) AND $game_category != NULL) {
				$tr_category = $tr[$game_category];
			} else {
				$tr_category = $game_category;
			}
			$menu_gametype_item_list = $menu_gametype_item_list . '<label><span class="gt" id="' . $id_head . $game_category . '">' . $tr_category . '</span></label>';
		}
	} else {
		$menu_gametype_item_list = '';
	}

	return $menu_gametype_item_list;
}
// -------------------------------------------------
// 顯示 category 分類選單 - END
// -------------------------------------------------


// ------------------------------------------------
// 顯示 subcategory 分類選單 function
// -------------------------------------------------
function games_subcategory_item($maingame_category, $debug = 0)
{

	global $tr;
	$gamelobbyLib = new gamelobby_lib();

	$menu_gametype_item_list = 'var subitem = [];';

	// 選單子項目
	if ($maingame_category == 'game') {
		$gametype_item_result = $gamelobbyLib->getMainCategoriesByOpenGames($debug);
		for ($j = 1; $j <= $gametype_item_result[0]; $j++) {
			$maincategory = trim($gametype_item_result[$j]->category);
			if (strlen($maincategory) == 0) continue;
			$menu_gametype_item_result = $gamelobbyLib->getSubCategoriesByOpenGames($maincategory, $debug);
			if ($menu_gametype_item_result[0] > 1) {
				$menu_gametype_item_list = $menu_gametype_item_list . '
				subitem[\''. $gametype_item_result[$j]->category .'\'] = \'';
				$menu_gametype_item_list = $menu_gametype_item_list . '<label><span class="gt selected" id="allsubcategory">'. $tr['all'] .'</span></label>';
				for ($l = 1; $l <= $menu_gametype_item_result[0]; $l++) {
					// 翻譯
					$game_category = trim($menu_gametype_item_result[$l]->sub_category);
					if (strlen($game_category) == 0) continue;
					if ($game_category != NULL AND $game_category != '') {
						if (isset($tr[$game_category])) {
							$tr_category = $tr[$game_category];
						} else {
							$tr_category = $game_category;
						}
						$menu_gametype_item_list = $menu_gametype_item_list . '<label><span class="gt" id="'. $game_category .'">'. $tr_category .'</span></label>';
					}
				}
				$menu_gametype_item_list = $menu_gametype_item_list . '\';';
			}
		}
	} else {
		$gametype_item_result = $gamelobbyLib->getGameTypeByOpenGames($maingame_category, $debug);
		for ($j = 1; $j <= $gametype_item_result[0]; $j++) {
			$maingametype = trim($gametype_item_result[$j]->gametype);
			if (strlen($maingametype) == 0) continue;
			$menu_gametype_item_result = $gamelobbyLib->getSubCategoriesByGameType($maingametype);
			if ($menu_gametype_item_result[0] >= 1) {
				$menu_gametype_item_list = $menu_gametype_item_list . '
				subitem[\'' . $maingame_category . '_' . $maingametype . '\'] = \'';
				$menu_gametype_item_list = $menu_gametype_item_list . '<label><span class="gt selected" id="allsubcategory">'. $tr['all'] .'</span></label>';
				for ($l = 1; $l <= $menu_gametype_item_result[0]; $l++) {
					// 翻譯
					$game_category = trim($menu_gametype_item_result[$l]->sub_category);
					if (strlen($game_category) == 0) continue;
					if ($game_category != NULL AND $game_category != '') {
						if (isset($tr[$game_category])) {
							$tr_category = $tr[$game_category];
						} else {
							$tr_category = $game_category;
						}
						$menu_gametype_item_list = $menu_gametype_item_list . '<label><span class="gt" id="'. $game_category .'">'. $tr_category .'</span></label>';
					}
				}
				$menu_gametype_item_list = $menu_gametype_item_list . '\';';
			} else {
				$menu_gametype_item_list = '';
			}
		}
	}
	return $menu_gametype_item_list;
}

// -------------------------------------------------
// 顯示 category 分類選單 - END
// -------------------------------------------------


/**
 * 娛樂城自訂順序排序
 *
 * @param mixed $value1 陣列內數值
 * @param mixed $value2 陣列內數值
 *
 * @return int 比較結果，相等為 0，前數小於後數為 -1，大於為 1
 */
function casinoOrder($value1, $value2)
{
	if (intval($value1) == 0 and intval($value2) == 0) {
		return 0;
	} elseif (intval($value1) != 0 and intval($value2) == 0) {
		return -1;
	} elseif (intval($value1) == 0 and intval($value2) != 0) {
		return 1;
	} else {
		if (intval($value1) - intval($value2) < 0) {
			return -1;
		} elseif (intval($value1) - intval($value2) > 0) {
			return 1;
		} else {
			return 0;
		}
	}
}


/**
 *  轉換主分類到娛樂城反水分類
 *
 * @param mixed $mgc 主分類
 *
 * @return string 娛樂城反水分類
 */
function transMGCToPlatformList($mgc)
{
	$platform = '';
	// 設定主分類與娛樂城反水分類對應關係
	global $gamelobby_setting;
	$categories = $gamelobby_setting['main_category_info'];
	$mapping = [];
	foreach ($categories as $key => $value) {
		$mapping[$key] = $value['flatform'];
	}

	// 取得分類
	$mappingKeys = array_keys($categories);
	if (in_array($mgc, $mappingKeys)) {
		$platform = $mapping[$mgc];
	}

	return $platform;
}
// -------------------------------------------------------------------------
// END function lib
// -------------------------------------------------------------------------

// -------------------------------------------------------------------------
// GET / POST 傳值處理
// -------------------------------------------------------------------------
global $config;
global $cdnrooturl;
global $tr;
global $cdnfullurl_js;
$reviewCounts = 7;
$account = '';
$casinoLib = new casino_lib();
if (isset($_SESSION['member'])) {
	$account = $_SESSION['member']->account;
}

$gametype_selected = '';

if (isset($_GET['g'])) {
	// 填入 search box進行search
	$gametype_selected = urldecode($_GET['g']);
}

$maingame_category = (isset($_GET['mgc']) AND $_GET['mgc'] != '') ? filter_var(urldecode($_GET['mgc']), FILTER_SANITIZE_STRING) : 'game';
if (!in_array($maingame_category, array_keys($gamelobby_setting['main_category_info']))) {
	$maingame_category = 'game';
}
$maingame_sql = ' AND "marketing_strategy"->>\'mct\' = \'' . $maingame_category . '\'';

$gametype_display = '';
if ($maingame_category != 'game' AND $maingame_category != 'Lottery' AND $config['website_type'] == 'casino') {
	$gametype_display = ' style="display: none"';
}

$casino_selected = '';
if (isset($_GET['cn'])) {
	// 用來進入casino
	$casino_selected = urldecode($_GET['cn']);
}
$tab_selected = [];
$tab_selected['selected'] = 'gametable';
$tab_selected['all'] = 'active';
$tab_selected['hotgames'] = '';
$tab_selected['myfav'] = '';
if (isset($_GET['t']) AND ($_GET['t'] == 'hotgames' OR $_GET['t'] == 'myfav')) {
	// 設定進入的tab
	$tab_selected[$_GET['t']] = 'active';
	$tab_selected['all'] = '';
	$tab_selected['selected'] = $_GET['t'] . 'table';
}
// -------------------------------------------------------------------------
// GET / POST 傳值處理 END
// -------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
$function_title = $tr['gamelobby'];
// 擴充 head 內的 css or js
$extend_head = '';
// 放在結尾的 js
$extend_js = '';
// body 內的主要內容
$indexbody_content = '';
// 系統訊息選單
$messages = '';

// ------------------------------------------------
// 判斷是否已登入
// -------------------------------------------------

//登入後我的最愛才會出現
$myfavourite_pc = '';
$myfavourite_m = '';

// 登入和沒有登入的畫面不一樣ˋ，點擊後顯示的訊息不一樣。
// 只有登入後才作這些動作。
if (isset($_SESSION['member'])) {
	// 刪除除了當下的 session 以外，所有同使用者的 key
	Member_runRedisKeepOneUser();

	//登入後我的最愛才會出現
	$myfavourite_pc = '<li class="nav-item allcasinotab ' . $tab_selected['myfav'] . '" id="myfav" attr-table="myfav"><a class="nav-link"><span class="glyphicon glyphicon-heart"></span>' . $tr['MyFav'] . '</a></li>';
	$myfavourite_m = '<li class="nav-item allcasinotab ' . $tab_selected['myfav'] . '" id="myfav" attr-table="myfav"><a class="nav-link"><span class="glyphicon glyphicon-heart"></span>' . $tr['MyFav'] . '</a></li>';
	// Transferout_Casino_MG2_balance() and Retrieve_Casino_MG2_balance()
	// 執行完成後，把 $_SESSION['wallet_transfer'] 變數清除, 其他程式才可以進入。
	// 避免連續性呼叫 Retrieve_Casino_MG2_balance() lib , 需要到 home.php and gamelobby.php 清除變數才可以。
	if (isset($_SESSION['wallet_transfer'])) {
		unset($_SESSION['wallet_transfer']);
	}

	// 取消登入遊戲時設定的 session 變數, 阻止過多的重複登入(減少MG2 的故障機會)
	// 設定在 gamelobby_action.php?a=goto_mggame
	if (isset($_SESSION['running_gamecode'])) {
		unset($_SESSION['running_gamecode']);
	}
}

//篩選選單
if ($config['site_style'] == 'mobile') {
	$casino_tab = '<li class="nav-item allcasinotab ' . $tab_selected['all'] . '" id="allcasinotab" attr-table="game"><a class="nav-link"><span class="glyphicon glyphicon-th" aria-hidden="true"></span>' . $tr['all'] . '</a></li>
	<li class="nav-item allcasinotab ' . $tab_selected['hotgames'] . '" id="hotgames" attr-table="hotgames"><a class="nav-link"><span class="glyphicon glyphicon-fire"></span>' . $tr['hot'] . '</a></li>
	'.$myfavourite_m.'
	<li class="nav-item allcasinotab" id="gamesel" attr-table="gamesel"><a href="" class="nav-link" data-toggle="collapse" href="#collapsemenu" aria-expanded="false" aria-controls="collapsemenu"><i class="fas fa-filter"></i>' . $tr['Advanced'] . '</a></li>
	';
} else {
	$casino_tab = '<li class="nav-item allcasinotab ' . $tab_selected['all'] . '" id="allcasinotab" attr-table="game"><a class="nav-link"><span class="glyphicon glyphicon-th" aria-hidden="true"></span>' . $tr['all'] . '</a></li>
	<li class="nav-item allcasinotab ' . $tab_selected['hotgames'] . '" id="hotgames" attr-table="hotgames"><a class="nav-link"><span class="glyphicon glyphicon-fire"></span>' . $tr['hot'] . '</a></li>
	'.$myfavourite_pc.'
	<li class="nav-item allcasinotab" id="gamesel" attr-table="gamesel"><a class="nav-link" data-toggle="collapse" href="#collapsemenu" aria-expanded="false" aria-controls="collapsemenu"><i class="fas fa-filter"></i>' . $tr['Advanced'] . '</a></li>
	<li class="nav-item allcasinotab recommend_bt_content ml-auto"><a class="recommend_bt_a" attr-open="true"><i class="far fa-clock"></i>'.$tr['recommend games'].'<span>游戏</span></a></li>
	<li class="nav-item allcasinotab browse_bt_content"><a class="browse_bt_a" attr-open="true"><i class="far fa-clock"></i>' . $tr['Browse title'] . '<span>' . $tr['mobile record'] . '</span></a></li>
';
}
if ($config['themepath'] == 'landscapem') {
	$casino_tab = '<li class="nav-item allcasinotab ' . $tab_selected['all'] . '" id="allcasinotab" attr-table="game"><a class="nav-link"><span class="glyphicon glyphicon-th" aria-hidden="true"></span>' . $tr['all'] . '</a></li>
	<li class="nav-item allcasinotab ' . $tab_selected['hotgames'] . '" id="hotgames" attr-table="hotgames"><a class="nav-link"><span class="glyphicon glyphicon-fire"></span>' . $tr['hot'] . '</a></li>
	'.$myfavourite_m.'
	<li class="nav-item allcasinotab" id="gamesel" attr-table="gamesel"><a class="nav-link" data-toggle="collapse" href="#collapsemenu" aria-expanded="false" aria-controls="collapsemenu"><i class="fas fa-filter"></i>' . $tr['Advanced'] . '</a></li>
	';
}

// 取得有啟用的 casino
$gamelobbyLib = new gamelobby_lib();
$casino_list_result = $gamelobbyLib->getCasinosOrderByOpenGames($maingame_category, $debug);

// 處理排序
if ($casino_list_result[0] > 0) {
	for ($i = 1; $i <= $casino_list_result[0]; $i++) {
		if ($i == 1) {
			$idToOrder = array($casino_list_result[$i]->casino_id => $casino_list_result[$i]->casino_order);
			$idToName = array($casino_list_result[$i]->casino_id => $casino_list_result[$i]->casino_name);
		} else {
			$idToOrder[$casino_list_result[$i]->casino_id] = $casino_list_result[$i]->casino_order;
			$idToName[$casino_list_result[$i]->casino_id] = $casino_list_result[$i]->casino_name;
		}
	}
} else {
	$idToOrder = array();
	$idToName = array();
}


// 使用自訂排序
uasort($idToOrder, 'casinoOrder');
$idKeys = array_keys($idToOrder);

// 取得狀態為開啟的娛樂城
$casinoOpen = 1;
$casinos = $casinoLib->getCasinosByStatus($casinoOpen);

// 組成娛樂城選項
$casinoitem_state = 0;
$casino_item = '';
for ($i = 0; $i < count($idToOrder); $i++) {
	// 篩選符合分類
	$casinoPlatformList = array_values($casinos[$idKeys[$i]]->getGameFlatformList());
	if (!in_array(transMGCToPlatformList($maingame_category), $casinoPlatformList)) {
		continue;
	}
	// 組合遊戲平台選項
	$casinoname = $casinos[$idKeys[$i]]->getCasinoName();
	if ($casino_selected == $idKeys[$i]) {
		$casino_item .= '<label><span class="gt selected" id="' . $idKeys[$i] . '">' . $casinoname . '</span></label>';
		$casinoitem_state = 1;
	} else {
		$casino_item .= '<label><span class="gt" id="' . $idKeys[$i] . '">' . $casinoname . '</span></label>';
	}
}

// 全選項目
if ($casinoitem_state == 1) {
	$casino_item = '<label><span class="gt" id="allcasino">' . $tr['all'] . '</span></label>' . $casino_item;
} else {
	$casino_item = '<label><span class="gt selected" id="allcasino">' . $tr['all'] . '</span></label>' . $casino_item;
}

// ------------------------------------------------
// 顯示 gametype 分類選單 function - MG_HTML5Games
// -------------------------------------------------
$gamecategory = games_category_item($maingame_category, $gametype_selected);
$gamesubcategory = games_subcategory_item($maingame_category, $gametype_selected);

//推薦遊戲樣式
$recommend_content = <<<HTML
<div class="recommend_content d-none" attr-open="true">
	<div id="titleGame" class="recommend_bg_style align-items-center">
		<a href="#" title="" class="recommend_list_close"><i class="fas fa-times-circle"></i></a>
		<div class="browse_title">推荐游戏</div>
		<ul class="recommend_list_content d-flex"></ul>
	</div>
</div>
HTML;


//瀏覽紀錄列表
$browse_content_list = '';
if ($config['site_style'] == 'desktop') {
	// 桌機
	// 輪播效果  swiper
	$swiper_content = <<<HTML
	<script src='{$cdnfullurl_js}m/js/swiper.min.js'></script>
	<link rel='stylesheet' href='{$cdnfullurl_js}/m/css/swiper.min.css'>
	<div class="swiper-container browse_list_content">
	    <ul id="browse_content_list" class="swiper-wrapper browse_list_li"></ul>
	</div>
	HTML;

	// 瀏覽紀錄-外框(關閉按鈕與下一頁)
	$browse_content = <<<HTML
	<div class="browse_content" attr-open="false">
		<a href="#" title="" class="browse_close"><i class="fas fa-times-circle"></i></a>
		<div class="browse_title">{$tr['Browse Recent']}{$tr['Browse title']}</div>
		<div class="browse_list_content">
			{$swiper_content}
		</div>
		<div class="next_browse_list">
			<i class="fas fa-angle-right"></i>
		</div>
	</div>
HTML;
}

$extend_head .= '
	<script type="text/javascript" language="javascript" class="init">
			window.name=\'gamelobby\';
  // 預設的 url 參數
  var global = {
    cdnurl: \''.$cdnrooturl.'\',
  	page: 1,
  	listtables: \''.$tab_selected['selected'].'\',
  	maxiconnum: 18,
  	myWindow: \'\',
  	reviews: 7,
  	recommends: 6
  }
	var ss = \'' . isset($_SESSION["member"]) . '\';
	' . $gamesubcategory . '</script>
		<script src="' . $cdnfullurl_js . 'jquery.blockUI.js"></script>
		<link rel="stylesheet" href="casino/gamelobby.css">';
if($config['themepath'] == 'landscapem'){
		$extend_head .= '<script src="casino/gamelobbym_landscape.js"></script>';
}elseif($config['site_style'] == 'mobile'){
		$extend_head .= '<script src="casino/gamelobbym.js"></script>';
}else{
		$extend_head .= '<script src="casino/gamelobby.js"></script>';
}
$lobbyname_org = $gamelobby_setting['main_category_info'][$maingame_category]['name'];
$lobbyname = (isset($tr[$lobbyname_org])) ? $tr[$lobbyname_org] : $lobbyname_org;

//篩選bar
$filter_bar_content = <<<HTML
<div class="filter_bar_content">
	<ul id="casinotab" class="nav">
		{$casino_tab}
	</ul>
</div>
HTML;

//篩選功能表
$filter_function_content = <<<HTML
<div class="filter_function_content">
	<form id="searchform" action="javascript:" class="input-group">
		<input id="searchitem" type="text" class="form-control py-2 border-right-0 border" placeholder="{$tr['input game name']}" aria-label="{$tr['input game name']}" value="{$gametype_selected}">
		<input id="searchct" type="hidden" value="{$maingame_category}"> <span class="input-group-append">
		<button class="btn btn-outline-secondary" type="submit">
		<i class="fa fa-search"></i></button></span>
	</form>
	<div id="casino" class="row gamesel">
		<div class="col-12 d-flex">
			<div class="title">{$tr['gaming platform']}</div>
				<div class="items">
					{$casino_item}
				</div>
		</div>
	</div>
	<div id="gametype" class="row gamesel" {$gametype_display}>
		<div class="col-12 d-flex">
		<div class="title">{$tr['game type']}</div>
		<div class="items">
		{$gamecategory}
		</div>
		</div>
	</div>
		<div id="gamessubcategory" class="row gamesel" style="display: none">
			<div class="col-10 col-md-12  d-flex">
			<div class="title">{$tr['Game subtype']}</div>
			<div id="subcategoryitems" class="items">
			<label><span class="gt selected" id="allsubcategory">{$tr['all']}</span></label>
			</div>
		</div>
</div>
HTML;

// $tr['input game name']='輸入遊戲名稱'    $tr['search']='搜尋';   $tr['gaming platform']='遊戲平台：';   $tr['game type']='遊戲類型：';   $tr['Game subtype']=戲子類型：';
$lobby_table = '<div class="gamelobby-container">
	<div id="gametable" class="row"></div>
	</div>';
if ($config['themepath'] == 'landscapem') {
	$lobby_table = '<div class="gamelobby-container swiper-container">
	<div id="gametable" class="swiper-wrapper"></div>
	</div>';
	$indexbody_content = '
	<div id="gamelobby-option">
	<div class="row pt-2">
	<div class="col-12 col-md-8">
		<ul id="casinotab" class="nav nav-pills nav-justified">
		' . $casino_tab . '
		</ul>
	</div>
	<div class="col-12 col-md-4">
	</div>
	</div>

	</div>
	' . $lobby_table . '
	<div class="row"><div class="col-12">
	<ul class="pagination" id="gametablepage"></ul>
	</div></div>
	<div class="collapse gamelobby-option-collapse" id="collapsemenu">
	<div class="row pb-2">
	<form id="searchform" action="javascript:" class="input-group col-12">
	<input id="searchitem" type="text" class="form-control py-2 border-right-0 border" placeholder="' . $tr['input game name'] . ' " aria-label="' . $tr['input game name'] . '" value="' . $gametype_selected . '">
	<input id="searchct" type="hidden" value="' . $maingame_category . '"> <span class="input-group-append">
	<button class="btn btn-outline-secondary border-left-0 border" type="submit">
	<i class="fa fa-search"></i></button></span>
	</form>
    </div>
	<div id="casino" class="row gamesel">
		<div class="col-12">
		<div class="title">' . $tr['gaming platform'] . '</div>
		<div class="items">
			' . $casino_item . '
		</div>
		</div>
	</div>
	<div id="gametype" class="row gamesel" ' . $gametype_display . '>
		<div class="col-12">
		<div class="title">' . $tr['game type'] . '</div>
		<div class="items">
			' . $gamecategory . '
		</div>
		</div>
	</div>
	<div id="gamessubcategory" class="row gamesel" style="display: none">
		<div class="col-10 col-md-12">
		<div class="title">' . $tr['Game subtype'] . '</div>
		<div id="subcategoryitems" class="items">
			<label><span class="gt selected" id="allsubcategory">'. $tr['all'] .'</span></label>
		</div>
		</div>
	</div>
	</div>';
} else if ($config['site_style'] == 'mobile') {

//mobile
//手機板瀏覽紀錄
	$browse_content = <<<HTML
<div class="browse_content">
	<div class="browse_title">
		<i class="far fa-clock"></i> {$tr['Browse title']}{$tr['mobile record']}
	</div>
	<div class="browse_list">
		<ul id="browse_content_list" class="browse_list_li"></ul>
	</div>
</div>
HTML;
// mobile 直版
	$indexbody_content .= <<<HTML
<div id="gamelobby-option">
    <div class="row">
		<div class="col-12">
			{$filter_bar_content}
        </div>
        <div class="col-12 col-md-4">
        </div>
    </div>
</div>
<div id="collapsemenu" style="right:-70vw">
		<div class="control mb-2">
       <button class="btn btn-block btn-secondary" type="button" onclick="resetFillter()">
			 {$tr['clear fillter']}
        </button>
     </div>
    <div class="row py-2">
        <form id="searchform" action="javascript:" class="input-group col-12">
            <input id="searchitem" type="text" class="form-control py-2 border-right-0 border"
                placeholder="{$tr['input game name']}" aria-label="{$tr['input game name']}"
                value="{$gametype_selected}">
            <input id="searchct" type="hidden" value="{$maingame_category}"> <span class="input-group-append">
                <button class="btn btn-outline-secondary border-left-0 border" type="submit">
                    <i class="fa fa-search"></i></button></span>
        </form>
    </div>
		<div id="casino" class="row gamesel">
				<div class="title">{$tr['gaming platform']}</div>
        <div class="col-12 d-flex flex-wrap items">
                {$casino_item}
        </div>
    </div>
		<div id="gametype" class="row gamesel" {$gametype_display}>
				<div class="title">{$tr['game type']}</div>
        <div class="col-12 d-flex flex-wrap items">
                {$gamecategory}
        </div>
    </div>
		<div id="gamessubcategory" class="row gamesel" style="display: none">
				<div class="title">{$tr['Game subtype']}</div>
        <div id="subcategoryitems" class="col-md-12 d-flex flex-wrap items">
        	<label><span class="gt selected" id="allsubcategory">{$tr['all']}</span></label>
        </div>
    </div>
		<div class="control mt-2">
       <button class="btn btn-block btn-primary" type="button" onclick="closeSel()">
			 {$tr['fillter result']}
        </button>
    </div>
	{$browse_content}
</div>
{$lobby_table}
<div class="row">
    <div class="col-12">
        <ul class="pagination" id="gametablepage"></ul>
    </div>
</div>
<div id="gamesel-block" class="block-layout motransaction_layout" style="display: none;z-index: 98"></div>
	<script>
	$( document ).ready(function() {
			$(document).on("click","#gamesel",function(){
				$("#gamesel-block").show();
				$("#collapsemenu").animate({right:'0'});
			});
			$(document).on("click","#gamesel-block",function(){
				$("#gamesel-block").hide();
				$("#collapsemenu").animate({right:'-70vw'});
			});
	});
	function closeSel(){
		$("#gamesel-block").hide();
		$("#collapsemenu").animate({right:'-70vw'});
	}
	</script>
HTML;
} else {
//PC 推薦遊戲
	$indexbody_content = $recommend_content . $browse_content . '
	<div id="gamelobby-option">
	<div class="row">
	<div class="col-12">
	' . $filter_bar_content . '
	</div>
	<div class="col-12 col-md-4">
	</div>
	</div>
	<div class="collapse" id="collapsemenu">
	' . $filter_function_content . '
	</div>
	</div>
	</div>
	' . $lobby_table . '
	<div class="row"><div class="col-12">
	<ul class="pagination" id="gametablepage"></ul>
	</div></div>';
}
// ---
// 業務展示站台用
// ---
$indexbody_content .= '
  <!-- Modal -->
  <div class="modal fade" id="bsdemo" role="dialog">
    <div class="modal-dialog">
  <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
        	<h4 class="modal-title">業務展示站台</h4>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
               <center><p>目前所在站台為業務展示站台，不支援進此娛樂城</p></center>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>';
	// ---
	// 錢包鎖定用
	// ---
	$indexbody_content .= '
	  <!-- Modal -->
	  <div class="modal fade" id="walletlock" role="dialog">
	    <div class="modal-dialog">
	  <!-- Modal content-->
	      <div class="modal-content">
	        <div class="modal-header">
	        	<h4 class="modal-title">'.$tr['Member wallets'].'</h4>
	          <button type="button" class="close" data-dismiss="modal">&times;</button>
	        </div>
	        <div class="modal-body">
	               <center><p>'.$tr['Account has been locked'].'</p></center>
	        </div>
	        <div class="modal-footer">
	          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
	        </div>
	      </div>
	    </div>
	  </div>';

// ----------------------------------------------------------------------
// 新增檢查系統預設會員存款別，如預設存gcash但是gtoken自動儲值為關閉則跳警告訊息通知會員
// 記得至會員錢包自行手動儲值或開啟自動儲值功能
// ----------------------------------------------------------------------
if (isset($_SESSION['member'])) {
	$setting_member_deposit_currency = runSQLall("SELECT value FROM root_protalsetting WHERE setttingname = 'default' AND name = 'member_deposit_currency';")[1]->value;
	$setting_member_auto_gtoken = runSQLall("SELECT auto_gtoken FROM root_member_wallets WHERE id = '" . $_SESSION['member']->id . "';")[1]->auto_gtoken;
	//echo $setting_member_deposit_currency.' ?? '.$setting_member_auto_gtoken;
	if ($setting_member_deposit_currency == 'gcash' AND $setting_member_auto_gtoken == '0' AND $_SESSION['member']->gcash_balance > '0' AND $_SESSION['member']->gtoken_lock == '' AND $_SESSION['member']->gtoken_balance < '1') {
		$extend_head = $extend_head . '
      <script type="text/javascript" language="javascript" class="init">
        $(document).ready(function() {
          alert("目前未开启自动储值功能，进入游戏前请记得前往会员钱包进行手动储值或开启自动储值功能，谢谢！");
        });
      </script>';
	}
}
if ($config['site_style'] == 'mobile') {
	$extend_js = <<<HTML
		<script>
			// 推薦遊戲和瀏覽紀錄
			$(document).ready(function(){
				//game menu
				var navlingth = $('#gNavi li').length;
				if( navlingth == 2 ){
					$('#gNavi li').addClass('widthactive');
				}
				// 取得推薦遊戲
				getRecommendGame();
			});

			// 取得推薦遊戲
			function getRecommendGame() {
				$.post("gamelobby_action.php?a=recommendList",
					{recommend: global.recommends},
					function(data) {
						let count = JSON.parse(data).counts;
						let games = JSON.parse(data).gameitems;
						let recommendHtml = "";
						// console.log(JSON.parse(data));
						for (let i=1; i <= count; i++) {
							let game = games[i];

							recommendHtml += '<li class="swiper-slide"><div class="recommend_game_content"><div class="gameimg_img">';

							// 圖片
							recommendHtml += '<img src=\''+ game.gameimgurl +'\' alt="" onerror="this.src=\'' + game.cdnurl +'.png\'">';

							// 進入遊戲按鈕
							if (game.bsdemo == '1') {
								recommendHtml += '<button type="button" data-toggle="modal" data-target="#bsdemo">'+ game.g2gamelabel +'</button>';
							} else {
								if (ss == 1) {
									recommendHtml += '<a class="gotogame" href="gamelobby_action.php" target="gpk_gamewindow"><button value="' + game.g2gamelabel + '" onclick="gotogame(\'' + game.casinoid + '\',\'' + game.gamecode + '\',\'' + game.gameplatform + '\',\'' + game.gamename + '\',\'' + game.token + '\')">' + game.g2gamelabel + '</button></a>';
								} else {
									recommendHtml += '<button class="gotogame" value="' + game.g2gamelabel + '" onclick="loginpage(\'' + game.token + '\')">' + game.g2gamelabel + '</button>';
								}
							}
							recommendHtml += '</div>';

							// 遊戲名稱
							recommendHtml += '<div class="gamename_content">'+ game.gamename +'</div>';

							// 娛樂城
							recommendHtml += '<div class="gamelike_content"><div>'+ game.casinoname +'</div>';

							// 手機遊戲標示
							recommendHtml += '<div class="falike">';

							// 最愛遊戲
							if (game.myfavfunc == 'addmyfav') {
								recommendHtml += '<span class="far fa-heart" value="'+ game.myfavlabel +'" onclick="'+ game.myfavfunc +'(\''+ game.casinoid +'\',\''+ game.gamecode +'\',\''+ game.gameplatform +'\',\''+ game.myfavtoken +'\')"></span>'
							} else {
								recommendHtml += '<span class="fas fa-heart" value="'+ game.myfavlabel +'" onclick="'+ game.myfavfunc +'(\''+ game.casinoid +'\',\''+ game.gamecode +'\',\''+ game.gameplatform +'\',\''+ game.gametype +'\',\''+ game.gamename +'\')"></span>'
							}
							recommendHtml += '</div></div></div></li>'
						}
						$(".recommend_list_content").html(recommendHtml);
						game_recommend_swiper.update()
					}
				);
			}
	</script>
HTML;
} else {
	$extend_js = <<<HTML
<script>
	// 推薦遊戲和瀏覽紀錄
	$(document).ready(function(){
		//game menu
		var navlingth = $('#gNavi li').length;
		if( navlingth == 2 ){
			$('#gNavi li').addClass('widthactive');
		}
	    // 取得推薦遊戲
		getRecommendGame();
	});

	// 取得推薦遊戲
	function getRecommendGame() {
		$.post("gamelobby_action.php?a=recommendList",
			{recommend: global.recommends},
			function(data) {
				let count = JSON.parse(data).counts >5 ? 5 : JSON.parse(data).counts;
				let games = JSON.parse(data).gameitems;
				let recommendHtml = "";
				if ( games !='' ) {
				for (let i=1; i <= count; i++) {
				    let game = games[i];
				    recommendHtml += '<li><div class="recommend_game_content"><div class="gameimg_img">';
				    // 圖片
				    recommendHtml += '<img src=\''+ game.gameimgurl +'\' alt="" onerror="this.src=\'' + game.cdnurl +'.png\'">';

				    // 進入遊戲按鈕
				    if (game.bsdemo == '1') {
				        recommendHtml += '<button type="button" data-toggle="modal" data-target="#bsdemo">'+ game.g2gamelabel +'</button>';
				    } else {
				        if (ss == 1) {
				            recommendHtml += '<a class="gotogame" href="gamelobby_action.php" target="gpk_gamewindow"><button value="' + game.g2gamelabel + '" onclick="gotogame(\'' + game.casinoid + '\',\'' + game.gamecode + '\',\'' + game.gameplatform + '\',\'' + game.gamename + '\',\'' + game.token + '\')">' + game.g2gamelabel + '</button></a>';
				        } else {
				            recommendHtml += '<button class="gotogame" value="' + game.g2gamelabel + '" onclick="loginpage(\'' + game.token + '\')">' + game.g2gamelabel + '</button>';
				        }
						}
						// 最愛遊戲
				    if (game.myfavfunc == 'addmyfav') {
				        recommendHtml += '<button class="myfav"  value="'+ game.myfavlabel +'" onclick="'+ game.myfavfunc +'(\''+ game.casinoid +'\',\''+ game.gamecode +'\',\''+ game.gameplatform +'\',\''+ game.myfavtoken +'\')">'+ game.myfavlabel +'</button>'
				    } else {
				        recommendHtml += '<button class="myfav" value="'+ game.myfavlabel +'" onclick="'+ game.myfavfunc +'(\''+ game.casinoid +'\',\''+ game.gamecode +'\',\''+ game.gameplatform +'\',\''+ game.gametype +'\',\''+ game.gamename +'\')">'+ game.myfavlabel +'</button>'
						}
						
				    recommendHtml += '</div>';

				   	// 娛樂城  // 遊戲名稱 
						recommendHtml += '<div class="gamename_content"><span class="browsegame_platformname">'+ game.casinoname +'</span>'+ game.gamename +'</div>';

			    
				    recommendHtml += '</div></div></li>'
				}

				$(".recommend_list_content").html(recommendHtml);
				$(".recommend_content").removeClass('d-none');
				}else{
				 $(".recommend_content").addClass('d-none');
				}
			}
		);
	}

	//推薦遊戲伸縮效果
	$(function(){
		$('.recommend_bt_a').click(function(){
			var data = $(this).attr('attr-open');
			if( data == 'true' ){
				$('.recommend_content').attr('attr-open','false');
				$('.recommend_bt_a').attr('attr-open','false');
			}else{
				$('.recommend_content').attr('attr-open','true');
				$('.recommend_bt_a').attr('attr-open','true');
			}
			return false;
		});
		$('.recommend_list_close').click(function(){
			$('.recommend_content').attr('attr-open','false');
			$('.recommend_bt_a').attr('attr-open','false');
			return false;
		});
	});

</script>
HTML;
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
$tmpl['paneltitle_content'] = '<span class="glyphicon glyphicon-th" aria-hidden="true"></span>' . $function_title;
// 主要內容 -- content
$tmpl['panelbody_content'] = $indexbody_content;
// banner標題
$tmpl['banner'] = [$lobbyname_org];
// menu增加active
$tmpl['menu_active'] = ['gamelobby.php?mgc=' . $maingame_category];

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include($config['template_path'] . "template/lobby_mggame.tmpl.php");
