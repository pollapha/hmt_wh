<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'Transaction'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'Transaction'}[0] == 0) {
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
	if ($_SESSION['xxxRole']->{'Transaction'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {
	} else if ($type == 12) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'Transaction'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'Transaction'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'Transaction'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {
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
		ROW_NUMBER() OVER (partition by document_no order by document_date DESC, document_no DESC, transaction_line_id ASC) row_no,
		document_no, t1.document_date, transaction_type, t1.delivery_date, supplier_code,
        order_no, dos_no, t2.work_order_no,
		pallet_no, case_tag_no, fg_tag_no, part_no, part_name,
		qty, gross_kg, net_per_pallet, measurement_cbm, certificate_no, coil_lot_no, t2.invoice_no,
		t2.remark,
		t4.location_code as from_location,
		t5.location_code as to_location,
		date_format(t1.created_at, '%d-%m-%Y %h:%s:%i') created_at, t8.user_fName created_by,
		date_format(t1.updated_at, '%d-%m-%Y %h:%s:%i') updated_at, t9.user_fName updated_by
	FROM
		tbl_transaction t1
			INNER JOIN tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
			INNER JOIN tbl_part_master t3 ON t2.part_id = t3.part_id
			LEFT JOIN tbl_location_master t4 ON t2.from_location_id = t4.location_id
			LEFT JOIN tbl_location_master t5 ON t2.to_location_id = t5.location_id
            LEFT JOIN tbl_order_header t6 ON t1.order_header_id = t6.order_header_id
            LEFT JOIN tbl_supplier_master t7 ON t3.supplier_id = t7.supplier_id
			LEFT JOIN tbl_user t8 ON t1.created_user_id = t8.user_id
			LEFT JOIN tbl_user t9 ON t1.updated_user_id = t9.user_id
	WHERE t2.status = 'Complete'
		$sqlWhere
	order by document_date DESC, t1.created_at DESC, transaction_line_id ASC;";
	return $sql;
}

$mysqli->close();
exit();
