<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 01/01/15
 * Time: 15:19
 */

namespace CodingGuys;
use Facebook\GraphObject;
use Facebook\GraphPage;

class CGSearchResult extends GraphObject{

    /**
     * get the paging object
     * @return CGPaging | null
     */
    public function getPaging(){
        return $this->getProperty("paging", CGPaging::className());
    }
    /**
     * get the pages
     * @return array | null
     */
    public function getPages(){
        return $this->getPropertyAsArray('data',GraphPage::className());
    }
    public function hasNext(){
        $paging = $this->getPaging();
        if ($paging != null) {
            return !($paging->getNext() == null);
        }else {
            return false;
        }
    }
} 