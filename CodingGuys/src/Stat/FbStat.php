<?php
/**
 * User: kit
 * Date: 30/04/2016
 * Time: 4:54 PM
 */

namespace CodingGuys\Stat;

use CodingGuys\FbRepo\FbPageTimestampRepo;
use CodingGuys\MongoFb\CGMongoFb;
use CodingGuys\FbRepo\FbPageRepo;
use CodingGuys\FbRepo\FbFeedRepo;
use CodingGuys\FbDocumentManager\FbDocumentManager;

class FbStat
{
    protected $STDERR;

    private $mongoFb;
    private $pageRepo;
    private $feedRepo;
    private $fbDm;
    private $pageTimestampRepo;

    public function __construct()
    {
        $this->STDERR = fopen('php://stderr', 'w+');
    }

    /**
     * @param $colName
     * @return \MongoCollection
     */
    protected function getMongoCollection($colName)
    {
        return $this->getMongoFb()->getMongoCollection($colName);
    }

    /**
     * @return \MongoDB
     */
    protected function getMongoDB()
    {
        return $this->getMongoFb()->getMongoDB();
    }

    /**
     * @return \MongoClient
     */
    protected function getMongoClient()
    {
        return $this->getMongoFb()->getMongoClient();
    }

    /**
     * @return CGMongoFb
     * @deprecated
     */
    protected function getMongoFb()
    {
        if ($this->mongoFb == null)
        {
            $this->mongoFb = new CGMongoFb();
        }
        return $this->mongoFb;
    }

    /**
     * @return \MongoCollection
     * @deprecated
     */
    protected function getFbFeedCol()
    {
        return $this->getMongoCollection($this->getMongoFb()->getFeedCollectionName());
    }

    /**
     * @return \MongoCollection
     */
    protected function getFbFeedTimestampCol()
    {
        return $this->getMongoCollection($this->getMongoFb()->getFeedTimestampCollectionName());
    }


    /**
     * @return FbPageRepo
     */
    protected function getPageRepo()
    {
        if ($this->pageRepo == null)
        {
            $this->pageRepo = new FbPageRepo($this->getFbDocumentManager());
        }
        return $this->pageRepo;
    }

    /**
     * @return FbFeedRepo
     */
    protected function getFeedRepo()
    {
        if ($this->feedRepo == null)
        {
            $this->feedRepo = new FbFeedRepo();
        }
        return $this->feedRepo;
    }

    protected function getPageTimestampRepo()
    {
        if ($this->pageTimestampRepo == null)
        {
            $this->pageTimestampRepo = new FbPageTimestampRepo($this->getFbDocumentManager());
        }
        return $this->pageTimestampRepo;
    }


    /**
     * @return FbDocumentManager
     */
    protected function getFbDocumentManager()
    {
        if ($this->fbDm == null)
        {
            $this->fbDm = new FbDocumentManager();
        }
        return $this->fbDm;
    }
}