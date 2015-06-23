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
} 