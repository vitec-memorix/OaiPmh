<?php
/**
 * Created by PhpStorm.
 * User: jeroen
 * Date: 12/1/14
 * Time: 10:18 AM
 */

namespace Picturae\OAI\Exception;


use Picturae\OAI\Exception;

class BadArgumentException extends Exception
{
    public function getErrorName(){
        return "badArgument";
    }
} 