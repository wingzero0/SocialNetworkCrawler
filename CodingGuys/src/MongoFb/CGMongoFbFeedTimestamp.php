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
    public function __construct($rawDataFromMongo){
        $this->rawDataFromMongo = $rawDataFromMongo;
        $this->_id = $rawDataFromMongo["_id"];
        parent::__construct();
    }
    public function getLikesTotalCount(){
    	if (isset($this->rawDataFromMongo["likes_total_count"])){
    		return $this->rawDataFromMongo["likes_total_count"];
    	}else{
    		return 0;
    	}
    }
} 