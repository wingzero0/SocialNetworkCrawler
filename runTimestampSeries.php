<?php
/**
 * User: kit
 * Date: 18/05/15
 * Time: 20:21
 */

require_once(__DIR__ . '/facebook-php-sdk-v4/autoload.php');
require_once(__DIR__ . '/CodingGuys/autoload.php');

use CodingGuys\CGFeedStat;

if (isset($argv[1])){
    $windowSize = intval($argv[1]);
}else{
    $windowSize = 7;
}

if (isset($argv[2])){
    $filename = $argv[2];
}else{
    $filename = "fbReport".$windowSize.".csv";
}

if (isset($argv[3])){
    $city = $argv[3];
}else{
    $city = "mo";
}

//$endDate = new \DateTime();
$endDate = \DateTime::createFromFormat(\DateTime::ISO8601, "2015-09-23T00:00:00+0000");
$startDate = clone $endDate;
$startDate->sub(new \DateInterval('P'.$windowSize.'D'));


$obj = new CGFeedStat($startDate, $endDate, $filename);
$obj->timestampSeriesCount($city);

