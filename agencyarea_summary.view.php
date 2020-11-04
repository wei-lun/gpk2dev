<?php use_layout( $config['template_path']."template/admin.tmpl.php" ); ?>

<!-- 導覽列 -->
<?php begin_section('paneltitle_content'); ?>
<!-- <script src="in/jquery/jquery.min.js"></script> -->
<!-- <script src="in/popper/1.12.9/popper.min.js"></script>
<script src="in/bootstrap/js/bootstrap.min.js"></script> -->
<!-- <link rel="stylesheet" href="in/bootstrap/css/bootstrap.min.css"> -->

<!-- DataTable -->
<!-- <link rel="stylesheet" type="text/css" href="in/datatables/css/jquery.dataTables.min.css">
<script type="text/javascript" language="javascript" src="in/datatables/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" language="javascript" src="in/datatables/js/dataTables.bootstrap.min.js"></script> -->

<!-- bootstrap-select -->
<!-- <script src="in/bootstrap-select/1.13.1/bootstrap-select.min.js"></script>
<link rel="stylesheet" href="in/bootstrap-select/1.13.1/bootstrap-select.min.css"> -->

<!-- DateRangePicker -->
<link rel="stylesheet" type="text/css" href="in/bootstrap-daterangepicker/2.1.25/daterangepicker.min.css">
<script src="in/bootstrap-daterangepicker/2.1.25/moment.min.js"></script>
<script src="in/bootstrap-daterangepicker/2.1.25/daterangepicker.min.js"></script>
<script src="in/bootstrap-daterangepicker/2.1.25/agencyarea_summary.js"></script>

<style>
    /* DateRangePicker 這個月日期的文字顏色顯示(會跟既有的CSS衝突，故在這邊新增) */
    table.table-condensed > tbody > tr > td:not(.off){
      color: #212529;
    }
    #DateRangePicker{
      min-width: 195px;
    }
</style>
<!-- 導覽列 -->
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
<?php end_section(); ?>
<!-- end 導覽列 -->


<!-- begin of panelbody_content -->
<?php begin_section('panelbody_content'); ?>
<?php if (!$has_permission): ?>
    <div class="row">
        <div class="col-12">
            <?php echo get_permission_message(); ?>
        </div>
    </div>
    <div class="row">
        <div id="preview_result"></div>
    </div>
<?php else: ?>
<!-- search form -->
<div class="row">
    <div class="col-12">
        <form class="form-inline" method="get">

            <span>用户名称：</span>
            <input type="text" class="form-control" name="query_member_account" id="query_member_account" value="<?php echo ( (isset($member_account)) ? $member_account : '' );?>" placeholder="请输入要搜寻的帐户">
            <!-- <select id="______account" class="selectpicker" data-live-search="true">
                <option data-tokens="ketchup mustard">Hot Dog, Fries and a Soda</option>
                <option data-tokens="mustard">Burger, Shake and a Smile</option>
                <option data-tokens="frosting">Sugar, Spice and all things nice</option>
            </select> -->
            <button class="btn btn-primary" type="submit" role="button">查询</button>
        </form>
    </div>
</div>
<!-- end of search form -->
<br>


<!-- Nav tabs -->
<div class="row">
    <div class="col-12">
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active">
                <a href="#preferential" aria-controls="preferential" role="tab" data-toggle="tab">反水佣金</a>
            </li>
        </ul>
    </div>
</div>
<br>


<!-- Tab panes -->
<div class="tab-content">

    <!-- 代理商反水佣金收入摘要 -->
    <div role="tabpanel" class="tab-pane active" id="preferential">
        <div class="row" style="margin-bottom: 15px;">
            <div class="col-12">
                <div class="myorganization_title" style="border-bottom: 1px #555 solid;border-left: 10px #820000 solid;padding: 8px;margin: 5px; font-weight:bold; font-size: 1em; width: 300px;">
                    <span>代理商反水佣金收入摘要</span>
                    <i class="fas fa-info-circle" title="「系统已发放金额」栏位为零，表示该金额尚未发送到个人派彩。请等待客服处理后即可领取。"></i>
                </div>
                <!-- <div class="well well-sm"> -->
                <!-- <p>* 「系统已发放金额」栏位为零，表示该金额尚未发送到个人派彩。请等待客服处理后即可领取。</p> -->
                <!-- </div> -->
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <span class="label label-default">目前查询的代理商帐号： <?php echo $member_account; ?></span>

                <!-- preferential table -->
                <table class="table agencyarea_summary">
                    <thead>
                        <th width="15%" class="text-center"><?php echo $tr['time']; ?></th>
                        <th width="20%" class="text-right">个人投注量</th>
                        <th width="20%" class="text-right">反水佣金金额</th>
                        <th width="20%" class="text-right">系统已发放金额</th>
                        <th class="text-right">更新时间及说明</th>
                    </thead>

                    <tbody>
                        <?php if (count($preferential_summary_result) > 0): ?>
                        <?php foreach ($preferential_summary_result as $preferential): ?>
                            <tr>
                            <td class="text-center"><?php echo ( (isset($preferential->dailydate) ? date("Y/ m/ d", strtotime($preferential->dailydate)) : '') ); ?></td>
                            <td class="text-right"><?php echo all_bets_amount_helper($preferential); ?></td>
                            <td class="text-right"><?php echo all_favorablerate_amount_helper($preferential); ?></td>
                            <td class="text-right"><?php echo number_format($preferential->all_favorablerate_beensent_amount, 2); ?></td>
                            <td class="text-right"><?php echo ( (isset($preferential->updatetime)) ? date("Y/ m/ d H:i", strtotime($preferential->updatetime)) : '' ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td>No DATA</td></tr>
                        <?php endif; ?>
                        <tfoot>
                            <th width="15%" class="text-center"><?php echo $tr['time']; ?></th>
                            <th width="20%" class="text-right">个人投注量</th>
                            <th width="20%" class="text-right">反水佣金金额</th>
                            <th width="20%" class="text-right">系统已发放金额</th>
                            <th class="text-right">更新时间及说明</th>
                        </tfoot>
                    </tbody>
                </table>
                <!-- end of preferential table -->
            </div>
        </div>
    </div>
    <!-- end of 代理商反水佣金收入摘要 -->
</div>

<?php endif; ?>

<?php end_section(); ?>
<!-- end of panelbody_content -->


<!-- begin of extend_js -->
<?php begin_section('extend_js'); ?>
<script>
$(function(){
  $("table").DataTable();

  /* 原本的日期起訖選擇，現已棄用 */
    /* $('#query_date_start_datepicker, #query_date_end_datepicker').datetimepicker({
    timepicker:false,
    format:'Y-m-d',
    lang:'en'
    }); */
}); // END FUNCTION
</script>
<?php end_section(); ?>
<!-- end of extend_js -->