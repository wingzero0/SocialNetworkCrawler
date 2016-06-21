<?php
/**
 * User: kit
 * Date: 21/05/2016
 * Time: 4:39 PM
 */

namespace CodingGuys\FbRepo;


class FbPageTimestampRepo extends FbRepo
{
    /**
     * @param \MongoDB\BSON\ObjectID $pageId
     * @param \MongoDate $endDate
     * @param \MongoDate $startDate
     * @return \MongoDB\Driver\Cursor
     */
    public function findByPageAndDate(\MongoDB\BSON\ObjectID $pageId, \MongoDate $startDate = null, \MongoDate $endDate = null)
    {
        $col = $this->getPageTimestampCollection();
        $query = array(
            "fbPage.\$id" => $pageId
        );

        $dateRange = $this->createBatchDateRangeQuery($startDate, $endDate);
        if (!empty($dateRange))
        {
            $query = array_merge($dateRange, $query);
        }

        $options = array( "sort" => array("batchTime" => 1));
        return $col->find($query, $options);
    }

    /**
     * @param \MongoDate|null $startDate
     * @param \MongoDate|null $endDate
     * @return \MongoDB\Driver\Cursor
     */
    public function findByDateRange(\MongoDate $startDate = null, \MongoDate $endDate = null)
    {
        $col = $this->getPageTimestampCollection();
        $dateRange = $this->createBatchDateRangeQuery($startDate, $endDate);
        $options = array( "sort" => array("batchTime" => 1));
        return $col->find($dateRange, $options);
    }

    /**
     * @param \MongoDate|null $startDate
     * @param \MongoDate|null $endDate
     * @return array
     */
    private function createBatchDateRangeQuery(\MongoDate $startDate = null, \MongoDate $endDate = null)
    {
        $dateRange = array();
        if ($startDate != null)
        {
            $dateRange["\$gte"] = $startDate;
        }
        if ($endDate != null)
        {
            $dateRange["\$lte"] = $endDate;
        }
        if (!empty($dateRange))
        {
            return array("batchTime" => $dateRange);
        } else
        {
            return array();
        }
    }

    /**
     * @return \MongoDB\Collection
     */
    private function getPageTimestampCollection()
    {
        return $this->getFbDM()->getPageTimestampCollection();
    }
}