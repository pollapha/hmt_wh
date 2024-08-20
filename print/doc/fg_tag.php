<?php
include '../fpdf.php';
include '../exfpdf.php';
include '../easyTable.php';
include 'PDF_Code128.php';
include('../../php/connection.php');

$doc = $mysqli->real_escape_string(trim(strtoupper($_REQUEST['data'])));
$dataset = array();
$q1  = "SELECT t3.fg_tag_no, SUM(t3.qty) qty, SUM(t3.net_per_pcs) net_per_pallet, 
t3.package_no, t3.package_type, t3.steel_qty, certificate_no,
part_no, part_name
FROM tbl_order t3
	LEFT JOIN tbl_inventory t4 ON t3.case_tag_no = t4.case_tag_no
    LEFT JOIN tbl_part_master t5 ON t3.part_id = t5.part_id
WHERE t3.fg_tag_no = '$doc';";

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
//$headerData = $dataset[1];

// echo '<pre>';

// print_r($headerData);
// echo '</pre>';

// exit;
// if (isset($array[0])) {
//     $value = $array[0];
// } else {
//     echo "ไม่มี";
// }
class PDF extends PDF_Code128
{
    // function Rotate($angle, $x=-1, $y=-1) {
    //     if($x == -1)
    //         $x = $this->x;
    //     if($y == -1)
    //         $y = $this->y;
    //     if($this->angle != 0)
    //         $this->_out('Q');
    //     $this->angle = $angle;
    //     if($angle != 0) {
    //         $angle *= M_PI/180;
    //         $c = cos($angle);
    //         $s = sin($angle);
    //         $cx = $x * $this->k;
    //         $cy = ($this->h - $y) * $this->k;
    //         $this->_out(sprintf('q %.2F %.2F %.2F %.2F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
    //     }
    // }

