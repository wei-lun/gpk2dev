<?php
// ----------------------------------------
// Features:	前後台共用設定檔
// File Name:	lib_common.php
// Author:		Barkley
// Related:
// Log:
// 2017.2.3 update
// -----------------------------------------------------------------------------

// ------------------------------------------------------------------------------------------------
// PostGreSQL 常用的 Function
// ------------------------------------------------------------------------------------------------


// ------------------------------------------------------------------------------------------------
// PostGreSQL 常用的 Function
// ------------------------------------------------------------------------------------------------

function get_pdo_object($sqlact='w')
{
    // ref:http://php.net/manual/en/book.pdo.php
    // db 帳號密碼變數 global
    global  $pdo;

    // 当读写分离的主机设定都存在的时候, 才使用读写分离的设定
    if(isset($pdo['host4write']) AND isset($pdo['host'])) {
        // DB主機讀寫分離
        if(strtolower($sqlact) == 'w'){
            $pdo_host = $pdo['host4write'];
        }else{
            $pdo_host = $pdo['host'];
        }
    }else{
        // 没有设定档的时候, 就停止强迫使用者更新 DB config , 以后可以加入多台读取的主机
        die('Lost DB PDO config.');
        // $pdo_host = $pdo['host'];
    }

    // 建立 DB 連線
    try {
        $dbh_string = $pdo['db'].':dbname='.$pdo['dbname'].';host='.$pdo_host;
        $dbh = new PDO("$dbh_string", $pdo['user'], $pdo['password'] );
    } catch (PDOException $e) {
        print "DB connect Error!: " . $e->getMessage() . "<br/>";
        die();
    }

    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    return $dbh;
}

// ---------------------------------------------------------------------
// run SQL command with prepare then return $result
// $result[0] ~ $result[n] --> 資料內容，從第 [0] 開始
// 使用方式 example:
// $sql = 'SELECT * FROM root_statisticsdailyreport WHERE date = :date';
// $result = runSQLall($sql, [':date' => '2017-12-20']);
// var_dump($result);
//
// $fetch_classname --> for PDO::FETCH_CLASS
// ref: https://stackoverflow.com/questions/5137051/pdo-php-fetch-class
//
// $debug --> 除錯資訊顯示 1 , 不顯示 0
// ---------------------------------------------------------------------
function runSQLall_prepared($sql="SET NAMES 'utf8';", $prepare_array = [], $fetch_classname="", $debug="0", $sqlact='w')
{
  // 取得PDO物件
  $dbh = get_pdo_object($sqlact);

  if(strtolower($sqlact) == 'w'){
      $stime = 10000;
  }else{
      $stime = 0;
  }

  try{
    $info = $dbh->getAttribute(\PDO::ATTR_SERVER_INFO);
    if (is_null($info)) {
        echo "---DB server has gone away\n";
    }

						// sql 執行
						$sth = $dbh->prepare("$sql");
						$db_dump_result_all = NULL;
						// 如果執行成功, 就把資料以 FETCH_OBJ 方式拿出來
						if($sth->execute($prepare_array)) {
							// 所有資料取出, 會花費時間儲存變數
							if(empty($fetch_classname))
								$db_dump_result_all = $sth->fetchAll(PDO::FETCH_OBJ);
							else
								$db_dump_result_all = $sth->fetchAll(PDO::FETCH_CLASS, $fetch_classname);

						}else{
							// 請參考 postgresql error code 對應表 https://www.postgresql.org/docs/9.4/static/errcodes-appendix.html
							$debug_message = "runSQLall_prepared ERROR: ["
								. "\nerrorCode:".$sth->errorCode()
								. "\ninfo:".$sth->errorInfo()[2]
								. "\n]\n";

							if($debug == 1) {
								var_dump($sql);
							}
							error_log( date("Y-m-d H:i:s").' '.$debug_message.' SQL:'.$sql);
							$db_dump_result_all = FALSE;
							echo "$debug_message";
							die();
						}

						// 顯示除錯資訊
						if($debug == 1) {
							var_dump($sql);
						}
						// 設定讀寫分離，需於寫入時等待約 10 亳秒的時間讓MASTER DB資料寫入SLAVE DB
						// usleep($stime);
				} catch(\Exception $e) {
				    echo "ErrorCode:" . $e->getCode() . ",ErrorMsg:" . $e->getMessage();
				}
				$dbh = null;

	return($db_dump_result_all);
}
// ---------------------------------------------------------------------



