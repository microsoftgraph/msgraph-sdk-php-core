<?php

namespace Microsoft\Graph\Test\Http\Request;

use Microsoft\Graph\Core\GraphConstants;
use Microsoft\Graph\Core\NationalCloud;
use Microsoft\Graph\Exception\GraphClientException;
use Microsoft\Graph\Http\AbstractGraphClient;
use Microsoft\Graph\Http\GraphRequest;
use Microsoft\Graph\Test\Http\TestModel;
use Microsoft\Graph\Test\TestData\Model\User;

class GraphRequestTest extends BaseGraphRequestTest
{
    public function testConstructorWithNullParametersThrowsException(): void {
        $this->expectException(\TypeError::class);
        $request = new GraphRequest(null, null, null);
    }

    public function testConstructorWithEmptyParametersThrowsException(): void {
        $this->expectException(GraphClientException::class);
        $request = new GraphRequest("", "", $this->mockGraphClient);
    }

    public function testConstructorWithoutAccessTokenThrowsException(): void {
        $graphClient = $this->getMockForAbstractClass(AbstractGraphClient::class);
        $this->expectException(GraphClientException::class);
        $request = new GraphRequest("GET", "/me", $graphClient);
    }

    public function testConstructorWithInvalidCustomBaseUrlThrowsException(): void {
        $this->expectException(GraphClientException::class);
        $baseUrl = "www.outlook.com"; # no scheme
        $request = new GraphRequest("GET", "/me", $this->mockGraphClient, $baseUrl);
    }

    public function testConstructorWithAccessTokenCreatesGraphRequest(): void {
        $request = new GraphRequest("GET", "/me", $this->mockGraphClient);
        $this->assertInstanceOf(GraphRequest::class, $request);
    }

    public function testConstructorWithValidCustomBaseUrlCreatesGraphRequest(): void {
        $baseUrl = "https://www.onedrive.com";
        $request = new GraphRequest("GET", "/me", $this->mockGraphClient, $baseUrl);
        $this->assertInstanceOf(GraphRequest::class, $request);
    }

    public function testConstructorSetsExpectedRequestUri(): void {
        $apiVersion = $this->mockGraphClient->getApiVersion();
        // Sample baseUrls, endpoints and the final expected url
        $baseUrlEndpointCombis = [
            [NationalCloud::GLOBAL, "/me", NationalCloud::GLOBAL."/".$apiVersion."/me"],
            [NationalCloud::GLOBAL, "me/users/", NationalCloud::GLOBAL."/".$apiVersion."/me/users/"],
            [NationalCloud::GLOBAL, "me/users?\$count=true", NationalCloud::GLOBAL."/".$apiVersion."/me/users?\$count=true"],
            [NationalCloud::GLOBAL."/beta", "/me/users", NationalCloud::GLOBAL."/".$apiVersion."/me/users"],
            [NationalCloud::GLOBAL."/beta/", "me/users", NationalCloud::GLOBAL."/".$apiVersion."/me/users"]
        ];

        foreach ($baseUrlEndpointCombis as $combi) {
            $this->mockGraphClient->method('getNationalCloud')
                                    ->willReturn($combi[0]);
            $endpoint = $combi[1];
            $expectedRequestUrl = $combi[2];

            $request = new GraphRequest("GET", $endpoint, $this->mockGraphClient);
            $this->assertEquals($expectedRequestUrl, strval($request->getRequestUri()));
        }
    }

    public function testConstructorSetsExpectedRequestUriGivenValidCustomBaseUrl(): void {
        $baseUrl = "https://www.onedrive.com";
        $request = new GraphRequest("GET", "/me", $this->mockGraphClient, $baseUrl);
        $expectedUrl = $baseUrl."/me";
        $this->assertEquals($expectedUrl, strval($request->getRequestUri()));
    }

