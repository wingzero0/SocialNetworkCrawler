<?php

namespace CodingGuys;

interface IQueueClient
{
    /**
     * @param string $fnName
     * @param string $workload
     * @return string The job handle for the submitted task
     */
    public function doBackground($fnName, $workload);
}
