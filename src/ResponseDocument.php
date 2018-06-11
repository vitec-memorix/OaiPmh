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


namespace Picturae\OaiPmh;

use GuzzleHttp\Psr7\Response;
use Picturae\OaiPmh\Exception\NoRecordsMatchException;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ResponseDocument
 * @internal
 */
class ResponseDocument
{

    /**
     * @var string
     */
    private $output;

    /**
     * @var string[]
     */
    private $headers = ['Content-Type' => 'text/xml; charset=utf8'];

    /**
     * @var string
     */
    private $status = '200';

    /**
     * @var \DOMDocument
     */
    private $document;

    /**
     * @return \DOMDocument
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @param \DOMDocument $document
     */
    public function setDocument($document)
    {
        $this->document = $document;
    }
    /**
     *
     */
    public function __construct()
    {
        $this->document = new \DOMDocument('1.0', 'UTF-8');
        $this->document->formatOutput = true;
        $documentElement = $this->document->createElementNS('http://www.openarchives.org/OAI/2.0/', "OAI-PMH");
        $documentElement->setAttribute('xmlns', 'http://www.openarchives.org/OAI/2.0/');
        $documentElement->setAttributeNS(
            "http://www.w3.org/2001/XMLSchema-instance",
            'xsi:schemaLocation',
            'http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd'
        );

        $this->document->appendChild($documentElement);
    }

    /**
     * @param $name
     * @param string $value
     * @return \DOMElement
     */
    public function addElement($name, $value = null)
    {
        $element = $this->createElement($name, $value);
        $this->document->documentElement->appendChild($element);
        return $element;
    }

    /**
     * adds an error node base on a Exception
     * @param Exception $error
     */
    public function addError(Exception $error)
    {
        $errorNode = $this->addElement("error", $error->getMessage());

        if (!$error instanceof NoRecordsMatchException) {
            $this->status = '400';
        }

        if ($error->getErrorName()) {
            $errorNode->setAttribute("code", $error->getErrorName());
        } else {
            $errorNode->setAttribute("code", "badArgument");
        }
    }

    /**
     * @param \string[] $headers
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }

    /**
     * @param $header string
     * @return $this
     */
    public function addHeader($header)
    {
        $this->headers [] = $header;
        return $this;
    }

    /**
     * @param string $name
     * @param \DOMDocument|string $value
     * @return \DOMElement
     */
    public function createElement($name, $value = null)
    {
        $nameSpace = 'http://www.openarchives.org/OAI/2.0/';
        $element = $this->document->createElementNS($nameSpace, $name, htmlspecialchars($value, ENT_XML1));
        return $element;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return new Response($this->status, $this->headers, $this->document->saveXML());
    }
}
