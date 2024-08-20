<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include '../fpdf.php';
include '../exfpdf.php';
include '../easyTable.php';
include 'PDF_Code128.php';
include('../../php/connection.php');
require '../../vendor/autoload.php';

$doc = $mysqli->real_escape_string(trim(strtoupper($_REQUEST['data'])));

$file_name = substr($doc, 0, 13);

// create merger instance
$pdf = new \Jurosh\PDFMerge\PDFMerger;


$pdf->addPDF('files/grn/case_tag/' . $doc . '.pdf', 'all', 'vertical')
    ->addPDF('files/grn/part_tag/' . $doc . '.pdf', 'all', 'vertical')
    ->merge('file', 'files/grn/grn_doc/TAG_' . $file_name . '.pdf');
