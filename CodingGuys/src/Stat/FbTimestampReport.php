<?php
/**
 * User: kit
 * Date: 24/03/15
 * Time: 17:04
 */

namespace CodingGuys\Stat;

use CodingGuys\MongoFb\CGMongoFbFeed;
use CodingGuys\MongoFb\CGMongoFbFeedTimestamp;
use CodingGuys\MongoFb\CGMongoFbPage;

class FbTimestampReport extends FbFeedStat{
    private $watchStartTime;
    private $watchEndTime;
    private $watchDelta;
    private $filename;
    private $fp;
    private $pagePool;
    private $feedPool;

    public function __construct(\DateTime $startDate, \DateTime $endDate, $filename){
        parent::__construct($startDate, $endDate);
        $this->filename = $filename;
    }
    

    public function timestampSeriesCount($city = "mo"){
        $countArray = array();
        $this->feedPool = array();
        $this->pagePool = array();
        $i = 0;
        $batchTimeIndex = array();

        //$this->checkTime(true, "start timer");

        $feedCol = $this->getFbFeedCol();
        while (1){
            $cursor = $feedCol->find($this->getFacebookFeedDateRangeQuery())->skip($i)->limit(100);
            if (!$cursor->hasNext()){
                break;
            }else{
                //$this->checkTime(false, "working on feed:" . $i . "\n");
                fprintf($this->STDERR, "working on feed:" . $i . "\n");
            }
            foreach ($cursor as $feed){
                $i++;
                $page = \MongoDBRef::get($feedCol->db, $feed["fbPage"]);
                if ($page["mnemono"]["location"]["city"] != $city){
                    continue;
                }
                $timestampRecords = $this->findTimestampByFeed($feed["_id"]);
                $reformedSeries = $this->reformulateTimestampSeries($page, $feed, $timestampRecords, $batchTimeIndex);
                if (!empty($reformedSeries)){
                    $countArray[$page["fbID"]][$feed["fbID"]] = $reformedSeries;
                }
            }
        }

        $this->outputCountArray($countArray, $batchTimeIndex, $this->feedPool, $this->pagePool);
    }
    private function getPreviousAverageFeedLikes(CGMongoFbPage $cgMongoFbPage){
        $batchTime = $cgMongoFbPage->getFirstBatchTimeWithInWindow($this->getStartDate(),$this->getEndDate());
        return $cgMongoFbPage->getAverageFeedLikesInTheBatch($batchTime);
    }
    private function getPreviousAverageFeedComments(CGMongoFbPage $cgMongoFbPage){
        $batchTime = $cgMongoFbPage->getFirstBatchTimeWithInWindow($this->getStartDate(),$this->getEndDate());
        return $cgMongoFbPage->getAverageFeedCommentsInTheBatch($batchTime);
    }
    private function skipNColumn($n){
        $ret = "";
        for($i = 0;$i<$n;$i++){
            $ret .= ",";
        }
        return $ret;
    }
    private function outputCountArray($countArray, $batchTimeIndex, $feedPool, $pageRaw){
        $this->outputString("fbpage,feed,feedLink,guessLink,feedCreatedTime,FeedShareCounts,mnemonoCategory,pageLikeCount,"
            . "LastBatchBeforeCurrentWindowAverageLikes,LastBatchBeforeCurrentWindowAverageComments,"
            . "pageFeedCount,CurrentWindowAverageLikes,CurrentWindowAverageComments,");
        ksort($batchTimeIndex);
        foreach($batchTimeIndex as $batchTimeString => $value){
            $this->outputString($batchTimeString . "," . $this->skipNColumn(1));
        }
        $this->outputString("\n" . $this->skipNColumn(13));
        foreach($batchTimeIndex as $batchTimeString => $value){
            $this->outputString("deltaLike,deltaComment,");
        }
        $this->outputString("\n");
        foreach ($countArray as $pageId => $page){
            $cgFbPage = $pageRaw[$pageId];
            if ($cgFbPage instanceof CGMongoFbPage){
                $previousAvgLikes = $this->getPreviousAverageFeedLikes($cgFbPage);
                $previousAvgComments = $this->getPreviousAverageFeedComments($cgFbPage);
                foreach ($page as $feedId => $feed){
                    $cgFbFeed = $feedPool[$feedId];
                    if (!($cgFbFeed instanceof CGMongoFbFeed)){
                        continue;
                    }
                    $this->outputString($cgFbPage->getShortLink() . ",");
                    $this->outputString($cgFbFeed->getShortLink() . ",");
                    $this->outputString(preg_replace("/%/", "%%", $cgFbFeed->getRawLink()) . ",");
                    $this->outputString(preg_replace("/%/", "%%", $cgFbFeed->guessLink()) . ",");
                    $this->outputString($cgFbFeed->getCreatedTime() . ",");
                    $this->outputString($cgFbFeed->getSharesCount() . ",");

                    $this->outputString($cgFbPage->getMnemonoCategory() . ",");
                    $this->outputString($cgFbPage->getLikes() . ",");

                    $this->outputString($previousAvgLikes. ",");
                    $this->outputString($previousAvgComments . ",");
                    $this->outputString($cgFbPage->getFeedCount() . ",");
                    $this->outputString($cgFbPage->getFeedAverageLike() . ",");
                    $this->outputString($cgFbPage->getFeedAverageComment() . ",");

                    foreach($batchTimeIndex as $batchTimeString => $value){
                        if (isset($feed[$batchTimeString])){
                            $timestampRecord = $feed[$batchTimeString];
                            $this->outputString($timestampRecord['deltaLike'].",");
                            $this->outputString($timestampRecord['deltaComment'].",");
                        }else{
                            $this->outputString($this->skipNColumn(2));
                        }
                    }
                    $this->outputString("\n");
                }
            }
        }
        $this->outputString("", true);
    }
    private function accumulatePageLikeAndComment($page, $feed, $timestampRecords){
        if (!isset($this->pagePool[$page["fbID"]])){
            $this->pagePool[$page["fbID"]] = new CGMongoFbPage($page);
        }
        $cgMongoFbPage = $this->pagePool[$page["fbID"]];
        if (!($cgMongoFbPage instanceof CGMongoFbPage)){
            return;
        }
        $cgMongoFbPage->setFeedCount($cgMongoFbPage->getFeedCount() + 1);

        $ret = $this->findMaxLikeAndMaxComment($timestampRecords);
        $cgMongoFbPage->setAccumulateLike($cgMongoFbPage->getAccumulateLike() + $ret['maxLike']);
        $cgMongoFbPage->setAccumulateComment($cgMongoFbPage->getAccumulateComment() + $ret['maxComment']);

        $this->feedPool[$feed["fbID"]] = new CGMongoFbFeed($feed);
    }
    private function reformulateTimestampSeries($page, $feed, $timestampRecords, & $batchTimeIndex){
        $this->accumulatePageLikeAndComment($page, $feed, $timestampRecords);
        $lastLikeCount = 0;
        $lastCommentCount = 0;
        $ret = array();
        foreach ($timestampRecords as $timestampRecord){
            if ($timestampRecord instanceof CGMongoFbFeedTimestamp ){
                $batchTimeString = $timestampRecord->getBatchTimeInISO();
                $batchTimeIndex[$batchTimeString] = 1;
                $totalLike = $timestampRecord->getLikesTotalCount();
                $deltaLike = $totalLike - $lastLikeCount;
                $lastLikeCount = $totalLike;

                $totalComment = $timestampRecord->getCommentsTotalCount();
                $deltaComment = $totalComment - $lastCommentCount;
                $lastCommentCount = $totalComment;

                $ret[$batchTimeString] = array(
                    "deltaLike" => $deltaLike,
                    "deltaComment" => $deltaComment,
                    "updateTime" => $timestampRecord->getUpdateTimeInISO(),
                );
            }
        }
        return $ret;
    }


