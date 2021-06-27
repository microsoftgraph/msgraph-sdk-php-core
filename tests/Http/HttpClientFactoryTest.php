<?php


namespace Http;


use Microsoft\Graph\Core\NationalCloud;
use Microsoft\Graph\Exception\ClientInitialisationException;
use Microsoft\Graph\Http\HttpClientFactory;

class HttpClientFactoryTest extends \PHPUnit\Framework\TestCase
{
    function testNationalCloudWithEmptyString() {
        $this->expectException(ClientInitialisationException::class);
        $clientFactory = (new HttpClientFactory())->nationalCloud("");
    }

    function testNationalCloudWithInvalidUrl() {
        $this->expectException(ClientInitialisationException::class);
        $clientFactory = (new HttpClientFactory())->nationalCloud("https://www.microsoft.com");
    }

    function testCreateWithNoConfigReturnsDefaultClient() {
        $client = (new HttpClientFactory())->create();
        $this->assertInstanceOf(\GuzzleHttp\Client::class, $client);
    }

    function testCreateWithConfigCreatesClient() {
        $config = [
            "proxy" => "localhost:8000",
            "verify" => false
        ];
        $client = (new HttpClientFactory())->httpClientConfig($config)
                                            ->nationalCloud(NationalCloud::GERMANY)
                                            ->create();
        $this->assertInstanceOf(\GuzzleHttp\Client::class, $client);
    }

}
