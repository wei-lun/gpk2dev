<?php
// ----------------------------------------------------------------------------
// Features:	前台--娛樂城函式庫
// File Name:	casino_lib.php
// Author:		Letter
// Related:		casino.php
// Log:
// 2020.02.21 新建 Letter
// ----------------------------------------------------------------------------

require_once 'lib.php';
require_once 'Casino.php';


class casino_lib
{
	static public $debug = 0;

	static public $ops = 'ops';
	static public $master = 'master';
	static public $theRole = 'R';

	static public $isNew = 1;
	static public $isOld = 0;
	static public $noSet = -1;

	/**
	 * casino_switch_process_lib constructor.
	 */
	public function __construct()
	{
	}


	/**
	 * 依帳號取得權限
	 *
	 * @param string $account 帳號
	 *
	 * @return string 權限
	 */
	public function getPermissionByAccount(string $account)
	{
		global $su;
		if (in_array($account, $su[$this::$ops])) {
			return $this::$ops;
		} elseif (in_array($account, $su[$this::$master])) {
			return $this::$master;
		} else {
			return $this->getTheroleByAccount($account);
		}
	}


	/**
	 * 依帳號取得資料庫內會員權限
	 *
	 * @param string $account 帳號
	 *
	 * @return string 會員權限
	 */
	public function getTheroleByAccount(string $account)
	{
		$sql = 'SELECT therole FROM "root_member" WHERE "account" = \'' . $account . '\';';
		$role = runSQLall($sql, $this::$debug);
		return $role[0] > 0 ? $role[1]->therole : ' ';
	}


	/**
	 * 依狀態選擇娛樂城
	 *
	 * @param string $status 娛樂城狀態
	 * @param bool   $show   顯示永久停用，true 為顯示
	 * @param array  $sort   排序設定，[ '排序資料表欄位', '排序方式' ]
	 * @param int    $debug  除錯模式，0 為非除錯模式
	 *
	 * @return mixed 符合狀態的娛樂城
	 * @throws Exception
	 */
	public function getCasinosByStatusAndOrder(string $status, bool $show, array $sort, int $debug = 0)
	{
		global $tr;

		$sql = <<<SQL
			SELECT * ,to_char((notify_datetime at time zone 'AST'),'YYYY-MM-DD HH24:MI:SS') as notify_datetime FROM casino_list
SQL;

		// 娛樂城狀態
		switch ($status) {
			case $this::$ops:
				$sql .= ' WHERE "open" <> ' . casino::$casinoDeprecated;
				// 顯示永久停用
				if ($show) {
					$sql .= ' OR "open" = ' . casino::$casinoDeprecated;
				}
				break;
			case $this::$master:
			case $this::$theRole:
				$sql .= ' WHERE "open" = ' . casino::$casinoOff . ' OR "open" = ' . casino::$casinoOn . ' OR "open" = ' . casino::$casinoEmgForCasinoOn . ' OR "open" = ' . casino::$casinoEmgForCasinoOff;
				break;
			case casino::$casinoNew:
				$sql .= ' WHERE "notify_datetime" > now() AND ("open" = ' . casino::$casinoOff . ' OR "open" = ' .
					casino::$casinoOn . ' OR "open" = ' . casino::$casinoEmg . ')';
				break;
			case casino::$casinoOff:
				$sql .= ' WHERE "open" = ' . casino::$casinoOff;
				break;
			case casino::$casinoEmg:
				$sql .= ' WHERE "open" = ' . casino::$casinoEmgForCasinoOn . ' OR "open" = ' . casino::$casinoEmgForCasinoOff;
				break;
			case casino::$casinoClose:
				$sql .= ' WHERE "open" = ' . casino::$casinoCloseForCasinoOn . ' OR "open" = ' . casino::$casinoCloseForCasinoOff;
				break;
			case casino::$casinoDeprecated:
			default:
				break;
		}

		// 排序
		$sql .= ' ORDER BY "' . $sort['columnIndex'] . '" ' . strtoupper($sort['sortFormat']) . ';';

		$result = runSQLall($sql, $debug);
		debugMode($this::$debug, $result);

		$casinos = array();
		if ($result[0] > 0) {
			for ($i = 1; $i <= $result[0]; $i++) {
				$casino = new casino(
					$result[$i]->id,
					$result[$i]->casinoid,
					$this->getCurrentLanguageCasinoName($result[$i]->display_name, 'default'),
					$result[$i]->casino_dbtable,
					$result[$i]->note,
					$result[$i]->open,
					$result[$i]->account_column,
					$result[$i]->bettingrecords_tables,
					$result[$i]->casino_order,
					json_decode($result[$i]->game_flatform_list, true),
					$result[$i]->notify_datetime,
					$result[$i]->api_update,
					$this->getCurrentLanguageCasinoName($result[$i]->display_name, $_SESSION['lang']),
					$this->getNewAlert($result[$i]->notify_datetime)
				);
				array_push($casinos, $casino);
			}
		}

		return $casinos;
	}