    // // Function to add rotated text
    // function RotatedText($x, $y, $txt, $angle) {
    //     // Rotate the text around its origin
    //     $this->Rotate($angle, $x, $y);
    //     $this->Text($x, $y, $txt);
    //     $this->Rotate(0);
    // }
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
$docno = $headerData[0]['fg_tag_no'];
$pdf->SetTitle($docno);
$data = sizeof($headerData);
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
    // -------------------------------------START Table 1--------------------------------------------------------
	$detail = new easyTable($pdf, '%{100}', 'border:0;font-family:Trirong;font-size:14;valign:M;');
	$detail->easyCell(utf8Th(""), 'align:C;font-style:B;');
	$detail->printRow();
	$detail->endTable(5);
    // -------------------------------------FG Tag No. Rows--------------------------------------------------------
	$detail = new easyTable($pdf, '%{30,50,20}', 'border:0;font-family:Trirong;font-size:18;valign:M;font-style:B');
	$detail->easyCell(utf8Th('FG Tag No. :'), 'align:C;rowspan:2;border:LTB;');
	$detail->easyCell(utf8Th($headerData[$i]["fg_tag_no"]), 'align:C;rowspan:2;border:TB;');
	$detail->easyCell('', 'img:images/hmt_logo.png, w18;align:C;border:TR;', '');
	$detail->printRow();
	$detail->easyCell('', 'img:images/TTVNEW.jpg, w18;align:C;border:BR;', '');
	$detail->printRow();
    // -------------------------------------Barcode Rows--------------------------------------------------------
	$detail = new easyTable($pdf, '%{20,80}', 'border:1;font-family:Trirong;font-size:16;valign:M;font-style:B;');
	$detail->rowStyle('min-height:16');
	$detail->easyCell(utf8Th('Barcode'), 'font-size:10;valign:M; align:C; rotate:90;');
	$detail->easyCell(utf8Th(''), 'align:C;');
	$pdf->Code128($x + 71, $y + 28.5, $headerData[$i]['fg_tag_no'], 88, 10);
	$detail->printRow();
    // -------------------------------------Part No Rows--------------------------------------------------------
	$detail = new easyTable($pdf, '%{30,70}', 'border:1;font-family:Trirong;font-size:15;valign:M;line-height:2');
	$detail->easyCell(utf8Th('Part No'), 'align:L;font-style:B;');
	$detail->easyCell(utf8Th($headerData[$i]["part_no"]), 'align:C;');
	$detail->printRow();
    // -------------------------------------Part Description Rows--------------------------------------------------------
	$detail = new easyTable($pdf, '%{30,70}', 'border:1;font-family:Trirong;font-size:15;valign:M;line-height:2');
	$detail->easyCell(utf8Th('Part Description'), 'align:L;font-style:B;');
	$detail->easyCell(utf8Th($headerData[$i]["part_name"]), 'align:C;');
	$detail->printRow();
    // -------------------------------------Coil Lot NO Rows--------------------------------------------------------
	$detail = new easyTable($pdf, '%{30,30,40}', 'border:1;font-family:Trirong;font-size:15;valign:M;line-height:2');
	$detail->easyCell(utf8Th('Certificate NO'), 'align:L;font-style:B;');
	$detail->easyCell(utf8Th($headerData[$i]["certificate_no"]), 'border:1;align:C;');
	$detail->easyCell(utf8Th('QC  : ( Check )'), 'border:LRT;align:C;font-style:B;');
	$detail->printRow();
    // -------------------------------------Q'ty (Pcs.) Rows--------------------------------------------------------
	$detail = new easyTable($pdf, '%{30,30,40}', 'border:LR;font-family:Trirong;font-size:15;valign:M;line-height:2');
	$detail->easyCell(utf8Th("Q'ty (Pcs.)"), 'border:LRBT;align:L;font-style:B;');
	$detail->easyCell(utf8Th($headerData[$i]["qty"]), 'align:C;border:LRBT;');
	$detail->easyCell('', 'align:C;');
	$detail->printRow();
    // -------------------------------------Net Weight (kg.) Rows--------------------------------------------------------
	$detail = new easyTable($pdf, '%{30,30,40}', 'border:LR;font-family:Trirong;font-size:15;valign:M;line-height:2');
	$detail->easyCell('Net Weight (kg.)', 'border:LRBT;align:L;font-style:B;');
	$detail->easyCell(utf8Th($headerData[$i]["net_per_pallet"]), 'align:C;');
	$detail->easyCell('', 'align:C;');
	$detail->printRow();
    // -------------------------------------Package No Rows--------------------------------------------------------
	$detail = new easyTable($pdf, '%{30,30,40}', 'border:LRB;font-family:Trirong;font-size:15;valign:M;line-height:2');
	$detail->easyCell('Package No', 'border:LRBT;align:L;font-style:B;');
	$detail->easyCell(utf8Th($headerData[$i]["package_no"]), 'border:LRBT;align:C;');
	$detail->easyCell('', 'align:C;');
	$detail->printRow();

    // ------------------------------------- END Table 1--------------------------------------------------------
	$detail->endTable(7);
	$detail = new easyTable($pdf, '%{100}', 'border:0;font-family:Trirong;font-size:14;valign:M;');
	$detail->easyCell(utf8Th("------------------------------------------------------------------------------------"), 'align:C;font-style:B;');
	$detail->printRow();
	$detail->endTable(7);
    // -------------------------------------START Table 2--------------------------------------------------------

