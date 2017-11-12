<?php

namespace CodingGuys\Tests;

use PHPUnit\Framework\TestCase;
use CodingGuys\PostJobDispatcher;

class PostJobDispatcherTest extends TestCase
{
    private static $queueClient;
    private static $feedCol;

    public static function setUpBeforeClass()
    {
        self::$queueClient = new FakeQueueClient();
        $dbClient = new \MongoDB\Client($_ENV['MONGODB_URI']);
        self::$feedCol = $dbClient->selectCollection(
            $_ENV['MONGODB_DATABASE'],
            'FacebookFeed'
        );
        self::$feedCol->drop();
    }

    protected function tearDown()
    {
        self::$queueClient->reset();
        self::$feedCol->drop();
    }

    public function testDispatchInPeriod1()
    {
        $createdAt = new \DateTime(
            'now',
            new \DateTimeZone('GMT')
        );
        $doc = [
            'name' => 'xxx',
            'created_time' => $createdAt->format(\DateTime::ISO8601),
        ];
        self::$feedCol->insertOne($doc);
        $time = new \DateTime(
            'now',
            new \DateTimeZone('GMT')
        );
        $postJobDispatcher = new PostJobDispatcher(self::$queueClient);
        $postJobDispatcher->dispatchAt($time);
        $this->assertEquals(1, self::$queueClient->count($_ENV['QUEUE_CRAWLER']));
    }

    public function testDispatchInPeriod2AtCrawlTime()
    {
        $crawlTimeArray = explode(',', $_ENV['POST_CRAWL_TIME_IN_PERIODS'][1]);
        $time = new \DateTime(
            'now',
            new \DateTimeZone('GMT')
        );
        $time->setTime(intval($crawlTimeArray[0]), 0);
        $bp = $_ENV['POST_BREAKPOINTS'][0] + 1;
        $interval = 'PT' . $bp . 'H';
        $createdAt = clone $time;
        $createdAt->sub(new \DateInterval($interval));
        $doc = [
            'name' => 'xxx',
            'created_time' => $createdAt->format(\DateTime::ISO8601),
        ];
        self::$feedCol->insertOne($doc);
        $postJobDispatcher = new PostJobDispatcher(self::$queueClient);
        $postJobDispatcher->dispatchAt($time);
        $this->assertEquals(1, self::$queueClient->count($_ENV['QUEUE_CRAWLER']));
    }

    public function testDispatchInPeriod2NotAtCrawlTime()
    {
        $hourArray = [
            0, 1, 2, 3, 4, 5,
            6, 7, 8, 9, 10, 11,
            12, 13, 14, 15, 16, 17,
            18, 19, 20, 21, 22, 23
        ];
        $crawlTimeArray = explode(',', $_ENV['POST_CRAWL_TIME_IN_PERIODS'][1]);
        $diff = array_diff($hourArray, $crawlTimeArray);
        if (empty($diff)) {
            return;
        }
        $time = new \DateTime(
            'now',
            new \DateTimeZone('GMT')
        );
        $time->setTime($diff[0], 0);
        $bp = $_ENV['POST_BREAKPOINTS'][0] + 1;
        $interval = 'PT' . $bp . 'H';
        $createdAt = clone $time;
        $createdAt->sub(new \DateInterval($interval));
        $doc = [
            'name' => 'xxx',
            'created_time' => $createdAt->format(\DateTime::ISO8601),
        ];
        self::$feedCol->insertOne($doc);
        $postJobDispatcher = new PostJobDispatcher(self::$queueClient);
        $postJobDispatcher->dispatchAt($time);
        $this->assertEquals(0, self::$queueClient->count($_ENV['QUEUE_CRAWLER']));
    }

    public function testDispatchInPeriod3()
    {
        $now = new \DateTime(
            'now',
            new \DateTimeZone('GMT')
        );
        $bp = $_ENV['POST_BREAKPOINTS'][1] + 1;
        $interval = 'PT' . $bp . 'H';
        $createdAt = clone $now;
        $createdAt->sub(new \DateInterval($interval));
        $doc = [
            'name' => 'xxx',
            'created_time' => $createdAt->format(\DateTime::ISO8601),
        ];
        self::$feedCol->insertOne($doc);
        $postJobDispatcher = new PostJobDispatcher(self::$queueClient);
        $postJobDispatcher->dispatchAt($now);
        $this->assertEquals(0, self::$queueClient->count($_ENV['QUEUE_CRAWLER']));
    }
}
