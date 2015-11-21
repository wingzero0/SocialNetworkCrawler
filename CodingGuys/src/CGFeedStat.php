<?php
/**
 * User: kit
 * Date: 24/03/15
 * Time: 17:04
 */

namespace CodingGuys;
use CodingGuys\MongoFb\CGMongoFbFeed;
use CodingGuys\MongoFb\CGMongoFbFeedTimestamp;
use CodingGuys\MongoFb\CGMongoFbPage;
use CodingGuys\MongoFb\CGMongoFb;

class CGFeedStat {
    private $startDate;
    private $endDate;
    private $mongoFb;
    private $watchStartTime;
    private $watchEndTime;
    private $watchDelta;
    private $STDERR;
    private $fbTimestamps;
    private $filename;
    private $fp;
    private $pagePool;
    private $feedPool;
    public function __construct(\DateTime $startDate, \DateTime $endDate, $filename){
        $this->setDateRange($startDate, $endDate);
        $this->STDERR = fopen('php://stderr', 'w+');
        $this->filename = $filename;
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
        $col = $this->getMongoCollection($this->getMongoFb()->getFeedCollectionName());
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
        return (isset($fb["link"]) && $this->isFbPhotoLink($fb["link"]) ?
                $fb["link"] : "https://www.facebook.com/" . $fb["fbID"]);
    }
    private function isFbPhotoLink($link){
        return preg_match('/www\.facebook\.com(.*)photos/', $link) > 0;
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
        $col = $this->getMongoCollection($this->getMongoFb()->getFeedCollectionName());
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
    public function timestampSeriesCount($city = "mo"){
        $countArray = array();
        $this->feedPool = array();
        $this->pagePool = array();
        $i = 0;
        $batchTimeIndex = array();

        //$this->checkTime(true, "start timer");

        $feedCol = $this->getMongoCollection($this->getMongoFb()->getFeedCollectionName());
        while (1){
            $cursor = $feedCol->find($this->getFacebookFeedDateRangeQuery())->skip($i)->limit(100);
            if (!$cursor->hasNext()){
                break;
            }else{
                //$this->checkTime(false, "working on feed:" . $i . "\n");
                fprintf($this->STDERR, "working on feed:" . $i . "\n");
            }
            foreach ($cursor as $feed){
                $i++;
                $page = \MongoDBRef::get($feedCol->db, $feed["fbPage"]);
                if ($page["mnemono"]["location"]["city"] != $city){
                    continue;
                }
                $timestampRecords = $this->queryTimestampByFeed($feed["_id"]);
                $reformedSeries = $this->reformulateTimestampSeries($page, $feed, $timestampRecords, $batchTimeIndex);
                if (!empty($reformedSeries)){
                    $countArray[$page["fbID"]][$feed["fbID"]] = $reformedSeries;
                }
            }
        }

        $this->outputCountArray($countArray, $batchTimeIndex, $this->feedPool, $this->pagePool);
    }
    private function getPreviousAverageFeedLikes(CGMongoFbPage $cgMongoFbPage){
        $batchTime = $cgMongoFbPage->getFirstBatchTimeWithInWindow($this->startDate,$this->endDate);
        return $cgMongoFbPage->getAverageFeedLikesInTheBatch($batchTime);
    }
    private function getPreviousAverageFeedComments(CGMongoFbPage $cgMongoFbPage){
        $batchTime = $cgMongoFbPage->getFirstBatchTimeWithInWindow($this->startDate,$this->endDate);
        return $cgMongoFbPage->getAverageFeedCommentsInTheBatch($batchTime);
    }
    private function skipNColumn($n){
        $ret = "";
        for($i = 0;$i<$n;$i++){
            $ret .= ",";
        }
        return $ret;
    }
    private function outputCountArray($countArray, $batchTimeIndex, $feedPool, $pageRaw){
        $this->outputString("fbpage,fbPageId,feed,feedLink,feedId,feedCreatedTime,FeedShareCounts,mnemonoCategory,pageLikeCount,"
            . "LastBatchBeforeCurrentWindowAverageLikes,LastBatchBeforeCurrentWindowAverageComments,"
            . "pageFeedCount,CurrentWindowAverageLikes,CurrentWindowAverageComments,");
        ksort($batchTimeIndex);
        foreach($batchTimeIndex as $batchTimeString => $value){
            $this->outputString($batchTimeString . "," . $this->skipNColumn(1));
        }
        $this->outputString("\n" . $this->skipNColumn(14));
        foreach($batchTimeIndex as $batchTimeString => $value){
            $this->outputString("deltaLike,deltaComment,");
        }
        $this->outputString("\n");
        foreach ($countArray as $pageId => $page){
            $cgFbPage = $pageRaw[$pageId];
            if ($cgFbPage instanceof CGMongoFbPage){
                $previousAvgLikes = $this->getPreviousAverageFeedLikes($cgFbPage);
                $previousAvgComments = $this->getPreviousAverageFeedComments($cgFbPage);
                foreach ($page as $feedId => $feed){
                    $cgFbFeed = $feedPool[$feedId];
                    if (!($cgFbFeed instanceof CGMongoFbFeed)){
                        continue;
                    }
                    $this->outputString($cgFbPage->getShortLink() . "," . $pageId . ",");

                    $this->outputString($cgFbFeed->getShortLink() . "," . preg_replace("/%/", "%%", $cgFbFeed->getRawLink()) . "," . $feedId . ",");
                    $this->outputString($cgFbFeed->getCreatedTime() . ",");
                    $this->outputString($cgFbFeed->getSharesCount() . ",");

                    $this->outputString($cgFbPage->getMnemonoCategory() . ",");
                    $this->outputString($cgFbPage->getLikes() . ",");

                    $this->outputString($previousAvgLikes. ",");
                    $this->outputString($previousAvgComments . ",");
                    $this->outputString($cgFbPage->getFeedCount() . ",");
                    $this->outputString($cgFbPage->getFeedAverageLike() . ",");
                    $this->outputString($cgFbPage->getFeedAverageComment() . ",");

                    foreach($batchTimeIndex as $batchTimeString => $value){
                        if (isset($feed[$batchTimeString])){
                            $timestampRecord = $feed[$batchTimeString];
                            $this->outputString($timestampRecord['deltaLike'].",");
                            $this->outputString($timestampRecord['deltaComment'].",");
                        }else{
                            $this->outputString($this->skipNColumn(2));
                        }
                    }
                    $this->outputString("\n");
                }
            }
        }
        $this->outputString("", true);
    }
    private function accumulatePageLikeAndComment($page, $feed, $timestampRecords){
        if (!isset($this->pagePool[$page["fbID"]])){
            $this->pagePool[$page["fbID"]] = new CGMongoFbPage($page);
        }
        $cgMongoFbPage = $this->pagePool[$page["fbID"]];
        if (!($cgMongoFbPage instanceof CGMongoFbPage)){
            return;
        }
        $cgMongoFbPage->setFeedCount($cgMongoFbPage->getFeedCount() + 1);

        $ret = $this->findMaxLikeAndMaxComment($timestampRecords);
        $cgMongoFbPage->setAccumulateLike($cgMongoFbPage->getAccumulateLike() + $ret['maxLike']);
        $cgMongoFbPage->setAccumulateComment($cgMongoFbPage->getAccumulateComment() + $ret['maxComment']);

        $this->feedPool[$feed["fbID"]] = new CGMongoFbFeed($feed);
    }
    private function reformulateTimestampSeries($page, $feed, $timestampRecords, & $batchTimeIndex){
        $this->accumulatePageLikeAndComment($page, $feed, $timestampRecords);
        $lastLikeCount = 0;
        $lastCommentCount = 0;
        $ret = array();
        foreach ($timestampRecords as $timestampRecord){
            if ($timestampRecord instanceof CGMongoFbFeedTimestamp ){
                $batchTimeString = $timestampRecord->getBatchTimeInISO();
                $batchTimeIndex[$batchTimeString] = 1;
                $totalLike = $timestampRecord->getLikesTotalCount();
                $deltaLike = $totalLike - $lastLikeCount;
                $lastLikeCount = $totalLike;

                $totalComment = $timestampRecord->getCommentsTotalCount();
                $deltaComment = $totalComment - $lastCommentCount;
                $lastCommentCount = $totalComment;

                $ret[$batchTimeString] = array(
                    "deltaLike" => $deltaLike,
                    "deltaComment" => $deltaComment,
                    "updateTime" => $timestampRecord->getUpdateTimeInISO(),
                );
            }
        }
        return $ret;
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
        return $dateRange;
    }
    /**
     * @return array mongo date query with range of $this->startDate and $this->endDate
     */
    private function getFacebookFeedDateRangeQuery(){
        $dateRange = array();
        if ($this->startDate != null){
            $dateRange["\$gte"] = gmdate(\DateTime::ISO8601, $this->startDate->sec);
        }
        if ($this->endDate != null){
            $dateRange["\$lte"] = gmdate(\DateTime::ISO8601, $this->endDate->sec);
        }
        if (empty($dateRange)){
            return array();
        }
        return array("created_time" => $dateRange);
    }
    private function checkTime($isRest = true, $displayMessage = ""){
        if ($isRest){
            $this->watchStartTime = time();
        }
        $this->watchEndTime = time();
        $this->watchDelta = $this->watchEndTime - $this->watchStartTime;
        fprintf($this->STDERR, $displayMessage . " deltaTime:" .$this->watchDelta . "\n");
        $this->watchStartTime = $this->watchEndTime;
    }
    private function releaseTimestampMemory(){
        $this->fbTimestamps = null;
    }
    /**
     * @param \MongoId $feedId
     * @return array
     */
    private function queryTimestampByFeed(\MongoId $feedId){
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
     * @param $colName
     * @return \MongoCollection
     */
    private function getMongoCollection($colName){
        return $this->getMongoFb()->getMongoCollection($colName);
    }

    /**
     * @return \MongoDB
     */
    private function getMongoDB(){
        return $this->getMongoFb()->getMongoDB();
    }

    /**
     * @return \MongoClient
     */
    private function getMongoClient(){
        return $this->getMongoFb()->getMongoClient();
    }
    /**
     * @return CGMongoFb
     */
    private function getMongoFb(){
        if ($this->mongoFb == null){
            $this->mongoFb = new CGMongoFb();
        }
        return $this->mongoFb;
    }

    private function outputString($str, $closeAfterWrite = false){
        if ($this->fp == null){
            $this->fp = fopen($this->filename,"w");
            if ($this->fp == null){
                fprintf($this->STDERR, "output file: " . $this->filename . " can't be written, redirect output to STDOUT\n");
                $this->fp = fopen('php://stdout', 'w+');
            }
        }
        fprintf($this->fp, $str);
        if ($closeAfterWrite){
            fclose($this->fp);
            $this->fp = null;
        }
    }
}