<?php
// ----------------------------------------------------------------------------
// Features:	前台 -- 行銷優惠活動專區
// File Name:	promotions.php
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

require_once dirname(__FILE__) ."/promotions_lib.php";

//var_dump($_SESSION);
//var_dump($_POST);

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

$extend_head=<<<HTML
	<style type="text/css">
	.promotions_name .text-secondary{
		padding-top: 14px;
		color: gray;
		font-size: 8px;
		font-weight: normal;
	}
	.promotion_arrow_right {
		font-size: 3em;
	}
	#no_data{
		display: none;
	}
	.table{
    	width: 100%;
  	}

	</style>

HTML;
// ----------------------------------------------------------------------------

// -----------------------------
// main
// -----------------------------
// 導覽列

if($config['site_style'] == 'mobile'){
	$navigational_hierarchy_html =<<<HTML
		<a href="{$config['website_baseurl']}home.php"><i class="fas fa-chevron-left" aria-hidden="true"></i></a>
		<span>{$function_title}</span>
		<i></i>	
HTML;
}else{
	$navigational_hierarchy_html =<<<HTML
	<ul class="breadcrumb">
		<li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
		<li class="active">{$function_title}</li>
	</ul>
	
HTML;
}

$list_html = '';
$select_classification = '';
$determine = $config['site_style'];
// var_dump($_SERVER['HTTP_HOST']);die();
// 優惠分類
$get_classifications_of_promotions = get_classification($_SERVER['HTTP_HOST'],$determine);

// 左上角分類
if(is_array($get_classifications_of_promotions['result'])){
foreach($get_classifications_of_promotions['result'] as $key => $value){
		$class[$value->sort] = $value->classification;
		$select_classification .=<<<HTML
			<tr><td class="cl_cates" data-dateval="{$class[$value->sort]}">{$class[$value->sort]}</td></tr>
HTML;
	}
}

// 手機名稱 分類, 狀態
if($config['site_style'] == 'mobile'){
$promotions_filter_list = $tr['Pormotions_abbreviation_category'];
$promotions_filter_status = $tr['Pormotions_abbreviation_status'];
}else{
// 桌機名稱 優惠分類, 優惠狀態
$promotions_filter_list = $tr['Pormotions category'];
$promotions_filter_status = $tr['Pormotions status'];
}

$indexbody_content =<<<HTML
	<!-- yaoyuan start -->
		<div class="promotions filter_list_button">		
			<div>
				<button id = "a_promotion_cate_select" type = "button" onclick = "on_slidemenu('classification');" class = "btn btn-outline-secondary dropdown-toggle" value = "all_cate">{$promotions_filter_list}</button>
			</div>
				<div>
				<button id = "promotions_select_list" type = "button" onclick = "on_slidemenu('duetime');" class = " btn btn-outline-secondary dropdown-toggle" value = "processing">{$promotions_filter_status}</button>
				</div>		
		</div>

	<!-- yaoyuan end-->

		<!-- 内容显示 -->
		<div class="tab-content row">			
				<div id="promotions_Table" class="col">
						<ul id="promotionsTableBody" class="li_list col-12">
							<div id="no_data" class="no_data_style">
								<p class="no_data_p">{$tr['search no data']}</p>		
							</div>					
						</ul>
				</div>
				<div class="col-12">
				<button type="button" class = "send_btn loadMore" id="promotionLoadMore">{$tr['load more']}</button>
				</div>
		</div>

		<!-- 分類 -->
		<div class="block-layout motransaction_layout"></div>
		<div id="classification" class="slide-up-menu slide-up-style">
			<table class = "table">
				<thead class = "thead-light">
					<tr><th class="bg-secondary">{$tr['please choose pormotions category']}</th></tr>
					<tr><td class = "cl_cates" data-dateval = "all_cate">{$tr['all pormotions category']}</td></tr>
						{$select_classification}
					<tr><td class="ca_tdcancel bg-secondary" onclick="off_slidemenu()">{$tr['cancel']}</td></tr>
				</thead>
			</table>
		</div>

		<!-- 優惠 -->
		<div id="duetime" class="slide-up-menu slide-up-style">
			<table class="table">
				<thead class="thead-light">
					<tr><th class="bg-secondary">{$tr['please choose pormotions status']}</th></tr>
					<tr><td class = "cl_promotions" data-dateval = "processing">{$tr['in processing']}</td></tr>
					<tr><td class = "cl_promotions" data-dateval = "end_promotion">{$tr['Completed Offers']}</td></tr>
					<tr><td class="ca_tdcancel bg-secondary" onclick="off_slidemenu()">{$tr['cancel']}</td></tr>
				</thead>
			</table>
		</div>

	<!-- 優惠 -->
	<div id="duetime" class="slide-up-menu slide-up-style">     
		<table class="table">
			<thead class="thead-light">
				<tr><th>{$tr['please choose pormotions status']}</th></tr>
				<tr><td class = "cl_promotions" data-dateval = "processing">{$tr['in processing']}</td></tr>
				<tr><td class = "cl_promotions" data-dateval = "end_promotion">{$tr['Completed Offers']}</td></tr>
				<tr><th></th></tr>
				<tr><td class="ca_tdcancel" onclick="off_slidemenu()">{$tr['cancel']}</td></tr>
			</thead>
		</table>
	</div>
HTML;

