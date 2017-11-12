<?php
/**
 * User: kit
 * Date: 04/06/2016
 * Time: 5:51 PM
 */

namespace CodingGuys\Document;

use CodingGuys\Exception\KeyNotExistsException;
use CodingGuys\Utility\DateUtility;

class FacebookFeedTimestamp extends BaseObj
{
    private $id;
    private $commentsTotalCount;
    private $sharesTotalCount;
    private $fbPage;
    private $fbFeed;
    private $updateTime;
    private $batchTime;
    private $reactionsLikeTotalCount;
    private $reactionsLoveTotalCount;
    private $reactionsWowTotalCount;
    private $reactionsHahaTotalCount;
    private $reactionsSadTotalCount;
    private $reactionsAngryTotalCount;
    private $postCreatedTime;

    const TARGET_COLLECTION = "FacebookFeedTimestamp";

    const FIELD_ID = "id";
    const FIELD_REACTIONS_LIKE_TOTAL_COUNT = "reactionsLikeTotalCount";
    const FIELD_REACTIONS_LOVE_TOTAL_COUNT = "reactionsLoveTotalCount";
    const FIELD_REACTIONS_WOW_TOTAL_COUNT = "reactionsWowTotalCount";
    const FIELD_REACTIONS_HAHA_TOTAL_COUNT = "reactionsHahaTotalCount";
    const FIELD_REACTIONS_SAD_TOTAL_COUNT = "reactionsSadTotalCount";
    const FIELD_REACTIONS_ANGRY_TOTAL_COUNT = "reactionsAngryTotalCount";
    const FIELD_COMMENTS_TOTAL_COUNT = "commentsTotalCount";
    const FIELD_SHARES_TOTAL_COUNT = "sharesTotalCount";
    const FIELD_FB_PAGE = "fbPage";
    const FIELD_FB_FEED = "fbFeed";
    const FIELD_UPDATE_TIME = "updateTime";
    const FIELD_BATCH_TIME = "batchTime";
    const FIELD_POST_CREATED_TIME = "postCreatedTime";

    const KEY_ID = "_id";
    const KEY_REACTIONS_LIKE_TOTAL_COUNT = "reactions_like_total_count";
    const KEY_REACTIONS_LOVE_TOTAL_COUNT = "reactions_love_total_count";
    const KEY_REACTIONS_WOW_TOTAL_COUNT = "reactions_wow_total_count";
    const KEY_REACTIONS_HAHA_TOTAL_COUNT = "reactions_haha_total_count";
    const KEY_REACTIONS_SAD_TOTAL_COUNT = "reactions_sad_total_count";
    const KEY_REACTIONS_ANGRY_TOTAL_COUNT = "reactions_angry_total_count";
    const KEY_COMMENTS_TOTAL_COUNT = "comments_total_count";
    const KEY_SHARES_TOTAL_COUNT = "shares_total_count";
    const KEY_FB_PAGE = "fbPage";
    const KEY_FB_FEED = "fbFeed";
    const KEY_UPDATE_TIME = "updateTime";
    const KEY_BATCH_TIME = "batchTime";
    const KEY_POST_CREATED_TIME = "post_created_time";

