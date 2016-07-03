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
     * @param \MongoDB\BSON\UTCDateTime $endDate
     * @param \MongoDB\BSON\UTCDateTime $startDate
     * @return \MongoDB\Driver\Cursor
     */
    public function findByPageAndDate(\MongoDB\BSON\ObjectID $pageId, \MongoDB\BSON\UTCDateTime $startDate = null, \MongoDB\BSON\UTCDateTime $endDate = null)
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

        $options = array("sort" => array("batchTime" => 1));
        return $col->find($query, $options);
    }

    /**
     * @param \MongoDB\BSON\UTCDateTime|null $startDate
     * @param \MongoDB\BSON\UTCDateTime|null $endDate
     * @return \MongoDB\Driver\Cursor
     */
    public function findByDateRange(\MongoDB\BSON\UTCDateTime $startDate = null, \MongoDB\BSON\UTCDateTime $endDate = null)
    {
        $col = $this->getPageTimestampCollection();
        $dateRange = $this->createBatchDateRangeQuery($startDate, $endDate);
        $options = array("sort" => array("batchTime" => 1));
        return $col->find($dateRange, $options);
    }

    /**
     * @param \MongoDB\BSON\UTCDateTime|null $startDate
     * @param \MongoDB\BSON\UTCDateTime|null $endDate
     * @return array
     */
    private function createBatchDateRangeQuery(\MongoDB\BSON\UTCDateTime $startDate = null, \MongoDB\BSON\UTCDateTime $endDate = null)
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