// ---------------------------------------------------------------------
// run SQL command then return $result
// $result[0] --> 資料數量, 如果為 0 表示沒有變動的列
// $result[1] ~ $result[n] --> 資料內容，從第 [1] 開始
// 使用方式 example:
// $result = runSQLall($sql);
// var_dump($result);
//
// $debug --> 除錯資訊顯示 1 , 不顯示 0
// $cache --> 使用 memcache = 1, 不使用 memcache = 0 --> todo
// $cache_timeout --> 時間 timeout = 600 sec  --> todo
// ---------------------------------------------------------------------
function runSQLall($sql="SET NAMES 'utf8';", $debug="0", $sqlact='w')
{
  // 取得PDO物件
  $dbh = get_pdo_object($sqlact);

  if(strtolower($sqlact) == 'w'){
      $stime = 10000;
  }else{
      $stime = 0;
  }

  try{
    $info = $dbh->getAttribute(\PDO::ATTR_SERVER_INFO);
    if (is_null($info)) {
        echo "---DB server has gone away\n";
    }

						// sql 執行
						$sth = $dbh->prepare("$sql");
						$db_dump_result_all = NULL;
						// 如果執行成功, 就把資料以 FETCH_OBJ 方式拿出來
						if($sth->execute()) {
							// 放置紀錄數量
							$db_dump_result_all[0] = $sth->rowCount();
							// 所有資料取出, 會花費時間儲存變數
							$i=1;
							while($db_dump_result = $sth->fetch(PDO::FETCH_OBJ)) {
								$db_dump_result_all[$i] = $db_dump_result;
								$i++;
							}
						}else{
							// 請參考 postgresql error code 對應表 https://www.postgresql.org/docs/9.4/static/errcodes-appendix.html
							$debug_message = "[runSQLall ERROR:".$sth->errorCode()."]";

							if($debug == 1) {
								var_dump($sql);
							}

							error_log( date("Y-m-d H:i:s").' '.$debug_message.' SQL:'.$sql);
							$db_dump_result_all = FALSE;
							echo "$debug_message";
							die();
						}

						// 顯示除錯資訊
						if($debug == 1) {
							var_dump($sql);
						}
				 // 設定讀寫分離，需於寫入時等待約 10 亳秒的時間讓MASTER DB資料寫入SLAVE DB
				 	usleep($stime);
				} catch(\Exception $e) {
				    echo "ErrorCode:" . $e->getCode() . ",ErrorMsg:" . $e->getMessage();
				}
				$dbh = null;

	return($db_dump_result_all);
}
// ---------------------------------------------------------------------

