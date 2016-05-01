<?php
/**
 * User: kit
 * Date: 10/06/15
 * Time: 19:42
 */

require_once(__DIR__ . '/../facebook-php-sdk-v4/autoload.php');
require_once(__DIR__ . '/../CodingGuys/autoload.php');

$fp = fopen($argv[1], "r");

$linksCat = array();
while ($line = fgets($fp))
{
    $line = trim($line);
    $list = preg_split("/\t/", $line);
    $cat = $list[0];
    $link = $list[1];
    $linksCat[$link] = $cat;
}

$m = new MongoClient();
$col = $m->selectCollection("directory", "Facebook");
$cursor = $col->find();

$linksPages = array();
foreach ($cursor as $page)
{
    $linksPages[toString($page, "link")] = $page;
}

foreach ($linksCat as $link => $cat)
{
    if (!isset($linksPages[$link]))
    {
        echo $link . " not found in DB\n";
        continue;
    }
    if ($cat == 'x')
    {
        echo (string)$linksPages[$link]["fbID"] . "\n";
    }
    //var_dump($linksPages[$link]["_id"]);
    $col->update(
        array("_id" => $linksPages[$link]["_id"]),
        array("\$set" => array("mnemonoCat" => $cat))
    );
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