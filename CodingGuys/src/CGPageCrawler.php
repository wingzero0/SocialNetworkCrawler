<?php
/**
 * User: kit
 * Date: 13/1/2016
 * Time: 11:43
 */

namespace CodingGuys;

use CodingGuys\MongoFb\CGMongoFb;
use Facebook\FacebookRequest;
use Facebook\FacebookResponse;
use Facebook\FacebookRequestException;
use Facebook\FacebookThrottleException;

class CGPageCrawler extends CGFbCrawler{
    /**
     * @param string $pageFbId
     * @param string $category
     * @param string $city
     * @param string $country
     * @param array $crawlTime
     * @return array|null
     */
    public function crawlNewPage($pageFbId, $category, $city, $country, $crawlTime){
        $request = new FacebookRequest($this->getFbSession(), 'GET', '/'. $pageFbId );
        $headerMsg = "get error while crawling page:" . $pageFbId;
        $response = $this->tryRequest($request, $headerMsg);
        if ($response == null){
            return "fail";
        }
        $pageMainContent = $response->getResponse();
        $pageMainContent->fbID = $pageMainContent->id;
        unset($pageMainContent->id);

        $pageMainContent->mnemono = array(
            "category" => $category,
            "location" => array("city" => $city, "country" => $country),
            "crawlTime" => $crawlTime,
        );

        $this->insert($pageMainContent);

        return "success";
    }

    /**
     * @param \MongoId $id
     * @param string $category
     * @param string $city
     * @param string $country
     * @param array $crawlTime
     */
    public function updateMeta(\MongoId $id, $category, $city, $country, $crawlTime){
        $col = $this->getPageCollection();
        $col->update(array("_id" => $id), array("\$set"=>
            array(
                "mnemono" => array(
                    "category" => $category,
                    "location" => array("city" => $city, "country" => $country),
                    "crawlTime" => $crawlTime,
                )
            )
        ));
    }

    /**
     * @param $fbId
     * @return \MongoId|null
     */
    public function getFbMongoId($fbId){
        $cursor = $this->getPageCollection()->find(array("fbID"=>$fbId));
        if ($cursor->hasNext()){
            $data = $cursor->getNext();
            return $data["_id"];
        }else {
            return null;
        }
    }

    private function insert($data){
        $this->getPageCollection()->insert($data);
    }

    /**
     * @return array|null
     */
    private function crawlProfilePicture($pageFbId){
        $request = new FacebookRequest($this->getFbSession(), 'GET', '/'. $pageFbId . '/picture?type=large&redirect=false');
        $headerMsg = "get error while crawling page profile picture:" . $pageFbId;
        $pictureResponse = $this->tryRequest($request, $headerMsg);
        if ($pictureResponse == null){
            return null;
        }
        $pageProfilePicture = $pictureResponse->getResponse();
        return $pageProfilePicture->data;
    }
}