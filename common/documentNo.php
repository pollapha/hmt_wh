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
	toArrayStringOne($mysqli->query("SELECT DISTINCT document_no FROM tbl_transaction t1 
	INNER JOIN tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
	WHERE transaction_type = 'Temp-In' AND t2.status != 'Cancel';"), 1);
} else if ($_REQUEST['type'] == 2) {
	toArrayStringOne($mysqli->query("SELECT DISTINCT document_no FROM tbl_transaction t1 
	INNER JOIN tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
	WHERE transaction_type = 'Temp-Packing' AND t2.status != 'Cancel';"), 1);
} else if ($_REQUEST['type'] == 3) {
	toArrayStringOne($mysqli->query("SELECT DISTINCT document_no FROM tbl_transaction t1 
	INNER JOIN tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
	WHERE transaction_type = 'Temp-Picking' AND t2.status != 'Cancel';"), 1);
}else if ($_REQUEST['type'] == 4) {
	toArrayStringOne($mysqli->query("SELECT DISTINCT document_no FROM tbl_transaction t1 
	INNER JOIN tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
	WHERE transaction_type = 'Temp-Move' AND t2.status != 'Cancel';"), 1);
}else if ($_REQUEST['type'] == 5) {
	toArrayStringOne($mysqli->query("SELECT DISTINCT document_no FROM tbl_transaction t1 
	INNER JOIN tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
	WHERE transaction_type = 'Picking' AND t2.status != 'Cancel';"), 1);
}else if ($_REQUEST['type'] == 6) {
	toArrayStringOne($mysqli->query("SELECT DISTINCT document_no FROM tbl_transaction t1 
	INNER JOIN tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
	WHERE transaction_type = 'Temp-Package In' AND t2.status != 'Cancel';"), 1);
}

$mysqli->close();
exit();
