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
			$this->dbName = DEFAULT_DB_NAME;
		}else{
			$this->dbName = $dbName;
		}
	}
    /**
     * @param $colName
     * @return \MongoCollection
     */
    protected function getMongoCollection($colName){
        $m = $this->getMongoClient();
        $col = $m->selectCollection($this->dbName, $colName);
        return $col;
    }

    /**
     * @return \MongoDB
     */
    protected function getMongoDB(){
        $m = $this->getMongoClient();
        $db = $m->selectDB($this->dbName);
        return $db;
    }

    /**
     * @return \MongoClient
     */
    protected function getMongoClient(){
        if ($this->mongoClient == null){
            $this->mongoClient = new \MongoClient();
        }
        return $this->mongoClient;
    }
} 