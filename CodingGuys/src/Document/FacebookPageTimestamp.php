<?php
/**
 * User: kit
 * Date: 19-May-16
 * Time: 8:32 AM
 */

namespace CodingGuys\Document;


use CodingGuys\Exception\KeyNotExistsException;
use CodingGuys\Utility\DateUtility;

class FacebookPageTimestamp extends BaseObj
{
    private $_id;
    private $were_here_count;
    private $talking_about_count;
    private $likes;
    private $fbPage;
    private $updateTime;
    private $batchTime;
    const TARGET_COLLECTION = "FacebookPageTimestamp";

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

        try
        {
            $this->setWereHereCount($this->getFromRaw("were_here_count"));
        } catch (KeyNotExistsException $e)
        {
            $this->setWereHereCount(0);
        }

        try
        {
            $this->setTalkingAboutCount($this->getFromRaw("talking_about_count"));
        } catch (KeyNotExistsException $e)
        {
            $this->setTalkingAboutCount(0);
        }

        try
        {
            $this->setLikes($this->getFromRaw("likes"));
        } catch (KeyNotExistsException $e)
        {
            $this->setLikes(0);
        }

        try
        {
            $this->setFbPage($this->getFromRaw("fbPage"));
        } catch (KeyNotExistsException $e)
        {
            $this->setFbPage(null);
        }

        try
        {
            $this->setUpdateTime($this->getFromRaw("updateTime"));
        } catch (KeyNotExistsException $e)
        {
            $this->setUpdateTime(null);
        }

        try
        {
            $this->setBatchTime($this->getFromRaw("batchTime"));
        } catch (KeyNotExistsException $e)
        {
            $this->setBatchTime(null);
        }
    }

    public function toArray()
    {
        // TODO: Implement toArray() method.
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
    public function setId(\MongoId $id = null)
    {
        $this->_id = $id;
    }

    /**
     * @return int
     */
    public function getWereHereCount()
    {
        return $this->were_here_count;
    }

    /**
     * @param int $were_here_count
     */
    public function setWereHereCount($were_here_count)
    {
        $this->were_here_count = $were_here_count;
    }

    /**
     * @return int
     */
    public function getTalkingAboutCount()
    {
        return $this->talking_about_count;
    }

    /**
     * @param int $talking_about_count
     */
    public function setTalkingAboutCount($talking_about_count)
    {
        $this->talking_about_count = $talking_about_count;
    }

    /**
     * @return int
     */
    public function getLikes()
    {
        return $this->likes;
    }

    /**
     * @param int $likes
     */
    public function setLikes($likes)
    {
        $this->likes = $likes;
    }

    /**
     * @return \MongoDBRef|null
     */
    public function getFbPage()
    {
        return $this->fbPage;
    }

    /**
     * @param \MongoDBRef $fbPage
     */
    public function setFbPage($fbPage)
    {
        $this->fbPage = $fbPage;
    }

    /**
     * @return \MongoDate|null
     */
    public function getUpdateTime()
    {
        return $this->updateTime;
    }

    /**
     * @param \MongoDate $updateTime
     */
    public function setUpdateTime(\MongoDate $updateTime = null)
    {
        $this->updateTime = $updateTime;
    }

    /**
     * @return \MongoDate|null
     */
    public function getBatchTime()
    {
        return $this->batchTime;
    }

    /**
     * @param \MongoDate $batchTime
     */
    public function setBatchTime(\MongoDate $batchTime = null)
    {
        $this->batchTime = $batchTime;
    }

    public function getBatchTimeInISO()
    {
        $batchTime = $this->getBatchTime();
        if ($batchTime instanceof \MongoDate)
        {
            $isoStr = DateUtility::convertMongoDateToISODate($batchTime);
            return $isoStr;
        }
        return "";
    }

    public function getCollectionName()
    {
        return FacebookPageTimestamp::TARGET_COLLECTION;
    }
}