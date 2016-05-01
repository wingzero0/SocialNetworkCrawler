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
        )
    )
);

$updateCount = 0;

foreach ($cursor as $pageRaw)
{
    //echo $pageRaw["_id"]."\n";
    //echo $pageRaw["name"]."\n";
    $crawlTime = $pageRaw["mnemono"]["crawlTime"];
    //var_dump($pageRaw["mnemono"]["crawlTime"]);
    $updateCount += patchCrawlTime($pageRaw["_id"], $crawlTime);
}

echo "\ntotal updated:" . $updateCount . "\n";

/**
 * @param \MongoId $mongoId
 * @param array $crawlTime
 * @return int
 */
function patchCrawlTime(MongoId $mongoId, $crawlTime)
{
    if (empty($crawlTime))
    {
        return 0;
    }
    $intCrawlTime = array();
    $shouldUpdate = false;
    foreach ($crawlTime as $value)
    {
        if (is_string($value))
        {
            $shouldUpdate = true;
            $intCrawlTime[] = intval($value);
        } else if (is_int($value))
        {
            $intCrawlTime[] = $value;
        }
    }

    if ($shouldUpdate)
    {
        echo "patching:\n";
        var_dump($mongoId);
        var_dump($intCrawlTime);

        $fbMongo = new CGMongoFb();
        $col = $fbMongo->getMongoCollection($fbMongo->getPageCollectionName());
        $col->update(
            array("_id" => $mongoId),
            array(
                "\$set" => array(
                    "mnemono.crawlTime" => $intCrawlTime
                )
            )
        );
        return 1;
    }
    return 0;
}