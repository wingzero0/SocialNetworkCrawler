<?php
/**
 * User: kit
 * Date: 10-Jun-16
 * Time: 5:52 PM
 */

namespace CodingGuys\FbRepo;


class FbFeedTimestampRepo extends FbRepo
{
    /**
     * @param \MongoDB\BSON\ObjectID $pageId
     * @param \MongoDate $batchTime
     * @return \MongoDB\Driver\Cursor
     */
    public function findByPageIdAndBatchTime(\MongoDB\BSON\ObjectID $pageId, \MongoDate $batchTime)
    {
        $col = $this->getFbDM()->getFeedTimestampCollection();
        $cursor = $col->find(array(
            "fbPage.\$id" => $pageId,
            "batchTime" => $batchTime
        ));
        return $cursor;
    }

    /**
     * @param \MongoDB\BSON\ObjectID $feedId
     * @param \MongoDate $start
     * @param \MongoDate $end
     * @return \MongoDB\Driver\Cursor
     */
    public function findByFeedIdAndDateRange(\MongoDB\BSON\ObjectID $feedId, \MongoDate $start, \MongoDate $end)
    {
        $col = $this->getFbDM()->getFeedTimestampCollection();
        $query = array(
            "fbFeed.\$id" => $feedId,
            "batchTime" => $this->createDateRangeQuery($start, $end)
        );
        $options = array(
            "sort" => array("batchTime" => 1)
        );
        $cursor = $col->find($query, $options);
        return $cursor;
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     * @return \MongoDB\Driver\Cursor
     */
    public function findByDateRange(\DateTime $start, \DateTime $end)
    {
        $col = $this->getFbDM()->getFeedTimestampCollection();
        $query = array(
            "batchTime" => $this->createDateRangeQuery($start, $end)
        );
        $cursor = $col->find($query);
        return $cursor;
    }

    /**
     * @param \MongoDB\BSON\ObjectID $pageId
     * @param \MongoDate $start
     * @param \MongoDate $end
     * @return \MongoDate|null
     */
    public function findFirstBatchByPageAndDateRange(\MongoDB\BSON\ObjectID $pageId, \MongoDate $start, \MongoDate $end)
    {
        $col = $this->getFbDM()->getFeedTimestampCollection();
        $query = array(
            "fbPage.\$id" => $pageId,
            "batchTime" => $this->createDateRangeQuery($start, $end)
        );
        $options = array(
            "sort" => array("updateTime" => 1)
        );
        $arr = $col->findOne($query, $options);
        if ($arr != null){
            return $arr["batchTime"];
        } else {
            return null;
        }
    }

    /**
     * @param \MongoDate|\DateTime $start
     * @param \MongoDate|\DateTime $end
     * @return array
     */
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
}