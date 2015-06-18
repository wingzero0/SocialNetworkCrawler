<?php
/**
 * User: kit
 * Date: 18/06/15
 * Time: 20:22
 */

use CodingGuys\CGFeedStat;

class dailyReport extends PHPUnit_Framework_TestCase{
    public function testReport(){
        $windowSize = 7;


        $endDate = DateTime::createFromFormat(\DateTime::ISO8601,"2015-06-01T06:00:00+0000");
        $startDate = clone $endDate;
        $startDate->sub(new \DateInterval('P'.$windowSize.'D'));


        $obj = new CGFeedStat($startDate, $endDate, __DIR__ . "/testReport7.csv" );
        $obj->timestampSeriesCount();
        $fpAns = fopen(__DIR__ . "/fbReport7.csv", "r" );
        $this->assertNotEquals(null,$fpAns);
        $fp = fopen(__DIR__ . "/testReport7.csv", "r" );
        $this->assertNotEquals(null,$fpAns);
        $ansStr = null;
        $testStr = null;
        do{
            $this->assertEquals($ansStr, $testStr);
            $ansStr = fgets($fpAns);
            $testStr = fgets($fp);
        }while($ansStr != null && $testStr != null);
        $this->assertEquals($ansStr, $testStr);
        //$obj->timestampSeriesCount();
    }
}