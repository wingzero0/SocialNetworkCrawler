<?php
/**
 * User: kit
 * Date: 19-May-16
 * Time: 8:32 AM
 */

namespace CodingGuys\Document;


use CodingGuys\Exception\KeyNotExistsException;
use CodingGuys\Utility\DateUtility;
use MongoDB\BSON\ObjectID;

class FacebookPageTimestamp extends BaseObj
{
    private $_id;
    private $wereHereCount;
    private $talkingAboutCount;
    private $fanCount;
    private $fbPage;
    private $updateTime;
    private $batchTime;

    const TARGET_COLLECTION = "FacebookPageTimestamp";

    const DB_FIELD_ID = "_id";
    const DB_FIELD_WERE_HERE_COUNT = "were_here_count";
    const DB_FIELD_TALKING_ABOUT_COUNT = "talking_about_count";
    const DB_FIELD_FAN_COUNT = "fan_count";
    const DB_FIELD_FB_PAGE = "fbPage";
    const DB_FIELD_UPDATE_TIME = "updateTime";
    const DB_FIELD_BATCH_TIME = "batchTime";

    const FIELD_ID = "id";
    const FIELD_WERE_HERE_COUNT = "wereHereCount";
    const FIELD_TALKING_ABOUT_COUNT = "talkingAboutCount";
    const FIELD_FAN_COUNT = "fanCount";
    const FIELD_FB_PAGE = "fbPage";
    const FIELD_UPDATE_TIME = "updateTime";
    const FIELD_BATCH_TIME = "batchTime";

    private static $dbMapping = array(
        FacebookPageTimestamp::FIELD_ID => FacebookPageTimestamp::DB_FIELD_ID,
        FacebookPageTimestamp::FIELD_WERE_HERE_COUNT => FacebookPageTimestamp::DB_FIELD_WERE_HERE_COUNT,
        FacebookPageTimestamp::FIELD_TALKING_ABOUT_COUNT => FacebookPageTimestamp::DB_FIELD_TALKING_ABOUT_COUNT,
        FacebookPageTimestamp::FIELD_FAN_COUNT => FacebookPageTimestamp::DB_FIELD_FAN_COUNT,
        FacebookPageTimestamp::FIELD_FB_PAGE => FacebookPageTimestamp::DB_FIELD_FB_PAGE,
        FacebookPageTimestamp::FIELD_UPDATE_TIME => FacebookPageTimestamp::DB_FIELD_UPDATE_TIME,
        FacebookPageTimestamp::FIELD_BATCH_TIME => FacebookPageTimestamp::DB_FIELD_BATCH_TIME,
    );

    protected function init()
    {
        foreach (FacebookPageTimestamp::$dbMapping as $field => $dbCol)
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
    }

    public function toArray()
    {
        $arr = $this->getMongoRawData();
        foreach (FacebookPageTimestamp::$dbMapping as $field => $dbCol)
        {
            $arr[$dbCol] = $this->{"get" . ucfirst($field)}();
        }
        $arr = array_filter($arr, array($this, 'filterNonNullValue'));
        return $arr;
    }

    /**
     * @return ObjectID
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param ObjectID $id
     */
    public function setId(ObjectID $id = null)
    {
        $this->_id = $id;
    }

    /**
     * @return int
     */
    public function getWereHereCount()
    {
        return $this->wereHereCount;
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
        return $this->talkingAboutCount;
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
        return $this->fanCount;
    }

    /**
     * @param int $fanCount
     */
    public function setFanCount($fanCount)
    {
        $this->fanCount = intval($fanCount);
    }

    /**
     * @return \MongoDBRef|array|null
     */
    public function getFbPage()
    {
        return $this->fbPage;
    }

    /**
     * @param \MongoDBRef|array $fbPage
     */
    public function setFbPage($fbPage)
    {
        $this->fbPage = $fbPage;
    }

    /**
     * @return \MongoDB\BSON\UTCDateTime|null
     */
    public function getUpdateTime()
    {
        return $this->updateTime;
    }

    /**
     * @param \MongoDB\BSON\UTCDateTime $updateTime
     */
    public function setUpdateTime(\MongoDB\BSON\UTCDateTime $updateTime = null)
    {
        $this->updateTime = $updateTime;
    }

    /**
     * @return \MongoDB\BSON\UTCDateTime|null
     */
    public function getBatchTime()
    {
        return $this->batchTime;
    }

    /**
     * @param \MongoDB\BSON\UTCDateTime $batchTime
     */
    public function setBatchTime(\MongoDB\BSON\UTCDateTime $batchTime = null)
    {
        $this->batchTime = $batchTime;
    }

    public function getBatchTimeInISO()
    {
        $batchTime = $this->getBatchTime();
        if ($batchTime instanceof \MongoDB\BSON\UTCDateTime)
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