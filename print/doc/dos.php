<?php
include '../fpdf.php';
include '../exfpdf.php';
include '../easyTable.php';
include 'PDF_Code128.php';
include('../../php/connection.php');

$doc = $mysqli->real_escape_string(trim(strtoupper($_REQUEST['data'])));
$dataset = array();
// -------------------------------------headerData detailData Start--------------------------------------------------------
$q1  = "SELECT 
    t1.dos_no,
    DATE_FORMAT(t1.document_date, '%d-%b-%y') AS document_date,
    t1.order_no,
    DATE_FORMAT(t1.order_date, '%d-%b-%y') AS order_date, 
    DATE_FORMAT(t1.delivery_date, '%d-%b-%y') AS delivery_date,
    DATE_FORMAT(t1.created_at, '%d-%m-%Y / %h:%s:%i') AS created_at,
    t3.user_fName AS created_by,
    supplier_code,
    t1.repack
FROM
    tbl_order_header t1 
        INNER JOIN
   tbl_order t2 ON t1.order_header_id = t2.order_header_id
        INNER JOIN
    tbl_user t3 ON t1.created_user_id = t3.user_id
		INNER JOIN
	tbl_supplier_master t4 ON t1.supplier_id = t4.supplier_id
WHERE
    t1.dos_no = '$doc'
        AND t2.status = 'Complete'
group by t1.order_no
ORDER BY t2.work_order_no, t2.case_tag_no ASC;";
// -------------------------------------Query headerData EDN--------------------------------------------------------

// -------------------------------------Query detailData Start--------------------------------------------------------
$q1  .= "SELECT 
    part_no,
    part_name,
    t2.repack,
    t2.work_order_no,
    t2.fg_tag_no tag_no,
    SUM(t2.net_per_pcs) net_per_pallet,
    SUM(t2.qty) qty,
    t2.package_no,
    t6.location_code,
    supplier_code
FROM
    tbl_order_header t1
        INNER JOIN
    tbl_order t2 ON t1.order_header_id = t2.order_header_id
        LEFT JOIN
    tbl_inventory_detail t3 ON t2.part_tag_no = t3.part_tag_no
        LEFT JOIN
    tbl_part_master t5 ON t3.part_id = t5.part_id
        LEFT JOIN
    tbl_location_master t6 ON t3.location_id = t6.location_id
		LEFT JOIN
	tbl_supplier_master t8 ON t1.supplier_id = t8.supplier_id
WHERE
    t1.dos_no = '$doc'
        AND t2.status = 'Complete'
GROUP BY work_order_no, fg_tag_no
ORDER BY work_order_no, fg_tag_no;";
// -------------------------------------Query detailData EDN--------------------------------------------------------

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
$detailData = $dataset[1];

// echo '<pre>';

// print_r($headerData);
// print_r($dataset);
// // print_r($noteData);
// echo '</pre>';

// exit;
// if (isset($array[0])) {
//     $value = $array[0];
// } else {
//     echo "ไม่มี";
// }

