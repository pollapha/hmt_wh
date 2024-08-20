<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'ConfirmShip'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'ConfirmShip'}[0] == 0) {
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
	} else if ($type == 2) {


		$dataParams = array(
			'obj',
			'obj=>document_no:s:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				ROW_NUMBER() OVER (partition by case_tag_no ORDER BY work_order_no, fg_tag_no, transaction_line_id) as row_num,
				if(fg_tag_no IS NOT NULL, fg_tag_no, case_tag_no) tag_no,
				t2.work_order_no, fg_tag_no, package_no, part_no, part_name, SUM(t2.qty) as qty_per_pallet, SUM(net_per_pallet) net_per_pallet,
				t2.remark, transaction_type
			FROM
				tbl_transaction t1
					inner join tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
					inner join tbl_part_master t3 on t2.part_id = t3.part_id
			WHERE
				t1.document_no = '$document_no'
					AND t1.transaction_type = 'Out'
					AND t2.status = 'Complete'
			GROUP BY work_order_no, fg_tag_no
			ORDER BY work_order_no, fg_tag_no;";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);

			$mysqli->commit();
			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 3) {

		$dataParams = array(
			'obj',
			'obj=>document_no:s:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$body = [];
			$sql = "SELECT document_no
			FROM
				tbl_transaction t1
				INNER JOIN
				tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
			WHERE
				t1.document_no = '$document_no'
					AND t2.status = 'Complete'
			group by t1.document_no;";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล Document No.');
			}

			$sql = "SELECT document_no
			FROM
				tbl_transaction t1
				INNER JOIN
				tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
			WHERE
				t1.document_no = '$document_no'
					AND t1.transaction_type = 'Out'
					AND t2.status = 'Complete'
			group by t1.document_no;";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('Document No. นี้<br>คอนเฟิร์มแล้ว');
			}

			$sql = "SELECT document_no
			FROM
				tbl_transaction t1
			WHERE
				t1.document_no = '$document_no'
					AND t1.transaction_type = 'Temp-Picking';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('Document No. นี้<br>ยังออก GTN ไม่เสร็จสิ้น');
			}

			$sql = "SELECT 
				ROW_NUMBER() OVER (partition by case_tag_no ORDER BY work_order_no, fg_tag_no, transaction_line_id) as row_num,
				if(fg_tag_no IS NOT NULL, fg_tag_no, case_tag_no) tag_no,
				t2.work_order_no, fg_tag_no, package_no, part_no, part_name, SUM(t2.qty) as qty_per_pallet, SUM(net_per_pallet) net_per_pallet,
				t2.remark, transaction_type
			FROM
				tbl_transaction t1
					inner join tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
					inner join tbl_part_master t3 on t2.part_id = t3.part_id
			WHERE
				t1.document_no = '$document_no'
					AND t1.transaction_type = 'Picking'
					AND t2.status = 'Complete'
			GROUP BY work_order_no, fg_tag_no
			ORDER BY work_order_no, fg_tag_no;";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);

			$mysqli->commit();
			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'ConfirmShip'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {
	} else if ($type == 12) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'ConfirmShip'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'ConfirmShip'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'ConfirmShip'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {

		$dataParams = array(
			'obj',
			'obj=>document_no:s:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT document_no
			FROM
				tbl_transaction t1
			WHERE
				t1.document_no = '$document_no'
					AND t1.transaction_type = 'Picking';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}

			$sql = "SELECT bin_to_uuid(transaction_id,true) transaction_id, bin_to_uuid(order_header_id,true) order_header_id
			FROM tbl_transaction WHERE document_no = '$document_no';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($re1->num_rows === 0) {
				throw new Exception('ไม่พบข้อมูล DOS No.');
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$transaction_id = $row['transaction_id'];
				$order_header_id = $row['order_header_id'];
			}

			$sql = "UPDATE tbl_transaction 
				SET transaction_type = 'Out', 
				updated_user_id = $cBy,
				updated_at = now()
				WHERE transaction_id = uuid_to_bin('$transaction_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . $sql);
			}

			$sql = "UPDATE tbl_order_header 
				SET order_status = 'Delivered',
					updated_user_id = $cBy,
					updated_at = now()
				WHERE order_header_id = uuid_to_bin('$order_header_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}


			$sql = "SELECT bin_to_uuid(supplier_id,true) supplier_id
			FROM tbl_order_header WHERE order_header_id = uuid_to_bin('$order_header_id',true);";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($re1->num_rows === 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$supplier_id = $row['supplier_id'];
			}

			$sql = "SELECT DISTINCT package_no
			FROM tbl_transaction_line
			WHERE transaction_id = uuid_to_bin('$transaction_id',true) AND status != 'Cancel' AND package_no IS NOT NULL;";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$package_no = $row['package_no'];

					$sql = "UPDATE tbl_package_master
					SET delivery_status = 'Out',
					supplier_id = uuid_to_bin('$supplier_id',true),
					updated_at = NOW(),
					updated_user_id = $cBy
					WHERE package_code = '$package_no';";
					sqlError($mysqli, __LINE__, $sql, 1, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
					}
				}
			}

			$mysqli->commit();
			closeDBT($mysqli, 1, $document_no);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else closeDBT($mysqli, 2, 'TYPE ERROR');

$mysqli->close();
exit();
