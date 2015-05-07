<?php
/**
 * User: kit
 * Date: 24/03/15
 * Time: 17:15
 */


require_once(__DIR__ . '/facebook-php-sdk-v4/autoload.php');
require_once(__DIR__ . '/CodingGuys/autoload.php');

use CodingGuys\CGFeedStat;

$startDate = new \DateTime();
$startDate->setDate(2015, 4, 25);
$startDate->setTime(0,0,0);
$endDate = new \DateTime();
$endDate->setDate(2015, 4, 27);
$endDate->setTime(0,0,0);
$obj = new CGFeedStat($startDate, $endDate);
$obj->topNResult(100);
//$obj->timestampSeriesCount();
