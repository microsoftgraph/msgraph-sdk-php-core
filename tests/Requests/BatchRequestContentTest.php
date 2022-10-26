<?php

namespace Microsoft\Graph\Core\Test\Requests;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Utils;
use Microsoft\Graph\Core\Requests\BatchRequestContent;
use Microsoft\Graph\Core\Requests\BatchRequestItem;
use Microsoft\Kiota\Abstractions\RequestInformation;
use Microsoft\Kiota\Serialization\Json\JsonSerializationWriter;
use PHPUnit\Framework\TestCase;

class BatchRequestContentTest extends TestCase
{
    private array $requests;
    private RequestInformation $requestInformation;

    protected function setUp(): void
    {
        $this->requestInformation = new RequestInformation();
        $this->requestInformation->httpMethod = "POST";
        $this->requestInformation->setUri(new Uri("/v1/users"));
        $this->requestInformation->setStreamContent(Utils::streamFor("abcd"));

        $this->requests = [$this->requestInformation, $this->requestInformation, $this->requestInformation];
    }

    public function testConstructor()
    {
        $requestContent = new BatchRequestContent([$this->requestInformation, $this->requestInformation]);
        $this->assertInstanceOf(BatchRequestContent::class, $requestContent);
    }

    public function testMaximumNumberOfRequests()
    {
        $this->expectException(\InvalidArgumentException::class);
        new BatchRequestContent(array_map(fn ($index) => $this->requestInformation, range(0, 22)));
    }

    public function testAddRequest()
    {
        $requestContent = new BatchRequestContent();
        $requestContent->addRequest(new BatchRequestItem($this->requestInformation));
        $this->assertEquals(1, sizeof($requestContent->getRequests()));
    }

    public function testAddRequestInformation()
    {
        $requestContent = new BatchRequestContent();
        $requestContent->addRequestInformation($this->requestInformation);
        $this->assertEquals(1, sizeof($requestContent->getRequests()));
    }

    public function testAddPsrRequest()
    {
        $requestContent = new BatchRequestContent();
        $requestContent->addPsrRequest(new Request("POST", "/v1/users", [], null));
        $this->assertEquals(1, sizeof($requestContent->getRequests()));
    }

    public function testRemoveByRequestId()
    {
        $requestItem = new BatchRequestItem($this->requestInformation);
        $requestContext = new BatchRequestContent([$requestItem]);
        $this->assertNotEmpty($requestContext->getRequests());
        $requestContext->remove($requestItem->getId());
        $this->assertEmpty($requestContext->getRequests());
    }

    public function testRemoveBatchRequestItem()
    {
        $requestItem = new BatchRequestItem($this->requestInformation);
        $requestContext = new BatchRequestContent([$requestItem]);
        $this->assertNotEmpty($requestContext->getRequests());
        $requestContext->removeBatchRequestItem($requestItem);
        $this->assertEmpty($requestContext->getRequests());
    }

    public function testSerialization()
    {
        $this->requestInformation->headers = ['content-type' => 'application/json'];
        $batchRequestContent = new BatchRequestContent([$this->requestInformation]);

        $serializationWriter = new JsonSerializationWriter();
        $serializationWriter->writeObjectValue(null, $batchRequestContent);

        $expectedJson = json_encode([
            'requests' => [
                [
                    "id" => $batchRequestContent->getRequests()[0]->getId(),
                    "method" => $this->requestInformation->httpMethod,
                    "url" => '/v1/users',
                    'headers' => ['content-type' => 'application/json'],
                    "body" => urlencode("abcd")
                ]
            ]
        ], JSON_UNESCAPED_SLASHES);

        $this->assertEquals($expectedJson, $serializationWriter->getSerializedContent()->getContents());
    }
}
