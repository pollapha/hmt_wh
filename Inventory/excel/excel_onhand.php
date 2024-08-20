<?php
include('../vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


global $date;
global $time;

$date = date('d-m-Y');
$time = date('H:i');

function applyFont($worksheet, $range, $fontSize = 10, $color = 0)
{
    $styleArray = [
        'font' => [
            'bold' => true,
            'size' => $fontSize,
        ],
    ];

    if ($color === 1) {
        $styleArray['font']['color'] = ['rgb' => '0000FF'];
    }

    $worksheet->getStyle($range)->applyFromArray($styleArray);
}

function applyBorders($worksheet, $range, $borderCode, $inside = 1)
{
    $borderDefinitions = [
        'thin' => Border::BORDER_THIN,
        'thick' => Border::BORDER_THICK,
        'double' => Border::BORDER_DOUBLE,
    ];

    $borderStyle = [];
    for ($i = 0; $i < strlen($borderCode); $i += 2) {
        $char = $borderCode[$i];
        $styleCode = $borderCode[$i + 1];
        $borderStyleKey = '';
        if ($char === 'T') {
            $borderStyleKey = 'top';
        } elseif ($char === 'B') {
            $borderStyleKey = 'bottom';
        } elseif ($char === 'L') {
            $borderStyleKey = 'left';
        } elseif ($char === 'R') {
            $borderStyleKey = 'right';
        }

        if ($styleCode === '0') {
            $borderStyle['borders'][$borderStyleKey]['borderStyle'] = $borderDefinitions['thick'];
        } elseif ($styleCode === '2') {
            $borderStyle['borders'][$borderStyleKey]['borderStyle'] = $borderDefinitions['double'];
        } else {
            $borderStyle['borders'][$borderStyleKey]['borderStyle'] = $borderDefinitions['thin'];
        }
    }

    if ($inside === 1) {
        $borderStyle['borders']['inside'] = [
            'borderStyle' => Border::BORDER_THIN,
        ];
    }

    $worksheet->getStyle($range)->applyFromArray($borderStyle);
}

function setDefaultStylesSheet($worksheet)
{
    $styles = [
        'A' => ['width' => 10.00], 'B' => ['width' => 15.00], 'C' => ['width' => 20.00], 'D' => ['width' => 20.00], 'E' => ['width' => 25.00],
        'F' => ['width' => 45.00], 'G' => ['width' => 20.00], 'H' => ['width' => 25.00], 'I' => ['width' => 20.00], 'J' => ['width' => 15.00],
        'K' => ['width' => 15.00], 'L' => ['width' => 20.00], 'M' => ['width' => 15.00], 'N' => ['width' => 20.00], 'O' => ['width' => 15.00],
    ];
    $worksheet->getParent()->getDefaultStyle()->getFont()->setName('Calibri');
    $worksheet->getParent()->getDefaultStyle()->getFont()->setSize(9);
    $worksheet->getParent()->getDefaultStyle()->getAlignment()->setVertical('Center');
    $worksheet->getParent()->getDefaultStyle()->getAlignment()->setWrapText(true);
    //$worksheet->getParent()->getDefaultStyle()->getAlignment()->setHorizontal('Center');

    $worksheet->getRowDimension('4')->setRowHeight(3, 'pt');
    foreach ($styles as $col => $style) {
        $worksheet->getColumnDimension($col)->setWidth($style['width']);
    }
}

function addDetailTableSheet($worksheet, $data, $row, $col, $border = 1)
{
    $row += 1;
    if (empty($data)) {
        return $row;
    }

    foreach ($data as $rowData) {
        // var_dump($rowData);
        for ($i = 0; $i < count($col); $i++) {
            // echo($rowData[$i]);
            $worksheet->setCellValue($col[$i] . $row, $rowData[$i]);
        }
        $row++;
    }
    return $row;
}

function addHeaderData($worksheet, $date, $time)
{
    $cellData = [
        //header table
        'A1' => ['value' => 'Onhand (By item)', 'alignment' => 'center',],
        'A2' => ['value' => 'Date : ', 'alignment' => 'center',],
        'A3' => ['value' => 'Time : ', 'alignment' => 'center',],
        'B2' => ['value' => $date, 'alignment' => 'center',],
        'B3' => ['value' => $time, 'alignment' => 'center',],

        'A5' => ['value' => 'No.', 'alignment' => 'center',],
        'B5' => ['value' => 'Destination', 'alignment' => 'center',],
        'C5' => ['value' => 'Receive Date', 'alignment' => 'center',],
        'D5' => ['value' => 'Invoice No.', 'alignment' => 'center',],
        'E5' => ['value' => 'Part No.', 'alignment' => 'center',],
        'F5' => ['value' => 'Part Description', 'alignment' => 'center',],
        'G5' => ['value' => 'Pallet No.', 'alignment' => 'center',],
        'H5' => ['value' => 'Certificate No.', 'alignment' => 'center',],
        'I5' => ['value' => 'Case Tag No.', 'alignment' => 'center',],
        'J5' => ['value' => 'Net Weight (Kg.)', 'alignment' => 'center',],
        'K5' => ['value' => 'Qty (pcs)', 'alignment' => 'center',],
        'L5' => ['value' => 'FG Tag No.', 'alignment' => 'center',],
        'M5' => ['value' => 'Coil Direction Process', 'alignment' => 'center',],
        'N5' => ['value' => 'Location', 'alignment' => 'center',],
        'O5' => ['value' => 'Area', 'alignment' => 'center',],
    ];

    $worksheet->getStyle('A1')
        ->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('C4D79B');

    $worksheet->getStyle('A5:O5')
        ->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('def0fc');


    $worksheet->getStyle("J")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
    $worksheet->getStyle("K")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);

    $worksheet->mergeCells('A1:B1');

    foreach ($cellData as $cell => $data) {
        $worksheet->setCellValue($cell, $data['value']);
        $cellStyle = $worksheet->getStyle($cell);
        $cellStyle->getAlignment()->setHorizontal($data['alignment']);
        $cellStyle->getAlignment()->setVertical('center');
    }

    $borderCode = 'L1R1T1B1';
    applyBorders($worksheet, 'A1:B3', $borderCode);
}

