<?php
/**
 * User: kit
 * Date: 13/1/2016
 * Time: 11:59
 */

namespace CodingGuys;

use CodingGuys\FbDocumentManager\FbDocumentManager;
use CodingGuys\FbRepo\FbPageRepo;
use Facebook\Facebook as FacebookBase;
use Facebook\FacebookResponse;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookThrottleException;

class CGFbCrawler
{
    private $fbSession;
    private $fbAppBase;
    private $fbDM;
    private $lastException;

    public function __construct($appId, $appSecret)
    {
        $fb = new FacebookBase([
            'app_id' => $appId,
            'app_secret' => $appSecret,
            'default_graph_version' => 'v2.7',
        ]);
        $fb->setDefaultAccessToken($appId . '|' . $appSecret);
        $this->setFbAppBase($fb);
        $this->setFbDM(new FbDocumentManager());
    }

    /**
     * @return \Exception
     */
    public function getLastException()
    {
        return $this->lastException;
    }

    /**
     * @param \Exception $lastFbException
     */
    public function setLastException($lastFbException)
    {
        $this->lastException = $lastFbException;
    }

    /**
     * @return FacebookBase
     */
    protected function getFbAppBase()
    {
        return $this->fbAppBase;
    }

    /**
     * @param FacebookBase $fbAppBase
     */
    private function setFbAppBase(FacebookBase $fbAppBase)
    {
        $this->fbAppBase = $fbAppBase;
    }

    /**
     * @param string $requestEndPoint
     * @param string $headerMessage message that will be dump to stderr if exception occurs
     * @return FacebookResponse|null
     *
     * @TODO catch api limit exception, send mail to user and sleep a long time.
     */
    protected function tryRequest($requestEndPoint, $headerMessage)
    {
        $response = null;
        $counter = 0;
        do
        {
            $counter++;
            try
            {
                $response = $this->getFbAppBase()->get($requestEndPoint);
            } catch (FacebookThrottleException $e)
            {
                $this->dumpErr($e, $headerMessage);
                $response = null;
                sleep(600);
            } catch (FacebookResponseException $e)
            {
                $this->dumpErr($e, $headerMessage);
                $response = null;
                sleep(10);
            } catch (\Exception $e)
            {
                $this->dumpErr($e, $headerMessage);
                $response = null;
                break;
            }
        } while ($response == null && $counter < 2);

        return $response;
    }

    protected function dumpErr(\Exception $e, $headerMessage)
    {
        $stderr = fopen('php://stderr', 'w');
        $dateObj = new \DateTime();
        fprintf($stderr, $dateObj->format(\DateTime::ISO8601) . ": " . $headerMessage . "\n");
        if ($e instanceof FacebookResponseException)
        {
            fprintf($stderr, $e->getRawResponse() . "\n");
        } else
        {
            fprintf($stderr, $e->getMessage() . "\n");
        }
        $this->setLastException($e);
        fclose($stderr);
    }

    /**
     * @return FacebookSession
     */
    protected function getFbSession()
    {
        // TODO remove this method
        return $this->fbSession;
    }

    /**
     * @return \MongoCollection
     */
    protected function getFeedCollection()
    {
        return $this->getFbDM()->getFeedCollection();
    }

    /**
     * @return \MongoCollection
     */
    protected function getPageCollection()
    {
        return $this->getFbDM()->getPageCollection();
    }

    /**
     * @return \MongoCollection
     */
    protected function getExceptionPageCollection()
    {
        return $this->getFbDM()->getFacebookExceptionPageCollection();
    }

    /**
     * @return FbDocumentManager
     */
    protected function getFbDM()
    {
        return $this->fbDM;
    }

    /**
     * @param FbDocumentManager $fbDM
     */
    protected function setFbDM(FbDocumentManager $fbDM)
    {
        $this->fbDM = $fbDM;
    }

    /**
     * @return FbPageRepo
     */
    protected function getFbPageRepo()
    {
        return new FbPageRepo($this->getFbDM());
    }
}