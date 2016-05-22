<?php
/**
 * User: kit
 * Date: 17-May-16
 * Time: 8:32 AM
 */

namespace CodingGuys\Exception;

class ClassTypeException extends \Exception
{
    public function __construct($expectedClassType, $currentVar, \Exception $previous = null)
    {
        if (is_object($currentVar))
        {
            $currentType = get_class($currentVar);
        } else
        {
            $currentType = gettype($currentVar);
        }
        $message = "class type not match: expected class type-" . $expectedClassType .
            " current class type-" . $currentType;

        parent::__construct($message, 0, $previous);
    }
}