function summary_data($worksheet, $row, $data)
{
    $col = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J',
        'K', 'L', 'M', 'N', 'O'
    ];
    $dataStartRow = $row + 1;
    $lastRow = addDetailTableSheet($worksheet, $data, $row, $col);
    $row = $lastRow;
    $borderCode = 'L1R1T1B1';
    $row_border = $row - 1;
    applyBorders($worksheet, 'A5:O' . $row_border, $borderCode);
    $worksheet->getStyle('A5:O' . $row_border, $borderCode)->getAlignment()->setVertical('center');
    $worksheet->getStyle('A5:O' . $row_border, $borderCode)->getAlignment()->setHorizontal('center');

    $row_sum =  $row_border + 1;
    $worksheet->setCellValue('I' . $row_sum, 'Total : ');
    $worksheet->setCellValue('J' . $row_sum, '=SUM(J6:J' . $row_border . ')');
    $worksheet->setCellValue('K' . $row_sum, '=SUM(K6:K' . $row_border . ')');


    $worksheet->getStyle('I' . $row_sum, $borderCode)->getAlignment()->setHorizontal('right');
    applyBorders($worksheet, 'I' . $row_sum . ':K' . $row_sum, $borderCode);
    $worksheet->getStyle('J' . $row_sum . ':K' . $row_sum, $borderCode)->getAlignment()->setVertical('center');
    $worksheet->getStyle('J' . $row_sum . ':K' . $row_sum, $borderCode)->getAlignment()->setHorizontal('center');

    $worksheet->getStyle('I' . $row_sum . ':K' . $row_sum)
        ->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('EEECE1');

    return $row;
}



function setDefaultStylesSheet2($worksheet)
{
    $styles = [
        'A' => ['width' => 10.00], 'B' => ['width' => 15.00], 'C' => ['width' => 25.00], 'D' => ['width' => 50.00], 'E' => ['width' => 15.00],
        'F' => ['width' => 15.00],
    ];
    $worksheet->getParent()->getDefaultStyle()->getFont()->setName('Calibri');
    $worksheet->getParent()->getDefaultStyle()->getFont()->setSize(9);
    $worksheet->getParent()->getDefaultStyle()->getAlignment()->setVertical('Center');
    $worksheet->getParent()->getDefaultStyle()->getAlignment()->setWrapText(true);
    //$worksheet->getParent()->getDefaultStyle()->getAlignment()->setHorizontal('Center');

    $worksheet->getRowDimension('4')->setRowHeight(3, 'pt');
    foreach ($styles as $col => $style) {
        $worksheet->getColumnDimension($col)->setWidth($style['width']);
    }
}


