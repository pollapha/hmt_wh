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

date_default_timezone_set("Asia/Bangkok");
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

function setDefaultStylesSheetShipout($worksheet)
{
    $styles = [
        'A' => ['width' => 10.00], 'B' => ['width' => 15.00], 'C' => ['width' => 15.00], 'D' => ['width' => 20.00], 'E' => ['width' => 15.00],
        'F' => ['width' => 15.00], 'G' => ['width' => 25.00], 'H' => ['width' => 20.00], 'I' => ['width' => 20.00], 'J' => ['width' => 20.00],
        'K' => ['width' => 20.00], 'L' => ['width' => 20.00], 'M' => ['width' => 40.00], 'N' => ['width' => 50.00], 'O' => ['width' => 15.00],
        'P' => ['width' => 15.00],
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


function addHeaderDataShipout($worksheet, $date, $time)
{
    $cellData = [
        //header table
        'A1' => ['value' => 'Ship Out', 'alignment' => 'center',],
        'A2' => ['value' => 'Date : ', 'alignment' => 'center',],
        'A3' => ['value' => 'Time : ', 'alignment' => 'center',],
        'B2' => ['value' => $date, 'alignment' => 'center',],
        'B3' => ['value' => $time, 'alignment' => 'center',],

        'A5' => ['value' => 'No.', 'alignment' => 'center',],
        'B5' => ['value' => 'Document No.', 'alignment' => 'center',],
        'C5' => ['value' => 'Document Date', 'alignment' => 'center',],
        'D5' => ['value' => 'Invoice No.', 'alignment' => 'center',],
        'E5' => ['value' => 'Destination', 'alignment' => 'center',],
        'F5' => ['value' => 'Delivery Date', 'alignment' => 'center',],
        'G5' => ['value' => 'Order No.', 'alignment' => 'center',],
        'H5' => ['value' => 'Dos No.', 'alignment' => 'center',],
        'I5' => ['value' => 'Pallet No.', 'alignment' => 'center',],
        'J5' => ['value' => 'Certificate No.', 'alignment' => 'center',],
        'K5' => ['value' => 'Case Tag No.', 'alignment' => 'center',],
        'L5' => ['value' => 'FG Tag No.', 'alignment' => 'center',],
        'M5' => ['value' => 'Part No.', 'alignment' => 'center',],
        'N5' => ['value' => 'Part Description', 'alignment' => 'center',],
        'O5' => ['value' => 'Net Weight (Kg.)', 'alignment' => 'center',],
        'P5' => ['value' => 'Qty (pcs)', 'alignment' => 'center',],
    ];

    $worksheet->getStyle('A1')
        ->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('C4D79B');

    $worksheet->getStyle('A5:P5')
        ->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('def0fc');


    $worksheet->getStyle("O")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
    $worksheet->getStyle("P")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);

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

function summary_dataShipout($worksheet, $row, $data)
{
    $col = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J',
        'K', 'L', 'M', 'N', 'O', 'P'
    ];
    $dataStartRow = $row + 1;
    $lastRow = addDetailTableSheet($worksheet, $data, $row, $col);
    $row = $lastRow;
    $borderCode = 'L1R1T1B1';
    $row_border = $row - 1;
    applyBorders($worksheet, 'A5:P' . $row_border, $borderCode);
    $worksheet->getStyle('A5:P' . $row_border, $borderCode)->getAlignment()->setVertical('center');
    $worksheet->getStyle('A5:P' . $row_border, $borderCode)->getAlignment()->setHorizontal('center');

    $row_sum =  $row_border + 1;
    $worksheet->setCellValue('N' . $row_sum, 'Total : ');
    $worksheet->setCellValue('O' . $row_sum, '=SUM(O6:O' . $row_border . ')');
    $worksheet->setCellValue('P' . $row_sum, '=SUM(P6:P' . $row_border . ')');


    $worksheet->getStyle('N' . $row_sum, $borderCode)->getAlignment()->setHorizontal('right');
    applyBorders($worksheet, 'N' . $row_sum . ':P' . $row_sum, $borderCode);
    $worksheet->getStyle('O' . $row_sum . ':P' . $row_sum, $borderCode)->getAlignment()->setVertical('center');
    $worksheet->getStyle('O' . $row_sum . ':P' . $row_sum, $borderCode)->getAlignment()->setHorizontal('center');

    $worksheet->getStyle('N' . $row_sum . ':P' . $row_sum)
        ->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('EEECE1');

    return $row;
}

$spreadsheet = new Spreadsheet();

$worksheet = $spreadsheet->getActiveSheet();
$worksheet->setShowGridlines(false);
$worksheet->setTitle('Ship Out');
setDefaultStylesSheetShipout($worksheet);
addHeaderDataShipout($worksheet, $date, $time);
$row = 5;
summary_dataShipout($worksheet, $row, $dataTransactionArray);

$date = date('Y-m-d His');

$filename = 'excel/fileoutput/shipout_' . $date . '.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($filename);
