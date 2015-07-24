<?php
/**
 * User: kit
 * Date: 01/06/15
 * Time: 14:03
 */

require_once(__DIR__ . '/../facebook-php-sdk-v4/autoload.php');
require_once(__DIR__ . '/../CodingGuys/autoload.php');

use CodingGuys\MongoFb\CGMongoFb;

$fbMongo = new CGMongoFb();
$col = $fbMongo->getMongoCollection($fbMongo->getPageCollectionName());
$cursor = $col->find(array( "\$or" => array(
    array("exception" => array("\$exists" => false)),
    array("exception" => false),
)));

echo "fbID,name,likes,link,checkins,were_here_count,mnemonoCat,fbCategory,fbCategory_list,\n";
foreach ($cursor as $pageRaw){
	echo $pageRaw["fbID"].",";
	echo toString($pageRaw, "name").",";
	echo toString($pageRaw, "likes").",";
	echo toString($pageRaw, "link").",";
	echo toString($pageRaw, "checkins").",";
	echo toString($pageRaw, "were_here_count").",";
    if (isset($pageRaw["mnemono"]) && isset($pageRaw["mnemono"]["category"])){
        echo $pageRaw["mnemono"]["category"] .",";
    }else{
        echo "NULL,";
    }
	echo toString($pageRaw, "category").",";
	if (isset($pageRaw["category_list"])){
		foreach ($pageRaw["category_list"] as $category){
			echo $category["name"].",";
		}
	}else {
		echo "NULL,";
	}

	echo "\n";
}

function toString($page, $attributeName){
	if (!isset($page[$attributeName])){
		return "NULL";
	}

	if (preg_match("/,/", $page[$attributeName])){
		return "\"" . $page[$attributeName] . "\"";
	}
	return $page[$attributeName];
}