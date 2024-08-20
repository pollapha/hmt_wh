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
t1.document_no,
DATE_FORMAT(t1.document_date, '%d-%b-%y') AS document_date,
t2.order_no,
DATE_FORMAT(t2.order_date, '%d-%b-%y') AS order_date, 
DATE_FORMAT(t1.delivery_date, '%d-%b-%y') AS delivery_date,
DATE_FORMAT(t1.created_at, '%d-%m-%Y / %h:%s:%i') AS created_at,
t3.user_fName AS created_by,
supplier_code, supplier_name, truck_number, driver_name
 FROM tbl_transaction t1
	INNER JOIN tbl_order_header t2 ON t1.order_header_id = t2.order_header_id
	INNER JOIN tbl_user t3 ON t1.created_user_id = t3.user_id
    INNER JOIN tbl_supplier_master t4 ON t2.supplier_id = t4.supplier_id
    INNER JOIN tbl_truck_master t5 ON t1.truck_id = t5.truck_id
    INNER JOIN tbl_driver_master t6 ON t1.driver_id = t6.driver_id
 WHERE document_no = '$doc'
    AND (transaction_type = 'Picking' OR transaction_type = 'Out');";
// -------------------------------------Query headerData EDN--------------------------------------------------------

// -------------------------------------Query detailData Start--------------------------------------------------------
$q1  .= "SELECT 
fg_tag_no tag_no,
package_no, SUM(qty) qty, steel_qty, SUM(net_per_pallet) net_per_pallet, certificate_no,
part_no, part_name, location_code
 FROM tbl_transaction t1
	INNER JOIN tbl_order_header t2 ON t1.order_header_id = t2.order_header_id
	INNER JOIN tbl_user t3 ON t1.created_user_id = t3.user_id
    INNER JOIN tbl_transaction_line t4 ON t1.transaction_id = t4.transaction_id
    INNER JOIN tbl_part_master t5 ON t4.part_id = t5.part_id
    INNER JOIN tbl_location_master t6 ON t4.from_location_id = t6.location_id
 WHERE document_no = '$doc'
		AND (transaction_type = 'Picking' OR transaction_type = 'Out')
        AND t4.status = 'Complete'
GROUP BY t4.work_order_no, fg_tag_no
ORDER BY t4.work_order_no, package_no, case_tag_no, fg_tag_no;";
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

