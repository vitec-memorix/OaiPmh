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


namespace Test\Picturae\OaiPmh;

use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Picturae\OaiPmh\Exception\BadResumptionTokenException;
use Picturae\OaiPmh\Exception\IdDoesNotExistException;
use Picturae\OaiPmh\Implementation\MetadataFormatType;
use Picturae\OaiPmh\Implementation\Record;
use Picturae\OaiPmh\Implementation\Record\Header;
use Picturae\OaiPmh\Implementation\RecordList;
use Picturae\OaiPmh\Implementation\Repository\Identity;
use Picturae\OaiPmh\Implementation\Set;
use Picturae\OaiPmh\Implementation\SetList;
use Psr\Http\Message\ResponseInterface;

class ProviderTest extends TestCase
{
    private function getProvider()
    {
        $mock = $this->getRepo();
        return new \Picturae\OaiPmh\Provider($mock);
    }

    public function testNoVerb()
    {
        $repo = $this->getProvider();
        $response = $repo->getResponse();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badVerb']");
    }

    public function testBadVerb()
    {
        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams(['verb' => 'badverb']));
        $response = $repo->getResponse();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badVerb']");
    }

    public function testMultipleVerbs()
    {
        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams(['verb' => 'badverb']));
        $response = $repo->getResponse();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badVerb']");
    }

    public function testBadArguments()
    {
        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams([
            'verb' => 'Identify',
            'nonExistingArg' => '1',
            'nonExistingArg2' => '1'
        ]));
        $response = $repo->getResponse();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument'][1]");
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument'][2]");
    }

    private function assertXPathExists(ResponseInterface $response, $query)
    {
        $document = new \DOMDocument();
        $document->loadXML($response->getBody());

        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace("oai", 'http://www.openarchives.org/OAI/2.0/');

        $this->assertTrue(
            $this->xpathExists($response, $query),
            "Didn't find expected element $query:\n" . $response->getBody()
        );
    }

    private function assertXPathNotExists(ResponseInterface $response, $query)
    {
        $this->assertTrue(
            !$this->xpathExists($response, $query),
            "Found elements using query $query:\n" . $response->getBody()
        );
    }

    /**
     * @param ResponseInterface $response
     * @param $query
     * @return bool
     */
    private function xpathExists(ResponseInterface $response, $query)
    {
        $document = new \DOMDocument();
        $document->loadXML($response->getBody());

        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace("oai", 'http://www.openarchives.org/OAI/2.0/');
        return $xpath->query($query)->length > 0;
    }

    /**
     * @param ResponseInterface $response
     */
    private function assertValidResponse(ResponseInterface $response)
    {
        $document = new \DOMDocument();
        $document->loadXML($response->getBody());

        $schemaLocation = $document->documentElement->getAttributeNS(
            'http://www.w3.org/2001/XMLSchema-instance',
            'schemaLocation'
        );
        $xsd = explode(" ", $schemaLocation)[1];

        $this->assertRegExp(
            '#^[1-5]\d{2}$#',
            (string)$response->getStatusCode(),
            "invalid status code: " . $response->getStatusCode()
        );

        if ($this->xpathExists($response, '//oai:error')) {
            $this->assertRegExp(
                '#^4\d{2}$#',
                (string)$response->getStatusCode(),
                "Expected some kind of 4xx header found: " . $response->getStatusCode()
            );
        }

        try {
            $this->assertTrue($document->schemaValidate($xsd));
        } catch (\Exception $e) {
            $this->fail($e->getMessage() . " in:\n" . $response->getBody());
        }
    }

    public function testIdentify()
    {
        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams(['verb' => 'Identify']));
        $response = $repo->getResponse();

        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:Identify");
        $this->assertValidResponse($response);
    }


    public function testPost()
    {
        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withParsedBody(['verb' => 'Identify'])->withMethod('POST'));
        $response = $repo->getResponse();

        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:Identify");
        $this->assertValidResponse($response);
    }

    public function testListMetadataFormats()
    {
        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams(['verb' => 'ListMetadataFormats']));
        $response = $repo->getResponse();


        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:ListMetadataFormats");
        $this->assertValidResponse($response);
    }

    public function testListMetadataFormatsWithIdentifier()
    {
        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams([
            'verb' => 'ListMetadataFormats',
            'identifier' => 'a'
        ]));
        $response = $repo->getResponse();

        $this->assertValidResponse($response);

        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams([
            'verb' => 'ListMetadataFormats',
            'identifier' => 'b'
        ]));
        $response = $repo->getResponse();

        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='noMetadataFormats']");
        $this->assertValidResponse($response);

        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams([
            'verb' => 'ListMetadataFormats',
            'identifier' => 'c'
        ]));
        $response = $repo->getResponse();
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='idDoesNotExist']");

        $this->assertValidResponse($response);
    }

    public function testListSets()
    {
        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams(['verb' => 'ListSets']));
        $response = $repo->getResponse();

        $this->assertValidResponse($response);

        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams(['verb' => 'ListSets', 'resumptionToken' => 'a']));
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:ListSets/oai:set");
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:ListSets/oai:resumptionToken");
        $response = $repo->getResponse();

        $this->assertValidResponse($response);

        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams(['verb' => 'ListSets', 'resumptionToken' => 'b']));
        $response = $repo->getResponse();

        $this->assertValidResponse($response);
        $this->assertXPathNotExists($response, "/oai:OAI-PMH/oai:ListSets/oai:resumptionToken");

        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams(['verb' => 'ListSets', 'resumptionToken' => 'c']));
        $response = $repo->getResponse();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badResumptionToken']");
    }

    public function testListRecords()
    {
        //bad date in Y-m-d format
        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams(['verb' => 'ListRecords', 'from' => '2345-44-56']));
        $response = $repo->getResponse();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument']");

        //metadata prefix missing
        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams([
            'verb' => 'ListRecords',
            'from' => '2345-01-01T12:12+00'
        ]));
        $response = $repo->getResponse();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument']");

        //metadata prefix wrong
        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams([
            'verb' => 'ListRecords',
            'metadataPrefix' => 'oai_custom',
        ]));
        $response = $repo->getResponse();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='cannotDisseminateFormat']");

        //bad date
        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams([
            'verb' => 'ListRecords',
            'from' => '2345-31-12T12:12:00Z',
            'metadataPrefix' => 'oai_dc'
        ]));
        $response = $repo->getResponse();

        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument']");

        //valid request
        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams([
            'verb' => 'ListRecords',
            'from' => '2345-01-01T12:12:00Z',
            'metadataPrefix' => 'oai_dc'
        ]));
        $response = $repo->getResponse();

        $this->assertXPathNotExists($response, "/oai:OAI-PMH/oai:error");
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:ListRecords/oai:resumptionToken");
        $this->assertXPathExists(
            $response,
            "/oai:OAI-PMH/oai:ListRecords/oai:resumptionToken[@cursor=\"0\"]"
        );
        $this->assertXPathExists(
            $response,
            "/oai:OAI-PMH/oai:ListRecords/oai:resumptionToken[@completeListSize=\"100\"]"
        );

        //do a request with an invalid date
        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams([
            'verb' => 'ListRecords',
            'from' => '2345-31-12',
            'metadataPrefix' => 'oai_dc'
        ]));
        $response = $repo->getResponse();

        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument']");
        $this->assertValidResponse($response);
    }

    public function testGetRecord()
    {
        //no identifier
        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams([
            'verb' => 'GetRecord',
            'metadataPrefix' => 'oai_dc'
        ]));
        $response = $repo->getResponse();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument']");

        //no metadataPrefix
        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams(['verb' => 'GetRecord', 'identifier' => 'a']));
        $response = $repo->getResponse();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument']");

        //metadata prefix wrong
        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams([
            'verb' => 'GetRecord',
            'identifier' => 'a',
            'metadataPrefix' => 'oai_custom',
        ]));
        $response = $repo->getResponse();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='cannotDisseminateFormat']");

        //valid request
        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams([
            'verb' => 'GetRecord',
            'metadataPrefix' => 'oai_dc',
            'identifier' => 'a'
        ]));
        $response = $repo->getResponse();

        // @TODO The schema validation does not work properly in that case.
        // Fix schema validation and enable this test again.
        // @see https://github.com/picturae/OaiPmh/issues/2