$extend_js=<<<HTML
	<script>
	// 分類、優惠slide menu
	function off_slidemenu(){
       $('.slide-up-menu').removeClass('slide-up');
       $('.block-layout').fadeOut();
    }
	function on_slidemenu(toggle){
       $('#'+toggle).addClass('slide-up');
       $('.block-layout').fadeIn();
    }

	// 載入更多顯示
	function to_loading_show_text(onoff){
		if(onoff == '1'){
			$('#promotionLoadMore').text('{$tr['load more']}');
			$('#promotionLoadMore').prop('disabled',false);
		}else{
			$('#promotionLoadMore').text('{$tr['no more data']}');
			$('#promotionLoadMore').prop('disabled',true);
		}
	}

	// 抓取過濾條件資訊
	function get_query_source_condition(){
		// 分類
		var classification_name = $('#a_promotion_cate_select').val();
		// 優惠
		var promotion_list = $('#promotions_select_list').val();
		// 筆數
		var limit = $('.promotion_row').length;

		var data = {
			"cate": classification_name,
			"count": limit,
			"list": promotion_list
		};
     	return data;
	}
	
	//  分類、優惠
	// ajax到action
	function ajax_send(data,time){
		$.ajax({
			url: 'promotions_action.php',
			type: "POST",
			data:{
				sdata: data,
				action : 'select_cate'
			},
			success:function(resp){
				var res = JSON.parse(resp);
				// console.log(res);
				if (res.status == 'success') {
					if($('.promotion_row:last').length == 0){
						to_loading_show_text('1');
						$('#promotionsTableBody').append(combineHTML('{$config['site_style']}','promotion', res.data));
					}else{
						$('.promotion_row:last').after(combineHTML('{$config['site_style']}','promotion', res.data));
					}
					$('#promotionLoadMore').show();
				}else if(res.status == 'fail'){	
					// 沒有資料
					$('#promotionLoadMore').hide();
					$('#no_data').css('display','flex');
				}

				if(res.counting_data == '0'){
					to_loading_show_text('0');
				}else if(res.counting_data == '1'){
					to_loading_show_text('1');
				}

			}
		})
	}

	// 組成html
	function combineHTML(siteStyle,className, data){	
		var count = $('.promotion_row').length;
		var html = '';
		var loadMoreDataHtml = data.reduce(function(accumulator, currentValue, currentIndex, array) {
			if(siteStyle == 'mobile'){
				html = `
				<li  class=" row b-c-7a7a `+className+`_row" id="`+className+`_`+currentValue.id+`" onclick="document.location='promotions_detail.php?id=`+currentValue.id+`';">
				<div class="id_name_box">
					<div class="id_name">
					<span>`+((count + currentIndex) + 1)+`</span>
					</div>
				</div>
				<div class="col-10">
					<div class="por-midle">
						<div class="title_h2 text-truncate">`+currentValue.name+` </div>
						<time>{$tr['pormotions end time']}: `+currentValue.endtime+` </time>
					</div>				
				</div>
				<div class="col-1">
					<i class="fas fa-chevron-right "></i>
				</div>			
				</li>
			`;	
			}else{
				html = `
				<li  class=" row b-c-7a7a `+className+`_row" id="`+className+`_`+currentValue.id+`" onclick="document.location='promotions_detail.php?id=`+currentValue.id+`';">
				<div class="id_name_box">
					<div class="id_name">
					<span>`+((count + currentIndex) + 1)+`</span>
					</div>
				</div>
				<div class="col-11">
					<div class="por-midle d-flex align-items-center">
						<div class="title_h2 text-truncate pr-5">`+currentValue.name+` </div>
						<time class="ml-auto">{$tr['pormotions end time']}: `+currentValue.endtime+` </time>
					</div>				
				</div>
				<div class="promotions_iconend">
					<i class="fas fa-chevron-right "></i>
				</div>			
				</li>
			`;	
			}
					
			return accumulator + html;
		},'');
		return loadMoreDataHtml;
	}

	$(document).ready(function() {
		//add css
		$('.container').addClass('promotionsclass');
		$('.block-layout').on('click',off_slidemenu);
		$('#promotionLoadMore').hide();
		$('#no_data').hide();

		time = Math.floor($.now()/1000);
		data = get_query_source_condition();
		ajax_send(data,time);

		// 全部分類
		$('.cl_cates').click(function(e){
			$('.promotion_row').remove();
			$('#no_data').hide();
			var datetext = $(e.target).text(); // 分類name
			var seldatevalue = $(e.target).attr("data-dateval");// 分類name

			if($(e.target).attr('class') == 'ca_tdcancel'){

			}else{
				$('#a_promotion_cate_select').text(datetext);
				$('#a_promotion_cate_select').val(seldatevalue);
			}
			var data = get_query_source_condition();
			ajax_send(data,time);
			off_slidemenu();
     	});

		// 全部優惠
		$('.cl_promotions').click(function(e){
			$('.promotion_row').remove();
			$('#no_data').hide();
			var show_promotion_list = $(e.target).text();
			var sel_promotion_cla = $(e.target).attr("data-dateval");// 分類name

			$('#promotions_select_list').text(show_promotion_list);
			$('#promotions_select_list').val(sel_promotion_cla);

			var data = get_query_source_condition();
			ajax_send(data);
			off_slidemenu();
		})

		// loadmore
		$('#promotionLoadMore').click(function(e){
			var data = get_query_source_condition();
			ajax_send(data,time);
		});

	});

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
// banner標題
$tmpl['banner'] = ['Pormotions'];
// menu增加active
$tmpl['menu_active'] =['promotions.php'];
// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------

include $config['template_path'] . "template/static.tmpl.php";

// include $config['template_path'] . "template/admin_fluid.tmpl.php";

?>