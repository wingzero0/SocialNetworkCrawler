<?php

namespace CodingGuys;

class QueueClient implements IQueueClient
{
    private $client;

    public function __construct($host, $port)
    {
        $this->client = new \GearmanClient();
        $this->client->addServer($host, $port);
    }

    public function doBackground($fnName, $workload)
    {
        return $this->client->doBackground($fnName, $workload);
    }
}
