<?php
/**
 * User: kit
 * Date: 24/03/15
 * Time: 17:04
 */

namespace CodingGuys\Stat;

use CodingGuys\Document\FacebookPageTimestamp;
use CodingGuys\Exception\ClassTypeException;
use CodingGuys\FbRepo\FbFeedDeltaRepo;
use CodingGuys\FbRepo\FbPageDeltaRepo;
use CodingGuys\MongoFb\CGMongoFbFeed;
use CodingGuys\MongoFb\CGMongoFbFeedTimestamp;
use CodingGuys\MongoFb\CGMongoFbPage;
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
        $this->feedDeltaRepo = new FbFeedDeltaRepo();
        $this->pageDeltaRepo = new FbPageDeltaRepo();
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
                try
                {
                    $this->storeInPoolAndGenDelta($feed, $page);
                    $countArray[$page["fbID"]][$feed["fbID"]] = 1;
                } catch (\UnexpectedValueException $e)
                {
                    $this->logToSTDERR($e->getTraceAsString());
                }
            }
        }

        ksort($countArray);

        $this->outputCountArray($countArray);
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
     * @return CGMongoFbFeed|null
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

    private function outputPageDelta(CGMongoFbPage $cgFbPage)
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

    private function outputFeedDelta(CGMongoFbFeed $cgFbFeed)
    {
        $deltas = $this->findFeedDeltaByFeedId($cgFbFeed->getId());
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
     * @param \MongoId $pageId
     * @return array array of FbPageDelta
     */
    private function findPageDeltaByPageId(\MongoId $pageId)
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
     * @param \MongoId $feedId
     * @return array array of FbFeedDelta
     */
    private function findFeedDeltaByFeedId(\MongoId $feedId)
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
     * @param CGMongoFbPage $cgMongoFbPage mongo raw data of fb page
     * @param array $feedTimestampRecords array of CGMongoFbFeedTimestamp
     */
    private function accumulatePageLikeAndComment(CGMongoFbPage $cgMongoFbPage, $feedTimestampRecords)
    {
        $cgMongoFbPage->setFeedCount($cgMongoFbPage->getFeedCount() + 1);

        $ret = $this->getIndexOfMaxRecord($feedTimestampRecords);
        $record = $feedTimestampRecords[$ret['indexOfMaxLike']];
        if (!$record instanceof CGMongoFbFeedTimestamp)
        {
            //TODO throw exception
            return;
        }
        $maxLike = $record->getLikesTotalCount();
        $record = $feedTimestampRecords[$ret['indexOfMaxComment']];
        if (!$record instanceof CGMongoFbFeedTimestamp)
        {
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
     */
    private function storeInPoolAndGenDelta($feed, $page)
    {
        $this->feedPool[$feed["fbID"]] = new CGMongoFbFeed($feed);
        if (!isset($this->pagePool[$page["fbID"]]))
        {
            $this->pagePool[$page["fbID"]] = new CGMongoFbPage($page);
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
     * @param \MongoId $feedId
     * @return array $sortedFeedTimestampRecords
     */
    private function genFeedTimestampDeltaToTmp(\MongoId $feedId)
    {
        $sortedFeedTimestampRecords = $this->findTimestampByFeed($feedId);

        $lastLikeCount = 0;
        $lastCommentCount = 0;

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
     * @param \MongoId $pageId
     */
    private function genPageTimestampDeltasToTmp(\MongoId $pageId)
    {
        $cursor = $this->getPageTimestampRepo()
            ->findTimestampByPageAndDate(
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
            $deltaLike = $pageT->getLikes() - $lastLike;
            $lastLike = $pageT->getLikes();

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
        fprintf($this->STDERR, $msg);
    }
}