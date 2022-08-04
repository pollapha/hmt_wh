<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'Ship'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'Ship'}[0] == 0) {
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
			sh.GTN_Number, sh.Ship_Date
		FROM
			tbl_shipping_header sh
				LEFT JOIN
			tbl_shipping_pre sp ON sh.Shipping_Header_ID = sp.Shipping_Header_ID
		WHERE
			sh.Created_By_ID = $cBy
				AND sh.Status_Shipping = 'PENDING'
				AND (sp.ID IS NULL OR sp.status = 'PENDING')
		GROUP BY sh.GTN_Number;";
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);

		$header = jsonRow($re1, true, 0);
		$body = [];
		if (count($header) > 0) {
			$GTN_Number = $header[0]['GTN_Number'];
			$sql = "SELECT 
				sh.GTN_Number,
				sp.Package_Number,
				sp.FG_Serial_Number,
				sp.Part_No,
				pm.Part_Name,
				sp.Qty
			FROM
				tbl_shipping_pre sp
					INNER JOIN
				tbl_part_master pm ON sp.Part_ID = pm.Part_ID
					INNER JOIN
				tbl_shipping_header sh ON sp.Shipping_Header_ID = sh.Shipping_Header_ID
			WHERE
				sh.GTN_Number = '$GTN_Number'
					AND sp.status = 'PENDING';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$body = jsonRow($re1, true, 0);
		}
		$returnData = ['header' => $header, 'body' => $body];
		//$returnData = ['header' => $header];
		closeDBT($mysqli, 1, $returnData);
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'Ship'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {

		$dataParams = array(
			'obj',
			'obj=>Ship_Date:s:0:1',
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
				Delivery_Date = '$Ship_Date' and Pick_Status = 'COMPLETE';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}

			// สร้างเลขที่เอกสาร GTN
			$GTN_Number = (sqlError($mysqli, __LINE__, "SELECT func_GenRuningNumber('gtn',0) GTN_Number", 1))->fetch_array(MYSQLI_ASSOC)['GTN_Number'];

			//เพิ่ม GTN_Number
			$sql = "INSERT INTO tbl_shipping_header (
				GTN_Number,
				Ship_Date,
				Creation_DateTime,
				Created_By_ID,
				Last_Updated_DateTime,
				Updated_By_ID)
			values('$GTN_Number','$Ship_Date', now(), $cBy, now(), $cBy)";
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
			'obj=>Ship_Date:s:0:1',
			'obj=>GTN_Number:s:0:1',
			'obj=>Package_Number:s:0:1',
			'obj=>FG_Serial_Number:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			// $sql = "SELECT 
			// 	BIN_TO_UUID(Part_ID, TRUE) AS Part_ID
			// FROM
			// 	tbl_inventory
			// WHERE
			// 	Package_Number = '$Package_Number'
			// 		AND FG_Serial_Number = '$FG_Serial_Number'
			// 		AND Area = 'Pick'
			// 		AND Ship_Status = 'N';";
			// $re1 = sqlError($mysqli, __LINE__, $sql, 1);
			// if ($re1->num_rows == 0) {
			// 	throw new Exception('ไม่พบข้อมูล' . __LINE__);
			// }
			// $Part_ID = $re1->fetch_array(MYSQLI_ASSOC)['Part_ID'];

			$sql = "SELECT 
				BIN_TO_UUID(tiv.Part_ID, TRUE) AS Part_ID,
				tiv.FG_Serial_Number
			FROM
				tbl_inventory tiv
					INNER JOIN
				tbl_picking_header tph ON tiv.Picking_Header_ID = tph.Picking_Header_ID
					INNER JOIN
				tbl_picking_pre tpp ON tiv.FG_Serial_Number = tpp.FG_Serial_Number
			WHERE
					tiv.Package_Number = '$Package_Number'
					AND tiv.Area = 'Pick'
					AND tpp.Area = 'Pick'
					AND Pick_Status = 'Y'
					AND Ship_Status = 'N'
					AND status = 'COMPLETE';";
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
					AND Delivery_Date = '$Ship_Date';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}

			$sql = "SELECT 
				BIN_TO_UUID(Shipping_Header_ID, TRUE) AS Shipping_Header_ID
			FROM
				tbl_shipping_header
			WHERE
				Ship_Date = '$Ship_Date'
					AND GTN_Number = '$GTN_Number';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Shipping_Header_ID = $row['Shipping_Header_ID'];
			}


			$sql = "INSERT INTO tbl_shipping_pre (
				Shipping_Header_ID,
				Part_ID,
				Part_No,
				Package_Number,
				FG_Serial_Number,
				Qty,
				Area,
				Creation_DateTime)
			SELECT
				UUID_TO_BIN('$Shipping_Header_ID', true),
				UUID_TO_BIN('$Part_ID', true),
				'$Part_No',
				'$Package_Number',
				'$FG_Serial_Number', 
				tiv.Qty,
				'Pick',
				now()
			FROM tbl_inventory tiv 
			where Package_Number = '$Package_Number' and FG_Serial_Number = '$FG_Serial_Number' 
			AND Area = 'Pick' 
			AND Ship_Status = 'N'
			;";
			//exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			$sql = "SELECT 
				SUM(Qty) AS Qty
			FROM
				tbl_shipping_pre tsp
					INNER JOIN
				tbl_shipping_header tsh ON tsp.Shipping_Header_ID = tsh.Shipping_Header_ID
			WHERE
				BIN_TO_UUID(tsp.Shipping_Header_ID, TRUE) = '$Shipping_Header_ID'
					AND status = 'PENDING'
			GROUP BY Part_No;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Qty = $row['Qty'];
			}
			//exit($Qty);

			$sql = "SELECT 
				Part_No, SNP
			FROM
				tbl_weld_on_order
					INNER JOIN
				tbl_shipping_header sh ON Ship_Date = Delivery_Date
			WHERE
				$Qty <= SNP AND Part_No = '$Part_No';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('Ship ครบแล้ว' . __LINE__);
			}

			$sql = "UPDATE tbl_inventory 
			SET 
				Shipping_Header_ID = UUID_TO_BIN('$Shipping_Header_ID', TRUE),
				Last_Updated_DateTime = NOW(),
				Updated_By_ID = $cBy
			WHERE
				Package_Number = '$Package_Number'
					AND FG_Serial_Number = '$FG_Serial_Number'
					AND Area = 'Pick'
					AND Ship_Status = 'N';";
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
			'obj=>Ship_Date:s:0:1',
			'obj=>GTN_Number:s:0:1',
			'obj=>Package_Number:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			// $sql = "SELECT 
			// 	BIN_TO_UUID(Part_ID, TRUE) AS Part_ID
			// FROM
			// 	tbl_inventory
			// WHERE
			// 	Package_Number = '$Package_Number'
			// 		AND Area = 'Pick'
			// 		AND Pick_Status = 'Y';";
			// $re1 = sqlError($mysqli, __LINE__, $sql, 1);
			// if ($re1->num_rows == 0) {
			// 	throw new Exception('ไม่พบข้อมูล' . __LINE__);
			// }
			// $Part_ID = $re1->fetch_array(MYSQLI_ASSOC)['Part_ID'];

			$sql = "SELECT 
				BIN_TO_UUID(tiv.Part_ID, TRUE) AS Part_ID,
				tiv.FG_Serial_Number
			FROM
				tbl_inventory tiv
					INNER JOIN
				tbl_picking_header tph ON tiv.Picking_Header_ID = tph.Picking_Header_ID
					INNER JOIN
				tbl_picking_pre tpp ON tiv.FG_Serial_Number = tpp.FG_Serial_Number
			WHERE
					tiv.Package_Number = '$Package_Number'
					AND tiv.Area = 'Pick'
					AND tpp.Area = 'Pick'
					AND Pick_Status = 'Y'
					AND Ship_Status = 'N'
					AND status = 'COMPLETE';";
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
					AND Delivery_Date = '$Ship_Date' 
					AND GTN_No = '';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Weld_On_No = $row['Weld_On_No'];
				$SNP = $row['SNP'];
			}


			$sql = "SELECT 
				BIN_TO_UUID(Shipping_Header_ID, TRUE) AS Shipping_Header_ID
			FROM
				tbl_shipping_header
			WHERE
				Ship_Date = '$Ship_Date'
					AND GTN_Number = '$GTN_Number';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Shipping_Header_ID = $row['Shipping_Header_ID'];
			}


			$sql = "INSERT INTO tbl_shipping_pre (
				Shipping_Header_ID,
				Part_ID,
				Part_No,
				Package_Number,
				FG_Serial_Number,
				Qty,
				Area,
				Creation_DateTime)
				SELECT 
					UUID_TO_BIN('$Shipping_Header_ID', TRUE),
					UUID_TO_BIN('$Part_ID', TRUE),
					'$Part_No',
					tiv.Package_Number,
					tiv.FG_Serial_Number,
					tiv.Qty,
					'Pick',
					NOW()
				FROM
					tbl_inventory tiv
				WHERE
					Package_Number = '$Package_Number'
					AND Area = 'Pick'
					AND Ship_Status = 'N'
					AND Pick_Status = 'Y'
				ORDER BY Creation_DateTime
				LIMIT $SNP;";
			//exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			$sql = "UPDATE tbl_inventory 
			SET 
				Shipping_Header_ID = UUID_TO_BIN('$Shipping_Header_ID', TRUE),
				Last_Updated_DateTime = NOW(),
				Updated_By_ID = $cBy
			WHERE
				Package_Number = '$Package_Number'
					AND Area = 'Pick'
					AND Ship_Status = 'N'
					AND Pick_Status = 'Y'
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
	if ($_SESSION['xxxRole']->{'Ship'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'Ship'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {

		$obj  = $_POST['obj'];
		$explode = explode("/", $obj);
		$GTN_Number  = $explode[0];
		$FG_Serial_Number  = $explode[1];
		//exit($GRN_Number .' , '.$FG_Serial_Number);

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				BIN_TO_UUID(tsh.Shipping_Header_ID, TRUE) AS Shipping_Header_ID,
				Qty
			FROM
				tbl_shipping_pre tsp
					INNER JOIN
				tbl_shipping_header tsh ON tsp.Shipping_Header_ID = tsh.Shipping_Header_ID
			WHERE
				GTN_Number = '$GTN_Number';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Shipping_Header_ID = $row['Shipping_Header_ID'];
				$Qty = $row['Qty'];
			}

			$sql = "DELETE FROM tbl_shipping_pre tsp 
			WHERE
				BIN_TO_UUID(tsp.Shipping_Header_ID, TRUE) = '$Shipping_Header_ID'
					AND FG_Serial_Number = '$FG_Serial_Number';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถลบได้' . __LINE__);
			}

			$sql = "SELECT 
				Qty, SUM(Qty) AS Sum_Qty
			FROM
				tbl_shipping_pre sp
					INNER JOIN
				tbl_shipping_header sh ON sp.Shipping_Header_ID = sh.Shipping_Header_ID
			WHERE
				GTN_Number = '$GTN_Number'
					AND Status_Shipping = 'PENDING';";
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
				tbl_shipping_header 
			WHERE
				GTN_Number = '$GTN_Number';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Total_Qty = $row['Total_Qty'];
			}

			if ($Total_Qty != 0) {
				$sql = "UPDATE tbl_shipping_header
				set Total_Qty = $Sum_Qty
				where GTN_Number = '$GTN_Number';";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถลบข้อมูลได้' . __LINE__);
				}
			}

			$sql = "SELECT 
				Trans_Type,
				To_Area,
				BIN_TO_UUID(To_Loc_ID, TRUE) AS To_Loc_ID,
				(SELECT 
						Location_Code
					FROM
						tbl_location_master
					WHERE
						To_Loc_ID = Location_ID) AS From_Loc
			FROM
				tbl_transaction tts
				INNER JOIN
				tbl_picking_header tph ON tts.Picking_Header_ID = tph.Picking_Header_ID
			WHERE
				tts.Serial_Number = '$FG_Serial_Number'
					AND To_Area = 'Pick'
			ORDER BY tts.Creation_DateTime DESC LIMIT 1;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล Location' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Area = $row['To_Area'];
				$Loc_ID = $row['To_Loc_ID'];
			}
			//exit($Area.' , '.$Loc_ID);

			$sql = "UPDATE tbl_inventory 
			SET 
				Shipping_Header_ID = NULL,
				Ship_Status = 'N',
				Ship_Number = NULL,
				Area = '$Area',
				Location_ID = UUID_TO_BIN('$Loc_ID', TRUE)
			WHERE
				BIN_TO_UUID(Shipping_Header_ID, TRUE) = '$Shipping_Header_ID'
					AND FG_Serial_Number = '$FG_Serial_Number';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถลบข้อมูลได้' . __LINE__);
			}


			//exit('ลบสำเร็จ');

			$mysqli->commit();
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}

		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'Ship'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {

		$dataParams = array(
			'obj',
			'obj=>Ship_Date:s:0:1',
			'obj=>GTN_Number:s:0:1'
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				BIN_TO_UUID(sh.Shipping_Header_ID, TRUE) AS Shipping_Header_ID,
				Part_No,
				SUM(Qty) AS Qty
			FROM
				tbl_shipping_pre rp
					INNER JOIN
				tbl_shipping_header sh ON rp.Shipping_Header_ID = sh.Shipping_Header_ID
			WHERE
				GTN_Number = '$GTN_Number'
					AND status = 'PENDING'
			GROUP BY Part_No;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Qty = $row['Qty'];
				$Part_No = $row['Part_No'];
				$Shipping_Header_ID = $row['Shipping_Header_ID'];
			}

			$sql = "SELECT 
				Part_No, SNP
			FROM
				tbl_weld_on_order
					INNER JOIN
				tbl_shipping_header sh ON Ship_Date = Delivery_Date
			WHERE
				SNP = $Qty AND Part_No = '$Part_No'";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ยังไม่ครบ' . __LINE__);
			}

			$sql = "UPDATE tbl_shipping_pre 
			SET 
				status = 'COMPLETE',
				Area = 'Pick'
			WHERE
				BIN_TO_UUID(Shipping_Header_ID, TRUE) = '$Shipping_Header_ID'
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
