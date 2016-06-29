<?php
/**
 * User: kit
 * Date: 23/04/2016
 * Time: 5:15 PM
 */

namespace CodingGuys;

use CodingGuys\Document\FacebookPage;
use CodingGuys\FbRepo\FbFeedRepo;
use CodingGuys\FbRepo\FbPageRepo;

class CGPageStat
{
    private $pageRepo;
    private $feedRepo;

    public function findAllPageLastUpdateTime()
    {
        $pageRepo = $this->getPageRepo();
        $cursor = $pageRepo->findAllWorkingPage();
        $ret = array();
        $counter = 0;
        foreach ($cursor as $pageRaw)
        {
            $fbPage = new FacebookPage($pageRaw);
            $mnemono = $fbPage->getMnemono();
            $timeStr = $this->getPageLastUpdateTime($pageRaw);
            if ($timeStr == null)
            {
                $timeStr = " " . $counter;
                $counter++;
            }
            $ret[$timeStr] = array("mnemono" => $mnemono, "fbID" => $fbPage->getFbID());
        }
        ksort($ret);
        return $ret;
    }

    public function pageLastUpdateTime(\MongoDB\BSON\ObjectID $pageMongoId)
    {
        $pageRepo = $this->getPageRepo();
        $pageRaw = $pageRepo->findOneById($pageMongoId);
        if (empty($pageRaw))
        {
            return null;
        }
        return $this->getPageLastUpdateTime($pageRaw);
        //$createdTime = \DateTime::createFromFormat(\DateTime::ISO8601, $feedRaw["created_time"]);
        //var_dump($createdTime);
    }

    private function getPageLastUpdateTime($pageRaw)
    {
        $feedRepo = $this->getFeedRepo();
        $feedRaw = $feedRepo->findLatestOneByPageId($pageRaw["_id"]);
        if (empty($feedRaw))
        {
            return null;
        }
        return $feedRaw["created_time"];
    }

    /**
     * @return FbPageRepo
     */
    private function getPageRepo()
    {
        if ($this->pageRepo == null)
        {
            $this->pageRepo = new FbPageRepo();
        }
        return $this->pageRepo;
    }

    private function getFeedRepo()
    {
        if ($this->feedRepo == null)
        {
            $this->feedRepo = new FbFeedRepo();
        }
        return $this->feedRepo;
    }
}