// ---------------------------------------------------------------------
// run SQL command then return $result
// $result --> 資料數量, 如果為 0 表示沒有變動的列
//
// 使用方式 example:
// $result = runSQL($sql);
// var_dump($result);
//
// $debug --> 除錯資訊顯示 1 , 不顯示 0
// $cache --> 使用 memcache = 1, 不使用 memcache = 0 --> todo
// $cache_timeout --> 時間 timeout = 600 sec  --> todo
// ---------------------------------------------------------------------
function runSQL($sql="SET NAMES 'utf8';", $debug="0", $sqlact='w', $error_callback = null)
{
  // 取得PDO物件
  $dbh = get_pdo_object($sqlact);

  if(strtolower($sqlact) == 'w'){
      $stime = 10000;
  }else{
      $stime = 0;
  }

  try{
    $info = $dbh->getAttribute(\PDO::ATTR_SERVER_INFO);
    if (is_null($info)) {
        echo "---DB server has gone away\n";
    }

						$sth = $dbh->prepare("$sql");
						$db_dump_result_num = NULL;
						// 如果執行成功, 就把資料以 FETCH_OBJ 方式拿出來
						if($sth->execute()) {
							// 放置紀錄數量
							$db_dump_result_num = $sth->rowCount();
						}else{
							// 請參考 postgresql error code 對應表 https://www.postgresql.org/docs/9.4/static/errcodes-appendix.html
							$debug_message = "[runSQL ERROR:".$sth->errorCode()."]";

							if($debug == 1) {
								var_dump($sql);
							}

							if(!empty($error_callback)) {
								$error_callback($sql);
							}
							error_log( date("Y-m-d H:i:s").' '.$debug_message.' SQL:'.$sql);
							$db_dump_result_num = FALSE;
							echo $debug_message;
							die();
						}

						// 顯示除錯資訊
						if($debug == 1) {
							var_dump($sql);
							var_dump($db_dump_result_num);
						}
		  		// 設定讀寫分離，需於寫入時等待約 10 亳秒的時間讓MASTER DB資料寫入SLAVE DB
		   usleep($stime);
				} catch(\Exception $e) {
				    echo "ErrorCode:" . $e->getCode() . ",ErrorMsg:" . $e->getMessage();
				}
				$dbh = null;

	// 回傳受影響的列
	return($db_dump_result_num);
}
// ---------------------------------------------------------------------
// $sql = 'select * from "dot1dTpFdbTable" limit 5;';
// var_dump(runSQLall($sql));


// ---------------------------------------------------------------------
// run SQL command使用交易方式確保成功 ACID then return $result
// 使用方式 example:
// $result = runSQLtransactions($sql);
// var_dump($result);
// success = 1 , false = 0
// ref: http://php.net/manual/en/pdo.transactions.php
// ---------------------------------------------------------------------
function runSQLtransactions($sql="SET NAMES 'utf8';", $sqlact='w')
{
  // 取得PDO物件
  $dbh = get_pdo_object($sqlact);

  if(strtolower($sqlact) == 'w'){
      $stime = 10000;
  }else{
      $stime = 0;
  }

  try{
    $info = $dbh->getAttribute(\PDO::ATTR_SERVER_INFO);
    if (is_null($info)) {
        echo "---DB server has gone away\n";
    }

				// var_dump($sql);

				  $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				  $dbh->beginTransaction();
				  $dbh->exec($sql);
				  $dbh->commit();
				} catch (Exception $e) {
					// 請參考 postgresql error code 對應表 https://www.postgresql.org/docs/9.4/static/errcodes-appendix.html
					$debug_message = "[runSQLtransactions ERROR:".$dbh->errorCode()."]";
					error_log( date("Y-m-d H:i:s").' '.$debug_message.' SQL:'.$sql);
					echo $debug_message;

					$dbh->rollBack();
					echo "Failed: " . $e->getMessage();
					return(0);
				}
			 // 設定讀寫分離，需於寫入時等待約 10 亳秒的時間讓MASTER DB資料寫入SLAVE DB
			 usleep($stime);
			 $dbh = null;

	return(1);
}
// ---------------------------------------------------------------------


