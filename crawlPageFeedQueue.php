<?php

require_once(__DIR__ . '/config.php');
setDefaultConfig();
require_once(__DIR__ . '/CodingGuys/autoload.php');
require_once(__DIR__ . '/vendor/autoload.php');

use CodingGuys\QueueClient;
use CodingGuys\PageJobDispatcher;
use CodingGuys\FeedJobDispatcher;
use CodingGuys\PostJobDispatcher;

$crawlTime = new \DateTime();
// TODO Patch data, change FacebookPage.mnemono.crawlTime to GMT+8
$crawlTime->setTimezone(new \DateTimeZone('GMT'));
$crawlTimeH = intval($crawlTime->format('H'));

echo 'crawlTimeH: ' . $crawlTimeH . "\n";

$pageJobDispatcher = new PageJobDispatcher(
    new QueueClient($_ENV['GEARMAN_HOST'], $_ENV['GEARMAN_PORT'])
);
$pageJobDispatcher->dispatchAt($crawlTime);

$feedJobDispatcher = new FeedJobDispatcher(
    new QueueClient($_ENV['GEARMAN_HOST'], $_ENV['GEARMAN_PORT'])
);
$feedJobDispatcher->dispatchAt($crawlTime);

$postJobDispatcher = new PostJobDispatcher(
    new QueueClient($_ENV['GEARMAN_HOST'], $_ENV['GEARMAN_PORT'])
);
$postJobDispatcher->dispatchAt($crawlTime);
