<?php
/**
 * User: kit
 * Date: 09-May-16
 * Time: 8:45 AM
 */

date_default_timezone_set('GMT+0');
$cli = new MongoClient();
$db = $cli->selectDB("Mnemono");
$pageCol = $db->selectCollection("FacebookPage");
$timestampCol = $db->selectCollection("FacebookPageTimestamp");
$cursor = $pageCol->find();

$ret = array();
$columnIndex = array();
foreach ($cursor as $page)
{
    $pageMongoId = $page["_id"];
    $pageFbId = $page["fbID"];
    $tCursor = $timestampCol->find(array(
        "fbPage.\$id" => $pageMongoId
    ));
    foreach ($tCursor as $timestamp)
    {
        $batchTime = mongoDateToDateTime($timestamp["batchTime"]);
        $columnIndex[$batchTime->format(DateTime::ISO8601)] = 1;
        $ret[$pageFbId][$batchTime->format(DateTime::ISO8601)] = $timestamp;
    }
}

ksort($columnIndex);

printHeading($columnIndex);

foreach ($ret as $pageFbId => $series)
{
    echo "https://www.facebook.com/" . $pageFbId . ",";
    $lastHereCount = 0;
    $lastTalkingCount = 0;
    $lastLikesCount = 0;
    foreach ($columnIndex as $dateStr => $dummyValue)
    {
        if (isset($series[$dateStr]))
        {
            $timestamp = $series[$dateStr];

            $delta = $timestamp["were_here_count"] - $lastHereCount;
            echo $delta . ",";
            $lastHereCount = $timestamp["were_here_count"];

            $delta = $timestamp["talking_about_count"] - $lastTalkingCount;
            echo $delta . ",";
            $lastTalkingCount = $timestamp["talking_about_count"] . ",";

            $delta = $timestamp["likes"] - $lastLikesCount;
            echo $delta . ",";
            $lastLikesCount = $timestamp["likes"] . ",";
        } else
        {
            echo ",,,";
        }
    }
    echo "\n";
}

/**
 * @param array $columnIndex
 */
function printHeading($columnIndex)
{
    echo "fbPage,";
    foreach ($columnIndex as $dateStr => $dummyValue)
    {
        echo $dateStr . ",,,";
    }
    echo "\n";
    echo ",";
    foreach ($columnIndex as $dateStr => $dummyValue)
    {
        echo "were_here_count,talking_about_count,likes,";
    }
    echo "\n";
}

/**
 * @param MongoDate $mDate
 * @return DateTime
 */
function mongoDateToDateTime(MongoDate $mDate)
{
    $stdStartDate = new \DateTime();
    $stdStartDate->setTimestamp($mDate->sec);
    return $stdStartDate;
}