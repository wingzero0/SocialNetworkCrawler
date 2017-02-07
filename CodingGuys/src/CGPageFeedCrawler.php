<?php
/**
 * User: kit
 * Date: 17/03/15
 * Time: 20:30
 */

namespace CodingGuys;

use CodingGuys\Document\FacebookExceptionPage;
use CodingGuys\Document\FacebookFeed;
use CodingGuys\Document\FacebookFeedTimestamp;
use CodingGuys\Document\FacebookPage;
use CodingGuys\Document\FacebookPageTimestamp;
use CodingGuys\FbRepo\FbFeedRepo;
use CodingGuys\Utility\DateUtility;
use Facebook\Exceptions\FacebookResponseException;

class CGPageFeedCrawler extends CGFbCrawler
{
    private $pageFbId;
    private $pageMongoId;
    private $batchTime;

    const FAIL = "fail";
    const SUCCESS = "success";
    const PAGE_FIELDS = '?fields=id,about,affiliation,app_id,app_links,artists_we_like,attire,awards,band_interests,band_members,best_page,bio,birthday,booking_agent,built,business,can_checkin,can_post,category,category_list,checkins,company_overview,contact_address,country_page_likes,cover,culinary_team,current_location,description,description_html,directed_by,display_subtext,displayed_message_response_time,emails,engagement,fan_count,featured_video,features,food_styles,founded,general_info,general_manager,genre,global_brand_page_name,global_brand_root_id,has_added_app,hometown,hours,impressum,influences,is_always_open,is_community_page,is_permanently_closed,is_published,is_unclaimed,is_verified,is_webhooks_subscribed,last_used_time,leadgen_tos_accepted,link,location,members,mission,mpg,name,name_with_location_descriptor,network,new_like_count,offer_eligible,parent_page,parking,payment_options,personal_info,personal_interests,pharma_safety_info,phone,place_type,plot_outline,press_contact,price_range,produced_by,products,promotion_ineligible_reason,public_transit,publisher_space,record_label,release_date,restaurant_services,restaurant_specialties,schedule,screenplay_by,season,single_line_address,starring,start_info,store_location_descriptor,store_number,studio,talking_about_count,unread_message_count,unread_notif_count,unseen_message_count,username,verification_status,voip_info,website,were_here_count,written_by';
    const FEED_FIELDS = '?fields=id,admin_creator,application,call_to_action,caption,created_time,description,feed_targeting,from,icon,instagram_eligibility,is_hidden,is_instagram_eligible,is_published,link,message,message_tags,name,object_id,parent_id,picture,place,privacy,properties,shares,source,status_type,story,story_tags,targeting,to,type,updated_time,with_tags,likes.limit(5).summary(true),comments.limit(5).summary(true),attachments';

    /**
     * @param string $pageFbId
     * @param \MongoDB\BSON\ObjectID $pageMongoId
     * @param \MongoDB\BSON\UTCDateTime $batchTime
     * @param string $appId
     * @param string $appSecret
     */
    public function __construct($pageFbId, $pageMongoId, \MongoDB\BSON\UTCDateTime $batchTime, $appId, $appSecret)
    {
        parent::__construct($appId, $appSecret);
        $this->pageFbId = $pageFbId;
        $this->pageMongoId = $pageMongoId;
        $this->batchTime = $batchTime;
    }

    /**
     * @return string CGFeedCrawler::FAIL|CGFeedCrawler::SUCCESS
     */
    public function crawl()
    {
        $ret = $this->crawlPage();
        if ($ret == CGPageFeedCrawler::FAIL)
        {
            return CGPageFeedCrawler::FAIL;
        }
        $ret = $this->crawlFeed();
        if ($ret == CGPageFeedCrawler::FAIL)
        {
            return CGPageFeedCrawler::FAIL;
        }
        return CGPageFeedCrawler::SUCCESS;
    }

    private function crawlPage()
    {
        $requestEndPoint = '/' . $this->pageFbId;
        $requestEndPoint .= CGPageFeedCrawler::PAGE_FIELDS;
        $headerMsg = "get error while crawling page:" . $this->pageFbId;
        $response = $this->tryRequest($requestEndPoint, $headerMsg);
        if ($response == null)
        {
            $this->handleErrorPage($this->getLastException(), $this->pageFbId);
            return CGPageFeedCrawler::FAIL;
        }
        $this->findAndModifyPage($this->pageFbId, $response->getDecodedBody());
        return CGPageFeedCrawler::SUCCESS;
    }

