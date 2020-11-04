<?php
// ----------------------------------------------------------------------------
// Features:	前台 -- 優惠活動專區 - 詳情
// File Name:	promotions_detail.php
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
// 優惠活動
require_once dirname(__FILE__) ."/promotions_lib.php";
// 優惠紅包兌換
require_once dirname(__FILE__) ."/promotion_activity_lib.php";


// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// 檢查獎置介面是行動裝置還是桌機
// ----------------------------------------------------------------------------
$device_chk_html = clientdevice_detect(0,0)['html'];
echo $device_chk_html;
// ----------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
$function_title 		= $tr['Pormotions detail'];
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
        <li><a href="promotions.php">{$tr['Pormotions']}</a></li>
        <li class="active">{$function_title}</li>
    </ul>
HTML;

if($config['site_style'] == 'mobile'){
	$navigational_hierarchy_html =<<<HTML
        <a href="{$config['website_baseurl']}promotions.php"><i class="fas fa-chevron-left" aria-hidden="true"></i></a>
        <span>{$function_title}</span>
        <i></i>
HTML;
}

$extend_head = <<<HTML
<style type="text/css">
    form{
        width: 100%;
    }
</style>
HTML;
$extend_js=<<<HTML
    <script>
       $(document).ready(function(){ 
        $('.container').addClass('promotionsclass');
        $('.content table').removeAttr('style');
        var bordernum = $('.content table').attr('border');
        if( bordernum == '1' ){
          $('.content table').addClass('borderstyle');
        }else if( bordernum > 1){
          $('.content table').addClass('borderstyle');
          $('.content table').css({'border-width':bordernum+'px'});
          $('.content table tr td').css({'border-width':bordernum+'px'});
          $('.content table tr th').css({'border-width':bordernum+'px'});
        } 
       });
    </script>
HTML;
// ----------------------------------------------------------------------------