// ---------------------------------------------------------------------
// 紀錄程式
// example: memberlog2db('mtchang','login','info');
// example: memberlog2db('使用者','服務','訊息等級','訊息內容');
// 預設會紀錄 client ip , client browser 指紋碼,
// ---------------------------------------------------------------------
function memberlog2db($who = 'guest', $service, $message_level, $message = NULL) {
    global $config;

        //$who = 'guest';
        //$service = 'login';
        //$message_level = 'info';

    // 定義log level所包含要記錄的訊息層級
    $log_level_list = [
    	 'debug' => [ 'notice', 'info',	'error', 'warning' ],
    	 'warning' => [ 'info', 'error', 'warning' ],
    	 'error' => [ 'info', 'error' ]
    ];

    $s = '';

    if(in_array($message_level,$log_level_list[$config['log_level']])){
			// 應用程式的訊息資訊
			$message = filter_var($message, FILTER_SANITIZE_MAGIC_QUOTES);

			// 操作人員的 web http remote ip
			if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$agent_ip = explode(',',$_SERVER['HTTP_X_FORWARDED_FOR'])['0'];
			}else{
				// $agent_ip = 'no_remote_addr';
					$agent_ip = '0.0.0.0';
			}

			// 操作人員使用的 browser 指紋碼, 有可能會沒有指紋碼. JS close 的時候會發生
			if(isset($_SESSION['fingertracker'])) {
				$fingertracker = $_SESSION['fingertracker'];
			}else{
				$fingertracker = 'no_fingerprinting';
			}

			// 執行的程式檔名 - client
			if(isset($_SERVER['SCRIPT_NAME'])){
				$script_name = filter_var($_SERVER['SCRIPT_NAME'], FILTER_SANITIZE_MAGIC_QUOTES);
			}else{
				$script_name = 'no_script_name';
			}

			// 瀏覽器資訊 - client
			if(isset($_SERVER['HTTP_USER_AGENT'])) {
				$http_user_agent = filter_var($_SERVER['HTTP_USER_AGENT'], FILTER_SANITIZE_MAGIC_QUOTES);
			}else{
				$http_user_agent = 'no_http_user_agent';
			}

			// 使用者的 cookie 資訊
			if(isset($_SERVER['HTTP_COOKIE'])) {
				$http_cookie = filter_var($_SERVER['HTTP_COOKIE'], FILTER_SANITIZE_MAGIC_QUOTES);
			}else{
				$http_cookie = 'no_cookie';
			}

			// 使用 $_GET 的傳入網址
			if(isset($_SERVER['QUERY_STRING'])) {
				$query_string = filter_var($_SERVER['QUERY_STRING'], FILTER_SANITIZE_MAGIC_QUOTES);
			}else{
				$query_string = 'no_query_string';
			}

			$sql = 'INSERT INTO "root_memberlog" ("who", "service", "message_level" , "agent_ip", "message", "fingerprinting_id", "script_name", "http_user_agent", "http_cookie", "query_string")'.
			" VALUES ('$who', '$service', '$message_level' , '$agent_ip', '$message', '$fingertracker', '$script_name', '$http_user_agent', '$http_cookie', '$query_string');";
			//var_dump($sql);

			$s = runSQL($sql, 0,'w');
			// var_dump($s);
		}
	return($s);
}
// syslog2db('mtchang','login','info');
// ---------------------------------------------------------------------

