<?php
/**
 * Created by PhpStorm.
 * User: jsmit
 * Date: 28-11-14
 * Time: 12:56
 */

namespace Picturae\OaiPmh\Interfaces;

use Picturae\OaiPmh\Interfaces\Record\Header;

interface Record
{

    /**
     * @return Header
     * contains the unique identifier of the item and properties necessary for selective harvesting.
     */
    public function getHeader();

    /**
     * @return \DOMDocument|null
     * an optional and repeatable container to hold data about the metadata part of the record. The contents of an about
     * container must conform to an XML Schema. Individual implementation communities may create XML Schema that define
     * specific uses for the contents of about containers.
     */
    public function getAbout();

    /**
     * @return \DOMDocument
     * a single manifestation of the metadata from an item
     */
    public function getMetadata();
}
