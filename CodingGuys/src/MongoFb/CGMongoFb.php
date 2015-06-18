<?php
/**
 * User: kit
 * Date: 20/05/15
 * Time: 09:00
 */

namespace CodingGuys\MongoFb;

class CGMongoFb{
	private $dbName;
	private $mongoClient;
    protected $pageCollectionName = "FacebookPage"; // origin is 'Facebook'
    protected $feedCollectionName = "FacebookFeed"; // origin is 'FacebookFeed'
    protected $feedTimestampCollectionName = "FacebookFeedTimestamp"; // origin is 'FacebookTimestampRecord'
    const DEFAULT_DB_NAME = 'Mnemono';

    public function __construct($dbName = null){
        if ($dbName == null){
            // TODO move db location manually
            $this->dbName = CGMongoFb::DEFAULT_DB_NAME;
        }else{
            $this->dbName = $dbName;
        }
    }

    /**
     * @return string
     */
    public function getPageCollectionName()
    {
        return $this->pageCollectionName;
    }

    /**
     * @param string $pageCollectionName
     */
    public function setPageCollectionName($pageCollectionName)
    {
        $this->pageCollectionName = $pageCollectionName;
    }

    /**
     * @return string
     */
    public function getFeedCollectionName()
    {
        return $this->feedCollectionName;
    }

    /**
     * @param string $feedCollectionName
     */
    public function setFeedCollectionName($feedCollectionName)
    {
        $this->feedCollectionName = $feedCollectionName;
    }

    /**
     * @return string
     */
    public function getFeedTimestampCollectionName()
    {
        return $this->feedTimestampCollectionName;
    }

    /**
     * @param string $feedTimestampCollectionName
     */
    public function setFeedTimestampCollectionName($feedTimestampCollectionName)
    {
        $this->feedTimestampCollectionName = $feedTimestampCollectionName;
    }

    /**
     * @param $colName
     * @return \MongoCollection
     */
    public function getMongoCollection($colName){
        $m = $this->getMongoClient();
        $col = $m->selectCollection($this->dbName, $colName);
        return $col;
    }

    /**
     * @return \MongoDB
     */
    public function getMongoDB(){
        $m = $this->getMongoClient();
        $db = $m->selectDB($this->dbName);
        return $db;
    }

    /**
     * @return \MongoClient
     */
    public function getMongoClient(){
        if ($this->mongoClient == null){
            $this->mongoClient = new \MongoClient();
        }
        return $this->mongoClient;
    }
} 