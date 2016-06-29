<?php
/**
 * User: kit
 * Date: 13/1/2016
 * Time: 11:43
 */

namespace CodingGuys;

use CodingGuys\Document\FacebookPage;
use Facebook\FacebookRequest;
use Facebook\FacebookResponse;
use Facebook\FacebookRequestException;
use Facebook\FacebookThrottleException;


// TODO migrate query to FacebookPageRepo
class CGPageCrawler extends CGFbCrawler
{
    const FAIL = "fail";
    const SUCCESS = "success";

    /**
     * @param string $pageFbId
     * @param string $category
     * @param string $city
     * @param string $country
     * @param array $crawlTime
     * @return array|null
     */
    public function crawlNewPage($pageFbId, $category, $city, $country, $crawlTime)
    {
        $request = new FacebookRequest($this->getFbSession(), 'GET', '/' . $pageFbId);
        $headerMsg = "get error while crawling page:" . $pageFbId;
        $response = $this->tryRequest($request, $headerMsg);
        if ($response == null)
        {
            return CGPageCrawler::FAIL;
        }
        $pageMainContent = json_decode(json_encode($response->getResponse()), true);
        $pageMainContent["fbID"] = $pageMainContent["id"];
        unset($pageMainContent["id"]);

        $page = new FacebookPage();
        $page->setFbResponse($pageMainContent);
        $page->setMnemono(array(
            "category" => $category,
            "location" => array("city" => $city, "country" => $country),
            "crawlTime" => $crawlTime,
        ));

        $this->getFbDM()->writeToDB($page);

        return CGPageCrawler::SUCCESS;
    }

    /**
     * @param \MongoDB\BSON\ObjectID $id
     * @param string $category
     * @param string $city
     * @param string $country
     * @param array $crawlTime
     * @return string
     */
    public function reCrawlData(\MongoDB\BSON\ObjectID $id, $category, $city, $country, $crawlTime)
    {
        $repo = $this->getFbPageRepo();
        $raw = $repo->findOneById($id);
        if ($raw === null)
        {
            throw new \UnexpectedValueException();
        }
        $fbPage = new FacebookPage($raw);

        $request = new FacebookRequest($this->getFbSession(), 'GET', '/' . $fbPage->getFbID());
        $headerMsg = "get error while crawling page:" . $fbPage->getFbID();
        $response = $this->tryRequest($request, $headerMsg);
        if ($response == null)
        {
            return CGPageCrawler::FAIL;
        }
        $pageMainContent = json_decode(json_encode($response->getResponse()), true);
        $pageMainContent["fbID"] = $pageMainContent["id"];
        unset($pageMainContent["id"]);

        $fbPage->setException(false);
        $fbPage->setError(null);
        $fbPage->setFbResponse($pageMainContent);
        $fbPage->setMnemono(array(
            "category" => $category,
            "location" => array("city" => $city, "country" => $country),
            "crawlTime" => $crawlTime,
        ));
        $this->getFbDM()->writeToDB($fbPage);
        return CGPageCrawler::SUCCESS;
    }

    /**
     * @param $fbId
     * @return \MongoDB\BSON\ObjectID|null
     */
    public function getFbMongoId($fbId)
    {
        $page = $this->getDBPageValue($fbId);
        if ($page)
        {
            return $page["_id"];
        } else
        {
            return null;
        }
    }

    /**
     * @param FacebookRequestException $e
     * @param array $page the page record fetch from mongoDB;
     */
    private function handleErrorPage(FacebookRequestException $e, $page)
    {
        //TODO move error handling to FeedCrawler
        echo $e->getRawResponse() . "\n";
        $errorMsg = json_decode($e->getRawResponse());
        $code = $errorMsg->error->code;
        $hit = preg_match("/Page ID (.+) was migrated to page ID (.+)\\./", $errorMsg->error->message, $matches);
        if ($code == 21 && $hit > 0)
        {
            $newID = $matches[2];
            $this->handleMigration($page, $newID);
        }
    }

    private function handleMigration($oldPage, $newID)
    {
        if ($this->getDBPageValue($newID) == null)
        {
            $category = $oldPage["mnemono"]["category"];
            $city = $oldPage["mnemono"]["location"]["city"];
            $country = $oldPage["mnemono"]["location"]["country"];
            $crawlTime = $oldPage["mnemono"]["crawlTime"];
            $this->crawlNewPage($newID, $category, $city, $country, $crawlTime);
        } else
        {
            // Let it go
        }
    }

    /**
     * @param $fbId
     * @return array|null
     */
    private function getDBPageValue($fbId)
    {
        return $this->getFbPageRepo()->findOneByFbId($fbId);
    }

    /**
     * @return array|null
     */
    private function crawlProfilePicture($pageFbId)
    {
        $request = new FacebookRequest($this->getFbSession(), 'GET', '/' . $pageFbId . '/picture?type=large&redirect=false');
        $headerMsg = "get error while crawling page profile picture:" . $pageFbId;
        $pictureResponse = $this->tryRequest($request, $headerMsg);
        if ($pictureResponse == null)
        {
            return null;
        }
        $pageProfilePicture = $pictureResponse->getResponse();
        return $pageProfilePicture->data;
    }


}