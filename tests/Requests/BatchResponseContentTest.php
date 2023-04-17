<?php

namespace Microsoft\Graph\Core\Test\Requests;

use GuzzleHttp\Psr7\Utils;
use Microsoft\Graph\Core\Requests\BatchResponseContent;
use Microsoft\Graph\Core\Requests\BatchResponseItem;
use Microsoft\Kiota\Abstractions\RequestInformation;
use Microsoft\Kiota\Abstractions\Serialization\ParseNodeFactoryRegistry;
use Microsoft\Kiota\Serialization\Json\JsonParseNodeFactory;
use PHPUnit\Framework\TestCase;

class BatchResponseContentTest extends TestCase
{
    private BatchResponseContent $batchResponseContent;

    protected function setUp(): void
    {
        $responseItem = new BatchResponseItem();
        $responseItem->setId('1');
        $responseItem->setHeaders(['Content-Type' => 'application/json']);
        $responseItem->setBody(Utils::streamFor(json_encode([
            'id' => '123',
            'name' => 'xyz'
        ])));
        $responses = [
            $responseItem, $responseItem
        ];
        $this->batchResponseContent = new BatchResponseContent();
        $this->batchResponseContent->setResponses($responses);
        ParseNodeFactoryRegistry::getDefaultInstance()
            ->contentTypeAssociatedFactories['application/json'] = new JsonParseNodeFactory();
        parent::setUp();
    }

    public function testGetResponseBody(): void
    {
        /** @var TestUserModel $response */
        $response = $this->batchResponseContent->getResponseBody('1', TestUserModel::class);
        $this->assertInstanceOf(TestUserModel::class, $response);
        $this->assertEquals('123', $response->getId());
        $this->assertEquals('xyz', $response->getName());
    }

    public function testGetResponseBodyWithInvalidIdThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->batchResponseContent->getResponse('2');
    }

    public function testGetResponseBodyThrowsExceptionGivenNonParsableClassName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->batchResponseContent->getResponseBody('1', RequestInformation::class);
    }

    public function testGetResponseBodyTriesBase64DecodingBeforeFailure(): void
    {
        $this->batchResponseContent->getResponse('1')->setBody(
            Utils::streamFor(base64_encode(json_encode(
                [
                    'id' => '123',
                    'name' => 'xyz'
                ]
            )))
        );
        $response = $this->batchResponseContent->getResponseBody('1', TestUserModel::class);
        $this->assertInstanceOf(TestUserModel::class, $response);
        $this->assertEquals('123', $response->getId());
        $this->assertEquals('xyz', $response->getName());
    }

    public function testGetResponseBodyTotalFailureThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->batchResponseContent->getResponse('1')->setBody(
            Utils::streamFor(base64_encode("{'key':val"))
        );
        $response = $this->batchResponseContent->getResponseBody('1', TestUserModel::class);
    }
}
