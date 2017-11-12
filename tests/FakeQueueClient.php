<?php

namespace CodingGuys\Tests;

use CodingGuys\IQueueClient;

class FakeQueueClient implements IQueueClient
{
    private $client;

    public function __construct()
    {
        $this->client = [];
    }

    public function doBackground($fnName, $workload)
    {
        if (!isset($this->client[$fnName]))
        {
            $this->client[$fnName] = [];
        }
        $this->client[$fnName][] = $workload;
        return "FAKE";
    }

    public function count($fnName)
    {
        if (!isset($this->client[$fnName]))
        {
            return 0;
        }
        return count($this->client[$fnName]);
    }

    public function reset()
    {
        $this->client = [];
    }
}
