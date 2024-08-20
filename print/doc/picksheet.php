<?php
include '../fpdf.php';
include '../exfpdf.php';
include '../easyTable.php';
include 'PDF_Code128.php';
include('../../php/connection.php');

$doc = $mysqli->real_escape_string(trim(strtoupper($_REQUEST['data'])));
$dataset = array();
$q1  = "SELECT
document_no, document_date
FROM
tbl_transaction t1
WHERE document_no = '$doc';";

$q1  .= "SELECT 
BIN_TO_UUID(t1.transaction_id, TRUE) AS transaction_id,
document_no, document_date,
BIN_TO_UUID(t2.transaction_line_id, TRUE) AS transaction_line_id,
t3.item_code,
t3.Item_name,
t2.part_qty
FROM
tbl_transaction t1
	LEFT JOIN
tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
	INNER JOIN
alt_freezone.tbl_item t3 ON t2.part_id = t3.item_id
WHERE document_no = '$doc'
	AND t2.status = 'Complete' 
order by document_date, document_no DESC, transaction_line_id ASC;";
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
		$header = new easyTable($this->instance, '%{85,15}', 'border:LR;font-family:Trirong;');
		$header->easyCell('Albatross Logistics Co.,LTD(Head Office)', 'valign:M;align:L;font-size:10; font-style:B;border:LT');
		$header->easyCell('', 'img:images/abt-logo.gif, w30;align:C;rowspan:2;bgcolor:#000080;border:1', '');
		$header->printRow();
		$header->easyCell('Head Office 336/7 Moo 7 Bowin, Sriracha Chonburi 20230 Thailand
		Phone (66) 38 110 910-2, (66) 38 110 915
		Fax (66) 38 110 916
		E-mail mail@albatrossthai.com', 'valign:M;align:L;font-size:8; font-style:B;border:LB');
		$header->printRow();

		$header = new easyTable($this->instance, '%{100}', 'border:1;font-family:Trirong;font-size:12; font-style:B;');
		$header->easyCell(utf8Th('Pick Sheet'), 'valign:M;align:C');
		$header->printRow();

		$header = new easyTable($this->instance, '%{25,25,25,25}', 'border:0;font-family:Trirong;font-size:8;');
		$header->easyCell("", 'valign:T;align:R;border:L');
		$header->easyCell(utf8Th(''), 'valign:T;align:L;');
		$header->easyCell("Document No. :", 'valign:T;align:R;');
		$header->easyCell(utf8Th($v[0]['document_no']), 'valign:T;align:L;border:R;');
		$header->printRow();
		$header->easyCell("", 'valign:T;align:R;border:L');
		$header->easyCell(utf8Th(''), 'valign:T;align:L;border:0');
		$header->easyCell("Document Date :", 'valign:T;align:R;');
		$header->easyCell(utf8Th($v[0]['document_date']), 'valign:T;align:L;border:R;');
		$header->printRow();
		$header->easyCell("", 'valign:T;align:R;border:L');
		$header->easyCell(utf8Th(''), 'valign:T;align:L;border:0');
		$header->easyCell("", 'border:0;');
		$header->easyCell("", 'border:R;');
		$header->printRow();
		$header->rowStyle('min-height:6');
		$header->easyCell("", 'valign:T;align:R;border:L');
		$header->easyCell(utf8Th(''), 'valign:T;align:LB;border:0;');
		$header->easyCell("", 'border:B;');
		$header->easyCell("", 'border:BR;');
		$header->printRow();
		//$this->instance->Code128(20, 48, $v[0]['case_no'], 50, 7);
		$this->instance->Code128(140, 48, $v[0]['document_no'], 50, 7);

		$headdetail = new easyTable(
			$this->instance,
			'%{10,25,50,15}',
			'border:1;font-family:Trirong;font-size:7; font-style:B; valign:M;'
		);
		$headdetail->easyCell(utf8Th('No.'), 'align:C');
		$headdetail->easyCell(utf8Th('Part No.'), 'align:C');
		$headdetail->easyCell(utf8Th('Part Description'), 'align:C');
		$headdetail->easyCell(utf8Th('Qty'), 'align:C');
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
$docno = $headerData[0]['document_no'];
$pdf->SetTitle($docno);
$detail = new easyTable($pdf, '%{10,25,50,15}', 'border:1;font-family:Trirong;font-size:6;valign:M;');
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
	if ($countrow > $pagebreak) {
		$pdf->AddPage();
		$countrow = 1;
	}
	$countrow++;
	$x = $pdf->GetX();
	$y = $pdf->GetY();
	$detail->easyCell(utf8Th($nn), 'align:C');
	$detail->easyCell(utf8Th($detailData[$i]["item_code"]), 'align:L;');
	$detail->easyCell(utf8Th($detailData[$i]["Item_name"]), 'align:L;');
	$detail->easyCell(utf8Th($detailData[$i]["part_qty"]), 'align:R;');
	$detail->printRow();
	$sumqty += $detailData[$i]['part_qty'];
	$i++;
	$nn++;
}


$detail->easyCell(utf8Th(''), 'align:C;border:0;');
$detail->easyCell(utf8Th(''), 'align:C;border:0;');
$detail->easyCell(utf8Th(''), 'align:C;border:0;');
$detail->easyCell(utf8Th($sumqty), 'align:R;font-style:B;font-size:8;');
$detail->printRow();
$detail->endTable(8);



$pdf->SetY(240);
$lastfooter = new easyTable($pdf, '%{30,70}', 'border:1;font-family:Trirong;font-size:8;');
$lastfooter->easyCell(utf8Th('Data Entry By :'), 'align:C;');
$lastfooter->easyCell('', 'align:C;border:0;');
$lastfooter->printRow();
$lastfooter->rowStyle('min-height:20');
$lastfooter->easyCell(utf8Th(''), 'align:C;');
$lastfooter->easyCell('', 'align:C;border:0;');
$lastfooter->printRow();
$lastfooter->easyCell(utf8Th('Albatross Logistics'), 'align:C;');
$lastfooter->easyCell('', 'align:C;border:0;');
$lastfooter->printRow();

$pdf->Output();

function utf8Th($v)
{
	return iconv('UTF-8', 'TIS-620//TRANSLIT', $v);
}
