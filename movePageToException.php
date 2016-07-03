<?php
/**
 * User: kit
 * Date: 22/11/15
 * Time: 2:09 PM
 */
require_once(__DIR__ . '/CodingGuys/autoload.php');
require_once(__DIR__ . '/vendor/autoload.php');

use CodingGuys\Document\FacebookPage;
use CodingGuys\Document\FacebookExceptionPage;
use CodingGuys\FbDocumentManager\FbDocumentManager;
use CodingGuys\FbRepo\FbPageRepo;
use CodingGuys\Utility\DateUtility;

$options = getopt("", array("id:", "message:"));
if (is_array($options["id"]))
{
    $queryId = $options["id"];
} else
{
    $queryId = array($options["id"]);
}

$message = $options["message"];

$fbDM = new FbDocumentManager();
$fbPageRepo = new FbPageRepo($fbDM);

foreach ($queryId as $id)
{
    $pageRaw = $fbPageRepo->findOneById(new \MongoDB\BSON\ObjectID($id));
    if ($pageRaw === null)
    {
        throw new UnexpectedValueException();
    }

    $exPage = new FacebookExceptionPage($pageRaw);
    $exPage->setId(null);
    $exPage->setError(array("message" => $message));
    $mongoDate = DateUtility::convertDateTimeToMongoDate(new \DateTime());
    $exPage->setExceptionTime($mongoDate);
    $exPage->setException(true);
    $fbDM->writeToDB($exPage);

    $page = new FacebookPage($pageRaw);
    $page->setException(true);
    $page->setError(array("message" => $message));
    $fbDM->writeToDB($page);
}


