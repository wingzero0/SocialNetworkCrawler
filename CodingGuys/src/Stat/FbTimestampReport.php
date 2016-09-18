<?php
/**
 * User: kit
 * Date: 24/03/15
 * Time: 17:04
 */

namespace CodingGuys\Stat;

use CodingGuys\Document\FacebookFeed;
use CodingGuys\Document\FacebookFeedTimestamp;
use CodingGuys\Document\FacebookPage;
use CodingGuys\Document\FacebookPageTimestamp;
use CodingGuys\Exception\ClassTypeException;
use CodingGuys\FbRepo\FbFeedDeltaRepo;
use CodingGuys\FbRepo\FbPageDeltaRepo;
use CodingGuys\Document\FbPageDelta;
use CodingGuys\Document\FbFeedDelta;

class FbTimestampReport extends FbFeedStat
{
    private $watchStartTime;
    private $watchEndTime;
    private $watchDelta;
    private $filename;
    private $fp;
    private $pagePool;
    private $feedPool;
    private $batchTimeIndexes;
    private $feedDeltaRepo;
    private $pageDeltaRepo;

    public function __construct(\DateTime $startDate, \DateTime $endDate, $filename)
    {
        parent::__construct($startDate, $endDate);
        $this->filename = $filename;
        $this->batchTimeIndexes = array();
        $this->feedDeltaRepo = new FbFeedDeltaRepo($this->getFbDocumentManager());
        $this->pageDeltaRepo = new FbPageDeltaRepo($this->getFbDocumentManager());
    }

    public function timestampSeriesCount($city = "mo")
    {
        $countArray = array();
        $this->feedPool = array();
        $this->pagePool = array();
        $i = 0;
        $this->getFbDocumentManager()->dropTmpCollection();
        $this->getFbDocumentManager()->createTmpCollectionIndex();

        //$this->checkTime(true, "start timer");
        while (1)
        {
            // TODO should start from Page, not from feed
            $cursor = $this->findFeedByDateRange($i, 100);

            fprintf($this->STDERR, "working on feed:" . $i . "\n");
            $lastCount = $i;
            foreach ($cursor as $feed)
            {
                $i++;
                $page = $this->getFbDocumentManager()->dbRefHelper($feed["fbPage"]);
                if ($page["mnemono"]["location"]["city"] != $city)
                {
                    continue;
                }
                try
                {
                    $this->storeInPoolAndGenDelta($feed, $page);
                    $countArray[$page["fbID"]][$feed["fbID"]] = 1;
                } catch (\UnexpectedValueException $e)
                {
                    $this->logToSTDERR($e->getMessage() . " " . $e->getTraceAsString());
                }
            }
            if ($lastCount == $i)
            {
                break;
            }
        }

        ksort($countArray);

        $this->outputCountArray($countArray);
    }

    private function getFirstBatchAverageFeedLikes(FacebookPage $fbPage)
    {
        $repo = $this->getFeedTimestampRepo();
        $batchTime = $repo->findFirstBatchByPageAndDateRange($fbPage->getId(), $this->getStartDateMongoDate(), $this->getEndDateMongoDate());

        if (!($batchTime instanceof \MongoDB\BSON\UTCDateTime))
        {
            throw new \UnexpectedValueException();
        }

        $cursor = $repo->findByPageIdAndBatchTime($fbPage->getId(), $batchTime);

        $total = 0;
        $numOfRecord = 0;
        foreach ($cursor as $timestampRecord)
        {
            $numOfRecord++;
            $cgMongoFbFeedTimestamp = new FacebookFeedTimestamp($timestampRecord);
            $total += $cgMongoFbFeedTimestamp->getLikesTotalCount();
        }
        if ($numOfRecord == 0)
        {
            return 0;
        }
        return $total / $numOfRecord;
    }

