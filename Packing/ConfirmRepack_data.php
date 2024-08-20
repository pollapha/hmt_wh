<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'ConfirmRepack'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'ConfirmRepack'}[0] == 0) {
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
			'obj=>work_order_no:s:0:0',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));
		$mysqli->autocommit(FALSE);
		try {

			$body = [];

			$sql = "WITH a AS (
			SELECT 
				t2.work_order_no, t2.fg_tag_no, t2.case_tag_no, SUM(t2.qty) total_qty, SUM(t2.net_per_pcs) total_net
			FROM 
				tbl_order t2
			WHERE t2.work_order_no = '$work_order_no' AND t2.status = 'Complete' AND t2.repack != 'No'
			GROUP BY t2.work_order_no, t2.fg_tag_no )
			SELECT 
				row_number() over(partition by t2.fg_tag_no order by t1.part_tag_no, t2.fg_tag_no) row_num,
				t3.order_no, t3.order_status, t3.delivery_status, t2.repack, t2.steel_qty,
				t2.work_order_no, t2.fg_tag_no, 
				if(row_number() over(partition by t2.fg_tag_no order by t1.part_tag_no, t2.fg_tag_no)=1,a.total_qty,'') total_qty,
    			if(row_number() over(partition by t2.fg_tag_no order by t1.part_tag_no, t2.fg_tag_no)=1,a.total_net,'') total_net,
				t2.case_tag_no, t2.qty, t2.net_per_pcs, 
				if(isnull(t2.package_no), '', t2.package_no) package_no, if(t2.package_type = '', t4.package_type, t2.package_type) package_type,
				t1.part_tag_no, substring_index(substring_index(t1.part_tag_no, '-', 2), '-', -1) part_tag_no_num, 
				t1.repack_process part_tag_repack,
				part_no, part_name
			FROM 
				tbl_inventory_detail t1
				LEFT JOIN tbl_order t2 ON t1.part_tag_no = t2.part_tag_no
				LEFT JOIN tbl_order_header t3 ON t2.order_header_id = t3.order_header_id
				LEFT JOIN tbl_part_master t4 ON t1.part_id = t4.part_id
				LEFT JOIN a ON t2.fg_tag_no = a.fg_tag_no
			WHERE t2.work_order_no = '$work_order_no' AND t2.status = 'Complete' AND t2.repack != 'No';";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$body = jsonRow($re1, true, 0);

			if (count($body) > 0) {
				$returnData = ['body' => $body];
			} else {
				$returnData = ['body' => ''];
			}

			//$returnData = ['header' => $header, 'body' => $body];
			closeDBT($mysqli, 1, $returnData);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 2) {

		$dataParams = array(
			'obj',
			'obj=>package_no:s:0:0',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT
				package_type
			FROM
				tbl_package_master
			WHERE
				package_code = '$package_no';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล Package No.');
			}
			$package_type = $re1->fetch_array(MYSQLI_ASSOC)['package_type'];


			$sql = "SELECT
				package_code
			FROM
				tbl_package_master
			WHERE
				package_code = '$package_no'
					AND delivery_status = 'Out';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('Package No. ยังมีสถานะเป็น Out <br> กรุณาสแกนรับเข้าในเมนู 7.1');
			}


			/* $sql = "SELECT
				t1.package_no, delivery_qty
			FROM
				tbl_inventory_line t1
			WHERE
				t1.package_no = '$package_no';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('Package No. มีการใช้งานอยู่');
			} */


			$mysqli->commit();
			closeDBT($mysqli, 1, $package_type);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 3) {

		$dataParams = array(
			'obj',
			'obj=>work_order_no:s:0:0',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT
				work_order_no
			FROM
				tbl_order
			WHERE
				work_order_no = '$work_order_no';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล Work Order No.');
			}

			$sql = "SELECT
				work_order_no
			FROM
				tbl_order
			WHERE
				work_order_no = '$work_order_no' AND pick_check = 'No';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('Work Order No.นี้ยังเช็คงานใน<br>เมนู 4.3 Check pick-up ไม่ครบ');
			}

			$sql = "SELECT
				work_order_no
			FROM
				tbl_order t1
					INNER JOIN tbl_order_header t2 ON t1.order_header_id = t2.order_header_id
					INNER JOIN tbl_supplier_master t3 ON t2.supplier_id = t3.supplier_id
			WHERE
				work_order_no = '$work_order_no' AND t3.supplier_code = 'HMTH';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				$data = 'HMTH';
			} else {
				$data = 'NKAPM';
			}

			$mysqli->commit();
			closeDBT($mysqli, 1, $data);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'ConfirmRepack'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {

		$dataParams = array(
			'obj',
			'obj=>work_order_no:s:0:1',
			'obj=>package_no:s:0:0',
			'obj=>steel_qty:s:0:0',
			'obj=>part_tag_no:s:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {


			$sql = "SELECT
				bin_to_uuid(order_header_id,true) order_header_id, bin_to_uuid(order_id,true) order_id
			FROM
				tbl_order
			WHERE
				work_order_no = '$work_order_no';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$order_header_id = $row['order_header_id'];
			}

			$sql = "SELECT
				work_order_no
			FROM
				tbl_order t1
					INNER JOIN tbl_order_header t2 ON t1.order_header_id = t2.order_header_id
					INNER JOIN tbl_supplier_master t3 ON t2.supplier_id = t3.supplier_id
			WHERE
				work_order_no = '$work_order_no' AND t3.supplier_code = 'HMTH';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				//HMTH

				$sql = "SELECT
					package_code
				FROM
					tbl_package_master
				WHERE
					package_code = '$package_no';";
				//exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล Package No.');
				}

				$sql = "SELECT
					package_code
				FROM
					tbl_package_master
				WHERE
					package_code = '$package_no'
						AND delivery_status = 'Out';";
				//exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows > 0) {
					throw new Exception('Package No. ยังมีสถานะเป็น Out <br> กรุณาสแกนรับเข้าในเมนู 7.1');
				}

				$sql = "SELECT
					part_tag_no
				FROM
					tbl_inventory_detail
				WHERE
					part_tag_no = '$part_tag_no';";
				//exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล Part tag');
				}


				$sql = "SELECT
					part_tag_no, bin_to_uuid(inventory_line_id,true) inventory_line_id
				FROM 
					tbl_inventory_detail
				WHERE
					part_tag_no = '$part_tag_no'
					AND repack_process = 'Yes';";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows > 0) {
					throw new Exception('Part tag นี้<br>สแกนไปเรียบร้อยแล้ว');
				}
				/* while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
			$inventory_line_id = $row['inventory_line_id'];
		} */


				$sql = "SELECT
					fg_tag_no, bin_to_uuid(part_id,true) part_id, net_per_pcs
				FROM 
					tbl_order
				WHERE
					part_tag_no = '$part_tag_no';";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล' . __LINE__);
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$fg_tag_no = $row['fg_tag_no'];
					$part_id = $row['part_id'];
					$net_per_pcs = $row['net_per_pcs'];
				}

				$sql = "SELECT
					bin_to_uuid(supplier_id,true) supplier_id
				FROM 
					tbl_part_master
				WHERE
					part_id = uuid_to_bin('$part_id',true);";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล' . __LINE__);
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$supplier_id = $row['supplier_id'];
				}

				$sql = "SELECT
					supplier_code
				FROM 
					tbl_supplier_master
				WHERE
					supplier_id = uuid_to_bin('$supplier_id',true);";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล' . __LINE__);
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$supplier_code = $row['supplier_code'];
				}


				$sql = "SELECT
					package_no
				FROM
					tbl_order
				WHERE
					fg_tag_no = '$fg_tag_no'
					AND package_no IS NOT NULL;";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows > 0) {
					while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
						$package_no_current = $row['package_no'];

						if ($package_no_current != $package_no) {
							throw new Exception('FG tag นี้<br>อยู่ใน Package No : ' . $package_no_current);
						}
					}
				}

				$explode = explode('-', $part_tag_no);
				$case_tag_no = $explode[0];

				$package_type = getPackageType($mysqli, $package_no);
				// $package_type_part = getPackageTypeByPart($mysqli, $part_id);

				// if ($package_type != $package_type_part) {
				// 	throw new Exception('Package Type ไม่ตรงกับใน Part Master' . __LINE__);
				// }

				$sql = "UPDATE tbl_order
				SET
				repack = 'Yes',
				updated_at = NOW(), 
				updated_user_id = $cBy
				WHERE part_tag_no = '$part_tag_no';";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
				}

				$sql = "UPDATE tbl_order
				SET
				package_no = '$package_no',
				package_type = '$package_type',
				steel_qty = $steel_qty,
				updated_at = NOW(), 
				updated_user_id = $cBy
				WHERE fg_tag_no = '$fg_tag_no';";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
				}


				$sql = "SELECT
					fg_tag_no fg_tag, package_no, SUM(net_per_pcs) net_pallet
				FROM
					tbl_order
				WHERE
					order_header_id = uuid_to_bin('$order_header_id',true)
						AND package_no = '$package_no';";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows > 0) {
					while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
						$fg_tag = $row['fg_tag'];
						$net_pallet = $row['net_pallet'];

						if ($net_pallet > 1500) {
							throw new Exception('Package No : ' . $package_no . '<br> ผลรวม Net Weight มากกว่า 1500');
						}
					}
				}

				$sum_net = 0;
				$sql = "SELECT
					fg_tag_no fg_tag, package_no, net_per_pcs
				FROM
					tbl_order
				WHERE
					order_header_id = uuid_to_bin('$order_header_id',true)
						AND package_no = '$package_no';";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 1) {
					while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
						$fg_tag = $row['fg_tag'];

						if ($fg_tag_no != $fg_tag) {
							throw new Exception('Package No : ' . $package_no . '<br>ไม่ใช่ FG Tag เดียวกัน');
						}
					}
				}

				$sql = "UPDATE tbl_inventory_detail
				SET
				repack_process = 'Yes',
				updated_at = NOW(), 
				updated_user_id = $cBy
				WHERE part_tag_no = '$part_tag_no';";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
				}


				$sql = "SELECT
					fg_tag_no
				FROM
					tbl_order
				WHERE
					fg_tag_no = '$fg_tag_no'
					AND repack = 'Pending';";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows > 0) {
					$data = ['package_type' => $package_type, 'supplier_code' => $supplier_code, 'fg_tag_no' => ''];
				} else {
					$data = ['package_type' => $package_type, 'supplier_code' => $supplier_code, 'fg_tag_no' => $fg_tag_no];
				}
			} else {

				//NKAPM

				$sql = "SELECT
					part_tag_no
				FROM
					tbl_inventory_detail
				WHERE
					part_tag_no = '$part_tag_no';";
				//exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล Part tag');
				}


				$sql = "SELECT
					part_tag_no, bin_to_uuid(inventory_line_id,true) inventory_line_id
				FROM 
					tbl_inventory_detail
				WHERE
					part_tag_no = '$part_tag_no'
					AND repack_process = 'Yes';";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows > 0) {
					throw new Exception('Part tag นี้<br>สแกนไปเรียบร้อยแล้ว');
				}


				$sql = "SELECT
					fg_tag_no, bin_to_uuid(part_id,true) part_id, net_per_pcs
				FROM 
					tbl_order
				WHERE
					part_tag_no = '$part_tag_no';";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล' . __LINE__);
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$fg_tag_no = $row['fg_tag_no'];
					$part_id = $row['part_id'];
					$net_per_pcs = $row['net_per_pcs'];
				}

				$sql = "SELECT
					bin_to_uuid(supplier_id,true) supplier_id
				FROM 
					tbl_part_master
				WHERE
					part_id = uuid_to_bin('$part_id',true);";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล' . __LINE__);
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$supplier_id = $row['supplier_id'];
				}

				$sql = "SELECT
					supplier_code
				FROM 
					tbl_supplier_master
				WHERE
					supplier_id = uuid_to_bin('$supplier_id',true);";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล' . __LINE__);
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$supplier_code = $row['supplier_code'];
				}



				$explode = explode('-', $part_tag_no);
				$case_tag_no = $explode[0];


				$sql = "UPDATE tbl_order
				SET
				steel_qty = 0,
				repack = 'Yes',
				updated_at = NOW(), 
				updated_user_id = $cBy
				WHERE part_tag_no = '$part_tag_no';";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
				}

				/* $sql = "UPDATE tbl_order
				SET
				steel_qty = 0,
				updated_at = NOW(), 
				updated_user_id = $cBy
				WHERE fg_tag_no = '$fg_tag_no';";
				// exit($sql);
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
				} */


				$sql = "UPDATE tbl_inventory_detail
				SET
				repack_process = 'Yes',
				updated_at = NOW(), 
				updated_user_id = $cBy
				WHERE part_tag_no = '$part_tag_no';";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
				}


				$sql = "SELECT
					fg_tag_no
				FROM
					tbl_order
				WHERE
					fg_tag_no = '$fg_tag_no'
					AND repack = 'Pending';";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows > 0) {
					$data = ['package_type' => '', 'supplier_code' => $supplier_code, 'fg_tag_no' => ''];
				} else {
					$data = ['package_type' => '', 'supplier_code' => $supplier_code, 'fg_tag_no' => $fg_tag_no];
				}
			}



			$mysqli->commit();
			closeDBT($mysqli, 1, $data);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 12) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'ConfirmRepack'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {

		$dataParams = array(
			'obj',
			'obj=>work_order_no:s:0:1',
			'obj=>package_no:s:0:0',
			'obj=>steel_qty:s:0:0',
			'obj=>part_tag_no:s:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT
				bin_to_uuid(order_header_id,true) order_header_id, bin_to_uuid(order_id,true) order_id
			FROM
				tbl_order
			WHERE
				work_order_no = '$work_order_no';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$order_header_id = $row['order_header_id'];
			}

			$sql = "SELECT
				work_order_no
			FROM
				tbl_order t1
					INNER JOIN tbl_order_header t2 ON t1.order_header_id = t2.order_header_id
					INNER JOIN tbl_supplier_master t3 ON t2.supplier_id = t3.supplier_id
			WHERE
				work_order_no = '$work_order_no' AND t3.supplier_code = 'HMTH';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				//HMTH
				$sql = "SELECT
					package_code
				FROM
					tbl_package_master
				WHERE
					package_code = '$package_no';";
				//exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล Package No.');
				}

				$sql = "SELECT
					package_code
				FROM
					tbl_package_master
				WHERE
					package_code = '$package_no'
						AND delivery_status = 'Out';";
				//exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows > 0) {
					throw new Exception('Package No. ยังมีสถานะเป็น Out <br> กรุณาสแกนรับเข้าในเมนู 7.1');
				}

				$sql = "SELECT
					part_tag_no
				FROM
					tbl_inventory_detail
				WHERE
					part_tag_no = '$part_tag_no';";
				//exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล Part tag');
				}


				$sql = "SELECT
					fg_tag_no, bin_to_uuid(part_id,true) part_id, net_per_pcs
				FROM 
					tbl_order
				WHERE
					part_tag_no = '$part_tag_no';";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล' . __LINE__);
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$fg_tag_no = $row['fg_tag_no'];
					$part_id = $row['part_id'];
					$net_per_pcs = $row['net_per_pcs'];
				}

				$sql = "UPDATE tbl_order
				SET
				package_no = NULL,
				updated_at = NOW(), 
				updated_user_id = $cBy
				WHERE fg_tag_no = '$fg_tag_no';";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
				}

				$sql = "SELECT
					package_no
				FROM
					tbl_order
				WHERE
					fg_tag_no = '$fg_tag_no'
					AND package_no IS NOT NULL;";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows > 0) {
					while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
						$package_no_current = $row['package_no'];

						if ($package_no_current != $package_no) {
							throw new Exception('FG tag นี้<br>อยู่ใน Package No : ' . $package_no_current);
						}
					}
				}

				$explode = explode('-', $part_tag_no);
				$case_tag_no = $explode[0];

				$package_type = getPackageType($mysqli, $package_no);
				// $package_type_part = getPackageTypeByPart($mysqli, $part_id);

				// if ($package_type != $package_type_part) {
				// 	throw new Exception('Package Type ไม่ตรงกับใน Part Master' . __LINE__);
				// }

				$sql = "UPDATE tbl_order
				SET
				package_no = '$package_no',
				package_type = '$package_type',
				steel_qty = $steel_qty,
				updated_at = NOW(), 
				updated_user_id = $cBy
				WHERE fg_tag_no = '$fg_tag_no';";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
				}


				$sql = "SELECT
					fg_tag_no fg_tag, package_no, SUM(net_per_pcs) net_pallet
				FROM
					tbl_order
				WHERE
					order_header_id = uuid_to_bin('$order_header_id',true)
						AND package_no = '$package_no';";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows > 0) {
					while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
						$fg_tag = $row['fg_tag'];
						$net_pallet = $row['net_pallet'];

						if ($net_pallet > 1500) {
							throw new Exception('Package No : ' . $package_no . '<br> ผลรวม Net Weight มากกว่า 1500');
						}
					}
				}

				$sum_net = 0;
				$sql = "SELECT
					fg_tag_no fg_tag, package_no, net_per_pcs
				FROM
					tbl_order
				WHERE
					order_header_id = uuid_to_bin('$order_header_id',true)
						AND package_no = '$package_no';";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 1) {
					while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
						$fg_tag = $row['fg_tag'];

						if ($fg_tag_no != $fg_tag) {
							throw new Exception('Package No : ' . $package_no . '<br>ไม่ใช่ FG Tag เดียวกัน');
						}
					}
				}

				$sql = "SELECT
					fg_tag_no
				FROM
					tbl_order
				WHERE
					fg_tag_no = '$fg_tag_no'
					AND repack = 'Pending';";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows > 0) {
					$data = ['fg_tag_no' => ''];
				} else {
					$data = ['fg_tag_no' => $fg_tag_no];
				}
			} else {
				//NKAPM


				$sql = "SELECT
					part_tag_no
				FROM
					tbl_inventory_detail
				WHERE
					part_tag_no = '$part_tag_no';";
				//exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล Part tag');
				}


				$sql = "SELECT
					fg_tag_no, bin_to_uuid(part_id,true) part_id, net_per_pcs
				FROM 
					tbl_order
				WHERE
					part_tag_no = '$part_tag_no';";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล' . __LINE__);
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$fg_tag_no = $row['fg_tag_no'];
					$part_id = $row['part_id'];
					$net_per_pcs = $row['net_per_pcs'];
				}


				$sql = "UPDATE tbl_order
				SET
				steel_qty = 0,
				updated_at = NOW(), 
				updated_user_id = $cBy
				WHERE fg_tag_no = '$fg_tag_no';";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
				}

				$sql = "SELECT
					fg_tag_no
				FROM
					tbl_order
				WHERE
					fg_tag_no = '$fg_tag_no'
					AND repack = 'Pending';";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows > 0) {
					$data = ['fg_tag_no' => ''];
				} else {
					$data = ['fg_tag_no' => $fg_tag_no];
				}
			}




			$mysqli->commit();
			closeDBT($mysqli, 1, $data);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 22) {

		$dataParams = array(
			'obj',
			'obj=>work_order_no:s:0:1',
			'obj=>fg_tag_no:s:0:1',
			'obj=>part_tag_no:s:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT
				bin_to_uuid(order_header_id,true) order_header_id, bin_to_uuid(order_id,true) order_id
			FROM
				tbl_order
			WHERE
				work_order_no = '$work_order_no' AND part_tag_no = '$part_tag_no';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$order_header_id = $row['order_header_id'];
				$order_id = $row['order_id'];
			}

			$sql = "SELECT
				work_order_no
			FROM
				tbl_order t1
					INNER JOIN tbl_order_header t2 ON t1.order_header_id = t2.order_header_id
					INNER JOIN tbl_supplier_master t3 ON t2.supplier_id = t3.supplier_id
			WHERE
				work_order_no = '$work_order_no' AND t3.supplier_code = 'HMTH';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				//HMTH

				$sql = "SELECT
				part_tag_no
			FROM
				tbl_inventory_detail
			WHERE
				part_tag_no = '$part_tag_no';";
				//exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล Part tag');
				}

				$sql = "SELECT
				fg_tag_no, package_no, steel_qty, bin_to_uuid(part_id,true) fg_part_id
			FROM 
				tbl_order
			WHERE
				fg_tag_no = '$fg_tag_no' LIMIT 1;";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล' . __LINE__);
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$fg_part_id = $row['fg_part_id'];
					$package_no = $row['package_no'];
					$steel_qty = $row['steel_qty'];
				}


				$sql = "SELECT
				fg_tag_no old_fg_tag, bin_to_uuid(part_id,true) part_id, net_per_pcs
			FROM 
				tbl_order
			WHERE
				part_tag_no = '$part_tag_no';";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล' . __LINE__);
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$part_id = $row['part_id'];
					$net_per_pcs = $row['net_per_pcs'];
					$old_fg_tag = $row['old_fg_tag'];
				}


				if ($fg_part_id != $part_id) {
					throw new Exception('Part No. ไม่ตรงกัน');
				}

				if ($old_fg_tag == $fg_tag_no) {
					throw new Exception('Part tag นี้<br>อยู่ใน FG นี้แล้ว');
				}

				if ($package_no == '' || $package_no == NULL) {
					$package_type = '';
				} else {
					$package_type = getPackageType($mysqli, $package_no);
				}


				$sql = "UPDATE tbl_order
			SET
			fg_tag_no = '$fg_tag_no',
			package_no = '$package_no',
			package_type = '$package_type',
			steel_qty = '$steel_qty',
			updated_at = NOW(), 
			updated_user_id = $cBy
			WHERE part_tag_no = '$part_tag_no';";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
				}


				$sql = "SELECT
				package_no
			FROM
				tbl_order
			WHERE
				fg_tag_no = '$fg_tag_no'
				AND package_no IS NOT NULL;";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows > 0) {
					while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
						$package_no_current = $row['package_no'];

						if ($package_no_current != $package_no) {
							throw new Exception('FG tag นี้<br>อยู่ใน Package No : ' . $package_no_current);
						}
					}
				}


				$explode = explode('-', $part_tag_no);
				$case_tag_no = $explode[0];


				$sql = "SELECT
				fg_tag_no fg_tag, package_no, SUM(net_per_pcs) net_pallet
			FROM
				tbl_order
			WHERE
				order_header_id = uuid_to_bin('$order_header_id',true)
					AND package_no = '$package_no';";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows > 0) {
					while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
						$fg_tag = $row['fg_tag'];
						$net_pallet = $row['net_pallet'];

						if ($net_pallet > 1500) {
							throw new Exception('Package No : ' . $package_no . '<br> ผลรวม Net Weight มากกว่า 1500');
						}
					}
				}

				$sum_net = 0;
				$sql = "SELECT
				fg_tag_no fg_tag, package_no, net_per_pcs
			FROM
				tbl_order
			WHERE
				order_header_id = uuid_to_bin('$order_header_id',true)
					AND package_no = '$package_no';";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 1) {
					while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
						$fg_tag = $row['fg_tag'];

						if ($fg_tag_no != $fg_tag) {
							throw new Exception('Package No : ' . $package_no . '<br>ไม่ใช่ FG Tag เดียวกัน');
						}
					}
				}

				$sql = "SELECT
				bin_to_uuid(transaction_line_id,true) transaction_line_id,
				bin_to_uuid(transaction_id,true) transaction_id
			FROM 
				tbl_transaction_line
			WHERE
				fg_tag_no = '$old_fg_tag' AND case_tag_no = '$case_tag_no' AND status = 'Complete';";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล' . __LINE__);
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$transaction_line_id = $row['transaction_line_id'];
					$transaction_id = $row['transaction_id'];
				}

				$sql = "SELECT
				*, bin_to_uuid(transaction_line_id,true) transaction_line_id
			FROM 
				tbl_transaction_detail
			WHERE
				transaction_line_id = uuid_to_bin('$transaction_line_id',true) AND part_tag_no = '$part_tag_no';";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล' . __LINE__);
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$old_transaction_line_id = $row['transaction_line_id'];
				}

				$sql = "SELECT
				bin_to_uuid(transaction_line_id,true) transaction_line_id
			FROM 
				tbl_transaction_line
			WHERE
				transaction_line_id = uuid_to_bin('$transaction_line_id',true) AND status = 'Complete'
			AND qty > 0;";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows > 0) {
					$sql = "UPDATE tbl_transaction_line
				SET
				net_per_pallet = net_per_pallet-$net_per_pcs,
				qty = qty-1,
				updated_at = NOW(), 
				updated_user_id = $cBy
				WHERE transaction_line_id = uuid_to_bin('$transaction_line_id',true) AND status = 'Complete';";
					sqlError($mysqli, __LINE__, $sql, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
					}
				} else {
					$sql = "UPDATE tbl_transaction_line
				SET
				status = 'Cancel',
				updated_at = NOW(), 
				updated_user_id = $cBy
				WHERE transaction_line_id = uuid_to_bin('$transaction_line_id',true) AND status = 'Complete';";
					sqlError($mysqli, __LINE__, $sql, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
					}
				}


				$sql = "SELECT
				bin_to_uuid(transaction_line_id,true) transaction_line_id
			FROM 
				tbl_transaction_line
			WHERE
				fg_tag_no = '$fg_tag_no' AND case_tag_no = '$case_tag_no' AND status = 'Complete';";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows > 0) {
					while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
						$transaction_line_id = $row['transaction_line_id'];
					}

					$sql = "UPDATE tbl_transaction_line
				SET
				net_per_pallet = net_per_pallet+$net_per_pcs,
				qty = qty+1,
				updated_at = NOW(), 
				updated_user_id = $cBy
				WHERE transaction_line_id = uuid_to_bin('$transaction_line_id',true) AND status = 'Complete';";
					sqlError($mysqli, __LINE__, $sql, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
					}
				} else {

					$sql = "INSERT INTO tbl_transaction_line 
				( pallet_no, case_tag_no, fg_tag_no, work_order_no, part_id, certificate_no, orientation, package_type, invoice_no,
				remark, transaction_id, from_location_id, to_location_id, 
				qty, net_per_pallet, status,
				created_at, updated_at, created_user_id, updated_user_id )
				SELECT 
				pallet_no, case_tag_no, '$fg_tag_no', work_order_no, part_id, certificate_no, orientation, package_type, invoice_no,
				remark, transaction_id, from_location_id, to_location_id, 
				1, $net_per_pcs, 'Complete',
				NOW(), NOW(), $cBy, $cBy
				FROM tbl_transaction_line
				WHERE transaction_line_id = uuid_to_bin('$transaction_line_id',true) AND status = 'Complete'";
					sqlError($mysqli, __LINE__, $sql, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
					}
				}

				$sql = "SELECT
					bin_to_uuid(transaction_line_id,true) transaction_line_id
				FROM 
					tbl_transaction_line
				WHERE
					transaction_id = uuid_to_bin('$transaction_id',true) AND fg_tag_no = '$fg_tag_no' AND case_tag_no = '$case_tag_no' AND status = 'Complete';";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล' . __LINE__);
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$transaction_line_id = $row['transaction_line_id'];
				}


				$sql = "UPDATE tbl_transaction_detail
				SET
				transaction_line_id = uuid_to_bin('$transaction_line_id',true),
				updated_at = NOW(), 
				updated_user_id = $cBy
				WHERE transaction_line_id = uuid_to_bin('$old_transaction_line_id',true) AND part_tag_no = '$part_tag_no';";
				// exit($sql);
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
				}


				$sql = "SELECT
				fg_tag_no
			FROM
				tbl_order
			WHERE
				fg_tag_no = '$fg_tag_no'
				AND repack = 'Pending';";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows > 0) {
					$data = ['fg_tag_no' => ''];
				} else {
					$data = ['fg_tag_no' => $fg_tag_no];
				}
			} else {


				//NKAPM
				$sql = "SELECT
				part_tag_no
			FROM
				tbl_inventory_detail
			WHERE
				part_tag_no = '$part_tag_no';";
				//exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล Part tag');
				}

				$sql = "SELECT
				fg_tag_no, steel_qty, bin_to_uuid(part_id,true) fg_part_id
			FROM 
				tbl_order
			WHERE
				fg_tag_no = '$fg_tag_no' LIMIT 1;";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล' . __LINE__);
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$fg_part_id = $row['fg_part_id'];
					$steel_qty = $row['steel_qty'];
				}


				$sql = "SELECT
				fg_tag_no old_fg_tag, bin_to_uuid(part_id,true) part_id, net_per_pcs
			FROM 
				tbl_order
			WHERE
				part_tag_no = '$part_tag_no';";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล' . __LINE__);
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$part_id = $row['part_id'];
					$net_per_pcs = $row['net_per_pcs'];
					$old_fg_tag = $row['old_fg_tag'];
				}


				if ($fg_part_id != $part_id) {
					throw new Exception('Part No. ไม่ตรงกัน');
				}

				if ($old_fg_tag == $fg_tag_no) {
					throw new Exception('Part tag นี้<br>อยู่ใน FG นี้แล้ว');
				}


				$sql = "UPDATE tbl_order
			SET
			fg_tag_no = '$fg_tag_no',
			steel_qty = 0,
			updated_at = NOW(), 
			updated_user_id = $cBy
			WHERE part_tag_no = '$part_tag_no';";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
				}


				$explode = explode('-', $part_tag_no);
				$case_tag_no = $explode[0];


				$sql = "SELECT
				bin_to_uuid(transaction_line_id,true) transaction_line_id,
				bin_to_uuid(transaction_id,true) transaction_id
			FROM 
				tbl_transaction_line
			WHERE
				fg_tag_no = '$old_fg_tag' AND case_tag_no = '$case_tag_no' AND status = 'Complete';";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล' . __LINE__);
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$transaction_line_id = $row['transaction_line_id'];
					$transaction_id = $row['transaction_id'];
				}

				$sql = "SELECT
				*, bin_to_uuid(transaction_line_id,true) transaction_line_id
			FROM 
				tbl_transaction_detail
			WHERE
				transaction_line_id = uuid_to_bin('$transaction_line_id',true) AND part_tag_no = '$part_tag_no';";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล' . __LINE__);
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$old_transaction_line_id = $row['transaction_line_id'];
				}

				$sql = "SELECT
				bin_to_uuid(transaction_line_id,true) transaction_line_id
			FROM 
				tbl_transaction_line
			WHERE
				transaction_line_id = uuid_to_bin('$transaction_line_id',true) AND status = 'Complete'
			AND qty > 0;";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows > 0) {
					$sql = "UPDATE tbl_transaction_line
				SET
				net_per_pallet = net_per_pallet-$net_per_pcs,
				qty = qty-1,
				updated_at = NOW(), 
				updated_user_id = $cBy
				WHERE transaction_line_id = uuid_to_bin('$transaction_line_id',true) AND status = 'Complete';";
					sqlError($mysqli, __LINE__, $sql, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
					}
				} else {
					$sql = "UPDATE tbl_transaction_line
				SET
				status = 'Cancel',
				updated_at = NOW(), 
				updated_user_id = $cBy
				WHERE transaction_line_id = uuid_to_bin('$transaction_line_id',true) AND status = 'Complete';";
					sqlError($mysqli, __LINE__, $sql, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
					}
				}


				$sql = "SELECT
				bin_to_uuid(transaction_line_id,true) transaction_line_id
			FROM 
				tbl_transaction_line
			WHERE
				fg_tag_no = '$fg_tag_no' AND case_tag_no = '$case_tag_no' AND status = 'Complete';";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows > 0) {
					while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
						$transaction_line_id = $row['transaction_line_id'];
					}

					$sql = "UPDATE tbl_transaction_line
				SET
				net_per_pallet = net_per_pallet+$net_per_pcs,
				qty = qty+1,
				updated_at = NOW(), 
				updated_user_id = $cBy
				WHERE transaction_line_id = uuid_to_bin('$transaction_line_id',true) AND status = 'Complete';";
					sqlError($mysqli, __LINE__, $sql, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
					}
				} else {

					$sql = "INSERT INTO tbl_transaction_line 
				( pallet_no, case_tag_no, fg_tag_no, work_order_no, part_id, certificate_no, orientation, package_type, invoice_no,
				remark, transaction_id, from_location_id, to_location_id, 
				qty, net_per_pallet, status,
				created_at, updated_at, created_user_id, updated_user_id )
				SELECT 
				pallet_no, case_tag_no, '$fg_tag_no', work_order_no, part_id, certificate_no, orientation, package_type, invoice_no,
				remark, transaction_id, from_location_id, to_location_id, 
				1, $net_per_pcs, 'Complete',
				NOW(), NOW(), $cBy, $cBy
				FROM tbl_transaction_line
				WHERE transaction_line_id = uuid_to_bin('$transaction_line_id',true) AND status = 'Complete'";
					sqlError($mysqli, __LINE__, $sql, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
					}
				}

				$sql = "SELECT
					bin_to_uuid(transaction_line_id,true) transaction_line_id
				FROM 
					tbl_transaction_line
				WHERE
					transaction_id = uuid_to_bin('$transaction_id',true) AND fg_tag_no = '$fg_tag_no' AND case_tag_no = '$case_tag_no' AND status = 'Complete';";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล' . __LINE__);
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$transaction_line_id = $row['transaction_line_id'];
				}


				$sql = "UPDATE tbl_transaction_detail
				SET
				transaction_line_id = uuid_to_bin('$transaction_line_id',true),
				updated_at = NOW(), 
				updated_user_id = $cBy
				WHERE transaction_line_id = uuid_to_bin('$old_transaction_line_id',true) AND part_tag_no = '$part_tag_no';";
				// exit($sql);
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
				}


				$sql = "SELECT
				fg_tag_no
			FROM
				tbl_order
			WHERE
				fg_tag_no = '$fg_tag_no'
				AND repack = 'Pending';";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows > 0) {
					$data = ['fg_tag_no' => ''];
				} else {
					$data = ['fg_tag_no' => $fg_tag_no];
				}
			}

			$mysqli->commit();
			closeDBT($mysqli, 1, $data);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'ConfirmRepack'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {


		$dataParams = array(
			'obj',
			'obj=>work_order_no:s:0:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {


			$sql = "SELECT bin_to_uuid(order_header_id,true) order_header_id FROM tbl_order WHERE work_order_no = '$work_order_no';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			$order_header_id = $re1->fetch_array(MYSQLI_ASSOC)['order_header_id'];

			$sql = "SELECT t1.part_tag_no
			FROM tbl_inventory_detail t1 INNER JOIN tbl_order t2 ON t1.order_id = t2.order_id
			WHERE order_header_id = uuid_to_bin('$order_header_id',true) AND t1.package_no IS NOT NULL;";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('ไม่สามารถยกเลิกได้<br>เนื่องจากทำการออก GTN แล้ว');
			}

			// exit($sql);


			$sql = "SELECT part_tag_no
			FROM tbl_order
			WHERE order_header_id = uuid_to_bin('$order_header_id',true) 
			AND work_order_no = '$work_order_no' AND repack = 'Yes';";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$part_tag_no = $row['part_tag_no'];

					$sql = "UPDATE tbl_order
					SET
					repack = 'Pending',
					updated_at = NOW(), 
					updated_user_id = $cBy
					WHERE part_tag_no = '$part_tag_no';";
					sqlError($mysqli, __LINE__, $sql, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
					}
	
					$sql = "UPDATE tbl_inventory_detail
					SET
					repack_process = 'Pending',
					updated_at = NOW(), 
					updated_user_id = $cBy
					WHERE part_tag_no = '$part_tag_no';";
					sqlError($mysqli, __LINE__, $sql, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
					}
				}
			}


			$sql = "SELECT DISTINCT package_no
				FROM tbl_order
				WHERE order_header_id = uuid_to_bin('$order_header_id',true) 
				AND work_order_no = '$work_order_no'
				AND package_no IS NOT NULL;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$package_no = $row['package_no'];

					$sql = "UPDATE tbl_package_master
					SET
					package_status = 'Empty',
					updated_user_id = $cBy,
					updated_at = now()
					WHERE package_code = '$package_no';";
					sqlError($mysqli, __LINE__, $sql, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . $sql);
					}
				}
			}


			$sql = "UPDATE tbl_order
			SET
			package_no = null,
			package_type = '',
			steel_qty = 0,
			updated_at = NOW(), 
			updated_user_id = $cBy
			WHERE order_header_id = uuid_to_bin('$order_header_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}


			$sql = "UPDATE tbl_order_header
				SET
				order_status = 'Packing',
				delivery_status = 'Pending',
				dos_no = '',
				document_date = null,
				updated_user_id = $cBy,
				updated_at = now()
				WHERE order_header_id = uuid_to_bin('$order_header_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . $sql);
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
	if ($_SESSION['xxxRole']->{'ConfirmRepack'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {

		$dataParams = array(
			'obj',
			'obj=>work_order_no:s:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$order_id_array = [];

			$sql = "SELECT
				bin_to_uuid(order_id,true) order_id,
				bin_to_uuid(order_header_id,true) order_header_id
			FROM
				tbl_order
			WHERE
				work_order_no = '$work_order_no'
				AND status = 'Complete';";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล Work Order No.');
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$order_id = $row['order_id'];
				$order_header_id = $row['order_header_id'];

				$sql = "SELECT
					part_tag_no
				FROM 
					tbl_inventory_detail
				WHERE
					order_id = uuid_to_bin('$order_id',true)
					AND repack_process = 'Pending';";
				$re2 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re2->num_rows > 0) {
					throw new Exception('Work Order นี้<br>ยังสแกน Part tag ไม่ครบ');
				}
			}

			$sql = "SELECT
				work_order_no
			FROM
				tbl_order t1
					INNER JOIN tbl_order_header t2 ON t1.order_header_id = t2.order_header_id
					INNER JOIN tbl_supplier_master t3 ON t2.supplier_id = t3.supplier_id
			WHERE
				work_order_no = '$work_order_no' AND t3.supplier_code = 'HMTH';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				//HMTH
				$sql = "SELECT
					package_no
				FROM
					tbl_order
				WHERE
					work_order_no = '$work_order_no'
					AND status = 'Complete' GROUP BY package_no;";
				// exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล Work Order No.');
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$package_no = $row['package_no'];

					$sql = "UPDATE tbl_package_master
					SET
					package_status = 'FG',
					updated_user_id = $cBy,
					updated_at = now()
					WHERE package_code = '$package_no';";
					sqlError($mysqli, __LINE__, $sql, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . $sql);
					}
				}
			}


			$sql = "SELECT
				work_order_no
			FROM
				tbl_order
			WHERE
				order_header_id = uuid_to_bin('$order_header_id',true)
				AND repack = 'Pending';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				$data = ['dos_no' => '', 'work_order_no' => $work_order_no];
			} else {

				$sql = "SELECT
					dos_no
				FROM
					tbl_order_header
				WHERE
					order_header_id = uuid_to_bin('$order_header_id',true)
					AND dos_no != '';";
				$re2 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re2->num_rows > 0) {
					while ($row = $re2->fetch_array(MYSQLI_ASSOC)) {
						$dos_no = $row['dos_no'];
					}
				} else {
					$sql = "SELECT func_GenRuningNumber('delivery_order',0) as dos_no ;";
					$re1 = sqlError($mysqli, __LINE__, $sql, 1, 1);
					if ($re1->num_rows === 0) {
						throw new Exception('ไม่พบข้อมูล DOS No.' . __LINE__);
					}
					$dos_no = $re1->fetch_assoc()["dos_no"];
					$gen = true;
				}

				$sql = "UPDATE tbl_order_header
				SET
				order_status = 'Picking',
				delivery_status = 'Pending',
				dos_no = '$dos_no',
				document_date = NOW(),
				updated_user_id = $cBy,
				updated_at = now()
				WHERE order_header_id = uuid_to_bin('$order_header_id',true);";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . $sql);
				}
				$data = ['dos_no' => $dos_no, 'work_order_no' => $work_order_no];
			}


			// var_dump($data);
			// exit();
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