$destination = $headerData[0]['supplier_name'];

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

        $truck_number = $v[0]['truck_number'];
        if($truck_number == 'N/A'){
            $truck_number = '';
        }

        $driver_name = $v[0]['driver_name'];
        if($driver_name == 'N/A'){
            $driver_name = '';
        }

        $header = new easyTable($this->instance, '%{85,15}', 'border:0;font-family:Trirong;');
        $header->easyCell('TTV SUPPLYCHAIN CO., LTD. (TTV)', 'valign:M;align:L;font-size:10; font-style:B;border:0');
        $header->easyCell('', 'img:images/TTVNEW.jpg, w30;align:C;rowspan:2;bgcolor:#ffffff;border:0', '');
        $header->printRow();
        $header->easyCell('336/11 Moo 7 BoWin, Sriracha, Chonburi 20230
		Tel: 033 -135 020', 'valign:M;align:L;font-size:8; font-style:B;border:0');
        $header->printRow();

        $this->instance->Code128(95, 13, $v[0]['document_no'], 60, 8);
        // ---------------------------------------------------------------------------------------------
        $header = new easyTable($this->instance, '%{100}', 'border:1;font-family:Trirong;font-size:12; font-style:B;');
        $header->easyCell(utf8Th('Good Transfer Note  ( GTN )'), 'valign:M;align:C');
        $header->printRow();
        $header->endTable(5);
        // ---------------------------------------------------------------------------------------------
        $header = new easyTable($this->instance, 6, 'border:0;font-family:Trirong;font-size:8;');
        $header->easyCell("Order No  ( OC ) :", 'valign:T;align:L;font-style:B;');
        $header->easyCell(utf8Th($v[0]['order_no']), 'valign:M;align:L;');
        $header->easyCell("", 'valign:T;align:R;');
        $header->easyCell(utf8Th(''), 'valign:T;align:L;');
        $header->easyCell("Document No. :", 'valign:T;align:R;font-style:B;');
        $header->easyCell(utf8Th($v[0]['document_no']), 'valign:M;align:L;');
        $header->printRow();
        // ---------------------------------------------------------------------------------------------
        $header->easyCell("Delivery Date :", 'valign:T;align:L;font-style:B;');
        $header->easyCell(utf8Th($v[0]['delivery_date']), 'valign:T;align:L;');
        $header->easyCell("", 'valign:T;align:R;');
        $header->easyCell(utf8Th(''), 'valign:T;align:L;');
        $header->easyCell("Document Date :", 'valign:T;align:R;font-style:B;');
        $header->easyCell(utf8Th($v[0]['document_date']), 'valign:M;align:L;');
        $header->printRow();
        // ---------------------------------------------------------------------------------------------
        $header->easyCell("Truck No :", 'valign:T;align:L;font-style:B;');
        $header->easyCell(utf8Th($truck_number), 'valign:T;align:L;');
        $header->easyCell("", 'valign:T;align:R;');
        $header->easyCell(utf8Th(''), 'valign:T;align:L;');
        $header->easyCell("Creator :", 'valign:T;align:R;font-style:B;');
        $header->easyCell(utf8Th($v[0]['created_by']), 'valign:M;align:L;');
        $header->printRow();
        // ---------------------------------------------------------------------------------------------
        $header = new easyTable($this->instance, 6, 'border:0;font-family:Trirong;font-size:8;');
        $header->easyCell("Driver Name :", 'valign:T;align:L;font-style:B;');
        $header->easyCell(utf8Th($driver_name), 'valign:T;align:L;');

        $header->easyCell("", 'valign:T;align:R;');
        $header->easyCell(utf8Th(''), 'valign:T;align:L;');;
        $header->easyCell("Created At :", 'valign:T;align:R;font-style:B;');
        $header->easyCell(utf8Th($v[0]['created_at']), 'valign:M;align:L;');
        $header->printRow();
        // ---------------------------------------------------------------------------------------------
        $header = new easyTable($this->instance, 6, 'border:0;font-family:Trirong;font-size:8;');
        $header->easyCell("Destination :", 'valign:T;align:L;font-style:B;');
        $header->easyCell(utf8Th($v[0]['supplier_code']), 'valign:T;align:L;');

        $header->easyCell("", 'valign:T;align:R;');
        $header->easyCell(utf8Th(''), 'valign:T;align:L;');;
        $header->easyCell("", 'valign:T;align:R;font-style:B;');
        $header->easyCell("", 'valign:M;align:L;');
        $header->printRow();
        // ---------------------------------------------------------------------------------------------
        // $header->easyCell("Invoice No. :", 'valign:T;align:L;font-style:B;');
        // $header->easyCell(utf8Th($v[0]['invoice_no']), 'valign:T;align:L;');
        // $header->printRow();
        $header->endTable(5);

        // -------------------------------------headdetail- Table--------------------------------------------------------
        $headdetail = new easyTable(
            $this->instance,
            '%{5, 20, 22, 10, 13, 9, 6, 9, 6}',
            'border:1;font-family:Trirong;font-size:6; font-style:B; valign:M;'
        );
        $headdetail->rowStyle('align:{RCCCCCCCCCCC};font-style:B');
        $headdetail->easyCell(utf8Th('Item'), 'align:C');
        $headdetail->easyCell(utf8Th('Part No.'), 'align:C');
        $headdetail->easyCell(utf8Th('Part Description'), 'align:C');
        $headdetail->easyCell(utf8Th('Tag No'), 'align:C');
        $headdetail->easyCell(utf8Th('Certificate No.'), 'align:C;');
        $headdetail->easyCell(utf8Th("Net\nWeight (kg.)"), 'align:C');
        $headdetail->easyCell(utf8Th("Qty\n(Pcs.)"), 'align:C');
        $headdetail->easyCell(utf8Th('Package No'), 'align:C');
        $headdetail->easyCell(utf8Th("Pipe\nsteel"), 'align:C');


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

$pdf->AddFont('Trirong', '', 'Trirong-Regular.php');
$pdf->AddFont('Trirong', 'I', 'Trirong-Italic.php');
$pdf->AddFont('Trirong', 'B', 'Trirong-Bold.php');
$pdf->AddFont('Trirong', 'BI', 'Trirong-BoldItalic.php');
$pdf->setInstance($pdf);
$pdf->setHeaderData($headerData);
$pdf->AddPage();
$docno = $headerData[0]['document_no'];
$pdf->SetTitle($docno);
$detail = new easyTable($pdf, '%{5, 20, 22, 10, 13, 9, 6, 9, 6}', 'border:1;font-family:Trirong;font-size:6;valign:M;');
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
    // $detail->easyCell(utf8Th($nn), 'align:C');

    $detail->easyCell(utf8Th($nn), 'align:C');
    $detail->easyCell(utf8Th($detailData[$i]["part_no"]), 'align:C;');
    $detail->easyCell(utf8Th($detailData[$i]["part_name"]), 'align:C;');
    $detail->easyCell(utf8Th($detailData[$i]["tag_no"]), 'align:C;');
    $detail->easyCell(utf8Th($detailData[$i]["certificate_no"]), 'align:C;'); # code...
    $detail->easyCell(utf8Th($detailData[$i]["net_per_pallet"]), 'align:C;'); # code...
    $detail->easyCell(utf8Th($detailData[$i]["qty"]), 'align:C;');
    $detail->easyCell(utf8Th($detailData[$i]["package_no"]), 'align:C;');
    $detail->easyCell(utf8Th($detailData[$i]["steel_qty"]), 'align:C;');
    // $detail->easyCell(utf8Th($detailData[$i]["total_net_per_pallet"]), 'align:C;');
    $pdf->Code128($x + 11, $y + 7, $detailData[$i]['part_no'], 75, 8);
    $pdf->Code128($x + 132.5, $y + 6.5, $detailData[$i]['net_per_pallet'], 18, 8);

    $detail->printRow();

    $detail->rowStyle('min-height:13');
    $detail->easyCell('', 'border:L;');
    $detail->easyCell('', 'border:0;');
    $detail->easyCell('', 'border:0;');
    $detail->easyCell('', 'border:0;');
    $detail->easyCell('', 'border:0;');
    $detail->easyCell('', 'border:0;');
    $detail->easyCell('', 'border:0;');
    $detail->easyCell('', 'border:0;');
    $detail->easyCell('', 'border:R;');
    $detail->printRow();
    $sumnw += $detailData[$i]['net_per_pallet'];
    $sumsnp += $detailData[$i]['qty'];
    $sumtps += $detailData[$i]['steel_qty'];
    $i++;
    $nn++;
}
// $detail->easyCell('', 'colspan:3;');

