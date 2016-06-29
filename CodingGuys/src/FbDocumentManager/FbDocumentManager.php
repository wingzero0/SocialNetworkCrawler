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
use MongoDB\Client as MongoDBClient;
use MongoDB\Collection as MongoDBCollection;
use MongoDB\Database as MongoDBDatabase;

class FbDocumentManager
{
    private $dbName;
    private $mongoClient;

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

        $arr = $obj->toArray();
        ksort($arr);
        if ($obj->getId() instanceof \MongoDB\BSON\ObjectID)
        {
            $result = $col->findOneAndUpdate(
                array("_id" => $obj->getId()),
                array("\$set" => $arr)
            );
        } else
        {
            $result = $col->insertOne($arr);
            $arr["_id"] = $result->getInsertedId();
            $obj->setMongoRawData($arr);
        }
        return $result;
    }

    /**
     * @param BaseObj $obj
     * @return MongoDBCollection
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
            && $obj->getId() != $queryCondition["_id"]
        )
        {
            throw new \UnexpectedValueException();
        } else
        {
            $result = $col->findOneAndUpdate(
                $queryCondition,
                array("\$set" => $serialize),
                array("upsert" => true)
            );
        }
        return $result;
    }

    /**
     * @return MongoDBCollection
     */
    public function getFeedDeltaCollection()
    {
        return $this->getMongoCollection(FbFeedDelta::TARGET_COLLECTION);
    }

    /**
     * @return MongoDBCollection
     */
    public function getPageDeltaCollection()
    {
        return $this->getMongoCollection(FbPageDelta::TARGET_COLLECTION);
    }

    /**
     * @return MongoDBCollection
     */
    public function getFeedCollection()
    {
        return $this->getMongoCollection(FacebookFeed::TARGET_COLLECTION);
    }

    /**
     * @return MongoDBCollection
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

    /**
     * @return MongoDBCollection
     */
    public function getFacebookExceptionPageCollection()
    {
        return $this->getMongoCollection(FacebookExceptionPage::TARGET_COLLECTION);
    }

    /**
     * @return MongoDBCollection
     */
    public function getFeedTimestampCollection()
    {
        return $this->getMongoCollection(FacebookFeedTimestamp::TARGET_COLLECTION);
    }

    /**
     * @return MongoDBCollection
     */
    public function getPageTimestampCollection()
    {
        return $this->getMongoCollection(FacebookPageTimestamp::TARGET_COLLECTION);
    }

    /**
     * @param $colName
     * @return MongoDBCollection
     */
    public function getMongoCollection($colName)
    {
        $m = $this->getMongoClient();
        $col = $m->selectCollection($this->dbName, $colName);
        return $col;
    }

    /**
     * @return MongoDBDatabase
     */
    public function getMongoDB()
    {
        $m = $this->getMongoClient();
        $db = $m->selectDatabase($this->dbName);
        return $db;
    }

    /**
     * @return MongoDBClient
     */
    public function getMongoClient()
    {
        if ($this->mongoClient == null)
        {
            $this->mongoClient = new MongoDBClient();
        }
        return $this->mongoClient;
    }

    /**
     * @param \MongoDB\BSON\ObjectID $mongoId
     * @return array
     */
    public function createPageRef(\MongoDB\BSON\ObjectID $mongoId)
    {
        return array("\$ref" => FacebookPage::TARGET_COLLECTION, "\$id" => $mongoId);
    }

    /**
     * @param \MongoDB\BSON\ObjectID $mongoId
     * @return array
     */
    public function createFeedRef(\MongoDB\BSON\ObjectID $mongoId)
    {
        return array("\$ref" => FacebookFeed::TARGET_COLLECTION, "\$id" => $mongoId);
    }

    /**
     * @param array $dbRef
     * @return array|null|object
     */
    public function dbRefHelper($dbRef){
        if (!isset($dbRef["\$ref"]) || !isset($dbRef["\$id"])){
            throw new \UnexpectedValueException("required \$ref, \$id in the array");
        }
        $col = $this->getMongoCollection($dbRef["\$ref"]);
        return $col->findOne(array("_id" => $dbRef["\$id"]));
    }
} 