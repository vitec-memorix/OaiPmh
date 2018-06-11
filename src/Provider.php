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

use Picturae\OaiPmh\Exception\BadArgumentException;
use Picturae\OaiPmh\Exception\BadVerbException;
use Picturae\OaiPmh\Exception\MultipleExceptions;
use Picturae\OaiPmh\Exception\NoMetadataFormatsException;
use Picturae\OaiPmh\Exception\NoRecordsMatchException;
use Picturae\OaiPmh\Exception\NoSetHierarchyException;
use Picturae\OaiPmh\Exception\CannotDisseminateFormatException;
use Picturae\OaiPmh\Interfaces\ResultList as ResultListInterface;
use Picturae\OaiPmh\Interfaces\Repository;
use Picturae\OaiPmh\Interfaces\Repository\Identity;
use Picturae\OaiPmh\Interfaces\Record\Header;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Provider
 *
 * @example
 * <code>
 *
 * //create provider object
 * $provider = new Picturae\OaiPmh\Provider($someRepository);
 * //where some $someRepository is an implementation of \Picturae\OaiPmh\Interfaces\Repository
 *
 * // add request variables, this could be just $_GET or $_POST in case of a post but can also come from a different
 * // source
 * $provider->setRequest($get);
 *
 * //run the oai provider this will return a object containing all headers and output
 * $response = $provider->getResponse();
 *
 * //output headers, body and then exit (it is possible to do manipulations before outputting but this is not advised.
 * $response->outputAndExit();
 * </code>
 * @package Picturae\OaiPmh
 */
class Provider
{

    /**
     * @var array containing all verbs and the arguments they except
     */
    private static $verbs = [
        "Identify" => [],
        "ListMetadataFormats" => ['identifier'],
        "ListSets" => ['resumptionToken'],
        "GetRecord" => ['identifier', 'metadataPrefix'],
        "ListIdentifiers" => ['from', 'until', 'metadataPrefix', 'set', 'resumptionToken'],
        "ListRecords" => ['from', 'until', 'metadataPrefix', 'set', 'resumptionToken']
    ];

    /**
     * @var string the verb of the current request
     */
    private $verb;

    /**
     * @var ResponseDocument
     */
    private $response;

    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var array
     */
    private $params = [];

    /**
     * @var ServerRequestInterface
     */
    private $request;

    /**
     * @var array
     */
    private $records = [];

    /**
     * @param Repository $repository
     * @param ServerRequestInterface $request
     */
    public function __construct(Repository $repository, ServerRequestInterface $request = null)
    {
        $this->repository = $repository;

        if ($request) {
            $this->setRequest($request);
        }
    }

    /**
     * @return ServerRequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param ServerRequestInterface $request
     */
    public function setRequest(ServerRequestInterface $request)
    {
        if ($request->getMethod() === 'POST') {
            $this->params = $request->getParsedBody();
        } else {
            $this->params = $request->getQueryParams();
        }
        $this->request = $request;
    }

    /**
     * @param \DateTime $time
     * @return string
     */
    private function toUtcDateTime(\DateTime $time)
    {
        $UTC = new \DateTimeZone("UTC");
        $time->setTimezone($UTC);
        return $time->format('Y-m-d\TH:i:s\Z');
    }

    /**
     * handles the current request
     * @return ResponseInterface
     */
    public function getResponse()
    {
        $this->response = new ResponseDocument();
        $this->response->addElement("responseDate", $this->toUtcDateTime(new \DateTime()));
        $requestNode = $this->response->createElement("request", $this->repository->getBaseUrl());
        $this->response->getDocument()->documentElement->appendChild($requestNode);

        try {
            $this->checkVerb();
            $verbOutput = $this->doVerb();

            // we are sure now that all request variables are correct otherwise an error would have been thrown
            foreach ($this->params as $k => $v) {
                $requestNode->setAttribute($k, $v);
            }

            // the element is only added when everything went fine, otherwise we would add error node(s) in the catch
            // block below
            $this->response->getDocument()->documentElement->appendChild($verbOutput);

            // Shift the records from the records stack and add them to the DOM tree
            // Records proper are always stored in the 'metadata' node
            foreach ($this->response->getDocument()->getElementsByTagName('metadata') as $item) {
                $record = array_shift($this->records);
                $node = $this->response->getDocument()->importNode($record->documentElement, true);
                $item->appendChild($node);
            }
        } catch (MultipleExceptions $errors) {
            //multiple errors happened add all of the to the response
            foreach ($errors as $error) {
                $this->response->addError($error);
            }
        } catch (\Exception $error) {
            //add this error to the response
            if ($error instanceof Exception) {
                $this->response->addError($error);
            } else {
                $this->response->addError(new Exception($error->getMessage()));
            }
        }

        return $this->response->getResponse();
    }

