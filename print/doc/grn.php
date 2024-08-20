<?php
include '../fpdf.php';
include '../exfpdf.php';
include '../easyTable.php';
include 'PDF_Code128.php';
include('../../php/connection.php');

$doc = $mysqli->real_escape_string(trim(strtoupper($_REQUEST['data'])));
$dataset = array();
$q1  = "SELECT
document_no, date_format(document_date, '%d-%b-%y') document_date, declaration_no, container_no, bl_no, invoice_no,
date_format(t1.created_at, '%d-%m-%Y / %h:%s:%i') created_at, t2.user_fName created_by
FROM
tbl_transaction t1
	left join
tbl_user t2 ON t1.created_user_id = t2.user_id
WHERE document_no = '$doc'
	AND t1.transaction_type = 'In';";
	
$q1  .= "SELECT 
	part_no, part_name, pallet_no, case_tag_no, 
	qty, gross_kg, net_per_pallet, certificate_no, coil_lot_no,
	t2.remark
FROM
	tbl_transaction t1
		inner join 
	tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
		inner join
	tbl_part_master t3 ON t2.part_id = t3.part_id
WHERE
	document_no = '$doc'
		AND t1.transaction_type = 'In'
		AND t2.status = 'Complete'
		;";
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

		$this->instance->Code128(95, 13, $v[0]['document_no'], 60, 8);

		$header->endTable(2);

		$header = new easyTable($this->instance, '%{100}', 'border:1;font-family:Trirong;font-size:12; font-style:B;');
		$header->easyCell(utf8Th('Goods Receipt Note (GRN)'), 'valign:M;align:C');
		$header->printRow();

		$header->endTable(3);

		$header = new easyTable($this->instance, '%{25,25,25,25}', 'border:0;font-family:Trirong;font-size:8; line-height:1.3');
		$header->easyCell("Declaration No. :", 'valign:T;align:R;border:0;');
		$header->easyCell(utf8Th($v[0]['declaration_no']), 'valign:T;align:L;border:0;');
		$header->easyCell("Document No. :", 'valign:T;align:R;border:0;');
		$header->easyCell(utf8Th($v[0]['document_no']), 'valign:T;align:L;border:0;');
		$header->printRow();
		$header->easyCell("Container No. :", 'valign:T;align:R;border:0;');
		$header->easyCell(utf8Th($v[0]['container_no']), 'valign:T;align:L;border:0;');
		$header->easyCell("Document Date :", 'valign:T;align:R;border:0;');
		$header->easyCell(utf8Th($v[0]['document_date']), 'valign:T;align:L;border:0;');
		$header->printRow();
		$header->easyCell("BL No. :", 'valign:T;align:R;border:0;');
		$header->easyCell(utf8Th($v[0]['bl_no']), 'valign:T;align:L;border:0;');
		$header->easyCell("Creator :", 'valign:T;align:R;border:0;');
		$header->easyCell(utf8Th($v[0]['created_by']), 'valign:T;align:L;border:0;');
		$header->printRow();
		$header->easyCell("Invoice No. :", 'valign:T;align:R;border:0;');
		$header->easyCell(utf8Th($v[0]['invoice_no']), 'valign:T;align:L;border:0;');
		$header->easyCell("Created At :", 'valign:T;align:R;border:0;');
		$header->easyCell(utf8Th($v[0]['created_at']), 'valign:T;align:L;border:0;');
		$header->printRow();

		$header->endTable(3);

		$headdetail = new easyTable(
			$this->instance,
			'%{5,20,30,10,15,10,10}',
			'border:1;font-family:Trirong;font-size:7; font-style:B; valign:M;'
		);
		$headdetail->easyCell(utf8Th('Item'), 'align:C');
		$headdetail->easyCell(utf8Th('Part No.'), 'align:C');
		$headdetail->easyCell(utf8Th('Part Description'), 'align:C');
		$headdetail->easyCell(utf8Th('Pallet No.'), 'align:C');
		$headdetail->easyCell(utf8Th('Certificate No.'), 'align:C');
		$headdetail->easyCell(utf8Th('Net Weight
		(Kg.)'), 'align:C');
		$headdetail->easyCell(utf8Th('Qty
		(Pcs.)'), 'align:C');
		$headdetail->printRow();
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
$detail = new easyTable($pdf, '%{5,20,30,10,15,10,10}', 'border:1;font-family:Trirong;font-size:6;valign:M;line-height:1.5;');
$data = sizeof($detailData);
// หน้าละ15row
$pagebreak = 30;
$i = 0;
$countrow = 1;
$nn = 1;
$sumqty = 0;
$sumBoxes = 0;
$sumCBM = 0;
while ($i <  $data) {
	//echo($countrow).'<br>';
	if ($countrow > $pagebreak) {
		//echo('new');
		$pdf->AddPage();
		$countrow = 1;
	}
	$countrow++;
	$x = $pdf->GetX();
	$y = $pdf->GetY();

	//$pdf->Code128($x+11, $y+2, $detailData[$i]['item_code'], 60, 10);

	$detail->easyCell(utf8Th($nn), 'align:C');
	$detail->easyCell(utf8Th($detailData[$i]["part_no"]), 'align:L;');
	$detail->easyCell(utf8Th($detailData[$i]["part_name"]), 'align:L;');
	$detail->easyCell(utf8Th($detailData[$i]["pallet_no"]), 'align:C;');
	$detail->easyCell(utf8Th($detailData[$i]["certificate_no"]), 'align:C;');
	$detail->easyCell(utf8Th($detailData[$i]["net_per_pallet"]), 'align:C;');
	$detail->easyCell(utf8Th($detailData[$i]["qty"]), 'align:C;');
	$detail->printRow();
	$sumqty += $detailData[$i]['qty'];
	$i++;
	$nn++;
}

$detail->easyCell(utf8Th(''), 'align:C;border:0;');
$detail->easyCell(utf8Th(''), 'align:C;border:0;');
$detail->easyCell(utf8Th(''), 'align:C;border:0;');
$detail->easyCell(utf8Th(''), 'align:C;border:0;');
$detail->easyCell(utf8Th(''), 'align:C;border:0;');
$detail->easyCell(utf8Th(''), 'align:C;border:0;');
$detail->easyCell(utf8Th($sumqty), 'align:C;font-style:B;font-size:7;');
$detail->printRow();
$detail->endTable(5);

$lastfooter = new easyTable($pdf, '%{60,10,30}', 'border:0;font-family:Trirong;font-size:8;');
$lastfooter->easyCell(utf8Th('Note : '), 'align:L;font-style:B;border:LRT;');
$lastfooter->easyCell('', 'align:C;border:0;');
$lastfooter->easyCell('Checker', 'align:C;border:1;font-style:B');
$lastfooter->printRow();

$lastfooter->easyCell(utf8Th(''), 'align:L;border:LR;');
$lastfooter->easyCell('', 'align:C;border:0;');
$lastfooter->rowStyle('min-height:15');
$lastfooter->easyCell('', 'align:C;border:1;');
$lastfooter->printRow();

$lastfooter->easyCell('', 'align:C;border:LRB;');
$lastfooter->easyCell('', 'align:C;border:0;');
$lastfooter->easyCell('TTV SUPPLYCHAIN CO., LTD. (TTV)', 'align:C;border:1;');
$lastfooter->printRow();

$pdf->Output();

function utf8Th($v)
{
	return iconv('UTF-8', 'TIS-620//TRANSLIT', $v);
}
