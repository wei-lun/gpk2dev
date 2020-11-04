<?php
// redis
function connect_redis(){
	global $redisdb;
	if(isset($redisdb['db'])) {
		$db = $redisdb['db'];
	}else{
		die('No select RedisDB');
	}

	$redis = new Redis();
	// 2 秒 timeout
	if($redis->pconnect($redisdb['host'], 6379, 1)) {
		// success
		if($redis->auth($redisdb['auth'])) {
			// echo 'Authentication Success';
		}else{
			return(0);
			die('Redisdb authentication failed');
		}
	}else{
		// error
		return(0);
		die('Redisdb Connection Failed');
	}
	// 選擇 DB , member 使用者自訂的 session 放在 db 2 ($redisdb['db'] 替代)
	$redis->select($db);
	// echo "Server is running: ".$redis->ping();
	return $redis;
}

// 判斷redis db內是否有該帳號資料
function check_redis_member_exist_data($account){
	// 連結redis
	$connect = connect_redis();
	$combine = [];
	// key 組成
	$key_name = date("Ymd").$account;
	$exist_account = $connect->exists($key_name,'account');
	$combine['key_name'] = $key_name;
	
	// key存活時間
	$live_time 	= time();
	$expire = 14400;
	$expire_time = $live_time + $expire;
	$connect->expireAt($key_name,$expire_time);
	// $delete_attempt = $connect->delete($key_name);
	// var_dump($delete_attempt);die();
	// -----------------

	// phpredis db沒有該帳號
	if(!$exist_account){

		// 第一次錯誤的時間
		$first_attempt_time = gmdate('Y-m-d H:i:s',time() + '-4' * 3600);
		// 初始帳號錯誤次數
		$acc_counter = 1;

		// 帳號
		$connect->hSet($key_name,'account',$account);
		// 帳號錯誤次數
		$connect->hSet($key_name,'counter',$acc_counter);
		// 錯誤時間
		$connect->hSet($key_name,'error_time',$first_attempt_time);
		// 返回key的所有key value
		$combine['data'] = $connect->hGetAll($key_name);

	}else{
		// 錯誤次數+1
		// 帳號
		$add_new_count = $connect->hGet($key_name,"counter")+1; 
		$connect->hSet($key_name,'counter',$add_new_count);

		// 返回key的所有key value
		$combine['data'] = $connect->hGetAll($key_name);
	}
	return $combine;
}

// 判斷redis db內是否有該IP資料
function check_redis_member_ip_exist_data($ip){
	// 連結redis
	$connect = connect_redis();
	$combine = [];

	// key 組成
	$key_name = date("Ymd").$ip;
	$exist_ip = $connect->exists($key_name,'error_ip');
	$combine['key_name'] = $key_name;

	// key存活時間
	$live_time 	= time();
	$expire = 14400;
	$expire_time = $live_time + $expire;
	$connect->expireAt($key_name,$expire_time);
	// $delete_attempt = $connect->delete($key_name);
	// var_dump($delete_attempt);die();
	// -----------------

	// phpredis db沒有該ip
	if(!$exist_ip){

		// 第一次錯誤的時間
		$first_attempt_time = gmdate('Y-m-d H:i:s',time() + '-4' * 3600);
		// 初始IP錯誤次數
		$ip_counter = 1;

		// IP錯誤次數
		$connect->hSet($key_name,'ip_counter',$ip_counter);
		// 錯誤時間
		$connect->hSet($key_name,'error_time',$first_attempt_time);
		// ip
		$connect->hSet($key_name,'error_ip',$ip);

		// 返回key的所有key value
		$combine['data'] = $connect->hGetAll($key_name);

	}else{
		// 錯誤次數+1
		// ip
		$add_new_count_ip = $connect->hGet($key_name,"ip_counter")+1;
		$connect->hSet($key_name,'ip_counter',$add_new_count_ip);

		$get_ip_datas = IP_data($ip);
			
		if($get_ip_datas[1]->status == 1 AND $get_ip_datas[1]->counter == 0){
			// 客服手動開啟IP
			// 因為初始預設status = 1；counter = 0 ，所以剛開始會跑這段code
	
			// 重設IP錯誤次數
			$reset_ip_eeror_count = 1;
			$connect->hSet($key_name,'ip_counter',$reset_ip_eeror_count);

			$IP_dbdata = update_ip_db('0','1',$ip);
		}
		// 返回key的所有key value
		$combine['data'] = $connect->hGetAll($key_name);
	}
	return $combine;
}

