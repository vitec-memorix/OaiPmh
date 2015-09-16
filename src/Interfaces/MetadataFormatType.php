<?php
/**
 * Created by PhpStorm.
 * User: jsmit
 * Date: 28-11-14
 * Time: 12:56
 */

namespace Picturae\OaiPmh\Interfaces;

interface MetadataFormatType
{

    /**
     * @return string
     * The metadataPrefix - a string to specify the metadata format in OAI-PMH requests issued to the repository.
     * metadataPrefix consists of any valid URI unreserved characters. metadataPrefix arguments are used in ListRecords,
     * ListIdentifiers, and GetRecord requests to retrieve records, or the headers of records that include metadata in
     * the format specified by the metadataPrefix;
     */
    public function getPrefix();

    /**
     * @return string
     * The metadata schema URL - the URL of an XML schema to test validity of metadata expressed according to the format
     */
    public function getSchema();

    /**
     * @return string The XML namespace URI that is a global identifier of the metadata format.
     */
    public function getNamespace();
}