$detail->easyCell(utf8Th(''), 'align:C;border:BLT;');
$detail->easyCell(utf8Th(''), 'align:C;border:BT;');
$detail->easyCell(utf8Th('Total'), 'align:C;border:BT;font-style:B;font-size:6;');
$detail->easyCell(utf8Th(''), 'align:C;border:TB;');
$detail->easyCell(utf8Th(''), 'align:C;border:BT;');

$detail->easyCell(utf8Th($sumnw), 'align:C;font-style:B;font-size:6;');
$detail->easyCell(utf8Th($sumsnp), 'align:C;font-style:B;font-size:6;');
$detail->easyCell('', 'align:C;font-style:B;font-size:5;');
$detail->easyCell(utf8Th($sumtps), 'align:C;font-style:B;font-size:6;');
$detail->printRow();

$detail->endTable(10);

$lastfooter = new easyTable($pdf, '%{30,5,30,5,30}', 'border:1;font-family:Trirong;font-size:8;');
$lastfooter->rowStyle('align:{LCRCCC}; font-style:B');
$lastfooter->rowStyle('min-height:8');
$lastfooter->easyCell('Recived', 'valign:M;align:C;border:LTBR;font-style:B');
$lastfooter->easyCell('', 'align:L;border:0;');
$lastfooter->easyCell('Truck Driver', 'valign:M;align:C;border:LTBR;font-style:B');
$lastfooter->easyCell('', 'align:L;border:0;');
$lastfooter->easyCell('Checker', 'valign:M;align:C;border:LTBR;font-style:B');
$lastfooter->printRow();

$lastfooter->rowStyle('min-height:20');
$lastfooter->easyCell('', 'align:L;border:LBTR;');
$lastfooter->easyCell('', 'align:C;border:0;');
$lastfooter->easyCell('', 'align:C;border:LBTR;');
$lastfooter->easyCell('', 'align:C;border:0;');
$lastfooter->easyCell('', 'align:C;border:LBTR;');
$lastfooter->printRow();

$lastfooter->rowStyle('min-height:8');
$lastfooter->easyCell(utf8Th($destination), 'valign:M;align:C;border:LRB;');
$lastfooter->easyCell('', 'align:C;border:0;');
$lastfooter->easyCell('TTV SUPPLYCHAIN CO., LTD. (TTV)', 'valign:M;align:C;border:LBTR;');
$lastfooter->easyCell('', 'align:C;border:0;');
$lastfooter->easyCell('TTV SUPPLYCHAIN CO., LTD. (TTV)', 'valign:M;align:C;border:1;');
$lastfooter->printRow();
$lastfooter->endTable(10);

$pdf->Output();

// $pdf->Output('F', 'files/WorkOrder/' . $docno . '.pdf');
function utf8Th($v)
{
    return iconv('UTF-8', 'TIS-620//TRANSLIT', $v);
}
