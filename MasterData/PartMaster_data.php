<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'PartMaster'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'PartMaster'}[0] == 0) {
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
		$sql = "SELECT bin_to_uuid(part_id,true) as part_id, 
		alloy, gauge, part_no, part_name, part_width, 
		package_type, supplier_code,
		t1.status, t1.created_at, t1.updated_at, 
		t3.user_fName as created_by , t4.user_fName as updated_by
		FROM
		tbl_part_master t1
			LEFT JOIN
		tbl_supplier_master t2 ON t1.supplier_id = t2.supplier_id
			LEFT JOIN
		tbl_user t3 ON t1.created_user_id = t3.user_id
			LEFT JOIN
		tbl_user t4 ON t1.updated_user_id = t4.user_id
		Order by status, part_no;";
		//exit($sql);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'PartMaster'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {

		$dataParams = array(
			'obj',
			'obj=>part_no:s:0:1',
			'obj=>part_name:s:0:1',
			'obj=>alloy:s:0:1',
			'obj=>gauge:f:0:0.01',
			'obj=>part_width:f:0:0.01',
			'obj=>package_type:s:0:1',
			'obj=>supplier_code:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT part_no FROM tbl_part_master 
			where part_no = '$part_no'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('มี part_no นี้แล้ว');
			}

			if ($supplier_code != '') {
				$supplier_id = getSupplierID($mysqli, $supplier_code);
			} else {
				$supplier_id = '';
			}

			//exit($supplier_id);

			$sql = "INSERT INTO tbl_part_master (
				part_no, 
			part_name,
			alloy,
			gauge,
			part_width,
			supplier_id,
			created_at,
			created_user_id
			)
			values (
				'$part_no', 
			'$part_name',
			'$alloy',
			'$gauge',
			'$part_width',
			'$package_type',
			if('$supplier_id' = '', NULL, uuid_to_bin('$supplier_id',true)),
			now(),
			$cBy
			)";
			//exit($sql);
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
	if ($_SESSION['xxxRole']->{'PartMaster'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
		$dataParams = array(
			'obj',
			'obj=>part_id:s:0:1',
			'obj=>part_no:s:0:1',
			'obj=>part_name:s:0:1',
			'obj=>alloy:s:0:1',
			'obj=>gauge:f:0:0.01',
			'obj=>part_width:f:0:0.01',
			'obj=>package_type:s:0:1',
			'obj=>supplier_code:s:0:1',
			'obj=>status:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT part_no FROM tbl_part_master 
			where part_no = '$part_no' AND BIN_TO_UUID(part_id,TRUE) != '$part_id'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('มี part_no นี้แล้ว');
			}

			if ($supplier_code != '') {
				$supplier_id = getSupplierID($mysqli, $supplier_code);
			} else {
				$supplier_id = '';
			}

			$sql = "UPDATE tbl_part_master 
			SET part_no = '$part_no',
				part_name = '$part_name',
				alloy = '$alloy',
				gauge = '$gauge',
				part_width = '$part_width',
				package_type = '$package_type',
				supplier_id = if('$supplier_id' = '', NULL, uuid_to_bin('$supplier_id',true)),
				status = '$status',
				updated_at = NOW(),
				updated_user_id = $cBy
			WHERE 
				BIN_TO_UUID(part_id,TRUE) = '$part_id';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถแก้ไขข้อมูลได้');
			}

			$mysqli->commit();

			closeDBT($mysqli, 1, 'OK');
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'PartMaster'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'PartMaster'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
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

							$alloy = $row[1];
							$gauge = $row[2];
							$part_width = $row[3];
							$part_no = $row[4];
							$part_name = $row[5];
							$package_type = $row[6];
							$supplier_code = $row[7];

							$supplier_id = getSupplierID($mysqli, $supplier_code);

							$sqlArray[] = array(
								'alloy' => stringConvert($alloy),
								'gauge' => stringConvert($gauge),
								'part_width' => stringConvert($part_width),
								'part_no' => stringConvert($part_no),
								'part_name' => stringConvert($part_name),
								'package_type' => stringConvert($package_type),
								'supplier_id' => 'uuid_to_bin("' . $supplier_id . '",true)',
								'created_at' => 'now()',
								'created_user_id' => $cBy,
							);
						} else {
							$count = 1;
						}
					}

					$total = 0;
					if (count($sqlArray) > 0) {
						$sqlName = prepareNameInsert($sqlArray[0]);

						for ($i = 0, $len = count($sqlArray); $i < $len; $i++) {

							$alloy = $sqlArray[$i]['alloy'];
							$gauge = $sqlArray[$i]['gauge'];
							$part_width = $sqlArray[$i]['part_width'];
							$part_no = $sqlArray[$i]['part_no'];
							$part_name = $sqlArray[$i]['part_name'];
							$package_type = $sqlArray[$i]['package_type'];
							$supplier_id = $sqlArray[$i]['supplier_id'];
							$created_at = $sqlArray[$i]['created_at'];
							$created_user_id = $sqlArray[$i]['created_user_id'];

							//exit();
							$sql = "INSERT IGNORE INTO tbl_part_master
							$sqlName
							VALUES (
							$alloy,
							$gauge,
							$part_width,
							$part_no,
							$part_name,
							$package_type,
							$supplier_id,
							$created_at,
							$created_user_id)
							ON DUPLICATE KEY UPDATE 
							alloy = $alloy,
							gauge = $gauge,
							part_width = $part_width,
							part_name = $part_name,
							package_type = $package_type,
							supplier_id = $supplier_id,
							updated_at = NOW(),
							updated_user_id = $cBy";
							//exit($sql);
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

// function prepareValueInsertOnUpdate($header, $data)
// {
// 	$dataReturn = array();
// 	foreach ($data as $valueAr) {
// 		$typeV;
// 		$keyV;
// 		$valueV;
// 		$dataAr = array();
// 		foreach ($valueAr as $key => $value) {
// 			$keyV = $key;
// 			$valueV = $value;
// 			$dataAr[] = $valueV;
// 		}
// 	}
// 	foreach ($header as $valueArheader) {
// 		$typeVheader;
// 		$keyVheader;
// 		$valueVheader;
// 		$dataArheader = array();
// 		foreach ($valueArheader as $keyheader => $valueheader) {
// 			$keyVheader = $keyheader;
// 			$valueVheader = $valueheader;
// 			$dataArheader[] = $valueVheader;
// 		}
// 	}
// 	$dataReturn[] = join(',', $dataAr);
// 	print_r($dataReturn);
// 	exit();
// 	return join(',', $dataReturn);
// }


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
