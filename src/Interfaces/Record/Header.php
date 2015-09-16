<?php
/**
 * Created by PhpStorm.
 * User: jsmit
 * Date: 28-11-14
 * Time: 14:32
 */

namespace Picturae\OaiPmh\Interfaces\Record;

interface Header
{

    /**
     * @return string
     * the unique identifier of this record
     */
    public function getIdentifier();

    /**
     * @return \DateTime
     * the date of creation, modification or deletion of the record for the purpose of selective harvesting.
     */
    public function getDatestamp();

    /**
     * @return array
     * the set memberships of the item for the purpose of selective harvesting.
     */
    public function getSetSpecs();

    /**
     * @return boolean
     * indicator if the record is deleted, will be converted to status
     */
    public function isDeleted();
}