// ---------------------------------------------------------------------
//20180626 yaoyuan紀錄程式
function memberlogtodb($who = 'guest', $service, $message_level, $message = NULL,$target_user=NULL,$message_log=NULL,$site='f',$sub_service=NULL) {
		global $config;

		//$who = 'guest';
		//$service = 'login';
		//$message_level = 'info';

		// 定義log level所包含要記錄的訊息層級
    $log_level_list = [
    	 'debug' => [ 'notice', 'info',	'error', 'warning' ],
    	 'warning' => [ 'info', 'error', 'warning' ],
    	 'error' => [ 'info', 'error' ]
    ];

		$s = '';

		if(in_array($message_level,$log_level_list[$config['log_level']])){
			// 應用程式的訊息資訊
			$message = filter_var($message, FILTER_SANITIZE_MAGIC_QUOTES);

			// 操作人員的 web http remote ip
			if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$agent_ip = explode(',',$_SERVER['HTTP_X_FORWARDED_FOR'])['0'];
			}else{
				// $agent_ip = 'no_remote_addr';
					$agent_ip = '0.0.0.0';
			}

			// IP所在區域
			$curl_ip_data = curl_ip_region($agent_ip);
			foreach($curl_ip_data as $v){
				if(isset($v['country_en']) AND $v['country_en'] != ''){
					$ip_location = $v['country_en']." ".$v['city_en'];
				}else{
					$ip_location = 'no_ip_region';
				}
			}

			// 操作人員使用的 browser 指紋碼, 有可能會沒有指紋碼. JS close 的時候會發生
			if(isset($_SESSION['fingertracker'])) {
				$fingertracker = $_SESSION['fingertracker'];
			}else{
				$fingertracker = 'no_fingerprinting';
			}

			// 執行的程式檔名 - client
			if(isset($_SERVER['SCRIPT_NAME'])){
				$script_name = filter_var($_SERVER['SCRIPT_NAME'], FILTER_SANITIZE_MAGIC_QUOTES);
			}else{
				$script_name = 'no_script_name';
			}

			// 瀏覽器資訊 - client
			if(isset($_SERVER['HTTP_USER_AGENT'])) {
				$http_user_agent = filter_var($_SERVER['HTTP_USER_AGENT'], FILTER_SANITIZE_MAGIC_QUOTES);
			}else{
				$http_user_agent = 'no_http_user_agent';
			}

			// 使用者的 cookie 資訊
			if(isset($_SERVER['HTTP_COOKIE'])) {
				$http_cookie = filter_var($_SERVER['HTTP_COOKIE'], FILTER_SANITIZE_MAGIC_QUOTES);
			}else{
				$http_cookie = 'no_cookie';
			}

			// 使用 $_GET 的傳入網址
			if(isset($_SERVER['QUERY_STRING'])) {
				$query_string = filter_var($_SERVER['QUERY_STRING'], FILTER_SANITIZE_MAGIC_QUOTES);
			}else{
				$query_string = 'no_query_string';
			}

			$sql = 'INSERT INTO "root_memberlog" ("who", "service", "message_level" , "agent_ip", "message", "fingerprinting_id", "script_name", "http_user_agent", "http_cookie", "query_string",
				"target_users","message_log","site","sub_service","ip_region")'.
			" VALUES ('$who', '$service', '$message_level' , '$agent_ip', '$message', '$fingertracker', '$script_name', '$http_user_agent', '$http_cookie', '$query_string',
				'$target_user', :message_log, '$site','$sub_service','$ip_location');";
			// echo($sql);

			$s = runSQLall_prepared($sql, [':message_log' => $message_log]);

			// var_dump($s);
		}

	return($s);
}
// ---------------------------------------------------------------------

// curl IP到IP來源API，透過MAXMIND做查詢IP所在地
// 送到IP來源API的IP須包成陣列
function curl_ip_region($user_ip_data){
	global $config;

	$ip_to_array = array($user_ip_data);

    $header = ['Content-type: application/x-www-form-urlencoded;charset=utf-8'];

    $ch = curl_init();
    $options = array(
        CURLOPT_URL				=> $config['ip_region_url'], // 設定欲抓取的網址
        CURLOPT_HTTPHEADER      => $header, // 設置一個header中傳輸內容的數組
        CURLOPT_CUSTOMREQUEST   => 'POST',  // post
        CURLOPT_POSTFIELDS		=> http_build_query($ip_to_array), // post參數
        CURLOPT_SSL_VERIFYPEER  => false, // 規避ssl的檢查
        CURLOPT_RETURNTRANSFER  => true, // 只傳回結果，不輸出在畫面上
        CURLOPT_TIMEOUT         => 30 // 允許執行的最長秒數
    );

    curl_setopt_array($ch,$options);
    $curl_result = curl_exec($ch);

    if($curl_result == false){
        echo curl_error($ch);
        exit();
    };
    curl_close($ch);

    return json_decode($curl_result,true);
};

