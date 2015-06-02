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


//$endDate = DateTime::createFromFormat(\DateTime::ISO8601,"2015-05-17T06:00:00+0000");
$endDate = new \DateTime();
$startDate = clone $endDate;
$startDate->sub(new \DateInterval('P'.$windowSize.'D'));


$obj = new CGFeedStat($startDate, $endDate);
$obj->timestampSeriesCount();

