<?php
/**
 * User: kit
 * Date: 23/04/2016
 * Time: 6:15 PM
 */

namespace CodingGuys\FbRepo;


class FbFeedRepo extends FbRepo
{
    /**
     * @param \MongoId $pageMongoId
     * @return array
     */
    public function findLatestOneByPageId(\MongoId $pageMongoId)
    {
        $query = array(
            "fbPage.\$id" => $pageMongoId
        );
        $orderQ = array(
            "created_time" => -1
        );
        $cursor = $this->getFeedCollection()
            ->find($query)
            ->sort($orderQ)
            ->limit(1);
        if ($cursor->hasNext())
        {
            return $cursor->getNext();
        } else
        {
            return null;
        }
    }

    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return \MongoCursor
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
        $cursor = $this->getFeedCollection()
            ->find(array("fbID" => $fbId))
            ->limit(1);

        if ($cursor->hasNext())
        {
            return $cursor->getNext();
        } else
        {
            return null;
        }
    }

    /**
     * @return \MongoCollection
     */
    private function getFeedCollection()
    {
        return $this->getFbDM()->getFeedCollection();
    }

}