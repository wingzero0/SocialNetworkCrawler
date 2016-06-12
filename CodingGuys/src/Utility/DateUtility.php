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
     * @param \MongoDate $mongoDate
     * @return string
     */
    static function convertMongoDateToISODate(\MongoDate $mongoDate)
    {
        $dateTime = DateUtility::convertMongoDateToDateTime($mongoDate);
        return $dateTime->format(\DateTime::ISO8601);
    }

    /**
     * @param \MongoDate $mongoDate
     * @return \DateTime
     */
    static function convertMongoDateToDateTime(\MongoDate $mongoDate)
    {
        $dateTime = new \DateTime();
        $dateTime->setTimestamp($mongoDate->sec);
        return $dateTime;
    }
}