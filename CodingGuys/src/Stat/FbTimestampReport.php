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
                // TODO find suitable anchor point, same batch? one hour?
                $page = \MongoDBRef::get($this->getFbFeedCol()->db, $feed["fbPage"]);
                if ($page["mnemono"]["location"]["city"] != $city)
                {
                    continue;
                }
                $reformedSeries = $this->reformulateTimestampSeries($feed);
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

        $this->outputCountArray($countArray, $batchTimeIndex, $this->feedPool, $this->pagePool);
    }

    private function getPreviousAverageFeedLikes(CGMongoFbPage $cgMongoFbPage)
    {
        $batchTime = $cgMongoFbPage->getFirstBatchTimeWithInWindow($this->getStartDateMongoDate(), $this->getEndDateMongoDate());
        return $cgMongoFbPage->getAverageFeedLikesInTheBatch($batchTime);
    }

    private function getPreviousAverageFeedComments(CGMongoFbPage $cgMongoFbPage)
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

    private function outputCountArray($countArray, $batchTimeIndex, $feedPool, $pageRaw)
    {
        $this->outputString("fbpage,feed,feedLink,guessLink,feedCreatedTime,FeedShareCounts,mnemonoCategory,pageLikeCount,"
            . "LastBatchBeforeCurrentWindowAverageLikes,LastBatchBeforeCurrentWindowAverageComments,"
            . "pageFeedCount,CurrentWindowAverageLikes,CurrentWindowAverageComments,");
        ksort($batchTimeIndex);
        foreach ($batchTimeIndex as $batchTimeString => $value)
        {
            $this->outputString($batchTimeString . "," . $this->skipNColumn(1));
        }
        $this->outputString("\n" . $this->skipNColumn(13));
        foreach ($batchTimeIndex as $batchTimeString => $value)
        {
            $this->outputString("deltaLike,deltaComment,");
        }
        $this->outputString("\n");
        foreach ($countArray as $pageId => $page)
        {
            $cgFbPage = $pageRaw[$pageId];
            if ($cgFbPage instanceof CGMongoFbPage)
            {
                $previousAvgLikes = $this->getPreviousAverageFeedLikes($cgFbPage);
                $previousAvgComments = $this->getPreviousAverageFeedComments($cgFbPage);
                foreach ($page as $feedId => $feed)
                {
                    $cgFbFeed = $feedPool[$feedId];
                    if (!($cgFbFeed instanceof CGMongoFbFeed))
                    {
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

                    $this->outputString($previousAvgLikes . ",");
                    $this->outputString($previousAvgComments . ",");
                    $this->outputString($cgFbPage->getFeedCount() . ",");
                    $this->outputString($cgFbPage->getFeedAverageLike() . ",");
                    $this->outputString($cgFbPage->getFeedAverageComment() . ",");

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
                                // TODO throw exception;
                            }
                        } else
                        {
                            $this->outputString($this->skipNColumn(2));
                        }
                    }
                    $this->outputString("\n");
                }
            }
        }
        $this->outputString("", true);
    }

    /**
     * @param array $page mongo raw data of fb page
     * @param array $feedTimestampRecords array of CGMongoFbFeedTimestamp
     */
    private function accumulatePageLikeAndComment($page, $feedTimestampRecords)
    {
        if (!isset($this->pagePool[$page["fbID"]]))
        {
            $this->pagePool[$page["fbID"]] = new CGMongoFbPage($page);
        }
        $cgMongoFbPage = $this->pagePool[$page["fbID"]];
        if (!($cgMongoFbPage instanceof CGMongoFbPage))
        {
            return;
        }
        $cgMongoFbPage->setFeedCount($cgMongoFbPage->getFeedCount() + 1);

        $ret = $this->findMaxLikeAndMaxComment($feedTimestampRecords);
        $cgMongoFbPage->setAccumulateLike($cgMongoFbPage->getAccumulateLike() + $ret['maxLike']);
        $cgMongoFbPage->setAccumulateComment($cgMongoFbPage->getAccumulateComment() + $ret['maxComment']);
    }

    /**
     * @param array $feed fb feed
     * @return array array of FbFeedDelta
     */
    private function reformulateTimestampSeries($feed)
    {
        $db = $this->getFbFeedCol()->db;
        $page = \MongoDBRef::get($db, $feed["fbPage"]);
        $sortedFeedTimestampRecords = $this->findTimestampByFeed($feed["_id"]);
        $this->accumulatePageLikeAndComment($page, $sortedFeedTimestampRecords);
        $this->feedPool[$feed["fbID"]] = new CGMongoFbFeed($feed);

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