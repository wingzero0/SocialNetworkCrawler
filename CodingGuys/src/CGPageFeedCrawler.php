<?php
/**
 * User: kit
 * Date: 17/03/15
 * Time: 20:30
 */

namespace CodingGuys;

use CodingGuys\Document\FacebookFeed;
use CodingGuys\Document\FacebookFeedTimestamp;
use CodingGuys\Document\FacebookPage;
use CodingGuys\Document\FacebookPageTimestamp;
use CodingGuys\FbRepo\FbFeedRepo;
use Facebook\FacebookRequest;
use Facebook\FacebookResponse;
use Facebook\FacebookSession;
use Facebook\FacebookRequestException;
use Facebook\FacebookThrottleException;

// TODO rename to PageFeedCrawler
class CGPageFeedCrawler extends CGFbCrawler
{
    private $pageFbId;
    private $pageMongoId;
    private $batchTime;

    const FAIL = "fail";
    const SUCCESS = "success";


    /**
     * @param string $pageFbId
     * @param \MongoId $pageMongoId
     * @param \MongoDate $batchTime
     * @param string $appId
     * @param string $appSecret
     */
    public function __construct($pageFbId, $pageMongoId, \MongoDate $batchTime, $appId, $appSecret)
    {
        parent::__construct($appId, $appSecret);
        $this->pageFbId = $pageFbId;
        $this->pageMongoId = $pageMongoId;
        $this->batchTime = $batchTime;
    }

    /**
     * @return string CGFeedCrawler::FAIL|CGFeedCrawler::SUCCESS
     */
    public function crawl()
    {
        $ret = $this->crawlPage();
        if ($ret == CGPageFeedCrawler::FAIL)
        {
            return CGPageFeedCrawler::FAIL;
        }
        $ret = $this->crawlFeed();
        if ($ret == CGPageFeedCrawler::FAIL)
        {
            return CGPageFeedCrawler::FAIL;
        }
        return CGPageFeedCrawler::SUCCESS;
    }

    private function crawlPage()
    {
        $request = new FacebookRequest($this->getFbSession(), 'GET', '/' . $this->pageFbId);
        $headerMsg = "get error while crawling page:" . $this->pageFbId;
        $response = $this->tryRequest($request, $headerMsg);
        if ($response == null)
        {
            return CGPageFeedCrawler::FAIL;
        }
        $this->findAndModifyPage($this->pageFbId, $response->getResponse()); // test from var dump , $response->getResponse() is a stdClass
        return CGPageFeedCrawler::SUCCESS;
    }

    private function crawlFeed()
    {
        $request = new FacebookRequest($this->getFbSession(), 'GET', '/' . $this->pageFbId . '/posts');
        $headerMsg = "get error while crawling page:" . $this->pageFbId;
        $response = $this->tryRequest($request, $headerMsg);
        if ($response == null)
        {
            return CGPageFeedCrawler::FAIL;
        }

        $responseData = $response->getResponse();

        foreach ($responseData->data as $i => $feed)
        {
            $this->findAndModifyFeed($feed);
        }
        return CGPageFeedCrawler::SUCCESS;
    }

    /**
     * @param \stdClass $newPage
     * @param string $oldFbId
     */
    private function findAndModifyPage($oldFbId, $newPage)
    {
        $oldPage = $this->queryOldPage($oldFbId);

        $newPage->fbID = $newPage->id;
        unset($newPage->id);
        $newPage = json_decode(json_encode($newPage), true);

        $fbObj = new FacebookPage($oldPage);
        $fbObj->setFbResponse($newPage);
        $this->getFbDM()->writeToDB($fbObj);
        // TODO handle duplicated fbID because of migration / changing fans page

        $different = $this->checkPageDiff($oldPage, $newPage);
        if ($different)
        {
            $this->createPageTimestamp($newPage);
            // TODO sync page
        }
    }

    /**
     * @param string $oldFbId
     * @return array one of mongo result record
     * @throws \Exception
     */
    private function queryOldPage($oldFbId)
    {
        $repo = $this->getFbPageRepo();
        $oldPage = $repo->findOneByFbId($oldFbId);
        if ($oldPage == null)
        {
            $e = new \Exception("unknown page fb id:" . $oldFbId);
            $this->dumpErr($e, "find and modify page fb id:" . $oldFbId);
            throw $e;
        }
        return $oldPage;
    }

