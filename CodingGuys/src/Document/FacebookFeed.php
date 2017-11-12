<?php
/**
 * User: kit
 * Date: 29/05/2016
 * Time: 4:39 PM
 */

namespace CodingGuys\Document;

use CodingGuys\Exception\KeyNotExistsException;

class FacebookFeed extends BaseObj
{
    private $id;
    private $fbId;
    private $from;
    private $message;
    private $picture;
    private $link;
    private $type;
    private $statusType;
    private $createdTime;
    private $updatedTime;
    private $shares;
    private $isExpired;
    private $comments;
    private $attachments;
    private $fbPage;
    private $fbResponse;
    private $story;
    private $reactionsLike;
    private $reactionsLove;
    private $reactionsWow;
    private $reactionsHaha;
    private $reactionsSad;
    private $reactionsAngry;

    const TARGET_COLLECTION = "FacebookFeed";

    const DB_KEY_ID = "_id";
    const DB_KEY_FB_ID = "fbID";
    const DB_KEY_FROM = "from";
    const DB_KEY_MESSAGE = "message";
    const DB_KEY_PICTURE = "picture";
    const DB_KEY_LINK = "link";
    const DB_KEY_TYPE = "type";
    const DB_KEY_STATUS_TYPE = "status_type";
    const DB_KEY_CREATED_TIME = "created_time";
    const DB_KEY_UPDATED_TIME = "updated_time";
    const DB_KEY_SHARES = "shares";
    const DB_KEY_IS_EXPIRED = "is_expired";
    const DB_KEY_COMMENTS = "comments";
    const DB_KEY_ATTACHMENTS = "attachments";
    const DB_KEY_FB_PAGE = "fbPage";
    const DB_KEY_STORY = "story";
    const DB_KEY_REACTIONS_LIKE = "reactions_like";
    const DB_KEY_REACTIONS_LOVE = "reactions_love";
    const DB_KEY_REACTIONS_WOW = "reactions_wow";
    const DB_KEY_REACTIONS_HAHA = "reactions_haha";
    const DB_KEY_REACTIONS_SAD = "reactions_sad";
    const DB_KEY_REACTIONS_ANGRY = "reactions_angry";

    const FIELD_ID = "id";
    const FIELD_FB_ID = "fbId";
    const FIELD_FROM = "from";
    const FIELD_MESSAGE = "message";
    const FIELD_PICTURE = "picture";
    const FIELD_LINK = "link";
    const FIELD_TYPE = "type";
    const FIELD_STATUS_TYPE = "statusType";
    const FIELD_CREATED_TIME = "createdTime";
    const FIELD_UPDATED_TIME = "updatedTime";
    const FIELD_SHARES = "shares";
    const FIELD_IS_EXPIRED = "isExpired";
    const FIELD_COMMENTS = "comments";
    const FIELD_ATTACHMENTS = "attachments";
    const FIELD_FB_PAGE = "fbPage";
    const FIELD_STORY = "story";
    const FIELD_REACTIONS_LIKE = "reactionsLike";
    const FIELD_REACTIONS_LOVE = "reactionsLove";
    const FIELD_REACTIONS_WOW = "reactionsWow";
    const FIELD_REACTIONS_HAHA = "reactionsHaha";
    const FIELD_REACTIONS_SAD = "reactionsSad";
    const FIELD_REACTIONS_ANGRY = "reactionsAngry";

    private static $dbMapping = array(
        self::FIELD_ID => self::DB_KEY_ID,
        self::FIELD_FB_ID => self::DB_KEY_FB_ID,
        self::FIELD_FROM => self::DB_KEY_FROM,
        self::FIELD_MESSAGE => self::DB_KEY_MESSAGE,
        self::FIELD_PICTURE => self::DB_KEY_PICTURE,
        self::FIELD_LINK => self::DB_KEY_LINK,
        self::FIELD_TYPE => self::DB_KEY_TYPE,
        self::FIELD_STATUS_TYPE => self::DB_KEY_STATUS_TYPE,
        self::FIELD_CREATED_TIME => self::DB_KEY_CREATED_TIME,
        self::FIELD_UPDATED_TIME => self::DB_KEY_UPDATED_TIME,
        self::FIELD_SHARES => self::DB_KEY_SHARES,
        self::FIELD_IS_EXPIRED => self::DB_KEY_IS_EXPIRED,
        self::FIELD_COMMENTS => self::DB_KEY_COMMENTS,
        self::FIELD_ATTACHMENTS => self::DB_KEY_ATTACHMENTS,
        self::FIELD_FB_PAGE => self::DB_KEY_FB_PAGE,
        self::FIELD_STORY => self::DB_KEY_STORY,
        self::FIELD_REACTIONS_LIKE => self::DB_KEY_REACTIONS_LIKE,
        self::FIELD_REACTIONS_LOVE => self::DB_KEY_REACTIONS_LOVE,
        self::FIELD_REACTIONS_WOW => self::DB_KEY_REACTIONS_WOW,
        self::FIELD_REACTIONS_HAHA => self::DB_KEY_REACTIONS_HAHA,
        self::FIELD_REACTIONS_SAD => self::DB_KEY_REACTIONS_SAD,
        self::FIELD_REACTIONS_ANGRY => self::DB_KEY_REACTIONS_ANGRY,
    );