    $detail = new easyTable($pdf, '%{30,50,20}', 'border:0;font-family:Trirong;font-size:18;valign:M;font-style:B');
	$detail->easyCell(utf8Th('FG Tag No. :'), 'align:C;rowspan:2;border:LTB;');
	$detail->easyCell(utf8Th($headerData[$i]["fg_tag_no"]), 'align:C;rowspan:2;border:TB;');
	$detail->easyCell('', 'img:images/hmt_logo.png, w18;align:C;border:TR;', '');
	$detail->printRow();
	$detail->easyCell('', 'img:images/TTVNEW.jpg, w18;align:C;border:BR;', '');
	$detail->printRow();
    // -------------------------------------Barcode Rows--------------------------------------------------------
	$detail = new easyTable($pdf, '%{20,80}', 'border:1;font-family:Trirong;font-size:16;valign:M;font-style:B;');
	$detail->rowStyle('min-height:16');
	$detail->easyCell(utf8Th('Barcode'), 'font-size:10;valign:M; align:C; rotation:90;');
	$detail->easyCell(utf8Th(''), 'align:C;');
	$pdf->Code128($x + 71, $y + 168.5, $headerData[$i]['fg_tag_no'], 88, 11);
	$detail->printRow();
    // -------------------------------------Part No Rows--------------------------------------------------------
	$detail = new easyTable($pdf, '%{30,70}', 'border:1;font-family:Trirong;font-size:15;valign:M;line-height:2');
	$detail->easyCell(utf8Th('Part No'), 'align:L;font-style:B;');
	$detail->easyCell(utf8Th($headerData[$i]["part_no"]), 'align:C;');
	$detail->printRow();
    // -------------------------------------Part Description Rows--------------------------------------------------------
	$detail = new easyTable($pdf, '%{30,70}', 'border:1;font-family:Trirong;font-size:15;valign:M;line-height:2');
	$detail->easyCell(utf8Th('Part Description'), 'align:L;font-style:B;');
	$detail->easyCell(utf8Th($headerData[$i]["part_name"]), 'align:C;');
	$detail->printRow();
    // -------------------------------------Coil Lot NO Rows--------------------------------------------------------
	$detail = new easyTable($pdf, '%{30,30,40}', 'border:1;font-family:Trirong;font-size:15;valign:M;line-height:2');
	$detail->easyCell(utf8Th('Certificate No.'), 'align:L;font-style:B;');
	$detail->easyCell(utf8Th($headerData[$i]["certificate_no"]), 'border:1;align:C;');
	$detail->easyCell(utf8Th('QC  : ( Check )'), 'border:LRT;align:C;font-style:B;');
	$detail->printRow();
    // -------------------------------------Q'ty (Pcs.) Rows--------------------------------------------------------
	$detail = new easyTable($pdf, '%{30,30,40}', 'border:LR;font-family:Trirong;font-size:15;valign:M;line-height:2');
	$detail->easyCell(utf8Th("Q'ty (Pcs.)"), 'border:LRBT;align:L;font-style:B;');
	$detail->easyCell(utf8Th($headerData[$i]["qty"]), 'align:C;border:LRBT;');
	$detail->easyCell('', 'align:C;');
	$detail->printRow();
    // -------------------------------------Net Weight (kg.) Rows--------------------------------------------------------
	$detail = new easyTable($pdf, '%{30,30,40}', 'border:LR;font-family:Trirong;font-size:15;valign:M;line-height:2');
	$detail->easyCell('Net Weight (kg.)', 'border:LRBT;align:L;font-style:B;');
	$detail->easyCell(utf8Th($headerData[$i]["net_per_pallet"]), 'align:C;');
	$detail->easyCell('', 'align:C;');
	$detail->printRow();
    // -------------------------------------Package No Rows--------------------------------------------------------
	$detail = new easyTable($pdf, '%{30,30,40}', 'border:LRB;font-family:Trirong;font-size:15;valign:M;line-height:2');
	$detail->easyCell('Package No', 'border:LRBT;align:L;font-style:B;');
	$detail->easyCell(utf8Th($headerData[$i]["package_no"]), 'border:LRBT;align:C;');
	$detail->easyCell('', 'align:C;');
	$detail->printRow();
    // ------------------------------------- END Table 2--------------------------------------------------------



	$i++;
}

// $pdf->Output('F', 'files/grn/Fg_tag/' . $docno . '.pdf');
$pdf->Output('F', 'files/Fg_tag/' . $docno . '.pdf');

$pdf->Output();

function utf8Th($v)
{
	return iconv('UTF-8', 'TIS-620//TRANSLIT', $v);
}
