<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 10/01/15
 * Time: 13:12
 */


require_once(__DIR__ . '/../facebook-php-sdk-v4/autoload.php');
require_once(__DIR__ . '/../CodingGuys/autoload.php');

use CodingGuys\FbWrapper\CGGraphPage;
use CodingGuys\FbWrapper\CGSearchResult;
use Facebook\FacebookRequest;
use Facebook\FacebookSession;
use Facebook\GraphPage;

FacebookSession::setDefaultApplication('717078611708065', 'cfcb7c75936b2c44caba648cb4d20e69');
$session = FacebookSession::newAppSession();

$names = array("meetandsweet",
    "lovewingscafe",
    //"cafenovakokoro",
    "BleisureMacao",
    "paradisemacau",
    "PuffinCafe",
    "Poker.caffee",
    "Poker.cafe",
    "cafedesmuseemacau",
    "nanamacau2",
    "belostempos",
    "fancycafe.gabee",
    "EuroPancakes",
    "azucarmacau",
    "HelloKittyMacauFriends",
    "macaupalms",
    "CaravelaM",
    "myrecipemacau",
    "riomarinternet",
    "camposno.6",
    "MacauNoodles",
    "cafevoyagemacau",
    "TheChillOutCafeMacau",
    "kafkasweets",
    "aloiadou",
    "kumabakerymacau",
    "GuiaBakery",
    "lemonlemonmacao",
    "thedrinkcardmacau",
    "ilcafegelatomacau",
    "trioroomddd",
    "decaf.macau",
    "TenkaSushiHK",
    "MacauMuseum",
    "cipa.macau");

foreach ($names as $name)
{
    $request = new FacebookRequest($session, 'GET', '/' . $name);
    $response = $request->execute();

    $responseData = $response->getGraphObject(CGGraphPage::className());
    echo $responseData->getId() . "\n";
    //print_r($response->getResponse());
    //sleep(3);
}