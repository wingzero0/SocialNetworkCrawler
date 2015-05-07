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
    private $mongoClient;
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
        $cursor = $col->find($this->getDateRangeQuery())->sort(array("updateTime" => 1));
        echo $cursor->count()."\n";

        $maxLikeRecord = array();
        $maxCommentRecord = array();
        foreach ($cursor as $timestampRecord){
            $fbFeed = \MongoDBRef::get($col->db, $timestampRecord["fbFeed"]);
            if (!isset($timestampRecord["likes_total_count"])){
                $timestampRecord["likes_total_count"] = 0;
            }
            if (!isset($timestampRecord["comments_total_count"])){
                $timestampRecord["comments_total_count"] = 0;
            }

            if (!isset($maxLikeRecord[$fbFeed["fbID"]])){
                $maxLikeRecord[$fbFeed["fbID"]] = $timestampRecord;
            }else {
                if ($maxLikeRecord[$fbFeed["fbID"]]["likes_total_count"] < $timestampRecord["likes_total_count"]){
                    $maxLikeRecord[$fbFeed["fbID"]] = $timestampRecord;
                }
            }
            if (!isset($maxCommentRecord[$fbFeed["fbID"]])){
                $maxCommentRecord[$fbFeed["fbID"]] = $timestampRecord;
            }else {
                if ($maxCommentRecord[$fbFeed["fbID"]]["comments_total_count"] < $timestampRecord["comments_total_count"]){
                    $maxCommentRecord[$fbFeed["fbID"]] = $timestampRecord;
                }
            }
        }
        return array('maxLikeRecord' => $maxLikeRecord, 'maxCommentRecord' => $maxCommentRecord);
    }
    public function topNResult($topN){
        mb_internal_encoding("UTF-8");
        $maxRecord = $this->groupByFeedWithMaxValue();

        $maxLike = array_values($maxRecord['maxLikeRecord']);
        usort($maxLike, array("CodingGuys\\CGFeedStat", "cmpLikeRecord"));
        $topNLikes = $this->filterTopNLike($maxLike, $topN);
        $result = array();
        echo "{'topNLikes':[";
        $first = true;
        foreach($topNLikes as $i => $v){
            $result['topNLikes'][$i] = $this->extractTimestampNecessaryField($v);
            if ($first){
                echo json_encode($result['topNLikes'][$i]);
                $first = false;
            }else{
                echo "," . json_encode($result['topNLikes'][$i]);
            }
        }
        echo "],'topNComments':[";
        $first = true;
        $maxComment = array_values($maxRecord['maxCommentRecord']);
        usort($maxComment, array("CodingGuys\\CGFeedStat", "cmpCommentRecord"));
        $topNComments = $this->filterTopNComment($maxComment, $topN);
        foreach($topNComments as $i => $v){
            $result['topNComments'][$i] = $this->extractTimestampNecessaryField($v);
            if ($first){
                echo json_encode($result['topNComments'][$i]);
                $first = false;
            }else{
                echo "," . json_encode($result['topNComments'][$i]);
            }
        }
        echo "]}";
        //print_r(json_encode($result));
        return ;
    }
    private function extractTimestampNecessaryField($timestampRecord){
        $fbFeed = \MongoDBRef::get($this->getMongoDB(), $timestampRecord["fbFeed"]);
        $updateTime = new \DateTime();
        $updateTime->setTimestamp($timestampRecord["updateTime"]->sec);
        return array(
            'shortLink' => (isset($fbFeed["link"]) ? $fbFeed["link"] : "https://www.facebook.com/" . $fbFeed["fbID"]),
            'likes_total_count' => $timestampRecord['likes_total_count'],
            'comments_total_count' => $timestampRecord['comments_total_count'],
            'message' => (isset($fbFeed["message"]) ? mb_substr($fbFeed["message"], 0, 20) . "..." : ""),
            "updateTime" => $updateTime->format(\DateTime::ISO8601),
        );
    }
    private function filterTopNComment($sortedCommentTimestamp, $topN){
        return $this->filterTopN("comments_total_count", $sortedCommentTimestamp, $topN);
    }
    private function filterTopNLike($sortedLikeTimestamp, $topN){
        return $this->filterTopN("likes_total_count", $sortedLikeTimestamp, $topN);
    }
    private function filterTopN($fieldName, $sortedTimestamp, $topN){
        if ($topN <= 0){
            return array();
        }
        $ret = array_slice($sortedTimestamp, 0, $topN);
        for ($i = $topN; $i < count($sortedTimestamp); $i++){
            if ($sortedTimestamp[$i - 1][$fieldName] == $sortedTimestamp[$i][$fieldName]
                //&& $sortedTimestamp[$i - 1][$fieldName] >= 100
            ){
                $ret[] = $sortedTimestamp[$i];
            }else{
                break;
            }
        }
        return $ret;
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

    /**
     * @param $colName
     * @return \MongoCollection
     */
    private function getMongoCollection($colName){
        $m = $this->getMongoClient();
        $col = $m->selectCollection(CGFeedStat::$dbName, $colName);
        return $col;
    }

    /**
     * @return \MongoDB
     */
    private function getMongoDB(){
        $m = $this->getMongoClient();
        $col = $m->selectDB(CGFeedStat::$dbName);
        return $col;
    }

    /**
     * @return \MongoClient
     */
    private function getMongoClient(){
        if ($this->mongoClient == null){
            $this->mongoClient = new \MongoClient();
        }
        return $this->mongoClient;
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