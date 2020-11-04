<?php
// ----------------------------------------------------------------------------
// Features:  前台 行動版 -- 會員及代理商，交易紀錄查詢
// File Name: mo_translog_query.php
// Author:    YaoYuan
// Related:   mo_translog_query、mo_translog_query_action、mo_translog_query_lib、
// DB Table:  root_member_gcashpassbook、root_member_gtokenpassbook
// Log:       會員只查自己的紀錄、而代理商只查下線的交易紀錄
// ----------------------------------------------------------------------------

session_start();

// 載入預設lib檔
require_once dirname(__FILE__) ."/mo_translog_query_lib.php";

// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();

// var_dump($_SESSION);// die();

// 初始化變數
// 功能標題，放在標題列及meta
$function_title = $tr['membercenter_mo_transaction_log'];
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
if(!isset($_SESSION['member']) OR ($_SESSION['member']->therole != 'A' AND  $_SESSION['member']->therole != 'M')) {
    echo '<script>document.location.href="./home.php";</script>';
}

// 判斷是否為代理商A或會員M網頁
if (isset($_GET['transpage']) AND ($_GET['transpage']=='a' OR $_GET['transpage']=='m') ) {
    $transpage = filter_var($_GET['transpage'], FILTER_SANITIZE_STRING);
}else{
   echo '<script>alert("'.$tr['Web identity does not exist,code'].'：190114。");history.go(-1);</script>';die();
}

if (isset($_GET['id'])) {
    $query_id             = filter_var($_GET['id'], FILTER_SANITIZE_STRING);
    $all_downline_account = json_decode(find_downline_member($_SESSION['member']->id,$transpage), true);

    // 除了自己與下線可供查詢外，其餘皆錯誤。
    if ($query_id == (string)$_SESSION['member']->id) {
        $inputbox_val = '';
        if($transpage=='m'){$inputbox_val=$_SESSION['member']->account;}//會員，則id為session id
    } elseif (in_array($query_id, $all_downline_account['id']) AND $transpage=='a') {
        $inputbox_val = $all_downline_account['account'][array_keys($all_downline_account['id'], $query_id)['0']];
        // var_dump($all_downline_account['account']);
    } else {
        echo '<script>alert("'.$tr['account does not exist, error code'].'：181219。");history.go(-1);</script>';die();
    }
} else {
    echo '<script>alert("'.$tr['error, account not set error, error code'].'：181218。");history.go(-1);</script>';die();
}

if(isset($_GET['tid']) && $_GET['tid'] != '') {
  $tid = filter_var($_GET['tid'], FILTER_SANITIZE_STRING);
}else{
  $tid='';
}

// 代理商下線，json格式
$all_downline_member=find_downline_member($_SESSION['member']->id,$transpage);
// echo($all_downline_member);die();

$csrftoken = csrf_token_make();
// var_dump($csrftoken);die();


// 導覽列

if($transpage == 'a'){
$navigational_hierarchy_html = '
    <ul class="breadcrumb">
      <li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
      <li>'.$tr['membercenter_menu_admin'].'</li>
      <li>'.$tr['membercenter_menu_admin_agent'].'</li>
      <li class="active">'.$function_title.'</li>
    </ul>
';
}
elseif($transpage=='m'){
$navigational_hierarchy_html = '
    <ul class="breadcrumb">
      <li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
      <li>'.$tr['membercenter_menu_admin'].'</li>
      <li class="active">'.$function_title.'</li>
    </ul>
';
}