    /**
     * @param array $fbArray
     * @param array $fbPage dbRef
     * @return FacebookFeed
     */
    public static function constructByFbArray($fbArray, $fbPage = null){
        $feed = new FacebookFeed();
        $fbArray[self::DB_KEY_FB_ID] = $fbArray["id"];
        unset($fbArray["id"]);
        $feed->setFbResponse($fbArray);
        $feed->setFbPage($fbPage);
        return $feed;
    }

    /**
     * @param array $mongoArray
     * @return FacebookFeed
     */
    public static function constructByMongoArray($mongoArray){
        $feed = new FacebookFeed($mongoArray);
        return $feed;
    }

    protected function init()
    {
        foreach (self::$dbMapping as $field => $dbCol)
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
        $this->setFbResponse(array());
    }

    /**
     * @return array|null
     * @throws \Exception
     */
    public function toArray()
    {
        $arr = $this->getMongoRawData();
        $arr = array_merge($arr, $this->getFbResponse());
        foreach (self::$dbMapping as $field => $dbCol)
        {
            $arr[$dbCol] = $this->{"get" . ucfirst($field)}();
        }
        $arr = array_filter($arr, array($this, 'filterNonNullValue'));
        return $arr;
    }

    public function getShortLink()
    {
        return "https://www.facebook.com/" . $this->getFbId();
    }

    public function guessLink()
    {
        if ($this->getStatusType() == "added_video")
        {
            return $this->getLink();
        }

        $story = $this->getStory();
        if (empty($story))
        {
            return $this->getShortLink();
        }

        $pattern = "/new photos to the album:/";
        $ret = preg_match($pattern, $story);
        if ($ret > 0)
        {
            return $this->getLink();
        }

        $pattern = "/cover photo./";
        $ret = preg_match($pattern, $story);
        if ($ret > 0)
        {
            return $this->getLink();
        }

        return $this->getShortLink();
    }

    /**
     * @return int
     */
    public function getSharesCount()
    {
        $shares = $this->getShares();
        if (isset($shares["count"]))
        {
            return $shares["count"];
        }
        return 0;
    }

    /**
     * @return array
     */
    public function getReactionsLike()
    {
        return $this->reactionsLike;
    }

    /**
     * @param array $reactionsLike
     */
    public function setReactionsLike($reactionsLike)
    {
        $this->reactionsLike = $reactionsLike;
    }

    /**
     * @return array
     */
    public function getReactionsLove()
    {
        return $this->reactionsLove;
    }

    /**
     * @param array $reactionsLove
     */
    public function setReactionsLove($reactionsLove)
    {
        $this->reactionsLove = $reactionsLove;
    }

    /**
     * @return array
     */
    public function getReactionsWow()
    {
        return $this->reactionsWow;
    }

    /**
     * @param array $reactionsWow
     */
    public function setReactionsWow($reactionsWow)
    {
        $this->reactionsWow = $reactionsWow;
    }

    /**
     * @return array
     */
    public function getReactionsHaha()
    {
        return $this->reactionsHaha;
    }

    /**
     * @param array $reactionsHaha
     */
    public function setReactionsHaha($reactionsHaha)
    {
        $this->reactionsHaha = $reactionsHaha;
    }

    /**
     * @return array
     */
    public function getReactionsSad()
    {
        return $this->reactionsSad;
    }

    /**
     * @param array $reactionsSad
     */
    public function setReactionsSad($reactionsSad)
    {
        $this->reactionsSad = $reactionsSad;
    }

    /**
     * @return array
     */
    public function getReactionsAngry()
    {
        return $this->reactionsAngry;
    }

    /**
     * @param array $reactionsAngry
     */
    public function setReactionsAngry($reactionsAngry)
    {
        $this->reactionsAngry = $reactionsAngry;
    }

    /**
     * @return string
     */
    public function getStory()
    {
        return $this->story;
    }

