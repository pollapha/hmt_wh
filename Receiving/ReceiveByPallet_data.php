<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'ReceiveByPallet'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'ReceiveByPallet'}[0] == 0) {
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
include('../vendor/autoload.php');
include('../common/common.php');

if ($type <= 10) //data
{
	if ($type == 1) {

		$sql = "SELECT 
				BIN_TO_UUID(t1.transaction_id, TRUE) AS transaction_id,
				document_no, document_date, declaration_no, container_no, bl_no, t1.invoice_no
			FROM
				tbl_transaction t1
					LEFT JOIN
				tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
			WHERE
				t1.editing_user_id = $cBy
					AND t1.transaction_type = 'Temp-In'
					AND t2.status = 'Pending'
			GROUP BY t1.transaction_id ORDER BY t1.editing_at DESC LIMIT 1";
		// exit($sql);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		$header = jsonRow($re1, true, 0);

		$body = [];

		if (count($header) > 0) {
			$transaction_id = $header[0]['transaction_id'];

			$sql = "SELECT 
				BIN_TO_UUID(transaction_id, TRUE) AS transaction_id,
				BIN_TO_UUID(transaction_line_id, TRUE) AS transaction_line_id,
				pallet_no, case_tag_no, part_no, part_name,
				qty, steel_qty, gross_kg, net_per_pallet, measurement_cbm, certificate_no,
				t1.remark
			FROM
				tbl_transaction_line t1
					inner join tbl_part_master t2 on t1.part_id = t2.part_id
			WHERE
				transaction_id = uuid_to_bin('$transaction_id',true)
					AND t1.status = 'Pending';";
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
				document_no, document_date, declaration_no, container_no, bl_no, t1.invoice_no
			FROM
				tbl_transaction t1
					LEFT JOIN
				tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
			WHERE
				t1.document_no = '$document_no'
					AND t1.transaction_type = 'Temp-In'
					AND t2.status = 'Pending'
			GROUP BY t1.transaction_id";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$header = jsonRow($re1, true, 0);

			$body = [];

			if (count($header) > 0) {
				$transaction_id = $header[0]['transaction_id'];

				$sql = "SELECT 
				BIN_TO_UUID(transaction_id, TRUE) AS transaction_id,
				BIN_TO_UUID(transaction_line_id, TRUE) AS transaction_line_id,
				pallet_no, case_tag_no, part_no, part_name,
				qty, steel_qty, gross_kg, net_per_pallet, measurement_cbm, certificate_no,
				t1.remark
			FROM
				tbl_transaction_line t1
					inner join tbl_part_master t2 on t1.part_id = t2.part_id
			WHERE
				transaction_id = uuid_to_bin('$transaction_id',true)
					AND t1.status = 'Pending';";
				//exit($sql);
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);

				$body = jsonRow($re1, true, 0);
			}

			$returnData = ['header' => $header, 'body' => $body];

			$mysqli->commit();
			closeDBT($mysqli, 1, $returnData);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'ReceiveByPallet'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {

		$dataParams = array(
			'obj',
			'obj=>document_no:s:0:0',
			'obj=>document_date:s:0:1',
			'obj=>declaration_no:s:0:0',
			'obj=>container_no:s:0:0',
			'obj=>bl_no:s:0:0',
			'obj=>invoice_no:s:0:0',
			'obj=>part_no:s:0:1',
			'obj=>pallet_no:s:0:1',
			'obj=>net_per_pallet:f:0:1',
			'obj=>qty:i:0:1',
			'obj=>gross_kg:f:0:0',
			'obj=>coil_lot_no:s:0:0',
			'obj=>certificate_no:s:0:1',
			'obj=>measurement_cbm:f:0:0.00',
			'obj=>remark:s:0:0',
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
				(document_no, document_date, declaration_no, container_no, bl_no, invoice_no, transaction_type, location_id, created_at, created_user_id) 
				VALUES
				('$document_no','$document_date', '$declaration_no', '$container_no', '$bl_no', '$invoice_no', 'Temp-In', uuid_to_bin('$location_id',true), now(), $cBy);";
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
				declaration_no = '$declaration_no',
				container_no = '$container_no',
				bl_no = '$bl_no',
				invoice_no = '$invoice_no',
				editing_at = NOW(), 
				editing_user_id = $cBy
			WHERE transaction_id = uuid_to_bin('$transaction_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}


			$sql = "SELECT pallet_no FROM tbl_transaction_line WHERE transaction_id = uuid_to_bin('$transaction_id',true) AND pallet_no = '$pallet_no' AND status != 'Cancel';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('Pallet นี้มีการเพิ่มข้อมูลแล้ว ' . __LINE__);
			}


			$sql = "SELECT bin_to_uuid(part_id,true) part_id FROM tbl_part_master WHERE part_no = '$part_no';";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			$part_id = $re1->fetch_array(MYSQLI_ASSOC)['part_id'];

			$package_type = getPackageTypeByPart($mysqli, $part_id);

			$sql = "SELECT func_GenRuningNumber('case_tag',0) as case_tag_no ;";
			$result = sqlError($mysqli, __LINE__, $sql, 1, 1);
			$case_tag_no = $result->fetch_assoc()["case_tag_no"];

			$sql = "INSERT INTO tbl_transaction_line 
				( pallet_no, case_tag_no, part_id, qty, gross_kg, net_per_pallet, measurement_cbm, certificate_no, coil_lot_no, remark, package_type,
				transaction_id, to_location_id, invoice_no,
				created_at, updated_at, created_user_id, updated_user_id
				)
				VALUES 
				( '$pallet_no', '$case_tag_no', uuid_to_bin('$part_id',true), $qty, '$gross_kg', '$net_per_pallet', '$measurement_cbm', '$certificate_no', '$coil_lot_no', '$remark', '$package_type',
				uuid_to_bin('$transaction_id',true), uuid_to_bin('$location_id',true), '$invoice_no',
				now(), now(), $cBy, $cBy
				);";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			$sql = "SELECT bin_to_uuid(transaction_line_id,true) transaction_line_id, case_tag_no FROM tbl_transaction_line WHERE transaction_id = uuid_to_bin('$transaction_id',true)
			AND case_tag_no = '$case_tag_no';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$transaction_line_id = $row['transaction_line_id'];
				$case_tag_no = $row['case_tag_no'];
			}

			$net_per_pcs = round($net_per_pallet / $qty, 2);
			$i = 1;
			while ($i <= $qty) {

				$part_tag_no = $case_tag_no . '-' . $i;

				// $sql = "SELECT func_GenRuningNumber('serial',0) as part_tag_no ;";
				// $result = sqlError($mysqli, __LINE__, $sql, 1, 1);
				// $part_tag_no = $result->fetch_assoc()["part_tag_no"];

				$sql = "INSERT INTO tbl_transaction_detail 
					(part_tag_no, part_id, qty, net_per_pcs, remark, transaction_line_id, to_location_id, 
					created_at, updated_at, created_user_id, updated_user_id)
					VALUES 
					('$part_tag_no', uuid_to_bin('$part_id',true), 1, '$net_per_pcs', '$remark', uuid_to_bin('$transaction_line_id',true), uuid_to_bin('$location_id',true), 
					now(), now(), $cBy, $cBy);";
				//exit($sql);
				sqlError($mysqli, __LINE__, $sql, 1, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
				}

				$i++;
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
			'obj=>document_date:s:0:0',
			'obj=>transaction_id:s:0:1',
			'obj=>transaction_line_id:s:0:1',
			'obj=>case_tag_no:s:0:1',
			'obj=>part_no:s:0:1',
			'obj=>pallet_no:s:0:1',
			'obj=>net_per_pallet:f:0:1',
			'obj=>qty:i:0:1',
			'obj=>gross_kg:f:0:0',
			'obj=>coil_lot_no:s:0:0',
			'obj=>certificate_no:s:0:1',
			'obj=>invoice_no:s:0:0',
			'obj=>measurement_cbm:f:0:0.00',
			'obj=>remark:s:0:0',
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

			$sql = "UPDATE tbl_transaction
			SET editing_at = NOW(), 
				editing_user_id = $cBy
			WHERE transaction_id = uuid_to_bin('$transaction_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}

			$sql = "SELECT bin_to_uuid(part_id,true) part_id FROM tbl_part_master WHERE part_no = '$part_no';";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			$part_id = $re1->fetch_array(MYSQLI_ASSOC)['part_id'];

			$package_type = getPackageTypeByPart($mysqli, $part_id);

			$sql = "SELECT pallet_no FROM tbl_transaction_line WHERE transaction_id = uuid_to_bin('$transaction_id',true) 
			AND pallet_no = '$pallet_no'
			AND transaction_line_id != uuid_to_bin('$transaction_line_id',true);";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('Pallet นี้มีการเพิ่มข้อมูลแล้ว ' . __LINE__);
			}

			$sql = "UPDATE tbl_transaction_line t1,
			(SELECT transaction_line_id, pallet_no, case_tag_no 
			FROM tbl_transaction_line WHERE pallet_no = '$pallet_no' AND case_tag_no = '$case_tag_no' AND status != 'Cancel') t2
			SET 
				t1.package_type = '$package_type',
				t1.pallet_no = '$pallet_no',
				t1.part_id = uuid_to_bin('$part_id',true),
				t1.qty = $qty,
				t1.gross_kg = '$gross_kg',
				t1.net_per_pallet = '$net_per_pallet',
				t1.measurement_cbm = '$measurement_cbm',
				t1.certificate_no = '$certificate_no',
				t1.coil_lot_no = '$coil_lot_no',
				t1.invoice_no = '$invoice_no',
				t1.remark = '$remark'
			WHERE t1.transaction_line_id = t2.transaction_line_id";
			// exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่มีการเปลี่ยนแปลงค่า');
			}

			$sql = "UPDATE tbl_transaction_line
			SET 
				updated_at = NOW(), 
				updated_user_id = $cBy
			WHERE transaction_id = uuid_to_bin('$transaction_id',true)
			AND transaction_line_id = uuid_to_bin('$transaction_line_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			$serialArray = [];
			$sql = "SELECT part_tag_no FROM tbl_transaction_detail 
				WHERE transaction_line_id = uuid_to_bin('$transaction_line_id',true)
				AND status = 'Complete' order by part_tag_no;";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$part_tag_no = $row['part_tag_no'];
					$serialArray[] = $part_tag_no;
				}
			}

			$count = count($serialArray);
			if ($qty > $count) {
				$net_per_pcs = round($net_per_pallet / $qty, 2);

				$i = 1;
				while ($i <= $qty) {
					if ($i > $count) {
						$part_tag_no = $case_tag_no . '-' . $i;
						//echo ($part_tag_no . '<br>');
						$sql = "INSERT INTO tbl_transaction_detail 
						(part_tag_no, part_id, qty, net_per_pcs, remark, transaction_line_id, to_location_id,
						created_at, updated_at, created_user_id, updated_user_id)
						VALUES 
						('$part_tag_no', uuid_to_bin('$part_id',true), 1, '$net_per_pcs', '$remark', uuid_to_bin('$transaction_line_id',true), uuid_to_bin('$location_id',true),
						now(), now(), $cBy, $cBy);";
						//exit($sql);
						sqlError($mysqli, __LINE__, $sql, 1, 1);
						if ($mysqli->affected_rows == 0) {
							throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
						}
					} else {
						//echo ($serialArray[$i - 1] . '<br>');
						$part_tag_no = $serialArray[$i - 1];
						$sql = "UPDATE tbl_transaction_detail
						SET part_id = uuid_to_bin('$part_id',true),
							net_per_pcs = '$net_per_pcs',
							remark = '$remark',
							updated_at = NOW(), 
							updated_user_id = $cBy
						WHERE part_tag_no = '$part_tag_no'
							AND transaction_line_id = uuid_to_bin('$transaction_line_id',true);";
						sqlError($mysqli, __LINE__, $sql, 1, 1);
						if ($mysqli->affected_rows == 0) {
							throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
						}
					}
					$i++;
				}
			} else if ($qty < $count) {
				$net_per_pcs = $net_per_pallet / $qty;
				$i = 1;
				while ($i <= $count) {
					if ($i > $qty) {
						$part_tag_no = $case_tag_no . '-' . $i;
						//echo ($part_tag_no . '<br>');
						$sql = "DELETE FROM tbl_transaction_detail
						WHERE part_tag_no = '$part_tag_no'
							AND transaction_line_id = uuid_to_bin('$transaction_line_id',true);";
						sqlError($mysqli, __LINE__, $sql, 1, 1);
						if ($mysqli->affected_rows == 0) {
							throw new Exception('ไม่สามารถยกเลิกได้ ' . __LINE__);
						}
					} else {
						$part_tag_no = $serialArray[$i - 1];
						//echo ($serialArray[$i - 1] . '<br>');
						$sql = "UPDATE tbl_transaction_detail
						SET part_id = uuid_to_bin('$part_id',true),
							net_per_pcs = '$net_per_pcs',
							remark = '$remark',
							updated_at = NOW(), 
							updated_user_id = $cBy
						WHERE part_tag_no = '$part_tag_no'
							AND transaction_line_id = uuid_to_bin('$transaction_line_id',true);";
						sqlError($mysqli, __LINE__, $sql, 1, 1);
						if ($mysqli->affected_rows == 0) {
							throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
						}
					}
					$i++;
				}
			} else if ($qty == $count) {
				$i = 0;
				$net_per_pcs = $net_per_pallet / $qty;
				while ($i < $count) {
					$part_tag_no = $serialArray[$i];
					$sql = "UPDATE tbl_transaction_detail
					SET part_id = uuid_to_bin('$part_id',true),
						net_per_pcs = '$net_per_pcs',
						remark = '$remark',
						updated_at = NOW(), 
						updated_user_id = $cBy
					WHERE part_tag_no = '$part_tag_no'
						AND transaction_line_id = uuid_to_bin('$transaction_line_id',true);";
					sqlError($mysqli, __LINE__, $sql, 1, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
					}
					$i++;
				}
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
			'obj=>declaration_no:s:0:0',
			'obj=>container_no:s:0:0',
			'obj=>bl_no:s:0:0',
			'obj=>invoice_no:s:0:0',
			// 'obj=>part_no:s:0:1',
			// 'obj=>pallet_no:s:0:1',
			// 'obj=>net_per_pallet:f:0:1',
			// 'obj=>qty:i:0:1',
			// 'obj=>gross_kg:f:0:0',
			// 'obj=>coil_lot_no:s:0:1',
			// 'obj=>certificate_no:s:0:0',
			// 'obj=>measurement_cbm:f:0:0.00',
			'obj=>plan:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

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
					(document_no, document_date, declaration_no, container_no, bl_no, invoice_no, transaction_type, location_id, created_at, created_user_id) 
					VALUES
					('$document_no','$document_date', '$declaration_no', '$container_no', '$bl_no', '$invoice_no', 'Temp-In', uuid_to_bin('$location_id',true), now(), $cBy);";
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
						declaration_no = '$declaration_no',
						container_no = '$container_no',
						bl_no = '$bl_no',
						invoice_no = '$invoice_no',
						editing_at = NOW(), 
						editing_user_id = $cBy
					WHERE transaction_id = uuid_to_bin('$transaction_id',true);";
			// exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
			}

			$text_plan = explode('\n', $plan);

			$part_array = [];
			$data_array = [];

			foreach ($text_plan as $id => $row) {
				if (strpos(strtolower($row), 'part no') !== false) {
					$list = explode(':', $row);
					$part_no = $list[3];
					$part_no = trim($part_no);
					$part_array[] = $part_no;
					unset($text_plan[$id]);
				} else if (strpos(strtolower($row), 'aluminium') !== false) {
					unset($text_plan[$id]);
				}
			}



			$i = 0;
			foreach ($text_plan as $row) {
				if (strpos(strtolower($row), 'sub-total') !== false) {
					$i++;
				} else if (trim($row) == '') {
					break;
				} else {
					$list = preg_split('/\t+/', $row);
					$part_no = $part_array[$i];
					$gross_kg = $list[2];
					$net_per_pallet = $list[3];
					$measurement_cbm = $list[4];
					$certificate_no = $list[5];
					$pallet_no = $list[6];
					$qty = trim($list[7]);
					$coil_lot_no = '';
					$remark = '';

					// var_dump($list);

					/* Transaction */

					$sql = "SELECT pallet_no FROM tbl_transaction_line WHERE transaction_id = uuid_to_bin('$transaction_id',true) AND pallet_no = '$pallet_no' 
					AND status != 'Cancel';";
					$re1 = sqlError($mysqli, __LINE__, $sql, 1);
					if ($re1->num_rows > 0) {
						throw new Exception('Pallet นี้มีการเพิ่มข้อมูลแล้ว ' . __LINE__);
					}


					$sql = "SELECT bin_to_uuid(part_id,true) part_id FROM tbl_part_master WHERE part_no = '$part_no';";
					// exit($sql);
					$re1 = sqlError($mysqli, __LINE__, $sql, 1);
					if ($re1->num_rows == 0) {
						throw new Exception('ไม่พบข้อมูล ' . __LINE__);
					}
					$part_id = $re1->fetch_array(MYSQLI_ASSOC)['part_id'];

					$package_type = getPackageTypeByPart($mysqli, $part_id);

					$sql = "SELECT func_GenRuningNumber('case_tag',0) as case_tag_no ;";
					$result = sqlError($mysqli, __LINE__, $sql, 1, 1);
					$case_tag_no = $result->fetch_assoc()["case_tag_no"];

					$sql = "INSERT INTO tbl_transaction_line 
					( pallet_no, case_tag_no, part_id, qty, gross_kg, net_per_pallet, measurement_cbm, certificate_no, coil_lot_no, remark, package_type,
					transaction_id, to_location_id, invoice_no,
					created_at, updated_at, created_user_id, updated_user_id
					)
					VALUES 
					( '$pallet_no', '$case_tag_no', uuid_to_bin('$part_id',true), $qty, '$gross_kg', '$net_per_pallet', '$measurement_cbm', '$certificate_no', '$coil_lot_no', '$remark', '$package_type',
					uuid_to_bin('$transaction_id',true), uuid_to_bin('$location_id',true), '$invoice_no',
					now(), now(), $cBy, $cBy
					);";
					sqlError($mysqli, __LINE__, $sql, 1, 1);
					if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
					}

					$sql = "SELECT bin_to_uuid(transaction_line_id,true) transaction_line_id, case_tag_no FROM tbl_transaction_line 
					WHERE transaction_id = uuid_to_bin('$transaction_id',true)
					AND case_tag_no = '$case_tag_no';";
					$re1 = sqlError($mysqli, __LINE__, $sql, 1);
					if ($re1->num_rows == 0) {
						throw new Exception('ไม่พบข้อมูล ' . __LINE__);
					}
					while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
						$transaction_line_id = $row['transaction_line_id'];
					}

					// echo $transaction_line_id .' '. $case_tag_no.'<br>';

					$net_per_pcs = round($net_per_pallet / $qty, 2);
					$j = 1;
					while ($j <= $qty) {

						$part_tag_no = $case_tag_no . '-' . $j;


						$sql = "INSERT INTO tbl_transaction_detail 
						(part_tag_no, part_id, qty, net_per_pcs, remark, transaction_line_id, to_location_id, 
						created_at, updated_at, created_user_id, updated_user_id)
						VALUES 
						('$part_tag_no', uuid_to_bin('$part_id',true), 1, '$net_per_pcs', '$remark', uuid_to_bin('$transaction_line_id',true), uuid_to_bin('$location_id',true), 
						now(), now(), $cBy, $cBy);";
						sqlError($mysqli, __LINE__, $sql, 1, 1);
						if ($mysqli->affected_rows == 0) {
							throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
						}

						$j++;
					}
				}
			}

			// exit();

			$mysqli->commit();

			closeDBT($mysqli, 1, 'เพิ่มสำเร็จ');
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 14) {

		$mysqli->autocommit(FALSE);
		try {
			if ($_FILES["upload"]["name"] != '') {
				$allowed_extension = 'pdf';
				$file_array = explode(".", $_FILES["upload"]["name"]);
				$file_extension = strtolower(end($file_array));

				if ($file_extension === $allowed_extension) {

					$file_name = '../order_file/packing_list/' . time() . '.' . $file_extension;
					move_uploaded_file($_FILES['upload']['tmp_name'], $file_name);


					$parser = new \Smalot\PdfParser\Parser();
					$pdf = $parser->parseFile($file_name);


					$text = $pdf->getText();

					$text_plan = explode('P/O NO:', $text);
					// $part_array = [];

					foreach ($text_plan as $id => $row) {
						if (strpos($row, 'PART NO:') === false) {
							unset($text_plan[$id]);
						}
					}

					$plan_array = [];
					foreach ($text_plan as $row) {
						$plan = explode('Page', $row);
						foreach ($plan as $val1) {
							$plan2 = explode('Tax Reg. No: 31011460735952X 	Fax: +86-021-59543111/59543050 ', $val1);
							foreach ($plan2 as $id => $val2) {
								if (strpos($val2, 'ALLOY') !== false) {
									$plan_array[] = $plan2[$id];
								}
							}
						}
					}

					// var_dump($plan_array);
					// exit();

					$listpart_array = [];
					// $list_array = [];
					foreach ($plan_array as $id => $row) {
						$plan = explode(' ', $row);
						$split_partno = explode('NO:', $plan[1]);
						$part_no = $split_partno[1];
						$listpart_array[] = $part_no;
					}

					$list_array = [];
					foreach ($plan_array as $id => $row) {
						$plan = explode(' ', $row);
						$list_array[] = $plan;
					}

					for ($i = 0; $i < count($list_array); $i++) {
						$list = $list_array[$id];
						foreach ($list as $id => $row) {
							if (strpos($row, 'ALLOY') === false) {
								unset($list[$id]);
							} else if (strpos($row, 'NO:') === false) {
								unset($list[$id]);
							}
						}
					}

					// foreach ($list_array as $id => $row) {
					// 	var_dump($row[$id]);
					// }

					// var_dump($list_array);
					exit();

					// unlink($file_name); // ลบไฟล์ที่อัปโหลดหลังจากใช้งานเสร็จ

					$textArray = [];
					foreach ($pdf->getPages() as $page) {
						$text = $page->getText();
						$matches = [];
						// ค้นหารายการที่มีรูปแบบ "*...*"
						if (preg_match_all('/PART NO:(.*?)[\s]+/', $text, $matches)) {
							// เก็บข้อมูลที่ตรงกับรูปแบบในอาร์เรย์
							$textArray = array_merge($textArray, $matches[1]);
						}
					}


					$textArray = array_unique($textArray);
					$pds_no = null;
					$del_no = null;

					// แสดงผลลัพธ์
					foreach ($textArray as $item) {
						$itemLen = strlen($item);
						if ($itemLen === 16) {
							$pds_no = $item;
							continue;
						} elseif ($itemLen === 10) {
							$del_no = $item;
						}
						// var_dump("pds_no :".$pds_no);
						// var_dump("del_no :".$del_no);
						$sql = "UPDATE tbl_order set pds_no = '$pds_no', updated_at = now(), updated_user_id = $cBy 
								WHERE del_no = '$del_no';";
						sqlError($mysqli, __LINE__, $sql, 1);
						if ($mysqli->affected_rows == 0) {
							throw new Exception('ไม่พบ Delivery No ที่ตรงกัน');
						}
					}
					if (($pds_no === null) || ($del_no === null)) {
						closeDBT($mysqli, 2, "ไม่พบ pds no หรือ del no ตรวจสอบ format ไฟล์ pdf ใหม่");
					}

					$mysqli->commit();
					closeDBT($mysqli, 1, 'upload สำเร็จ');
				} else {
					closeDBT($mysqli, 2, "Only PDF files are supported");
				}
			} else {
				closeDBT($mysqli, 2, "Please Select File");
			}
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'ReceiveByPallet'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
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

			$sql = "SELECT bin_to_uuid(transaction_id,true) transaction_id 
					FROM tbl_transaction 
					WHERE document_no = '$document_no' AND transaction_type = 'In';";
			$result = sqlError($mysqli, __LINE__, $sql, 1);
			if ($result->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล document_no : ' . $document_no);
			}
			$transaction_id = $result->fetch_assoc()["transaction_id"];


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
				SET transaction_type = 'Temp-In',
				editing_at = NOW(),
				editing_user_id = $cBy
				WHERE transaction_id = uuid_to_bin('$transaction_id',true) 
				AND transaction_type = 'In';";
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
	if ($_SESSION['xxxRole']->{'ReceiveByPallet'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {

		$dataParams = array(
			'obj',
			'obj=>transaction_id:s:0:1',
			'obj=>transaction_line_id:s:0:1',
			'obj=>pallet_no:s:0:1',
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

			$sql = "DELETE FROM tbl_transaction_line
			WHERE transaction_id = uuid_to_bin('$transaction_id',true)
				AND transaction_line_id = uuid_to_bin('$transaction_line_id',true);";
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

			$mysqli->commit();
			closeDBT($mysqli, 1, 'OK');
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'ReceiveByPallet'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {

		$dataParams = array(
			'obj',
			'obj=>document_no:s:0:1',
			'obj=>document_date:s:0:1',
			'obj=>declaration_no:s:0:0',
			'obj=>container_no:s:0:0',
			'obj=>bl_no:s:0:0',
			'obj=>invoice_no:s:0:0',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT bin_to_uuid(transaction_id,true) transaction_id 
					FROM tbl_transaction 
					WHERE document_no = '$document_no' AND transaction_type = 'Temp-In'";
			$result = sqlError($mysqli, __LINE__, $sql, 1);
			if ($result->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล document_no : ' . $document_no);
			}
			$transaction_id = $result->fetch_assoc()["transaction_id"];

			$document_no_new = $document_no;
			$gen = false;

			if ((stripos($document_no, "grn")) === FALSE) {
				$sql = "SELECT func_GenRuningNumber('grn',0) as document_no ;";
				$result = sqlError($mysqli, __LINE__, $sql, 1, 1);
				if ($result->num_rows === 0) {
					throw new Exception('ไม่พบข้อมูล document_no GRN ' . __LINE__);
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
				declaration_no = '$declaration_no',
				container_no = '$container_no',
				bl_no = '$bl_no',
				invoice_no = '$invoice_no',
				editing_at = null,
				editing_user_id = null
				WHERE transaction_id = uuid_to_bin('$transaction_id',true) AND transaction_type = 'Temp-In';";
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


			$sql = "INSERT IGNORE INTO tbl_inventory
			(pallet_no, case_tag_no, part_id, qty, gross_kg, net_per_pallet, measurement_cbm, certificate_no, invoice_no, coil_lot_no, remark, package_type,
			location_id, transaction_line_id, created_at, created_user_id)
			SELECT pallet_no, case_tag_no, part_id, qty, gross_kg, net_per_pallet, measurement_cbm, certificate_no, t1.invoice_no, coil_lot_no, remark, package_type,
			to_location_id, t1.transaction_line_id, NOW(), $cBy
			FROM tbl_transaction_line t1
			WHERE t1.transaction_id = uuid_to_bin('$transaction_id',true)
			ON DUPLICATE KEY UPDATE 
			pallet_no = VALUES(pallet_no),
			case_tag_no = VALUES(case_tag_no),
			part_id = VALUES(part_id),
			qty = VALUES(qty),
			gross_kg = VALUES(gross_kg),
			net_per_pallet = VALUES(net_per_pallet),
			measurement_cbm = VALUES(measurement_cbm),
			certificate_no = VALUES(certificate_no),
			invoice_no = VALUES(invoice_no),
			coil_lot_no = VALUES(coil_lot_no),
			remark = VALUES(remark),
			package_type = VALUES(package_type),
			transaction_line_id = VALUES(transaction_line_id),
			updated_at = NOW(),
			updated_user_id = $cBy;";
			// exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}

			$sql = "INSERT IGNORE INTO tbl_inventory_detail
			(part_tag_no, part_id, qty, net_per_pcs, remark, location_id, transaction_detail_id, inventory_id, created_at, created_user_id)
			SELECT t1.part_tag_no, t1.part_id, t1.qty, t1.net_per_pcs, t1.remark, t1.to_location_id, t1.transaction_detail_id, t3.inventory_id, NOW(), $cBy
			FROM tbl_transaction_detail t1
				INNER JOIN tbl_transaction_line t2 ON t1.transaction_line_id = t2.transaction_line_id
				INNER JOIN tbl_inventory t3 ON t2.transaction_line_id = t3.transaction_line_id
			WHERE t2.transaction_id = uuid_to_bin('$transaction_id',true)
			ON DUPLICATE KEY UPDATE 
			part_tag_no = VALUES(part_tag_no),
			part_id = VALUES(part_id),
			net_per_pcs = VALUES(net_per_pcs),
			remark = VALUES(remark),
			transaction_detail_id = VALUES(transaction_detail_id),
			updated_at = NOW(),
			updated_user_id = $cBy;";
			//exit($sql);
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


$mysqli->close();
exit();
