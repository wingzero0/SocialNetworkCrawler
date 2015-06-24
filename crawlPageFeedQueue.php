<?php


require_once(__DIR__ . '/CodingGuys/autoload.php');
use CodingGuys\MongoFb\CGMongoFb;

$mongoFb = new CGMongoFb();
$pageCol = $mongoFb->getMongoCollection($mongoFb->getPageCollectionName());
$cursor = $pageCol->find(array( "\$or" => array(
    array("exception" => array("\$exists" => false)),
    array("exception" => false),
)));

// Create our client object
$client = new GearmanClient();

// Add a server
$client->addServer(); // by default host/port will be "localhost" & 4730
$batchTime = new MongoDate();
foreach($cursor as $doc){
	echo "crawling:".$doc["fbID"]."\n";

	echo "Sending job\n";
	// Send reverse job
	$job_handle = $client->doBackground("fbCrawler", serialize(array(
        "fbID" => $doc["fbID"],
        "_id" => $doc["_id"]."",
        "batchTime" => $batchTime,
    )));
}
