<?php
/**
 * User: kit
 * Date: 22/11/15
 * Time: 2:09 PM
 */
require_once(__DIR__ . '/CodingGuys/autoload.php');

use CodingGuys\MongoFb\CGMongoFb;

$options = getopt("", array("id:", "message::"));
if (is_array($options["id"]))
{
    $queryId = $options["id"];
} else
{
    $queryId = array($options["id"]);
}

$message = $options["message"];
print_r($options);

$mongoFb = new CGMongoFb();
$col = $mongoFb->getMongoCollection($mongoFb->getPageCollectionName());
$exceptionCol = $mongoFb->getMongoCollection($mongoFb->getExceptionPageCollectionName());

foreach ($queryId as $id)
{
    $query = array("_id" => new MongoId($id));
    $cursor = $col->find($query);
    foreach ($cursor as $page)
    {
        var_dump($page);
        $exceptionCol->update(
            array("_id" => $page["_id"]),
            array_merge($page, array("error" => array("message" => $message))),
            array("upsert" => true)
        );
        $col->update(
            array("_id" => $page["_id"]),
            array_merge(
                array(
                    "fbID" => $page["fbID"],
                    "exception" => true
                ),
                array("error" => array("message" => $message))
            )
        );
    }
}


