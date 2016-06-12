<?php
/**
 * User: kit
 * Date: 30/04/2016
 * Time: 4:54 PM
 */

namespace CodingGuys\Stat;

use CodingGuys\FbRepo\FbFeedTimestampRepo;
use CodingGuys\FbRepo\FbPageTimestampRepo;
use CodingGuys\FbRepo\FbPageRepo;
use CodingGuys\FbRepo\FbFeedRepo;
use CodingGuys\FbDocumentManager\FbDocumentManager;

class FbStat
{
    protected $STDERR;

    private $pageRepo;
    private $feedRepo;
    private $fbDm;
    private $pageTimestampRepo;
    private $feedTimestampRepo;

    public function __construct()
    {
        $this->STDERR = fopen('php://stderr', 'w+');
    }

    /**
     * @return \MongoDB
     */
    protected function getMongoDB()
    {
        return $this->getFbDocumentManager()->getMongoDB();
    }

    /**
     * @return \MongoCollection
     */
    protected function getFbFeedCol()
    {
        return $this->getFbDocumentManager()->getFeedCollection();
    }

    /**
     * @return \MongoCollection
     */
    protected function getFbFeedTimestampCol()
    {
        return $this->getFbDocumentManager()->getFeedTimestampCollection();
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

    /**
     * @return FbPageTimestampRepo
     */
    protected function getPageTimestampRepo()
    {
        if ($this->pageTimestampRepo == null)
        {
            $this->pageTimestampRepo = new FbPageTimestampRepo($this->getFbDocumentManager());
        }
        return $this->pageTimestampRepo;
    }

    /**
     * @return FbFeedTimestampRepo
     */
    protected function getFeedTimestampRepo()
    {
        if ($this->feedTimestampRepo == null)
        {
            $this->feedTimestampRepo = new FbFeedTimestampRepo($this->getFbDocumentManager());
        }
        return $this->feedTimestampRepo;
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