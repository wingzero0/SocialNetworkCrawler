<?php
/**
 * User: kit
 * Date: 04/06/2016
 * Time: 5:51 PM
 */

namespace CodingGuys\Document;

use CodingGuys\Exception\KeyNotExistsException;

class FacebookFeedTimestamp extends BaseObj
{
    private $id;
    private $likeTotalCount;
    private $commentsTotalCount;
    private $shareTotalCount;
    private $fbPage;
    private $fbFeed;
    private $updateTime;
    private $batchTime;

    const TARGET_COLLECTION = "FacebookFeedTimestamp";

    const FIELD_ID = "id";
    const FIELD_LIKES_TOTAL_COUNT = "likeTotalCount";
    const FIELD_COMMENTS_TOTAL_COUNT = "commentsTotalCount";
    const FIELD_SHARES_TOTAL_COUNT = "shareTotalCount";
    const FIELD_FB_PAGE = "fbPage";
    const FIELD_FB_FEED = "fbFeed";
    const FIELD_UPDATE_TIME = "updateTime";
    const FIELD_BATCH_TIME = "batchTime";

    const KEY_ID = "_id";
    const KEY_LIKES_TOTAL_COUNT = "likes_total_count";
    const KEY_COMMENTS_TOTAL_COUNT = "comments_total_count";
    const KEY_SHARES_TOTAL_COUNT = "shares_total_count";
    const KEY_FB_PAGE = "fbPage";
    const KEY_FB_FEED = "fbFeed";
    const KEY_UPDATE_TIME = "updateTime";
    const KEY_BATCH_TIME = "batchTime";

    private static $dbMapping = array(
        FacebookFeedTimestamp::FIELD_ID => FacebookFeedTimestamp::KEY_ID,
        FacebookFeedTimestamp::FIELD_LIKES_TOTAL_COUNT => FacebookFeedTimestamp::KEY_LIKES_TOTAL_COUNT,
        FacebookFeedTimestamp::FIELD_COMMENTS_TOTAL_COUNT => FacebookFeedTimestamp::KEY_COMMENTS_TOTAL_COUNT,
        FacebookFeedTimestamp::FIELD_SHARES_TOTAL_COUNT => FacebookFeedTimestamp::KEY_SHARES_TOTAL_COUNT,
        FacebookFeedTimestamp::FIELD_FB_PAGE => FacebookFeedTimestamp::KEY_FB_PAGE,
        FacebookFeedTimestamp::FIELD_FB_FEED => FacebookFeedTimestamp::KEY_FB_FEED,
        FacebookFeedTimestamp::FIELD_UPDATE_TIME => FacebookFeedTimestamp::KEY_UPDATE_TIME,
        FacebookFeedTimestamp::FIELD_BATCH_TIME => FacebookFeedTimestamp::KEY_BATCH_TIME,
    );

    /**
     * @return \MongoId
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param \MongoId $id
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
        return $arr;
    }

    public function getCollectionName()
    {
        return FacebookFeedTimestamp::TARGET_COLLECTION;
    }

    /**
     * @return int
     */
    public function getLikeTotalCount()
    {
        return $this->likeTotalCount;
    }

    /**
     * @param int $likeTotalCount
     */
    public function setLikeTotalCount($likeTotalCount)
    {
        if ($likeTotalCount === null)
        {
            $this->likeTotalCount = 0;
        } else
        {
            $this->likeTotalCount = $likeTotalCount;
        }
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
        if ($commentsTotalCount === null)
        {
            $this->commentsTotalCount = 0;
        }
        $this->commentsTotalCount = $commentsTotalCount;
    }

    /**
     * @return \MongoDBRef
     */
    public function getFbPage()
    {
        return $this->fbPage;
    }

    /**
     * @param \MongoDBRef $fbPage
     */
    public function setFbPage(\MongoDBRef $fbPage)
    {
        $this->fbPage = $fbPage;
    }

    /**
     * @return \MongoDBRef
     */
    public function getFbFeed()
    {
        return $this->fbFeed;
    }

    /**
     * @param \MongoDBRef $fbFeed
     */
    public function setFbFeed(\MongoDBRef $fbFeed)
    {
        $this->fbFeed = $fbFeed;
    }

    /**
     * @return \MongoDate
     */
    public function getUpdateTime()
    {
        return $this->updateTime;
    }

    /**
     * @param \MongoDate $updateTime
     */
    public function setUpdateTime(\MongoDate $updateTime)
    {
        $this->updateTime = $updateTime;
    }

    /**
     * @return \MongoDate
     */
    public function getBatchTime()
    {
        return $this->batchTime;
    }

    /**
     * @param \MongoDate $batchTime
     */
    public function setBatchTime(\MongoDate $batchTime)
    {
        $this->batchTime = $batchTime;
    }

    /**
     * @return int
     */
    public function getShareTotalCount()
    {
        return $this->shareTotalCount;
    }

    /**
     * @param int $shareTotalCount
     */
    public function setShareTotalCount($shareTotalCount)
    {
        if ($shareTotalCount === null)
        {
            $this->shareTotalCount = 0;
        }
        $this->shareTotalCount = $shareTotalCount;
    }
}