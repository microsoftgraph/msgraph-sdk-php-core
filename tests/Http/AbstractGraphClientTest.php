<?php

namespace Microsoft\Graph\Test\Http;

use Microsoft\Graph\Core\NationalCloud;
use Microsoft\Graph\Exception\GraphClientException;
use Microsoft\Graph\Http\AbstractGraphClient;
use Microsoft\Graph\Http\GraphCollectionRequest;
use Microsoft\Graph\Http\GraphRequest;
use Microsoft\Graph\Http\HttpClientFactory;
use Microsoft\Graph\Http\HttpClientInterface;
use PHPUnit\Framework\TestCase;

class AbstractGraphClientTest extends TestCase {

    private $defaultGraphClient;

    public function setUp(): void {
        $this->defaultGraphClient = $this->getMockForAbstractClass(AbstractGraphClient::class);
    }

    public function testGraphConstructorWithDefaultParams() {
        $this->assertEquals(NationalCloud::GLOBAL, $this->defaultGraphClient->getNationalCloud());
        $this->assertInstanceOf(HttpClientInterface::class, $this->defaultGraphClient->getHttpClient());
    }

    public function testGraphConstructor() {
        $httpClient = HttpClientFactory::createAdapter();
        $graphClient = $this->getMockBuilder(AbstractGraphClient::class)
                            ->setConstructorArgs([NationalCloud::US_DOD, $httpClient])
                            ->getMockForAbstractClass();
        $this->assertInstanceOf(AbstractGraphClient::class, $graphClient);
    }

    public function testConstructorWithNullParams() {
        $graphClient = $this->getMockBuilder(AbstractGraphClient::class)
                            ->setConstructorArgs([null, null])
                            ->getMockForAbstractClass();
        $this->assertEquals(NationalCloud::GLOBAL, $graphClient->getNationalCloud());
        $this->assertInstanceOf(HttpClientInterface::class, $graphClient->getHttpClient());
    }

    public function testConstructorWithInvalidNationalCloud() {
        $this->expectException(\InvalidArgumentException::class);
        $graphClient = $this->getMockBuilder(AbstractGraphClient::class)
                            ->setConstructorArgs(["https://www.microsoft.com", null])
                            ->getMockForAbstractClass();
    }

    public function testSetAndRetrieveAccessToken() {
        $accessToken = "123";
        $result = $this->defaultGraphClient->setAccessToken($accessToken);
        $this->assertInstanceOf(AbstractGraphClient::class, $result);
        $this->assertEquals($accessToken, $this->defaultGraphClient->getAccessToken());
    }

    public function testCreateRequestReturnsGraphRequest() {
        $request = $this->defaultGraphClient->setAccessToken("abc")
                                            ->createRequest("GET", "/me");
        $this->assertInstanceOf(GraphRequest::class, $request);
    }

    public function testCreateRequestWithoutSettingAccessTokenThrowsException() {
        $this->expectException(\InvalidArgumentException::class);
        $request = $this->defaultGraphClient->createRequest("GET", "/");
    }

    public function testCreateRequestWithInvalidParamsThrowsException() {
        $this->expectException(\InvalidArgumentException::class);
        $this->defaultGraphClient->createRequest("", "");
    }

    public function testCreateCollectionRequestReturnsGraphCollectionRequest() {
        $request = $this->defaultGraphClient->setAccessToken("abc")
                                            ->createCollectionRequest("GET", "/me/users");
        $this->assertInstanceOf(GraphCollectionRequest::class, $request);
    }

    public function testCreateCollectionRequestWithoutAccessTokenThrowsException() {
        $this->expectException(\InvalidArgumentException::class);
        $request = $this->defaultGraphClient->createCollectionRequest("GET", "/me/users");
    }

    public function testCreateCollectionRequestWithInvalidParamsThrowsException() {
        $this->expectException(\InvalidArgumentException::class);
        $this->defaultGraphClient->createCollectionRequest("", "");
    }
}
