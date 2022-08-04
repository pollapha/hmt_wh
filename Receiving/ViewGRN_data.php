<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'ViewGRN'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'ViewGRN'}[0] == 0) {
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

			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'ViewGRN'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {
	} else if ($type == 12) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'ViewGRN'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {

		$obj  = $_POST['obj'];
		$explode = explode("/", $obj);
		$GRN_Number  = $explode[0];
		//exit($GRN_Number .' , '.$FG_Serial_Number);

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				Part_No,
				trp.Package_Number,
				trp.FG_Serial_Number,
				Status_Receiving,
				Pick_status,
				Picking_Header_ID
			FROM
				tbl_receiving_header trh
					INNER JOIN
				tbl_receiving_pre trp ON trp.Receiving_Header_ID = trh.Receiving_Header_ID
					LEFT JOIN
				tbl_inventory tiv ON trp.FG_Serial_Number = tiv.FG_Serial_Number
			WHERE
				Picking_Header_ID IS NOT NULL
			ORDER BY GRN_Number DESC , Part_No DESC , FG_Serial_Number ASC;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('มีบางรายการ Pick ไปเรียบร้อยแล้ว' . __LINE__);
			}

			$sql = "SELECT 
				BIN_TO_UUID(trh.Receiving_Header_ID, TRUE) AS Receiving_Header_ID
			FROM
				tbl_receiving_header trh
			WHERE
				GRN_Number = '$GRN_Number'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Receiving_Header_ID = $row['Receiving_Header_ID'];
			}

			$sql = "SELECT 
				Package_Number
			FROM
				tbl_receiving_pre trp
			WHERE
				BIN_TO_UUID(Receiving_Header_ID, TRUE) = '$Receiving_Header_ID'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Package_Number = $row['Package_Number'];
			}

			//exit($Receiving_Header_ID);

			$sql = "UPDATE tbl_receiving_header 
			SET 
				Status_Receiving = 'PENDING',
				Confirm_Receive_DateTime = null,
				Total_Qty = 0,
				Last_Updated_DateTime = NOW(),
				Updated_By_ID = $cBy
			WHERE
				GRN_Number = '$GRN_Number';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			$sql = "UPDATE tbl_receiving_pre 
				SET 
					status = 'PENDING'
				WHERE 
					BIN_TO_UUID(Receiving_Header_ID, TRUE) = '$Receiving_Header_ID';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			$sql = "SELECT 
				Package_Number,
				Qty,
				BIN_TO_UUID(Location_ID, TRUE) AS Location_ID,
				Area
			FROM
				tbl_inventory
			WHERE
				BIN_TO_UUID(Receiving_Header_ID, TRUE) = '$Receiving_Header_ID';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				//confirm แล้ว
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$Package_Number = $row['Package_Number'];
					$Location_ID = $row['Location_ID'];
					$Area = $row['Area'];
				}
				//exit($Area . ' , ' . $Location_ID);

				//อัพเดท Area ใน tbl_receiving_pre
				$sql = "UPDATE tbl_receiving_pre
				SET 
					Area = NULL
				WHERE
				BIN_TO_UUID(Receiving_Header_ID, TRUE) = '$Receiving_Header_ID'
					AND Package_Number = '$Package_Number';";
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
					Receive_Status = 'N'
				WHERE
					GRN_Number = '$GRN_Number'
						AND Status_Receiving = 'PENDING'
						AND status = 'PENDING'
						AND Receive_Status = 'Y';";
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
					tbl_receiving_header trh ON tts.Receiving_Header_ID = trh.Receiving_Header_ID
						INNER JOIN
					tbl_part_master tpm ON tts.Part_ID = tpm.Part_ID
				WHERE
					GRN_Number = '$GRN_Number'
						ORDER BY tts.Creation_DateTime DESC LIMIT 1;";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล Location' . __LINE__);
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
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
						tbl_receiving_header trh ON tts.Receiving_Header_ID = trh.Receiving_Header_ID
							INNER JOIN
						tbl_part_master tpm ON tts.Part_ID = tpm.Part_ID
					WHERE
						GRN_Number = '$GRN_Number'
							order by tts.Creation_DateTime DESC LIMIT 1;";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$From_Area = $row['From_Area'];
					$From_Loc_ID = $row['From_Loc_ID'];
				}
				//exit($sql);
				if ($From_Loc_ID == NULL) {
					//throw new Exception('ไม่พบข้อมูล Location' . __LINE__);
					$sql = "INSERT INTO
						tbl_transaction(
						Receiving_Header_ID,
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
						UUID_TO_BIN('$Receiving_Header_ID',TRUE),
						tiv.Part_ID ,
						tiv.Package_Number ,
						tiv.FG_Serial_Number ,
						tiv.Qty ,
						tiv.Area,
						tiv.Area,
						'EDIT',
						now(),
						$cBy,
						tiv.Location_ID,
						tiv.Location_ID,
						now(),
						$cBy
					FROM
						tbl_receiving_header trh
					LEFT JOIN 
						tbl_inventory tiv ON trh.Receiving_Header_ID = tiv.Receiving_Header_ID 
					WHERE
						trh.GRN_Number = '$GRN_Number'
						AND tiv.Package_Number = '$Package_Number';";
					sqlError($mysqli, __LINE__, $sql, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
					}
				} else {
					$sql = "INSERT INTO
						tbl_transaction(
						Receiving_Header_ID,
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
						UUID_TO_BIN('$Receiving_Header_ID',TRUE),
						tiv.Part_ID ,
						tiv.Package_Number ,
						tiv.FG_Serial_Number ,
						tiv.Qty ,
						'$To_Area',
						'$From_Area',
						'EDIT',
						now(),
						$cBy,
						UUID_TO_BIN('$To_Loc_ID',TRUE),
						UUID_TO_BIN('$From_Loc_ID',TRUE),
						now(),
						$cBy
					FROM
						tbl_receiving_header trh
					LEFT JOIN 
						tbl_inventory tiv ON trh.Receiving_Header_ID = tiv.Receiving_Header_ID 
					WHERE
						trh.GRN_Number = '$GRN_Number'
						AND tiv.Package_Number = '$Package_Number';";
					sqlError($mysqli, __LINE__, $sql, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
					}

					$sql = "UPDATE tbl_inventory tiv
							INNER JOIN
						tbl_receiving_pre trp ON tiv.FG_Serial_Number = trp.FG_Serial_Number
							INNER JOIN
						tbl_receiving_header trh ON trp.Receiving_Header_ID = trh.Receiving_Header_ID 
					SET 
						tiv.Area = '$From_Area',
						tiv.Location_ID = UUID_TO_BIN('$From_Loc_ID',TRUE)
					WHERE
						GRN_Number = '$GRN_Number';";
					sqlError($mysqli, __LINE__, $sql, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถแก้ไขข้อมูลได้' . __LINE__);
					}
				}
			}
			//exit('สำเร็จ');

			$mysqli->commit();
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}

		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'ViewGRN'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {

		$GRN_Number  = $_POST['obj'];

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				Part_No,
				trp.Package_Number,
				trp.FG_Serial_Number,
				Status_Receiving,
				Pick_status,
				Picking_Header_ID
			FROM
				tbl_receiving_header trh
					INNER JOIN
				tbl_receiving_pre trp ON trp.Receiving_Header_ID = trh.Receiving_Header_ID
					LEFT JOIN
				tbl_inventory tiv ON trp.FG_Serial_Number = tiv.FG_Serial_Number
			WHERE
				Picking_Header_ID IS NOT NULL
			ORDER BY GRN_Number DESC , Part_No DESC , FG_Serial_Number ASC;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('มีบางรายการ Pick ไปเรียบร้อยแล้ว' . __LINE__);
			}

			$sql = "SELECT 
				BIN_TO_UUID(trh.Receiving_Header_ID, TRUE) AS Receiving_Header_ID,
				Package_Number,
				Part_No,
				SUM(Qty) AS Qty
			FROM
				tbl_receiving_pre trp
					INNER JOIN
				tbl_receiving_header trh ON trp.Receiving_Header_ID = trh.Receiving_Header_ID
			WHERE
				GRN_Number = '$GRN_Number'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Part_No = $row['Part_No'];
				$Qty = $row['Qty'];
				$Receiving_Header_ID = $row['Receiving_Header_ID'];
				$Package_Number = $row['Package_Number'];
			}

			$sql = "UPDATE tbl_receiving_header trh,
				tbl_receiving_pre trp 
			SET 
				Status_Receiving = 'CANCEL',
				status = 'CANCEL',
				Last_Updated_DateTime = NOW(),
				Updated_By_ID = $cBy
			WHERE
				BIN_TO_UUID(trp.Receiving_Header_ID, TRUE) = BIN_TO_UUID(trh.Receiving_Header_ID, TRUE)
					AND GRN_Number = '$GRN_Number';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถยกเลิกได้' . __LINE__);
			}

			$sql = "SELECT 
				Package_Number,
				Qty,
				BIN_TO_UUID(Location_ID, TRUE) AS Location_ID,
				Area
			FROM
				tbl_inventory
			WHERE
				BIN_TO_UUID(Receiving_Header_ID, TRUE) = '$Receiving_Header_ID'
					AND Area = 'Storage';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {

				//confirm แล้ว
				$sql = "UPDATE tbl_dn_order tdo
					INNER JOIN
				tbl_receiving_pre trp ON trp.FG_Serial_Number = tdo.FG_Serial_Number
					INNER JOIN
				tbl_receiving_header trh ON trh.Receiving_Header_ID = trp.Receiving_Header_ID 
			SET 
				Receive_Status = 'N',
				tdo.Last_Updated_Date = CURDATE(),
				tdo.Last_Updated_DateTime = NOW(),
				tdo.Updated_By_ID = $cBy
			WHERE
				GRN_Number = '$GRN_Number'
					AND Receive_Status = 'Y';";
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
					tbl_receiving_header trh ON tts.Receiving_Header_ID = trh.Receiving_Header_ID
						INNER JOIN
					tbl_part_master tpm ON tts.Part_ID = tpm.Part_ID
				WHERE
					GRN_Number = '$GRN_Number'
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
					tbl_receiving_header trh ON tts.Receiving_Header_ID = trh.Receiving_Header_ID
						INNER JOIN
					tbl_part_master tpm ON tts.Part_ID = tpm.Part_ID
				WHERE
					GRN_Number = '$GRN_Number'
						ORDER BY tts.Creation_DateTime DESC LIMIT 1;";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล Location' . __LINE__);
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$To_Loc = $row['To_Loc'];
					$From_Area = $row['From_Area'];
					$From_Loc_ID = $row['From_Loc_ID'];
				}

				if ($From_Loc_ID == NULL) {
					$sql = "INSERT INTO
						tbl_transaction(
						Receiving_Header_ID,
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
						UUID_TO_BIN('$Receiving_Header_ID',TRUE),
						tiv.Part_ID ,
						tiv.Package_Number ,
						tiv.FG_Serial_Number ,
						tiv.Qty ,
						tiv.Area,
						tiv.Area,
						'CANCEL',
						now(),
						$cBy,
						tiv.Location_ID,
						tiv.Location_ID,
						now(),
						$cBy
					FROM
						tbl_receiving_header trh
					LEFT JOIN 
						tbl_inventory tiv ON trh.Receiving_Header_ID = tiv.Receiving_Header_ID 
					WHERE
						trh.GRN_Number = '$GRN_Number' AND tiv.Package_Number = '$Package_Number';";
					sqlError($mysqli, __LINE__, $sql, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
					}
				} else {
					$sql = "INSERT INTO
						tbl_transaction(
						Receiving_Header_ID,
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
						UUID_TO_BIN('$Receiving_Header_ID',TRUE),
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
						tbl_receiving_header trh
					LEFT JOIN 
						tbl_inventory tiv ON trh.Receiving_Header_ID = tiv.Receiving_Header_ID 
					WHERE
						trh.GRN_Number = '$GRN_Number' AND tiv.Package_Number = '$Package_Number';";
					sqlError($mysqli, __LINE__, $sql, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
					}
				}

				$sql = "DELETE FROM tbl_inventory
				WHERE
					BIN_TO_UUID(Receiving_Header_ID, TRUE) = '$Receiving_Header_ID';";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
				}
			} else {
				$sql = "SELECT 
					trp.Area
				FROM
					tbl_receiving_pre trp
						INNER JOIN
					tbl_receiving_header trh ON trp.Receiving_Header_ID = trh.Receiving_Header_ID
				WHERE
					trh.GRN_Number = '$GRN_Number'
				ORDER BY trp.Creation_DateTime DESC
				LIMIT 1;";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล Location' . __LINE__);
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$Area = $row['Area'];
				}

				$sql = "INSERT INTO
					tbl_transaction(
					Receiving_Header_ID,
					Part_ID,
					Package_Number,
					Serial_Number,
					Qty,
					From_Area,
					To_Area,
					Trans_Type,
					Creation_DateTime,
					Created_By_ID,
					Last_Updated_DateTime,
					Updated_By_ID)
				SELECT
					UUID_TO_BIN('$Receiving_Header_ID',TRUE),
					trp.Part_ID ,
					trp.Package_Number ,
					trp.FG_Serial_Number ,
					trp.Qty ,
					'$Area',
					'$Area',
					'CANCEL',
					now(),
					$cBy,
					now(),
					$cBy
				FROM
					tbl_receiving_header trh
				LEFT JOIN 
					tbl_receiving_pre trp ON trh.Receiving_Header_ID = trp.Receiving_Header_ID 
				WHERE
					trh.GRN_Number = '$GRN_Number' AND trp.Package_Number = '$Package_Number';";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถยกเลิกข้อมูลได้' . __LINE__);
				}
			}

			$sql = "WITH a AS (
			SELECT 
				GRN_Number,
				Creation_DateTime,
				MONTH(Creation_DateTime) AS Creation_Month,
				period_Date
			FROM 
				tbl_receiving_header trh
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
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'ViewGRN'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else closeDBT($mysqli, 2, 'TYPE ERROR');

