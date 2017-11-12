<?php
/**
 * User: kit
 * Date: 13/1/2016
 * Time: 11:43
 */

namespace CodingGuys;

use CodingGuys\Document\FacebookExceptionPage;
use CodingGuys\Document\FacebookPage;
use CodingGuys\Document\FacebookPageTimestamp;
use CodingGuys\Utility\DateUtility;
use Facebook\Exceptions\FacebookResponseException;

// TODO migrate query to FacebookPageRepo
class CGPageCrawler extends CGFbCrawler
{
    private $queueClient;
    private $pageFbId;
    private $batchTime;
    private $pageMongoId;

    /**
     * @param IFacebookSdk $fb
     * @param IQueueClient $queueClient
     * @param string $pageFbId
     * @param \MongoDB\BSON\UTCDateTime $batchTime
     * @param \MongoDB\BSON\ObjectID $pageMongoId
     */
    public function __construct(IFacebookSdk $fb,
                                IQueueClient $queueClient,
                                $pageFbId,
                                \MongoDB\BSON\UTCDateTime $batchTime,
                                \MongoDB\BSON\ObjectID $pageMongoId = null)
    {
        parent::__construct($fb);
        $this->queueClient = $queueClient;
        $this->pageFbId = $pageFbId;
        $this->batchTime = $batchTime;
        $this->pageMongoId = $pageMongoId;
    }

    /**
     * @return string CGFbCrawler::FAIL|CGFbCrawler::SUCCESS
     */
    public function crawl()
    {
        $requestEndPoint = $this->getPageEndpoint($this->pageFbId);
        $headerMsg = "get error while crawling page:" . $this->pageFbId;
        $response = $this->tryRequest($requestEndPoint, $headerMsg);
        if ($response == null)
        {
            $this->handleError($this->getLastException(), $this->pageFbId);
            return CGFbCrawler::FAIL;
        }
        $this->findAndModifyPage($this->pageFbId, $response->getDecodedBody());
        return CGFbCrawler::SUCCESS;
    }

    /**
     * @param string $oldFbId
     * @param array $fbResponseArray
     */
    private function findAndModifyPage($oldFbId, $fbResponseArray)
    {
        $prepareToUpdate = FacebookPage::constructByMongoArray($this->findPageByFbId($oldFbId));
        $prepareToUpdate->setFbResponse($fbResponseArray);
        $oldPageArray = $this->getFbDM()->upsertDB($prepareToUpdate, array("fbID" => $prepareToUpdate->getFbId()));
        $oldPage = FacebookPage::constructByMongoArray($oldPageArray);
        if (true === $_ENV['PAGE_SNAPSHOT_FORCED_SAVE'])
        {
            $this->createPageTimestamp($fbResponseArray);
        }
        else if ($prepareToUpdate->isDiffMetricFrom($oldPage))
        {
            $this->createPageTimestamp($fbResponseArray);
        }
        // TODO handle duplicated fbID because of migration / changing fans page
    }

    /**
     * @param string $fbId
     * @return array one of mongo result record
     * @throws \Exception
     */
    private function findPageByFbId($fbId)
    {
        $repo = $this->getFbPageRepo();
        $raw = $repo->findOneByFbId($fbId);
        if ($raw == null)
        {
            $e = new \UnexpectedValueException("unknown page fb id:" . $fbId);
            $this->dumpErr($e, "find and modify page fb id:" . $fbId);
            throw $e;
        }
        return $raw;
    }



    /**
     * @param \MongoDB\BSON\ObjectID $id
     * @return array|null
     */
    private function findPageByMongoId(\MongoDB\BSON\ObjectID $id)
    {
        $repo = $this->getFbPageRepo();
        $raw = $repo->findOneById($id);
        if ($raw === null)
        {
            $e = new \UnexpectedValueException("unknown mongo id:" . $id);
            $this->dumpErr($e, "find and modify page fb id:" . $fbId);
            throw $e;
        }
        return $raw;
    }

    /**
     * @param array $page
     */
    private function createPageTimestamp($page)
    {
        $doc = new FacebookPageTimestamp();

        if (isset($page["were_here_count"]))
        {
            $doc->setWereHereCount($page["were_here_count"]);
        }
        else
        {
            $doc->setWereHereCount(0);
        }

        if (isset($page["talking_about_count"]))
        {
            $doc->setTalkingAboutCount($page["talking_about_count"]);
        }
        else
        {
            $doc->setTalkingAboutCount(0);
        }

        if (isset($page["fan_count"]))
        {
            $doc->setFanCount($page["fan_count"]);
        }
        else
        {
            $doc->setFanCount(0);
        }

        if (isset($page["overall_star_rating"]))
        {
            $doc->setOverallStarRating($page["overall_star_rating"]);
        }
        else
        {
            $doc->setOverallStarRating(0.0);
        }

        if (isset($page["rating_count"]))
        {
            $doc->setRatingCount($page["rating_count"]);
        }
        else
        {
            $doc->setRatingCount(0);
        }

        $doc->setFbPage($this->getFbDM()->createPageRef($this->pageMongoId));
        $doc->setUpdateTime(DateUtility::getCurrentMongoDate());
        $doc->setBatchTime($this->batchTime);

        $this->getFbDM()->writeToDB($doc);
    }

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
        $requestEndPoint = $this->getPageEndpoint($pageFbId);
        $headerMsg = "get error while crawling page:" . $pageFbId;
        $response = $this->tryRequest($requestEndPoint, $headerMsg);
        if ($response == null)
        {
            return CGFbCrawler::FAIL;
        }
        $pageMainContent = $response->getDecodedBody();
        $page = FacebookPage::constructByFbArray($pageMainContent);
        $page->setMnemono(array(
            "category" => $category,
            "location" => array("city" => $city, "country" => $country),
            "crawlTime" => $crawlTime,
        ));

