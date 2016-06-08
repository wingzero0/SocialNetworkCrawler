<?php
/**
 * User: kit
 * Date: 19-May-16
 * Time: 8:35 AM
 */

namespace CodingGuys\Document;


use CodingGuys\Exception\KeyNotExistsException;

abstract class BaseObj
{
    private $mongoRawData;

    /**
     * @return array
     */
    public function getMongoRawData()
    {
        return $this->mongoRawData;
    }

    /**
     * @param array $mongoRawData
     */
    public function setMongoRawData($mongoRawData)
    {
        $this->mongoRawData = $mongoRawData;
        $this->init();
    }

    public function __construct($mongoRawData = null)
    {
        if (is_array($mongoRawData) && !empty($mongoRawData))
        {
            $this->setMongoRawData($mongoRawData);
        } else
        {
            $this->mongoRawData = array();
        }
    }

    public function getFromRaw($key)
    {
        $raw = $this->getMongoRawData();
        if (isset($raw[$key]))
        {
            return $raw[$key];
        } else
        {
            throw new KeyNotExistsException();
        }
    }

    public abstract function getId();

    protected abstract function init();

    /**
     * @return array
     */
    public abstract function toArray();

    public abstract function getCollectionName();

    public function filterNonNullValue($value)
    {
        return !($value === null);
    }
}
