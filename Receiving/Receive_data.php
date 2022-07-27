<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
include('../common/common.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'Receive'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'Receive'}[0] == 0) {
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
			DN_Number AS value
		FROM
			tbl_dn_order
		WHERE
			DN_Number LIKE '%$val%'
				AND Receive_Status = 'N'
		GROUP BY DN_Number
		LIMIT 5;";

		if ($re1 = $mysqli->query($sql)) {
			echo json_encode(jsonRow($re1, false, 0));
		} else {
			echo "[{ID:0,value:'ERROR'}]";
		}
	} else if ($type == 4) {

		$sql = "SELECT 
			trh.GRN_Number, trh.DN_Number
		FROM
			tbl_receiving_header trh
				LEFT JOIN
			tbl_receiving_pre trp ON trh.Receiving_Header_ID = trp.Receiving_Header_ID
		WHERE
			trh.Created_By_ID = $cBy
				AND trh.Status_Receiving = 'PENDING'
				AND (trp.ID IS NULL OR trp.status = 'PENDING')
		GROUP BY trh.GRN_Number;";

		$re1 = sqlError($mysqli, __LINE__, $sql, 1);

		$header = jsonRow($re1, true, 0);

		$body = [];

		if (count($header) > 0) {
			$GRN_Number = $header[0]['GRN_Number'];
			$sql = "SELECT 
				trh.GRN_Number,
				trh.DN_Number,
				trp.Package_Number,
				trp.FG_Serial_Number,
				trp.Part_No,
				tpm.Part_Name,
				trp.Qty
			FROM
				tbl_receiving_pre trp
					INNER JOIN
				tbl_part_master tpm ON trp.Part_ID = tpm.Part_ID
					INNER JOIN
				tbl_receiving_header trh ON trp.Receiving_Header_ID = trh.Receiving_Header_ID
			WHERE
				trh.GRN_Number = '$GRN_Number'
					AND trp.status = 'PENDING';";

			$re1 = sqlError($mysqli, __LINE__, $sql, 1);

			$body = jsonRow($re1, true, 0);
		}

		$returnData = ['header' => $header, 'body' => $body];

		closeDBT($mysqli, 1, $returnData);
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'Receive'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {
		$dataParams = array(
			'obj',
			'obj=>DN_Number:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				DN_Number
			FROM
				tbl_dn_order
			WHERE
				DN_Number = '$DN_Number'
			LIMIT 1;";

			$re1 = sqlError($mysqli, __LINE__, $sql, 1);

			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}

			$DN_Number = $re1->fetch_array(MYSQLI_ASSOC)['DN_Number'];

			// สร้างเลขที่เอกสาร
			$GRN_Number = (sqlError($mysqli, __LINE__, "SELECT func_GenRuningNumber('grn',0) GRN_Number", 1))->fetch_array(MYSQLI_ASSOC)['GRN_Number'];

			// เพิ่ม tbl_receiving_header
			$sql = "INSERT INTO tbl_receiving_header (
				GRN_Number, 
				Receive_DateTime, 
				DN_Number,
				Creation_DateTime,
				Created_By_ID,
				Last_Updated_DateTime,
				Updated_By_ID)
			values('$GRN_Number', now(), '$DN_Number', now(), $cBy, now(), $cBy)";

			sqlError($mysqli, __LINE__, $sql, 1);

			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}
			$mysqli->commit();
			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 12) {

		$dataParams = array(
			'obj',
			'obj=>DN_Number:s:0:1',
			'obj=>GRN_Number:s:0:1',
			'obj=>Package_Number:s:0:1',
			'obj=>FG_Serial_Number:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				Part_No
			FROM
				tbl_dn_order
			WHERE
				DN_Number = '$DN_Number'
					AND Package_Number = '$Package_Number'
					AND FG_Serial_Number = '$FG_Serial_Number';";

			$re1 = sqlError($mysqli, __LINE__, $sql, 1);

			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			$Part_No = $re1->fetch_array(MYSQLI_ASSOC)['Part_No'];
			$Part_Name = getPartName($mysqli, $Part_No);
			$Part_ID = getPartID($mysqli, $Part_No);

			$sql = "SELECT 
				trp.Part_No
			FROM
				tbl_receiving_pre trp
					INNER JOIN
				tbl_receiving_header trh ON trp.Receiving_Header_ID = trh.Receiving_Header_ID
			WHERE
				trp.Part_No = '$Part_No'
					AND trp.Package_Number = '$Package_Number'
					AND trp.FG_Serial_Number = '$FG_Serial_Number'
					AND (trp.status = 'PENDING'
					OR trp.status = 'COMPLETE');";

			$re1 = sqlError($mysqli, __LINE__, $sql, 1);

			if ($re1->num_rows > 0) {
				throw new Exception('Order นี้ได้ทำการเพิ่มไปเรียบร้อยแล้ว' . __LINE__);
			}

			$sql = "SELECT 
				BIN_TO_UUID(Receiving_Header_ID, TRUE) AS Receiving_Header_ID
			FROM
				tbl_receiving_header
			WHERE
				GRN_Number = '$GRN_Number';";

			$re1 = sqlError($mysqli, __LINE__, $sql, 1);

			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}

			$Receiving_Header_ID = $re1->fetch_array(MYSQLI_ASSOC)['Receiving_Header_ID'];

			$sql = "INSERT INTO tbl_receiving_pre (
				Receiving_Header_ID,
				Part_ID,
				Part_No,
				Package_Number,
				FG_Serial_Number,
				Qty,
				Area,
				Creation_DateTime,
				Created_By_ID)
				values (
					uuid_to_bin('$Receiving_Header_ID',true),
				uuid_to_bin('$Part_ID',true),
				'$Part_No',
				'$Package_Number',
				'$FG_Serial_Number',
				1,
				'Received',
				now(),
				$cBy);";

			sqlError($mysqli, __LINE__, $sql, 1);

			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}

			$mysqli->commit();

			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'Receive'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'Receive'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'Receive'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
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
				SUM(Qty) AS Qty
			FROM
				tbl_receiving_pre trp
					INNER JOIN
				tbl_receiving_header trh ON trp.Receiving_Header_ID = trh.Receiving_Header_ID
			WHERE
				GRN_Number = '$GRN_Number';";

			$re1 = sqlError($mysqli, __LINE__, $sql, 1);

			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}

			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Qty = $row['Qty'];
				$Receiving_Header_ID = $row['Receiving_Header_ID'];
			}

			$sql = "UPDATE tbl_receiving_header 
			SET 
				Total_Qty = $Qty
			WHERE
				GRN_Number = '$GRN_Number';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			$sql = "UPDATE tbl_receiving_pre 
			SET 
				status = 'COMPLETE'
			WHERE
				BIN_TO_UUID(Receiving_Header_ID, TRUE) = '$Receiving_Header_ID'
					AND status = 'PENDING';";

			sqlError($mysqli, __LINE__, $sql, 1);

			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
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
