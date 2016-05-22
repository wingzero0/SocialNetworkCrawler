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
        $a["date_str"] = $this->getDateStr();
        $a["delta_like"] = $this->getDeltaLike();
        $a["dalta_were_here_count"] = $this->getDeltaWereHereCount();
        $a["delta_talking_about_count"] = $this->getDeltaTalkingAboutCount();
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
     */
    public function setId($id)
    {
        $this->_id = $id;
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
     */
    public function setDeltaLike($deltaLike)
    {
        $this->deltaLike = $deltaLike;
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
     */
    public function setDeltaWereHereCount($deltaWereHereCount)
    {
        $this->deltaWereHereCount = $deltaWereHereCount;
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
     */
    public function setDeltaTalkingAboutCount($deltaTalkingAboutCount)
    {
        $this->deltaTalkingAboutCount = $deltaTalkingAboutCount;
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
     */
    public function setDateStr($dateStr)
    {
        $this->dateStr = $dateStr;
    }
}