//        $this->assertValidResponse($response);

        $this->assertXPathNotExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument']");

        //valid request
        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams([
            'verb' => 'GetRecord',
            'metadataPrefix' => 'oai_dc',
            'identifier' => 'b'
        ]));
        $response = $repo->getResponse();

        $this->assertValidResponse($response);
        $this->assertXPathNotExists($response, "/oai:OAI-PMH/oai:error[@code='IdDoesNotExistException']");
    }

    public function testListIdentifiers()
    {
        //metadata prefix missing
        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams([
            'verb' => 'ListIdentifiers',
        ]));
        $response = $repo->getResponse();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument']");

        //metadata prefix wrong
        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams([
            'verb' => 'ListIdentifiers',
            'metadataPrefix' => 'oai_custom',
        ]));
        $response = $repo->getResponse();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='cannotDisseminateFormat']");

        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams([
            'verb' => 'ListIdentifiers',
            'metadataPrefix' => 'oai_dc',
            'from' => '2345-44-56'
        ]));
        $response = $repo->getResponse();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument']");

        // different form and until granularity in one request
        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams([
            'verb' => 'ListIdentifiers',
            'metadataPrefix' => 'oai_dc',
            'from' => '2012-02-12',
            'until' => '2012-04-12T12:00:00Z'
        ]));
        $response = $repo->getResponse();
        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument']");

        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams([
            'verb' => 'ListIdentifiers',
            'metadataPrefix' => 'oai_dc',
            'from' => '2012-02-12T12:00:00Z',
            'until' => '2012-04-12'
        ]));
        $response = $repo->getResponse();
        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument']");

        // from after until
        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams([
            'verb' => 'ListIdentifiers',
            'metadataPrefix' => 'oai_dc',
            'from' => '2012-06-12',
            'until' => '2012-04-12'
        ]));
        $response = $repo->getResponse();
        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument']");

        // From before until
        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams([
            'verb' => 'ListIdentifiers',
            'metadataPrefix' => 'oai_dc',
            'from' => '2012-02-12',
            'until' => '2012-04-12'
        ]));
        $response = $repo->getResponse();
        $this->assertValidResponse($response);
        $this->assertXPathNotExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument']");

        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams([
            'verb' => 'ListIdentifiers',
            'metadataPrefix' => 'oai_dc',
            'from' => '2345-31-12'
        ]));
        $response = $repo->getResponse();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument']");

        //we don't allow ++00
        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams([
            'verb' => 'ListIdentifiers',
            'metadataPrefix' => 'oai_dc',
            'from' => '2345-01-01T12:12:00+00'
        ]));
        $response = $repo->getResponse();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument']");

        //valid request
        $repo = $this->getProvider();
        $repo->setRequest((new ServerRequest())->withQueryParams([
            'verb' => 'ListIdentifiers',
            'metadataPrefix' => 'oai_dc',
            'from' => '2345-01-01T12:12:00Z'
        ]));
        $response = $repo->getResponse();

        $this->assertValidResponse($response);
        $this->assertXPathNotExists($response, "/oai:OAI-PMH/oai:error");
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:ListIdentifiers/oai:resumptionToken");
        $this->assertXPathExists(
            $response,
            "/oai:OAI-PMH/oai:ListIdentifiers/oai:resumptionToken[@cursor=\"0\"]"
        );
        $this->assertXPathExists(
            $response,
            "/oai:OAI-PMH/oai:ListIdentifiers/oai:resumptionToken[@completeListSize=\"100\"]"
        );
    }

    public function testDeletedRecords()
    {
        $requests = [
            // In list records
            (new ServerRequest())->withQueryParams([
                'verb' => 'ListRecords',
                'metadataPrefix' => 'oai_dc',
                'set' => 'deleted:set',
            ]),

            // In list identifiers
            (new ServerRequest())->withQueryParams([
                'verb' => 'ListIdentifiers',
                'metadataPrefix' => 'oai_dc',
                'set' => 'deleted:set',
            ]),

            // In get record
            (new ServerRequest())->withQueryParams([
                'verb' => 'GetRecord',
                'metadataPrefix' => 'oai_dc',
                'identifier' => 'deleted',
            ]),
        ];

        foreach ($requests as $request) {
            $repo = $this->getProvider();
            $repo->setRequest($request);
            $response = $repo->getResponse();

            $this->assertValidResponse($response);
            $this->assertXPathNotExists($response, "/oai:OAI-PMH/oai:error");
            $this->assertXPathExists($response, "//oai:header[@status='deleted']");
            $this->assertXPathNotExists($response, "//oai:metadata");
            $this->assertXPathNotExists($response, "//oai:about");
        }
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getRepo()
    {
        $mock = $this->getMockBuilder('\Picturae\OaiPmh\Interfaces\Repository')->getMock();

//        $description = new \DOMDocument();
//        $description->loadXML('<eprints
//                     xmlns="http://www.openarchives.org/OAI/1.1/eprints"
//                     xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
//                     xsi:schemaLocation="http://www.openarchives.org/OAI/1.1/eprints
//                     http://www.openarchives.org/OAI/1.1/eprints.xsd">
//                    <content>
//                      <URL>http://memory.loc.gov/ammem/oamh/lcoa1_content.html</URL>
//                      <text>Selected collections from American Memory at the Library
//                            of Congress</text>
//                    </content>
//                    <metadataPolicy/>
//                    <dataPolicy/>
//                    </eprints>'
//        );
        $mock->expects($this->any())->method('identify')->will(
            $this->returnValue(
                new Identity(
                    'testRepo',
                    new \DateTime(),
                    \Picturae\OaiPmh\Interfaces\Repository\Identity::DELETED_RECORD_PERSISTENT,
                    ["email@example.com"],
                    \Picturae\OaiPmh\Interfaces\Repository\Identity::GRANULARITY_YYYY_MM_DDTHH_MM_SSZ,
                    'gzip'
                )
            )
        );

        $listFormats = function ($identifier = null) {
            switch ($identifier) {
                case "a":
                case "deleted":
                case null:
                    return [
                        new MetadataFormatType(
                            "oai_dc",
                            'http://www.openarchives.org/OAI/2.0/oai_dc.xsd',
                            'http://www.openarchives.org/OAI/2.0/oai_dc/'
                        ),
                        new MetadataFormatType(
                            "olac",
                            'http://www.language-archives.org/OLAC/olac-0.2.xsd',
                            'http://www.language-archives.org/OLAC/0.2/'
                        ),
                    ];
                case "b":
                    return [];
                case "c":
                    throw new IdDoesNotExistException(
                        "The value of the identifier argument is unknown or illegal in this repository."
                    );
            }
        };

        $mock->expects($this->any())->method('listMetadataFormats')->will(
            $this->returnCallback($listFormats)
        )->with();


        $setList = new SetList(
            [
                new Set("a", "set A"),
                new Set("b", "set B"),
            ],
            'resumptionToken'
        );

        $mock->expects($this->any())->method('listSets')->will(
            $this->returnValue($setList)
        )->with();

        $mock->expects($this->any())->method('listSetsByToken')->will(
            $this->returnCallback(
                function ($token) use ($setList) {
                    if ($token == "a") {
                        return $setList;
                    } elseif ($token == "a") {
                        return new SetList(
                            [
                                new Set("a", "set A"),
                                new Set("b", "set B"),
                            ]
                        );
                    } else {
                        throw new BadResumptionTokenException(
                            "The value of the resumptionToken argument is invalid or expired."
                        );
                    }
                }
            )
        )->with();

        $recordMetadata = new \DOMDocument();
        $recordMetadata->loadXML(
            '
            <oai_dc:dc
                 xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/"
                 xmlns:dc="http://purl.org/dc/elements/1.1/"
                 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                 xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/oai_dc/
                 http://www.openarchives.org/OAI/2.0/oai_dc.xsd">
                <dc:title>Using Structural Metadata to Localize Experience of
                          Digital Content</dc:title>
                <dc:creator>Dushay, Naomi</dc:creator>
                <dc:subject>Digital Libraries</dc:subject>
                <dc:description>With the increasing technical sophistication of
                    both information consumers and providers, there is
                    increasing demand for more meaningful experiences of digital
                    information. We present a framework that separates digital
                    object experience, or rendering, from digital object storage
                    and manipulation, so the rendering can be tailored to
                    particular communities of users.
                </dc:description>
                <dc:description>Comment: 23 pages including 2 appendices,
                    8 figures</dc:description>
                <dc:date>2001-12-14</dc:date>
            </oai_dc:dc>'
        );

        $someRecord = new Record(new Header("id1", new \DateTime()), $recordMetadata);

        $deletedRecordMetadata = new \DOMDocument();
        $deletedRecordMetadata->loadXML(
            '
            <oai_dc:dc
                 xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/"
                 xmlns:dc="http://purl.org/dc/elements/1.1/"
                 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                 xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/oai_dc/
                 http://www.openarchives.org/OAI/2.0/oai_dc.xsd">
                <dc:title>Deleted record</dc:title>
                <dc:description>A testing deleted record</dc:description>
                <dc:date>2001-01-01</dc:date>
            </oai_dc:dc>'
        );

        $deletedRecord = new Record(
            new Header("deleted", new \DateTime(), [], true),
            $deletedRecordMetadata
        );

        $listRecords = function (
            $metadataFormat = null,
            $from = null,
            $until = null,
            $set = null
        ) use (
            $someRecord,
            $deletedRecord
        ) {
            switch ($set) {
                case 'deleted:set':
                    return new RecordList(
                        [
                            $deletedRecord,
                        ],
                        'resumptionToken',
                        100,
                        0
                    );
                default:
                    return new RecordList(
                        [
                            $someRecord,
                            $deletedRecord,
                        ],
                        'resumptionToken',
                        100,
                        0
                    );
            }
        };

        $mock->expects($this->any())->method('listRecords')->will(
            $this->returnCallback($listRecords)
        )->with();


        $getRecords = function ($metadataFormat = null, $identifier = null) use ($someRecord, $deletedRecord) {
            switch ($identifier) {
                case "a":
                    return $someRecord;
                case "deleted":
                    return $deletedRecord;
                default:
                    throw new IdDoesNotExistException(
                        "The value of the identifier argument is unknown or illegal in this repository."
                    );
            }
        };

        $mock->expects($this->any())->method('getRecord')->will(
            $this->returnCallback($getRecords)
        )->with();

        return $mock;
    }
}
