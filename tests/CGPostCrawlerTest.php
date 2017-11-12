<?php

namespace CodingGuys\Tests;

use PHPUnit\Framework\TestCase;
use CodingGuys\CGPostCrawler;
use CodingGuys\FacebookSdk;
use CodingGuys\Document\FacebookFeed;
use CodingGuys\FbDocumentManager\FbDocumentManager;

class CGPostCrawlerTest extends TestCase
{
    private static $queueClient;
    private static $feedCol;
    private static $feedTimestampCol;

    public static function setUpBeforeClass()
    {
        self::$queueClient = new FakeQueueClient();
        $dbClient = new \MongoDB\Client($_ENV['MONGODB_URI']);
        self::$feedCol = $dbClient->selectCollection(
            $_ENV['MONGODB_DATABASE'],
            'FacebookFeed'
        );
        self::$feedCol->drop();
        self::$feedTimestampCol = $dbClient->selectCollection(
            $_ENV['MONGODB_DATABASE'],
            'FacebookFeedTimestamp'
        );
        self::$feedTimestampCol->drop();
    }

    protected function tearDown()
    {
        self::$queueClient->reset();
        self::$feedCol->drop();
        self::$feedTimestampCol->drop();
    }

    public function testCrawlNewPost()
    {
        $fb = $this->createMock(FakeFacebookSdk::class);
        $post = require(__DIR__ . '/post.php');
        $res = new FakeFacebookResponse($post);
        $fb->method('get')->willReturn($res);
        $postFbId = $post['id'];
        $pageMongoId = new \MongoDB\BSON\ObjectId();
        $batchTime = new \MongoDB\BSON\UTCDateTime();
        $crawler = new CGPostCrawler(
            $fb,
            self::$queueClient,
            $postFbId,
            $pageMongoId,
            $batchTime
        );
        $crawler->crawl();
        $this->assertEquals(1, self::$queueClient->count($_ENV['QUEUE_CREATE_POST']));
        $this->assertEquals(0, self::$queueClient->count($_ENV['QUEUE_UPDATE_POST']));
        $this->assertEquals(1, self::$feedCol->count());
        $this->assertEquals(1, self::$feedTimestampCol->count());
    }

    public function testCrawlOldPostWithDiff()
    {
        $post = require(__DIR__ . '/post.php');
        $postFbId = $post['id'];
        $postCopy = array_merge([], $post);
        $feedObj = new FacebookFeed();
        unset($postCopy['id']);
        $feedObj->setFbResponse($postCopy);
        $feedObj->setFbId($postFbId);
        $docMgr = new FbDocumentManager();
        $docMgr->writeToDB($feedObj);
        $post['comments']['summary']['total_count']++;
        $fb = $this->createMock(FakeFacebookSdk::class);
        $res = new FakeFacebookResponse($post);
        $fb->method('get')->willReturn($res);
        $pageMongoId = new \MongoDB\BSON\ObjectId();
        $batchTime = new \MongoDB\BSON\UTCDateTime();
        $crawler = new CGPostCrawler(
            $fb,
            self::$queueClient,
            $postFbId,
            $pageMongoId,
            $batchTime
        );
        $crawler->crawl();
        $this->assertEquals(0, self::$queueClient->count($_ENV['QUEUE_CREATE_POST']));
        $this->assertEquals(1, self::$queueClient->count($_ENV['QUEUE_UPDATE_POST']));
        $this->assertEquals(1, self::$feedCol->count());
        $this->assertEquals(1, self::$feedTimestampCol->count());
    }

    public function testCrawlOldPostWithoutDiff()
    {
        $post = require(__DIR__ . '/post.php');
        $postFbId = $post['id'];
        $postCopy = array_merge([], $post);
        $feedObj = new FacebookFeed();
        unset($postCopy['id']);
        $feedObj->setFbResponse($postCopy);
        $feedObj->setFbId($postFbId);
        $docMgr = new FbDocumentManager();
        $docMgr->writeToDB($feedObj);
        $fb = $this->createMock(FakeFacebookSdk::class);
        $res = new FakeFacebookResponse($post);
        $fb->method('get')->willReturn($res);
        $pageMongoId = new \MongoDB\BSON\ObjectId();
        $batchTime = new \MongoDB\BSON\UTCDateTime();
        $crawler = new CGPostCrawler(
            $fb,
            self::$queueClient,
            $postFbId,
            $pageMongoId,
            $batchTime
        );
        $crawler->crawl();
        $this->assertEquals(0, self::$queueClient->count($_ENV['QUEUE_CREATE_POST']));
        $this->assertEquals(0, self::$queueClient->count($_ENV['QUEUE_UPDATE_POST']));
        $this->assertEquals(1, self::$feedCol->count());
        $this->assertEquals(0, self::$feedTimestampCol->count());
    }

    public function testCrawlOldPostWithForcedSave()
    {
        $_ENV['POST_SNAPSHOT_FORCED_SAVE'] = true;
        $post = require(__DIR__ . '/post.php');
        $postFbId = $post['id'];
        $postCopy = array_merge([], $post);
        $feedObj = new FacebookFeed();
        unset($postCopy['id']);
        $feedObj->setFbResponse($postCopy);
        $feedObj->setFbId($postFbId);
        $docMgr = new FbDocumentManager();
        $docMgr->writeToDB($feedObj);
        $fb = $this->createMock(FakeFacebookSdk::class);
        $res = new FakeFacebookResponse($post);
        $fb->method('get')->willReturn($res);
        $pageMongoId = new \MongoDB\BSON\ObjectId();
        $batchTime = new \MongoDB\BSON\UTCDateTime();
        $crawler = new CGPostCrawler(
            $fb,
            self::$queueClient,
            $postFbId,
            $pageMongoId,
            $batchTime
        );
        $crawler->crawl();
        $this->assertEquals(0, self::$queueClient->count($_ENV['QUEUE_CREATE_POST']));
        $this->assertEquals(1, self::$queueClient->count($_ENV['QUEUE_UPDATE_POST']));
        $this->assertEquals(1, self::$feedCol->count());
        $this->assertEquals(1, self::$feedTimestampCol->count());
    }
}