    /**
     * executes the right function for the current verb
     * @return \DOMElement
     * @throws BadVerbException
     */
    private function doVerb()
    {
        switch ($this->verb) {
            case "Identify":
                return $this->identify();
                break;
            case "ListMetadataFormats":
                return $this->listMetadataFormats();
                break;
            case "ListSets":
                return $this->listSets();
                break;
            case "ListRecords":
                return $this->listRecords();
                break;
            case "ListIdentifiers":
                return $this->listIdentifiers();
                break;
            case "GetRecord":
                return $this->getRecord();
                break;
            default:
                //shouldn't be possible to come here because verb was already checked, but just in case
                throw new BadVerbException("$this->verb is not a valid verb");
        }
    }

    /**
     * handles GetRecord requests
     * @return \DOMElement
     * @throws BadArgumentException
     */
    private function getRecord()
    {
        $checks = [
            function () {
                if (!isset($this->params['identifier'])) {
                    throw new BadArgumentException("Missing required argument identifier");
                }
            },
            function () {
                if (!isset($this->params['metadataPrefix'])) {
                    throw new BadArgumentException("Missing required argument metadataPrefix");
                }
                $this->checkMetadataPrefix(
                    $this->params['metadataPrefix'],
                    isset($this->params['identifier']) ? $this->params['identifier'] : null
                );
            }
        ];
        $this->doChecks($checks);

        $record = $this->repository->getRecord($this->params['metadataPrefix'], $this->params['identifier']);
        $recordNode = $this->response->createElement('record');

        $header = $record->getHeader();
        $recordNode->appendChild($this->getRecordHeaderNode($header));

        // Only add metadata and about if the record is not deleted.
        if (!$header->isDeleted()) {
            $recordNode->appendChild($this->response->createElement('metadata'));

            // Push the record itself on the records stack
            array_push($this->records, $record->getMetadata());

            //only add an 'about' node if it's not null
            $about = $record->getAbout();
            if ($about !== null) {
                $recordNode->appendChild($this->response->createElement('about', $about));
            }
        }

        $getRecordNode = $this->response->createElement('GetRecord');
        $getRecordNode->appendChild($recordNode);

        return $getRecordNode;
    }

    /**
     * handles Identify requests
     * @return \DOMElement
     */
    private function identify()
    {
        $identity = $this->repository->identify();
        $identityNode = $this->response->createElement('Identify');

        // create a node for each property of identify
        $identityNode->appendChild($this->response->createElement('repositoryName', $identity->getRepositoryName()));
        $identityNode->appendChild($this->response->createElement('baseURL', $this->repository->getBaseUrl()));
        $identityNode->appendChild($this->response->createElement('protocolVersion', '2.0'));
        foreach ($identity->getAdminEmails() as $email) {
            $identityNode->appendChild($this->response->createElement('adminEmail', $email));
        }
        $identityNode->appendChild(
            $this->response->createElement('earliestDatestamp', $this->toUtcDateTime($identity->getEarliestDatestamp()))
        );
        $identityNode->appendChild($this->response->createElement('deletedRecord', $identity->getDeletedRecord()));
        $identityNode->appendChild($this->response->createElement('granularity', $identity->getGranularity()));
        if ($identity->getCompression()) {
            $identityNode->appendChild($this->response->createElement('compression', $identity->getCompression()));
        }
        if ($identity->getDescription()) {
            $identityNode->appendChild($this->response->createElement('description', $identity->getDescription()));
        }

        return $identityNode;
    }

    /**
     * handles ListMetadataFormats requests
     * @return \DOMElement
     * @throws NoMetadataFormatsException
     */
    private function listMetadataFormats()
    {
        $listNode = $this->response->createElement('ListMetadataFormats');

        $identifier = isset($this->params['identifier']) ? $this->params['identifier'] : null;
        $formats = $this->repository->listMetadataFormats($identifier);

        if (!count($formats)) {
            throw new NoMetadataFormatsException("There are no metadata formats available for the specified item.");
        }

        //create a node for each metadataFormat
        foreach ($formats as $format) {
            $formatNode = $this->response->createElement('metadataFormat');
            $formatNode->appendChild($this->response->createElement("metadataPrefix", $format->getPrefix()));
            $formatNode->appendChild($this->response->createElement("schema", $format->getSchema()));
            $formatNode->appendChild($this->response->createElement("metadataNamespace", $format->getNamespace()));
            $listNode->appendChild($formatNode);
        }
        return $listNode;
    }

