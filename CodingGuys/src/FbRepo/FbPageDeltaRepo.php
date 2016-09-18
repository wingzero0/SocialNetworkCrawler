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
     * @param \MongoDB\BSON\ObjectID $pageId
     * @return \MongoDB\Driver\Cursor
     */
    public function findByPageId(\MongoDB\BSON\ObjectID $pageId)
    {
        $col = $this->getFbDM()->getPageDeltaCollection();
        $query = array(
            "fbPage.\$id" => $pageId
        );
        return $col->find($query);
    }
}