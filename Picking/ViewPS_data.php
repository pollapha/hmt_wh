<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'ViewPS'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'ViewPS'}[0] == 0) {
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


include('../php/xlsxwriter.class.php');
include('../common/common.php');
include('../php/connection.php');
if ($type <= 10) //data
{
	if ($type == 1) {

		$re = select_group($mysqli);
		closeDBT($mysqli, 1, $re);
		//

	} else if ($type == 5) {

		$obj  = $_POST['obj'];
		$filenameprefix = $mysqli->real_escape_string(trim($obj['filenameprefix']));
		$sql = sqlexport_excel();
		$mysqli->autocommit(FALSE);
		try {
			if ($sql != '') {
				if ($re1 = $mysqli->query($sql)) {
					if ($re1->num_rows > 0) {
						$data = excelRow($re1);
						$writer = new XLSXWriter();
						$writer->writeSheet($data);
						$randomString = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
						$filename = $filenameprefix . '-' . $randomString . '.xlsx';
						ob_end_clean();
						header('Content-disposition: attachment; filename="' . XLSXWriter::sanitize_filename($filename) . '"');
						header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
						header('Content-Transfer-Encoding: binary');
						header('Cache-Control: must-revalidate');
						header('Pragma: public');
						$writer->writeToStdOut();
					} else {
						echo json_encode(array('ch' => 2, 'data' => "ไม่พบข้อมูลในระบบ"));
					}
				} else {
					echo json_encode(array('ch' => 2, 'data' => "Error SP"));
				}
			} else {
				echo json_encode(array('ch' => 2, 'data' => "Error SP"));
			}
			$mysqli->commit();
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'ViewPS'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {
	} else if ($type == 12) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'ViewPS'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'ViewPS'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {


		$PS_Number  = $_POST['obj'];

		$mysqli->autocommit(FALSE);
		try {
			$sql = "SELECT
			BIN_TO_UUID(ph.Picking_Header_ID,true) as Picking_Header_ID,
			sum(Qty) as Qty
			from tbl_picking_pre pp
			inner join tbl_picking_header ph on pp.Picking_Header_ID = ph.Picking_Header_ID
			where PS_Number = '$PS_Number' and status = 'COMPLETE'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Qty = $row['Qty'];
				$Picking_Header_ID = $row['Picking_Header_ID'];
			}

			$sql = "UPDATE tbl_picking_header ph, tbl_picking_pre pp
			set Status_Picking = 'CANCEL', status = 'CANCEL'
			where BIN_TO_UUID(pp.Picking_Header_ID,true) = BIN_TO_UUID(ph.Picking_Header_ID,true) and
			PS_Number = '$PS_Number' and Status_Picking = 'PENDING' and status = 'COMPLETE';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถยกเลิกได้' . __LINE__);
			}

			$sql = "UPDATE tbl_inventory
			set Picking_Header_ID = null
			where BIN_TO_UUID(Picking_Header_ID,true) = '$Picking_Header_ID' and Pick_Status = 'N'";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			// $sql = "UPDATE tbl_picking_pre tpp
			// inner join tbl_picking_header tph on tpp.Picking_Header_ID = tph.Picking_Header_ID
			// set tpp.status = 'CANCEL'
			// where PS_Number = '$PS_Number' and tph.Status_Receiving = 'PENDING' and tpp.status = 'COMPLETE';";
			// sqlError($mysqli, __LINE__, $sql, 1);
			// if ($mysqli->affected_rows == 0) {
			// 	throw new Exception('ไม่สามารถยกเลิกได้' . __LINE__);
			// }

			// $sql = "UPDATE tbl_picking_pre
			// 	set status = 'CANCEL'
			// 	where Picking_Header_ID = '$Picking_Header_ID' and Status_Receiving = 'PENDING' and Status = 'COMPLETE'";
			// sqlError($mysqli, __LINE__, $sql, 1);
			// if ($mysqli->affected_rows == 0) {
			// 	throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			// }

			// $sql = "UPDATE tbl_transaction
			// 	set Trans_Type = 'CANCEL'
			// 	where Picking_Header_ID = '$Picking_Header_ID'";
			// sqlError($mysqli, __LINE__, $sql, 1);
			// if ($mysqli->affected_rows == 0) {
			// 	throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			// }

			$mysqli->commit();
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}



		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'ViewPS'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else closeDBT($mysqli, 2, 'TYPE ERROR');


function select_group($mysqli)
{
	$sql = "SELECT PS_Number,
	date_format(Pick_Date, '%d/%m/%y') AS Pick_Date,
    Part_No,
	Qty,
    Package_Number,
    FG_Serial_Number,
	Status_Picking,
	date_format(Confirm_Picking_DateTime, '%d/%m/%y %H:%i') AS Confirm_Picking_DateTime
	from tbl_picking_header ph
    inner join tbl_picking_pre pp on pp.Picking_Header_ID = ph.Picking_Header_ID
    WHERE Pick_Date IS NOT NULL and status = 'COMPLETE'
	ORDER BY PS_Number DESC, Part_No DESC, FG_Serial_Number ASC;";
	$re1 = sqlError($mysqli, __LINE__, $sql, 1);
	$value = jsonRow($re1, false, 0);
	$data = group_by('PS_Number', $value); //group datatable tree
	$dateset = array();
	$c = 1;
	foreach ($data as $key1 => $value1) {
		$sub = selectColumnFromArray($value1, array(
			'Part_No',
			'Qty',
			'Package_Number',
			'FG_Serial_Number'
		)); //ที่จะให้อยู่ในตัว Child rows
		$c2 = 1;
		foreach ($sub as $key2 => $value2) {
			$sub[$key2]['PS_Number'] = $c2;
			$sub[$key2]['Is_Header'] = 'NO';
			$c2++;
		}

		$dateset[] =  array(
			"No" => $c, 'Is_Header' => 'YES', "PS_Number" => $key1,
			"Pick_Date" => $value1[0]['Pick_Date'],
			//"DN_Number" => $value1[0]['DN_Number'],
			"Status_Picking" => $value1[0]['Status_Picking'],
			"Confirm_Picking_DateTime" => $value1[0]['Confirm_Picking_DateTime'],
			'Total_Item' => count($value1), "open" => 0, "data" => $sub
		);
		$c++;
	}
	return $dateset;
}

function excelRow($result, $row = true, $seq = 0)
{
	$exceldata = array();
	$headdata = array();
	$data = array();
	$c = 0;
	if ($row) {
		$i = $seq;
		array_push($headdata, 'NO');
		while ($row = $result->fetch_field()) {
			array_push($headdata, $row->name);
		}
		$data[] = $headdata;
		while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
			array_unshift($row, ++$c);
			$data[] = $row;
		}
	}
	return $data;
}

function sqlexport_excel()
{
	$sql = "SELECT PS_Number,
	date_format(Pick_Date, '%d/%m/%y') AS Pick_Date,
	DN_Number,
    Part_No,
	Qty,
    Package_Number,
    FG_Serial_Number,
	Status_Picking,
	date_format(Confirm_Picking_DateTime, '%d/%m/%y %H:%i') AS Confirm_Picking_DateTime
	from tbl_picking_header ph
    inner join tbl_picking_pre pp on pp.Picking_Header_ID = ph.Picking_Header_ID
    WHERE Pick_Date IS NOT NULL and status = 'COMPLETE'
	ORDER BY PS_Number DESC, Part_No DESC, FG_Serial_Number ASC;";

	return $sql;
}


$mysqli->close();
exit();
