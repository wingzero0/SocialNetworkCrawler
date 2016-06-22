<?php

/**
 * User: kit
 * Date: 22/05/2016
 * Time: 3:06 PM
 */

namespace CodingGuys\Utility;

class DateUtility
{
    /**
     * @param \MongoDB\BSON\UTCDateTime $mongoDate
     * @return string
     */
    static function convertMongoDateToISODate(\MongoDB\BSON\UTCDateTime $mongoDate)
    {
        $dateTime = DateUtility::convertMongoDateToDateTime($mongoDate);
        return $dateTime->format(\DateTime::ISO8601);
    }

    /**
     * @param \MongoDB\BSON\UTCDateTime $mongoDate
     * @return \DateTime
     */
    static function convertMongoDateToDateTime(\MongoDB\BSON\UTCDateTime $mongoDate)
    {
        return $mongoDate->toDateTime();
    }

    /**
     * @param \DateTime $dateTime
     * @return \MongoDB\BSON\UTCDateTime
     */
    static function convertDateTimeToMongoDate(\DateTime $dateTime)
    {
        return new \MongoDB\BSON\UTCDateTime($dateTime->getTimestamp() * 1000);
    }
}