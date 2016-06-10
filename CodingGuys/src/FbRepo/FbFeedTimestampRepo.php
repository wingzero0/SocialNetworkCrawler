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
    public function findByPageIdAndBatchTime(\MongoId $pageId, \MongoDate $batchTime){
        $col = $this->getFbDM()->getFeedTimestampCollection();
        $cursor = $col->find(array(
            "fbPage.\$id" => $pageId,
            "batchTime" => $batchTime
        ));
        return $cursor;
    }
}