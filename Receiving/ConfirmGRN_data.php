<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
include('../common/common.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'ConfirmGRN'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'ConfirmGRN'}[0] == 0) {
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

		$val = checkTXT($mysqli, $_GET['filter']['value']);

		if (strlen(trim($val)) == 0) {
			echo "[]";
		}

		$sql = "SELECT 
			GRN_Number AS value
		FROM
			tbl_receiving_header
		WHERE
			GRN_Number LIKE '%$val%'
				AND Status_Receiving = 'PENDING'
		LIMIT 5;";

		if ($re1 = $mysqli->query($sql)) {
			echo json_encode(jsonRow($re1, false, 0));
		} else {
			echo "[{ID:0,value:'ERROR'}]";
		}
	} else if ($type == 2) {
		$dataParams = array(
			'obj',
			'obj=>GRN_Number:s:0:1'
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);

		try {

			$sql = "SELECT 
				GRN_Number,
				DATE_FORMAT(Receive_DateTime, '%d/%m/%y %H:%i') AS Receive_DateTime,
				DN_Number,
				Package_Number,
				FG_Serial_Number,
				Qty,
				DATE_FORMAT(Confirm_Receive_DateTime,
						'%d/%m/%y %H:%i') AS Confirm_Receive_DateTime
			FROM
				tbl_receiving_header rh
					INNER JOIN
				tbl_receiving_pre rp ON rp.Receiving_Header_ID = rh.Receiving_Header_ID
			WHERE
				GRN_Number = '$GRN_Number'
					AND Status_Receiving = 'PENDING'
					AND status = 'COMPLETE';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);

			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}

			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}

		$mysqli->commit();

		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else if ($type == 3) {
		$dataParams = array(
			'obj',
			'obj=>GRN_Number:s:0:1'
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$sql = "SELECT 
			GRN_Number,
			DATE_FORMAT(Receive_DateTime, '%d/%m/%y %H:%i') AS Receive_DateTime,
			DN_Number,
			Package_Number,
			FG_Serial_Number,
			Qty,
			DATE_FORMAT(Confirm_Receive_DateTime,
					'%d/%m/%y %H:%i') AS Confirm_Receive_DateTime
		FROM
			tbl_receiving_header rh
				INNER JOIN
			tbl_receiving_pre rp ON rp.Receiving_Header_ID = rh.Receiving_Header_ID
		WHERE
			GRN_Number = '$GRN_Number'
				AND Status_Receiving = 'COMPLETE'
				AND status = 'COMPLETE';";
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);

		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'ConfirmGRN'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {
	} else if ($type == 12) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'ConfirmGRN'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'ConfirmGRN'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'ConfirmGRN'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {
		$dataParams = array(
			'obj',
			'obj=>GRN_Number:s:0:1'
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				BIN_TO_UUID(trh.Receiving_Header_ID, TRUE) AS Receiving_Header_ID,
				Package_Number,
				Area
			FROM
				tbl_receiving_pre trp
					INNER JOIN
				tbl_receiving_header trh ON trp.Receiving_Header_ID = trh.Receiving_Header_ID
			WHERE
				GRN_Number = '$GRN_Number'
					AND Status_Receiving = 'PENDING'
					AND status = 'COMPLETE';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);

			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Receiving_Header_ID = $row['Receiving_Header_ID'];
				$Package_Number = $row['Package_Number'];
				$Area = $row['Area'];
			}

			//exit($sql);

			$sql = "UPDATE tbl_receiving_header 
			SET 
				Status_Receiving = 'COMPLETE',
				Confirm_Receive_DateTime = NOW()
			WHERE
				GRN_Number = '$GRN_Number';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			$sql = "UPDATE tbl_dn_order tdo
					INNER JOIN
				tbl_receiving_header rh ON tdo.DN_Number = rh.DN_Number
					INNER JOIN
				tbl_receiving_pre rp ON tdo.FG_Serial_Number = rp.FG_Serial_Number
			SET 
				Receive_Status = 'Y'
			WHERE
				GRN_Number = '$GRN_Number'
					AND Status_Receiving = 'COMPLETE'
					AND status = 'COMPLETE'
					AND Receive_Status = 'N';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			//อัพเดท Area ใน tbl_receiving_pre
			$sql = "UPDATE tbl_receiving_pre
			SET 
				Area = 'Received'
			WHERE
				BIN_TO_UUID(Receiving_Header_ID, TRUE) = '$Receiving_Header_ID'
					AND Package_Number = '$Package_Number';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			// $sql = "SELECT 
			// 	BIN_TO_UUID(Location_ID, TRUE) AS Location_ID, Location_Code
			// FROM
			// 	tbl_location_master
			// WHERE
			// 	Location_Code = 'N/A';";
			// $re1 = sqlError($mysqli, __LINE__, $sql, 1);

			// if ($re1->num_rows == 0) {
			// 	throw new Exception('ไม่พบข้อมูล' . __LINE__);
			// }
			// while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
			// 	$Location_ID = $row['Location_ID'];
			// }

			$sql = "INSERT INTO tbl_inventory
			(Receiving_Header_ID, Receiveing_Pre_ID, Part_ID, Package_Number, FG_Serial_Number, Qty, Area, Location_ID, Creation_DateTime, Created_By_ID)
			SELECT
				trh.Receiving_Header_ID,
				trp.ID ,
				trp.Part_ID ,
				trp.Package_Number ,
				trp.FG_Serial_Number ,
				trp.Qty ,
				'Received',
				(SELECT Location_ID FROM tbl_location_master WHERE Location_Code = 'N/A'),
				NOW(),
				$cBy
			FROM
				tbl_receiving_header trh
			LEFT JOIN tbl_receiving_pre trp ON
				trh.Receiving_Header_ID = trp.Receiving_Header_ID 
				WHERE trh.GRN_Number = '$GRN_Number'
				ON DUPLICATE KEY UPDATE 
				Last_Updated_DateTime = NOW(),
				Updated_By_ID = '$cBy';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			//exit('s');

			$sp_trans = "CALL SP_Transaction_Save('IN','$GRN_Number','','','','$cBy','','');";

			// echo $sp_trans;exit();

			$re1 = sqlError($mysqli, __LINE__, $sp_trans, 1);

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
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}

			$mysqli->commit();
			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else closeDBT($mysqli, 2, 'TYPE ERROR');

$mysqli->close();
exit();
