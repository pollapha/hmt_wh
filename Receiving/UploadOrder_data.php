<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
include('../common/common.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'UploadOrder'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'UploadOrder'}[0] == 0) {
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

		$sql = "SELECT Header_DateTime,
		DN_Number,
		DN_Date_Text,
		Package_Number,
		FG_Serial_Number,
		FG_Date_Text,
		Part_No,
		BIN_TO_UUID(DN_ID,true) as DN_ID,
		Creation_Date,
		Receive_Status
		FROM tbl_dn_order";
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'UploadOrder'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {
	} else if ($type == 12) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'UploadOrder'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {

		$dataParams = array(
			'obj',
			'obj=>DN_ID:s:0:0',
			'obj=>Header_DateTime:s:0:0',
			'obj=>DN_Number:s:0:0',
			'obj=>DN_Date_Text:s:0:0',
			'obj=>Package_Number:s:0:0',
			'obj=>FG_Serial_Number:s:0:0',
			'obj=>FG_Date_Text:s:0:0',
			'obj=>Part_No:s:0:0',
			'obj=>Receive_Status:s:0:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {
			$sql = "SELECT DN_ID from tbl_dn_order where FG_Serial_Number='$FG_Serial_Number' limit 1;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$DN_ID = $row['DN_ID'];
			}

			$sql = "UPDATE tbl_dn_order 
			set 
			Header_DateTime = '$Header_DateTime',
			DN_Number = '$DN_Number',
			DN_Date_Text = '$DN_Date_Text',
			Package_Number = '$Package_Number',
			FG_Serial_Number = '$FG_Serial_Number',
			FG_Date_Text = '$FG_Date_Text',
			Part_No = '$Part_No',
			Receive_Status = '$Receive_Status',
			Creation_Date = curdate(),
			Creation_DateTime = now(),
			Created_By_ID = $cBy,
			Last_Updated_Date = curdate(),
			Last_Updated_DateTime = now(),
			Updated_By_ID = $cBy
			where DN_ID = '$DN_ID'";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถแก้ไขข้อมูลได้' . __LINE__);
			}

			$mysqli->commit();

			$sql = "SELECT Header_DateTime,
			DN_Number,
			DN_Date_Text,
			Package_Number,
			FG_Serial_Number,
			FG_Date_Text,
			BIN_TO_UUID(DN_ID,true) as DN_ID,
			Part_No,
			Creation_Date,
			Receive_Status
			FROM tbl_dn_order";
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
	if ($_SESSION['xxxRole']->{'UploadOrder'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'CustomerMaster'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {
		if (!isset($_FILES["upload"])) {
			echo json_encode(array('status' => 'server', 'mms' => 'ไม่พบไฟล์ UPLOAD'));
			closeDB($mysqli);
		}
		$randomString = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
		$fileName = $randomString . '_' . $_FILES["upload"]["name"];
		$tempName = $_FILES["upload"]["tmp_name"];
		if (move_uploaded_file($tempName, "../order_file/" . $fileName)) {
			$file_info = pathinfo("../order_file/" . $fileName);
			$myfile = fopen("../order_file/" . $file_info['basename'], "r") or die("Unable to open file!");
			$data_file = fread($myfile, filesize("../order_file/" . $file_info['basename']));
			$dataAR_line = explode('<br />', nl2br($data_file));
			fclose($myfile);

			$mysqli->autocommit(FALSE);
			try {
				$DN_Number = trim(substr($dataAR_line[1], strpos($dataAR_line[1], ":") + 1, strlen($dataAR_line[1]) - strpos($dataAR_line[1], ":")));
				$Header_DateTime = trim(substr($dataAR_line[2], strpos($dataAR_line[2], ":") + 1, strlen($dataAR_line[2]) - strpos($dataAR_line[2], ":")));
				for ($i = 0; $i < sizeof($dataAR_line); $i++) {
					if ($i >= 6) {
						if (strpos($dataAR_line[$i], '******')) {
							break;
						}
						$exp_data = explode(":", trim($dataAR_line[$i]));

						$sql = "SELECT SUBSTRING('$exp_data[3]', 14,24) as Part_No;";
						$re1 = sqlError($mysqli, __LINE__, $sql, 1);
						while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
							$Part_No = $row['Part_No'];
						}

						$Part_ID = getPartID($mysqli, $Part_No);
						$sqlArray[] = array(
							'Header_DateTime' => convertDate($Header_DateTime),
							'DN_Number' => stringConvert($exp_data[0]),
							'DN_Date_Text' => stringConvert($exp_data[1]),
							'Package_Number' => stringConvert($exp_data[2]),
							'FG_Serial_Number' => stringConvert($exp_data[3]),
							'FG_Date_Text' => stringConvert($exp_data[4]),
							'Part_No' => stringConvert($Part_No),
							'Part_ID' => 'uuid_to_bin("' . $Part_ID . '",true)',
							'Creation_Date' => 'curdate()',
							'Creation_DateTime' => 'now()',
							'Created_By_ID' => $cBy,
							'Last_Updated_Date' => 'curdate()',
							'Last_Updated_DateTime' => 'now()',
							'Updated_By_ID' => $cBy,
							'File_Name' => stringConvert($fileName),
						);
					}
				}
				$total = 0;
				if (count($sqlArray) > 0) {
					$sqlName = prepareNameInsert($sqlArray[0]);
					$sqlChunk = array_chunk($sqlArray, 500);

					for ($i = 0, $len = count($sqlChunk); $i < $len; $i++) {
						$sqlValues = prepareValueInsert($sqlChunk[$i]);
						$sql = "INSERT IGNORE INTO tbl_dn_order $sqlName VALUES $sqlValues";
						sqlError($mysqli, __LINE__, $sql, 1, 0);
						$total += $mysqli->affected_rows;
					}
					$mysqli->commit();

					if ($total == 0) throw new Exception('ไม่มีรายการอัพเดท' . $mysqli->error);
					echo '{"status":"server","mms":"Upload สำเร็จ ' . $total . '","data":[]}';
					closeDB($mysqli);
				} else {
					echo '{"status":"server","mms":"ไม่พบข้อมูลในไฟล์ ' . count($sqlArray) . '","data":[]}';
					closeDB($mysqli);
				}

				$sql = "SELECT Header_DateTime,
					DN_Number,
					DN_Date_Text,
					Package_Number,
					FG_Serial_Number,
					FG_Date_Text,
					Part_No,
					Creation_Date,
					Receive_Status
					FROM tbl_dn_order";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
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
