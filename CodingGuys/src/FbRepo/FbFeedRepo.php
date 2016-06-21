<?php
/**
 * User: kit
 * Date: 23/04/2016
 * Time: 6:15 PM
 */

namespace CodingGuys\FbRepo;

use MongoDB\Collection as MongoDBCollection;

class FbFeedRepo extends FbRepo
{
    /**
     * @param \MongoDB\BSON\ObjectID $pageMongoId
     * @return array
     */
    public function findLatestOneByPageId(\MongoDB\BSON\ObjectID $pageMongoId)
    {
        $query = array(
            "fbPage.\$id" => $pageMongoId
        );
        $options = array(
            "sort" => array(
                "created_time" => -1
            )
        );
        $arr = $this->getFeedCollection()
            ->findOne($query, $options);
        return $arr;
    }

    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return \MongoDB\Driver\Cursor
     */
    public function findFeedByCreatedTime(\DateTime $startDate = null, \DateTime $endDate = null)
    {
        $dateRange = array();
        if ($startDate != null)
        {
            $tmpDate = clone $startDate;
            $tmpDate->setTimezone(new \DateTimeZone("GMT+0"));
            $dateRange["\$gte"] = $tmpDate->format(\DateTime::ISO8601);
        }
        if ($endDate != null)
        {
            $tmpDate = clone $endDate;
            $tmpDate->setTimezone(new \DateTimeZone("GMT+0"));
            $dateRange["\$lte"] = $tmpDate->format(\DateTime::ISO8601);
        }

        if (empty($dateRange))
        {
            return $this->getFeedCollection()->find();
        }
        return $this->getFeedCollection()->find(array("created_time" => $dateRange));
    }

    /**
     * @param string $fbId
     * @return array|null
     */
    public function findOneByFbId($fbId)
    {
        return $this->getFeedCollection()
            ->findOne(array("fbID" => $fbId));
    }

    /**
     * @return MongoDBCollection
     */
    private function getFeedCollection()
    {
        return $this->getFbDM()->getFeedCollection();
    }

}