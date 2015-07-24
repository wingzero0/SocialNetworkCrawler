<?php
/**
 * User: kit
 */

require_once(__DIR__ . '/CodingGuys/autoload.php');

use CodingGuys\CGDumpFbCollection;

$options = getopt("s:e:");

$dumper = new CGDumpFbCollection();

$originFeedTimestampCol = $dumper->getMongoCollection($dumper->getFeedTimestampCollectionName());
$newFeedTimestampCol = $dumper->getTmpCollection($dumper->getFeedTimestampCollectionName());
$newPageCol = $dumper->getTmpCollection($dumper->getPageCollectionName());
$newFeedCol = $dumper->getTmpCollection($dumper->getFeedCollectionName());

$startDate = \DateTime::createFromFormat(\DateTime::ISO8601, $options["s"]);
$endDate = \DateTime::createFromFormat(\DateTime::ISO8601, $options["e"]);

$cursor = $originFeedTimestampCol->find(
        array(
            "batchTime" => array(
                "\$gte" => new \MongoDate($startDate->getTimestamp()),
                "\$lte" => new \MongoDate($endDate->getTimestamp())
            )
        )
    );

$originDB = $dumper->getMongoDB();
foreach($cursor as $feedTimestamp){
    $page = MongoDBRef::get($originDB, $feedTimestamp["fbPage"]);
    $newPageCol->update(
            array("_id" => $page["_id"]),
            $page,
            array("upsert" => true));

    $feed = MongoDBRef::get($originDB, $feedTimestamp["fbFeed"]);
    $newFeedCol->update(
            array("_id" => $feed["_id"]),
            $feed,
            array("upsert" => true));

    $newFeedTimestampCol->update(
        array("_id" => $feedTimestamp["_id"]),
        $feedTimestamp,
        array("upsert" => true));
}