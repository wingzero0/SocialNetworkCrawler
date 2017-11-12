<?php

namespace CodingGuys\Tests;

use PHPUnit\Framework\TestCase;
use CodingGuys\PageJobDispatcher;

class PageJobDispatcherTest extends TestCase
{
    private static $queueClient;
    private static $pageCol;

    public static function setUpBeforeClass()
    {
        self::$queueClient = new FakeQueueClient();
        $dbClient = new \MongoDB\Client($_ENV['MONGODB_URI']);
        self::$pageCol = $dbClient->selectCollection(
            $_ENV['MONGODB_DATABASE'],
            'FacebookPage'
        );
        self::$pageCol->drop();
    }

    protected function tearDown()
    {
        self::$queueClient->reset();
        self::$pageCol->drop();
    }

    public function testDispatch()
    {
        $doc = [
            'name' => 'xxx',
        ];
        self::$pageCol->insertOne($doc);

        $time = new \DateTime(
            'now',
            new \DateTimeZone('GMT')
        );
        $pageJobDispatcher = new PageJobDispatcher(self::$queueClient);
        $pageJobDispatcher->dispatchAt($time);
        $this->assertEquals(1, self::$queueClient->count($_ENV['QUEUE_CRAWLER']));
    }
}