    /**
     * @return array mongo date query with range of $this->getStartDate() and $this->getEndDate()
     */
    private function getFacebookFeedDateRangeQuery(){
        $dateRange = array();
        if ($this->getStartDate() != null){
            $dateRange["\$gte"] = gmdate(\DateTime::ISO8601, $this->getStartDate()->sec);
        }
        if ($this->getEndDate() != null){
            $dateRange["\$lte"] = gmdate(\DateTime::ISO8601, $this->getEndDate()->sec);
        }
        if (empty($dateRange)){
            return array();
        }
        return array("created_time" => $dateRange);
    }
    private function checkTime($isRest = true, $displayMessage = ""){
        if ($isRest){
            $this->watchStartTime = time();
        }
        $this->watchEndTime = time();
        $this->watchDelta = $this->watchEndTime - $this->watchStartTime;
        fprintf($this->STDERR, $displayMessage . " deltaTime:" .$this->watchDelta . "\n");
        $this->watchStartTime = $this->watchEndTime;
    }

    private function outputString($str, $closeAfterWrite = false){
        if ($this->fp == null){
            $this->fp = fopen($this->filename,"w");
            if ($this->fp == null){
                fprintf($this->STDERR, "output file: " . $this->filename . " can't be written, redirect output to STDOUT\n");
                $this->fp = fopen('php://stdout', 'w+');
            }
        }
        fprintf($this->fp, $str);
        if ($closeAfterWrite){
            fclose($this->fp);
            $this->fp = null;
        }
    }
}