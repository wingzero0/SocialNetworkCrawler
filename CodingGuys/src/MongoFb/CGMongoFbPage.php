<?php
/**
 * User: kit
 * Date: 20/05/15
 * Time: 09:00
 */

namespace CodingGuys\MongoFb;

use CodingGuys\MongoFb\CGMongoFb;
use CodingGuys\MongoFb\CGMongoFbFeed;
use CodingGuys\MongoFb\CGMongoFbFeedTimestamp;
class CGMongoFbPage extends CGMongoFb{
    private $rawDataFromMongo;
    private $_id;
    public function __construct($rawDataFromMongo){
        $this->rawDataFromMongo = $rawDataFromMongo;
        $this->_id = $rawDataFromMongo["_id"];
        parent::__construct();
    }
    private function createQuery($start, $end){
        $query = array(
            "fbPage.\$id" => $this->_id
        );

        $batchTimeRange = $this->createDateRangeQuery($start,$end);

        if (!empty($batchTimeRange)){
            $query["batchTime"] = $batchTimeRange;
        }
        return $query;
    }
    public function getFirstBatchTimeWithInWindow($start, $end){
        $query = $this->createQuery($start,$end);
        $col = $this->getMongoCollection($this->feedTimestampCollectionName);
        $cursor = $col->find($query)->limit(1)->sort(array("updateTime"=>1));
        if ($cursor->hasNext()){
            $v = $cursor->getNext();
            return $v["batchTime"];
        }
        return null;
    }
    public function getLastBatchTimeWithInWindow($start, $end){
        $query = $this->createQuery($start,$end);
        $col = $this->getMongoCollection($this->feedTimestampCollectionName);
        $cursor = $col->find($query)->limit(1)->sort(array("updateTime"=>-1));
        if ($cursor->hasNext()){
            $v = $cursor->getNext();
            return $v["batchTime"];
        }
        return null;
    }
    public function getLatestBatchTime(){
        return $this->getLatestBatchTimeWithInWindow(null, null);
    }
    public function getAverageFeedCommentsInTheBatch(\MongoDate $batchTime){
        $col = $this->getMongoCollection($this->feedTimestampCollectionName);
        $cursor = $col->find(array(
            "fbPage.\$id" => $this->_id,
            "batchTime" => $batchTime
            ));
        $total = 0;
        $numOfRecord = $cursor->count();
        if ($numOfRecord <= 0) {return 0;}
        foreach ($cursor as $timestampRecord){
            $cgMongoFbFeedTimestamp = new CGMongoFbFeedTimestamp($timestampRecord);
            $total += $cgMongoFbFeedTimestamp->getCommentsTotalCount();
        }
        return $total / $numOfRecord;
    }
    public function getAverageFeedLikesInTheBatch(\MongoDate $batchTime){
        $col = $this->getMongoCollection($this->feedTimestampCollectionName);
        $cursor = $col->find(array(
            "fbPage.\$id" => $this->_id,
            "batchTime" => $batchTime
            ));
        $total = 0;
        $numOfRecord = $cursor->count();
        if ($numOfRecord <= 0) {return 0;}
        foreach ($cursor as $timestampRecord){
            $cgMongoFbFeedTimestamp = new CGMongoFbFeedTimestamp($timestampRecord);
            $total += $cgMongoFbFeedTimestamp->getLikesTotalCount();
        }
        return $total / $numOfRecord;
    }
    public function getFeedWithInWindow($start, $end){
        $query = array(
            "fbPage.\$id" => $this->_id
        );

        $batchTimeRange = $this->createDateRangeQuery($start,$end);

        if (!empty($batchTimeRange)){
            $query["batchTime"] = $batchTimeRange;
        }
        $cursor = $this->queryFacebookFeed($query);
        $ret = array();
        foreach ($cursor as $feed){
            $ret[] = new CGMongoFbFeed($feed);
        }
        return $ret;
    }
    private function createDateRangeQuery($start,$end){
        $dateRange = array();
        if ($start != null){
            if ($start instanceof \DateTime){
                $start = new \MongoDate($start->getTimestamp());
            }
            if ($start instanceof \MongoDate){
                $dateRange["\$gte"] = $start;
            }
        }
        if ($end != null){
            if ($end instanceof \DateTime){
                $end = new \MongoDate($end->getTimestamp());
            }
            if ($end instanceof \MongoDate){
                $dateRange["\$lte"] = $end;
            }
        }
        return $dateRange;
    }
    /**
     * @param array $queryArray the mongo query array
     * @return \MongoCursor
     */
    private function queryFacebookFeed($queryArray){
        $col = $this->getMongoCollection($this->feedCollectionName);
        $cursor = $col->find($queryArray);
        return $cursor;
    }
} 