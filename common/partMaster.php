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
include('../common/common.php');

if ($_REQUEST['type'] == 1) {
	$val = checkTXT($mysqli, $_REQUEST['filter']['value']);
	if (strlen(trim($val)) == 0) {
		echo "[]";
		exit();
	}
	$row = [];
	$sql = "SELECT
        DISTINCT part_no AS value
    FROM
		tbl_part_master t1
			LEFT JOIN tbl_supplier_master t2 ON t1.supplier_id = t2.supplier_id
    WHERE
	(part_no LIKE '%$val%')
			AND t1.status = 'Active'
    LIMIT 5;";

	if ($re1 = $mysqli->query($sql)) {
		$row = array();
		while ($result = $re1->fetch_array(MYSQLI_ASSOC)) {
			$row[] = $result['value'];
			//array_push($row, array("id" => $result['part_id'], "value" => $result['value']));
		}
		echo json_encode($row);
	} else {
		echo "[]";
	}
} else if ($_REQUEST['type'] == 2) {
	toArrayStringOne($mysqli->query("SELECT DISTINCT part_no FROM tbl_part_master t1
			LEFT JOIN tbl_supplier_master t2 ON t1.supplier_id = t2.supplier_id WHERE t1.status = 'Active';"), 1);

	// $row = [];
	// $sql = "SELECT
    //     DISTINCT bin_to_uuid(part_id,true) as part_id, part_no
    // FROM
	// 	tbl_part_master
    // WHERE
	// 	status = 'Active';";
	// //exit($sql);

	// if ($re1 = $mysqli->query($sql)) {

	// 	while ($result = $re1->fetch_array(MYSQLI_ASSOC)) {
	// 		array_push($row, array("part_id" => $result['part_id'], "part_no" => $result['part_no']));
	// 	}
	// 	echo json_encode($row);
	// } else {
	// 	echo "[]";
	// }

	// toArrayStringOne($mysqli->query("SELECT DISTINCT 
	// 	concat(t1.part_no , ' | ', t1.part_name) as part_no 
	// FROM 
	// 	tbl_part_master t1
	// WHERE 
	// 	t1.status = 'Active';"), 1);
}else if ($_REQUEST['type'] == 3) {
	$val = checkTXT($mysqli, $_REQUEST['filter']['value']);
	if (strlen(trim($val)) == 0) {
		echo "[]";
		exit();
	}
	$row = [];
	$sql = "SELECT
        DISTINCT concat(part_no, ' | ', part_name) AS value
    FROM
		tbl_part_master t1
			LEFT JOIN tbl_supplier_master t2 ON t1.supplier_id = t2.supplier_id
    WHERE
	(part_no LIKE '%$val%')
			AND t1.status = 'Active'
			AND t2.supplier_id IS NULL
    LIMIT 5;";

	if ($re1 = $mysqli->query($sql)) {
		$row = array();
		while ($result = $re1->fetch_array(MYSQLI_ASSOC)) {
			$row[] = $result['value'];
			//array_push($row, array("id" => $result['part_id'], "value" => $result['value']));
		}
		echo json_encode($row);
	} else {
		echo "[]";
	}
} else if ($_REQUEST['type'] == 4) {
	$supplier_code = $_REQUEST['supplier_code'];
	$supplier_id = getSupplierID($mysqli,$supplier_code);

	toArrayStringOne($mysqli->query("SELECT DISTINCT part_no value FROM tbl_part_master t1
			LEFT JOIN tbl_supplier_master t2 ON t1.supplier_id = t2.supplier_id WHERE t1.status = 'Active' 
			AND t2.supplier_id = uuid_to_bin('$supplier_id',true) order by part_no;"), 1);

}
$mysqli->close();
exit();
