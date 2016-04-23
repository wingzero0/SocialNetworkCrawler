<?php
/**
 * User: kit
 * Date: 23/04/2016
 * Time: 6:08 PM
 */

namespace CodingGuys\FbRepo;

use CodingGuys\MongoFb\CGMongoFb;

class FbRepo
{
    private $mongoFb;

    /**
     * FbRepo constructor.
     * @param $dbName
     */
    public function __construct($dbName = null){
        $this->mongoFb = new CGMongoFb($dbName);
    }

    /**
     * @return CGMongoFb
     */
    protected function getMongoFb(){
        return $this->mongoFb;
    }
}