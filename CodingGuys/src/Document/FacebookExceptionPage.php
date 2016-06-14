<?php

/**
 * User: kit
 * Date: 24/04/2016
 * Time: 3:32 PM
 */

namespace CodingGuys\Document;

class FacebookExceptionPage extends FacebookPage
{
    const TARGET_COLLECTION = "FacebookExceptionPage";
    public function getCollectionName()
    {
        return FacebookExceptionPage::TARGET_COLLECTION;
    }
}