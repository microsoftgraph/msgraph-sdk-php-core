<?php

namespace Microsoft\Graph\Core\Core\Test\Http\Request;

use Microsoft\Graph\Core\Core\GraphConstants;
use Microsoft\Graph\Core\Core\NationalCloud;
use Microsoft\Graph\Core\Core\Http\AbstractGraphClient;
use Microsoft\Graph\Core\Core\Http\GraphRequest;
use Microsoft\Graph\Core\Core\Test\TestData\Model\User;

class GraphRequestTest extends BaseGraphRequestTest
{
    public function testConstructorWithNullParametersThrowsException(): void {
        $this->expectException(\TypeError::class);
        $request = new GraphRequest(null, null, null);
    }

    public function testConstructorWithEmptyParametersThrowsException(): void {
        $this->expectException(\InvalidArgumentException::class);
        $request = new GraphRequest("", "", $this->mockGraphClient);
    }

    public function testConstructorWithoutAccessTokenThrowsException(): void {
        $graphClient = $this->getMockForAbstractClass(AbstractGraphClient::class);
        $this->expectException(\InvalidArgumentException::class);
        $request = new GraphRequest("GET", "/me", $graphClient);
    }

    public function testConstructorWithInvalidCustomBaseUrlThrowsException(): void {
        $this->expectException(\InvalidArgumentException::class);
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
            'Content-Type' => 'application/json',
            'SdkVersion' => "graph-php/".$this->mockGraphClient->getSdkVersion().", graph-php-core/".GraphConstants::SDK_VERSION,
            'Authorization' => 'Bearer ' . $this->mockGraphClient->getAccessToken(),
        ];
        $request = new GraphRequest("GET", "/me", $this->mockGraphClient);
        $this->assertEquals($expectedHeaders, $request->getHeaders());
    }

    public function testConstructorSetsExpectedBetaSdkVersionHeader(): void {
        $graphClient = $this->createMock(AbstractGraphClient::class);
        $graphClient->method('getAccessToken')->willReturn("abc");
        $graphClient->method('getNationalCloud')->willReturn(NationalCloud::GLOBAL);
        $graphClient->method('getSdkVersion')->willReturn('2.0.0');
        $graphClient->method('getApiVersion')->willReturn(GraphConstants::BETA_API_VERSION);

        $request = new GraphRequest("GET", "/me", $graphClient);
        $expected = "graph-php-beta/".$this->mockGraphClient->getSdkVersion().", graph-php-core/".GraphConstants::SDK_VERSION;
        $this->assertEquals($expected, $request->getHeaders()["SdkVersion"]);
    }

    public function testConstructorSetsExpectedHeadersGivenValidCustomBaseUrl(): void {
        $baseUrl = "https://www.outlook.com";
        $expectedHeaders = [
            'Content-Type' => 'application/json',
        ];
        $request = new GraphRequest("GET", "/me", $this->mockGraphClient, $baseUrl);
        $this->assertEquals($expectedHeaders, $request->getHeaders());
    }

    public function testConstructorSetsExpectedHeadersGivenGraphEndpointUrl(): void {
        $endpoint = "https://graph.microsoft.com/v1.0/me/users\$skip=10&\$top=5";
        $expectedHeaders = [
            'Content-Type' => 'application/json',
            'SdkVersion' => "graph-php/".$this->mockGraphClient->getSdkVersion().", graph-php-core/".GraphConstants::SDK_VERSION,
            'Authorization' => 'Bearer ' . $this->mockGraphClient->getAccessToken(),
        ];
        $request = new GraphRequest("GET", $endpoint, $this->mockGraphClient);
        $this->assertEquals($expectedHeaders, $request->getHeaders());

    }

    public function testConstructorSetsExpectedHeadersGivenNonGraphEndpointUrl(): void {
        $endpoint = "https://www.outlook.com/messages";
        $expectedHeaders = [
            'Content-Type' => 'application/json',
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
        $this->assertEquals($expectedHeaderValue, $actualHeaders);
    }

    public function testSetReturnTypeReturnsGraphRequestInstance(): void {
        $this->assertInstanceOf(GraphRequest::class, $this->defaultGraphRequest->setReturnType(User::class));
    }

    public function testSetReturnTypeWithInvalidClassThrowsException(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->defaultGraphRequest->setReturnType("Model\User");
    }

    public function testSetReturnTypeToGuzzleStreamIsValid(): void {
        $this->assertInstanceOf(GraphRequest::class, $this->defaultGraphRequest->setReturnType("GuzzleHttp\\Psr7\\Stream"));
    }

    public function testAddHeadersReturnsGraphRequestInstance(): void {
        $this->assertInstanceOf(GraphRequest::class, $this->defaultGraphRequest->addHeaders([]));
    }

    public function testAddHeadersAllowsSameSdkVersionHeader(): void {
        $instance = $this->defaultGraphRequest->addHeaders([
            "SdkVersion" => $this->defaultGraphRequest->getHeaders()["SdkVersion"]
        ]);
        $this->assertInstanceOf(GraphRequest::class, $instance);
    }

    public function testAddHeadersWithStringValueAppendsNewHeader(): void {
        $this->defaultGraphRequest->addHeaders(['Connection' => 'keep-alive']);
        $this->assertEquals('keep-alive', $this->defaultGraphRequest->getHeaders()['Connection']);
    }

    public function testAddHeadersWithArrayOfValuesAppendsNewHeaders(): void {
        $values = ['de', 'en', 'fr'];
        $this->defaultGraphRequest->addHeaders(['Accept-Language' => $values]);
        $this->assertEquals($values, $this->defaultGraphRequest->getHeaders()['Accept-Language']);
    }

    public function testAddHeadersWithExistingHeaderNameOverwrites(): void {
        $this->assertEquals('application/json', $this->defaultGraphRequest->getHeaders()['Content-Type']);
        $this->defaultGraphRequest->addHeaders(['Content-Type' => 'text']);
        $this->assertEquals("text", $this->defaultGraphRequest->getHeaders()['Content-Type']);
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
