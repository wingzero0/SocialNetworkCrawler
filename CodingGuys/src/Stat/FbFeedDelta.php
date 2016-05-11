<?php
/**
 * User: kitlei
 * Date: 11-May-16
 * Time: 8:40 AM
 */

namespace CodingGuys\Stat;


class FbFeedDelta
{
    private $deltaLike;
    private $deltaComment;

    public function __construct($deltaLike, $deltaComment)
    {
        $this->deltaLike = $deltaLike;
        $this->deltaComment = $deltaComment;
    }

    /**
     * @return int
     */
    public function getDeltaLike()
    {
        return $this->deltaLike;
    }

    /**
     * @param int $deltaLike
     */
    public function setDeltaLike($deltaLike)
    {
        $this->deltaLike = $deltaLike;
    }

    /**
     * @return int
     */
    public function getDeltaComment()
    {
        return $this->deltaComment;
    }

    /**
     * @param int $deltaComment
     */
    public function setDeltaComment($deltaComment)
    {
        $this->deltaComment = $deltaComment;
    }
}