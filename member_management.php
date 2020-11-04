<?php
// ----------------------------------------------------------------------------
// Features:	前台 -- 會員管理
// File Name:   member_management.php
// Author:		Mavis、Damocles
// Related:
// Log:
//
// ----------------------------------------------------------------------------

session_start();

// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

require_once dirname(__FILE__) ."/member_management_lib.php";

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
$function_title 		= $tr['membercenter_member_management'];//'会员管理'
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
		<li><a href="member.php">{$tr['Member Centre']}</a></li>
		<li class="active">{$function_title}</li>
	</ul>
HTML;

if($config['site_style']=='mobile'){
  	$navigational_hierarchy_html =<<<HTML
		<a href="{$config['website_baseurl']}menu_admin.php?gid=agent"><i class="fas fa-chevron-left"></i></a>
		<span>{$function_title}</span>
		<i></i>
HTML;
}

// ----------------------------------------------------------------------------

// var_dump($_SESSION['member']->id); exit();
// 協助註冊 highlight
if( isset($_GET['a']) && (!is_null($_GET['a'])) ){
	$member_account_parameter = base64_decode(filter_var($_GET['a'],FILTER_SANITIZE_STRING));
}
else{// 主選單
	$member_account_parameter = "";
}

// 參考使用 datatables 顯示
// https://datatables.net/examples/styling/bootstrap.html
$extend_head =<<<HTML
	<link rel="stylesheet" type="text/css" href="{$cdnfullurl_js}datatables/css/jquery.dataTables.min.css">
	<script type="text/javascript" language="javascript" src="{$cdnfullurl_js}datatables/js/jquery.dataTables.min.js"></script>
	<script type="text/javascript" language="javascript" src="{$cdnfullurl_js}datatables/js/dataTables.bootstrap.min.js"></script>
	<script type="text/javascript" language="javascript" src="//cdn.datatables.net/plug-ins/1.10.12/api/sum().js"></script>

	<style>
	.dataTables_filter, .dataTables_info {
		display: none;
	}
	#member_list_paginate .pagination{
	    display: none;
  	}
  	</style>
HTML;

if( !isset($_SESSION['member']) || ($_SESSION['member']->therole!='A') ){
	echo '<script> location.replace("./home.php"); </script>';
}
else{ // 協助開戶
	$register_help = '';
	if( isset($protalsetting['register_agenthelp_isopen']) && ($protalsetting['register_agenthelp_isopen']=='on')){

		if($config['site_style']=='mobile'){
		$register_help = '
		<a href="register_agenthelp.php">
			<button type="button" class="btn btn-primary help_register">'.$tr['membercenter_register_agenthelp'].'</button>
		</a>';
		}else{
		$register_help = '
		<a href="register_agenthelp.php">
			<button type="button" class="btn btn-primary help_register">'.$tr['membercenter_register_agenthelp'].'</button>
		</a>';
		}
	}


	$col_name =<<<HTML
	<tr class="row">
        <th scope="col-3" class="col-4">{$tr['Account']}</th>
        <th scope="col-2" class="col-2">{$tr['type']}</th>
        <th scope="col-3" class="col-3">{$tr['Recent login']}</th>
        <th scope="col-4" class="col-3">{$tr['Number of subordinates']}</th>
    </tr>
HTML;
	if($config['site_style']=='mobile'){
	$table_content =<<<HTML
			<div class="row filter_list_button">
				<div class="col-9">
					<input id="id_q_account" type="text" name="serch_bar" class="form-control" value="" placeholder={$tr['memberInquiry']}>
				</div>
				<div class="col-3">
					<button id="search_send" type="button" class="btn btn-outline-secondary btn-block">{$tr['search']}</button>
				</div>
			</div>

			<table id="member_list" class="table_liststyle" cellspacing="0" width="100%">
				<thead>{$col_name}</thead>
				<tbody></tbody>
			</table>
HTML;		
	}else{
		$table_content =<<<HTML
			<div class="row tablehead management_header">
				<div class="search">
					<div>
						<input id="id_q_account" type="text" name="serch_bar" class="form-control" value="" placeholder={$tr['memberInquiry']}>
					</div>
					<div>
						<button id="search_send" type="button" class="btn btn-outline-secondary btn-block">{$tr['search']}</button>
					</div>
				</div>
				<div>
					{$register_help}
				</div>
			</div>

			<table id="member_list" class="table_liststyle" cellspacing="0" width="100%">
				<thead>{$col_name}</thead>
				<tbody></tbody>
			</table>
HTML;		
	}


	// 載入更多
	$table_content_btn = <<<HTML
			<button id="loadmore_btn" class="send_btn load-more" onclick="loadmore()">{$tr['load more']}</button>
HTML;

	// 手機顯示
	if($config['site_style']=='mobile'){
		$navigational_hierarchy_html =<<<HTML
			<a href="{$config['website_baseurl']}menu_admin.php?gid=agent"><i class="fas fa-chevron-left" aria-hidden="true"></i></a>
			<span>{$function_title}</span>
			<i>{$register_help}</i>
HTML;
	$indexbody_content = <<<HTML
		<div class="row">
			<div class="col-12">{$table_content}</div>
		</div>
		{$table_content_btn}
HTML;
	}
	// 桌機顯示
	else{
		$indexbody_content =<<<HTML
			<div class="col-12 member_management_table">{$table_content}</div>
			{$table_content_btn}
HTML;
	}
}

