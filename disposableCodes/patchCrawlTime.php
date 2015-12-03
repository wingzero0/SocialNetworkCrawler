<?php
/**
 * User: kit
 * Date: 01/06/15
 * Time: 14:03
 */

require_once(__DIR__ . '/../facebook-php-sdk-v4/autoload.php');
require_once(__DIR__ . '/../CodingGuys/autoload.php');

use CodingGuys\MongoFb\CGMongoFb;

$fbMongo = new CGMongoFb();
$col = $fbMongo->getMongoCollection($fbMongo->getPageCollectionName());
$cursor = $col->find(
    array(
        "\$or" => array(
            array("exception" => array("\$exists" => false)),
            array("exception" => false),
        ),
        "mnemono.crawlTime" => array(
            "\$size" => 3
        )
    )
);

foreach ($cursor as $pageRaw){
    echo $pageRaw["_id"]."\n";
    echo $pageRaw["name"]."\n";
    print_r ($pageRaw["mnemono"]["crawlTime"]);
    $crawlTime = $pageRaw["mnemono"]["crawlTime"];
    $arrayFilter = array("6" => 0, "12" => 0, "18" => 0);
    foreach($crawlTime as $hour){
        if (!isset($arrayFilter[$hour])){
            $arrayFilter[$hour] = 0;
        }
        $arrayFilter[$hour] += 1;
    }
    if (count($arrayFilter) == 3){
        patchCrawlTime($pageRaw["_id"]);
    }
}

function patchCrawlTime($mongoId){
    $fbMongo = new CGMongoFb();
    $col = $fbMongo->getMongoCollection($fbMongo->getPageCollectionName());
    $col->update(
        array("_id" => $mongoId),
        array(
            "\$set" => array(
                "mnemono.crawlTime" => array(
                    0, 6, 12, 18
                )
            )
        )
    );
}