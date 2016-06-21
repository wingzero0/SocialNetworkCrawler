<?php


require_once(__DIR__ . '/CodingGuys/autoload.php');
require_once(__DIR__ . '/vendor/autoload.php');

use CodingGuys\FbRepo\FbPageRepo;
use CodingGuys\Document\FacebookPage;

$batchTime = new MongoDate();
$crawlTime = new \DateTime();
$crawlTime->setTimestamp($batchTime->sec)->setTimezone(new \DateTimeZone("GMT"));
$crawlTimeH = intval($crawlTime->format('H'));

echo "crawlTimeH:" . $crawlTimeH . "\n";

$repo = new FbPageRepo();
$cursor = $repo->findAllWorkingPageByCrawlTime($crawlTimeH);

// Create our client object
$client = new GearmanClient();

// Add a server
$client->addServer(); // by default host/port will be "localhost" & 4730
foreach ($cursor as $doc)
{
    $fbPage = new FacebookPage($doc);
    echo "crawling:" . $fbPage->getFbID() . "\n";

    echo "Sending job\n";
    // Send reverse job
    $job_handle = $client->doBackground("fbCrawler", serialize(array(
        "fbID" => $fbPage->getFbID(),
        "_id" => $fbPage->getId() . "",
        "batchTime" => $batchTime,
    )));
}
