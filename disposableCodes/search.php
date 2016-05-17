<?php
/**
 * User: kit
 * Date: 01/01/15
 * Time: 13:56
 */


require_once(__DIR__ . '/../facebook-php-sdk-v4/autoload.php');
require_once(__DIR__ . '/../CodingGuys/autoload.php');

use CodingGuys\FbWrapper\CGSearchResult;
use CodingGuys\MongoFb\CGMongoFb;
use Facebook\FacebookRequest;
use Facebook\FacebookSession;
use Facebook\GraphPage;

FacebookSession::setDefaultApplication('717078611708065', 'cfcb7c75936b2c44caba648cb4d20e69');
$session = FacebookSession::newAppSession();

$q = $argv[1];

$fbMongo = new CGMongoFb();
$col = $fbMongo->getMongoCollection("FacebookSearchResults");

$limit = 100;
$after = null;
do
{
    if ($after != null)
    {
        $query = array('q' => $q, 'type' => 'page',
            'limit' => $limit, 'after' => $after);
    } else
    {
        $query = array('q' => $q, 'type' => 'page',
            'limit' => $limit, 'after' => $after);
    }
    echo "querying:\n";
    print_r($query);

    $request = new FacebookRequest($session, 'GET', '/search', $query);

    $response = $request->execute();

    //$responseData = $response->getResponse();
    //print_r($responseData);

    $searchR = $response->getGraphObject(CGSearchResult::className());
    if ($searchR instanceof CGSearchResult)
    {
        $pages = $searchR->getPages();

        foreach ($pages as $i => $page)
        {
            //print_r($page);
            if ($page instanceof GraphPage)
            {
                $pageAsArray = $page->asArray();
                $pageAsArray['fbID'] = $pageAsArray['id'];
                unset($pageAsArray['id']);
                //print_r($pageAsArray);
                $col->insert($pageAsArray);
            }
        }
        $after = $searchR->getAfter();
    }

} while ($after != null)

?>