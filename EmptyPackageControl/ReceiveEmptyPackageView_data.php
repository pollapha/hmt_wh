<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'ReceiveEmptyPackage'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'ReceiveEmptyPackage'}[0] == 0) {
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
			'obj=>start_date:s:5',
			'obj=>stop_date:s:5',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$data = ['start_date' => $start_date, 'stop_date' => $stop_date];
		$sql = select_group($mysqli, $data);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));


		// $re1 = select_group($mysqli, $data);
		// closeDBT($mysqli, 1, $re1);
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'ReceiveEmptyPackage'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {

		$dataParams = array(
			'obj',
			'obj=>document_no:s:0:0',
			'obj=>document_date:s:0:1',
			'obj=>package_control_no:s:0:0',
			'obj=>package_no:s:0:1',
			'obj=>steel_qty:i:0:0',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			/* Transaction */

			$sql = "SELECT bin_to_uuid(location_id,true) location_id FROM tbl_location_master WHERE location_area = 'receiving' LIMIT 1;";
			$result = sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($result->num_rows === 0) {
				throw new Exception('ไม่พบข้อมูล Location');
			}
			$location_id = $result->fetch_assoc()["location_id"];

			$sql = "SELECT bin_to_uuid(transaction_id,true) transaction_id, document_no FROM tbl_transaction WHERE document_no = '$document_no'; ";
			$result = sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($result->num_rows === 0) {
				$sql = "SELECT func_GenRuningNumber('temp',0) as document_no ;";
				$result = sqlError($mysqli, __LINE__, $sql, 1, 1);
				if ($result->num_rows === 0) {
					throw new Exception('ไม่พบข้อมูล Document_no TEMP');
				}
				$document_no = $result->fetch_assoc()["document_no"];

				$sql = "INSERT INTO tbl_transaction 
				(document_no, document_date, package_control_no, transaction_type, created_at, created_user_id) 
				VALUES
				('$document_no','$document_date', '$package_control_no', 'Temp-Package In', now(), $cBy);";
				// exit($sql);
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
				package_control_no = '$package_control_no',
				editing_at = NOW(), 
				editing_user_id = $cBy
			WHERE transaction_id = uuid_to_bin('$transaction_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}


			$sql = "SELECT package_no FROM tbl_transaction_line WHERE transaction_id = uuid_to_bin('$transaction_id',true) AND package_no = '$package_no';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('Package นี้มีการเพิ่มข้อมูลแล้ว ' . __LINE__);
			}

			$package_type = getPackageType($mysqli, $package_no);

			$sql = "INSERT INTO tbl_transaction_line 
				( package_no, package_type, steel_qty, transaction_id, 
				created_at, updated_at, created_user_id, updated_user_id
				)
				VALUES 
				( '$package_no', '$package_type', $steel_qty, uuid_to_bin('$transaction_id',true),
				now(), now(), $cBy, $cBy
				);";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			$sql = "INSERT INTO tbl_package_onhand 
			( package_no, package_type, steel_qty, transaction_line_id, 
			created_at, updated_at, created_user_id, updated_user_id
			)
			SELECT package_no, package_type, steel_qty, transaction_line_id,
			now(), now(), $cBy, $cBy
			FROM tbl_transaction_line
			WHERE transaction_id = uuid_to_bin('$transaction_id',true) 
			AND package_no = '$package_no' AND status != 'Cancel';";
			// exit($sql);
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
			'obj=>transaction_id:s:0:1',
			'obj=>transaction_line_id:s:0:1',
			'obj=>package_control_no:s:0:0',
			'obj=>package_no:s:0:1',
			'obj=>steel_qty:i:0:0',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			/* Transaction */

			$sql = "UPDATE tbl_transaction
			SET editing_at = NOW(), 
				editing_user_id = $cBy
			WHERE transaction_id = uuid_to_bin('$transaction_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}

			$sql = "UPDATE tbl_transaction_line
			SET 
				package_no = '$package_no',
				steel_qty = $steel_qty,
				updated_at = NOW(), 
				updated_user_id = $cBy
			WHERE transaction_id = uuid_to_bin('$transaction_id',true)
			AND transaction_line_id = uuid_to_bin('$transaction_line_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			$sql = "UPDATE tbl_package_onhand
			SET 
				package_no = '$package_no',
				steel_qty = $steel_qty,
				updated_at = NOW(), 
				updated_user_id = $cBy
			WHERE transaction_line_id = uuid_to_bin('$transaction_line_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			$mysqli->commit();
			closeDBT($mysqli, 1, 'OK');
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'ReceiveEmptyPackage'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'ReceiveEmptyPackage'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else if ($type == 32) {

		$dataParams = array(
			'obj',
			'obj=>document_no:s:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			/* Transaction */

			$sql = "SELECT bin_to_uuid(transaction_id,true) transaction_id 
					FROM tbl_transaction 
					WHERE document_no = '$document_no';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล document_no : ' . $document_no);
			}
			$transaction_id = $re1->fetch_assoc()["transaction_id"];

			$sql = "UPDATE tbl_transaction 
				SET transaction_type = 'Temp-Package In',
				updated_user_id = $cBy,
				updated_at = now()
				WHERE transaction_id = uuid_to_bin('$transaction_id',true)";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}


			$sql = "SELECT bin_to_uuid(transaction_line_id,true) transaction_line_id, bin_to_uuid(supplier_id,true) supplier_id, package_no 
					FROM tbl_transaction_line 
					WHERE transaction_id = uuid_to_bin('$transaction_id',true) AND status != 'Cancel';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล : ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$transaction_line_id = $row['transaction_line_id'];
				$supplier_id = $row['supplier_id'];
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

				$sql = "UPDATE tbl_transaction_line
				SET status = 'Cancel'
				WHERE transaction_id = uuid_to_bin('$transaction_id',true)
					AND transaction_line_id = uuid_to_bin('$transaction_line_id',true);";
				sqlError($mysqli, __LINE__, $sql, 1, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถยกเลิกได้ ' . __LINE__);
				}
			}


			// $sql = "SELECT package_no FROM tbl_transaction_line WHERE transaction_id = uuid_to_bin('$transaction_id',true) AND package_no = '$package_no';";
			// $re1 = sqlError($mysqli, __LINE__, $sql, 1);
			// if ($re1->num_rows > 0) {
			// 	throw new Exception('Package นี้มีการเพิ่มข้อมูลแล้ว ' . __LINE__);
			// }

			// $package_type = getPackageType($mysqli, $package_no);

			// $sql = "INSERT INTO tbl_transaction_line 
			// 	( package_no, package_type, steel_qty, transaction_id, 
			// 	created_at, updated_at, created_user_id, updated_user_id
			// 	)
			// 	VALUES 
			// 	( '$package_no', '$package_type', $steel_qty, uuid_to_bin('$transaction_id',true),
			// 	now(), now(), $cBy, $cBy
			// 	);";
			// sqlError($mysqli, __LINE__, $sql, 1, 1);
			// if ($mysqli->affected_rows == 0) {
			// 	throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			// }

			// $sql = "UPDATE tbl_package_master
			// SET delivery_status = 'In',
			// updated_at = NOW(),
			// updated_user_id = $cBy
			// WHERE package_code = '$package_no';";
			// sqlError($mysqli, __LINE__, $sql, 1, 1);
			// if ($mysqli->affected_rows == 0) {
			// 	throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			// }

			// 	/* Transaction */

			// 	$sql = "UPDATE tbl_transaction
			// 	SET editing_at = NOW(), 
			// 		editing_user_id = $cBy
			// 	WHERE transaction_id = uuid_to_bin('$transaction_id',true);";
			// 	sqlError($mysqli, __LINE__, $sql, 1, 1);
			// 	if ($mysqli->affected_rows == 0) {
			// 		throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			// 	}


			// 	$sql = "DELETE FROM tbl_transaction_line
			// 	WHERE transaction_id = uuid_to_bin('$transaction_id',true)
			// 		AND transaction_line_id = uuid_to_bin('$transaction_line_id',true);";
			// 	sqlError($mysqli, __LINE__, $sql, 1, 1);
			// 	if ($mysqli->affected_rows == 0) {
			// 		throw new Exception('ไม่สามารถยกเลิกได้ ' . __LINE__);
			// 	}

			// 	$sql = "UPDATE tbl_package_master
			// 	SET delivery_status = 'Out',
			// 	updated_at = NOW(),
			// 	updated_user_id = $cBy
			// 	WHERE package_code = '$package_no';";
			// 	sqlError($mysqli, __LINE__, $sql, 1, 1);
			// 	if ($mysqli->affected_rows == 0) {
			// 		throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			// 	}

			$mysqli->commit();
			closeDBT($mysqli, 1, 'OK');
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'ReceiveEmptyPackage'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {

		$dataParams = array(
			'obj',
			'obj=>document_no:s:0:1',
			'obj=>document_date:s:0:1',
			'obj=>package_control_no:s:0:0',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT bin_to_uuid(transaction_id,true) transaction_id 
					FROM tbl_transaction 
					WHERE document_no = '$document_no' AND transaction_type = 'Temp-Package In';";
			$result = sqlError($mysqli, __LINE__, $sql, 1);
			if ($result->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล document_no : ' . $document_no);
			}
			$transaction_id = $result->fetch_assoc()["transaction_id"];

			$document_no_new = $document_no;
			$gen = false;

			if ((stripos($document_no, "emp")) === FALSE) {
				$sql = "SELECT func_GenRuningNumber('emp_pack',0) as document_no ;";
				$result = sqlError($mysqli, __LINE__, $sql, 1, 1);
				if ($result->num_rows === 0) {
					throw new Exception('ไม่พบข้อมูล document_no EMP ' . __LINE__);
				}
				$document_no_new = $result->fetch_assoc()["document_no"];
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


			$sql = "UPDATE tbl_transaction 
				SET transaction_type = 'In', 
				document_no = '$document_no_new',
				document_date = '$document_date',
				package_control_no = '$package_control_no',
				editing_at = null,
				editing_user_id = null
				WHERE transaction_id = uuid_to_bin('$transaction_id',true) AND transaction_type = 'Temp-Package In';";
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

			$document_no = $document_no_new;
			$mysqli->commit();
			closeDBT($mysqli, 1, $document_no);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else closeDBT($mysqli, 2, 'TYPE ERROR');


function select_group($mysqli, $data)
{

	try {
		$where = [];

		if ($data['start_date'] == '' && $data['stop_date'] == '') {
			$sqlWhere = '';
		} else if ($data['start_date'] != '' && $data['stop_date'] == '') {
			$sqlWhere = '';
			throw new Exception('กรุณาป้อนวันที่สิ้นสุด');
		} else if ($data['start_date'] == '' && $data['stop_date'] != '') {
			throw new Exception('กรุณาป้อนวันที่เริ่มต้น');
			$sqlWhere = '';
		} else {
			$where[] = "AND DATE(document_date) between DATE('$data[start_date]') and DATE('$data[stop_date]')";
		}

		$sqlWhere = join(' and ', $where);
	} catch (Exception $e) {
		$mysqli->rollback();
		closeDBT($mysqli, 2, $e->getMessage());
	}

	$sql = "SELECT 
		row_number() over (partition by document_no order by document_date DESC, document_no DESC, transaction_line_id ASC) row_no,
		document_no, document_date, package_control_no, transaction_type,
		package_no, package_type, steel_qty
	FROM
		tbl_transaction t1
			INNER JOIN tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
	WHERE t1.transaction_type = 'Package In'
		AND t2.status = 'Complete'
		$sqlWhere 
	order by document_date DESC, document_no DESC, transaction_line_id ASC;";
	// exit($sql);
	return $sql;
}



$mysqli->close();
exit();
