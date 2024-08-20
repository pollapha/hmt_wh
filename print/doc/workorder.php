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
    t1.transaction_type,
    t4.order_no,
    DATE_FORMAT(t4.order_date, '%d-%b-%y') AS order_date, 
    DATE_FORMAT(t4.delivery_date, '%d-%b-%y') AS delivery_date,
    t1.invoice_no,
    t2.work_order_no,
    part_no,
    part_name,
    DATE_FORMAT(t1.created_at, '%d-%m-%Y / %h:%s:%i') AS created_at,
    t8.user_fName AS created_by
FROM
    tbl_transaction t1
        INNER JOIN
    tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
        INNER JOIN
    tbl_part_master t3 ON t2.part_id = t3.part_id
        INNER JOIN
    tbl_order_header t4 ON t1.order_header_id = t4.order_header_id
        INNER JOIN
    tbl_user t8 ON t1.created_user_id = t8.user_id
WHERE
    t2.work_order_no = '$doc'
        AND t2.status = 'Complete'
GROUP BY t2.work_order_no 
ORDER BY t1.document_date DESC , t1.document_no DESC , t2.work_order_no ASC;";
// -------------------------------------Query headerData EDN--------------------------------------------------------

// -------------------------------------Query detailData Start--------------------------------------------------------
$q1  .= "WITH a AS (
SELECT
	t1.work_order_no, case_tag_no, fg_tag_no, SUM(qty) total_qty, SUM(net_per_pallet) total_net,
    certificate_no, t2.invoice_no
FROM
	tbl_transaction_line t1
		inner join tbl_transaction t2 ON t1.transaction_id = t2.transaction_id
WHERE
	t1.work_order_no = '$doc'
		AND t1.status = 'Complete'
group by t1.work_order_no, fg_tag_no
ORDER BY t1.work_order_no, fg_tag_no
)
SELECT 
	ROW_NUMBER() OVER (partition by fg_tag_no order by t1.document_date DESC, document_no DESC, t2.work_order_no, t2.fg_tag_no, t2.case_tag_no ASC) as row_num,	
    part_no,
    part_name,
    t2.case_tag_no,
    t5.location_code,
    a.total_net,
    a.total_qty,
    t2.net_per_pallet,
    t2.qty qty_per_pallet,
    t3.net_per_pcs net_per_pcs,
    t2.fg_tag_no,
    t2.certificate_no,
    t2.invoice_no
FROM
    tbl_transaction t1
        INNER JOIN
    tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
		INNER JOIN
	a ON t2.work_order_no = t2.work_order_no AND t2.fg_tag_no = a.fg_tag_no
        INNER JOIN
    tbl_transaction_detail t3 ON t2.transaction_line_id = t3.transaction_line_id
        INNER JOIN
    tbl_part_master t4 ON t2.part_id = t4.part_id
        INNER JOIN
    tbl_location_master t5 ON t2.from_location_id = t5.location_id
WHERE  t2.work_order_no = '$doc'
        -- AND t1.transaction_type = 'Packing'
        AND t2.status = 'Complete'
GROUP BY t2.work_order_no, fg_tag_no, t2.case_tag_no
order by t1.document_date DESC, document_no DESC, t2.work_order_no, t2.fg_tag_no, t2.case_tag_no ASC;";
// -------------------------------------Query detailData EDN--------------------------------------------------------

// -------------------------------------Query noteData Start--------------------------------------------------------
$q1  .= "SELECT 
    t2.fg_tag_no,
    GROUP_CONCAT(DISTINCT t3.part_tag_no SEPARATOR ', ') AS part_tag_no
FROM
    tbl_transaction t1
        INNER JOIN
    tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
        INNER JOIN
    tbl_transaction_detail t3 ON t2.transaction_line_id = t3.transaction_line_id
WHERE  t2.work_order_no = '$doc'
        AND t2.status = 'Complete'
