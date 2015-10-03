<?php
/**
 * User: kit
 */

namespace CodingGuys;
use CodingGuys\MongoFb\CGMongoFb;

class CGDumpFbCollection extends CGMongoFb{
    const TMP_DB_NAME = 'MnemonoDump';
    private $tmpDbName;
    public function __construct($targetDbName = null, $tmpDbName = null){
        parent::__construct($targetDbName);
        if ($tmpDbName == null){
            $this->tmpDbName = CGDumpFbCollection::TMP_DB_NAME;
        }else{
            $this->tmpDbName = $tmpDbName;
        }
    }
    public function getTmpCollection($colName){
        $m = $this->getMongoClient();
        $col = $m->selectCollection($this->tmpDbName, $colName);
        return $col;
    }

    /**
     * @return \MongoDB
     */
    public function getTmpDB(){
        return $this->getMongoClient()->selectDB(CGDumpFbCollection::TMP_DB_NAME);
    }
}