	/**
	 * 用狀態取得娛樂城
	 *
	 * @param int $status 娛樂城狀態
	 * @param int $debug  除錯模式，預設 0 為不開啟
	 *
	 * @return array 符合狀態的娛樂城
	 * @throws Exception
	 */
	function getCasinosByStatus($status, $debug = 0)
	{
		$sql = 'SELECT * FROM casino_list WHERE "open" = ' . $status . ';';
		$result = runSQLall($sql, $debug);
		$casinos = array();
		if ($result[0] > 0) {
			for ($i = 1; $i <= $result[0]; $i++) {
				$item = new Casino(
					$result[$i]->id,
					$result[$i]->casinoid,
					$this->getCurrentLanguageCasinoName($result[$i]->display_name, 'default'),
					$result[$i]->casino_dbtable,
					$result[$i]->note,
					$result[$i]->open,
					$result[$i]->account_column,
					$result[$i]->bettingrecords_tables,
					$result[$i]->casino_order,
					json_decode($result[$i]->game_flatform_list, true),
					$result[$i]->notify_datetime,
					$result[$i]->api_update,
					$this->getCurrentLanguageCasinoName($result[$i]->display_name, $_SESSION['lang']),
					$this->getNewAlert($result[$i]->notify_datetime)
				);
				$casinos[$result[$i]->casinoid] = $item;
			}
		}
		return $casinos;
	}

	/**
	 * 取得最新提醒
	 *
	 * @param $notifyTime
	 *
	 * @return int
	 * @throws Exception
	 */
	public function getNewAlert($notifyTime)
	{
		$now = new DateTime();
		if (is_null($notifyTime)) {
			return $this::$noSet;
		}
		$nt = new DateTime($notifyTime);
		$result = $this::$noSet;
		if ($nt >= $now) {
			$result = $this::$isNew;
		} elseif ($nt < $now) {
			$result = $this::$isOld;
		}
		return $result;
	}


	/**
	 * 更新娛樂城排序
	 *
	 * @param int $id    序號
	 * @param int $order 順序
	 * @param int $debug 除錯模式，0 為非除錯模式
	 *
	 * @return mixed 被更新資料數
	 */
	public function updateCasinoOrder($id, $order, int $debug = 0)
	{
		$sql = 'UPDATE "casino_list" SET casino_order = ' . $order . ' WHERE id = ' . $id . ';';
		$result = runSQLall($sql, $debug);
		return $result;
	}


	/**
	 * 取得所有娛樂城
	 *
	 * @param int $debug 除錯模式，0 為非除錯模式
	 *
	 * @return array 娛樂城物件
	 */
	public function getCasinos($debug = 0)
	{
		$sql = 'SELECT * FROM "casino_list"';
		$result = runSQLall($sql, $debug);
		return $result;
	}


	/**
	 * 取得娛樂城總數
	 *
	 * @param int $debug 除錯模式，0 為非除錯模式
	 *
	 * @return int 娛樂城總數
	 */
	public function getCasinosCount($debug = 0)
	{
		$result = $this->getCasinos($debug);
		return $result[0];
	}


	/**
	 * 更新娛樂城資料表欄位資料
	 *
	 * @param int    $id     ID
	 * @param string $column 資料表欄位
	 * @param string $value  更新數值
	 * @param int    $debug  除錯模式，0 為非除錯模式
	 *
	 * @return int 資料表變動資料數
	 */
	public function updateCasinoColumnById($id, string $column, string $value, int $debug = 0)
	{
		$typeValue = $this->getColumnTypeValue($column, $value);
		$sql = 'UPDATE "casino_list" SET ' . $column . ' = \'' . $typeValue . '\' WHERE id = ' . $id . ';';
		$result = runSQLall($sql, $debug);
		return $result[0];
	}


	/**
	 * 取得欄位資料型態資料
	 *
	 * @param string $column 欄位名稱
	 * @param mixed  $value  欲轉換資料
	 *
	 * @return mixed 轉換後資料
	 */
	public function getColumnTypeValue($column, $value)
	{
		switch ($column) {
			case 'game_flatform_list':
				$result = json_encode($value);
				break;
			default:
				$result = $value;
		}
		return $result;
	}


	/**
	 *  依目前語系取得娛樂城顯示名稱
	 *
	 * @param mixed $displayNames 語系顯示名稱
	 * @param mixed $i18n         語系
	 *
	 * @return mixed 目前語系顯示名稱，若該語系無顯示名稱，回覆預設顯示名稱
	 */
	public function getCurrentLanguageCasinoName($displayNames, $i18n)
	{

		$i18nNameArr = get_object_vars(json_decode($displayNames));

		// 取得對應語系顯示名稱
		if (key_exists($i18n, $i18nNameArr)) {
			$display = $i18nNameArr[$i18n];
		} else {
			$display = $i18nNameArr['en-us'];
		}
		return $display;
	}


	/**
	 * 用娛樂城 ID 取得娛樂城語系名稱
	 *
	 * @param mixed $casinoId 娛樂城 ID
	 * @param mixed $i18n 目前平台選擇語系
	 * @param int   $debug    除錯模式，0 為非除錯模式
	 *
	 * @return string 娛樂城語系名稱
	 */
	function getCasinoNameByCasinoId($casinoId, $i18n, $debug = 0)
	{
		$sql = 'SELECT * FROM casino_list WHERE casinoid = \''. $casinoId .'\'';
		$result = runSQLall($sql, $debug);
		$casinoDefaultName = '';
		if ($result[0] > 0 and !is_null($result[1]->display_name)) {
			$casinoDefaultName = $this->getCurrentLanguageCasinoName($result[1]->display_name, $i18n);
		}
		return $casinoDefaultName;
	}
}