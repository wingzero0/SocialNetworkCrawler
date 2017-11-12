<?php

namespace CodingGuys\Tests;

use PHPUnit\Framework\TestCase;
use CodingGuys\CGFeedCrawler;
use CodingGuys\FacebookSdk;

class CGFeedCrawlerTest extends TestCase
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

    public function testEndpoint()
    {
        $appId = $_ENV['FB_APP_ID'];
        $appSecret = $_ENV['FB_APP_SECRET'];
        $fbId = '105259197447';
        $mongoId = new \MongoDB\BSON\ObjectID();
        $now = new \DateTime(
            'now',
            new \DateTimeZone('GMT')
        );
        $batchTime = $now->format(\DateTime::ISO8601);
        $since = null;
        $until = null;

        $fbConfig = [
            'app_id' => $appId,
            'app_secret' => $appSecret,
            'default_graph_version' => $_ENV['FB_DEFAULT_GRAPH_VERSION'],
            'default_access_token' => $appId . '|' . $appSecret,
        ];
        $crawler = new CGFeedCrawler(
            new FacebookSdk($fbConfig),
            self::$queueClient,
            $fbId,
            $mongoId,
            $batchTime,
            $since,
            $until
        );
        $endpoint = $this->invokeMethod($crawler, 'getFeedEndpoint', [
            $fbId,
            $since,
            $until
        ]);
        $expectedEndpoint = '/' . $fbId . '/posts?limit=' . $crawler::FEED_LIMIT;
        $this->assertEquals($expectedEndpoint, $endpoint);
    }

    public function testCrawlSuccess()
    {
        $doc = [
            'name' => 'xxx',
        ];
        $res = self::$pageCol->insertOne($doc);
        $pageMongoId = $res->getInsertedId();

        $appId = $_ENV['FB_APP_ID'];
        $appSecret = $_ENV['FB_APP_SECRET'];
        $fbId = '105259197447';
        $mongoId = $pageMongoId;
        $now = new \DateTime(
            'now',
            new \DateTimeZone('GMT')
        );
        $batchTime = $now->format(\DateTime::ISO8601);
        $since = null;
        $until = null;
        $fbConfig = [
            'app_id' => $appId,
            'app_secret' => $appSecret,
            'default_graph_version' => $_ENV['FB_DEFAULT_GRAPH_VERSION'],
            'default_access_token' => $appId . '|' . $appSecret,
        ];
        $crawler = new CGFeedCrawler(
            new FacebookSdk($fbConfig),
            self::$queueClient,
            $fbId,
            $mongoId,
            $batchTime,
            $since,
            $until
        );
        $crawler->crawl();
        $this->assertEquals(26, self::$queueClient->count($_ENV['QUEUE_CRAWLER']));
    }

    public function invokeMethod(&$object,
                                 $methodName,
                                 array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