    /**
     * @param array $oldPage
     * @param array $newPage
     * @return bool
     */
    private function checkPageDiff($oldPage, $newPage)
    {
        $oldValue = (isset($oldPage["were_here_count"]) ? $oldPage["were_here_count"] : 0);
        $newValue = (isset($newPage["were_here_count"]) ? $newPage["were_here_count"] : 0);
        if ($oldValue != $newValue)
        {
            return true;
        }

        $oldValue = (isset($oldPage["talking_about_count"]) ? $oldPage["talking_about_count"] : 0);
        $newValue = (isset($newPage["talking_about_count"]) ? $newPage["talking_about_count"] : 0);
        if ($oldValue != $newValue)
        {
            return true;
        }

        $oldValue = (isset($oldPage["likes"]) ? $oldPage["likes"] : 0);
        $newValue = (isset($newPage["likes"]) ? $newPage["likes"] : 0);
        // TODO change to fan_count in fb api 2.6
        if ($oldValue != $newValue)
        {
            return true;
        }

        return false;
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
        } else
        {
            $doc->setWereHereCount(0);
        }

        if (isset($page["talking_about_count"]))
        {
            $doc->setTalkingAboutCount($page["talking_about_count"]);
        } else
        {
            $doc->setTalkingAboutCount(0);
        }

        if (isset($page["likes"]))
        {
            // TODO change to fan_count in fb api 2.6
            $doc->setLikes($page["likes"]);
        } else
        {
            $doc->setLikes(0);
        }

        $doc->setFbPage($this->getFbDM()->createPageRef($this->pageMongoId));
        $doc->setUpdateTime(new \MongoDate());
        $doc->setBatchTime($this->batchTime);

