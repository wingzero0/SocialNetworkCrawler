<?php
/**
 * User: kit
 * Date: 30/04/2016
 * Time: 4:47 PM
 */

namespace CodingGuys\Stat;

use CodingGuys\Document\FacebookFeedTimestamp;

class FbFeedStat extends FbStat
{
    private $startDateMongoDate;
    private $endDateMongoDate;
    private $startDateDateTime;
    private $endDateDateTime;

    public function __construct(\DateTime $startDate, \DateTime $endDate)
    {
        parent::__construct();
        $this->setDateRange($startDate, $endDate);
    }

    public function setDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        $this->startDateDateTime = $startDate;
        if ($startDate != null)
        {
            $this->startDateMongoDate = new \MongoDate($startDate->getTimestamp());
        } else
        {
            $this->startDateMongoDate = null;
        }

        $this->endDateDateTime = $endDate;
        if ($endDate != null)
        {
            $this->endDateMongoDate = new \MongoDate($endDate->getTimestamp());
        } else
        {
            $this->endDateMongoDate = null;
        }
    }

    public function basicCount()
    {
        $col = $this->getFbFeedCol();
        $cursor = $col->find();
        $likesCount = array();
        $commentsCount = array();
        $likesSum = 0;
        $commentsSum = 0;
        foreach ($cursor as $feed)
        {
            if (isset($feed['likes']['summary']['total_count']))
            {
                if (!isset($likesCount[$feed['likes']['summary']['total_count']]))
                {
                    $likesCount[$feed['likes']['summary']['total_count']] = 0;
                }
                $likesCount[$feed['likes']['summary']['total_count']] += 1;
                $likesSum += $feed['likes']['summary']['total_count'];
            }
            if (isset($feed['comments']['summary']['total_count']))
            {
                if (!isset($commentsCount[$feed['comments']['summary']['total_count']]))
                {
                    $commentsCount[$feed['comments']['summary']['total_count']] = 0;
                }
                $commentsCount[$feed['comments']['summary']['total_count']] += 1;
                $commentsSum += $feed['comments']['summary']['total_count'];
            }
        }
        ksort($likesCount);
        ksort($commentsCount);
        print_r($likesCount);
        print_r($commentsCount);
    }

    /**
     * @return \MongoDB\Driver\Cursor
     */
    protected function findFeedByDateRange()
    {
        $repo = $this->getFeedRepo();
        return $repo->findFeedByCreatedTime($this->getStartDateDateTime(), $this->getEndDateDateTime());
    }

    /**
     * @param $timestampRecords array of CGMongoFbFeedTimestamp
     * @return array the maximum record of CGMongoFbFeedTimestamp
     * @deprecated please use getIndexOfMaxRecord
     */
    protected function findMaxLikeAndMaxComment($timestampRecords)
    {
        $maxLikeRecord = null;
        $maxCommentRecord = null;
        $maxLike = -1;
        $maxComment = -1;
        foreach ($timestampRecords as $record)
        {
            if ($record instanceof FacebookFeedTimestamp)
            {
                if ($maxLike < $record->getLikesTotalCount())
                {
                    $maxLikeRecord = $record;
                    $maxLike = $record->getLikesTotalCount();
                }
                if ($maxComment < $record->getCommentsTotalCount())
                {
                    $maxCommentRecord = $record;
                    $maxComment = $record->getCommentsTotalCount();
                }
            }
        }
        return array('maxLikeRecord' => $maxLikeRecord,
            'maxCommentRecord' => $maxCommentRecord,
            'maxLike' => $maxLike,
            'maxComment' => $maxComment);
    }

    /**
     * @param $timestampRecords array of FacebookFeedTimestamp
     * @return array the index of maximum record in input
     */
    protected function getIndexOfMaxRecord($timestampRecords)
    {
        $maxLike = -1;
        $maxComment = -1;
        $indexOfMaxLike = 0;
        $indexOfMaxComment = 0;
        foreach ($timestampRecords as $i => $record)
        {
            if ($record instanceof FacebookFeedTimestamp)
            {
                if ($maxLike < $record->getLikesTotalCount())
                {
                    $indexOfMaxLike = $i;
                    $maxLike = $record->getLikesTotalCount();
                }
                if ($maxComment < $record->getCommentsTotalCount())
                {
                    $indexOfMaxComment = $i;
                    $maxComment = $record->getCommentsTotalCount();
                }
            }
        }

        return array(
            "indexOfMaxLike" => $indexOfMaxLike,
            "indexOfMaxComment" => $indexOfMaxComment);
    }

    /**
     * @param \MongoDB\BSON\ObjectID $feedId
     * @return array array of FacebookFeedTimestamp
     */
    protected function findTimestampByFeed(\MongoDB\BSON\ObjectID $feedId)
    {
        $cursor = $this->getFeedTimestampRepo()->findByFeedIdAndDateRange($feedId, $this->getStartDateMongoDate(), $this->getEndDateMongoDate());
        $ret = array();
        foreach ($cursor as $feedTimestamp)
        {
            $ret[] = new FacebookFeedTimestamp($feedTimestamp);
        }
        return $ret;
    }

    /**
     * @return \MongoDate|null
     */
    public function getStartDateMongoDate()
    {
        return $this->startDateMongoDate;
    }

    /**
     * @return \MongoDate|null
     */
    public function getEndDateMongoDate()
    {
        return $this->endDateMongoDate;
    }


    /**
     * @return \DateTime|null
     */
    public function getEndDateDateTime()
    {
        return $this->endDateDateTime;
    }

    /**
     * @return \DateTime|null
     */
    public function getStartDateDateTime()
    {
        return $this->startDateDateTime;
    }
}
