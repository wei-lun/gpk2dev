<?php
    // agencyarea_member_datas.php
    require_once dirname(__FILE__)."/config.php";
    require_once dirname(__FILE__)."/lib_common.php";
    require_once dirname(__FILE__)."/i18n/language.php";// 支援多国语系
    require_once dirname(__FILE__)."/lib_agents_setting.php"; //function float_to_percent from
    require_once dirname(__FILE__)."/lib_member_tree.php";


    if ( isset($_SESSION['query_member_id']) ) {
        $query_member_id = $_SESSION['query_member_id'];

        //上級代理商(含root)與自己
        $ancestors = MemberTreeNode::getPredecessorList($query_member_id); // echo '<pre>', var_dump($ancestors), '</pre>'; exit();
        $ancestors = array_map(function ($ancestor) use ($query_member_id) {
            // 只允许初始化被访问的代理商
            $init = $ancestor->therole == 'A' && !isset($ancestor->feedbackinfo) && $ancestor->id == $query_member_id;
            $ancestor->feedbackinfo = getMemberFeedbackinfo($ancestor, $init);
            return $ancestor;
        }, $ancestors); // echo '<pre>', var_dump($ancestors), '</pre>'; exit();

        // 自身
        $user = $ancestors[0];

        // 一级代理商
        $_1st_agent = array_reverse($ancestors)[1] ?? null;

        // 目前所觀看的對象 == 登入帳號 (是否可以調整反水、佣金比例)
        $adjust_ratio_status = ( ($query_member_id == $_SESSION['member']->id) ? '' : 'disabled' );

        // 会员的反水占成 %
        $p_allocable_user = float_to_percent($user->feedbackinfo->preferential->allocable);
        $p_max = max(0, $p_allocable_user - float_to_percent($_1st_agent->feedbackinfo->preferential->{'1st_agent'}->child_occupied->min));
        $p_min = max(0, $p_allocable_user - float_to_percent($_1st_agent->feedbackinfo->preferential->{'1st_agent'}->child_occupied->max));

        // 会员的佣金占成派发比例 %
        $d_allocable_user = float_to_percent($user->feedbackinfo->dividend->allocable);
        $d_max = max(0, $d_allocable_user - float_to_percent($_1st_agent->feedbackinfo->dividend->{'1st_agent'}->child_occupied->min));
        $d_min = max(0, $d_allocable_user - float_to_percent($_1st_agent->feedbackinfo->dividend->{'1st_agent'}->child_occupied->max));

        global $tzonename;
        $empty_prefix = '----';
        $searchable_columns = [
            'id',
            'account',
            'therole',
            'enrollmentdate',
            'status',
            'feedbackinfo',
            'parent_id'
        ];
        $orderable_columns = [
            0 => 'account',
            1 => 'therole',
            2 => 'status',
            5 => 'feedbackinfo->\'preferential\'->\'allocable\'',
            6 => 'feedbackinfo->\'preferential\'->\'allocable\'', // 排序方式相反
            7 => 'feedbackinfo->\'dividend\'->\'allocable\'',
            8 => 'feedbackinfo->\'dividend\'->\'allocable\'' // 排序方式相反
        ];
        $stmt = <<<SQL
            SELECT "id",
                   "account",
                   "therole",
                   to_char(("enrollmentdate" AT TIME ZONE '{$tzonename}'),'YYYY-MM-DD HH24:MI:SS') as "enrollmentdate",
                   "status",
                   "feedbackinfo",
                   "parent_id"
            FROM "root_member"
            WHERE ("parent_id" = '{$query_member_id}')
        SQL;
        $recordsTotal = runSQL($stmt); // return rowCount ("不"包含條件式的所有資料總數)


        if ( isset($_POST['search']['value']) ) {
            $add_stmt = '';

            // 是否為正規表達式
            if ($_POST['search']['regex'] === 'false') {
                $keyword = '%'.filter_var($_POST["search"]["value"], FILTER_SANITIZE_STRING).'%';
                $searchable_column_count = count($searchable_columns);
                $last_round = ($searchable_column_count - 1);

                for ($i=0; $i < $searchable_column_count; $i++) {
                    if ($i == 0) { // 第一圈 (加上左邊大括號)
                        $add_stmt .= <<<SQL
                             AND ( ("{$searchable_columns[$i]}"::text LIKE '{$keyword}')
                        SQL;
                    } else if ($i == $last_round){ // 最後一圈 (加上右邊大括號)
                        $add_stmt .= <<<SQL
                             OR ("{$searchable_columns[$i]}"::text LIKE '{$keyword}') )
                        SQL;
                    } else {
                        $add_stmt .= <<<SQL
                             OR ("{$searchable_columns[$i]}"::text LIKE '{$keyword}')
                        SQL;
                    }
                }
            } else {
                // 待開發
            }
            $stmt .= $add_stmt;

            // 包含條件式的所有資料總數
            $recordsFiltered = runSQL($stmt);
        } else if ( !empty($_POST['query_account']) ) { // 接收從會員管理 點擊 占成比例設定來的會員id，並且只顯示該會員的設定畫面
            $account = filter_var($_POST['query_account'], FILTER_SANITIZE_STRING);
            $add_stmt = <<<SQL
                 AND ("account" = '{$account}')
            SQL;
            $stmt .= $add_stmt;
            $recordsFiltered = runSQL($stmt);
        } else {
            $recordsFiltered = $recordsTotal;
        }

        // order
        if ( !empty($_POST['order']) ) {
            // 因為dataTable只有前3個欄位可以在SQL內排序，所以只有在這範圍內的才可以排序
            $order_column_key = filter_var($_POST['order'][0]['column'], FILTER_SANITIZE_NUMBER_INT);
            $order_dir = filter_var($_POST['order'][0]['dir'], FILTER_SANITIZE_STRING);
            $orderable_columns_count = count($orderable_columns);
            if ( !is_null($order_column_key) && !is_null($order_dir) ) {
                $order_column = $orderable_columns[ $order_column_key ];
                $order_dir = strtoupper($order_dir); // 轉大寫
                if ( ($order_column_key == 6) || ($order_column_key == 8) ) {
                    $order_dir = ( ($order_dir == 'ASC') ? 'DESC' : 'ASC' );
                }

                $stmt .= <<<SQL
                    ORDER BY {$order_column} {$order_dir}
                SQL;
            }
        } else {
            $order_column = $searchable_columns[0];
            $stmt .= <<<SQL
                 ORDER BY "{$order_column}"
            SQL;
        }

        // limit
        if ( !empty($_POST['length']) && isset($_POST['start']) ) {
            $length = filter_var($_POST['length'], FILTER_SANITIZE_NUMBER_INT);
            $start = filter_var($_POST['start'], FILTER_SANITIZE_NUMBER_INT);
            $stmt .= <<<SQL
                 LIMIT {$length} OFFSET {$start}
            SQL;
        } else {
            $stmt .= <<<SQL
                 LIMIT 10 OFFSET 0
            SQL;
        }

        $order_member_datas = runSQLall($stmt);
        unset($order_member_datas[0]);
        $order_member_datas = array_values($order_member_datas); // 因為去除掉key為0的統計欄位，這邊要重整array，不然前端的dataTable會無法顯示資料
        // echo '<pre>', var_dump($order_member_datas), '</pre>'; exit();

        // 把 $order_member_datas 塞進相對應的html標籤
        foreach ($order_member_datas as $key => $value) {

            $order_member_datas[$key]->p_allocable_user = $empty_prefix;
            $order_member_datas[$key]->d_allocable_user = $empty_prefix;
            $order_member_datas[$key]->p_allocable_user_setting = $empty_prefix;
            $order_member_datas[$key]->p_allocable_user_children = $empty_prefix;
            $order_member_datas[$key]->d_allocable_user_setting = $empty_prefix;
            $order_member_datas[$key]->d_allocable_user_children = $empty_prefix;

            $id = $value->id;
            $account = $value->account;
            $status = $value->status;
            $therole = $value->therole;

            // 直屬下線
            if ($therole == 'A'){ // 代理商
                if ( count(getMemberChild($id)) > 0 ) {
                    $order_member_datas[$key]->id = <<<HTML
                        <a href="agencyarea.php?a={$id}">{$account}</a>
                    HTML;
                } else {
                    $order_member_datas[$key]->id = $account;
                }
            } else { //会员 && 管理员
                $order_member_datas[$key]->id = $account;
            }

            // 帳號身分
            switch ($therole) {
                case 'M':
                    $order_member_datas[$key]->therole = <<<HTML
                        <a href="#" title="{$tr['member']}" onclick="return false;">
                            <span class="glyphicon glyphicon-user"></span>
                        </a>
                    HTML;
                    break;
                case 'A':
                    $order_member_datas[$key]->therole = <<<HTML
                        <a href="#" title="{$tr['agent']}" onclick="return false;">
                            <span class="glyphicon glyphicon-knight"></span>
                        </a>
                    HTML;
                    break;
                case 'R':
                    $order_member_datas[$key]->therole = <<<HTML
                        <a href="#" title="{$tr['management']}" onclick="return false;">
                            <span class="glyphicon glyphicon-king"></span>
                        </a>
                    HTML;
                    break;
                default:
                    $order_member_datas[$key]->therole = <<<HTML
                        <a href="#" title="" onclick="return false;">?</a>
                    HTML;
            }

            // 帳號狀態
            switch ($status) {
                case '1':
                    $order_member_datas[$key]->status = <<<HTML
                        <span class="label label-primary">{$tr['normal']}</span>
                    HTML;
                    break;
                case '2':
                    $order_member_datas[$key]->status = <<<HTML
                        <span class="label label-warning">{$tr['wallet frozen']}</span>
                    HTML;
                    break;
                default:
                    $order_member_datas[$key]->status = <<<HTML
                        <span class="label label-danger">{$tr['disabled']}</span>
                    HTML;
            }

            // 直属保障反水
            $p_allocable_user = intval($p_allocable_user);
            $order_member_datas[$key]->p_allocable_user = <<<HTML
                <span data-toggle="tooltip" title="当{$account}投注时，{$user->account}可抽成之比例">{$p_allocable_user}%</span>
            HTML;

            // 直属保障佣金
            $d_allocable_user = intval($d_allocable_user);
            $order_member_datas[$key]->d_allocable_user = <<<HTML
                <span data-toggle="tooltip" title="当{$account}投注时，{$user->account}可抽成之比例">{$d_allocable_user}%</span>
            HTML;

            // 非直属反水設定
            if($therole == 'A'){
                $preferentialratio_option_state = ( ($status == 1) ? '' : 'disabled' );
                $value->feedbackinfo = json_decode($value->feedbackinfo);
                $p_allocable = ( is_null($value->feedbackinfo->preferential->allocable) ? null : float_to_percent($value->feedbackinfo->preferential->allocable) ); // 可分配額度
                $result = is_valid('preferential', ['u_id'=>$id, 'value'=>$p_allocable]);
                if( ($preferentialratio_option_state=='disabled') || ($adjust_ratio_status=='disabled') ){
                    $order_member_datas[$key]->p_allocable_user_setting = '-';
                }
                else if( !$result && !is_null($p_allocable) ){ // 無法再修改
                    $order_member_datas[$key]->p_allocable_user_setting = $p_allocable.' %';
                }
                else if( is_null($p_allocable) ){
                    // 可分配比例小於3%時，不顯示可調整 (2019-07-13跟安平討論的結果 Redmine #2055)
                    if( ($p_max-$p_min) > 3 ){
                        $order_member_datas[$key]->p_allocable_user_setting = "<a href='#' class='fake_range_setting' data-type='preferential' data-uid='{$id}' data-min='{$p_min}' data-max='{$p_max}' data-allocable='0' data-uaccount='{$account}' data-p_allocable_user='{$p_allocable_user}'>設定比例</a>";
                    }
                    else{
                        $order_member_datas[$key]->p_allocable_user_setting = $p_max-$p_min.' %';
                    }
                }
                else{
                    // 可分配比例小於3%時，不顯示可調整 (2019-07-13跟安平討論的結果 Redmine #2055)
                    if( ($p_max-$p_min) > 3 ){
                        $order_member_datas[$key]->p_allocable_user_setting = "<a href='#' class='fake_range_setting' data-type='preferential' data-uid='{$id}' data-min='{$p_min}' data-max='{$p_max}' data-allocable='{$p_allocable}' data-uaccount='{$account}' data-p_allocable_user='{$p_allocable_user}'>{$p_allocable} %</a>";
                    }
                    else{
                        $order_member_datas[$key]->p_allocable_user_setting = $p_max-$p_min.' %';
                    }

                    // 燈號提示
                    $adjust_range_third = intval( ($p_max-$p_min)/3 );
                    $adjust_range_one_third = $p_min + $adjust_range_third;
                    $adjust_range_two_third = $p_min + ($adjust_range_third * 2);

                    if( $p_allocable >= $adjust_range_two_third ){
                        $order_member_datas[$key]->p_allocable_user_setting .= '<i class="fas fa-circle" style="color: #00ff00"></i>';
                    }
                    else if( ($adjust_range_one_third <= $p_allocable) && ($p_allocable < $adjust_range_two_third) ){
                        $order_member_datas[$key]->p_allocable_user_setting .= '<i class="fas fa-circle" style="color: #FFBB00"></i>';
                    }
                    else{
                        $order_member_datas[$key]->p_allocable_user_setting .= '<i class="fas fa-circle" style="color: #CC0000"></i>';
                    }
                }
            }
            // ---------------------------------------------------------------

            // 非直属反水
            if($therole == 'A'){
                $p_allocable_memberinfo = is_null($value->feedbackinfo->preferential->allocable) ? null : float_to_percent($value->feedbackinfo->preferential->allocable);
                $p_allocable_diff = !is_null($p_allocable_user) && !is_null($p_allocable_memberinfo) ? $p_allocable_user - $p_allocable_memberinfo : 0;
                $order_member_datas[$key]->p_allocable_user_children = "<span data-toggle='tooltip' title='当 {$account} 的下线投注时，{$user->account} 可抽成之比例' data-bind='p_agent_{$id}'> {$p_allocable_diff} %</span>";
            }
            // ---------------------------------------------------------------

            // 非直属佣金設定
            if($therole == 'A'){
                $dividendratio_option_state = $status == 1 ? '' : 'disabled';
                $d_allocable = is_null($value->feedbackinfo->dividend->allocable) ? null : float_to_percent($value->feedbackinfo->dividend->allocable);
                $result = is_valid('dividend', ['u_id'=>$id, 'value'=>$d_allocable]);
                if( ($dividendratio_option_state=='disabled') || ($adjust_ratio_status=='disabled') ){
                    $order_member_datas[$key]->d_allocable_user_setting = '-';
                }
                else if( !$result && !is_null($d_allocable) ){ // 無法再修改
                    $order_member_datas[$key]->d_allocable_user_setting = $d_allocable.' %';
                }
                else if( is_null($d_allocable) ){
                    if( ($d_max-$d_min) > 3 ){
                        $order_member_datas[$key]->d_allocable_user_setting = "<a href='#' class='fake_range_setting' data-type='dividend' data-uid='{$id}' data-min='{$d_min}' data-max='{$d_max}' data-allocable='0' data-uaccount='{$account}' data-p_allocable_user='{$d_allocable_user}'>設定比例</a>";
                    }
                    else{
                        $order_member_datas[$key]->d_allocable_user_setting = $d_allocable.' %';
                    }
                }
                else{
                    if( ($d_max-$d_min) > 3 ){
                        $order_member_datas[$key]->d_allocable_user_setting = "<a href='#' class='fake_range_setting' data-type='dividend' data-uid='{$id}' data-min='{$d_min}' data-max='{$d_max}' data-allocable='{$d_allocable}' data-uaccount='{$account}' data-p_allocable_user='{$d_allocable_user}'>{$d_allocable} %</a>";
                    }
                    else{
                        $order_member_datas[$key]->d_allocable_user_setting = $d_allocable.' %';
                    }

                    $adjust_range_third = intval( ($d_max-$d_min)/3 );
                    $adjust_range_one_third = $d_min + $adjust_range_third;
                    $adjust_range_two_third = $d_min + ($adjust_range_third * 2);

                    // 燈號提示
                    if( $d_allocable >= $adjust_range_two_third ){
                        $order_member_datas[$key]->d_allocable_user_setting .= '<i class="fas fa-circle" style="color: #00ff00"></i>';
                    }
                    else if( ($adjust_range_one_third <= $d_allocable) && ($d_allocable < $adjust_range_two_third) ){
                        $order_member_datas[$key]->d_allocable_user_setting .= '<i class="fas fa-circle" style="color: #FFBB00"></i>';
                    }
                    else{
                        $order_member_datas[$key]->d_allocable_user_setting .= '<i class="fas fa-circle" style="color: #CC0000"></i>';
                    }
                }
            }
            // ---------------------------------------------------------------

            // 非直属佣金
            if($therole == 'A'){
                $d_allocable_memberinfo = is_null($value->feedbackinfo->dividend->allocable) ? null : float_to_percent($value->feedbackinfo->dividend->allocable);
                $d_allocable_diff = !is_null($d_allocable_user) && !is_null($d_allocable_memberinfo) ? $d_allocable_user - $d_allocable_memberinfo : 0;
                $order_member_datas[$key]->d_allocable_user_children = "<span data-toggle='tooltip' title='当 {$account} 的下线投注时，{$user->account} 可抽成之比例' data-bind='d_agent_{$id}'> {$d_allocable_diff} %</span>";
            }
            // ---------------------------------------------------------------

        } //end foreach

        $result = [
            'draw' => (isset($_POST['draw']) ? $_POST['draw'] : 1),
            // 'is_post'=>(isset($_POST) ? $_POST : ''),
            'stmt'=>$stmt,
            'recordsTotal' => $recordsTotal, // "不"包含條件式的所有資料總數
            'recordsFiltered' => $recordsFiltered, // 包含條件式的所有資料總數
            'data' => $order_member_datas
        ];
        echo json_encode($result, JSON_PRETTY_PRINT);
    }

    function is_valid(string $type, $params) {
        $result = ['state' => true, 'description' => '非法测试!', 'params' => NULL];

        $userid = $params['u_id'];
        $target_value_percent = $params['value'];

        // 找出直系一级代理，取得每代间的最小最大值
        $ancestors = MemberTreeNode::getPredecessorList($params['u_id']);
        $ancestors = array_map(function($ancestor) {
          $ancestor->feedbackinfo = getMemberFeedbackinfo($ancestor);
          return $ancestor;
        }, $ancestors);
        // 取得下层
        $r = runSQLALL_prepared("SELECT * FROM root_member WHERE root_member.parent_id = :account_id;", $values = ['account_id' => $userid]);
        $children = [];
        array_walk($r, function($child) use (&$children) {
          $child->feedbackinfo = getMemberFeedbackinfo($child);
          $children[$child->id] = $child;
        });

        $user = $ancestors[0] ?? null;
        $parent_of_user = $ancestors[1] ?? null;
        $_1st_agent_of_user = array_reverse($ancestors)[1] ?? null;

        // 验证目标 id 的父代是否为 session id
        if(empty($user)): $result['description'] = '目标会员不存在。'; $result['state'] = false;
        elseif($user->parent_id != $_SESSION['member']->id): $result['description'] = '操作下层对象错误'; $result['state'] = false;
        elseif($user->status != 1): $result['description'] = '操作下层对象的状态错误，该帐号状态为冻结或停用'; $result['state'] = false;
        else: $result['description'] = '会员 u_id 验证通过，进行下一步验证';
        endif;

        $is_locked = count($children) > 0 or has_spread_linkcode($user->account);
        if ($is_locked) {
          $result['description'] = '代理商已开始发展下线，不允许变动代理线基本设置';
          $result['state'] = false;
        }

        $min_allocable = float_to_percent($_1st_agent_of_user->feedbackinfo->$type->{'1st_agent'}->child_occupied->min ?? null);
        $max_allocable = float_to_percent($_1st_agent_of_user->feedbackinfo->$type->{'1st_agent'}->child_occupied->max ?? null);
        // 验证目标数据的值是否在限定范围内
        // 该对象被调整时，是否造成下一代受影响
        $children_allocable = get_next_allocable($type, $user->id, false);
        $children_flag = [ 'min' => [], 'max' => [] ];
        foreach($children_allocable as $childid => $allocable):
          $children_flag['min'][$childid] = !is_null($allocable) && ($target_value_percent - float_to_percent($allocable)) < $min_allocable;
          $children_flag['max'][$childid] = !is_null($allocable) && ($target_value_percent - float_to_percent($allocable)) > $max_allocable;
        endforeach;
        $result['description'] = $children_flag;

        // 验证自身持有比能否再生成下线
        if($parent_of_user->feedbackinfo->$type->allocable <= $_1st_agent_of_user->feedbackinfo->$type->{'1st_agent'}->last_occupied):
          $result['description'] = '自身持有反水分佣比例不足，不可分配给下线';
          $result['state'] = false;
        endif;

        if($target_value_percent === false):
          $result['description'] = '不允许将 ' . $user->account . ' 的比例还原成未设定';
          $result['state'] = false;
        // 验证上线持有比能否再生成下线，以及是否为合法值
        elseif(float_to_percent($parent_of_user->feedbackinfo->$type->allocable) < $target_value_percent):
          $result['description'] = '上线 '.$parent_of_user->account.' 持有反水占成比例不足，不可分配给下线 ' . $user->account;
          $result['state'] = false;
        elseif((float_to_percent($parent_of_user->feedbackinfo->$type->allocable) - $target_value_percent) > $max_allocable):
            $result['description'] = '操作失败 ， ' . $parent_of_user->account . '最多能保留 ' . float_to_percent($_1st_agent_of_user->feedbackinfo->$type->{'1st_agent'}->child_occupied->max) .' %';
            $result['state'] = false;
        elseif((float_to_percent($parent_of_user->feedbackinfo->$type->allocable) - $target_value_percent) < $min_allocable):
            $result['description'] = '操作失败， ' . $parent_of_user->account . '最少须保留 ' . float_to_percent($_1st_agent_of_user->feedbackinfo->$type->{'1st_agent'}->child_occupied->min) . ' %';
            $result['state'] = false;
        // 该对象被调整时，是否造成下一代受影响
        elseif( array_sum($children_flag['min']) > 0):
          $result['description'] = '操作失败，会导致 ' . $user->account . ' 保留过少，原因为 ' . $user->account . ' 已经设定占成比给会员 '
            . implode(',', array_map(function($id) use ($children) { return $children[$id]->account; }, array_keys(array_filter($children_flag['min']))));
          $result['state'] = false;
        elseif( array_sum($children_flag['max']) > 0):
          $result['description'] = '操作失败，会导致 ' . $user->account . ' 保留过多，原因为 ' . $user->account . ' 已经设定占成比给会员 '
            . implode(',', array_map(function($id) use ($children) { return $children[$id]->account; }, array_keys(array_filter($children_flag['max']))));
          $result['state'] = false;
        endif;

        // $result['description'] = '会员分配比例验证通过';

        $user->feedbackinfo->$type->allocable = ($target_value_percent === false) ? null : $target_value_percent * 0.01;

        // 返回必要的资讯，如目标会员的 feedbackinfo 设定值
        // $result['params'] = ['user' => $user, 'parent_of_user' => $parent_of_user, '_1st_agnet_of_user' => $_1st_agent_of_user];

        return $result['state'];
    } // end is_vaild

    function getMemberChild($query_member_id, $level = 1) {
        global $tzonename;
        $member_sql = "SELECT id, account, therole, to_char((enrollmentdate AT TIME ZONE '$tzonename'),'YYYY-MM-DD HH24:MI:SS') as enrollmentdate, status, feedbackinfo, parent_id FROM root_member WHERE parent_id = :query_member_id ORDER BY id;";
        $childs = runSQLall_prepared($member_sql, ['query_member_id' => $query_member_id]);
        // var_dump($childs);
        return $childs;
    } // end getMemberChild
?>