    private function getFirstBatchAverageFeedComments(FacebookPage $fbPage)
    {
        $repo = $this->getFeedTimestampRepo();
        $batchTime = $repo->findFirstBatchByPageAndDateRange($fbPage->getId(), $this->getStartDateMongoDate(), $this->getEndDateMongoDate());

        if (!($batchTime instanceof \MongoDB\BSON\UTCDateTime))
        {
            throw new \UnexpectedValueException();
        }

        $cursor = $repo->findByPageIdAndBatchTime($fbPage->getId(), $batchTime);

        $total = 0;
        $numOfRecord = 0;
        foreach ($cursor as $timestampRecord)
        {
            $numOfRecord++;
            $cgMongoFbFeedTimestamp = new FacebookFeedTimestamp($timestampRecord);
            $total += $cgMongoFbFeedTimestamp->getCommentsTotalCount();
        }
        if ($numOfRecord == 0)
        {
            return 0;
        }
        return $total / $numOfRecord;
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
     * @return FacebookPage|null
     */
    private function getCGFbPage($fbID)
    {
        if (isset($this->pagePool[$fbID]))
        {
            return $this->pagePool[$fbID];
        }
        return null;
    }

    /**
     * @param string $fbID
     * @return FacebookFeed|null
     */
    private function getCGFbFeed($fbID)
    {
        if (isset($this->feedPool[$fbID]))
        {
            return $this->feedPool[$fbID];
        }
        return null;
    }

    private function outputCountArray($matrix)
    {
        ksort($this->batchTimeIndexes);
        $this->outputHeading($this->batchTimeIndexes);
        foreach ($matrix as $pageId => $page)
        {
            $cgFbPage = $this->getCGFbPage($pageId);
            $this->outputPageDelta($cgFbPage);
            $firstBatchAvgLikes = $this->getFirstBatchAverageFeedLikes($cgFbPage);
            $firstBatchAvgComments = $this->getFirstBatchAverageFeedComments($cgFbPage);
            foreach ($page as $feedId => $dummyValue)
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
                $this->outputFeedDelta($cgFbFeed);
            }
        }
        $this->outputString("", true);
    }

    private function outputPageDelta(FacebookPage $cgFbPage)
    {
        // TODO should page static only in page?
        $this->outputString($cgFbPage->getShortLink() . ",");
        $this->outputString($cgFbPage->getMnemonoCategory() . ",");
        $this->outputString($this->skipNColumn(2));
        $this->outputString($cgFbPage->getFeedCount() . ",");
        $this->outputString($cgFbPage->getFeedAverageLike() . ",");
        $this->outputString($cgFbPage->getFeedAverageComment() . ",");
        $this->outputString($this->skipNColumn(3));

        $deltas = $this->findPageDeltaByPageId($cgFbPage->getId());
        $batchTimeIndexes = $this->batchTimeIndexes;
        foreach ($batchTimeIndexes as $batchTimeString => $value)
        {
            if (isset($deltas[$batchTimeString]))
            {
                $delta = $deltas[$batchTimeString];
                if ($delta instanceof FbPageDelta)
                {
                    $this->outputString($delta->getDeltaLike() . ",");
                    $this->outputString($delta->getDeltaTalkingAboutCount() . ",");
                } else
                {
                    throw new ClassTypeException("FbPageDelta", $delta);
                }
            } else
            {
                $this->outputString($this->skipNColumn(2));
            }
        }
        $this->outputString("\n");
    }

