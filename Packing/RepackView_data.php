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
		$sql = select_group($mysqli, $data);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		// $re1 = select_group($mysqli, $data);
		// closeDBT($mysqli, 1, $re1);
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'Repack'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'Repack'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'Repack'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else if ($type == 32) {

		$dataParams = array(
			'obj',
			'obj=>document_no:s:0:1',
			'obj=>order_no:s:0:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT bin_to_uuid(transaction_id,true) transaction_id, bin_to_uuid(order_header_id,true) order_header_id
			FROM tbl_transaction 
			WHERE document_no = '$document_no';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล document_no : ' . $document_no);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$transaction_id = $row['transaction_id'];
			}

			$sql = "UPDATE tbl_transaction 
				SET transaction_type = 'Temp-Packing',
				updated_user_id = $cBy,
				updated_at = now()
				WHERE transaction_id = uuid_to_bin('$transaction_id',true)";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}


			$sql = "SELECT bin_to_uuid(order_header_id,true) order_header_id FROM tbl_order_header WHERE order_no = '$order_no';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			$order_header_id = $re1->fetch_array(MYSQLI_ASSOC)['order_header_id'];

			$sql = "SELECT order_no
			FROM tbl_order_header
			WHERE order_header_id = uuid_to_bin('$order_header_id',true)
				AND (order_status = 'Delivered' OR order_status = 'In-transit');";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('ไม่สามารถยกเลิกได้<br>เนื่องจากทำการออก GTN แล้ว');
			}

			$sql = "SELECT part_tag_no
			FROM tbl_order
			WHERE order_header_id = uuid_to_bin('$order_header_id',true)
				AND repack = 'Yes';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('ไม่สามารถแก้ไขได้<br>เนื่องจากคอนเฟิร์ม Repack แล้ว');
			}


			$sql = "UPDATE tbl_order_header 
				SET order_status = 'Packing',
				delivery_status = 'Pending',
				updated_user_id = $cBy,
				updated_at = now()
				WHERE order_no = '$order_no';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			$sql = "UPDATE tbl_order
				SET repack = 'No',
				fg_tag_no = NULL,
				updated_user_id = $cBy,
				updated_at = now()
				WHERE order_header_id = uuid_to_bin('$order_header_id',true);";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			/* Transaction */


			$sql = "SELECT bin_to_uuid(t1.transaction_detail_id,true) transaction_detail_id
			FROM tbl_transaction_detail t1
			INNER JOIN tbl_transaction_line t2 ON t1.transaction_line_id = t2.transaction_line_id
			WHERE transaction_id = uuid_to_bin('$transaction_id',true);";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล  ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$transaction_detail_id = $row['transaction_detail_id'];

				$sql = "DELETE FROM tbl_transaction_detail
				WHERE transaction_detail_id = uuid_to_bin('$transaction_detail_id',true);";

				// exit($sql);
				sqlError($mysqli, __LINE__, $sql, 1, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
				}
			}

			$sql = "DELETE FROM tbl_transaction_line
			WHERE transaction_id = uuid_to_bin('$transaction_id',true);";
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
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'Repack'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 50 && $type <= 60) //export
{
	if ($_SESSION['xxxRole']->{'Repack'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 51) {
		if (!isset($_REQUEST['start_date']) || !isset($_REQUEST['stop_date']))
			closeDBT($mysqli, 2, 'ข้อมูลไม่ถูกต้อง 1');

		$start_date = checkTXT($mysqli, $_REQUEST['start_date']);
		$stop_date = checkTXT($mysqli, $_REQUEST['stop_date']);

		$Date = date("Ymd");
		$randomString = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
		$filename = "Data Repack " . $Date . "_" . $randomString . ".xlsx";


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
							$row['document_no'], $row['document_date'], $row['transaction_type'], $row['location_code'],
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
			$where[] = "AND DATE(document_date) between DATE('$data[start_date]') and DATE('$data[stop_date]')";
		}

		$sqlWhere = join(' and ', $where);
	} catch (Exception $e) {
		$mysqli->rollback();
		closeDBT($mysqli, 2, $e->getMessage());
	}



	$sql = "WITH a AS (
SELECT
	t1.work_order_no, case_tag_no, fg_tag_no, SUM(qty) total_qty, SUM(net_per_pallet) total_net,
    certificate_no, t2.invoice_no
FROM
	tbl_transaction_line t1
		inner join tbl_transaction t2 ON t1.transaction_id = t2.transaction_id
WHERE
	t2.transaction_type = 'Packing'
		AND t1.status = 'Complete'
	$sqlWhere
group by t1.work_order_no, fg_tag_no
ORDER BY t1.work_order_no, fg_tag_no
)
SELECT 
	ROW_NUMBER() OVER (partition by document_no order by t1.document_date DESC, document_no DESC, t2.work_order_no, t2.fg_tag_no, t2.case_tag_no ASC) as row_no,
	t1.document_date,
	t1.document_no,
	order_no, 
	order_date, 
	t6.delivery_date,
    part_no,
    part_name,
	t2.work_order_no,
    t2.case_tag_no,
    t5.location_code,
	if(ROW_NUMBER() OVER (partition by fg_tag_no order by t1.document_date DESC, document_no DESC, t2.work_order_no, t2.case_tag_no, t2.fg_tag_no ASC)=1,a.total_net,'') total_net,
    if(ROW_NUMBER() OVER (partition by fg_tag_no order by t1.document_date DESC, document_no DESC, t2.work_order_no, t2.case_tag_no, t2.fg_tag_no ASC)=1,a.total_qty,'') total_qty,
    t2.net_per_pallet,
    t2.qty qty_per_pallet,
    t3.net_per_pcs net_weight_pcs,
    t2.fg_tag_no,
    t2.certificate_no,
    t2.invoice_no
