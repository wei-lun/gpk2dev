<?php
// ----------------------------------------------------------------------------
// Features:	前台--遊戲大廳函式庫
// File Name:	gamelobby_lib.php
// Author:		Letter
// Related:		gamelobby.php gamelobby_action.php
// Log:
// 2020.08.11 新建 Letter
// ----------------------------------------------------------------------------

require_once 'lib.php';

class gamelobby_lib
{

	/**
	 *  由開啟的娛樂城取得主分類
	 *
	 * @param  mixed $type 類別
	 * @param int $debug 除錯模式，0 為非除錯模式
	 *
	 * @return array 主類別
	 */
	function getMainCategoriesByOpenCasino($type, $debug = 0)
	{

		switch ($type) {
			case 'game':
				$sql = <<< SQL
			SELECT DISTINCT category FROM casino_gameslist AS cg LEFT JOIN casino_list AS cl ON cl.casinoid = cg.casino_id 
			WHERE cg.open = 1 AND cl.open= 1 AND cg.category NOT SIMILAR TO ('%(Live|Lottery|Sport)%');
SQL;
				break;
			case 'Sport':
			case 'Live':
			case 'Fishing':
			case 'Lottery':
			case 'Chessboard':
				$sql = <<< SQL
			SELECT DISTINCT gametype AS category FROM casino_gameslist AS cg LEFT JOIN casino_list AS cl 
			ON cl.casinoid = cg.casino_id WHERE cg.open = 1 AND "marketing_strategy"->>'mct' = '{$type}';
SQL;
				break;
			default:
				$sql = '';
		}

		return runSQLall($sql, $debug);
	}


	/**
	 *  由開啟的遊戲取得主分類
	 *
	 * @param int $debug 除錯模式，0 為非除錯模式
	 *
	 * @return array 主類別
	 */
	function getMainCategoriesByOpenGames($debug = 0)
	{
		$sql = <<< SQL
			SELECT DISTINCT category FROM casino_gameslist WHERE open = 1 AND category NOT SIMILAR TO ('%(Live|Lottery|Sport)%');
SQL;
		return runSQLall($sql, $debug);
	}


	/**
	 *  由開啟的遊戲取得次分類
	 *
	 * @param mixed $type 遊戲主類別
	 * @param int $debug 除錯模式，0 為非除錯模式
	 *
	 * @return array 子類別
	 */
	function getSubCategoriesByOpenGames($type, $debug = 0)
	{
		$sql = <<< SQL
			SELECT DISTINCT sub_category FROM casino_gameslist WHERE open = 1 AND category='{$type}' ORDER BY sub_category;
SQL;
		return runSQLall($sql, $debug);
	}


	/**
	 *  由開啟的遊戲及主分類取得遊戲類型
	 *
	 * @param mixed $category 主分類
	 * @param int $debug 除錯模式，0 為非除錯模式
	 *
	 * @return array 遊戲類型
	 */
	function getGameTypeByOpenGames($category, $debug = 0)
	{
		$sql = <<< SQL
			SELECT DISTINCT gametype FROM casino_gameslist WHERE open = 1 AND "marketing_strategy"->>'mct' = '{$category}';
SQL;
		return runSQLall($sql, $debug);
	}


	/**
	 *  由開啟的遊戲及類型取得遊戲次分類
	 *
	 * @param mixed $gameType 遊戲類型
	 * @param int $debug 除錯模式，0 為非除錯模式
	 *
	 * @return array 次分類
	 */
	function getSubCategoriesByGameType($gameType, $debug = 0)
	{
		$sql = <<< SQL
			SELECT DISTINCT sub_category FROM casino_gameslist WHERE open = 1 AND gametype='{$gameType}' ORDER BY sub_category;
SQL;
		return runSQLall($sql, $debug);
	}


	/**
	 *  由開啟的遊戲取得娛樂城排序
	 *
	 * @param mixed $category 主分類
	 * @param int $debug 除錯模式，0 為非除錯模式
	 *
	 * @return array 排序後的娛樂城
	 */
	function getCasinosOrderByOpenGames($category, $debug = 0)
	{
		$sql = <<< SQL
			SELECT DISTINCT casino_id, casino_name, casino_order FROM casino_gameslist JOIN casino_list 
			ON casino_gameslist.casino_id = casino_list.casinoid WHERE casino_list.open = '1' 
			AND "marketing_strategy"->>'mct' = '{$category}';
SQL;
		return runSQLall($sql, $debug);
	}
}