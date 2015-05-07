<?php
/**
 * User: kit
 * Date: 24/03/15
 * Time: 17:04
 */

namespace CodingGuys;

// function cmpLikeRecord($a, $b){
//     return $a["likes_total_count"] > $b["likes_total_count"];
// }

class CGFeedStat {
    private $startDate;
    private $endDate;
    private static $dbName = "directory";
    public function __construct(\DateTime $startDate, \DateTime $endDate){
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
    private function groupByFeedWithMaxValue(){
        $col = $this->getMongoCollection("FacebookTimestampRecord");
        $cursor = $col->find($this->getDateRangeQuery());
        echo $cursor->count()."\n";

        $i = 0;
        $maxLikeRecord = array();
        $maxCommentRecord = array();
        foreach ($cursor as $timestampRecord){
            $fbFeed = \MongoDBRef::get($col->db, $timestampRecord["fbFeed"]);
            if (!isset($timestampRecord["likes_total_count"])){
                $timestampRecord["likes_total_count"] = 0;
            }
            if (!isset($maxLikeRecord[$fbFeed["fbID"]])){
                $maxLikeRecord[$fbFeed["fbID"]] = $timestampRecord;
            }else {
                if ($maxLikeRecord[$fbFeed["fbID"]]["likes_total_count"] < $timestampRecord["likes_total_count"]){
                    $maxLikeRecord[$fbFeed["fbID"]] = $timestampRecord;
                }
            }
            if (!isset($timestampRecord["comments_total_count"])){
                $timestampRecord["comments_total_count"] = 0;
            }
            if (!isset($maxCommentRecord[$fbFeed["fbID"]])){
                $maxCommentRecord[$fbFeed["fbID"]] = $timestampRecord;
            }else {
                if ($maxCommentRecord[$fbFeed["fbID"]]["comments_total_count"] < $timestampRecord["comments_total_count"]){
                    $maxCommentRecord[$fbFeed["fbID"]] = $timestampRecord;
                }
            }
            $i++;
        }
        return array('maxLikeRecord' => $maxLikeRecord, 'maxCommentRecord' => $maxCommentRecord);
    }
    public function topNResult($topN){
        $maxRecord = $this->groupByFeedWithMaxValue();

        $maxLike = array_values($maxRecord['maxLikeRecord']);
        usort($maxLike, array("CodingGuys\\CGFeedStat", "cmpLikeRecord"));
        var_dump($maxLike[0]);
        echo count($maxLike)."\n";

        $maxComment = array_values($maxRecord['maxCommentRecord']);
        usort($maxComment, array("CodingGuys\\CGFeedStat", "cmpCommentRecord"));
        var_dump($maxComment[0]);
        echo count($maxComment)."\n";
    }
    public static function cmpLikeRecord($a, $b){
        if (isset($a["likes_total_count"]) )
        return $a["likes_total_count"] < $b["likes_total_count"];
    }
    public static function cmpCommentRecord($a, $b){
        if (isset($a["comments_total_count"]) )
        return $a["comments_total_count"] < $b["comments_total_count"];
    }
    public function basicCount(){
        $col = $this->getMongoCollection("FacebookFeed");
        $cursor = $col->find();
        $likesCount = array();
		$commentsCount = array();
		$likesSum = 0;
		$commentsSum = 0;
		$i = 0;
        foreach ($cursor as $feed){
        	//print_r($feed);
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
    public function timestampSeriesCount(){
        $col = $this->getMongoCollection("FacebookTimestampRecord");
        $cursor = $col->find();
        $countArray = array();
        $i = 0;
        foreach ($cursor as $timestampRecord){
            $fbFeed = \MongoDBRef::get($col->db, $timestampRecord["fbFeed"]);
            $fbPage = \MongoDBRef::get($col->db, $timestampRecord["fbPage"]);
            $updateTime = new \DateTime();
            $updateTime->setTimestamp($timestampRecord["updateTime"]->sec);
            $countArray[$fbPage["fbID"]][$fbFeed["fbID"]][] = array(
                "likes_total_count" => (isset($timestampRecord["likes_total_count"]) ? intval($timestampRecord["likes_total_count"]):0),
                "comments_total_count" => (isset($timestampRecord["comments_total_count"]) ? intval($timestampRecord["comments_total_count"]):0),
                "updateTime" => $updateTime->format(\DateTime::ISO8601),
            );
            $i++;
            //if ($i > 10000){
            //    break;
            //}
        }
        //echo "done";
        $this->outputCountArray($countArray);
    }
    private function outputCountArray($countArray){
        foreach ($countArray as $pageId => $page){
            foreach ($page as $feedId => $feed){
                echo $pageId.":".$feedId;
                $updateTime = "";
                $likeCount = "";
                $commentCount = "";
                foreach($feed as $timestampRecord){
                    $updateTime .= $timestampRecord['updateTime'].",";
                    $likeCount .= $timestampRecord['likes_total_count'].",";
                    $commentCount .= $timestampRecord['comments_total_count'].",";
                }
                echo ",".$updateTime."\nlikeCount,".$likeCount."\ncommentCount,".$commentCount."\n";
            }
        }
    }
    private function getMongoCollection($colName){
        $m = new \MongoClient();
        $col = $m->selectCollection(CGFeedStat::$dbName, $colName);
        return $col;
    }
    private function getDateRangeQuery(){
        $dateRange = array();
        if ($this->startDate != null){
            $dateRange["\$gte"] = $this->startDate;
        }
        if ($this->endDate != null){
            $dateRange["\$lte"] = $this->endDate;
        }
        if (empty($dateRange)){
            return array();
        }
        return array("updateTime" => $dateRange);
    }
}