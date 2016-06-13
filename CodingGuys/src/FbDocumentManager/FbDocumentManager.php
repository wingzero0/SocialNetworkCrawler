<?php
/**
 * User: kit
 * Date: 24/04/2016
 * Time: 3:23 PM
 */

namespace CodingGuys\FbDocumentManager;


use CodingGuys\Document\BaseObj;
use CodingGuys\Document\FacebookExceptionPage;
use CodingGuys\Document\FacebookFeed;
use CodingGuys\Document\FacebookFeedTimestamp;
use CodingGuys\Document\FacebookPage;
use CodingGuys\Document\FacebookPageTimestamp;
use CodingGuys\Document\FbFeedDelta;
use CodingGuys\Document\FbPageDelta;
use CodingGuys\Exception\CollectionNotExist;

class FbDocumentManager
{
    private $dbName;
    private $mongoClient;
    protected $exceptionPageCollectionName = "FacebookExceptionPage";
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
        $col = $this->getObjCollection($obj);

        $serialize = $obj->toArray();
        ksort($serialize);
        if ($obj->getId() instanceof \MongoId)
        {
            $result = $col->findAndModify(
                array("_id" => $obj->getId()),
                $serialize
            );
        } else
        {
            $result = $col->insert($serialize);
            $obj->setMongoRawData($serialize);
        }
        return $result;
    }

    /**
     * @param BaseObj $obj
     * @return \MongoCollection
     * @throws CollectionNotExist
     */
    private function getObjCollection(BaseObj $obj)
    {
        $collectionName = $obj->getCollectionName();
        if (empty ($collectionName))
        {
            throw new CollectionNotExist;
        }
        return $this->getMongoCollection($collectionName);
    }

    /**
     * @param BaseObj $obj
     * @param array $queryCondition
     * @return array
     * @throws CollectionNotExist
     * @throws \Exception
     */
    public function upsertDB(BaseObj $obj, $queryCondition)
    {
        $col = $this->getObjCollection($obj);

        $serialize = $obj->toArray();
        ksort($serialize);
        if ($obj->getId() !== null
            && isset($queryCondition["_id"])
            && $obj->getId() != $queryCondition["_id"])
        {
            throw new \UnexpectedValueException();
        } else
        {
            $result = $col->findAndModify(
                $queryCondition,
                $serialize,
                null,
                array("upsert" => true)
            );
        }
        return $result;
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
    public function getFeedCollection()
    {
        return $this->getMongoCollection(FacebookFeed::TARGET_COLLECTION);
    }

    /**
     * @return \MongoCollection
     */
    public function getPageCollection()
    {
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
        $col->createIndex(array("fbPage.\$id" => 1));
        $col = $this->getFeedDeltaCollection();
        $col->createIndex(array("fbFeed.\$id" => 1));
    }

    public function getFacebookExceptionPageCollection()
    {
        return $this->getMongoCollection(FacebookExceptionPage::TARGET_COLLECTION);
    }

    /**
     * @return \MongoCollection
     */
    public function getFeedTimestampCollection()
    {
        return $this->getMongoCollection(FacebookFeedTimestamp::TARGET_COLLECTION);
    }

    /**
     * @return \MongoCollection
     */
    public function getPageTimestampCollection()
    {
        return $this->getMongoCollection(FacebookPageTimestamp::TARGET_COLLECTION);
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
} 