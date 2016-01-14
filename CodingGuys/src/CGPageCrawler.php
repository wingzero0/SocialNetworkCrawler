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
    private $fbId;
    public function __construct($fbPageId){
        $this->fbId = $fbPageId;
        parent::__construct();
    }

    /**
     * @return array|null
     */
    public function crawl(){
        $request = new FacebookRequest($this->getFbSession(), 'GET', '/'. $this->fbId );
        $headerMsg = "get error while crawling page:" . $this->fbId;
        $response = $this->tryRequest($request, $headerMsg);
        if ($response == null){
            return null;
        }
        $pageMainContent = $response->getResponse();

        $request = new FacebookRequest($this->getFbSession(), 'GET', '/'. $this->fbId . '/picture?type=large&redirect=false');
        $pictureResponse = $this->tryRequest($request, $headerMsg);
        if ($pictureResponse == null){
            return null;
        }
        $pageProfilePicture = $pictureResponse->getResponse();

        $pageMainContent->fbID = $pageMainContent->id;
        unset($pageMainContent->id);
        $pageMainContent->profilePicture = $pageProfilePicture->data;

        return $pageMainContent;
    }

}