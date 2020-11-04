<?php
    @session_start();
    // agencyarea_summary_member_datas.php
    /*
    ** 時區資料再從這邊找
    ** https://www.php.net/manual/zh/timezones.america.php
    */
    date_default_timezone_set('Asia/Taipei');

    require_once dirname(__FILE__) . "/config.php";
    require_once dirname(__FILE__) . "/lib_common.php";
    require_once dirname(__FILE__) . "/i18n/language.php"; // 支援多国语系
    require_once dirname(__FILE__) . "/lib_agents_setting.php";
    require_once dirname(__FILE__) . "/lib_member_tree.php";

    // 中繼層
    if( !isset($_SESSION['member']) ){
        die('請先登入後再查詢');
    }
    else if( ($_SESSION['member']->therole!='A') && ($_SESSION['member']->therole!='R') ){
        die('無權限操作');
    }

    if( isset($_POST['search']) && !empty($_POST['search']['value']) ){
        $search = json_decode( $_POST['search']['value'] );

        if( isset($search->account) && !empty($search->account) ){
            $search_account = (string)trim($search->account);
            $memberNode = MemberTreeNode::createNodeBySession();
            if( !$memberNode->isSuccessor($search_account) ){
                die('非法測試');
            }
        }
    }

    $current_date = date("Y-m-d");
    if( isset($search->date) && !empty($search->date) ){
        if( $search->date=='today' ){
            $search_date_from = $current_date;
            $search_date_to = $current_date;
        }
        else if( $search->date=='yesterday' ){
            $yesterday = date("Y-m-d", strtotime('-1 day'));
            $search_date_from = $yesterday;
            $search_date_to = $yesterday;
        }
        else if( $search->date=='week' ){
            $current_week = date("w", strtotime($current_date));
            // 這邊以星期一為開始
            if( $current_week==1 ){
                $search_date_from = $current_date;
                $search_date_to = date("Y-m-d", strtotime('+6 day'));
            }
            else if( $current_week==2 ){
                $search_date_from = date("Y-m-d", strtotime("$current_date-".'1 day'));
                $search_date_to = date("Y-m-d", strtotime("$current_date+".'5 day'));
            }
            else if( $current_week==3 ){
                $search_date_from = date("Y-m-d", strtotime("$current_date-".'2 day'));
                $search_date_to = date("Y-m-d", strtotime("$current_date+".'4 day'));
            }
            else if( $current_week==4 ){
                $search_date_from = date("Y-m-d", strtotime("$current_date-".'3 day'));
                $search_date_to = date("Y-m-d", strtotime("$current_date+".'3 day'));
            }
            else if( $current_week==5 ){
                $search_date_from = date("Y-m-d", strtotime("$current_date-".'4 day'));
                $search_date_to = date("Y-m-d", strtotime("$current_date+".'2 day'));
            }
            else if( $current_week==6 ){
                $search_date_from = date("Y-m-d", strtotime("$current_date-".'5 day'));
                $search_date_to = date("Y-m-d", strtotime("$current_date+".'1 day'));
            }
            else if( $current_week==0 ){
                $search_date_from = date("Y-m-d", strtotime("$current_date-".'6 day'));
                $search_date_to = $current_date;
            }
        }
        else if( $search->date=='month' ){
            $daysInCurrentMonth = date("t", strtotime(date("Y-m")."-1")); // 本月天數
            $search_date_from = (string)date("Y-m").'-01';
            $search_date_to = (string)date("Y-m").'-'.$daysInCurrentMonth;
        }
        else if( $search->date=='lastmonth' ){
            $lastMonth = date("Y-m", strtotime('-1 month'));
            $daysInLastMonth = date("t", strtotime($lastMonth."-1")); // 本月天數
            $search_date_from = (string)$lastMonth.'-01';
            $search_date_to = (string)$lastMonth.'-'.$daysInLastMonth;
        }
        else{ // 預設值-本日
            $search_date_from = $current_date;
            $search_date_to = $current_date;
        }
    }
    else{ // 預設值-本日
        $search_date_from = $current_date;
        $search_date_to = $current_date;
    }

    // 列出指定日期區間的反水資料 (反水報表日期, 帳號, 更新時間, 該帳號總反水金額, 該帳號總反水明細 (內含下級代理商與會員的反水金額與反水比例))
    $preferential_summary_sql = '
        SELECT dailydate,
                member_account,
                updatetime,
                all_favorablerate_amount,
                all_favorablerate_amount_detail
        FROM root_statisticsdailypreferential
        WHERE member_account = :member_account
        AND (dailydate BETWEEN :search_begin_date AND :search_end_date) ';

    $preferential_summary_result = runSQLall_prepared( $preferential_summary_sql, [
        'member_account' => $_SESSION['member']->account,
        'search_begin_date' => $search_date_from,
        'search_end_date' => $search_date_to
    ], '', 0, 'r' );

    $total_self_favorable = (float)0;   // 累計自身反水金額
    $total_level_distribute = (float)0; // 累計下級代理商與會員總反水金額 (不含自身反水)
    $total_favorable = (float)0;        // 總反水金額
    $datas = [];                        // 用來存未處理的資料
    $output_datas = [];                 // 用來存已處理的資料

    if(
        ( (count($preferential_summary_result)==1) && ($preferential_summary_result[0]->all_favorablerate_amount_detail!=NULL) && ($preferential_summary_result[0]->all_favorablerate_amount!=NULL) ) ||
        ( count($preferential_summary_result)>1 )
      ){
        // 這邊查詢結果可能一筆以上，故一定要用foreach處理
        foreach( $preferential_summary_result as $key_outer=>$val_outer ){
            // 這些母資料要放進子資料內
            $dailydate = date("Y/m/d", strtotime($val_outer->dailydate));        // 反水記錄日期
            $updatetime = date("Y/m/d H:i", strtotime($val_outer->updatetime));  // 反水紀錄更新日期
            // $all_favorablerate_amount = $val_outer->all_favorablerate_amount; // 該會員當天反水總金額 (後面加總計算)

            // 子資料
            $val_outer->all_favorablerate_amount_detail = json_decode($val_outer->all_favorablerate_amount_detail);

            $total_self_favorable += (float)$val_outer->all_favorablerate_amount_detail->self_favorable; // 累計加總自身反水金額
            $level_distribute = $val_outer->all_favorablerate_amount_detail->level_distribute; // 下級代理商與會員反水明細


            foreach($level_distribute as $val_inner){
                // 有特別指定要查詢哪位使用者
                if( isset($search_account) && !empty($search_account) ){
                    // 符合該使用者的資料才會被插入
                    if( $val_inner->from_account==$search_account ){
                        $insert_member_datas = [
                            'dailydate'=>$dailydate,
                            'account'=>$val_inner->from_account,
                            'base_favorable'=>(float)$val_inner->base_favorable,
                            'from_favorable_rate'=>$val_inner->from_favorable_rate,
                            'from_favorable'=>(float)$val_inner->from_favorable,
                            'updatetime'=>$updatetime
                        ];
                        array_push($datas, $insert_member_datas);
                    }
                }
                // 沒有特別指定要查詢哪位使用者
                else{
                    // 插入所有使用者的資料
                    $insert_member_datas = [
                        'dailydate'=>$dailydate,
                        'account'=>$val_inner->from_account,
                        'base_favorable'=>(float)$val_inner->base_favorable,
                        'from_favorable_rate'=>$val_inner->from_favorable_rate,
                        'from_favorable'=>(float)$val_inner->from_favorable,
                        'updatetime'=>$updatetime
                    ];
                    array_push($datas, $insert_member_datas);
                }

                // P.S.特別說明：累計反水(自身、下級代理商與會員)不因有沒有限定查詢會員而有所不一樣
                $total_level_distribute += (float)$val_inner->from_favorable; // 下級代理商與會員反水累計
            } // end inner foreach
        } // end outer foreach

        // sql order by
        $data_items = ['dailydate', 'account', 'base_favorable', 'from_favorable_rate', 'from_favorable', 'updatetime']; // 前台欄位顯示
        function sortArrByField( $datas, $field, $desc=false ){
            $fieldArr = [];
            foreach($datas as $key=>$val){
                $fieldArr[$key] = $val[$field];
            } // end foreach
            $sort = ( ($desc==false) ? SORT_ASC : SORT_DESC );
            array_multisort( $fieldArr, $sort, $datas );
            return $datas;
            // echo '<pre>', var_dump($datas), '</pre>';  exit();
        } // end sortArrByField

        if( isset($_POST['order']) ){
            $datas = sortArrByField( $datas, $data_items[$_POST['order'][0]['column']], ( ($_POST['order'][0]['dir']=='desc') ? false : true ) );
        }

        // sql limit
        $start = (int)0;
        $length = (int)10;
        if( isset($_POST['start']) && isset($_POST['length']) ){
            $start = (int)$_POST['start'];
            $length = (int)$_POST['length'];
        }

        // 數值資料加上千分位符號、比例資料轉換成百分比格式，並推入已處理的輸出陣列
        foreach( $datas as $key=>$val ){
            if( ($key==$start) || ( ($start<$key) && ( $key<($start+$length) ) ) ){
                $val['base_favorable'] = '$'.@number_format( (float)$val['base_favorable'], 2, '.', ',' );
                $val['from_favorable'] = '$'.@number_format( (float)$val['from_favorable'], 2, '.', ',' );
                $val['from_favorable_rate'] = ($val['from_favorable_rate']*100).'%';
                array_push($output_datas, $val);
            }
        } // end foreach

        // 整理"總計"金額 (加上千分號與取小數點到第二位)
        $total_favorable = @number_format( ($total_self_favorable+$total_level_distribute), 2, '.', ',' ); // 總反水金額
        $total_level_distribute = @number_format($total_level_distribute, 2, '.', ',');                    // 下級代理商與會員總反水金額
        $total_self_favorable = @number_format($total_self_favorable, 2, '.', ',');                        // 自身總反水金額
    }

    //
    $result = [
        'draw'=>(isset($_POST['draw']) ? $_POST['draw'] : 1),
        'is_post'=>(isset($_POST) ? $_POST : ''),
        'sql'=>$preferential_summary_sql,
        'recordsTotal'=>count( $datas ), // "不"包含條件式的所有資料總數
        'recordsFiltered'=>count( $output_datas ), // 包含條件式的所有資料總數
        'data'=>$output_datas,
        'search_begin_date' => $search_date_from,
        'search_end_date' => $search_date_to,
        'total_favorable'=>'$'.$total_favorable,
        'total_level_distribute'=>'$'.$total_level_distribute,
        'total_self_favorable'=>'$'.$total_self_favorable
    ];
    echo json_encode($result, JSON_PRETTY_PRINT);
?>