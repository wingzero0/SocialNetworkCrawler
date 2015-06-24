<?php
/**
 * User: kit
 * Date: 20/05/15
 * Time: 14:05
 */

namespace CodingGuys\MongoFb;

use CodingGuys\MongoFb\CGMongoFb;

class CGMongoFbFeed extends CGMongoFb{
    private $rawDataFromMongo;
    private $_id;
    public function __construct($rawDataFromMongo, $dbName = null){
        $this->rawDataFromMongo = $rawDataFromMongo;
        $this->_id = $rawDataFromMongo["_id"];
        parent::__construct($dbName);
    }
} 