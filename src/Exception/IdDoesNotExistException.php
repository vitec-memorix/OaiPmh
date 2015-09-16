<?php
/**
 * Created by PhpStorm.
 * User: jeroen
 * Date: 12/1/14
 * Time: 10:16 AM
 */

namespace Picturae\OaiPmh\Exception;

use Picturae\OaiPmh\Exception;

class IdDoesNotExistException extends Exception
{
    public function getErrorName()
    {
        return "idDoesNotExist";
    }
}
