<?php
/**
 * User: kit
 * Date: 24/03/15
 * Time: 17:04
 */

namespace CodingGuys;

class CGFeedStat {
	private $startDate;
    private static $dbName = "directory";
	public function __construct(\DateTime $startDate){
		$this->startDate = new \MongoDate($startDate->getTimestamp());
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
}