FROM
    tbl_transaction t1
        INNER JOIN
    tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
		INNER JOIN
	a ON t2.work_order_no = t2.work_order_no AND t2.fg_tag_no = a.fg_tag_no
        INNER JOIN
    tbl_transaction_detail t3 ON t2.transaction_line_id = t3.transaction_line_id
        INNER JOIN
    tbl_part_master t4 ON t2.part_id = t4.part_id
        INNER JOIN
    tbl_location_master t5 ON t2.from_location_id = t5.location_id
		INNER JOIN
	tbl_order_header t6 ON t1.order_header_id = t6.order_header_id
WHERE  t1.transaction_type = 'Packing'
        AND t2.status = 'Complete'
	$sqlWhere
GROUP BY t2.work_order_no, fg_tag_no, t2.case_tag_no
order by t1.document_date DESC, document_no DESC, t2.work_order_no, t2.fg_tag_no, t2.case_tag_no ASC;";
	//exit($sql);
	return $sql;
	// $re1 = sqlError($mysqli, __LINE__, $sql, 1);
	// $value = jsonRow($re1, false, 0);
	// $data = group_by('document_no', $value); //group datatable tree
	// $dateset = array();
	// $c = 1;
	// foreach ($data as $key1 => $value1) {
	// 	$sub = selectColumnFromArray($value1, array(
	// 		'row_num',
	// 		'work_order_no',
	// 		'part_no',
	// 		'part_name',
	// 		'case_tag_no',
	// 		'location_code',
	// 		'total_net_weight',
	// 		'total_qty',
	// 		'net_weight_pcs',
	// 		'snp_per_rack',
	// 		'net_weight',
	// 		'certificate_no',
	// 		'coil_lot_no',
	// 		'fg_tag_no'
	// 	)); //ที่จะให้อยู่ในตัว Child rows
	// 	$c2 = 1;
	// 	foreach ($sub as $key2 => $value2) {
	// 		$sub[$key2]['document_no'] = $c2;
	// 		$sub[$key2]['Is_Header'] = 'NO';
	// 		$c2++;
	// 	}

	// 	$dateset[] =  array(
	// 		"No" => $c, 'Is_Header' => 'YES', "document_no" => $key1,
	// 		"document_date" => $value1[0]['document_date'],
	// 		"order_no" => $value1[0]['order_no'],
	// 		"order_date" => $value1[0]['order_date'],
	// 		"delivery_date" => $value1[0]['delivery_date'],
	// 		"invoice_no" => $value1[0]['invoice_no'],
	// 		"transaction_type" => $value1[0]['transaction_type'],
	// 		'Total_Item' => count($value1), "open" => 1, "data" => $sub
	// 	);
	// 	$c++;
	// }
	// return $dateset;
}

function sqlexport($mysqli, $data)
{
	$where = [];

	$where[] = "AND DATE(document_date) between DATE('$data[start_date]') and DATE('$data[stop_date]')";

	$sqlWhere = join(' and ', $where);

	$sql = "SELECT 
	BIN_TO_UUID(t1.transaction_id, TRUE) AS transaction_id,
	document_no, document_date,
	BIN_TO_UUID(t2.transaction_line_id, TRUE) AS transaction_line_id,
	t3.item_code,
	t3.Item_name,
	t2.part_qty,
	t4.location_code,
	t2.remark,
	transaction_type
FROM
	tbl_transaction t1
		LEFT JOIN
	tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
		INNER JOIN
	alt_freezone.tbl_item t3 ON t2.part_id = t3.item_id
		INNER JOIN
	alt_freezone.tbl_location t4 ON t1.location_id = t4.location_id
WHERE
	t1.transaction_type = 'In'
		AND t2.status = 'Complete' 
	$sqlWhere
order by document_date ASC, document_no ASC, transaction_line_id ASC;";
	//exit($sql);
	return $sql;
}

$mysqli->close();
exit();
