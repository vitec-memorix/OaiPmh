<?php
/**
 * Created by PhpStorm.
 * User: jsmit
 * Date: 28-11-14
 * Time: 11:02
 */

namespace Picturae\OAI\Interfaces;


use Picturae\OAI\Interfaces\Repository\Identity;

interface Repository
{

    /**
     * @return Identity
     */
    public function identify();

    /**
     * @return SetList
     */
    public function listSets();

    /**
     * @param string $token
     * @return SetList
     */
    public function listSetsByToken($token);

    /**
     * @param string $metadataFormat
     * @param string $identifier
     * @return Record
     */
    public function getRecord($metadataFormat, $identifier);

    /**
     * @param string $metadataFormat metadata format of the records to be fetch or null if only headers are fetched
     * (listIdentifiers)
     * @param \DateTime $from
     * @param \DateTime $until
     * @param string $set name of the set containing this record
     * @return RecordList
     */
    public function listRecords($metadataFormat = null, \DateTime $from = null, \DateTime $until = null, $set = null);

    /**
     * @param string $token
     * @return RecordList
     */
    public function listRecordsByToken($token);

    /**
     * @param string $identifier
     * @return MetadataFormatType[]
     */
    public function listMetadataFormats($identifier = null);
}