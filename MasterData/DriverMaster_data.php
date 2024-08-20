<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'DriverMaster'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'DriverMaster'}[0] == 0) {
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
			BIN_TO_UUID(driver_id,TRUE) AS driver_id,
			driver_name,
			driver_phone,
			t1.status,
			t1.created_at,
			t1.updated_at,
			t2.user_fName as created_by,
			t3.user_fName as updated_by

		FROM 
			tbl_driver_master t1
				LEFT JOIN
			tbl_user t2 ON t1.created_user_id = t2.user_id
				LEFT JOIN
			tbl_user t3 ON t1.updated_user_id = t3.user_id
		ORDER BY status, driver_name;";
		//exit($sql);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'DriverMaster'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {

		$dataParams = array(
			'obj',
			'obj=>driver_name:s:0:1',
			'obj=>driver_phone:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {
			$sql = "SELECT 
				driver_name 
			FROM 
				tbl_driver_master 
			WHERE 
				driver_name = '$driver_name';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('มีชื่อนี้แล้ว');
			}

			$driver_phone = str_replace('-', '', $driver_phone);

			$sql = "INSERT INTO tbl_driver_master (
				driver_name,
			driver_phone,
			created_at,
			created_user_id )
			VALUES (
				'$driver_name',
				'$driver_phone',
				now(),
				$cBy )";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
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
	if ($_SESSION['xxxRole']->{'DriverMaster'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {

		$dataParams = array(
			'obj',
			'obj=>driver_id:s:0:0',
			'obj=>driver_name:s:0:1',
			'obj=>driver_phone:s:0:1',
			'obj=>status:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				driver_phone 
			FROM 
				tbl_driver_master 
			WHERE 
				driver_name = '$driver_name'
					AND driver_id != uuid_to_bin('$driver_id',true);";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('มีชื่อนี้แล้ว');
			}

			$driver_phone = str_replace('-', '', $driver_phone);

			$sql = "UPDATE tbl_driver_master 
			SET 
				driver_name = '$driver_name',
				driver_phone = '$driver_phone',
				status = '$status',
				updated_at = NOW(),
				updated_user_id = $cBy
			WHERE
				BIN_TO_UUID(driver_id,TRUE) = '$driver_id';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถแก้ไขข้อมูลได้');
			}

			//exit($driver_id);

			$mysqli->commit();

			closeDBT($mysqli, 1, 'OK');
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'DriverMaster'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'DriverMaster'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else closeDBT($mysqli, 2, 'TYPE ERROR');

$mysqli->close();
exit();
