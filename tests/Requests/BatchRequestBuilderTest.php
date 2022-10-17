<?php

namespace Microsoft\Graph\Core\Test\Requests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Microsoft\Graph\Core\BaseGraphRequestAdapter;
use Microsoft\Graph\Core\Middleware\Option\GraphTelemetryOption;
use Microsoft\Graph\Core\Requests\BatchRequestBuilder;
use Microsoft\Graph\Core\Requests\BatchRequestContent;
use Microsoft\Graph\Core\Requests\BatchResponseContent;
use Microsoft\Kiota\Abstractions\ApiException;
use Microsoft\Kiota\Abstractions\RequestAdapter;
use Microsoft\Kiota\Serialization\Json\JsonParseNodeFactory;
use Microsoft\Kiota\Serialization\Json\JsonSerializationWriterFactory;
use PHPUnit\Framework\TestCase;

class BatchRequestBuilderTest extends TestCase
{
    private RequestAdapter $requestAdapter;

    protected function setUp(): void
    {
        $mockResponses = [
            new Response(200,
                ['content-type' => 'application/json'],
                Utils::streamFor(json_encode([
                    "responses" => [
                        [
                            "id" => 1,
                            "status" => 200,
                            "headers" => ['content-type' => 'text/plain'],
                            "body" => "text"
                        ]
                    ]
                ]))
            ),
            new Response(400),
            new Response(200,
                ['content-type' => 'application/json'],
                Utils::streamFor(json_encode([
                    "responses" => [
                        [
                            "id" => 1,
                            "status" => 200,
                            "headers" => ['content-type' => 'text/plain'],
                            "body" => "text"
                        ],
                        [
                            "id" => 2,
                            "status" => 424,
                            "headers" => ['content-type' => 'text/plain'],
                            "body" => "Failed Dependency"
                        ]
                    ]
                ]))
            )
        ];
        $mockHandler = new MockHandler($mockResponses);
        $handlerStack = new HandlerStack($mockHandler);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        $this->requestAdapter = new BaseGraphRequestAdapter(
            null, new GraphTelemetryOption(), new JsonParseNodeFactory(), new JsonSerializationWriterFactory(), $guzzleClient
        );
    }

    public function testPostAsync()
    {
        $requestBuilder = new BatchRequestBuilder($this->requestAdapter);
        $requestContent = new BatchRequestContent();
        $response = $requestBuilder->postAsync($requestContent)->wait();

        // Successful request
        $this->assertInstanceOf(BatchResponseContent::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['content-type' => 'application/json'], $response->getHeaders());
        $this->assertEquals(1, $response->getResponses()[0]->getId());
        $this->assertEquals(200, $response->getResponses()[0]->getStatusCode());
        $this->assertEquals(['content-type' => 'text/plain'], $response->getResponses()[0]->getHeaders());
        $this->assertEquals("text", $response->getResponses()[0]->getBody()->getContents());

        // Bad Request
        try {
            $response = $requestBuilder->postAsync($requestContent)->wait();
        } catch (\Exception $ex) {
            $this->assertInstanceOf(ApiException::class, $ex);
        }

        // Failed request
        $response = $requestBuilder->postAsync($requestContent)->wait();
        $this->assertInstanceOf(BatchResponseContent::class, $response);
        try {
            // Invalid request Id
            $response->getResponse(3);
        } catch (\Exception $ex) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $ex);
        }
        $this->assertEquals(424, $response->getResponse(2)->getStatusCode());
    }

}
