<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
require_once('../php/connection.php');
require '../vendor/autoload.php';
include('../Inventory/dataReport.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

sendOnhand($mysqli);


function sendOnhand($mysqli)
{
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

		// $start_date = date('Y-m-d');
		// $stop_date = date('Y-m-d');

		$start_date = date('Y-m-01');
		$stop_date = date("Y-m-t", strtotime($start_date));


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
		// $mail->addAddress('pollapha.d@ttv-supplychain.com');
		     //Add a recipient
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

		date_default_timezone_set("Asia/Bangkok");
		$date = date('Y-m-d H:i:s');
		$msg = 'Message has been sent ' . $date;
		echo ($msg);

		if (is_file($filename)) {
			unlink($filename);
		}
	} catch (Exception $e) {
		$mysqli->rollback();

		date_default_timezone_set("Asia/Bangkok");
		$date = date('Y-m-d H:i:s');
		$msg = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
		echo ($msg);
		$mysqli->close();
		exit();
	}
}

$mysqli->close();
exit();
