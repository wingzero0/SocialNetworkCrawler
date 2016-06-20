<?php
/**
 * User: kit
 */

require_once(__DIR__ . '/CodingGuys/autoload.php');

use CodingGuys\Document\FacebookFeed;
use CodingGuys\Document\FacebookFeedTimestamp;
use CodingGuys\Document\FacebookPage;
use CodingGuys\Document\FacebookPageTimestamp;
use CodingGuys\FbDocumentManager\FbDocumentManager;
use CodingGuys\FbRepo\FbFeedTimestampRepo;
use CodingGuys\FbRepo\FbPageTimestampRepo;
use CodingGuys\Utility\DateUtility;

const TMP_DB_NAME = 'MnemonoDump';

$options = getopt("s:e:");

$newDM = new FbDocumentManager(TMP_DB_NAME);
// TODO migrate the new php mongo lib
$newDM->getMongoDB()->drop();

$newFeedTimestampCol = $newDM->getFeedTimestampCollection();
$newPageCol = $newDM->getPageCollection();
$newPageTimestampCol = $newDM->getPageTimestampCollection();
$newFeedCol = $newDM->getFeedCollection();

$newFeedCol->createIndex(array("fbID" => 1));
$newFeedCol->createIndex(array("created_time" => 1));
$newFeedCol->createIndex(array("fbPage.\$id" => 1, "created_time" => 1));

$newFeedTimestampCol->createIndex(array("fbPage.\$id" => -1));
$newFeedTimestampCol->createIndex(array("batchTime" => -1));
$newFeedTimestampCol->createIndex(array("fbPage.\$id" => -1, "batchTime" => -1));
$newFeedTimestampCol->createIndex(array("fbFeed.\$id" => -1, "batchTime" => -1));

$newPageCol->createIndex(array("fbID" => 1));
$newPageCol->createIndex(array("exception" => 1));

$newPageTimestampCol->createIndex(array("fbPage.\$id" => -1));
$newPageTimestampCol->createIndex(array("batchTime" => -1));
$newPageTimestampCol->createIndex(array("fbPage.\$id" => -1 , "batchTime" => -1));


$oldDM = new FbDocumentManager();
$originDB = $oldDM->getMongoDB();
$startDate = \DateTime::createFromFormat(\DateTime::ISO8601, $options["s"]);
$endDate = \DateTime::createFromFormat(\DateTime::ISO8601, $options["e"]);

$repo = new FbFeedTimestampRepo($oldDM);
$cursor = $repo->findByDateRange($startDate, $endDate);

foreach ($cursor as $rawFeedTimestamp)
{
    $feedTimestamp = new FacebookFeedTimestamp($rawFeedTimestamp);

    $rawPage = \MongoDBRef::get($originDB, $feedTimestamp->getFbPage());
    $page = new FacebookPage($rawPage);
    $newDM->upsertDB($page, array("_id" => $page->getId()));

    $rawFeed = \MongoDBRef::get($originDB, $feedTimestamp->getFbFeed());
    $feed = new FacebookFeed($rawFeed);
    $newDM->upsertDB($feed, array("_id" => $feed->getId()));

    $newDM->upsertDB($feedTimestamp, array("_id" => $feedTimestamp->getId()));
}

$repo = new FbPageTimestampRepo($oldDM);
$cursor = $repo->findByDateRange(
    DateUtility::convertDateTimeToMongoDate($startDate),
    DateUtility::convertDateTimeToMongoDate($endDate)
);

foreach ($cursor as $rawPageTimestamp)
{
    $pageTimestamp = new FacebookPageTimestamp($rawPageTimestamp);

    $rawPage = \MongoDBRef::get($originDB, $pageTimestamp->getFbPage());
    $page = new FacebookPage($rawPage);
    $newDM->upsertDB($page, array("_id" => $page->getId()));

    $newDM->upsertDB($pageTimestamp, array("_id" => $pageTimestamp->getId()));
}