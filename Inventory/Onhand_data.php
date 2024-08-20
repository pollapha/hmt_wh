<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'Onhand'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'Onhand'}[0] == 0) {
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

		$dataParams = array(
			'obj',
			'obj=>group_by:s:0:1',
			'obj=>supplier_code:s:0:0',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$where1 = '';
			$where2 = '';
			if ($supplier_code != '') {
				$where1 = "AND supplier_code = '$supplier_code'";
				$where2 = "WHERE supplier_code = '$supplier_code'";
			}

			if ($group_by == 'By item') {
				$sql = "SELECT 
				supplier_code, document_date receive_date, 
				t1.invoice_no, part_no, part_name, 
				t1.pallet_no, t1.certificate_no, t1.case_tag_no, 
				SUM(t2.qty) total_qty, 
				ROUND(SUM(t2.net_per_pcs)) total_net_per_pallet,
				tod.fg_tag_no,
				if(tod.fg_tag_no IS NOT NULL, 'Yes', 'No') repack_process, 
				location_code, location_area
				FROM hmt_wh.tbl_inventory t1
				left join tbl_inventory_detail t2 ON t1.inventory_id = t2.inventory_id
				left join tbl_order tod ON t2.order_id = tod.order_id
				left join tbl_part_master t3 ON t1.part_id = t3.part_id
				left join tbl_location_master t4 ON t2.location_id = t4.location_id
				left join tbl_supplier_master t5 ON t3.supplier_id = t5.supplier_id
				left join tbl_transaction_line t6 ON t1.transaction_line_id = t6.transaction_line_id
				left join tbl_transaction t7 ON t6.transaction_id = t7.transaction_id
				WHERE t2.package_no IS NULL $where1
				group by t1.case_tag_no, tod.fg_tag_no
				order by receive_date, t1.case_tag_no, tod.fg_tag_no;";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			} else if ($group_by == 'By part') {
				$sql = "WITH a AS (
				SELECT 
				row_number() over (partition by part_no order by part_no) row_num,
				supplier_code, part_no, part_name, t1.qty, t1.net_per_pcs, t1.package_no
				FROM hmt_wh.tbl_inventory_detail t1
				left join tbl_part_master t3 ON t1.part_id = t3.part_id
				left join tbl_location_master t4 ON t1.location_id = t4.location_id
				left join tbl_supplier_master t5 ON t3.supplier_id = t5.supplier_id
				$where2
				order by supplier_code, part_no)
				SELECT *, sum(qty) total_qty, sum(net_per_pcs) total_net_per_pallet FROM a WHERE package_no IS NULL
				GROUP BY part_no";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			}



			$mysqli->commit();
			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 2) {

		$dataParams = array(
			'obj',
			'obj=>group_by:s:0:1',
			'obj=>supplier_code:s:0:0',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$where = '';
			if ($supplier_code != '') {
				$where = "WHERE supplier_code = '$supplier_code'";
			}

			$sql = "WITH a AS (
			SELECT
			supplier_code, document_date receive_date, 
			t1.invoice_no, part_no, part_name, 
			t1.pallet_no, t1.certificate_no, t1.case_tag_no, 
			SUM(t2.qty) total_qty, 
			ROUND(SUM(t2.net_per_pcs)) total_net_per_pallet,
			tod.fg_tag_no,
			if(tod.fg_tag_no IS NOT NULL, 'Yes', 'No') repack_process, 
			location_code, location_area
			FROM hmt_wh.tbl_inventory t1
			left join tbl_inventory_detail t2 ON t1.inventory_id = t2.inventory_id
			left join tbl_order tod ON t2.order_id = tod.order_id
			left join tbl_part_master t3 ON t1.part_id = t3.part_id
			left join tbl_location_master t4 ON t2.location_id = t4.location_id
			left join tbl_supplier_master t5 ON t3.supplier_id = t5.supplier_id
			left join tbl_transaction_line t6 ON t1.transaction_line_id = t6.transaction_line_id
			left join tbl_transaction t7 ON t6.transaction_id = t7.transaction_id
			WHERE t2.package_no IS NULL
			group by t1.case_tag_no, tod.fg_tag_no
			order by receive_date, t1.case_tag_no, tod.fg_tag_no)
			SELECT row_number() over (order by receive_date, case_tag_no, fg_tag_no) row_num, 
			supplier_code, receive_date, invoice_no, part_no, part_name, pallet_no, certificate_no, case_tag_no, 
			total_net_per_pallet, total_qty, fg_tag_no, repack_process, 
			location_code, location_area
			FROM a;";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$dataByItemArray = array();
			while ($row = $re1->fetch_array(MYSQLI_NUM)) {
				$dataByItemArray[] = $row;
			}

			$sql = "WITH a AS (
			SELECT 
			supplier_code, part_no, part_name, t1.qty, t1.net_per_pcs, t1.package_no
			FROM hmt_wh.tbl_inventory_detail t1
			left join tbl_part_master t3 ON t1.part_id = t3.part_id
			left join tbl_location_master t4 ON t1.location_id = t4.location_id
			left join tbl_supplier_master t5 ON t3.supplier_id = t5.supplier_id
			$where
			order by supplier_code, part_no)
			SELECT row_number() over (order by part_no) row_num, 
            supplier_code, part_no, part_name, sum(net_per_pcs) total_net_per_pallet, sum(qty) total_qty FROM a WHERE package_no IS NULL
			GROUP BY part_no";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$dataByPartArray = array();
			while ($row = $re1->fetch_array(MYSQLI_NUM)) {
				$dataByPartArray[] = $row;
			}

			include('excel/excel_onhand.php');

			$mysqli->commit();

			closeDBT($mysqli, 1, $filename);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'Onhand'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {
	} else if ($type == 12) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'Onhand'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'Onhand'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'Onhand'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else closeDBT($mysqli, 2, 'TYPE ERROR');

$mysqli->close();
exit();
