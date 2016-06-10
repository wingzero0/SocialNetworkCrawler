<?php
/**
 * User: kit
 * Date: 18/06/15
 * Time: 20:22
 */

use CodingGuys\CGFeedStat;

class dailyReport extends PHPUnit_Framework_TestCase
{
    public function testReport()
    {
        $windowSize = 7;


        $endDate = DateTime::createFromFormat(\DateTime::ISO8601, "2015-06-01T08:00:00+0000");
        $startDate = clone $endDate;
        $startDate->sub(new \DateInterval('P' . $windowSize . 'D'));


        $obj = new CGFeedStat($startDate, $endDate, __DIR__ . "/testReport7.csv");
        $obj->timestampSeriesCount();
        $ansArr = $this->fileToArray(__DIR__ . "/fbReport7.csv");
        $testArr = $this->fileToArray(__DIR__ . "/testReport7.csv");

        foreach ($ansArr as $str => $v)
        {
            $exsitsInTest = isset($testArr[$str]);
            if (!$exsitsInTest)
            {
                echo $str . " not exists in test csv\n";
            }
            $this->assertEquals(true, $exsitsInTest);
        }
        foreach ($testArr as $str => $v)
        {
            $exsitsInAns = isset($ansArr[$str]);
            if (!$exsitsInAns)
            {
                echo $str . " not exists in Ans csv\n";
            }
            $this->assertEquals(true, $exsitsInAns);
        }
    }

    private function fileToArray($filename)
    {
        $fp = fopen($filename, "r");
        $this->assertNotEquals(null, $fp);
        $ret = array();
        while ($line = fgets($fp))
        {
            $line = trim($line);
            $ret[$line] = 1;
        }
        fclose($fp);
        return $ret;
    }
}