        $this->getFbDM()->writeToDB($doc);
    }

    /**
     * @param \stdClass $feed
     */
    private function findAndModifyFeed($feed)
    {
        $feedArr = json_decode(json_encode($feed), true);
        $feedArr["fbID"] = $feedArr["id"];
        unset($feedArr["id"]);
        $newDoc = new FacebookFeed();
        $newDoc->setFbResponse($feedArr);
        $newDoc->setFbId($feedArr["fbID"]);
        $newDoc->setFbPage($this->getFbDM()->createPageRef($this->pageMongoId));

        $extraInfo = $this->queryFeedExtraInfo($newDoc->getFbId());
        $extraInfo = json_decode(json_encode($extraInfo), true);
        if (isset($extraInfo["likes"]))
        {
            $newDoc->setLikes($extraInfo["likes"]);
        }
        if (isset($extraInfo["comments"]))
        {
            $newDoc->setComments($extraInfo["comments"]);
        }
        if (isset($extraInfo["attachments"]))
        {
            $newDoc->setAttachments($extraInfo["attachments"]);
        }

        $oldFeed = $this->getFbDM()->upsertDB($newDoc, array("fbID" => $newDoc->getFbId()));
        if (empty($oldFeed))
        {
            $this->syncFeed($newDoc->getFbId(), true);
            // TODO get new Doc Mongo ID from db operation?
            $this->createFeedTimestamp($newDoc);
        } else
        {
            $oldDoc = new FacebookFeed($oldFeed);
            $different = $this->checkFeedDiff($oldDoc, $newDoc);
            if ($different)
            {
                $this->syncFeed($newDoc->getFbId(), false);
                $this->createFeedTimestamp($newDoc, $oldDoc->getId());
            }
        }
    }

    private function checkFeedDiff(FacebookFeed $oldDoc, FacebookFeed $newDoc)
    {
        $diff = $this->compareCountAttr($oldDoc->getLikes(), $newDoc->getLikes());
        if ($diff)
        {
            return true;
        }

        $diff = $this->compareCountAttr($oldDoc->getComments(), $newDoc->getComments());
        if ($diff)
        {
            return true;
        }

        $diff = $this->compareCountAttr($oldDoc->getShares(), $newDoc->getShares());
        if ($diff)
        {
            return true;
        }

        return false;
    }

    /**
     * @param array $oldFeedAttr
     * @param array $newFeedAttr
     * @return bool
     */
    private function compareCountAttr($oldFeedAttr, $newFeedAttr)
    {
        $oldFeedTotalCount = 0;
        if (is_array($oldFeedAttr))
        {
            if (isset($oldFeedAttr["summary"]) && isset($oldFeedAttr["summary"]["total_count"]))
            {
                $oldFeedTotalCount = $oldFeedAttr["summary"]["total_count"];
            } else if (isset($oldFeedAttr["count"]))
            {
                $oldFeedTotalCount = $oldFeedAttr["count"];
            }
        }

        $newFeedTotalCount = 0;
        if (is_array($newFeedAttr))
        {
            if (isset($newFeedAttr["summary"]) && isset($newFeedAttr["summary"]["total_count"]))
            {
                $newFeedTotalCount = $newFeedAttr["summary"]["total_count"];
            } else if (isset($newFeedAttr["count"]))
            {
                $newFeedTotalCount = $newFeedAttr["count"];
            }
        }

        if ($newFeedTotalCount != $oldFeedTotalCount)
        {
            return true;
        }
        return false;
    }

    private function syncFeed($fbId, $createdFlag = true)
    {
        // Create our client object
        $client = new \GearmanClient();

        // Add a server
        $client->addServer(); // by default host/port will be "localhost" & 4730
        $workload = json_encode(array("fbId" => $fbId));

        if ($createdFlag)
        {
            $job_handle = $client->doBackground("MnemonoBackgroundServiceBundleServicesSyncFbFeedService~createPost", $workload);
        } else
        {
            $job_handle = $client->doBackground("MnemonoBackgroundServiceBundleServicesSyncFbFeedService~updatePost", $workload);
        }


    }

    /**
     * Query feed's likes and comment total count
     * @param $fbID
     * @return \stdClass extraInfo
     */
    private function queryFeedExtraInfo($fbID)
    {
        $request = new FacebookRequest($this->getFbSession(), 'GET', '/' . $fbID . '/?fields=likes.limit(5).summary(true),comments.limit(5).summary(true),attachments');
        $headerMsg = "get error while crawling feed:" . $fbID;
        $response = $this->tryRequest($request, $headerMsg);
        if ($response == null)
        {
            return new \stdClass();
        }

        return $response->getResponse();
    }

    /**
     * @param FacebookFeed $feedObj
     * @param \MongoId $feedMongoId
     * @return FacebookFeedTimestamp
     */
    private function createFeedTimestamp($feedObj, $feedMongoId = null)
    {
        $timestamp = new FacebookFeedTimestamp();
        $likes = $feedObj->getLikes();
        if (!empty($likes) && isset($likes["summary"]["total_count"]))
        {
            $timestamp->setLikesTotalCount($likes["summary"]["total_count"]);
        }

        $comments = $feedObj->getComments();
        if (!empty($comments) && isset($comments["summary"]["total_count"]))
        {
            $timestamp->setCommentsTotalCount($comments["summary"]["total_count"]);
        }

        $shares = $feedObj->getShares();
        if (!empty($shares) && isset($shares["count"]))
        {
            $timestamp->setShareTotalCount($shares["count"]);
        }

        $fbDM = $this->getFbDM();
        $timestamp->setFbPage($fbDM->createPageRef($this->pageMongoId));
        if (!($feedMongoId instanceof \MongoId))
        {
            $feedMongoId = $this->getFeedMongoId($feedObj->getFbId());
        }
        $timestamp->setFbFeed($fbDM->createFeedRef($feedMongoId));
        $timestamp->setUpdateTime(new \MongoDate());
        $timestamp->setBatchTime($this->batchTime);
        $fbDM->writeToDB($timestamp);

        return $timestamp;
    }

    /**
     * @param string $fbID
     * @return \MongoId|null
     */
    private function getFeedMongoId($fbID)
    {
        $repo = new FbFeedRepo($this->getFbDM());
        $feed = $repo->findOneByFbId($fbID);
        if (isset($feed["_id"]))
        {
            return $feed["_id"];
        }
        return null;
    }

}