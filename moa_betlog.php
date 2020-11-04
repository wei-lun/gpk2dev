<?php
// ----------------------------------------------------------------------------
// Features:  前台 行動版 -- 代理商下線投注紀錄
// File Name: moa_betlog.php
// Author:    YaoYuan
// Related:   
// DB Table:  
// Log:
// ----------------------------------------------------------------------------

session_start();

// 載入預設lib檔
require_once dirname(__FILE__) ."/moa_betlog_lib.php";

// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();

// var_dump($_SESSION);// die();

// 初始化變數
// 功能標題，放在標題列及meta
$function_title = $tr['membercenter_betrecord'];
// 擴充 head 內的 css or js
$extend_head        = '';
// 放在結尾的 js
$extend_js          = '';
// body 內的主要內容
$indexbody_content  = '';
// 系統訊息選單
$messages           = '';
// ----------------------------------------------------------------------------

// 身分不存在、測試、管理員身份，皆不合法
// if(!isset($_SESSION['member']) OR $_SESSION['member']->therole == 'T' OR $_SESSION['member']->therole == 'R') {
//     echo '<script>alert("不合法的身份！");history.go(-1);</script>';die();
// }
if (!isset($_SESSION['member'])) {
  echo '<script>document.location.href="./home.php";</script>';
}

// 判斷是否為代理商a或會員m網頁
if (isset($_GET['betpage']) AND ($_GET['betpage']=='a' OR $_GET['betpage']=='m') ) {
    $betpage = filter_var($_GET['betpage'], FILTER_SANITIZE_STRING);
}else{
   echo '<script>alert("'.$tr['Web identity does not exist,code'].'：190114。");history.go(-1);</script>';die();

}

