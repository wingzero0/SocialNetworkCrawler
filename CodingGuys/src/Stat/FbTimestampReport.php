<?php
/**
 * User: kit
 * Date: 24/03/15
 * Time: 17:04
 */

namespace CodingGuys\Stat;

use CodingGuys\Exception\ClassTypeException;
use CodingGuys\MongoFb\CGMongoFbFeed;
use CodingGuys\MongoFb\CGMongoFbFeedTimestamp;
use CodingGuys\MongoFb\CGMongoFbPage;

class FbTimestampReport extends FbFeedStat
{
    private $watchStartTime;
    private $watchEndTime;
    private $watchDelta;
    private $filename;
    private $fp;
    private $pagePool;
    private $feedPool;

    public function __construct(\DateTime $startDate, \DateTime $endDate, $filename)
    {
        parent::__construct($startDate, $endDate);
        $this->filename = $filename;
    }


    public function timestampSeriesCount($city = "mo")
    {
        $countArray = array();
        $this->feedPool = array();
        $this->pagePool = array();
        $i = 0;
        $batchTimeIndex = array();

        //$this->checkTime(true, "start timer");
        while (1)
        {
            // TODO should start from Page, not from feed
            $cursor = $this->findFeedByDateRange()->skip($i)->limit(100);
            if (!$cursor->hasNext())
            {
                break;
            } else
            {
                //$this->checkTime(false, "working on feed:" . $i . "\n");
                fprintf($this->STDERR, "working on feed:" . $i . "\n");
            }
            foreach ($cursor as $feed)
            {
                $i++;
                $page = \MongoDBRef::get($this->getFbDocumentManager()->getMongoDB(), $feed["fbPage"]);
                if ($page["mnemono"]["location"]["city"] != $city)
                {
                    continue;
                }
                $reformedSeries = $this->storeInPoolAndGetDelta($feed, $page);
                if (!empty($reformedSeries))
                {
                    $countArray[$page["fbID"]][$feed["fbID"]] = $reformedSeries;
                    foreach ($reformedSeries as $batchTime => $v)
                    {
                        $batchTimeIndex[$batchTime] = 1;
                    }
                }
            }
        }

        ksort($countArray);

        $this->outputCountArray($countArray, $batchTimeIndex);
    }

    private function getFirstBatchAverageFeedLikes(CGMongoFbPage $cgMongoFbPage)
    {
        $batchTime = $cgMongoFbPage->getFirstBatchTimeWithInWindow($this->getStartDateMongoDate(), $this->getEndDateMongoDate());
        return $cgMongoFbPage->getAverageFeedLikesInTheBatch($batchTime);
    }

    private function getFirstBatchAverageFeedComments(CGMongoFbPage $cgMongoFbPage)
    {
        $batchTime = $cgMongoFbPage->getFirstBatchTimeWithInWindow($this->getStartDateMongoDate(), $this->getEndDateMongoDate());
        return $cgMongoFbPage->getAverageFeedCommentsInTheBatch($batchTime);
    }

    private function skipNColumn($n)
    {
        $ret = "";
        for ($i = 0; $i < $n; $i++)
        {
            $ret .= ",";
        }
        return $ret;
    }

    /**
     * @param string $fbID
     * @return CGMongoFbPage|null
     */
    private function getCGFbPage($fbID){
        if (isset($this->pagePool[$fbID])){
            return $this->pagePool[$fbID];
        }
        return null;
    }

    /**
     * @param string $fbID
     * @return CGMongoFbFeed|null
     */
    private function getCGFbFeed($fbID){
        if (isset($this->feedPool[$fbID])){
            return $this->feedPool[$fbID];
        }
        return null;
    }

    private function outputCountArray($countArray, $batchTimeIndex)
    {
        ksort($batchTimeIndex);
        $this->outputHeading($batchTimeIndex);
        foreach ($countArray as $pageId => $page)
        {
            $cgFbPage = $this->getCGFbPage($pageId);
            $firstBatchAvgLikes = $this->getFirstBatchAverageFeedLikes($cgFbPage);
            $firstBatchAvgComments = $this->getFirstBatchAverageFeedComments($cgFbPage);
            foreach ($page as $feedId => $feed)
            {
                $this->outputString($cgFbPage->getShortLink() . ",");
                $this->outputString($cgFbPage->getMnemonoCategory() . ",");
                $this->outputString($firstBatchAvgLikes . ",");
                $this->outputString($firstBatchAvgComments . ",");
                $this->outputString($cgFbPage->getFeedCount() . ",");
                $this->outputString($cgFbPage->getFeedAverageLike() . ",");
                $this->outputString($cgFbPage->getFeedAverageComment() . ",");

                $cgFbFeed = $this->getCGFbFeed($feedId);
                $this->outputString(preg_replace("/%/", "%%", $cgFbFeed->guessLink()) . ",");
                $this->outputString($cgFbFeed->getCreatedTime() . ",");
                $this->outputString($cgFbFeed->getSharesCount() . ",");

                foreach ($batchTimeIndex as $batchTimeString => $value)
                {
                    if (isset($feed[$batchTimeString]))
                    {
                        $delta = $feed[$batchTimeString];
                        if ($delta instanceof FbFeedDelta)
                        {
                            $this->outputString($delta->getDeltaLike() . ",");
                            $this->outputString($delta->getDeltaComment() . ",");
                        } else
                        {
                            throw new ClassTypeException("FbFeedDelta", $delta);
                        }
                    } else
                    {
                        $this->outputString($this->skipNColumn(2));
                    }
                }
                $this->outputString("\n");
            }
        }
        $this->outputString("", true);
    }

