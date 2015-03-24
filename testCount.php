<?php
/**
 * User: kit
 * Date: 24/03/15
 * Time: 17:15
 */


require_once(__DIR__ . '/facebook-php-sdk-v4/autoload.php');
require_once(__DIR__ . '/CodingGuys/autoload.php');

use CodingGuys\CGFeedStat;

$obj = new CGFeedStat(new \DateTime());
$obj->basicCount();