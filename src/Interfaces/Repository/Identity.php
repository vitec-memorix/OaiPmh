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


namespace Picturae\OaiPmh\Interfaces\Repository;

interface Identity
{
    const GRANULARITY_YYYY_MM_DD = 'YYYY-MM-DD';
    const GRANULARITY_YYYY_MM_DDTHH_MM_SSZ = 'YYYY-MM-DDThh:mm:ssZ';

    /**
     * The repository does not maintain information about deletions.
     * A repository that indicates this level of support must not reveal a deleted status in any response.
     */
    const DELETED_RECORD_NO = 'no';

    /**
     * The repository maintains information about deletions with no time limit. A repository that indicates this level
     * of support must persistently keep track of the full history of deletions and consistently reveal the status of a
     * deleted record over time.
     */
    const DELETED_RECORD_PERSISTENT = 'persistent';

    /**
     * The repository does not guarantee that a list of deletions is maintained persistently or consistently.
     * A repository that indicates this level of support may reveal a deleted status for records.
     */
    const DELETED_RECORD_TRANSIENT = 'transient';

    /**
     * @return string
     * a human readable name for the repository
     */
    public function getRepositoryName();

    /**
     * @return \DateTime
     * a datetime that is the guaranteed lower limit of all datestamps recording changes,modifications, or deletions
     * in the repository. A repository must not use datestamps lower than the one specified
     * by the content of the earliestDatestamp element. earliestDatestamp must be expressed at the finest granularity
     * supported by the repository.
     */
    public function getEarliestDatestamp();

    /**
     * @return string
     * the manner in which the repository supports the notion of deleted records. Legitimate values are:
     * no
     * transient
     * persistent
     * with meanings defined in the section on deletion.
     */
    public function getDeletedRecord();

    /**
     * @return string
     * the finest harvesting granularity supported by the repository. The legitimate values are
     * YYYY-MM-DD and YYYY-MM-DDThh:mm:ssZ with meanings as defined in ISO8601.
     */
    public function getGranularity();

    /**
     * @return string[] the e-mail address(es) of the administrator(s) of the repository.
     */
    public function getAdminEmails();

    /**
     * @return string|null
     * optional a compression encoding supported by the repository. The recommended values are those
     * defined for the Content-Encoding header in Section 14.11 of RFC 2616 describing HTTP 1.1
     */
    public function getCompression();

    /**
     * @return \DOMDocument|null
     * optional an extensible mechanism for communities to describe their repositories. For
     * example, the description container could be used to include collection-level metadata in the response to the
     * Identify request. Implementation Guidelines are available to give directions with this respect. Each description
     * container must be accompanied by the URL of an XML schema describing the structure of the description container.
     */
    public function getDescription();
}
