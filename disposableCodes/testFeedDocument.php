<?php
/**
 * User: kit
 * Date: 5/31/2016
 * Time: 8:48 AM
 */

require_once __DIR__ . "/../CodingGuys/autoload.php";

use CodingGuys\Document\FacebookFeed;

$cli = new MongoClient();
$col = $cli->selectCollection("Mnemono", "FacebookFeed");
$cur = $col->find()->limit(1)->sort(array("_id" => -1));

foreach ($cur as $feed)
{
    //var_dump($feed);
    $fb = new FacebookFeed($feed);
    var_dump($fb);
}