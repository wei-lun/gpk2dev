<?php use_layout($config['template_path'] . "template/admin.tmpl.php"); ?>

<?php begin_section('paneltitle_content'); ?>
<!-- 導覽列 -->
<?php if ($config['site_style']=='mobile') : ?>
        <a href="{$config['website_baseurl']}menu_admin.php?gid=agent"><i class="fas fa-chevron-left"></i></a>
		<span><?php echo $function_title; ?></span>
		<i></i>
<?php else : ?>
<ul class="breadcrumb">
    <li>
        <a href="home.php">
            <span class="glyphicon glyphicon-home"></span>
        </a>
    </li>
    <li>
        <a href="member.php"><?php echo $tr['Member Centre']; ?></a>
    </li>
    <li>
        <a href="agencyarea.php"><?php echo $tr['agencyarea title']; ?></a>
    </li>
    <li class="active">
        <?php echo $function_title; ?>
    </li>
</ul>
<?php endif; ?>

<?php end_section(); ?>
<!-- end 導覽列 -->


<!-- begin of panelbody_content -->
<?php begin_section('panelbody_content'); ?>
<?php if (!$has_permission) : ?>
    <div class="row">
        <div class="col-12">
            <?php echo get_permission_message(); ?>
        </div>
    </div>
    <div class="row">
        <div id="preview_result"></div>
    </div>