    /**
     * checks if the provided verb is correct and if the arguments supplied are allowed for this verb
     * @throws BadArgumentException
     * @throws BadVerbException
     * @throws MultipleExceptions
     */
    private function checkVerb()
    {
        if (!isset($this->params['verb'])) {
            throw new BadVerbException("Verb is missing");
        }

        $this->verb = $this->params['verb'];
        if (is_array($this->verb)) {
            throw new BadVerbException("Only 1 verb allowed, multiple given");
        }
        if (!array_key_exists($this->verb, self::$verbs)) {
            throw new BadVerbException("$this->verb is not a valid verb");
        }

        $requestParams = $this->params;
        unset($requestParams['verb']);

        $errors = [];
        foreach (array_diff_key($requestParams, array_flip(self::$verbs[$this->verb])) as $key => $value) {
            $errors[] = new BadArgumentException(
                "Argument {$key} is not allowed for verb $this->verb. " .
                "Allowed arguments are: " . implode(", ", self::$verbs[$this->verb])
            );
        }
        if (count($errors)) {
            throw (new MultipleExceptions())->setExceptions($errors);
        }

        //if the resumption token is set it should be the only argument
        if (isset($requestParams['resumptionToken']) && count($requestParams) > 1) {
            throw new BadArgumentException("resumptionToken can not be used together with other arguments");
        }
    }

    /**
     * handles ListSets requests
     * @return \DOMElement
     * @throws NoSetHierarchyException
     */
    private function listSets()
    {
        $listNode = $this->response->createElement('ListSets');

        // fetch the sets either by resumption token or without
        if (isset($this->params['resumptionToken'])) {
            $sets = $this->repository->listSetsByToken($this->params['resumptionToken']);
        } else {
            $sets = $this->repository->listSets();
            if (!count($sets->getItems())) {
                throw new NoSetHierarchyException("The repository does not support sets.");
            }
        }

        //create node for all sets
        foreach ($sets->getItems() as $set) {
            $setNode = $this->response->createElement('set');
            $setNode->appendChild($this->response->createElement('setSpec', $set->getSpec()));
            $setNode->appendChild($this->response->createElement('setName', $set->getName()));
            if ($set->getDescription()) {
                $setNode->appendChild($this->response->createElement('setDescription', $set->getDescription()));
            }
            $listNode->appendChild($setNode);
        }

        $this->addResumptionToken($sets, $listNode);

        return $listNode;
    }

    /**
     * handles ListSets Records
     * @return \DOMElement
     * @throws NoSetHierarchyException
     * @throws NoRecordsMatchException
     */
    private function listRecords()
    {
        $listNode = $this->response->createElement('ListRecords');
        if (isset($this->params['resumptionToken'])) {
            $records = $this->repository->listRecordsByToken($this->params['resumptionToken']);
        } else {
            list($metadataPrefix, $from, $until, $set) = $this->getRecordListParams();
            $records = $this->repository->listRecords($metadataPrefix, $from, $until, $set);

            if (!count($records->getItems())) {
                //maybe this is because someone tries to fetch from a set and we don't support that
                if ($set && !count($this->repository->listSets()->getItems())) {
                    throw new NoSetHierarchyException("The repository does not support sets.");
                }
                throw new NoRecordsMatchException(
                    "The combination of the values of the from, until, set and metadataPrefix arguments "
                    . "results in an empty list."
                );
            }
        }

        //create 'record' node for each record with a 'header', 'metadata' and possibly 'about' node
        foreach ($records->getItems() as $record) {
            $recordNode = $this->response->createElement('record');

            $header = $record->getHeader();
            $recordNode->appendChild($this->getRecordHeaderNode($header));

            // Only add metadata and about if the record is not deleted.
            if (!$header->isDeleted()) {
                $recordNode->appendChild($this->response->createElement('metadata'));

                // Push the record itself on the records stack
                array_push($this->records, $record->getMetadata());

                //only add an 'about' node if it's not null
                $about = $record->getAbout();
                if ($about !== null) {
                    $recordNode->appendChild($this->response->createElement('about', $about));
                }
            }

            $listNode->appendChild($recordNode);
        }

        $this->addResumptionToken($records, $listNode);

        return $listNode;
    }