if (isset($_GET['id'])) {
    $query_id             = filter_var($_GET['id'], FILTER_SANITIZE_STRING);
    $all_downline_account = json_decode(find_downline_member($_SESSION['member']->id,$betpage), true);
    // var_dump($all_downline_account['id']);var_dump($query_id);die();
    if ($query_id == (string)$_SESSION['member']->id) {
        $inputbox_val = '';
        if($betpage=='m'){$inputbox_val=$_SESSION['member']->account;}//會員，則id為session id
    } elseif (in_array($query_id, $all_downline_account['id']) AND $betpage=='a') {
        $inputbox_val = $all_downline_account['account'][array_keys($all_downline_account['id'], $query_id)['0']];
        // var_dump($all_downline_account['account']);
    } else {
        echo '<script>alert("'.$tr['account does not exist, error code'].'：181219。");history.go(-1);</script>';die();
    }
} else {
    echo '<script>alert("'.$tr['error, account not set
 error, error code'].'：181218。");history.go(-1);</script>';die();
}


// 代理商下線
$all_downline_member=find_downline_member($_SESSION['member']->id,$betpage);
// echo($all_downline_member);die();

$csrftoken = csrf_token_make();
// var_dump($csrftoken);die();


// 取出娛樂城名稱，用於下拉式選單
$casino_kind=casino_kind();



// 導覽列
//$navigational_hierarchy_html = '';

if($betpage == 'a'){
$navigational_hierarchy_html = '
    <ul class="breadcrumb">
      <li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
      <li>'.$tr['membercenter_menu_admin'].'</li>
      <li>'.$tr['membercenter_menu_admin_agent'].'</li>
      <li class="active">'.$function_title.'</li>
    </ul>
';
}
elseif($betpage=='m'){
$navigational_hierarchy_html = '
    <ul class="breadcrumb">
      <li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
      <li>'.$tr['membercenter_menu_admin'].'</li>
      <li class="active">'.$function_title.'</li>
    </ul>
';
}

if($config['site_style']=='mobile'){
  if($betpage == 'a'){
$navigational_hierarchy_html .=<<<HTML
            <a href="{$config['website_baseurl']}menu_admin.php?gid=agent"><i class="fas fa-chevron-left" aria-hidden="true"></i></a>
            <span>{$function_title}</span>
            <i></i>  
HTML;
  }
  elseif($betpage=='m'){
$navigational_hierarchy_html .=<<<HTML
            <a href="{$config['website_baseurl']}menu_admin.php"><i class="fas fa-chevron-left" aria-hidden="true"></i></a>
            <span>{$function_title}</span>
            <i></i>   
HTML;
  }
}

if($config['site_style']=='mobile'){
$filter_list_button = <<<HTML
    <div id="morahtm_show" class="row filter_list_button">
        <div class="col-9">
            <div class="inner-addon left-addon">
              <span class="input-group-addon glyphicon glyphicon-search" aria-hidden="true"></span>
              <input id="id_q_account" type="text" name="serch_bar" class="form-control" value="{$inputbox_val}" placeholder="{$tr['Subordinate bet inquiry']}">
            </div>
        </div>
        <div class="col-3">    
            <button id="search_send" type="button" class="btn btn-outline-secondary btn-block">{$tr['search']}</button>
        </div>
    </div>
HTML;
}else{
$filter_list_button = <<<HTML
    <div id="morahtm_show" class="filter_list_button merge_filter">
      <div class="d-flex justify-content-between align-items-center">
        <div class="translog_search">
            <div class="inner-addon left-addon">
              <span class="input-group-addon glyphicon glyphicon-search" aria-hidden="true"></span>
              <input id="id_q_account" type="text" name="serch_bar" class="form-control" value="{$inputbox_val}" placeholder="{$tr['Subordinate bet inquiry']}">
            </div>
        </div>
        <div class="translog_ser_button">    
            <button id="search_send" type="button" class="btn btn-outline-secondary btn-block">{$tr['search']}</button>
        </div>
      </div>  
    </div>
HTML;
}
//投注狀態名稱
if($config['site_style']=='mobile'){
  $betstatus = $tr['bet_status_abbreviation'];
}else{
  $betstatus = $tr['bet status'];
}
$mobile_indexbody_content = <<<HTML
  <div id="member_noexist" class="query_failed">
    <p class="">{$tr['member does not exist']}！</p>
  </div>

<!-- 1224 yaoyuan啟始 -->
  <div id="moa_betlog">
      {$filter_list_button}
      <div class="filter_list_button filter_ser">
          <div>
            <button id="id_date_select" type="button" onclick="on_slidemenu('date_select');" class="btn btn-outline-secondary dropdown-toggle" value="today">{$tr['today']}</button>
          </div>
          <div>
            <button id="id_casino_select" type="button" onclick="on_slidemenu('casino_filter');" class="btn btn-outline-secondary dropdown-toggle mr-2" value="all_casino">{$tr['Casino']}</button>
            <button id="id_betstatus_select" type="button" onclick="on_slidemenu('status_filter');" class="btn btn-outline-secondary dropdown-toggle" value="all_status">{$betstatus}</button>
          </div>
      </div>

      <div class="col table_content">    
      <table class="table table-hover modal_click table_from6" id="betTable">
        <tbody id="betTableBody">
        </tbody>
      </table>
      </div>

      <div class="row" id="idbetloadmore">
        <div class="col">
          <button type="button" class="send_btn loadMore load-more" id="betLoadMore" value="bet"
          data-toggle="modal" data-target="#exampleModal">{$tr['load more']}</button>
        </div>
      </div>
  </div>

<!-- 1224 yaoyuan結尾 -->
<div class="block-layout motransaction_layout"></div> 
<div id="date_select" class="slide-up-menu slide-up-style">         
  <table class="table">
      <thead class="thead-light">
        <tr><th class="bg-secondary">{$tr['please select date']}</th></tr>
        <tr><td class="cl_date" data-dateval="today">{$tr['today']}</td></tr>
        <tr><td class="cl_date" data-dateval="yesterday">{$tr['yesterday']}</td></tr>
        <tr><td class="cl_date" data-dateval="week">{$tr['week']}</td></tr>
        <tr><td class="cl_date" data-dateval="month">{$tr['month']}</td></tr>
        <tr><td class="cl_date" data-dateval="lastmonth">{$tr['lastmonth']}</td></tr>
        <tr><td class="ca_tdcancel bg-secondary" onclick="off_slidemenu()">{$tr['cancel']}</td></tr>
      </thead>
  </table> 
</div>
<div id="casino_filter" class="slide-up-menu slide-up-style">         
  <table class="table">
      <thead class="thead-light">
        <tr><th class="bg-secondary">{$tr['please select casino']}</th></tr>
        <tr><td class="cl_casino" data-casinoval="all_casino">{$tr['Casino']}</td></tr>
        {$casino_kind}
        <tr><td class="ca_tdcancel bg-secondary" onclick="off_slidemenu()">{$tr['cancel']}</td></tr>
      </thead>
  </table> 
</div>
<div id="status_filter" class="slide-up-menu slide-up-style">         
  <table class="table">
      <thead class="thead-light">
        <tr><th class="bg-secondary">{$tr['please select bet status']}</th></tr>
        <tr><td class="cl_status" data-statusval="all_status">{$tr['bet status']}</td></tr>
        <tr><td class="cl_status" data-statusval="Paid">{$tr['Paid']}</td></tr>
        <tr><td class="cl_status" data-statusval="Unpaid">{$tr['Unpaid']}</td></tr>
        <tr><td class="cl_status" data-statusval="modified">{$tr['modified']}</td></tr>
        <tr><td class="ca_tdcancel bg-secondary" onclick="off_slidemenu()">{$tr['cancel']}</td></tr>
      </thead>
  </table> 
</div>


HTML;


$extend_head = <<<HTML
<script src="./in/jquery/jquery-ui.min.js"></script>
<script type="text/javascript" language="javascript" src="{$cdnfullurl_js}datatables/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" language="javascript" src="{$cdnfullurl_js}datatables/js/dataTables.bootstrap.min.js"></script>
<!-- <script src="./in/jquery/dataTables.scroller.min.js"></script> -->
<link rel="stylesheet" href="./in/jquery/jquery-ui.css">
<!-- <link rel="stylesheet" href="./in/jquery/scroller.dataTables.min.css"> -->
<style type="text/css">
table{
width: 100%;
}
/*
.block-layout{
background-color: #00000080;
position: fixed;
height: 100vh;
width: 100vw;
z-index: 10;
top: 0;
left: 0;
display: none;
}
.slide-up-menu {
border-top: 1px solid #999999;
position:fixed;
width: auto;
height: auto;
z-index: 20;
text-align:center;
font-size:18px;
color: #000;
background: #FFF;
display: flex;
justify-content: center; 
right: 0;
left: 0;
margin-right: auto;
margin-left: auto;
margin-bottom:60px;
bottom: -1500px;
transition: all 0.2s;
}
.slide-up {
bottom: 0px;
}
*/
/*enable absolute positioning*/
.inner-addon { 
position: relative; 
}
/*style icon*/
.inner-addon .glyphicon {
position: absolute;
padding: 10px;
pointer-events: none;
}
/*align icon*/
.left-addon .glyphicon { left: 0px;}
.right-addon .glyphicon { right: 0px;}
/*add padding*/
.right-addon input { padding-right: 30px; }


#nodata ,#member_noexist { 
position: absolute; 
width: 400px;
min-height: 40px;
left:50%;
bottom:50%;
transform: translate(-50%,-50%);
display: none;
z-index:10;
color:#856404;
border: 2px solid #ffeeba;
border-radius: 20px;
background-color:#fff3cd;
}
/*#nodata p ,#member_noexist p {
margin: 0; 
padding: 0.4em; 
text-align: center;
font-size: 1.5vw; 
}
#nodata p ,#member_noexist p {
margin: 0; 
padding: 0.6em; 
text-align: center;
font-size: 1.35em; 
}*/

