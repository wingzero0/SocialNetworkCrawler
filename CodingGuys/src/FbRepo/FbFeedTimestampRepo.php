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
     * @param \MongoId $pageId
     * @param \MongoDate $batchTime
     * @return \MongoCursor
     */
    public function findByPageIdAndBatchTime(\MongoId $pageId, \MongoDate $batchTime)
    {
        $col = $this->getFbDM()->getFeedTimestampCollection();
        $cursor = $col->find(array(
            "fbPage.\$id" => $pageId,
            "batchTime" => $batchTime
        ));
        return $cursor;
    }

    /**
     * @param \MongoId $feedId
     * @param \MongoDate $start
     * @param \MongoDate $end
     * @return \MongoCursor
     */
    public function findByFeedIdAndDateRange(\MongoId $feedId, \MongoDate $start, \MongoDate $end)
    {
        $col = $this->getFbDM()->getFeedTimestampCollection();
        $query = array(
            "fbFeed.\$id" => $feedId,
            "batchTime" => $this->createDateRangeQuery($start, $end)
        );
        $cursor = $col->find($query)->sort(array("batchTime" => 1));
        return $cursor;
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     * @return \MongoCursor
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
     * @param \MongoId $pageId
     * @param \MongoDate $start
     * @param \MongoDate $end
     * @return \MongoDate|null
     */
    public function findFirstBatchByPageAndDateRange(\MongoId $pageId, \MongoDate $start, \MongoDate $end)
    {
        $col = $this->getFbDM()->getFeedTimestampCollection();
        $query = array(
            "fbPage.\$id" => $pageId,
            "batchTime" => $this->createDateRangeQuery($start, $end)
        );
        $cursor = $col->find($query)->limit(1)->sort(array("updateTime" => 1));
        if ($cursor->hasNext())
        {
            $v = $cursor->getNext();
            return $v["batchTime"];
        }
        return null;
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