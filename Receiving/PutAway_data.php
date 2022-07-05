<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'PutAway'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'PutAway'}[0] == 0) {
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
			'obj=>GRN_Number:s:0:0',
			'obj=>Package_Number:s:0:0',
			'obj=>Location_Code:s:0:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$sql = "SELECT trh.GRN_Number,
		tiv.Package_Number,
		tiv.FG_Serial_Number,
		tiv.Qty,
		tiv.Area,
		tlm.Location_Code
		FROM tbl_inventory tiv
		inner join tbl_receiving_header trh on tiv.Receiving_Header_ID = trh.Receiving_Header_ID
		left join tbl_location_master tlm on tiv.Location_ID = tlm.Location_ID
		where trh.GRN_Number = '$GRN_Number' and tiv.Package_Number = '$Package_Number';";

		$re1 = sqlError($mysqli, __LINE__, $sql, 1);

		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else if ($type == 2) {
		$dataParams = array(
			'obj',
			'obj=>GRN_Number:s:0:1',
			'obj=>Package_Number:s:0:1',
			'obj=>Location_Code:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {
			$sql = "SELECT
			Receiving_Header_ID
			from tbl_receiving_header
			where GRN_Number = '$GRN_Number' and Status_Receiving = 'COMPLETE'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			$Receiving_Header_ID = $re1->fetch_array(MYSQLI_ASSOC)['Receiving_Header_ID'];

			$sql = "SELECT
			Area
			from tbl_inventory
			where Receiving_Header_ID = '$Receiving_Header_ID' 
			and Package_Number = '$Package_Number' and Area = 'Storage'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('GRN นี้ทำการ Put away ไปเรียบร้อยแล้ว' . __LINE__);
			}

			$sql = "SELECT
			Area
			from tbl_inventory
			where Receiving_Header_ID = '$Receiving_Header_ID' 
			and Package_Number = '$Package_Number' and Area = 'Received'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}

			$sql = "SELECT
			Location_ID
			from tbl_location_master where Location_Code = '$Location_Code'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}

			$sql = "SELECT
			Location_ID,
			Area
			from tbl_location_master where Location_Code = '$Location_Code' and Area = 'Storage';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('Location นี้ไม่อยู่ใน Area Storage' . __LINE__);
			}

			$mysqli->commit();

			$sql = "SELECT trh.GRN_Number,
		tiv.Package_Number,
		tiv.FG_Serial_Number,
		tiv.Qty,
		tiv.Area,
		tlm.Location_Code
		FROM tbl_inventory tiv
		inner join tbl_receiving_header trh on tiv.Receiving_Header_ID = trh.Receiving_Header_ID
		left join tbl_location_master tlm on tiv.Location_ID = tlm.Location_ID
		where trh.GRN_Number = '$GRN_Number' and tiv.Package_Number = '$Package_Number';";

			$re1 = sqlError($mysqli, __LINE__, $sql, 1);

			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'PutAway'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {
	} else if ($type == 12) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'PutAway'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
		$dataParams = array(
			'obj',
			'obj=>GRN_Number:s:0:1',
			'obj=>Package_Number:s:0:1',
			'obj=>Location_Code:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {
			$sql = "SELECT
			Receiving_Header_ID
			from tbl_receiving_header
			where GRN_Number = '$GRN_Number' and Status_Receiving = 'COMPLETE'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			$Receiving_Header_ID = $re1->fetch_array(MYSQLI_ASSOC)['Receiving_Header_ID'];

			$sql = "SELECT
			Area
			from tbl_inventory
			where Receiving_Header_ID = '$Receiving_Header_ID' 
			and Package_Number = '$Package_Number' and Area = 'Storage'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('GRN นี้ทำการ Put away ไปเรียบร้อยแล้ว' . __LINE__);
			}

			$sql = "SELECT
			Area
			from tbl_inventory
			where Receiving_Header_ID = '$Receiving_Header_ID' 
			and Package_Number = '$Package_Number' and Area = 'Received'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}

			$sql = "SELECT
			Location_ID
			from tbl_location_master where Location_Code = '$Location_Code'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}

			$sql = "SELECT
			Location_ID,
			Area
			from tbl_location_master where Location_Code = '$Location_Code' and Area = 'Storage';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('Location นี้ไม่อยู่ใน Area Storage' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Area = $row['Area'];
				$Location_ID = $row['Location_ID'];
			}

			$sql = "SELECT
			Area
			from tbl_inventory
			where Receiving_Header_ID = '$Receiving_Header_ID' ";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Area_receive = $row['Area'];
			}

			//อัพเดท Area ใน tbl_inventory
			$sql = "UPDATE tbl_inventory tiv
			left join tbl_receiving_header trh on tiv.Receiving_Header_ID = trh.Receiving_Header_ID
			set tiv.Area = 'Storage',
			tiv.Location_ID = '$Location_ID'
			where tiv.Receiving_Header_ID = '$Receiving_Header_ID' and tiv.Package_Number = '$Package_Number' 
			and trh.Status_Receiving = 'COMPLETE' and tiv.Area = 'Received';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			$sql = "CALL SP_Transaction_Save('PUT AWAY','$GRN_Number','$Package_Number','','$cBy','N/A','$Location_Code');";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			// echo $sp_trans;exit();
			//exit($sp_trans);
			if (!$re1) {

				throw new Exception('ERROR, SP');
			} else {

				$row = $re1->fetch_array(MYSQLI_NUM);

				$sp_status = $row[0];

				$sp_ms = $row[1];

				if ($sp_status == '0') {

					throw new Exception($sp_ms);
				} else {
				}
			}

			$mysqli->commit();

			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'PutAway'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'PutAway'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else closeDBT($mysqli, 2, 'TYPE ERROR');

$mysqli->close();
exit();