// 在mobile版，交易明細左邊，會有回上一頁的功能，會員及代理商不同
if($config['site_style']=='mobile'){
  if($transpage == 'a'){
$navigational_hierarchy_html .=<<<HTML
            <a href="{$config['website_baseurl']}menu_admin.php?gid=agent"><i class="fas fa-chevron-left" aria-hidden="true"></i></a>
            <span>{$function_title}</span>
            <i></i>
HTML;
  }
  elseif($transpage=='m'){
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
              <input id="id_q_account" type="text" name="serch_bar" class="form-control" value="{$inputbox_val}" placeholder="{$tr['Subordinate transaction inquiry']}">
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
              <input id="id_q_account" type="text" name="serch_bar" class="form-control" value="{$inputbox_val}" placeholder="{$tr['Subordinate transaction inquiry']}">
            </div>
        </div>
        <div class="translog_ser_button">
            <button id="search_send" type="button" class="btn btn-outline-secondary btn-block">{$tr['search']}</button>
        </div>
      </div>
    </div>
HTML;
}

$tr_cate = '';
foreach ($transaction_ary as $key => $tray_val) {
    $tr_cate .= '  <tr><td class="cl_cate" data-cateval="' . $tray_val . '">' . $tr[$key] . '</td></tr>';
}

$mobile_indexbody_content = <<<HTML
  <div id="member_noexist" class="query_failed">
    <p class="">{$tr['member does not exist']}！</p>
  </div>

  <div id="mo_translog">
      {$filter_list_button}
      <div class="filter_list_button filter_ser">
          <div>
            <button id="id_date_select" type="button" onclick="on_slidemenu('date_select');" class="btn btn-outline-secondary dropdown-toggle" value="today">{$tr['today']}</button>
          </div>
          <div class="d-flex">
            <button id="id_cash_select" type="button" onclick="on_slidemenu('cash_filter');" class="btn btn-outline-secondary dropdown-toggle mr-2" value="gtoken_val">{$tr['GTOKEN']}</button>
            <button id="id_categroy_select" type="button" onclick="on_slidemenu('categroy_filter');" class="btn btn-outline-secondary dropdown-toggle" value="cate_all_val">{$tr['transaction type']}</button>
            <div id="export" class="ml-2"></div>
            <!-- <button id="export" type="button" class="btn btn-success ml-2 d-none" value="export">{$tr['export']}Excel</button> -->
          </div>
      </div>

      <div class="col table_content">
      <table class="table table-hover modal_click table_from4" id="betTable">
        <tbody id="transTableBody">
        </tbody>
      </table>
      </div>

      <div class="row" id="idtransloadmore">
        <div class="col">
          <button type="button" class="send_btn loadMore load-more" id="transLoadMore" value="bet">{$tr['load more']}</button>
        </div>
      </div>
  </div>

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
<div id="cash_filter" class="slide-up-menu slide-up-style">
  <table class="table">
      <thead class="thead-light">
        <tr><th class="bg-secondary">{$tr['please select category']}</th></tr>
        <tr><td class="cl_money" data-currency="gtoken_val">{$tr['GTOKEN']}</td></tr>
        <tr><td class="cl_money" data-currency="gcash_val">{$tr['GCASH']}</td></tr>
        <tr><td class="ca_tdcancel bg-secondary" onclick="off_slidemenu()">{$tr['cancel']}</td></tr>
      </thead>
  </table>
</div>
<div id="categroy_filter" class="slide-up-menu slide-up-style">
  <table class="table mb-0 table-fontsize-1rem">
      <thead class="thead-light">
        <tr><th class="bg-secondary">{$tr['please select category']}</th></tr>
        <tr><td class="cl_cate" data-cateval="cate_all_val">{$tr['all category']}</td></tr>
        {$tr_cate}
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

        // 取出 現金或遊戲幣
        var money_val=$('#id_cash_select').val();

        // 取出 分類類別
        var cate_val=$('#id_categroy_select').val();

        // 取出帳號
        var account=$('#id_q_account').val().trim();

        // 抓出目前的資料筆數
        var tr_offset = $('.transData').length;

        var data = {
          'sel_date':date_val,
          'sel_money_cate':money_val,
          'sel_cate':cate_val,
          'account':account,
          'tr_offset':tr_offset,
          'transpage':'{$transpage}'
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

    function combineLoadMoreDataHtml(data,tid) {
        var html = '';
        // var receiveMoneyStatus = '';
        // console . log('className:'+className);
        // console . log('data:'+data);
        // console . log(tid);

        var loadMoreDataHtml = data.reduce(function(accumulator, currentValue, currentIndex, array) {
        // console . log('accumulator.   :'+accumulator);
        // console . log('currentValue  :'+currentValue);
        // console . log('currentIndex  :'+currentIndex);
        // console . log('array   :'+array);

          if(tid !='' && tid ==currentValue.transaction_id ){
              addcls="table-success";
          }else{
              addcls='';
          }

          html =
          `
          <tr class="row transData `+addcls+`" id="`+currentValue.trans_id+`">
            <td class="col-8 pb-2">`+currentValue.summary+`</td>
            <td class="col-4">`+currentValue.transaction_amount+`</td>
            <td class="col-8">`+currentValue.transtime+`</td>
            <td class="col-4">$`+currentValue.balance+`</td>
          </tr>
          `;

          // console . log(accumulator + html);
          return accumulator + html;
        }, '');

      return loadMoreDataHtml;
    }

    function onoff_loadmore(onoff){
        if(onoff=='1'){
            $('#transLoadMore').text('{$tr['load more']}');
            $('#transLoadMore').prop('disabled', false);
        }else{
            $('#transLoadMore').text('{$tr['no more data']}');
            $('#transLoadMore').prop('disabled', true);
        }
    }


    function combinedetail(data){
      // console.log(data);
      /* <tr scope="row">
        <th scope="col-6">{$tr['Transfer number']}</th>
        <td scope="col">`+currentValue.transaction_id+`(EDT)</td>
      </tr> */
      // 帶存褶
      // <tr scope="row">
      //   <th scope="col-6">存簿名称</th>
      //   <td scope="col">`+currentValue.passbook+`存褶</td>
      // </tr>

      var html = '';
      var loadMoreDataHtml = data.reduce(function(accumulator, currentValue, currentIndex, array) {
        html = `
          <div class="modal fade" id="`+currentValue.trans_id+`_modal" tabindex="-1" role="dialog" aria-labelledby="_modalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
              <div class="modal-content modal_contentstyle">
                <div class="modal-header">
                  <h6 class="modal-title" id="_modalLabel">{$tr['membercenter_mo_transaction_log']}</h6>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                <div class="modal-body p-0">
                  <div class="table-responsive">
                    <table class="table table-striped mb-0 mo_translog_query_table">
                    <tbody>
                      <tr scope="row">
                        <th scope="col-6">{$tr['Transfer number']}</th>
                        <td scope="col">`+currentValue.transaction_id+`</td>
                      </tr>
                      <tr scope="row">
                        <th scope="col-6">{$tr['transcation time']}</th>
                        <td scope="col">`+currentValue.transtime+`(EDT)</td>
                      </tr>
                      <tr scope="row">
                        <th scope="col-6">
                          <button type="button" class="btn nav-headerbuttonpc" data-container="body" data-toggle="popover" data-placement="left" data-content="{$tr['real amount without fee']}">
                            <i class="fa fa-info-circle"></i>
                          </button>
                          <span>{$tr['Deposit amount']}</span>
                        </th>
                        <td scope="col">$`+currentValue.deposit+`</td>
                      </tr>
                      <tr scope="row">
                        <th scope="col-6">{$tr['withdrawal amount']}</th>
                        <td scope="col">$`+currentValue.withdrawal+`</td>
                      </tr>
                      <tr scope="row">
                        <th scope="col-6">{$tr['transaction_log_payout']}</th>
                        <td scope="col">`+currentValue.payout+`</td>
                      </tr>
                      <tr scope="row">
                        <th scope="col-6">
                          <button type="button" class="btn nav-headerbuttonpc" data-container="body" data-toggle="popover" data-placement="left" data-content="此余额不包含娱乐城内游戏币,会因娱乐城游戏币有所不同。">
                          <i class="fa fa-info-circle"></i>
                          </button>
                          <span>{$tr['current balance']}</span>
                        </th>
                        <td scope="col">$`+currentValue.balance+`</td>
                      </tr>
                      <tr scope="row">
                        <th scope="col-6">{$tr['transaction type']}</th>
                        <td scope="col">`+currentValue.transaction_category+`</td>
                      </tr>
                      <tr scope="row">
                        <th scope="col-6">{$tr['summary']}</th>
                        <td scope="col">`+currentValue.summary+`</td>
                      </tr>


                    </tbody>
                  </table>

                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-block btn-secondary" data-dismiss="modal">{$tr['close']}</button>
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
          url: 'mo_translog_query_action.php',
          data: {
            csrftoken: csrftoken,
            data:getdata,
            time:time,
            action: action
          },
          success: function(resp) {
            var res = JSON.parse(resp);

            // download xlsx
            $("#id_href").remove();
            var link= `<a id="id_href" class="btn btn-success disabled">{$tr['export']}<span>Excel</span></a>`;
            $("#export").append(link);

            //$tid
            var tid='{$tid}';

            if (res.status == 'success') {
              if ($('.transData').length == 0) {
                // console . log(combinedetail(res . result));
                onoff_loadmore('1');

                $('#transTableBody').append(combineLoadMoreDataHtml(res.result,tid));
                $('#idtransloadmore').after(combinedetail(res.result));

              } else {
                // console.log(combinedetail(res.result));
                $('.transData:last').after(combineLoadMoreDataHtml(res.result,tid));
                $('#idtransloadmore').after(combinedetail(res.result));

              }
              $('#transLoadMore').show();

              $("#id_href").remove();
              var link= `<a id="id_href" class="btn btn-success" href="`+res.download_url+`" target="_blank">{$tr['export']}<span>Excel</span></a>`;
              // var link= `<a id="id_href" href="`+res.download_url+`" target="_blank">{$tr['export']}Excel</a>`;
              $("#export").append(link);

            }else if(res.status == 'query_fail'){
                runEffect('nodata');
                $( "#nodata" ).hide();
                var msg=$('#id_date_select').text().trim();
                $('.data_empty').remove();
                $('#transTableBody').append('<tr class="data_empty no_data row"><td class="col no_data_style"><p>'+msg+',{$tr["no transaction log"]}</p></td></tr>');
                $('#transLoadMore').hide();
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
      $('#transLoadMore').hide();

      var show_query_html='{$transpage}';
      if(show_query_html=='a'){
          $('#morahtm_show').show();
      }else{
          $('#morahtm_show').hide();
      }

      $('#transLoadMore').hide();

      var csrftoken = '{$csrftoken}';
      var all_downline= JSON.parse('{$all_downline_member}');
      time=Math.floor($.now()/1000);

      if(downline_search(all_downline)){
          var getdata=getSearchRequirementValue();
          ajax_send(csrftoken,getdata,time,'query');
      }

      //filter 日期選擇
      $('.cl_date').click(function(e){
          var datetext=$(e.target).text();
          var seldatevalue=$(e.target).attr("data-dateval");
          $('#id_date_select').text(datetext);
          $('#id_date_select').val(seldatevalue);

          if(downline_search(all_downline)){
              $('.transData').remove();
              $('.data_empty').remove();

              time=Math.floor($.now()/1000);
              var getdata   = getSearchRequirementValue();
              ajax_send(csrftoken,getdata,time,'query');
          }
          off_slidemenu();
      });

      //filter 現金、遊戲幣選擇
      $('.cl_money').click(function(e){
          var moneytext=$(e.target).text();
          var moneyvalue=$(e.target).attr("data-currency");

          $('#id_cash_select').text(moneytext);
          $('#id_cash_select').val(moneyvalue);

          if(downline_search(all_downline)){
              $('.transData').remove();
              $('.data_empty').remove();

              time=Math.floor($.now()/1000);
              var getdata   = getSearchRequirementValue();
              ajax_send(csrftoken,getdata,time,'query');
          }
          off_slidemenu();
      });

      //filter 交易分類選擇
      $('.cl_cate').click(function(e){
          var catetext=$(e.target).text();
          var catevalue=$(e.target).attr("data-cateval");

          $('#id_categroy_select').text(catetext);
          $('#id_categroy_select').val(catevalue);

          if(downline_search(all_downline)){
              $('.transData').remove();
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
            $('.transData').remove();
            $('.data_empty').remove();

            time=Math.floor($.now()/1000);
            var getdata=getSearchRequirementValue();
            ajax_send(csrftoken, getdata, time,'query');
          }

      });

      // 加载更多按下
      $('#transLoadMore').click(function(e){
          if(downline_search(all_downline)){
            var getdata=getSearchRequirementValue();
            ajax_send(csrftoken, getdata, time,'loadmore');
          }
      });
    });

    // 注單按下時，顯示詳細資料
    $(document).on('click','.transData', function(){
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

$extend_js = <<<HTML
<script>
  $(function () {
    $('.modal_click').click(function(){
      $('[data-toggle="popover"]').popover();
      $('.popover-dismiss').popover({
      trigger: 'focus'
      });
    });
  });
</script>
HTML;


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
if($transpage == 'a'){
// 側邊欄MENU var[0]側邊欄組合(lib_menu) var[1]目前頁籤名
$tmpl['sidebar_content'] =['agent','mo_translog_query'];
// banner標題
$tmpl['banner'] = ['membercenter_menu_admin_agent'];
// menu增加active
$tmpl['menu_active'] =['agent_instruction.php'];
}else{
  // banner標題
  $tmpl['banner'] = ['membercenter_mo_transaction_log'];
  // menu增加active
  $tmpl['menu_active'] =['mo_translog_query.php?transpage=m&id='.$_SESSION["member"]->id];
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
