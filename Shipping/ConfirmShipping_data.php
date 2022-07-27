<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'ConfirmShipping'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'ConfirmShipping'}[0] == 0) {
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
			tsh.GTN_Number
		FROM
			tbl_shipping_header tsh
				LEFT JOIN
			tbl_shipping_pre tsp ON tsh.Shipping_Header_ID = tsp.Shipping_Header_ID
		WHERE
			tsh.Created_By_ID = 1
				AND tsh.Status_Shipping = 'PENDING'
				AND (tsp.ID IS NULL
				OR tsp.status = 'COMPLETE')
				AND Total_Qty != 0
		GROUP BY tsh.GTN_Number;";

		$re1 = sqlError($mysqli, __LINE__, $sql, 1);

		$header = jsonRow($re1, true, 0);

		$body = [];

		if (count($header) > 0) {
			$GTN_Number = $header[0]['GTN_Number'];
			$sql = "WITH a AS(
			SELECT 
				GTN_Number,
				DATE_FORMAT(Ship_Date, '%d/%m/%y') AS Ship_Date,
				tsp.Package_Number,
				tsp.FG_Serial_Number,
				tsp.Qty,
				DATE_FORMAT(Confirm_Shipping_DateTime,
						'%d/%m/%y %H:%i') AS Confirm_Shipping_DateTime,
				status,
				Status_Shipping
			FROM
				tbl_inventory tiv
					INNER JOIN
				tbl_shipping_header tsh ON tiv.Shipping_Header_ID = tsh.Shipping_Header_ID
					INNER JOIN
				tbl_shipping_pre tsp ON tsh.Shipping_Header_ID = tsp.Shipping_Header_ID
			WHERE
				GTN_Number = '$GTN_Number'
					AND Status_Shipping = 'PENDING'
					AND status = 'COMPLETE')
			SELECT a.*, Ship_Number from tbl_inventory tiv
			inner join a ON a.FG_Serial_Number = tiv.FG_Serial_Number
			GROUP BY FG_Serial_Number;";

			$re1 = sqlError($mysqli, __LINE__, $sql, 1);

			$body = jsonRow($re1, true, 0);
		}

		$returnData = ['header' => $header, 'body' => $body];

		closeDBT($mysqli, 1, $returnData);
	} else if ($type == 2) {
		$dataParams = array(
			'obj',
			'obj=>GTN_Number:s:0:1'
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);

		try {

			$sql = "WITH a AS(
			SELECT 
				GTN_Number,
				DATE_FORMAT(Ship_Date, '%d/%m/%y') AS Ship_Date,
				tsp.Package_Number,
				tsp.FG_Serial_Number,
				tsp.Qty,
				DATE_FORMAT(Confirm_Shipping_DateTime,
						'%d/%m/%y %H:%i') AS Confirm_Shipping_DateTime,
				status,
				Status_Shipping
			FROM
				tbl_inventory tiv
					INNER JOIN
				tbl_shipping_header tsh ON tiv.Shipping_Header_ID = tsh.Shipping_Header_ID
					INNER JOIN
				tbl_shipping_pre tsp ON tsh.Shipping_Header_ID = tsp.Shipping_Header_ID
			WHERE
				GTN_Number = '$GTN_Number'
					AND Status_Shipping = 'PENDING'
					AND status = 'COMPLETE')
			SELECT a.*, Ship_Number from tbl_inventory tiv
			inner join a ON a.FG_Serial_Number = tiv.FG_Serial_Number
			GROUP BY FG_Serial_Number;";
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
	} else if ($type == 3) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'ConfirmShipping'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {

		$dataParams = array(
			'obj',
			'obj=>GTN_Number:s:0:1',
			'obj=>Package_Number:s:0:1',
			'obj=>FG_Serial_Number:s:0:1'
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
					INNER JOIN
				tbl_shipping_pre tsp ON tsp.Shipping_Header_ID = ti.Shipping_Header_ID
			WHERE
				Status_Shipping = 'COMPLETE'
					AND ti.Package_Number = '$Package_Number'
					AND ti.FG_Serial_Number = '$FG_Serial_Number'
					AND Ship_Number IS NOT NULL
					AND status = 'COMPLETE'
			GROUP BY ti.FG_Serial_Number;";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('Serial_Number นี้ Confirm เรียบร้อยแล้ว' . __LINE__);
			}

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
					AND Status_Shipping = 'PENDING'
					AND ti.Package_Number = '$Package_Number'
					AND ti.FG_Serial_Number = '$FG_Serial_Number'
					AND Ship_Number IS NOT NULL
			GROUP BY ti.FG_Serial_Number;";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('Serial_Number นี้เช็คเรียบร้อยแล้ว' . __LINE__);
			}

			$sql = "SELECT 
				Qty, SUM(Qty) AS Sum_Qty
			FROM
				tbl_shipping_pre sp
					INNER JOIN
				tbl_shipping_header sh ON sp.Shipping_Header_ID = sh.Shipping_Header_ID
			WHERE
				GTN_Number = '$GTN_Number'
					AND Status_Shipping = 'PENDING'
					AND status = 'COMPLETE';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Qty = $row['Qty'];
				$Sum_Qty = $row['Sum_Qty'];
			}

			$sql = "SELECT 
				Total_Qty
			FROM
				tbl_shipping_header sh
					INNER JOIN
				tbl_shipping_pre sp ON sp.Shipping_Header_ID = sh.Shipping_Header_ID
			WHERE
				GTN_Number = '$GTN_Number'
					AND Status_Shipping = 'PENDING'
					AND status = 'COMPLETE';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Total_Qty = $row['Total_Qty'];
			}

			//echo ($Total_Qty);
			//echo ($Sum_Qty);
			//exit();
			if ($Total_Qty < $Sum_Qty) {

				$sql = "UPDATE tbl_shipping_header sh
						INNER JOIN
					tbl_shipping_pre sp ON sp.Shipping_Header_ID = sh.Shipping_Header_ID 
				SET 
					Total_Qty = Total_Qty + Qty
				WHERE
					GTN_Number = '$GTN_Number'
						AND sp.Package_Number = '$Package_Number'
						AND sp.FG_Serial_Number = '$FG_Serial_Number'
						AND Status_Shipping = 'PENDING'
						AND status = 'COMPLETE';";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
				}

				$sql = "SELECT 
					Total_Qty
				FROM
					tbl_shipping_header sh
						INNER JOIN
					tbl_shipping_pre sp ON sp.Shipping_Header_ID = sh.Shipping_Header_ID
				WHERE
					GTN_Number = '$GTN_Number'
						AND Status_Shipping = 'PENDING'
						AND status = 'COMPLETE';";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล' . __LINE__);
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$Total_Qty = $row['Total_Qty'];
				}
				//echo ($Total_Qty);
				//exit();

				$sql = "UPDATE tbl_inventory ti
						INNER JOIN
					tbl_shipping_header sh ON ti.Shipping_Header_ID = sh.Shipping_Header_ID 
				SET 
					Ship_Number = $Total_Qty
				WHERE
					GTN_Number = '$GTN_Number'
						AND ti.Package_Number = '$Package_Number'
						AND ti.FG_Serial_Number = '$FG_Serial_Number'
						AND Status_Shipping = 'PENDING';";
				//exit($sql);
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
				}
			} else {
				throw new Exception('ครบแล้ว' . __LINE__);
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
					AND Status_Shipping = 'PENDING'
			GROUP BY ti.FG_Serial_Number;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}

			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 12) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'ConfirmShipping'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'ConfirmShipping'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'ConfirmShipping'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
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
			BIN_TO_UUID(sh.Shipping_Header_ID,true) as Shipping_Header_ID,
			Ship_Date,
			Part_No,
			Package_Number,
			sum(Qty) as Qty
			from tbl_shipping_pre sp
			inner join tbl_shipping_header sh on sp.Shipping_Header_ID = sh.Shipping_Header_ID
			where GTN_Number = '$GTN_Number' and Status_Shipping = 'PENDING' and status = 'COMPLETE'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Shipping_Header_ID = $row['Shipping_Header_ID'];
				$Ship_Date = $row['Ship_Date'];
				$Part_No = $row['Part_No'];
				$Package_Number = $row['Package_Number'];
				$Qty = $row['Qty'];
			}

			$sql = "SELECT
			Total_Qty
			from tbl_shipping_header ph
			where GTN_Number = '$GTN_Number' and Status_Shipping = 'PENDING' and Total_Qty = $Qty";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่ครบ' . __LINE__);
			}

			$sql = "UPDATE tbl_shipping_header
			set Status_Shipping = 'COMPLETE',
			Confirm_Shipping_DateTime = now()
			where GTN_Number = '$GTN_Number'";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			$sql = "UPDATE tbl_inventory
			set Ship_Status = 'Y'
			where BIN_TO_UUID(Shipping_Header_ID,true) = '$Shipping_Header_ID' and Ship_Status = 'N' limit $Qty";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			$sql = "UPDATE tbl_weld_on_order
			set GTN_No = '$GTN_Number',
			Ship_Qty = $Qty,
			Ship_Status = 'COMPLETE'
			where Part_No = '$Part_No' and Delivery_Date = '$Ship_Date'";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			$sql = "SELECT
			BIN_TO_UUID(Location_ID,true) as Location_ID
			from tbl_inventory where BIN_TO_UUID(Shipping_Header_ID,true) = '$Shipping_Header_ID' 
			and Package_Number = '$Package_Number' and Ship_Status = 'Y'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล Location' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Old_Location_ID = $row['Location_ID'];
			}

			$sql = "SELECT
			Location_Code
			from tbl_location_master where BIN_TO_UUID(Location_ID,true) = '$Old_Location_ID'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล Location' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$From_Location_Code = $row['Location_Code'];
			}

			//exit($Old_Location_ID .' , '.$From_Location_Code);

			$sql = "CALL SP_Transaction_Save('OUT','','$GTN_Number','$Package_Number','','$cBy','$From_Location_Code','N/A');";

			//echo $sql;exit();

			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
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
} else closeDBT($mysqli, 2, 'TYPE ERROR');

$mysqli->close();
exit();
