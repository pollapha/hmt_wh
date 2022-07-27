<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'Pick'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'Pick'}[0] == 0) {
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


include('../common/common.php');
include('../php/connection.php');
if ($type <= 10) //data
{
	if ($type == 1) {

		$sql = "SELECT 
			ph.PS_Number, ph.Pick_Date
		FROM
			tbl_picking_header ph
				LEFT JOIN
			tbl_picking_pre pp ON ph.Picking_Header_ID = pp.Picking_Header_ID
		WHERE
			ph.Created_By_ID = $cBy
				AND ph.Status_Picking = 'PENDING'
				AND (pp.ID IS NULL OR pp.status = 'PENDING')
		GROUP BY ph.PS_Number;";
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);


		$header = jsonRow($re1, true, 0);
		$body = [];
		if (count($header) > 0) {
			$PS_Number = $header[0]['PS_Number'];
			$sql = "SELECT 
				ph.PS_Number,
				pp.Package_Number,
				pp.FG_Serial_Number,
				pp.Part_No,
				pm.Part_Name,
				pp.Qty
			FROM
				tbl_picking_pre pp
					INNER JOIN
				tbl_part_master pm ON pp.Part_ID = pm.Part_ID
					INNER JOIN
				tbl_picking_header ph ON pp.Picking_Header_ID = ph.Picking_Header_ID
			WHERE
				ph.PS_Number = '$PS_Number'
					AND pp.status = 'PENDING';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);

			$body = jsonRow($re1, true, 0);
		}

		$returnData = ['header' => $header, 'body' => $body];

		closeDBT($mysqli, 1, $returnData);
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'Pick'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {

		$dataParams = array(
			'obj',
			'obj=>Pick_Date:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				Delivery_Date
			FROM
				tbl_weld_on_order
			WHERE
				Delivery_Date = '$Pick_Date'
					AND Pick_Status = 'PENDING';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}

			// สร้างเลขที่เอกสาร PS
			$PS_Number = (sqlError($mysqli, __LINE__, "SELECT func_GenRuningNumber('ps',0) PS_Number", 1))->fetch_array(MYSQLI_ASSOC)['PS_Number'];

			//เพิ่ม PS_Number
			$sql = "INSERT INTO tbl_picking_header (
				PS_Number,
				Pick_Date,
				Creation_DateTime,
				Created_By_ID,
				Last_Updated_DateTime,
				Updated_By_ID)
			values('$PS_Number','$Pick_Date', now(), $cBy, now(), $cBy)";
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
			'obj=>Pick_Date:s:0:1',
			'obj=>Package_Number:s:0:1',
			'obj=>FG_Serial_Number:s:0:1',
			// 'obj=>Part_No:s:0:1',
			// 'obj=>Qty:i:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				BIN_TO_UUID(Part_ID, TRUE) AS Part_ID
			FROM
				tbl_inventory
			WHERE
				Package_Number = '$Package_Number'
				AND FG_Serial_Number = '$FG_Serial_Number';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			$Part_ID = $re1->fetch_array(MYSQLI_ASSOC)['Part_ID'];


			$sql = "SELECT 
				Part_No
			FROM
				tbl_part_master
			WHERE
				BIN_TO_UUID(Part_ID, TRUE) = '$Part_ID';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			$Part_No = $re1->fetch_array(MYSQLI_ASSOC)['Part_No'];


			$sql = "SELECT 
				Part_No, SNP
			FROM
				tbl_weld_on_order
			WHERE
				Part_No = '$Part_No'
					AND Delivery_Date = '$Pick_Date';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}


			$sql = "SELECT 
				BIN_TO_UUID(Picking_Header_ID, TRUE) AS Picking_Header_ID
			FROM
				tbl_picking_header
			WHERE
				Pick_Date = '$Pick_Date';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Picking_Header_ID = $row['Picking_Header_ID'];
			}


			$sql = "INSERT INTO tbl_picking_pre (
				Picking_Header_ID,
				Part_ID,
				Part_No,
				Package_Number,
				FG_Serial_Number,
				Qty,
				Creation_DateTime)
			SELECT
				UUID_TO_BIN('$Picking_Header_ID', true),
				UUID_TO_BIN('$Part_ID', true),
				'$Part_No',
				'$Package_Number',
				'$FG_Serial_Number', 
				tiv.Qty,
				now()
			FROM tbl_inventory tiv 
			where Package_Number = '$Package_Number' and FG_Serial_Number = '$FG_Serial_Number' 
			and Area = 'Storage' and Pick_Status = 'N';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}


			$sql = "SELECT 
				SUM(Qty) AS Qty
			FROM
				tbl_picking_pre pp
					INNER JOIN
				tbl_picking_header ph ON pp.Picking_Header_ID = ph.Picking_Header_ID
			WHERE
				BIN_TO_UUID(pp.Picking_Header_ID, TRUE) = '$Picking_Header_ID'
					AND status = 'PENDING'
			GROUP BY Part_No;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Qty = $row['Qty'];
			}


			$sql = "SELECT 
				Part_No, SNP
			FROM
				tbl_weld_on_order
					INNER JOIN
				tbl_picking_header ph ON Pick_Date = Delivery_Date
			WHERE
				$Qty <= SNP AND Part_No = '$Part_No';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('Pick ครบแล้ว' . __LINE__);
			}


			$sql = "UPDATE tbl_inventory 
			SET 
				Picking_Header_ID = UUID_TO_BIN('$Picking_Header_ID', TRUE)
			WHERE
				Package_Number = '$Package_Number'
					AND FG_Serial_Number = '$FG_Serial_Number'
					AND Area = 'Storage'
					AND Pick_Status = 'N';";
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
	} else if ($type == 13) {

		$dataParams = array(
			'obj',
			'obj=>Pick_Date:s:0:1',
			'obj=>Package_Number:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				BIN_TO_UUID(Part_ID, TRUE) AS Part_ID
			FROM
				tbl_inventory
			WHERE
				Package_Number = '$Package_Number'
					AND Pick_Status = 'N';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			$Part_ID = $re1->fetch_array(MYSQLI_ASSOC)['Part_ID'];


			$sql = "SELECT 
				Part_No
			FROM
				tbl_part_master
			WHERE
				BIN_TO_UUID(Part_ID, TRUE) = '$Part_ID';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			$Part_No = $re1->fetch_array(MYSQLI_ASSOC)['Part_No'];


			$sql = "SELECT 
				Weld_On_No, Part_No, SNP, PS_No
			FROM
				tbl_weld_on_order
			WHERE
				Part_No = '$Part_No'
					AND Delivery_Date = '$Pick_Date' 
					AND PS_No = '';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Weld_On_No = $row['Weld_On_No'];
				$SNP = $row['SNP'];
			}


			$sql = "SELECT 
				BIN_TO_UUID(Picking_Header_ID, TRUE) AS Picking_Header_ID
			FROM
				tbl_picking_header
			WHERE
				Pick_Date = '$Pick_Date';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Picking_Header_ID = $row['Picking_Header_ID'];
			}


			$sql = "INSERT INTO tbl_picking_pre (
				Picking_Header_ID,
				Part_ID,
				Part_No,
				Package_Number,
				FG_Serial_Number,
				Qty,
				Creation_DateTime)
				SELECT 
					UUID_TO_BIN('$Picking_Header_ID', TRUE),
					UUID_TO_BIN('$Part_ID', TRUE),
					'$Part_No',
					tiv.Package_Number,
					tiv.FG_Serial_Number,
					tiv.Qty,
					NOW()
				FROM
					tbl_inventory tiv
				WHERE
					Package_Number = '$Package_Number'
					AND Area = 'Storage'
					AND Pick_Status = 'N'
				ORDER BY Creation_DateTime
				LIMIT $SNP;";
			//exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}

			$sql = "UPDATE tbl_inventory 
			SET 
				Picking_Header_ID = UUID_TO_BIN('$Picking_Header_ID', TRUE)
			WHERE
				Package_Number = '$Package_Number'
					AND Area = 'Storage'
					AND Pick_Status = 'N'
					ORDER BY Creation_DateTime limit $SNP;";
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
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'Pick'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'Pick'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'Pick'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {


		$dataParams = array(
			'obj',
			'obj=>Pick_Date:s:0:1',
			'obj=>PS_Number:s:0:1'
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				BIN_TO_UUID(ph.Picking_Header_ID, TRUE) AS Picking_Header_ID,
				Part_No,
				SUM(Qty) AS Qty
			FROM
				tbl_picking_pre rp
					INNER JOIN
				tbl_picking_header ph ON rp.Picking_Header_ID = ph.Picking_Header_ID
			WHERE
				PS_Number = '$PS_Number'
					AND status = 'PENDING'
			GROUP BY Part_No;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Qty = $row['Qty'];
				$Part_No = $row['Part_No'];
				$Picking_Header_ID = $row['Picking_Header_ID'];
			}


			$sql = "SELECT 
				Part_No, SNP
			FROM
				tbl_weld_on_order
					INNER JOIN
				tbl_picking_header ph ON Pick_Date = Delivery_Date
			WHERE
				SNP = $Qty AND Part_No = '$Part_No';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ยังไม่ครบ' . __LINE__);
			}

			$sql = "UPDATE tbl_picking_pre 
			SET 
				status = 'COMPLETE'
			WHERE
				BIN_TO_UUID(Picking_Header_ID, TRUE) = '$Picking_Header_ID'
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
