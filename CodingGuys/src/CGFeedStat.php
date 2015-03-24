<?php
/**
 * User: kit
 * Date: 24/03/15
 * Time: 17:04
 */

namespace CodingGuys;

class CGFeedStat {
	private $startDate;
	public function __construct(\DateTime $startDate){
		$this->startDate = new \MongoDate($startDate->getTimestamp());
	}
    public function basicCount(){
        $m = new \MongoClient();
        $col = $m->selectCollection("directory", "FacebookFeed");
        $cursor = $col->find();
        $likesCount = array();
		$commentsCount = array();
		$likesSum = 0;
		$commentsSum = 0;
		$i = 0;
        foreach ($cursor as $feed){
        	//print_r($feed);
        	if (isset($feed['likes']['summary']['total_count'])){
        		if (!isset($likesCount[$feed['likes']['summary']['total_count']])){
        			$likesCount[$feed['likes']['summary']['total_count']] = 0;
        		}
        		$likesCount[$feed['likes']['summary']['total_count']] += 1;
        		$likesSum += $feed['likes']['summary']['total_count'];
        	}
        	if (isset($feed['comments']['summary']['total_count'])){
        		if (!isset($commentsCount[$feed['comments']['summary']['total_count']])){
        			$commentsCount[$feed['comments']['summary']['total_count']] = 0;
        		}
        		$commentsCount[$feed['comments']['summary']['total_count']] += 1;
        		$commentsSum += $feed['comments']['summary']['total_count'];
        	}
        	// $i++;
        	// if ($i > 10){
        	// 	break;
        	// }
        }
        ksort($likesCount); ksort($commentsCount);
        print_r($likesCount);
        print_r($commentsCount);
    }
}