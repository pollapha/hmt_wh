<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'PutAwayPick'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'PutAwayPick'}[0] == 0) {
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
			'obj=>PS_Number:s:0:0',
			'obj=>Package_Number:s:0:0',
			'obj=>Location_Code:s:0:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$sql = "SELECT 
			tph.PS_Number,
			tiv.Package_Number,
			tiv.FG_Serial_Number,
			tiv.Qty,
			tiv.Area,
			tlm.Location_Code
		FROM
			tbl_inventory tiv
				INNER JOIN
			tbl_picking_header tph ON tiv.Picking_Header_ID = tph.Picking_Header_ID
				INNER JOIN
			tbl_picking_pre tpp ON tpp.FG_Serial_Number = tiv.FG_Serial_Number
				LEFT JOIN
			tbl_location_master tlm ON tiv.Location_ID = tlm.Location_ID
		WHERE
			tph.PS_Number = '$PS_Number'
				AND tiv.Package_Number = '$Package_Number'
				AND tpp.status = 'COMPLETE';";

		$re1 = sqlError($mysqli, __LINE__, $sql, 1);

		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else if ($type == 2) {
		$dataParams = array(
			'obj',
			'obj=>PS_Number:s:0:1',
			'obj=>Package_Number:s:0:1',
			'obj=>Location_Code:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {
			$sql = "SELECT 
				BIN_TO_UUID(Picking_Header_ID, TRUE) AS Picking_Header_ID
			FROM
				tbl_picking_header
			WHERE
				PS_Number = '$PS_Number'
					AND Status_Picking = 'COMPLETE';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			$Picking_Header_ID = $re1->fetch_array(MYSQLI_ASSOC)['Picking_Header_ID'];


			$sql = "SELECT 
				Area
			FROM
				tbl_picking_pre
			WHERE
				BIN_TO_UUID(Picking_Header_ID, TRUE) = '$Picking_Header_ID'
					AND Package_Number = '$Package_Number'
					AND Area = 'Pick';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('PS นี้ทำการ Put away ไปเรียบร้อยแล้ว' . __LINE__);
			}

			$sql = "SELECT 
				Area
			FROM
				tbl_picking_pre
			WHERE
				BIN_TO_UUID(Picking_Header_ID, TRUE) = '$Picking_Header_ID'
					AND Package_Number = '$Package_Number'
					AND Area = 'Storage';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}

			$sql = "SELECT 
				BIN_TO_UUID(Location_ID, TRUE) AS Location_ID
			FROM
				tbl_location_master
			WHERE
				Location_Code = '$Location_Code';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล Location' . __LINE__);
			}

			$sql = "SELECT 
				BIN_TO_UUID(Location_ID, TRUE) AS Location_ID, Area
			FROM
				tbl_location_master
			WHERE
				Location_Code = '$Location_Code'
					AND Area = 'Pick';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('Location นี้ไม่อยู่ใน Area Pick' . __LINE__);
			}

			$mysqli->commit();

			$sql = "SELECT 
				tph.PS_Number,
				tiv.Package_Number,
				tiv.FG_Serial_Number,
				tiv.Qty,
				tiv.Area,
				tlm.Location_Code
			FROM
				tbl_inventory tiv
					INNER JOIN
				tbl_picking_header tph ON tiv.Picking_Header_ID = tph.Picking_Header_ID
					LEFT JOIN
				tbl_location_master tlm ON tiv.Location_ID = tlm.Location_ID
			WHERE
				tph.PS_Number = '$PS_Number'
					AND tiv.Package_Number = '$Package_Number';";

			$re1 = sqlError($mysqli, __LINE__, $sql, 1);

			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 3) {
		$dataParams = array(
			'obj',
			'obj=>PS_Number:s:0:0',
			'obj=>Package_Number:s:0:0',
			'obj=>Location_Code:s:0:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				tph.PS_Number,
				tiv.Package_Number,
				tiv.FG_Serial_Number,
				tiv.Qty,
				tiv.Area,
				tlm.Location_Code
			FROM
				tbl_inventory tiv
					INNER JOIN
				tbl_picking_header tph ON tiv.Picking_Header_ID = tph.Picking_Header_ID
					LEFT JOIN
				tbl_location_master tlm ON tiv.Location_ID = tlm.Location_ID
			WHERE
				tph.PS_Number = '$PS_Number'
					AND tiv.Package_Number = '$Package_Number'
					AND Status_Picking = 'COMPLETE';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}

			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'PutAwayPick'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {
	} else if ($type == 12) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'PutAwayPick'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
		$dataParams = array(
			'obj',
			'obj=>PS_Number:s:0:1',
			'obj=>Package_Number:s:0:1',
			'obj=>Location_Code:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				BIN_TO_UUID(Picking_Header_ID, TRUE) AS Picking_Header_ID
			FROM
				tbl_picking_header
			WHERE
				PS_Number = '$PS_Number'
					AND Status_Picking = 'COMPLETE';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			$Picking_Header_ID = $re1->fetch_array(MYSQLI_ASSOC)['Picking_Header_ID'];


			$sql = "SELECT 
				BIN_TO_UUID(Location_ID, TRUE) AS Location_ID
			FROM
				tbl_location_master
			WHERE
				Location_Code = '$Location_Code'
					AND Area = 'Pick';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('Location นี้ไม่อยู่ใน Area Pick' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Location_ID = $row['Location_ID'];
			}


			$sql = "SELECT 
				BIN_TO_UUID(Location_ID, TRUE) AS Location_ID
			FROM
				tbl_inventory
			WHERE
				BIN_TO_UUID(Picking_Header_ID, TRUE) = '$Picking_Header_ID'
					AND Package_Number = '$Package_Number'
					AND Pick_Status = 'Y';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล Location' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Old_Location_ID = $row['Location_ID'];
			}

			$sql = "SELECT 
				Location_Code
			FROM
				tbl_location_master
			WHERE
				BIN_TO_UUID(Location_ID, TRUE) = '$Old_Location_ID';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล Location' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$From_Location_Code = $row['Location_Code'];
			}

			//exit($From_Location_Code.' , '.$Location_Code);

			//อัพเดท Area ใน tbl_picking_pre
			$sql = "UPDATE tbl_picking_pre
			SET 
				Area = 'Pick'
			WHERE
				BIN_TO_UUID(Picking_Header_ID, TRUE) = '$Picking_Header_ID'
					AND Package_Number = '$Package_Number';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			//อัพเดท Area ใน tbl_inventory
			$sql = "UPDATE tbl_inventory tiv 
			SET 
				tiv.Area = 'Pick',
				tiv.Location_ID = UUID_TO_BIN('$Location_ID', TRUE),
				tiv.Last_Updated_DateTime = NOW(),
				tiv.Updated_By_ID = $cBy
			WHERE
				BIN_TO_UUID(tiv.Picking_Header_ID, TRUE) = '$Picking_Header_ID'
					AND tiv.Package_Number = '$Package_Number'";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}
			$sql = "CALL SP_Transaction_Save('PUT AWAY PICK','','$PS_Number','$Package_Number','','$cBy','$From_Location_Code','$Location_Code');";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			//exit($sql);
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
	if ($_SESSION['xxxRole']->{'PutAwayPick'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'PutAwayPick'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else closeDBT($mysqli, 2, 'TYPE ERROR');

$mysqli->close();
exit();
