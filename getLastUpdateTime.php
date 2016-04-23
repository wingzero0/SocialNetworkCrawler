<?php
/**
 * User: kit
 * Date: 23/04/2016
 * Time: 6:35 PM
 */

require_once(__DIR__ . '/CodingGuys/autoload.php');

use CodingGuys\CGPageStat;

$stat = new CGPageStat();
$ret = $stat->findAllPageLastUpdateTime();
print_r($ret);