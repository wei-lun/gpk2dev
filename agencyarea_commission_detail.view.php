<?php use_layout( $config['template_path']."template/admin.tmpl.php" ); ?>

<!-- begin of paneltitle_content -->
<?php begin_section('paneltitle_content'); ?>
<ul class="breadcrumb">
  <li>
    <a href="home.php"><span class="glyphicon glyphicon-home"></span></a>
  </li>
  <li>
    <a href="member.php"><?php echo $tr['Member Centre']; ?></a>
  </li>
  <li>
    <a href="agencyarea.php"><?php echo $tr['agencyarea title']; ?></a>
  </li>
  <li class="active">代理商收入摘要</li>
</ul>
<?php end_section(); ?>
<!-- end of paneltitle_content -->

<!-- begin of panelbody_content -->
<?php begin_section('panelbody_content'); ?>
<?php if (!$has_permission): ?>
  <div class="row">
    <div class="col-12">
      <?php echo get_permission_message(); ?>
    </div>
  </div>
  <br>
  <div class="row">
    <div id="preview_result"></div>
  </div>
<?php else: ?>

  <!-- agencyarea nav -->
  <div class="row">
    <div class="col-12">
      <?php echo menu_agentadmin('agencyarea_summary.php'); ?>
    </div>
  </div>
  <!-- end of agencyarea nav -->
  <br>
  <div class="row">
    <div class="col-12">
      <a class="btn btn-primary" onclick="window.history.back();">返回</a>
    </div>
  </div>
  <br>
  <div class="row">
    <div class="col-12 mb-2">
      <ul class="list-group">
        <li class="list-group-item"> 目前查询的会员帐号：<?php echo $commission_detail->member_account; ?>  </li>
        <li class="list-group-item"> 目前查询区间：<?php echo $commission_detail->dailydate; ?> ~ <?php echo $commission_detail->end_date; ?> </li>
        <li class="list-group-item list-group-item-success"> 当日总佣金：<?php echo $commission_detail->agent_commission; ?>  </li>
      </ul>
    </div>


    <div class="col-12">
      <p class="alert alert-success">
        * 计算公式：<br>
        代理商损益 = (一级代理之下所有会员娱乐城损益 - 平台成本 - 行销成本) * (分佣比例) <br>
        平台成本 = (娱乐城损益 * 平台成本比例) (平台成本比例: 依照代理商分佣等级</a>之设定) <br>
        行销成本 = (优惠金额 + 反水金额) * (承担比例) (承担比例: 依照代理商分佣等级</a>之设定) <br><br>
        * 分佣比例設定參考 代理商组织转帐及分佣设定 生成。<br>
        * 如果代理商损益经过分佣计算后，为负值，则累积到次分润盈余扣储上次留底后为正值后发放，如为负值则继续累计<br>
      </p>

      <!-- Nav tabs -->
      <ul class="nav nav-tabs" role="tablist">
        <li role="presentation" class="active"><a href="#preferential_detail" aria-controls="preferential_detail" role="tab" data-toggle="tab">佣金收入来源</a></li>
        <li role="presentation"><a href="#distribute_detail" aria-controls="distribute_detail" role="tab" data-toggle="tab">会员损益分配列表</a></li>
      </ul>
      <br>

      <!-- Tab panes -->
      <div class="tab-content">
        <!-- 佣金收入來源 -->
        <div role="tabpanel" class="tab-pane active" id="preferential_detail">
          <!-- plateform_cost -->
          <?php if ( count($commission_detail->commission_detail['all_profitloss_amount_detail']['plateform_cost']) > 0 ): ?>
            <p class="alert alert-warning">
              * 平台成本
            </p>
            <ul class="list-group">
              <?php foreach ($commission_detail->commission_detail['all_profitloss_amount_detail']['plateform_cost'] as $plateform_cost_row): ?>
                <li class="list-group-item">
                  <?php echo (get_plateform_cost_name($plateform_cost_row['type'])); ?>：
                  <?php echo $plateform_cost_row['cost_base'] . ' X ' . (100 * $plateform_cost_row['cost_rate']) .  '% = ' . $plateform_cost_row['cost']; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
          <!-- end of plateform_cost -->

          <?php if ($has_no_commission_from_successor): ?>
            <p class="alert alert-warning">
              * 无来自下线的佣金
            </p>
          <?php else: ?>
            <p class="alert alert-info">
              * 来自下线的佣金
            </p>

            <!-- show preferential detail list -->
            <table class="table">
              <tr>
                <th>佣金来源帐号</th>
                <th>佣金 ( = 该会员损益基数 X 此代理之分配比例 )</th>
              </tr>
              <?php foreach ($commission_detail->commission_detail['all_profitloss_amount_detail']['level_distribute'] as $list): ?>
                <tr>
                  <td>
                    <a href="<?php
                        echo (
                          'agencyarea_commission_detail.php?member_account=' . $list['from_account']
                          . '&dailydate_start=' . $dailydate_start
                          . '&dailydate_end=' . $dailydate_end
                        );
                      ?>"
                    >
                      <?php echo $list['from_account']; ?>
                    </a>
                    <?php if (isset($list['is_rest']) && $list['is_rest']): ?>
                      <span class='label label-warning pull-right'>未分出损益</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php echo $list['base_profitloss'] . ' X ' . (100 * $list['from_profitloss_rate']) .  '% = ' . $list['from_profitloss']; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </table>
            <!-- end of show preferential detail list -->
          <?php endif; ?>
        </div>
        <!-- end of 佣金收入來源 -->
        <!-- 會員損益分配列表 -->
        <div role="tabpanel" class="tab-pane" id="distribute_detail">
          <?php if ($has_no_self_profitloss): ?>
            <p class="alert alert-warning">
              * 无损益
            </p>
          <?php else: ?>
            <!-- bet detail table -->
            <div class="row">
              <?php foreach ($commission_detail->commission_detail['profitloss_distribute']['total_bets_detail'] as $casino => $category_bet): ?>
                <div class="col-12 col-sm-6">
                  <h4>
                    <?php echo $casino; ?>
                  </h4>

                  <table class="table table-borded">
                    <tr>
                      <th>分类</th>
                      <th class="text-right">损益</th>
                      <th class="text-right">分类损益比</th>
                      <th class="text-right">分类损益</th>
                    </tr>
                    <?php foreach ($category_bet as $category => $bet): ?>
                      <tr>
                        <td><?php echo $category; ?></td>
                        <td class="text-right">
                          <?php echo $bet; ?>
                        </td>
                        <td class="text-right">
                          <?php
                            echo ( ($commission_detail->commission_detail['profitloss_distribute']['casino_profitlossrates'][$casino][$category]) * 100 ) . ' %';
                          ?>
                        </td>
                        <td class="text-right">
                          <?php
                            echo ($bet * $commission_detail->commission_detail['profitloss_distribute']['casino_profitlossrates'][$casino][$category]);
                          ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </table>

                </div>
              <?php endforeach; ?>
            </div>
            <!-- end of bet detail table -->

            <p class="alert alert-info">
              * 会员损益基数: <?php echo $commission_detail->commission_detail['profitloss_distribute']['total_profitloss']; ?> (分类损益总和)<br>
            </p>

            <!-- show preferential distribute list -->
            <ul class="list-group">
              <?php foreach ($commission_detail->commission_detail['profitloss_distribute']['level_distribute'] as $list): ?>
                <li class="list-group-item">
                  <button class="btn btn-default">
                    <span class="glyphicon glyphicon-arrow-down" aria-hidden="true"></span>
                    <br>
                    <?php echo $list['to_account']; ?> ( <?php echo (100 * $list['to_profitloss_rate']); ?> %)
                  </button>
                  <?php echo $list['base_profitloss'] . ' X ' . (100 * $list['to_profitloss_rate']) .  '% = ' . $list['to_profitloss']; ?>
                </li>
              <?php endforeach; ?>
              <?php
                if ( isset( $commission_detail->commission_detail['profitloss_distribute']['rest_distribute'] )
                  && !empty( $commission_detail->commission_detail['profitloss_distribute']['rest_distribute'] )
                ):
                  $rest_distribute = $commission_detail->commission_detail['profitloss_distribute']['rest_distribute'];
              ?>
                <li class="list-group-item list-group-item-warning">
                  未分出损益:
                  <?php echo $list['base_profitloss'] . ' X ' . (100 * $rest_distribute['to_profitloss_rate']) .  '% = ' . $rest_distribute['to_profitloss']; ?>
                </li>
              <?php endif; ?>
            </ul>
            <!-- end of show preferential distribute list -->
          <?php endif; ?>
        </div>
        <!-- end of 會員損益分配列表 -->
      </div>
      <!-- end of Tab panes -->
    </div>

  </div>
<?php endif; ?>

<?php end_section(); ?>
<!-- end of panelbody_content -->
