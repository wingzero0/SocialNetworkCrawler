<?php
/**
 * User: kit
 * Date: 13/1/2016
 * Time: 12:20
 */


require_once(__DIR__ . '/facebook-php-sdk-v4/autoload.php');
require_once(__DIR__ . '/CodingGuys/autoload.php');

use CodingGuys\CGPageCrawler;

$crawler = new CGPageCrawler("107348712640921");
//$crawler->crawl();
var_dump($crawler->crawl());
