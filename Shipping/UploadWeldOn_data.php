<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'UploadWeldOn'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'UploadWeldOn'}[0] == 0) {
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
		$re = select_group($mysqli);
		closeDBT($mysqli, 1, $re);
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'UploadWeldOn'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {
	} else if ($type == 12) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'UploadWeldOn'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'UploadWeldOn'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {

		$Weld_On_No  = $_POST['obj'];

		$mysqli->autocommit(FALSE);
		try {
			$sql = "DELETE FROM tbl_weld_on_order
			where Weld_On_No = '$Weld_On_No';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถลบข้อมูลได้' . __LINE__);
			}

			$mysqli->commit();

			$sql = "SELECT Customer,
				Dock,
				Delivery_DateTime,
				Qty,
				Weld_On_No,
				Part_No,
				SNP,
				Package_Type,
				Pick_Qty,
				Pick_Status,
				Ship_Qty,
				Ship_Status,
				Slide_Status,
				Creation_DateTime,
				Created_By_ID,
				Creation_Pick_DateTime,
				Created_Pick_By_ID,
				Creation_Ship_DateTime,
				Created_Ship_By_ID
				FROM tbl_weld_on_order
				order by Delivery_DateTime, Weld_On_No";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'UploadWeldOn'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {

		if (!isset($_FILES["upload"])) {
			echo json_encode(array('status' => 'server', 'mms' => 'ไม่พบไฟล์ UPLOAD'));
			closeDB($mysqli);
		}
		$randomString = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
		$fileName = $randomString . '_' . $_FILES["upload"]["name"];
		$tempName = $_FILES["upload"]["tmp_name"];
		if (move_uploaded_file($tempName, "../d-note_file/" . $fileName)) {
			$file_info = pathinfo("../d-note_file/" . $fileName);
			$myfile = fopen("../d-note_file/" . $file_info['basename'], "r") or die("Unable to open file!");
			$data_file = fread($myfile, filesize("../d-note_file/" . $file_info['basename']));
			$file_ext = pathinfo($fileName, PATHINFO_EXTENSION);
			$allowed_ext = ['xls', 'csv', 'xlsx'];
			fclose($myfile);

			$mysqli->autocommit(FALSE);
			try {
				if (in_array($file_ext, $allowed_ext)) {
					$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load('../d-note_file/' . $fileName);
					$data = $spreadsheet->getActiveSheet()->toArray();
					$count = 0;
					foreach ($data as $row) {
						if ($count > 0) {

							$Weld_On_No = $row[0];
							$Delivery_DateTime = $row[1];
							$Delivery_Date = $row[1];
							$MMTH_Part_No = $row[2];
							$Qty = $row[3];
							$SNP = $row[4];

							//exit($row[0]. ' ' . ' ' . $row[1] . ' ' . $row[3] .' ' . $row[4] );
							$sql = "SELECT Concat(Part_Name ,' ',Specification) as Part_Descri, Part_No
							from tbl_part_master
							where MMTH_Part_No = '$MMTH_Part_No' limit 1;";
							$re1 = sqlError($mysqli, __LINE__, $sql, 1);
							while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
								$Part_Descri = $row['Part_Descri'];
								$Part_No = $row['Part_No'];
							}

							$Part_ID = getPartID($mysqli, $Part_No);
							//exit($Part_ID);

							$sqlArray[] = array(
								'Weld_On_No' => stringConvert($Weld_On_No),
								'Delivery_Date' => stringConvert($Delivery_Date),
								'Delivery_DateTime' => stringConvert($Delivery_DateTime),
								'Part_ID' => 'uuid_to_bin("' . $Part_ID . '",true)',
								'Part_No' => stringConvert($Part_No),
								'MMTH_Part_No' => stringConvert($MMTH_Part_No),
								'Part_Descri' => stringConvert($Part_Descri),
								'Qty' => $Qty,
								'SNP' => $SNP,
								'Creation_DateTime' => 'now()',
								'Created_By_ID' => $cBy,
								'File_Name' => stringConvert($fileName)
							);
						} else {
							$count = 1;
						}
					}

					$total = 0;
					if (count($sqlArray) > 0) {
						$sqlName = prepareNameInsert($sqlArray[0]);
						$sqlChunk = array_chunk($sqlArray, 500);

						for ($i = 0, $len = count($sqlChunk); $i < $len; $i++) {
							$sqlValues = prepareValueInsert($sqlChunk[$i]);
							$sql = "INSERT IGNORE INTO tbl_weld_on_order $sqlName VALUES $sqlValues";
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
				}

				$re = select_group($mysqli);
				closeDBT($mysqli, 1, $re);
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

function select_group($mysqli)
{
	$sql = "SELECT 
	Weld_On_No,
	date_format(Delivery_DateTime, '%d/%m/%y %H:%i:%s') AS Delivery_DateTime,
	Qty,
	SNP,
	MMTH_Part_No,
	Part_Descri
	FROM tbl_weld_on_order
	order by Delivery_DateTime, Weld_On_No";
	$re1 = sqlError($mysqli, __LINE__, $sql, 1);
	$value = jsonRow($re1, false, 0);
	$data = group_by('Weld_On_No', $value); //group datatable tree
	$dateset = array();
	$c = 1;
	foreach ($data as $key1 => $value1) {
		$sub = selectColumnFromArray($value1, array(
			'MMTH_Part_No',
			'Part_Descri',
			'Qty',
			'SNP'
		)); //ที่จะให้อยู่ในตัว Child rows
		$c2 = 1;
		foreach ($sub as $key2 => $value2) {
			$sub[$key2]['Weld_On_No'] = $c2;
			$sub[$key2]['Is_Header'] = 'NO';
			$c2++;
		}

		$dateset[] =  array(
			"No" => $c, 'Is_Header' => 'YES', "Weld_On_No" => $key1,
			"Delivery_DateTime" => $value1[0]['Delivery_DateTime'],
			'Total_Item' => count($value1), "open" => 0, "data" => $sub
		);
		$c++;
	}
	return $dateset;
}

$mysqli->close();
exit();
