<?php
/**
 * Created by PhpStorm.
 * User: jeroen
 * Date: 12/1/14
 * Time: 10:14 AM
 */

namespace Picturae\OaiPmh;

class Exception extends \InvalidArgumentException
{

    /**
     * @return string
     */
    public function getErrorName()
    {
        return null;
    }
}
