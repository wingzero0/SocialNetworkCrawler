<?php
/**
 * Created by PhpStorm.
 * User: CodingGuys
 * Date: 5/29/2016
 * Time: 9:31 AM
 */

namespace CodingGuys\FbRepo;


class FbPageDeltaRepo extends FbRepo
{
    /**
     * @param \MongoId $pageId
     * @return \MongoCursor
     */
    public function findByPageId(\MongoId $pageId)
    {
        $col = $this->getFbDM()->getPageDeltaCollection();
        $query = array(
            "fbPage.\$id" => $pageId
        );
        return $col->find($query);
    }
}