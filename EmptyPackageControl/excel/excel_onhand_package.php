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
        'K6' => ['value' => 'Steel Pipe', 'alignment' => 'center',],
        'L6' => ['value' => 'TTV', 'alignment' => 'center',],
        'M6' => ['value' => 'HMTH', 'alignment' => 'center',],
        'N6' => ['value' => 'NKAPM', 'alignment' => 'center',],
        'O6' => ['value' => 'Total', 'alignment' => 'center',],
    ];


    $worksheet->getStyle('K6:O6')
        ->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('def0fc');


    $worksheet->getStyle("L")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
    $worksheet->getStyle("M")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
    $worksheet->getStyle("N")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
    $worksheet->getStyle("O")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);


    foreach ($cellData as $cell => $data) {
        $worksheet->setCellValue($cell, $data['value']);
        $cellStyle = $worksheet->getStyle($cell);
        $cellStyle->getAlignment()->setHorizontal($data['alignment']);
        $cellStyle->getAlignment()->setVertical('center');
    }

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
$worksheet->setTitle('Onhand (Package)');
setDefaultStylesSheetPackage($worksheet);
addHeaderDataPackage($worksheet, $date, $time);
$row = 6;
summary_dataPackage($worksheet, $row, $dataByPackageArray);

setDefaultStylesSheetSteel($worksheet);
addHeaderDataSteel($worksheet, $date, $time);
summary_dataSteel($worksheet, $row, $dataSteelArray);

$row = 9;
addHeaderDataPackageSteel($worksheet, $date, $time);
summary_dataPackageSteel($worksheet, $row, $dataPackageSteelArray);

$row = 12;
addHeaderDataPackageWooden($worksheet, $date, $time);
summary_dataPackageWooden($worksheet, $row, $dataPackageWoodenArray);

$date = date('Y-m-d His');

$filename = 'excel/fileoutput/onhand_package_' . $date . '.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($filename);
