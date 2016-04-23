<?php
/**
 * User: kit
 * Date: 23/04/2016
 * Time: 5:15 PM
 */

namespace CodingGuys;

use CodingGuys\FbRepo\FbFeedRepo;
use CodingGuys\FbRepo\FbPageRepo;

class CGPageStat
{
    private $pageRepo;
    private $feedRepo;
    public function pageLastUpdateTime(\MongoId $pageMongoId){
        $pageRepo = $this->getPageRepo();
        $pageRaw = $pageRepo->findOneById($pageMongoId);
        if (empty($pageRaw)){
            return null;
        }
        $feedRepo = $this->getFeedRepo();
        $feedRaw = $feedRepo->findLatestOneByPageId($pageRaw["_id"]);
        echo $feedRaw["created_time"];
        $createdTime = \DateTime::createFromFormat(\DateTime::ISO8601, $feedRaw["created_time"]);
        var_dump($createdTime);
    }

    /**
     * @return FbPageRepo
     */
    private function getPageRepo(){
        if ($this->pageRepo == null){
            $this->pageRepo = new FbPageRepo();
        }
        return $this->pageRepo;
    }
    
    private function getFeedRepo(){
        if ($this->feedRepo == null){
            $this->feedRepo = new FbFeedRepo();
        }
        return $this->feedRepo;
    }
}