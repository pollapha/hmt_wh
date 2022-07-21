<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'Transaction'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'Transaction'}[0] == 0) {
	echo "{ch:9,data:'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้'}";
	exit();
}

if (!isset($_REQUEST['type'])) {
	echo json_encode(array('ch' => 2, 'data' => 'ข้อมูลไม่ถูกต้อง'));
	exit();
}
$cBy = $_SESSION['xxxID'];
$fName = $_SESSION['xxxFName'];
$type  = intval($_REQUEST['type']);


include('../php/connection.php');
if ($type <= 10) //data
{
	if ($type == 1) {
		$sql = "SELECT 
		trh.GRN_Number,
		tph.PS_Number,
		ts.Package_Number,
		tpm.Part_No,
		ts.Serial_Number,
		ts.Qty,
		Trans_Type,
		From_Area,
		To_Area,
		(SELECT 
				Location_Code
			FROM
				tbl_location_master tlm
			WHERE
				ts.From_Loc_ID = tlm.Location_ID) AS From_Location_Code,
		(SELECT 
				Location_Code
			FROM
				tbl_location_master tlm
			WHERE
				ts.To_Loc_ID = tlm.Location_ID) AS To_Location_Code,
		Pick_Number,
		FIFO_No,
		DATE_FORMAT(ts.Creation_DateTime, '%d/%m/%y %H:%i') AS Creation_DateTime,
		(SELECT 
				user_fName
			FROM
				tbl_user tu
			WHERE
				ts.Created_By_ID = tu.user_id) AS Created_By,
		DATE_FORMAT(ts.Last_Updated_DateTime,
				'%d/%m/%y %H:%i') AS Last_Updated_DateTime,
		(SELECT 
				user_fName
			FROM
				tbl_user tu
			WHERE
				ts.Updated_By_ID = tu.user_id) AS Updated_By
	FROM
		tachi.tbl_transaction ts
			LEFT JOIN
		tbl_receiving_header trh ON ts.Receiving_Header_ID = trh.Receiving_Header_ID
			LEFT JOIN
		tbl_picking_header tph ON ts.Picking_Header_ID = tph.Picking_Header_ID
			LEFT JOIN
		tbl_part_master tpm ON ts.Part_ID = tpm.Part_ID
	ORDER BY Creation_DateTime DESC;";
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'Transaction'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {
	} else if ($type == 12) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'Transaction'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'Transaction'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'Transaction'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else closeDBT($mysqli, 2, 'TYPE ERROR');

$mysqli->close();
exit();
