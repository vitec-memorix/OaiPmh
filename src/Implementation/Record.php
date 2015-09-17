<?php
/**
 * Created by PhpStorm.
 * User: jeroen
 * Date: 12/1/14
 * Time: 4:11 PM
 */

namespace Picturae\OaiPmh\Implementation;

use Picturae\OaiPmh\Interfaces\Record as RecordInterface;
use Picturae\OaiPmh\Interfaces\Record\Header;

/**
 * Class Record
 * Basic implementation of Picturae\OaiPmh\Interfaces\Record
 *
 * @package Picturae\OaiPmh
 */
class Record implements RecordInterface
{
    /**
     * @var Header
     */
    private $header;

    /**
     * @var 'DOMDocument
     */
    private $about;

    /**
     * @var \DOMDocument
     */
    private $metadata;

    /**
     * @param Header $header
     * @param \DOMDocument $metadata
     * @param \DOMDocument $about
     */
    public function __construct(Header $header, \DOMDocument $metadata, \DOMDocument $about = null)
    {
        $this->about = $about;
        $this->header = $header;
        $this->metadata = $metadata;
    }


    /**
     * @return Header
     * contains the unique identifier of the item and properties necessary for selective harvesting.
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @return \DOMDocument|null
     * an optional and repeatable container to hold data about the metadata part of the record. The contents of an about
     * container must conform to an XML Schema. Individual implementation communities may create XML Schema that define
     * specific uses for the contents of about containers.
     */
    public function getAbout()
    {
        return $this->about;
    }

    /**
     * @return \DOMDocument
     * a single manifestation of the metadata from an item
     */
    public function getMetadata()
    {
        return $this->metadata;
    }
}
