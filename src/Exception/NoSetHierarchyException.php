<?php
/**
 * Created by PhpStorm.
 * User: jeroen
 * Date: 12/1/14
 * Time: 10:20 AM
 */

namespace Picturae\OAI\Exception;


use Picturae\OAI\Exception;

class NoSetHierarchyException extends Exception
{
    public function getErrorName()
    {
        return "noSetHierarchy";
    }
} 