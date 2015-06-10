<?php
/**
 * User: kit
 * Date: 19/05/15
 * Time: 21:23
 */

$fbID = $argv[1];
$m = new \MongoClient();
$col = $m->selectCollection("directory", "Facebook");
$cursor = $col->find(array("fbID" => $fbID));

$fbPage = null;
foreach ($cursor as $v){
    $fbPage = $v;
    print_r($fbPage);
}

if ($fbPage == null){
    return ;
}

$timestampCol = $m->selectCollection("directory", "FacebookTimestampRecord");

// TODO check ret["ok"]
$ret = $timestampCol->remove(array("fbPage.\$id" => $fbPage["_id"]));

print_r($ret);


$feedCol = $m->selectCollection("directory", "FacebookFeed");
$ret = $feedCol->remove(array("fbPage.\$id" => $fbPage["_id"]));

print_r($ret);


$ret = $col->remove(array("fbID" => $fbID));
print_r($ret);