    /**
     * @param string $story
     */
    public function setStory($story)
    {
        $this->story = $story;
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
    public function setId(\MongoDB\BSON\ObjectID $id = null)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getFbId()
    {
        return $this->fbId;
    }

    /**
     * @param string $fbId
     */
    public function setFbId($fbId)
    {
        $this->fbId = $fbId;
    }

    /**
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param string $from
     */
    public function setFrom($from)
    {
        $this->from = $from;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getPicture()
    {
        return $this->picture;
    }

    /**
     * @param string $picture
     */
    public function setPicture($picture)
    {
        $this->picture = $picture;
    }

    /**
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param string $link
     */
    public function setLink($link)
    {
        $this->link = $link;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getStatusType()
    {
        return $this->statusType;
    }

    /**
     * @param string $statusType
     */
    public function setStatusType($statusType)
    {
        $this->statusType = $statusType;
    }

    /**
     * @return string
     */
    public function getCreatedTime()
    {
        return $this->createdTime;
    }

    /**
     * @param string $createdTime
     */
    public function setCreatedTime($createdTime)
    {
        $this->createdTime = $createdTime;
    }

    /**
     * @return string
     */
    public function getUpdatedTime()
    {
        return $this->updatedTime;
    }

    /**
     * @param string $updatedTime
     */
    public function setUpdatedTime($updatedTime)
    {
        $this->updatedTime = $updatedTime;
    }

    /**
     * @return array|null
     */
    public function getFbResponse()
    {
        return $this->fbResponse;
    }

    /**
     * @param array $fbResponse
     */
    public function setFbResponse($fbResponse)
    {
        $this->fbResponse = $fbResponse;
        if (!empty($fbResponse))
        {
            foreach (self::$dbMapping as $field => $fbCol)
            {
                if (isset($fbResponse[$fbCol]))
                {
                    $this->{"set" . ucfirst($field)}($fbResponse[$fbCol]);
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getShares()
    {
        return $this->shares;
    }

    /**
     * @param array $shares
     */
    public function setShares($shares)
    {
        $this->shares = $shares;
    }

    /**
     * @return bool
     */
    public function getIsExpired()
    {
        return $this->isExpired;
    }

    /**
     * @param bool $isExpired
     */
    public function setIsExpired($isExpired)
    {
        $this->isExpired = $isExpired;
    }



    /**
     * @return array
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * @param array $comments
     */
    public function setComments($comments)
    {
        $this->comments = $comments;
    }

    /**
     * @return array
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * @param array $attachments
     */
    public function setAttachments($attachments)
    {
        $this->attachments = $attachments;
    }

    /**
     * @return array
     */
    public function getFbPage()
    {
        return $this->fbPage;
    }

    /**
     * @param array $fbPage
     */
    public function setFbPage($fbPage)
    {
        $this->fbPage = $fbPage;
    }

    public function getCollectionName()
    {
        return self::TARGET_COLLECTION;
    }

    /**
     * @param FacebookFeed $obj
     * @return bool
     */
    public function isDiffMetricFrom(FacebookFeed $obj)
    {
        $selfTotal = $this->getMetricTotal($this->getReactionsLike());
        $objTotal = $obj->getMetricTotal($obj->getReactionsLike());
        if ($selfTotal !== $objTotal)
        {
            return true;
        }

        $selfTotal = $this->getMetricTotal($this->getReactionsLove());
        $objTotal = $obj->getMetricTotal($obj->getReactionsLove());
        if ($selfTotal !== $objTotal)
        {
            return true;
        }

        $selfTotal = $this->getMetricTotal($this->getReactionsWow());
        $objTotal = $obj->getMetricTotal($obj->getReactionsWow());
        if ($selfTotal !== $objTotal)
        {
            return true;
        }

        $selfTotal = $this->getMetricTotal($this->getReactionsHaha());
        $objTotal = $obj->getMetricTotal($obj->getReactionsHaha());
        if ($selfTotal !== $objTotal)
        {
            return true;
        }

        $selfTotal = $this->getMetricTotal($this->getReactionsSad());
        $objTotal = $obj->getMetricTotal($obj->getReactionsSad());
        if ($selfTotal !== $objTotal)
        {
            return true;
        }

        $selfTotal = $this->getMetricTotal($this->getReactionsAngry());
        $objTotal = $obj->getMetricTotal($obj->getReactionsAngry());
        if ($selfTotal !== $objTotal)
        {
            return true;
        }

        $selfTotal = $this->getMetricTotal($this->getComments());
        $objTotal = $obj->getMetricTotal($obj->getComments());
        if ($selfTotal !== $objTotal)
        {
            return true;
        }

        $selfTotal = $this->getMetricTotal($this->getShares());
        $objTotal = $obj->getMetricTotal($obj->getShares());
        if ($selfTotal !== $objTotal)
        {
            return true;
        }

        return false;
    }

    /**
     * @param object|array $metric
     * @return int $total
     */
    private function getMetricTotal($metric)
    {
        $total = 0;
        if (is_object($metric))
        {
            $metric = json_decode(json_encode($metric), true);
        }
        if (is_array($metric))
        {
            if (isset($metric['summary']) &&
                isset($metric['summary']['total_count']))
            {
                $total = $metric['summary']['total_count'];
            } else if (isset($metric['count']))
            {
                $total = $metric['count'];
            }
        }
        return $total;
    }
}
