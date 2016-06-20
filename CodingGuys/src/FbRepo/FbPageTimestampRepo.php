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
     * @param \MongoId $pageId
     * @param \MongoDate $endDate
     * @param \MongoDate $startDate
     * @return \MongoCursor
     */
    public function findByPageAndDate(\MongoId $pageId, \MongoDate $startDate = null, \MongoDate $endDate = null)
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

        return $col->find($query)->sort(array("batchTime" => 1));
    }

    /**
     * @param \MongoDate|null $startDate
     * @param \MongoDate|null $endDate
     * @return \MongoCursor
     */
    public function findByDateRange(\MongoDate $startDate = null, \MongoDate $endDate = null)
    {
        $col = $this->getPageTimestampCollection();
        $dateRange = $this->createBatchDateRangeQuery($startDate, $endDate);
        return $col->find($dateRange)->sort(array("batchTime" => 1));
    }

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

    private function getPageTimestampCollection()
    {
        return $this->getFbDM()->getPageTimestampCollection();
    }
}