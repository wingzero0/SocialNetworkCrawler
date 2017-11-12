<?php

namespace CodingGuys;

use Facebook\Exceptions\FacebookSDKException;
use Facebook\FacebookResponse;
use Facebook\Authentication\AccessToken;

interface IFacebookSdk
{
    /**
     * interface to forward request to the real FbSDK
     * Sends a GET request to Graph and returns the result.
     *
     * @param string                  $endpoint
     * @param AccessToken|string|null $accessToken
     * @param string|null             $eTag
     * @param string|null             $graphVersion
     *
     * @return FacebookResponse
     *
     * @throws FacebookSDKException
     */
    public function get($endpoint, $accessToken = null, $eTag = null, $graphVersion = null);
}
