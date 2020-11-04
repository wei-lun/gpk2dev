<?php
// ----------------------------------------------------------------------------
// Features:	前台 -- 2階段驗證
// File Name:   member_authentication_action.php
// Author:		Mavis
// Related:
// Log:
//
// ----------------------------------------------------------------------------

// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

require_once dirname(__FILE__) ."/in/PHPGangsta/GoogleAuthenticator.php";

require_once dirname(__FILE__) ."/member_authentication_lib.php";

// 宣告兩階段驗證物件
$ga = new PHPGangsta_GoogleAuthenticator();

// 回上一頁，clear session
if(isset($_GET['s'])){
	clear_session();
}

// action
$action = isset($_GET['a']) ? filter_string($_GET['a'],"string") : "";

// 會員id
$id_query = isset($_POST['i']) ? filter_string($_POST['i'],"string") : "";
// 啟用2fa 問題
$twofa_question = isset($_POST['questions']) ? filter_string($_POST['questions'],"string") : "";
// 啟用2fa 答案
$twofa_ans = isset($_POST['twofa_ans']) ? filter_string($_POST['twofa_ans'],"string") : "";
// 驗證碼
$verify_code = isset($_POST['verify_code']) ? filter_string($_POST['verify_code'],"string") : "";
// 金鑰
$secret_id = isset($_POST['secret_id']) ? filter_string($_POST['secret_id'],"string") : "";
// 停用答案
$twofa_disable_ans = isset($_POST['twofa_disable_ans']) ? filter_string($_POST['twofa_disable_ans'],"string") : "";


// 取會員帳號，user 掃QR CODE會自動產生與會員同名稱的帳戶
$get_member_account = member_data($id_query);

if($action == 'refresh' AND isset($_SESSION['member'])){
	// 重新產生
	$twofa_generate_data = generate_secret($ga,$get_member_account[1]->account);
	echo json_encode($twofa_generate_data);
	return;
}

// 開啟2FA
if($action == 'save_factor' AND isset($_SESSION['member']->id)){

	if($twofa_ans == '' or $verify_code == ''){
		// $tr['Required field'] 必填欄位
		$logger = $tr['Required field'];
		echo "<script>alert('$logger');</script>";
		return;
	}

	$verify_result = verify_secret($secret_id, $verify_code,$ga);
	// 判斷驗證碼，正確：寫db，reload　；錯誤：提示驗證碼錯誤
	if($verify_result['check_result']){

		// 2FA DB是否有資料
		$member_auth_sql = sql_member_authentication($id_query);
		if($member_auth_sql[0] == 0){
			// 新增
			$factor_sql = insert_member_authentication($id_query,json_encode($twofa_question),$secret_id,json_encode($twofa_ans));
		}else{
			// 更新(關->開)
			$factor_sql = update_member_authentication($id_query,json_encode($twofa_question),$secret_id,json_encode($twofa_ans));
		}
		// $tr['Successfully enabled'] 啟用成功
		$logger = $tr['Successfully enabled'];
		echo "<script>alert('$logger');window.location.href='home.php';</script>";
		return;
	}else{
		// $tr['The verification code is incorrect, please re-enter'] 驗證碼錯誤，請重新輸入!
		$logger = $tr['The verification code is incorrect, please re-enter'];
		echo "<script>alert('$logger');</script>";
		return;
	 }

}

// 停用，填停用答案
if($action == 'factor_disable' AND isset($_SESSION['member']->id)){

	// 停用答案沒填
	if($twofa_disable_ans == ''){
		// $tr['Please fill in the answer set at the beginning, please contact customer service staff forgot!'] = 請填入當初設定的答案，忘記請洽客服人員!
		$logger = $tr['Please fill in the answer set at the beginning, please contact customer service staff forgot!'];
		echo "<script>alert('$logger');</script>";
		return;
	}

	// 比對跟2FA DB內的 two_fa_ans 跟user輸入的是否一致
	$check_dis_ans = sql_member_authentication($id_query);

	// 改狀態為停用
	if($twofa_disable_ans == json_decode($check_dis_ans[1]->two_fa_ans,true)){
		update_factor_disable($id_query);
		// $tr['Successfully disabled'] = 停用成功
		$logger = $tr['Successfully disabled'];
		echo "<script>alert('$logger');window.location.href='home.php';</script>";
		return;
	}else{
		// 请填入当初设定的答案，忘记请洽客服人员!
		$logger = $tr['Please fill in the answer set at the beginning, please contact customer service staff forgot!'];
		echo "<script>alert('$logger');location.reload();</script>";
		return;
	}

}

?>