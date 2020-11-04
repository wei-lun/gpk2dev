
<?php
// ----------------------------------------------------------------------------
// Features:  前端 -- 代理中心，转帐及观看会员的报表。
// File Name:  agencyarea.php
// Author:    Webb Lu, Barkley
// Related:
// Log:
// ----------------------------------------------------------------------------
/*
DB Table :
root_member 会员资料

File :
agencyarea.php - 代理中心
member_agentdepositgcash.php - 代理商会员钱包转帐给其他会员
 */

// 主机及资料库设定
require_once dirname(__FILE__) . "/config.php";
// 支援多国语系
require_once dirname(__FILE__) . "/i18n/language.php";
// 自订函式库
require_once dirname(__FILE__) . "/lib.php";
require_once dirname(__FILE__) . "/lib_common.php";
require_once dirname(__FILE__) . "/lib_member_tree.php";
require_once dirname(__FILE__) . "/lib_view.php";
require_once dirname(__FILE__) . "/lib_agents_setting.php";

// var_dump(session_id());
// 只要 session 活著,就要同步纪录该 user account in redis server db 1
RegSession2RedisDB();

// 偵測是否為手機版頁面，如果是，則跳警告並導到推廣註冊頁
mobile_device_detect();
// ----------------------------------------------------------------------------
// Function Area
// 使用到的 function 整理在此处，以利维护及后续拆分

// 取出该代理帐号的下线及相关资讯
function getMemberChild($query_member_id, $level = 1) {
  global $tzonename;
  $member_sql = "SELECT id, account, therole, to_char((enrollmentdate AT TIME ZONE '$tzonename'),'YYYY-MM-DD HH24:MI:SS') as enrollmentdate, status, feedbackinfo, parent_id FROM root_member WHERE parent_id = :query_member_id ORDER BY id;";
  $childs = runSQLall_prepared($member_sql, ['query_member_id' => $query_member_id]);
  // var_dump($childs);
  return $childs;
}

// 检查 $query_member_id 是否合理，如果 SESSION ID 是查询对象的祖先/自身才允许往下查
function is_query_member_valid($ancestors) {
  $is_query_member_id_valid = false;
  foreach ($ancestors as $parent_info) {
    if ($parent_info->id == $_SESSION['member']->id):
      $is_query_member_id_valid = true;
      break;
    endif;
  }
  return $is_query_member_id_valid;
}

// 生成 select 中的 options；特定数值范围
$options = function ($selected = null, $min = 0, $max = 100) {
  $option_null = '<option value>未设定</option>';
  $options_Arr = array_map(function ($value) use ($selected) {
    return !is_null($selected) && $value == $selected ? "<option selected=\"selected\" value=\"$value\">$value %</option>" : "<option value=\"$value\">$value %</option>";
  }, range($min, $max));
  return $option_null . implode('', $options_Arr);
};

$f_to_100 = 'float_to_percent';

// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化变数
// 功能标题，放在标题列及meta
//加盟联营股东专区
$function_title = $tr['rebate rate setting'];
// 扩充 head 内的 css or js
$extend_head = '';
// 放在结尾的 js
$extend_js = '';
// body 内的主要内容
$indexbody_content = '';
// 系统讯息选单
$messages = '';
// 初始化变数 end
// ----------------------------------------------------------------------------
// 导览列
$navigational_hierarchy_html = '
<ul class="breadcrumb">
  <li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
  <li><a href="member.php">' . $tr['Member Centre'] . '</a></li>
  <li><a href="agencyarea.php">' . $tr['agencyarea title'] . '</a></li>
  <li class="active">' . $function_title . '</li>
</ul>
';
// ----------------------------------------------------------------------------

if (!isset($_SESSION['member']) || $_SESSION['member']->therole != 'A') {
  echo '<script>document.location.href="./home.php";</script>';
}

// 有登入，有钱包才显示。只有代理商可以进入
$has_permission = (
  isset($_SESSION['member'])
  && ($_SESSION['member']->therole == 'A' || $_SESSION['member']->therole == 'R')
);

function hint_html() {
  global $tr;
  if (isset($_SESSION['member'])):
    if ($_SESSION['member']->therole == 'T'):
      $description = $tr['trail use member first'];
    elseif ($_SESSION['member']->therole != 'A' && $_SESSION['member']->therole != 'R'):
      // 直接从 menu 拿描述或自己写描述
      $description = menu_agentadmin('agencyarea.php');
    else:
      $description = 'pass';
    endif;
  else:
    $description = login2return_url(0);
  endif;
  return $description;
};

