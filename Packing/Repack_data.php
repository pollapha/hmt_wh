<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'Repack'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'Repack'}[0] == 0) {
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

		$dataParams = array(
			'obj',
			'obj=>document_no:s:0:0',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			if ($document_no == '') {

				$sql = "SELECT 
					BIN_TO_UUID(t1.transaction_id, TRUE) AS transaction_id,
					document_no, t1.document_date, order_no, t1.invoice_no,
					date_format(t3.delivery_date, '%a %d-%m-%Y') delivery_date
				FROM
					tbl_transaction t1
						LEFT JOIN
					tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
						INNER JOIN
					tbl_order_header t3 ON t1.order_header_id = t3.order_header_id
				WHERE
					t1.editing_user_id = $cBy
						AND t1.transaction_type = 'Temp-Packing'
						AND t2.status = 'Pending'
				GROUP BY t1.transaction_id ORDER BY t1.editing_at DESC LIMIT 1;";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				$header = jsonRow($re1, true, 0);

				$body = [];

				if (count($header) > 0) {
					$transaction_id = $header[0]['transaction_id'];

					$sql = "WITH transaction_line AS 
					(SELECT
						transaction_id, transaction_line_id, work_order_no, case_tag_no, fg_tag_no, qty qty_per_pallet, net_per_pallet
					FROM
						tbl_transaction_line
					WHERE
						transaction_id = uuid_to_bin('$transaction_id',true)
							AND status = 'Pending'
					GROUP BY work_order_no, fg_tag_no, case_tag_no
					ORDER BY work_order_no, fg_tag_no, case_tag_no)
					SELECT
						BIN_TO_UUID(transaction_id, TRUE) AS transaction_id,
						BIN_TO_UUID(t1.transaction_line_id, TRUE) AS transaction_line_id,
						t2.work_order_no, t2.fg_tag_no, part_tag_no, part_no, part_name, t1.qty, t1.net_per_pcs,
						if(ROW_NUMBER() OVER(partition by fg_tag_no, case_tag_no ORDER BY work_order_no, fg_tag_no, part_tag_no) = 1, qty_per_pallet,'') qty_per_pallet,
						if(ROW_NUMBER() OVER(partition by fg_tag_no, case_tag_no ORDER BY work_order_no, fg_tag_no, part_tag_no) = 1, net_per_pallet,'') net_per_pallet
						
					FROM
						tbl_transaction_detail t1
							inner join transaction_line t2 ON t1.transaction_line_id = t2.transaction_line_id
							inner join tbl_part_master t3 on t1.part_id = t3.part_id
					WHERE
						transaction_id = uuid_to_bin('$transaction_id',true)
					ORDER BY work_order_no, fg_tag_no, part_tag_no";
					// exit($sql);
					$re1 = sqlError($mysqli, __LINE__, $sql, 1);

					$body = jsonRow($re1, true, 0);
				}
			} else {


				$sql = "SELECT 
					BIN_TO_UUID(t1.transaction_id, TRUE) AS transaction_id,
					document_no, t1.document_date, order_no, t1.invoice_no,
					date_format(t3.delivery_date, '%a %d-%m-%Y') delivery_date
				FROM
					tbl_transaction t1
						LEFT JOIN
					tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
						INNER JOIN
					tbl_order_header t3 ON t1.order_header_id = t3.order_header_id
				WHERE
					t1.document_no = '$document_no'
						AND t1.transaction_type = 'Temp-Packing'
						AND t2.status = 'Pending'
				GROUP BY t1.transaction_id ORDER BY t1.editing_at DESC LIMIT 1;";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				$header = jsonRow($re1, true, 0);

				$body = [];

				if (count($header) > 0) {
					$transaction_id = $header[0]['transaction_id'];

					$sql = "WITH transaction_line AS 
					(SELECT
						transaction_id, transaction_line_id, work_order_no, case_tag_no, fg_tag_no, qty qty_per_pallet, net_per_pallet
					FROM
						tbl_transaction_line
					WHERE
						transaction_id = uuid_to_bin('$transaction_id',true)
							AND status = 'Pending'
					GROUP BY work_order_no, fg_tag_no, case_tag_no
					ORDER BY work_order_no, fg_tag_no, case_tag_no)
					SELECT
						BIN_TO_UUID(transaction_id, TRUE) AS transaction_id,
						BIN_TO_UUID(t1.transaction_line_id, TRUE) AS transaction_line_id,
						t2.work_order_no, t2.fg_tag_no, part_tag_no, part_no, part_name, t1.qty, t1.net_per_pcs,
					if(ROW_NUMBER() OVER(partition by fg_tag_no, case_tag_no ORDER BY work_order_no, fg_tag_no, part_tag_no) = 1, qty_per_pallet,'') qty_per_pallet,
					if(ROW_NUMBER() OVER(partition by fg_tag_no, case_tag_no ORDER BY work_order_no, fg_tag_no, part_tag_no) = 1, net_per_pallet,'') net_per_pallet
						
					FROM
						tbl_transaction_detail t1
							inner join transaction_line t2 ON t1.transaction_line_id = t2.transaction_line_id
							inner join tbl_part_master t3 on t1.part_id = t3.part_id
					WHERE
						transaction_id = uuid_to_bin('$transaction_id',true)
					ORDER BY work_order_no, fg_tag_no, part_tag_no";
					// exit($sql);
					$re1 = sqlError($mysqli, __LINE__, $sql, 1);

					$body = jsonRow($re1, true, 0);
				}
			}

			$returnData = ['header' => $header, 'body' => $body];
			closeDBT($mysqli, 1, $returnData);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 2) {
	} else if ($type == 3) {

		$dataParams = array(
			'obj',
			'obj=>order_no:s:0:1',
			'obj=>part_no:s:0:0',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$where = '';
			if ($part_no != '') {
				$part_id = getPartID($mysqli, $part_no);
				$where = "AND part_no = '$part_no'";
			}

			/* $sql = "SELECT order_no, bin_to_uuid(t1.order_header_id,true) order_header_id,
			bin_to_uuid(t2.order_id,true) order_id,
			part_no, part_name, case_tag_no, (qty-packing_qty) qty_per_pallet, (net_per_pallet/qty)*(qty-packing_qty) net_per_pallet,
			invoice_no
			FROM tbl_order_header t1 
				INNER JOIN tbl_order t2 ON t1.order_header_id = t2.order_header_id
				INNER JOIN tbl_part_master t3 ON t2.part_id = t3.part_id
			WHERE order_status = 'Packing' 
				AND t2.status != 'Cancel' 
				AND order_no = '$order_no'
				AND (t2.repack = 'No' OR (qty-packing_qty) > 0)
				$where;"; */
			$sql = "SELECT order_no, bin_to_uuid(t1.order_header_id,true) order_header_id,
			bin_to_uuid(t2.order_id,true) order_id,
			part_no, part_name, part_tag_no, qty, net_per_pcs
			FROM tbl_order_header t1 
				INNER JOIN tbl_order t2 ON t1.order_header_id = t2.order_header_id
				INNER JOIN tbl_part_master t3 ON t2.part_id = t3.part_id
			WHERE order_status = 'Packing' 
				AND t2.status != 'Cancel' 
				AND order_no = '$order_no'
				AND t2.repack = 'No'
			$where
			ORDER BY part_no, case_tag_no;";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$body = jsonRow($re1, true, 0);
			$returnData = ['body' => $body, 'invoice_no' => ''];

			$mysqli->commit();
			closeDBT($mysqli, 1, $returnData);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'Repack'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {

		$dataParams = array(
			'obj',
			'obj=>document_no:s:0:0',
			'obj=>document_date:s:0:1',
			'obj=>order_no:s:0:1',
			'obj=>part_no:s:0:1',
			'obj=>order_header_id:s:0:1',
			'obj=>order_id:s:0:1',
			'obj=>case_tag_no:s:0:1',
			'obj=>qty_per_pallet:i:0:1',
			'obj=>net_per_pallet:i:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT bin_to_uuid(transaction_line_id,true) transaction_line_id FROM tbl_transaction_line t1
			LEFT JOIN tbl_transaction t2 ON t1.transaction_id = t2.transaction_id
			WHERE order_header_id = uuid_to_bin('$order_header_id',true) 
			AND status != 'Cancel' AND document_no != '$document_no'
			AND (transaction_type = 'Temp-Packing' OR transaction_type = 'Packing');";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('เลข Order นี้มีเอกสารอยู่แล้ว' . __LINE__);
			}
			/* Transaction */

			$sql = "SELECT bin_to_uuid(location_id,true) location_id FROM tbl_location_master WHERE location_area = 'wip' LIMIT 1;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($re1->num_rows === 0) {
				throw new Exception('ไม่พบข้อมูล Location');
			}
			$location_id = $re1->fetch_assoc()["location_id"];


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
				(document_no, document_date, transaction_type, order_header_id, created_at, created_user_id) 
				VALUES
				('$document_no','$document_date', 'Temp-Packing', uuid_to_bin('$order_header_id',true), now(), $cBy);";
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

			//exit($transaction_id);

			$sql = "UPDATE tbl_transaction
			SET document_date = '$document_date',
				editing_at = NOW(), 
				editing_user_id = $cBy
			WHERE transaction_id = uuid_to_bin('$transaction_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			$part_id = getPartID($mysqli, $part_no);


			/* Transaction */

			/* Genarate FG Tag */
			$sum_net = 0;

			$sql = "SELECT func_GenRuningNumber('fg_tag',0) as fg_tag_no ;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($re1->num_rows === 0) {
				throw new Exception('ไม่พบข้อมูล FG Tag');
			}
			$fg_tag_no = $re1->fetch_assoc()["fg_tag_no"];

			/* Genarate Work Order */

			$sql = "SELECT work_order_no
			FROM tbl_transaction_line
			WHERE transaction_id = uuid_to_bin('$transaction_id',true) AND status = 'Pending'
			AND part_id = uuid_to_bin('$part_id',true)
			AND work_order_no IS NOT NULL LIMIT 1;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$work_order_no = $row['work_order_no'];
				}
			} else {
				$sql = "SELECT func_GenRuningNumber('work_order',0) as work_order_no ;";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);
				if ($re1->num_rows === 0) {
					throw new Exception('ไม่พบข้อมูล document no PAC ' . __LINE__);
				}
				$work_order_no = $re1->fetch_assoc()["work_order_no"];
			}


			$part_tag_array = [];
			$sql = "SELECT part_tag_no, net_per_pcs
			FROM tbl_inventory t1 
				INNER JOIN tbl_inventory_detail t2 ON t1.inventory_id = t2.inventory_id
			WHERE case_tag_no = '$case_tag_no';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$part_tag_no = $row['part_tag_no'];
				$net_per_pcs = $row['net_per_pcs'];
				$sum_net += $net_per_pcs;

				if ($sum_net > 1500) {
					$left_net = $sum_net - 1500;
					$sql = "SELECT func_GenRuningNumber('fg_tag',0) as fg_tag_no ;";
					$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);
					if ($re1->num_rows === 0) {
						throw new Exception('ไม่พบข้อมูล FG Tag');
					}
					$fg_tag_no = $re1->fetch_assoc()["fg_tag_no"];

					$part_tag_array[] = ['fg_tag_no' => $fg_tag_no, 'part_tag_no' => $part_tag_no];
					if ($left_net > 0) {
						$sum_net = $left_net;
					} else {
						$sum_net = 0;
					}
				} else {
					$part_tag_array[] = ['fg_tag_no' => $fg_tag_no, 'part_tag_no' => $part_tag_no];
				}


				// echo ($sum_net . '<br>');

				$sql = "INSERT INTO tbl_transaction_line 
					( pallet_no, case_tag_no, fg_tag_no, work_order_no, part_id, qty, net_per_pallet, 
					certificate_no, coil_lot_no, orientation, package_type, 
					remark, transaction_id, from_location_id, to_location_id, order_id, 
					invoice_no,
					created_at, updated_at, created_user_id, updated_user_id
					)
					SELECT 
					pallet_no, case_tag_no, '$fg_tag_no', '$work_order_no', part_id, 1, $net_per_pcs, 
					certificate_no, coil_lot_no, orientation, package_type, 
					remark,  uuid_to_bin('$transaction_id',true), location_id, uuid_to_bin('$location_id',true), uuid_to_bin('$order_id',true), 
					invoice_no,
					NOW(), NOW(), $cBy, $cBy
					FROM tbl_inventory
					WHERE case_tag_no = '$case_tag_no'
					ON DUPLICATE KEY UPDATE 
					tbl_transaction_line.qty = tbl_transaction_line.qty+1,
					tbl_transaction_line.net_per_pallet = tbl_transaction_line.net_per_pallet+$net_per_pcs;";
				//exit($sql);
				sqlError($mysqli, __LINE__, $sql, 1, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
				}

				$sql = "UPDATE tbl_inventory
					SET 
						packing_qty = packing_qty+1,
						location_id = uuid_to_bin('$location_id',true),
						updated_user_id = $cBy,
						updated_at = now()
					WHERE case_tag_no = '$case_tag_no';";
				sqlError($mysqli, __LINE__, $sql, 1, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
				}
			}

			// exit();

			$sql = "INSERT INTO tbl_inventory_line
				( fg_tag_no, part_id, qty, net_per_pallet, certificate_no, coil_lot_no, repack_process, package_type,
				location_id, transaction_line_id, order_id, inventory_id,
				created_at, updated_at, created_user_id, updated_user_id
				)
				SELECT 
				t1.fg_tag_no, t1.part_id, t1.qty, t1.net_per_pallet, t1.certificate_no, t1.coil_lot_no, repack_process, t2.package_type,
				t1.to_location_id, t1.transaction_line_id, t1.order_id, t2.inventory_id,
				NOW(), NOW(), $cBy, $cBy
				FROM tbl_transaction_line t1
				INNER JOIN tbl_inventory t2 ON t1.case_tag_no = t2.case_tag_no
				WHERE transaction_id = uuid_to_bin('$transaction_id',true)
				AND t1.case_tag_no = '$case_tag_no'
				AND t1.status != 'Cancel'
				ON DUPLICATE KEY UPDATE 
				fg_tag_no = VALUES(fg_tag_no),
				part_id = VALUES(part_id), 
				qty = VALUES(qty), 
				net_per_pallet = VALUES(net_per_pallet), 
				certificate_no = VALUES(certificate_no),
				coil_lot_no = VALUES(coil_lot_no),
				repack_process = VALUES(repack_process),
				package_type = VALUES(package_type),
				location_id = VALUES(location_id), 
				transaction_line_id = VALUES(transaction_line_id), 
				inventory_id = VALUES(inventory_id), 
				order_id = VALUES(order_id),
				updated_at = NOW(), 
				updated_user_id = $cBy;";
			// exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			foreach ($part_tag_array as $value) {
				$fg_tag_no = $value['fg_tag_no'];
				$part_tag_no = $value['part_tag_no'];
				$sql = "SELECT bin_to_uuid(t1.transaction_line_id,true) transaction_line_id, t1.fg_tag_no, 
				bin_to_uuid(t2.inventory_line_id,true) inventory_line_id, bin_to_uuid(t2.order_id,true) order_id
				FROM tbl_transaction_line t1
					INNER JOIN tbl_inventory_line t2 ON t1.transaction_line_id = t2.transaction_line_id
				WHERE transaction_id = uuid_to_bin('$transaction_id',true) AND t1.fg_tag_no = '$fg_tag_no';";
				//exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล ' . __LINE__);
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$transaction_line_id = $row['transaction_line_id'];
					$inventory_line_id = $row['inventory_line_id'];
					$order_id = $row['order_id'];

					$sql = "INSERT INTO tbl_transaction_detail 
						( part_tag_no, part_id, qty, net_per_pcs, remark, parent_tag_no, 
						transaction_line_id, from_location_id, to_location_id,
						created_at, updated_at, created_user_id, updated_user_id
						)
						SELECT 
						part_tag_no, part_id, qty, net_per_pcs, remark, '$case_tag_no', 
						uuid_to_bin('$transaction_line_id',true), location_id, uuid_to_bin('$location_id',true),
						NOW(), NOW(), $cBy, $cBy
						FROM tbl_inventory_detail
						WHERE part_tag_no = '$part_tag_no';";
					sqlError($mysqli, __LINE__, $sql, 1, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
					}

					$sql = "UPDATE tbl_inventory_detail
					SET inventory_line_id = uuid_to_bin('$inventory_line_id',true),
						order_id = uuid_to_bin('$order_id',true),
						updated_at = NOW(), 
						updated_user_id = $cBy
					WHERE part_tag_no = '$part_tag_no';";
					sqlError($mysqli, __LINE__, $sql, 1, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
					}
				}
			}

			// exit();

			$sql = "UPDATE tbl_order_header
			SET delivery_status = 'On process',
				updated_at = NOW(), 
				updated_user_id = $cBy
			WHERE order_no = '$order_no';";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			$sql = "UPDATE tbl_order
			SET repack = 'Pending',
				work_order_no = '$work_order_no',
				updated_at = NOW(), 
				updated_user_id = $cBy
			WHERE order_header_id = uuid_to_bin('$order_header_id',true) AND order_id = uuid_to_bin('$order_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}


			$mysqli->commit();
			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 12) {

		$dataParams = array(
			'obj',
			'obj=>document_no:s:0:0',
			'obj=>document_date:s:0:1',
			'obj=>order_no:s:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT bin_to_uuid(order_header_id,true) order_header_id FROM tbl_order_header WHERE order_no = '$order_no';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			$order_header_id = $re1->fetch_array(MYSQLI_ASSOC)['order_header_id'];


			$sql = "SELECT bin_to_uuid(transaction_line_id,true) transaction_line_id FROM tbl_transaction_line t1
			LEFT JOIN tbl_transaction t2 ON t1.transaction_id = t2.transaction_id
			WHERE order_header_id = uuid_to_bin('$order_header_id',true) 
			AND status != 'Cancel' AND document_no != '$document_no' 
			AND (transaction_type = 'Temp-Packing' OR transaction_type = 'Packing');";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('เลข Order นี้มีเอกสารอยู่แล้ว ' . __LINE__);
			}


			/* Transaction */

			$sql = "SELECT bin_to_uuid(location_id,true) location_id FROM tbl_location_master WHERE location_area = 'wip' LIMIT 1;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($re1->num_rows === 0) {
				throw new Exception('ไม่พบข้อมูล Location');
			}
			$location_id = $re1->fetch_assoc()["location_id"];


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
				(document_no, document_date, transaction_type, order_header_id, created_at, created_user_id) 
				VALUES
				('$document_no','$document_date', 'Temp-Packing', uuid_to_bin('$order_header_id',true), now(), $cBy);";
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

			//exit($transaction_id);

			$sql = "UPDATE tbl_transaction
			SET document_date = '$document_date',
				editing_at = NOW(), 
				editing_user_id = $cBy
			WHERE transaction_id = uuid_to_bin('$transaction_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}



			// 		
			$fg_array = [];

			$sql = "WITH a AS (
			SELECT bin_to_uuid(t1.order_id,true) order_id, bin_to_uuid(t1.part_id,true) part_id, 
			t1.work_order_no, t1.case_tag_no, SUM(t1.net_per_pcs) sum_net, SUM(t1.qty) sum_qty, t2.package_type
			FROM tbl_order t1 INNER JOIN tbl_part_master t2 ON t1.part_id = t2.part_id
			INNER JOIN tbl_supplier_master t3 ON t2.supplier_id = t3.supplier_id
			WHERE order_header_id = uuid_to_bin('$order_header_id',true) 
			AND t1.fg_tag_no IS NULL AND t1.status = 'Complete' AND t1.repack = 'No' 
			AND t2.package_type = 'Steel' AND (t3.supplier_code = 'HMTH' OR t3.supplier_code = 'NKAPM')
			GROUP BY t1.case_tag_no
			order by t1.work_order_no, sum_net DESC, t1.case_tag_no, t1.part_tag_no),
			b AS (
			SELECT a.*, t1.part_tag_no, t1.net_per_pcs, t1.qty,
			SUM(t1.net_per_pcs) OVER (PARTITION BY a.case_tag_no order by work_order_no, sum_net DESC, case_tag_no, part_tag_no) as cumulative
			FROM a INNER JOIN tbl_order t1 ON a.case_tag_no = t1.case_tag_no
			WHERE order_header_id = uuid_to_bin('$order_header_id',true) 
			AND status = 'Complete' AND repack = 'No'
			order by work_order_no, sum_net DESC, case_tag_no, part_tag_no)
			SELECT order_id, part_id, package_type, work_order_no, case_tag_no, part_tag_no, net_per_pcs, qty, 
			if(cumulative > 1500, 0, cumulative) cumulative, if(cumulative > 1500, net_per_pcs, 0) net_left,
			row_number() over(partition by case_tag_no order by work_order_no, sum_net DESC, case_tag_no, part_tag_no) row_num
			FROM b order by work_order_no, sum_net DESC, case_tag_no, part_tag_no";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$part_tag_no = $row['part_tag_no'];
					$qty = $row['qty'];
					$net_per_pcs = $row['net_per_pcs'];
					$net_left = $row['net_left'];
					$row_num = $row['row_num'];

					if ($row_num == 1) {
						$sql = "SELECT func_GenRuningNumber('fg_tag',0) as fg_tag_no ;";
						$result = sqlError($mysqli, __LINE__, $sql, 1, 1);
						if ($result->num_rows === 0) {
							throw new Exception('ไม่พบข้อมูล FG Tag');
						}
						$fg_tag_no = $result->fetch_assoc()["fg_tag_no"];
					}

					if ($net_left <= 0) {
						$fg_array[] = ['fg_tag_no' => $fg_tag_no, 'part_tag_no' => $part_tag_no, 'qty' => $qty, 'net_per_pcs' => $net_per_pcs];
					}
				}
			}

			foreach ($fg_array as $row) {
				$fg_tag_no = $row['fg_tag_no'];
				$part_tag_no = $row['part_tag_no'];
				$qty = $row['qty'];
				$net_per_pcs = $row['net_per_pcs'];

				$sql = "UPDATE tbl_order
				SET fg_tag_no = '$fg_tag_no',
				updated_user_id = $cBy,
				updated_at = now()
				WHERE order_header_id = uuid_to_bin('$order_header_id',true) AND part_tag_no = '$part_tag_no';";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
				}
			}

			$sql = "SELECT bin_to_uuid(t1.order_id,true) order_id, bin_to_uuid(t1.part_id,true) part_id, 
			t1.work_order_no, t1.case_tag_no, SUM(t1.net_per_pcs) sum_net, SUM(t1.qty) sum_qty, t2.package_type
			FROM tbl_order t1 INNER JOIN tbl_part_master t2 ON t1.part_id = t2.part_id
			INNER JOIN tbl_supplier_master t3 ON t2.supplier_id = t3.supplier_id
			WHERE order_header_id = uuid_to_bin('$order_header_id',true) 
			AND t1.fg_tag_no IS NULL AND t1.status = 'Complete' AND t1.repack = 'No' AND t2.package_type = 'Steel' 
			AND (t3.supplier_code = 'HMTH' OR t3.supplier_code = 'NKAPM')
			GROUP BY t1.case_tag_no
			order by t1.work_order_no, sum_net DESC, t1.case_tag_no, t1.part_tag_no;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$work_order_no = $row['work_order_no'];
					$case_tag_no = $row['case_tag_no'];
					$sum_net = $row['sum_net'];
					$sum_qty = $row['sum_qty'];

					// echo ($case_tag_no);
					$sql = "WITH a AS (
					SELECT bin_to_uuid(t1.order_id,true) order_id, bin_to_uuid(t1.part_id,true) part_id, 
					t1.work_order_no, t1.case_tag_no, t1.fg_tag_no, SUM(t1.net_per_pcs) sum_net, SUM(t1.qty) sum_qty, t2.package_type
					FROM tbl_order t1 INNER JOIN tbl_part_master t2 ON t1.part_id = t2.part_id
					WHERE order_header_id = uuid_to_bin('$order_header_id',true) 
					AND t1.fg_tag_no IS NOT NULL AND t1.status = 'Complete'
					AND work_order_no = '$work_order_no'
					GROUP BY t1.fg_tag_no
					order by t1.work_order_no, sum_net DESC, t1.case_tag_no, t1.part_tag_no)
					SELECT * FROM a WHERE sum_net+$sum_net <= 1500;";
					// exit($sql);
					$re2 = sqlError($mysqli, __LINE__, $sql, 1);
					if ($re2->num_rows > 0) {
						while ($row2 = $re2->fetch_array(MYSQLI_ASSOC)) {
							$fg_tag_no = $row2['fg_tag_no'];

							$sql = "UPDATE tbl_order t1,
							(
								SELECT part_tag_no FROM tbl_order WHERE order_header_id = uuid_to_bin('$order_header_id',true) 
								AND case_tag_no = '$case_tag_no' AND fg_tag_no IS NULL
							) t2
							SET 
							t1.fg_tag_no = '$fg_tag_no',
							t1.updated_user_id = $cBy,
							t1.updated_at = now()
							WHERE t1.order_header_id = uuid_to_bin('$order_header_id',true) AND t1.part_tag_no = t2.part_tag_no;";
							// exit($sql);
							sqlError($mysqli, __LINE__, $sql, 1);
							if ($mysqli->affected_rows == 0) {
								throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
							}
						}
					} else {

						$sql = "SELECT func_GenRuningNumber('fg_tag',0) as fg_tag_no ;";
						$result = sqlError($mysqli, __LINE__, $sql, 1, 1);
						if ($result->num_rows === 0) {
							throw new Exception('ไม่พบข้อมูล FG Tag');
						}
						$fg_tag_no = $result->fetch_assoc()["fg_tag_no"];

						$sql = "UPDATE tbl_order t1,
						(
							SELECT part_tag_no FROM tbl_order WHERE order_header_id = uuid_to_bin('$order_header_id',true) 
							AND case_tag_no = '$case_tag_no' AND fg_tag_no IS NULL
						) t2
						SET 
						t1.fg_tag_no = '$fg_tag_no',
						t1.updated_user_id = $cBy,
						t1.updated_at = now()
						WHERE t1.order_header_id = uuid_to_bin('$order_header_id',true) AND t1.part_tag_no = t2.part_tag_no;";
						// exit($sql);
						sqlError($mysqli, __LINE__, $sql, 1);
						if ($mysqli->affected_rows == 0) {
							throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
						}
					}
				}
			}


			// Wooden NKAPM
			$fg_array = [];

			$sql = "WITH a AS (
			SELECT bin_to_uuid(t1.order_id,true) order_id, bin_to_uuid(t1.part_id,true) part_id, 
			t1.work_order_no, t1.case_tag_no, SUM(t1.net_per_pcs) sum_net, SUM(t1.qty) sum_qty, t2.package_type
			FROM tbl_order t1 INNER JOIN tbl_part_master t2 ON t1.part_id = t2.part_id
			INNER JOIN tbl_supplier_master t3 ON t2.supplier_id = t3.supplier_id
			WHERE order_header_id = uuid_to_bin('$order_header_id',true) 
			AND t1.fg_tag_no IS NULL AND t1.status = 'Complete' AND t1.repack = 'No' 
			AND t2.package_type = 'Wooden' AND t3.supplier_code = 'NKAPM'
			GROUP BY t1.case_tag_no
			order by t1.work_order_no, sum_net DESC, t1.case_tag_no, t1.part_tag_no),
			b AS (
			SELECT a.*, t1.part_tag_no, t1.net_per_pcs, t1.qty,
			SUM(t1.net_per_pcs) OVER (PARTITION BY a.case_tag_no order by work_order_no, sum_net DESC, case_tag_no, part_tag_no) as cumulative
			FROM a INNER JOIN tbl_order t1 ON a.case_tag_no = t1.case_tag_no
			WHERE order_header_id = uuid_to_bin('$order_header_id',true) 
			AND status = 'Complete' AND repack = 'No'
			order by work_order_no, sum_net DESC, case_tag_no, part_tag_no)
			SELECT order_id, part_id, package_type, work_order_no, case_tag_no, part_tag_no, net_per_pcs, qty, 
			if(cumulative > 1500, 0, cumulative) cumulative, if(cumulative > 1500, net_per_pcs, 0) net_left,
			row_number() over(partition by case_tag_no order by work_order_no, sum_net DESC, case_tag_no, part_tag_no) row_num
			FROM b order by work_order_no, sum_net DESC, case_tag_no, part_tag_no";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$part_tag_no = $row['part_tag_no'];
					$qty = $row['qty'];
					$net_per_pcs = $row['net_per_pcs'];
					$net_left = $row['net_left'];
					$row_num = $row['row_num'];

					if ($row_num == 1) {
						$sql = "SELECT func_GenRuningNumber('fg_tag',0) as fg_tag_no ;";
						$result = sqlError($mysqli, __LINE__, $sql, 1, 1);
						if ($result->num_rows === 0) {
							throw new Exception('ไม่พบข้อมูล FG Tag');
						}
						$fg_tag_no = $result->fetch_assoc()["fg_tag_no"];
					}

					if ($net_left <= 0) {
						$fg_array[] = ['fg_tag_no' => $fg_tag_no, 'part_tag_no' => $part_tag_no, 'qty' => $qty, 'net_per_pcs' => $net_per_pcs];
					}
				}
			}

			foreach ($fg_array as $row) {
				$fg_tag_no = $row['fg_tag_no'];
				$part_tag_no = $row['part_tag_no'];
				$qty = $row['qty'];
				$net_per_pcs = $row['net_per_pcs'];

				$sql = "UPDATE tbl_order
				SET fg_tag_no = '$fg_tag_no',
				updated_user_id = $cBy,
				updated_at = now()
				WHERE order_header_id = uuid_to_bin('$order_header_id',true) AND part_tag_no = '$part_tag_no';";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
				}
			}

			$sql = "SELECT bin_to_uuid(t1.order_id,true) order_id, bin_to_uuid(t1.part_id,true) part_id, 
			t1.work_order_no, t1.case_tag_no, SUM(t1.net_per_pcs) sum_net, SUM(t1.qty) sum_qty, t2.package_type
			FROM tbl_order t1 INNER JOIN tbl_part_master t2 ON t1.part_id = t2.part_id
			INNER JOIN tbl_supplier_master t3 ON t2.supplier_id = t3.supplier_id
			WHERE order_header_id = uuid_to_bin('$order_header_id',true) 
			AND t1.fg_tag_no IS NULL AND t1.status = 'Complete' AND t1.repack = 'No' AND t2.package_type = 'Wooden' AND t3.supplier_code = 'NKAPM'
			GROUP BY t1.case_tag_no
			order by t1.work_order_no, sum_net DESC, t1.case_tag_no, t1.part_tag_no;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$work_order_no = $row['work_order_no'];
					$case_tag_no = $row['case_tag_no'];
					$sum_net = $row['sum_net'];
					$sum_qty = $row['sum_qty'];

					// echo ($case_tag_no);
					$sql = "WITH a AS (
					SELECT bin_to_uuid(t1.order_id,true) order_id, bin_to_uuid(t1.part_id,true) part_id, 
					t1.work_order_no, t1.case_tag_no, t1.fg_tag_no, SUM(t1.net_per_pcs) sum_net, SUM(t1.qty) sum_qty, t2.package_type
					FROM tbl_order t1 INNER JOIN tbl_part_master t2 ON t1.part_id = t2.part_id
					WHERE order_header_id = uuid_to_bin('$order_header_id',true) 
					AND t1.fg_tag_no IS NOT NULL AND t1.status = 'Complete'
					AND work_order_no = '$work_order_no'
					GROUP BY t1.fg_tag_no
					order by t1.work_order_no, sum_net DESC, t1.case_tag_no, t1.part_tag_no)
					SELECT * FROM a WHERE sum_net+$sum_net <= 1500;";
					// exit($sql);
					$re2 = sqlError($mysqli, __LINE__, $sql, 1);
					if ($re2->num_rows > 0) {
						while ($row2 = $re2->fetch_array(MYSQLI_ASSOC)) {
							$fg_tag_no = $row2['fg_tag_no'];

							$sql = "UPDATE tbl_order t1,
							(
								SELECT part_tag_no FROM tbl_order WHERE order_header_id = uuid_to_bin('$order_header_id',true) 
								AND case_tag_no = '$case_tag_no' AND fg_tag_no IS NULL
							) t2
							SET 
							t1.fg_tag_no = '$fg_tag_no',
							t1.updated_user_id = $cBy,
							t1.updated_at = now()
							WHERE t1.order_header_id = uuid_to_bin('$order_header_id',true) AND t1.part_tag_no = t2.part_tag_no;";
							// exit($sql);
							sqlError($mysqli, __LINE__, $sql, 1);
							if ($mysqli->affected_rows == 0) {
								throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
							}
						}
					} else {

						$sql = "SELECT func_GenRuningNumber('fg_tag',0) as fg_tag_no ;";
						$result = sqlError($mysqli, __LINE__, $sql, 1, 1);
						if ($result->num_rows === 0) {
							throw new Exception('ไม่พบข้อมูล FG Tag');
						}
						$fg_tag_no = $result->fetch_assoc()["fg_tag_no"];

						$sql = "UPDATE tbl_order t1,
						(
							SELECT part_tag_no FROM tbl_order WHERE order_header_id = uuid_to_bin('$order_header_id',true) 
							AND case_tag_no = '$case_tag_no' AND fg_tag_no IS NULL
						) t2
						SET 
						t1.fg_tag_no = '$fg_tag_no',
						t1.updated_user_id = $cBy,
						t1.updated_at = now()
						WHERE t1.order_header_id = uuid_to_bin('$order_header_id',true) AND t1.part_tag_no = t2.part_tag_no;";
						// exit($sql);
						sqlError($mysqli, __LINE__, $sql, 1);
						if ($mysqli->affected_rows == 0) {
							throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
						}
					}
				}
			}




			// Wooden HMT

			$fg_array = [];

			$sql = "SELECT bin_to_uuid(t1.order_id,true) order_id, bin_to_uuid(t1.part_id,true) part_id, t2.package_type,
			t1.work_order_no, t1.case_tag_no, t1.part_tag_no, t1.net_per_pcs, t1.qty
			FROM tbl_order t1 INNER JOIN tbl_part_master t2 ON t1.part_id = t2.part_id
			INNER JOIN tbl_supplier_master t3 ON t2.supplier_id = t3.supplier_id
			WHERE order_header_id = uuid_to_bin('$order_header_id',true) 
			AND t1.status = 'Complete' AND t1.repack = 'No' AND t2.package_type = 'Wooden' AND t3.supplier_code = 'HMTH'
			order by t1.work_order_no, t1.case_tag_no, t1.part_tag_no";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$part_tag_no = $row['part_tag_no'];

					$sql = "SELECT func_GenRuningNumber('fg_tag',0) as fg_tag_no ;";
					$result = sqlError($mysqli, __LINE__, $sql, 1, 1);
					if ($result->num_rows === 0) {
						throw new Exception('ไม่พบข้อมูล FG Tag');
					}
					$fg_tag_no = $result->fetch_assoc()["fg_tag_no"];

					$sql = "UPDATE tbl_order
					SET fg_tag_no = '$fg_tag_no',
					updated_user_id = $cBy,
					updated_at = now()
					WHERE order_header_id = uuid_to_bin('$order_header_id',true) AND part_tag_no = '$part_tag_no';";
					sqlError($mysqli, __LINE__, $sql, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
					}
				}
			}

			$sql = "UPDATE tbl_order t1,
			(
				SELECT part_tag_no FROM tbl_order WHERE order_header_id = uuid_to_bin('$order_header_id',true) AND status = 'Complete'
			) t2
			SET t1.repack = 'Pending',
			t1.updated_user_id = $cBy,
			t1.updated_at = now()
			WHERE t1.order_header_id = uuid_to_bin('$order_header_id',true) AND t1.part_tag_no = t2.part_tag_no;";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			$sql = "INSERT INTO tbl_transaction_line 
				( pallet_no, case_tag_no, fg_tag_no, work_order_no, part_id, certificate_no, orientation, package_type, invoice_no,
				remark, transaction_id, from_location_id, to_location_id, 
				qty, net_per_pallet, 
				created_at, updated_at, created_user_id, updated_user_id )
			SELECT 
				t1.pallet_no, t1.case_tag_no, t1.fg_tag_no, t1.work_order_no, t1.part_id, t2.certificate_no, t2.orientation, t2.package_type, t2.invoice_no,
				t2.remark, uuid_to_bin('$transaction_id',true), location_id, uuid_to_bin('$location_id',true),
				SUM(t1.qty), SUM(t1.net_per_pcs),
				NOW(), NOW(), $cBy, $cBy
			FROM tbl_order t1 INNER JOIN tbl_inventory t2 ON t1.case_tag_no = t2.case_tag_no
			WHERE order_header_id = uuid_to_bin('$order_header_id',true) AND t1.status = 'Complete' AND t1.repack = 'Pending'
			GROUP BY t1.work_order_no, t1.fg_tag_no, t1.case_tag_no
			ORDER BY t1.work_order_no, t1.fg_tag_no;";
			// exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}


			$sql = "INSERT INTO tbl_transaction_detail 
			( part_tag_no, part_id, qty, net_per_pcs, parent_tag_no, 
			transaction_line_id, from_location_id, to_location_id,
			created_at, updated_at, created_user_id, updated_user_id
			)
			SELECT t1.part_tag_no, t1.part_id, t1.qty, t1.net_per_pcs, t1.case_tag_no,
			t2.transaction_line_id, t2.from_location_id, uuid_to_bin('$location_id',true),
			NOW(), NOW(), $cBy, $cBy
			FROM tbl_order t1 INNER JOIN tbl_transaction_line t2 ON t1.work_order_no = t2.work_order_no AND t1.case_tag_no = t2.case_tag_no AND t1.fg_tag_no = t2.fg_tag_no
			WHERE order_header_id = uuid_to_bin('$order_header_id',true) AND t1.status = 'Complete' AND t1.repack = 'Pending';";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}


			$sql = "UPDATE tbl_order_header
			SET delivery_status = 'On process',
				updated_at = NOW(), 
				updated_user_id = $cBy
			WHERE order_no = '$order_no';";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			$mysqli->commit();
			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 13) {
		$dataParams = array(
			'obj',
			'obj=>document_no:s:0:0',
			'obj=>document_date:s:0:1',
			'obj=>order_no:s:0:1',
			'obj=>part_no:s:0:1',
			'obj=>order_header_id:s:0:1',
			'obj=>order_id:s:0:1',
			'obj=>case_tag_no:s:0:1',
			'obj=>qty_per_pallet:i:0:1',
			'obj=>net_per_pallet:i:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT bin_to_uuid(transaction_line_id,true) transaction_line_id FROM tbl_transaction_line t1
			LEFT JOIN tbl_transaction t2 ON t1.transaction_id = t2.transaction_id
			WHERE order_header_id = uuid_to_bin('$order_header_id',true) 
			AND status != 'Cancel' AND document_no != '$document_no'
			AND (transaction_type = 'Temp-Packing' OR transaction_type = 'Packing');";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('เลข Order นี้มีเอกสารอยู่แล้ว ' . __LINE__);
			}
			/* Transaction */

			$sql = "SELECT bin_to_uuid(location_id,true) location_id FROM tbl_location_master WHERE location_area = 'wip' LIMIT 1;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($re1->num_rows === 0) {
				throw new Exception('ไม่พบข้อมูล Location');
			}
			$location_id = $re1->fetch_assoc()["location_id"];

			$sql = "SELECT bin_to_uuid(location_id,true) left_location_id FROM tbl_location_master WHERE location_area = 'overflow' LIMIT 1;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($re1->num_rows === 0) {
				throw new Exception('ไม่พบข้อมูล Location');
			}
			$left_location_id = $re1->fetch_assoc()["left_location_id"];


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
				(document_no, document_date, transaction_type, order_header_id, created_at, created_user_id) 
				VALUES
				('$document_no','$document_date', 'Temp-Packing', uuid_to_bin('$order_header_id',true), now(), $cBy);";
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

			//exit($transaction_id);

			$sql = "UPDATE tbl_transaction
			SET document_date = '$document_date',
				editing_at = NOW(), 
				editing_user_id = $cBy
			WHERE transaction_id = uuid_to_bin('$transaction_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			$part_id = getPartID($mysqli, $part_no);


			/* Transaction */

			/* Genarate FG Tag */
			$sum_net = 0;

			$sql = "SELECT func_GenRuningNumber('fg_tag',0) as fg_tag_no ;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($re1->num_rows === 0) {
				throw new Exception('ไม่พบข้อมูล FG Tag');
			}
			$fg_tag_no = $re1->fetch_assoc()["fg_tag_no"];

			/* Genarate Work Order */

			$sql = "SELECT work_order_no
			FROM tbl_transaction_line
			WHERE transaction_id = uuid_to_bin('$transaction_id',true) AND status = 'Pending'
			AND part_id = uuid_to_bin('$part_id',true)
			AND work_order_no IS NOT NULL LIMIT 1;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$work_order_no = $row['work_order_no'];
				}
			} else {
				$sql = "SELECT func_GenRuningNumber('work_order',0) as work_order_no ;";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);
				if ($re1->num_rows === 0) {
					throw new Exception('ไม่พบข้อมูล document no PAC ' . __LINE__);
				}
				$work_order_no = $re1->fetch_assoc()["work_order_no"];
			}


			$part_tag_array = [];
			$sql = "SELECT part_tag_no, net_per_pcs
			FROM tbl_inventory t1 
				INNER JOIN tbl_inventory_detail t2 ON t1.inventory_id = t2.inventory_id
			WHERE case_tag_no = '$case_tag_no';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$part_tag_no = $row['part_tag_no'];
				$net_per_pcs = $row['net_per_pcs'];
				$sum_net += $net_per_pcs;

				if ($sum_net < 1500) {
					$part_tag_array[] = ['fg_tag_no' => $fg_tag_no, 'part_tag_no' => $part_tag_no];

					$sql = "INSERT INTO tbl_transaction_line 
					( pallet_no, case_tag_no, fg_tag_no, work_order_no, part_id, qty, net_per_pallet, 
					certificate_no, coil_lot_no, orientation, package_type, 
					remark, transaction_id, from_location_id, to_location_id, order_id, 
					invoice_no,
					created_at, updated_at, created_user_id, updated_user_id
					)
					SELECT 
					pallet_no, case_tag_no, '$fg_tag_no', '$work_order_no', part_id, 1, $net_per_pcs, 
					certificate_no, coil_lot_no, orientation, package_type, 
					remark,  uuid_to_bin('$transaction_id',true), location_id, uuid_to_bin('$location_id',true), uuid_to_bin('$order_id',true), 
					invoice_no,
					NOW(), NOW(), $cBy, $cBy
					FROM tbl_inventory
					WHERE case_tag_no = '$case_tag_no'
					ON DUPLICATE KEY UPDATE 
					tbl_transaction_line.qty = tbl_transaction_line.qty+1,
					tbl_transaction_line.net_per_pallet = tbl_transaction_line.net_per_pallet+$net_per_pcs;";
					//exit($sql);
					sqlError($mysqli, __LINE__, $sql, 1, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
					}

					$sql = "UPDATE tbl_inventory
					SET 
						packing_qty = packing_qty+1,
						location_id = uuid_to_bin('$location_id',true),
						updated_user_id = $cBy,
						updated_at = now()
					WHERE case_tag_no = '$case_tag_no';";
					sqlError($mysqli, __LINE__, $sql, 1, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
					}
				} else {
					// echo ($part_tag_no . '<br>');

					/* $sql = "UPDATE tbl_inventory
					SET 
						location_id = uuid_to_bin('$left_location_id',true),
						updated_user_id = $cBy,
						updated_at = now()
					WHERE case_tag_no = '$case_tag_no';";
					sqlError($mysqli, __LINE__, $sql, 1, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
					} */
				}
			}

			// exit();

			$sql = "INSERT INTO tbl_inventory_line
				( fg_tag_no, part_id, qty, net_per_pallet, certificate_no, coil_lot_no, repack_process, package_type,
				location_id, transaction_line_id, order_id, inventory_id,
				created_at, updated_at, created_user_id, updated_user_id
				)
				SELECT 
				t1.fg_tag_no, t1.part_id, t1.qty, t1.net_per_pallet, t1.certificate_no, t1.coil_lot_no, repack_process, t2.package_type,
				t1.to_location_id, t1.transaction_line_id, t1.order_id, t2.inventory_id,
				NOW(), NOW(), $cBy, $cBy
				FROM tbl_transaction_line t1
				INNER JOIN tbl_inventory t2 ON t1.case_tag_no = t2.case_tag_no
				WHERE transaction_id = uuid_to_bin('$transaction_id',true)
				AND t1.case_tag_no = '$case_tag_no'
				AND t1.status != 'Cancel'
				ON DUPLICATE KEY UPDATE 
				fg_tag_no = VALUES(fg_tag_no),
				part_id = VALUES(part_id), 
				qty = VALUES(qty), 
				net_per_pallet = VALUES(net_per_pallet), 
				certificate_no = VALUES(certificate_no),
				coil_lot_no = VALUES(coil_lot_no),
				repack_process = VALUES(repack_process),
				package_type = VALUES(package_type),
				location_id = VALUES(location_id), 
				transaction_line_id = VALUES(transaction_line_id), 
				inventory_id = VALUES(inventory_id), 
				order_id = VALUES(order_id),
				updated_at = NOW(), 
				updated_user_id = $cBy;";
			// exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			foreach ($part_tag_array as $value) {
				$fg_tag_no = $value['fg_tag_no'];
				$part_tag_no = $value['part_tag_no'];
				$sql = "SELECT bin_to_uuid(t1.transaction_line_id,true) transaction_line_id, t1.fg_tag_no, 
				bin_to_uuid(t2.inventory_line_id,true) inventory_line_id, bin_to_uuid(t2.order_id,true) order_id
				FROM tbl_transaction_line t1
					INNER JOIN tbl_inventory_line t2 ON t1.transaction_line_id = t2.transaction_line_id
				WHERE transaction_id = uuid_to_bin('$transaction_id',true) AND t1.fg_tag_no = '$fg_tag_no';";
				//exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล ' . __LINE__);
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$transaction_line_id = $row['transaction_line_id'];
					$inventory_line_id = $row['inventory_line_id'];
					$order_id = $row['order_id'];

					$sql = "INSERT INTO tbl_transaction_detail 
						( part_tag_no, part_id, qty, net_per_pcs, remark, parent_tag_no, 
						transaction_line_id, from_location_id, to_location_id,
						created_at, updated_at, created_user_id, updated_user_id
						)
						SELECT 
						part_tag_no, part_id, qty, net_per_pcs, remark, '$case_tag_no', 
						uuid_to_bin('$transaction_line_id',true), location_id, uuid_to_bin('$location_id',true),
						NOW(), NOW(), $cBy, $cBy
						FROM tbl_inventory_detail
						WHERE part_tag_no = '$part_tag_no';";
					sqlError($mysqli, __LINE__, $sql, 1, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
					}

					$sql = "UPDATE tbl_inventory_detail
					SET inventory_line_id = uuid_to_bin('$inventory_line_id',true),
						order_id = uuid_to_bin('$order_id',true),
						updated_at = NOW(), 
						updated_user_id = $cBy
					WHERE part_tag_no = '$part_tag_no';";
					sqlError($mysqli, __LINE__, $sql, 1, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
					}

					$sql = "UPDATE tbl_order
					SET repack = 'Pending',
						packing_qty = packing_qty+1,
						updated_at = NOW(), 
						updated_user_id = $cBy
					WHERE order_header_id = uuid_to_bin('$order_header_id',true) AND order_id = uuid_to_bin('$order_id',true);";
					sqlError($mysqli, __LINE__, $sql, 1, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
					}
				}
			}

			// exit();

			$sql = "UPDATE tbl_order_header
			SET delivery_status = 'On process',
				updated_at = NOW(), 
				updated_user_id = $cBy
			WHERE order_no = '$order_no';";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			$sql = "UPDATE tbl_order
			SET repack = 'Pending',
				work_order_no = '$work_order_no',
				updated_at = NOW(), 
				updated_user_id = $cBy
			WHERE order_header_id = uuid_to_bin('$order_header_id',true) AND order_id = uuid_to_bin('$order_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}


			$mysqli->commit();
			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'Repack'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
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
					WHERE document_no = '$document_no' AND transaction_type = 'Packing';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล document_no : ' . $document_no);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$transaction_id = $row['transaction_id'];
				$order_header_id = $row['order_header_id'];
			}

			$sql = "SELECT order_no
			FROM tbl_order_header
			WHERE order_header_id = uuid_to_bin('$order_header_id',true)
				AND (order_status = 'Delivered' OR order_status = 'In-transit');";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('ไม่สามารถแก้ไขได้<br>เนื่องจากทำการออก GTN แล้ว');
			}
			
			$sql = "SELECT part_tag_no
			FROM tbl_order
			WHERE order_header_id = uuid_to_bin('$order_header_id',true)
				AND repack = 'Yes';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('ไม่สามารถแก้ไขได้<br>เนื่องจากคอนเฟิร์ม Repack แล้ว');
			}



			$sql = "UPDATE tbl_transaction 
			SET 
			updated_at = NOW(), 
			updated_user_id = $cBy
			WHERE transaction_id = uuid_to_bin('$transaction_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}


			$sql = "UPDATE tbl_transaction 
				SET transaction_type = 'Temp-Packing',
				editing_at = NOW(),
				editing_user_id = $cBy
				WHERE transaction_id = uuid_to_bin('$transaction_id',true) 
				AND transaction_type = 'Packing';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			$sql = "UPDATE tbl_transaction_line 
			SET status = 'Pending',
				updated_user_id = $cBy,
				updated_at = now()
			WHERE transaction_id = uuid_to_bin('$transaction_id',true) AND status = 'Complete';";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
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
	if ($_SESSION['xxxRole']->{'Repack'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
		$dataParams = array(
			'obj',
			'obj=>transaction_id:s:0:1',
			'obj=>transaction_line_id:s:0:1',
			'obj=>case_tag_no:s:0:1',
			'obj=>order_no:s:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT bin_to_uuid(order_header_id,true) order_header_id FROM tbl_order_header WHERE order_no = '$order_no';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			$order_header_id = $re1->fetch_array(MYSQLI_ASSOC)['order_header_id'];

			/* Transaction */

			$sql = "UPDATE tbl_transaction
			SET editing_at = NOW(), 
				editing_user_id = $cBy
			WHERE transaction_id = uuid_to_bin('$transaction_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}

			$sql = "SELECT bin_to_uuid(transaction_line_id,true) transaction_line_id,
			bin_to_uuid(from_location_id,true) from_location_id
			FROM tbl_transaction_line 
			WHERE bin_to_uuid(transaction_id,true) = '$transaction_id' AND case_tag_no = '$case_tag_no';";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				//exit($sql);
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$transaction_line_id = $row['transaction_line_id'];
				$from_location_id = $row['from_location_id'];

				$sql = "DELETE FROM tbl_transaction_detail
					WHERE transaction_line_id = uuid_to_bin('$transaction_line_id',true);";
				sqlError($mysqli, __LINE__, $sql, 1, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถยกเลิกได้ ' . __LINE__);
				}
			}

			$sql = "SELECT case_tag_no, SUM(qty) qty,
			bin_to_uuid(from_location_id,true) from_location_id
			FROM tbl_transaction_line 
			WHERE bin_to_uuid(transaction_id,true) = '$transaction_id' AND case_tag_no = '$case_tag_no'
			GROUP BY case_tag_no;";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				//exit($sql);
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$from_location_id = $row['from_location_id'];
				$qty = $row['qty'];

				$sql = "UPDATE tbl_inventory
				SET packing_qty = packing_qty-$qty,
					location_id = uuid_to_bin('$from_location_id',true),
					updated_user_id = $cBy,
					updated_at = now()
				WHERE case_tag_no = '$case_tag_no';";
				sqlError($mysqli, __LINE__, $sql, 1, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถยกเลิกได้ ' . __LINE__);
				}
			}


			$sql = "UPDATE tbl_order
			SET repack = 'No',
			work_order_no = NULL,
			updated_at = NOW(),
			updated_user_id = $cBy
			WHERE order_header_id = uuid_to_bin('$order_header_id',true) 
			AND case_tag_no = '$case_tag_no';";
			//exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถยกเลิกได้ ' . __LINE__);
			}

			$sql = "SELECT t1.fg_tag_no, t3.part_tag_no
			FROM tbl_transaction_line t1
			INNER JOIN tbl_inventory_line t2 ON t1.transaction_line_id = t2.transaction_line_id
			INNER JOIN tbl_inventory_detail t3 ON t2.inventory_line_id = t3.inventory_line_id
			WHERE bin_to_uuid(transaction_id,true) = '$transaction_id' 
			AND t1.case_tag_no = '$case_tag_no' AND status != 'Cancel';";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$part_tag_no = $row['part_tag_no'];

					$sql = "UPDATE tbl_inventory_detail
					SET inventory_line_id = NULL,
						order_id = NULL,
						updated_at = NOW(), 
						updated_user_id = $cBy
					WHERE part_tag_no = '$part_tag_no';";
					sqlError($mysqli, __LINE__, $sql, 1, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
					}
				}
			}


			$sql = "SELECT t1.fg_tag_no, t1.qty
			FROM tbl_transaction_line t1
			INNER JOIN tbl_inventory_line t2 ON t1.transaction_line_id = t2.transaction_line_id
			WHERE bin_to_uuid(transaction_id,true) = '$transaction_id' 
			AND t1.case_tag_no = '$case_tag_no' AND status != 'Cancel';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$fg_tag_no = $row['fg_tag_no'];
					$qty = $row['qty'];

					$sql = "DELETE FROM tbl_inventory_line
					WHERE fg_tag_no = '$fg_tag_no';";
					sqlError($mysqli, __LINE__, $sql, 1, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถยกเลิกได้ ' . __LINE__);
					}
				}
			}


			$sql = "DELETE FROM tbl_transaction_line
			WHERE transaction_id = uuid_to_bin('$transaction_id',true)
				AND case_tag_no = '$case_tag_no';";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถยกเลิกได้ ' . __LINE__);
			}

			$sql = "SELECT case_tag_no
			FROM tbl_order
			WHERE order_header_id = uuid_to_bin('$order_header_id',true) AND repack = 'Pending';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				$sql = "UPDATE tbl_order_header
				SET order_status = 'Packing',
				delivery_status = 'Pending',
				updated_at = NOW(),
				updated_user_id = $cBy
				WHERE order_header_id = uuid_to_bin('$order_header_id',true);";
				//exit($sql);
				sqlError($mysqli, __LINE__, $sql, 1, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถยกเลิกได้ ' . __LINE__);
				}
			}

			/* Inventory */

			//exit();


			$mysqli->commit();
			closeDBT($mysqli, 1, 'OK');
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'Repack'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {

		$dataParams = array(
			'obj',
			'obj=>document_no:s:0:1',
			'obj=>document_date:s:0:1',
			'obj=>order_no:s:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {


			$sql = "SELECT bin_to_uuid(order_header_id,true) order_header_id
					FROM tbl_order_header
					WHERE order_no = '$order_no';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			$order_header_id = $re1->fetch_assoc()["order_header_id"];

			$sql = "UPDATE tbl_order_header
				SET order_status = 'Packed',
				delivery_status = 'On process',
				updated_at = NOW(),
				updated_user_id = $cBy
				WHERE order_header_id = uuid_to_bin('$order_header_id',true);";
			//exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถยกเลิกได้ ' . __LINE__);
			}

			$sql = "SELECT bin_to_uuid(order_id,true) order_id
			FROM tbl_order
			WHERE order_header_id = uuid_to_bin('$order_header_id',true) AND repack = 'No';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('ยังเหลือรายการในออเดอร์<br>กรุณาเลือกรายการให้ครบ');
			}


			$sql = "SELECT bin_to_uuid(transaction_id,true) transaction_id 
					FROM tbl_transaction 
					WHERE document_no = '$document_no' AND transaction_type = 'Temp-Packing'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล document_no : ' . $document_no);
			}
			$transaction_id = $re1->fetch_assoc()["transaction_id"];

			$document_no_new = $document_no;
			$gen = false;

			if ((stripos($document_no, "pac")) === FALSE) {
				$sql = "SELECT func_GenRuningNumber('packing',0) as document_no ;";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);
				if ($re1->num_rows === 0) {
					throw new Exception('ไม่พบข้อมูล document no PAC ' . __LINE__);
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
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
				}
			} else {
				$sql = "UPDATE tbl_transaction 
				SET 
				updated_at = NOW(), 
				updated_user_id = $cBy
				WHERE transaction_id = uuid_to_bin('$transaction_id',true);";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
				}
			}

			$sql = "UPDATE tbl_transaction 
				SET transaction_type = 'Packing', 
				document_no = '$document_no_new',
				document_date = '$document_date',
				editing_at = null,
				editing_user_id = null
				WHERE transaction_id = uuid_to_bin('$transaction_id',true) AND transaction_type = 'Temp-Packing';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			$sql = "UPDATE tbl_transaction_line 
				SET status = 'Complete',
					updated_user_id = $cBy,
					updated_at = now()
				WHERE transaction_id = uuid_to_bin('$transaction_id',true) AND status = 'Pending';";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			/* Inventory */

			$sql = "SELECT bin_to_uuid(location_id,true) location_id FROM tbl_location_master WHERE location_area = 'wip' LIMIT 1;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($re1->num_rows === 0) {
				throw new Exception('ไม่พบข้อมูล Location');
			}
			$location_id = $re1->fetch_assoc()["location_id"];


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
