<?php

namespace CodingGuys;

use CodingGuys\Document\FacebookFeed;
use CodingGuys\Document\FacebookPage;

class CGFeedCrawler extends CGFbCrawler
{
    private $queueClient;
    private $pageFbId;
    private $pageMongoId;
    private $batchTime;
    private $since;
    private $until;

    /**
     * @param IFacebookSdk $fb
     * @param IQueueClient $queueClient
     * @param string $pageFbId
     * @param \MongoDB\BSON\ObjectID $pageMongoId
     * @param string $batchTime
     * @param string $since
     * @param string $until
     */
    public function __construct(IFacebookSdk $fb,
                                IQueueClient $queueClient,
                                $pageFbId,
                                \MongoDB\BSON\ObjectID $pageMongoId,
                                $batchTime,
                                $since = null,
                                $until = null)
    {
        parent::__construct($fb);
        $this->queueClient = $queueClient;
        $this->pageFbId = $pageFbId;
        $this->pageMongoId = $pageMongoId;
        $this->batchTime = $batchTime;
        $this->since = $since;
        $this->until = $until;
    }

    /**
     * @return string CGFbCrawler::FAIL|CGFbCrawler::SUCCESS
     */
    public function crawl()
    {
        $requestEndPoint = $this->getFeedEndpoint(
            $this->pageFbId,
            $this->since,
            $this->until
        );
        $headerMsg = "get error while crawling page:" . $this->pageFbId;
        $response = $this->tryRequest($requestEndPoint, $headerMsg);
        if ($response == null)
        {
            return CGFbCrawler::FAIL;
        }

        $responseData = $response->getDecodedBody();
        if (!empty($responseData['data']))
        {
            $data = $responseData['data'];
            foreach ($data as $i => $post)
            {
                $this->createPostJob($post);
            }
            $newestPost = FacebookFeed::constructByFbArray($data[0]);
            $oldestPost = FacebookFeed::constructByFbArray($data[count($data) - 1]);
            $doc = $this->getFbPageRepo()->findOneById($this->pageMongoId);
            $page = FacebookPage::constructByMongoArray($doc);
            if ($page->getLastPostCreatedTime() == null ||
                $page->getLastPostCreatedTime() < $newestPost->getCreatedTime())
            {
                $page->setLastPostCreatedTime($newestPost->getCreatedTime());
                $this->getFbDM()->writeToDB($page);
            }
            $timeDiffInSec = strtotime($this->batchTime) -
                strtotime($oldestPost->getCreatedTime());
            $lastPostBreakpoint = intval(
                $_ENV['POST_BREAKPOINTS'][count($_ENV['POST_BREAKPOINTS']) - 1]
            );
            $lastPostBreakpointInSec = $lastPostBreakpoint * 60 * 60;
            if (!empty($responseData['paging']['next']) &&
                CGFbCrawler::FEED_LIMIT === count($data) &&
                $timeDiffInSec <= $lastPostBreakpointInSec)
            {
                $this->createFeedJob($oldestPost->getCreatedTime());
            }
        }
        return CGFbCrawler::SUCCESS;
    }

    /**
     * @param array $post facebook raw data
     * @return string
     */
    private function createPostJob($post)
    {
        $workload = json_encode([
            'fbID' => $post['id'],
            'pageMongoId' => $this->pageMongoId . '',
            'batchTime' => $this->batchTime,
            'type' => 'post',
        ]);
        $this->queueClient
            ->doBackground($_ENV['QUEUE_CRAWLER'], $workload);
    }

    /**
     * @param string $until date with format ISO8601
     */
    private function createFeedJob($until)
    {
        $workload = json_encode([
            'fbID' => $this->pageFbId,
            'pageMongoId' => $this->pageMongoId . '',
            'batchTime' => $this->batchTime,
            'since' => $this->since,
            'until' => $until,
            'type' => 'feed',
        ]);
        $this->queueClient
            ->doBackground($_ENV['QUEUE_CRAWLER'], $workload);
    }
}
