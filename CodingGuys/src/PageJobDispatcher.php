<?php

namespace CodingGuys;

use CodingGuys\FbRepo\FbPageRepo;
use CodingGuys\Document\FacebookPage;

class PageJobDispatcher
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
        $crawlTimeArray = explode(',', $_ENV['PAGE_CRAWL_TIME']);
        if (in_array((string)$crawlTimeH, $crawlTimeArray))
        {
            $repo = new FbPageRepo();
            $cursor = $repo->findAllWorkingPage();
            foreach ($cursor as $doc)
            {
                $fbPage = new FacebookPage($doc);
                echo 'crawling page: ' . $fbPage->getFbID() . "\n";
                $workload = json_encode([
                    'fbID' => $fbPage->getFbID(),
                    'pageMongoId' => $fbPage->getId() . '',
                    'batchTime' => $batchTime,
                    'type' => 'page',
                ]);
                $this->queueClient
                     ->doBackground($_ENV['QUEUE_CRAWLER'], $workload);
            }
        }
    }
}