/*
tr.betData td:first-child{
color: #FF6600;
font-size: 14px;
}
*/

.table tbody tr td {
text-align:left;
}

.table tbody tr th {
text-align:right;
}
</style>
</style>
<script>
    function off_slidemenu(){
      $('.slide-up-menu').removeClass('slide-up');
      $('.block-layout').fadeOut();
    }

    function on_slidemenu(toggle){  
      $('#'+toggle).addClass('slide-up');
      $('.block-layout').fadeIn();
    }
    
    // Run the effect
    function runEffect(idname) {
      $('#'+idname ).show( 'fade',  500, callback(idname) );
    };

    //callback function to bring a hidden box back
    function callback(idname) {
      setTimeout(function() {
        $( "#"+idname+":visible" ).removeAttr( "style" ).fadeOut();
        // $( "#member_noexist:visible" ).removeAttr( "style" ).fadeOut();
      }, 2000 );
    };

    //-------------2018/12/26  yaoyuan  start------------------------------
    function getSearchRequirementValue(){
        // 抓出時間區間
        var date_val=$('#id_date_select').val();
        // 取出娛樂城
        var casino_val=$('#id_casino_select').val();
        // 取出注单状态
        var betstatus_val=$('#id_betstatus_select').val();
        // 取出帳號
        var account=$('#id_q_account').val().trim();
        // 抓出目前的資料筆數
        var tr_offset = $('.betData').length;

        var data = {
          'sel_date':date_val,
          'sel_casino':casino_val,
          'sel_betstatus':betstatus_val,
          'account':account,
          'tr_offset':tr_offset
        };
      return data;
    }

    // 判斷帳號是否為下線。無值：不判斷；有值：1.正確，pass。2.錯誤，不存在下線，顯示alert。
    function downline_search(all_downline){
        var acctext=$('#id_q_account').val().trim();
        // console.log(acctext);
        // console.log(all_downline);
        if (acctext ==''){
        }else{
          // console.log(all_downline);
          if (all_downline.account.includes(acctext)){
          }else{
            // alert("会员 "+acctext+" 不存在！");
            runEffect('member_noexist');
            $("#member_noexist" ).hide();
            return false;
          }
        }
        return true;
    }

    function combineLoadMoreDataHtml(data) {
        var html = '';
        // var receiveMoneyStatus = '';
        // console . log('className:'+className);
        // console . log('data:'+data);

        var loadMoreDataHtml = data.reduce(function(accumulator, currentValue, currentIndex, array) {
        // console . log('accumulator.   :'+accumulator);
        // console . log('currentValue.id  :'+currentValue.id);
        // console . log('currentIndex  :'+currentIndex);
        // console . log('array   :'+array);

          html = `
          <tr class="row betData" id="`+currentValue.rowid+`">
            <td class="col-9">`+currentValue.casinoid+`：`+currentValue.game_name+`</td>
            <td class="col-3">$`+currentValue.betamount+`</td>
            <td class="col-9">`+currentValue.rowid+` ( `+currentValue.user_account+` ) </td>
            <td class="col-3">`+currentValue.betresult+`</td>
            <td class="col-9">`+currentValue.bet_time+`</td>
            <td class="col-3"><span class="btn btn-outline-secondary btn-sm disabled">`+currentValue.status+`</span></td>
          </tr>
          `;

          // console . log(accumulator + html);
          return accumulator + html;
        }, '');

      return loadMoreDataHtml;
    }

    function onoff_loadmore(onoff){
        if(onoff=='1'){
            $('#betLoadMore').text('{$tr['load more']}');
            $('#betLoadMore').prop('disabled', false);
        }else{
            $('#betLoadMore').text('{$tr['no more data']}');
            $('#betLoadMore').prop('disabled', true);
        }
    }

    function combinedetail(data){
      var html = '';
      var loadMoreDataHtml = data.reduce(function(accumulator, currentValue, currentIndex, array) {
        html = `
          <div class="modal fade" id="`+currentValue.rowid+`_modal" tabindex="-1" role="dialog" aria-labelledby="_modalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
              <div class="modal-content modal_contentstyle">
                <div class="modal-header">
                  <h6 class="modal-title" id="_modalLabel">{$tr['bet info']}</h6>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                <div class="modal-body p-0">
                  <div class="table-responsive">
                    <table class="table table-striped mb-0 moa_betlog_table">
                    <tbody>
                      <tr scope="row">
                        <th scope="col-6">{$tr['bet number']}</th>
                        <td scope="col">`+currentValue.rowid+`</td>
                      </tr>
                      <tr scope="row">
                        <th scope="col-6">{$tr['member account']}</th>
                        <td scope="col">`+currentValue.user_account+`</td>
                      </tr>
                      <tr scope="row">
                        <th scope="col-6">{$tr['bet time']}</th>
                        <td scope="col">`+currentValue.bet_time+`(EDT)</td>
                      </tr>
                      <tr scope="row">
                        <th scope="col-6">{$tr['game name']}</th>
                        <td scope="col">`+currentValue.game_name+`</td>
                      </tr>
                      <tr scope="row">
                        <th scope="col-6">{$tr['Game classification']}</th>
                        <td scope="col"> `+currentValue.game_category+`</td>
                      </tr>
                      <tr scope="row">
                        <th scope="col-6">{$tr['Betting']}</th>
                        <td scope="col">$`+currentValue.betamount+`</td>
                      </tr>
                      <tr scope="row">
                        <th scope="col-6">{$tr['Profit amount']}</th>
                        <td scope="col">`+currentValue.betresult+`</td>
                      </tr>
                      <tr scope="row">
                        <th scope="col-6">{$tr['Profit time']}</th>
                        <td scope="col">`+currentValue.receive_time+`(EDT)</td>
                      </tr>
                       <tr scope="row">
                        <th scope="col-6">{$tr['bet status']}</th>
                        <td scope="col">`+currentValue.status+`</td>
                      </tr>
                      <tr scope="row">
                        <th scope="col-6">{$tr['Casino']}</th>
                        <td scope="col">`+currentValue.casinoid+`</td>
                      </tr>
                    
                    </tbody>
                  </table>

                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-block btn-secondary" data-dismiss="modal">关闭</button>
                </div>
              </div>
            </div>
          </div>
          `;
          return accumulator + html;
      }, '');
      return loadMoreDataHtml;
    }

    function ajax_send(csrftoken,getdata,time,action){
        $.ajax({
          type: 'POST',
          url: 'moa_betlog_action.php',
          data: {
            action: action,
            time:time,
            csrftoken: csrftoken,
            data:getdata
          },
          success: function(resp) {
            // $('#postresult').html(resp);
            var res = JSON.parse(resp);
            
            if (res.status == 'success') {
              // console.log('result:'+res.result);
              if ($('.betData').length == 0) {
                // console.log(combineLoadMoreDataHtml(res.result));
                // console . log(combinedetail(res . result));
                onoff_loadmore('1');

                $('#betTableBody').append(combineLoadMoreDataHtml(res.result));
                $('#idbetloadmore').after(combinedetail(res.result));

              } else {
                // console.log(combinedetail(res.result));
                $('.betData:last').after(combineLoadMoreDataHtml(res.result));
                $('#idbetloadmore').after(combinedetail(res.result));

              }
              $('#betLoadMore').show();

            }else if(res.status == 'query_fail'){
                runEffect('nodata');
                $( "#nodata" ).hide();
                var msg=$('#id_date_select').text().trim();
                $('.data_empty').remove();
                $('#betTableBody').append('<tr class="data_empty no_data row"><td class="col no_data_style"><p>'+msg+',{$tr["no betlog"]}</p></td></tr>');
                $('#betLoadMore').hide();
            }else if(res.status == 'loadmore_fail'){
                onoff_loadmore('0');
            }
            
            if(res.hvaedata == '0'){
                onoff_loadmore('0');
            }else if(res.hvaedata == '1'){
                onoff_loadmore('1');
            }
          }
        });
    }



    //-------------2018/12/13  yaoyuan end------------------------------

    $(document).ready(function() {  
      $('.block-layout').on('click',off_slidemenu); 
      $('#betLoadMore').hide();

      var show_query_html='{$betpage}';
      if(show_query_html=='a'){
          $('#morahtm_show').show();
      }else{
          $('#morahtm_show').hide();
      }

      $('#betLoadMore').hide();


      var csrftoken = '{$csrftoken}';
      var all_downline= JSON.parse('{$all_downline_member}');
      time=Math.floor($.now()/1000);
      if(downline_search(all_downline)){
          var getdata=getSearchRequirementValue();
          ajax_send(csrftoken,getdata,time,'query');
      }      
      // console.log(getdata,all_downline);

      //filter 日期選擇  
      $('.cl_date').click(function(e){
          var datetext=$(e.target).text();
          var seldatevalue=$(e.target).attr("data-dateval");
          $('#id_date_select').text(datetext); 
          $('#id_date_select').val(seldatevalue); 

          if(downline_search(all_downline)){
              $('.betData').remove();
              $('.data_empty').remove();

              time=Math.floor($.now()/1000);
              var getdata   = getSearchRequirementValue();
              ajax_send(csrftoken,getdata,time,'query');
          }
          off_slidemenu();
      });

      //filter casino選擇
      $('.cl_casino').click(function(e){
          var casinotext=$(e.target).text();
          var selcasinovalue=$(e.target).attr("data-casinoval");
          $('#id_casino_select').text(casinotext); 
          $('#id_casino_select').val(selcasinovalue); 

          if(downline_search(all_downline)){
              $('.betData').remove();
              $('.data_empty').remove();

              time=Math.floor($.now()/1000);
              var getdata   = getSearchRequirementValue();
              ajax_send(csrftoken,getdata,time,'query');
          }
          off_slidemenu();
      });

      //filter 注單狀態選擇
      $('.cl_status').click(function(e){
          var statustext=$(e.target).text();
          var selstatusvalue=$(e.target).attr("data-statusval");
          $('#id_betstatus_select').text(statustext); 
          $('#id_betstatus_select').val(selstatusvalue); 

          if(downline_search(all_downline)){
              $('.betData').remove();
              $('.data_empty').remove();

              time=Math.floor($.now()/1000);
              var getdata   = getSearchRequirementValue();
              ajax_send(csrftoken,getdata,time,'query');
          }
          off_slidemenu();
      });

      

      // 搜尋按鈕按下
      $('#search_send').click(function(e){
          if(downline_search(all_downline)){
            $('.betData').remove();
            $('.data_empty').remove();

            time=Math.floor($.now()/1000);
            var getdata=getSearchRequirementValue();
            ajax_send(csrftoken, getdata, time,'query');
          }
      });

      // 加载更多按下
      $('#betLoadMore').click(function(e){
          if(downline_search(all_downline)){
            var getdata=getSearchRequirementValue();
            ajax_send(csrftoken, getdata, time,'loadmore');
          }
      });
    });

    // 注單按下時，顯示詳細資料
    $(document).on('click','.betData', function(){
        var idname=  $(this).attr('id');
        // console.log(idname);
        $('#'+idname+'_modal').modal('show');
    });
