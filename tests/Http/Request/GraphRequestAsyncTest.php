<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Test\Http\Request;


use Http\Client\HttpAsyncClient;
use Http\Promise\Promise;
use Microsoft\Graph\Exception\GraphServiceException;
use Microsoft\Graph\Http\GraphResponse;
use Microsoft\Graph\Test\Http\SampleGraphResponsePayload;
use Microsoft\Graph\Test\Http\TestModel;
use Microsoft\Graph\Test\TestData\Model\User;
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
        $promise = $this->defaultGraphRequest->setReturnType(StreamInterface::class)
            ->executeAsync();
        $this->assertInstanceOf(StreamInterface::class, $promise->wait());
    }

    public function testExecuteAsyncPromiseResolvesToGraphResponseIfNoReturnType(): void {
        MockHttpClientAsyncResponseConfig::configureWithFulfilledPromise($this->mockHttpClient);
        $promise = $this->defaultGraphRequest->executeAsync();
        $this->assertInstanceOf(GraphResponse::class, $promise->wait());
    }

    public function testExecuteAsyncPromiseThrowsExceptionForErrorResponse(): void {
        $this->expectException(GraphServiceException::class);
        MockHttpClientAsyncResponseConfig::statusCode(400)::configureWithFulfilledPromise(
            $this->mockHttpClient,
            SampleGraphResponsePayload::ERROR_PAYLOAD
        );
        $promise = $this->defaultGraphRequest->executeAsync();
        $promise->wait();
    }

    public function testExecuteAsyncResolvesToModelForModelReturnType(): void {
        MockHttpClientAsyncResponseConfig::statusCode()::configureWithFulfilledPromise(
            $this->mockHttpClient,
            SampleGraphResponsePayload::ENTITY_PAYLOAD
        );
        $promise = $this->defaultGraphRequest->setReturnType(User::class)->executeAsync();
        $this->assertInstanceOf(User::class, $promise->wait());
    }

    public function testExecuteAsyncResolvesToModelArrayForCollectionRequest(): void {
        MockHttpClientAsyncResponseConfig::statusCode()::configureWithFulfilledPromise(
            $this->mockHttpClient,
            SampleGraphResponsePayload::COLLECTION_PAYLOAD
        );
        $promise = $this->defaultGraphRequest->setReturnType(User::class)->executeAsync();
        $response = $promise->wait();
        $this->assertIsArray($response);
        $this->assertEquals(2, sizeof($response));
        $this->assertContainsOnlyInstancesOf(User::class, $response);
    }
}
