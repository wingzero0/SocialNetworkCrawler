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

    public function findAllWorkingPage(){
        $query = array(
            "\$or" => array(
                array(
                    "exception" => array("\$exists" => false),
                ),
                array( "exception" => false),
            )
        );
        return $this->getPageCollection()->find($query);
    }
    
    /**
     * @return \MongoCollection
     */
    private function getPageCollection(){
        $fbDM = $this->getFbDM();
        return $fbDM->getMongoCollection($fbDM->getPageCollectionName());
    }
}