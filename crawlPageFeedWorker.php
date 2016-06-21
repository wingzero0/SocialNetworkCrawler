<?php
/**
 * User: kit
 * Date: 07/03/15
 * Time: 17:34
 */


require_once(__DIR__ . '/facebook-php-sdk-v4/autoload.php');
require_once(__DIR__ . '/CodingGuys/autoload.php');

use CodingGuys\CGPageFeedCrawler;

$options = getopt("", array("appId:", "appSecret:"));

// Create our worker object
$worker = new \GearmanWorker();

// Add a server (again, same defaults apply as a worker)
$worker->addServer();

// Inform the server that this worker can process "reverse" function calls
$worker->addFunction("fbCrawler", "fbCrawler_fn", $options);

while (1)
{
    print "Waiting for job...\n";
    $ret = $worker->work(); // work() will block execution until a job is delivered
    if ($worker->returnCode() != GEARMAN_SUCCESS)
    {
        break;
    }
}

// A much simple reverse function
function fbCrawler_fn(GearmanJob $job, &$options)
{
    $appId = $options["appId"];
    $appSecret = $options["appSecret"];
    $workload = unserialize($job->workload());
    $mID = new \MongoDB\BSON\ObjectID($workload["_id"]);
    $batchTime = $workload["batchTime"];
    echo "Received job: " . $job->handle() . "\n";
    echo "Workload: \n";
    var_dump($workload);
    $crawler = new CGPageFeedCrawler($workload["fbID"], $mID, $batchTime, $appId, $appSecret);
    echo "crawling:" . $crawler->crawl();

    return "Finish";
}


