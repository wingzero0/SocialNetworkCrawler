<?php
/**
 * User: kit
 * Date: 13/1/2016
 * Time: 11:59
 */

namespace CodingGuys;

use CodingGuys\FbDocumentManager\FbDocumentManager;
use CodingGuys\FbRepo\FbPageRepo;
use Facebook\FacebookRequest;
use Facebook\FacebookResponse;
use Facebook\FacebookSession;
use Facebook\FacebookRequestException;
use Facebook\FacebookThrottleException;

class CGFbCrawler
{
    private $mongFb;
    private $fbSession;
    private $fbDM;

    public function __construct($appId, $appSecret)
    {
        FacebookSession::setDefaultApplication($appId, $appSecret);
        $this->setFbSession(FacebookSession::newAppSession());
        $this->setFbDM(new FbDocumentManager());
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