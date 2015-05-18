<?php
/**
 * User: kit
 * Date: 18/05/15
 * Time: 20:21
 */

require_once(__DIR__ . '/facebook-php-sdk-v4/autoload.php');
require_once(__DIR__ . '/CodingGuys/autoload.php');

use CodingGuys\CGFeedStat;

$endDate = new \DateTime();
$startDate = clone $endDate;
$startDate->sub(new \DateInterval('P10D'));

$obj = new CGFeedStat($startDate, $endDate);
$obj->timestampSeriesCount(100);
