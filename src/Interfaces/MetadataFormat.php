<?php
/**
 * Created by PhpStorm.
 * User: jeroen
 * Date: 12/1/14
 * Time: 3:07 PM
 */

namespace Picturae\OaiPmh\Interfaces;

class MetadataFormat implements MetadataFormatType
{
    /**
     * @var string
     */
    private $namespace;

    /**
     * @var string
     */
    private $schema;

    /**
     * @var string
     */
    private $prefix;


    /**
     * @param string $prefix
     * @param string $schema
     * @param string $namespace
     */
    public function __construct($prefix, $schema, $namespace)
    {
        $this->namespace = $namespace;
        $this->prefix = $prefix;
        $this->schema = $schema;
    }


    /**
     * @return string
     * The metadataPrefix - a string to specify the metadata format in OAI-PMH requests issued to the repository.
     * metadataPrefix consists of any valid URI unreserved characters. metadataPrefix arguments are used in ListRecords,
     * ListIdentifiers, and GetRecord requests to retrieve records, or the headers of records that include metadata in
     * the format specified by the metadataPrefix;
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @return string
     * The metadata schema URL - the URL of an XML schema to test validity of metadata expressed according to the format
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * @return string The XML namespace URI that is a global identifier of the metadata format.
     */
    public function getNamespace()
    {
        return $this->namespace;
    }
}
