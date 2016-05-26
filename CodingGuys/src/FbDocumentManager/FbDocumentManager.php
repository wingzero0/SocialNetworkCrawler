<?php
/**
 * User: kit
 * Date: 24/04/2016
 * Time: 3:23 PM
 */

namespace CodingGuys\FbDocumentManager;


use CodingGuys\Document\BaseObj;
use CodingGuys\Document\FbFeedDelta;
use CodingGuys\Document\FbPageDelta;
use CodingGuys\Exception\CollectionNotExist;

class FbDocumentManager
{
    private $dbName;
    private $mongoClient;
    protected $pageCollectionName = "FacebookPage";
    protected $exceptionPageCollectionName = "FacebookExceptionPage";
    protected $feedCollectionName = "FacebookFeed";
    protected $feedTimestampCollectionName = "FacebookFeedTimestamp";
    protected $pageTimestampCollectionName = "FacebookPageTimestamp";
    protected $pageDeltaCollectionName = "FacebookPageDelta";
    // TODO change all name attribute into const, add get collection function directly
    const FEED_DELTA_COLLECTION_NAME = "FacebookFeedDelta";

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
        if ($obj instanceof FbPageDelta)
        {
            $col = $this->getMongoCollection($this->getPageDeltaCollectionName());
        } else if ($obj instanceof FbFeedDelta)
        {
            $col = $this->getMongoCollection(FbDocumentManager::FEED_DELTA_COLLECTION_NAME);
        } else
        {
            throw new CollectionNotExist();
        }

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
    public function getFeedDeltaCollection(){
        return $this->getMongoCollection(FbDocumentManager::FEED_DELTA_COLLECTION_NAME);
    }

    public function dropTmpCollection()
    {
        $col = $this->getMongoCollection($this->getPageDeltaCollectionName());
        $col->drop();
        $col = $this->getFeedDeltaCollection();
        $col->drop();
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
        return \MongoDBRef::create($this->getPageCollectionName(), $mongoId);
    }

    /**
     * @param \MongoId $mongoId
     * @return \MongoDBRef|array
     */
    public function createFeedRef(\MongoId $mongoId)
    {
        return \MongoDBRef::create($this->getFeedCollectionName(), $mongoId);
    }

    protected function convertMongoDateToISODate(\MongoDate $mongoDate)
    {
        $batchTime = new \DateTime();
        $batchTime->setTimestamp($mongoDate->sec);
        return $batchTime->format(\DateTime::ISO8601);
    }

    /**
     * @return string
     */
    public function getPageDeltaCollectionName()
    {
        return $this->pageDeltaCollectionName;
    }

    /**
     * @param string $pageDeltaCollectionName
     */
    public function setPageDeltaCollectionName($pageDeltaCollectionName)
    {
        $this->pageDeltaCollectionName = $pageDeltaCollectionName;
    }
} 