if(isset($_GET['id']) AND $_GET['id'] != NULL){
    $promotion_id = filter_var($_GET['id'],FILTER_SANITIZE_NUMBER_INT);

    // 判斷user使用的是手機還是桌機
    if($config['site_style'] == 'mobile'){
        $result = getPromotions_mobile_id($promotion_id); // 手機板
    }else{
        $result = getPromotions_id($promotion_id); // 桌機版
    }
 
	// $result = getPromotions_id($promotion_id); // 取優惠管理id

    // 當未登入時，加上JWT字串
    // 需要傳遞的陣列
    // formtype --> [POST|GET] 轉址傳遞變數的方式(必要)
    // formurl --> 自訂轉址指定的網址, 相對路徑或絕對路徑都可以 (必要)
    // 其他變數(自訂)
    $get_serial = array(
        "formtype" => "POST",
        "formurl" => "promotions_detail.php?id=$promotion_id"
    );
    // var_dump($get_serial);die();
    // 產生 token , salt是檢核密碼預設值為123456 ,需要配合 jwtdec 的解碼, 此範例設定為 123456
    $token = jwtenc('123456', $get_serial);
    $gotourl = 'login2page.php?t='.$token;
    // echo($gotourl);die();
// ---------------------------------------

    $color = '';
    $show = '';
    $href = '';
    $show_text = '';
    $to_link = '';

    // 活動優惠碼連結
    $find_activity_id = '';
    $find_activity_name = '';

    $show_requirements = '';

    $today = gmdate('Y-m-d H:i',time() + '-4' * 3600);
    
    if($result[0] >= 1){
        // 優惠活動
        $id = $result[1]->id ;
        $name = $result[1]->name;
        $mobile_show = $result[1]->mobile_show; // 手機
        $desktop_show = $result[1]->desktop_show ; // 桌機
        $endtime = date('Y-m-d h:i:s', strtotime($result[1]->endtime) + 12 * 3600);//$result[1]->the_endtime;  //(gmdate('Y-m-d H:i:s',strtotime($result[1]->endtime.'-04')));
        $content_detail = htmlspecialchars_decode($result[1]->content);

        if(strtotime($result[1]->endtime) < strtotime($today)){
            $imgurl= $result[1]->bannerurl_end; // 結束圖片
        }else{
            $imgurl = $result[1]->bannerurl_effect; // 開始圖片
        }

        // 沒設定優惠紅包連結
        if($result[1]->show_promotion_activity == 0 OR $result[1]->show_promotion_activity == NULL){
            $get_promotion_id = '';
            $btn_link = '';
        }else{
            // 有登入，而且有設定優惠紅包連結
            if(isset($_SESSION['member']->id)){
                $get_promotion_id = $result[1]->show_promotion_activity;  // 從優惠活動取得活動優惠碼id
                $go_check = get_promotion_activity($get_promotion_id); // 取得優惠紅包data
                $id = $go_check[1]->id; // id
                $act_id = $go_check[1]->activity_id; // 優惠紅包的活動代碼 eg.qxes

                //判斷優惠活動有沒有過期
                if(strtotime($today) > strtotime($result[1]->endtime)){
                    // 過期
                    $show = 'disabled';
                    $color .= 'primary';
                    $href .= '#';
                    $show_text = $tr['Completed Offers'];//已结束优惠
                    // 顯示結束優惠button
                    $to_link.=<<<HTML
                        <div class="w-100">
                            <a href="{$href}"><button type="button" class="btn btn-{$color} w-100" {$show}>{$show_text}</button></a>
                        </div>
HTML;
                }else{
                    // 判斷user領過沒
                    $check_user_promotion = check_user_frompromotion($act_id);
                
                    $activity = get_activity_data($act_id);
                
                    $activity_sdate = $activity[1]->effecttime; // 活動開始時間
                    $act_requirement = $activity[1]->promocode_req;
                    $act_decode = json_decode($act_requirement,true);
                    
                    $requirement_betting_amount = $act_decode['betting_amount'];
                    $requirement_desposit_amount = $act_decode['desposit_amount'];
                    $requirement_member_time = $act_decode['reg_member_time'];
                    $requirement_account_type = $act_decode['user_therole'];

                    // 會員資料
                    $get_member_data = get_member_data();
                    $reg_date = $get_member_data[1]->enrollmentdate; // 入會日期
                    $member_tokenwallet = $get_member_data[1]->gtoken_balance; // gtoken錢包
                    $member_ip = $get_member_data[1]->registerip; // ip
                    $member_fingerprint = $get_member_data[1]->registerfingerprinting; // fingerprint
                    $member_role = $get_member_data[1]->therole; // 角色
                
                    // 取得會員投注紀錄
                    $now = date('Y-m-d');
                    $activity_select_sdate = date('Y-m-d',strtotime("$activity_sdate -1 month")); // 活動開始前1個月到領取的前一天
                    $activity_select_edate = date('Y-m-d',strtotime("$now -1 day"));
                    $get_betting = get_betting_data($activity_select_sdate,$activity_select_edate);
                    $total_bet = $get_betting[1]->bets;

                    if($check_user_promotion[0] > 0){
                        // 領過
                        $show = 'disabled';
                        $color .= 'primary';
                        $href .= '#';
                        $show_text = $tr['already Redeemed'];//您已经兑换完毕
                        $to_link.=<<<HTML
                            <div class="bt-w-100 bt-md-92-auto mt-3">
                                <a href="{$href}"><button type="button" class="btn btn-{$color} w-100" {$show}>{$show_text}</button></a>
                            </div>
HTML;
                    }else{
                        $act_decode_req['betting_amount'] = betting_limit($total_bet,$requirement_betting_amount);
                        $act_decode_req['desposit_amount'] = deposit_limit($member_tokenwallet,$requirement_desposit_amount);
                        $act_decode_req['reg_member_time'] = reg_time_limit($reg_date,$requirement_member_time);
                        $act_decode_req['user_therole'] = check_member_role($member_role,$requirement_account_type);

                        $show_disable_ch[]=$act_decode_req['betting_amount']['status'];
                        $show_disable_ch[]=$act_decode_req['desposit_amount']['status'];
                        $show_disable_ch[]=$act_decode_req['reg_member_time']['status'];
                        $show_disable_ch[]=$act_decode_req['user_therole']['status'];

                        // 有設定條件而且有登入顯示條件、是否符合資格
                        $show_requirements.=$act_decode_req['betting_amount']['html'];
                        $show_requirements.=$act_decode_req['desposit_amount']['html'];
                        $show_requirements.=$act_decode_req['reg_member_time']['html'];
                        $show_requirements.=$act_decode_req['user_therole']['html'];

                        // 沒領過，判斷符不符合領的資格      
                        if(in_array('0',$show_disable_ch)){
                            $show = '';
                            $color .= 'primary';
                            $href .= "promotion_activity.php?a={$act_id}";
                            $show_text = $tr['Not qualified'];//尚未符合兑换资格
                            $to_link.=<<<HTML
                                <div>
                                    <a href="{$href}"><button type="button" class="btn btn-{$color} w-100 p-3 rounded" {$show}>{$show_text}</button></a>
                                </div>
HTML;
                        }else{
                            $show = '';
                            $color .= 'primary';
                            $href .= "promotion_activity.php?a={$act_id}";
                            $show_text = $tr['Promotion code redeem'];//活动优惠码兑换
                            // 連結到優惠碼兌換button
                            $to_link.=<<<HTML
                            <form name="form" action="{$href}" method="POST">
                                <div class="w-100">
                                    <a href="{$href}">
                                        <button type="submit" class="btn btn-{$color} send_btn" {$show} value="{$promotion_id}" name="more_detail">{$show_text}</button></a>
                                </div>
                            </form>
HTML;
                        }
                    }
                }
            }else{
                // 沒登入
                $color.='success';
                $href .= $gotourl;
                $show_text .= $tr['Promotion code redeem'].'('.$tr['member login first'].')';//活动优惠码兑换 (请先登入)
                // 登入連結button
                $to_link.=<<<HTML
                    <div class="w-100">
                        <a href="{$href}"><button type="button" class="btn btn-{$color} w-100" {$show}>{$show_text}</button></a>
                    </div>
HTML;
            }
        }
        $indexbody_content =<<<HTML
        <!-- 内容显示 -->
        <div class="promotions_detail">
            <div class="main_content mx-auto"> 
                <div class="d-block text-center"><img class="img-fluid" src ="{$imgurl}"></div>
                <h2>{$name}</h2>
                <time>{$tr['pormotions end time']}: {$endtime}</time>
                <div class="content">{$content_detail}</div>
                <div>{$to_link}</div>
            </div>
        </div>
HTML;
    }else{
        $indexbody_content =<<<HTML
        <div class="bt-w-100 bt-md-92-auto mt-3">
            <button type="button" class="btn btn-{$color} w-100">无活动资料</button>
        </div>
HTML;
    }
   
//         $indexbody_content =<<<HTML
//             <!-- 内容显示 -->
//             <div class="row promotions_detail">
//                 <div class="col-12"> 
//                     <img class="img-fluid" src ="{$imgurl}" width="100%">
//                     <h2>{$name}</h2>
//                     <time>{$tr['pormotions end time']}: {$endtime}</time>
//                     <div>{$btn_link}</div>
//                     <div class="content">{$content_detail}</div>
//                 </div>
//             </div>
// HTML;

//     if($mobile_show == 1 OR $desktop_show == 1){
//         // 如果桌機或手機板有開啟
//         $indexbody_content =<<<HTML
//             <!-- 内容显示 -->
//             <div class="row promotions_detail">
//             <div class="col-12"> 
//             <img class="img-fluid" src ="{$imgurl}" width="100%">
//             <h2>{$name}</h2>
//             <time>{$tr['pormotions end time']}: {$endtime}</time>
//             <div>{$btn_link}</div>
//             <div class="content">{$content_detail}</div>
//             </div>
//         </div>
// HTML;
//     }else{
//         $indexbody_content =<<<HTML
//             <div class="bt-w-100 bt-md-92-auto mt-3">
//                 <button type="button" class="btn btn-{$color} w-100">无活动资料</button>
//             </div>
// HTML;
//     }

    if($config['site_style']=='desktop'){
        $indexbody_content = $indexbody_content.'<a class="btn btn-outline-secondary back_prev" href="'.$config['website_baseurl'].'promotions.php"><i class="fas fa-chevron-left"></i>'.$tr['back to list'].'</a>';
    }
}else{
    header('Location:home.php');
	die();
}

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
// banner標題
$tmpl['banner'] = ['Pormotions'];
// menu增加active
$tmpl['menu_active'] =['promotions.php'];

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include $config['template_path'] . "template/static.tmpl.php";

?>