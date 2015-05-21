<?php
/**
 * User: kit
 * Date: 18/05/15
 * Time: 20:21
 */

require_once(__DIR__ . '/facebook-php-sdk-v4/autoload.php');
require_once(__DIR__ . '/CodingGuys/autoload.php');

use CodingGuys\CGFeedStat;
use CodingGuys\MongoFb\CGMongoFbPage;

//$endDate = DateTime::createFromFormat(\DateTime::ISO8601,"2015-05-17T06:00:00+0000");
$endDate = new \DateTime();
$startDate = clone $endDate;
$startDate->sub(new \DateInterval('P7D'));

// $m = new \MongoClient();
// $col = $m->selectCollection("directory", "Facebook");
// $cursor = $col->find(array("fbID" => "963585093655755"));

// $cgMongoFbPage = new CGMongoFbPage($cursor->next());
// $obj = $cgMongoFbPage->getAverageFeedLikesBeforeTheBatch($cgMongoFbPage->getFirstBatchTimeWithInWindow(
// 		$startDate,
// 		$endDate
// 	));
// print_r($obj);

$obj = new CGFeedStat($startDate, $endDate);
$obj->timestampSeriesCount();

