<?php
/**
 * User: kit
 * Date: 01/01/15
 * Time: 16:04
 */

namespace CodingGuys\FbWrapper;
use Facebook\GraphObject;
use CodingGuys\FbWrapper\CGCursor;

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

    /**
     * @return string | null
     */
    public function getAfter(){
        $cgCursor = $this->getProperty("cursors", CGCursor::className());
        if ($cgCursor instanceof CGCursor){
            return $cgCursor->getAfter();
        }else{
            return null;
        }
    }
} 