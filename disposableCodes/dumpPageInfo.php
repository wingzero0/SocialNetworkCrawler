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
$cursor = $col->find(array("\$or" => array(
    array("exception" => array("\$exists" => false)),
    array("exception" => false),
)));

echo "fbID" . getDelimiter()
    . "mnemonoCat" . getDelimiter()
    . "mnemonoCrawlTime" . getDelimiter()
    . "mnemonoCity" . getDelimiter()
    . "mnemonoCountry" . getDelimiter()
    . "name" . getDelimiter()
    . "likes" . getDelimiter()
    . "link" . getDelimiter()
    . "checkins" . getDelimiter()
    . "were_here_count" . getDelimiter()
    . "fbCategory" . getDelimiter()
    . "fbCategory_list" . getDelimiter()
    . "\n";
foreach ($cursor as $pageRaw)
{
    echo $pageRaw["fbID"] . getDelimiter();
    if (isset($pageRaw["mnemono"]))
    {
        if (isset($pageRaw["mnemono"]["category"]))
        {
            echo $pageRaw["mnemono"]["category"] . getDelimiter();
        } else
        {
            echo "NULL" . getDelimiter();
        }
        if (isset($pageRaw["mnemono"]["crawlTime"]) && !empty($pageRaw["mnemono"]["crawlTime"]))
        {
            foreach ($pageRaw["mnemono"]["crawlTime"] as $hour)
            {
                echo $hour . ",";
            }
            echo getDelimiter();
        } else
        {
            echo "NULL" . getDelimiter();
        }
        if (isset($pageRaw["mnemono"]["location"]))
        {
            echo $pageRaw["mnemono"]["location"]["city"] . getDelimiter();
            echo $pageRaw["mnemono"]["location"]["country"] . getDelimiter();
        } else
        {
            for ($i = 0; $i < 2; $i++)
            {
                echo "NULL" . getDelimiter();
            }
        }
    } else
    {
        for ($i = 0; $i < 4; $i++)
        {
            echo "NULL" . getDelimiter();
        }
    }

    echo toString($pageRaw, "name") . getDelimiter();
    echo toString($pageRaw, "likes") . getDelimiter();
    echo toString($pageRaw, "link") . getDelimiter();
    echo toString($pageRaw, "checkins") . getDelimiter();
    echo toString($pageRaw, "were_here_count") . getDelimiter();

    echo toString($pageRaw, "category") . getDelimiter();
    if (isset($pageRaw["category_list"]) && !empty($pageRaw["category_list"]))
    {
        foreach ($pageRaw["category_list"] as $category)
        {
            echo $category["name"] . ",";
        }
    } else
    {
        echo "NULL" . getDelimiter();
    }

    echo "\n";
}

/**
 * @return string
 */
function getDelimiter()
{
    return "\t";
}

function toString($page, $attributeName)
{
    if (!isset($page[$attributeName]))
    {
        return "NULL";
    }

    if (preg_match("/,/", $page[$attributeName]))
    {
        return "\"" . $page[$attributeName] . "\"";
    }
    return $page[$attributeName];
}