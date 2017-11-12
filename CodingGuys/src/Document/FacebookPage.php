<?php

/**
 * User: kit
 * Date: 24/04/2016
 * Time: 3:32 PM
 */

namespace CodingGuys\Document;

use CodingGuys\Exception\KeyNotExistsException;

class FacebookPage extends BaseObj
{
    private $mnemono;
    private $_id;
    private $fbID;
    private $exception;
    private $fbResponse;
    private $feedCount;
    private $accumulateComment;
    private $accumulateLike;
    private $error;
    private $lastPostCreatedTime;
    private $wereHereCount;
    private $talkingAboutCount;
    private $fanCount;
    private $overallStarRating;
    private $ratingCount;

    const TARGET_COLLECTION = "FacebookPage";

    const DB_KEY_ID = "_id";
    const DB_KEY_FB_ID = "fbID";
    const DB_KEY_MNEMONO = "mnemono";
    const DB_KEY_EXCEPTION = "exception";
    const DB_KEY_ERROR = "error";
    const DB_KEY_LAST_POST_CREATED_TIME = "last_post_created_time";
    const DB_KEY_WERE_HERE_COUNT = "were_here_count";
    const DB_KEY_TALKING_ABOUT_COUNT = "talking_about_count";
    const DB_KEY_FAN_COUNT = "fan_count";
    const DB_KEY_OVERALL_STAR_RATING = "overall_star_rating";
    const DB_KEY_RATING_COUNT = "rating_count";

    const FIELD_ID = "id";
    const FIELD_FB_ID = "fbId";
    const FIELD_MNEMONO = "mnemono";
    const FIELD_EXCEPTION = "exception";
    const FIELD_ERROR = "error";
    const FIELD_LAST_POST_CREATED_TIME = "lastPostCreatedTime";
    const FIELD_WERE_HERE_COUNT = "wereHereCount";
    const FIELD_TALKING_ABOUT_COUNT = "talkingAboutCount";
    const FIELD_FAN_COUNT = "fanCount";
    const FIELD_OVERALL_STAR_RATING = "overallStarRating";
    const FIELD_RATING_COUNT = "ratingCount";

    private static $dbMapping = array(
        self::FIELD_ID => self::DB_KEY_ID,
        self::FIELD_FB_ID => self::DB_KEY_FB_ID,
        self::FIELD_MNEMONO => self::DB_KEY_MNEMONO,
        self::FIELD_EXCEPTION => self::DB_KEY_EXCEPTION,
        self::FIELD_ERROR => self::DB_KEY_ERROR,
        self::FIELD_LAST_POST_CREATED_TIME => self::DB_KEY_LAST_POST_CREATED_TIME,
        self::FIELD_WERE_HERE_COUNT => self::DB_KEY_WERE_HERE_COUNT,
        self::FIELD_TALKING_ABOUT_COUNT => self::DB_KEY_TALKING_ABOUT_COUNT,
        self::FIELD_FAN_COUNT => self::DB_KEY_FAN_COUNT,
        self::FIELD_OVERALL_STAR_RATING => self::DB_KEY_OVERALL_STAR_RATING,
        self::FIELD_RATING_COUNT => self::DB_KEY_RATING_COUNT,
    );

    /**
     * @param array $fbArray
     * @return FacebookPage
     */
    public static function constructByFbArray($fbArray){
        $page = new FacebookPage();
        $page->setFbResponse($fbArray);
        return $page;
    }

