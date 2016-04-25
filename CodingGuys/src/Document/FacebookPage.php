<?php

/**
 * User: kit
 * Date: 24/04/2016
 * Time: 3:32 PM
 */

namespace CodingGuys\Document;

class FacebookPage{
    private $mnemono;
    private $_id;
    private $fbID;
    private $rawData;
    
    public function __construct($rawData = null){
        if (is_array($rawData) && !empty($rawData) ){
            $this->rawData = $rawData;
            $this->init();
        }else {
            $this->rawData = array();
        }
    }
    
    private function init(){
        $rawData = $this->getRawData();
        if (isset($rawData["mnemono"])){
            $this->setMnemono($rawData["mnemono"]);
        }
        if (isset($rawData["_id"])){
            $this->setId($rawData["_id"]);
        }
        if (isset($rawData["fbID"])){
            $this->setFbID($rawData["fbID"]);
        }
    }

    /**
     * @return array|null
     */
    public function getMnemono()
    {
        $this->mnemono;
        return $this->mnemono;
    }

    /**
     * @param array $mnemono
     */
    public function setMnemono($mnemono)
    {
        $this->mnemono = $mnemono;
    }

    /**
     * @return \MongoId|null
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param \MongoId $id
     */
    public function setId($id)
    {
        $this->_id = $id;
    }
    
    /**
     * @return array
     */
    public function getRawData()
    {
        return $this->rawData;
    }

    /**
     * @param array $rawData
     */
    public function setRawData($rawData)
    {
        $this->rawData = $rawData;
    }

    /**
     * @return string|null
     */
    public function getFbID()
    {
        return $this->fbID;
    }

    /**
     * @param string $fbID
     */
    public function setFbID($fbID)
    {
        $this->fbID = $fbID;
    }
}