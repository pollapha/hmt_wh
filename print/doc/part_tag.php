<?php
include '../fpdf.php';
include '../exfpdf.php';
include '../easyTable.php';
include 'PDF_Code128.php';
include('../../php/connection.php');

$doc = $mysqli->real_escape_string(trim(strtoupper($_REQUEST['data'])));
$dataset = array();
$q1  = "SELECT 
	part_no, part_name, part_tag_no, 
	t2.qty, t2.net_per_pcs, certificate_no,
    substring_index(substring_index(part_tag_no, '-', 2), '-', -1) row_num
FROM
	tbl_inventory t1
	inner join tbl_inventory_detail t2 on t1.inventory_id = t2.inventory_id
	inner join tbl_part_master t3 ON t2.part_id = t3.part_id
WHERE
	part_tag_no = '$doc';";

/* $q1  .= "SELECT 
	part_no, part_name, pallet_no, case_tag_no, 
	qty, gross_kg, net_per_pallet, certificate_no, 
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
		AND t2.status = 'Complete';"; */

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
//$lineData = $dataset[1];
//$headerData = $dataset[2];


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
	}
	function Footer()
	{
		// $this->SetXY(-30, 2);
		// $this->SetFont('Trirong', '', 8);
		// $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
	}
}

$pdf = new PDF('P');

$pdf->AddFont('Trirong', '', 'Trirong-Regular.php');
$pdf->AddFont('Trirong', 'I', 'Trirong-Italic.php');
$pdf->AddFont('Trirong', 'B', 'Trirong-Bold.php');
$pdf->AddFont('Trirong', 'BI', 'Trirong-BoldItalic.php');
$pdf->setInstance($pdf);
$pdf->setHeaderData($headerData);
$docno = $headerData[0]['part_tag_no'];
$pdf->SetTitle($docno);
$data = sizeof($headerData);
$pdf->AddPage();
// หน้าละ15row
$pagebreak = 4;
$i = 0;
$countrow = 1;

$i = 0;

while ($i <  $data) {

	if ($countrow > $pagebreak) {
		$pdf->AddPage();
		$countrow = 1;
	}
	$countrow++;
	$x = $pdf->GetX();
	$y = $pdf->GetY();


	$detail = new easyTable($pdf, '%{100}', 'border:0;font-family:Trirong;font-size:14;valign:M;');
	$detail->easyCell(utf8Th(""), 'align:C;font-style:B;');
	$detail->printRow();
	$detail->endTable(5);

	$detail = new easyTable($pdf, '%{25,60,15}', 'border:1;font-family:Trirong;font-size:12;valign:M;line-height:1.5');
	$detail->easyCell(utf8Th('Part No :'), 'align:L;');
	$detail->easyCell(utf8Th($headerData[$i]["part_no"]), 'align:C;font-style:B');
	$detail->easyCell('', 'img:images/TTVNEW.jpg, w20;align:C;rowspan:2;', '');
	$detail->printRow();

	$detail->easyCell(utf8Th('Part Description :'), 'align:L;');
	$detail->easyCell(utf8Th($headerData[$i]["part_name"]), 'align:C;');
	$detail->printRow();

	$detail = new easyTable($pdf, '%{25,60,15}', 'border:1;font-family:Trirong;font-size:12;valign:M;');
	$detail->rowStyle('min-height:15');
	$detail->easyCell(utf8Th('Barcode :'), 'align:L;');
	$detail->easyCell(utf8Th(''), 'align:C;');
	$pdf->Code128($x + 65, $y + 27, $headerData[$i]['part_tag_no'], 80, 12);
	$detail->easyCell(utf8Th($headerData[$i]["row_num"]), 'align:C;');
	$detail->printRow();

	$detail = new easyTable($pdf, '%{25,20,30,25}', 'border:1;font-family:Trirong;font-size:12;valign:M;line-height:1.5');
	$detail->easyCell(utf8Th('Net Weight (Kg.)'), 'align:C;font-style:B;bgcolor:#E7E6E6');
	$detail->easyCell(utf8Th("Q'ty (Pcs.)"), 'align:C;font-style:B;bgcolor:#E7E6E6');
	$detail->easyCell(utf8Th('Certificate No'), 'align:C;font-style:B;bgcolor:#E7E6E6;');
	$detail->easyCell(utf8Th('Part Tag No'), 'align:C;font-style:B;bgcolor:#E7E6E6;');
	$detail->printRow();

	$detail->easyCell(utf8Th($headerData[$i]["net_per_pcs"]), 'align:C;');
	$detail->easyCell(utf8Th($headerData[$i]["qty"]), 'align:C;');
	$detail->easyCell(utf8Th($headerData[$i]["certificate_no"]), 'align:C;');
	$detail->easyCell(utf8Th($headerData[$i]["part_tag_no"]), 'align:C;');
	$detail->printRow();

	$detail->endTable(5);

	$i++;
}

//$pdf->Output('F', 'files/grn/part_tag/' . $docno . '.pdf');

$pdf->Output();

function utf8Th($v)
{
	return iconv('UTF-8', 'TIS-620//TRANSLIT', $v);
}
