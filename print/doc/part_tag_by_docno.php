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
$lineData = $dataset[1];
//$detailData = $dataset[2];


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
$docno = $headerData[0]['document_no'];
$pdf->SetTitle($docno);
$data = sizeof($lineData);
// หน้าละ15row
$pagebreak = 1;
$i = 0;
$countrow = 1;

$j = 0;

while ($i <  $data) {

	$case_tag_no = $lineData[$i]['case_tag_no'];

	$i++;

	$dataset_detail = array();
	$sql = "SELECT 
		part_no, part_name, part_tag_no, certificate_no,
		t3.qty, net_per_pcs,
		concat(ROW_NUMBER() OVER (partition by case_tag_no ORDER BY case_tag_no, part_tag_no),'/',t2.qty) AS row_num
	FROM
		tbl_transaction t1
			inner join 
		tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
			inner join
		tbl_transaction_detail t3 ON t2.transaction_line_id = t3.transaction_line_id
			inner join
		tbl_part_master t4 ON t3.part_id = t4.part_id
	WHERE
		case_tag_no = '$case_tag_no'
			AND t1.transaction_type = 'In'
			AND t2.status = 'Complete';";
	if (!$mysqli->multi_query($sql)) {
		echo "Multi query failed: (" . $mysqli->errno . ") " . $mysqli->error;
	}
	do {
		if ($res = $mysqli->store_result()) {
			array_push($dataset_detail, $res->fetch_all(MYSQLI_ASSOC));
			$res->free();
		}
	} while ($mysqli->more_results() && $mysqli->next_result());
	$detailData = $dataset_detail[0];
	// var_dump($detailData);
	// exit();

	$pagebreakdetail = 4;
	$countrowDetail = 1;
	$j = 0;
	$dataDetail = sizeof($detailData);
	$pdf->AddPage();


	while ($j <  $dataDetail) {

		if ($countrowDetail > $pagebreakdetail) {
			$pdf->AddPage();
			$countrowDetail = 1;
		}
		$countrowDetail++;
		$x = $pdf->GetX();
		$y = $pdf->GetY();


		$detail = new easyTable($pdf, '%{100}', 'border:0;font-family:Trirong;font-size:14;valign:M;');
		$detail->easyCell(utf8Th(""), 'align:C;font-style:B;');
		$detail->printRow();
		$detail->endTable(5);

		$detail = new easyTable($pdf, '%{25,60,15}', 'border:1;font-family:Trirong;font-size:12;valign:M;line-height:1.5');
		$detail->easyCell(utf8Th('Part No :'), 'align:L;');
		$detail->easyCell(utf8Th($detailData[$j]["part_no"]), 'align:C;font-style:B');
		$detail->easyCell('', 'img:images/TTVNEW.jpg, w20;align:C;rowspan:2;', '');
		$detail->printRow();

		$detail->easyCell(utf8Th('Part Description :'), 'align:L;');
		$detail->easyCell(utf8Th($detailData[$j]["part_name"]), 'align:C;');
		$detail->printRow();

		$detail = new easyTable($pdf, '%{25,60,15}', 'border:1;font-family:Trirong;font-size:12;valign:M;');
		$detail->rowStyle('min-height:15');
		$detail->easyCell(utf8Th('Barcode :'), 'align:L;');
		$detail->easyCell(utf8Th(''), 'align:C;');
		$pdf->Code128($x + 65, $y + 27, $detailData[$j]['part_tag_no'], 80, 12);
		$detail->easyCell(utf8Th($detailData[$j]["row_num"]), 'align:C;');
		$detail->printRow();

		$detail = new easyTable($pdf, '%{25,20,30,25}', 'border:1;font-family:Trirong;font-size:12;valign:M;line-height:1.5');
		$detail->easyCell(utf8Th('Net Weight (Kg.)'), 'align:C;font-style:B;bgcolor:#E7E6E6');
		$detail->easyCell(utf8Th("Q'ty (Pcs.)"), 'align:C;font-style:B;bgcolor:#E7E6E6');
		$detail->easyCell(utf8Th('Certificate No'), 'align:C;font-style:B;bgcolor:#E7E6E6;');
		$detail->easyCell(utf8Th('Part Tag No'), 'align:C;font-style:B;bgcolor:#E7E6E6;');
		$detail->printRow();
	
		$detail->easyCell(utf8Th($detailData[$j]["net_per_pcs"]), 'align:C;');
		$detail->easyCell(utf8Th($detailData[$j]["qty"]), 'align:C;');
		$detail->easyCell(utf8Th($detailData[$j]["certificate_no"]), 'align:C;');
		$detail->easyCell(utf8Th($detailData[$j]["part_tag_no"]), 'align:C;');
		$detail->printRow();

		$detail->endTable(5);

		$j++;
	}
}

$pdf->Output('F', 'files/grn/part_tag/' . $docno . '.pdf');

$pdf->Output();

function utf8Th($v)
{
	return iconv('UTF-8', 'TIS-620//TRANSLIT', $v);
}
