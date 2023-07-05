<?php

namespace Microsoft\Graph\Core\Test\Requests;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Utils;
use InvalidArgumentException;
use Microsoft\Graph\Core\Requests\BatchRequestItem;
use Microsoft\Kiota\Abstractions\HttpMethod;
use Microsoft\Kiota\Abstractions\RequestInformation;
use Microsoft\Kiota\Serialization\Json\JsonSerializationWriter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class BatchRequestItemTest extends TestCase
{
    private RequestInformation $requestInformation;
    private RequestInterface $psrRequest;

    protected function setUp(): void
    {
        $this->requestInformation = new RequestInformation();
        $this->requestInformation->httpMethod = HttpMethod::GET;
        $this->requestInformation->addHeaders(["test" => ["value", "value2"]]);
        $this->requestInformation->setUri(new Uri('https://graph.microsoft.com/v1.0/users?$top=2'));

        $this->psrRequest = new Request(HttpMethod::POST, "https://graph.microsoft.com/beta/users", ["key" => ["value1", "value2"]], Utils::streamFor(json_encode(["key" => "val"])));
    }


    public function testConstructor()
    {
        $batchRequestItem = new BatchRequestItem($this->requestInformation);
        $this->assertInstanceOf(BatchRequestItem::class, $batchRequestItem);
        $this->assertNotEmpty($batchRequestItem->getId()); // default ID is set
        $this->assertEquals($this->requestInformation->httpMethod, $batchRequestItem->getMethod());
        $this->assertEquals($this->requestInformation->getHeaders()->getAll(), $batchRequestItem->getHeaders());
        $this->assertEquals('/users?$top=2', $batchRequestItem->getUrl()); // relative URL is set
    }

    public function testInvalidRequestInformationThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->requestInformation->httpMethod = '';
        new BatchRequestItem($this->requestInformation);
    }

    public function testCreateWithPsrRequest()
    {
        $batchRequestItem = BatchRequestItem::createWithPsrRequest($this->psrRequest);
        $this->assertInstanceOf(BatchRequestItem::class, $batchRequestItem);
        $this->assertNotEmpty($batchRequestItem->getId()); // default ID is set
        $this->assertEquals($this->psrRequest->getMethod(), $batchRequestItem->getMethod());
        $this->assertEquals(['host' => ['graph.microsoft.com'], 'key' => ['value1', 'value2']], $batchRequestItem->getHeaders());
        $this->assertEquals('/users', $batchRequestItem->getUrl()); // relative URL is set
    }

    public function testDependsOn()
    {
        $batchRequestItem1 = new BatchRequestItem($this->requestInformation);
        $batchRequestItem2 = new BatchRequestItem($this->requestInformation);
        $batchRequestItem1->dependsOn([$batchRequestItem2]);
        $this->assertEquals($batchRequestItem2->getId(), $batchRequestItem1->getDependsOn()[0]);
    }

    public function testSerialization()
    {
        $batchRequestItem1 = BatchRequestItem::createWithPsrRequest($this->psrRequest);
        $batchRequestItem2 = new BatchRequestItem($this->requestInformation);
        $batchRequestItem3 = new BatchRequestItem($this->requestInformation, "1");
        $batchRequestItem1->dependsOn([$batchRequestItem2, $batchRequestItem3]);

        $jsonSerializationWriter = new JsonSerializationWriter();
        $jsonSerializationWriter->writeObjectValue(null, $batchRequestItem1);

        $this->psrRequest->getBody()->rewind();
        $expectedJson = json_encode([
            "id" => $batchRequestItem1->getId(),
            "method" => $batchRequestItem1->getMethod(),
            "url" => '/users',
            "dependsOn" => [$batchRequestItem2->getId(), $batchRequestItem3->getId()],
            "headers" => ['host' => 'graph.microsoft.com', 'key' => 'value1, value2'],
            "body" => ['key' => 'val']
        ], JSON_UNESCAPED_SLASHES);

        $this->assertEquals($expectedJson, $jsonSerializationWriter->getSerializedContent()->getContents());
    }

    public function testSettingInvalidUrlPathThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $item = new BatchRequestItem($this->requestInformation);
        $item->setId('1243');
        $item->setMethod('GET');
        $item->setHeaders([]);
        $item->getFieldDeserializers();
        $item->setUrl('https://a.b.com$1');
    }

    public function testSetEmptyUrlThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $item = new BatchRequestItem($this->requestInformation);
        $item->setUrl('');
    }

    public function testMePlaceholderIsReplacedInUrls(): void
    {
        $meUrlPath = '/me';
        $tokenToReplaceUrl = 'https://graph.microsoft.com/v1.0/users/me-token-to-replace';

        $this->requestInformation->setUri(new Uri('https://graph.microsoft.com/beta/me'));
        $this->assertEquals($meUrlPath, (new BatchRequestItem($this->requestInformation))->getUrl());

        $this->requestInformation->setUri(new Uri($tokenToReplaceUrl));
        $this->assertEquals($meUrlPath, (new BatchRequestItem($this->requestInformation))->getUrl());

        $this->requestInformation->setUri(new Uri("$tokenToReplaceUrl/messages"));
        $this->assertEquals("$meUrlPath/messages", (new BatchRequestItem($this->requestInformation))->getUrl());

        $this->requestInformation->setUri(new Uri("$tokenToReplaceUrl/messages/123"));
        $this->assertEquals("$meUrlPath/messages/123", (new BatchRequestItem($this->requestInformation))->getUrl());
    }
}
