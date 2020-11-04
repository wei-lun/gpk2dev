<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 站內信件
// File Name:	stationmessage.php
// Author:		Barkley
// Related:
// Log:
// 只有登入的會員才可以看到這個功能。
// ----------------------------------------------------------------------------



// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

// var_dump($_SESSION);

// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------


// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
$function_title 		= $tr['membercenter_stationmail'];//'站內信件'
// 擴充 head 內的 css or js
$extend_head				= '';
// 放在結尾的 js
$extend_js					= '';
// body 內的主要內容
$indexbody_content	= '';
// 系統訊息選單
$messages 					= '';
// 初始化變數 end
// ----------------------------------------------------------------------------
// 導覽列
$navigational_hierarchy_html = '
<ul class="breadcrumb">
  <li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
  <li><a href="member.php">'.$tr['Member Centre'].'</a></li>
  <li class="active">'.$function_title.'</li>
</ul>
';
// ----------------------------------------------------------------------------




// 有會員資料 不論身份 therole 為何，都可以修改個人資料。但是除了 therole = T 除外。
if(isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T' ) {

  $message_html = '';
  $msgfrom_account = 'mtchang';
  $msgto_account = 'gpk';

  $sql = "SELECT id,sendtime, to_char((sendtime AT TIME ZONE 'CST'),'MM-DD HH24:MI:SS' )  as cst_sendtime ,msgfrom,msgto,message,read FROM root_stationmessage WHERE (msgfrom = '$msgto_account' AND msgto = '$msgfrom_account') OR (msgfrom = '$msgfrom_account' AND msgto = '$msgto_account') ORDER BY id LIMIT 30;";
  // var_dump($sql);
  $msg_result = runSQLall($sql);
  // var_dump($msg_result);
  if($msg_result[0] >=0 ) {

    for($i=1;$i<=$msg_result[0];$i++) {

      if($msg_result[$i]->msgfrom == $msgto_account) {
        // from A -- station
        $message_a_text = $msg_result[$i]->message;
        $message_a_time = $msg_result[$i]->cst_sendtime;
        $message_a_name = $msgfrom_account;
        $message_a_read = $msg_result[$i]->read;
      	$message_html = $message_html.'
        <div class="row">
          <div class="col-12 col-md-3">
          </div>
        	<div class="col-12 col-md-6">
          <p align="left"><img src="'.$cdnrooturl.'message_cs.png" height="40px" width="40px" />&nbsp;&nbsp;<span>客服金小姐</span></p>
        	<p align="left"><button class="btn btn-info" type="submit">'.$message_a_text.' </button>&nbsp;<span style="color:#808080;">'.$message_a_time.'</span></p>
        	</div>
          <div class="col-12 col-md-3">
          </div>
        </div>
        ';
      }else{
        // to B -- customer
        $message_b_text = $msg_result[$i]->message;
        $message_b_time = $msg_result[$i]->cst_sendtime;
        $message_b_name = $msgfrom_account;
        $message_b_read = $msg_result[$i]->read;
      	$message_html = $message_html.'
        <div class="row">
          <div class="col-12 col-md-3">
          </div>
        	<div class="col-12 col-md-6">
        	<p align="right">
          <span style="color:#808080;">'.$message_b_time.'</span>&nbsp;<button class="btn btn-success" type="submit">'.$message_b_text.'</button>
          <span style="color:#808080;font-size: x-small ;">'.$message_b_read.'</span>
          </p>
        	</div>
          <div class="col-12 col-md-3">
          </div>
        </div>
        ';
      }
      // end if

    }
    // end loop get message

  }else{
    $logger = 'DB error';
    echo $logger;
  }

  $send_message_html = '';
  $send_message_html = $send_message_html.'

  <div class="row">
    <div class="col-12 col-md-3">
    </div>
  	<div class="col-12 col-md-6">
      <hr>
      <input type="text" class="form-control" id="send_message_text" placeholder="Type a Message here ...">
      <button class="btn btn-info" id="submit_to_send_message" type="submit">SEND</button>
  	</div>

    <div class="col-12 col-md-3">
    </div>
  </div>
  ';

$extend_js = "
  <script>
  	$(document).ready(function() {
  		$('#submit_to_send_message').click(function(){
  			var send_message_text = $('#send_message_text').val();

  			if(jQuery.trim(send_message_text) != '') {
  				$.post('stationmessage_action.php?a=stationmessage_send',
  					{
  						send_message_text: send_message_text,
  					},
  					function(result){
  						$('#preview').html(result);}
  				);
  			}
  		});

  	});
  </script>
";

}else{
	// 不合法登入者的顯示訊息
	$message_html = $tr['login first'];//'(x) 請先登入會員，才可以使用此功能。'
  $send_message_html = '';
}



// 切成 3 欄版面
$indexbody_content = $indexbody_content.'
'.$message_html.'
'.$send_message_html.'
<div class="row">
	<div class="col-12 col-md-3">
	</div>

	<div class="col-12 col-md-6">
		<div id="preview"></div>
	</div>

	<div class="col-12 col-md-3">
	</div>
</div>
';


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
$tmpl['paneltitle_content'] 			= '<span class="glyphicon glyphicon-bookmark" aria-hidden="true"></span>'.$function_title;
// 主要內容 -- content
$tmpl['panelbody_content']				= $indexbody_content;


// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include($config['template_path']."template/admin.tmpl.php");

?>
