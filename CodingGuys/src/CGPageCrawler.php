<?php
/**
 * User: kit
 * Date: 13/1/2016
 * Time: 11:43
 */

namespace CodingGuys;

use CodingGuys\Document\FacebookPage;
use Facebook\Exceptions\FacebookResponseException;


// TODO migrate query to FacebookPageRepo
class CGPageCrawler extends CGFbCrawler
{
    const FAIL = "fail";
    const SUCCESS = "success";
    const PAGE_FIELDS = '?fields=id,about,affiliation,app_id,app_links,artists_we_like,attire,awards,band_interests,band_members,best_page,bio,birthday,booking_agent,built,business,can_checkin,can_post,category,category_list,checkins,company_overview,contact_address,country_page_likes,cover,culinary_team,current_location,description,description_html,directed_by,display_subtext,displayed_message_response_time,emails,engagement,fan_count,featured_video,features,food_styles,founded,general_info,general_manager,genre,global_brand_page_name,global_brand_root_id,has_added_app,hometown,hours,impressum,influences,is_always_open,is_community_page,is_permanently_closed,is_published,is_unclaimed,is_verified,is_webhooks_subscribed,last_used_time,leadgen_tos_accepted,link,location,members,mission,mpg,name,name_with_location_descriptor,network,new_like_count,offer_eligible,parent_page,parking,payment_options,personal_info,personal_interests,pharma_safety_info,phone,place_type,plot_outline,press_contact,price_range,produced_by,products,promotion_ineligible_reason,public_transit,publisher_space,record_label,release_date,restaurant_services,restaurant_specialties,schedule,screenplay_by,season,single_line_address,starring,start_info,store_location_descriptor,store_number,studio,talking_about_count,unread_message_count,unread_notif_count,unseen_message_count,username,verification_status,voip_info,website,were_here_count,written_by';

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
        $requestEndPoint = '/' . $pageFbId;
        $requestEndPoint .= CGPageCrawler::PAGE_FIELDS;
        $headerMsg = "get error while crawling page:" . $pageFbId;
        $response = $this->tryRequest($requestEndPoint, $headerMsg);
        if ($response == null)
        {
            return CGPageCrawler::FAIL;
        }
        $pageMainContent = $response->getDecodedBody();
        $pageMainContent["fbID"] = $pageMainContent["id"];
        unset($pageMainContent["id"]);

        $page = new FacebookPage();
        $page->setFbResponse($pageMainContent);
        $page->setMnemono(array(
            "category" => $category,
            "location" => array("city" => $city, "country" => $country),
            "crawlTime" => $crawlTime,
        ));

        $this->getFbDM()->writeToDB($page);

        return CGPageCrawler::SUCCESS;
    }

    /**
     * @param \MongoDB\BSON\ObjectID $id
     * @param string $category
     * @param string $city
     * @param string $country
     * @param array $crawlTime
     * @return string
     */
    public function reCrawlData(\MongoDB\BSON\ObjectID $id, $category, $city, $country, $crawlTime)
    {
        $repo = $this->getFbPageRepo();
        $raw = $repo->findOneById($id);
        if ($raw === null)
        {
            throw new \UnexpectedValueException();
        }
        $fbPage = new FacebookPage($raw);

        $requestEndPoint = '/' . $fbPage->getFbID() ;
        $requestEndPoint .= CGPageCrawler::PAGE_FIELDS;
        $headerMsg = "get error while crawling page:" . $fbPage->getFbID();
        $response = $this->tryRequest($requestEndPoint, $headerMsg);
        if ($response == null)
        {
            return CGPageCrawler::FAIL;
        }
        $pageMainContent = $response->getDecodedBody();
        $pageMainContent["fbID"] = $pageMainContent["id"];
        unset($pageMainContent["id"]);

        $fbPage->setException(false);
        $fbPage->setError(null);
        $fbPage->setFbResponse($pageMainContent);
        $fbPage->setMnemono(array(
            "category" => $category,
            "location" => array("city" => $city, "country" => $country),
            "crawlTime" => $crawlTime,
        ));
        $this->getFbDM()->writeToDB($fbPage);
        return CGPageCrawler::SUCCESS;
    }

    /**
     * @param $fbId
     * @return \MongoDB\BSON\ObjectID|null
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
     * @param FacebookResponseException $e
     * @param array $page the page record fetch from mongoDB;
     */
    private function handleErrorPage(FacebookResponseException $e, $page)
    {
        //TODO move error handling to FeedCrawler
        echo $e->getRawResponse() . "\n";
        $errorMsg = json_decode($e->getResponse()->getDecodedBody());
        $code = $errorMsg['error']['code'];
        $hit = preg_match("/Page ID (.+) was migrated to page ID (.+)\\./", $errorMsg->error->message, $matches);
        if ($code == 21 && $hit > 0)
        {
            $newID = $matches[2];
            $this->handleMigration($page, $newID);
        }
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
     * @param $fbId
     * @return array|null
     */
    private function getDBPageValue($fbId)
    {
        return $this->getFbPageRepo()->findOneByFbId($fbId);
    }

    /**
     * @return array|null
     */
    private function crawlProfilePicture($pageFbId)
    {
        $requestEndPoint = '/' . $pageFbId . '/picture?type=large&redirect=false';
        $headerMsg = "get error while crawling page profile picture:" . $pageFbId;
        $pictureResponse = $this->tryRequest($requestEndPoint, $headerMsg);
        if ($pictureResponse == null)
        {
            return null;
        }
        $pageProfilePicture = $pictureResponse->getDecodedBody();
        return $pageProfilePicture['data'];
    }


}