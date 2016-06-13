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
            return "fail";
        }
        $pageMainContent = $response->getResponse();
        $pageMainContent->fbID = $pageMainContent->id;
        unset($pageMainContent->id);

        $page = new FacebookPage();
        $page->setFbResponse($pageMainContent);
        $page->setMnemono(array(
            "category" => $category,
            "location" => array("city" => $city, "country" => $country),
            "crawlTime" => $crawlTime,
        ));

        $this->getFbDM()->writeToDB($page);

        return "success";
    }

    /**
     * @param \MongoId $id
     * @param string $category
     * @param string $city
     * @param string $country
     * @param array $crawlTime
     */
    public function updateMeta(\MongoId $id, $category, $city, $country, $crawlTime)
    {
        $repo = $this->getFbPageRepo();
        $raw = $repo->findOneById($id);
        if ($raw === null)
        {
            throw new \UnexpectedValueException();
        }
        $page = new FacebookPage($raw);
        $page->setMnemono(array(
            "category" => $category,
            "location" => array("city" => $city, "country" => $country),
            "crawlTime" => $crawlTime,
        ));
        $this->getFbDM()->writeToDB($page);
    }


    /**
     * @param $fbId
     * @return \MongoId|null
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
        $this->setPageAsException($page["fbID"]);
        $page["error"] = $errorMsg->error;
        $this->backupExceptionPage($page);
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
     * @param string $fbID
     */
    private function setPageAsException($fbID)
    {
        $this->getPageCollection()->update(
            array("fbID" => $fbID),
            array("exception" => true, "fbID" => $fbID)
        );
    }

    /**
     * @param array $page the page record fetch from mongoDB;
     */
    private function backupExceptionPage($page)
    {
        echo "backup " . $page["fbID"] . "\n";
        $this->getExceptionPageCollection()->update(array("_id" => $page["_id"]), $page, array("upsert" => true));
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