    /**
     * @param array $mongoArray
     * @return FacebookPage
     */
    public static function constructByMongoArray($mongoArray){
        $page = new FacebookPage($mongoArray);
        return $page;
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

    public function isDiffMetricFrom(FacebookPage $page)
    {
        if ($this->getWereHereCount() != $page->getWereHereCount())
        {
            return true;
        }
        if ($this->getTalkingAboutCount() != $page->getTalkingAboutCount())
        {
            return true;
        }
        if ($this->getFanCount() != $page->getFanCount())
        {
            return true;
        }
        if ($this->getOverallStarRating() != $page->getOverallStarRating())
        {
            return true;
        }
        if ($this->getRatingCount() != $page->getRatingCount())
        {
            return true;
        }
        return false;
    }

    public function getCollectionName()
    {
        return self::TARGET_COLLECTION;
    }

    /**
     * @return array|null
     */
    public function getMnemono()
    {
        $this->mnemono;
        return $this->mnemono;
    }

    /**
     * @param array $mnemono
     */
    public function setMnemono($mnemono)
    {
        $this->mnemono = $mnemono;
    }

    /**
     * @return \MongoDB\BSON\ObjectID|null
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param \MongoDB\BSON\ObjectID $id
     */
    public function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * @return string|null
     */
    public function getFbID()
    {
        return $this->fbID;
    }

    /**
     * @param string $fbID
     */
    public function setFbID($fbID)
    {
        $this->fbID = $fbID;
    }

    /**
     * @return array
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
        if (isset($fbResponse['id']))
        {
            $this->setFbID($fbResponse['id']);
            unset($fbResponse['id']);
        }
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
     * @return int
     */
    public function getFeedCount()
    {
        return $this->feedCount;
    }

    /**
     * @param int $feedCount
     * @return self
     */
    public function setFeedCount($feedCount)
    {
        $this->feedCount = $feedCount;
        return $this;
    }

    /**
     * @return int
     */
    public function getAccumulateLike()
    {
        return $this->accumulateLike;
    }

    /**
     * @param int $accumulateLike
     * @return self
     */
    public function setAccumulateLike($accumulateLike)
    {
        $this->accumulateLike = $accumulateLike;
        return $this;
    }

    /**
     * @return int
     */
    public function getAccumulateComment()
    {
        return $this->accumulateComment;
    }

    /**
     * @param int $accumulateComment
     * @return self
     */
    public function setAccumulateComment($accumulateComment)
    {
        $this->accumulateComment = $accumulateComment;
        return $this;
    }

    /**
     * @return double
     */
    public function getFeedAverageLike()
    {
        return $this->getAccumulateLike() / $this->getFeedCount();
    }

    /**
     * @return double
     */
    public function getFeedAverageComment()
    {
        return $this->getAccumulateComment() / $this->getFeedCount();
    }


    /**
     * @return string|null
     */
    public function getMnemonoCategory()
    {
        $mnemono = $this->getMnemono();
        if (isset($mnemono["category"]))
        {
            return $mnemono["category"];
        } else
        {
            return null;
        }
    }

    /**
     * @return string
     */
    public function getShortLink()
    {
        return "https://www.facebook.com/" . $this->getFbID();
    }

    /**
     * @return bool
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @param bool $exception
     */
    public function setException($exception)
    {
        $this->exception = $exception;
    }

    /**
     * @return array
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param array $error
     */
    public function setError($error)
    {
        $this->error = $error;
    }

    /**
     * @return string
     */
    public function getLastPostCreatedTime()
    {
        return $this->lastPostCreatedTime;
    }

    /**
     * @param string $lastPostCreatedTime
     */
    public function setLastPostCreatedTime($lastPostCreatedTime)
    {
        $this->lastPostCreatedTime = $lastPostCreatedTime;
    }

    /**
     * @return int
     */
    public function getWereHereCount()
    {
        return ($this->wereHereCount != null ? $this->wereHereCount : 0 );
    }

    /**
     * @param int $wereHereCount
     */
    public function setWereHereCount($wereHereCount)
    {
        $this->wereHereCount = intval($wereHereCount);
    }

    /**
     * @return int
     */
    public function getTalkingAboutCount()
    {
        return ($this->talkingAboutCount != null ? $this->talkingAboutCount : 0);
    }

    /**
     * @param int $talkingAboutCount
     */
    public function setTalkingAboutCount($talkingAboutCount)
    {
        $this->talkingAboutCount = intval($talkingAboutCount);
    }

    /**
     * @return int
     */
    public function getFanCount()
    {
        return ($this->fanCount != null ? $this->fanCount : 0);
    }

    /**
     * @param int $fanCount
     */
    public function setFanCount($fanCount)
    {
        $this->fanCount = intval($fanCount);
    }

    /**
     * @return float
     */
    public function getOverallStarRating()
    {
        return $this->overallStarRating;
    }

    /**
     * @param float $overallStarRating
     */
    public function setOverallStarRating($overallStarRating)
    {
        $this->overallStarRating = floatval($overallStarRating);
    }

    /**
     * @return int
     */
    public function getRatingCount()
    {
        return $this->ratingCount;
    }

    /**
     * @param int $ratingCount
     */
    public function setRatingCount($ratingCount)
    {
        $this->ratingCount = intval($ratingCount);
    }

}
