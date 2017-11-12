<?php
/**
 * User: kit
 * Date: 7/13/2015
 * Time: 2:16 PM
 */

// TODO remove this legacy file / class

namespace CodingGuys\FbWrapper;

use Facebook\FacebookSession;

class CGSession
{
    /**
     * @return FacebookSession
     */
    public function createFacebookSession()
    {
        FacebookSession::setDefaultApplication('717078611708065', 'cfcb7c75936b2c44caba648cb4d20e69');
        $session = FacebookSession::newAppSession();
        return $session;
    }
}