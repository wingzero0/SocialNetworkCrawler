<?php

$m = new \MongoClient();
$col = $m->selectCollection("directory", "Facebook");
$query = array();
$cursor = $col->find();
$i = 0;
foreach($cursor as $doc){
	echo "crawling:".$doc["fbID"]."\n";
	// Create our client object
	$client = new GearmanClient();

	// Add a server
	$client->addServer(); // by default host/port will be "localhost" & 4730
	echo "Sending job\n";
	// Send reverse job
	$result = $client->doNormal("fbCrawler", json_encode(array("fbID" => $doc["fbID"], "_id" => $doc["_id"]."")));
	echo $result."\n";

	$i++;
	if ($i > 10){
		break;
	}
}
