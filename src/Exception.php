<?php
/**
 * Created by PhpStorm.
 * User: jeroen
 * Date: 12/1/14
 * Time: 10:14 AM
 */

namespace Picturae\OAI;


class Exception extends \InvalidArgumentException {

    public function getErrorName(){
        return null;
    }
} 