<?php else : ?>
    <!-- start -->
    <div id="return_table" class="return_table_table">
    <?php if ($config['site_style']=='mobile') : ?>
        <div class="row account">
            <div class="col-6">
                <p><?php echo $tr['agent rakeback']; ?></p>
                <p class="agent" id="total_level_distribute"></p>
            </div>
            <div class="col-6">
                <p><?php echo $tr['self rakeback']; ?></p>
                <p class="self" id="total_self_favorable"></p>
            </div>
            <div class="col-12">
                <button type="button" class="btn btn_info" data-container="body" data-toggle="popover"  data-placement="top" data-content="<?php echo $tr['total rebate is sum of agent rebate and self rebate'];?>">
                    <i class="fa fa-info-circle mr-1" aria-hidden="true"></i><?php echo $tr['total rakeback']; ?>
                </button>
                <p class="total" id="total_favorable"></p>
            </div>
        </div>

        <!-- search form -->
        <div id="morahtm_show" class="filter_list_button merge_filter">
            <div class="row justify-content-between align-items-center">
                <div class="return_search col-8">
                    <select id="search_account" class="selectpicker" data-live-search="true" style="height:50px;" title="<?php echo $tr['choose member']; ?>">
                        <option value=""><?php echo $tr['Clear condition']; ?></option>
                        <?php
                            if( isset($recursive_result) && (count($recursive_result)>0) ){
                                foreach( $recursive_result as $key=>$val ){
                                    echo '<option value="'.$val->account.'">'.$val->account.'</option>';
                                } // end foreach
                            }
                            else{
                                echo '<option disabled selected>无资料</option>';
                            }
                        ?>
                    </select>
                </div>
                <div class="return_ser_button col-4">
                    <button id="search_send" type="button" class="btn btn-outline-secondary btn-block"><?php echo $tr['search']; ?></button>
                </div>
            </div>
        </div>
        <!-- end of search form -->
        <!-- filter_list_button -->
        <div class="filter_list_button filter_ser">
            <div>
                <button id="id_date_select" type="button" onclick="on_slidemenu('date_select');" class="btn btn-outline-secondary dropdown-toggle" value="<?php echo isset($_SESSION['search_date']) ? $_SESSION['search_date'] : 'today'; ?>">
                    <?php echo isset($_SESSION['search_date']) ? $tr["{$_SESSION['search_date']}"] : $tr['today']; ?>
                </button>
            </div>
            <div id="export" class="ml-2">
                <a id="id_href" class="btn btn-success" href="agencyarea_summary_member_datas.php?output=true" target="_blank"><?php echo $tr['export']; ?><span>Excel</span></a>
            </div>
        </div>
        <div class="block-layout motransaction_layout"></div>
        <div id="date_select" class="slide-up-menu slide-up-style">
        <table id="date_range" class="table">
            <thead class="thead-light">
                <tr><th class="bg-secondary"><?php echo $tr['please select date']; ?></th></tr>
                <tr><td class="cl_date" data-dateval="today"><?php echo $tr['today']; ?></td></tr>
                <tr><td class="cl_date" data-dateval="yesterday"><?php echo $tr['yesterday']; ?></td></tr>
                <tr><td class="cl_date" data-dateval="week"><?php echo $tr['week']; ?></td></tr>
                <tr><td class="cl_date" data-dateval="month"><?php echo $tr['month']; ?></td></tr>
                <tr><td class="cl_date" data-dateval="lastmonth"><?php echo $tr['lastmonth']; ?></td></tr>
                <tr><td class="ca_tdcancel bg-secondary" onclick="off_slidemenu()"><?php echo $tr['cancel']; ?></td></tr>
            </thead>
        </table>
        </div>
        <!-- end of filter_list_button -->

    <?php else : ?>
        <!-- search form -->
        <div id="morahtm_show" class="filter_list_button merge_filter">
            <div class="d-flex justify-content-between align-items-center">
                <div class="return_search">
                    <select id="search_account" class="selectpicker" data-live-search="true" style="height:50px;" title="<?php echo $tr['choose member']; ?>">
                        <option value=""><?php echo $tr['Clear condition']; ?></option>
                        <?php
                            if( isset($recursive_result) && (count($recursive_result)>0) ){
                                foreach( $recursive_result as $key=>$val ){
                                    if( isset($_SESSION['search_account']) && ($_SESSION['search_account']==$val->account) ){
                                        echo '<option value="'.$val->account.'" selected>'.$val->account.'</option>';
                                    }
                                    else{
                                        echo '<option value="'.$val->account.'">'.$val->account.'</option>';
                                    }
                                } // end foreach
                            }
                            else{
                                echo '<option disabled selected>无资料</option>';
                            }
                        ?>
                    </select>
                </div>
                <div class="return_ser_button">
                    <button id="search_send" type="button" class="btn btn-outline-secondary btn-block"><?php echo $tr['search']; ?></button>
                </div>
            </div>
        </div>
        <!-- end of search form -->
        <!-- filter_list_button -->
        <div class="filter_list_button filter_ser">
            <div>
                <button id="id_date_select" type="button" onclick="on_slidemenu('date_select');" class="btn btn-outline-secondary dropdown-toggle" value="<?php echo isset($_SESSION['search_date']) ? $_SESSION['search_date'] : 'today'; ?>">
                    <?php echo isset($_SESSION['search_date']) ? $tr["{$_SESSION['search_date']}"] : $tr['today']; ?>
                </button>
            </div>
            <div id="export" class="ml-2">
                <a id="id_href" class="btn btn-success" href="agencyarea_summary_member_datas.php?output=true" target="_blank"><?php echo $tr['export']; ?><span>Excel</span></a>
            </div>
        </div>
        <div class="block-layout motransaction_layout"></div>
        <div id="date_select" class="slide-up-menu slide-up-style">
        <table id="date_range" class="table">
            <thead class="thead-light">
                <tr><th class="bg-secondary"><?php echo $tr['please select date']; ?></th></tr>
                <tr><td class="cl_date" data-dateval="today"><?php echo $tr['today']; ?></td></tr>
                <tr><td class="cl_date" data-dateval="yesterday"><?php echo $tr['yesterday']; ?></td></tr>
                <tr><td class="cl_date" data-dateval="week"><?php echo $tr['week']; ?></td></tr>
                <tr><td class="cl_date" data-dateval="month"><?php echo $tr['month']; ?></td></tr>
                <tr><td class="cl_date" data-dateval="lastmonth"><?php echo $tr['lastmonth']; ?></td></tr>
                <tr><td class="ca_tdcancel bg-secondary" onclick="off_slidemenu()"><?php echo $tr['cancel']; ?></td></tr>
            </thead>
        </table>
        </div>
        <!-- end of filter_list_button -->

        <div class="row account">
            <div class="col-4">
                <p><?php echo $tr['agent rakeback']; ?></p>
                <p class="agent" id="total_level_distribute"></p>
            </div>
            <div class="col-4">
                <p><?php echo $tr['self rakeback']; ?></p>
                <p class="self" id="total_self_favorable"></p>
            </div>
            <div class="col-4">
                <button type="button" class="btn btn_info" data-container="body" data-toggle="popover"  data-placement="top" data-content="<?php echo $tr['total rebate is sum of agent rebate and self rebate'];?>">
                    <i class="fa fa-info-circle mr-1" aria-hidden="true"></i><?php echo $tr['total rakeback']; ?>
                </button>
                <p class="total" id="total_favorable"></p>
            </div>
        </div>
    <?php endif; ?>
        <?php if ($config['site_style']=='mobile') : ?>
        <div class="return_table_data">
        <?php else : ?>
        <div class="col-12 return_table_data">
        <?php endif; ?>
            <table id="member_list" class="table_liststyle table-striped dt-responsive nowrap" cellspacing="0" width="100%">
				<thead>
                    <tr class="row">
                        <th scope="col-3" class="col-lg-2 col-4"><?php echo $tr['time']; ?></th>
                        <th scope="col-2" class="col-lg-2 col-4"><?php echo $tr['source account']; ?></th>
                        <th scope="col-3" class="col-2 desktop none"><?php echo $tr['rakeback base']; ?></th>
                        <th scope="col-4" class="col-2 desktop none"><?php echo $tr['rebate propotion']; ?></th>
                        <th scope="col-4" class="col-lg-2 col-4"><?php echo $tr['rebate amount']; ?></th>
                        <th scope="col-4" class="col-2 desktop none"><?php echo $tr['Update time and description']; ?></th>
                    </tr>
                </thead>
			</table>
        </div>
    </div>
    <!-- end -->
<?php endif; ?>

