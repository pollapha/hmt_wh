<?php

function explode_data($split, $data)
{
    $explode = explode($split, $data);
    $result = $explode[0];
    return $result;
}


function getSupplierID($mysqli, $data)
{
    $sql = "SELECT BIN_TO_UUID(supplier_id,true) as supplier_id from tbl_supplier_master where supplier_code='$data' limit 1;";

    $re1 = sqlError($mysqli, __LINE__, $sql);
    if ($re1->num_rows == 0) closeDBT($mysqli, 2, 'ERROR LINE ' . __LINE__ . "<br>ไม่พบข้อมูล Supplier " . $data);
    return  $re1->fetch_array(MYSQLI_ASSOC)['supplier_id'];
}

function getPartID($mysqli, $data)
{
    $sql = "SELECT BIN_TO_UUID(part_id,true) as part_id from tbl_part_master where part_no='$data' limit 1;";

    $re1 = sqlError($mysqli, __LINE__, $sql);
    if ($re1->num_rows == 0) closeDBT($mysqli, 2, 'ERROR LINE ' . __LINE__ . "<br>ไม่พบข้อมูล Part");
    return  $re1->fetch_array(MYSQLI_ASSOC)['part_id'];
}

function getTruckID($mysqli, $data)
{
    $sql = "SELECT BIN_TO_UUID(truck_id,true) as truck_id from tbl_truck_master where truck_number='$data' limit 1;";

    $re1 = sqlError($mysqli, __LINE__, $sql);
    if ($re1->num_rows == 0) closeDBT($mysqli, 2, 'ERROR LINE ' . __LINE__ . "<br>ไม่พบข้อมูล Truck");
    return  $re1->fetch_array(MYSQLI_ASSOC)['truck_id'];
}

function getTruckID2($mysqli, $data)
{
    $truck_number = $data['truck_number'];
    $truck_type = $data['truck_type'];
    $sql = "SELECT BIN_TO_UUID(truck_id,true) as truck_id from tbl_truck_master 
    where truck_number='$truck_number' AND truck_type='$truck_type' limit 1;";

    $re1 = sqlError($mysqli, __LINE__, $sql);
    if ($re1->num_rows == 0) closeDBT($mysqli, 2, 'ERROR LINE ' . __LINE__ . "<br>ไม่พบข้อมูล Truck");
    return  $re1->fetch_array(MYSQLI_ASSOC)['truck_id'];
}

function getDriverID($mysqli, $data)
{
    $sql = "SELECT BIN_TO_UUID(driver_id,true) as driver_id from tbl_driver_master where driver_name='$data' limit 1;";

    $re1 = sqlError($mysqli, __LINE__, $sql);
    if ($re1->num_rows == 0) closeDBT($mysqli, 2, 'ERROR LINE ' . __LINE__ . "<br>ไม่พบข้อมูล Driver");
    return  $re1->fetch_array(MYSQLI_ASSOC)['driver_id'];
}

function getPackageType($mysqli, $data)
{
    $sql = "SELECT package_type from tbl_package_master where package_code ='$data' limit 1;";

    $re1 = sqlError($mysqli, __LINE__, $sql);
    if ($re1->num_rows == 0) closeDBT($mysqli, 2, 'ERROR LINE ' . __LINE__ . "<br>ไม่พบข้อมูล Package No.");
    return  $re1->fetch_array(MYSQLI_ASSOC)['package_type'];
}

function getPackageTypeByPart($mysqli, $data)
{
    $sql = "SELECT package_type from tbl_part_master where part_id = uuid_to_bin('$data',true) limit 1;";
    $re1 = sqlError($mysqli, __LINE__, $sql);
    if ($re1->num_rows == 0) closeDBT($mysqli, 2, 'ERROR LINE ' . __LINE__ . "<br>ไม่พบข้อมูล Part No.");
    return  $re1->fetch_array(MYSQLI_ASSOC)['package_type'];
}


function getPackageIdByPart($mysqli, $data)
{
    $sql = "SELECT BIN_TO_UUID(package_id,true) as package_id from tbl_part_master where part_no='$data' limit 1;";

    $re1 = sqlError($mysqli, __LINE__, $sql);
    if ($re1->num_rows == 0) closeDBT($mysqli, 2, 'ERROR LINE ' . __LINE__ . "<br>ไม่พบข้อมูล Part");
    return  $re1->fetch_array(MYSQLI_ASSOC)['package_id'];
}

function getLocationID($mysqli, $data)
{
    $sql = "SELECT BIN_TO_UUID(location_id,true) as location_id from tbl_location_master where location_code='$data' limit 1;";
    $re1 = sqlError($mysqli, __LINE__, $sql);
    if ($re1->num_rows == 0) closeDBT($mysqli, 2, 'ERROR LINE ' . __LINE__ . "<br>ไม่พบข้อมูล Location");
    return  $re1->fetch_array(MYSQLI_ASSOC)['location_id'];
}

function getDestinationID($mysqli, $data)
{
    $sql = "SELECT BIN_TO_UUID(destination_id,true) as destination_id from tbl_destination_master where destination_code='$data' limit 1;";

    $re1 = sqlError($mysqli, __LINE__, $sql);
    if ($re1->num_rows == 0) closeDBT($mysqli, 2, 'ERROR LINE ' . __LINE__ . "<br>ไม่พบข้อมูล Destination");
    return  $re1->fetch_array(MYSQLI_ASSOC)['destination_id'];
}


function selectColumnFromArray($dataAr, $columnAr)
{
    $returnData = array();
    for ($i = 0, $len = count($dataAr); $i < $len; $i++) {
        $ar = array();
        for ($i2 = 0, $len2 = count($columnAr); $i2 < $len2; $i2++) {
            $ar[$columnAr[$i2]] = $dataAr[$i][$columnAr[$i2]];
        }
        $returnData[] = $ar;
    }
    return $returnData;
}

function group_by($key, $data)
{
    $result = array();

    foreach ($data as $val) {
        if (array_key_exists($key, $val)) {
            $result[$val[$key]][] = $val;
        } else {
        }
    }

    return $result;
}
