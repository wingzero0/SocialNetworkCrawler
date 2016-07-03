<?php

require_once __DIR__ . "/../CodingGuys/autoload.php";

use CodingGuys\Utility\DateUtility;

$m = new \MongoClient();
$col = $m->selectCollection("directory", "FacebookTimestampRecord");
$cursor = $col->find();

$dateObj = new \DateTime();
$dateObj->setTimeZone(new \DateTimeZone("UTC"));
foreach ($cursor as $timestamp)
{
    $mongoDate = $timestamp['updateTime'];
    $dateObj->setTimestamp($mongoDate->sec);
    //echo $dateObj->format(\DateTime::ISO8601) . "\n";
    $hour = intval($dateObj->format('H'));
    for ($interval = 18; $interval >= 0; $interval -= 6)
    {
        if ($hour >= $interval)
        {
            $hour = $interval;
            break;
        }
    }
    $batchTime = clone $dateObj;
    $batchTime->setTime($hour, 0, 0);
    //echo $batchTime->format(\DateTime::ISO8601) . "\n";
    $timestamp['batchTime'] = DateUtility::convertDateTimeToMongoDate($batchTime);
    $col->update(array('_id' => $timestamp['_id']), $timestamp);
}