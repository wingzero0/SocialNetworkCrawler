<?php
/**
 * User: kit
 * Date: 07/03/15
 * Time: 17:34
 */

require_once(__DIR__ . '/config.php');
setDefaultConfig();
require_once(__DIR__ . '/CodingGuys/autoload.php');
require_once(__DIR__ . '/vendor/autoload.php');

use CodingGuys\CGPageCrawler;
use CodingGuys\CGFeedCrawler;
use CodingGuys\CGPostCrawler;
use CodingGuys\Utility\DateUtility;
use CodingGuys\FacebookSdk;
use CodingGuys\QueueClient;

$options = getopt("", array("appId:", "appSecret:", "iteration:"));

$worker = new \GearmanWorker();
$worker->addServer($_ENV['GEARMAN_HOST'], $_ENV['GEARMAN_PORT']);
$worker->addFunction($_ENV['QUEUE_CRAWLER'], "fbCrawler_fn");

if (isset($options["iteration"]))
{
    $maxIteration = intval($options["iteration"]);
}else{
    $maxIteration = 100;
}

for ($loopCount = 0;  $loopCount < $maxIteration; $loopCount++)
{
    print "Waiting for job...\n";
    $ret = $worker->work(); // work() will block execution until a job is delivered
    if ($worker->returnCode() != GEARMAN_SUCCESS)
    {
        break;
    }
}

print "worker aging out\n";
exit(0);

function fbCrawler_fn(GearmanJob $job)
{
    global $options;
    // TODO read app id and secret from $_ENV, use options as control flag to swap key;
    $appId = $options["appId"];
    $appSecret = $options["appSecret"];
    $workload = json_decode($job->workload(), true);
    $mID = new \MongoDB\BSON\ObjectID($workload["pageMongoId"]);
    $batchTime = DateUtility::convertDateTimeToMongoDate(
        DateTime::createFromFormat(DateTime::ISO8601, $workload['batchTime'])
    );
    echo "Received job: " . $job->handle() . "\n";
    echo "Workload: \n";
    var_dump($workload);

    $fbConfig = [
        'app_id' => $appId,
        'app_secret' => $appSecret,
        'default_graph_version' => $_ENV['FB_DEFAULT_GRAPH_VERSION'],
        'default_access_token' => $appId . '|' . $appSecret,
    ];
    switch ($workload["type"])
    {
        case 'page':
            $crawler = new CGPageCrawler(
                new FacebookSdk($fbConfig),
                new QueueClient($_ENV['GEARMAN_HOST'], $_ENV['GEARMAN_PORT']),
                $workload["fbID"],
                $batchTime,
                $mID
            );
            break;
        case 'feed':
            $crawler = new CGFeedCrawler(
                new FacebookSdk($fbConfig),
                new QueueClient($_ENV['GEARMAN_HOST'], $_ENV['GEARMAN_PORT']),
                $workload["fbID"],
                $mID,
                $workload['batchTime'],
                $workload['since'],
                $workload['until']
            );
            break;
        case 'post':
            $crawler = new CGPostCrawler(
                new FacebookSdk($fbConfig),
                new QueueClient($_ENV['GEARMAN_HOST'], $_ENV['GEARMAN_PORT']),
                $workload["fbID"],
                $mID,
                $batchTime
            );
            break;
        default:
            throw new \UnexpectedValueException ("'type' should be 'page', 'feed' or 'post'");
    }
    echo "crawling: " . $crawler->crawl() . "\n";

    return "Finish";
}
