<?php
/**
 * User: kit
 * Date: 23/04/2016
 * Time: 5:44 PM
 */

namespace CodingGuys\FbRepo;


class FbPageRepo extends FbRepo
{
    /**
     * @param \MongoId $mongoId
     * @return array
     */
    public function findOneById(\MongoId $mongoId){
        $query = array("_id" => $mongoId);
        return $this->getPageCollection()->find($query)->limit(1)->getNext();
    }

    /**
     * @return \MongoCursor
     */
    public function findAll(){
        return $this->getPageCollection()->find();
    }
    
    /**
     * @return \MongoCollection
     */
    private function getPageCollection(){
        $mongoFb = $this->getMongoFb();
        return $mongoFb->getMongoCollection($mongoFb->getPageCollectionName());
    }
}