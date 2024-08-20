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
    toArrayStringOne($mysqli->query("SELECT DISTINCT work_order_no FROM tbl_order_header t1 
	INNER JOIN tbl_order t2 ON t1.order_header_id = t2.order_header_id
	WHERE order_status = 'Packed' AND delivery_status = 'On process' AND t2.status != 'Cancel' 
    -- AND t2.repack = 'Pending'
    AND t1.repack = 'Yes'
    order by work_order_no;"), 1);
} else if ($_REQUEST['type'] == 2) {

    $val = checkTXT($mysqli, $_REQUEST['filter']['value']);
    if (strlen(trim($val)) == 0) {
        echo "[]";
        exit();
    }
    $row = [];
    $sql = "SELECT DISTINCT order_no as value
    FROM tbl_order_header t1 
	    INNER JOIN tbl_order t2 ON t1.order_header_id = t2.order_header_id
	WHERE order_status = 'Packed' AND delivery_status = 'On process'
        AND t2.status != 'Cancel'
        AND t2.repack = 'Pending'
        AND t1.repack = 'Yes'
	    AND (order_no LIKE '%$val%')
        order by work_order_no
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
}
$mysqli->close();
exit();
