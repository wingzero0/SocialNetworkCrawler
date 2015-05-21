<?php
/**
 * User: kit
 * Date: 24/03/15
 * Time: 17:04
 */

namespace CodingGuys;
use CodingGuys\MongoFb\CGMongoFbPage;

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
    /**
     * query feed's timestamp record in the pre-set date range. 
     * feed's timestamp record could change with time. 
     * this function will return only the timestamp with max like and max comment for a feed
     * @return array it contains two indexes, 'maxLikeRecord' link to the timestamp record with max value of like. 'maxCommentRecord' link to the timestamp record with max value of comment.
     */
    private function queryTimestampMaxValue(){
        $col = $this->getMongoCollection("FacebookFeed");
        $cursor = $col->find($this->getFacebookFeedDateRangeQuery());
        // TODO rename maxLikeRecord to maxLikeRecords, maxCommentRecord to maxCommentRecords
        $maxLikeRecord = array();
        $maxCommentRecord = array();
        foreach ($cursor as $feed){
            $timestampRecords = $this->queryTimestampByFeed($feed["_id"]);
            $ret = $this->findMaxLikeAndMaxComment($timestampRecords);
            $maxLikeRecord[$feed["fbID"]] = $ret['maxLikeRecord'];
            $maxCommentRecord[$feed["fbID"]] = $ret['maxCommentRecord'];
        }
        return array('maxLikeRecord' => $maxLikeRecord, 'maxCommentRecord' => $maxCommentRecord);
    }
    private function findMaxLikeAndMaxComment($timestampRecords){
        $maxLikeRecord = null; $maxCommentRecord = null;
        $maxLike = 0; $maxComment = 0;
        foreach ($timestampRecords as $record){
            if (!isset($record["likes_total_count"])){
                $record["likes_total_count"] = 0;
            }
            if (!isset($record["comments_total_count"])){
                $record["comments_total_count"] = 0;
            }
            if ($maxLike < $record["likes_total_count"]) {
                $maxLikeRecord = $record;
            }
            if ($maxComment < $record["comments_total_count"]){
                $maxCommentRecord = $record;
            }
        }
        return array('maxLikeRecord' => $maxLikeRecord, 'maxCommentRecord' => $maxCommentRecord);
    }

    /**
     * @param $feedId
     * @return array
     */
    private function queryTimestampByFeed($feedId){
        $col = $this->getMongoCollection("FacebookTimestampRecord");
        $cursor = $col->find(
            array('fbFeed.$id' => $feedId)
        )->sort(array("updateTime" => 1));
        $ret = array();
        foreach($cursor as $feedTimestamp){
            $ret[] = $feedTimestamp;
        }
        return $ret;
    }
    // TODO refine output format
    public function topNResult($topN){
        mb_internal_encoding("UTF-8");
        $maxRecord = $this->queryTimestampMaxValue();

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
            'shortLink' => $this->extractShortLink($fbFeed),
            'likes_total_count' => $timestampRecord['likes_total_count'],
            'comments_total_count' => $timestampRecord['comments_total_count'],
            'message' => (isset($fbFeed["message"]) ? mb_substr($fbFeed["message"], 0, 20) . "..." : ""),
            "updateTime" => $updateTime->format(\DateTime::ISO8601),
        );
    }
    private function extractShortLink($fb){
        return (isset($fb["link"]) && $this->isFbInternalLink($fb["link"]) ?
                $fb["link"] : "https://www.facebook.com/" . $fb["fbID"]);
    }
    private function isFbInternalLink($link){
        return preg_match('/www\.facebook\.com/', $link) > 0;
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
        $countArray = array();
        $feedRaw = array();
        $pageRaw = array();
        $i = 0;
        $batchTimeIndex = array();
        $STDERR = fopen('php://stderr', 'w+');

        $col = $this->getMongoCollection("FacebookFeed");
        while (1){
            $cursor = $col->find($this->getFacebookFeedDateRangeQuery())->skip($i)->limit(100);
            if (!$cursor->hasNext()){
                break;
            }else{
                fprintf($STDERR, $i . "\n");
            }
            foreach ($cursor as $feed){
                $page = \MongoDBRef::get($col->db, $feed["fbPage"]);            
                if (!isset($pageRaw[$page["fbID"]])){
                    $pageRaw[$page["fbID"]] = $page;
                    $pageRaw[$page["fbID"]]["feedCount"] = 0;
                    $pageRaw[$page["fbID"]]["feedAverageLike"] = 0;
                    $pageRaw[$page["fbID"]]["feedAverageComment"] = 0;
                }
                $pageRaw[$page["fbID"]]["feedCount"] += 1;

                $timestampRecords = $this->queryTimestampByFeed($feed["_id"]);
                $ret = $this->findMaxLikeAndMaxComment($timestampRecords);
                $feedRaw[$feed["fbID"]] = $feed;
                $pageRaw[$page["fbID"]]["feedAverageLike"] += $ret['maxLikeRecord']["likes_total_count"];
                $pageRaw[$page["fbID"]]["feedAverageComment"] += $ret['maxCommentRecord']["comments_total_count"];

                $lastLikeCount = 0;
                $lastCommentCount = 0;
                foreach ($timestampRecords as $timestampRecord){
                    $batchTimeString = $this->convertMongoDateToISODate($timestampRecord["batchTime"]);
                    $batchTimeIndex[$batchTimeString] = 1;

                    $totalLike = (isset($timestampRecord["likes_total_count"]) ? intval($timestampRecord["likes_total_count"]):0);                
                    $deltaLike = $totalLike - $lastLikeCount;
                    $lastLikeCount = $totalLike;

                    $totalComment = (isset($timestampRecord["comments_total_count"]) ? intval($timestampRecord["comments_total_count"]):0);
                    $deltaComment = $totalComment - $lastCommentCount;
                    $lastCommentCount = $totalComment;

                    $countArray[$page["fbID"]][$feed["fbID"]][$batchTimeString] = array(
                        "deltaLike" => $deltaLike,
                        "deltaComment" => $deltaComment,
                        "updateTime" => $this->convertMongoDateToISODate($timestampRecord["updateTime"]),
                    );
                }
                $i++;
            }
        }
        
        
        foreach ($pageRaw as $pageId => $page){
            $pageRaw[$pageId]["feedAverageLike"] = $pageRaw[$pageId]["feedAverageLike"] / $pageRaw[$pageId]["feedCount"];
            $pageRaw[$pageId]["feedAverageComment"] = $pageRaw[$pageId]["feedAverageComment"] / $pageRaw[$pageId]["feedCount"];
        }
        //echo "done";
        $this->outputCountArray($countArray, $batchTimeIndex, $feedRaw, $pageRaw);
    }
    private function getPreviousAverageFeedLikes($pageRaw){
        $cgMongoFbPage = new CGMongoFbPage($pageRaw);
        $batchTime = $cgMongoFbPage->getFirstBatchTimeWithInWindow($this->startDate,$this->endDate);
        return $cgMongoFbPage->getAverageFeedLikesBeforeTheBatch($batchTime);
    }
    private function getPreviousAverageFeedComments($pageRaw){
        $cgMongoFbPage = new CGMongoFbPage($pageRaw);
        $batchTime = $cgMongoFbPage->getFirstBatchTimeWithInWindow($this->startDate,$this->endDate);
        return $cgMongoFbPage->getAverageFeedCommentsBeforeTheBatch($batchTime);
    }
    private function skipNColumn($n){
        $ret = "";
        for($i = 0;$i<$n;$i++){
            $ret .= ",";
        }
        return $ret;
    }
    private function outputCountArray($countArray, $batchTimeIndex, $feedRaw, $pageRaw){
        echo "fbpage,fbPageId,feed,feedId,feedCreatedTime,pageLikeCount,LastBatchBeforeCurrentWindowAverageLikes,LastBatchBeforeCurrentWindowAverageComments,pageFeedCount,CurrentWindowAverageLikes,CurrentWindowAverageComments,";
        ksort($batchTimeIndex);
        foreach($batchTimeIndex as $batchTimeString => $value){
            echo $batchTimeString . "," . $this->skipNColumn(1);
        }
        echo "\n" . $this->skipNColumn(11);
        foreach($batchTimeIndex as $batchTimeString => $value){
            echo "deltaLike,deltaComment,";
        }
        echo "\n";
        foreach ($countArray as $pageId => $page){
            $previousAvgLikes = $this->getPreviousAverageFeedLikes($pageRaw[$pageId]);
            $previousAvgComments = $this->getPreviousAverageFeedComments($pageRaw[$pageId]);
            foreach ($page as $feedId => $feed){
                echo $this->extractShortLink($pageRaw[$pageId]) . "," . $pageId . ",";
                echo $this->extractShortLink($feedRaw[$feedId]) . "," . $feedId . ",";
                echo $feedRaw[$feedId]["created_time"] . ",";
                if (isset($pageRaw[$pageId]["likes"])){
                    echo $pageRaw[$pageId]["likes"] . ",";
                }else{
                    echo $this->skipNColumn(1);
                }
                echo $previousAvgLikes. ",";
                echo $previousAvgComments . ",";
                echo $pageRaw[$pageId]["feedCount"] . ",";
                echo $pageRaw[$pageId]["feedAverageLike"] . ",";
                echo $pageRaw[$pageId]["feedAverageComment"] . ",";
                foreach($batchTimeIndex as $batchTimeString => $value){
                    if (isset($feed[$batchTimeString])){
                        $timestampRecord = $feed[$batchTimeString];
                        echo $timestampRecord['deltaLike'].",";
                        echo $timestampRecord['deltaComment'].",";
                    }else{
                        echo ",,";
                    }
                }
                echo "\n";
            }
        }
    }
    private function convertMongoDateToISODate(\MongoDate $mongoDate){
        $batchTime = new \DateTime();
        $batchTime->setTimestamp($mongoDate->sec);
        return $batchTime->format(\DateTime::ISO8601);
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

    /**
     * @return array mongo date query with range of $this->startDate and $this->endDate
     */
    private function getFacebookTimestampDateRangeQuery(){
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
    /**
     * @return array mongo date query with range of $this->startDate and $this->endDate
     */
    private function getFacebookFeedDateRangeQuery(){
        $dateRange = array();
        if ($this->startDate != null){
            $dateRange["\$gte"] = date(\DateTime::ISO8601, $this->startDate->sec);
        }
        if ($this->endDate != null){
            $dateRange["\$lte"] = date(\DateTime::ISO8601, $this->endDate->sec);
        }
        if (empty($dateRange)){
            return array();
        }
        return array("created_time" => $dateRange);
    }
}