<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'ConfirmDelivery'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'ConfirmDelivery'}[0] == 0) {
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
		$dataParams = array(
			'obj',
			'obj=>GTN_Number:s:0:1'
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);

		try {

			$sql = "SELECT 
				GTN_Number,
				DATE_FORMAT(Ship_Date, '%d/%m/%y') AS Ship_Date,
				ti.Package_Number,
				ti.FG_Serial_Number,
				ti.Qty,
				DATE_FORMAT(Confirm_Shipping_DateTime,
						'%d/%m/%y %H:%i') AS Confirm_Shipping_DateTime,
				Ship_Number,
				Status_Shipping
			FROM
				tbl_shipping_header sh
					INNER JOIN
				tbl_inventory ti ON ti.Shipping_Header_ID = sh.Shipping_Header_ID
			WHERE
				GTN_Number = '$GTN_Number'
					AND Status_Shipping = 'COMPLETE'
			GROUP BY ti.FG_Serial_Number;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}

		$mysqli->commit();

		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'ConfirmDelivery'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {
	} else if ($type == 12) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'ConfirmDelivery'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'ConfirmDelivery'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'ConfirmDelivery'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {
		$dataParams = array(
			'obj',
			'obj=>GTN_Number:s:0:1'
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {
			$sql = "SELECT 
				GTN_Number,
				ti.Package_Number,
				ti.FG_Serial_Number Status_Shipping
			FROM
				tbl_shipping_header sh
					INNER JOIN
				tbl_inventory ti ON ti.Shipping_Header_ID = sh.Shipping_Header_ID
			WHERE
				GTN_Number = 'GTN2207210001'
					AND Status_Shipping = 'COMPLETE'
			GROUP BY ti.FG_Serial_Number;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}

			$sql = "UPDATE tbl_shipping_header 
			SET 
				Status_Shipping = 'DELIVERY',
				Confirm_Delivery_DateTime = NOW()
			WHERE
				GTN_Number = '$GTN_Number';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			$mysqli->commit();
			$sql = "SELECT 
				GTN_Number,
				DATE_FORMAT(Ship_Date, '%d/%m/%y') AS Ship_Date,
				ti.Package_Number,
				ti.FG_Serial_Number,
				ti.Qty,
				DATE_FORMAT(Confirm_Shipping_DateTime,
						'%d/%m/%y %H:%i') AS Confirm_Shipping_DateTime,
				Ship_Number,
				Status_Shipping
			FROM
				tbl_shipping_header sh
					INNER JOIN
				tbl_inventory ti ON ti.Shipping_Header_ID = sh.Shipping_Header_ID
			WHERE
				GTN_Number = '$GTN_Number'
					AND Status_Shipping = 'DELIVERY'
			GROUP BY ti.FG_Serial_Number;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);

			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else closeDBT($mysqli, 2, 'TYPE ERROR');

$mysqli->close();
exit();
