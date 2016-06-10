<?php
/**
 * User: kit
 * Date: 20/05/15
 * Time: 09:00
 */

namespace CodingGuys\MongoFb;

class CGMongoFbPage extends CGMongoFb
{
    private $rawDataFromMongo;
    private $_id;
    private $feedCount;
    private $accumulateLike;
    private $accumulateComment;

    public function __construct($rawDataFromMongo, $dbName = null)
    {
        $this->rawDataFromMongo = $rawDataFromMongo;
        if (!isset($rawDataFromMongo["_id"]))
        {
            var_dump($rawDataFromMongo);
            // TODO throw exception instead of exit program, add error message
            exit(-1);
        }
        $this->_id = $rawDataFromMongo["_id"];
        $this->setFeedCount(0)
            ->setAccumulateLike(0)
            ->setAccumulateComment(0);
        parent::__construct($dbName);
    }

    /**
     * @return \MongoId|null
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @return int
     */
    public function getLikes()
    {
        if (isset($this->rawDataFromMongo["likes"]))
        {
            return intval($this->rawDataFromMongo["likes"]);
        }
        return 0;
    }

    /**
     * @return string
     */
    public function getMnemonoCategory()
    {
        return $this->rawDataFromMongo["mnemono"]["category"];
    }

    /**
     * @return string
     */
    public function getShortLink()
    {
        return parent::extractShortLink($this->rawDataFromMongo);
    }

    public function getFirstBatchTimeWithInWindow($start, $end)
    {
        $query = $this->createQuery($start, $end);
        $col = $this->getMongoCollection($this->feedTimestampCollectionName);
        $cursor = $col->find($query)->limit(1)->sort(array("updateTime" => 1));
        if ($cursor->hasNext())
        {
            $v = $cursor->getNext();
            return $v["batchTime"];
        }
        return null;
    }

    public function getLastBatchTimeWithInWindow($start, $end)
    {
        $query = $this->createQuery($start, $end);
        $col = $this->getMongoCollection($this->feedTimestampCollectionName);
        $cursor = $col->find($query)->limit(1)->sort(array("updateTime" => -1));
        if ($cursor->hasNext())
        {
            $v = $cursor->getNext();
            return $v["batchTime"];
        }
        return null;
    }

    public function getLatestBatchTime()
    {
        return $this->getLatestBatchTimeWithInWindow(null, null);
    }

    public function getAverageFeedCommentsInTheBatch(\MongoDate $batchTime)
    {
        $col = $this->getMongoCollection($this->feedTimestampCollectionName);
        $cursor = $col->find(array(
            "fbPage.\$id" => $this->_id,
            "batchTime" => $batchTime
        ));
        $total = 0;
        $numOfRecord = $cursor->count();
        if ($numOfRecord <= 0)
        {
            return 0;
        }
        foreach ($cursor as $timestampRecord)
        {
            $cgMongoFbFeedTimestamp = new CGMongoFbFeedTimestamp($timestampRecord);
            $total += $cgMongoFbFeedTimestamp->getCommentsTotalCount();
        }
        return $total / $numOfRecord;
    }

    public function getAverageFeedLikesInTheBatch(\MongoDate $batchTime)
    {
        $col = $this->getMongoCollection($this->feedTimestampCollectionName);
        $cursor = $col->find(array(
            "fbPage.\$id" => $this->_id,
            "batchTime" => $batchTime
        ));
        $total = 0;
        $numOfRecord = $cursor->count();
        if ($numOfRecord <= 0)
        {
            return 0;
        }
        foreach ($cursor as $timestampRecord)
        {
            $cgMongoFbFeedTimestamp = new CGMongoFbFeedTimestamp($timestampRecord);
            $total += $cgMongoFbFeedTimestamp->getLikesTotalCount();
        }
        return $total / $numOfRecord;
    }

    public function getFeedWithInWindow($start, $end)
    {
        $query = array(
            "fbPage.\$id" => $this->_id
        );

        $batchTimeRange = $this->createDateRangeQuery($start, $end);

        if (!empty($batchTimeRange))
        {
            $query["batchTime"] = $batchTimeRange;
        }
        $cursor = $this->queryFacebookFeed($query);
        $ret = array();
        foreach ($cursor as $feed)
        {
            $ret[] = new CGMongoFbFeed($feed);
        }
        return $ret;
    }

    private function createDateRangeQuery($start, $end)
    {
        $dateRange = array();
        if ($start != null)
        {
            if ($start instanceof \DateTime)
            {
                $start = new \MongoDate($start->getTimestamp());
            }
            if ($start instanceof \MongoDate)
            {
                $dateRange["\$gte"] = $start;
            }
        }
        if ($end != null)
        {
            if ($end instanceof \DateTime)
            {
                $end = new \MongoDate($end->getTimestamp());
            }
            if ($end instanceof \MongoDate)
            {
                $dateRange["\$lte"] = $end;
            }
        }
        return $dateRange;
    }

    /**
     * @param array $queryArray the mongo query array
     * @return \MongoCursor
     */
    private function queryFacebookFeed($queryArray)
    {
        $col = $this->getMongoCollection($this->feedCollectionName);
        $cursor = $col->find($queryArray);
        return $cursor;
    }

    private function createQuery($start, $end)
    {
        $query = array(
            "fbPage.\$id" => $this->_id
        );

        $batchTimeRange = $this->createDateRangeQuery($start, $end);

        if (!empty($batchTimeRange))
        {
            $query["batchTime"] = $batchTimeRange;
        }
        return $query;
    }

    /**
     * @return int
     */
    public function getFeedCount()
    {
        return $this->feedCount;
    }

    /**
     * @param int $feedCount
     * @return self
     */
    public function setFeedCount($feedCount)
    {
        $this->feedCount = $feedCount;
        return $this;
    }

    /**
     * @return int
     */
    public function getAccumulateLike()
    {
        return $this->accumulateLike;
    }

    /**
     * @param int $accumulateLike
     * @return self
     */
    public function setAccumulateLike($accumulateLike)
    {
        $this->accumulateLike = $accumulateLike;
        return $this;
    }

    /**
     * @return int
     */
    public function getAccumulateComment()
    {
        return $this->accumulateComment;
    }

    /**
     * @param int $accumulateComment
     * @return self
     */
    public function setAccumulateComment($accumulateComment)
    {
        $this->accumulateComment = $accumulateComment;
        return $this;
    }

    /**
     * @return double
     */
    public function getFeedAverageLike()
    {
        return $this->getAccumulateLike() / $this->getFeedCount();
    }

    /**
     * @return double
     */
    public function getFeedAverageComment()
    {
        return $this->getAccumulateComment() / $this->getFeedCount();
    }
}