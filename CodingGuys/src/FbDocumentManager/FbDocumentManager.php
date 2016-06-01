<?php
/**
 * User: kit
 * Date: 24/04/2016
 * Time: 3:23 PM
 */

namespace CodingGuys\FbDocumentManager;


use CodingGuys\Document\BaseObj;
use CodingGuys\Document\FacebookFeed;
use CodingGuys\Document\FacebookPage;
use CodingGuys\Document\FbFeedDelta;
use CodingGuys\Document\FbPageDelta;
use CodingGuys\Exception\CollectionNotExist;

class FbDocumentManager
{
    private $dbName;
    private $mongoClient;
    protected $pageCollectionName = "FacebookPage";
    protected $exceptionPageCollectionName = "FacebookExceptionPage";
    protected $feedTimestampCollectionName = "FacebookFeedTimestamp";
    protected $pageTimestampCollectionName = "FacebookPageTimestamp";
    // TODO change all name attribute into const, add get collection function directly

    const DEFAULT_DB_NAME = 'Mnemono';

    public function __construct($dbName = null)
    {
        if ($dbName == null)
        {
            // TODO move db location manually
            $this->dbName = FbDocumentManager::DEFAULT_DB_NAME;
        } else
        {
            $this->dbName = $dbName;
        }
    }

    public function writeToDB(BaseObj $obj)
    {
        $collectionName = $obj->getCollectionName();
        if (empty ($collectionName)){
            throw new CollectionNotExist;
        }
        $col = $this->getMongoCollection($collectionName);

        $serialize = $obj->toArray();
        if ($obj->getId() instanceof \MongoId)
        {
            $col->findAndModify(array("_id" => $obj->getId()), $serialize);
        } else
        {
            $col->insert($serialize);
        }
    }

    /**
     * @return \MongoCollection
     */
    public function getFeedDeltaCollection()
    {
        return $this->getMongoCollection(FbFeedDelta::TARGET_COLLECTION);
    }

    /**
     * @return \MongoCollection
     */
    public function getPageDeltaCollection()
    {
        return $this->getMongoCollection(FbPageDelta::TARGET_COLLECTION);
    }

    /**
     * @return \MongoCollection
     */
    public function getFeedCollection(){
        return $this->getMongoCollection(FacebookFeed::TARGET_COLLECTION);
    }

    /**
     * @return \MongoCollection
     */
    public function getPageCollection(){
        return $this->getMongoCollection(FacebookPage::TARGET_COLLECTION);
    }

    public function dropTmpCollection()
    {
        $col = $this->getPageDeltaCollection();
        $col->drop();
        $col = $this->getFeedDeltaCollection();
        $col->drop();
    }

    public function createTmpCollectionIndex()
    {
        $col = $this->getPageDeltaCollection();
        $col->ensureIndex(array("fbPage.\$id" => 1));
        $col = $this->getFeedDeltaCollection();
        $col->ensureIndex(array("fbFeed.\$id" => 1));
    }

    /**
     * @return string
     */
    public function getExceptionPageCollectionName()
    {
        return $this->exceptionPageCollectionName;
    }

    /**
     * @param $collectionName
     */
    public function setExceptionPageCollectionName($collectionName)
    {
        $this->exceptionPageCollectionName = $collectionName;
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
     * @return string
     */
    public function getPageTimestampCollectionName()
    {
        return $this->pageTimestampCollectionName;
    }

    /**
     * @param string $pageTimestampCollectionName
     */
    public function setPageTimestampCollectionName($pageTimestampCollectionName)
    {
        $this->pageTimestampCollectionName = $pageTimestampCollectionName;
    }

    /**
     * @param $colName
     * @return \MongoCollection
     */
    public function getMongoCollection($colName)
    {
        $m = $this->getMongoClient();
        $col = $m->selectCollection($this->dbName, $colName);
        return $col;
    }

    /**
     * @return \MongoDB
     */
    public function getMongoDB()
    {
        $m = $this->getMongoClient();
        $db = $m->selectDB($this->dbName);
        return $db;
    }

    /**
     * @return \MongoClient
     */
    public function getMongoClient()
    {
        if ($this->mongoClient == null)
        {
            $this->mongoClient = new \MongoClient();
        }
        return $this->mongoClient;
    }

    /**
     * @param \MongoId $mongoId
     * @return \MongoDBRef|array
     */
    public function createPageRef(\MongoId $mongoId)
    {
        return \MongoDBRef::create(FacebookPage::TARGET_COLLECTION, $mongoId);
    }

    /**
     * @param \MongoId $mongoId
     * @return \MongoDBRef|array
     */
    public function createFeedRef(\MongoId $mongoId)
    {
        return \MongoDBRef::create(FacebookFeed::TARGET_COLLECTION, $mongoId);
    }

    /**
     * @param \MongoDate $mongoDate
     * @return string
     */
    protected function convertMongoDateToISODate(\MongoDate $mongoDate)
    {
        $batchTime = new \DateTime();
        $batchTime->setTimestamp($mongoDate->sec);
        return $batchTime->format(\DateTime::ISO8601);
    }
} 