// ---------------------------------------------------------------------
// 紀錄程式
// example: cashflowlog2db('mtchang','login','info');
// example: cashflowlog2db('使用者','服務','訊息等級','訊息內容');
// 預設會紀錄 client ip , client browser 指紋碼,
// ---------------------------------------------------------------------
function cashflowlog2db($who = 'guest', $service, $message_level, $message = NULL, $log = []) {

		//$who = 'guest';
		//$service = 'login';
		//$message_level = 'info';

		// 應用程式的訊息資訊
		$message = filter_var($message, FILTER_SANITIZE_MAGIC_QUOTES);

		// 操作人員的 web http remote ip
		if(isset($_SERVER["REMOTE_ADDR"])) {
			$agent_ip = $_SERVER["REMOTE_ADDR"];
		}else{
			$agent_ip = 'no_remote_addr';
		}

		// 操作人員使用的 browser 指紋碼, 有可能會沒有指紋碼. JS close 的時候會發生
		if(isset($_SESSION['fingertracker'])) {
			$fingertracker = $_SESSION['fingertracker'];
		}else{
			$fingertracker = 'no_fingerprinting';
		}

		// 執行的程式檔名 - client
		if(isset($_SERVER['SCRIPT_NAME'])){
			$script_name = filter_var($_SERVER['SCRIPT_NAME'], FILTER_SANITIZE_MAGIC_QUOTES);
		}else{
			$script_name = 'no_script_name';
		}

		// 瀏覽器資訊 - client
		if(isset($_SERVER['HTTP_USER_AGENT'])) {
			$http_user_agent = filter_var($_SERVER['HTTP_USER_AGENT'], FILTER_SANITIZE_MAGIC_QUOTES);
		}else{
			$http_user_agent = 'no_http_user_agent';
		}

		// 使用者的 cookie 資訊
		if(isset($_SERVER['HTTP_COOKIE'])) {
			$http_cookie = filter_var($_SERVER['HTTP_COOKIE'], FILTER_SANITIZE_MAGIC_QUOTES);
		}else{
			$http_cookie = 'no_cookie';
		}

		// 使用 $_GET 的傳入網址
		if(isset($_SERVER['QUERY_STRING'])) {
			$query_string = filter_var($_SERVER['QUERY_STRING'], FILTER_SANITIZE_MAGIC_QUOTES);
		}else{
			$query_string = 'no_query_string';
		}

		$log_json_str = json_encode($log);

		$sql = 'INSERT INTO "root_cashflowlog" ("who", "service", "message_level" , "agent_ip", "message", "fingerprinting_id", "script_name", "http_user_agent", "http_cookie", "query_string", "log")'.
		" VALUES ('$who', '$service', '$message_level' , '$agent_ip', '$message', '$fingertracker', '$script_name', '$http_user_agent', '$http_cookie', '$query_string', '$log_json_str');";
		// echo $sql;

		$s = runSQL($sql, 0,'w');
		// var_dump($s);

	return($s);
}
// syslog2db('mtchang','login','info');
// ---------------------------------------------------------------------




// ---------------------------------------------------------------------
// 紀錄行為, 如果有 fingerprinting 的話 , 使用 member log 函式, 紀錄使用者的行為. but 當 logger 成長太快速時, 需要修正.
// 要放在每個頁面才可以, 否則會紀錄不正確的頁面。
// ---------------------------------------------------------------------
/*
function webuser_behavior_log($logger=NULL) {

	//$who 誰在那個頁面操作
	if(isset($_SESSION['member']->account)){
		$account = 	$_SESSION['member']->account;
	}else{
		$account = 'guest';
	}
	$service = 'behavior';
	$message_level = 'info';
	// 傳入想要寫入的訊息, 沒有的話就是空
	// $logger = $logger;
	$r = memberlog2db("$account","$service","$message_level", "$logger");

	return($r);
}
*/
// ---------------------------------------------------------------------


