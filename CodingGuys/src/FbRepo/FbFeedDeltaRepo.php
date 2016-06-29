<?php
/**
 * Created by PhpStorm.
 * User: CodingGuys
 * Date: 5/25/2016
 * Time: 8:41 AM
 */

namespace CodingGuys\FbRepo;


class FbFeedDeltaRepo extends FbRepo
{
    /**
     * @param \MongoDB\BSON\ObjectID $feedId
     * @return \MongoDB\Driver\Cursor
     */
    public function findByFeedId(\MongoDB\BSON\ObjectID $feedId)
    {
        $col = $this->getFbDM()->getFeedDeltaCollection();
        $query = array(
            "fbFeed.\$id" => $feedId
        );
        return $col->find($query);
    }
}