if (!$has_permission) {
  return render(
    __DIR__ . '/agencyarea.view.php',
    compact('tmpl', 'has_permission', 'function_title')
  );
}

// -------------------------------------------------------------------
// 代理商组织列表
// -------------------------------------------------------------------
$query_member_id = isset($_GET['a']) ? (int) filter_input(INPUT_GET, 'a') : $_SESSION['member']->id;
$_SESSION['query_member_id'] = $query_member_id;

//上級代理商(含root)與自己
$ancestors = MemberTreeNode::getPredecessorList($query_member_id);
$ancestors = array_map(function ($ancestor) use ($query_member_id) {
  // 只允许初始化被访问的代理商
  $init = $ancestor->therole == 'A' && !isset($ancestor->feedbackinfo) && $ancestor->id == $query_member_id;
  $ancestor->feedbackinfo = getMemberFeedbackinfo($ancestor, $init);
  return $ancestor;
}, $ancestors);


// 一级代理商
$_1st_agent = array_reverse($ancestors)[1] ?? null;
// echo '<pre>' , var_dump($_1st_agent) , '</pre>'; exit();

// 自身
$user = $ancestors[0];

// 检查 $query_member_id 是否合理，如果 SESSION ID 是查询对象的祖先/自身才允许往下查
is_query_member_valid($ancestors) OR die('不合法测试：试图查询组织中非下线成员');

// 取得下线资料
$childs = getMemberChild($query_member_id);
// echo '<pre>' , var_dump($childs) , '</pre>'; exit();

$childs = array_map(function ($child) {
  // 只允许初始化自身与下线
  $init = $child->therole == 'A' && !isset($child->feedbackinfo);
  $child->feedbackinfo = getMemberFeedbackinfo($child, $init);
  return $child;
}, $childs);

// 取出要查询帐号的下线及相关资讯
if ($query_member_id == $_SESSION['member']->id):// 目前所觀看的對象 == 登入帳號
  // 损益反水分佣调配开关，统一控制
  $adjust_ratio_status = '';
  $query_member_account = $_SESSION['member']->account;
  // 会员的佣金占成派发比例 %
  $query_member_account_div = float_to_percent($user->feedbackinfo->dividend->allocable);
  // 会员的反水占成 %
  $query_member_account_preferentialratio = float_to_percent($user->feedbackinfo->preferential->allocable);
else:
  $adjust_ratio_status = 'disabled';

  // 第一层是自己
  $query_member_account = $ancestors[1]->account;// var_dump($query_member_account);exit();
  // 会员的佣金占成派发比例 %
  // $query_member_account_div = $ancestors[1]->dividendratio * 100;
  $query_member_account_div = float_to_percent($user->feedbackinfo->dividend->allocable);
  // 会员的反水占成 %
  $query_member_account_preferentialratio = float_to_percent($user->feedbackinfo->preferential->allocable);
endif;

// 提示说明
// $agentadmin_message_html = '<div class="alert alert-info">* 代理商组织转帐及占成设定</div>'; // 原本舊的code
$agentadmin_message_html = <<<HTML
    <div style="margin-bottom: 20px;">
        <span style="font-size: 1.5rem;">{$tr['agent line']}｜</span>
        <span>{$tr['member self rebate']}：{$f_to_100($_1st_agent->feedbackinfo->preferential->{'1st_agent'}->self_ratio)} &#37;</span><!-- 百分比符號%為HTML特別字元，要用字元代碼替換 -->
    </div>
HTML;

// 更新占成比例的提示说明文字 (代理商组织转帐及占成设定)
$agentadmin_message_html = sprintf(
  $agentadmin_message_html,
  '<strong>' . min((int) $query_member_account_preferentialratio, $f_to_100($_1st_agent->feedbackinfo->preferential->{'1st_agent'}->child_occupied->max)) . ' %</strong>',
  '<strong>' . min((int) $query_member_account_div, $f_to_100($_1st_agent->feedbackinfo->dividend->{'1st_agent'}->child_occupied->max)) . ' %</strong>'
);

$show_href = function ($arrays) {
  $flags = [];
  $flag = false;
  // session 在 array 的 index
  foreach ($arrays as $index => $value) {
    if ($value['id'] == $_SESSION['member']->id) {
      $flag = true;
    }

    $flags[$index] = $flag;
  }
  return $flags;
}; // end $show_href

