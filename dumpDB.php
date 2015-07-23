<?php
/**
 * User: kit
 */

require_once(__DIR__ . '/CodingGuys/autoload.php');

use CodingGuys\CGDumpFbCollection;

$originClient = new \MongoClient();
$originCol = $originClient->selectCollection("Mnemono","FacebookFeedTimestamp");

$newClient = new \MongoClient();
$newFeedTimestampCol = $newClient->selectCollection("MnemonoDump", "FacebookFeedTimestamp");

$cursor = $originCol->find(
        array("batchTime" =>
            array("\$lte" => new \MongoDate()))
    );

$originDB = $originClient->selectDB("Mnemono");
$newDB = $newClient->selectDB("MnemonoDump");
foreach($cursor as $feedTimestamp){
    $page = MongoDBRef::get($originDB, $feedTimestamp["fbPage"]);
    $newClient->selectCollection("MnemonoDump", "FacebookPage")
        ->update(
            array("_id" => $page["_id"]),
            $page,
            array("upsert" => true));

    $feed = MongoDBRef::get($originDB, $feedTimestamp["fbFeed"]);
    $newClient->selectCollection("MnemonoDump", "FacebookFeed")
        ->update(
            array("_id" => $feed["_id"]),
            $feed,
            array("upsert" => true));

    $newFeedTimestampCol->update(
        array("_id" => $feedTimestamp["_id"]),
        $feedTimestamp,
        array("upsert" => true));
}