<?php
/**
 * User: kit
 * Date: 12/07/15
 * Time: 12:49
 */

namespace CodingGuys\FbWrapper;
use Facebook\GraphObject;

class CGCursor extends GraphObject{
    /**
     * @return string | null
     */
    public function getAfter(){
        echo $this->getProperty('after');
        return $this->getProperty('after');
    }
}