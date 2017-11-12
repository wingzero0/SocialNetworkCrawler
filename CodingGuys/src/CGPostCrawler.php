<?php

namespace CodingGuys;

use CodingGuys\Document\FacebookFeed;
use CodingGuys\Document\FacebookFeedTimestamp;
use CodingGuys\FbRepo\FbFeedRepo;
use CodingGuys\Utility\DateUtility;

class CGPostCrawler extends CGFbCrawler
{
    private $queueClient;
    private $pageFbId;
    private $pageMongoId;
    private $batchTime;

    /**
     * @param IFacebookSdk $fb
     * @param IQueueClient $queueClient
     * @param string $postFbId
     * @param \MongoDB\BSON\ObjectID $pageMongoId
     * @param \MongoDB\BSON\UTCDateTime $batchTime
     */
    public function __construct(IFacebookSdk $fb,
                                IQueueClient $queueClient,
                                $postFbId,
                                \MongoDB\BSON\ObjectID $pageMongoId,
                                \MongoDB\BSON\UTCDateTime $batchTime)
    {
        parent::__construct($fb);
        $this->queueClient = $queueClient;
        $this->postFbId = $postFbId;
        $this->pageMongoId = $pageMongoId;
        $this->batchTime = $batchTime;
    }

    /**
     * @return string CGFbCrawler::FAIL|CGFbCrawler::SUCCESS
     */
    public function crawl()
    {
        $requestEndPoint = $this->getPostEndpoint($this->postFbId);
        $headerMsg = "get error while crawling post: " . $this->postFbId;
        $response = $this->tryRequest($requestEndPoint, $headerMsg);
        if (empty($response))
        {
            return CGFbCrawler::FAIL;
        }
        $body = $response->getDecodedBody();
        if (empty($body))
        {
            return CGFbCrawler::FAIL;
        }
        $newFeed = FacebookFeed::constructByFbArray(
            $body,
            $this->getFbDM()->createPageRef($this->pageMongoId)
        );

        $oldFeedArray = $this->getFbDM()->upsertDB($newFeed, array("fbID" => $newFeed->getFbId()));
        if (empty($oldFeedArray))
        {
            $this->syncFeed($newFeed->getFbId(), true);
            // TODO get new Doc Mongo ID from db operation?
            $this->createFeedTimestamp($newFeed);
        } else
        {
            $oldFeed = FacebookFeed::constructByMongoArray($oldFeedArray);
            $different = $oldFeed->isDiffMetricFrom($newFeed);
            if (true === $_ENV['POST_SNAPSHOT_FORCED_SAVE'])
            {
                $this->syncFeed($newFeed->getFbId(), false);
                $this->createFeedTimestamp($newFeed, $oldFeed->getId());
            }
            else if ($different)
            {
                $this->syncFeed($newFeed->getFbId(), false);
                $this->createFeedTimestamp($newFeed, $oldFeed->getId());
            }
        }
        return CGFbCrawler::SUCCESS;
    }

    private function syncFeed($fbId, $createdFlag = true)
    {
        $workload = json_encode(array("fbId" => $fbId));

        if ($createdFlag)
        {
            $this->queueClient
                 ->doBackground($_ENV['QUEUE_CREATE_POST'], $workload);
        } else
        {
            $this->queueClient
                 ->doBackground($_ENV['QUEUE_UPDATE_POST'], $workload);
        }
    }

    /**
     * @param FacebookFeed $feedObj
     * @param \MongoDB\BSON\ObjectID $feedMongoId
     * @return FacebookFeedTimestamp
     */
    private function createFeedTimestamp($feedObj, $feedMongoId = null)
    {
        $timestamp = FacebookFeedTimestamp::createEmptyObj();

        $likes = $feedObj->getReactionsLike();
        if (!empty($likes) && isset($likes["summary"]["total_count"]))
        {
            $timestamp->setReactionsLikeTotalCount($likes["summary"]["total_count"]);
        }

        $loves = $feedObj->getReactionsLove();
        if (!empty($loves) && isset($loves["summary"]["total_count"]))
        {
            $timestamp->setReactionsLoveTotalCount($loves["summary"]["total_count"]);
        }

        $wows = $feedObj->getReactionsWow();
        if (!empty($wows) && isset($wows["summary"]["total_count"]))
        {
            $timestamp->setReactionsWowTotalCount($wows["summary"]["total_count"]);
        }

        $hahas = $feedObj->getReactionsHaha();
        if (!empty($hahas) && isset($hahas["summary"]["total_count"]))
        {
            $timestamp->setReactionsHahaTotalCount($hahas["summary"]["total_count"]);
        }

        $sads = $feedObj->getReactionsSad();
        if (!empty($sads) && isset($sads["summary"]["total_count"]))
        {
            $timestamp->setReactionsSadTotalCount($sads["summary"]["total_count"]);
        }

        $angries = $feedObj->getReactionsAngry();
        if (!empty($angries) && isset($angries["summary"]["total_count"]))
        {
            $timestamp->setReactionsAngryTotalCount($angries["summary"]["total_count"]);
        }

        $comments = $feedObj->getComments();
        if (!empty($comments) && isset($comments["summary"]["total_count"]))
        {
            $timestamp->setCommentsTotalCount($comments["summary"]["total_count"]);
        }

        $shares = $feedObj->getShares();
        if (!empty($shares) && isset($shares["count"]))
        {
            $timestamp->setSharesTotalCount($shares["count"]);
        }

        $fbDM = $this->getFbDM();
        $timestamp->setFbPage($fbDM->createPageRef($this->pageMongoId));
        if (!($feedMongoId instanceof \MongoDB\BSON\ObjectID))
        {
            $feedMongoId = $this->getFeedMongoId($feedObj->getFbId());
        }
        $timestamp->setFbFeed($fbDM->createFeedRef($feedMongoId));
        $timestamp->setUpdateTime(DateUtility::getCurrentMongoDate());
        $timestamp->setBatchTime($this->batchTime);
        $timestamp->setPostCreatedTime($feedObj->getCreatedTime());
        $fbDM->writeToDB($timestamp);

        return $timestamp;
    }

    /**
     * @param string $fbID
     * @return \MongoDB\BSON\ObjectID|null
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