$occupied_list_DOMs = function ($memberinfo, $show_href_flag, $self_ratio) use ($query_member_id, $config) {
  $memberinfo = (object) $memberinfo;
  // 计算法一，对直属有利
  // $occupied = isset($memberinfo->source) && isset($memberinfo->occupied_state) && $memberinfo->occupied_state ? array_sum($memberinfo->source) : 0;
  // 计算法二，对没向下分配的代理商有利
  $occupied = isset($memberinfo->source) ? array_sum($memberinfo->source) : 0;

  $btn_style = 'btn-default';
  $btn_state = '';

  if ($show_href_flag == true):
    if (isset($memberinfo->status) && $memberinfo->status == 0 && $memberinfo->id != $config['system_company_id'] && !empty($memberinfo->id)):
      $btn_style = 'btn-danger';
      $title = '此帐号状态为禁用，请联系管理人员';
    elseif ($memberinfo->id == $_SESSION['member']->id): $btn_style = 'btn-primary';
    elseif ($memberinfo->id == $query_member_id): $btn_style = 'btn-info';
    endif;
  else:
    $btn_state = 'disabled';
  endif;

  return <<<HTML
    <a class="btn $btn_style btn-sm $btn_state" href="agencyarea.php?a={$memberinfo->id}" data-toggle="tooltip">{$memberinfo->account}</a>
HTML;
}; // end $occupied_list_DOMs


// 图例：佣金占成比例
$dividend_occupied = occupied_list($ancestors, 'dividend');
// 從目前登入身分開始顯示，並隱藏 % 數
foreach ($dividend_occupied as &$value) {
  if ($value['id'] == $_SESSION['member']->parent_id) {
    $value['role'] = $config['system_company_account'];
    break;
  }
  array_shift($dividend_occupied);
}

// 去除掉公司，陣列1不存在!!
$key_start = 0;
$_dividend_occupied =[];
$self_ratio_array =[];
foreach($dividend_occupied as $val){
  if($val['role'] != 'root'){
    $_dividend_occupied[$key_start] = $val;
    $self_ratio_array[$key_start] ='';
    if($key_start===0){
      $key_start += 2;
    }
    else{
      $key_start++;
    }
  }
}

$dividend_occupied_list_DOMs = array_map($occupied_list_DOMs, $_dividend_occupied, $show_href($_dividend_occupied), $self_ratio_array);
$dividend_occupied_list = implode('<span class="glyphicon glyphicon-arrow-right" aria-hidden="true"></span>', $dividend_occupied_list_DOMs);

// 代理商搜寻的条件
// echo '<pre>', var_dump( $user->feedbackinfo->preferential ), '</pre>'; exit();
$lockedinfo_preferential = (@$user->feedbackinfo->preferential->locked) ? '会员 ' . $query_member_account . ' 已分配下线，上层不可降反水比例' : '';
$lockedinfo_dividend = (@$user->feedbackinfo->dividend->locked) ? '会员 ' . $query_member_account . ' 已分配下线，上层不可降占成比例' : '';

// ==================================================================================================================================================================================

// 表格栏位名称
// group hint 1
// 目前觀看的使用者帳號名稱
$current_query_member = "SELECT account FROM root_member WHERE id = :id LIMIT 1;";
$current_member = runSQLall_prepared($current_query_member, ['id' => $_SESSION['query_member_id']]); // var_dump($current_member);

$table_colname_html_hint_head = <<<HTML
<tr>
  <th colspan="3">{$tr['watching']}：{$current_member[0]->account}</th>
  <th colspan="2" class="text-center" style="vertical-align: middle">{$tr['directly under guarantee']}</th>
  <th colspan="4" class="text-center">{$tr['rebate setting of nondirectly']}</th>
</tr>
HTML;

//下线第1代 身分 入会时间(UTC+8) 帐号状态
$table_colname_html = '
<tr>
  <th>' . $tr['downline'] . '</th><!-- ' . $tr['1st downline'] . ' -->
  <th>' . $tr['idetntity'] . '</th>
  <th>' . $tr['account status'] . '</th>
  <th class="text-center">'.$tr['rebate'].'</th>
  <th class="text-center">'.$tr['commission'].'</th>
  <th class="text-center">'.$tr['rebate rate'].'</th>
  <th class="text-center">'.$tr['rebate'].'</th>
  <th class="text-center">'.$tr['rebate rate'].'</th>
  <th class="text-center">'.$tr['commission'].'</th>
</tr>
';

$show_listrow_html = '';

