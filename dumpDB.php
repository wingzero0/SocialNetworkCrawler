<?php
/**
 * User: kit
 */

require_once(__DIR__ . '/CodingGuys/autoload.php');

use CodingGuys\FbDocumentManager\FbDocumentManager;
use CodingGuys\FbRepo\FbFeedTimestampRepo;

const TMP_DB_NAME = 'MnemonoDump';

$options = getopt("s:e:");

$oldDM = new FbDocumentManager();
$originFeedTimestampCol = $oldDM->getFeedTimestampCollection();

$newDM = new FbDocumentManager(TMP_DB_NAME);
// TODO migrate the new php mongo lib
$newDM->getMongoDB()->drop();

$newFeedTimestampCol = $newDM->getFeedTimestampCollection();
$newPageCol = $newDM->getPageCollection();
$newFeedCol = $newDM->getFeedCollection();

$startDate = \DateTime::createFromFormat(\DateTime::ISO8601, $options["s"]);
$endDate = \DateTime::createFromFormat(\DateTime::ISO8601, $options["e"]);

$repo = new FbFeedTimestampRepo($oldDM);
$cursor = $repo->findByDateRange($startDate, $endDate);

$originDB = $oldDM->getMongoDB();

$newPageCol->createIndex(array("fbID" => 1));
$newFeedCol->createIndex(array("fbID" => 1));
$newFeedTimestampCol->createIndex(array("fbPage.\$id" => -1));
$newFeedTimestampCol->createIndex(array("batchTime" => 1));
$newFeedTimestampCol->createIndex(array("batchTime" => -1));
$newFeedTimestampCol->createIndex(array("fbPage.\$id" => -1, "batchTime" => -1));
$newFeedTimestampCol->createIndex(array("fbFeed.\$id" => -1, "batchTime" => -1));

foreach ($cursor as $feedTimestamp)
{
    $page = \MongoDBRef::get($originDB, $feedTimestamp["fbPage"]);
    $newPageCol->update(
        array("_id" => $page["_id"]),
        $page,
        array("upsert" => true));

    $feed = \MongoDBRef::get($originDB, $feedTimestamp["fbFeed"]);
    $newFeedCol->update(
        array("_id" => $feed["_id"]),
        $feed,
        array("upsert" => true));

    $newFeedTimestampCol->update(
        array("_id" => $feedTimestamp["_id"]),
        $feedTimestamp,
        array("upsert" => true));
}