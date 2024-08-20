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
	t2.remark, date_format(document_date, '%d-%m-%y') document_date
FROM
	tbl_transaction t1
		inner join 
	tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
		inner join
	tbl_part_master t3 ON t2.part_id = t3.part_id
WHERE
	document_no = '$doc'
		AND t1.transaction_type = 'In'
		AND t2.status = 'Complete';";

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
	}
	function Footer()
	{
		$this->SetXY(-30, 2);
		$this->SetFont('Trirong', '', 8);
		//$this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
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
$data = sizeof($detailData);
// หน้าละ15row
$pagebreak = 1;
$i = 0;
$countrow = 1;

$j = 0;

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

	$detail = new easyTable($pdf, '%{30,40,15,15}', 'border:0;font-family:Trirong;font-size:18;valign:M;font-style:B;line-height:2');
	$detail->easyCell(utf8Th('Case Tag No :'), 'align:L;border:LTB;');
	$detail->easyCell(utf8Th($detailData[$i]["case_tag_no"]), 'align:C;border:TB;');
	$detail->easyCell('', 'img:images/hmt_logo.png, w18;align:R;border:T;', '');
	$detail->easyCell('', 'img:images/TTVNEW.jpg, w18;align:C;border:TR;', '');
	$detail->printRow();

	$detail = new easyTable($pdf, '%{30,70}', 'border:1;font-family:Trirong;font-size:16;valign:M;font-style:B;');
	$detail->rowStyle('min-height:18');
	$detail->easyCell(utf8Th('Barcode'), 'font-size:10;valign:M; align:C; rotate:90;');
	$detail->easyCell(utf8Th(''), 'align:C;');
	$pdf->Code128($x + 82, $y + 26.5, $detailData[$i]['case_tag_no'], 85, 12);
	$detail->printRow();

	$detail = new easyTable($pdf, '%{30,70}', 'border:1;font-family:Trirong;font-size:16;valign:M;line-height:2');
	$detail->easyCell(utf8Th('Part No'), 'align:L;font-style:B;');
	$detail->easyCell(utf8Th($detailData[$i]["part_no"]), 'align:C;');
	$detail->printRow();

	$detail = new easyTable($pdf, '%{30,70}', 'border:1;font-family:Trirong;font-size:16;valign:M;line-height:2');
	$detail->easyCell(utf8Th('Part Description'), 'align:L;font-style:B;');
	$detail->easyCell(utf8Th($detailData[$i]["part_name"]), 'align:C;');
	$detail->printRow();

	$detail = new easyTable($pdf, '%{30,70}', 'border:1;font-family:Trirong;font-size:16;valign:M;line-height:2');
	$detail->easyCell(utf8Th('Pallet No'), 'align:L;font-style:B;');
	$detail->easyCell(utf8Th($detailData[$i]["pallet_no"]), 'align:C;');
	$detail->printRow();

	$detail = new easyTable($pdf, '%{30,30,40}', 'border:1;font-family:Trirong;font-size:16;valign:M;line-height:1.5');
	$detail->easyCell(utf8Th("Q'ty (Pcs.)"), 'align:C;font-style:B;bgcolor:#E7E6E6');
	$detail->easyCell(utf8Th('Net Weight (Kg.)'), 'align:C;font-style:B;bgcolor:#E7E6E6');
	$detail->easyCell(utf8Th('Certificate No.'), 'align:C;font-style:B;bgcolor:#E7E6E6;');
	$detail->printRow();

	$detail->easyCell(utf8Th($detailData[$i]["qty"]), 'align:C;');
	$detail->easyCell(utf8Th($detailData[$i]["net_per_pallet"]), 'align:C;');
	$detail->easyCell(utf8Th($detailData[$i]["certificate_no"]), 'align:C;');
	$detail->printRow();

	
	$detail = new easyTable($pdf, '%{30,70}', 'border:1;font-family:Trirong;font-size:16;valign:M;line-height:1.5');
	$detail->easyCell(utf8Th('Date Receive'), 'align:C;font-style:B;');
	$detail->easyCell(utf8Th($detailData[$i]["document_date"]), 'align:C;');
	$detail->printRow();


	$detail->endTable(12);
	$detail = new easyTable($pdf, '%{100}', 'border:0;font-family:Trirong;font-size:14;valign:M;');
	$detail->easyCell(utf8Th("------------------------------------------------------------------------------------"), 'align:C;font-style:B;');
	$detail->printRow();
	$detail->endTable(12);

	$detail = new easyTable($pdf, '%{30,40,15,15}', 'border:0;font-family:Trirong;font-size:18;valign:M;font-style:B;line-height:2');
	$detail->easyCell(utf8Th('Case Tag No :'), 'align:L;border:LTB;');
	$detail->easyCell(utf8Th($detailData[$i]["case_tag_no"]), 'align:C;border:TB;');
	$detail->easyCell('', 'img:images/hmt_logo.png, w18;align:R;border:T;', '');
	$detail->easyCell('', 'img:images/TTVNEW.jpg, w18;align:C;border:TR;', '');
	$detail->printRow();

	$detail = new easyTable($pdf, '%{30,70}', 'border:1;font-family:Trirong;font-size:16;valign:M;font-style:B;');
	$detail->rowStyle('min-height:18');
	$detail->easyCell(utf8Th('Barcode'), 'align:L;font-style:B;');
	$detail->easyCell(utf8Th(''), 'align:C;');
	$pdf->Code128($x + 82, $y + 171.5, $detailData[$i]['case_tag_no'], 85, 12);
	$detail->printRow();

	$detail = new easyTable($pdf, '%{30,70}', 'border:1;font-family:Trirong;font-size:16;valign:M;line-height:2');
	$detail->easyCell(utf8Th('Part No'), 'align:L;font-style:B;');
	$detail->easyCell(utf8Th($detailData[$i]["part_no"]), 'align:C;');
	$detail->printRow();

	$detail = new easyTable($pdf, '%{30,70}', 'border:1;font-family:Trirong;font-size:16;valign:M;line-height:2');
	$detail->easyCell(utf8Th('Part Description'), 'align:L;font-style:B;');
	$detail->easyCell(utf8Th($detailData[$i]["part_name"]), 'align:C;');
	$detail->printRow();

	$detail = new easyTable($pdf, '%{30,70}', 'border:1;font-family:Trirong;font-size:16;valign:M;line-height:2');
	$detail->easyCell(utf8Th('Pallet No'), 'align:L;font-style:B;');
	$detail->easyCell(utf8Th($detailData[$i]["pallet_no"]), 'align:C;');
	$detail->printRow();

	$detail = new easyTable($pdf, '%{30,30,40}', 'border:1;font-family:Trirong;font-size:16;valign:M;line-height:1.5');
	$detail->easyCell(utf8Th("Q'ty (Pcs.)"), 'align:C;font-style:B;bgcolor:#E7E6E6');
	$detail->easyCell(utf8Th('Net Weight (Kg.)'), 'align:C;font-style:B;bgcolor:#E7E6E6');
	$detail->easyCell(utf8Th('Certificate No.'), 'align:C;font-style:B;bgcolor:#E7E6E6;');
	$detail->printRow();

	$detail->easyCell(utf8Th($detailData[$i]["qty"]), 'align:C;');
	$detail->easyCell(utf8Th($detailData[$i]["net_per_pallet"]), 'align:C;');
	$detail->easyCell(utf8Th($detailData[$i]["certificate_no"]), 'align:C;');
	$detail->printRow();

	
	$detail = new easyTable($pdf, '%{30,70}', 'border:1;font-family:Trirong;font-size:16;valign:M;line-height:1.5');
	$detail->easyCell(utf8Th('Date Receive'), 'align:C;font-style:B;');
	$detail->easyCell(utf8Th($detailData[$i]["document_date"]), 'align:C;');
	$detail->printRow();

	$case_tag_no = $detailData[$i]['case_tag_no'];

	$i++;
}

$pdf->Output('F', 'files/grn/case_tag/' . $docno . '.pdf');

$pdf->Output();

function utf8Th($v)
{
	return iconv('UTF-8', 'TIS-620//TRANSLIT', $v);
}