if ($user->therole == 'A') { /* var_dump( count($childs) ); exit(); */
  for ($i = 0; $i < count($childs); $i++) {

    // 反水占成比例
    $preferentialratio_html = '';
    $p_allocable_user = $query_member_account_preferentialratio; //var_dump($p_allocable_user);
    $p_allocable_memberinfo = is_null($childs[$i]->feedbackinfo->preferential->allocable) ? null : float_to_percent($childs[$i]->feedbackinfo->preferential->allocable); //var_dump($p_allocable_memberinfo);
    $p_allocable_memberinfo_str = is_numeric($p_allocable_memberinfo) ? "$p_allocable_memberinfo %" : 'N/A'; //var_dump($p_allocable_memberinfo_str);
    $p_allocable_diff = !is_null($p_allocable_user) && !is_null($p_allocable_memberinfo) ? $p_allocable_user - $p_allocable_memberinfo : 0; //var_dump($p_allocable_diff);

    // id
    $preferentialratio_option_state = $childs[$i]->status == 1 ? '' : 'disabled';
    $_allocable = is_null($childs[$i]->feedbackinfo->preferential->allocable) ? null : float_to_percent($childs[$i]->feedbackinfo->preferential->allocable); // 可分配額度
    $_max = max(0, $query_member_account_preferentialratio - float_to_percent($_1st_agent->feedbackinfo->preferential->{'1st_agent'}->child_occupied->min)); // 最大值
    $_min = max(0, $query_member_account_preferentialratio - float_to_percent($_1st_agent->feedbackinfo->preferential->{'1st_agent'}->child_occupied->max)); // 最小值

    // 佣金分用比例 -- 上线:下线
    $d_allocable_user = $query_member_account_div;
    $d_allocable_memberinfo = is_null($childs[$i]->feedbackinfo->dividend->allocable) ? null : float_to_percent($childs[$i]->feedbackinfo->dividend->allocable);
    $d_allocable_memberinfo_str = is_numeric($d_allocable_memberinfo) ? "$d_allocable_memberinfo %" : 'N/A';
    $d_allocable_diff = !is_null($d_allocable_user) && !is_null($d_allocable_memberinfo) ? $d_allocable_user - $d_allocable_memberinfo : 0;
    $dividendratio_option = $options(
      is_null($childs[$i]->feedbackinfo->dividend->allocable) ? null : float_to_percent($childs[$i]->feedbackinfo->dividend->allocable),
      max(0, $query_member_account_div - float_to_percent($_1st_agent->feedbackinfo->dividend->{'1st_agent'}->child_occupied->max)),
      max(0, $query_member_account_div - float_to_percent($_1st_agent->feedbackinfo->dividend->{'1st_agent'}->child_occupied->min))
    );
    // id
    $dividendratio_option_id = $childs[$i]->account . '_' . $childs[$i]->id;
    $dividendratio_option_state = $childs[$i]->status == 1 ? '' : 'disabled';
  }

  // 目前登入者且允许在前台显示的资料，供反水设定用
  $loggin_userinfo = json_encode(
    [
      'id' => $_SESSION['member']->id,
      'account' => $_SESSION['member']->account,
      'feedbackinfo' => $user->feedbackinfo,
    ]
  );

  $percent_view = function ($var) {
    return is_numeric($var) ? $var . ' %' : '未设定';
  };

  @$p_hint = <<<HTML
      当 $user->account 的直属下线（假名小华）投注时，小华扮演玩家的角色，$user->account 可获得小华 $p_allocable_user % 的投注反水抽成；<hr>
      为了保障较下游的代理商，若投注抽成过少的情况，则会补足到末代保障的比例；多馀的部份则返回一级代理商 $_1st_agent->account 身上。<hr>
      当 $user->account 的直属下线（小华）为代理商时，小华拥有 $user->account 所设定代理反水占成比例可供支配<small>（即小华可获得直属下线的投注反水抽成，比例足够的情形下可对下线配比）</small>
HTML;
  @$d_hint = <<<HTML
      当 $user->account 的直属下线（假名小华）投注时，小华扮演玩家的角色，$user->account 可获得小华 $p_allocable_user % 的投注佣金抽成；<hr>
      为了保障较下游的代理商，若投注抽成过少的情况，则会补足到末代保障的比例；多馀的部份则返回一级代理商 $_1st_agent->account 身上。<hr>
      当 $user->account 的直属下线（小华）为代理商时，小华拥有 $user->account 所设定代理佣金占成比例可供支配<small>（即小华可获得直属下线的投注佣金抽成，比例足够的情形下可对下线配比）</small>
HTML;

   // 主要排版: 列出资料, 主表格架构
   $agentadmin_html = '
   <div>
     '.$agentadmin_message_html.'
     <p class="mb-2">' . $dividend_occupied_list . '</p>
     <hr>
     <span style="font-size: 1.5rem;">'.$tr['rebate rate setting'].'｜</span>
     <span>'.$tr['Agency transfer and rebate setting'].'</span>
     <hr>
     <div>' . $lockedinfo_preferential . '</div>
     <div>' . $lockedinfo_dividend . '</div>
     ' . (empty($lockedinfo_preferential . $lockedinfo_dividend) ? '' : '<hr') . '
   </div>
   <table id="transaction_list" class="table table-striped agencyarea_table newstyle_table" cellspacing="0" width="100%" style="width:100%;">
   <thead>
   ' . $table_colname_html_hint_head . '
   ' . $table_colname_html . '
   </thead>
   <tfoot>
   ' . $table_colname_html . '
   </tfoot>
   <tbody>
   </tbody>
   </table>
   '; //$show_listrow_html

   // $agentadmin_html = '
   // <div class="col-12">'.$agentadmin_message_html.'</div>
   // <!--div class="col-12"></div-->

   // <div class="col-12">
   //   <!-- 下線階級圖 -->
   //   <div class="row">
   //     <div class="col-12">
   //       <p class="mb-2">' . $dividend_occupied_list . '</p>
   //     </div>
   //   </div>
   //   <hr>
   //   <!--  -->
   //   <div class="row" style="margin-bottom: 20px;">
   //     <div class="col-12">
   //       <span style="font-size: 1.5rem;">'.$tr['rebate rate setting'].'｜</span>
   //       <span>'.$tr['Agency transfer and rebate setting'].'</span>
   //     </div>
   //   </div>
   // </div>

   // <div class="col-12">
   //   <div class="row">
   //     <div class="col-12">
   //       <div class="row">' . $lockedinfo_preferential . '</div>
   //       <div class="row">' . $lockedinfo_dividend . '</div>
   //     </div>
   //   </div>
   //   ' . (empty($lockedinfo_preferential . $lockedinfo_dividend) ? '' : '<hr') . '
   // </div>

   // <div class="col-12">
   // 	<table id="transaction_list" class="table table-striped agencyarea_table newstyle_table" cellspacing="0" width="100%">
   // 	<thead>
   // 	' . $table_colname_html_hint_head . '
   // 	' . $table_colname_html . '
   // 	</thead>
   // 	<tfoot>
   // 	' . $table_colname_html . '
   // 	</tfoot>
   // 	<tbody>
   // 	</tbody>
   // 	</table>
   // </div>
   // '; //$show_listrow_html
} else {
  //(X) 代理商组织列表查无相关资料。
  $agentadmin_html = '<div class="col-12">' . $tr['agency organization no data'] . '</div>';
}

