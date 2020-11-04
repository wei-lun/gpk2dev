<?php
// ----------------------------------------------------------------------------
// Features:	前台 -- 優惠活動專區 - 詳情
// File Name:	mo_promotions_detail.php
// Author:		Mavis
// Related:
// Log:
// 依據後台開啟的優惠活動, 引導進入對應的行銷活動頁面. 前台要包裝特別在另外連結。
// ----------------------------------------------------------------------------

require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";


// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
$function_title 		= '活动详情';
// 擴充 head 內的 css or js
$extend_head				= '';
// 放在結尾的 js
$extend_js					= '';
// body 內的主要內容
$indexbody_content	= '';
// 系統訊息選單
$messages 					= '';
// ----------------------------------------------------------------------------
// 初始化變數 end
// ----------------------------------------------------------------------------
// 導覽列
$navigational_hierarchy_html =<<<HTML
<ul class="breadcrumb">
  <li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
  <li><a href="mo_promotions.php">优惠专区</a></li>
  <li class="active">{$function_title}</li>
</ul>
HTML;

if($config['site_style'] == 'mobile'){
	$navigational_hierarchy_html =<<<HTML
        <a href="{$config['website_baseurl']}mo_promotions.php"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span></a>
        <span>{$function_title}</span>
        <i></i>
HTML;
}
// ----------------------------------------------------------------------------

// 活動優惠碼管理
function get_promotion_activity($get_promotion_id){
  $sql=<<<SQL
    SELECT * FROM root_promotion_activity
    WHERE id = '{$get_promotion_id}'
    AND activity_status = 1 
    --AND effecttime <= current_timestamp 
    --AND endtime >= current_timestamp
SQL;
  $result = runSQLall($sql);
  return $result;
}


