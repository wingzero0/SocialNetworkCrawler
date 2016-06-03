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
     * @param $fbDM
     */
    public function __construct(FbDocumentManager $fbDM = null)
    {
        if ($fbDM != null)
        {
            $this->fbDM = $fbDM;
        } else
        {
            $this->fbDM = new FbDocumentManager();
        }
    }

    /**
     * @return FbDocumentManager
     */
    protected function getFbDM()
    {
        return $this->fbDM;
    }
}