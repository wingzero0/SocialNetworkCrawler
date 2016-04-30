<?php
/**
 * User: kit
 * Date: 30/04/2016
 * Time: 4:47 PM
 */

namespace CodingGuys\Stat;

use CodingGuys\MongoFb\CGMongoFbFeedTimestamp;

class FbFeedStat extends FbStat{
    private $startDate;
    private $endDate;
    public function __construct(\DateTime $startDate, \DateTime $endDate){
        parent::__construct();
        $this->setDateRange($startDate, $endDate);
    }
    public function setDateRange(\DateTime $startDate, \DateTime $endDate){
        if ($startDate != null){
            $this->startDate = new \MongoDate($startDate->getTimestamp());
        }else{
            $this->startDate = null;
        }
        if ($endDate != null){
            $this->endDate = new \MongoDate($endDate->getTimestamp());
        }else{
            $this->endDate = null;
        }
    }

    public function basicCount(){
        $col = $this->getFbFeedCol();
        $cursor = $col->find();
        $likesCount = array();
        $commentsCount = array();
        $likesSum = 0;
        $commentsSum = 0;
        foreach ($cursor as $feed){
            if (isset($feed['likes']['summary']['total_count'])){
                if (!isset($likesCount[$feed['likes']['summary']['total_count']])){
                    $likesCount[$feed['likes']['summary']['total_count']] = 0;
                }
                $likesCount[$feed['likes']['summary']['total_count']] += 1;
                $likesSum += $feed['likes']['summary']['total_count'];
            }
            if (isset($feed['comments']['summary']['total_count'])){
                if (!isset($commentsCount[$feed['comments']['summary']['total_count']])){
                    $commentsCount[$feed['comments']['summary']['total_count']] = 0;
                }
                $commentsCount[$feed['comments']['summary']['total_count']] += 1;
                $commentsSum += $feed['comments']['summary']['total_count'];
            }
        }
        ksort($likesCount); ksort($commentsCount);
        print_r($likesCount);
        print_r($commentsCount);
    }

    /**
     * @return array mongo date query with range of $this->getStartDate() and $this->getEndDate()
     */
    protected function getFacebookFeedDateRangeQuery(){
        $dateRange = array();
        if ($this->getStartDate() != null){
            $dateRange["\$gte"] = gmdate(\DateTime::ISO8601, $this->getStartDate()->sec);
        }
        if ($this->getEndDate() != null){
            $dateRange["\$lte"] = gmdate(\DateTime::ISO8601, $this->getEndDate()->sec);
        }
        if (empty($dateRange)){
            return array();
        }
        return array("created_time" => $dateRange);
    }

    /**
     * @param $timestampRecords array of CGMongoFbFeedTimestamp
     * @return array the max record
     */
    protected function findMaxLikeAndMaxComment($timestampRecords){
        $maxLikeRecord = null; $maxCommentRecord = null;
        $maxLike = -1; $maxComment = -1;
        foreach ($timestampRecords as $record){
            if ($record instanceof CGMongoFbFeedTimestamp){
                if ($maxLike < $record->getLikesTotalCount()){
                    $maxLikeRecord = $record;
                    $maxLike = $record->getLikesTotalCount();
                }
                if ($maxComment < $record->getCommentsTotalCount()){
                    $maxCommentRecord = $record;
                    $maxComment = $record->getCommentsTotalCount();
                }
            }
        }
        return array('maxLikeRecord' => $maxLikeRecord,
            'maxCommentRecord' => $maxCommentRecord,
            'maxLike' => $maxLike,
            'maxComment' => $maxComment );
    }

    /**
     * @param \MongoId $feedId
     * @return array
     */
    protected function findTimestampByFeed(\MongoId $feedId){
        $col = $this->getMongoCollection($this->getMongoFb()->getFeedTimestampCollectionName());
        $query = array(
            "batchTime" => $this->getFacebookTimestampDateRangeQuery(),
            "fbFeed.\$id" => $feedId
        );
        $cursor = $col->find($query)->sort(array("batchTime"=>1));
        $ret = array();
        foreach($cursor as $feedTimestamp){
            $ret[] = new CGMongoFbFeedTimestamp($feedTimestamp);
        }
        return $ret;
    }

    /**
     * @return array mongo date query with range of $this->getStartDate() and $this->getEndDate()
     */
    private function getFacebookTimestampDateRangeQuery(){
        $dateRange = array();
        if ($this->getStartDate() != null){
            $dateRange["\$gte"] = $this->getStartDate();
        }
        if ($this->getEndDate() != null){
            $dateRange["\$lte"] = $this->getEndDate();
        }
        if (empty($dateRange)){
            return array();
        }
        return $dateRange;
    }

    /**
     * @return \MongoDate|null
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @return \MongoDate|null
     */
    public function getEndDate()
    {
        return $this->endDate;
    }
}
