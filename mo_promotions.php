<?php
// ----------------------------------------------------------------------------
// Features:	前台 -- 行銷優惠活動專區
// File Name:	mo_promotions.php
// Author:		Mavis
// Related:
// Log:
// 依據後台開啟的優惠活動, 引導進入對應的行銷活動頁面. 前台要包裝特別在另外連結。
// ----------------------------------------------------------------------------



// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

// require_once dirname(__FILE__) ."/in/mobiledetect/Mobile_Detect.php";

//var_dump($_SESSION);
//var_dump($_POST);

// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
$function_title 		= $tr['Pormotions'];
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
	<li class="active">{$function_title}</li>
	</ul>
HTML;

if($config['site_style']=='mobile'){
	$navigational_hierarchy_html =<<<HTML
		<a href="{$config['website_baseurl']}home.php"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span></a>
		<span>{$function_title}</span>
		<i></i>
HTML;
}

$extend_head=<<<HTML
	<style type="text/css">
		tbody .promotions_name{
			text-overflow : ellipsis;
			display: inline-block;
			overflow: hidden;
			white-space: nowrap;
			width: 100%;
		}
	</style>

HTML;
// ----------------------------------------------------------------------------

// domain
function get_promotions_domain($domain,$determine){
	$tzonename = 'posix/Etc/GMT-8';

	if($determine == 'mobile') {
		$sql = <<<SQL
		SELECT * , 
			  to_char((effecttime AT TIME ZONE '{$tzonename}'),'YYYY/MM/DD HH24:MI') AS effecttime, 
			  to_char((endtime AT TIME ZONE '{$tzonename}'),'YYYY/MM/DD HH24:MI') AS e_time 
		FROM root_promotions 
		WHERE status = 1 
		AND mobile_show = 1
		AND mobile_domain = '{$domain}'
		-- AND effecttime <= current_timestamp 
    	AND endtime >= current_timestamp
		ORDER BY endtime ASC
		-- LIMIT 2
SQL;
	  } else {
		$sql = <<<SQL
		SELECT * ,
			to_char((effecttime AT TIME ZONE '{$tzonename}'),'YYYY/MM/DD HH24:MI') AS effecttime,
			to_char((endtime AT TIME ZONE '{$tzonename}'),'YYYY/MM/DD HH24:MI') AS e_time
		FROM root_promotions
		WHERE status = 1
		AND desktop_show = 1
		AND desktop_domain = '{$domain}'
		-- AND effecttime <= current_timestamp 
    	AND endtime >= current_timestamp
		ORDER BY endtime ASC
		-- LIMIT 2
SQL;
	}
	$result = runSQLall($sql);

	if(empty($result[0])) {
		$error_text = '优惠查询错误或暫無優惠';
		return array('status' => false, 'result' => $error_text);
	}
	
	unset($result[0]);
	return array('status' => true, 'result' => $result);
	
}

// 分類
function get_classification($domain,$determine){
	if($determine == 'mobile'){
		$sql=<<<SQL
		SELECT * FROM(
			SELECT DISTINCT ON (classification) classification,sort FROM root_promotions 
			WHERE (mobile_domain = '{$domain}' AND mobile_domain IS NOT NULL)
			AND status = 1
			AND mobile_show = 1
			AND effecttime <= current_timestamp 
            AND endtime >= current_timestamp
			ORDER BY classification
		) t
		ORDER BY sort
SQL;
	}else{
		$sql=<<<SQL
		SELECT * FROM(
			SELECT DISTINCT ON (classification) classification,sort FROM root_promotions 
			WHERE (desktop_domain = '{$domain}' AND desktop_domain IS NOT NULL)
			AND status = 1
			AND desktop_show = 1
			AND effecttime <= current_timestamp 
            AND endtime >= current_timestamp
			ORDER BY classification
		) t
		ORDER BY sort
SQL;
	}

	$result = runSQLall($sql);
	
	if(empty($result[0])){
		$error_text = '优惠分类查询错误或暫無優惠';
		return array('status' => false, 'result' => $error_text);
	}
	unset($result[0]);
	return array('status' => true, 'result' => $result);

}

// -----------------------------
// main
// -----------------------------

$list_html = '';
$select_classification = '';
$end_promotions = '';

$determine = $config['site_style'];

$get_domain = get_promotions_domain($_SERVER['HTTP_HOST'],$determine);

$classifications = get_classification($_SERVER['HTTP_HOST'],$determine);

$today =  gmdate('Y-m-d H:i',time() + '-4' * 3600);

