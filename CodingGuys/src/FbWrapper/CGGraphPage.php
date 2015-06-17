<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 01/01/15
 * Time: 12:27
 */

namespace CodingGuys\FbWrapper;

use Facebook\GraphPage;
use Facebook\GraphLocation;

class CGGraphPage extends GraphPage{
    /**
     * Returns the best available Page on Facebook.
     *
     * @return GraphPage|null
     */
    public function getBestPage()
    {
        return $this->getProperty('best_page');
    }

    /**
     * Returns the brand's global (parent) Page.
     *
     * @return GraphPage|null
     */
    public function getGlobalBrandParentPage()
    {
        return $this->getProperty('global_brand_parent_page');
    }

    /**
     * Returns the location of this place.
     *
     * @return GraphLocation|null
     */
    public function getLocation()
    {
        return $this->getProperty('location',GraphLocation::className());
    }

    /**
     * Returns the roles of the page admin user.
     * Only available in the `/me/accounts` context.
     *
     * @return array|null
     */
    public function getHours()
    {
        return $this->getProperty('hours')->backingData;
    }

    /**
     * Returns phone
     *
     * @return String|null
     */
    public function getPhone()
    {
        return $this->getProperty('phone');
    }

    /**
     * Returns website url
     *
     * @return String|null
     */
    public function getWebsite()
    {
        return $this->getProperty('website');
    }

    /**
     * Returns username of the page.
     *
     * @return String|null
     */
    public function getUsername()
    {
        return $this->getProperty('username');
    }
    /**
     * Returns cover
     *
     * @return array|null
     */
    public function getCover()
    {
        return $this->getProperty('cover')->backingData;
    }
    /**
     * Returns CoverUrl
     *
     * @return String|null
     */
    public function getCoverUrl()
    {
        return $this->getProperty('cover')->backingData['source'];
    }

    /**
     * Returns description
     *
     * @return String|null
     */
    public function getDescription()
    {
        return $this->getProperty('description');
    }

    /**
     * Returns likes
     *
     * @return int|null
     */
    public function getLikes()
    {
        return $this->getProperty('likes');
    }

    /**
     * Returns category_list
     *
     * @return array|null
     */
    public function getCategoryList()
    {
        return $this->getProperty('category_list')->backingData;
    }

    /**
     * Returns about
     *
     * @return array|null
     */
    public function getAbout()
    {
        return $this->getProperty('about');
    }

    /**
     * Returns parking
     *
     * @return array|null
     */
    public function getParking()
    {
        return $this->getProperty('parking')->backingData['lot'];
    }
} 