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

function setDefaultStylesSheet($worksheet)
{
    $styles = [
        'A' => ['width' => 10.00], 'B' => ['width' => 15.00], 'C' => ['width' => 20.00], 'D' => ['width' => 20.00], 'E' => ['width' => 25.00],
        'F' => ['width' => 45.00], 'G' => ['width' => 15.00], 'H' => ['width' => 25.00], 'I' => ['width' => 20.00], 'J' => ['width' => 15.00],
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


function setDefaultStylesSheetPackage($worksheet)
{
    $styles = [
        'A' => ['width' => 10.00], 'B' => ['width' => 15.00], 'C' => ['width' => 15.00], 'D' => ['width' => 15.00], 'E' => ['width' => 10.00],
        'F' => ['width' => 10.00], 'G' => ['width' => 10.00], 'H' => ['width' => 20.00], 'I' => ['width' => 20.00],
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

function addHeaderDataPackage($worksheet, $date, $time)
{
    $cellData = [
        //header table
        'A1' => ['value' => 'Onhand (Package)', 'alignment' => 'center',],
        'A2' => ['value' => 'Date : ', 'alignment' => 'center',],
        'A3' => ['value' => 'Time : ', 'alignment' => 'center',],
        'B2' => ['value' => $date, 'alignment' => 'center',],
        'B3' => ['value' => $time, 'alignment' => 'center',],

        'A6' => ['value' => 'No.', 'alignment' => 'center',],
        'B6' => ['value' => 'Package No.', 'alignment' => 'center',],
        'C6' => ['value' => 'Package Type', 'alignment' => 'center',],
        'D6' => ['value' => 'Status', 'alignment' => 'center',],
        'E6' => ['value' => 'TTV', 'alignment' => 'center',],
        'F6' => ['value' => 'HMT', 'alignment' => 'center',],
        'G6' => ['value' => 'NKAPM', 'alignment' => 'center',],
        'H6' => ['value' => 'Updated At', 'alignment' => 'center',],
        'I6' => ['value' => 'Updated By', 'alignment' => 'center',],
    ];

    $worksheet->getStyle('A1')
        ->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('C4D79B');

    $worksheet->getStyle('A6:I6')
        ->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('def0fc');


    $worksheet->getStyle("E")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
    $worksheet->getStyle("F")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
    $worksheet->getStyle("G")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);

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

function summary_dataPackage($worksheet, $row, $data)
{
    $col = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'
    ];
    $dataStartRow = $row + 1;
    $lastRow = addDetailTableSheet($worksheet, $data, $row, $col);
    $row = $lastRow;
    $borderCode = 'L1R1T1B1';
    $row_border = $row - 1;
    applyBorders($worksheet, 'A6:I' . $row_border, $borderCode);
    $worksheet->getStyle('A6:I' . $row_border, $borderCode)->getAlignment()->setVertical('center');
    $worksheet->getStyle('A6:I' . $row_border, $borderCode)->getAlignment()->setHorizontal('center');

    $row_sum =  5;
    $worksheet->setCellValue('D' . $row_sum, 'Total : ');
    $worksheet->setCellValue('E' . $row_sum, '=SUM(E7:E' . $row_border . ')');
    $worksheet->setCellValue('F' . $row_sum, '=SUM(F7:F' . $row_border . ')');
    $worksheet->setCellValue('G' . $row_sum, '=SUM(G7:G' . $row_border . ')');
    $worksheet->setCellValue('H' . $row_sum, '=SUM(e5:G5)');


    $worksheet->getStyle('D' . $row_sum, $borderCode)->getAlignment()->setHorizontal('right');
    applyBorders($worksheet, 'D' . $row_sum . ':H' . $row_sum, $borderCode);
    $worksheet->getStyle('D' . $row_sum . ':H' . $row_sum, $borderCode)->getAlignment()->setVertical('center');
    $worksheet->getStyle('D' . $row_sum . ':H' . $row_sum, $borderCode)->getAlignment()->setHorizontal('center');

    $worksheet->getStyle('D' . $row_sum . ':H' . $row_sum)
        ->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('EEECE1');

    return $row;
}

function setDefaultStylesSheetSteel($worksheet)
{
    $styles = [
        'K' => ['width' => 20.00], 'L' => ['width' => 10.00], 'CM' => ['width' => 10.00], 'N' => ['width' => 10.00], 'O' => ['width' => 10.00],
    ];
    $worksheet->getParent()->getDefaultStyle()->getFont()->setName('Calibri');
    $worksheet->getParent()->getDefaultStyle()->getFont()->setSize(9);
    $worksheet->getParent()->getDefaultStyle()->getAlignment()->setVertical('Center');
    $worksheet->getParent()->getDefaultStyle()->getAlignment()->setWrapText(true);
    //$worksheet->getParent()->getDefaultStyle()->getAlignment()->setHorizontal('Center');

    //$worksheet->getRowDimension('4')->setRowHeight(3, 'pt');
    foreach ($styles as $col => $style) {
        $worksheet->getColumnDimension($col)->setWidth($style['width']);
    }
}

function addHeaderDataSteel($worksheet, $date, $time)
{
    $cellData = [
        //header table
        'K6' => ['value' => 'Steel', 'alignment' => 'center',],
        'L6' => ['value' => 'TTV', 'alignment' => 'center',],
        'M6' => ['value' => 'HMTH', 'alignment' => 'center',],
        'N6' => ['value' => 'NKAPM', 'alignment' => 'center',],
        'O6' => ['value' => 'Total', 'alignment' => 'center',],
    ];

    $worksheet->getStyle('A1')
        ->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('C4D79B');

    $worksheet->getStyle('K6:O6')
        ->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('def0fc');


    $worksheet->getStyle("L")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
    $worksheet->getStyle("M")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
    $worksheet->getStyle("N")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
    $worksheet->getStyle("O")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);

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

function summary_dataSteel($worksheet, $row, $data)
{
    $col = [
        'K', 'L', 'M', 'N',
    ];
    $dataStartRow = $row + 1;
    $lastRow = addDetailTableSheet($worksheet, $data, $row, $col);
    $row = $lastRow;
    $borderCode = 'L1R1T1B1';
    $row_border = $row - 1;
    applyBorders($worksheet, 'K6:O' . $row_border, $borderCode);
    $worksheet->getStyle('K6:O' . $row_border, $borderCode)->getAlignment()->setVertical('center');
    $worksheet->getStyle('K6:O' . $row_border, $borderCode)->getAlignment()->setHorizontal('center');


    $worksheet->setCellValue('O7', '=SUM(L7:N7)');
    $worksheet->getStyle('O6')
        ->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('EEECE1');

    return $row;
}


function addHeaderDataPackageSteel($worksheet, $date, $time)
{
    $cellData = [
        //header table
        'K9' => ['value' => 'Package (Steel)', 'alignment' => 'center',],
        'L9' => ['value' => 'TTV', 'alignment' => 'center',],
        'M9' => ['value' => 'HMTH', 'alignment' => 'center',],
        'N9' => ['value' => 'NKAPM', 'alignment' => 'center',],
        'O9' => ['value' => 'Total', 'alignment' => 'center',],
    ];

    $worksheet->getStyle('K9:O9')
        ->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('def0fc');


    $worksheet->getStyle("L")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
    $worksheet->getStyle("M")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
    $worksheet->getStyle("N")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
    $worksheet->getStyle("O")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);

    // $worksheet->mergeCells('A1:B1');

    foreach ($cellData as $cell => $data) {
        $worksheet->setCellValue($cell, $data['value']);
        $cellStyle = $worksheet->getStyle($cell);
        $cellStyle->getAlignment()->setHorizontal($data['alignment']);
        $cellStyle->getAlignment()->setVertical('center');
    }

}

function summary_dataPackageSteel($worksheet, $row, $data)
{
    $col = [
        'K', 'L', 'M', 'N',
    ];
    $dataStartRow = $row + 1;
    $lastRow = addDetailTableSheet($worksheet, $data, $row, $col);
    $row = $lastRow;
    $borderCode = 'L1R1T1B1';
    $row_border = $row - 1;
    applyBorders($worksheet, 'K9:O' . $row_border, $borderCode);
    $worksheet->getStyle('K9:O' . $row_border, $borderCode)->getAlignment()->setVertical('center');
    $worksheet->getStyle('K9:O' . $row_border, $borderCode)->getAlignment()->setHorizontal('center');

    
    $worksheet->setCellValue('O10', '=SUM(L10:N10)');
    $worksheet->getStyle('O9')
        ->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('EEECE1');

    return $row;
}

function addHeaderDataPackageWooden($worksheet, $date, $time)
{
    $cellData = [
        //header table
        'K12' => ['value' => 'Package (Wooden)', 'alignment' => 'center',],
        'L12' => ['value' => 'TTV', 'alignment' => 'center',],
        'M12' => ['value' => 'HMTH', 'alignment' => 'center',],
        'N12' => ['value' => 'NKAPM', 'alignment' => 'center',],
        'O12' => ['value' => 'Total', 'alignment' => 'center',],
    ];

    $worksheet->getStyle('K12:O12')
        ->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('def0fc');


    $worksheet->getStyle("L")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
    $worksheet->getStyle("M")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
    $worksheet->getStyle("N")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
    $worksheet->getStyle("O")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);

    // $worksheet->mergeCells('A1:B1');

    foreach ($cellData as $cell => $data) {
        $worksheet->setCellValue($cell, $data['value']);
        $cellStyle = $worksheet->getStyle($cell);
        $cellStyle->getAlignment()->setHorizontal($data['alignment']);
        $cellStyle->getAlignment()->setVertical('center');
    }

}

function summary_dataPackageWooden($worksheet, $row, $data)
{
    $col = [
        'K', 'L', 'M', 'N',
    ];
    $dataStartRow = $row + 1;
    $lastRow = addDetailTableSheet($worksheet, $data, $row, $col);
    $row = $lastRow;
    $borderCode = 'L1R1T1B1';
    $row_border = $row - 1;
    applyBorders($worksheet, 'K12:O' . $row_border, $borderCode);
    $worksheet->getStyle('K12:O' . $row_border, $borderCode)->getAlignment()->setVertical('center');
    $worksheet->getStyle('K12:O' . $row_border, $borderCode)->getAlignment()->setHorizontal('center');

    
    $worksheet->setCellValue('O13', '=SUM(L13:N13)');
    $worksheet->getStyle('O12')
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
$row1 = 5;
summary_data($worksheet, $row1, $dataByItemArray);

$worksheet2 = new Worksheet($spreadsheet, 'Onhand (By part)');
$spreadsheet->addSheet($worksheet2, 2);
$worksheet2->setShowGridlines(false);
setDefaultStylesSheet2($worksheet2);
addHeaderData2($worksheet2, $date, $time);
$row2 = 5;
summary_data2($worksheet2, $row2, $dataByPartArray);


$worksheetshipout = new Worksheet($spreadsheet, 'Ship Out');
$spreadsheet->addSheet($worksheetshipout, 3);
$worksheetshipout->setShowGridlines(false);
setDefaultStylesSheetShipout($worksheetshipout);
addHeaderDataShipout($worksheetshipout, $date, $time);
$row3 = 5;
summary_dataShipout($worksheetshipout, $row3, $dataTransactionArray);


$worksheetpackage = new Worksheet($spreadsheet, 'Onhand (Package)');
$spreadsheet->addSheet($worksheetpackage, 4);
$worksheetpackage->setShowGridlines(false);

setDefaultStylesSheetPackage($worksheetpackage);
addHeaderDataPackage($worksheetpackage, $date, $time);

setDefaultStylesSheetSteel($worksheetpackage);
$row4 = 6;
summary_dataPackage($worksheetpackage, $row4, $dataByPackageArray);
addHeaderDataSteel($worksheetpackage, $date, $time);
summary_dataSteel($worksheetpackage, $row4, $dataSteelArray);
$row = 9;
addHeaderDataPackageSteel($worksheetpackage, $date, $time);
summary_dataPackageSteel($worksheetpackage, $row, $dataPackageSteelArray);
$row = 12;
addHeaderDataPackageWooden($worksheetpackage, $date, $time);
summary_dataPackageWooden($worksheetpackage, $row, $dataPackageWoodenArray);


date_default_timezone_set("Asia/Bangkok");
$date = date('d-m-Y His');

$filename = 'excel/fileoutput/Report Onhand_' . $date . '.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($filename);