    private static $dbMapping = array(
        FacebookFeedTimestamp::FIELD_ID => FacebookFeedTimestamp::KEY_ID,
        FacebookFeedTimestamp::FIELD_REACTIONS_LIKE_TOTAL_COUNT => FacebookFeedTimestamp::KEY_REACTIONS_LIKE_TOTAL_COUNT,
        FacebookFeedTimestamp::FIELD_REACTIONS_LOVE_TOTAL_COUNT => FacebookFeedTimestamp::KEY_REACTIONS_LOVE_TOTAL_COUNT,
        FacebookFeedTimestamp::FIELD_REACTIONS_WOW_TOTAL_COUNT => FacebookFeedTimestamp::KEY_REACTIONS_WOW_TOTAL_COUNT,
        FacebookFeedTimestamp::FIELD_REACTIONS_HAHA_TOTAL_COUNT => FacebookFeedTimestamp::KEY_REACTIONS_HAHA_TOTAL_COUNT,
        FacebookFeedTimestamp::FIELD_REACTIONS_SAD_TOTAL_COUNT => FacebookFeedTimestamp::KEY_REACTIONS_SAD_TOTAL_COUNT,
        FacebookFeedTimestamp::FIELD_REACTIONS_ANGRY_TOTAL_COUNT => FacebookFeedTimestamp::KEY_REACTIONS_ANGRY_TOTAL_COUNT,
        FacebookFeedTimestamp::FIELD_COMMENTS_TOTAL_COUNT => FacebookFeedTimestamp::KEY_COMMENTS_TOTAL_COUNT,
        FacebookFeedTimestamp::FIELD_SHARES_TOTAL_COUNT => FacebookFeedTimestamp::KEY_SHARES_TOTAL_COUNT,
        FacebookFeedTimestamp::FIELD_FB_PAGE => FacebookFeedTimestamp::KEY_FB_PAGE,
        FacebookFeedTimestamp::FIELD_FB_FEED => FacebookFeedTimestamp::KEY_FB_FEED,
        FacebookFeedTimestamp::FIELD_UPDATE_TIME => FacebookFeedTimestamp::KEY_UPDATE_TIME,
        FacebookFeedTimestamp::FIELD_BATCH_TIME => FacebookFeedTimestamp::KEY_BATCH_TIME,
        FacebookFeedTimestamp::FIELD_POST_CREATED_TIME => FacebookFeedTimestamp::KEY_POST_CREATED_TIME,
    );

    /**
     * @return FacebookFeedTimestamp
     */
    public static function createEmptyObj(){
        $obj = new FacebookFeedTimestamp();
        $obj->init();
        return $obj;
    }

    /**
     * @return \MongoDB\BSON\ObjectID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param \MongoDB\BSON\ObjectID $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    protected function init()
    {
        foreach (FacebookFeedTimestamp::$dbMapping as $field => $dbCol)
        {
            try
            {
                $val = $this->getFromRaw($dbCol);
                $this->{"set" . ucfirst($field)}($val);
            } catch (KeyNotExistsException $e)
            {
                $this->{"set" . ucfirst($field)}(null);
            }
        }
    }

    public function toArray()
    {
        $arr = $this->getMongoRawData();
        foreach (FacebookFeedTimestamp::$dbMapping as $field => $dbCol)
        {
            $arr[$dbCol] = $this->{"get" . ucfirst($field)}();
        }
        $arr = array_filter($arr, array($this, 'filterNonNullValue'));
        return $arr;
    }

    public function getCollectionName()
    {
        return FacebookFeedTimestamp::TARGET_COLLECTION;
    }

    /**
     * @return int
     */
    public function getReactionsLikeTotalCount()
    {
        return $this->reactionsLikeTotalCount;
    }

    /**
     * @param int $reactionsLikeTotalCount
     */
    public function setReactionsLikeTotalCount($reactionsLikeTotalCount)
    {
        $this->reactionsLikeTotalCount = intval($reactionsLikeTotalCount);
    }

    /**
     * @return int
     */
    public function getReactionsLoveTotalCount()
    {
        return $this->reactionsLoveTotalCount;
    }

    /**
     * @param int $reactionsLoveTotalCount
     */
    public function setReactionsLoveTotalCount($reactionsLoveTotalCount)
    {
        $this->reactionsLoveTotalCount = intval($reactionsLoveTotalCount);
    }

    /**
     * @return int
     */
    public function getReactionsWowTotalCount()
    {
        return $this->reactionsWowTotalCount;
    }

    /**
     * @param int $reactionsWowTotalCount
     */
    public function setReactionsWowTotalCount($reactionsWowTotalCount)
    {
        $this->reactionsWowTotalCount = intval($reactionsWowTotalCount);
    }

