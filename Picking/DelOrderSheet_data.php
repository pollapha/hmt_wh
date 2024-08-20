<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'DelOrderSheet'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'DelOrderSheet'}[0] == 0) {
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



include('../php/xlsxwriter.class.php');
include('../common/common.php');
include('../php/connection.php');
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
		$re1 = select_group($mysqli, $data);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'DelOrderSheet'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {
	} else if ($type == 12) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'DelOrderSheet'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'DelOrderSheet'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
		$dataParams = array(
			'obj',
			'obj=>dos_no:s:0:1',
			'obj=>order_no:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {


			$sql = "SELECT bin_to_uuid(order_header_id,true) order_header_id 
			FROM tbl_order_header
			WHERE dos_no = '$dos_no' AND order_no = '$order_no';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			$order_header_id = $re1->fetch_array(MYSQLI_ASSOC)['order_header_id'];

			$sql = "SELECT order_no
			FROM tbl_order_header
			WHERE order_header_id = uuid_to_bin('$order_header_id',true) 
			AND (order_status = 'Delivered' OR order_status = 'In-transit');";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('ไม่สามารถยกเลิกได้<br>เนื่องจากทำการออก GTN แล้ว');
			}

			$sql = "SELECT part_tag_no
			FROM tbl_order
			WHERE order_header_id = uuid_to_bin('$order_header_id',true) AND repack = 'Yes';";
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
	if ($_SESSION['xxxRole']->{'DelOrderSheet'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 50 && $type <= 60) //export
{
	if ($_SESSION['xxxRole']->{'OrderRepack'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 51) {
		if (!isset($_REQUEST['start_date']) || !isset($_REQUEST['stop_date']))
			closeDBT($mysqli, 2, 'ข้อมูลไม่ถูกต้อง 1');

		$start_date = checkTXT($mysqli, $_REQUEST['start_date']);
		$stop_date = checkTXT($mysqli, $_REQUEST['stop_date']);

		$Date = date("Ymd");
		$randomString = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
		$filename = "Data Order " . $Date . "_" . $randomString . ".xlsx";


		$data = ['start_date' => $start_date, 'stop_date' => $stop_date];
		$sqlexport = sqlexport($mysqli, $data);

		if ($sqlexport != '') {
			$data_export = [];

			if ($re1 = $mysqli->query($sqlexport)) {
				if ($re1->num_rows > 0) {

					$writer = new XLSXWriter();

					$header = array(
						'Document No.' => 'string',
						'Document Date' => 'string',
						'Transaction' => 'string',
						'Location' => 'string',
						'Part No.' => 'string',
						'Part Name' => 'string',
						'Qty' => 'integer',
						'Remark' => 'string'
					);

					while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
						$linedata_export = array(
							$row['order_no'], $row['order_date'], $row['order_status'], $row['location_code'],
							$row['item_code'], $row['Item_name'], $row['part_qty'], $row['remark']
						);
						array_push($data_export, $linedata_export);
					}

					$writer->writeSheetHeader('Data', $header);
					foreach ($data_export as $row) {
						$writer->writeSheetRow('Data', $row);
					}

					header('Content-disposition: attachment; filename="' . XLSXWriter::sanitize_filename($filename) . '"');
					header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
					header('Content-Transfer-Encoding: binary');
					header('Cache-Control: must-revalidate');
					header('Pragma: public');

					$writer->writeToStdOut();
				} else {
					echo json_encode(array('ch' => 2, 'data' => "ไม่พบข้อมูลในระบบ"));
				}
			} else {
				echo json_encode(array('ch' => 2, 'data' => "Error SP"));
			}
		} else {
			echo json_encode(array('ch' => 2, 'data' => "Error SP"));
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
			$where[] = "AND DATE(order_date) between DATE('$data[start_date]') and DATE('$data[stop_date]')";
		}

		$sqlWhere = join(' and ', $where);


		$sql = "SELECT 
	ROW_NUMBER() OVER (partition by dos_no order by delivery_date DESC, dos_no, work_order_no, fg_tag_no ASC) AS row_num,
	dos_no, document_date,
	order_no, order_date, delivery_date, order_status, delivery_status,
    part_no,
    part_name,
    t2.repack,
    t2.work_order_no,
    t2.fg_tag_no,
    t2.case_tag_no,
    SUM(t2.net_per_pcs) net_per_pallet,
    SUM(t2.qty) qty,
    t2.package_no,
    t6.location_code,
    supplier_code
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
    t2.status = 'Complete' AND dos_no != ''
	$sqlWhere
GROUP BY work_order_no, fg_tag_no
ORDER BY delivery_date DESC, dos_no DESC, work_order_no ASC, fg_tag_no ASC;";
		//exit($sql);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		return $re1;
	} catch (Exception $e) {
		$mysqli->rollback();
		closeDBT($mysqli, 2, $e->getMessage());
	}
}


function sqlexport($mysqli, $data)
{
	$where = [];

	$where[] = "AND DATE(order_date) between DATE('$data[start_date]') and DATE('$data[stop_date]')";

	$sqlWhere = join(' and ', $where);

	$sql = "SELECT 
	BIN_TO_UUID(t1.order_header_id, TRUE) AS order_header_id,
	order_no, order_date,
	BIN_TO_UUID(t2.transaction_line_id, TRUE) AS transaction_line_id,
	t3.item_code,
	t3.Item_name,
	t2.part_qty,
	t4.location_code,
	t2.remark,
	order_status
FROM
	tbl_order_header t1
		LEFT JOIN
	tbl_order_header_line t2 ON t1.order_header_id = t2.order_header_id
		INNER JOIN
	alt_freezone.tbl_item t3 ON t2.part_id = t3.item_id
		INNER JOIN
	alt_freezone.tbl_location t4 ON t1.location_id = t4.location_id
WHERE
	t1.order_status = 'In'
		AND t2.status = 'Complete' 
	$sqlWhere
order by order_date ASC, order_no ASC, transaction_line_id ASC;";
	//exit($sql);
	return $sql;
}

$mysqli->close();
exit();
