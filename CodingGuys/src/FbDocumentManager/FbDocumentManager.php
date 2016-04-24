<?php
/**
 * User: kit
 * Date: 24/04/2016
 * Time: 3:23 PM
 */

namespace CodingGuys\FbDocumentManager;


class FbDocumentManager{
    private $dbName;
    private $mongoClient;
    protected $pageCollectionName = "FacebookPage"; // origin is 'Facebook'
    protected $exceptionPageCollectionName = "FacebookExceptionPage";
    protected $feedCollectionName = "FacebookFeed"; // origin is 'FacebookFeed'
    protected $feedTimestampCollectionName = "FacebookFeedTimestamp"; // origin is 'FacebookTimestampRecord'

    const DEFAULT_DB_NAME = 'Mnemono';

    public function __construct($dbName = null){
        if ($dbName == null){
            // TODO move db location manually
            $this->dbName = FbDocumentManager::DEFAULT_DB_NAME;
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
    public function getExceptionPageCollectionName(){
        return $this->exceptionPageCollectionName;
    }

    /**
     * @param $collectionName
     */
    public function setExceptionPageCollectionName($collectionName){
        $this->exceptionPageCollectionName = $collectionName;
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


    /**
     * @param \MongoId $mongoId
     * @return \MongoDBRef|array
     */
    public function createPageRef(\MongoId $mongoId){
        return \MongoDBRef::create($this->getPageCollectionName(), $mongoId);
    }

    /**
     * @param \MongoId $mongoId
     * @return \MongoDBRef|array
     */
    public function createFeedRef(\MongoId $mongoId){
        return \MongoDBRef::create($this->getFeedCollectionName(), $mongoId);
    }
    protected function convertMongoDateToISODate(\MongoDate $mongoDate){
        $batchTime = new \DateTime();
        $batchTime->setTimestamp($mongoDate->sec);
        return $batchTime->format(\DateTime::ISO8601);
    }
} 