    public function testConstructorSetsExpectedRequestUriGivenValidFullEndpointUri(): void {
        $endpoint = NationalCloud::GLOBAL."/v1.0/me/users?\$top=10&\$skip=500";
        $request = new GraphRequest("GET", $endpoint, $this->mockGraphClient);
        $expectedUrl = $endpoint;
        $this->assertEquals($expectedUrl, strval($request->getRequestUri()));
    }

    public function testConstructorGivenInvalidFullEndpointUriAppendsItToDefaultBaseUrl(): void {
        $invalidEndpoint = "http/microsoft.com:localhost\$endpoint"; # Not https
        $request = new GraphRequest("GET", $invalidEndpoint, $this->mockGraphClient);
        $expected = NationalCloud::GLOBAL."/".$this->mockGraphClient->getApiVersion()."/".$invalidEndpoint;
        $this->assertEquals($expected, strval($request->getRequestUri()));
    }

    public function testConstructorSetsExpectedHeadersGivenValidGraphBaseUrl(): void {
        $expectedHeaders = [
            'Content-Type' => ['application/json'],
            'SdkVersion' => ["graph-php-core/".GraphConstants::SDK_VERSION.", Graph-php-".$this->mockGraphClient->getSdkVersion()],
            'Authorization' => ['Bearer ' . $this->mockGraphClient->getAccessToken()],
            'Host' => [substr($this->mockGraphClient->getNationalCloud(), strlen("https://"))]
        ];
        $request = new GraphRequest("GET", "/me", $this->mockGraphClient);
        $this->assertEquals($expectedHeaders, $request->getHeaders());
    }

    public function testConstructorSetsExpectedHeadersGivenValidCustomBaseUrl(): void {
        $baseUrl = "https://www.outlook.com";
        $expectedHeaders = [
            'Content-Type' => ['application/json'],
            'Host' => [substr($baseUrl, strlen("https://"))]
        ];
        $request = new GraphRequest("GET", "/me", $this->mockGraphClient, $baseUrl);
        $this->assertEquals($expectedHeaders, $request->getHeaders());
    }

    public function testConstructorSetsExpectedHeadersGivenGraphEndpointUrl(): void {
        $endpoint = "https://graph.microsoft.com/v1.0/me/users\$skip=10&\$top=5";
        $expectedHeaders = [
            'Content-Type' => ['application/json'],
            'SdkVersion' => ["graph-php-core/".GraphConstants::SDK_VERSION.", Graph-php-".$this->mockGraphClient->getSdkVersion()],
            'Authorization' => ['Bearer ' . $this->mockGraphClient->getAccessToken()],
            'Host' => [substr($this->mockGraphClient->getNationalCloud(), strlen("https://"))]
        ];
        $request = new GraphRequest("GET", $endpoint, $this->mockGraphClient);
        $this->assertEquals($expectedHeaders, $request->getHeaders());

    }

    public function testConstructorSetsExpectedHeadersGivenNonGraphEndpointUrl(): void {
        $endpoint = "https://www.outlook.com/messages";
        $expectedHeaders = [
            'Content-Type' => ['application/json'],
            'Host' => ["www.outlook.com"]
        ];
        $request = new GraphRequest("GET", $endpoint, $this->mockGraphClient);
        $this->assertEquals($expectedHeaders, $request->getHeaders());
    }

    public function testSetAccessTokenReturnsGraphRequestInstance(): void {
        $this->assertInstanceOf(GraphRequest::class, $this->defaultGraphRequest->setAccessToken("123"));
    }

    public function testSetAccessTokenChangesAuthorizationHeaderValue(): void {
        $accessToken = "newAccessToken";
        $this->defaultGraphRequest->setAccessToken($accessToken);
        $expectedHeaderValue = "Bearer ".$accessToken;
        $actualHeaders = $this->defaultGraphRequest->getHeaders()['Authorization'];
        $this->assertEquals(1, sizeof($actualHeaders));
        $this->assertEquals($expectedHeaderValue, $actualHeaders[0]);
    }