// ----------------------------------------------------------------------
// 產生轉帳用transaction id
// $action: w:代表提款
// 					d:代表存款
// 					md:後台人工存款
// 					mw:後台人工提款
// ----------------------------------------------------------------------
function get_transaction_id($acc, $action)
{
  $id = $action.date("YmdHis").$acc.str_pad(mt_rand(1,999),3,'0',STR_PAD_LEFT);

  return $id;
}


// ----------------------------------------------------------------------
// 檢查 CSRF token 是否正確 , 對應的 function 為 csrf_token_make()
// 有兩個對應的 function
// csrf_token_make() 使用在傳送端 client
// csrf_action_check() 使用在接收端 server
/*
// 檢查產生的 CSRF token 是否存在 , 錯誤就停止使用
$csrftoken_ret = csrf_action_check();
if($csrftoken_ret['code'] != 1) {
  //var_dump($csrftoken_ret);
  die($csrftoken_ret['messages']);
}
*/
// ----------------------------------------------------------------------
function csrf_action_check($form_array=array()) {

	// 只接收指定來源的 script name, 預設為空陣列, 不限制來源網頁!!
	// $form_array = array("a.php", "b.php", "c.php", "d.php");
	// $form_array = array('/gpk2/home2.php');
	//$form_array = array();

  // 檢查所帶入的 CSRF token 是否存在 ,且不是空值, 需要有登入才可以
  if(isset($_POST['csrftoken']) AND !empty($_POST['csrftoken']) ) {
    // 從 $_POST['csrftoken'] 解出正確的 $csrftoken_valid
    $csrftoken = $_POST['csrftoken'];
		//$csrftoken = "eyJSRU1PVEVfQUREUiI6IjExNC4zMy4yMDEuMjQyIiwiUEhQX1NFTEYiOiJcL2dwazJcL2hvbWUucGhwIiwiZGF0YSI6bnVsbCwiZmluZ2VydHJhY2tlciI6IjY2NjA4NzQ2OCJ9_1883f43e672ed87db87b027f0a0d98e5187e370e";
		//var_dump($csrftoken);

		// 加上個人化資訊 salt , 避免所有人都一樣的 csrf
		$csrf_salt = '5566';
		if(isset($_SESSION['member']->salt)) {
			$csrf_salt = $_SESSION['member']->salt;
		}

		// 加上特殊 key, 避免 jwt sha1 编码被识破, $jwt_csrftoken_key = date('Y-m-d H:m:s');
		// $jwt_csrftoken_key = gmdate('Y-m-d_H');
		// 以每日改變一次,避免一直產生錯誤但同時風險就是一天
		$jwt_csrftoken_key = gmdate('Y-m-d').$csrf_salt;
		// var_dump($jwt_csrftoken_key);

		$jwt = explode("_", $csrftoken);
		// var_dump($jwt);
		// SHA1 驗證
		$csrftoken_check_sha1 = sha1($jwt[0].$jwt_csrftoken_key);
		// var_dump($csrftoken_check_sha1);
		// jwt[1] --> 傳來的 hash code


		// 檢查 JWT hash 是否相同, 相同才繼續
		if(isset($jwt[1]) AND $csrftoken_check_sha1 == $jwt[1]) {

			$client_data = json_decode(base64_decode($jwt[0]));
			// var_dump($client_data);
			$ret['code']      = 1;
			$ret['messages'] = 'token correct.';
			$ret['debug']  = 'CSRF token correct. jwtkey='.$jwt_csrftoken_key.' token='.$csrftoken;

			if($form_array == array() OR in_array($client_data->PHP_SELF , $form_array )) {
				$ret['code']      = 1;
				$ret['messages'] = 'token correct, data correct.';
				$ret['debug']  = 'CSRF token correc, data correct jwtkey='.$jwt_csrftoken_key.' token='.$csrftoken;
			}else{
				// 只接收指定來源的 script name , 空 ARRAY
				$ret['code']      = 404;
				// $ret['messages'] = 'token correct, data error.';
				$ret['messages'] = '<a href="#" onClick="window.location.reload()">你好像输入了错误的资料。</a>';
				$ret['debug']  = 'CSRF token correct,  data error '.$client_data->PHP_SELF.' jwtkey='.$jwt_csrftoken_key.' token='.$csrftoken;
			}

		}else{
			$ret['code']      = 0;
			$ret['messages'] = '你休息太久没动作，请重新整理页面。';
			// $ret['messages'] = '<a href="#" onClick="window.location.reload()">你休息太久没动作，请重新整理页面。</a>';

			//$ret['messages'] = '<a href="#" onClick="window.location.reload()">You rest too long, please refresh the page.</a>';
			$ret['debug']  = 'CSRF token hashcode error!! jwtkey='.$jwt_csrftoken_key.' token='.$csrftoken;
		}

  }else{
    // 請傳入 $_POST['csrftoken'] 變數, 以及 $_SESSION['csrftoken_valid'] 的 session 值
    $ret['code']      = 500;
    $ret['messages'] = 'token does not exist.';
	$ret['debug']  = 'CSRF token does not exist!!';
  }

  return($ret);
}
// ----------------------------------------------------------------------


