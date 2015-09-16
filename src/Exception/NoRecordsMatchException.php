<?php
/**
 * Created by PhpStorm.
 * User: jeroen
 * Date: 12/1/14
 * Time: 10:19 AM
 */

namespace Picturae\OaiPmh\Exception;

use Picturae\OaiPmh\Exception;

class NoRecordsMatchException extends Exception
{
    public function getErrorName()
    {
        return "noRecordsMatch";
    }
}
