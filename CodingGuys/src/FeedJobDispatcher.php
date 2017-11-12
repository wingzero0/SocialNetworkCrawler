<?php

namespace CodingGuys;

use CodingGuys\FbRepo\FbPageRepo;
use CodingGuys\Document\FacebookPage;

class FeedJobDispatcher
{
    private $queueClient;

    public function __construct(IQueueClient $queueClient)
    {
        $this->queueClient = $queueClient;
    }

    public function dispatchAt(\DateTime $time)
    {
        $crawlTimeH = intval($time->format('H'));
        $batchTime = $time->format(\DateTime::ISO8601);
        $crawlTimeArray = explode(',', $_ENV['FEED_CRAWL_TIME']);
        if (in_array((string)$crawlTimeH, $crawlTimeArray))
        {
            $repo = new FbPageRepo();
            $cursor = $repo->findAllWorkingPage();
            foreach ($cursor as $doc)
            {
                $fbPage = new FacebookPage($doc);
                $since = $fbPage->getLastPostCreatedTime();
                echo 'crawling feed: ' . $fbPage->getFbID() . "\n";
                $workload = json_encode([
                    'fbID' => $fbPage->getFbID(),
                    'pageMongoId' => $fbPage->getId() . '',
                    'batchTime' => $batchTime,
                    'since' => $since,
                    'until' => null,
                    'type' => 'feed',
                ]);
                $this->queueClient
                     ->doBackground($_ENV['QUEUE_CRAWLER'], $workload);
            }
        }
    }
}
