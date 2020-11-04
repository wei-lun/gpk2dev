<?php
$current_domainname	= $_SERVER["HTTP_HOST"];

// -------------------------------------------------------------------------
// 前台 + 後台 -- 每日統計報表用的變數
// 相關檔案名稱： statistics_daily_report_lib.php
// -------------------------------------------------------------------------
// 資料庫變數，紀錄投注紀錄專用的資料庫
// $stats_config['mg_bettingrecords_tables'] = 'gpk_mg_bettingrecords';
// 因為需要開發測試, 所以使用模擬的資料庫來做大量的資料,
$stats_config = [];
// MG 投注紀錄表
$stats_config['mg_bettingrecords_tables'] = $config['projectid'].'_mg_bettingrecords';
// PT 投注紀錄表
$stats_config['pt_bettingrecords_tables'] = $config['projectid'].'_pt_bettingrecords';
// MEGA 投注紀錄表
$stats_config['mega_bettingrecords_tables'] = $config['projectid'].'_mega_bettingrecords';
// EC 投注紀錄表
$stats_config['ec_bettingrecords_tables'] = $config['projectid'].'_ec_bettingrecords';
// IG SST 投注紀錄表
$stats_config['igsst_bettingrecords_tables'] = $config['projectid'].'_igsst_bettingrecords';
// IG HKT 投注紀錄表
$stats_config['ighkt_bettingrecords_tables'] = $config['projectid'].'_ighkt_bettingrecords';
// -----------------------------------------------------------------------------


// start session
if(session_status() === PHP_SESSION_ACTIVE) session_destroy();
$sessionname = session_name($config['projectid']);
session_set_cookie_params(ini_get("session.gc_maxlifetime"), '/', $currency_basedomain);
session_start();

// -----------------------------------------------------------------------------
// 讀取多網域時非主站網域的設定值
// -----------------------------------------------------------------------------

// 前後台通用設定檔
require_once dirname(__FILE__) ."/lib_common.php";

// 讀取多網域變數
$currency_domainbase = explode(':',$currency_basedomain)[0];
$current_domain = explode(':',$current_domainname)[0];
$websiteconf = [];

$websiteconf_result = runSQLall('SELECT configdata,stylesettingid FROM site_subdomain_setting WHERE open=\'1\' AND domainname=\''.$currency_domainbase.'\';','r');
if($websiteconf_mode == 'file') $websiteconf_result[0] = 0;
if($websiteconf_result[0] == 1){
	$websiteconf_db = json_decode($websiteconf_result[1]->configdata,'true');
  foreach($websiteconf_db as $key => $configdata){
		if($configdata['style']['desktop']['suburl'] == $currency_suburl OR $configdata['style']['mobile']['suburl'] == $currency_suburl){
			$site_styles = $configdata['style'];
			unset($configdata['style']);
			foreach($site_styles as $site_style => $site_config){
				$suburl = $site_config['suburl'];
				unset($site_config['suburl']);
				$websiteconf[$suburl.'.'.$currency_domainbase] = array_merge($configdata,$site_config);
				$basepathway = (isset($pathway)) ? $pathway[1].'/' : '';
				if($site_style == 'desktop'){
					$websiteconf[$suburl.'.'.$currency_domainbase]['site_style'] = 'desktop';
					$websiteconf[$suburl.'.'.$currency_domainbase]['desktop_url']  = $suburl.'.'.$currency_basedomain.'/'.$basepathway;
					$websiteconf[$suburl.'.'.$currency_domainbase]['mobile_url']   = $site_styles['mobile']['suburl'].'.'.$currency_basedomain.'/'.$basepathway;
				}elseif($site_style == 'mobile'){
					$websiteconf[$suburl.'.'.$currency_domainbase]['site_style'] = 'mobile';
					$websiteconf[$suburl.'.'.$currency_domainbase]['desktop_url']  = $site_styles['desktop']['suburl'].'.'.$currency_basedomain.'/'.$basepathway;
					$websiteconf[$suburl.'.'.$currency_domainbase]['mobile_url']   = $suburl.'.'.$currency_basedomain.'/'.$basepathway;
				}else{}
				//子網域uisetting
				if(isset($configdata['component']))
					$websiteconf[$suburl.'.'.$currency_domainbase]['component']   = $configdata['component'];
				//主網域uisetting
				if(!is_null($websiteconf_result[1]->stylesettingid))
					$websiteconf[$suburl.'.'.$currency_domainbase]['stylesettingid']   = $websiteconf_result[1]->stylesettingid;
			}
		}
  }
}else{
  $websiteconf_file = dirname(__FILE__) ."/websiteconf.php";
  if(file_exists($websiteconf_file)) {
  	require_once $websiteconf_file;
  }
}

