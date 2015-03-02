<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 01/01/15
 * Time: 16:04
 */

namespace CodingGuys;
use Facebook\GraphObject;

class CGPaging extends GraphObject{
    /**
     * @return string | null
     */
    public function getNext(){
        return $this->getProperty('next');
    }

    /**
     * @return string | null
     */
    public function getPrevious(){
        return $this->getProperty('previous');
    }
} 