if(isset($_GET['id']) AND $_GET['id'] != NULL){
    $promotion_id = filter_var($_GET['id'],FILTER_SANITIZE_NUMBER_INT);

    $sql=<<<SQL
    SELECT * FROM root_promotions 
    WHERE id = '{$promotion_id}' 
    AND status = 1
    AND mobile_show = 1
    AND desktop_show = 1
    -- AND effecttime <= current_timestamp 
    -- AND endtime >= current_timestamp
SQL;
    $result = runSQLall($sql);

    // 當未登入時，加上JWT字串
    // 需要傳遞的陣列
    // formtype --> [POST|GET] 轉址傳遞變數的方式(必要)
    // formurl --> 自訂轉址指定的網址, 相對路徑或絕對路徑都可以 (必要)
    // 其他變數(自訂)
    $get_serial = array(
        "formtype" => "POST",
        "formurl" => "mo_promotions_detail.php?id=$promotion_id"
    );
    // var_dump($get_serial);die();
    // 產生 token , salt是檢核密碼預設值為123456 ,需要配合 jwtdec 的解碼, 此範例設定為 123456
    $token = jwtenc('123456', $get_serial);
    $gotourl = 'login2page.php?t='.$token;

// ---------------------------------------

    $content = '';
    $today = gmdate('Y-m-d H:i',time() + '-4' * 3600);

    if($result[0]>=1){
        for($i=1;$i<=$result[0];$i++){
            
            
            $id = $result[$i]->id;
            $name = $result[$i]->name;
            // $endtime = (gmdate('Y-m-d H:i:s',strtotime($result[$i]->endtime)));
            $endtime = (gmdate('Y-m-d H:i:s',strtotime($result[$i]->endtime.'-04')));
            $banner_effect = $result[$i]->bannerurl_effect;
            $banner_end = $result[$i]->bannerurl_end;
            $content_detail = htmlspecialchars_decode($result[$i]->content);
            
            if(strtotime($result[$i]->endtime) < strtotime($today)){
                $imgurl= $result[$i]->bannerurl_end;
            }else{
                $imgurl = $result[$i]->bannerurl_effect;
            }

            $color = '';
            $btn_link = '';
            $show = '';
            $href = '';
            $show_text = '';
            $to_link = '';
            if(isset($_SESSION['member']->id)){
                // 活動優惠碼連結
                $find_activity_id = '';
                $find_activity_name = '';
    
                if($result[$i]->show_promotion_activity == 0 ){
                    $get_promotion_id = '';
                    $btn_link .= '';
                }else{
                    $get_promotion_id = $result[$i]->show_promotion_activity;  // 活動優惠碼id
                    $go_check = get_promotion_activity($get_promotion_id);

                    if($go_check[0]<=1){
                        for($i=1;$i<=$go_check[0];$i++){
                            $find_activity_id = $go_check[$i]->activity_id; // 活動代碼
                            $find_activity_name = $go_check[$i]->activity_name;
                        
                            // 以優惠活動時間為主
                            // $e_date = strtotime($go_check[$i]->endtime);
                            $endtime = (gmdate('Y-m-d H:i:s',strtotime($result[$i]->endtime)));

                            if(strtotime($today) > strtotime($endtime)){
                                $show = 'disabled';
                                $color .= 'primary';
                                $href .= '#'; 
                                $show_text = '优惠红包领取';
                                $to_link.=<<<HTML
                                <div class="col-md-10 text-center">
                                    <a href="{$href}"><button type="button" class="btn btn-{$color} w-100 " {$show}>{$show_text}</button></a>
                                </div>
HTML;

                            }else{                               
                                $show = '';
                                $color .= 'primary';
                                $href .= "promotion_activity.php?a={$find_activity_id}";
                                $show_text = '优惠红包领取';
                                $to_link.=<<<HTML
                                <div class="col-md-10 text-center">
                                    <a href="{$href}"><button type="button" class="btn btn-{$color} w-100 " {$show}>{$show_text}</button></a>
                                </div>
HTML;
                            }
                        }
                    }
                }
            }else{
                $color.='success';
                $href .= "'.$gotourl.'";
                $show_text .= '请先登入会员';
                $to_link.=<<<HTML
                <div class="col-md-10 text-center">
                    <a href="{$href}"><button type="button" class="btn btn-{$color} w-100 " {$show}>{$show_text}</button></a>
                </div>
HTML;
            }
            $btn_link .=<<<HTML
                {$to_link}
HTML;
        }
            $content = <<<HTML
                <img src ="{$imgurl}" width="100%">
                <h2 class="col-12 font-weight-bolder">{$name}</h2>
                <p class="col-8">活动截止日期: {$endtime}</p>
                <br>
                <div class="row justify-content-md-center border-bottom pb-4">{$btn_link}</div>
                <div>{$content_detail}</div>
HTML;
        }
    }

    $tab_html =<<<HTML
    <div class="card">
    <!-- 内容显示 -->
        <div class="card-body w-100 card border-0 promotionsArea">
            <div class="card-body promotionsAreaBody">
                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane active" >
                        {$content}
                    </div>
                </div>
            </div>
        </div>
    </div>
HTML;

    //手機顯示
    if($config['site_style']=='mobile'){
        $navigational_hierarchy_html =<<<HTML
            <a href="{$config['website_baseurl']}mo_promotions.php"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span></a>
            <span>{$function_title}</span>
            <i></i>
HTML;
        $indexbody_content = $tab_html;
    }else{//桌機顯示
        $indexbody_content =<<<HTML
            {$tab_html}
HTML;
    }
// }




// ----------------------------------------------------------------------------
// 準備填入的內容
// ----------------------------------------------------------------------------
// 將內容塞到 html meta 的關鍵字, SEO 加強使用
$tmpl['html_meta_description'] 		= $config['companyShortName'];
$tmpl['html_meta_author']	 				= $config['companyShortName'];
$tmpl['html_meta_title'] 					= $function_title.'-'.$config['companyShortName'];

// 系統訊息顯示
$tmpl['message']									= $messages;
// 擴充再 head 的內容 可以是 js or css
$tmpl['extend_head']							= $extend_head;
// 擴充於檔案末端的 Javascript
$tmpl['extend_js']								= $extend_js;
// 主要內容 -- title
$tmpl['paneltitle_content'] 			= $navigational_hierarchy_html;
// 主要內容 -- content
$tmpl['panelbody_content']				= $indexbody_content;
// 側邊欄MENU var[0]側邊欄組合(lib_menu) var[1]目前頁籤名
// $tmpl['sidebar_content'] = ['message','promotions'];

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
// include($config['template_path']."template/static.tmpl.php");
include $config['template_path'] . "template/admin.tmpl.php";
?>