<?php
/**
 * Created by PhpStorm.
 * User: jeroen
 * Date: 12/1/14
 * Time: 4:11 PM
 */

namespace Picturae\OaiPmh;

use Picturae\OaiPmh\Interfaces\Set as SetInterface;

/**
 * Class SetList
 * Basic implementation of Picturae\OaiPmh\Interfaces\SetList
 *
 * @package Picturae\OaiPmh
 */
class SetList implements \Picturae\OaiPmh\Interfaces\SetList
{
    /**
     * @var string
     */
    private $resumptionToken;

    /**
     * @var SetInterface[]
     */
    private $items;

    /**
     * @param SetInterface[] $items
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
     * @return Set[]
     */
    public function getItems()
    {
        return $this->items;
    }
}
