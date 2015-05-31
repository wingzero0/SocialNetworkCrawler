<?php
/**
 * User: kit
 * Date: 31/05/15
 * Time: 14:13
 */

require_once(__DIR__ . '/CodingGuys/autoload.php');
use CodingGuys\CGMailer;

$mailMessage = $argv[1];
$attachment = $argv[2];
CGMailer::sendMail("admin@coding-guys.com","FB Daily Digest",$argv[1], $argv[2]);
