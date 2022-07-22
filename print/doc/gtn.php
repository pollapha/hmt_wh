<?php
include '../fpdf.php';
include '../exfpdf.php';
include '../easyTable.php';
include 'PDF_Code128.php';
include('../../php/connection.php');
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

$doc = $mysqli->real_escape_string(trim(strtoupper($_REQUEST['data'])));
$dataset = array();
$q1  = "SELECT
		GTN_Number,
		Ship_Date,
		date_format(Ship_Date, '%Y-%m-%d') AS Ship_Date,
		Total_Qty,
		Remark,
		Status_Shipping,
		tsh.Creation_DateTime,
		tsh.Created_By_ID,
		tcm.Customer_Name  as Supplier
		FROM
		tbl_shipping_header tsh
		LEFT JOIN tbl_shipping_pre tsp ON
		tsh.Shipping_Header_ID = tsp.Shipping_Header_ID
		INNER JOIN tbl_part_master tpm ON
		tsp.Part_ID = tpm.Part_ID
		INNER JOIN tbl_customer_master tcm ON
		tcm.Customer_ID = tpm.Customer_ID WHERE tsh.GTN_Number= '$doc' GROUP BY tsh.GTN_Number ;";

$q1  .= "SELECT
GTN_Number,
Ship_Date,
date_format(Ship_Date, '%Y-%m-%d') AS Ship_Date,
Total_Qty,
tsp.FG_Serial_Number ,
Remark,
Status_Shipping,
tsh.Creation_DateTime,
tsh.Created_By_ID,
tcm.Customer_Name ,
tsp.Part_No as Part_Number,
tpm.Part_Name ,
tsp.Qty,
tpm2.Package_Type  
FROM
tbl_shipping_header tsh
LEFT JOIN tbl_shipping_pre tsp ON
tsh.Shipping_Header_ID = tsp.Shipping_Header_ID
INNER JOIN tbl_part_master tpm ON
tsp.Part_ID = tpm.Part_ID
INNER JOIN tbl_customer_master tcm ON
tcm.Customer_ID = tpm.Customer_ID
INNER JOIN tbl_package_master tpm2 ON
tpm.Package_ID = tpm2.Package_ID WHERE tsh.GTN_Number= '$doc';";
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
	function __construct($orientation='P', $unit='mm', $format='A4')
	{
		parent::__construct($orientation,$unit,$format);
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
	    $header = new easyTable($this->instance, '%{20, 50, 30,}', 'border:0;font-family:THSarabun;font-size:12; font-style:B;');
		$header->easyCell('', 'img:images/abt-logo.gif, w35;align:L', '');
      $header->easyCell('ALBATROSS LOGISTICS CO., LTD.
	  336/7 MOO 7 BOWIN, SRIRACHA CHONBURI 20230
	  Phone +66 38 058 021, +66 38 058 081-2
	  Fax : +66 38 058 007
	  ', 'valign:M;align:L');
      $header->easyCell($v[0]['GTN_Number'], 'valign:B;align:C');
      $header->printRow();
      $header->endTable(2);
      	

      	$header=new easyTable($this->instance, '%{100}','border:0;font-family:THSarabun;font-size:20; font-style:B;');
      	$header->easyCell(utf8Th('GOODS TRANSFER NOTE'), 'valign:M;align:C;border:TB');
      	$header->printRow();
      	$header->endTable(1);

        $header=new easyTable($this->instance, '%{20,20,15,20,15,10}','border:0;font-family:THSarabun;font-size:13;');
        $header->easyCell("Ship Date Time :", 'valign:T;align:L;font-style:B;');
        $header->easyCell(utf8Th($v[0]['Ship_Date']), 'valign:T;align:L;');
        $header->easyCell("PS Number :", 'valign:T;align:L;font-style:B;');
        $header->easyCell(utf8Th($v[0]['GTN_Number']), 'valign:T;align:L;');
        $header->easyCell("Supplier Name : ", 'valign:T;align:L;font-style:B;');
        $header->easyCell($v[0]['Supplier'], 'valign:T;align:L;');
        $header->printRow();
		// $header->easyCell("DN Number :", 'valign:T;align:L;font-style:B;');
        // $header->easyCell(utf8Th($v[0]['DN_Number']), 'valign:T;align:L;');
        // $header->printRow();
        $header->endTable(2);

	    $headdetail =new easyTable($this->instance, '{10,35,45,50,20,20,55}',
	    'width:300;border:1;font-family:THSarabun;font-size:12; font-style:B;bgcolor:#C8C8C8;');
		$headdetail->easyCell(utf8Th('No.'), 'align:C');
        $headdetail->easyCell(utf8Th('Part Number'), 'align:C');
        $headdetail->easyCell(utf8Th('Part Name'), 'align:C');
        $headdetail->easyCell(utf8Th('Serial Number'), 'align:C');
        $headdetail->easyCell(utf8Th('Qty'), 'align:C');
        $headdetail->easyCell(utf8Th('Package Type'), 'align:C');
        $headdetail->easyCell(utf8Th('Remark'), 'align:C');
		$headdetail->printRow(); 
		$headdetail->endTable(0);

		$this->instance->Code128(145,10,$v[0]['GTN_Number'],55,7);
  	}
  	function Footer()
  	{
  		$this->SetXY(-20,0);
	    $this->SetFont('THSarabun','I',8);
	    $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
  	}
}

