<?php
/**
 * User: kit
 * Date: 24/03/15
 * Time: 17:15
 */

require_once(__DIR__ . '/config.php');
setDefaultConfig();
require_once(__DIR__ . '/CodingGuys/autoload.php');
require_once(__DIR__ . '/vendor/autoload.php');

use CodingGuys\Stat\FbTopNReport;

$endDate = new \DateTime();
$startDate = clone $endDate;
$startDate->sub(new \DateInterval('P1D'));

$obj = new FbTopNReport($startDate, $endDate, null);
$obj->topNResult(100);
