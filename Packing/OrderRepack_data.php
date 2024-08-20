<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'OrderRepack'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'OrderRepack'}[0] == 0) {
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
			'obj=>order_no:s:0:0',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			if ($order_no == '') {

				$sql = "SELECT 
					BIN_TO_UUID(t1.order_header_id, TRUE) AS order_header_id,
					order_no, order_date, delivery_date, supplier_code, t1.repack
				FROM
					tbl_order_header t1
						LEFT JOIN
					tbl_order t2 ON t1.order_header_id = t2.order_header_id
						INNER JOIN
					tbl_supplier_master t3 ON t1.supplier_id = t3.supplier_id
				WHERE
					t1.editing_user_id = $cBy
						AND t1.order_status = 'Pending'
						AND t2.status = 'Pending'
				GROUP BY t1.order_header_id ORDER BY t1.editing_at DESC LIMIT 1;";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				$header = jsonRow($re1, true, 0);

				$body = [];

				if (count($header) > 0) {
					$order_header_id = $header[0]['order_header_id'];


					$sql = "WITH sum_net_by_part AS (
					SELECT part_id, SUM(net_per_pcs) sum_net FROM tbl_order
					WHERE order_header_id = uuid_to_bin('$order_header_id',true) AND status = 'Pending'
					GROUP BY part_id )
					SELECT
						BIN_TO_UUID(order_header_id, TRUE) AS order_header_id,
						BIN_TO_UUID(order_id, TRUE) AS order_id,
						work_order_no, part_tag_no, qty, net_per_pcs, part_no, part_name, 
						if(ROW_NUMBER() OVER (partition by part_no order by part_no, t1.part_tag_no)=1,t3.sum_net,'') sum_net
					FROM
						tbl_order t1
							inner join tbl_part_master t2 on t1.part_id = t2.part_id
							inner join sum_net_by_part t3 ON t1.part_id = t3.part_id
					WHERE
						order_header_id = uuid_to_bin('$order_header_id',true)
							AND t1.status = 'Pending'
					order by work_order_no, part_tag_no;";
					//exit($sql);
					$re1 = sqlError($mysqli, __LINE__, $sql, 1);

					$body = jsonRow($re1, true, 0);
				}
			} else {
				$sql = "SELECT 
					BIN_TO_UUID(t1.order_header_id, TRUE) AS order_header_id,
					order_no, order_date, delivery_date, supplier_code, t1.repack
				FROM
					tbl_order_header t1
						LEFT JOIN
					tbl_order t2 ON t1.order_header_id = t2.order_header_id
						INNER JOIN
					tbl_supplier_master t3 ON t1.supplier_id = t3.supplier_id
				WHERE
					order_no = '$order_no'
						AND t1.order_status = 'Pending'
						AND t2.status = 'Pending'
				GROUP BY t1.order_header_id ORDER BY t1.editing_at DESC LIMIT 1;";
				//exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				$header = jsonRow($re1, true, 0);

				$body = [];

				if (count($header) > 0) {
					$order_header_id = $header[0]['order_header_id'];

					$sql = "WITH sum_net_by_part AS (
					SELECT part_id, SUM(net_per_pcs) sum_net FROM tbl_order
					WHERE order_header_id = uuid_to_bin('$order_header_id',true) AND status = 'Pending'
					GROUP BY part_id )
					SELECT
						BIN_TO_UUID(order_header_id, TRUE) AS order_header_id,
						BIN_TO_UUID(order_id, TRUE) AS order_id,
						work_order_no, part_tag_no, qty, net_per_pcs, part_no, part_name, 
						if(ROW_NUMBER() OVER (partition by part_no order by part_no, t1.part_tag_no)=1,t3.sum_net,'') sum_net
					FROM
						tbl_order t1
							inner join tbl_part_master t2 on t1.part_id = t2.part_id
							inner join sum_net_by_part t3 ON t1.part_id = t3.part_id
					WHERE
						order_header_id = uuid_to_bin('$order_header_id',true)
							AND t1.status = 'Pending'
					order by work_order_no, part_tag_no;";
					// exit($sql);
					$re1 = sqlError($mysqli, __LINE__, $sql, 1);

					$body = jsonRow($re1, true, 0);
				}
			}

			$returnData = ['header' => $header, 'body' => $body];

			$mysqli->commit();
			closeDBT($mysqli, 1, $returnData);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 2) {
	} else if ($type == 3) {

		$dataParams = array(
			'obj',
			'obj=>supplier_code:s:0:1',
			'obj=>part_no:s:0:0',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$supplier_id = getSupplierID($mysqli, $supplier_code);

			if ($part_no != '') {
				$part_id = getPartID($mysqli, $part_no);
				$where = "AND t2.part_id = uuid_to_bin('$part_id',true)";
			} else {
				$where = '';
			}

			/* $sql = "SELECT 
				bin_to_uuid(t1.inventory_id,true) inventory_id, case_tag_no, pallet_no,
				bin_to_uuid(t1.part_id,true) part_id, part_no, part_name, 
				tt1.part_tag_no, tt1.qty, net_per_pcs, tt1.repack_process, 
				bin_to_uuid(t1.location_id,true) location_id, t4.location_area, t4.location_code
			FROM
				tbl_inventory t1
					INNER JOIN tbl_inventory_detail tt1 ON t1.inventory_id = tt1.inventory_id
					INNER JOIN tbl_part_master t2 ON t1.part_id = t2.part_id
					INNER JOIN tbl_location_master t4 ON t1.location_id = t4.location_id
			WHERE 
			t2.supplier_id = uuid_to_bin('$supplier_id',true) 
				-- AND t4.location_area = 'truck-sim'
				AND tt1.repack_process = 'No'
			ORDER BY case_tag_no ASC;"; */

			$sql = "SELECT 
				bin_to_uuid(t1.inventory_id,true) inventory_id, case_tag_no, pallet_no,
				bin_to_uuid(t1.part_id,true) part_id, part_no, part_name, 
				case_tag_no, SUM(tt1.qty) qty, SUM(net_per_pcs) net_per_pallet, tt1.repack_process, 
				bin_to_uuid(t1.location_id,true) location_id, t4.location_area, t4.location_code,
				t2.package_type, t5.supplier_code
			FROM
				tbl_inventory t1
					INNER JOIN tbl_inventory_detail tt1 ON t1.inventory_id = tt1.inventory_id
					INNER JOIN tbl_part_master t2 ON t1.part_id = t2.part_id
					INNER JOIN tbl_location_master t4 ON t1.location_id = t4.location_id
					INNER JOIN tbl_supplier_master t5 ON t2.supplier_id = t5.supplier_id
			WHERE 
			t2.supplier_id = uuid_to_bin('$supplier_id',true)
				AND tt1.repack_process = 'No'
			GROUP BY t1.case_tag_no
			ORDER BY case_tag_no ASC;";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$body = jsonRow($re1, true, 0);

			$header = [];

			if (count($body) > 0) {
				$returnData = ['body' => $body];
			} else {
				$returnData = ['body' => ''];
			}


			$mysqli->commit();
			closeDBT($mysqli, 1, $returnData);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 4) {

		$dataParams = array(
			'obj',
			'obj=>case_tag_no:s:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				bin_to_uuid(t1.inventory_id,true) inventory_id, case_tag_no, pallet_no,
				bin_to_uuid(t1.part_id,true) part_id, part_no, part_name, 
				tt1.part_tag_no, tt1.qty, net_per_pcs, tt1.repack_process, 
				bin_to_uuid(t1.location_id,true) location_id, t4.location_area, t4.location_code
			FROM
				tbl_inventory t1
					INNER JOIN tbl_inventory_detail tt1 ON t1.inventory_id = tt1.inventory_id
					INNER JOIN tbl_part_master t2 ON t1.part_id = t2.part_id
					INNER JOIN tbl_location_master t4 ON t1.location_id = t4.location_id
			WHERE 
				t1.case_tag_no = '$case_tag_no'
				AND tt1.repack_process = 'No'
			ORDER BY case_tag_no ASC;";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$body = jsonRow($re1, true, 0);

			$header = [];

			if (count($body) > 0) {
				$returnData = ['body' => $body];
			} else {
				$returnData = ['body' => ''];
			}


			$mysqli->commit();
			closeDBT($mysqli, 1, $returnData);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'OrderRepack'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {

		$dataParams = array(
			'obj',
			'obj=>order_no:s:0:0',
			'obj=>order_date:s:0:1',
			'obj=>delivery_date:s:0:1',
			'obj=>supplier_code:s:0:1',
			'obj=>repack:s:0:1',
			'obj=>case_tag_no:s:0:1',
			'obj=>inventory_id:s:0:0',
			'obj=>part_no:s:0:1',
			'obj=>part_id:s:0:1',
			'obj=>pallet_no:s:0:1',
			'obj=>qty:i:0:1',
			'obj=>net_per_pallet:f:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$supplier_id = getSupplierID($mysqli, $supplier_code);

			/* Order Header */

			$sql = "SELECT bin_to_uuid(order_header_id,true) order_header_id, order_no FROM tbl_order_header WHERE order_no = '$order_no'; ";

			$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($re1->num_rows === 0) {
				$sql = "SELECT func_GenRuningNumber('temp',0) as order_no ;";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);
				if ($re1->num_rows === 0) {
					throw new Exception('ไม่พบข้อมูล Order No TEMP');
				}
				$order_no = $re1->fetch_assoc()["order_no"];

				$sql = "INSERT INTO tbl_order_header 
				(order_no, order_date, delivery_date, supplier_id, repack, created_at, created_user_id) 
				VALUES
				('$order_no','$order_date', '$delivery_date', uuid_to_bin('$supplier_id',true), '$repack', now(), $cBy);";
				// exit($sql);
				sqlError($mysqli, __LINE__, $sql, 1, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
				}
			}

			$sql = "SELECT bin_to_uuid(order_header_id,true) order_header_id, order_no FROM tbl_order_header WHERE order_no = '$order_no';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			$order_header_id = $re1->fetch_array(MYSQLI_ASSOC)['order_header_id'];


			$sql = "UPDATE tbl_order_header
			SET order_date = '$order_date',
				delivery_date = '$delivery_date',
				repack = '$repack',
				editing_at = NOW(), 
				editing_user_id = $cBy
			WHERE order_header_id = uuid_to_bin('$order_header_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			/* $sql = "SELECT part_tag_no FROM tbl_order WHERE part_tag_no = '$part_tag_no' AND status != 'Cancel';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('Part Tag นี้มีการเพิ่มข้อมูลแล้ว ' . __LINE__);
			} */

			$sql = "SELECT work_order_no
			FROM tbl_order
			WHERE order_header_id = uuid_to_bin('$order_header_id',true) AND status != 'Cancel'
			AND part_id = uuid_to_bin('$part_id',true)
			AND work_order_no IS NOT NULL LIMIT 1;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$work_order_no = $row['work_order_no'];
				}
			} else {
				$sql = "SELECT func_GenRuningNumber('work_order',0) as work_order_no ;";
				$result = sqlError($mysqli, __LINE__, $sql, 1, 1);
				if ($result->num_rows === 0) {
					throw new Exception('ไม่พบข้อมูล Work Order ' . __LINE__);
				}
				$work_order_no = $result->fetch_assoc()["work_order_no"];
			}

			$sql = "INSERT INTO tbl_order 
			( part_id, pallet_no, case_tag_no, part_tag_no, qty, net_per_pcs, work_order_no, order_header_id, created_at, updated_at, created_user_id, updated_user_id )
			SELECT t1.part_id, pallet_no, case_tag_no, part_tag_no, t1.qty, net_per_pcs, '$work_order_no', uuid_to_bin('$order_header_id',true), 
			now(), now(), $cBy, $cBy
			FROM tbl_inventory_detail t1 
			INNER JOIN tbl_inventory t2 ON t1.inventory_id = t2.inventory_id
			WHERE case_tag_no = '$case_tag_no' AND order_id IS NULL;";
			// exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			/* $sql = "SELECT bin_to_uuid(order_id,true) order_id 
			FROM tbl_order 
			WHERE order_header_id = uuid_to_bin('$order_header_id',true) 
			AND part_tag_no = '$part_tag_no' AND status != 'Cancel';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			$order_id = $re1->fetch_array(MYSQLI_ASSOC)['order_id']; */


			$sql = "UPDATE tbl_inventory_detail t1,
			( SELECT part_tag_no, order_id FROM tbl_order WHERE order_header_id = uuid_to_bin('$order_header_id',true) 
			AND case_tag_no = '$case_tag_no' AND status != 'Cancel') t2
			SET 
				t1.order_id = t2.order_id,
				t1.repack_process = 'Pending',
				t1.updated_at = NOW(),
				t1.updated_user_id = $cBy
			WHERE t1.part_tag_no = t2.part_tag_no;";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}


			$sql = "SELECT SUM(net_per_pcs) sum_net FROM tbl_order 
			WHERE order_header_id = uuid_to_bin('$order_header_id',true) AND status != 'Cancel';";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$sum_net = $row['sum_net'];
			}

			if ($sum_net > 7000) {
				throw new Exception('ไม่สามารถเพิ่มได้<br>Net Weight จะมากกว่า 7 ตัน');
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
			'obj=>order_no:s:0:0',
			'obj=>order_date:s:0:1',
			'obj=>delivery_date:s:0:1',
			'obj=>supplier_code:s:0:1',
			'obj=>repack:s:0:1',
			'obj=>part_tag_no:s:0:1',
			'obj=>case_tag_no:s:0:1',
			'obj=>inventory_id:s:0:0',
			'obj=>part_no:s:0:1',
			'obj=>part_id:s:0:1',
			'obj=>pallet_no:s:0:1',
			'obj=>qty:i:0:1',
			'obj=>net_per_pcs:f:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$supplier_id = getSupplierID($mysqli, $supplier_code);

			/* Order Header */

			$sql = "SELECT bin_to_uuid(order_header_id,true) order_header_id, order_no FROM tbl_order_header WHERE order_no = '$order_no'; ";

			$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($re1->num_rows === 0) {
				$sql = "SELECT func_GenRuningNumber('temp',0) as order_no ;";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);
				if ($re1->num_rows === 0) {
					throw new Exception('ไม่พบข้อมูล Order No TEMP');
				}
				$order_no = $re1->fetch_assoc()["order_no"];

				$sql = "INSERT INTO tbl_order_header 
				(order_no, order_date, delivery_date, supplier_id, repack, created_at, created_user_id) 
				VALUES
				('$order_no','$order_date', '$delivery_date', uuid_to_bin('$supplier_id',true), '$repack', now(), $cBy);";
				// exit($sql);
				sqlError($mysqli, __LINE__, $sql, 1, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
				}
			}

			$sql = "SELECT bin_to_uuid(order_header_id,true) order_header_id, order_no FROM tbl_order_header WHERE order_no = '$order_no';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			$order_header_id = $re1->fetch_array(MYSQLI_ASSOC)['order_header_id'];


			$sql = "UPDATE tbl_order_header
			SET order_date = '$order_date',
				delivery_date = '$delivery_date',
				repack = '$repack',
				editing_at = NOW(), 
				editing_user_id = $cBy
			WHERE order_header_id = uuid_to_bin('$order_header_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}


			$sql = "SELECT part_tag_no FROM tbl_order WHERE part_tag_no = '$part_tag_no' AND status != 'Cancel';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('Part Tag นี้มีการเพิ่มข้อมูลแล้ว ' . __LINE__);
			}

			$sql = "SELECT work_order_no
			FROM tbl_order
			WHERE order_header_id = uuid_to_bin('$order_header_id',true) AND status != 'Cancel'
			AND part_id = uuid_to_bin('$part_id',true)
			AND work_order_no IS NOT NULL LIMIT 1;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$work_order_no = $row['work_order_no'];
				}
			} else {
				$sql = "SELECT func_GenRuningNumber('work_order',0) as work_order_no ;";
				$result = sqlError($mysqli, __LINE__, $sql, 1, 1);
				if ($result->num_rows === 0) {
					throw new Exception('ไม่พบข้อมูล Work Order ' . __LINE__);
				}
				$work_order_no = $result->fetch_assoc()["work_order_no"];
			}

			$sql = "INSERT INTO tbl_order 
			( part_id, pallet_no , case_tag_no, part_tag_no, qty, net_per_pcs, work_order_no, order_header_id, created_at, updated_at, created_user_id, updated_user_id )
			VALUES
			( uuid_to_bin('$part_id',true), '$pallet_no' , '$case_tag_no', '$part_tag_no', $qty, '$net_per_pcs', '$work_order_no', uuid_to_bin('$order_header_id',true), 
			now(), now(), $cBy, $cBy );";
			// exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			$sql = "SELECT bin_to_uuid(order_id,true) order_id 
			FROM tbl_order 
			WHERE order_header_id = uuid_to_bin('$order_header_id',true) 
			AND part_tag_no = '$part_tag_no' AND status != 'Cancel';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			$order_id = $re1->fetch_array(MYSQLI_ASSOC)['order_id'];

			$sql = "UPDATE tbl_inventory_detail
			SET 
				order_id = uuid_to_bin('$order_id',true),
				repack_process = 'Pending',
				updated_at = NOW(),
				updated_user_id = $cBy
			WHERE part_tag_no = '$part_tag_no';";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}


			$sql = "SELECT SUM(net_per_pcs) sum_net FROM tbl_order 
			WHERE order_header_id = uuid_to_bin('$order_header_id',true) AND status != 'Cancel';";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$sum_net = $row['sum_net'];
			}

			if ($sum_net > 7000) {
				throw new Exception('ไม่สามารถเพิ่มได้<br>Net Weight จะมากกว่า 7 ตัน');
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
	if ($_SESSION['xxxRole']->{'OrderRepack'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {

		$dataParams = array(
			'obj',
			'obj=>order_no:s:0:1',
			'obj=>order_date:s:0:1',
			'obj=>delivery_date:s:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT order_no
					FROM tbl_order_header 
					WHERE order_no = '$order_no' AND delivery_status = 'Pending' AND (order_status = 'Pending' OR order_status = 'Packing');";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่สามารถแก้ไขได้<br>ออเดอร์อยู่ในขั้นตอนดำเนินการ');
			}

			$sql = "SELECT bin_to_uuid(order_header_id,true) order_header_id 
					FROM tbl_order_header 
					WHERE order_no = '$order_no';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล order_no : ' . $order_no);
			}
			$order_header_id = $re1->fetch_assoc()["order_header_id"];


			$sql = "UPDATE tbl_order_header 
			SET 
			updated_at = NOW(), 
			updated_user_id = $cBy
			WHERE order_header_id = uuid_to_bin('$order_header_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถแก้ไขข้อมูลได้' . __LINE__);
			}


			$sql = "UPDATE tbl_order_header 
				SET order_status = 'Pending',
				editing_at = NOW(),
				editing_user_id = $cBy
				WHERE order_header_id = uuid_to_bin('$order_header_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถแก้ไขข้อมูลได้' . __LINE__);
			}

			$sql = "UPDATE tbl_order
			SET status = 'Pending',
				updated_user_id = $cBy,
				updated_at = now()
			WHERE order_header_id = uuid_to_bin('$order_header_id',true) AND status = 'Complete';";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถแก้ไขข้อมูลได้ ' . __LINE__);
			}


			$mysqli->commit();
			closeDBT($mysqli, 1, $order_no);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'OrderRepack'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {

		$dataParams = array(
			'obj',
			'obj=>order_header_id:s:0:1',
			'obj=>order_id:s:0:1',
			'obj=>part_tag_no:s:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT bin_to_uuid(order_header_id,true) order_header_id, repack
					FROM tbl_order_header 
					WHERE order_header_id = uuid_to_bin('$order_header_id',true);";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล order_no : ' . $order_no);
			}
			// $repack = $re1->fetch_assoc()["repack"];

			$sql = "UPDATE tbl_inventory_detail
			SET order_id = NULL,
				repack_process = 'No',
				updated_at = NOW(),
				updated_user_id = $cBy
			WHERE part_tag_no = '$part_tag_no';";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			$sql = "DELETE FROM tbl_order
			WHERE order_header_id = uuid_to_bin('$order_header_id',true) AND order_id = uuid_to_bin('$order_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถยกเลิกข้อมูลได้ ' . __LINE__);
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
	if ($_SESSION['xxxRole']->{'OrderRepack'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {

		$dataParams = array(
			'obj',
			'obj=>order_no:s:0:0',
			'obj=>order_date:s:0:1',
			'obj=>delivery_date:s:0:1',
			'obj=>supplier_code:s:0:1',
			'obj=>repack:s:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT bin_to_uuid(order_header_id,true) order_header_id, dos_no
					FROM tbl_order_header
					WHERE order_no = '$order_no' AND order_status = 'Pending';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล order_no : ' . $order_no);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$order_header_id = $row['order_header_id'];
				$dos_no = $row['dos_no'];
			}

			$order_no_new = $order_no;
			$gen = false;

			if ((stripos($order_no, "hmt")) === FALSE) {
				$sql = "SELECT func_GenRuningNumber('sale_order',0) as order_no ;";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);
				if ($re1->num_rows === 0) {
					throw new Exception('ไม่พบข้อมูล Sale Order No.' . __LINE__);
				}
				$order_no_new = $re1->fetch_assoc()["order_no"];
				// $date = date_create($order_date);
				// $order_date = date_format($date, "Ymd");
				$order_no_new = 'HMT-' . $order_no_new;
				$gen = true;

				$sql = "UPDATE tbl_order_header 
				SET 
				created_at = NOW(), 
				created_user_id = $cBy
				WHERE order_header_id = uuid_to_bin('$order_header_id',true);";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . $sql);
				}
			} else {
				$sql = "UPDATE tbl_order_header 
				SET 
				updated_at = NOW(), 
				updated_user_id = $cBy
				WHERE order_header_id = uuid_to_bin('$order_header_id',true);";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . $sql);
				}
			}


			$sql = "UPDATE tbl_order_header
			SET order_status = 'Packing', 
			order_no = '$order_no_new',
			order_date = '$order_date',
			delivery_date = '$delivery_date'
			WHERE order_header_id = uuid_to_bin('$order_header_id',true) AND order_status = 'Pending';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . $sql);
			}

			/* if ($repack == 'Yes') {
				$sql = "UPDATE tbl_order_header
				SET order_status = 'Packing', 
				order_no = '$order_no_new',
				order_date = '$order_date',
				delivery_date = '$delivery_date'
				WHERE order_header_id = uuid_to_bin('$order_header_id',true) AND order_status = 'Pending';";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . $sql);
				}
			} else {

				$sql = "SELECT case_tag_no, bin_to_uuid(order_id,true) order_id
					FROM tbl_order
					WHERE order_header_id = uuid_to_bin('$order_header_id',true) AND status != 'Cancel';";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล ' . __LINE__);
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$case_tag_no = $row['case_tag_no'];
					$order_id = $row['order_id'];

					$sql = "UPDATE tbl_inventory_detail t1,
					(SELECT inventory_id
					FROM tbl_inventory
					WHERE case_tag_no = '$case_tag_no') t2
					SET t1.order_id = uuid_to_bin('$order_id',true),
						t1.updated_at = NOW(),
						t1.updated_user_id = $cBy
					WHERE t1.inventory_id = t2.inventory_id;";
					sqlError($mysqli, __LINE__, $sql, 1, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
					}
				}


				$sql = "UPDATE tbl_order_header
				SET order_status = 'Picking', 
				order_no = '$order_no_new',
				order_date = '$order_date',
				delivery_date = '$delivery_date'
				WHERE order_header_id = uuid_to_bin('$order_header_id',true);";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . $sql);
				}

				if ((stripos($dos_no, "dos")) === FALSE) {
					$sql = "SELECT func_GenRuningNumber('delivery_order',0) as dos_no ;";
					$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);
					if ($re1->num_rows === 0) {
						throw new Exception('ไม่พบข้อมูล DOS No.' . __LINE__);
					}
					$dos_no = $re1->fetch_assoc()["dos_no"];
					$gen = true;

					$sql = "UPDATE tbl_order_header
					SET
					dos_no = '$dos_no',
					document_date = NOW(),
					updated_user_id = $cBy,
					updated_at = now()
					WHERE order_header_id = uuid_to_bin('$order_header_id',true);";
					sqlError($mysqli, __LINE__, $sql, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . $sql);
					}
				}
			} */

			$sql = "UPDATE tbl_order
			SET status = 'Complete',
				updated_user_id = $cBy,
				updated_at = now()
			WHERE order_header_id = uuid_to_bin('$order_header_id',true) AND status = 'Pending';";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}

			$order_no = $order_no_new;

			$data = ['order_no' => $order_no, 'dos_no' => $dos_no];
			$mysqli->commit();
			closeDBT($mysqli, 1, $data);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else closeDBT($mysqli, 2, 'TYPE ERROR');

$mysqli->close();
exit();
