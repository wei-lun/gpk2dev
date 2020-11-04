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

    // php spreadsheet
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

    if( !isset($_SESSION['member']) ){
        die('請先登入後再查詢');
    } else if ( ($_SESSION['member']->therole!='A') && ($_SESSION['member']->therole != 'R') ) {
        die('無權限操作');
    }

    if( !empty($_POST['search']['value']) ){
        $search = json_decode( $_POST['search']['value'] );
        $_SESSION['search'] = $_POST['search'];
    } else if ( !empty($_SESSION['search']['value']) ){
        $search = json_decode( $_SESSION['search']['value'] );
    }

    if( !empty($search->account) ){
        $search_account = trim($search->account);
        $memberNode = MemberTreeNode::createNodeBySession();
        if( !$memberNode->isSuccessor($search_account) ){
            die('非法測試');
        }
        $_SESSION['search_account'] = $search->account;
    } else{
        $_SESSION['search_account'] = '';
    }

    $current_date = date("Y-m-d");
    $_SESSION['search_date'] = 'today';
    $search_date_from = $current_date;
    $search_date_to = $current_date;
    if ( !empty($search->date) ) {
        $_SESSION['search_date'] = $search->date;
        switch ($search->date) {
            case 'today':
                $search_date_from = $current_date;
                $search_date_to = $current_date;
                break;
            case 'yesterday':
                $yesterday = date("Y-m-d", strtotime('-1 day'));
                $search_date_from = $yesterday;
                $search_date_to = $yesterday;
                break;
            case 'week':
                $current_week = date("w", strtotime($current_date));
                switch ($current_week) {
                    case 0:
                        $search_date_from = $current_date;
                        $search_date_to = date("Y-m-d", strtotime('+6 day'));
                        break;
                    case 1:
                        $search_date_from = date("Y-m-d", strtotime("{$current_date}-1 day"));
                        $search_date_to = date("Y-m-d", strtotime("{$current_date}+5 day"));
                        break;
                    case 2:
                        $search_date_from = date("Y-m-d", strtotime("{$current_date}-2 day"));
                        $search_date_to = date("Y-m-d", strtotime("{$current_date}+4 day"));
                        break;
                    case 3:
                        $search_date_from = date("Y-m-d", strtotime("{$current_date}-3 day"));
                        $search_date_to = date("Y-m-d", strtotime("{$current_date}+3 day"));
                        break;
                    case 4:
                        $search_date_from = date("Y-m-d", strtotime("{$current_date}-4 day"));
                        $search_date_to = date("Y-m-d", strtotime("{$current_date}+2 day"));
                        break;
                    case 5:
                        $search_date_from = date("Y-m-d", strtotime("{$current_date}-5 day"));
                        $search_date_to = date("Y-m-d", strtotime("{$current_date}+1 day"));
                        break;
                    case 6:
                        $search_date_from = date("Y-m-d", strtotime("{$current_date}-6 day"));
                        $search_date_to = $current_date;
                        break;
                }
                break;
            case 'month':
                $daysInCurrentMonth = date("t", strtotime(date("Ym")."-1")); // 本月天數
                $search_date_from = date("Y-m").'-01'; // Y-m
                $search_date_to = date("Y-m")."-{$daysInCurrentMonth}";
                break;
            case 'lastmonth':
                $lastMonth = date("Y-m", strtotime('-1 month'));
                $daysInLastMonth = date("t", strtotime("{$lastMonth}-1")); // 上個月天數
                $search_date_from = "{$lastMonth}-01";
                $search_date_to = "{$lastMonth}-{$daysInLastMonth}";
                break;
            default:
                $_SESSION['search_date'] = 'today';
                $search_date_from = $current_date;
                $search_date_to = $current_date;
        }
    }


    // 列出指定日期區間的反水資料 (反水報表日期, 帳號, 更新時間, 該帳號總反水金額, 該帳號總反水明細 (內含下級代理商與會員的反水金額與反水比例))
    $preferential_summary_sql = <<<SQL
        SELECT "dailydate",
               "member_account",
               "updatetime",
               "all_favorablerate_amount",
               "all_favorablerate_amount_detail"
        FROM "root_statisticsdailypreferential"
        WHERE "member_account" = :member_account
            AND ("dailydate" BETWEEN :search_begin_date AND :search_end_date)
    SQL;

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
    $datas_total = [];                  // 用來存未處理的資料

    if(
        ( (count($preferential_summary_result)==1) && ($preferential_summary_result[0]->all_favorablerate_amount_detail!=NULL) && ($preferential_summary_result[0]->all_favorablerate_amount!=NULL) ) ||
        ( count($preferential_summary_result)>1 )
      ){
        // 這邊查詢結果可能一筆以上，故一定要用foreach處理
        foreach( $preferential_summary_result as $key_outer=>$val_outer ){
            // 這些母資料要放進子資料內
            // 原版
            // $dailydate = date("Y/m/d", strtotime($val_outer->dailydate));        // 反水記錄日期
            // $updatetime = date("Y/m/d H:i", strtotime($val_outer->updatetime));  // 反水紀錄更新日期

            // -------------------------------------------------
            // 2019-12-16
            $dailydate = gmdate("Y/m/d",strtotime($val_outer->dailydate) + -4*3600); // 反水記錄日期
            $updatetime = gmdate("Y/m/d H:i",strtotime($val_outer->updatetime) + -4*3600); // 反水紀錄更新日期
            // --------------------------------------------------

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
                        array_push($datas_total, $insert_member_datas);
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
        } // end sortArrByField

        if( isset($_POST['order']) ){
            $_SESSION['order'] = $_POST['order'];
            $datas = sortArrByField( $datas, $data_items[$_POST['order'][0]['column']], ( ($_POST['order'][0]['dir']=='desc') ? false : true ) );
        }
        else if( isset($_SESSION['order']) ){
            $datas = sortArrByField( $datas, $data_items[$_SESSION['order'][0]['column']], ( ($_SESSION['order'][0]['dir']=='desc') ? false : true ) );
        }

        // 這邊判斷僅輸出檢視資料 或 輸出EXCEL
        if( isset($_GET['output']) && ($_GET['output']==true) ){ // 這邊$output_datas資料量是全部
            // 數值資料加上千分位符號、比例資料轉換成百分比格式，並推入已處理的輸出陣列
            foreach( $datas as $key=>$val ){
                $val['base_favorable'] = '$'.@number_format( (float)$val['base_favorable'], 2, '.', ',' );
                $val['from_favorable'] = '$'.@number_format( (float)$val['from_favorable'], 2, '.', ',' );
                $val['from_favorable_rate'] = ($val['from_favorable_rate']*100).'%';
                array_push($output_datas, $val);
            } // end foreach

            // 整理"總計"金額 (加上千分號與取小數點到第二位)
            $total_favorable = '$'.@number_format( ($total_self_favorable+$total_level_distribute), 2, '.', ',' ); // 總反水金額
            $total_level_distribute = '$'.@number_format($total_level_distribute, 2, '.', ',');                    // 下級代理商與會員總反水金額
            $total_self_favorable = '$'.@number_format($total_self_favorable, 2, '.', ',');                        // 自身總反水金額

            // 原版
            // include_once('in/PHP_Excel/report-agencyarea_summary.php');
            // exit();

            // -------------------------------------------------------------------------
            // 2019/12/17
            // 清除快取以防亂碼
            ob_end_clean();

            //---------------phpspreadsheet----------------------------
            $spreadsheet = new Spreadsheet();

            // Create a new worksheet called "My Data"
            $myWorkSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, '反水佣金明细');

            // Attach the "My Data" worksheet as the first worksheet in the Spreadsheet object
            $spreadsheet->addSheet($myWorkSheet, 0);

            // 總表索引標籤開始寫入資料
            $sheet = $spreadsheet->setActiveSheetIndex(0);

            // 寫入總表資料陣列
            // $sheet->fromArray($xls_agency_summary, null, 'A7');


            $worksheet = $spreadsheet->getActiveSheet()->mergeCells('A1:F1'); // 合併儲存格
            $worksheet = $spreadsheet->getActiveSheet()->setCellValue('A1', '反水佣金明细');
            $worksheet = $spreadsheet->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // 製表日期
            $worksheet = $spreadsheet->getActiveSheet()->setCellValue('A2', '制表日期：');
            $worksheet = $spreadsheet->getActiveSheet()->setCellValue('B2', date("Y/m/d"));
            $worksheet = $spreadsheet->getActiveSheet()->getStyle('B2')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_YYYYMMDDSLASH);
            $worksheet = $spreadsheet->getActiveSheet()->getStyle('B2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

            // 代理反水
            $worksheet = $spreadsheet->getActiveSheet()->setCellValue('A3', '代理反水：');
            $worksheet = $spreadsheet->getActiveSheet()->setCellValue('B3', $total_level_distribute);
            $worksheet = $spreadsheet->getActiveSheet()->getStyle('B3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

            // 自身反水
            $worksheet = $spreadsheet->getActiveSheet()->setCellValue('A4', '自身反水：');
            $worksheet = $spreadsheet->getActiveSheet()->setCellValue('B4', $total_self_favorable);
            $worksheet = $spreadsheet->getActiveSheet()->getStyle('B4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

            // 總反水
            $worksheet = $spreadsheet->getActiveSheet()->setCellValue('A5', '总反水：');
            $worksheet = $spreadsheet->getActiveSheet()->setCellValue('B5', $total_favorable);
            $worksheet = $spreadsheet->getActiveSheet()->getStyle('B5')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

            // 自動欄寬
            $worksheet = $spreadsheet->getActiveSheet();

            foreach (range('A', $worksheet->getHighestColumn()) as $column) {
                $spreadsheet->getActiveSheet()->getColumnDimension($column)->setAutoSize(true);
            }

            if($_SESSION['search_account'] == ''){
                // 檔名
                // 未選擇特定下線(顯示自己的帳號)
                $output_filename = 'agencyarea_summary_'.$search_date_from.'_'.$search_date_to.'_'.$_SESSION['member']->account;

            }else{
                // 選擇特定下線
                $output_filename = 'agencyarea_summary_'.$search_date_from.'_'.$search_date_to.'_'.$_SESSION['search_account'];
            }

            //相當於$A=array("A","B","C",.............,"Z");
            for( $i=65; $i<=90; $i++ ){
                $A[] = chr($i);
            };

            // 輸出列表表頭
            $col_title = ['时间', '来源帐号', '反水基数', '代理分配比例', '反水金额', '更新时间及说明'];
            $sheet->getRowDimension('7')->setRowHeight(18);
            for($i = 0; $i < count($A); $i++){
                if( $i <count($col_title) ){
                    $sheet->setCellValue( $A[$i].'7', $col_title[$i] );
                    $sheet->getStyle($A[$i].'7')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                }
                else{
                    break;
                }
            }

            // 輸出列表內容
            $contents = ['dailydate', 'account', 'base_favorable', 'from_favorable_rate', 'from_favorable', 'updatetime'];
            foreach($output_datas as $key => $value){
                $sheet->getRowDimension($key+8)->setRowHeight(18);
                for($i = 0 ; $i <= count($A); $i++){
                    if($i < count($value)){
                        $sheet->setCellValue($A[$i].(string)($key+8), $value[$contents[$i]]);
                    }else{

                    }
                }
            }

            // xlsx
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $output_filename . '.xlsx"');
            header('Cache-Control: max-age=0');

            // 直接匯出，不存於disk
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');

            // -----------------------------------------------------------------------------

        }
        else{ // 這邊$output_datas資料量是部份
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
    }

    if( isset($_GET['output']) && ($_GET['output']==true) ){
        $error_message = <<<HTML
        <script>
            alert('查無資料，無法輸出報表');
            // history.back();
            window.close();
        </script>
HTML;
        echo $error_message;
    }
    else{
        $result = [
            'draw'=>(isset($_POST['draw']) ? $_POST['draw'] : 1),
            'is_post'=>(isset($_POST) ? $_POST : ''),
            'recordsTotal'=>count( $datas ), // "不"包含條件式的所有資料總數
            'recordsFiltered'=>count( $datas ), // 包含條件式的所有資料總數
            'data'=>$output_datas,
            'search_begin_date' => $search_date_from,
            'search_end_date' => $search_date_to,
            'total_favorable'=>'$'.$total_favorable,
            'total_level_distribute'=>'$'.$total_level_distribute,
            'total_self_favorable'=>'$'.$total_self_favorable
        ];
        echo json_encode($result, JSON_PRETTY_PRINT);
    }
?>