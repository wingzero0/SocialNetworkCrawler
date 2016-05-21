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
    public function findTimestampByPageAndDate(\MongoId $pageId, \MongoDate $startDate = null, \MongoDate $endDate = null)
    {
        $col = $this->getPageTimestampCollection();
        $query = array(
            "fbFeed.\$id" => $pageId
        );
        
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
            $query = array_merge(array("batchTime" => $dateRange), $query);
        }
        
        return $col->find($query)->sort(array("batchTime" => 1));
    }
    
    private function getPageTimestampCollection(){
        return $this->getFbDM()->getMongoCollection($this->getFbDM()->getPageTimestampCollectionName());
    }
}