    private function outputHeading($batchTimeIndex){
        $this->outputString("FbPage,MnemonoCategory,"
            . "FirstBatchAverageLikes,FirstBatchAverageComments,"
            . "PageFeedCount,CurrentWindowAverageLikes,CurrentWindowAverageComments,"
            . "FbFeed,FeedCreatedTime,FeedShareCounts,");
        foreach ($batchTimeIndex as $batchTimeString => $value)
        {
            $this->outputString($batchTimeString . "," . $this->skipNColumn(1));
        }
        $this->outputString("\n" . $this->skipNColumn(10));
        foreach ($batchTimeIndex as $batchTimeString => $value)
        {
            $this->outputString("deltaLike,deltaComment,");
        }
        $this->outputString("\n");
    }

    /**
     * @param CGMongoFbPage $cgMongoFbPage mongo raw data of fb page
     * @param array $feedTimestampRecords array of CGMongoFbFeedTimestamp
     */
    private function accumulatePageLikeAndComment(CGMongoFbPage $cgMongoFbPage, $feedTimestampRecords)
    {
        $cgMongoFbPage->setFeedCount($cgMongoFbPage->getFeedCount() + 1);

        $ret = $this->getIndexOfMaxRecord($feedTimestampRecords);
        $record =  $feedTimestampRecords[$ret['indexOfMaxLike']];
        if (! $record instanceof CGMongoFbFeedTimestamp){
            //TODO throw exception
            return;
        }
        $maxLike = $record->getLikesTotalCount();
        $record =  $feedTimestampRecords[$ret['indexOfMaxComment']];
        if (! $record instanceof CGMongoFbFeedTimestamp){
            //TODO throw exception
            return;
        }
        $maxComment = $record->getCommentsTotalCount();

        $cgMongoFbPage->setAccumulateLike($cgMongoFbPage->getAccumulateLike() + $maxLike);
        $cgMongoFbPage->setAccumulateComment($cgMongoFbPage->getAccumulateComment() + $maxComment);
    }

    /**
     * @param array $feed mongo raw data of fb feed
     * @param array $page mongo raw data of fb page
     * @return array array of FbFeedDelta
     */
    private function storeInPoolAndGetDelta($feed, $page)
    {
        $this->feedPool[$feed["fbID"]] = new CGMongoFbFeed($feed);
        if (!isset($this->pagePool[$page["fbID"]]))
        {
            $this->pagePool[$page["fbID"]] = new CGMongoFbPage($page);
            $this->getPageTimestamp($page["_id"]);
        }

        $cgMongoFbPage = $this->pagePool[$page["fbID"]];

        $sortedFeedTimestampRecords = $this->findTimestampByFeed($feed["_id"]);
        $this->accumulatePageLikeAndComment($cgMongoFbPage, $sortedFeedTimestampRecords);

        $lastLikeCount = 0;
        $lastCommentCount = 0;
        $ret = array();

        foreach ($sortedFeedTimestampRecords as $timestampRecord)
        {
            if ($timestampRecord instanceof CGMongoFbFeedTimestamp)
            {
                $batchTimeString = $timestampRecord->getBatchTimeInISO();
                $totalLike = $timestampRecord->getLikesTotalCount();
                $deltaLike = $totalLike - $lastLikeCount;
                $lastLikeCount = $totalLike;

                $totalComment = $timestampRecord->getCommentsTotalCount();
                $deltaComment = $totalComment - $lastCommentCount;
                $lastCommentCount = $totalComment;

                $ret[$batchTimeString] = new FbFeedDelta($deltaLike, $deltaComment);
            }
        }
        return $ret;
    }

    private function getPageTimestamp(\MongoId $pageId){
        $cursor = $this->getPageTimestampRepo()->findTimestampByPageAndDate($pageId, $this->getStartDateMongoDate(), $this->getEndDateMongoDate());
        foreach ($cursor as $record){
            // TODO store in obj and db tmp table?
        }
    }

    private function checkTime($isRest = true, $displayMessage = "")
    {
        if ($isRest)
        {
            $this->watchStartTime = time();
        }
        $this->watchEndTime = time();
        $this->watchDelta = $this->watchEndTime - $this->watchStartTime;
        fprintf($this->STDERR, $displayMessage . " deltaTime:" . $this->watchDelta . "\n");
        $this->watchStartTime = $this->watchEndTime;
    }

    private function outputString($str, $closeAfterWrite = false)
    {
        if ($this->fp == null)
        {
            $this->fp = fopen($this->filename, "w");
            if ($this->fp == null)
            {
                fprintf($this->STDERR, "output file: " . $this->filename . " can't be written, redirect output to STDOUT\n");
                $this->fp = fopen('php://stdout', 'w+');
            }
        }
        fprintf($this->fp, $str);
        if ($closeAfterWrite)
        {
            fclose($this->fp);
            $this->fp = null;
        }
    }
}