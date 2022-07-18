<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'Picking'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'Picking'}[0] == 0) {
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

		$sql = "SELECT Delivery_Date, PS_No
    	FROM tbl_weld_on_order tw
    	WHERE Created_By_ID = $cBy 
		AND Pick_Status = 'PENDING'";
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);

		$header = jsonRow($re1, true, 0);
		$body = [];
		if (count($header) > 0) {
			//$PS_No = $header[0]['PS_No'];
			//$Delivery_Date = $header[0]['Delivery_Date'];
			$sql = "SELECT Customer,
			Dock,
			Delivery_DateTime,
			Qty,
			Weld_On_No,
			Part_No,
			PS_No,
			MMTH_Part_No,
			SNP,
			Part_Descri,
			Package_Type,
			Pick_Qty,
			Pick_Status,
			Ship_Qty,
			Ship_Status,
			Slide_Status,
			Creation_DateTime,
			Created_By_ID,
			Creation_Pick_DateTime,
			Created_Pick_By_ID,
			Creation_Ship_DateTime,
			Created_Ship_By_ID
			FROM tbl_weld_on_order
			where Pick_Status = 'PENDING' 
			order by Delivery_DateTime, Weld_On_No";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$body = jsonRow($re1, true, 0);
		}
		$returnData = ['header' => $header, 'body' => $body];
		//$returnData = ['header' => $header];
		closeDBT($mysqli, 1, $returnData);
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'Picking'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {
		$dataParams = array(
			'obj',
			'obj=>Delivery_Date:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT Delivery_Date FROM tbl_weld_on_order
			where Delivery_Date = '$Delivery_Date'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}

			// สร้างเลขที่เอกสาร PS
			$PS_No = (sqlError($mysqli, __LINE__, "SELECT func_GenRuningNumber('ps',0) PS_No", 1))->fetch_array(MYSQLI_ASSOC)['PS_No'];

			//เพิ่ม PS_No
			$sql = "UPDATE tbl_weld_on_order 
			set PS_No = '$PS_No',
			Created_Pick_By_ID = $cBy,
			Creation_Pick_DateTime = now()
			where Delivery_Date = '$Delivery_Date' and Pick_Status = 'PENDING'";
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
			'obj=>Delivery_Date:s:0:1',
			'obj=>Package_Number:s:0:1',
			'obj=>Part_No:s:0:1',
			'obj=>Qty:i:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT Pick_Qty from tbl_weld_on_order
			where Delivery_Date = '$Delivery_Date' and Part_No = '$Part_No' and Pick_Status = 'PENDING'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Pick_Qty = $row['Pick_Qty'];
			}

			$sql = "SELECT Pick_Qty from tbl_weld_on_order
			where Delivery_Date = '$Delivery_Date' and Part_No = '$Part_No' and Pick_Status = 'PENDING' and  SNP >= $Qty; ";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('จำนวนเกิน SNP' . __LINE__);
			}
			$Part_ID = getPartID($mysqli, $Part_No);

			$sql = "SELECT Pick_Qty from tbl_weld_on_order
			where Delivery_Date = '$Delivery_Date' and Part_No = '$Part_No' and Pick_Status = 'PENDING' and Pick_Qty != 0;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);

			if ($re1->num_rows > 0) {

				//ลบจำนวนเดิมออก
				$sql = "UPDATE tbl_weld_on_order 
					set Pick_Qty = Pick_Qty-$Pick_Qty
					where Delivery_Date = '$Delivery_Date' and Part_No = '$Part_No' and Pick_Status = 'PENDING'";
				sqlError($mysqli, __LINE__, $sql, 1);

				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
				}

				//คืนสถานะ Pick_Status เป็น N 
				$sql = "WITH a AS
				( SELECT ROW_NUMBER() OVER (ORDER BY ti.FG_Serial_Number) AS 'No.', 
				GRN_Number,Package_Number, FG_Serial_Number, ti.Qty, 
				SUM(ti.Qty) OVER (PARTITION BY ti.Package_Number ORDER BY GRN_Number, ti.FG_Serial_Number) as Sum_Qty,
				ti.Pick_Status
				FROM tbl_inventory ti
				inner join tbl_receiving_header trh on trh.Receiving_Header_ID = ti.Receiving_Header_ID) 
				UPDATE tbl_inventory iv
				set iv.Pick_Status = 'N'
				WHERE BIN_TO_UUID(iv.Part_ID, true) = '$Part_ID' and Package_Number = '$Package_Number' 
				and Area = 'Storage' and Pick_Status = 'Y' order by FG_Serial_Number DESC LIMIT $Pick_Qty;";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
				}

				$sql = "SELECT ROW_NUMBER() OVER (ORDER BY ti.FG_Serial_Number) AS 'No.', 
				Package_Number,FG_Serial_Number, ti.Qty, 
				LAST_VALUE(SUM(ti.Qty)) OVER (PARTITION BY ti.Package_Number ORDER BY ti.FG_Serial_Number) as Sum_Qty
				FROM tbl_inventory ti inner join tbl_part_master pm on pm.Part_ID = ti.Part_ID 
				where pm.Part_No = '$Part_No' and Package_Number = '$Package_Number' 
				and Area = 'Storage' and Pick_Status = 'N';";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล' . __LINE__);
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$Sum_Qty = $row['Sum_Qty'];
				}

				$sql = "SELECT Pick_Qty from tbl_weld_on_order
				where Delivery_Date = '$Delivery_Date' and Part_No = '$Part_No' and Pick_Status = 'PENDING' and $Sum_Qty >= $Qty;";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('จำนวนไม่เพียงพอ' . __LINE__);
				}
			} else {

				$sql = "SELECT ROW_NUMBER() OVER (ORDER BY ti.FG_Serial_Number) AS 'No.', 
				Package_Number,FG_Serial_Number, ti.Qty, 
				LAST_VALUE(SUM(ti.Qty)) OVER (PARTITION BY ti.Package_Number ORDER BY ti.FG_Serial_Number) as Sum_Qty
				FROM tbl_inventory ti inner join tbl_part_master pm on pm.Part_ID = ti.Part_ID 
				where pm.Part_No = '$Part_No' and Package_Number = '$Package_Number' 
				and Area = 'Storage' and Pick_Status = 'N';";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล' . __LINE__);
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$Sum_Qty = $row['Sum_Qty'];
				}

				$sql = "SELECT Pick_Qty from tbl_weld_on_order
				where Delivery_Date = '$Delivery_Date' and Part_No = '$Part_No' and Pick_Status = 'PENDING' and $Sum_Qty >= $Qty;";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('จำนวนไม่เพียงพอ' . __LINE__);
				}
			}

			//เพิ่มจำนวน Pick_Qty
			$sql = "UPDATE tbl_weld_on_order 
			set Pick_Qty = Pick_Qty+$Qty,
			Created_Pick_By_ID = $cBy,
			Creation_Pick_DateTime = now()
			where Delivery_Date = '$Delivery_Date' and Part_No = '$Part_No' and Pick_Status = 'PENDING'";
			sqlError($mysqli, __LINE__, $sql, 1);

			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			//เปลี่ยนสถานะ Pick_Status เป็น Y 
			$sql = "WITH a AS
			( SELECT ROW_NUMBER() OVER (ORDER BY ti.FG_Serial_Number) AS 'No.', 
			GRN_Number,Package_Number, FG_Serial_Number, ti.Qty, 
			SUM(ti.Qty) OVER (PARTITION BY ti.Package_Number ORDER BY GRN_Number, ti.FG_Serial_Number ) as Sum_Qty,
			ti.Pick_Status
			FROM tbl_inventory ti
			inner join tbl_receiving_header trh on trh.Receiving_Header_ID = ti.Receiving_Header_ID) 
			UPDATE tbl_inventory iv
			set iv.Pick_Status = 'Y'
			WHERE BIN_TO_UUID(iv.Part_ID, true) = '$Part_ID' and Package_Number = '$Package_Number' 
			and Area = 'Storage' and Pick_Status = 'N' LIMIT $Qty;";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			$mysqli->commit();

			closeDBT($mysqli, 1, jsonRow($re1, true, 0));

			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'Picking'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'Picking'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'Picking'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
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
			BIN_TO_UUID(rh.Receiving_Header_ID,true) as Receiving_Header_ID,
			sum(Qty) as Qty
			from tbl_receiving_pre rp
			inner join tbl_receiving_header rh on rp.Receiving_Header_ID = rh.Receiving_Header_ID
			where GRN_Number = '$GRN_Number'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Qty = $row['Qty'];
				$Receiving_Header_ID = $row['Receiving_Header_ID'];
			}

			$sql = "UPDATE tbl_receiving_header
			set Total_Qty = $Qty
			where GRN_Number = '$GRN_Number'";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			$sql = "UPDATE tbl_receiving_pre
			set status = 'COMPLETE'
			where BIN_TO_UUID(Receiving_Header_ID,true) = '$Receiving_Header_ID' and status = 'PENDING'";
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