// ----------------------------------------------------------------------
// 產生 CSRF token 對應的 function 為 csrf_action_check()
// 有兩個對應的 function
// csrf_token_make() 使用在傳送端 client
// csrf_action_check() 使用在接收端 server
/*
// 產生 csrf token , $csrftoken 需要透過這個傳遞到對應的 action page post 內
$csrftoken = csrf_token_make();
// 可以放傳遞的變數 json ,做資料的驗證
$csrftoken = csrf_token_make($json_data);
*/
// ----------------------------------------------------------------------
function csrf_token_make($json_data=NULL){
	global $program_start_time;
	// 加上特殊 key, 避免 jwt sha1 编码被识破, $jwt_csrftoken_key = date('Y-m-d H:m:s');
	//$jwt_csrftoken_key = gmdate('Y-m-d_H');
	// 加上個人化資訊 salt , 避免所有人都一樣的 csrf
	$csrf_salt = '5566';
	if(isset($_SESSION['member']->salt)) {
		$csrf_salt = $_SESSION['member']->salt;
	}

	// 以每日改變一次,避免一直產生錯誤但同時風險就是一天
	$jwt_csrftoken_key = gmdate('Y-m-d').$csrf_salt;

	// DATA: 遠端IP + 浏览器 ID
	$client_data_array['REMOTE_ADDR']		= explode(',',$_SERVER['HTTP_X_FORWARDED_FOR'])['0'];
	$client_data_array['PHP_SELF'] 			= $_SERVER['PHP_SELF'];
	$client_data_array['data'] 					= $json_data;
	if(isset($_SESSION['fingertracker'])) {
		$client_data_array['fingertracker'] = $_SESSION['fingertracker'];
	}else{
		$client_data_array['fingertracker'] = 'NoFingerID';
	}
	$client_data_encode = json_encode($client_data_array);

	// BASE64
	$csrftoken_orig = base64_encode($client_data_encode);
	// SHA1
	$csrftoken_orig_sha1 = sha1($csrftoken_orig.$jwt_csrftoken_key);
	// JWT String
	$csrftoken 			= $csrftoken_orig.'_'.$csrftoken_orig_sha1;
	//echo "<br><br><br><br><br><hr>";
	//var_dump($jwt_csrftoken_key);
	//var_dump($csrftoken_orig);
	//var_dump($client_data_encode);
	//var_dump($csrftoken);

	// 在 post 看到的 CSRF
	return($csrftoken);
}
// ----------------------------------------------------------------------


// ----------------------------------------------------------------------
// CSRF 全域变数, 预先载入了. 位置很重要. 紧接著产生之后
// lib_common.php 创造了 CSRF token function, 產生 csrf token , $csrftoken 需要透過這個傳遞到對應的 action page post 內
$csrftoken = csrf_token_make();
//var_dump($csrftoken);
// ----------------------------------------------------------------------

?>
