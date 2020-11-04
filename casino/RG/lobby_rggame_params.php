<?php
/**
 * RG API 彩票相關參數
 *
 * Author: Letter
 * Date: 2018/12/13
 * Time: 下午 03:29
 */

class lobby_rggame_params
{
	public static $RG_CASINO_ID = 'RG';
	public static $RG_CASINO_FS_RATIO_EXCHANGE = 10;
	public static $RG_CASINO_ROOT_FS_RATIO = 10.0;

	public static $CASINO_TRANSFER_MODE_TRAIL = 0;
	public static $CASINO_TRANSFER_MODE_NORMAL = 1;
	public static $CASINO_TRANSFER_MODE_LIMITED = 2;
	public static $CASINO_TRANSFER_LIMITED_AMOUNT = 10;
	public static $BALANCE_PRECISION = 2;
	public static $TRANSFER_TYPE = array(
		'Withdraw' => 0,
		'Deposit' => 1,
		'Deposit_all' => 2
	);
	public static $TRANSFER_STATUS_SUCCEED = 0;
	public static $TRANSFER_STATUS_FAILED = 1;

	public static $API_ERROR_CODE_NO_ERROR = 0;
	public static $API_ERROR_CODE_PARAMS_NOT_MATCH = 1;
	public static $API_ERROR_CODE_INVALIDATE_PARAMS = 2;
	public static $API_ERROR_CODE_MEMBER_NOT_FOUND = 2;
	public static $API_ERROR_CODE_INVALIDATE_KEY = 3;
	public static $API_ERROR_CODE_TRANSFER = array(
		0 => 'success',
		2 => 'TransferType not exist',
		11 => 'Amount should bigger than 1',
		12 => 'Miss Transaction Id',
		13 => 'Transaction Id already exist',
		14 => 'User not exist',
		15 => 'Withdraw failed',
		16 => 'Deposit failed',
		17 => 'Balance not enough, current balance is …'
	);

	public static $EXECUTE_FAILED = 1;
	public static $MULTIPLE_FUNCTION_EXECUTE = 9;
	public static $EXECUTE_SUCCESS = 10;
	public static $MEMBER_NOT_EXIST = 14;
	public static $CHECK_WATTLE_COIN_SUCCESS = 20;
	public static $API_FUNCTION_ERROR = 21;
	public static $SESSION_MEMBER_MISSING = 22;
	public static $ILLEGAL_CONDITIONS = 99;

	public static $DB_EXECUTE_SUCCESS = 1;
	public static $DB_EXIST_RECORD = 2;
	public static $DB_ZERO_RESULT = 0;
}