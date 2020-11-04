<?php use_layout( $config['template_path']."template/admin.tmpl.php" ) ?>
<?php begin_section('extend_head') ?>

    <style type="text/css">
        .show_query_option {
            padding-top: 3px;
            padding-bottom: 3px;
        }

        .show_pageinfo {
            padding-top: 3px;
            padding-bottom: 3px;
        }

        .panel-heading *[data-toggle="collapse"]:after {
            /* symbol for "opening" panels */
            font-family: 'Glyphicons Halflings';  /* essential for enabling glyphicon */
            content: "\e114";    /* adjust as needed, taken from bootstrap.css */
            float: right;        /* adjust as needed */
            color: grey;
        }

        .panel-heading *[data-toggle="collapse"].collapsed:after {
            /* symbol for "collapsed" panels */
            content: "\e080";    /* adjust as needed, taken from bootstrap.css */
        }

        /* 威鵬新增 */
        .newstyle_table td, .table th{
            border-top: none;
            color: white !important;
            background: rgba(60, 60, 60, 0.5);
            vertical-align: middle !important;
            text-align: center !important;
            border: 3px rgba(33, 37, 41, 0.8) solid;
        }

        .newstyle_table thead tr:nth-child(1) th{
        height: 30px;
        }

        .newstyle_table thead tr:nth-child(2) th{
        height: 20px;
        }

        .newstyle_table td{
        height: 30px;
        }

        .newstyle_table td > a{
        padding: 4px 6px 4px 6px !important;
        }

        /* 比例警示燈號的大小 */
        td.text-center > i{
        font-size: 0.9rem;
        }
    </style>


    <script type="text/javascript" src="in/rangeslider/_agencyarea.js"></script>

    <link rel="stylesheet" type="text/css" href="in/rangeslider/rangeslider.css"/>
    <link rel="stylesheet" type="text/css" href="in/rangeslider/_agencyarea.css"/>


    <link rel="stylesheet" type="text/css" href="<?php echo $cdnfullurl_js ?>datetimepicker/jquery.datetimepicker.css" rel="stylesheet"/>
    <script type="text/javascript" language="javascript" src="<?php echo $cdnfullurl_js ?>datetimepicker/jquery.datetimepicker.full.min.js"></script>

    <link rel="stylesheet" type="text/css" href="<?php echo $cdnfullurl_js ?>datatables/css/jquery.dataTables.min.css">
    <script type="text/javascript" language="javascript" src="<?php echo $cdnfullurl_js ?>datatables/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" language="javascript" src="<?php echo $cdnfullurl_js ?>datatables/js/dataTables.bootstrap.min.js"></script>

    <script>
        window.userinfo = <?php echo ($loggin_userinfo ?? 'false') ?>;
        // 保留当前值
        $(function(){

            function goto_1st_agent_setting(){
                if($('#collapse1').attr('aria-expanded') != 'true') $('.panel-heading *[data-toggle="collapse"]:eq(0)').click();
                if($('#collapse2').attr('aria-expanded') != 'true') $('.panel-heading *[data-toggle="collapse"]:eq(1)').click();
                location.href = '#__V2';
            }//end goto_1st_agent_setting

            $("#transaction_list").DataTable({
                // "dom":"<fltip>",
                "serverSide": true,
                "ordering": true,
                "order": [[ 2, "asc" ]],
                "info": true,
                "pageLength": 10,
                "ajax": {
                    "url": "agencyarea_member_datas.php",
                    "type": "POST",
                    "data":{'query_account':<?php echo "'".$query_account."'";?>},
                    "dataSrc": function(json){
                        console.log(json);
                        return json.data;
                    }},
                    "language": {
                        "decimal":        "",
                        "emptyTable":     "<?php echo $tr['no relative information'] ?>",
                        "info":           "<?php echo $tr['show'] ?> _START_ <?php echo $tr['to'] ?> _END_ <?php echo $tr['data'] ?> (<?php echo $tr['total'] ?> _TOTAL_ <?php echo $tr['data'] ?>)",
                        "infoEmpty":      "<?php echo $tr['no data'] ?>",
                        "infoFiltered":   "(<?php echo $tr['from'] ?> _MAX_ <?php echo $tr['filtering in data'] ?>)",
                        "infoPostFix":    "",
                        "thousands":      ",",
                        "lengthMenu":     "<?php echo $tr['each page'] ?> _MENU_ <?php echo $tr['item'] ?>",
                        "loadingRecords": "<?php echo $tr['now loading'] ?>...",
                        "processing":     "<?php echo $tr['searching'] ?>...",
                        "search":         '<div style="height:calc(1.7em); width:150px; font-size:1.15rem; padding-top:5px;"><?php echo $tr['search'] ?>：</div>',
                        "zeroRecords":    "<?php echo $tr['no relative information'] ?>",
                        "paginate": {
                            "first":      "<?php echo $tr['first'] ?>",
                            "last":       "<?php echo $tr['last'] ?>",
                            "next":       "<?php echo $tr['next'] ?>",
                            "previous":   "<?php echo $tr['previous'] ?>"
                        }
                    },
                    "columns": [
                        {"data": "account"},
                        {"data": "therole"},
                        {"data": "status"},
                        {"data": "p_allocable_user", "orderable": false}, // 這邊每列資料數值都一樣，故不需要排序
                        {"data": "d_allocable_user", "orderable": false}, // 這邊每列資料數值都一樣，故不需要排序
                        {"data": "p_allocable_user_setting"},
                        {"data": "p_allocable_user_children"},
                        {"data": "d_allocable_user_setting"},
                        {"data": "d_allocable_user_children"}
                    ],
                    "initComplete": function(){
                        $("#transaction_list > tbody > tr").each(function(){
                            $(this).children("td").eq(0).addClass("text-left");
                            $(this).children("td").eq(1).addClass("text-center");
                            $(this).children("td").eq(2).addClass("text-center");
                            $(this).children("td").eq(3).addClass("text-center");
                            $(this).children("td").eq(4).addClass("text-center");
                            $(this).children("td").eq(5).addClass("text-center");
                            $(this).children("td").eq(6).addClass("text-center");
                            $(this).children("td").eq(7).addClass("text-center");
                            $(this).children("td").eq(8).addClass("text-center");
                        });
                    }
            });

            $("input[type=search]").val(<?php echo "'".$query_account."'";?>);

            $('.preferential_dispatch').on('focusin', function() {
                var _ = $(this);
                $(this).attr('data-current', _.val());
            });

            $('.dividend_dispatch').on('focusin', function() {
                var _ = $(this);
                $(this).attr('data-current', _.val());
            });
        }); // END FUNCTION

        // 修改反水 ajax
        function select_preferentialratio_chang(uid, event){
            var e = event.target;
            var change_value_div = e.options[e.selectedIndex].value;
            var preferentialratio = change_value_div;
            var uaccount = e.dataset.account;
            var confirm_mesg = "确定更新帐号 "+uaccount+" 的反水占成比例为 "+preferentialratio+" %？(送出后不可还原为未设定)";

            //
            var req_setting = {
                "url": "agencyarea_action.php",
                "method": "PUT",
                "headers": {
                "content-type": "application/x-www-form-urlencoded",
                "cache-control": "no-cache",
                },
                "data": {
                "action": "update_preferential",
                "u_id": uid,
                "value": change_value_div
                }
            };

            var pass = true;

            if(pass && confirm(confirm_mesg)) {
                var oldValue = e.dataset.current;
                $.ajax(req_setting).done(function(response) {
                // console.log(response);
                alert(response.message.description);
                // $("#preview_result").html(response.message.description);
                if (change_value_div == '') $('[data-bind="p_agent_'+uid+'"]').html('0 %');
                else $('[data-bind="p_agent_'+uid+'"]').html(window.userinfo.feedbackinfo.preferential.allocable * 100 - change_value_div + ' %');
                }).fail(function(error) {
                // console.log(error.responseJSON);
                e.value = oldValue;
                // alert(error.responseJSON.message.description);
                alert(111);
                });
            }
            else{
                e.value = e.dataset.current
                // console.log("取消更新反水比例")
                return false;
            }
            }// end select_preferentialratio_chang

            // 修改反水 ajax (2019/06/19 modified by damocles)
            function _select_preferentialratio_chang(uid, uaccount, change_value_div, p_allocable_user){
                var confirm_mesg = "确定更新帐号 "+uaccount+" 的反水占成比例为 "+change_value_div+" %？(送出后不可还原为未设定)";
                var req_setting = {
                    "url": "agencyarea_action.php",
                    "method": "PUT",
                    "headers": {
                    "content-type": "application/x-www-form-urlencoded",
                    "cache-control": "no-cache",
                    },
                    "data": {
                    "action": "update_preferential",
                    "u_id": uid,
                    "value": change_value_div
                    }
                };

                if(confirm(confirm_mesg)){
                    $.ajax(req_setting)
                    .done(function(response){
                    alert(response.message.description);
                    // $("#preview_result").html(response.message.description);

                    // 調整比例成功後，修改<a>的顯示比例，並且移除掉rangeslider.
                    $('div.fake_range_setting').prev('a.fake_range_setting').data('allocable', change_value_div).text(change_value_div + ' %').show();// 更新設訂的反水比例並顯示.
                    $('div.fake_range_setting').parent().next('td.text-center').children('span').text( (p_allocable_user-change_value_div) + ' %' ); // 非直屬設定的反水比例更新(直屬保障-反水比例設定).

                    min = parseInt( $('div.fake_range_setting').children('div.range').children('input[type=range]').attr('min') );
                    max = parseInt( $('div.fake_range_setting').children('div.range').children('input[type=range]').attr('max') );

                    adjust_range_third = parseInt( (max-min)/3 );
                    adjust_range_one_third = min + adjust_range_third;
                    adjust_range_two_third = min + (adjust_range_third * 2);

                    // 警示燈號
                    if( change_value_div >= adjust_range_two_third ){
                        color = '#00ff00';
                    }
                    else if( (adjust_range_one_third <= change_value_div) && (change_value_div < adjust_range_two_third) ){
                        color = '#FFBB00';
                    }
                    else{
                        color = '#CC0000';
                    }

                    $('div.fake_range_setting').parent().children('i').show().css('color', color);
                    $('div.fake_range_setting').remove();
                    })
                    .fail(function(error){ // 無法修改的情況下，移除掉該欄位的range，並且顯示 "-".
                    // console.log(error.responseJSON);
                    $('div.fake_range_setting').parent().html('-');
                    alert(error.responseJSON.message.description);
                    });
                }
                else{
                    // console.log("取消更新反水比例")
                    return false;
                }
            }// end _select_preferentialratio_chang

            // 修改占成 ajax (2019/06/19 modified by damocles)
            function _select_dividendratio_chang(uid, uaccount, change_value_div, p_allocable_user){
            // var confirm_mesg = "确定更新帐号 "+uaccount+" 的佣金占成比例为 "+dividendratio+" %？(送出后不可还原为未设定)";
            var confirm_mesg = "确定更新帐号 "+uaccount+" 的佣金占成比例为 "+change_value_div+" %？(送出后不可还原为未设定)";

            ////
            var req_setting = {
            "url": "agencyarea_action.php",
            "method": "PUT",
            "headers": {
                "content-type": "application/x-www-form-urlencoded",
                "cache-control": "no-cache",
            },
            "data": {
                "action": "update_dividend",
                "u_id": uid,
                "value": change_value_div
            }
            };

            if( confirm(confirm_mesg) ) {
                $.ajax(req_setting)
                .done(function(response) {
                alert(response.message.description);

                // 調整比例成功後，修改<a>的顯示比例，並且移除掉rangeslider.
                $('div.fake_range_setting').prev('a.fake_range_setting').data('allocable', change_value_div).text(change_value_div + ' %').show();// 更新設訂的反水比例並顯示.
                $('div.fake_range_setting').parent().next('td.text-center').children('span').text( (p_allocable_user-change_value_div) + ' %' ); // 非直屬設定的反水比例更新(直屬保障-反水比例設定).

                min = parseInt( $('div.fake_range_setting').children('div.range').children('input[type=range]').attr('min') );
                max = parseInt( $('div.fake_range_setting').children('div.range').children('input[type=range]').attr('max') );

                adjust_range_third = parseInt( (max-min)/3 );
                adjust_range_one_third = min + adjust_range_third;
                adjust_range_two_third = min + (adjust_range_third * 2);

                // 警示燈號
                if( change_value_div >= adjust_range_two_third ){
                    color = '#00ff00';
                }
                else if( (adjust_range_one_third <= change_value_div) && (change_value_div < adjust_range_two_third) ){
                    color = '#FFBB00';
                }
                else{
                    color = '#CC0000';
                }

                $('div.fake_range_setting').parent().children('i').show().css('color', color);
                $('div.fake_range_setting').remove();
                })
                .fail(function(error) {
                $('div.fake_range_setting').parent().html('-');
                alert(error.responseJSON.message.description);
                });
            }
            else{
                e.value = e.dataset.current
                // console.log("取消更新损益比例")
                return false;
            }
        } // end _select_dividendratio_chang

        // 修改占成 ajax
        function select_dividendratio_chang(uid, event){
            var e = event.target;
            var change_value_div = e.options[e.selectedIndex].value;
            var dividendratio = change_value_div;
            // var csrftoken = '$csrftoken';
            var uaccount = e.dataset.account;
            var confirm_mesg = "确定更新帐号 "+uaccount+" 的佣金占成比例为 "+dividendratio+" %？(送出后不可还原为未设定)";

            ////
            var req_setting = {
            "url": "agencyarea_action.php",
            "method": "PUT",
            "headers": {
                "content-type": "application/x-www-form-urlencoded",
                "cache-control": "no-cache",
            },
            "data": {
                "action": "update_dividend",
                // "u_id": "1038",
                // "value": "30"
                "u_id": uid,
                "value": change_value_div
            }
            };

            // var pass = is_valid('dividend', {"className": "dividend_dispatch"});
            var pass = true;

            if(pass && confirm(confirm_mesg)) {
                var oldValue = e.dataset.current;
                $.ajax(req_setting).done(function(response) {
                // console.log(response);
                alert(response.message.description);
                // $("#preview_result").html(response.message.description);
                if (change_value_div == '') $('[data-bind="p_agent_'+uid+'"]').html('0 %');
                else $('[data-bind="d_agent_'+uid+'"]').html(window.userinfo.feedbackinfo.dividend.allocable * 100 - change_value_div + ' %');
                }).fail(function(error) {
                // console.log(error.responseJSON);
                e.value = oldValue;
                alert(error.responseJSON.message.description);
                });
            }
            else{
                e.value = e.dataset.current
                // console.log("取消更新损益比例")
                return false;
            }
        }

        // 验证分派总和，以及自身剩下(实拿)
        // input 为要验证的动作, 必要变数
        function is_valid(type, params) {
            switch (type) {
            case 'preferential':
                var _obj = $('.' + params.className);

                var child_values = [];
                for (let index = 0; index < _obj.length; index++) {
                child_values.push(parseInt(_obj[index].value));
                }
                // console.log(child_values);
                var sum = child_values.reduce((a, b) => a + b, 0);
                var allocable = Math.floor(window.userinfo.feedbackinfo.preferential.allocable * 100);
                // console.log(sum);
                if(sum > allocable) {
                alert('分配给下线的反水总和('+ sum +'%)不得超过自身获得反水比例('+allocable+'%)!');
                return false
                }
                window.userinfo.feedbackinfo.preferential.occupied = Math.min(
                window.userinfo.feedbackinfo.preferential.occupied,
                window.userinfo.feedbackinfo.preferential.allocable - (sum / 100)
                );

                return true;
                break;

            case 'dividend':
                var _obj = $('.' + params.className);

                var child_values = [];
                for (let index = 0; index < _obj.length; index++) {
                child_values.push(parseInt(_obj[index].value));
                }
                // console.log(child_values);
                var sum = child_values.reduce((a, b) => a + b, 0);
                var allocable = Math.floor(window.userinfo.feedbackinfo.dividend.allocable * 100);
                // console.log(sum);
                if(sum > allocable) {
                alert('分配给下线的佣金总和('+ sum +'%)不得超过自身获得佣金比例('+allocable+'%)!');
                return false
                }
                window.userinfo.feedbackinfo.dividend.occupied = Math.min(
                window.userinfo.feedbackinfo.dividend.occupied,
                window.userinfo.feedbackinfo.dividend.allocable - (sum / 100)
                );

                return true;
                break;

            default:
                return false
                break;
            }
        }
    </script>

    <script>
        $(function() {
        // 一级代理商的反水设定
        $('._1st_agent_setting')
        .one('focusin', function() {
        var _ = $(this);
        // console.log(_.val());
        $(this).attr('data-current', _.val());
        })
        .on('change', function(e) {
        // console.log(e);
        // console.log(e.delegateTarget.value);
        // console.log(e.currentTarget.value);
        // console.log(e.target.value);
        // console.log(e.target.dataset);

        var ajax_setting = {
            "url": "agencyarea_action.php",
            "method": "PUT",
            "headers": {
            "content-type": "application/x-www-form-urlencoded",
            "cache-control": "no-cache",
            },
            "data": {
            "action": 'update_1st_agent_setting',
            "type_of_setting": e.target.dataset.type,
            "attr": e.target.dataset.action,
            "value": e.target.value
            }
        };

        var confirm_msg = '[一级代理商设定] 确定修改'+e.target.dataset.description+'为 '+e.target[e.target.selectedIndex].text+' 吗？';
        var pass = <?php echo ($is_1st_agent ?? 'false') ?>;
        if(!pass){
            e.target.value = e.target.dataset.current
            alert('非法测试!');
            return false;
        }

        if(pass && confirm(confirm_msg)) {
            var oldValue = e.target.dataset.current;
            $.ajax(ajax_setting).done(function(response) {
            // console.log(response);
            window.userinfo.feedbackinfo = response.message.params.self_feedbackinfo;
            $('.page_allocable_preferential').html(Math.floor(window.userinfo.feedbackinfo.preferential.allocable * 100) + '%');
            $('.page_allocable_dividend').html(Math.floor(window.userinfo.feedbackinfo.dividend.allocable * 100) + '%');
            alert(response.message.description);
            // $("#preview_result").html(response.message.description);
            location.reload();
            }).fail(function(error) {
            // console.log(error.responseJSON);
            e.target.value = oldValue;
            alert(error.responseJSON.message.description);
            });
        }else{
            e.target.value = e.target.dataset.current;
            console.log("取消更新一级代理商设定值")
            return false;
        }
        });
        });
    </script>
<?php end_section() ?>

<?php begin_section('paneltitle_content')  ?>
<ul class="breadcrumb">
  <li>
  <a href="home.php"><span class="glyphicon glyphicon-home"></span></a>
  </li>
  <li>
  <a href="member.php"><?php echo $tr['Member Centre'] ?></a>
  </li>
  <li>
  <a href="agencyarea.php"><?php echo $tr['agencyarea title'] ?></a>
  </li>
  <li class="active"><?php echo $function_title ?></li>
</ul>
<?php end_section() ?>

<?php begin_section('panelbody_content') ?>

<?php if (! $has_permission): ?>
  <div class="row">
  <div class="col-12">
    <?php echo hint_html(); ?>
  </div>
  </div>
  <div class="row">
  <div id="preview_result"></div>
  </div>
<?php else: ?>
  <!-- 我的组织 start -->
  <div class="row">
    <?php echo $agentadmin_html ?>
  </div>
  <hr>
  <div class="row">
    <div id="preview_result"></div>
  </div>
  <br>
  <!-- 我的组织 end -->

  <script>
  $(function() {
    $('[data-toggle="tooltip"]').tooltip({});
    $('[data-toggle="popover"]').popover({});
  });
  </script>
<?php endif; ?>
<?php end_section() ?>