    private function outputFeedDelta(FacebookFeed $fbFeed)
    {
        $deltas = $this->findFeedDeltaByFeedId($fbFeed->getId());
        $batchTimeIndexes = $this->batchTimeIndexes;

        foreach ($batchTimeIndexes as $batchTimeString => $value)
        {
            if (isset($deltas[$batchTimeString]))
            {
                $delta = $deltas[$batchTimeString];
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

    /**
     * @param \MongoDB\BSON\ObjectID $pageId
     * @return array array of FbPageDelta
     */
    private function findPageDeltaByPageId(\MongoDB\BSON\ObjectID $pageId)
    {
        $cursor = $this->pageDeltaRepo->findByPageId($pageId);
        $ret = array();
        foreach ($cursor as $raw)
        {
            $delta = new FbPageDelta($raw);
            $ret[$delta->getDateStr()] = $delta;
        }
        return $ret;
    }

    /**
     * @param \MongoDB\BSON\ObjectID $feedId
     * @return array array of FbFeedDelta
     */
    private function findFeedDeltaByFeedId(\MongoDB\BSON\ObjectID $feedId)
    {
        $cursor = $this->feedDeltaRepo->findByFeedId($feedId);
        $ret = array();
        foreach ($cursor as $raw)
        {
            $delta = new FbFeedDelta($raw);
            $ret[$delta->getDateStr()] = $delta;
        }
        return $ret;
    }

    private function outputHeading($batchTimeIndex)
    {
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
     * @param FacebookPage $fbPage mongo raw data of fb page
     * @param array $feedTimestampRecords array of FacebookFeedTimestamp
     */
    private function accumulatePageLikeAndComment(FacebookPage $fbPage, $feedTimestampRecords)
    {
        $fbPage->setFeedCount($fbPage->getFeedCount() + 1);

        $ret = $this->getIndexOfMaxRecord($feedTimestampRecords);
        $record = $feedTimestampRecords[$ret['indexOfMaxLike']];
        if (!$record instanceof FacebookFeedTimestamp)
        {
            throw new \UnexpectedValueException();
        }
        $maxLike = $record->getLikesTotalCount();
        $record = $feedTimestampRecords[$ret['indexOfMaxComment']];
        if (!$record instanceof FacebookFeedTimestamp)
        {
            throw new \UnexpectedValueException();
        }
        $maxComment = $record->getCommentsTotalCount();

        $fbPage->setAccumulateLike($fbPage->getAccumulateLike() + $maxLike);
        $fbPage->setAccumulateComment($fbPage->getAccumulateComment() + $maxComment);
    }

    /**
     * @param array $feed mongo raw data of fb feed
     * @param array $page mongo raw data of fb page
     */
    private function storeInPoolAndGenDelta($feed, $page)
    {
        $this->feedPool[$feed["fbID"]] = new FacebookFeed($feed);
        if (!isset($this->pagePool[$page["fbID"]]))
        {
            $this->pagePool[$page["fbID"]] = new FacebookPage($page);
            $this->genPageTimestampDeltasToTmp($page["_id"]);
        }

        $sortedFeedTimestampRecords = $this->genFeedTimestampDeltaToTmp($feed["_id"]);
        if (empty($sortedFeedTimestampRecords))
        {
            throw new \UnexpectedValueException(
                "no timestamp for feed "
                . $feed["_id"] .
                ". Is Timestamp range query too narrow?");
        }

        $cgMongoFbPage = $this->pagePool[$page["fbID"]];
        $this->accumulatePageLikeAndComment($cgMongoFbPage, $sortedFeedTimestampRecords);
    }

    /**
     * @param \MongoDB\BSON\ObjectID $feedId
     * @return array $sortedFeedTimestampRecords
     */
    private function genFeedTimestampDeltaToTmp(\MongoDB\BSON\ObjectID $feedId)
    {
        $sortedFeedTimestampRecords = $this->findTimestampByFeed($feedId);

        $lastLikeCount = 0;
        $lastCommentCount = 0;

        foreach ($sortedFeedTimestampRecords as $timestampRecord)
        {
            if ($timestampRecord instanceof FacebookFeedTimestamp)
            {
                $batchTimeString = $timestampRecord->getBatchTimeInISO();
                $totalLike = $timestampRecord->getLikesTotalCount();
                $deltaLike = $totalLike - $lastLikeCount;
                $lastLikeCount = $totalLike;

                $totalComment = $timestampRecord->getCommentsTotalCount();
                $deltaComment = $totalComment - $lastCommentCount;
                $lastCommentCount = $totalComment;

                $dm = $this->getFbDocumentManager();
                $feedDelta = new FbFeedDelta();
                $feedDelta->setDateStr($batchTimeString)
                    ->setDeltaLike($deltaLike)
                    ->setDeltaComment($deltaComment)
                    ->setFbFeedRef(
                        $dm->createFeedRef($feedId)
                    );
                $dm->writeToDB($feedDelta);
                $this->batchTimeIndexes[$feedDelta->getDateStr()] = 1;
            }
        }
        return $sortedFeedTimestampRecords;
    }

    /**
     * @param \MongoDB\BSON\ObjectID $pageId
     */
    private function genPageTimestampDeltasToTmp(\MongoDB\BSON\ObjectID $pageId)
    {
        $cursor = $this->getPageTimestampRepo()
            ->findByPageAndDate(
                $pageId,
                $this->getStartDateMongoDate(),
                $this->getEndDateMongoDate()
            );
        $lastLike = 0;
        $lastHere = 0;
        $lastTalking = 0;
        foreach ($cursor as $record)
        {
            $pageT = new FacebookPageTimestamp($record);
            $deltaLike = $pageT->getFanCount() - $lastLike;
            $lastLike = $pageT->getFanCount();

            $deltaHere = $pageT->getWereHereCount() - $lastHere;
            $lastHere = $pageT->getWereHereCount();

            $deltaTalking = $pageT->getTalkingAboutCount() - $lastTalking;
            $lastTalking = $pageT->getTalkingAboutCount();

            $delta = new FbPageDelta();
            $dm = $this->getFbDocumentManager();
            $delta->setDateStr($pageT->getBatchTimeInISO())
                ->setDeltaLike($deltaLike)
                ->setDeltaTalkingAboutCount($deltaTalking)
                ->setDeltaWereHereCount($deltaHere)
                ->setFbPageRef(
                    $dm->createPageRef($pageId)
                );
            $dm->writeToDB($delta);

            $this->batchTimeIndexes[$delta->getDateStr()] = 1;
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

    private function logToSTDERR($msg)
    {
        $dateTime = new \DateTime();
        $dateISO = $dateTime->format(\DateTime::ISO8601);
        fprintf($this->STDERR, $dateISO . " " . $msg . "\n");
    }
}