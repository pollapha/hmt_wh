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

$arrContextOptions = array(
    "ssl" => array(
        "verify_peer" => false,
        "verify_peer_name" => false,
    ),
);

$doc = $mysqli->real_escape_string(trim(strtoupper($_REQUEST['data'])));
$dataset = array();


$q1 = "SELECT
    DISTINCT t2.fg_tag_no
FROM
    tbl_order_header t1
		INNER JOIN tbl_order t2 ON t1.order_header_id = t2.order_header_id
WHERE  t2.work_order_no = '$doc'
		AND t2.repack = 'Yes'
ORDER BY t2.fg_tag_no ASC;";
if (!$mysqli->multi_query($q1)) {
    echo "Multi query failed: (" . $mysqli->errno . ") " . $mysqli->error;
}
do {
    if ($res = $mysqli->store_result()) {
        array_push($dataset, $res->fetch_all(MYSQLI_ASSOC));
        $res->free();
    }
} while ($mysqli->more_results() && $mysqli->next_result());
$headerData = $dataset[0];
// create merger instancehmt
$pdf = new \Jurosh\PDFMerge\PDFMerger;

// $doc 
foreach ($headerData as $value) {
    // echo($value['work_order_no']);
    $fg_tag_no = $value['fg_tag_no'];
    $ip_server = $_SERVER['SERVER_NAME'];
    //echo($ip_server);
    $pus_file =  file_get_contents('http://' . $ip_server . '/hmt_wh/print/doc/fg_tag.php?data=' . $fg_tag_no, false, stream_context_create($arrContextOptions));
    $pdf->addPDF('files/Fg_tag/' . $fg_tag_no . '.pdf', 'all', 'vertical');
}
$pdf->merge('file', 'files/Fg_tag/' . $doc . '.pdf');