    /**
     * @return int
     */
    public function getReactionsHahaTotalCount()
    {
        return $this->reactionsHahaTotalCount;
    }

    /**
     * @param int $reactionsHahaTotalCount
     */
    public function setReactionsHahaTotalCount($reactionsHahaTotalCount)
    {
        $this->reactionsHahaTotalCount = intval($reactionsHahaTotalCount);
    }

    /**
     * @return int
     */
    public function getReactionsSadTotalCount()
    {
        return $this->reactionsSadTotalCount;
    }

    /**
     * @param int $reactionsSadTotalCount
     */
    public function setReactionsSadTotalCount($reactionsSadTotalCount)
    {
        $this->reactionsSadTotalCount = intval($reactionsSadTotalCount);
    }

    /**
     * @return int
     */
    public function getReactionsAngryTotalCount()
    {
        return $this->reactionsAngryTotalCount;
    }

    /**
     * @param int $reactionsAngryTotalCount
     */
    public function setReactionsAngryTotalCount($reactionsAngryTotalCount)
    {
        $this->reactionsAngryTotalCount = intval($reactionsAngryTotalCount);
    }

    /**
     * @return int
     */
    public function getCommentsTotalCount()
    {
        return $this->commentsTotalCount;
    }

    /**
     * @param int $commentsTotalCount
     */
    public function setCommentsTotalCount($commentsTotalCount)
    {
        $this->commentsTotalCount = intval($commentsTotalCount);
    }

    /**
     * @return int
     */
    public function getSharesTotalCount()
    {
        return $this->sharesTotalCount;
    }

    /**
     * @param int $sharesTotalCount
     */
    public function setSharesTotalCount($sharesTotalCount)
    {
        $this->sharesTotalCount = intval($sharesTotalCount);
    }

    /**
     * @return array|null
     */
    public function getFbPage()
    {
        return $this->fbPage;
    }

    /**
     * @param array $fbPage dbRef
     */
    public function setFbPage($fbPage)
    {
        $this->fbPage = $fbPage;
    }

    /**
     * @return \MongoDBRef|array
     */
    public function getFbFeed()
    {
        return $this->fbFeed;
    }

    /**
     * @param \MongoDBRef|array $fbFeed
     */
    public function setFbFeed($fbFeed)
    {
        $this->fbFeed = $fbFeed;
    }

    /**
     * @return \MongoDB\BSON\UTCDateTime
     */
    public function getUpdateTime()
    {
        return $this->updateTime;
    }

    /**
     * @param \MongoDB\BSON\UTCDateTime $updateTime
     */
    public function setUpdateTime(\MongoDB\BSON\UTCDateTime $updateTime = null)
    {
        $this->updateTime = $updateTime;
    }

    /**
     * @return \MongoDB\BSON\UTCDateTime
     */
    public function getBatchTime()
    {
        return $this->batchTime;
    }

    /**
     * @param \MongoDB\BSON\UTCDateTime $batchTime
     */
    public function setBatchTime(\MongoDB\BSON\UTCDateTime $batchTime = null)
    {
        $this->batchTime = $batchTime;
    }

    /**
     * @return string
     */
    public function getBatchTimeInISO()
    {
        $batchTime = $this->getBatchTime();
        if ($batchTime == null)
        {
            return "";
        }
        return DateUtility::convertMongoDateToISODate($batchTime);
    }

    /**
     * @return \MongoDB\BSON\UTCDateTime $postCreatedTime
     */
    public function getPostCreatedTime()
    {
        return $this->postCreatedTime;
    }

    /**
     * @param string $postCreatedTime|\MongoDB\BSON\UTCDateTime $postCreatedTime
     */
    public function setPostCreatedTime($postCreatedTime)
    {
        if ($postCreatedTime instanceof \MongoDB\BSON\UTCDateTime)
        {
            $this->postCreatedTime = $postCreatedTime;
        } else
        {
            $this->postCreatedTime = new \MongoDB\BSON\UTCDateTime(
                strtotime($postCreatedTime) * 1000
            );
        }
    }
}
