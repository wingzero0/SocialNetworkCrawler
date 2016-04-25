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
foreach($ret as $key => $record){
    $mnemono = $record["mnemono"];
    $fbID = $record["fbID"];
    $cat = $mnemono["category"];
    $city = $mnemono["location"]["city"];
    $country = $mnemono["location"]["country"];
    echo $cat . "\t" . $city . "\t" . $country . "\t"  .
        $fbID . "\t" .
        $key . "\n";
}