$extend_js =<<<HTML
	<script type="text/javascript" language="javascript" class="init">

	//載入更多
	function loadmore(){
		//抓取DataTable array
		var oTable = $('#member_list').DataTable().page.info();
		//他會顯示每頁的長度
		var length = oTable.length;
		//顯示結束的長度
		var end = oTable.end;
		//如果結束長度 = 0 就是沒資料
		if(end == 0){
			$('#loadmore_btn').text('{$tr['no more data']}');
			return true;
		}
		//click button 載入 + 10
		$('#member_list').DataTable().page.len(length+10).draw();
		//長度 原本的 10 項 + 新載入的 10項
		length = $('#member_list').DataTable().page.info().length;
		//結束的長度
		end = $('#member_list').DataTable().page.info().end;
		if(length > end){
			$('#loadmore_btn').text('{$tr['no more data']}').attr("disabled", true);
			return true;
		}
	} // end loadmore

  	// 彈掉選項視窗
	function combineMenuHtml(id) {
		var html = `
		<div class="modal fade MenuModal" id="` + id + `_MenuModal" tabindex="-1" role="dialog" aria-labelledby="vLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered" role="document">
				<div class="modal-content modal_contentstyle">
					<div class="modal-header">
						<h6 class="modal-title" id="` + id + `_MenuModalLabel">{$tr['Please select an operation']}</h6>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body member_management">
						<a href="mo_translog_query.php?transpage=a&id=`+ id +`" class="d-block"><button type="button" class="btn btn-link btn-lg btn-block agent_transfer text-dark" value="` + id + `"><h6>{$tr['Transaction log']}</h6></button></a>

						<a href="moa_betlog.php?betpage=a&id=`+ id +`" class="d-block"><button type="button" class="btn btn-link btn-lg btn-block staticreport text-dark" value="` + id + `"><h6>{$tr['Betting log']}</h6></button></a>

						<a href="agencyarea.php?id=`+ id +`" class="d-block"><button type="button" class="btn btn-link btn-lg btn-block staticreport text-dark" value="` + id + `"><h6>{$tr['agencyarea setting']}</h6></button></a>
					</div>
					<div class="modal-footer border-0 p-0"></div>
				</div>
			</div>
		</div>
		`;
		return html;
	} // end combineMenuHtml

	$(function(){
		// 初始化
		// var parameter_value = '$member_account_parameter';
		$("#member_list").DataTable({
			"bLengthChange": false,
			"bProcessing":   true,
			"bServerSide":   true,
			"bRetrieve":     true,
			"searching":     true,
			"bFilter":       false,
			"pageLength":    7,
			"aaSorting":     [[0,"desc"],[2,"asc"]],
			"oLanguage": {
                "sSearch": "{$tr['member account']}:",//会员帐号
                "sEmptyTable": "{$tr['no data']}",//目前没有资料
                "sLengthMenu": "{$tr['each page']} _MENU_ {$tr['item']}",//每页显示笔
                "sZeroRecords": "{$tr['no data']}",//目前没有资料
                "sInfo": "{$tr['now at']} _PAGE_ {$tr['page']}，{$tr['total']} _PAGES_ {$tr['page']}",//目前在第页共页
                "sInfoEmpty": "{$tr['no data']}",//目前没有资料
                "sInfoFiltered": "({$tr['from']} _MAX_ {$tr['filtering in data']})",//从笔资料中过滤
                "oPaginate": {
                    "sPrevious": "{$tr['previous']}",//上一页
                    "sNext": "{$tr['next']}"//下一页
                }
            },
			"ajax": {
				"url":  "member_management_action.php?s=detail",
				"type": "POST",
				"data": {},
			},
			"columns":[
				{"data": "member_name"},
				{"data": "member_level"},
				{"data": "last_login"},
				{"data": "member_lower_level"},
				{"data": "parent"}
			],
			"columnDefs": [
				{"targets":[0],"className":"col-4"},
				{"targets":[1],"orderable": false,"className":"col-2"},
				{"targets":[2],"className":"col-3 d-flex justify-content-center"},
				{"targets":[3],"className":"col-3 d-flex justify-content-center"},
				{"targets": [4],"visible": false,"searchable": false}
			],
			"rowCallback": function(row,data){
				/* if( data.login_member==data.parent ){ // && (parameter_value==data.member_name)
					$('td', row).addClass('alert-success');
				}
				else{
					$('td', row).removeClass('alert-success');
				} */ // 不知其用途，暫時關閉
				$('td', row).removeClass('alert-success');
				$(row).addClass("row");
			},
			"drawCallback":function(settings){
				if( (parseInt(this.api().page.info().page) + 1) >= (this.api().page.info().pages) ){
					$('#loadmore_btn').text('{$tr['no more data']}');
				}
				else{
					$('#loadmore_btn').text('{$tr['load more']}');
					$("#transactionDataTable thead").remove();
				}
			}
		}); // end DataTable

		  // 查看下級
		$(document).on('click','#to_get_lower_detail',function(e){
			e.preventDefault();
			var search = {
					'keyword': '', // 欲搜尋的關鍵字
					'id': $(this).data("children-id"),
					'depth': (parseInt($(this).data("depth")) + 1),
					'parent_id': ''
				},
				search_json = JSON.stringify(search);
			$("#member_list").DataTable().search( search_json ).draw();
		});

		// 上級
		$(document).on('click','#to_get_upper_detail',function(e){
			e.preventDefault();
			var search = {
					'keyword': '', // 欲搜尋的關鍵字
					'id': '',
					'depth': (parseInt($(this).data("depth")) - 1),
					'parent_id': $(this).data("grandparent-id")
				},
				search_json = JSON.stringify(search);
			$("#member_list").DataTable().search( search_json ).draw();
		});

		// click 檢查id有沒有上下級
		$('#member_list tbody').on('click', 'tr', function (e) {
			e.preventDefault();
			var mdata = $("#member_list").DataTable().row(this).data(); 
			// console.log(mdata);

			if(typeof mdata == 'undefined'){
				return false;
			}
			var id = mdata.id; // console.log('id：' + id);
			var account = mdata.member_name; // console.log(account);

			// 類型
			// var depth = mdata.member_level;

			// 類型(只顯示數字)
			var depth = mdata.member_level_hide;

			// 上層
			var parent_id = mdata.parent;
			// highlight
			var shouldHighlight = mdata.shouldHighlight;
			// var csrftoken = '$csrftoken';

			// modal
			$('#'+id+'_MenuModal').children(".member_data").remove();
			$.post("member_management_action.php?s=member_details",{
				"id": id,
				"parent_id": parent_id,
				"depth": depth,
				"shouldHighlight": shouldHighlight
			},
			function(response){
				$("#"+ id +"_MenuModal .modal-body").append(response);
			}); // end post

			$('#member_list').after(combineMenuHtml(id)); // 加入指定區塊外的最後
			$('#' + id + '_MenuModal').modal('show');
		}); //end #member_list tbody click

		// 搜尋會員
		$("#search_send").click(function(){
			var search = {
					'keyword': $("#id_q_account").val(), // 欲搜尋的關鍵字
					'id': '',
					'depth': '',
					'parent_id': ''
				},
				search_json = JSON.stringify(search);
			$("#member_list").DataTable().search( search_json ).draw();
		}); // end click

		// 鍵盤輸入偵測
		$("#id_q_account").keydown(function(e){
			var _which = e.which;
			if( _which==13 ){
				$("#search_send").click();
			}
		}); // end keydown

	}); // END FUNCTION
	</script>
HTML;

// ----------------------------------------------------------------------------
// MAIN  END
// ----------------------------------------------------------------------------

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
$tmpl['sidebar_content'] =['agent','member_management'];
// banner標題
$tmpl['banner'] = ['membercenter_menu_admin_agent'];
// menu增加active
$tmpl['menu_active'] =['agent_instruction.php'];

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include $config['template_path'] . "template/admin.tmpl.php";
?>