    private function crawlFeed()
    {
        $requestEndPoint = '/' . $this->pageFbId . '/posts' ;
        $headerMsg = "get error while crawling page:" . $this->pageFbId;
        $response = $this->tryRequest($requestEndPoint, $headerMsg);
        if ($response == null)
        {
            return CGPageFeedCrawler::FAIL;
        }

        $responseData = $response->getDecodedBody();

        foreach ($responseData["data"] as $i => $feed)
        {
            $this->findAndModifyFeed($feed["id"]);
        }
        return CGPageFeedCrawler::SUCCESS;
    }

    /**
     * @param string $oldFbId
     * @param array $newPage
     */
    private function findAndModifyPage($oldFbId, $newPage)
    {
        $oldPage = $this->queryOldPage($oldFbId);
        $newPage['fbID'] = $newPage['id'];
        unset($newPage['id']);

        $fbObj = new FacebookPage($oldPage);
        $fbObj->setFbResponse($newPage);
        $this->getFbDM()->writeToDB($fbObj);
        // TODO handle duplicated fbID because of migration / changing fans page

        $different = $this->checkPageDiff($oldPage, $newPage);
        if ($different)
        {
            $this->createPageTimestamp($newPage);
        }
    }

    /**
     * @param string $oldFbId
     * @return array one of mongo result record
     * @throws \Exception
     */
    private function queryOldPage($oldFbId)
    {
        $repo = $this->getFbPageRepo();
        $oldPage = $repo->findOneByFbId($oldFbId);
        if ($oldPage == null)
        {
            $e = new \Exception("unknown page fb id:" . $oldFbId);
            $this->dumpErr($e, "find and modify page fb id:" . $oldFbId);
            throw $e;
        }
        return $oldPage;
    }

    /**
     * @param array $oldPage
     * @param array $newPage
     * @return bool
     */
    private function checkPageDiff($oldPage, $newPage)
    {
        $oldValue = (isset($oldPage["were_here_count"]) ? $oldPage["were_here_count"] : 0);
        $newValue = (isset($newPage["were_here_count"]) ? $newPage["were_here_count"] : 0);
        if ($oldValue != $newValue)
        {
            return true;
        }

        $oldValue = (isset($oldPage["talking_about_count"]) ? $oldPage["talking_about_count"] : 0);
        $newValue = (isset($newPage["talking_about_count"]) ? $newPage["talking_about_count"] : 0);
        if ($oldValue != $newValue)
        {
            return true;
        }

        $oldValue = (isset($oldPage["fan_count"]) ? $oldPage["fan_count"] : 0);
        $newValue = (isset($newPage["fan_count"]) ? $newPage["fan_count"] : 0);
        if ($oldValue != $newValue)
        {
            return true;
        }

        return false;
    }

    /**
     * @param array $page
     */
    private function createPageTimestamp($page)
    {
        $doc = new FacebookPageTimestamp();

        if (isset($page["were_here_count"]))
        {
            $doc->setWereHereCount($page["were_here_count"]);
        } else
        {
            $doc->setWereHereCount(0);
        }

        if (isset($page["talking_about_count"]))
        {
            $doc->setTalkingAboutCount($page["talking_about_count"]);
        } else
        {
            $doc->setTalkingAboutCount(0);
        }

        if (isset($page["fan_count"]))
        {
            $doc->setFanCount($page["fan_count"]);
        } else
        {
            $doc->setFanCount(0);
        }

        $doc->setFbPage($this->getFbDM()->createPageRef($this->pageMongoId));
        $doc->setUpdateTime(DateUtility::getCurrentMongoDate());
        $doc->setBatchTime($this->batchTime);

        $this->getFbDM()->writeToDB($doc);
    }

