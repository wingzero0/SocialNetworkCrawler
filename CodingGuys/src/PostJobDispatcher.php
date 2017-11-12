<?php

namespace CodingGuys;

use CodingGuys\FbRepo\FbFeedRepo;
use CodingGuys\Document\FacebookFeed;

class PostJobDispatcher
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
        for ($i = 0, $l = count($_ENV['POST_CRAWL_TIME_IN_PERIODS']); $i < $l; $i++)
        {
            $crawlTimeArray = explode(',', $_ENV['POST_CRAWL_TIME_IN_PERIODS'][$i]);
            if (in_array((string)$crawlTimeH, $crawlTimeArray))
            {
                $startTime = null;
                $endTime = null;
                if (!empty($_ENV['POST_BREAKPOINTS'][$i - 1]))
                {
                    $ts = $time->getTimestamp() -
                        (intval($_ENV['POST_BREAKPOINTS'][$i - 1]) * 60 * 60);
                    $endTime = new \DateTime(
                        "@$ts",
                        new \DateTimeZone('GMT')
                    );
                }
                if (!empty($_ENV['POST_BREAKPOINTS'][$i]))
                {
                    $ts = $time->getTimestamp() -
                        (intval($_ENV['POST_BREAKPOINTS'][$i]) * 60 * 60);
                    $startTime = new \DateTime(
                        "@$ts",
                        new \DateTimeZone('GMT')
                    );
                }
                $repo = new FbFeedRepo();
                $cursor = $repo->findFeedByCreatedTime($startTime, $endTime);
                foreach ($cursor as $doc)
                {
                    $fbPost = new FacebookFeed($doc);
                    $fbId = $fbPost->getFbId();
                    echo 'crawling post: ' . $fbId . "\n";
                    $workload = json_encode([
                        'fbID' => $fbId,
                        'pageMongoId' => $fbPost->getFbPage()['$id'] . '',
                        'batchTime' => $batchTime,
                        'type' => 'post',
                    ]);
                    $this->queueClient
                         ->doBackground($_ENV['QUEUE_CRAWLER'], $workload);
                }
            }
        }
    }
}
