<?php

namespace CodingGuys\Tests;

use CodingGuys\IFacebookSdk;

class FakeFacebookSdk implements IFacebookSdk
{
    public function get($endpoint,
                        $accessToken = NULL,
                        $eTag = NULL,
                        $graphVersion = NULL)
    {
        return null;
    }
}