        $res = $this->getFbDM()->writeToDB($page);
        $this->pageMongoId = $res->getInsertedId();
        $this->createPageTimestamp($pageMainContent);
        $this->syncPage($pageFbId, true);
        return CGFbCrawler::SUCCESS;
    }

    /**
     * @param \MongoDB\BSON\ObjectID $id
     * @param string $category
     * @param string $city
     * @param string $country
     * @param array $crawlTime
     * @return string
     */
    public function reCrawlData(\MongoDB\BSON\ObjectID $id,
                                $category,
                                $city,
                                $country,
                                $crawlTime)
    {
        $this->pageMongoId = $id;
        $fbPage = FacebookPage::constructByMongoArray($this->findPageByMongoId($id));
        $requestEndPoint = $this->getPageEndpoint($fbPage->getFbID());
        $headerMsg = "get error while crawling page:" . $fbPage->getFbID();
        $response = $this->tryRequest($requestEndPoint, $headerMsg);
        if ($response == null)
        {
            return CGFbCrawler::FAIL;
        }
        $pageMainContent = $response->getDecodedBody();
        $fbPage->setException(false);
        $fbPage->setError(null);
        $fbPage->setFbResponse($pageMainContent);
        $fbPage->setMnemono(array(
            "category" => $category,
            "location" => array("city" => $city, "country" => $country),
            "crawlTime" => $crawlTime,
        ));
        $oldPageArray = $this->getFbDM()->upsertDB($fbPage, array("fbID" => $fbPage->getFbID()));
        $oldPage = FacebookPage::constructByMongoArray($oldPageArray);
        if (true === $_ENV['PAGE_SNAPSHOT_FORCED_SAVE'])
        {
            $this->createPageTimestamp($pageMainContent);
        }
        else if ($oldPage->isDiffMetricFrom($fbPage))
        {
            $this->createPageTimestamp($pageMainContent);
        }
        $this->syncPage($fbPage->getFbID(), false);
        return CGFbCrawler::SUCCESS;
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
     * @param \Exception $e
     * @param string $pageFbId
     */
    private function handleError(\Exception $e, $pageFbId)
    {
        $pageRaw = $this->getFbPageRepo()->findOneByFbId($pageFbId);
        $errPage = new FacebookExceptionPage($pageRaw);
        $errPage->setException(true);
        $errPage->setExceptionTime(DateUtility::getCurrentMongoDate());
        $errPage->setId(null);

        if ($e instanceof FacebookResponseException)
        {
            // TODO create test case for it, confirm exception type
            $errorResponse = $e->getResponseData();
            $code = $errorResponse["error"]["code"];
            $hit = preg_match("/Page ID (.+) was migrated to page ID (.+)\\./", $errorResponse["error"]["message"], $matches);

            $oldPage = new FacebookPage($pageRaw);
            if ($code == 21 && $hit > 0)
            {
                $newID = $matches[2];
                $this->migratePage($oldPage, $newID);
                $this->markAsException($oldPage, $errorResponse["error"]);
            } else if ($code == 100)
            {
                $this->markAsException($oldPage, $errorResponse["error"]);
            }
            $errPage->setError($errorResponse["error"]);
        } else
        {
            $errPage->setError(array("message" => $e->getMessage(), "trace" => $e->getTraceAsString()));
        }
        $this->getFbDM()->writeToDB($errPage);
    }

    /**
     * @param FacebookPage $oldPage
     * @param array $error
     */
    private function markAsException(FacebookPage $oldPage, $error)
    {
        $oldPage->setError($error);
        $oldPage->setException(true);
        $this->getFbDM()->writeToDB($oldPage);
    }

    /**
     * @param FacebookPage $oldPage
     * @param string $newPageFbId
     * @return string
     */
    private function migratePage(FacebookPage $oldPage, $newPageFbId)
    {
        if ($this->getFbPageRepo()->findOneByFbId($newPageFbId) === null)
        {
            $requestEndPoint = $this->getPageEndpoint($newPageFbId);
            $headerMsg = "get error while migrating page:" . $newPageFbId;
            $response = $this->tryRequest($requestEndPoint, $headerMsg);
            if ($response == null)
            {
                return CGFbCrawler::FAIL;
            }

            $mnemono = $oldPage->getMnemono();
            $page = FacebookPage::constructByFbArray($response->getDecodedBody());
            $page->setMnemono($mnemono);
            $this->getFbDM()->writeToDB($page);

            $workload = json_encode(array("fbId" => $newPageFbId));
            $this->queueClient
                 ->doBackground($_ENV['QUEUE_CREATE_BIZ'], $workload);
        }
        return CGFbCrawler::SUCCESS;
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
        $requestEndPoint = '/' . $pageFbId . '/picture?type=large&redirect=false';
        $headerMsg = "get error while crawling page profile picture:" . $pageFbId;
        $pictureResponse = $this->tryRequest($requestEndPoint, $headerMsg);
        if ($pictureResponse == null)
        {
            return null;
        }
        $pageProfilePicture = $pictureResponse->getDecodedBody();
        return $pageProfilePicture['data'];
    }


    private function syncPage($fbId, $createdFlag = true)
    {
        $workload = json_encode(array("fbId" => $fbId));

        if ($createdFlag)
        {
            $this->queueClient
                 ->doBackground($_ENV['QUEUE_CREATE_BIZ'], $workload);
        } else
        {
            $this->queueClient
                 ->doBackground($_ENV['QUEUE_UPDATE_BIZ'], $workload);
        }
    }
}
