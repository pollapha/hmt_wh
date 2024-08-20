<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'Putaway'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'Putaway'}[0] == 0) {
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

		$sql = "SELECT 
				BIN_TO_UUID(t1.transaction_id, TRUE) AS transaction_id,
				document_no
			FROM
				tbl_transaction t1
					LEFT JOIN
				tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
			WHERE
				t1.editing_user_id = $cBy
					AND t1.transaction_type = 'Temp-Move'
					AND substring(document_no, 1, 1) = 'R'
					AND t2.status = 'Pending'
			GROUP BY t1.transaction_id ORDER BY t1.editing_at DESC LIMIT 1";
		// exit($sql);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		$header = jsonRow($re1, true, 0);

		$body = [];

		if (count($header) > 0) {
			$transaction_id = $header[0]['transaction_id'];

			$sql = "SELECT 
				BIN_TO_UUID(transaction_id, TRUE) AS transaction_id,
				BIN_TO_UUID(transaction_line_id, TRUE) AS transaction_line_id,
				pallet_no, case_tag_no, part_no, part_name,
				qty, steel_qty, gross_kg, net_per_pallet, measurement_cbm, certificate_no, coil_lot_no,
				t1.remark,
				t3.location_code,
				t3.location_area,
				BIN_TO_UUID(t1.from_location_id, TRUE) AS from_location_id,
				BIN_TO_UUID(t1.to_location_id, TRUE) AS to_location_id
			FROM
				tbl_transaction_line t1
					inner join tbl_part_master t2 on t1.part_id = t2.part_id
					left join tbl_location_master t3 on t1.to_location_id = t3.location_id
			WHERE
				transaction_id = uuid_to_bin('$transaction_id',true)
					AND t1.status = 'Pending';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);

			$body = jsonRow($re1, true, 0);
		}

		$returnData = ['header' => $header, 'body' => $body];

		closeDBT($mysqli, 1, $returnData);
	} else if ($type == 2) {

		$dataParams = array(
			'obj',
			'obj=>to_location:s:0:0',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT
				location_code
			FROM
				tbl_location_master
			WHERE
				location_code = '$to_location';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล Location Code');
			}

			$mysqli->commit();
			closeDBT($mysqli, 1, 'OK');
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'Putaway'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {

		$dataParams = array(
			'obj',
			'obj=>document_no:s:0:0',
			'obj=>to_location:s:0:1',
			'obj=>case_tag_no:s:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			/* Transaction */

			$explode = explode(' | ', $to_location);
			$to_location = $explode[0];
			$to_location_id = getLocationID($mysqli, $to_location);

			$sql = "SELECT bin_to_uuid(transaction_id,true) transaction_id, document_no FROM tbl_transaction WHERE document_no = '$document_no'; ";
			$result = sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($result->num_rows === 0) {
				$sql = "SELECT func_GenRuningNumber('temp',0) as document_no ;";
				$result = sqlError($mysqli, __LINE__, $sql, 1, 1);
				if ($result->num_rows === 0) {
					throw new Exception('ไม่พบข้อมูล Document_no TEMP');
				}
				$document_no = $result->fetch_assoc()["document_no"];
				$document_no = 'R' . $document_no;

				$sql = "INSERT INTO tbl_transaction 
				(document_no, document_date, transaction_type, created_at, created_user_id) 
				VALUES
				('$document_no',NOW(), 'Temp-Move', now(), $cBy);";
				// exit($sql);
				sqlError($mysqli, __LINE__, $sql, 1, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
				}
			}

			$sql = "SELECT bin_to_uuid(transaction_id,true) transaction_id, document_no FROM tbl_transaction WHERE document_no = '$document_no';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			$transaction_id = $re1->fetch_array(MYSQLI_ASSOC)['transaction_id'];

			$sql = "UPDATE tbl_transaction
			SET editing_at = NOW(), 
				editing_user_id = $cBy
			WHERE transaction_id = uuid_to_bin('$transaction_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			$sql = "SELECT case_tag_no FROM tbl_inventory WHERE case_tag_no = '$case_tag_no';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล Case Tag No. นี้ ' . __LINE__);
			}

			$sql = "SELECT
				case_tag_no FROM tbl_inventory WHERE case_tag_no = '$case_tag_no' AND tag_check = 'No';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('Case Tag No.นี้<br>ยังไม่สแกน Compare Tag');
			}


			$sql = "SELECT case_tag_no FROM tbl_transaction_line WHERE transaction_id = uuid_to_bin('$transaction_id',true) AND case_tag_no = '$case_tag_no';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('Case Tag No. นี้<br>ทำการย้ายโลเคชั่นแล้ว ' . __LINE__);
			}

			$sql = "SELECT case_tag_no FROM tbl_inventory WHERE case_tag_no = '$case_tag_no' AND location_id = uuid_to_bin('$to_location_id',true);";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('Case Tag No. นี้<br>มีอยู่ในโลเคชั่นนี้แล้ว ' . __LINE__);
			}

			$sql = "INSERT INTO tbl_transaction_line 
				( pallet_no, case_tag_no, part_id, qty, gross_kg, net_per_pallet, measurement_cbm, certificate_no, coil_lot_no, remark, 
				transaction_id, from_location_id, to_location_id, invoice_no,
				created_at, updated_at, created_user_id, updated_user_id
				)
				SELECT pallet_no, case_tag_no, part_id, qty, gross_kg, net_per_pallet, measurement_cbm, certificate_no, coil_lot_no, remark, 
				uuid_to_bin('$transaction_id',true), location_id, uuid_to_bin('$to_location_id',true), invoice_no,
				NOW(), NOW(), $cBy, $cBy
				FROM tbl_inventory WHERE case_tag_no = '$case_tag_no';";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			/* Inventory */

			$sql = "UPDATE tbl_inventory
			SET location_id = uuid_to_bin('$to_location_id',true),
				updated_at = NOW(), 
				updated_user_id = $cBy
			WHERE case_tag_no = '$case_tag_no';";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}


			$sql = "UPDATE tbl_inventory_detail t1,
			(SELECT inventory_id, location_id FROM tbl_inventory WHERE case_tag_no = '$case_tag_no') t2
			SET t1.location_id = t2.location_id,
				t1.updated_at = NOW(), 
				t1.updated_user_id = $cBy
			WHERE t1.inventory_id = t2.inventory_id;";
			//exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}


			$mysqli->commit();
			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 12) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'Putaway'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {

		$dataParams = array(
			'obj',
			'obj=>document_no:s:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT bin_to_uuid(transaction_id,true) transaction_id 
					FROM tbl_transaction 
					WHERE document_no = '$document_no' AND transaction_type = 'Move';";
			$result = sqlError($mysqli, __LINE__, $sql, 1);
			if ($result->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล document_no : ' . $document_no);
			}
			$transaction_id = $result->fetch_assoc()["transaction_id"];


			$sql = "UPDATE tbl_transaction 
			SET 
			updated_at = NOW(), 
			updated_user_id = $cBy
			WHERE transaction_id = uuid_to_bin('$transaction_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . $sql);
			}


			$sql = "UPDATE tbl_transaction 
				SET transaction_type = 'Temp-Move',
				editing_at = NOW(),
				editing_user_id = $cBy
				WHERE transaction_id = uuid_to_bin('$transaction_id',true) 
				AND transaction_type = 'Move';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . $sql);
			}

			$sql = "UPDATE tbl_transaction_line 
			SET status = 'Pending',
				updated_user_id = $cBy,
				updated_at = now()
			WHERE transaction_id = uuid_to_bin('$transaction_id',true) AND status = 'Complete';";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}

			$mysqli->commit();
			closeDBT($mysqli, 1, $document_no);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'Putaway'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {

		$dataParams = array(
			'obj',
			'obj=>transaction_id:s:0:0',
			'obj=>transaction_line_id:s:0:1',
			'obj=>case_tag_no:s:0:1',
			'obj=>from_location_id:s:0:1',
			'obj=>to_location_id:s:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			/* Transaction */

			$sql = "UPDATE tbl_transaction
			SET editing_at = NOW(), 
				editing_user_id = $cBy
			WHERE transaction_id = uuid_to_bin('$transaction_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			$sql = "DELETE FROM tbl_transaction_line
			WHERE transaction_id = uuid_to_bin('$transaction_id',true) AND transaction_line_id = uuid_to_bin('$transaction_line_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			/* Inventory */

			$sql = "UPDATE tbl_inventory
			SET location_id = uuid_to_bin('$from_location_id',true),
				updated_at = NOW(), 
				updated_user_id = $cBy
			WHERE case_tag_no = '$case_tag_no';";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}


			$sql = "UPDATE tbl_inventory_detail t1,
			(SELECT inventory_id, location_id FROM tbl_inventory WHERE case_tag_no = '$case_tag_no') t2
			SET t1.location_id = t2.location_id,
				t1.updated_at = NOW(), 
				t1.updated_user_id = $cBy
			WHERE t1.inventory_id = t2.inventory_id;";
			//exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}


			$mysqli->commit();
			closeDBT($mysqli, 1, 'OK');
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'Putaway'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {

		$dataParams = array(
			'obj',
			'obj=>document_no:s:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT bin_to_uuid(transaction_id,true) transaction_id 
					FROM tbl_transaction 
					WHERE document_no = '$document_no' AND transaction_type = 'Temp-Move';";
			$result = sqlError($mysqli, __LINE__, $sql, 1);
			if ($result->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล document_no : ' . $document_no);
			}
			$transaction_id = $result->fetch_assoc()["transaction_id"];

			$document_no_new = $document_no;
			$gen = false;

			if ((stripos($document_no, "mov")) === FALSE) {
				$sql = "SELECT func_GenRuningNumber('move',0) as document_no ;";
				$result = sqlError($mysqli, __LINE__, $sql, 1, 1);
				if ($result->num_rows === 0) {
					throw new Exception('ไม่พบข้อมูล document_no MOV ' . __LINE__);
				}
				$document_no_new = $result->fetch_assoc()["document_no"];
				$document_no_new = 'R' . $document_no_new;
				$gen = true;

				//exit($document_no_new);

				$sql = "UPDATE tbl_transaction
				SET 
				created_at = NOW(), 
				created_user_id = $cBy
				WHERE transaction_id = uuid_to_bin('$transaction_id',true);";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
				}
			} else {
				$sql = "UPDATE tbl_transaction 
				SET 
				updated_at = NOW(), 
				updated_user_id = $cBy
				WHERE transaction_id = uuid_to_bin('$transaction_id',true);";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
				}
			}


			$sql = "UPDATE tbl_transaction 
				SET transaction_type = 'Move', 
				document_no = '$document_no_new',
				editing_at = null,
				editing_user_id = null
				WHERE transaction_id = uuid_to_bin('$transaction_id',true) AND transaction_type = 'Temp-Move';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}


			$sql = "UPDATE tbl_transaction_line 
				SET status = 'Complete',
					updated_user_id = $cBy,
					updated_at = now()
				WHERE transaction_id = uuid_to_bin('$transaction_id',true) AND status = 'Pending';";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			$document_no = $document_no_new;
			$mysqli->commit();
			closeDBT($mysqli, 1, $document_no);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else closeDBT($mysqli, 2, 'TYPE ERROR');

$mysqli->close();
exit();
