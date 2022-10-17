<?php

namespace Microsoft\Graph\Core\Test\Requests;

use GuzzleHttp\Psr7\Utils;
use Microsoft\Graph\Core\Requests\BatchResponseContent;
use Microsoft\Graph\Core\Requests\BatchResponseItem;
use PHPUnit\Framework\TestCase;

class BatchResponseContentTest extends TestCase
{
    private BatchResponseContent $batchResponseContent;

    protected function setUp(): void
    {
        $responseItem = new BatchResponseItem();
        $responseItem->setId('1');
        $responseItem->setHeaders(['content-type' => 'application/json']);
        $responseItem->setBody(Utils::streamFor(json_encode([
            'id' => '123',
            'name' => 'xyz'
        ])));
        $responses = [
            $responseItem, $responseItem
        ];
        $this->batchResponseContent = new BatchResponseContent();
        $this->batchResponseContent->setResponses($responses);
        parent::setUp();
    }

    public function testGetResponseBody(): void
    {
        $response = $this->batchResponseContent->getResponseBody('1', TestUserModel::class);
        $this->assertInstanceOf(TestUserModel::class, $response);
        $this->assertEquals('123', $response->getId());
        $this->assertEquals('xyz', $response->getName());
    }
}
