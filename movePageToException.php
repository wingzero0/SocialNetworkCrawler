<?php
/**
 * User: kit
 * Date: 22/11/15
 * Time: 2:09 PM
 */
require_once(__DIR__ . '/CodingGuys/autoload.php');

use CodingGuys\Document\FacebookPage;
use CodingGuys\Document\FacebookExceptionPage;
use CodingGuys\FbDocumentManager\FbDocumentManager;
use CodingGuys\FbRepo\FbPageRepo;

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

$fbDM = new FbDocumentManager();
$fbPageRepo = new FbPageRepo($fbDM);

foreach ($queryId as $id)
{
    $pageRaw = $fbPageRepo->findOneById(new MongoId($id));
    if ($pageRaw === null){
        throw new UnexpectedValueException();
    }

    $exPage = new FacebookExceptionPage($pageRaw);
    $exPage->setError(array("error" => array("message" => $message)));
    $fbDM->upsertDB($exPage,array("_id" => $exPage->getId()));

    var_dump($pageRaw);

    $page = new FacebookPage($pageRaw);
    $page->setException(true);
    $page->setError(array("error" => array("message" => $message)));
    $fbDM->writeToDB($page);
}