</script>

HTML;


if($config['site_style']=='desktop'){
    $indexbody_content = $mobile_indexbody_content;
  }
elseif($config['site_style']=='mobile'){
    $indexbody_content = $mobile_indexbody_content;
  }


// ----------------------------------------------------------------------------
// 準備填入的內容
// ----------------------------------------------------------------------------
// 將內容塞到 html meta 的關鍵字, SEO 加強使用
$tmpl['html_meta_description'] = $config['companyShortName'];
$tmpl['html_meta_author'] = $config['companyShortName'];
$tmpl['html_meta_title'] = $function_title . '-' . $config['companyShortName'];

// 系統訊息顯示
$tmpl['message'] = $messages;
// 擴充再 head 的內容 可以是 js or css
$tmpl['extend_head'] = $extend_head;
// 擴充於檔案末端的 Javascript
$tmpl['extend_js'] = $extend_js;
// 主要內容 -- title
$tmpl['paneltitle_content'] = $navigational_hierarchy_html;
// 主要內容 -- content
$tmpl['panelbody_content'] = $indexbody_content;
// 側邊欄MENU var[0]側邊欄組合(lib_menu) var[1]目前頁籤名
if($betpage == 'a'){
$tmpl['sidebar_content'] =['agent','moa_betlog'];
// banner標題
$tmpl['banner'] = ['membercenter_menu_admin_agent'];
// menu增加active
$tmpl['menu_active'] =['agent_instruction.php'];
}else{
  // banner標題
  $tmpl['banner'] = ['membercenter_moa_betlog'];
  // menu增加active
  $tmpl['menu_active'] =['moa_betlog.php?betpage=m&id='.$_SESSION["member"]->id];
}

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
// 如果有登入的話, 畫面不一樣。
if(isset($_SESSION['member'])) {
  //var_dump($config['site_style']);
  if($config['site_style']=='desktop')
    include($config['template_path']."template/admin.tmpl.php");
  elseif($config['site_style']=='mobile')
    include($config['template_path']."template/admin.tmpl.php");
} else {
  // 訪客註冊使用
  include($config['template_path']."template/member.tmpl.php");
}