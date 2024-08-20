<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
/*  if(!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName']) )
    {
        echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
        exit();
    }
 */
include('../php/connection.php');

if ($_REQUEST['type'] == 1) {
	$val = checkTXT($mysqli, $_REQUEST['filter']['value']);
	if (strlen(trim($val)) == 0) {
		echo "[]";
		exit();
	}
	$sql = "SELECT
        DISTINCT location_code AS value
    FROM
		tbl_location_master
    WHERE
		location_code LIKE '%$val%'
			AND location_code != 'N/A'
    LIMIT 5;";

	if ($re1 = $mysqli->query($sql)) {
		$row = array();
		while ($result = $re1->fetch_array(MYSQLI_ASSOC)) {
			$row[] = $result['value'];
		}
		echo json_encode($row);
	} else {
		echo "[]";
	}
} else if ($_REQUEST['type'] == 2) {
	toArrayStringOne($mysqli->query("SELECT DISTINCT location_code FROM tbl_location_master WHERE location_code != 'N/A';"), 1);
} else if ($_REQUEST['type'] == 3) {
	$val = checkTXT($mysqli, $_REQUEST['filter']['value']);
	if (strlen(trim($val)) == 0) {
		echo "[]";
		exit();
	}
	$sql = "SELECT
        DISTINCT location_code AS value
    FROM
		tbl_location_master
    WHERE
		location_code LIKE '%$val%' AND location_code != 'N/A'
    ORDER BY location_area, location_code LIMIT 5;";

	if ($re1 = $mysqli->query($sql)) {
		$row = array();
		while ($result = $re1->fetch_array(MYSQLI_ASSOC)) {
			$row[] = $result['value'];
		}
		echo json_encode($row);
	} else {
		echo "[]";
	}
} else if ($_REQUEST['type'] == 4) {
	toArrayStringOne($mysqli->query("SELECT DISTINCT location_code FROM tbl_location_master WHERE location_code != 'N/A' ORDER BY location_area, location_code ;"), 1);
}else if ($_REQUEST['type'] == 5) {
	$val = checkTXT($mysqli, $_REQUEST['filter']['value']);
	if (strlen(trim($val)) == 0) {
		echo "[]";
		exit();
	}
	$sql = "SELECT
        DISTINCT location_code AS value
    FROM
		tbl_location_master
    WHERE
		location_code LIKE '%$val%'
			AND location_code != 'N/A'
			AND location_area = 'truck-sim'
    LIMIT 5;";

	if ($re1 = $mysqli->query($sql)) {
		$row = array();
		while ($result = $re1->fetch_array(MYSQLI_ASSOC)) {
			$row[] = $result['value'];
		}
		echo json_encode($row);
	} else {
		echo "[]";
	}
}else if ($_REQUEST['type'] == 6) {
	toArrayStringOne($mysqli->query("SELECT DISTINCT location_code FROM tbl_location_master WHERE location_code != 'N/A' AND location_area = 'truck-sim' ORDER BY location_area, location_code ;"), 1);
}

$mysqli->close();
exit();
