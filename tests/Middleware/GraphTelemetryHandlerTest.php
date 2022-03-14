<?php

namespace Microsoft\Graph\Core\Test\Middleware;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Microsoft\Graph\Core\GraphConstants;
use Microsoft\Graph\Core\Middleware\GraphMiddleware;
use Microsoft\Graph\Core\Middleware\Option\GraphTelemetryOption;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class GraphTelemetryHandlerTest extends TestCase
{
    private $expectedSdkVersionValue;

    protected function setUp(): void
    {
        $this->expectedSdkVersionValue = "graph-php-core/".GraphConstants::SDK_VERSION
                                            .", (featureUsage=0x00000000; hostOS=".php_uname('s')
                                            ."; runtimeEnvironment=PHP/".phpversion().")";
    }

    public function testHandlerSetsCorrectHeaderByDefault()
    {
        $mockResponse = [
            function (RequestInterface $request) {
                $this->assertTrue($request->hasHeader('client-request-id'));
                $this->assertTrue($request->hasHeader('SdkVersion'));
                $this->assertEquals($this->expectedSdkVersionValue, $request->getHeaderLine('SdkVersion'));
                return new Response(200);
            }
        ];
        $this->executeMockRequestWithGraphTelemetryHandler($mockResponse);
    }

    public function testHandlerSetsCorrectServiceLibraryVersions()
    {
        $mockResponse = [
            function (RequestInterface $request) {
                $expected = 'graph-php/2.0.0, '.$this->expectedSdkVersionValue;
                $this->assertTrue($request->hasHeader('client-request-id'));
                $this->assertTrue($request->hasHeader('SdkVersion'));
                $this->assertEquals($expected, $request->getHeaderLine('SdkVersion'));
                return new Response(200);
            }
        ];
        $this->executeMockRequestWithGraphTelemetryHandler($mockResponse, new GraphTelemetryOption('v1.0', '2.0.0'));
        $mockResponse = [
            function (RequestInterface $request) {
                $expected = 'graph-php-beta/2.0.0, '.$this->expectedSdkVersionValue;
                $this->assertTrue($request->hasHeader('client-request-id'));
                $this->assertTrue($request->hasHeader('SdkVersion'));
                $this->assertEquals($expected, $request->getHeaderLine('SdkVersion'));
                return new Response(200);
            }
        ];
        $this->executeMockRequestWithGraphTelemetryHandler($mockResponse, new GraphTelemetryOption('beta', '2.0.0'));
    }

    public function testRequestOptionsOverride()
    {
        $telemetryOption = new GraphTelemetryOption();
        $telemetryOption->setClientRequestId("abcd");
        $requestOptions = [
            GraphTelemetryOption::class => $telemetryOption
        ];
        $mockResponse = [
            function (RequestInterface $request) {
                $this->assertTrue($request->hasHeader('client-request-id'));
                $this->assertEquals("abcd", $request->getHeaderLine('client-request-id'));
                $this->assertTrue($request->hasHeader('SdkVersion'));
                $this->assertEquals($this->expectedSdkVersionValue, $request->getHeaderLine('SdkVersion'));
                return new Response(200);
            }
        ];
        $this->executeMockRequestWithGraphTelemetryHandler($mockResponse, null, $requestOptions);
    }

    private function executeMockRequestWithGraphTelemetryHandler(array $mockResponses, ?GraphTelemetryOption $graphTelemetryOption = null, array $requestOptions = [], ?Client $guzzleClient = null)
    {
        $mockHandler = new MockHandler($mockResponses);
        $handlerStack = new HandlerStack($mockHandler);
        $handlerStack->push(GraphMiddleware::telemetry($graphTelemetryOption));

        if (!$guzzleClient) {
            $guzzleClient = new Client(['handler' => $handlerStack, 'http_errors' => false]);
        }
        return $guzzleClient->get("/", $requestOptions);
    }
}
