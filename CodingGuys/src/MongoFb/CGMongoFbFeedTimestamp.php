<?php
/**
 * User: kit
 * Date: 20/05/15
 * Time: 12:05
 */

namespace CodingGuys\MongoFb;

use CodingGuys\MongoFb\CGMongoFb;

class CGMongoFbFeedTimestamp extends CGMongoFb{
    private $rawDataFromMongo;
    private $_id;
    public function __construct($rawDataFromMongo, $dbName = null){
        $this->rawDataFromMongo = $rawDataFromMongo;
        $this->_id = $rawDataFromMongo["_id"];
        parent::__construct($dbName);
    }
    public function getLikesTotalCount(){
    	if (isset($this->rawDataFromMongo["likes_total_count"])){
    		return $this->rawDataFromMongo["likes_total_count"];
    	}else{
    		return 0;
    	}
    }
    public function getCommentsTotalCount(){
        if (isset($this->rawDataFromMongo["comments_total_count"])){
            return $this->rawDataFromMongo["comments_total_count"];
        }else{
            return 0;
        }
    }

    /**
     * @return \MongoDate
     */
    public function getBatchTime(){
        if (isset($this->rawDataFromMongo["batchTime"])){
            return $this->rawDataFromMongo["batchTime"];
        }
        return null;
    }
    /**
     * @return \MongoDate
     */
    public function getUpdateTime(){
        if (isset($this->rawDataFromMongo["updateTime"])){
            return $this->rawDataFromMongo["updateTime"];
        }
        return null;
    }
    /**
     * @return string
     */
    public function getBatchTimeInISO(){
        $batchTime = $this->getBatchTime();
        if ($batchTime == null){
            return "";
        }
        return $this->convertMongoDateToISODate($batchTime);
    }
    /**
     * @return string
     */
    public function getUpdateTimeInISO(){
        $updateTime = $this->getUpdateTimeInISO();
        if ($updateTime == null){
            return "";
        }
        return $this->convertMongoDateToISODate($updateTime);
    }
}