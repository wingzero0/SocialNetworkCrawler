<?php
/**
 * User: kit
 * Date: 13/1/2016
 * Time: 11:59
 */

namespace CodingGuys;

use CodingGuys\FbDocumentManager\FbDocumentManager;
use CodingGuys\FbRepo\FbPageRepo;
use Facebook\FacebookResponse;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookThrottleException;
use MongoDB\Collection as MongoDBCollection;

class CGFbCrawler
{
    private $fbAppBase;
    private $fbDM;
    private $lastException;
    const FAIL = "fail";
    const SUCCESS = "success";
    const FEED_LIMIT = 25;
    const PAGE_FIELDS = [
        'id',
        'about',
        'affiliation',
        'app_id',
        'app_links',
        'artists_we_like',
        'attire',
        'awards',
        'band_interests',
        'band_members',
        'best_page',
        'bio',
        'birthday',
        'booking_agent',
        'built',
        'can_checkin',
        'can_post',
        'category',
        'category_list',
        'checkins',
        'company_overview',
        'contact_address',
        'country_page_likes',
        'cover',
        'culinary_team',
        'current_location',
        'description',
        'description_html',
        'directed_by',
        'display_subtext',
        'displayed_message_response_time',
        'emails',
        'engagement',
        'fan_count',
        'featured_video',
        'features',
        'food_styles',
        'founded',
        'general_info',
        'general_manager',
        'genre',
        'global_brand_page_name',
        'global_brand_root_id',
        'has_added_app',
        'hometown',
        'hours',
        'impressum',
        'influences',
        'is_always_open',
        'is_community_page',
        'is_permanently_closed',
        'is_published',
        'is_unclaimed',
        'is_verified',
        'is_webhooks_subscribed',
        'leadgen_tos_accepted',
        'link',
        'location',
        'members',
        'mission',
        'mpg',
        'name',
        'name_with_location_descriptor',
        'network',
        'new_like_count',
        'offer_eligible',
        'overall_star_rating',
        'parent_page',
        'parking',
        'payment_options',
        'personal_info',
        'personal_interests',
        'pharma_safety_info',
        'phone',
        'place_type',
        'plot_outline',
        'press_contact',
        'price_range',
        'produced_by',
        'products',
        'promotion_ineligible_reason',
        'public_transit',
        'publisher_space',
        'rating_count',
        'record_label',
        'release_date',
        'restaurant_services',
        'restaurant_specialties',
        'schedule',
        'screenplay_by',
        'season',
        'single_line_address',
        'starring',
        'start_info',
        'store_location_descriptor',
        'store_number',
        'studio',
        'talking_about_count',
        'unread_message_count',
        'unread_notif_count',
        'unseen_message_count',
        'username',
        'verification_status',
        'voip_info',
        'website',
        'were_here_count',
        'written_by',
    ];
    const POST_FIELDS = [
        'id',
        'admin_creator',
        'application',
        'call_to_action',
        'caption',
        'created_time',
        'description',
        'feed_targeting',
        'from',
        'icon',
        'instagram_eligibility',
        'is_hidden',
        'is_instagram_eligible',
        'is_published',
        'link',
        'message',
        'message_tags',
        'name',
        'object_id',
        'parent_id',
        'picture',
        'place',
        'privacy',
        'properties',
        'shares',
        'source',
        'status_type',
        'story',
        'story_tags',
        'targeting',
        'to',
        'type',
        'updated_time',
        'with_tags',
        'reactions.type(LIKE).limit(5).summary(true).as(reactions_like)',
        'reactions.type(LOVE).limit(5).summary(true).as(reactions_love)',
        'reactions.type(WOW).limit(5).summary(true).as(reactions_wow)',
        'reactions.type(HAHA).limit(5).summary(true).as(reactions_haha)',
        'reactions.type(SAD).limit(5).summary(true).as(reactions_sad)',
        'reactions.type(ANGRY).limit(5).summary(true).as(reactions_angry)',
        'comments.limit(5).summary(true)',
        'attachments',
    ];

    public function __construct(IFacebookSdk $fb)
    {
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
     * @return IFacebookSdk
     */
    protected function getFbAppBase()
    {
        return $this->fbAppBase;
    }

    /**
     * @param IFacebookSdk $fbAppBase
     */
    private function setFbAppBase(IFacebookSdk $fbAppBase)
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
                if ($response->isError()){
                    $this->dumpErr($response->getThrownException(), $headerMessage . " (Got response error but no exception)");
                    $response = null;
                    sleep(600);
                }
            } catch (FacebookThrottleException $e)
            {
                $this->dumpErr($e, $headerMessage . " (Got Exception:" . get_class($e) . ")");
                $response = null;
                sleep(600);
            } catch (FacebookResponseException $e)
            {
                $this->dumpErr($e, $headerMessage . " (Got Exception:" . get_class($e) . ")");
                $response = null;
                sleep(10);
            } catch (\Exception $e)
            {
                $this->dumpErr($e, $headerMessage . " (Got Exception:" . get_class($e) . ")");
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
        fprintf($stderr, $e->getTraceAsString() . "\n");
        $this->setLastException($e);
        fclose($stderr);
    }

    /**
     * @return MongoDBCollection
     */
    protected function getFeedCollection()
    {
        return $this->getFbDM()->getFeedCollection();
    }

    /**
     * @return MongoDBCollection
     */
    protected function getPageCollection()
    {
        return $this->getFbDM()->getPageCollection();
    }

    /**
     * @return MongoDBCollection
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

    /**
     * @param string $pageId
     * @return string endpoint
     */
    protected function getPageEndpoint($pageId)
    {
        return '/' . $pageId .
            '?fields=' .
            implode(',', CGFbCrawler::PAGE_FIELDS);
    }

    /**
     * @param string $postId
     * @return string endpoint
     */
    protected function getPostEndpoint($postId)
    {
        return '/' . $postId .
            '?fields=' .
            implode(',', CGFbCrawler::POST_FIELDS);
    }

    /**
     * @param string $pageId
     * @param string $since
     * @param string $until
     * @return string endpoint
     */
    protected function getFeedEndpoint($pageId, $since = null, $until = null)
    {
        $endpoint = '/' . $pageId . '/posts';
        $params = [];
        $params[] = 'limit=' . self::FEED_LIMIT;
        if (!empty($since))
        {
            $params[] = 'since=' . strtotime($since);
        }
        if (!empty($until))
        {
            $params[] = 'until=' . strtotime($until);
        }
        if (count($params) > 0)
        {
            $endpoint .= '?' . implode('&', $params);
        }
        return $endpoint;
    }
}