    /**
     * @param string $feedFbId
     */
    private function findAndModifyFeed($feedFbId)
    {
        $newDoc = new FacebookFeed();

        $extraInfo = $this->queryFeedExtraInfo($feedFbId);
        if (empty($extraInfo))
        {
            return;
        }
        unset($extraInfo["id"]);
        $newDoc->setFbResponse($extraInfo);
        $newDoc->setFbId($feedFbId);
        $newDoc->setFbPage($this->getFbDM()->createPageRef($this->pageMongoId));

        $oldFeed = $this->getFbDM()->upsertDB($newDoc, array("fbID" => $newDoc->getFbId()));
        if (empty($oldFeed))
        {
            $this->syncFeed($newDoc->getFbId(), true);
            // TODO get new Doc Mongo ID from db operation?
            $this->createFeedTimestamp($newDoc);
        } else
        {
            $oldDoc = new FacebookFeed(new \ArrayObject($oldFeed));
            $different = $this->checkFeedDiff($oldDoc, $newDoc);
            if ($different)
            {
                $this->syncFeed($newDoc->getFbId(), false);
                $this->createFeedTimestamp($newDoc, $oldDoc->getId());
            }
        }
    }

    private function checkFeedDiff(FacebookFeed $oldDoc, FacebookFeed $newDoc)
    {
        $diff = $this->compareCountAttr($oldDoc->getLikes(), $newDoc->getLikes());
        if ($diff)
        {
            return true;
        }

        $diff = $this->compareCountAttr($oldDoc->getComments(), $newDoc->getComments());
        if ($diff)
        {
            return true;
        }

        $diff = $this->compareCountAttr($oldDoc->getShares(), $newDoc->getShares());
        if ($diff)
        {
            return true;
        }

        return false;
    }

    /**
     * @param array $oldFeedAttr
     * @param array $newFeedAttr
     * @return bool
     */
    private function compareCountAttr($oldFeedAttr, $newFeedAttr)
    {
        $oldFeedTotalCount = 0;
        if (is_object($oldFeedAttr))
        {
            $oldFeedAttr = json_decode(json_encode($oldFeedAttr), true);
        }
        if (is_array($oldFeedAttr))
        {
            if (isset($oldFeedAttr["summary"]) && isset($oldFeedAttr["summary"]["total_count"]))
            {
                $oldFeedTotalCount = $oldFeedAttr["summary"]["total_count"];
            } else if (isset($oldFeedAttr["count"]))
            {
                $oldFeedTotalCount = $oldFeedAttr["count"];
            }
        }

        $newFeedTotalCount = 0;
        if (is_object($newFeedAttr))
        {
            $newFeedAttr = json_decode(json_encode($newFeedAttr), true);
        }
        if (is_array($newFeedAttr))
        {
            if (isset($newFeedAttr["summary"]) && isset($newFeedAttr["summary"]["total_count"]))
            {
                $newFeedTotalCount = $newFeedAttr["summary"]["total_count"];
            } else if (isset($newFeedAttr["count"]))
            {
                $newFeedTotalCount = $newFeedAttr["count"];
            }
        }

        if ($newFeedTotalCount != $oldFeedTotalCount)
        {
            return true;
        }
        return false;
    }

    private function syncFeed($fbId, $createdFlag = true)
    {
        // Create our client object
        $client = new \GearmanClient();

        // Add a server
        $client->addServer(); // by default host/port will be "localhost" & 4730
        $workload = json_encode(array("fbId" => $fbId));

        if ($createdFlag)
        {
            $job_handle = $client->doBackground("MnemonoBackgroundServiceBundleServicesSyncFbFeedService~createPost", $workload);
        } else
        {
            $job_handle = $client->doBackground("MnemonoBackgroundServiceBundleServicesSyncFbFeedService~updatePost", $workload);
        }
    }

    /**
     * Query feed's likes and comment total count
     * @param string $fbID
     * @return array extraInfo
     */
    private function queryFeedExtraInfo($fbID)
    {
        $requestEndPoint = '/' . $fbID . '';
        $requestEndPoint .= CGPageFeedCrawler::FEED_FIELDS;
        $headerMsg = "get error while crawling feed:" . $fbID;
        $response = $this->tryRequest($requestEndPoint, $headerMsg);
        if ($response == null)
        {
            return array();
        }

        return $response->getDecodedBody();
    }

