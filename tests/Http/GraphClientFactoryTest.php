<?php


namespace Microsoft\Graph\Core\Test\Http;


use Microsoft\Graph\Core\NationalCloud;
use Microsoft\Graph\Core\Http\GraphClientFactory;
use Microsoft\Graph\Core\Http\HttpClientInterface;

class GraphClientFactoryTest extends \PHPUnit\Framework\TestCase
{
    function testNationalCloudWithEmptyString() {
        $this->expectException(\InvalidArgumentException::class);
        GraphClientFactory::setNationalCloud("");
    }

    function testNationalCloudWithInvalidUrl() {
        $this->expectException(\InvalidArgumentException::class);
        GraphClientFactory::setNationalCloud("https://www.microsoft.com");
    }

    function testCreateWithNoConfigReturnsDefaultClient() {
        $client = GraphClientFactory::create();
        $this->assertInstanceOf(\GuzzleHttp\Client::class, $client);
    }

    function testCreateWithConfigCreatesClient() {
        $config = [
            "proxy" => "localhost:8000",
            "verify" => false
        ];
        $client = GraphClientFactory::setNationalCloud(NationalCloud::GERMANY)::createWithConfig($config);
        $this->assertInstanceOf(\GuzzleHttp\Client::class, $client);
    }

    function testCreateAdapterReturnsHttpClientInterface() {
        $adapter = GraphClientFactory::setNationalCloud(NationalCloud::US_DOD)::createAdapter();
        $this->assertInstanceOf(HttpClientInterface::class, $adapter);
    }

}