    /**
     * handles ListIdentifiers requests
     * @return \DOMElement
     * @throws NoSetHierarchyException
     * @throws NoRecordsMatchException
     */
    private function listIdentifiers()
    {
        $listNode = $this->response->createElement('ListIdentifiers');
        if (isset($this->params['resumptionToken'])) {
            $records = $this->repository->listRecordsByToken($this->params['resumptionToken']);
        } else {
            list($metadataPrefix, $from, $until, $set) = $this->getRecordListParams();
            $records = $this->repository->listRecords($metadataPrefix, $from, $until, $set);

            if (!count($records->getItems())) {
                //maybe this is because someone tries to fetch from a set and we don't support that
                if ($set && !count($this->repository->listSets()->getItems())) {
                    throw new NoSetHierarchyException("The repository does not support sets.");
                }
                throw new NoRecordsMatchException(
                    "The combination of the values of the from, until, set and metadataPrefix arguments "
                    . "results in an empty list."
                );
            }
        }

        // create 'record' with only headers
        foreach ($records->getItems() as $record) {
            $listNode->appendChild($this->getRecordHeaderNode($record->getHeader()));
        }

        $this->addResumptionToken($records, $listNode);

        return $listNode;
    }

    /**
     * Converts the header of a record to a header node, used for both ListRecords and ListIdentifiers
     * @param Header $header
     * @return \DOMElement
     */
    private function getRecordHeaderNode(Header $header)
    {
        $headerNode = $this->response->createElement('header');
        $headerNode->appendChild($this->response->createElement('identifier', $header->getIdentifier()));
        $headerNode->appendChild(
            $this->response->createElement('datestamp', $this->toUtcDateTime($header->getDatestamp()))
        );
        foreach ($header->getSetSpecs() as $setSpec) {
            $headerNode->appendChild($this->response->createElement('setSpec', $setSpec));
        }
        if ($header->isDeleted()) {
            $headerNode->setAttribute("status", "deleted");
        }
        return $headerNode;
    }

    /**
     * does all the checks in the closures and merge any exceptions into one big exception
     * @param \Closure[] $checks
     * @throws MultipleExceptions
     */
    private function doChecks($checks)
    {
        $errors = [];
        foreach ($checks as $check) {
            try {
                $check();
            } catch (Exception $e) {
                $errors[] = $e;
            }
        }
        if (count($errors)) {
            throw (new MultipleExceptions)->setExceptions($errors);
        }
    }

    /**
     * Converts a date coming from a request param and converts it to a \DateTime
     * @param string $date
     * @return [\DateTime, string]
     * @throws BadArgumentException when the date is invalid or not supplied in the right format
     */
    private function parseRequestDate($date)
    {
        $timezone = new \DateTimeZone("UTC");
        $granularity = null;

        if (preg_match('#^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$#', $date)) {
            $parsedDate = date_create_from_format('Y-m-d\TH:i:s\Z', $date, $timezone);
            $granularity = Identity::GRANULARITY_YYYY_MM_DDTHH_MM_SSZ;
        } elseif (preg_match('#^\d{4}-\d{2}-\d{2}$#', $date)) {
            // Add ! to format to set time to 00:00:00
            $parsedDate = date_create_from_format('!Y-m-d', $date, $timezone);
            $granularity = Identity::GRANULARITY_YYYY_MM_DD;
        } else {
            throw new BadArgumentException("Expected a data in one of the following formats: " .
                Identity::GRANULARITY_YYYY_MM_DDTHH_MM_SSZ . " OR " .
                Identity::GRANULARITY_YYYY_MM_DD ." FOUND " . $date);
        }

        $parseResult = date_get_last_errors();
        if (!$parsedDate || ($parseResult['error_count'] > 0) || ($parseResult['warning_count'] > 0)) {
            throw new BadArgumentException("$date is not a valid date");
        }

        return [$parsedDate, $granularity];
    }

