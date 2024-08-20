<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'TransactionBilling'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'TransactionBilling'}[0] == 0) {
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
require '../vendor/autoload.php';
include('../Inventory/dataReport.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

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
	} else if ($type == 2) {

		$dataParams = array(
			'obj',
			'obj=>start_date:s:5',
			'obj=>stop_date:s:5',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$data = ['start_date' => $start_date, 'stop_date' => $stop_date];
			$sql = select_group_billing($mysqli, $data);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$dataTransactionArray = array();
			while ($row = $re1->fetch_array(MYSQLI_NUM)) {
				$dataTransactionArray[] = $row;
			}

			include('excel/excel_shipout.php');

			$mysqli->commit();

			closeDBT($mysqli, 1, $filename);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 3) {

		$dataParams = array(
			'obj',
			'obj=>start_date:s:5',
			'obj=>stop_date:s:5',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));


		$mysqli->autocommit(FALSE);
		try {

			$sql = select_group_onhand_item($mysqli);
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$dataByItemArray = array();
			while ($row = $re1->fetch_array(MYSQLI_NUM)) {
				$dataByItemArray[] = $row;
			}


			$sql = select_group_onhand_part($mysqli);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$dataByPartArray = array();
			while ($row = $re1->fetch_array(MYSQLI_NUM)) {
				$dataByPartArray[] = $row;
			}

			if ($start_date == '') {
				$start_date = date('Y-m-01');
				$stop_date = date("Y-m-t", strtotime($start_date));
			}

			$data = ['start_date' => $start_date, 'stop_date' => $stop_date];
			$sql = select_group_billing($mysqli, $data);
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$dataTransactionArray = array();
			while ($row = $re1->fetch_array(MYSQLI_NUM)) {
				$dataTransactionArray[] = $row;
			}

			$sql = select_group_onhand_package($mysqli);
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$dataByPackageArray = array();
			while ($row = $re1->fetch_array(MYSQLI_NUM)) {
				$dataByPackageArray[] = $row;
			}


			$sql = select_group_onhand_steelpipe($mysqli);
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$dataSteelArray = array();
			while ($row = $re1->fetch_array(MYSQLI_NUM)) {
				$dataSteelArray[] = $row;
			}

			$sql = select_group_package_steel($mysqli);
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$dataPackageSteelArray = array();
			while ($row = $re1->fetch_array(MYSQLI_NUM)) {
				$dataPackageSteelArray[] = $row;
			}


			$sql = select_group_package_wooden($mysqli);
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$dataPackageWoodenArray = array();
			while ($row = $re1->fetch_array(MYSQLI_NUM)) {
				$dataPackageWoodenArray[] = $row;
			}

			include('excel/excel_onhand_all.php');

			$mysqli->commit();

			closeDBT($mysqli, 1, $filename);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 4) {

		$dataParams = array(
			'obj',
			'obj=>start_date:s:5',
			'obj=>stop_date:s:5',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));


		$mysqli->autocommit(FALSE);
		try {

			$sql = select_group_onhand_item($mysqli);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$dataByItemArray = array();
			while ($row = $re1->fetch_array(MYSQLI_NUM)) {
				$dataByItemArray[] = $row;
			}


			$sql = select_group_onhand_part($mysqli);
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$dataByPartArray = array();
			while ($row = $re1->fetch_array(MYSQLI_NUM)) {
				$dataByPartArray[] = $row;
			}

			if ($start_date == '') {
				$start_date = date('Y-m-01');
				$stop_date = date("Y-m-t", strtotime($start_date));
				// $start_date = date('Y-m-d');
				// $stop_date = date('Y-m-d');
			}

			$data = ['start_date' => $start_date, 'stop_date' => $stop_date];
			$sql = select_group_billing($mysqli, $data);
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$dataTransactionArray = array();
			while ($row = $re1->fetch_array(MYSQLI_NUM)) {
				$dataTransactionArray[] = $row;
			}

			$sql = select_group_onhand_package($mysqli);
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$dataByPackageArray = array();
			while ($row = $re1->fetch_array(MYSQLI_NUM)) {
				$dataByPackageArray[] = $row;
			}


			$sql = select_group_onhand_steelpipe($mysqli);
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$dataSteelArray = array();
			while ($row = $re1->fetch_array(MYSQLI_NUM)) {
				$dataSteelArray[] = $row;
			}

			$sql = select_group_package_steel($mysqli);
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$dataPackageSteelArray = array();
			while ($row = $re1->fetch_array(MYSQLI_NUM)) {
				$dataPackageSteelArray[] = $row;
			}


			$sql = select_group_package_wooden($mysqli);
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$dataPackageWoodenArray = array();
			while ($row = $re1->fetch_array(MYSQLI_NUM)) {
				$dataPackageWoodenArray[] = $row;
			}

			include('excel/excel_onhand_all.php');


			//Create an instance; passing `true` enables exceptions
			$mail = new PHPMailer(true);

			$mail->SMTPOptions = array(
				'ssl' => array(
					'verify_peer' => false,
					'verify_peer_name' => false,
					'allow_self_signed' => true
				)
			);

			//Server settings
			// $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
			$mail->SMTPDebug  = 0;
			$mail->isSMTP();                                            //Send using SMTP
			$mail->Host       = 'smtp.office365.com';                     //Set the SMTP server to send through
			$mail->SMTPAuth   = true;                                   //Enable SMTP authentication
			$mail->Username   = 'ttv.autoinbox@all2gether.net';       //SMTP username
			$mail->Password   = '7yM_6hO%5iN*6O';                               //SMTP password
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;            //Enable implicit TLS encryption
			$mail->Port       = 587;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

			//Recipients
			$mail->setFrom('ttv.autoinbox@all2gether.net', 'TTV');
			// $mail->addAddress('pollapha.d@ttv-supplychain.com');     //Add a recipient
			
			$mail->addAddress('nopadol.shawpaknoi@highly-marelli.com');          //Name is optional
			$mail->addAddress('supaporn.nonthatham@highly-marelli.com');          //Name is optional
			$mail->addAddress('jutamart.tankulrat@highly-marelli.com');          //Name is optional
			$mail->addAddress('aomjai.banjongsin@highly-marelli.com');          //Name is optional
			$mail->addAddress('paweena.rujipornsakul@highly-marelli.com');          //Name is optional
			$mail->addAddress('phastheema.tungmunpearn@highly-marelli.com');          //Name is optional
			$mail->addAddress('jutamad.choterattanatamrong@highly-marelli.com');          //Name is optional

			$mail->addAddress('rittipong.k@ttv-supplychain.com');          //Name is optional
			$mail->addAddress('witchara.s@ttv-supplychain.com');          //Name is optional
			$mail->addAddress('sirinart.j@ttv-supplychain.com');          //Name is optional
			// //Attachments
			$mail->addAttachment($filename);         //Add attachments
			// //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name

			//Content
			$mail->isHTML(true);                                  //Set email format to HTML
			$mail->CharSet = "utf-8";
			// $mail_title = " (New Costing Request.)";
			$mail->Subject = 'Report Inventory Onhand HMT - Auto Message';
			$mail->Body = "
			<html>
				<body>
				<br>
				<br>             
				<div>
				<p>[CAUTION] This email originated from outside of the organization. Do not click links or open attachments unless you recognize the sender and know the content is safe.</p>
				<br>
				<p>Please see attachment for onhand data update.</p>
				</div>              
				</body>
			</html>
			";

			$mail->send();
			$mysqli->commit();

			$date = date('Y-m-d His');
			$msg = 'ข้อความถูกส่งสำเร็จ';
			closeDBT($mysqli, 1, ['filename' => $filename, 'msg' => $msg]);
			if (is_file($filename)) {
				unlink($filename);
			}
		} catch (Exception $e) {
			$mysqli->rollback();
			$date = date('Y-m-d His');
			$msg = 'ข้อความส่งไม่สำเร็จ';
			closeDBT($mysqli, 2, ['msg' => $msg]);
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'TransactionBilling'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {
	} else if ($type == 12) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'TransactionBilling'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'TransactionBilling'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'TransactionBilling'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
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
			$where[] = "AND DATE(t1.delivery_date) between DATE('$data[start_date]') and DATE('$data[stop_date]')";
		}

		$sqlWhere = join(' and ', $where);
	} catch (Exception $e) {
		$mysqli->rollback();
		closeDBT($mysqli, 2, $e->getMessage());
	}

	$sql = "SELECT 
		ROW_NUMBER() OVER (partition by document_no order by document_date DESC, document_no DESC, transaction_line_id ASC) row_no,
		ROW_NUMBER() OVER (order by document_date DESC, document_no DESC, transaction_line_id ASC) row_num,
		document_no, t1.document_date, transaction_type, t1.delivery_date, supplier_code,
        order_no, dos_no, t2.work_order_no,
		pallet_no, case_tag_no, fg_tag_no, part_no, part_name,
		qty, gross_kg, net_per_pallet, measurement_cbm, certificate_no, t2.invoice_no,
		t2.remark,
		t4.location_code as from_location,
		t5.location_code as to_location
	FROM
		tbl_transaction t1
			INNER JOIN tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
			INNER JOIN tbl_part_master t3 ON t2.part_id = t3.part_id
			LEFT JOIN tbl_location_master t4 ON t2.from_location_id = t4.location_id
			LEFT JOIN tbl_location_master t5 ON t2.to_location_id = t5.location_id
            LEFT JOIN tbl_order_header t6 ON t1.order_header_id = t6.order_header_id
            LEFT JOIN tbl_supplier_master t7 ON t3.supplier_id = t7.supplier_id
	WHERE (t1.transaction_type = 'Picking' OR t1.transaction_type = 'Out')
		AND t2.status = 'Complete'
		$sqlWhere
	order by document_date DESC, document_no DESC, transaction_line_id ASC;";
	return $sql;
}

$mysqli->close();
exit();