    public function testSetReturnTypeReturnsGraphRequestInstance(): void {
        $this->assertInstanceOf(GraphRequest::class, $this->defaultGraphRequest->setReturnType(User::class));
    }

    public function testSetReturnTypeWithInvalidClassThrowsException(): void {
        $this->expectException(GraphClientException::class);
        $this->defaultGraphRequest->setReturnType("Model\User");
    }

    public function testSetReturnTypeToGuzzleStreamIsValid(): void {
        $this->assertInstanceOf(GraphRequest::class, $this->defaultGraphRequest->setReturnType("GuzzleHttp\\Psr7\\Stream"));
    }

    public function testAddHeadersReturnsGraphRequestInstance(): void {
        $this->assertInstanceOf(GraphRequest::class, $this->defaultGraphRequest->addHeaders([]));
    }

    public function testAddHeadersCannotAppendOrOverwriteSdkVersionValue(): void {
        $this->expectException(GraphClientException::class);
        $this->defaultGraphRequest->addHeaders([
            'SdkVersion' => 'Version1',
            'Content-Encoding' => 'gzip'
        ]);
    }

    public function testAddHeadersWithStringValueAppendsNewHeader(): void {
        $this->defaultGraphRequest->addHeaders(['Connection' => 'keep-alive']);
        $this->assertEquals(['keep-alive'], $this->defaultGraphRequest->getHeaders()['Connection']);
    }

    public function testAddHeadersWithArrayOfValuesAppendsNewHeaders(): void {
        $values = ['de', 'en', 'fr'];
        $this->defaultGraphRequest->addHeaders(['Accept-Language' => $values]);
        $this->assertEquals($values, $this->defaultGraphRequest->getHeaders()['Accept-Language']);
    }

    public function testAddHeadersWithExistingHeaderNameDoesCaseInsensitiveAppend(): void {
        $this->assertEquals(['application/json'], $this->defaultGraphRequest->getHeaders()['Content-Type']);
        $this->defaultGraphRequest->addHeaders(['conTeNT-tyPe' => 'text']);
        $this->assertEquals(['application/json', 'text'], $this->defaultGraphRequest->getHeaders()['Content-Type']);
    }

    public function testAttachBodyReturnsGraphRequestInstance(): void {
        $instance = $this->defaultGraphRequest->attachBody('');
        $this->assertInstanceOf(GraphRequest::class, $instance);
    }

    public function testAttachBodyWithNullObjectSetsNullStringBody(): void {
        $this->defaultGraphRequest->attachBody(null);
        $this->assertEquals("null", $this->defaultGraphRequest->getBody());
    }

    public function testAttachBodyWithStringSetsStringBody(): void {
        $this->defaultGraphRequest->attachBody("Body");
        $this->assertEquals("Body", $this->defaultGraphRequest->getBody());
    }

    public function testAttachBodyWithObjectSetsJsonSerializedStringBody(): void {
        $model = new User(array("id" => 1, "child" => new User(["id" => 2])));
        $this->defaultGraphRequest->attachBody($model);
        $this->assertEquals('{"id":1,"child":{"id":2}}', $this->defaultGraphRequest->getBody());
    }

    public function testAttachBodyWithArraySetsJsonArrayBody(): void {
        $this->defaultGraphRequest->attachBody(["id" => 1, "name" => "user"]);
        $this->assertEquals('{"id":1,"name":"user"}', $this->defaultGraphRequest->getBody());
    }

    public function testExecuteAsyncWithNullClientUsesGraphClientHttpClient(): void {
        MockHttpClientAsyncResponseConfig::configureWithFulfilledPromise($this->mockHttpClient);
        $this->mockHttpClient->expects($this->once())->method('sendAsyncRequest');
        $this->defaultGraphRequest->executeAsync(null);
    }
}