if(isset($websiteconf[$current_domain])){
	if(isset($websiteconf[$current_domain]['projectid'])) unset($websiteconf[$current_domain]['projectid']);
	foreach($websiteconf[$current_domain] as $key => $val){
		if($val != '') $config[$key] = $val;
	}
}

// ----------------------------------------------------------------------------
// 讀取廣告元件(若沒有設定則讀取預設)
// ----------------------------------------------------------------------------
// 前後台通用設定檔
require_once dirname(__FILE__) ."/lib_uisetting.php";

$result = get_uisetting();
$ui_data = $result['ui_data'];
$ui_link = $result['ui_link'];

// ----------------------------------------------------------------------------
// 依据 Client 装置的不同, 切换对应的网址开头
// ----------------------------------------------------------------------------

// 判断装置类型, 转跳到对应的位置
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443 || getenv('HTTP_X_FORWARDED_PROTO') === 'https') ? "https://" : "http://";
if($system_mode == 'developer') {
	$sub_sitepath = explode('/',$_SERVER['DOCUMENT_URI'])[1];
	$_SERVER['DOCUMENT_URI'] = preg_replace("/^(\/$sub_sitepath\/)/i", '', $_SERVER['DOCUMENT_URI']);
}else{
	$_SERVER['DOCUMENT_URI'] = preg_replace("/^(\/)/i", '', $_SERVER['DOCUMENT_URI']);
}
if(isset($_SERVER['QUERY_STRING']) AND $_SERVER['QUERY_STRING'] != '' ){
	$currency_uri = $_SERVER['DOCUMENT_URI'].'?'.$_SERVER['QUERY_STRING'];
}else{
	$currency_uri = $_SERVER['DOCUMENT_URI'];
}

if ( $config['site_style'] == 'mobile' ) {
	$_SESSION['site_mode'] = 'mobile';
	$config['website_baseurl'] = $protocol.$config['mobile_url'];
}else{
	$_SESSION['site_mode'] = 'desktop';
	$config['website_baseurl'] = $protocol.$config['desktop_url'];
}

// template 路径
$config['template_path'] = dirname(__FILE__).'/ui/'.$config['themepath'].'/';

// -----------------------------------------------------
// CDN infomation
// 靜態樣板或是 JS 等靜態檔案，使用 CDN 加速，但是為了區隔專案的不同，把 CDN 設定為不同專案的目錄。
// -----------------------------------------------------
// 需要修改變換保護 cdn , 避免 http://cdn.baidu-cdn-hk.com/ 這個網址直接暴露, 被 GFW 封鎖
// 這段配合修改 nginx 設定檔，加入 proxy_pass 設定 , 當網址進入 nginx 後，會轉換成為實際的網址，但在外觀上看不出來實際的 cdn 位置。
// location /CdnRedirect {proxy_pass http://cdn.baidu-cdn-hk.com/;}
 // $config['cdn_baseurl']	= $config['website_baseurl'];
$config['cdn_baseurl']  = (isset($custom_cdn) AND $custom_cdn != '' ) ? $custom_cdn : 'https://cdn.playgt.com/site/';
// CDN 檔案路徑設定
//$config['themepath'] = 'gp02';
$cdnfullurl_js = $config['cdn_baseurl'].'in/';
$cdnrooturl = $config['cdn_baseurl'].$config['website_project_type'].'/';
$cdnfullurl = $config['cdn_baseurl'].$config['website_project_type'].'/'.$config['themepath'].'/';
$cdn4gamesicon = $config['cdn_baseurl'].$config['website_project_type'].'/'.$config['gameiconpath'].'/';
$config['cdn_upload']	= 'http://cdn.playgt.com/site/upload/';
$config['cdn_version_key']	= '200814';

// -----------------------------------------------------
// 多網域config 前台顯示
// -----------------------------------------------------
$config['companylogo']=$config['companylogo']??$cdnfullurl.'img/common/logo.png';
$config['companyFavicon']=$config['companyFavicon']??$cdnfullurl.'img/common/favicon.ico';
$config['companyName']=$config['companyName']??'JIGDEMO';
$config['companyShortName']=$config['companyShortName']??'JIGDEMO';