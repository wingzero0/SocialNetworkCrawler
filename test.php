<?php
/**
 * User: kit
 * Date: 07/03/15
 * Time: 17:34
 */

require_once(__DIR__ . '/CodingGuys/autoload.php');
require_once(__DIR__ . '/vendor/autoload.php');

$client = new MongoDB\Client("mongodb://localhost:27017");
$collection = $client->Mnemono->test;

$queryCondition = array("test" => 1);
$obj = array(
    "test" => 1,
    "objField" => array(
        "lv2" => 2,
        "next" => array("lv3" => 3)
    ),
    "rand" => rand()
);

$result = $collection->findOneAndUpdate(
    $queryCondition,
    array("\$set" => $obj),
    array("upsert" => true)
);

var_dump($result);
$obj = new \ArrayObject($result);
var_dump($obj);