<?php
/**
 * User: kit
 * Date: 30/04/2016
 * Time: 4:54 PM
 */

namespace CodingGuys\Stat;

use CodingGuys\MongoFb\CGMongoFb;

class FbStat
{
    protected $STDERR;

    private $mongoFb;

    public function __construct()
    {
        $this->STDERR = fopen('php://stderr', 'w+');
    }

    /**
     * @param $colName
     * @return \MongoCollection
     */
    protected function getMongoCollection($colName)
    {
        return $this->getMongoFb()->getMongoCollection($colName);
    }

    /**
     * @return \MongoDB
     */
    protected function getMongoDB()
    {
        return $this->getMongoFb()->getMongoDB();
    }

    /**
     * @return \MongoClient
     */
    protected function getMongoClient()
    {
        return $this->getMongoFb()->getMongoClient();
    }

    /**
     * @return CGMongoFb
     */
    protected function getMongoFb()
    {
        if ($this->mongoFb == null)
        {
            $this->mongoFb = new CGMongoFb();
        }
        return $this->mongoFb;
    }

    /**
     * @return \MongoCollection
     */
    protected function getFbFeedCol()
    {
        return $this->getMongoCollection($this->getMongoFb()->getFeedCollectionName());
    }

    /**
     * @return \MongoCollection
     */
    protected function getFbFeedTimestampCol()
    {
        return $this->getMongoCollection($this->getMongoFb()->getFeedTimestampCollectionName());
    }
}