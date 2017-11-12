<?php
/**
 * User: kitlei
 * Date: 11-May-16
 * Time: 8:40 AM
 */

namespace CodingGuys\Document;

use CodingGuys\Exception\KeyNotExistsException;

class FbFeedDelta extends BaseObj
{
    private $deltaLike;
    private $deltaComment;
    private $dateStr;
    private $_id;
    private $fbFeedRef;

    const TARGET_COLLECTION = "FacebookFeedDelta";

    protected function init()
    {
        try
        {
            $id = $this->getFromRaw("_id");
            $this->setId($id);
        } catch (KeyNotExistsException $e)
        {
            throw new \UnexpectedValueException("_id should not be null");
        }

        try
        {
            $this->setDateStr($this->getFromRaw("dateStr"));
        } catch (KeyNotExistsException $e)
        {
            $this->setDateStr(null);
        }

        try
        {
            $this->setDeltaLike($this->getFromRaw("deltaLike"));
        } catch (KeyNotExistsException $e)
        {
            $this->setDeltaLike(0);
        }

        try
        {
            $this->setDeltaComment($this->getFromRaw("deltaComment"));
        } catch (KeyNotExistsException $e)
        {
            $this->setDeltaComment(0);
        }

        try
        {
            $this->setFbFeedRef($this->getFromRaw("fbFeed"));
        } catch (KeyNotExistsException $e)
        {
            $this->setFbFeedRef(null);
        }
    }

    public function toArray()
    {
        $a = array();
        if ($this->getId() != null)
        {
            $a["_id"] = $this->getId();
        }
        $a["dateStr"] = $this->getDateStr();
        $a["deltaLike"] = $this->getDeltaLike();
        $a["deltaComment"] = $this->getDeltaComment();
        $a["fbFeed"] = $this->getFbFeedRef();
        return $a;
    }

    /**
     * @return int
     */
    public function getDeltaLike()
    {
        return $this->deltaLike;
    }

    /**
     * @param int $deltaLike
     * @return self
     */
    public function setDeltaLike($deltaLike)
    {
        $this->deltaLike = $deltaLike;
        return $this;
    }

    /**
     * @return int
     */
    public function getDeltaComment()
    {
        return $this->deltaComment;
    }

    /**
     * @param int $deltaComment
     * @return self
     */
    public function setDeltaComment($deltaComment)
    {
        $this->deltaComment = $deltaComment;
        return $this;
    }

    /**
     * @return \MongoDB\BSON\ObjectID
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param \MongoDB\BSON\ObjectID $id
     * @return self
     */
    public function setId(\MongoDB\BSON\ObjectID $id)
    {
        $this->_id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getDateStr()
    {
        return $this->dateStr;
    }

    /**
     * @param string $dateStr
     * @return self
     */
    public function setDateStr($dateStr)
    {
        $this->dateStr = $dateStr;
        return $this;
    }

    /**
     * @return array
     */
    public function getFbFeedRef()
    {
        return $this->fbFeedRef;
    }

    /**
     * @param array $fbFeedRef
     * @return self
     */
    public function setFbFeedRef($fbFeedRef)
    {
        $this->fbFeedRef = $fbFeedRef;
        return $this;
    }

    public function getCollectionName()
    {
        return FbFeedDelta::TARGET_COLLECTION;
    }
}