<?php
/**
 * User: kit
 * Date: 10-Jun-16
 * Time: 5:52 PM
 */

namespace CodingGuys\FbRepo;


use CodingGuys\Utility\DateUtility;

class FbFeedTimestampRepo extends FbRepo
{
    /**
     * @param \MongoDB\BSON\ObjectID $pageId
     * @param \MongoDB\BSON\UTCDateTime $batchTime
     * @return \MongoDB\Driver\Cursor
     */
    public function findByPageIdAndBatchTime(\MongoDB\BSON\ObjectID $pageId, \MongoDB\BSON\UTCDateTime $batchTime)
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
     * @param \MongoDB\BSON\UTCDateTime $start
     * @param \MongoDB\BSON\UTCDateTime $end
     * @return \MongoDB\Driver\Cursor
     */
    public function findByFeedIdAndDateRange(\MongoDB\BSON\ObjectID $feedId, \MongoDB\BSON\UTCDateTime $start, \MongoDB\BSON\UTCDateTime $end)
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
     * @param \MongoDB\BSON\UTCDateTime $start
     * @param \MongoDB\BSON\UTCDateTime $end
     * @return \MongoDB\BSON\UTCDateTime|null
     */
    public function findFirstBatchByPageAndDateRange(\MongoDB\BSON\ObjectID $pageId, \MongoDB\BSON\UTCDateTime $start, \MongoDB\BSON\UTCDateTime $end)
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
     * @param \MongoDB\BSON\UTCDateTime|\DateTime $start
     * @param \MongoDB\BSON\UTCDateTime|\DateTime $end
     * @return array
     */
    private function createDateRangeQuery($start, $end)
    {
        $dateRange = array();
        if ($start != null)
        {
            if ($start instanceof \DateTime)
            {
                $start = DateUtility::convertDateTimeToMongoDate($start);
            }
            if ($start instanceof \MongoDB\BSON\UTCDateTime)
            {
                $dateRange["\$gte"] = $start;
            }
        }
        if ($end != null)
        {
            if ($end instanceof \DateTime)
            {
                $end = DateUtility::convertDateTimeToMongoDate($end);
            }
            if ($end instanceof \MongoDB\BSON\UTCDateTime)
            {
                $dateRange["\$lte"] = $end;
            }
        }
        return $dateRange;
    }
}