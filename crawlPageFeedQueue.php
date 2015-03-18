<?php

$m = new \MongoClient();
$col = $m->selectCollection("directory", "Facebook");
$cursor = $col->find();

$jobStacks = array();
$maxJob = 4;
for ($i = 0;$i<$maxJob;$i++){
    $jobStacks[$i] = null;
}

// Create our client object
$client = new GearmanClient();

// Add a server
$client->addServer(); // by default host/port will be "localhost" & 4730

$processingJobCount = 0;
$maxTestIteration = 0;
foreach($cursor as $doc){
	echo "crawling:".$doc["fbID"]."\n";

	echo "Sending job\n";
	// Send reverse job
	$job_handle = $client->doBackground("fbCrawler", json_encode(array("fbID" => $doc["fbID"], "_id" => $doc["_id"]."")));
    for($i=0;$i<$maxJob;$i++){
        if ($jobStacks[$i] == null){
            $jobStacks[$i] = $job_handle;
            $processingJobCount +=1;
            break;
        }
    }
	while ($processingJobCount >= $maxJob){
        print_r($processingJobCount);
        print_r($jobStacks);
        echo "waiting\n";
        for($i=0;$i<$maxJob;$i++){
            $stat = $client->jobStatus($jobStacks[$i]);
            if (!$stat[0]){
                $jobStacks[$i] = null;
                $processingJobCount -=1;
            }
        }
        sleep(1);
    }
    $maxTestIteration +=1;
    if ($maxTestIteration > 50){break;}
}
