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
		$PS_Number  = $_POST['obj'];

		$sql = "SELECT 
			PS_Number,
			DATE_FORMAT(Pick_Date, '%d/%m/%y') AS Pick_Date,
			Part_No,
			pp.Qty,
			pp.Package_Number,
			pp.FG_Serial_Number,
			Status_Picking,
			Ship_status,
			DATE_FORMAT(Confirm_Picking_DateTime,
					'%d/%m/%y %H:%i') AS Confirm_Picking_DateTime
		FROM
			tbl_picking_header ph
				INNER JOIN
			tbl_picking_pre pp ON pp.Picking_Header_ID = ph.Picking_Header_ID
				INNER JOIN
			tbl_inventory tiv ON pp.FG_Serial_Number = tiv.FG_Serial_Number
		WHERE
			Pick_Date IS NOT NULL
		ORDER BY PS_Number DESC , Part_No DESC , FG_Serial_Number ASC;";

		$re1 = sqlError($mysqli, __LINE__, $sql, 1);

		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else if ($type == 22) {

		$obj  = $_POST['obj'];

		$explode = explode("/", $obj);
		$PS_Number = $explode[0];
		$FG_Serial_Number = $explode[1];
		$status = $explode[2];

		$mysqli->autocommit(FALSE);
		try {
			$sql = "SELECT 
			BIN_TO_UUID(tpp.ID, TRUE) as ID,
			BIN_TO_UUID(tph.Picking_Header_ID, TRUE) as Picking_Header_ID,
			tpp.Qty
			FROM
				tbl_picking_pre tpp
					INNER JOIN
				tbl_picking_header tph ON tpp.Picking_Header_ID = tph.Picking_Header_ID
			WHERE
				PS_Number = '$PS_Number'
				AND FG_Serial_Number = '$FG_Serial_Number'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$ID = $row['ID'];
				$Picking_Header_ID = $row['Picking_Header_ID'];
				$Qty = $row['Qty'];
			}

			if ($status == 'CANCEL') {
				$sql = "UPDATE tbl_picking_pre 
			SET 
				status = '$status'
			WHERE
				BIN_TO_UUID(ID, TRUE) = '$ID';";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					//throw new Exception('เปลี่ยนสถานะไม่สำเร็จ' . __LINE__);
				}

				$sql = "UPDATE tbl_picking_header
			SET 
				Total_Qty = Total_Qty-$Qty
			WHERE
				PS_Number = '$PS_Number';";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					//throw new Exception('เปลี่ยนสถานะไม่สำเร็จ' . __LINE__);
				}

				$sql = "UPDATE tbl_inventory 
			SET 
				Pick_Number = NULL
			WHERE
				BIN_TO_UUID(Picking_Header_ID, TRUE) = '$Picking_Header_ID'
					AND FG_Serial_Number = '$FG_Serial_Number'
					AND Pick_Status = 'N';";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
				}
			} else if ($status == 'PENDING') {

				$sql = "UPDATE tbl_picking_pre 
			SET 
				status = '$status'
			WHERE
				BIN_TO_UUID(ID, TRUE) = '$ID';";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					//throw new Exception('เปลี่ยนสถานะไม่สำเร็จ' . __LINE__);
				}
			}



			$mysqli->commit();

			$sql = "SELECT 
				PS_Number,
				DATE_FORMAT(Pick_Date, '%d/%m/%y') AS Pick_Date,
				Part_No,
				pp.Qty,
				pp.Package_Number,
				pp.FG_Serial_Number,
				Status_Picking,
				Ship_status,
				DATE_FORMAT(Confirm_Picking_DateTime,
						'%d/%m/%y %H:%i') AS Confirm_Picking_DateTime
			FROM
				tbl_picking_header ph
					INNER JOIN
				tbl_picking_pre pp ON pp.Picking_Header_ID = ph.Picking_Header_ID
					INNER JOIN
				tbl_inventory tiv ON pp.FG_Serial_Number = tiv.FG_Serial_Number
			WHERE
				Pick_Date IS NOT NULL
			ORDER BY PS_Number DESC , Part_No DESC , FG_Serial_Number ASC;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);

			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}

		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'ViewPS'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');

	if ($type == 31) {

		$PS_Number  = $_POST['obj'];

		$mysqli->autocommit(FALSE);
		try {


			$sql = "SELECT 
				BIN_TO_UUID(tph.Picking_Header_ID, TRUE) AS Picking_Header_ID,
				Package_Number,
				Part_No,
				SUM(Qty) AS Qty
			FROM
				tbl_picking_pre tpp
					INNER JOIN
				tbl_picking_header tph ON tpp.Picking_Header_ID = tph.Picking_Header_ID
			WHERE
				PS_Number = '$PS_Number'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Part_No = $row['Part_No'];
				$Qty = $row['Qty'];
				$Picking_Header_ID = $row['Picking_Header_ID'];
				$Package_Number = $row['Package_Number'];
			}

			$sql = "UPDATE tbl_picking_header tph,
				tbl_picking_pre tpp 
			SET 
				Status_Picking = 'CANCEL',
				status = 'CANCEL',
				Last_Updated_DateTime = NOW(),
				Updated_By_ID = $cBy
			WHERE
				BIN_TO_UUID(tpp.Picking_Header_ID, TRUE) = BIN_TO_UUID(tph.Picking_Header_ID, TRUE)
					AND PS_Number = '$PS_Number';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถยกเลิกได้' . __LINE__);
			}

			$sql = "UPDATE tbl_dn_order 
			SET 
				Receive_Status = 'N',
				Last_Updated_Date = curdate()
				Last_Updated_DateTime = now()
				Updated_By_ID = $cBy
			WHERE
				Part_No = '$Part_No'";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			$sql = "SELECT 
				To_Area,
				BIN_TO_UUID(To_Loc_ID, TRUE) AS To_Loc_ID,
				(SELECT Location_Code FROM tbl_location_master where To_Loc_ID = Location_ID )AS From_Loc
			FROM
				tbl_transaction tts
					INNER JOIN
				tbl_picking_header tph ON tts.Picking_Header_ID = tph.Picking_Header_ID
					INNER JOIN
				tbl_part_master tpm ON tts.Part_ID = tpm.Part_ID
			WHERE
				PS_Number = '$PS_Number'
					ORDER BY tts.Creation_DateTime DESC LIMIT 1;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล Location' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$From_Loc = $row['From_Loc'];
				$To_Area = $row['To_Area'];
				$To_Loc_ID = $row['To_Loc_ID'];
			}

			$sql = "SELECT 
				From_Area,
				BIN_TO_UUID(From_Loc_ID, TRUE) AS From_Loc_ID,
				(SELECT Location_Code FROM tbl_location_master where From_Loc_ID = Location_ID )AS To_Loc
			FROM
				tbl_transaction tts
					INNER JOIN
				tbl_picking_header tph ON tts.Picking_Header_ID = tph.Picking_Header_ID
					INNER JOIN
				tbl_part_master tpm ON tts.Part_ID = tpm.Part_ID
			WHERE
				PS_Number = '$PS_Number'
					order by tts.Creation_DateTime DESC LIMIT 1;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล Location' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$To_Loc = $row['To_Loc'];
				$From_Area = $row['From_Area'];
				$From_Loc_ID = $row['From_Loc_ID'];
			}

			//exit($To_Loc_ID . ' , ' . $From_Loc_ID);

			$sql = "INSERT INTO
            tbl_transaction(
            Picking_Header_ID,
            Part_ID,
            Package_Number,
            Serial_Number,
            Qty,
            From_Area,
            To_Area,
            Trans_Type,
            Creation_DateTime,
            Created_By_ID,
            From_Loc_ID,
            To_Loc_ID,
            Last_Updated_DateTime,
            Updated_By_ID)
        SELECT
            UUID_TO_BIN('$Picking_Header_ID',TRUE),
            tiv.Part_ID ,
            tiv.Package_Number ,
            tiv.FG_Serial_Number ,
            tiv.Qty ,
            '$To_Area',
            '$From_Area',
            'CANCEL',
            now(),
            $cBy,
            UUID_TO_BIN('$To_Loc_ID', TRUE),
            UUID_TO_BIN('$From_Loc_ID', TRUE),
            now(),
            $cBy
        FROM
            tbl_picking_header tph
        LEFT JOIN 
            tbl_inventory tiv ON tph.Picking_Header_ID = tiv.Picking_Header_ID 
        WHERE
            tph.PS_Number = '$PS_Number' AND tiv.Package_Number = '$Package_Number';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}


			$sql = "UPDATE tbl_inventory 
			SET 
				Picking_Header_ID = NULL,
				Pick_Number = NULL,
				Pick_Status = 'N',
				Area = '$To_Area',
				Location_ID = UUID_TO_BIN('$From_Loc_ID', TRUE),
				Last_Updated_DateTime = NOW(),
				Updated_By_ID = $cBy
			WHERE
				BIN_TO_UUID(Picking_Header_ID, TRUE) = '$Picking_Header_ID';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			$sql = "WITH a AS (
				SELECT 
					GRN_Number,
					Creation_DateTime,
					MONTH(Creation_DateTime) AS Creation_Month,
					period_Date
				FROM 
					tbl_picking_header tph
					CROSS JOIN 
						tbl_period tpr
				WHERE 
					YEAR(period_Date) = YEAR(curdate())
				ORDER BY 
					GRN_Number, period_Date)
				SELECT a.*
				FROM a 
				WHERE Creation_Month = MONTH(curdate()) 
				AND GRN_Number = '$GRN_Number'
				GROUP BY GRN_Number;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่สามารถยกเลิกได้' . __LINE__);
			}

			//exit('ยกเลิกสำเร็จ');

			$mysqli->commit();
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}

		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		//
	} else if ($type == 32) {

		$PS_Number  = $_POST['obj'];

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				BIN_TO_UUID(tph.Picking_Header_ID, TRUE) AS Picking_Header_ID,
				Package_Number,
				Part_No,
				SUM(Qty) AS Qty
			FROM
				tbl_picking_pre tpp
					INNER JOIN
				tbl_picking_header tph ON tpp.Picking_Header_ID = tph.Picking_Header_ID
			WHERE
				PS_Number = '$PS_Number'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Part_No = $row['Part_No'];
				$Qty = $row['Qty'];
				$Picking_Header_ID = $row['Picking_Header_ID'];
				$Package_Number = $row['Package_Number'];
			}

			$sql = "UPDATE tbl_picking_header tph,
				tbl_picking_pre tpp 
			SET 
				Status_Picking = 'CANCEL',
				status = 'CANCEL',
				Last_Updated_DateTime = NOW(),
				Updated_By_ID = $cBy
			WHERE
				BIN_TO_UUID(tpp.Picking_Header_ID, TRUE) = BIN_TO_UUID(tph.Picking_Header_ID, TRUE)
					AND PS_Number = '$PS_Number';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถยกเลิกได้' . __LINE__);
			}

			$sql = "SELECT 
				tiv.Area,
				BIN_TO_UUID(tiv.Location_ID, TRUE) AS Location_ID,
				Location_Code
			FROM
				tbl_inventory tiv
					INNER JOIN
				tbl_picking_header tph ON tiv.Picking_Header_ID = tph.Picking_Header_ID
					INNER JOIN
				tbl_location_master tlm ON tiv.Location_ID = tlm.Location_ID
				WHERE tph.PS_Number = '$PS_Number'
			ORDER BY tiv.Creation_DateTime DESC
			LIMIT 1;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล Location' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Area = $row['Area'];
				$Location_ID = $row['Location_ID'];
			}

			//exit($Package_Number);

			$sql = "INSERT INTO
            tbl_transaction(
            Picking_Header_ID,
            Part_ID,
            Package_Number,
            Serial_Number,
            Qty,
            From_Area,
            To_Area,
            Trans_Type,
            Creation_DateTime,
            Created_By_ID,
            From_Loc_ID,
            To_Loc_ID,
            Last_Updated_DateTime,
            Updated_By_ID)
        SELECT
            UUID_TO_BIN('$Picking_Header_ID',TRUE),
            tiv.Part_ID ,
            tiv.Package_Number ,
            tiv.FG_Serial_Number ,
            tiv.Qty ,
            '$Area',
            '$Area',
            'CANCEL',
            now(),
            $cBy,
            UUID_TO_BIN('$Location_ID', TRUE),
            UUID_TO_BIN('$Location_ID', TRUE),
            now(),
            $cBy
        FROM
            tbl_picking_header tph
        LEFT JOIN 
            tbl_inventory tiv ON tph.Picking_Header_ID = tiv.Picking_Header_ID 
        WHERE
            tph.PS_Number = '$PS_Number' AND tiv.Package_Number = '$Package_Number';";
			sqlError($mysqli, __LINE__, $sql, 1);
			//exit($sql);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถยกเลิกข้อมูลได้' . __LINE__);
			}


			$sql = "UPDATE tbl_inventory 
			SET 
				Picking_Header_ID = NULL,
				Last_Updated_DateTime = NOW(),
				Updated_By_ID = $cBy
			WHERE
				BIN_TO_UUID(Picking_Header_ID, TRUE) = '$Picking_Header_ID';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			$sql = "WITH a AS (
				SELECT 
					GRN_Number,
					Creation_DateTime,
					MONTH(Creation_DateTime) AS Creation_Month,
					period_Date
				FROM 
					tbl_picking_header tph
					CROSS JOIN 
						tbl_period tpr
				WHERE 
					YEAR(period_Date) = YEAR(curdate())
				ORDER BY 
					GRN_Number, period_Date)
				SELECT a.*
				FROM a 
				WHERE Creation_Month = MONTH(curdate()) 
				AND GRN_Number = '$GRN_Number'
				GROUP BY GRN_Number;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่สามารถยกเลิกได้' . __LINE__);
			}

			//exit('ยกเลิกสำเร็จ');

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
	$sql = "SELECT 
		PS_Number,
		DATE_FORMAT(Pick_Date, '%d/%m/%y') AS Pick_Date,
		Part_No,
		pp.Qty,
		pp.Package_Number,
		pp.FG_Serial_Number,
		Status_Picking,
		Ship_status,
		DATE_FORMAT(Confirm_Picking_DateTime,
				'%d/%m/%y %H:%i') AS Confirm_Picking_DateTime
	FROM
		tbl_picking_header ph
			INNER JOIN
		tbl_picking_pre pp ON pp.Picking_Header_ID = ph.Picking_Header_ID
			LEFT JOIN
		tbl_inventory tiv ON pp.FG_Serial_Number = tiv.FG_Serial_Number
	WHERE
		Pick_Date IS NOT NULL
	ORDER BY PS_Number DESC , Part_No DESC , FG_Serial_Number ASC;";
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
			'FG_Serial_Number',
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
			"Status_Picking" => $value1[0]['Status_Picking'],
			"Ship_status" => $value1[0]['Ship_status'],
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
	$sql = "SELECT 
		PS_Number,
		DATE_FORMAT(Pick_Date, '%d/%m/%y') AS Pick_Date,
		Part_No,
		pp.Qty,
		pp.Package_Number,
		pp.FG_Serial_Number,
		Status_Picking,
		DATE_FORMAT(Confirm_Picking_DateTime,
				'%d/%m/%y %H:%i') AS Confirm_Picking_DateTime
	FROM
		tbl_picking_header ph
			INNER JOIN
		tbl_picking_pre pp ON pp.Picking_Header_ID = ph.Picking_Header_ID
			INNER JOIN
		tbl_inventory tiv ON pp.FG_Serial_Number = tiv.FG_Serial_Number
	WHERE
		Pick_Date IS NOT NULL
			AND status = 'COMPLETE'
	ORDER BY PS_Number DESC , Part_No DESC , FG_Serial_Number ASC;";

	return $sql;
}


$mysqli->close();
exit();
