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
    private $fbResponse;
    private $feedCount;
    private $accumulateComment;
    private $accumulateLike;

    const TARGET_COLLECTION = "FacebookPage";

    const KEY_ID = "_id";
    const KEY_FB_ID = "fbID";
    const KEY_MNEMONO = "mnemono";

    const FIELD_ID = "id";
    const FIELD_FB_ID = "fbId";
    const FIELD_MNEMONO = "mnemono";

    private static $dbMapping = array(
        FacebookPage::FIELD_ID => FacebookPage::KEY_ID,
        FacebookPage::FIELD_FB_ID => FacebookPage::KEY_FB_ID,
        FacebookPage::FIELD_MNEMONO => FacebookPage::KEY_MNEMONO,
    );

    protected function init()
    {
        foreach (FacebookPage::$dbMapping as $field => $dbCol)
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
        foreach (FacebookPage::$dbMapping as $field => $dbCol)
        {
            $arr[$dbCol] = $this->{"get" . ucfirst($field)}();
        }
        $arr = array_filter($arr, array($this, 'filterNonNullValue'));
        return $arr;
    }

    public function getCollectionName()
    {
        return FacebookPage::TARGET_COLLECTION;
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
     * @return \MongoId|null
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param \MongoId $id
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
        $this->fbResponse = $fbResponse;
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
}