<?php
/**
 * User: kit
 * Date: 23/04/2016
 * Time: 5:44 PM
 */

namespace CodingGuys\FbRepo;

use MongoDB\Collection as MongoDBCollection;

class FbPageRepo extends FbRepo
{
    /**
     * @param \MongoDB\BSON\ObjectID $mongoId
     * @return array|null
     */
    public function findOneById(\MongoDB\BSON\ObjectID $mongoId)
    {
        $query = array("_id" => $mongoId);
        $options = array("limit" => 1);
        $arr = $this->getPageCollection()->find($query, $options);
        return $arr;
    }

    /**
     * @param string $fbId
     * @return array|null
     */
    public function findOneByFbId($fbId)
    {
        $query = array("fbID" => $fbId);
        $options = array("limit" => 1);
        $arr = $this->getPageCollection()->find($query, $options);
        return $arr;
    }

    /**
     * @return \MongoDB\Driver\Cursor
     */
    public function findAll()
    {
        return $this->getPageCollection()->find();
    }

    /**
     * @return \MongoDB\Driver\Cursor
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
     * @param int $crawlTimeH
     * @return \MongoDB\Driver\Cursor
     */
    public function findAllWorkingPageByCrawlTime($crawlTimeH)
    {
        $query = array(
            "\$or" => array(
                array(
                    "exception" => array("\$exists" => false),
                ),
                array("exception" => false),
            ),
            "mnemono.crawlTime" => $crawlTimeH,
        );
        return $this->getPageCollection()->find($query);
    }

    /**
     * @return MongoDBCollection
     */
    private function getPageCollection()
    {
        return $this->getFbDM()->getPageCollection();
    }
}