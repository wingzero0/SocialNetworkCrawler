<?php
/**
 * User: kit
 * Date: 20/05/15
 * Time: 09:00
 */

namespace CodingGuys\MongoFb;

use CodingGuys\MongoFb\CGMongoFb;
use CodingGuys\MongoFb\CGMongoFbFeedTimestamp;
class CGMongoFbPage extends CGMongoFb{
    private $rawDataFromMongo;
    private $_id;
    public function __construct($rawDataFromMongo){
        $this->rawDataFromMongo = $rawDataFromMongo;
        $this->_id = $rawDataFromMongo["_id"];
        parent::__construct();
    }
    public function getLatestBatchTimeWithInWindow($start, $end){
        $query = array(
            "fbPage.\$id" => $this->_id
        );

        $batchTimeRange = array();
        if ($start != null){
            if ($start instanceof \DateTime){
                $start = new \MongoDate($start->getTimestamp());
            }
            if ($start instanceof \MongoDate){
                $batchTimeRange["\$gte"] = $start;
            }
        }
        if ($end != null){
            if ($end instanceof \DateTime){
                $end = new \MongoDate($end->getTimestamp());
            }
            if ($end instanceof \MongoDate){
                $batchTimeRange["\$lte"] = $end;
            }
        }

        if (!empty($batchTimeRange)){
            $query["batchTime"] = $batchTimeRange;
        }
        print_r($query);
        $cursor = $this->queryFacebookTimestampRecord($query);
        if ($cursor->hasNext()){
            $v = $cursor->next();
            return $v["batchTime"];
        }
        return null;
    }
    /**
     * @param $queryArray the mongo query
     * @return \MongoCursor
     */
    private function queryFacebookTimestampRecord($queryArray){
        $col = $this->getMongoCollection("FacebookTimestampRecord");
        $cursor = $col->find($queryArray)->limit(1)->sort(array("updateTime"=>-1));
        return $cursor;
    }
    public function getLatestBatchTime(){
        return $this->getLatestBatchTimeWithInWindow(null, null);
    }
    public function getFeedTimestampByBatch(\MongoDate $batchTime){
        $col = $this->getMongoCollection("FacebookTimestampRecord");
        $cursor = $col->find(array(
            "fbPage.\$id" => $this->_id,
            "batchTime" => $batchTime
            ));
        //echo $cursor->count()."\n";
        $ret = array();
        foreach ($cursor as $timestampRecord){
            $ret[] =  new CGMongoFbFeedTimestamp($timestampRecord);
        }
        return $ret;
    }
} 