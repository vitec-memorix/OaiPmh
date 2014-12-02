<?php
/**
 * Created by PhpStorm.
 * User: jeroen
 * Date: 12/1/14
 * Time: 10:19 AM
 */

namespace Picturae\OAI\Exception;


use Picturae\OAI\Exception;

class BadResumptionTokenException extends Exception
{
    public function getErrorName(){
        return "badResumptionToken";
    }
} 