    /**
     * @param FacebookFeed $feedObj
     * @param \MongoDB\BSON\ObjectID $feedMongoId
     * @return FacebookFeedTimestamp
     */
    private function createFeedTimestamp($feedObj, $feedMongoId = null)
    {
        $timestamp = new FacebookFeedTimestamp();
        $likes = $feedObj->getLikes();
        if (!empty($likes) && isset($likes["summary"]["total_count"]))
        {
            $timestamp->setLikesTotalCount($likes["summary"]["total_count"]);
        }

        $comments = $feedObj->getComments();
        if (!empty($comments) && isset($comments["summary"]["total_count"]))
        {
            $timestamp->setCommentsTotalCount($comments["summary"]["total_count"]);
        }

        $shares = $feedObj->getShares();
        if (!empty($shares) && isset($shares["count"]))
        {
            $timestamp->setShareTotalCount($shares["count"]);
        }

        $fbDM = $this->getFbDM();
        $timestamp->setFbPage($fbDM->createPageRef($this->pageMongoId));
        if (!($feedMongoId instanceof \MongoDB\BSON\ObjectID))
        {
            $feedMongoId = $this->getFeedMongoId($feedObj->getFbId());
        }
        $timestamp->setFbFeed($fbDM->createFeedRef($feedMongoId));
        $timestamp->setUpdateTime(DateUtility::getCurrentMongoDate());
        $timestamp->setBatchTime($this->batchTime);
        $fbDM->writeToDB($timestamp);

        return $timestamp;
    }

    /**
     * @param string $fbID
     * @return \MongoDB\BSON\ObjectID|null
     */
    private function getFeedMongoId($fbID)
    {
        $repo = new FbFeedRepo($this->getFbDM());
        $feed = $repo->findOneByFbId($fbID);
        if (isset($feed["_id"]))
        {
            return $feed["_id"];
        }
        return null;
    }

    /**
     * @param \Exception $e
     * @param string $pageFbId
     */
    private function handleErrorPage(\Exception $e, $pageFbId)
    {
        $pageRaw = $this->getFbPageRepo()->findOneByFbId($pageFbId);
        $errPage = new FacebookExceptionPage($pageRaw);
        $errPage->setException(true);
        $errPage->setExceptionTime(DateUtility::getCurrentMongoDate());
        $errPage->setId(null);

        if ($e instanceof FacebookResponseException)
        {
            // TODO create test case for it, confirm exception type
            $errorResponse = $e->getResponseData();
            $code = $errorResponse["error"]["code"];
            $hit = preg_match("/Page ID (.+) was migrated to page ID (.+)\\./", $errorResponse["error"]["message"], $matches);

            $oldPage = new FacebookPage($pageRaw);
            if ($code == 21 && $hit > 0)
            {
                $newID = $matches[2];
                $this->migratePage($oldPage, $newID);
                $this->markAsException($oldPage, $errorResponse["error"]);
            } else if ($code == 100)
            {
                $this->markAsException($oldPage, $errorResponse["error"]);
            }
            $errPage->setError($errorResponse["error"]);
        } else
        {
            $errPage->setError(array("message" => $e->getMessage(), "trace" => $e->getTraceAsString()));
        }
        $this->getFbDM()->writeToDB($errPage);
    }

    /**
     * @param FacebookPage $oldPage
     * @param array $error
     */
    private function markAsException(FacebookPage $oldPage, $error)
    {
        $oldPage->setError($error);
        $oldPage->setException(true);
        $this->getFbDM()->writeToDB($oldPage);
    }

    /**
     * @param FacebookPage $oldPage
     * @param string $newPageFbId
     * @return string
     */
    private function migratePage(FacebookPage $oldPage, $newPageFbId)
    {
        if ($this->getFbPageRepo()->findOneByFbId($newPageFbId) === null)
        {
            $requestEndPoint = '/' . $newPageFbId;
            $requestEndPoint .= CGPageFeedCrawler::PAGE_FIELDS;
            $headerMsg = "get error while migrating page:" . $newPageFbId;
            $response = $this->tryRequest($requestEndPoint, $headerMsg);
            if ($response == null)
            {
                return CGPageCrawler::FAIL;
            }
            $pageMainContent = $response->getDecodedBody();
            $pageMainContent["fbID"] = $pageMainContent["id"];
            unset($pageMainContent["id"]);

            $mnemono = $oldPage->getMnemono();
            $page = new FacebookPage();
            $page->setFbResponse($pageMainContent);
            $page->setMnemono($mnemono);
            $this->getFbDM()->writeToDB($page);

            $client = new \GearmanClient();
            $client->addServer();
            $workload = json_encode(array("fbId" => $newPageFbId));
            $job_handle = $client->doBackground("MnemonoBackgroundServiceBundleServicesSyncFbPageService~createBiz", $workload);
        }
        return CGPageCrawler::SUCCESS;
    }

}