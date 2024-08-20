<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'SupplierMaster'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'SupplierMaster'}[0] == 0) {
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


require('../vendor/autoload.php');
include('../common/common.php');
include('../php/connection.php');


if ($type <= 10) //data
{
	if ($type == 1) {
		$sql = "SELECT 
			BIN_TO_UUID(supplier_id,TRUE) AS supplier_id,
			supplier_code,
			supplier_name,
			t1.status,
			t1.created_at,
			t1.updated_at,
			t2.user_fName as created_by,
			t3.user_fName as updated_by

		FROM 
			tbl_supplier_master t1
				LEFT JOIN
			tbl_user t2 ON t1.created_user_id = t2.user_id
				LEFT JOIN
			tbl_user t3 ON t1.updated_user_id = t3.user_id
		ORDER BY status, supplier_name;";
		//exit($sql);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'SupplierMaster'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {

		$dataParams = array(
			'obj',
			'obj=>supplier_code:s:0:1',
			'obj=>supplier_name:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {
			$sql = "SELECT 
				supplier_code 
			FROM 
				tbl_supplier_master 
			WHERE 
				supplier_code = '$supplier_code';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('มี Supplier นี้แล้ว');
			}


			$sql = "INSERT INTO tbl_supplier_master (
				supplier_code,
			supplier_name,
			created_at,
			created_user_id )
			VALUES (
				'$supplier_code',
				'$supplier_name',
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
	if ($_SESSION['xxxRole']->{'SupplierMaster'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {

		$dataParams = array(
			'obj',
			'obj=>supplier_id:s:0:0',
			'obj=>supplier_code:s:0:1',
			'obj=>supplier_name:s:0:1',
			'obj=>status:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				supplier_name 
			FROM 
				tbl_supplier_master 
			WHERE 
				supplier_code = '$supplier_code'
					AND supplier_id != uuid_to_bin('$supplier_id',true);";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('มี Supplier Code นี้แล้ว');
			}

			$sql = "UPDATE tbl_supplier_master 
			SET 
				supplier_code = '$supplier_code',
				supplier_name = '$supplier_name',
				status = '$status',
				updated_at = NOW(),
				updated_user_id = $cBy
			WHERE
				BIN_TO_UUID(supplier_id,TRUE) = '$supplier_id';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถแก้ไขข้อมูลได้');
			}

			//exit($supplier_id);

			$mysqli->commit();

			closeDBT($mysqli, 1, 'OK');
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'SupplierMaster'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'SupplierMaster'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {

		if (!isset($_FILES["upload"])) {
			echo json_encode(array('status' => 'server', 'mms' => 'ไม่พบไฟล์ UPLOAD'));
			closeDB($mysqli);
		}
		$randomString = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
		$fileName = $randomString . '_' . $_FILES["upload"]["name"];
		$tempName = $_FILES["upload"]["tmp_name"];
		if (move_uploaded_file($tempName, "../temp_fileupload/" . $fileName)) {
			$file_info = pathinfo("../temp_fileupload/" . $fileName);
			$myfile = fopen("../temp_fileupload/" . $file_info['basename'], "r") or die("Unable to open file!");
			$data_file = fread($myfile, filesize("../temp_fileupload/" . $file_info['basename']));
			$file_ext = pathinfo($fileName, PATHINFO_EXTENSION);
			$allowed_ext = ['xls', 'csv', 'xlsx'];
			fclose($myfile);

			$mysqli->autocommit(FALSE);
			try {
				if (in_array($file_ext, $allowed_ext)) {
					$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load('../temp_fileupload/' . $fileName);
					$data = $spreadsheet->getActiveSheet()->toArray();
					$count = 0;

					foreach ($data as $row) {
						if ($count > 0) {

							$Province = $row[1];
							$Sub_Zone = $row[2];
							$supplier_code = $row[3];
							$supplier_name = $row[5];
							$Address = $row[6];
							$GPS = $row[7];


							$sqlArray[] = array(
								'Province' => stringConvert($Province),
								'Sub_Zone' => stringConvert($Sub_Zone),
								'supplier_code' => stringConvert($supplier_code),
								'supplier_name' => stringConvert($supplier_name),
								'Address' => stringConvert($Address),
								'GPS' => stringConvert($GPS),
								'geo' => $supplier_geo,
								'Customer_ID' => 'uuid_to_bin("' . $Customer_ID . '",true)',
								'created_user_id' => $cBy,
								'created_atTime' => 'now()',
								'created_at' => 'curdate()'
							);
						} else {
							$count = 1;
						}
					}

					$total = 0;
					if (count($sqlArray) > 0) {
						$sqlName = prepareNameInsert($sqlArray[0]);

						for ($i = 0, $len = count($sqlArray); $i < $len; $i++) {

							$Province = $sqlArray[$i]['Province'];
							$Sub_Zone = $sqlArray[$i]['Sub_Zone'];
							$supplier_code = $sqlArray[$i]['supplier_code'];
							$supplier_name = $sqlArray[$i]['supplier_name'];
							$Address = $sqlArray[$i]['Address'];
							$GPS = $sqlArray[$i]['GPS'];
							$geo = $sqlArray[$i]['geo'];
							$Customer_ID = $sqlArray[$i]['Customer_ID'];
							$created_user_id = $sqlArray[$i]['created_user_id'];
							$created_atTime = $sqlArray[$i]['created_atTime'];
							$created_at = $sqlArray[$i]['created_at'];

							//exit();
							$sql = "INSERT IGNORE INTO tbl_supplier_master
							$sqlName
							VALUES (
							$Province,
							$Sub_Zone,
							$supplier_code,
							$supplier_name,
							$Address,
							$GPS,
							ST_GeomFromText('$geo'),
							$Customer_ID,
							$created_user_id,
							$created_atTime,
							$created_at)
							ON DUPLICATE KEY UPDATE 
							Province = $Province,
							Sub_Zone = $Sub_Zone,
							supplier_name = $supplier_name,
							Customer_ID = $Customer_ID,
							Address = $Address,
							GPS = $GPS,
							geo = ST_GeomFromText('$geo'),
							Last_Updated_DateTime = NOW(),
							Updated_By_ID = $cBy";
							sqlError($mysqli, __LINE__, $sql, 1, 0);
							$total += $mysqli->affected_rows;
							$mysqli->commit();
						}

						$mysqli->commit();

						if ($total == 0) throw new Exception('ไม่มีรายการอัพเดท' . $mysqli->error);
						echo '{"status":"server","mms":"Upload สำเร็จ ' . $total . '","data":[]}';
						closeDB($mysqli);
					} else {
						echo '{"status":"server","mms":"ไม่พบข้อมูลในไฟล์ ' . count($sqlArray) . '","data":[]}';
						closeDB($mysqli);
					}

					// $total = 0;
					// if (count($sqlArray) > 0) {
					// 	$sqlName = prepareNameInsert($sqlArray[0]);
					// 	$sqlChunk = array_chunk($sqlArray, 500);

					// 	for ($i = 0, $len = count($sqlChunk); $i < $len; $i++) {
					// 		$sqlValues = prepareValueInsert($sqlChunk[$i]);
					// 		$sql = "INSERT IGNORE INTO tbl_supplier_master $sqlName VALUES $sqlValues";
					// 		sqlError($mysqli, __LINE__, $sql, 1, 0);
					// 		$total += $mysqli->affected_rows;
					// 	}
					// 	//exit();
					// 	$mysqli->commit();

					// 	if ($total == 0) throw new Exception('ไม่มีรายการอัพเดท' . $mysqli->error);
					// 	echo '{"status":"server","mms":"Upload สำเร็จ ' . $total . '","data":[]}';
					// 	closeDB($mysqli);
					// } else {
					// 	echo '{"status":"server","mms":"ไม่พบข้อมูลในไฟล์ ' . count($sqlArray) . '","data":[]}';
					// 	closeDB($mysqli);
					// }
				}
				closeDBT($mysqli, 1, jsonRow($re1, true, 0));
			} catch (Exception $e) {
				$mysqli->rollback();
				echo '{"status":"server","mms":"' . $e->getMessage() . '","sname":[]}';
				closeDB($mysqli);
			}
		} else echo json_encode(array('status' => 'server', 'mms' => 'ข้อมูลในไฟล์ไม่ถูกต้อง', 'sname' => array()));
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
function insert($mysqli, $tableName, $data, $error)
{
	$sql = "INSERT into $tableName" . prepareInsert($data);
	sqlError($mysqli, __LINE__, $sql, 1);
	if ($mysqli->affected_rows == 0) {
		throw new Exception($error);
	}
}
function convertDate($valueV)
{
	if (strlen($valueV) > 0) {
		if (is_a($valueV, 'DateTime')) {
			$v = "'" . $valueV->format('Y-m-d') . "'";
		} else {
			$valueV1 = explode('-', $valueV);
			$valueV2 = explode('/', $valueV);
			$valueV3 = explode('.', $valueV);
			$valueV4 = strlen($valueV);
			if (count($valueV1) == 3) {
				$v = switchDate($valueV1);
			} else if (count($valueV2) == 3) {
				$v = switchDate($valueV2);
			} else if (count($valueV3) == 3) {
				$v = switchDate($valueV3);
			} else if ($valueV4 == 8) {
				$v = "'" . substr($valueV, 0, 4) . '-' . substr($valueV, 4, 2) . '-' . substr($valueV, 6, 2) . "'";
			} else {
				$UNIX_DATE = ($valueV - 25569) * 86400;
				$v = "'" . gmdate("Y-m-d", $UNIX_DATE) . "'";
			}
		}
	} else {
		return 'null';
	}


	return $v;
}

function switchDate($d)
{
	if (strlen($d[0]) == 4) {
		return "'" . "$d[0]-$d[1]-$d[2]" . "'";
	} else {
		return "'" . "$d[2]-$d[1]-$d[0]" . "'";
	}
}

$mysqli->close();
exit();
