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

    protected function init()
    {
        try
        {
            $id = $this->getFromRaw("_id");
            $this->setId($id);
        } catch (KeyNotExistsException $e)
        {
            $this->setId(null);
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
     * @return \MongoId
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param \MongoId $id
     * @return self
     */
    public function setId(\MongoId $id)
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
}