<?php
/**
 * Created by PhpStorm.
 * User: jeroen
 * Date: 12/1/14
 * Time: 4:11 PM
 */

namespace Picturae\OAI\Record;



use Picturae\OAI\Interfaces\Record\Header as HeaderInterface;

/**
 * Class Header
 * Basic implementation of \Picturae\OAI\Interfaces\Record\Header
 *
 * @package Picturae\OAI\Record
 */
class Header implements HeaderInterface
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var \DateTime
     */
    private $datestamp;

    /**
     * @var string[]
     */
    private $setSpecs;

    /**
     * @var boolean
     */
    private $deleted;

    /**
     * @param string $identifier
     * @param \DateTime $datestamp
     * @param array $setSpecs
     * @param bool $deleted
     */
    public function __construct($identifier, \DateTime $datestamp, $setSpecs = [], $deleted = false)
    {
        $this->identifier = $identifier;
        $this->datestamp = $datestamp;
        $this->setSpecs = $setSpecs;
        $this->deleted = $deleted;
    }


    /**
     * @return string
     * the unique identifier of this record
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return \DateTime
     * the date of creation, modification or deletion of the record for the purpose of selective harvesting.
     */
    public function getDatestamp()
    {
        return $this->datestamp;
    }

    /**
     * @return array
     * the set memberships of the item for the purpose of selective harvesting.
     */
    public function getSetSpecs()
    {
        return $this->setSpecs;
    }

    /**
     * @return boolean
     * indicator if the record is deleted, will be converted to status
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

}