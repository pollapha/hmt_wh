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

		$sql = "SELECT ph.PS_Number, ph.Pick_Date
		FROM tbl_picking_header ph
		LEFT JOIN tbl_picking_pre pp ON ph.Picking_Header_ID = pp.Picking_Header_ID
		WHERE ph.Created_By_ID = $cBy AND ph.Status_Picking = 'PENDING' 
		AND (pp.ID IS NULL OR pp.status = 'PENDING') GROUP BY ph.PS_Number ;";
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
			FROM tbl_picking_pre pp
			inner join tbl_part_master pm on pp.Part_ID = pm.Part_ID
			inner join tbl_picking_header ph on pp.Picking_Header_ID = ph.Picking_Header_ID
			where ph.PS_Number = '$PS_Number' and pp.status = 'PENDING'";
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
			'obj=>Pick_Date:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT Delivery_Date FROM tbl_weld_on_order
			where Delivery_Date = '$Pick_Date'";
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

			$sql = "SELECT BIN_TO_UUID(Part_ID,true) as Part_ID
			FROM tbl_inventory
			where Package_Number = '$Package_Number' and FG_Serial_Number = '$FG_Serial_Number'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			$Part_ID = $re1->fetch_array(MYSQLI_ASSOC)['Part_ID'];

			$sql = "SELECT Part_No
			FROM tbl_part_master
			where BIN_TO_UUID(Part_ID,true) = '$Part_ID'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			$Part_No = $re1->fetch_array(MYSQLI_ASSOC)['Part_No'];

			$sql = "SELECT Part_No, SNP 
			from tbl_weld_on_order
			where Part_No = '$Part_No' and Delivery_Date = '$Pick_Date';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}

			$sql = "SELECT BIN_TO_UUID(Picking_Header_ID, true) as Picking_Header_ID FROM tbl_picking_header
			where Pick_Date = '$Pick_Date'";
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
			//exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}

			$sql = "SELECT
			sum(Qty) as Qty
			from tbl_picking_pre pp
			inner join tbl_picking_header ph on pp.Picking_Header_ID = ph.Picking_Header_ID
			where BIN_TO_UUID(pp.Picking_Header_ID, true) = '$Picking_Header_ID' and status = 'PENDING'
			group by Part_No;";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Qty = $row['Qty'];
			}
			//exit($Qty);

			$sql = "SELECT Part_No, SNP from tbl_weld_on_order
			inner join tbl_picking_header ph on Pick_Date = Delivery_Date
			where $Qty <= SNP and Part_No = '$Part_No'";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('Pick ครบแล้ว' . __LINE__);
			}

			$sql = "UPDATE tbl_inventory
			set Picking_Header_ID = UUID_TO_BIN('$Picking_Header_ID', true)
			where Package_Number = '$Package_Number' and FG_Serial_Number = '$FG_Serial_Number' 
			and Area = 'Storage' and Pick_Status = 'N';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			// $sql = "SELECT Pick_Qty from tbl_weld_on_order
			// where Delivery_Date = '$Pick_Date' and Part_No = '$Part_No' and Pick_Status = 'PENDING'";
			// $re1 = sqlError($mysqli, __LINE__, $sql, 1);
			// if ($re1->num_rows == 0) {
			// 	throw new Exception('ไม่พบข้อมูล' . __LINE__);
			// }
			// while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
			// 	$Pick_Qty = $row['Pick_Qty'];
			// }

			// $sql = "SELECT Pick_Qty from tbl_weld_on_order
			// where Delivery_Date = '$Pick_Date' and Part_No = '$Part_No' and Pick_Status = 'PENDING' and  SNP >= $Qty; ";
			// $re1 = sqlError($mysqli, __LINE__, $sql, 1);
			// if ($re1->num_rows == 0) {
			// 	throw new Exception('จำนวนเกิน SNP' . __LINE__);
			// }
			// $Part_ID = getPartID($mysqli, $Part_No);

			// $sql = "SELECT Pick_Qty from tbl_weld_on_order
			// where Delivery_Date = '$Pick_Date' and Part_No = '$Part_No' and Pick_Status = 'PENDING' and Pick_Qty != 0;";
			// $re1 = sqlError($mysqli, __LINE__, $sql, 1);

			// if ($re1->num_rows > 0) {

			// 	//ลบจำนวนเดิมออก
			// 	$sql = "UPDATE tbl_weld_on_order 
			// 		set Pick_Qty = Pick_Qty-$Pick_Qty
			// 		where Delivery_Date = '$Pick_Date' and Part_No = '$Part_No' and Pick_Status = 'PENDING'";
			// 	sqlError($mysqli, __LINE__, $sql, 1);

			// 	if ($mysqli->affected_rows == 0) {
			// 		throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			// 	}

			// 	//คืนสถานะ Pick_Status เป็น N 
			// 	$sql = "WITH a AS
			// 	( SELECT ROW_NUMBER() OVER (ORDER BY ti.FG_Serial_Number) AS 'No.', 
			// 	GRN_Number,Package_Number, FG_Serial_Number, ti.Qty, 
			// 	SUM(ti.Qty) OVER (PARTITION BY ti.Package_Number ORDER BY GRN_Number, ti.FG_Serial_Number) as Sum_Qty,
			// 	ti.Pick_Status
			// 	FROM tbl_inventory ti
			// 	inner join tbl_receiving_header trh on trh.Receiving_Header_ID = ti.Receiving_Header_ID) 
			// 	UPDATE tbl_inventory iv
			// 	set iv.Pick_Status = 'N'
			// 	WHERE BIN_TO_UUID(iv.Part_ID, true) = '$Part_ID' and Package_Number = '$Package_Number' 
			// 	and Area = 'Storage' and Pick_Status = 'Y' order by FG_Serial_Number DESC LIMIT $Pick_Qty;";
			// 	sqlError($mysqli, __LINE__, $sql, 1);
			// 	if ($mysqli->affected_rows == 0) {
			// 		throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			// 	}

			// $sql = "SELECT ROW_NUMBER() OVER (ORDER BY ti.FG_Serial_Number) AS 'No.', 
			// Package_Number,FG_Serial_Number, ti.Qty, 
			// LAST_VALUE(SUM(ti.Qty)) OVER (PARTITION BY ti.Package_Number ORDER BY ti.FG_Serial_Number) as Sum_Qty
			// FROM tbl_inventory ti inner join tbl_part_master pm on pm.Part_ID = ti.Part_ID 
			// where pm.Part_No = '$Part_No' and Package_Number = '$Package_Number' 
			// and Area = 'Storage' and Pick_Status = 'N';";
			// 	$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			// 	if ($re1->num_rows == 0) {
			// 		throw new Exception('ไม่พบข้อมูล' . __LINE__);
			// 	}
			// 	while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
			// 		$Sum_Qty = $row['Sum_Qty'];
			// 	}

			// 	$sql = "SELECT Pick_Qty from tbl_weld_on_order
			// 	where Delivery_Date = '$Delivery_Date' and Part_No = '$Part_No' and Pick_Status = 'PENDING' and $Sum_Qty >= $Qty;";
			// 	$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			// 	if ($re1->num_rows == 0) {
			// 		throw new Exception('จำนวนไม่เพียงพอ' . __LINE__);
			// 	}
			// } else {

			// 	$sql = "SELECT ROW_NUMBER() OVER (ORDER BY ti.FG_Serial_Number) AS 'No.', 
			// 	Package_Number,FG_Serial_Number, ti.Qty, 
			// 	LAST_VALUE(SUM(ti.Qty)) OVER (PARTITION BY ti.Package_Number ORDER BY ti.FG_Serial_Number) as Sum_Qty
			// 	FROM tbl_inventory ti inner join tbl_part_master pm on pm.Part_ID = ti.Part_ID 
			// 	where pm.Part_No = '$Part_No' and Package_Number = '$Package_Number' 
			// 	and Area = 'Storage' and Pick_Status = 'N';";
			// 	$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			// 	if ($re1->num_rows == 0) {
			// 		throw new Exception('ไม่พบข้อมูล' . __LINE__);
			// 	}
			// 	while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
			// 		$Sum_Qty = $row['Sum_Qty'];
			// 	}

			// 	$sql = "SELECT Pick_Qty from tbl_weld_on_order
			// 	where Delivery_Date = '$Delivery_Date' and Part_No = '$Part_No' and Pick_Status = 'PENDING' and $Sum_Qty >= $Qty;";
			// 	$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			// 	if ($re1->num_rows == 0) {
			// 		throw new Exception('จำนวนไม่เพียงพอ' . __LINE__);
			// 	}
			// }

			// //เพิ่มจำนวน Pick_Qty
			// $sql = "UPDATE tbl_weld_on_order 
			// set Pick_Qty = Pick_Qty+$Qty,
			// Created_Pick_By_ID = $cBy,
			// Creation_Pick_DateTime = now()
			// where Delivery_Date = '$Delivery_Date' and Part_No = '$Part_No' and Pick_Status = 'PENDING'";
			// sqlError($mysqli, __LINE__, $sql, 1);

			// if ($mysqli->affected_rows == 0) {
			// 	throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			// }

			// //เปลี่ยนสถานะ Pick_Status เป็น Y 
			// $sql = "WITH a AS
			// ( SELECT ROW_NUMBER() OVER (ORDER BY ti.FG_Serial_Number) AS 'No.', 
			// GRN_Number,Package_Number, FG_Serial_Number, ti.Qty, 
			// SUM(ti.Qty) OVER (PARTITION BY ti.Package_Number ORDER BY GRN_Number, ti.FG_Serial_Number ) as Sum_Qty,
			// ti.Pick_Status
			// FROM tbl_inventory ti
			// inner join tbl_receiving_header trh on trh.Receiving_Header_ID = ti.Receiving_Header_ID) 
			// UPDATE tbl_inventory iv
			// set iv.Pick_Status = 'Y'
			// WHERE BIN_TO_UUID(iv.Part_ID, true) = '$Part_ID' and Package_Number = '$Package_Number' 
			// and Area = 'Storage' and Pick_Status = 'N' LIMIT $Qty;";
			// sqlError($mysqli, __LINE__, $sql, 1);
			// if ($mysqli->affected_rows == 0) {
			// 	throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			// }

			$mysqli->commit();

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
			'obj=>Pick_Date:s:0:1',
			'obj=>PS_Number:s:0:1'
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT
			BIN_TO_UUID(ph.Picking_Header_ID,true) as Picking_Header_ID,
			Part_No,
			sum(Qty) as Qty
			from tbl_picking_pre rp
			inner join tbl_picking_header ph on rp.Picking_Header_ID = ph.Picking_Header_ID
			where PS_Number = '$PS_Number' and status = 'PENDING'
			group by Part_No;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Qty = $row['Qty'];
				$Part_No = $row['Part_No'];
				$Picking_Header_ID = $row['Picking_Header_ID'];
			}

			$sql = "SELECT Part_No, SNP from tbl_weld_on_order
			inner join tbl_picking_header ph on Pick_Date = Delivery_Date
			where SNP = $Qty and Part_No = '$Part_No'";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ยังไม่ครบ' . __LINE__);
			}

			$sql = "UPDATE tbl_picking_pre
			set status = 'COMPLETE'
			where BIN_TO_UUID(Picking_Header_ID,true) = '$Picking_Header_ID' and status = 'PENDING'";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			// $sql = "UPDATE tbl_inventory
			// set Pick_Status = 'Y'
			// where BIN_TO_UUID(Picking_Header_ID,true) = '$Picking_Header_ID' and Pick_Status = 'N'";
			// sqlError($mysqli, __LINE__, $sql, 1);
			// if ($mysqli->affected_rows == 0) {
			// 	throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			// }

			// $sql = "UPDATE tbl_weld_on_order
			// set PS_No = '$PS_Number',
			// Pick_Qty = $Qty
			// where Part_No = '$Part_No' and Delivery_Date = '$Pick_Date'";
			// sqlError($mysqli, __LINE__, $sql, 1);
			// if ($mysqli->affected_rows == 0) {
			// 	throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			// }



			$mysqli->commit();
			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else closeDBT($mysqli, 2, 'TYPE ERROR');


function prepareNameInsert($data)
{
	$dataReturn = array();
	foreach ($data as $key => $value) {
		$dataReturn[] = $key;
	}
	return '(' . join(',', $dataReturn) . ')';
}

function prepareValueInsert($data)
{
	$dataReturn = array();
	foreach ($data as $valueAr) {
		$typeV;
		$keyV;
		$valueV;
		$dataAr = array();
		foreach ($valueAr as $key => $value) {
			$keyV = $key;
			$valueV = $value;
			$dataAr[] = $valueV;
		}
		$dataReturn[] = '(' . join(',', $dataAr) . ')';
	}
	return join(',', $dataReturn);
}

function stringConvert($data)
{
	if (strlen($data) > 0) {
		return "'$data'";
	} else {
		return 'null';
	}
}

$mysqli->close();
exit();
