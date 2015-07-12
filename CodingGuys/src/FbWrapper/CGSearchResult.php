<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 01/01/15
 * Time: 15:19
 */

namespace CodingGuys\FbWrapper;
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
    /**
     * @return boolean
     */
    public function hasNext(){
        $paging = $this->getPaging();
        if ($paging != null) {
            return !($paging->getNext() == null);
        }else {
            return false;
        }
    }
    /**
     * get next page cursor offset
     * @return array | null
     */
    public function getAfter(){
        $paging = $this->getPaging();
        if ($paging instanceof CGPaging){
            echo "pass search result successful.\n";
            echo $paging->getAfter();
            return $paging->getAfter();
        }else{
            echo "can't pass search result.\n";
            return null;
        }
        echo $paging->getAfter();
        return $paging->getAfter();
    }
} 