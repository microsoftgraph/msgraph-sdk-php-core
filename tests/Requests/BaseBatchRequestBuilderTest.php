<?php

namespace Microsoft\Graph\Core\Test\Requests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Microsoft\Graph\Core\BaseGraphRequestAdapter;
use Microsoft\Graph\Core\Middleware\Option\GraphTelemetryOption;
use Microsoft\Graph\Core\Requests\BaseBatchRequestBuilder;
use Microsoft\Graph\Core\Requests\BatchRequestContent;
use Microsoft\Graph\Core\Requests\BatchResponseContent;
use Microsoft\Kiota\Abstractions\ApiException;
use Microsoft\Kiota\Abstractions\RequestAdapter;
use Microsoft\Kiota\Abstractions\Serialization\Parsable;
use Microsoft\Kiota\Abstractions\Serialization\ParseNode;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriter;
use Microsoft\Kiota\Serialization\Json\JsonParseNodeFactory;
use Microsoft\Kiota\Serialization\Json\JsonSerializationWriterFactory;
use PHPUnit\Framework\TestCase;

class BaseBatchRequestBuilderTest extends TestCase
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
                            "headers" => ['content-type' => 'text/plain', 'content-length' => '10'],
                            "body" => "text"
                        ]
                    ]
                ]))
            ),
            new Response(400, ['content-type' => 'application/json'], Utils::streamFor(json_encode(['errorMsg' => 'bad request']))),
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
            new GraphTelemetryOption(), null, new JsonParseNodeFactory(), new JsonSerializationWriterFactory(), $guzzleClient
        );
    }

    public function testPostAsync()
    {
        $requestBuilder = new BaseBatchRequestBuilder($this->requestAdapter);
        $requestContent = new BatchRequestContent();
        $response = $requestBuilder->postAsync($requestContent)->wait();

        // Successful request
        $this->assertInstanceOf(BatchResponseContent::class, $response);
        $this->assertEquals(1, $response->getResponses()[0]->getId());
        $this->assertEquals(200, $response->getResponses()[0]->getStatusCode());
        $this->assertEquals(['content-type' => 'text/plain', 'content-length' => '10'], $response->getResponses()[0]->getHeaders());
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

    public function testErrorMappingDeserialization()
    {
        $mappings = [
            '4XX' => [TestErrorModel::class, 'create']
        ];
        $batch = new BaseBatchRequestBuilder($this->requestAdapter, $mappings);
        // first response is successful
        $response = $batch->postAsync(new BatchRequestContent())->wait();
        // next response is 400
        try {
            $response = $batch->postAsync(new BatchRequestContent())->wait();
        } catch (\Exception $ex) {
            $this->assertInstanceOf(ApiException::class, $ex);
            $this->assertInstanceOf(TestErrorModel::class, $ex);
            $this->assertEquals("bad request", $ex->getErrorMsg());
        }
    }

}

class TestErrorModel extends ApiException implements Parsable
{
    private string $errorMsg;

    public static function create(ParseNode $parseNode): TestErrorModel {
        return new TestErrorModel();
    }

    public function serialize(SerializationWriter $writer): void
    {
    }

    public function getFieldDeserializers(): array
    {
        return [
            'errorMsg' => fn (ParseNode $n) => $this->setErrorMsg($n->getStringValue())
        ];
    }

    /**
     * @return string
     */
    public function getErrorMsg(): string
    {
        return $this->errorMsg;
    }

    /**
     * @param string $errorMsg
     */
    public function setErrorMsg(string $errorMsg): void
    {
        $this->errorMsg = $errorMsg;
    }
}