if ($get_domain['status'] && $classifications['status']) {
	// 活動
	foreach($get_domain['result'] as $k => $v){
		$id = $v->id;
		$name = $v->name;
		$effecttime = $v->effecttime;
		$endtime = (gmdate('Y-m-d',strtotime($v->endtime.'-04')));
		$status = $v->status;

		// table
		$list_html .=<<<HTML
			<tr class="row border promotion_row" id="promotion_{$id}" onclick="document.location='mo_promotions_detail.php?id={$id}';">	
				<td class="col-8 border-0 h5 font-weight-bold m-0  promotions_name">{$name}</td>
				<td class="col-4 text-right border-0">
					<span class="glyphicon glyphicon-chevron-right" aria-hidden="true">
				</td>
				<td class="col-8 border-0 pt-0 text-secondary ">截止日期: {$endtime}</td>
				</span>				
			</tr>
HTML;

		if(strtotime($today) < strtotime($endtime)){
			// $find_end_promotions = end_promotions($_SERVER['HTTP_HOST'],$determine,$today);

			$end_promotions= <<<HTML
			<button type="button" class="btn btn-primary closed_promotions">已结束优惠</button>
HTML;
		}else{
			$end_promotions= '';
		}
	}

		// 分類
		foreach($classifications['result'] as $key => $value){
			$class[$v->sort] = $value->classification;
			// $offer_data[array_search($v->classification, $class)][] = $v;
		
			$select_classification .=<<<HTML
				<option class="select_classification" value="{$class[$v->sort]}" >{$class[$v->sort]}</option>
HTML;
	}

  // 將各 tab 表格內容填入
  $tab_html =<<<HTML
  <div class="card">

    <div class="card-header">
		<!-- 下拉式選單 -->
		<select class="w-50 border-0" id="select_one" name="select_class" onchange="select_a_calssification()">
			<option class="select_classification" value="all"> 全部优惠</option>
			{$select_classification}
		</select>
			{$end_promotions}
		
    </div>

    <!-- 内容显示 -->
    <div class="card-body w-100 card border-0 promotionsArea">
		<div class="card-body promotionsAreaBody">
		<div class="tab-content">
			<div role="tabpanel" class="tab-pane active" id="show_promotion">
				<table class="table table-hover table_reflash" id="promotions_Table">
					<tbody id="promotionsTableBody">
						{$list_html}
					</tbody>
				</table>
			<!-- <button type="button" class="btn btn-primary btn-lg btn-block loadMore" id="promotionLoadMore" value="promotion">载入更多</button> -->
		</div>
      </div>
    </div>
  </div> 
HTML;

	//手機顯示
	if($config['site_style']=='mobile'){
		// echo '23e2e3';die();
		$navigational_hierarchy_html =<<<HTML
		<div class="d-flex justify-content-between">
			<a href="{$config['website_baseurl']}home.php"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span></a>
			<span>{$function_title}</span>
			<i></i>
		</div>
HTML;
		$indexbody_content = $tab_html;
	}else{//桌機顯示
		$indexbody_content =<<<HTML
			{$tab_html}
HTML;
		}

	}
// }

$extend_js=<<<HTML
<script>

	// 活動detail
	$('#promotion_Table tbody').on('click','tr',function(){
		var trId = $(this).attr('id').split('_');
		var id = trId[1];

		$.ajax({
			url:"mo_promotions_detail.php?id='$id'",
			type: "GET",
			data:{
				id: id
			},
			success:function(response){
				console.log('success');
			},
			error:function(error){
				console.log('error');
			}
		})
	})

	// 分類
	function select_a_calssification(){

		var select =$('select[name="select_class"] :selected').val();
		// console.log(select);

		$.ajax({
			url:"mo_promotions_action.php?a=select",
			type: "POST",
			data:{
				select: select
				// action : 'select'
			},
			success:function(response){
				$("tbody #promotion_{$id}").remove();
				$('#promotionsTableBody').html(response);
			},
			error:function(error){
				console.log('error');
			}
		})
	}

	// 已結束
	$(".closed_promotions").on('click',function(){
		var now = '$today';
		// console.log(now);
		$.ajax({
			url:"mo_promotions_action.php?a=closed",
			type: "POST",
			data:{
				now: now
			},
			success:function(response){
				$("tbody #promotion_{$id}").remove();
				// $("tbody").remove(); //  #promotion_{$id}
				$('#promotionsTableBody').html(response);

			},error:function(error){
				console.log('error');
			}
		})
	})

	// loadmore
	// $(document).on('click','.loadMore',function(){
	// 	var dataArea = $(this).val();
	// 	var limit = $('.'+dataArea+'_row').length;
	// 	// console.log(dataArea);
	// 	// console.log(limit);

	// 	$.ajax({
	// 		url: "mo_promotions_action.php?a=more",
	// 		type: "POST",
	// 		data:{
	// 			condition: dataArea,
    //     		limit: limit
	// 			// action: 'more'
	// 		},
	// 		success: function(resp){
	// 			// console.log(resp);
	// 			var res = JSON.parse(resp);
	// 			// console.log(res);

	// 			if (res.status == 'success') {
	// 				if ($('.'+dataArea+'_row:last').length == 0) {
	// 					// console.log('table');
	// 					$('#'+dataArea+'_Table').append(combineHTML(dataArea, res.data));
	// 				} else {
	// 					// console.log('last');
	// 					$('.'+dataArea+'_row:last').after(combineHTML(dataArea, res.data));
	// 				}

	// 				if (res.count < 2) {
	// 					// console.log('<2');
	// 					$('#'+dataArea+' br').remove();
	// 					$('#promotionLoadMore').remove();
	// 				}
	// 			}else {
	// 				// console.log('error');
	// 				$('.'+dataArea+' _row br').remove();
	// 				$('#'+dataArea+'LoadMore').remove();
	// 				alert(res.data);
	// 			}
					
	// 		}
	// 	})
	// })

	function combineHTML(className, data){
		var html = '';
		var loadMoreDataHtml = data.reduce(function(accumulator, currentValue, currentIndex, array) {
			html = `
				<tr class="row border `+className+`_row" id="`+className+`_`+currentValue.id+`" onclick="document.location='mo_promotions_detail.php?id=`+currentValue.id+`';">	
					<td class="col-8 border-0 h5 font-weight-bold m-0  promotions_name">`+currentValue.name+`</td>
					<td class="col-4 text-right border-0">
						<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>	
					</td>
					<td class="col-8 border-0 pt-0 text-secondary ">截止日期: `+currentValue.endtime+` </td>			
				</tr>
			`;
			return accumulator + html;
		},'');
		return loadMoreDataHtml;
	}

</script>
HTML;


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


// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
// include($config['template_path']."template/static.tmpl.php");
include $config['template_path'] . "template/admin.tmpl.php";

?>
