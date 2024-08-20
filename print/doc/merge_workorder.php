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
    t2.work_order_no
FROM
    tbl_transaction t1
        INNER JOIN
    tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
WHERE  t1.document_no = '$doc'
        AND t2.status = 'Complete'
GROUP BY t2.work_order_no 
ORDER BY t1.document_date DESC , t1.document_no DESC , t2.work_order_no ASC;";
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
    $work_order_no = $value['work_order_no'];
    $ip_server = $_SERVER['SERVER_NAME'];
    //echo($ip_server);
    $pus_file =  file_get_contents('http://' . $ip_server . '/hmt_wh/print/doc/workorder.php?data=' . $work_order_no, false, stream_context_create($arrContextOptions));
    $pdf->addPDF('files/WorkOrder/' . $work_order_no . '.pdf', 'all', 'vertical');

}
$pdf->merge('file', 'files/WorkOrder/' . $doc . '.pdf');
// echo '<pre>';

// print_r($headerData);
// // print_r($dataset);
// echo '</pre>';

// exit;
// if (isset($array[0])) {
//     $value = $array[0];
// } else {
//     echo "ไม่มี";
// }

// $detailData = $dataset[1];

// $file_name = substr($doc, 0, 13);

// create merger instance
// $pdf = new \Jurosh\PDFMerge\PDFMerger;

// $pdf->addPDF('files/WorkOrder/' . $doc . '.pdf', 'all', 'vertical')
//     ->addPDF('files/WorkOrder/' . $doc . '.pdf', 'all', 'vertical')
//     ->merge('file', 'files/grn/TAG_' . $file_name . '.pdf');
