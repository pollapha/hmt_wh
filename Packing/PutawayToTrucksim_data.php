<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'PutawayToTrucksim'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'PutawayToTrucksim'}[0] == 0) {
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
include('../common/common.php');
if ($type <= 10) //data
{
	if ($type == 1) {


		$sql = "SELECT 
				BIN_TO_UUID(t1.transaction_id, TRUE) AS transaction_id,
				document_no, dos_no, location_code to_location,
				t1.work_order_no
			FROM
				tbl_transaction t1
					LEFT JOIN
				tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
					INNER JOIN
				tbl_order_header t3 ON t1.order_header_id = t3.order_header_id
					INNER JOIN
				tbl_location_master t4 ON t1.location_id = t4.location_id
			WHERE
				t1.editing_user_id = $cBy
					AND t1.transaction_type = 'Temp-Move'
					AND substring(document_no, 1, 1) = 'P'
					AND t2.status = 'Pending'
			GROUP BY t1.transaction_id ORDER BY t1.editing_at DESC LIMIT 1";
		// exit($sql);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		$header = jsonRow($re1, true, 0);

		$body = [];

		if (count($header) > 0) {
			$work_order_no = $header[0]['work_order_no'];

			$sql = "SELECT 
				part_no,
				part_name,
				t2.repack,
				t2.work_order_no,
				t2.fg_tag_no,
				SUM(t2.net_per_pcs) net_per_pallet,
				SUM(t2.qty) qty,
				t2.package_no,
				if(location_area = 'truck-sim',location_code,'') location_code
			FROM
				tbl_order_header t1
					INNER JOIN
				tbl_order t2 ON t1.order_header_id = t2.order_header_id
					LEFT JOIN
				tbl_inventory_detail t3 ON t2.part_tag_no = t3.part_tag_no
					LEFT JOIN
				tbl_part_master t5 ON t3.part_id = t5.part_id
					LEFT JOIN
				tbl_location_master t6 ON t3.location_id = t6.location_id
			WHERE
				t2.work_order_no = '$work_order_no'
					AND t2.status = 'Complete'
			GROUP BY work_order_no, fg_tag_no
			ORDER BY work_order_no, fg_tag_no;";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);

			$body = jsonRow($re1, true, 0);
		}

		$returnData = ['header' => $header, 'body' => $body];

		closeDBT($mysqli, 1, $returnData);
	} else if ($type == 2) {

		$dataParams = array(
			'obj',
			'obj=>work_order_no:s:0:0',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));
		$mysqli->autocommit(FALSE);
		try {

			$body = [];

			$sql = "SELECT 
				part_no,
				part_name,
				t2.repack,
				t2.work_order_no,
				t2.fg_tag_no,
				SUM(t2.net_per_pcs) net_per_pallet,
				SUM(t2.qty) qty,
				t2.package_no,
				'' location_code
			FROM
				tbl_order_header t1
					INNER JOIN
				tbl_order t2 ON t1.order_header_id = t2.order_header_id
					LEFT JOIN
				tbl_inventory_detail t3 ON t2.part_tag_no = t3.part_tag_no
					LEFT JOIN
				tbl_part_master t5 ON t3.part_id = t5.part_id
					LEFT JOIN
				tbl_location_master t6 ON t3.location_id = t6.location_id
			WHERE
				t2.work_order_no = '$work_order_no'
					AND t2.status = 'Complete'
			GROUP BY work_order_no, fg_tag_no
			ORDER BY work_order_no, fg_tag_no;";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$body = jsonRow($re1, true, 0);

			if (count($body) > 0) {
				$returnData = ['body' => $body];
			} else {
				$returnData = ['body' => ''];
			}

			closeDBT($mysqli, 1, $returnData);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 3) {

		$dataParams = array(
			'obj',
			'obj=>work_order_no:s:0:0',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT
				work_order_no
			FROM
				tbl_order
			WHERE
				work_order_no = '$work_order_no';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล Work Order No.');
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
	if ($_SESSION['xxxRole']->{'PutawayToTrucksim'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {
		$dataParams = array(
			'obj',
			'obj=>document_no:s:0:0',
			'obj=>work_order_no:s:0:1',
			'obj=>to_location:s:0:1',
			'obj=>tag_no:s:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			/* Transaction */

			$explode = explode(' | ', $to_location);
			$to_location = $explode[0];
			$to_location_id = getLocationID($mysqli, $to_location);

			$sql = "SELECT bin_to_uuid(order_header_id,true) order_header_id FROM tbl_order WHERE work_order_no = '$work_order_no';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($re1->num_rows === 0) {
				throw new Exception('ไม่พบข้อมูล Workorder No.');
			}
			$order_header_id = $re1->fetch_assoc()["order_header_id"];

			$sql = "SELECT bin_to_uuid(transaction_id,true) transaction_id, document_no FROM tbl_transaction WHERE document_no = '$document_no'; ";
			$result = sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($result->num_rows === 0) {
				$sql = "SELECT func_GenRuningNumber('temp',0) as document_no ;";
				$result = sqlError($mysqli, __LINE__, $sql, 1, 1);
				if ($result->num_rows === 0) {
					throw new Exception('ไม่พบข้อมูล Document_no TEMP');
				}
				$document_no = $result->fetch_assoc()["document_no"];
				$document_no = 'P' . $document_no;

				$sql = "INSERT INTO tbl_transaction 
				(document_no, document_date, transaction_type, order_header_id, location_id, work_order_no, created_at, created_user_id) 
				VALUES
				('$document_no',NOW(), 'Temp-Move', uuid_to_bin('$order_header_id',true), uuid_to_bin('$to_location_id',true), '$work_order_no',now(), $cBy);";
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

			$prefix = substr($tag_no, 0, 1);


			$fg_tag_no = $tag_no;
			$sql = "SELECT fg_tag_no FROM tbl_transaction_line WHERE transaction_id = uuid_to_bin('$transaction_id',true) AND fg_tag_no = '$fg_tag_no';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('FG Tag No. นี้<br>ทำการย้ายโลเคชั่นแล้ว ' . __LINE__);
			}

			// $sql = "SELECT fg_tag_no FROM tbl_inventory_line WHERE fg_tag_no = '$fg_tag_no' AND location_id = uuid_to_bin('$to_location_id',true);";
			// $re1 = sqlError($mysqli, __LINE__, $sql, 1);
			// if ($re1->num_rows > 0) {
			// 	throw new Exception('FG Tag No. นี้<br>มีอยู่ในโลเคชั่นนี้แล้ว ' . __LINE__);
			// }


			$sql = "INSERT INTO tbl_transaction_line 
			( pallet_no, case_tag_no, fg_tag_no, part_id, qty, net_per_pallet, work_order_no,
			measurement_cbm, certificate_no, invoice_no,
			transaction_id, from_location_id, to_location_id, 
			created_at, updated_at, created_user_id, updated_user_id
			)
			SELECT t1.pallet_no, t1.case_tag_no, t1.fg_tag_no, t1.part_id, SUM(t1.qty), SUM(t1.net_per_pcs), t1.work_order_no,
			t3.measurement_cbm, t3.certificate_no, t3.invoice_no, 
			uuid_to_bin('$transaction_id',true), t2.location_id, uuid_to_bin('$to_location_id',true),
			now(), now(), $cBy, $cBy
			FROM tbl_order t1 
			INNER JOIN tbl_inventory_detail t2 ON t1.part_tag_no = t2.part_tag_no
			LEFT JOIN tbl_inventory t3 ON t2.inventory_id = t3.inventory_id
			WHERE t1.fg_tag_no = '$fg_tag_no'
			GROUP BY t1.fg_tag_no, t1.case_tag_no;";
			// exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			$sql = "INSERT INTO tbl_transaction_detail 
			( part_tag_no, part_id, qty, net_per_pcs, 
			transaction_line_id, from_location_id, to_location_id, 
			created_at, updated_at, created_user_id, updated_user_id
			)
			SELECT t1.part_tag_no, t1.part_id, t1.qty, t1.net_per_pcs,
			t3.transaction_line_id, t2.location_id, uuid_to_bin('$to_location_id',true),
			now(), now(), $cBy, $cBy
			FROM tbl_order t1 
			INNER JOIN tbl_inventory_detail t2 ON t1.part_tag_no = t2.part_tag_no
			INNER JOIN tbl_transaction_line t3 ON t1.fg_tag_no = t3.fg_tag_no AND t1.case_tag_no = t3.case_tag_no
			WHERE t1.fg_tag_no = '$fg_tag_no' AND t3.transaction_id = uuid_to_bin('$transaction_id',true)
			GROUP BY t1.fg_tag_no, t1.part_tag_no;";
			// exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			/* Inventory */


			$sql = "UPDATE tbl_inventory_detail t1,
			(SELECT t1.part_tag_no, t2.to_location_id 
			FROM tbl_order t1 INNER JOIN tbl_transaction_detail t2 ON t1.part_tag_no = t2.part_tag_no
			INNER JOIN tbl_transaction_line t3 ON t2.transaction_line_id = t3.transaction_line_id
			WHERE t3.transaction_id = uuid_to_bin('$transaction_id',true) AND t1.fg_tag_no = '$fg_tag_no') t2
			SET t1.location_id = t2.to_location_id,
				t1.updated_at = NOW(), 
				t1.updated_user_id = $cBy
			WHERE t1.part_tag_no = t2.part_tag_no;";
			// exit($sql);
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
	if ($_SESSION['xxxRole']->{'PutawayToTrucksim'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
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
	if ($_SESSION['xxxRole']->{'PutawayToTrucksim'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
		$dataParams = array(
			'obj',
			'obj=>document_no:s:0:1',
			'obj=>fg_tag_no:s:0:0',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			/* Transaction */

			$sql = "SELECT bin_to_uuid(transaction_id,true) transaction_id, document_no 
			FROM tbl_transaction WHERE document_no = '$document_no'; ";
			$result = sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($result->num_rows > 0) {
				$transaction_id = $result->fetch_assoc()["transaction_id"];
			}

			$sql = "UPDATE tbl_transaction
			SET editing_at = NOW(), 
				editing_user_id = $cBy
			WHERE transaction_id = uuid_to_bin('$transaction_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			/* Inventory */

			$sql = "UPDATE tbl_inventory_detail t1,
			(SELECT t3.part_tag_no, t1.from_location_id 
			FROM tbl_transaction_detail t1
			INNER JOIN tbl_transaction_line t2 ON t1.transaction_line_id = t2.transaction_line_id
			INNER JOIN tbl_inventory_detail t3 ON t1.part_tag_no = t3.part_tag_no
			WHERE transaction_id = uuid_to_bin('$transaction_id',true)
			AND fg_tag_no = '$fg_tag_no') t2
			SET t1.location_id = t2.from_location_id,
				t1.updated_at = NOW(), 
				t1.updated_user_id = $cBy
			WHERE t1.part_tag_no = t2.part_tag_no;";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}


			$sql = "SELECT bin_to_uuid(t1.transaction_detail_id,true) transaction_detail_id, t3.part_tag_no, t1.from_location_id 
			FROM tbl_transaction_detail t1
			INNER JOIN tbl_transaction_line t2 ON t1.transaction_line_id = t2.transaction_line_id
			INNER JOIN tbl_inventory_detail t3 ON t1.part_tag_no = t3.part_tag_no
			WHERE transaction_id = uuid_to_bin('$transaction_id',true)
			AND fg_tag_no = '$fg_tag_no';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล  ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$transaction_detail_id = $row['transaction_detail_id'];

				$sql = "DELETE FROM tbl_transaction_detail
				WHERE transaction_detail_id = uuid_to_bin('$transaction_detail_id',true);";

				// exit($sql);
				sqlError($mysqli, __LINE__, $sql, 1, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
				}
			}

			$sql = "DELETE FROM tbl_transaction_line
			WHERE transaction_id = uuid_to_bin('$transaction_id',true) AND fg_tag_no = '$fg_tag_no';";

			// exit($sql);
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
	if ($_SESSION['xxxRole']->{'PutawayToTrucksim'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {


		$dataParams = array(
			'obj',
			'obj=>document_no:s:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT bin_to_uuid(transaction_id,true) transaction_id, bin_to_uuid(order_header_id,true) order_header_id
					FROM tbl_transaction 
					WHERE document_no = '$document_no' AND transaction_type = 'Temp-Move';";
			$result = sqlError($mysqli, __LINE__, $sql, 1);
			if ($result->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล document_no : ' . $document_no);
			}
			while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
				$transaction_id = $row['transaction_id'];
				$order_header_id = $row['order_header_id'];
			}

			$document_no_new = $document_no;
			$gen = false;

			if ((stripos($document_no, "mov")) === FALSE) {
				$sql = "SELECT func_GenRuningNumber('move',0) as document_no ;";
				$result = sqlError($mysqli, __LINE__, $sql, 1, 1);
				if ($result->num_rows === 0) {
					throw new Exception('ไม่พบข้อมูล document_no MOV ' . __LINE__);
				}
				$document_no_new = $result->fetch_assoc()["document_no"];
				$document_no_new = 'P' . $document_no_new;
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

			$sql = "SELECT
				DISTINCT t4.dos_no
			FROM
				tbl_order t1
					inner join tbl_order_header t4 ON t1.order_header_id = t4.order_header_id
			WHERE
				t1.order_header_id = uuid_to_bin('$order_header_id',true);";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$dos_no = $row['dos_no'];
				}
			}

			$sql = "SELECT
				t4.dos_no, t1.work_order_no, t1.part_tag_no, location_area
			FROM
				tbl_order t1
					INNER JOIN tbl_inventory_detail t2 ON t1.part_tag_no = t2.part_tag_no
					INNER JOIN tbl_location_master t3 ON t2.location_id = t3.location_id
					inner join tbl_order_header t4 ON t1.order_header_id = t4.order_header_id
			WHERE
				t1.order_header_id = uuid_to_bin('$order_header_id',true)
				AND location_area != 'truck-sim';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				$data = ['dos_no' => ''];
			} else {
				$data = ['dos_no' => $dos_no];
			}

			$document_no = $document_no_new;

			$mysqli->commit();
			closeDBT($mysqli, 1, $data);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else closeDBT($mysqli, 2, 'TYPE ERROR');

$mysqli->close();
exit();
