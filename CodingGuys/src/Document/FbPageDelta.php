<?php
/**
 * User: kit
 * Date: 22/05/2016
 * Time: 2:45 PM
 */

namespace CodingGuys\Document;

use CodingGuys\Exception\KeyNotExistsException;

class FbPageDelta extends BaseObj
{
    private $deltaLike;
    private $deltaWereHereCount;
    private $deltaTalkingAboutCount;
    private $dateStr;
    private $_id;
    private $fbPageRef;

    const TARGET_COLLECTION = "FacebookPageDelta";

    protected function init()
    {
        try
        {
            $this->setId($this->getFromRaw("_id"));
        } catch (KeyNotExistsException $e)
        {
            $this->setId(null);
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
            $this->setDeltaWereHereCount($this->getFromRaw("deltaWereHereCount"));
        } catch (KeyNotExistsException $e)
        {
            $this->setDeltaWereHereCount(0);
        }

        try
        {
            $this->setDeltaTalkingAboutCount($this->getFromRaw("deltaTalkingAboutCount"));
        } catch (KeyNotExistsException $e)
        {
            $this->setDeltaTalkingAboutCount(0);
        }

        try
        {
            $this->setFbPageRef($this->getFromRaw("fbPage"));
        } catch (KeyNotExistsException $e)
        {
            $this->setFbPageRef(null);
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
        $a["deltaWereHereCount"] = $this->getDeltaWereHereCount();
        $a["deltaTalkingAboutCount"] = $this->getDeltaTalkingAboutCount();
        $a["fbPage"] = $this->getFbPageRef();
        return $a;
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
    public function setId($id)
    {
        $this->_id = $id;
        return $this;
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
    public function getDeltaWereHereCount()
    {
        return $this->deltaWereHereCount;
    }

    /**
     * @param int $deltaWereHereCount
     * @return self
     */
    public function setDeltaWereHereCount($deltaWereHereCount)
    {
        $this->deltaWereHereCount = $deltaWereHereCount;
        return $this;
    }

    /**
     * @return int
     */
    public function getDeltaTalkingAboutCount()
    {
        return $this->deltaTalkingAboutCount;
    }

    /**
     * @param int $deltaTalkingAboutCount
     * @return self
     */
    public function setDeltaTalkingAboutCount($deltaTalkingAboutCount)
    {
        $this->deltaTalkingAboutCount = $deltaTalkingAboutCount;
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
    public function getFbPageRef()
    {
        return $this->fbPageRef;
    }

    /**
     * @param array $fbPageRef
     * @return self
     */
    public function setFbPageRef($fbPageRef)
    {
        $this->fbPageRef = $fbPageRef;
        return $this;
    }

    public function getCollectionName()
    {
        return FbPageDelta::TARGET_COLLECTION;
    }
}