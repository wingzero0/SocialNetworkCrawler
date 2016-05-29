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
    private $_id;
    private $fbId;
    private $from;
    private $message;
    private $picture;
    private $link;
    private $type;
    private $statusType;
    private $createdTime;
    private $updatedTime;
    private $fbResponse;

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
            $fbId = $this->getFromRaw("fbID");
            $this->setFbId($fbId);
        } catch (KeyNotExistsException $e)
        {
            $this->setFbId(null);
        }
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
     * @return array|null
     * @throws \Exception
     */
    public function toArray()
    {
        $response = $this->getFbResponse();
        if (!empty($response)){
            return $response;
        }
        throw new \Exception("not ready to write back");
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
    }
}