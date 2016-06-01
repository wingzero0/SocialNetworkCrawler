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
    private $likes;
    private $comments;
    private $attachments;
    private $fbPage;
    private $fbResponse;

    const TARGET_COLLECTION = "FacebookFeed";

    const KEY_ID = "_id";
    const KEY_FB_ID = "fbID";
    const KEY_FROM = "from";
    const KEY_MESSAGE = "message";
    const KEY_PICTURE = "picture";
    const KEY_LINK = "link";
    const KEY_TYPE = "type";
    const KEY_STATUS_TYPE = "status_type";
    const KEY_CREATED_TIME = "created_time";
    const KEY_UPDATED_TIME = "updated_time";
    const KEY_SHARES = "shares";
    const KEY_IS_EXPIRED = "is_expired";
    const KEY_LIKES = "likes";
    const KEY_COMMENTS = "comments";
    const KEY_ATTACHMENTS = "attachments";
    const KEY_FB_PAGE = "fbPage";

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
    const FIELD_LIKES = "likes";
    const FIELD_COMMENTS = "comments";
    const FIELD_ATTACHMENTS = "attachments";
    const FIELD_FB_PAGE = "fbPage";

    private static $dbMapping = array(
        FacebookFeed::FIELD_ID => FacebookFeed::KEY_ID,
        FacebookFeed::FIELD_FB_ID => FacebookFeed::KEY_FB_ID,
        FacebookFeed::FIELD_FROM => FacebookFeed::KEY_FROM,
        FacebookFeed::FIELD_MESSAGE => FacebookFeed::KEY_MESSAGE,
        FacebookFeed::FIELD_PICTURE => FacebookFeed::KEY_PICTURE,
        FacebookFeed::FIELD_LINK => FacebookFeed::KEY_LINK,
        FacebookFeed::FIELD_TYPE => FacebookFeed::KEY_TYPE,
        FacebookFeed::FIELD_STATUS_TYPE => FacebookFeed::KEY_STATUS_TYPE,
        FacebookFeed::FIELD_CREATED_TIME => FacebookFeed::KEY_CREATED_TIME,
        FacebookFeed::FIELD_UPDATED_TIME => FacebookFeed::KEY_UPDATED_TIME,
        FacebookFeed::FIELD_SHARES => FacebookFeed::KEY_SHARES,
        FacebookFeed::FIELD_IS_EXPIRED => FacebookFeed::KEY_IS_EXPIRED,
        FacebookFeed::FIELD_LIKES => FacebookFeed::KEY_LIKES,
        FacebookFeed::FIELD_COMMENTS => FacebookFeed::KEY_COMMENTS,
        FacebookFeed::FIELD_ATTACHMENTS => FacebookFeed::KEY_ATTACHMENTS,
        FacebookFeed::FIELD_FB_PAGE => FacebookFeed::KEY_FB_PAGE,
    );

    protected function init()
    {
        foreach (FacebookFeed::$dbMapping as $field => $dbCol){
            try{
                $val = $this->getFromRaw($dbCol);
                $this->{"set" . ucfirst($field)}($val);
            }catch (KeyNotExistsException $e){
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
        $arr = $this->getFbResponse();
        foreach (FacebookFeed::$dbMapping as $field => $dbCol){
            $arr[$dbCol] = $this->{"get" . ucfirst($field)}();
        }
        return $arr;
    }

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
    public function setId(\MongoId $id = null)
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
    public function getLikes()
    {
        return $this->likes;
    }

    /**
     * @param array $likes
     */
    public function setLikes($likes)
    {
        $this->likes = $likes;
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
     * @return \MongoDBRef
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

    public function getCollectionName()
    {
        return FacebookFeed::TARGET_COLLECTION;
    }
}