    /**
     * Adds a resumptionToken to a a listNode if the is a resumption token otherwise it does nothing
     * @param ResultListInterface $resultList
     * @param \DomElement $listNode
     */
    private function addResumptionToken(ResultListInterface $resultList, $listNode)
    {
        // @TODO Add support for expirationDate

        $resumptionTokenNode = null;

        if ($resultList->getResumptionToken()) {
            $resumptionTokenNode = $this->response->createElement('resumptionToken', $resultList->getResumptionToken());
        } elseif ($resultList->getCompleteListSize() !== null || $resultList->getCursor() !== null) {
            // An empty resumption token with attributes completeListSize and/or cursor.
            $resumptionTokenNode = $this->response->createElement('resumptionToken');
        }

        if ($resultList->getCompleteListSize() !== null) {
            $resumptionTokenNode->setAttribute('completeListSize', $resultList->getCompleteListSize());
        }

        if ($resultList->getCursor() !== null) {
            $resumptionTokenNode->setAttribute('cursor', $resultList->getCursor());
        }

        if ($resumptionTokenNode !== null) {
            $listNode->appendChild($resumptionTokenNode);
        }
    }

    /**
     * Parses request arguments used by both ListIdentifiers and ListRecords
     * @return array
     * @throws BadArgumentException
     */
    private function getRecordListParams()
    {
        $metadataPrefix = null;
        $from = null;
        $until = null;
        $fromGranularity = null;
        $untilGranularity  = null;
        $set = isset($this->params['set']) ? $this->params['set'] : null;


        $checks = [
            function () use (&$from, &$fromGranularity) {
                if (isset($this->params['from'])) {
                    list($from, $fromGranularity)  = $this->parseRequestDate($this->params['from']);
                }
            },
            function () use (&$until, &$untilGranularity) {
                if (isset($this->params['until'])) {
                    list($until, $untilGranularity) = $this->parseRequestDate($this->params['until']);
                }
            },
            function () use (&$from, &$until) {
                if ($from !== null and $until !== null && $from > $until) {
                    throw new BadArgumentException(
                        'The `from` argument must be less than or equal to the `until` argument'
                    );
                }
            },
            function () use (&$from, &$until, &$fromGranularity, &$untilGranularity) {
                if ($from !== null and $until !== null && $fromGranularity !== $untilGranularity) {
                    throw new BadArgumentException('The `from` and `until` arguments have different granularity');
                }
            },
            function () use (&$fromGranularity) {
                if ($fromGranularity !== null &&
                    $fromGranularity === Identity::GRANULARITY_YYYY_MM_DDTHH_MM_SSZ &&
                    $this->repository->getGranularity() === Identity::GRANULARITY_YYYY_MM_DD) {
                    throw new BadArgumentException(
                        'The granularity of the `from` argument is not supported by this repository'
                    );
                }
            },
            function () use (&$untilGranularity) {
                if ($untilGranularity !== null &&
                    $untilGranularity === Identity::GRANULARITY_YYYY_MM_DDTHH_MM_SSZ &&
                    $this->repository->getGranularity() === Identity::GRANULARITY_YYYY_MM_DD) {
                    throw new BadArgumentException(
                        'The granularity of the `until` argument is not supported by this repository'
                    );
                }
            },
            function () use (&$metadataPrefix) {
                if (!isset($this->params['metadataPrefix'])) {
                    throw new BadArgumentException("Missing required argument metadataPrefix");
                }
                $metadataPrefix = $this->params['metadataPrefix'];
                if (is_array($metadataPrefix)) {
                    throw new BadArgumentException("Only one metadataPrefix allowed");
                }
                $this->checkMetadataPrefix($metadataPrefix);
            }
        ];

        $this->doChecks($checks);
        return [$metadataPrefix, $from, $until, $set];
    }

    /**
     * Checks if the metadata prefix is in the available metadata formats list.
     * @param string $metadataPrefix
     * @param string $identifier , optional argument that specifies the unique identifier of an item
     * @throws CannotDisseminateFormatException
     */
    private function checkMetadataPrefix($metadataPrefix, $identifier = null)
    {
        $availableMetadataFormats = $this->repository->listMetadataFormats($identifier);

        $found = false;
        if (!empty($availableMetadataFormats)) {
            foreach ($availableMetadataFormats as $metadataFormat) {
                if ($metadataPrefix == $metadataFormat->getPrefix()) {
                    $found = true;
                    break;
                }
            }
        }

        if (!$found) {
            throw new CannotDisseminateFormatException(
                'The metadata format identified by the value given for the metadataPrefix argument '
                . 'is not supported by the item or by the repository.'
            );
        }
    }
}
