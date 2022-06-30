<?php
if(!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');

$date1 = '2016-12-01';
$date2 = '2026-12-01';
// $date2 = '2027-01-01';
/*$dateRang = getDatesFromRange($date1,$date2);
$sqlArrayChunk = array_chunk($dateRang,1000);
$len = count($sqlArrayChunk);
include('php/connection.php');
for ($i=0; $i < $len; $i++) 
{ 
    $sql = "insert ignore into dl(`day`) values";
    $sql .=join(',',$sqlArrayChunk[$i]);
    $mysqli->query($sql);
}
closeDB($mysqli);*/
// var_dump($sqlArrayChunk);
function getDatesFromRange($startDate, $endDate)
{
    $return = array("('".$startDate."')");
    $start = $startDate;
    $i=1;
    if (strtotime($startDate) < strtotime($endDate))
    {
       while (strtotime($start) < strtotime($endDate))
        {
            $start = date('Y-m-d', strtotime($startDate.'+'.$i.' days'));
            $return[] = "('".$start."')";
            $i++;
        }
    }
    return $return;
}

function getDatesFromRange2($startDate, $endDate)
{
    $return = array();
    $return[] = $startDate;
    $start = $startDate;
    $i=1;
    if (strtotime($startDate) < strtotime($endDate))
    {
       while (strtotime($start) < strtotime($endDate))
        {
            $start = date('Y-m-d', strtotime($startDate.'+'.$i.' days'));
            $return[] = $start;
            $i++;
        }
    }
    return $return;
}
?>