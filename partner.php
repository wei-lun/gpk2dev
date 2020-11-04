<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 合作伙伴
// File Name:	partner.php
// Author:		Yaoyuan
// Related:   partner_casino.php(娛樂城)、partner_ecshoop.php(商城)
// Log:
// 2017.12.22
// ----------------------------------------------------------------------------


// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

//var_dump($_SESSION);
//var_dump($_POST);

// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------
if($config['website_type'] == 'ecshop'){
    include 'partner_ecshop.php';
  }else{
    include 'partner_casino.php';
  }

?>