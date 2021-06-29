<?php


namespace Http;


use Microsoft\Graph\Core\NationalCloud;
use Microsoft\Graph\Exception\ClientInitialisationException;
use Microsoft\Graph\Http\HttpClientFactory;
use Microsoft\Graph\Http\HttpClientInterface;

class HttpClientFactoryTest extends \PHPUnit\Framework\TestCase
{
    function testNationalCloudWithEmptyString() {
        $this->expectException(ClientInitialisationException::class);
        HttpClientFactory::nationalCloud("");
    }

    function testNationalCloudWithInvalidUrl() {
        $this->expectException(ClientInitialisationException::class);
        HttpClientFactory::nationalCloud("https://www.microsoft.com");
    }

    function testCreateWithNoConfigReturnsDefaultClient() {
        $client = HttpClientFactory::create();
        $this->assertInstanceOf(\GuzzleHttp\Client::class, $client);
    }

    function testCreateWithConfigCreatesClient() {
        $config = [
            "proxy" => "localhost:8000",
            "verify" => false
        ];
        $client = HttpClientFactory::clientConfig($config)::nationalCloud(NationalCloud::GERMANY)::create();
        $this->assertInstanceOf(\GuzzleHttp\Client::class, $client);
    }

    function testCreateAdapterReturnsHttpClientInterface() {
        $adapter = HttpClientFactory::nationalCloud(NationalCloud::US_DOD)::createAdapter();
        $this->assertInstanceOf(HttpClientInterface::class, $adapter);
    }

}
