<?php
/**
 * User: kit
 * Date: 24/03/15
 * Time: 17:15
 */


require_once(__DIR__ . '/facebook-php-sdk-v4/autoload.php');
require_once(__DIR__ . '/CodingGuys/autoload.php');

use CodingGuys\CGFeedStat;

$endDate = new \DateTime();
$startDate = clone $endDate;
$startDate->sub(new \DateInterval('P1D'));

$obj = new CGFeedStat($startDate, $endDate);
$obj->topNResult(100);