function select_group($mysqli)
{
	$sql = "SELECT 
		GRN_Number,
		DATE_FORMAT(Receive_DateTime, '%d/%m/%y %H:%i') AS Receive_DateTime,
		DN_Number,
		Part_No,
		trp.Qty,
		trp.Package_Number,
		trp.FG_Serial_Number,
		Status_Receiving,
		Pick_status,
		Picking_Header_ID,
		DATE_FORMAT(Confirm_Receive_DateTime,
				'%d/%m/%y %H:%i') AS Confirm_Receive_DateTime
	FROM
		tbl_receiving_header trh
			INNER JOIN
		tbl_receiving_pre trp ON trp.Receiving_Header_ID = trh.Receiving_Header_ID
			LEFT JOIN
		tbl_inventory tiv ON trp.FG_Serial_Number = tiv.FG_Serial_Number
	WHERE
		Receive_DateTime IS NOT NULL
	ORDER BY GRN_Number DESC , Part_No DESC , FG_Serial_Number ASC;";
	$re1 = sqlError($mysqli, __LINE__, $sql, 1);
	$value = jsonRow($re1, false, 0);
	$data = group_by('GRN_Number', $value); //group datatable tree
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
			$sub[$key2]['GRN_Number'] = $c2;
			$sub[$key2]['Is_Header'] = 'NO';
			$c2++;
		}

		$dateset[] =  array(
			"No" => $c, 'Is_Header' => 'YES', "GRN_Number" => $key1,
			"Receive_DateTime" => $value1[0]['Receive_DateTime'],
			"DN_Number" => $value1[0]['DN_Number'],
			"Status_Receiving" => $value1[0]['Status_Receiving'],
			"Confirm_Receive_DateTime" => $value1[0]['Confirm_Receive_DateTime'],
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
		GRN_Number,
		DATE_FORMAT(Receive_DateTime, '%d/%m/%y %H:%i') AS Receive_DateTime,
		DN_Number,
		Part_No,
		Qty,
		Package_Number,
		FG_Serial_Number,
		Status_Receiving,
		DATE_FORMAT(Confirm_Receive_DateTime,
				'%d/%m/%y %H:%i') AS Confirm_Receive_DateTime
	FROM
		tbl_receiving_header rh
			INNER JOIN
		tbl_receiving_pre rp ON rp.Receiving_Header_ID = rh.Receiving_Header_ID
	WHERE
		Receive_DateTime IS NOT NULL
			AND status = 'COMPLETE'
	ORDER BY GRN_Number DESC , Part_No DESC , FG_Serial_Number ASC;";

	return $sql;
}

$mysqli->close();
exit();
