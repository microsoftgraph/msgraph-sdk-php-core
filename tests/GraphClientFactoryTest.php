<?php


namespace Microsoft\Graph\Core\Test;


use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Microsoft\Graph\Core\GraphConstants;
use Microsoft\Graph\Core\Middleware\GraphRetryHandler;
use Microsoft\Graph\Core\Middleware\Option\GraphTelemetryOption;
use Microsoft\Graph\Core\NationalCloud;
use Microsoft\Graph\Core\GraphClientFactory;
use Microsoft\Graph\Core\HttpClientInterface;
use Microsoft\Kiota\Http\Constants;
use Microsoft\Kiota\Http\KiotaClientFactory;
use Psr\Http\Message\RequestInterface;

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

    public function testMiddlewareProcessing()
    {
        $guzzleVersion = ClientInterface::MAJOR_VERSION;
        $kiotaVersion = Constants::KIOTA_HTTP_CLIENT_VERSION;
        $userAgentHeaderValue = "GuzzleHttp/$guzzleVersion kiota-php/$kiotaVersion";
        $sdkVersionValue = 'graph-php-core/'.GraphConstants::SDK_VERSION
            .', (featureUsage='.sprintf('0x%08X', GraphRetryHandler::FEATURE_FLAG)
            .'; hostOS='.php_uname('s')
            .'; runtimeEnvironment=PHP/'.phpversion().')';
        $mockResponses = [
            function (RequestInterface $request) use ($userAgentHeaderValue, $sdkVersionValue) {
                // test telemetry handler
                $this->assertTrue($request->hasHeader('SdkVersion'));
                $this->assertEquals($sdkVersionValue, $request->getHeaderLine('SdkVersion'));
                $this->assertTrue($request->hasHeader('client-request-id'));
                // test parameter name decoding
                $this->assertEquals('https://graph.microsoft.com/users?$top=5', (string) $request->getUri());
                $this->assertTrue($request->hasHeader('User-Agent'));
                $this->assertEquals($userAgentHeaderValue, $request->getHeaderLine('User-Agent'));
                // trigger retry
                return new Response(429, ['Retry-After' => '1']);
            },
            function (RequestInterface $retriedRequest) use ($userAgentHeaderValue) {
                $this->assertTrue($retriedRequest->hasHeader('SdkVersion'));
                $this->assertTrue($retriedRequest->hasHeader('client-request-id'));
                $sdkVersionValue = 'graph-php-core/'.GraphConstants::SDK_VERSION
                    .', (featureUsage='.sprintf('0x%08X', GraphRetryHandler::FEATURE_FLAG)
                    .'; hostOS='.php_uname('s')
                    .'; runtimeEnvironment=PHP/'.phpversion().')';
                $this->assertEquals($sdkVersionValue, $retriedRequest->getHeaderLine('SdkVersion'));
                $this->assertEquals('https://graph.microsoft.com/users?$top=5', (string) $retriedRequest->getUri());
                $this->assertTrue($retriedRequest->hasHeader('User-Agent'));
                $this->assertEquals($userAgentHeaderValue, $retriedRequest->getHeaderLine('User-Agent'));
                $this->assertTrue($retriedRequest->hasHeader('Retry-Attempt'));
                $this->assertEquals('1', $retriedRequest->getHeaderLine('Retry-Attempt'));
                // trigger redirect
                return new Response(302, ['Location' => 'https://graph.microsoft.com/users?%24top=5']);
            },
            function (RequestInterface $request) use ($userAgentHeaderValue, $sdkVersionValue) {
                // test telemetry handler
                $this->assertTrue($request->hasHeader('SdkVersion'));
                $this->assertEquals($sdkVersionValue, $request->getHeaderLine('SdkVersion'));
                $this->assertTrue($request->hasHeader('client-request-id'));
                // test no parameter name decoding. Redirect happens as is
                $this->assertEquals('https://graph.microsoft.com/users?%24top=5', (string) $request->getUri());
                $this->assertTrue($request->hasHeader('User-Agent'));
                $this->assertEquals($userAgentHeaderValue, $request->getHeaderLine('User-Agent'));
                return new Response(200);
            }
        ];
        $middlewareStack = GraphClientFactory::getDefaultHandlerStack();
        $middlewareStack->setHandler(new MockHandler($mockResponses));
        $mockClient = new Client(['handler' => $middlewareStack]);
        $mockClient->get('https://graph.microsoft.com/users?%24top=5');
    }

}
