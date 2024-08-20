<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'ConfirmReceive'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'ConfirmReceive'}[0] == 0) {
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

		$dataParams = array(
			'obj',
			'obj=>document_no:s:0:0',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT
				document_no, t1.pallet_no, t1.case_tag_no, part_no, part_name, t1.tag_check
			FROM
				tbl_inventory t1
					INNER JOIN
				tbl_transaction_line t2 ON t1.transaction_line_id = t2.transaction_line_id
					INNER JOIN
				tbl_transaction t3 ON t2.transaction_id = t3.transaction_id
					INNER JOIN
				tbl_part_master t4 ON t1.part_id = t4.part_id
			WHERE
				document_no = '$document_no' order by case_tag_no;";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล Document No.');
			}

			$mysqli->commit();
			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 2) {
	} else if ($type == 3) {

		$dataParams = array(
			'obj',
			'obj=>document_no:s:0:1',
			'obj=>scan_check:s:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$prefix = substr($scan_check, 0, 1);
			if ($prefix == 'R') {
				$sql = "SELECT
					case_tag_no
				FROM
					tbl_inventory
				WHERE
					case_tag_no = '$scan_check';";
				//exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล Case Tag No.');
				}

				$sql = "SELECT
					case_tag_no
				FROM
					tbl_inventory
				WHERE
					case_tag_no = '$scan_check'
						AND tag_check = 'Yes';";
				//exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows > 0) {
					throw new Exception('Case Tag No.นี้<br>สแกนเช็คแล้ว');
				}

				$sql = "SELECT
					document_no
				FROM
					tbl_inventory t1
						INNER JOIN
					tbl_transaction_line t2 ON t1.transaction_line_id = t2.transaction_line_id
						INNER JOIN
					tbl_transaction t3 ON t2.transaction_id = t3.transaction_id
				WHERE
					document_no = '$document_no' AND t1.case_tag_no = '$scan_check';";
				//exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล Tag ตรงกับเอกสาร');
				}
			} else {

				$sql = "SELECT
					pallet_no
				FROM
					tbl_inventory
				WHERE
					pallet_no = '$scan_check';";
				//exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล Pallet No.');
				}

				$sql = "SELECT
					case_tag_no
				FROM
					tbl_inventory
				WHERE
					pallet_no = '$scan_check'
						AND tag_check = 'Yes';";
				//exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows > 0) {
					throw new Exception('Pallet No. นี้<br>สแกนเช็คแล้ว');
				}

				$sql = "SELECT
					document_no
				FROM
					tbl_inventory t1
						INNER JOIN
					tbl_transaction_line t2 ON t1.transaction_line_id = t2.transaction_line_id
						INNER JOIN
					tbl_transaction t3 ON t2.transaction_id = t3.transaction_id
				WHERE
					document_no = '$document_no' AND t1.pallet_no = '$scan_check';";
				//exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล Tag ตรงกับเอกสาร');
				}
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
	if ($_SESSION['xxxRole']->{'ConfirmReceive'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {

		$dataParams = array(
			'obj',
			'obj=>document_no:s:0:1',
			'obj=>pallet_no:s:0:1',
			'obj=>case_tag_no:s:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT
				document_no
			FROM
				tbl_inventory t1
					INNER JOIN
				tbl_transaction_line t2 ON t1.transaction_line_id = t2.transaction_line_id
					INNER JOIN
				tbl_transaction t3 ON t2.transaction_id = t3.transaction_id
			WHERE
				document_no = '$document_no';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล Document No.');
			}

			$sql = "SELECT
				case_tag_no
			FROM
				tbl_inventory
			WHERE
				case_tag_no = '$case_tag_no';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล Case Tag No.');
			}

			$sql = "SELECT
				case_tag_no
			FROM
				tbl_inventory
			WHERE
				case_tag_no = '$case_tag_no'
					AND tag_check = 'Yes';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('Case Tag No.นี้<br>สแกนเช็คแล้ว');
			}

			$sql = "SELECT
				pallet_no
			FROM
				tbl_inventory
			WHERE
				pallet_no = '$pallet_no';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล Pallet No.');
			}

			$sql = "SELECT
				case_tag_no
			FROM
				tbl_inventory
			WHERE
				pallet_no = '$pallet_no'
					AND tag_check = 'Yes';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('Pallet No. นี้<br>สแกนเช็คแล้ว');
			}

			$sql = "SELECT
				case_tag_no, pallet_no 
			FROM
				tbl_inventory
			WHERE
				pallet_no = '$pallet_no' AND case_tag_no = '$case_tag_no';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('Tag ไม่ตรงกัน');
			}


			$sql = "UPDATE tbl_inventory
			SET
			tag_check = 'Yes',
			updated_at = NOW(), 
			updated_user_id = $cBy
			WHERE pallet_no = '$pallet_no' AND case_tag_no = '$case_tag_no';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			$mysqli->commit();
			closeDBT($mysqli, 1, 'OK');
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 12) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'ConfirmReceive'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'ConfirmReceive'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'ConfirmReceive'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else closeDBT($mysqli, 2, 'TYPE ERROR');

$mysqli->close();
exit();
