<?php
/**
 * User: kit
 * Date: 30/04/2016
 * Time: 5:04 PM
 */

namespace CodingGuys\Stat;

use CodingGuys\Document\FacebookFeedTimestamp;

class FbTopNReport extends FbFeedStat
{
    /**
     * @param $maxRecords array of CGMongoFbFeedTimestamp
     * use for debugging, very element should not be null
     */
    private function checkNull($maxRecords)
    {
        echo "count of max Records:" . count($maxRecords) . "\n";
        foreach ($maxRecords as $record)
        {
            if (!($record instanceof FacebookFeedTimestamp))
            {
                echo "get invalid object\n";
            }
        }
    }

    // TODO refine output format
    public function topNResult($topN)
    {
        mb_internal_encoding("UTF-8");
        $maxRecord = $this->queryTimestampMaxValue();

        $maxLike = array_values($maxRecord['maxLikeRecord']);
        usort($maxLike, array("CodingGuys\\Stat\\FbTopNReport", "cmpLikeRecord"));
        $topNLikes = $this->filterTopNLike($maxLike, $topN);
        $result = array();
        echo "{'topNLikes':[";
        $first = true;
        foreach ($topNLikes as $i => $v)
        {
            $result['topNLikes'][$i] = $this->extractTimestampNecessaryField($v);
            if ($first)
            {
                echo json_encode($result['topNLikes'][$i]);
                $first = false;
            } else
            {
                echo "," . json_encode($result['topNLikes'][$i]);
            }
        }
        echo "],'topNComments':[";
        $first = true;
        $maxComment = array_values($maxRecord['maxCommentRecord']);
        usort($maxComment, array("CodingGuys\\Stat\\FbTopNReport", "cmpCommentRecord"));
        $topNComments = $this->filterTopNComment($maxComment, $topN);
        foreach ($topNComments as $i => $v)
        {
            $result['topNComments'][$i] = $this->extractTimestampNecessaryField($v);
            if ($first)
            {
                echo json_encode($result['topNComments'][$i]);
                $first = false;
            } else
            {
                echo "," . json_encode($result['topNComments'][$i]);
            }
        }
        echo "]}";
        return;
    }

    /**
     * query feed's timestamp record in the pre-set date range.
     * feed's timestamp record could change with time.
     * this function will return only the timestamp with max like and max comment for a feed
     * @return array it contains two indexes, 'maxLikeRecord' link to the timestamp record with max value of like. 'maxCommentRecord' link to the timestamp record with max value of comment.
     */
    private function queryTimestampMaxValue()
    {
        $cursor = $this->findFeedByDateRange();
        // TODO rename maxLikeRecord to maxLikeRecords, maxCommentRecord to maxCommentRecords
        $maxLikeRecord = array();
        $maxCommentRecord = array();
        foreach ($cursor as $feed)
        {
            // TODO Replace CGMongoFbFeedTimestamp with FacebookFeedTimestamp 
            $timestampRecords = $this->findTimestampByFeed($feed["_id"]);
            if (empty($timestampRecords))
            {
                continue;
            }
            $ret = $this->findMaxLikeAndMaxComment($timestampRecords);
            $maxLikeRecord[$feed["fbID"]] = $ret['maxLikeRecord'];
            $maxCommentRecord[$feed["fbID"]] = $ret['maxCommentRecord'];
        }
        return array('maxLikeRecord' => $maxLikeRecord, 'maxCommentRecord' => $maxCommentRecord);
    }

    private function extractTimestampNecessaryField(FacebookFeedTimestamp $t)
    {
        $fbFeed = \MongoDBRef::get($this->getMongoDB(), $t->getFbFeed());
        $updateTime = new \DateTime();
        $updateTime->setTimestamp($t->getUpdateTime()->sec);
        return array(
            'shortLink' => $this->extractShortLink($fbFeed),
            'likes_total_count' => $t->getLikesTotalCount(),
            'comments_total_count' => $t->getCommentsTotalCount(),
            'message' => (isset($fbFeed["message"]) ? mb_substr($fbFeed["message"], 0, 20) . "..." : ""),
            "updateTime" => $updateTime->format(\DateTime::ISO8601),
        );
    }

    private function extractShortLink($fb)
    {
        return (isset($fb["link"]) && $this->isFbPhotoLink($fb["link"]) ?
            $fb["link"] : "https://www.facebook.com/" . $fb["fbID"]);
    }

    private function isFbPhotoLink($link)
    {
        return preg_match('/www\.facebook\.com(.*)photos/', $link) > 0;
    }

    const COMMENTS_TOTAL_COUNT = "comments_total_count";
    const LIKES_TOTAL_COUNT = "likes_total_count";

    private function filterTopNComment($sortedCommentTimestamp, $topN)
    {
        return $this->filterTopN(FbTopNReport::COMMENTS_TOTAL_COUNT, $sortedCommentTimestamp, $topN);
    }

    private function filterTopNLike($sortedLikeTimestamp, $topN)
    {
        return $this->filterTopN(FbTopNReport::LIKES_TOTAL_COUNT, $sortedLikeTimestamp, $topN);
    }

    /**
     * @param string $fieldName comments_total_count|likes_total_count
     * @param $sortedTimestamp
     * @param int $topN
     * @return array top n result
     *
     * get top n result, any record has the sample score as the n-th record, it also will be included;
     */
    private function filterTopN($fieldName, $sortedTimestamp, $topN)
    {
        if ($topN <= 0)
        {
            return array();
        }
        $ret = array_slice($sortedTimestamp, 0, $topN);
        for ($i = $topN; $i < count($sortedTimestamp); $i++)
        {
            $lastRecord = $sortedTimestamp[$i - 1];
            $currentRecord = $sortedTimestamp[$i];
            if (!($lastRecord instanceof FacebookFeedTimestamp)
                || !($currentRecord instanceof FacebookFeedTimestamp)
            )
            {
                break;
            }

            if ($fieldName == FbTopNReport::COMMENTS_TOTAL_COUNT
                && $lastRecord->getCommentsTotalCount() == $currentRecord->getCommentsTotalCount()
            )
            {
                $ret[] = $sortedTimestamp[$i];
            } else if ($fieldName == FbTopNReport::LIKES_TOTAL_COUNT
                && $lastRecord->getLikesTotalCount() == $currentRecord->getLikesTotalCount()
            )
            {
                $ret[] = $sortedTimestamp[$i];
            } else
            {
                break;
            }
        }
        return $ret;
    }

    /**
     * @param FacebookFeedTimestamp $a
     * @param FacebookFeedTimestamp $b
     * @return bool
     */
    public static function cmpLikeRecord(FacebookFeedTimestamp $a, FacebookFeedTimestamp $b)
    {
        return $a->getLikesTotalCount() < $b->getLikesTotalCount();
    }

    /**
     * @param FacebookFeedTimestamp $a
     * @param FacebookFeedTimestamp $b
     * @return bool
     */
    public static function cmpCommentRecord(FacebookFeedTimestamp $a, FacebookFeedTimestamp $b)
    {
        return $a->getCommentsTotalCount() < $b->getCommentsTotalCount();
    }
}