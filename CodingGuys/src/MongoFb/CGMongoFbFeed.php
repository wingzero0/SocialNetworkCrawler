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
    public function getShortLink(){
        return parent::extractShortLink($this->rawDataFromMongo);
    }

    public function getRawLink(){
        return parent::extractRawLink($this->rawDataFromMongo);
    }

    public function guessLink(){
        if (!isset($this->rawDataFromMongo["story"])){
            return $this->getShortLink();
        }

        if (isset($this->rawDataFromMongo["message"])){
            return $this->getShortLink();
        }
        if (isset($this->rawDataFromMongo["message"])){
            return $this->getShortLink();
        }

        $pattern = "/new photos to the album:/";
        $ret = preg_match($pattern, $this->rawDataFromMongo["story"]);
        if ($ret > 0){
            return $this->getRawLink();
        }else{
            return $this->getShortLink();
        }
    }

    /**
     * @return int
     */
    public function getSharesCount(){
        if (isset($this->rawDataFromMongo["shares"]) &&
            isset($this->rawDataFromMongo["shares"]["count"])
        ){
            return intval($this->rawDataFromMongo["shares"]["count"]);
        }
        return 0;
    }

    /**
     * @return string
     */
    public function getCreatedTime(){
        return $this->rawDataFromMongo["created_time"];
    }
}