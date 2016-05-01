<?php
/**
 * User: kit
 * Date: 13/1/2016
 * Time: 11:59
 */

namespace CodingGuys;

use CodingGuys\MongoFb\CGMongoFb;
use Facebook\FacebookRequest;
use Facebook\FacebookResponse;
use Facebook\FacebookSession;
use Facebook\FacebookRequestException;
use Facebook\FacebookThrottleException;

class CGFbCrawler
{
    private $mongFb;
    private $fbSession;

    public function __construct($appId, $appSecret)
    {
        $this->setMongFb(new CGMongoFb());
        FacebookSession::setDefaultApplication($appId, $appSecret);
        $this->setFbSession(FacebookSession::newAppSession());
    }

    /**
     * @param FacebookRequest $request
     * @param string $headerMessage message that will be dump to stderr if exception occurs
     * @return FacebookResponse|null
     *
     * @TODO catch api limit exception, send mail to user and sleep a long time.
     */
    protected function tryRequest(FacebookRequest $request, $headerMessage)
    {
        $response = null;
        $counter = 0;
        do
        {
            $counter++;
            try
            {
                $response = $request->execute();
            } catch (FacebookThrottleException $e)
            {
                $this->dumpErr($e, $headerMessage);
                $response = null;
                sleep(600);
            } catch (FacebookRequestException $e)
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
        if ($e instanceof FacebookRequestException)
        {
            fprintf($stderr, $e->getRawResponse() . "\n");
        } else
        {
            fprintf($stderr, $e->getMessage() . "\n");
        }
        fclose($stderr);
    }

    /**
     * @return FacebookSession
     */
    protected function getFbSession()
    {
        return $this->fbSession;
    }

    /**
     * @param FacebookSession $fbSession
     */
    private function setFbSession($fbSession)
    {
        $this->fbSession = $fbSession;
    }

    /**
     * @return CGMongoFb
     */
    protected function getMongFb()
    {
        return $this->mongFb;
    }

    /**
     * @param CGMongoFb $mongFb
     */
    private function setMongFb($mongFb)
    {
        $this->mongFb = $mongFb;
    }

    /**
     * @return \MongoCollection
     */
    protected function getFeedCollection()
    {
        $feedCollectionName = $this->getMongFb()->getFeedCollectionName();
        return $this->getMongFb()->getMongoCollection($feedCollectionName);
    }

    /**
     * @return \MongoCollection
     */
    protected function getFeedTimestampCollection()
    {
        $feedTimestampCollectionName = $this->getMongFb()->getFeedTimestampCollectionName();
        return $this->getMongFb()->getMongoCollection($feedTimestampCollectionName);
    }

    /**
     * @return \MongoCollection
     */
    protected function getPageCollection()
    {
        $pageCollectionName = $this->getMongFb()->getPageCollectionName();
        return $this->getMongFb()->getMongoCollection($pageCollectionName);
    }

    /**
     * @return \MongoCollection
     */
    protected function getExceptionPageCollection()
    {
        $exceptionPageCollectionName = $this->getMongFb()->getExceptionPageCollectionName();
        return $this->getMongFb()->getMongoCollection($exceptionPageCollectionName);
    }
}