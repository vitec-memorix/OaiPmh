<?php

/*
 * This file is part of Picturae\Oai-Pmh.
 *
 * Picturae\Oai-Pmh is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Picturae\Oai-Pmh is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Picturae\Oai-Pmh.  If not, see <http://www.gnu.org/licenses/>.
 */


namespace Picturae\OaiPmh\Interfaces;

use Picturae\OaiPmh\Interfaces\Repository\Identity;

interface Repository
{
    /**
     * @return string the base URL of the repository
     */
    public function getBaseUrl();
    
    /**
     * @return string
     * the finest harvesting granularity supported by the repository. The legitimate values are
     * YYYY-MM-DD and YYYY-MM-DDThh:mm:ssZ with meanings as defined in ISO8601.
     */
    public function getGranularity();

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
