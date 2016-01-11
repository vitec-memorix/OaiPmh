[![Latest Version](https://img.shields.io/packagist/v/picturae/oai-pmh.svg)](https://packagist.org/packages/picturae/oai-pmh)
[![Build Status](https://travis-ci.org/picturae/OaiPmh.svg?branch=develop)](https://travis-ci.org/picturae/OaiPmh)
[![Coverage Status](https://coveralls.io/repos/picturae/OaiPmh/badge.svg?service=github)](https://coveralls.io/github/picturae/OaiPmh)
[![Total Downloads](https://img.shields.io/packagist/dt/picturae/oai-pmh.svg)](https://packagist.org/packages/picturae/oai-pmh)

# Description

Provides an wrapper to produce a OAI-PMH endpoint.

# Repository

To use the OAI-PMH Library it is required to make your own implementation of
the \Picturae\OaiPmh\Interfaces\Repository interface

Below is an example implementation needs to be modified to return your data

```php
<?php

namespace My\Repository;

use DateTime;
use OpenSkos2\OaiPmh\Concept as OaiConcept;
use Picturae\OaiPmh\Exception\IdDoesNotExistException;
use Picturae\OaiPmh\Implementation\MetadataFormatType as ImplementationMetadataFormatType;
use Picturae\OaiPmh\Implementation\RecordList as OaiRecordList;
use Picturae\OaiPmh\Implementation\Repository\Identity as ImplementationIdentity;
use Picturae\OaiPmh\Implementation\Set;
use Picturae\OaiPmh\Implementation\SetList;
use Picturae\OaiPmh\Interfaces\MetadataFormatType;
use Picturae\OaiPmh\Interfaces\Record;
use Picturae\OaiPmh\Interfaces\RecordList;
use Picturae\OaiPmh\Interfaces\Repository as InterfaceRepository;
use Picturae\OaiPmh\Interfaces\Repository\Identity;
use Picturae\OaiPmh\Interfaces\SetList as InterfaceSetList;

class Repository implements InterfaceRepository
{
    /**
     * @return string the base URL of the repository
     */
    public function getBaseUrl()
    {
        return 'http://my-data-provider/oai-pmh';
    }

    /**
     * @return Identity
     */
    public function identify()
    {
        return new ImplementationIdentity();
    }

    /**
     * @return InterfaceSetList
     */
    public function listSets()
    {
        $items = [];
        $items[] = new Set('my:spec', 'Title of spec');        
        return new SetList($items);
    }

    /**
     * @param string $token
     * @return InterfaceSetList
     */
    public function listSetsByToken($token)
    {
        $params = $this->decodeResumptionToken($token);
        return $this->listSets();
    }

    /**
     * @param string $metadataFormat
     * @param string $identifier
     * @return Record
     */
    public function getRecord($metadataFormat, $identifier)
    {
        // Fetch record
        $record = $this->getSomeRecord($identifier);

        // Throw exception if it does not exists
        if (!record) {
            throw new IdDoesNotExistException('No matching identifier ' . $identifier, $exc->getCode(), $exc);
        }
        
        return new Record($record);
    }

    /**
     * @param string $metadataFormat metadata format of the records to be fetch or null if only headers are fetched
     * (listIdentifiers)
     * @param DateTime $from
     * @param DateTime $until
     * @param string $set name of the set containing this record
     * @return RecordList
     */
    public function listRecords($metadataFormat = null, DateTime $from = null, DateTime $until = null, $set = null)
    {        
        $items = [];
        $items[] = new OaiConcept($concept);

        // Show token only if more records exists then are shown
        $token = $this->encodeResumptionToken($this->limit, $from, $until, $metadataFormat, $set);
        
        return new OaiRecordList($items, $token);
    }

    /**
     * @param string $token
     * @return RecordList
     */
    public function listRecordsByToken($token)
    {
        $params = $this->decodeResumptionToken($token);

        $records = $this->GetRecords($params);

        // Only show if there are more records available else $token = null;
        $token = $this->encodeResumptionToken(
            $params['offset'] + 100,
            $params['from'],
            $params['until'],
            $params['metadataPrefix'],
            $params['set']
        );    

        return new OaiRecordList($items, $token);
    }

    /**
     * @param string $identifier
     * @return MetadataFormatType[]
     */
    public function listMetadataFormats($identifier = null)
    {
        $formats = [];
        $formats[] = new ImplementationMetadataFormatType(
            'oai_dc',
            'http://www.openarchives.org/OAI/2.0/oai_dc.xsd',
            'http://www.openarchives.org/OAI/2.0/oai_dc/'
        );

        $formats[] = new ImplementationMetadataFormatType(
            'oai_rdf',
            'http://www.openarchives.org/OAI/2.0/rdf.xsd',
            'http://www.w3.org/2004/02/skos/core#'
        );

        return $formats;
    }    

    /**
     * Decode resumption token
     * possible properties are:
     *
     * ->offset
     * ->metadataPrefix
     * ->set
     * ->from (timestamp)
     * ->until (timestamp)
     *
     * @param string $token
     * @return array
     */
    private function decodeResumptionToken($token)
    {
        $params = (array) json_decode(base64_decode($token));

        if (!empty($params['from'])) {
            $params['from'] = new \DateTime('@' . $params['from']);
        }

        if (!empty($params['until'])) {
            $params['until'] = new \DateTime('@' . $params['until']);
        }

        return $params;
    }

    /**
     * Get resumption token
     *
     * @param int $offset
     * @param DateTime $from
     * @param DateTime $util
     * @param string $metadataPrefix
     * @param string $set
     * @return string
     */
    private function encodeResumptionToken(
        $offset = 0,
        DateTime $from = null,
        DateTime $util = null,
        $metadataPrefix = null,
        $set = null
    ) {
        $params = [];
        $params['offset'] = $offset;
        $params['metadataPrefix'] = $metadataPrefix;
        $params['set'] = $set;
        $params['from'] = null;
        $params['until'] = null;

        if ($from) {
            $params['from'] = $from->getTimestamp();
        }

        if ($util) {
            $params['until'] = $util->getTimestamp();
        }

        return base64_encode(json_encode($params));
    }

    /**
     * Get earliest modified timestamp
     *
     * @return DateTime
     */
    private function getEarliestDateStamp()
    {
        // Fetch earliest timestamp
        return new DateTime();
    }
}

```

# Sending a response

To create a request and send a response you will need something that can create a PSR 7 server request
and emit a PSR 7 response

In this example we use zend-diactoros

```
composer require zendframework/zend-diactoros
```

```php
// Where $repository is an instance of \Picturae\OaiPmh\Interfaces\Repository
$repository = new \Your\Implementation\Repository();
$provider = new \Picturae\OaiPmh\Provider($repository);

$request = Zend\Diactoros\ServerRequestFactory::fromGlobals();
$provider = new Picturae\OaiPmh\Provider($repository, $request);
$response = $provider->getResponse();
// Send PSR 7 Response
(new Zend\Diactoros\Response\SapiEmitter())->emit($response);
```
## Validators

Values for setSpec must be validated yourself before adding them to the header

```php
$header = new \Picturae\OaiPmh\Validator\SetSpecValidator();
$boolean = $header->isValid($value);
````