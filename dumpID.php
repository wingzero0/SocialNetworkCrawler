<?php
/**
 * User: kit
 * Date: 10/01/15
 * Time: 12:56
 */



$m = new MongoClient();
$col = $m->selectCollection("directory", "FacebookPageList");
$cursor = $col->find()->sort(array("_id" => 1));


foreach($cursor as $doc){
    //print_r($doc);
    echo($doc['_id']."\n");
}