<?php end_section(); ?>
<!-- end of panelbody_content -->

<!-- begin of extend_js -->
<?php begin_section('extend_js'); ?>
<script>
$(document).ready(function(){
        $('[data-toggle="popover"]').popover();

        $('.popover-dismiss').popover({
            trigger: 'focus'
        }); // end popover

        // 初始化
		// var parameter_value = '$member_account_parameter';
		$("#member_list").dataTable({
			"bLengthChange": true,
			"bProcessing":   true,
			"bServerSide":   true,
			"bRetrieve":     true,
			"searching":     true,
			"bFilter":       false,
			"pageLength":    10,
            "info": true,
            'paging': true,
            "lengthMenu": [[10, 15, 25, 50], [10, 15, 25, 50]],
			"aaSorting":     [[0,"desc"]],
            "dom": "rt"+"<'row justify-content-between align-items-center table_info'<'col-3'i><'col-lg-6 col-12 d-flex justify-content-center'p><'col-3 d-none d-lg-flex justify-content-end'l>>",
			"oLanguage": {
                "sSearch": "<?php echo $tr['member account']; ?>:",//会员帐号
                "sEmptyTable": "<?php echo $tr['no data']; ?>",//目前没有资料
                "sLengthMenu": "<?php echo $tr['each page']; ?> _MENU_ <?php echo $tr['item']; ?>",//每页显示笔
                "sZeroRecords": "<?php echo $tr['no data']; ?>",//目前没有资料
                "sInfo": "<?php echo $tr['now at']; ?> _PAGE_ <?php echo $tr['page']; ?>，<?php echo $tr['total']; ?> _PAGES_ <?php echo $tr['page']; ?>",//目前在第页共页
                "sInfoEmpty": "<?php echo "" ?>",//目前没有资料
                "sInfoFiltered": "<br>(<?php echo $tr['from']; ?> _MAX_ <?php echo $tr['filtering in data']; ?>)",//从笔资料中过滤
                "oPaginate": {
                    "sPrevious": "<?php echo '<' ?>",//上一页
                    "sNext": "<?php echo '>' ?>"//下一页
                }
            },
			"ajax": {
                "url":  "agencyarea_summary_member_datas.php",
                "type": "POST",
                "data": {},
            },
            "columns":[
                {"data": "dailydate"},
                {"data": "account"},
                {"data": "base_favorable"},
                {"data": "from_favorable_rate"},
                {"data": "from_favorable"},
                {"data": "updatetime"}
            ],
            "columnDefs": [
                {"targets": [0], "className": "col-lg-2 col-4"},
                {"targets": [1], "className": "col-lg-2 col-4"},
                {"targets": [2], "className": "col-lg-2 col-4"},
                {"targets": [3], "className": "col-lg-2 col-4"},
                {"targets": [4], "className": "col-lg-2 col-4"},
                {"targets": [5], "className": "col-lg-2 col-4"}
            ],
			"rowCallback": function(row,data){
				$('td', row).removeClass('alert-success');
                $(row).addClass("row");
			},
            "drawCallback":function(settings){               
                if( settings.json.total_favorable == '$0' ){
									$('#total_favorable').text('$0.00');
								}else{
									$('#total_favorable').text( settings.json.total_favorable );
								}
								if( settings.json.total_level_distribute == '$0' ){
									$('#total_level_distribute').text('$0.00');
								}else{
									$('#total_level_distribute').text( settings.json.total_level_distribute );
								}
								if( settings.json.total_self_favorable == '$0' ){
									$('#total_self_favorable').text('$0.00');
								}else{
									$('#total_self_favorable').text( settings.json.total_self_favorable );
								}               								
                if ($(this).find('tbody tr').length<=1) {
                    $(this).parent().find('.table_info').hide();
                }else{
                    $(this).parent().find('.table_info').show();
                }
            }
		}); // end DataTable

        // 清除条件
        $('select').change(function(){
            if( $(this).val()=='' ){
                $('#search_send').click();
            }
        }); // end change

            // 日期筛选 (点选后立即搜寻)
        $('#date_range > thead > tr:not(:first):not(:last)').click(function(){
            $('#id_date_select').val( $(this).children('td').data('dateval') ).text( $(this).children('td').text() );
            off_slidemenu();
            $('#search_send').click();
        }); // end click

        // 變更名字搜尋
        $('body').on('click', 'ul.dropdown-menu.inner.show > li', function(){
            $('#search_send').click();
        }); // end on

        // 执行搜寻
        $('#search_send').click(function(){
            result = {};
            result.account = $('#search_account').val();
            result.date = $('#id_date_select').val();
            $("#member_list").DataTable().search( JSON.stringify(result) ).draw('page');
        }); // end click

}); // END FUNCTION

function off_slidemenu(){
  $('.slide-up-menu').removeClass('slide-up');
  $('.block-layout').fadeOut();
}

function on_slidemenu(toggle){
  $('#'+toggle).addClass('slide-up');
  $('.block-layout').fadeIn();
}

</script>
<?php end_section(); ?>
<!-- end of extend_js -->