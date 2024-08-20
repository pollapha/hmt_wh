<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'OnhandPackage'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'OnhandPackage'}[0] == 0) {
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


function transpose($array)
{
	$transposedArray = [];
	foreach ($array as $row => $columns) {
		foreach ($columns as $row2 => $column2) {
			$transposedArray[$row2][$row] = $column2;
		}
	}
	return $transposedArray;
}


if ($type <= 10) //data
{
	if ($type == 1) {

		$mysqli->autocommit(FALSE);
		try {

			$sql = "WITH a AS (
			SELECT package_code package_no, package_type, delivery_status, 
			if(delivery_status = 'In' AND supplier_code IS NULL, 'TTV', supplier_code) supplier_code,
            package_status,
			t1.updated_at, t2.user_fName as updated_by
			FROM tbl_package_master t1
			LEFT JOIN tbl_user t2 ON t1.updated_user_id = t2.user_id
			LEFT JOIN tbl_supplier_master t3 ON t1.supplier_id = t3.supplier_id
			WHERE t1.status = 'Active')
			SELECT
			package_no, package_type, delivery_status, package_status,
			if(supplier_code = 'TTV',1,0) as ttv,
			if(supplier_code = 'HMTH',1,0) as hmth,
			if(supplier_code = 'NKAPM',1,0) as nkapm,
			updated_at, updated_by
			FROM a;";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);

			$mysqli->commit();
			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 2) {

		$mysqli->autocommit(FALSE);
		try {

			$steel_pipe = [];
			$package_steel = [];
			$package_wooden = [];
			$package_status = [];

			$sql = "WITH package_in AS (
			SELECT 
				transaction_type, if(isnull(steel_qty),0,sum(steel_qty)) ttv, 0 hmth, 0 nkapm
			FROM
				tbl_transaction t1
					LEFT JOIN tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
			WHERE t1.transaction_type = 'Package In'
				AND t2.status = 'Complete'
				AND package_no IS NOT NULL)
			,package_in_2 AS (
			SELECT 
				transaction_type, if(isnull(steel_qty),0,sum(steel_qty)) ttv, 0 hmth, 0 nkapm
			FROM
				tbl_transaction t1
					LEFT JOIN tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
			WHERE t1.transaction_type = 'Package In'
				AND t2.status = 'Complete'
				AND package_no IS NOT NULL
				AND t2.supplier_id IS NOT NULL)
			,package_onhand_pre AS (
			SELECT 
			t2.package_no, t2.steel_qty, part_id, status
			FROM
			tbl_order_header t1
				INNER JOIN tbl_order t2 ON t1.order_header_id = t2.order_header_id
			WHERE t1.order_status = 'Picking'
			AND t2.status = 'Complete'
			GROUP BY t2.package_no
			order by t2.package_no)
			, package_onhand as (
			SELECT 
				t2.package_no,
				if(isnull(t2.steel_qty),0,sum(t2.steel_qty)) ttv, 0 hmth, 0 nkapm
			FROM
				package_onhand_pre t2
					INNER JOIN tbl_part_master t4 ON t2.part_id = t4.part_id
					INNER JOIN tbl_supplier_master t5 ON t4.supplier_id = t5.supplier_id)
			,package_out_pre AS (
			SELECT 
				t1.transaction_id, package_no, steel_qty, t2.part_id, t2.status
			FROM
				tbl_transaction t1
					LEFT JOIN tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
			WHERE t1.transaction_type = 'Out'
				AND t2.status = 'Complete'
				AND package_no IS NOT NULL
			GROUP BY package_no ORDER BY t1.transaction_id, package_no)
			,package_out AS (
			SELECT
				0 ttv,
				if(supplier_code = 'HMTH',if(isnull(steel_qty),0,sum(steel_qty)),0) as hmth,
				if(supplier_code = 'NKAPM',if(isnull(steel_qty),0,sum(steel_qty)),0) as nkapm
			FROM
				package_out_pre t2
					INNER JOIN tbl_part_master t3 ON t2.part_id = t3.part_id
					INNER JOIN tbl_supplier_master t4 ON t3.supplier_id = t4.supplier_id)
			,sum_total AS (
			SELECT 'Qty(pcs.)' as steel_pipe,
			(package_in.ttv-package_onhand.ttv)-package_out.hmth as ttv,
			package_out.hmth-package_in_2.ttv as hmth,
			0 nkapm
			FROM package_in, package_onhand, package_out, package_in_2)
			SELECT *, ttv+hmth+nkapm as total FROM sum_total;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$steel_pipe = jsonRow($re1, true, 0);

			$sql = "WITH a AS (
				SELECT package_code package_no, package_type, delivery_status, 
				if(delivery_status = 'In' AND supplier_code IS NULL, 'TTV', supplier_code) supplier_code
				FROM tbl_package_master t1
				LEFT JOIN tbl_supplier_master t3 ON t1.supplier_id = t3.supplier_id
				WHERE t1.status = 'Active'
					AND t1.package_type = 'Steel'),
			b AS (
				SELECT
				package_no, package_type, delivery_status,
				if(supplier_code = 'TTV',1,0) as ttv,
				if(supplier_code = 'HMTH',1,0) as hmth,
				if(supplier_code = 'NKAPM',1,0) as nkapm
				FROM a),
			sum_total AS (
			SELECT 'Qty(pcs.)' as steel_pipe, sum(ttv) ttv, sum(hmth) hmth, sum(nkapm) nkapm FROM b )
			SELECT *, ttv+hmth+nkapm as total FROM sum_total;";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$package_steel = jsonRow($re1, true, 0);

			$sql = "WITH a AS (
				SELECT package_code package_no, package_type, delivery_status, 
				if(delivery_status = 'In' AND supplier_code IS NULL, 'TTV', supplier_code) supplier_code
				FROM tbl_package_master t1
				LEFT JOIN tbl_supplier_master t3 ON t1.supplier_id = t3.supplier_id
				WHERE t1.status = 'Active'
					AND t1.package_type = 'Wooden'),
			b AS (
				SELECT
				package_no, package_type, delivery_status,
				if(supplier_code = 'TTV',1,0) as ttv,
				if(supplier_code = 'HMTH',1,0) as hmth,
				if(supplier_code = 'NKAPM',1,0) as nkapm
				FROM a),
			sum_total AS (
			SELECT 'Qty(pcs.)' as steel_pipe, sum(ttv) ttv, sum(hmth) hmth, sum(nkapm) nkapm FROM b )
			SELECT *, ttv+hmth+nkapm as total FROM sum_total;";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$package_wooden = jsonRow($re1, true, 0);


			$sql = "WITH a AS (
				SELECT package_code package_no, package_type, delivery_status, package_status
				FROM tbl_package_master t1
				WHERE t1.status = 'Active' AND delivery_status = 'In' AND package_type = 'Steel'),
			b AS (
				SELECT
				package_no, package_type, delivery_status, package_status,
				if(package_status = 'FG',1,0) as FG,
				if(package_status = 'Empty',1,0) as `Empty`
				FROM a)	
			,sum_total AS (
				SELECT 'In (TTV)' as steel_pipe, sum(FG) FG, sum(`Empty`) `Empty` FROM b)
				SELECT *, FG+`Empty` as total FROM sum_total;";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$package_status = jsonRow($re1, true, 0);

			$sql = "WITH a AS (
				SELECT package_code package_no, package_type, delivery_status, package_status
				FROM tbl_package_master t1
				WHERE t1.status = 'Active' AND delivery_status = 'In'),
			b AS (
				SELECT
				package_no, package_type, delivery_status, package_status,
				if(package_status = 'FG',1,0) as FG,
				if(package_status = 'Empty',1,0) as `Empty`
				FROM a)
			SELECT package_type, sum(FG) FG, sum(`Empty`) `Empty` FROM b GROUP BY package_type;";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$package_status = jsonRow($re1, true, 0);

			$return_data = [
				'steel_pipe' => $steel_pipe, 'package_steel' => $package_steel, 'package_wooden' => $package_wooden, 
				'package_status' => $package_status
			];

			$mysqli->commit();
			closeDBT($mysqli, 1, $return_data);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 3) {

		$sql = "WITH a AS (
			SELECT package_code package_no, package_type, delivery_status, 
			if(delivery_status = 'In' AND supplier_code IS NULL, 'TTV', supplier_code) supplier_code,
			t1.updated_at, t2.user_fName as updated_by
			FROM tbl_package_master t1
			LEFT JOIN tbl_user t2 ON t1.updated_user_id = t2.user_id
			LEFT JOIN tbl_supplier_master t3 ON t1.supplier_id = t3.supplier_id
			WHERE t1.status = 'Active')
			SELECT
			row_number() over(ORDER BY package_no ASC) row_num,
			package_no, package_type, delivery_status,
			if(supplier_code = 'TTV',1,0) as ttv,
			if(supplier_code = 'HMTH',1,0) as hmth,
			if(supplier_code = 'NKAPM',1,0) as nkapm,
			updated_at, updated_by
			FROM a;";
		// exit($sql);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		$dataByPackageArray = array();
		while ($row = $re1->fetch_array(MYSQLI_NUM)) {
			$dataByPackageArray[] = $row;
		}


		$sql = "WITH package_in AS (
			SELECT 
				transaction_type, if(isnull(steel_qty),0,sum(steel_qty)) ttv, 0 hmth, 0 nkapm
			FROM
				tbl_transaction t1
					LEFT JOIN tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
			WHERE t1.transaction_type = 'Package In'
				AND t2.status = 'Complete'
				AND package_no IS NOT NULL),
			package_onhand AS (
			SELECT 
				t1.transaction_type, if(isnull(t3.steel_qty),0,sum(t3.steel_qty)) ttv, 0 hmth, 0 nkapm
			FROM
				tbl_transaction t1
					LEFT JOIN tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
					LEFT JOIN tbl_inventory_line t3 ON t2.transaction_line_id = t3.transaction_line_id
					INNER JOIN tbl_part_master t4 ON t2.part_id = t4.part_id
					INNER JOIN tbl_supplier_master t5 ON t4.supplier_id = t5.supplier_id
			WHERE t1.transaction_type = 'Packing'
				AND t2.status = 'Complete'
				AND t3.delivery_qty = 0),
			package_out AS (
			SELECT 
				transaction_type,
				0 ttv,
				if(supplier_code = 'HMTH',if(isnull(steel_qty),0,sum(steel_qty)),0) as hmth,
				if(supplier_code = 'NKAPM',if(isnull(steel_qty),0,sum(steel_qty)),0) as nkapm
			FROM
				tbl_transaction t1
					LEFT JOIN tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
					INNER JOIN tbl_part_master t3 ON t2.part_id = t3.part_id
					INNER JOIN tbl_supplier_master t4 ON t3.supplier_id = t4.supplier_id
			WHERE t1.transaction_type = 'Out'
				AND t2.status = 'Complete'
				AND package_no IS NOT NULL)
			,sum_total AS (
			SELECT 'Qty(pcs.)' as steel_pipe,
			(package_onhand.ttv+package_in.ttv) as ttv,
			if(package_out.hmth=0,0,package_out.hmth-package_in.ttv) as hmth,
			0 nkapm
			FROM package_in, package_onhand, package_out)
			SELECT * FROM sum_total;";
		// exit($sql);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		$dataSteelArray = array();
		while ($row = $re1->fetch_array(MYSQLI_NUM)) {
			$dataSteelArray[] = $row;
		}


		$sql = "WITH a AS (
			SELECT package_code package_no, package_type, delivery_status, 
			if(delivery_status = 'In' AND supplier_code IS NULL, 'TTV', supplier_code) supplier_code
			FROM tbl_package_master t1
			LEFT JOIN tbl_supplier_master t3 ON t1.supplier_id = t3.supplier_id
			WHERE t1.status = 'Active'
				AND t1.package_type = 'Steel'),
		b AS (
			SELECT
			package_no, package_type, delivery_status,
			if(supplier_code = 'TTV',1,0) as ttv,
			if(supplier_code = 'HMTH',1,0) as hmth,
			if(supplier_code = 'NKAPM',1,0) as nkapm
			FROM a)
		SELECT 'Qty(pcs.)' as steel_pipe, sum(ttv) ttv, sum(hmth) hmth, sum(nkapm) nkapm FROM b;";
		// exit($sql);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		$dataPackageSteelArray = array();
		while ($row = $re1->fetch_array(MYSQLI_NUM)) {
			$dataPackageSteelArray[] = $row;
		}

		$sql = "WITH a AS (
			SELECT package_code package_no, package_type, delivery_status, 
			if(delivery_status = 'In' AND supplier_code IS NULL, 'TTV', supplier_code) supplier_code
			FROM tbl_package_master t1
			LEFT JOIN tbl_supplier_master t3 ON t1.supplier_id = t3.supplier_id
			WHERE t1.status = 'Active'
				AND t1.package_type = 'Wooden'),
		b AS (
			SELECT
			package_no, package_type, delivery_status,
			if(supplier_code = 'TTV',1,0) as ttv,
			if(supplier_code = 'HMTH',1,0) as hmth,
			if(supplier_code = 'NKAPM',1,0) as nkapm
			FROM a)
		SELECT 'Qty(pcs.)' as steel_pipe, sum(ttv) ttv, sum(hmth) hmth, sum(nkapm) nkapm FROM b;";
		// exit($sql);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		$dataPackageWoodenArray = array();
		while ($row = $re1->fetch_array(MYSQLI_NUM)) {
			$dataPackageWoodenArray[] = $row;
		}

		$sql = "WITH a AS (
			SELECT package_code package_no, package_type, delivery_status, package_status
			FROM tbl_package_master t1
			WHERE t1.status = 'Active' AND delivery_status = 'In'),
		b AS (
			SELECT
			package_no, package_type, delivery_status, package_status,
			if(package_status = 'FG',1,0) as FG,
			if(package_status = 'Empty',1,0) as `Empty`
			FROM a)	
		,sum_total AS (
			SELECT 'In (TTV)' as steel_pipe, sum(FG) FG, sum(`Empty`) `Empty` FROM b)
			SELECT *, FG+`Empty` as total FROM sum_total;";
		// exit($sql);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		$dataPackageWoodenArray = array();
		while ($row = $re1->fetch_array(MYSQLI_NUM)) {
			$dataStatusPackageArray[] = $row;
		}

		include('excel/excel_onhand_package.php');
		closeDBT($mysqli, 1, $filename);
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'OnhandPackage'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {
	} else if ($type == 12) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'OnhandPackage'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'OnhandPackage'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'OnhandPackage'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else closeDBT($mysqli, 2, 'TYPE ERROR');

$mysqli->close();
exit();
