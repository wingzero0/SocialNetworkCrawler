<?php
/**
 * User: kit
 * Date: 10/01/15
 * Time: 14:09
 *
 * Program function:
 * read a file with fbID list. crawl the new fb page information and store in directory.Facebook
 *
 * input file format:
 * each line contains one fbID
 *
 * sample command:
 * php upsertFbPage.php -f fbId.sample.txt --appId xxx --appSecret yyy
 */

require_once(__DIR__ . '/config.php');
setDefaultConfig();
require_once(__DIR__ . '/CodingGuys/autoload.php');
require_once(__DIR__ . '/vendor/autoload.php');

use CodingGuys\CGPageCrawler;
use CodingGuys\FacebookSdk;
use CodingGuys\QueueClient;
use CodingGuys\Utility\DateUtility;

$options = getopt("f:", array("appId:", "appSecret:"));

checkOptions($options);

$fp = fopen($options["f"], 'r');

$appId = $options['appId'];
$appSecret = $options['appSecret'];
$fbConfig = [
    'app_id' => $appId,
    'app_secret' => $appSecret,
    'default_graph_version' => $_ENV['FB_DEFAULT_GRAPH_VERSION'],
    'default_access_token' => $appId . '|' . $appSecret,
];
$batchTime = DateUtility::getCurrentMongoDate();
// TODO create unit test for this php, remove crawl time (in function and input file) if it is unnecessary
while ($line = fgets($fp))
{
    list($category, $city, $country, $fbId, $crawlTime) = parseData($line);

    $pageCrawler = new CGPageCrawler(
        new FacebookSdk($fbConfig),
        new QueueClient($_ENV['GEARMAN_HOST'], $_ENV['GEARMAN_PORT']),
        $fbId,
        $batchTime
    );
    $mongoId = $pageCrawler->getFbMongoId($fbId);
    if ($mongoId)
    {
        $ret = $pageCrawler->reCrawlData($mongoId, $category, $city, $country, $crawlTime);
        echo "crawler status " . $ret . "\n";
    } else
    {
        $ret = $pageCrawler->crawlNewPage($fbId, $category, $city, $country, $crawlTime);
        echo "crawler status " . $ret . "\n";
    }
}
fclose($fp);

function checkOptions($options)
{
    $errorFlag = true;
    if (!isset($options["f"]))
    {
        echo "you must specific the input file with option '-f'\n";
    } else if (!isset($options["appId"]))
    {
        echo "you must specific fb appId with option '--appId'\n";
    } else if (!isset($options["appSecret"]))
    {
        echo "you must specific fb appId with option '--appSecret'\n";
    } else
    {
        $errorFlag = false;
    }

    if ($errorFlag)
    {
        exit(-1);
    }
    return;
}

function parseData($line)
{
    $lineElements = preg_split("/\t/", $line);
    $category = trimAndReplaceEmptyAsNull($lineElements[0]);
    $city = trimAndReplaceEmptyAsNull($lineElements[1]);
    $country = trimAndReplaceEmptyAsNull($lineElements[2]);
    $id = trimAndReplaceEmptyAsNull($lineElements[3]);
    $crawlTime = array();
    if (isset($lineElements[4]))
    {
        $crawlTimeTmp = preg_split("/,/", trim($lineElements[4]));
        foreach ($crawlTimeTmp as $i => $value)
        {
            if ($value !== "0" && empty($value))
            {
                continue;
            }
            $crawlTime[] = intval($value);
        }
    }
    return array($category, $city, $country, $id, $crawlTime);
}

function trimAndReplaceEmptyAsNull($inputStr)
{
    $str = trim($inputStr);
    if (empty($str))
    {
        return null;
    }
    return $str;
}