class PDF extends PDF_Code128
{
    function __construct($orientation = 'P', $unit = 'mm', $format = 'A4')
    {
        parent::__construct($orientation, $unit, $format);
        $this->AliasNbPages();
    }
    public function setHeaderData($v)
    {
        $this->headerData = $v;
    }
    public function setInstance($v)
    {
        $this->instance = $v;
    }
    function Header()
    {
        $v = $this->headerData;
        $header = new easyTable($this->instance, '%{85,15}', 'border:0;font-family:Trirong;');
        $header->easyCell('TTV SUPPLYCHAIN CO., LTD. (TTV)', 'valign:M;align:L;font-size:10; font-style:B;border:0');
        $header->easyCell('', 'img:images/TTVNEW.jpg, w30;align:C;rowspan:2;bgcolor:#ffffff;border:0', '');
        $header->printRow();
        $header->easyCell('336/11 Moo 7 BoWin, Sriracha, Chonburi 20230
		Tel: 033 -135 020', 'valign:M;align:L;font-size:8; font-style:B;border:0');
        $header->printRow();

        $this->instance->Code128(95, 13, $v[0]['dos_no'], 60, 8);
        $header->endTable(2);
        // ---------------------------------------------------------------------------------------------
        $header = new easyTable($this->instance, '%{100}', 'border:1;font-family:Trirong;font-size:12; font-style:B;');
        $header->easyCell(utf8Th('Delivery Order Sheet  ( DOS )'), 'valign:M;align:C');
        $header->printRow();
        $header->endTable(5);
        // ---------------------------------------------------------------------------------------------
        $header = new easyTable($this->instance, 6, 'border:0;font-family:Trirong;font-size:8;');
        $header->easyCell("Order No  ( OC ) :", 'valign:T;align:L;font-style:B;');
        $header->easyCell(utf8Th($v[0]['order_no']), 'valign:M;align:L;');
        $header->easyCell("", 'valign:T;align:R;');
        $header->easyCell(utf8Th(''), 'valign:T;align:L;');
        $header->easyCell("Document No. :", 'valign:T;align:R;font-style:B;');
        $header->easyCell(utf8Th($v[0]['dos_no']), 'valign:M;align:L;');
        $header->printRow();
        // ---------------------------------------------------------------------------------------------
        $header->easyCell("Order Date :", 'valign:T;align:L;font-style:B;');
        $header->easyCell(utf8Th($v[0]['order_date']), 'valign:T;align:L;');
        $header->easyCell("", 'valign:T;align:R;');
        $header->easyCell(utf8Th(''), 'valign:T;align:L;');
        $header->easyCell("Document Date :", 'valign:T;align:R;font-style:B;');
        $header->easyCell(utf8Th($v[0]['document_date']), 'valign:M;align:L;');
        $header->printRow();
        // ---------------------------------------------------------------------------------------------
        $header->easyCell("Delivery Date : ", 'valign:T;align:L;font-style:B;');
        $header->easyCell(utf8Th($v[0]['delivery_date']), 'valign:T;align:L;');
        $header->easyCell("", 'valign:T;align:R;');
        $header->easyCell(utf8Th(''), 'valign:T;align:L;');;
        $header->easyCell("Creator :", 'valign:T;align:R;font-style:B;');
        $header->easyCell(utf8Th($v[0]['created_by']), 'valign:M;align:L;');
        $header->printRow();
        // ---------------------------------------------------------------------------------------------
        $header->easyCell("Destination :", 'valign:T;align:L;font-style:B;');
        $header->easyCell(utf8Th($v[0]['supplier_code']), 'valign:T;align:L;');
        $header->easyCell("", 'valign:T;align:R;');
        $header->easyCell(utf8Th(''), 'valign:T;align:L;');
        $header->easyCell("Creator :", 'valign:T;align:R;font-style:B;');
        $header->easyCell(utf8Th($v[0]['created_at']), 'valign:M;align:L;');
        $header->printRow();
        // ---------------------------------------------------------------------------------------------
        $header->easyCell("", 'valign:T;align:L;font-style:B;');
        $header->easyCell("", 'valign:T;align:L;');
        $header->easyCell("", 'valign:T;align:R;');
        $header->easyCell(utf8Th(''), 'valign:T;align:L;');
        $header->easyCell("", 'valign:T;align:R;font-style:B;');
        $header->easyCell('', 'valign:M;align:L;');
        $header->printRow();

        $header->endTable(5);

        $repack = $v[0]['repack'];

        if ($repack == 'No') {
            $head_tag = 'Case Tag No.';
        } else {
            $head_tag = 'FG Tag No.';
        }

        // -------------------------------------headdetail- Table--------------------------------------------------------
        $headdetail = new easyTable(
            $this->instance,
            '%{5, 20, 30, 10, 10, 5, 10, 10}',
            'border:1;font-family:Trirong;font-size:6; font-style:B; valign:M;'
        );
        $headdetail->rowStyle('align:{RCCCCCCCCCCC};font-style:B');
        $headdetail->easyCell(utf8Th('Item'), 'align:C');
        $headdetail->easyCell(utf8Th('Part No.'), 'align:C');
        $headdetail->easyCell(utf8Th('Part Description'), 'align:C');
        $headdetail->easyCell(utf8Th($head_tag), 'align:C');
        // $headdetail->easyCell(utf8Th('Coil Lot NO'), 'align:C;font-color:#FF0000;');
        $headdetail->easyCell(utf8Th('Net Weight (kg.)'), 'align:C');
        $headdetail->easyCell(utf8Th('Qty (PCS)'), 'align:C');
        $headdetail->easyCell(utf8Th('Package No.'), 'align:C');
        $headdetail->easyCell(utf8Th('Location'), 'align:C');


        $headdetail->printRow(true);
        $headdetail->endTable(0);
    }

    function Footer()
    {
        $this->SetXY(-30, 2);
        $this->SetFont('Trirong', '', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

$pdf = new PDF('P');

// $pdf->AddFont('THSarabun', '', 'THSarabun.php');
// $pdf->AddFont('THSarabun', 'I', 'THSarabun Italic.php');
// $pdf->AddFont('THSarabun', 'B', 'THSarabun Bold.php');
// $pdf->AddFont('THSarabun', 'BI', 'THSarabun Bold Italic.php');

$pdf->AddFont('Trirong', '', 'Trirong-Regular.php');
$pdf->AddFont('Trirong', 'I', 'Trirong-Italic.php');
$pdf->AddFont('Trirong', 'B', 'Trirong-Bold.php');
$pdf->AddFont('Trirong', 'BI', 'Trirong-BoldItalic.php');
$pdf->setInstance($pdf);
$pdf->setHeaderData($headerData);
$pdf->AddPage();
$docno = $headerData[0]['dos_no'];
$pdf->SetTitle($docno);
$detail = new easyTable($pdf, '%{5, 20, 30, 10, 10, 5, 10, 10}', 'border:1;font-family:Trirong;font-size:6;valign:M;line-height:2;');
$data = sizeof($detailData);
// หน้าละ15row
$pagebreak = 15;
$i = 0;
$countrow = 1;
$nn = 1;
$sumnw = 0;
$sumsnp = 0;
$sumtps = 0;
// $case_tag_no = $detailData[$i]["case_tag_no"];
while ($i <  $data) {

    if ($countrow > $pagebreak) {
        $pdf->AddPage();
        $countrow = 1;
    }
    $countrow++;
    $x = $pdf->GetX();
    $y = $pdf->GetY();

    $detail->easyCell(utf8Th($nn), 'align:C');
    $detail->easyCell(utf8Th($detailData[$i]["part_no"]), 'align:C;');
    $detail->easyCell(utf8Th($detailData[$i]["part_name"]), 'align:C;');
    $detail->easyCell(utf8Th($detailData[$i]["tag_no"]), 'align:C;');
    // $detail->easyCell(utf8Th(''), 'align:C;');
    $detail->easyCell(utf8Th($detailData[$i]["net_per_pallet"]), 'align:C;'); # code...
    $detail->easyCell(utf8Th($detailData[$i]["qty"]), 'align:C;');
    $detail->easyCell(utf8Th($detailData[$i]["package_no"]), 'align:C;');
    $detail->easyCell(utf8Th($detailData[$i]["location_code"]), 'align:C;');
    $detail->printRow();

    $sumnw += $detailData[$i]['net_per_pallet'];
    $sumsnp += $detailData[$i]['qty'];

    $i++;
    $nn++;
}

$detail->easyCell(utf8Th(''), 'align:C;border:BLT;');
$detail->easyCell(utf8Th(''), 'align:C;border:BT;');
$detail->easyCell(utf8Th('Total'), 'align:C;border:BT;font-style:B;');
$detail->easyCell(utf8Th(''), 'align:C;border:TB;');
// $detail->easyCell(utf8Th(''), 'align:C;border:BT;');

$detail->easyCell(utf8Th($sumnw), 'align:C;font-style:B;font-size:6;');
$detail->easyCell(utf8Th($sumsnp), 'align:C;font-style:B;font-size:6;');
$detail->easyCell('', 'align:C;font-style:B;font-size:5;');
$detail->easyCell('', 'align:C;font-style:B;font-size:5;');
$detail->easyCell('', 'align:C;font-style:B;font-size:5;');
$detail->printRow();
$detail->endTable(10);


$lastfooter = new easyTable($pdf, '%{60,5,35}', 'border:1;font-family:Trirong;font-size:8;');
$lastfooter->rowStyle('align:{LCRCCC}; font-style:B');
$lastfooter->rowStyle('min-height:8');
$lastfooter->easyCell('Note :', 'valign:M;align:L;border:LTR;font-style:B');
$lastfooter->easyCell('', 'align:C;border:0;');
$lastfooter->easyCell('Checker', 'valign:M;align:C;border:LTR;font-style:B');
$lastfooter->printRow();

$lastfooter->rowStyle('min-height:25');
$lastfooter->easyCell('', 'align:L;border:LR;');
$lastfooter->easyCell('', 'align:C;border:0;');
$lastfooter->easyCell('', 'align:C;border:LBTR;');
$lastfooter->easyCell('', 'align:C;border:0;');
$lastfooter->printRow();

$lastfooter->rowStyle('min-height:8');
$lastfooter->easyCell('', 'valign:M;align:C;border:LBR;');
$lastfooter->easyCell('', 'align:C;border:0;');
$lastfooter->easyCell('TTV SUPPLYCHAIN CO., LTD. (TTV)', 'valign:M;align:C;border:LBTR;');
$lastfooter->easyCell('', 'align:C;border:0;');
$lastfooter->printRow();
$lastfooter->endTable(10);

$pdf->Output();

// $pdf->Output('F', 'files/WorkOrder/' . $docno . '.pdf');
function utf8Th($v)
{
    return iconv('UTF-8', 'TIS-620//TRANSLIT', $v);
}
