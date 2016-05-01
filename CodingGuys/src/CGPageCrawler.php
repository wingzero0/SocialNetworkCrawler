<?php
/**
 * User: kit
 * Date: 13/1/2016
 * Time: 11:43
 */

namespace CodingGuys;

use CodingGuys\MongoFb\CGMongoFb;
use Facebook\FacebookRequest;
use Facebook\FacebookResponse;
use Facebook\FacebookRequestException;
use Facebook\FacebookThrottleException;

class CGPageCrawler extends CGFbCrawler
{
    /**
     * @param string $pageFbId
     * @param string $category
     * @param string $city
     * @param string $country
     * @param array $crawlTime
     * @return array|null
     */
    public function crawlNewPage($pageFbId, $category, $city, $country, $crawlTime)
    {
        $request = new FacebookRequest($this->getFbSession(), 'GET', '/' . $pageFbId);
        $headerMsg = "get error while crawling page:" . $pageFbId;
        $response = $this->tryRequest($request, $headerMsg);
        if ($response == null)
        {
            return "fail";
        }
        $pageMainContent = $response->getResponse();
        $pageMainContent->fbID = $pageMainContent->id;
        unset($pageMainContent->id);

        $pageMainContent->mnemono = array(
            "category" => $category,
            "location" => array("city" => $city, "country" => $country),
            "crawlTime" => $crawlTime,
        );

        $this->insert($pageMainContent);

        return "success";
    }

    /**
     * @param \MongoId $id
     * @param string $category
     * @param string $city
     * @param string $country
     * @param array $crawlTime
     */
    public function updateMeta(\MongoId $id, $category, $city, $country, $crawlTime)
    {
        $col = $this->getPageCollection();
        $col->update(array("_id" => $id), array("\$set" =>
            array(
                "mnemono" => array(
                    "category" => $category,
                    "location" => array("city" => $city, "country" => $country),
                    "crawlTime" => $crawlTime,
                )
            )
        ));
    }

    public function updateExistingPages()
    {
        $cursor = $this->getPageCollection()->find(array("\$or" => array(
            array("exception" => array("\$exists" => false)),
            array("exception" => false),
        )));
        $pageArray = array();
        foreach ($cursor as $page)
        {
            $pageArray[] = $page;
        }
        $i = 0;
        foreach ($pageArray as $page)
        {
            $i++;
            if ($i % 100 == 0)
            {
                echo $i . "\n";
            }
            $this->updateExistingPage($page);
        }
    }


    /**
     * @param $fbId
     * @return \MongoId|null
     */
    public function getFbMongoId($fbId)
    {
        $page = $this->getDBPageValue($fbId);
        if ($page)
        {
            return $page["_id"];
        } else
        {
            return null;
        }
    }

    /**
     * @param array $page the page record fetch from mongoDB;
     * @return string
     */
    private function updateExistingPage($page)
    {
        $pageFbId = $page["fbID"];
        $request = new FacebookRequest($this->getFbSession(), 'GET', '/' . $pageFbId);
        $headerMsg = "get error while crawling page:" . $pageFbId;
        $response = $this->tryUpdateRequest($request, $headerMsg, $page);
        if ($response == null)
        {
            return "fail";
        }
        $responseData = $response->getResponse();
        $responseData->fbID = $responseData->id;

        if (isset($page["mnemono"]))
        {
            $responseData->mnemono = $page["mnemono"];
        } else
        {
            echo "mnemono fields do not exist in: ";
            var_dump($page);
        }
        unset($responseData->id);

        $this->getPageCollection()->update(array("fbID" => $page["fbID"]), $responseData);
        return "success";
    }

    /**
     * @param FacebookRequest $request
     * @param string $headerMessage message that will be dump to stderr if exception occurs
     * @param array $page the page record fetch from mongoDB;
     * @return FacebookResponse|null
     *
     * @TODO catch api limit exception, send mail to user and sleep a long time.
     */
    private function tryUpdateRequest(FacebookRequest $request, $headerMessage, $page)
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
                $response = null;
                $this->handleErrorPage($e, $page);
                break;
            } catch (\Exception $e)
            {
                $this->dumpErr($e, $headerMessage);
                $response = null;
                break;
            }
        } while ($response == null && $counter < 2);

        return $response;
    }

    /**
     * @param FacebookRequestException $e
     * @param array $page the page record fetch from mongoDB;
     */
    private function handleErrorPage(FacebookRequestException $e, $page)
    {
        echo $e->getRawResponse() . "\n";
        $errorMsg = json_decode($e->getRawResponse());
        $code = $errorMsg->error->code;
        $hit = preg_match("/Page ID (.+) was migrated to page ID (.+)\\./", $errorMsg->error->message, $matches);
        if ($code == 21 && $hit > 0)
        {
            $newID = $matches[2];
            $this->handleMigration($page, $newID);
        }
        $this->setPageAsException($page["fbID"]);
        $page["error"] = $errorMsg->error;
        $this->backupExceptionPage($page);
    }

    private function handleMigration($oldPage, $newID)
    {
        if ($this->getDBPageValue($newID) == null)
        {
            $category = $oldPage["mnemono"]["category"];
            $city = $oldPage["mnemono"]["location"]["city"];
            $country = $oldPage["mnemono"]["location"]["country"];
            $crawlTime = $oldPage["mnemono"]["crawlTime"];
            $this->crawlNewPage($newID, $category, $city, $country, $crawlTime);
        } else
        {
            // Let it go
        }
    }

    /**
     * @param string $fbID
     */
    private function setPageAsException($fbID)
    {
        $this->getPageCollection()->update(
            array("fbID" => $fbID),
            array("exception" => true, "fbID" => $fbID)
        );
    }

    /**
     * @param array $page the page record fetch from mongoDB;
     */
    private function backupExceptionPage($page)
    {
        echo "backup " . $page["fbID"] . "\n";
        $this->getExceptionPageCollection()->update(array("_id" => $page["_id"]), $page, array("upsert" => true));
    }

    /**
     * @param $fbId
     * @return array|null
     */
    private function getDBPageValue($fbId)
    {
        $cursor = $this->getPageCollection()->find(array("fbID" => $fbId));
        if ($cursor->hasNext())
        {
            $data = $cursor->getNext();
            return $data;
        } else
        {
            return null;
        }
    }

    private function insert($data)
    {
        $this->getPageCollection()->insert($data);
    }

    /**
     * @return array|null
     */
    private function crawlProfilePicture($pageFbId)
    {
        $request = new FacebookRequest($this->getFbSession(), 'GET', '/' . $pageFbId . '/picture?type=large&redirect=false');
        $headerMsg = "get error while crawling page profile picture:" . $pageFbId;
        $pictureResponse = $this->tryRequest($request, $headerMsg);
        if ($pictureResponse == null)
        {
            return null;
        }
        $pageProfilePicture = $pictureResponse->getResponse();
        return $pageProfilePicture->data;
    }


}