function addHeaderData2($worksheet, $date, $time)
{
    $cellData = [
        //header table
        'A1' => ['value' => 'Onhand (By item)', 'alignment' => 'center',],
        'A2' => ['value' => 'Date : ', 'alignment' => 'center',],
        'A3' => ['value' => 'Time : ', 'alignment' => 'center',],
        'B2' => ['value' => $date, 'alignment' => 'center',],
        'B3' => ['value' => $time, 'alignment' => 'center',],

        'A5' => ['value' => 'No.', 'alignment' => 'center',],
        'B5' => ['value' => 'Destination', 'alignment' => 'center',],
        'C5' => ['value' => 'Part No.', 'alignment' => 'center',],
        'D5' => ['value' => 'Part Description', 'alignment' => 'center',],
        'E5' => ['value' => 'Net Weight (Kg.)', 'alignment' => 'center',],
        'F5' => ['value' => 'Qty (pcs)', 'alignment' => 'center',],
    ];

    $worksheet->getStyle('A1')
        ->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('C4D79B');


    $worksheet->getStyle('A5:F5')
        ->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('def0fc');


    $worksheet->getStyle("E")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
    $worksheet->getStyle("F")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);

    $worksheet->mergeCells('A1:B1');

    foreach ($cellData as $cell => $data) {
        $worksheet->setCellValue($cell, $data['value']);
        $cellStyle = $worksheet->getStyle($cell);
        $cellStyle->getAlignment()->setHorizontal($data['alignment']);
        $cellStyle->getAlignment()->setVertical('center');
    }

    $borderCode = 'L1R1T1B1';
    applyBorders($worksheet, 'A1:B3', $borderCode);
}

function summary_data2($worksheet, $row, $data)
{
    $col = [
        'A', 'B', 'C', 'D', 'E', 'F'
    ];
    $dataStartRow = $row + 1;
    $lastRow = addDetailTableSheet($worksheet, $data, $row, $col);
    $row = $lastRow;
    $borderCode = 'L1R1T1B1';
    $row_border = $row - 1;
    applyBorders($worksheet, 'A5:F' . $row_border, $borderCode);
    $worksheet->getStyle('A5:F' . $row_border, $borderCode)->getAlignment()->setVertical('center');
    $worksheet->getStyle('A5:F' . $row_border, $borderCode)->getAlignment()->setHorizontal('center');

    $row_sum =  $row_border + 1;
    $worksheet->setCellValue('D' . $row_sum, 'Total : ');
    $worksheet->setCellValue('E' . $row_sum, '=SUM(E6:E' . $row_border . ')');
    $worksheet->setCellValue('F' . $row_sum, '=SUM(F6:F' . $row_border . ')');

    $worksheet->getStyle('D' . $row_sum, $borderCode)->getAlignment()->setHorizontal('right');
    applyBorders($worksheet, 'D' . $row_sum . ':F' . $row_sum, $borderCode);
    $worksheet->getStyle('E' . $row_sum . ':F' . $row_sum, $borderCode)->getAlignment()->setVertical('center');
    $worksheet->getStyle('E' . $row_sum . ':F' . $row_sum, $borderCode)->getAlignment()->setHorizontal('center');

    $worksheet->getStyle('D' . $row_sum . ':F' . $row_sum)
        ->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('EEECE1');

    return $row;
}


$spreadsheet = new Spreadsheet();

$worksheet = $spreadsheet->getActiveSheet();
$worksheet->setShowGridlines(false);
$worksheet->setTitle('Onhand (By item)');
setDefaultStylesSheet($worksheet);
addHeaderData($worksheet, $date, $time);
$row = 5;
summary_data($worksheet, $row, $dataByItemArray);

$worksheet2 = new Worksheet($spreadsheet, 'Onhand (By part)');
$spreadsheet->addSheet($worksheet2, 2);
$worksheet2->setShowGridlines(false);
setDefaultStylesSheet2($worksheet2);
addHeaderData2($worksheet2, $date, $time);
$row = 5;
summary_data2($worksheet2, $row, $dataByPartArray);

$date = date('Y-m-d His');

$filename = 'excel/fileoutput/Onhand_' . $date . '.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($filename);
