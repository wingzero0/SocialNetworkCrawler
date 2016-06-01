<?php

/**
 * User: kit
 * Date: 24/04/2016
 * Time: 3:32 PM
 */

namespace CodingGuys\Document;

use CodingGuys\Exception\KeyNotExistsException;

class FacebookPage extends BaseObj
{
    private $mnemono;
    private $_id;
    private $fbID;
    private $fbResponse;

    const TARGET_COLLECTION = "FacebookPage";

    const KEY_ID = "_id";
    const KEY_FB_ID = "fbID";
    const KEY_MNEMONO = "mnemono";

    const FIELD_ID = "id";
    const FIELD_FB_ID = "fbId";
    const FIELD_MNEMONO = "mnemono";

    private static $dbMapping = array(
        FacebookPage::KEY_ID => FacebookPage::FIELD_ID,
        FacebookPage::KEY_FB_ID => FacebookPage::FIELD_FB_ID,
        FacebookPage::KEY_MNEMONO => FacebookPage::FIELD_MNEMONO,
    );

    protected function init()
    {
        foreach (FacebookPage::$dbMapping as $field => $dbCol)
        {
            try
            {
                $val = $this->getFromRaw($dbCol);
                $this->{"set" . ucfirst($field)}($val);
            } catch (KeyNotExistsException $e)
            {
                $this->{"set" . ucfirst($field)}(null);
            }
        }
        $this->setFbResponse(array());
    }

    public function toArray()
    {
        $arr = $this->getFbResponse();
        foreach (FacebookPage::$dbMapping as $field => $dbCol)
        {
            $arr[$dbCol] = $this->{"get" . ucfirst($field)}();
        }
        return $arr;
    }

    public function getCollectionName()
    {
        return FacebookPage::TARGET_COLLECTION;
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

    /**
     * @return array
     */
    public function getFbResponse()
    {
        return $this->fbResponse;
    }

    /**
     * @param array $fbResponse
     */
    public function setFbResponse($fbResponse)
    {
        $this->fbResponse = $fbResponse;
    }
}