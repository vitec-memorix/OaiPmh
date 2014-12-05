<?php
/**
 * Created by PhpStorm.
 * User: jeroen
 * Date: 12/1/14
 * Time: 11:34 AM
 */

namespace Picturae\OAI\Tests;


use Picturae\OAI\Exception\BadResumptionTokenException;
use Picturae\OAI\Exception\IdDoesNotExistException;
use Picturae\OAI\Interfaces\MetadataFormat;
use Picturae\OAI\Record;
use Picturae\OAI\Record\Header;
use Picturae\OAI\RecordList;
use Picturae\OAI\Repository\Identity;
use Picturae\OAI\Response;
use Picturae\OAI\Set;
use Picturae\OAI\SetList;

class ProviderTest extends \PHPUnit_Framework_TestCase
{
    private function getProvider()
    {
        $mock = $this->getRepo();
        return new \Picturae\OAI\Provider($mock);
    }

    public function testNoVerb()
    {
        $repo = $this->getProvider();
        $response = $repo->execute();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badVerb']");
    }

    public function testBadVerb()
    {
        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'badverb']);
        $response = $repo->execute();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badVerb']");
    }

    public function testMultipleVerbs()
    {
        $repo = $this->getProvider();
        $repo->setRequest(['verb' => ['verb1', 'verb2']]);
        $response = $repo->execute();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badVerb']");
    }

    public function testBadArguments()
    {
        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'Identify', 'nonExistingArg' => '1', 'nonExistingArg2' => '1']);
        $response = $repo->execute();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument'][1]");
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument'][2]");
    }

    private function assertXPathExists(Response $response, $query)
    {
        $document = $response->getDocument();
        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace("oai", 'http://www.openarchives.org/OAI/2.0/');

        $this->assertGreaterThan(
            0,
            $xpath->query($query)->length,
            "Didn't find expected element $query:\n" . $response->getDocument()->saveXML()
        );
    }

    private function assertXPathNotExists(Response $response, $query)
    {
        $document = $response->getDocument();
        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace("oai", 'http://www.openarchives.org/OAI/2.0/');

        $this->assertEquals(
            0,
            $xpath->query($query)->length,
            "Found elements using query $query:\n" . $response->getDocument()->saveXML()
        );
    }

    private function assertValidResponse(Response $response)
    {
        $schemaLocation = $response->getDocument()->documentElement->getAttributeNS(
            'http://www.w3.org/2001/XMLSchema-instance',
            'schemaLocation'
        );
        $xsd = explode(" ", $schemaLocation)[1];

        try {
            $this->assertTrue($response->getDocument()->schemaValidate($xsd));
        } catch (\Exception $e) {
            $this->fail($e->getMessage() . " in:\n" . $response->getDocument()->saveXML());
        }
    }

    public function testIdentify()
    {
        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'Identify']);
        $response = $repo->execute();

        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:Identify");
        $this->assertValidResponse($response);
    }

    public function testListMetadataFormats()
    {
        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'ListMetadataFormats']);
        $response = $repo->execute();


        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:ListMetadataFormats");
        $this->assertValidResponse($response);
    }

    public function testListMetadataFormatsWithIdentifier()
    {
        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'ListMetadataFormats', 'identifier' => 'a']);
        $response = $repo->execute();

        $this->assertValidResponse($response);

        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'ListMetadataFormats', 'identifier' => 'b']);
        $response = $repo->execute();

        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='noMetadataFormats']");
        $this->assertValidResponse($response);

        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'ListMetadataFormats', 'identifier' => 'c']);
        $response = $repo->execute();
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='idDoesNotExist']");

        $this->assertValidResponse($response);
    }

    public function testListSets()
    {
        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'ListSets']);
        $response = $repo->execute();

        $this->assertValidResponse($response);

        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'ListSets', 'resumptionToken' => 'a']);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:ListSets/oai:set");
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:ListSets/oai:resumptionToken");
        $response = $repo->execute();

        $this->assertValidResponse($response);

        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'ListSets', 'resumptionToken' => 'b']);
        $response = $repo->execute();

        $this->assertValidResponse($response);
        $this->assertXPathNotExists($response, "/oai:OAI-PMH/oai:ListSets/oai:resumptionToken");

        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'ListSets', 'resumptionToken' => 'c']);
        $response = $repo->execute();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badResumptionToken']");
    }

    public function testListRecords()
    {
        //bad date in Y-m-d format
        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'ListRecords', 'from' => '2345-44-56']);
        $response = $repo->execute();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument']");

        //metadata prefix missing
        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'ListRecords', 'from' => '2345-01-01T12:12+00']);
        $response = $repo->execute();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument']");

        //bad date
        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'ListRecords', 'from' => '2345-31-12T12:12:00Z', 'metadataPrefix' => 'oai_pmh']);
        $response = $repo->execute();

        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument']");

        //valid request
        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'ListRecords', 'from' => '2345-01-01T12:12:00Z', 'metadataPrefix' => 'oai_pmh']);
        $response = $repo->execute();

        $this->assertXPathNotExists($response, "/oai:OAI-PMH/oai:error");

        //do a request with an invalid date
        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'ListRecords', 'from' => '2345-31-12', 'metadataPrefix' => 'oai_pmh']);
        $response = $repo->execute();

        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument']");
        $this->assertValidResponse($response);
    }

    public function testGetRecord(){
        //no identifier
        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'GetRecord', 'metadataPrefix' => 'oai_dc']);
        $response = $repo->execute();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument']");

        //no metadataPrefix
        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'GetRecord', 'identifier' => 'a']);
        $response = $repo->execute();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument']");

        //valid request
        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'GetRecord', 'metadataPrefix' => 'oai_dc', 'identifier' => 'a']);
        $response = $repo->execute();

        $this->assertValidResponse($response);
        $this->assertXPathNotExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument']");

        //valid request
        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'GetRecord', 'metadataPrefix' => 'oai_dc', 'identifier' => 'b']);
        $response = $repo->execute();

        $this->assertValidResponse($response);
        $this->assertXPathNotExists($response, "/oai:OAI-PMH/oai:error[@code='IdDoesNotExistException']");
    }

    public function testListIdentifiers()
    {
        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'ListIdentifiers', 'from' => '2345-44-56']);
        $response = $repo->execute();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument']");

        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'ListIdentifiers', 'from' => '2345-31-12']);
        $response = $repo->execute();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument']");

        //we don't allow ++00
        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'ListIdentifiers', 'from' => '2345-01-01T12:12:00+00']);
        $response = $repo->execute();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument']");

        //valid request
        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'ListIdentifiers', 'from' => '2345-01-01T12:12:00Z']);
        $response = $repo->execute();

        $this->assertValidResponse($response);
        $this->assertXPathNotExists($response, "/oai:OAI-PMH/oai:error");
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getRepo()
    {
        $mock = $this->getMockBuilder('\Picturae\OAI\Interfaces\Repository')->getMock();

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
                    'http://example.com',
                    new \DateTime(),
                    \Picturae\OAI\Interfaces\Repository\Identity::DELETED_RECORD_PERSISTENT,
                    ["email@example.com"],
                    \Picturae\OAI\Interfaces\Repository\Identity::GRANULARITY_YYYY_MM_DDTHH_MM_SSZ,
                    'gzip'
                )
            )
        );

        $listFormats = function ($identifier = null) {
            switch ($identifier) {
                case "a":
                case null:
                    return [
                        new MetadataFormat(
                            "oai_dc",
                            'http://www.openarchives.org/OAI/2.0/oai_dc.xsd',
                            'http://www.openarchives.org/OAI/2.0/oai_dc/'
                        ),
                        new MetadataFormat(
                            "olac",
                            'http://www.language-archives.org/OLAC/olac-0.2.xsd',
                            'http://www.language-archives.org/OLAC/0.2/'
                        ),
                    ];
                case "b":
                    return [];
                case "c":
                    throw new IdDoesNotExistException();
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
                        throw new BadResumptionTokenException();
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
                 xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/oai_dc/ http://www.openarchives.org/OAI/2.0/oai_dc.xsd">
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
        $recordList = new RecordList(
            [
                $someRecord,
            ],
            'resumptionToken'
        );

        $mock->expects($this->any())->method('listRecords')->will(
            $this->returnValue($recordList)
        )->with();



        $getRecords = function ($identifier = null) use ($someRecord) {
            switch ($identifier) {
                case "a":
                    return $someRecord;
                default:
                    throw new IdDoesNotExistException();
            }
        };

        $mock->expects($this->any())->method('getRecord')->will(
            $this->returnCallback($getRecords)
        )->with();

        return $mock;
    }
} 