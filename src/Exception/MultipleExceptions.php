<?php
/**
 * Created by PhpStorm.
 * User: jeroen
 * Date: 12/1/14
 * Time: 2:27 PM
 */

namespace Picturae\OAI\Exception;


use Picturae\OAI\Exception;
use Traversable;

class MultipleExceptions extends Exception implements \IteratorAggregate
{
    private $exceptions = [];

    public function setExceptions($exceptions)
    {
        $this->exceptions = $exceptions;
        return $this;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->exceptions);
    }


} 