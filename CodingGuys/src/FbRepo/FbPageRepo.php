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
     * @return array|null
     */
    public function findOneById(\MongoId $mongoId)
    {
        $query = array("_id" => $mongoId);
        $cursor = $this->getPageCollection()->find($query)->limit(1);
        if (!$cursor->hasNext()){
            return $cursor->getNext();
        }else{
            return null;
        }
    }

    /**
     * @param string $fbId
     * @return array|null
     */
    public function findOneByFbId($fbId)
    {
        $query = array("fbID" => $fbId);
        $cursor = $this->getPageCollection()->find($query)->limit(1);
        if (!$cursor->hasNext()){
            return $cursor->getNext();
        }else{
            return null;
        }
    }

    /**
     * @return \MongoCursor
     */
    public function findAll()
    {
        return $this->getPageCollection()->find();
    }

    /**
     * @return \MongoCursor
     */
    public function findAllWorkingPage()
    {
        $query = array(
            "\$or" => array(
                array(
                    "exception" => array("\$exists" => false),
                ),
                array("exception" => false),
            )
        );
        return $this->getPageCollection()->find($query);
    }

    /**
     * @return \MongoCollection
     */
    private function getPageCollection()
    {
        return $this->getFbDM()->getPageCollection();
    }
}