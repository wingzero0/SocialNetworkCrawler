<?php
/**
 * User: kit
 * Date: 30/04/2016
 * Time: 4:47 PM
 */

namespace CodingGuys\Stat;

use CodingGuys\MongoFb\CGMongoFbFeedTimestamp;

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
     * @return array mongo date query with range of $this->getStartDate() and $this->getEndDate()
     */
    protected function getFacebookFeedDateRangeQuery()
    {
        $dateRange = array();
        if ($this->getStartDateDateTime() != null)
        {
            $startTime = clone $this->getStartDateDateTime();
            $startTime->setTimezone(new \DateTimeZone("GMT+0"));
            $dateRange["\$gte"] = $startTime->format(\DateTime::ISO8601);
        }
        if ($this->getEndDateDateTime() != null)
        {
            $endTime = clone $this->getEndDateDateTime();
            $endTime->setTimezone(new \DateTimeZone("GMT+0"));
            $dateRange["\$lte"] = $endTime->format(\DateTime::ISO8601);
        }
        if (empty($dateRange))
        {
            return array();
        }
        return array("created_time" => $dateRange);
    }

    /**
     * @return \MongoCursor
     */
    protected function findFeedByDateRange()
    {
        $feedCol = $this->getFbFeedCol();
        return $feedCol->find($this->getFacebookFeedDateRangeQuery());
    }

    /**
     * @param $timestampRecords array of CGMongoFbFeedTimestamp
     * @return array the max record
     */
    protected function findMaxLikeAndMaxComment($timestampRecords)
    {
        $maxLikeRecord = null;
        $maxCommentRecord = null;
        $maxLike = -1;
        $maxComment = -1;
        foreach ($timestampRecords as $record)
        {
            if ($record instanceof CGMongoFbFeedTimestamp)
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
     * @param \MongoId $feedId
     * @return array
     */
    protected function findTimestampByFeed(\MongoId $feedId)
    {
        $col = $this->getFbFeedTimestampCol();
        $query = array(
            "batchTime" => $this->getFacebookTimestampDateRangeQuery(),
            "fbFeed.\$id" => $feedId
        );
        $cursor = $col->find($query)->sort(array("batchTime" => 1));
        $ret = array();
        foreach ($cursor as $feedTimestamp)
        {
            $ret[] = new CGMongoFbFeedTimestamp($feedTimestamp);
        }
        return $ret;
    }

    /**
     * @return array mongo date query with range of $this->getStartDate() and $this->getEndDate()
     */
    private function getFacebookTimestampDateRangeQuery()
    {
        $dateRange = array();
        if ($this->getStartDateMongoDate() != null)
        {
            $dateRange["\$gte"] = $this->getStartDateMongoDate();
        }
        if ($this->getEndDateMongoDate() != null)
        {
            $dateRange["\$lte"] = $this->getEndDateMongoDate();
        }
        if (empty($dateRange))
        {
            return array();
        }
        return $dateRange;
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