GROUP BY t2.fg_tag_no, t2.work_order_no
ORDER BY t2.fg_tag_no ASC , t3.part_tag_no ASC;";
// -------------------------------------Query noteData EDN--------------------------------------------------------

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
$noteData = $dataset[2];


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

        $this->instance->Code128(95, 13, $v[0]['work_order_no'], 60, 8);

        $header->endTable(2);
        /* $header = new easyTable($this->instance, 2, 'font-family:Trirong;');
        $header->easyCell('TTV SUPPLYCHAIN CO., LTD. (TTV)', 'font-size:10; font-style:B;');
        $header->printRow();

        $header->rowStyle('font-size:8;');
        $header->easyCell('336/11 Moo 7 BoWin, Sriracha, Chonburi 20230 
				Tel: 033 -135 020');
        $header->easyCell('', 'img:images/TTV_Logo.png, w30; align:R;');
        $header->printRow();
        $header->endTable(3); */
        // ---------------------------------------------------------------------------------------------

        $header = new easyTable($this->instance, '%{100}', 'border:1;font-family:Trirong;font-size:12; font-style:B;');
        $header->easyCell(utf8Th('Work Order  ( WOD )'), 'valign:M;align:C');
        $header->printRow();
        $header->endTable(5);
        // ---------------------------------------------------------------------------------------------
        $header = new easyTable($this->instance, 6, 'border:0;font-family:Trirong;font-size:6;');
        $header->easyCell("Order No  ( OC ) :", 'valign:T;align:L;font-style:B;');
        $header->easyCell(utf8Th($v[0]['order_no']), 'valign:M;align:L;');
        $header->easyCell("", 'valign:T;align:R;');
        $header->easyCell(utf8Th(''), 'valign:T;align:L;');
        $header->easyCell("Work Order No. :", 'valign:T;align:R;font-style:B;');
        $header->easyCell(utf8Th($v[0]['work_order_no']), 'valign:M;align:L;');
        $header->printRow();
        // ---------------------------------------------------------------------------------------------
        $header->easyCell("Order Date :", 'valign:T;align:L;font-style:B;');
        $header->easyCell(utf8Th($v[0]['order_date']), 'valign:T;align:L;');
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
        $header = new easyTable($this->instance, 6, 'border:0;font-family:Trirong;font-size:6;');
        $header->easyCell("Part No. :", 'valign:T;align:L;font-style:B;');
        $header->easyCell(utf8Th($v[0]['part_no']), 'valign:T;align:L;');

        $header->easyCell("", 'valign:T;align:R;');
        $header->easyCell(utf8Th(''), 'valign:T;align:L;');
        $header->easyCell("Creator :", 'valign:T;align:R;font-style:B;');
        $header->easyCell(utf8Th($v[0]['created_by']), 'valign:M;align:L;');
        $header->printRow();
        // ---------------------------------------------------------------------------------------------
        $header->easyCell("", 'valign:T;align:L;font-style:B;');
        $header->easyCell('', 'valign:T;align:L;');
        $header->easyCell("", 'valign:T;align:R;');
        $header->easyCell(utf8Th(''), 'valign:T;align:L;');;
        $header->easyCell("Created At :", 'valign:T;align:R;font-style:B;');
        $header->easyCell(utf8Th($v[0]['created_at']), 'valign:M;align:L;');
        $header->printRow();
        $header->endTable(6);


        // $this->instance->Code128(90, 11, $v[0]['work_order_no'], 50, 7);
        // $x = 104;
        // $y = 14 + 7 + 2;
        // $this->instance->SetFont('Arial', '', 8);

        // $this->instance->Text($x, $y, $v[0]['work_order_no']);


        // -------------------------------------headdetail- Table--------------------------------------------------------
        $headdetail = new easyTable(
            $this->instance,
            '%{5,15,18,10,7,7,10,7,7,7,7}',
            'border:1;font-family:Trirong;font-size:5; font-style:B; valign:M;'
        );
        $headdetail->rowStyle('align:{RCCCCCCCCCCC};font-style:B');
        $headdetail->easyCell(utf8Th('Item'), 'align:C');
        $headdetail->easyCell(utf8Th('Part No.'), 'align:C');
        $headdetail->easyCell(utf8Th('Part Description'), 'align:C');
        $headdetail->easyCell(utf8Th('FG Tag No'), 'align:C');
        $headdetail->easyCell(utf8Th('Total Net
        Weight (kg.)'), 'align:C');
        $headdetail->easyCell(utf8Th('Total Qty
        (PCS)'), 'align:C');
        $headdetail->easyCell(utf8Th('Case Tag No'), 'align:C');
        $headdetail->easyCell(utf8Th('Location'), 'align:C');
        $headdetail->easyCell(utf8Th('Weight/PCS
        (kg.)'), 'align:C');
        $headdetail->easyCell(utf8Th('Qty
        (PCS)'), 'align:C');
        $headdetail->easyCell(utf8Th('Net
        Weight (kg.)'), 'align:C');
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
$docno = $headerData[0]['work_order_no'];
$pdf->SetTitle($docno);
$detail = new easyTable($pdf, '%{5,15,18,10,7,7,10,7,7,7,7}', 'border:1;font-family:Trirong;font-size:5;valign:M;');
$data = sizeof($detailData);
// หน้าละ15row
$pagebreak = 20;
$i = 0;
$countrow = 1;
$nn = 1;
$sumqty = 0;
$sumBoxes = 0;
$sumCBM = 0;
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
    if ($detailData[$i]["row_num"] == 1) {
        $detail->easyCell(utf8Th($detailData[$i]["part_no"]), 'align:C;');
        $detail->easyCell(utf8Th($detailData[$i]["part_name"]), 'align:C;');
        $detail->easyCell(utf8Th($detailData[$i]["fg_tag_no"]), 'align:C;');
        $detail->easyCell(utf8Th($detailData[$i]["total_net"]), 'align:C;');
        $detail->easyCell(utf8Th($detailData[$i]["total_qty"]), 'align:C;');
    } else {
        $detail->easyCell(utf8Th(''), 'align:C;');
        $detail->easyCell(utf8Th(''), 'align:C;');
        $detail->easyCell(utf8Th(''), 'align:C;');
        $detail->easyCell(utf8Th(''), 'align:C;');
        $detail->easyCell(utf8Th(''), 'align:C;');
    }

    $detail->easyCell(utf8Th($detailData[$i]["case_tag_no"]), 'align:C;');
    $detail->easyCell(utf8Th($detailData[$i]["location_code"]), 'align:C;');
    $detail->easyCell(utf8Th($detailData[$i]["net_per_pcs"]), 'align:C;'); # code...

    $detail->easyCell(utf8Th($detailData[$i]["qty_per_pallet"]), 'align:C;');
    $detail->easyCell(utf8Th($detailData[$i]["net_per_pallet"]), 'align:C;');
    $detail->printRow();
    $i++;
    $nn++;
}
$detail->endTable(10);

$data = sizeof($noteData);

$j = 0;
// $case_tag_no = $detailData[$i]["case_tag_no"];
$nteData = new easyTable($pdf, '%{100}', 'border:0;font-family:Trirong;font-size:6;');

$nteData->easyCell(utf8Th("<b>Note :</b>  "), 'align:L;border:LRTB;');
$nteData->easyCell('', 'align:C;border:0;');
$nteData->printRow();

while ($j < $data) {
    $nteData->easyCell(utf8Th("<b>" . $noteData[$j]["fg_tag_no"] . " : </b>" . $noteData[$j]["part_tag_no"]), 'align:L;border:LR;');
    $nteData->easyCell('', 'align:C;border:0;');
    $nteData->easyCell('', 'valign:M;align:C;border:LR;font-style:B');
    $nteData->printRow();
    $j++;
}
$nteData->easyCell(utf8Th(''), 'align:L;border:T;');
$nteData->printRow();

$nteData->endTable(15);

$lastfooter = new easyTable($pdf, '%{70,30}', 'border:1;font-family:Trirong;font-size:8;');
$lastfooter->easyCell('', 'valign:M;align:C;border:0;font-style:B');
$lastfooter->easyCell('Checker', 'valign:M;align:C;border:LTBR;font-style:B');
$lastfooter->printRow();

$lastfooter->easyCell('', 'valign:M;align:C;border:0;font-style:B');
$lastfooter->rowStyle('min-height:20');
$lastfooter->easyCell('', 'valign:M;align:C;border:LTBR;font-style:B;');
$lastfooter->printRow();

$lastfooter->easyCell('', 'valign:M;align:C;border:0;font-style:B');
$lastfooter->easyCell('TTV SUPPLYCHAIN CO., LTD. (TTV)', 'align:C;border:1;');
$lastfooter->printRow();

$pdf->Output();

$pdf->Output('F', 'files/WorkOrder/' . $docno . '.pdf');
function utf8Th($v)
{
    return iconv('UTF-8', 'TIS-620//TRANSLIT', $v);
}