// 計算時間
function calcu_time($eg_time){

    $now = gmdate('Y-m-d H:i:s',time() + '-4' * 3600); // 現在時間
    
	// 現在時間 - 登入錯誤的時間 
	$counting_time = floor((strtotime($now) - strtotime($eg_time))/ (60)); // 從現在到第一次登入錯誤時間，過幾分鐘

    $time_data = array(
        'current' => $now,
		'counting_time' => $counting_time
    );

    return $time_data;
}

// 有登入成功，清除該帳號redis db record
function clear_record($account,$ip){
	$connect = connect_redis();
	// $del = $connect->hDel($account,"account","counter","ip_counter","error_time");
	// $check = $connect->hGet($account,"account");

	// account
	$current_account = date("Ymd").$account;
	$del[0] = $connect->delete($current_account);

	// ip
	$current_ip = date("Ymd").$ip;
	$del[1] = $connect->delete($current_ip);

	return $del;
}

function get_ip_data($account,$ip_address,$count){
	// 後台登入錯誤記錄管理
	global $protalsetting;

	// 取IP資料
	$ip_result = IP_data($ip_address);
	
	// 有該IP
	if($ip_result[0] == 1){
		$count_ip_db = $ip_result[1]->counter;

		if($count >= $protalsetting['ip_error_count'] AND $ip_result[1]->status == 1){		
			// 15分鐘內，錯誤次數>20，封鎖IP，由客服在後台開啟
			$ip_db = update_ip_db($count,'0',$ip_address);
		}else{
			// 更新錯誤次數
			$ip_db = update_ip_db($count,'1',$ip_address);
		}

	}else{
		// insert
		$ip_db = attempt_ip_insert($ip_address,$count);
	}
}

// 檢查是否真有此帳號
function check_member_exists($account){
	$sql = <<<SQL
		SELECT * FROM root_member 
			WHERE account = '{$account}' 
SQL;
  	$result = runSQLall($sql);
  	return $result;
}

// 1.封鎖帳號15分鐘
// 2.解除帳號封鎖
function check_lock_account($account,$time,$account_status){
	$sql = <<<SQL
		UPDATE root_member 
			SET status = '{$account_status}',
				lastlogin = '{$time}'
			WHERE account = '{$account}'
SQL;
	$result = runSQLall($sql);
	return $result;
}

// 取DB IP資料
function IP_data($ip){
	$sql = <<<SQL
		SELECT * FROM root_attempt_login 
		WHERE ip_address = '{$ip}' 
SQL;
	$result = runSQLall($sql);
	return $result;
}

// IP寫進db
function attempt_ip_insert($ip,$count){
	$sql = <<<SQL
		INSERT INTO root_attempt_login (ip_address,counter,status) 
		VALUES ('{$ip}','{$count}', '1')
SQL;
	$result = runSQLall($sql);
	return $result;
}

// 1.封鎖IP
// 2.更新錯誤次數
// 3.登出成功後，原本的IP DB錯誤次數歸0和狀態開啟
function update_ip_db($count,$ip_status,$ip){
	$sql = <<<SQL
		UPDATE root_attempt_login
		SET counter = '{$count}',
			status = '{$ip_status}'
		WHERE ip_address = '{$ip}'
SQL;
	// var_dump($sql);die();
	$result = runSQLall($sql);
	return $result;
}

// db有此帳號，且密碼為空
function check_import_account($account){
	$sql = <<<SQL
		SELECT * FROM root_member 
			WHERE account = '{$account}' 
				AND passwd = ''
				AND (therole = 'A' OR therole = 'M')
SQL;
	$result = runSQLall($sql);
	return $result;
}

