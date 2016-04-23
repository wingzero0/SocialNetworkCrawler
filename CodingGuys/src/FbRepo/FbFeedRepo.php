<?php
/**
 * User: kit
 * Date: 23/04/2016
 * Time: 6:15 PM
 */

namespace CodingGuys\FbRepo;


class FbFeedRepo extends FbRepo{
    /**
     * @param \MongoId $pageMongoId
     * @return array
     */
    public function findLatestOneByPageId(\MongoId $pageMongoId){
        $query = array(
            "fbPage.\$id" => $pageMongoId
        );
        $orderQ = array(
            "created_time" => -1
        );
        return $this->getFeedCollection()
            ->find($query)
            ->sort($orderQ)
            ->limit(1)->getNext();
    }
    /**
     * @return \MongoCollection
     */
    private function getFeedCollection(){
        $mongoFb = $this->getMongoFb();
        return $mongoFb->getMongoCollection($mongoFb->getFeedCollectionName());
    }

}