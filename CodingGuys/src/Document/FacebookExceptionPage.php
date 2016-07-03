<?php

/**
 * User: kit
 * Date: 24/04/2016
 * Time: 3:32 PM
 */

namespace CodingGuys\Document;

use CodingGuys\Exception\KeyNotExistsException;

class FacebookExceptionPage extends FacebookPage
{
    private $exceptionTime;
    const TARGET_COLLECTION = "FacebookExceptionPage";
    const KEY_EXCEPION_TIME = 'exception_time';
    const FIELD_EXCEPTION_TIME = 'exceptionTime';

    private static $dbMapping = array(
        FacebookExceptionPage::FIELD_EXCEPTION_TIME => FacebookExceptionPage::KEY_EXCEPION_TIME,
    );

    protected function init()
    {
        parent::init();
        foreach (FacebookExceptionPage::$dbMapping as $field => $dbCol)
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
        $arr = parent::toArray();
        foreach (FacebookExceptionPage::$dbMapping as $field => $dbCol)
        {
            $arr[$dbCol] = $this->{"get" . ucfirst($field)}();
        }
        $arr = array_filter($arr, array($this, 'filterNonNullValue'));
        return $arr;
    }

    public function getCollectionName()
    {
        return FacebookExceptionPage::TARGET_COLLECTION;
    }

    /**
     * @return \MongoDB\BSON\UTCDateTime
     */
    public function getExceptionTime()
    {
        return $this->exceptionTime;
    }

    /**
     * @param \MongoDB\BSON\UTCDateTime $exceptionTime
     */
    public function setExceptionTime(\MongoDB\BSON\UTCDateTime $exceptionTime = null)
    {
        $this->exceptionTime = $exceptionTime;
    }
}