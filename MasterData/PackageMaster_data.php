<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'PackageMaster'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'PackageMaster'}[0] == 0) {
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
		$sql = "SELECT Package_Name, 
		Package_Type, 
		Package_Width_MM, 
		Package_Length_MM,  
		Package_Height_MM,
		Status,
		Creation_Date
		FROM tbl_package_master";
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'PackageMaster'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {

		$dataParams = array(
			'obj',
			'obj=>Package_Name:s:0:0',
			'obj=>Package_Type:s:0:1',
			'obj=>Package_Width_MM:i:0:1',
			'obj=>Package_Length_MM:i:0:1',
			'obj=>Package_Height_MM:i:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {
			$sql = "SELECT Package_Name FROM tbl_package_master 
			where Package_Name = '$Package_Name'";
			if ((sqlError($mysqli, __LINE__, $sql, 1))->num_rows > 0) {
				throw new Exception('มี Package_Name นี้แล้ว');
			}

			$sql = "INSERT INTO tbl_package_master (
			Package_Name, 
			Package_Type, 
			Package_Width_MM, 
			Package_Length_MM,  
			Package_Height_MM,
			Creation_Date,
			Creation_DateTime,
			Created_By_ID,
			Last_Updated_Date,
			Last_Updated_DateTime,
			Updated_By_ID)
			values (
				concat(SUBSTRING('$Package_Type', 1,1),$Package_Width_MM,$Package_Length_MM,$Package_Height_MM),
			'$Package_Type',
			$Package_Width_MM,
			$Package_Length_MM,
			$Package_Height_MM,
			curdate(),
			now(),
			$cBy,
			curdate(),
			now(),
			$cBy)";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}
			$mysqli->commit();

			$sql = "SELECT Package_Name, 
			Package_Type, 
			Package_Width_MM, 
			Package_Length_MM,  
			Package_Height_MM,
			Status,
			Creation_Date
			FROM tbl_package_master";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 12) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'PackageMaster'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {

		$dataParams = array(
			'obj',
			//'obj=>Package_ID:s:0:0',
			'obj=>Package_Name:s:0:1',
			'obj=>Package_Type:s:0:1',
			'obj=>Package_Width_MM:i:0:1',
			'obj=>Package_Length_MM:i:0:1',
			'obj=>Package_Height_MM:i:0:1',
			'obj=>Status:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "UPDATE tbl_package_master 
			set Package_Name = concat(SUBSTRING('$Package_Type', 1,1),$Package_Width_MM,$Package_Length_MM,$Package_Height_MM),
			Package_Type ='$Package_Type', 
			Package_Width_MM ='$Package_Width_MM', 
			Package_Length_MM ='$Package_Length_MM',  
			Package_Height_MM ='$Package_Height_MM',
			Status = '$Status',
			Creation_Date = curdate(),
			Creation_DateTime = now(),
			Created_By_ID = $cBy,
			Last_Updated_Date = curdate(),
			Last_Updated_DateTime = now(),
			Updated_By_ID = $cBy
			where Package_Name = '$Package_Name'";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถแก้ไขข้อมูลได้');
			}

			$mysqli->commit();

			$sql = "SELECT Package_Name, 
			Package_Type, 
			Package_Width_MM, 
			Package_Length_MM,  
			Package_Height_MM,
			Status,
			Creation_Date
			FROM tbl_package_master";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$data =  jsonRow($re1, true, 0);
			closeDBT($mysqli, 1, $data);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'PackageMaster'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'PackageMaster'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else closeDBT($mysqli, 2, 'TYPE ERROR');

$mysqli->close();
exit();