// 未登入成功
// 帳號封鎖、IP封鎖都以15分鐘內做時限
// 用於封鎖帳號和IP都開啟，或者開啟帳號封鎖
function login_attempt($attempt_account,$ip){
	global $protalsetting;

	// 連結redis
	$connect = connect_redis();

	$check_account['status'] = true;
		
	// 錯誤要在15 分鐘內發生
	// $dead_line = 15;

	// 檢查該會員是否已被封鎖
	$get_data = check_member_exists($attempt_account);

	// ip資料
	$get_ip_datas = IP_data($ip);

	// 檢查redis db內有無該帳號、IP資料
	$check_acc = check_redis_member_exist_data($attempt_account);
	$check_ip = check_redis_member_ip_exist_data($ip);

	//	------------------------------------------
	// 計算時間
	$err_time = $connect->hGet($check_acc['key_name'],"error_time");  // 帳號初次登入錯誤時間
	$err_time_ip = $connect->hGet($check_ip['key_name'],"error_time"); // IP出次登入錯誤時間

	$get_time_data_acc = calcu_time($err_time); // 帳號
	$get_time_data_ip = calcu_time($err_time_ip); // IP

	// 錯誤次數
	// 1.帳號錯誤次數
	$acc_error_count = $connect->hGet($check_acc['key_name'],"counter");

	// 2.ip錯誤次數
	// 把錯誤次數跟該帳號用的IP 同步到IP錯誤紀錄DB內
	$ip_error_count = $connect->hGet($check_ip['key_name'],"ip_counter"); 
	// ---------------------------------------------------------------------------

	if($protalsetting['ip_status'] == 'on' AND $protalsetting['account_status'] == 'on'){
		// ip封鎖 + account封鎖 = on
		// 通常封鎖順序: 帳號->IP
		
		// 帳號還沒被封鎖
		if($get_data[1]->status == 1){
			$check_account['status'] = false;
			
			// 15分鐘內
			if($get_time_data_acc['counting_time'] <= $protalsetting['account_lock_time']){
				// ip 
				get_ip_data($attempt_account,$ip,$ip_error_count);

				// user帳號錯誤次數 = 設定的帳號錯誤次數
				if($acc_error_count == $protalsetting['account_err_count'] AND $get_data[1]->status == 1){
					// 封鎖帳號
					$lock_account_time = gmdate('Y-m-d H:i',time() + '-4' * 3600);
					$to_lock_account = check_lock_account($attempt_account,$lock_account_time,'3');
				}
			}elseif($get_time_data_acc['counting_time'] >= $protalsetting['account_lock_time']){
				// 超過設定的時間 清除原本的資料，從新開始
				$IP_dbdata = update_ip_db('0','1',$ip);
				$to_clear = clear_record($attempt_account,$ip);
			}

		}elseif($get_data[1]->status == 3){
			// 帳號被封鎖了，還是繼續try，IP錯誤次數>IP設定次數=封鎖IP
			if($ip_error_count >= $protalsetting['ip_error_count'] AND $get_ip_datas[1]->status == 1){
				// 封鎖IP
				$IP_dbdata = update_ip_db($ip_error_count,'0',$ip);
				$check_account['status'] = false;
			}else{
				$IP_dbdata = update_ip_db($ip_error_count,'1',$ip);
				$check_account['status'] = true;
			}
			$check_account['status'] = false;
		}

	}elseif($protalsetting['ip_status'] == 'on' AND $protalsetting['account_status'] == 'off'){
		// ip封鎖 = on;
		// account封鎖 = off
		// ip被封鎖須由客服開啟，只要是同個IP，換別的帳號也封鎖

		// IP狀態=1(開啟)，而且15 mins內，一直亂try
		if($get_time_data_ip['counting_time'] <= $protalsetting['account_lock_time'] AND $get_ip_datas[1]->status == 1){

			// 錯誤次數>設定次數
			if($ip_error_count >= $protalsetting['ip_error_count']){
				// 封鎖IP
				$IP_dbdata = update_ip_db($ip_error_count,'0',$ip);
				$check_account['status'] = false;

			}else{
				$IP_dbdata = update_ip_db($ip_error_count,'1',$ip);
				$check_account['status'] = true;
			}

		}elseif($get_time_data_ip['counting_time'] >= $protalsetting['account_lock_time'] AND $get_ip_datas[1]->status == 1){
			// 超過設定的時間 清除原本的資料，從新開始
			$IP_dbdata = update_ip_db('0','1',$ip);
			$to_clear = clear_record($attempt_account,$ip);
		}

	}elseif($protalsetting['ip_status'] == 'off' AND $protalsetting['account_status'] == 'on'){
		// ip封鎖 = off; 帳號封鎖 = on
		// 被封鎖帳號15分鐘後會計算時間，是否解除封鎖
		// 可以換其他帳號try

		if($get_data[1]->status == 1){
			$check_account['status'] = false;

			// 15 分鐘內
			if($get_time_data_acc['counting_time'] <= $protalsetting['account_lock_time']){

				// user帳號錯誤次數 = 設定的帳號錯誤次數
				if($acc_error_count  == $protalsetting['account_err_count'] AND $get_data[1]->status == 1){
					// 封鎖帳號
					$lock_account_time = gmdate('Y-m-d H:i',time() + '-4' * 3600);
					$to_lock_account = check_lock_account($attempt_account,$lock_account_time,'3');
				}
			}elseif($get_time_data_acc['counting_time'] >= $protalsetting['account_lock_time']){
				// 超過設定的時間 清除原本的資料，從新開始
				$to_clear = clear_record($attempt_account,$ip);
			}

		}elseif($get_data[1]->status == 3){
			// 帳號被封鎖了
			$check_account['status'] = false;
		}
	}
	return $check_account;
}

