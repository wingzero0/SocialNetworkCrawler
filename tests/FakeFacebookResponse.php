<?php

namespace CodingGuys\Tests;

class FakeFacebookResponse
{
    private $body;

    public function __construct($body)
    {
        $this->body = $body;
    }

    public function isError()
    {
        $body = $this->getDecodedBody();
        return isset($body['error']);
    }

    public function getDecodedBody()
    {
        return $this->body;
    }
}
