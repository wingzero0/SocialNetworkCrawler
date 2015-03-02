<?php
/**
 * User: kit
 * Date: 10/01/15
 * Time: 13:27
 */



$m = new MongoClient();
$col = $m->selectCollection("directory", "FacebookPageList");
$cursor = $col->find()->sort(array("_id" => 1));


foreach($cursor as $doc){
    //print_r($doc);
    echo($doc['_id']."\n");
    $pageCol = $m->selectCollection("directory", "FbPageIdList");
    $pageCol->insert(array("_id" => $doc['_id']));
}