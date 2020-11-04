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

<div class="row">
  <div class="col-12">
    <?php echo menu_agentadmin('agencyarea_summary.php'); ?>
  </div>
</div>
<br>
<div class="row">
  <div class="col-12">
    <a class="btn btn-primary" onclick="window.history.back();">返回</a>
  </div>
</div>
<br>
<?php if (!$has_permission): ?>
  <div class="row">
    <div class="col-12">
      <?php echo( $is_test_account ? $tr['trail use member first'] : $tr['member login first'] ); ?>
    </div>
  </div>
  <br>
  <div class="row">
    <div id="preview_result"></div>
  </div>
<?php else: ?>
  <div class="row">
    <div class="col-12">
      <ul class="list-group">
        <li class="list-group-item"> 目前查询的会员帐号：<?php echo $preferential_detail->member_account; ?>  </li>
        <li class="list-group-item"> 目前查询日期：<?php echo $preferential_detail->dailydate; ?>  </li>
        <li class="list-group-item list-group-item-success"> 當日總反水：<?php echo $preferential_detail->all_favorablerate_amount; ?>  </li>
        <?php if ( isset($preferential_detail->all_favorablerate_amount_detail['self_favorable']) ): ?>
          <li class="list-group-item"> 自身反水：<?php echo $preferential_detail->all_favorablerate_amount_detail['self_favorable']; ?>  </li>
        <?php endif; ?>
      </ul>
    </div>
    <div class="col-12">


      <!-- Nav tabs -->
      <ul class="nav nav-tabs" role="tablist">
        <li role="presentation" class="active"><a href="#preferential_detail" aria-controls="preferential_detail" role="tab" data-toggle="tab">反水佣金收入來源</a></li>
        <li role="presentation"><a href="#distribute_detail" aria-controls="distribute_detail" role="tab" data-toggle="tab">會員反水分配列表</a></li>
      </ul>
      <br>

      <!-- Tab panes -->
      <div class="tab-content">
        <!-- 反水佣金收入來源 -->
        <div role="tabpanel" class="tab-pane active" id="preferential_detail">
          <?php if ($has_no_preferential_from_successor): ?>
            <p class="alert alert-warning">
              * 無來自下線的反水
            </p>
          <?php else: ?>
            <p class="alert alert-info">
              * 來自下線的反水
            </p>

            <!-- show preferential detail list -->
            <table class="table">
              <tr>
                <th>反水來源帳號</th>
                <th>反水 ( = 該會員反水基數 X 此代理之分配比例 )</th>
              </tr>
              <?php foreach ($preferential_detail->all_favorablerate_amount_detail['level_distribute'] as $list): ?>
                <tr>
                  <td>
                    <a href="agencyarea_preferential_detail.php?member_account=<?php echo $list['from_account']; ?>&dailydate=<?php echo $preferential_detail->dailydate; ?>">
                      <?php echo $list['from_account']; ?>
                    </a>
                    <?php if (isset($list['is_rest']) && $list['is_rest']): ?>
                      <span class='label label-warning pull-right'>未分出反水</span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo $list['base_favorable'] . ' X ' . (100 * $list['from_favorable_rate']) .  '% = ' . $list['from_favorable']; ?></td>
                </tr>
              <?php endforeach; ?>
            </table>
            <!-- end of show preferential detail list -->
          <?php endif; ?>
        </div>
        <!-- end of 反水佣金收入來源 -->
        <!-- 會員反水分配列表 -->
        <div role="tabpanel" class="tab-pane" id="distribute_detail">
          <?php if ($has_no_self_preferential): ?>
            <p class="alert alert-warning">
              * 無自身反水
            </p>
          <?php else: ?>
            <!-- bet detail table -->
            <div class="row">
              <?php foreach ($preferential_detail->favorable_distribute['total_bets_detail'] as $casino => $category_bet): ?>
                <div class="col-12 col-sm-6">
                  <h4>
                    <?php echo $casino; ?>
                  </h4>

                  <table class="table table-borded">
                    <tr>
                      <th>分類</th>
                      <th class="text-right">投注</th>
                      <th class="text-right">分類反水比</th>
                      <th class="text-right">分類反水</th>
                    </tr>
                    <?php foreach ($category_bet as $category => $bet): ?>
                      <tr>
                        <td><?php echo $category; ?></td>
                        <td class="text-right">
                          <?php echo $bet; ?>
                        </td>
                        <td class="text-right">
                          <?php
                            echo ( ($preferential_detail->favorable_distribute['casino_favorablerates'][$casino][$category]) * 100 ) . ' %';
                          ?>
                        </td>
                        <td class="text-right">
                          <?php
                            echo ($bet * $preferential_detail->favorable_distribute['casino_favorablerates'][$casino][$category]);
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
              * 會員反水基數: <?php echo $preferential_detail->favorable_distribute['total_favorable']; ?> (分類反水總和)<br>
              * 會員自身反水比例: <?php echo (100 * $self_favorablerate); ?> %<br>
              * 會員自身反水 <?php echo $preferential_detail->all_favorablerate_amount_detail['self_favorable']; ?>
            </p>

            <!-- show preferential distribute list -->
            <ul class="list-group">
              <?php foreach ($preferential_detail->favorable_distribute['level_distribute'] as $list): ?>
                <li class="list-group-item">
                  <button class="btn btn-default">
                    <span class="glyphicon glyphicon-arrow-down" aria-hidden="true"></span>
                    <br>
                    <?php echo $list['to_account']; ?> ( <?php echo (100 * $list['to_favorable_rate']); ?> %)
                  </button>
                  <?php echo $list['base_favorable'] . ' X ' . (100 * $list['to_favorable_rate']) .  '% = ' . $list['to_favorable']; ?>
                </li>
              <?php endforeach; ?>
              <?php
                if ( isset($preferential_detail->favorable_distribute['rest_distribute'])
                  && !empty($preferential_detail->favorable_distribute['rest_distribute'])
                ):
                  $rest_distribute = $preferential_detail->favorable_distribute['rest_distribute'];
              ?>
                <li class="list-group-item list-group-item-warning">
                  未分出反水:
                  <?php echo $list['base_favorable'] . ' X ' . (100 * $rest_distribute['to_favorable_rate']) .  '% = ' . $rest_distribute['to_favorable']; ?>
                </li>
              <?php endif; ?>
            </ul>
            <!-- end of show preferential distribute list -->
          <?php endif; ?>
        </div>
        <!-- end of 會員反水分配列表 -->
      </div>
      <!-- end of Tab panes -->
    </div>

  </div>
<?php endif; ?>

<?php end_section(); ?>
<!-- end of panelbody_content -->
