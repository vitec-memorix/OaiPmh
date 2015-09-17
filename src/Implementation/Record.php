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
