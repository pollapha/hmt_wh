<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'CheckPickup'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'CheckPickup'}[0] == 0) {
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
			'obj=>work_order_no:s:0:0',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT
				t1.case_tag_no, t1.part_tag_no, part_no, part_name, t1.pick_check
			FROM
				tbl_order t1
					INNER JOIN
				tbl_part_master t2 ON t1.part_id = t2.part_id
				
			WHERE
				t1.work_order_no = '$work_order_no'
			order by t1.fg_tag_no, t1.case_tag_no;";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล Work Order No.');
			}

			$mysqli->commit();
			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 2) {

		$dataParams = array(
			'obj',
			'obj=>work_order_no:s:0:1',
			'obj=>case_tag_no:s:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

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
				tbl_order
			WHERE
				work_order_no = '$work_order_no' AND case_tag_no = '$case_tag_no';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล<br>Case Tag : ' . $case_tag_no . '<br>ใน Work Order นี้');
			}

			$sql = "SELECT
				case_tag_no
			FROM
				tbl_order
			WHERE
				work_order_no = '$work_order_no' AND case_tag_no = '$case_tag_no' AND pick_check = 'Yes';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('Case Tag No.นี้<br>สแกนเช็คแล้ว');
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
	if ($_SESSION['xxxRole']->{'CheckPickup'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {

		$dataParams = array(
			'obj',
			'obj=>work_order_no:s:0:1',
			'obj=>case_tag_no:s:0:1',
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
				tbl_order
			WHERE
				case_tag_no = '$case_tag_no'
					AND pick_check = 'Yes';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('Case Tag No.นี้<br>สแกนเช็คแล้ว');
			}


			$sql = "UPDATE tbl_order
			SET
			pick_check = 'Yes',
			updated_at = NOW(), 
			updated_user_id = $cBy
			WHERE work_order_no = '$work_order_no' AND case_tag_no = '$case_tag_no';";
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
	if ($_SESSION['xxxRole']->{'CheckPickup'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'CheckPickup'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'CheckPickup'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else closeDBT($mysqli, 2, 'TYPE ERROR');

$mysqli->close();
exit();
