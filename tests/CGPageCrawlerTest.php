<?php

namespace CodingGuys\Tests;

use PHPUnit\Framework\TestCase;
use CodingGuys\CGPageCrawler;
use CodingGuys\FacebookSdk;
use CodingGuys\Document\FacebookPage;
use CodingGuys\FbDocumentManager\FbDocumentManager;

class CGPageCrawlerTest extends TestCase
{
    private static $queueClient;
    private static $pageCol;
    private static $pageTimestampCol;

    public static function setUpBeforeClass()
    {
        self::$queueClient = new FakeQueueClient();
        $dbClient = new \MongoDB\Client($_ENV['MONGODB_URI']);
        self::$pageCol = $dbClient->selectCollection(
            $_ENV['MONGODB_DATABASE'],
            'FacebookPage'
        );
        self::$pageCol->drop();
        self::$pageTimestampCol = $dbClient->selectCollection(
            $_ENV['MONGODB_DATABASE'],
            'FacebookPageTimestamp'
        );
        self::$pageTimestampCol->drop();
    }

    protected function tearDown()
    {
        self::$queueClient->reset();
        self::$pageCol->drop();
        self::$pageTimestampCol->drop();
    }

    public function testCrawlNewPage()
    {
        $page = require(__DIR__ . '/page.php');
        $pageFbId = $page['id'];
        $res = new FakeFacebookResponse($page);
        $fb = $this->createMock(FakeFacebookSdk::class);
        $fb->method('get')->willReturn($res);
        $pageMongoId = new \MongoDB\BSON\ObjectId();
        $batchTime = new \MongoDB\BSON\UTCDateTime();
        $crawler = new CGPageCrawler(
            $fb,
            self::$queueClient,
            $pageFbId,
            $batchTime
        );
        $pageFbId = $page['id'];
        $category = 'info';
        $city = 'hk';
        $country = 'cn';
        $crawlTime = [];
        $crawler->crawlNewPage(
            $pageFbId,
            $category,
            $city,
            $country,
            $crawlTime
        );
        $this->assertEquals(1, self::$queueClient->count($_ENV['QUEUE_CREATE_BIZ']));
        $this->assertEquals(0, self::$queueClient->count($_ENV['QUEUE_UPDATE_BIZ']));
        $this->assertEquals(1, self::$pageCol->count());
        $this->assertEquals(1, self::$pageTimestampCol->count());
    }

    public function testCrawlOldPageWithDiff()
    {
        $page = require(__DIR__ . '/page.php');
        $pageFbId = $page['id'];
        $pageCopy = array_merge([], $page);
        $pageObj = FacebookPage::constructByFbArray($pageCopy);
        $docMgr = new FbDocumentManager();
        $docMgr->writeToDB($pageObj);
        $page['fan_count']++;
        $fb = $this->createMock(FakeFacebookSdk::class);
        $res = new FakeFacebookResponse($page);
        $fb->method('get')->willReturn($res);
        $pageMongoId = new \MongoDB\BSON\ObjectId();
        $batchTime = new \MongoDB\BSON\UTCDateTime();
        $crawler = new CGPageCrawler(
            $fb,
            self::$queueClient,
            $pageFbId,
            $batchTime,
            $pageMongoId
        );
        $crawler->crawl();
        $this->assertEquals(0, self::$queueClient->count($_ENV['QUEUE_CREATE_BIZ']));
        $this->assertEquals(0, self::$queueClient->count($_ENV['QUEUE_UPDATE_BIZ']));
        $this->assertEquals(1, self::$pageCol->count());
        $this->assertEquals(1, self::$pageTimestampCol->count());
    }

    public function testCrawlOldPageWithoutDiff()
    {
        $page = require(__DIR__ . '/page.php');
        $pageFbId = $page['id'];
        $pageCopy = array_merge([], $page);
        $pageObj = FacebookPage::constructByFbArray($pageCopy);
        $docMgr = new FbDocumentManager();
        $docMgr->writeToDB($pageObj);
        $fb = $this->createMock(FakeFacebookSdk::class);
        $res = new FakeFacebookResponse($page);
        $fb->method('get')->willReturn($res);
        $pageMongoId = new \MongoDB\BSON\ObjectId();
        $batchTime = new \MongoDB\BSON\UTCDateTime();
        $crawler = new CGPageCrawler(
            $fb,
            self::$queueClient,
            $pageFbId,
            $batchTime,
            $pageMongoId
        );
        $crawler->crawl();
        $this->assertEquals(0, self::$queueClient->count($_ENV['QUEUE_CREATE_BIZ']));
        $this->assertEquals(0, self::$queueClient->count($_ENV['QUEUE_UPDATE_BIZ']));
        $this->assertEquals(1, self::$pageCol->count());
        $this->assertEquals(0, self::$pageTimestampCol->count());
    }

    public function testCrawlOldPageWithForcedSave()
    {
        $_ENV['PAGE_SNAPSHOT_FORCED_SAVE'] = true;
        $page = require(__DIR__ . '/page.php');
        $pageFbId = $page['id'];
        $pageCopy = array_merge([], $page);
        $pageObj = FacebookPage::constructByFbArray($pageCopy);
        $docMgr = new FbDocumentManager();
        $docMgr->writeToDB($pageObj);
        $fb = $this->createMock(FakeFacebookSdk::class);
        $res = new FakeFacebookResponse($page);
        $fb->method('get')->willReturn($res);
        $pageMongoId = new \MongoDB\BSON\ObjectId();
        $batchTime = new \MongoDB\BSON\UTCDateTime();
        $crawler = new CGPageCrawler(
            $fb,
            self::$queueClient,
            $pageFbId,
            $batchTime,
            $pageMongoId
        );
        $crawler->crawl();
        $this->assertEquals(0, self::$queueClient->count($_ENV['QUEUE_CREATE_BIZ']));
        $this->assertEquals(0, self::$queueClient->count($_ENV['QUEUE_UPDATE_BIZ']));
        $this->assertEquals(1, self::$pageCol->count());
        $this->assertEquals(1, self::$pageTimestampCol->count());
    }
}
