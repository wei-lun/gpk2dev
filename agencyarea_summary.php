<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 代理商專區，轉帳及觀看會員的報表。
// File Name:	agencyarea_summary.php
// Author:		Yuan、Damocles
// Related:
// Log:
// ----------------------------------------------------------------------------
/*
DB Table :
root_statisticsdailyreport - 每日營收日結報表
root_statisticsbonusagent - 放射線組織獎金計算-直銷組織加盟金
root_statisticsbonussale - 放射線組織獎金計算-營業獎金
root_statisticsbonusprofit - 放射線組織獎金計算-營運利潤獎金


File :
agencyarea.php - 代理商專區
member_agentdepositgcash.php - 代理商會員錢包轉帳給其他會員
bonus_commission_agent_deltail.php - 傭金分紅明細
bonus_commission_sale_deltail.php - 營業分紅明細
bonus_commission_profit_deltail.php - 營利分紅明細
*/

// 主機及資料庫設定
require_once __DIR__ ."/config.php";
// 支援多國語系
require_once __DIR__ ."/i18n/language.php";
// 自訂函式庫
require_once __DIR__ ."/lib.php";

require_once __DIR__ ."/lib_member_tree.php";

require_once __DIR__ ."/lib_view.php";

require_once __DIR__ ."/lib_agents_setting.php";

// 擴充 head 內的 css or js
$extend_head = '';

$extend_head =<<<HTML

<!-- 參考使用 datatables 顯示 -->
<!-- https://datatables.net/examples/styling/bootstrap.html -->
<link rel="stylesheet" type="text/css" href="{$cdnfullurl_js}datatables/css/jquery.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="{$cdnfullurl_js}datatables/css/responsive.bootstrap.min.css">
<script type="text/javascript" language="javascript" src="{$cdnfullurl_js}datatables/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" language="javascript" src="{$cdnfullurl_js}datatables/js/dataTables.bootstrap.min.js"></script>
<script type="text/javascript" language="javascript" src="{$cdnfullurl_js}datatables/js/dataTables.responsive.min.js"></script>
<script type="text/javascript" language="javascript" src="{$cdnfullurl_js}datatables/js/responsive.bootstrap.min.js"></script>
<script type="text/javascript" language="javascript" src="//cdn.datatables.net/plug-ins/1.10.12/api/sum().js"></script>

<!-- bootstrap-select -->
<script src="in/bootstrap-select/1.13.1/bootstrap-select.min.js"></script>
<link rel="stylesheet" href="in/bootstrap-select/1.13.1/bootstrap-select.min.css">

<style>
.dataTables_filter, .dataTables_info {
    display: none;
}

table.table-condensed > tbody > tr > td:not(.off){
    color: #212529;
}
</style>
HTML;

// var_dump(session_id());
// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
// RegSession2RedisDB();

// 偵測是否為手機版頁面，如果是，則跳警告並導到推廣註冊頁
// mobile_device_detect();
// ----------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
//加盟聯營股東專區


if (!isset($_SESSION['member']) || $_SESSION['member']->therole != 'A') {
     echo '<script>location.replace("./home.php");</script>';
}

// 有登入，有錢包才顯示。只有代理商可以進入
$has_permission = (
    isset($_SESSION['member'])
    && ($_SESSION['member']->therole == 'A' || $_SESSION['member']->therole == 'R')
);

// no permission return no permission view
if(! $has_permission) {
    // render view
    $function_title = '反水佣金明细';
    $tmpl['html_meta_title'] = $function_title.'-'.$config['companyShortName'];

    return render(
        __DIR__ . '/agencyarea_summary.view.php',
        compact('function_title', 'has_permission')
    );
}

// 取得該帳號所屬下級代理商與會員 (不含自身)
$member_search = <<<SQL
    WITH RECURSIVE subordinates AS (
        SELECT
          id,
          parent_id,
          account
        FROM root_member
        WHERE id = :id
        UNION
          SELECT
            m.id,
            m.parent_id,
            m.account
          FROM root_member m
          INNER JOIN subordinates s ON m.parent_id = s.id
    ) SELECT *
      FROM subordinates
      WHERE (account!=:account);
SQL;
$recursive_result = runSQLall_prepared( $member_search, [
    ':id'=>$_SESSION['member']->id,
    ':account'=>$_SESSION['member']->account
], '', 0, 'r' );

// render view
$function_title = $tr['rakeback summary'];
$tmpl['html_meta_title'] = $function_title.'-'.$config['companyShortName'];
// 側邊欄MENU var[0]側邊欄組合(lib_menu) var[1]目前頁籤名
$tmpl['sidebar_content'] = ['agent','agencyarea_summary'];
// 擴充再 head 的內容 可以是 js or css
$tmpl['extend_head'] = $extend_head;
// banner標題
$tmpl['banner'] = ['rakeback summary'];
// menu增加active
$tmpl['menu_active'] =['agent_instruction.php'];

return render(
    __DIR__ . '/agencyarea_summary_ui.view.php',
    compact(
        'function_title',
        'has_permission',
        'recursive_result'
    )
);