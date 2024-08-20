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
// echo('remove');
unlink($doc);
