<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'IssuedGTN'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'IssuedGTN'}[0] == 0) {
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
include('../common/common.php');
if ($type <= 10) //data
{
	if ($type == 1) {

		$sql = "SELECT 
			BIN_TO_UUID(t1.transaction_id, TRUE) AS transaction_id,
			document_no, t1.document_date, dos_no, t1.invoice_no, t1.delivery_date, truck_number, driver_name
		FROM
			tbl_transaction t1
				LEFT JOIN
			tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
				INNER JOIN
			tbl_order_header t3 ON t1.order_header_id = t3.order_header_id
				INNER JOIN
			tbl_truck_master t4 ON t1.truck_id = t4.truck_id
				INNER JOIN
			tbl_driver_master t5 ON t1.driver_id = t5.driver_id
		WHERE
			t1.editing_user_id = $cBy
				AND t1.transaction_type = 'Temp-Picking'
				AND t2.status = 'Pending'
		GROUP BY t1.transaction_id ORDER BY t1.editing_at DESC LIMIT 1;";
		// exit($sql);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		$header = jsonRow($re1, true, 0);

		$body = [];

		if (count($header) > 0) {
			$transaction_id = $header[0]['transaction_id'];

			$sql = "SELECT 
				ROW_NUMBER() OVER (partition by case_tag_no ORDER BY work_order_no, fg_tag_no, transaction_line_id) as row_num,
				BIN_TO_UUID(transaction_id, TRUE) AS transaction_id,
				BIN_TO_UUID(t1.transaction_line_id, TRUE) AS transaction_line_id,
				work_order_no, case_tag_no, package_no, fg_tag_no, part_no, part_name, SUM(t1.qty) as qty_per_pallet, SUM(net_per_pallet) net_per_pallet,
				t1.remark
			FROM
				tbl_transaction_line t1
					inner join tbl_part_master t3 on t1.part_id = t3.part_id
			WHERE
				transaction_id = uuid_to_bin('$transaction_id',true)
					AND t1.status = 'Pending'
			GROUP BY work_order_no, fg_tag_no
			ORDER BY work_order_no, package_no, fg_tag_no, transaction_line_id;";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);

			$body = jsonRow($re1, true, 0);
		}

		$returnData = ['header' => $header, 'body' => $body];

		closeDBT($mysqli, 1, $returnData);
	} else if ($type == 2) {

		$dataParams = array(
			'obj',
			'obj=>document_no:s:0:0',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				BIN_TO_UUID(t1.transaction_id, TRUE) AS transaction_id,
				document_no, t1.document_date, dos_no, t1.invoice_no, t1.delivery_date, truck_number, driver_name
			FROM
				tbl_transaction t1
					LEFT JOIN
				tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
					INNER JOIN
				tbl_order_header t3 ON t1.order_header_id = t3.order_header_id
					INNER JOIN
				tbl_truck_master t4 ON t1.truck_id = t4.truck_id
					INNER JOIN
				tbl_driver_master t5 ON t1.driver_id = t5.driver_id
			WHERE
				t1.document_no = '$document_no'
					AND t1.transaction_type = 'Temp-Picking'
					AND t2.status = 'Pending'
			GROUP BY t1.transaction_id ORDER BY t1.editing_at DESC LIMIT 1;";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$header = jsonRow($re1, true, 0);

			$body = [];

			if (count($header) > 0) {
				$transaction_id = $header[0]['transaction_id'];

				$sql = "SELECT 
					ROW_NUMBER() OVER (partition by case_tag_no ORDER BY work_order_no, fg_tag_no, transaction_line_id) as row_num,
					BIN_TO_UUID(transaction_id, TRUE) AS transaction_id,
					BIN_TO_UUID(t1.transaction_line_id, TRUE) AS transaction_line_id,
					work_order_no, case_tag_no, package_no, fg_tag_no, part_no, part_name, SUM(t1.qty) as qty_per_pallet, SUM(net_per_pallet) net_per_pallet,
					t1.remark
				FROM
					tbl_transaction_line t1
						inner join tbl_part_master t3 on t1.part_id = t3.part_id
				WHERE
					transaction_id = uuid_to_bin('$transaction_id',true)
						AND t1.status = 'Pending'
				GROUP BY work_order_no, fg_tag_no
				ORDER BY work_order_no, package_no, fg_tag_no, transaction_line_id;";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);

				$body = jsonRow($re1, true, 0);
			}

			$returnData = ['header' => $header, 'body' => $body];

			closeDBT($mysqli, 1, $returnData);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 3) {

		$dataParams = array(
			'obj',
			'obj=>dos_no:s:0:0',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT
				t1.dos_no,
				t1.order_no,
				t1.delivery_date,
				supplier_code,
				t1.repack
			FROM
				tbl_order_header t1 
					INNER JOIN
			tbl_order t2 ON t1.order_header_id = t2.order_header_id
					INNER JOIN
				tbl_supplier_master t4 ON t1.supplier_id = t4.supplier_id
			WHERE
				t1.dos_no = '$dos_no'
					AND t2.status = 'Complete'
			group by t1.order_no
			ORDER BY t2.work_order_no, t2.case_tag_no ASC;";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$header = jsonRow($re1, true, 0);

			$body = [];

			if (count($header) > 0) {
				$dos_no = $header[0]['dos_no'];

				$sql = "SELECT 
					part_no,
					part_name,
					t2.repack,
					t2.work_order_no,
					t2.fg_tag_no,
					SUM(t2.net_per_pcs) net_per_pallet,
					SUM(t2.qty) qty,
					t2.package_no,
					t6.location_code,
					supplier_code,
					if(t3.package_no IS NULL,'No','Yes') check_status
				FROM
					tbl_order_header t1
						INNER JOIN
					tbl_order t2 ON t1.order_header_id = t2.order_header_id
						LEFT JOIN
					tbl_inventory_detail t3 ON t2.part_tag_no = t3.part_tag_no
						LEFT JOIN
					tbl_part_master t5 ON t3.part_id = t5.part_id
						LEFT JOIN
					tbl_location_master t6 ON t3.location_id = t6.location_id
						LEFT JOIN
					tbl_supplier_master t8 ON t1.supplier_id = t8.supplier_id
				WHERE
					t1.dos_no = '$dos_no'
						AND t2.status = 'Complete'
				GROUP BY work_order_no, fg_tag_no
				ORDER BY work_order_no, fg_tag_no;";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);

				$body = jsonRow($re1, true, 0);
			}

			$returnData = ['header' => $header, 'body' => $body];

			closeDBT($mysqli, 1, $returnData);

			$mysqli->commit();
			closeDBT($mysqli, 1, $returnData);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	}
	else if ($type == 4) {

		$dataParams = array(
			'obj',
			'obj=>dos_no:s:0:0',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				t1.dos_no
			FROM
				tbl_order_header t1
			WHERE
				t1.dos_no = '$dos_no';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล Dos No. นี้');
			}

			$sql = "SELECT 
				t1.dos_no, t3.package_no
			FROM
				tbl_order_header t1 
					INNER JOIN
				tbl_order t2 ON t1.order_header_id = t2.order_header_id
					INNER JOIN 
				tbl_inventory_detail t3 ON t2.part_tag_no = t3.part_tag_no
			WHERE
				t1.dos_no = '$dos_no'
					AND t2.status = 'Complete'
					AND t3.package_no IS NULL
			group by t1.order_no
			ORDER BY t2.work_order_no, t2.case_tag_no ASC;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('Dos No. นี้มีการเพิ่มข้อมูลแล้ว ');
			}


			$sql = "SELECT
				t1.dos_no,
				t1.order_no,
				t1.delivery_date,
				supplier_code,
				t1.repack
			FROM
				tbl_order_header t1 
					INNER JOIN
			tbl_order t2 ON t1.order_header_id = t2.order_header_id
					INNER JOIN
				tbl_supplier_master t4 ON t1.supplier_id = t4.supplier_id
			WHERE
				t1.dos_no = '$dos_no'
					AND t2.status = 'Complete'
			group by t1.order_no
			ORDER BY t2.work_order_no, t2.case_tag_no ASC;";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$header = jsonRow($re1, true, 0);

			$body = [];

			if (count($header) > 0) {
				$dos_no = $header[0]['dos_no'];

				$sql = "SELECT 
					part_no,
					part_name,
					t2.repack,
					t2.work_order_no,
					t2.fg_tag_no,
					SUM(t2.net_per_pcs) net_per_pallet,
					SUM(t2.qty) qty,
					t2.package_no,
					t6.location_code,
					supplier_code,
					if(t3.package_no IS NULL,'No','Yes') check_status
				FROM
					tbl_order_header t1
						INNER JOIN
					tbl_order t2 ON t1.order_header_id = t2.order_header_id
						LEFT JOIN
					tbl_inventory_detail t3 ON t2.part_tag_no = t3.part_tag_no
						LEFT JOIN
					tbl_part_master t5 ON t3.part_id = t5.part_id
						LEFT JOIN
					tbl_location_master t6 ON t3.location_id = t6.location_id
						LEFT JOIN
					tbl_supplier_master t8 ON t1.supplier_id = t8.supplier_id
				WHERE
					t1.dos_no = '$dos_no'
						AND t2.status = 'Complete'
				GROUP BY work_order_no, fg_tag_no
				ORDER BY work_order_no, fg_tag_no;";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);

				$body = jsonRow($re1, true, 0);
			}

			$returnData = ['header' => $header, 'body' => $body];

			closeDBT($mysqli, 1, $returnData);

			$mysqli->commit();
			closeDBT($mysqli, 1, $returnData);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'IssuedGTN'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {


		$dataParams = array(
			'obj',
			'obj=>document_no:s:0:0',
			'obj=>document_date:s:0:1',
			'obj=>dos_no:s:0:1',
			'obj=>delivery_date:s:0:1',
			'obj=>truck_number:s:0:1',
			'obj=>driver_name:s:0:1',
			'obj=>tag_no:s:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			/* Transaction */

			$sql = "SELECT bin_to_uuid(order_header_id,true) order_header_id FROM tbl_order_header WHERE dos_no = '$dos_no';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($re1->num_rows === 0) {
				throw new Exception('ไม่พบข้อมูล DOS No.');
			}
			$order_header_id = $re1->fetch_assoc()["order_header_id"];

			$truck_id = getTruckID($mysqli, $truck_number);
			$driver_id = getDriverID($mysqli, $driver_name);

			$sql = "SELECT bin_to_uuid(transaction_id,true) transaction_id, document_no FROM tbl_transaction WHERE document_no = '$document_no'; ";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($re1->num_rows === 0) {
				$sql = "SELECT func_GenRuningNumber('temp',0) as document_no ;";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);
				if ($re1->num_rows === 0) {
					throw new Exception('ไม่พบข้อมูล Document_no TEMP');
				}
				$document_no = $re1->fetch_assoc()["document_no"];

				$sql = "INSERT INTO tbl_transaction 
				(document_no, document_date, delivery_date, transaction_type,
				truck_id, driver_id, order_header_id, 
				created_at, created_user_id) 
				VALUES
				('$document_no','$document_date', '$delivery_date', 'Temp-Picking',
				uuid_to_bin('$truck_id',true), uuid_to_bin('$driver_id',true), uuid_to_bin('$order_header_id',true), 
				now(), $cBy);";
				sqlError($mysqli, __LINE__, $sql, 1, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
				}
			}

			$sql = "SELECT bin_to_uuid(transaction_id,true) transaction_id, document_no FROM tbl_transaction WHERE document_no = '$document_no';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			$transaction_id = $re1->fetch_array(MYSQLI_ASSOC)['transaction_id'];

			$sql = "UPDATE tbl_transaction
			SET document_date = '$document_date',
				delivery_date = '$delivery_date',
				truck_id = uuid_to_bin('$truck_id',true),
				driver_id = uuid_to_bin('$driver_id',true),
				editing_at = NOW(), 
				editing_user_id = $cBy
			WHERE transaction_id = uuid_to_bin('$transaction_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			$prefix = substr($tag_no, 0, 1);

			$fg_tag_no  = $tag_no;
			$sql = "SELECT fg_tag_no FROM tbl_transaction_line WHERE transaction_id = uuid_to_bin('$transaction_id',true) AND fg_tag_no = '$fg_tag_no';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('Tag นี้มีการเพิ่มข้อมูลแล้ว ' . __LINE__);
			}


			$sql = "INSERT INTO tbl_transaction_line 
			( pallet_no, case_tag_no, fg_tag_no, work_order_no, part_id, qty, net_per_pallet, 
			package_no, package_type, steel_qty,
			measurement_cbm, certificate_no, invoice_no,
			transaction_id, from_location_id, 
			created_at, updated_at, created_user_id, updated_user_id
			)
			SELECT t1.pallet_no, t1.case_tag_no, t1.fg_tag_no, t1.work_order_no, t1.part_id, SUM(t1.qty), SUM(t1.net_per_pcs), 
			t1.package_no, t1.package_type, t1.steel_qty,
			t3.measurement_cbm, t3.certificate_no, t3.invoice_no, 
			uuid_to_bin('$transaction_id',true), t2.location_id,
			now(), now(), $cBy, $cBy
			FROM tbl_order t1 
			INNER JOIN tbl_inventory_detail t2 ON t1.part_tag_no = t2.part_tag_no
			LEFT JOIN tbl_inventory t3 ON t2.inventory_id = t3.inventory_id
			WHERE t1.fg_tag_no = '$fg_tag_no'
			GROUP BY t1.fg_tag_no, t1.case_tag_no;";
			// exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			$sql = "INSERT INTO tbl_transaction_detail 
			( part_tag_no, part_id, qty, net_per_pcs, 
			transaction_line_id, from_location_id, 
			created_at, updated_at, created_user_id, updated_user_id
			)
			SELECT t1.part_tag_no, t1.part_id, t1.qty, t1.net_per_pcs,
			t3.transaction_line_id, t2.location_id,
			now(), now(), $cBy, $cBy
			FROM tbl_order t1 
			INNER JOIN tbl_inventory_detail t2 ON t1.part_tag_no = t2.part_tag_no
			INNER JOIN tbl_transaction_line t3 ON t1.fg_tag_no = t3.fg_tag_no AND t1.case_tag_no = t3.case_tag_no
			WHERE t1.fg_tag_no = '$fg_tag_no' AND t3.transaction_id = uuid_to_bin('$transaction_id',true)
			GROUP BY t1.fg_tag_no, t1.part_tag_no;";
			// exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			/* Inventory */


			$sql = "UPDATE tbl_inventory_detail t1,
			(SELECT t1.part_tag_no, t2.to_location_id, if(t1.package_no IS NULL,'',t1.package_no) package_no
			FROM tbl_order t1 INNER JOIN tbl_transaction_detail t2 ON t1.part_tag_no = t2.part_tag_no
			INNER JOIN tbl_transaction_line t3 ON t2.transaction_line_id = t3.transaction_line_id
			WHERE t3.transaction_id = uuid_to_bin('$transaction_id',true) AND t1.fg_tag_no = '$fg_tag_no') t2
			SET t1.package_no = t2.package_no,
				t1.updated_at = NOW(), 
				t1.updated_user_id = $cBy
			WHERE t1.part_tag_no = t2.part_tag_no;";
			// exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			$sql = "UPDATE tbl_order_header 
			SET order_status = 'Picking',
				delivery_status = 'On process',
				updated_user_id = $cBy,
				updated_at = now()
			WHERE order_header_id = uuid_to_bin('$order_header_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}

			$mysqli->commit();
			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 12) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'IssuedGTN'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {

		$dataParams = array(
			'obj',
			'obj=>document_no:s:0:1',
			'obj=>document_date:s:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT bin_to_uuid(transaction_id,true) transaction_id, bin_to_uuid(order_header_id,true) order_header_id
					FROM tbl_transaction 
					WHERE document_no = '$document_no'  
					AND transaction_type = 'Out';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('document_no : ' . $document_no . '<br>Confirm ship แล้ว');
			}

			$sql = "SELECT bin_to_uuid(transaction_id,true) transaction_id, bin_to_uuid(order_header_id,true) order_header_id
					FROM tbl_transaction 
					WHERE document_no = '$document_no'  
					AND transaction_type = 'Picking'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล document_no : ' . $document_no);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$transaction_id = $row['transaction_id'];
				$order_header_id = $row['order_header_id'];
			}

			$sql = "UPDATE tbl_order_header 
				SET order_status = 'Picking',
				delivery_status = 'Pending',
				updated_user_id = $cBy,
				updated_at = now()
				WHERE order_header_id = uuid_to_bin('$order_header_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}


			$sql = "UPDATE tbl_transaction 
			SET 
			updated_at = NOW(), 
			updated_user_id = $cBy
			WHERE transaction_id = uuid_to_bin('$transaction_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . $sql);
			}


			$sql = "UPDATE tbl_transaction 
				SET transaction_type = 'Temp-Picking',
				editing_at = NOW(),
				editing_user_id = $cBy
				WHERE transaction_id = uuid_to_bin('$transaction_id',true) 
				-- AND transaction_type = 'Picking'
				;";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . $sql);
			}

			$sql = "UPDATE tbl_transaction_line 
			SET status = 'Pending',
				updated_user_id = $cBy,
				updated_at = now()
			WHERE transaction_id = uuid_to_bin('$transaction_id',true) AND status = 'Complete';";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}

			$mysqli->commit();
			closeDBT($mysqli, 1, $document_no);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'IssuedGTN'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {

		$dataParams = array(
			'obj',
			'obj=>transaction_id:s:0:1',
			'obj=>transaction_line_id:s:0:1',
			'obj=>fg_tag_no:s:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			/* Transaction */

			$sql = "SELECT bin_to_uuid(order_header_id,true) order_header_id 
					FROM tbl_transaction 
					WHERE transaction_id = uuid_to_bin('$transaction_id',true);";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล document_no : ' . $document_no);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$order_header_id = $row['order_header_id'];
			}


			$sql = "UPDATE tbl_transaction
			SET editing_at = NOW(), 
				editing_user_id = $cBy
			WHERE transaction_id = uuid_to_bin('$transaction_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}


			$sql = "SELECT package_no FROM tbl_transaction_line
			WHERE transaction_id = uuid_to_bin('$transaction_id',true) AND transaction_line_id = uuid_to_bin('$transaction_line_id',true);";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				$package_no = $re1->fetch_array(MYSQLI_ASSOC)['package_no'];

				$sql = "UPDATE tbl_package_master
				SET delivery_status = 'In',
				supplier_id = NULL,
				updated_at = NOW(),
				updated_user_id = $cBy
				WHERE package_code = '$package_no';";
				sqlError($mysqli, __LINE__, $sql, 1, 1);
				/* if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
				} */
			}

			$sql = "UPDATE tbl_inventory_detail t1,
			(SELECT t3.part_tag_no 
			FROM tbl_transaction_detail t1
			INNER JOIN tbl_transaction_line t2 ON t1.transaction_line_id = t2.transaction_line_id
			INNER JOIN tbl_inventory_detail t3 ON t1.part_tag_no = t3.part_tag_no
			WHERE transaction_id = uuid_to_bin('$transaction_id',true)
			AND fg_tag_no = '$fg_tag_no') t2
			SET t1.package_no = NULL,
				t1.updated_at = NOW(), 
				t1.updated_user_id = $cBy
			WHERE t1.part_tag_no = t2.part_tag_no;";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			$sql = "DELETE FROM tbl_transaction_line
			WHERE transaction_id = uuid_to_bin('$transaction_id',true) AND fg_tag_no = '$fg_tag_no';";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถยกเลิกได้ ' . __LINE__);
			}

			$sql = "DELETE FROM tbl_transaction_detail
			WHERE transaction_line_id = uuid_to_bin('$transaction_line_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถยกเลิกได้ ' . __LINE__);
			}

			$sql = "SELECT * FROM tbl_transaction_line
			WHERE transaction_id = uuid_to_bin('$transaction_id',true) AND status != 'Cancel';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				$sql = "UPDATE tbl_order_header 
				SET order_status = 'Picking',
					delivery_status = 'Pending',
					updated_user_id = $cBy,
					updated_at = now()
				WHERE order_header_id = uuid_to_bin('$order_header_id',true);";
				sqlError($mysqli, __LINE__, $sql, 1, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
				}
			}

			$mysqli->commit();
			closeDBT($mysqli, 1, 'OK');
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'IssuedGTN'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {

		$dataParams = array(
			'obj',
			'obj=>document_no:s:0:1',
			'obj=>document_date:s:0:1',
			'obj=>dos_no:s:0:1',
			'obj=>delivery_date:s:0:1',
			'obj=>truck_number:s:0:1',
			'obj=>driver_name:s:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {


			$sql = "SELECT bin_to_uuid(order_header_id,true) order_header_id, delivery_date order_delivery_date FROM tbl_order_header WHERE dos_no = '$dos_no';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($re1->num_rows === 0) {
				throw new Exception('ไม่พบข้อมูล DOS No.');
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$order_header_id = $row['order_header_id'];
				$order_delivery_date = $row['order_delivery_date'];
			}


			$date = date_create($delivery_date);
			$delivery_date = date_format($date, "Y-m-d");

			$delivery_status = '';
			// echo ($order_delivery_date . ' = ' . $delivery_date . '<br>');
			if ($order_delivery_date < $delivery_date) {
				$delivery_status = 'Delivery delay';
			} else if ($order_delivery_date == $delivery_date) {
				$delivery_status = 'Delivery on-time';
			} else {
				$delivery_status = 'Delivery early';
			}

			$sql = "SELECT
				t3.package_no
			FROM
				tbl_order_header t1
					INNER JOIN
				tbl_order t2 ON t1.order_header_id = t2.order_header_id
					INNER JOIN
				tbl_inventory_detail t3 ON t2.part_tag_no = t3.part_tag_no
			WHERE
				t1.dos_no = '$dos_no'
					AND t3.package_no IS NULL;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1, 0);
			if ($re1->num_rows > 0) {
				throw new Exception('ยังสแกนรายการไม่ครบตามออเดอร์');
			}


			$sql = "SELECT bin_to_uuid(transaction_id,true) transaction_id 
					FROM tbl_transaction 
					WHERE document_no = '$document_no' AND transaction_type = 'Temp-Picking'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล document_no : ' . $document_no);
			}
			$transaction_id = $re1->fetch_assoc()["transaction_id"];

			$document_no_new = $document_no;
			$gen = false;

			if ((stripos($document_no, "gtn")) === FALSE) {
				$sql = "SELECT func_GenRuningNumber('gtn',0) as document_no ;";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);
				if ($re1->num_rows === 0) {
					throw new Exception('ไม่พบข้อมูล document_no GTN ' . __LINE__);
				}
				$document_no_new = $re1->fetch_assoc()["document_no"];
				$gen = true;

				$sql = "UPDATE tbl_transaction 
				SET 
				created_at = NOW(), 
				created_user_id = $cBy
				WHERE transaction_id = uuid_to_bin('$transaction_id',true);";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . $sql);
				}
			} else {
				$sql = "UPDATE tbl_transaction 
				SET 
				updated_at = NOW(), 
				updated_user_id = $cBy
				WHERE transaction_id = uuid_to_bin('$transaction_id',true);";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . $sql);
				}
			}


			$truck_id = getTruckID($mysqli, $truck_number);
			$driver_id = getDriverID($mysqli, $driver_name);


			$sql = "UPDATE tbl_transaction 
				SET transaction_type = 'Picking', 
				document_no = '$document_no_new',
				document_date = '$document_date',
				delivery_date = '$delivery_date',
				truck_id = uuid_to_bin('$truck_id',true),
				driver_id = uuid_to_bin('$driver_id',true),
				editing_at = null,
				editing_user_id = null
				WHERE transaction_id = uuid_to_bin('$transaction_id',true) AND transaction_type = 'Temp-Picking';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . $sql);
			}


			$sql = "UPDATE tbl_transaction_line 
				SET status = 'Complete',
					updated_user_id = $cBy,
					updated_at = now()
				WHERE transaction_id = uuid_to_bin('$transaction_id',true) AND status = 'Pending';";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}

			$sql = "UPDATE tbl_order_header 
				SET order_status = 'In-transit',
					delivery_status = '$delivery_status',
					updated_user_id = $cBy,
					updated_at = now()
				WHERE order_header_id = uuid_to_bin('$order_header_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}

			// exit($sql);

			$document_no = $document_no_new;
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
