<?php

$m = new \MongoClient();
$col = $m->selectCollection("directory", "Facebook");
$cursor = $col->find();

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
