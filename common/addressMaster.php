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
        DISTINCT address_description AS value
    FROM
		tbl_address_master
    WHERE
		address_description LIKE '%$val%' OR address LIKE '%$val%'
	ORDER BY address_description
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
	toArrayStringOne($mysqli->query("SELECT DISTINCT address_description FROM tbl_address_master
	ORDER BY address_description;"), 1);
}

$mysqli->close();
exit();
