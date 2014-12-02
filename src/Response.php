<?php
/**
 * Created by PhpStorm.
 * User: jsmit
 * Date: 28-11-14
 * Time: 15:42
 */

namespace Picturae\OAI;


class Response
{

    /**
     * @var string
     */
    private $output;

    /**
     * @var string[]
     */
    private $headers;

    private $rootNode;

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
     * @return string
     */
    public function getOutput()
    {
        return $this->document->saveXML();
    }

    public function __construct()
    {
        $this->document = new \DOMDocument();
        $this->document->formatOutput = true;
        $this->rootNode = $this->document->createElementNS('http://www.openarchives.org/OAI/2.0/', "oai-pmh:OAI-PMH");
        $this->rootNode->setAttributeNS(
            "http://www.w3.org/2001/XMLSchema-instance",
            'xsi:schemaLocation',
            'http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd'
        );

        $this->document->appendChild($this->rootNode);
    }

    public function addElement($name, $value = null, $nameSpace=null)
    {
        $element = $this->createElement($name, $value, $nameSpace);
        $this->document->documentElement->appendChild($element);
        return $element;
    }

    public function addError(Exception $error)
    {
        $errorNode = $this->addElement("error", $error->getMessage());
        if ($error->getErrorName()) {
            $errorNode->setAttribute("code", $error->getErrorName());
        }
    }

    /**
     * @return \string[]
     */
    public function getHeaders()
    {
        return $this->headers;
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
     * @return $this
     */
    public function printHeaders()
    {
        foreach ($this->headers as $header) {
            header($header);
        }
        return $this;
    }

    /**
     *
     */
    public function outputAndExit()
    {
        $this->printHeaders();
        echo $this->output;
    }

    /**
     * @param $name
     * @param $value
     * @param $nameSpace
     * @return \DOMElement
     */
    public function createElement($name, $value = null, $nameSpace = null)
    {
        $nameSpace = $nameSpace ?: 'http://www.openarchives.org/OAI/2.0/';

        if ($value instanceof \DOMDocument) {
            $element = $this->document->createElementNS($nameSpace, $name, null);
            $node = $this->document->importNode($value->documentElement);
            $element->appendChild($node);
        } else {
            $element = $this->document->createElementNS($nameSpace, $name, $value);
        }
        return $element;
    }
}