$pdf=new PDF('P');

$pdf->AddFont('THSarabun','','THSarabun.php');
$pdf->AddFont('THSarabun','I','THSarabun Italic.php');
$pdf->AddFont('THSarabun','B','THSarabun Bold.php');
$pdf->AddFont('THSarabun','BI','THSarabun Bold Italic.php');
$pdf->setInstance($pdf);
$pdf->setHeaderData($headerData);
$pdf->AddPage();
$docno = $headerData[0]['GTN_Number'];
$pdf->SetTitle($docno);
$detail =new easyTable($pdf, '{10,35,45,50,20,20,55}','width:300;border:1;font-family:THSarabun;font-size:10;valign:M;');
$data = sizeof($detailData);
// หน้าละ15row
$pagebreak = 15;
$i = 0;
$countrow = 1;
$nn = 1;
$sumqty=0;
$sumBoxes=0;
$sumCBM=0;
while ( $i <  $data)
{
if ($countrow > $pagebreak) 
{
  $pdf->AddPage();
  $countrow = 1;
}
$countrow++;
$x=$pdf->GetX();
$y=$pdf->GetY();
$detail->easyCell(utf8Th($nn), 'align:C');
$detail->easyCell(utf8Th($detailData[$i]["Part_Number"]), 'align:C;font-style:B;font-size:10;');
$detail->easyCell(utf8Th($detailData[$i]["Part_Name"]), 'align:C');
$detail->easyCell(utf8Th($detailData[$i]["FG_Serial_Number"]), 'align:C;font-style:B;font-size:10;');
$detail->easyCell(utf8Th($detailData[$i]["Qty"]), 'align:C;font-style:B;font-size:14;');
$detail->easyCell(utf8Th($detailData[$i]["Package_Type"]), 'align:C;font-style:B;font-size:14;');
$detail->easyCell(utf8Th(''), 'align:C;font-style:B;font-size:14;');
$detail->printRow();
$sumqty += $detailData[$i]['Qty'];
$i++;$nn++;

}
$detail->easyCell(utf8Th('Total :'), 'align:R;font-style:B;;colspan:4;font-size:14;');
$detail->easyCell(utf8Th($sumqty), 'align:C;font-size:14;');
$detail->easyCell(utf8Th(''), 'align:C');
$detail->easyCell(utf8Th(''), 'align:C;font-size:14;');
$detail->easyCell(utf8Th(''), 'align:C;colspan:3');
$detail->printRow();
$detail->endTable(10);

$lastfooter =new easyTable($pdf, '%{20,25,20,35}','width:300;border:0;font-family:THSarabun;font-size:12;');
$lastfooter->easyCell(utf8Th('Data Entry By :'), 'align:C;font-size:14;');
$lastfooter->easyCell(utf8Th('____________________'), 'align:C;font-size:14;');
$lastfooter->easyCell(utf8Th('Check By :'), 'align:C;font-size:14;');
$lastfooter->easyCell(utf8Th('____________________  Suppervisor'), 'align:C;font-size:14;');
$lastfooter->printRow();
$lastfooter->endTable(3);

$pdf->Output();

function utf8Th($v)
{
	return iconv( 'UTF-8','TIS-620//TRANSLIT',$v);
}
