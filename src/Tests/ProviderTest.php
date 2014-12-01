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
use Picturae\OAI\Repository\Identity;
use Picturae\OAI\Response;
use Picturae\OAI\Set;
use Picturae\OAI\SetList;

class ProviderTest extends \PHPUnit_Framework_TestCase
{
    private function getProvider ()
    {
        $mock = $this->getRepo();
        return new \Picturae\OAI\Provider($mock);
    }

    public function testNoVerb(){
        $repo = $this->getProvider();
        $response = $repo->execute();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badVerb']");
    }

    public function testBadVerb(){
        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'badverb']);
        $response = $repo->execute();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badVerb']");
    }

    public function testMultipleVerbs(){
        $repo = $this->getProvider();
        $repo->setRequest(['verb' => ['verb1', 'verb2']]);
        $response = $repo->execute();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badVerb']");
    }

    public function testBadArguments(){
        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'Identify', 'nonExistingArg' => '1', 'nonExistingArg2' => '1']);
        $response = $repo->execute();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument'][1]");
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument'][2]");
    }

    private function assertXPathExists(Response $response, $query){
        $document = $response->getDocument();
        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace("oai", 'http://www.openarchives.org/OAI/2.0/');

        $this->assertGreaterThan(
            0,
            $xpath->query($query)->length,
            "Didn't find expected element $query:\n" . $response->getDocument()->saveXML()
        );
    }

    private function assertXPathNotExists(Response $response, $query){
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
            'http://www.w3.org/2001/XMLSchema-instance', 'schemaLocation'
        );
        $xsd = explode(" ", $schemaLocation)[1];

        try {
            $this->assertTrue($response->getDocument()->schemaValidate($xsd));
        } catch (\Exception $e){
            $this->fail($e->getMessage() . " in:\n" . $response->getDocument()->saveXML());
        }
    }

    public function testIdentify(){
        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'Identify']);
        $response = $repo->execute();

        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:Identify");
        $this->assertValidResponse($response);
    }

    public function testListMetadataFormats(){
        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'ListMetadataFormats']);
        $response = $repo->execute();


        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:ListMetadataFormats");
        $this->assertValidResponse($response);
    }

    public function testListMetadataFormatsWithIdentifier(){
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

    public function testListSets(){
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

    public function testListRecords(){
        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'ListRecords', 'from' => '2345-44-56']);
        $response = $repo->execute();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument']");

        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'ListRecords', 'from' => '2345-31-12']);
        $response = $repo->execute();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument']");

        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'ListRecords', 'from' => '2345-31-12T12:12+00']);
        $response = $repo->execute();

        $this->assertValidResponse($response);
        $this->assertXPathExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument']");

        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'ListRecords', 'from' => '2345-31-12T12:12:00Z']);
        $response = $repo->execute();

        $this->assertValidResponse($response);
        $this->assertXPathNotExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument']");

        $repo = $this->getProvider();
        $repo->setRequest(['verb' => 'ListRecords', 'from' => '2345-31-12']);
        $response = $repo->execute();

        $this->assertValidResponse($response);
        $this->assertXPathNotExists($response, "/oai:OAI-PMH/oai:error[@code='badArgument']");
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
                    "persistent",
                    ["email@example.com"],
                    'YYYY-MM-DD',
                    'gzip'
                )
            )
        );

        $listFormats = function ($identifier = null){
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
                        return  new SetList(
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

        return $mock;
    }
} 