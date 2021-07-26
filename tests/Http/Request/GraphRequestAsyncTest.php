<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Test\Http\Request;


use GuzzleHttp\Psr7\Stream;
use Http\Client\HttpAsyncClient;
use Http\Promise\Promise;
use Microsoft\Graph\Http\GraphResponse;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\StreamInterface;

class GraphRequestAsyncTest extends BaseGraphRequestTest
{
    public function testExecuteAsyncWithCustomClientUsesCustomClient(): void {
        $customClient = $this->createMock(HttpAsyncClient::class);
        MockHttpClientAsyncResponseConfig::configureWithFulfilledPromise($customClient);
        $customClient->expects($this->once())->method('sendAsyncRequest');
        $this->mockHttpClient->expects($this->never())->method('sendAsyncRequest');
        $this->defaultGraphRequest->executeAsync($customClient);
    }

    public function testExecuteAsyncThrowsPsr18ExceptionsWithRejectedPromise(): void {
        $this->expectException(ClientExceptionInterface::class);
        MockHttpClientAsyncResponseConfig::configureWithRejectedPromise(
            $this->mockHttpClient,
            $this->createMock(NetworkExceptionInterface::class)
        );
        $resultPromise = $this->defaultGraphRequest->executeAsync();
        $resultPromise->wait();
    }

    public function testExecuteAsyncReturnsFulfilledPromise(): void {
        MockHttpClientAsyncResponseConfig::configureWithFulfilledPromise($this->mockHttpClient);
        $response = $this->defaultGraphRequest->executeAsync();
        $this->assertInstanceOf(Promise::class, $response);
    }

    public function testExecuteAsyncPromiseResolvesToStream(): void {
        MockHttpClientAsyncResponseConfig::configureWithFulfilledPromise(
            $this->mockHttpClient,
            SampleGraphResponsePayload::STREAM_PAYLOAD()
        );
        $promise = $this->defaultGraphRequest->setReturnType(Stream::class)
            ->executeAsync();
        $this->assertInstanceOf(StreamInterface::class, $promise->wait());
    }

    public function testExecuteAsyncPromiseResolvesToGraphResponseIfNoReturnType(): void {
        MockHttpClientAsyncResponseConfig::configureWithFulfilledPromise($this->mockHttpClient);
        $promise = $this->defaultGraphRequest->executeAsync();
        $this->assertInstanceOf(GraphResponse::class, $promise->wait());
    }

    public function testExecuteAsyncPromiseResolvesToGraphResponseForErrorPayload(): void {
        MockHttpClientAsyncResponseConfig::statusCode(400)::configureWithFulfilledPromise(
            $this->mockHttpClient,
            SampleGraphResponsePayload::ERROR_PAYLOAD
        );
        $promise = $this->defaultGraphRequest->executeAsync();
        $this->assertInstanceOf(GraphResponse::class, $promise->wait());
    }

    public function testExecuteAsyncResolvesToModelForModelReturnType(): void {
        MockHttpClientAsyncResponseConfig::configureWithFulfilledPromise(
            $this->mockHttpClient,
            SampleGraphResponsePayload::ENTITY_PAYLOAD
        );
        $promise = $this->defaultGraphRequest->setReturnType(TestModel::class)->executeAsync();
        $this->assertInstanceOf(TestModel::class, $promise->wait());
    }

    public function testExecuteAsyncResolvesToModelArrayForCollectionRequest(): void {
        MockHttpClientAsyncResponseConfig::configureWithFulfilledPromise(
            $this->mockHttpClient,
            SampleGraphResponsePayload::COLLECTION_PAYLOAD
        );
        $promise = $this->defaultGraphRequest->setReturnType(TestModel::class)->executeAsync();
        $response = $promise->wait();
        $this->assertIsArray($response);
        $this->assertEquals(2, sizeof($response));
        array_filter($response, function ($item) { $this->assertInstanceOf(TestModel::class, $item); });
    }
}