// 分红明细 button 事件 js
// 跳转到分红明细页面
$extend_js = $extend_js . "";

// -------------------------------------------------------------------
// 一级代理商设定区域
// -------------------------------------------------------------------
// 标题

$permission_control = ($_SESSION['member']->parent_id == $config['system_company_id']) ? '' : 'disabled';
// $_1st_agent = array_reverse($ancestors)[1];
// var_dump($_1st_agent);

// 提示说明
$_1st_agemt_message_html = '<div class="alert alert-info"> * 仅一级代理商 (' . $_1st_agent->account . ') 能够设定；下线遵循一级代理商此处设定</div>';


$is_1st_agent = ($_SESSION['member']->parent_id == $config['system_company_id']) ? 'true' : 'false';

// render view
$tmpl['html_meta_title'] = '占成比例设定-' . $config['companyShortName'];
// 側邊欄MENU var[0]側邊欄組合(lib_menu) var[1]目前頁籤名
$tmpl['sidebar_content'] =['agent','agencyarea'];

$query_account = '';
if( isset($_GET['id']) && is_numeric($_GET['id']) ){
  $sql = 'SELECT account FROM "root_member" WHERE id = :query_id LIMIT 1';
  $result = runSQLall_prepared($sql, ['query_id' => intval($_GET['id'])]);
  $query_account = $result[0]->account;
}

// menu增加active
$tmpl['menu_active'] =['agent_instruction.php'];

return render(
  __DIR__ . '/agencyarea.view.php',
  compact(
    'function_title',
    'has_permission',
    'agentadmin_html',
    'is_1st_agent',
    'loggin_userinfo',
    'query_account'
  )
);
