<?php
/**
 * User: kit
 * Date: 23/04/2016
 * Time: 6:08 PM
 */

namespace CodingGuys\FbRepo;

use CodingGuys\FbDocumentManager\FbDocumentManager;

class FbRepo
{
    private $fbDM;

    /**
     * FbRepo constructor.
     * @param $dbName
     */
    public function __construct($dbName = null)
    {
        $this->fbDM = new FbDocumentManager($dbName);
    }

    /**
     * @return FbDocumentManager
     */
    protected function getFbDM()
    {
        return $this->fbDM;
    }
}