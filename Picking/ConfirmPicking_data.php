<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'ConfirmPicking'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'ConfirmPicking'}[0] == 0) {
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
			tph.PS_Number
		FROM
			tbl_picking_header tph
				LEFT JOIN
			tbl_picking_pre tpp ON tph.Picking_Header_ID = tpp.Picking_Header_ID
		WHERE
			tph.Created_By_ID = 1
				AND tph.Status_Picking = 'PENDING'
				AND (tpp.ID IS NULL
				OR tpp.status = 'COMPLETE')
				AND Total_Qty != 0
		GROUP BY tph.PS_Number;";

		$re1 = sqlError($mysqli, __LINE__, $sql, 1);

		$header = jsonRow($re1, true, 0);

		$body = [];

		if (count($header) > 0) {
			$PS_Number = $header[0]['PS_Number'];
			$sql = "WITH a AS(
			SELECT 
				PS_Number,
				DATE_FORMAT(Pick_Date, '%d/%m/%y') AS Pick_Date,
				tpp.Package_Number,
				tpp.FG_Serial_Number,
				tpp.Qty,
				DATE_FORMAT(Confirm_Picking_DateTime,
						'%d/%m/%y %H:%i') AS Confirm_Picking_DateTime,
				status,
				Status_Picking
			FROM
				tbl_inventory tiv
					INNER JOIN
				tbl_picking_header tph ON tiv.Picking_Header_ID = tph.Picking_Header_ID
					INNER JOIN
				tbl_picking_pre tpp ON tph.Picking_Header_ID = tpp.Picking_Header_ID
			WHERE
				PS_Number = '$PS_Number'
					AND Status_Picking = 'PENDING'
					AND status = 'COMPLETE')
			SELECT a.*, Pick_Number from tbl_inventory tiv
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
			'obj=>PS_Number:s:0:1'
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);

		try {

			$sql = "WITH a AS(
			SELECT 
				PS_Number,
				DATE_FORMAT(Pick_Date, '%d/%m/%y') AS Pick_Date,
				tpp.Package_Number,
				tpp.FG_Serial_Number,
				tpp.Qty,
				DATE_FORMAT(Confirm_Picking_DateTime,
						'%d/%m/%y %H:%i') AS Confirm_Picking_DateTime,
				status,
				Status_Picking
			FROM
				tbl_inventory tiv
					INNER JOIN
				tbl_picking_header tph ON tiv.Picking_Header_ID = tph.Picking_Header_ID
					INNER JOIN
				tbl_picking_pre tpp ON tph.Picking_Header_ID = tpp.Picking_Header_ID
			WHERE
				PS_Number = '$PS_Number'
					AND Status_Picking = 'PENDING'
					AND status = 'COMPLETE')
			SELECT a.*, Pick_Number from tbl_inventory tiv
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
	if ($_SESSION['xxxRole']->{'ConfirmPicking'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {

		$dataParams = array(
			'obj',
			'obj=>PS_Number:s:0:1',
			'obj=>Package_Number:s:0:1',
			'obj=>FG_Serial_Number:s:0:1'
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);

		try {

			$sql = "SELECT 
				PS_Number,
				DATE_FORMAT(Pick_Date, '%d/%m/%y') AS Pick_Date,
				ti.Package_Number,
				ti.FG_Serial_Number,
				ti.Qty,
				DATE_FORMAT(Confirm_Picking_DateTime,
						'%d/%m/%y %H:%i') AS Confirm_Picking_DateTime,
				Pick_Number,
				Status_Picking
			FROM
				tbl_picking_header ph
					INNER JOIN
				tbl_inventory ti ON ti.Picking_Header_ID = ph.Picking_Header_ID
					INNER JOIN
				tbl_shipping_pre tsp ON tsp.Shipping_Header_ID = ti.Shipping_Header_ID
			WHERE
				Status_Picking = 'COMPLETE'
					AND ti.Package_Number = '$Package_Number'
					AND ti.FG_Serial_Number = '$FG_Serial_Number'
					AND Pick_Number IS NOT NULL
					AND status = 'COMPLETE'
			GROUP BY ti.FG_Serial_Number;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('Serial_Number นี้ Confirm เรียบร้อยแล้ว' . __LINE__);
			}


			$sql = "SELECT 
				PS_Number,
				DATE_FORMAT(Pick_Date, '%d/%m/%y') AS Pick_Date,
				tiv.Package_Number,
				tiv.FG_Serial_Number,
				tiv.Qty,
				DATE_FORMAT(Confirm_Picking_DateTime,
						'%d/%m/%y %H:%i') AS Confirm_Picking_DateTime,
				Pick_Number,
				Status_Picking
			FROM
				tbl_picking_header tph
					INNER JOIN
				tbl_inventory tiv ON tiv.Picking_Header_ID = tph.Picking_Header_ID
			WHERE
				PS_Number = '$PS_Number'
					AND Status_Picking = 'PENDING'
					AND tiv.Package_Number = '$Package_Number'
					AND tiv.FG_Serial_Number = '$FG_Serial_Number'
					AND Pick_Number IS NOT NULL
			GROUP BY tiv.FG_Serial_Number;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('Serial_Number นี้เช็คเรียบร้อยแล้ว' . __LINE__);
			}


			$sql = "SELECT 
				Qty, SUM(Qty) AS Sum_Qty
			FROM
				tbl_picking_pre pp
					INNER JOIN
				tbl_picking_header ph ON pp.Picking_Header_ID = ph.Picking_Header_ID
			WHERE
				PS_Number = '$PS_Number'
					AND Status_Picking = 'PENDING'
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
				tbl_picking_header ph
					INNER JOIN
				tbl_picking_pre pp ON pp.Picking_Header_ID = ph.Picking_Header_ID
			WHERE
				PS_Number = '$PS_Number'
					AND Status_Picking = 'PENDING'
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
			if ($Total_Qty < $Sum_Qty) {

				$sql = "UPDATE tbl_picking_header tph
						INNER JOIN
					tbl_picking_pre tpp ON tpp.Picking_Header_ID = tph.Picking_Header_ID 
				SET 
					Total_Qty = Total_Qty + Qty
				WHERE
					PS_Number = '$PS_Number'
						AND tpp.Package_Number = '$Package_Number'
						AND tpp.FG_Serial_Number = '$FG_Serial_Number'
						AND Status_Picking = 'PENDING'
						AND status = 'COMPLETE';";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
				}


				$sql = "SELECT 
					Total_Qty
				FROM
					tbl_picking_header ph
						INNER JOIN
					tbl_picking_pre pp ON pp.Picking_Header_ID = ph.Picking_Header_ID
				WHERE
					PS_Number = '$PS_Number'
						AND Status_Picking = 'PENDING'
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
					tbl_picking_header ph ON ti.Picking_Header_ID = ph.Picking_Header_ID 
				SET 
					Pick_Number = $Total_Qty
				WHERE
					PS_Number = '$PS_Number'
						AND ti.Package_Number = '$Package_Number'
						AND ti.FG_Serial_Number = '$FG_Serial_Number'
						AND Status_Picking = 'PENDING';";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
				}
			} else {
				throw new Exception('ครบแล้ว' . __LINE__);
			}

			$mysqli->commit();


			$sql = "SELECT 
				PS_Number,
				DATE_FORMAT(Pick_Date, '%d/%m/%y') AS Pick_Date,
				ti.Package_Number,
				ti.FG_Serial_Number,
				ti.Qty,
				DATE_FORMAT(Confirm_Picking_DateTime,
						'%d/%m/%y %H:%i') AS Confirm_Picking_DateTime,
				Pick_Number,
				Status_Picking
			FROM
				tbl_picking_header ph
					INNER JOIN
				tbl_inventory ti ON ti.Picking_Header_ID = ph.Picking_Header_ID
			WHERE
				PS_Number = '$PS_Number'
					AND Status_Picking = 'PENDING'
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
	if ($_SESSION['xxxRole']->{'ConfirmPicking'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'ConfirmPicking'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'ConfirmPicking'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {
		$dataParams = array(
			'obj',
			'obj=>PS_Number:s:0:1'
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				BIN_TO_UUID(ph.Picking_Header_ID, TRUE) AS Picking_Header_ID,
				Pick_Date,
				Part_No,
				Package_Number,
				SUM(Qty) AS Qty
			FROM
				tbl_picking_pre pp
					INNER JOIN
				tbl_picking_header ph ON pp.Picking_Header_ID = ph.Picking_Header_ID
			WHERE
				PS_Number = '$PS_Number'
					AND Status_Picking = 'PENDING'
					AND status = 'COMPLETE';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Picking_Header_ID = $row['Picking_Header_ID'];
				$Pick_Date = $row['Pick_Date'];
				$Part_No = $row['Part_No'];
				$Package_Number = $row['Package_Number'];
				$Qty = $row['Qty'];
			}


			$sql = "SELECT 
				Total_Qty
			FROM
				tbl_picking_header ph
			WHERE
				PS_Number = '$PS_Number'
					AND Status_Picking = 'PENDING'
					AND Total_Qty = $Qty;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่ครบ' . __LINE__);
			}


			$sql = "UPDATE tbl_picking_header 
			SET 
				Status_Picking = 'COMPLETE',
				Confirm_Picking_DateTime = NOW()
			WHERE
				PS_Number = '$PS_Number';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}


			$sql = "UPDATE tbl_inventory 
			SET 
				Pick_Status = 'Y'
			WHERE
				BIN_TO_UUID(Picking_Header_ID, TRUE) = '$Picking_Header_ID'
					AND Pick_Status = 'N' LIMIT $Qty;";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}


			$sql = "UPDATE tbl_weld_on_order 
			SET 
				PS_No = '$PS_Number',
				Pick_Qty = $Qty,
				Pick_Status = 'COMPLETE'
			WHERE
				Part_No = '$Part_No'
					AND Delivery_Date = '$Pick_Date';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
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

			//exit($Old_Location_ID .' , '.$From_Location_Code);

			$sql = "CALL SP_Transaction_Save('PICKING','','$PS_Number','$Package_Number','','$cBy','$From_Location_Code','N/A');";

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
