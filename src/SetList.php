<?php
/**
 * Created by PhpStorm.
 * User: jeroen
 * Date: 12/1/14
 * Time: 4:11 PM
 */

namespace Picturae\OAI;


use Picturae\OAI\Interfaces\Set as SetInterface;

class SetList implements \Picturae\OAI\Interfaces\SetList
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
    public function __construct($items, $resumptionToken=null)
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