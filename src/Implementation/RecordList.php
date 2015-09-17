<?php
/**
 * Created by PhpStorm.
 * User: jeroen
 * Date: 12/1/14
 * Time: 4:11 PM
 */

namespace Picturae\OaiPmh\Implementation;

use Picturae\OaiPmh\Interfaces\Record as RecordInterface;
use Picturae\OaiPmh\Interfaces\RecordList as RecordListInterface;

/**
 * Class RecordList
 * Basic implementation of Picturae\OaiPmh\Interfaces\RecordList
 *
 * @package Picturae\OaiPmh
 */
class RecordList implements RecordListInterface
{
    /**
     * @var string
     */
    private $resumptionToken;

    /**
     * @var Record[]
     */
    private $items;

    /**
     * @param Set[] $items
     * @param null|string $resumptionToken
     */
    public function __construct($items, $resumptionToken = null)
    {
        $this->items = $items;
        $this->resumptionToken = $resumptionToken;
    }


    /**
     * @return string
     */
    public function getResumptionToken()
    {
        return $this->resumptionToken;
    }

    /**
     * @return RecordInterface[]
     */
    public function getItems()
    {
        return $this->items;
    }
}