// 檢查IP是否已被封鎖
function check_attempt_ip($account,$ip,$debug=0){

	global $protalsetting;
	// 連結redis
	$connect = connect_redis();

	if($debug == 1) {
		var_dump($_POST);
		var_dump($_SESSION);
		var_dump($account);
		var_dump($password);
		var_dump($login_force);
	}

	$ip_block_status['success'] = true;
	$ip_block_status['messages'] = '';

	// 取DB IP資料
	$get_ip_datas = IP_data($ip);
	
	// 有該IP資料
	if($get_ip_datas[0] == 1){

		// ip_status存在而且是On
		if(isset($protalsetting['ip_status']) AND $protalsetting['ip_status'] == 'on'){

			if(isset($protalsetting['ip_error_count'])){
				if($get_ip_datas[1]->status == 0 AND $get_ip_datas[1]->counter >= $protalsetting['ip_error_count']  AND $get_ip_datas[1]->counter != 0){
				
					$link = '<a href="contactus.php" target="_blank">客服</a>';
					// 網頁上顯示的錯誤訊息
					$show_logger = '此IP('.$ip.')登入错误次数过多，需联系'.$link.'处理。';
		
					// root_memberlog
					$logger = '此IP('.$ip.')登入错误次数过多，需联系客服处理。';
					$ip_block_status['success'] = false;
					$ip_block_status['messages'] = $show_logger;
		
					$msg = $logger;
					$msg_log = $logger;
					$sub_service = 'login';
					memberlogtodb('guest','member','error',"$msg",$account,"$msg_log",'f',$sub_service);

					return $ip_block_status;
				}
			}

			if($get_ip_datas[1]->status == 0 AND $get_ip_datas[1]->counter == 0){
				// 客服自行封鎖
				
				// 網頁上顯示的錯誤訊息
				$show_logger = '此IP('.$ip.')已被封锁。';
	
				// root_memberlog
				$logger = '此IP('.$ip.')已被封锁。';
				$ip_block_status['success'] = false;
				$ip_block_status['messages'] = $show_logger;
	
				$msg = $logger;
				$msg_log = $logger;
				$sub_service = 'login';
				memberlogtodb('guest','member','error',"$msg",$account,"$msg_log",'f',$sub_service);
	
				// 2019-8-28
				$IP_dbdata = update_ip_db('0','0',$ip);
				$to_clear = clear_record($account,$ip);
	
			}else{
				$ip_block_status['success'] = true;
			}

		}elseif(!isset($protalsetting['ip_status']) OR $protalsetting['ip_status'] == 'off'){
			// ip_status == off
			if($get_ip_datas[1]->status == 0 AND $get_ip_datas[1]->counter == 0){
				// 客服自行封鎖
				
				// 網頁上顯示的錯誤訊息
				$show_logger = '此IP('.$ip.')已被封锁。';
	
				// root_memberlog
				$logger = '此IP('.$ip.')已被封锁。';
				$ip_block_status['success'] = false;
				$ip_block_status['messages'] = $show_logger;
	
				$msg = $logger;
				$msg_log = $logger;
				$sub_service = 'login';
				memberlogtodb('guest','member','error',"$msg",$account,"$msg_log",'f',$sub_service);
	
				// 2019-8-28
				$IP_dbdata = update_ip_db('0','0',$ip);
				$to_clear = clear_record($account,$ip);
	
			}else{
				$ip_block_status['success'] = true;
			}
		}
	}else{
		// 沒IP登入錯誤的資料
		$ip_block_status['success'] = true;
	}

	return $ip_block_status;
}

?>