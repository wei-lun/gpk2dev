<?php
// report-agencyarea_summary.php
date_default_timezone_set('Asia/Taipei');
require_once('in/PHP_Excel/PHPExcel.php');
require_once('in/PHP_Excel/PHPExcel/IOFactory.php');

$PHPExcel = new PHPExcel();

//相當於$A=array("A","B","C",.............,"Z");
for( $i=65; $i<=90; $i++ ){
	$A[] = chr($i);
} // end for

//指定目前要編輯的工作表 ，預設0是指第一個工作表
$PHPExcel->setActiveSheetIndex(0);
$sheet = $PHPExcel->getActiveSheet();

//設定欄寬
$sheet->getColumnDimension('A')->setWidth(15);
$sheet->getColumnDimension('B')->setWidth(14);
$sheet->getColumnDimension('C')->setWidth(15);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(15);
$sheet->getColumnDimension('F')->setWidth(17);

//合併儲存格
$sheet->mergeCells('A1:F1');
$sheet->setCellValue('A1','反水佣金明细');
$sheet->getStyle('A1')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$sheet->getRowDimension('1')->setRowHeight(20);

$sheet->setCellValue('A2', '製表日期：');
$sheet->setCellValue('B2', date("Y/m/d"));
$sheet->getStyle('B2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$sheet->getRowDimension('2')->setRowHeight(18);

$sheet->setCellValue('A3', '代理反水：');
$sheet->setCellValue('B3', $total_level_distribute);
$sheet->getStyle('B3')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$sheet->getRowDimension('3')->setRowHeight(18);

$sheet->setCellValue('A4', '自身反水：');
$sheet->setCellValue('B4', $total_self_favorable);
$sheet->getStyle('B4')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$sheet->getRowDimension('4')->setRowHeight(18);

$sheet->setCellValue('A5', '总反水：');
$sheet->setCellValue('B5', $total_favorable);
$sheet->getStyle('B5')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$sheet->getRowDimension('5')->setRowHeight(18);

// 輸出列表表頭
$titles = ['时间', '来源帐号', '反水基数', '代理分配比例', '反水金额', '更新时间及说明'];
$sheet->getRowDimension('7')->setRowHeight(18);
for( $i=0; $i<count($A); $i++ ){
	if( $i<count($titles) ){
        $sheet->setCellValue( $A[$i].'7', $titles[$i] );
        $sheet->getStyle($A[$i].'7')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    }
    else{
        break;
    }
} // end for

// 輸出列表內容
$contents = ['dailydate', 'account', 'base_favorable', 'from_favorable_rate', 'from_favorable', 'updatetime'];
foreach( $output_datas as $key=>$val ){
    $sheet->getRowDimension($key+8)->setRowHeight(18);
    for( $i=0; $i<count($A); $i++ ){
        if( $i<count($val) ){
            $sheet->setCellValue($A[$i].(string)($key+8), $val[$contents[$i]]);
        }
        else{
            // break;
        }
    } // end for
} // end foreach

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="agencyarea_summary.xlsx"');
header('Cache-Control: max-age=0');
$PHPExcelWriter = PHPExcel_IOFactory::createWriter($PHPExcel, 'Excel5');
$PHPExcelWriter->save('php://output');
exit();
?>